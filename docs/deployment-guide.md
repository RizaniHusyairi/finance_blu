# Panduan Deploy — SIKEREN-BLU

Aplikasi Laravel 11 (PHP ^8.2) untuk Sistem Informasi Keuangan & Penagihan Terpadu
BLU Kantor UPBU APT Pranoto — Samarinda.

Panduan ini mencakup deploy ke server Linux (Ubuntu/Debian) dengan Nginx + PHP-FPM + MySQL.
Catatan khusus Windows/Laragon ada di bagian akhir.

---

## 1. Kebutuhan Server (Prasyarat)

| Komponen | Versi minimum | Catatan |
|---|---|---|
| PHP | 8.2+ | beserta PHP-FPM |
| Composer | 2.x | manajer paket PHP |
| Node.js | 18+ (disarankan 20 LTS) | untuk build aset Vite |
| NPM | 9+ | bundling frontend |
| Database | MySQL 8 / MariaDB 10.6+ | (boleh PostgreSQL; default repo SQLite hanya untuk dev) |
| Web server | Nginx (atau Apache) | reverse proxy ke PHP-FPM |
| Git | terbaru | clone/pull kode |
| Cron | bawaan OS | untuk Laravel Scheduler |
| Supervisor | bawaan OS | untuk queue worker yang persisten |

### 1.1 Ekstensi PHP wajib
Aplikasi memakai DomPDF, QR (GD/Imagick), PhpSpreadsheet, dan MySQL. Pastikan ekstensi berikut aktif:

```
php-cli  php-fpm  php-mysql  php-mbstring  php-xml  php-bcmath
php-curl php-zip  php-gd     php-intl      php-fileinfo  php-tokenizer
php-ctype php-json php-openssl php-pdo
```

> `php-gd` (atau `php-imagick`) wajib karena QR Code & DomPDF butuh rendering gambar.
> `php-zip` & `php-xml` wajib untuk PhpSpreadsheet (import tarif/Excel).

Instal contoh (Ubuntu):
```bash
sudo apt update
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring \
  php8.2-xml php8.2-bcmath php8.2-curl php8.2-zip php8.2-gd php8.2-intl
```

---

## 2. Library / Dependency yang Di-install

### 2.1 Dependency PHP (Composer) — otomatis dari `composer.json`
Produksi (`require`):
- `laravel/framework` ^11.9 — framework inti
- `barryvdh/laravel-dompdf` ^3.1 — generate PDF (SPP/SPM/NPI/SP2D/Bupot)
- `laravel/tinker` ^2.9 — REPL/console
- `laravel/ui` ^4.5 — scaffolding auth
- `phpoffice/phpspreadsheet` ^5.7 — import/export Excel (tarif layanan)
- `simplesoftwareio/simple-qrcode` ^4.2 — QR Code TTE
- `spatie/laravel-permission` ^6.24 — RBAC (role & permission)

Dev (`require-dev`, TIDAK diinstal di produksi):
- `fakerphp/faker`, `laravel/breeze`, `laravel/pint`, `laravel/sail`,
  `mockery/mockery`, `nunomaduro/collision`, `phpunit/phpunit`

Perintah instal (produksi):
```bash
composer install --no-dev --optimize-autoloader
```

### 2.2 Dependency Frontend (NPM) — dari `package.json`
- `vite` ^6, `laravel-vite-plugin` ^1, `sass`, `bootstrap` ^5.2,
  `@popperjs/core`, `axios`, `fs-extra`

Perintah instal & build:
```bash
npm ci
npm run build
```

---

## 3. Langkah Deploy (Server Produksi Baru)

### Langkah 1 — Clone kode
```bash
cd /var/www
git clone https://github.com/RizaniHusyairi/finance_blu.git
cd finance_blu
git checkout main
```

### Langkah 2 — Instal dependency PHP
```bash
composer install --no-dev --optimize-autoloader
```

### Langkah 3 — Konfigurasi environment
```bash
cp .env.example .env
php artisan key:generate
```
Lalu edit `.env` (lihat bagian 4 di bawah untuk nilai produksi).

### Langkah 4 — Siapkan database
- Buat database & user di MySQL:
```sql
CREATE DATABASE sikeren_blu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sikeren'@'localhost' IDENTIFIED BY 'password_kuat';
GRANT ALL PRIVILEGES ON sikeren_blu.* TO 'sikeren'@'localhost';
FLUSH PRIVILEGES;
```
- Isi kredensial di `.env` (DB_CONNECTION=mysql, DB_DATABASE, DB_USERNAME, DB_PASSWORD).

### Langkah 5 — Migrasi & seeding
```bash
# migrasi skema + data awal (role, user, master)
php artisan migrate --force --seed
```
> `--force` wajib di produksi (Laravel meminta konfirmasi tanpa flag ini).
> Jika hanya ingin migrasi tanpa seeder: `php artisan migrate --force`.
>
> Seeder penting (lihat `database/seeders/DatabaseSeeder.php`): Role & Permission,
> User Account, Master Pegawai/COA/Pihak/Pajak, Workflow Definition.
> Jalankan seeder spesifik bila perlu, mis:
> `php artisan db:seed --class=RoleAndPermissionSeeder --force`

### Langkah 6 — Build aset frontend
```bash
npm ci
npm run build
```
Menghasilkan `public/build/`. Pastikan folder ini ikut ter-deploy.

### Langkah 7 — Symlink storage (file publik)
```bash
php artisan storage:link
```
> Menautkan `public/storage` → `storage/app/public`. Diperlukan agar arsip/QR/bukti
> yang disimpan di disk `public` dapat diakses.

### Langkah 8 — Optimasi & cache produksi
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```
> Jika nanti mengubah `.env`/route/view, ulangi cache atau `php artisan optimize:clear`.

### Langkah 9 — Set permission folder
```bash
sudo chown -R www-data:www-data /var/www/finance_blu
sudo find /var/www/finance_blu -type f -exec chmod 644 {} \;
sudo find /var/www/finance_blu -type d -exec chmod 755 {} \;
sudo chmod -R 775 storage bootstrap/cache
```

### Langkah 10 — Cron untuk Scheduler (WAJIB)
Aplikasi memakai Laravel Scheduler (`routes/console.php`):
- `wa:reminder-due-date` — reminder WhatsApp tagihan jatuh tempo (tiap jam)
- `users:disable-expired-temporary` — nonaktifkan akun PLT/PLH kedaluwarsa (harian 00:05)

Tambahkan crontab (`crontab -e` sebagai user www-data):
```cron
* * * * * cd /var/www/finance_blu && php artisan schedule:run >> /dev/null 2>&1
```

### Langkah 11 — Queue Worker (WAJIB, `QUEUE_CONNECTION=database`)
Notifikasi (WhatsApp, dll) berjalan via queue. Jalankan worker persisten dengan Supervisor.

Buat `/etc/supervisor/conf.d/sikeren-worker.conf`:
```ini
[program:sikeren-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/finance_blu/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/finance_blu/storage/logs/worker.log
stopwaitsecs=3600
```
Aktifkan:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sikeren-worker:*
```

### Langkah 12 — Konfigurasi Web Server (Nginx)
Contoh `/etc/nginx/sites-available/sikeren`:
```nginx
server {
    listen 80;
    server_name sikeren.example.go.id;
    root /var/www/finance_blu/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }

    client_max_body_size 20M;   # cukup untuk upload PDF/scan (maks ~10MB)
}
```
Aktifkan & reload:
```bash
sudo ln -s /etc/nginx/sites-available/sikeren /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### Langkah 13 — HTTPS (SSL)
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d sikeren.example.go.id
```

---

## 4. Konfigurasi `.env` Produksi (poin penting)

```dotenv
APP_NAME="SIKEREN-BLU"
APP_ENV=production
APP_KEY=base64:...            # dari php artisan key:generate
APP_DEBUG=false               # WAJIB false di produksi
APP_TIMEZONE=Asia/Makassar    # zona WITA (Samarinda)
APP_URL=https://sikeren.example.go.id

APP_LOCALE=id
APP_FALLBACK_LOCALE=id

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sikeren_blu
DB_USERNAME=sikeren
DB_PASSWORD=password_kuat

# Session & keamanan
SESSION_DRIVER=database
SESSION_ENCRYPT=true          # disarankan true di produksi
SESSION_SECURE_COOKIE=true    # hanya kirim cookie via HTTPS
SESSION_LIFETIME=120

QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=local
LOG_LEVEL=warning             # kurangi noise di produksi

# WhatsApp (isi sesuai gateway yang dipakai)
FONNTE_TOKEN=
WA_GATEWAY_URL=
WA_API_KEY=
WA_GATEWAY_SESSION=

# Mail (jika dipakai untuk reset password, dsb)
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@example.go.id"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 5. Alur Deploy Ulang (Update / Rilis Berikutnya)

```bash
cd /var/www/finance_blu

# (opsional) aktifkan maintenance mode
php artisan down

git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan migrate --force

# refresh cache produksi
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# restart queue worker agar pakai kode baru
php artisan queue:restart
sudo supervisorctl restart sikeren-worker:*

php artisan up
```

---

## 6. Verifikasi Pasca-Deploy (Checklist)

- [ ] `https://domain` membuka halaman login tanpa error
- [ ] Login berhasil dengan akun seeder; menu sesuai role
- [ ] `php artisan migrate:status` semua migrasi `Ran`
- [ ] `public/storage` symlink aktif; QR & arsip tampil
- [ ] Generate PDF SPP/SPM/NPI/SP2D berhasil (uji DomPDF + GD)
- [ ] QR TTE muncul pada dokumen final & halaman publik TTE terbuka
- [ ] Import tarif layanan (Excel) berfungsi (uji PhpSpreadsheet)
- [ ] `php artisan schedule:list` menampilkan job terjadwal
- [ ] Queue worker jalan (`supervisorctl status`); kirim notifikasi uji
- [ ] `APP_DEBUG=false` (cek halaman error tidak membocorkan stack trace)
- [ ] `composer audit` bersih (tidak ada CVE)

---

## 7. Catatan Lingkungan Windows / Laragon (Dev)

Untuk menjalankan secara lokal di Laragon (kondisi saat ini):
```bash
composer install
cp .env.example .env
php artisan key:generate
# DB: boleh tetap MySQL Laragon atau SQLite (touch database/database.sqlite)
php artisan migrate --seed
php artisan storage:link
npm install && npm run dev   # dev server Vite (jangan dipakai di produksi)
php artisan serve            # atau akses via virtual host Laragon
```
> Untuk dev, `npm run dev` (Vite) berjalan; di produksi gunakan `npm run build`.
> Scheduler & queue di dev bisa dijalankan manual:
> `php artisan schedule:work` dan `php artisan queue:work`.

---

## 8. Ringkasan Perintah Inti (Cheat Sheet)

```bash
composer install --no-dev --optimize-autoloader   # dependency PHP (produksi)
npm ci && npm run build                            # dependency & build frontend
php artisan key:generate                           # APP_KEY
php artisan migrate --force --seed                 # skema + data awal
php artisan storage:link                           # symlink file publik
php artisan config:cache route:cache view:cache    # optimasi
php artisan queue:work                             # worker (via Supervisor di prod)
php artisan schedule:run                           # dipanggil cron tiap menit
```
