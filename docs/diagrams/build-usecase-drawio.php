<?php

declare(strict_types=1);

/**
 * Generator Use Case Diagram (Draw.io / diagrams.net)
 *
 * Dibaca dari hasil analisis routes/web.php + sidebar untuk SIKEREN-BLU.
 * Eksekusi:  php docs/diagrams/build-usecase-drawio.php
 * Output  :  docs/diagrams/use-case-sikeren.drawio
 *
 * Setiap halaman diagram berisi:
 *   - Boundary persegi panjang (Sistem)
 *   - Actor stick figure di kiri (atau kanan kalau >4 actor)
 *   - Use case ellipse di dalam boundary, di-grid otomatis
 *   - Garis assosiasi actor → use case
 */

/* ============================================================
 | 1. DATA: Use case per role berdasarkan analisis kode.
 |   - Daftar disusun berdasarkan kelompok modul utama agar
 |     diagram terbaca sebagai "tugas pokok" tiap role.
 |============================================================ */
$useCases = [

    // === Modul Administrasi ===
    'Super Admin' => [
        'Mengelola User',
        'Mengatur Role User',
        'Mereset Password User',
        'Mengelola Master Pegawai',
        'Melihat Daftar Role',
        'Mengatur Integrasi API (WhatsApp)',
        'Mengakses Seluruh Modul (override)',
    ],

    // === Modul Anggaran & Pejabat Eksekutif ===
    'KPA' => [
        'Melihat Dashboard Internal',
        'Mengelola DIPA & COA',
        'Memonitor Log Tagihan Bulanan',
        'Memonitor Jatuh Tempo Tagihan Jasa',
        'Memonitor Verifikasi Tagihan Jasa',
        'Melihat Laporan BKU',
    ],
    'PLT/PLH' => [
        'Melihat Dashboard Internal',
        'Mengelola DIPA & COA',
        'Memonitor Log Tagihan Bulanan',
        'Memonitor Jatuh Tempo Tagihan Jasa',
        'Memonitor Verifikasi Tagihan Jasa',
        'Melihat Laporan BKU',
    ],
    'Kepala Subbagian Keuangan dan Tata Usaha' => [
        'Mengelola DIPA & COA',
        'Memverifikasi Tagihan Kontrak',
        'Memverifikasi Tagihan Honorarium',
        'Memverifikasi Tagihan Jasa',
        'Memverifikasi Perjaldin',
        'Memverifikasi SPP Terpadu',
        'Memverifikasi SPM Kontrak/Perjaldin/Honor',
        'Memverifikasi NPI Kontrak',
        'Memverifikasi SP2D Kontrak',
        'Melihat Log Tagihan Bulanan',
        'Melihat Laporan BKU',
    ],
    'Kepala Seksi Pelayanan dan Kerjasama' => [
        'Mengelola DIPA & COA',
        'Memverifikasi Tagihan Jasa (PNBP)',
        'Melihat Log Tagihan Bulanan',
        'Melihat Laporan Mitra Jasa',
        'Melihat Laporan BKU',
    ],

    // === Komitmen & Pengadaan ===
    'PPK' => [
        'Mengelola Kontrak (CRUD, Addendum, Termin)',
        'Mengelola SPK / SPMK / Ringkasan Kontrak',
        'Memverifikasi BAST Tagihan Kontrak',
        'Memverifikasi Perjaldin BLU',
        'Memverifikasi Tagihan Kontrak',
        'Memverifikasi Tagihan Honorarium',
        'Memverifikasi SPP Terpadu',
        'Memverifikasi NPI Kontrak/Honor',
        'Memverifikasi SP2D Kontrak',
        'Melihat Laporan BKU',
    ],
    'Pejabat Pengadaan' => [
        'Mengelola Master Vendor/Supplier',
        'Mengelola Nomor Dokumen',
        'Mengelola Kontrak (CRUD)',
        'Mengelola Addendum & Termin',
        'Mengelola SPK / SPMK / Ringkasan Kontrak',
    ],

    // === Pencairan ===
    'Operator BLU' => [
        'Mengelola DIPA & COA',
        'Mengelola Master Pajak',
        'Mengelola Master Layanan Jasa',
        'Membuat SPP (Perjaldin/Honor/Kontrak)',
        'Mengirim SPP ke Verifikasi',
        'Membuat SPM (Perjaldin/Honor/Kontrak)',
        'Mengirim SPM ke Verifikasi',
        'Mencetak PDF SPP/SPM/NPI/SP2D',
    ],
    'PPSPM' => [
        'Memverifikasi SPM Kontrak',
        'Memverifikasi SPM Perjaldin',
        'Memverifikasi SPM Honorarium',
        'Memverifikasi Perjaldin',
        'Memverifikasi SP2D Kontrak',
    ],
    'Koordinator Keuangan' => [
        'Memverifikasi SPP Terpadu',
        'Memverifikasi SPM Kontrak/Perjaldin/Honor',
        'Memverifikasi NPI Kontrak/Perjaldin/Honor',
        'Memverifikasi SP2D Kontrak/Perjaldin/Honor',
        'Memverifikasi Perjaldin',
        'Memverifikasi Honorarium',
    ],
    'PPABP' => [
        'Mengelola Data Honorarium (CRUD)',
        'Mengunggah Dokumen Pendukung Honor',
        'Mengirim Honorarium ke Verifikasi',
        'Mencetak PDF Honor & Nominatif',
    ],
    'Operator Perjaldin' => [
        'Mengelola Master Uang Harian Perjaldin',
        'Mengelola Perjalanan Dinas (CRUD)',
        'Mengirim Perjaldin ke Workflow',
        'Mencetak PDF Perjaldin & Nominatif',
        'Mengunggah Nominatif TTD',
    ],

    // === Bendahara ===
    'Bendahara Pengeluaran' => [
        'Memverifikasi Perjaldin',
        'Memverifikasi Honorarium',
        'Memverifikasi Tagihan Kontrak',
        'Membuat NPI Perjaldin/Honor/Kontrak',
        'Mengunggah NPI bertanda tangan',
        'Membuat SP2D Perjaldin/Honor/Kontrak',
        'Mengunggah SP2D bertanda tangan',
        'Menyetor Pajak (Umum, Kontrak, Honor)',
        'Mengelola BKU & Buku Pembantu',
        'Memantau Pengesahan Belanja',
    ],
    'Bendahara Penerimaan' => [
        'Memverifikasi Perjaldin',
        'Memverifikasi Tagihan Kontrak',
        'Memverifikasi NPI Kontrak/Perjaldin',
        'Memantau BKU & Buku Pembantu Bank',
        'Memantau Buku Bunga Rekening & Pajak',
        'Memantau Pengesahan Belanja & Piutang',
        'Melihat Laporan BKU',
    ],

    // === Modul Jasa (PNBP) ===
    'Super Admin Jasa' => [
        'Melihat Dashboard Jasa',
        'Mengelola Master Layanan & Tarif Jasa',
        'Mengelola Mitra Jasa & Akun Mitra',
        'Mengelola Kontrak Mitra Jasa',
        'Mengelola Konsesi & PJP2U Mitra',
        'Mengelola Admin Jasa & Layanan-nya',
        'Memverifikasi Tagihan Jasa',
        'Memverifikasi Laporan Utilitas',
        'Membuat Tagihan Utilitas (Listrik/Air)',
        'Melihat Log Tagihan Bulanan & Jatuh Tempo',
    ],
    'Koordinator Jasa' => [
        'Melihat Dashboard Koordinator Jasa',
        'Mengelola Mitra Jasa & Admin Jasa',
        'Mengelola Master Layanan & Tarif Jasa',
        'Memverifikasi Tagihan Jasa',
        'Memantau Log Tagihan Bulanan',
        'Memantau Jatuh Tempo Tagihan',
        'Melihat Laporan Mitra (Konsesi & PJP2U)',
    ],
    'Admin Jasa' => [
        'Melihat Dashboard Admin Jasa',
        'Memverifikasi Laporan Penjualan Konsesi',
        'Memverifikasi Laporan PAX PJP2U',
        'Memverifikasi Laporan Utilitas',
        'Menetapkan Tarif & Membuat Tagihan Utilitas',
        'Membuat Tagihan Jasa',
        'Mengelola Mitra Jasa (sesuai layanan ditugaskan)',
        'Memantau Log Tagihan & Jatuh Tempo',
    ],
    'Admin Konsesi' => [
        'Memverifikasi Laporan Penjualan Konsesi',
        'Memverifikasi Laporan PAX PJP2U',
        'Membuat Tagihan Jasa Konsesi',
        'Memantau Tagihan Konsesi',
    ],

    // === Utilitas ===
    'Admin Listrik' => [
        'Melihat Dashboard Catat Meter',
        'Mencatat Stan Meter Listrik',
        'Mengirim Laporan ke Admin Jasa',
        'Melihat Stan Akhir Periode Sebelumnya',
        'Menghapus Draft Laporan',
    ],
    'Admin Air' => [
        'Melihat Dashboard Catat Meter',
        'Mencatat Stan Meter Air',
        'Mengirim Laporan ke Admin Jasa',
        'Melihat Stan Akhir Periode Sebelumnya',
        'Menghapus Draft Laporan',
    ],

    // === Eksternal ===
    'Mitra' => [
        'Login & Melihat Dashboard Mitra',
        'Mengelola Profil & Password',
    ],
    'Mitra Jasa' => [
        'Login & Melihat Dashboard Mitra',
        'Melihat Layanan Aktif',
        'Melaporkan Penjualan Konsesi',
        'Melaporkan PAX PJP2U',
        'Melihat Tagihan Jasa & Surat Pengantar',
        'Mengunduh Kontrak Jasa',
        'Mengelola Profil & Password',
    ],
];

/* ============================================================
 | 2. PEMBAGIAN HALAMAN
 |============================================================ */
$pages = [
    [
        'name'  => 'Overview',
        'title' => 'Use Case Overview — SIKEREN-BLU',
        'roles' => array_keys($useCases),
        'mode'  => 'overview', // tampilkan semua actor, group use case berdasar modul
    ],
    [
        'name'  => 'Administrasi',
        'title' => 'Modul Administrasi (Super Admin)',
        'roles' => ['Super Admin'],
    ],
    [
        'name'  => 'Anggaran & Eksekutif',
        'title' => 'Anggaran & Pejabat Eksekutif',
        'roles' => [
            'KPA', 'PLT/PLH',
            'Kepala Subbagian Keuangan dan Tata Usaha',
            'Kepala Seksi Pelayanan dan Kerjasama',
        ],
    ],
    [
        'name'  => 'Komitmen & Pengadaan',
        'title' => 'Komitmen & Pengadaan',
        'roles' => ['PPK', 'Pejabat Pengadaan'],
    ],
    [
        'name'  => 'Pencairan',
        'title' => 'Pencairan (SPP/SPM/NPI/SP2D & Honor/Perjaldin)',
        'roles' => [
            'Operator BLU', 'PPSPM', 'Koordinator Keuangan',
            'PPABP', 'Operator Perjaldin',
        ],
    ],
    [
        'name'  => 'Bendahara',
        'title' => 'Bendahara & Pembukuan',
        'roles' => ['Bendahara Pengeluaran', 'Bendahara Penerimaan'],
    ],
    [
        'name'  => 'Modul Jasa (PNBP)',
        'title' => 'Modul Jasa — Layanan PNBP',
        'roles' => [
            'Super Admin Jasa', 'Koordinator Jasa',
            'Admin Jasa', 'Admin Konsesi',
        ],
    ],
    [
        'name'  => 'Utilitas',
        'title' => 'Catat Meter Utilitas',
        'roles' => ['Admin Listrik', 'Admin Air'],
    ],
    [
        'name'  => 'Mitra Eksternal',
        'title' => 'Portal Mitra (Eksternal)',
        'roles' => ['Mitra', 'Mitra Jasa'],
    ],
];

/* ============================================================
 | 3. RENDERER
 |============================================================ */

/**
 * @return string XML <mxCell …/> blok untuk satu halaman.
 */
function renderPage(array $page, array $useCases): string
{
    $isOverview = ($page['mode'] ?? null) === 'overview';
    if ($isOverview) {
        return renderOverviewPage($page, $useCases);
    }
    return renderPerRolePage($page, $useCases);
}

function renderPerRolePage(array $page, array $useCases): string
{
    $roles = $page['roles'];

    // Layout konstanta
    $actorWidth   = 40;
    $actorHeight  = 60;
    $actorGapY    = 60;
    $ucWidth      = 240;
    $ucHeight     = 50;
    $ucGapY       = 24;
    $columnGap    = 90;
    $marginLeft   = 60;
    $boundaryPadX = 40;
    $boundaryPadY = 90;

    // Hitung jumlah use case unik (yang dipilih untuk halaman ini)
    $allUcs = [];
    foreach ($roles as $role) {
        foreach (($useCases[$role] ?? []) as $uc) {
            $allUcs[] = $uc;
        }
    }
    $uniqUcs = array_values(array_unique($allUcs));

    // Untuk overview: tampilkan use case bertumpuk dalam beberapa kolom (max 18 per kolom)
    $ucPerColumn = 18;
    $ucColumns   = max(1, (int) ceil(count($uniqUcs) / $ucPerColumn));

    // Hitung ukuran area aktor (kiri) dan use case (kanan)
    $actorColTop  = 120;
    $actorAreaHeight = max(count($roles), 1) * ($actorHeight + $actorGapY);

    $ucBoundaryX = $marginLeft + $actorWidth + 240; // ada jarak nama actor
    $ucBoundaryY = 80;
    $ucAreaWidth  = $ucColumns * $ucWidth + ($ucColumns - 1) * 30;
    $ucAreaHeight = $ucPerColumn * ($ucHeight + $ucGapY);

    $boundaryWidth  = $ucAreaWidth + 2 * $boundaryPadX;
    $boundaryHeight = max($ucAreaHeight + $boundaryPadY, $actorAreaHeight + 100);

    $cells = [];
    $idCounter = 100;

    // 3.1 Judul
    $titleId = $idCounter++;
    $cells[] = sprintf(
        '<mxCell id="%d" value="%s" style="text;html=1;fontSize=18;fontStyle=1;align=left;verticalAlign=middle;" vertex="1" parent="1"><mxGeometry x="%d" y="20" width="%d" height="40" as="geometry"/></mxCell>',
        $titleId,
        htmlspecialchars($page['title'], ENT_XML1),
        $marginLeft,
        $boundaryWidth + 600
    );

    // 3.2 Boundary (system)
    $boundaryId = $idCounter++;
    $cells[] = sprintf(
        '<mxCell id="%d" value="SIKEREN-BLU" style="rounded=0;whiteSpace=wrap;html=1;fillColor=none;strokeColor=#6c8ebf;fontSize=12;fontStyle=1;verticalAlign=top;align=right;spacingTop=10;spacingRight=14;" vertex="1" parent="1"><mxGeometry x="%d" y="%d" width="%d" height="%d" as="geometry"/></mxCell>',
        $boundaryId,
        $ucBoundaryX,
        $ucBoundaryY,
        $boundaryWidth,
        $boundaryHeight
    );

    // 3.3 Use case ellipse, kelompokkan agar use case yang sama dishare
    $ucIds = []; // label => cellId
    $col = 0; $rowInCol = 0;
    foreach ($uniqUcs as $uc) {
        $cellId = $idCounter++;
        $x = $ucBoundaryX + $boundaryPadX + $col * ($ucWidth + 30);
        $y = $ucBoundaryY + $boundaryPadY + $rowInCol * ($ucHeight + $ucGapY);
        $cells[] = sprintf(
            '<mxCell id="%d" value="%s" style="ellipse;whiteSpace=wrap;html=1;fillColor=#dae8fc;strokeColor=#6c8ebf;fontSize=11;align=center;" vertex="1" parent="1"><mxGeometry x="%d" y="%d" width="%d" height="%d" as="geometry"/></mxCell>',
            $cellId,
            htmlspecialchars($uc, ENT_XML1),
            $x,
            $y,
            $ucWidth,
            $ucHeight
        );
        $ucIds[$uc] = $cellId;

        $rowInCol++;
        if ($rowInCol >= $ucPerColumn) {
            $rowInCol = 0;
            $col++;
        }
    }

    // 3.4 Actor (umlActor) di kiri
    $actorIds = [];
    $palette = ['#fff2cc', '#d5e8d4', '#f8cecc', '#e1d5e7', '#d4e1f5', '#fad9c1', '#cce5ff', '#dae8fc'];
    $actorAreaTop = $ucBoundaryY + 40;
    foreach ($roles as $i => $role) {
        $cellId = $idCounter++;
        $x = $marginLeft;
        $y = $actorAreaTop + $i * ($actorHeight + $actorGapY);
        $color = $palette[$i % count($palette)];
        $cells[] = sprintf(
            '<mxCell id="%d" value="%s" style="shape=umlActor;html=1;fillColor=%s;strokeColor=#666666;verticalLabelPosition=bottom;verticalAlign=top;fontSize=11;fontStyle=1;" vertex="1" parent="1"><mxGeometry x="%d" y="%d" width="%d" height="%d" as="geometry"/></mxCell>',
            $cellId,
            htmlspecialchars($role, ENT_XML1),
            $color,
            $x,
            $y,
            $actorWidth,
            $actorHeight
        );
        $actorIds[$role] = $cellId;
    }

    // 3.5 Edge actor → use case
    foreach ($roles as $role) {
        if (! isset($actorIds[$role])) continue;
        foreach (($useCases[$role] ?? []) as $uc) {
            if (! isset($ucIds[$uc])) continue;
            $edgeId = $idCounter++;
            $cells[] = sprintf(
                '<mxCell id="%d" style="edgeStyle=none;rounded=0;html=1;strokeColor=#999999;endArrow=none;startArrow=none;exitX=1;exitY=0.5;exitDx=0;exitDy=0;entryX=0;entryY=0.5;entryDx=0;entryDy=0;" edge="1" parent="1" source="%d" target="%d"><mxGeometry relative="1" as="geometry"/></mxCell>',
                $edgeId,
                $actorIds[$role],
                $ucIds[$uc]
            );
        }
    }

    // 3.6 Legenda kecil
    $legendId = $idCounter++;
    $legendY = $ucBoundaryY + $boundaryHeight + 30;
    $cells[] = sprintf(
        '<mxCell id="%d" value="%s" style="text;html=1;fontSize=10;align=left;verticalAlign=top;fontColor=#666666;" vertex="1" parent="1"><mxGeometry x="%d" y="%d" width="%d" height="40" as="geometry"/></mxCell>',
        $legendId,
        'Diagram di-generate otomatis dari analisis routes/web.php &amp; sidebar SIKEREN-BLU.&#10;Garis tanpa panah = relasi assosiasi actor → use case.',
        $marginLeft,
        $legendY,
        700
    );

    return implode("\n        ", $cells);
}

/**
 * Halaman overview: tampilkan actor dikelompokkan per modul + cluster use case
 * (modul) sebagai ringkasan. Lebih mudah dibaca daripada 102 ellipse.
 */
function renderOverviewPage(array $page, array $useCases): string
{
    $modules = [
        'Administrasi' => ['Super Admin'],
        'Anggaran & Eksekutif' => [
            'KPA', 'PLT/PLH',
            'Kepala Subbagian Keuangan dan Tata Usaha',
            'Kepala Seksi Pelayanan dan Kerjasama',
        ],
        'Komitmen & Pengadaan' => ['PPK', 'Pejabat Pengadaan'],
        'Pencairan' => [
            'Operator BLU', 'PPSPM', 'Koordinator Keuangan',
            'PPABP', 'Operator Perjaldin',
        ],
        'Bendahara & Pembukuan' => ['Bendahara Pengeluaran', 'Bendahara Penerimaan'],
        'Modul Jasa (PNBP)' => [
            'Super Admin Jasa', 'Koordinator Jasa', 'Admin Jasa', 'Admin Konsesi',
        ],
        'Utilitas' => ['Admin Listrik', 'Admin Air'],
        'Mitra Eksternal' => ['Mitra', 'Mitra Jasa'],
    ];

    $actorWidth   = 40;
    $actorHeight  = 60;
    $actorGapY    = 22;
    $clusterWidth = 320;
    $clusterPadV  = 40;
    $clusterGapV  = 30;
    $marginLeft   = 80;
    $clusterX     = 460;
    $titleY       = 20;

    $cells = [];
    $idCounter = 100;

    // Title
    $cells[] = sprintf(
        '<mxCell id="%d" value="%s" style="text;html=1;fontSize=18;fontStyle=1;align=left;verticalAlign=middle;" vertex="1" parent="1"><mxGeometry x="%d" y="%d" width="1600" height="40" as="geometry"/></mxCell>',
        $idCounter++,
        htmlspecialchars($page['title'], ENT_XML1),
        $marginLeft,
        $titleY
    );

    $palette = ['#fff2cc', '#d5e8d4', '#f8cecc', '#e1d5e7', '#d4e1f5', '#fad9c1', '#cce5ff', '#dae8fc'];
    $clusterColors = ['#dae8fc', '#d5e8d4', '#fff2cc', '#f8cecc', '#e1d5e7', '#cce5ff', '#fad9c1', '#dae8fc'];

    $currentY = 80;
    $modIndex = 0;

    foreach ($modules as $moduleName => $roles) {
        $clusterColor = $clusterColors[$modIndex % count($clusterColors)];
        $modIndex++;

        // Tinggi cluster ditentukan jumlah actor di modul ini
        $clusterHeight = max(
            count($roles) * ($actorHeight + $actorGapY) - $actorGapY + 2 * $clusterPadV,
            120
        );

        // Cluster (modul) box
        $clusterId = $idCounter++;
        $cells[] = sprintf(
            '<mxCell id="%d" value="%s" style="rounded=1;whiteSpace=wrap;html=1;fillColor=%s;strokeColor=#6c8ebf;fontSize=14;fontStyle=1;verticalAlign=top;align=center;spacingTop=10;" vertex="1" parent="1"><mxGeometry x="%d" y="%d" width="%d" height="%d" as="geometry"/></mxCell>',
            $clusterId,
            htmlspecialchars($moduleName, ENT_XML1),
            $clusterColor,
            $clusterX,
            $currentY,
            $clusterWidth,
            $clusterHeight
        );

        // Actor & garis ke cluster
        foreach ($roles as $i => $role) {
            $actorId = $idCounter++;
            $ax = $marginLeft;
            $ay = $currentY + $clusterPadV + $i * ($actorHeight + $actorGapY);
            $color = $palette[$i % count($palette)];
            $cells[] = sprintf(
                '<mxCell id="%d" value="%s" style="shape=umlActor;html=1;fillColor=%s;strokeColor=#666666;verticalLabelPosition=bottom;verticalAlign=top;fontSize=10;fontStyle=1;" vertex="1" parent="1"><mxGeometry x="%d" y="%d" width="%d" height="%d" as="geometry"/></mxCell>',
                $actorId,
                htmlspecialchars($role, ENT_XML1),
                $color,
                $ax,
                $ay,
                $actorWidth,
                $actorHeight
            );

            $cells[] = sprintf(
                '<mxCell id="%d" style="edgeStyle=none;rounded=0;html=1;strokeColor=#999999;endArrow=none;startArrow=none;exitX=1;exitY=0.5;exitDx=0;exitDy=0;entryX=0;entryY=0.5;entryDx=0;entryDy=0;" edge="1" parent="1" source="%d" target="%d"><mxGeometry relative="1" as="geometry"/></mxCell>',
                $idCounter++,
                $actorId,
                $clusterId
            );

            // Tampilkan jumlah use case sebagai sub-label kecil di dalam cluster
            $ucCount = count($useCases[$role] ?? []);
            $cells[] = sprintf(
                '<mxCell id="%d" value="%s — %d use case" style="text;html=1;fontSize=10;align=left;verticalAlign=middle;fontColor=#444444;" vertex="1" parent="1"><mxGeometry x="%d" y="%d" width="%d" height="20" as="geometry"/></mxCell>',
                $idCounter++,
                htmlspecialchars($role, ENT_XML1),
                $ucCount,
                $clusterX + 15,
                $currentY + $clusterPadV - 5 + $i * ($actorHeight + $actorGapY) + ($actorHeight / 2) - 10,
                $clusterWidth - 30
            );
        }

        $currentY += $clusterHeight + $clusterGapV;
    }

    // Legend
    $cells[] = sprintf(
        '<mxCell id="%d" value="%s" style="text;html=1;fontSize=10;align=left;verticalAlign=top;fontColor=#666666;" vertex="1" parent="1"><mxGeometry x="%d" y="%d" width="800" height="60" as="geometry"/></mxCell>',
        $idCounter++,
        'Diagram overview: kelompok modul SIKEREN-BLU dan actor yang terlibat. Lihat halaman per-modul untuk daftar use case detail.',
        $marginLeft,
        $currentY + 20
    );

    return implode("\n        ", $cells);
}

/* ============================================================
 | 4. ASSEMBLE FILE
 |============================================================ */

$diagrams = '';
foreach ($pages as $i => $page) {
    $body = renderPage($page, $useCases);
    $diagramId = 'diag-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
    $pageName = htmlspecialchars($page['name'], ENT_XML1 | ENT_QUOTES);
    $diagrams .= <<<XML

  <diagram id="{$diagramId}" name="{$pageName}">
    <mxGraphModel dx="1422" dy="827" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="2200" pageHeight="1700" math="0" shadow="0">
      <root>
        <mxCell id="0"/>
        <mxCell id="1" parent="0"/>
        {$body}
      </root>
    </mxGraphModel>
  </diagram>
XML;
}

$generatedAt = date('Y-m-d H:i');
$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mxfile host="diagrams.net" modified="{$generatedAt}" agent="SIKEREN UseCase Generator" version="22.0.0">
{$diagrams}
</mxfile>
XML;

$out = __DIR__ . '/use-case-sikeren.drawio';
file_put_contents($out, $xml);

echo "✓ Tergenerate: {$out}\n";
echo "  - " . count($pages) . " halaman\n";
$totalUc = 0;
foreach ($useCases as $u) $totalUc += count($u);
echo "  - " . count($useCases) . " role / actor\n";
echo "  - " . $totalUc . " baris use case (sebelum dedup per halaman)\n";
echo "Buka file di app.diagrams.net atau VS Code Draw.io extension.\n";
