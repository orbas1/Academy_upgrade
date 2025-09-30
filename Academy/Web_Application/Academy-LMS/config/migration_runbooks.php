<?php

return [
    'default_maintenance_window_minutes' => 30,
    'runbooks' => [
        'communities_schema_v1' => [
            'name' => 'Communities Schema v1 rollout',
            'description' => 'Step-by-step execution playbook for introducing the community-first data model in production.',
            'plan_key' => 'communities_data_convergence',
            'service_owner' => [
                'Communities Platform Guild',
                'Data Engineering',
            ],
            'approvers' => [
                'Site Reliability Engineering',
                'Product Operations',
                'Compliance',
            ],
            'maintenance_window_minutes' => 45,
            'communication_channels' => [
                '#on-call-community',
                '#ops-war-room',
                'ops@academy.example',
            ],
            'steps' => [
                [
                    'key' => 'preflight-safety-checks',
                    'name' => 'Preflight safety & readiness checks',
                    'type' => 'precheck',
                    'owner_roles' => ['Site Reliability', 'DBA on-call'],
                    'expected_runtime_minutes' => 10,
                    'maintenance_window_minutes' => 15,
                    'prechecks' => [
                        'Confirm latest automated snapshot completed within last 4 hours (RDS automated backup dashboard).',
                        'Verify replication lag on reader instances is < 1s using `SELECT * FROM performance_schema.replication_group_member_stats;`.',
                        'Ensure `community_feed_v2` LaunchDarkly flag is disabled in production.',
                        'Check `/v1/ops/migration-plan?phase=expand-schema-and-services` returns `status=ready` in observability dashboard.',
                        'Notify #ops-war-room and confirm acknowledgements from Data Engineering + SRE leads.',
                    ],
                    'execution' => [
                        'Run `php artisan migration:plan communities_data_convergence --phase=expand-schema-and-services` and archive output to Confluence page MIG-OPS-11.2.',
                        'Execute `aws rds describe-db-instances --db-instance-identifier academy-cluster` to confirm storage headroom > 20%.',
                    ],
                    'verification' => [
                        'Sign-off recorded in Jira MIG-310 by SRE duty engineer.',
                        'PagerDuty maintenance window activated for service "Academy API".',
                    ],
                    'rollback' => [
                        'If any precheck fails, abort the window, post status in #ops-war-room, and reschedule within 24h.',
                        'Re-enable standard alerts in PagerDuty if they were paused.',
                    ],
                    'dependencies' => [],
                    'telemetry' => [
                        'Grafana dashboard `Community Migration / Preflight` healthy (all panels green).',
                    ],
                    'related_migrations' => [],
                    'related_commands' => ['migration:plan'],
                    'notes' => 'Must be re-run if maintenance window exceeds 60 minutes before schema deployment begins.',
                ],
                [
                    'key' => 'deploy-community-core-schema',
                    'name' => 'Deploy community core schema',
                    'type' => 'migration',
                    'owner_roles' => ['DBA on-call', 'Data Engineering'],
                    'expected_runtime_minutes' => 8,
                    'maintenance_window_minutes' => 15,
                    'dependencies' => ['preflight-safety-checks'],
                    'prechecks' => [
                        'Confirm `Schema::hasTable(\'communities\')` returns false in production tinker session.',
                        'Validate change request MIG-CR-884 is approved by Compliance and Product Ops.',
                    ],
                    'execution' => [
                        'Run `php artisan migrate --path=database/migrations/2024_12_24_000000_create_community_core_tables.php --force`.',
                        'Verify migration log entry emitted to CloudWatch group `/academy/api/migrations`.',
                    ],
                    'verification' => [
                        'Execute `php artisan migrate:status --path=database/migrations/2024_12_24_000000_create_community_core_tables.php` and confirm status `Ran`.',
                        'Run `DESCRIBE communities;` and ensure columns `slug`, `visibility`, `join_policy` exist.',
                        'Check `SHOW INDEX FROM community_categories;` includes `community_categories_slug_unique`.',
                    ],
                    'rollback' => [
                        'Execute `php artisan migrate:rollback --path=database/migrations/2024_12_24_000000_create_community_core_tables.php --step=1 --force`.',
                        'Drop partially created tables manually if rollback fails: `DROP TABLE IF EXISTS communities, community_categories, geo_places CASCADE;`.',
                    ],
                    'telemetry' => [
                        'Prometheus alert `community_schema_migration_errors` remains at 0.',
                    ],
                    'related_migrations' => ['2024_12_24_000000_create_community_core_tables'],
                    'related_commands' => ['migrate', 'migrate:status', 'migrate:rollback'],
                    'notes' => 'Ensure application read replicas are in sync before moving to engagement layer.',
                ],
                [
                    'key' => 'deploy-engagement-layer-schema',
                    'name' => 'Deploy engagement layer schema',
                    'type' => 'migration',
                    'owner_roles' => ['DBA on-call', 'Communities Platform Guild'],
                    'expected_runtime_minutes' => 12,
                    'maintenance_window_minutes' => 20,
                    'dependencies' => ['deploy-community-core-schema'],
                    'prechecks' => [
                        'Confirm community core tables exist via `SELECT COUNT(*) FROM information_schema.tables WHERE table_name = \"communities\";`.',
                        'Ensure Horizon queue `communities-backfill` is paused (`php artisan horizon:pause communities-backfill`).',
                    ],
                    'execution' => [
                        'Run `php artisan migrate --path=database/migrations/2024_12_24_000100_create_community_engagement_tables.php --force`.',
                        'Tail migration logs: `tail -f storage/logs/laravel.log` to monitor progress.',
                    ],
                    'verification' => [
                        'Confirm `community_posts`, `community_post_comments`, and `community_members` tables created with soft delete columns.',
                        'Run `SHOW CREATE TABLE community_post_comments;` to confirm self-referential foreign key on `parent_id`.',
                        'Execute `SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = \"community_post_likes\";` expecting > 0.',
                    ],
                    'rollback' => [
                        'Run `php artisan migrate:rollback --path=database/migrations/2024_12_24_000100_create_community_engagement_tables.php --step=1 --force`.',
                        'If rollback fails due to foreign key constraints, disable checks `SET FOREIGN_KEY_CHECKS=0;` before dropping tables, then re-enable.',
                    ],
                    'telemetry' => [
                        'RDS performance insights shows execution time < 5 minutes; alert threshold is 10 minutes.',
                    ],
                    'related_migrations' => ['2024_12_24_000100_create_community_engagement_tables'],
                    'related_commands' => ['migrate', 'migrate:rollback'],
                    'notes' => 'Leave Horizon queues paused until index optimization completes.',
                ],
                [
                    'key' => 'apply-feed-indexes-and-partitions',
                    'name' => 'Apply feed indexes and partitions',
                    'type' => 'migration',
                    'owner_roles' => ['DBA on-call'],
                    'expected_runtime_minutes' => 6,
                    'maintenance_window_minutes' => 10,
                    'dependencies' => ['deploy-engagement-layer-schema'],
                    'prechecks' => [
                        'Confirm MySQL engine is InnoDB and partitioning supported: `SHOW PLUGINS LIKE \"partition%\";`.',
                        'Ensure `community_posts` table row count < 5M during initial rollout to avoid long locks.',
                    ],
                    'execution' => [
                        'Run `php artisan migrate --path=database/migrations/2024_12_24_000200_optimize_community_indexes_and_partitions.php --force`.',
                        'Monitor MySQL process list with `SHOW PROCESSLIST;` to ensure no long-running blockers.',
                    ],
                    'verification' => [
                        'Check `SHOW INDEX FROM community_posts WHERE Key_name = \"community_posts_feed_idx\";` returns rows.',
                        'Query `SELECT PARTITION_NAME FROM information_schema.PARTITIONS WHERE TABLE_NAME = \"community_posts\" AND PARTITION_NAME IS NOT NULL;` expecting 16 rows.',
                    ],
                    'rollback' => [
                        'Execute `php artisan migrate:rollback --path=database/migrations/2024_12_24_000200_optimize_community_indexes_and_partitions.php --step=1 --force`.',
                        'If partition rollback fails, run manual statement `ALTER TABLE community_posts REMOVE PARTITIONING;`.',
                    ],
                    'telemetry' => [
                        'Grafana panel `Community Feed Query p95` remains < 180ms after index creation.',
                    ],
                    'related_migrations' => ['2024_12_24_000200_optimize_community_indexes_and_partitions'],
                    'related_commands' => ['migrate', 'migrate:rollback'],
                    'notes' => 'Do not resume Horizon until partitioning confirmed to prevent backlog ingest hitting missing indexes.',
                ],
                [
                    'key' => 'deploy-supporting-tables',
                    'name' => 'Deploy supporting preference/search tables',
                    'type' => 'migration',
                    'owner_roles' => ['Data Engineering'],
                    'expected_runtime_minutes' => 4,
                    'maintenance_window_minutes' => 10,
                    'dependencies' => ['apply-feed-indexes-and-partitions'],
                    'prechecks' => [
                        'Ensure Meilisearch cluster is healthy via `/health` endpoint.',
                    ],
                    'execution' => [
                        'Run `php artisan migrate --path=database/migrations/2024_12_24_000900_create_search_saved_queries_table.php --force`.',
                        'Run `php artisan migrate --path=database/migrations/2024_12_24_001100_create_community_notification_preferences_table.php --force`.',
                    ],
                    'verification' => [
                        'Check `DESCRIBE search_saved_queries;` includes columns `filters` and `flags` (JSON).',
                        'Run `SELECT COUNT(*) FROM information_schema.TABLES WHERE table_name IN (\"search_saved_queries\", \"community_notification_preferences\");` expecting 2.',
                    ],
                    'rollback' => [
                        'Execute `php artisan migrate:rollback --path=database/migrations/2024_12_24_001100_create_community_notification_preferences_table.php --step=1 --force`.',
                        'Execute `php artisan migrate:rollback --path=database/migrations/2024_12_24_000900_create_search_saved_queries_table.php --step=1 --force`.',
                    ],
                    'telemetry' => [
                        'Application log channel `search` shows no schema-related errors post-deploy.',
                    ],
                    'related_migrations' => [
                        '2024_12_24_000900_create_search_saved_queries_table',
                        '2024_12_24_001100_create_community_notification_preferences_table',
                    ],
                    'related_commands' => ['migrate', 'migrate:rollback'],
                    'notes' => 'Allows saved search rollout and notification preference gating immediately after schema availability.',
                ],
                [
                    'key' => 'seed-foundation-data',
                    'name' => 'Seed foundation datasets',
                    'type' => 'data',
                    'owner_roles' => ['Communities Platform Guild'],
                    'expected_runtime_minutes' => 5,
                    'maintenance_window_minutes' => 10,
                    'dependencies' => ['deploy-supporting-tables'],
                    'prechecks' => [
                        'Confirm artisan maintenance mode is enabled to avoid user writes during seeding (`php artisan down`).',
                    ],
                    'execution' => [
                        'Run `php artisan db:seed --class=Database\\Seeders\\Communities\\CommunityFoundationSeeder --force`.',
                        'Sync S3 assets for community icons using `aws s3 sync s3://academy-assets/communities/icons storage/app/public/communities/icons`.',
                    ],
                    'verification' => [
                        'Query `SELECT COUNT(*) FROM community_categories;` expecting >= 5 categories.',
                        'API smoke test: `curl -H "Authorization: Bearer <token>" https://api.academy.test/api/v1/communities/categories` returns seeded categories.',
                    ],
                    'rollback' => [
                        'If seeding fails mid-run, execute `php artisan tinker --execute="\\App\\Domain\\Communities\\Models\\CommunityCategory::truncate();"` and re-run seeder.',
                        'Restore icon assets from previous S3 version (`aws s3 cp s3://academy-assets/communities/icons@latest-backup ./ -r`).',
                    ],
                    'telemetry' => [
                        'NewRelic custom event `community_seed_completed` emitted once.',
                    ],
                    'related_migrations' => [],
                    'related_commands' => ['db:seed'],
                    'notes' => 'Keep maintenance mode enabled until verification completed to prevent inconsistent caches.',
                ],
                [
                    'key' => 'post-migration-verification',
                    'name' => 'Post-migration verification & resume traffic',
                    'type' => 'verification',
                    'owner_roles' => ['Site Reliability', 'QA Lead'],
                    'expected_runtime_minutes' => 7,
                    'maintenance_window_minutes' => 10,
                    'dependencies' => ['seed-foundation-data'],
                    'prechecks' => [
                        'Ensure maintenance mode still enabled and background workers paused.',
                    ],
                    'execution' => [
                        'Run automated smoke suite: `php artisan test --testsuite=Feature --filter=Community`.',
                        'Execute mobile contract tests via GitHub Actions workflow `mobile-contract-tests` (trigger manual dispatch).',
                        'Resume Horizon queues: `php artisan horizon:continue communities-backfill`.',
                        'Disable maintenance mode: `php artisan up`.',
                    ],
                    'verification' => [
                        'API endpoint `/api/v1/communities` returns HTTP 200 with non-empty payload.',
                        'Grafana synthetic transaction `community-home` stays green for 15 minutes post cutover.',
                        'Mobile smoke testers confirm feed loads on build >=3.2.0 (TestFlight runbook).',
                    ],
                    'rollback' => [
                        'If smoke tests fail, re-enable maintenance mode, pause Horizon, and execute `php artisan migrate:rollback --step=1 --force` in reverse order of applied migrations.',
                        'Trigger database snapshot restore if data integrity compromised (RDS snapshot `pre-community-schema`).',
                    ],
                    'telemetry' => [
                        'PagerDuty maintenance window closed with status update referencing runbook step completion.',
                    ],
                    'related_migrations' => [],
                    'related_commands' => ['test', 'horizon:continue', 'up'],
                    'notes' => 'Do not roll forward feature flags until Product Ops sign-off captured in MIG-CR-884.',
                ],
            ],
        ],
    ],
];
