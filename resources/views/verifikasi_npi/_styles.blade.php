{{-- ============================================================
     Shared design system for NPI Verification pages
     (index + detail, Honorarium + Perjaldin)
     ============================================================ --}}
<style>
    /* === Hero === */
    .vnpi-hero {
        position: relative; overflow: hidden; border: 0 !important;
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 55%, #6610f2 100%);
        color: #fff; border-radius: 1.1rem !important;
        box-shadow: 0 12px 30px -12px rgba(76,29,149,.45) !important;
    }
    .vnpi-hero.is-revisi { background: linear-gradient(135deg, #be123c 0%, #e11d48 55%, #f43f5e 100%); box-shadow: 0 12px 30px -12px rgba(190,18,60,.45) !important; }
    .vnpi-hero.is-final  { background: linear-gradient(135deg, #047857 0%, #059669 55%, #10b981 100%); box-shadow: 0 12px 30px -12px rgba(4,120,87,.45) !important; }
    .vnpi-hero::before {
        content: ''; position: absolute; inset: 0;
        background-image:
            radial-gradient(circle at 92% 8%,  rgba(255,255,255,.18) 0%, transparent 30%),
            radial-gradient(circle at 8% 100%, rgba(255,255,255,.08) 0%, transparent 40%);
        pointer-events: none;
    }
    .vnpi-hero::after {
        content: ''; position: absolute; right: -30px; top: -30px;
        width: 170px; height: 170px; border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,.12) 0%, transparent 70%);
        pointer-events: none;
    }
    .vnpi-hero .card-body { position: relative; z-index: 1; }
    .vnpi-hero h3, .vnpi-hero h4 { color: #fff !important; }
    .vnpi-hero .hero-sub { color: rgba(255,255,255,.85); }
    .vnpi-hero .hero-tag {
        background: rgba(255,255,255,.18); color: #fff;
        padding: .3rem .8rem; border-radius: 50rem; font-size: .68rem;
        font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
        backdrop-filter: blur(6px); border: 1px solid rgba(255,255,255,.2);
    }
    .vnpi-hero .btn-hero-light { color: #fff; border: 1px solid rgba(255,255,255,.4); background: rgba(255,255,255,.08); }
    .vnpi-hero .btn-hero-light:hover { background: rgba(255,255,255,.2); color: #fff; }

    .hero-meta {
        background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.22) !important;
        backdrop-filter: blur(8px); border-radius: .85rem !important;
    }
    .hero-meta .field-label { color: rgba(255,255,255,.7) !important; }
    .hero-meta .field-value { color: #fff !important; font-weight: 600; }
    .hero-meta .nominal-hero { font-size: 1.3rem; font-weight: 800; color: #fff; letter-spacing: -.01em; }

    /* === Status pill === */
    .vnpi-pill {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .4rem .9rem; font-size: .72rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .06em; border-radius: 50rem; line-height: 1;
    }
    .vnpi-pill::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: currentColor; box-shadow: 0 0 0 3px rgba(255,255,255,.18); }
    .vnpi-pill.s-wait   { background: rgba(255,255,255,.22); color: #fff; }
    .vnpi-pill.s-revisi { background: #dc3545; color: #fff; }
    .vnpi-pill.s-final  { background: #198754; color: #fff; }
    .vnpi-pill.s-alert  { background: #fbbf24; color: #422006; }

    /* === Cards === */
    .vnpi-card {
        background: #fff; border: 1px solid #eef0f4; border-radius: .95rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
        transition: transform .15s ease, box-shadow .2s ease; overflow: hidden;
    }
    .vnpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 22px -8px rgba(15,23,42,.12); }
    .vnpi-card .vnpi-card-head { padding: 1rem 1.15rem .6rem; display: flex; align-items: center; gap: .8rem; }
    .vnpi-card .vnpi-card-head .ico-wrap {
        width: 38px; height: 38px; flex-shrink: 0; border-radius: .65rem;
        background: var(--card-tint, rgba(13,110,253,.1)); color: var(--card-accent, #0d6efd);
        display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem;
    }
    .vnpi-card .vnpi-card-head h6 { margin: 0; font-size: .82rem; letter-spacing: .05em; text-transform: uppercase; font-weight: 800; color: #0f172a; }
    .vnpi-card .vnpi-card-head .head-sub { display: block; font-size: .72rem; font-weight: 500; color: #94a3b8; text-transform: none; letter-spacing: 0; margin-top: 2px; }
    .vnpi-card .vnpi-card-body { padding: .35rem 1.15rem 1.15rem; }

    .vnpi-card.c-blue   { --card-accent: #2563eb; --card-tint: rgba(37,99,235,.12); }
    .vnpi-card.c-purple { --card-accent: #7c3aed; --card-tint: rgba(124,58,237,.12); }
    .vnpi-card.c-teal   { --card-accent: #0d9488; --card-tint: rgba(13,148,136,.12); }
    .vnpi-card.c-amber  { --card-accent: #d97706; --card-tint: rgba(217,119,6,.12); }
    .vnpi-card.c-green  { --card-accent: #16a34a; --card-tint: rgba(22,163,74,.12); }
    .vnpi-card.c-slate  { --card-accent: #64748b; --card-tint: rgba(100,116,139,.12); }
    .vnpi-card.c-rose   { --card-accent: #e11d48; --card-tint: rgba(225,29,72,.12); }

    .field-label { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; margin-bottom: .2rem; display: block; }
    .field-value { font-weight: 600; color: #0f172a; }

    /* Mini doc cards */
    .mini-doc { background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #e2e8f0; border-radius: .65rem; padding: .65rem .8rem; }
    .mini-doc .doc-label { font-size: .65rem; font-weight: 800; color: var(--card-accent, #2563eb); text-transform: uppercase; letter-spacing: .08em; }

    /* Doc rows (kelengkapan) */
    .doc-row { padding: .65rem .85rem; border-radius: .65rem; transition: transform .12s ease; border: 1px solid transparent; }
    .doc-row + .doc-row { margin-top: .35rem; }
    .doc-row:hover { transform: translateX(3px); }
    .doc-row.is-ready    { background: rgba(22,163,74,.07); border-color: rgba(22,163,74,.18); }
    .doc-row.is-optional { background: #f8fafc; border-color: #e2e8f0; }

    /* Data table */
    .vnpi-table { font-size: .8rem; margin-bottom: 0; }
    .vnpi-table thead th { font-size: .65rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; background: #f8fafc; border-color: #e2e8f0; font-weight: 700; }
    .vnpi-table td { vertical-align: middle; border-color: #eef0f4; }

    /* Approval / verifikator rows */
    .approval-row { border: 1px solid #eef0f4; border-left-width: 4px; border-radius: .7rem; padding: .85rem 1rem; background: #fff; transition: transform .12s ease, box-shadow .15s ease; }
    .approval-row + .approval-row { margin-top: .6rem; }
    .approval-row:hover { transform: translateX(2px); box-shadow: 0 4px 12px -4px rgba(15,23,42,.08); }
    .approval-row.is-approved { border-left-color: #16a34a; background: linear-gradient(90deg, rgba(22,163,74,.07) 0%, #fff 60%); }
    .approval-row.is-revision { border-left-color: #e11d48; background: linear-gradient(90deg, rgba(225,29,72,.07) 0%, #fff 60%); }
    .approval-row.is-pending  { border-left-color: #f59e0b; background: linear-gradient(90deg, rgba(245,158,11,.08) 0%, #fff 60%); }
    .approval-row.is-waiting  { border-left-color: #cbd5e1; }
    .approval-row.is-mine     { box-shadow: 0 0 0 2px rgba(79,70,229,.25); }
    .approval-row .role-avatar { width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; background: #f1f5f9; color: #475569; font-size: 1.1rem; }
    .approval-row.is-approved .role-avatar { background: rgba(22,163,74,.15); color: #16a34a; }
    .approval-row.is-revision .role-avatar { background: rgba(225,29,72,.15); color: #e11d48; }
    .approval-row.is-pending  .role-avatar { background: rgba(245,158,11,.15); color: #d97706; }
    .approval-badge { font-size: .62rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; padding: .25rem .55rem; border-radius: 50rem; }

    /* Action hero (panel kanan) */
    .action-hero { background: linear-gradient(135deg, #4f46e5 0%, #6610f2 100%); color: #fff; border-radius: .85rem; padding: 1.1rem 1.15rem 1.25rem; position: relative; overflow: hidden; margin-bottom: 1rem; }
    .action-hero::before { content: ''; position: absolute; right: -30%; top: -50%; width: 80%; height: 200%; background: radial-gradient(ellipse, rgba(255,255,255,.16) 0%, transparent 60%); }
    .action-hero > * { position: relative; z-index: 1; }
    .action-hero .ah-label { color: rgba(255,255,255,.75); font-size: .68rem; letter-spacing: .08em; text-transform: uppercase; font-weight: 700; }
    .action-hero .ah-nominal { font-size: 1.45rem; font-weight: 800; color: #fff; letter-spacing: -.01em; }
    .role-action-box { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2); border-radius: .7rem; padding: .7rem .8rem; }
    .role-action-box + .role-action-box { margin-top: .55rem; }

    /* Readiness checklist */
    .readiness-progress { height: 8px; background: #eef0f4; border-radius: 50rem; overflow: hidden; margin-bottom: .85rem; }
    .readiness-progress .bar { height: 100%; border-radius: 50rem; background: linear-gradient(90deg, #f59e0b, #16a34a); transition: width .4s ease; }
    .ready-list { list-style: none; padding: 0; margin: 0; }
    .ready-list li { display: flex; align-items: flex-start; gap: .6rem; padding: .55rem 0; font-size: .82rem; border-bottom: 1px dashed rgba(15,23,42,.07); }
    .ready-list li:last-child { border-bottom: 0; padding-bottom: 0; }
    .ready-list .ico { width: 24px; height: 24px; flex: 0 0 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: .95rem; margin-top: 1px; }
    .ready-list .ico.ok { background: rgba(22,163,74,.15); color: #16a34a; }
    .ready-list .ico.no { background: rgba(225,29,72,.15); color: #e11d48; }

    /* Timeline */
    .vnpi-timeline-item { position: relative; padding-left: 1.5rem; padding-bottom: 1.1rem; border-left: 2px solid #eef0f4; }
    .vnpi-timeline-item:last-child { padding-bottom: 0; }
    .vnpi-timeline-item::before { content: ''; position: absolute; left: -8px; top: 4px; width: 14px; height: 14px; border-radius: 50%; background: #fff; border: 2px solid #94a3b8; }
    .vnpi-timeline-item.is-active::before { border-color: #4f46e5; background: #4f46e5; box-shadow: 0 0 0 4px rgba(79,70,229,.15); }

    /* Index stat tiles */
    .vnpi-stat { border: 1px solid #eef0f4; border-radius: 1rem; background: #fff; padding: 1.1rem 1.2rem; box-shadow: 0 1px 2px rgba(15,23,42,.04); transition: transform .15s ease, box-shadow .2s ease; height: 100%; }
    .vnpi-stat:hover { transform: translateY(-2px); box-shadow: 0 10px 24px -12px rgba(15,23,42,.18); }
    .vnpi-stat .stat-ico { width: 50px; height: 50px; border-radius: .8rem; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .vnpi-stat .stat-num { font-size: 1.9rem; font-weight: 800; line-height: 1; color: #0f172a; }
    .vnpi-stat .stat-label { font-size: .72rem; color: #64748b; font-weight: 600; }
    .vnpi-stat.t-amber .stat-ico { background: rgba(245,158,11,.14); color: #d97706; }
    .vnpi-stat.t-green .stat-ico { background: rgba(22,163,74,.14); color: #16a34a; }
    .vnpi-stat.t-rose  .stat-ico { background: rgba(225,29,72,.14); color: #e11d48; }
    .vnpi-stat.t-blue  .stat-ico { background: rgba(37,99,235,.14); color: #2563eb; }

    /* Index table */
    .vnpi-list thead th { font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; background: #f8fafc; border-color: #eef0f4; font-weight: 700; }
    .vnpi-list td { vertical-align: middle; border-color: #f1f5f9; }
    .vnpi-list tbody tr { transition: background .12s ease; }
    .vnpi-list tbody tr.row-actionable { background: rgba(245,158,11,.06); }
    .vnpi-list tbody tr:hover { background: rgba(37,99,235,.04); }

    .npi-min-w-0 { min-width: 0; }
    .vnpi-sticky { position: static; }
    @media (min-width: 1200px) { .vnpi-sticky { position: sticky; top: 88px; } }
</style>
