#!/usr/bin/env bash
set -euo pipefail

# Captures a browser screenshot of the Symfony-powered Laravel application that
# runs inside the docker-compose.symfony.yml stack. The script relies on
# Playwright to drive a headless Chromium instance so that the screenshot can be
# committed under docs/screenshots/.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
COMPOSE_FILE="${REPO_ROOT}/docker-compose.symfony.yml"
OUTPUT_DIR="${REPO_ROOT}/docs/screenshots"
URL="${1:-http://localhost:8000}"
WAIT_TIME="${WAIT_TIME:-5000}"

mkdir -p "$OUTPUT_DIR"

timestamp() {
  date +"%Y%m%d-%H%M%S"
}

ensure_command() {
  if ! command -v "$1" >/dev/null 2>&1; then
    printf '\033[1;31m[capture][error]\033[0m Missing required binary: %s\n' "$1" >&2
    if [ "$1" = "docker" ]; then
      printf '\033[1;33m[capture][hint]\033[0m Install Docker Desktop/Engine so the Symfony stack can run, or execute tools/install_container_runtime.sh for a Podman-based workflow.\n' >&2
      printf '\033[1;33m[capture][hint]\033[0m Review docs/local-development-symfony.md#prerequisites for dependency setup.\n' >&2
    fi
    exit 1
  fi
}

ensure_command node
ensure_command npm

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
        printf '\033[1;33m[capture][warn]\033[0m docker-compose detected but failed to execute; trying alternate frontends.\n'
      fi
    fi
  fi

  if command -v podman-compose >/dev/null 2>&1; then
    DOCKER_COMPOSE=(podman-compose)
    COMPOSE_FLAVOUR="podman-compose"
    return
  fi

  printf '\033[1;31m[capture][error]\033[0m No compatible compose frontend found.\n' >&2
  printf 'Install Docker or run tools/install_container_runtime.sh to provision Podman.\n' >&2
  exit 1
}

select_compose_command
printf '\033[1;34m[capture]\033[0m Compose frontend: %s\n' "$COMPOSE_FLAVOUR"

if ! "${DOCKER_COMPOSE[@]}" -f "$COMPOSE_FILE" ps --services --filter status=running | grep -q '^app$'; then
  printf '\033[1;33m[capture][warn]\033[0m App container is not running. Attempting to start stack.\n'
  "${DOCKER_COMPOSE[@]}" -f "$COMPOSE_FILE" up --detach --build
fi

PLAYWRIGHT_BIN=(npx --yes playwright@1.48.0)
"${PLAYWRIGHT_BIN[@]}" install --with-deps chromium >/dev/null

OUTPUT_FILE="${OUTPUT_DIR}/academy-symfony-$(timestamp).png"

printf '\033[1;34m[capture]\033[0m Taking screenshot of %s -> %s\n' "$URL" "$OUTPUT_FILE"
"${PLAYWRIGHT_BIN[@]}" screenshot "$URL" "$OUTPUT_FILE" --wait-for-timeout "$WAIT_TIME" --full-page

printf '\033[1;32m[capture]\033[0m Screenshot stored at %s\n' "$OUTPUT_FILE"
printf 'Remember to review and commit the image to preserve the environment state.\n'
