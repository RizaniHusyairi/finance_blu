# Runbook — Backup & Restore Database (BR-01)

> Prosedur backup, restore, dan Disaster Recovery (DR) untuk SIKEREN.
> Wajib bagian dari kesiapan produksi keuangan BLU/PNBP (auditable, ada RPO/RTO).

## 1. Mekanisme Backup

Backup terotomasi via Artisan command `db:backup` (lihat `app/Console/Commands/BackupDatabaseCommand.php`):

- **MySQL/MariaDB:** `mysqldump --single-transaction --quick --routines --no-tablespaces` → gzip.
- **SQLite:** salin berkas → gzip.
- Kredensial mysqldump dilewatkan via `--defaults-extra-file` (password tidak muncul di daftar proses), file kredensial sementara dihapus segera.
- Tanpa pipe shell → aman lintas-platform (Windows/Linux).

**Lokasi berkas:** `DB_BACKUP_PATH` (default `storage/app/backups`, disk privat — bukan path publik).
Format nama: `sikeren-{database}-{YYYY-MM-DD_HHMMSS}.sql.gz`.

**Jadwal:** harian **01:30** (`routes/console.php` → `Schedule::command('db:backup')`).
Prasyarat: scheduler berjalan (`php artisan schedule:work` atau cron `* * * * * php artisan schedule:run`).

**Retensi:** default **14** berkas terbaru (opsi `--keep`). Berkas lebih lama dihapus otomatis.

### Manual
```bash
php artisan db:backup                 # backup sekali, retensi default
php artisan db:backup --keep=30       # pertahankan 30 berkas terbaru
```

### Konfigurasi (.env)
```dotenv
# Opsional — arahkan ke volume/mount yang disinkronkan off-site
DB_BACKUP_PATH=/var/backups/sikeren
# Opsional — path biner mysqldump bila tidak ada di PATH
MYSQLDUMP_PATH=/usr/bin/mysqldump
```

## 2. Off-site & Enkripsi (WAJIB produksi)

Backup di server yang sama **tidak cukup** (rentan kehilangan server/ransomware).
Sinkronkan direktori backup ke object storage **terenkripsi & off-site**, mis. cron terpisah:

```bash
# Contoh sinkronisasi harian ke S3 (server-side encryption aktif)
30 2 * * * aws s3 sync /var/backups/sikeren s3://sikeren-backup/db/ --sse AES256 --delete

# Alternatif rclone (enkripsi crypt remote)
30 2 * * * rclone sync /var/backups/sikeren enc-remote:sikeren/db
```

Pastikan bucket/remote: enkripsi at-rest, versioning aktif, akses terbatas (least privilege), dan **bukan** publik.

## 3. Restore

> Lakukan di lingkungan terkendali. Restore menimpa data — konfirmasi target DB.

**MySQL/MariaDB:**
```bash
# 1. (opsional) buat database kosong bila perlu
mysql -u root -p -e "CREATE DATABASE sikeren_blu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. restore dari berkas gzip
gunzip < sikeren-sikeren_blu-2026-06-23_013000.sql.gz | mysql -u sikeren -p sikeren_blu

# 3. verifikasi aplikasi
php artisan migrate:status
php artisan config:cache
```

**SQLite:**
```bash
gunzip < sikeren-sqlite-2026-06-23_013000.sqlite.gz > database/database.sqlite
```

## 4. RPO / RTO

| Target | Nilai | Catatan |
|---|---|---|
| **RPO** (maks kehilangan data) | ≤ 24 jam | Backup harian. Untuk RPO lebih ketat, tambah jadwal (mis. tiap 6 jam) + binlog. |
| **RTO** (maks waktu pulih) | ≤ 2 jam | Restore gzip→mysql + verifikasi. |
| **Retensi** | 14 hari lokal | Tambah retensi berjenjang di off-site (harian 7 / mingguan 4 / bulanan 6). |

## 5. DR Drill (uji restore berkala)

- **Frekuensi:** triwulanan (atau setelah perubahan skema besar).
- **Langkah:** ambil backup terbaru → restore ke DB staging terpisah → jalankan `migrate:status` + smoke test alur kunci (login, lihat tagihan, cetak SPP) → catat durasi (validasi RTO).
- **Bukti:** simpan log drill (tanggal, berkas, durasi, hasil) untuk audit kepatuhan.
- **Checklist pasca-deploy:** pastikan item "backup harian berjalan & uji restore terakhir < 90 hari" terverifikasi.

## 6. Monitoring (kaitkan dengan MON-01)

- Command menulis `Log::error('db:backup gagal', ...)` saat gagal.
- Disarankan: alert bila tidak ada berkas backup baru > 26 jam, dan bila ukuran berkas anomali (mis. < 50% rata-rata).
- Integrasikan ke error tracking/alerting (Sentry/Slack) saat MON-01 dikerjakan.
