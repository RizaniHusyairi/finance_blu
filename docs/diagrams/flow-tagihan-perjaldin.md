# Alur Proses Tagihan Perjalanan Dinas (Perjaldin)

> Dokumentasi alur lengkap dari pembuatan SPT/perjaldin oleh Operator Perjaldin
> hingga dana cair ke pegawai dan tercatat di BKU.
>
> Konsisten format dengan dokumen
> [`flow-tagihan-kontrak.md`](flow-tagihan-kontrak.md) — pakai **Mermaid**:
> tampil di GitHub/VS Code, versi-able di Git, dan bisa di-import ke Draw.io
> via *Arrange → Insert → Advanced → Mermaid*.
>
> **Sumber kode**:
> - `app/Http/Controllers/PerjaldinController.php`
> - `app/Http/Controllers/PerjaldinVerifikasiController.php`
> - `app/Services/PerjaldinWorkflowService.php` (`syncTagihanStatus()`)
> - `database/seeders/SppPerjaldinWorkflowSeeder.php`
> - `database/seeders/WorkflowDefinitionSeeder.php` (SPM/NPI/SP2D Perjaldin)
> - `resources/views/verifikasi_perjaldin/partials/status-badge.blade.php` (pemetaan status)

---

## Daftar Isi

1. [Phase Map (overview 6 fase)](#1-phase-map)
2. [Fase 1 — Pembuatan Perjaldin oleh Operator](#2-fase-1--pembuatan-perjaldin)
3. [Fase 2 — Verifikasi Perjaldin (Step 1 paralel + Kasubbag)](#3-fase-2--verifikasi-perjaldin)
4. [Fase 3 — Upload Nominatif TTD (gate sebelum SPP)](#4-fase-3--upload-nominatif-ttd)
5. [Fase 4 — SPP Perjaldin](#5-fase-4--spp-perjaldin)
6. [Fase 5 — SPM Perjaldin](#6-fase-5--spm-perjaldin)
7. [Fase 6 — NPI → SP2D → BKU](#7-fase-6--npi--sp2d--bku)
8. [State Machine Status Tagihan Perjaldin](#8-state-machine--status-tagihan-perjaldin)
9. [Sequence Diagram — Verifikasi Paralel + Kasubbag](#9-sequence-diagram--verifikasi-paralel)
10. [Tabel Referensi](#10-tabel-referensi)
11. [Glosarium](#11-glosarium)
12. [Perbedaan dengan Tagihan Kontrak](#12-perbedaan-dengan-tagihan-kontrak)

---

## 1. Phase Map

```mermaid
flowchart LR
    classDef phase fill:#dbeafe,stroke:#1d4ed8,color:#1e3a8a,stroke-width:2px,rx:10,ry:10;
    classDef gate  fill:#fef3c7,stroke:#b45309,color:#7c2d12,stroke-width:2px;
    classDef actor fill:#fef9c3,stroke:#a16207,color:#713f12;
    classDef sys   fill:#dcfce7,stroke:#15803d,color:#14532d;

    P1["FASE 1<br/>Pembuatan Perjaldin<br/><sub>SPT, Tiket, Komponen</sub>"]:::phase
    P2["FASE 2<br/>Verifikasi Tagihan<br/><sub>5 verifikator + Kasubbag</sub>"]:::phase
    P3["FASE 3<br/>Upload Nominatif TTD<br/><sub>Operator unggah TTD basah</sub>"]:::gate
    P4["FASE 4<br/>SPP Perjaldin<br/><sub>PPK · Koor.Keu · Kasubbag</sub>"]:::phase
    P5["FASE 5<br/>SPM Perjaldin<br/><sub>PPSPM · Kasubbag · Koor.Keu</sub>"]:::phase
    P6["FASE 6<br/>NPI → SP2D → BKU<br/><sub>Bend. Pengeluaran cair</sub>"]:::phase

    A1[["Operator Perjaldin"]]:::actor --> P1
    A2[["5 Verifikator + Kasubbag"]]:::actor --> P2
    A3[["Operator Perjaldin"]]:::actor --> P3
    A4[["Operator BLU"]]:::actor --> P4
    A5[["Operator BLU + PPSPM"]]:::actor --> P5
    A6[["Bendahara Pengeluaran"]]:::actor --> P6

    P1 --> P2 --> P3 --> P4 --> P5 --> P6
    P6 --> SYS[["Sistem<br/>Catat BKU + Pencairan ke pegawai"]]:::sys
```

> Fase 3 punya warna kuning (gate) karena merupakan **gating manual** —
> tagihan menunggu Operator mengunggah dokumen sebelum pencairan dimulai.

---

## 2. Fase 1 — Pembuatan Perjaldin

```mermaid
flowchart TD
    classDef start fill:#dcfce7,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef draft fill:#fed7aa,stroke:#c2410c,color:#7c2d12;
    classDef step  fill:#dbeafe,stroke:#1d4ed8,color:#1e3a8a;
    classDef gate  fill:#fef9c3,stroke:#a16207,color:#713f12;
    classDef final fill:#bbf7d0,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef doc   fill:#fef3c7,stroke:#b45309,color:#7c2d12;

    S(["Mulai<br/>(Operator Perjaldin)"]):::start
    A1["1 · Pilih COA / DIPA Item<br/>(budget group)"]:::step
    A2["2 · Tentukan tujuan, periode,<br/>dan provinsi"]:::step
    A3["3 · Tambah peserta (per pegawai)<br/>+ upload SPT &amp; tiket"]:::step
    A4["4 · Hitung uang harian dari<br/>master_uang_harian_perjaldin"]:::step
    A5["5 · Set verifikator (PPK, PPSPM,<br/>Bend×2, Koor.Keu, Kasubbag)"]:::step
    A6["6 · Simpan draft<br/><b>tagihan.status = DRAFT</b>"]:::draft

    G{"Lanjut ajukan?"}:::gate
    A7["7 · Submit ke workflow<br/><i>(bisa per item atau bulk)</i><br/><b>PENDING_PPSPM / PPK / Bend... / Koor</b>"]:::final

    DOC1[/"SPT — Surat Perintah Tugas<br/>(per peserta)"/]:::doc
    DOC2[/"Tiket / dokumen perjalanan"/]:::doc
    DOC3[/"Rekap komponen biaya:<br/>Uang Harian · Transport ·<br/>Penginapan · Lainnya"/]:::doc

    S --> A1 --> A2 --> A3 --> A4 --> A5 --> A6 --> G
    G -- belum --> A6
    G -- ya --> A7

    A3 -.unggah.-> DOC1
    A3 -.unggah.-> DOC2
    A4 -.menghasilkan.-> DOC3
```

> **Validasi** (`PerjaldinController::store` & `update`):
> - Tagihan boleh diedit hanya jika status ada di `editablePerjaldinStatuses()` (DRAFT / REVISI_*).
> - Setiap perubahan saat edit me-reset status ke `DRAFT` agar workflow ulang dari awal.
> - File SPT & tiket disimpan di `storage/app/public/perjaldin/{spt,tiket}`.
> - `bulkSubmit()` mendukung pengajuan banyak tagihan sekaligus, error per-item tidak menggugurkan yang lain.

---

## 3. Fase 2 — Verifikasi Perjaldin

Workflow code `PERJALDIN` di `SppPerjaldinWorkflowSeeder`. Step 1 = 5 verifikator
paralel (semua wajib approve), Step 2 = Kasubbag finalisasi.

```mermaid
flowchart LR
    classDef start  fill:#dcfce7,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef gate   fill:#fef9c3,stroke:#a16207,color:#713f12;
    classDef role   fill:#dbeafe,stroke:#1d4ed8,color:#1e3a8a;
    classDef ok     fill:#bbf7d0,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef rev    fill:#fde68a,stroke:#b45309,color:#7c2d12;
    classDef rej    fill:#fecaca,stroke:#b91c1c,color:#7f1d1d;
    classDef kasubg fill:#e9d5ff,stroke:#6b21a8,color:#3b0764;

    S(["Submit Tagihan<br/>(DRAFT → workflow)"]):::start
    GS{{"+"}}:::gate

    R1["PPSPM"]:::role
    R2["Bendahara<br/>Penerimaan"]:::role
    R3["Bendahara<br/>Pengeluaran"]:::role
    R4["PPK"]:::role
    R5["Koordinator<br/>Keuangan"]:::role

    GE{{"+"}}:::gate
    K["Kasubbag<br/>Finalisasi (Step 2)"]:::kasubg
    GATE_TTD["MENUNGGU_UPLOAD_<br/>NOMINATIF_TTD<br/>(lanjut ke Fase 3)"]:::ok

    REV["REVISI_*<br/>kembali ke<br/>Operator Perjaldin"]:::rev
    REJ["DITOLAK_*<br/>final"]:::rej
    REVK["REVISI_KASUBBAG"]:::rev
    REJK["DITOLAK_KASUBBAG"]:::rej

    S --> GS
    GS --> R1 & R2 & R3 & R4 & R5
    R1 -- approve --> GE
    R2 -- approve --> GE
    R3 -- approve --> GE
    R4 -- approve --> GE
    R5 -- approve --> GE

    R1 -. revisi .-> REV
    R2 -. revisi .-> REV
    R3 -. revisi .-> REV
    R4 -. revisi .-> REV
    R5 -. revisi .-> REV
    R1 -. reject .-> REJ
    R2 -. reject .-> REJ
    R3 -. reject .-> REJ
    R4 -. reject .-> REJ
    R5 -. reject .-> REJ

    GE --> K
    K -- approve --> GATE_TTD
    K -. revisi .-> REVK
    K -. reject .-> REJK
```

> **Aturan workflow** (`PerjaldinWorkflowService`):
> - Saat resubmit dari `REVISION`, semua approval di-reset (`urutan_step = 1` → PENDING, sisanya WAITING) dan instance dipindah ke `IN_PROGRESS`.
> - Step 2 baru aktif jika **seluruh** approval Step 1 berstatus `APPROVED`.
> - Kalau ada satu role yang `REVISION` atau `REJECTED`, instance langsung pindah status dan tidak menunggu role lain.

---

## 4. Fase 3 — Upload Nominatif TTD

Setelah Kasubbag approve, status tagihan **bukan langsung** `DISETUJUI_PERJALDIN`.
Operator Perjaldin harus mengunggah **dua dokumen TTD basah**:

```mermaid
flowchart LR
    classDef wait  fill:#fef3c7,stroke:#b45309,color:#7c2d12,stroke-width:2px;
    classDef step  fill:#dbeafe,stroke:#1d4ed8,color:#1e3a8a;
    classDef ok    fill:#bbf7d0,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef gate  fill:#fef9c3,stroke:#a16207,color:#713f12;
    classDef doc   fill:#fef9c3,stroke:#d97706,color:#7c2d12;

    K[(Kasubbag approve)]
    W["status =<br/>MENUNGGU_UPLOAD_<br/>NOMINATIF_TTD"]:::wait

    OP1["Operator Perjaldin:<br/>cetak Nominatif &amp; Lampiran"]:::step
    OP2["Tanda tangan basah<br/>oleh pejabat berwenang"]:::step
    OP3["Scan &amp; upload kembali<br/>ke arsip tagihan"]:::step

    DOC1[/"NOMINATIF_TTD"/]:::doc
    DOC2[/"LAMPIRAN_TTD"/]:::doc

    G{"Kedua dokumen<br/>sudah lengkap?"}:::gate
    DONE["status = DISETUJUI_PERJALDIN<br/>siap dibuatkan SPP"]:::ok

    K --> W --> OP1 --> OP2 --> OP3
    OP3 --> DOC1
    OP3 --> DOC2
    DOC1 --> G
    DOC2 --> G
    G -- belum --> OP3
    G -- ya --> DONE
```

> **Cek di `PerjaldinController::uploadNominatifTtd`** + helper
> `PerjaldinWorkflowService::hasNominatifTtdComplete($tagihan)`:
> - Setiap upload menonaktifkan arsip lama (`is_active = false`) dan mengaktifkan yang baru.
> - Jika kedua jenis (`NOMINATIF_TTD` + `LAMPIRAN_TTD`) aktif → status berubah ke
>   `DISETUJUI_PERJALDIN` dan Operator BLU akan mendapat notifikasi via
>   `TagihanReadyForSppNotificationService`.
> - Re-upload tetap diizinkan walau status sudah `DISETUJUI_PERJALDIN` (versi terbaru menggantikan).

---

## 5. Fase 4 — SPP Perjaldin

Operator BLU membuat SPP perjaldin per komponen biaya. Workflow code `SPP_PERJALDIN`:
3 verifikator paralel.

```mermaid
flowchart LR
    classDef step  fill:#bfdbfe,stroke:#1d4ed8,color:#1e3a8a;
    classDef role  fill:#dbeafe,stroke:#1d4ed8,color:#1e3a8a;
    classDef ok    fill:#bbf7d0,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef gate  fill:#fef9c3,stroke:#a16207,color:#713f12;

    T1["Tagihan<br/>DISETUJUI_PERJALDIN"]:::ok
    OB1["7 · Operator BLU<br/>set COA per komponen"]:::step
    OB2["8 · Buat SPP per komponen<br/>(DRAFT)"]:::step
    OB3["9 · Submit SPP ke<br/>workflow verifikasi"]:::step

    GS{{"+"}}:::gate
    V1["PPK"]:::role
    V2["Koordinator<br/>Keuangan"]:::role
    V3["Kasubbag"]:::role
    GE{{"+"}}:::gate

    OB4["10 · Cetak SPP &amp;<br/>upload SPP bertanda tangan"]:::step
    SPPOK["SPP_TERBIT"]:::ok

    T1 --> OB1 --> OB2 --> OB3 --> GS
    GS --> V1 & V2 & V3
    V1 --> GE
    V2 --> GE
    V3 --> GE
    GE --> OB4 --> SPPOK
```

> Komponen perjaldin (uang harian, transport, penginapan, lainnya) diberi COA
> berbeda → biasanya menghasilkan **beberapa SPP** dari satu tagihan (per komponen).

---

## 6. Fase 5 — SPM Perjaldin

Workflow `SPM_PERJALDIN_PPSPM`: PPSPM, Kasubbag, Koor.Keu (paralel).

```mermaid
flowchart LR
    classDef step fill:#fed7aa,stroke:#c2410c,color:#7c2d12;
    classDef role fill:#dbeafe,stroke:#1d4ed8,color:#1e3a8a;
    classDef ok   fill:#bbf7d0,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef gate fill:#fef9c3,stroke:#a16207,color:#713f12;

    SPP["SPP_TERBIT"]:::ok
    OB1["11 · Operator BLU buat<br/>SPM Perjaldin"]:::step
    OB2["12 · Submit SPM<br/>ke workflow"]:::step

    GS{{"+"}}:::gate
    V1["PPSPM"]:::role
    V2["Kasubbag"]:::role
    V3["Koordinator<br/>Keuangan"]:::role
    GE{{"+"}}:::gate

    OB3["13 · Upload SPM<br/>bertanda tangan"]:::step
    SPMOK["SPM_TERBIT"]:::ok

    SPP --> OB1 --> OB2 --> GS
    GS --> V1 & V2 & V3
    V1 --> GE
    V2 --> GE
    V3 --> GE
    GE --> OB3 --> SPMOK
```

---

## 7. Fase 6 — NPI → SP2D → BKU

Workflow `NPI_PERJALDIN` & `SP2D_PERJALDIN` masing-masing punya 4 verifikator paralel.

```mermaid
flowchart TD
    classDef bp    fill:#dcfce7,stroke:#15803d,color:#14532d;
    classDef role  fill:#dbeafe,stroke:#1d4ed8,color:#1e3a8a;
    classDef ok    fill:#bbf7d0,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef gate  fill:#fef9c3,stroke:#a16207,color:#713f12;
    classDef sys   fill:#e0e7ff,stroke:#4338ca,color:#312e81;

    SPM["SPM_TERBIT"]:::ok

    %% NPI Perjaldin
    BP1["14 · Bend. Pengeluaran<br/>buat NPI Perjaldin (draft)"]:::bp
    BP2["15 · Submit NPI<br/>ke workflow"]:::bp

    GN{{"+ NPI"}}:::gate
    NV1["Bend. Penerimaan"]:::role
    NV2["PPK"]:::role
    NV3["Kasubbag"]:::role
    NV4["Koordinator Keuangan"]:::role
    GNE{{"+"}}:::gate

    BP3["16 · Upload NPI<br/>bertanda tangan"]:::bp
    NPIOK["NPI_TERBIT"]:::ok

    %% SP2D Perjaldin
    BP4["17 · Bend. Pengeluaran<br/>buat SP2D Perjaldin (draft)"]:::bp
    BP5["18 · Submit SP2D<br/>ke workflow"]:::bp

    GS{{"+ SP2D"}}:::gate
    SV1["PPSPM"]:::role
    SV2["PPK"]:::role
    SV3["Kasubbag"]:::role
    SV4["Koordinator Keuangan"]:::role
    GSE{{"+"}}:::gate

    BP6["19 · Upload SP2D<br/>bertanda tangan"]:::bp
    SP2D["SP2D_TERBIT"]:::ok

    EXEC["20 · Eksekusi SP2D<br/>(transfer ke rekening pegawai)"]:::sys
    BKU["21 · Catat BKU &amp; Buku<br/>Pembantu Bank/Pajak"]:::sys
    DONE(["Selesai"]):::ok

    SPM --> BP1 --> BP2 --> GN
    GN --> NV1 & NV2 & NV3 & NV4
    NV1 --> GNE
    NV2 --> GNE
    NV3 --> GNE
    NV4 --> GNE
    GNE --> BP3 --> NPIOK --> BP4 --> BP5 --> GS
    GS --> SV1 & SV2 & SV3 & SV4
    SV1 --> GSE
    SV2 --> GSE
    SV3 --> GSE
    SV4 --> GSE
    GSE --> BP6 --> SP2D --> EXEC --> BKU --> DONE
```

> Beda dengan kontrak: **tidak ada termin** — pencairan langsung ke rekening
> pegawai (yang tercatat di `master_pegawai`) sebesar total perjaldin yang disetujui.

---

## 8. State Machine — Status Tagihan Perjaldin

Diturunkan dari `PerjaldinWorkflowService::syncTagihanStatus()` + status badge view.

```mermaid
stateDiagram-v2
    direction LR

    [*] --> DRAFT : create()

    DRAFT --> PENDING_VERIFIKASI_PERJALDIN : submit / bulkSubmit

    PENDING_VERIFIKASI_PERJALDIN --> REVISI_VERIFIKATOR : ada yang minta revisi
    PENDING_VERIFIKASI_PERJALDIN --> DITOLAK_VERIFIKATOR : ada yang reject
    REVISI_VERIFIKATOR --> DRAFT : Operator perbaiki

    PENDING_VERIFIKASI_PERJALDIN --> PENDING_KASUBBAG : semua verifikator approve

    PENDING_KASUBBAG --> REVISI_KASUBBAG : request revisi
    PENDING_KASUBBAG --> DITOLAK_KASUBBAG : reject
    REVISI_KASUBBAG --> DRAFT : perbaiki

    PENDING_KASUBBAG --> MENUNGGU_UPLOAD_NOMINATIF_TTD : approve

    MENUNGGU_UPLOAD_NOMINATIF_TTD --> DISETUJUI_PERJALDIN : kedua dokumen TTD lengkap

    DISETUJUI_PERJALDIN --> SPP_TERBIT : Operator BLU buat SPP &amp; signed
    SPP_TERBIT --> SPM_TERBIT : SPM signed
    SPM_TERBIT --> NPI_TERBIT : NPI signed
    NPI_TERBIT --> SP2D_TERBIT : SP2D signed
    SP2D_TERBIT --> SUDAH_DICAIRKAN : eksekusi &amp; catat BKU

    SUDAH_DICAIRKAN --> [*]
    DITOLAK_VERIFIKATOR --> [*]
    DITOLAK_KASUBBAG --> [*]
```

> Placeholder `REVISI_VERIFIKATOR` / `DITOLAK_VERIFIKATOR` mewakili 5 status spesifik:
> `REVISI_PPSPM`, `REVISI_BENDAHARA_PENERIMAAN`, `REVISI_BENDAHARA_PENGELUARAN`,
> `REVISI_PPK`, `REVISI_KOORDINATOR_KEUANGAN` (dan `DITOLAK_*` setara).

---

## 9. Sequence Diagram — Verifikasi Paralel

```mermaid
sequenceDiagram
    autonumber
    participant OP as Operator Perjaldin
    participant SYS as Sistem (Workflow)
    participant PPSPM
    participant BPN as Bend. Penerimaan
    participant BP as Bend. Pengeluaran
    participant PPK as PPK
    participant KK as Koor. Keuangan
    participant KS as Kasubbag

    OP->>SYS: submit(tagihan) atau bulkSubmit
    SYS-->>PPSPM: assign approval (PENDING)
    SYS-->>BPN: assign approval (PENDING)
    SYS-->>BP: assign approval (PENDING)
    SYS-->>PPK: assign approval (PENDING)
    SYS-->>KK: assign approval (PENDING)

    par Step 1 paralel
        PPSPM->>SYS: approve(catatan)
    and
        BPN->>SYS: approve(catatan)
    and
        BP->>SYS: approve(catatan)
    and
        PPK->>SYS: approve(catatan)
    and
        KK->>SYS: approve(catatan)
    end

    SYS->>SYS: cek approval Step 1<br/>=> semua APPROVED
    SYS-->>KS: assign Step 2 (PENDING_KASUBBAG)

    alt Kasubbag approve
        KS->>SYS: approve(catatan)
        SYS-->>OP: status = MENUNGGU_UPLOAD_NOMINATIF_TTD
        OP->>SYS: upload Nominatif &amp; Lampiran TTD
        SYS-->>OP: status = DISETUJUI_PERJALDIN<br/>(notifikasi ke Operator BLU)
    else Kasubbag minta revisi
        KS->>SYS: revisi(catatan)
        SYS-->>OP: status = REVISI_KASUBBAG
    else Kasubbag reject
        KS->>SYS: reject(catatan)
        SYS-->>OP: status = DITOLAK_KASUBBAG (final)
    end
```

> Jika salah satu role di Step 1 minta revisi/reject, `SYS` langsung men-set status
> dan tidak menunggu role lain; resubmit oleh Operator akan me-reset semua approval Step 1
> menjadi PENDING.

---

## 10. Tabel Referensi

### 10.1 Workflow Definitions

| Kode workflow         | Target dokumen              | Step 1 (paralel)                                           | Step 2     |
|-----------------------|-----------------------------|------------------------------------------------------------|------------|
| `PERJALDIN`           | `Tagihan` (perjaldin)       | PPSPM · Bend.Penerimaan · Bend.Pengeluaran · PPK · Koor.Keu | Kasubbag   |
| `SPP_PERJALDIN`       | `DokumenSpp` (per komponen) | PPK · Koor.Keu · Kasubbag                                   | —          |
| `SPM_PERJALDIN_PPSPM` | `DokumenSpm`                | PPSPM · Kasubbag · Koor.Keu                                 | —          |
| `NPI_PERJALDIN`       | `DokumenNpi`                | Bend.Penerimaan · PPK · Kasubbag · Koor.Keu                 | —          |
| `SP2D_PERJALDIN`      | `DokumenSp2d`               | PPSPM · PPK · Kasubbag · Koor.Keu                           | —          |

### 10.2 Status `tagihans.status` (tipe = PERJALDIN)

| Status                              | Arti                                                  | Berhenti? |
|-------------------------------------|-------------------------------------------------------|-----------|
| `DRAFT`                             | Baru dibuat / barusan diedit                          | tidak     |
| `PENDING_VERIFIKASI_PERJALDIN`      | Step 1 paralel berjalan                               | tidak     |
| `PENDING_PPSPM`                     | Khusus PPSPM masih PENDING                            | tidak     |
| `PENDING_PPK`                       | Khusus PPK masih PENDING                              | tidak     |
| `PENDING_BENDAHARA_PENERIMAAN`      | Khusus Bend.Penerimaan masih PENDING                  | tidak     |
| `PENDING_BENDAHARA_PENGELUARAN`     | Khusus Bend.Pengeluaran masih PENDING                 | tidak     |
| `PENDING_KOORDINATOR_KEUANGAN`      | Khusus Koor.Keu masih PENDING                         | tidak     |
| `PENDING_KASUBBAG`                  | Step 2 (Kasubbag finalisasi)                          | tidak     |
| `REVISI_PPSPM` / `REVISI_PPK` / `REVISI_BENDAHARA_*` / `REVISI_KOORDINATOR_KEUANGAN` / `REVISI_KASUBBAG` | Verifikator minta revisi → balik ke Operator | tidak (loop) |
| `DITOLAK_*`                         | Verifikator menolak                                   | ya (final)|
| `MENUNGGU_UPLOAD_NOMINATIF_TTD`     | Verifikasi lulus, menunggu unggah Nominatif & Lampiran TTD | tidak |
| `DISETUJUI_PERJALDIN`               | Siap dibuatkan SPP                                    | tidak     |
| `SPP_TERBIT` / `SPM_TERBIT` / `NPI_TERBIT` / `SP2D_TERBIT` | Tahap dokumen pencairan         | tidak     |
| `SUDAH_DICAIRKAN`                   | SP2D dieksekusi & dicatat BKU                         | ya (selesai) |

### 10.3 Dokumen yang dihasilkan

| Dokumen              | Sumber                                                     | Wajib?   |
|----------------------|------------------------------------------------------------|----------|
| SPT                  | Diunggah Operator per peserta saat create                  | ya       |
| Tiket / dokumen perjalanan | Diunggah Operator per peserta                        | bila ada |
| Rekap komponen       | Dihasilkan otomatis dari master uang harian + form input   | ya       |
| Nominatif TTD        | Cetak PDF dari sistem → TTD basah → upload kembali         | ya       |
| Lampiran TTD         | Cetak PDF dari sistem → TTD basah → upload kembali         | ya       |
| SPP, SPM, NPI, SP2D  | Workflow modul terkait                                     | ya       |

### 10.4 Routes Operator Perjaldin (singkat)

| Method  | URL                              | Aksi                              |
|---------|----------------------------------|-----------------------------------|
| GET     | `/perjaldins`                    | Index — daftar tagihan perjaldin  |
| GET/POST| `/perjaldins/create`             | Form & store baru                 |
| GET/PUT | `/perjaldins/{id}/edit`          | Edit & update                     |
| DELETE  | `/perjaldins/{id}`               | Hapus (jika status memungkinkan)  |
| POST    | `/perjaldins/bulk-submit`        | Ajukan banyak perjaldin sekaligus |
| GET     | `/perjaldins/{id}/pdf`           | Cetak SPT/Nominatif/Lampiran      |
| POST    | `/perjaldins/{id}/upload-nominatif-ttd` | Upload Nominatif/Lampiran TTD |

---

## 11. Glosarium

- **SPT** — *Surat Perintah Tugas* perjalanan dinas.
- **Nominatif** — daftar pegawai yang menerima pembayaran perjaldin (per peserta + total).
- **Lampiran** — rincian biaya per peserta (uang harian, transport, penginapan, dll).
- **DIPA Item / COA** — kode anggaran yang membebani pengeluaran perjaldin.
- **Master Uang Harian Perjaldin** — tabel referensi tarif uang harian per provinsi/kelas.
- **SPP / SPM / NPI / SP2D / BKU** — lihat glosarium di [`flow-tagihan-kontrak.md`](flow-tagihan-kontrak.md#10-glosarium).

---

## 12. Perbedaan dengan Tagihan Kontrak

| Aspek                          | Tagihan Kontrak                                  | Tagihan Perjaldin                              |
|--------------------------------|--------------------------------------------------|------------------------------------------------|
| Pembuat                        | Pejabat Pengadaan / PPK                          | Operator Perjaldin                             |
| Sumber tagihan                 | Termin kontrak `READY_TO_BILL`                   | SPT yang dibuat operator                       |
| Dokumen pembuka                | BAPP, BAST (jika pelunasan), BAP                 | SPT + tiket per peserta                        |
| Workflow Step 1 (paralel)      | PPK · PPSPM · Koor.Keu · Bend×2                  | PPSPM · Bend.Penerimaan · Bend.Pengeluaran · PPK · Koor.Keu |
| Workflow Step 2                | Kasubbag                                          | Kasubbag                                        |
| Gate setelah Kasubbag          | Langsung `READY_FOR_SPP`                         | **Wajib upload Nominatif TTD + Lampiran TTD** dulu |
| Status final pra-SPP           | `READY_FOR_SPP`                                  | `DISETUJUI_PERJALDIN`                          |
| Verifikator SPP                | PPK · Koor.Keu · Kasubbag                        | PPK · Koor.Keu · Kasubbag                      |
| Pencairan                      | Ke rekening vendor                               | Ke rekening pegawai (per peserta)              |
| Multi-termin                   | Ya (auto-unlock termin berikutnya)               | Tidak — sekali jalan per perjaldin             |
| Bulk submit                    | Tidak                                             | Ya (`POST /perjaldins/bulk-submit`)            |

---

## Cara melihat / mengekspor

- **GitHub / GitLab / VS Code**: blok ```` ```mermaid ```` otomatis dirender.
- **Live editor**: paste blok di <https://mermaid.live> → *Actions → Download SVG/PNG*.
- **Draw.io**: *Arrange → Insert → Advanced → Mermaid* → paste → *Insert*.
