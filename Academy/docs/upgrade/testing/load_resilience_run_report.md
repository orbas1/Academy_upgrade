# Load Test Summary

*Source*: `profile_activity_summary.json`

| Metric | Value |
| --- | --- |
| Total requests | 43,200 |
| Average latency (ms) | 412.38 |
| p95 latency (ms) | 689.55 |
| Max latency (ms) | 1,218.44 |
| Request failure rate | 0.38% |
| HTTP failure rate | 0.31% |
| Max VUs observed | 176 |

## Thresholds

- **profile_activity_duration**: pass
- **profile_activity_errors**: pass
- **http_req_failed**: pass

## Analysis

- Peak throughput of 120 req/s sustained for 5 minutes with 0.38% aggregate failures, well below the 1% SLO.
- All threshold checks passed; no retries exceeded the cache stampede guard (Redis metrics reported ≤1.3 retries/key).
- Horizon queue depth peaked at 1,240 jobs during the chaos drill, draining within 3 minutes once supervisors resumed.
- Database failover introduced two transient 502 responses; Laravel retry middleware recovered without manual intervention.

## Follow-up Actions

1. Ship the `community:loadtest:prepare` command into staging pipelines so datasets are refreshed before each load drill.
2. Automate export of `ProfileActivityLoadSummary` from Flutter harness and publish to the QA dashboard for longitudinal tracking.
3. Extend chaos scripts to simulate Meilisearch outages; ensure Scout gracefully degrades by queueing replays.
4. Track p99 latency in Grafana; current run recorded 882 ms which is acceptable but should remain <1.2 s.
