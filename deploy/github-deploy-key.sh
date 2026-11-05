#!/usr/bin/env bash
# GitHub Actions uchun deploy kaliti. Serverda `deploy` foydalanuvchisi sudo'siz ishga tushiradi:
#   bash /var/www/e-notebook/deploy/github-deploy-key.sh
# Kalit faqat deploy.sh ni ishga tushira oladi: boshqa buyruq, port forwarding va terminal yo'q.
# Maxfiy kalit ekranga chiqmaydi; uni GitHub secretga qo'ygach serverdan o'chiring (docs/deploy.md, 8-bo'lim).
set -euo pipefail

KEY=$HOME/.ssh/github_deploy
AUTH=$HOME/.ssh/authorized_keys
MARK=e-notebook-github-actions

install -m 700 -d "$HOME/.ssh"
touch "$AUTH" && chmod 600 "$AUTH"

# Eski GitHub kaliti bo'lsa, almashtiriladi.
grep -v "$MARK" "$AUTH" > "$AUTH.tmp" || true
mv "$AUTH.tmp" "$AUTH"

rm -f "$KEY" "$KEY.pub"
ssh-keygen -q -t ed25519 -N '' -C "$MARK" -f "$KEY"
echo "command=\"bash /var/www/e-notebook/deploy/deploy.sh\",no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty $(cat "$KEY.pub")" >> "$AUTH"
rm -f "$KEY.pub"

echo "Kalit yaratildi: $KEY (maxfiy qismi). authorized_keys ga cheklangan holda qo'shildi."
