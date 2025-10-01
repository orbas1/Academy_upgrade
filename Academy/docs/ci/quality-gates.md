# Quality Gate Design & Enforcement

The Academy pipeline enforces enterprise-grade quality gates across the Laravel backend and Flutter mobile client. This design delivers deterministic pass/fail criteria, consistent stakeholder notifications, and auditable evidence for compliance teams.

## Branch Policy Gate

* **Scope**: all pull requests targeting `main` or `develop`.
* **Allowed branch prefixes**: `feature/`, `bugfix/`, `hotfix/`, `chore/`, `task/`, `refactor/`, `release/` with the exact branch `develop` permitted for integration.
* **Automation**: `.github/workflows/ci.yml` job `policy_guard` executes `tools/ci/enforce_branch_policy.php`. Non-conforming branches fail the workflow before any build steps run.
* **Rationale**: prevents ad-hoc branch names, simplifies release notes, and aligns with GitHub protection rules documented in the rollout plan.

## Coverage Thresholds

| Surface | Tooling | Coverage Metric | Minimum Threshold |
|---------|---------|-----------------|-------------------|
| Laravel backend | PHPUnit + Clover | Statement (line) coverage | **75%** (with method ≥ 70%, branch ≥ 65%) |
| Flutter mobile | `flutter test --coverage` + lcov | Executed line coverage | **80%** |

* **Backend enforcement**: `tools/ci/enforce_php_coverage.php` parses `build/coverage/clover.xml`, writes `build/coverage/summary.json`, and fails the job if coverage drops below the thresholds.
* **Mobile enforcement**: `tools/ci/enforce_lcov_coverage.py` evaluates `coverage/lcov.info`, emits `coverage/summary.json`, and halts the run on failure.
* **Artifact trail**: coverage summaries upload as artifacts for audit/regression comparisons (`backend-coverage-summary`, `flutter-coverage-summary`).

## Quality Gate Aggregation

* **Job**: `quality_gates` (see `.github/workflows/ci.yml`).
* **Inputs**: depends on `backend_tests`, `flutter_tests`, `security_scans`, and `infra_scan` results.
* **Outputs**:
  * `coverage/quality-gates-summary.json` (machine-readable)
  * Persistent PR comment `### Quality Gates` (auto-updated on every run)
  * Optional Slack digest via `SLACK_WEBHOOK_URL`
* **Routing rules**:
  * PR comment surfaces ✅/⚪️/❌ icons with coverage deltas.
  * Slack message (if webhook configured) lists backend, mobile, security, and infrastructure gate status with thresholds for quick triage.

## Deployment Gate Coupling

Deployment job `deploy_gate` requires the `quality_gates` job to succeed, preventing manual approvals while any quality signal is degraded. This ensures:

1. Coverage regressions or branch violations block promotion.
2. Security/infra scans must report success before staging approvals appear.
3. Operators receive a consolidated audit trail before triggering production packaging.

## Local Reproduction

Developers can reproduce the gates locally via:

```bash
# Backend
cd Web_Application/Academy-LMS
composer install
php artisan test --coverage-clover build/coverage/clover.xml
php ../../tools/ci/enforce_php_coverage.php build/coverage/clover.xml 75 build/coverage/summary.json

# Mobile
cd "Student Mobile APP/academy_lms_app"
flutter test --coverage
python ../../tools/ci/enforce_lcov_coverage.py coverage/lcov.info 80 coverage/summary.json
```

These commands align with CI execution, ensuring consistent results across environments.
