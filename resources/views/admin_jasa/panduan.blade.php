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
        ],
        [
            'no' => '02',
            'icon' => 'bi-receipt',
            'title' => 'Buat Tagihan',
            'desc' => 'Pilih mitra, layanan, volume, tarif, dan dokumen dasar.',
            'tone' => 'cyan',
            'ui' => ['Form Tagihan', 'Rincian Layanan', 'Simpan Draft'],
        ],
        [
            'no' => '03',
            'icon' => 'bi-envelope-paper',
            'title' => 'Draft Surat',
            'desc' => 'Sistem membuat surat pengantar dengan QR keaslian dokumen.',
            'tone' => 'indigo',
            'ui' => ['Surat Pengantar', 'QR Validasi', 'Preview PDF'],
        ],
        [
            'no' => '04',
            'icon' => 'bi-diagram-3',
            'title' => 'Workflow',
            'desc' => 'Tagihan masuk verifikasi. Jika revisi, Admin Jasa edit ulang.',
            'tone' => 'amber',
            'ui' => ['Koordinator', 'Kasubag', 'Kaban'],
        ],
        [
            'no' => '05',
            'icon' => 'bi-patch-check',
            'title' => 'Final TTD',
            'desc' => 'Setelah disetujui final, generate surat final bertanda tangan elektronik.',
            'tone' => 'green',
            'ui' => ['Generate Final', 'TTD KPA/PLH', 'Hash Cocok'],
        ],
        [
            'no' => '06',
            'icon' => 'bi-send',
            'title' => 'Publish',
            'desc' => 'Sistem membuat VA dan mengirim notifikasi WhatsApp ke mitra.',
            'tone' => 'teal',
            'ui' => ['Publish', 'Virtual Account', 'WhatsApp'],
        ],
        [
            'no' => '07',
            'icon' => 'bi-credit-card',
            'title' => 'Pembayaran',
            'desc' => 'Pembayaran masuk dari VA atau ditandai lunas manual sesuai kewenangan.',
            'tone' => 'purple',
            'ui' => ['VA Callback', 'Lunas Manual', 'Denda Jatuh Tempo'],
        ],
        [
            'no' => '08',
            'icon' => 'bi-archive',
            'title' => 'Arsip & Laporan',
            'desc' => 'Draft, final TTD, status, dan pembayaran tersimpan untuk audit.',
            'tone' => 'slate',
            'ui' => ['Arsip Aktif', 'Riwayat Versi', 'Rekap Bulanan'],
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
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 16px;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, .94) 0%, rgba(248, 250, 252, .96) 100%);
        box-shadow: 0 14px 28px rgba(15, 23, 42, .06);
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
            <div class="flow-card" style="--delay: {{ $loop->index * 110 }}ms">
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
            </div>
        @endforeach
    </div>
</div>

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
