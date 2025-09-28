# Laravel 11 Upgrade – Rollback Procedure

1. **Trigger**
   - Any SEV1 incident or failure of smoke tests post-cutover.

2. **Immediate Actions (0–5 minutes)**
   - Freeze new deployments via CI protection rule.
   - Switch load balancer target group from `academy-green` back to `academy-blue`.
   - Disable feature flags: `php artisan feature:toggle communities --off`.

3. **Data Validation (5–15 minutes)**
   - Confirm blue stack application logs show healthy requests.
   - Verify Horizon queues drained and no stuck jobs remain.
   - Run `php artisan migrate:rollback --step=1` if contract migrations were executed.

4. **Database Restoration (if needed)**
   - Restore latest snapshot using `mysql < backups/academy-<stamp>.sql` on replica, then promote replica.
   - Replay Redis dump via `redis-cli --pipe < backups/academy-cache-<stamp>.rdb`.

5. **Communication**
   - Update #academy-status and incident bridge with rollback status.
   - Notify stakeholders via email template referencing new ETA.

6. **Post-Rollback Review**
   - Capture logs, metrics, and diff artifacts for root cause analysis.
   - Schedule retrospective within 24 hours.

7. **Decision to Reattempt**
   - CAB reconvenes to evaluate readiness; require updated compatibility report and remediation checklist before next attempt.
