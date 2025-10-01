# Stage 12.4 – Load & Resilience Testing Plan

## Objectives

1. Validate the profile activity API, backfill commands, and feature-flag middleware under sustained read pressure (120 req/s peak) with deterministic datasets.
2. Exercise asynchronous fan-out (notifications, search ingestion, queue backlogs) during burst traffic to confirm graceful degradation.
3. Provide a mobile-first load harness that mirrors repository usage patterns, captures latency percentiles, and feeds CI signal.
4. Define chaos drills targeting database failover and cache/queue outages with clear rollback criteria.

## Service-Level Goals

| Metric | SLO | Notes |
| --- | --- | --- |
| p95 API latency | ≤ 800 ms | Measured from k6 `profile_activity_duration`. Requests above 800 ms trigger autoscaler playbook. |
| Error rate | < 1% | Combined HTTP + application-level (429/5xx). Exceeding threshold requires rollback. |
| Queue recovery | < 5 min | After simulated queue outage, Horizon supervisors recover without manual drain. |
| Cache stampede retries | < 3 per key | Optimistic locking on profile activity projection prevents thundering herd. |

## Environment Preparation

1. **Seed deterministic load dataset**

   ```bash
   php artisan community:loadtest:prepare \
     --communities=3 \
     --members=120 \
     --posts=8 \
     --comments=12 \
     --reactions=30 \
     --points=6 \
     --tokens=25 \
     --output=storage/app/testing/load_credentials.json
   ```

   * Generates 3 “Load Test Guild” communities with 360 active members, 2,880 posts, 34,560 comments, and 86,400 reactions.
   * Persists API tokens for k6 and mobile harnesses in `storage/app/testing/load_credentials.json`.
   * Seeds profile activity projections for every post/comment to guarantee cursor coverage.

2. **Feature flags** – Enable `community_profile_activity` via `php artisan community:enable-feature community_profile_activity --actor-type=percentage --actor-value=100` before running load scripts.
3. **Caches** – Warm relevant cache keys (`cache:clear`, `config:cache`) and prime Scout indexers with `php artisan search:reindex --entities=communities,posts,comments`.
4. **Mobile test data** – Copy the credential payload into Flutter integration tests through `lib/features/communities/data/testing/in_memory_community_api_service.dart` to avoid live HTTP calls in CI.

## Load Execution Blueprint

### Web/API (k6)

* Script: `tools/testing/load/profile_activity.js`.
* Runtime parameters (override with environment variables):
  * `BASE_URL` – Target host (e.g., `https://staging.api.example.com`).
  * `API_TOKEN` – Token minted for `communities:read` scopes from the seeder summary.
  * `START_RATE` = 20 req/s, warm-up to 50 req/s over 2 min, peak 120 req/s for 5 min, cool-down 2 min.
  * `PAGE_SIZE` default 50 to match mobile pagination.
* Thresholds encoded in the script: `p95 < 800 ms`, `max < 2 s`, failure rate `< 1%`.
* Use `k6 run --vus 200 --summary-export=artifacts/profile_activity_summary.json tools/testing/load/profile_activity.js` and parse results with `php tools/testing/load/analyse_k6_summary.php artifacts/profile_activity_summary.json --markdown=docs/upgrade/testing/load_resilience_run_report.md`.

### Chaos & Resilience Drills

| Drill | Steps | Expected Outcome |
| --- | --- | --- |
| Queue outage | Disable Horizon supervisors for 3 min, enqueue 5k notification jobs, restore supervisors. | Pending jobs drain within 5 min; `profile_activity_errors` remains <1%. |
| Database failover | Promote replica to primary in staging (use ProxySQL script) while k6 ramping. | API emits ≤2 transient 5xx responses; Laravel auto-retries idempotent requests. |
| Cache purge | Flush Redis during peak traffic. | Feature flag middleware continues serving via database fallback; latency spike <200 ms. |

### Observability Hooks

* Horizon + Laravel Telescope dashboards bookmarked in Grafana folder `Community/Load`. Alerts fire on queue depth > 2,000 or API latency > 900 ms for 2 consecutive minutes.
* `App\Support\FeatureFlags\FeatureRolloutRepository` logs every request gating decision; inspect `storage/logs/laravel.log` for rollout anomalies.
* Cloudflare logs (if fronting the API) monitored for surge or bot traffic; rate-limits tuned to allow load harness CIDR.

## Mobile Load Harness

* **Driver**: `lib/features/communities/data/testing/profile_activity_load_driver.dart` runs concurrent repository fetches, recording per-request latency, throughput, and failure metadata.
* **Usage**: instantiate `ProfileActivityLoadDriver` with in-memory API (`InMemoryCommunityApiService`) or live `CommunityRepository`, call `run()` to produce a `ProfileActivityLoadSummary` with total requests, success/failure counts, p95 latency, and throughput.
* **Automation**: `test/features/communities/profile_activity_load_driver_test.dart` asserts deterministic percentile calculations; integration test `integration_test/profile_activity_flow_test.dart` can be extended to execute the driver against staging credentials when Flutter CLI is available.

## Reporting & CI Integration

1. Export k6 summary JSON, parse with `analyse_k6_summary.php`, and commit Markdown report alongside raw data under `docs/upgrade/testing/fixtures/`.
2. Upload Flutter load summary (JSON via `ProfileActivityLoadSummary.toJson()`) as CI artifact; compare against historical baselines (<10% regression triggers failure).
3. Update `docs/upgrade/artifacts/progress_tracker.md` with completion percentages and reference the latest run report.
4. During regressions, capture trace IDs from API responses (headers: `X-Request-Id`) and correlate with Laravel logs to speed root-cause analysis.

## Next Steps

* Automate chaos drills using GitHub Actions + Terraform Cloud triggers once infrastructure definitions land (Section 10).
* Extend load harness to cover write-heavy scenarios (post creation, reactions) after rate-limit tunings are finalized.
