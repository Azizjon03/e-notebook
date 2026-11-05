# Birinchi deploy (e-store serveri, sslip.io)

Server: `155.117.43.22` (Ubuntu 24.04). Domen olinguncha vaqtinchalik manzil:
`https://155-117-43-22.sslip.io`. Domen olingach `APP_URL` va Nginx `server_name`
almashtiriladi, keyin `php artisan telegram:set-webhook` qayta ishga tushiriladi.

Sirlar (bot tokeni, `APP_KEY`, baza paroli) faqat serverdagi `.env` ga yoziladi.
Ularni chatga yoki repoga yozmang.

## 1. Serverni sozlash (bir marta, sudo)

`deploy/server-setup.sh` hamma root qadamlarini bajaradi: `/var/www/e-notebook` papkasi,
PostgreSQL roli va bazasi (parol `/home/deploy/.enotebook-db-password` ga yoziladi, ekranga chiqmaydi),
Redis, Nginx sayti, Let's Encrypt sertifikati, queue xizmati, scheduler cron'i va `deploy` uchun
faqat queue/php-fpm'ni qayta ishga tushirishga parolsiz sudo. Skriptni qayta ishga tushirish xavfsiz.

```bash
sudo bash server-setup.sh
```

## 2. Kodni o'rnatish va yangilash (deploy foydalanuvchisi, sudo'siz)

```bash
bash /var/www/e-notebook/deploy/deploy.sh   # birinchi marta: skriptni serverga ko'chirib, bash deploy.sh
```

Skript kodni `main` dan oladi, birinchi marta `.env` ni to'ldiradi (baza paroli, webhook siri,
alohida Redis bazalari), composer va npm build qiladi, migratsiya, birinchi marta namoz vaqtlarini yuklaydi,
keshlarni yangilaydi va queue'ni qayta ishga tushiradi.

## 3. Bot tokeni (o'zingiz)

`/var/www/e-notebook/.env` da quyidagilarni yozing (chatga yozmang):

```
TELEGRAM_BOT_TOKEN=<BotFather bergan token>
TELEGRAM_BOT_USERNAME=<bot nomi, @ siz>
```

Keyin:

```bash
cd /var/www/e-notebook && php artisan optimize && sudo systemctl restart enotebook-queue
```

## 4. Bot webhook

```bash
php artisan telegram:set-webhook
```

Shundan keyin botga `/start` yozib, ro'yxatdan o'tishni tekshiring.

## 5. Admin

O'zingizni admin qilish (avval botga `/start` yozing, Telegram ID'ingizni @userinfobot dan bilib olasiz):

```bash
php artisan user:make-admin <telegram_id>
```

Keyin ilovada "Admin" tugmasi chiqadi: `/admin` da statistika, `/admin/bot` da webhook holati,
bot profili, buyruqlar va bot xabarlari matnlari. Bot tokeni u yerdan kiritilmaydi, faqat `.env` da.

## 6. Zaxira

`backup:database` har kuni 04:00 da `storage/app/backups` ga `pg_dump` zaxirasini yozadi va 14 kundan eskilarini o'chiradi.
Serverda `postgresql-client` (pg_dump) o'rnatilgan bo'lishi kerak. Zaxiralarni vaqti-vaqti bilan serverdan tashqariga ham ko'chiring.

Tiklash:

```bash
pg_restore --clean --no-owner -d enotebook storage/app/backups/<fayl>.dump
```
