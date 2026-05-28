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
