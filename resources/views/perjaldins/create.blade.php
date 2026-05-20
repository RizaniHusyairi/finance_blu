@extends('layouts.app')
@section('title')
    Tambah Data Perjaldin
@endsection

@push('css')
<style>
    body[data-bs-theme="blue-theme"] .main-content { background: #f6f7fb; }

    /* ============ HERO BANNER ============ */
    .perj-hero {
        background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 50%, #ec4899 100%);
        border-radius: 1.25rem;
        padding: 1.5rem 2rem;
        color: #fff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 14px 32px rgba(14, 165, 233, .25);
        margin-bottom: 1.25rem;
        animation: heroIn .6s cubic-bezier(.22,1,.36,1) both;
    }
    .perj-hero::before, .perj-hero::after {
        content: ''; position: absolute; border-radius: 50%;
    }
    .perj-hero::before {
        right: -90px; top: -90px;
        width: 280px; height: 280px;
        background: rgba(255,255,255,.12);
    }
    .perj-hero::after {
        right: 60px; bottom: -70px;
        width: 180px; height: 180px;
        background: rgba(255,255,255,.07);
    }
    .perj-hero > * { position: relative; z-index: 1; }
    .perj-hero h2 {
        color: #fff !important;
        font-weight: 800;
        font-size: 1.6rem;
        margin: 0 0 .35rem;
        letter-spacing: -.01em;
        text-shadow: 0 1px 2px rgba(0,0,0,.15);
    }
    .perj-hero p { color: rgba(255,255,255,.92) !important; margin: 0; }
    .perj-hero .plane-illust {
        position: absolute;
        right: 1.5rem; top: 50%;
        transform: translateY(-50%) rotate(-15deg);
        font-size: 8rem;
        opacity: .14;
    }
    .perj-hero .hero-tag {
        display: inline-flex; align-items: center; gap: .45rem;
        background: rgba(255,255,255,.18);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.30);
        padding: .35rem .85rem;
        border-radius: 999px;
        font-size: .75rem; font-weight: 600;
        margin-bottom: .55rem;
        color: #fff !important;
    }
    .btn-back-perj {
        background: rgba(255,255,255,.18);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.30);
        color: #fff;
        font-weight: 600;
        padding: .55rem 1.05rem;
        border-radius: 999px;
        transition: all .2s ease;
        font-size: .82rem;
        text-decoration: none;
        display: inline-flex; align-items: center; gap: .35rem;
    }
    .btn-back-perj:hover {
        background: rgba(255,255,255,.30);
        color: #fff;
        transform: translateX(-3px);
    }

    /* ============ STEPPER (sticky) ============ */
    .form-stepper {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        position: sticky;
        top: 70px;
        z-index: 30;
        box-shadow: 0 4px 12px rgba(15,23,42,.05);
        animation: heroIn .65s cubic-bezier(.22,1,.36,1) .1s both;
    }
    .stepper-progress-wrap {
        height: 6px;
        background: #f1f5f9;
        border-radius: 999px;
        overflow: hidden;
        margin-bottom: .85rem;
    }
    .stepper-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #0ea5e9, #6366f1, #ec4899);
        background-size: 200% 100%;
        border-radius: 999px;
        transition: width .5s cubic-bezier(.22,1,.36,1);
        animation: shimmerSlide 3s linear infinite;
        width: 0%;
    }
    @keyframes shimmerSlide { 0% { background-position: 0% 0; } 100% { background-position: 200% 0; } }

    .stepper-list {
        display: flex; gap: 1rem;
        align-items: center; flex-wrap: wrap;
    }
    .stepper-item {
        display: inline-flex; align-items: center; gap: .55rem;
        font-size: .82rem; color: #94a3b8; font-weight: 600;
        cursor: pointer;
        transition: color .2s ease;
    }
    .stepper-item:hover { color: #475569; }
    .stepper-item.done { color: #10b981; }
    .stepper-item.active { color: #4f46e5; }
    .stepper-item .dot {
        width: 28px; height: 28px;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        background: #f1f5f9; color: #94a3b8;
        font-size: .85rem;
        transition: all .25s ease;
    }
    .stepper-item.done .dot {
        background: linear-gradient(135deg, #34d399, #10b981);
        color: #fff;
        box-shadow: 0 4px 10px rgba(16,185,129,.30);
    }
    .stepper-item.active .dot {
        background: linear-gradient(135deg, #818cf8, #6366f1);
        color: #fff;
        box-shadow: 0 4px 10px rgba(99,102,241,.40);
        animation: pulseDot 1.6s ease-in-out infinite;
    }
    @keyframes pulseDot {
        0%, 100% { box-shadow: 0 0 0 0 rgba(99,102,241,.45); }
        50%      { box-shadow: 0 0 0 8px rgba(99,102,241,0); }
    }

    /* ============ SECTION CARD ============ */
    .sec-card {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        margin-bottom: 1.25rem;
        overflow: hidden;
        animation: secIn .55s cubic-bezier(.22,1,.36,1) both;
        transition: box-shadow .25s ease, border-color .25s ease;
    }
    .sec-card:hover { box-shadow: 0 14px 32px rgba(15,23,42,.06); }
    .sec-card.is-active {
        border-color: rgba(99,102,241,.35);
        box-shadow: 0 0 0 4px rgba(99,102,241,.08);
    }
    .sec-card[data-section="dokumen"]    { animation-delay: .15s; }
    .sec-card[data-section="verifikator"]{ animation-delay: .25s; }
    .sec-card[data-section="peserta"]    { animation-delay: .35s; }
    .sec-card[data-section="coa"]        { animation-delay: .45s; }

    .sec-head {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #f1f3f7;
        display: flex; align-items: center; justify-content: space-between;
        gap: 1rem;
        background: linear-gradient(180deg, #fafbff 0%, #ffffff 100%);
    }
    .sec-head .head-left { display: flex; align-items: center; gap: .85rem; }
    .sec-head .sec-icon {
        width: 44px; height: 44px;
        border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.2rem; color: #fff;
        flex-shrink: 0;
        background: var(--icon-bg, linear-gradient(135deg, #818cf8, #6366f1));
        box-shadow: 0 6px 14px var(--icon-shadow, rgba(99,102,241,.30));
        transition: transform .3s ease;
    }
    .sec-card:hover .sec-icon { transform: rotate(-6deg) scale(1.06); }
    .sec-head h6 {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -.01em;
    }
    .sec-head small {
        font-size: .78rem;
        color: #64748b;
        display: block;
        margin-top: .15rem;
    }
    .sec-letter {
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .12em;
        color: #94a3b8;
        text-transform: uppercase;
        background: #f1f5f9;
        padding: .2rem .55rem;
        border-radius: 999px;
    }
    .sec-body { padding: 1.25rem 1.5rem; }

    .icon-info    { --icon-bg: linear-gradient(135deg, #38bdf8, #0ea5e9); --icon-shadow: rgba(14,165,233,.35); }
    .icon-primary { --icon-bg: linear-gradient(135deg, #818cf8, #6366f1); --icon-shadow: rgba(99,102,241,.35); }
    .icon-success { --icon-bg: linear-gradient(135deg, #34d399, #10b981); --icon-shadow: rgba(16,185,129,.35); }
    .icon-warning { --icon-bg: linear-gradient(135deg, #fbbf24, #f59e0b); --icon-shadow: rgba(245,158,11,.35); }

    /* ============ MODERN INPUTS ============ */
    .form-label.modern,
    .form-label-modern {
        font-size: .78rem;
        font-weight: 700;
        color: #475569;
        letter-spacing: .02em;
        margin-bottom: .4rem;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .form-control.modern,
    .form-select.modern {
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        border-radius: .65rem;
        padding: .6rem .85rem;
        font-size: .9rem;
        transition: all .2s ease;
    }
    .form-control.modern:hover,
    .form-select.modern:hover {
        border-color: #cbd5e1;
        background: #fff;
    }
    .form-control.modern:focus,
    .form-select.modern:focus {
        outline: 0;
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(99,102,241,.12);
        transform: translateY(-1px);
    }

    /* ============ ANIMATIONS ============ */
    @keyframes heroIn {
        from { opacity: 0; transform: translateY(-12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes secIn {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>

<style>
    /* ============ VERIFIKATOR CARD ============ */
    .verif-mini {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: .85rem;
        padding: .9rem 1rem;
        position: relative;
        overflow: hidden;
        transition: all .25s ease;
        height: 100%;
    }
    .verif-mini::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 3px;
        background: var(--vm-color, #cbd5e1);
        transition: all .25s ease;
    }
    .verif-mini:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(15,23,42,.06);
        border-color: var(--vm-color, #cbd5e1);
    }
    .verif-mini.is-filled::before { height: 4px; background: var(--vm-color); }
    .verif-mini .vm-head {
        display: flex; align-items: center; gap: .6rem;
        margin-bottom: .85rem;
    }
    .verif-mini .vm-icon {
        width: 32px; height: 32px;
        border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--vm-soft, rgba(99,102,241,.10));
        color: var(--vm-color, #4f46e5);
        font-size: .9rem;
    }
    .verif-mini .vm-title {
        font-weight: 700;
        font-size: .82rem;
        color: #0f172a;
        margin: 0;
    }
    .verif-mini .vm-auto-pill {
        font-size: .65rem;
        font-weight: 700;
        padding: .15rem .5rem;
        border-radius: 999px;
        background: rgba(100,116,139,.12);
        color: #475569;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-left: auto;
    }
    .verif-mini.is-auto {
        background: linear-gradient(180deg, rgba(99,102,241,.04) 0%, #fff 100%);
        border-color: rgba(99,102,241,.18);
        border-style: dashed;
    }
    .verif-mini .field-mini { margin-bottom: .45rem; }
    .verif-mini .field-mini:last-child { margin-bottom: 0; }
    .verif-mini .field-mini label {
        font-size: .7rem;
        font-weight: 700;
        color: #64748b;
        margin-bottom: .25rem;
        display: block;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .verif-mini .field-mini .form-control,
    .verif-mini .field-mini .form-select {
        border: 1px solid #e2e8f0;
        background: #fff;
        border-radius: .55rem;
        padding: .45rem .7rem;
        font-size: .82rem;
        transition: all .2s ease;
    }
    .verif-mini .field-mini .form-control:focus,
    .verif-mini .field-mini .form-select:focus {
        outline: 0;
        border-color: var(--vm-color, #6366f1);
        box-shadow: 0 0 0 3px rgba(99,102,241,.10);
    }
    .verif-mini .field-mini .form-control[readonly] {
        background: #f1f5f9;
        color: #64748b;
    }

    /* color variants for verifikator-mini */
    .vm-primary { --vm-color: #6366f1; --vm-soft: rgba(99,102,241,.12); }
    .vm-info    { --vm-color: #0ea5e9; --vm-soft: rgba(14,165,233,.12); }
    .vm-success { --vm-color: #10b981; --vm-soft: rgba(16,185,129,.12); }
    .vm-warning { --vm-color: #f59e0b; --vm-soft: rgba(245,158,11,.12); }
    .vm-danger  { --vm-color: #ef4444; --vm-soft: rgba(239,68,68,.12); }
    .vm-violet  { --vm-color: #8b5cf6; --vm-soft: rgba(139,92,246,.12); }

    /* ============ PESERTA SUMMARY BAR ============ */
    .peserta-summary {
        background: linear-gradient(135deg, rgba(14,165,233,.10), rgba(99,102,241,.06));
        border: 1px solid rgba(99,102,241,.20);
        border-radius: .85rem;
        padding: .85rem 1.15rem;
        margin-bottom: 1rem;
        display: flex; align-items: center; gap: 1rem;
        flex-wrap: wrap;
    }
    .peserta-summary .ps-icon {
        width: 44px; height: 44px;
        border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #38bdf8, #6366f1);
        color: #fff;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .peserta-summary .ps-text { font-size: .85rem; color: #475569; }
    .peserta-summary .ps-text strong { color: #0f172a; }
    .peserta-summary .ps-grand {
        margin-left: auto;
        text-align: right;
    }
    .peserta-summary .ps-grand .ps-label {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #64748b;
    }
    .peserta-summary .ps-grand .ps-value {
        font-size: 1.35rem;
        font-weight: 800;
        background: linear-gradient(135deg, #4f46e5, #ec4899);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        font-variant-numeric: tabular-nums;
        line-height: 1;
        margin-top: .2rem;
        transition: transform .2s ease;
    }
    .peserta-summary .ps-value.flash {
        animation: flashValue .55s ease;
    }
    @keyframes flashValue {
        0%   { transform: scale(1); }
        50%  { transform: scale(1.06); }
        100% { transform: scale(1); }
    }

    .btn-add-peserta {
        background: linear-gradient(135deg, #10b981, #059669);
        border: 0;
        color: #fff;
        font-weight: 600;
        padding: .55rem 1.05rem;
        border-radius: .65rem;
        font-size: .82rem;
        box-shadow: 0 6px 14px rgba(16,185,129,.30);
        transition: all .2s ease;
        display: inline-flex; align-items: center; gap: .35rem;
    }
    .btn-add-peserta:hover {
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 10px 22px rgba(16,185,129,.40);
    }

    /* ============ COA ROWS ============ */
    .komponen-coa-empty {
        background: linear-gradient(135deg, rgba(245,158,11,.08), rgba(217,119,6,.04));
        border: 1px dashed rgba(245,158,11,.30);
        border-radius: .85rem;
        padding: 1.25rem;
        color: #b45309;
        text-align: center;
        font-size: .85rem;
    }
    .komponen-coa-empty i {
        color: #f59e0b;
        font-size: 1.5rem;
        display: block;
        margin-bottom: .35rem;
    }
    .komponen-coa-row {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: .85rem;
        margin-bottom: .65rem;
        padding: 1rem 1.1rem;
        transition: all .2s ease;
        animation: slideRight .35s cubic-bezier(.22,1,.36,1) both;
    }
    @keyframes slideRight {
        from { opacity: 0; transform: translateX(-8px); }
        to   { opacity: 1; transform: translateX(0); }
    }
    .komponen-coa-row:hover {
        border-color: #c7d2fe;
        box-shadow: 0 8px 18px rgba(99,102,241,.08);
    }
    .komponen-coa-row .kr-icon {
        width: 42px; height: 42px;
        border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
        background: linear-gradient(135deg, rgba(99,102,241,.15), rgba(99,102,241,.05));
        color: #4f46e5;
        flex-shrink: 0;
    }
    .komponen-coa-row .kr-label {
        font-weight: 700;
        font-size: .9rem;
        color: #1e293b;
        margin: 0;
    }
    .komponen-coa-row .kr-meta {
        font-size: .72rem;
        color: #94a3b8;
        margin-top: .15rem;
    }
    .komponen-coa-row .kr-total-pill {
        display: inline-block;
        background: rgba(16,185,129,.10);
        color: #047857;
        font-weight: 700;
        padding: .25rem .75rem;
        border-radius: 999px;
        font-size: .82rem;
        font-variant-numeric: tabular-nums;
    }
    .komponen-coa-row .form-select {
        border: 1px solid #e2e8f0;
        background: #fff;
        border-radius: .55rem;
        padding: .5rem .75rem;
        font-size: .82rem;
    }
    .komponen-coa-row .form-select.is-invalid {
        border-color: #f43f5e;
        box-shadow: 0 0 0 3px rgba(244,63,94,.10);
    }
    .komponen-coa-warning {
        background: rgba(244,63,94,.08);
        border-left: 3px solid #f43f5e;
        padding: .5rem .65rem;
        border-radius: .45rem;
        margin-top: .5rem;
        color: #991b1b;
        font-size: .73rem;
        display: flex; align-items: center; gap: .35rem;
    }

    /* ============ STICKY SUBMIT BAR ============ */
    .submit-bar-perj {
        background: rgba(255,255,255,.92);
        backdrop-filter: blur(10px);
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        display: flex;
        gap: .75rem;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        box-shadow: 0 12px 28px rgba(15,23,42,.08);
        position: sticky;
        bottom: 1rem;
        z-index: 20;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        animation: secIn .65s cubic-bezier(.22,1,.36,1) .55s both;
    }
    .submit-bar-perj .sb-status {
        display: flex; align-items: center; gap: .85rem;
    }
    .submit-bar-perj .sb-status-icon {
        width: 48px; height: 48px;
        border-radius: 14px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.3rem; color: #fff;
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        box-shadow: 0 6px 14px rgba(245,158,11,.30);
    }
    .submit-bar-perj.is-ready .sb-status-icon {
        background: linear-gradient(135deg, #34d399, #10b981);
        box-shadow: 0 6px 14px rgba(16,185,129,.30);
        animation: pulseReady 1.8s ease-in-out infinite;
    }
    @keyframes pulseReady {
        0%, 100% { box-shadow: 0 0 0 0 rgba(16,185,129,.35), 0 6px 14px rgba(16,185,129,.30); }
        50%      { box-shadow: 0 0 0 10px rgba(16,185,129,0), 0 6px 14px rgba(16,185,129,.30); }
    }
    .btn-cancel-perj {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-weight: 600;
        padding: .7rem 1.4rem;
        border-radius: .7rem;
        font-size: .9rem;
        transition: all .2s ease;
        text-decoration: none;
    }
    .btn-cancel-perj:hover {
        background: #e2e8f0;
        color: #1e293b;
        transform: translateY(-1px);
    }
    .btn-submit-perj {
        background: linear-gradient(135deg, #0ea5e9, #6366f1, #ec4899);
        background-size: 200% 100%;
        background-position: 0% 0%;
        border: 0;
        color: #fff;
        font-weight: 700;
        padding: .7rem 1.5rem;
        border-radius: .7rem;
        font-size: .9rem;
        box-shadow: 0 8px 22px rgba(99,102,241,.35);
        transition: all .35s ease;
        display: inline-flex; align-items: center; gap: .5rem;
    }
    .btn-submit-perj:hover {
        background-position: 100% 0%;
        transform: translateY(-2px);
        box-shadow: 0 14px 28px rgba(99,102,241,.45);
        color: #fff;
    }

    /* ============ ALERT ERROR ============ */
    .alert-modern-error {
        background: linear-gradient(135deg, rgba(244,63,94,.06), rgba(220,38,38,.04));
        border: 1px solid rgba(244,63,94,.20);
        border-left: 4px solid #f43f5e;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        color: #991b1b;
        margin-bottom: 1.25rem;
        animation: shake .5s cubic-bezier(.36,.07,.19,.97) both;
    }
    .alert-modern-error .alert-title {
        font-weight: 800;
        color: #b91c1c;
        display: flex; align-items: center; gap: .5rem;
        margin-bottom: .5rem;
    }
    .alert-modern-error ul { margin: 0; padding-left: 1.5rem; font-size: .85rem; }
    @keyframes shake {
        10%, 90% { transform: translateX(-1px); }
        20%, 80% { transform: translateX(2px); }
        30%, 50%, 70% { transform: translateX(-3px); }
        40%, 60% { transform: translateX(3px); }
    }

    /* ============ Select2 polish ============ */
    .select2-container--bootstrap-5 .select2-selection {
        border: 1px solid #e2e8f0 !important;
        background: #fff !important;
        border-radius: .55rem !important;
        min-height: 38px !important;
        transition: all .2s ease !important;
    }
    .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    .select2-container--bootstrap-5.select2-container--open .select2-selection {
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 3px rgba(99,102,241,.12) !important;
    }

    @media (max-width: 991px) {
        .form-stepper { position: static; }
        .submit-bar-perj { position: static; }
    }
</style>
@endpush


@section('content')
<x-page-title title="Manajemen Perjaldin" subtitle="Tambah Data" />

{{-- HERO --}}
<div class="perj-hero">
    <i class="bi bi-airplane-engines plane-illust d-none d-md-block"></i>
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div class="flex-grow-1">
            <span class="hero-tag"><i class="bi bi-stars"></i> Form Pengajuan Baru</span>
            <h2><i class="bi bi-airplane me-2"></i>Perjalanan Dinas</h2>
            <p>Lengkapi data dokumen, verifikator, daftar nominatif peserta, dan pilih COA per komponen biaya untuk pengajuan perjaldin.</p>
        </div>
        <a href="{{ route('perjaldins.index') }}" class="btn-back-perj">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>
</div>

{{-- STEPPER --}}
<div class="form-stepper" id="formStepper">
    <div class="stepper-progress-wrap">
        <div class="stepper-progress-fill" id="progressFill"></div>
    </div>
    <div class="stepper-list" id="stepperList">
        <div class="stepper-item" data-step="dokumen">
            <span class="dot"><i class="bi bi-file-earmark-text"></i></span>
            <span>Dokumen</span>
        </div>
        <i class="bi bi-chevron-right text-muted small"></i>
        <div class="stepper-item" data-step="verifikator">
            <span class="dot"><i class="bi bi-person-check"></i></span>
            <span>Verifikator</span>
        </div>
        <i class="bi bi-chevron-right text-muted small"></i>
        <div class="stepper-item" data-step="peserta">
            <span class="dot"><i class="bi bi-people"></i></span>
            <span>Peserta</span>
        </div>
        <i class="bi bi-chevron-right text-muted small"></i>
        <div class="stepper-item" data-step="coa">
            <span class="dot"><i class="bi bi-bank"></i></span>
            <span>COA</span>
        </div>
        <span class="ms-auto small fw-bold text-muted" id="progressPct">0% lengkap</span>
    </div>
</div>

{{-- VALIDATION ERRORS --}}
@if ($errors->any())
    <div class="alert-modern-error">
        <div class="alert-title">
            <i class="bi bi-exclamation-octagon-fill"></i>
            Terdapat kesalahan pada formulir
        </div>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('perjaldins.store') }}" method="POST" enctype="multipart/form-data" id="perjaldinForm">
    @csrf

    {{-- ============ A: DOKUMEN ============ --}}
    <div class="sec-card" data-section="dokumen">
        <div class="sec-head">
            <div class="head-left">
                <span class="sec-icon icon-info"><i class="bi bi-file-earmark-text-fill"></i></span>
                <div>
                    <h6>Informasi Dokumen & Anggaran</h6>
                    <small>Header dokumen perjalanan dinas dan periode</small>
                </div>
            </div>
            <span class="sec-letter">Step A</span>
        </div>
        <div class="sec-body">
            <div class="row g-3">
                <div class="col-md-6 col-lg-3">
                    <label class="form-label-modern">Uraian / Judul Perjalanan <span class="text-danger">*</span></label>
                    <input type="text" name="deskripsi" id="inp_deskripsi" class="form-control modern" placeholder="Contoh: Rapat Koordinasi Anggaran..." required value="{{ old('deskripsi') }}">
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label-modern">Nomor Perjalanan Dinas <span class="text-danger">*</span></label>
                    <input type="text" name="nomor_perjaldin" id="inp_nomor" class="form-control modern" placeholder="KU.201/1245/APTP/2026" required value="{{ old('nomor_perjaldin') }}">
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label-modern">Periode Bulan <span class="text-danger">*</span></label>
                    <select name="periode_bulan" id="inp_bulan" class="form-select modern" required>
                        <option value="">-- Pilih Bulan --</option>
                        @for($i=1; $i<=12; $i++)
                            <option value="{{ $i }}" {{ old('periode_bulan', date('n')) == $i ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $i, 10)) }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label-modern">Periode Tahun <span class="text-danger">*</span></label>
                    <input type="number" name="periode_tahun" id="inp_tahun" class="form-control modern" required value="{{ old('periode_tahun', date('Y')) }}" min="2000" max="2100">
                </div>

                <div class="col-md-6 col-lg-3">
                    <label class="form-label-modern">Kota TTD <span class="text-danger">*</span></label>
                    <input type="text" name="kota_ttd" id="inp_kota" class="form-control modern" placeholder="Samarinda" required value="{{ old('kota_ttd', 'Samarinda') }}">
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label-modern">Tanggal TTD <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_ttd" id="inp_tanggal" class="form-control modern" required value="{{ old('tanggal_ttd', date('Y-m-d')) }}">
                </div>
                <div class="col-md-12 col-lg-6">
                    <label class="form-label-modern"><i class="bi bi-credit-card-2-front"></i> Mekanisme Pembayaran <span class="text-danger">*</span></label>
                    <select name="mekanisme_pembayaran" class="form-select modern" required>
                        @foreach(\App\Enums\MekanismePembayaran::optionsFor('PERJALDIN') as $val => $lbl)
                            <option value="{{ $val }}" {{ old('mekanisme_pembayaran', \App\Enums\MekanismePembayaran::defaultFor('PERJALDIN')->value) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted small mt-1 d-block"><i class="bi bi-info-circle me-1"></i>LS - Pihak Ketiga: ditransfer langsung ke rekening peserta. LS - Via Bendahara: diteruskan melalui Bendahara Pengeluaran.</small>
                </div>
            </div>
        </div>
    </div>


    {{-- ============ B: VERIFIKATOR ============ --}}
    @php
        $kasubbagId = old('kasubbag_user_id', optional($kasubbagUser)->id);
        $kasubbagNama = old('kasubbag_nama_snapshot', optional($kasubbagUser)->name);
        $kasubbagNip = old('kasubbag_nip_snapshot', optional(optional($kasubbagUser)->pegawai)->nip);
    @endphp

    <div class="sec-card" data-section="verifikator">
        <div class="sec-head">
            <div class="head-left">
                <span class="sec-icon icon-primary"><i class="bi bi-pen-fill"></i></span>
                <div>
                    <h6>Verifikator Dokumen</h6>
                    <small>Pejabat yang akan memverifikasi & menandatangani</small>
                </div>
            </div>
            <span class="sec-letter">Step B</span>
        </div>
        <div class="sec-body">
            <div class="row g-3">
                {{-- PPK --}}
                <div class="col-md-6 col-lg-4">
                    <div class="verif-mini vm-primary">
                        <div class="vm-head">
                            <span class="vm-icon"><i class="bi bi-person-badge"></i></span>
                            <p class="vm-title">PPK</p>
                        </div>
                        <div class="field-mini">
                            <label>Pilih User</label>
                            <select name="ppk_user_id" class="form-select select2" id="ppkUserId">
                                <option value="">-- Pilih --</option>
                                @foreach($ppkUsers as $user)
                                    <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('ppk_user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="ppk_nama_snapshot" id="ppkNamaSnapshot" value="{{ old('ppk_nama_snapshot') }}">
                        <div class="field-mini">
                            <label>NIP <span class="text-danger">*</span></label>
                            <input type="text" name="ppk_nip_snapshot" id="ppkNipSnapshot" class="form-control" required value="{{ old('ppk_nip_snapshot') }}">
                        </div>
                    </div>
                </div>

                {{-- PPSPM --}}
                <div class="col-md-6 col-lg-4">
                    <div class="verif-mini vm-success">
                        <div class="vm-head">
                            <span class="vm-icon"><i class="bi bi-person-check"></i></span>
                            <p class="vm-title">PPSPM</p>
                        </div>
                        <div class="field-mini">
                            <label>Pilih User <span class="text-danger">*</span></label>
                            <select name="ppspm_user_id" class="form-select select2" id="ppspmUserId" required>
                                <option value="">-- Pilih --</option>
                                @foreach($ppspmUsers as $user)
                                    <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('ppspm_user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="ppspm_nama_snapshot" id="ppspmNamaSnapshot" value="{{ old('ppspm_nama_snapshot') }}">
                        <div class="field-mini">
                            <label>NIP</label>
                            <input type="text" name="ppspm_nip_snapshot" id="ppspmNipSnapshot" class="form-control" value="{{ old('ppspm_nip_snapshot') }}">
                        </div>
                    </div>
                </div>

                {{-- Koordinator Keuangan --}}
                <div class="col-md-6 col-lg-4">
                    <div class="verif-mini vm-info">
                        <div class="vm-head">
                            <span class="vm-icon"><i class="bi bi-clipboard-check"></i></span>
                            <p class="vm-title">Koordinator Keuangan</p>
                        </div>
                        <div class="field-mini">
                            <label>Pilih User <span class="text-danger">*</span></label>
                            <select name="koordinator_keuangan_user_id" class="form-select select2" id="koorKeuanganUserId" required>
                                <option value="">-- Pilih --</option>
                                @foreach($koorKeuanganUsers as $user)
                                    <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('koordinator_keuangan_user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="koordinator_keuangan_nama_snapshot" id="koorKeuanganNamaSnapshot" value="{{ old('koordinator_keuangan_nama_snapshot') }}">
                        <div class="field-mini">
                            <label>NIP</label>
                            <input type="text" name="koordinator_keuangan_nip_snapshot" id="koorKeuanganNipSnapshot" class="form-control" value="{{ old('koordinator_keuangan_nip_snapshot') }}">
                        </div>
                    </div>
                </div>

                {{-- Bendahara Penerimaan --}}
                <div class="col-md-6 col-lg-4">
                    <div class="verif-mini vm-warning">
                        <div class="vm-head">
                            <span class="vm-icon"><i class="bi bi-wallet2"></i></span>
                            <p class="vm-title">Bendahara Penerimaan</p>
                        </div>
                        <div class="field-mini">
                            <label>Pilih User <span class="text-danger">*</span></label>
                            <select name="bendahara_penerimaan_user_id" class="form-select select2" id="bendaharaPenerimaanUserId" required>
                                <option value="">-- Pilih --</option>
                                @foreach($bendaharaPenerimaanUsers as $user)
                                    <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('bendahara_penerimaan_user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="bendahara_penerimaan_nama_snapshot" id="bendaharaPenerimaanNamaSnapshot" value="{{ old('bendahara_penerimaan_nama_snapshot') }}">
                        <div class="field-mini">
                            <label>NIP</label>
                            <input type="text" name="bendahara_penerimaan_nip_snapshot" id="bendaharaPenerimaanNipSnapshot" class="form-control" value="{{ old('bendahara_penerimaan_nip_snapshot') }}">
                        </div>
                    </div>
                </div>

                {{-- Bendahara Pengeluaran --}}
                <div class="col-md-6 col-lg-4">
                    <div class="verif-mini vm-danger">
                        <div class="vm-head">
                            <span class="vm-icon"><i class="bi bi-cash-stack"></i></span>
                            <p class="vm-title">Bendahara Pengeluaran</p>
                        </div>
                        <div class="field-mini">
                            <label>Pilih User <span class="text-danger">*</span></label>
                            <select name="bendahara_pengeluaran_user_id" class="form-select select2" id="bendaharaUserId" required>
                                <option value="">-- Pilih --</option>
                                @foreach($bendaharaUsers as $user)
                                    <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('bendahara_pengeluaran_user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="bendahara_pengeluaran_nama_snapshot" id="bendaharaNamaSnapshot" value="{{ old('bendahara_pengeluaran_nama_snapshot') }}">
                        <div class="field-mini">
                            <label>NIP <span class="text-danger">*</span></label>
                            <input type="text" name="bendahara_pengeluaran_nip_snapshot" id="bendaharaNipSnapshot" class="form-control" required value="{{ old('bendahara_pengeluaran_nip_snapshot') }}">
                        </div>
                    </div>
                </div>

                {{-- Kasubbag (Auto) --}}
                <div class="col-md-6 col-lg-4">
                    <div class="verif-mini vm-violet is-auto {{ $kasubbagId ? 'is-filled' : '' }}">
                        <div class="vm-head">
                            <span class="vm-icon"><i class="bi bi-person-gear"></i></span>
                            <p class="vm-title">Kasubbag</p>
                            <span class="vm-auto-pill"><i class="bi bi-magic"></i> Otomatis</span>
                        </div>
                        <input type="hidden" name="kasubbag_user_id" value="{{ $kasubbagId }}">
                        <div class="field-mini">
                            <label>User Kasubbag</label>
                            <input type="text" class="form-control" readonly value="{{ $kasubbagNama ?: 'Belum ada user Kasubbag' }}">
                            <input type="hidden" name="kasubbag_nama_snapshot" value="{{ $kasubbagNama }}">
                        </div>
                        <div class="field-mini">
                            <label>NIP Kasubbag</label>
                            <input type="text" class="form-control" readonly value="{{ $kasubbagNip ?: '-' }}">
                            <input type="hidden" name="kasubbag_nip_snapshot" value="{{ $kasubbagNip }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ C: PESERTA ============ --}}
    <div class="sec-card" data-section="peserta">
        <div class="sec-head">
            <div class="head-left">
                <span class="sec-icon icon-success"><i class="bi bi-people-fill"></i></span>
                <div>
                    <h6>Daftar Nominatif Peserta</h6>
                    <small>Tambahkan peserta perjalanan dinas dan rincian biaya</small>
                </div>
            </div>
            <button type="button" class="btn-add-peserta btn-add-row-trigger">
                <i class="bi bi-plus-lg"></i> Tambah Peserta
            </button>
        </div>
        <div class="sec-body">
            <div class="peserta-summary">
                <div class="ps-icon"><i class="bi bi-people-fill"></i></div>
                <div class="ps-text">
                    <strong id="summaryCount">1</strong> peserta terdaftar dalam pengajuan ini
                </div>
                <div class="ps-grand">
                    <div class="ps-label">Grand Total</div>
                    <div class="ps-value" id="summaryGrandTotal">Rp 0</div>
                </div>
                <input type="hidden" id="grandTotal" name="total_bruto" value="0">
            </div>

            <div id="pesertaRepeater">
                @php
                    $oldPeserta = old('peserta', [0 => []]);
                    $isCreate = true;
                @endphp
                @foreach($oldPeserta as $index => $row)
                    @include('perjaldins.partials.peserta-card', ['index' => $index, 'row' => $row, 'masterProvinsi' => $masterProvinsi, 'masterPegawai' => $masterPegawai, 'isCreate' => true])
                @endforeach
            </div>

            <div class="text-center mt-3">
                <button type="button" class="btn-add-peserta btn-add-row-trigger">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Baris Peserta
                </button>
            </div>
        </div>
    </div>

    {{-- ============ D: COA ============ --}}
    @php
        $komponenList = [
            ['kode' => 'TIKET', 'label' => 'Biaya Tiket', 'icon' => 'bi-ticket-detailed-fill'],
            ['kode' => 'TRANSPORT', 'label' => 'Biaya Transport', 'icon' => 'bi-car-front-fill'],
            ['kode' => 'PENGINAPAN', 'label' => 'Biaya Penginapan', 'icon' => 'bi-buildings-fill'],
            ['kode' => 'UANG_HARIAN', 'label' => 'Uang Harian', 'icon' => 'bi-cash-coin'],
        ];
    @endphp

    <div class="sec-card" data-section="coa">
        <div class="sec-head">
            <div class="head-left">
                <span class="sec-icon icon-warning"><i class="bi bi-bank2"></i></span>
                <div>
                    <h6>Pemilihan COA per Komponen Biaya</h6>
                    <small>Tentukan akun anggaran untuk setiap komponen yang aktif</small>
                </div>
            </div>
            <span class="sec-letter">Step D</span>
        </div>
        <div class="sec-body">
            <div id="komponenCoaSection">
                <div class="komponen-coa-empty">
                    <i class="bi bi-info-circle-fill"></i>
                    Belum ada komponen biaya bernilai. Isi rincian biaya peserta terlebih dahulu — baris pemilihan COA akan muncul otomatis.
                </div>
                @foreach($komponenList as $k)
                    <div class="komponen-coa-row d-none" data-kode="{{ $k['kode'] }}">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="kr-icon"><i class="bi {{ $k['icon'] }}"></i></div>
                                    <div>
                                        <p class="kr-label">{{ $k['label'] }}</p>
                                        <p class="kr-meta mb-0">Kode: {{ $k['kode'] }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block" style="font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;">Total Komponen</small>
                                <span class="kr-total-pill">Rp <span class="komponen-coa-total">0</span></span>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-modern mb-1">Pilih COA <span class="text-danger">*</span></label>
                                <select name="komponen_coa[{{ $k['kode'] }}]" class="form-select form-select-sm komponen-coa-select @error('komponen_coa.' . $k['kode']) is-invalid @enderror">
                                    <option value="">-- Pilih COA --</option>
                                    @foreach($budgetGroups as $group)
                                        <optgroup label="{{ $group['label'] }}">
                                            @foreach($group['items'] as $item)
                                                <option value="{{ $item['id'] }}" data-sisa-pagu="{{ $item['sisa_pagu'] }}" {{ (string) old('komponen_coa.' . $k['kode']) === (string) $item['id'] ? 'selected' : '' }}>
                                                    {{ $item['option_label'] }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <div class="d-none komponen-coa-warning">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    <span class="komponen-coa-warning-text"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ============ STICKY SUBMIT BAR ============ --}}
    <div class="submit-bar-perj" id="submitBar">
        <div class="sb-status">
            <div class="sb-status-icon"><i class="bi bi-shield-exclamation" id="sbIcon"></i></div>
            <div>
                <h6 class="fw-bold mb-1 text-dark" id="sbTitle">Lengkapi formulir</h6>
                <p class="small text-muted mb-0" id="sbDesc">Isi semua field wajib pada Step A–D untuk siap submit.</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('perjaldins.index') }}" class="btn-cancel-perj">
                <i class="bi bi-x-circle me-1"></i> Batal
            </a>
            <button type="submit" class="btn-submit-perj">
                <i class="bi bi-cloud-upload-fill"></i> Simpan Pengajuan
            </button>
        </div>
    </div>
</form>
@endsection


@push('script')
<script>
    let rowIdx = {{ count($oldPeserta) }};

    function formatNumber(n) {
        return n.toString().replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    window.toggleTiketFile = function (element) {
        let card = $(element).closest('.item-row');
        let val = parseFloat(String($(element).val()).replace(/,/g, '')) || 0;
        let wrapper = card.find('.tiket-file-wrapper');
        if (val > 0) {
            wrapper.removeClass('d-none');
        } else {
            wrapper.addClass('d-none');
            wrapper.find('.tiket-file-input').val('');
            wrapper.find('.tiket-existing-notice').remove();
        }
    };

    function calculateUangHarianGroup(card) {
        let total = 0;
        card.find('.uang-harian-component').each(function () {
            let num = parseFloat(String($(this).val()).replace(/,/g, ''));
            if (!isNaN(num)) total += num;
        });
        card.find('.uang-harian-total').text(formatNumber(total));
    }

    function evaluateCoaPagu(row, total) {
        let select = row.find('.komponen-coa-select');
        let warning = row.find('.komponen-coa-warning');
        let warningText = row.find('.komponen-coa-warning-text');
        let selectedOpt = select.find('option:selected');
        let sisa = parseFloat(selectedOpt.data('sisa-pagu'));
        if (!selectedOpt.val() || isNaN(sisa)) {
            warning.addClass('d-none');
            select.removeClass('is-invalid');
            return;
        }
        if (sisa < total) {
            warningText.text('Sisa pagu COA (Rp ' + formatNumber(Math.round(sisa)) + ') tidak mencukupi total komponen (Rp ' + formatNumber(total) + ').');
            warning.removeClass('d-none');
            select.addClass('is-invalid');
        } else {
            warning.addClass('d-none');
            select.removeClass('is-invalid');
        }
    }

    function recalcKomponenCoaSection() {
        let totals = { TIKET: 0, TRANSPORT: 0, PENGINAPAN: 0, UANG_HARIAN: 0 };
        $('.komponen-input').each(function () {
            let kode = $(this).data('kode');
            if (!(kode in totals)) return;
            let num = parseFloat(String($(this).val()).replace(/,/g, ''));
            if (!isNaN(num)) totals[kode] += num;
        });
        let anyVisible = false;
        $('.komponen-coa-row').each(function () {
            let row = $(this);
            let kode = row.data('kode');
            let total = totals[kode] || 0;
            row.find('.komponen-coa-total').text(formatNumber(total));
            let select = row.find('.komponen-coa-select');
            if (total > 0) {
                row.removeClass('d-none');
                select.prop('required', true);
                anyVisible = true;
            } else {
                row.addClass('d-none');
                select.prop('required', false).val('');
            }
            evaluateCoaPagu(row, total);
        });
        $('.komponen-coa-empty').toggleClass('d-none', anyVisible);
        updateProgress();
    }

    $(document).on('change', '.komponen-coa-select', function () {
        let row = $(this).closest('.komponen-coa-row');
        let total = parseFloat(String(row.find('.komponen-coa-total').text()).replace(/,/g, '')) || 0;
        evaluateCoaPagu(row, total);
        updateProgress();
    });

    window.calculateJumlah = function (element) {
        let val = $(element).val();
        if (val !== '') $(element).val(formatNumber(val));

        let card = $(element).closest('.item-row');
        let total = 0;
        card.find('.biaya-input').each(function () {
            let num = parseFloat($(this).val().replace(/,/g, ''));
            if (!isNaN(num)) total += num;
        });
        card.find('.row-jumlah').val(formatNumber(total));
        card.find('.summary-total').text(formatNumber(total));
        calculateUangHarianGroup(card);
        calculateGrandTotal();
        recalcKomponenCoaSection();
    }

    function calculateGrandTotal() {
        let grandTotal = 0;
        $('.row-jumlah').each(function () {
            let num = parseFloat($(this).val().replace(/,/g, ''));
            if (!isNaN(num)) grandTotal += num;
        });
        $('#grandTotal').val(formatNumber(grandTotal));
        const grandEl = $('#summaryGrandTotal');
        const newText = 'Rp ' + formatNumber(grandTotal);
        if (grandEl.text() !== newText) {
            grandEl.removeClass('flash');
            void grandEl[0].offsetWidth;
            grandEl.addClass('flash');
        }
        grandEl.text(newText);
        $('#summaryCount').text($('.item-row').length);
        updateProgress();
    }

    function calculateUangHarian(card) {
        let provSelect = card.find('.provinsi-select option:selected');
        let tipe = card.find('.tipe-select').val();
        let lamaHari = parseInt(card.find('.lama-hari-input').val()) || 0;

        if (provSelect.val() !== '' && typeof provSelect.val() !== 'undefined' && tipe !== '') {
            let rate = 0;
            if (tipe === 'luar_kota') rate = parseFloat(provSelect.data('luar')) || 0;
            else if (tipe === 'dalam_kota_lebih_8_jam') rate = parseFloat(provSelect.data('dalam')) || 0;
            else if (tipe === 'diklat') rate = parseFloat(provSelect.data('diklat')) || 0;

            let totalUangHarian = rate * lamaHari;
            card.find('.uang-harian-input').val(formatNumber(totalUangHarian));
            calculateJumlah(card.find('.uang-harian-input')[0]);
        }
    }

    // ===== Progress stepper =====
    function updateProgress() {
        const flags = {
            dokumen: ['#inp_deskripsi', '#inp_nomor', '#inp_bulan', '#inp_tahun', '#inp_kota', '#inp_tanggal']
                .every(sel => ($(sel).val() || '').trim() !== ''),
            verifikator: ['#ppkUserId', '#ppspmUserId', '#bendaharaUserId', '#bendaharaPenerimaanUserId', '#koorKeuanganUserId']
                .every(sel => ($(sel).val() || '').trim() !== '')
                && ($('#ppkNipSnapshot').val() || '').trim() !== ''
                && ($('#bendaharaNipSnapshot').val() || '').trim() !== '',
            peserta: false,
            coa: false,
        };

        // peserta: minimal 1 row dengan total > 0 dan pegawai terpilih
        let validPeserta = 0;
        $('.item-row').each(function () {
            const card = $(this);
            const total = parseFloat(String(card.find('.row-jumlah').val() || '0').replace(/,/g, '')) || 0;
            const pegawaiVal = card.find('.pegawai-select').val() || '';
            if (total > 0 && pegawaiVal !== '') validPeserta++;
        });
        flags.peserta = validPeserta > 0;

        // coa: semua komponen yang visible (nilainya > 0) sudah dipilih COA-nya
        let coaOK = true;
        let coaNeeded = 0;
        $('.komponen-coa-row:not(.d-none)').each(function () {
            coaNeeded++;
            const select = $(this).find('.komponen-coa-select');
            if (!select.val() || select.hasClass('is-invalid')) coaOK = false;
        });
        flags.coa = coaNeeded > 0 && coaOK;

        const total = 4;
        let done = 0;
        const stepEls = document.querySelectorAll('.stepper-item');
        let firstActiveSet = false;
        stepEls.forEach(el => {
            const step = el.dataset.step;
            el.classList.remove('done', 'active');
            if (flags[step]) {
                el.classList.add('done');
                done++;
            } else if (!firstActiveSet) {
                el.classList.add('active');
                firstActiveSet = true;
            }
        });
        const pct = Math.round((done / total) * 100);
        document.getElementById('progressFill').style.width = pct + '%';
        document.getElementById('progressPct').textContent = pct + '% lengkap';

        // Highlight active section card
        document.querySelectorAll('.sec-card').forEach(card => {
            card.classList.toggle('is-active', card.dataset.section === document.querySelector('.stepper-item.active')?.dataset.step);
        });

        // Update submit bar
        const allReady = flags.dokumen && flags.verifikator && flags.peserta && flags.coa;
        const sb = document.getElementById('submitBar');
        const sbIcon = document.getElementById('sbIcon');
        const sbTitle = document.getElementById('sbTitle');
        const sbDesc = document.getElementById('sbDesc');
        if (allReady) {
            sb.classList.add('is-ready');
            sbIcon.className = 'bi bi-shield-check';
            sbTitle.textContent = 'Siap diajukan';
            sbDesc.textContent = 'Semua field wajib sudah terisi. Klik Simpan Pengajuan untuk melanjutkan.';
        } else {
            sb.classList.remove('is-ready');
            sbIcon.className = 'bi bi-shield-exclamation';
            sbTitle.textContent = 'Lengkapi formulir (' + pct + '%)';
            const missing = [];
            if (!flags.dokumen)      missing.push('Dokumen');
            if (!flags.verifikator)  missing.push('Verifikator');
            if (!flags.peserta)      missing.push('Peserta');
            if (!flags.coa)          missing.push('COA');
            sbDesc.textContent = missing.length ? 'Belum lengkap: ' + missing.join(', ') + '.' : 'Hampir selesai!';
        }
    }

    $(document).ready(function () {
        $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

        $('.biaya-input').each(function() { calculateJumlah(this); });
        $('.tiket-amount-input').each(function() { toggleTiketFile(this); });

        $('#ppkUserId').change(function() {
            let s = $(this).find(':selected');
            if (s.val() !== '') {
                $('#ppkNamaSnapshot').val(s.data('nama'));
                $('#ppkNipSnapshot').val(s.data('nip'));
            }
            updateProgress();
        });
        $('#ppspmUserId').change(function() {
            let s = $(this).find(':selected');
            if (s.val() !== '') {
                $('#ppspmNamaSnapshot').val(s.data('nama'));
                $('#ppspmNipSnapshot').val(s.data('nip'));
            }
            updateProgress();
        });
        $('#bendaharaPenerimaanUserId').change(function() {
            let s = $(this).find(':selected');
            if (s.val() !== '') {
                $('#bendaharaPenerimaanNamaSnapshot').val(s.data('nama'));
                $('#bendaharaPenerimaanNipSnapshot').val(s.data('nip'));
            }
            updateProgress();
        });
        $('#bendaharaUserId').change(function() {
            let s = $(this).find(':selected');
            if (s.val() !== '') {
                $('#bendaharaNamaSnapshot').val(s.data('nama'));
                $('#bendaharaNipSnapshot').val(s.data('nip'));
            }
            updateProgress();
        });
        $('#koorKeuanganUserId').change(function() {
            let s = $(this).find(':selected');
            if (s.val() !== '') {
                $('#koorKeuanganNamaSnapshot').val(s.data('nama'));
                $('#koorKeuanganNipSnapshot').val(s.data('nip'));
            }
            updateProgress();
        });

        // Update progress when document fields change
        $('#inp_deskripsi, #inp_nomor, #inp_bulan, #inp_tahun, #inp_kota, #inp_tanggal, #ppkNipSnapshot, #bendaharaNipSnapshot').on('input change', updateProgress);

        // Stepper click → smooth scroll to section
        $('.stepper-item').on('click', function () {
            const step = $(this).data('step');
            const target = document.querySelector('[data-section="' + step + '"]');
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        // Add Row
        $(document).on('click', '.btn-add-row-trigger', function (e) {
            e.preventDefault();

            let firstRow = $('.item-row:first');
            if (firstRow.find('.select2').hasClass('select2-hidden-accessible')) {
                firstRow.find('.select2').select2('destroy');
            }

            let newRow = firstRow.clone();
            firstRow.find('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

            newRow.find('input[type="text"], input[type="number"], input[type="date"], input[type="hidden"], input[type="file"], textarea').val('').removeClass('is-invalid');
            newRow.find('select').prop('selectedIndex', 0).removeClass('is-invalid');
            newRow.find('.row-jumlah').val('0');
            newRow.find('.summary-nama, .summary-tujuan').text('-');
            newRow.find('.summary-total').text('0');
            newRow.find('.uang-harian-total').text('0');
            newRow.find('.file-existing-notice').remove();
            newRow.find('.tiket-existing-notice').remove();
            newRow.find('.tiket-file-wrapper').addClass('d-none');
            newRow.find('.file-status-badge').removeClass('bg-success').addClass('bg-secondary').html('<i class="bi bi-paperclip"></i> SPT Kosong');
            newRow.find('.nip-input').val('').prop('readonly', true);
            newRow.find('.rekening-input').val('').prop('readonly', false);
            newRow.find('.rek-hint').addClass('d-none');

            newRow.find('.select2-container').remove();
            newRow.find('.select2-hidden-accessible').removeClass('select2-hidden-accessible').removeAttr('data-select2-id aria-hidden tabindex');

            let collapseId = 'collapsePeserta' + rowIdx;
            newRow.find('.collapse-trigger').attr('data-bs-target', '#' + collapseId);
            newRow.find('.peserta-collapse').attr('id', collapseId).addClass('show');

            newRow.find('input, select, textarea').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    name = name.replace(/\[\d+\]/g, '[' + rowIdx + ']');
                    $(this).attr('name', name);
                }
                $(this).removeAttr('id');
            });

            newRow.find('.btn-delete-row').prop('disabled', false);
            $('#pesertaRepeater').append(newRow);

            newRow.find('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

            updateRowNumbers();
            $('.btn-delete-row').prop('disabled', false);
            rowIdx++;
            updateProgress();
        });

        $(document).on('click', '.btn-delete-row', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if ($('.item-row').length > 1) {
                $(this).closest('.item-row').remove();
                updateRowNumbers();
                calculateGrandTotal();
                recalcKomponenCoaSection();
                if ($('.item-row').length === 1) {
                    $('.btn-delete-row').prop('disabled', true);
                }
                updateProgress();
            }
        });

        $(document).on('change', '.provinsi-select, .tipe-select', function() {
            calculateUangHarian($(this).closest('.item-row'));
        });
        $(document).on('input', '.lama-hari-input', function() {
            calculateUangHarian($(this).closest('.item-row'));
        });

        $(document).on('change', '.pegawai-select', function() {
            let card = $(this).closest('.item-row');
            let selected = $(this).find(':selected');
            let nama = selected.data('nama') || '';
            let nip = selected.data('nip') || '';
            let rek = selected.data('rek') || '';

            card.find('.input-nama-hidden').val(nama);
            card.find('.nip-input').val(nip);

            if (rek) {
                card.find('.rekening-input').val(rek).prop('readonly', false);
                card.find('.rek-hint').addClass('d-none');
            } else {
                card.find('.rekening-input').val('').prop('readonly', false);
                if (selected.val() !== '') {
                    card.find('.rek-hint').removeClass('d-none');
                } else {
                    card.find('.rek-hint').addClass('d-none');
                }
            }

            card.find('.summary-nama').text(nama || '-');
            updateProgress();
        });

        $(document).on('input', '.input-tujuan', function() {
            $(this).closest('.item-row').find('.summary-tujuan').text($(this).val() || '-');
        });

        $(document).on('change', '.spt-file-input', function() {
            let card = $(this).closest('.item-row');
            let badge = card.find('.file-status-badge');
            if (this.files && this.files.length > 0) {
                badge.removeClass('bg-secondary text-secondary border-secondary').addClass('bg-success text-white').html('<i class="bi bi-paperclip"></i> SPT: ' + this.files[0].name.substring(0, 15) + '...');
            } else {
                badge.removeClass('bg-success text-white').addClass('bg-secondary text-white').html('<i class="bi bi-paperclip"></i> SPT Kosong');
            }
        });

        $(document).on('click', '.collapse-trigger', function() {
            let icon = $(this).find('.toggle-icon');
            if ($(this).attr('aria-expanded') === 'true') {
                icon.removeClass('bi-chevron-down').addClass('bi-chevron-right');
            } else {
                icon.removeClass('bi-chevron-right').addClass('bi-chevron-down');
            }
        });

        function updateRowNumbers() {
            $('.item-row').each(function (index) {
                $(this).find('.row-number').text(index + 1);
            });
            calculateGrandTotal();
        }

        @if($errors->any())
            $('.is-invalid').closest('.peserta-collapse').addClass('show');
            $('.is-invalid').closest('.item-row').addClass('border border-danger');
        @endif

        updateRowNumbers();
        updateProgress();
    });
</script>
@endpush
