# Section 7.3 – Metrics & Reporting Framework

## Reporting Goals
- Provide operational, financial, and trust & safety metrics to community admins and internal teams.
- Automate scheduled exports and integrate with BI tools (Metabase, Looker) while respecting RBAC.

## KPI Inventory
- **Operational**: Moderation queue length, SLA compliance, automation job success rate, uptime of integrations.
- **Engagement**: Daily active members, post/comment velocity, reaction rate, onboarding completion.
- **Financial**: MRR, ARPU, ARPPU, churn, failed payments, refunds, LTV.
- **Trust & Safety**: Flag rate per 1k posts, ban appeals, repeat offender count.

## Data Sources & Pipelines
- Real-time metrics via ClickHouse replicating from Kafka streams (`admin.metrics.*`).
- Daily aggregates in Snowflake via dbt models `f_admin_operations`, `f_financial_summary`, `f_trust_safety`.
- Integration with Section 6 analytics events for consistent definitions.

## Deliverables
- Standardized metric definitions stored in `docs/metrics_dictionary.xlsx` (maintained by Data team).
- API endpoints:
  - `GET /api/admin/metrics/overview` (cached 5 min)
  - `GET /api/admin/metrics/export?format=csv&range=...`
- Scheduled reports:
  - Daily moderation digest emailed 07:00 local time.
  - Weekly revenue snapshot to Finance Ops.
  - Monthly compliance report summarizing audit events and access reviews.

## Visualization Guidelines
- Use time-series charts for trends, stacked bar for composition, heatmaps for moderation hotspots.
- Provide drill-down links to raw tables when permitted by role.
- Display SLA indicators with color-coded badges.

## Governance
- Metric ownership documented with contact info for questions/escalations.
- Change requests go through Analytics Governance Council with 2-week notice before production change.

## Testing & Validation
- Contract tests verifying API responses align with metric definitions.
- Snapshot tests for PDF exports ensuring layout stability.
- Regression tests triggered when dbt models change to prevent definition drift.
