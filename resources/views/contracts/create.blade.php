@extends('layouts.app')
@section('title')
    Tambah Kontrak Pengadaan
@endsection

@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    body[data-bs-theme="blue-theme"] .main-content { background: #f6f7fb; }

    /* ============ HERO ============ */
    .form-hero {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%);
        border-radius: 1.25rem;
        padding: 1.5rem 2rem;
        color: #fff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 14px 32px rgba(79,70,229,.25);
        margin-bottom: 1.5rem;
        animation: heroIn .55s cubic-bezier(.22,1,.36,1) both;
    }
    .form-hero::before, .form-hero::after { content:''; position:absolute; border-radius:50%; }
    .form-hero::before { right:-90px; top:-90px; width:280px; height:280px; background: rgba(255,255,255,.10); }
    .form-hero::after  { right:60px; bottom:-70px; width:180px; height:180px; background: rgba(255,255,255,.07); }
    .form-hero > * { position: relative; z-index: 1; }
    .form-hero h2 {
        color: #fff !important;
        font-weight: 800;
        font-size: 1.55rem;
        margin: 0 0 .35rem;
        letter-spacing: -.01em;
        text-shadow: 0 1px 2px rgba(0,0,0,.15);
    }
    .form-hero p { color: rgba(255,255,255,.92) !important; margin: 0; }
    .form-hero .hero-tag {
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
    .briefcase-illust {
        position: absolute;
        right: 1.5rem; top: 50%;
        transform: translateY(-50%) rotate(-8deg);
        font-size: 7rem; opacity: .14;
    }
    .btn-back-hero {
        background: rgba(255,255,255,.18);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.30);
        color: #fff;
        font-weight: 600;
        padding: .55rem 1.05rem;
        border-radius: 999px;
        font-size: .82rem;
        transition: all .2s ease;
        text-decoration: none;
        display: inline-flex; align-items: center; gap: .35rem;
    }
    .btn-back-hero:hover {
        background: rgba(255,255,255,.30);
        color: #fff;
        transform: translateX(-3px);
    }

    /* ============ Section Card ============ */
    .sec-card {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1.15rem;
        margin-bottom: 1.15rem;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(15,23,42,.04);
        transition: box-shadow .25s ease, transform .25s ease;
        animation: secIn .55s cubic-bezier(.22,1,.36,1) both;
    }
    .sec-card:nth-of-type(1) { animation-delay: .12s; }
    .sec-card:nth-of-type(2) { animation-delay: .19s; }
    .sec-card:nth-of-type(3) { animation-delay: .26s; }
    .sec-card:nth-of-type(4) { animation-delay: .33s; }
    .sec-card:nth-of-type(5) { animation-delay: .40s; }
    .sec-card:hover { box-shadow: 0 14px 32px rgba(15,23,42,.07); }
    .sec-head {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #f1f3f7;
        display: flex; align-items: center; gap: .85rem;
        background: linear-gradient(180deg, #fafbff 0%, #ffffff 100%);
    }
    .sec-icon {
        width: 44px; height: 44px;
        border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.2rem; color: #fff;
        flex-shrink: 0;
        background: var(--si-bg, linear-gradient(135deg, #818cf8, #6366f1));
        box-shadow: 0 6px 14px var(--si-shadow, rgba(99,102,241,.30));
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
        font-size: .76rem;
        color: #64748b;
        display: block;
        margin-top: .15rem;
    }
    .sec-letter {
        margin-left: auto;
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .12em;
        color: #94a3b8;
        text-transform: uppercase;
        background: #f1f5f9;
        padding: .2rem .55rem;
        border-radius: 999px;
    }
    .sec-body { padding: 1.5rem; }

    .si-primary { --si-bg: linear-gradient(135deg, #818cf8, #6366f1); --si-shadow: rgba(99,102,241,.30); }
    .si-info    { --si-bg: linear-gradient(135deg, #38bdf8, #0ea5e9); --si-shadow: rgba(14,165,233,.30); }
    .si-warning { --si-bg: linear-gradient(135deg, #fbbf24, #f59e0b); --si-shadow: rgba(245,158,11,.30); }
    .si-success { --si-bg: linear-gradient(135deg, #34d399, #10b981); --si-shadow: rgba(16,185,129,.30); }
    .si-secondary { --si-bg: linear-gradient(135deg, #94a3b8, #475569); --si-shadow: rgba(100,116,139,.30); }

    /* ============ Modern Inputs ============ */
    .form-label.modern {
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
    .form-select.modern,
    .sec-card input[type="text"]:not(.select2-search__field):not(.bg-light),
    .sec-card input[type="number"],
    .sec-card input[type="date"],
    .sec-card textarea,
    .sec-card select:not(.select2-hidden-accessible):not(.bg-light) {
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        border-radius: .65rem;
        padding: .58rem .85rem;
        font-size: .9rem;
        transition: all .2s ease;
    }
    .sec-card input.bg-light,
    .sec-card .form-control.bg-light {
        background: #f1f5f9 !important;
        color: #64748b;
        border: 1px solid #e2e8f0;
        border-radius: .65rem;
        padding: .58rem .85rem;
    }
    .form-control.modern:hover,
    .form-select.modern:hover,
    .sec-card input[type="text"]:not(.select2-search__field):not([readonly]):hover,
    .sec-card input[type="number"]:hover,
    .sec-card input[type="date"]:hover,
    .sec-card textarea:hover {
        border-color: #cbd5e1;
        background: #fff;
    }
    .form-control.modern:focus,
    .form-select.modern:focus,
    .sec-card input[type="text"]:not(.select2-search__field):not([readonly]):focus,
    .sec-card input[type="number"]:focus,
    .sec-card input[type="date"]:focus,
    .sec-card textarea:focus,
    .sec-card select:not(.bg-light):focus {
        outline: 0;
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(99,102,241,.12);
    }
    .input-group-text {
        background: rgba(99,102,241,.07);
        border: 1px solid #e2e8f0;
        color: #4f46e5;
        font-weight: 600;
    }

    /* ============ Select2 Premium Polish ============ */
    .select2-container .select2-selection--single {
        height: 44px !important;
        border: 1px solid #e2e8f0 !important;
        background: #f8fafc !important;
        border-radius: .7rem !important;
        transition: all .2s ease !important;
        padding: .35rem .35rem !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
        color: #1e293b;
        font-size: .9rem;
        padding-left: .85rem !important;
        padding-right: 2.2rem !important;
        font-weight: 500;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #94a3b8 !important;
        font-weight: 400;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 42px !important;
        width: 30px !important;
        right: 6px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: #6366f1 transparent transparent transparent !important;
        border-width: 6px 5px 0 5px !important;
        margin-top: -3px !important;
        transition: transform .2s ease;
    }
    .select2-container--default .select2-selection--single:hover {
        border-color: #c7d2fe !important;
        background: #fff !important;
    }
    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #6366f1 !important;
        background: #fff !important;
        box-shadow: 0 0 0 4px rgba(99,102,241,.12), 0 4px 12px rgba(99,102,241,.10) !important;
    }
    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
        transform: rotate(180deg);
        margin-top: -1px !important;
    }

    /* Allow clear button */
    .select2-container--default .select2-selection__clear {
        color: #f43f5e;
        margin-right: .5rem;
        font-size: 1.1rem;
        font-weight: 700;
    }
    .select2-container--default .select2-selection__clear:hover { color: #be123c; }

    /* ===== Dropdown Panel ===== */
    .select2-dropdown {
        border: 1px solid #c7d2fe !important;
        border-radius: .85rem !important;
        box-shadow: 0 16px 40px rgba(15,23,42,.12), 0 4px 12px rgba(99,102,241,.10) !important;
        overflow: hidden;
        background: #fff;
        animation: dropdownIn .2s cubic-bezier(.22,1,.36,1) both;
    }
    @keyframes dropdownIn {
        from { opacity: 0; transform: translateY(-6px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* Search box di dalam dropdown */
    .select2-search--dropdown {
        padding: .65rem !important;
        background: linear-gradient(180deg, #fafbff 0%, #ffffff 100%);
        border-bottom: 1px solid #f1f3f7;
    }
    .select2-search--dropdown .select2-search__field {
        border: 1px solid #e2e8f0 !important;
        border-radius: .55rem !important;
        padding: .5rem .85rem .5rem 2.25rem !important;
        font-size: .85rem !important;
        background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E") no-repeat .8rem center !important;
        transition: all .2s ease;
        outline: 0 !important;
    }
    .select2-search--dropdown .select2-search__field:focus {
        border-color: #6366f1 !important;
        background-color: #fff !important;
        box-shadow: 0 0 0 3px rgba(99,102,241,.12) !important;
    }

    /* Result list */
    .select2-results__options {
        padding: .35rem !important;
        max-height: 280px !important;
        overflow-y: auto !important;
    }
    .select2-results__options::-webkit-scrollbar { width: 8px; }
    .select2-results__options::-webkit-scrollbar-track { background: #f8fafc; }
    .select2-results__options::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
    }
    .select2-results__options::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    .select2-container--default .select2-results__option {
        padding: .55rem .85rem !important;
        font-size: .87rem;
        color: #334155;
        border-radius: .5rem;
        margin: 1px 0;
        transition: all .12s ease;
        cursor: pointer;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected],
    .select2-container--default .select2-results__option--highlighted {
        background: linear-gradient(135deg, rgba(99,102,241,.12), rgba(139,92,246,.08)) !important;
        color: #4338ca !important;
        font-weight: 600;
        transform: translateX(2px);
    }
    .select2-container--default .select2-results__option[aria-selected=true] {
        background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
        color: #fff !important;
        font-weight: 600;
        box-shadow: 0 4px 10px rgba(99,102,241,.30);
    }
    .select2-container--default .select2-results__option[aria-selected=true]::after {
        content: '\F26B';
        font-family: 'bootstrap-icons';
        margin-left: .65rem;
        float: right;
        font-size: .85rem;
    }

    /* Optgroup styling */
    .select2-container--default .select2-results__group {
        background: linear-gradient(135deg, #f1f5f9, #fafbff);
        color: #4338ca !important;
        padding: .45rem .85rem !important;
        font-size: .68rem !important;
        font-weight: 800 !important;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin: .35rem .15rem .25rem;
        border-radius: .45rem;
        border-left: 3px solid #6366f1;
        cursor: default;
    }
    .select2-container--default .select2-results__option .select2-results__group { margin: 0; }

    /* No results / loading state */
    .select2-container--default .select2-results__option--disabled {
        color: #94a3b8 !important;
        font-style: italic;
        text-align: center;
        padding: 1rem !important;
    }

    /* ============ Native form-select polish ============ */
    .form-select.modern {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%236366f1' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M2 5l6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right .85rem center;
        background-size: 14px 14px;
        padding-right: 2.5rem;
    }

    /* ============ Form Switch & Radio ============ */
    .form-switch .form-check-input {
        width: 2.6em;
        height: 1.4em;
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e");
        background-color: #cbd5e1;
        border-color: #cbd5e1;
    }
    .form-switch .form-check-input:checked {
        background-color: #6366f1;
        border-color: #6366f1;
    }
    .form-switch .form-check-input:focus { box-shadow: 0 0 0 4px rgba(99,102,241,.20); }
    .form-check-input:checked { background-color: #6366f1; border-color: #6366f1; }

    /* ============ Inline Hint Banner ============ */
    .info-banner {
        background: linear-gradient(135deg, rgba(99,102,241,.06), rgba(99,102,241,.02));
        border: 1px solid rgba(99,102,241,.20);
        border-left: 4px solid #6366f1;
        border-radius: .75rem;
        padding: .75rem 1rem;
        font-size: .82rem;
        color: #475569;
        display: flex;
        gap: .55rem;
        align-items: flex-start;
    }
    .info-banner i {
        color: #4f46e5;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .info-banner.banner-warning {
        background: linear-gradient(135deg, rgba(245,158,11,.06), rgba(245,158,11,.02));
        border-color: rgba(245,158,11,.25);
        border-left-color: #f59e0b;
        color: #92400e;
    }
    .info-banner.banner-warning i { color: #b45309; }
    .info-banner.banner-secondary {
        background: linear-gradient(135deg, rgba(100,116,139,.06), rgba(100,116,139,.02));
        border-color: rgba(100,116,139,.20);
        border-left-color: #64748b;
        color: #475569;
    }
    .info-banner.banner-secondary i { color: #475569; }

    /* ============ Termin sub-card ============ */
    .termin-subcard {
        background: linear-gradient(135deg, rgba(99,102,241,.04), rgba(14,165,233,.02));
        border: 1px solid rgba(99,102,241,.18);
        border-radius: 1rem;
        padding: 1.25rem;
        margin-top: 1rem;
    }
    .termin-subcard table {
        background: #fff;
        border-radius: .85rem;
        overflow: hidden;
    }
    .termin-subcard table th {
        background: #f8fafc;
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        padding: .65rem .75rem;
        border: 0;
        border-bottom: 1px solid #eef0f4;
    }
    .termin-subcard table td {
        padding: .65rem .75rem;
        font-size: .85rem;
        border-bottom: 1px solid #f1f3f7;
        vertical-align: middle;
    }
    .termin-subcard table tr:last-child td { border-bottom: 0; }

    .btn-add-termin {
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: #fff;
        border: 0;
        font-weight: 600;
        padding: .5rem 1.1rem;
        border-radius: .65rem;
        font-size: .82rem;
        box-shadow: 0 4px 10px rgba(99,102,241,.30);
        transition: all .2s ease;
        display: inline-flex; align-items: center; gap: .35rem;
    }
    .btn-add-termin:hover {
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(99,102,241,.40);
    }
    .btn-del-termin {
        background: rgba(244,63,94,.10);
        color: #be123c;
        border: 1px solid rgba(244,63,94,.18);
        padding: .35rem .55rem;
        border-radius: .5rem;
        font-size: .85rem;
        transition: all .15s ease;
    }
    .btn-del-termin:hover:not(:disabled) {
        background: #f43f5e;
        color: #fff;
        border-color: #f43f5e;
    }
    .btn-del-termin:disabled { opacity: .35; cursor: not-allowed; }

    .preview-card {
        background: #fff;
        border: 1px solid rgba(99,102,241,.15);
        border-radius: .85rem;
        padding: 1rem 1.15rem;
        margin-top: 1rem;
    }
    .preview-card h6 {
        font-size: .8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #4338ca;
        margin: 0 0 .65rem;
    }
    .preview-um {
        background: linear-gradient(135deg, rgba(245,158,11,.08), rgba(245,158,11,.02));
        border: 1px solid rgba(245,158,11,.22);
        border-radius: .85rem;
        padding: 1rem 1.15rem;
        margin-top: 1rem;
    }
    .preview-um h6 {
        font-size: .85rem; font-weight: 800;
        color: #92400e;
        margin: 0 0 .35rem;
    }
    .preview-um .pu-stat {
        background: #fff;
        border-radius: .55rem;
        padding: .55rem .75rem;
        border: 1px solid rgba(245,158,11,.15);
    }
    .preview-um .pu-stat .pu-label {
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #94a3b8;
    }
    .preview-um .pu-stat .pu-value {
        font-size: 1.05rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
    }

    /* ============ Submit Bar ============ */
    .submit-bar {
        background: rgba(255,255,255,.92);
        backdrop-filter: blur(10px);
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        display: flex;
        gap: .75rem;
        align-items: center;
        justify-content: flex-end;
        box-shadow: 0 12px 28px rgba(15,23,42,.08);
        position: sticky;
        bottom: 1rem;
        z-index: 20;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        animation: secIn .65s cubic-bezier(.22,1,.36,1) .55s both;
    }
    .btn-cancel-submit {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-weight: 600;
        padding: .7rem 1.4rem;
        border-radius: .7rem;
        font-size: .9rem;
        transition: all .2s ease;
    }
    .btn-cancel-submit:hover {
        background: #e2e8f0;
        color: #1e293b;
        transform: translateY(-1px);
    }
    .btn-submit-primary {
        background: linear-gradient(135deg, #6366f1, #8b5cf6, #ec4899);
        background-size: 200% 100%;
        background-position: 0% 0%;
        border: 0;
        color: #fff;
        font-weight: 700;
        padding: .7rem 1.6rem;
        border-radius: .7rem;
        font-size: .9rem;
        box-shadow: 0 8px 22px rgba(99,102,241,.35);
        transition: all .35s ease;
        display: inline-flex;
        align-items: center;
        gap: .5rem;
    }
    .btn-submit-primary:not(:disabled):hover {
        background-position: 100% 0%;
        transform: translateY(-2px);
        box-shadow: 0 14px 28px rgba(99,102,241,.45);
        color: #fff;
    }
    .btn-submit-primary:disabled {
        background: linear-gradient(135deg, #cbd5e1, #94a3b8);
        cursor: not-allowed;
        box-shadow: none;
        opacity: .8;
    }

    /* Validation alert modern */
    .alert-modern-error {
        background: linear-gradient(135deg, rgba(244,63,94,.06), rgba(220,38,38,.04));
        border: 1px solid rgba(244,63,94,.20);
        border-left: 4px solid #f43f5e;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        color: #991b1b;
        margin-bottom: 1.25rem;
        animation: shake .55s cubic-bezier(.36,.07,.19,.97) both;
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

    /* Animations */
    @keyframes heroIn {
        from { opacity: 0; transform: translateY(-12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes secIn {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@section('content')
    @php
        $oldProgressKeterangan = old('progress_keterangan', ['']);
        $oldProgressPersentase = old('progress_persentase', ['']);
        $progressRowCount = max(count($oldProgressKeterangan), count($oldProgressPersentase), 1);
    @endphp

    {{-- HERO --}}
    <div class="form-hero">
        <i class="bi bi-briefcase-fill briefcase-illust d-none d-md-block"></i>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="hero-tag"><i class="bi bi-stars"></i> Form Pengadaan Baru</span>
                <h2><i class="bi bi-folder-plus me-2"></i>Tambah Kontrak Pengadaan</h2>
                <p>Lengkapi data kontrak, vendor, skema pembayaran, dan penandatangan untuk membuat draft pengadaan.</p>
            </div>
            <a href="{{ route('contracts.index') }}" class="btn-back-hero">
                <i class="bi bi-arrow-left"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

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

    <form action="{{ route('contracts.store') }}" method="POST" id="formKontrak" enctype="multipart/form-data">
        @csrf

        <div class="row">
            {{-- A: Data Utama & Anggaran --}}
            <div class="col-12">
                <div class="sec-card">
                    <div class="sec-head">
                        <div class="sec-icon si-primary"><i class="bi bi-file-earmark-text-fill"></i></div>
                        <div class="flex-grow-1">
                            <h6>Data Utama & Pemilihan Anggaran</h6>
                            <small>Vendor, sumber anggaran, dan ringkasan pekerjaan</small>
                        </div>
                        <span class="sec-letter">Step A</span>
                    </div>
                    <div class="sec-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label modern"><i class="bi bi-building"></i> Pilih Vendor / Mitra <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="vendor_id" required>
                                    <option value="">-- Cari Vendor --</option>
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                            {{ $vendor->nama_pihak }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                @include('partials.dipa-item-grouped-select', [
                                    'budgetGroups' => $budgetGroups,
                                    'fieldName' => 'dipa_revision_item_id',
                                    'fieldId' => 'dipa_revision_item_id',
                                    'fieldClass' => 'form-select select2',
                                    'fieldLabel' => 'Pilih Item Anggaran (COA)',
                                    'placeholder' => '-- Cari Item Anggaran DIPA Aktif --',
                                ])
                            </div>
                            <div class="col-12">
                                <label class="form-label modern"><i class="bi bi-journal-text"></i> Nama Pekerjaan <span class="text-danger">*</span></label>
                                <textarea class="form-control modern" rows="3" name="nama_pekerjaan" placeholder="Contoh: Pengadaan Jasa Kebersihan (Cleaning Service) Area Terminal Bandara" required>{{ old('nama_pekerjaan') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- B: Detail Kontrak & Waktu Pelaksanaan --}}
            <div class="col-12">
                <div class="sec-card">
                    <div class="sec-head">
                        <div class="sec-icon si-info"><i class="bi bi-calendar-range-fill"></i></div>
                        <div class="flex-grow-1">
                            <h6>Detail Kontrak & Waktu Pelaksanaan</h6>
                            <small>Nomor SPK/SPMK, periode pekerjaan, dan ketentuan denda</small>
                        </div>
                        <span class="sec-letter">Step B</span>
                    </div>
                    <div class="sec-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label modern"><i class="bi bi-hash"></i> Nomor SPK</label>
                                <input type="text" class="form-control bg-light" value="{{ $nomorSpkPreview }}" readonly>
                                <small class="text-muted d-block mt-1"><i class="bi bi-info-circle me-1"></i>Preview nomor otomatis. Final akan digenerate saat kontrak disimpan.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label modern"><i class="bi bi-calendar-date"></i> Tanggal SPK <span class="text-danger">*</span></label>
                                <input type="date" class="form-control modern" name="tanggal_spk" value="{{ old('tanggal_spk', now()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label modern"><i class="bi bi-hash"></i> Nomor SPMK</label>
                                <input type="text" class="form-control bg-light" value="{{ $nomorSpmkPreview }}" readonly>
                                <small class="text-muted d-block mt-1"><i class="bi bi-info-circle me-1"></i>Preview nomor otomatis. Final akan digenerate saat kontrak disimpan.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label modern"><i class="bi bi-calendar-date"></i> Tanggal SPMK <span class="text-danger">*</span></label>
                                <input type="date" class="form-control modern" name="tanggal_spmk" value="{{ old('tanggal_spmk', now()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label modern"><i class="bi bi-envelope-paper"></i> Nomor Surat Undangan Pengadaan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control modern" name="nomor_surat_undangan_pengadaan" value="{{ old('nomor_surat_undangan_pengadaan') }}" placeholder="Contoh: B/123/PL.04.01/2026" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label modern"><i class="bi bi-clipboard-check"></i> Nomor BA Hasil Pengadaan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control modern" name="nomor_ba_hasil_pengadaan" value="{{ old('nomor_ba_hasil_pengadaan') }}" placeholder="Contoh: BA/045/PL/2026" required>
                            </div>

                            <div class="col-12">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label modern"><i class="bi bi-play-circle"></i> Tgl Mulai <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control modern" id="tanggal_mulai" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}" required onchange="hitungTanggalSelesai()">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label modern"><i class="bi bi-clock-history"></i> Jangka Waktu <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control modern" id="jangka_waktu" name="jangka_waktu" value="{{ old('jangka_waktu') }}" min="1" required oninput="hitungTanggalSelesai()">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label modern"><i class="bi bi-stopwatch"></i> Satuan Waktu <span class="text-danger">*</span></label>
                                        <select class="form-select modern" id="satuan_waktu" name="satuan_waktu" required onchange="hitungTanggalSelesai()">
                                            <option value="HARI" {{ old('satuan_waktu') == 'HARI' ? 'selected' : '' }}>Hari</option>
                                            <option value="MINGGU" {{ old('satuan_waktu') == 'MINGGU' ? 'selected' : '' }}>Minggu</option>
                                            <option value="BULAN" {{ old('satuan_waktu') == 'BULAN' ? 'selected' : '' }}>Bulan</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label modern"><i class="bi bi-flag"></i> Tgl Selesai (otomatis)</label>
                                        <input type="date" class="form-control bg-light" id="tanggal_selesai" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}" readonly required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label modern"><i class="bi bi-exclamation-circle"></i> Ketentuan Denda</label>
                                <textarea class="form-control modern" rows="2" name="ketentuan_denda" placeholder="Contoh: Denda keterlambatan dikenakan 1/1000 dari nilai kontrak per hari kalender.">{{ old('ketentuan_denda') }}</textarea>
                            </div>
                            <div class="col-12 mt-3">
                                <label class="form-label modern"><i class="bi bi-image"></i> Gambar RAB (JPG/PNG) <small class="text-muted">(Opsional, untuk Lampiran SPK)</small></label>
                                <input type="file" class="form-control modern" name="gambar_rab" id="gambar_rab" accept=".jpg,.jpeg,.png">
                                <small class="text-muted d-block mt-1"><i class="bi bi-info-circle me-1"></i>Unggah gambar screenshot RAB jika diperlukan untuk cetak lampiran Draft SPK.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- C: Waktu Pemeliharaan --}}
            <div class="col-12">
                <div class="sec-card">
                    <div class="sec-head">
                        <div class="sec-icon si-warning"><i class="bi bi-tools"></i></div>
                        <div class="flex-grow-1">
                            <h6>Waktu Pemeliharaan</h6>
                            <small>Periode dan masa pemeliharaan setelah pekerjaan selesai (opsional)</small>
                        </div>
                        <span class="sec-letter">Step C</span>
                    </div>
                    <div class="sec-body">
                        <div class="row g-4 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label modern"><i class="bi bi-calendar-plus"></i> Tgl Mulai Pemeliharaan</label>
                                <input type="date" class="form-control modern" id="tanggal_mulai_pemeliharaan" name="tanggal_mulai_pemeliharaan" value="{{ old('tanggal_mulai_pemeliharaan') }}" onchange="hitungTanggalSelesaiPemeliharaan()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label modern"><i class="bi bi-hourglass"></i> Masa Pemeliharaan (hari kalender)</label>
                                <input type="number" class="form-control modern" id="masa_pemeliharaan_hari" name="masa_pemeliharaan_hari" value="{{ old('masa_pemeliharaan_hari') }}" min="0" placeholder="Contoh: 180" oninput="hitungTanggalSelesaiPemeliharaan()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label modern"><i class="bi bi-flag-fill"></i> Tgl Selesai Pemeliharaan (otomatis)</label>
                                <input type="date" class="form-control bg-light" id="tanggal_selesai_pemeliharaan" name="tanggal_selesai_pemeliharaan" value="{{ old('tanggal_selesai_pemeliharaan') }}" readonly>
                            </div>
                            <div class="col-12">
                                <div class="info-banner banner-warning">
                                    <i class="bi bi-info-circle-fill"></i>
                                    <span>Waktu pemeliharaan bersifat opsional. Jika diisi, tanggal selesai pemeliharaan akan dihitung otomatis berdasarkan tanggal mulai dan masa pemeliharaan.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- D: Nilai & Skema Pembayaran --}}
            <div class="col-12">
                <div class="sec-card">
                    <div class="sec-head">
                        <div class="sec-icon si-success"><i class="bi bi-cash-stack"></i></div>
                        <div class="flex-grow-1">
                            <h6>Nilai & Skema Pembayaran</h6>
                            <small>Total nilai, metode pembayaran, uang muka, dan termin progress</small>
                        </div>
                        <span class="sec-letter">Step D</span>
                    </div>
                    <div class="sec-body">
                        <div class="row g-4 align-items-center">
                            <div class="col-md-6">
                                <label class="form-label modern"><i class="bi bi-currency-dollar"></i> Nilai Total Kontrak (Rp) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control modern rupiah-input" id="nilai_total_kontrak_display" placeholder="Misal: 100.000.000" value="{{ old('nilai_total_kontrak') }}" required>
                                <input type="hidden" name="nilai_total_kontrak" id="nilai_total_kontrak_value" value="{{ old('nilai_total_kontrak') }}">
                                <small class="text-danger mt-1 fw-bold d-none" id="pagu_error"></small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label modern d-block"><i class="bi bi-credit-card-2-front"></i> Metode Pembayaran <span class="text-danger">*</span></label>
                                <div class="form-check form-check-inline mt-2">
                                    <input class="form-check-input" type="radio" name="metode_pembayaran" id="metodeLumpsum" value="LUMPSUM" {{ old('metode_pembayaran', 'LUMPSUM') == 'LUMPSUM' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="metodeLumpsum">LUMPSUM (Sekaligus)</label>
                                </div>
                                <div class="form-check form-check-inline mt-2">
                                    <input class="form-check-input" type="radio" name="metode_pembayaran" id="metodeTermin" value="TERMIN" {{ old('metode_pembayaran') == 'TERMIN' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="metodeTermin">TERMIN (Bertahap)</label>
                                </div>
                            </div>

                            <div class="col-12 pt-2 border-top" id="wrapper_toggle_uang_muka">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="ada_uang_muka" name="ada_uang_muka" value="1" {{ old('ada_uang_muka') ? 'checked' : '' }} onchange="toggleUangMuka()">
                                    <label class="form-check-label fw-bold" for="ada_uang_muka">Kontrak ini menerapkan Uang Muka (DP)?</label>
                                </div>
                            </div>

                                <div class="col-md-6" id="wrapper_uang_muka" style="display: none;">
                                    <label class="form-label modern"><i class="bi bi-cash-coin"></i> Nilai Uang Muka (Rp) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control modern rupiah-input" id="nilai_uang_muka_display" placeholder="Misal: 20.000.000" value="{{ old('nilai_uang_muka') }}" oninput="validasiUangMuka()">
                                    <input type="hidden" name="nilai_uang_muka" id="nilai_uang_muka_value" value="{{ old('nilai_uang_muka', 0) }}">
                                    <small class="text-danger mt-1 d-none" id="uang_muka_error">Peringatan: Nilai Uang Muka melebihi batas wajar (30%) dari Total Kontrak!</small>
                                </div>
                                <div class="col-md-6" id="wrapper_file_jaminan_um" style="display: none;">
                                    <label class="form-label modern"><i class="bi bi-shield-fill-check"></i> Jaminan Uang Muka <small class="text-muted">(PDF)</small></label>
                                    <input type="file" class="form-control modern" name="file_jaminan_um" id="file_jaminan_um" accept=".pdf">
                                    <small class="text-muted d-block mt-1"><i class="bi bi-info-circle me-1"></i>Unggah dokumen jaminan uang muka.</small>
                                </div>

                                {{-- SKEMA TERMIN DINAMIS --}}
                                <div class="col-12" id="wrapper_termin" style="display: none;">
                                    <div class="termin-subcard">
                                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                            <div>
                                                <h6 class="fw-bold mb-1 text-dark"><i class="bi bi-list-columns-reverse text-primary me-1"></i>Rincian Termin Progress</h6>
                                                <small class="text-muted">Sistem akan otomatis menambahkan baris pelunasan dan retensi (jika diaktifkan).</small>
                                            </div>
                                            <button type="button" class="btn-add-termin" id="btnTambahTermin" onclick="tambahRowProgress()">
                                                <i class="bi bi-plus-lg"></i> Tambah Progress
                                            </button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table mb-2" id="tabelTermin">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center" style="width:60px;">#</th>
                                                        <th>Keterangan Progress</th>
                                                        <th class="text-center" style="width:140px;">Persentase</th>
                                                        <th>Nilai Bruto</th>
                                                        <th>Preview Angsuran UM</th>
                                                        <th class="text-center" style="width:50px;"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="bodyTermin">
                                                    @for ($i = 0; $i < $progressRowCount; $i++)
                                                        <tr class="termin-row" data-index="{{ $i + 1 }}">
                                                            <td class="text-center termin-nomor fw-bold align-middle">{{ $i + 1 }}</td>
                                                            <td>
                                                                <input type="text" class="form-control" name="progress_keterangan[]" placeholder="Contoh: Progress Tahap {{ $i + 1 }}" value="{{ $oldProgressKeterangan[$i] ?? '' }}" required>
                                                            </td>
                                                            <td>
                                                                <div class="input-group">
                                                                    <input type="number" class="form-control termin-persen text-center" name="progress_persentase[]" placeholder="0" min="0.01" max="100" step="0.0001" value="{{ $oldProgressPersentase[$i] ?? '' }}" required oninput="kalkulasiTotalTermin()">
                                                                    <span class="input-group-text">%</span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <input type="text" class="form-control bg-light termin-nilai-display fw-bold text-success" placeholder="Rp 0" readonly>
                                                            </td>
                                                            <td>
                                                                <input type="text" class="form-control bg-light termin-potongan-um-display fw-bold" placeholder="Rp 0" readonly style="color:#b45309;">
                                                            </td>
                                                            <td class="text-center align-middle">
                                                                <button type="button" class="btn-del-termin btn-hapus-termin" onclick="hapusRowProgress(this)" {{ $progressRowCount === 1 ? 'disabled' : '' }} title="Hapus baris">
                                                                    <i class="bi bi-trash3"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endfor
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="row g-3 mt-1">
                                            <div class="col-12" id="wrapper_toggle_retensi">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="gunakan_retensi" name="gunakan_retensi" value="1" {{ old('gunakan_retensi') ? 'checked' : '' }} onchange="toggleRetensiFields()">
                                                    <label class="form-check-label fw-bold" for="gunakan_retensi">Kontrak ini menggunakan retensi?</label>
                                                    <div class="form-text small">Standar Perpres 12/2021: 5% dari nilai kontrak, ditahan sampai masa pemeliharaan selesai.</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row g-3 mt-1" id="wrapper_retensi_fields" style="display: none;">
                                            <div class="col-lg-4">
                                                <label class="form-label modern"><i class="bi bi-pen"></i> Keterangan Retensi <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control modern" id="retensi_keterangan" name="retensi_keterangan" value="{{ old('retensi_keterangan', 'Retensi Masa Pemeliharaan') }}" placeholder="Contoh: Retensi masa pemeliharaan">
                                            </div>
                                            <div class="col-lg-4">
                                                <label class="form-label modern"><i class="bi bi-percent"></i> Retensi (%) <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control text-center" id="retensi_persentase" name="retensi_persentase" placeholder="5" min="0.01" max="100" step="0.0001" value="{{ old('retensi_persentase') }}" oninput="kalkulasiTotalTermin(); validasiRangeRetensi();">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                                <small class="text-warning d-none mt-1" id="retensi_warning"><i class="bi bi-exclamation-triangle-fill me-1"></i>Retensi umumnya 5–10% dari nilai kontrak.</small>
                                            </div>
                                            <div class="col-lg-4">
                                                <label class="form-label modern"><i class="bi bi-shield-lock"></i> Nilai Retensi (Rp)</label>
                                                <input type="text" class="form-control bg-light fw-bold" id="retensi_nilai_display" placeholder="Rp 0" readonly style="color:#b91c1c;">
                                            </div>
                                        </div>

                                        <div class="preview-card">
                                            <h6>Preview Termin Otomatis</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm align-middle mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Jenis</th>
                                                            <th class="text-center">Persentase</th>
                                                            <th class="text-end">Nilai</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td class="fw-semibold text-primary">Pelunasan (otomatis)</td>
                                                            <td class="text-center"><span id="pelunasan_persen_display">0%</span></td>
                                                            <td class="text-end fw-bold text-success" id="pelunasan_nilai_display">Rp 0</td>
                                                        </tr>
                                                        <tr id="retensi_preview_row">
                                                            <td class="fw-semibold text-danger">Retensi</td>
                                                            <td class="text-center"><span id="retensi_persen_display">0%</span></td>
                                                            <td class="text-end fw-bold text-danger" id="retensi_preview_display">Rp 0</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="preview-um d-none" id="wrapper_preview_angsuran_um">
                                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                                <div>
                                                    <h6 class="mb-1">Preview Estimasi Potongan Angsuran Uang Muka</h6>
                                                    <small class="text-muted">Informasi ini hanya preview estimasi. Nilai final dihitung saat penagihan termin dibuat.</small>
                                                </div>
                                                <span class="badge" style="background: rgba(245,158,11,.18); color:#b45309;">Estimasi</span>
                                            </div>
                                            <div class="row g-3 mt-1">
                                                <div class="col-md-4">
                                                    <div class="pu-stat">
                                                        <div class="pu-label">Rasio Uang Muka</div>
                                                        <div class="pu-value" id="rasio_uang_muka_display" style="color:#1e293b;">0%</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="pu-stat">
                                                        <div class="pu-label">Estimasi Potongan pada Progress</div>
                                                        <div class="pu-value" id="total_estimasi_um_progress_display" style="color:#b45309;">Rp 0</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="pu-stat">
                                                        <div class="pu-label">Sisa Tertutup di Pelunasan</div>
                                                        <div class="pu-value" id="sisa_estimasi_um_pelunasan_display" style="color:#047857;">Rp 0</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between px-2 pt-3 border-top mt-3">
                                            <small id="termin_peringatan">Total Progress + Retensi: <strong id="total_persen_display">0%</strong></small>
                                            <small class="fw-bold">Total Nilai Progress: <strong class="text-success" id="total_nilai_termin_display">Rp 0</strong></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            {{-- E: Penandatangan Kontrak --}}
            <div class="col-12">
                <div class="sec-card">
                    <div class="sec-head">
                        <div class="sec-icon si-secondary"><i class="bi bi-person-badge-fill"></i></div>
                        <div class="flex-grow-1">
                            <h6>Penandatangan Kontrak</h6>
                            <small>Pilih PPK yang akan menandatangani dokumen kontrak</small>
                        </div>
                        <span class="sec-letter">Step E</span>
                    </div>
                    <div class="sec-body">
                        <div class="row g-4">
                            <div class="col-md-8">
                                <label class="form-label modern"><i class="bi bi-person-check"></i> Pilih PPK <span class="text-danger">*</span></label>
                                <select class="form-select select2" id="ppk_user_id" name="ppk_user_id" required>
                                    <option value="">-- Cari PPK --</option>
                                    @foreach($ppkUsers as $ppkUser)
                                        <option
                                            value="{{ $ppkUser->id }}"
                                            data-nama="{{ $ppkUser->pegawai->nama_lengkap ?? $ppkUser->name }}"
                                            data-nip="{{ $ppkUser->pegawai->nip ?? '' }}"
                                            {{ old('ppk_user_id') == $ppkUser->id ? 'selected' : '' }}
                                        >
                                            {{ $ppkUser->pegawai->nama_lengkap ?? $ppkUser->name }} - {{ $ppkUser->pegawai->nip ?? 'NIP belum diisi' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="info-banner banner-secondary">
                                    <i class="bi bi-info-circle-fill"></i>
                                    <span>PPK yang dipilih akan menjadi penandatangan dokumen kontrak sekaligus verifikator kontrak saat dokumen diajukan.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Submit Bar --}}
        <div class="submit-bar">
            <button type="reset" class="btn-cancel-submit">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Form
            </button>
            <button type="submit" class="btn-submit-primary">
                <i class="bi bi-cloud-check-fill"></i> Simpan Kontrak
            </button>
        </div>
    </form>
@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Initialize Select2 with custom theme
        $('.select2').select2({
            theme: 'default',
            width: '100%'
        });

        // Toggle Uang Muka On Load
        toggleUangMuka();

        document.querySelectorAll('.rupiah-input').forEach(input => {
            // Inisialisasi awal jika ada error server / old value
            let hiddenInput = document.getElementById(input.id.replace('_display', '_value'));
            if(hiddenInput.value && hiddenInput.value > 0) {
                input.value = formatRupiah(hiddenInput.value);
            }

            input.addEventListener('input', function(e) {
                let cleanValue = this.value.replace(/[^,\d]/g, '');
                hiddenInput.value = cleanValue;
                this.value = formatRupiah(cleanValue);
                if (this.id === 'nilai_total_kontrak_display') {
                    validasiUangMuka();
                    validasiSisaPagu();
                    kalkulasiTotalTermin();
                } else if (this.id === 'nilai_uang_muka_display') {
                    validasiUangMuka();
                    kalkulasiTotalTermin();
                }
            });
        });

        $('#dipa_revision_item_id').on('change', function() {
            validasiSisaPagu();
        });
    });

    function formatRupiah(angka, prefix = 'Rp '){
        let number_string = angka.replace(/[^,\d]/g, '').toString(),
        split   		= number_string.split(','),
        sisa     		= split[0].length % 3,
        rupiah     		= split[0].substr(0, sisa),
        ribuan     		= split[0].substr(sisa).match(/\d{3}/gi);

        if(ribuan){
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return prefix == undefined ? rupiah : (rupiah ? 'Rp ' + rupiah : '');
    }

    function toggleUangMuka() {
        let metodeDipilih = document.querySelector('input[name="metode_pembayaran"]:checked')?.value;
        let wrapperToggle = document.getElementById('wrapper_toggle_uang_muka');
        let checkbox = document.getElementById('ada_uang_muka');
        let isChecked = document.getElementById('ada_uang_muka').checked;
        let wrapper = document.getElementById('wrapper_uang_muka');
        let wrapperFileJaminan = document.getElementById('wrapper_file_jaminan_um');
        let wrapperPreview = document.getElementById('wrapper_preview_angsuran_um');
        let inputDisplay = document.getElementById('nilai_uang_muka_display');
        let inputValue = document.getElementById('nilai_uang_muka_value');
        let inputFileJaminan = document.getElementById('file_jaminan_um');

        if (metodeDipilih !== 'TERMIN') {
            wrapperToggle.style.display = 'none';
            checkbox.checked = false;
            isChecked = false;
        } else {
            wrapperToggle.style.display = 'block';
        }

        if (isChecked) {
            wrapper.style.display = 'block';
            wrapperFileJaminan.style.display = 'block';
            inputDisplay.required = true;
        } else {
            wrapper.style.display = 'none';
            wrapperFileJaminan.style.display = 'none';
            inputDisplay.required = false;
            inputDisplay.value = '';
            inputValue.value = 0;
            inputFileJaminan.value = '';
            wrapperPreview.classList.add('d-none');
            document.getElementById('uang_muka_error').classList.add('d-none');
        }
        kalkulasiTotalTermin();
    }

    function validasiUangMuka() {
        let total = parseFloat(document.getElementById('nilai_total_kontrak_value').value) || 0;
        let dp = parseFloat(document.getElementById('nilai_uang_muka_value').value) || 0;
        let errEl = document.getElementById('uang_muka_error');

        if (total > 0 && dp > (total * 0.3)) {
            errEl.classList.remove('d-none');
        } else {
            errEl.classList.add('d-none');
        }
    }

    function validasiSisaPagu() {
        let selectCoa = document.getElementById('dipa_revision_item_id');
        let selectedOption = selectCoa.options[selectCoa.selectedIndex];
        let total = parseFloat(document.getElementById('nilai_total_kontrak_value').value) || 0;
        let sisaPagu = selectedOption ? parseFloat(selectedOption.getAttribute('data-sisa-pagu')) || 0 : 0;
        let btnSubmit = document.querySelector('button[type="submit"]');
        let errPaguEl = document.getElementById('pagu_error');

        if (total > 0 && selectedOption && selectedOption.value !== "") {
            if (total > sisaPagu) {
                if (errPaguEl) {
                    errPaguEl.classList.remove('d-none');
                    errPaguEl.innerText = "Peringatan: Nilai Kontrak (Rp " + formatRupiah(total.toString(), '') + ") melebihi sisa pagu COA yang tersedia (Rp " + formatRupiah(sisaPagu.toString(), '') + ").";
                }
                btnSubmit.disabled = true;
            } else {
                if (errPaguEl) errPaguEl.classList.add('d-none');
                btnSubmit.disabled = false;
            }
        } else {
            if (errPaguEl) errPaguEl.classList.add('d-none');
            btnSubmit.disabled = false;
        }
    }

    function hitungTanggalSelesai() {
        let tglMulai = document.getElementById('tanggal_mulai').value;
        let satuan = document.getElementById('satuan_waktu').value;
        let jangka = parseInt(document.getElementById('jangka_waktu').value);

        if (tglMulai && jangka > 0) {
            let nDate = new Date(tglMulai);
            
            if (satuan === 'HARI') {
                nDate.setDate(nDate.getDate() + jangka);
            } else if (satuan === 'MINGGU') {
                nDate.setDate(nDate.getDate() + (jangka * 7));
            } else if (satuan === 'BULAN') {
                nDate.setMonth(nDate.getMonth() + jangka);
            }
            
            let dd = String(nDate.getDate()).padStart(2, '0');
            let mm = String(nDate.getMonth() + 1).padStart(2, '0');
            let yyyy = nDate.getFullYear();

            document.getElementById('tanggal_selesai').value = yyyy + '-' + mm + '-' + dd;
        } else {
            document.getElementById('tanggal_selesai').value = '';
        }
    }

    function hitungTanggalSelesaiPemeliharaan() {
        let tglMulai = document.getElementById('tanggal_mulai_pemeliharaan').value;
        let lamaHari = parseInt(document.getElementById('masa_pemeliharaan_hari').value);

        if (tglMulai && !isNaN(lamaHari) && lamaHari >= 0) {
            let nDate = new Date(tglMulai);
            nDate.setDate(nDate.getDate() + lamaHari);

            let dd = String(nDate.getDate()).padStart(2, '0');
            let mm = String(nDate.getMonth() + 1).padStart(2, '0');
            let yyyy = nDate.getFullYear();
            document.getElementById('tanggal_selesai_pemeliharaan').value = yyyy + '-' + mm + '-' + dd;
        } else {
            document.getElementById('tanggal_selesai_pemeliharaan').value = '';
        }
    }

    // --- LOGIKA SKEMA TERMIN DINAMIS ---
    document.querySelectorAll('input[name="metode_pembayaran"]').forEach(radio => {
        radio.addEventListener('change', toggleTerminWrapper);
    });

    function updateNomorTermin() {
        let rows = document.querySelectorAll('.termin-row');
        rows.forEach((row, index) => {
            row.querySelector('.termin-nomor').innerText = index + 1;
            row.setAttribute('data-index', index + 1);
        });

        document.querySelectorAll('.btn-hapus-termin').forEach(button => {
            button.disabled = rows.length === 1;
        });
    }

    function toggleTerminWrapper() {
        let wp = document.getElementById('wrapper_termin');
        let isTermin = document.querySelector('input[name="metode_pembayaran"]:checked').value === 'TERMIN';
        let retensiInput = document.getElementById('retensi_persentase');
        let retensiKeterangan = document.getElementById('retensi_keterangan');
        let progressInputs = document.querySelectorAll('input[name="progress_keterangan[]"], input[name="progress_persentase[]"]');

        wp.style.display = isTermin ? 'block' : 'none';
        
        if (!isTermin) {
            document.getElementById('gunakan_retensi').checked = false;
        }

        retensiInput.required = isTermin;
        retensiKeterangan.required = isTermin;
        progressInputs.forEach(input => {
            input.required = isTermin;
        });

        toggleUangMuka();
        toggleRetensiFields();
        kalkulasiTotalTermin();
    }

    function toggleRetensiFields() {
        let isTermin = document.querySelector('input[name="metode_pembayaran"]:checked').value === 'TERMIN';
        let gunakanRetensi = document.getElementById('gunakan_retensi').checked;
        let wrapperToggleRetensi = document.getElementById('wrapper_toggle_retensi');
        let wrapperRetensiFields = document.getElementById('wrapper_retensi_fields');
        let retensiInput = document.getElementById('retensi_persentase');
        let retensiKeterangan = document.getElementById('retensi_keterangan');
        let retensiPreviewRow = document.getElementById('retensi_preview_row');

        wrapperToggleRetensi.style.display = isTermin ? 'block' : 'none';

        if (isTermin && gunakanRetensi) {
            wrapperRetensiFields.style.display = 'flex';
            retensiInput.required = true;
            retensiKeterangan.required = true;
            retensiPreviewRow.classList.remove('d-none');
            // Auto-fill 5% (default Perpres 12/2021) jika field kosong agar user tinggal konfirmasi.
            if (! retensiInput.value || parseFloat(retensiInput.value) <= 0) {
                retensiInput.value = '5';
                kalkulasiTotalTermin();
            }
        } else {
            wrapperRetensiFields.style.display = 'none';
            retensiInput.required = false;
            retensiKeterangan.required = false;
            retensiInput.value = '';
            retensiPreviewRow.classList.add('d-none');
        }
        validasiRangeRetensi();
    }

    function validasiRangeRetensi() {
        let warning = document.getElementById('retensi_warning');
        let gunakanRetensi = document.getElementById('gunakan_retensi').checked;
        let nilai = parseFloat(document.getElementById('retensi_persentase').value) || 0;

        if (warning) {
            warning.classList.toggle('d-none', !(gunakanRetensi && nilai > 10));
        }
    }

    function tambahRowProgress() {
        let tbody = document.getElementById('bodyTermin');
        let rowNumber = tbody.querySelectorAll('.termin-row').length + 1;
        let newRow = document.createElement('tr');
        newRow.className = 'termin-row';
        newRow.setAttribute('data-index', rowNumber);
        newRow.innerHTML = `
            <td class="text-center termin-nomor fw-bold align-middle">${rowNumber}</td>
            <td>
                <input type="text" class="form-control" name="progress_keterangan[]" placeholder="Contoh: Progress Tahap ${rowNumber}" required>
            </td>
            <td>
                <div class="input-group">
                    <input type="number" class="form-control termin-persen text-center" name="progress_persentase[]" placeholder="0" min="0.01" max="100" step="0.0001" required oninput="kalkulasiTotalTermin()">
                    <span class="input-group-text">%</span>
                </div>
            </td>
            <td>
                <input type="text" class="form-control bg-light termin-nilai-display fw-bold text-success" placeholder="Rp 0" readonly>
            </td>
            <td>
                <input type="text" class="form-control bg-light termin-potongan-um-display fw-bold text-warning" placeholder="Rp 0" readonly>
            </td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-termin" onclick="hapusRowProgress(this)"><i class="bi bi-trash"></i></button>
            </td>
        `;

        tbody.appendChild(newRow);
        updateNomorTermin();
        toggleTerminWrapper();
    }

    function hapusRowProgress(button) {
        let rows = document.querySelectorAll('.termin-row');
        if (rows.length === 1) {
            return;
        }

        button.closest('.termin-row').remove();
        updateNomorTermin();
        kalkulasiTotalTermin();
    }

    function kalkulasiTotalTermin() {
        let methodIsTermin = document.querySelector('input[name="metode_pembayaran"]:checked').value === 'TERMIN';
        let gunakanRetensi = document.getElementById('gunakan_retensi').checked;
        if (!methodIsTermin) {
            document.getElementById('total_persen_display').innerText = '0%';
            document.getElementById('total_nilai_termin_display').innerText = 'Rp 0';
            document.getElementById('pelunasan_persen_display').innerText = '0%';
            document.getElementById('pelunasan_nilai_display').innerText = 'Rp 0';
            document.getElementById('retensi_persen_display').innerText = '0%';
            document.getElementById('retensi_preview_display').innerText = 'Rp 0';
            document.getElementById('retensi_nilai_display').value = '';
            document.querySelectorAll('.termin-potongan-um-display').forEach(input => input.value = '');
            document.getElementById('wrapper_preview_angsuran_um').classList.add('d-none');
            document.getElementById('rasio_uang_muka_display').innerText = '0%';
            document.getElementById('total_estimasi_um_progress_display').innerText = 'Rp 0';
            document.getElementById('sisa_estimasi_um_pelunasan_display').innerText = 'Rp 0';
            return;
        }

        let totalKontrakStr = document.getElementById('nilai_total_kontrak_value').value || 0;
        let totalKontrak = parseFloat(totalKontrakStr);
        let nilaiUangMuka = parseFloat(document.getElementById('nilai_uang_muka_value').value || 0);
        let rasioUangMuka = totalKontrak > 0 ? (nilaiUangMuka / totalKontrak) : 0;
        let shouldShowPreviewUm = methodIsTermin && document.getElementById('ada_uang_muka').checked && nilaiUangMuka > 0;
        let totalProgressPersen = 0;
        let totalProgressNilai = 0;
        let totalEstimasiPotonganUmProgress = 0;
        let rows = document.querySelectorAll('.termin-row');

        rows.forEach(row => {
            let persenInput = row.querySelector('.termin-persen');
            let dispVal = row.querySelector('.termin-nilai-display');
            let dispPotonganUm = row.querySelector('.termin-potongan-um-display');
            let p = parseFloat(persenInput.value) || 0;
            let n = totalKontrak > 0 && p > 0 ? ((p / 100) * totalKontrak) : 0;
            let estimasiPotongan = shouldShowPreviewUm ? (n * rasioUangMuka) : 0;

            dispVal.value = formatRupiah(Math.round(n).toString(), 'Rp ');
            dispPotonganUm.value = shouldShowPreviewUm ? formatRupiah(Math.round(estimasiPotongan).toString(), 'Rp ') : '';
            totalProgressPersen += p;
            totalProgressNilai += n;
            totalEstimasiPotonganUmProgress += estimasiPotongan;
        });

        let retensiPersen = gunakanRetensi ? (parseFloat(document.getElementById('retensi_persentase').value) || 0) : 0;
        let retensiNilai = totalKontrak > 0 && retensiPersen > 0 ? ((retensiPersen / 100) * totalKontrak) : 0;
        let pelunasanPersen = 100 - totalProgressPersen - retensiPersen;
        let pelunasanNilai = totalKontrak > 0 ? ((pelunasanPersen / 100) * totalKontrak) : 0;
        let totalPersen = totalProgressPersen + retensiPersen;
        document.getElementById('termin_peringatan').innerHTML = gunakanRetensi
            ? 'Total Progress + Retensi: <strong id="total_persen_display">' + totalPersen + '%</strong>'
            : 'Total Progress: <strong id="total_persen_display">' + totalPersen + '%</strong>';

        let dispPersen = document.getElementById('total_persen_display');
        dispPersen.innerText = totalPersen + '%';
        if (totalPersen > 100) {
            dispPersen.className = 'text-danger fw-bold';
        } else if (Math.abs(totalPersen - 100) < 0.0001) {
            dispPersen.className = 'text-success fw-bold';
        } else {
            dispPersen.className = 'text-warning text-dark fw-bold';
        }

        document.getElementById('total_nilai_termin_display').innerText = formatRupiah(Math.round(totalProgressNilai).toString(), 'Rp ');
        document.getElementById('retensi_nilai_display').value = gunakanRetensi ? formatRupiah(Math.round(retensiNilai).toString(), 'Rp ') : '';
        document.getElementById('retensi_persen_display').innerText = gunakanRetensi ? (retensiPersen + '%') : '0%';
        document.getElementById('retensi_preview_display').innerText = gunakanRetensi ? formatRupiah(Math.round(retensiNilai).toString(), 'Rp ') : 'Rp 0';
        document.getElementById('pelunasan_persen_display').innerText = pelunasanPersen.toFixed(4).replace(/\.?0+$/, '') + '%';
        document.getElementById('pelunasan_nilai_display').innerText = formatRupiah(Math.round(Math.max(pelunasanNilai, 0)).toString(), 'Rp ');

        let wrapperPreview = document.getElementById('wrapper_preview_angsuran_um');
        let sisaEstimasiPelunasan = Math.max(nilaiUangMuka - totalEstimasiPotonganUmProgress, 0);

        if (shouldShowPreviewUm) {
            wrapperPreview.classList.remove('d-none');
            document.getElementById('rasio_uang_muka_display').innerText = (rasioUangMuka * 100).toFixed(2).replace(/\.?0+$/, '') + '%';
            document.getElementById('total_estimasi_um_progress_display').innerText = formatRupiah(Math.round(totalEstimasiPotonganUmProgress).toString(), 'Rp ');
            document.getElementById('sisa_estimasi_um_pelunasan_display').innerText = formatRupiah(Math.round(sisaEstimasiPelunasan).toString(), 'Rp ');
        } else {
            wrapperPreview.classList.add('d-none');
            document.getElementById('rasio_uang_muka_display').innerText = '0%';
            document.getElementById('total_estimasi_um_progress_display').innerText = 'Rp 0';
            document.getElementById('sisa_estimasi_um_pelunasan_display').innerText = 'Rp 0';
        }
    }

    document.getElementById('gunakan_retensi').addEventListener('change', function() {
        toggleRetensiFields();
        kalkulasiTotalTermin();
    });

    toggleTerminWrapper();
    toggleRetensiFields();
    updateNomorTermin();
    hitungTanggalSelesaiPemeliharaan();
    kalkulasiTotalTermin();
    validasiSisaPagu();
</script>
@endpush
