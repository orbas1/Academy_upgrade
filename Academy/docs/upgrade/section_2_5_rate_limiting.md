# Section 2.5 – Rate Limiting & Anti-Abuse Implementation Playbook

## 1. Objectives
Deliver deterministic protections against brute force, spam, and resource exhaustion while preserving good-user experience. Rate limiting will be centralized in Redis, surfaced through Laravel's `RateLimiter` facade, and augmented with behaviour scoring, manual overrides, and observability hooks.

## 2. Scope & Coverage Matrix
| Surface | Purpose | Limit | Decay | Identifier | Action on Breach |
| --- | --- | --- | --- | --- | --- |
| `auth.login` | Prevent credential stuffing | 5 attempts | 60 seconds | email + IP hash | Temporary block + CAPTCHA challenge |
| `auth.register` | Reduce fake signups | 3 attempts | 10 minutes | IP + device fingerprint | Show "slow down" toast, raise abuse score |
| `auth.password.forgot` | Limit password reset spam | 3 requests | 15 minutes | email + IP | Rate limit error, notify security Slack |
| `feed.post.create` | Throttle posting | 10 posts | 5 minutes | user id | Queue moderation review if limit exceeded |
| `feed.comment.create` | Prevent comment flooding | 20 comments | 5 minutes | user id | Soft block for 15 minutes |
| `feed.reaction.toggle` | Guard like toggles | 60 actions | 1 minute | user id + post id | Drop action silently, log metric |
| `community.join` | Avoid join churn | 5 joins | 10 minutes | user id | Prompt to contact support |
| `payments.checkout` | Protect Stripe from retries | 5 sessions | 30 minutes | user id + IP | Return 429 + surfaced to payments SRE |

*All limits configurable via `config/rate-limits.php` and may be tuned per environment.*

## 3. Architecture Overview
1. **Central configuration** – new `config/rate-limits.php` enumerates throttles, enabling environment overrides.
2. **Service provider registration** – `App\Providers\RateLimitingServiceProvider` iterates configuration and calls `RateLimiter::for(...)` with named callbacks.
3. **Request binding** – HTTP routes use named limiters, e.g. `Route::middleware(['auth', 'throttle:feed.post.create'])->post(...)`.
4. **Abuse scoring** – `App\Domain\Security\AbuseScoreService` increments per-user scores stored in Redis sorted sets.
5. **Escalation** – surpassing defined thresholds triggers jobs on `notifications` queue to alert moderation team and optionally require CAPTCHA via feature flag `abuse.captcha`.

## 4. Implementation Steps
1. **Create configuration file**
   ```php
   // config/rate-limits.php
   return [
       'auth.login' => ['max_attempts' => 5, 'decay' => 60],
       'auth.register' => ['max_attempts' => 3, 'decay' => 600],
       'auth.password.forgot' => ['max_attempts' => 3, 'decay' => 900],
       'feed.post.create' => ['max_attempts' => 10, 'decay' => 300],
       'feed.comment.create' => ['max_attempts' => 20, 'decay' => 300],
       'feed.reaction.toggle' => ['max_attempts' => 60, 'decay' => 60],
       'community.join' => ['max_attempts' => 5, 'decay' => 600],
       'payments.checkout' => ['max_attempts' => 5, 'decay' => 1800],
   ];
   ```
2. **Register service provider**
   ```php
   // app/Providers/RateLimitingServiceProvider.php
   namespace App\Providers;

   use Illuminate\Cache\RateLimiting\Limit;
   use Illuminate\Support\Facades\RateLimiter;
   use Illuminate\Support\ServiceProvider;

   class RateLimitingServiceProvider extends ServiceProvider
   {
       public function boot(): void
       {
           foreach (config('rate-limits') as $name => $definition) {
               RateLimiter::for($name, function ($request) use ($definition, $name) {
                   $key = $this->resolveKey($name, $request);

                   return Limit::perMinutes(
                       $definition['decay'] / 60,
                       $definition['max_attempts']
                   )->by($key);
               });
           }
       }

       private function resolveKey(string $name, $request): string
       {
           return match ($name) {
               'auth.login' => sha1(strtolower($request->input('email')).'|'.$request->ip()),
               'auth.register' => sha1($request->ip().'|'.$request->header('X-Device-Id')),
               'auth.password.forgot' => sha1($request->input('email').'|'.$request->ip()),
               'feed.post.create', 'feed.comment.create', 'feed.reaction.toggle' => (string) optional($request->user())->id,
               'community.join', 'payments.checkout' => (string) optional($request->user())->id.'|'.$request->ip(),
               default => $request->ip(),
           };
       }
   }
   ```
3. **Bind middleware** – update route definitions to use named limiters and append `throttle:<name>` to route middleware arrays.
4. **Abuse scoring** – add event listener (`PostRateLimitExceeded`) that enqueues `HandleRateLimitBreach` job writing to Redis sorted set `abuse:scores` and notifying Slack via Webhook.
5. **CAPTCHA hook** – integrate hCaptcha via middleware triggered when `AbuseScoreService::score($user) > 80`.

## 5. Observability & Alerting
- **Metrics**: expose Prometheus counters `rate_limit_breaches_total{limiter="auth.login"}` and histograms for wait times via Horizon tagged metrics.
- **Logging**: structured logs to `security` channel containing request id, limiter name, user id, IP, and trace id.
- **Dashboards**: Grafana panel summarizing top offenders and breach trends per hour.
- **Alerts**: PagerDuty SEV3 triggered when breach rate > 10/min or abuse score > 100 for any user within 15 minutes.

## 6. Testing Strategy
1. **Unit tests** verifying key generation and `Limit` definitions for each limiter.
2. **Feature tests** simulating repeated requests using `Travel::seconds` to assert lockouts and JSON 429 responses.
3. **Security regression** ensures 2FA and login flows respect rate limiting even with throttle resets.
4. **Load testing** with k6 script `load/auth-rate-limits.js` to validate no more than configured requests succeed per window.

## 7. Operations & Runbooks
- **Emergency bypass**: `php artisan rate-limits:bypass user@example.com --limiter=feed.post.create --ttl=900` temporarily whitelists a user.
- **Global tuning**: adjust `.env` overrides (`RATE_LIMIT_AUTH_LOGIN_MAX=7`) and run `php artisan config:cache`.
- **Redis eviction**: monitor `redis-cli info memory` for eviction warnings; configure `maxmemory-policy noeviction` for limiter DB.
- **Monitoring integration**: alerts feed into `#academy-security` Slack channel with correlation id linking to logs and abuse score dashboard.

## 8. Acceptance Checklist
- [ ] All named limiters registered with environment overrides documented.
- [ ] Abuse scoring service and notification jobs deployed and tested.
- [ ] Grafana dashboard and PagerDuty alert rules created and reviewed with Security Ops.
- [ ] Runbook for emergency bypass validated in staging.
- [ ] QA sign-off for throttled flows recorded in change ticket.

## 9. Evidence & Deliverables
- Configuration file `config/rate-limits.php` committed with environment-specific overrides.
- Service provider registered in `config/app.php` providers array.
- Automated tests under `tests/Feature/Security/RateLimitingTest.php` passing in CI.
- k6 report stored under `docs/upgrade/runbooks/rate-limit-validation.md`.
- Monitoring dashboard snapshot archived in `docs/upgrade/backups/metrics/`.
