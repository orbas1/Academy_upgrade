#!/usr/bin/env bash
# Academy Upgrade – Compatibility Audit Script
# Validates host runtime versions before initiating the Laravel 11 upgrade rollout.

set -euo pipefail

REQUIRED_PHP="8.3"
REQUIRED_NODE="20"
REQUIRED_MYSQL="8.0.36"
REQUIRED_REDIS="7"

check_version() {
  local name="$1" required="$2" command="$3"
  local current
  if ! current=$(eval "$command"); then
    echo "[FAIL] Unable to determine $name version" >&2
    exit 1
  fi
  if [[ "$current" != *"$required"* ]]; then
    echo "[FAIL] $name version $current (requires $required)" >&2
    exit 1
  fi
  echo "[OK] $name version $current"
}

check_version "PHP" "$REQUIRED_PHP" "php -v | head -n1"
check_version "Node" "$REQUIRED_NODE" "node -v"
check_version "MySQL" "$REQUIRED_MYSQL" "mysql -V"
check_version "Redis" "$REQUIRED_REDIS" "redis-server -v"

echo "All compatibility checks passed."
