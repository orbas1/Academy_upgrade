# Stage 12.1 – Unit Testing Coverage Blueprint

## Objectives

- Establish deterministic unit-test coverage across community migration/backfill surfaces and supporting domain services.
- Define minimum coverage thresholds enforced in CI for Laravel (`70%` line, `80%` class-level) and Flutter (`65%` line) projects.
- Document prioritised components, data fixtures, and reporting artefacts required for executive sign-off.

## Scope & Priorities

| Layer | Components | Rationale | Owners |
| --- | --- | --- | --- |
| Laravel Domain | `CommunityMembershipBackfillService`, `ProfileActivityMigrationService`, `CommunityPointsService`, `FeatureRolloutRepository` | Drives data correctness and rollout guardrails. | Platform Eng. |
| Laravel HTTP | `ProfileActivityController`, `EnsureFeatureIsEnabled` middleware, auth flows | Guarantees API contract for mobile/web. | API Guild |
| Flutter | `CommunityNotifier`, `CommunityRepository`, `AccountScreen` contributions widget | Ensures mobile parity and regression gating. | Mobile Guild |
| Shared Utilities | `get_phrase`, `CommunityCourseLinkResolver`, config caches | Reduces flaky bootstrapping & localisation failures. | Enablement |

## Coverage Targets

- **Laravel:**
  - Unit suite executes via `php artisan test --testsuite=Unit` with sqlite in-memory profile.
  - Coverage threshold enforced using PHPUnit config: `line >= 70%`, `classes >= 80%`, `functions >= 75%`.
  - Critical service classes listed above require explicit assertion of idempotency and error handling.
- **Flutter:**
  - `flutter test --coverage` executed with fake API clients; enforce minimum 65% line coverage for community feature packages.
  - Snapshot golden tests added for `AccountScreen` contributions card to guard UI regressions.

## Tooling & Reporting

- PHPUnit configuration updated to emit `coverage-clover storage/logs/coverage.xml` for ingestion by Sonar or Codecov.
- GitHub Actions job `ci-tests` extended with matrix steps: `phpunit-unit`, `phpunit-feature`, `flutter-unit`, `flutter-analyze`.
- Coverage badges exported to `docs/upgrade/artifacts/coverage/` and referenced in executive dashboard.
- `Web_Application/Academy-LMS/tools/testing/enforce_coverage.php` parses Clover reports and enforces thresholds (lines ≥70%, functions ≥75%, classes ≥80%).

## Fixture Strategy

- SQLite migrations executed in-memory with deterministic factories (UUID/idempotency seeded per model).
- Flutter harness uses mock JSON payloads recorded from staging API (`docs/upgrade/fixtures/profile_activity.json`).
- Sensitive data masked using the `anonymiseFixture` Artisan command before committing artefacts.

## Acceptance Checklist

- [x] Blueprint approved by Platform, API, and Mobile guild leads.
- [x] Critical service tests (membership + activity migrations) implemented and green.
- [ ] Feature and Flutter suites upgraded (tracked in Stage 12.2 tasks).
- [x] Coverage reporting wired to CI with storage under `storage/logs/coverage.xml`.

## Next Steps

1. Expand unit coverage to `CommunityPointsService` and search document builders (Stage 12.1 implementation backlog).
2. Author feature-test scenario catalogue for Stage 12.2, including API pagination, flag toggles, and error states.
3. Align Flutter widget tests with server fixtures once feature API stabilises.
