# Slip Gaji TSG

Aplikasi generator slip gaji **CV. Toedjoe Sinar Group**.

## Deploy ke VPS

```bash
git clone https://github.com/asyaress/slip-7.git
cd slip-7

composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate

# Edit .env: DB_*, APP_URL, MAIL_*
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link

npm ci && npm run build   # jika public/build belum ada
```

### Python (QR signature)

```bash
pip install -r scripts/requirements.txt
```

### Setelah update dari Git

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Database

Migration utama:
- `employees` — data karyawan
- `salary_slips` — slip gaji per periode

Seed karyawan: `php artisan db:seed --class=EmployeeSeeder`
