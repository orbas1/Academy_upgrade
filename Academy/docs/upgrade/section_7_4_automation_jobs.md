# Section 7.4 – Automation Jobs & Schedulers

## Overview
Automation jobs maintain member engagement, ensure data hygiene, and keep leaderboards and notifications timely.

## Job Catalog
| Job | Schedule | Description | Tech Stack | Monitoring |
| --- | --- | --- | --- | --- |
| `daily_community_digest` | Cron `0 12 * * *` (per tenant timezone) | Compile top posts, new members, upcoming events; send via email & push | Laravel job -> Notification channels | Success count, bounce rate, send latency |
| `weekly_leaderboard_recalculate` | Cron `0 6 * * 1` | Recompute points, badges, streaks; update caches and notify top performers | Laravel job + Redis sorted sets | Leaderboard freshness, execution time |
| `subscription_delinquency_retry` | Cron `*/30 * * * *` | Retry failed Stripe payments, send reminder notifications | Queue worker + Stripe API | Retry attempts, recovered revenue |
| `moderation_sla_checker` | Cron `*/15 * * * *` | Identify flags breaching SLA, escalate to Trust & Safety | Queue worker + Slack webhook | SLA breach count |
| `analytics_backfill_replay` | On-demand | Replay analytics events for specific window from Kafka to Snowflake | Artisan command + Airflow DAG | Row counts reconciled |
| `data_cleanup_archiver` | Cron `30 3 * * 0` | Archive inactive members, prune soft-deleted content beyond retention | Laravel job + S3 archive | Rows archived, duration |
| `webhook_health_monitor` | Cron `*/10 * * * *` | Ping configured webhooks; disable unhealthy integrations | Laravel job | Webhook uptime |

## Infrastructure
- Jobs managed via Laravel Scheduler + Horizon queues (`automation`, `notifications`, `billing`).
- Concurrency control using Redis locks to avoid overlapping runs per community.
- Configurable per-community toggles stored in `community_automation_settings` table.

## Deployment & Configuration
- `.env` entries for cron timezone defaults, webhook endpoints, retry thresholds.
- Artisan command `automation:sync --community={id}` to regenerate schedules after config changes.

## Observability
- Metrics exported to Prometheus (`automation_job_duration_seconds`, `automation_job_failures_total`).
- Alerting via DataDog for job failures or prolonged durations.
- Runbook references stored under `docs/upgrade/runbooks/` per critical job (digest, leaderboard, billing retries).

## Testing
- PHPUnit tests for job classes using fakes (Notification, Stripe, Slack).
- Integration tests verifying scheduler registration using `artisan schedule:list` snapshot.
- Chaos testing by injecting failures (network errors) and verifying retry/resume behavior.
