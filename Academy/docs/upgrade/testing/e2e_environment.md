# Stage 12.3 – E2E Environment Preparation

Stage 12.3 formalises the browser and mobile end-to-end harness for the community rollout. The steps below prepare a clean dataset, enable required feature flags, and expose the smoke scenario harness that powers Laravel Dusk and Flutter integration coverage.

## Provisioning checklist

1. **Reset database (optional but recommended before the first run)**

   ```bash
   php artisan community:e2e:setup --fresh
   ```

   * Runs `migrate:fresh` with `--force` to guarantee a clean SQLite schema for the testing environment.
   * Seeds baseline community configuration (`community:seed-baseline --force`).
   * Enables the `community_profile_activity` feature flag at 100% rollout for the `internal,e2e` segment.
   * Executes the `CommunityEndToEndHarness`, storing a JSON report at `storage/app/testing/community_flow_report.json`.

2. **Subsequent refreshes**

   When the schema is already migrated, skip the destructive reset:

   ```bash
   php artisan community:e2e:setup
   ```

   Use `--skip-run` to prepare the environment without executing the harness (useful when Dusk will exercise the flow itself), or `--skip-feature` if the flag is already managed in a different environment.

3. **Environment variables**

   * `.env.dusk.ci` uses SQLite at `/tmp/academy_dusk.sqlite` and sets `APP_URL=http://127.0.0.1:8000`.
   * Dusk expects `php artisan serve --env=dusk.ci` or the test runner to boot the HTTP server on `127.0.0.1:8000`.

4. **Feature artifacts**

   * The setup command writes an audited report containing community membership, subscription state, leaderboard output, and notification payloads to `storage/app/testing/community_flow_report.json`.
   * The browser harness is served from `/testing/community-flow` when `APP_ENV=testing` (automatically true for Dusk).

## Verification commands

* **Browser flow** – executes Chromium-driven scenario assertions.

  ```bash
  php artisan dusk --env=dusk.ci --filter=CommunityFlowE2ETest
  ```

* **Laravel CLI audit** – rerun the harness and review the persisted report.

  ```bash
  php artisan community:e2e:setup --skip-feature --report=testing/manual_report.json --skip-seed
  cat storage/app/testing/manual_report.json
  ```

* **Flutter integration** – validates the account screen contribution card using the in-memory API service.

  ```bash
  flutter test integration_test/profile_activity_flow_test.dart
  ```

  > _Note:_ the repository does not ship with the Flutter SDK; run the command from a workstation with Flutter 3.19+ installed.

## Outputs captured

* `storage/app/testing/community_flow_report.json` – JSON payload from the harness.
* `tests/Browser/CommunityFlowE2ETest.php` – Laravel Dusk coverage for the join → subscribe → compose → react pipeline.
* `integration_test/profile_activity_flow_test.dart` – mobile integration smoke path exercising profile activity pagination and refresh UX.
