# Panduan Performa & Deploy — SIKEREN

Status: ketiga rekomendasi performa lanjutan (pagination, cache agregasi, Redis)
ternyata sebagian besar bersifat **deployment** atau **refactor**, bukan swap
kode cepat. Dokumen ini merangkum apa yang aman dilakukan dan caranya.

---

## 1. Caching produksi — WIN TERBESAR, GRATIS, SUDAH SIAP ✅

Diverifikasi: `config:cache`, `route:cache`, `view:cache` **semua berhasil** pada
kode saat ini (app sudah cache-ready). Ini menghilangkan parsing config, kompilasi
route, dan kompilasi Blade pada **setiap request** — biasanya pengurangan latensi
paling besar untuk aplikasi Laravel.

**Jalankan setiap kali deploy (setelah `git pull` + `composer install`):**

```bash
php artisan optimize          # config + route + view + event cache sekaligus
php artisan optimize:clear    # (saat perlu reset, mis. sebelum migrasi/debug)

composer install --no-dev --optimize-autoloader   # autoloader terklasifikasi
```

> PENTING: setelah `config:cache`, `env()` di LUAR file config TIDAK lagi terbaca.
> Pastikan seluruh akses env melalui `config(...)`. Kode ini sudah mengikuti pola
> itu (mis. `MONITORING_HEALTH_TOKEN` dibaca di controller dengan default aman).

---

## 2. Queue worker — WAJIB untuk WhatsApp async (perbaikan #1)

Pengiriman WhatsApp kini di-antrikan (`App\Jobs\SendWhatsappMessage`) agar tidak
memblokir request. Saat `QUEUE_CONNECTION=database`, **worker WAJIB berjalan**:

```ini
# /etc/supervisor/conf.d/sikeren-worker.conf
[program:sikeren-worker]
command=php /var/www/sikeren/artisan queue:work --tries=3 --backoff=30 --sleep=3
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/sikeren/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
supervisorctl reread && supervisorctl update && supervisorctl start sikeren-worker:*
# restart worker SETIAP deploy agar memuat kode baru:
php artisan queue:restart
```

Pengaman: probe `queue_backlog` pada `monitor:health` (MON-03) otomatis meng-alert
bila job menumpuk (tanda worker mati).

---

## 3. Redis — migrasi cache/queue/session (DEPLOYMENT, belum aktif)

Saat ini semua driver = `database` (berfungsi, tapi Redis jauh lebih cepat untuk
cache & queue). Belum bisa diaktifkan dari sisi kode: butuh server Redis +
ekstensi. Langkah:

```bash
# 1) Server + ekstensi
apt-get install redis-server
pecl install redis            # ATAU: composer require predis/predis

# 2) .env (produksi)
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

php artisan config:cache
php artisan queue:restart
```

Konfigurasi `config/database.php` (koneksi `redis`) sudah ada bawaan Laravel —
hanya perlu provisioning + flip env di atas.

---

## 4. Pagination daftar besar — perlu server-side DataTables (refactor)

Daftar volume tinggi (`tagihans/ppk_index`, `contracts/index`, `tagihan_jasa/index`,
`perjaldins/index`, `honorarium/index`, dll. — 19 view) memakai **DataTables
sisi-klien**: seluruh baris dimuat lalu di-paginate/cari di browser. Karena itu
`->get()`-nya **disengaja**; mengubah ke `->paginate()` server akan **merusak**
DataTables.

Fix yang benar = **server-side DataTables** (AJAX): controller mengembalikan JSON,
search/sort/paginate ditangani server. Ini refactor per-halaman (~50–100 baris +
penyesuaian view). Prioritaskan tabel paling cepat tumbuh: tagihan, kontrak,
log status dokumen, transaksi pembukuan. Lakukan bertahap, satu halaman per PR.

Mitigasi sementara (tanpa refactor): beri filter default (mis. tahun anggaran
berjalan) pada halaman terberat agar tak memuat seluruh riwayat.

---

## 5. Cache agregasi dashboard — keputusan trade-off (per-dashboard)

Tidak ada query mahal per-request global; agregasi mahal ada di dashboard keuangan.
Men-cache angka keuangan = trade-off **akurasi vs kecepatan**: TTL pendek (mis.
2–5 menit) bisa menampilkan angka basi sesaat setelah transaksi. Rekomendasi:

- Cache hanya widget ringkasan yang **toleran basi** (total bulanan/tahunan,
  serapan pagu) dengan `Cache::remember(..., now()->addMinutes(5), ...)`.
- Untuk angka kritis real-time (antrean persetujuan, saldo terkini), **jangan**
  di-cache; atau pakai **invalidasi berbasis event** (clear cache saat data
  terkait berubah) alih-alih TTL agar selalu akurat.

Karena ini menyentuh akurasi laporan keuangan, pemilihan widget mana yang di-cache
sebaiknya dikonfirmasi pemilik proses.
