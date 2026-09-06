#!/bin/sh
set -e

# Buat .env dari .env.example bila belum ada (mis. volume kosong saat pertama kali jalan)
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env
fi

if ! grep -q '^APP_KEY=base64:' /var/www/html/.env 2>/dev/null; then
    php artisan key:generate --force
fi

php artisan storage:link --force || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
