# Laporan Temuan Audit — SIKEREN-BLU (finance_blu)

Tanggal audit: 2026-05-30 · Branch: `rombak_DB` · Auditor: Claude Code
Basis: `docs/audit-plan.md` (8 domain A–H, checklist, Lampiran A–C)

Metode: SAST/dependency (`composer audit`), manual code review terfokus (domain A & B),
review konfigurasi, dan **rekonsiliasi DB langsung** (query Lampiran A dijalankan via tinker
terhadap database dev `blu_finance`).

---

## 0. Ringkasan Eksekutif

| Severity | Jumlah |
|---|---|
| Critical | 0 |
| High | 3 |
| Medium | 9 |
| Low | 6 |

**Status rekonsiliasi keuangan saat ini: BERSIH.** Seluruh query Lampiran A dijalankan
terhadap data dev (9 tagihan, 6 baris BKU) dan tidak menemukan inkonsistensi:

| Pemeriksaan | Hasil |
|---|---|
| Tagihan SELESAI tanpa BKU | 0 |
| BKU duplikat (`referensi_pengeluaran_id` + `nomor_bukti`) | 0 |
| `total_bruto ≠ total_potongan + total_netto` | 0 |
| Selisih Σ`detail.pph` vs potongan PAJAK (honor) | 0 (dari 2 honor) |
| Pajak ber-NTPN tapi BKU belum terbit | 0 |

Kesimpulan: tidak ada uang hilang/dobel/salah hitung **pada data saat ini**. Namun terdapat
**celah struktural** (idempotensi BKU hanya di level aplikasi, beberapa generator tanpa lock,
dokumen sensitif di disk publik) yang dapat menimbulkan kerusakan di bawah konkurensi/beban
produksi. Prioritaskan remediasi 3 temuan High.

---

## 1. Temuan High

### H-1 · Posting BKU tidak transaksional & tanpa unique constraint DB (idempotensi rapuh)
- **Domain:** A (Integritas Keuangan) · A04 Insecure Design
- **Lokasi:** `app/Services/BkuPostingService.php:30-78`; migrasi `database/migrations/2026_04_01_010500_create_bku_mutasi_rekonsiliasi_pelaporan_v2.php:12-27` (tabel `buku_kas_umum` hanya punya index `(tanggal_transaksi, arus_kas)`, **tidak ada** unique pada `(referensi_pengeluaran_id, nomor_bukti)`).
- **Dampak:** Idempotensi hanya dijaga pola *check-then-create* (cari existing → create) **tanpa `DB::transaction` dan tanpa lock**. Pada double-click / dua request paralel (TOCTOU), kedua request lolos cek "existing" lalu sama-sama meng-create → **baris BKU ganda = pengeluaran kas tercatat dobel**. Tidak ada jaring pengaman DB.
- **Bukti:** saat ini 0 duplikat (belum ada konkurensi), tetapi tidak ada penghalang struktural.
- **Rekomendasi:**
  1. Tambah unique index `(referensi_pengeluaran_id, nomor_bukti)` pada `buku_kas_umum`.
  2. Bungkus `postTagihanPengeluaran()` dalam `DB::transaction` + `lockForUpdate` pada tagihan/rekening (ikuti pola `BudgetRealizationService::recordFromSp2d`).
  3. Tangani `QueryException` (unique) sebagai idempotent no-op.

### H-2 · Dokumen keuangan sensitif disimpan di disk `public` (broken access control)
- **Domain:** E (Keamanan) · A01 Broken Access Control / A05 Misconfiguration
- **Lokasi:** ~30 titik `->store('...', 'public')`, mis.:
  - `PenyetoranPajakHonorController.php:170,239,255` & `PenyetoranPajakKontrakController.php:198,261,278` — **Kode Billing, BPN (bukti setor pajak), BPPU**.
  - `Sp2dController.php:226` — **bukti transfer SP2D**.
  - `SppController.php:1142` (faktur pajak), `TagihanController.php:201-229` (invoice/RAB/lampiran), `ContractController.php` (SPK/SPMK/jaminan), `DipaController.php:97,240` (dokumen DIPA).
- **Dampak:** Berkas di disk `public` ter-symlink ke `public/storage` dan **dapat diunduh siapa pun** yang menebak/menemukan URL (`/storage/arsip/pajak-honor/...`), **tanpa autentikasi/otorisasi**. Ini membocorkan bukti setor pajak, mutasi transfer, dan dokumen kontrak BLU.
- **Rekomendasi:** Pindahkan ke disk `local`/`private`; sajikan via route ber-autentikasi (`Storage::download` + policy/role) atau `temporarySignedRoute` berdurasi pendek. Migrasikan berkas lama keluar dari `public`.

### H-3 · Tautan tanda tangan (magic link) tanpa kedaluwarsa & tanpa proteksi rute
- **Domain:** D (Keabsahan Dokumen / TTE) · A01/A07
- **Lokasi:** `routes/web.php:84-87` (`public.magic-link.*` — GET/POST `sign` **tanpa** middleware `signed`/`auth`); token dibuat `TagihanTteController.php:66,80` (`Str::random(40)` — acak kuat) namun `document_signatures` **tidak punya kolom `expires_at`** (bandingkan `ShortLink` yang punya).
- **Dampak:** Token penandatanganan berlaku **selamanya**. Bila tautan bocor (mis. di-forward via WhatsApp), pihak tak berwenang dapat membuka dokumen (`documentPdf`) dan **menandatangani kapan saja**. Tidak ada batas waktu maupun pembatasan rute.
- **Rekomendasi:** Tambah `expires_at` + cek kedaluwarsa pada `show/sign/document`; pertimbangkan single-use (invalidasi token setelah `signed_at`); rate-limit endpoint sign.

---

## 2. Temuan Medium

### M-1 · `saldo_akhir` BKU dihitung tanpa lock (race)
- A · `BkuPostingService.php:150-159`. `nextSaldoAkhir` membaca baris terakhir lalu kurangi nominal tanpa `lockForUpdate`. Posting konkuren pada rekening sama → `saldo_akhir` salah/berurutan kacau. **Rekomendasi:** hitung saldo di dalam transaksi dengan lock baris terakhir, atau kalkulasi saldo sebagai turunan (view) alih-alih disimpan.

### M-2 · Generator nomor legacy tanpa lock/transaksi (SPP & `nomor_bupot`)
- A · `DocumentNumberingService.php:14-31` (SPP: `orderBy desc` + parse + 1, tanpa lock); `PenyetoranPajakHonorController.php:464-475` (`nomor_bupot`). **Dampak diperkecil** oleh UNIQUE di DB (`nomor_spp`, `nomor_bupot`) → race menghasilkan **error 500/kegagalan**, bukan duplikasi diam. Tetap mengganggu keandalan. **Rekomendasi:** migrasikan ke `DocumentNumberService` (sudah pakai `lockForUpdate`) atau bungkus dengan lock + retry.

### M-3 · Kegagalan realisasi anggaran ditelan (silent) saat eksekusi SP2D
- A/C · `Sp2dController.php:242-250`. `syncRealisasiAnggaran` menangkap `\Exception` dan **hanya `Log::error`** di dalam transaksi `catatBku`. Akibatnya SP2D bisa `EXECUTED` & BKU terbit **tanpa** baris `realisasi_anggaran` (komentar kode sendiri ragu "maybe rethrow?"). **Rekomendasi:** rethrow agar transaksi rollback, atau flag eksplisit + alert; jangan biarkan pencairan "berhasil sebagian".

### M-4 · Otorisasi tidak konsisten (campuran middleware route vs cek manual)
- B · Sebagian aksi cek `hasRole` manual (`PenyetoranPajak*Controller@index`), sebagian `storeBilling`/`storeNtpn` **tidak** cek role di method dan sepenuhnya bergantung middleware rute. Perlu **matriks RBAC** (peran × route) untuk memastikan tiap endpoint sensitif benar-benar tergerbang. **Rekomendasi:** standardkan dengan `authorize()`/Policy atau middleware `role:`/`permission:` konsisten; hapus cek manual ad-hoc.

### M-5 · Mass-assignment pada model finansial (`$guarded = ['id']`)
- B/A08 · 45+ model memakai `$guarded=['id']` (termasuk `BukuKasUmum`, `PotonganTagihan`, `WorkflowApproval`, `DokumenSp2d`, `RealisasiAnggaran`, `LogStatusDokumen`). Titik konkret berisiko: `MasterUangHarianPerjaldinController.php:52` `->update($request->all())`. Bila pola `request->all()` menyebar, atribut sensitif (`ntpn`, `nominal`, `status`, `saldo_akhir`) bisa di-set dari input. **Rekomendasi:** gunakan `$fillable` eksplisit pada model finansial; ganti `$request->all()` dengan `$request->validate([...])`.

### M-6 · Log status bukan append-only (mutable)
- C/A09 · `LogStatusDokumen` `$guarded=['id']`, tanpa kontrol immutability (tak ada trigger/observer yang mencegah `update`/`delete`). Jejak audit non-repudiation berisiko dimodifikasi. **Rekomendasi:** observer yang melempar pada `updating`/`deleting`, atau pindahkan ke storage append-only / `spatie/laravel-activitylog`.

### M-7 · Dependency rentan — `composer audit`: 11 advisory / 7 paket
- E/A06 · Antara lain Symfony `http-foundation` (CVE-2026-48736 SSRF bypass), `http-kernel` (CVE-2026-45075 HEAD bypass `IsSignatureValid`/`IsCsrfTokenValid` — relevan karena aplikasi memakai signed routes), `mailer` (CVE-2026-45068 argument injection), `mime`. **Rekomendasi:** `composer update` ke versi ter-patch; jadikan `composer audit` gate CI.

> Catatan: CVE-2026-45075 (`IsSignatureValid` bypass via HEAD) berinteraksi langsung dengan banyak rute `signed` (TTE, vendor upload). Patch ini sebaiknya didahulukan.

### M-8 · `APP_DEBUG=true` & `SESSION_ENCRYPT=false`
- E/A05 · `.env:4,31` (lokal) dan **`.env.example:4,31`** (template) → risiko terbawa ke produksi: stack trace/konfigurasi bocor, session tak terenkripsi. **Rekomendasi:** set `.env.example` ke `APP_DEBUG=false`; dokumentasikan checklist produksi (HTTPS paksa, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`).

### M-9 · FK `buku_kas_umum.referensi_pengeluaran_id` = `nullOnDelete`
- F/A08 · Migrasi BKU baris 21. Jika tagihan dihapus, referensi BKU menjadi `null` → baris kas "yatim" tak tertaut sumber, padahal modul memakai soft-delete. **Rekomendasi:** gunakan `restrictOnDelete` (cegah hapus tagihan yang sudah masuk kas) dan pastikan soft-delete tagihan tidak memutus jejak.

---

## 3. Temuan Low

- **L-1 (A)** Validasi selisih PPh honor **tautologis** — `SppController.php:633-640` membandingkan `$totalPotonganPajak` dengan dirinya sendiri (`= $totalPphFromDetail`), sehingga `if (round(a) !== round(a))` tak pernah benar. Tidak ada validasi nyata. Ganti dengan rekonsiliasi terhadap nilai yang benar-benar tersimpan + rollback bila beda.
- **L-2 (A)** Perhitungan uang memakai `float` di PHP (`sum('pph')`, `nilai_honor`) — risiko *drift* pembulatan; dimitigasi kolom `decimal(18,2)`. Gunakan `bcmath`/integer-sen untuk agregasi kritis.
- **L-3 (C)** `role_saat_itu` kadang hardcoded (`'Bendahara Pengeluaran'` di PenyetoranPajak*) atau fallback `'SYSTEM'` (`BkuPostingService.php:70`), bukan role aktual; `status_baru` kadang = status lama. Kurang akurat untuk forensik.
- **L-4 (D)** TTE QR `URL::signedRoute` tanpa kedaluwarsa — wajar untuk dokumen cetak permanen, namun catat sebagai keputusan desain (tanda tangan signed melindungi tamper `hash`/`signer`).
- **L-5 (G)** Tidak ada static analysis (Larastan/PHPStan); hanya Pint. Test ada (`AuthFlowTest`, `RoleAccessTest`, `PerjaldinWorkflowTest`, `ContractAddendumWorkflowTest`, `DashboardTest`, `PembukuanMenuTest`) namun **tidak ada** test integritas keuangan (idempotensi BKU, agregasi pajak, defer-pajak).
- **L-6 (G)** Duplikasi logika antar `PenyetoranPajakKontrak/Honor` & keluarga `Sp2d*Controller`; controller gemuk (`SppController` >1000 baris). Ekstrak ke service.

---

## 4. Temuan Positif (kontrol yang sudah baik)

- `BudgetRealizationService::recordFromSp2d` — `DB::transaction` + `lockForUpdate` + cek idempotensi (`status=TERCATAT`). Pola teladan.
- `Sp2dController::catatBku` — transaksional; **defer posting BKU sampai pajak tersetor konsisten untuk KONTRAK & HONORARIUM** (sesuai sasaran audit).
- `DocumentNumberService` (generator baru) — `lockForUpdate` + loop anti-collision + retry.
- **SQL Injection: tidak ditemukan.** Seluruh `whereRaw` memakai parameter binding `?` (mis. `Sp2dKontrakController.php:59-70`, `SuperAdminJasaLaporanController.php:313-319`); `DB::raw` hanya pada ekspresi/agregasi statis.
- **Rate-limit login aktif** (`Auth/LoginController.php:127` `throttleKey` + verifikasi `throttle:6,1`).
- TTE QR hanya terbit setelah `DocumentTte::isFullyVerified` (workflow `APPROVED` + semua approval `APPROVED`); hash SHA-256 mencakup nomor, tanggal, nominal, status, dan rincian approval; rute publik ber-`signed` melindungi manipulasi `hash`/`signer`.
- Nomor dokumen unik di level DB (`nomor_spp/spm/npi/sp2d/bupot/tagihan/spk/invoice`), index pada kolom yang sering di-query, FK `constrained`.
- Dashboard memakai agregasi SQL (`DB::raw SUM/COUNT`), bukan agregasi di PHP.

---

## 5. Matriks Risiko (likelihood × impact)

| ID | Temuan | Likelihood | Impact | Severity |
|---|---|---|---|---|
| H-1 | BKU non-transaksional / tanpa unique | Sedang (double-click/konkuren) | Tinggi (kas dobel) | **High** |
| H-2 | Dokumen sensitif di disk publik | Tinggi (URL ditebak/bocor) | Tinggi (kebocoran data) | **High** |
| H-3 | Magic link TTE tanpa expiry | Sedang | Tinggi (TTE disalahgunakan) | **High** |
| M-1 | saldo_akhir tanpa lock | Sedang | Sedang | Medium |
| M-3 | Realisasi anggaran silent-fail | Rendah | Tinggi | Medium |
| M-7 | Dependency CVE | Tinggi | Sedang | Medium |
| M-5 | Mass-assignment | Rendah | Tinggi | Medium |
| M-6 | Log mutable | Rendah | Tinggi | Medium |

---

## 6. Rencana Remediasi (prioritas)

**Sprint 1 (Critical/High — segera):**
1. H-1: unique index + transaksi/lock pada posting BKU. (effort: S)
2. H-2: pindahkan dokumen sensitif ke disk privat + download ber-otorisasi. (effort: M)
3. H-3: tambah `expires_at` + cek kedaluwarsa pada magic link TTE. (effort: S)
4. M-7: `composer update` (utamakan patch `symfony/http-kernel`). (effort: S)

**Sprint 2 (Medium):**
5. M-3 rethrow realisasi; M-1 lock saldo; M-2 migrasi generator nomor ke `DocumentNumberService`.
6. M-4 standardkan RBAC + bangun **Matriks RBAC**; M-5 ganti `$guarded` → `$fillable` pada model finansial; M-6 observer immutability log; M-8/M-9 hardening config & FK.

**Sprint 3 (Low + baseline regresi):**
7. L-1 perbaiki validasi PPh; L-2 bcmath; L-5 pasang Larastan + test integritas keuangan (idempotensi BKU, agregasi pajak, defer-pajak, IDOR, tampering signed URL) sebagai **baseline regresi**.

---

## 7. Lampiran — Query Rekonsiliasi (siap pakai)

Dijalankan via `php artisan tinker`. Hasil per 2026-05-30: seluruhnya 0 (bersih).

```sql
-- 1. Tagihan SELESAI tanpa BKU
SELECT t.id FROM tagihan t WHERE t.status='SELESAI'
  AND NOT EXISTS (SELECT 1 FROM buku_kas_umum b WHERE b.referensi_pengeluaran_id=t.id);

-- 3. BKU duplikat
SELECT referensi_pengeluaran_id, nomor_bukti, COUNT(*) c FROM buku_kas_umum
  WHERE referensi_pengeluaran_id IS NOT NULL
  GROUP BY referensi_pengeluaran_id, nomor_bukti HAVING c>1;

-- 5. Selisih PPh honor
SELECT t.id,
  (SELECT SUM(pph) FROM detail_honorarium dh WHERE dh.tagihan_id=t.id) sd,
  (SELECT SUM(nominal_potongan) FROM potongan_tagihan pt WHERE pt.tagihan_id=t.id AND pt.jenis_potongan='PAJAK') sp
  FROM tagihan t WHERE t.tipe_tagihan='HONORARIUM' HAVING ABS(COALESCE(sd,0)-COALESCE(sp,0))>0.01;

-- 6. Inkonsistensi total
SELECT id FROM tagihan WHERE ROUND(total_bruto,2) <> ROUND(COALESCE(total_potongan,0)+COALESCE(total_netto,0),2);
```

---

## 8. Pemetaan Cakupan vs `audit-plan.md` (verifikasi kelengkapan)

| Domain plan | Status | Temuan terkait |
|---|---|---|
| A Integritas Keuangan | ✅ | H-1, M-1, M-2, M-3, L-1, L-2 + 5 rekonsiliasi (bersih) |
| B Otorisasi & RBAC | ✅ | M-4 (RBAC konsistensi), M-5 (mass-assign), IDOR (abort_if ada) |
| C Jejak Audit | ✅ | M-6 (immutability), L-3 (konsistensi enum/role) |
| D TTE | ✅ | H-3 (magic link), L-4 (expiry QR); positif isFullyVerified+hash |
| E Keamanan OWASP | ✅ | H-2 (disk publik), M-7 (CVE), M-8 (config); SQLi nihil; login throttled |
| F Integritas Data | ✅ | M-9 (FK nullOnDelete), unique nomor OK, index OK |
| G Kualitas | ✅ | L-5 (no PHPStan/test keuangan), L-6 (duplikasi/controller gemuk) |
| H Kinerja | ✅ | Agregasi SQL OK; verifikasi WA async = tindak lanjut |
| Lampiran A query | ✅ | dijalankan, hasil 0 |
| Lampiran B inventaris | ✅ | service & controller berisiko ditinjau |
| Lampiran C OWASP map | ✅ | A01/A03/A05/A06/A07/A09/A10 tercakup di temuan |
