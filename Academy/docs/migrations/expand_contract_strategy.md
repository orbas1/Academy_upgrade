# Section 11.1 — Expand/Contract Migration Strategy

This runbook codifies the end-to-end plan for moving the Academy platform from the legacy forum-centric data structures to the community-first architecture while ensuring zero downtime for learners and creators. It aligns with the `config/migrations.php` plans and the operational tooling introduced in the Laravel API (`migration:plan` artisan command and `/api/v1/ops/migration-plan` endpoint).

## Guiding Principles

1. **Expand → Backfill → Contract**: All changes follow the phased cadence to avoid risky toggles.
2. **Dual-write safety net**: Writes fan out to legacy and new stores until reconciliation reports stay within a 0.5% delta for seven consecutive days.
3. **Telemetry-first**: Every phase must surface metrics (Prometheus gauges, CloudWatch alarms) before the next phase begins.
4. **Mobile parity gating**: A migration cannot advance if the minimum supported mobile build (see plan) is not live in TestFlight/Play Internal.

## Plan Overview

| Key | Owner | Description | Expand Window | Backfill Window | Contract Window |
|-----|-------|-------------|---------------|-----------------|-----------------|
| `communities_data_convergence` | Communities Platform Guild | Replace legacy forum/geo tables with the v2 community projections and automation feeds. | 10 days | 14 days | 7 days |
| `messaging_pipeline_split` | Messaging Platform Squad | Carve notification infrastructure into a dedicated pipeline with SNS/SQS fan-out. | 5 days (rolling) | 7 days | 5 days |

*The artisan command `php artisan migration:plan` renders these timelines with detailed steps for sprint planning and status reviews.*

## Communities Data Convergence

### Expand
- **DDL rollout** (`ddl-community-core`):
  - Apply migrations `2024_08_01_010000`–`040000` during a maintenance window with Amazon RDS fast DDL enabled.
  - Provision an Aurora reader dedicated to bulk ETL, keeping production replicas available for read queries.
  - Ship repository classes in a dark state guarded by LaunchDarkly flag `community_feed_v2`.
- **API preview surface** (`api-preview-surface`):
  - Expose `/api/v1/communities` preview variant that emits both v1/v2 payload signatures.
  - Sanctum ability `community.preview` added to staff API tokens; contract tests execute against canary staging.

### Backfill & Dual Write
- **Historic replay** (`activity-backfill`):
  - `php artisan community:backfill` streams 90 days of posts/comments with chunked batching and retry queue semantics.
  - AWS DMS replicates media metadata to the analytics schema while Prometheus gauge `community_backfill_drift` reports deltas.
- **Dual-write cutover** (`dual-write-cutover`):
  - LaunchDarkly flag `community_dual_write` increments 10% → 100% over 24 hours.
  - Background reconciliation job compares primary/secondary write counts every five minutes, paging Slack `#on-call-community` if drift exceeds 50 rows.

### Contract
- **Read path cutover** (`read-path-cutover`):
  - API, admin dashboards, and GraphQL resolvers switch to new projections and stop querying forum tables.
  - Observability dashboards confirm zero read traffic to legacy tables for 48 hours.
- **Decommission** (`decommission-legacy`):
  - Glacier archive retains historical tables for 90 days; compliance stores the final row counts in the audit vault.
  - Stored procedures, triggers, and job schedules tied to the old schema are removed via automated Liquibase script.

### Mobile & Web Readiness Gates
- Minimum mobile build **3.2.0** must be distributed to pilot testers before dual-write begins. The Flutter bootstrap now caches the migration plan and surfaces release blockers to Product Ops dashboards (via observability metrics).
- Web admin module flag `admin-community-dashboard` must be live to allow moderation teams to validate parity before legacy shutdown.

## Messaging Pipeline Split

### Expand
- Terraform stack provisions SNS topics + encrypted SQS queues and rotates IAM credentials.
- Laravel queue connection `sns` is introduced with metrics streaming to CloudWatch and Prometheus for delivery latency.

### Backfill
- Artisan job `messaging:backfill-preferences` normalizes opt-in/out payloads into the new schema while GDPR export dry-runs validate retention policies.
- Compliance sign-off recorded in Jira MIG-238 before moving forward.

### Contract
- Laravel notification channels re-point to SNS transport; legacy cron-based digest job is retired only after automated smoke tests verify parity.
- Final send logs exported to Redshift for analytics continuity.

## Operational Controls

- **Command & API tooling**: `migration:plan` provides engineers with immediate insight into the current plan, while `/api/v1/ops/migration-plan` powers dashboards and mobile readiness checks.
- **Alert thresholds**: Prometheus alerts guard dual-write drift, API p95 latencies, and queue depths; CloudWatch alarms watch SNS throughput and Lambda error rates.
- **Rollback Playbooks**: Every step enumerates precise rollback instructions—flag toggles, data purge scripts, Terraform reversions—kept current in the config file.
- **Change approval**: Expand/backfill/contract transitions require approvals from Data Engineering, Site Reliability, Product Ops, and Compliance respectively.

## Communication Cadence

1. **Weekly steering review**: CTO, product, and engineering leads inspect plan progress via the planner command output and Grafana dashboards.
2. **Daily sync during cutover**: 15-minute stand-ups align developers, SRE, and QA, focusing on drift metrics and on-call readiness.
3. **Stakeholder updates**: Product marketing receives automated emails generated from the migration plan API describing upcoming customer-visible impacts.

## Testing & Verification Checklist

- Contract tests execute across API, admin UI, and mobile (integration harness) before advancing phases.
- Load tests (k6) run during expand/backfill to ensure headroom remains ≥30%.
- Chaos drills simulate RDS replica failure and SNS topic throttling during the backfill window.

## Rollback Readiness

- Feature flags default to OFF states and can revert within five minutes.
- Glacier snapshots and S3 archives scripted for emergency restoration.
- Mobile app caches the last known good plan so that QA can revalidate expectations even when the API is in recovery mode.

With this plan codified in both configuration and documentation, Section 11.1 is satisfied: engineering, QA, and operations teams share a single source of truth for the expand/contract journey, complete with tooling hooks and mobile readiness gates.
