# Backup Evidence Log Template

Record each pre-flight backup run for the Laravel 11 upgrade.

| Date (UTC) | Backup Type | Location | SHA-256 | Restore Validation | Operator |
| --- | --- | --- | --- | --- | --- |
| 2025-01-05 01:15 | MySQL logical snapshot | s3://academy-backups/db/academy-2025-01-05.sql | `TODO` | Restored to staging, smoke tests pass | DBA |
| 2025-01-05 01:20 | Redis dump | s3://academy-backups/cache/academy-cache-2025-01-05.rdb | `TODO` | Loaded into staging cache node, Horizon smoke tests pass | Platform |
| 2025-01-05 01:35 | Asset sync | s3://academy-assets-backup-2025-01-05/ | `manifest.json` | Integrity verified via `aws s3 sync --dryrun` | Platform |

*Update this log per maintenance window; retain for compliance audits.*
