# Admin Access Review Runbook

## Purpose
Ensure administrative access remains limited to authorized personnel and all elevated permissions are periodically validated.

## Cadence
- Quarterly (aligned with SOC 2 requirements) or immediately after major org changes.

## Prerequisites
- Access to IAM audit logs, `admin_audit_log` table, HR roster, and ticketing system.
- Latest permission matrix exported from `/admin/permissions/export`.

## Steps
1. **Prepare Dataset**
   - Export list of admin accounts and roles.
   - Retrieve activity logs for past 90 days.
   - Obtain HR roster to confirm employment status.
2. **Validate Necessity**
   - For each admin, confirm business justification via manager sign-off.
   - Flag dormant accounts (>45 days inactivity) for removal.
3. **Review High-Risk Actions**
   - Filter audit logs for role escalations, paywall changes, data exports.
   - Confirm corresponding approval tickets.
4. **Action Items**
   - Disable accounts lacking justification via `/admin/permissions/revoke` API.
   - Document removals and adjustments in change log.
5. **Report & Archive**
   - Summarize findings, remediation, outstanding risks.
   - Store report in GRC system and update compliance checklist.

## SLA & Ownership
- Owner: Security Operations Lead.
- SLA: Complete review within 5 business days of cycle start.

## Automation Hooks
- Script `artisan admin:access-report` generates CSVs and pushes to secure S3 bucket.
- Slack reminder via workflow builder 1 week before cycle.
