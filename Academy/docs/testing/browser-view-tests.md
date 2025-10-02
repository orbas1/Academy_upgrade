# Browser View Test Harness

This project already ships with [Laravel Dusk](https://laravel.com/docs/dusk) browser tests under `Web_Application/Academy-LMS/tests/Browser`. The configuration in this repository has been expanded so the full UI flow can be exercised inside local containers (including this Code Execution environment) without any additional setup.

## Prerequisites

* PHP dependencies installed: `composer install`
* Front-end assets built so CSS/JS are available to the browser: `npm install && npm run build`
* Chrome is provided by Laravel Dusk's packaged driver, so no additional system packages are required.

## One-time setup

1. Copy the testing environment file if you need a customised configuration:

   ```bash
   cd Web_Application/Academy-LMS
   cp .env.dusk.local .env.dusk.local.override # optional edits go here
   ```

   > The committed `.env.dusk.local` points the application at an isolated SQLite database (`database/dusk.sqlite`) so browser runs never modify your main data.

2. Ensure the SQLite database file exists (the repository ships with an empty file, but you can recreate it at any time):

   ```bash
   touch database/dusk.sqlite
   ```

## Running the browser suite

From the `Web_Application/Academy-LMS` directory run:

```bash
composer dusk
```

This command boots the Laravel application with the Dusk environment, compiles view templates, and opens a headless Chrome session. Any assertion or CSS issues will fail the run while capturing the following artefacts automatically:

* `tests/Browser/screenshots` – last rendered state of the browser for each failure.
* `tests/Browser/console` – browser console logs to surface JavaScript errors.
* `tests/Browser/source` – the HTML markup as rendered.

## Inspecting failures visually

If a test fails you can open the PNG files in `tests/Browser/screenshots` to inspect how the page rendered, including CSS regressions. Because the tests run in isolation, each scenario starts with a fresh database and deterministic seed state, giving you reproducible UI snapshots.

To iterate on a single test you can use PHPUnit's filtering:

```bash
php artisan dusk --filter=CommunityFlowE2ETest
```

## Continuous integration

The new `composer dusk` script can be plugged into CI pipelines or run locally. Combine it with `php artisan test` for backend coverage to ensure UI regressions are caught before deployment.
