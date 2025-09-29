# Section 3.7 – Load & Stress Testing

## Objectives

- Validate that community feed, post creation, and notification dispatch APIs stay within **p95 ≤ 250 ms** under 120 RPS sustained load.
- Exercise burst behaviour at 3× baseline traffic and confirm Horizon auto-scaling and Redis persistence recover without manual intervention.
- Capture baseline metrics for rollout SLOs and store artefacts for regression comparison.

## Tooling

- [`k6`](https://k6.io/) scripts located under `tools/performance/k6`.
- Grafana dashboards backed by InfluxDB bucket `academy_performance` for trend visualisations.
- GitHub Actions job `performance-smoke` (defined below) to run nightly and on release branches.

## Test Matrix

| Scenario | Script | Target RPS | Duration | Success Criteria |
| --- | --- | --- | --- | --- |
| Community feed read/write mix | `community_feed_load_test.js` | 120 steady (burst 3×) | 3m ramp + 3m steady + 1m ramp down | `http_req_duration{p(95)} < 250 ms`, error rate `<1%`, engagement rate ≥ 0.98 |
| Notification throughput | `notification_throughput_test.js` | 40 | 10m | Dispatch accepted ≥ 99%, queue latency `<5s` |
| Resilience soak | `community_feed_load_test.js` | 60 (overnight) | 2h | No Horizon restarts, memory stable (<75% container cap) |

## Execution Steps

1. Provision **staging-green** with production-like data snapshot (≥ 1M posts).
2. Run `terraform workspace select staging-green` and apply autoscaling target tracking for `notifications` and `feed` queues.
3. Warm caches using `php artisan communities:cache --all` and pre-render hero community pages.
4. Execute scripts:
   ```bash
   cd tools/performance
   ./scripts/bootstrap.sh   # scales Horizon to baseline and primes Octane cache
   k6 run k6/community_feed_load_test.js --summary-trend-stats="avg,p(95),p(99)"
   k6 run k6/notification_throughput_test.js
   ```
5. Export results to `tools/performance/results/<date>-<scenario>.json` and upload to S3 bucket `academy-perf-results` with retention of 180 days.
6. Compare Grafana dashboard **Academy/Communities – Load** panels against SLO thresholds; annotate run.

## GitHub Actions Snippet

```yaml
name: performance-smoke
on:
  workflow_dispatch:
  schedule:
    - cron: '0 5 * * *'
jobs:
  k6:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Install k6
        uses: grafana/setup-k6-action@v1
      - name: Execute community feed load test
        run: |
          cd Academy/tools/performance/k6
          k6 run community_feed_load_test.js \
            -e BASE_URL="${{ secrets.STAGING_API_BASE_URL }}" \
            -e TOKEN="${{ secrets.PERF_TEST_TOKEN }}" \
            --summary-export ../../docs/upgrade/artifacts/community_feed_latest.json
      - name: Publish artefact
        uses: actions/upload-artifact@v4
        with:
          name: performance-feed
          path: Academy/docs/upgrade/artifacts/community_feed_latest.json
```

## Reporting

- Store executed command logs, environment variables (non-secret), and summary metrics inside `docs/upgrade/artifacts/`.
- Update `docs/upgrade/section_3_deliverables_summary.md` with pass/fail status and deviations.
- Raise incident ticket if thresholds breach for two consecutive runs.
