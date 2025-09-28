# Rate Limit Validation Runbook

## Purpose
Provide repeatable steps to confirm Section 2.5 rate limiters operate as expected across staging and production.

## Preconditions
- Deployed Laravel build containing `RateLimitingServiceProvider` and `config/rate-limits.php`.
- Redis databases online with `abuse:scores` sorted set accessible.
- k6 binary installed on operator workstation or CI runner.

## Procedure
1. **Smoke check**
   ```bash
   php artisan tinker --execute="RateLimiter::tooManyAttempts('auth.login', 1)"
   ```
   Ensure result is `false`.
2. **Simulate breach**
   ```bash
   php artisan rate-limits:test auth.login user@example.com --attempts=6
   ```
   Expect command to return HTTP 429 with JSON payload containing `retry_after` seconds.
3. **k6 load test**
   ```bash
   k6 run tests/load/auth-rate-limits.js --vus 5 --duration 2m --env EMAIL=user@example.com --env PASSWORD=secret
   ```
   Confirm success rate <= configured limit and 429 responses recorded.
4. **Metrics verification**
   - Check Grafana dashboard `Security / Rate Limiting` for spike corresponding to test window.
   - Validate Prometheus counter `rate_limit_breaches_total{limiter="auth.login"}` incremented.
5. **Abuse score review**
   ```bash
   redis-cli ZRANGE abuse:scores 0 -1 WITHSCORES | grep user_id
   ```
   Score should increase by expected amount and decay after TTL window.
6. **Alerting**
   Temporarily set `RATE_LIMIT_ALERT_THRESHOLD=1` in staging `.env` and trigger automation to assert PagerDuty SEV3 fired then auto-resolved after revert.

## Rollback / Remediation
- Reset limiter counters: `php artisan rate-limits:clear auth.login user@example.com`.
- Remove test abuse scores: `redis-cli ZREM abuse:scores user_id:123`.
- If throttling misconfiguration detected, update `config/rate-limits.php`, run `php artisan config:cache`, redeploy, and rerun steps above.

## Evidence Capture
Store k6 output (`out.json`), Grafana screenshot, and command logs under `docs/upgrade/backups/metrics/YYYY-MM-DD-rate-limit/` for CAB records.
