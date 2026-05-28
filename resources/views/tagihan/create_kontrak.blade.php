@extends('layouts.app')
@section('title')
    Buat Tagihan (Kontrak & BAST)
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
        font-weight: 800; font-size: 1.55rem;
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
    .receipt-illust {
        position: absolute;
        right: 1.5rem; top: 50%;
        transform: translateY(-50%) rotate(-8deg);
        font-size: 7rem; opacity: .14;
    }
    .btn-back-hero {
        background: rgba(255,255,255,.18);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.30);
        color: #fff; font-weight: 600;
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
        transition: box-shadow .25s ease;
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
    .si-danger  { --si-bg: linear-gradient(135deg, #fb7185, #f43f5e); --si-shadow: rgba(244,63,94,.30); }

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
    .sec-card input[type="text"]:not(.select2-search__field):not([readonly]),
    .sec-card input[type="number"],
    .sec-card input[type="date"],
    .sec-card input[type="file"],
    .sec-card textarea,
    .sec-card select:not(.select2-hidden-accessible) {
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        border-radius: .65rem;
        padding: .58rem .85rem;
        font-size: .9rem;
        transition: all .2s ease;
    }
    .sec-card input.bg-light,
    .sec-card .form-control.bg-light,
    .sec-card input[readonly] {
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
    .sec-card input[type="file"]:focus,
    .sec-card textarea:focus,
    .sec-card select:focus {
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
    .select2-results__options {
        padding: .35rem !important;
        max-height: 280px !important;
        overflow-y: auto !important;
    }
    .select2-results__options::-webkit-scrollbar { width: 8px; }
    .select2-results__options::-webkit-scrollbar-track { background: #f8fafc; }
    .select2-results__options::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
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
    .select2-container--default .select2-results__option--disabled {
        color: #94a3b8 !important;
        font-style: italic;
        text-align: center;
        padding: 1rem !important;
    }
    .form-select.modern {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%236366f1' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M2 5l6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right .85rem center;
        background-size: 14px 14px;
        padding-right: 2.5rem;
    }

    /* ============ Info banner ============ */
    .info-banner {
        background: linear-gradient(135deg, rgba(99,102,241,.06), rgba(99,102,241,.02));
        border: 1px solid rgba(99,102,241,.20);
        border-left: 4px solid #6366f1;
        border-radius: .75rem;
        padding: .75rem 1rem;
        font-size: .82rem;
        color: #475569;
        display: flex; gap: .55rem; align-items: flex-start;
    }
    .info-banner i { color: #4f46e5; font-size: 1.1rem; flex-shrink: 0; }
    .info-banner.banner-info {
        background: linear-gradient(135deg, rgba(14,165,233,.06), rgba(14,165,233,.02));
        border-color: rgba(14,165,233,.20);
        border-left-color: #0ea5e9;
        color: #0369a1;
    }
    .info-banner.banner-info i { color: #0ea5e9; }
    .info-banner.banner-warning {
        background: linear-gradient(135deg, rgba(245,158,11,.06), rgba(245,158,11,.02));
        border-color: rgba(245,158,11,.25);
        border-left-color: #f59e0b;
        color: #92400e;
    }
    .info-banner.banner-warning i { color: #b45309; }

    /* ============ Preset Card (kontrak/termin selected) ============ */
    .preset-card {
        position: relative;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 0;
        height: 100%;
        overflow: hidden;
        box-shadow: 0 4px 14px rgba(15,23,42,.04);
        transition: box-shadow .25s ease, transform .25s ease;
    }
    .preset-card:hover {
        box-shadow: 0 12px 28px rgba(15,23,42,.08);
        transform: translateY(-1px);
    }
    .preset-card::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 4px;
        background: linear-gradient(180deg, #6366f1, #8b5cf6);
    }
    .preset-card.is-success::before {
        background: linear-gradient(180deg, #10b981, #34d399);
    }

    .preset-card .pc-head {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .85rem 1.15rem;
        background: linear-gradient(135deg, rgba(99,102,241,.06), rgba(139,92,246,.03));
        border-bottom: 1px solid #f1f5f9;
    }
    .preset-card.is-success .pc-head {
        background: linear-gradient(135deg, rgba(16,185,129,.06), rgba(52,211,153,.03));
    }
    .preset-card .pc-head .pc-icon {
        width: 32px; height: 32px;
        border-radius: 9px;
        background: linear-gradient(135deg, #818cf8, #6366f1);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .9rem;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(99,102,241,.30);
    }
    .preset-card.is-success .pc-head .pc-icon {
        background: linear-gradient(135deg, #34d399, #10b981);
        box-shadow: 0 4px 10px rgba(16,185,129,.30);
    }
    .preset-card .pc-head .pc-title {
        font-weight: 700;
        font-size: .85rem;
        color: #0f172a;
        line-height: 1.2;
    }
    .preset-card .pc-head .pc-sub {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #6366f1;
        margin-top: .15rem;
    }
    .preset-card.is-success .pc-head .pc-sub {
        color: #047857;
    }

    .preset-card .pc-body {
        padding: .85rem 1.15rem .35rem;
    }
    .preset-card .pc-row {
        display: grid;
        grid-template-columns: 110px 1fr;
        gap: .75rem;
        align-items: baseline;
        padding: .5rem 0;
        border-bottom: 1px dashed #eef2f7;
    }
    .preset-card .pc-row:last-child { border-bottom: 0; }
    .preset-card .pc-label {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
        margin-bottom: 0;
    }
    .preset-card .pc-value {
        font-weight: 600;
        color: #0f172a;
        font-size: .88rem;
        margin-bottom: 0;
        word-break: break-word;
    }
    .preset-card .pc-value.pc-mono {
        font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
        font-weight: 700;
        letter-spacing: -.005em;
    }

    .preset-card .pc-foot {
        margin: .35rem 1.15rem 1rem;
        padding: .8rem 1rem;
        background: linear-gradient(135deg, rgba(16,185,129,.08), rgba(16,185,129,.02));
        border: 1px solid rgba(16,185,129,.20);
        border-radius: .75rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
    }
    .preset-card .pc-foot .pc-foot-label {
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #047857;
    }
    .preset-card .pc-money {
        font-size: 1.25rem;
        font-weight: 800;
        color: #047857;
        font-variant-numeric: tabular-nums;
        letter-spacing: -.01em;
        margin: 0;
    }

    /* ============ Auto-generated number callout ============ */
    .auto-gen {
        background: rgba(99,102,241,.06);
        border: 1px dashed rgba(99,102,241,.30);
        border-radius: .55rem;
        padding: .5rem .75rem;
        font-size: .78rem;
        color: #475569;
        margin-bottom: .5rem;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .auto-gen i { color: #6366f1; }
    .auto-gen strong { color: #4338ca; font-family: ui-monospace, "SF Mono", monospace; }

    /* ============ Nominal big card ============ */
    .nominal-card {
        background: linear-gradient(135deg, #fafbff 0%, #fff 100%);
        border: 1px solid rgba(16,185,129,.25);
        border-radius: .85rem;
        padding: 1rem 1.15rem;
    }
    .nominal-card .nc-label {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #047857;
        margin-bottom: .25rem;
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
        text-decoration: none;
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

    /* ============ Modern File Dropzone ============ */
    .file-drop {
        position: relative;
        display: block;
        border: 2px dashed #cbd5e1;
        border-radius: 1rem;
        background:
            radial-gradient(120% 100% at 0% 0%, rgba(99,102,241,.05), transparent 55%),
            radial-gradient(120% 100% at 100% 100%, rgba(236,72,153,.04), transparent 55%),
            #fafbff;
        padding: 1.4rem 1.15rem;
        text-align: center;
        cursor: pointer;
        transition: all .25s ease;
        overflow: hidden;
    }
    .file-drop input[type="file"] {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }
    .file-drop:hover {
        border-color: #818cf8;
        background:
            radial-gradient(120% 100% at 0% 0%, rgba(99,102,241,.10), transparent 55%),
            radial-gradient(120% 100% at 100% 100%, rgba(236,72,153,.07), transparent 55%),
            #ffffff;
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(99,102,241,.10);
    }
    .file-drop.is-drag {
        border-color: #6366f1;
        border-style: solid;
        background:
            linear-gradient(135deg, rgba(99,102,241,.08), rgba(139,92,246,.06)),
            #ffffff;
        box-shadow: 0 0 0 4px rgba(99,102,241,.12), 0 14px 30px rgba(99,102,241,.15);
        transform: scale(1.01);
    }
    .file-drop.is-filled {
        border-style: solid;
        border-color: #34d399;
        background: linear-gradient(135deg, rgba(16,185,129,.06), rgba(52,211,153,.02)), #ffffff;
    }
    .file-drop.is-error {
        border-color: #f43f5e;
        background: linear-gradient(135deg, rgba(244,63,94,.06), rgba(220,38,38,.02)), #ffffff;
        animation: shake .55s cubic-bezier(.36,.07,.19,.97) both;
    }
    .file-drop .fd-icon {
        width: 56px; height: 56px;
        border-radius: 16px;
        background: linear-gradient(135deg, #818cf8, #6366f1);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.55rem;
        margin-bottom: .65rem;
        box-shadow: 0 8px 20px rgba(99,102,241,.30);
        transition: transform .3s ease, background .3s ease, box-shadow .3s ease;
    }
    .file-drop:hover .fd-icon { transform: translateY(-3px) rotate(-6deg); }
    .file-drop.is-filled .fd-icon {
        background: linear-gradient(135deg, #34d399, #10b981);
        box-shadow: 0 8px 20px rgba(16,185,129,.30);
    }
    .file-drop.is-error .fd-icon {
        background: linear-gradient(135deg, #fb7185, #f43f5e);
        box-shadow: 0 8px 20px rgba(244,63,94,.30);
    }
    .file-drop .fd-title {
        font-weight: 700;
        color: #0f172a;
        font-size: .95rem;
        margin-bottom: .15rem;
    }
    .file-drop .fd-title strong {
        background: linear-gradient(135deg, #6366f1, #ec4899);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .file-drop .fd-sub {
        color: #64748b;
        font-size: .78rem;
    }
    .file-drop .fd-meta {
        display: inline-flex;
        gap: .35rem;
        align-items: center;
        background: rgba(99,102,241,.08);
        color: #4338ca;
        font-weight: 600;
        font-size: .68rem;
        padding: .25rem .55rem;
        border-radius: 999px;
        margin-top: .55rem;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .file-drop.is-filled .fd-meta {
        background: rgba(16,185,129,.10);
        color: #047857;
    }

    /* Filled preview */
    .fd-preview {
        position: relative;
        display: flex;
        align-items: center;
        gap: .85rem;
        text-align: left;
        background: #ffffff;
        border-radius: .75rem;
        padding: .65rem .85rem;
        border: 1px solid rgba(16,185,129,.20);
        box-shadow: 0 6px 16px rgba(16,185,129,.08);
        z-index: 3;
    }
    .fd-preview .fp-icon {
        width: 44px; height: 44px;
        border-radius: 12px;
        background: linear-gradient(135deg, #fb7185, #ef4444);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
        box-shadow: 0 6px 14px rgba(239,68,68,.30);
    }
    .fd-preview .fp-icon.is-zip {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        box-shadow: 0 6px 14px rgba(245,158,11,.30);
    }
    .fd-preview .fp-icon.is-img {
        background: linear-gradient(135deg, #38bdf8, #0ea5e9);
        box-shadow: 0 6px 14px rgba(14,165,233,.30);
    }
    .fd-preview .fp-info { flex: 1 1 auto; min-width: 0; }
    .fd-preview .fp-name {
        font-weight: 700;
        color: #0f172a;
        font-size: .88rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .fd-preview .fp-detail {
        font-size: .72rem;
        color: #64748b;
        margin-top: .15rem;
        display: flex;
        gap: .55rem;
        align-items: center;
        flex-wrap: wrap;
    }
    .fd-preview .fp-detail .fp-size {
        font-weight: 600;
        color: #047857;
        background: rgba(16,185,129,.10);
        padding: .1rem .45rem;
        border-radius: 999px;
    }
    .fd-preview .fp-detail .fp-size.is-warn {
        color: #b45309;
        background: rgba(245,158,11,.12);
    }
    .fd-preview .fp-detail .fp-size.is-error {
        color: #b91c1c;
        background: rgba(244,63,94,.12);
    }
    .fd-preview .fp-remove {
        position: relative;
        z-index: 4;
        width: 32px; height: 32px;
        border-radius: 10px;
        border: 1px solid #fecaca;
        background: #fff5f5;
        color: #dc2626;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem;
        cursor: pointer;
        flex-shrink: 0;
        transition: all .18s ease;
    }
    .fd-preview .fp-remove:hover {
        background: #fee2e2;
        border-color: #fca5a5;
        transform: rotate(90deg);
    }
    .file-drop.is-filled .fd-default { display: none; }
    .file-drop:not(.is-filled) .fd-preview { display: none; }

    /* Tiny progress meter (file size visual) */
    .fp-bar {
        height: 4px;
        width: 100%;
        background: #f1f5f9;
        border-radius: 999px;
        overflow: hidden;
        margin-top: .35rem;
    }
    .fp-bar > span {
        display: block;
        height: 100%;
        background: linear-gradient(90deg, #34d399, #10b981);
        border-radius: 999px;
        transition: width .3s ease;
    }
    .fp-bar > span.is-warn { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
    .fp-bar > span.is-error { background: linear-gradient(90deg, #fb7185, #f43f5e); }

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
        $isPresetTagihan = isset($selectedKontrak, $selectedTermin) && $selectedKontrak && $selectedTermin;
        $initialPotonganAngsuran = old('potongan_angsuran_uang_muka', $selectedPotonganAngsuran ?? 0);
        $kontrakTerminMap = [];

        foreach (($kontraks ?? collect()) as $kontrakItem) {
            $terms = [];

            foreach ($kontrakItem->termin->where('status_termin', 'READY_TO_BILL') as $terminItem) {
                $potongan = 0;

                if (
                    $kontrakItem->ada_uang_muka &&
                    (float) $kontrakItem->sisa_uang_muka_belum_lunas > 0 &&
                    in_array($terminItem->jenis_termin, ['PROGRESS', 'PELUNASAN'], true)
                ) {
                    $potongan = min((float) $terminItem->potongan_angsuran_uang_muka, (float) $kontrakItem->sisa_uang_muka_belum_lunas);
                }

                $terms[] = [
                    'id' => $terminItem->id,
                    'keterangan_termin' => $terminItem->keterangan_termin,
                    'persentase' => $terminItem->persentase,
                    'nilai_bruto_termin' => $terminItem->nilai_bruto_termin,
                    'jenis_termin' => $terminItem->jenis_termin,
                    'potongan_angsuran_uang_muka' => round($potongan, 2),
                ];
            }

            $kontrakTerminMap[$kontrakItem->id] = [
                'vendor' => optional($kontrakItem->vendor)->nama_perusahaan ?? 'N/A',
                'nama' => $kontrakItem->nama_pekerjaan,
                'nilai' => $kontrakItem->nilai_total_kontrak,
                'terms' => $terms,
            ];
        }
    @endphp

    {{-- HERO --}}
    <div class="form-hero">
        <i class="bi bi-receipt-cutoff receipt-illust d-none d-md-block"></i>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="hero-tag"><i class="bi bi-stars"></i> Penagihan Kontrak</span>
                <h2><i class="bi bi-cash-stack me-2"></i>Penagihan Termin / BAST</h2>
                <p>Formulir pengajuan pembayaran berdasarkan prestasi pekerjaan SPK. Lengkapi data kontrak, BAST/BAP, verifikator, dan ringkasan nilai.</p>
            </div>
            <a href="{{ url()->previous() }}" class="btn-back-hero">
                <i class="bi bi-arrow-left"></i> Kembali
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

    <form action="{{ route('tagihan.kontrak.store') }}" method="POST" enctype="multipart/form-data" id="formTagihan">
        @csrf

        {{-- ============ A. Pemilihan Kontrak & Termin ============ --}}
        <div class="sec-card">
            <div class="sec-head">
                <span class="sec-icon si-primary"><i class="bi bi-file-earmark-text-fill"></i></span>
                <div>
                    <h6>Pemilihan Kontrak &amp; Termin</h6>
                    <small>Tentukan SPK dan termin yang akan ditagih.</small>
                </div>
                <span class="sec-letter">A</span>
            </div>
            <div class="sec-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        @if($isPresetTagihan)
                            <label class="form-label modern"><i class="bi bi-bookmark-check-fill text-primary"></i> Kontrak Terpilih</label>
                            <input type="hidden" name="kontrak_pengadaan_id" id="kontrak_pengadaan_id" value="{{ $selectedKontrak->id }}">
                            <div class="preset-card">
                                <div class="pc-head">
                                    <span class="pc-icon"><i class="bi bi-file-earmark-text-fill"></i></span>
                                    <div>
                                        <div class="pc-sub">Kontrak SPK</div>
                                        <div class="pc-title">{{ $selectedKontrak->nomor_spk }}</div>
                                    </div>
                                </div>
                                <div class="pc-body">
                                    <div class="pc-row">
                                        <div class="pc-label">Vendor</div>
                                        <div class="pc-value">{{ $selectedKontrak->vendor->nama_perusahaan ?? '-' }}</div>
                                    </div>
                                    <div class="pc-row">
                                        <div class="pc-label">Pekerjaan</div>
                                        <div class="pc-value" style="font-weight:500;">{{ $selectedKontrak->nama_pekerjaan }}</div>
                                    </div>
                                </div>
                                <div class="pc-foot">
                                    <span class="pc-foot-label"><i class="bi bi-cash-stack me-1"></i>Nilai Total Kontrak</span>
                                    <span class="pc-money">Rp {{ number_format($selectedKontrak->nilai_total_kontrak, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @else
                            <label class="form-label modern" for="kontrak_pengadaan_id">
                                <i class="bi bi-search text-primary"></i> Pilih Kontrak (Nomor SPK)
                                <span class="text-danger ms-1">*</span>
                            </label>
                            <select class="form-select select2" name="kontrak_pengadaan_id" id="kontrak_pengadaan_id" required onchange="getDetailKontrak(this.value)">
                                <option value="">-- Cari atau ketik Nomor SPK --</option>
                                @foreach($kontraks ?? [] as $k)
                                    <option value="{{ $k->id }}" data-vendor="{{ $k->vendor->nama_perusahaan ?? 'N/A' }}" data-nama="{{ $k->nama_pekerjaan }}" data-nilai="{{ $k->nilai_total_kontrak }}">
                                        {{ $k->nomor_spk }} - {{ Str::limit($k->nama_pekerjaan, 40) }}
                                    </option>
                                @endforeach
                            </select>

                            <div id="panel_info_kontrak" class="preset-card mt-3" style="display: none;">
                                <div class="pc-head">
                                    <span class="pc-icon"><i class="bi bi-file-earmark-text-fill"></i></span>
                                    <div>
                                        <div class="pc-sub">Detail Kontrak</div>
                                        <div class="pc-title">Ringkasan Vendor &amp; Pekerjaan</div>
                                    </div>
                                </div>
                                <div class="pc-body">
                                    <div class="pc-row">
                                        <div class="pc-label">Vendor</div>
                                        <div class="pc-value" id="info_vendor">-</div>
                                    </div>
                                    <div class="pc-row">
                                        <div class="pc-label">Pekerjaan</div>
                                        <div class="pc-value" style="font-weight:500;" id="info_pekerjaan">-</div>
                                    </div>
                                </div>
                                <div class="pc-foot">
                                    <span class="pc-foot-label"><i class="bi bi-cash-stack me-1"></i>Nilai Total Kontrak</span>
                                    <span class="pc-money" id="info_nilai">-</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="col-md-6">
                        @if($isPresetTagihan)
                            <label class="form-label modern"><i class="bi bi-collection-fill text-success"></i> Termin yang Akan Ditagih</label>
                            <input type="hidden" name="kontrak_termin_id" id="kontrak_termin_id" value="{{ $selectedTermin->id }}">
                            <div class="preset-card is-success">
                                <div class="pc-head">
                                    <span class="pc-icon"><i class="bi bi-collection-fill"></i></span>
                                    <div>
                                        <div class="pc-sub">Termin Aktif</div>
                                        <div class="pc-title">Termin {{ $selectedTermin->termin_ke }} &middot; {{ str_replace('_', ' ', $selectedTermin->jenis_termin) }}</div>
                                    </div>
                                </div>
                                <div class="pc-body">
                                    <div class="pc-row">
                                        <div class="pc-label">Keterangan</div>
                                        <div class="pc-value" style="font-weight:500;">{{ $selectedTermin->keterangan_termin }}</div>
                                    </div>
                                    @if(!is_null($selectedTermin->persentase ?? null))
                                        <div class="pc-row">
                                            <div class="pc-label">Persentase</div>
                                            <div class="pc-value pc-mono">{{ rtrim(rtrim(number_format($selectedTermin->persentase, 2, ',', '.'), '0'), ',') }}%</div>
                                        </div>
                                    @endif
                                </div>
                                <div class="pc-foot">
                                    <span class="pc-foot-label"><i class="bi bi-cash-stack me-1"></i>Nilai Bruto Termin</span>
                                    <span class="pc-money">Rp {{ number_format($selectedTermin->nilai_bruto_termin, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @else
                            <label class="form-label modern" for="kontrak_termin_id">
                                <i class="bi bi-collection-fill text-success"></i> Pilih Termin Tagihan
                                <span class="text-danger ms-1">*</span>
                            </label>
                            <select class="form-select select2" name="kontrak_termin_id" id="kontrak_termin_id" required disabled onchange="setBrutoFromTermin()">
                                <option value="">-- Pilih Kontrak Terlebih Dahulu --</option>
                            </select>
                            <div class="info-banner banner-info mt-3">
                                <i class="bi bi-info-circle-fill"></i>
                                <span>Hanya termin dengan status <strong>READY_TO_BILL</strong> yang akan tampil dalam daftar.</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ B. Legalitas Pekerjaan (Berita Acara) ============ --}}
        <div class="sec-card">
            <div class="sec-head">
                <span class="sec-icon si-info"><i class="bi bi-file-earmark-check-fill"></i></span>
                <div>
                    <h6>Legalitas Pekerjaan (Berita Acara)</h6>
                    <small>Tanggal BAPP, BAST, BAP dan data pemeriksa.</small>
                </div>
                <span class="sec-letter">B</span>
            </div>
            <div class="sec-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label modern"><i class="bi bi-clipboard2-check text-info"></i> Nomor BAPP <span class="text-muted fw-normal">(Pemeriksaan)</span></label>
                        <div class="auto-gen mb-2">
                            <i class="bi bi-magic"></i>
                            <span>Akan digenerate:</span>
                            <strong>{{ $previewBapp }}</strong>
                        </div>
                        <label class="form-label modern" style="font-size:.7rem;color:#94a3b8;">Tanggal BAPP</label>
                        <input type="date" class="form-control modern" name="tanggal_bapp" value="{{ old('tanggal_bapp', now()->format('Y-m-d')) }}">
                        <label class="form-label modern mt-3" for="gambar_rab_bapp">
                            <i class="bi bi-file-earmark-image text-success"></i> Gambar RAB
                            <span class="text-muted fw-normal ms-1">(Opsional)</span>
                        </label>
                        <label class="file-drop" data-accept=".jpg,.jpeg,.png" data-max-mb="5" data-target="gambar_rab_bapp">
                            <input type="file" id="gambar_rab_bapp" name="gambar_rab_bapp" accept=".jpg,.jpeg,.png">
                            <div class="fd-default">
                                <div class="fd-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                                <div class="fd-title">Tarik &amp; lepaskan, atau <strong>klik untuk memilih</strong></div>
                                <div class="fd-sub">Gambar RAB yang akan ditampilkan pada draft PDF BAPP.</div>
                                <div class="fd-meta"><i class="bi bi-file-earmark-image"></i> JPG / PNG &middot; Maks 5MB</div>
                            </div>
                            <div class="fd-preview">
                                <div class="fp-icon is-img"><i class="bi bi-file-earmark-image-fill"></i></div>
                                <div class="fp-info">
                                    <div class="fp-name">-</div>
                                    <div class="fp-detail">
                                        <span class="fp-size">0 KB</span>
                                        <span class="fp-type text-muted">Gambar</span>
                                    </div>
                                    <div class="fp-bar"><span style="width:0%"></span></div>
                                </div>
                                <button type="button" class="fp-remove" title="Hapus berkas"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </label>
                    </div>
                    <div class="col-md-4" id="wrapper_bast_fields" style="display: none;">
                        <label class="form-label modern"><i class="bi bi-truck text-warning"></i> Nomor BAST <span class="text-danger ms-1">*</span> <span class="text-muted fw-normal ms-1">(Serah Terima)</span></label>
                        <div class="auto-gen mb-2">
                            <i class="bi bi-magic"></i>
                            <span>Akan digenerate:</span>
                            <strong>{{ $previewBast }}</strong>
                        </div>
                        <label class="form-label modern" style="font-size:.7rem;color:#94a3b8;">Tanggal BAST</label>
                        <input type="date" class="form-control modern" name="tanggal_bast" id="tanggal_bast" value="{{ old('tanggal_bast', now()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4" id="wrapper_bap_fields">
                        <label class="form-label modern"><i class="bi bi-cash-coin text-success"></i> Nomor BAP <span class="text-danger ms-1">*</span> <span class="text-muted fw-normal ms-1">(Pembayaran)</span></label>
                        <div class="auto-gen mb-2">
                            <i class="bi bi-magic"></i>
                            <span>Akan digenerate:</span>
                            <strong>{{ $previewBap }}</strong>
                        </div>
                        <label class="form-label modern" style="font-size:.7rem;color:#94a3b8;">Tanggal BAP</label>
                        <input type="date" class="form-control modern" name="tanggal_bap" value="{{ old('tanggal_bap', now()->format('Y-m-d')) }}" required>
                    </div>

                    <div class="col-12">
                        <div class="d-flex align-items-center gap-2 mb-3 mt-2 pt-3" style="border-top:1px dashed #e2e8f0;">
                            <span class="badge" style="background:rgba(14,165,233,.10);color:#0369a1;font-weight:700;letter-spacing:.04em;padding:.4rem .75rem;border-radius:999px;">
                                <i class="bi bi-person-vcard me-1"></i> Pemeriksa Hasil Pekerjaan (BAPP)
                            </span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label modern" for="namaPemeriksaSelect">
                                    <i class="bi bi-person-badge text-primary"></i> Nama Pemeriksa
                                    <span class="text-danger ms-1">*</span>
                                </label>
                                <select class="form-select select2" name="nama_pemeriksa" id="namaPemeriksaSelect" required>
                                    <option value="">-- Pilih Pegawai --</option>
                                    @foreach($pegawaiList as $peg)
                                        <option
                                            value="{{ $peg->nama_lengkap }}"
                                            data-nip="{{ $peg->nip }}"
                                            data-jabatan="{{ $peg->jabatan }}"
                                            data-wa="{{ $peg->nomor_hp }}"
                                            @selected(old('nama_pemeriksa') === $peg->nama_lengkap)
                                        >{{ $peg->nama_lengkap }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1" style="font-size:.74rem;"><i class="bi bi-magic me-1"></i>NIP &amp; Jabatan otomatis.</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label modern"><i class="bi bi-hash text-secondary"></i> NIP Pemeriksa</label>
                                <input type="text" class="form-control modern" name="nip_pemeriksa" id="nipPemeriksaInput" placeholder="Akan terisi setelah memilih nama" value="{{ old('nip_pemeriksa') }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label modern"><i class="bi bi-briefcase text-secondary"></i> Jabatan Pemeriksa <span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control modern" name="jabatan_pemeriksa" id="jabatanPemeriksaInput" placeholder="Akan terisi setelah memilih nama" value="{{ old('jabatan_pemeriksa') }}" required readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label modern"><i class="bi bi-whatsapp text-success"></i> No. WA Pemeriksa <span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control modern" name="wa_pemeriksa" id="waPemeriksaInput" placeholder="Contoh: 0812..." value="{{ old('wa_pemeriksa') }}" required>
                                <small class="text-muted d-block mt-1" style="font-size:.74rem;">Digunakan untuk link TTE BAPP.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ C. Verifikator Penagihan ============ --}}
        <div class="sec-card">
            <div class="sec-head">
                <span class="sec-icon si-success"><i class="bi bi-people-fill"></i></span>
                <div>
                    <h6>Verifikator Penagihan</h6>
                    <small>Penanda tangan dokumen tagihan ini.</small>
                </div>
                <span class="sec-letter">C</span>
            </div>
            <div class="sec-body">
                <div class="info-banner banner-info mb-4">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>
                        Pilih pejabat yang akan menjadi verifikator/penanda tangan untuk tagihan ini.
                        <strong>PPK</strong> ditentukan otomatis dari kontrak yang dipilih.
                        Nama &amp; NIP akan dipotret (snapshot) dan ditampilkan pada dokumen yang dicetak.
                    </span>
                </div>

                @php
                    $verifikatorFields = [
                        ['key' => 'ppspm',                 'label' => 'PPSPM',                                          'icon' => 'bi-shield-check',     'options' => $verifikatorOptions['ppspm'] ?? collect()],
                        ['key' => 'koordinator_keuangan',  'label' => 'Koordinator Keuangan',                            'icon' => 'bi-diagram-3-fill',   'options' => $verifikatorOptions['koordinator_keuangan'] ?? collect()],
                        ['key' => 'bendahara_pengeluaran', 'label' => 'Bendahara Pengeluaran',                           'icon' => 'bi-wallet2',          'options' => $verifikatorOptions['bendahara_pengeluaran'] ?? collect()],
                        ['key' => 'bendahara_penerimaan',  'label' => 'Bendahara Penerimaan',                            'icon' => 'bi-piggy-bank-fill',  'options' => $verifikatorOptions['bendahara_penerimaan'] ?? collect()],
                        ['key' => 'kasubbag',              'label' => 'Kepala Subbagian Keuangan dan Tata Usaha',         'icon' => 'bi-person-workspace','options' => $verifikatorOptions['kasubbag'] ?? collect()],
                    ];
                @endphp

                <div class="row g-3">
                    @foreach($verifikatorFields as $vf)
                        <div class="col-md-6">
                            <label class="form-label modern" for="verif_{{ $vf['key'] }}">
                                <i class="bi {{ $vf['icon'] }} text-success"></i> {{ $vf['label'] }}
                                <span class="text-danger ms-1">*</span>
                            </label>
                            <select
                                id="verif_{{ $vf['key'] }}"
                                class="form-select verifikator-select"
                                name="{{ $vf['key'] }}_user_id"
                                data-key="{{ $vf['key'] }}"
                                required
                            >
                                <option value="">-- Pilih {{ $vf['label'] }} --</option>
                                @foreach($vf['options'] as $opt)
                                    <option
                                        value="{{ $opt['id'] }}"
                                        data-name="{{ $opt['name'] }}"
                                        data-nip="{{ $opt['nip'] }}"
                                        data-jabatan="{{ $opt['jabatan'] }}"
                                        @selected(old($vf['key'].'_user_id') == $opt['id'])
                                    >{{ $opt['name'] }} {{ $opt['nip'] !== '-' ? '— NIP: '.$opt['nip'] : '' }}</option>
                                @endforeach
                            </select>
                            <div class="small text-muted mt-1" style="font-size:.76rem;" id="info_{{ $vf['key'] }}"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ============ D. Dokumen Vendor & Ringkasan Nilai ============ --}}
        <div class="sec-card">
            <div class="sec-head">
                <span class="sec-icon si-warning"><i class="bi bi-calculator-fill"></i></span>
                <div>
                    <h6>Dokumen Vendor &amp; Ringkasan Nilai</h6>
                    <small>Detail invoice dan perhitungan netto.</small>
                </div>
                <span class="sec-letter">D</span>
            </div>
            <div class="sec-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label modern" for="nomor_invoice">
                            <i class="bi bi-receipt text-warning"></i> Nomor Invoice / Permohonan
                            <span class="text-danger ms-1">*</span>
                        </label>
                        <input type="text" id="nomor_invoice" class="form-control modern" name="nomor_invoice" placeholder="Contoh: INV/2026/001" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label modern" for="tanggal_invoice">
                            <i class="bi bi-calendar-event text-warning"></i> Tanggal Invoice
                            <span class="text-danger ms-1">*</span>
                        </label>
                        <input type="date" id="tanggal_invoice" class="form-control modern" name="tanggal_invoice" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label modern"><i class="bi bi-cash-stack text-success"></i> Nilai Bruto (DPP + PPN)</label>
                        <input type="text" class="form-control modern fw-bold fs-5" id="total_bruto_display" value="{{ $isPresetTagihan ? 'Rp ' . number_format($selectedTermin->nilai_bruto_termin, 0, ',', '.') : 'Rp 0' }}" readonly>
                        <input type="hidden" name="total_bruto" id="total_bruto" value="{{ $isPresetTagihan ? $selectedTermin->nilai_bruto_termin : 0 }}">
                        <small class="text-muted d-block mt-1" style="font-size:.74rem;"><i class="bi bi-magic me-1"></i>Terisi otomatis dari Termin.</small>
                    </div>
                </div>

                <div class="info-banner banner-warning mt-4 d-none" id="info_potongan_um">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>Kontrak ini masih memiliki <strong>sisa uang muka</strong>. Potongan angsuran uang muka akan otomatis diperhitungkan pada termin ini.</span>
                </div>

                <div class="row g-3 mt-4 pt-4" style="border-top:1px dashed #e2e8f0;">
                    <div class="col-md-4">
                        <div class="nominal-card" style="border-color:rgba(99,102,241,.20);">
                            <div class="nc-label" style="color:#4338ca;"><i class="bi bi-cash me-1"></i>Nilai Bruto</div>
                            <div class="fw-bold fs-5 mb-0" id="summary_bruto_display" style="color:#0f172a;font-variant-numeric:tabular-nums;">{{ $isPresetTagihan ? 'Rp ' . number_format($selectedTermin->nilai_bruto_termin, 0, ',', '.') : 'Rp 0' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="nominal-card" style="border-color:rgba(245,158,11,.30);">
                            <div class="nc-label" style="color:#b45309;"><i class="bi bi-dash-circle me-1"></i>Potongan Angsuran UM</div>
                            <div class="fw-bold fs-5 mb-0" id="potongan_um_display" style="color:#b45309;font-variant-numeric:tabular-nums;">Rp {{ number_format($initialPotonganAngsuran, 0, ',', '.') }}</div>
                            <input type="hidden" name="potongan_angsuran_uang_muka" id="potongan_angsuran_uang_muka" value="{{ $initialPotonganAngsuran }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="nominal-card" style="background:linear-gradient(135deg,rgba(16,185,129,.08),rgba(16,185,129,.02));">
                            <div class="nc-label"><i class="bi bi-check-circle-fill me-1"></i>Nilai Netto</div>
                            <div class="fw-bold fs-3 mb-0" id="total_netto_display" style="color:#047857;font-variant-numeric:tabular-nums;letter-spacing:-.01em;">Rp 0</div>
                            <input type="hidden" name="total_netto" id="total_netto" value="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ E. Arsip Digital Pekerjaan ============ --}}
        <div class="sec-card">
            <div class="sec-head">
                <span class="sec-icon si-danger"><i class="bi bi-cloud-arrow-up-fill"></i></span>
                <div>
                    <h6>Arsip Digital Pekerjaan</h6>
                    <small>Format .PDF / .ZIP, maksimal 5MB per berkas.</small>
                </div>
                <span class="sec-letter">E</span>
            </div>
            <div class="sec-body">
                <div class="info-banner banner-info mb-4">
                    <i class="bi bi-info-circle-fill"></i>
                    <span><strong>Pemberitahuan:</strong> Dokumen final bertandatangan untuk BAPP, BAST, dan BAP dikelola nanti melalui halaman <strong>Detail Tagihan (Working Hub)</strong> setelah draft ini tersimpan.</span>
                </div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label modern" for="file_invoice">
                            <i class="bi bi-file-earmark-pdf-fill text-danger"></i> Surat Permohonan / Invoice
                            <span class="text-danger ms-1">*</span>
                        </label>
                        <label class="file-drop" data-accept=".pdf" data-max-mb="5" data-target="file_invoice">
                            <input type="file" id="file_invoice" name="file_invoice" accept=".pdf" required>
                            <div class="fd-default">
                                <div class="fd-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                                <div class="fd-title">Tarik &amp; lepaskan, atau <strong>klik untuk memilih</strong></div>
                                <div class="fd-sub">Surat Permohonan pembayaran resmi dari vendor.</div>
                                <div class="fd-meta"><i class="bi bi-file-earmark-pdf"></i> PDF &middot; Maks 5MB</div>
                            </div>
                            <div class="fd-preview">
                                <div class="fp-icon"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                                <div class="fp-info">
                                    <div class="fp-name">-</div>
                                    <div class="fp-detail">
                                        <span class="fp-size">0 KB</span>
                                        <span class="fp-type text-muted">PDF</span>
                                    </div>
                                    <div class="fp-bar"><span style="width:0%"></span></div>
                                </div>
                                <button type="button" class="fp-remove" title="Hapus berkas"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label modern" for="file_lampiran_lainnya">
                            <i class="bi bi-images text-secondary"></i> Lampiran Laporan (Foto/Dokumentasi)
                            <span class="text-muted fw-normal ms-1">(Opsional)</span>
                        </label>
                        <label class="file-drop" data-accept=".pdf,.zip" data-max-mb="5" data-target="file_lampiran_lainnya">
                            <input type="file" id="file_lampiran_lainnya" name="file_lampiran_lainnya" accept=".pdf,.zip">
                            <div class="fd-default">
                                <div class="fd-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                                <div class="fd-title">Tarik &amp; lepaskan, atau <strong>klik untuk memilih</strong></div>
                                <div class="fd-sub">Laporan progres, dokumentasi pekerjaan, atau backup.</div>
                                <div class="fd-meta"><i class="bi bi-file-earmark-zip"></i> PDF / ZIP &middot; Maks 5MB</div>
                            </div>
                            <div class="fd-preview">
                                <div class="fp-icon"><i class="bi bi-file-earmark-zip-fill"></i></div>
                                <div class="fp-info">
                                    <div class="fp-name">-</div>
                                    <div class="fp-detail">
                                        <span class="fp-size">0 KB</span>
                                        <span class="fp-type text-muted">-</span>
                                    </div>
                                    <div class="fp-bar"><span style="width:0%"></span></div>
                                </div>
                                <button type="button" class="fp-remove" title="Hapus berkas"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ Submit Bar ============ --}}
        <div class="submit-bar">
            <div class="me-auto d-none d-md-flex align-items-center gap-2 text-muted" style="font-size:.82rem;">
                <i class="bi bi-shield-lock"></i>
                <span>Pastikan seluruh data telah diisi dengan benar sebelum menyimpan draft.</span>
            </div>
            <button type="reset" class="btn-cancel-submit">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
            </button>
            <button type="submit" class="btn-submit-primary">
                <i class="bi bi-save2-fill"></i> Buat Draft Tagihan
            </button>
        </div>
    </form>
@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    const isPresetTagihan = @json($isPresetTagihan);
    const kontrakTerminMap = @json($kontrakTerminMap);
    const selectedTerminMeta = @json($selectedTermin ? [
        'jenis_termin' => $selectedTermin->jenis_termin,
        'potongan_angsuran_uang_muka' => (float) $initialPotonganAngsuran,
    ] : null);
    
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'default',
            width: '100%',
            dropdownAutoWidth: true,
        });
        $('.verifikator-select').select2({
            theme: 'default',
            width: '100%',
            dropdownAutoWidth: true,
            placeholder: function () {
                return $(this).find('option:first').text();
            },
        });

        if (isPresetTagihan) {
            toggleBastFields(selectedTerminMeta?.jenis_termin ?? null);
            updatePotonganAngsuranDisplay(selectedTerminMeta?.potongan_angsuran_uang_muka ?? 0);
            hitungTotalNetto();
        }
    });

    function getDetailKontrak(idKontrak) {
        if(!idKontrak) {
            $('#panel_info_kontrak').hide();
            let $termin0 = $('#kontrak_termin_id');
            if ($termin0.hasClass('select2-hidden-accessible')) { $termin0.select2('destroy'); }
            $termin0.html('<option value="">-- Pilih Kontrak Terlebih Dahulu --</option>').prop('disabled', true);
            $termin0.select2({ theme: 'default', width: '100%' });
            toggleBastFields(null);
            updatePotonganAngsuranDisplay(0);
            $('#total_bruto').val(0);
            $('#total_bruto_display').val('Rp 0');
            $('#summary_bruto_display').text('Rp 0');
            hitungTotalNetto();
            return;
        }

        let kontrakData = kontrakTerminMap[idKontrak];
        if (!kontrakData) {
            return;
        }

        $('#info_vendor').text(kontrakData.vendor);
        $('#info_pekerjaan').text(kontrakData.nama);
        $('#info_nilai').text(formatRupiah(kontrakData.nilai.toString()));
        $('#panel_info_kontrak').fadeIn();

        let html = '<option value="">-- Pilih Termin / Tagihan --</option>';
        kontrakData.terms.forEach(t => {
            html += `<option value="${t.id}" data-bruto="${t.nilai_bruto_termin}" data-jenis="${t.jenis_termin}" data-potongan-um="${t.potongan_angsuran_uang_muka}">${t.keterangan_termin} - ${t.persentase}% (Rp ${formatRupiahCustom(t.nilai_bruto_termin)})</option>`;
        });
        if (kontrakData.terms.length === 0) {
            html = '<option value="">Tidak ada Termin berstatus READY_TO_BILL</option>';
        }
        let $termin = $('#kontrak_termin_id');
        if ($termin.hasClass('select2-hidden-accessible')) {
            $termin.select2('destroy');
        }
        $termin.html(html).prop('disabled', false);
        $termin.select2({ theme: 'default', width: '100%' });
        toggleBastFields(null);
        updatePotonganAngsuranDisplay(0);
    }

    function setBrutoFromTermin() {
        let opt = $('#kontrak_termin_id').find(':selected');
        let brutoVal = opt.data('bruto') || 0;
        let jenisTermin = opt.data('jenis') || null;
        let potonganUm = parseFloat(opt.data('potongan-um')) || 0;
        
        $('#total_bruto').val(brutoVal);
        $('#total_bruto_display').val('Rp ' + formatRupiahCustom(brutoVal));
        $('#summary_bruto_display').text('Rp ' + formatRupiahCustom(brutoVal));
        toggleBastFields(jenisTermin);
        updatePotonganAngsuranDisplay(potonganUm);
        hitungTotalNetto();
    }

    function toggleBastFields(jenisTermin) {
        const isPelunasan = jenisTermin === 'PELUNASAN';
        const bastWrapper = document.getElementById('wrapper_bast_fields');
        const bastFileWrapper = document.getElementById('wrapper_file_bast');
        const tanggalBast = document.getElementById('tanggal_bast');
        const fileBast = document.getElementById('file_bast');

        if (bastWrapper) bastWrapper.style.display = isPelunasan ? 'block' : 'none';
        if (bastFileWrapper) bastFileWrapper.style.display = isPelunasan ? 'block' : 'none';
        if (tanggalBast) tanggalBast.required = isPelunasan;
        if (fileBast) fileBast.required = isPelunasan;

        if (!isPelunasan) {
            if (tanggalBast) tanggalBast.value = '';
            if (fileBast) fileBast.value = '';
        }
    }

    function updatePotonganAngsuranDisplay(nominal) {
        const normalized = parseFloat(nominal) || 0;
        document.getElementById('potongan_angsuran_uang_muka').value = normalized;
        document.getElementById('potongan_um_display').textContent = 'Rp ' + formatRupiahCustom(Math.round(normalized));
        document.getElementById('info_potongan_um').classList.toggle('d-none', normalized <= 0);
    }

    function formatRupiah(numberStr) {
        let nStr = numberStr.toString();
        let split = nStr.split('.');
        let sisa = split[0].length % 3;
        let rupiah = split[0].substr(0, sisa);
        let ribuan = split[0].substr(sisa).match(/\d{3}/gi);
        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return 'Rp ' + rupiah;
    }

    function formatRupiahCustom(angka) {
        let number_string = angka.toString().replace(/[^,\d]/g, ''),
        split   		= number_string.split(','),
        sisa     		= split[0].length % 3,
        rupiah     		= split[0].substr(0, sisa),
        ribuan     		= split[0].substr(sisa).match(/\d{3}/gi);

        if(ribuan){
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return rupiah;
    }

    function hitungTotalNetto() {
        let bruto = parseFloat($('#total_bruto').val()) || 0;
        let potonganAngsuranUangMuka = parseFloat($('#potongan_angsuran_uang_muka').val()) || 0;

        let netto = bruto - potonganAngsuranUangMuka;
        
        $('#total_netto').val(netto);
        $('#total_netto_display').text('Rp ' + formatRupiahCustom(Math.round(netto)));
    }

    // Verifikator info preview (NIP & Jabatan)
    document.addEventListener('DOMContentLoaded', function () {
        // ============ File Drop Zones ============
        document.querySelectorAll('.file-drop').forEach(function (zone) {
            const input = zone.querySelector('input[type="file"]');
            if (!input) return;

            const preview = zone.querySelector('.fd-preview');
            const fpName = preview.querySelector('.fp-name');
            const fpSize = preview.querySelector('.fp-size');
            const fpType = preview.querySelector('.fp-type');
            const fpIcon = preview.querySelector('.fp-icon');
            const fpBar = preview.querySelector('.fp-bar > span');
            const fpRemove = preview.querySelector('.fp-remove');
            const maxMb = parseFloat(zone.dataset.maxMb || '5');
            const maxBytes = maxMb * 1024 * 1024;

            const fmtSize = function (bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
            };

            const setIconForFile = function (file) {
                fpIcon.classList.remove('is-zip', 'is-img');
                const name = (file.name || '').toLowerCase();
                const isZip = /\.zip$/.test(name) || file.type === 'application/zip' || file.type === 'application/x-zip-compressed';
                const isImg = (file.type || '').startsWith('image/');
                let html = '<i class="bi bi-file-earmark-pdf-fill"></i>';
                if (isZip) {
                    fpIcon.classList.add('is-zip');
                    html = '<i class="bi bi-file-earmark-zip-fill"></i>';
                } else if (isImg) {
                    fpIcon.classList.add('is-img');
                    html = '<i class="bi bi-file-earmark-image-fill"></i>';
                }
                fpIcon.innerHTML = html;
            };

            const renderFile = function (file) {
                if (!file) {
                    zone.classList.remove('is-filled', 'is-error');
                    return;
                }

                const size = file.size || 0;
                const ratio = Math.min(size / maxBytes, 1);
                const ext = (file.name.split('.').pop() || '').toUpperCase();

                fpName.textContent = file.name;
                fpSize.textContent = fmtSize(size);
                fpType.textContent = ext;
                setIconForFile(file);

                fpBar.classList.remove('is-warn', 'is-error');
                fpSize.classList.remove('is-warn', 'is-error');
                if (ratio >= 1) {
                    fpBar.classList.add('is-error');
                    fpSize.classList.add('is-error');
                } else if (ratio >= 0.8) {
                    fpBar.classList.add('is-warn');
                    fpSize.classList.add('is-warn');
                }
                fpBar.style.width = (ratio * 100).toFixed(0) + '%';

                zone.classList.remove('is-error');
                if (size > maxBytes) {
                    zone.classList.add('is-error');
                    zone.classList.remove('is-filled');
                    fpName.textContent = file.name + ' — melebihi ' + maxMb + 'MB';
                } else {
                    zone.classList.add('is-filled');
                }
            };

            input.addEventListener('change', function () {
                const file = input.files && input.files[0];
                renderFile(file || null);
            });

            fpRemove.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                input.value = '';
                zone.classList.remove('is-filled', 'is-error');
                fpName.textContent = '-';
                fpSize.textContent = '0 KB';
                fpType.textContent = '-';
                fpBar.style.width = '0%';
            });

            // Drag & drop
            ['dragenter', 'dragover'].forEach(function (evt) {
                zone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    zone.classList.add('is-drag');
                });
            });
            ['dragleave', 'dragend', 'drop'].forEach(function (evt) {
                zone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    zone.classList.remove('is-drag');
                });
            });
            zone.addEventListener('drop', function (e) {
                const dt = e.dataTransfer;
                if (!dt || !dt.files || !dt.files.length) return;
                try {
                    input.files = dt.files;
                } catch (err) {
                    // Fallback for browsers that don't allow direct assignment
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(dt.files[0]);
                    input.files = dataTransfer.files;
                }
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        document.querySelectorAll('.verifikator-select').forEach(function (sel) {
            const key = sel.dataset.key;
            const info = document.getElementById('info_' + key);
            const update = function () {
                const opt = sel.options[sel.selectedIndex];
                if (!opt || !opt.value) {
                    info.innerHTML = '';
                    return;
                }
                const nip = opt.dataset.nip || '-';
                const jab = opt.dataset.jabatan || '';
                info.innerHTML = '<i class="bi bi-person-badge me-1"></i>NIP: <span class="font-monospace">' + nip + '</span>' + (jab ? ' &middot; ' + jab : '');
            };
            sel.addEventListener('change', update);
            if (sel.value) update();
        });
    });

    // Auto-fill NIP, Jabatan & WA saat memilih Nama Pemeriksa dari dropdown pegawai
    $(document).ready(function () {
        const $namaSelect = $('#namaPemeriksaSelect');
        const $nipInput = $('#nipPemeriksaInput');
        const $jabatanInput = $('#jabatanPemeriksaInput');
        const $waInput = $('#waPemeriksaInput');

        if (!$namaSelect.length || !$nipInput.length || !$jabatanInput.length || !$waInput.length) return;

        function syncPemeriksa() {
            const $opt = $namaSelect.find(':selected');
            if (!$opt.val()) {
                $nipInput.val('');
                $jabatanInput.val('');
                $waInput.val('');
                return;
            }
            $nipInput.val($opt.data('nip') || '');
            $jabatanInput.val($opt.data('jabatan') || '');
            $waInput.val($opt.data('wa') || '');
        }

        $namaSelect.on('change', syncPemeriksa);

        // Inisialisasi (mis. setelah validasi gagal & old() mengembalikan pilihan)
        if ($namaSelect.val()) syncPemeriksa();
    });
</script>
@endpush
