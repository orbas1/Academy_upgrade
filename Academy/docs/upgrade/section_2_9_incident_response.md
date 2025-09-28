# Section 2.9 – Incident Response Finalization

This guide summarizes the incident response assets added for the Academy upgrade.

## Scope
- Security and availability incidents impacting the Communities platform, APIs, mobile apps, or data plane.
- Integration with platform SLOs (API p95 < 250 ms, websocket < 2 s) and security posture requirements.

## Deliverables
1. **Runbooks** – Detailed triage and remediation steps stored under `docs/upgrade/runbooks/`.
2. **Communication templates** – Incident-specific messaging for customers, executives, and regulators.
3. **Tooling** – Automation hooks to capture timelines, create Jira tickets, and trigger PagerDuty incidents.

## Workflow Overview
1. **Detection**
   - Alerts from security scans (`security-scan` workflow) or runtime monitors (WAF, CloudWatch, Crashlytics).
   - Manual reports via support escalations (Zendesk → PagerDuty integration).
2. **Triage**
   - Duty engineer uses the severity matrix to classify the event (SEV0–SEV4).
   - Kick off the `incident-triage.md` runbook, documenting actions in the shared incident doc template.
3. **Containment & Eradication**
   - Follow mitigation steps from the runbook (feature flag kill switches, revoke tokens, isolate workers).
   - Engage the security lead within 15 minutes for SEV0/SEV1 incidents.
4. **Communication**
   - Use the security incident communication template for external notifications.
   - Provide updates every 30 minutes during active mitigation.
5. **Recovery**
   - Validate services via smoke tests and analytics dashboards.
   - Maintain heightened monitoring for 24 hours post-incident.
6. **Post-Incident Review**
   - Schedule a blameless retrospective within 5 business days.
   - File remediation tasks in Jira with owners and due dates.

## Metrics & Reporting
- MTTA/MTTR tracked per incident, target MTTR < 60 minutes for SEV2+.
- Monthly incident drill to validate on-call readiness (documented in runbook).
- Compliance exports stored in the security share (retained for 2 years).

## Integrations
- PagerDuty service: `Academy – Communities Platform`.
- Slack channels: `#academy-incident-warroom` (private) and `#academy-status` (public updates).
- Jira project: `ACSEC` for security follow-ups.
- Google Drive folder `Incident Reports` for final summaries and evidence.
