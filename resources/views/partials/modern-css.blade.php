<style>
    body[data-bs-theme="blue-theme"] .main-content { background: #f6f7fb; }

    /* ============ HERO STATUS-AWARE ============ */
    .kontrak-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.25rem;
        padding: 1.5rem 2rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 14px 30px rgba(15,23,42,.18);
        animation: heroIn .55s cubic-bezier(.22,1,.36,1) both;
    }
    .kontrak-hero::before, .kontrak-hero::after {
        content: ''; position: absolute; border-radius: 50%;
    }
    .kontrak-hero::before {
        right: -90px; top: -90px;
        width: 280px; height: 280px;
        background: rgba(255,255,255,.10);
    }
    .kontrak-hero::after {
        right: 60px; bottom: -70px;
        width: 180px; height: 180px;
        background: rgba(255,255,255,.07);
    }
    .kontrak-hero > * { position: relative; z-index: 1; }
    .hero-aktif    { background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%); }
    .hero-selesai  { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #4338ca 100%); }
    .hero-draft    { background: linear-gradient(135deg, #475569 0%, #334155 100%); }
    .hero-revisi   { background: linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%); }
    .hero-pending  { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 50%, #0369a1 100%); }

    .briefcase-illust {
        position: absolute; right: 1.5rem; top: 50%;
        transform: translateY(-50%) rotate(-8deg);
        font-size: 7rem; opacity: .14;
    }

    .hero-status-pill {
        display: inline-flex; align-items: center; gap: .45rem;
        background: rgba(255,255,255,.22);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.28);
        font-weight: 700; font-size: .78rem;
        padding: .4rem .9rem;
        border-radius: 999px;
        text-transform: uppercase; letter-spacing: .04em;
        color: #fff !important;
    }
    .hero-status-pill::before {
        content: '';
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(255,255,255,.3);
        animation: pulseDot 1.6s ease-in-out infinite;
    }
    @keyframes pulseDot {
        0%, 100% { box-shadow: 0 0 0 0 rgba(255,255,255,.45); }
        50%      { box-shadow: 0 0 0 8px rgba(255,255,255,0); }
    }

    .hero-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: #fff !important;
        margin: 0 0 .35rem;
        letter-spacing: -.01em;
    }
    .hero-meta {
        font-size: .8rem;
        color: rgba(255,255,255,.92) !important;
        margin: 0;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .hero-meta strong { color: #fff !important; }

    .btn-hero {
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
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .btn-hero:hover {
        background: rgba(255,255,255,.30);
        color: #fff;
        transform: translateY(-1px);
    }
    .btn-hero-primary {
        background: #fff;
        color: #047857;
        font-weight: 700;
    }
    .btn-hero-primary:hover {
        background: #fff; color: #065f46;
        box-shadow: 0 6px 14px rgba(0,0,0,.15);
    }

    /* ============ Modern Card ============ */
    .modern-card {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1.15rem;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(15,23,42,.04);
        transition: box-shadow .3s ease;
        margin-bottom: 1.15rem;
    }
    .modern-card:hover {
        box-shadow: 0 14px 32px rgba(15,23,42,.07);
    }
    .modern-card .mc-head {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #f1f3f7;
        background: linear-gradient(180deg, #fafbff 0%, #ffffff 100%);
        display: flex; align-items: center; justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .modern-card .mc-head h6 {
        margin: 0;
        font-size: .95rem;
        font-weight: 800;
        color: #0f172a;
        display: inline-flex; align-items: center; gap: .55rem;
    }
    .modern-card .mc-head h6 i.mc-h-icon {
        width: 32px; height: 32px;
        border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(99,102,241,.12);
        color: #4f46e5;
        font-size: 1rem;
    }
    .mc-h-icon.icon-warning { background: rgba(245,158,11,.15) !important; color: #b45309 !important; }
    .mc-h-icon.icon-info    { background: rgba(14,165,233,.15) !important; color: #0369a1 !important; }
    .mc-h-icon.icon-success { background: rgba(16,185,129,.15) !important; color: #047857 !important; }
    .mc-h-icon.icon-secondary { background: rgba(100,116,139,.15) !important; color: #475569 !important; }
    .modern-card .mc-body { padding: 1.25rem 1.5rem; }

    /* ============ Activation status checks ============ */
    .activation-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: .75rem;
        margin-bottom: 1rem;
    }
    .check-item {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: .85rem;
        padding: .85rem 1rem;
        display: flex;
        align-items: center;
        gap: .75rem;
        transition: all .25s ease;
    }
    .check-item:hover { transform: translateY(-2px); }
    .check-item.is-done {
        background: linear-gradient(135deg, rgba(16,185,129,.08), rgba(16,185,129,.02));
        border-color: rgba(16,185,129,.30);
    }
    .check-item.is-pending {
        background: linear-gradient(135deg, rgba(244,63,94,.06), rgba(244,63,94,.02));
        border-color: rgba(244,63,94,.25);
    }
    .check-item .ci-icon {
        width: 36px; height: 36px;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
        transition: transform .35s cubic-bezier(.22,1,.36,1);
    }
    .check-item.is-done .ci-icon {
        background: linear-gradient(135deg, #34d399, #10b981);
        color: #fff;
        box-shadow: 0 4px 10px rgba(16,185,129,.30);
    }
    .check-item.is-pending .ci-icon {
        background: rgba(244,63,94,.15);
        color: #b91c1c;
    }
    .check-item:hover .ci-icon { transform: scale(1.1) rotate(8deg); }
    .check-item .ci-label {
        font-weight: 700;
        color: #0f172a;
        font-size: .87rem;
    }
    .check-item .ci-status {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-top: .15rem;
    }
    .check-item.is-done .ci-status { color: #047857; }
    .check-item.is-pending .ci-status { color: #b91c1c; }

    /* ============ Status Alert (info banner) ============ */
    .status-banner {
        display: flex; align-items: flex-start; gap: .85rem;
        border-radius: .85rem;
        padding: 1rem 1.15rem;
        border: 1px solid;
    }
    .status-banner-success {
        background: rgba(16,185,129,.06);
        border-color: rgba(16,185,129,.20);
        color: #047857;
    }
    .status-banner-warning {
        background: rgba(245,158,11,.08);
        border-color: rgba(245,158,11,.25);
        color: #92400e;
    }
    .sb-icon {
        width: 38px; height: 38px;
        border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .status-banner-success .sb-icon { background: rgba(16,185,129,.15); color: #047857; }
    .status-banner-warning .sb-icon { background: rgba(245,158,11,.18); color: #b45309; }

    /* ============ Progress Card ============ */
    .progress-card {
        background: linear-gradient(135deg, #fff 0%, #fafbff 100%);
        border: 1px solid #eef0f4;
        border-radius: 1.15rem;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.15rem;
        position: relative;
        overflow: hidden;
        animation: secIn .55s cubic-bezier(.22,1,.36,1) .15s both;
    }
    .progress-card::after {
        content: '';
        position: absolute;
        right: -100px; top: -100px;
        width: 220px; height: 220px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(16,185,129,.10), transparent 70%);
    }
    .progress-card > * { position: relative; z-index: 1; }
    .progress-label {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
        margin-bottom: .25rem;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .progress-amount {
        font-size: 1.9rem;
        font-weight: 800;
        color: #047857;
        font-variant-numeric: tabular-nums;
        letter-spacing: -.01em;
        line-height: 1;
        background: linear-gradient(135deg, #10b981, #059669);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .progress-amount-total {
        font-size: .9rem;
        color: #64748b;
        font-weight: 600;
        margin-left: .35rem;
    }
    .progress-percent-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        background: linear-gradient(135deg, rgba(16,185,129,.12), rgba(5,150,105,.05));
        color: #047857;
        border: 1px solid rgba(16,185,129,.22);
        font-weight: 700;
        font-size: .82rem;
        padding: .35rem .85rem;
        border-radius: 999px;
    }
    .progress-track {
        height: 14px;
        background: #f1f5f9;
        border-radius: 999px;
        overflow: hidden;
        margin-top: 1rem;
        position: relative;
    }
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #10b981, #14b8a6, #06b6d4);
        background-size: 200% 100%;
        border-radius: 999px;
        transition: width .9s cubic-bezier(.22,1,.36,1);
        animation: shimmerSlide 3s linear infinite;
        box-shadow: 0 2px 8px rgba(16,185,129,.30);
    }
    @keyframes shimmerSlide {
        0%   { background-position: 0% 0; }
        100% { background-position: 200% 0; }
    }

    /* ============ Summary Cards ============ */
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 1.15rem;
    }
    .summary-card {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1.1rem;
        padding: 1rem 1.15rem;
        position: relative;
        overflow: hidden;
        transition: all .3s cubic-bezier(.22,1,.36,1);
        animation: secIn .55s cubic-bezier(.22,1,.36,1) both;
    }
    .summary-card:nth-child(1) { animation-delay: .20s; }
    .summary-card:nth-child(2) { animation-delay: .27s; }
    .summary-card:nth-child(3) { animation-delay: .34s; }
    .summary-card:nth-child(4) { animation-delay: .41s; }
    .summary-card::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: var(--sc-accent, #6366f1);
    }
    .summary-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 30px rgba(15,23,42,.08);
        border-color: var(--sc-accent);
    }
    .sc-primary { --sc-accent: #6366f1; --sc-soft: rgba(99,102,241,.10); }
    .sc-info    { --sc-accent: #0ea5e9; --sc-soft: rgba(14,165,233,.10); }
    .sc-success { --sc-accent: #10b981; --sc-soft: rgba(16,185,129,.10); }
    .sc-warning { --sc-accent: #f59e0b; --sc-soft: rgba(245,158,11,.10); }

    .sc-head {
        display: flex; align-items: center; gap: .55rem;
        margin-bottom: .85rem;
    }
    .sc-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--sc-soft);
        color: var(--sc-accent);
        font-size: 1rem;
        flex-shrink: 0;
        transition: transform .3s ease;
    }
    .summary-card:hover .sc-icon { transform: rotate(-8deg) scale(1.05); }
    .sc-label {
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
    }
    .sc-row {
        font-size: .8rem;
        color: #475569;
        margin-bottom: .25rem;
    }
    .sc-row strong { color: #0f172a; }
    .sc-money {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--sc-accent);
        font-variant-numeric: tabular-nums;
    }
    .sc-mono {
        font-family: ui-monospace, "SF Mono", monospace;
        font-size: .82rem;
        font-weight: 700;
        color: #4338ca;
    }
    .sc-rek-chip {
        background: var(--sc-soft);
        color: var(--sc-accent);
        font-family: ui-monospace, "SF Mono", monospace;
        font-size: .75rem;
        padding: .25rem .55rem;
        border-radius: .4rem;
        display: inline-block;
        margin-top: .35rem;
        font-weight: 600;
    }

    /* ============ Document section uniform style ============ */
    .doc-status-card {
        background: linear-gradient(180deg, #fafbff 0%, #ffffff 100%);
        border: 1px solid #eef0f4;
        border-radius: .85rem;
        padding: 1rem;
        height: 100%;
        transition: all .25s ease;
    }
    .doc-status-card:hover {
        border-color: #c7d2fe;
        transform: translateY(-2px);
    }
    .doc-status-card .dsc-label {
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
        margin-bottom: .35rem;
    }
    .doc-status-card .dsc-value {
        font-weight: 700;
        color: #0f172a;
        font-size: .85rem;
        margin-bottom: .15rem;
    }
    .doc-status-card .dsc-meta {
        font-size: .72rem;
        color: #64748b;
    }
    .badge-doc {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .7rem;
        font-weight: 700;
        padding: .3rem .75rem;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .badge-doc-success { background: linear-gradient(135deg, #34d399, #10b981); color: #fff; box-shadow: 0 4px 10px rgba(16,185,129,.35); }
    .badge-doc-danger  { background: linear-gradient(135deg, #fb7185, #f43f5e); color: #fff; box-shadow: 0 4px 10px rgba(244,63,94,.35); }

    /* ============ Action buttons ============ */
    .btn-act-modern {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .78rem;
        font-weight: 600;
        padding: .5rem .95rem;
        border-radius: .6rem;
        border: 1px solid transparent;
        text-decoration: none;
        transition: all .18s ease;
        cursor: pointer;
    }
    .btn-act-modern:hover { transform: translateY(-1px); }
    .btn-act-primary {
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: #fff;
        box-shadow: 0 4px 10px rgba(99,102,241,.30);
    }
    .btn-act-primary:hover {
        color: #fff;
        box-shadow: 0 8px 18px rgba(99,102,241,.40);
    }
    .btn-act-success {
        background: linear-gradient(135deg, #34d399, #10b981);
        color: #fff;
        box-shadow: 0 4px 10px rgba(16,185,129,.30);
    }
    .btn-act-success:hover {
        color: #fff;
        box-shadow: 0 8px 18px rgba(16,185,129,.40);
    }
    .btn-act-danger {
        background: linear-gradient(135deg, #fb7185, #f43f5e);
        color: #fff;
        box-shadow: 0 4px 10px rgba(244,63,94,.30);
    }
    .btn-act-danger:hover {
        color: #fff;
        box-shadow: 0 8px 18px rgba(244,63,94,.40);
    }
    .btn-act-info {
        background: linear-gradient(135deg, #38bdf8, #0ea5e9);
        color: #fff;
        box-shadow: 0 4px 10px rgba(14,165,233,.30);
    }
    .btn-act-info:hover {
        color: #fff;
        box-shadow: 0 8px 18px rgba(14,165,233,.40);
    }
    .btn-act-soft {
        background: rgba(99,102,241,.08);
        color: #4338ca;
        border-color: rgba(99,102,241,.18);
    }
    .btn-act-soft:hover {
        background: #6366f1; color: #fff; border-color: #6366f1;
    }
    .btn-act-pdf {
        background: rgba(244,63,94,.08);
        color: #be123c;
        border-color: rgba(244,63,94,.18);
    }
    .btn-act-pdf:hover { background: #f43f5e; color: #fff; border-color: #f43f5e; }

    /* ============ Tabs ============ */
    .tabs-bar {
        display: flex;
        gap: .35rem;
        background: #fafbff;
        border-bottom: 1px solid #f1f3f7;
        padding: .5rem .85rem 0;
    }
    .tabs-bar .tab-btn {
        background: transparent;
        border: 0;
        padding: .65rem 1.1rem .85rem;
        color: #64748b;
        font-size: .85rem;
        font-weight: 600;
        cursor: pointer;
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        transition: all .25s ease;
    }
    .tabs-bar .tab-btn:hover { color: #1e293b; }
    .tabs-bar .tab-btn::after {
        content: '';
        position: absolute;
        left: .8rem; right: .8rem; bottom: -1px;
        height: 3px;
        background: linear-gradient(90deg, #6366f1, #8b5cf6);
        border-radius: 999px 999px 0 0;
        transform: scaleX(0);
        transition: transform .3s cubic-bezier(.22,1,.36,1);
    }
    .tabs-bar .tab-btn.active {
        color: #4f46e5;
    }
    .tabs-bar .tab-btn.active::after { transform: scaleX(1); }
    .tab-pane-c { display: none; animation: secIn .35s cubic-bezier(.22,1,.36,1) both; }
    .tab-pane-c.active { display: block; }

    /* ============ Termin status pills ============ */
    .termin-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .68rem;
        font-weight: 700;
        padding: .3rem .7rem;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .termin-pill.tp-locked   { background: rgba(100,116,139,.10); color: #475569; }
    .termin-pill.tp-ready    { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #fff; box-shadow: 0 4px 10px rgba(245,158,11,.30); }
    .termin-pill.tp-draft    { background: rgba(100,116,139,.10); color: #475569; }
    .termin-pill.tp-billed   { background: linear-gradient(135deg, #34d399, #10b981); color: #fff; box-shadow: 0 4px 10px rgba(16,185,129,.30); }

    /* ============ Timeline (right column) ============ */
    .timeline-card {
        position: sticky;
        top: 20px;
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1.15rem;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(15,23,42,.04);
        animation: secIn .55s cubic-bezier(.22,1,.36,1) .35s both;
    }
    .timeline-card .tl-head {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f1f3f7;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff;
    }
    .timeline-card .tl-head h6 {
        font-weight: 800;
        margin: 0;
        color: #fff;
        font-size: .95rem;
        display: inline-flex;
        align-items: center;
        gap: .55rem;
    }
    .timeline-card .tl-body {
        padding: 1.25rem 1.5rem 1.5rem;
        max-height: 80vh;
        overflow-y: auto;
    }
    .activity-list {
        position: relative;
        padding-left: 28px;
    }
    .activity-list::before {
        content: '';
        position: absolute;
        left: 11px; top: 6px; bottom: 6px;
        width: 2px;
        background: linear-gradient(180deg, #818cf8, #c4b5fd, #f1f5f9);
        border-radius: 999px;
    }
    .timeline-mod {
        position: relative;
        margin-bottom: 1.5rem;
        animation: tlIn .55s cubic-bezier(.22,1,.36,1) both;
    }
    .timeline-mod:nth-child(1) { animation-delay: .15s; }
    .timeline-mod:nth-child(2) { animation-delay: .22s; }
    .timeline-mod:nth-child(3) { animation-delay: .29s; }
    .timeline-mod:nth-child(4) { animation-delay: .36s; }
    .timeline-mod:nth-child(n+5) { animation-delay: .43s; }
    @keyframes tlIn {
        from { opacity: 0; transform: translateX(-10px); }
        to   { opacity: 1; transform: translateX(0); }
    }
    .timeline-mod .tl-dot {
        position: absolute;
        left: -28px; top: 0;
        width: 24px; height: 24px;
        border-radius: 50%;
        background: linear-gradient(135deg, #818cf8, #6366f1);
        border: 4px solid #fff;
        box-shadow: 0 0 0 1px #c7d2fe, 0 4px 10px rgba(99,102,241,.30);
        display: inline-flex; align-items: center; justify-content: center;
        z-index: 1;
        transition: transform .25s ease;
    }
    .timeline-mod:hover .tl-dot { transform: scale(1.15); }
    .timeline-mod .tl-dot i { color: #fff; font-size: .7rem; display: none; }
    .timeline-mod .tl-time {
        font-size: .7rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: .15rem;
    }
    .timeline-mod .tl-time .tl-rel { color: #4f46e5; }
    .timeline-mod .tl-title {
        font-weight: 700;
        color: #1e293b;
        font-size: .9rem;
        margin: 0 0 .25rem;
    }
    .timeline-mod .tl-actor {
        font-size: .72rem;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .timeline-mod .tl-note {
        margin-top: .55rem;
        padding: .55rem .75rem;
        background: rgba(99,102,241,.06);
        border-left: 3px solid #818cf8;
        border-radius: .5rem;
        font-size: .78rem;
        color: #475569;
        font-style: italic;
    }
    .timeline-end {
        position: relative;
        padding-left: 28px;
        font-size: .75rem;
        color: #94a3b8;
    }
    .timeline-end::before {
        content: '';
        position: absolute;
        left: 5px; top: 4px;
        width: 14px; height: 14px;
        border-radius: 50%;
        background: linear-gradient(135deg, #cbd5e1, #94a3b8);
        border: 3px solid #fff;
        box-shadow: 0 0 0 1px #cbd5e1;
    }

    /* ============ Modal premium ============ */
    .modal-content { border: 0; border-radius: 1.15rem; overflow: hidden; }
    .modal-header.modal-grad-success { background: linear-gradient(135deg, #10b981, #059669); }
    .modal-header.modal-grad-primary { background: linear-gradient(135deg, #6366f1, #4f46e5); }
    .modal-header.modal-grad-info    { background: linear-gradient(135deg, #38bdf8, #0ea5e9); }
    .modal-header.modal-grad-secondary { background: linear-gradient(135deg, #475569, #334155); }
    .modal-header.modal-grad-purple { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%); }
    .modal-header.modal-grad-success,
    .modal-header.modal-grad-primary,
    .modal-header.modal-grad-info,
    .modal-header.modal-grad-secondary,
    .modal-header.modal-grad-purple {
        color: #fff;
        border: 0;
        padding: 1.15rem 1.5rem;
    }
    .modal-header .btn-close { filter: invert(1); }

    /* ============ Upload Modal premium ============ */
    .upload-modal .modal-content {
        border-radius: 1.4rem;
        overflow: hidden;
        box-shadow: 0 30px 60px rgba(15,23,42,.25), 0 8px 18px rgba(15,23,42,.10);
    }
    .upload-modal .modal-hero {
        position: relative;
        padding: 1.5rem 1.75rem 1.4rem;
        color: #fff;
        overflow: hidden;
    }
    .upload-modal .modal-hero::before,
    .upload-modal .modal-hero::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
    }
    .upload-modal .modal-hero::before {
        right: -90px; top: -90px;
        width: 220px; height: 220px;
        background: rgba(255,255,255,.10);
    }
    .upload-modal .modal-hero::after {
        left: -50px; bottom: -70px;
        width: 160px; height: 160px;
        background: rgba(255,255,255,.06);
    }
    .upload-modal .modal-hero > * { position: relative; z-index: 1; }
    .upload-modal .modal-hero .um-illust {
        position: absolute;
        right: 1.25rem; top: 50%;
        transform: translateY(-50%) rotate(-10deg);
        font-size: 5.5rem;
        opacity: .15;
        z-index: 0;
        line-height: 1;
    }
    .upload-modal .um-tag {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        background: rgba(255,255,255,.18);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.30);
        padding: .3rem .8rem;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        margin-bottom: .55rem;
        color: #fff;
    }
    .upload-modal .um-title {
        font-weight: 800;
        font-size: 1.2rem;
        letter-spacing: -.01em;
        margin: 0 0 .25rem;
        color: #fff;
        text-shadow: 0 1px 2px rgba(0,0,0,.15);
    }
    .upload-modal .um-sub {
        font-size: .82rem;
        color: rgba(255,255,255,.92);
        margin: 0;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .upload-modal .um-sub strong { font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace; }
    .upload-modal .btn-close-um {
        position: absolute;
        right: 1rem; top: 1rem;
        width: 32px; height: 32px;
        border-radius: 10px;
        background: rgba(255,255,255,.18);
        border: 1px solid rgba(255,255,255,.30);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all .2s ease;
        font-size: .95rem;
        z-index: 2;
    }
    .upload-modal .btn-close-um:hover {
        background: rgba(255,255,255,.30);
        transform: rotate(90deg);
    }
    .upload-modal .modal-body {
        padding: 1.5rem 1.75rem;
        background: #fafbff;
    }
    .upload-modal .modal-footer {
        background: #fff;
        border-top: 1px solid #eef0f4;
        padding: 1rem 1.5rem;
        gap: .65rem;
    }

    /* Modal info banner */
    .um-banner {
        display: flex;
        gap: .65rem;
        align-items: flex-start;
        background: linear-gradient(135deg, rgba(99,102,241,.06), rgba(99,102,241,.02));
        border: 1px solid rgba(99,102,241,.20);
        border-left: 4px solid #6366f1;
        border-radius: .75rem;
        padding: .75rem 1rem;
        margin-bottom: 1.15rem;
        font-size: .82rem;
        color: #475569;
        line-height: 1.5;
    }
    .um-banner i { color: #4f46e5; font-size: 1.1rem; flex-shrink: 0; margin-top: 1px; }
    .um-banner strong { color: #4338ca; }

    /* Modal field label */
    .um-label {
        font-size: .76rem;
        font-weight: 700;
        color: #475569;
        letter-spacing: .02em;
        margin-bottom: .55rem;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }

    /* Modal file dropzone */
    .um-drop {
        position: relative;
        display: block;
        border: 2px dashed #cbd5e1;
        border-radius: 1rem;
        background:
            radial-gradient(120% 100% at 0% 0%, rgba(99,102,241,.05), transparent 55%),
            radial-gradient(120% 100% at 100% 100%, rgba(236,72,153,.04), transparent 55%),
            #ffffff;
        padding: 1.5rem 1.15rem;
        text-align: center;
        cursor: pointer;
        transition: all .25s ease;
        overflow: hidden;
    }
    .um-drop input[type="file"] {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }
    .um-drop:hover {
        border-color: #818cf8;
        background:
            radial-gradient(120% 100% at 0% 0%, rgba(99,102,241,.10), transparent 55%),
            radial-gradient(120% 100% at 100% 100%, rgba(236,72,153,.07), transparent 55%),
            #fff;
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(99,102,241,.10);
    }
    .um-drop.is-drag {
        border-color: #6366f1;
        border-style: solid;
        background: linear-gradient(135deg, rgba(99,102,241,.08), rgba(139,92,246,.06)), #fff;
        box-shadow: 0 0 0 4px rgba(99,102,241,.12), 0 14px 30px rgba(99,102,241,.15);
        transform: scale(1.01);
    }
    .um-drop.is-filled {
        border-style: solid;
        border-color: #34d399;
        background: linear-gradient(135deg, rgba(16,185,129,.06), rgba(52,211,153,.02)), #fff;
    }
    .um-drop .ud-icon {
        width: 60px; height: 60px;
        border-radius: 16px;
        background: linear-gradient(135deg, #818cf8, #6366f1);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.65rem;
        margin-bottom: .65rem;
        box-shadow: 0 8px 20px rgba(99,102,241,.30);
        transition: all .3s ease;
    }
    .um-drop:hover .ud-icon { transform: translateY(-3px) rotate(-6deg); }
    .um-drop.is-filled .ud-icon {
        background: linear-gradient(135deg, #34d399, #10b981);
        box-shadow: 0 8px 20px rgba(16,185,129,.30);
    }
    .um-drop .ud-title {
        font-weight: 700;
        color: #0f172a;
        font-size: .95rem;
        margin-bottom: .15rem;
    }
    .um-drop .ud-title strong {
        background: linear-gradient(135deg, #6366f1, #ec4899);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .um-drop .ud-sub {
        color: #64748b;
        font-size: .78rem;
        margin-bottom: .55rem;
    }
    .um-drop .ud-meta {
        display: inline-flex;
        gap: .35rem;
        align-items: center;
        background: rgba(99,102,241,.08);
        color: #4338ca;
        font-weight: 600;
        font-size: .68rem;
        padding: .25rem .55rem;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .um-drop.is-filled .ud-meta { background: rgba(16,185,129,.10); color: #047857; }

    .um-drop .ud-preview {
        position: relative;
        display: flex;
        align-items: center;
        gap: .85rem;
        text-align: left;
        background: #fff;
        border-radius: .75rem;
        padding: .75rem .9rem;
        border: 1px solid rgba(16,185,129,.20);
        box-shadow: 0 6px 16px rgba(16,185,129,.08);
        z-index: 3;
    }
    .um-drop .ud-fp-icon {
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
    .um-drop .ud-fp-info { flex: 1 1 auto; min-width: 0; }
    .um-drop .ud-fp-name {
        font-weight: 700;
        color: #0f172a;
        font-size: .88rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .um-drop .ud-fp-detail {
        font-size: .72rem;
        color: #64748b;
        margin-top: .15rem;
        display: flex;
        gap: .55rem;
        align-items: center;
        flex-wrap: wrap;
    }
    .um-drop .ud-fp-size {
        font-weight: 600;
        color: #047857;
        background: rgba(16,185,129,.10);
        padding: .1rem .45rem;
        border-radius: 999px;
    }
    .um-drop .ud-fp-remove {
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
        cursor: pointer;
        flex-shrink: 0;
        transition: all .18s ease;
    }
    .um-drop .ud-fp-remove:hover {
        background: #fee2e2;
        border-color: #fca5a5;
        transform: rotate(90deg);
    }
    .um-drop.is-filled .ud-default { display: none; }
    .um-drop:not(.is-filled) .ud-preview { display: none; }
    .um-drop .ud-thumb {
        width: 44px; height: 44px;
        border-radius: 12px;
        object-fit: cover;
        flex-shrink: 0;
        border: 2px solid #fff;
        box-shadow: 0 6px 14px rgba(14,165,233,.25);
    }
    .um-drop .ud-bar {
        height: 4px;
        width: 100%;
        background: #f1f5f9;
        border-radius: 999px;
        overflow: hidden;
        margin-top: .35rem;
    }
    .um-drop .ud-bar > span {
        display: block;
        height: 100%;
        background: linear-gradient(90deg, #34d399, #10b981);
        border-radius: 999px;
        transition: width .3s ease;
    }

    /* Current active file card */
    .um-current {
        margin-top: 1rem;
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: .85rem;
        padding: .85rem 1rem;
        display: flex;
        align-items: center;
        gap: .75rem;
        transition: all .2s ease;
    }
    .um-current:hover {
        border-color: #c7d2fe;
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(15,23,42,.06);
    }
    .um-current .uc-icon {
        width: 38px; height: 38px;
        border-radius: 10px;
        background: linear-gradient(135deg, rgba(99,102,241,.12), rgba(139,92,246,.08));
        color: #4338ca;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .um-current .uc-info { flex: 1 1 auto; min-width: 0; }
    .um-current .uc-label {
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
    }
    .um-current .uc-name {
        font-weight: 700;
        color: #0f172a;
        font-size: .85rem;
        margin-top: .1rem;
    }
    .um-current .uc-link {
        background: #fff;
        border: 1px solid #c7d2fe;
        color: #4338ca;
        font-weight: 600;
        font-size: .75rem;
        padding: .4rem .75rem;
        border-radius: .55rem;
        text-decoration: none;
        transition: all .18s ease;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        white-space: nowrap;
    }
    .um-current .uc-link:hover {
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 6px 14px rgba(99,102,241,.30);
    }

    /* Modal buttons */
    .btn-um-cancel {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-weight: 600;
        padding: .55rem 1.1rem;
        border-radius: .6rem;
        font-size: .85rem;
        transition: all .2s ease;
    }
    .btn-um-cancel:hover {
        background: #e2e8f0;
        color: #1e293b;
        transform: translateY(-1px);
    }
    .btn-um-submit {
        background: linear-gradient(135deg, #6366f1, #8b5cf6, #ec4899);
        background-size: 200% 100%;
        background-position: 0% 0%;
        border: 0;
        color: #fff;
        font-weight: 700;
        padding: .6rem 1.3rem;
        border-radius: .6rem;
        font-size: .85rem;
        box-shadow: 0 8px 22px rgba(99,102,241,.35);
        transition: all .35s ease;
        display: inline-flex;
        align-items: center;
        gap: .4rem;
    }
    .btn-um-submit:hover {
        background-position: 100% 0%;
        transform: translateY(-2px);
        box-shadow: 0 14px 28px rgba(99,102,241,.45);
        color: #fff;
    }

    /* ============ Termin Table modern ============ */
    .termin-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .termin-table thead th {
        background: #f8fafc;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #64748b;
        padding: .85rem 1rem;
        border-top: 1px solid #eef0f4;
        border-bottom: 1px solid #eef0f4;
        white-space: nowrap;
    }
    .termin-table tbody td {
        padding: 1rem;
        font-size: .87rem;
        border-bottom: 1px solid #f1f3f7;
        background: #fff;
        vertical-align: middle;
        transition: background .18s ease;
    }
    .termin-table tbody tr:hover td { background: #fafbff; }
    .termin-table tbody tr:last-child td { border-bottom: 0; }

    .termin-num {
        width: 36px; height: 36px;
        border-radius: 10px;
        background: linear-gradient(135deg, #818cf8, #6366f1);
        color: #fff;
        font-weight: 800;
        font-size: 1rem;
        display: inline-flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 10px rgba(99,102,241,.30);
    }
    .termin-keterangan {
        font-weight: 700;
        color: #1e293b;
        font-size: .9rem;
        margin: 0 0 .15rem;
    }
    .termin-jenis-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        background: rgba(99,102,241,.10);
        color: #4338ca;
        padding: .15rem .55rem;
        border-radius: .35rem;
    }
    .termin-percent-chip {
        display: inline-block;
        background: linear-gradient(135deg, #475569, #334155);
        color: #fff;
        font-weight: 800;
        font-size: .85rem;
        padding: .35rem .9rem;
        border-radius: 999px;
        font-variant-numeric: tabular-nums;
    }
    .termin-money {
        font-weight: 800;
        font-size: 1rem;
        color: #047857;
        font-variant-numeric: tabular-nums;
    }

    /* ============ Hint banner (Tab content) ============ */
    .hint-banner {
        background: linear-gradient(135deg, rgba(245,158,11,.06), rgba(245,158,11,.02));
        border: 1px solid rgba(245,158,11,.20);
        border-left: 4px solid #f59e0b;
        border-radius: .85rem;
        padding: .85rem 1.15rem;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: flex-start;
        gap: .65rem;
        font-size: .82rem;
        color: #475569;
    }
    .hint-banner i.bi-lightbulb-fill {
        color: #f59e0b;
        font-size: 1.15rem;
        flex-shrink: 0;
    }
    .hint-banner .badge-mini {
        display: inline-flex;
        align-items: center;
        font-size: .65rem;
        font-weight: 700;
        padding: .15rem .5rem;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin: 0 .15rem;
    }
    .hint-banner .badge-mini.bm-ready { background: rgba(245,158,11,.18); color: #b45309; }
    .hint-banner .badge-mini.bm-locked { background: rgba(100,116,139,.15); color: #475569; }
    .hint-banner .badge-mini.bm-billed { background: rgba(16,185,129,.15); color: #047857; }

    .empty-cell-state {
        text-align: center;
        padding: 2.5rem 1rem;
        color: #94a3b8;
    }
    .empty-cell-state i {
        font-size: 2.5rem;
        margin-bottom: .55rem;
        display: block;
        background: linear-gradient(135deg, #c7d2fe, #818cf8);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* ============ Animations ============ */
    @keyframes heroIn {
        from { opacity: 0; transform: translateY(-12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes secIn {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>