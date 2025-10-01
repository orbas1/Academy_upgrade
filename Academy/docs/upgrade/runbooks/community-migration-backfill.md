# Community Migration & Backfill Runbook

## Purpose

Execute and validate the migration tranche for stage 11: seeding baseline data, backfilling memberships from classroom enrollments, and replaying legacy activity into profile projections.

## Prerequisites

- Database migrations applied, including `profile_activities` table.
- Queue workers paused for write-heavy communities during backfill window.
- Feature flag `community_profile_activity` disabled until verification completes.
- Classroom enrollment data available in `enrollments` table.

## Commands

### Seed baseline catalog

```bash
php artisan community:seed-baseline --force
```

- Idempotent: updates categories, levels, and points rules in place.
- Logs success in artisan output; review via `tail -f storage/logs/laravel.log` for anomalies.

### Backfill community memberships

```bash
php artisan community:backfill-membership --source=classrooms --batch=1000
```

Options:

- `--community=` (ID or slug) limits scope for phased rollouts.
- `--dry-run` reports counts without persistence.
- Batch size controls chunked `enrollments` reads; default `1000`.

Telemetry:

- Artisan output renders metrics table (communities processed, members created/reactivated, skips).
- Monitor MySQL replica lag and Horizon queue depth while command runs.

### Migrate legacy activity

```bash
php artisan community:migrate-legacy-activity --chunk=500 --since="-90 days"
```

- `--since` accepts ISO timestamp or relative modifiers accepted by Carbon.
- Dry-run available via `--dry-run` to validate counts before applying.
- Populates `profile_activities` with posts, comments, and course completions.

## Verification

1. API check:
   ```bash
   curl -H "Authorization: Bearer <token>" https://<host>/api/v1/me/profile-activity
   ```
   Expect HTTP 404 until the feature flag is enabled; 200 with paginated data afterwards.
2. Spot-check memberships in admin UI and via SQL:
   ```sql
   SELECT community_id, COUNT(*) FROM community_members WHERE metadata->'$.backfill.classrooms' IS NOT NULL GROUP BY 1;
   ```
3. Confirm `profile_activities` entries match counts from `community_posts`, `community_post_comments`, and `certificates` for sampled users.
4. Review [Stage 11 validation evidence](../artifacts/stage11_validation.md) for dry-run metrics, automation coverage, and rollback fire drill notes.

## Dry Run & Evidence Capture

- Execute dry runs in staging using sqlite profile (`--env=testing`) prior to production rollout.
- Archive artisan output (JSON mode recommended via `--output=json`) alongside the [validation evidence](../artifacts/stage11_validation.md).
- Capture database spot checks referenced above and store snapshots in `storage/app/upgrade/stage11/` for audit.

## Rollback

1. Disable feature flag:
   ```bash
   php artisan community:enable-feature --flag=community_profile_activity --percentage=0 --force
   ```
2. Remove backfilled membership rows if required:
   ```sql
   DELETE FROM community_members WHERE metadata->'$.backfill.classrooms.idempotency_key' IS NOT NULL;
   ```
3. Truncate `profile_activities` if the projection is invalid:
   ```bash
   php artisan db:table profile_activities --truncate
   ```

Document rollback execution in incident notes and notify stakeholders via the #academy-upgrade Slack channel.
