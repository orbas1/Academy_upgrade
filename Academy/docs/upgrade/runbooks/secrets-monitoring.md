# Secrets Monitoring Playbook

## Metrics & Alerts
- **CloudWatch Metrics**: `SecretsManagerGetSecretValue.ThrottleCount`, `VaultAuditLog.Errors`, `ParameterStoreThrottles`.
- **Dashboards**: Grafana dashboard `Security / Secrets` aggregates API latency, rotation success counts, and unauthorized attempt spikes.
- **Alerts**:
  - PagerDuty SEV2: Secrets retrieval failures > 5 in 5 minutes.
  - PagerDuty SEV1: Unauthorized Vault access detected (audit log severity >= `warning`).
  - Slack `#academy-security`: Notification for each successful rotation event via SNS subscription.

## Daily Checklist
1. Review Grafana dashboard for anomalies.
2. Confirm last rotation event timestamp < cadence threshold.
3. Validate no IAM changes in AWS Config drift report.
4. Ensure Localstack integration tests passed in nightly CI run.

## Incident Response
1. Triage alert, gather secret ID, requesting principal, and timestamp.
2. Lock down IAM role by disabling policy via Terraform variable `secrets_access_enabled=false` followed by `terraform apply`.
3. Trigger `php artisan security:rotate --secret=<id>` to invalidate cached copies.
4. Rotate affected credentials using `Secret Rotation Checklist` runbook.
5. File incident report within 24 hours including root cause and mitigation.

## Evidence Retention
Archive weekly dashboards and audit logs under `docs/upgrade/backups/metrics/secrets/<YYYY-MM-DD>/` for compliance.
