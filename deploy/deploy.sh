#!/usr/bin/env bash
# e-notebook: kodni o'rnatish va yangilash. `deploy` foydalanuvchisi sudo'siz ishga tushiradi:
#   bash /var/www/e-notebook/deploy/deploy.sh          (keyingi yangilashlar)
#   bash deploy.sh                                       (birinchi marta, papka bo'sh bo'lsa ham)
# Avval bir marta `sudo bash deploy/server-setup.sh` ishlagan bo'lishi kerak.
set -euo pipefail
umask 002

APP_DIR=/var/www/e-notebook
REPO=https://github.com/Azizjon03/e-notebook.git
BRANCH=main
URL=${APP_URL:-https://enotebook.uz}
PASS_FILE=$HOME/.enotebook-db-password

if [ ! -d "$APP_DIR/.git" ]; then
    echo "Kod yuklanmoqda"
    git clone --branch "$BRANCH" "$REPO" "$APP_DIR"
fi
cd "$APP_DIR"
git fetch --quiet origin "$BRANCH"
git reset --hard "origin/$BRANCH"

FIRST=0
if [ ! -f .env ]; then
    FIRST=1
    echo ".env yaratilmoqda (sirlar ekranga chiqmaydi)"
    cp .env.example .env
    chmod 640 .env
    set_env() { # kalit qiymat
        if grep -q "^$1=" .env; then sed -i "s|^$1=.*|$1=$2|" .env; else echo "$1=$2" >> .env; fi
    }
    set_env APP_ENV production
    set_env APP_DEBUG false
    set_env APP_URL "$URL"
    set_env LOG_LEVEL warning
    set_env DB_CONNECTION pgsql
    set_env DB_DATABASE enotebook
    set_env DB_USERNAME enotebook
    set_env DB_PASSWORD "$(cat "$PASS_FILE")"
    set_env SESSION_SAME_SITE none
    set_env SESSION_SECURE_COOKIE true
    # Serverdagi boshqa loyihalar bilan aralashmasligi uchun alohida Redis bazalari.
    set_env REDIS_DB 3
    set_env REDIS_CACHE_DB 4
    set_env REDIS_PREFIX enotebook_
    set_env TELEGRAM_WEBHOOK_SECRET "$(openssl rand -hex 32)"
    set_env DEV_LOGIN false
fi

# Domen o'zgarsa ham APP_URL har safar yangilanadi.
sed -i "s|^APP_URL=.*|APP_URL=$URL|" .env

composer install --no-dev --optimize-autoloader --no-interaction --quiet
grep -q '^APP_KEY=base64:' .env || php artisan key:generate --force
npm ci --no-audit --no-fund --silent
npm run build --silent

chgrp -R www-data storage bootstrap/cache
chmod -R g+rwX storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod g+s {} +

php artisan migrate --force --seed
[ "$FIRST" -eq 1 ] && php artisan prayer-times:fetch || true
php artisan optimize

sudo -n /usr/bin/systemctl restart enotebook-queue
sudo -n /usr/bin/systemctl reload php8.4-fpm

echo "Tayyor: $URL"
grep -q '^TELEGRAM_BOT_TOKEN=.\+' .env || echo "Eslatma: .env da TELEGRAM_BOT_TOKEN va TELEGRAM_BOT_USERNAME ni o'zingiz yozing, keyin: php artisan optimize && php artisan telegram:set-webhook"
