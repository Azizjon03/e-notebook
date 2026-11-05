#!/usr/bin/env bash
# e-notebook: serverni bir martalik sozlash (root huquqi kerak qadamlar).
# Ishga tushirish:  sudo bash server-setup.sh
# Qayta ishga tushirish xavfsiz: bor narsa qayta yaratilmaydi.
#
# Kod, composer, build va migratsiyani keyin `deploy` foydalanuvchisi sudo'siz bajaradi.
# Sirlar: baza paroli faqat /home/deploy/.enotebook-db-password faylida (600) saqlanadi
# va ekranga chiqarilmaydi. Bot tokenini .env ga o'zingiz yozasiz.
set -euo pipefail

APP_USER=deploy
APP_DIR=/var/www/e-notebook
DOMAIN=155-117-43-22.sslip.io
PHP=8.4
DB=enotebook

[ "$(id -u)" -eq 0 ] || { echo "sudo bilan ishga tushiring: sudo bash $0"; exit 1; }
id "$APP_USER" >/dev/null || { echo "$APP_USER foydalanuvchisi topilmadi"; exit 1; }

echo "1/7 Papka: $APP_DIR"
usermod -aG www-data "$APP_USER"
mkdir -p "$APP_DIR"
chown "$APP_USER":www-data "$APP_DIR"
chmod 2775 "$APP_DIR"

echo "2/7 PostgreSQL: $DB roli va bazasi"
PASS_FILE=/home/$APP_USER/.enotebook-db-password
if ! sudo -u postgres psql -tAc "select 1 from pg_roles where rolname='$DB'" | grep -q 1; then
    PASS=$(openssl rand -hex 24)
    sudo -u postgres psql -q -c "create role $DB login password '$PASS'"
    install -m 600 -o "$APP_USER" -g "$APP_USER" /dev/null "$PASS_FILE"
    printf '%s' "$PASS" > "$PASS_FILE"
    unset PASS
else
    echo "   rol bor, parol o'zgartirilmadi"
fi
sudo -u postgres psql -tAc "select 1 from pg_database where datname='$DB'" | grep -q 1 \
    || sudo -u postgres createdb -O "$DB" "$DB"

echo "3/7 Redis"
systemctl enable --now redis-server

echo "4/7 Nginx: $DOMAIN"
cat > /etc/nginx/sites-available/e-notebook <<NGINX
server {
    listen 80;
    server_name $DOMAIN;
    root $APP_DIR/public;
    index index.php;
    client_max_body_size 10m;

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php$PHP-fpm.sock;
    }
    location ~ /\.(?!well-known) { deny all; }
}
NGINX
ln -sf /etc/nginx/sites-available/e-notebook /etc/nginx/sites-enabled/e-notebook
nginx -t
systemctl reload nginx

echo "5/7 HTTPS (Let's Encrypt)"
certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos --register-unsafely-without-email --redirect

echo "6/7 Queue xizmati va scheduler"
cat > /etc/systemd/system/enotebook-queue.service <<UNIT
[Unit]
Description=e-notebook queue
After=network.target redis-server.service postgresql.service

[Service]
User=$APP_USER
Group=www-data
UMask=0002
WorkingDirectory=$APP_DIR
ExecStart=/usr/bin/php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=10
MemoryMax=150M

[Install]
WantedBy=multi-user.target
UNIT
systemctl daemon-reload
systemctl enable enotebook-queue   # kod joylangach deploy foydalanuvchisi ishga tushiradi

CRON="* * * * * cd $APP_DIR && umask 002 && php artisan schedule:run >> /dev/null 2>&1"
( crontab -u "$APP_USER" -l 2>/dev/null | grep -v 'e-notebook && umask 002' || true; echo "$CRON" ) | crontab -u "$APP_USER" -

echo "7/7 Faqat shu buyruqlar uchun parolsiz sudo (yangilashdan keyin qayta ishga tushirish)"
cat > /etc/sudoers.d/enotebook <<SUDO
$APP_USER ALL=(root) NOPASSWD: /usr/bin/systemctl restart enotebook-queue, /usr/bin/systemctl start enotebook-queue, /usr/bin/systemctl reload php$PHP-fpm
SUDO
chmod 440 /etc/sudoers.d/enotebook
visudo -cf /etc/sudoers.d/enotebook

echo
echo "Tayyor. https://$DOMAIN ga kod joylanishi bilan sayt ochiladi."
