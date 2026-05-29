# Rancangan Audit Proyek — SIKEREN-BLU

Sistem Informasi Keuangan & Penagihan Terpadu
BLU Kantor UPBU Kelas 1 Aji Pangeran Tumenggung Pranoto — Samarinda

> Dokumen ini adalah blueprint audit menyeluruh terhadap aplikasi berbasis Laravel 11
> dengan alur keuangan SPP → SPM → NPI → SP2D → BKU, penyetoran pajak (PPN/PPh),
> tanda tangan elektronik (TTE QR), serta RBAC (Spatie Permission).

| Atribut | Nilai |
|---|---|
| Framework | Laravel 11 (PHP ^8.2) |
| Otorisasi | spatie/laravel-permission ^6.24 (20+ peran) |
| PDF | barryvdh/laravel-dompdf |
| QR/TTE | simplesoftwareio/simple-qrcode |
| Spreadsheet | phpoffice/phpspreadsheet |
| DB | SQLite (default) / MySQL (produksi) |
| Tooling kualitas saat ini | Laravel Pint, PHPUnit (test masih minimal) |
| Skala | ~110 migration, 20+ service, modul keuangan + jasa/PNBP |

---

## 1. Tujuan & Sasaran Audit

| Tujuan | Penjelasan |
|---|---|
| Integritas keuangan | Memastikan tidak ada uang hilang, dobel, atau salah hitung pada alur tagihan → SP2D → BKU → pajak |
| Keamanan & otorisasi | Memastikan RBAC ditegakkan pada setiap endpoint sensitif (verifikasi, pencairan, master data) |
| Jejak audit (auditability) | Memastikan setiap aksi finansial terekam (`log_status_dokumen`) lengkap & tak dapat disangkal |
| Keabsahan dokumen | Memastikan TTE QR & hash dokumen tidak dapat dipalsukan/disalahgunakan |
| Kualitas & keterpeliharaan | Mengurangi risiko regresi pada logika keuangan |
| Kepatuhan | Sesuai kaidah pengelolaan keuangan negara/BLU (akuntabel, transparan, terintegrasi) |

---

## 2. Ruang Lingkup

### Dalam lingkup
- Modul keuangan inti: Tagihan (Kontrak/Perjaldin/Honorarium), SPP, SPM, NPI, SP2D, BKU, Realisasi Anggaran, Penyetoran Pajak (PPN/PPh).
- Workflow & verifikasi paralel (PPK, PPSPM, Koordinator Keuangan, Kasubbag, Bendahara Penerimaan/Pengeluaran, KPA, PLT/PLH).
- RBAC (Spatie) — gating menu & route, permission map.
- TTE QR / `App\Support\DocumentTte` / tabel `document_signatures`.
- Modul Jasa/PNBP (konsesi, PJP2U, utilitas) & integrasi pembayaran (BTN Virtual Account, WhatsApp gateway).
- Master data (DIPA, COA, Pajak, Pegawai, Pihak/Vendor).
- Konfigurasi & infrastruktur aplikasi (`.env`, session, storage, queue, cache).

### Di luar lingkup (kecuali diminta)
- Audit infrastruktur server/jaringan fisik.
- Penetration test oleh pihak ketiga eksternal.

---

## 3. Domain Audit & Area Risiko Spesifik

### A. Integritas Keuangan (prioritas tertinggi)
Fokus berkas: `app/Services/BkuPostingService.php`, `app/Services/BudgetRealizationService.php`,
`app/Http/Controllers/PenyetoranPajakKontrakController.php`,
`app/Http/Controllers/PenyetoranPajakHonorController.php`,
`app/Http/Controllers/Sp2dController.php` (`catatBku`),
`app/Http/Controllers/SppController.php` (`syncPotonganPajakHonor`),
`app/Services/DocumentNumberingService.php`.

Pertanyaan audit kunci:
- Apakah posting BKU **idempoten**? (cek duplikasi `referensi_pengeluaran_id` + `nomor_bukti`).
- Apakah semua mutasi uang dibungkus `DB::transaction`?
- Apakah invariant `total_bruto = total_potongan + total_netto` selalu konsisten?
- Apakah agregasi PPh 21 honor (`Σ detail.pph` vs `nominal_potongan`) tervalidasi dengan rollback bila selisih?
- Apakah penundaan posting BKU sampai pajak tersetor (NTPN lengkap) berlaku konsisten untuk KONTRAK & HONORARIUM?
- Pembulatan/float drift pada perhitungan pajak/potongan.
- Race condition pada generator nomor dokumen (`DocumentNumberingService`, `nomor_bupot`).

### B. Otorisasi & Akses (RBAC)
- Kecocokan middleware `role:`/`permission:` pada `routes/web.php` dengan logika controller (`hasRole` manual).
- IDOR: apakah `findOrFail($id)` pada dokumen keuangan memverifikasi tipe/kepemilikan (`abort_if`)?
- Mass assignment: model dengan `$guarded = ['id']` (PotonganTagihan, WorkflowApproval, DetailHonorarium, dll).
- Verifikasi: cegah approve langkah milik orang lain / double-approve.

### C. Jejak Audit & Non-repudiation
- Kelengkapan `log_status_dokumen` (user_id, role_saat_itu, ip_address, waktu) pada semua aksi finansial: create/update draft, submit, approve, revisi, post BKU, input billing/NTPN, eksekusi SP2D, finalisasi e-Bupot.
- Immutability log (tidak dapat dihapus/diubah).
- Konsistensi enum `aksi` & `status_baru`.

### D. Keabsahan Dokumen (TTE)
- `DocumentTte::hash()` — cakupan data cukup untuk mendeteksi tampering; signed URL kedaluwarsa.
- QR hanya terbit setelah `isFullyVerified` untuk semua tipe dokumen (SPP/SPM/NPI/SP2D).
- Parameter `signer` (PPK/Bendahara) pada signed URL tervalidasi & tak dapat dimanipulasi.

### E. Keamanan Aplikasi (OWASP Top 10)
- **Konfigurasi**: `APP_DEBUG=false` di produksi, `SESSION_ENCRYPT`, `APP_KEY`, paksa HTTPS.
- **Upload file** (bukti transfer/billing/BPN/BPPU): validasi mime & ukuran, path traversal, dan visibilitas disk (`public` vs `private` + signed download untuk dokumen sensitif).
- **Injeksi**: tinjau `whereRaw` (mis. `Sp2dKontrakController`) terhadap SQL injection.
- **XSS** (output Blade), **CSRF** (form), **SSRF** (WhatsApp/BTN gateway), secret di repo.
- Rate limiting login, kebijakan password, proteksi brute-force.

### F. Integritas Data (skema & migrasi)
- FK & perilaku `onDelete` (cascade/restrict/nullOnDelete); apakah soft-delete tagihan merambat benar ke potongan & detail.
- Migration data (backfill PPh, migrasi status NPI) reversibel & aman dijalankan ulang.
- Index pada kolom yang sering di-query (status, tipe_tagihan).

### G. Kualitas Kode & Keterpeliharaan
- Belum ada static analysis (hanya Pint); test minimal (mayoritas `ExampleTest`).
- Duplikasi logika antar `Sp2d*Controller` & `Verifikasi*Controller`.
- Controller gemuk (logika bisnis di controller, bukan service).

### H. Kinerja
- N+1 query pada index & dashboard; query di dalam loop; agregasi di PHP vs SQL.
- Notifikasi WhatsApp: sinkron vs async (queue).

---

## 4. Metodologi & Teknik

1. **SAST** — pasang Larastan/PHPStan (level bertahap), Pint (sudah ada), opsional Enlightn/Rector.
2. **Manual code review** terfokus pada domain A (keuangan) & B (otorisasi).
3. **Dynamic testing** — uji per peran (RBAC), alur end-to-end tiap tipe tagihan, uji IDOR & tampering signed URL.
4. **Database audit** — query rekonsiliasi (lihat Lampiran A).
5. **Configuration review** — `.env`, `config/*`, header keamanan, visibilitas disk.
6. **Dependency audit** — `composer audit` untuk CVE.

---

## 5. Checklist Audit (dapat dieksekusi)

### Keuangan
- [ ] Semua penulisan multi-tabel memakai transaksi DB
- [ ] BKU idempoten di seluruh jalur (kontrak/honor/perjaldin)
- [ ] Defer BKU sampai pajak tersetor konsisten (kontrak & honor)
- [ ] Konsistensi bruto/potongan/netto tervalidasi
- [ ] Generator nomor dokumen aman dari race & duplikasi

### Otorisasi
- [ ] Setiap route sensitif memiliki middleware `role:`/`permission:` yang benar
- [ ] Verifikasi tidak dapat di-bypass / double-approve
- [ ] Tidak ada IDOR pada dokumen keuangan
- [ ] Model finansial tidak rentan mass-assignment

### Jejak audit
- [ ] Semua aksi finansial ter-log (user, role, ip, waktu)
- [ ] Log bersifat append-only

### Keamanan
- [ ] `APP_DEBUG=false`, HTTPS, session secure di produksi
- [ ] Dokumen sensitif tidak di disk publik tanpa proteksi
- [ ] `whereRaw` aman dari injeksi
- [ ] Rate-limit & kebijakan password login
- [ ] `composer audit` bersih

### Data
- [ ] FK & cascade konsisten; soft-delete merambat benar
- [ ] Migration backfill reversibel/idempoten

### Kualitas
- [ ] PHPStan/Larastan terpasang & lulus level target
- [ ] Coverage test untuk service keuangan kritis

---

## 6. Tooling yang Disarankan

| Tool | Tujuan |
|---|---|
| `larastan/larastan` (PHPStan) | Analisis statik tipe & bug |
| `laravel/pint` (sudah ada) | Konsistensi style/format |
| `composer audit` | CVE dependency |
| PHPUnit (sudah ada) — perluas | Test integritas keuangan & RBAC |
| Laravel Telescope (dev) / log query | Deteksi N+1 & query lambat |
| `spatie/laravel-activitylog` (opsional) | Memperkuat audit trail bila `log_status_dokumen` kurang |

---

## 7. Deliverable Audit

1. **Laporan Temuan** — daftar temuan + severity (Critical/High/Medium/Low), lokasi `file:baris`, dampak, rekomendasi.
2. **Matriks Risiko** — likelihood × impact per temuan.
3. **Bukti rekonsiliasi keuangan** — hasil query integritas (BKU vs SP2D vs pajak).
4. **Matriks RBAC** — peran × route/aksi, menandai gap otorisasi.
5. **Rencana remediasi** — prioritas, estimasi effort, pemilik.
6. **Baseline regresi** — set test otomatis untuk mencegah temuan berulang.

---

## 8. Skema Severity

- **Critical**: kehilangan/duplikasi uang, bypass otorisasi pencairan, pemalsuan TTE, kebocoran kredensial.
- **High**: jejak audit hilang pada aksi finansial, IDOR dokumen, defer-pajak tidak konsisten.
- **Medium**: validasi input lemah, N+1 berat, konfigurasi tidak aman di non-prod.
- **Low**: code smell, duplikasi, kekurangan dokumentasi.

---

## 9. Tahapan & Urutan Pelaksanaan

1. **Persiapan** — pasang tooling (PHPStan, composer audit), siapkan data uji, inventaris route/role.
2. **Audit otomatis** — jalankan SAST, dependency audit, query rekonsiliasi DB.
3. **Review manual terfokus** — domain A → B → C/D → E → F–H.
4. **Pengujian dinamis** — skenario per peran & per tipe tagihan, uji tampering.
5. **Pelaporan & triase** — kompilasi temuan + severity + rekomendasi.
6. **Remediasi & verifikasi ulang** — perbaiki Critical/High lebih dulu, tambah test regresi.

---

## Lampiran A — Query Rekonsiliasi Keuangan (contoh)

> Jalankan via `php artisan tinker` atau SQL client. Sesuaikan nama kolom bila perlu.

1. **Tagihan SELESAI tetapi belum masuk BKU**
   - Tujuan: deteksi pencairan yang tidak terbukukan.
   - Logika: `tagihan.status = 'SELESAI'` AND tidak ada `buku_kas_umum.referensi_pengeluaran_id = tagihan.id`.

2. **Potongan PAJAK ber-NTPN tetapi BKU belum terbit (atau sebaliknya)**
   - Tujuan: deteksi inkonsistensi penundaan posting BKU.

3. **Baris BKU duplikat** (`referensi_pengeluaran_id` + `nomor_bukti` ganda)
   - Tujuan: uji idempotensi posting.

4. **Nomor dokumen duplikat** (`nomor_spp`, `nomor_spm`, `nomor_npi`, `nomor_sp2d`, `nomor_bupot`)
   - Tujuan: uji race generator nomor.

5. **Selisih PPh honor** (`Σ detail_honorarium.pph` per tagihan ≠ `potongan_tagihan.nominal_potongan` jenis PAJAK)
   - Tujuan: verifikasi agregasi pajak honor.

6. **Inkonsistensi total tagihan** (`total_bruto ≠ total_potongan + total_netto`)
   - Tujuan: verifikasi sinkronisasi total.

---

## Lampiran B — Inventaris Komponen Berisiko (titik awal review)

### Services keuangan/bisnis
- `BkuPostingService`, `BudgetRealizationService`, `DocumentNumberingService`, `DocumentNumberService`,
  `WorkflowService`, `PerjaldinWorkflowService`, `SppPerjaldinWorkflowService`,
  `TagihanHonorariumWorkflowService`, `TagihanKontrakWorkflowService`,
  `TagihanJasaCalculationService`, `Pembukuan\PembukuanService`, `Pembukuan\PiutangSyncService`,
  `Reports\ReportAggregationService`, `BtnVirtualAccountService`, `WhatsappService`.

### Controller pencairan & pajak
- `Sp2dController`, `Sp2dKontrakController`, `Sp2dHonorController`, `Sp2dPerjaldinController`,
  `PenyetoranPajakKontrakController`, `PenyetoranPajakHonorController`,
  `SppController`, `DocumentController`, `PublicDocumentSignatureController`.

### Verifikasi (workflow paralel)
- `BenpenNpiKontrakVerifikasiController`, `KasubbagNpiKontrakVerifikasiController`,
  `VerifikasiNpiHonorController`, `VerifikasiNpiPerjaldinController`, dan keluarga `Verifikasi*`.

### Model dengan `$guarded = ['id']` (cek mass-assignment)
- `PotonganTagihan`, `WorkflowApproval`, `DetailHonorarium`, `LogStatusDokumen`, dll.

### Dukungan TTE
- `App\Support\DocumentTte`, tabel `document_signatures`, route publik `public.document-tte.*`.

### Konfigurasi
- `.env` / `.env.example` (`APP_DEBUG`, `SESSION_ENCRYPT`, `FILESYSTEM_DISK`, kredensial gateway),
  visibilitas disk `public` untuk arsip pajak & bukti transfer.

---

## Lampiran C — Pemetaan OWASP Top 10 → Area Sistem

| OWASP | Area di SIKEREN-BLU |
|---|---|
| A01 Broken Access Control | RBAC route/menu, IDOR dokumen keuangan, verifikasi workflow |
| A02 Cryptographic Failures | `APP_KEY`, hash TTE, signed URL, penyimpanan dokumen sensitif |
| A03 Injection | `whereRaw` pencarian SP2D/tagihan, input filter |
| A04 Insecure Design | Alur pencairan, idempotensi BKU, double-approve |
| A05 Security Misconfiguration | `APP_DEBUG`, disk publik, header keamanan |
| A06 Vulnerable Components | `composer audit` |
| A07 Auth Failures | Login rate-limit, kebijakan password, session |
| A08 Data Integrity Failures | Migration backfill, mass assignment, soft-delete cascade |
| A09 Logging Failures | Kelengkapan & immutability `log_status_dokumen` |
| A10 SSRF | Integrasi WhatsApp gateway & BTN Virtual Account |
