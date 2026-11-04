# e-notebook

Talabalar uchun rejalar ilovasi: kunlik vazifalar, kalendar, namoz vaqtlari, odatlar,
o'qish sessiyalari va kitoblar. Web, Telegram bot va Mini App bitta Laravel ilovasida.

## Stack

- Laravel 12, PHP 8.3+
- Inertia + React, Tailwind CSS 4, Vite
- PostgreSQL 16, Redis (queue, cache, sessiya)
- Pest testlar, Pint, GitHub Actions CI

## Lokal ishga tushirish

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
composer run dev
```

`.env` dagi `DB_*` qiymatlarini o'z PostgreSQL bazangizga moslang.

## Sirlar

Bot tokeni (`TELEGRAM_BOT_TOKEN`), `APP_KEY` va baza paroli faqat serverdagi `.env` va
GitHub Secrets'da turadi. Ularni repoga, issue'ga yoki chatga yozmang.

## Testlar

```bash
vendor/bin/pest
vendor/bin/pint --test
```

## Tuzilma

Kod modullarga bo'lingan, qarang: [app/Modules/README.md](app/Modules/README.md).
