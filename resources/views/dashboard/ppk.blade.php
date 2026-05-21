@extends('layouts.app')

@section('title', 'Dashboard - Pejabat Pembuat Komitmen')

@push('css')
<style>
    /* Welcome banner */
    .welcome-banner {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%);
        border-radius: 1.25rem;
        padding: 1.75rem 2rem;
        color: #fff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 12px 32px rgba(79, 70, 229, .25);
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
        background: rgba(255,255,255,.08);
    }
    .welcome-banner::after {
        content: '';
        position: absolute;
        right: 60px; bottom: -60px;
        width: 180px; height: 180px;
        border-radius: 50%;
        background: rgba(255,255,255,.06);
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
        display: inline-flex; align-items: center; gap: .5rem;
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
        box-shadow: 0 0 0 4px rgba(253, 224, 71, .35);
        animation: pulse 1.8s infinite;
    }
    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(253, 224, 71, .55); }
        50%      { box-shadow: 0 0 0 8px rgba(253, 224, 71, 0); }
    }
    .welcome-illustration {
        position: absolute; right: 1.25rem; top: 50%;
        transform: translateY(-50%);
        font-size: 6rem;
        opacity: .15;
    }

    /* Section heading */
    .section-heading {
        display: flex; align-items: center; justify-content: space-between;
        margin: 1.75rem 0 1rem;
    }
    .section-heading h6 {
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #475569;
        margin: 0;
        display: inline-flex; align-items: center; gap: .6rem;
    }
    .section-heading h6::before {
        content: '';
        width: 4px; height: 18px;
        border-radius: 4px;
        background: linear-gradient(180deg, #6366f1, #2563eb);
    }

    /* KPI Card */
    .kpi-card {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        padding: 1.25rem;
        height: 100%;
        transition: transform .18s ease, box-shadow .18s ease;
        position: relative;
        overflow: hidden;
    }
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
        box-shadow: 0 12px 28px rgba(15, 23, 42, .08);
    }
    .kpi-card > * { position: relative; z-index: 1; }
    .kpi-card .kpi-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
        color: #fff;
        background: var(--kpi-icon-bg, linear-gradient(135deg, #6366f1, #2563eb));
        box-shadow: 0 6px 16px var(--kpi-icon-shadow, rgba(99, 102, 241, .30));
    }
    .kpi-card .kpi-label {
        font-size: .72rem; font-weight: 700; letter-spacing: .05em;
        color: #64748b; text-transform: uppercase;
        margin: .85rem 0 .15rem;
    }
    .kpi-card .kpi-value {
        font-size: 1.85rem; font-weight: 800; color: #0f172a;
        line-height: 1.1; margin: 0;
        font-variant-numeric: tabular-nums;
    }
    .kpi-card .kpi-foot {
        font-size: .78rem; color: #6b7280;
        margin-top: .65rem;
        display: flex; flex-wrap: wrap; gap: .35rem; align-items: center;
    }
    .kpi-card .kpi-pill {
        display: inline-flex; align-items: center;
        font-size: .7rem; font-weight: 600;
        padding: .15rem .65rem; border-radius: 999px;
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

    /* Panel */
    .panel {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        position: relative;
        overflow: hidden;
    }
    .panel.h-fill { height: 100%; }
    .panel-head {
        padding: 1.1rem 1.25rem .9rem;
        border-bottom: 1px solid #f1f3f7;
        display: flex; align-items: center; justify-content: space-between;
        background: linear-gradient(180deg, #fafbff 0%, #ffffff 100%);
    }
    .panel-head h6 {
        margin: 0;
        font-size: .95rem; font-weight: 700; color: #0f172a;
        display: inline-flex; align-items: center; gap: .55rem;
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

    /* Critical alert */
    .alert-critical {
        background: linear-gradient(135deg, rgba(244,63,94,.08), rgba(220,38,38,.04));
        border: 1px solid rgba(244,63,94,.25);
        border-left: 4px solid #f43f5e;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        color: #991b1b;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: .85rem;
        animation: shake .55s cubic-bezier(.36,.07,.19,.97) both;
    }
    .alert-critical .ac-icon {
        width: 42px; height: 42px;
        flex-shrink: 0;
        border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(244,63,94,.15); color: #b91c1c;
        font-size: 1.4rem;
    }
    .alert-critical strong { color: #b91c1c; }
    @keyframes shake {
        10%, 90% { transform: translateX(-1px); }
        20%, 80% { transform: translateX(2px); }
        30%, 50%, 70% { transform: translateX(-3px); }
        40%, 60% { transform: translateX(3px); }
    }
</style>

<style>
    /* Worklist tabs */
    .work-tabs {
        display: flex;
        gap: .35rem;
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        padding: .35rem;
        margin-bottom: 1rem;
        overflow-x: auto;
    }
    .work-tabs .tab-btn {
        flex: 1 1 auto;
        min-width: max-content;
        padding: .65rem 1.1rem;
        border-radius: .7rem;
        background: transparent;
        border: 0;
        color: #64748b;
        font-size: .85rem; font-weight: 600;
        cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
        transition: all .25s cubic-bezier(.22,1,.36,1);
        white-space: nowrap;
    }
    .work-tabs .tab-btn:hover { color: #1e293b; background: #f8fafc; }
    .work-tabs .tab-btn.active {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        box-shadow: 0 6px 14px rgba(99,102,241,.30);
    }
    .work-tabs .tab-btn .tab-count {
        background: rgba(255,255,255,.25);
        padding: .1rem .45rem;
        border-radius: 999px;
        font-size: .68rem; font-weight: 700;
    }
    .work-tabs .tab-btn:not(.active) .tab-count {
        background: rgba(99,102,241,.12);
        color: #4f46e5;
    }
    .tab-pane-d { display: none; animation: fadeUp .35s cubic-bezier(.22,1,.36,1) both; }
    .tab-pane-d.active { display: block; }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Modern table */
    .table-modern {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .table-modern thead th {
        background: #f8fafc;
        font-size: .7rem; font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #64748b;
        padding: .85rem 1rem;
        border-top: 1px solid #eef0f4;
        border-bottom: 1px solid #eef0f4;
        white-space: nowrap;
    }
    .table-modern thead th:first-child { padding-left: 1.5rem; }
    .table-modern thead th:last-child  { padding-right: 1.5rem; }
    .table-modern tbody td {
        padding: .9rem 1rem;
        font-size: .87rem;
        border-bottom: 1px solid #f1f3f7;
        background: #fff;
        vertical-align: middle;
        transition: background .15s ease;
    }
    .table-modern tbody td:first-child { padding-left: 1.5rem; }
    .table-modern tbody td:last-child  { padding-right: 1.5rem; }
    .table-modern tbody tr:hover td { background: #fafbff; }
    .table-modern tbody tr:last-child td { border-bottom: 0; }

    .doc-no { font-weight: 700; color: #1e293b; font-size: .87rem; }
    .doc-desc { font-size: .76rem; color: #64748b; margin-top: .15rem; }
    .money-pos { color: #047857; font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }

    .prio-pill {
        display: inline-flex; align-items: center; gap: .35rem;
        font-size: .7rem; font-weight: 700;
        padding: .25rem .65rem;
        border-radius: 999px;
    }
    .prio-pill::before {
        content: '';
        width: 6px; height: 6px;
        border-radius: 50%;
        background: currentColor;
        box-shadow: 0 0 0 3px currentColor;
        opacity: .25;
    }
    .prio-tinggi { background: rgba(220,38,38,.10); color: #b91c1c; }
    .prio-sedang { background: rgba(245,158,11,.12); color: #b45309; }

    .type-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        font-size: .7rem; font-weight: 700;
        padding: .15rem .65rem;
        border-radius: 999px;
        background: rgba(99,102,241,.12); color: #4338ca;
        text-transform: uppercase; letter-spacing: .04em;
    }
    .type-chip.type-spp  { background: rgba(99,102,241,.12); color: #4338ca; }
    .type-chip.type-npi  { background: rgba(139,92,246,.12); color: #6d28d9; }
    .type-chip.type-sp2d { background: rgba(236,72,153,.12); color: #9d174d; }

    .btn-review {
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: #fff;
        border: 0;
        font-size: .78rem; font-weight: 600;
        padding: .4rem .85rem;
        border-radius: .55rem;
        display: inline-flex; align-items: center; gap: .35rem;
        box-shadow: 0 4px 12px rgba(99,102,241,.30);
        transition: all .18s ease;
    }
    .btn-review:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(99,102,241,.40);
        color: #fff;
    }

    .empty-state {
        text-align: center;
        padding: 2.5rem 1rem;
        color: #94a3b8;
    }
    .empty-state i {
        font-size: 2.5rem;
        margin-bottom: .65rem;
        display: block;
        background: linear-gradient(135deg, #c7d2fe, #818cf8);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Modal review */
    #reviewModal .modal-content { border-radius: 1rem; overflow: hidden; }
    #reviewModal .modal-header {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff;
        border: 0;
        padding: 1rem 1.5rem;
    }
    #reviewModal .modal-header .modal-title { color: #fff; font-weight: 700; }
    #reviewModal .modal-header .btn-close { filter: invert(1); }
    #reviewModal .info-row {
        padding: .55rem 0;
        border-bottom: 1px dashed #e2e8f0;
        font-size: .85rem;
    }
    #reviewModal .info-row:last-child { border-bottom: 0; }
    #reviewModal .info-row .label { color: #64748b; font-weight: 500; }
    #reviewModal .info-row .value { font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
    #reviewModal .review-actions {
        background: linear-gradient(180deg, #fafbff 0%, #f1f5f9 100%);
        border-top: 1px solid #e2e8f0;
    }
    .btn-approve {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff; border: 0;
        font-weight: 700; padding: .75rem 1rem;
        border-radius: .65rem;
        box-shadow: 0 6px 16px rgba(16,185,129,.30);
        transition: all .2s ease;
    }
    .btn-approve:hover {
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 10px 24px rgba(16,185,129,.40);
    }
    .btn-reject {
        background: rgba(244,63,94,.10);
        color: #be123c;
        border: 1px solid rgba(244,63,94,.20);
        font-weight: 600; padding: .65rem 1rem;
        border-radius: .65rem;
        transition: all .2s ease;
    }
    .btn-reject:hover {
        background: #f43f5e;
        color: #fff;
        border-color: #f43f5e;
    }
</style>
@endpush

@section('content')

@php
    $hour = (int) date('H');
    $greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 18 ? 'Selamat Sore' : 'Selamat Malam'));
    $totalAksi = $kpi['kontrak_baru'] + $kpi['tagihan_bast'] + $kpi['pencairan'];
    $persenSerap = $kpi['total_pagu'] > 0 ? round((($kpi['total_pagu'] - $kpi['sisa_pagu']) / $kpi['total_pagu']) * 100, 1) : 0;
@endphp

{{-- WELCOME BANNER --}}
<div class="welcome-banner mb-4">
    <i class="bi bi-pen welcome-illustration d-none d-md-block"></i>
    <div class="row align-items-center">
        <div class="col-md-8">
            <h3>{{ $greeting }}, {{ Auth::user()->name }} 👋</h3>
            <p class="fs-6">
                @if($totalAksi > 0)
                    Ada <strong>{{ $totalAksi }} dokumen</strong> di meja Anda yang menunggu otorisasi hari ini.
                @else
                    Tidak ada dokumen yang menunggu otorisasi. Selamat menikmati hari produktif!
                @endif
            </p>
            <div class="badge-action">
                <span class="dot"></span>
                <span>{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</span>
            </div>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="#worklistSection" class="btn btn-light fw-bold px-4 py-2 rounded-pill shadow-sm">
                <i class="bi bi-clipboard-check me-1"></i> Lihat Meja Kerja
            </a>
        </div>
    </div>
</div>

{{-- Critical alert --}}
@if($alertModal)
<div class="alert-critical">
    <div class="ac-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
    <div>
        <strong>Peringatan Kritis</strong>
        <div>{{ $alertModal }}</div>
    </div>
</div>
@endif

{{-- KPI CARDS --}}
<div class="section-heading">
    <h6><i class="bi bi-speedometer2"></i> Ringkasan Tugas Otorisasi</h6>
    <span class="text-muted small">Tahun anggaran {{ date('Y') }}</span>
</div>

<div class="row g-3 mb-2">
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card kpi-warning">
            <div class="kpi-icon"><i class="bi bi-file-earmark-plus-fill"></i></div>
            <div class="kpi-label">Verifikasi SPK Baru</div>
            <div class="kpi-value">{{ $kpi['kontrak_baru'] }}</div>
            <div class="kpi-foot">
                <span class="kpi-pill tint-warning"><i class="bi bi-hourglass-split me-1"></i> Menunggu PPK</span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card kpi-info">
            <div class="kpi-icon"><i class="bi bi-receipt-cutoff"></i></div>
            <div class="kpi-label">Verifikasi BAST / Tagihan</div>
            <div class="kpi-value">{{ $kpi['tagihan_bast'] }}</div>
            <div class="kpi-foot">
                <span class="kpi-pill tint-info"><i class="bi bi-shield-check me-1"></i> Cek kesesuaian fisik</span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card kpi-danger">
            <div class="kpi-icon"><i class="bi bi-pen-fill"></i></div>
            <div class="kpi-label">Otorisasi Pencairan</div>
            <div class="kpi-value">{{ $kpi['pencairan'] }}</div>
            <div class="kpi-foot">
                <span class="kpi-pill tint-danger"><i class="bi bi-fingerprint me-1"></i> SPP, NPI & SP2D</span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card kpi-success">
            <div class="kpi-icon"><i class="bi bi-piggy-bank-fill"></i></div>
            <div class="kpi-label">Sisa Pagu DIPA {{ date('Y') }}</div>
            <div class="kpi-value" style="font-size:1.25rem;" title="Rp {{ number_format($kpi['sisa_pagu'], 0, ',', '.') }}">Rp {{ number_format($kpi['sisa_pagu'], 0, ',', '.') }}</div>
            <div class="kpi-foot">
                <span class="kpi-pill tint-success"><i class="bi bi-graph-up me-1"></i> {{ $persenSerap }}% terserap</span>
            </div>
        </div>
    </div>
</div>


{{-- CHARTS --}}
<div class="section-heading">
    <h6><i class="bi bi-graph-up-arrow"></i> Analisis Anggaran</h6>
</div>

<div class="row g-3 mb-2">
    <div class="col-lg-5">
        <div class="panel h-fill">
            <div class="panel-head">
                <h6><i class="bi bi-pie-chart-fill ph-success"></i> Persentase Serapan DIPA</h6>
            </div>
            <div class="panel-body">
                <div style="height: 280px; position: relative;">
                    <canvas id="serapanChart"></canvas>
                </div>
            </div>
            <div class="panel-foot">
                <i class="bi bi-info-circle me-1"></i>
                Total Pagu: <strong class="text-dark">Rp {{ number_format($kpi['total_pagu'], 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="panel h-fill">
            <div class="panel-head">
                <h6><i class="bi bi-bar-chart-line ph-primary"></i> Serapan per Jenis Belanja</h6>
            </div>
            <div class="panel-body">
                <div style="height: 280px; position: relative;">
                    <canvas id="belanjaChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- WORKLIST --}}
<div class="section-heading" id="worklistSection">
    <h6><i class="bi bi-clipboard-check"></i> Meja Kerja Anda</h6>
    <span class="kpi-pill tint-primary">{{ $totalAksi }} dokumen menunggu</span>
</div>

<div class="work-tabs" id="workTabs">
    <button class="tab-btn active" data-tab="kontrak">
        <i class="bi bi-file-earmark-text"></i> Kontrak Baru
        <span class="tab-count">{{ $kpi['kontrak_baru'] }}</span>
    </button>
    <button class="tab-btn" data-tab="tagihan">
        <i class="bi bi-receipt-cutoff"></i> Tagihan / BAST
        <span class="tab-count">{{ $kpi['tagihan_bast'] }}</span>
    </button>
    <button class="tab-btn" data-tab="pencairan">
        <i class="bi bi-fingerprint"></i> TTD Pencairan
        <span class="tab-count">{{ $kpi['pencairan'] }}</span>
    </button>
</div>

{{-- TAB: KONTRAK --}}
<div class="tab-pane-d active" data-pane="kontrak">
    <div class="panel">
        <div class="panel-head">
            <h6><i class="bi bi-file-earmark-text ph-warning"></i> Kontrak Baru Menunggu Verifikasi</h6>
        </div>
        <div style="overflow-x: auto;">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Prioritas</th>
                        <th>Nomor SPK & Pekerjaan</th>
                        <th class="text-end">Nilai</th>
                        <th>Vendor</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tab_kontrak as $k)
                        <tr>
                            <td><span class="prio-pill prio-sedang">Sedang</span></td>
                            <td>
                                <div class="doc-no">{{ $k->nomor_spk }}</div>
                                <div class="doc-desc">{{ Str::limit($k->nama_pekerjaan, 50) }}</div>
                            </td>
                            <td class="text-end"><span class="money-pos">Rp {{ number_format($k->nilai_total_kontrak, 0, ',', '.') }}</span></td>
                            <td><span class="text-muted small">{{ $k->vendor->nama_perusahaan ?? '-' }}</span></td>
                            <td class="text-center">
                                <button class="btn-review btn-do-review"
                                    data-title="Review Kontrak: {{ $k->nomor_spk }}"
                                    data-url-approve="{{ route('contracts.approve', $k->id) }}"
                                    data-url-reject="{{ route('contracts.reject', $k->id) }}"
                                    data-file="{{ $k->file_spk_final_ttd ? asset('storage/' . $k->file_spk_final_ttd) : '' }}"
                                    data-nominal="Rp {{ number_format($k->nilai_total_kontrak, 0, ',', '.') }}">
                                    <i class="bi bi-eye-fill"></i> Review & TTD
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <h6 class="text-secondary fw-bold mb-1">Tidak ada kontrak baru</h6>
                                    <small>Semua kontrak sudah diverifikasi.</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- TAB: TAGIHAN --}}
<div class="tab-pane-d" data-pane="tagihan">
    <div class="panel">
        <div class="panel-head">
            <h6><i class="bi bi-receipt-cutoff ph-info"></i> Tagihan / BAST Menunggu Verifikasi</h6>
        </div>
        <div style="overflow-x: auto;">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Prioritas</th>
                        <th>Tipe</th>
                        <th>Nomor & Deskripsi</th>
                        <th class="text-end">Nilai Bruto</th>
                        <th>Pembuat</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tab_tagihan as $t)
                        <tr>
                            <td><span class="prio-pill prio-sedang">Sedang</span></td>
                            <td><span class="type-chip">{{ $t->tipe_tagihan }}</span></td>
                            <td>
                                <div class="doc-no">{{ $t->nomor_tagihan }}</div>
                                <div class="doc-desc">{{ Str::limit($t->deskripsi, 50) }}</div>
                            </td>
                            <td class="text-end"><span class="money-pos">Rp {{ number_format($t->total_bruto, 0, ',', '.') }}</span></td>
                            <td><span class="text-muted small">{{ $t->creator->name ?? '-' }}</span></td>
                            <td class="text-center">
                                <button class="btn-review btn-do-review"
                                    data-title="Review Tagihan: {{ $t->nomor_tagihan }}"
                                    data-url-approve="#"
                                    data-url-reject="#"
                                    data-nominal="Rp {{ number_format($t->total_bruto, 0, ',', '.') }}">
                                    <i class="bi bi-eye-fill"></i> Review & TTD
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <h6 class="text-secondary fw-bold mb-1">Tidak ada tagihan menunggu</h6>
                                    <small>Semua BAST/tagihan sudah diverifikasi.</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- TAB: PENCAIRAN --}}
<div class="tab-pane-d" data-pane="pencairan">
    <div class="panel">
        <div class="panel-head">
            <h6><i class="bi bi-fingerprint ph-danger"></i> Dokumen Pencairan Siap TTD</h6>
        </div>
        <div style="overflow-x: auto;">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Prioritas</th>
                        <th>Jenis</th>
                        <th>Nomor Surat</th>
                        <th class="text-end">Nilai</th>
                        <th>Pembuat</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tab_pencairan as $p)
                        @php
                            $typeCls = strtolower($p->jenis) === 'spp' ? 'type-spp' : (strtolower($p->jenis) === 'npi' ? 'type-npi' : 'type-sp2d');
                            $prioCls = $p->prioritas === 'Tinggi' ? 'prio-tinggi' : 'prio-sedang';
                        @endphp
                        <tr>
                            <td><span class="prio-pill {{ $prioCls }}">{{ $p->prioritas }}</span></td>
                            <td><span class="type-chip {{ $typeCls }}">{{ $p->jenis }}</span></td>
                            <td><div class="doc-no">{{ $p->nomor }}</div></td>
                            <td class="text-end">
                                @if($p->nilai > 0)
                                    <span class="money-pos">Rp {{ number_format($p->nilai, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-muted small fst-italic">Mengikuti SPP</span>
                                @endif
                            </td>
                            <td><span class="text-muted small">{{ $p->pembuat }}</span></td>
                            <td class="text-center">
                                <button class="btn-review btn-do-review"
                                    data-title="Review Pencairan: {{ $p->nomor }}"
                                    data-url-approve="{{ $p->url_approve }}"
                                    data-url-reject="{{ $p->url_reject }}"
                                    data-nominal="{{ $p->nilai > 0 ? 'Rp '.number_format($p->nilai,0,',','.') : '-' }}">
                                    <i class="bi bi-eye-fill"></i> Review & TTD
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <h6 class="text-secondary fw-bold mb-1">Tidak ada dokumen pencairan</h6>
                                    <small>Semua dokumen pencairan sudah ditandatangani.</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL REVIEW --}}
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel"><i class="bi bi-eye-fill me-2"></i>Review Dokumen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0 h-100">
                    <div class="col-lg-8 border-end bg-light p-3 d-flex flex-column" style="min-height: 500px;">
                        <span class="fw-bold small text-secondary mb-2"><i class="bi bi-file-pdf text-danger me-1"></i> Pratinjau Dokumen PDF</span>
                        <iframe id="pdfPreview" src="" class="w-100 flex-grow-1 border rounded bg-white shadow-sm" style="display: none;"></iframe>
                        <div id="pdfNoFile" class="w-100 flex-grow-1 border rounded bg-white shadow-sm d-flex justify-content-center align-items-center flex-column text-muted">
                            <i class="bi bi-file-earmark-x" style="font-size: 3rem;"></i>
                            <p class="small mt-2 mb-0">File dokumen belum diunggah atau tidak tersedia.</p>
                        </div>
                    </div>
                    <div class="col-lg-4 d-flex flex-column">
                        <div class="p-4 flex-grow-1">
                            <h6 class="fw-bold text-secondary text-uppercase small mb-3" style="letter-spacing:.05em;">Informasi Utama</h6>
                            <div class="info-row d-flex justify-content-between">
                                <span class="label">Nomor Seri</span>
                                <span class="value text-end" id="reviewNoSeri">-</span>
                            </div>
                            <div class="info-row d-flex justify-content-between">
                                <span class="label">Total Nilai</span>
                                <span class="value text-end" style="color:#047857;" id="reviewNilai">Rp 0</span>
                            </div>

                            <div class="mt-4 p-3 rounded" style="background: rgba(245,158,11,.08); border: 1px solid rgba(245,158,11,.20);">
                                <span class="fw-bold small" style="color:#b45309;"><i class="bi bi-info-circle me-1"></i> Catatan Sebelumnya:</span>
                                <p class="mb-0 mt-2 small fst-italic" style="color:#92400e;">"Dokumen sudah sesuai dengan fisik dan pagu masih tersedia."</p>
                            </div>
                        </div>

                        <div class="review-actions p-3">
                            <form id="formReject" method="POST" action="" class="mb-2">
                                @csrf
                                <textarea name="notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Alasan revisi/penolakan (wajib jika ditolak)..." style="resize: none; border-radius: .55rem;"></textarea>
                                <button type="button" class="btn-reject w-100" onclick="submitReject()"><i class="bi bi-arrow-return-left me-1"></i> Kembalikan / Tolak</button>
                            </form>
                            <form id="formApprove" method="POST" action="">
                                @csrf
                                <button type="button" class="btn-approve w-100" onclick="submitApprove()"><i class="bi bi-check-circle-fill me-1"></i> Setujui Dokumen</button>
                            </form>
                        </div>
                    </div>
                </div>
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

    // Tabs
    const tabs = document.querySelectorAll('#workTabs .tab-btn');
    const panes = document.querySelectorAll('.tab-pane-d');
    tabs.forEach(t => {
        t.addEventListener('click', () => {
            tabs.forEach(x => x.classList.toggle('active', x.dataset.tab === t.dataset.tab));
            panes.forEach(p => p.classList.toggle('active', p.dataset.pane === t.dataset.tab));
        });
    });

    // Modal
    const reviewModalEl = document.getElementById('reviewModal');
    const reviewModal = reviewModalEl ? new bootstrap.Modal(reviewModalEl) : null;
    document.querySelectorAll('.btn-do-review').forEach(btn => {
        btn.addEventListener('click', function () {
            if (!reviewModal) return;
            const title = this.dataset.title || '-';
            document.getElementById('reviewModalLabel').innerHTML = '<i class="bi bi-eye-fill me-2"></i>' + title;
            document.getElementById('reviewNoSeri').textContent = (title.split(': ')[1] || '-');
            document.getElementById('reviewNilai').textContent = this.dataset.nominal || '-';
            document.getElementById('formApprove').setAttribute('action', this.dataset.urlApprove || '#');
            document.getElementById('formReject').setAttribute('action', this.dataset.urlReject || '#');

            const fileUrl = this.dataset.file || '';
            const iframe = document.getElementById('pdfPreview');
            const placeholder = document.getElementById('pdfNoFile');
            if (fileUrl && !fileUrl.endsWith('/storage/')) {
                iframe.src = fileUrl;
                iframe.style.display = 'block';
                placeholder.style.display = 'none';
            } else {
                iframe.src = '';
                iframe.style.display = 'none';
                placeholder.style.display = 'flex';
            }
            reviewModal.show();
        });
    });

    window.submitApprove = function () {
        if (confirm('Apakah Anda yakin menyetujui dokumen ini?')) {
            document.getElementById('formApprove').submit();
        }
    };
    window.submitReject = function () {
        const notes = document.querySelector('#formReject textarea[name="notes"]').value.trim();
        if (notes === '') {
            alert('Alasan penolakan / perbaikan wajib diisi!');
            return;
        }
        if (confirm('Kembalikan dokumen ini untuk direvisi?')) {
            document.getElementById('formReject').submit();
        }
    };

    // Doughnut: Serapan
    const ctxSerapan = document.getElementById('serapanChart');
    if (ctxSerapan) {
        new Chart(ctxSerapan, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($chart_serapan_labels) !!},
                datasets: [{
                    data: {!! json_encode($chart_serapan_data) !!},
                    backgroundColor: ['#10b981', '#e2e8f0'],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, boxHeight: 12, padding: 12, font: { size: 11 } } },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,.95)',
                        padding: 10, cornerRadius: 8,
                        callbacks: {
                            label: function (ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? Math.round(ctx.raw * 100 / total) : 0;
                                return ctx.label + ': ' + fmtRp(ctx.raw) + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // Bar: Serapan per Belanja
    const ctxBelanja = document.getElementById('belanjaChart');
    if (ctxBelanja) {
        new Chart(ctxBelanja, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chart_bar_labels) !!},
                datasets: [
                    {
                        label: 'Pagu DIPA',
                        data: {!! json_encode($chart_bar_pagu) !!},
                        backgroundColor: 'rgba(99,102,241,0.20)',
                        borderRadius: 8,
                        borderWidth: 0,
                    },
                    {
                        label: 'Terserap',
                        data: {!! json_encode($chart_bar_realisasi) !!},
                        backgroundColor: 'rgba(99,102,241,0.95)',
                        borderRadius: 8,
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', align: 'end', labels: { boxWidth: 14, boxHeight: 14, padding: 14, font: { size: 12 } } },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,.95)',
                        padding: 12, cornerRadius: 8,
                        callbacks: {
                            label: ctx => ctx.dataset.label + ': ' + fmtRp(ctx.raw)
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { callback: fmtCompact, color: '#64748b' },
                        grid: { color: '#f1f3f7' }
                    },
                    y: { grid: { display: false }, ticks: { color: '#475569', font: { weight: 600 } } }
                }
            }
        });
    }
});
</script>
@endpush
