#!/usr/bin/env bash
set -euo pipefail

DOMAIN="${1:-slip-gaji.toedjoesinargroup.com}"
APP_DIR="${2:-/www/wwwroot/slip-gaji.toedjoesinargroup.com/slip-gaji}"
PUBLIC_DIR="${APP_DIR}/public"
NGINX_VHOST="/www/server/panel/vhost/nginx/${DOMAIN}.conf"
NGINX_REWRITE="/www/server/panel/vhost/rewrite/${DOMAIN}.conf"

echo "==> [1] Kosongkan rewrite include..."
mkdir -p "$(dirname "$NGINX_REWRITE")"
echo '# Laravel routing in vhost' > "$NGINX_REWRITE"

echo "==> [2] Perbaiki root + location / di vhost..."
python3 - "$NGINX_VHOST" "$DOMAIN" "$PUBLIC_DIR" <<'PYEOF'
import re, sys
path, domain, public_dir = sys.argv[1], sys.argv[2], sys.argv[3]

with open(path) as f:
    content = f.read()

# Paksa root ke .../public
content = re.sub(
    r'(\n\s*root\s+)[^;]+;',
    rf'\1{public_dir};',
    content,
    count=1,
)
if 'root ' not in content:
    content = content.replace(
        'server_name',
        f'root {public_dir};\n    server_name',
        1,
    )

# Hapus semua location / try_files lama
content = re.sub(
    r'\n\s*location / \{\s*\n\s*try_files[^}]+\}\s*\n',
    '\n',
    content,
    flags=re.MULTILINE,
)

block = '''
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
'''

marker = 'include enable-php-82.conf;'
if marker in content:
    content = content.replace(marker, block + '\n    ' + marker)
else:
    content = re.sub(r'(\n\s*index\s+index\.php[^;]*;)', r'\1' + block, content, count=1)

# Dedup rewrite include
lines, seen, clean = content.splitlines(), False, []
for line in lines:
    if '/www/server/panel/vhost/rewrite/' in line and domain in line:
        if seen:
            continue
        seen = True
        line = line.strip().lstrip('#').strip()
        clean.append('    ' + line)
        continue
    clean.append(line)

with open(path, 'w') as f:
    f.write('\n'.join(clean) + '\n')
print(f'root -> {public_dir}')
PYEOF

echo "==> [3] Reload Nginx..."
nginx -t
/etc/init.d/nginx reload

echo "==> [4] Clear Laravel cache..."
cd "$APP_DIR"
php artisan route:clear 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan config:cache 2>/dev/null || true

echo ""
echo "========== DIAGNOSTIK =========="
echo -n "root nginx  → "; grep -m1 '^\s*root ' "$NGINX_VHOST" | awk '{print $2}' | tr -d ';'
echo -n "index.php   → "; test -f "$PUBLIC_DIR/index.php" && echo OK || echo MISSING
echo -n "route login → "; php artisan route:list --path=login 2>/dev/null | grep -c login || echo 0
echo ""
echo "========== TES HTTP =========="
for path in / /login /up; do
    code=$(curl -s -o /tmp/curl-body.txt -w "%{http_code}" "https://${DOMAIN}${path}")
    hint=""
    if grep -qi 'nginx' /tmp/curl-body.txt 2>/dev/null; then hint=" (body nginx)"; fi
    if grep -qi 'Laravel\|Slip Gaji\|Masuk' /tmp/curl-body.txt 2>/dev/null; then hint=" (body Laravel)"; fi
    echo "${path} → HTTP ${code}${hint}"
done
