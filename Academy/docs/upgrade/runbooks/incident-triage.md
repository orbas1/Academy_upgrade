# Incident Triage Runbook

## Purpose
Standardize the first 30 minutes of a security or availability incident affecting the Communities platform.

## Preconditions
- PagerDuty incident triggered with severity level.
- Incident commander (IC) and deputy assigned.

## Timeline
**T+0 – Alert Receipt**
1. Acknowledge PagerDuty alert and join `#academy-incident-warroom`.
2. Declare the incident in the shared Google Doc using the template in `docs/upgrade/runbooks/incident-template.md` (auto-generated via automation script).

**T+5 – Initial Assessment**
1. Identify blast radius (services, tenants, % traffic) and data exposure risk.
2. Capture current state metrics: API error rate, latency, queue depths, login success.
3. Assign scribe to document all actions and timestamps.

**T+10 – Containment Decision**
1. Evaluate kill-switches and feature flags. For security incidents, disable affected entry points and rotate credentials if compromise suspected.
2. If customer communication required, hand off to Communications Lead to draft notice using the security template.

**T+15 – Stakeholder Update**
1. Post first update in `#academy-status` using canned format.
2. If external SLA impacted, notify Customer Success for targeted outreach.

**T+20 – Mitigation Execution**
1. Apply fixes (redeploy, scale out, revoke tokens) per service-specific playbooks.
2. Coordinate with infrastructure team for database failover or CDN rules as needed.

**T+30 – Reassessment**
1. Confirm mitigation effectiveness using smoke tests.
2. Decide on continuing incident (if still active) or moving to monitoring phase.

## Post-Mitigation Checklist
- Capture root cause hypothesis, remediation steps, and outstanding actions.
- Ensure logs, dashboards, and evidence exported to incident folder.
- Schedule post-incident review within 5 business days.

## Escalation Matrix
| Severity | IC Role | Backup IC | Security Lead | Executive Liaison |
|----------|---------|-----------|---------------|-------------------|
| SEV0     | Director of Engineering | Staff Engineer | CISO | CTO |
| SEV1     | Staff Engineer | Senior Engineer | Security Manager | VP Product |
| SEV2     | Senior Engineer | On-call Backend | Security On-call | Head of Support |
| SEV3+    | On-call Backend | On-call Frontend | Security Analyst | Support Manager |

## Tooling Links
- Feature flag console: `https://flags.academy.local`
- Runbook automation repo: `https://git.academy.local/academy/runbooks`
- Metrics dashboard: `https://grafana.academy.local/d/communities/slo`
