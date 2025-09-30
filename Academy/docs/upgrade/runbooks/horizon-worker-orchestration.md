# Horizon Worker Orchestration Runbook

This runbook covers provisioning, scaling, and recovering the Laravel Horizon
supervisor pools that power Academy background jobs.

## Provisioning

1. Copy the systemd units from `infra/systemd` into `/etc/systemd/system`.
2. Run `sudo systemctl daemon-reload`.
3. Enable the target: `sudo systemctl enable --now horizon.target`.
4. Confirm status via `systemctl status horizon@notifications` (repeat for
   `media`, `webhooks`, and `search`).

## Scaling Pools

- Adjust concurrency by creating drop-in files:

```bash
sudo systemctl edit horizon@media.service
```

Add overrides:

```
[Service]
Environment="HORIZON_MIN_PROCESSES=2"
Environment="HORIZON_MAX_PROCESSES=16"
```

Reload units (`systemctl daemon-reload`) and restart the affected supervisor.

## Graceful Deployments

1. Run `php artisan horizon:pause` to drain jobs.
2. Deploy new code and run database migrations.
3. Restart supervisors with `sudo systemctl restart horizon.target`.
4. Resume processing using `php artisan horizon:continue`.

## Incident Response

- If the queue backlog alarm fires, consult CloudWatch metrics (provisioned in
  `infra/terraform/devops`) to identify the impacted queue.
- Scale the relevant pool using the steps above or invoke `php artisan
  queues:monitor` to inspect queue health from the CLI.
- For long-running or bespoke queues, use the templated
  `queue-worker@.service` unit to run dedicated workers separate from Horizon.
