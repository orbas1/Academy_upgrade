# Section 6.4 – Data Governance & Privacy Controls

## Governance Charter
- Establish Analytics Governance Council (Product, Data Engineering, Security, Legal, Privacy).
- Monthly review of schema changes, consent metrics, retention schedules, and incident reports.

## Consent & Preferences
- Central consent service storing `analytics_opt_in`, `marketing_opt_in`, `notifications_opt_in` with timestamps and channel-specific overrides.
- Consent captured during onboarding with explicit opt-in, logged to `consent_events` table.
- APIs expose consent state to clients for gating instrumentation (see Section 6.2).

## Data Retention
- Raw event storage (S3) retained for 13 months, then archived to Glacier.
- Aggregated warehouse tables retain 36 months for trend analysis.
- Differential privacy noise applied before exposing metrics to communities with < 20 active members.

## Access Control
- Role-based access (Snowflake RBAC) restricting raw PII to `PII_ACCESS` role.
- Community admins receive aggregated views only; no row-level personal data.
- Audit logs captured via Snowflake access history and forwarded to SIEM.

## Data Quality & Lineage
- dbt sources documented with tests for uniqueness, not null, accepted values.
- Data Catalog (Amundsen) holds lineage diagrams linking OLTP → Kafka → Snowflake.
- SLA: 99.5% of events available in warehouse within 5 minutes.

## Incident Response
- Analytics incident playbook triggered when data loss > 1% or unauthorized access detected.
- Steps: Contain (disable exports), Triage (identify scope), Backfill (replay from Kafka), Postmortem (root cause, actions).

## Compliance Alignment
- GDPR & CCPA: Right to access/export via `/privacy/data-export` service; events filtered by hashed user id.
- SOC 2: Evidence of quarterly access reviews stored in GRC tool.
- FERPA considerations for education communities; ensure no grade data in analytics events.

## Change Management
- Schema updates require PR with JSON schema diff, governance council approval, and versioned release notes.
- Automated tests ensure no breaking changes deployed without new version suffix.

## Metadata & Documentation
- Maintain README in `analytics/` repo with event definitions, owners, sample payloads.
- Data dictionary auto-generated from dbt metadata and published to internal portal weekly.
