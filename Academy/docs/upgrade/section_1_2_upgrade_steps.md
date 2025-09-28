# Section 1.2 – Laravel 11 Upgrade Execution Guide

## 1. Objectives
Deliver an auditable, script-driven uplift of the Academy Laravel application to Laravel 11 on PHP 8.3. The outcome is a reproducible process that updates Composer dependencies, modernises the bootstrap pipeline, enforces security defaults, and verifies the upgrade through automated testing and static analysis.

## 2. Implementation Overview
| Phase | Owner | Description |
| --- | --- | --- |
| Dependency uplift | Backend | Execute Composer platform pin, Laravel framework upgrade, and supporting packages. |
| Bootstrap alignment | Backend | Adopt the Laravel 11 `bootstrap/app.php` structure, consolidate middleware, and register HTTP kernel changes. |
| Security defaults | Backend | Enforce Argon2id hashing, refresh auth scaffolding, and remove deprecated helpers. |
| Quality gates | QA | Run automated tests, static analysis, and collect artefacts for CAB. |

The execution is automated via `tools/preflight/laravel11_upgrade.sh`, which encapsulates the command sequence and generates artefacts under `storage/upgrade/`.

## 3. Dependency Upgrade Steps
1. **Create feature branch**
   ```bash
   git checkout -b upgrade/laravel11-php83
   ```
2. **Lock Composer platform**
   ```bash
   composer config platform.php 8.3.0
   ```
3. **Require framework & companion packages**
   ```bash
   composer require laravel/framework:^11.0 laravel/tinker --with-all-dependencies
   composer require nunomaduro/larastan:^2.9 phpstan/phpstan:^1.11 --dev
   composer require laravel/scout meilisearch/meilisearch-php --no-update
   composer update
   ```
4. **Audit for removed packages**
   ```bash
   composer show --direct --outdated
   composer remove swiftmailer/swiftmailer
   ```
5. **Node & Vite alignment**
   ```bash
   npm install
   npm audit fix
   ```

All commands are emitted by the automation script so that logs are captured for CAB evidence.

## 4. Bootstrap & Kernel Refactor
1. Replace legacy `bootstrap/app.php` with Laravel 11 template using `Illuminate\Foundation\Application::configure` pattern.
2. Move middleware bindings from `app/Http/Kernel.php` into the new bootstrap pipeline closures to reduce per-request overhead.
3. Adopt `config/app.php` service provider registration updates, including HTTP client and event aliases.
4. Validate `RouteServiceProvider` namespace removal and ensure route files use closure-based controllers or fully-qualified classes.
5. Execute `php artisan config:cache` and `php artisan route:cache` locally to confirm bootstrap changes compile.

## 5. Security Defaults Enforcement
1. Switch password hashing default to Argon2id in `config/hashing.php`.
2. Regenerate authentication scaffolding (if Breeze/Jetstream) to remove deprecated helpers.
3. Verify `APP_FEATURE_FLAGS` includes `"webauthn": false` for feature flag gating.
4. Confirm `config/session.php` uses `secure` and `same_site` defaults compatible with Section 2 requirements.

## 6. Automated Quality Gates
| Tool | Command | Purpose |
| --- | --- | --- |
| PHPUnit | `php artisan test --parallel` | Regression test suite on upgraded framework. |
| PHPStan | `vendor/bin/phpstan analyse --memory-limit=1G` | Static analysis for type and API drift. |
| Larastan | `vendor/bin/larastan analyse --level=6 app database routes` | Laravel-specific static analysis. |
| Pint | `./vendor/bin/pint` | Code style pass for new framework conventions. |

Test results are exported to `storage/upgrade/reports/` by the automation script for inclusion in change management artefacts.

## 7. Artefact Collection
After successful execution the script writes:
- `storage/upgrade/laravel11-composer.log` – Composer output.
- `storage/upgrade/tests-parallel.log` – PHPUnit parallel run output.
- `storage/upgrade/phpstan.log` – PHPStan analysis results.
- `storage/upgrade/larastan.log` – Larastan analysis results.
- `storage/upgrade/summary.json` – Machine-readable summary consumed by CI for gating.

## 8. Validation Checklist
- [ ] Application boots locally with `php artisan serve`.
- [ ] Queue workers (`php artisan queue:work`) start without deprecated binding warnings.
- [ ] Horizon dashboard loads and reports connection success.
- [ ] Vite build (`npm run build`) completes under Node 20.x.
- [ ] API smoke test (login, course listing, community placeholder endpoints) passes.

## 9. Rollback Notes
If any gating step fails, invoke `tools/preflight/laravel11_upgrade.sh --rollback` to restore the pre-upgrade `composer.lock` and Git working tree snapshot created at the start of execution.

## 10. Sign-off
Upon validation the engineering lead updates `AGENTS.md` Section 1.2 tracking to 100% across Functionality, Integration, UI:UX, and Security, attaches artefacts to the CAB ticket, and proceeds to Section 1.3 activities.
