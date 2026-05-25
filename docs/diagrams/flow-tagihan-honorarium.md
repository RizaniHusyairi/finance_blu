# Alur Proses Tagihan Honorarium (Sampai Pencairan SP2D)

> Dokumen ini memetakan alur lengkap dari pembuatan **Tagihan Honorarium** sampai
> dana cair ke pegawai/penerima dan tercatat di BKU.
>
> Diagram dibuat dengan **Mermaid** sehingga:
> - Tampil otomatis di GitHub, GitLab, VS Code, IntelliJ, Obsidian
> - Bersih (tidak ada garis silang) karena auto-layout
> - Bisa diimpor ke Draw.io: *Arrange → Insert → Advanced → Mermaid*
>
> **Sumber kode**:
> - `app/Http/Controllers/HonorariumController.php`,
>   `BendaharaHonorariumVerifikasiController.php`
> - `app/Services/TagihanHonorariumWorkflowService.php`
> - `database/seeders/WorkflowDefinitionSeeder.php` — definisi workflow
>   `TAGIHAN_HONORARIUM`, `SPP_HONORARIUM_PPK`, `SPM_HONORARIUM_PPSPM`,
>   `NPI_HONORARIUM`, `SP2D_HONORARIUM`
> - `app/Services/BudgetRealizationService.php` — pencatatan BKU saat SP2D cair

---

## Daftar Isi

1. [Phase Map (overview 5 fase)](#1-phase-map)
2. [Fase 1 — Pembuatan Tagihan Honorarium](#2-fase-1--pembuatan-tagihan-honorarium)
3. [Fase 2 — Verifikasi Tagihan (5 paralel + Kasubbag)](#3-fase-2--verifikasi-tagihan-honorarium)
4. [Fase 3 — SPP Honorarium](#4-fase-3--spp-honorarium)
5. [Fase 4 — SPM Honorarium](#5-fase-4--spm-honorarium)
6. [Fase 5 — NPI → SP2D → Pencairan & BKU](#6-fase-5--npi--sp2d--pencairan--bku)
7. [State Machine — Status Tagihan Honorarium](#7-state-machine--status-tagihan-honorarium)
8. [Sequence Diagram — Verifikasi Paralel Fase 2](#8-sequence-diagram--verifikasi-paralel-fase-2)
9. [Tabel Workflow & Role per Fase](#9-tabel-workflow--role-per-fase)
10. [Glosarium](#10-glosarium)

---

## 1. Phase Map

Peta tinggi-level lima fase. Pakai sebagai indeks visual sebelum membaca diagram detail.

```mermaid
flowchart LR
    classDef phase fill:#dbeafe,stroke:#1d4ed8,color:#1e3a8a,stroke-width:2px,rx:10,ry:10;
    classDef actor fill:#fef3c7,stroke:#b45309,color:#7c2d12;
    classDef sys   fill:#dcfce7,stroke:#15803d,color:#14532d;

    P1["FASE 1<br/>Pembuatan Tagihan<br/><sub>Honorarium</sub>"]:::phase
    P2["FASE 2<br/>Verifikasi Tagihan<br/><sub>5 paralel + Kasubbag</sub>"]:::phase
    P3["FASE 3<br/>SPP Honorarium<br/><sub>PPK · Koor.Keu · Kasubbag</sub>"]:::phase
    P4["FASE 4<br/>SPM Honorarium<br/><sub>Kasubbag · PPSPM · Koor.Keu</sub>"]:::phase
    P5["FASE 5<br/>NPI → SP2D → BKU<br/><sub>Bend. Pengeluaran cair</sub>"]:::phase

    A1[["PPABP / PPK"]]:::actor --> P1
    A2[["5 Verifikator + Kasubbag"]]:::actor --> P2
    A3[["Operator BLU"]]:::actor --> P3
    A4[["Operator BLU + PPSPM"]]:::actor --> P4
    A5[["Bendahara Pengeluaran"]]:::actor --> P5

    P1 --> P2 --> P3 --> P4 --> P5
    P5 --> SYS[["Sistem<br/>Catat BKU + Update Realisasi DIPA"]]:::sys
```

---

## 2. Fase 1 — Pembuatan Tagihan Honorarium

PPABP (atau PPK) menyiapkan daftar honorarium per pegawai/komponen, melengkapi
dokumen pendukung (SK, daftar hadir, SPK kegiatan, bukti potongan pajak), lalu
men-submit ke alur verifikasi.

```mermaid
flowchart TD
    classDef start fill:#dcfce7,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef draft fill:#fed7aa,stroke:#c2410c,color:#7c2d12;
    classDef step  fill:#dbeafe,stroke:#1d4ed8,color:#1e3a8a;
    classDef gate  fill:#fef9c3,stroke:#a16207,color:#713f12;
    classDef final fill:#bbf7d0,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef doc   fill:#fef3c7,stroke:#b45309,color:#7c2d12;

    S(["Trigger:<br/>SK kegiatan / SK honorarium<br/>terbit"]):::start
    A1["1 · Pilih jenis kegiatan honorarium<br/>(Narasumber, Moderator, Pengelola, dll)"]:::step
    A2["2 · Tambah daftar penerima<br/>nama · NIP · golongan · jumlah jam · tarif"]:::step
    A3["3 · Hitung otomatis<br/>nilai bruto · potongan PPh · netto"]:::step
    A4["4 · Generate nomor tagihan<br/><i>via DocumentNumberService</i>"]:::step
    A5["5 · Simpan draft<br/><b>status = DRAFT</b>"]:::draft
    A6["6 · Upload arsip pendukung<br/>SK · daftar hadir · bukti pajak"]:::step
    G{"Data lengkap<br/>&amp; nilai tervalidasi?"}:::gate
    A7["7 · Submit ke verifikasi<br/><b>PENDING_VERIFIKASI_HONORARIUM</b>"]:::final

    DOC1[/"SK Kegiatan / SK Honor"/]:::doc
    DOC2[/"Daftar Hadir &amp; Notula"/]:::doc
    DOC3[/"Bukti Potong PPh 21"/]:::doc

    S --> A1 --> A2 --> A3 --> A4 --> A5 --> A6 --> G
    G -- belum --> A6
    G -- ya --> A7

    A6 -.upload.-> DOC1
    A6 -.upload.-> DOC2
    A6 -.upload.-> DOC3
```

> **Validasi penting di Fase 1** (`HonorariumController::store`):
> - Setiap baris detail wajib memiliki **NIP/identitas penerima** yang valid.
> - Tarif harus diambil dari `MasterTarifHonorarium` (golongan + jenis kegiatan).
> - Total bruto = Σ(detail). Pajak otomatis dihitung dari `MasterTarifPajak`.
> - Sebelum submit, semua dokumen wajib diupload (SK, daftar hadir, bukti pajak).

---

## 3. Fase 2 — Verifikasi Tagihan Honorarium

Workflow `TAGIHAN_HONORARIUM` (lihat `WorkflowDefinitionSeeder`).
Step 1 berjalan **paralel oleh 5 verifikator** (semua wajib approve), Step 2
finalisasi oleh **Kasubbag**.

```mermaid
flowchart LR
    classDef start  fill:#dcfce7,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef gate   fill:#fef9c3,stroke:#a16207,color:#713f12;
    classDef role   fill:#dbeafe,stroke:#1d4ed8,color:#1e3a8a;
    classDef ok     fill:#bbf7d0,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef rev    fill:#fde68a,stroke:#b45309,color:#7c2d12;
    classDef rej    fill:#fecaca,stroke:#b91c1c,color:#7f1d1d;
    classDef kasubg fill:#e9d5ff,stroke:#6b21a8,color:#3b0764;

    S(["Submit Tagihan<br/>PENDING_VERIFIKASI_HONORARIUM"]):::start
    GS{{"+"}}:::gate

    R1["PPK"]:::role
    R2["PPSPM"]:::role
    R3["Koordinator<br/>Keuangan"]:::role
    R4["Bendahara<br/>Pengeluaran"]:::role
    R5["Bendahara<br/>Penerimaan"]:::role

    GA{{"AND<br/>(semua approve)"}}:::gate
    K["Kasubbag —<br/>Persetujuan Final"]:::kasubg

    OK(["DISETUJUI<br/>siap proses SPP"]):::ok
    REV[/"Salah satu minta REVISI<br/>→ kembali ke pembuat"/]:::rev
    REJ[/"Salah satu menolak<br/>→ DITOLAK"/]:::rej

    S --> GS
    GS --> R1 & R2 & R3 & R4 & R5
    R1 & R2 & R3 & R4 & R5 --> GA
    GA --> K --> OK

    R1 -. revisi .-> REV
    R2 -. revisi .-> REV
    R3 -. revisi .-> REV
    R4 -. revisi .-> REV
    R5 -. revisi .-> REV
    K  -. revisi .-> REV

    R1 -. tolak .-> REJ
    R2 -. tolak .-> REJ
    R3 -. tolak .-> REJ
    R4 -. tolak .-> REJ
    R5 -. tolak .-> REJ
    K  -. tolak .-> REJ
```

> **Catatan implementasi** (`TagihanHonorariumWorkflowService::pendingStatusForHonorarium`):
> - Status tagihan saat menunggu approval **paralel**: `PENDING_VERIFIKASI_HONORARIUM`
>   (bukan per role) — lebih ringkas karena 5 role dapat ditemui sekaligus.
> - Setelah 5 verifikator approve, status berpindah ke `PENDING_KASUBBAG`.
> - Setelah Kasubbag approve → `DISETUJUI`, otomatis dapat diproses ke Fase 3.
> - Jika ada satu verifikator yang minta revisi atau menolak, instance status
>   workflow berpindah ke `REVISION` / `REJECTED`, status dokumen menjadi
>   `REVISI_<ROLE>` / `DITOLAK_<ROLE>`.

---

## 4. Fase 3 — SPP Honorarium

Operator BLU membuat **Surat Perintah Pembayaran (SPP)** dari tagihan yang
sudah `DISETUJUI`. Workflow `SPP_HONORARIUM_PPK` memverifikasi SPP secara
**paralel** oleh **PPK · Koordinator Keuangan · Kasubbag**.

```mermaid
flowchart LR
    classDef start fill:#dcfce7,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef step  fill:#dbeafe,stroke:#1d4ed8,color:#1e3a8a;
    classDef gate  fill:#fef9c3,stroke:#a16207,color:#713f12;
    classDef ok    fill:#bbf7d0,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef doc   fill:#fef3c7,stroke:#b45309,color:#7c2d12;

    S(["Tagihan DISETUJUI"]):::start
    A1["1 · Operator BLU buat<br/>DokumenSpp · type=HONORARIUM"]:::step
    A2["2 · Submit SPP →<br/>PENDING_VERIFIKASI_SPP_HONOR"]:::step

    GP{{"+"}}:::gate
    V1["Verifikasi PPK"]:::step
    V2["Verifikasi Koor.Keu"]:::step
    V3["Verifikasi Kasubbag"]:::step
    GA{{"AND"}}:::gate

    OK(["SPP_HONOR_DISETUJUI<br/>siap dilanjutkan ke SPM"]):::ok
    DOC1[/"PDF SPP Honorarium"/]:::doc

    S --> A1 --> A2 --> GP
    GP --> V1 & V2 & V3
    V1 & V2 & V3 --> GA --> OK
    A1 -.menerbitkan.-> DOC1
```

> **Validasi penting di Fase 3** (`SppController::storeHonorarium`):
> - SPP hanya bisa dibuat kalau tagihan `status = DISETUJUI`.
> - Nomor SPP digenerate via `DocumentNumberService` dengan tipe `SPP_HONOR`.
> - Total SPP harus = total netto tagihan honorarium.
> - PDF SPP otomatis di-stream dari template Blade `dokumen-spp.honorarium`.

---

## 5. Fase 4 — SPM Honorarium

Operator BLU melanjutkan dengan membuat **Surat Perintah Membayar (SPM)** dari
SPP yang sudah disetujui. Workflow `SPM_HONORARIUM_PPSPM` memverifikasi SPM
**paralel** oleh **Kasubbag · PPSPM · Koordinator Keuangan**.

```mermaid
flowchart LR
    classDef start fill:#dcfce7,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef step  fill:#dbeafe,stroke:#1d4ed8,color:#1e3a8a;
    classDef gate  fill:#fef9c3,stroke:#a16207,color:#713f12;
    classDef ok    fill:#bbf7d0,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef doc   fill:#fef3c7,stroke:#b45309,color:#7c2d12;

    S(["SPP_HONOR_DISETUJUI"]):::start
    A1["1 · Operator BLU buat<br/>DokumenSpm · type=HONORARIUM"]:::step
    A2["2 · Submit SPM →<br/>PENDING_VERIFIKASI_SPM_HONOR"]:::step

    GP{{"+"}}:::gate
    V1["Verifikasi Kasubbag"]:::step
    V2["Verifikasi PPSPM"]:::step
    V3["Verifikasi Koor.Keu"]:::step
    GA{{"AND"}}:::gate

    OK(["SPM_HONOR_DISETUJUI<br/>siap diteruskan ke NPI &amp; SP2D"]):::ok
    DOC1[/"PDF SPM Honorarium"/]:::doc

    S --> A1 --> A2 --> GP
    GP --> V1 & V2 & V3
    V1 & V2 & V3 --> GA --> OK
    A1 -.menerbitkan.-> DOC1
```

> **Catatan implementasi**:
> - Urutan tampilan TTD pada PDF SPM Honor: Kasubbag → PPSPM → Koordinator
>   Keuangan, sesuai dengan order step pada workflow `SPM_HONORARIUM_PPSPM`.
> - Jika satu verifikator minta revisi, dokumen di-rollback ke draft tanpa
>   menghilangkan history TTD verifikator lainnya.

---

## 6. Fase 5 — NPI → SP2D → Pencairan & BKU

Tahap akhir: **NPI** (Nota Pemindahbukuan Internal) untuk potongan pajak,
**SP2D** sebagai surat perintah pencairan, dan pencatatan otomatis ke BKU.

```mermaid
flowchart TD
    classDef start fill:#dcfce7,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef step  fill:#dbeafe,stroke:#1d4ed8,color:#1e3a8a;
    classDef gate  fill:#fef9c3,stroke:#a16207,color:#713f12;
    classDef ok    fill:#bbf7d0,stroke:#15803d,color:#14532d,stroke-width:2px;
    classDef cash  fill:#bbf7d0,stroke:#0891b2,color:#0e7490,stroke-width:2px;
    classDef doc   fill:#fef3c7,stroke:#b45309,color:#7c2d12;

    S(["SPM_HONOR_DISETUJUI"]):::start

    %% NPI block
    N1["1 · Buat DokumenNpi<br/>(potongan PPh 21)"]:::step
    N2["2 · Verifikasi NPI Honorarium<br/><b>PARALEL 4 ROLE</b><br/>Bend. Penerimaan · PPK<br/>Kasubbag · Koor.Keu"]:::step
    N3((NPI<br/>DISETUJUI)):::ok

    %% SP2D block
    P1["3 · Buat DokumenSp2d<br/>type=HONORARIUM"]:::step
    P2["4 · Verifikasi SP2D Honorarium<br/><b>PARALEL 4 ROLE</b><br/>PPSPM · PPK · Kasubbag · Koor.Keu"]:::step
    P3["5 · SP2D TTD lengkap<br/>siap eksekusi"]:::step

    %% Cair
    C1["6 · Bendahara Pengeluaran<br/>eksekusi pencairan ke rekening<br/>penerima honor"]:::cash
    C2["7 · Tandai SP2D <b>CAIR</b><br/>dan kunci dokumen"]:::cash
    C3["8 · Sistem catat realisasi<br/>BKU + DIPA<br/><i>BudgetRealizationService</i>"]:::ok

    DOC1[/"PDF NPI Honorarium"/]:::doc
    DOC2[/"PDF SP2D Honorarium"/]:::doc

    S --> N1 --> N2 --> N3 --> P1 --> P2 --> P3 --> C1 --> C2 --> C3
    N1 -.menerbitkan.-> DOC1
    P1 -.menerbitkan.-> DOC2
```

> **Pencatatan BKU** (`BudgetRealizationService`):
> - Saat SP2D ditandai `CAIR`, service otomatis membuat baris di
>   `realisasi_anggaran` (mengikat ke `master_dipa_id` pada tagihan honor).
> - `sisa_pagu` di tabel `master_dipa` ikut berkurang sesuai nilai netto.
> - Mutasi muncul di laporan **BKU** (`reports.bku`) dan **Realisasi DIPA**.

---

## 7. State Machine — Status Tagihan Honorarium

Diagram status tagihan honorarium dari `DRAFT` sampai `LUNAS` (cair). Tiap
panah merepresentasikan transisi yang dipicu oleh aksi manual (workflow) atau
event sistem.

```mermaid
stateDiagram-v2
    [*] --> DRAFT
    DRAFT --> PENDING_VERIFIKASI_HONORARIUM : submit
    PENDING_VERIFIKASI_HONORARIUM --> PENDING_KASUBBAG : 5 verifikator approve
    PENDING_KASUBBAG --> DISETUJUI : Kasubbag approve

    PENDING_VERIFIKASI_HONORARIUM --> REVISI_PPK : PPK minta revisi
    PENDING_VERIFIKASI_HONORARIUM --> REVISI_PPSPM : PPSPM minta revisi
    PENDING_VERIFIKASI_HONORARIUM --> REVISI_KOORKEU : Koor.Keu minta revisi
    PENDING_VERIFIKASI_HONORARIUM --> REVISI_BENDAHARA_PENGELUARAN : Bend.Keluar minta revisi
    PENDING_VERIFIKASI_HONORARIUM --> REVISI_BENDAHARA_PENERIMAAN  : Bend.Terima minta revisi
    PENDING_KASUBBAG --> REVISI_KASUBBAG : Kasubbag minta revisi

    REVISI_PPK --> DRAFT : pembuat perbaiki
    REVISI_PPSPM --> DRAFT : pembuat perbaiki
    REVISI_KOORKEU --> DRAFT : pembuat perbaiki
    REVISI_BENDAHARA_PENGELUARAN --> DRAFT : pembuat perbaiki
    REVISI_BENDAHARA_PENERIMAAN  --> DRAFT : pembuat perbaiki
    REVISI_KASUBBAG --> DRAFT : pembuat perbaiki

    PENDING_VERIFIKASI_HONORARIUM --> DITOLAK : ada penolakan
    PENDING_KASUBBAG --> DITOLAK : Kasubbag tolak
    DITOLAK --> [*]

    DISETUJUI --> SPP_TERBIT : Operator BLU buat SPP
    SPP_TERBIT --> SPM_TERBIT : SPM diverifikasi
    SPM_TERBIT --> SP2D_TERBIT : NPI &amp; SP2D diverifikasi
    SP2D_TERBIT --> CAIR : Bend.Pengeluaran cairkan
    CAIR --> LUNAS : BKU tercatat
    LUNAS --> [*]
```

---

## 8. Sequence Diagram — Verifikasi Paralel Fase 2

Detil interaksi 5 verifikator + Kasubbag pada **Fase 2** (sumber:
`TagihanHonorariumWorkflowService::approveCurrentStep`).

```mermaid
sequenceDiagram
    autonumber
    participant U as PPABP
    participant Sys as SIKEREN
    participant V1 as PPK
    participant V2 as PPSPM
    participant V3 as Koor.Keu
    participant V4 as Bend.Keluar
    participant V5 as Bend.Terima
    participant K as Kasubbag

    U->>Sys: Submit tagihan honorarium
    Sys-->>V1: Notifikasi tugas verifikasi
    Sys-->>V2: Notifikasi tugas verifikasi
    Sys-->>V3: Notifikasi tugas verifikasi
    Sys-->>V4: Notifikasi tugas verifikasi
    Sys-->>V5: Notifikasi tugas verifikasi

    par Approval paralel (Step 1)
        V1->>Sys: Approve / Revisi / Tolak
        V2->>Sys: Approve / Revisi / Tolak
        V3->>Sys: Approve / Revisi / Tolak
        V4->>Sys: Approve / Revisi / Tolak
        V5->>Sys: Approve / Revisi / Tolak
    end

    alt Semua approve
        Sys-->>K: Aktifkan Step 2 (Kasubbag)
        K->>Sys: Approve Final
        Sys-->>U: Status berubah → DISETUJUI
    else Ada revisi / penolakan
        Sys-->>U: Status berubah → REVISI_<ROLE> / DITOLAK
    end
```

---

## 9. Tabel Workflow & Role per Fase

| Fase | Workflow Definition | Step (urutan) | Role | Sifat |
|---|---|---|---|---|
| 2 | `TAGIHAN_HONORARIUM` | 1 (paralel) | PPK · PPSPM · Koor.Keu · Bend.Pengeluaran · Bend.Penerimaan | semua wajib approve |
| 2 | `TAGIHAN_HONORARIUM` | 2 (final) | Kasubbag | finalisasi |
| 3 | `SPP_HONORARIUM_PPK` | 1 (paralel) | PPK · Koor.Keu · Kasubbag | semua wajib approve |
| 4 | `SPM_HONORARIUM_PPSPM` | 1 (paralel) | Kasubbag · PPSPM · Koor.Keu | semua wajib approve |
| 5 | `NPI_HONORARIUM` | 1 (paralel) | Bend.Penerimaan · PPK · Kasubbag · Koor.Keu | semua wajib approve |
| 5 | `SP2D_HONORARIUM` | 1 (paralel) | PPSPM · PPK · Kasubbag · Koor.Keu | semua wajib approve |

> Catatan: meskipun nilai `urutan_step = 1` untuk seluruh role di setiap workflow,
> validasi `is_required = true` membuat workflow hanya boleh berlanjut setelah
> **semua role pada urutan tersebut approve** (logika paralel AND).

---

## 10. Glosarium

| Singkatan | Kepanjangan / Arti |
|---|---|
| **BAPP / BAST / BAP** | Berita Acara Pemeriksaan / Serah Terima / Pembayaran (khusus tagihan kontrak; tidak dipakai di honorarium). |
| **BKU** | Buku Kas Umum — catatan kas masuk/keluar BLU. |
| **DIPA** | Daftar Isian Pelaksanaan Anggaran — pagu yang dipakai sebagai sumber pembayaran honorarium. |
| **NPI** | Nota Pemindahbukuan Internal — dokumen pembukuan potongan PPh 21 honorarium. |
| **PPABP** | Petugas Pengelola Administrasi Belanja Pegawai — penyiap data honorarium. |
| **PPK** | Pejabat Pembuat Komitmen. |
| **PPSPM** | Pejabat Penandatangan Surat Perintah Membayar. |
| **SP2D** | Surat Perintah Pencairan Dana. |
| **SPM** | Surat Perintah Membayar. |
| **SPP** | Surat Perintah Pembayaran. |
| **Kasubbag** | Kepala Subbagian Keuangan dan Tata Usaha. |
| **Koor.Keu** | Koordinator Keuangan. |
| **Bend.Pengeluaran / Bend.Penerimaan** | Bendahara Pengeluaran / Penerimaan. |
