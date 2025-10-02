# Test Data & Fixture Strategy

This document captures the strategy that powers the community fixture pipeline introduced in Stage 12 of the upgrade program. It
covers the anonymised data model, refresh cadence, validation gates, and downstream consumers across the Laravel and Flutter test
suites.

## Objectives

1. **Realistic coverage** – provide multi-community data with paid/public posts, cross-timezone membership, tiered paywalls, and
   events so that feature, API, and mobile tests exercise enterprise use cases.
2. **Deterministic generation** – guarantee stable fixtures by sourcing all derived files from a single base dataset and
   deterministic enrichment rules.
3. **Privacy preservation** – ensure data does not include personally identifiable information from production by using curated
   synthetic personas and anonymised analytics.
4. **Cross-platform parity** – expose identical source-of-truth structures to Laravel (JSON fixtures) and Flutter (const Dart maps)
   to keep behaviour aligned between web and mobile.

## Source Dataset

The curated dataset lives at `docs/upgrade/testing/fixtures/community_base_dataset.json`. It defines two representative
communities:

- **Product Leaders Guild** – public, leadership focus, with free + paid tiers and global membership.
- **Creator Engineers Collective** – private, growth focus, emphasising automation heavy workflows and quarterly billing tiers.

Each community captures:

- Membership roster with role/level/points metadata, timezone coverage, and status flags (active, pending, banned).
- Content feed (text, image, video, poll) including engagement counters, tags, and paywall metadata.
- Scheduled events with RSVP counts and host relationships.
- Subscription tiers and points rules that mirror enterprise paywall and gamification rules.

The dataset timestamp (`generated_at`) anchors the deterministic calculations for online presence and recency-based engagement
scoring.

## Enrichment Pipeline

`tools/testing/generate_test_fixtures.py` consumes the base dataset and builds three artefacts:

1. `Web_Application/Academy-LMS/tests/Fixtures/community_fixture_set.json` – payload used by PHPUnit feature/integration tests.
2. `Student Mobile APP/academy_lms_app/test/fixtures/community_fixture_set.dart` – const Dart fixtures for widget/integration
   tests and offline QA harnesses.
3. `docs/upgrade/testing/fixtures/fixture_manifest.json` – metadata, checksums, and record counts to validate fixture integrity.

The enrichment logic applies:

- **Role and timezone distributions** to validate policy gating and presence indicators.
- **Leaderboard projections** that sort by points, level, and tie-breakers to reflect PointsService semantics.
- **Engagement scores** combining reactions/comments/shares with a 48h recency bonus for feed ranking validation.
- **Upcoming events aggregation** filtered against the dataset timestamp for calendar UX testing.
- **Paywall slicing** to expose paid content separately for entitlement checks.

## Validation & Quality Gates

- The manifest stores SHA-256 checksums and record counts for CI verification. GitHub Actions compares the manifest to detect
  drift.
- Fixture generation is idempotent; re-running the script without modifying the base dataset yields identical hashes.
- Laravel and Flutter tests assert on engagement scores, leaderboard ordering, and paywall gating using the generated fixtures.
- A dedicated smoke test (see `tests/Feature/CommunityFixtureIntegrityTest.php`) parses the JSON payload to ensure schema
  alignment before the broader suite executes.

## Refresh Cadence & Process

1. Update `community_base_dataset.json` with new personas or behaviours (for example, additional paywall tiers or experiment
   flags).
2. Run `python tools/testing/generate_test_fixtures.py` to regenerate downstream artefacts.
3. Commit the regenerated fixture files alongside an updated manifest to keep traceability intact.
4. Run `./tools/testing/run_full_test_suite.sh` to validate Laravel + Flutter harnesses against the refreshed fixtures.
5. Archive the manifest and suite logs under `docs/upgrade/testing/fixtures/history/` (automated via CI nightly job).

## Data Governance

- All personas are synthetic; names, emails, and timezone distributions are invented for QA.
- Engagement metrics intentionally exceed baseline to stress-test leaderboard, notification batching, and digest previews.
- The dataset avoids direct course/classroom identifiers to keep community modelling decoupled while still mapping to membership
  backfills via IDs.

## Consumers

| Consumer | Usage |
| --- | --- |
| Laravel feature tests | Feed ranking, paywall gating, event visibility, and API contract assertions. |
| Laravel artisan commands | Backfill dry runs utilise the JSON to populate in-memory sqlite databases. |
| Flutter widget/integration tests | Validate mobile feed rendering, tier badges, and offline caching logic. |
| Load testing harness | k6 scenarios use the manifest to scale arrival rates by post count. |

## Change Management

- Updates require review from QA and mobile leads to ensure cross-platform expectations stay aligned.
- Manifest diffs are reviewed during PRs; unexpected checksum changes trigger pipeline alerts.
- The strategy is versioned; major revisions increment a semantic version stored in the manifest (future enhancement captured in
  the backlog).

## Related Artefacts

- `docs/upgrade/testing/stage12_acceptance_report.md`
- `tools/testing/run_full_test_suite.sh`
- `tools/testing/generate_test_fixtures.py`
- `docs/upgrade/testing/fixtures/fixture_manifest.json`
