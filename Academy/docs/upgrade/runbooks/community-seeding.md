# Community Seed Runbook

This runbook explains how to populate the Orbas Learn communities schema with
baseline data in each environment. The goal is to guarantee parity across
staging, demo, and production before we open onboarding to real cohorts.

## When to Run

- Initial environment bring-up (staging → production).
- Refreshing demo data for sales enablement.
- After destructive testing that truncates `communities_*` tables.

## Preconditions

- Latest migrations applied (`php artisan migrate`).
- Storage and queue services are reachable (Redis, S3-compatible store).
- `.env` configured with Orbas Learn naming (`APP_NAME="Orbas Learn"`).

## Commands

1. Warm caches and config to ensure seeder bindings resolve correctly:
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   ```
2. Execute the community foundation seeder:
   ```bash
   php artisan db:seed --class=Database\\Seeders\\Communities\\CommunityFoundationSeeder
   ```
3. Verify seed output:
   ```bash
   php artisan tinker --execute "App\\Domain\\Communities\\Models\\CommunityCategory::count();"
   php artisan tinker --execute "App\\Domain\\Communities\\Models\\CommunityLevel::whereNull('community_id')->count();"
   ```

## Post-Run Validation

- `community_categories` contains 5 default records.
- `community_levels` exposes global tiers (Newbie → Champion).
- `community_points_rules` seeded with baseline actions (post/comment/like/etc.).
- Horizon `seeders` queue is empty (if offloaded to async workers).

## Troubleshooting

| Symptom | Mitigation |
| --- | --- |
| Seeder fails with "class not found" | Run `composer dump-autoload` and retry. |
| Duplicate seed data | Use `--force` flag and confirm `updateOrCreate` logic is intact. |
| Redis connection errors | Check `QUEUE_CONNECTION` and `REDIS_URL`; retry after service recovery. |

## Change Control

- Track seeder adjustments in Jira component **Communities Platform**.
- For production runs, obtain CAB approval and record the execution timestamp in the release log.
