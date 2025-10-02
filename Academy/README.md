# Orbas Learn Platform Monorepo

This repository now tracks the in-progress migration from the legacy **Academy**
branding to **Orbas Learn**. The codebase hosts three primary deliverables:

1. **Orbas Learn API & Web App** – Laravel 11 application located in
   `Web_Application/Academy-LMS`.
2. **Orbas Learn Student Mobile App** – Flutter 3 application living in
   `Student Mobile APP/academy_lms_app`.
3. **Automation & Tooling** – installer scripts, infrastructure templates, and
   provisioning helpers under `tools/` and `infra/`.

This guide pulls the scattered setup notes into a single runbook so the team can
spin up every surface—API, web, and mobile—under the new Orbas Learn identity.

> **Heads-up**: The rebrand is still underway. Some filenames retain `Academy`
> prefixes for now, but all environment variables, app titles, and client
> headers should reflect **Orbas Learn** going forward.

For an honest look at remaining scope, consult the
[`docs/upgrade/progress.md`](docs/upgrade/progress.md) tracker. It lists the
major service, UI, data, and DevOps milestones that are still outstanding before
we can enter broad beta testing.

To understand how each deliverable is graded, review the "Quality Grading Model"
in [`AGENTS.md`](AGENTS.md). Every roadmap task is assigned equal weight, and
the required quality checkers (code quality, security, functionality, platform
surfaces, and design) must be documented before we move to the next milestone.

Section 7 of `AGENTS.md` also captures the **current baseline grades** for each
task (updated 2025-02-19). Treat those values as the ground truth for what's
implemented versus still outstanding.

> **Status (February 2025):** Only ~20% of the community upgrade is represented
> in the repository. Foundational contracts and documentation exist, but the
> production experience, installation automation, payments, mobile parity, and
> DevOps tooling are still incomplete. Prioritise work according to the
> "Roadmap from 20% → 100%" section in `AGENTS.md`.

---

## 1. Prerequisites

Install the following tooling locally. Versions listed are the minimum we have
validated during smoke testing.

| Tool | Version | Purpose |
| --- | --- | --- |
| PHP | 8.2+ | Laravel runtime, queues, scheduler |
| Composer | 2.6+ | PHP dependency manager |
| Node.js | 20.x LTS | Vite asset pipeline |
| npm | 10+ | Front-end package manager |
| MySQL | 8.x | Primary datastore |
| Redis | 7.x | Cache, queues, websockets |
| Git | latest | Version control |
| Flutter SDK | 3.19+ | Mobile client builds |
| Dart | 3.2+ | Flutter dependency |
| Android Studio / Xcode | latest | Native mobile builds |

Optional but recommended:

- Docker (24+) if you prefer containerized services.
- k6 (0.47+) for load testing scripts located in `tools/perf`.
- Node 20 LTS ensures compatibility with our Vite config.

---

## 2. Repository Bootstrap

Clone the monorepo and install git submodules if any get added later.

```bash
git clone git@github.com:your-org/orbas-learn.git
cd orbas-learn/Academy
```

`tools/Start_Up Script` now provisions `.env`, installs Composer/npm
dependencies, runs migrations/seeders, builds Vite assets, and generates an
Apache vhost stub without interactive prompts. Milestone A still tracks the
remaining work: removing CodeCanyon licence checks, adding Nginx parity,
provisioning queue/scheduler services, and publishing checksum validation.

If you prefer manual control (or are validating individual steps), run:

```bash
cp Web_Application/Academy-LMS/.env.example Web_Application/Academy-LMS/.env
php Web_Application/Academy-LMS/artisan key:generate
composer install
npm install
```

Then update `.env` with correct credentials before running migrations manually.

---

## 3. Laravel Web/API Application

Navigate to `Web_Application/Academy-LMS` for all PHP-related development.

### 3.1 Environment Configuration

The updated `.env.example` ships with Orbas Learn friendly defaults:

```
APP_NAME="Orbas Learn"
APP_URL=http://localhost
OBS_METRICS_PREFIX=orbas_learn
SECRETS_MANAGER_PATH=orbas-learn/${APP_ENV}/laravel
```

Replace database credentials, mail drivers, broadcast drivers, and third-party
API keys as needed. When editing configuration, clear caches so the app sees the
changes:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

### 3.2 Database

Run migrations and seeders after the `.env` is prepared:

```bash
php artisan migrate
php artisan db:seed
```

Current seeders only cover default community categories, levels, and points
rules. Demo users, subscription fixtures, geo metadata, and analytics scaffolds
still need to be authored.

### 3.3 Local Services

Start the web server, queue worker, and scheduler in separate terminals:

```bash
php artisan serve --host=0.0.0.0 --port=${APP_PORT:-8000}
php artisan queue:work
php artisan schedule:work
npm run dev
```

Access the application via `http://127.0.0.1:${APP_PORT:-8000}`. Queue workers
and the scheduler are required for notifications, scheduled posts, and
subscription webhooks.

### 3.4 Testing & Quality Gates

Run the available checks locally before opening a PR:

```bash
php artisan test
composer phpstan
composer security-scan
npm run build
php artisan communities:quality-gate --json
```

Pending TODOs:

- Add first-class `composer test` and JavaScript lint/typecheck scripts.
- Configure Playwright for browser end-to-end tests.
- Wire up k6 scripts for feed throughput testing.
- Expand seeders to cover leaderboard fixtures and subscription tiers.
- Automate the quality gate in CI (see `docs/upgrade/runbooks/quality-gate.md`).

### 3.5 Community Service Contracts

Concrete community services now live under `app/Domain/Communities/Services`
with feature tests covering feed pagination, paywall gating, points, and
subscriptions. Milestones B and C focus on tightening coverage (geo, classroom
sync, realtime) and wiring any remaining adapters into controllers/jobs.

---

## 4. Flutter Mobile App

The mobile client still resides at
`Student Mobile APP/academy_lms_app` while we work through renaming. Update your
IDE run configuration to reference **Orbas Learn** in titles and package IDs.

### 4.1 Dependency Setup

```bash
cd "Student Mobile APP/academy_lms_app"
flutter pub get
flutter pub run build_runner build --delete-conflicting-outputs
```

Recommended backlog packages (not yet declared) once we start the mobile parity
work:

- `dio` + Retrofit for API calls (replace the current `http` usage).
- `riverpod`/`hooks_riverpod` and `freezed` for state management + models.
- `firebase_messaging` for push notifications.
- `flutter_stripe` for subscription payments.

### 4.2 Environment Configuration

Environment specific base URLs live in `lib/core/config/environment.dart`.
Update the staging and production endpoints once the Laravel rebrand is
deployed. The mobile client still uses the legacy user-agent/header until the
renaming work lands.

### 4.3 Running the App

```bash
flutter run --flavor dev -d <device-id>
```

Flavors defined in `android/app/build.gradle` and `ios/Runner.xcconfig` point to
corresponding API environments.

---

## 5. Tooling & Runbooks

- **Runbooks** live in `docs/upgrade/runbooks` and now cover the community
  seeding, rollback, and search reindex procedures required for Orbas Learn
  cutovers.
- **Load tests** reside in `tools/perf/k6`. Use the shared `README` for
  invocation examples.
- **Installer interface** is being rebuilt. `tools/Start_Up Script` handles
  non-interactive provisioning today, but Milestone A must finish licence
  removal, Nginx parity, queue/scheduler automation, and checksum validation.

---

## 6. Next Steps

The actionable backlog lives in `AGENTS.md` under **Roadmap from 20% → 100%
Completion**. Focus on one milestone at a time:

1. **Milestone A** – Remove CodeCanyon licence checks, finish the one-command
   installer, and align documentation across the repo.
2. **Milestone B** – Ship the communities data model, factories, seeders, and
   audit/rollback automation.
3. **Milestone C** – Implement backend services, APIs, search, and monitoring.
4. **Milestone D** – Deliver the web experience, moderation/admin control
   centre, and notification UX.
5. **Milestone E** – Bring the Flutter app, messaging pipeline, payments, and
   DevOps platform to production readiness.

Update this README as milestones close so new contributors land on an accurate
snapshot of the platform.
