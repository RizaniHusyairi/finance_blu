{{-- ============================================================
     Shared styles + animations for "Pencatatan SP2D" index pages
     (Kontrak / Perjaldin / Honorarium) — unified modern UI.
============================================================ --}}
<style>
    :root {
        --sp2d-ink: #0f1b33;
        --sp2d-muted: #6b7a99;
        --sp2d-line: #e6ecf5;
        --sp2d-primary: #4f46e5;
        --sp2d-primary-2: #7c3aed;
        --sp2d-cyan: #0891b2;
        --sp2d-green: #059669;
        --sp2d-amber: #d97706;
        --sp2d-rose: #e11d48;
        --sp2d-slate: #64748b;
    }

    .sp2d-page { position: relative; }

    /* ===== HERO ===== */
    .sp2d-hero {
        position: relative;
        overflow: hidden;
        border-radius: 22px;
        padding: 28px 30px;
        margin-bottom: 22px;
        color: #fff;
        background: linear-gradient(125deg, #312e81 0%, #4f46e5 40%, #7c3aed 70%, #0891b2 100%);
        box-shadow: 0 22px 48px -18px rgba(79, 70, 229, .55);
        animation: sp2d-fade-down .55s ease both;
    }
    .sp2d-hero::before,
    .sp2d-hero::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        background: radial-gradient(circle at center, rgba(255,255,255,.22), transparent 70%);
        pointer-events: none;
    }
    .sp2d-hero::before { width: 320px; height: 320px; top: -140px; right: -60px; animation: sp2d-float 7s ease-in-out infinite; }
    .sp2d-hero::after  { width: 220px; height: 220px; bottom: -120px; left: 18%; animation: sp2d-float 9s ease-in-out infinite reverse; }
    .sp2d-hero__bar {
        position: absolute; left: 0; right: 0; bottom: 0; height: 4px;
        background: linear-gradient(90deg, #38bdf8, #22c55e, #f43f5e, #38bdf8);
        background-size: 220% 100%;
        animation: sp2d-ribbon 5s linear infinite;
    }
    .sp2d-hero__content { position: relative; z-index: 1; }
    .sp2d-eyebrow {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 5px 13px; border-radius: 999px;
        background: rgba(255,255,255,.16);
        border: 1px solid rgba(255,255,255,.28);
        font-size: 12px; font-weight: 700; letter-spacing: .04em;
        backdrop-filter: blur(4px);
    }
    .sp2d-hero h1 {
        margin: 12px 0 4px; font-size: clamp(22px, 3vw, 32px);
        font-weight: 800; letter-spacing: -.01em; line-height: 1.1;
        color: #fff !important;
    }
    .sp2d-hero .sp2d-eyebrow,
    .sp2d-hero p,
    .sp2d-hero .sp2d-hero__badge { color: #fff; }
    .sp2d-hero p { opacity: .9; }
    .sp2d-hero p { margin: 0; max-width: 620px; color: rgba(255,255,255,.82); font-size: 14px; line-height: 1.55; }
    .sp2d-hero__badge {
        display: inline-flex; align-items: center; gap: 8px;
        margin-top: 14px; padding: 8px 15px; border-radius: 12px;
        background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.25);
        font-size: 13px; font-weight: 600;
    }
    .sp2d-hero__icon {
        width: 84px; height: 84px; border-radius: 24px;
        display: grid; place-items: center;
        background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.26);
        animation: sp2d-float 5s ease-in-out infinite;
    }
    .sp2d-hero__icon i { font-size: 44px; }

    /* ===== STAT CARDS ===== */
    .sp2d-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 22px; }
    .sp2d-stat {
        position: relative; overflow: hidden;
        border-radius: 18px; padding: 18px 18px 18px 20px;
        background: #fff; border: 1px solid var(--sp2d-line);
        box-shadow: 0 10px 26px -18px rgba(15, 27, 51, .4);
        cursor: pointer;
        transition: transform .25s cubic-bezier(.2,.8,.2,1), box-shadow .25s ease, border-color .25s ease;
        animation: sp2d-rise .5s ease both;
    }
    .sp2d-stat:nth-child(1) { animation-delay: .04s; }
    .sp2d-stat:nth-child(2) { animation-delay: .12s; }
    .sp2d-stat:nth-child(3) { animation-delay: .20s; }
    .sp2d-stat:nth-child(4) { animation-delay: .28s; }
    .sp2d-stat::before {
        content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 5px;
        background: var(--accent, var(--sp2d-primary));
        transform: scaleY(.4); transform-origin: center;
        transition: transform .3s ease;
    }
    .sp2d-stat:hover { transform: translateY(-5px); box-shadow: 0 20px 38px -18px rgba(15,27,51,.5); border-color: transparent; }
    .sp2d-stat:hover::before { transform: scaleY(1); }
    .sp2d-stat.is-active { border-color: var(--accent, var(--sp2d-primary)); box-shadow: 0 16px 34px -18px var(--accent, rgba(79,70,229,.5)); }
    .sp2d-stat.is-active::before { transform: scaleY(1); }
    .sp2d-stat__top { display: flex; align-items: center; justify-content: space-between; }
    .sp2d-stat__icon {
        width: 46px; height: 46px; border-radius: 14px; display: grid; place-items: center;
        background: color-mix(in srgb, var(--accent, var(--sp2d-primary)) 14%, #fff);
        color: var(--accent, var(--sp2d-primary));
        transition: transform .3s ease;
    }
    .sp2d-stat:hover .sp2d-stat__icon { transform: rotate(-8deg) scale(1.08); }
    .sp2d-stat__icon i { font-size: 24px; }
    .sp2d-stat__num { font-size: 30px; font-weight: 800; line-height: 1; color: var(--sp2d-ink); }
    .sp2d-stat__label { margin-top: 6px; font-size: 12.5px; font-weight: 600; color: var(--sp2d-muted); }
    .sp2d-stat__spark {
        position: absolute; right: 14px; bottom: 10px; font-size: 11px; font-weight: 700;
        color: var(--accent, var(--sp2d-primary)); opacity: .0; transform: translateY(6px);
        transition: opacity .25s ease, transform .25s ease;
    }
    .sp2d-stat:hover .sp2d-stat__spark { opacity: .9; transform: translateY(0); }

    /* ===== TOOLBAR ===== */
    .sp2d-toolbar {
        border-radius: 16px; background: #fff; border: 1px solid var(--sp2d-line);
        padding: 14px 16px; margin-bottom: 18px;
        box-shadow: 0 8px 22px -18px rgba(15,27,51,.4);
        animation: sp2d-rise .5s ease both .3s;
    }
    .sp2d-input-wrap { position: relative; }
    .sp2d-input-wrap i {
        position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
        color: var(--sp2d-muted); font-size: 20px;
    }
    .sp2d-input {
        width: 100%; height: 42px; padding: 0 14px 0 42px;
        border-radius: 11px; border: 1px solid var(--sp2d-line); background: #f8fafc;
        font-size: 14px; transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
    }
    .sp2d-input:focus { outline: none; background: #fff; border-color: var(--sp2d-primary); box-shadow: 0 0 0 4px rgba(79,70,229,.12); }
    .sp2d-select {
        height: 42px; border-radius: 11px; border: 1px solid var(--sp2d-line);
        background: #f8fafc; font-size: 14px; padding: 0 12px;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .sp2d-select:focus { outline: none; border-color: var(--sp2d-primary); box-shadow: 0 0 0 4px rgba(79,70,229,.12); }
    .sp2d-btn {
        height: 42px; border: none; border-radius: 11px; padding: 0 18px;
        font-weight: 700; font-size: 14px; display: inline-flex; align-items: center; gap: 7px;
        color: #fff; background: linear-gradient(120deg, var(--sp2d-primary), var(--sp2d-primary-2));
        box-shadow: 0 10px 22px -10px rgba(79,70,229,.7);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .sp2d-btn:hover { transform: translateY(-2px); box-shadow: 0 16px 28px -12px rgba(79,70,229,.8); color: #fff; }
    .sp2d-btn-ghost {
        height: 42px; width: 42px; border-radius: 11px; display: inline-grid; place-items: center;
        border: 1px solid var(--sp2d-line); background: #fff; color: var(--sp2d-muted);
        transition: all .2s ease;
    }
    .sp2d-btn-ghost:hover { color: var(--sp2d-primary); border-color: var(--sp2d-primary); transform: rotate(90deg); }

    /* ===== TABLE / CARD ===== */
    .sp2d-panel {
        border-radius: 18px; overflow: hidden; background: #fff;
        border: 1px solid var(--sp2d-line); box-shadow: 0 14px 36px -22px rgba(15,27,51,.45);
        animation: sp2d-rise .5s ease both .38s;
    }
    .sp2d-panel__head {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-bottom: 1px solid var(--sp2d-line);
        background: linear-gradient(90deg, #fafbff, #fff);
    }
    .sp2d-panel__title { font-weight: 800; color: var(--sp2d-ink); font-size: 15px; display: flex; align-items: center; gap: 9px; }
    .sp2d-panel__title i { color: var(--sp2d-primary); }
    .sp2d-count-chip {
        font-size: 12px; font-weight: 700; color: var(--sp2d-primary);
        background: rgba(79,70,229,.1); padding: 4px 11px; border-radius: 999px;
    }

    .sp2d-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .sp2d-table thead th {
        text-align: left; font-size: 11px; letter-spacing: .06em; text-transform: uppercase;
        color: var(--sp2d-muted); font-weight: 800; padding: 13px 18px;
        background: #f6f8fc; border-bottom: 1px solid var(--sp2d-line); white-space: nowrap;
    }
    .sp2d-table tbody td { padding: 15px 18px; border-bottom: 1px solid var(--sp2d-line); font-size: 13.5px; vertical-align: middle; }
    .sp2d-row { animation: sp2d-rise .45s ease both; transition: background .18s ease; }
    .sp2d-row:hover { background: #f7f9ff; }
    .sp2d-row td:first-child { border-left: 3px solid transparent; }
    .sp2d-row.is-actionable td:first-child { border-left-color: var(--sp2d-amber); }
    .sp2d-row:last-child td { border-bottom: none; }

    .sp2d-docnum { font-weight: 800; color: var(--sp2d-primary); }
    .sp2d-docnum-empty { color: var(--sp2d-muted); font-style: italic; font-size: 12.5px; }
    .sp2d-sub { color: var(--sp2d-muted); font-size: 11.5px; }
    .sp2d-meta { font-size: 12px; line-height: 1.7; }
    .sp2d-meta .k { color: var(--sp2d-muted); }
    .sp2d-amount { font-weight: 800; color: var(--sp2d-ink); font-size: 14px; white-space: nowrap; }

    .sp2d-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 11px; border-radius: 999px; font-size: 11.5px; font-weight: 700; white-space: nowrap;
    }
    .sp2d-badge .dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .sp2d-badge.is-pulse .dot { animation: sp2d-pulse 1.6s ease-out infinite; }
    .sp2d-badge--amber  { color: var(--sp2d-amber);  background: #fef3c7; }
    .sp2d-badge--slate  { color: var(--sp2d-slate);  background: #e2e8f0; }
    .sp2d-badge--rose   { color: var(--sp2d-rose);   background: #ffe4e6; }
    .sp2d-badge--cyan   { color: var(--sp2d-cyan);   background: #cffafe; }
    .sp2d-badge--green  { color: var(--sp2d-green);  background: #d1fae5; }
    .sp2d-badge--primary{ color: var(--sp2d-primary);background: #e0e7ff; }

    /* verifikator mini-pills */
    .sp2d-verif { display: flex; gap: 6px; }
    .sp2d-vchip {
        display: inline-flex; align-items: center; gap: 3px;
        padding: 3px 8px; border-radius: 8px; font-size: 10.5px; font-weight: 700;
        background: #f1f5f9; color: var(--sp2d-slate);
    }
    .sp2d-vchip i { font-size: 13px; }
    .sp2d-vchip.ok   { background: #d1fae5; color: var(--sp2d-green); }
    .sp2d-vchip.wait { background: #fef3c7; color: var(--sp2d-amber); }
    .sp2d-vchip.bad  { background: #ffe4e6; color: var(--sp2d-rose); }

    /* action buttons */
    .sp2d-action {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 14px; border-radius: 10px; font-size: 12.5px; font-weight: 700;
        text-decoration: none; transition: transform .18s ease, box-shadow .2s ease, background .2s ease;
        white-space: nowrap;
    }
    .sp2d-action i { font-size: 16px; }
    .sp2d-action--create { color: #fff; background: linear-gradient(120deg, var(--sp2d-primary), var(--sp2d-primary-2)); box-shadow: 0 8px 18px -8px rgba(79,70,229,.7); }
    .sp2d-action--create:hover { transform: translateY(-2px); color: #fff; box-shadow: 0 14px 24px -10px rgba(79,70,229,.85); }
    .sp2d-action--manage { color: #fff; background: linear-gradient(120deg, #f59e0b, #d97706); box-shadow: 0 8px 18px -8px rgba(217,119,6,.6); }
    .sp2d-action--manage:hover { transform: translateY(-2px); color: #fff; box-shadow: 0 14px 24px -10px rgba(217,119,6,.75); }
    .sp2d-action--view { color: var(--sp2d-slate); background: #f1f5f9; border: 1px solid var(--sp2d-line); }
    .sp2d-action--view:hover { transform: translateY(-2px); color: var(--sp2d-primary); border-color: var(--sp2d-primary); }

    /* empty state */
    .sp2d-empty { text-align: center; padding: 56px 20px; }
    .sp2d-empty__icon {
        width: 84px; height: 84px; margin: 0 auto 14px; border-radius: 26px; display: grid; place-items: center;
        background: linear-gradient(135deg, #eef2ff, #f0fdfa); color: var(--sp2d-primary);
        animation: sp2d-float 4s ease-in-out infinite;
    }
    .sp2d-empty__icon i { font-size: 42px; }
    .sp2d-empty h6 { font-weight: 800; color: var(--sp2d-ink); margin: 0 0 4px; }
    .sp2d-empty p { color: var(--sp2d-muted); margin: 0; font-size: 13.5px; }

    /* ===== keyframes ===== */
    @keyframes sp2d-fade-down { from { opacity: 0; transform: translateY(-16px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes sp2d-rise { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes sp2d-float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
    @keyframes sp2d-ribbon { to { background-position: 220% 0; } }
    @keyframes sp2d-pulse {
        0% { box-shadow: 0 0 0 0 currentColor; }
        70% { box-shadow: 0 0 0 6px transparent; }
        100% { box-shadow: 0 0 0 0 transparent; }
    }

    @media (max-width: 991px) {
        .sp2d-stats { grid-template-columns: repeat(2, 1fr); }
        .sp2d-hero__icon { display: none; }
    }
    @media (max-width: 575px) {
        .sp2d-stats { grid-template-columns: 1fr 1fr; gap: 12px; }
        .sp2d-hero { padding: 22px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .sp2d-hero, .sp2d-stat, .sp2d-toolbar, .sp2d-panel, .sp2d-row,
        .sp2d-hero::before, .sp2d-hero::after, .sp2d-hero__bar, .sp2d-hero__icon,
        .sp2d-empty__icon, .sp2d-badge.is-pulse .dot { animation: none !important; }
    }
</style>
