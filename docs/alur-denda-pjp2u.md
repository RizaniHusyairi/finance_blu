# Alur Jatuh Tempo & Denda — PJP2U dan Tagihan Jasa Lain

Dokumen ini menjelaskan aturan jatuh tempo dan denda keterlambatan untuk tagihan
jasa, dengan penekanan pada **PJP2U** (Pelayanan Jasa Penumpang Pesawat Udara).

## 1. Ringkasan aturan

| | Jatuh tempo | Denda keterlambatan |
|---|---|---|
| **PJP2U** | **7 hari** sejak publish (khusus, lebih ketat) | 2% per 30 hari |
| Tagihan jasa lain | 30 hari sejak publish (umumnya) | 2% per 30 hari |

> Yang membedakan PJP2U hanyalah **jatuh tempo (7 hari)**. Rumus dendanya **sama**
> untuk semua jenis tagihan jasa: **2% per periode 30 hari**.

## 2. Aturan denda

- Denda dikenakan **begitu lewat jatuh tempo** (telat ≥ 1 hari) sebesar **2%**.
- Denda **datar 2% selama 30 hari pertama** keterlambatan — tidak bertambah tiap hari.
- Setelah **genap 30 hari** telat (masuk hari ke-31), denda **bertambah 2% lagi → 4%**.
- Seterusnya **+2% setiap 30 hari**, **terus berjalan tanpa pembekuan** sampai dibayar.

Rumus: `denda = 2% × total_tagihan × ceil(hari_terlambat ÷ 30)`

## 3. Garis waktu PJP2U (contoh total Rp1.000.000)

```
            PJP2U · jatuh tempo 7 hari · denda 2% per 30 hari (akumulasi)
════════════════════════════════════════════════════════════════════════════

  Terbit                 Jatuh tempo
  tagihan                (H7)
  H0                      │
  ●────────7 hari─────────●━━━━━━━━━━━━━━ KETERLAMBATAN ━━━━━━━━━━━━━━━━▶
  │   belum jatuh tempo   │          │           │           │
  │      DENDA Rp0        │ telat    │ telat      │ telat     │ …tiap
  │                       │ 1–30 hr  │ 31–60 hr   │ 61–90 hr  │ +30 hr
  │                       ▼          ▼            ▼           ▼
  │                      +2%        +2%          +2%         +2%
  │                     = 2%       = 4%         = 6%        = 8%…
  │                   Rp20.000   Rp40.000     Rp60.000   Rp80.000
```

## 4. Tangga denda (datar lalu naik bertahap)

```
 Denda
  8% ┤                                      ┌────────
  6% ┤                          ┌───────────┘
  4% ┤             ┌────────────┘
  2% ┤┌────────────┘
  0% ┴┼──────┬─────────────┬─────────────┬─────────────┬──── telat (hari)
     0│      30            60            90            120
      └─ begitu lewat tempo (telat 1 hari) → LANGSUNG 2%
         lalu naik +2% tiap genap 30 hari berikutnya
```

## 5. Contoh nominal (total Rp1.000.000)

| Keterlambatan | Periode | Denda | Total bayar |
|---|--:|--:|--:|
| belum jatuh tempo | 0 | Rp0 | Rp1.000.000 |
| telat 1–30 hari | 1 | 2% = Rp20.000 | Rp1.020.000 |
| telat 31–60 hari | 2 | 4% = Rp40.000 | Rp1.040.000 |
| telat 61–90 hari | 3 | 6% = Rp60.000 | Rp1.060.000 |
| telat 181–210 hari | 7 | 14% = Rp140.000 | Rp1.140.000 |

## 6. Kualitas piutang (label, TIDAK menghentikan denda)

Mengikuti tangga baku piutang pemerintah (lihat `App\Services\Pembukuan\PiutangAgingService`):

```
 Umur telat:  0 ─────── 90 ──────── 180 ───────────────▶
              │          │            │
 Kualitas  : Lancar  Kurang Lancar  Diragukan      MACET
             (0 hr)   (1–90 hr)     (91–180 hr)   (>180 hr)

 Denda     :  ◀───── tetap +2% tiap 30 hari di SEMUA tahap ─────▶
```

| Umur tunggakan | Kualitas |
|---|---|
| 0 hari (belum jatuh tempo / lunas) | Lancar |
| 1–90 hari | Kurang Lancar |
| 91–180 hari | Diragukan |
| > 180 hari | **Macet** |

"Macet" hanya label kualitas piutang; denda **tetap berjalan**, tidak berhenti.

## 7. Catatan teknis

**Jatuh tempo** — disnapshot saat publish dari master layanan
(`App\Services\TagihanJasaPublishService::resolveDueDateData`):
- `LayananJasa.jumlah_hari_jatuh_tempo` → `tagihan_jasas.jumlah_hari_jatuh_tempo`
  (PJP2U = 7, umumnya layanan lain = 30).

**Denda & kualitas** — accessor di `App\Models\TagihanJasa`:
- `periode_denda_hari` = `masa_denda_hari ?: 30` (panjang satu periode denda).
- `jumlah_periode_denda` = `ceil(hari_terlambat ÷ periode_denda_hari)`.
- `nominal_denda_keterlambatan` = `total_tagihan × 0.02 × jumlah_periode_denda`.
- `total_dengan_denda`, `sisa_tagihan_berjalan` — total/sisa termasuk denda berjalan.
- `kualitas_piutang` / `is_macet` / `status_jatuh_tempo` (= `MACET` saat umur > 180 hari).

**Kolom** `masa_denda_hari` (periode denda) ada di `layanan_jasas` dan `tagihan_jasas`,
ditambahkan oleh migration `2026_06_18_000008_add_masa_denda_hari_to_jasa_tables.php`.
PJP2U = 30; layanan lain `0` → memakai default 30 di accessor (denda tetap seragam).

**Seeder demo**:
- PJP2U saja: `DemoPjp2uJatuhTempoDendaSeeder`
  (`php artisan db:seed --class=DemoPjp2uJatuhTempoDendaSeeder`).
- PJP2U + tagihan jasa lain (menunjukkan beda jatuh tempo 7 vs 30 hari dengan denda
  seragam): `DemoJatuhTempoDendaSeeder`
  (`php artisan db:seed --class=DemoJatuhTempoDendaSeeder`). Idempoten — memakai nomor
  tagihan stabil `TAG-PJP2U-DEMO-*` dan `TAG-LAIN-DEMO-*`.
