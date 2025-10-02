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

The `tools/Start_Up Script` remains the fastest way to configure the Laravel
stack. It now honors Orbas Learn defaults for app naming, metrics, and secret
paths.

```bash
DB_USERNAME=myuser DB_PASSWORD=mypassword LOCAL_DOMAIN=orbas.test \
  ./tools/Start_Up\ Script
```

Key behaviours:

1. Copies `.env.example` → `.env` with Orbas Learn defaults and injects database
   credentials supplied via environment variables.
2. Validates PHP, Composer, Node, npm, and MySQL connectivity before continuing.
3. Creates the target database (defaults to `orbas_learn`) if permissions allow.
4. Installs PHP and Node dependencies, generates an `APP_KEY`, and clears caches.
5. Runs migrations and (unless `SEED_DATABASE=0`) seeds baseline reference data.
6. Builds Vite assets so the dashboard loads without the dev server.
7. Writes an Apache vhost stub to `infra/apache/local_orbas.test.conf` for local
   reverse proxies.

Override behaviour with the following flags:

| Variable | Default | Description |
| --- | --- | --- |
| `SEED_DATABASE` | `1` | Execute `php artisan db:seed --force`. |
| `SKIP_DATABASE` | `0` | Skip DB connectivity, migrations, and seeders. |
| `SKIP_FRONTEND_BUILD` | `0` | Skip `npm run build`. |
| `SKIP_VHOST` | `0` | Prevent Apache stub generation. |
| `APP_PORT` | `8000` | Used for `APP_URL` and post-setup hints. |

If you prefer a lighter bootstrap, run the preflight script directly:

```bash
cp Web_Application/Academy-LMS/.env.example Web_Application/Academy-LMS/.env
SKIP_FRONTEND_BUILD=1 tools/preflight/bootstrap_local_env.sh
```

Update `.env` with correct credentials before running migrations manually.

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

Baseline seeders load:

- Foundational community categories and membership levels.
- Demo users for smoke testing (admin, moderator, member).
- Stripe test products if the `SUBSCRIPTION_*` env variables are present.

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

Run the quality gate locally before opening a PR:

```bash
composer test          # PHPUnit & Pest suites
php artisan test       # Feature tests
npm run lint           # Front-end linting (ESLint/Tailwind)
npm run typecheck      # TypeScript checks
phpstan analyse        # Static analysis (level defined in phpstan.neon.dist)
```

Pending TODOs:

- Configure Playwright for browser end-to-end tests.
- Wire up k6 scripts for feed throughput testing.
- Expand seeders to cover leaderboard fixtures.

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

Recommended packages (already declared in `pubspec.yaml`):

- `flutter_secure_storage` for token storage.
- `dio` + Retrofit for API calls (migrating from `http`).
- `riverpod` and `freezed` for state management (in progress).
- `flutter_stripe` for subscription payments.

### 4.2 Environment Configuration

Environment specific base URLs live in `lib/core/config/environment.dart`. Update
the staging and production endpoints once the Laravel rebrand is deployed. The
mobile client sends the `X-Orbas-Client` header and `OrbasLearn/<version>`
user-agent once the rename patch lands.

### 4.3 Running the App

```bash
flutter run --flavor dev -d <device-id>
```

Flavors defined in `android/app/build.gradle` and `ios/Runner.xcconfig` point to
corresponding API environments. Remember to run `melos bootstrap` once the
workspace migration is complete (tracked in `docs/mobile-roadmap.md`).

---

## 5. Tooling & Runbooks

- **Runbooks** live in `docs/upgrade/runbooks` and now cover the community
  seeding, rollback, and search reindex procedures required for Orbas Learn
  cutovers.
- **Load tests** reside in `tools/perf/k6`. Use the shared `README` for
  invocation examples.
- **Installer interface** launches via `tools/Start_Up Script` and feeds status
  updates through ANSI output for CI/CD compatibility.

---

## 6. Next Steps

- Complete renaming in Flutter (`X-Academy-Client` → `X-Orbas-Client`, app
  strings, icons).
- Replace legacy LMS-only features with community-first services as outlined in
  `docs/orbas-learn-roadmap.md`.
- Implement OpenAPI generation and publish the spec for mobile/web parity.

Please report configuration drift or missing instructions via the issue tracker
so we can keep this runbook authoritative during the rebrand.
