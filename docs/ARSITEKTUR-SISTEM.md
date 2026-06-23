# Arsitektur & Struktur Sistem — Finance BLU (SIKEREN)

> Dokumentasi struktur **Back-End**, **Front-End**, dan **Modul Sistem** secara menyeluruh.
> Aplikasi pengelolaan keuangan **BLU (Badan Layanan Umum) Bandara / PNBP** — mencakup
> penganggaran (DIPA), pengadaan/kontrak, penagihan (tagihan kontrak, honorarium, perjalanan
> dinas, jasa/PNBP), pencairan (SPP→SPM→NPI→SP2D), pembukuan BLU (BKU, buku pembantu,
> rekonsiliasi bank, pengesahan), serta portal mitra jasa.

---

## 1. Ringkasan Teknologi (Tech Stack)

| Lapisan | Teknologi |
|---|---|
| **Framework** | Laravel 11.51 (PHP ^8.2) |
| **Pola** | MVC + Service Layer + Workflow Engine + Spatie Permission (RBAC) |
| **Database** | MySQL/MariaDB (default migrasi), kompatibel SQLite |
| **Front-End build** | Vite 8 + Bootstrap 5 + Sass + Axios |
| **UI Template** | Maxton (admin dashboard, Bootstrap-based) |
| **Auth** | Laravel UI / Breeze (scaffolding), session-based |
| **Otorisasi** | `spatie/laravel-permission` (role-based) |
| **PDF** | `barryvdh/laravel-dompdf` |
| **Spreadsheet** | `phpoffice/phpspreadsheet` (import/export Excel) |
| **QR Code** | `simplesoftwareio/simple-qrcode` (TTE / verifikasi dokumen) |
| **Queue** | database driver |
| **Lokalisasi** | `id` (Bahasa Indonesia), Carbon locale id |

**Dependensi kunci (composer.json):**
`laravel/framework`, `laravel/ui`, `laravel/tinker`, `barryvdh/laravel-dompdf`,
`phpoffice/phpspreadsheet`, `simplesoftwareio/simple-qrcode`, `spatie/laravel-permission`.

---

## 2. Arsitektur Back-End

### 2.1 Pola Arsitektur
```
Request → Route (web.php) → Middleware (auth, account.active, role:*)
       → Controller (HTTP, validasi via Form Request)
       → Service (business logic) ──→ Model (Eloquent) ──→ Database
       → Workflow Engine (approval berjenjang)
       → View (Blade) / PDF (dompdf) / Notification (WA/Email)
```

Logika bisnis berat **tidak** diletakkan di controller, tetapi di lapisan **`app/Services`**
(mis. posting BKU, kalkulasi tagihan jasa, workflow approval, integrasi). Controller bertugas
sebagai orkestrator tipis (validasi → panggil service → render).

### 2.2 Struktur Direktori `app/`
```
app/
├── Console/Commands/        # Artisan commands + scheduled jobs
├── Enums/                   # Enum domain (jenis rekening, kode buku, dll)
├── Helpers/                 # TerbilangHelper (angka → terbilang, autoload composer)
├── Http/
│   ├── Controllers/         # ±115 controller (lihat §2.4)
│   ├── Middleware/          # EnsureAccountIsActive
│   └── Requests/            # Form Request (validasi terstruktur)
├── Models/                  # ±95 model Eloquent (lihat §2.3)
├── Notifications/           # Notifikasi (Perjaldin revisi, Workflow)
├── Providers/               # AppServiceProvider
├── Services/                # Business logic layer (lihat §2.5)
└── Support/                 # Penolong TTE/dokumen (ContractDocumentTte, dll)
```

### 2.3 Model (Domain Data) — `app/Models`
Dikelompokkan per domain fungsional:

**Master Data Inti**
- `User`, `MasterPegawai`, `MasterPihak`, `MasterPersonelEksternal`
- `MasterCoa` (bagan akun), `MasterDipa` / `DetailDipa` / `RiwayatRevisiDipa`, `Budget`
- `RekeningBank`, `MasterTarifPajak`, `MasterUangHarianPerjaldin`
- `KodeTransaksi`, `AkunPendapatan`

**Manajemen Kontrak & Pengadaan**
- `Contract` / `KontrakPengadaan`, `DetailKontrak`, `KontrakTermin`, `KontrakAddendum`, `JaminanKontrak`
- `MasterMitraVendor`, `Supplier` (via model terkait)

**Transaksi & Tagihan**
- `Tagihan`, `PotonganTagihan`, `TransaksiPenerimaan`, `RealisasiAnggaran`
- `DetailPerjaldin`, `Perjaldin`, `TagihanPerjaldinKomponen` (perjalanan dinas)
- `DetailHonorarium` (honorarium)
- `LogStatusDokumen` (audit trail status)

**Dokumen Pencairan & Arsip**
- `Spp` / `DokumenSpp`, `DokumenSpm`, `DokumenNpi`, `DokumenSp2d`, `ArsipDokumen`
- `DocumentNumber`, `DocumentNumberSequence`, `DocumentSignature` (TTE)

**Workflow / Approval**
- `WorkflowDefinition`, `WorkflowDefinitionStep`, `WorkflowInstance`, `WorkflowApproval`
- `StandingInstruction`

**Modul Jasa / PNBP (Mitra)**
- `LayananJasa`, `LayananJasaTarif`, `JenisLayanan`, `KategoriLayanan`, `ItemTarifLayanan`
- `MitraJasa`, `KontrakMitraJasa`, `MitraJasaKonsesi`, `MitraJasaPjp2u`
- `MitraJasaPenjualan` / `MitraJasaPenjualanDetail`
- `TagihanJasa`, `TagihanJasaDetail`, `TagihanJasaPaymentProof`
- `PnbpUmumItem`, `PnbpUmumRealisasi`
- `LaporanUtilitas` (listrik/air), `PemakaianGarbarata`, `PengajuanPenagihanGarbarata`
- `PermohonanNonSchedule`, `JadwalPenerbangan`, `LogPerubahanTarifPjp2u`

**Pembukuan BLU**
- `BukuKasUmum`, `TransaksiPembukuan`, `PembukuanSetup`, `PembukuanSaldoAwal`
- `ImportMutasiBank`, `DetailMutasiBank`, `RekonsiliasiBank`, `RekonsiliasiBankLog`
- `LaporanPengesahanBlu`

**Integrasi & Notifikasi**
- `IntegrationSetting`, `IntegrationLog`, `PaymentTransaction`, `WhatsappNotificationLog`
- `ShortLink`

### 2.4 Controller — `app/Http/Controllers`
Dikelompokkan per area fungsional (±115 controller):

| Area | Controller utama |
|---|---|
| **Auth** | `Auth/LoginController`, `RegisterController`, `ForgotPasswordController`, `ResetPasswordController`, `VerificationController`, `ConfirmPasswordController` |
| **Administrasi (Super Admin)** | `Admin/UserManagementController`, `Admin/RoleManagementController`, `Admin/MasterPegawaiController`, `Admin/NotifikasiWaController` |
| **Dashboard** | `DashboardController`, `HomeController`, `BendaharaPenerimaanDashboardController`, `BendaharaPengeluaranDashboardController`, `AdminJasaDashboardController`, `SuperAdminJasaDashboardController` |
| **Master Data** | `DipaController`, `CoaController`, `RekeningBankController`, `MasterTarifPajakController`, `MasterUangHarianPerjaldinController`, `SupplierController`, `MasterLayananJasaController`, `TarifLayananController` |
| **Kontrak** | `ContractController`, `ContractAddendumController`, `ContractTermController` |
| **Tagihan (umum)** | `TagihanController`, `TagihanProsesController`, `TagihanTteController` |
| **Verifikasi Tagihan** | `TagihanKontrakVerifikasiController`, `TagihanHonorariumVerifikasiController`, `TagihanJasaVerifikasiController`, `PpkHonorariumVerifikasiController`, `BendaharaHonorariumVerifikasiController` |
| **Honorarium** | `HonorariumController` |
| **Perjalanan Dinas (Perjaldin)** | `PerjaldinController`, `PerjaldinVerifikasiController`, `PerjaldinWorkflowController`, `PerjaldinBluController` |
| **Pencairan** | `SppController`, `SpmController`, `NpiController`, `DocumentController`, `DocumentNumberController`, `SuratNumberController`, `StandingInstructionKpaController`, `KpaApprovalController` |
| **Jasa / PNBP** | `AdminJasaController`, `AdminJasaTagihanController`, `AdminJasaLayananController`, `AdminJasaUtilitasController`, `TagihanJasaController`, `NomorTagihanJasaController`, `MitraJasaController`, `MitraJasaKonsesiController`, `MitraJasaPenjualanController`, `MitraJasaPjp2uController`, `MitraLayananController`, `MitraAccountController`, `KontrakMitraJasaController`, `MonitoringPelaporanController` |
| **AMC / Garbarata** | `PemakaianGarbarataController`, `PengajuanPenagihanGarbarataController`, `PermohonanNonScheduleController` |
| **Utilitas (Listrik/Air)** | `UtilitasController` |
| **Portal Mitra** | `MitraPortalController` |
| **Pembukuan BLU** | `BukuKasUmumController`, `BkuPenerimaanController`, `BkuPenerimaanManualController`, `BkuPengeluaranController`, `TransaksiPembukuanController`, `BukuPembantuBankController`, `BukuPembantuBendaharaController`, `BukuPembantuBungaController`, `BukuPembantuPajakController`, `BukuPembantuPartisiController`, `BukuPengesahanBelanjaController`, `BukuPengesahanPendapatanController`, `KlasifikasiPenerimaanController`, `RealisasiPenerimaanController`, `PembukuanSetupController`, `PengecekanPembayaranPiutangController` |
| **Pajak** | `PenyetoranPajakController`, `PenyetoranPajakHonorController`, `PenyetoranPajakKontrakController` |
| **PNBP** | `ManajemenPnbpController` |
| **Laporan** | `ReportController`, `SuperAdminJasaLaporanController`, `LogPerubahanTarifPjp2uController` |
| **Integrasi** | `JasaIntegrationSettingController`, `BtnPaymentCallbackController` |
| **Notifikasi** | `NotificationController` |
| **Publik (signed URL/TTE)** | `PublicSppSignatureController`, `PublicDocumentSignatureController`, `PublicContractSignatureController`, `PublicTagihanSignatureController`, `PublicMagicLinkSignatureController`, `PublicContractVendorUploadController`, `PublicTagihanJasaController`, `PublicTagihanJasaVerificationController`, `PublicTagihanActivityController`, `ShortLinkController` |
| **Profil** | `ProfileController` |

### 2.5 Service Layer — `app/Services`
Inti logika bisnis. Disusun per domain:

**Pembukuan (`Services/Pembukuan/`)**
- `PembukuanService`, `PostingPembukuanService`, `PostingPenerimaanService`, `BukuPembantuService`
- `RealisasiPenerimaanService`, `AkunPendapatanClassifier`, `CmsKoranImportService` (impor rekening koran)
- `DokumenPembukuanService`, `PiutangAgingService`, `PiutangRekonsiliasiService`, `PiutangSyncService`

**Tagihan & Workflow**
- `BkuPostingService`, `BudgetRealizationService`
- `TagihanJasaCalculationService`, `TagihanJasaPublishService`
- `TagihanKontrakWorkflowService`, `TagihanHonorariumWorkflowService`, `TagihanReadyForSppNotificationService`
- `WorkflowService`, `WorkflowWaNotifier`, `PerjaldinWorkflowService`, `SppPerjaldinWorkflowService`, `PerjaldinKomponenService`

**Dokumen & Penomoran**
- `DocumentNumberService`, `DocumentNumberingService`, `DocumentArchiveService`, `DokumenChainService`

**Jasa / Mitra**
- `AdminJasaDashboardService`, `AdminJasaLayananService`, `JasaAccessService`
- `MitraAccountService`, `MitraLayananService`, `MitraJasaKonsesiService`
- `TarifLayananService`, `TarifLayananImportService`, `Pjp2uTariffLogService`

**Integrasi & Notifikasi**
- `WhatsappService`, `EmailNotificationService`, `BtnVirtualAccountService`

**Lainnya**
- `Admin/UserProvisioningService`, `Reports/ReportAggregationService`

### 2.6 Enums — `app/Enums`
`JenisRekening`, `JenisTransaksiPenerimaan`, `KategoriMutasiBank`, `KodeBuku`,
`MekanismePembayaran`, `PeranBuku`.

### 2.7 Support & Helpers
- **`app/Support/`** — penolong dokumen & TTE: `DocumentTte`, `ContractDocumentTte`,
  `ContractBaTte`, `TagihanDocumentTte`, `TagihanDokumenPendukung`, `PaymentPdfReference`,
  `DipaBudgetOptionService`.
- **`app/Helpers/TerbilangHelper.php`** — konversi angka ke teks "terbilang" (autoload global).

### 2.8 Middleware
- `auth` — wajib login.
- `account.active` (`EnsureAccountIsActive`) — blokir akun non-aktif/expired (PLT/PLH).
- `role:*` (Spatie) — otorisasi berbasis role pada hampir seluruh grup route.
- `signed` — validasi signed URL untuk endpoint publik (TTE, tagihan jasa, QR).

Registrasi alias middleware: `bootstrap/app.php`.

### 2.9 Console Commands & Scheduler (`app/Console/Commands` + `routes/console.php`)
| Command | Fungsi | Jadwal |
|---|---|---|
| `wa:reminder-due-date` (`SendDueDateReminderCommand`) | Reminder WA tagihan mendekati jatuh tempo | Hourly |
| `users:disable-expired-temporary` (`DisableExpiredTemporaryUsersCommand`) | Nonaktifkan akun PLT/PLH kedaluwarsa | Harian 00:05 |
| `jasa:reminder-pelaporan` (`SendReportReminderCommand`) | Ingatkan mitra belum lapor konsesi/PAX | Bulanan tgl 3, 08:00 |
| `ImportTarifLayananCommand` | Impor tarif layanan dari Excel | Manual |
| `RecomputeSaldoPembukuan` | Hitung ulang saldo BKU | Manual |
| `ExportAlurTagihanJasaPdf` / `ExportAlurTagihanJasaMitraPdf` | Generate dokumentasi alur PDF | Manual |

---

## 3. Arsitektur Front-End

### 3.1 Build & Aset
- **Vite** (`vite.config.js`) sebagai bundler; entry SCSS/JS dikompilasi ke `public/build`.
- **Bootstrap 5** + **Sass** untuk styling; **Axios** untuk AJAX (polling notifikasi, lookup).
- Template admin **Maxton** (aset CSS/JS di `public/`, preview di `resources/views/template_maxton/`).
- Script: `npm run dev` (HMR) / `npm run build` (produksi).

### 3.2 Layout & Komponen Global — `resources/views/layouts`
- `app.blade.php` — layout utama internal (authenticated).
- `guest.blade.php` — layout halaman tamu (login/publik).
- `sidebar.blade.php`, `sidebar-app.blade.php`, `sidebar-template.blade.php` — navigasi (menu per role).
- `topbar.blade.php` — bar atas + notifikasi.
- `head-css.blade.php`, `theme-head.blade.php`, `app-scripts.blade.php`, `common-scripts.blade.php`,
  `footer.blade.php`, `extra.blade.php`, `theme-customizer-script.blade.php`.
- `_partials/sky-alerts.blade.php` — alert/flash global.
- **Komponen Blade:** `components/page-title.blade.php`, `partials/modern-css.blade.php`,
  `partials/dipa-item-grouped-select.blade.php`.

### 3.3 Pemetaan View per Modul (`resources/views/`)
| Folder view | Modul |
|---|---|
| `auth/` | Login, register, reset/forgot password, verifikasi |
| `dashboard/`, `dashboards/` | Dashboard per role (kpa, ppk, ppspm, ppabp, plt_plh, mitra, amc, koordinator_keuangan, bendahara_*, operator_perjaldin, super_admin, pejabat_pengadaan) |
| `admin/` | Manajemen pegawai, users, roles, notifikasi WA |
| `dipas/`, `coas/` | DIPA & bagan akun (CoA) + revisi |
| `rekening_bank/`, `master-pajak/`, `master-uang-harian-perjaldin/`, `suppliers/` | Master data |
| `contracts/`, `pdf/kontrak/` | Kontrak, addendum, SPK/SPMK, BA (BAP/BAPP/BAST) |
| `tagihan/`, `tagihans/`, `proses_tagihan/` | Tagihan kontrak, proses & verifikasi PPK |
| `honorarium/` | Honorarium + verifikasi (PPK/Bendahara) + PDF nominatif |
| `perjaldins/`, `perjaldin_blu/`, `verifikasi_perjaldin/` | Perjalanan dinas + workflow + PDF |
| `tagihan_jasa/`, `tagihan_honorarium_verifikasi/`, `tagihan_kontrak_verifikasi/` | Tagihan jasa & layar verifikasi |
| `admin_jasa/`, `super_admin_jasa/`, `koordinator_jasa/` | Pengelolaan jasa, mitra, konsesi, PJP2U, laporan, integrasi |
| `jasa/`, `jasa_assignments/` | Monitoring pelaporan, assignment layanan |
| `manajemen_pnbp/` | Manajemen PNBP (Bendahara Penerimaan) |
| `pembukuan/` | BKU, buku pembantu (bank/bendahara/bunga/pajak/partisi), penerimaan, pengeluaran, klasifikasi, realisasi, pengesahan, piutang, setup |
| `penyetoran_pajak/`, `penyetoran_pajak_honor/`, `penyetoran_pajak_kontrak/` | Penyetoran pajak + bukti potong |
| `pemakaian_garbarata/`, `pengajuan_penagihan_garbarata/`, `permohonan_non_schedule/` | AMC / Garbarata |
| `utilitas/` | Portal Admin Listrik & Air |
| `spps/`, `spms/`, `npis/`, `sp2ds/`, `document_numbers/`, `surat_numbers/`, `standing_instruction/` | Dokumen pencairan & penomoran |
| `reports/` | Laporan BKU |
| `public/` | Halaman publik signed (TTE, magic link, vendor upload, tagihan jasa) |
| `profile/` | Profil & ganti password |
| `errors/` | 401/403/404/419/429/500/503 |
| `template_maxton/` | Showcase/preview komponen template (tidak dipakai produksi) |

Pola umum tiap modul CRUD: `index`, `create`, `edit`, `show`, partial `_form`/`_table`,
dan `pdf` (cetak via dompdf).

---

## 4. Struktur Database

Migrasi v2 mengelompokkan skema ke beberapa file besar bertema (`*_v2.php`), kemudian diikuti
migrasi inkremental. Kelompok tabel utama:

**Master Data Inti** (`create_master_data_inti_v2`)
`users`, `master_coas`, `master_dipas`, `dipa_revisions`, `dipa_revision_items`,
`master_pihak`, `master_pegawai`, `rekening_bank`, `master_tarif_pajak`.

**Workflow & Approval** (`create_workflow_approval_v2`)
`workflow_definitions`, `workflow_definition_steps`, `workflow_instances`, `workflow_approvals`.

**Manajemen Kontrak** (`create_manajemen_kontrak_v2`)
`kontrak_pengadaan`, `kontrak_termin`, `jaminan_kontrak`, `kontrak_addendum`.

**Transaksi & Tagihan** (`create_transaksi_tagihan_v2`)
`transaksi_penerimaan`, `tagihan`, `potongan_tagihan`, `log_status_dokumen`,
`detail_perjaldin`, `detail_kontrak`, `detail_honorarium`, `realisasi_anggaran`.

**Dokumen Pencairan & Arsip** (`create_dokumen_pencairan_arsip_v2`)
`dokumen_spp`, `dokumen_spm`, `dokumen_npi`, `arsip_dokumen`.

**BKU, Mutasi, Rekonsiliasi, Pelaporan** (`create_bku_mutasi_rekonsiliasi_pelaporan_v2`)
`buku_kas_umum`, `import_mutasi_bank`, `detail_mutasi_bank`, `rekonsiliasi_bank`,
`rekonsiliasi_bank_logs`, `laporan_pengesahan_blu`.

**Penomoran Dokumen**
`document_number_sequences`, `document_numbers`, `document_signatures`.

**Modul Jasa / PNBP**
`layanan_jasas`, `layanan_jasa_tarifs`, `master_jenis_layanan`, `master_kategori_layanan`,
`master_item_tarif_layanan`, `mitra_jasa`, `kontrak_mitra_jasa`, `mitra_jasa_layanan`,
`mitra_layanan_jasa`, `admin_jasa_layanan`, `kontrak_mitra_jasa_layanan`,
`mitra_jasa_konsesi`, `mitra_jasa_penjualan`, `mitra_jasa_penjualan_details`,
`tagihan_jasas`, `tagihan_jasa_details`, `tagihan_jasa_payment_proofs`,
`pnbp_umum_items`, `pnbp_umum_realisasis`, `laporan_utilitas`,
`permohonan_non_schedule`, `pemakaian_garbarata`, `pengajuan_penagihan_garbarata`,
`jadwal_penerbangan`.

**Pembukuan (SILABI)**
`kode_transaksi`, `akun_pendapatan`, `pembukuan_setup`, `pembukuan_saldo_awal`,
`transaksi_pembukuan`.

**Perjaldin**
`master_uang_harian_perjaldins`, `tagihan_perjaldin_komponen`.

**Standing Instruction & Integrasi**
`standing_instructions`, `integration_settings`, `integration_logs`,
`payment_transactions`, `whatsapp_notification_logs`, `short_links`.

**Infrastruktur Laravel**
`jobs`/`job_batches`/`failed_jobs` (queue), `cache`/`cache_locks`, `sessions`,
`password_reset_tokens`, `notifications`, dan tabel Spatie (`roles`, `permissions`,
`model_has_roles`, dll. via `create_permission_tables`).

**Seeder utama** (`database/seeders`): `DatabaseSeeder` mengorkestrasi
`RoleAndPermissionSeeder`, `UserAccountSeeder`, `MasterPegawaiSeeder`, `MasterCoaSeeder`,
`MasterDipaSeeder`, `MasterPihakSeeder`, `MasterTarifPajakSeeder`, `RekeningBankDefaultSeeder`,
`WorkflowDefinitionSeeder`, `LayananJasaSeeder` & turunannya, `KodeTransaksiSeeder`,
`AkunPendapatanSeeder`, `PembukuanSetupSeeder`, dll.

---

## 5. Modul Sistem (Functional Modules)

> Setiap modul = kombinasi Controller + Service + View + Tabel + Role.

### M1. Autentikasi & Profil
Login/register/reset password, verifikasi, ganti password, profil universal.
Akun non-aktif/expired diblokir middleware `account.active`.

### M2. Administrasi & RBAC (Super Admin)
Manajemen User (CRUD, reset password, sync role), Master Pegawai, Roles (read-only),
Manajemen Notifikasi WhatsApp (template + test + run reminder). Prefix `/admin`.

### M3. Master Data
DIPA + revisi & item, Bagan Akun (CoA), Rekening Bank (+saldo awal), Tarif Pajak,
Uang Harian Perjaldin, Supplier/Vendor, Penomoran Dokumen & Nomor Surat KU.

### M4. Manajemen Kontrak & Pengadaan
Kontrak (CRUD + submit/approve/reject), Addendum, Termin, Jaminan. Generate SPK/SPMK/
Ringkasan Kontrak (PDF + TTE), upload dokumen final vendor (signed URL).
Role: Pejabat Pengadaan, PPK, Super Admin.

### M5. Tagihan Kontrak
Pembuatan tagihan dari kontrak, proses CoA & pajak, pengajuan SPP/SPM/NPI, upload bukti
transfer, batalkan rantai. Verifikasi berjenjang multi-role
(PPK → Koordinator Keuangan → Bendahara → Kasubbag → PPSPM).

### M6. Honorarium
Pembuatan honorarium (PPABP), upload dokumen, submit verifikasi.
Verifikasi paralel: PPK, Koordinator Keuangan, Bendahara Pengeluaran.
PDF rekap & nominatif + bukti potong PPh21.

### M7. Perjalanan Dinas (Perjaldin)
Input perjaldin (Operator Perjaldin) + komponen biaya, workflow approval berjenjang:
PPK → Bendahara Pengeluaran → Kasubbag → Koordinator Keuangan → PPSPM → Bendahara Penerimaan.
PDF: nominatif, daftar nominatif pembayaran, lampiran, perincian. TTE via QR.

### M8. Pencairan Dana (SPP → SPM → NPI → SP2D)
Generasi dokumen berurutan dengan penomoran otomatis & TTE/QR.
Standing Instruction (PPK→KPA), KPA Approval via WhatsApp magic link.
Cetak PDF SPP/SPM/NPI/SP2D.

### M9. Jasa / PNBP (Inti Bisnis Mitra)
- **Master Layanan Jasa** — pohon layanan berjenjang (PJP2U, konsesi, listrik, air,
  garbarata, sewa, dll), tarif berlaku per periode, riwayat perubahan tarif PJP2U.
- **Mitra Jasa** — CRUD mitra, akun login mitra, kontrak mitra, assignment layanan,
  konsesi, hak PJP2U.
- **Tagihan Jasa** — pembuatan (Admin Jasa/Konsesi), kalkulasi otomatis
  (`TagihanJasaCalculationService`), publish ke mitra (WA + signed URL), verifikasi
  berjenjang (Koord. Jasa → Kasi PK → Kasubbag → KPA), bukti pembayaran, kuitansi/invoice PDF,
  denda jatuh tempo.
- **Pelaporan Mitra** — laporan penjualan konsesi & PAX PJP2U, monitoring & reminder.
- **Nomor Tagihan Jasa** — set nomor urut awal & monitoring (Super Admin Jasa).

### M10. AMC / Garbarata
Permohonan non-schedule, pencatatan pemakaian garbarata (+jadwal penerbangan),
rekap harian, pengajuan penagihan garbarata → jadi sumber tagihan jasa.

### M11. Utilitas (Listrik & Air)
Portal Admin Listrik / Admin Air: input laporan kWh/meteran (stan awal/akhir),
submit → diverifikasi Admin Jasa → dibuatkan tagihan utilitas.

### M12. Pembukuan BLU (SILABI)
- **BKU** Penerimaan & Pengeluaran (partisi per peran), input transaksi manual (jurnal),
  setup identitas satker + saldo awal.
- **Buku Pembantu**: Bank (per rekening), Bendahara, Bunga, Pajak, Partisi (Kas Tunai/UP/BPP/
  Perjadin/Pajak LS/Pengesahan/Pengembalian).
- **Penerimaan**: klasifikasi rekening koran → akun pendapatan → BKU; impor CMS koran;
  rekap realisasi; pengecekan pembayaran piutang (aging & rekonsiliasi).
- **Pengesahan**: Belanja (Bendahara Pengeluaran) & Pendapatan (Bendahara Penerimaan) —
  generate `laporan_pengesahan_blu` per periode + PDF.
- Export PDF & Excel di tiap buku.

### M13. Penyetoran Pajak
Pajak umum, pajak honor (+bukti potong/bupot), pajak kontrak: billing code & NTPN,
cetak bukti setor. Role: Bendahara Pengeluaran/Penerimaan.

### M14. Manajemen PNBP
Monitoring & rekap PNBP (Bendahara Penerimaan), edit realisasi PNBP umum, export.

### M15. Laporan & Monitoring
Laporan BKU; Laporan Super Admin Jasa (rekap tagihan/layanan/terima-setor/pembayaran/
piutang/performa mitra/log tarif PJP2U) — export PDF/Excel; Monitoring pelaporan.

### M16. Portal Mitra (eksternal)
Dashboard mitra, profil, layanan aktif, lapor penjualan konsesi & PAX PJP2U,
lihat & bayar tagihan jasa (upload bukti), riwayat pembayaran, tagihan jatuh tempo,
unduh invoice/kuitansi/surat pengantar/kontrak.

### M17. Workflow Engine (lintas modul)
Mesin approval generik: `WorkflowDefinition`/`Step`/`Instance`/`Approval`.
Mendukung approval berjenjang & paralel, status (draft/waiting/approved/revisi/rejected),
target revisi, notifikasi WA per langkah (`WorkflowWaNotifier`). Dipakai Kontrak,
Tagihan, Honorarium, Perjaldin, SPP.

### M18. Integrasi & Notifikasi
- **WhatsApp** (`WhatsappService`) — kirim tagihan, reminder, magic link approval/TTE.
- **Email** (`EmailNotificationService`).
- **BTN Virtual Account** (`BtnVirtualAccountService` + `BtnPaymentCallbackController`) —
  pembayaran tagihan jasa via VA, callback tanpa CSRF.
- **TTE / QR Code** — tanda tangan elektronik dokumen via signed URL & QR verifikasi.
- **Short Link** — pemendek URL untuk pesan WhatsApp.
- Pengaturan integrasi: `IntegrationSetting` + test endpoint (Super Admin).

---

## 6. Peran (Roles) & Hak Akses

22 role (Spatie Permission, guard `web`) — didefinisikan di `RoleAndPermissionSeeder`:

**Internal Keuangan/Pejabat**
`Super Admin`, `KPA`, `PLT/PLH`, `Kepala Subbagian Keuangan dan Tata Usaha`,
`Kepala Seksi Pelayanan dan Kerjasama`, `PPK`, `PPSPM`, `Bendahara Pengeluaran`,
`Bendahara Penerimaan`, `Pejabat Pengadaan`, `Operator BLU`, `PPABP`,
`Operator Perjaldin`, `Koordinator Keuangan`.

**Pengelola Jasa**
`Super Admin Jasa`, `Koordinator Jasa`, `Admin Jasa`, `Admin Listrik`, `Admin Air`, `AMC`.

**Eksternal**
`Mitra`, `Mitra Jasa`.

**Pola otorisasi route:**
- Root `/` → mitra diarahkan ke `mitra.dashboard`, lainnya ke `dashboard`.
- Grup besar dilindungi `middleware(['auth','account.active'])` lalu `role:...`.
- Banyak modul punya beberapa lapis role: pembuat (create/store) terpisah dari
  verifikator (read/approve) dan pemantau read-only (KPA/PLT/PLH/Kasi/Kasubbag).

---

## 7. Endpoint Publik (tanpa login, `signed`)
- `/i/{slug}` — resolver short link.
- `/p/tagihan-jasa/{id}` (+`/pdf`,`/verify`,`/surat-pengantar-tte`) — tagihan jasa untuk mitra via WA.
- `/tte/...` — TTE dokumen (SPP/SPM/NPI/SP2D, kontrak SPK/SPMK/ringkasan, tagihan nominatif/rekap).
- `/public/tte/sign/{token}` — magic link TTE Berita Acara (BAP/BAPP/BAST).
- `/p/kontrak/{id}/vendor-upload` — upload dokumen kontrak final oleh vendor.
- `/p/kpa-approval/tagihan/{id}` — persetujuan KPA via WA.
- `/aktivitas-tagihan/{id}` — aktivitas tagihan dari QR di PDF SPP.
- `/integrations/btn/virtual-account/callback` — callback pembayaran BTN VA (POST, tanpa CSRF).

---

## 8. Diagram Alur Tersedia (`docs/`)
- `alur-tagihan-jasa/` — lifecycle, workflow verifikasi, sequence end-to-end (.mmd/.png/.pdf),
  varian per jenis (Air/Listrik/Konsesi/PJP2U).
- `diagrams/` — flow tagihan kontrak/honorarium/perjaldin, use-case SIKEREN (.drawio/.md/.pdf).
- `alur-denda-pjp2u.md`, `proses-revisi-tagihan.md`, `deployment-guide.md`, `audit-plan.md`,
  `audit-report.md`, dan brief modul jasa per peran.

---

## 9. Cara Menjalankan (ringkas)
```bash
composer install
npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm run dev          # atau: npm run build
php artisan serve
php artisan schedule:work   # untuk reminder WA & job terjadwal
php artisan queue:work      # untuk antrian (notifikasi/email)
```

---

*Dokumen ini dihasilkan dari pemetaan kode aktual (routes, controllers, models, services,
migrations, views) per Juni 2026. Untuk detail per modul, telusuri controller & service
yang disebut di tabel masing-masing.*
