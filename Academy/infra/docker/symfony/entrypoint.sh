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

if [ -d storage ] && [ ! -L public/storage ]; then
    php artisan storage:link || true
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

        if [ "${DB_CREATE_DATABASE:-1}" = "1" ]; then
            echo "Ensuring database ${DB_DATABASE:-academy} exists"
            MYSQL_PWD="${DB_ADMIN_PASSWORD:-${DB_PASSWORD:-}}" mysql \
                --host="${DB_HOST:-mysql}" \
                --port="${DB_PORT:-3306}" \
                --user="${DB_ADMIN_USERNAME:-${DB_USERNAME:-root}}" \
                --protocol=tcp \
                --execute="CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE:-academy}\` CHARACTER SET ${DB_CHARSET:-utf8mb4} COLLATE ${DB_COLLATION:-utf8mb4_unicode_ci};"
        fi
    fi

    php artisan migrate --force --no-interaction

    if [ "${RUN_DB_SEED:-0}" = "1" ]; then
        php artisan db:seed --force
    fi
fi

if [ "${WARM_CACHES:-1}" = "1" ]; then
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
fi

symfony server:stop >/dev/null 2>&1 || true
symfony server:start --no-tls --allow-http --port="${APP_PORT:-8000}" --document-root=public

exec symfony server:log
