{{-- ============================================================
     Shared styles + animations for "Detail Pencatatan SP2D" pages
     (Kontrak / Perjaldin / Honorarium) — unified modern UI.
============================================================ --}}
<style>
    :root {
        --d-ink: #0f1b33;
        --d-muted: #6b7a99;
        --d-line: #e6ecf5;
        --d-primary: #4f46e5;
        --d-primary-2: #7c3aed;
        --d-cyan: #0891b2;
        --d-green: #059669;
        --d-amber: #d97706;
        --d-rose: #e11d48;
        --d-slate: #64748b;
    }

    .sp2dd { position: relative; }

    /* ===== HERO ===== */
    .sp2dd-hero {
        position: relative; overflow: hidden;
        border-radius: 22px; padding: 26px 30px; margin-bottom: 22px; color: #fff;
        background: linear-gradient(125deg, #312e81 0%, #4f46e5 42%, #7c3aed 72%, #0891b2 100%);
        box-shadow: 0 22px 48px -18px rgba(79, 70, 229, .55);
        animation: d-fade-down .55s ease both;
    }
    .sp2dd-hero::before, .sp2dd-hero::after {
        content: ""; position: absolute; border-radius: 50%;
        background: radial-gradient(circle at center, rgba(255,255,255,.20), transparent 70%);
        pointer-events: none;
    }
    .sp2dd-hero::before { width: 320px; height: 320px; top: -150px; right: -50px; animation: d-float 7s ease-in-out infinite; }
    .sp2dd-hero::after  { width: 220px; height: 220px; bottom: -130px; left: 22%; animation: d-float 9s ease-in-out infinite reverse; }
    .sp2dd-hero__bar {
        position: absolute; left: 0; right: 0; bottom: 0; height: 4px;
        background: linear-gradient(90deg, #38bdf8, #22c55e, #f43f5e, #38bdf8);
        background-size: 220% 100%; animation: d-ribbon 5s linear infinite;
    }
    .sp2dd-hero__in { position: relative; z-index: 1; }
    .sp2dd-eyebrow {
        display: inline-flex; align-items: center; gap: 7px; padding: 5px 13px; border-radius: 999px;
        background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.28);
        font-size: 12px; font-weight: 700; letter-spacing: .04em; color: #fff;
    }
    .sp2dd-hero h1 { margin: 12px 0 2px; font-size: clamp(20px, 2.6vw, 28px); font-weight: 800; line-height: 1.12; color: #fff !important; }
    .sp2dd-hero .sub { margin: 0; color: rgba(255,255,255,.82); font-size: 13.5px; }

    .sp2dd-hero__meta { display: flex; flex-wrap: wrap; gap: 22px; margin-top: 16px; }
    .sp2dd-hero__meta .k { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: rgba(255,255,255,.7); }
    .sp2dd-hero__meta .v { font-size: 14px; font-weight: 700; color: #fff; }
    .sp2dd-hero__amount {
        margin-top: 16px; display: inline-flex; flex-direction: column;
        padding: 12px 18px; border-radius: 14px;
        background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.26);
    }
    .sp2dd-hero__amount .k { font-size: 11px; letter-spacing: .05em; text-transform: uppercase; color: rgba(255,255,255,.8); }
    .sp2dd-hero__amount .v { font-size: 24px; font-weight: 800; color: #fff; letter-spacing: -.01em; }

    .sp2dd-status {
        display: inline-flex; align-items: center; gap: 7px; padding: 7px 14px; border-radius: 999px;
        font-size: 12.5px; font-weight: 800; background: rgba(255,255,255,.92);
    }
    .sp2dd-status .dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; animation: d-pulse 1.6s ease-out infinite; }
    .sp2dd-status--amber { color: var(--d-amber); }
    .sp2dd-status--slate { color: var(--d-slate); }
    .sp2dd-status--rose  { color: var(--d-rose); }
    .sp2dd-status--cyan  { color: var(--d-cyan); }
    .sp2dd-status--green { color: var(--d-green); }
    .sp2dd-status--primary { color: var(--d-primary); }

    .sp2dd-hbtn {
        display: inline-flex; align-items: center; gap: 6px; padding: 9px 15px; border-radius: 11px;
        font-size: 13px; font-weight: 700; text-decoration: none; border: 1px solid rgba(255,255,255,.4);
        color: #fff; background: rgba(255,255,255,.12); transition: transform .2s ease, background .2s ease;
    }
    .sp2dd-hbtn:hover { transform: translateY(-2px); background: rgba(255,255,255,.22); color: #fff; }
    .sp2dd-hbtn--solid { background: #fff; color: #312e81; border-color: #fff; }
    .sp2dd-hbtn--solid:hover { color: #312e81; }
    .sp2dd-hbtn i { font-size: 17px; }

    /* ===== STEPPER ===== */
    .sp2dd-card {
        border-radius: 18px; background: #fff; border: 1px solid var(--d-line);
        box-shadow: 0 12px 30px -22px rgba(15,27,51,.5); margin-bottom: 22px; overflow: hidden;
        animation: d-rise .5s ease both;
    }
    .sp2dd-card__head {
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
        padding: 15px 20px; border-bottom: 1px solid var(--d-line);
        background: linear-gradient(90deg, #fafbff, #fff);
    }
    .sp2dd-card__title { font-weight: 800; color: var(--d-ink); font-size: 14.5px; display: flex; align-items: center; gap: 9px; }
    .sp2dd-card__title i { color: var(--d-primary); font-size: 20px; }
    .sp2dd-card__body { padding: 20px; }

    .sp2dd-stepper { display: flex; align-items: flex-start; position: relative; }
    /* continuous track centered on the icon row */
    .sp2dd-track {
        position: absolute; top: 26px; left: 12.5%; right: 12.5%; height: 4px;
        transform: translateY(-50%); background: var(--d-line); border-radius: 999px;
        z-index: 1; overflow: hidden;
    }
    .sp2dd-track__fill {
        height: 100%; width: 0; border-radius: 999px;
        background: linear-gradient(90deg, var(--d-green), var(--d-cyan));
        transition: width .9s cubic-bezier(.4,0,.2,1);
    }
    .sp2dd-step { position: relative; z-index: 2; flex: 1; text-align: center; padding: 0 4px; }
    .sp2dd-step__icon {
        width: 52px; height: 52px; margin: 0 auto 10px; border-radius: 50%;
        display: grid; place-items: center; background: #fff; border: 3px solid var(--d-line);
        color: #94a3b8; transition: all .35s cubic-bezier(.2,.8,.2,1);
    }
    .sp2dd-step__icon i { font-size: 24px; }
    .sp2dd-step__label { font-weight: 700; color: var(--d-ink); font-size: 13px; }
    .sp2dd-step__sub { color: var(--d-muted); font-size: 11px; }
    .sp2dd-step.passed .sp2dd-step__icon { border-color: var(--d-green); background: var(--d-green); color: #fff; box-shadow: 0 10px 20px -8px rgba(5,150,105,.6); }
    .sp2dd-step.active .sp2dd-step__icon { border-color: var(--d-primary); color: var(--d-primary); background: #fff; box-shadow: 0 0 0 5px rgba(79,70,229,.16); animation: d-bob 2s ease-in-out infinite; }
    .sp2dd-step.fail .sp2dd-step__icon { border-color: var(--d-rose); color: var(--d-rose); background: #ffe4e6; }
    .sp2dd-step.active .sp2dd-step__label { color: var(--d-primary); }

    /* ===== VERIFIKATOR ===== */
    .sp2dd-verif { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .sp2dd-vcard {
        border-radius: 14px; padding: 14px; text-align: center; border: 1px solid var(--d-line); background: #fff;
        transition: transform .25s ease, box-shadow .25s ease; animation: d-rise .5s ease both;
    }
    .sp2dd-vcard:hover { transform: translateY(-3px); box-shadow: 0 14px 26px -16px rgba(15,27,51,.4); }
    .sp2dd-vcard__role { font-weight: 800; font-size: 12.5px; color: var(--d-ink); }
    .sp2dd-vcard__name { font-size: 11px; color: var(--d-muted); margin: 2px 0 8px; min-height: 14px; }
    .sp2dd-vpill { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; }
    .sp2dd-vpill i { font-size: 14px; }
    .sp2dd-vcard.ok   { border-color: rgba(5,150,105,.4);  background: #ecfdf5; }
    .sp2dd-vcard.wait { border-color: rgba(217,119,6,.4);  background: #fffbeb; }
    .sp2dd-vcard.bad  { border-color: rgba(225,29,72,.4);  background: #fff1f2; }
    .sp2dd-vpill.ok   { color: var(--d-green); background: #d1fae5; }
    .sp2dd-vpill.wait { color: var(--d-amber); background: #fef3c7; }
    .sp2dd-vpill.bad  { color: var(--d-rose);  background: #ffe4e6; }
    .sp2dd-vpill.idle { color: var(--d-slate); background: #e2e8f0; }

    /* ===== INFO ROWS ===== */
    .sp2dd-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px 22px; }
    .sp2dd-field .k { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--d-muted); font-weight: 700; margin-bottom: 2px; }
    .sp2dd-field .v { font-weight: 700; color: var(--d-ink); font-size: 13.5px; }
    .sp2dd-field .v.mono { font-family: ui-monospace, Menlo, Consolas, monospace; color: var(--d-primary); }

    /* highlight strip cards */
    .sp2dd-mini { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .sp2dd-minicard {
        border-radius: 14px; padding: 14px 16px; border: 1px solid var(--d-line);
        background: linear-gradient(135deg, #fafbff, #fff); animation: d-rise .5s ease both;
    }
    .sp2dd-minicard .k { font-size: 10.5px; text-transform: uppercase; letter-spacing: .04em; color: var(--d-muted); font-weight: 700; }
    .sp2dd-minicard .v { font-weight: 800; color: var(--d-ink); font-size: 14px; margin-top: 3px; }
    .sp2dd-minicard.accent { background: linear-gradient(135deg, #eef2ff, #f5f3ff); border-color: rgba(79,70,229,.25); }
    .sp2dd-minicard.accent .v { color: var(--d-primary); }
    .sp2dd-minicard.green { background: linear-gradient(135deg, #ecfdf5, #f0fdfa); border-color: rgba(5,150,105,.25); }
    .sp2dd-minicard.green .v { color: var(--d-green); }

    /* checklist */
    .sp2dd-check { display: flex; align-items: flex-start; gap: 11px; padding: 11px 0; border-bottom: 1px dashed var(--d-line); }
    .sp2dd-check:last-child { border-bottom: none; }
    .sp2dd-check__ic { width: 26px; height: 26px; border-radius: 8px; display: grid; place-items: center; flex-shrink: 0; }
    .sp2dd-check__ic i { font-size: 17px; }
    .sp2dd-check.ok .sp2dd-check__ic { background: #d1fae5; color: var(--d-green); }
    .sp2dd-check.no .sp2dd-check__ic { background: #ffe4e6; color: var(--d-rose); }
    .sp2dd-check__t { font-weight: 700; font-size: 13px; color: var(--d-ink); }
    .sp2dd-check__s { font-size: 11px; color: var(--d-muted); }

    /* form card */
    .sp2dd-form-card { border-radius: 18px; overflow: hidden; border: 1px solid var(--d-line); box-shadow: 0 16px 38px -22px rgba(15,27,51,.5); animation: d-rise .5s ease both; }
    .sp2dd-form-card__head { padding: 16px 20px; color: #fff; background: linear-gradient(120deg, var(--d-primary), var(--d-primary-2)); font-weight: 800; display: flex; align-items: center; gap: 9px; }
    .sp2dd-form-card__head i { font-size: 20px; }
    .sp2dd-form-card__body { padding: 20px; background: #fff; }

    .sp2dd-readline { display: flex; justify-content: space-between; align-items: center; padding: 11px 0; border-bottom: 1px solid var(--d-line); }
    .sp2dd-readline:last-of-type { border-bottom: none; }
    .sp2dd-readline .k { color: var(--d-muted); font-size: 12.5px; font-weight: 600; }
    .sp2dd-readline .v { font-weight: 800; color: var(--d-ink); }

    .sp2dd-amount-box {
        display: flex; justify-content: space-between; align-items: center;
        padding: 14px 16px; border-radius: 12px; margin: 6px 0 16px;
        background: linear-gradient(135deg, #ecfdf5, #f0fdfa); border: 1px solid rgba(5,150,105,.25);
    }
    .sp2dd-amount-box .k { font-size: 12px; font-weight: 700; color: var(--d-muted); }
    .sp2dd-amount-box .v { font-size: 20px; font-weight: 800; color: var(--d-green); }

    /* buttons */
    .sp2dd-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; width: 100%; padding: 12px 16px; border: none; border-radius: 12px; font-weight: 800; font-size: 14px; text-decoration: none; transition: transform .2s ease, box-shadow .2s ease, filter .2s ease; cursor: pointer; }
    .sp2dd-btn i { font-size: 18px; }
    .sp2dd-btn--primary { color: #fff; background: linear-gradient(120deg, var(--d-primary), var(--d-primary-2)); box-shadow: 0 12px 24px -10px rgba(79,70,229,.7); }
    .sp2dd-btn--success { color: #fff; background: linear-gradient(120deg, #10b981, #059669); box-shadow: 0 12px 24px -10px rgba(5,150,105,.65); }
    .sp2dd-btn--warn { color: #fff; background: linear-gradient(120deg, #f59e0b, #d97706); box-shadow: 0 12px 24px -10px rgba(217,119,6,.6); }
    .sp2dd-btn:hover:not(:disabled) { transform: translateY(-2px); filter: brightness(1.04); color: #fff; }
    .sp2dd-btn:disabled { opacity: .5; cursor: not-allowed; box-shadow: none; }

    .sp2dd-input { width: 100%; height: 44px; padding: 0 14px; border-radius: 11px; border: 1px solid var(--d-line); background: #f8fafc; font-size: 14px; transition: border-color .2s ease, box-shadow .2s ease, background .2s ease; }
    .sp2dd-input:focus { outline: none; background: #fff; border-color: var(--d-primary); box-shadow: 0 0 0 4px rgba(79,70,229,.12); }
    textarea.sp2dd-input { height: auto; padding: 12px 14px; }
    .sp2dd-label { font-weight: 700; font-size: 12.5px; color: var(--d-ink); margin-bottom: 5px; display: block; }

    /* tables */
    .sp2dd-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .sp2dd-table thead th { text-align: left; font-size: 10.5px; letter-spacing: .05em; text-transform: uppercase; color: var(--d-muted); font-weight: 800; padding: 12px 16px; background: #f6f8fc; border-bottom: 1px solid var(--d-line); white-space: nowrap; }
    .sp2dd-table tbody td { padding: 12px 16px; border-bottom: 1px solid var(--d-line); font-size: 12.5px; vertical-align: middle; }
    .sp2dd-table tbody tr { animation: d-rise .4s ease both; transition: background .16s ease; }
    .sp2dd-table tbody tr:hover { background: #f7f9ff; }
    .sp2dd-table tfoot td { padding: 13px 16px; background: #f6f8fc; font-weight: 800; }

    /* timeline / logs */
    .sp2dd-tl { position: relative; padding-left: 26px; }
    .sp2dd-tl::before { content: ""; position: absolute; left: 8px; top: 4px; bottom: 4px; width: 2px; background: var(--d-line); }
    .sp2dd-tl__item { position: relative; padding-bottom: 16px; animation: d-rise .45s ease both; }
    .sp2dd-tl__item:last-child { padding-bottom: 0; }
    .sp2dd-tl__dot { position: absolute; left: -22px; top: 3px; width: 12px; height: 12px; border-radius: 50%; background: var(--d-primary); box-shadow: 0 0 0 4px rgba(79,70,229,.15); }
    .sp2dd-tl__t { font-weight: 700; font-size: 12.5px; color: var(--d-ink); }
    .sp2dd-tl__m { font-size: 11px; color: var(--d-muted); }
    .sp2dd-tl__note { margin-top: 5px; padding: 7px 10px; border-radius: 8px; background: #fff1f2; border-left: 3px solid var(--d-rose); font-size: 12px; font-style: italic; color: #9f1239; }

    .sp2dd-locked { display: flex; gap: 12px; align-items: center; padding: 16px; border-radius: 12px; background: #f1f5f9; border: 1px dashed var(--d-line); }
    .sp2dd-locked i { font-size: 30px; color: var(--d-slate); }

    /* ===== UPLOAD DROPZONE (bukti transfer) ===== */
    .sp2dd-pay { animation: d-rise .5s ease both; }
    .sp2dd-pay__banner {
        position: relative; overflow: hidden;
        display: flex; align-items: center; gap: 12px;
        padding: 13px 15px; border-radius: 13px; margin-bottom: 14px;
        background: linear-gradient(120deg, #ecfdf5, #e0f2fe);
        border: 1px solid rgba(5,150,105,.25);
    }
    .sp2dd-pay__banner::after {
        content: ""; position: absolute; top: 0; bottom: 0; width: 60px; left: -80px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.65), transparent);
        animation: d-sheen 3s ease-in-out infinite;
    }
    .sp2dd-pay__banner i { font-size: 26px; color: var(--d-green); }
    .sp2dd-pay__banner .t { font-weight: 800; font-size: 13px; color: var(--d-ink); }
    .sp2dd-pay__banner .s { font-size: 11.5px; color: var(--d-muted); }

    .sp2dd-drop {
        position: relative; border: 2px dashed #b7c3da; border-radius: 16px;
        padding: 26px 18px; text-align: center; cursor: pointer;
        background: linear-gradient(180deg, #fbfdff, #f3f7fd);
        transition: border-color .25s ease, background .25s ease, transform .2s ease, box-shadow .25s ease;
    }
    .sp2dd-drop:hover { border-color: var(--d-primary); background: #f5f7ff; transform: translateY(-2px); box-shadow: 0 14px 30px -20px rgba(79,70,229,.6); }
    .sp2dd-drop.is-drag { border-color: var(--d-primary); background: #eef2ff; box-shadow: 0 0 0 4px rgba(79,70,229,.14); }
    .sp2dd-drop.has-file { border-style: solid; border-color: var(--d-green); background: linear-gradient(180deg, #f0fdf6, #ecfdf5); }
    .sp2dd-drop__icon {
        width: 56px; height: 56px; margin: 0 auto 10px; border-radius: 16px; display: grid; place-items: center;
        background: #fff; color: var(--d-primary); box-shadow: 0 10px 22px -14px rgba(79,70,229,.7);
        transition: transform .3s cubic-bezier(.2,.8,.2,1), color .25s ease;
    }
    .sp2dd-drop:hover .sp2dd-drop__icon { transform: translateY(-3px) scale(1.05); }
    .sp2dd-drop.has-file .sp2dd-drop__icon { color: var(--d-green); }
    .sp2dd-drop__icon i { font-size: 30px; }
    .sp2dd-drop__title { font-weight: 800; font-size: 13.5px; color: var(--d-ink); }
    .sp2dd-drop__hint { font-size: 11.5px; color: var(--d-muted); margin-top: 3px; }
    .sp2dd-drop__browse { color: var(--d-primary); font-weight: 800; text-decoration: underline; }
    .sp2dd-drop input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .sp2dd-file-pill {
        display: none; align-items: center; gap: 10px; margin-top: 12px;
        padding: 10px 12px; border-radius: 11px; background: #fff; border: 1px solid var(--d-line); text-align: left;
        animation: d-rise .35s ease both;
    }
    .sp2dd-file-pill.show { display: flex; }
    .sp2dd-file-pill__ic { width: 34px; height: 34px; border-radius: 9px; display: grid; place-items: center; background: #fee2e2; color: var(--d-rose); flex-shrink: 0; }
    .sp2dd-file-pill__ic i { font-size: 19px; }
    .sp2dd-file-pill__name { font-weight: 700; font-size: 12.5px; color: var(--d-ink); word-break: break-all; line-height: 1.2; }
    .sp2dd-file-pill__meta { font-size: 11px; color: var(--d-muted); }
    .sp2dd-min-w-0 { min-width: 0; }
    .sp2dd-file-pill__x { margin-left: auto; border: none; background: #f1f5f9; color: var(--d-slate); width: 26px; height: 26px; border-radius: 8px; cursor: pointer; flex-shrink: 0; transition: background .2s ease, color .2s ease; }
    .sp2dd-file-pill__x:hover { background: #fee2e2; color: var(--d-rose); }
    .sp2dd-file-pill__x i { font-size: 16px; vertical-align: middle; }

    .sticky-side { position: sticky; top: 1rem; }

    /* ===== keyframes ===== */
    @keyframes d-fade-down { from { opacity: 0; transform: translateY(-16px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes d-rise { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes d-float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
    @keyframes d-ribbon { to { background-position: 220% 0; } }
    @keyframes d-bob { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
    @keyframes d-pulse { 0% { box-shadow: 0 0 0 0 currentColor; } 70% { box-shadow: 0 0 0 5px transparent; } 100% { box-shadow: 0 0 0 0 transparent; } }
    @keyframes d-sheen { 0% { left: -80px; } 55%, 100% { left: 120%; } }

    @media (max-width: 991px) {
        .sp2dd-verif { grid-template-columns: repeat(2, 1fr); }
        .sp2dd-mini { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 575px) {
        .sp2dd-grid { grid-template-columns: 1fr; }
        .sp2dd-hero { padding: 22px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .sp2dd-hero, .sp2dd-card, .sp2dd-vcard, .sp2dd-minicard, .sp2dd-form-card,
        .sp2dd-table tbody tr, .sp2dd-tl__item, .sp2dd-step.active .sp2dd-step__icon,
        .sp2dd-hero::before, .sp2dd-hero::after, .sp2dd-hero__bar, .sp2dd-status .dot,
        .sp2dd-pay, .sp2dd-pay__banner::after, .sp2dd-file-pill { animation: none !important; }
    }
</style>
