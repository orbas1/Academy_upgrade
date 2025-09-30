# Academy LMS – Local Testing Quickstart

This Laravel application powers the Academy web experience. Use the automation shipped in `/Academy/tools` to bring up a fully configured local environment in minutes, or follow the manual steps below if you prefer to control each stage yourself.

> **Need the full mono-repo walkthrough?** See the [Local Development Handbook](../../docs/local-development.md) for Flutter/mobile setup, optional Docker services, and hardening tips.

## Prerequisites

Install the following tooling before running either installer:

| Tool | Version | Notes |
| --- | --- | --- |
| PHP | 8.2+ | Enable `intl`, `mbstring`, `openssl`, `pdo_mysql`, `redis`, `bcmath`, `curl`, `fileinfo`, `zip` |
| Composer | 2.6+ | Required for Laravel dependencies |
| Node.js | 20.x LTS | npm 10+ recommended |
| npm | 10+ | Used for Vite/asset builds |
| MySQL Client & Server | 8.x | Script will create the `academy` database if credentials allow |
| Redis | 7.x | Optional but required for queue/broadcast testing |
| Apache (optional) | 2.4+ | Needed if you want to use the generated virtual host |
| Git | latest | Clone the repository |

## 1. One-Command Setup (Start_Up Script)

The `Start_Up Script` is a comprehensive bootstrap that installs dependencies, provisions the database, writes `.env`, and drops an Apache virtual host stub for local testing.

```bash
cd Academy_upgrade/Academy
DB_USERNAME=myuser DB_PASSWORD=secret LOCAL_DOMAIN=academy.test ./tools/Start_Up\ Script
```

What the script now covers:

1. Copies `.env.example` to `.env` (with a `.env.startup.bak` backup when the file already exists) and injects the database and `APP_URL` values provided via environment variables (defaults to `academy`/`root`).
2. Verifies PHP, Composer, Node.js, npm, and the MySQL CLI are installed before continuing. PHP versions older than 8.2 trigger a warning.
3. Performs a live MySQL connectivity test and creates the database if it does not already exist (can be skipped with `SKIP_DATABASE=1`).
4. Runs `composer install`, `npm ci`/`npm install`, and generates the `APP_KEY` if needed.
5. Executes Laravel migrations, optionally seeds demo content (`SEED_DATABASE=0` to skip), links `storage`, clears caches, and rebuilds configuration/route caches.
6. Builds the Vite assets for production parity (`SKIP_FRONTEND_BUILD=1` to bypass when iterating quickly).
7. Writes an Apache virtual host stub to `infra/apache/local_<domain>.conf` (`SKIP_VHOST=1` disables this) and reminds you to add the host entry.

Additional runtime toggles are available via environment variables:

| Variable | Default | Effect |
| --- | --- | --- |
| `SEED_DATABASE` | `1` | Run `php artisan db:seed --force` when migrations succeed |
| `SKIP_DATABASE` | `0` | Skip all database connectivity, creation, migrations, and seeders |
| `SKIP_FRONTEND_BUILD` | `0` | Skip `npm run build` (useful when you plan to run `npm run dev`) |
| `SKIP_VHOST` | `0` | Prevent creation/overwrite of the Apache stub |
| `APP_PORT` | `8000` | Included in the generated `APP_URL` and serve command hint |

After the script completes, start the development server:

```bash
cd Web_Application/Academy-LMS
APP_PORT=8000 php artisan serve --host=0.0.0.0 --port=8000
npm run dev
```

## 2. Manual Setup (Bootstrap Installer)

If you need a lighter-weight reinstall, use the curated installer in `tools/preflight/bootstrap_local_env.sh`.

```bash
cd Academy_upgrade/Academy/Web_Application/Academy-LMS
cp .env.example .env        # only if you have not created one yet
SKIP_FRONTEND_BUILD=1 ../../tools/preflight/bootstrap_local_env.sh
php artisan migrate --seed  # run manually after configuring DB creds
```

The preflight script installs Composer/npm dependencies, ensures an `APP_KEY` exists, links storage, clears caches, and (unless you set `SKIP_FRONTEND_BUILD=1`) builds the front-end assets. It now captures a `.env.bootstrap.bak` snapshot for reference. Update your `.env` before running migrations so the database connection succeeds.

## 3. Database & Credentials

The Laravel app defaults to the following connection settings:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=academy
DB_USERNAME=root
DB_PASSWORD=
```

Override these values either by exporting environment variables when running `Start_Up Script` or by editing `.env` manually. The automated script uses the MySQL CLI to create the database with `utf8mb4_unicode_ci` collation when possible. If you cannot grant create permissions, create the database manually before running migrations.

## 4. Local Virtual Host (Apache)

The Start_Up Script writes an Apache stub at `infra/apache/local_<your-domain>.conf` (unless `SKIP_VHOST=1`). To use it:

1. Copy or symlink the file into your Apache `sites-available` directory (e.g. `/etc/apache2/sites-available/academy.test.conf`).
2. Update the PHP handler inside the file to match your local stack (mod_php or PHP-FPM socket/port).
3. Enable the site (`sudo a2ensite academy.test.conf`) and reload Apache (`sudo systemctl reload apache2`).
4. Add the host entry to `/etc/hosts` (or the Windows equivalent): `127.0.0.1 academy.test`.

The DocumentRoot already points at `Web_Application/Academy-LMS/public` and includes the rewrite rules Laravel needs.

## 5. Environment Variables (.env)

Key values to verify after bootstrapping:

- `APP_URL` – set to `http://academy.test:8000` (or your chosen host/port)
- `APP_ENV` / `APP_DEBUG` – defaults are fine for local development
- `DB_*` – database connection credentials
- `BROADCAST_DRIVER`, `CACHE_DRIVER`, `QUEUE_CONNECTION`, `SESSION_DRIVER` – default to file/database drivers for local testing
- `MAIL_MAILER`, `PUSHER_*` – configure if testing notifications or real-time features

Regenerate caches whenever you tweak configuration:

```bash
php artisan optimize:clear
php artisan config:cache
```

## 6. Running the Stack

- `php artisan serve --host=0.0.0.0 --port=8000` – Laravel HTTP server
- `npm run dev` – Vite dev server with hot module reloading
- `php artisan queue:work` – process queued jobs if testing notifications
- `php artisan schedule:work` – run scheduled tasks continuously

## 7. Useful Commands

| Command | Description |
| --- | --- |
| `php artisan test` | Run the Laravel test suite |
| `npm run build` | Compile production assets |
| `php artisan migrate:fresh --seed` | Rebuild schema with demo data |
| `composer outdated` / `npm outdated` | Audit dependency versions |
| `php artisan optimize:clear` | Reset caches after editing config/routes |

## 8. Troubleshooting

| Symptom | Fix |
| --- | --- |
| `Missing required tooling` error from installer | Install PHP, Composer, npm, and the MySQL CLI, then rerun the script |
| `SQLSTATE[HY000]` during migrations | Confirm `.env` credentials and that the `academy` database exists |
| `APP_KEY` missing exception | Run `php artisan key:generate --ansi` |
| Vite cannot connect | Ensure `APP_URL` matches the host and run `npm run dev -- --host` |
| Apache 403 or 500 locally | Verify the generated virtual host is enabled and points to the `public` directory |

Stay aligned with the broader mono-repo instructions via the [Local Development Handbook](../../docs/local-development.md).
