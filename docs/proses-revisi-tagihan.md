# Proses Revisi Tagihan (Kontrak, Perjaldin, Honorarium)

Catatan ini menjelaskan bagaimana **revisi** bekerja pada halaman **Proses Tagihan** terpadu untuk ketiga tipe tagihan: `KONTRAK`, `PERJALDIN`, dan `HONORARIUM`.

Sumber kebenaran kode:
- Controller: `app/Http/Controllers/TagihanProsesController.php` → method `aksiDokumen()`
- Service: `app/Services/DokumenChainService.php`

---

## Prinsip dasar

Dokumen rantai pencairan — **SPP → SPM → NPI → SP2D** — semuanya **di-generate dari data tagihan**. Karena itu tidak ada yang bisa "diperbaiki" di level dokumen.

Maka verifikator dokumen rantai hanya punya **dua aksi**:

| Aksi | Arti |
|------|------|
| **Approve** | Setujui step verifikasi dokumen saat ini. |
| **Revisi** | Kembalikan ke **akar masalah** (root cause). Tidak ada opsi "tolak permanen". |

Saat memilih **Revisi**, verifikator wajib memilih **`target`** (akar masalah). Inilah inti seluruh mekanisme revisi.

---

## Empat target revisi

```
revisi → target = tagihan | pajak | coa | bukti
```

| `target` | Berlaku untuk | Dikembalikan ke | Method service | Verifikasi tagihan diulang? |
|----------|---------------|-----------------|----------------|------------------------------|
| `tagihan` | Semua tipe | **Pembuat tagihan** | `returnChainToCreator` | **Ya** (6 verifikator ulang) |
| `pajak` | **KONTRAK saja** | **Operator BLU** | `rewindChainForCorrection` | Tidak |
| `coa` | Semua tipe | **PPK** | `rewindChainForCorrection` | Tidak |
| `bukti` | **SP2D saja** | **Bendahara Pengeluaran** | (langsung di controller) | Tidak |

---

## 1. `target = tagihan` — kembali ke pembuat (verifikasi ulang penuh)

Dipakai bila masalahnya ada di **substansi data tagihan** itu sendiri.

Alur (`returnChainToCreator`):

1. Verifikator mencentang **bagian** mana yang perlu direvisi, dengan **catatan wajib per bagian**. Bagian yang bisa dipilih (`RETURNABLE_PARTS`):
   - `tagihan` — Data Tagihan & Dokumen Pendukung
   - `spp` — SPP
   - `spm` — SPM
   - `npi` — NPI
2. Catatan revisi dicatat ke **log tiap dokumen** dulu (jejak tetap terbaca walau rantai dihapus).
3. **Seluruh rantai dokumen dibatalkan/dihapus** (`deleteChainDocuments`).
4. **Persetujuan KPA di-reset** (`kpa_approval_status`, `kpa_approved_at`, dll. → null).
5. Status tagihan menjadi **`REVISI_{role}`** sesuai role verifikator yang meminta:

   | Role verifikator | Status hasil |
   |------------------|--------------|
   | PPK | `REVISI_PPK` |
   | PPSPM | `REVISI_PPSPM` |
   | Bendahara Penerimaan | `REVISI_BENDAHARA_PENERIMAAN` |
   | Bendahara Pengeluaran | `REVISI_BENDAHARA_PENGELUARAN` |
   | Koordinator Keuangan | `REVISI_KOORDINATOR_KEUANGAN` |
   | Kepala Subbagian Keuangan & Tata Usaha | `REVISI_KASUBBAG` |

6. Pembuat memperbaiki data, lalu **mengajukan ulang** lewat alur verifikasi tagihan biasa → **diverifikasi ulang oleh 6 verifikator**.

> Status `REVISI_{role}` harus selalu ada di `allowedSubmitStatuses` workflow service tagihan (Kontrak/Perjaldin/Honorarium) agar pengajuan ulang tidak buntu.

---

## 2. `target = pajak` — perbaikan pajak & faktur (KONTRAK saja)

Hanya untuk **tagihan kontrak**. Bila dipilih pada perjaldin/honorarium → ditolak dengan pesan *"Perbaikan pajak hanya berlaku untuk tagihan kontrak."*

Alur (`rewindChainForCorrection`, marker `PAJAK`):

1. Catatan revisi **wajib diisi**.
2. Seluruh rantai dokumen dibatalkan.
3. Persetujuan KPA **di-reset** (Standing Instruction ke KPA memuat **nominal netto** yang berubah saat pajak berubah).
4. **Hasil verifikasi tagihan oleh 6 verifikator TIDAK diulang.** Status kembali ke siap-proses:
   - `READY_FOR_SPP` (kontrak/honorarium)
5. Penanda dipasang di tagihan: `chain_correction_target = 'PAJAK'` (+ catatan, requester, waktu). Banner muncul di kartu pajak (`_pajak_kontrak_card`).
6. **Operator BLU** memperbaiki pajak/faktur pajak. Saat disimpan (`simpanPajak`), penanda dibersihkan via `clearChainCorrection`.

---

## 3. `target = coa` — perbaikan pembebanan COA (semua tipe)

Alur sama dengan pajak (`rewindChainForCorrection`, marker `COA`), tapi dikembalikan ke **PPK**:

1. Catatan revisi wajib diisi.
2. Rantai dibatalkan, KPA di-reset, verifikasi tagihan **tidak** diulang.
3. Status kembali ke siap-proses:
   - `READY_FOR_SPP` (kontrak/honorarium)
   - `DISETUJUI_PERJALDIN` (perjaldin)
4. Penanda `chain_correction_target = 'COA'`. Banner muncul di kartu COA (`_coa_card`).
5. **PPK** memperbaiki pembebanan COA. Saat disimpan (`simpanCoa`), penanda dibersihkan via `clearChainCorrection`.

---

## 4. `target = bukti` — ganti bukti transfer (SP2D saja)

Hanya saat verifikasi **SP2D**. Bila dipilih pada dokumen lain → ditolak *"Perbaikan bukti transfer hanya berlaku pada SP2D."*

Alur (langsung di controller, **tanpa** membatalkan rantai):

1. Catatan revisi wajib diisi.
2. Hanya **SP2D** yang berubah status → `REVISI`.
3. **Bendahara Pengeluaran** mengunggah ulang bukti transfer (form `_bukti_transfer_card` terbuka kembali saat SP2D `REVISI`).

> Ini satu-satunya substansi SP2D yang diperbaiki manual. Rantai, verifikasi tagihan, dan KPA **tidak** terpengaruh.

---

## Perbedaan antar tipe tagihan

| Hal | KONTRAK | PERJALDIN | HONORARIUM |
|-----|---------|-----------|------------|
| Target `tagihan` | ✅ | ✅ | ✅ |
| Target `pajak` | ✅ | ❌ (ditolak) | ❌ (ditolak) |
| Target `coa` | ✅ | ✅ | ✅ |
| Target `bukti` (SP2D) | ✅ | ✅ | ✅ |
| Status siap-proses setelah rewind pajak/coa | `READY_FOR_SPP` | `DISETUJUI_PERJALDIN` | `READY_FOR_SPP` |
| Sinkronisasi komponen | – | `komponenPerjaldin->syncStatusFromDocuments()` dipanggil di tiap jalur | – |
| Workflow rantai | `SPP_KONTRAK_PPK`, `SPM_KONTRAK_PPSPM`, `NPI_KONTRAK`, `SP2D_KONTRAK` | `SPP_PERJALDIN`, `SPM_PERJALDIN_PPSPM`, `NPI_PERJALDIN`, `SP2D_PERJALDIN` | `SPP_HONORARIUM_PPK`, `SPM_HONORARIUM_PPSPM`, `NPI_HONORARIUM`, `SP2D_HONORARIUM` |

---

## Pengaman (guard) penting

- **SP2D sudah EXECUTED → revisi diblokir.** `returnChainToCreator` dan `rewindChainForCorrection` melempar error *"…tidak dapat dikembalikan karena SP2D sudah terbit."*
- **Approval harus milik user.** Hanya approval ber-status `PENDING` milik user/role-nya yang boleh diproses.
- **KPA selalu di-reset di setiap jalur rewind** (tagihan/pajak/coa) karena Standing Instruction WA ke KPA memuat nominal netto & sumber dana yang berubah saat pajak/COA berubah.
- Selama `chain_correction_target` terisi, pengajuan SI ke KPA **ditahan** (`KpaApprovalController::sendWa` & kartu KPA). Aksi via magic link KPA lama ditolak bila `kpa_approval_status !== 'PENDING_KPA'` atau tagihan belum approved.

---

## Ringkasan satu layar

```
Verifikator dokumen rantai (SPP/SPM/NPI/SP2D)
      │
      ├── Approve ─────────────► lanjut step berikutnya
      │
      └── Revisi (pilih target)
            ├── tagihan → returnChainToCreator
            │             → rantai dibatalkan, KPA reset,
            │               tagihan = REVISI_{role},
            │               VERIFIKASI ULANG 6 verifikator
            │
            ├── pajak (KONTRAK) → rewindChainForCorrection(PAJAK)
            │             → rantai dibatalkan, KPA reset,
            │               status = READY_FOR_SPP,
            │               Operator BLU perbaiki pajak,
            │               TANPA verifikasi ulang tagihan
            │
            ├── coa → rewindChainForCorrection(COA)
            │             → rantai dibatalkan, KPA reset,
            │               status = READY_FOR_SPP / DISETUJUI_PERJALDIN,
            │               PPK perbaiki COA,
            │               TANPA verifikasi ulang tagihan
            │
            └── bukti (SP2D) → SP2D = REVISI
                          → Bendahara Pengeluaran unggah ulang bukti transfer,
                            rantai & verifikasi tagihan TIDAK terpengaruh
```
