#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
WEB_APP_DIR="${REPO_ROOT}/Web_Application/Academy-LMS"
MOBILE_APP_DIR="${REPO_ROOT}/Student Mobile APP/academy_lms_app"

log() {
  printf '\n[%s] %s\n' "$(date '+%Y-%m-%dT%H:%M:%S%z')" "$*"
}

command -v realpath >/dev/null 2>&1 || realpath() { python3 -c 'import os,sys; print(os.path.realpath(sys.argv[1]))' "$1"; }

log "Starting consolidated security scan"

if command -v composer >/dev/null 2>&1; then
  log "Installing PHP dependencies"
  (cd "${WEB_APP_DIR}" && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --no-progress --prefer-dist --no-scripts)

  log "Running Composer vulnerability audit"
  (cd "${WEB_APP_DIR}" && composer audit --locked)

  if [ -x "${WEB_APP_DIR}/vendor/bin/phpstan" ]; then
    log "Executing PHP static analysis (Larastan)"
    (cd "${WEB_APP_DIR}" && vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=1G)
  else
    log "Skipping PHP static analysis because vendor/bin/phpstan is unavailable"
  fi
else
  log "composer not found on PATH; skipping PHP dependency and static analysis checks"
fi

if command -v npm >/dev/null 2>&1; then
  log "Auditing npm dependencies (production scope)"
  (cd "${WEB_APP_DIR}" && npm ci --ignore-scripts --no-audit)
  (cd "${WEB_APP_DIR}" && npm audit --omit=dev --audit-level=high)
else
  log "npm not found on PATH; skipping frontend dependency audit"
fi

if command -v flutter >/dev/null 2>&1; then
  log "Refreshing Flutter dependencies"
  (cd "${MOBILE_APP_DIR}" && flutter pub get)
  log "Checking Flutter packages for available security updates"
  (cd "${MOBILE_APP_DIR}" && flutter pub outdated --mode=null-safety)
else
  log "flutter not found on PATH; skipping mobile dependency review"
fi

if command -v trivy >/dev/null 2>&1; then
  log "Running Trivy filesystem scan"
  (cd "${REPO_ROOT}" && trivy fs --exit-code 1 --severity HIGH,CRITICAL .)
else
  log "trivy not found on PATH; skipping container/filesystem vulnerability scan"
fi

ZAP_TARGET="${ZAP_BASELINE_TARGET:-}"
if [[ -z "${ZAP_TARGET}" ]]; then
  log "ZAP_BASELINE_TARGET not set; skipping OWASP ZAP baseline scan"
elif ! command -v docker >/dev/null 2>&1; then
  log "docker not found on PATH; unable to run OWASP ZAP baseline scan"
else
  ZAP_OUTPUT_DIR="$(mktemp -d)"
  log "Running OWASP ZAP baseline scan against ${ZAP_TARGET}"
  if ZAP_OUTPUT_DIR="${ZAP_OUTPUT_DIR}" "${SCRIPT_DIR}/zap/run_baseline.sh" "${ZAP_TARGET}"; then
    log "OWASP ZAP baseline scan complete"
    if [[ -f "${ZAP_OUTPUT_DIR}/zap-summary.md" ]]; then
      log "OWASP ZAP findings summary"
      sed 's/^/  /' "${ZAP_OUTPUT_DIR}/zap-summary.md"
    fi
  else
    log "OWASP ZAP baseline scan encountered an error"
  fi
  log "ZAP artifacts available at ${ZAP_OUTPUT_DIR}"
fi

log "Security scan completed"
