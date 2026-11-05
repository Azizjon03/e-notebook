# Deploy (e-store serveri, enotebook.uz)

Server: `155.117.43.22` (Ubuntu 24.04). Domen: `enotebook.uz` (Cloudflare).

Cloudflare DNS: `enotebook.uz` va `www` uchun `A 155.117.43.22`. Sertifikat olinguncha yozuvlar
"DNS only" (kulrang bulut) bo'lsin. `server-setup.sh` sertifikatni olgach, ularni "Proxied" ga o'tkazing
va SSL/TLS rejimini **Full (strict)** qiling. Domen o'zgarsa: `sudo DOMAIN=<domen> bash server-setup.sh`,
keyin `APP_URL=https://<domen> bash deploy.sh` (APP_URL faqat shunday berilganda o'zgaradi) va `php artisan telegram:set-webhook`.

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

## 7. Kitob PDF fayllari

Foydalanuvchilar yuklagan PDF'lar `storage/app/private/books/<user_id>/` da saqlanadi (bazada emas),
shuning uchun `backup:database` ularni o'z ichiga olmaydi. Bu papkani ham vaqti-vaqti bilan zaxiralang.

Bitta fayl 50 MB gacha. Buning uchun nginx'da `client_max_body_size 60m` va faqat shu sayt uchun
`upload_max_filesize=50M`, `post_max_size=55M` (PHP_VALUE) qo'yilgan. Eski serverda bu sozlamalar
`server-setup.sh` ni qayta ishga tushirganda qo'llanadi (kitchens.uz ga ta'sir qilmaydi).

## 8. Avtomatik deploy (GitHub Actions)

`main` ga push (yoki PR merge) bo'lganda `CI` testlari o'tsa, `.github/workflows/deploy.yml` serverga
SSH orqali kirib `deploy.sh` ni ishga tushiradi va `/login` 200 qaytarishini tekshiradi. Testlar yiqilsa,
deploy bo'lmaydi. Qo'lda: GitHub → Actions → Deploy → Run workflow.

Serverdagi kalit faqat `deploy.sh` ni ishga tushira oladi (`authorized_keys` da `command=...`),
boshqa buyruq bajarib bo'lmaydi. Avtomatik deploy `APP_URL` ni o'zgartirmaydi.

Bir martalik sozlash (Mac Terminalida, `gh` o'rnatilgan va `gh auth login` qilingan bo'lsin):

```bash
# 1. Serverda cheklangan kalit yaratish
ssh estore 'bash /var/www/e-notebook/deploy/github-deploy-key.sh'

# 2. Kalitni va server "barmoq izini" GitHub secretlariga to'g'ridan-to'g'ri yozish (ekranga chiqmaydi)
ssh estore 'cat ~/.ssh/github_deploy' | gh secret set DEPLOY_SSH_KEY -R Azizjon03/e-notebook
ssh-keyscan -t ed25519 155.117.43.22 2>/dev/null | gh secret set DEPLOY_KNOWN_HOSTS -R Azizjon03/e-notebook

# 3. Maxfiy kalitni serverdan o'chirish (u endi faqat GitHub'da)
ssh estore 'rm ~/.ssh/github_deploy'
```

Ixtiyoriy secretlar: `DEPLOY_HOST` (standart `155.117.43.22`), `DEPLOY_USER` (`deploy`), `DEPLOY_PORT` (`22`).
Domen ishlagach, sayt tekshiruvi uchun repo o'zgaruvchisi: `gh variable set APP_URL -b https://enotebook.uz -R Azizjon03/e-notebook`.

`gh` bo'lmasa: GitHub → Settings → Secrets and variables → Actions → New repository secret,
qiymatni `ssh estore 'cat ~/.ssh/github_deploy' | pbcopy` bilan nusxalab qo'ying.
