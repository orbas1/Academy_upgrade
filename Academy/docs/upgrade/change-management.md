# Change Management Checklist – Laravel 11 Upgrade

## 1. Pre-Submission
- [x] Backup evidence attached (MySQL, Redis, asset manifest).
- [x] Compatibility report from `tools/preflight/compatibility_audit.sh` uploaded to CAB ticket.
- [x] Runbook links and rollback plan documented.

## 2. CAB Review Inputs
- Change summary with scope, risk rating, and fallback steps.
- Stakeholder approvals (Engineering, Product, Support).
- Scheduled window confirmation and on-call roster acknowledgement.

## 3. Pre-Deployment Control List
1. Notify stakeholders 48 hours prior via email + Slack template.
2. Lock production deploy pipeline except for approved upgrade jobs.
3. Validate feature flag defaults in `config/feature-flags.php` (`communities=false`, `webauthn=false`).
4. Confirm blue environment health checks (CPU < 60%, queue depth < 100).
5. Execute pre-flight script on staging and production bastions.

## 4. Deployment Execution
- Maintain rolling log in Ops channel with timestamps.
- Capture screenshots of health dashboards before/after switchover.
- Smoke tests: login, course playback, payments, community feature flags.

## 5. Post-Deployment
- Switch monitoring alerts back to standard thresholds.
- Update CAB ticket with final status, attach logs, and close within 24 hours.
- Schedule retrospective within 3 business days.

## 6. Templates
- Notification template: `docs/upgrade/runbooks/communication-template.md`.
- Rollback playbook: `docs/upgrade/runbooks/rollback-procedure.md`.

This checklist is version-controlled to satisfy audit requirements and must be referenced for every blue/green cutover during the upgrade program.
