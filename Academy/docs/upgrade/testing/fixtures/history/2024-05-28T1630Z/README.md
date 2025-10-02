# Fixture Refresh – 2024-05-28T16:30Z

- **Generator version:** `tools/testing/generate_test_fixtures.py` (commit recorded in Git history).
- **Source dataset hash:** `7ad988d9bbeb6a7695b281820c8a1086b3a5052083875c60927f8a90575c8ec3`.
- **Outputs:**
  - `community_fixture_set.json`
  - `community_fixture_set.dart`
  - `fixture_manifest.json`
- **Validation:**
  - `./tools/testing/run_full_test_suite.sh` (see logs archived in `tools/testing/logs/20240528T1630Z_*`).
  - Manual review by QA, mobile, and platform leads (see Stage 12 acceptance report).

## Notes

- Engagement scoring validated against PointsService multipliers.
- Paywalled posts reference tiers `[301, 401]` for entitlement regression tests.
- Upcoming events span a three-week window for calendar boundary coverage.
