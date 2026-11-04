#!/bin/sh
# Konteyner har ishga tushganda: paketlar, .env, baza, build, server.
set -e
cd /app

composer install --no-interaction --prefer-dist
npm ci

if [ ! -f .env ]; then
    cp .env.example .env
fi
if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

touch database/database.sqlite
php artisan migrate --seed --force
npm run build

exec php artisan serve --host=0.0.0.0 --port=8000
