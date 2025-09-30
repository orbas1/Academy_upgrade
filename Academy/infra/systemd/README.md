# Horizon & Queue Worker Systemd Units

These unit files orchestrate the Laravel Horizon supervisor pools described in
Stage 10 of the upgrade plan. They are designed for Ubuntu 22.04+ hosts running
PHP-FPM 8.3 and Redis-backed queues.

## Files

- `horizon.target` – logical target ensuring all worker slices are started.
- `horizon@.service` – templated service supervising Horizon with per-queue
  tags and graceful shutdown handling.
- `queue-worker@.service` – fallback worker service for dedicated queues that
  are not Horizon-managed (e.g., long-running exports).

## Deployment

```bash
sudo cp horizon.target /etc/systemd/system/
sudo cp horizon@.service /etc/systemd/system/
sudo cp queue-worker@.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now horizon.target
```

Scaling pools is handled by `systemctl edit horizon@notifications.service`
where `Environment="HORIZON_TAG=notifications"` can be overridden or by adding
`HORIZON_MIN_PROCESSES`/`HORIZON_MAX_PROCESSES` drop-ins to adjust concurrency
without modifying the repository-managed units.
