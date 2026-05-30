#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${1:-/www/wwwroot/slip-gaji.toedjoesinargroup.com/slip-gaji}"
ENV_FILE="${APP_DIR}/.env"
BACKUP="${ENV_FILE}.bak.$(date +%Y%m%d%H%M%S)"
DEBUG_KEY="${DEBUG_KEY:-tsg-debug-$(date +%s)}"

cd "$APP_DIR"

if [ ! -f "$ENV_FILE" ]; then
    echo "ERROR: .env tidak ditemukan di $ENV_FILE"
    exit 1
fi

cp "$ENV_FILE" "$BACKUP"
echo "Backup .env → $BACKUP"

set_env() {
    local key="$1"
    local val="$2"
    if grep -q "^${key}=" "$ENV_FILE"; then
        sed -i "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
    else
        echo "${key}=${val}" >> "$ENV_FILE"
    fi
}

set_env APP_DEBUG true
set_env APP_ENV local
set_env LOG_LEVEL debug

if ! grep -q "^DEBUG_KEY=" "$ENV_FILE"; then
    set_env DEBUG_KEY "$DEBUG_KEY"
else
    DEBUG_KEY=$(grep "^DEBUG_KEY=" "$ENV_FILE" | cut -d= -f2-)
fi

# Subfolder deploy (aaPanel running dir /public under /slip-gaji)
if ! grep -q "^APP_URL=.*/slip-gaji" "$ENV_FILE"; then
    set_env APP_URL "https://slip-gaji.toedjoesinargroup.com/slip-gaji"
fi

php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo ""
echo "========== DEBUG AKTIF =========="
echo "APP_DEBUG=true, LOG_LEVEL=debug"
echo ""
echo "Buka di browser:"
echo "  https://slip-gaji.toedjoesinargroup.com/slip-gaji/debug/status"
echo "  https://slip-gaji.toedjoesinargroup.com/slip-gaji/debug/last-error"
echo "  https://slip-gaji.toedjoesinargroup.com/slip-gaji/debug/test-slip"
echo ""
echo "Jika APP_DEBUG=false nanti, pakai key:"
echo "  ?key=${DEBUG_KEY}"
echo ""
echo "Setelah selesai: bash scripts/vps-disable-debug.sh"
