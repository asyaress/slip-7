#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${1:-/www/wwwroot/slip-gaji.toedjoesinargroup.com/slip-gaji}"

cd "$APP_DIR"

echo "==> Pull latest from GitHub..."
git fetch origin main
git reset --hard origin/main

echo "==> Composer (if needed)..."
if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --optimize-autoloader --no-interaction
fi

echo "==> Migrate..."
php artisan migrate --force

echo "==> Clear caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo "==> Done. Latest commit:"
git log -1 --oneline
