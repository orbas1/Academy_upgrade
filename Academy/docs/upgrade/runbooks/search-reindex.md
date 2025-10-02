# Search Reindex Runbook

Meilisearch powers discovery for communities, posts, comments, and members.
Use this guide to regenerate indexes safely after schema changes, production
incidents, or bulk imports.

## When to Run

- After deploying migrations that touch searchable columns.
- Restoring search nodes from backup or failover.
- Clearing corrupted or out-of-sync indexes detected by monitoring.

## Preconditions

- Meilisearch cluster reachable and healthy (`/health` = `true`).
- Laravel Horizon queues processing normally.
- `.env` has `MEILISEARCH_HOST` and `SCOUT_DRIVER=meilisearch` configured.

## Commands

1. Pause write-heavy workers to reduce churn during rebuild:
   ```bash
   php artisan horizon:pause search-index
   ```
2. Trigger scoped reindex (adjust `--entities` as needed):
   ```bash
   php artisan search:reindex --entities=communities,posts,comments,users
   ```
3. Resume workers once reindex completes:
   ```bash
   php artisan horizon:continue search-index
   ```

## Validation Checklist

- Run smoke query to confirm document counts:
  ```bash
  curl -s "$MEILISEARCH_HOST/indexes/posts/stats" | jq '.numberOfDocuments'
  ```
- Laravel logs show `ReindexComplete` event with zero failures.
- API endpoint `/api/communities/search?q=test` returns results in < 30 ms p95.

## Troubleshooting

| Issue | Action |
| --- | --- |
| Command exits with `connection refused` | Check security groups / firewall; ensure Meilisearch container is up. |
| Documents missing after rebuild | Verify `Scout::shouldBeSearchable()` rules; run per-model `scout:import`. |
| Horizon queue stuck | Restart worker: `php artisan horizon:terminate`. |

## Change Control

- Capture index stats before/after run in Datadog dashboard `Orbas Learn / Search`.
- File completion note in release ticket with timestamp and operator initials.
