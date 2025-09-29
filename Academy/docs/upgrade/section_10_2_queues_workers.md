# Section 10.2 – Queues & Workers Upgrade Summary

## Horizon Scaling
- Horizon now exposes per-queue concurrency controls in `config/horizon.php` with environment overrides for staging and production.
- Scheduler executes `queues:monitor` alongside `horizon:snapshot` every five minutes on shared infrastructure.

## Queue Health Monitoring
- `queue_metrics` table captures backlog size, latency and backlog delta per minute for each logical queue.
- `queues:monitor` command records metrics, prunes historical data beyond the configured retention window, and dispatches `QueueBacklogDetected` when thresholds are breached.
- Thresholds configurable via `config/queue-monitor.php` and `.env` defaults; results shipped to logs for SIEM ingestion.

## Operational API
- `/api/v1/ops/queue-health` returns the most recent snapshot. Admins receive full counts and thresholds; authenticated members receive public-facing degradation messages only.
- Mobile and web surfaces can surface queue degradation without leaking operational internals.

## Mobile Surfacing
- Flutter community screens poll the queue health API after composition, showing degradations via warning snackbars to set expectations for delayed media processing.

## Retention & Governance
- Metrics purge automatically after configurable retention (`QUEUE_MONITOR_RETENTION_HOURS`).
- Tests cover recorder calculations, retention pruning, and API exposure controls.
