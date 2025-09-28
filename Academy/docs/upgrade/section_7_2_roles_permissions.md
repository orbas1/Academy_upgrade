# Section 7.2 – Roles, Permissions & Audit Trails

## RBAC Model
- **Global Roles**: Super Admin, Trust & Safety, Finance Ops, Community Ops, Support.
- **Community Scoped Roles**: Owner, Admin, Moderator, Member, Guest.
- Permission matrix stored in `rbac_permissions` table referencing abilities (view, edit, moderate, manage_paywall, export_data, manage_automation).
- Policy classes implemented under `App\Policies\Admin\*` with Gate definitions in `AuthServiceProvider`.

## Admin Access Controls
- Mandatory SSO (SAML/OIDC) for global admin roles with conditional MFA enforcement.
- Device trust tokens (WebAuthn) for privileged actions (payout changes, policy updates).
- Session timeout 30 minutes inactivity; re-auth prompt for sensitive operations.

## Audit Logging
- Append-only log stored in PostgreSQL `admin_audit_log` with columns: `id`, `actor_id`, `actor_role`, `action`, `resource_type`, `resource_id`, `payload_hash`, `ip_address`, `user_agent`, `performed_at`.
- Mirror events streamed to immutable S3 bucket (`audit-logs`) with Object Lock (WORM) retention 7 years.
- UI surface under `/admin/audit-log` with filtering, CSV export, and correlation ID linking to support tickets.

## Change Management
- Permission changes require dual authorization (request + approval). Workflow handled by `admin_permission_requests` table and notifications.
- Weekly report emailed to Security summarizing permission changes and dormant admin accounts.

## Monitoring & Alerts
- Real-time alerts for high-risk actions (role escalation, paywall price change, data export) delivered to Slack and PagerDuty.
- SIEM integration forwarding audit events via syslog for anomaly detection.

## Testing & Validation
- Automated PHPUnit tests verifying Gate coverage and denial cases.
- Security regression tests simulating privilege escalation attempts.
- Quarterly access review checklist stored in `docs/upgrade/runbooks/admin-access-review.md` (to be authored).
