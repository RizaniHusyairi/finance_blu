@extends('layouts.app')
@section('title')
    Portal Mitra
@endsection
@section('content')
@push('css')
@include('dashboard.partials.mitra-ui')
<style>
    /* Colored Tailwind UI Inspired Dashboard */
    @keyframes mitraCardReveal {
        from {
            opacity: 0;
            transform: translateY(18px) scale(.98);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    @keyframes mitraCardGlow {
        0%, 100% {
            opacity: .45;
            transform: translate3d(-18px, 10px, 0) scale(1);
        }
        50% {
            opacity: .95;
            transform: translate3d(24px, -10px, 0) scale(1.1);
        }
    }
    @keyframes mitraCardShine {
        0% { transform: translateX(-130%) skewX(-18deg); opacity: 0; }
        18% { opacity: .45; }
        52%, 100% { transform: translateX(185%) skewX(-18deg); opacity: 0; }
    }
    @keyframes mitraIconFloat {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-5px) rotate(2deg); }
    }
    @keyframes mitraIconPulse {
        0%, 100% {
            box-shadow: 0 12px 24px rgba(15, 23, 42, .13), 0 0 0 0 rgba(37, 99, 235, .16);
        }
        50% {
            box-shadow: 0 18px 34px rgba(15, 23, 42, .18), 0 0 0 12px rgba(37, 99, 235, 0);
        }
    }
    @keyframes mitraIconWiggle {
        0%, 100% { transform: scale(1); }
        45% { transform: scale(1.08); }
        65% { transform: scale(.98); }
    }
    @keyframes mitraHeroSweep {
        0% { transform: translateX(-120%) skewX(-18deg); opacity: 0; }
        20% { opacity: .32; }
        55%, 100% { transform: translateX(220%) skewX(-18deg); opacity: 0; }
    }
    @keyframes mitraActivityIn {
        from { opacity: 0; transform: translateY(12px) scale(.98); }
        to { opacity: 1; transform: none; }
    }
    @keyframes mitraActivityPulse {
        0%, 100% { transform: scale(1); box-shadow: inset 0 0 0 1px rgba(255,255,255,.18), 0 0 0 0 rgba(251,191,36,.28); }
        50% { transform: scale(1.07); box-shadow: inset 0 0 0 1px rgba(255,255,255,.22), 0 0 0 9px rgba(251,191,36,0); }
    }
    @keyframes mitraContourDrift {
        0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg); opacity: .68; }
        50% { transform: translate3d(-14px, 10px, 0) rotate(2deg); opacity: .95; }
    }
    .dashboard-wrapper {
        font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }
    
    .mitra-hero {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        background:
            radial-gradient(circle at 18% 30%, rgba(96, 165, 250, .28), transparent 28%),
            linear-gradient(110deg, #071421 0%, #0d2744 42%, #174f86 100%);
        border: 1px solid rgba(147, 197, 253, .28);
        border-radius: 0 24px 24px 0;
        color: #fff;
        padding: 28px 30px;
        box-shadow: 0 18px 50px rgba(18, 53, 92, .22);
        margin-bottom: 2rem;
        animation: mitraCardReveal .55s cubic-bezier(.2,.8,.2,1) both;
    }
    .mitra-hero-shell {
        position: relative;
        margin-bottom: 2rem;
        padding: 0;
        border-radius: 0 24px 24px 0;
        background: transparent;
        box-shadow: none;
    }
    .mitra-hero-shell::before {
        content: "";
        position: absolute;
        inset: -2px -2px -2px -2px;
        z-index: 0;
        border-radius: 0 26px 26px 0;
        border: 1px solid rgba(251, 191, 36, .36);
        pointer-events: none;
    }
    .mitra-hero-shell .mitra-hero {
        margin-bottom: 0;
    }
    .mitra-hero::before,
    .mitra-hero::after {
        content: "";
        position: absolute;
        pointer-events: none;
        z-index: -1;
    }
    .mitra-hero::before {
        width: 430px;
        height: 220px;
        right: -90px;
        top: -80px;
        border-radius: 0 0 0 999px;
        border-left: 2px solid rgba(251, 191, 36, .44);
        border-bottom: 2px solid rgba(147, 197, 253, .24);
        background: radial-gradient(circle at 50% 30%, rgba(96, 165, 250, .18), transparent 62%);
        animation: mitraContourDrift 5.6s ease-in-out infinite;
    }
    .mitra-hero::after {
        inset: 0;
        width: 46%;
        background: linear-gradient(90deg, transparent, rgba(125, 211, 252, .12), rgba(255, 255, 255, .20), rgba(96, 165, 250, .10), transparent);
        animation: mitraHeroSweep 4.2s ease-in-out infinite;
    }
    .mitra-hero-contour {
        position: absolute;
        right: 11%;
        bottom: -78px;
        z-index: -1;
        width: 360px;
        height: 210px;
        border-radius: 999px 999px 0 0;
        border-top: 2px solid rgba(251, 191, 36, .42);
        border-left: 2px solid rgba(147, 197, 253, .20);
        transform: rotate(-10deg);
        animation: mitraContourDrift 6.4s ease-in-out infinite reverse;
    }
    .mitra-hero-contour::before,
    .mitra-hero-contour::after {
        content: "";
        position: absolute;
        border-radius: inherit;
        border-top: 1px solid rgba(191, 219, 254, .24);
    }
    .mitra-hero-contour::before {
        inset: 26px 24px auto 20px;
        height: 138px;
    }
    .mitra-hero-contour::after {
        inset: 58px 52px auto 54px;
        height: 88px;
        border-color: rgba(251, 191, 36, .25);
    }
    .mitra-hero-content {
        position: relative;
        z-index: 1;
    }
    .mitra-hero-date {
        border: 1px solid rgba(191, 219, 254, .24);
        border-radius: 999px;
        background: rgba(15, 23, 42, .22);
        padding: 8px 10px;
        backdrop-filter: blur(8px);
    }
    .mitra-hero .btn-outline-light {
        background: rgba(255, 255, 255, .08);
        box-shadow: 0 10px 24px rgba(15, 23, 42, .18);
    }
    .mitra-activity-btn {
        position: relative;
        border: 0;
        color: #0f2f57;
        box-shadow: 0 12px 26px rgba(15, 23, 42, .18);
    }
    
    .stat-card {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        border-radius: 0.75rem; /* rounded-xl */
        border: 1px solid rgba(0,0,0,0.05);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); /* shadow-md */
        padding: 1.5rem; /* p-6 */
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        height: 100%;
        border-left-width: 4px;
        border-left-style: solid;
        animation: mitraCardReveal .58s cubic-bezier(.2,.8,.2,1) both;
    }
    .row.g-4.mb-4 > div:nth-child(2) .stat-card { animation-delay: .08s; }
    .row.g-4.mb-4 > div:nth-child(3) .stat-card { animation-delay: .16s; }
    .stat-card::before,
    .stat-card::after {
        content: "";
        position: absolute;
        pointer-events: none;
        z-index: -1;
    }
    .stat-card::before {
        width: 180px;
        height: 180px;
        right: -70px;
        top: -80px;
        border-radius: 999px;
        opacity: .6;
        animation: mitraCardGlow 4.6s ease-in-out infinite;
    }
    .stat-card::after {
        inset: 0;
        width: 45%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.48), transparent);
        animation: mitraCardShine 4.4s ease-in-out infinite;
    }
    .stat-card:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); /* shadow-lg */
        transform: translateY(-6px);
    }

    /* Soft Colored Backgrounds for Cards */
    .card-kontrak { background-color: #eff6ff; border-left-color: #3b82f6; } /* blue-50, blue-500 */
    .card-dibayar { background-color: #f0fdf4; border-left-color: #22c55e; } /* green-50, green-500 */
    .card-proses { background-color: #fff7ed; border-left-color: #f97316; } /* orange-50, orange-500 */
    .card-kontrak::before { background: radial-gradient(circle, rgba(59, 130, 246, .26), transparent 68%); }
    .card-dibayar::before { background: radial-gradient(circle, rgba(16, 185, 129, .24), transparent 68%); }
    .card-proses::before { background: radial-gradient(circle, rgba(245, 158, 11, .26), transparent 68%); }
    
    .stat-title {
        font-size: 0.875rem; /* text-sm */
        font-weight: 700; /* font-bold */
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }
    .card-kontrak .stat-title { color: #2563eb; } /* blue-600 */
    .card-dibayar .stat-title { color: #16a34a; } /* green-600 */
    .card-proses .stat-title { color: #ea580c; } /* orange-600 */

    .stat-value {
        font-size: 1.875rem; /* text-3xl */
        font-weight: 800; /* font-extrabold */
        margin-top: 0.5rem;
    }
    .card-kontrak .stat-value { color: #1e3a8a; } /* blue-900 */
    .card-dibayar .stat-value { color: #14532d; } /* green-900 */
    .card-proses .stat-value { color: #7c2d12; } /* orange-900 */

    .stat-desc {
        font-size: 0.75rem; /* text-xs */
        margin-top: 0.5rem;
        font-weight: 600;
    }
    .card-kontrak .stat-desc { color: #60a5fa; } /* blue-400 */
    .card-dibayar .stat-desc { color: #34d399; } /* green-400 */
    .card-proses .stat-desc { color: #fbbf24; } /* yellow-400 */

    .stat-icon {
        width: 3.5rem; /* w-14 */
        height: 3.5rem; /* h-14 */
        border-radius: 0.75rem; /* rounded-xl */
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem; /* text-3xl */
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        animation: mitraIconFloat 3s ease-in-out infinite, mitraIconPulse 2.6s ease-in-out infinite;
        transform-origin: center;
    }
    .stat-card:hover .stat-icon {
        animation: mitraIconWiggle .65s ease both, mitraIconPulse 2.6s ease-in-out infinite;
    }
    .stat-icon i {
        filter: drop-shadow(0 4px 8px rgba(15, 23, 42, .16));
    }
    .card-kontrak .stat-icon { background-image: linear-gradient(to bottom right, #60a5fa, #3b82f6); color: white; }
    .card-dibayar .stat-icon { background-image: linear-gradient(to bottom right, #34d399, #10b981); color: white; }
    .card-proses .stat-icon { background-image: linear-gradient(to bottom right, #fbbf24, #f59e0b); color: white; }

    /* Tailwind Tables with Matching Headers */
    .modern-table-card {
        background-color: #ffffff;
        border-radius: 18px;
        border: 1px solid rgba(37, 99, 235, .12);
        box-shadow: 0 16px 42px rgba(37, 99, 235, .08);
        overflow: hidden;
        animation: mitraCardReveal .62s cubic-bezier(.2,.8,.2,1) both;
    }
    .modern-table-card .card-header {
        background: linear-gradient(90deg, #eff6ff 0%, #f8fbff 58%, #ffffff 100%);
        border-bottom: 1px solid #bfdbfe;
        padding: 12px 14px;
        font-weight: 700;
        color: #1e3a8a;
    }
    .modern-table {
        margin-bottom: 0;
        width: 100%;
    }
    .modern-table th {
        background-color: rgba(248, 250, 252, .86);
        color: #64748b;
        text-transform: uppercase;
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0.03em;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid rgba(148, 163, 184, .20);
        text-align: left;
    }
    .modern-table td {
        padding: 1rem 1.5rem; /* py-4 px-6 */
        vertical-align: middle;
        font-size: 0.875rem; /* text-sm */
        color: #334155; /* slate-700 */
        border-bottom: 1px solid #f1f5f9; /* slate-100 */
        transition: all 0.2s ease;
    }
    .modern-table tbody tr {
        background-color: #ffffff;
        transition: all 0.2s ease;
    }
    .modern-table tbody tr:hover {
        background-color: #f8fafc; /* slate-50 */
        transform: scale(1.005);
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        z-index: 10;
        position: relative;
    }
    .modern-table tbody tr:hover td {
        color: #0f172a; /* slate-900 */
        font-weight: 500;
    }
    
    /* Clean up default button and link colors */
    .btn-outline-light { border-color: #cbd5e1; color: #f8fafc; }
    .btn-outline-light:hover { background-color: rgba(255,255,255,0.1); }
    .mitra-activity-modal .modal-dialog { max-width: 790px; }
    .mitra-activity-modal .modal-content {
        border: 0;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 28px 80px rgba(15,23,42,.28);
    }
    .mitra-activity-head {
        position: relative;
        overflow: hidden;
        padding: 24px 26px;
        color: #fff;
        background: linear-gradient(120deg, #071421 0%, #0d2744 44%, #174f86 100%);
    }
    .mitra-activity-head::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(2,8,23,.42), rgba(2,8,23,.12) 58%, transparent);
        pointer-events: none;
    }
    .mitra-activity-head::after {
        content: "";
        position: absolute;
        inset: 0;
        width: 46%;
        background: linear-gradient(90deg, transparent, rgba(125,211,252,.16), rgba(255,255,255,.22), transparent);
        animation: mitraHeroSweep 5.2s ease-in-out infinite;
        pointer-events: none;
    }
    .mitra-activity-head > * { position: relative; z-index: 1; }
    .mitra-activity-head .modal-title {
        color: #fff !important;
        font-weight: 900;
        text-shadow: 0 2px 14px rgba(0,0,0,.24);
    }
    .mitra-activity-date { color: rgba(255,255,255,.82) !important; }
    .mitra-activity-icon {
        width: 50px;
        height: 50px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,.16);
        color: #fff;
        font-size: 1.35rem;
        animation: mitraActivityPulse 2.8s ease-in-out infinite;
    }
    .mitra-activity-eyebrow {
        color: #fbbf24;
        font-size: .7rem;
        font-weight: 900;
        letter-spacing: .14em;
        text-transform: uppercase;
    }
    .mitra-activity-body { background: #f8fbff; }
    .mitra-activity-alert {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        border-radius: 18px;
        padding: 15px 16px;
        margin-bottom: 14px;
        border: 1px solid transparent;
        opacity: 0;
        animation: mitraActivityIn .45s cubic-bezier(.22,.61,.36,1) .05s both;
    }
    .mitra-activity-alert.is-warning { color: #92400e; background: #fff7ed; border-color: #fed7aa; }
    .mitra-activity-alert.is-safe { color: #047857; background: #ecfdf5; border-color: #bbf7d0; }
    .mitra-activity-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 11px;
    }
    .mitra-activity-tile {
        position: relative;
        overflow: hidden;
        border: 1px solid #e5eefb;
        border-radius: 18px;
        background: #fff;
        padding: 14px;
        opacity: 0;
        animation: mitraActivityIn .45s cubic-bezier(.22,.61,.36,1) both;
        animation-delay: calc(.12s + var(--i, 0) * .06s);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .mitra-activity-tile::before {
        content: "";
        position: absolute;
        inset: 0 0 auto;
        height: 4px;
        background: var(--accent, #2563eb);
    }
    .mitra-activity-tile:hover {
        transform: translateY(-4px);
        border-color: #cfe1ff;
        box-shadow: 0 16px 32px rgba(15,47,87,.11);
    }
    .mitra-activity-tile .tile-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
    }
    .mitra-activity-tile .tile-icon {
        width: 34px;
        height: 34px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--soft, #eff6ff);
        color: var(--accent, #2563eb);
    }
    .mitra-activity-tile .label {
        color: #64748b;
        font-size: .7rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .mitra-activity-tile .num {
        color: #0f172a;
        font-size: 1.65rem;
        font-weight: 900;
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }
    .mitra-activity-list { display: grid; gap: 9px; margin-top: 14px; }
    .mitra-activity-task {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
        border-radius: 17px;
        border: 1px solid #eaf1fb;
        background: #fff;
        padding: 13px 14px 13px 15px;
        opacity: 0;
        animation: mitraActivityIn .45s cubic-bezier(.22,.61,.36,1) both;
        animation-delay: calc(.32s + var(--i, 0) * .06s);
        transition: transform .18s ease, box-shadow .18s ease;
    }
    .mitra-activity-task::before {
        content: "";
        position: absolute;
        left: 0;
        top: 14px;
        bottom: 14px;
        width: 4px;
        border-radius: 0 999px 999px 0;
        background: var(--accent, #2563eb);
    }
    .mitra-activity-task:hover { transform: translateX(4px); box-shadow: 0 12px 26px rgba(15,47,87,.09); }
    .mitra-activity-task .task-icon {
        width: 38px;
        height: 38px;
        border-radius: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        background: var(--soft, #dbeafe);
        color: var(--accent, #1d4ed8);
    }
    .mitra-activity-task.is-warning { --accent: #f59e0b; --soft: #fef3c7; }
    .mitra-activity-task.is-danger { --accent: #ef4444; --soft: #fee2e2; }
    .mitra-activity-task.is-safe { --accent: #10b981; --soft: #d1fae5; }
    .mitra-activity-task.is-info { --accent: #2563eb; --soft: #dbeafe; }
    .mitra-activity-pending {
        border: 1px dashed #bfdbfe;
        border-radius: 18px;
        background: #fff;
        padding: 13px;
        opacity: 0;
        animation: mitraActivityIn .45s cubic-bezier(.22,.61,.36,1) .58s both;
    }
    .mitra-activity-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 0;
        border-bottom: 1px solid #eaf1fb;
    }
    .mitra-activity-row:last-child { border-bottom: 0; }
    .mitra-activity-row-icon {
        width: 34px;
        height: 34px;
        border-radius: 11px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        color: #2563eb;
        background: #eff6ff;
    }
    .mitra-min-w-0 { min-width: 0; }
    .mitra-activity-modal .modal-footer { border-top: 1px solid #eaf1fb; }
    @media (max-width: 767.98px) {
        .mitra-activity-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .mitra-hero { align-items: flex-start !important; flex-direction: column; }
    }
    @media (max-width: 575.98px) {
        .mitra-activity-grid { grid-template-columns: 1fr; }
        .mitra-activity-head { padding: 20px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .mitra-hero,
        .mitra-hero::before,
        .mitra-hero::after,
        .stat-card,
        .stat-card::before,
        .stat-card::after,
        .stat-icon,
        .stat-card:hover .stat-icon,
        .modern-table-card,
        .mitra-activity-head::after,
        .mitra-activity-icon,
        .mitra-activity-alert,
        .mitra-activity-tile,
        .mitra-activity-task,
        .mitra-activity-pending {
            animation: none !important;
        }
        .stat-card:hover {
            transform: none;
        }
    }
</style>
@endpush

    @if(!$vendor)
        <div class="alert alert-warning border-0 bg-warning alert-dismissible fade show">
            <div class="text-dark">
                <i class="bi bi-exclamation-triangle"></i> <strong>Akun Anda belum terhubung dengan data Mitra/Penyedia.</strong>
                Hubungi Operator BLU untuk menghubungkan akun Anda.
            </div>
        </div>
    @else
        @php
            $isMitraJasaPortal = $isMitraJasaPortal ?? false;
            $activity = $mitraActivity ?? [];
            $activityAttention = (int) ($activity['unpaid_count'] ?? 0)
                + (int) ($activity['due_today_count'] ?? 0)
                + (int) ($activity['overdue_count'] ?? 0)
                + (int) ($activity['pending_proof_count'] ?? 0)
                + (int) ($activity['correction_proof_count'] ?? 0);
        @endphp
        @if($isMitraJasaPortal)
            <style>
                .layanan-tree-panel {
                    max-height: 420px;
                    overflow: auto;
                    background: #fff;
                    border: 1px solid #e5e7eb;
                    border-radius: 6px;
                    padding: 14px 18px;
                }
                .layanan-tree-node summary {
                    cursor: pointer;
                    list-style: none;
                }
                .layanan-tree-node summary::-webkit-details-marker {
                    display: none;
                }
                .layanan-tree-row {
                    display: flex;
                    align-items: flex-start;
                    gap: 6px;
                    color: #3469a4;
                    line-height: 1.8;
                    font-size: 15px;
                }
                .layanan-tree-row:hover .layanan-tree-title {
                    text-decoration: underline;
                }
                .layanan-tree-branch {
                    width: 12px;
                    height: 14px;
                    border-left: 1px solid #1f2937;
                    border-bottom: 1px solid #1f2937;
                    flex: 0 0 12px;
                    margin-top: 2px;
                }
                .layanan-tree-icon {
                    color: #2f669b;
                    width: 16px;
                    flex: 0 0 16px;
                }
                .layanan-tree-title {
                    flex: 1;
                }
                .layanan-tree-children {
                    margin-left: 16px;
                }
                .layanan-tree-leaf {
                    margin-bottom: 4px;
                }
                .layanan-tree-meta {
                    margin-left: 34px;
                }
            </style>
        @endif
        <div class="mitra-hero-shell">
            <div class="mitra-hero d-flex justify-content-between align-items-center">
                <span class="mitra-hero-contour"></span>
                <div class="mitra-hero-content">
                    <div class="small text-white-50 fw-bold text-uppercase mb-1">Portal Mitra Jasa</div>
                    <h4 class="mb-1 fw-bold text-white">Selamat Datang, {{ $vendor->nama_pihak ?? $vendor->nama_mitra }}!</h4>
                    <p class="mb-0 small text-white-50">Pantau status kontrak, layanan jasa, dan riwayat tagihan Anda dalam satu tempat.</p>
                </div>
                <div class="mitra-hero-content d-flex flex-wrap align-items-center justify-content-end gap-2 small text-white-50">
                    <button type="button" class="btn btn-warning btn-sm fw-bold mitra-activity-btn" data-bs-toggle="modal" data-bs-target="#mitraActivityModal">
                        <i class="bi bi-bell me-1"></i>Aktivitas Hari Ini
                        @if($activityAttention > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $activityAttention }}</span>
                        @endif
                    </button>
                    <span class="mitra-hero-date d-none d-md-inline-flex">
                        <i class="bi bi-calendar3 me-1"></i>
                        {{ now()->format('d/m/Y') }}
                    </span>
                    @if(Auth::user()->hasRole('Mitra Jasa'))
                        <a href="{{ route('mitra.profile') }}" class="btn btn-sm btn-outline-light ms-2 px-3 rounded-pill">
                            <i class="bi bi-person-circle"></i> Profil
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="modal fade mitra-activity-modal" id="mitraActivityModal" tabindex="-1" aria-labelledby="mitraActivityModalLabel" aria-hidden="true" data-storage-key="{{ $activity['storage_key'] ?? 'mitra_activity_seen' }}">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="mitra-activity-head d-flex align-items-start gap-3">
                        <span class="mitra-activity-icon"><i class="bi bi-bell-fill"></i></span>
                        <div class="flex-grow-1">
                            <div class="mitra-activity-eyebrow">Peringatan Mitra</div>
                            <h5 class="modal-title mb-1" id="mitraActivityModalLabel">Aktivitas yang perlu dicek hari ini</h5>
                            <p class="mitra-activity-date mb-0 small">{{ $activity['date_label'] ?? now()->isoFormat('dddd, D MMMM Y') }}</p>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body mitra-activity-body p-4">
                        <div class="mitra-activity-alert {{ ($activity['needs_attention'] ?? false) ? 'is-warning' : 'is-safe' }}">
                            <i class="bi {{ ($activity['needs_attention'] ?? false) ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' }}"></i>
                            <div>
                                <div class="fw-bold">{{ ($activity['needs_attention'] ?? false) ? 'Ada tagihan atau pembayaran yang perlu dicek.' : 'Tidak ada peringatan penting untuk hari ini.' }}</div>
                                <div class="small">Popup ini otomatis muncul sekali per hari. Setelah ditutup, bisa dibuka lagi dari tombol Aktivitas Hari Ini.</div>
                            </div>
                        </div>

                        <div class="mitra-activity-grid">
                            <div class="mitra-activity-tile" style="--i: 0; --accent: #2563eb; --soft: #eff6ff;">
                                <div class="tile-top">
                                    <div class="label">Belum Dibayar</div>
                                    <span class="tile-icon"><i class="bi bi-receipt-cutoff"></i></span>
                                </div>
                                <div class="num mt-2">{{ number_format((int) ($activity['unpaid_count'] ?? 0), 0, ',', '.') }}</div>
                                <div class="small text-muted mt-1">Rp {{ number_format((float) ($activity['unpaid_nominal'] ?? 0), 0, ',', '.') }}</div>
                            </div>
                            <div class="mitra-activity-tile" style="--i: 1; --accent: #ef4444; --soft: #fef2f2;">
                                <div class="tile-top">
                                    <div class="label">Lewat Tempo</div>
                                    <span class="tile-icon"><i class="bi bi-calendar-x"></i></span>
                                </div>
                                <div class="num mt-2">{{ number_format((int) ($activity['overdue_count'] ?? 0), 0, ',', '.') }}</div>
                                <div class="small text-muted mt-1">{{ number_format((int) ($activity['due_today_count'] ?? 0), 0, ',', '.') }} jatuh tempo hari ini</div>
                            </div>
                            <div class="mitra-activity-tile" style="--i: 2; --accent: #f59e0b; --soft: #fffbeb;">
                                <div class="tile-top">
                                    <div class="label">Bukti Transfer</div>
                                    <span class="tile-icon"><i class="bi bi-file-earmark-arrow-up"></i></span>
                                </div>
                                <div class="num mt-2">{{ number_format((int) ($activity['pending_proof_count'] ?? 0), 0, ',', '.') }}</div>
                                <div class="small text-muted mt-1">menunggu verifikasi</div>
                            </div>
                            <div class="mitra-activity-tile" style="--i: 3; --accent: #7c3aed; --soft: #f5f3ff;">
                                <div class="tile-top">
                                    <div class="label">Perlu Perbaikan</div>
                                    <span class="tile-icon"><i class="bi bi-pencil-square"></i></span>
                                </div>
                                <div class="num mt-2">{{ number_format((int) ($activity['correction_proof_count'] ?? 0), 0, ',', '.') }}</div>
                                <div class="small text-muted mt-1">{{ number_format((int) ($activity['due_soon_count'] ?? 0), 0, ',', '.') }} mendekati tempo</div>
                            </div>
                        </div>

                        <div class="mitra-activity-list">
                            <div class="mitra-activity-task {{ ((int) ($activity['unpaid_count'] ?? 0) > 0) ? 'is-warning' : 'is-safe' }}" style="--i: 0;">
                                <span class="task-icon"><i class="bi {{ ((int) ($activity['unpaid_count'] ?? 0) > 0) ? 'bi-credit-card' : 'bi-check2' }}"></i></span>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">Cek tagihan yang belum dibayar.</div>
                                    <div class="small text-muted">{{ number_format((int) ($activity['unpaid_count'] ?? 0), 0, ',', '.') }} tagihan masih terbuka di portal Anda.</div>
                                </div>
                            </div>
                            <div class="mitra-activity-task {{ ((int) ($activity['pending_proof_count'] ?? 0) > 0) ? 'is-info' : 'is-safe' }}" style="--i: 1;">
                                <span class="task-icon"><i class="bi {{ ((int) ($activity['pending_proof_count'] ?? 0) > 0) ? 'bi-hourglass-split' : 'bi-check2' }}"></i></span>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">Pantau bukti pembayaran yang sedang diverifikasi.</div>
                                    <div class="small text-muted">{{ number_format((int) ($activity['pending_proof_count'] ?? 0), 0, ',', '.') }} bukti transfer sedang dicek Admin Jasa.</div>
                                </div>
                            </div>
                            <div class="mitra-activity-task {{ ((int) ($activity['correction_proof_count'] ?? 0) > 0) ? 'is-danger' : 'is-safe' }}" style="--i: 2;">
                                <span class="task-icon"><i class="bi {{ ((int) ($activity['correction_proof_count'] ?? 0) > 0) ? 'bi-exclamation-octagon' : 'bi-shield-check' }}"></i></span>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">Perbaiki bukti pembayaran yang ditolak/perlu koreksi.</div>
                                    <div class="small text-muted">{{ number_format((int) ($activity['correction_proof_count'] ?? 0), 0, ',', '.') }} bukti pembayaran perlu tindak lanjut.</div>
                                </div>
                            </div>
                            <div class="mitra-activity-task {{ (((int) ($activity['due_today_count'] ?? 0) + (int) ($activity['overdue_count'] ?? 0)) > 0) ? 'is-danger' : 'is-safe' }}" style="--i: 3;">
                                <span class="task-icon"><i class="bi {{ (((int) ($activity['due_today_count'] ?? 0) + (int) ($activity['overdue_count'] ?? 0)) > 0) ? 'bi-calendar-x' : 'bi-check2' }}"></i></span>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">Prioritaskan tagihan jatuh tempo.</div>
                                    <div class="small text-muted">{{ number_format((int) ($activity['due_today_count'] ?? 0), 0, ',', '.') }} jatuh tempo hari ini, {{ number_format((int) ($activity['overdue_count'] ?? 0), 0, ',', '.') }} lewat tempo.</div>
                                </div>
                            </div>
                        </div>

                        @if(($activity['highlight_tagihan'] ?? collect())->isNotEmpty())
                            <div class="mitra-activity-pending mt-3">
                                <div class="fw-bold small text-uppercase text-muted mb-1">Prioritas tagihan</div>
                                @foreach($activity['highlight_tagihan'] as $item)
                                    @php
                                        $proofStatus = $item->latestPaymentProof?->status;
                                        $priorityLabel = match (true) {
                                            in_array($proofStatus, [
                                                \App\Models\TagihanJasaPaymentProof::STATUS_DITOLAK,
                                                \App\Models\TagihanJasaPaymentProof::STATUS_PERLU_PERBAIKAN,
                                            ], true) => 'Perlu Perbaikan',
                                            $proofStatus === \App\Models\TagihanJasaPaymentProof::STATUS_MENUNGGU || $item->status_pembayaran === 'menunggu_verifikasi' => 'Menunggu Verifikasi',
                                            $item->status_jatuh_tempo === 'MACET' => 'Macet',
                                            $item->status_jatuh_tempo === 'LEWAT_JATUH_TEMPO' => 'Lewat Tempo',
                                            $item->status_jatuh_tempo === 'JATUH_TEMPO_HARI_INI' => 'Jatuh Tempo Hari Ini',
                                            default => 'Belum Dibayar',
                                        };
                                        $priorityBadge = match ($priorityLabel) {
                                            'Macet' => 'bg-dark',
                                            'Lewat Tempo', 'Perlu Perbaikan' => 'bg-danger',
                                            'Jatuh Tempo Hari Ini' => 'bg-warning text-dark',
                                            'Menunggu Verifikasi' => 'bg-info text-dark',
                                            default => 'bg-primary',
                                        };
                                    @endphp
                                    <div class="mitra-activity-row">
                                        <div class="d-flex align-items-center gap-2 mitra-min-w-0">
                                            <span class="mitra-activity-row-icon"><i class="bi bi-receipt"></i></span>
                                            <div class="mitra-min-w-0">
                                                <div class="fw-semibold text-truncate">{{ $item->nomor_tagihan ?? '-' }}</div>
                                                <div class="small text-muted text-truncate">
                                                    {{ $item->tanggal_jatuh_tempo ? 'Jatuh tempo ' . $item->tanggal_jatuh_tempo->format('d/m/Y') : 'Belum ada jatuh tempo' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge {{ $priorityBadge }}">{{ $priorityLabel }}</span>
                                            @if($isMitraJasaPortal)
                                                <a href="{{ route('mitra.tagihan-jasa.show', $item->id) }}" class="btn btn-sm btn-primary rounded-pill fw-bold">Detail</a>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                        <a href="#mitraStatusPembayaran" class="btn btn-outline-primary"><i class="bi bi-receipt-cutoff me-1"></i>Lihat Tagihan</a>
                        @if(Auth::user()->hasRole('Mitra Jasa'))
                            <a href="{{ route('mitra.profile') }}" class="btn btn-primary"><i class="bi bi-person-circle me-1"></i>Profil Mitra</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary Cards --}}
        @include('_partials.sa-stat-card-style')
        <div class="row g-4 mb-4 sa-stat-row">
            <div class="col-xl-4 col-md-6 d-flex align-items-stretch">
                <div class="card sa-stat-card border-0 shadow-sm w-100"
                     style="--accent: #2563eb; --accent-bg: #eaf2ff; --accent-glow: rgba(37, 99, 235, .22); --accent-soft: rgba(37, 99, 235, .28);">
                    <span class="stat-glow"></span>
                    <span class="stat-shine"></span>
                    <span class="stat-ribbon"></span>
                    <div class="stat-accent"></div>
                    <div class="card-body ps-4">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="small fw-bold stat-label">Total Kontrak</div>
                                <div class="fs-3 fw-bold stat-value" style="color: #1e3a8a;">{{ number_format($totalKontrak, 0, ',', '.') }}</div>
                            </div>
                            <div class="stat-icon"><i class="bi bi-file-earmark-text"></i></div>
                        </div>
                        <div class="small text-muted mt-2">Seluruh kontrak Anda terdaftar</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 d-flex align-items-stretch">
                <div class="card sa-stat-card border-0 shadow-sm w-100"
                     style="--accent: #16a34a; --accent-bg: #ecfdf3; --accent-glow: rgba(22, 163, 74, .22); --accent-soft: rgba(22, 163, 74, .28);">
                    <span class="stat-glow"></span>
                    <span class="stat-shine"></span>
                    <span class="stat-ribbon"></span>
                    <div class="stat-accent"></div>
                    <div class="card-body ps-4">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="small fw-bold stat-label">Sudah Dibayar</div>
                                <div class="fs-3 fw-bold stat-value" style="color: #14532d;">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</div>
                            </div>
                            <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
                        </div>
                        <div class="small text-muted mt-2">Tagihan dengan status lunas</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 d-flex align-items-stretch">
                <div class="card sa-stat-card border-0 shadow-sm w-100"
                     style="--accent: #f59e0b; --accent-bg: #fff7ed; --accent-glow: rgba(245, 158, 11, .22); --accent-soft: rgba(245, 158, 11, .28);">
                    <span class="stat-glow"></span>
                    <span class="stat-shine"></span>
                    <span class="stat-ribbon"></span>
                    <div class="stat-accent"></div>
                    <div class="card-body ps-4">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="small fw-bold stat-label">Dalam Proses</div>
                                <div class="fs-3 fw-bold stat-value" style="color: #7c2d12;">Rp {{ number_format($totalPending, 0, ',', '.') }}</div>
                            </div>
                            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                        </div>
                        <div class="small text-muted mt-2">Menunggu pembayaran diselesaikan</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Contracts --}}
        <div class="card modern-table-card mb-5">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-file-earmark-text text-primary me-2"></i> Daftar {{ $isMitraJasaPortal ? 'Dokumen/Kontrak Jasa' : 'Kontrak Anda' }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table modern-table mb-0">
                        <thead>
                            <tr>
                                <th>No. Kontrak</th>
                                <th>Judul Pekerjaan</th>
                                <th>Masa Kontrak</th>
                                <th>Status</th>
                                @if($isMitraJasaPortal)
                                    <th class="text-end">Dokumen</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($contracts as $c)
                            <tr>
                                <td>{{ $isMitraJasaPortal ? ($c->nomor_kontrak ?: '-') : ($c->nomor_spk ?: '-') }}</td>
                                <td>{{ $isMitraJasaPortal ? ($c->nama_kontrak ?: $c->jenis_dokumen ?: 'Dokumen Mitra Jasa') : $c->nama_pekerjaan }}</td>
                                <td>
                                    {{ $c->tanggal_mulai ? \Carbon\Carbon::parse($c->tanggal_mulai)->format('d/m/Y') : '-' }}
                                    -
                                    {{ $c->tanggal_selesai ? \Carbon\Carbon::parse($c->tanggal_selesai)->format('d/m/Y') : '-' }}
                                </td>
                                <td><span class="badge bg-{{ $c->status_kontrak == 'AKTIF' ? 'success' : 'secondary' }}">{{ str_replace('_', ' ', $c->status_kontrak) }}</span></td>
                                @if($isMitraJasaPortal)
                                    <td class="text-end">
                                        @if($c->file_kontrak)
                                            <a href="{{ route('mitra.kontrak-jasa.download', $c) }}" class="btn btn-sm btn-light border fw-semibold">
                                                <i class="bi bi-download me-1"></i> Download
                                            </a>
                                        @else
                                            <span class="text-muted small">Tidak ada file</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                            @empty
                            <tr><td colspan="{{ $isMitraJasaPortal ? 5 : 4 }}" class="text-center text-muted py-3">Tidak ada kontrak.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Transactions / Payment Timeline --}}
        <div class="card modern-table-card mb-5" id="mitraStatusPembayaran">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-receipt-cutoff text-success me-2"></i> Status Pembayaran Anda</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table modern-table mb-0">
                        <thead>
                            <tr>
                                <th>No. Transaksi</th>
                                <th>Tanggal</th>
                                @if($isMitraJasaPortal)
                                    <th>Jatuh Tempo</th>
                                @endif
                                <th>Uraian</th>
                                <th>Status</th>
                                <th class="text-end">Bruto</th>
                                <th class="text-end">Potongan Pajak</th>
                                <th class="text-end">Netto</th>
                                @if($isMitraJasaPortal)
                                    <th class="text-end">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tagihan as $t)
                            @php
                                if ($isMitraJasaPortal) {
                                    $taxTotal = 0;
                                    $bruto = (float) ($t->total_tagihan ?? 0);
                                    $netto = $bruto;
                                    $uraian = $t->details->pluck('layananJasa.nama_layanan')->filter()->unique()->take(3)->implode(', ') ?: 'Tagihan PNBP Jasa';
                                    $sc = match($t->status) {
                                        'PUBLISHED' => 'warning',
                                        'LUNAS' => 'success',
                                        default => 'secondary'
                                    };
                                } else {
                                    $taxTotal = $t->potonganTagihan?->sum('jumlah_potongan') ?? 0;
                                    $bruto = (float) ($t->total_bruto ?? $t->total_netto ?? 0);
                                    $netto = (float) ($t->total_netto ?? 0);
                                    $uraian = $t->deskripsi ?? $t->uraian ?? 'Tagihan kontrak';
                                    $sc = match($t->status) {
                                        'DRAFT' => 'warning',
                                        'PENDING_REVIEW', 'PENDING_BENDAHARA' => 'info',
                                        'READY_FOR_SPP', 'DISETUJUI_PPK' => 'primary',
                                        'CAIR', 'SP2D', 'SELESAI' => 'success',
                                        'DITOLAK_PPK', 'REVISI', 'REVISI_BENDAHARA' => 'danger',
                                        default => 'secondary'
                                    };
                                }
                            @endphp
                            <tr>
                                <td>{{ $t->nomor_tagihan ?? '-' }}</td>
                                <td>{{ $t->tanggal_tagihan ? \Carbon\Carbon::parse($t->tanggal_tagihan)->format('d/m/Y') : optional($t->created_at)->format('d/m/Y') }}</td>
                                @if($isMitraJasaPortal)
                                    <td>
                                        {{ $t->tanggal_jatuh_tempo ? \Carbon\Carbon::parse($t->tanggal_jatuh_tempo)->format('d/m/Y') : '-' }}
                                        @if($t->status_jatuh_tempo === 'MACET')
                                            <div class="badge bg-dark">Macet</div>
                                        @elseif($t->status_jatuh_tempo === 'LEWAT_JATUH_TEMPO')
                                            <div class="badge bg-danger">Lewat tempo</div>
                                        @elseif($t->status_jatuh_tempo === 'MENDEKATI_JATUH_TEMPO')
                                            <div class="badge bg-warning text-dark">Mendekati</div>
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    {{ \Illuminate\Support\Str::limit($uraian, 60) }}
                                    @if($isMitraJasaPortal && $t->nomor_va)
                                        <div class="small text-muted">VA: {{ $t->nomor_va }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $sc }}">{{ str_replace('_', ' ', $t->status) }}</span>
                                    @if(in_array($t->status, ['CAIR', 'SP2D', 'SELESAI', 'LUNAS'], true))
                                        <i class="bi bi-check-circle-fill text-success ms-1"></i>
                                    @endif
                                </td>
                                <td class="text-end">Rp {{ number_format($bruto, 0, ',', '.') }}</td>
                                <td class="text-end text-danger">{{ $taxTotal > 0 ? '- Rp ' . number_format($taxTotal, 0, ',', '.') : '-' }}</td>
                                <td class="text-end fw-bold">Rp {{ number_format($netto, 0, ',', '.') }}</td>
                                @if($isMitraJasaPortal)
                                    <td class="text-end">
                                        <a href="{{ route('mitra.tagihan-jasa.show', $t->id) }}" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold shadow-sm">
                                            Detail
                                        </a>
                                    </td>
                                @endif
                            </tr>
                            @empty
                            <tr><td colspan="{{ $isMitraJasaPortal ? 9 : 7 }}" class="text-center text-muted py-3">Belum ada transaksi pembayaran.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($isMitraJasaPortal)
            <div class="card modern-table-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-diagram-3 text-info me-2"></i> Layanan Jasa Aktif</h5>
                    <a href="{{ route('mitra.layanan-aktif') }}" class="btn btn-sm btn-outline-primary fw-semibold rounded-pill px-3">Lihat Halaman Penuh</a>
                </div>
                <div class="card-body">
                    @if(($selectedLayananIds ?? []) === [])
                        <div class="text-muted">Belum ada layanan jasa aktif untuk akun mitra ini.</div>
                    @else
                        <div class="layanan-tree-panel">
                            @php
                                $childrenByParent = $layananTreeItems->groupBy(fn ($item) => $item->parent_id ?: 'root');
                            @endphp
                            @include('super_admin_jasa.mitra.partials.layanan-tree-readonly', [
                                'childrenByParent' => $childrenByParent,
                                'parentId' => 'root',
                                'depth' => 0,
                                'selectedLayananIds' => $selectedLayananIds,
                                'visibleLayananIds' => $visibleLayananIds,
                            ])
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Simple info box --}}
        <div class="alert alert-info border-0 bg-light-info">
            <i class="bi bi-info-circle"></i> Halaman ini menampilkan data kontrak dan pembayaran yang terkait dengan akun Anda (<strong>{{ $vendor->nama_pihak ?? $vendor->nama_mitra }}</strong>). 
            Untuk pertanyaan lebih lanjut, silakan hubungi Operator BLU.
        </div>
    @endif
@endsection

@push('script')
<script>
(function () {
    const activityModalEl = document.getElementById('mitraActivityModal');
    if (!activityModalEl || !window.bootstrap || !bootstrap.Modal) return;

    const storageKey = activityModalEl.dataset.storageKey || 'mitra_activity_seen';
    const modal = bootstrap.Modal.getOrCreateInstance(activityModalEl);
    let alreadySeen = false;

    try {
        alreadySeen = localStorage.getItem(storageKey) === '1';
    } catch (error) {
        alreadySeen = true;
    }

    if (!alreadySeen) {
        setTimeout(() => modal.show(), 650);
    }

    activityModalEl.addEventListener('hidden.bs.modal', function () {
        try {
            localStorage.setItem(storageKey, '1');
        } catch (error) {}
    });
})();
</script>
@endpush
