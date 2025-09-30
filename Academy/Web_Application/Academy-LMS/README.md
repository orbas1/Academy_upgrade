# Academy LMS – Local Testing Quickstart

This Laravel application powers the Academy web experience. The repository now includes a set of helper scripts and documentation so you can pull the project, install dependencies, and verify the build on your machine in a few minutes.

> **Tip:** If you need a deeper walkthrough covering the mobile apps and extended services, read the [Local Development Handbook](../../docs/local-development.md).

## Prerequisites

Install the following tooling before running the bootstrap script:

- PHP 8.2 or newer with the `intl`, `mbstring`, `openssl`, `pdo_mysql`, and `zip` extensions
- [Composer](https://getcomposer.org/) 2.6+
- Node.js 20 LTS with npm 10+
- MySQL 8.x (or MariaDB 10.5+) and Redis 7.x for queue/cache testing
- Git, and optionally pnpm if you prefer an alternative package manager

## One-Time Environment Setup

1. Clone the repository and switch into the Laravel application directory:

   ```bash
   git clone https://github.com/<your-org>/Academy_upgrade.git
   cd Academy_upgrade/Academy/Web_Application/Academy-LMS
   ```

2. Copy the example environment file and adjust the database credentials:

   ```bash
   cp .env.example .env
   # edit .env to match your local DB connection
   ```

3. Run the bootstrap helper to install Composer & npm dependencies, generate an application key, clear caches, and build the front-end assets:

   ```bash
   ../../tools/preflight/bootstrap_local_env.sh
   ```

4. Run the database migrations and (optionally) seed demo content:

   ```bash
   php artisan migrate --seed
   ```

5. Boot the development server and Vite watcher:

   ```bash
   php artisan serve
   npm run dev
   ```

At this point you can sign in with any seeded account or create a fresh one.

## Useful Commands

| Command | Description |
| --- | --- |
| `php artisan test` | Run the Laravel test suite |
| `php artisan optimize:clear` | Reset caches when changing config or routes |
| `npm run build` | Compile a production build of the front-end assets |
| `php artisan queue:work` | Start the queue worker for background jobs |

## Troubleshooting

- **Composer/NPM missing:** The bootstrap script will skip steps if tooling is not installed. Install the prerequisites and re-run the script.
- **APP_KEY missing:** Delete the `APP_KEY` line in `.env` and run `php artisan key:generate`.
- **Database errors:** Confirm the `.env` database credentials and that MySQL is running. Run `php artisan migrate:fresh --seed` to rebuild the schema.

## Next Steps

- Review the [Local Development Handbook](../../docs/local-development.md) for Flutter/mobile setup, optional Docker recipes, and environment hardening tips.
- Configure queues, mail, and third-party integrations (`MAIL_MAILER`, `PUSHER_*`) before testing real-time features.
- Keep dependencies healthy by running `composer outdated`, `npm audit`, and the security scripts inside `Academy/tools/security/`.
