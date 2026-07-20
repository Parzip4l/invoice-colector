#!/usr/bin/env sh
set -e

if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

if [ -f artisan ]; then
    php artisan config:clear >/dev/null 2>&1 || true
    php artisan key:generate --no-interaction --force >/dev/null 2>&1 || true
fi

exec "$@"
