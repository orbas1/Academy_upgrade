#!/usr/bin/env bash
set -euo pipefail

# Bootstrapper for the Symfony-flavoured Docker environment that ships with the
# Academy project. The script ensures Docker is available, launches the stack,
# waits for the Laravel application to become reachable through the Symfony CLI
# server, and offers helpers for database migrations, seeding, and health checks.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
COMPOSE_FILE="${REPO_ROOT}/docker-compose.symfony.yml"
ENV_FILE="${REPO_ROOT}/Web_Application/Academy-LMS/.env.docker"
APP_SERVICE="app"
APP_URL="${APP_URL:-http://localhost:8000}"
WAIT_TIMEOUT="${WAIT_TIMEOUT:-120}"
RUN_SEED="${RUN_SEED:-0}"

usage() {
  cat <<USAGE
setup_symfony_environment.sh – Orchestrates the Docker Symfony runtime

Optional environment variables:
  APP_URL            Base URL that should respond once the stack is healthy.
                     Defaults to http://localhost:8000.
  WAIT_TIMEOUT       Seconds to wait for the application health check (default 120).
  RUN_SEED           Set to 1 to trigger database seeding after migrations (default 0).

Flags:
  --down             Stop the stack instead of bringing it up.
  --recreate         Pass --force-recreate to docker compose up.
  --no-build         Pass --no-build to docker compose up.
  --logs             Tail application logs after start-up.
  -h|--help          Show this message.
USAGE
}

ensure_command() {
  if ! command -v "$1" >/dev/null 2>&1; then
    printf '\n\033[1;31m[setup][error]\033[0m Missing required dependency: %s\n' "$1" >&2
    if [ "$1" = "docker" ]; then
      printf '\033[1;33m[setup][hint]\033[0m Install Docker Desktop/Engine and ensure "docker" is on your PATH, or run tools/install_container_runtime.sh for a Podman-based setup.\n' >&2
      printf '\033[1;33m[setup][hint]\033[0m See docs/local-development-symfony.md#prerequisites for setup guidance.\n' >&2
    fi
    exit 1
  fi
}

ensure_command curl

select_compose_command() {
  if command -v docker >/dev/null 2>&1; then
    if docker compose version >/dev/null 2>&1; then
      DOCKER_COMPOSE=(docker compose)
      COMPOSE_FLAVOUR="docker compose"
      return
    fi
    if command -v docker-compose >/dev/null 2>&1; then
      if docker-compose version >/dev/null 2>&1; then
        DOCKER_COMPOSE=(docker-compose)
        COMPOSE_FLAVOUR="docker-compose"
        return
      else
        printf '\033[1;33m[setup][warn]\033[0m docker-compose detected but failed to execute; trying alternate frontends.\n'
      fi
    fi
  fi

  if command -v podman-compose >/dev/null 2>&1; then
    DOCKER_COMPOSE=(podman-compose)
    COMPOSE_FLAVOUR="podman-compose"
    return
  fi

  printf '\n\033[1;31m[setup][error]\033[0m No compatible compose frontend found.\n' >&2
  printf 'Install Docker or run tools/install_container_runtime.sh to provision Podman.\n' >&2
  exit 1
}

select_compose_command

if [ ! -f "$COMPOSE_FILE" ]; then
  printf '\n\033[1;31m[setup][error]\033[0m docker-compose.symfony.yml not found at %s\n' "$COMPOSE_FILE" >&2
  exit 1
fi

ACTION="up"
COMPOSE_ARGS=("--detach")
TAIL_LOGS=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --down)
      ACTION="down"
      shift
      ;;
    --recreate)
      COMPOSE_ARGS+=("--force-recreate")
      shift
      ;;
    --no-build)
      COMPOSE_ARGS+=("--no-build")
      shift
      ;;
    --logs)
      TAIL_LOGS=1
      shift
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      printf '\n\033[1;31m[setup][error]\033[0m Unknown argument: %s\n' "$1" >&2
      usage
      exit 1
      ;;
  esac
  done

printf '\n\033[1;34m[setup]\033[0m Using compose file %s\n' "$COMPOSE_FILE"
printf '\033[1;34m[setup]\033[0m Compose frontend: %s\n' "$COMPOSE_FLAVOUR"

if [ ! -f "$ENV_FILE" ] && [ -f "${ENV_FILE}.example" ]; then
  cp "${ENV_FILE}.example" "$ENV_FILE"
  printf '\033[1;34m[setup]\033[0m Created %s from example template.\n' "$ENV_FILE"
fi

if [ "$ACTION" = "down" ]; then
  "${DOCKER_COMPOSE[@]}" -f "$COMPOSE_FILE" down
  exit 0
fi

printf '\033[1;34m[setup]\033[0m Starting Symfony environment (this may take a minute)\n'
"${DOCKER_COMPOSE[@]}" -f "$COMPOSE_FILE" up "${COMPOSE_ARGS[@]}" --build

printf '\033[1;34m[setup]\033[0m Waiting for application health check at %s (timeout: %ss)\n' "$APP_URL" "$WAIT_TIMEOUT"
START="$(date +%s)"
while true; do
  if curl --fail --silent --max-time 5 "$APP_URL" >/dev/null 2>&1; then
    printf '\033[1;32m[setup]\033[0m Application responded successfully.\n'
    break
  fi
  NOW="$(date +%s)"
  if (( NOW - START > WAIT_TIMEOUT )); then
    printf '\n\033[1;31m[setup][error]\033[0m Application did not become ready within %s seconds.\n' "$WAIT_TIMEOUT" >&2
    printf 'Check container logs with "%s -f %s logs".\n' "${DOCKER_COMPOSE[*]}" "$COMPOSE_FILE" >&2
    exit 1
  fi
  sleep 3
  printf '.'
  done
printf '\n'

printf '\033[1;34m[setup]\033[0m Running database migrations inside container\n'
"${DOCKER_COMPOSE[@]}" -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan migrate --force --no-interaction

if [ "$RUN_SEED" = "1" ]; then
  printf '\033[1;34m[setup]\033[0m Seeding database\n'
  "${DOCKER_COMPOSE[@]}" -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan db:seed --force
fi

printf '\033[1;32m[setup]\033[0m Symfony environment is ready at %s\n' "$APP_URL"
printf 'Use "%s -f %s logs -f %s" to follow server output.\n' "${DOCKER_COMPOSE[*]}" "$COMPOSE_FILE" "$APP_SERVICE"

if [ "$TAIL_LOGS" -eq 1 ]; then
  "${DOCKER_COMPOSE[@]}" -f "$COMPOSE_FILE" logs -f "$APP_SERVICE"
fi
