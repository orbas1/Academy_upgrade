#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/html"
cd "$APP_DIR"

if [ ! -f .env ]; then
    if [ -f .env.docker ]; then
        cp .env.docker .env
    elif [ -f .env.docker.example ]; then
        cp .env.docker.example .env
    elif [ -f .env.example ]; then
        cp .env.example .env
    fi
fi

if [ "${SKIP_COMPOSER_INSTALL:-0}" != "1" ]; then
    composer install --prefer-dist --no-progress --no-interaction
fi

if ! grep -q '^APP_KEY=' .env || [ -z "$(grep '^APP_KEY=' .env | cut -d '=' -f2-)" ]; then
    php artisan key:generate --force
fi

if [ "${SKIP_MIGRATIONS:-0}" != "1" ]; then
    if [ -n "${DB_HOST:-}" ]; then
        echo "Waiting for database ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
        until MYSQL_PWD="${DB_PASSWORD:-}" mysqladmin \
            --host="${DB_HOST:-mysql}" \
            --port="${DB_PORT:-3306}" \
            --user="${DB_USERNAME:-root}" \
            --silent ping >/dev/null 2>&1; do
            sleep 2
        done
    fi
    php artisan migrate --force --no-interaction
fi

symfony server:stop >/dev/null 2>&1 || true
symfony server:start --no-tls --allow-http --port="${APP_PORT:-8000}" --document-root=public

exec symfony server:log
