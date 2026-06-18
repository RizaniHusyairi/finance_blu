# Rencana: Kontrak Wajib untuk Semua Layanan Mitra

Status: **SELESAI diimplementasikan (2026-06-19)** — lihat bagian "Status implementasi" di bawah.

## Keputusan yang disepakati
1. **Semua layanan wajib punya dasar dokumen (kontrak)** — tanpa pengecualian. Jenis menyesuaikan via `kontrak_mitra_jasa.jenis_dokumen`:
   - Komersial (sewa, reklame, workshop, kendaraan) → **Kontrak/Perjanjian**
   - Konsesi → **Perjanjian Konsesi**
   - PJP2U / JKP2U regulatori → **SK / Regulasi Tarif**
   - One-off (studi lapangan, shooting, garbarata insidental) → **SPK / Berita Acara**
2. **Data lama** → auto-buat **"Kontrak Legacy"** per mitra dari pool layanan saat ini (status perlu dilengkapi).
3. **Penegakan** → **peringatan dulu (grace)**, belum blok keras.

## Alur target
```
Mitra → Kontrak (jenis dok + layanan tercakup + periode + nilai/diskon)
      → pool layanan mitra = union layanan dari kontrak AKTIF (otomatis/turunan)
      → Buat tagihan: layanan di luar kontrak aktif → ⚠️ peringatan (masih boleh lanjut)
      → jatuh tempo & denda (seperti sekarang)
```

## ⚠️ Masalah desain yang harus dipecah dulu: sirkularitas
Saat ini pilihan layanan ketika **membuat kontrak** diambil dari **pool mitra**
(`KontrakMitraJasaController::layanansForMitra` → `$mitra->layananJasaAktif()`).
Kalau pool jadi *turunan* kontrak, maka kontrak pertama tidak punya sumber pilihan (sirkular).

**Solusi:** sumber pilihan layanan saat buat kontrak diganti ke **master layanan aktif (leaf)**,
bukan pool mitra. Pool mitra (`mitra_jasa_layanan`) berubah peran jadi **hasil turunan** dari
kontrak aktif (atau di-recompute setiap kontrak disimpan).

## Langkah implementasi (bertahap, tanpa downtime karena grace)

### Fase 0 — Persiapan data & enum
- Audit semua pemakaian `mitra_jasa_layanan` / `layananJasaAktif()` (controller tagihan, laporan, dsb) agar tidak ada yang putus.
- Tambah daftar `jenis_dokumen` (enum/select + label) di form & validasi kontrak.
- **Migrasi/command "Kontrak Legacy"**: untuk tiap mitra yang punya `mitra_jasa_layanan`, buat 1 `KontrakMitraJasa` (`nomor_kontrak = LEGACY-<kode>`, `status_kontrak = DRAFT`/perlu dilengkapi, periode terbuka), `sync` layananJasa = pool sekarang.

### Fase 1 — Kontrak jadi sumber kebenaran
- `layanansForMitra` → ambil dari **master layanan aktif (leaf)**, bukan pool mitra.
- Pool mitra (`mitra_jasa_layanan`) di-recompute = union layanan kontrak aktif setiap kontrak disimpan/diubah (atau diganti accessor turunan).

### Fase 2 — UI mitra
- Tombol aksi **"Atur Layanan"** → diganti **"Kelola Kontrak"** (atau jadikan halaman layanan **read-only** yang menampilkan "layanan tercakup kontrak aktif").
- Daftar mitra: **"Kontrak Aktif"** jadi indikator utama + badge **"ada layanan tanpa kontrak aktif"**.

### Fase 3 — Penegakan grace di buat-tagihan
- Saat pilih layanan di Buat Tagihan: tandai layanan yang **di luar kontrak aktif** dengan ⚠️ + tampilkan kontrak yang mencakup. Tetap boleh lanjut (grace). Catat di log.

### Fase 4 — PJP2U & Konsesi wajib kontrak
- `MitraJasaPjp2u.kontrak_mitra_jasa_id` & `MitraJasaKonsesi.kontrak_mitra_jasa_id` → wajib (form harus pilih kontrak; PJP2U pakai jenis SK). Backfill ke kontrak legacy.

### Fase 5 — Seeder/demo & dokumentasi
- Pastikan mitra demo (CITILINK, PT ABC/ABCD) punya kontrak; seeder tagihan demo mereferensi kontrak. Perbarui `docs/demo-modul-jasa-lengkap.md`.

## Risiko & catatan
- Sirkularitas pool↔kontrak → diselesaikan di Fase 1 (kontrak ambil dari master).
- Legacy kontrak `DRAFT` → memicu peringatan grace saat menagih sampai dilengkapi admin. Itu disengaja (mendorong pelengkapan).
- Karena penegakan grace, semua fase bisa dirilis bertahap tanpa memblok operasional.

## Estimasi
Sedang — beberapa controller + 1–2 migrasi + penyesuaian view & seeder. Aman dikerjakan per fase.

---

## Status implementasi (2026-06-19) — SELESAI

- **Fase 0** — `jenis_dokumen` dicentralkan ke `KontrakMitraJasa::JENIS_DOKUMEN` (+ Perjanjian Konsesi, SK/Regulasi Tarif, SPK). Migrasi `2026_06_19_000001_create_legacy_kontrak_from_pool` membuat Kontrak Legacy (AKTIF, `LEGACY-*`) untuk mitra yang punya pool tanpa kontrak. Hasil: pool ⊆ kontrak terpenuhi.
- **Fase 1** — `KontrakMitraJasaController::layanansForMitra`/`syncLayanan` ambil dari **master** (memecah sirkularitas). Pool (`mitra_jasa_layanan`) jadi **turunan** kontrak aktif via `MitraLayananService::syncFromKontrak`, dipanggil saat kontrak store/update/destroy.
- **Fase 2** — Daftar mitra: aksi "Atur Layanan" → **"Kelola Kontrak"** + badge **"Legacy perlu dilengkapi"**. `MitraLayananController` (editor manual) dipensiunkan (redirect).
- **Fase 3** — `TagihanJasaController::kontrakGraceNote` memberi **peringatan grace** (tanpa kontrak / kontrak legacy) di create & update tagihan. (Layanan di luar kontrak aktif sudah ter-blok keras oleh cek pool yang ada.)
- **Fase 4** — Konsesi & PJP2U **wajib kontrak** (form + validasi `required`). Migrasi `2026_06_19_000002_backfill_kontrak_for_konsesi_pjp2u` menautkan data lama. **`nilai_kontrak` dihapus** dari mitra jasa (model + tampilan).
- **Fase 5** — `DemoJatuhTempoDendaSeeder` membuat kontrak demo (SK PJP2U + Kontrak layanan lain), pool turunan, tagihan demo tertaut kontrak.

Catatan: kolom DB `kontrak_mitra_jasa.nilai_kontrak` & `tagihan_jasas.masa_denda_hari` dibiarkan (tak mengganggu). Penegakan "wajib kontrak" pada konsesi/pjp2u di lapisan validasi (kolom tetap nullable agar aman).
