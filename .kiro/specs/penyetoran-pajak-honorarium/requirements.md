# Requirements Document

Penyetoran Pajak Honorarium (PPh 21) — SIKEREN-BLU

## Introduction

Fitur **Penyetoran Pajak Honorarium** memperluas siklus pembayaran honorarium pada SIKEREN-BLU dengan menambahkan tahap penyetoran PPh 21 ke kas negara oleh Bendahara Pengeluaran. Saat ini sistem hanya menghitung `pph` per `detail_honorarium` secara numerik, namun belum membuat entitas `PotonganTagihan` untuk pajak honorarium dan tidak memiliki kontroler penyetoran (ID Billing → NTPN → BKU posting → e-Bupot 21).

Fitur ini mengikuti pola yang sudah berjalan pada `PenyetoranPajakKontrakController` (kontrak), dengan penyesuaian untuk karakteristik honorarium: PPh 21 yang dipotong dari banyak penerima (`detail_honorarium`) namun disetorkan secara agregat per jenis pajak ke kas negara, dan setiap penerima berhak atas Bukti Potong (e-Bupot 21).

Tujuan utama:

1. Bendahara Pengeluaran dapat menyetorkan PPh 21 atas honorarium yang sudah cair (SP2D EXECUTED) ke kas negara.
2. Sistem mencatat ID Billing dan NTPN per (tagihan honor × jenis pajak) untuk keperluan rekonsiliasi.
3. Sistem memposting setoran ke Buku Kas Umum (BKU) dan Buku Pembantu Pajak setelah seluruh PPh 21 atas tagihan honor tersetor.
4. Sistem menerbitkan e-Bupot 21 (PDF) per penerima honorarium sebagai dokumen pemotongan pajak.
5. Dashboard Bendahara Pengeluaran menampilkan antrian penyetoran pajak honorarium secara terpisah dari kontrak.

## Glossary

- **Bendahara_Pengeluaran**: Pejabat di SIKEREN-BLU yang berwenang memungut, menyetor, dan mempertanggungjawabkan PPh 21 atas pembayaran honorarium.
- **PPh_21**: Pajak Penghasilan Pasal 21 yang dipungut atas pembayaran honorarium kepada orang pribadi.
- **Tagihan_Honor**: Entitas `Tagihan` dengan `tipe_tagihan = 'HONORARIUM'` yang berisi banyak baris `detail_honorarium`.
- **Detail_Honorarium**: Baris penerima honorarium pada satu tagihan honor; menyimpan `nilai_honor`, `pph`, dan identitas penerima (NRP/NIP, nama, jabatan).
- **Potongan_Tagihan_Pajak_Honor**: Baris `potongan_tagihan` dengan `jenis_potongan = 'PAJAK'` yang berasosiasi dengan tagihan honor; satu baris per (tagihan_id × pajak_id) yang menjumlahkan seluruh `detail_honorarium.pph` untuk jenis pajak tersebut.
- **ID_Billing**: Kode billing yang diterbitkan DJP Online / SIMPONI sebagai dasar pembayaran pajak ke bank persepsi.
- **NTPN**: Nomor Transaksi Penerimaan Negara yang dikembalikan oleh sistem penerimaan negara setelah pajak diterima di kas negara.
- **BPN**: Bukti Penerimaan Negara, dokumen resmi yang diterbitkan setelah pembayaran diterima.
- **e_Bupot_21**: Bukti Pemotongan PPh 21 yang diterbitkan oleh pemotong pajak (Bendahara) untuk setiap penerima penghasilan.
- **SP2D_EXECUTED**: Status `DokumenSp2d` yang menandai SP2D sudah dieksekusi (transfer bank tercatat); konstanta `DokumenSp2d::STATUS_EXECUTED`.
- **Tagihan_SELESAI**: Status `Tagihan` setelah seluruh proses pembayaran (termasuk bukti transfer SP2D) selesai.
- **Sistem**: Aplikasi SIKEREN-BLU (modul Penyetoran Pajak Honorarium).
- **BKU**: Buku Kas Umum, catatan kas masuk/keluar Bendahara.
- **Buku_Pembantu_Pajak**: Buku pembantu yang merangkum mutasi PPh per jenis pajak.

## Requirements

### Requirement 1: Pembentukan Potongan Pajak Honorarium pada SPP

**User Story:** Sebagai Operator BLU, saya ingin baris `potongan_tagihan` untuk PPh 21 honorarium terbentuk otomatis saat SPP honor disimpan, sehingga setiap pajak honorarium memiliki entitas yang dapat ditelusuri penyetorannya.

#### Acceptance Criteria

1. WHEN draft SPP Honorarium disimpan melalui `SppController::storeHonor`, THE Sistem SHALL menghapus seluruh baris `potongan_tagihan` dengan `jenis_potongan = 'PAJAK'` milik tagihan honor tersebut sebelum membentuk baris baru.
2. WHEN draft SPP Honorarium disimpan, THE Sistem SHALL membentuk satu baris `potongan_tagihan` per jenis pajak (`pajak_id`) yang `nominal_potongan`-nya merupakan penjumlahan `detail_honorarium.pph` untuk jenis pajak tersebut pada tagihan honor.
3. WHEN baris `potongan_tagihan` PPh 21 honorarium dibentuk, THE Sistem SHALL mengisi kolom `tagihan_id`, `pajak_id`, `jenis_potongan = 'PAJAK'`, `dpp` (sebesar total `nilai_honor` yang menjadi DPP untuk jenis pajak tersebut), `persentase_tarif_snapshot`, `nama_pajak_snapshot`, dan `nominal_potongan`.
4. THE Sistem SHALL memvalidasi bahwa `dpp >= 0`, `persentase_tarif_snapshot > 0`, dan `nominal_potongan > 0` sebelum menyimpan baris `potongan_tagihan` PPh 21 honorarium.
5. IF jumlah `nominal_potongan` seluruh baris `potongan_tagihan` PPh 21 honorarium yang akan dibentuk tidak sama dengan jumlah `detail_honorarium.pph` pada tagihan honor, THEN THE Sistem SHALL membatalkan transaksi penyimpanan SPP dan mengembalikan pesan kesalahan yang menjelaskan adanya selisih.
6. WHEN seluruh baris `potongan_tagihan` PPh 21 honorarium telah dibentuk, THE Sistem SHALL memperbarui `tagihan.total_potongan` dan `tagihan.total_netto` agar konsisten dengan jumlah PPh 21 yang dibukukan.
7. IF total `detail_honorarium.pph` pada tagihan honor adalah 0, THEN THE Sistem SHALL tidak membentuk baris `potongan_tagihan` PPh 21 untuk tagihan tersebut.
8. THE Sistem SHALL menggunakan jenis pajak default PPh 21 yang sudah aktif pada `master_tarif_pajak` (`status_aktif = true`) untuk pengelompokan `detail_honorarium.pph`.

### Requirement 2: Antrian Penyetoran Pajak Honorarium

**User Story:** Sebagai Bendahara Pengeluaran, saya ingin melihat daftar PPh 21 honorarium yang siap disetor, sehingga saya dapat memilih tagihan mana yang akan diproses penyetorannya.

#### Acceptance Criteria

1. THE Sistem SHALL menyediakan halaman index penyetoran pajak honorarium pada route `pajak-potongan.honor.index`.
2. THE Sistem SHALL menampilkan baris `potongan_tagihan` dengan `jenis_potongan = 'PAJAK'` yang tagihannya bertipe `HONORARIUM`.
3. THE Sistem SHALL hanya menampilkan baris yang tagihannya berstatus `SELESAI` dan SP2D-nya berstatus `EXECUTED`.
4. WHEN pengguna mengetik kata kunci pencarian, THE Sistem SHALL memfilter daftar berdasarkan `kode_billing`, `ntpn`, `nama_pajak_snapshot`, `nomor_tagihan`, atau nama personel pada `detail_honorarium`.
5. WHEN pengguna memilih filter status, THE Sistem SHALL memfilter daftar dengan kategori: `belum_billing` (tanpa `kode_billing`), `sudah_billing` (`kode_billing` ada, `ntpn` kosong), `sudah_setor` (`kode_billing` dan `ntpn` ada).
6. THE Sistem SHALL menampilkan ringkasan jumlah baris untuk masing-masing kategori status (belum billing, sudah billing, sudah setor) berdasarkan seluruh data yang memenuhi syarat (tanpa filter status).
7. THE Sistem SHALL mengurutkan daftar berdasarkan tanggal dibuat secara menurun (`latest()`).

### Requirement 3: Detail dan Workspace Penyetoran

**User Story:** Sebagai Bendahara Pengeluaran, saya ingin membuka workspace detail penyetoran satu baris pajak honor agar dapat menginput ID Billing dan NTPN dengan referensi lengkap.

#### Acceptance Criteria

1. THE Sistem SHALL menyediakan halaman detail penyetoran pajak honorarium pada route `pajak-potongan.honor.detail` yang menerima parameter `potongan` (id `potongan_tagihan`).
2. WHEN halaman detail dibuka, THE Sistem SHALL menampilkan informasi tagihan honor (nomor, deskripsi, total bruto, total potongan, total netto), informasi SPP/SPM/NPI/SP2D, dan daftar `detail_honorarium` (nama personel, jabatan, nilai honor, pph) yang berkontribusi pada nominal pajak ini.
3. WHEN halaman detail dibuka, THE Sistem SHALL menampilkan informasi pajak (jenis pajak, persentase tarif, DPP, nominal potongan, kode billing, NTPN) dan akun potongan terkait.
4. IF baris `potongan_tagihan` yang diakses bukan milik tagihan dengan `tipe_tagihan = 'HONORARIUM'`, THEN THE Sistem SHALL mengembalikan respons 404.
5. IF baris `potongan_tagihan` yang diakses bukan `jenis_potongan = 'PAJAK'`, THEN THE Sistem SHALL mengembalikan respons 404.
6. IF baris `potongan_tagihan` yang diakses gagal melewati pemeriksaan validasi referensi (misalnya tagihan terkait sudah soft-deleted, jenis pajak tidak terdefinisi, atau relasi tagihan honor tidak konsisten), THEN THE Sistem SHALL mengembalikan respons 404.
7. THE Sistem SHALL menampilkan file arsip terkait (E-Billing, BPN/Bukti Setor, BPPU jika ada) berikut metadata uploader dan waktu unggah.
8. THE Sistem SHALL menampilkan label status setor: `Belum Billing` (NTPN dan kode_billing kosong), `Sudah Billing` (kode_billing ada, NTPN kosong), `Sudah Setor` (NTPN ada).
9. WHILE baris pajak belum memiliki NTPN, THE Sistem SHALL mengaktifkan tombol input ID Billing dan input NTPN tanpa memandang status sistem lain selain status pengguna yang sudah lulus middleware peran (Requirement 12).
10. WHERE baris pajak sudah memiliki NTPN, THE Sistem SHALL menonaktifkan tombol input ID Billing dan input NTPN.

### Requirement 4: Input ID Billing dan Unggah E-Billing

**User Story:** Sebagai Bendahara Pengeluaran, saya ingin mencatat ID Billing dari DJP Online beserta cetakan E-Billing-nya, sehingga ada bukti bahwa billing sudah dibuat untuk PPh 21 ini.

#### Acceptance Criteria

1. THE Sistem SHALL menyediakan endpoint `POST` pada route `pajak-potongan.honor.billing` untuk menyimpan/memperbarui kode billing.
2. WHEN form ID Billing disubmit, THE Sistem SHALL memvalidasi `kode_billing` sebagai string wajib dengan panjang maksimum 50 karakter.
3. WHEN form ID Billing disubmit dan belum ada arsip E-Billing sebelumnya, THE Sistem SHALL mewajibkan unggahan `file_billing` bertipe `pdf|jpg|jpeg|png` dengan ukuran maksimum 5120 KB.
4. WHEN form ID Billing disubmit dan sudah ada arsip E-Billing sebelumnya, THE Sistem SHALL mengizinkan `file_billing` kosong (mempertahankan file lama) atau diunggah ulang.
5. WHEN file E-Billing baru diunggah, THE Sistem SHALL menyimpan arsip baru pada disk `public` dengan path `arsip/pajak-honor` dan `jenis_dokumen = 'KODE_BILLING'` terlebih dahulu, dan SHALL menghapus arsip E-Billing lama hanya setelah penyimpanan arsip baru berhasil.
6. IF tagihan honor terkait belum berstatus `SELESAI` ATAU SP2D-nya belum `EXECUTED`, THEN THE Sistem SHALL menolak penyimpanan ID Billing dan mengembalikan pesan kesalahan yang menjelaskan prasyarat tersebut.
7. IF baris pajak sudah memiliki NTPN, THEN THE Sistem SHALL menolak perubahan ID Billing dan mengembalikan pesan kesalahan.
8. WHEN ID Billing berhasil disimpan, THE Sistem SHALL mencatat baris `log_status_dokumen` dengan `aksi = 'INPUT_KODE_BILLING'`, `status_baru = 'SUDAH_BILLING'`, `role_saat_itu = 'Bendahara Pengeluaran'`, dan IP pengguna.
9. THE Sistem SHALL membatasi akses endpoint ini hanya untuk pengguna berperan `Bendahara Pengeluaran` atau `Super Admin`.

### Requirement 5: Input NTPN dan Unggah Bukti Setor

**User Story:** Sebagai Bendahara Pengeluaran, saya ingin merekam NTPN beserta Bukti Penerimaan Negara setelah pajak dibayar di bank persepsi, sehingga status setor PPh 21 honor dapat ditandai lunas.

#### Acceptance Criteria

1. THE Sistem SHALL menyediakan endpoint `POST` pada route `pajak-potongan.honor.ntpn` untuk menyimpan NTPN.
2. WHEN form NTPN disubmit, THE Sistem SHALL memvalidasi `ntpn` sebagai string wajib dengan panjang maksimum 50 karakter dan `file_bukti_setor` sebagai unggahan wajib bertipe `pdf|jpg|jpeg|png` dengan ukuran maksimum 5120 KB.
3. WHEN form NTPN disubmit, THE Sistem SHALL menerima `file_bppu` opsional bertipe `pdf|jpg|jpeg|png` dengan ukuran maksimum 5120 KB.
4. IF baris pajak belum memiliki `kode_billing`, THEN THE Sistem SHALL menolak penyimpanan NTPN dan mengembalikan pesan kesalahan.
5. IF baris pajak sudah memiliki NTPN, THEN THE Sistem SHALL menolak penyimpanan ulang NTPN dan mengembalikan pesan kesalahan.
6. IF tagihan honor terkait belum berstatus `SELESAI` ATAU SP2D-nya belum `EXECUTED`, THEN THE Sistem SHALL menolak penyimpanan NTPN.
7. IF prasyarat tagihan SELESAI dan SP2D EXECUTED terpenuhi namun validasi lain gagal (misalnya `kode_billing` masih kosong, NTPN sudah pernah diinput, file gagal diunggah, atau aturan integritas lain yang ditambahkan kemudian), THEN THE Sistem SHALL tetap menolak penyimpanan NTPN dan mengembalikan pesan kesalahan yang sesuai.
8. WHEN NTPN berhasil disimpan, THE Sistem SHALL menyimpan arsip BPN dengan `jenis_dokumen = 'BUKTI_SETOR_PAJAK'` pada disk `public` dengan path `arsip/pajak-honor`.
9. WHERE `file_bppu` diunggah, THE Sistem SHALL menyimpan arsip BPPU dengan `jenis_dokumen = 'BPPU'` pada disk `public` dengan path `arsip/pajak-honor`.
10. WHEN NTPN berhasil disimpan, THE Sistem SHALL mencatat baris `log_status_dokumen` dengan `aksi = 'INPUT_NTPN'`, `status_baru = 'SUDAH_SETOR'`, `role_saat_itu = 'Bendahara Pengeluaran'`, dan IP pengguna.
11. THE Sistem SHALL membatasi akses endpoint ini hanya untuk pengguna berperan `Bendahara Pengeluaran` atau `Super Admin`.

### Requirement 6: Posting BKU Otomatis Saat Seluruh Pajak Honor Tersetor

**User Story:** Sebagai Bendahara Pengeluaran, saya ingin tagihan honor terposting ke BKU secara otomatis saat seluruh PPh 21-nya sudah memiliki NTPN, agar pencatatan kas konsisten dengan realisasi.

#### Acceptance Criteria

1. WHEN sebuah baris `potongan_tagihan` PPh 21 honorarium menerima NTPN, THE Sistem SHALL memeriksa apakah masih ada baris `potongan_tagihan` dengan `jenis_potongan = 'PAJAK'`, `nominal_potongan > 0`, dan NTPN kosong pada tagihan honor yang sama.
2. IF masih terdapat baris pajak honorarium yang belum tersetor pada tagihan tersebut, THEN THE Sistem SHALL tidak memposting tagihan ke BKU.
3. WHEN seluruh baris pajak honorarium pada tagihan tersebut sudah tersetor, THE Sistem SHALL memanggil `BkuPostingService::postTagihanPengeluaran` untuk tagihan honor tersebut dengan `nominal` sebesar `total_bruto` dan catatan menyebut "Pembayaran tagihan honorarium setelah bukti transfer SP2D dan seluruh setoran pajak honor lengkap".
4. THE Sistem SHALL menampilkan pesan sukses yang membedakan antara "BKU sudah dibuat" dan "BKU akan dibuat setelah seluruh pajak tersetor".
5. WHEN posting BKU berhasil, THE Sistem SHALL memastikan tidak ada duplikasi baris BKU untuk pasangan (`referensi_pengeluaran_id` = tagihan honor, `nomor_bukti` = nomor SP2D).

### Requirement 7: Cetak Ringkasan Penyetoran Pajak Honor

**User Story:** Sebagai Bendahara Pengeluaran, saya ingin mencetak ringkasan satu baris penyetoran pajak honorarium, sehingga dapat mengarsipkan dokumen fisik bila diperlukan.

#### Acceptance Criteria

1. THE Sistem SHALL menyediakan route `pajak-potongan.honor.cetak` yang menerima parameter `potongan` (id `potongan_tagihan`).
2. WHEN halaman cetak dibuka, THE Sistem SHALL menampilkan informasi tagihan honor, daftar `detail_honorarium` yang berkontribusi, jenis pajak, nominal, kode billing, NTPN, dan tanggal-tanggal terkait.
3. IF pengguna yang mengakses route ini tidak memiliki peran `Bendahara Pengeluaran` atau `Super Admin`, THEN THE Sistem SHALL mengembalikan respons HTTP 403 dan SHALL NOT menyajikan konten halaman cetak (termasuk untuk keperluan baca-saja oleh peran lain).

### Requirement 8: Cetak e-Bupot 21 per Penerima Honorarium

**User Story:** Sebagai Bendahara Pengeluaran, saya ingin menerbitkan Bukti Pemotongan PPh 21 (e-Bupot 21) untuk setiap penerima honorarium, sehingga penerima memiliki dokumen pemotongan pajak yang sah.

#### Acceptance Criteria

1. THE Sistem SHALL menyediakan route `pajak-potongan.honor.bupot` yang menerima parameter `detail_honorarium` (id baris penerima honor).
2. WHEN halaman e-Bupot 21 dibuka, THE Sistem SHALL menampilkan identitas pemotong (Bendahara Pengeluaran), identitas penerima (nama, NRP/NIP, jabatan dari `detail_honorarium`), DPP (`nilai_honor`), tarif PPh 21, nominal `pph`, periode pemotongan (bulan dan tahun SP2D), nomor SP2D, dan NTPN setoran terkait.
3. THE Sistem SHALL menyimpan kolom `bupot_status` pada baris `detail_honorarium` (atau pada tabel pendamping `bupot_honorarium`) dengan nilai `DRAFT` atau `FINAL`.
4. THE Sistem SHALL menentukan label tampilan e-Bupot 21 (DRAFT/FINAL) hanya berdasarkan nilai `bupot_status`, dan SHALL NOT menyimpulkan label dari status setor pajak secara langsung pada saat tampilan.
5. WHEN seluruh baris `potongan_tagihan` PPh 21 honorarium pada tagihan terkait sudah memiliki NTPN, THE Sistem SHALL memperbarui `bupot_status` seluruh `detail_honorarium` pada tagihan tersebut menjadi `FINAL` dan SHALL menetapkan `nomor_bupot` final.
6. WHILE `bupot_status` masih `DRAFT`, THE Sistem SHALL menampilkan halaman e-Bupot 21 dengan label "DRAFT" dan menyebutkan bahwa bukti potong belum final.
7. WHEN `bupot_status` bernilai `FINAL`, THE Sistem SHALL menampilkan e-Bupot 21 final dengan nomor bukti potong dan referensi NTPN.
8. THE Sistem SHALL membentuk nomor bukti potong yang unik per `detail_honorarium` mengikuti format `BP21/{tahun}/{nomor_urut}` dengan nomor urut yang tidak terulang dalam tahun yang sama.
9. THE Sistem SHALL menyajikan e-Bupot 21 sebagai halaman cetak (HTML berorientasi cetak) yang dapat disimpan sebagai PDF oleh browser.
10. THE Sistem SHALL membatasi akses route ini hanya untuk pengguna berperan `Bendahara Pengeluaran` atau `Super Admin`.

### Requirement 9: Tampilan Dashboard Bendahara Pengeluaran

**User Story:** Sebagai Bendahara Pengeluaran, saya ingin melihat ringkasan antrian penyetoran pajak honorarium di dashboard saya, sehingga saya tidak perlu membuka halaman terpisah untuk mengetahui jumlah pekerjaan.

#### Acceptance Criteria

1. WHEN dashboard Bendahara Pengeluaran dimuat melalui `BendaharaPengeluaranDashboardController::index`, THE Sistem SHALL menyediakan koleksi `potonganPajakHonor` berisi baris `potongan_tagihan` PPh 21 honorarium yang tagihannya `SELESAI` dan SP2D-nya `EXECUTED`.
2. THE Sistem SHALL menyediakan tiga turunan koleksi: `pajakHonorBelumBilling`, `pajakHonorSudahBilling`, dan `pajakHonorSudahSetor`.
3. THE Sistem SHALL menampilkan panel "Penyetoran Pajak Honorarium" pada view `dashboards.bendahara_pengeluaran` yang menampilkan jumlah baris di tiap kategori status.
4. THE Sistem SHALL menyediakan tautan dari panel tersebut ke route `pajak-potongan.honor.index`.
5. THE Sistem SHALL mempertahankan panel "Penyetoran Pajak Kontrak" yang sudah ada tanpa perubahan.

### Requirement 10: Penanganan Revisi Tagihan dan Pajak

**User Story:** Sebagai Bendahara Pengeluaran, saya ingin perubahan nilai PPh 21 saat revisi tagihan tetap konsisten dengan baris `potongan_tagihan`, sehingga tidak ada selisih yang membingungkan.

#### Acceptance Criteria

1. WHEN draft SPP Honorarium disimpan ulang (revisi) sementara baris `potongan_tagihan` PPh 21-nya BELUM memiliki `kode_billing`, THE Sistem SHALL menghapus baris pajak lama dan membentuk baris baru sesuai `detail_honorarium.pph` terbaru.
2. IF salah satu baris `potongan_tagihan` PPh 21 honorarium pada tagihan sudah memiliki `kode_billing` atau NTPN, THEN THE Sistem SHALL menolak penyimpanan ulang draft SPP yang akan mengubah baris pajak tersebut, mengembalikan pesan kesalahan menyatakan revisi pajak tidak diperbolehkan setelah billing terbentuk, dan SHALL melakukan rollback transaksi sehingga tidak ada perubahan apa pun pada baris pajak (tidak ada penghapusan, pembentukan, maupun pemutakhiran).
3. IF tagihan honor dibatalkan setelah baris pajak terbentuk, THEN THE Sistem SHALL mempertahankan seluruh baris `potongan_tagihan` yang sudah ada (soft delete saat tagihan soft-delete) sebagai jejak audit dan SHALL NOT membentuk baris pajak baru selama proses pembatalan.

### Requirement 11: Penanganan Setoran Parsial

**User Story:** Sebagai Bendahara Pengeluaran, saya ingin menyetor PPh 21 honorarium per jenis pajak satu per satu, sehingga jika sebagian sudah disetor namun sebagian belum, sistem tetap konsisten.

#### Acceptance Criteria

1. WHILE sebagian baris `potongan_tagihan` PPh 21 honorarium pada satu tagihan sudah memiliki NTPN dan sebagian belum, THE Sistem SHALL tetap mengizinkan input ID Billing dan NTPN untuk baris yang belum tersetor.
2. WHILE sebagian baris pajak belum tersetor, THE Sistem SHALL tidak memposting tagihan ke BKU.
3. WHEN baris pajak terakhir pada tagihan honor selesai disetor, THE Sistem SHALL memposting tagihan ke BKU sesuai Requirement 6.

### Requirement 12: Pembatasan Akses Berbasis Peran

**User Story:** Sebagai Administrator Sistem, saya ingin hanya Bendahara Pengeluaran (dan Super Admin) yang dapat mengakses modul penyetoran pajak honorarium, sehingga tidak ada peran lain yang mengubah data setoran.

#### Acceptance Criteria

1. IF pengguna yang mengakses route `pajak-potongan.honor.*` tidak memiliki peran `Bendahara Pengeluaran` atau `Super Admin`, THEN THE Sistem SHALL mengembalikan respons HTTP 403 dengan pesan "Akses ditolak".
2. THE Sistem SHALL mengelompokkan seluruh route `pajak-potongan.honor.*` di bawah middleware `role:Super Admin|Bendahara Pengeluaran` pada `routes/web.php`.

### Requirement 13: Audit Trail Aksi Penyetoran

**User Story:** Sebagai Auditor Internal, saya ingin setiap aksi pada modul penyetoran pajak honorarium tercatat dengan pengguna, waktu, dan IP, sehingga jejak audit lengkap.

#### Acceptance Criteria

1. WHEN baris `potongan_tagihan` PPh 21 honorarium dibentuk pertama kali, THE Sistem SHALL mencatat baris `log_status_dokumen` dengan `aksi = 'CREATE_PAJAK_HONOR'` dan referensi ke `PotonganTagihan`.
2. WHEN ID Billing diinput atau diperbarui, THE Sistem SHALL mencatat `log_status_dokumen` sesuai Requirement 4 poin 8.
3. WHEN NTPN diinput, THE Sistem SHALL mencatat `log_status_dokumen` sesuai Requirement 5 poin 9.
4. WHEN tagihan honor diposting ke BKU akibat penyetoran pajak, THE Sistem SHALL mencatat `log_status_dokumen` dengan `aksi = 'POST_BKU'` melalui `BkuPostingService` yang sudah ada.
5. THE Sistem SHALL menyimpan `user_id`, `role_saat_itu`, `ip_address`, dan timestamp pada setiap baris `log_status_dokumen` yang dibentuk modul ini.
6. IF salah satu kolom audit wajib (`user_id`, `role_saat_itu`, `ip_address`, atau timestamp) tidak dapat ditentukan saat baris log akan dibentuk, THEN THE Sistem SHALL membatalkan transaksi penyimpanan dan SHALL NOT melanjutkan operasi penyetoran terkait.

### Requirement 14: Konsistensi Buku Pembantu Pajak

**User Story:** Sebagai Bendahara Pengeluaran, saya ingin penyetoran PPh 21 honorarium muncul pada Buku Pembantu Pajak, sehingga laporan pajak bulanan akurat.

#### Acceptance Criteria

1. WHEN baris `potongan_tagihan` PPh 21 honorarium memiliki NTPN, THE Sistem SHALL menampilkannya pada laporan Buku Pembantu Pajak (`BukuPembantuPajakController`) tanpa perubahan tambahan pada kontroler tersebut.
2. THE Sistem SHALL memastikan kolom `kode_billing`, `ntpn`, `nama_pajak_snapshot`, `nominal_potongan`, dan `tagihan_id` terisi sehingga Buku Pembantu Pajak dapat memproses baris tersebut.
3. WHERE seluruh kolom pada poin 2 telah lengkap namun aturan validasi atau kendala sistem lain pada `BukuPembantuPajakController` (misalnya filter periode, batas hak akses pembaca, atau validasi tambahan yang ditambahkan kemudian) tidak terpenuhi, THE Sistem SHALL membiarkan kontroler tersebut tetap dapat menolak/memfilter baris tanpa harus dipaksa berhasil oleh modul ini.

### Requirement 15: Tampilan Daftar Personel pada Detail Penyetoran

**User Story:** Sebagai Bendahara Pengeluaran, saya ingin melihat rincian penerima honorarium yang berkontribusi pada satu baris pajak, sehingga saya dapat memverifikasi nominal sebelum menyetor.

#### Acceptance Criteria

1. WHEN halaman detail penyetoran dibuka, THE Sistem SHALL memuat seluruh `detail_honorarium` milik tagihan honor dan menampilkan: nama personel, NRP/NIP, jabatan, `nilai_honor`, dan `pph`.
2. THE Sistem SHALL menampilkan total kontrol pada bagian akhir tabel: jumlah `nilai_honor` (sebagai DPP) dan jumlah `pph` (yang harus sama dengan `potongan_tagihan.nominal_potongan` pada baris yang sedang dilihat).
3. IF jumlah `pph` pada `detail_honorarium` tidak sama dengan `nominal_potongan` baris pajak (misalnya akibat data lama), THEN THE Sistem SHALL menampilkan peringatan visual yang menyatakan adanya selisih dan menyarankan revisi SPP.
