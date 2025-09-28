# Audit Log Investigation Runbook

## Trigger Conditions
- SIEM alert for unusual admin action volume.
- DataDog anomaly detection on `audit_events_per_minute` metric.
- Manual report of suspected unauthorized admin activity.

## Immediate Actions
1. Acknowledge alert in PagerDuty.
2. Notify Trust & Safety and Security Incident Commander.
3. Preserve evidence by exporting relevant log segments to secured S3 folder.

## Investigation Steps
1. Identify affected communities, actors, and actions using `/api/admin/audit-log` filters.
2. Correlate with authentication logs (SSO provider) and IP geolocation.
3. Verify whether actions had associated approval tickets.
4. Review related application logs for errors or suspicious API calls.
5. Determine scope (single account vs. systemic issue).

## Mitigation
- If compromise suspected, disable affected admin accounts via `/admin/permissions/revoke` and force password reset.
- Revert unauthorized changes (e.g., paywall price adjustments) using audit trail data.
- Engage Legal/Comms if customer impact identified.

## Post-Incident
- Document timeline, root cause, remediation steps in incident report.
- Update detection rules to prevent recurrence.
- Conduct tabletop exercise if gaps identified.

## Contacts
- Security Incident Commander – security-ic@academy.io
- Trust & Safety Lead – safety@academy.io
- Data Engineering On-Call – data-oncall@academy.io
