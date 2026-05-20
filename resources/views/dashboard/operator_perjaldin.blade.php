@extends('layouts.app')

@section('title', 'Dashboard Operator Perjaldin')

@push('css')
<style>
    /* ============== Modern Design System (Plus Jakarta Sans vibe) ============== */
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

    :root {
        --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        --accent-gradient: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
        --warn-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        --danger-gradient: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
        --success-gradient: linear-gradient(135deg, #10b981 0%, #047857 100%);
        --bg-glass: rgba(255, 255, 255, 0.7);
        --border-glass: rgba(255, 255, 255, 0.4);
    }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif !important;
    }

    /* ============== Welcome banner ============== */
    .welcome-banner {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #0d9488 100%);
        border-radius: 1.5rem;
        padding: 2.25rem;
        color: #fff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 16px 40px rgba(30, 58, 138, 0.2);
        animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    .welcome-banner,
    .welcome-banner h3,
    .welcome-banner p,
    .welcome-banner span,
    .welcome-banner strong {
        color: #fff !important;
    }
    .welcome-banner h3 strong { color: #fde047 !important; }
    
    .welcome-banner::before {
        content: '';
        position: absolute;
        right: -60px; top: -60px;
        width: 320px; height: 320px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, transparent 70%);
    }
    
    .welcome-banner::after {
        content: '';
        position: absolute;
        right: 120px; bottom: -80px;
        width: 240px; height: 240px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 75%);
    }
    
    .welcome-banner > * { position: relative; z-index: 1; }
    
    .welcome-banner h3 {
        font-weight: 800;
        font-size: 2rem;
        margin: 0 0 .5rem;
        letter-spacing: -.02em;
        text-shadow: 0 2px 4px rgba(0,0,0,.15);
    }
    
    .welcome-banner p {
        margin: 0;
        opacity: 0.95;
        font-size: 1.05rem;
        line-height: 1.5;
    }
    
    .welcome-banner .badge-action {
        display: inline-flex;
        align-items: center;
        gap: .6rem;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: .6rem 1.25rem;
        border-radius: 999px;
        font-size: .85rem;
        font-weight: 600;
        margin-top: 1.25rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .welcome-banner .badge-action .dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #fde047;
        box-shadow: 0 0 0 4px rgba(253, 224, 71, .35);
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(253, 224, 71, 0.6); }
        50%      { box-shadow: 0 0 0 10px rgba(253, 224, 71, 0); }
    }
    
    .welcome-illustration {
        position: absolute;
        right: 2.5rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 7.5rem;
        opacity: .12;
        transition: transform 0.3s ease;
    }

    .welcome-banner:hover .welcome-illustration {
        transform: translateY(-50%) rotate(5deg) scale(1.05);
    }

    /* ============== Section heading ============== */
    .section-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 2.25rem 0 1.25rem;
    }
    
    .section-heading h6 {
        font-size: .85rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #334155;
        margin: 0;
        display: inline-flex;
        align-items: center;
        gap: .75rem;
    }
    
    .section-heading h6::before {
        content: '';
        width: 5px; height: 20px;
        border-radius: 4px;
        background: linear-gradient(180deg, #3b82f6, #0d9488);
    }

    /* ============== KPI Card ============== */
    .kpi-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 1.25rem;
        padding: 1.5rem;
        height: 100%;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
    }
    
    .kpi-card::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 5px;
        background: var(--kpi-accent, var(--primary-gradient));
    }
    
    .kpi-card::after {
        content: '';
        position: absolute;
        right: -40px; top: -40px;
        width: 130px; height: 130px;
        border-radius: 50%;
        background: var(--kpi-glow, radial-gradient(circle, rgba(59,130,246,.08), transparent 70%));
        z-index: 0;
    }
    
    .kpi-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 32px rgba(15, 23, 42, 0.08);
        border-color: #cbd5e1;
    }
    
    .kpi-card > * { position: relative; z-index: 1; }
    
    .kpi-card .kpi-icon {
        width: 52px; height: 52px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        color: #fff;
        background: var(--kpi-icon-bg, var(--primary-gradient));
        box-shadow: 0 8px 20px var(--kpi-icon-shadow, rgba(59, 102, 241, 0.25));
        transition: transform 0.3s ease;
    }
    
    .kpi-card:hover .kpi-icon {
        transform: scale(1.08) rotate(-3deg);
    }

    .kpi-card .kpi-label {
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .06em;
        color: #64748b;
        text-transform: uppercase;
        margin: 1.1rem 0 .2rem;
    }
    
    .kpi-card .kpi-value {
        font-size: 2.1rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
        margin: 0;
        letter-spacing: -.02em;
    }
    
    .kpi-card .kpi-foot {
        font-size: .8rem;
        color: #64748b;
        margin-top: .85rem;
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
        align-items: center;
    }
    
    .kpi-card .kpi-pill {
        display: inline-flex;
        align-items: center;
        font-size: .72rem;
        font-weight: 700;
        padding: .2rem .75rem;
        border-radius: 999px;
        white-space: nowrap;
    }

    /* KPI Colors */
    .kpi-draft {
        --kpi-accent: linear-gradient(90deg, #94a3b8, #64748b);
        --kpi-glow: radial-gradient(circle, rgba(148,163,184,.12), transparent 70%);
        --kpi-icon-bg: linear-gradient(135deg, #cbd5e1, #64748b);
        --kpi-icon-shadow: rgba(148,163,184,.2);
    }
    .kpi-revisi {
        --kpi-accent: var(--danger-gradient);
        --kpi-glow: radial-gradient(circle, rgba(244,63,94,.12), transparent 70%);
        --kpi-icon-bg: var(--danger-gradient);
        --kpi-icon-shadow: rgba(244,63,94,.25);
    }
    .kpi-verifikasi {
        --kpi-accent: var(--primary-gradient);
        --kpi-glow: radial-gradient(circle, rgba(59,130,246,.12), transparent 70%);
        --kpi-icon-bg: var(--primary-gradient);
        --kpi-icon-shadow: rgba(59,130,246,.25);
    }
    .kpi-ttd {
        --kpi-accent: var(--warn-gradient);
        --kpi-glow: radial-gradient(circle, rgba(245,158,11,.15), transparent 70%);
        --kpi-icon-bg: var(--warn-gradient);
        --kpi-icon-shadow: rgba(245,158,11,.25);
    }
    .kpi-selesai {
        --kpi-accent: var(--success-gradient);
        --kpi-glow: radial-gradient(circle, rgba(16,185,129,.12), transparent 70%);
        --kpi-icon-bg: var(--success-gradient);
        --kpi-icon-shadow: rgba(16,185,129,.25);
    }

    .tint-draft    { background: rgba(148, 163, 184, 0.15); color: #475569; }
    .tint-revisi   { background: rgba(244, 63, 94, 0.12); color: #be123c; }
    .tint-verif    { background: rgba(59, 130, 246, 0.12); color: #1d4ed8; }
    .tint-ttd      { background: rgba(245, 158, 11, 0.15); color: #b45309; }
    .tint-success  { background: rgba(16, 185, 129, 0.12); color: #047857; }

    /* ============== Panels ============== */
    .panel {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 1.5rem;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.01);
        overflow: hidden;
    }
    
    .panel.h-fill { height: 100%; }
    
    .panel-head {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    }
    
    .panel-head h6 {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
        display: inline-flex;
        align-items: center;
        gap: .7rem;
    }
    
    .panel-head h6 i {
        width: 34px; height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(59,130,246,0.1);
        color: #3b82f6;
        font-size: 1.15rem;
    }

    .panel-head h6 i.ph-revisi   { background: rgba(244,63,94,0.12); color: #e11d48; }
    .panel-head h6 i.ph-verif    { background: rgba(59,130,246,0.12); color: #2563eb; }
    .panel-head h6 i.ph-ttd      { background: rgba(245,158,11,0.12); color: #d97706; }
    .panel-head h6 i.ph-success  { background: rgba(16,185,129,0.12); color: #059669; }
    
    .panel-body { padding: 1.25rem 1.5rem; }
    
    .panel-foot {
        padding: .9rem 1.5rem;
        border-top: 1px solid #f1f5f9;
        font-size: .85rem;
        color: #64748b;
        background: #f8fafc;
    }

    /* ============== Modern Action Table ============== */
    .table-modern {
        margin: 0;
    }
    
    .table-modern thead th {
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #475569;
        background: #f8fafc;
        border-top: 0;
        border-bottom: 2px solid #cbd5e1;
        padding: 1rem 1.5rem;
    }
    
    .table-modern tbody td {
        padding: 1.1rem 1.5rem;
        vertical-align: middle;
        border-color: #f1f5f9;
        font-size: .88rem;
        color: #334155;
    }
    
    .table-modern tbody tr {
        transition: background-color 0.2s ease;
    }

    .table-modern tbody tr:hover td {
        background: #f8fafc;
    }
    
    .table-modern .doc-no {
        font-weight: 700;
        color: #0f172a;
        font-size: .92rem;
    }
    
    .table-modern .doc-desc {
        font-size: .78rem;
        color: #64748b;
        margin-top: .2rem;
        font-weight: 500;
    }

    /* ============== Status badges ============== */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        font-size: .75rem;
        font-weight: 700;
        padding: .3rem .8rem;
        border-radius: 999px;
        white-space: nowrap;
    }
    
    .status-pill::before {
        content: '';
        width: 6px; height: 6px;
        border-radius: 50%;
        background: currentColor;
    }
    
    .status-draft       { background: rgba(148,163,184,0.15); color: #475569; }
    .status-revisi      { background: rgba(244,63,94,0.12); color: #e11d48; }
    .status-verifikasi  { background: rgba(59,130,246,0.12); color: #2563eb; }
    .status-ttd         { background: rgba(245,158,11,0.12); color: #d97706; }
    .status-disetujui   { background: rgba(16,185,129,0.12); color: #059669; }

    /* ============== Interactive Parallel Matrix Tracker ============== */
    .parallel-tracker-matrix {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }
    
    .tracker-node {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.88rem;
        border: 2px solid #e2e8f0;
        position: relative;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        cursor: pointer;
    }

    /* States mapping */
    .tracker-node.state-done {
        background: #10b981;
        color: #ffffff;
        border-color: #059669;
        box-shadow: 0 2px 6px rgba(16, 185, 129, 0.2);
    }
    
    .tracker-node.state-active {
        background: #3b82f6;
        color: #ffffff;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        animation: activePulse 2s infinite;
    }
    
    .tracker-node.state-revision {
        background: #f59e0b;
        color: #ffffff;
        border-color: #d97706;
    }

    .tracker-node.state-rejected {
        background: #ef4444;
        color: #ffffff;
        border-color: #dc2626;
    }

    .tracker-node.state-pending {
        background: #f1f5f9;
        color: #94a3b8;
        border-color: #cbd5e1;
    }

    @keyframes activePulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.6); }
        50%      { box-shadow: 0 0 0 6px rgba(59, 130, 246, 0); }
    }

    /* Tooltip styling */
    .tracker-node::after {
        content: attr(data-title);
        position: absolute;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%) translateY(4px);
        background: #0f172a;
        color: #ffffff;
        padding: 0.4rem 0.6rem;
        border-radius: 0.5rem;
        font-size: 0.65rem;
        font-weight: 700;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: all 0.2s ease;
        z-index: 10;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .tracker-node::before {
        content: '';
        position: absolute;
        bottom: 105%;
        left: 50%;
        transform: translateX(-50%) translateY(4px);
        border: 5px solid transparent;
        border-top-color: #0f172a;
        opacity: 0;
        pointer-events: none;
        transition: all 0.2s ease;
        z-index: 10;
    }

    .tracker-node:hover::after,
    .tracker-node:hover::before {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    /* ============== Dropdown & Custom Form styling ============== */
    .upload-collapse-card {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 1rem;
        padding: 1.25rem;
        margin-top: 0.75rem;
        animation: slideDown 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ============== Empty state ============== */
    .empty-state {
        text-align: center;
        padding: 3rem 1.5rem;
        color: #94a3b8;
    }
    
    .empty-state i {
        font-size: 3rem;
        margin-bottom: .85rem;
        opacity: .6;
        display: block;
        color: #cbd5e1;
    }

    /* ============== Quick actions ============== */
    .quick-action {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.25rem;
        border-radius: 1rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        text-decoration: none;
        color: #1e293b;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    .quick-action:hover {
        border-color: #3b82f6;
        background: #fafbff;
        transform: translateX(6px);
        color: #2563eb;
        text-decoration: none;
    }
    
    .quick-action .qa-icon {
        width: 44px; height: 44px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
        transition: all 0.2s ease;
    }

    .quick-action:hover .qa-icon {
        transform: scale(1.05);
    }
    
    .quick-action .qa-title {
        font-weight: 700;
        font-size: .92rem;
        margin: 0;
    }
    
    .quick-action .qa-sub {
        font-size: .78rem;
        color: #94a3b8;
        margin: 0;
        font-weight: 500;
    }
    
    .quick-action .qa-arrow {
        margin-left: auto;
        color: #cbd5e1;
        transition: all 0.2s ease;
    }
    
    .quick-action:hover .qa-arrow {
        color: #3b82f6;
        transform: translateX(3px);
    }

    /* ============== File Upload Styling ============== */
    .file-input-wrapper {
        position: relative;
        border: 2px dashed #cbd5e1;
        border-radius: 0.75rem;
        padding: 1.5rem;
        text-align: center;
        background: #fff;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .file-input-wrapper:hover {
        border-color: #3b82f6;
        background: rgba(59, 130, 246, 0.02);
    }

    .file-input-wrapper input[type="file"] {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }

    .file-input-wrapper i {
        font-size: 2.2rem;
        color: #94a3b8;
        margin-bottom: 0.5rem;
        display: block;
    }

    .file-input-wrapper .file-label-text {
        font-size: 0.82rem;
        font-weight: 600;
        color: #475569;
    }

    .file-input-wrapper .file-selected-name {
        font-size: 0.82rem;
        font-weight: 700;
        color: #0d9488;
        display: none;
        margin-top: 0.5rem;
    }
</style>
@endpush

@section('content')

@php
    $hour = (int) date('H');
    $greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 18 ? 'Selamat Sore' : 'Selamat Malam'));
    $needsActionTotal = $kpi['draft'] + $kpi['revisi'];
@endphp

{{-- ============================================================
     1. WELCOME BANNER
     ============================================================ --}}
<div class="welcome-banner mb-4">
    <i class="bi bi-airplane welcome-illustration d-none d-md-block"></i>
    <div class="row align-items-center">
        <div class="col-md-8">
            <h3>{{ $greeting }}, {{ Auth::user()->name }} 👋</h3>
            <p>
                @if($needsActionTotal > 0)
                    Ada <strong>{{ $needsActionTotal }} tagihan perjalanan dinas (Perjaldin)</strong>
                    yang membutuhkan tindak lanjut atau perbaikan dari Anda hari ini.
                @else
                    Semua tagihan perjalanan dinas Anda dalam kondisi aman dan terverifikasi. Bagus sekali!
                @endif
            </p>
            <div class="badge-action">
                <span class="dot"></span>
                <span>{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</span>
            </div>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="{{ route('perjaldins.create') }}" class="btn btn-light fw-bold px-4 py-2 rounded-pill shadow-sm border-0">
                <i class="bi bi-plus-circle-fill text-primary me-1"></i> Buat Tagihan Perjaldin
            </a>
        </div>
    </div>
</div>

{{-- Flash messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm p-3 mb-4 d-flex align-items-center gap-3" role="alert">
        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink:0;">
            <i class="bi bi-check-lg"></i>
        </div>
        <div class="flex-fill">
            <strong class="text-success small d-block">Berhasil!</strong>
            <span class="small text-dark">{{ session('success') }}</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->has('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm p-3 mb-4 d-flex align-items-center gap-3" role="alert">
        <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink:0;">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <div class="flex-fill">
            <strong class="text-danger small d-block">Kesalahan!</strong>
            <span class="small text-dark">{{ $errors->first('error') }}</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- ============================================================
     2. KPI CARDS
     ============================================================ --}}
<div class="section-heading">
    <h6><i class="bi bi-grid-fill"></i> Ringkasan Kinerja Perjaldin</h6>
    <span class="text-muted small fw-semibold">Tahun Anggaran {{ $tahun }}</span>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-2 col-xxl-2" style="width: 20%; min-width: 200px;">
        <div class="kpi-card kpi-draft">
            <div class="kpi-icon"><i class="bi bi-pencil-square"></i></div>
            <div class="kpi-label">Tagihan Draft</div>
            <div class="kpi-value">{{ $kpi['draft'] }}</div>
            <div class="kpi-foot">
                <span class="kpi-pill tint-draft">
                    Rp {{ number_format($kpi['nominal_draft'], 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2 col-xxl-2" style="width: 20%; min-width: 200px;">
        <div class="kpi-card kpi-revisi">
            <div class="kpi-icon"><i class="bi bi-arrow-counterclockwise"></i></div>
            <div class="kpi-label">Perlu Revisi</div>
            <div class="kpi-value">{{ $kpi['revisi'] }}</div>
            <div class="kpi-foot">
                <span class="kpi-pill tint-revisi">
                    <i class="bi bi-exclamation-triangle me-1"></i> Dikembalikan
                </span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2 col-xxl-2" style="width: 20%; min-width: 200px;">
        <div class="kpi-card kpi-verifikasi">
            <div class="kpi-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="kpi-label">Verifikasi Aktif</div>
            <div class="kpi-value">{{ $kpi['verifikasi'] }}</div>
            <div class="kpi-foot">
                <span class="kpi-pill tint-verif">
                    Rp {{ number_format($kpi['nominal_verifikasi'], 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2 col-xxl-2" style="width: 20%; min-width: 200px;">
        <div class="kpi-card kpi-ttd">
            <div class="kpi-icon"><i class="bi bi-file-earmark-pdf"></i></div>
            <div class="kpi-label">Antrean TTD</div>
            <div class="kpi-value">{{ $kpi['menunggu_ttd'] }}</div>
            <div class="kpi-foot">
                <span class="kpi-pill tint-ttd">
                    <i class="bi bi-upload me-1"></i> Upload TTD
                </span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2 col-xxl-2" style="width: 20%; min-width: 200px;">
        <div class="kpi-card kpi-selesai">
            <div class="kpi-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="kpi-label">Selesai / Cair</div>
            <div class="kpi-value">{{ $kpi['selesai'] }}</div>
            <div class="kpi-foot">
                <span class="kpi-pill tint-success">
                    Rp {{ number_format($kpi['nominal_selesai'], 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     3. ANALYTICS CHARTS
     ============================================================ --}}
<div class="section-heading">
    <h6><i class="bi bi-graph-up"></i> Dashboard Analytics</h6>
</div>

<div class="row g-3 mb-4">
    {{-- Tren Bulanan --}}
    <div class="col-lg-7">
        <div class="panel h-fill">
            <div class="panel-head">
                <h6><i class="bi bi-bar-chart-line-fill"></i> Tren 6 Bulan Terakhir</h6>
                <span class="kpi-pill tint-verif fw-bold">{{ $kpi['tagihan_bulan_ini'] }} Tagihan Bulan Ini</span>
            </div>
            <div class="panel-body">
                <div style="height: 280px; position: relative;">
                    <canvas id="trenPerjaldinChart"></canvas>
                </div>
            </div>
            <div class="panel-foot d-flex justify-content-between align-items-center">
                <span>Total nominal terproses bulan ini:</span>
                <strong class="text-dark">Rp {{ number_format($kpi['nominal_bulan_ini'], 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>

    {{-- Distribusi Biaya Komponen + Quick Actions --}}
    <div class="col-lg-5 d-flex flex-column gap-3">
        <div class="panel flex-fill">
            <div class="panel-head">
                <h6><i class="bi bi-pie-chart-fill ph-success"></i> Distribusi Biaya Komponen</h6>
            </div>
            <div class="panel-body py-3 d-flex align-items-center justify-content-center">
                <div style="height: 200px; width: 100%; position: relative;">
                    <canvas id="komponenPerjaldinChart"></canvas>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h6><i class="bi bi-lightning-charge-fill ph-ttd"></i> Menu Aksi Cepat</h6>
            </div>
            <div class="panel-body d-flex flex-column gap-2 py-3">
                <a href="{{ route('perjaldins.create') }}" class="quick-action">
                    <span class="qa-icon" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                        <i class="bi bi-plus-lg"></i>
                    </span>
                    <div>
                        <p class="qa-title">Buat SPT & Tagihan</p>
                        <p class="qa-sub">Mulai pengajuan Perjaldin baru</p>
                    </div>
                    <i class="bi bi-arrow-right qa-arrow"></i>
                </a>
                
                <a href="{{ route('perjaldins.index') }}" class="quick-action">
                    <span class="qa-icon" style="background: rgba(13,148,136,0.1); color: #0d9488;">
                        <i class="bi bi-folder2-open"></i>
                    </span>
                    <div>
                        <p class="qa-title">Daftar Tagihan Perjaldin</p>
                        <p class="qa-sub">Lihat, kelola, dan tracking status</p>
                    </div>
                    <i class="bi bi-arrow-right qa-arrow"></i>
                </a>

                <a href="{{ route('master-uang-harian-perjaldin.index') }}" class="quick-action">
                    <span class="qa-icon" style="background: rgba(245,158,11,0.1); color: #d97706;">
                        <i class="bi bi-cash-coin"></i>
                    </span>
                    <div>
                        <p class="qa-title">Tarif Uang Harian</p>
                        <p class="qa-sub">Kelola besaran uang harian per provinsi</p>
                    </div>
                    <i class="bi bi-arrow-right qa-arrow"></i>
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     4. TABLE 1: PERLU TINDAKAN (DRAFT & REVISI)
     ============================================================ --}}
<div class="section-heading">
    <h6><i class="bi bi-bookmark-dash-fill"></i> Meja Kerja & Perbaikan Mandiri</h6>
</div>

<div class="panel mb-4">
    <div class="panel-head">
        <h6><i class="bi bi-inbox-fill ph-revisi"></i> Tagihan Butuh Tindakan Anda</h6>
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1 fw-bold">{{ $perlu_tindakan->count() }} Item</span>
    </div>
    <div class="table-responsive">
        <table class="table table-modern align-middle">
            <thead>
                <tr>
                    <th>Dokumen</th>
                    <th>Status</th>
                    <th>Nilai Bruto</th>
                    <th>Catatan Terakhir / Catatan Penolakan</th>
                    <th>Terakhir Diperbarui</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($perlu_tindakan as $t)
                    @php
                        $isDraft = $t->status === 'DRAFT';
                        $statusClass = $isDraft ? 'status-draft' : 'status-revisi';
                        $statusLabel = $isDraft ? 'Draft' : 'Perlu Revisi';

                        // Ambil log revisi terakhir
                        $lastRevisionLog = $t->logs
                            ->filter(fn($l) => str_starts_with((string) $l->status_baru, 'REVISI_') || str_starts_with((string) $l->status_baru, 'DITOLAK_'))
                            ->sortByDesc('created_at')
                            ->first();
                    @endphp
                    <tr>
                        <td>
                            <div class="doc-no">{{ $t->nomor_tagihan }}</div>
                            <div class="doc-desc text-truncate" style="max-width: 250px;">{{ $t->deskripsi }}</div>
                        </td>
                        <td><span class="status-pill {{ $statusClass }}">{{ $statusLabel }}</span></td>
                        <td><strong class="text-dark">Rp {{ number_format($t->total_bruto, 0, ',', '.') }}</strong></td>
                        <td>
                            @if($isDraft)
                                <span class="text-muted small font-italic"><i class="bi bi-info-circle me-1"></i>Tagihan belum diajukan ke workflow.</span>
                            @elseif($lastRevisionLog)
                                <div class="p-2 rounded bg-warning bg-opacity-10 border border-warning border-opacity-30 small" style="max-width: 320px;">
                                    <div class="fw-bold text-warning-emphasis mb-1">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        {{ $lastRevisionLog->role_saat_itu }} ({{ $lastRevisionLog->user?->name }})
                                    </div>
                                    <div class="text-dark">{{ $lastRevisionLog->catatan ?? '-' }}</div>
                                </div>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td><span class="text-muted small">{{ $t->updated_at->diffForHumans() }}</span></td>
                        <td class="text-center">
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <a href="{{ route('perjaldins.edit-perjaldin', $t->id) }}" class="btn btn-sm btn-primary fw-bold rounded-pill px-3">
                                    <i class="bi bi-pencil-square me-1"></i> {{ $isDraft ? 'Lengkapi' : 'Revisi' }}
                                </a>
                                @if($isDraft)
                                    <form action="{{ route('perjaldin.workflow.submit', $t->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin mengajukan tagihan ini ke workflow verifikasi?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success fw-bold rounded-pill px-3">
                                            <i class="bi bi-send me-1"></i> Ajukan
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="bi bi-clipboard2-check"></i>
                                <h6 class="text-muted mb-1">Semua Pekerjaan Selesai</h6>
                                <small class="text-muted">Tidak ada draft yang menggantung atau revisi yang dikembalikan ke meja Anda.</small>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ============================================================
     5. TABLE 2: ANTREAN UPLOAD NOMINATIF BERTANDA TANGAN (STATUS: MENUNGGU_UPLOAD_NOMINATIF_TTD)
     ============================================================ --}}
<div class="section-heading">
    <h6><i class="bi bi-file-earmark-check-fill"></i> Upload File Nominatif Bertanda Tangan</h6>
</div>

<div class="panel mb-4">
    <div class="panel-head">
        <h6><i class="bi bi-cloud-upload-fill ph-ttd"></i> Daftar Tagihan Menunggu TTD Basah</h6>
        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1 fw-bold">{{ $menunggu_ttd->count() }} Antrean</span>
    </div>
    <div class="table-responsive">
        <table class="table table-modern align-middle">
            <thead>
                <tr>
                    <th>Dokumen</th>
                    <th>Rincian Biaya</th>
                    <th>Unduh Draft PDF</th>
                    <th>Status Berkas TTD</th>
                    <th>Aksi Unggah</th>
                </tr>
            </thead>
            <tbody>
                @forelse($menunggu_ttd as $t)
                    @php
                        $hasNominatif = $t->arsipDokumen->where('jenis_dokumen', 'NOMINATIF_PERJALDIN_TTD')->where('is_active', true)->first();
                        $hasDaftarNom = $t->arsipDokumen->where('jenis_dokumen', 'DAFTAR_NOMINATIF_PEMBAYARAN_PERJALDIN_TTD')->where('is_active', true)->first();
                    @endphp
                    <tr>
                        <td>
                            <div class="doc-no">{{ $t->nomor_tagihan }}</div>
                            <div class="doc-desc text-truncate" style="max-width: 250px;">{{ $t->deskripsi }}</div>
                        </td>
                        <td>
                            <span class="fw-bold text-dark">Rp {{ number_format($t->total_bruto, 0, ',', '.') }}</span>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <a href="{{ route('perjaldins.pdf-nominatif', $t->id) }}" target="_blank" class="btn btn-xs btn-outline-danger fw-bold rounded-pill text-start py-1 px-2 small" style="font-size: 0.72rem; width: fit-content;">
                                    <i class="bi bi-file-pdf me-1"></i> PDF Nominatif
                                </a>
                                <a href="{{ route('perjaldins.pdf-lampiran', $t->id) }}" target="_blank" class="btn btn-xs btn-outline-danger fw-bold rounded-pill text-start py-1 px-2 small" style="font-size: 0.72rem; width: fit-content; margin-top: 2px;">
                                    <i class="bi bi-file-pdf me-1"></i> PDF Lampiran
                                </a>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $hasNominatif ? 'bg-success' : 'bg-danger' }} rounded-circle p-1 d-flex align-items-center justify-content-center" style="width: 16px; height: 16px;">
                                        <i class="bi {{ $hasNominatif ? 'bi-check' : 'bi-x' }} text-white" style="font-size: 0.65rem;"></i>
                                    </span>
                                    <span class="small text-dark font-weight-semibold">Nominatif Perjaldin</span>
                                    @if($hasNominatif)
                                        <a href="{{ route('perjaldins.view-nominatif-ttd', [$t->id, $hasNominatif->id]) }}" target="_blank" class="text-decoration-none small ms-1" style="font-size:0.75rem;"><i class="bi bi-eye"></i> Lihat</a>
                                    @endif
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="badge {{ $hasDaftarNom ? 'bg-success' : 'bg-danger' }} rounded-circle p-1 d-flex align-items-center justify-content-center" style="width: 16px; height: 16px;">
                                        <i class="bi {{ $hasDaftarNom ? 'bi-check' : 'bi-x' }} text-white" style="font-size: 0.65rem;"></i>
                                    </span>
                                    <span class="small text-dark font-weight-semibold">Daftar Nominatif Pembayaran</span>
                                    @if($hasDaftarNom)
                                        <a href="{{ route('perjaldins.view-nominatif-ttd', [$t->id, $hasDaftarNom->id]) }}" target="_blank" class="text-decoration-none small ms-1" style="font-size:0.75rem;"><i class="bi bi-eye"></i> Lihat</a>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-warning fw-bold rounded-pill px-3" data-bs-toggle="collapse" data-bs-target="#uploadCollapse-{{ $t->id }}" aria-expanded="false">
                                <i class="bi bi-upload me-1"></i> Unggah File
                            </button>
                        </td>
                    </tr>
                    
                    {{-- Kollapsable panel upload langsung --}}
                    <tr class="collapse-row">
                        <td colspan="5" class="p-0 border-0">
                            <div class="collapse" id="uploadCollapse-{{ $t->id }}">
                                <div class="upload-collapse-card m-3">
                                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-cloud-upload text-warning me-2"></i>Unggah Nominatif Bertanda Tangan ({{ $t->nomor_tagihan }})</h6>
                                    
                                    <form action="{{ route('perjaldins.upload-nominatif-ttd', $t->id) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="row g-3">
                                            <div class="col-md-5">
                                                <label class="form-label small fw-bold text-dark">Jenis Dokumen</label>
                                                <select name="jenis_dokumen" class="form-select rounded-3 border-slate py-2 small" required>
                                                    <option value="">-- Pilih Jenis Dokumen --</option>
                                                    <option value="NOMINATIF_PERJALDIN_TTD">NOMINATIF PERJALDIN (TTD)</option>
                                                    <option value="DAFTAR_NOMINATIF_PEMBAYARAN_PERJALDIN_TTD">DAFTAR NOMINATIF PEMBAYARAN (TTD)</option>
                                                </select>
                                                <div class="form-text small text-muted mt-2">Pilih jenis dokumen yang bertanda tangan basah dan di-scan ke format PDF/JPG/PNG.</div>
                                            </div>
                                            
                                            <div class="col-md-5">
                                                <label class="form-label small fw-bold text-dark">Berkas Dokumen</label>
                                                <div class="file-input-wrapper">
                                                    <i class="bi bi-file-earmark-arrow-up"></i>
                                                    <span class="file-label-text">Tarik berkas atau klik di sini</span>
                                                    <span class="file-selected-name"></span>
                                                    <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" onchange="fileSelected(this)" required>
                                                </div>
                                                <div class="form-text small text-muted mt-2">Maksimal 10 MB. Format: PDF, JPG, PNG.</div>
                                            </div>
                                            
                                            <div class="col-md-2 d-flex align-items-end justify-content-start">
                                                <button type="submit" class="btn btn-warning fw-bold text-white rounded-pill px-4 py-2 w-100 shadow-sm" style="height: 48px;">
                                                    <i class="bi bi-cloud-arrow-up-fill me-1"></i> Unggah
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="bi bi-file-earmark-check"></i>
                                <h6 class="text-muted mb-0">Tidak ada antrean upload tanda tangan</h6>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ============================================================
     6. TABLE 3: PELACAKAN PIPELINE VERIFIKASI AKTIF (PARALEL VISUAL TRACKER)
     ============================================================ --}}
<div class="section-heading">
    <h6><i class="bi bi-share-fill"></i> Pelacakan Pipeline Verifikasi Aktif</h6>
</div>

<div class="panel mb-4">
    <div class="panel-head">
        <h6><i class="bi bi-diagram-3-fill ph-verif"></i> Alur Verifikasi Paralel (5 Verifikator)</h6>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-bold">{{ $dalam_verifikasi->count() }} Pengajuan Aktif</span>
    </div>
    <div class="table-responsive">
        <table class="table table-modern align-middle">
            <thead>
                <tr>
                    <th>Dokumen</th>
                    <th>Nominal</th>
                    <th class="text-center">Pipeline Verifikasi Paralel (Selesai/Total)</th>
                    <th>Tahap Sekarang</th>
                    <th class="text-center">Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dalam_verifikasi as $t)
                    @php
                        // Ambil workflow active
                        $workflow = collect($t->workflowInstances ?? [])->sortByDesc('created_at')->first();
                        $approvals = collect($workflow?->approvals ?? []);

                        // 5 Roles definition
                        $rolesToCheck = [
                            'BENDAHARA_PENERIMAAN'  => ['label' => 'Bendahara Penerimaan',  'abbr' => 'BPn', 'icon' => 'bi-cash-stack'],
                            'BENDAHARA_PENGELUARAN' => ['label' => 'Bendahara Pengeluaran', 'abbr' => 'BPg', 'icon' => 'bi-wallet2'],
                            'PPK'                   => ['label' => 'PPK',                   'abbr' => 'PPK', 'icon' => 'bi-shield-check'],
                            'PPSPM'                 => ['label' => 'PPSPM',                 'abbr' => 'PSM', 'icon' => 'bi-file-earmark-check'],
                            'KOORDINATOR_KEUANGAN'  => ['label' => 'Koordinator Keuangan',  'abbr' => 'KKe', 'icon' => 'bi-graph-up-arrow'],
                        ];

                        $doneCount = 0;
                        $totalCount = count($rolesToCheck);

                        // Cari approval states
                        $nodesData = [];
                        foreach($rolesToCheck as $code => $cfg) {
                            $app = $approvals->firstWhere('role_code', $code);
                            $state = 'pending';
                            $userAct = '';

                            if ($app) {
                                $userAct = $app->actedByUser?->name ? ' (' . $app->actedByUser->name . ')' : '';
                                $state = match($app->status) {
                                    'APPROVED' => 'done',
                                    'REVISION' => 'revision',
                                    'REJECTED' => 'rejected',
                                    'PENDING'  => ($workflow && (int) $workflow->step_saat_ini === (int) $app->urutan_step) ? 'active' : 'pending',
                                    default    => 'pending',
                                };
                            }

                            if ($state === 'done') {
                                $doneCount++;
                            }

                            $nodesData[$code] = array_merge($cfg, [
                                'state' => $state,
                                'user'  => $userAct
                            ]);
                        }

                        // Kasubbag state (Step 2)
                        $kasubbag = $approvals->firstWhere('urutan_step', 2);
                        $kasubbagState = 'pending';
                        if ($kasubbag) {
                            $kasubbagState = match($kasubbag->status) {
                                'APPROVED' => 'done',
                                'REVISION' => 'revision',
                                'REJECTED' => 'rejected',
                                'PENDING'  => ($workflow && (int) $workflow->step_saat_ini === 2) ? 'active' : 'pending',
                                default    => 'pending',
                            };
                        }

                        $percentage = round(($doneCount / $totalCount) * 100);
                    @endphp
                    <tr>
                        <td>
                            <div class="doc-no">{{ $t->nomor_tagihan }}</div>
                            <div class="doc-desc text-truncate" style="max-width: 200px;">{{ $t->deskripsi }}</div>
                        </td>
                        <td>
                            <strong class="text-dark">Rp {{ number_format($t->total_bruto, 0, ',', '.') }}</strong>
                        </td>
                        <td>
                            <div class="d-flex flex-column align-items-center gap-2">
                                <div class="parallel-tracker-matrix justify-content-center">
                                    @foreach($nodesData as $code => $node)
                                        @php
                                            $stateLabel = match($node['state']) {
                                                'done'     => 'Selesai disetujui',
                                                'active'   => 'Menunggu verifikasi',
                                                'revision' => 'Meminta revisi',
                                                'rejected' => 'Menolak pengajuan',
                                                default    => 'Belum dimulai'
                                            };
                                            $tooltipText = $node['label'] . $node['user'] . ': ' . $stateLabel;
                                        @endphp
                                        <div class="tracker-node state-{{ $node['state'] }}" data-title="{{ $tooltipText }}">
                                            <i class="bi {{ $node['icon'] }}"></i>
                                        </div>
                                    @endforeach
                                    
                                    {{-- Separator arrow --}}
                                    <div class="mx-1 text-muted" style="font-size:0.75rem;"><i class="bi bi-chevron-right"></i></div>
                                    
                                    {{-- Kasubbag Node --}}
                                    @php
                                        $kStateLabel = match($kasubbagState) {
                                            'done'     => 'Selesai disetujui',
                                            'active'   => 'Menunggu verifikasi',
                                            'revision' => 'Meminta revisi',
                                            'rejected' => 'Menolak pengajuan',
                                            default    => 'Belum dimulai'
                                        };
                                        $kTooltip = 'Kasubbag Keuangan: ' . $kStateLabel;
                                    @endphp
                                    <div class="tracker-node state-{{ $kasubbagState }}" data-title="{{ $kTooltip }}">
                                        <i class="bi bi-patch-check-fill"></i>
                                    </div>
                                </div>
                                <div class="w-75">
                                    <div class="progress rounded-pill bg-light" style="height: 6px;">
                                        <div class="progress-bar rounded-pill bg-success" style="width: {{ $percentage }}%;"></div>
                                    </div>
                                    <div class="text-center mt-1" style="font-size:0.68rem; font-weight:700; color:#475569;">
                                        {{ $doneCount }}/{{ $totalCount }} Verifikator Paralel Selesai ({{ $percentage }}%)
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @php
                                $tahapClean = str_replace(['PENDING_', '_'], ['', ' '], $t->status);
                                $tahapClean = \Illuminate\Support\Str::title(strtolower($tahapClean));
                            @endphp
                            <span class="status-pill status-verifikasi">{{ $tahapClean }}</span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('perjaldins.show', $t->id) }}" class="btn btn-sm btn-outline-primary rounded-pill">
                                <i class="bi bi-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="bi bi-diagram-3"></i>
                                <h6 class="text-muted mb-0">Tidak ada pengajuan sedang diverifikasi secara aktif</h6>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Preview selected file name
    function fileSelected(input) {
        const nameSpan = input.parentNode.querySelector('.file-selected-name');
        const textSpan = input.parentNode.querySelector('.file-label-text');
        const icon = input.parentNode.querySelector('i');
        
        if (input.files && input.files.length > 0) {
            nameSpan.textContent = 'Berkas dipilih: ' + input.files[0].name;
            nameSpan.style.display = 'block';
            textSpan.style.display = 'none';
            icon.className = 'bi bi-file-earmark-check-fill text-success';
        } else {
            nameSpan.style.display = 'none';
            textSpan.style.display = 'block';
            icon.className = 'bi bi-file-earmark-arrow-up';
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        // Formatter helpers
        const fmtRupiah = v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v);
        const fmtCompact = v => {
            if (v >= 1e9) return 'Rp ' + (v / 1e9).toFixed(1) + ' M';
            if (v >= 1e6) return 'Rp ' + (v / 1e6).toFixed(1) + ' Jt';
            if (v >= 1e3) return 'Rp ' + (v / 1e3).toFixed(0) + ' rb';
            return 'Rp ' + v;
        };

        // ============================================================
        // 1. TREN BULANAN CHART
        // ============================================================
        const trenCtx = document.getElementById('trenPerjaldinChart');
        if (trenCtx) {
            const dataTren = {
                labels: @json($tren_labels),
                datasets: [
                    {
                        label: 'Nominal Total (Rp)',
                        data: @json($tren_nominal),
                        type: 'line',
                        borderColor: '#2563eb',
                        borderWidth: 3,
                        pointBackgroundColor: '#2563eb',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        tension: 0.35,
                        fill: false,
                        yAxisID: 'yNominal',
                    },
                    {
                        label: 'Jumlah Tagihan',
                        data: @json($tren_jumlah),
                        type: 'bar',
                        backgroundColor: 'rgba(13, 148, 136, 0.15)',
                        borderColor: '#0d9488',
                        borderWidth: 2,
                        borderRadius: 6,
                        yAxisID: 'yJumlah',
                    }
                ]
            };

            new Chart(trenCtx, {
                type: 'bar',
                data: dataTren,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                font: {
                                    family: 'Plus Jakarta Sans',
                                    weight: 600,
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            padding: 12,
                            backgroundColor: '#0f172a',
                            titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: 700 },
                            bodyFont: { family: 'Plus Jakarta Sans', size: 11 },
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.datasetIndex === 0) {
                                        label += fmtRupiah(context.parsed.y);
                                    } else {
                                        label += context.parsed.y + ' tagihan';
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { family: 'Plus Jakarta Sans', size: 10, weight: 600 } }
                        },
                        yNominal: {
                            type: 'linear',
                            position: 'left',
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                font: { family: 'Plus Jakarta Sans', size: 10 },
                                callback: function(value) {
                                    return fmtCompact(value);
                                }
                            }
                        },
                        yJumlah: {
                            type: 'linear',
                            position: 'right',
                            grid: { display: false },
                            ticks: {
                                stepSize: 1,
                                font: { family: 'Plus Jakarta Sans', size: 10 }
                            }
                        }
                    }
                }
            });
        }

        // ============================================================
        // 2. KOMPONEN BIAYA CHART
        // ============================================================
        const komponenCtx = document.getElementById('komponenPerjaldinChart');
        if (komponenCtx) {
            const dataKomponen = {
                labels: @json($komponen_labels),
                datasets: [{
                    data: @json($komponen_data),
                    backgroundColor: [
                        '#3b82f6', // Tiket Pesawat
                        '#0d9488', // Transportasi
                        '#f59e0b', // Penginapan
                        '#10b981'  // Uang Harian, Rapat, Representasi
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 12
                }]
            };

            new Chart(komponenCtx, {
                type: 'doughnut',
                data: dataKomponen,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 10,
                                padding: 15,
                                font: {
                                    family: 'Plus Jakarta Sans',
                                    weight: 600,
                                    size: 10
                                }
                            }
                        },
                        tooltip: {
                            padding: 12,
                            backgroundColor: '#0f172a',
                            titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: 700 },
                            bodyFont: { family: 'Plus Jakarta Sans', size: 11 },
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.label + ': ' + fmtRupiah(context.parsed);
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
