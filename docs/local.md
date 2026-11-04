# Kompyuterda ishga tushirish

Kerak: PHP 8.3+, Composer, Node 22+, Git. Ma'lumotlar bazasi uchun SQLite yetadi (PostgreSQL shart emas).

```bash
git clone https://github.com/Azizjon03/e-notebook.git
cd e-notebook
composer install
npm install

cp .env.example .env
php artisan key:generate
```

`.env` faylida quyidagilarni o'zgartiring:

```
APP_ENV=local
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
DEV_LOGIN=true
QUEUE_CONNECTION=sync
CACHE_STORE=file
SESSION_DRIVER=file
```

Keyin:

```bash
touch database/database.sqlite
php artisan migrate --seed
npm run build
php artisan serve
```

Brauzerda http://127.0.0.1:8000 ni oching. Kirish sahifasida "Erkak sifatida" yoki "Ayol sifatida" tugmasini bosing: Telegramsiz sinov foydalanuvchisi bilan kirasiz.

Kodni o'zgartirib ko'rmoqchi bo'lsangiz, `npm run build` o'rniga boshqa oynada `npm run dev` ni ishga tushiring.

`DEV_LOGIN` faqat `APP_ENV=local` bo'lganda ishlaydi. Serverda bu qator yo'q yoki `false` bo'lishi kerak.

Botni kompyuterda sinash uchun Telegram serverlari sizning kompyuteringizga yetib kelishi kerak (masalan, ngrok orqali). Bu shart emas: web ilovaning hammasi botsiz ishlaydi.
