# Alur Proses Tagihan Kontrak (BAST / Termin)

> Dokumen ini memetakan alur lengkap dari pembuatan tagihan kontrak BAST/Termin
> sampai dana cair ke vendor dan tercatat di BKU.
>
> Diagram dibuat dengan **Mermaid** sehingga:
> - Tampil otomatis di GitHub, GitLab, VS Code, IntelliJ, Obsidian
> - Bersih (tidak ada garis silang) karena auto-layout
> - Bisa diimpor ke Draw.io: *Arrange → Insert → Advanced → Mermaid*
>
> **Sumber kode**: `app/Http/Controllers/TagihanController.php`,
> `TagihanKontrakVerifikasiController.php`, `TagihanKontrakWorkflowService.php`,
> `database/seeders/WorkflowDefinitionSeeder.php`,
> `app/Models/DokumenSp2d.php` (auto-unlock termin berikutnya).

---

## Daftar Isi

1. [Phase Map (overview 5 fase)](#1-phase-map)
2. [Fase 1 — Pembuatan Tagihan](#2-fase-1--pembuatan-tagihan)
3. [Fase 2 — Verifikasi Tagihan (Step 1 paralel + Step 2 Kasubbag)](#3-fase-2--verifikasi-tagihan)
4. [Fase 3 — SPP Kontrak](#4-fase-3--spp-kontrak)
5. [Fase 4 — SPM Kontrak](#5-fase-4--spm-kontrak)
6. [Fase 5 — NPI → SP2D → BKU](#6-fase-5--npi--sp2d--bku)
7. [State Machine — Status Tagihan](#7-state-machine--status-tagihan)
8. [Sequence Diagram — Verifikasi Paralel](#8-sequence-diagram--verifikasi-paralel)
9. [Tabel Status & Dokumen](#9-tabel-referensi)
10. [Glosarium](#10-glosarium)

---

## 1. Phase Map

Peta tinggi-level 5 fase. Pakai ini sebagai indeks visual sebelum membaca diagram detail.

```mermaid
flowchart LR
    classDef phase fill:#dbeafe,stroke:#1d4ed8,color:#1e3a8a,stroke-width:2px,rx:10,ry:10;
    classDef actor fill:#fef3c7,stroke:#b45309,color:#7c2d12;
    classDef sys   fill:#dcfce7,stroke:#15803d,color:#14532d;

    P1["FASE 1<br/>Pembuatan Tagihan<br/><sub>BAPP / BAST / BAP</sub>"]:::phase
    P2["FASE 2<br/>Verifikasi Tagihan<br/><sub>Step 1 paralel + Kasubbag</sub>"]:::phase
    P3["FASE 3<br/>SPP Kontrak<br/><sub>PPK · Koor.Keu · Kasubbag</sub>"]:::phase
    P4["FASE 4<br/>SPM Kontrak<br/><sub>PPSPM · Kasubbag · Koor.Keu</sub>"]:::phase
    P5["FASE 5<br/>NPI → SP2D → BKU<br/><sub>Bend. Pengeluaran cair</sub>"]:::phase

    A1[["Pejabat Pengadaan / PPK"]]:::actor --> P1
    A2[["5 Verifikator + Kasubbag"]]:::actor --> P2
    A3[["Operator BLU"]]:::actor --> P3
    A4[["Operator BLU + PPSPM"]]:::actor --> P4
    A5[["Bendahara Pengeluaran"]]:::actor --> P5

    P1 --> P2 --> P3 --> P4 --> P5
    P5 --> SYS[["Sistem<br/>Catat BKU + Unlock Termin Berikutnya"]]:::sys
```

---

## 2. Fase 1 — Pembuatan Tagihan

Pejabat Pengadaan / PPK membuat draft tagihan dari termin yang sudah berstatus
`READY_TO_BILL`, lalu melengkapi dokumen final BAPP, BAST (jika pelunasan), dan BAP.

```mermaid
flowchart TD
    classDef start fill:#dcfce7,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef draft fill:#fed7aa,stroke:#c2410c,color:#7c2d12;
    classDef step  fill:#dbeafe,stroke:#1d4ed8,color:#1e3a8a;
    classDef gate  fill:#fef9c3,stroke:#a16207,color:#713f12;
    classDef final fill:#bbf7d0,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef doc   fill:#fef3c7,stroke:#b45309,color:#7c2d12;

    S(["Termin status<br/>READY_TO_BILL"]):::start
    A1["1 · Pilih kontrak aktif &amp; termin"]:::step
    A2["2 · Generate nomor BAPP, BAST, BAP<br/><i>via DocumentNumberService</i>"]:::step
    A3["3 · Simpan draft tagihan<br/><b>status = DRAFT</b><br/>termin → DRAFT (lock UI)"]:::draft
    A4["4 · Upload arsip final<br/>BAPP_FINAL_TTD<br/>BAST_FINAL_TTD <i>(jika pelunasan)</i><br/>BAP_FINAL_TTD"]:::step
    G{"Dokumen<br/>lengkap?"}:::gate
    A5["5 · Submit ke verifikasi<br/><b>PENDING_VERIFIKASI_KONTRAK</b>"]:::final

    DOC1[/"BAPP — Berita Acara<br/>Pemeriksaan Pekerjaan"/]:::doc
    DOC2[/"BAST — Berita Acara<br/>Serah Terima<br/>(hanya jenis_termin = PELUNASAN)"/]:::doc
    DOC3[/"BAP — Berita Acara<br/>Pembayaran"/]:::doc

    S --> A1 --> A2 --> A3 --> A4 --> G
    G -- belum --> A4
    G -- ya --> A5

    A2 -.menerbitkan.-> DOC1
    A2 -.menerbitkan.-> DOC2
    A2 -.menerbitkan.-> DOC3
```

> **Validasi penting di Fase 1** (`TagihanController::storeKontrak`):
> - Termin harus `READY_TO_BILL`.
> - Draft tagihan tidak boleh ganda untuk termin yang sama (`DetailKontrak.kontrak_termin_id` unik).
> - Nilai bruto yang dikirim klien harus cocok dengan `nilai_bruto_termin` di backend (toleransi 1 rupiah).
> - Jika `jenis_termin = PELUNASAN`, BAST wajib di-input.

---

## 3. Fase 2 — Verifikasi Tagihan

Workflow `TAGIHAN_KONTRAK_VERIFIKATOR` dari `WorkflowDefinitionSeeder`.
Step 1 = 5 verifikator paralel (semua wajib approve), Step 2 = Kasubbag finalisasi.

```mermaid
flowchart LR
    classDef start  fill:#dcfce7,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef gate   fill:#fef9c3,stroke:#a16207,color:#713f12;
    classDef role   fill:#dbeafe,stroke:#1d4ed8,color:#1e3a8a;
    classDef ok     fill:#bbf7d0,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef rev    fill:#fde68a,stroke:#b45309,color:#7c2d12;
    classDef rej    fill:#fecaca,stroke:#b91c1c,color:#7f1d1d;
    classDef kasubg fill:#e9d5ff,stroke:#6b21a8,color:#3b0764;

    S(["Submit Tagihan"]):::start
    GS{{"+"}}:::gate
    R1["PPK"]:::role
    R2["PPSPM"]:::role
    R3["Koordinator<br/>Keuangan"]:::role
    R4["Bendahara<br/>Pengeluaran"]:::role
    R5["Bendahara<br/>Penerimaan"]:::role
    GE{{"+"}}:::gate
    K["Kasubbag<br/>Finalisasi"]:::kasubg
    OK["READY_FOR_SPP<br/>(siap dibuatkan SPP)"]:::ok

    REV["REVISI_*<br/>kembali ke<br/>Pejabat Pengadaan"]:::rev
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
    K -- approve --> OK
    K -. revisi .-> REVK
    K -. reject .-> REJK
```

> **Aturan**:
> - Semua role di Step 1 **wajib** approve (`is_required = true`) sebelum Step 2 dieksekusi.
> - Setiap role boleh `request revision` (loop balik ke Pejabat Pengadaan) atau `reject` (workflow stop).
> - `TagihanKontrakWorkflowService::syncTagihanStatus()` memetakan keputusan terakhir ke status tagihan
>   (`PENDING_<ROLE>`, `REVISI_<ROLE>`, `DITOLAK_<ROLE>`, `READY_FOR_SPP`).

---

## 4. Fase 3 — SPP Kontrak

Operator BLU membuat SPP berdasarkan tagihan `READY_FOR_SPP`. Verifikasi paralel
di workflow `SPP_KONTRAK_PPK` melibatkan PPK, Koordinator Keuangan, Kasubbag.

```mermaid
flowchart LR
    classDef step  fill:#bfdbfe,stroke:#1d4ed8,color:#1e3a8a;
    classDef role  fill:#dbeafe,stroke:#1d4ed8,color:#1e3a8a;
    classDef ok    fill:#bbf7d0,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef gate  fill:#fef9c3,stroke:#a16207,color:#713f12;

    T1["Tagihan<br/>READY_FOR_SPP"]:::ok
    OB1["6 · Operator BLU<br/>buat draft SPP Kontrak"]:::step
    OB2["7 · Submit SPP ke<br/>workflow verifikasi"]:::step

    GS{{"+"}}:::gate
    V1["Verifikasi<br/>PPK"]:::role
    V2["Verifikasi<br/>Koordinator Keuangan"]:::role
    V3["Verifikasi<br/>Kasubbag"]:::role
    GE{{"+"}}:::gate

    OB3["8 · Cetak SPP &amp; upload<br/>SPP bertanda tangan"]:::step
    SPPOK["SPP_TERBIT"]:::ok

    T1 --> OB1 --> OB2 --> GS
    GS --> V1 & V2 & V3
    V1 --> GE
    V2 --> GE
    V3 --> GE
    GE --> OB3 --> SPPOK
```

---

## 5. Fase 4 — SPM Kontrak

Workflow `SPM_KONTRAK_PPSPM`: PPSPM, Kasubbag, Koordinator Keuangan (paralel).

```mermaid
flowchart LR
    classDef step fill:#fed7aa,stroke:#c2410c,color:#7c2d12;
    classDef role fill:#dbeafe,stroke:#1d4ed8,color:#1e3a8a;
    classDef ok   fill:#bbf7d0,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef gate fill:#fef9c3,stroke:#a16207,color:#713f12;

    SPP["SPP_TERBIT"]:::ok
    OB1["9 · Operator BLU<br/>buat SPM Kontrak"]:::step
    OB2["10 · Submit SPM<br/>ke workflow verifikasi"]:::step

    GS{{"+"}}:::gate
    V1["PPSPM"]:::role
    V2["Kasubbag"]:::role
    V3["Koordinator Keuangan"]:::role
    GE{{"+"}}:::gate

    OB3["11 · Upload SPM<br/>bertanda tangan"]:::step
    SPMOK["SPM_TERBIT"]:::ok

    SPP --> OB1 --> OB2 --> GS
    GS --> V1 & V2 & V3
    V1 --> GE
    V2 --> GE
    V3 --> GE
    GE --> OB3 --> SPMOK
```

---

## 6. Fase 5 — NPI → SP2D → BKU

Bendahara Pengeluaran membuat NPI dan SP2D. Workflow `NPI_KONTRAK` dan
`SP2D_KONTRAK` punya 4 verifikator paralel.

```mermaid
flowchart TD
    classDef bp    fill:#dcfce7,stroke:#15803d,color:#14532d;
    classDef role  fill:#dbeafe,stroke:#1d4ed8,color:#1e3a8a;
    classDef ok    fill:#bbf7d0,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef gate  fill:#fef9c3,stroke:#a16207,color:#713f12;
    classDef sys   fill:#e0e7ff,stroke:#4338ca,color:#312e81;

    SPM["SPM_TERBIT"]:::ok

    %% NPI
    BP1["12 · Bend. Pengeluaran<br/>buat NPI Kontrak (draft)"]:::bp
    BP2["13 · Submit NPI<br/>ke workflow"]:::bp

    GN{{"+ NPI"}}:::gate
    NV1["Bend. Penerimaan"]:::role
    NV2["PPK"]:::role
    NV3["Kasubbag"]:::role
    NV4["Koordinator Keuangan"]:::role
    GNE{{"+"}}:::gate

    BP3["14 · Upload NPI<br/>bertanda tangan"]:::bp
    NPIOK["NPI_TERBIT"]:::ok

    %% SP2D
    BP4["15 · Bend. Pengeluaran<br/>buat SP2D Kontrak (draft)"]:::bp
    BP5["16 · Submit SP2D<br/>ke workflow"]:::bp

    GS{{"+ SP2D"}}:::gate
    SV1["PPSPM"]:::role
    SV2["PPK"]:::role
    SV3["Kasubbag"]:::role
    SV4["Koordinator Keuangan"]:::role
    GSE{{"+"}}:::gate

    BP6["17 · Upload SP2D<br/>bertanda tangan"]:::bp
    SP2D["SP2D_TERBIT"]:::ok

    %% Eksekusi
    EXEC["18 · Eksekusi SP2D<br/>(transfer dana ke vendor)"]:::sys
    BKU["19 · Catat BKU &amp;<br/>Buku Pembantu"]:::sys
    UNLK["20 · Termin → SUDAH_DITAGIH<br/>Termin berikutnya: LOCKED → READY_TO_BILL"]:::sys
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
    GSE --> BP6 --> SP2D --> EXEC --> BKU --> UNLK --> DONE
```

> **Auto-unlock termin berikutnya** (`app/Models/DokumenSp2d.php`): saat SP2D
> sebuah termin dieksekusi, sistem mencari termin selanjutnya di kontrak yang sama
> dan mengubah `status_termin` dari `LOCKED` ke `READY_TO_BILL`. Sehingga kontrak
> multi-termin bisa langsung mulai siklus berikutnya.

---

## 7. State Machine — Status Tagihan

Transisi status tagihan kontrak (kolom `tagihans.status`).

```mermaid
stateDiagram-v2
    direction LR

    [*] --> DRAFT : create()

    DRAFT --> PENDING_VERIFIKASI_KONTRAK : submit
    PENDING_VERIFIKASI_KONTRAK --> REVISI_VERIFIKATOR : salah satu request revisi
    PENDING_VERIFIKASI_KONTRAK --> DITOLAK_VERIFIKATOR : salah satu reject
    REVISI_VERIFIKATOR --> DRAFT : Pejabat Pengadaan perbaiki

    PENDING_VERIFIKASI_KONTRAK --> PENDING_KASUBBAG : semua verifikator approve

    PENDING_KASUBBAG --> REVISI_KASUBBAG : request revisi
    PENDING_KASUBBAG --> DITOLAK_KASUBBAG : reject
    REVISI_KASUBBAG --> DRAFT : perbaiki

    PENDING_KASUBBAG --> READY_FOR_SPP : approve final

    READY_FOR_SPP --> PROSES_SPP : Operator BLU buat SPP
    PROSES_SPP --> SEBAGIAN_SPP_TERBIT : sebagian termin
    SEBAGIAN_SPP_TERBIT --> SPP_LENGKAP : seluruh termin SPP terbit
    PROSES_SPP --> SPP_TERBIT : SPP signed

    SPP_TERBIT --> SPM_TERBIT : SPM signed
    SPM_TERBIT --> NPI_TERBIT : NPI signed
    NPI_TERBIT --> SP2D_TERBIT : SP2D signed
    SP2D_TERBIT --> SUDAH_DITAGIH : eksekusi &amp; BKU

    SUDAH_DITAGIH --> [*]
    DITOLAK_VERIFIKATOR --> [*]
    DITOLAK_KASUBBAG --> [*]
```

> Catatan: `REVISI_VERIFIKATOR`/`DITOLAK_VERIFIKATOR` di atas adalah kelompok placeholder.
> Status sebenarnya bersuffix nama role: `REVISI_PPK`, `REVISI_PPSPM`,
> `REVISI_KOORDINATOR_KEUANGAN`, `REVISI_BENDAHARA_PENGELUARAN`,
> `REVISI_BENDAHARA_PENERIMAAN` (dan `DITOLAK_*` yang setara).

---

## 8. Sequence Diagram — Verifikasi Paralel

Cara workflow paralel bekerja dari sudut interaksi pemain.

```mermaid
sequenceDiagram
    autonumber
    participant PP as Pejabat Pengadaan
    participant SYS as Sistem (Workflow)
    participant PPK as PPK
    participant PPSPM
    participant KK as Koor. Keuangan
    participant BP as Bend. Pengeluaran
    participant BPN as Bend. Penerimaan
    participant KS as Kasubbag

    PP->>SYS: submit(tagihan)
    SYS-->>PPK: assign approval (PENDING)
    SYS-->>PPSPM: assign approval (PENDING)
    SYS-->>KK: assign approval (PENDING)
    SYS-->>BP: assign approval (PENDING)
    SYS-->>BPN: assign approval (PENDING)

    par Step 1 paralel
        PPK->>SYS: approve(catatan)
    and
        PPSPM->>SYS: approve(catatan)
    and
        KK->>SYS: approve(catatan)
    and
        BP->>SYS: approve(catatan)
    and
        BPN->>SYS: approve(catatan)
    end

    SYS->>SYS: cek semua approval Step 1<br/>=> semua APPROVED
    SYS-->>KS: assign Step 2 (PENDING_KASUBBAG)

    alt Kasubbag approve
        KS->>SYS: approve(catatan)
        SYS-->>PP: status = READY_FOR_SPP
    else Kasubbag minta revisi
        KS->>SYS: revisi(catatan)
        SYS-->>PP: status = REVISI_KASUBBAG
    end
```

> Jika salah satu Step 1 minta revisi atau reject, `SYS` langsung men-set status
> tagihan ke `REVISI_<ROLE>` / `DITOLAK_<ROLE>` tanpa menunggu approval lain selesai.

---

## 9. Tabel Referensi

### 9.1 Workflow Definition (kode → step)

| Kode workflow                  | Target dokumen      | Step 1 (paralel)                                           | Step 2     |
|--------------------------------|---------------------|------------------------------------------------------------|------------|
| `TAGIHAN_KONTRAK_VERIFIKATOR`  | `Tagihan` (kontrak) | PPK · PPSPM · Koor.Keu · Bend.Pengeluaran · Bend.Penerimaan | Kasubbag   |
| `SPP_KONTRAK_PPK`              | `DokumenSpp`        | PPK · Koor.Keu · Kasubbag                                  | —          |
| `SPM_KONTRAK_PPSPM`            | `DokumenSpm`        | PPSPM · Kasubbag · Koor.Keu                                | —          |
| `NPI_KONTRAK`                  | `DokumenNpi`        | Bend.Penerimaan · PPK · Kasubbag · Koor.Keu                | —          |
| `SP2D_KONTRAK`                 | `DokumenSp2d`       | PPSPM · PPK · Kasubbag · Koor.Keu                          | —          |

> Walaupun Step 1 berisi banyak role, workflow non-tagihan di atas memakai
> `urutan_step = 1` untuk semuanya, sehingga seluruhnya paralel dan
> tidak ada Step 2 final.

### 9.2 Status `tagihans.status`

| Status                          | Arti                                                  | Berhenti di sini? |
|---------------------------------|-------------------------------------------------------|-------------------|
| `DRAFT`                         | Baru dibuat, belum di-submit                          | tidak             |
| `PENDING_VERIFIKASI_KONTRAK`    | Menunggu Step 1 (5 verifikator)                       | tidak             |
| `REVISI_<ROLE>`                 | Verifikator role tsb minta revisi                     | tidak (loop)      |
| `DITOLAK_<ROLE>`                | Verifikator role tsb reject                           | ya (final)        |
| `PENDING_KASUBBAG`              | Step 1 lulus, menunggu Kasubbag                       | tidak             |
| `REVISI_KASUBBAG` / `DITOLAK_KASUBBAG` | Outcome Step 2                                  | tidak / ya        |
| `READY_FOR_SPP`                 | Disetujui semua, siap dibuatkan SPP                   | tidak             |
| `PROSES_SPP`                    | Sudah ada SPP draft / sebagian termin diproses        | tidak             |
| `SEBAGIAN_SPP_TERBIT`           | Beberapa termin sudah SPP                             | tidak             |
| `SPP_LENGKAP`                   | Semua termin SPP terbit                               | tidak             |
| `SPP_TERBIT` / `SPM_TERBIT` / `NPI_TERBIT` / `SP2D_TERBIT` | Tahap dokumen   | tidak             |
| `SUDAH_DITAGIH`                 | SP2D dieksekusi + tercatat BKU                        | ya (selesai)      |

### 9.3 Status `kontrak_termin.status_termin`

| Status            | Kondisi                                                                       |
|-------------------|-------------------------------------------------------------------------------|
| `LOCKED`          | Termin belum aktif. Akan unlock ketika termin sebelumnya `SUDAH_DITAGIH`.     |
| `READY_TO_BILL`   | Bisa dibuatkan tagihan (state default termin pertama setelah kontrak aktif).  |
| `DRAFT`           | Sudah ada draft tagihan; mencegah pembuatan duplikat.                         |
| `SUDAH_DITAGIH`   | SP2D termin ini sudah dieksekusi.                                             |

### 9.4 Dokumen yang dihasilkan

| Dokumen | Sumber kode (Document Number Key) | Wajib?                             |
|---------|-----------------------------------|-------------------------------------|
| BAPP    | `DocumentNumberService::generateByKey('BAPP')` | Selalu                  |
| BAST    | `DocumentNumberService::generateByKey('BAST')` | Hanya `jenis_termin = PELUNASAN` |
| BAP     | `DocumentNumberService::generateByKey('BAP')`  | Selalu                  |
| SPP, SPM, NPI, SP2D | masing-masing dari workflow modul terkait | Selalu          |

---

## 10. Glosarium

- **BAPP** — *Berita Acara Pemeriksaan Pekerjaan*. Pemeriksaan hasil pekerjaan oleh tim pemeriksa.
- **BAST** — *Berita Acara Serah Terima*. Penyerahan akhir pekerjaan dari penyedia ke PA/KPA.
- **BAP** — *Berita Acara Pembayaran*. Dokumen dasar pembayaran.
- **SPP** — *Surat Permintaan Pembayaran*. Dibuat Operator BLU, ditujukan ke PPSPM.
- **SPM** — *Surat Perintah Membayar*. Dibuat Operator BLU dari SPP yang sudah disetujui PPSPM.
- **NPI** — *Nota Pemindahbukuan Internal*. Pemindahbukuan internal kas BLU.
- **SP2D** — *Surat Perintah Pencairan Dana*. Pencairan ke rekening vendor.
- **BKU** — *Buku Kas Umum*. Pencatatan akuntansi bendahara.
- **PPK** — *Pejabat Pembuat Komitmen*.
- **PPSPM** — *Pejabat Penanda-tangan SPM*.
- **PPABP** — *Pejabat Pengelola Administrasi Belanja Pegawai* (untuk honor).
- **DIPA** — *Daftar Isian Pelaksanaan Anggaran*.

---

## Cara melihat / mengekspor diagram

- **GitHub / GitLab / VS Code**: blok ```` ```mermaid ```` otomatis dirender.
- **VS Code**: install ekstensi *Markdown Preview Mermaid Support* untuk live preview.
- **Draw.io / diagrams.net**: buka *Arrange → Insert → Advanced → Mermaid*, paste isi
  blok mermaid yang diinginkan, klik *Insert*. Hasil dapat di-edit lebih lanjut sebagai shape.
- **PNG / SVG**: dari Mermaid Live Editor (<https://mermaid.live>) — paste, lalu *Actions → Download*.
