# Symfony Runtime Environment (Docker) Setup

The repository ships with a Symfony CLI powered runtime for the Laravel web
application. The new automation scripts streamline standing up the container
stack, provisioning MariaDB, and capturing screenshots for documentation.

## Prerequisites

* Docker Engine 24+ with `docker compose` plugin (or Docker Compose v2). If
  Docker isn't available, run `sudo ./tools/install_container_runtime.sh` to
  install a Podman-based runtime that is compatible with the helper scripts.
* Node.js 18+ and npm (required for automated screenshots)
* cURL (used by the helper scripts for health checks)

## 1. Configure Environment Variables

The Symfony runtime consumes `.env.docker`. The bootstrapper will copy the
example file automatically:

```bash
cp Web_Application/Academy-LMS/.env.docker.example Web_Application/Academy-LMS/.env.docker
```

You may customise database credentials, caching drivers, or application URLs in
that file. The container entrypoint now supports the following variables:

| Variable | Purpose |
| --- | --- |
| `DB_CREATE_DATABASE` | Automatically creates the schema using the privileged credentials provided via `DB_ADMIN_USERNAME` / `DB_ADMIN_PASSWORD`. |
| `RUN_DB_SEED` | Runs `php artisan db:seed` after migrations when set to `1`. |
| `WARM_CACHES` | Controls the execution of cache warm-up commands after migrations. |

## 2. Start the Runtime

Use the orchestration script to launch the stack, wait for readiness, and run
migrations:

```bash
./tools/setup_symfony_environment.sh
```

Environment variables can tweak the behaviour:

```bash
RUN_SEED=1 WAIT_TIMEOUT=180 ./tools/setup_symfony_environment.sh --recreate
```

Useful flags:

* `--down` – shuts the stack down
* `--logs` – tails the application logs after boot
* `--no-build` – skips Docker image rebuilds for faster iteration

## 3. Database Lifecycle

The container entrypoint now waits for MariaDB, ensures the requested database
exists, applies migrations, and optionally seeds data. The database credentials
are injected through `docker-compose.symfony.yml`, using a dedicated admin user
for schema creation (`root` by default).

## 4. Capture Screenshots

The `tools/screenshots/capture_symfony.sh` helper spins up Chromium via
Playwright, visits the running application, and stores the result under
`docs/screenshots/` with a timestamped filename. Example usage:

```bash
./tools/screenshots/capture_symfony.sh http://localhost:8000/dashboard
```

The command automatically ensures the Docker stack is running. Review the
resulting PNG and commit it to document the state of the application.

## 5. Tear Down

To stop the containers and remove volumes:

```bash
./tools/setup_symfony_environment.sh --down
```

For a full reset (including deleting database data) run `docker volume rm
academy_mysql_data` afterwards.

## Troubleshooting

If the helper scripts exit with `Missing required dependency: docker`, install
[Docker Desktop](https://docs.docker.com/desktop/) (macOS/Windows) or Docker
Engine (Linux) and ensure the `docker` binary is available on your shell `PATH`.
In sandboxed or CI environments where Docker cannot run, execute
`sudo ./tools/install_container_runtime.sh` to provision a Podman-based
replacement. Re-run `./tools/setup_symfony_environment.sh` afterwards.

---

With these scripts, developers can bootstrap, verify, and document the Symfony
runtime quickly, keeping local environments consistent and shareable.
