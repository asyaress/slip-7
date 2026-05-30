#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${1:-/www/wwwroot/slip-gaji.toedjoesinargroup.com/slip-gaji}"
cd "$APP_DIR"

echo "========== DIAGNOSIS GENERATE SLIP =========="
echo ""

echo "==> [1] PHP proc_open (QR Python)..."
php -r "
\$disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
echo function_exists('proc_open') && !in_array('proc_open', \$disabled, true)
    ? 'proc_open: OK' : 'proc_open: DISABLED (QR akan dilewati, bukan penyebab 500 setelah patch)';
echo PHP_EOL;
"

echo ""
echo "==> [2] Python + segno..."
if command -v python3 >/dev/null; then
    python3 --version
    python3 -c "import segno; print('segno: OK')" 2>/dev/null || echo "segno: MISSING → pip3 install segno"
else
    echo "python3: MISSING"
fi

echo ""
echo "==> [3] Storage & symlink..."
test -L public/storage && echo "storage link: OK" || echo "storage link: MISSING → php artisan storage:link"
test -w storage/app/public && echo "storage writable: OK" || echo "storage writable: NO → chmod 775 storage"
test -f public/images/kop.png && echo "kop.png: OK" || echo "kop.png: MISSING"
test -f public/images/logo_m.png && echo "logo_m.png: OK" || echo "logo_m.png: MISSING"
test -f scripts/generate_qr_signature.py && echo "QR script: OK" || echo "QR script: MISSING"

echo ""
echo "==> [4] Database migration lembur..."
php artisan migrate:status 2>/dev/null | grep -E "lembur|Pending|Ran" || php artisan migrate:status

echo ""
echo "==> [5] Error log terakhir (generate/preview)..."
if [ -f storage/logs/laravel.log ]; then
    tail -30 storage/logs/laravel.log | grep -A2 "ERROR\|Exception\|SQLSTATE" | tail -20 || tail -5 storage/logs/laravel.log
else
    echo "laravel.log belum ada"
fi

echo ""
echo "==> [6] Quick fix..."
php artisan migrate --force 2>/dev/null || true
php artisan storage:link 2>/dev/null || true
chown -R www:www storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
php artisan config:clear
php artisan config:cache

echo ""
echo "Selesai. Coba generate lagi. Jika masih 500, kirim output [5] di atas."
