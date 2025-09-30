#!/usr/bin/env bash
set -euo pipefail

# Bootstrap script to get the Laravel web application ready for local testing.
# Installs Composer & Node dependencies, ensures an APP_KEY exists, and clears caches.

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../Web_Application/Academy-LMS" && pwd)"
cd "$ROOT_DIR"

command_exists() {
  command -v "$1" >/dev/null 2>&1
}

note() {
  printf '\n\033[1;34m[bootstrap]\033[0m %s\n' "$1"
}

warn() {
  printf '\n\033[1;33m[bootstrap][warn]\033[0m %s\n' "$1"
}

if [ ! -f .env ] && [ -f .env.example ]; then
  note "Creating .env from .env.example"
  cp .env.example .env
  NEW_ENV_CREATED=1
else
  NEW_ENV_CREATED=0
fi

if command_exists composer; then
  note "Installing PHP dependencies via composer install"
  composer install --no-interaction --prefer-dist --optimize-autoloader
else
  warn "Composer is not installed. Skipping PHP dependency installation."
fi

if command_exists npm; then
  if [ -f package-lock.json ]; then
    note "Installing Node dependencies via npm ci"
    npm ci
  else
    note "Installing Node dependencies via npm install"
    npm install
  fi
else
  warn "npm is not installed. Skipping Node dependency installation."
fi

if command_exists php && [ -f artisan ]; then
  if [ "${NEW_ENV_CREATED}" -eq 1 ]; then
    note "Generating a fresh APP_KEY"
    php artisan key:generate --ansi
  else
    APP_KEY="$(grep -E '^APP_KEY=' .env | cut -d '=' -f2-)"
    if [ -z "$APP_KEY" ]; then
      note "APP_KEY missing; generating one now"
      php artisan key:generate --ansi --force
    fi
  fi

  note "Clearing and optimizing Laravel caches"
  php artisan optimize:clear
  php artisan config:cache
else
  warn "PHP or artisan not available; skipping Laravel specific setup."
fi

if command_exists npm && [ -f package.json ]; then
  note "Building front-end assets"
  npm run build
fi

note "Bootstrap complete. Configure your database credentials in .env and run php artisan migrate when ready."
