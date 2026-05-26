@extends('layouts.app')

@section('title', 'Detail Honorarium')

@push('css')
<style>
    body[data-bs-theme="blue-theme"] .main-content { background: #f6f7fb; }

    /* ============================================================
       HERO HEADER (status-aware)
       ============================================================ */
    .detail-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.25rem;
        padding: 1.5rem 2rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 14px 30px rgba(15,23,42,.18);
        animation: fadeSlideDown .55s cubic-bezier(.22,1,.36,1) both;
    }
    .detail-hero::before {
        content: '';
        position: absolute;
        right: -90px; top: -90px;
        width: 280px; height: 280px;
        border-radius: 50%;
        background: rgba(255,255,255,.10);
    }
    .detail-hero::after {
        content: '';
        position: absolute;
        right: 60px; bottom: -70px;
        width: 180px; height: 180px;
        border-radius: 50%;
        background: rgba(255,255,255,.07);
    }
    .detail-hero > * { position: relative; z-index: 1; }
    .hero-draft     { background: linear-gradient(135deg, #475569 0%, #334155 100%); }
    .hero-pending   { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #2563eb 100%); }
    .hero-approved  { background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%); }
    .hero-rejected  { background: linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%); }
    .hero-info      { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 50%, #0369a1 100%); }

    .hero-doc-no {
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: -.01em;
        color: #fff !important;
        margin: 0 0 .35rem;
    }
    .hero-status-pill {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        background: rgba(255,255,255,.22);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.28);
        font-weight: 700;
        font-size: .78rem;
        padding: .4rem .9rem;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: .04em;
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

    .hero-meta {
        display: flex;
        gap: 1rem 2rem;
        flex-wrap: wrap;
        margin-top: 1rem;
        font-size: .8rem;
    }
    .hero-meta .meta-item {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        opacity: .92;
    }
    .hero-meta .meta-item i { font-size: .9rem; }

    .hero-actions {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
    }
    .btn-hero {
        background: rgba(255,255,255,.18);
        border: 1px solid rgba(255,255,255,.30);
        color: #fff;
        font-weight: 600;
        padding: .55rem 1.05rem;
        border-radius: 999px;
        font-size: .82rem;
        transition: all .2s ease;
        backdrop-filter: blur(8px);
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
        color: #4f46e5;
        font-weight: 700;
    }
    .btn-hero-primary:hover {
        background: #fff;
        color: #4338ca;
        box-shadow: 0 6px 14px rgba(0,0,0,.15);
    }

    /* ============================================================
       STATUS ALERT (below hero)
       ============================================================ */
    .status-alert {
        display: flex;
        align-items: flex-start;
        gap: .85rem;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
        border: 1px solid;
        animation: fadeSlideUp .55s cubic-bezier(.22,1,.36,1) .12s both;
    }
    .status-alert .sa-icon {
        width: 42px; height: 42px;
        flex-shrink: 0;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    .status-alert.alert-draft     { background: rgba(100,116,139,.06); border-color: rgba(100,116,139,.20); color: #475569; }
    .status-alert.alert-draft .sa-icon { background: rgba(100,116,139,.15); color: #475569; }
    .status-alert.alert-pending   { background: rgba(99,102,241,.06); border-color: rgba(99,102,241,.20); color: #4338ca; }
    .status-alert.alert-pending .sa-icon { background: rgba(99,102,241,.15); color: #4f46e5; }
    .status-alert.alert-approved  { background: rgba(16,185,129,.06); border-color: rgba(16,185,129,.20); color: #047857; }
    .status-alert.alert-approved .sa-icon { background: rgba(16,185,129,.15); color: #047857; }
    .status-alert.alert-rejected  { background: rgba(245,158,11,.08); border-color: rgba(245,158,11,.25); color: #92400e; }
    .status-alert.alert-rejected .sa-icon { background: rgba(245,158,11,.18); color: #b45309; }
    .status-alert h6 { margin: 0 0 .25rem; font-weight: 800; font-size: .95rem; }
    .status-alert p { margin: 0; font-size: .85rem; }

    /* ============================================================
       KPI STAT CARDS
       ============================================================ */
    .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.25rem; }
    @media (max-width: 991px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 575px) { .stat-grid { grid-template-columns: 1fr; } }

    .stat-card-d {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        padding: 1.1rem 1.2rem;
        position: relative;
        overflow: hidden;
        transition: all .25s ease;
        animation: fadeSlideUp .55s cubic-bezier(.22,1,.36,1) both;
    }
    .stat-card-d:nth-child(1) { animation-delay: .15s; }
    .stat-card-d:nth-child(2) { animation-delay: .22s; }
    .stat-card-d:nth-child(3) { animation-delay: .29s; }
    .stat-card-d:nth-child(4) { animation-delay: .36s; }
    .stat-card-d::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 3px;
        background: var(--sc-accent, #6366f1);
    }
    .stat-card-d:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 28px rgba(15,23,42,.08);
    }
    .stat-card-d .sc-icon {
        width: 42px; height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        background: var(--sc-bg, rgba(99,102,241,.10));
        color: var(--sc-accent, #6366f1);
        margin-bottom: .65rem;
    }
    .stat-card-d .sc-label {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        margin: 0 0 .15rem;
    }
    .stat-card-d .sc-value {
        font-size: 1.45rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        line-height: 1.1;
        font-variant-numeric: tabular-nums;
    }
    .stat-card-d .sc-foot {
        font-size: .75rem;
        color: #94a3b8;
        margin-top: .35rem;
    }
    .sc-primary { --sc-accent: #6366f1; --sc-bg: rgba(99,102,241,.10); }
    .sc-danger  { --sc-accent: #dc2626; --sc-bg: rgba(220,38,38,.10); }
    .sc-success { --sc-accent: #059669; --sc-bg: rgba(5,150,105,.10); }
    .sc-info    { --sc-accent: #0ea5e9; --sc-bg: rgba(14,165,233,.10); }

    /* ============================================================
       PANEL (info card)
       ============================================================ */
    .panel-d {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        margin-bottom: 1.25rem;
        overflow: hidden;
        animation: fadeSlideUp .55s cubic-bezier(.22,1,.36,1) .25s both;
    }
    .panel-d-head {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #f1f3f7;
        background: linear-gradient(180deg, #fafbff 0%, #ffffff 100%);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .panel-d-head h6 {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
        display: inline-flex;
        align-items: center;
        gap: .55rem;
        letter-spacing: -.01em;
    }
    .panel-d-head h6 i {
        width: 30px; height: 30px;
        border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(99,102,241,.12);
        color: #4f46e5;
        font-size: 1rem;
    }
    .panel-d-body { padding: 1.25rem 1.5rem; }

    .info-list {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem 2rem;
    }
    @media (max-width: 767px) { .info-list { grid-template-columns: 1fr; } }
    .info-item .info-label {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
        margin-bottom: .35rem;
    }
    .info-item .info-value {
        font-size: .9rem;
        color: #0f172a;
        font-weight: 500;
        line-height: 1.45;
    }

    /* ============================================================
       DIPA SOURCE PANEL
       ============================================================ */
    .dipa-header {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 1.25rem;
        align-items: start;
        padding: 1rem 1.15rem;
        background: linear-gradient(135deg, rgba(99,102,241,.06), rgba(14,165,233,.04));
        border: 1px solid rgba(99,102,241,.18);
        border-radius: .85rem;
        margin-bottom: 1rem;
    }
    @media (max-width: 575px) {
        .dipa-header { grid-template-columns: 1fr; }
    }
    .dipa-doc-no {
        font-weight: 800;
        color: #4338ca;
        font-size: 1.1rem;
        margin-top: .2rem;
        display: inline-flex;
        align-items: center;
        gap: .45rem;
    }
    .mak-chip {
        display: inline-flex;
        align-items: center;
        background: #fff;
        border: 1px solid #c7d2fe;
        color: #4338ca;
        padding: .55rem .95rem;
        border-radius: .65rem;
        font-family: ui-monospace, "SF Mono", monospace;
        font-size: .85rem;
        font-weight: 700;
        letter-spacing: .04em;
        margin-top: .25rem;
        box-shadow: 0 4px 10px rgba(99,102,241,.10);
    }

    .coa-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: .55rem;
        margin-bottom: .75rem;
    }
    .coa-cell {
        background: #f8fafc;
        border: 1px solid #eef0f4;
        border-radius: .65rem;
        padding: .55rem .75rem;
        transition: all .2s ease;
    }
    .coa-cell:hover {
        background: #fff;
        border-color: #c7d2fe;
        transform: translateY(-1px);
    }
    .coa-cell-label {
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
    }
    .coa-cell-value {
        font-family: ui-monospace, "SF Mono", monospace;
        font-size: .9rem;
        font-weight: 700;
        color: #1e293b;
        margin-top: .15rem;
    }

    .coa-name-card {
        background: #fafbff;
        border: 1px solid #eef0f4;
        border-left: 4px solid #6366f1;
        border-radius: .65rem;
        padding: .85rem 1rem;
    }
    .coa-name-value {
        font-size: .95rem;
        font-weight: 700;
        color: #1e293b;
        margin-top: .15rem;
    }

    .dipa-stat-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: .85rem;
        margin: 1.25rem 0 .85rem;
    }
    @media (max-width: 575px) {
        .dipa-stat-grid { grid-template-columns: 1fr; }
    }
    .dipa-stat {
        padding: 1rem 1.1rem;
        border-radius: .85rem;
        position: relative;
        overflow: hidden;
        transition: transform .2s ease;
    }
    .dipa-stat:hover { transform: translateY(-2px); }
    .dipa-stat-pagu {
        background: linear-gradient(135deg, rgba(99,102,241,.08), rgba(99,102,241,.02));
        border: 1px solid rgba(99,102,241,.20);
    }
    .dipa-stat-real {
        background: linear-gradient(135deg, rgba(14,165,233,.08), rgba(14,165,233,.02));
        border: 1px solid rgba(14,165,233,.20);
    }
    .dipa-stat-sisa {
        background: linear-gradient(135deg, rgba(16,185,129,.08), rgba(16,185,129,.02));
        border: 1px solid rgba(16,185,129,.20);
    }
    .dipa-stat-label {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        margin-bottom: .35rem;
    }
    .dipa-stat-pagu .dipa-stat-label { color: #4338ca; }
    .dipa-stat-real .dipa-stat-label { color: #0369a1; }
    .dipa-stat-sisa .dipa-stat-label { color: #047857; }
    .dipa-stat-value {
        font-size: 1.1rem;
        font-weight: 800;
        color: #0f172a;
        font-variant-numeric: tabular-nums;
        line-height: 1.2;
    }
    .dipa-stat-foot {
        font-size: .72rem;
        color: #64748b;
        margin-top: .15rem;
    }

    .dipa-progress-wrap { margin-top: 1rem; }
    .dipa-progress-bar {
        height: 10px;
        background: #f1f5f9;
        border-radius: 999px;
        overflow: hidden;
        margin-bottom: .55rem;
    }
    .dipa-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899);
        background-size: 200% 100%;
        border-radius: 999px;
        transition: width .8s cubic-bezier(.22,1,.36,1);
        animation: shimmer 3s linear infinite;
        box-shadow: 0 2px 6px rgba(99,102,241,.30);
    }
    .dipa-progress-meta {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        font-size: .78rem;
        color: #64748b;
    }
    .dipa-progress-meta i { color: #6366f1; }
    .dipa-tagihan-share { font-style: italic; }
    .tabs-bar {
        display: flex;
        gap: .35rem;
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        padding: .35rem;
        margin-bottom: 1rem;
        overflow-x: auto;
        scrollbar-width: thin;
        animation: fadeSlideUp .55s cubic-bezier(.22,1,.36,1) .3s both;
    }
    .tabs-bar .tab-btn {
        flex: 1 1 auto;
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
    .tabs-bar .tab-btn:hover {
        color: #1e293b;
        background: #f8fafc;
    }
    .tabs-bar .tab-btn.active {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        box-shadow: 0 6px 14px rgba(99,102,241,.30);
    }
    .tabs-bar .tab-btn .tab-count {
        background: rgba(255,255,255,.25);
        padding: .1rem .45rem;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 700;
    }
    .tabs-bar .tab-btn:not(.active) .tab-count {
        background: rgba(99,102,241,.12);
        color: #4f46e5;
    }

    .tab-pane-d { display: none; animation: fadeSlideUp .35s cubic-bezier(.22,1,.36,1) both; }
    .tab-pane-d.active { display: block; }

    /* ============================================================
       RECIPIENT TABLE
       ============================================================ */
    .table-recipients {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }
    .table-recipients thead th {
        background: #f8fafc;
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #64748b;
        padding: .85rem 1rem;
        border-top: 1px solid #eef0f4;
        border-bottom: 1px solid #eef0f4;
        white-space: nowrap;
    }
    .table-recipients thead th:first-child { padding-left: 1.5rem; }
    .table-recipients thead th:last-child  { padding-right: 1.5rem; }
    .table-recipients tbody td {
        padding: 1rem;
        border-bottom: 1px solid #f1f3f7;
        font-size: .87rem;
        vertical-align: middle;
        background: #fff;
        transition: background .15s ease;
    }
    .table-recipients tbody td:first-child { padding-left: 1.5rem; }
    .table-recipients tbody td:last-child  { padding-right: 1.5rem; }
    .table-recipients tbody tr:hover td { background: #fafbff; }
    .table-recipients tbody tr:last-child td { border-bottom: 0; }

    .recipient-avatar {
        width: 40px; height: 40px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #a5b4fc, #6366f1);
        color: #fff;
        font-weight: 700;
        font-size: .85rem;
        flex-shrink: 0;
    }
    .recipient-avatar.av-2 { background: linear-gradient(135deg, #fda4af, #f43f5e); }
    .recipient-avatar.av-3 { background: linear-gradient(135deg, #6ee7b7, #10b981); }
    .recipient-avatar.av-4 { background: linear-gradient(135deg, #fcd34d, #f59e0b); }
    .recipient-avatar.av-5 { background: linear-gradient(135deg, #93c5fd, #3b82f6); }

    .recipient-name { font-weight: 700; color: #1e293b; }
    .recipient-meta { font-size: .76rem; color: #64748b; margin-top: .15rem; }

    .bank-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        background: rgba(99,102,241,.08);
        color: #4338ca;
        padding: .15rem .55rem;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .2rem;
    }

    .table-recipients tfoot td {
        background: linear-gradient(180deg, #fafbff 0%, #f1f5f9 100%);
        padding: 1rem;
        font-weight: 700;
        font-size: .87rem;
        border-top: 2px solid #e2e8f0;
    }
    .table-recipients tfoot td:first-child { padding-left: 1.5rem; }
    .table-recipients tfoot td:last-child  { padding-right: 1.5rem; }

    .money-pos { color: #059669; font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .money-neg { color: #dc2626; font-weight: 600; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .money     { color: #1e293b; font-weight: 600; font-variant-numeric: tabular-nums; white-space: nowrap; }

    /* ============================================================
       VERIFIKATOR FLOW
       ============================================================ */
    .flow-progress-bar {
        height: 8px;
        background: #f1f5f9;
        border-radius: 999px;
        overflow: hidden;
        margin-bottom: 1.25rem;
    }
    .flow-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899);
        background-size: 200% 100%;
        border-radius: 999px;
        transition: width .6s cubic-bezier(.22,1,.36,1);
        animation: shimmer 3s linear infinite;
    }
    @keyframes shimmer {
        0%   { background-position: 0% 0; }
        100% { background-position: 200% 0; }
    }

    .verif-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem;
    }

    .verif-card {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        padding: 1rem 1.1rem;
        position: relative;
        transition: all .25s ease;
    }
    .verif-card::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 3px;
        border-top-left-radius: 1rem;
        border-top-right-radius: 1rem;
        background: var(--vc-color, #cbd5e1);
        transition: all .25s ease;
    }
    .verif-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 28px rgba(15,23,42,.08);
    }
    .verif-card.is-empty {
        background: linear-gradient(180deg, #fafbff 0%, #f1f5f9 100%);
        border-style: dashed;
    }
    .verif-card.is-approved::before { background: linear-gradient(90deg, #34d399, #10b981); height: 4px; }
    .verif-card.is-pending::before  { background: linear-gradient(90deg, #fbbf24, #f59e0b); height: 4px; }
    .verif-card.is-revision::before,
    .verif-card.is-rejected::before { background: linear-gradient(90deg, #fb7185, #ef4444); height: 4px; }

    .verif-step {
        position: absolute;
        top: -10px; left: 16px;
        width: 28px; height: 28px;
        border-radius: 50%;
        background: #fff;
        color: var(--vc-color, #4f46e5);
        font-size: .78rem;
        font-weight: 800;
        display: flex; align-items: center; justify-content: center;
        z-index: 2;
        border: 2px solid var(--vc-color, #4f46e5);
        box-shadow: 0 4px 10px rgba(15,23,42,.10);
    }

    .verif-status-pill {
        position: absolute;
        top: -10px; right: 12px;
        font-size: .65rem;
        font-weight: 700;
        padding: .15rem .55rem;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: .06em;
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        box-shadow: 0 3px 8px rgba(15,23,42,.10);
    }
    .pill-approved { background: linear-gradient(135deg, #34d399, #10b981); color: #fff; }
    .pill-pending  { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #fff; }
    .pill-revision,
    .pill-rejected { background: linear-gradient(135deg, #fb7185, #ef4444); color: #fff; }
    .pill-waiting  { background: #f1f5f9; color: #64748b; }

    .verif-avatar {
        width: 48px; height: 48px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: 1rem;
        flex-shrink: 0;
        background: var(--vc-color, #6366f1);
        box-shadow: 0 6px 14px var(--vc-color-shadow, rgba(99,102,241,.30));
    }
    .verif-avatar.empty {
        background: #e2e8f0;
        color: #94a3b8;
        box-shadow: none;
    }

    .role-chip {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        font-size: .65rem;
        font-weight: 700;
        padding: .15rem .55rem;
        border-radius: 999px;
        letter-spacing: .04em;
        text-transform: uppercase;
        background: var(--vc-soft-bg, #eef2ff);
        color: var(--vc-color, #4338ca);
    }

    .verif-card .verif-name {
        font-weight: 700;
        color: #1e293b;
        font-size: .9rem;
        margin-top: .2rem;
        margin-bottom: .15rem;
    }
    .verif-card .verif-empty-name {
        font-style: italic;
        color: #94a3b8;
        font-weight: 500;
    }
    .verif-card .verif-nip {
        font-size: .73rem;
        color: #64748b;
        font-family: ui-monospace, "SF Mono", monospace;
    }
    .verif-card .verif-role {
        font-size: .76rem;
        color: #64748b;
        margin-top: .35rem;
    }
    .verif-card .verif-acted {
        font-size: .72rem;
        color: #94a3b8;
        margin-top: .55rem;
        display: flex;
        align-items: center;
        gap: .35rem;
    }
    .verif-card .verif-note {
        margin-top: .55rem;
        padding: .55rem .65rem;
        background: rgba(244,63,94,.06);
        border-left: 3px solid #f43f5e;
        border-radius: .5rem;
        font-size: .76rem;
        color: #991b1b;
        font-style: italic;
    }

    /* ============================================================
       UPLOAD ZONE
       ============================================================ */
    .upload-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    @media (max-width: 767px) { .upload-grid { grid-template-columns: 1fr; } }

    .upload-zone {
        border: 2px dashed #e2e8f0;
        border-radius: .85rem;
        padding: 1.4rem 1.25rem;
        background: #fafbff;
        transition: all .25s ease;
        position: relative;
        cursor: pointer;
        text-align: center;
    }
    .upload-zone:hover {
        border-color: #6366f1;
        background: rgba(99,102,241,.04);
    }
    .upload-zone.has-file {
        border-color: #10b981;
        background: rgba(16,185,129,.04);
    }
    .upload-zone .uz-label {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #6366f1;
        margin-bottom: .35rem;
    }
    .upload-zone .uz-icon {
        width: 50px; height: 50px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(99,102,241,.10);
        color: #4f46e5;
        font-size: 1.4rem;
        margin-bottom: .55rem;
    }
    .upload-zone.has-file .uz-icon {
        background: rgba(16,185,129,.15);
        color: #047857;
    }
    .upload-zone .uz-title {
        font-weight: 700;
        color: #1e293b;
        font-size: .85rem;
        margin-bottom: .15rem;
    }
    .upload-zone .uz-sub {
        font-size: .72rem;
        color: #64748b;
    }
    .upload-zone input[type="file"] {
        position: absolute; inset: 0;
        opacity: 0;
        cursor: pointer;
    }
    .upload-zone.is-done {
        border-style: solid;
        border-color: #10b981;
        background: rgba(16,185,129,.07);
        cursor: default;
    }
    .upload-zone.is-done .uz-title {
        color: #047857;
    }

    /* ============================================================
       DOCUMENT LIST
       ============================================================ */
    .doc-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: .75rem;
    }
    .doc-item {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: .85rem;
        padding: .85rem 1rem;
        display: flex;
        align-items: center;
        gap: .85rem;
        transition: all .2s ease;
    }
    .doc-item:hover {
        border-color: #c7d2fe;
        box-shadow: 0 8px 18px rgba(99,102,241,.10);
        transform: translateY(-2px);
    }
    .doc-item .doc-icon {
        width: 42px; height: 42px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(244,63,94,.15), rgba(220,38,38,.10));
        color: #b91c1c;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .doc-item .doc-info { flex: 1; min-width: 0; }
    .doc-item .doc-type {
        font-size: .72rem;
        font-weight: 700;
        color: #4f46e5;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .doc-item .doc-name {
        font-size: .85rem;
        font-weight: 600;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .doc-item .doc-time {
        font-size: .7rem;
        color: #94a3b8;
        margin-top: .15rem;
    }
    .doc-item .doc-actions {
        display: flex;
        gap: .35rem;
    }
    .doc-action-btn {
        width: 32px; height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        border: 0;
        color: #475569;
        font-size: .85rem;
        text-decoration: none;
        transition: all .15s ease;
        cursor: pointer;
    }
    .doc-action-btn:hover { transform: translateY(-1px); }
    .doc-action-btn.btn-dl:hover     { background: #1d4ed8; color: #fff; }
    .doc-action-btn.btn-rm:hover     { background: #dc2626; color: #fff; }

    /* ============================================================
       SUBMIT BAR
       ============================================================ */
    .submit-zone {
        background: linear-gradient(135deg, rgba(99,102,241,.06), rgba(139,92,246,.04));
        border: 1px solid rgba(99,102,241,.20);
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        margin-top: 1.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .submit-zone-global {
        background: rgba(255,255,255,.95);
        backdrop-filter: blur(10px);
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        box-shadow: 0 12px 28px rgba(15,23,42,.10);
        position: sticky;
        bottom: 1rem;
        z-index: 30;
        animation: fadeSlideUp .55s cubic-bezier(.22,1,.36,1) .35s both;
    }
    .submit-status-icon {
        width: 52px; height: 52px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
        transition: all .3s ease;
    }
    .submit-status-icon.is-ready {
        background: linear-gradient(135deg, #34d399, #10b981);
        color: #fff;
        box-shadow: 0 8px 18px rgba(16,185,129,.35);
        animation: pulseReady 1.8s ease-in-out infinite;
    }
    .submit-status-icon.is-pending {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: #fff;
        box-shadow: 0 8px 18px rgba(245,158,11,.35);
    }
    @keyframes pulseReady {
        0%, 100% { box-shadow: 0 0 0 0 rgba(16,185,129,.35), 0 8px 18px rgba(16,185,129,.35); }
        50%      { box-shadow: 0 0 0 10px rgba(16,185,129,0), 0 8px 18px rgba(16,185,129,.35); }
    }
    .submit-zone .goto-tab {
        color: #4f46e5;
        font-weight: 600;
        text-decoration: none;
        border-bottom: 1px dashed #c7d2fe;
        transition: all .15s ease;
    }
    .submit-zone .goto-tab:hover {
        color: #4338ca;
        border-bottom-color: #4338ca;
    }

    @media (max-width: 991px) {
        .submit-zone-global { position: static; }
    }
    .btn-submit-verifikasi {
        background: linear-gradient(135deg, #10b981, #059669);
        border: 0;
        color: #fff;
        font-weight: 700;
        padding: .75rem 1.5rem;
        border-radius: .7rem;
        font-size: .9rem;
        box-shadow: 0 8px 22px rgba(16,185,129,.35);
        transition: all .25s ease;
        display: inline-flex;
        align-items: center;
        gap: .55rem;
    }
    .btn-submit-verifikasi:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 14px 28px rgba(16,185,129,.45);
        color: #fff;
    }
    .btn-submit-verifikasi:disabled {
        background: linear-gradient(135deg, #cbd5e1, #94a3b8);
        cursor: not-allowed;
        box-shadow: none;
        opacity: .85;
    }

    /* ============================================================
       TIMELINE
       ============================================================ */
    .timeline {
        position: relative;
        padding-left: 28px;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 9px; top: 6px; bottom: 6px;
        width: 2px;
        background: linear-gradient(180deg, #818cf8, #c4b5fd, #f1f5f9);
        border-radius: 999px;
    }
    .timeline-item {
        position: relative;
        padding-bottom: 1.5rem;
        animation: fadeSlideRight .55s cubic-bezier(.22,1,.36,1) both;
    }
    .timeline-item:nth-child(1) { animation-delay: .05s; }
    .timeline-item:nth-child(2) { animation-delay: .15s; }
    .timeline-item:nth-child(3) { animation-delay: .25s; }
    .timeline-item:nth-child(4) { animation-delay: .35s; }
    .timeline-item:nth-child(n+5) { animation-delay: .45s; }
    .timeline-item:last-child { padding-bottom: 0; }
    .timeline-dot {
        position: absolute;
        left: -28px; top: 0;
        width: 22px; height: 22px;
        border-radius: 50%;
        background: linear-gradient(135deg, #818cf8, #6366f1);
        border: 4px solid #fff;
        box-shadow: 0 0 0 1px #c7d2fe, 0 4px 10px rgba(99,102,241,.30);
        z-index: 1;
    }
    .timeline-dot.dot-success { background: linear-gradient(135deg, #34d399, #10b981); box-shadow: 0 0 0 1px #6ee7b7, 0 4px 10px rgba(16,185,129,.30); }
    .timeline-dot.dot-warning { background: linear-gradient(135deg, #fbbf24, #f59e0b); box-shadow: 0 0 0 1px #fcd34d, 0 4px 10px rgba(245,158,11,.30); }
    .timeline-dot.dot-danger  { background: linear-gradient(135deg, #fb7185, #ef4444); box-shadow: 0 0 0 1px #fca5a5, 0 4px 10px rgba(239,68,68,.30); }
    .timeline-dot.dot-slate   { background: linear-gradient(135deg, #94a3b8, #64748b); box-shadow: 0 0 0 1px #cbd5e1, 0 4px 10px rgba(100,116,139,.20); }

    .timeline-card {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: .85rem;
        padding: .85rem 1.1rem;
        transition: all .2s ease;
    }
    .timeline-card:hover {
        border-color: #c7d2fe;
        box-shadow: 0 8px 18px rgba(99,102,241,.10);
    }
    .timeline-status {
        font-weight: 700;
        font-size: .9rem;
        color: #1e293b;
        margin: 0 0 .15rem;
    }
    .timeline-meta {
        font-size: .73rem;
        color: #64748b;
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .timeline-note {
        margin-top: .65rem;
        padding: .55rem .75rem;
        background: rgba(99,102,241,.06);
        border-left: 3px solid #818cf8;
        border-radius: .5rem;
        font-size: .82rem;
        color: #475569;
    }
    .timeline-note.note-danger {
        background: rgba(244,63,94,.06);
        border-color: #f43f5e;
        color: #991b1b;
    }
    .timeline-note.note-success {
        background: rgba(16,185,129,.06);
        border-color: #10b981;
        color: #047857;
    }

    /* ============================================================
       Empty state
       ============================================================ */
    .empty-state-d {
        text-align: center;
        padding: 2.5rem 1rem;
        color: #94a3b8;
    }
    .empty-state-d i {
        font-size: 2.5rem;
        margin-bottom: .65rem;
        display: block;
        background: linear-gradient(135deg, #c7d2fe, #818cf8);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* ============================================================
       ANIMATIONS
       ============================================================ */
    @keyframes fadeSlideDown {
        from { opacity: 0; transform: translateY(-12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeSlideUp {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeSlideRight {
        from { opacity: 0; transform: translateX(-10px); }
        to   { opacity: 1; transform: translateX(0); }
    }
</style>
@endpush

@php
    $statusInfoMap = [
        'DRAFT'         => ['hero' => 'hero-draft',    'alert' => 'alert-draft',    'icon' => 'bi-pencil-square',          'label' => 'Draft',         'msg' => 'Dokumen ini belum diajukan. Silakan lengkapi data dan dokumen wajib sebelum mengajukan verifikasi.'],
        'PENDING_PPK'   => ['hero' => 'hero-pending',  'alert' => 'alert-pending',  'icon' => 'bi-hourglass-split',        'label' => 'Menunggu PPK',  'msg' => 'Dokumen sedang dalam telaah verifikator. Data tidak dapat diubah selama proses berjalan.'],
        'DITOLAK_PPK'   => ['hero' => 'hero-rejected', 'alert' => 'alert-rejected', 'icon' => 'bi-arrow-counterclockwise', 'label' => 'Dikembalikan',  'msg' => 'Dokumen ini dikembalikan untuk revisi. Lihat catatan pada riwayat lalu klik Edit Data untuk memperbaiki.'],
        'DISETUJUI_PPK' => ['hero' => 'hero-approved', 'alert' => 'alert-approved', 'icon' => 'bi-check-circle-fill',      'label' => 'Disetujui PPK', 'msg' => 'Dokumen telah melewati tahap verifikasi PPK dan akan diproses ke tahap selanjutnya.'],
        'PROSES_SPP'    => ['hero' => 'hero-info',     'alert' => 'alert-approved', 'icon' => 'bi-arrow-repeat',           'label' => 'Proses SPP',    'msg' => 'Dokumen telah disetujui dan sedang dalam proses pembuatan SPP.'],
        'SPP_TERBIT'    => ['hero' => 'hero-approved', 'alert' => 'alert-approved', 'icon' => 'bi-file-earmark-check',     'label' => 'SPP Terbit',    'msg' => 'SPP telah diterbitkan untuk dokumen ini.'],
    ];

    $defaultStatus = ['hero' => 'hero-pending', 'alert' => 'alert-pending', 'icon' => 'bi-info-circle-fill', 'label' => str_replace('_', ' ', $tagihan->status), 'msg' => 'Dokumen sedang dalam proses workflow.'];
    $statusInfo = $statusInfoMap[$tagihan->status] ?? $defaultStatus;

    $totalBruto = (float) $tagihan->detailHonorarium->sum('nilai_honor');
    $totalPph   = (float) $tagihan->detailHonorarium->sum('pph');
    $totalNetto = (float) $tagihan->total_netto;
    $jumlahPenerima = $tagihan->detailHonorarium->count();

    $skDoc = $tagihan->arsipDokumen->where('jenis_dokumen', 'SK Honorarium')->first();
    $uploadedTypes = $tagihan->arsipDokumen->pluck('jenis_dokumen')->toArray();
    $hasDaftarNominatif = in_array('Daftar Nominatif Bertandatangan', $uploadedTypes);
    $hasDokumenHonorarium = in_array('Dokumen Honorarium Bertandatangan', $uploadedTypes);
    $isReady = $hasDaftarNominatif && $hasDokumenHonorarium;

    // ===== DIPA / sumber anggaran =====
    $dipaItem = $tagihan->dipaRevisionItem;
    $coa      = $dipaItem?->coa;
    $masterDipa = $tagihan->dipa ?? $dipaItem?->dipaRevision?->masterDipa;
    $paguItem    = (float) ($dipaItem->nilai_pagu ?? 0);
    $realisasiItem = $dipaItem ? (float) $dipaItem->realisasiAnggarans()->sum('nominal_cair') : 0;
    $sisaItem    = max(0, $paguItem - $realisasiItem);
    $persenSerap = $paguItem > 0 ? min(100, round(($realisasiItem / $paguItem) * 100, 2)) : 0;
@endphp

@section('content')
<x-page-title title="Manajemen Honor" subtitle="Detail Honorarium" />

{{-- ============================================================
     HERO HEADER
     ============================================================ --}}
<div class="detail-hero {{ $statusInfo['hero'] }}">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="hero-status-pill"><i class="bi {{ $statusInfo['icon'] }}"></i> {{ $statusInfo['label'] }}</span>
                <span class="hero-status-pill" style="opacity:.85;">
                    <i class="bi bi-tag-fill"></i> Honorarium
                </span>
            </div>
            <h2 class="hero-doc-no"><i class="bi bi-file-earmark-text me-2"></i>{{ $tagihan->nomor_tagihan }}</h2>
            <p class="mb-0" style="color: rgba(255,255,255,.92);">{{ $tagihan->deskripsi }}</p>
            <div class="hero-meta">
                <span class="meta-item"><i class="bi bi-calendar3"></i> Dibuat {{ $tagihan->created_at?->isoFormat('D MMM YYYY') }}</span>
                <span class="meta-item"><i class="bi bi-clock-history"></i> Update {{ $tagihan->updated_at?->isoFormat('D MMM YYYY, HH:mm') }}</span>
                <span class="meta-item"><i class="bi bi-people-fill"></i> {{ $jumlahPenerima }} penerima</span>
            </div>
        </div>
        <div class="hero-actions">
            <a href="{{ route('honorarium.index') }}" class="btn-hero">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <div class="dropdown">
                <button class="btn-hero dropdown-toggle" type="button" id="dropdownCetak" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-printer"></i> Cetak
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow rounded-3 border-0 mt-2" aria-labelledby="dropdownCetak">
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('honorarium.pdf', $tagihan->id) }}" target="_blank">
                            <i class="bi bi-file-earmark-pdf text-danger me-2"></i> Dokumen Honorarium
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('honorarium.pdf-nominatif', $tagihan->id) }}" target="_blank">
                            <i class="bi bi-file-earmark-person text-danger me-2"></i> Daftar Nominatif
                        </a>
                    </li>
                </ul>
            </div>
            @if(in_array($tagihan->status, ['DRAFT', 'DITOLAK_PPK']))
                <a href="{{ route('honorarium.edit', $tagihan->id) }}" class="btn-hero btn-hero-primary">
                    <i class="bi bi-pencil-square"></i> Edit Data
                </a>
            @endif
        </div>
    </div>
</div>

{{-- ============================================================
     STATUS ALERT
     ============================================================ --}}
<div class="status-alert {{ $statusInfo['alert'] }}">
    <div class="sa-icon"><i class="bi {{ $statusInfo['icon'] }}"></i></div>
    <div>
        <h6>Status: {{ $statusInfo['label'] }}</h6>
        <p>{{ $statusInfo['msg'] }}</p>
    </div>
</div>

{{-- ============================================================
     KPI STATS
     ============================================================ --}}
<div class="stat-grid">
    <div class="stat-card-d sc-primary">
        <div class="sc-icon"><i class="bi bi-cash-stack"></i></div>
        <p class="sc-label">Total Bruto</p>
        <h3 class="sc-value">Rp {{ number_format($totalBruto, 0, ',', '.') }}</h3>
        <p class="sc-foot">Sebelum dipotong PPh</p>
    </div>
    <div class="stat-card-d sc-danger">
        <div class="sc-icon"><i class="bi bi-percent"></i></div>
        <p class="sc-label">Total PPh</p>
        <h3 class="sc-value">Rp {{ number_format($totalPph, 0, ',', '.') }}</h3>
        <p class="sc-foot">{{ $totalBruto > 0 ? round(($totalPph / $totalBruto) * 100, 2) : 0 }}% dari bruto</p>
    </div>
    <div class="stat-card-d sc-success">
        <div class="sc-icon"><i class="bi bi-wallet2"></i></div>
        <p class="sc-label">Total Netto</p>
        <h3 class="sc-value">Rp {{ number_format($totalNetto, 0, ',', '.') }}</h3>
        <p class="sc-foot">Yang diterima penerima</p>
    </div>
    <div class="stat-card-d sc-info">
        <div class="sc-icon"><i class="bi bi-people-fill"></i></div>
        <p class="sc-label">Jumlah Penerima</p>
        <h3 class="sc-value">{{ $jumlahPenerima }} <span style="font-size:.9rem; color:#94a3b8; font-weight:600;">orang</span></h3>
        <p class="sc-foot">Rata-rata Rp {{ $jumlahPenerima > 0 ? number_format($totalNetto / $jumlahPenerima, 0, ',', '.') : 0 }}/orang</p>
    </div>
</div>

{{-- ============================================================
     INFORMASI UTAMA
     ============================================================ --}}
<div class="panel-d">
    <div class="panel-d-head">
        <h6><i class="bi bi-info-circle-fill"></i> Informasi Pengajuan</h6>
    </div>
    <div class="panel-d-body">
        <div class="info-list">
            <div class="info-item">
                <div class="info-label">Uraian / Deskripsi Kegiatan</div>
                <div class="info-value">{{ $tagihan->deskripsi }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Mekanisme Pembayaran</div>
                <div class="info-value">
                    <span class="role-chip" style="--vc-color:#1d4ed8; --vc-soft-bg: rgba(59,130,246,.10);">
                        <i class="bi bi-credit-card-2-front"></i> {{ $tagihan->mekanisme_pembayaran?->label() ?? $tagihan->mekanisme_pembayaran ?? '—' }}
                    </span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Nama Supplier (SPP/SPM)</div>
                <div class="info-value">{{ $tagihan->nama_supplier ?? '—' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Dokumen Pendukung (SK Honorarium)</div>
                <div class="info-value">
                    @if($skDoc)
                        <a href="{{ Storage::url($skDoc->path_file) }}" target="_blank" class="role-chip" style="--vc-color:#b91c1c; --vc-soft-bg: rgba(244,63,94,.10); padding:.4rem .85rem; text-decoration:none;">
                            <i class="bi bi-file-earmark-pdf"></i> {{ \Illuminate\Support\Str::limit($skDoc->nama_file_asli, 40) }}
                        </a>
                    @else
                        <span class="text-muted small fst-italic"><i class="bi bi-dash-circle me-1"></i> Tidak ada lampiran</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     SUMBER ANGGARAN (DIPA)
     ============================================================ --}}
<div class="panel-d" style="animation-delay: .26s;">
    <div class="panel-d-head">
        <h6><i class="bi bi-wallet-fill"></i> Sumber Anggaran (DIPA)</h6>
        @if($masterDipa)
            <span class="role-chip" style="--vc-color:#0369a1; --vc-soft-bg: rgba(14,165,233,.12);">
                <i class="bi bi-calendar3"></i> Tahun Anggaran {{ $masterDipa->tahun_anggaran }}
            </span>
        @endif
    </div>
    <div class="panel-d-body">
        @if($dipaItem && $coa)
            {{-- DIPA Header info --}}
            <div class="dipa-header">
                <div class="dipa-header-left">
                    <div class="info-label">Nomor DIPA</div>
                    <div class="dipa-doc-no">
                        <i class="bi bi-file-earmark-text-fill"></i>
                        {{ $masterDipa->nomor_dipa ?? '—' }}
                    </div>
                    @if($masterDipa?->tanggal_disahkan)
                        <div class="text-muted small mt-1">
                            <i class="bi bi-check2-circle"></i>
                            Disahkan {{ \Carbon\Carbon::parse($masterDipa->tanggal_disahkan)->isoFormat('D MMMM YYYY') }}
                        </div>
                    @endif
                </div>
                <div class="dipa-header-right">
                    <div class="info-label">Kode MAK Lengkap</div>
                    <div class="mak-chip">{{ $coa->kode_mak_lengkap ?? '—' }}</div>
                </div>
            </div>

            {{-- COA breakdown --}}
            @if($coa)
                <div class="info-label" style="margin-top: 1.25rem; margin-bottom: .65rem;">Rincian Akun (COA)</div>
                <div class="coa-grid">
                    @foreach([
                        ['label' => 'Program', 'value' => $coa->kd_program],
                        ['label' => 'Kegiatan', 'value' => $coa->kd_giat],
                        ['label' => 'Output', 'value' => $coa->kd_output],
                        ['label' => 'Sub Output', 'value' => $coa->kd_suboutput],
                        ['label' => 'Komponen', 'value' => $coa->kd_komponen],
                        ['label' => 'Sub Komponen', 'value' => $coa->kd_subkomponen],
                        ['label' => 'Akun', 'value' => $coa->kd_akun],
                    ] as $row)
                        @if(!empty($row['value']))
                            <div class="coa-cell">
                                <div class="coa-cell-label">{{ $row['label'] }}</div>
                                <div class="coa-cell-value">{{ $row['value'] }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
                <div class="coa-name-card">
                    <div class="info-label">Nama Akun</div>
                    <div class="coa-name-value">{{ $coa->nama_akun ?? '—' }}</div>
                    @if($coa->jenis_akun)
                        <span class="role-chip mt-1" style="--vc-color:#4338ca; --vc-soft-bg: rgba(99,102,241,.10);">
                            <i class="bi bi-tag-fill"></i> {{ $coa->jenis_akun }}
                        </span>
                    @endif
                </div>
            @endif

            {{-- Pagu vs Realisasi vs Sisa --}}
            <div class="dipa-stat-grid">
                <div class="dipa-stat dipa-stat-pagu">
                    <div class="dipa-stat-label"><i class="bi bi-bank"></i> Pagu Item</div>
                    <div class="dipa-stat-value">Rp {{ number_format($paguItem, 0, ',', '.') }}</div>
                </div>
                <div class="dipa-stat dipa-stat-real">
                    <div class="dipa-stat-label"><i class="bi bi-graph-up-arrow"></i> Realisasi</div>
                    <div class="dipa-stat-value">Rp {{ number_format($realisasiItem, 0, ',', '.') }}</div>
                    <div class="dipa-stat-foot">{{ $persenSerap }}% terserap</div>
                </div>
                <div class="dipa-stat dipa-stat-sisa">
                    <div class="dipa-stat-label"><i class="bi bi-piggy-bank-fill"></i> Sisa Pagu</div>
                    <div class="dipa-stat-value">Rp {{ number_format($sisaItem, 0, ',', '.') }}</div>
                </div>
            </div>

            {{-- Progress bar serapan --}}
            <div class="dipa-progress-wrap">
                <div class="dipa-progress-bar">
                    <div class="dipa-progress-fill" style="width: {{ $persenSerap }}%"></div>
                </div>
                <div class="dipa-progress-meta">
                    <span><i class="bi bi-info-circle"></i> Serapan {{ $persenSerap }}% dari pagu item</span>
                    <span class="dipa-tagihan-share">
                        Bruto tagihan ini: <strong class="text-dark">Rp {{ number_format($totalBruto, 0, ',', '.') }}</strong>
                        @if($paguItem > 0)
                            ({{ round(($totalBruto / $paguItem) * 100, 2) }}% dari pagu item)
                        @endif
                    </span>
                </div>
            </div>
        @else
            <div class="empty-state-d">
                <i class="bi bi-wallet"></i>
                <h6 class="text-secondary fw-bold mb-1">Sumber anggaran belum terhubung</h6>
                <small>Edit tagihan ini untuk memilih item DIPA / COA.</small>
            </div>
        @endif
    </div>
</div>

{{-- ============================================================
     DOKUMEN SECTION
     ============================================================ --}}
<div class="dokumen-section mb-4">
    @if($tagihan->status === 'DRAFT' && !$isReady)
        <div class="status-alert alert-rejected">
            <div class="sa-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
            <div>
                <h6>Lengkapi Dokumen Wajib</h6>
                <p>Unggah salinan PDF pindaian <strong>Daftar Nominatif</strong> dan <strong>Dokumen Honorarium</strong> yang telah ditandatangani sebelum mengajukan verifikasi.</p>
            </div>
        </div>

        <div class="panel-d">
            <div class="panel-d-head">
                <h6><i class="bi bi-cloud-upload-fill"></i> Form Upload Dokumen Wajib</h6>
            </div>
            <div class="panel-d-body">
                <form action="{{ route('honorarium.dokumen.upload-wajib', $tagihan->id) }}" method="POST" enctype="multipart/form-data" id="formUploadWajib">
                    @csrf
                    <div class="upload-grid">
                        <label class="upload-zone {{ $hasDaftarNominatif ? 'is-done' : '' }}" {{ $hasDaftarNominatif ? '' : 'data-target' }}>
                            <div class="uz-label">1. Daftar Nominatif</div>
                            <div class="uz-icon">
                                <i class="bi bi-{{ $hasDaftarNominatif ? 'check-circle-fill' : 'cloud-arrow-up' }}"></i>
                            </div>
                            <div class="uz-title" id="title-nominatif">{{ $hasDaftarNominatif ? 'Sudah diunggah' : 'Klik atau seret file PDF' }}</div>
                            <div class="uz-sub">{{ $hasDaftarNominatif ? 'File tersimpan' : 'Format PDF · Maks 10MB' }}</div>
                            @unless($hasDaftarNominatif)
                                <input type="file" name="file_nominatif" id="fileNominatif" accept=".pdf" class="upload-wajib-input" required>
                            @endunless
                        </label>

                        <label class="upload-zone {{ $hasDokumenHonorarium ? 'is-done' : '' }}">
                            <div class="uz-label">2. Dokumen Honorarium</div>
                            <div class="uz-icon">
                                <i class="bi bi-{{ $hasDokumenHonorarium ? 'check-circle-fill' : 'cloud-arrow-up' }}"></i>
                            </div>
                            <div class="uz-title" id="title-honor">{{ $hasDokumenHonorarium ? 'Sudah diunggah' : 'Klik atau seret file PDF' }}</div>
                            <div class="uz-sub">{{ $hasDokumenHonorarium ? 'File tersimpan' : 'Format PDF · Maks 10MB' }}</div>
                            @unless($hasDokumenHonorarium)
                                <input type="file" name="file_honorarium" id="fileHonorarium" accept=".pdf" class="upload-wajib-input" required>
                            @endunless
                        </label>
                    </div>

                    @if(!$isReady)
                        <button type="submit" class="btn-submit-verifikasi w-100 mt-3" id="btnUploadWajib" disabled style="background: linear-gradient(135deg, #6366f1, #4f46e5); box-shadow: 0 8px 22px rgba(99,102,241,.35);">
                            <i class="bi bi-cloud-upload"></i> Unggah Dokumen
                        </button>
                    @endif
                </form>
            </div>
        </div>
    @endif

    <div class="panel-d">
        <div class="panel-d-head">
            <h6><i class="bi bi-folder-fill"></i> Dokumen Tersimpan</h6>
            <span class="role-chip" style="--vc-color:#4338ca; --vc-soft-bg: rgba(99,102,241,.12);">
                <i class="bi bi-file-earmark-fill"></i> {{ $tagihan->arsipDokumen->count() }} file
            </span>
        </div>
        <div class="panel-d-body">
            @if($tagihan->arsipDokumen->isEmpty())
                <div class="empty-state-d">
                    <i class="bi bi-folder-x"></i>
                    <h6 class="text-secondary fw-bold mb-1">Belum ada dokumen</h6>
                    <small>Unggah dokumen pendukung untuk melengkapi tagihan ini.</small>
                </div>
            @else
                <div class="doc-list">
                    @foreach($tagihan->arsipDokumen as $arsip)
                        <div class="doc-item">
                            <div class="doc-icon"><i class="bi bi-file-earmark-pdf"></i></div>
                            <div class="doc-info">
                                <div class="doc-type">{{ $arsip->jenis_dokumen }}</div>
                                <div class="doc-name" title="{{ $arsip->nama_file_asli }}">{{ $arsip->nama_file_asli }}</div>
                                <div class="doc-time"><i class="bi bi-clock"></i> {{ $arsip->created_at->isoFormat('D MMM YYYY HH:mm') }}</div>
                            </div>
                            <div class="doc-actions">
                                <a href="{{ Storage::url($arsip->path_file) }}" target="_blank" class="doc-action-btn btn-dl" title="Unduh">
                                    <i class="bi bi-download"></i>
                                </a>
                                @if($tagihan->status === 'DRAFT')
                                    <form action="{{ route('honorarium.dokumen.delete', ['id' => $tagihan->id, 'arsip_id' => $arsip->id]) }}" method="POST" class="m-0" onsubmit="return confirm('Hapus dokumen ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="doc-action-btn btn-rm" title="Hapus"><i class="bi bi-trash3"></i></button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if($tagihan->status === 'DRAFT')
        {{-- Submit zone dipindahkan ke luar tabs --}}
    @endif
</div>

{{-- ============================================================
     TABS NAVIGATION
     ============================================================ --}}
<div class="tabs-bar" id="tabsBar">
    <button class="tab-btn active" data-tab="rincian">
        <i class="bi bi-people-fill"></i> Rincian Penerima
        <span class="tab-count">{{ $jumlahPenerima }}</span>
    </button>
    <button class="tab-btn" data-tab="riwayat">
        <i class="bi bi-clock-history"></i> Riwayat
        <span class="tab-count">{{ $tagihan->logs->count() }}</span>
    </button>
</div>

{{-- ============================================================
     TAB: RINCIAN PENERIMA
     ============================================================ --}}
<div class="tab-pane-d active" data-pane="rincian">
    <div class="panel-d">
        <div class="panel-d-head">
            <h6><i class="bi bi-people-fill"></i> Daftar Rincian Penerima</h6>
            <span class="role-chip" style="--vc-color:#4338ca; --vc-soft-bg: rgba(99,102,241,.12);">
                <i class="bi bi-calculator"></i> Total Netto Rp {{ number_format($totalNetto, 0, ',', '.') }}
            </span>
        </div>
        <div style="overflow-x: auto;">
            <table class="table-recipients">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Penerima</th>
                        <th>Rekening Bank</th>
                        <th class="text-end">Bruto</th>
                        <th class="text-end">PPh</th>
                        <th class="text-end">Netto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tagihan->detailHonorarium as $idx => $detail)
                        @php $avatarVar = ($idx % 5) + 1; @endphp
                        <tr>
                            <td><span class="text-muted small fw-bold">{{ $loop->iteration }}</span></td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="recipient-avatar av-{{ $avatarVar }}">{{ \Illuminate\Support\Str::upper(mb_substr($detail->nama_personel ?? '?', 0, 1)) }}</span>
                                    <div>
                                        <div class="recipient-name">{{ $detail->nama_personel ?? '-' }}</div>
                                        <div class="recipient-meta">
                                            @if($detail->nrp_nip) <i class="bi bi-card-text"></i> {{ $detail->nrp_nip }} @endif
                                            @if($detail->jabatan) · {{ $detail->jabatan }} @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($detail->jenis_bank || $detail->rekening)
                                    <span class="bank-chip"><i class="bi bi-bank"></i> {{ $detail->jenis_bank ?? 'Bank' }}</span>
                                    <div class="recipient-meta" style="margin-top: .15rem;">
                                        <span class="font-monospace">{{ $detail->rekening ?? '-' }}</span>
                                        @if($detail->nama_rekening) · <span>{{ $detail->nama_rekening }}</span> @endif
                                    </div>
                                @else
                                    <span class="text-muted small fst-italic">Belum ada rekening</span>
                                @endif
                            </td>
                            <td class="text-end"><span class="money">Rp {{ number_format($detail->nilai_honor, 0, ',', '.') }}</span></td>
                            <td class="text-end"><span class="money-neg">Rp {{ number_format($detail->pph, 0, ',', '.') }}</span></td>
                            <td class="text-end"><span class="money-pos">Rp {{ number_format($detail->nilai_honor - $detail->pph, 0, ',', '.') }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state-d">
                                    <i class="bi bi-people"></i>
                                    <h6 class="text-secondary fw-bold mb-1">Belum ada penerima</h6>
                                    <small>Tambahkan rincian penerima honor melalui menu Edit Data.</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($jumlahPenerima > 0)
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end" style="color:#475569;">TOTAL KESELURUHAN</td>
                            <td class="text-end"><span class="money">Rp {{ number_format($totalBruto, 0, ',', '.') }}</span></td>
                            <td class="text-end"><span class="money-neg">Rp {{ number_format($totalPph, 0, ',', '.') }}</span></td>
                            <td class="text-end"><span class="money-pos">Rp {{ number_format($totalNetto, 0, ',', '.') }}</span></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- ============================================================
     TAB: VERIFIKASI
     ============================================================ --}}
@if($tagihan->status !== 'DRAFT')
    @php
        $approvalStatusByRole = collect();
        if ($activeWorkflowInstance ?? null) {
            $approvalStatusByRole = collect($activeWorkflowInstance->approvals ?? [])
                ->keyBy(fn ($a) => strtoupper(str_replace([' ', '-'], '_', $a->role_code)));
        }

        $verifikatorList = [
            ['key' => 'ppk',                  'role_code' => 'PPK',                  'label' => 'Pejabat Pembuat Komitmen',                 'short' => 'PPK',          'color' => '#6366f1', 'soft' => 'rgba(99,102,241,.12)',  'shadow' => 'rgba(99,102,241,.30)',  'nama' => $tagihan->ppk_nama_snapshot,                  'nip' => $tagihan->ppk_nip_snapshot],
            ['key' => 'ppspm',                'role_code' => 'PPSPM',                'label' => 'PPSPM',                                    'short' => 'PPSPM',        'color' => '#8b5cf6', 'soft' => 'rgba(139,92,246,.12)',  'shadow' => 'rgba(139,92,246,.30)',  'nama' => $tagihan->ppspm_nama_snapshot,                'nip' => $tagihan->ppspm_nip_snapshot],
            ['key' => 'bendahara_pengeluaran','role_code' => 'BENDAHARA_PENGELUARAN','label' => 'Bendahara Pengeluaran',                    'short' => 'BEND. KELUAR', 'color' => '#ec4899', 'soft' => 'rgba(236,72,153,.12)',  'shadow' => 'rgba(236,72,153,.30)',  'nama' => $tagihan->bendahara_pengeluaran_nama_snapshot,'nip' => $tagihan->bendahara_pengeluaran_nip_snapshot],
            ['key' => 'bendahara_penerimaan', 'role_code' => 'BENDAHARA_PENERIMAAN', 'label' => 'Bendahara Penerimaan',                     'short' => 'BEND. TERIMA', 'color' => '#f59e0b', 'soft' => 'rgba(245,158,11,.12)',  'shadow' => 'rgba(245,158,11,.30)',  'nama' => $tagihan->bendahara_penerimaan_nama_snapshot, 'nip' => $tagihan->bendahara_penerimaan_nip_snapshot],
            ['key' => 'koordinator_keuangan', 'role_code' => 'KOORDINATOR_KEUANGAN', 'label' => 'Koordinator Keuangan',                     'short' => 'KOOR. KEU',    'color' => '#10b981', 'soft' => 'rgba(16,185,129,.12)',  'shadow' => 'rgba(16,185,129,.30)',  'nama' => $tagihan->koordinator_keuangan_nama_snapshot, 'nip' => $tagihan->koordinator_keuangan_nip_snapshot],
            ['key' => 'kasubbag',             'role_code' => 'KASUBBAG',             'label' => 'Kepala Subbagian Keuangan dan Tata Usaha', 'short' => 'KASUBBAG',     'color' => '#0ea5e9', 'soft' => 'rgba(14,165,233,.12)',  'shadow' => 'rgba(14,165,233,.30)',  'nama' => $tagihan->kasubbag_nama_snapshot,             'nip' => $tagihan->kasubbag_nip_snapshot],
        ];

        $initials = function ($name) {
            $name = trim((string) $name);
            if ($name === '') return '?';
            $parts = preg_split('/\s+/', $name);
            $first = mb_substr($parts[0] ?? '', 0, 1);
            $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
            return mb_strtoupper($first . $last);
        };

        $statusMeta = [
            'PENDING'  => ['cls' => 'pill-pending',  'icon' => 'hourglass-split',        'label' => 'Menunggu',  'card' => 'is-pending'],
            'APPROVED' => ['cls' => 'pill-approved', 'icon' => 'check-circle-fill',      'label' => 'Disetujui', 'card' => 'is-approved'],
            'REVISION' => ['cls' => 'pill-revision', 'icon' => 'arrow-counterclockwise', 'label' => 'Revisi',    'card' => 'is-revision'],
            'REJECTED' => ['cls' => 'pill-rejected', 'icon' => 'x-circle-fill',          'label' => 'Ditolak',   'card' => 'is-rejected'],
            'WAITING'  => ['cls' => 'pill-waiting',  'icon' => 'clock-history',          'label' => 'Belum aktif','card' => ''],
        ];

        $step1Approvals = collect($activeWorkflowInstance?->approvals ?? [])->where('urutan_step', 1);
        $step1Total = $step1Approvals->count();
        $step1Approved = $step1Approvals->where('status', 'APPROVED')->count();
        $step1Percent = $step1Total > 0 ? round(($step1Approved / $step1Total) * 100) : 0;
    @endphp

    <div class="panel-d" id="panelVerifikator" style="animation-delay: .28s;">
        <div class="panel-d-head">
                <h6><i class="bi bi-diagram-3"></i> Progress Verifikasi (Paralel)</h6>
                @if($step1Total > 0)
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="role-chip" style="--vc-color: {{ $step1Approved === $step1Total ? '#047857' : '#1d4ed8' }}; --vc-soft-bg: {{ $step1Approved === $step1Total ? 'rgba(16,185,129,.12)' : 'rgba(59,130,246,.12)' }};">
                            <i class="bi bi-{{ $step1Approved === $step1Total ? 'check-circle' : 'people-fill' }}"></i>
                            {{ $step1Approved }}/{{ $step1Total }} Step 1 disetujui
                        </span>
                        <span class="text-muted small fw-bold">{{ $step1Percent }}%</span>
                    </div>
                @endif
            </div>
            <div class="panel-d-body">
                @if($step1Total > 0 && $step1Approved < $step1Total)
                    <div class="flow-progress-bar"><div class="flow-progress-fill" style="width: {{ $step1Percent }}%"></div></div>
                @endif

                <div class="verif-grid">
                    @foreach($verifikatorList as $idx => $v)
                        @php
                            $filled = !empty($v['nama']);
                            $approval = $approvalStatusByRole->get($v['role_code']);
                            $apvMeta = $approval ? ($statusMeta[$approval->status] ?? null) : null;
                            $cardClass = $filled ? '' : 'is-empty';
                            if ($apvMeta) $cardClass .= ' ' . $apvMeta['card'];
                        @endphp
                        <div class="verif-card {{ $cardClass }}" style="--vc-color: {{ $v['color'] }}; --vc-color-shadow: {{ $v['shadow'] }}; --vc-soft-bg: {{ $v['soft'] }};">
                            <span class="verif-step">{{ $idx + 1 }}</span>
                            @if($apvMeta)
                                <span class="verif-status-pill {{ $apvMeta['cls'] }}">
                                    <i class="bi bi-{{ $apvMeta['icon'] }}"></i> {{ $apvMeta['label'] }}
                                </span>
                            @endif
                            <div class="d-flex align-items-start gap-3 mt-2">
                                <div class="verif-avatar {{ !$filled ? 'empty' : '' }}">
                                    {{ $initials($v['nama']) }}
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="role-chip">{{ $v['short'] }}</span>
                                        @if($v['key'] === 'kasubbag')
                                            <span class="role-chip" style="--vc-color:#475569; --vc-soft-bg: rgba(100,116,139,.12);">
                                                <i class="bi bi-shield-check"></i> Final
                                            </span>
                                        @endif
                                    </div>
                                    @if($filled)
                                        <div class="verif-name" title="{{ $v['nama'] }}">{{ $v['nama'] }}</div>
                                    @else
                                        <div class="verif-name verif-empty-name">— belum dipilih —</div>
                                    @endif
                                    @if($v['nip'])
                                        <div class="verif-nip">NIP: {{ $v['nip'] }}</div>
                                    @endif
                                    <div class="verif-role">{{ $v['label'] }}</div>
                                    @if($approval && $approval->acted_at)
                                        <div class="verif-acted">
                                            <i class="bi bi-clock"></i>
                                            {{ \Carbon\Carbon::parse($approval->acted_at)->isoFormat('D MMM YYYY · HH:mm') }}
                                        </div>
                                    @endif
                                    @if($approval && $approval->catatan)
                                        <div class="verif-note">"{{ \Illuminate\Support\Str::limit($approval->catatan, 90) }}"</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
@endif

{{-- ============================================================
     TAB: RIWAYAT
     ============================================================ --}}
<div class="tab-pane-d" data-pane="riwayat">
    <div class="panel-d">
        <div class="panel-d-head">
            <h6><i class="bi bi-clock-history"></i> Riwayat Proses Dokumen</h6>
        </div>
        <div class="panel-d-body">
            @if($tagihan->logs->isEmpty())
                <div class="empty-state-d">
                    <i class="bi bi-journal-x"></i>
                    <h6 class="text-secondary fw-bold mb-1">Belum ada riwayat</h6>
                    <small>Aktivitas pada dokumen ini akan dicatat di sini.</small>
                </div>
            @else
                <div class="timeline">
                    @foreach($tagihan->logs as $log)
                        @php
                            $statusBaru = strtoupper((string) $log->status_baru);
                            if (str_contains($statusBaru, 'DITOLAK') || str_contains($statusBaru, 'REVISI')) {
                                $dotCls = 'dot-danger'; $noteCls = 'note-danger'; $icon = 'bi-arrow-counterclockwise';
                            } elseif (str_contains($statusBaru, 'DISETUJUI') || str_contains($statusBaru, 'TERBIT')) {
                                $dotCls = 'dot-success'; $noteCls = 'note-success'; $icon = 'bi-check-circle-fill';
                            } elseif (str_contains($statusBaru, 'PENDING') || str_contains($statusBaru, 'PROSES')) {
                                $dotCls = ''; $noteCls = ''; $icon = 'bi-hourglass-split';
                            } elseif ($statusBaru === 'DRAFT') {
                                $dotCls = 'dot-slate'; $noteCls = ''; $icon = 'bi-pencil-square';
                            } else {
                                $dotCls = 'dot-warning'; $noteCls = ''; $icon = 'bi-info-circle-fill';
                            }
                        @endphp
                        <div class="timeline-item">
                            <span class="timeline-dot {{ $dotCls }}"></span>
                            <div class="timeline-card">
                                <h6 class="timeline-status"><i class="bi {{ $icon }} me-1"></i>{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $log->status_baru)) }}</h6>
                                <div class="timeline-meta">
                                    <span><i class="bi bi-calendar3"></i> {{ $log->created_at->isoFormat('D MMM YYYY') }}</span>
                                    <span><i class="bi bi-clock"></i> {{ $log->created_at->format('H:i:s') }}</span>
                                    @if($log->user)
                                        <span><i class="bi bi-person-circle"></i> {{ $log->user->name ?? 'Sistem' }}</span>
                                    @endif
                                    @if($log->aksi)
                                        <span><i class="bi bi-tag"></i> {{ str_replace('_', ' ', $log->aksi) }}</span>
                                    @endif
                                </div>
                                @if($log->catatan)
                                    <div class="timeline-note {{ $noteCls }}">
                                        <strong>Catatan:</strong> {{ $log->catatan }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ============================================================
     SUBMIT BAR — selalu tampil saat status DRAFT
     ============================================================ --}}
@if($tagihan->status === 'DRAFT')
<div class="submit-zone submit-zone-global">
    <div class="d-flex align-items-center gap-3 flex-grow-1">
        <div class="submit-status-icon {{ $isReady ? 'is-ready' : 'is-pending' }}">
            <i class="bi bi-{{ $isReady ? 'shield-check' : 'shield-exclamation' }}"></i>
        </div>
        <div>
            <h6 class="fw-bold mb-1 text-dark">{{ $isReady ? 'Siap diajukan untuk verifikasi' : 'Belum siap diajukan' }}</h6>
            <p class="small text-muted mb-0">
                @if($isReady)
                    Semua dokumen wajib sudah lengkap. Klik tombol untuk meneruskan ke verifikator.
                @else
                    Lengkapi <strong>2 dokumen wajib</strong> di tab <a href="#" class="goto-tab" data-tab="dokumen">Dokumen</a> untuk mengaktifkan tombol pengajuan.
                @endif
            </p>
        </div>
    </div>
    <form action="{{ route('honorarium.submit-verifikasi', $tagihan->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin akan mengajukan tagihan honorarium ini? Data tidak dapat diubah setelah pengajuan.')">
        @csrf
        <button type="submit" class="btn-submit-verifikasi" {{ !$isReady ? 'disabled' : '' }}>
            <i class="bi bi-send-check-fill"></i> Ajukan Verifikasi
        </button>
    </form>
</div>
@endif

<script>
(function () {
    // ===== Tabs =====
    const tabs = document.querySelectorAll('#tabsBar .tab-btn');
    const panes = document.querySelectorAll('.tab-pane-d');

    function activate(tabName) {
        tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === tabName));
        panes.forEach(p => p.classList.toggle('active', p.dataset.pane === tabName));
        // re-trigger animation on pane swap
        const activePane = document.querySelector('.tab-pane-d.active');
        if (activePane) {
            activePane.style.animation = 'none';
            void activePane.offsetWidth;
            activePane.style.animation = '';
        }
    }

    tabs.forEach(t => {
        t.addEventListener('click', () => activate(t.dataset.tab));
    });

    // Goto-tab links inside submit zone
    document.querySelectorAll('.goto-tab').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            activate(link.dataset.tab);
            window.scrollTo({ top: document.querySelector('#tabsBar')?.offsetTop - 80, behavior: 'smooth' });
        });
    });

    // ===== Upload zone visual feedback =====
    const uploadInputs = document.querySelectorAll('.upload-wajib-input');
    uploadInputs.forEach(input => {
        input.addEventListener('change', () => {
            const zone = input.closest('.upload-zone');
            if (input.files.length) {
                zone.classList.add('has-file');
                const titleEl = zone.querySelector('.uz-title');
                if (titleEl) titleEl.textContent = input.files[0].name;
            } else {
                zone.classList.remove('has-file');
            }
            // Toggle submit button
            const btn = document.getElementById('btnUploadWajib');
            if (btn) {
                const allFilled = Array.from(uploadInputs).every(i => i.files && i.files.length > 0);
                btn.disabled = !allFilled;
            }
        });
    });
})();
</script>
@endsection
