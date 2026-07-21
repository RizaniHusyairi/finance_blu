@extends('layouts.app')
@section('title', 'Dashboard AMC')

@section('content')
@php
    $durasiJam = function ($menit) {
        $menit = (int) $menit;
        return sprintf('%02d:%02d', intdiv($menit, 60), $menit % 60);
    };
    $permTotal = (int) $permohonanStat['menunggu'] + (int) $permohonanStat['disetujui'] + (int) $permohonanStat['ditolak'];
    $pct = fn ($n) => $permTotal > 0 ? round($n / $permTotal * 100) : 0;
    $briefing = $amcBriefing ?? [];
    $briefingAttention = (int) ($briefing['pending_permohonan_count'] ?? 0)
        + (int) ($briefing['draft_pemakaian_count'] ?? 0)
        + (((int) ($briefing['today_pemakaian_count'] ?? 0) === 0) ? 1 : 0);
    $autoChecklist = [
        [
            'done' => (int) ($briefing['today_pemakaian_count'] ?? 0) > 0,
            'label' => 'Catat pemakaian garbarata hari ini',
            'detail' => ((int) ($briefing['today_pemakaian_count'] ?? 0) > 0)
                ? 'Data hari ini sudah masuk ke dashboard.'
                : 'Belum ada catatan untuk tanggal hari ini.',
            'icon_done' => 'bi-check2',
            'icon_open' => 'bi-pencil-square',
            'tone' => 'primary',
        ],
        [
            'done' => (int) ($briefing['pending_permohonan_count'] ?? 0) === 0,
            'label' => 'Pantau permohonan non-schedule yang masih diajukan',
            'detail' => (int) ($briefing['pending_permohonan_count'] ?? 0) . ' permohonan sedang menunggu keputusan.',
            'icon_done' => 'bi-check2',
            'icon_open' => 'bi-hourglass-split',
            'tone' => 'warning',
        ],
        [
            'done' => (int) ($briefing['draft_pemakaian_count'] ?? 0) === 0,
            'label' => 'Rapikan draft pemakaian sebelum akhir shift',
            'detail' => (int) ($briefing['draft_pemakaian_count'] ?? 0) . ' draft pemakaian masih perlu dilengkapi.',
            'icon_done' => 'bi-clipboard-check',
            'icon_open' => 'bi-journal-text',
            'tone' => 'violet',
        ],
    ];
    $manualChecklist = [
        [
            'key' => 'briefing_read',
            'label' => 'Briefing shift sudah dibaca',
            'detail' => 'Tandai setelah arahan operasional hari ini sudah dipahami.',
            'icon' => 'bi-eye',
        ],
        [
            'key' => 'field_coordination',
            'label' => 'Koordinasi lapangan sudah dilakukan',
            'detail' => 'Tandai setelah koordinasi dengan petugas apron atau unit terkait selesai.',
            'icon' => 'bi-people',
        ],
        [
            'key' => 'end_shift_review',
            'label' => 'Cek akhir shift sudah disiapkan',
            'detail' => 'Tandai setelah data yang perlu ditutup/ditagih sudah dicek ulang.',
            'icon' => 'bi-clipboard-check',
        ],
    ];
    $autoChecklistDone = collect($autoChecklist)->where('done', true)->count();
    $checklistTotal = count($autoChecklist) + count($manualChecklist);
    $checklistInitialPct = $checklistTotal > 0 ? round($autoChecklistDone / $checklistTotal * 100) : 0;
@endphp

<style>
    /* ===== entrance animations ===== */
    @media (prefers-reduced-motion: reduce) {
        [class*="amc-"], [class*="amc-"] * { animation-duration: .001s !important; animation-delay: 0s !important; animation-iteration-count: 1 !important; transition-duration: .001s !important; }
    }
    @keyframes amcUp { from { opacity: 0; transform: translateY(22px); } to { opacity: 1; transform: none; } }
    @keyframes amcFloat { 0%,100% { transform: translateY(0) rotate(-2deg); } 50% { transform: translateY(-12px) rotate(-2deg); } }
    @keyframes amcSweep { 0% { transform: translateX(-130%) skewX(-18deg); opacity: 0; } 22% { opacity: .35; } 60%,100% { transform: translateX(240%) skewX(-18deg); opacity: 0; } }
    @keyframes amcOrb { 0%,100% { transform: translate3d(0,0,0) scale(1); opacity: .8; } 50% { transform: translate3d(-16px,12px,0) scale(1.12); opacity: 1; } }
    @keyframes amcPulse { 0%,100% { box-shadow: 0 0 0 0 rgba(37,99,235,.35); } 50% { box-shadow: 0 0 0 8px rgba(37,99,235,0); } }
    @keyframes amcItemPop { from { opacity: 0; transform: translateY(14px) scale(.985); } to { opacity: 1; transform: none; } }
    @keyframes amcBriefingIn { from { opacity: 0; transform: translateY(12px) scale(.98); } to { opacity: 1; transform: none; } }
    @keyframes amcBriefingIcon { 0%,100% { transform: scale(1); } 50% { transform: scale(1.08); } }
    .amc-rise { opacity: 0; animation: amcUp .65s cubic-bezier(.22,.61,.36,1) both; animation-delay: var(--d, 0s); }

    /* ===== hero ===== */
    .amc-hero {
        position: relative; overflow: hidden; border-radius: 24px; padding: 30px 32px; margin-bottom: 1.5rem;
        background: linear-gradient(125deg, #0b2545 0%, #15407e 48%, #1d65d6 100%);
        color: #fff; box-shadow: 0 24px 60px rgba(11, 37, 69, .35);
    }
    .amc-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(3, 15, 35, .42) 0%, rgba(3, 15, 35, .20) 44%, transparent 78%);
        pointer-events: none;
    }
    .amc-hero .orb { position: absolute; border-radius: 999px; filter: blur(2px); pointer-events: none; }
    .amc-hero .orb-1 { width: 260px; height: 260px; right: 6%; top: -120px; background: radial-gradient(circle, rgba(125,211,252,.45), transparent 68%); animation: amcOrb 6s ease-in-out infinite; }
    .amc-hero .orb-2 { width: 180px; height: 180px; right: 26%; bottom: -90px; background: radial-gradient(circle, rgba(251,191,36,.32), transparent 70%); animation: amcOrb 7.5s ease-in-out infinite reverse; }
    .amc-hero .sweep { position: absolute; inset: 0; width: 46%; background: linear-gradient(100deg, transparent, rgba(255,255,255,.16), transparent); animation: amcSweep 6s ease-in-out 1s infinite; pointer-events: none; }
    .amc-hero .plane { position: absolute; right: 30px; bottom: 14px; font-size: 5.4rem; color: rgba(255,255,255,.12); animation: amcFloat 5s ease-in-out infinite; }
    .amc-hero .eyebrow { letter-spacing: .14em; text-transform: uppercase; font-size: .72rem; font-weight: 800; color: #fbbf24; }
    .amc-hero h3 {
        color: #ffffff !important;
        font-weight: 900;
        margin: .3rem 0 .25rem;
        font-size: 1.7rem;
        line-height: 1.15;
        text-shadow: 0 2px 12px rgba(0,0,0,.28);
    }
    .amc-hero p {
        color: rgba(255,255,255,.88) !important;
        text-shadow: 0 1px 8px rgba(0,0,0,.18);
    }
    .amc-hero .hero-copy {
        position: relative;
        z-index: 1;
        min-width: 0;
    }
    .amc-hero .hero-actions {
        position: relative;
        z-index: 1;
    }
    .amc-hero .btn-amc { border-radius: 12px; font-weight: 700; transition: transform .15s ease, box-shadow .15s ease; }
    .amc-hero .btn-amc:hover { transform: translateY(-2px); }
    .amc-hero .btn-briefing { position: relative; border: 0; color: #0f172a; box-shadow: 0 10px 24px rgba(15,23,42,.18); }
    @media (max-width: 767.98px) {
        .amc-hero { padding: 24px 22px; }
        .amc-hero h3 { font-size: 1.35rem; overflow-wrap: anywhere; }
        .amc-hero::before { background: linear-gradient(180deg, rgba(3, 15, 35, .46), rgba(3, 15, 35, .18)); }
    }

    /* ===== KPI ===== */
    .amc-kpi { position: relative; overflow: hidden; border: 0; border-radius: 18px; background: #fff; box-shadow: 0 12px 30px rgba(15,47,87,.08); padding: 18px 20px; height: 100%; transition: transform .2s ease, box-shadow .2s ease; }
    .amc-kpi::before { content: ""; position: absolute; left: 0; top: 0; height: 100%; width: 5px; background: var(--accent, #2563eb); }
    .amc-kpi:hover { transform: translateY(-5px); box-shadow: 0 22px 44px rgba(15,47,87,.16); }
    .amc-kpi .kpi-icon { width: 48px; height: 48px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.45rem; }
    .amc-kpi .kpi-label { font-size: .72rem; letter-spacing: .07em; text-transform: uppercase; color: #64748b; font-weight: 800; }
    .amc-kpi .kpi-value { font-size: 1.9rem; font-weight: 800; color: #0f172a; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .amc-kpi .kpi-sub { font-size: .74rem; color: #94a3b8; }

    /* ===== panels ===== */
    .amc-panel { border: 0; border-radius: 20px; background: #fff; box-shadow: 0 12px 30px rgba(15,47,87,.08); overflow: hidden; }
    .amc-panel .panel-head { padding: 16px 20px; border-bottom: 1px solid #eef2f7; font-weight: 800; color: #0f172a; }
    .amc-legend-row { display: flex; align-items: center; gap: 12px; border-radius: 14px; padding: 12px 14px; transition: background .15s ease; }
    .amc-legend-row:hover { background: #f8fafc; }
    .amc-legend-dot { width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; font-size: 1rem; }

    /* ===== tables ===== */
    .amc-table th { background: #f8fafc; color: #475569; text-transform: uppercase; font-size: .67rem; letter-spacing: .05em; white-space: nowrap; border: 0; }
    .amc-table td { vertical-align: middle; font-size: .85rem; border-color: #f1f5f9; }
    .amc-table tbody tr { transition: background .12s ease; }
    .amc-table tbody tr:hover { background: #f8fbff; }
    .amc-ava { width: 28px; height: 28px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: .72rem; font-weight: 800; color: #1d4ed8; background: #eff6ff; }
    .amc-link { font-size: .78rem; font-weight: 700; text-decoration: none; }

    /* ===== activity cards ===== */
    .amc-feed { display: grid; gap: 12px; padding: 14px; background: linear-gradient(180deg, #f8fbff 0%, #fff 100%); }
    .amc-activity-card {
        position: relative; display: flex; align-items: center; gap: 13px; min-height: 78px; padding: 14px 16px 14px 18px;
        border: 1px solid #eaf1fb; border-radius: 16px; background: #fff; box-shadow: 0 10px 22px rgba(15,47,87,.06);
        opacity: 0; animation: amcItemPop .48s cubic-bezier(.22,.61,.36,1) both; animation-delay: calc(var(--i, 0) * .065s);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .amc-activity-card::before {
        content: ""; position: absolute; left: 0; top: 14px; bottom: 14px; width: 4px; border-radius: 0 999px 999px 0;
        background: var(--accent, #2563eb);
    }
    .amc-activity-card:hover { transform: translateY(-4px); border-color: #cfe1ff; box-shadow: 0 18px 34px rgba(15,47,87,.12); }
    .amc-activity-icon {
        flex: 0 0 auto; width: 44px; height: 44px; border-radius: 13px; display: inline-flex; align-items: center; justify-content: center;
        color: var(--accent, #2563eb); background: var(--soft, #eff6ff); font-weight: 900;
    }
    .amc-activity-main { min-width: 0; flex: 1; }
    .amc-activity-title { color: #0f172a; font-size: .88rem; font-weight: 850; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .amc-activity-date { color: #64748b; font-size: .73rem; font-weight: 700; white-space: nowrap; }
    .amc-chip-row { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 9px; }
    .amc-chip {
        display: inline-flex; align-items: center; gap: 5px; max-width: 100%; padding: 5px 9px; border-radius: 999px;
        background: #f8fafc; color: #475569; font-size: .72rem; font-weight: 750; line-height: 1;
    }
    .amc-chip.flight { background: #eef6ff; color: #1d4ed8; }
    .amc-chip.route { background: #f8fafc; color: #475569; }
    .amc-chip.rentang { background: #ecfdf5; color: #047857; }
    .amc-request-card { align-items: flex-start; --accent: #7c3aed; --soft: #f5f3ff; }
    .amc-request-card .badge { white-space: nowrap; }
    .amc-request-meta { color: #64748b; font-size: .75rem; font-weight: 650; }
    .amc-min-w-0 { min-width: 0; }
    .amc-empty-state { min-height: 150px; display: grid; place-items: center; text-align: center; color: #94a3b8; padding: 26px 16px; }
    .amc-empty-state i { width: 46px; height: 46px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; background: #f1f5f9; color: #64748b; font-size: 1.3rem; margin-bottom: 9px; }
    @media (max-width: 575.98px) {
        .amc-activity-card { align-items: flex-start; padding-right: 13px; }
        .amc-activity-date { width: 100%; margin-top: 4px; }
        .amc-activity-title { white-space: normal; }
    }

    /* ===== briefing modal ===== */
    .amc-briefing-modal .modal-dialog { max-width: 760px; }
    .amc-briefing-modal .modal-content { border: 0; border-radius: 22px; overflow: hidden; box-shadow: 0 28px 80px rgba(15,23,42,.28); }
    .amc-briefing-head {
        position: relative; padding: 24px 26px;
        background: linear-gradient(125deg, #08234a 0%, #174195 48%, #2563eb 100%);
        color: #fff; overflow: hidden;
    }
    .amc-briefing-head::before { content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, rgba(2,8,23,.42), rgba(2,8,23,.10) 62%, transparent); pointer-events: none; }
    .amc-briefing-head::after { content: ""; position: absolute; inset: 0; background: linear-gradient(100deg, transparent, rgba(255,255,255,.13), transparent); transform: translateX(-55%); animation: amcSweep 6s ease-in-out infinite; pointer-events: none; }
    .amc-briefing-head > * { position: relative; z-index: 1; }
    .amc-briefing-head .modal-title { color: #fff !important; font-weight: 900; letter-spacing: 0; text-shadow: 0 2px 14px rgba(0,0,0,.24); }
    .amc-briefing-head .briefing-date { color: rgba(255,255,255,.84) !important; text-shadow: 0 1px 8px rgba(0,0,0,.18); }
    .amc-briefing-icon { width: 50px; height: 50px; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; background: rgba(255,255,255,.16); color: #fff; font-size: 1.35rem; box-shadow: inset 0 0 0 1px rgba(255,255,255,.16); animation: amcBriefingIcon 2.6s ease-in-out infinite; }
    .amc-briefing-eyebrow { color: #fbbf24; font-size: .7rem; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; }
    .amc-briefing-body { background: #f8fbff; }
    .amc-briefing-status {
        display: flex; gap: 12px; align-items: flex-start; border-radius: 18px; padding: 15px 16px; margin-bottom: 14px;
        border: 1px solid transparent; opacity: 0; animation: amcBriefingIn .45s cubic-bezier(.22,.61,.36,1) .05s both;
    }
    .amc-briefing-status.is-warning { background: #fff7ed; color: #92400e; border-color: #fed7aa; }
    .amc-briefing-status.is-safe { background: #ecfdf5; color: #047857; border-color: #bbf7d0; }
    .amc-briefing-status i { font-size: 1.2rem; margin-top: 1px; }
    .amc-briefing-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 11px; }
    .amc-briefing-tile {
        position: relative; overflow: hidden; border: 1px solid #e5eefb; border-radius: 18px; padding: 14px; background: #fff;
        opacity: 0; animation: amcBriefingIn .45s cubic-bezier(.22,.61,.36,1) both; animation-delay: calc(.12s + var(--i, 0) * .06s);
        transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    }
    .amc-briefing-tile::before { content: ""; position: absolute; inset: 0 0 auto; height: 4px; background: var(--accent, #2563eb); }
    .amc-briefing-tile:hover { transform: translateY(-4px); border-color: #cfe1ff; box-shadow: 0 16px 32px rgba(15,47,87,.11); }
    .amc-briefing-tile .tile-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .amc-briefing-tile .tile-icon { width: 34px; height: 34px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; background: var(--soft, #eff6ff); color: var(--accent, #2563eb); }
    .amc-briefing-tile .num { color: #0f172a; font-size: 1.7rem; font-weight: 900; line-height: 1; font-variant-numeric: tabular-nums; }
    .amc-briefing-tile .label { color: #64748b; font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
    .amc-briefing-list { display: grid; gap: 9px; margin-top: 14px; }
    .amc-briefing-task {
        position: relative; display: flex; align-items: center; gap: 12px; border-radius: 17px; padding: 13px 14px 13px 15px; background: #fff; border: 1px solid #eaf1fb;
        opacity: 0; animation: amcBriefingIn .45s cubic-bezier(.22,.61,.36,1) both; animation-delay: calc(.26s + var(--i, 0) * .06s);
        transition: transform .18s ease, box-shadow .18s ease;
    }
    .amc-briefing-task::before { content: ""; position: absolute; left: 0; top: 14px; bottom: 14px; width: 4px; border-radius: 0 999px 999px 0; background: var(--accent, #2563eb); }
    .amc-briefing-task:hover { transform: translateX(4px); box-shadow: 0 12px 26px rgba(15,47,87,.09); }
    .amc-briefing-task .task-icon { width: 38px; height: 38px; border-radius: 13px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; background: var(--soft, #dbeafe); color: var(--accent, #1d4ed8); }
    .amc-briefing-task.is-warning { --accent: #f59e0b; --soft: #fef3c7; }
    .amc-briefing-task.is-safe { --accent: #10b981; --soft: #d1fae5; }
    .amc-briefing-task.is-info { --accent: #2563eb; --soft: #dbeafe; }
    .amc-briefing-checkpanel {
        margin-top: 14px; border: 1px solid #e5eefb; border-radius: 20px; padding: 15px; background: #fff;
        opacity: 0; animation: amcBriefingIn .45s cubic-bezier(.22,.61,.36,1) .26s both;
    }
    .amc-check-progress-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
    .amc-check-progress-title { color: #0f172a; font-weight: 900; }
    .amc-check-progress-sub { color: #64748b; font-size: .78rem; }
    .amc-check-progress-label {
        flex: 0 0 auto; border-radius: 999px; padding: 6px 10px; background: #eff6ff; color: #1d4ed8;
        font-size: .73rem; font-weight: 900; font-variant-numeric: tabular-nums;
    }
    .amc-check-progress-label.is-complete { background: #dcfce7; color: #047857; }
    .amc-check-progress-track { height: 9px; overflow: hidden; border-radius: 999px; background: #eaf1fb; }
    .amc-check-progress-bar {
        height: 100%; width: 0; border-radius: inherit; background: linear-gradient(90deg, #2563eb, #10b981);
        transition: width .28s ease;
    }
    .amc-check-list { display: grid; gap: 9px; margin-top: 13px; }
    .amc-check-item {
        position: relative; display: flex; align-items: center; gap: 12px; min-height: 64px; border: 1px solid #eaf1fb;
        border-radius: 17px; padding: 12px 13px; background: #f8fbff; transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }
    .amc-check-item:hover { transform: translateX(3px); border-color: #cfe1ff; box-shadow: 0 12px 24px rgba(15,47,87,.08); }
    .amc-check-toggle {
        width: 36px; height: 36px; border-radius: 13px; display: inline-flex; align-items: center; justify-content: center;
        position: relative; flex: 0 0 auto; background: #fff7ed; color: #b45309; border: 1px solid #fed7aa; transition: all .18s ease;
    }
    .amc-check-meta { min-width: 0; flex: 1; }
    .amc-check-title { color: #0f172a; font-weight: 800; line-height: 1.25; }
    .amc-check-detail { color: #64748b; font-size: .77rem; margin-top: 2px; }
    .amc-check-tag { flex: 0 0 auto; font-size: .68rem; font-weight: 900; border-radius: 999px; padding: 5px 8px; background: #e2e8f0; color: #475569; }
    .amc-check-input { position: absolute; opacity: 0; pointer-events: none; }
    .amc-check-item.is-auto.is-done,
    .amc-check-item.is-manual.is-done { background: #f0fdf4; border-color: #bbf7d0; }
    .amc-check-item.is-auto.is-done .amc-check-toggle,
    .amc-check-item.is-manual.is-done .amc-check-toggle { background: #dcfce7; color: #047857; border-color: #bbf7d0; }
    .amc-check-item.is-auto.is-open { background: #fffbeb; border-color: #fde68a; }
    .amc-check-item.is-manual { cursor: pointer; background: #fff; }
    .amc-check-item.is-manual .amc-check-toggle i { transition: all .18s ease; }
    .amc-check-item.is-manual .amc-check-toggle .check-icon { position: absolute; opacity: 0; transform: scale(.7); }
    .amc-check-item.is-manual .amc-check-toggle .manual-icon { opacity: 1; transform: scale(1); }
    .amc-check-item.is-manual.is-done .amc-check-toggle .manual-icon { opacity: 0; transform: scale(.7); }
    .amc-check-item.is-manual.is-done .amc-check-toggle .check-icon { opacity: 1; transform: scale(1); }
    .amc-check-item.is-manual.is-done .amc-check-title { text-decoration: line-through; text-decoration-thickness: 2px; text-decoration-color: rgba(16,185,129,.45); }
    .amc-check-divider { color: #94a3b8; font-size: .68rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; margin: 4px 0 -2px; }
    .amc-briefing-pending { border: 1px dashed #bfdbfe; border-radius: 18px; padding: 13px; background: #fff; opacity: 0; animation: amcBriefingIn .45s cubic-bezier(.22,.61,.36,1) .48s both; }
    .amc-briefing-pending-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 11px 0; border-bottom: 1px solid #eaf1fb; }
    .amc-briefing-pending-row:last-child { border-bottom: 0; }
    .amc-briefing-pending-row .pending-icon { width: 34px; height: 34px; border-radius: 11px; display: inline-flex; align-items: center; justify-content: center; background: #eff6ff; color: #2563eb; flex: 0 0 auto; }
    .amc-briefing-modal .modal-footer { border-top: 1px solid #eaf1fb; }
    @media (max-width: 575.98px) {
        .amc-briefing-grid { grid-template-columns: 1fr; }
        .amc-briefing-head { padding: 20px; }
        .amc-check-progress-head,
        .amc-check-item { align-items: flex-start; }
        .amc-check-item { flex-wrap: wrap; }
        .amc-check-tag { margin-left: 48px; }
    }
</style>

{{-- ===== HERO ===== --}}
<div class="amc-hero amc-rise d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <span class="orb orb-1"></span><span class="orb orb-2"></span><span class="sweep"></span>
    <i class="bi bi-airplane-engines plane"></i>
    <div class="hero-copy">
        <div class="eyebrow"><i class="bi bi-broadcast-pin me-1"></i>AMC &middot; Apron Movement Control</div>
        <h3>Halo, {{ $user?->name ?? 'Operator AMC' }} <span style="display:inline-block; animation: amcFloat 3s ease-in-out infinite;">👋</span></h3>
        <p class="mb-0 small opacity-75">Ringkasan operasional garbarata &amp; permohonan non-schedule — periode <strong>{{ $bulanLabel }}</strong>.</p>
    </div>
    <div class="hero-actions d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-light btn-amc btn-briefing" data-bs-toggle="modal" data-bs-target="#amcBriefingModal">
            <i class="bi bi-bell me-1"></i>Briefing Hari Ini
            @if($briefingAttention > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $briefingAttention }}</span>
            @endif
        </button>
        @if($canCreate)
            <a href="{{ route('pemakaian-garbarata.create') }}" class="btn btn-warning btn-amc"><i class="bi bi-plus-lg me-1"></i>Catat Pemakaian</a>
            <a href="{{ route('permohonan-non-schedule.create') }}" class="btn btn-outline-light btn-amc"><i class="bi bi-file-earmark-plus me-1"></i>Buat Permohonan</a>
        @endif
    </div>
</div>

{{-- ===== BRIEFING MODAL ===== --}}
<div class="modal fade amc-briefing-modal" id="amcBriefingModal" tabindex="-1" aria-labelledby="amcBriefingModalLabel" aria-hidden="true" data-storage-key="{{ $briefing['storage_key'] ?? 'amc_briefing_seen' }}" data-checklist-key="{{ $briefing['checklist_key'] ?? 'amc_briefing_checklist' }}">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="amc-briefing-head d-flex align-items-start gap-3">
                <span class="amc-briefing-icon"><i class="bi bi-bell-fill"></i></span>
                <div class="flex-grow-1">
                    <div class="amc-briefing-eyebrow">Briefing AMC</div>
                    <h5 class="modal-title fw-bold mb-1" id="amcBriefingModalLabel">Yang perlu dicek hari ini</h5>
                    <p class="briefing-date mb-0 small">{{ $briefing['date_label'] ?? now()->isoFormat('dddd, D MMMM Y') }}</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body amc-briefing-body p-4">
                <div class="amc-briefing-status {{ ($briefing['needs_attention'] ?? false) ? 'is-warning' : 'is-safe' }}">
                    <i class="bi {{ ($briefing['needs_attention'] ?? false) ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' }}"></i>
                    <div>
                        <div class="fw-bold">{{ ($briefing['needs_attention'] ?? false) ? 'Ada beberapa hal yang perlu dipantau.' : 'Operasional hari ini terlihat aman.' }}</div>
                        <div class="small">Prioritaskan catatan pemakaian, pantau surat diajukan, dan rapikan draft sebelum akhir shift.</div>
                    </div>
                </div>

                <div class="amc-briefing-grid">
                    <div class="amc-briefing-tile" style="--i: 0; --accent: #2563eb; --soft: #eff6ff;">
                        <div class="tile-top">
                            <div class="label">Pemakaian Hari Ini</div>
                            <span class="tile-icon"><i class="bi bi-airplane-engines"></i></span>
                        </div>
                        <div class="num mt-2">{{ (int) ($briefing['today_pemakaian_count'] ?? 0) }}</div>
                        <div class="small text-muted mt-1">{{ (int) ($briefing['today_rentang'] ?? 0) }} rentang tercatat</div>
                    </div>
                    <div class="amc-briefing-tile" style="--i: 1; --accent: #f59e0b; --soft: #fffbeb;">
                        <div class="tile-top">
                            <div class="label">Permohonan Diajukan</div>
                            <span class="tile-icon"><i class="bi bi-send-check"></i></span>
                        </div>
                        <div class="num mt-2">{{ (int) ($briefing['pending_permohonan_count'] ?? 0) }}</div>
                        <div class="small text-muted mt-1">perlu dipantau statusnya</div>
                    </div>
                    <div class="amc-briefing-tile" style="--i: 2; --accent: #7c3aed; --soft: #f5f3ff;">
                        <div class="tile-top">
                            <div class="label">Draft Pemakaian</div>
                            <span class="tile-icon"><i class="bi bi-journal-text"></i></span>
                        </div>
                        <div class="num mt-2">{{ (int) ($briefing['draft_pemakaian_count'] ?? 0) }}</div>
                        <div class="small text-muted mt-1">{{ (int) ($briefing['siap_pemakaian_count'] ?? 0) }} siap ditagih</div>
                    </div>
                </div>

                <div class="amc-briefing-checkpanel" data-total-checklist="{{ $checklistTotal }}" data-auto-done="{{ $autoChecklistDone }}">
                    <div class="amc-check-progress-head">
                        <div>
                            <div class="amc-check-progress-title">To-do Briefing Hari Ini</div>
                            <div class="amc-check-progress-sub">Checklist otomatis ikut data sistem, checklist manual tersimpan untuk hari ini.</div>
                        </div>
                        <span class="amc-check-progress-label" data-checklist-progress-label>{{ $autoChecklistDone }}/{{ $checklistTotal }} selesai</span>
                    </div>
                    <div class="amc-check-progress-track" aria-hidden="true">
                        <div class="amc-check-progress-bar" data-checklist-progress style="width: {{ $checklistInitialPct }}%;"></div>
                    </div>

                    <div class="amc-check-list">
                        <div class="amc-check-divider">Otomatis dari sistem</div>
                        @foreach($autoChecklist as $item)
                            <div class="amc-check-item is-auto {{ $item['done'] ? 'is-done' : 'is-open' }}" data-auto-done="{{ $item['done'] ? '1' : '0' }}">
                                <span class="amc-check-toggle">
                                    <i class="bi {{ $item['done'] ? $item['icon_done'] : $item['icon_open'] }}"></i>
                                </span>
                                <div class="amc-check-meta">
                                    <div class="amc-check-title">{{ $item['label'] }}</div>
                                    <div class="amc-check-detail">{{ $item['detail'] }}</div>
                                </div>
                                <span class="amc-check-tag">{{ $item['done'] ? 'Selesai otomatis' : 'Belum selesai' }}</span>
                            </div>
                        @endforeach

                        <div class="amc-check-divider">Checklist manual shift</div>
                        @foreach($manualChecklist as $item)
                            <label class="amc-check-item is-manual" data-checklist-item>
                                <input type="checkbox" class="amc-check-input" data-check-key="{{ $item['key'] }}">
                                <span class="amc-check-toggle">
                                    <i class="bi {{ $item['icon'] }} manual-icon"></i>
                                    <i class="bi bi-check2 check-icon"></i>
                                </span>
                                <div class="amc-check-meta">
                                    <div class="amc-check-title">{{ $item['label'] }}</div>
                                    <div class="amc-check-detail">{{ $item['detail'] }}</div>
                                </div>
                                <span class="amc-check-tag">Manual</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                @if(($briefing['latest_pending_permohonan'] ?? collect())->isNotEmpty())
                    <div class="amc-briefing-pending mt-3">
                        <div class="fw-bold small text-uppercase text-muted mb-1">Permohonan yang sedang diajukan</div>
                        @foreach($briefing['latest_pending_permohonan'] as $p)
                            <div class="amc-briefing-pending-row">
                                <div class="d-flex align-items-center gap-2 amc-min-w-0">
                                    <span class="pending-icon"><i class="bi bi-file-earmark-text"></i></span>
                                    <div class="amc-min-w-0">
                                        <div class="fw-semibold text-truncate">{{ $p->nomor_surat ?? '-' }}</div>
                                        <div class="small text-muted text-truncate">{{ $p->mitra?->nama_mitra ?? '-' }}</div>
                                    </div>
                                </div>
                                <span class="badge {{ $p->status_badge }}">{{ $p->status_label }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="{{ route('permohonan-non-schedule.index') }}" class="btn btn-outline-primary"><i class="bi bi-list-check me-1"></i>Lihat Permohonan</a>
                @if($canCreate)
                    <a href="{{ route('pemakaian-garbarata.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Catat Pemakaian</a>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ===== KPI ===== --}}
<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="amc-kpi amc-rise" style="--accent:#2563eb; --d:.05s">
            <span class="kpi-icon" style="background:#eff6ff;color:#1d4ed8;"><i class="bi bi-airplane-engines"></i></span>
            <div class="kpi-label mt-2">Penerbangan</div>
            <div class="kpi-value amc-count" data-target="{{ (int) $kpi['penerbangan'] }}">0</div>
            <div class="kpi-sub">bulan ini</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="amc-kpi amc-rise" style="--accent:#f59e0b; --d:.1s">
            <span class="kpi-icon" style="background:#fef3c7;color:#b45309;"><i class="bi bi-calendar3-range"></i></span>
            <div class="kpi-label mt-2">Total Rentang</div>
            <div class="kpi-value amc-count" data-target="{{ (int) $kpi['rentang'] }}">0</div>
            <div class="kpi-sub">batch garbarata</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="amc-kpi amc-rise" style="--accent:#10b981; --d:.15s">
            <span class="kpi-icon" style="background:#ecfdf5;color:#059669;"><i class="bi bi-clock-history"></i></span>
            <div class="kpi-label mt-2">Total Durasi</div>
            <div class="kpi-value amc-count" data-target="{{ (int) $kpi['durasi_menit'] }}" data-fmt="time">00:00</div>
            <div class="kpi-sub">jam pemakaian</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="amc-kpi amc-rise" style="--accent:#7c3aed; --d:.2s">
            <span class="kpi-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="bi bi-airplane-fill"></i></span>
            <div class="kpi-label mt-2">Maskapai</div>
            <div class="kpi-value amc-count" data-target="{{ (int) $kpi['maskapai'] }}">0</div>
            <div class="kpi-sub">dilayani bulan ini</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    {{-- Tren 6 bulan (area chart) --}}
    <div class="col-lg-7">
        <div class="amc-panel amc-rise h-100" style="--d:.15s">
            <div class="panel-head d-flex justify-content-between align-items-center">
                <span><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Tren Penerbangan (6 Bulan)</span>
                <span class="badge bg-primary-subtle text-primary-emphasis">per bulan</span>
            </div>
            <div class="px-2 py-2"><div id="amcTrendChart"></div></div>
        </div>
    </div>

    {{-- Permohonan Non-Schedule (donut) --}}
    <div class="col-lg-5">
        <div class="amc-panel amc-rise h-100" style="--d:.22s">
            <div class="panel-head d-flex justify-content-between align-items-center">
                <span><i class="bi bi-flight-takeoff me-2 text-primary"></i>Status Permohonan Non-Schedule</span>
                <a href="{{ route('permohonan-non-schedule.index') }}" class="amc-link">Lihat semua <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="p-3">
                <div class="row align-items-center g-2">
                    <div class="col-5"><div id="amcDonut"></div></div>
                    <div class="col-7 d-flex flex-column gap-1">
                        <div class="amc-legend-row" style="background:#fffbeb;">
                            <span class="amc-legend-dot" style="background:#fde68a;color:#b45309;"><i class="bi bi-hourglass-split"></i></span>
                            <div class="flex-grow-1"><div class="small fw-semibold text-dark">Menunggu review</div></div>
                            <div class="text-end"><div class="fw-bold">{{ $permohonanStat['menunggu'] }}</div><div class="kpi-sub">{{ $pct($permohonanStat['menunggu']) }}%</div></div>
                        </div>
                        <div class="amc-legend-row" style="background:#ecfdf5;">
                            <span class="amc-legend-dot" style="background:#a7f3d0;color:#047857;"><i class="bi bi-check2-circle"></i></span>
                            <div class="flex-grow-1"><div class="small fw-semibold text-dark">Disetujui</div></div>
                            <div class="text-end"><div class="fw-bold">{{ $permohonanStat['disetujui'] }}</div><div class="kpi-sub">{{ $pct($permohonanStat['disetujui']) }}%</div></div>
                        </div>
                        <div class="amc-legend-row" style="background:#fef2f2;">
                            <span class="amc-legend-dot" style="background:#fecaca;color:#b91c1c;"><i class="bi bi-x-circle"></i></span>
                            <div class="flex-grow-1"><div class="small fw-semibold text-dark">Ditolak</div></div>
                            <div class="text-end"><div class="fw-bold">{{ $permohonanStat['ditolak'] }}</div><div class="kpi-sub">{{ $pct($permohonanStat['ditolak']) }}%</div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Pemakaian terbaru --}}
    <div class="col-lg-7">
        <div class="amc-panel amc-rise" style="--d:.18s">
            <div class="panel-head d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-check me-2 text-primary"></i>Pemakaian Garbarata Terbaru</span>
                <a href="{{ route('pemakaian-garbarata.index') }}" class="amc-link">Lihat semua <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="amc-feed">
                @forelse($recentPemakaian as $row)
                    <div class="amc-activity-card" style="--i: {{ $loop->index }}; --accent: #2563eb; --soft: #eff6ff;">
                        <span class="amc-activity-icon">{{ strtoupper(mb_substr($row->mitra?->nama_mitra ?? '?', 0, 2)) }}</span>
                        <div class="amc-activity-main">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div class="amc-activity-title">{{ $row->mitra?->nama_mitra ?? '-' }}</div>
                                <div class="amc-activity-date"><i class="bi bi-calendar3 me-1"></i>{{ $row->tanggal?->format('d/m/Y') }}</div>
                            </div>
                            <div class="amc-chip-row">
                                <span class="amc-chip flight"><i class="bi bi-airplane-engines"></i>{{ $row->nomor_penerbangan ?? '-' }}</span>
                                <span class="amc-chip route"><i class="bi bi-signpost-split"></i>{{ $row->route ?? '-' }}</span>
                                <span class="amc-chip rentang"><i class="bi bi-clock-history"></i>{{ $row->jumlah_rentang }} rentang</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="amc-empty-state">
                        <div>
                            <i class="bi bi-inbox"></i>
                            <div class="fw-semibold">Belum ada catatan pemakaian.</div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Permohonan terbaru --}}
    <div class="col-lg-5">
        <div class="amc-panel amc-rise" style="--d:.24s">
            <div class="panel-head"><i class="bi bi-clock-history me-2 text-primary"></i>Permohonan Terbaru</div>
            <div class="amc-feed">
                @forelse($recentPermohonan as $p)
                    <div class="amc-activity-card amc-request-card" style="--i: {{ $loop->index }};">
                        <span class="amc-activity-icon"><i class="bi bi-file-earmark-text"></i></span>
                        <div class="amc-activity-main">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="amc-min-w-0">
                                    <div class="amc-activity-title">{{ $p->nomor_surat ?? '-' }}</div>
                                    <div class="amc-request-meta">{{ $p->mitra?->nama_mitra ?? '-' }}</div>
                                </div>
                                <span class="badge {{ $p->status_badge }}">{{ $p->status_label }}</span>
                            </div>
                            <div class="amc-chip-row">
                                <span class="amc-chip route"><i class="bi bi-calendar3"></i>{{ $p->tanggal_surat?->format('d/m/Y') }}</span>
                                <span class="amc-chip flight"><i class="bi bi-send-check"></i>Non-schedule</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="amc-empty-state">
                        <div>
                            <i class="bi bi-inbox"></i>
                            <div class="fw-semibold">Belum ada permohonan.</div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script src="{{ URL::asset('build/plugins/apexchart/apexcharts.min.js') }}"></script>
<script>
(function () {
    // ===== Count-up KPI =====
    const toTime = v => { v = Math.round(v); return String(Math.floor(v / 60)).padStart(2, '0') + ':' + String(v % 60).padStart(2, '0'); };
    function countUp(el) {
        const target = parseFloat(el.dataset.target) || 0;
        const isTime = el.dataset.fmt === 'time';
        const dur = 1100, t0 = performance.now();
        function tick(now) {
            const p = Math.min(1, (now - t0) / dur);
            const eased = 1 - Math.pow(1 - p, 3);
            const val = target * eased;
            el.textContent = isTime ? toTime(val) : Math.round(val).toLocaleString('id-ID');
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    }
    document.querySelectorAll('.amc-count').forEach(el => setTimeout(() => countUp(el), 250));

    // ===== Briefing modal: auto once per user per day =====
    const briefingModalEl = document.getElementById('amcBriefingModal');
    if (briefingModalEl && window.bootstrap && bootstrap.Modal) {
        const storageKey = briefingModalEl.dataset.storageKey || 'amc_briefing_seen';
        const checklistKey = briefingModalEl.dataset.checklistKey || (storageKey + '_checklist');
        const modal = bootstrap.Modal.getOrCreateInstance(briefingModalEl);
        const checklistPanel = briefingModalEl.querySelector('.amc-briefing-checkpanel');
        const checklistInputs = Array.from(briefingModalEl.querySelectorAll('.amc-check-input[data-check-key]'));
        const progressBar = briefingModalEl.querySelector('[data-checklist-progress]');
        const progressLabel = briefingModalEl.querySelector('[data-checklist-progress-label]');
        let alreadySeen = false;
        let checklistState = {};

        try {
            alreadySeen = localStorage.getItem(storageKey) === '1';
        } catch (error) {
            alreadySeen = true;
        }

        try {
            checklistState = JSON.parse(localStorage.getItem(checklistKey) || '{}') || {};
        } catch (error) {
            checklistState = {};
        }

        function updateChecklistProgress() {
            if (!checklistPanel) return;

            const autoDone = briefingModalEl.querySelectorAll('.amc-check-item[data-auto-done="1"]').length;
            const manualDone = checklistInputs.filter(input => input.checked).length;
            const total = parseInt(checklistPanel.dataset.totalChecklist || '0', 10) || (autoDone + checklistInputs.length);
            const done = Math.min(total, autoDone + manualDone);
            const pct = total > 0 ? Math.round(done / total * 100) : 0;

            if (progressBar) {
                progressBar.style.width = pct + '%';
                progressBar.setAttribute('aria-valuenow', String(pct));
            }

            if (progressLabel) {
                progressLabel.textContent = done + '/' + total + ' selesai';
                progressLabel.classList.toggle('is-complete', done === total && total > 0);
            }
        }

        checklistInputs.forEach(input => {
            const key = input.dataset.checkKey;
            const item = input.closest('.amc-check-item');

            if (Object.prototype.hasOwnProperty.call(checklistState, key)) {
                input.checked = Boolean(checklistState[key]);
            }

            item?.classList.toggle('is-done', input.checked);

            input.addEventListener('change', function () {
                checklistState[key] = this.checked;
                item?.classList.toggle('is-done', this.checked);

                try {
                    localStorage.setItem(checklistKey, JSON.stringify(checklistState));
                } catch (error) {}

                updateChecklistProgress();
            });
        });

        updateChecklistProgress();

        if (!alreadySeen) {
            setTimeout(() => modal.show(), 650);
        }

        briefingModalEl.addEventListener('hidden.bs.modal', function () {
            try {
                localStorage.setItem(storageKey, '1');
            } catch (error) {}
        });
    }

    if (typeof ApexCharts === 'undefined') return;

    // ===== Tren area chart =====
    const TREND = @json($trend->values());
    const trendEl = document.getElementById('amcTrendChart');
    if (trendEl) {
        new ApexCharts(trendEl, {
            chart: { type: 'area', height: 268, fontFamily: 'inherit', toolbar: { show: false },
                animations: { enabled: true, easing: 'easeinout', speed: 1000, animateGradually: { enabled: true, delay: 200 } } },
            series: [{ name: 'Penerbangan', data: TREND.map(t => t.penerbangan) }],
            colors: ['#2563eb'],
            stroke: { curve: 'smooth', width: 3 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .45, opacityTo: .04, stops: [0, 92, 100] } },
            dataLabels: { enabled: false },
            markers: { size: 5, colors: ['#fff'], strokeColors: '#2563eb', strokeWidth: 3, hover: { size: 7 } },
            grid: { borderColor: '#eef2f7', strokeDashArray: 4, padding: { left: 8, right: 8 } },
            xaxis: { categories: TREND.map(t => t.label), axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { colors: '#94a3b8', fontWeight: 600 } } },
            yaxis: { min: 0, forceNiceScale: true, labels: { style: { colors: '#94a3b8' }, formatter: v => Math.round(v) } },
            tooltip: { theme: 'light', y: { formatter: v => v + ' penerbangan' } },
        }).render();
    }

    // ===== Donut permohonan =====
    const donutEl = document.getElementById('amcDonut');
    if (donutEl) {
        const data = [{{ (int) $permohonanStat['menunggu'] }}, {{ (int) $permohonanStat['disetujui'] }}, {{ (int) $permohonanStat['ditolak'] }}];
        const total = data.reduce((a, b) => a + b, 0);
        const empty = total === 0;
        new ApexCharts(donutEl, {
            chart: { type: 'donut', height: 200, fontFamily: 'inherit', animations: { enabled: true, speed: 900 } },
            series: empty ? [1] : data,
            labels: empty ? ['Belum ada'] : ['Menunggu review', 'Disetujui', 'Ditolak'],
            colors: empty ? ['#e2e8f0'] : ['#f59e0b', '#10b981', '#ef4444'],
            stroke: { width: 2, colors: ['#fff'] },
            legend: { show: false },
            dataLabels: { enabled: false },
            tooltip: { enabled: !empty, y: { formatter: v => v + ' permohonan' } },
            plotOptions: { pie: { donut: { size: '74%', labels: { show: true,
                name: { show: true, fontSize: '11px', color: '#94a3b8', offsetY: 18, formatter: () => 'Permohonan' },
                value: { show: true, fontSize: '26px', fontWeight: 800, color: '#0f172a', offsetY: -14, formatter: () => String(total) },
                total: { show: true, showAlways: true, label: 'Total', fontSize: '11px', color: '#94a3b8', formatter: () => String(total) } } } } },
        }).render();
    }
})();
</script>
@endpush
