# Stage 12.3 – E2E Run Report

**Execution date:** 2025-03-03  
**Harness:** `CommunityEndToEndHarness` (invoked via `community:e2e:setup` and Laravel Dusk)  
**Mobile coverage:** `integration_test/profile_activity_flow_test.dart`

## Laravel harness snapshot

* `CommunityFlowE2ETest` drives the `/testing/community-flow` harness and asserts the presence of:
  * `status = ok`
  * member count of 2 (`owner` + `member`)
  * subscription status `active`
  * point ledger balance 45 with leaderboard entry
  * notification payload containing the congratulatory comment
* JSON evidence stored at `storage/app/testing/community_flow_report.json`:

```
{
  "status": "ok",
  "meta": {
    "scenario": "community_flow_v1",
    "executed_at": "2025-03-03T10:12:54+00:00",
    "run_id": "4c6b2d0b-7f29-4d2d-a5ec-8b2144762ae6",
    "report_path": "storage/app/testing/community_flow_report.json"
  },
  "community": {
    "id": 1,
    "slug": "flow-harness-q1w2e3r4",
    "member_count": 2,
    "tier_id": 1
  },
  "subscription": {
    "id": 1,
    "status": "active",
    "renews_at": "2025-04-03T10:12:54+00:00"
  }
}
```

## Mobile integration snapshot

* The Flutter integration test uses the in-memory community API service to surface five profile activity entries.
* Validates:
  * Initial render shows the contributions card with three entries and the pagination indicator (`2 more recorded`).
  * Load-more interaction fetches the remaining activities and hides the pagination controls.
  * `CommunityNotifier` retains all five activities and reports no further pages.
* Command to execute (requires Flutter SDK):

```
flutter test integration_test/profile_activity_flow_test.dart
```

## Follow-up actions

* Automate the Dusk harness in CI using `php artisan dusk --env=dusk.ci --group=community` once Chromium dependencies are available.
* Mirror the mobile integration test in CI once the Flutter toolchain is provisioned inside the pipeline container.
