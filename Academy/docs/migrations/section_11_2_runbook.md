# Section 11.2 — Migration Steps Runbook

The following runbook operationalizes the communities data convergence rollout for production. It expands on the strategy defined in [Section 11.1](./expand_contract_strategy.md) and the executable definitions captured in `config/migration_runbooks.php`. The scope covers every schema change in migrations dated `2024_12_24_*` plus the foundational community seeders.

## Overview

- **Plan key**: `communities_data_convergence`
- **Runbook key**: `communities_schema_v1`
- **Maintenance window**: 45 minutes
- **Primary owners**: Communities Platform Guild & Data Engineering
- **Approvers**: Site Reliability Engineering, Product Operations, Compliance
- **Communication channels**: `#on-call-community`, `#ops-war-room`, `ops@academy.example`

All steps below must be executed in order. Each step lists the actors responsible, detailed commands, verification expectations, telemetry probes, and rollback playbooks. Gate reviews are captured in Jira change request **MIG-CR-884**.

## 1. Preflight Safety & Readiness Checks (`preflight-safety-checks`)

**Owners:** SRE duty engineer, DBA on-call  
**Window:** 15 minutes (expected runtime 10 minutes)

1. Confirm the latest Amazon RDS automated snapshot completed within the last 4 hours via the AWS console.  
2. Validate replication lag: run `SELECT * FROM performance_schema.replication_group_member_stats;` on the primary. Lag must be < 1s.  
3. Ensure LaunchDarkly flag `community_feed_v2` is disabled in production.  
4. Verify `/v1/ops/migration-plan?phase=expand-schema-and-services` returns `status=ready` on the observability dashboard.  
5. Announce the window in `#ops-war-room` and receive acknowledgements from Data Engineering and SRE leads.  
6. Execute `php artisan migration:plan communities_data_convergence --phase=expand-schema-and-services` and attach the output to Confluence page **MIG-OPS-11.2**.  
7. Run `aws rds describe-db-instances --db-instance-identifier academy-cluster` and ensure storage headroom > 20%.  
8. Record sign-off in Jira **MIG-310** and activate the PagerDuty maintenance window for “Academy API”.

**Rollback:** If any check fails, abort the deployment, revert alert states, post status in `#ops-war-room`, and schedule a new window within 24 hours.

## 2. Deploy Community Core Schema (`deploy-community-core-schema`)

**Owners:** DBA on-call, Data Engineering  
**Window:** 15 minutes (expected runtime 8 minutes)  
**Related migration:** `2024_12_24_000000_create_community_core_tables`

1. In a Laravel tinker session run `Schema::hasTable('communities')` and confirm it returns `false`.  
2. Confirm change request **MIG-CR-884** shows Compliance and Product Ops approval.  
3. Execute `php artisan migrate --path=database/migrations/2024_12_24_000000_create_community_core_tables.php --force`.  
4. Tail `storage/logs/laravel.log` or CloudWatch group `/academy/api/migrations` for success logs.  
5. Run `php artisan migrate:status --path=database/migrations/2024_12_24_000000_create_community_core_tables.php` and ensure the migration is marked `Ran`.  
6. Validate table structure: `DESCRIBE communities;` should contain `slug`, `visibility`, `join_policy`.  
7. Check indexes via `SHOW INDEX FROM community_categories;` ensuring `community_categories_slug_unique` exists.  
8. Confirm Prometheus alert `community_schema_migration_errors` remains at `0`.

**Rollback:**
- `php artisan migrate:rollback --path=database/migrations/2024_12_24_000000_create_community_core_tables.php --step=1 --force`  
- If rollback fails, manually drop tables: `DROP TABLE IF EXISTS communities, community_categories, geo_places CASCADE;`

## 3. Deploy Engagement Layer Schema (`deploy-engagement-layer-schema`)

**Owners:** DBA on-call, Communities Platform Guild  
**Window:** 20 minutes (expected runtime 12 minutes)  
**Related migration:** `2024_12_24_000100_create_community_engagement_tables`

1. Ensure core tables are present: `SELECT COUNT(*) FROM information_schema.tables WHERE table_name = "communities";` should be ≥1.  
2. Pause Horizon queue `communities-backfill` via `php artisan horizon:pause communities-backfill`.  
3. Apply schema: `php artisan migrate --path=database/migrations/2024_12_24_000100_create_community_engagement_tables.php --force`.  
4. Tail `storage/logs/laravel.log` for migration completion.  
5. Validate tables `community_posts`, `community_post_comments`, `community_members` exist and include `deleted_at`.  
6. Confirm self-referential FK: `SHOW CREATE TABLE community_post_comments;` must include `parent_id`.  
7. Ensure metrics tables exist by querying `information_schema.statistics` for `community_post_likes`.  
8. Monitor RDS Performance Insights to ensure execution < 5 minutes (alert threshold 10 minutes).

**Rollback:**
- `php artisan migrate:rollback --path=database/migrations/2024_12_24_000100_create_community_engagement_tables.php --step=1 --force`  
- If FK constraints block rollback, temporarily disable foreign keys (`SET FOREIGN_KEY_CHECKS=0;`), drop tables, then re-enable.

## 4. Apply Feed Indexes & Partitions (`apply-feed-indexes-and-partitions`)

**Owner:** DBA on-call  
**Window:** 10 minutes (expected runtime 6 minutes)  
**Related migration:** `2024_12_24_000200_optimize_community_indexes_and_partitions`

1. Confirm partitioning support with `SHOW PLUGINS LIKE "partition%";`.  
2. Check row count of `community_posts` to ensure < 5M rows (initial rollout).  
3. Execute `php artisan migrate --path=database/migrations/2024_12_24_000200_optimize_community_indexes_and_partitions.php --force`.  
4. Monitor `SHOW PROCESSLIST;` for long-running locks.  
5. Validate indexes using `SHOW INDEX FROM community_posts WHERE Key_name = "community_posts_feed_idx";`.  
6. Confirm partitions via `SELECT PARTITION_NAME FROM information_schema.PARTITIONS WHERE TABLE_NAME = "community_posts" AND PARTITION_NAME IS NOT NULL;` expecting 16 rows.  
7. Watch Grafana panel “Community Feed Query p95” to ensure < 180 ms.

**Rollback:**
- `php artisan migrate:rollback --path=database/migrations/2024_12_24_000200_optimize_community_indexes_and_partitions.php --step=1 --force`  
- If partition rollback fails, run `ALTER TABLE community_posts REMOVE PARTITIONING;`

## 5. Deploy Supporting Tables (`deploy-supporting-tables`)

**Owner:** Data Engineering  
**Window:** 10 minutes (expected runtime 4 minutes)  
**Related migrations:**
- `2024_12_24_000900_create_search_saved_queries_table`
- `2024_12_24_001100_create_community_notification_preferences_table`

1. Confirm Meilisearch health (`curl https://meili.internal/health`).  
2. Execute `php artisan migrate --path=database/migrations/2024_12_24_000900_create_search_saved_queries_table.php --force`.  
3. Execute `php artisan migrate --path=database/migrations/2024_12_24_001100_create_community_notification_preferences_table.php --force`.  
4. Validate JSON columns via `DESCRIBE search_saved_queries;` ensuring `filters`, `flags`.  
5. Confirm table presence using `SELECT COUNT(*) FROM information_schema.TABLES WHERE table_name IN ("search_saved_queries", "community_notification_preferences");` expecting `2`.  
6. Monitor application `search` log channel for schema errors.

**Rollback:**
- Reverse order with `php artisan migrate:rollback --path=database/migrations/2024_12_24_001100_create_community_notification_preferences_table.php --step=1 --force` then rollback the `000900` migration.

## 6. Seed Foundation Data (`seed-foundation-data`)

**Owner:** Communities Platform Guild  
**Window:** 10 minutes (expected runtime 5 minutes)

1. Enable maintenance mode: `php artisan down`.  
2. Seed categories, levels, and points rules: `php artisan db:seed --class=Database\Seeders\Communities\CommunityFoundationSeeder --force`.  
3. Sync community icon assets: `aws s3 sync s3://academy-assets/communities/icons storage/app/public/communities/icons`.  
4. Validate seeding via `SELECT COUNT(*) FROM community_categories;` (≥5 rows).  
5. Perform API smoke test: `curl -H "Authorization: Bearer <token>" https://api.academy.test/api/v1/communities/categories` should return seeded payload.  
6. Confirm New Relic custom event `community_seed_completed` fired.

**Rollback:**
- If seeding fails, truncate `community_categories` (and related defaults) via tinker and rerun the seeder.  
- Restore icon assets from `s3://academy-assets/communities/icons@latest-backup` if corruption occurs.

## 7. Post-Migration Verification & Traffic Resume (`post-migration-verification`)

**Owners:** Site Reliability, QA Lead  
**Window:** 10 minutes (expected runtime 7 minutes)

1. Ensure maintenance mode is still enabled and Horizon queues remain paused.  
2. Execute backend smoke tests: `php artisan test --testsuite=Feature --filter=Community`.  
3. Trigger the **mobile-contract-tests** GitHub Actions workflow to validate the Flutter client contract.  
4. Resume Horizon queue: `php artisan horizon:continue communities-backfill`.  
5. Disable maintenance mode: `php artisan up`.  
6. Verify `/api/v1/communities` returns HTTP 200 with non-empty data.  
7. Monitor Grafana synthetic transaction `community-home` for 15 minutes; ensure no regression.  
8. Collect confirmation from TestFlight/Play Internal testers that build ≥3.2.0 loads the feed successfully.  
9. Close the PagerDuty maintenance window with a status note referencing runbook completion.  
10. Update Jira **MIG-CR-884** with final approval from Product Ops.

**Rollback:**
- If smoke tests fail, re-enable maintenance mode, pause Horizon again, and execute `php artisan migrate:rollback --step=1 --force` in reverse order of applied migrations.  
- If data integrity is compromised, restore RDS snapshot `pre-community-schema` and notify stakeholders via `#ops-war-room`.

## Appendices

### Telemetry Checklist

- Prometheus: `community_schema_migration_errors`, `community_backfill_drift`
- Grafana: “Community Migration / Preflight”, “Community Feed Query p95”, “community-home” synthetic
- CloudWatch: `/academy/api/migrations`
- New Relic custom event: `community_seed_completed`

### Artifact Capture

| Artifact | Location |
| --- | --- |
| Migration plan output | Confluence page **MIG-OPS-11.2** |
| RDS snapshot confirmations | AWS Backup audit log |
| Smoke test reports | GitHub Actions run logs (Feature + mobile-contract-tests) |
| Sign-off approvals | Jira tickets **MIG-CR-884**, **MIG-310** |

Following this runbook in conjunction with automated tooling ensures zero-downtime deployment of the communities schema and delivers the documentation required by Section 11.2.
