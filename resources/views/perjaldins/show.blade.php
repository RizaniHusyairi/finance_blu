@extends('layouts.app')
@section('title')
    Detail Perjalanan Dinas
@endsection

@push('css')
<style>
    body[data-bs-theme="blue-theme"] .main-content { background: #f6f7fb; }

    /* ============ HERO STATUS-AWARE ============ */
    .perj-detail-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.25rem;
        padding: 1.5rem 2rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 14px 30px rgba(15,23,42,.18);
        animation: heroIn .55s cubic-bezier(.22,1,.36,1) both;
    }
    .perj-detail-hero::before, .perj-detail-hero::after {
        content: ''; position: absolute; border-radius: 50%;
    }
    .perj-detail-hero::before {
        right: -90px; top: -90px;
        width: 280px; height: 280px;
        background: rgba(255,255,255,.10);
    }
    .perj-detail-hero::after {
        right: 60px; bottom: -70px;
        width: 180px; height: 180px;
        background: rgba(255,255,255,.07);
    }
    .perj-detail-hero > * { position: relative; z-index: 1; }
    .hero-draft     { background: linear-gradient(135deg, #475569 0%, #334155 100%); }
    .hero-pending   { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #2563eb 100%); }
    .hero-approved  { background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%); }
    .hero-rejected  { background: linear-gradient(135deg, #f43f5e 0%, #e11d48 50%, #be123c 100%); }
    .hero-warning   { background: linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%); }
    .hero-info      { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 50%, #0369a1 100%); }

    .hero-doc-no {
        font-size: 1.5rem;
        font-weight: 800;
        color: #fff !important;
        margin: 0 0 .35rem;
        letter-spacing: -.01em;
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
    .perj-detail-hero p { color: rgba(255,255,255,.92) !important; margin: 0; }
    .perj-detail-hero .hero-meta {
        display: flex;
        gap: 1rem 2rem;
        flex-wrap: wrap;
        margin-top: 1rem;
        font-size: .8rem;
    }
    .perj-detail-hero .hero-meta .meta-item {
        display: inline-flex; align-items: center; gap: .45rem;
        opacity: .92; color: #fff;
    }
    .hero-total-card {
        background: rgba(255,255,255,.18);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,.28);
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        text-align: right;
    }
    .hero-total-card .label {
        font-size: .68rem;
        color: rgba(255,255,255,.85);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        margin: 0 0 .15rem;
    }
    .hero-total-card .value {
        font-size: 1.6rem;
        font-weight: 800;
        color: #fff;
        line-height: 1.05;
        font-variant-numeric: tabular-nums;
        text-shadow: 0 1px 2px rgba(0,0,0,.12);
    }
    .hero-total-card .sub {
        font-size: .7rem;
        color: rgba(255,255,255,.85);
        margin-top: .35rem;
    }

    .plane-illust {
        position: absolute;
        right: 1.5rem; top: 50%;
        transform: translateY(-50%) rotate(-15deg);
        font-size: 8rem;
        opacity: .14;
    }

    /* ============ STATUS ALERT ============ */
    .status-alert {
        display: flex;
        align-items: flex-start;
        gap: .85rem;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
        border: 1px solid;
        animation: secIn .55s cubic-bezier(.22,1,.36,1) .12s both;
    }
    .status-alert .sa-icon {
        width: 42px; height: 42px;
        flex-shrink: 0;
        border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.25rem;
    }
    .status-alert.alert-draft     { background: rgba(100,116,139,.06); border-color: rgba(100,116,139,.20); color: #475569; }
    .status-alert.alert-draft .sa-icon { background: rgba(100,116,139,.15); color: #475569; }
    .status-alert.alert-pending   { background: rgba(99,102,241,.06); border-color: rgba(99,102,241,.20); color: #4338ca; }
    .status-alert.alert-pending .sa-icon { background: rgba(99,102,241,.15); color: #4f46e5; }
    .status-alert.alert-approved  { background: rgba(16,185,129,.06); border-color: rgba(16,185,129,.20); color: #047857; }
    .status-alert.alert-approved .sa-icon { background: rgba(16,185,129,.15); color: #047857; }
    .status-alert.alert-rejected  { background: rgba(244,63,94,.08); border-color: rgba(244,63,94,.25); color: #991b1b; }
    .status-alert.alert-rejected .sa-icon { background: rgba(244,63,94,.18); color: #b91c1c; }
    .status-alert.alert-warning   { background: rgba(245,158,11,.08); border-color: rgba(245,158,11,.25); color: #92400e; }
    .status-alert.alert-warning .sa-icon { background: rgba(245,158,11,.18); color: #b45309; }
    .status-alert.alert-info      { background: rgba(14,165,233,.06); border-color: rgba(14,165,233,.20); color: #0369a1; }
    .status-alert.alert-info .sa-icon { background: rgba(14,165,233,.15); color: #0369a1; }
    .status-alert h6 { margin: 0 0 .15rem; font-weight: 800; font-size: .92rem; }
    .status-alert p { margin: 0; font-size: .85rem; }

    /* ============ ACTION BAR ============ */
    .action-bar {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        padding: .85rem 1.25rem;
        display: flex;
        align-items: center;
        gap: .75rem;
        flex-wrap: wrap;
        margin-bottom: 1.25rem;
        box-shadow: 0 4px 12px rgba(15,23,42,.04);
        animation: secIn .55s cubic-bezier(.22,1,.36,1) .18s both;
    }
    .btn-act {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        font-size: .82rem;
        font-weight: 600;
        padding: .55rem 1rem;
        border-radius: .65rem;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        text-decoration: none;
        transition: all .2s ease;
        cursor: pointer;
    }
    .btn-act:hover {
        border-color: #6366f1;
        background: #fafbff;
        color: #4f46e5;
        transform: translateY(-1px);
        box-shadow: 0 6px 14px rgba(99,102,241,.10);
    }
    .btn-act.btn-act-primary {
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 6px 14px rgba(99,102,241,.30);
    }
    .btn-act.btn-act-primary:hover {
        color: #fff;
        background: linear-gradient(135deg, #4f46e5, #4338ca);
        box-shadow: 0 10px 22px rgba(99,102,241,.40);
    }
    .btn-act.btn-act-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 6px 14px rgba(16,185,129,.30);
    }
    .btn-act.btn-act-success:hover {
        color: #fff;
        background: linear-gradient(135deg, #059669, #047857);
        box-shadow: 0 10px 22px rgba(16,185,129,.40);
    }
    .btn-act.btn-act-warning {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 6px 14px rgba(245,158,11,.30);
    }
    .btn-act.btn-act-warning:hover {
        color: #fff;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        box-shadow: 0 10px 22px rgba(245,158,11,.40);
    }
    .btn-act.btn-act-pdf {
        background: rgba(244,63,94,.08);
        color: #be123c;
        border-color: rgba(244,63,94,.18);
    }
    .btn-act.btn-act-pdf:hover {
        background: #f43f5e;
        color: #fff;
        border-color: #f43f5e;
    }

    .badge-success-pill {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        font-size: .78rem;
        font-weight: 600;
        padding: .5rem 1rem;
        border-radius: 999px;
        background: rgba(16,185,129,.12);
        color: #047857;
        border: 1px solid rgba(16,185,129,.20);
    }
    .badge-warning-pill {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        font-size: .78rem;
        font-weight: 600;
        padding: .5rem 1rem;
        border-radius: 999px;
        background: rgba(245,158,11,.12);
        color: #b45309;
        border: 1px solid rgba(245,158,11,.20);
    }

    /* ============ TABS ============ */
    .tabs-bar-perj {
        display: flex;
        gap: .35rem;
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        padding: .35rem;
        margin-bottom: 1rem;
        overflow-x: auto;
        scrollbar-width: thin;
        animation: secIn .55s cubic-bezier(.22,1,.36,1) .25s both;
    }
    .tabs-bar-perj .tab-btn {
        flex: 0 0 auto;
        min-width: max-content;
        padding: .65rem 1.1rem;
        border-radius: .7rem;
        background: transparent;
        border: 0;
        color: #64748b;
        font-size: .85rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        transition: all .25s cubic-bezier(.22,1,.36,1);
        white-space: nowrap;
    }
    .tabs-bar-perj .tab-btn:hover {
        color: #1e293b;
        background: #f8fafc;
    }
    .tabs-bar-perj .tab-btn.active {
        background: linear-gradient(135deg, #0ea5e9, #6366f1);
        color: #fff;
        box-shadow: 0 6px 14px rgba(99,102,241,.30);
    }
    .tabs-bar-perj .tab-btn .tab-count {
        background: rgba(255,255,255,.25);
        padding: .1rem .45rem;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 700;
    }
    .tabs-bar-perj .tab-btn:not(.active) .tab-count {
        background: rgba(99,102,241,.12);
        color: #4f46e5;
    }

    .tab-pane-d { display: none; animation: secIn .35s cubic-bezier(.22,1,.36,1) both; }
    .tab-pane-d.active { display: block; }

    /* ============ UPLOAD NOMINATIF ============ */
    .upload-nominatif-card {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.25rem;
        animation: secIn .55s cubic-bezier(.22,1,.36,1) .35s both;
    }
    .upload-nominatif-card.is-pending {
        border-left: 4px solid #f59e0b;
    }
    .upload-nominatif-card .un-head {
        display: flex;
        align-items: center;
        gap: .85rem;
        margin-bottom: 1rem;
    }
    .upload-nominatif-card .un-icon {
        width: 44px; height: 44px;
        border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, rgba(245,158,11,.15), rgba(217,119,6,.10));
        color: #b45309;
        font-size: 1.3rem;
    }
    .upload-nominatif-card.is-done .un-icon {
        background: linear-gradient(135deg, rgba(16,185,129,.15), rgba(5,150,105,.10));
        color: #047857;
    }
    .upload-slot {
        background: #fafbff;
        border: 2px dashed #e2e8f0;
        border-radius: .85rem;
        padding: 1rem;
        height: 100%;
        transition: all .2s ease;
    }
    .upload-slot.is-uploaded {
        background: rgba(16,185,129,.04);
        border-color: #10b981;
        border-style: solid;
    }
    .upload-slot:hover { border-color: #6366f1; }

    /* ============ COLLAPSIBLE BLU ============ */
    .blu-section {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        margin-bottom: 1.25rem;
        overflow: hidden;
        animation: secIn .55s cubic-bezier(.22,1,.36,1) .45s both;
    }
    .blu-section .blu-toggle {
        width: 100%;
        padding: 1rem 1.25rem;
        background: linear-gradient(180deg, #fafbff 0%, #ffffff 100%);
        border: 0;
        border-bottom: 1px solid #f1f3f7;
        display: flex;
        align-items: center;
        gap: .75rem;
        text-align: left;
        cursor: pointer;
        font-size: .95rem;
        font-weight: 700;
        color: #0f172a;
        transition: background .2s ease;
    }
    .blu-section .blu-toggle:hover { background: #fafbff; }
    .blu-section .blu-toggle .icon-bg {
        width: 32px; height: 32px;
        border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(99,102,241,.12);
        color: #4f46e5;
    }
    .blu-section .blu-toggle .blu-count {
        margin-left: auto;
        background: rgba(99,102,241,.10);
        color: #4338ca;
        font-size: .7rem;
        font-weight: 700;
        padding: .2rem .6rem;
        border-radius: 999px;
    }
    .blu-section .blu-toggle .chevron {
        transition: transform .25s ease;
    }
    .blu-section.is-open .blu-toggle .chevron { transform: rotate(180deg); }
    .blu-body {
        max-height: 0;
        overflow: hidden;
        transition: max-height .3s ease;
    }
    .blu-section.is-open .blu-body { max-height: 4000px; }
    .blu-body-inner { padding: 1rem 1.25rem; }

    /* ============ ANIMATIONS ============ */
    @keyframes heroIn {
        from { opacity: 0; transform: translateY(-12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes secIn {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ============ Style for partials wrapper ============ */
    /* Make partials inside tab pane consistent with new look */
    .tab-pane-d > .card,
    .tab-pane-d > div > .card {
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        box-shadow: 0 4px 12px rgba(15,23,42,.04);
    }

    /* ============ STANDALONE SECTIONS ============ */
    .standalone-section {
        margin-bottom: 1.25rem;
        opacity: 0;
        transform: translateY(20px);
        transition: opacity .6s cubic-bezier(.22,1,.36,1), transform .6s cubic-bezier(.22,1,.36,1);
    }
    .standalone-section.is-visible {
        opacity: 1;
        transform: translateY(0);
    }

    /* ===== Modern card base used by partials ===== */
    .modern-card {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1.25rem;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(15,23,42,.04);
        transition: box-shadow .3s ease;
        position: relative;
    }
    .modern-card:hover {
        box-shadow: 0 14px 32px rgba(15,23,42,.08);
    }
    .modern-card .mc-head {
        padding: 1.15rem 1.5rem;
        border-bottom: 1px solid #f1f3f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        background: linear-gradient(180deg, #fafbff 0%, #ffffff 100%);
        position: relative;
        overflow: hidden;
    }
    .modern-card .mc-head::before {
        content: '';
        position: absolute;
        right: -100px; top: -100px;
        width: 220px; height: 220px;
        border-radius: 50%;
        background: radial-gradient(circle, var(--mc-glow, rgba(99,102,241,.06)), transparent 70%);
        z-index: 0;
        pointer-events: none;
    }
    .modern-card .mc-head > * { position: relative; z-index: 1; }
    .modern-card .mc-head-left {
        display: flex;
        align-items: center;
        gap: .85rem;
    }
    .modern-card .mc-icon {
        width: 48px; height: 48px;
        border-radius: 14px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.3rem; color: #fff;
        flex-shrink: 0;
        background: var(--mc-icon-bg, linear-gradient(135deg, #818cf8, #6366f1));
        box-shadow: 0 8px 18px var(--mc-icon-shadow, rgba(99,102,241,.30));
        transition: transform .3s ease;
    }
    .modern-card:hover .mc-icon { transform: rotate(-6deg) scale(1.05); }
    .modern-card .mc-title {
        font-size: 1.02rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        letter-spacing: -.01em;
    }
    .modern-card .mc-sub {
        font-size: .78rem;
        color: #64748b;
        margin: .15rem 0 0;
    }
    .modern-card .mc-body { padding: 1.5rem; }

    .mc-icon-info    { --mc-icon-bg: linear-gradient(135deg, #38bdf8, #0ea5e9); --mc-icon-shadow: rgba(14,165,233,.35); --mc-glow: rgba(14,165,233,.08); }
    .mc-icon-primary { --mc-icon-bg: linear-gradient(135deg, #818cf8, #6366f1); --mc-icon-shadow: rgba(99,102,241,.35); --mc-glow: rgba(99,102,241,.08); }
    .mc-icon-success { --mc-icon-bg: linear-gradient(135deg, #34d399, #10b981); --mc-icon-shadow: rgba(16,185,129,.35); --mc-glow: rgba(16,185,129,.08); }
    .mc-icon-warning { --mc-icon-bg: linear-gradient(135deg, #fbbf24, #f59e0b); --mc-icon-shadow: rgba(245,158,11,.35); --mc-glow: rgba(245,158,11,.08); }
    .mc-icon-danger  { --mc-icon-bg: linear-gradient(135deg, #fb7185, #f43f5e); --mc-icon-shadow: rgba(244,63,94,.35); --mc-glow: rgba(244,63,94,.08); }

    /* ===== Pill chips for header counts ===== */
    .mc-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .75rem;
        font-weight: 700;
        padding: .35rem .85rem;
        border-radius: 999px;
        white-space: nowrap;
    }
    .mc-pill-primary { background: rgba(99,102,241,.12); color: #4338ca; }
    .mc-pill-success { background: rgba(16,185,129,.12); color: #047857; }
    .mc-pill-info    { background: rgba(14,165,233,.12); color: #0369a1; }

    /* ===== INFO GRID ===== */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    .info-cell {
        background: #f8fafc;
        border: 1px solid #eef0f4;
        border-radius: .85rem;
        padding: .85rem 1rem;
        transition: all .25s ease;
        position: relative;
        overflow: hidden;
    }
    .info-cell::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 3px;
        background: var(--ic-accent, #6366f1);
        opacity: 0;
        transition: opacity .25s ease;
    }
    .info-cell:hover {
        background: #fff;
        border-color: var(--ic-accent, #c7d2fe);
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(99,102,241,.08);
    }
    .info-cell:hover::before { opacity: 1; }
    .info-cell .ic-label {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
        margin-bottom: .35rem;
    }
    .info-cell .ic-label i { color: var(--ic-accent, #6366f1); }
    .info-cell .ic-value {
        display: block;
        font-size: .95rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.3;
        word-break: break-word;
    }
    .info-cell .ic-value.is-money {
        color: #047857;
        font-size: 1.05rem;
        font-variant-numeric: tabular-nums;
    }
    .info-cell .ic-value.is-mono {
        color: #4338ca;
        font-family: ui-monospace, "SF Mono", monospace;
        font-size: .9rem;
    }

    .ic-primary  { --ic-accent: #6366f1; }
    .ic-info     { --ic-accent: #0ea5e9; }
    .ic-success  { --ic-accent: #10b981; }
    .ic-warning  { --ic-accent: #f59e0b; }
    .ic-danger   { --ic-accent: #f43f5e; }
    .ic-violet   { --ic-accent: #8b5cf6; }

    /* ===== VERIFIKATOR CARD ===== */
    .vk-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 1rem;
    }
    .vk-card {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        padding: 1rem 1.15rem;
        position: relative;
        overflow: hidden;
        transition: all .3s cubic-bezier(.22,1,.36,1);
        animation: vkIn .45s cubic-bezier(.22,1,.36,1) both;
    }
    .vk-card:nth-child(1) { animation-delay: .05s; }
    .vk-card:nth-child(2) { animation-delay: .12s; }
    .vk-card:nth-child(3) { animation-delay: .19s; }
    .vk-card:nth-child(4) { animation-delay: .26s; }
    .vk-card:nth-child(5) { animation-delay: .33s; }
    .vk-card:nth-child(6) { animation-delay: .40s; }
    .vk-card::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: var(--vk-accent, #6366f1);
    }
    .vk-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 28px rgba(15,23,42,.08);
        border-color: var(--vk-accent);
    }
    @keyframes vkIn {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .vk-card .vk-head {
        display: flex; align-items: center; gap: .65rem;
        margin-bottom: .85rem;
    }
    .vk-avatar {
        width: 42px; height: 42px;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--vk-accent, #6366f1);
        color: #fff;
        font-weight: 800;
        font-size: .95rem;
        flex-shrink: 0;
        box-shadow: 0 6px 14px var(--vk-shadow, rgba(99,102,241,.30));
        text-transform: uppercase;
    }
    .vk-role {
        font-size: .65rem;
        font-weight: 700;
        color: var(--vk-accent, #4f46e5);
        text-transform: uppercase;
        letter-spacing: .08em;
        background: var(--vk-soft, rgba(99,102,241,.10));
        padding: .15rem .55rem;
        border-radius: .35rem;
        display: inline-block;
        margin-bottom: .15rem;
    }
    .vk-name {
        font-weight: 700;
        color: #1e293b;
        font-size: .92rem;
        line-height: 1.25;
        margin: 0;
    }
    .vk-status {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .7rem;
        font-weight: 700;
        padding: .3rem .75rem;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }
    .vk-status.vs-approved { background: linear-gradient(135deg, #34d399, #10b981); color: #fff; box-shadow: 0 4px 10px rgba(16,185,129,.35); }
    .vk-status.vs-pending  { background: linear-gradient(135deg, #818cf8, #6366f1); color: #fff; box-shadow: 0 4px 10px rgba(99,102,241,.35); }
    .vk-status.vs-revision { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #fff; box-shadow: 0 4px 10px rgba(245,158,11,.35); }
    .vk-status.vs-rejected { background: linear-gradient(135deg, #fb7185, #f43f5e); color: #fff; box-shadow: 0 4px 10px rgba(244,63,94,.35); }
    .vk-status.vs-empty    { background: #f1f5f9; color: #64748b; }

    .vk-primary { --vk-accent: #6366f1; --vk-soft: rgba(99,102,241,.12); --vk-shadow: rgba(99,102,241,.30); }
    .vk-success { --vk-accent: #10b981; --vk-soft: rgba(16,185,129,.12); --vk-shadow: rgba(16,185,129,.30); }
    .vk-info    { --vk-accent: #0ea5e9; --vk-soft: rgba(14,165,233,.12); --vk-shadow: rgba(14,165,233,.30); }
    .vk-warning { --vk-accent: #f59e0b; --vk-soft: rgba(245,158,11,.12); --vk-shadow: rgba(245,158,11,.30); }
    .vk-danger  { --vk-accent: #f43f5e; --vk-soft: rgba(244,63,94,.12); --vk-shadow: rgba(244,63,94,.30); }
    .vk-violet  { --vk-accent: #8b5cf6; --vk-soft: rgba(139,92,246,.12); --vk-shadow: rgba(139,92,246,.30); }

    /* ===== WORKFLOW STEPPER ===== */
    .wf-stepper {
        display: flex;
        align-items: stretch;
        gap: 0;
        flex-wrap: wrap;
        position: relative;
    }
    .wf-step {
        flex: 1 1 200px;
        min-width: 180px;
        position: relative;
        text-align: center;
        padding: 1rem .5rem;
    }
    .wf-step::after {
        content: '';
        position: absolute;
        right: -10px; top: 38px;
        width: 20px; height: 2px;
        background: linear-gradient(90deg, var(--ws-from, #cbd5e1), var(--ws-to, #cbd5e1));
        z-index: 0;
    }
    .wf-step:last-child::after { display: none; }
    .wf-circle {
        width: 56px; height: 56px;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
        color: #fff;
        position: relative;
        z-index: 1;
        background: var(--ws-bg, #94a3b8);
        box-shadow: 0 8px 18px var(--ws-shadow, rgba(148,163,184,.30));
        transition: all .35s cubic-bezier(.22,1,.36,1);
        margin-bottom: .75rem;
    }
    .wf-step.is-active .wf-circle {
        animation: wfPulse 1.6s ease-in-out infinite;
    }
    @keyframes wfPulse {
        0%, 100% { box-shadow: 0 0 0 0 var(--ws-shadow), 0 8px 18px var(--ws-shadow); }
        50%      { box-shadow: 0 0 0 10px transparent, 0 8px 18px var(--ws-shadow); }
    }
    .wf-step .wf-label {
        font-weight: 700;
        font-size: .85rem;
        color: #1e293b;
        margin: 0 0 .15rem;
        line-height: 1.2;
    }
    .wf-step .wf-sublabel {
        font-size: .7rem;
        color: #94a3b8;
        line-height: 1.35;
        margin-bottom: .55rem;
    }
    .wf-step .wf-state-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .65rem;
        font-weight: 700;
        padding: .2rem .6rem;
        border-radius: 999px;
        background: var(--ws-bg, #94a3b8);
        color: #fff;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .wf-step .wf-time {
        font-size: .68rem;
        color: #94a3b8;
        margin-top: .35rem;
    }

    .wf-state-done     { --ws-bg: linear-gradient(135deg, #34d399, #10b981); --ws-shadow: rgba(16,185,129,.40); --ws-from: #34d399; --ws-to: #10b981; }
    .wf-state-active   { --ws-bg: linear-gradient(135deg, #818cf8, #6366f1); --ws-shadow: rgba(99,102,241,.45); --ws-from: #6366f1; --ws-to: #cbd5e1; }
    .wf-state-revision { --ws-bg: linear-gradient(135deg, #fbbf24, #f59e0b); --ws-shadow: rgba(245,158,11,.40); --ws-from: #fbbf24; --ws-to: #cbd5e1; }
    .wf-state-rejected { --ws-bg: linear-gradient(135deg, #fb7185, #f43f5e); --ws-shadow: rgba(244,63,94,.40); --ws-from: #fb7185; --ws-to: #cbd5e1; }
    .wf-state-pending  { --ws-bg: #cbd5e1; --ws-shadow: rgba(148,163,184,.20); --ws-from: #cbd5e1; --ws-to: #cbd5e1; }

    .wf-revision-callout {
        background: linear-gradient(135deg, rgba(245,158,11,.08), rgba(217,119,6,.04));
        border: 1px solid rgba(245,158,11,.25);
        border-left: 4px solid #f59e0b;
        border-radius: .85rem;
        padding: 1rem 1.15rem;
        margin-top: 1.25rem;
        display: flex;
        gap: .85rem;
        align-items: flex-start;
    }
    .wf-revision-callout .wfr-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(245,158,11,.18);
        color: #b45309;
        font-size: 1.05rem;
        flex-shrink: 0;
    }
    .wf-revision-callout .wfr-title {
        font-weight: 800;
        color: #92400e;
        margin: 0 0 .25rem;
    }
    .wf-revision-callout p {
        font-size: .85rem;
        color: #78350f;
        margin: 0;
    }

    /* ===== PESERTA LIST ===== */
    .peserta-acc {
        display: flex;
        flex-direction: column;
        gap: .75rem;
    }
    .peserta-item {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        overflow: hidden;
        transition: all .3s cubic-bezier(.22,1,.36,1);
        animation: pesertaIn .4s cubic-bezier(.22,1,.36,1) both;
    }
    .peserta-item:nth-child(1) { animation-delay: .05s; }
    .peserta-item:nth-child(2) { animation-delay: .10s; }
    .peserta-item:nth-child(3) { animation-delay: .15s; }
    .peserta-item:nth-child(4) { animation-delay: .20s; }
    .peserta-item:nth-child(n+5) { animation-delay: .25s; }
    @keyframes pesertaIn {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .peserta-item:hover {
        border-color: #c7d2fe;
        box-shadow: 0 10px 20px rgba(99,102,241,.08);
    }
    .peserta-item .peserta-toggle {
        width: 100%;
        background: linear-gradient(180deg, #fafbff 0%, #fff 100%);
        border: 0;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        cursor: pointer;
        text-align: left;
        transition: background .2s ease;
    }
    .peserta-item .peserta-toggle:hover {
        background: linear-gradient(180deg, #f1f5f9 0%, #fafbff 100%);
    }
    .peserta-item.is-open .peserta-toggle {
        background: linear-gradient(135deg, rgba(99,102,241,.08), rgba(14,165,233,.04));
    }
    .peserta-num {
        width: 32px; height: 32px;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #818cf8, #6366f1);
        color: #fff;
        font-size: .82rem;
        font-weight: 800;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(99,102,241,.35);
    }
    .peserta-info { flex: 1; min-width: 0; }
    .peserta-name {
        font-weight: 700;
        color: #0f172a;
        font-size: .95rem;
        margin: 0 0 .15rem;
    }
    .peserta-meta {
        font-size: .75rem;
        color: #64748b;
    }
    .peserta-quick-stats {
        display: none;
        gap: .35rem;
        flex-shrink: 0;
        flex-wrap: wrap;
    }
    @media (min-width: 768px) {
        .peserta-quick-stats { display: flex; }
    }
    .pq-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .7rem;
        font-weight: 600;
        padding: .25rem .65rem;
        border-radius: 999px;
        background: #f1f5f9;
        color: #475569;
    }
    .pq-pill.pq-money {
        background: rgba(16,185,129,.12);
        color: #047857;
        font-weight: 700;
    }
    .peserta-chevron {
        color: #94a3b8;
        font-size: 1.2rem;
        transition: transform .3s ease;
    }
    .peserta-item.is-open .peserta-chevron {
        transform: rotate(180deg);
        color: #4f46e5;
    }
    .peserta-body {
        max-height: 0;
        overflow: hidden;
        transition: max-height .4s ease;
    }
    .peserta-item.is-open .peserta-body { max-height: 1500px; }
    .peserta-body-inner {
        padding: 1.25rem 1.25rem 1.5rem;
        border-top: 1px solid #f1f3f7;
    }

    .peserta-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: .75rem;
        margin-bottom: 1.25rem;
    }
    .pi-cell {
        background: #f8fafc;
        border: 1px solid #eef0f4;
        border-radius: .65rem;
        padding: .65rem .85rem;
    }
    .pi-cell .pi-label {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
        margin-bottom: .2rem;
    }
    .pi-cell .pi-value {
        display: block;
        font-size: .82rem;
        font-weight: 700;
        color: #1e293b;
        word-break: break-word;
    }
    .pi-attachment {
        background: rgba(244,63,94,.06);
        border: 1px solid rgba(244,63,94,.18);
    }
    .pi-attachment .pi-link {
        color: #be123c;
        font-weight: 700;
        text-decoration: none;
        font-size: .82rem;
    }

    .biaya-card {
        background: linear-gradient(135deg, rgba(99,102,241,.05), rgba(14,165,233,.03));
        border: 1px solid rgba(99,102,241,.18);
        border-radius: 1rem;
        padding: 1rem 1.15rem;
    }
    .biaya-title {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #4338ca;
        margin-bottom: .85rem;
    }
    .biaya-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
        gap: .55rem;
    }
    .biaya-cell {
        background: #fff;
        border-radius: .65rem;
        padding: .55rem .75rem;
        border: 1px solid rgba(99,102,241,.15);
        transition: all .2s ease;
    }
    .biaya-cell:hover {
        border-color: #6366f1;
        transform: translateY(-2px);
    }
    .biaya-cell.subtotal {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 8px 18px rgba(16,185,129,.30);
    }
    .biaya-cell.subtotal .b-label,
    .biaya-cell.subtotal .b-value { color: #fff !important; }
    .biaya-cell .b-label {
        display: block;
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
        margin-bottom: .15rem;
    }
    .biaya-cell .b-value {
        display: block;
        font-size: .82rem;
        font-weight: 700;
        color: #1e293b;
        font-variant-numeric: tabular-nums;
    }

    /* Layout rincian biaya — selaras dengan form tambah (Tiket/Transport/Penginapan + grup Uang Harian + Subtotal) */
    .biaya-layout {
        display: flex;
        flex-wrap: wrap;
        gap: .55rem;
        align-items: stretch;
    }
    .biaya-layout > .biaya-cell { flex: 1 1 120px; }
    .biaya-layout > .biaya-cell.subtotal { flex: 1 1 150px; display: flex; flex-direction: column; justify-content: center; }
    .uh-group {
        flex: 2 1 320px;
        background: #fff;
        border: 1px solid rgba(99,102,241,.18);
        border-radius: .65rem;
        padding: .55rem .75rem;
        display: flex;
        flex-direction: column;
    }
    .uh-group-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        margin-bottom: .45rem;
    }
    .uh-group-title {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #4338ca;
    }
    .uh-group-total {
        font-size: .8rem;
        font-weight: 800;
        color: #4338ca;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .uh-group-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: .4rem;
    }
    .uh-sub {
        background: #f8fafc;
        border: 1px solid #eef0f4;
        border-radius: .5rem;
        padding: .4rem .5rem;
        text-align: center;
    }
    .uh-sub .b-label {
        display: block;
        font-size: .58rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #94a3b8;
        margin-bottom: .1rem;
    }
    .uh-sub .b-value {
        display: block;
        font-size: .76rem;
        font-weight: 700;
        color: #1e293b;
        font-variant-numeric: tabular-nums;
    }

    /* ===== TIMELINE / AUDIT ===== */
    .timeline-modern {
        position: relative;
        padding-left: 32px;
    }
    .timeline-modern::before {
        content: '';
        position: absolute;
        left: 11px; top: 6px; bottom: 6px;
        width: 2px;
        background: linear-gradient(180deg, #818cf8, #c4b5fd, #f1f5f9);
        border-radius: 999px;
    }
    .tm-item {
        position: relative;
        margin-bottom: 1.5rem;
        animation: tmIn .55s cubic-bezier(.22,1,.36,1) both;
    }
    .tm-item:nth-child(1) { animation-delay: .05s; }
    .tm-item:nth-child(2) { animation-delay: .15s; }
    .tm-item:nth-child(3) { animation-delay: .25s; }
    .tm-item:nth-child(4) { animation-delay: .35s; }
    .tm-item:nth-child(n+5) { animation-delay: .45s; }
    .tm-item:last-child { margin-bottom: 0; }
    @keyframes tmIn {
        from { opacity: 0; transform: translateX(-12px); }
        to   { opacity: 1; transform: translateX(0); }
    }
    .tm-dot {
        position: absolute;
        left: -32px; top: 0;
        width: 24px; height: 24px;
        border-radius: 50%;
        background: var(--tm-bg, linear-gradient(135deg, #818cf8, #6366f1));
        border: 4px solid #fff;
        box-shadow: 0 0 0 1px var(--tm-ring, #c7d2fe), 0 4px 10px var(--tm-shadow, rgba(99,102,241,.30));
        z-index: 1;
        transition: transform .25s ease;
    }
    .tm-item:hover .tm-dot { transform: scale(1.15); }
    .tm-card {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: .85rem;
        padding: .9rem 1.15rem;
        transition: all .25s ease;
    }
    .tm-card:hover {
        border-color: var(--tm-border, #c7d2fe);
        box-shadow: 0 10px 20px rgba(15,23,42,.06);
        transform: translateX(4px);
    }
    .tm-row {
        display: flex;
        justify-content: space-between;
        gap: .85rem;
        flex-wrap: wrap;
        margin-bottom: .35rem;
    }
    .tm-aksi-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .7rem;
        font-weight: 700;
        padding: .25rem .65rem;
        border-radius: 999px;
        background: var(--tm-soft, rgba(99,102,241,.10));
        color: var(--tm-accent, #4338ca);
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .tm-status-flow {
        font-size: .82rem;
        color: #475569;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .tm-status-flow .tm-prev {
        color: #94a3b8;
        font-size: .76rem;
    }
    .tm-status-flow .tm-arrow { color: #cbd5e1; }
    .tm-status-flow .tm-new {
        font-weight: 700;
        color: var(--tm-accent, #4338ca);
    }
    .tm-time {
        font-size: .72rem;
        color: #94a3b8;
        white-space: nowrap;
    }
    .tm-note {
        margin-top: .55rem;
        padding: .55rem .75rem;
        background: var(--tm-soft, rgba(99,102,241,.06));
        border-left: 3px solid var(--tm-accent, #6366f1);
        border-radius: .5rem;
        font-size: .82rem;
        color: #475569;
    }
    .tm-note i { color: var(--tm-accent); margin-right: .35rem; }
    .tm-user {
        margin-top: .45rem;
        font-size: .72rem;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }

    .tm-primary { --tm-bg: linear-gradient(135deg, #818cf8, #6366f1); --tm-shadow: rgba(99,102,241,.30); --tm-ring: #c7d2fe; --tm-soft: rgba(99,102,241,.10); --tm-accent: #4338ca; --tm-border: #c7d2fe; }
    .tm-success { --tm-bg: linear-gradient(135deg, #34d399, #10b981); --tm-shadow: rgba(16,185,129,.30); --tm-ring: #6ee7b7; --tm-soft: rgba(16,185,129,.10); --tm-accent: #047857; --tm-border: #a7f3d0; }
    .tm-warning { --tm-bg: linear-gradient(135deg, #fbbf24, #f59e0b); --tm-shadow: rgba(245,158,11,.30); --tm-ring: #fcd34d; --tm-soft: rgba(245,158,11,.10); --tm-accent: #b45309; --tm-border: #fde68a; }
    .tm-danger  { --tm-bg: linear-gradient(135deg, #fb7185, #f43f5e); --tm-shadow: rgba(244,63,94,.30); --tm-ring: #fca5a5; --tm-soft: rgba(244,63,94,.10); --tm-accent: #b91c1c; --tm-border: #fecaca; }
    .tm-info    { --tm-bg: linear-gradient(135deg, #38bdf8, #0ea5e9); --tm-shadow: rgba(14,165,233,.30); --tm-ring: #7dd3fc; --tm-soft: rgba(14,165,233,.10); --tm-accent: #0369a1; --tm-border: #bae6fd; }

    .empty-state-modern {
        text-align: center;
        padding: 3rem 1rem;
        color: #94a3b8;
    }
    .empty-state-modern i {
        font-size: 3rem;
        background: linear-gradient(135deg, #c7d2fe, #818cf8);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: .75rem;
        display: block;
    }

    /* ============ COA SECTION (premium) ============ */
    .coa-section {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1.25rem;
        padding: 1.5rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 4px 12px rgba(15,23,42,.04);
        animation: secIn .55s cubic-bezier(.22,1,.36,1) .42s both;
        overflow: hidden;
        position: relative;
    }
    .coa-section::before {
        content: '';
        position: absolute;
        right: -120px; top: -120px;
        width: 280px; height: 280px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(99,102,241,.08), transparent 70%);
        z-index: 0;
        pointer-events: none;
    }
    .coa-section > * { position: relative; z-index: 1; }

    .coa-section-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }
    .coa-section-icon {
        width: 56px; height: 56px;
        border-radius: 16px;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #38bdf8, #6366f1, #a855f7);
        color: #fff;
        font-size: 1.6rem;
        flex-shrink: 0;
        box-shadow: 0 10px 24px rgba(99,102,241,.35);
    }
    .coa-section-title {
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 .15rem;
        font-size: 1.1rem;
        letter-spacing: -.01em;
    }
    .coa-section-sub {
        margin: 0;
        font-size: .82rem;
        color: #64748b;
    }
    .coa-mini-stats {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
    }
    .coa-mini-stat {
        background: #f8fafc;
        border: 1px solid #eef0f4;
        border-radius: .65rem;
        padding: .55rem .85rem;
        min-width: 100px;
    }
    .cms-label {
        display: block;
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
        margin-bottom: .15rem;
    }
    .cms-value {
        display: block;
        font-size: 1.15rem;
        font-weight: 800;
        color: #1e293b;
        font-variant-numeric: tabular-nums;
        line-height: 1;
    }
    .cms-value-money {
        display: block;
        font-size: .92rem;
        font-weight: 800;
        color: #4f46e5;
        font-variant-numeric: tabular-nums;
        line-height: 1.2;
    }

    .coa-progress-bar {
        position: relative;
        height: 12px;
        background: #f1f5f9;
        border-radius: 999px;
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    .coa-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899);
        background-size: 200% 100%;
        border-radius: 999px;
        transition: width .8s cubic-bezier(.22,1,.36,1);
        animation: shimmerSlide 3s linear infinite;
        box-shadow: 0 2px 6px rgba(99,102,241,.30);
    }
    @keyframes shimmerSlide {
        0%   { background-position: 0% 0; }
        100% { background-position: 200% 0; }
    }
    .coa-progress-label {
        position: absolute;
        left: 50%; top: 50%;
        transform: translate(-50%, -50%);
        font-size: .7rem;
        font-weight: 700;
        color: #fff;
        text-shadow: 0 1px 2px rgba(0,0,0,.2);
        letter-spacing: .03em;
    }

    /* ============ COA CARD (premium) ============ */
    .coa-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
        gap: 1.1rem;
    }
    .coa-card {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1.1rem;
        padding: 1.25rem 1.35rem;
        position: relative;
        overflow: hidden;
        transition: all .3s cubic-bezier(.22,1,.36,1);
        animation: coaCardIn .45s cubic-bezier(.22,1,.36,1) both;
    }
    .coa-card:nth-child(1) { animation-delay: .05s; }
    .coa-card:nth-child(2) { animation-delay: .12s; }
    .coa-card:nth-child(3) { animation-delay: .19s; }
    .coa-card:nth-child(4) { animation-delay: .26s; }
    .coa-card:nth-child(n+5) { animation-delay: .33s; }
    .coa-card::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 5px;
        background: linear-gradient(90deg, var(--coa-accent), color-mix(in srgb, var(--coa-accent) 50%, #ec4899));
        z-index: 1;
    }
    .coa-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 18px 36px rgba(15,23,42,.10);
        border-color: var(--coa-accent);
    }
    .coa-card.no-coa {
        background: linear-gradient(180deg, rgba(245,158,11,.04) 0%, #fff 100%);
        border-color: rgba(245,158,11,.20);
        border-style: dashed;
    }
    .coa-card.no-coa::before {
        background: linear-gradient(90deg, #fbbf24, #f59e0b);
    }
    @keyframes coaCardIn {
        from { opacity: 0; transform: translateY(16px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* Decorative blob */
    .coa-blob {
        position: absolute;
        right: -40px; top: -40px;
        width: 130px; height: 130px;
        border-radius: 50%;
        background: var(--coa-soft, rgba(99,102,241,.10));
        z-index: 0;
        transition: transform .6s cubic-bezier(.22,1,.36,1);
    }
    .coa-card:hover .coa-blob {
        transform: scale(1.3) translate(-10px, 10px);
    }
    .coa-card > *:not(.coa-blob) { position: relative; z-index: 1; }

    .coa-card-head {
        display: flex;
        align-items: center;
        gap: .75rem;
        margin-bottom: 1rem;
    }
    .coa-icon {
        width: 50px; height: 50px;
        border-radius: 14px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
        background: var(--coa-soft, rgba(99,102,241,.10));
        color: var(--coa-accent, #4f46e5);
        flex-shrink: 0;
        transition: transform .3s ease;
    }
    .coa-card:hover .coa-icon {
        transform: rotate(-8deg) scale(1.05);
    }
    .coa-komp-name {
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 .15rem;
        font-size: 1rem;
        letter-spacing: -.01em;
    }
    .coa-komp-code {
        font-size: .65rem;
        font-weight: 700;
        color: var(--coa-accent, #4f46e5);
        text-transform: uppercase;
        letter-spacing: .08em;
        font-family: ui-monospace, "SF Mono", monospace;
        background: var(--coa-soft, rgba(99,102,241,.10));
        padding: .2rem .55rem;
        border-radius: .35rem;
    }
    .coa-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .68rem;
        font-weight: 700;
        padding: .3rem .7rem;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .coa-pill-ok {
        background: linear-gradient(135deg, #34d399, #10b981);
        color: #fff;
        box-shadow: 0 4px 10px rgba(16,185,129,.35);
    }
    .coa-pill-empty {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: #fff;
        box-shadow: 0 4px 10px rgba(245,158,11,.35);
    }

    /* Hero number */
    .coa-hero-num {
        display: flex;
        align-items: baseline;
        gap: .35rem;
        margin-bottom: .55rem;
    }
    .chn-currency {
        font-size: .85rem;
        font-weight: 700;
        color: #94a3b8;
    }
    .chn-value {
        font-size: 1.7rem;
        font-weight: 800;
        color: var(--coa-accent, #4f46e5);
        font-variant-numeric: tabular-nums;
        letter-spacing: -.02em;
        line-height: 1;
    }
    .coa-hero-meta {
        display: flex;
        gap: .35rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }
    .coa-meta-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .72rem;
        font-weight: 600;
        padding: .25rem .65rem;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid #eef0f4;
        color: #475569;
    }
    .coa-meta-pill i { color: var(--coa-accent); }

    .coa-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
        margin: 0 0 1rem;
    }

    /* COA detail rows */
    .coa-detail {
        display: flex;
        flex-direction: column;
        gap: .65rem;
    }
    .coa-detail-row {
        display: flex;
        gap: .65rem;
        align-items: flex-start;
    }
    .coa-mini-icon {
        width: 28px; height: 28px;
        flex-shrink: 0;
        border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--coa-soft);
        color: var(--coa-accent);
        font-size: .8rem;
    }
    .coa-detail-label {
        display: block;
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
        margin-bottom: .15rem;
    }
    .coa-mak-chip {
        background: var(--coa-soft);
        border: 1px solid color-mix(in srgb, var(--coa-accent) 30%, transparent);
        color: var(--coa-accent);
        padding: .25rem .65rem;
        border-radius: .45rem;
        font-family: ui-monospace, "SF Mono", monospace;
        font-size: .8rem;
        font-weight: 700;
        display: inline-block;
    }
    .coa-nama-value {
        font-size: .87rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
        line-height: 1.4;
    }

    /* Pagu sub-card */
    .coa-pagu-card {
        background: linear-gradient(135deg, #fafbff 0%, #f8fafc 100%);
        border: 1px solid #eef0f4;
        border-radius: .85rem;
        padding: .85rem;
        margin-top: .35rem;
    }
    .coa-pagu-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .85rem;
        margin-bottom: .65rem;
    }
    .coa-pagu-cell .cpc-label {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
        margin-bottom: .15rem;
    }
    .coa-pagu-cell .cpc-value {
        display: block;
        font-size: .85rem;
        font-weight: 700;
        color: #1e293b;
        font-variant-numeric: tabular-nums;
    }
    .coa-pagu-progress {
        display: flex;
        align-items: center;
        gap: .55rem;
    }
    .cpp-track {
        flex: 1;
        height: 6px;
        background: #fff;
        border-radius: 999px;
        overflow: hidden;
        border: 1px solid #eef0f4;
    }
    .cpp-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--coa-accent), color-mix(in srgb, var(--coa-accent) 60%, #ec4899));
        border-radius: 999px;
        transition: width .8s cubic-bezier(.22,1,.36,1);
    }
    .cpp-text {
        font-size: .68rem;
        font-weight: 700;
        color: #475569;
        white-space: nowrap;
    }

    /* Empty state for no-coa cards */
    .coa-empty {
        background: rgba(245,158,11,.06);
        border: 1px dashed rgba(245,158,11,.30);
        border-radius: .85rem;
        padding: 1.25rem 1rem;
        text-align: center;
    }
    .coa-empty-icon {
        width: 44px; height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(245,158,11,.20), rgba(217,119,6,.10));
        color: #b45309;
        font-size: 1.3rem;
        display: inline-flex; align-items: center; justify-content: center;
        margin-bottom: .55rem;
        animation: pulseHourglass 2s ease-in-out infinite;
    }
    @keyframes pulseHourglass {
        0%, 100% { transform: rotate(0deg); }
        50%      { transform: rotate(180deg); }
    }
    .coa-empty-text {
        font-size: .82rem;
        color: #92400e;
        margin: 0;
        line-height: 1.4;
    }

    /* ============ BUKTI DUKUNG SECTION (premium) ============ */
    .bukti-section {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: 0 4px 12px rgba(15,23,42,.04);
        overflow: hidden;
        position: relative;
    }
    .bukti-section::before {
        content: '';
        position: absolute;
        right: -120px; top: -120px;
        width: 280px; height: 280px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(14,165,233,.10), transparent 70%);
        z-index: 0; pointer-events: none;
    }
    .bukti-section > * { position: relative; z-index: 1; }
    .bukti-head {
        display: flex; justify-content: space-between; align-items: flex-start;
        gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;
    }
    .bukti-head-icon {
        width: 56px; height: 56px; border-radius: 16px;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #38bdf8, #6366f1, #a855f7);
        color: #fff; font-size: 1.55rem; flex-shrink: 0;
        box-shadow: 0 10px 24px rgba(99,102,241,.35);
    }
    .bukti-title { font-weight: 800; color: #0f172a; margin: 0 0 .15rem; font-size: 1.1rem; letter-spacing: -.01em; }
    .bukti-sub { margin: 0; font-size: .82rem; color: #64748b; }
    .bukti-stats { display: flex; gap: .5rem; flex-wrap: wrap; }
    .bukti-stat {
        background: #f8fafc; border: 1px solid #eef0f4; border-radius: .65rem;
        padding: .55rem .9rem; min-width: 92px; text-align: center;
    }
    .bukti-stat .bs-label { display: block; font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-bottom: .1rem; }
    .bukti-stat .bs-value { display: block; font-size: 1.25rem; font-weight: 800; color: #0f172a; line-height: 1; font-variant-numeric: tabular-nums; }
    .bukti-stat .bs-value.text-success { color: #047857; }

    .bukti-group {
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        overflow: hidden;
        margin-bottom: .85rem;
        background: #fff;
        animation: vkIn .45s cubic-bezier(.22,1,.36,1) both;
    }
    .bukti-group:last-child { margin-bottom: 0; }
    .bukti-group-head {
        display: flex; align-items: center; gap: .75rem;
        padding: .8rem 1.1rem;
        background: linear-gradient(135deg, rgba(99,102,241,.07), rgba(14,165,233,.04));
        border-bottom: 1px solid #f1f3f7;
    }
    .bukti-pnum {
        width: 30px; height: 30px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #818cf8, #6366f1);
        color: #fff; font-weight: 800; font-size: .8rem; flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(99,102,241,.35);
    }
    .bukti-pname { font-weight: 700; color: #0f172a; font-size: .92rem; margin: 0; line-height: 1.2; }
    .bukti-pmeta { font-size: .72rem; color: #64748b; }
    .bukti-pcount {
        margin-left: auto; flex-shrink: 0;
        font-size: .7rem; font-weight: 700;
        padding: .25rem .7rem; border-radius: 999px;
        background: rgba(16,185,129,.12); color: #047857;
        white-space: nowrap;
    }
    .bukti-files {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(225px, 1fr));
        gap: .65rem; padding: 1rem 1.1rem;
    }
    .bukti-file {
        display: flex; align-items: center; gap: .7rem;
        padding: .65rem .75rem;
        border: 1px solid #eef0f4; border-radius: .8rem;
        background: #fafbff;
        text-decoration: none;
        transition: all .25s cubic-bezier(.22,1,.36,1);
        position: relative; overflow: hidden;
    }
    .bukti-file::before {
        content: ''; position: absolute; inset: 0 auto 0 0; width: 3px;
        background: var(--bf-accent, #6366f1); opacity: .85;
    }
    .bukti-file:hover {
        border-color: var(--bf-accent, #6366f1);
        background: #fff;
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(15,23,42,.08);
    }
    .bf-icon {
        width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.2rem; color: #fff;
        background: var(--bf-accent, #6366f1);
        box-shadow: 0 6px 14px var(--bf-shadow, rgba(99,102,241,.30));
    }
    .bf-meta { min-width: 0; flex: 1; }
    .bf-type { display: block; font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: var(--bf-accent, #4338ca); }
    .bf-name { display: block; font-size: .8rem; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .bf-ext { display: block; font-size: .6rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: #94a3b8; }
    .bf-view {
        margin-left: auto; flex-shrink: 0;
        color: #cbd5e1; font-size: 1rem;
        transition: color .2s ease, transform .2s ease;
    }
    .bukti-file:hover .bf-view { color: var(--bf-accent, #6366f1); transform: translateX(2px); }

    .bukti-empty { text-align: center; padding: 2.5rem 1rem; color: #94a3b8; }
    .bukti-empty i {
        font-size: 2.75rem;
        background: linear-gradient(135deg, #bae6fd, #818cf8);
        -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
        display: block; margin-bottom: .5rem;
    }

    /* Bukti dukung di dalam kartu peserta (gabungan) */
    .peserta-bukti { margin-top: 1.25rem; }
    .peserta-bukti .bukti-files { padding: 0; }
    .peserta-bukti-empty {
        font-size: .82rem;
        color: #94a3b8;
        background: #f8fafc;
        border: 1px dashed #e2e8f0;
        border-radius: .75rem;
        padding: .85rem 1rem;
        text-align: center;
    }
</style>
@endpush


@section('content')
<x-page-title title="Manajemen Perjaldin" subtitle="Detail Dokumen" />

@php
    $status = $tagihan->status;

    $statusInfoMap = [
        'DRAFT'                          => ['hero' => 'hero-draft',    'alert' => 'alert-draft',    'icon' => 'bi-pencil-square',          'label' => 'Draft',                'msg' => 'Data belum diajukan. Silakan lengkapi dan ajukan dokumen.'],
        'PENDING_VERIFIKASI_PERJALDIN'   => ['hero' => 'hero-pending',  'alert' => 'alert-pending',  'icon' => 'bi-hourglass-split',        'label' => 'Verifikasi Berjalan',  'msg' => 'Dokumen sedang diverifikasi paralel oleh PPK, PPSPM, Koordinator Keuangan, Bendahara Penerimaan, dan Bendahara Pengeluaran.'],
        'PENDING_PPK'                    => ['hero' => 'hero-pending',  'alert' => 'alert-pending',  'icon' => 'bi-hourglass-split',        'label' => 'Menunggu PPK',         'msg' => 'Dokumen sedang menunggu verifikasi oleh PPK.'],
        'PENDING_PPSPM'                  => ['hero' => 'hero-pending',  'alert' => 'alert-pending',  'icon' => 'bi-hourglass-split',        'label' => 'Menunggu PPSPM',       'msg' => 'Dokumen sedang menunggu verifikasi oleh PPSPM.'],
        'PENDING_KOORDINATOR_KEUANGAN'   => ['hero' => 'hero-pending',  'alert' => 'alert-pending',  'icon' => 'bi-hourglass-split',        'label' => 'Menunggu Koordinator', 'msg' => 'Dokumen sedang menunggu verifikasi oleh Koordinator Keuangan.'],
        'PENDING_BENDAHARA'              => ['hero' => 'hero-pending',  'alert' => 'alert-pending',  'icon' => 'bi-hourglass-split',        'label' => 'Menunggu Bendahara',   'msg' => 'Menunggu verifikasi oleh Bendahara Pengeluaran.'],
        'PENDING_BENDAHARA_PENERIMAAN'   => ['hero' => 'hero-pending',  'alert' => 'alert-pending',  'icon' => 'bi-hourglass-split',        'label' => 'Menunggu Bend. Penerimaan', 'msg' => 'Menunggu verifikasi oleh Bendahara Penerimaan.'],
        'PENDING_BENDAHARA_PENGELUARAN'  => ['hero' => 'hero-pending',  'alert' => 'alert-pending',  'icon' => 'bi-hourglass-split',        'label' => 'Menunggu Bend. Pengeluaran', 'msg' => 'Menunggu verifikasi oleh Bendahara Pengeluaran.'],
        'PENDING_KASUBBAG'               => ['hero' => 'hero-info',     'alert' => 'alert-info',     'icon' => 'bi-shield-check',           'label' => 'Menunggu Kasubbag',    'msg' => 'Seluruh verifikator sudah menyetujui. Menunggu persetujuan akhir Kasubbag.'],
        'REVISI_PPK'                     => ['hero' => 'hero-warning',  'alert' => 'alert-warning',  'icon' => 'bi-arrow-counterclockwise', 'label' => 'Revisi PPK',           'msg' => 'PPK meminta perbaikan. Silakan edit data dan ajukan kembali.'],
        'REVISI_PPSPM'                   => ['hero' => 'hero-warning',  'alert' => 'alert-warning',  'icon' => 'bi-arrow-counterclockwise', 'label' => 'Revisi PPSPM',         'msg' => 'PPSPM meminta perbaikan. Silakan edit data dan ajukan kembali.'],
        'REVISI_KOORDINATOR_KEUANGAN'    => ['hero' => 'hero-warning',  'alert' => 'alert-warning',  'icon' => 'bi-arrow-counterclockwise', 'label' => 'Revisi Koordinator',   'msg' => 'Koordinator Keuangan meminta perbaikan. Silakan edit data dan ajukan kembali.'],
        'REVISI_BENDAHARA'               => ['hero' => 'hero-warning',  'alert' => 'alert-warning',  'icon' => 'bi-arrow-counterclockwise', 'label' => 'Revisi Bendahara',     'msg' => 'Bendahara meminta perbaikan. Silakan edit data dan ajukan kembali.'],
        'REVISI_BENDAHARA_PENERIMAAN'    => ['hero' => 'hero-warning',  'alert' => 'alert-warning',  'icon' => 'bi-arrow-counterclockwise', 'label' => 'Revisi Bend. Penerimaan', 'msg' => 'Bendahara Penerimaan meminta perbaikan. Silakan edit data dan ajukan kembali.'],
        'REVISI_BENDAHARA_PENGELUARAN'   => ['hero' => 'hero-warning',  'alert' => 'alert-warning',  'icon' => 'bi-arrow-counterclockwise', 'label' => 'Revisi Bend. Pengeluaran', 'msg' => 'Bendahara Pengeluaran meminta perbaikan. Silakan edit data dan ajukan kembali.'],
        'REVISI_KASUBBAG'                => ['hero' => 'hero-warning',  'alert' => 'alert-warning',  'icon' => 'bi-arrow-counterclockwise', 'label' => 'Revisi Kasubbag',      'msg' => 'Kasubbag meminta perbaikan. Silakan edit data dan ajukan kembali.'],
        'DITOLAK_PPK'                    => ['hero' => 'hero-rejected', 'alert' => 'alert-rejected', 'icon' => 'bi-x-octagon-fill',         'label' => 'Ditolak PPK',          'msg' => 'Dokumen ditolak oleh PPK. Cek catatan pada riwayat untuk detail.'],
        'DITOLAK_PPSPM'                  => ['hero' => 'hero-rejected', 'alert' => 'alert-rejected', 'icon' => 'bi-x-octagon-fill',         'label' => 'Ditolak PPSPM',        'msg' => 'Dokumen ditolak oleh PPSPM. Cek catatan pada riwayat untuk detail.'],
        'DITOLAK_KOORDINATOR_KEUANGAN'   => ['hero' => 'hero-rejected', 'alert' => 'alert-rejected', 'icon' => 'bi-x-octagon-fill',         'label' => 'Ditolak Koordinator',  'msg' => 'Dokumen ditolak oleh Koordinator Keuangan. Cek catatan pada riwayat untuk detail.'],
        'DITOLAK_BENDAHARA'              => ['hero' => 'hero-rejected', 'alert' => 'alert-rejected', 'icon' => 'bi-x-octagon-fill',         'label' => 'Ditolak Bendahara',    'msg' => 'Dokumen ditolak oleh Bendahara Pengeluaran.'],
        'DITOLAK_BENDAHARA_PENERIMAAN'   => ['hero' => 'hero-rejected', 'alert' => 'alert-rejected', 'icon' => 'bi-x-octagon-fill',         'label' => 'Ditolak Bend. Penerimaan', 'msg' => 'Dokumen ditolak oleh Bendahara Penerimaan.'],
        'DITOLAK_BENDAHARA_PENGELUARAN'  => ['hero' => 'hero-rejected', 'alert' => 'alert-rejected', 'icon' => 'bi-x-octagon-fill',         'label' => 'Ditolak Bend. Pengeluaran', 'msg' => 'Dokumen ditolak oleh Bendahara Pengeluaran.'],
        'DITOLAK_KASUBBAG'               => ['hero' => 'hero-rejected', 'alert' => 'alert-rejected', 'icon' => 'bi-x-octagon-fill',         'label' => 'Ditolak Kasubbag',     'msg' => 'Dokumen ditolak oleh Kasubbag.'],
        'DISETUJUI_PPK'                  => ['hero' => 'hero-info',     'alert' => 'alert-info',     'icon' => 'bi-check-circle-fill',      'label' => 'Disetujui PPK',        'msg' => 'Disetujui PPK. Menunggu verifikasi Bendahara Pengeluaran.'],
        'DISETUJUI_PERJALDIN'            => ['hero' => 'hero-approved', 'alert' => 'alert-approved', 'icon' => 'bi-check-circle-fill',      'label' => 'Disetujui',            'msg' => 'Verifikasi selesai. Dokumen telah diteruskan ke tahap berikutnya (Operator BLU).'],
        'MENUNGGU_UPLOAD_NOMINATIF_TTD'  => ['hero' => 'hero-warning',  'alert' => 'alert-warning',  'icon' => 'bi-cloud-upload-fill',      'label' => 'Menunggu Upload TTD',  'msg' => 'Tagihan disetujui Kasubbag. Silakan unggah Nominatif & Daftar Nominatif Pembayaran yang sudah ditandatangani.'],
        'PROSES_COA'                     => ['hero' => 'hero-info',     'alert' => 'alert-info',     'icon' => 'bi-arrow-repeat',           'label' => 'Proses COA',           'msg' => 'Sedang dalam proses penetapan COA oleh Operator BLU.'],
        'PROSES_SPP'                     => ['hero' => 'hero-info',     'alert' => 'alert-info',     'icon' => 'bi-arrow-repeat',           'label' => 'Proses SPP',           'msg' => 'Dokumen sedang dalam proses pembuatan SPP.'],
        'SEBAGIAN_SPP_TERBIT'            => ['hero' => 'hero-approved', 'alert' => 'alert-approved', 'icon' => 'bi-file-earmark-check',     'label' => 'Sebagian SPP Terbit',  'msg' => 'Sebagian SPP sudah diterbitkan untuk dokumen ini.'],
        'SPP_LENGKAP'                    => ['hero' => 'hero-approved', 'alert' => 'alert-approved', 'icon' => 'bi-file-earmark-check',     'label' => 'SPP Lengkap',          'msg' => 'Seluruh SPP telah diterbitkan untuk dokumen ini.'],
    ];

    $defaultStatusInfo = ['hero' => 'hero-pending', 'alert' => 'alert-pending', 'icon' => 'bi-info-circle-fill', 'label' => str_replace('_', ' ', $status), 'msg' => 'Dokumen sedang dalam proses workflow.'];
    $statusInfo = $statusInfoMap[$status] ?? $defaultStatusInfo;

    $canEdit = in_array($status, ['DRAFT', 'REVISI_PPK', 'REVISI_PPSPM', 'REVISI_KOORDINATOR_KEUANGAN', 'REVISI_BENDAHARA', 'REVISI_BENDAHARA_PENERIMAAN', 'REVISI_BENDAHARA_PENGELUARAN', 'REVISI_KASUBBAG', 'DITOLAK_PPK', 'DITOLAK_PPSPM', 'DITOLAK_KOORDINATOR_KEUANGAN', 'DITOLAK_BENDAHARA_PENERIMAAN', 'DITOLAK_BENDAHARA_PENGELUARAN', 'DITOLAK_KASUBBAG']);
    $canSubmit = $canEdit;
    $isApprovedPerjaldin = in_array($status, ['DISETUJUI_PERJALDIN', 'PROSES_COA', 'PROSES_SPP', 'SEBAGIAN_SPP_TERBIT', 'SPP_LENGKAP']);
    $isOperatorPerjaldin = auth()->user()->hasRole('Operator Perjaldin');
    $isOperatorBlu = auth()->user()->hasRole('Operator BLU');

    $waitingNominatifTtd = $status === 'MENUNGGU_UPLOAD_NOMINATIF_TTD';
    $arsipNominatif = $tagihan->arsipDokumen()
        ->whereIn('jenis_dokumen', ['NOMINATIF_PERJALDIN_TTD', 'DAFTAR_NOMINATIF_PEMBAYARAN_PERJALDIN_TTD'])
        ->where('is_active', true)
        ->get()
        ->keyBy('jenis_dokumen');
    $hasNominatif = isset($arsipNominatif['NOMINATIF_PERJALDIN_TTD']);
    $hasDaftarNominatif = isset($arsipNominatif['DAFTAR_NOMINATIF_PEMBAYARAN_PERJALDIN_TTD']);
    $showUploadCard = $waitingNominatifTtd || $hasNominatif || $hasDaftarNominatif;

    $jumlahPeserta = $tagihan->detailPerjaldin->count();
    $jumlahKomponen = $tagihan->komponenPerjaldin?->where('total_nominal', '>', 0)->count() ?? 0;
    $jumlahLogs = $tagihan->logs?->count() ?? 0;

    // Komponen ikon mapping (untuk tab COA)
    $komponenIconMap = [
        'TIKET'              => 'bi-ticket-detailed-fill',
        'TRANSPORT'          => 'bi-car-front-fill',
        'PENGINAPAN'         => 'bi-buildings-fill',
        'UANG_HARIAN'        => 'bi-cash-coin',
        'UANG_REPRESENTASI'  => 'bi-person-badge',
    ];
    $komponenColorMap = [
        'TIKET'              => ['accent' => '#6366f1', 'soft' => 'rgba(99,102,241,.10)'],
        'TRANSPORT'          => ['accent' => '#0ea5e9', 'soft' => 'rgba(14,165,233,.10)'],
        'PENGINAPAN'         => ['accent' => '#f59e0b', 'soft' => 'rgba(245,158,11,.10)'],
        'UANG_HARIAN'        => ['accent' => '#10b981', 'soft' => 'rgba(16,185,129,.10)'],
        'UANG_REPRESENTASI'  => ['accent' => '#ec4899', 'soft' => 'rgba(236,72,153,.10)'],
    ];
@endphp

{{-- FLASH MESSAGES --}}
@if(session('success'))
    <div class="status-alert alert-approved" role="alert">
        <div class="sa-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div class="flex-grow-1">
            <h6>Berhasil</h6>
            <p>{{ session('success') }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="status-alert alert-rejected" role="alert">
        <div class="sa-icon"><i class="bi bi-x-circle-fill"></i></div>
        <div class="flex-grow-1">
            <h6>Terjadi Kesalahan</h6>
            <p>{{ session('error') }}</p>
        </div>
    </div>
@endif

{{-- ═══ HERO HEADER ═══ --}}
<div class="perj-detail-hero {{ $statusInfo['hero'] }}">
    <i class="bi bi-airplane-engines plane-illust d-none d-md-block"></i>
    <div class="row align-items-start g-3">
        <div class="col-md-7">
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="hero-status-pill"><i class="bi {{ $statusInfo['icon'] }}"></i> {{ $statusInfo['label'] }}</span>
                <span class="hero-status-pill" style="opacity:.85;">
                    <i class="bi bi-airplane"></i> Perjalanan Dinas
                </span>
            </div>
            <h2 class="hero-doc-no">
                <i class="bi bi-hash"></i>{{ $tagihan->nomor_tagihan ?? '—' }}
            </h2>
            <p>{{ $tagihan->deskripsi ?? 'Tanpa Judul' }}</p>
            <div class="hero-meta">
                <span class="meta-item"><i class="bi bi-people-fill"></i> {{ $jumlahPeserta }} peserta</span>
                @if($tagihan->periode_bulan && $tagihan->periode_tahun)
                    <span class="meta-item"><i class="bi bi-calendar3"></i> {{ \Carbon\Carbon::createFromDate($tagihan->periode_tahun, $tagihan->periode_bulan, 1)->translatedFormat('F Y') }}</span>
                @endif
                @if($tagihan->created_at)
                    <span class="meta-item"><i class="bi bi-calendar-plus"></i> Dibuat {{ $tagihan->created_at->isoFormat('D MMM YYYY') }}</span>
                @endif
                @if($tagihan->updated_at)
                    <span class="meta-item"><i class="bi bi-clock-history"></i> Update {{ $tagihan->updated_at->isoFormat('D MMM YYYY HH:mm') }}</span>
                @endif
            </div>
        </div>
        <div class="col-md-5">
            <div class="hero-total-card">
                <p class="label"><i class="bi bi-cash-stack me-1"></i> Total Bruto</p>
                <div class="value">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</div>
                @if($jumlahPeserta > 0)
                    <div class="sub">Rata-rata Rp {{ number_format($tagihan->total_bruto / $jumlahPeserta, 0, ',', '.') }} / peserta</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ═══ STATUS ALERT ═══ --}}
<div class="status-alert {{ $statusInfo['alert'] }}">
    <div class="sa-icon"><i class="bi {{ $statusInfo['icon'] }}"></i></div>
    <div>
        <h6>Status: {{ $statusInfo['label'] }}</h6>
        <p>{{ $statusInfo['msg'] }}</p>
    </div>
</div>

{{-- ═══ ACTION BAR ═══ --}}
<div class="action-bar">
    <a href="{{ route('perjaldins.index') }}" class="btn-act">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>

    <a href="{{ route('perjaldins.pdf-nominatif', $tagihan->id) }}" target="_blank" class="btn-act btn-act-pdf">
        <i class="bi bi-file-earmark-pdf-fill"></i> PDF Nominatif
    </a>

    <a href="{{ route('perjaldins.pdf-lampiran', $tagihan->id) }}" target="_blank" class="btn-act btn-act-pdf">
        <i class="bi bi-file-earmark-spreadsheet-fill"></i> PDF Daftar Pembayaran
    </a>

    @if($isOperatorPerjaldin && $canEdit)
        <a href="{{ route('perjaldins.edit-perjaldin', $tagihan->id) }}" class="btn-act">
            <i class="bi bi-pencil-square"></i> Edit Dokumen
        </a>
    @endif

    @if($isOperatorPerjaldin && $canSubmit)
        <form action="{{ route('perjaldin.workflow.submit', $tagihan->id) }}" method="POST"
              onsubmit="return confirm('Ajukan dokumen Perjaldin ke PPK, PPSPM, Koordinator Keuangan, Bendahara Penerimaan, dan Bendahara Pengeluaran?')"
              class="m-0">
            @csrf
            <button type="submit" class="btn-act btn-act-primary">
                <i class="bi bi-send-check-fill"></i> Ajukan Perjaldin
            </button>
        </form>
    @endif

    @if($isApprovedPerjaldin)
        <span class="badge-success-pill ms-auto">
            <i class="bi bi-check-circle-fill"></i> Diteruskan ke Operator BLU
        </span>
    @endif

    @if($status === 'MENUNGGU_UPLOAD_NOMINATIF_TTD')
        <span class="badge-warning-pill ms-auto">
            <i class="bi bi-cloud-upload-fill"></i> Menunggu Upload TTD
        </span>
    @endif
</div>

{{-- ═══ TABS NAVIGATION DIHILANGKAN — semua section ditampilkan langsung ═══ --}}

{{-- ═══ INFORMASI DOKUMEN ═══ --}}
<div class="standalone-section section-info">
    @include('perjaldins.partials.detail-info', ['tagihan' => $tagihan])
</div>

{{-- ═══ INFORMASI VERIFIKATOR ═══ --}}
<div class="standalone-section section-verifikator">
    @include('perjaldins.partials.verifikator-info', ['tagihan' => $tagihan])
</div>

{{-- ═══ PROGRESS WORKFLOW ═══ --}}
<div class="standalone-section section-workflow">
    @include('perjaldins.partials.workflow-progress', ['tagihan' => $tagihan])
</div>

{{-- ═══ DAFTAR PESERTA ═══ --}}
<div class="standalone-section section-peserta">
    @include('perjaldins.partials.peserta-list', ['tagihan' => $tagihan])
</div>

{{-- ═══ UPLOAD NOMINATIF (ALWAYS VISIBLE WHEN APPLICABLE) ═══ --}}
@if($showUploadCard)
<div class="upload-nominatif-card {{ $waitingNominatifTtd ? 'is-pending' : '' }} {{ $hasNominatif && $hasDaftarNominatif ? 'is-done' : '' }}">
    <div class="un-head">
        <div class="un-icon"><i class="bi bi-cloud-upload-fill"></i></div>
        <div>
            <h6 class="fw-bold mb-1">Upload Nominatif Bertandatangan</h6>
            <small class="text-muted">Wajib diunggah oleh Operator Perjaldin setelah Kasubbag menyetujui tagihan.</small>
        </div>
    </div>

    @if($waitingNominatifTtd)
        <div class="status-alert alert-warning mb-3">
            <div class="sa-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div>
                <h6>Menunggu unggahan dokumen</h6>
                <p>Tagihan telah disetujui Kasubbag. Silakan unggah Nominatif Perjalanan Dinas dan Daftar Nominatif Pembayaran yang sudah ditandatangani. Setelah keduanya lengkap, Operator BLU akan menerima notifikasi untuk membuat SPP.</p>
            </div>
        </div>
    @elseif($status === 'DISETUJUI_PERJALDIN' || $isApprovedPerjaldin)
        <div class="status-alert alert-approved mb-3">
            <div class="sa-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <h6>Dokumen lengkap</h6>
                <p>Kedua dokumen bertandatangan sudah lengkap. Tagihan sudah diteruskan ke Operator BLU untuk dibuatkan SPP.</p>
            </div>
        </div>
    @endif

    <div class="row g-3">
        @php
            $slots = [
                ['jenis' => 'NOMINATIF_PERJALDIN_TTD',                      'label' => 'Nominatif Perjalanan Dinas (TTD)', 'desc' => 'A4 portrait — sumber dari tombol "PDF Nominatif".'],
                ['jenis' => 'DAFTAR_NOMINATIF_PEMBAYARAN_PERJALDIN_TTD',    'label' => 'Daftar Nominatif Pembayaran (TTD)', 'desc' => 'A4 landscape — sumber dari tombol "PDF Daftar Pembayaran".'],
            ];
            $canUploadNominatif = $isOperatorPerjaldin && in_array($status, ['MENUNGGU_UPLOAD_NOMINATIF_TTD', 'DISETUJUI_PERJALDIN'], true);
        @endphp
        @foreach($slots as $slot)
            @php $arsip = $arsipNominatif[$slot['jenis']] ?? null; @endphp
            <div class="col-md-6">
                <div class="upload-slot {{ $arsip ? 'is-uploaded' : '' }}">
                    <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                        <div>
                            <div class="fw-bold text-dark">{{ $slot['label'] }}</div>
                            <small class="text-muted">{{ $slot['desc'] }}</small>
                        </div>
                        @if($arsip)
                            <span class="badge-success-pill" style="font-size:.7rem; padding: .25rem .55rem;"><i class="bi bi-check-circle-fill"></i> Tersedia</span>
                        @else
                            <span class="badge-warning-pill" style="font-size:.7rem; padding: .25rem .55rem;"><i class="bi bi-clock-history"></i> Belum diunggah</span>
                        @endif
                    </div>

                    @if($arsip)
                        <div class="d-flex gap-2 align-items-center small mb-2">
                            <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                            <a href="{{ route('perjaldins.view-nominatif-ttd', [$tagihan->id, $arsip->id]) }}" target="_blank" class="text-decoration-none fw-semibold text-primary">
                                {{ $arsip->nama_file_asli }}
                            </a>
                        </div>
                    @endif

                    @if($canUploadNominatif)
                        <form action="{{ route('perjaldins.upload-nominatif-ttd', $tagihan->id) }}" method="POST" enctype="multipart/form-data" class="mt-2">
                            @csrf
                            <input type="hidden" name="jenis_dokumen" value="{{ $slot['jenis'] }}">
                            <div class="input-group input-group-sm">
                                <input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                <button type="submit" class="btn-act btn-act-warning" style="border-radius: 0 .55rem .55rem 0;">
                                    <i class="bi bi-upload"></i> {{ $arsip ? 'Ganti' : 'Unggah' }}
                                </button>
                            </div>
                            <div class="form-text small">PDF / JPG / PNG, maksimal 10MB.</div>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- ═══ RIWAYAT (AUDIT TRAIL) ═══ --}}
<div class="standalone-section section-riwayat">
    @include('perjaldins.partials.audit-timeline', ['tagihan' => $tagihan])
</div>

@endsection

@push('script')
<script>
(function () {
    // Animasi reveal saat scroll (intersection observer)
    const sections = document.querySelectorAll('.standalone-section, .coa-section, .upload-nominatif-card');
    if (!('IntersectionObserver' in window)) {
        sections.forEach(s => s.classList.add('is-visible'));
        return;
    }
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                obs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08 });
    sections.forEach(s => obs.observe(s));
})();
</script>
@endpush
