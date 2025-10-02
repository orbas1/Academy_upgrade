# Local Development Handbook

The Academy repository contains multiple applications (Laravel web, Flutter mobile, background workers). This guide consolidates the commands you need to get everything running locally so you can test changes without hunting through separate READMEs.

## Directory Layout

- `Academy/Web_Application/Academy-LMS` – Laravel web application (primary focus of this upgrade)
- `Academy/Student Mobile APP/academy_lms_app` – Flutter mobile application for students
- `Academy/tools` – Helper scripts for security, deployment, and preflight automation

## Global Requirements

Install the following before you begin:

| Tool | Version | Notes |
| --- | --- | --- |
| PHP | 8.2+ | Enable `intl`, `mbstring`, `openssl`, `pdo_mysql`, `redis`, `bcmath`, `curl`, `fileinfo`, `gd`, `zip` |
| Composer | 2.6+ | Used for Laravel dependencies |
| Node.js | 20.x LTS | npm 10+ recommended |
| MySQL | 8.x | Create a database named `academy` (or update `.env`) |
| Redis | 7.x | Optional but required for queues and broadcasting |
| Dart/Flutter | Dart 3.2+, Flutter 3.16+ | Install via `flutter doctor` |

## 1. Laravel Web App Setup

```bash
cd Academy/Web_Application/Academy-LMS
cp .env.example .env
../../tools/preflight/bootstrap_local_env.sh
php artisan migrate --seed
php artisan serve        # http://127.0.0.1:8000
npm run dev              # asset watcher
```

### Common Maintenance

- `php artisan test` – run automated tests
- `php artisan migrate:fresh --seed` – rebuild schema with demo data
- `php artisan queue:work` – process queued jobs (emails, notifications)
- `php artisan schedule:work` – run scheduled tasks continuously

## 2. Flutter Mobile App Setup

```bash
cd Academy/Student\ Mobile\ APP/academy_lms_app
flutter pub get
flutter pub run build_runner build --delete-conflicting-outputs
flutter run
```

Configure the API base URL in `lib/core/constants/api_constants.dart` to point at your local Laravel server (e.g. `http://10.0.2.2:8000/api` for Android emulators).

Run the analyzer and tests to verify dependencies:

```bash
flutter analyze
flutter test
```

## 3. Background Services

The repository assumes MySQL and Redis are available locally. You can run them via Docker Compose if you prefer:

```bash
docker compose -f infra/docker/docker-compose.local.yml up -d mysql redis meilisearch
```

Update `.env` to match the container ports if you use Docker.

Need a fully containerised PHP stack instead? A Symfony CLI powered image (with MariaDB) now lives at the repo root:

```bash
docker compose -f docker-compose.symfony.yml up --build
```

The PHP container provisions `.env`, installs Composer dependencies, runs migrations, and serves the app on port `8000` so you can immediately test Blade views.

## 4. Keeping Dependencies Fresh

- Run `composer outdated` and `npm outdated` monthly to track upgrades.
- Apply security fixes using the helper scripts in `Academy/tools/security/` (e.g. `php audit`, `npm audit`).
- For Flutter, keep packages updated with `flutter pub upgrade --major-versions`.

## 5. Troubleshooting Checklist

| Symptom | Fix |
| --- | --- |
| `Class 'PDO' not found` | Install/enable the PHP `pdo_mysql` extension |
| `APP_KEY` missing exception | Run `php artisan key:generate` |
| Vite cannot connect | Ensure `APP_URL` matches the host and run `npm run dev -- --host` |
| Flutter cannot reach API | Use `10.0.2.2` for Android emulator or update iOS simulator networking |
| Queue jobs stuck | Run `php artisan queue:flush` and restart the worker |

## 6. Verification Before Committing

1. `php artisan test`
2. `npm run build`
3. `flutter test`
4. `flutter analyze`

Document any failing command in your pull request to keep the team aware of environment issues.
