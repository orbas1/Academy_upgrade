#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")"/../.. && pwd)"
APP_DIR="${ROOT_DIR}/../../Web_Application/Academy-LMS"

if [[ ! -d "${APP_DIR}" ]]; then
  echo "[bootstrap] Unable to locate Laravel app directory at ${APP_DIR}" >&2
  exit 1
fi

pushd "${APP_DIR}" >/dev/null

printf "[bootstrap] Clearing caches and warming config...\n"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

printf "[bootstrap] Priming community caches...\n"
php artisan communities:cache --all || true

printf "[bootstrap] Scaling Horizon workers...\n"
php artisan horizon:pause || true
php artisan horizon:terminate || true
php artisan horizon:continue || true

printf "[bootstrap] Preloading Octane if enabled...\n"
if [[ "${OCTANE_ENABLED:-false}" == "true" ]]; then
  php artisan octane:start --server=swoole --workers=8 --task-workers=8 --watch --max-requests=100 &
  OCTANE_PID=$!
  sleep 5
  kill "$OCTANE_PID" || true
fi

printf "[bootstrap] Completed pre-test bootstrap.\n"
popd >/dev/null
