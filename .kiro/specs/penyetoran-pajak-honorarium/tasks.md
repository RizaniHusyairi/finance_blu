# Implementation Plan: Penyetoran Pajak Honorarium (PPh 21)

## Overview

Pendekatan implementasi mengikuti pola `PenyetoranPajakKontrakController` yang sudah ada. Pekerjaan dibagi menjadi: (1) penambahan kolom `bupot_status`/`nomor_bupot` pada `detail_honorarium`, (2) modifikasi `SppController::storeHonor` agar membentuk baris `potongan_tagihan` PPh 21 dengan agregasi & guard revisi, (3) pembuatan `PenyetoranPajakHonorController` baru beserta route, view, dan helper, (4) integrasi BKU posting dan finalisasi e-Bupot 21, (5) penambahan panel dashboard, dan (6) property-based tests menggunakan PHPUnit + Eris untuk semua properti universal pada design. Setiap tahap menambahkan tes properti untuk menjaga invariant yang relevan tetap terpenuhi.

## Tasks

- [ ] 1. Skema data dan factory pendukung
  - [ ] 1.1 Buat migration `add_bupot_columns_to_detail_honorarium_table`
    - Tambahkan kolom `bupot_status` string(20) default `'DRAFT'` dan `nomor_bupot` string(50) nullable pada tabel `detail_honorarium`
    - Tambahkan index pada `bupot_status` dan unique pada `nomor_bupot`
    - Sediakan logika `down()` yang menghapus kedua kolom
    - _Requirements: 8.3, 8.5, 8.8_

  - [ ] 1.2 Perbarui model `DetailHonorarium`
    - Tambahkan `bupot_status` dan `nomor_bupot` ke `$fillable`
    - Pastikan relasi `tagihan()` dan akses ke `MasterPegawai`/`MasterPersonelEksternal` mengembalikan field `nama_personel`, `nrp_nip`, `jabatan`
    - _Requirements: 8.3, 8.7, 15.1_

  - [ ] 1.3 Tambah factory `MasterTarifPajakFactory` untuk PPh 21
    - Sediakan state `pph21Aktif()` yang membentuk baris dengan `kode_pajak = 'PPH21-TER'`, `status_aktif = true`, `persentase` valid, `jenis_pajak = 'PPh Pasal 21'`
    - Pastikan factory dapat dipakai pada test PBT
    - _Requirements: 1.8_

- [ ] 2. Modifikasi `SppController::storeHonor` (pembentukan PotonganTagihan PPh 21)
  - [ ] 2.1 Implementasikan agregasi PPh 21 honorarium dalam `storeHonor`
    - Bungkus logika dalam `DB::transaction`
    - Hapus baris `potongan_tagihan` PAJAK lama pada tagihan honor dan arsipnya
    - Resolve `MasterTarifPajak` aktif (`PPH21-TER`, fallback `PPh Pasal 21` aktif terbaru); lempar `ValidationException` bila tidak ada
    - Group `detail_honorarium.pph` per `pajak_id` resolver, hitung `dpp = Σ nilai_honor`, `nominal_potongan = Σ pph`
    - Bentuk satu baris `PotonganTagihan` per pajak dengan `jenis_potongan = 'PAJAK'`, `deskripsi = "PPh 21 honorarium"`, `persentase_tarif_snapshot`, `nama_pajak_snapshot`
    - Validasi `dpp >= 0`, `persentase_tarif_snapshot > 0`, `nominal_potongan > 0` per baris
    - Validasi `Σ nominal_potongan == Σ detail_honorarium.pph`; lempar `ValidationException` "Selisih PPh 21 honorarium tidak konsisten." bila tidak sama
    - Sinkronisasi `tagihan.total_potongan` dan `tagihan.total_netto = max(0, total_bruto - total_potongan)`
    - Skip pembentukan baris bila `Σ pph = 0`
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 10.1_

  - [ ] 2.2 Implementasikan guard revisi pajak setelah billing
    - Sebelum menghapus baris pajak lama, cek apakah salah satu memiliki `kode_billing` atau `ntpn`
    - Bila ada, lempar `ValidationException` "Revisi pajak honorarium tidak diperbolehkan setelah billing terbentuk." sehingga transaksi rollback total
    - _Requirements: 10.2_

  - [ ] 2.3 Catat audit `CREATE_PAJAK_HONOR` saat baris pajak honor pertama kali dibentuk
    - Buat helper `recordAuditLog($dokumen, $aksi, $statusBaru)` yang melempar `RuntimeException` bila `auth()->id()`, `role_saat_itu`, atau `ip_address` tidak tersedia
    - Catat satu baris `LogStatusDokumen` per pembentukan baris pajak honor dengan `dokumen_type = PotonganTagihan::class`
    - _Requirements: 13.1, 13.5, 13.6_

  - [ ]* 2.4 Property test pembentukan & rollback selisih (`Tests\Property\StoreHonorAggregationTest`)
    - **Property 1: Pembentukan baris PPh 21 honor yang konsisten**
    - **Property 2: Selisih pph membatalkan transaksi penyimpanan**
    - **Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.8, 10.1**

  - [ ]* 2.5 Property test guard revisi (`Tests\Property\StoreHonorRevisionGuardTest`)
    - **Property 3: Revisi pajak setelah billing menolak perubahan dengan rollback total**
    - **Validates: Requirements 10.2**

  - [ ]* 2.6 Property test soft-delete cascade (`Tests\Property\TagihanSoftDeleteCascadeTest`)
    - **Property 15: Soft-delete tagihan honor merambat ke baris pajak**
    - **Validates: Requirements 10.3**

- [ ] 3. Routing & kontroler dasar `PenyetoranPajakHonorController`
  - [ ] 3.1 Tambahkan grup route `pajak-potongan.honor.*` di `routes/web.php`
    - Tempatkan route honor-spesifik sebelum route umum `/pajak-potongan/{potongan}/...` agar matching benar
    - Bungkus dalam grup middleware `role:Super Admin|Bendahara Pengeluaran`
    - Definisikan route `index`, `detail`, `billing`, `ntpn`, `cetak`, `bupot` sesuai design
    - _Requirements: 2.1, 3.1, 4.1, 4.9, 5.1, 5.11, 7.1, 8.1, 8.10, 12.1, 12.2_

  - [ ] 3.2 Buat skeleton `app/Http/Controllers/PenyetoranPajakHonorController.php`
    - Definisikan method publik `index`, `show`, `storeBilling`, `storeNtpn`, `cetak`, `bupot` (sementara return placeholder)
    - Tambahkan helper privat `findHonorPajakPotongan($id)` yang `findOrFail` dengan filter `tipe_tagihan = 'HONORARIUM'` dan `jenis_potongan = 'PAJAK'`, dan abort 404 bila relasi tagihan/SPP/SP2D tidak konsisten
    - Tambahkan helper `penyetoranReadinessError($potongan)` yang mengecek `tagihan.status = SELESAI` dan `sp2d.status = EXECUTED`
    - Tambahkan helper `resolveSp2d($potongan)` (`tagihan->spps->latest->spm->npi->sp2d`)
    - _Requirements: 3.4, 3.5, 3.6_

  - [ ]* 3.3 Unit test pendaftaran route (`Tests\Unit\HonorRouteRegistrationTest`)
    - Verifikasi semua route `pajak-potongan.honor.*` terdaftar dengan controller dan middleware `role:Super Admin|Bendahara Pengeluaran`
    - _Requirements: 12.2, 2.1, 3.1, 4.1, 5.1, 7.1, 8.1_

  - [ ]* 3.4 Property test otorisasi peran (`Tests\Property\HonorRoleAuthorizationTest`)
    - **Property 13: Otorisasi peran pada seluruh route honor**
    - **Validates: Requirements 4.9, 5.11, 7.3, 8.10, 12.1, 12.2**

- [ ] 4. Halaman index (antrian penyetoran)
  - [ ] 4.1 Implementasikan `PenyetoranPajakHonorController::index`
    - Build query dasar `PotonganTagihan` dengan filter `jenis_potongan = 'PAJAK'`, tagihan `tipe_tagihan = 'HONORARIUM'`, `status = 'SELESAI'`, dan SP2D `status = STATUS_EXECUTED`
    - Eager load `tagihan.detailHonorarium`, `tagihan.spps.spm.npi.sp2d`, `pajak`, `akunPotongan`
    - Implementasikan filter pencarian (`kode_billing`, `ntpn`, `nama_pajak_snapshot`, `nomor_tagihan`, dan nama personel pada `detail_honorarium`)
    - Implementasikan filter status (`belum_billing`, `sudah_billing`, `sudah_setor`)
    - Hitung summary per kategori dari koleksi tanpa filter status
    - Order `latest()`
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7_

  - [ ] 4.2 Buat view `resources/views/penyetoran_pajak_honor/index.blade.php`
    - Layout daftar dengan kartu summary, search bar, filter status
    - Tabel berisi nomor tagihan, jenis pajak, nominal, status setor, action ke detail
    - _Requirements: 2.1, 2.6_

  - [ ]* 4.3 Property test query/index (`Tests\Property\PenyetoranPajakHonorIndexTest`)
    - **Property 4: Filter & ordering antrian penyetoran pajak honor**
    - **Property 5: Label & partisi status setor deterministik**
    - **Property 6: Pencarian honor mencakup kolom yang benar**
    - **Validates: Requirements 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 3.8, 9.1, 9.2**

- [ ] 5. Halaman detail (workspace penyetoran)
  - [ ] 5.1 Implementasikan `PenyetoranPajakHonorController::show`
    - Panggil `findHonorPajakPotongan($id)`
    - Muat `tagihan.detailHonorarium`, SPP/SPM/NPI/SP2D terbaru, dan `arsipDokumen`
    - Hitung flag `isReadyForPenyetoran`, `canInputBilling`, `canInputNtpn`, `statusSetor`, dan `selisihPph`
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10, 15.1, 15.2_

  - [ ] 5.2 Buat view `resources/views/penyetoran_pajak_honor/detail.blade.php`
    - Tampilkan informasi tagihan, daftar `detail_honorarium` (nama personel, NRP/NIP, jabatan, nilai_honor, pph) dengan total kontrol DPP & PPh
    - Tampilkan informasi pajak, kode billing, NTPN, dan arsip dokumen
    - Tampilkan label status setor
    - Tampilkan peringatan visual bila `selisihPph != 0`
    - Render form input ID Billing dan NTPN dengan toggle aktif/disable mengikuti `canInputBilling`/`canInputNtpn`
    - _Requirements: 3.2, 3.3, 3.7, 3.8, 3.9, 3.10, 15.1, 15.2, 15.3_

  - [ ]* 5.3 Property test detail/show (`Tests\Property\PenyetoranPajakHonorDetailTest`)
    - **Property 7: Toggle tombol input dan validasi ruang lingkup**
    - **Property 18: Konten render detail penyetoran lengkap**
    - **Validates: Requirements 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.9, 3.10, 15.1, 15.2, 15.3**

- [ ] 6. Checkpoint - Verifikasi pembentukan pajak & antrian penyetoran
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 7. Input ID Billing dan NTPN
  - [ ] 7.1 Implementasikan `PenyetoranPajakHonorController::storeBilling`
    - Validasi `kode_billing: required|string|max:50`
    - `file_billing`: `required|file|mimes:pdf,jpg,jpeg,png|max:5120` jika belum ada arsip; `nullable` jika sudah ada
    - Tolak bila `ntpn` sudah ada, tagihan belum SELESAI, atau SP2D belum EXECUTED (`penyetoranReadinessError`)
    - Dalam transaksi: simpan arsip baru (`jenis_dokumen = 'KODE_BILLING'`, disk `public`, path `arsip/pajak-honor`) dulu, lalu hapus arsip lama
    - Update `kode_billing`
    - Catat `LogStatusDokumen` (`aksi = 'INPUT_KODE_BILLING'`, `status_baru = 'SUDAH_BILLING'`, `role_saat_itu = 'Bendahara Pengeluaran'`, `ip_address`)
    - Flash message `'Kode Billing berhasil disimpan.'`
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 13.2_

  - [ ] 7.2 Implementasikan helper `postBkuIfAllPajakSettled($potongan)`
    - Cek apakah masih ada `PotonganTagihan` PAJAK dengan `nominal_potongan > 0` dan `ntpn` null pada tagihan yang sama
    - Bila tidak ada, panggil `BkuPostingService::postTagihanPengeluaran` dengan `nominal = total_bruto`, `sp2d = resolveSp2d($potongan)`, dan catatan menyebut "honorarium" + "SP2D" + "pajak honor lengkap"
    - Pastikan idempoten: cek duplikasi pasangan (`referensi_pengeluaran_id`, `nomor_bukti = nomor_sp2d`) sebelum posting
    - Return boolean `true` bila baris BKU dibuat di pemanggilan ini, `false` bila belum siap
    - _Requirements: 6.1, 6.2, 6.3, 6.5, 11.1, 11.2, 11.3, 13.4_

  - [ ] 7.3 Implementasikan helper `finalizeBupotIfAllPajakSettled($potongan)`
    - Bila seluruh pajak honor pada tagihan sudah `ntpn ≠ null`, set `bupot_status = 'FINAL'` untuk semua `DetailHonorarium` pada tagihan
    - Generate `nomor_bupot` per detail dengan format `BP21/{tahun}/{nomor_urut}` (urutan 4-digit padded)
    - Sediakan generator `nomor_bupot` yang dipanggil dalam transaksi; rely pada `unique` constraint untuk race-safety dengan retry
    - Catat `LogStatusDokumen` (`aksi = 'FINALIZE_BUPOT_HONOR'`, `status_baru = 'BUPOT_FINAL'`, `dokumen_type = DetailHonorarium::class`)
    - _Requirements: 8.5, 8.8, 13.5_

  - [ ] 7.4 Implementasikan `PenyetoranPajakHonorController::storeNtpn`
    - Validasi `ntpn: required|string|max:50`, `file_bukti_setor: required|file|mimes:pdf,jpg,jpeg,png|max:5120`, `file_bppu: nullable|file|mimes:pdf,jpg,jpeg,png|max:5120`
    - Tolak bila `kode_billing` kosong, `ntpn` sudah ada, tagihan belum SELESAI, atau SP2D belum EXECUTED
    - Dalam transaksi: simpan `ntpn`, simpan arsip BPN (`jenis_dokumen = 'BUKTI_SETOR_PAJAK'`) dan BPPU bila ada (`jenis_dokumen = 'BPPU'`), keduanya di disk `public` path `arsip/pajak-honor`
    - Catat `LogStatusDokumen` (`aksi = 'INPUT_NTPN'`, `status_baru = 'SUDAH_SETOR'`)
    - Panggil `postBkuIfAllPajakSettled($potongan)` dan `finalizeBupotIfAllPajakSettled($potongan)`
    - Flash message membedakan: bila BKU dibuat sertakan "Tagihan honorarium sudah masuk BKU"; bila belum sertakan "BKU akan dibuat setelah seluruh pajak tersetor"
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9, 5.10, 5.11, 6.4, 13.3_

  - [ ]* 7.5 Property test storeBilling/storeNtpn (`Tests\Property\StoreBillingNtpnTest`)
    - **Property 8: Precondition penyetoran (storeBilling & storeNtpn)**
    - **Property 9: Validasi input & manajemen arsip atomik**
    - **Property 10: Pengarsipan sukses (BPN & BPPU) memenuhi kontrak**
    - **Validates: Requirements 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9**

  - [ ]* 7.6 Property test BKU posting honor (`Tests\Property\BkuPostingHonorTest`)
    - **Property 11: Posting BKU iff seluruh pajak honor tersetor (idempoten)**
    - **Property 12: Pesan sukses penyetoran sesuai status BKU**
    - **Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.5, 11.1, 11.2, 11.3**

  - [ ]* 7.7 Property test audit trail (`Tests\Property\AuditTrailHonorTest`)
    - **Property 14: Audit trail komprehensif & rollback bila tidak lengkap**
    - **Validates: Requirements 4.8, 5.10, 13.1, 13.2, 13.3, 13.4, 13.5, 13.6**

- [ ] 8. Cetak ringkasan dan e-Bupot 21
  - [ ] 8.1 Implementasikan `PenyetoranPajakHonorController::cetak` dan view
    - `cetak($id)` panggil `findHonorPajakPotongan` (404 jika lintas-tipe)
    - Render view `penyetoran_pajak_honor.cetak` dengan ringkasan tagihan, daftar `detail_honorarium`, jenis pajak, nominal, billing, NTPN, dan tanggal-tanggal terkait
    - Buat `resources/views/penyetoran_pajak_honor/cetak.blade.php` dengan layout cetak
    - _Requirements: 7.1, 7.2, 7.3_

  - [ ] 8.2 Implementasikan `PenyetoranPajakHonorController::bupot` dan view
    - Load `DetailHonorarium::with('tagihan.spps.spm.npi.sp2d', 'tagihan.potonganTagihan')`; 404 bila tagihan bukan HONORARIUM
    - Susun konteks: identitas pemotong (Bendahara Pengeluaran), identitas penerima (nama, NRP/NIP, jabatan), DPP (`nilai_honor`), tarif (`MasterTarifPajak` aktif), nominal `pph`, periode (bulan/tahun `sp2d.tanggal_sp2d`), `nomor_sp2d`, NTPN agregat dari semua baris pajak honor pada tagihan
    - Tentukan label `DRAFT`/`FINAL` hanya dari `bupot_status`
    - Buat `resources/views/penyetoran_pajak_honor/bupot.blade.php` HTML berorientasi cetak (CSS `@media print`) dengan label DRAFT/FINAL dan teks pengantar sesuai status
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.6, 8.7, 8.9, 8.10_

  - [ ]* 8.3 Property test bupot (`Tests\Property\BupotHonorTest`)
    - **Property 16: Finalisasi e-Bupot 21 ketika seluruh pajak tersetor**
    - **Property 17: Label e-Bupot deterministik dari `bupot_status`**
    - **Property 19: Konten render e-Bupot 21 lengkap**
    - **Validates: Requirements 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8**

  - [ ]* 8.4 Smoke test view bupot (`Tests\Feature\BupotPrintViewSmokeTest`)
    - Pastikan view me-render HTTP 200 dan mengandung CSS `@media print`
    - _Requirements: 8.9_

- [ ] 9. Dashboard Bendahara Pengeluaran
  - [ ] 9.1 Modifikasi `BendaharaPengeluaranDashboardController::index`
    - Tambahkan query `potonganPajakHonor` (HONORARIUM + SELESAI + SP2D EXECUTED) dengan eager load `tagihan` dan `pajak`
    - Bentuk turunan koleksi `pajakHonorBelumBilling`, `pajakHonorSudahBilling`, `pajakHonorSudahSetor`
    - Sertakan keempat variabel pada `compact(...)` tanpa mengubah variabel kontrak yang sudah ada
    - _Requirements: 9.1, 9.2_

  - [ ] 9.2 Modifikasi view `resources/views/dashboards/bendahara_pengeluaran.blade.php`
    - Tambahkan panel "Penyetoran Pajak Honorarium" setelah panel kontrak dengan 3 kartu summary + tabel ringkas + tombol "Kelola Pajak Honorarium" → `route('pajak-potongan.honor.index')`
    - Pertahankan panel "Penyetoran Pajak Kontrak" tanpa perubahan
    - _Requirements: 9.3, 9.4, 9.5_

  - [ ]* 9.3 Snapshot test dashboard (`Tests\Feature\BendaharaDashboardHonorSnapshotTest`)
    - Verifikasi panel "Penyetoran Pajak Kontrak" tetap utuh dan panel "Penyetoran Pajak Honorarium" muncul dengan tautan benar
    - _Requirements: 9.3, 9.4, 9.5_

- [ ] 10. Integrasi Buku Pembantu Pajak
  - [ ]* 10.1 Integration test pajak honor pada Buku Pembantu Pajak (`Tests\Feature\BukuPembantuPajakHonorIntegrationTest`)
    - **Property 20: Integrasi Buku Pembantu Pajak**
    - **Validates: Requirements 14.1, 14.2, 14.3**

- [ ] 11. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP delivery; namun seluruh property test tetap direkomendasikan untuk menjaga invariant penting (agregasi pajak, idempotensi BKU, finalisasi e-Bupot, otorisasi peran).
- Setiap task merujuk requirement spesifik untuk traceability; property tests mereferensikan nomor properti dan klausul requirement yang divalidasi.
- Checkpoint pada task 6 dan 11 memastikan validasi inkremental sebelum melangkah ke fase berikutnya.
- Property tests menggunakan PHPUnit + Eris (≥ 100 iterasi per properti), `RefreshDatabase` SQLite in-memory, dan `Storage::fake('public')`. Setiap test method diawali komentar `// Feature: penyetoran-pajak-honorarium, Property {N}: {ringkasan}`.
- Tidak ada perubahan pada `BkuPostingService`, `BukuPembantuPajakController`, atau alur SPM/NPI/SP2D yang sudah ada — modul ini hanya menambah kontroler/route/view baru dan menyentuh `SppController::storeHonor`, `BendaharaPengeluaranDashboardController::index`, satu view dashboard, plus migration kolom bupot.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.3", "3.1"] },
    { "id": 1, "tasks": ["1.2", "3.2"] },
    { "id": 2, "tasks": ["2.1", "9.1"] },
    { "id": 3, "tasks": ["2.2", "2.3", "4.1", "9.2"] },
    { "id": 4, "tasks": ["4.2", "5.1", "9.3"] },
    { "id": 5, "tasks": ["2.4", "2.5", "2.6", "3.3", "3.4", "4.3", "5.2", "7.1", "7.2", "7.3"] },
    { "id": 6, "tasks": ["5.3", "7.4", "8.1"] },
    { "id": 7, "tasks": ["7.5", "7.6", "7.7", "8.2"] },
    { "id": 8, "tasks": ["8.3", "8.4", "10.1"] }
  ]
}
```
