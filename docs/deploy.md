# Birinchi deploy (e-store serveri, sslip.io)

Server: `155.117.43.22` (Ubuntu 24.04). Domen olinguncha vaqtinchalik manzil:
`https://155-117-43-22.sslip.io`. Domen olingach `APP_URL` va Nginx `server_name`
almashtiriladi, keyin `php artisan telegram:set-webhook` qayta ishga tushiriladi.

Sirlar (bot tokeni, `APP_KEY`, baza paroli) faqat serverdagi `.env` ga yoziladi.
Ularni chatga yoki repoga yozmang.

## 1. Foydalanuvchi va kod

```bash
sudo adduser --disabled-password --gecos "" enotebook
sudo mkdir -p /var/www/e-notebook && sudo chown enotebook: /var/www/e-notebook
sudo -u enotebook git clone https://github.com/Azizjon03/e-notebook /var/www/e-notebook
```

## 2. PostgreSQL bazasi

```bash
sudo -u postgres createuser enotebook --pwprompt
sudo -u postgres createdb enotebook --owner enotebook
```

## 3. PHP, Redis, Node

```bash
sudo apt install -y php8.4-fpm php8.4-pgsql php8.4-redis php8.4-intl php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip redis-server
# Node 22 (build uchun)
```

## 4. .env

```bash
cd /var/www/e-notebook
sudo -u enotebook cp .env.example .env
sudo -u enotebook nano .env
```

O'zgartiriladigan qatorlar:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://155-117-43-22.sslip.io
DB_PASSWORD=<baza paroli>
SESSION_SAME_SITE=none
SESSION_SECURE_COOKIE=true
TELEGRAM_BOT_TOKEN=<BotFather bergan token>
TELEGRAM_BOT_USERNAME=<bot nomi, @ siz>
TELEGRAM_WEBHOOK_SECRET=<openssl rand -hex 32 natijasi>
```

## 5. O'rnatish

```bash
sudo -u enotebook composer install --no-dev --optimize-autoloader
sudo -u enotebook php artisan key:generate
sudo -u enotebook npm ci && sudo -u enotebook npm run build
sudo -u enotebook php artisan migrate --force --seed
sudo -u enotebook php artisan config:cache && sudo -u enotebook php artisan route:cache
```

## 6. Nginx va HTTPS

`/etc/nginx/sites-available/e-notebook`:

```nginx
server {
    server_name 155-117-43-22.sslip.io;
    root /var/www/e-notebook/public;
    index index.php;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/e-notebook /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d 155-117-43-22.sslip.io
```

## 7. Queue va scheduler (systemd)

`/etc/systemd/system/enotebook-queue.service`:

```ini
[Service]
User=enotebook
WorkingDirectory=/var/www/e-notebook
ExecStart=/usr/bin/php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
Restart=always
MemoryMax=150M

[Install]
WantedBy=multi-user.target
```

Scheduler uchun `enotebook` foydalanuvchisining crontab'iga:

```
* * * * * cd /var/www/e-notebook && php artisan schedule:run >> /dev/null 2>&1
```

## 8. Bot webhook

```bash
sudo -u enotebook php artisan telegram:set-webhook
```

Shundan keyin botga `/start` yozib, ro'yxatdan o'tishni tekshiring.

## 9. Admin

O'zingizni admin qilish (avval botga `/start` yozing, Telegram ID'ingizni @userinfobot dan bilib olasiz):

```bash
sudo -u enotebook php artisan user:make-admin <telegram_id>
```

Keyin ilovada "Admin" tugmasi chiqadi: `/admin` da statistika, `/admin/bot` da webhook holati,
bot profili, buyruqlar va bot xabarlari matnlari. Bot tokeni u yerdan kiritilmaydi, faqat `.env` da.

## 10. Zaxira

`backup:database` har kuni 04:00 da `storage/app/backups` ga `pg_dump` zaxirasini yozadi va 14 kundan eskilarini o'chiradi.
Serverda `postgresql-client` (pg_dump) o'rnatilgan bo'lishi kerak. Zaxiralarni vaqti-vaqti bilan serverdan tashqariga ham ko'chiring.

Tiklash:

```bash
pg_restore --clean --no-owner -d enotebook storage/app/backups/<fayl>.dump
```
