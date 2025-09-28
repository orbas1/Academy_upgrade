# Section 7.5 – Audit & Compliance Controls

## Compliance Objectives
- Provide immutable logging for all admin actions, payouts, moderation decisions, and configuration changes.
- Align with SOC 2, GDPR, and enterprise customer security questionnaires.

## Audit Data Pipeline
1. Capture via Laravel events emitting `AdminActionLogged` with contextual metadata.
2. Persist to OLTP `admin_audit_log` (append-only) and queue to Kafka topic `audit.events`.
3. Stream to:
   - S3 WORM bucket with 7-year retention
   - Snowflake `f_admin_audit` table for reporting
   - SIEM (Splunk) for real-time threat detection

## Evidence & Reporting
- Automated monthly report listing critical actions (role changes, payout updates, data exports) stored in GRC tool.
- Dashboard widget (Section 7.1 Settings tab) summarizing audit stats and outstanding review tasks.
- API endpoint `GET /api/admin/audit-log` with pagination, filters, and export to CSV (rate limited).

## Compliance Workflows
- Trust & Safety review queue for escalated moderation cases with second-level approval requirement.
- Finance Ops approvals for payout changes captured in `finance_approvals` table with digital signatures.
- Periodic control testing (quarterly) verifying log immutability and retention policies.

## Security & Privacy
- Audit records contain hashed user identifiers where possible; sensitive details (payment metadata) masked.
- Access restricted via RBAC; only Trust & Safety and Compliance roles can view full payloads.
- Alerts when audit write volume deviates >50% from 30-day baseline (possible tampering or outage).

## Runbooks & Incident Handling
- `docs/upgrade/runbooks/audit-log-investigation.md` outlines steps for investigating anomalies (authored below).
- Incident severity matrix aligning with security incident response procedures (Section 2.9).

## Testing
- Automated integration test verifying audit entries created for each admin API endpoint.
- Chaos test simulating database failure; ensures Kafka stream continues buffering events.
- Quarterly restore drill retrieving logs from S3 to verify retention integrity.
