@extends('layouts.app')
@section('title', 'Dashboard Admin Jasa')

@section('content')
@php
    $rupiah = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $tanggal = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '-';
    $canCreateTagihanJasa = auth()->user()?->hasRole('Super Admin') === true
        || (auth()->user()?->hasAnyRole(['Admin Jasa', 'Admin Konsesi']) === true && ! auth()->user()?->hasRole('Super Admin Jasa'));
    $dueBadge = function ($tagihan) {
        return match ($tagihan->status_jatuh_tempo) {
            'LEWAT_JATUH_TEMPO' => ['Lewat Jatuh Tempo', 'bg-danger'],
            'JATUH_TEMPO_HARI_INI' => ['Jatuh Tempo', 'bg-dark'],
            'MENDEKATI_JATUH_TEMPO' => ['Mendekati Jatuh Tempo', 'bg-warning text-dark'],
            'NORMAL' => ['Normal', 'bg-success'],
            'LUNAS' => ['Lunas', 'bg-success'],
            default => ['Belum Diset', 'bg-secondary'],
        };
    };
@endphp

<style>
    /* Hero Card Header (mengikuti dashboard Mitra) */
    @keyframes ajHeroReveal {
        from { opacity: 0; transform: translateY(18px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes ajHeroSweep {
        0%        { transform: translateX(-120%) skewX(-18deg); opacity: 0; }
        20%       { opacity: .32; }
        55%, 100% { transform: translateX(220%) skewX(-18deg); opacity: 0; }
    }
    @keyframes ajContourDrift {
        0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg); opacity: .68; }
        50%      { transform: translate3d(-14px, 10px, 0) rotate(2deg); opacity: .95; }
    }
    .aj-hero-shell {
        position: relative;
        margin-bottom: 1.5rem;
        padding: 0;
        border-radius: 0 24px 24px 0;
        background: transparent;
        box-shadow: none;
    }
    .aj-hero-shell::before {
        content: "";
        position: absolute;
        inset: -2px;
        z-index: 0;
        border-radius: 0 26px 26px 0;
        border: 1px solid rgba(251, 191, 36, .36);
        pointer-events: none;
    }
    .aj-hero {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        background:
            radial-gradient(circle at 18% 30%, rgba(96, 165, 250, .28), transparent 28%),
            linear-gradient(110deg, #071421 0%, #0d2744 42%, #174f86 100%);
        border: 1px solid rgba(147, 197, 253, .28);
        border-radius: 0 24px 24px 0;
        color: #fff;
        padding: 28px 30px;
        box-shadow: 0 18px 50px rgba(18, 53, 92, .22);
        animation: ajHeroReveal .55s cubic-bezier(.2,.8,.2,1) both;
    }
    .aj-hero-shell .aj-hero { margin-bottom: 0; }
    .aj-hero h1, .aj-hero h2, .aj-hero h3, .aj-hero h4, .aj-hero p, .aj-hero label { color: #fff !important; }
    .aj-hero p { opacity: .86; }
    .aj-hero::before,
    .aj-hero::after {
        content: "";
        position: absolute;
        pointer-events: none;
        z-index: -1;
    }
    .aj-hero::before {
        width: 430px;
        height: 220px;
        right: -90px;
        top: -80px;
        border-radius: 0 0 0 999px;
        border-left: 2px solid rgba(251, 191, 36, .44);
        border-bottom: 2px solid rgba(147, 197, 253, .24);
        background: radial-gradient(circle at 50% 30%, rgba(96, 165, 250, .18), transparent 62%);
        animation: ajContourDrift 5.6s ease-in-out infinite;
    }
    .aj-hero::after {
        inset: 0;
        width: 46%;
        background: linear-gradient(90deg, transparent, rgba(125, 211, 252, .12), rgba(255, 255, 255, .20), rgba(96, 165, 250, .10), transparent);
        animation: ajHeroSweep 4.2s ease-in-out infinite;
    }
    .aj-hero-contour {
        position: absolute;
        right: 11%;
        bottom: -78px;
        z-index: -1;
        width: 360px;
        height: 210px;
        border-radius: 999px 999px 0 0;
        border-top: 2px solid rgba(251, 191, 36, .42);
        border-left: 2px solid rgba(147, 219, 254, .20);
        transform: rotate(-10deg);
        animation: ajContourDrift 6.4s ease-in-out infinite reverse;
    }
    .aj-hero-contour::before,
    .aj-hero-contour::after {
        content: "";
        position: absolute;
        border-radius: inherit;
        border-top: 1px solid rgba(191, 219, 254, .24);
    }
    .aj-hero-contour::before { inset: 26px 24px auto 20px; height: 138px; }
    .aj-hero-contour::after  { inset: 58px 52px auto 54px; height: 88px; border-color: rgba(251, 191, 36, .25); }
    .aj-hero-content { position: relative; z-index: 1; }
    .aj-hero-date {
        border: 1px solid rgba(191, 219, 254, .24);
        border-radius: 999px;
        background: rgba(15, 23, 42, .22);
        padding: 8px 14px;
        backdrop-filter: blur(8px);
    }
    @media (prefers-reduced-motion: reduce) {
        .aj-hero,
        .aj-hero::before,
        .aj-hero::after,
        .aj-hero-contour { animation: none !important; }
    }
    .aj-card { border: 0; border-radius: 14px; box-shadow: 0 10px 26px rgba(15,23,42,.08); }
    .aj-stat-label { color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .02em; }
    .aj-stat-value { color: #0f2f57; font-size: 24px; font-weight: 800; }
    .aj-table th { font-size: 12px; text-transform: uppercase; color: #64748b; white-space: nowrap; }
    .aj-table td { vertical-align: middle; }
    .aj-section-title { color: #0f2f57; font-weight: 800; }
    .aj-quick a { text-align: left; }
    
    /* Colored Cards */
    .card-terbit { background-color: #eff6ff; border-left: 4px solid #3b82f6; }
    .card-terbit .aj-stat-label, .card-terbit .aj-stat-value, .card-terbit i { color: #1d4ed8 !important; }
    .card-terbit .text-muted { color: #3b82f6 !important; font-weight: 600; }
    
    .card-lunas { background-color: #f0fdf4; border-left: 4px solid #22c55e; }
    .card-lunas .aj-stat-label, .card-lunas .aj-stat-value, .card-lunas i { color: #15803d !important; }
    .card-lunas .text-muted { color: #22c55e !important; font-weight: 600; }
    
    .card-jatuh-tempo { background-color: #fef2f2; border-left: 4px solid #ef4444; }
    .card-jatuh-tempo .aj-stat-label, .card-jatuh-tempo .aj-stat-value, .card-jatuh-tempo i { color: #b91c1c !important; }
    .card-jatuh-tempo .text-muted { color: #ef4444 !important; font-weight: 600; }
    
    .card-7hari { background-color: #fff7ed; border-left: 4px solid #f97316; }
    .card-7hari .aj-stat-label, .card-7hari .aj-stat-value, .card-7hari i { color: #c2410c !important; }
    .card-7hari .text-muted { color: #f97316 !important; font-weight: 600; }
    
    .card-layanan { background-color: #f5f3ff; border-left: 4px solid #8b5cf6; }
    .card-layanan .aj-stat-label, .card-layanan .aj-stat-value, .card-layanan i { color: #6d28d9 !important; }
    .card-layanan .text-muted { color: #8b5cf6 !important; font-weight: 600; }

    /* Soft Status Cards */
    .status-card-draft { background-color: #f8fafc; border: 1px solid #e2e8f0; }
    .status-card-draft .badge { background-color: #64748b !important; }
    
    .status-card-diajukan { background-color: #f0f9ff; border: 1px solid #bae6fd; }
    .status-card-diajukan .fw-semibold { color: #0284c7 !important; }
    .status-card-diajukan .badge { background-color: #0284c7 !important; }
    
    .status-card-menunggu { background-color: #fefce8; border: 1px solid #fef08a; }
    .status-card-menunggu .fw-semibold { color: #a16207 !important; }
    .status-card-menunggu .badge { background-color: #eab308 !important; color: white !important; }
    
    .status-card-diverifikasi { background-color: #eff6ff; border: 1px solid #bfdbfe; }
    .status-card-diverifikasi .fw-semibold { color: #1d4ed8 !important; }
    .status-card-diverifikasi .badge { background-color: #2563eb !important; }
    
    .status-card-ditolak { background-color: #fef2f2; border: 1px solid #fecaca; }
    .status-card-ditolak .fw-semibold { color: #b91c1c !important; }
    .status-card-ditolak .badge { background-color: #dc2626 !important; }
    
    .status-card-published { background-color: #f0fdfa; border: 1px solid #a7f3d0; }
    .status-card-published .fw-semibold { color: #0f766e !important; }
    .status-card-published .badge { background-color: #0d9488 !important; }
    
    .status-card-dibayar { background-color: #f0fdf4; border: 1px solid #bbf7d0; }
    .status-card-dibayar .fw-semibold { color: #15803d !important; }
    .status-card-dibayar .badge { background-color: #16a34a !important; }
    
    .status-card-batal { background-color: #f1f5f9; border: 1px solid #cbd5e1; }
    .status-card-batal .fw-semibold { color: #334155 !important; }
    .status-card-batal .badge { background-color: #475569 !important; }

    .aj-stat-label { font-size: 11px; }
    .aj-card { transition: transform 0.2s; }
    .aj-card:hover { transform: translateY(-3px); }

    @keyframes chartGlowDrift {
        0%, 100% { opacity: .45; transform: translate3d(-18px, 6px, 0) scale(1); }
        50% { opacity: .9; transform: translate3d(42px, -24px, 0) scale(1.12); }
    }

    @keyframes chartLightSweep {
        0% { opacity: 0; transform: translateX(-130%) skewX(-18deg); }
        24% { opacity: .42; }
        56%, 100% { opacity: 0; transform: translateX(230%) skewX(-18deg); }
    }

    .chart-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(37, 99, 235, .12);
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    }

    .chart-card::before,
    .chart-card::after {
        content: "";
        position: absolute;
        pointer-events: none;
    }

    .chart-card::before {
        width: 320px;
        height: 320px;
        right: -92px;
        top: -150px;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(59, 130, 246, .20), rgba(14, 165, 233, .10) 48%, transparent 70%);
        animation: chartGlowDrift 5.5s ease-in-out infinite;
    }

    .chart-card::after {
        inset: 0;
        width: 44%;
        background: linear-gradient(90deg, transparent, rgba(147, 197, 253, .22), rgba(255,255,255,.58), rgba(59, 130, 246, .12), transparent);
        animation: chartLightSweep 4.8s ease-in-out infinite;
    }

    .chart-card .card-body {
        position: relative;
        z-index: 1;
    }

    .chart-title {
        display: flex;
        align-items: center;
        gap: .65rem;
    }

    .chart-title-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        color: #fff;
        border-radius: 10px;
        background: linear-gradient(135deg, #12355c, #2563eb);
        box-shadow: 0 10px 24px rgba(37, 99, 235, .22);
    }

    .chart-title small {
        color: #64748b;
        font-weight: 600;
    }

    .chart-canvas-wrap {
        position: relative;
        min-height: 300px;
    }

    .chart-canvas-wrap canvas {
        position: relative;
        z-index: 1;
    }

    .chart-canvas-wrap.chart-compact {
        min-height: 260px;
    }

    .insight-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(37, 99, 235, .10);
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    }

    .insight-card::before {
        content: "";
        position: absolute;
        right: -120px;
        top: -130px;
        width: 280px;
        height: 280px;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(59,130,246,.16), transparent 68%);
        pointer-events: none;
    }

    .insight-card .card-header,
    .insight-card .card-body,
    .insight-card .table-responsive {
        position: relative;
        z-index: 1;
    }

    .insight-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 16px 18px 12px;
        border-bottom: 1px solid rgba(148, 163, 184, .18);
        background: linear-gradient(90deg, rgba(239, 246, 255, .96), rgba(255, 255, 255, .82));
    }

    .insight-title {
        display: flex;
        align-items: center;
        gap: .7rem;
    }

    .insight-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        border-radius: 11px;
        color: #fff;
        background: linear-gradient(135deg, #12355c, #2563eb);
        box-shadow: 0 12px 24px rgba(37, 99, 235, .20);
    }

    .insight-title h6 {
        margin: 0;
        color: #0f2f57;
        font-size: 15px;
        font-weight: 800;
    }

    .insight-title small {
        display: block;
        margin-top: 2px;
        color: #64748b;
        font-weight: 600;
    }

    .insight-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: 6px 10px;
        border-radius: 999px;
        color: #1d4ed8;
        background: #dbeafe;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .modern-table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .modern-table thead th {
        padding: 12px 16px;
        border-bottom: 1px solid rgba(148, 163, 184, .20);
        color: #64748b;
        background: rgba(248, 250, 252, .82);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .03em;
    }

    .modern-table tbody td {
        padding: 13px 16px;
        border-bottom: 1px solid rgba(226, 232, 240, .88);
        color: #475569;
    }

    .modern-table tbody tr {
        transition: background-color .2s ease, transform .2s ease;
    }

    .modern-table tbody tr:hover {
        background: rgba(239, 246, 255, .70);
    }

    .modern-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .invoice-code {
        color: #12355c;
        font-weight: 800;
        letter-spacing: .02em;
    }

    .status-soft {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: 6px 10px;
        border-radius: 999px;
        color: #0f766e;
        background: #ccfbf1;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .03em;
    }

    .status-soft::before {
        content: "";
        width: 7px;
        height: 7px;
        border-radius: 999px;
        background: #0f766e;
        box-shadow: 0 0 0 4px rgba(15, 118, 110, .12);
    }

    .detail-btn {
        border: 1px solid rgba(37, 99, 235, .22);
        color: #1d4ed8;
        background: #eff6ff;
        font-weight: 800;
        transition: .2s ease;
    }

    .detail-btn:hover {
        color: #fff;
        background: #2563eb;
        border-color: #2563eb;
        box-shadow: 0 10px 22px rgba(37, 99, 235, .20);
        transform: translateY(-1px);
    }

    .empty-state {
        min-height: 118px;
        color: #64748b;
    }

    .empty-state-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        margin-bottom: 8px;
        border-radius: 12px;
        color: #2563eb;
        background: #dbeafe;
    }

    .mitra-rank-item,
    .summary-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 12px 0;
        border-bottom: 1px solid rgba(226, 232, 240, .90);
    }

    .mitra-rank-item:last-child,
    .summary-row:last-child {
        border-bottom: 0;
    }

    .rank-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        flex: 0 0 28px;
        border-radius: 9px;
        color: #fff;
        background: linear-gradient(135deg, #1d4ed8, #38bdf8);
        font-size: 12px;
        font-weight: 900;
    }

    .summary-number {
        display: inline-flex;
        min-width: 34px;
        justify-content: center;
        padding: 5px 10px;
        border-radius: 999px;
        color: #1d4ed8;
        background: #dbeafe;
        font-weight: 900;
    }

    .summary-number.danger {
        color: #e11d48;
        background: #ffe4e6;
    }

    .text-slate {
        color: #334155;
    }
</style>

<div class="aj-hero-shell">
    <div class="aj-hero">
        <span class="aj-hero-contour"></span>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 aj-hero-content">
            <div>
                <div class="small text-white-50 fw-bold text-uppercase mb-1">Portal Admin Jasa</div>
                <h4 class="fw-bold mb-1 text-white">Dashboard Admin Jasa</h4>
                <p class="mb-0 small text-white-50">Monitoring tagihan layanan jasa, jatuh tempo, dan aktivitas mitra.</p>
            </div>
            <div class="d-flex flex-wrap align-items-end gap-2">
                <span class="aj-hero-date small text-white-50 d-none d-md-inline-flex align-items-center">
                    <i class="bi bi-calendar3 me-1"></i>
                    {{ now()->format('d/m/Y') }}
                </span>
                <form method="GET" class="row g-2 align-items-end mb-0">
                    <div class="col-auto">
                        <label class="form-label small mb-1 text-white-50">Bulan</label>
                        <select name="month" class="form-select form-select-sm">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ (int)($filters['month'] ?? now()->month) === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label small mb-1 text-white-50">Tahun</label>
                        <input type="number" name="year" value="{{ $filters['year'] ?? now()->year }}" class="form-control form-control-sm" style="width: 96px;">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-light btn-sm fw-bold">Terapkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card aj-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label class="form-label small fw-bold">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Mitra Jasa</label>
                <select name="mitra_jasa_id" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach($filterOptions['mitras'] as $mitra)
                        <option value="{{ $mitra->id }}" {{ (string)($filters['mitra_jasa_id'] ?? '') === (string)$mitra->id ? 'selected' : '' }}>{{ $mitra->nama_mitra }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Layanan</label>
                <select name="layanan_jasa_id" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach($filterOptions['layanans'] as $layanan)
                        <option value="{{ $layanan->id }}" {{ (string)($filters['layanan_jasa_id'] ?? '') === (string)$layanan->id ? 'selected' : '' }}>{{ $layanan->nama_layanan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Status Tagihan</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach(['DRAFT','VERIFIKASI_KOORDINATOR','VERIFIKASI_KABANDARA','DITOLAK','PUBLISHED','LUNAS','BATAL'] as $status)
                        <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ str_replace('_', ' ', $status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Status Pembayaran</label>
                <select name="status_pembayaran" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="belum_dibayar" {{ ($filters['status_pembayaran'] ?? '') === 'belum_dibayar' ? 'selected' : '' }}>Belum Dibayar</option>
                    <option value="sebagian" {{ ($filters['status_pembayaran'] ?? '') === 'sebagian' ? 'selected' : '' }}>Sebagian</option>
                    <option value="lunas" {{ ($filters['status_pembayaran'] ?? '') === 'lunas' ? 'selected' : '' }}>Lunas</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary btn-sm fw-bold">Filter Dashboard</button>
                <a href="{{ route('admin-jasa.dashboard') }}" class="btn btn-light border btn-sm fw-bold">Reset</a>
            </div>
        </form>
    </div>
</div>

@include('_partials.sa-stat-card-style')
<div class="row row-cols-1 row-cols-md-3 row-cols-xl-5 g-3 mb-4 sa-stat-row">
    @php
        $ajCardThemes = [
            ['#2563eb', '#eaf2ff', '#1e3a8a', 'rgba(37, 99, 235, .22)', 'rgba(37, 99, 235, .28)'],
            ['#16a34a', '#ecfdf3', '#14532d', 'rgba(22, 163, 74, .22)', 'rgba(22, 163, 74, .28)'],
            ['#ef4444', '#fef2f2', '#7f1d1d', 'rgba(239, 68, 68, .22)', 'rgba(239, 68, 68, .28)'],
            ['#f97316', '#fff7ed', '#7c2d12', 'rgba(249, 115, 22, .22)', 'rgba(249, 115, 22, .28)'],
            ['#7c3aed', '#f5f3ff', '#4c1d95', 'rgba(124, 58, 237, .22)', 'rgba(124, 58, 237, .28)'],
        ];
    @endphp
    @foreach([
        ['Total Tagihan Terbit', $summaryCards['published_count'], $rupiah($summaryCards['published_nominal']), 'bi-send-check'],
        ['Tagihan Lunas', $summaryCards['paid_count'], $rupiah($summaryCards['paid_nominal']), 'bi-patch-check'],
        ['Tagihan Jatuh Tempo', $summaryCards['overdue_count'], $rupiah($summaryCards['overdue_nominal']), 'bi-exclamation-triangle'],
        ['Jatuh Tempo 7 Hari', $summaryCards['due_soon_count'], 'Perlu dipantau', 'bi-calendar-event'],
        ['Item Layanan Dikelola', $layananSummary['total_item'], $layananSummary['total_jenis'] . ' jenis / ' . $layananSummary['total_kategori'] . ' kategori', 'bi-diagram-3'],
    ] as $i => $card)
        @php $t = $ajCardThemes[$i]; @endphp
        <div class="col d-flex align-items-stretch">
            <div class="card sa-stat-card border-0 shadow-sm h-100 w-100"
                 style="--accent: {{ $t[0] }}; --accent-bg: {{ $t[1] }}; --accent-glow: {{ $t[3] }}; --accent-soft: {{ $t[4] }};">
                <span class="stat-glow"></span>
                <span class="stat-shine"></span>
                <span class="stat-ribbon"></span>
                <div class="stat-accent"></div>
                <div class="card-body ps-4">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="small fw-bold stat-label">{{ $card[0] }}</div>
                            <div class="fs-3 fw-bold stat-value" style="color: {{ $t[2] }}">
                                {{ is_numeric($card[1]) ? number_format($card[1], 0, ',', '.') : $card[1] }}
                            </div>
                        </div>
                        <div class="stat-icon"><i class="bi {{ $card[3] }}"></i></div>
                    </div>
                    <div class="text-muted small mt-1" style="font-size:11px;">{{ $card[2] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    @foreach([
        ['Draft', $verificationSummary['draft'], 'status-card-draft'],
        ['Diajukan', $verificationSummary['diajukan'], 'status-card-diajukan'],
        ['Menunggu Verifikasi', $verificationSummary['menunggu_verifikasi'], 'status-card-menunggu'],
        ['Diverifikasi', $verificationSummary['diverifikasi'], 'status-card-diverifikasi'],
        ['Ditolak', $verificationSummary['ditolak'], 'status-card-ditolak'],
        ['Published', $verificationSummary['published'], 'status-card-published'],
        ['Dibayar', $verificationSummary['dibayar'], 'status-card-dibayar'],
        ['Batal', $verificationSummary['batal'], 'status-card-batal'],
    ] as $item)
        <div class="col-xl-3 col-md-4 col-6">
            <div class="card aj-card {{ $item[2] }} shadow-sm">
                <div class="card-body py-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-muted">{{ $item[0] }}</span>
                    <span class="badge px-3 py-2 rounded-pill">{{ number_format($item[1], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card aj-card chart-card h-100">
            <div class="card-body">
                <div class="chart-title mb-3">
                    <span class="chart-title-icon"><i class="bi bi-bar-chart-line"></i></span>
                    <div>
                        <h6 class="aj-section-title mb-0">Tagihan per Bulan</h6>
                        <small>Jumlah dan nominal tagihan sepanjang tahun</small>
                    </div>
                </div>
                <div class="chart-canvas-wrap">
                    <canvas id="chartMonthly"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card aj-card chart-card h-100">
            <div class="card-body">
                <div class="chart-title mb-3">
                    <span class="chart-title-icon"><i class="bi bi-pie-chart"></i></span>
                    <div>
                        <h6 class="aj-section-title mb-0">Status Tagihan</h6>
                        <small>Distribusi status tagihan jasa</small>
                    </div>
                </div>
                <div class="chart-canvas-wrap">
                    <canvas id="chartStatus"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============== Gauge + Kalender (style super admin jasa) ============== --}}
@include('_partials.sa-chart-style')
<div class="row g-4 mb-4 sa-chart-row">
    <div class="col-12">
        <div class="sa-chart-card h-100">
            <div class="sa-chart-body">
                <div class="sa-chart-head">
                    <div class="sa-chart-title">
                        <span class="sa-chart-icon" style="background: linear-gradient(135deg, #16a34a, #4ade80); box-shadow: 0 12px 24px rgba(22, 163, 74, .22);"><i class="bi bi-patch-check"></i></span>
                        <div>
                            <h6>Persentase Tagihan Lunas</h6>
                            <small>Berdasarkan filter aktif</small>
                        </div>
                    </div>
                </div>
                @php
                    $aj_circ = 339.292;
                    $aj_offset = $aj_circ - ($aj_circ * (($persentaseLunas ?? 0) / 100));
                @endphp
                <div class="sa-gauge" style="--gauge-circ: {{ $aj_circ }}; --gauge-target: {{ $aj_offset }};">
                    <svg viewBox="0 0 120 120">
                        <defs>
                            <linearGradient id="saGaugeGradient" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#fbbf24"/>
                                <stop offset="55%" stop-color="#16a34a"/>
                                <stop offset="100%" stop-color="#0ea5e9"/>
                            </linearGradient>
                        </defs>
                        <circle class="sa-gauge-track" cx="60" cy="60" r="54"></circle>
                        <circle class="sa-gauge-bar" cx="60" cy="60" r="54"></circle>
                    </svg>
                    <div class="sa-gauge-text">
                        <div class="val" id="ajGaugeVal">0%</div>
                        <div class="lbl">Lunas</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-12">
        <div class="card aj-card insight-card h-100">
            <div class="insight-header">
                <div class="insight-title">
                    <span class="insight-icon"><i class="bi bi-receipt-cutoff"></i></span>
                    <div>
                        <h6>Tagihan Terbaru</h6>
                        <small>Daftar tagihan terbaru yang sudah masuk sistem</small>
                    </div>
                </div>
                <span class="insight-pill"><i class="bi bi-lightning-charge-fill"></i> Update terbaru</span>
            </div>
            <div class="table-responsive">
                <table class="table aj-table modern-table mb-0">
                    <thead><tr><th>No</th><th>No Tagihan</th><th>Mitra</th><th>Tanggal</th><th>Jatuh Tempo</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @forelse($latestTagihan as $tagihan)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="invoice-code">{{ $tagihan->nomor_tagihan }}</td>
                            <td>{{ $tagihan->mitra->nama_mitra ?? '-' }}</td>
                            <td>{{ $tanggal($tagihan->tanggal_tagihan) }}</td>
                            <td>{{ $tanggal($tagihan->tanggal_jatuh_tempo) }}</td>
                            <td class="fw-bold text-primary">{{ $rupiah($tagihan->total_tagihan) }}</td>
                            <td><span class="status-soft">{{ str_replace('_', ' ', $tagihan->status) }}</span></td>
                            <td><a href="{{ route('tagihan-jasa.show', $tagihan->id) }}" class="btn btn-sm detail-btn">Detail</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center empty-state py-4">
                                <span class="empty-state-icon"><i class="bi bi-inbox"></i></span>
                                <div class="fw-bold">Belum ada tagihan terbaru</div>
                                <div class="small">Tagihan yang baru dibuat akan tampil di sini.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card aj-card insight-card h-100">
            <div class="insight-header">
                <div class="insight-title">
                    <span class="insight-icon"><i class="bi bi-calendar-x"></i></span>
                    <div>
                        <h6>Tagihan Lewat Jatuh Tempo</h6>
                        <small>Tagihan yang perlu segera ditindaklanjuti</small>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table aj-table modern-table mb-0">
                    <thead><tr><th>No</th><th>No Tagihan</th><th>Mitra</th><th>Jatuh Tempo</th><th>Terlambat</th><th>Total</th><th>Kontak</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @forelse($overdueTagihan as $tagihan)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="invoice-code">{{ $tagihan->nomor_tagihan }}</td>
                            <td>{{ $tagihan->mitra->nama_mitra ?? '-' }}</td>
                            <td>{{ $tanggal($tagihan->tanggal_jatuh_tempo) }}</td>
                            <td><span class="badge bg-danger">{{ $tagihan->hari_terlambat }} hari</span></td>
                            <td class="fw-bold text-danger">{{ $rupiah($tagihan->sisa_tagihan ?: $tagihan->total_tagihan) }}</td>
                            <td>{{ $tagihan->mitra->no_telepon ?? '-' }}</td>
                            <td><a href="{{ route('tagihan-jasa.show', $tagihan->id) }}" class="btn btn-sm detail-btn">Detail</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center empty-state py-4">
                                <span class="empty-state-icon"><i class="bi bi-shield-check"></i></span>
                                <div class="fw-bold">Tidak ada tagihan lewat jatuh tempo</div>
                                <div class="small">Semua tagihan masih dalam batas pemantauan.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-3">
        <div class="card aj-card insight-card h-100">
            <div class="insight-header">
                <div class="insight-title">
                    <span class="insight-icon"><i class="bi bi-trophy"></i></span>
                    <div>
                        <h6>Top Mitra Jasa</h6>
                        <small>Nominal terbesar</small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @forelse($mitraSummary['top_nominal'] as $row)
                    <div class="mitra-rank-item">
                        <div class="d-flex align-items-center gap-2">
                            <span class="rank-badge">{{ $loop->iteration }}</span>
                            <span class="fw-bold text-slate">{{ $row->nama_mitra }}</span>
                        </div>
                        <strong class="text-primary">{{ $rupiah($row->nominal) }}</strong>
                    </div>
                @empty
                    <div class="text-center empty-state py-4">
                        <span class="empty-state-icon"><i class="bi bi-bar-chart"></i></span>
                        <div class="fw-bold">Belum ada data mitra</div>
                        <div class="small">Data nominal mitra akan muncul setelah tagihan tersedia.</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-xl-3">
        <div class="card aj-card insight-card h-100 mb-4">
            <div class="insight-header">
                <div class="insight-title">
                    <span class="insight-icon"><i class="bi bi-people"></i></span>
                    <div>
                        <h6>Ringkasan Mitra</h6>
                        <small>Scope mitra jasa</small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="summary-row">
                    <span class="fw-semibold text-muted">Total Terkait</span>
                    <strong class="summary-number">{{ number_format($mitraSummary['total'], 0, ',', '.') }}</strong>
                </div>
                <div class="summary-row">
                    <span class="fw-semibold text-muted">Mitra Aktif</span>
                    <strong class="summary-number">{{ number_format($mitraSummary['aktif'], 0, ',', '.') }}</strong>
                </div>
                <div class="summary-row">
                    <span class="fw-semibold text-muted">Jatuh Tempo</span>
                    <strong class="summary-number danger">{{ number_format($mitraSummary['jatuh_tempo'], 0, ',', '.') }}</strong>
                </div>
            </div>
        </div>
        <div class="card aj-card h-100 aj-quick">
            <div class="card-body d-grid gap-2">
                <h6 class="aj-section-title mb-2">Tugas Cepat</h6>
                @if($canCreateTagihanJasa)
                    <a href="{{ route('tagihan-jasa.create') }}" class="btn btn-primary fw-bold">Buat Tagihan</a>
                    <a href="{{ route('tagihan-jasa.create', ['mode' => 'konsesi']) }}" class="btn btn-outline-primary fw-bold">Atur Layanan Konsesi</a>
                @endif
                <a href="{{ route('admin-jasa.tagihan.log-bulanan') }}" class="btn btn-light border fw-bold">Log Tagihan Bulanan</a>
                <a href="{{ route('admin-jasa.tagihan.jatuh-tempo') }}" class="btn btn-light border fw-bold">Tagihan Jatuh Tempo</a>
                <a href="{{ route('admin-jasa.mitra') }}" class="btn btn-light border fw-bold">Lihat Mitra Jasa</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-6">
        <div class="card aj-card chart-card">
            <div class="card-body">
                <div class="chart-title mb-3">
                    <span class="chart-title-icon"><i class="bi bi-graph-up-arrow"></i></span>
                    <div>
                        <h6 class="aj-section-title mb-0">Top Layanan Berdasarkan Nominal</h6>
                        <small>Layanan dengan kontribusi tagihan terbesar</small>
                    </div>
                </div>
                <div class="chart-canvas-wrap chart-compact">
                    <canvas id="chartTopLayanan"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card aj-card chart-card">
            <div class="card-body">
                <div class="chart-title mb-3">
                    <span class="chart-title-icon"><i class="bi bi-building-check"></i></span>
                    <div>
                        <h6 class="aj-section-title mb-0">Top Mitra Berdasarkan Nominal</h6>
                        <small>Mitra dengan nominal tagihan tertinggi</small>
                    </div>
                </div>
                <div class="chart-canvas-wrap chart-compact">
                    <canvas id="chartTopMitra"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card aj-card mt-4">
    <div class="card-header bg-white border-0"><h6 class="aj-section-title mb-0">Notifikasi Terbaru</h6></div>
    <div class="table-responsive">
        <table class="table aj-table mb-0">
            <thead><tr><th>Waktu</th><th>No Tagihan</th><th>Mitra</th><th>Channel</th><th>Recipient</th><th>Status</th><th>Error</th></tr></thead>
            <tbody>
                @forelse($latestNotifications as $notification)
                    <tr>
                        <td>{{ $notification->created_at ?? '-' }}</td>
                        <td>{{ $notification->nomor_tagihan ?? '-' }}</td>
                        <td>{{ $notification->nama_mitra ?? '-' }}</td>
                        <td>{{ $notification->channel ?? '-' }}</td>
                        <td>{{ $notification->recipient ?? '-' }}</td>
                        <td>{{ $notification->status ?? '-' }}</td>
                        <td>{{ $notification->error_message ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada log notifikasi khusus untuk tagihan jasa.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('script')
<script src="{{ URL::asset('build/plugins/chartjs/js/chart.js') }}"></script>
<script>
    const monthly = @json($chartTagihanBulanan);
    const statusChart = @json($chartTagihanByStatus);
    const topMitra = @json($chartTopMitra);
    const topLayanan = @json($chartTopLayanan);
    const moneyTick = value => new Intl.NumberFormat('id-ID').format(value);

    const chartGradient = (context, start, end) => {
        const chart = context.chart;
        const area = chart.chartArea;

        if (!area) {
            return start;
        }

        const gradient = chart.ctx.createLinearGradient(0, area.bottom, 0, area.top);
        gradient.addColorStop(0, end);
        gradient.addColorStop(1, start);

        return gradient;
    };

    const chartGrid = {
        color: 'rgba(148, 163, 184, .18)',
        borderDash: [4, 4],
        drawBorder: false,
    };

    const baseChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 1400,
            easing: 'easeOutQuart',
            delay: context => context.type === 'data' ? (context.dataIndex * 75) + (context.datasetIndex * 120) : 0,
        },
        interaction: {
            intersect: false,
            mode: 'index',
        },
        plugins: {
            legend: {
                labels: {
                    usePointStyle: true,
                    pointStyle: 'rectRounded',
                    boxWidth: 10,
                    boxHeight: 10,
                    color: '#475569',
                    font: { weight: '700' },
                },
            },
            tooltip: {
                backgroundColor: '#0f172a',
                borderColor: 'rgba(147, 197, 253, .35)',
                borderWidth: 1,
                padding: 12,
                titleColor: '#eff6ff',
                bodyColor: '#dbeafe',
                displayColors: true,
            },
        },
    };

    new Chart(document.getElementById('chartMonthly'), {
        type: 'bar',
        data: {
            labels: monthly.map(i => i.label),
            datasets: [
                {
                    label: 'Jumlah Tagihan',
                    data: monthly.map(i => i.count),
                    backgroundColor: context => chartGradient(context, '#2563eb', '#93c5fd'),
                    borderColor: 'rgba(37, 99, 235, .55)',
                    borderWidth: 1,
                    borderRadius: 10,
                    borderSkipped: false,
                    hoverBackgroundColor: '#1d4ed8',
                },
                {
                    label: 'Nominal',
                    data: monthly.map(i => i.nominal),
                    backgroundColor: context => chartGradient(context, '#0f766e', '#5eead4'),
                    borderColor: 'rgba(15, 118, 110, .5)',
                    borderWidth: 1,
                    borderRadius: 10,
                    borderSkipped: false,
                    hoverBackgroundColor: '#0f766e',
                    yAxisID: 'y1',
                },
            ],
        },
        options: {
            ...baseChartOptions,
            scales: {
                x: { grid: { display: false }, ticks: { color: '#64748b', font: { weight: '700' } } },
                y: { beginAtZero: true, grid: chartGrid, ticks: { color: '#64748b' } },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    ticks: { callback: moneyTick, color: '#64748b' },
                    grid: { drawOnChartArea: false, drawBorder: false },
                },
            },
        },
    });

    new Chart(document.getElementById('chartStatus'), {
        type: 'doughnut',
        data: {
            labels: statusChart.labels,
            datasets: [{
                data: statusChart.data,
                backgroundColor: ['#12355c', '#2563eb', '#f59e0b', '#0f766e', '#ef4444', '#38bdf8'],
                borderColor: '#ffffff',
                borderWidth: 4,
                hoverOffset: 14,
                spacing: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '52%',
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 1600,
                easing: 'easeOutQuart',
            },
            plugins: {
                ...baseChartOptions.plugins,
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 9,
                        boxHeight: 9,
                        color: '#475569',
                        font: { weight: '700' },
                    },
                },
            },
        },
    });

    new Chart(document.getElementById('chartTopLayanan'), {
        type: 'bar',
        data: {
            labels: topLayanan.labels,
            datasets: [{
                label: 'Nominal',
                data: topLayanan.data,
                backgroundColor: context => chartGradient(context, '#12355c', '#60a5fa'),
                borderColor: 'rgba(18, 53, 92, .45)',
                borderWidth: 1,
                borderRadius: 10,
                borderSkipped: false,
                hoverBackgroundColor: '#1d4ed8',
            }],
        },
        options: {
            ...baseChartOptions,
            indexAxis: 'y',
            scales: {
                x: { beginAtZero: true, grid: chartGrid, ticks: { callback: moneyTick, color: '#64748b' } },
                y: { grid: { display: false }, ticks: { color: '#475569', font: { weight: '700' } } },
            },
        },
    });

    new Chart(document.getElementById('chartTopMitra'), {
        type: 'bar',
        data: {
            labels: topMitra.labels,
            datasets: [{
                label: 'Nominal',
                data: topMitra.data,
                backgroundColor: context => chartGradient(context, '#165d9f', '#7dd3fc'),
                borderColor: 'rgba(22, 93, 159, .45)',
                borderWidth: 1,
                borderRadius: 10,
                borderSkipped: false,
                hoverBackgroundColor: '#0f2f57',
            }],
        },
        options: {
            ...baseChartOptions,
            indexAxis: 'y',
            scales: {
                x: { beginAtZero: true, grid: chartGrid, ticks: { callback: moneyTick, color: '#64748b' } },
                y: { grid: { display: false }, ticks: { color: '#475569', font: { weight: '700' } } },
            },
        },
    });

    // ====== Gauge counter (admin jasa) ======
    (function () {
        const gaugeEl = document.getElementById('ajGaugeVal');
        if (!gaugeEl) return;
        const target = {{ $persentaseLunas ?? 0 }};
        const duration = 1400;
        const start = performance.now();
        function step(now) {
            const t = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - t, 3);
            gaugeEl.textContent = (target * eased).toFixed(target % 1 === 0 ? 0 : 1) + '%';
            if (t < 1) requestAnimationFrame(step);
            else gaugeEl.textContent = target + '%';
        }
        requestAnimationFrame(step);
    })();
</script>
@endpush
