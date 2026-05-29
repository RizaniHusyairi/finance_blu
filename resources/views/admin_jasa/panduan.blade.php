@extends('layouts.app')
@section('title', 'Panduan Admin Jasa')

@section('content')
@php
    $flowSteps = [
        [
            'no' => '01',
            'icon' => 'bi-database-check',
            'title' => 'Sumber Data',
            'desc' => 'Tagihan manual, PAX PJP2U, konsesi, listrik, atau air.',
            'tone' => 'blue',
            'ui' => ['Laporan Harian', 'Data Mitra', 'Tarif Aktif'],
            'video' => null,
            'guide' => [
                'Cek data mitra yang akan ditagihkan: NPWP, alamat, email, dan nomor WhatsApp.',
                'Pastikan layanan dan tarif aktif sudah sesuai dengan jenis tagihan.',
                'Untuk PJP2U/konsesi/utilitas, gunakan laporan yang sudah diverifikasi sebagai dasar tagihan.',
            ],
            'tips' => 'Jangan lanjut buat tagihan kalau layanan belum punya tarif atau kode akun.',
            'short' => [
                ['icon' => 'bi-search', 'title' => 'Mulai dari data sumber', 'body' => 'Pilih laporan manual, PJP2U, konsesi, listrik, air, atau rincian Garbarata.'],
                ['icon' => 'bi-building-check', 'title' => 'Cek mitra', 'body' => 'Pastikan NPWP, alamat, email, dan WhatsApp mitra sudah benar.'],
                ['icon' => 'bi-tags', 'title' => 'Cek tarif aktif', 'body' => 'Layanan harus aktif, punya tarif, satuan, dan kode pembayaran.'],
            ],
        ],
        [
            'no' => '02',
            'icon' => 'bi-receipt',
            'title' => 'Buat Tagihan',
            'desc' => 'Pilih mitra, layanan, volume, tarif, dan dokumen dasar.',
            'tone' => 'cyan',
            'ui' => ['Form Tagihan', 'Rincian Layanan', 'Simpan Draft'],
            'video' => null,
            'guide' => [
                'Buka menu Buat Tagihan lalu pilih mitra jasa.',
                'Pilih dokumen dasar jika tagihan terkait kontrak.',
                'Tambahkan layanan, isi volume, tarif, satuan, dan keterangan.',
                'Simpan draf agar sistem membuat nomor tagihan dan draft surat pengantar.',
            ],
            'tips' => 'Untuk Garbarata, isi rincian penerbangan agar volume per 2 jam dihitung otomatis.',
            'short' => [
                ['icon' => 'bi-person-vcard', 'title' => 'Pilih mitra', 'body' => 'Buka Buat Tagihan, pilih mitra, tanggal, dan dokumen dasar.'],
                ['icon' => 'bi-list-check', 'title' => 'Tambah layanan', 'body' => 'Isi layanan, volume, tarif, satuan, serta keterangan perhitungan.'],
                ['icon' => 'bi-save', 'title' => 'Simpan draf', 'body' => 'Sistem membuat nomor tagihan dan draft surat pengantar otomatis.'],
            ],
        ],
        [
            'no' => '03',
            'icon' => 'bi-envelope-paper',
            'title' => 'Draft Surat',
            'desc' => 'Sistem membuat surat pengantar dengan QR keaslian dokumen.',
            'tone' => 'indigo',
            'ui' => ['Surat Pengantar', 'QR Validasi', 'Preview PDF'],
            'video' => null,
            'guide' => [
                'Buka detail tagihan lalu cek bagian Surat Pengantar.',
                'Gunakan Preview Draft untuk memastikan kop, nomor surat, dan nota tagihan sudah benar.',
                'Jika nomor/tanggal/perihal perlu diganti, edit data surat pengantar sebelum verifikasi final.',
                'Draft yang dipreview akan tersimpan ke arsip draft.',
            ],
            'tips' => 'QR pada draft hanya untuk validasi dokumen, bukan QR tanda tangan final.',
            'short' => [
                ['icon' => 'bi-file-earmark-pdf', 'title' => 'Preview draft', 'body' => 'Buka detail tagihan dan cek tampilan surat pengantar.'],
                ['icon' => 'bi-pencil-square', 'title' => 'Edit data surat', 'body' => 'Sesuaikan nomor surat, tanggal, atau perihal sebelum final.'],
                ['icon' => 'bi-qr-code', 'title' => 'QR validasi', 'body' => 'Draft memakai QR keaslian dokumen, belum QR tanda tangan.'],
            ],
        ],
        [
            'no' => '04',
            'icon' => 'bi-diagram-3',
            'title' => 'Workflow',
            'desc' => 'Tagihan masuk verifikasi. Jika revisi, Admin Jasa edit ulang.',
            'tone' => 'amber',
            'ui' => ['Koordinator', 'Kasubag', 'Kaban'],
            'video' => null,
            'guide' => [
                'Setelah tagihan dibuat, workflow berjalan ke verifikator sesuai urutan.',
                'Pantau status pada detail tagihan atau log tagihan bulanan.',
                'Jika ada revisi, baca catatan verifikator lalu edit ulang data tagihan.',
                'Kirim ulang agar tagihan kembali masuk proses verifikasi.',
            ],
            'tips' => 'Catatan revisi sebaiknya diselesaikan dulu sebelum membuat draft surat ulang.',
            'short' => [
                ['icon' => 'bi-send-check', 'title' => 'Masuk verifikasi', 'body' => 'Tagihan berjalan ke Koordinator, Kasubag, lalu pejabat final.'],
                ['icon' => 'bi-chat-left-text', 'title' => 'Baca catatan', 'body' => 'Jika revisi, lihat catatan verifikator di detail tagihan.'],
                ['icon' => 'bi-arrow-repeat', 'title' => 'Kirim ulang', 'body' => 'Perbaiki data lalu kirim ulang agar workflow lanjut kembali.'],
            ],
        ],
        [
            'no' => '05',
            'icon' => 'bi-patch-check',
            'title' => 'Final TTD',
            'desc' => 'Setelah disetujui final, generate surat final bertanda tangan elektronik.',
            'tone' => 'green',
            'ui' => ['Generate Final', 'TTD KPA/PLH', 'Hash Cocok'],
            'video' => null,
            'guide' => [
                'Tunggu sampai semua verifikator menyetujui tagihan.',
                'Sistem membuat Surat Final TTD setelah verifikasi terakhir selesai.',
                'Buka Lihat Surat Pengantar TTD untuk memastikan QR tanda tangan muncul.',
                'Scan QR untuk mengecek halaman TTE dan hash dokumen.',
            ],
            'tips' => 'Surat final TTD tidak perlu digenerate manual berulang jika sudah tersedia.',
            'short' => [
                ['icon' => 'bi-check2-all', 'title' => 'Tunggu final', 'body' => 'Pastikan semua verifikator sudah menyetujui tagihan.'],
                ['icon' => 'bi-patch-check', 'title' => 'Final TTD otomatis', 'body' => 'Surat final bertanda tangan muncul setelah verifikasi terakhir.'],
                ['icon' => 'bi-fingerprint', 'title' => 'Scan QR TTE', 'body' => 'QR menampilkan identitas penandatangan dan hash dokumen.'],
            ],
        ],
        [
            'no' => '06',
            'icon' => 'bi-send',
            'title' => 'Publish',
            'desc' => 'Sistem membuat VA dan mengirim notifikasi WhatsApp ke mitra.',
            'tone' => 'teal',
            'ui' => ['Publish', 'Virtual Account', 'WhatsApp'],
            'video' => null,
            'guide' => [
                'Pastikan status workflow sudah final dan Surat Final TTD tersedia.',
                'Klik Publish pada detail tagihan.',
                'Sistem membuat Virtual Account dan link tagihan untuk mitra.',
                'Cek nomor VA dan pesan notifikasi sebelum dikirimkan ke mitra.',
            ],
            'tips' => 'Publish dikunci jika surat final TTD belum tersedia.',
            'short' => [
                ['icon' => 'bi-lock', 'title' => 'Cek syarat publish', 'body' => 'Workflow harus final dan Surat Pengantar TTD harus tersedia.'],
                ['icon' => 'bi-credit-card-2-front', 'title' => 'VA dibuat', 'body' => 'Saat publish, sistem membuat Virtual Account pembayaran.'],
                ['icon' => 'bi-whatsapp', 'title' => 'Kirim ke mitra', 'body' => 'Link tagihan dan informasi VA siap dikirim ke WhatsApp mitra.'],
            ],
        ],
        [
            'no' => '07',
            'icon' => 'bi-credit-card',
            'title' => 'Pembayaran',
            'desc' => 'Pembayaran masuk dari VA atau ditandai lunas manual sesuai kewenangan.',
            'tone' => 'purple',
            'ui' => ['VA Callback', 'Lunas Manual', 'Denda Jatuh Tempo'],
            'video' => null,
            'guide' => [
                'Pantau status pembayaran dari log tagihan bulanan.',
                'Pembayaran VA akan mengubah status setelah callback diterima.',
                'Jika pembayaran dicatat manual, pastikan bukti setor sudah sesuai.',
                'Cek jatuh tempo dan denda jika tagihan belum lunas.',
            ],
            'tips' => 'Status bayar dan status workflow adalah dua hal berbeda.',
            'short' => [
                ['icon' => 'bi-hourglass-split', 'title' => 'Pantau pembayaran', 'body' => 'Lihat status bayar di log tagihan bulanan.'],
                ['icon' => 'bi-broadcast', 'title' => 'Callback VA', 'body' => 'Pembayaran VA mengubah status setelah callback diterima.'],
                ['icon' => 'bi-exclamation-triangle', 'title' => 'Cek jatuh tempo', 'body' => 'Tagihan lewat tempo dapat dikenakan denda sesuai aturan.'],
            ],
        ],
        [
            'no' => '08',
            'icon' => 'bi-archive',
            'title' => 'Arsip & Laporan',
            'desc' => 'Draft, final TTD, status, dan pembayaran tersimpan untuk audit.',
            'tone' => 'slate',
            'ui' => ['Arsip Aktif', 'Riwayat Versi', 'Rekap Bulanan'],
            'video' => null,
            'guide' => [
                'Buka detail tagihan untuk melihat arsip surat pengantar.',
                'Gunakan Log Tagihan Bulanan untuk monitoring rekap tagihan.',
                'Export PDF/Excel jika diperlukan untuk laporan bulanan.',
                'Pastikan arsip final TTD aktif adalah versi terbaru.',
            ],
            'tips' => 'Riwayat versi tetap disimpan untuk kebutuhan audit.',
            'short' => [
                ['icon' => 'bi-folder2-open', 'title' => 'Buka arsip', 'body' => 'Draft dan final TTD tersedia di detail tagihan.'],
                ['icon' => 'bi-clock-history', 'title' => 'Riwayat versi', 'body' => 'Versi aktif terbaru tampil, versi lama tetap jadi riwayat.'],
                ['icon' => 'bi-file-earmark-spreadsheet', 'title' => 'Rekap bulanan', 'body' => 'Export PDF atau Excel untuk laporan dan audit.'],
            ],
        ],
    ];

    $steps = [
        [
            'title' => '1. Cek data dasar sebelum membuat tagihan',
            'items' => [
                'Pastikan mitra jasa sudah aktif dan memiliki NPWP, alamat, email, serta kontak WhatsApp yang benar.',
                'Pastikan layanan jasa yang akan ditagihkan sudah aktif dan tarif/kode akun sudah sesuai.',
                'Jika tagihan berasal dari kontrak, pastikan dokumen dasar/kontrak sudah terhubung dengan mitra.',
            ],
        ],
        [
            'title' => '2. Buat tagihan jasa manual',
            'items' => [
                'Buka menu Tagihan > Buat Tagihan.',
                'Pilih mitra, dokumen dasar, tanggal tagihan, dan layanan yang ditagihkan.',
                'Isi volume, harga satuan, satuan, dan keterangan perhitungan layanan.',
                'Simpan tagihan. Sistem akan membuat nomor tagihan, draft surat pengantar, dan memulai workflow verifikasi.',
            ],
        ],
        [
            'title' => '3. Tagihan dari laporan Konsesi, PJP2U, Listrik, atau Air',
            'items' => [
                'Untuk PJP2U, laporan PAX harian yang sudah diajukan dapat langsung diverifikasi dan dibuat tagihan.',
                'Untuk konsesi, gunakan laporan penjualan/omzet yang sudah diverifikasi sebagai dasar tagihan.',
                'Untuk listrik/air, verifikasi laporan pemakaian lebih dulu lalu buat tagihan dari data pemakaian.',
                'Pastikan nomor tagihan PJP2U memakai prefix TAG-PJP2U, bukan TAG-KONSESI.',
            ],
        ],
        [
            'title' => '4. Kelola surat pengantar',
            'items' => [
                'Saat tagihan dibuat, draft surat pengantar sudah tersedia dan memiliki QR verifikasi keaslian dokumen.',
                'Gunakan Edit Data Surat Pengantar jika nomor surat, tanggal, atau perihal perlu disesuaikan sebelum final.',
                'Draft yang dipreview akan tersimpan ke arsip sebagai SURAT_PENGANTAR_DRAFT.',
                'Setelah seluruh verifikasi disetujui, generate Surat Final TTD agar QR tanda tangan elektronik muncul.',
            ],
        ],
        [
            'title' => '5. Verifikasi dan revisi',
            'items' => [
                'Jika verifikator meminta revisi, status tagihan menjadi REVISI dan Admin Jasa dapat edit ulang tagihan.',
                'Perbaiki data layanan, nominal, dokumen dasar, atau surat pengantar sesuai catatan.',
                'Kirim ulang tagihan agar workflow kembali berjalan dari tahapan verifikasi.',
            ],
        ],
        [
            'title' => '6. Publish dan pembayaran',
            'items' => [
                'Publish hanya dapat dilakukan setelah workflow disetujui final dan Surat Final TTD sudah digenerate.',
                'Saat publish, sistem membuat Virtual Account dan mengirim notifikasi WhatsApp ke mitra.',
                'Tagihan jatuh tempo akan menghitung denda 2% per hari dari total tagihan jika belum lunas.',
                'Status pembayaran berubah setelah callback VA diterima atau pembayaran ditandai lunas manual oleh petugas berwenang.',
            ],
        ],
        [
            'title' => '7. Arsip dokumen',
            'items' => [
                'Arsip surat pengantar tersimpan pada storage public di folder arsip-dokumen/TagihanJasa/{nomor-tagihan}.',
                'Metadata arsip tersimpan di tabel arsip_dokumen dan terhubung langsung ke TagihanJasa.',
                'Jenis arsip yang dipakai: SURAT_PENGANTAR_DRAFT dan SURAT_PENGANTAR_FINAL_TTD.',
                'Versi terbaru ditandai Aktif, sedangkan versi lama tetap disimpan sebagai riwayat.',
            ],
        ],
    ];
@endphp

<style>
    .guide-hero {
        position: relative;
        overflow: hidden;
        isolation: isolate;
        background: linear-gradient(135deg, #12355c, #1d65a6);
        border-radius: 18px;
        color: #fff;
        padding: 28px;
        box-shadow: 0 16px 38px rgba(18, 53, 92, .18);
    }
    .guide-hero::before {
        content: "";
        position: absolute;
        inset: -60% auto auto 48%;
        width: 420px;
        height: 420px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 255, 255, .24), rgba(255, 255, 255, 0) 66%);
        animation: heroGlow 7s ease-in-out infinite alternate;
        z-index: -1;
    }
    .guide-hero::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(110deg, transparent 0%, rgba(255, 255, 255, .14) 42%, transparent 62%);
        transform: translateX(-100%);
        animation: heroSweep 5.5s ease-in-out infinite;
        z-index: -1;
    }
    .guide-hero h3,
    .guide-hero p,
    .guide-hero .small {
        color: #fff !important;
    }
    .guide-hero p,
    .guide-hero .text-white-50 {
        color: rgba(255, 255, 255, .86) !important;
    }
    .guide-card {
        border: 1px solid #e5edf7;
        border-radius: 16px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
    }
    .guide-card h6 { color: #12355c; }
    .guide-list li { margin-bottom: .55rem; }
    .guide-pill {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        padding: .35rem .75rem;
        font-weight: 700;
        font-size: .78rem;
    }
    .flow-board {
        position: relative;
        overflow: hidden;
        background:
            radial-gradient(circle at 12% 18%, rgba(37, 99, 235, .12), transparent 30%),
            radial-gradient(circle at 86% 10%, rgba(20, 184, 166, .12), transparent 28%),
            linear-gradient(135deg, #ffffff 0%, #f8fbff 48%, #ffffff 100%);
        border: 1px solid #e5edf7;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
    }
    .flow-board::before {
        content: "";
        position: absolute;
        inset: -80px;
        background:
            linear-gradient(90deg, rgba(37, 99, 235, .08) 1px, transparent 1px),
            linear-gradient(0deg, rgba(14, 165, 233, .08) 1px, transparent 1px);
        background-size: 48px 48px;
        -webkit-mask-image: linear-gradient(180deg, rgba(0,0,0,.8), rgba(0,0,0,.12));
        mask-image: linear-gradient(180deg, rgba(0,0,0,.8), rgba(0,0,0,.12));
        animation: boardDrift 18s linear infinite;
        pointer-events: none;
    }
    .flow-board > * {
        position: relative;
        z-index: 1;
    }
    .flow-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }
    .flow-card {
        position: relative;
        overflow: hidden;
        min-height: 228px;
        width: 100%;
        text-align: left;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 16px;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, .94) 0%, rgba(248, 250, 252, .96) 100%);
        box-shadow: 0 14px 28px rgba(15, 23, 42, .06);
        cursor: pointer;
        translate: 0 0;
        transform: translateY(0);
        animation: cardFloat 4.8s ease-in-out infinite;
        animation-delay: var(--delay);
        transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
        will-change: translate, transform;
    }
    .flow-card::after {
        content: "";
        position: absolute;
        top: 50%;
        right: -15px;
        width: 14px;
        height: 2px;
        background: linear-gradient(90deg, #bfdbfe, #2563eb, #bfdbfe);
        background-size: 220% 100%;
        animation: connectorMove 1.8s linear infinite;
    }
    .flow-card::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(115deg, transparent 0%, rgba(255, 255, 255, .82) 42%, transparent 58%);
        transform: translateX(-120%);
        animation: cardShine 6s ease-in-out infinite;
        animation-delay: calc(var(--delay) + 400ms);
        pointer-events: none;
    }
    .flow-card:focus-visible {
        outline: 4px solid rgba(59, 130, 246, .25);
        outline-offset: 3px;
    }
    .flow-card:hover {
        transform: translateY(-8px) scale(1.015);
        border-color: #93c5fd;
        box-shadow: 0 22px 42px rgba(37, 99, 235, .15);
    }
    .flow-card:nth-child(4)::after,
    .flow-card:nth-child(8)::after {
        display: none;
    }
    .flow-icon {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        color: #fff;
        margin-bottom: 12px;
        box-shadow: 0 8px 18px rgba(15, 23, 42, .12);
        animation: iconPulse 2.6s ease-in-out infinite;
        animation-delay: var(--delay);
    }
    .tone-blue { background: #2563eb; }
    .tone-cyan { background: #0891b2; }
    .tone-indigo { background: #4f46e5; }
    .tone-amber { background: #d97706; }
    .tone-green { background: #16a34a; }
    .tone-teal { background: #0f766e; }
    .tone-purple { background: #7c3aed; }
    .tone-slate { background: #475569; }
    .flow-no {
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .08em;
        color: #64748b;
        text-transform: uppercase;
    }
    .flow-title {
        color: #0f172a;
        font-weight: 800;
        margin-bottom: 6px;
    }
    .flow-desc {
        color: #64748b;
        font-size: .86rem;
        line-height: 1.45;
        margin: 0;
    }
    .flow-mini {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 14px;
        padding-bottom: 18px;
    }
    .flow-mini span {
        border: 1px solid #dbeafe;
        background: rgba(239, 246, 255, .82);
        color: #1e40af;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 800;
        padding: 4px 8px;
        white-space: nowrap;
    }
    .flow-open {
        position: absolute;
        top: 14px;
        right: 14px;
        color: #2563eb;
        font-size: .78rem;
        font-weight: 900;
        opacity: .78;
    }
    .flow-progress {
        position: absolute;
        left: 16px;
        right: 16px;
        bottom: 14px;
        height: 5px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
    }
    .flow-progress span {
        display: block;
        width: 58%;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #2563eb, #06b6d4, #22c55e);
        animation: progressRun 2.8s ease-in-out infinite;
        animation-delay: var(--delay);
    }
    .flow-note {
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1e3a8a;
        border-radius: 14px;
        padding: 12px 14px;
    }
    .tutorial-video {
        border: 1px solid #dbeafe;
        border-radius: 16px;
        background:
            radial-gradient(circle at 18% 12%, rgba(59, 130, 246, .22), transparent 30%),
            radial-gradient(circle at 84% 78%, rgba(20, 184, 166, .18), transparent 28%),
            linear-gradient(135deg, #eff6ff, #ffffff);
        min-height: 430px;
        display: grid;
        place-items: center;
        overflow: hidden;
        padding: 16px;
    }
    .tutorial-video iframe,
    .tutorial-video video {
        width: 100%;
        min-height: 260px;
        border: 0;
        display: block;
    }
    .tutorial-placeholder {
        max-width: 420px;
        padding: 24px;
        text-align: center;
        color: #475569;
    }
    .tutorial-placeholder i {
        color: #2563eb;
        font-size: 42px;
    }
    .short-player {
        position: relative;
        width: min(300px, 100%);
        aspect-ratio: 9 / 16;
        border-radius: 30px;
        overflow: hidden;
        color: #fff;
        background:
            radial-gradient(circle at 18% 12%, rgba(125, 211, 252, .38), transparent 28%),
            radial-gradient(circle at 86% 86%, rgba(34, 197, 94, .34), transparent 28%),
            linear-gradient(155deg, #0f172a 0%, #164e63 52%, #14532d 100%);
        box-shadow: 0 24px 52px rgba(15, 23, 42, .28);
    }
    .short-player::before {
        content: "";
        position: absolute;
        inset: 12px;
        border: 1px solid rgba(255, 255, 255, .18);
        border-radius: 24px;
        pointer-events: none;
    }
    .short-topbar {
        position: absolute;
        z-index: 3;
        top: 18px;
        left: 18px;
        right: 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .short-live {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid rgba(255,255,255,.28);
        border-radius: 999px;
        background: rgba(255,255,255,.14);
        padding: 5px 9px;
        backdrop-filter: blur(10px);
    }
    .short-live span {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #22c55e;
        box-shadow: 0 0 0 0 rgba(34, 197, 94, .9);
        animation: shortPulse 1.6s ease-out infinite;
    }
    .short-brand {
        opacity: .8;
    }
    .short-scene {
        position: absolute;
        inset: 74px 20px 86px;
        display: grid;
        align-content: center;
        gap: 14px;
        opacity: 0;
        transform: translateY(18px) scale(.98);
        animation: shortScene 12s ease-in-out infinite;
        animation-delay: var(--scene-delay);
    }
    .short-scene-icon {
        width: 68px;
        height: 68px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 22px;
        color: #fff;
        background: rgba(255,255,255,.16);
        border: 1px solid rgba(255,255,255,.22);
        font-size: 32px;
        box-shadow: 0 16px 34px rgba(15,23,42,.22);
        backdrop-filter: blur(12px);
    }
    .short-scene h4 {
        color: #fff;
        font-size: 1.34rem;
        line-height: 1.08;
        font-weight: 950;
        margin: 0;
    }
    .short-scene p {
        color: rgba(255,255,255,.82);
        font-size: .92rem;
        line-height: 1.42;
        font-weight: 700;
        margin: 0;
    }
    .short-caption {
        position: absolute;
        left: 20px;
        right: 20px;
        bottom: 48px;
        z-index: 3;
        display: grid;
        gap: 8px;
    }
    .short-progress {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 6px;
    }
    .short-progress span {
        height: 4px;
        border-radius: 999px;
        background: rgba(255,255,255,.24);
        overflow: hidden;
    }
    .short-progress span::after {
        content: "";
        display: block;
        height: 100%;
        width: 0;
        border-radius: inherit;
        background: #fff;
        animation: shortBar 12s linear infinite;
        animation-delay: var(--bar-delay);
    }
    .short-caption small {
        color: rgba(255,255,255,.7);
        font-size: .72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
    }
    .tutorial-list {
        counter-reset: guide-counter;
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 10px;
    }
    .tutorial-list li {
        counter-increment: guide-counter;
        display: grid;
        grid-template-columns: 32px 1fr;
        gap: 10px;
        align-items: start;
        padding: 12px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #fff;
    }
    .tutorial-list li::before {
        content: counter(guide-counter);
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #2563eb;
        color: #fff;
        font-size: .78rem;
        font-weight: 900;
    }
    .tutorial-tip {
        border: 1px solid #bfdbfe;
        border-radius: 14px;
        background: #eff6ff;
        color: #1e3a8a;
        padding: 12px 14px;
        font-size: .88rem;
        font-weight: 700;
    }
    @keyframes heroGlow {
        from { transform: translate3d(0, 0, 0) scale(.92); opacity: .65; }
        to { transform: translate3d(42px, 32px, 0) scale(1.12); opacity: 1; }
    }
    @keyframes heroSweep {
        0%, 42% { transform: translateX(-110%); opacity: 0; }
        55% { opacity: 1; }
        78%, 100% { transform: translateX(110%); opacity: 0; }
    }
    @keyframes boardDrift {
        from { transform: translate3d(0, 0, 0); }
        to { transform: translate3d(48px, 48px, 0); }
    }
    @keyframes cardFloat {
        0%, 100% { translate: 0 0; }
        50% { translate: 0 -7px; }
    }
    @keyframes cardShine {
        0%, 46% { transform: translateX(-120%); opacity: 0; }
        58% { opacity: .72; }
        74%, 100% { transform: translateX(120%); opacity: 0; }
    }
    @keyframes connectorMove {
        from { background-position: 0 0; }
        to { background-position: 220% 0; }
    }
    @keyframes iconPulse {
        0%, 100% { transform: scale(1); box-shadow: 0 8px 18px rgba(15, 23, 42, .12); }
        50% { transform: scale(1.08); box-shadow: 0 14px 26px rgba(37, 99, 235, .22); }
    }
    @keyframes progressRun {
        0%, 100% { transform: translateX(-62%); }
        50% { transform: translateX(78%); }
    }
    @keyframes shortPulse {
        70% { box-shadow: 0 0 0 11px rgba(34, 197, 94, 0); }
        100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }
    @keyframes shortScene {
        0%, 7% { opacity: 0; transform: translateY(20px) scale(.98); }
        10%, 29% { opacity: 1; transform: translateY(0) scale(1); }
        34%, 100% { opacity: 0; transform: translateY(-18px) scale(.98); }
    }
    @keyframes shortBar {
        0%, 7% { width: 0; }
        10%, 29% { width: 100%; }
        34%, 100% { width: 100%; }
    }
    @media (max-width: 1199px) {
        .flow-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .flow-card:nth-child(even)::after { display: none; }
        .flow-card:nth-child(4)::after { display: none; }
    }
    @media (max-width: 575px) {
        .flow-grid { grid-template-columns: 1fr; }
        .flow-card::after { display: none; }
    }
    @media (prefers-reduced-motion: reduce) {
        .guide-hero::before,
        .guide-hero::after,
        .flow-board::before,
        .flow-card,
        .flow-card::before,
        .flow-card::after,
        .flow-icon,
        .flow-progress span {
            animation: none !important;
        }
        .short-live span,
        .short-scene,
        .short-progress span::after {
            animation: none !important;
        }
        .short-scene.is-first {
            opacity: 1;
            transform: none;
        }
        .flow-card:hover {
            transform: none;
        }
    }
</style>

<div class="guide-hero mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
        <div>
            <div class="small text-uppercase fw-bold text-white-50 mb-2">Panduan Role</div>
            <h3 class="fw-bold mb-2">Admin Jasa</h3>
            <p class="mb-0 text-white-50">Tata cara mengelola laporan jasa, membuat tagihan, mengurus surat pengantar, arsip dokumen, publish, dan monitoring pembayaran.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-start">
            <span class="guide-pill">Tagihan Jasa</span>
            <span class="guide-pill">Surat Pengantar</span>
            <span class="guide-pill">Arsip Dokumen</span>
        </div>
    </div>
</div>

<div class="flow-board mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div>
            <div class="small text-uppercase fw-bold text-primary mb-1">Gambar Alur</div>
            <h5 class="fw-bold mb-1">Alur Tagihan Admin Jasa</h5>
            <p class="text-muted small mb-0">Visual ini mengikuti proses di aplikasi dari sumber data sampai arsip dan laporan.</p>
        </div>
        <div class="flow-note small">
            <strong>Inti alur:</strong> tagihan tidak boleh publish sebelum workflow final dan Surat Final TTD tersedia.
        </div>
    </div>
    <div class="flow-grid">
        @foreach($flowSteps as $flow)
            <button type="button" class="flow-card" style="--delay: {{ $loop->index * 110 }}ms" data-bs-toggle="modal" data-bs-target="#flowGuideModal{{ $flow['no'] }}" aria-label="Buka panduan {{ $flow['title'] }}">
                <span class="flow-open"><i class="bi bi-play-circle me-1"></i>Panduan</span>
                <div class="flow-icon tone-{{ $flow['tone'] }}">
                    <i class="bi {{ $flow['icon'] }}"></i>
                </div>
                <div class="flow-no">Step {{ $flow['no'] }}</div>
                <div class="flow-title">{{ $flow['title'] }}</div>
                <p class="flow-desc">{{ $flow['desc'] }}</p>
                <div class="flow-mini">
                    @foreach($flow['ui'] as $ui)
                        <span>{{ $ui }}</span>
                    @endforeach
                </div>
                <div class="flow-progress"><span></span></div>
            </button>
        @endforeach
    </div>
</div>

@foreach($flowSteps as $flow)
    <div class="modal fade" id="flowGuideModal{{ $flow['no'] }}" tabindex="-1" aria-labelledby="flowGuideModalLabel{{ $flow['no'] }}" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header border-0 bg-primary text-white">
                    <div class="d-flex align-items-center gap-3">
                        <span class="flow-icon tone-{{ $flow['tone'] }} mb-0">
                            <i class="bi {{ $flow['icon'] }}"></i>
                        </span>
                        <div>
                            <div class="small fw-bold text-white-50 text-uppercase">Step {{ $flow['no'] }}</div>
                            <h5 class="modal-title fw-bold" id="flowGuideModalLabel{{ $flow['no'] }}">{{ $flow['title'] }}</h5>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="tutorial-video">
                                @if(!empty($flow['video']))
                                    @if(str_contains($flow['video'], 'youtube.com') || str_contains($flow['video'], 'youtu.be'))
                                        <iframe src="{{ $flow['video'] }}" title="Video panduan {{ $flow['title'] }}" allowfullscreen></iframe>
                                    @else
                                        <video controls preload="metadata">
                                            <source src="{{ $flow['video'] }}">
                                        </video>
                                    @endif
                                @else
                                    <div class="short-player" aria-label="Short tutorial {{ $flow['title'] }}">
                                        <div class="short-topbar">
                                            <span class="short-live"><span></span>Short</span>
                                            <span class="short-brand">Admin Jasa</span>
                                        </div>
                                        @foreach($flow['short'] as $scene)
                                            <div class="short-scene {{ $loop->first ? 'is-first' : '' }}" style="--scene-delay: {{ $loop->index * 4 }}s">
                                                <span class="short-scene-icon"><i class="bi {{ $scene['icon'] }}"></i></span>
                                                <h4>{{ $scene['title'] }}</h4>
                                                <p>{{ $scene['body'] }}</p>
                                            </div>
                                        @endforeach
                                        <div class="short-caption">
                                            <div class="short-progress">
                                                @foreach($flow['short'] as $scene)
                                                    <span style="--bar-delay: {{ $loop->index * 4 }}s"></span>
                                                @endforeach
                                            </div>
                                            <small>Durasi pendek - loop otomatis</small>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="small text-uppercase fw-bold text-primary mb-2">Tata Cara Penggunaan</div>
                            <ol class="tutorial-list">
                                @foreach($flow['guide'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ol>
                            <div class="tutorial-tip mt-3">
                                <i class="bi bi-lightbulb me-1"></i>{{ $flow['tips'] }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light border fw-bold" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

<div class="row g-4">
    <div class="col-lg-8">
        @foreach($steps as $step)
            <div class="card guide-card mb-3">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">{{ $step['title'] }}</h6>
                    <ul class="guide-list mb-0">
                        @foreach($step['items'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endforeach
    </div>
    <div class="col-lg-4">
        <div class="card guide-card mb-3">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-folder-check text-primary me-1"></i>Rekomendasi Arsip</h6>
                <p class="small text-muted mb-3">Untuk audit, dokumen tagihan sebaiknya tidak hanya tersimpan sebagai file final, tetapi juga punya metadata dan versi aktif.</p>
                <div class="small">
                    <div class="fw-bold">Lokasi file</div>
                    <code>storage/app/public/arsip-dokumen/TagihanJasa</code>
                    <hr>
                    <div class="fw-bold">Metadata</div>
                    <code>arsip_dokumen</code>
                    <hr>
                    <div class="fw-bold">Relasi</div>
                    <code>TagihanJasa -> arsipDokumen</code>
                </div>
            </div>
        </div>
        <div class="card guide-card">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-exclamation-circle text-warning me-1"></i>Catatan Penting</h6>
                <ul class="small mb-0">
                    <li>Jangan publish tagihan sebelum Surat Final TTD tersedia.</li>
                    <li>Jangan hapus file final lama jika ada generate ulang; biarkan menjadi riwayat arsip.</li>
                    <li>QR draft untuk keaslian dokumen, QR final untuk tanda tangan elektronik.</li>
                    <li>Nomor dan nilai tagihan yang sudah published sebaiknya tidak diubah tanpa prosedur revisi.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
