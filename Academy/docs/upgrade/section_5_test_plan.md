# Section 5 – Comprehensive Test Plan Execution

This document captures the automated and manual coverage implemented for the Test Plan tranche. The plan exercises unit, feature/API, end-to-end, load, and mobile layers to ensure community upgrades meet enterprise readiness expectations.

## 1. Automated Unit Coverage

| Domain Area | Test Suite | Description |
| --- | --- | --- |
| Points ledger | `tests/Unit/Domain/Communities/Services/CommunityPointsServiceTest.php` | Validates direct awards, rule caps, and reconciliation semantics for the ledger. |
| Leaderboards | `tests/Unit/Domain/Communities/Services/CommunityLeaderboardServiceTest.php` | Confirms time-bounded ranking, persisted metadata, and ordering logic. |
| Paywall access | `tests/Unit/Domain/Communities/Services/CommunityPaywallServiceTest.php` | Ensures entitlement checks, temporary grants, and subscription lookups behave correctly. |
| Membership lifecycle | `tests/Unit/Domain/Communities/Services/CommunityMembershipServiceTest.php` | Covers join/restore, status transitions, and leave flows including follow cleanup. |

Run locally:

```bash
php artisan test --filter=Unit\\Domain\\Communities\\Services
```

## 2. API & Feature Tests

| Capability | Test Suite | Coverage |
| --- | --- | --- |
| Admin authorization | `tests/Feature/Api/AdminCommunityAuthorizationTest.php` | Verifies Sanctum-guarded admin routes enforce policy gates. |
| Feed cursoring | `tests/Feature/Community/CommunityFeedCursorPaginationTest.php` | Asserts keyset pagination emits cursors and advances without duplication. |
| Paywall enforcement | `tests/Feature/Community/CommunityFeedPaywallTest.php` | Confirms paid posts remain hidden until subscriptions exist. |
| Stripe webhooks | `tests/Feature/Billing/StripeWebhookControllerTest.php` | Exercises success, signature failure, and hard failure branches. |

Execute:

```bash
php artisan test --filter=Feature
```

## 3. Browser E2E Harness

- **Harness Route**: `/testing/community-flow` (testing environment only).
- **Scenario**: Join → subscribe → compose → like/comment → notification dispatch → leaderboard regeneration.
- **Automation**: `tests/Browser/CommunityFlowE2ETest.php` drives the harness via Laravel Dusk and validates the JSON payload emitted by `CommunityFlowTestController`.

Run:

```bash
php artisan dusk --filter=CommunityFlowE2ETest
```

> The harness uses real services (membership, paywall, points, notifications) and queues execute synchronously under the test environment to provide deterministic results.

## 4. Performance & Load

- **Script**: `tools/performance/k6/community_feed_capacity_test.js`
- **Targets**: 500 RPS feed reads, 100 RPS post writes, thresholds at p95 < 350 ms read / < 420 ms write, failure rate <0.5%.
- **Invocation**:

```bash
cd tools/performance/k6
k6 run community_feed_capacity_test.js \
  -e BASE_URL="https://staging.api.academy.local" \
  -e TOKEN="$SANCTUM_TOKEN" \
  --summary-trend-stats="avg,p(90),p(95),p(99)"
```

Artifacts (JSON + HTML dashboards) should be stored in `docs/upgrade/artifacts/` for regression tracking.

## 5. Mobile (Flutter) QA

### Repository Tests

- **Suite**: `test/features/communities/community_feed_repository_test.dart`
- Validates offline cache priming, cursor bookkeeping, and API fallbacks for the Riverpod-powered repository by using fake API/cache implementations.

Run with Flutter tooling:

```bash
cd "Student Mobile APP/academy_lms_app"
flutter test test/features/communities/community_feed_repository_test.dart
```

### Manual Mobile Checklist

1. Authenticate with Sanctum token injection via `AuthSessionManager` mock.
2. Toggle airplane mode to confirm cached feed retrieval uses `CommunityCache` TTLs.
3. Execute deep link to `/communities/{slug}/posts/{id}` verifying in-app routing displays composed post.
4. Validate push notification payload renders actionable CTA using `NotificationRouter` preview screen.

## 6. Reporting & CI Hooks

- Add the following to CI gate once headless Chrome is available:

```yaml
- name: Feature & API tests
  run: php artisan test --testsuite=Feature
- name: Browser harness
  run: php artisan dusk --filter=CommunityFlowE2ETest
```

- Performance smoke job (`performance-smoke`) should be extended to call the new capacity script weekly.

## 7. Traceability

| Requirement | Evidence |
| --- | --- |
| Unit services coverage | PHPUnit suites above, generated JUnit reports in `storage/logs/tests`. |
| API guards & pagination | Feature suites verifying auth and response envelopes. |
| End-to-end flow | Harness payload includes subscription, points, notifications, leaderboard entries. |
| Load targets | k6 summary exports + Grafana dashboards. |
| Mobile readiness | Flutter unit tests + manual validation checklist. |

All suites execute against real database transactions (in-memory SQLite during tests) ensuring parity with production schema and services.
