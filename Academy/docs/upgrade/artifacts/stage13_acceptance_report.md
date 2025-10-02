# Stage 13 – Acceptance Evidence & Audit Summary

## 1. Overview

Stage 13 introduces an automated acceptance evidence pipeline that surfaces completion and quality metrics across the Laravel API and Flutter client. The tranche focuses on translating the requirement catalogue in `config/acceptance.php` into executable checks, exposing the results through authenticated surfaces, and ensuring operational visibility across web and mobile.

## 2. Backend Deliverables

- **Config-driven requirements** — `config/acceptance.php` enumerates the four acceptance domains (domain models, API/services, mobile parity, operational tooling) with class/file assertions and evidence references.
- **Evaluator service** — `App\Support\Acceptance\AcceptanceReportService` materialises requirement definitions, executes class/file/config checks, and builds per-requirement completion + quality scores.
- **HTTP surface** — `App\Http\Controllers\Api\V1\Ops\AcceptanceReportController` exposes `/api/v1/ops/acceptance-report`, guarded by the new `acceptance.report.view` gate (`config/authorization.php`, `AuthServiceProvider`).
- **Operational CLI** — `php artisan acceptance:report` renders tabular or JSON acceptance summaries for runbook automation.

## 3. Mobile Deliverables

- **Shared models/service** — `AcceptanceReportService` (Flutter) mirrors the API contract with offline caching, token refresh handling, and typed models for checks, requirements, and summary metrics.
- **Operations dashboard** — `AcceptanceReportScreen` in the mobile app surfaces completion/quality widgets, requirement drill-downs, and evidence lists with pull-to-refresh + cache fallback messaging.
- **Account navigation** — The account screen now links to both Migration Runbooks and the new Acceptance Report screen for operations staff.

## 4. Verification & Quality

- **Automated tests** — Laravel feature + unit tests assert the API response contract (`AcceptanceReportTest`, `AcceptanceReportServiceTest`). Flutter unit tests exercise the mocked HTTP synchronisation and caching logic.
- **Role-gated access** — Only owners/admins with `acceptance.report.view` may retrieve acceptance payloads; all responses include generation timestamps and aggregate metrics for audit trails.
- **Documentation & tracking** — Progress tracker updated with Stage 13 metrics; this report anchors evidence for programme reviews.

## 5. Acceptance Metrics Snapshot

| Requirement | Title | Completion | Quality | Status |
| --- | --- | --- | --- | --- |
| AC-01 | Community domain foundation | 100% | 100% | Pass |
| AC-02 | API, policies, and services | 100% | 100% | Pass |
| AC-03 | Mobile community parity | 100% | 100% | Pass |
| AC-04 | Operational readiness & reporting | 100% | 100% | Pass |

Aggregated summary from `php artisan acceptance:report --format=json`:

- Requirements: **4/4** passing.
- Checks: **21/21** satisfied across config, classes, and mobile artefacts.
- Completion: **100%**.
- Quality: **100%**.

## 6. Validation Evidence

- `php artisan acceptance:report --format=json` produces the metrics above with deterministic ordering for audit pipelines.
- `tools/testing/run_full_test_suite.sh` (scoped Pint via `PINT_PATHS`) executes PHPUnit, PHPStan, and Pint on the new acceptance surfaces with Flutter steps skipped due to absent SDK.
- Feature gate verified in acceptance feature test ensuring `acceptance.report.view` succeeds for owners and denies for unauthorised users.

## 7. Next Steps

- Wire acceptance metrics into CI dashboards for build-time gates.
- Extend config to cover additional sections (e.g., payments, observability) as future stages unlock.
