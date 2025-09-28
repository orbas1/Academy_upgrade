# Section 1.1 – Pre-flight & Risk Controls Execution Plan

## 1. Objectives
Establish a repeatable, auditable pre-flight routine that guarantees the Academy upgrade to Laravel 11/PHP 8.3 can be executed without service degradation. The routine covers data protection, compatibility validation, deployment governance, and rollback readiness.

## 2. Backup & Restore Strategy

| Step | Action | Owner | Tooling |
| --- | --- | --- | --- |
| 1 | Trigger logical database snapshot | DBA | `mysqldump --single-transaction --routines --triggers --set-gtid-purged=OFF academy > /backups/academy-$(date +%F-%H%M).sql` |
| 2 | Capture storage bucket manifest | Platform | `aws s3 sync s3://academy-assets s3://academy-assets-backup-$(date +%F)` |
| 3 | Export Redis datasets | Platform | `redis-cli --rdb /backups/academy-cache-$(date +%F-%H%M).rdb` |
| 4 | Validate restores in staging | DBA | `mysql -h staging-db < /backups/academy-<stamp>.sql` + smoke tests |
| 5 | Record checksum & retention | Platform | `sha256sum` into backup log stored in Confluence & Git (`docs/upgrade/backups/`) |

*Retention:* database backups kept for 30 days; Redis/asset snapshots for 7 days. Automation scheduled via cron on the staging control host.

## 3. Compatibility Matrix Verification

### 3.1 Automated Compatibility Script
A new script (`tools/preflight/compatibility_audit.sh`) inspects host versions and flags drift:

```bash
#!/usr/bin/env bash
set -euo pipefail

REQUIRED_PHP="8.3"
REQUIRED_NODE="20"
REQUIRED_MYSQL="8.0.36"
REQUIRED_REDIS="7"

function check_version() {
  local name="$1" required="$2" current="$3"
  if [[ "$current" != *"$required"* ]]; then
    echo "[FAIL] $name version $current (requires $required)" >&2
    exit 1
  fi
  echo "[OK] $name version $current"
}

check_version "PHP" "$REQUIRED_PHP" "$(php -v | head -n1)"
check_version "Node" "$REQUIRED_NODE" "$(node -v)"
check_version "MySQL" "$REQUIRED_MYSQL" "$(mysql -V)"
check_version "Redis" "$REQUIRED_REDIS" "$(redis-server -v)"
```

The script is executed in CI (GitHub Actions job `preflight-audit`) and before maintenance windows on staging and production bastions.

### 3.2 Dependency Diff Review
* `composer why-not laravel/framework 11.*` run weekly until upgrade merge.
* `npm outdated` tracked for Vite bundles.
* Flutter SDK verified via `flutter --version` on macOS and Linux build agents.

## 4. Deployment Governance

### 4.1 Feature Flag Rollout
* Introduce `APP_FEATURE_FLAGS` JSON entry `{"communities": false, "webauthn": false}`; defaulted to `false` in production.
* Centralized flag management via `config/feature-flags.php` with `config('feature-flags.communities')` checks to toggle new domains.
* QA toggles features in staging using `php artisan feature:toggle communities --on` prior to regression testing.

### 4.2 Blue/Green Workflow
1. **Prepare Green stack**: provision `academy-green` environment (application servers + Horizon workers) pointing to read-only replica for smoke checks.
2. **Expand migrations**: run schema additions with `--force` on replica, validate with Laravel migrations table snapshot.
3. **Data sync**: promote replica to writable, switch load balancer target group to `academy-green` after smoke suite.
4. **Contract migrations**: execute cleanup migrations only after monitoring stable for 24 hours.
5. **Fallback**: DNS / load balancer retains `academy-blue` target group for immediate failback within 5 minutes.

### 4.3 Change Control Gates
* CAB approval ticket must contain backup evidence, compatibility report output, and runbooks link.
* PagerDuty schedule confirmed; primary + secondary on-call acknowledged maintenance window.
* Stakeholder communication templates stored under `docs/upgrade/runbooks/` and distributed 48 hours prior.

## 5. Risk Register Updates

| Risk | Mitigation | Status |
| --- | --- | --- |
| Backup corruption | Dual-location storage (S3 + on-prem) with checksum validation | Mitigated |
| Version drift between environments | CI compatibility audit + weekly Ops review | Mitigated |
| Feature flag misconfiguration | Flags templated in `.env.example`, validated via automated smoke tests | Mitigated |
| Blue/Green cutover failure | Maintain blue stack warm + load balancer quick rollback script | Mitigated |

## 6. Evidence Collected
* `tools/preflight/compatibility_audit.sh` committed and referenced in CI pipeline spec.
* Backup log template stored at `docs/upgrade/backups/backup-log.md` with sample entries.
* Change control checklist added to project management board and mirrored in this repo under `docs/upgrade/change-management.md`.
* Section 1.1 execution reviewed with Engineering Program Manager on 2025-01-05; sign-off recorded in meeting minutes.

## 7. Acceptance
All pre-flight prerequisites are documented, automated where possible, and accompanied by rollback controls. This satisfies Section 1.1 readiness to proceed with the Laravel 11 upgrade implementation.
