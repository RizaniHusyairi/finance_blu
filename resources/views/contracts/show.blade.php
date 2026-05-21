@extends('layouts.app')
@section('title', 'Detail Kontrak: ' . Str::limit($kontrak->nama_pekerjaan, 30))

@push('css')
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
@endpush

@section('content')
@php
    $readyTerms = $kontrak->termin->where('status_termin', 'READY_TO_BILL')->values();
    $ringkasanFinalArsip = $kontrak->ringkasan_kontrak_final_ttd_arsip;
    $spkFinalArsip = $kontrak->spk_final_ttd_arsip;
    $spmkFinalArsip = $kontrak->spmk_final_ttd_arsip;
    $gambarRabArsip = $kontrak->gambar_rab_arsip;
    $selectedCoa = optional($kontrak->dipaRevisionItem)->coa;
@endphp

@if(session('success'))
    <div class="status-banner status-banner-success mb-3" role="alert">
        <div class="sb-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div><strong>Berhasil.</strong> {{ session('success') }}</div>
    </div>
@endif

@if(session('error'))
    <div class="status-banner status-banner-warning mb-3" role="alert" style="background: rgba(244,63,94,.06); border-color: rgba(244,63,94,.25); color: #991b1b;">
        <div class="sb-icon" style="background: rgba(244,63,94,.18); color: #b91c1c;"><i class="bi bi-x-circle-fill"></i></div>
        <div><strong>Terjadi kesalahan.</strong> {{ session('error') }}</div>
    </div>
@endif

@if($errors->any())
    <div class="status-banner status-banner-warning mb-3" role="alert" style="background: rgba(244,63,94,.06); border-color: rgba(244,63,94,.25); color: #991b1b;">
        <div class="sb-icon" style="background: rgba(244,63,94,.18); color: #b91c1c;"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div>{{ $errors->first() }}</div>
    </div>
@endif

@php
    $statusKontrak = $kontrak->status_kontrak;
    $heroCls = match($statusKontrak) {
        'AKTIF'      => 'hero-aktif',
        'SELESAI'    => 'hero-selesai',
        'DRAFT'      => 'hero-draft',
        'REVISI'     => 'hero-revisi',
        'PENDING_REVIEW' => 'hero-pending',
        default      => 'hero-draft',
    };
    $heroIcon = match($statusKontrak) {
        'AKTIF'      => 'bi-play-circle-fill',
        'SELESAI'    => 'bi-check-circle-fill',
        'DRAFT'      => 'bi-pencil-square',
        'REVISI'     => 'bi-arrow-counterclockwise',
        default      => 'bi-info-circle-fill',
    };
@endphp

{{-- ═══ HERO HEADER ═══ --}}
<div class="kontrak-hero {{ $heroCls }}">
    <i class="bi bi-briefcase-fill briefcase-illust d-none d-md-block"></i>
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div class="flex-grow-1 min-w-0">
            <div class="d-flex gap-2 align-items-center mb-2 flex-wrap">
                <span class="hero-status-pill"><i class="bi {{ $heroIcon }}"></i> {{ str_replace('_',' ',$statusKontrak) }}</span>
                <span class="hero-status-pill" style="opacity:.85;">
                    <i class="bi bi-folder2-open"></i> Kontrak Pengadaan
                </span>
            </div>
            <h2 class="hero-title">{{ $kontrak->nama_pekerjaan }}</h2>
            <p class="hero-meta">
                <i class="bi bi-hash"></i> Nomor SPK <strong>{{ $kontrak->nomor_spk }}</strong>
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-start">
            <a href="{{ route('contracts.index') }}" class="btn-hero">
                <i class="bi bi-arrow-left"></i> Kembali ke Daftar
            </a>
            @if($kontrak->status_kontrak === 'AKTIF')
                <button type="button" class="btn-hero btn-hero-primary" data-bs-toggle="modal" data-bs-target="#modalTagihKontrakDetail" {{ $readyTerms->isEmpty() ? 'disabled' : '' }}>
                    <i class="bi bi-cash-stack"></i> Buat Tagihan
                </button>
            @endif
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- KOLOM KIRI: MAIN KONTEN -->
    <div class="col-lg-8 col-xl-9">
        
        {{-- BAGIAN: KELENGKAPAN AKTIVASI --}}
        <div class="modern-card" style="animation: secIn .55s cubic-bezier(.22,1,.36,1) .12s both;">
            <div class="mc-head">
                <h6><i class="bi bi-shield-check mc-h-icon icon-success"></i> Status Aktivasi & Kelengkapan Dokumen Final</h6>
            </div>
            <div class="mc-body">
                <div class="activation-grid">
                    <div class="check-item {{ $ringkasanFinalArsip ? 'is-done' : 'is-pending' }}">
                        <div class="ci-icon">
                            <i class="bi {{ $ringkasanFinalArsip ? 'bi-check-lg' : 'bi-x-lg' }}"></i>
                        </div>
                        <div>
                            <div class="ci-label">Ringkasan Kontrak Final</div>
                            <div class="ci-status">{{ $ringkasanFinalArsip ? 'Sudah diunggah' : 'Belum diunggah' }}</div>
                        </div>
                    </div>
                    <div class="check-item {{ $spkFinalArsip ? 'is-done' : 'is-pending' }}">
                        <div class="ci-icon">
                            <i class="bi {{ $spkFinalArsip ? 'bi-check-lg' : 'bi-x-lg' }}"></i>
                        </div>
                        <div>
                            <div class="ci-label">SPK Final</div>
                            <div class="ci-status">{{ $spkFinalArsip ? 'Sudah diunggah' : 'Belum diunggah' }}</div>
                        </div>
                    </div>
                    <div class="check-item {{ $spmkFinalArsip ? 'is-done' : 'is-pending' }}">
                        <div class="ci-icon">
                            <i class="bi {{ $spmkFinalArsip ? 'bi-check-lg' : 'bi-x-lg' }}"></i>
                        </div>
                        <div>
                            <div class="ci-label">SPMK Final</div>
                            <div class="ci-status">{{ $spmkFinalArsip ? 'Sudah diunggah' : 'Belum diunggah' }}</div>
                        </div>
                    </div>
                </div>
                @if($kontrak->status_kontrak === 'AKTIF')
                    <div class="status-banner status-banner-success">
                        <div class="sb-icon"><i class="bi bi-check-circle-fill"></i></div>
                        <div><strong>Siap Aktif.</strong> Kontrak telah otomatis AKTIF karena seluruh dokumen final bertandatangan lengkap.</div>
                    </div>
                @else
                    <div class="status-banner status-banner-warning">
                        <div class="sb-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                        <div><strong>Belum Siap Aktif.</strong> Kontrak berstatus <strong>{{ $kontrak->status_kontrak }}</strong>. Unggah kelengkapan dokumen final di bawah ini agar kontrak menjadi otomatis aktif.</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- BAGIAN: PROGRESS SERAPAN --}}
        <div class="progress-card">
            <div class="d-flex justify-content-between align-items-end gap-2 flex-wrap">
                <div>
                    <div class="progress-label"><i class="bi bi-cash-coin"></i> Serapan Dana (Realisasi)</div>
                    <div>
                        <span class="progress-amount">Rp {{ number_format($kontrak->total_terserap, 0, ',', '.') }}</span>
                        <span class="progress-amount-total">/ Rp {{ number_format($kontrak->nilai_total_kontrak, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="progress-percent-pill">
                    <i class="bi bi-graph-up-arrow"></i> {{ number_format($kontrak->persentase_serapan, 1) }}% Tercapai
                </div>
            </div>
            <div class="progress-track">
                <div class="progress-fill" style="width: {{ $kontrak->persentase_serapan }}%"></div>
            </div>
        </div>

        {{-- BAGIAN: SUMMARY GRID --}}
        <div class="summary-grid">
            <div class="summary-card sc-primary">
                <div class="sc-head">
                    <span class="sc-icon"><i class="bi bi-file-earmark-text-fill"></i></span>
                    <div class="sc-label">Identitas Perikatan</div>
                </div>
                <div class="sc-mono mb-1">{{ $kontrak->nomor_spk }}</div>
                <div class="sc-row">Tgl: <strong>{{ \Carbon\Carbon::parse($kontrak->tanggal_spk)->isoFormat('D MMM YYYY') }}</strong></div>
                <div class="sc-row">DIPA: <span class="sc-rek-chip">{{ $kontrak->dipa->nomor_dipa ?? 'N/A' }}</span></div>
            </div>
            <div class="summary-card sc-info">
                <div class="sc-head">
                    <span class="sc-icon"><i class="bi bi-building-fill"></i></span>
                    <div class="sc-label">Vendor & Rekening</div>
                </div>
                <div class="fw-bold text-dark text-truncate" title="{{ $kontrak->vendor->nama_pihak ?? $kontrak->vendor->nama_perusahaan ?? '-' }}">{{ $kontrak->vendor->nama_pihak ?? $kontrak->vendor->nama_perusahaan ?? '-' }}</div>
                <div class="sc-row">NPWP: <strong>{{ $kontrak->vendor->npwp ?? '-' }}</strong></div>
                @php $rek = $kontrak->vendor->rekening->first(); @endphp
                <div class="sc-rek-chip text-truncate" style="max-width: 100%;">{{ $rek ? $rek->nama_bank . ' · ' . $rek->nomor_rekening : 'Belum Ada Rekening' }}</div>
            </div>
            <div class="summary-card sc-success">
                <div class="sc-head">
                    <span class="sc-icon"><i class="bi bi-cash-stack"></i></span>
                    <div class="sc-label">Nilai & Skema</div>
                </div>
                <div class="sc-money mb-1">Rp {{ number_format($kontrak->nilai_total_kontrak, 0, ',', '.') }}</div>
                <div class="sc-row">Metode: <strong>{{ $kontrak->metode_pembayaran }}</strong></div>
                <div class="sc-row">Uang Muka:
                    @if($kontrak->ada_uang_muka)
                        <strong>Rp {{ number_format($kontrak->nilai_uang_muka, 0, ',', '.') }}</strong>
                    @else
                        <span class="text-muted">Tidak ada</span>
                    @endif
                </div>
            </div>
            <div class="summary-card sc-warning">
                <div class="sc-head">
                    <span class="sc-icon"><i class="bi bi-calendar-range-fill"></i></span>
                    <div class="sc-label">Garis Waktu</div>
                </div>
                <div class="fw-bold text-dark mb-1">{{ $kontrak->jangka_waktu }} {{ ucfirst(strtolower($kontrak->satuan_waktu)) }}</div>
                <div class="sc-row">Mulai: <strong>{{ \Carbon\Carbon::parse($kontrak->tanggal_mulai)->isoFormat('D MMM YYYY') }}</strong></div>
                <div class="sc-row" style="color:#b91c1c;">Selesai: <strong>{{ \Carbon\Carbon::parse($kontrak->tanggal_selesai)->isoFormat('D MMM YYYY') }}</strong></div>
            </div>
        </div>

        <div class="modern-card" style="animation: secIn .55s cubic-bezier(.22,1,.36,1) .50s both;">
            <div class="mc-head">
                <h6><i class="bi bi-file-earmark-medical mc-h-icon"></i> Metadata Dokumen Kontrak</h6>
                <span class="termin-pill tp-locked">Data Sumber Dokumen</span>
            </div>
            <div class="mc-body">
                
                <div class="row g-3 mb-4 bg-light p-3 rounded-4 border">
                    <div class="col-md-6">
                        <div class="small text-muted text-uppercase fw-bold"><i class="bi bi-file-text me-1"></i> Identitas SPK</div>
                        <div class="fw-bold text-primary mt-1">{{ $kontrak->nomor_spk ?: '-' }}</div>
                        <div class="small text-muted">Tgl: {{ $kontrak->tanggal_spk ? \Carbon\Carbon::parse($kontrak->tanggal_spk)->translatedFormat('d M Y') : '-' }}</div>
                    </div>
                    <div class="col-md-6 border-start-md">
                        <div class="small text-muted text-uppercase fw-bold"><i class="bi bi-file-text me-1"></i> Identitas SPMK</div>
                        <div class="fw-bold text-info mt-1">{{ $kontrak->nomor_spmk ?: '-' }}</div>
                        <div class="small text-muted">Tgl: {{ $kontrak->tanggal_spmk ? \Carbon\Carbon::parse($kontrak->tanggal_spmk)->translatedFormat('d M Y') : '-' }}</div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="small text-muted">Nama PPK</div>
                        <div class="fw-bold">{{ $kontrak->nama_ppk ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">NIP PPK</div>
                        <div class="fw-bold">{{ $kontrak->nip_ppk ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Nomor Surat Undangan Pengadaan Langsung</div>
                        <div class="fw-bold">{{ $kontrak->nomor_surat_undangan_pengadaan ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Nomor BA Hasil Pengadaan Langsung</div>
                        <div class="fw-bold">{{ $kontrak->nomor_ba_hasil_pengadaan ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Nama Penandatangan Vendor</div>
                        <div class="fw-bold">{{ $kontrak->vendor->nama_penanggung_jawab ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Jabatan Penandatangan Vendor</div>
                        <div class="fw-bold">{{ $kontrak->vendor->jabatan_penandatangan ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Vendor / Mitra</div>
                        <div class="fw-bold">{{ $kontrak->vendor->nama_pihak ?? ($kontrak->vendor->nama_perusahaan ?? '-') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Masa Pemeliharaan</div>
                        <div class="fw-bold">
                            {{ (int) ($kontrak->masa_pemeliharaan_hari ?? 0) > 0 ? number_format((int) $kontrak->masa_pemeliharaan_hari, 0, ',', '.') . ' hari kalender' : '-' }}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Periode Pemeliharaan</div>
                        <div class="fw-bold">
                            @if($kontrak->tanggal_mulai_pemeliharaan && $kontrak->tanggal_selesai_pemeliharaan)
                                {{ \Carbon\Carbon::parse($kontrak->tanggal_mulai_pemeliharaan)->translatedFormat('d M Y') }}
                                s.d.
                                {{ \Carbon\Carbon::parse($kontrak->tanggal_selesai_pemeliharaan)->translatedFormat('d M Y') }}
                            @else
                                -
                            @endif
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="small text-muted">Ketentuan Denda</div>
                        <div class="fw-bold">{{ $kontrak->ketentuan_denda ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modern-card" style="animation: secIn .55s cubic-bezier(.22,1,.36,1) .56s both;">
            <div class="mc-head">
                <div>
                    <h6><i class="bi bi-journal-richtext mc-h-icon icon-secondary"></i> Dokumen Ringkasan Kontrak</h6>
                    <small class="text-muted d-block mt-1">Ringkasan Kontrak final bertandatangan wajib diunggah sebagai syarat aktivasi otomatis kontrak.</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if(!in_array($kontrak->status_kontrak, ['AKTIF', 'SELESAI']))
                    <a href="{{ route('contracts.ringkasan.export-pdf', $kontrak->id) }}" target="_blank" class="btn-act-modern btn-act-pdf">
                        <i class="bi bi-filetype-pdf"></i> Export PDF Draft
                    </a>
                    <button type="button" class="btn-act-modern btn-act-primary" data-bs-toggle="modal" data-bs-target="#modalUploadRingkasanKontrakFinal">
                        <i class="bi bi-upload"></i> Upload Final TTD
                    </button>
                    @endif
                    @if($ringkasanFinalArsip)
                        <a href="{{ Storage::url($ringkasanFinalArsip->path_file) }}" target="_blank" class="btn-act-modern btn-act-success">
                            <i class="bi bi-eye-fill"></i> Lihat Final
                        </a>
                    @endif
                </div>
            </div>
            <div class="mc-body">

                <div class="row g-3 align-items-stretch">
                    <div class="col-md-4">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-check2-circle me-1"></i> Status Dokumen Final</div>
                            @if($ringkasanFinalArsip)
                                <span class="badge-doc badge-doc-success"><i class="bi bi-check-circle-fill"></i> Sudah Diunggah</span>
                                <div class="dsc-meta mt-2">Waktu: {{ optional($ringkasanFinalArsip->uploaded_at)->translatedFormat('d M Y H:i') ?? optional($ringkasanFinalArsip->created_at)->translatedFormat('d M Y H:i') }}</div>
                            @else
                                <span class="badge-doc badge-doc-danger"><i class="bi bi-x-circle-fill"></i> Belum Diunggah</span>
                                <div class="dsc-meta mt-2">Upload Ringkasan Kontrak final bertandatangan setelah ditandatangani.</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-hash me-1"></i> Identitas Ringkasan</div>
                            <div class="dsc-value">{{ $kontrak->nomor_spk ?? '-' }}</div>
                            <div class="dsc-meta">{{ $kontrak->tanggal_spk ? \Carbon\Carbon::parse($kontrak->tanggal_spk)->translatedFormat('d M Y') : '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-shield-check me-1"></i> Masa Pemeliharaan</div>
                            <div class="dsc-value">{{ (int) ($kontrak->masa_pemeliharaan_hari ?? 0) > 0 ? number_format((int) $kontrak->masa_pemeliharaan_hari, 0, ',', '.') . ' hari kalender' : '-' }}</div>
                            <div class="dsc-meta">{{ $selectedCoa->kode_mak_lengkap ?? 'COA belum terhubung' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modern-card" style="animation: secIn .55s cubic-bezier(.22,1,.36,1) .62s both;">
            <div class="mc-head">
                <div>
                    <h6><i class="bi bi-file-earmark-check mc-h-icon icon-success"></i> Dokumen SPK</h6>
                    <small class="text-muted d-block mt-1">SPK final bertandatangan wajib diunggah sebagai syarat aktivasi otomatis kontrak.</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if(!in_array($kontrak->status_kontrak, ['AKTIF', 'SELESAI']))
                    <button type="button" class="btn-act-modern btn-act-soft" data-bs-toggle="modal" data-bs-target="#modalUploadGambarRab">
                        <i class="bi bi-image-fill"></i> {{ $gambarRabArsip ? 'Ganti Gambar RAB' : 'Upload Gambar RAB' }}
                    </button>
                    @if($gambarRabArsip)
                        <a href="{{ route('contracts.spk.export-pdf', $kontrak->id) }}" target="_blank" class="btn-act-modern btn-act-pdf">
                            <i class="bi bi-filetype-pdf"></i> Export PDF Draft
                        </a>
                    @else
                        <button type="button" class="btn-act-modern btn-act-pdf" disabled style="opacity:.5; cursor:not-allowed;" title="Upload Gambar RAB terlebih dahulu">
                            <i class="bi bi-filetype-pdf"></i> Export PDF Draft
                        </button>
                    @endif
                    <button type="button" class="btn-act-modern btn-act-primary" data-bs-toggle="modal" data-bs-target="#modalUploadSpkFinal">
                        <i class="bi bi-upload"></i> Upload Final TTD
                    </button>
                    @endif
                    @if($gambarRabArsip)
                        <a href="{{ route('contracts.spk.gambar-rab', $kontrak->id) }}" target="_blank" class="btn-act-modern btn-act-success">
                            <i class="bi bi-image"></i> Lihat RAB
                        </a>
                    @endif
                    @if($spkFinalArsip)
                        <a href="{{ Storage::url($spkFinalArsip->path_file) }}" target="_blank" class="btn-act-modern btn-act-success">
                            <i class="bi bi-eye-fill"></i> Lihat Final
                        </a>
                    @endif
                </div>
            </div>
            <div class="mc-body">

                <div class="row g-3 align-items-stretch">
                    <div class="col-md-6 col-lg-3">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-check2-circle me-1"></i> Status SPK Final</div>
                            @if($spkFinalArsip)
                                <span class="badge-doc badge-doc-success"><i class="bi bi-check-circle-fill"></i> Sudah Diunggah</span>
                                <div class="dsc-meta mt-2">{{ optional($spkFinalArsip->uploaded_at)->translatedFormat('d M Y H:i') ?? optional($spkFinalArsip->created_at)->translatedFormat('d M Y H:i') }}</div>
                            @else
                                <span class="badge-doc badge-doc-danger"><i class="bi bi-x-circle-fill"></i> Belum Diunggah</span>
                                <div class="dsc-meta mt-2">Upload SPK final bertandatangan untuk mengaktifkan kontrak.</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-image-alt me-1"></i> Gambar RAB</div>
                            @if($gambarRabArsip)
                                <span class="badge-doc badge-doc-success"><i class="bi bi-check-circle-fill"></i> Tersedia</span>
                                <div class="dsc-meta mt-2">{{ optional($gambarRabArsip->uploaded_at)->translatedFormat('d M Y H:i') ?? optional($gambarRabArsip->created_at)->translatedFormat('d M Y H:i') }}</div>
                            @else
                                <span class="badge-doc badge-doc-danger"><i class="bi bi-x-circle-fill"></i> Wajib Upload</span>
                                <div class="dsc-meta mt-2">Upload gambar RAB sebelum export PDF Draft SPK.</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-bank me-1"></i> DIPA / Revisi Aktif</div>
                            <div class="dsc-value">{{ $kontrak->dipa->nomor_dipa ?? '-' }}</div>
                            <div class="dsc-meta">TA {{ $kontrak->dipa->tahun_anggaran ?? '-' }} · Revisi {{ optional($kontrak->dipa->activeRevision)->nomor_revisi ?? $kontrak->dipa->revisi_aktif_ke ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-tag-fill me-1"></i> Item Anggaran / COA</div>
                            <div class="dsc-value">{{ $selectedCoa->kode_mak_lengkap ?? '-' }}</div>
                            <div class="dsc-meta">{{ $selectedCoa->nama_akun ?? 'Item anggaran belum terhubung' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modern-card" style="animation: secIn .55s cubic-bezier(.22,1,.36,1) .68s both;">
            <div class="mc-head">
                <div>
                    <h6><i class="bi bi-file-earmark-text mc-h-icon icon-info"></i> Dokumen SPMK</h6>
                    <small class="text-muted d-block mt-1">SPMK final bertandatangan wajib diunggah sebagai syarat aktivasi otomatis kontrak.</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if(!in_array($kontrak->status_kontrak, ['AKTIF', 'SELESAI']))
                    <a href="{{ route('contracts.spmk.export-pdf', $kontrak->id) }}" target="_blank" class="btn-act-modern btn-act-pdf">
                        <i class="bi bi-filetype-pdf"></i> Export PDF Draft
                    </a>
                    <button type="button" class="btn-act-modern btn-act-info" data-bs-toggle="modal" data-bs-target="#modalUploadSpmkFinal">
                        <i class="bi bi-upload"></i> Upload Final TTD
                    </button>
                    @endif
                    @if($spmkFinalArsip)
                        <a href="{{ Storage::url($spmkFinalArsip->path_file) }}" target="_blank" class="btn-act-modern btn-act-success">
                            <i class="bi bi-eye-fill"></i> Lihat Final
                        </a>
                    @endif
                </div>
            </div>
            <div class="mc-body">

                <div class="row g-3 align-items-stretch">
                    <div class="col-md-4">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-check2-circle me-1"></i> Status SPMK Final</div>
                            @if($spmkFinalArsip)
                                <span class="badge-doc badge-doc-success"><i class="bi bi-check-circle-fill"></i> Sudah Diunggah</span>
                                <div class="dsc-meta mt-2">{{ optional($spmkFinalArsip->uploaded_at)->translatedFormat('d M Y H:i') ?? optional($spmkFinalArsip->created_at)->translatedFormat('d M Y H:i') }}</div>
                            @else
                                <span class="badge-doc badge-doc-danger"><i class="bi bi-x-circle-fill"></i> Belum Diunggah</span>
                                <div class="dsc-meta mt-2">Upload SPMK final bertandatangan setelah ditandatangani.</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-hash me-1"></i> Identitas SPMK</div>
                            <div class="dsc-value">{{ $kontrak->nomor_spmk ?? '-' }}</div>
                            <div class="dsc-meta">{{ $kontrak->tanggal_spmk ? \Carbon\Carbon::parse($kontrak->tanggal_spmk)->translatedFormat('d M Y') : '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-person-badge me-1"></i> Penandatangan Vendor</div>
                            <div class="dsc-value">{{ $kontrak->vendor->nama_penanggung_jawab ?? '-' }}</div>
                            <div class="dsc-meta">{{ $kontrak->vendor->jabatan_penandatangan ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ BAGIAN: TABS DETAIL ═══ --}}
        <div class="modern-card" style="animation: secIn .55s cubic-bezier(.22,1,.36,1) .74s both;">
            <div class="tabs-bar" id="detailTabs">
                <button class="tab-btn" data-tab="dokumen">
                    <i class="bi bi-folder2-open"></i> Dokumen Awal & Jaminan
                </button>
                <button class="tab-btn active" data-tab="termin">
                    <i class="bi bi-list-check"></i> Skema Termin & Tagihan
                </button>
                <button class="tab-btn" data-tab="addendum">
                    <i class="bi bi-journal-text"></i> Riwayat Addendum
                </button>
            </div>
            <div class="mc-body">

                {{-- TAB DOKUMEN AWAL --}}
                <div class="tab-pane-c" data-pane="dokumen">
                    <div class="hint-banner">
                        <i class="bi bi-info-circle-fill" style="color:#0ea5e9 !important;"></i>
                        <div>Tab ini berisi salinan dokumen jaminan atau dokumen awal pendukung lainnya (sebelum kontrak aktif). Untuk Ringkasan Kontrak, SPK dan SPMK dikelola di area kartu atas.</div>
                    </div>
                    <h6 class="fw-bold mb-3 text-dark">Arsip Dokumen Pendukung & Jaminan</h6>
                    <div class="list-group mb-2">
                        @if($kontrak->file_jaminan_uang_muka)
                        <a href="{{ Storage::url($kontrak->file_jaminan_uang_muka) }}" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center border rounded-3 mb-2 shadow-sm">
                            <div><i class="bi bi-file-earmark-pdf-fill text-danger fs-4 me-2 align-middle"></i> <span class="fw-bold">Jaminan Uang Muka</span></div>
                            <span class="btn-act-modern btn-act-primary"><i class="bi bi-download"></i> Unduh</span>
                        </a>
                        @else
                        <div class="empty-cell-state">
                            <i class="bi bi-folder-x"></i>
                            <h6 class="text-secondary fw-bold mb-1">Tidak ada dokumen jaminan</h6>
                            <small>Belum ada dokumen jaminan/awal yang diunggah.</small>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- TAB TERMIN & TAGIHAN --}}
                <div class="tab-pane-c active" data-pane="termin">
                    <div class="hint-banner">
                        <i class="bi bi-lightbulb-fill"></i>
                        <div><strong>Catatan Skema Termin:</strong> Hanya termin berstatus <span class="badge-mini bm-ready">READY_TO_BILL</span> yang dapat dibuat menjadi tagihan. Termin <span class="badge-mini bm-locked">LOCKED</span> belum bisa diproses, dan termin <span class="badge-mini bm-locked">DRAFT</span> atau <span class="badge-mini bm-billed">SUDAH_DITAGIH</span> telah terikat pada pengajuan tagihan di sistem.</div>
                    </div>
                    <div class="table-responsive">
                        <table class="termin-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:90px;">Termin</th>
                                    <th>Keterangan</th>
                                    <th class="text-center" style="width:130px;">Persentase</th>
                                    <th>Nilai Bruto</th>
                                    <th class="text-center" style="width:130px;">Status</th>
                                    <th class="text-center" style="width:200px;">Aksi Tagihan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kontrak->termin as $termin)
                                <tr>
                                    <td class="text-center"><span class="termin-num">{{ $termin->termin_ke }}</span></td>
                                    <td>
                                        <p class="termin-keterangan">{{ $termin->keterangan_termin }}</p>
                                        <span class="termin-jenis-pill"><i class="bi bi-tag-fill"></i> {{ str_replace('_', ' ', $termin->jenis_termin) }}</span>
                                    </td>
                                    <td class="text-center"><span class="termin-percent-chip">{{ $termin->persentase }}%</span></td>
                                    <td><span class="termin-money">Rp {{ number_format($termin->nilai_bruto_termin, 0, ',', '.') }}</span></td>
                                    <td class="text-center">
                                        @if($termin->status_termin == 'LOCKED')
                                            <span class="termin-pill tp-locked"><i class="bi bi-lock-fill"></i> Locked</span>
                                        @elseif($termin->status_termin == 'READY_TO_BILL')
                                            <span class="termin-pill tp-ready"><i class="bi bi-bell-fill"></i> Ready</span>
                                        @elseif($termin->status_termin == 'DRAFT')
                                            <span class="termin-pill tp-draft"><i class="bi bi-file-earmark-text"></i> Draft</span>
                                        @elseif($termin->status_termin == 'SUDAH_DITAGIH')
                                            <span class="termin-pill tp-billed"><i class="bi bi-check-circle-fill"></i> Ditagih</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($kontrak->status_kontrak === 'AKTIF' && $termin->status_termin === 'READY_TO_BILL')
                                            <button type="button" class="btn-act-modern btn-act-success" title="Buat Tagihan" data-bs-toggle="modal" data-bs-target="#modalTagihTermin{{ $termin->id }}">
                                                <i class="bi bi-cash-stack"></i> Buat Tagihan
                                            </button>
                                        @elseif($kontrak->status_kontrak === 'AKTIF' && $termin->status_termin === 'LOCKED')
                                            <button disabled class="btn-act-modern btn-act-soft" style="opacity:.55; cursor:not-allowed;" title="Termin masih terkunci">
                                                <i class="bi bi-lock-fill"></i> Terkunci
                                            </button>
                                        @elseif(in_array($termin->status_termin, ['DRAFT', 'SUDAH_DITAGIH']))
                                            @php $tagihanLinked = $termin->detailKontrak->tagihan ?? null; @endphp
                                            @if($tagihanLinked)
                                                <div class="d-inline-flex gap-1 flex-wrap justify-content-center">
                                                    <a href="{{ route('tagihan.kontrak.show', $tagihanLinked->id) }}" class="btn-act-modern btn-act-soft" title="Detail Tagihan">
                                                        <i class="bi bi-file-text"></i> Detail
                                                    </a>
                                                    <button type="button" class="btn-act-modern btn-act-info" data-bs-toggle="modal" data-bs-target="#modalAktivitasTagihan{{ $tagihanLinked->id }}" title="Riwayat Aktivitas">
                                                        <i class="bi bi-clock-history"></i>
                                                    </button>
                                                </div>

                                                {{-- Modal Riwayat Aktivitas --}}
                                                <div class="modal fade text-start" id="modalAktivitasTagihan{{ $tagihanLinked->id }}" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                                        <div class="modal-content">
                                                            <div class="modal-header modal-grad-primary">
                                                                <div>
                                                                    <h6 class="modal-title fw-bold m-0"><i class="bi bi-clock-history me-2"></i>Riwayat Aktivitas Termin {{ $termin->termin_ke }}</h6>
                                                                    <div class="small opacity-90">{{ $tagihanLinked->nomor_tagihan ?? '' }}</div>
                                                                </div>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body p-4" style="background: #fafbff;">
                                                                @forelse($tagihanLinked->logs as $log)
                                                                    <div class="d-flex mb-3 gap-3">
                                                                        <div style="width:10px; height:10px; border-radius:50%; background:linear-gradient(135deg,#818cf8,#6366f1); margin-top:8px; flex-shrink:0; box-shadow: 0 0 0 3px rgba(99,102,241,.15);"></div>
                                                                        <div>
                                                                            <div class="fw-bold text-dark">{{ \Illuminate\Support\Str::title(strtolower(str_replace('_',' ',$log->status_baru))) }}</div>
                                                                            <div class="small text-muted">{{ \Carbon\Carbon::parse($log->created_at)->translatedFormat('d M Y H:i') }} · {{ $log->user ? $log->user->name : 'Sistem' }}</div>
                                                                            @if($log->catatan)
                                                                                <div class="small fst-italic mt-1 p-2 rounded" style="background: rgba(99,102,241,.06); border-left: 3px solid #818cf8; color: #475569;">"{{ $log->catatan }}"</div>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                @empty
                                                                    <div class="empty-cell-state">
                                                                        <i class="bi bi-journal-x"></i>
                                                                        <small>Belum ada riwayat aktivitas.</small>
                                                                    </div>
                                                                @endforelse
                                                            </div>
                                                            <div class="modal-footer border-0 bg-light">
                                                                <button type="button" class="btn-act-modern btn-act-soft" data-bs-dismiss="modal">Tutup</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-danger small"><i class="bi bi-exclamation-triangle"></i> Data Tagihan Hilang</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-cell-state">
                                            <i class="bi bi-list-task"></i>
                                            <h6 class="text-secondary fw-bold mb-1">Belum ada skema termin</h6>
                                            <small>Skema termin akan muncul setelah kontrak dibuat.</small>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB ADDENDUM --}}
                <div class="tab-pane-c" data-pane="addendum">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">Riwayat dan Workspace Addendum</h6>
                            <div class="small text-muted">Pantau perubahan kontrak, status addendum, dan buka workspace detail untuk approval atau revisi.</div>
                        </div>
                        <a href="{{ route('addendums.index', $kontrak->id) }}" class="btn-act-modern btn-act-primary">
                            <i class="bi bi-journal-text"></i> Kelola Addendum
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="termin-table">
                            <thead>
                                <tr>
                                    <th>No. Addendum</th>
                                    <th>Tanggal</th>
                                    <th>Jenis Perubahan</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kontrak->addendums as $addm)
                                <tr>
                                    <td><span class="termin-keterangan">{{ $addm->nomor_addendum }}</span></td>
                                    <td>{{ \Carbon\Carbon::parse($addm->tanggal_addendum)->isoFormat('D MMM YYYY') }}</td>
                                    <td><span class="termin-jenis-pill">{{ str_replace('_', ' ', $addm->jenis_addendum) }}</span></td>
                                    <td>
                                        @php
                                            $statusWorkflow = $addm->status_workflow ?? ($addm->status_addendum ?? 'DRAFT');
                                            $stCls = match($statusWorkflow) {
                                                'APPROVED'  => 'tp-billed',
                                                'SUBMITTED' => 'tp-ready',
                                                'REJECTED'  => 'tp-locked',
                                                default     => 'tp-draft',
                                            };
                                        @endphp
                                        <span class="termin-pill {{ $stCls }}">{{ str_replace('_', ' ', $statusWorkflow) }}</span>
                                    </td>
                                    <td><small class="text-muted">{{ Str::limit($addm->keterangan_alasan, 50) }}</small></td>
                                    <td class="text-center">
                                        <a href="{{ route('addendums.show', [$kontrak->id, $addm->id]) }}" class="btn-act-modern btn-act-soft">
                                            <i class="bi bi-search"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-cell-state">
                                            <i class="bi bi-journal-x"></i>
                                            <h6 class="text-secondary fw-bold mb-1">Belum ada addendum</h6>
                                            <small>Riwayat addendum akan tampil di sini ketika ada perubahan kontrak.</small>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- KOLOM KANAN: AUDIT TRAIL / RIWAYAT AKTIVITAS -->
    <div class="col-lg-4 col-xl-3">
        <div class="timeline-card">
            <div class="tl-head">
                <h6><i class="bi bi-clock-history"></i> Riwayat Aktivitas Proyek</h6>
            </div>
            <div class="tl-body">
                <div class="activity-list">
                    @foreach($semuaAktivitas as $idx => $akt)
                        <div class="timeline-mod">
                            <span class="tl-dot"></span>
                            <div class="tl-time">
                                <span class="tl-rel">{{ \Carbon\Carbon::parse($akt['tanggal'])->diffForHumans() }}</span>
                                <span class="text-muted">· {{ \Carbon\Carbon::parse($akt['tanggal'])->isoFormat('D MMM HH:mm') }}</span>
                            </div>
                            <p class="tl-title">{{ $akt['judul'] }}</p>
                            <div class="tl-actor"><i class="bi bi-person-circle"></i> Oleh: {{ $akt['aktor'] }}</div>
                            @if(isset($akt['catatan']) && $akt['catatan'] !== '-')
                                <div class="tl-note">"{{ $akt['catatan'] }}"</div>
                            @endif
                        </div>
                    @endforeach

                    <div class="timeline-end">Awal Inisiasi</div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>



@if($kontrak->status_kontrak === 'AKTIF')
<div class="modal fade" id="modalTagihKontrakDetail" tabindex="-1" aria-labelledby="modalTagihKontrakDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header modal-grad-success">
                <div>
                    <h5 class="modal-title fw-bold" id="modalTagihKontrakDetailLabel"><i class="bi bi-cash-stack me-2"></i>Pilih Termin / Lumpsum untuk Ditagih</h5>
                    <div class="small opacity-90">{{ $kontrak->nomor_spk }} · {{ Str::limit($kontrak->nama_pekerjaan, 80) }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                @if($readyTerms->isEmpty())
                    <div class="alert alert-light border mb-0">Belum ada termin atau lumpsum yang siap ditagih untuk kontrak ini.</div>
                @else
                    <div class="list-group">
                        @foreach($readyTerms as $termin)
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <div class="fw-bold">Termin {{ $termin->termin_ke }} - {{ str_replace('_', ' ', $termin->jenis_termin) }}</div>
                                    <div class="small text-muted">{{ $termin->keterangan_termin }}</div>
                                    <div class="small mt-1">
                                        <span class="badge bg-light text-dark border">{{ $termin->persentase }}%</span>
                                        <span class="ms-2 fw-semibold text-success">Rp {{ number_format($termin->nilai_bruto_termin, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                                <a href="{{ route('tagihan.kontrak.create', ['kontrak_id' => $kontrak->id, 'termin_id' => $termin->id]) }}" class="btn btn-primary btn-sm fw-bold">
                                    <i class="bi bi-send-plus me-1"></i> Tagih
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@foreach($readyTerms as $termin)
<div class="modal fade" id="modalTagihTermin{{ $termin->id }}" tabindex="-1" aria-labelledby="modalTagihTerminLabel{{ $termin->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header modal-grad-success">
                <h5 class="modal-title fw-bold" id="modalTagihTerminLabel{{ $termin->id }}"><i class="bi bi-cash-stack me-2"></i>Konfirmasi Buat Tagihan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="fw-bold mb-1">Termin {{ $termin->termin_ke }} - {{ str_replace('_', ' ', $termin->jenis_termin) }}</div>
                <div class="text-muted small mb-2">{{ $termin->keterangan_termin }}</div>
                <div class="small">Nilai bruto: <span class="fw-bold text-success">Rp {{ number_format($termin->nilai_bruto_termin, 0, ',', '.') }}</span></div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="{{ route('tagihan.kontrak.create', ['kontrak_id' => $kontrak->id, 'termin_id' => $termin->id]) }}" class="btn btn-primary fw-bold">
                    <i class="bi bi-send-plus me-1"></i> Lanjut Buat Tagihan
                </a>
            </div>
        </div>
    </div>
</div>
@endforeach
@endif

<div class="modal fade upload-modal" id="modalUploadGambarRab" tabindex="-1" aria-labelledby="modalUploadGambarRabLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('contracts.spk.upload-gambar-rab', $kontrak->id) }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-hero" style="background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 50%, #8b5cf6 100%);">
                <i class="bi bi-image-fill um-illust"></i>
                <button type="button" class="btn-close-um" data-bs-dismiss="modal" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
                <span class="um-tag"><i class="bi bi-stars"></i> Upload Gambar</span>
                <h5 class="um-title" id="modalUploadGambarRabLabel">
                    <i class="bi bi-image-fill me-1"></i>Gambar RAB
                </h5>
                <p class="um-sub">
                    <i class="bi bi-bookmark-check"></i>
                    Nomor SPK: <strong>{{ $kontrak->nomor_spk }}</strong>
                </p>
            </div>
            <div class="modal-body">
                <div class="um-banner" style="background: linear-gradient(135deg, rgba(14,165,233,.06), rgba(14,165,233,.02)); border-color: rgba(14,165,233,.20); border-left-color: #0ea5e9;">
                    <i class="bi bi-info-circle-fill" style="color:#0369a1;"></i>
                    <div>
                        Gambar RAB <strong>wajib diunggah</strong> sebelum export PDF Draft SPK dan akan tampil pada PDF sebelum kolom <strong>JENIS KONTRAK</strong>.
                    </div>
                </div>

                <label class="um-label" for="gambar_rab">
                    <i class="bi bi-image-fill text-info"></i>
                    File Gambar RAB
                    <span class="text-danger ms-1">*</span>
                </label>
                <label class="um-drop" data-max-mb="5" data-kind="image">
                    <input type="file" id="gambar_rab" name="gambar_rab" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
                    <div class="ud-default">
                        <div class="ud-icon" style="background: linear-gradient(135deg, #38bdf8, #0ea5e9); box-shadow: 0 8px 20px rgba(14,165,233,.30);"><i class="bi bi-image-fill"></i></div>
                        <div class="ud-title">Tarik &amp; lepaskan, atau <strong>klik untuk memilih</strong></div>
                        <div class="ud-sub">Pilih gambar Rancangan Anggaran Biaya (RAB).</div>
                        <div class="ud-meta" style="background: rgba(14,165,233,.10); color:#0369a1;"><i class="bi bi-image"></i> JPG / JPEG / PNG &middot; Maks 5MB</div>
                    </div>
                    <div class="ud-preview">
                        <div class="ud-fp-icon" style="background: linear-gradient(135deg, #38bdf8, #0ea5e9); box-shadow: 0 6px 14px rgba(14,165,233,.30);"><i class="bi bi-image-fill"></i></div>
                        <div class="ud-fp-info">
                            <div class="ud-fp-name">-</div>
                            <div class="ud-fp-detail">
                                <span class="ud-fp-size">0 KB</span>
                                <span class="ud-fp-type text-muted">-</span>
                            </div>
                            <div class="ud-bar"><span style="width:0%"></span></div>
                        </div>
                        <button type="button" class="ud-fp-remove" title="Hapus berkas"><i class="bi bi-x-lg"></i></button>
                    </div>
                </label>

                @if($gambarRabArsip)
                    <div class="um-current">
                        <span class="uc-icon" style="background: linear-gradient(135deg, rgba(14,165,233,.15), rgba(99,102,241,.10)); color:#0369a1;"><i class="bi bi-image-fill"></i></span>
                        <div class="uc-info">
                            <div class="uc-label">Gambar aktif saat ini</div>
                            <div class="uc-name">Gambar RAB &mdash; Tersimpan</div>
                        </div>
                        <a href="{{ route('contracts.spk.gambar-rab', $kontrak->id) }}" target="_blank" class="uc-link" style="border-color: rgba(14,165,233,.30); color:#0369a1;">
                            <i class="bi bi-box-arrow-up-right"></i> Lihat Gambar
                        </a>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-um-cancel" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Batal
                </button>
                <button type="submit" class="btn-um-submit" style="background: linear-gradient(135deg, #0ea5e9, #6366f1, #8b5cf6); box-shadow: 0 8px 22px rgba(14,165,233,.35);">
                    <i class="bi bi-cloud-arrow-up-fill"></i> Simpan Gambar RAB
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade upload-modal" id="modalUploadSpkFinal" tabindex="-1" aria-labelledby="modalUploadSpkFinalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('contracts.spk.upload-final', $kontrak->id) }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-hero" style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 50%, #8b5cf6 100%);">
                <i class="bi bi-file-earmark-check-fill um-illust"></i>
                <button type="button" class="btn-close-um" data-bs-dismiss="modal" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
                <span class="um-tag"><i class="bi bi-stars"></i> Upload Dokumen</span>
                <h5 class="um-title" id="modalUploadSpkFinalLabel">
                    <i class="bi bi-file-earmark-check-fill me-1"></i>SPK Bertandatangan
                </h5>
                <p class="um-sub">
                    <i class="bi bi-bookmark-check"></i>
                    Nomor SPK: <strong>{{ $kontrak->nomor_spk }}</strong>
                </p>
            </div>
            <div class="modal-body">
                <div class="um-banner">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>
                        Upload file <strong>PDF SPK final bertandatangan</strong>.
                        Jika diunggah ulang, file aktif sebelumnya akan dinonaktifkan dan file baru otomatis menjadi dokumen aktif.
                    </div>
                </div>

                <label class="um-label" for="file_spk_final_ttd">
                    <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                    File SPK Final Bertandatangan
                    <span class="text-danger ms-1">*</span>
                </label>
                <label class="um-drop" data-max-mb="5">
                    <input type="file" id="file_spk_final_ttd" name="file_spk_final_ttd" accept=".pdf" required>
                    <div class="ud-default">
                        <div class="ud-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                        <div class="ud-title">Tarik &amp; lepaskan, atau <strong>klik untuk memilih</strong></div>
                        <div class="ud-sub">Pilih dokumen SPK final yang sudah ditandatangani lengkap.</div>
                        <div class="ud-meta"><i class="bi bi-file-earmark-pdf"></i> PDF &middot; Maks 5MB</div>
                    </div>
                    <div class="ud-preview">
                        <div class="ud-fp-icon"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                        <div class="ud-fp-info">
                            <div class="ud-fp-name">-</div>
                            <div class="ud-fp-detail">
                                <span class="ud-fp-size">0 KB</span>
                                <span class="ud-fp-type text-muted">PDF</span>
                            </div>
                            <div class="ud-bar"><span style="width:0%"></span></div>
                        </div>
                        <button type="button" class="ud-fp-remove" title="Hapus berkas"><i class="bi bi-x-lg"></i></button>
                    </div>
                </label>

                @if($spkFinalArsip)
                    <div class="um-current">
                        <span class="uc-icon"><i class="bi bi-file-earmark-pdf-fill"></i></span>
                        <div class="uc-info">
                            <div class="uc-label">File aktif saat ini</div>
                            <div class="uc-name">SPK Final Bertandatangan &mdash; Tersimpan</div>
                        </div>
                        <a href="{{ Storage::url($spkFinalArsip->path_file) }}" target="_blank" class="uc-link">
                            <i class="bi bi-box-arrow-up-right"></i> Lihat Dokumen
                        </a>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-um-cancel" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Batal
                </button>
                <button type="submit" class="btn-um-submit">
                    <i class="bi bi-cloud-arrow-up-fill"></i> Simpan SPK Final
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade upload-modal" id="modalUploadRingkasanKontrakFinal" tabindex="-1" aria-labelledby="modalUploadRingkasanKontrakFinalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('contracts.ringkasan.upload-final', $kontrak->id) }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-hero" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);">
                <i class="bi bi-journal-richtext um-illust"></i>
                <button type="button" class="btn-close-um" data-bs-dismiss="modal" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
                <span class="um-tag"><i class="bi bi-stars"></i> Upload Dokumen</span>
                <h5 class="um-title" id="modalUploadRingkasanKontrakFinalLabel">
                    <i class="bi bi-journal-richtext me-1"></i>Ringkasan Kontrak Bertandatangan
                </h5>
                <p class="um-sub">
                    <i class="bi bi-bookmark-check"></i>
                    Nomor SPK: <strong>{{ $kontrak->nomor_spk }}</strong>
                </p>
            </div>
            <div class="modal-body">
                <div class="um-banner">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>
                        Upload file <strong>PDF Ringkasan Kontrak final bertandatangan</strong>.
                        Jika diunggah ulang, file aktif sebelumnya akan dinonaktifkan dan file baru otomatis menjadi dokumen aktif.
                    </div>
                </div>

                <label class="um-label" for="file_ringkasan_kontrak_final_ttd">
                    <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                    File Ringkasan Kontrak Final
                    <span class="text-danger ms-1">*</span>
                </label>
                <label class="um-drop" data-max-mb="5">
                    <input type="file" id="file_ringkasan_kontrak_final_ttd" name="file_ringkasan_kontrak_final_ttd" accept=".pdf" required>
                    <div class="ud-default">
                        <div class="ud-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                        <div class="ud-title">Tarik &amp; lepaskan, atau <strong>klik untuk memilih</strong></div>
                        <div class="ud-sub">Pilih dokumen Ringkasan Kontrak yang sudah ditandatangani lengkap.</div>
                        <div class="ud-meta"><i class="bi bi-file-earmark-pdf"></i> PDF &middot; Maks 5MB</div>
                    </div>
                    <div class="ud-preview">
                        <div class="ud-fp-icon"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                        <div class="ud-fp-info">
                            <div class="ud-fp-name">-</div>
                            <div class="ud-fp-detail">
                                <span class="ud-fp-size">0 KB</span>
                                <span class="ud-fp-type text-muted">PDF</span>
                            </div>
                            <div class="ud-bar"><span style="width:0%"></span></div>
                        </div>
                        <button type="button" class="ud-fp-remove" title="Hapus berkas"><i class="bi bi-x-lg"></i></button>
                    </div>
                </label>

                @if($ringkasanFinalArsip)
                    <div class="um-current">
                        <span class="uc-icon"><i class="bi bi-file-earmark-pdf-fill"></i></span>
                        <div class="uc-info">
                            <div class="uc-label">File aktif saat ini</div>
                            <div class="uc-name">Ringkasan Kontrak Final &mdash; Tersimpan</div>
                        </div>
                        <a href="{{ Storage::url($ringkasanFinalArsip->path_file) }}" target="_blank" class="uc-link">
                            <i class="bi bi-box-arrow-up-right"></i> Lihat Dokumen
                        </a>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-um-cancel" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Batal
                </button>
                <button type="submit" class="btn-um-submit">
                    <i class="bi bi-cloud-arrow-up-fill"></i> Simpan Ringkasan Kontrak
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade upload-modal" id="modalUploadSpmkFinal" tabindex="-1" aria-labelledby="modalUploadSpmkFinalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('contracts.spmk.upload-final', $kontrak->id) }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-hero" style="background: linear-gradient(135deg, #06b6d4 0%, #0ea5e9 50%, #2563eb 100%);">
                <i class="bi bi-file-earmark-text-fill um-illust"></i>
                <button type="button" class="btn-close-um" data-bs-dismiss="modal" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
                <span class="um-tag"><i class="bi bi-stars"></i> Upload Dokumen</span>
                <h5 class="um-title" id="modalUploadSpmkFinalLabel">
                    <i class="bi bi-file-earmark-text-fill me-1"></i>SPMK Bertandatangan
                </h5>
                <p class="um-sub">
                    <i class="bi bi-bookmark-check"></i>
                    Nomor SPMK: <strong>{{ $kontrak->nomor_spmk ?? '-' }}</strong>
                </p>
            </div>
            <div class="modal-body">
                <div class="um-banner" style="background: linear-gradient(135deg, rgba(14,165,233,.06), rgba(14,165,233,.02)); border-color: rgba(14,165,233,.20); border-left-color: #0ea5e9;">
                    <i class="bi bi-info-circle-fill" style="color:#0369a1;"></i>
                    <div>
                        Upload file <strong>PDF SPMK final bertandatangan</strong>.
                        Jika diunggah ulang, file aktif sebelumnya akan dinonaktifkan dan file baru otomatis menjadi dokumen aktif.
                    </div>
                </div>

                <label class="um-label" for="file_spmk_final_ttd">
                    <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                    File SPMK Final Bertandatangan
                    <span class="text-danger ms-1">*</span>
                </label>
                <label class="um-drop" data-max-mb="5">
                    <input type="file" id="file_spmk_final_ttd" name="file_spmk_final_ttd" accept=".pdf" required>
                    <div class="ud-default">
                        <div class="ud-icon" style="background: linear-gradient(135deg, #38bdf8, #0ea5e9); box-shadow: 0 8px 20px rgba(14,165,233,.30);"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                        <div class="ud-title">Tarik &amp; lepaskan, atau <strong>klik untuk memilih</strong></div>
                        <div class="ud-sub">Pilih dokumen SPMK final yang sudah ditandatangani lengkap.</div>
                        <div class="ud-meta" style="background: rgba(14,165,233,.10); color:#0369a1;"><i class="bi bi-file-earmark-pdf"></i> PDF &middot; Maks 5MB</div>
                    </div>
                    <div class="ud-preview">
                        <div class="ud-fp-icon"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                        <div class="ud-fp-info">
                            <div class="ud-fp-name">-</div>
                            <div class="ud-fp-detail">
                                <span class="ud-fp-size">0 KB</span>
                                <span class="ud-fp-type text-muted">PDF</span>
                            </div>
                            <div class="ud-bar"><span style="width:0%"></span></div>
                        </div>
                        <button type="button" class="ud-fp-remove" title="Hapus berkas"><i class="bi bi-x-lg"></i></button>
                    </div>
                </label>

                @if($spmkFinalArsip)
                    <div class="um-current">
                        <span class="uc-icon" style="background: linear-gradient(135deg, rgba(14,165,233,.15), rgba(99,102,241,.10)); color:#0369a1;"><i class="bi bi-file-earmark-pdf-fill"></i></span>
                        <div class="uc-info">
                            <div class="uc-label">File aktif saat ini</div>
                            <div class="uc-name">SPMK Final Bertandatangan &mdash; Tersimpan</div>
                        </div>
                        <a href="{{ Storage::url($spmkFinalArsip->path_file) }}" target="_blank" class="uc-link" style="border-color: rgba(14,165,233,.30); color:#0369a1;">
                            <i class="bi bi-box-arrow-up-right"></i> Lihat Dokumen
                        </a>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-um-cancel" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Batal
                </button>
                <button type="submit" class="btn-um-submit" style="background: linear-gradient(135deg, #06b6d4, #0ea5e9, #2563eb); box-shadow: 0 8px 22px rgba(14,165,233,.35);">
                    <i class="bi bi-cloud-arrow-up-fill"></i> Simpan SPMK Final
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('script')
<script>
document.addEventListener("DOMContentLoaded", function () {
    // ============ Upload Modal Dropzones ============
    document.querySelectorAll('.um-drop').forEach(function (zone) {
        const input = zone.querySelector('input[type="file"]');
        if (!input) return;
        const preview = zone.querySelector('.ud-preview');
        const fpIcon = preview.querySelector('.ud-fp-icon');
        const fpName = preview.querySelector('.ud-fp-name');
        const fpSize = preview.querySelector('.ud-fp-size');
        const fpType = preview.querySelector('.ud-fp-type');
        const fpBar = preview.querySelector('.ud-bar > span');
        const fpRemove = preview.querySelector('.ud-fp-remove');
        const maxMb = parseFloat(zone.dataset.maxMb || '5');
        const maxBytes = maxMb * 1024 * 1024;

        // Snapshot original icon HTML so we can restore after a thumbnail.
        const originalIconHtml = fpIcon ? fpIcon.innerHTML : '';
        const originalIconStyle = fpIcon ? fpIcon.getAttribute('style') || '' : '';

        const fmtSize = function (bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        };

        const resetIcon = function () {
            if (!fpIcon) return;
            fpIcon.innerHTML = originalIconHtml;
            fpIcon.setAttribute('style', originalIconStyle);
        };

        const setThumbnail = function (file) {
            if (!fpIcon) return;
            const url = URL.createObjectURL(file);
            fpIcon.innerHTML = '';
            fpIcon.removeAttribute('style');
            const img = document.createElement('img');
            img.src = url;
            img.alt = file.name;
            img.className = 'ud-thumb';
            img.onload = function () { URL.revokeObjectURL(url); };
            fpIcon.appendChild(img);
        };

        const adaptIconForFile = function (file) {
            if (!fpIcon) return;
            const name = (file.name || '').toLowerCase();
            const isImg = (file.type || '').startsWith('image/') || /\.(jpe?g|png|gif|webp|bmp)$/i.test(name);
            const isZip = /\.zip$/i.test(name) || file.type === 'application/zip' || file.type === 'application/x-zip-compressed';

            if (isImg) {
                setThumbnail(file);
                return;
            }
            // Non-image: choose icon set by extension
            resetIcon();
            if (isZip) {
                fpIcon.innerHTML = '<i class="bi bi-file-earmark-zip-fill"></i>';
                fpIcon.setAttribute('style', 'background: linear-gradient(135deg, #fbbf24, #f59e0b); box-shadow: 0 6px 14px rgba(245,158,11,.30);');
            }
        };

        const renderFile = function (file) {
            if (!file) {
                zone.classList.remove('is-filled');
                resetIcon();
                return;
            }
            const size = file.size || 0;
            const ratio = Math.min(size / maxBytes, 1);
            const ext = (file.name.split('.').pop() || '').toUpperCase();
            fpName.textContent = file.name;
            fpSize.textContent = fmtSize(size);
            if (fpType) fpType.textContent = ext;
            fpBar.style.width = (ratio * 100).toFixed(0) + '%';
            adaptIconForFile(file);
            zone.classList.add('is-filled');
        };

        input.addEventListener('change', function () {
            const file = input.files && input.files[0];
            renderFile(file || null);
        });

        if (fpRemove) {
            fpRemove.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                input.value = '';
                zone.classList.remove('is-filled');
                fpName.textContent = '-';
                fpSize.textContent = '0 KB';
                fpBar.style.width = '0%';
                resetIcon();
            });
        }

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
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(dt.files[0]);
                input.files = dataTransfer.files;
            }
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    // Custom tabs
    const tabs = document.querySelectorAll('#detailTabs .tab-btn');
    const panes = document.querySelectorAll('.tab-pane-c');
    tabs.forEach(t => {
        t.addEventListener('click', function () {
            const target = this.dataset.tab;
            tabs.forEach(b => b.classList.toggle('active', b.dataset.tab === target));
            panes.forEach(p => p.classList.toggle('active', p.dataset.pane === target));
        });
    });
});
</script>
@endpush
