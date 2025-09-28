# Section 2.2 – Session, CSRF, Cookies Hardening

## Overview
Section 2.2 locks down the Laravel session stack by defaulting all cookies to
secure and strict transport policies, wiring the project to an external secrets
manager, and defining an operator runbook for rotating sensitive keys without
user-visible downtime.

## Implementation Summary
- **Secure cookie defaults.** `config/session.php` now infers the primary domain
  from `APP_URL`, forces the `secure` attribute to `true` (with an opt-out
  override), and promotes `SameSite=strict` unless an environment explicitly
  relaxes it for cross-application embeds.
- **Environment template updates.** `.env.example` documents the new session
  controls (`SESSION_DOMAIN`, `SESSION_SECURE_COOKIE`, `SESSION_SAME_SITE`) and
  introduces secret manager coordinates so deployment automation can hydrate the
  runtime configuration from AWS Secrets Manager (or an equivalent vault).
- **Secrets retrieval script.** `tools/secrets/pull_secrets.sh` provides a
  repeatable CLI workflow for exporting the encrypted payloads into Laravel’s
  `.env` while keeping the authoritative values inside the secrets store.
- **Key rotation playbook.** `docs/upgrade/runbooks/app-key-rotation.md`
  formalises the expand/migrate/contract approach for rotating `APP_KEY` and
  related secrets across blue/green environments.

## Configuration Matrix
| Concern | Default | Override Guidance |
| --- | --- | --- |
| `SESSION_DOMAIN` | Derived from `APP_URL` | Set to parent domain when sharing auth across sub-domains. |
| `SESSION_SECURE_COOKIE` | `true` | Only disable for local HTTP development using `.env.local`. |
| `SESSION_SAME_SITE` | `strict` | Switch to `lax` when third-party flows (e.g., SSO) require cross-site posts. |
| Secrets driver | `aws` | Swap for `gcp`/`vault` by updating `SECRETS_MANAGER_DRIVER` and automation script. |

## Deployment Workflow
1. CI/CD retrieves the environment secret bundle using
   `tools/secrets/pull_secrets.sh` and writes a temporary `.env` artefact on the
   target host.
2. Laravel boots with hardened session cookies, guaranteeing HTTPS-only
   transport and CSRF protection aligned to the stricter SameSite setting.
3. Application load balancers continue serving legacy sessions while the new
   configuration is rolled out; cookies regenerate on next request without
   forcing logouts.

## Validation Checklist
- Run `php artisan about` to confirm `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE`,
  and `SESSION_SAME_SITE` values match the target environment.
- Use browser dev tools or automated smoke tests to verify `Set-Cookie`
  responses include `Secure`, `HttpOnly`, and `SameSite=strict`.
- Execute the key rotation runbook quarterly (or after any security incident)
  and capture the report in the compliance repository.

With these controls in place, session fixation, downgrade, and stolen secret
risks are significantly reduced while providing clear operational guidance for
secrets lifecycle management.
