# Application Key Rotation Runbook

## Purpose
Guarantee Laravel APP_KEY and shared secrets are rotated on a predictable
cadence while preserving session integrity and avoiding service interruptions.
Follow this runbook after security incidents or during quarterly maintenance
windows.

## Preconditions
- Blue/green or rolling deployment infrastructure is available.
- Updated secrets live in the external manager path referenced by
  `SECRETS_MANAGER_PATH`.
- All Horizon/queue workers drain gracefully (no in-flight jobs processing
  session payloads).

## Rotation Steps
1. **Stage secrets.**
   ```bash
   tools/secrets/pull_secrets.sh --env=staging --output=.env.next
   ```
   Validate the generated `.env.next` contains the new `APP_KEY`, cookie salts,
   and third-party credentials.
2. **Launch green environment.** Boot a new application pool using `.env.next`
   and run database migrations in expand-only mode.
3. **Warm caches.** Execute `php artisan config:cache` and hit health checks to
   pre-seed session and view caches.
4. **Flip traffic.** Switch the load balancer to the green pool. Monitor error
   rates, queue depth, and login success metrics for five minutes.
5. **Force rekey of legacy nodes.** Rotate `APP_KEY` on the blue nodes and drain
   them from the balancer. All new session cookies now derive from the rotated
   key.
6. **Cleanup.** Destroy `.env.next` and revoke the superseded secret version in
   the manager. Update the compliance log with timestamp, environment, operator,
   and validation evidence.

## Rollback Plan
If authentication errors spike after rotation, redirect traffic back to the
blue pool, restore the previous secret version, and invalidate new sessions via
`php artisan session:clear`. Re-run the runbook after remediation.
