#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${1:-/www/wwwroot/slip-gaji.toedjoesinargroup.com/slip-gaji}"
ENV_FILE="${APP_DIR}/.env"

cd "$APP_DIR"

set_env() {
    local key="$1"
    local val="$2"
    if grep -q "^${key}=" "$ENV_FILE"; then
        sed -i "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
    fi
}

set_env APP_DEBUG false
set_env APP_ENV production
set_env LOG_LEVEL error

php artisan config:clear
php artisan config:cache

echo "Debug dimatikan. APP_DEBUG=false, APP_ENV=production"
