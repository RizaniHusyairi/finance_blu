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
