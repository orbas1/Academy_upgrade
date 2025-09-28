#!/usr/bin/env bash
# Laravel 11 upgrade automation script
# Usage:
#   ./tools/preflight/laravel11_upgrade.sh [--dry-run]
#   ./tools/preflight/laravel11_upgrade.sh --rollback

set -euo pipefail

ROOT_DIR="$(git rev-parse --show-toplevel)"
APP_DIR="$ROOT_DIR/Academy"
WEB_DIR="$APP_DIR/Web_Application/Academy-LMS"
LOG_DIR="$WEB_DIR/storage/upgrade"
REPORT_DIR="$LOG_DIR/reports"
SUMMARY_FILE="$LOG_DIR/summary.json"
DRY_RUN=false
ROLLBACK=false

function usage() {
  cat <<USAGE
Laravel 11 upgrade automation

Options:
  --dry-run   Print commands without executing them.
  --rollback  Restore composer.lock and git state from backup.
USAGE
}

function parse_args() {
  while [[ "$#" -gt 0 ]]; do
    case "$1" in
      --dry-run)
        DRY_RUN=true
        shift
        ;;
      --rollback)
        ROLLBACK=true
        shift
        ;;
      -h|--help)
        usage
        exit 0
        ;;
      *)
        echo "Unknown option: $1" >&2
        usage
        exit 1
        ;;
    esac
  done
}

function ensure_directories() {
  mkdir -p "$LOG_DIR" "$REPORT_DIR"
}

function log_to() {
  local logfile="$1"
  shift
  if $DRY_RUN; then
    echo "[DRY-RUN] tee $logfile :: $*"
  else
    "$@" | tee "$logfile"
  fi
}

function backup_state() {
  if $ROLLBACK; then
    return
  fi
  if $DRY_RUN; then
    echo "[DRY-RUN] git stash push --include-untracked -m 'pre-laravel11-upgrade'"
  else
    git -C "$WEB_DIR" stash push --include-untracked -m 'pre-laravel11-upgrade' >/dev/null || true
  fi
}

function restore_state() {
  if $DRY_RUN; then
    echo "[DRY-RUN] git stash pop"
    echo "[DRY-RUN] git checkout composer.lock"
  else
    git -C "$WEB_DIR" stash list | grep -q 'pre-laravel11-upgrade' && git -C "$WEB_DIR" stash pop || true
    git -C "$WEB_DIR" checkout -- composer.lock composer.json 2>/dev/null || true
  fi
}

function run_composer_upgrade() {
  pushd "$WEB_DIR" >/dev/null
  log_to "$LOG_DIR/laravel11-composer.log" bash -c "
    set -euo pipefail
    composer config platform.php 8.3.0
    composer require laravel/framework:^11.0 laravel/tinker --with-all-dependencies
    composer require nunomaduro/larastan:^2.9 phpstan/phpstan:^1.11 --dev
    composer require laravel/scout meilisearch/meilisearch-php --no-update
    composer update
    composer remove swiftmailer/swiftmailer || true
  "
  popd >/dev/null
}

function run_npm_install() {
  pushd "$WEB_DIR" >/dev/null
  log_to "$REPORT_DIR/npm.log" bash -c "
    set -euo pipefail
    npm install
    npm audit fix || true
  "
  popd >/dev/null
}

function run_tests() {
  pushd "$WEB_DIR" >/dev/null
  log_to "$LOG_DIR/tests-parallel.log" php artisan test --parallel || true
  log_to "$LOG_DIR/phpstan.log" vendor/bin/phpstan analyse --memory-limit=1G || true
  log_to "$LOG_DIR/larastan.log" vendor/bin/larastan analyse --level=6 app database routes || true
  log_to "$REPORT_DIR/pint.log" ./vendor/bin/pint || true
  popd >/dev/null
}

function generate_summary() {
  if $DRY_RUN; then
    echo "[DRY-RUN] Generating summary.json"
    return
  fi
  cat <<JSON > "$SUMMARY_FILE"
{
  "timestamp": "$(date -Iseconds)",
  "commands": {
    "composer": "storage/upgrade/laravel11-composer.log",
    "tests": "storage/upgrade/tests-parallel.log",
    "phpstan": "storage/upgrade/phpstan.log",
    "larastan": "storage/upgrade/larastan.log",
    "pint": "storage/upgrade/reports/pint.log",
    "npm": "storage/upgrade/reports/npm.log"
  }
}
JSON
}

function rollback() {
  echo "[INFO] Executing rollback"
  restore_state
  rm -rf "$LOG_DIR"
}

function main() {
  parse_args "$@"
  ensure_directories
  if $ROLLBACK; then
    rollback
    exit 0
  fi
  backup_state
  run_composer_upgrade
  run_npm_install
  run_tests
  generate_summary
  echo "[INFO] Laravel 11 upgrade automation complete"
}

main "$@"
