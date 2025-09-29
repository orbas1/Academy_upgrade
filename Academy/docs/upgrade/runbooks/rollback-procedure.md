# Laravel 11 Upgrade – Rollback Procedure

## Trigger Matrix

- Any SEV1 incident or failure of smoke tests post-cutover.
- Performance smoke job breaching SLO twice consecutively.
- Database replication lag > 30s with customer-impacting errors.

## Immediate Actions (0–5 minutes)

1. Freeze new deployments via CI protection rule.
2. Switch load balancer target group from `academy-green` back to `academy-blue` using Terraform workspace `production` and apply.
3. Disable feature flags:
   ```bash
   php artisan feature:toggle communities --off
   php artisan feature:toggle octane --off
   ```
4. Pause Horizon queues to drain writes gracefully:
   ```bash
   php artisan horizon:pause feed notifications search-index
   ```

## Stabilise Services (5–15 minutes)

- Confirm blue stack application logs show healthy requests and error budget restored.
- Verify Horizon queues drained and no stuck jobs remain using `php artisan horizon:supervisors`.
- Run contract migration rollback if expand/contract executed:
  ```bash
  php artisan migrate:rollback --path=database/migrations/contract
  ```
- Clear Octane and application caches:
  ```bash
  php artisan octane:clear
  php artisan optimize:clear
  ```

## Database Restoration (if needed)

1. Restore latest snapshot using `mysql < backups/academy-<stamp>.sql` on replica, then promote replica.
2. Replay Redis dump via `redis-cli --pipe < backups/academy-cache-<stamp>.rdb`.
3. Validate `database:schema:dump` output matches version control.

## Observability & Validation

- Confirm Prometheus `http_request_duration_seconds_bucket` p95 < 0.25 after rollback.
- Ensure Datadog synthetic `/healthz` checks pass for blue stack twice consecutively.
- Check analytics ingestion lag < 60 seconds and event backlog < 10k.

## Communication

- Update `#academy-status` and incident bridge with rollback status including timeline and next update ETA.
- Notify stakeholders via email template referencing new ETA (`docs/upgrade/runbooks/communication-template.md`).
- Record CAB approval in Jira ticket comment linking to metrics snapshots.

## Post-Rollback Review

- Capture logs, metrics, and diff artefacts for root cause analysis.
- Execute `tools/preflight/compatibility_audit.sh --mode=post-rollback`.
- Schedule retrospective within 24 hours with engineering, QA, and customer success.

## Decision to Reattempt

- CAB reconvenes to evaluate readiness; require updated compatibility report, remediation checklist, and passing k6 baseline before next attempt.
