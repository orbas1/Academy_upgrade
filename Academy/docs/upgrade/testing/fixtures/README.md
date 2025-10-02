# Fixture Catalogue

This catalogue documents the canonical community fixtures used throughout the Stage 12 testing strategy.

## Current Snapshot – 2024-05-28

| Artifact | Purpose | Notes |
| --- | --- | --- |
| `community_base_dataset.json` | Authoritative source dataset used for deterministic fixture generation. | Includes two production-grade community archetypes with tiers, events, and engagement data. |
| `community_fixture_set.json` | Laravel test payload generated via `tools/testing/generate_test_fixtures.py`. | Validated by `CommunityFixtureIntegrityTest` and consumed by PHPUnit + Artisan harnesses. |
| `community_fixture_set.dart` | Flutter test fixtures mirroring the Laravel payload. | Ensures mobile widget/integration tests share identical data semantics. |
| `fixture_manifest.json` | Checksum + record-count manifest for CI enforcement. | Used by acceptance report and pipeline guardrails to detect drift. |

### Changes in this Release

- Added deterministic leaderboard, timezone, and engagement scoring enrichments.
- Introduced cross-platform fixture generation and manifest hashing.
- Registered fixture integrity test in Laravel suite to fail fast on schema drift.

### Refresh Workflow

1. Modify `community_base_dataset.json` with new personas or behaviour.
2. Run `python tools/testing/generate_test_fixtures.py` to regenerate artefacts.
3. Review diffs + manifest, then commit with acceptance report updates.
4. Execute `./tools/testing/run_full_test_suite.sh` and archive logs under `fixtures/history/`.

## History

- `history/2024-05-28T1630Z/` – First enterprise-ready fixture refresh with manifest + cross-platform outputs.

## Contacts

- QA Lead: Morgan Lee (qa-lead@academy.example)
- Mobile Lead: Priya Natarajan (mobile@academy.example)
- Platform Lead: Alicia Patel (platform@academy.example)
