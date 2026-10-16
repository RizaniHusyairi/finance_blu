# Integrasi SIPANDAI pada laporan PJP2U

## Penggunaan

1. Buka Laporan PJP2U → rekap bulanan → **Rekonsiliasi Penumpang** pada laporan harian/mingguan.
2. Super Admin, Super Admin Jasa, atau Koordinator Jasa mengisi pemetaan kode maskapai (misalnya GA) ke mitra terkait sekali saja. Pemetaan berlaku untuk seluruh laporan mitra, bukan hanya satu laporan. Jangan menebak kode dari nama mitra.
3. Pada laporan berstatus **diajukan**, klik **Ambil Data SIPANDAI**. Admin Jasa juga dapat mengambil dan merekonsiliasi.
4. Periksa selisih per nomor penerbangan, kelengkapan data, status penerbangan, dan kategori penumpang. Nomor yang berulang dijumlahkan selama periode karena laporan lama tidak memiliki tanggal per baris penerbangan.
5. Isi catatan pemeriksaan, centang konfirmasi, kemudian **Simpan Rekonsiliasi**. Jika laporan perlu dikoreksi, gunakan penolakan/perbaikan laporan yang ada sebelum merekonsiliasi ulang.
6. Verifikasi laporan melalui alur yang sudah ada. Setelah integrasi aktif, verifikasi PJP2U mensyaratkan rekonsiliasi terbaru yang masih sesuai isi laporan.

Angka SIPANDAI adalah jumlah baris manifest hasil scan valid, bukan konfirmasi boarding. Jumlah laporan untuk pembandingan mencakup dewasa, anak, bayi, dan transit. Sistem tidak mengubah kategori, jumlah yang ditagihkan, tarif, atau nilai tagihan secara otomatis. Catatan wajib diisi, termasuk bila jumlah sama. Data hilang ditampilkan sebagai kosong, bukan nol. Selisih/status non-CLOSED disorot untuk diperiksa; keputusan tetap pada verifikator.

## Konfigurasi

Backend SIPANDAI `.env`:

```dotenv
FINANCE_INTEGRATION_TOKEN="token-acak-minimal-32-karakter"
```

finance_blu `.env`:

```dotenv
SIPANDAI_ENABLED=true
SIPANDAI_URL="http://127.0.0.1:3001/api"
SIPANDAI_TOKEN="token-yang-sama-dengan-backend"
```

Untuk server selain localhost wajib HTTPS. Jangan menaruh token di JavaScript browser atau version control. Token berbeda dari JWT login petugas. Setelah mengganti konfigurasi, restart backend dan jalankan `php artisan config:clear` (atau bangun ulang config cache pada deployment).

Jalankan migrasi khusus:

```sh
php artisan migrate --path=database/migrations/2026_10_07_000001_create_sipandai_reconciliations_table.php
```

Opsional, pemetaan awal melalui `SIPANDAI_AIRLINE_MAP='{"ID_MITRA":"GA"}'`. Pemetaan yang disimpan melalui UI mengambil prioritas. Satu kode maskapai hanya dapat dipetakan ke satu mitra dalam tabel pemetaan UI.

## Kontrak API

`GET /api/integrations/finance/manifests?from=2026-10-07&to=2026-10-07&airline=GA`, header `Authorization: Bearer TOKEN`.

Respons schemaVersion 1 berisi `timezone`, `from`, `to`, `airlineCode`, `generatedAt`, `basis`, dan `flights` (ID, nomor, kode maskapai, STD, tujuan, status, jumlah scan). Maksimal 31 hari, batas hari Asia/Makassar inklusif. Endpoint tidak mengirim nama penumpang, tiket, PNR, KTP, atau foto. Semua status penerbangan disertakan agar pembatalan/tidak ditutup terlihat oleh pemeriksa.

## Penyimpanan dan batasan

`sipandai_reconciliations` menyimpan snapshot setiap pengambilan, ringkasan perbandingan, fingerprint laporan, petugas/waktu, dan hasil pemeriksaan. Pengambilan ulang membuat revisi yang harus diperiksa ulang, bukan laporan/tagihan baru. Versi lama tetap tersimpan di database. Verifikasi dan pengambilan mengunci baris laporan untuk menghindari penggunaan snapshot yang bersamaan berubah. Laporan yang telah diverifikasi atau ditagihkan tidak dapat diambil/disetujui ulang. Laporan historis yang sudah diverifikasi tidak diwajibkan rekonsiliasi retroaktif.

SIPANDAI masih memiliki fallback deduplikasi PNR dan pencatatan manifest best-effort. Jumlah scan dapat kurang lengkap; sinkronisasi perangkat dan rekonsiliasi operasional tetap diperlukan. Data SIPANDAI bukan satu-satunya dasar tagihan otomatis. Untuk data lokal tanpa mitra/hak PJP2U/laporan, siapkan data melalui alur aplikasi yang sudah tersedia; instalasi tidak menambahkan laporan atau tagihan dummy.

Tes: `php artisan test --filter=SipandaiReconciliationTest`; backend: `npm test -- --runInBand finance-manifest.controller.spec.ts` dan `npx tsc --noEmit --incremental false`.
