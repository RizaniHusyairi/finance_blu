@extends('layouts.app')

@section('title', 'Dashboard - Pejabat Pengadaan')

@push('css')
<style>
    body[data-bs-theme="blue-theme"] .main-content { background: #f6f7fb; }

    /* ============ Welcome banner ============ */
    .welcome-banner {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%);
        border-radius: 1.25rem;
        padding: 1.75rem 2rem;
        color: #fff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 14px 32px rgba(79, 70, 229, .25);
        animation: fadeSlideDown .55s cubic-bezier(.22,1,.36,1) both;
    }
    .welcome-banner,
    .welcome-banner h3,
    .welcome-banner p,
    .welcome-banner span,
    .welcome-banner strong { color: #fff !important; }
    .welcome-banner h3 strong { color: #fde047 !important; }
    .welcome-banner::before {
        content: '';
        position: absolute;
        right: -80px; top: -80px;
        width: 280px; height: 280px;
        border-radius: 50%;
        background: rgba(255,255,255,.10);
    }
    .welcome-banner::after {
        content: '';
        position: absolute;
        right: 60px; bottom: -60px;
        width: 180px; height: 180px;
        background: rgba(255,255,255,.07);
        border-radius: 50%;
    }
    .welcome-banner > * { position: relative; z-index: 1; }
    .welcome-banner h3 {
        font-weight: 800;
        margin: 0 0 .35rem;
        letter-spacing: -.01em;
        text-shadow: 0 1px 2px rgba(0,0,0,.15);
    }
    .welcome-banner p { margin: 0; opacity: 1; }
    .welcome-banner .badge-action {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        background: rgba(255,255,255,.18);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.25);
        padding: .55rem 1rem;
        border-radius: 999px;
        font-size: .82rem;
        font-weight: 600;
        margin-top: 1rem;
    }
    .welcome-banner .badge-action .dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #fde047;
        box-shadow: 0 0 0 4px rgba(253,224,71,.35);
        animation: pulse 1.8s infinite;
    }
    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(253,224,71,.55); }
        50%      { box-shadow: 0 0 0 8px rgba(253,224,71,0); }
    }
    .welcome-illustration {
        position: absolute;
        right: 1.25rem; top: 50%;
        transform: translateY(-50%);
        font-size: 6rem;
        opacity: .15;
    }

    /* ============ Section heading ============ */
    .section-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 1.75rem 0 1rem;
    }
    .section-heading h6 {
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #475569;
        margin: 0;
        display: inline-flex;
        align-items: center;
        gap: .6rem;
    }
    .section-heading h6::before {
        content: '';
        width: 4px; height: 18px;
        border-radius: 4px;
        background: linear-gradient(180deg, #6366f1, #2563eb);
    }

    /* ============ KPI Card ============ */
    .kpi-card {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        padding: 1.25rem;
        height: 100%;
        transition: transform .18s ease, box-shadow .18s ease;
        position: relative;
        overflow: hidden;
        animation: fadeSlideUp .55s cubic-bezier(.22,1,.36,1) both;
    }
    .kpi-card:nth-child(1) { animation-delay: .12s; }
    .kpi-card:nth-child(2) { animation-delay: .19s; }
    .kpi-card:nth-child(3) { animation-delay: .26s; }
    .kpi-card:nth-child(4) { animation-delay: .33s; }
    .kpi-card::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: var(--kpi-accent, linear-gradient(90deg, #6366f1, #2563eb));
    }
    .kpi-card::after {
        content: '';
        position: absolute;
        right: -50px; top: -50px;
        width: 140px; height: 140px;
        border-radius: 50%;
        background: var(--kpi-glow, radial-gradient(circle, rgba(99,102,241,.10), transparent 70%));
        z-index: 0;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px rgba(15,23,42,.08);
    }
    .kpi-card > * { position: relative; z-index: 1; }
    .kpi-card .kpi-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
        color: #fff;
        background: var(--kpi-icon-bg, linear-gradient(135deg, #6366f1, #2563eb));
        box-shadow: 0 6px 16px var(--kpi-icon-shadow, rgba(99,102,241,.30));
        transition: transform .3s ease;
    }
    .kpi-card:hover .kpi-icon { transform: rotate(-6deg) scale(1.06); }
    .kpi-card .kpi-label {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .05em;
        color: #64748b;
        text-transform: uppercase;
        margin: .85rem 0 .15rem;
    }
    .kpi-card .kpi-value {
        font-size: 1.85rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
        margin: 0;
        font-variant-numeric: tabular-nums;
    }
    .kpi-card .kpi-foot {
        font-size: .78rem;
        color: #6b7280;
        margin-top: .65rem;
        display: flex;
        flex-wrap: wrap;
        gap: .35rem;
        align-items: center;
    }
    .kpi-card .kpi-pill {
        display: inline-flex;
        align-items: center;
        font-size: .7rem;
        font-weight: 600;
        padding: .15rem .65rem;
        border-radius: 999px;
        white-space: nowrap;
    }
    .kpi-warning {
        --kpi-accent: linear-gradient(90deg, #f59e0b, #f97316);
        --kpi-glow:   radial-gradient(circle, rgba(245,158,11,.18), transparent 70%);
        --kpi-icon-bg: linear-gradient(135deg, #fbbf24, #f97316);
        --kpi-icon-shadow: rgba(245,158,11,.35);
    }
    .kpi-info {
        --kpi-accent: linear-gradient(90deg, #06b6d4, #3b82f6);
        --kpi-glow:   radial-gradient(circle, rgba(59,130,246,.18), transparent 70%);
        --kpi-icon-bg: linear-gradient(135deg, #38bdf8, #3b82f6);
        --kpi-icon-shadow: rgba(59,130,246,.35);
    }
    .kpi-primary {
        --kpi-accent: linear-gradient(90deg, #8b5cf6, #6366f1);
        --kpi-glow:   radial-gradient(circle, rgba(99,102,241,.18), transparent 70%);
        --kpi-icon-bg: linear-gradient(135deg, #a78bfa, #6366f1);
        --kpi-icon-shadow: rgba(99,102,241,.35);
    }
    .kpi-danger {
        --kpi-accent: linear-gradient(90deg, #f43f5e, #ef4444);
        --kpi-glow:   radial-gradient(circle, rgba(239,68,68,.18), transparent 70%);
        --kpi-icon-bg: linear-gradient(135deg, #fb7185, #ef4444);
        --kpi-icon-shadow: rgba(239,68,68,.35);
    }
    .kpi-success {
        --kpi-accent: linear-gradient(90deg, #10b981, #14b8a6);
        --kpi-glow:   radial-gradient(circle, rgba(16,185,129,.18), transparent 70%);
        --kpi-icon-bg: linear-gradient(135deg, #34d399, #10b981);
        --kpi-icon-shadow: rgba(16,185,129,.35);
    }
    .tint-warning  { background: rgba(245, 158, 11, .14); color: #b45309; }
    .tint-info     { background: rgba(59, 130, 246, .14); color: #1d4ed8; }
    .tint-danger   { background: rgba(239, 68, 68,  .14); color: #b91c1c; }
    .tint-success  { background: rgba(16, 185, 129, .14); color: #047857; }
    .tint-primary  { background: rgba(99, 102, 241, .14); color: #4338ca; }

    /* ============ Panel ============ */
    .panel {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        position: relative;
        overflow: hidden;
        animation: fadeSlideUp .55s cubic-bezier(.22,1,.36,1) both;
    }
    .panel.h-fill { height: 100%; }
    .panel-head {
        padding: 1.1rem 1.25rem .9rem;
        border-bottom: 1px solid #f1f3f7;
        display: flex; align-items: center; justify-content: space-between;
        background: linear-gradient(180deg, #fafbff 0%, #ffffff 100%);
        gap: 1rem;
        flex-wrap: wrap;
    }
    .panel-head h6 {
        margin: 0;
        font-size: .95rem;
        font-weight: 700;
        color: #0f172a;
        display: inline-flex;
        align-items: center;
        gap: .55rem;
    }
    .panel-head h6 i {
        width: 32px; height: 32px;
        border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(99,102,241,.12);
        color: #4f46e5;
        font-size: 1rem;
    }
    .panel-head h6 i.ph-warning  { background: rgba(245,158,11,.15); color: #b45309; }
    .panel-head h6 i.ph-info     { background: rgba(59,130,246,.15); color: #1d4ed8; }
    .panel-head h6 i.ph-primary  { background: rgba(99,102,241,.15); color: #4338ca; }
    .panel-head h6 i.ph-danger   { background: rgba(239,68,68,.15);  color: #b91c1c; }
    .panel-head h6 i.ph-success  { background: rgba(16,185,129,.15); color: #047857; }
    .panel-body { padding: 1rem 1.25rem; }
    .panel-foot {
        padding: .75rem 1.25rem;
        border-top: 1px solid #f1f3f7;
        font-size: .8rem; color: #64748b;
        background: #fafbff;
    }

    /* ============ Quick Action ============ */
    .quick-action {
        display: flex;
        align-items: center;
        gap: .85rem;
        padding: .9rem 1rem;
        border-radius: .75rem;
        background: #fff;
        border: 1px solid #eef0f4;
        text-decoration: none;
        color: #1e293b;
        transition: all .18s ease;
    }
    .quick-action:hover {
        border-color: #6366f1;
        background: #fafbff;
        transform: translateX(4px);
        color: #4f46e5;
    }
    .quick-action .qa-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .quick-action .qa-title {
        font-weight: 600;
        font-size: .88rem;
        margin: 0;
    }
    .quick-action .qa-sub {
        font-size: .73rem;
        color: #94a3b8;
        margin: 0;
    }
    .quick-action .qa-arrow {
        margin-left: auto;
        color: #cbd5e1;
        transition: all .18s ease;
    }
    .quick-action:hover .qa-arrow {
        color: #6366f1;
        transform: translateX(2px);
    }

    /* ============ Modern Table ============ */
    .table-modern { width: 100%; border-collapse: separate; border-spacing: 0; }
    .table-modern thead th {
        background: #f8fafc;
        font-size: .7rem; font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #64748b;
        padding: .75rem 1rem;
        border-top: 1px solid #eef0f4;
        border-bottom: 1px solid #eef0f4;
        white-space: nowrap;
    }
    .table-modern thead th:first-child { padding-left: 1.25rem; }
    .table-modern thead th:last-child  { padding-right: 1.25rem; }
    .table-modern tbody td {
        padding: .85rem 1rem;
        font-size: .85rem;
        border-bottom: 1px solid #f1f3f7;
        background: #fff;
        vertical-align: middle;
        transition: background .15s ease;
    }
    .table-modern tbody td:first-child { padding-left: 1.25rem; }
    .table-modern tbody td:last-child  { padding-right: 1.25rem; }
    .table-modern tbody tr:hover td { background: #fafbff; }
    .table-modern tbody tr:last-child td { border-bottom: 0; }

    .doc-no { font-weight: 700; color: #1e293b; font-size: .87rem; }
    .doc-desc { font-size: .75rem; color: #64748b; margin-top: .15rem; }
    .money-pos { color: #047857; font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .money     { color: #1e293b; font-weight: 600; font-variant-numeric: tabular-nums; white-space: nowrap; }

    .pill {
        display: inline-flex; align-items: center; gap: .35rem;
        font-size: .7rem; font-weight: 700;
        padding: .25rem .65rem;
        border-radius: 999px;
    }
    .pill::before {
        content: '';
        width: 6px; height: 6px;
        border-radius: 50%;
        background: currentColor;
        box-shadow: 0 0 0 3px currentColor;
        opacity: .25;
    }
    .pill-aktif    { background: rgba(16,185,129,.12); color: #047857; }
    .pill-pending  { background: rgba(245,158,11,.12); color: #b45309; }
    .pill-late     { background: rgba(239,68,68,.10); color: #b91c1c; }
    .pill-soon     { background: rgba(245,158,11,.12); color: #b45309; }
    .pill-draft    { background: rgba(100,116,139,.10); color: #475569; }
    .pill-revisi   { background: rgba(239,68,68,.10); color: #b91c1c; }
    .pill-info     { background: rgba(59,130,246,.12); color: #1d4ed8; }

    /* ============ Vendor Avatar ============ */
    .vendor-avatar {
        width: 36px; height: 36px;
        border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #818cf8, #6366f1);
        color: #fff;
        font-weight: 700;
        font-size: .78rem;
        flex-shrink: 0;
    }
    .vendor-avatar.va-2 { background: linear-gradient(135deg, #fda4af, #f43f5e); }
    .vendor-avatar.va-3 { background: linear-gradient(135deg, #6ee7b7, #10b981); }
    .vendor-avatar.va-4 { background: linear-gradient(135deg, #fcd34d, #f59e0b); }
    .vendor-avatar.va-5 { background: linear-gradient(135deg, #93c5fd, #3b82f6); }

    /* ============ Action button ============ */
    .btn-act {
        display: inline-flex; align-items: center; gap: .35rem;
        font-size: .76rem; font-weight: 600;
        padding: .35rem .85rem;
        border-radius: .55rem;
        border: 1px solid transparent;
        text-decoration: none;
        transition: all .15s ease;
    }
    .btn-act-primary {
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: #fff;
        box-shadow: 0 4px 10px rgba(99,102,241,.30);
    }
    .btn-act-primary:hover {
        color: #fff; transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(99,102,241,.40);
    }
    .btn-act-soft {
        background: rgba(99,102,241,.10);
        color: #4338ca;
        border-color: rgba(99,102,241,.18);
    }
    .btn-act-soft:hover {
        background: #6366f1; color: #fff; border-color: #6366f1;
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 2rem 1rem;
        color: #94a3b8;
    }
    .empty-state i {
        font-size: 2.2rem;
        margin-bottom: .55rem;
        display: block;
        background: linear-gradient(135deg, #c7d2fe, #818cf8);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Animations */
    @keyframes fadeSlideDown {
        from { opacity: 0; transform: translateY(-12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeSlideUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush


@section('content')

@php
    $hour = (int) date('H');
    $greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 18 ? 'Selamat Sore' : 'Selamat Malam'));
    $totalPerlu = $kpi['kontrak_pending'] + $kpi['kontrak_draft'] + $kpi['tagihan_revisi'];
@endphp

{{-- WELCOME BANNER --}}
<div class="welcome-banner mb-4">
    <i class="bi bi-briefcase-fill welcome-illustration d-none d-md-block"></i>
    <div class="row align-items-center">
        <div class="col-md-8">
            <h3>{{ $greeting }}, {{ Auth::user()->name }} 👋</h3>
            <p class="fs-6">
                @if($totalPerlu > 0)
                    Ada <strong>{{ $totalPerlu }} dokumen kontrak/tagihan</strong> di meja Anda yang menunggu tindakan.
                @else
                    Tidak ada dokumen yang menunggu tindakan. Semua kontrak dalam kondisi terkendali.
                @endif
            </p>
            <div class="badge-action">
                <span class="dot"></span>
                <span>{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</span>
            </div>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="{{ route('contracts.create') }}" class="btn btn-light fw-bold px-4 py-2 rounded-pill shadow-sm">
                <i class="bi bi-plus-circle-fill me-1"></i> Buat Kontrak Baru
            </a>
        </div>
    </div>
</div>

{{-- KPI CARDS --}}
<div class="section-heading">
    <h6><i class="bi bi-speedometer2"></i> Ringkasan Pengadaan</h6>
    <span class="text-muted small">Tahun anggaran {{ $tahun }}</span>
</div>

<div class="row g-3 mb-2">
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card kpi-success">
            <div class="kpi-icon"><i class="bi bi-briefcase-fill"></i></div>
            <div class="kpi-label">Kontrak Aktif</div>
            <div class="kpi-value">{{ $kpi['kontrak_aktif'] }}</div>
            <div class="kpi-foot">
                <span class="kpi-pill tint-success">
                    <i class="bi bi-cash-coin me-1"></i>
                    Rp {{ number_format($kpi['nilai_kontrak_aktif'], 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card kpi-warning">
            <div class="kpi-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="kpi-label">Pending Persetujuan</div>
            <div class="kpi-value">{{ $kpi['kontrak_pending'] + $kpi['kontrak_draft'] }}</div>
            <div class="kpi-foot">
                <span class="kpi-pill tint-warning">
                    <i class="bi bi-pencil me-1"></i> {{ $kpi['kontrak_draft'] }} draft
                </span>
                <span class="kpi-pill tint-info">
                    <i class="bi bi-clock me-1"></i> {{ $kpi['kontrak_pending'] }} review
                </span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card kpi-info">
            <div class="kpi-icon"><i class="bi bi-people-fill"></i></div>
            <div class="kpi-label">Mitra / Vendor</div>
            <div class="kpi-value">{{ $kpi['total_vendor'] }}</div>
            <div class="kpi-foot">
                <span class="kpi-pill tint-info">
                    <i class="bi bi-shield-check me-1"></i> {{ $kpi['vendor_aktif'] }} dengan kontrak aktif
                </span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card kpi-danger">
            <div class="kpi-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="kpi-label">Butuh Perhatian</div>
            <div class="kpi-value">{{ $kpi['tagihan_revisi'] }}</div>
            <div class="kpi-foot">
                <span class="kpi-pill tint-danger">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Tagihan revisi/ditolak
                </span>
            </div>
        </div>
    </div>
</div>


{{-- CHARTS + QUICK ACTIONS --}}
<div class="section-heading">
    <h6><i class="bi bi-graph-up-arrow"></i> Analisis & Aksi Cepat</h6>
</div>

<div class="row g-3 mb-2">
    {{-- Tren chart --}}
    <div class="col-lg-7">
        <div class="panel h-fill" style="animation-delay: .35s;">
            <div class="panel-head">
                <h6><i class="bi bi-bar-chart-line ph-primary"></i> Tren Kontrak Baru — 6 Bulan</h6>
                <span class="kpi-pill tint-primary">
                    {{ $kpi['kontrak_total'] }} total kontrak
                </span>
            </div>
            <div class="panel-body">
                <div style="height: 280px; position: relative;">
                    <canvas id="trenChart"></canvas>
                </div>
            </div>
            <div class="panel-foot">
                <i class="bi bi-info-circle me-1"></i>
                Total nilai semua kontrak: <strong class="text-dark">Rp {{ number_format($kpi['total_nilai_semua'], 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>

    <div class="col-lg-5 d-flex flex-column gap-3">
        <div class="panel" style="animation-delay: .42s;">
            <div class="panel-head">
                <h6><i class="bi bi-pie-chart-fill ph-info"></i> Distribusi Status Kontrak</h6>
            </div>
            <div class="panel-body">
                <div style="height: 220px; position: relative;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <div class="panel" style="animation-delay: .49s;">
            <div class="panel-head">
                <h6><i class="bi bi-lightning-charge-fill ph-warning"></i> Aksi Cepat</h6>
            </div>
            <div class="panel-body d-flex flex-column gap-2">
                <a href="{{ route('contracts.create') }}" class="quick-action">
                    <span class="qa-icon" style="background: rgba(99,102,241,.12); color: #4f46e5;">
                        <i class="bi bi-file-earmark-plus"></i>
                    </span>
                    <div>
                        <p class="qa-title">Buat Kontrak Baru</p>
                        <p class="qa-sub">Mulai SPK / Kontrak baru</p>
                    </div>
                    <i class="bi bi-arrow-right qa-arrow"></i>
                </a>
                <a href="{{ route('contracts.index') }}" class="quick-action">
                    <span class="qa-icon" style="background: rgba(16,185,129,.12); color: #047857;">
                        <i class="bi bi-list-ul"></i>
                    </span>
                    <div>
                        <p class="qa-title">Daftar Kontrak</p>
                        <p class="qa-sub">Lihat semua kontrak</p>
                    </div>
                    <i class="bi bi-arrow-right qa-arrow"></i>
                </a>
                <a href="{{ route('suppliers.index') }}" class="quick-action">
                    <span class="qa-icon" style="background: rgba(59,130,246,.12); color: #1d4ed8;">
                        <i class="bi bi-people"></i>
                    </span>
                    <div>
                        <p class="qa-title">Master Mitra / Vendor</p>
                        <p class="qa-sub">Kelola data vendor</p>
                    </div>
                    <i class="bi bi-arrow-right qa-arrow"></i>
                </a>
            </div>
        </div>
    </div>
</div>

{{-- WORKLIST --}}
<div class="section-heading">
    <h6><i class="bi bi-clipboard-check"></i> Meja Kerja Anda</h6>
</div>

<div class="row g-3 mb-3">
    {{-- Kontrak Pending --}}
    <div class="col-lg-12">
        <div class="panel" style="animation-delay: .55s;">
            <div class="panel-head">
                <h6><i class="bi bi-pencil-square ph-warning"></i> Kontrak Pending Tindakan</h6>
                <span class="kpi-pill tint-warning">{{ $kpi['kontrak_pending'] + $kpi['kontrak_draft'] }} dokumen</span>
            </div>
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Nomor SPK & Pekerjaan</th>
                            <th>Vendor</th>
                            <th>Status</th>
                            <th class="text-end">Nilai</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kontrak_pending_list as $idx => $k)
                            @php $av = ($idx % 5) + 1; @endphp
                            <tr>
                                <td>
                                    <div class="doc-no">{{ $k->nomor_spk ?? '—' }}</div>
                                    <div class="doc-desc">{{ \Illuminate\Support\Str::limit($k->nama_pekerjaan ?? '-', 50) }}</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="vendor-avatar va-{{ $av }}">{{ \Illuminate\Support\Str::upper(mb_substr($k->vendor->nama_pihak ?? '?', 0, 1)) }}</span>
                                        <span class="text-muted small">{{ \Illuminate\Support\Str::limit($k->vendor->nama_pihak ?? '-', 30) }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($k->status_kontrak === 'DRAFT')
                                        <span class="pill pill-draft">Draft</span>
                                    @else
                                        <span class="pill pill-pending">Pending Review</span>
                                    @endif
                                </td>
                                <td class="text-end"><span class="money-pos">Rp {{ number_format($k->nilai_total_kontrak, 0, ',', '.') }}</span></td>
                                <td class="text-center">
                                    <a href="{{ route('contracts.show', $k->id) }}" class="btn-act btn-act-primary">
                                        <i class="bi bi-eye-fill"></i> Tinjau
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="bi bi-check-all"></i>
                                        <h6 class="text-secondary fw-bold mb-1">Tidak ada kontrak pending</h6>
                                        <small>Semua kontrak sudah diproses.</small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    {{-- Jatuh Tempo --}}
    <div class="col-lg-7">
        <div class="panel h-fill" style="animation-delay: .60s;">
            <div class="panel-head">
                <h6><i class="bi bi-alarm-fill ph-danger"></i> Peringatan Jatuh Tempo (H-14)</h6>
                <span class="kpi-pill tint-danger">{{ $table_jatuh_tempo->count() }} kontrak</span>
            </div>
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Kontrak</th>
                            <th>Vendor</th>
                            <th>Tanggal Selesai</th>
                            <th>Sisa Waktu</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($table_jatuh_tempo as $idx => $k)
                            @php
                                $av = ($idx % 5) + 1;
                                $endDate = \Carbon\Carbon::parse($k->tanggal_selesai);
                                $isLate = $endDate->isPast();
                                $diffDays = $endDate->diffInDays(now());
                            @endphp
                            <tr>
                                <td>
                                    <div class="doc-no">{{ $k->nomor_spk ?? '—' }}</div>
                                    <div class="doc-desc">{{ \Illuminate\Support\Str::limit($k->nama_pekerjaan ?? '-', 35) }}</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="vendor-avatar va-{{ $av }}">{{ \Illuminate\Support\Str::upper(mb_substr($k->vendor->nama_pihak ?? '?', 0, 1)) }}</span>
                                        <span class="text-muted small">{{ \Illuminate\Support\Str::limit($k->vendor->nama_pihak ?? '-', 18) }}</span>
                                    </div>
                                </td>
                                <td><span class="text-muted small">{{ $endDate->isoFormat('D MMM YYYY') }}</span></td>
                                <td>
                                    @if($isLate)
                                        <span class="pill pill-late">Terlambat {{ $diffDays }} hr</span>
                                    @else
                                        <span class="pill pill-soon">{{ $diffDays }} hari lagi</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('contracts.show', $k->id) }}" class="btn-act btn-act-soft">
                                        <i class="bi bi-search"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="bi bi-shield-check"></i>
                                        <h6 class="text-secondary fw-bold mb-1">Aman</h6>
                                        <small>Tidak ada kontrak yang mendekati jatuh tempo.</small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Top Vendor --}}
    <div class="col-lg-5">
        <div class="panel h-fill" style="animation-delay: .65s;">
            <div class="panel-head">
                <h6><i class="bi bi-trophy-fill ph-warning"></i> Top Mitra / Vendor</h6>
            </div>
            <div class="panel-body p-0">
                <ul class="list-unstyled mb-0">
                    @forelse($top_vendor as $idx => $v)
                        @php $av = ($idx % 5) + 1; @endphp
                        <li class="d-flex align-items-center gap-3 px-3 py-3 border-bottom" style="border-color:#f1f3f7 !important;">
                            <span class="vendor-avatar va-{{ $av }}" style="width:42px; height:42px; font-size:.92rem;">
                                {{ \Illuminate\Support\Str::upper(mb_substr($v->vendor?->nama_pihak ?? '?', 0, 1)) }}
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-bold text-dark text-truncate" style="font-size:.88rem;">{{ $v->vendor?->nama_pihak ?? '—' }}</div>
                                <div class="d-flex justify-content-between align-items-center gap-2 mt-1">
                                    <span class="kpi-pill tint-primary"><i class="bi bi-briefcase me-1"></i>{{ $v->total_kontrak }} kontrak</span>
                                    <span class="money-pos small">Rp {{ number_format($v->total_nilai_kontrak ?? 0, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li>
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                <h6 class="text-secondary fw-bold mb-0">Belum ada vendor aktif</h6>
                            </div>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- Kontrak Aktif --}}
    <div class="col-lg-7">
        <div class="panel h-fill" style="animation-delay: .70s;">
            <div class="panel-head">
                <h6><i class="bi bi-check-circle-fill ph-success"></i> Kontrak Aktif Terbaru</h6>
                <span class="kpi-pill tint-success">{{ $kpi['kontrak_aktif'] }} aktif</span>
            </div>
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Nomor SPK</th>
                            <th>Vendor</th>
                            <th class="text-end">Nilai</th>
                            <th class="text-center">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kontrak_aktif_terbaru as $idx => $k)
                            @php $av = ($idx % 5) + 1; @endphp
                            <tr>
                                <td>
                                    <div class="doc-no">{{ $k->nomor_spk ?? '—' }}</div>
                                    <div class="doc-desc">{{ \Illuminate\Support\Str::limit($k->nama_pekerjaan ?? '-', 40) }}</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="vendor-avatar va-{{ $av }}">{{ \Illuminate\Support\Str::upper(mb_substr($k->vendor->nama_pihak ?? '?', 0, 1)) }}</span>
                                        <span class="text-muted small">{{ \Illuminate\Support\Str::limit($k->vendor->nama_pihak ?? '-', 22) }}</span>
                                    </div>
                                </td>
                                <td class="text-end"><span class="money-pos">Rp {{ number_format($k->nilai_total_kontrak, 0, ',', '.') }}</span></td>
                                <td class="text-center">
                                    <a href="{{ route('contracts.show', $k->id) }}" class="btn-act btn-act-soft">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <i class="bi bi-briefcase"></i>
                                        <h6 class="text-secondary fw-bold mb-0">Belum ada kontrak aktif</h6>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tagihan Bermasalah --}}
    <div class="col-lg-5">
        <div class="panel h-fill" style="animation-delay: .75s;">
            <div class="panel-head">
                <h6><i class="bi bi-x-octagon-fill ph-danger"></i> Tagihan Bermasalah</h6>
                <span class="kpi-pill tint-danger">{{ $table_tagihan_revisi->count() }}</span>
            </div>
            <div class="panel-body p-0">
                <ul class="list-unstyled mb-0">
                    @forelse($table_tagihan_revisi as $tag)
                        <li class="d-flex flex-column gap-1 px-3 py-3 border-bottom" style="border-color:#f1f3f7 !important;">
                            <div class="d-flex justify-content-between align-items-center gap-2">
                                <span class="doc-no">{{ $tag->nomor_tagihan }}</span>
                                <span class="pill pill-revisi">{{ \Illuminate\Support\Str::title(strtolower(str_replace('_',' ',$tag->status))) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center gap-2 mt-1">
                                <small class="text-muted fst-italic">"{{ \Illuminate\Support\Str::limit($tag->logs->first()->catatan ?? 'Tidak ada catatan.', 38) }}"</small>
                                <span class="money-pos small">Rp {{ number_format($tag->total_netto, 0, ',', '.') }}</span>
                            </div>
                        </li>
                    @empty
                        <li>
                            <div class="empty-state">
                                <i class="bi bi-emoji-smile"></i>
                                <h6 class="text-secondary fw-bold mb-0">Tidak ada tagihan bermasalah</h6>
                            </div>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection


@push('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const fmtRp = v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v);
    const fmtCompact = v => {
        if (v >= 1e9) return 'Rp ' + (v / 1e9).toFixed(1) + ' M';
        if (v >= 1e6) return 'Rp ' + (v / 1e6).toFixed(1) + ' Jt';
        if (v >= 1e3) return 'Rp ' + (v / 1e3).toFixed(0) + ' rb';
        return 'Rp ' + v;
    };

    // Tren Chart (mixed: bar jumlah + line nilai)
    const trenCtx = document.getElementById('trenChart');
    if (trenCtx) {
        new Chart(trenCtx, {
            data: {
                labels: {!! json_encode($tren_labels) !!},
                datasets: [
                    {
                        type: 'bar',
                        label: 'Jumlah Kontrak',
                        data: {!! json_encode($tren_jumlah) !!},
                        backgroundColor: 'rgba(99, 102, 241, 0.85)',
                        borderRadius: 8,
                        yAxisID: 'y',
                        order: 2
                    },
                    {
                        type: 'line',
                        label: 'Nilai Kontrak',
                        data: {!! json_encode($tren_nilai) !!},
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.12)',
                        borderWidth: 3,
                        tension: 0.35,
                        fill: true,
                        pointBackgroundColor: '#f97316',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        yAxisID: 'y1',
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: { boxWidth: 14, boxHeight: 14, padding: 14, font: { size: 12 } }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,.95)',
                        padding: 12, cornerRadius: 8,
                        callbacks: {
                            label: function (ctx) {
                                if (ctx.dataset.yAxisID === 'y1') {
                                    return ctx.dataset.label + ': ' + fmtRp(ctx.raw);
                                }
                                return ctx.dataset.label + ': ' + ctx.raw + ' kontrak';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear', position: 'left',
                        beginAtZero: true,
                        ticks: { precision: 0, color: '#6366f1' },
                        title: { display: true, text: 'Jumlah', color: '#6366f1', font: { weight: 600 } },
                        grid: { color: '#f1f3f7' }
                    },
                    y1: {
                        type: 'linear', position: 'right',
                        beginAtZero: true,
                        ticks: { color: '#f97316', callback: fmtCompact },
                        title: { display: true, text: 'Nilai', color: '#f97316', font: { weight: 600 } },
                        grid: { drawOnChartArea: false }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Status Chart (Doughnut)
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        const labels = {!! json_encode($chart_status_labels) !!};
        const data = {!! json_encode($chart_status_data) !!};
        const colorMap = {
            'AKTIF': '#10b981',
            'PENDING_REVIEW': '#3b82f6',
            'DRAFT': '#94a3b8',
            'SELESAI': '#6366f1',
            'DIBATALKAN': '#f43f5e',
        };
        const bgColors = labels.map(l => colorMap[l] ?? '#94a3b8');

        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: labels.map(l => l.replace(/_/g, ' ')),
                datasets: [{
                    data: data,
                    backgroundColor: bgColors,
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, boxHeight: 12, padding: 12, font: { size: 11 } }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,.95)',
                        padding: 10, cornerRadius: 8,
                        callbacks: {
                            label: function (ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? Math.round(ctx.raw * 100 / total) : 0;
                                return ctx.label + ': ' + ctx.raw + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
