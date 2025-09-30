#!/usr/bin/env bash
set -euo pipefail

# Bootstrap script to get the Laravel web application ready for local testing.
# Installs Composer & Node dependencies, ensures an APP_KEY exists, and clears caches.

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../Web_Application/Academy-LMS" && pwd)"
cd "$ROOT_DIR"

note() {
  printf '\n\033[1;34m[bootstrap]\033[0m %s\n' "$1"
}

warn() {
  printf '\n\033[1;33m[bootstrap][warn]\033[0m %s\n' "$1"
}

fail() {
  printf '\n\033[1;31m[bootstrap][error]\033[0m %s\n' "$1" >&2
  exit 1
}

command_exists() {
  command -v "$1" >/dev/null 2>&1
}

missing=()
for binary in php composer npm; do
  if ! command_exists "$binary"; then
    missing+=("$binary")
  fi
done

if [ "${#missing[@]}" -gt 0 ]; then
  fail "Missing required tooling: ${missing[*]}. Install prerequisites and rerun the installer."
fi

if [ ! -f .env ] && [ -f .env.example ]; then
  note "Creating .env from .env.example"
  cp .env.example .env
fi

note "Installing PHP dependencies via composer install"
composer install --no-interaction --prefer-dist --ansi

note "Installing Node dependencies"
if [ -f package-lock.json ]; then
  npm ci
else
  npm install
fi

note "Ensuring APP_KEY is present"
APP_KEY="$(grep -E '^APP_KEY=' .env | cut -d '=' -f2-)"
if [ -z "$APP_KEY" ]; then
  php artisan key:generate --ansi --force
fi

note "Clearing and optimizing Laravel caches"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

note "Linking storage directory"
if [ ! -L public/storage ]; then
  php artisan storage:link
else
  note "Storage link already exists"
fi

note "Building front-end assets"
npm run build

note "Bootstrap complete. Configure your database credentials in .env and run php artisan migrate when ready."
