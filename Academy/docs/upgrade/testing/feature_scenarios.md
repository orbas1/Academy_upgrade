# Stage 12.2 – Feature Testing Scenario Design

Stage 12.2 concentrates on end-to-end feature validation for the Stage 11 migration surfaces (profile activity projection, community membership tooling, and the Flutter account experience). The scenarios below link directly to the automated coverage that now runs inside the Laravel and Flutter suites.

## 1. Profile Activity API & Feature Flag

| Scenario | Surface | Fixtures | Assertions | Automation |
| --- | --- | --- | --- | --- |
| Authenticated member receives paginated profile activity | Laravel API (`GET /api/v1/me/profile-activity`) | `ProfileActivityFactory`, joined community, feature flag enabled | JSON envelope exposes cursor metadata, context payload, and community summary | `tests/Feature/Api/Profile/ProfileActivityFeatureTest::test_it_returns_paginated_activity_with_context` |
| Feature flag disabled hides endpoint | Laravel API | Feature flag forced off, authenticated token | Middleware responds `404` and no activity is queried | `tests/Feature/Api/Profile/ProfileActivityFeatureTest::test_it_returns_not_found_when_feature_disabled` |
| Anonymous request rejected | Laravel API | No auth token | Response returns `401` with structured error envelope | `tests/Feature/Api/Profile/ProfileActivityFeatureTest::test_it_requires_authentication` |
| Community scoping filters timeline | Laravel API | Two communities with mixed activities | Payload only returns the requested community’s events | `tests/Feature/Api/Profile/ProfileActivityFeatureTest::test_it_can_filter_activity_by_community` |

## 2. Community Migration Tooling

| Scenario | Surface | Fixtures | Assertions | Automation |
| --- | --- | --- | --- | --- |
| Seed baseline community taxonomy | Artisan `community:seed-baseline` | Empty categories, levels, rules tables | Seeder repopulates defaults idempotently | `tests/Feature/Console/CommunitySeedBaselineCommandTest::test_it_seeds_foundation_records` |
| Membership backfill from classroom enrollments | Artisan `community:backfill-membership` | Community linked to classroom, enrollment record | Members table gains active record with backfill metadata and command remains idempotent | `tests/Feature/Console/CommunityBackfillMembershipCommandTest::test_it_backfills_memberships_and_is_idempotent` |
| Legacy activity migration covers posts/comments/completions | Artisan `community:migrate-legacy-activity` | Community post, comment, certificate | Projection table contains denormalized rows for each artifact, second run is a no-op | `tests/Feature/Console/CommunityMigrateLegacyActivityCommandTest::test_it_migrates_posts_comments_and_completions` |
| Feature enablement writes rollout metadata | Artisan `community:enable-feature` | Community slug, segments, rollout percentage | JSON feature stores updated, community settings capture rollout | `tests/Feature/Console/CommunityEnableFeatureCommandTest::test_it_updates_feature_flags_and_rollout_metadata` |

## 3. Flutter Mobile Experience

| Scenario | Surface | Fixtures | Assertions | Automation |
| --- | --- | --- | --- | --- |
| Repository paginates and tracks availability | `CommunityRepository.loadProfileActivity` | Queue of fake API responses, in-memory cache | Cursor is stored, availability flag remains true, repeated calls do not re-fetch when exhausted | `test/features/communities/profile_activity_repository_test.dart::tracks cursors and availability toggles on success` |
| Feature flag error transitions to unavailable state | `CommunityRepository.loadProfileActivity` | Fake API throwing `FeatureUnavailableException` | Repository rethrows and toggles availability to false | `test/features/communities/profile_activity_repository_test.dart::marks feature unavailable and rethrows when flag disabled` |

## 4. Execution & Reporting

* Laravel feature specs execute with `php artisan test --filter=ProfileActivityFeatureTest`, `--filter=CommunityBackfillMembershipCommandTest`, `--filter=CommunityMigrateLegacyActivityCommandTest`, and `--filter=CommunityEnableFeatureCommandTest` to provide deterministic coverage on sqlite fixtures.
* Flutter integration specs run via `flutter test test/features/communities/profile_activity_repository_test.dart`, validating repository behavior without relying on a live backend.
* Command tests rely on idempotent factories and clean up generated rollout JSON between runs to keep the storage layer deterministic.
* All suites emit structured JSON envelopes conforming to the API response builder, ensuring downstream clients (Flutter, web) consume consistent metadata for pagination and feature gating.

## 5. Stage 12.3 – End-to-End Harnesses

| Scenario | Surface | Fixtures | Assertions | Automation |
| --- | --- | --- | --- | --- |
| Community join → subscribe → compose → react flow | Browser harness at `/testing/community-flow` (Laravel Dusk) | `CommunityEndToEndHarness` orchestrates owner/member users, paid tier, post, comment, reaction, points | JSON payload contains status `ok`, 2 members, subscription `active`, leaderboard entry, notification snapshot | `tests/Browser/CommunityFlowE2ETest::test_complete_community_flow_executes_successfully` |
| CLI preparation for browser/mobile runs | Artisan `community:e2e:setup` | Optional fresh migration, baseline seed, flag enablement, harness execution | Command exits successfully, writes `storage/app/testing/community_flow_report.json` with audited meta/report path | `tests/Feature/Console/CommunityE2ESetupCommandTest::test_command_prepares_environment_and_persists_report` |
| Mobile account screen renders recent contributions | Flutter integration (`integration_test/profile_activity_flow_test.dart`) | `InMemoryCommunityApiService`, mocked manifest & queue health, seeded shared preferences | Contributions card renders items, load-more fetches remaining entries, notifier has 5 activities & no further pages | `integration_test/profile_activity_flow_test.dart::renders and paginates recent contributions` |

## 6. Stage 12.4 – Load & Resilience

| Scenario | Surface | Fixtures | Assertions | Automation |
| --- | --- | --- | --- | --- |
| Profile activity sustained read load | k6 script `tools/testing/load/profile_activity.js` | `community:loadtest:prepare` dataset, feature flag enabled | `p95` latency ≤ 800 ms, failures < 1%, cursor pagination stable across 120 req/s peak | `k6 run ... --summary-export=docs/upgrade/testing/fixtures/profile_activity_summary.json` + `analyse_k6_summary.php` |
| Queue outage chaos drill | Horizon supervisors paused | Same dataset + 5k queued notifications | Queue drains < 5 min, API failure rate < 1% during outage | Manual via Horizon CLI (documented in `load_resilience_plan.md`) |
| Flutter repository concurrency load | `ProfileActivityLoadDriver.run()` | `InMemoryCommunityApiService` seeded with 5 cursors | Summary exposes throughput > 800 req/min, failure count = 0, p95 latency matches API SLO | `test/features/communities/profile_activity_load_driver_test.dart` |
