# Audit Database — SIKEREN

> Audit terfokus pada lapisan **database** (skema, constraint, FK, unique, index, tipe data,
> kolom audit, soft delete, morph) — diverifikasi langsung ke migrasi & model.
> Tujuan: dasar untuk menaikkan skor kategori **Database** (baseline 55 · estimasi kini 57).
> Status seluruh temuan: **Terverifikasi** (kode per Juni 2026).

---

## A. Postur Database (ringkas)

Fondasi **kuat**:
- Seluruh nominal uang memakai `decimal(18,2)` / `decimal(15,2)` — **tidak ada satu pun `float`/`double`**.
- `unique` luas pada nomor dokumen: DIPA, SPK, tagihan, SPP/SPM/NPI/SP2D, invoice, addendum, nomor laporan, kode pajak/akun, slug short-link, dll.
- Foreign key asli (`foreignId()->constrained()`) di mayoritas relasi; FK otomatis ter-index oleh Laravel.
- Composite unique pada relasi master (dipa_revisions, kontrak_termin, mitra_layanan, kategori/item tarif, dll).
- Indeks eksplisit pada kolom filter penting (mis. `transaksi_pembukuan(tanggal, kode_transaksi)`, search index `master_coas`).

Skor tertahan oleh: **constraint yang sengaja di-drop di area uang**, beberapa **FK "logis" tanpa constraint**,
**kolom `updated_by` tak ada** di tabel keuangan inti, **tipe integer untuk meteran**, dan **tanpa morph map permanen**.

---

## B. Yang sudah kuat (jangan diubah)

| Aspek | Bukti |
|---|---|
| Uang = decimal | `transaksi_pembukuan.jumlah_kotor/unsur_pajak_* decimal(18,2)`; `laporan_utilitas.total_biaya decimal(15,2)`; tidak ada float/double di seluruh migrasi |
| Nomor dokumen unik | `nomor_dipa/nomor_spk/nomor_tagihan/nomor_spp/spm/npi/sp2d/nomor_invoice/nomor_addendum/nomor_laporan` semua `->unique()` |
| FK asli + nullOnDelete/restrictOnDelete | `transaksi_pembukuan` (penerima_id, keg_output_akun_id, rekening_bank_id, created_by), `tagihan.created_by restrictOnDelete`, dst. |
| Composite unique master | `dipa_rev_items_revision_coa_unq`, `kontrak_termins_kontrak_termin_unq`, `mitra_jasa_layanan_unique`, `akun_pendapatan_akun_jenis_unq`, dll |
| Idempotensi pembayaran | `integration` `unique(['provider','external_reference'])` |

---

## C. Temuan & perbaikan (migrasi siap-pakai)

### DB-01 · `realisasi_anggaran` kehilangan unique `nomor_bukti` — High
- **Bukti:** `database/migrations/2026_06_13_090000_drop_unique_nomor_bukti_on_realisasi_anggaran.php` — `up()` men-drop unique; tidak ada pengganti composite. Anti-dobel hanya guard aplikasi di `BudgetRealizationService`.
- **Risiko:** satu SP2D dapat tercatat ganda sebagai realisasi → over-realisasi pagu DIPA tanpa rem di level DB.
- **Perbaikan:**
```php
Schema::table('realisasi_anggaran', function (Blueprint $table) {
    // sesuaikan granularitas realisasi per-komponen; sertakan deleted_at agar
    // re-record pasca-pembatalan tetap mungkin.
    $table->unique(['dokumen_sp2d_id', 'dipa_revision_item_id', 'deleted_at'], 'realisasi_sp2d_item_unq');
});
```

### DB-08 · BKU: sisi penerimaan tak terlindungi unique — Medium
- **Bukti:** `database/migrations/2026_05_30_010000_add_unique_index_to_buku_kas_umum.php` hanya `unique(['referensi_pengeluaran_id','nomor_bukti'])`. Tidak ada padanan untuk `referensi_penerimaan_id`, dan `nomor_bukti` BKU tidak unik global.
- **Risiko:** posting ganda transaksi penerimaan (DEBIT_MASUK) dari rekonsiliasi/import berulang → `saldo_akhir` kas rusak.
- **Perbaikan:**
```php
Schema::table('buku_kas_umum', function (Blueprint $table) {
    $table->unique(['referensi_penerimaan_id', 'nomor_bukti'], 'bku_ref_penerimaan_nomor_bukti_unq');
});
```
> Catatan (DI-04): baris BKU sering dibuat dengan `referensi_*_id = NULL` lalu diisi belakangan. Di MySQL, NULL tidak dianggap duplikat sehingga unique tak aktif saat insert. Solusi: set `referensi_*_id` saat insert, atau pakai unique pada kunci yang selalu terisi: `unique(['transaksi_pembukuan_id','kode_buku','arus_kas'])`.

### DB-02 · `transaksi_pembukuan`: FK logis & `no_bukti` tak unik — Medium
- **Bukti:** `database/migrations/2026_06_18_000004_create_transaksi_pembukuan_table.php:24` `kode_transaksi string(8)` ("FK logis ke kode_transaksi.kode"); `:41` `index('no_bukti')` (bukan unique).
- **Risiko:** kode transaksi typo/invalid lolos ke DB (gagal distribusi posting); jurnal ganda menggandakan mutasi kas.
- **Perbaikan** (kolom `kode_transaksi.kode` sudah `unique` → FK valid):
```php
Schema::table('transaksi_pembukuan', function (Blueprint $table) {
    $table->foreign('kode_transaksi')->references('kode')->on('kode_transaksi')->restrictOnDelete();
    $table->unique(['no_bukti', 'kode_transaksi', 'rekening_bank_id'], 'transaksi_pembukuan_bukti_unq');
});
```

### DB-03 · `laporan_utilitas` kehilangan unique periode — Medium
- **Bukti:** `database/migrations/2026_06_15_000000_drop_unik_laporan_utilitas_unique.php` men-drop `unik_laporan_utilitas` (mitra+layanan+bulan+tahun).
- **Risiko:** pemakaian listrik/air dapat dilaporkan & ditagih ganda untuk periode sama → over-billing / salah catat PNBP.
- **Perbaikan** (tambah pembeda `nomor_meter`, jangan hapus total):
```php
Schema::table('laporan_utilitas', function (Blueprint $table) {
    $table->unique(['mitra_jasa_id','layanan_jasa_id','nomor_meter','bulan','tahun'], 'unik_laporan_utilitas_v2');
});
```

### DB-05 · Tabel keuangan inti tanpa `updated_by` (+ 1 FK hilang) — Medium
- **Bukti:** `tagihan`, `tagihan_jasas`, `dokumen_spp/spm/npi/sp2d`, `transaksi_pembukuan`, `realisasi_anggaran` punya `created_by`/`dibuat_oleh_id` tetapi **tanpa `updated_by`**. `mitra_jasa_penjualan_details.created_by` = `unsignedBigInteger` **tanpa** FK (`2026_05_17_193849_…:23`).
- **Risiko:** forensik "siapa mengubah nominal" sulit; potensi orphan `created_by`.
- **Perbaikan:**
```php
foreach (['tagihan','tagihan_jasas','dokumen_spp','dokumen_spm','dokumen_npi','dokumen_sp2d','transaksi_pembukuan','realisasi_anggaran'] as $t) {
    Schema::table($t, fn (Blueprint $table) =>
        $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete());
}
Schema::table('mitra_jasa_penjualan_details', function (Blueprint $table) {
    $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
});
```
+ isi otomatis via observer/trait *Blameable* (`updated_by = auth()->id()` saat event `updating`).

### DB-04 · Meteran utilitas bertipe `integer` (signed) — Low–Medium
- **Bukti:** `database/migrations/2026_05_18_042830_create_laporan_utilitas_table.php:24-26` `stan_awal/stan_akhir/pemakaian` = `integer` (maks ~2,1 miliar; tak menampung m³ desimal).
- **Risiko:** overflow/truncation → `total_biaya` (tagihan) salah; pembacaan air pecahan terpotong.
- **Perbaikan:**
```php
Schema::table('laporan_utilitas', function (Blueprint $table) {
    $table->decimal('stan_awal', 18, 3)->default(0)->change();   // desimal utk m³; atau unsignedBigInteger utk kWh utuh
    $table->decimal('stan_akhir', 18, 3)->default(0)->change();
    $table->decimal('pemakaian', 18, 3)->default(0)->change();
});
```

### DB-07 · Tanpa morph map permanen — Medium · STATUS: **DITUNDA** (keputusan 23 Jun 2026)
- **Bukti:** `app/Providers/AppServiceProvider::boot()` tidak mendaftarkan morph map; kolom morf menyimpan FQCN class.
- **Risiko:** refactor/namespace/subclass model → relasi morf putus.

**Hasil investigasi (mengapa ditunda).** Implementasi aman menuntut jauh lebih dari sekadar migrasi data.

*Kolom morf aktual (berbasis data) & nilainya:*

| Kolom | Nilai (FQCN) tersimpan |
|---|---|
| `arsip_dokumen.documentable_type` | DetailKontrak, DokumenSpp/Spm/Sp2d, PotonganTagihan |
| `log_status_dokumen.dokumen_type` | DokumenSpp/Spm/Npi/Sp2d, PotonganTagihan, Tagihan |
| `workflow_instances.workflowable_type` | DokumenSpp/Spm/Npi/Sp2d, Tagihan |
| `workflow_definitions.target_type` | + TagihanJasa |
| `realisasi_anggaran.sourceable_type`, `transaksi_pembukuan.referensi_type` | Tagihan |
| `users.profilable_type` | MasterPegawai (+ MitraJasa di produksi) |
| `rekening_bank.pemilik_type` | MasterPihak, User |
| `model_has_roles.model_type` (Spatie) | User |

*Penghalang utama:* **106 perbandingan tipe morf di 44 file**, mayoritas berbasis FQCN (`::class` / `get_class()`) — termasuk **engine workflow** (semua `*WorkflowService`), `BkuPostingService`, `BudgetRealizationService`, penyetoran pajak, `UserProvisioningService`, dashboard. Mengonversi data ke alias tanpa memperbaiki seluruhnya akan **memutus** kueri tsb (mis. `WorkflowInstance::where('workflowable_type', Tagihan::class)`).

*Jebakan tambahan:*
- `MasterPihak` punya subclass (`MasterMitraVendor`, `MasterPersonelEksternal`) + override `getMorphClass()` — sudah ditangani `2026_06_11_000000_normalize_master_pihak_morph_types.php`; **jangan** diubah.
- `model_has_roles.model_type = User` (Spatie) — alias salah = **role seluruh user hilang**.

**Jalan aman (2 fase, wajib teruji dengan suite MySQL):**
1. *Fase 1 (prasyarat):* refactor seluruh ~106 perbandingan `::class`/`get_class()` → `getMorphClass()` (behavior-preserving tanpa map).
2. *Fase 2:* tambah `Relation::morphMap([...])` (non-strict; **kecualikan** `User` & `MasterPihak`) + migrasi konversi data per kolom morf.

**Keputusan: DITUNDA.** Manfaat marginal rendah (FQCN + `getMorphClass()` override saat ini stabil selama namespace model tak diubah) vs risiko tinggi (refactor 100+ titik keuangan inti). Dampak skor hanya ~+3 (Database ~75 → ~78).
**Konvensi diadopsi:** kode baru selalu memakai `$model->getMorphClass()` untuk perbandingan tipe morf; refactor Fase 1 dijadikan inisiatif tersendiri bila ada kebutuhan rename/refactor model.

### DB-06 · `dipa_revision_items` kehilangan soft delete — Low
- **Bukti:** `database/migrations/2026_04_03_070500_drop_soft_deletes_from_dipa_revision_items.php`.
- **Risiko:** hapus item pagu menjadi permanen; histori struktur pagu pada revisi DIPA tak bisa direkonstruksi untuk audit.
- **Perbaikan:** `Schema::table('dipa_revision_items', fn ($t) => $t->softDeletes());` — atau dokumentasikan kebijakan tanpa-soft-delete + tabel log nilai pagu historis.

---

## D. Prioritas & estimasi kenaikan skor

| Prioritas | Temuan | Inti |
|---|---|---|
| **High** | DB-01, DB-08, DB-02 | Anti-dobel-posting keuangan di level DB |
| **Medium** | DB-03, DB-05, DB-07 | Duplikasi tagihan, audit trail, ketahanan morf |
| **Low** | DB-04, DB-06 | Tipe data & histori (kerapian) |

**Status implementasi (23 Jun 2026):**
- ✅ **Selesai & teruji (MySQL, 192 assertion lulus):** DB-01, DB-02, DB-03, DB-04, DB-05, DB-08 — migrasi `2026_06_23_100001..100003` + trait `App\Models\Concerns\Blameable` pada 8 model keuangan inti.
- ⏸️ **Ditunda (keputusan):** DB-07 (morph map — 106 titik FQCN + jebakan Spatie/subclass; lihat §C), DB-06 (membalik keputusan drop soft-delete — perlu keputusan produk).

Estimasi dampak ke kategori **Database**: baseline ~57 → **~75** (setelah 6 temuan di atas). Menutup DB-07 nanti → ~78. Kontribusi berbobot ke nilai akhir: **+~2,7 poin** (Database berbobot 15%).

> Catatan lintas-kategori: DI-04 (referensi NULL saat insert), DI-05 (aritmetika float atas kolom decimal),
> dan DI-06 (perubahan saldo via `saveQuietly` tanpa audit) tercatat di dimensi **Integritas Data Keuangan**,
> tetapi remediasinya bersinggungan dengan constraint DB di atas — disarankan dikerjakan bersamaan.

---

## E. Urutan implementasi yang disarankan

1. ✅ **DB hardening keuangan** (`2026_06_23_100001`): DB-01, DB-08, DB-02 (composite unique + FK `kode_transaksi`). Dijalankan dengan pindai duplikat/orphan lebih dulu (dev = 0).
2. ✅ **Audit & integritas** (`2026_06_23_100002`): DB-05 (`updated_by` + FK) + trait `Blameable`. **(`2026_06_23_100003`):** DB-03 (`nomor_meter` unique).
3. ✅ **Tipe data** (`2026_06_23_100003`): DB-04 (decimal meter). ⏸️ DB-06 (softDeletes) — ditunda.
4. ⏸️ **DB-07** (morph map) — ditunda; lihat §C (butuh refactor 2 fase + suite tes).
5. **DR (produksi):** `php artisan db:backup` → pindai duplikat → `php artisan migrate`. Bersihkan duplikat eksisting dulu agar unique tak gagal.

*Audit ini diverifikasi terhadap migrasi/model aktual. Sebelum menerapkan unique baru di produksi, pindai & bersihkan baris duplikat eksisting agar migrasi tidak gagal.*
