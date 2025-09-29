# Performance & Resilience Test Harness

This folder hosts repeatable tooling that supports Section 3 performance deliverables.

## Structure

- `k6/` – Load and resilience scripts executed against staging/pre-production APIs.
- `results/` – JSON/CSV exports from k6 or auxiliary tooling (git-ignored by default). Create the folder locally to store latest runs.
- `scripts/` – Bash helpers that orchestrate cache warmups, Horizon scaling, and Octane toggles (future extension).

## Prerequisites

- [k6](https://k6.io/docs/getting-started/installation/) v0.48+
- Node.js 20.x for test data fabrication utilities.
- Access to staging environment with seeded community data.

## Running the Baseline Feed Test

```bash
export ACADEMY_API_BASE_URL="https://staging.api.academy.local"
export ACADEMY_API_TOKEN="<personal-access-token>"
# optional overrides
export ACADEMY_VUS=150
export ACADEMY_DURATION="5m"

cd k6
k6 run community_feed_load_test.js \
  -e BASE_URL="$ACADEMY_API_BASE_URL" \
  -e TOKEN="$ACADEMY_API_TOKEN" \
  -e VUS="${ACADEMY_VUS:-120}" \
  -e DURATION="${ACADEMY_DURATION:-3m}" \
  --out json=../results/feed_baseline.json
```

## Interpreting Results

- **Pass:** `http_req_duration{p(95)} <= 250` ms and error rate `< 0.1%`.
- **Warning:** `http_req_failed` > 0 but under 1% – investigate Horizon worker saturation.
- **Fail:** `http_req_duration{avg}` exceeds 300 ms or k6 aborts from threshold; execute rollback plan (Section 3.8) and scale.

## CI Integration

Use the GitHub Actions composite action snippet in `docs/upgrade/section_3_7_load_and_stress_testing.md` to wire automated smoke load tests as part of the release candidate pipeline.

## Observability Hooks

k6 pushes trend metrics to InfluxDB with the `K6_INFLUXDB_*` environment variables when configured, enabling Grafana dashboards for comparisons between builds.
