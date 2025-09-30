<?php

return [
    'default_stability_window_days' => 14,
    'plans' => [
        'communities_data_convergence' => [
            'name' => 'Communities Data Convergence',
            'description' => 'Gradually introduce the community-first data model while keeping legacy forum artefacts online until parity tests pass.',
            'service_owner' => 'Communities Platform Guild',
            'dependencies' => [
                'search.meilisearch-clusters',
                'observability.prometheus-core',
                'billing.stripe-entitlements-v2',
            ],
            'feature_flags' => [
                'backend' => [
                    'community_feed_v2',
                    'community_geo_boundaries',
                ],
                'mobile' => [
                    'communities-feed-v2',
                    'community-points-tracker',
                ],
                'web' => [
                    'admin-community-dashboard',
                ],
            ],
            'minimum_versions' => [
                'api' => '2024.08',
                'mobile' => '3.2.0',
                'web' => '2024.07',
            ],
            'phases' => [
                [
                    'key' => 'expand-schema-and-services',
                    'name' => 'Expand schema & services',
                    'type' => 'expand',
                    'stability_window_days' => 10,
                    'steps' => [
                        [
                            'key' => 'ddl-community-core',
                            'name' => 'Deploy community core tables',
                            'summary' => 'Add `community_posts`, `community_post_metrics`, shard tables, and audit logs without touching forum runtime.',
                            'operations' => [
                                'Apply Laravel migrations 2024_08_01_010000 through 2024_08_01_040000.',
                                'Provision Aurora read replica for heavy backfill traffic.',
                                'Deploy read-side repository classes behind feature flag `community_feed_v2`.',
                            ],
                            'backfill' => [
                                'Schedule `php artisan community:seed-projections --window=30-days` to preload heatmap aggregates.',
                                'Enable dual-write observers for posts/comments with LaunchDarkly targeting staff accounts only.',
                            ],
                            'verification' => [
                                'DBA validates index creation and replication lag < 2s.',
                                'Run contract tests `tests/Feature/Community/PreviewFeedTest.php` in canary env.',
                            ],
                            'rollback' => [
                                'Disable feature flag `community_feed_v2`.',
                                'Run `php artisan community:truncate-preview --force` to purge new tables.',
                            ],
                            'owners' => ['Data Engineering', 'Communities Platform'],
                            'dependencies' => [],
                            'stability_window_days' => 7,
                        ],
                        [
                            'key' => 'api-preview-surface',
                            'name' => 'Expose preview API surface',
                            'summary' => 'Introduce versioned `/api/v1/communities` endpoints with preview headers for mobile/web beta clients.',
                            'operations' => [
                                'Deploy API transformers returning legacy-compatible payloads plus v2 metadata.',
                                'Widen Sanctum abilities to include `community.preview` for beta testers.',
                            ],
                            'backfill' => [
                                'Warm API cache by replaying 24h of popular community queries from logs.',
                                'Seed CDN edge caches with new payload signatures.',
                            ],
                            'verification' => [
                                'Synthetic monitors hitting `/v1/communities?preview=true` stay <250ms p95.',
                                'QA smoke tests for moderation queue and geo boundaries succeed.',
                            ],
                            'rollback' => [
                                'Toggle LaunchDarkly segment `beta_community_preview` off.',
                                'Redeploy API package reverting to commit tag `community-preview-baseline`.',
                            ],
                            'owners' => ['API Platform'],
                            'dependencies' => ['ddl-community-core'],
                        ],
                    ],
                ],
                [
                    'key' => 'backfill-and-dual-write',
                    'name' => 'Backfill & dual write',
                    'type' => 'backfill',
                    'stability_window_days' => 14,
                    'steps' => [
                        [
                            'key' => 'activity-backfill',
                            'name' => 'Replay historic activity',
                            'summary' => 'Rehydrate new projections with historic forum, comment, and reaction data.',
                            'operations' => [
                                'Run `php artisan community:backfill --chunk=1000 --since="-90 days"` on background worker pool.',
                                'Stream media metadata into `community_post_metrics` using AWS Data Migration Service (DMS).',
                            ],
                            'backfill' => [
                                'Enable retry queue with exponential backoff for DMS failures.',
                                'Hash compare row counts vs legacy forum nightly and log to Prometheus gauge `community_backfill_drift`.',
                            ],
                            'verification' => [
                                'Data QA dashboard shows <0.5% variance across post counts.',
                                'Mobile beta build 3.2.0 renders feed without 5xx errors for 48h.',
                            ],
                            'rollback' => [
                                'Pause DMS tasks and artisan backfill job.',
                                'Archive partially migrated rows to S3 `community-migrations-drain` bucket.',
                            ],
                            'owners' => ['Data Engineering', 'Reliability Engineering'],
                            'dependencies' => ['api-preview-surface'],
                        ],
                        [
                            'key' => 'dual-write-cutover',
                            'name' => 'Cut over to dual write',
                            'summary' => 'Enable synchronous writes to both legacy forum tables and new community projections.',
                            'operations' => [
                                'Enable LaunchDarkly flag `community_dual_write` for 10% of traffic, ramp hourly.',
                                'Instrument Laravel event listeners to queue reconciliation jobs on failure.',
                            ],
                            'backfill' => [
                                'Background job `community:verify-dual-write` reconciles last 15 minutes of activity every 5 minutes.',
                            ],
                            'verification' => [
                                'Alert threshold: dual-write reconciliation drift < 50 rows over 15 minutes.',
                                'Grafana dashboard `community-cutover` stays green for 24h.',
                            ],
                            'rollback' => [
                                'Disable flag `community_dual_write` and flush Laravel queue `community-high`.',
                                'Re-run drift reconciliation after rollback to ensure no orphaned records.',
                            ],
                            'owners' => ['Communities Platform', 'Site Reliability'],
                            'dependencies' => ['activity-backfill'],
                        ],
                    ],
                ],
                [
                    'key' => 'contract-legacy',
                    'name' => 'Contract legacy artefacts',
                    'type' => 'contract',
                    'stability_window_days' => 7,
                    'steps' => [
                        [
                            'key' => 'read-path-cutover',
                            'name' => 'Route reads to new projections',
                            'summary' => 'Point all feed, geo, and notification queries exclusively to community projections.',
                            'operations' => [
                                'Switch API feature flag `community_feed_v2` to 100% of traffic.',
                                'Remove legacy forum relationships from Eloquent models.',
                            ],
                            'backfill' => [
                                'Finalize incremental sync for straggler attachments (last 7 days).',
                            ],
                            'verification' => [
                                'Zero queries per minute observed against legacy forum tables for 48h.',
                                'Synthetic monitors confirm 200 responses for `/communities/{id}/feed` from three regions.',
                            ],
                            'rollback' => [
                                'Restore read routing by toggling LaunchDarkly segment `community_feed_v2` to previous percentage.',
                                'Restore Eloquent relationships from git tag `forum-read-fallback`.',
                            ],
                            'owners' => ['API Platform'],
                            'dependencies' => ['dual-write-cutover'],
                        ],
                        [
                            'key' => 'decommission-legacy',
                            'name' => 'Decommission forum tables',
                            'summary' => 'Archive and remove obsolete forum tables once parity confirmed.',
                            'operations' => [
                                'Export legacy tables to S3 Glacier with 90-day retention.',
                                'Drop triggers and stored procedures tied to forum schema.',
                            ],
                            'backfill' => [
                                'Snapshot final record counts and store in compliance vault.',
                            ],
                            'verification' => [
                                'Compliance sign-off recorded in Jira MIG-221.',
                                'No new writes detected against legacy tables for 7 consecutive days.',
                            ],
                            'rollback' => [
                                'Restore Glacier snapshot into shadow schema `forum_legacy_restore`.',
                                'Re-enable limited read-only API endpoints if stakeholder sign-off requires.',
                            ],
                            'owners' => ['Data Engineering', 'Compliance'],
                            'dependencies' => ['read-path-cutover'],
                        ],
                    ],
                ],
            ],
        ],
        'messaging_pipeline_split' => [
            'name' => 'Messaging Pipeline Split',
            'description' => 'Separate transactional email/SMS from community notifications for performance and compliance.',
            'service_owner' => 'Messaging Platform Squad',
            'dependencies' => [
                'observability.alerting-v2',
                'compliance.policy-engine',
            ],
            'feature_flags' => [
                'backend' => ['messaging_pipeline_v2'],
                'mobile' => ['push-notifications-v2'],
                'web' => ['notifications-center-v2'],
            ],
            'minimum_versions' => [
                'api' => '2024.07',
                'mobile' => '3.3.0',
                'web' => '2024.06',
            ],
            'phases' => [
                [
                    'key' => 'expand',
                    'name' => 'Expand infrastructure',
                    'type' => 'expand',
                    'steps' => [
                        [
                            'key' => 'sns-topic-provisioning',
                            'name' => 'Provision SNS/SQS topics',
                            'summary' => 'Create dedicated fan-out for push notifications with encryption at rest.',
                            'operations' => [
                                'Deploy Terraform stack `messaging-pipeline` with encrypted SNS topics.',
                                'Update Laravel config `queue.connections.sns` with topic ARNs.',
                            ],
                            'backfill' => [
                                'Seed IAM roles and rotate access keys for messaging lambdas.',
                            ],
                            'verification' => [
                                'CloudWatch alarms show delivery latency < 2s at p95 during canary.',
                                'Run integration test suite `tests/Feature/Notifications/MessagingPipelineTest.php`.',
                            ],
                            'rollback' => [
                                'Revert Terraform stack to version `messaging-pipeline@1.2.3`.',
                                'Disable queue worker `messaging-realtime` via Horizon.',
                            ],
                            'owners' => ['Messaging Platform', 'Cloud Engineering'],
                        ],
                    ],
                ],
                [
                    'key' => 'backfill',
                    'name' => 'Backfill preferences',
                    'type' => 'backfill',
                    'steps' => [
                        [
                            'key' => 'preference-migration',
                            'name' => 'Migrate notification preferences',
                            'summary' => 'Normalize stored preferences and ensure opt-outs respect regulatory requirements.',
                            'operations' => [
                                'Run `php artisan messaging:backfill-preferences --batch=5000` nightly.',
                                'Publish migration progress to Slack channel #messaging-cutover.',
                            ],
                            'backfill' => [
                                'Execute GDPR export dry-run for random sample of 50 users.',
                            ],
                            'verification' => [
                                'Audit log entries show 100% of opt-outs preserved.',
                                'Mobile 3.3.0 beta receives only opted-in pushes during smoke tests.',
                            ],
                            'rollback' => [
                                'Stop artisan job and restore snapshot `preferences_pre_migration`.',
                                'Invalidate Redis cache `notification.preferences.*`.',
                            ],
                            'owners' => ['Messaging Platform', 'Compliance'],
                        ],
                    ],
                ],
                [
                    'key' => 'contract',
                    'name' => 'Contract legacy pipeline',
                    'type' => 'contract',
                    'steps' => [
                        [
                            'key' => 'switch-producers',
                            'name' => 'Switch producers to v2 pipeline',
                            'summary' => 'Flip feature flags so all producers publish to the new pipeline.',
                            'operations' => [
                                'Update Laravel notification channel bindings to route through SNS transport.',
                                'Decommission cron `legacy-notification-digest` after verifying replacements.',
                            ],
                            'backfill' => [
                                'Export final send logs to Redshift for historical analytics.',
                            ],
                            'verification' => [
                                'Queue depth for legacy pipeline drains to zero.',
                                'Alerting dashboards show no increase in delivery failures for 72h.',
                            ],
                            'rollback' => [
                                'Re-enable legacy channel binding in config and redeploy previous release tag.',
                            ],
                            'owners' => ['Messaging Platform', 'Site Reliability'],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
