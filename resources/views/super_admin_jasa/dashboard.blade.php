@extends('layouts.app')
@section('title', auth()->user()?->hasRole('Koordinator Jasa') && ! auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa']) ? 'Dashboard Koordinator Jasa' : 'Dashboard Super Admin Jasa')

@section('content')
<style>
    /* Hero Card Header (mengikuti dashboard Mitra) */
    @keyframes saJasaHeroReveal {
        from { opacity: 0; transform: translateY(18px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes saJasaHeroSweep {
        0%        { transform: translateX(-120%) skewX(-18deg); opacity: 0; }
        20%       { opacity: .32; }
        55%, 100% { transform: translateX(220%) skewX(-18deg); opacity: 0; }
    }
    @keyframes saJasaContourDrift {
        0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg); opacity: .68; }
        50%      { transform: translate3d(-14px, 10px, 0) rotate(2deg); opacity: .95; }
    }
    .jasa-hero-shell {
        position: relative;
        margin-bottom: 1.5rem;
        padding: 0;
        border-radius: 0 24px 24px 0;
        background: transparent;
        box-shadow: none;
    }
    .jasa-hero-shell::before {
        content: "";
        position: absolute;
        inset: -2px;
        z-index: 0;
        border-radius: 0 26px 26px 0;
        border: 1px solid rgba(251, 191, 36, .36);
        pointer-events: none;
    }
    .jasa-hero {
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
        animation: saJasaHeroReveal .55s cubic-bezier(.2,.8,.2,1) both;
    }
    .jasa-hero-shell .jasa-hero { margin-bottom: 0; }
    .jasa-hero::before,
    .jasa-hero::after {
        content: "";
        position: absolute;
        pointer-events: none;
        z-index: -1;
    }
    .jasa-hero::before {
        width: 430px;
        height: 220px;
        right: -90px;
        top: -80px;
        border-radius: 0 0 0 999px;
        border-left: 2px solid rgba(251, 191, 36, .44);
        border-bottom: 2px solid rgba(147, 197, 253, .24);
        background: radial-gradient(circle at 50% 30%, rgba(96, 165, 250, .18), transparent 62%);
        animation: saJasaContourDrift 5.6s ease-in-out infinite;
    }
    .jasa-hero::after {
        inset: 0;
        width: 46%;
        background: linear-gradient(90deg, transparent, rgba(125, 211, 252, .12), rgba(255, 255, 255, .20), rgba(96, 165, 250, .10), transparent);
        animation: saJasaHeroSweep 4.2s ease-in-out infinite;
    }
    .jasa-hero-contour {
        position: absolute;
        right: 11%;
        bottom: -78px;
        z-index: -1;
        width: 360px;
        height: 210px;
        border-radius: 999px 999px 0 0;
        border-top: 2px solid rgba(251, 191, 36, .42);
        border-left: 2px solid rgba(147, 219, 254, .20);
        transform: rotate(-10deg);
        animation: saJasaContourDrift 6.4s ease-in-out infinite reverse;
    }
    .jasa-hero-contour::before,
    .jasa-hero-contour::after {
        content: "";
        position: absolute;
        border-radius: inherit;
        border-top: 1px solid rgba(191, 219, 254, .24);
    }
    .jasa-hero-contour::before { inset: 26px 24px auto 20px; height: 138px; }
    .jasa-hero-contour::after  { inset: 58px 52px auto 54px; height: 88px; border-color: rgba(251, 191, 36, .25); }
    .jasa-hero-content { position: relative; z-index: 1; }
    .jasa-hero-date {
        border: 1px solid rgba(191, 219, 254, .24);
        border-radius: 999px;
        background: rgba(15, 23, 42, .22);
        padding: 8px 14px;
        backdrop-filter: blur(8px);
    }
    @media (prefers-reduced-motion: reduce) {
        .jasa-hero,
        .jasa-hero::before,
        .jasa-hero::after,
        .jasa-hero-contour { animation: none !important; }
    }
    /* ============== Premium Light Stat Cards ============== */
    @keyframes saStatReveal {
        from { opacity: 0; transform: translateY(20px) scale(.96); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes saStatGoldShift {
        0%, 100% { transform: translate(0, 0) skewX(-22deg); opacity: .55; }
        50%      { transform: translate(8px, -6px) skewX(-22deg); opacity: .9; }
    }
    @keyframes saStatShine {
        0%        { transform: translateX(-130%) skewX(-22deg); opacity: 0; }
        24%       { opacity: .55; }
        58%, 100% { transform: translateX(220%) skewX(-22deg); opacity: 0; }
    }
    @keyframes saStatGlow {
        0%, 100% { opacity: .35; transform: translate3d(-12px, 6px, 0) scale(1); }
        50%      { opacity: .85; transform: translate3d(28px, -16px, 0) scale(1.12); }
    }
    @keyframes saStatIconFloat {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50%      { transform: translateY(-5px) rotate(2deg); }
    }
    @keyframes saStatIconPulse {
        0%, 100% { box-shadow: 0 10px 22px rgba(15, 23, 42, .14), 0 0 0 0 var(--accent-soft, rgba(251, 191, 36, .35)); }
        50%      { box-shadow: 0 16px 32px rgba(15, 23, 42, .20), 0 0 0 12px transparent; }
    }
    @keyframes saStatIconWiggle {
        0%, 100% { transform: scale(1) rotate(0); }
        45%      { transform: scale(1.12) rotate(-6deg); }
        65%      { transform: scale(.96) rotate(4deg); }
    }
    @keyframes saStatValueIn {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .stat-card {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        border-radius: 16px !important;
        background: #ffffff !important;
        border: 1px solid rgba(15, 23, 42, .06) !important;
        box-shadow: 0 10px 26px rgba(15, 23, 42, .08) !important;
        animation: saStatReveal .55s cubic-bezier(.2,.8,.2,1) both;
        transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
    }
    .stat-card .card-body { position: relative; z-index: 2; }

    /* staggered reveal */
    .row.g-3.mb-4 > div:nth-child(1) .stat-card { animation-delay: .00s; }
    .row.g-3.mb-4 > div:nth-child(2) .stat-card { animation-delay: .06s; }
    .row.g-3.mb-4 > div:nth-child(3) .stat-card { animation-delay: .12s; }
    .row.g-3.mb-4 > div:nth-child(4) .stat-card { animation-delay: .18s; }
    .row.g-3.mb-4 > div:nth-child(5) .stat-card { animation-delay: .24s; }
    .row.g-3.mb-4 > div:nth-child(6) .stat-card { animation-delay: .30s; }
    .row.g-3.mb-4 > div:nth-child(7) .stat-card { animation-delay: .36s; }
    .row.g-3.mb-4 > div:nth-child(8) .stat-card { animation-delay: .42s; }
    .row.g-3.mb-4 > div:nth-child(9) .stat-card { animation-delay: .48s; }
    .row.g-3.mb-4 > div:nth-child(10) .stat-card { animation-delay: .54s; }

    /* Gold diagonal accent stripes (subtle) */
    .stat-card::before,
    .stat-card::after {
        content: "";
        position: absolute;
        pointer-events: none;
        z-index: 1;
        top: -40%;
        height: 200%;
        background: linear-gradient(180deg, rgba(217, 119, 6, .55), rgba(251, 191, 36, .35) 60%, rgba(251, 191, 36, 0));
        animation: saStatGoldShift 6s ease-in-out infinite;
    }
    .stat-card::before {
        right: 18%;
        width: 1.5px;
        filter: drop-shadow(0 0 4px rgba(251, 191, 36, .35));
    }
    .stat-card::after {
        right: 6%;
        width: 3px;
        opacity: .35;
        filter: drop-shadow(0 0 6px rgba(251, 191, 36, .25));
        animation-delay: -1.4s;
    }

    /* radial glow + shine sweep */
    .stat-card .stat-glow,
    .stat-card .stat-shine {
        position: absolute;
        pointer-events: none;
        z-index: 0;
    }
    .stat-card .stat-glow {
        right: -90px;
        top: -110px;
        width: 240px;
        height: 240px;
        border-radius: 999px;
        background: radial-gradient(circle, var(--accent-glow, rgba(96, 165, 250, .25)), transparent 70%);
        animation: saStatGlow 5.4s ease-in-out infinite;
    }
    .stat-card .stat-shine {
        inset: 0;
        width: 38%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .55), rgba(251, 191, 36, .15), rgba(255, 255, 255, .55), transparent);
        animation: saStatShine 5.2s ease-in-out infinite;
    }
    .stat-card .stat-ribbon {
        position: absolute;
        top: 18px;
        left: -60px;
        width: 160px;
        height: 1px;
        transform: rotate(-38deg);
        background: linear-gradient(90deg, transparent, rgba(217, 119, 6, .65), transparent);
        z-index: 1;
        opacity: .55;
    }

    .stat-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 40px rgba(15, 23, 42, .14), 0 0 0 1px rgba(251, 191, 36, .35) inset !important;
        border-color: rgba(251, 191, 36, .35) !important;
    }
    .stat-card:hover::before { animation-duration: 3.4s; }
    .stat-card:hover .stat-icon { animation: saStatIconWiggle .65s ease both, saStatIconPulse 2.4s ease-in-out infinite; }

    /* keep light/dark text contrast */
    .stat-card .small.text-muted.fw-bold { letter-spacing: .04em; text-transform: uppercase; color: #64748b !important; }
    .stat-card .text-muted { color: #64748b !important; }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        position: relative;
        z-index: 2;
        background: var(--accent-bg, #eaf2ff) !important;
        color: var(--accent, #0d6efd) !important;
        border: 1px solid var(--accent-soft, rgba(13, 110, 253, .25));
        box-shadow: 0 10px 22px rgba(15, 23, 42, .12), 0 0 0 0 var(--accent-soft, rgba(13, 110, 253, .25));
        animation: saStatIconFloat 3.2s ease-in-out infinite, saStatIconPulse 2.6s ease-in-out infinite;
        transform-origin: center;
    }
    .stat-icon i { filter: drop-shadow(0 2px 4px rgba(15, 23, 42, .12)); }

    .stat-card .fs-3 {
        position: relative;
        z-index: 2;
        animation: saStatValueIn .6s ease both;
        animation-delay: .25s;
    }

    .stat-accent {
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        z-index: 2;
        background: linear-gradient(180deg, var(--accent, #fbbf24), var(--accent-soft, rgba(251, 191, 36, .15)));
        box-shadow: 0 0 12px var(--accent-soft, rgba(251, 191, 36, .35));
    }

    @media (prefers-reduced-motion: reduce) {
        .stat-card,
        .stat-card::before,
        .stat-card::after,
        .stat-card .stat-glow,
        .stat-card .stat-shine,
        .stat-icon,
        .stat-card .fs-3 { animation: none !important; }
        .stat-card:hover { transform: none; }
    }

    .panel-icon {
        width: 30px;
        height: 30px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* ============== Premium Panel Cards (Mitra/Admin/Status) ============== */
    @keyframes saPanelReveal {
        from { opacity: 0; transform: translateY(18px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes saPanelShine {
        0%        { transform: translateX(-130%) skewX(-22deg); opacity: 0; }
        24%       { opacity: .55; }
        58%, 100% { transform: translateX(220%) skewX(-22deg); opacity: 0; }
    }
    @keyframes saPanelGlow {
        0%, 100% { opacity: .35; transform: translate3d(-12px, 6px, 0) scale(1); }
        50%      { opacity: .85; transform: translate3d(28px, -16px, 0) scale(1.12); }
    }
    @keyframes saPanelGoldShift {
        0%, 100% { transform: translate(0, 0) skewX(-22deg); opacity: .45; }
        50%      { transform: translate(8px, -6px) skewX(-22deg); opacity: .85; }
    }
    @keyframes saPanelIconFloat {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50%      { transform: translateY(-3px) rotate(2deg); }
    }
    @keyframes saPanelIconPulse {
        0%, 100% { box-shadow: 0 6px 14px rgba(15, 23, 42, .12), 0 0 0 0 var(--panel-accent-soft, rgba(13, 110, 253, .25)); }
        50%      { box-shadow: 0 10px 22px rgba(15, 23, 42, .18), 0 0 0 8px transparent; }
    }
    @keyframes saPanelIconWiggle {
        0%, 100% { transform: scale(1) rotate(0); }
        45%      { transform: scale(1.12) rotate(-6deg); }
        65%      { transform: scale(.96) rotate(4deg); }
    }
    @keyframes saPanelItemIn {
        from { opacity: 0; transform: translateX(-8px); }
        to   { opacity: 1; transform: translateX(0); }
    }

    .panel-card {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        border-radius: 16px !important;
        background: #ffffff !important;
        border: 1px solid rgba(15, 23, 42, .06) !important;
        box-shadow: 0 10px 26px rgba(15, 23, 42, .08) !important;
        animation: saPanelReveal .55s cubic-bezier(.2,.8,.2,1) both;
        transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
    }
    .row.g-4 > div:nth-child(1) .panel-card { animation-delay: .00s; }
    .row.g-4 > div:nth-child(2) .panel-card { animation-delay: .08s; }
    .row.g-4 > div:nth-child(3) .panel-card { animation-delay: .16s; }

    .panel-card::before,
    .panel-card::after {
        content: "";
        position: absolute;
        pointer-events: none;
        z-index: 1;
        top: -40%;
        height: 200%;
        background: linear-gradient(180deg, rgba(217, 119, 6, .55), rgba(251, 191, 36, .35) 60%, rgba(251, 191, 36, 0));
        animation: saPanelGoldShift 6s ease-in-out infinite;
    }
    .panel-card::before {
        right: 18%;
        width: 1.5px;
        filter: drop-shadow(0 0 4px rgba(251, 191, 36, .35));
    }
    .panel-card::after {
        right: 6%;
        width: 3px;
        opacity: .35;
        filter: drop-shadow(0 0 6px rgba(251, 191, 36, .25));
        animation-delay: -1.4s;
    }
    .panel-card .panel-glow,
    .panel-card .panel-shine {
        position: absolute;
        pointer-events: none;
        z-index: 0;
    }
    .panel-card .panel-glow { display: none; }
    .panel-card .panel-shine {
        inset: 0;
        width: 38%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .55), rgba(251, 191, 36, .15), rgba(255, 255, 255, .55), transparent);
        animation: saPanelShine 5.4s ease-in-out infinite;
    }

    .panel-card .card-header,
    .panel-card .card-body {
        position: relative;
        z-index: 2;
        background: transparent !important;
    }
    .panel-card .card-header {
        border-bottom: 1px solid rgba(15, 23, 42, .06);
        padding: 14px 18px;
    }
    .panel-card .card-body {
        padding: 14px 18px;
    }

    .panel-card .panel-icon {
        background: var(--panel-accent-bg, #eaf2ff) !important;
        color: var(--panel-accent, #0d6efd) !important;
        border: 1px solid var(--panel-accent-soft, rgba(13, 110, 253, .25));
        box-shadow: 0 6px 14px rgba(15, 23, 42, .12), 0 0 0 0 var(--panel-accent-soft, rgba(13, 110, 253, .25));
        animation: saPanelIconFloat 3.2s ease-in-out infinite, saPanelIconPulse 2.6s ease-in-out infinite;
        transform-origin: center;
    }
    .panel-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 18px 38px rgba(15, 23, 42, .14), 0 0 0 1px rgba(251, 191, 36, .35) inset !important;
        border-color: rgba(251, 191, 36, .35) !important;
    }
    .panel-card:hover::before { animation-duration: 3.4s; }
    .panel-card:hover .panel-icon { animation: saPanelIconWiggle .65s ease both, saPanelIconPulse 2.4s ease-in-out infinite; }

    /* row items inside panel */
    .panel-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: 10px 8px;
        border-radius: 10px;
        border-bottom: 1px solid rgba(15, 23, 42, .06);
        animation: saPanelItemIn .5s cubic-bezier(.2,.8,.2,1) both;
        transition: background-color .2s ease, transform .2s ease;
    }
    .panel-row:last-child { border-bottom: 0; }
    .panel-row:hover {
        background: linear-gradient(90deg, var(--panel-accent-tint, rgba(13, 110, 253, .08)), transparent);
        transform: translateX(2px);
    }
    .panel-card .card-body > .panel-row:nth-child(1) { animation-delay: .12s; }
    .panel-card .card-body > .panel-row:nth-child(2) { animation-delay: .18s; }
    .panel-card .card-body > .panel-row:nth-child(3) { animation-delay: .24s; }
    .panel-card .card-body > .panel-row:nth-child(4) { animation-delay: .30s; }
    .panel-card .card-body > .panel-row:nth-child(5) { animation-delay: .36s; }
    .panel-card .card-body > .panel-row:nth-child(6) { animation-delay: .42s; }

    .panel-row .btn-outline-primary {
        border-color: var(--panel-accent, #0d6efd);
        color: var(--panel-accent, #0d6efd);
        font-weight: 700;
        border-radius: 999px;
        padding: 4px 14px;
        transition: all .2s ease;
    }
    .panel-row .btn-outline-primary:hover {
        background: var(--panel-accent, #0d6efd);
        color: #fff;
        box-shadow: 0 8px 18px var(--panel-accent-glow, rgba(13, 110, 253, .35));
        transform: translateY(-1px);
    }

    .panel-status-pill {
        display: inline-flex;
        min-width: 36px;
        justify-content: center;
        padding: 4px 12px;
        border-radius: 999px;
        background: var(--panel-accent-tint, rgba(13, 110, 253, .12));
        color: var(--panel-accent, #0d6efd);
        font-weight: 800;
        font-size: 13px;
    }

    @media (prefers-reduced-motion: reduce) {
        .panel-card,
        .panel-card::before,
        .panel-card::after,
        .panel-card .panel-glow,
        .panel-card .panel-shine,
        .panel-card .panel-icon,
        .panel-row { animation: none !important; }
        .panel-card:hover { transform: none; }
    }
</style>
@include('_partials.sa-chart-style')
<style>
    /* keeps below empty so existing structure stays intact */
    .sa-noop { display: none; }
</style>

@php
    $isKoordinatorJasa = $isKoordinatorJasa ?? false;
    $statCards = [
        [
            'label' => 'Total Mitra Jasa',
            'value' => $stats['mitra_total'],
            'icon' => 'bi-buildings',
            'color' => '#0d6efd',
            'bg' => '#eaf2ff',
            'note' => 'Seluruh mitra jasa terdaftar',
        ],
        [
            'label' => 'Mitra Aktif',
            'value' => $stats['mitra_aktif'],
            'icon' => 'bi-check-circle',
            'color' => '#00a86b',
            'bg' => '#e9fbf2',
            'note' => 'Mitra yang dapat ditagihkan',
        ],
        [
            'label' => 'Mitra Punya Akun',
            'value' => $stats['mitra_akun'],
            'icon' => 'bi-person-check',
            'color' => '#2563eb',
            'bg' => '#eff6ff',
            'note' => 'Akses portal sudah dibuat',
        ],
        [
            'label' => 'Mitra Tanpa Akun',
            'value' => $stats['mitra_tanpa_akun'],
            'icon' => 'bi-person-exclamation',
            'color' => '#f97316',
            'bg' => '#fff4e8',
            'note' => 'Perlu dibuatkan akun',
        ],
        [
            'label' => 'Kontrak Aktif',
            'value' => $stats['kontrak_aktif'],
            'icon' => 'bi-file-earmark-check',
            'color' => '#0891b2',
            'bg' => '#e9faff',
            'note' => 'Dokumen dasar aktif',
        ],
        [
            'label' => 'Kontrak Akan Berakhir',
            'value' => $stats['kontrak_akan_berakhir'],
            'icon' => 'bi-calendar2-event',
            'color' => '#eab308',
            'bg' => '#fff9db',
            'note' => 'Dalam 60 hari ke depan',
        ],
        [
            'label' => 'Layanan Billable',
            'value' => $stats['layanan_billable'],
            'icon' => 'bi-list-check',
            'color' => '#7c3aed',
            'bg' => '#f3efff',
            'note' => 'Item tarif aktif',
        ],
        [
            'label' => 'Admin Jasa',
            'value' => $stats['admin_jasa'],
            'icon' => 'bi-person-workspace',
            'color' => '#475569',
            'bg' => '#f1f5f9',
            'note' => 'Petugas pengelola layanan',
        ],
        [
            'label' => 'Tagihan Bulan Ini',
            'value' => $stats['tagihan_bulan_ini'],
            'icon' => 'bi-receipt',
            'color' => '#db2777',
            'bg' => '#fdf2f8',
            'note' => 'Tagihan jasa periode berjalan',
        ],
        [
            'label' => 'Tagihan Lunas',
            'value' => $stats['tagihan_lunas'],
            'icon' => 'bi-cash-coin',
            'color' => '#16a34a',
            'bg' => '#ecfdf3',
            'note' => 'Tagihan dengan status lunas',
        ],
    ];
@endphp



<div class="jasa-hero-shell">
    <div class="jasa-hero d-flex justify-content-between align-items-center">
        <span class="jasa-hero-contour"></span>
        <div class="jasa-hero-content">
            <div class="small text-white-50 fw-bold text-uppercase mb-1">Portal Super Admin Jasa</div>
            <h4 class="mb-1 fw-bold text-white">Dashboard Super Admin Jasa</h4>
            <p class="mb-0 small text-white-50">Ringkasan pengelolaan mitra, admin jasa, layanan, dan tagihan PNBP.</p>
        </div>
        <div class="jasa-hero-content d-none d-md-flex align-items-center gap-2 small text-white-50">
            <span class="jasa-hero-date">
                <i class="bi bi-calendar3 me-1"></i>
                {{ now()->format('d/m/Y') }}
            </span>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach($statCards as $card)
        @php
            $hex = ltrim($card['color'], '#');
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $tint = "rgba({$r}, {$g}, {$b}, .14)";
            $glow = "rgba({$r}, {$g}, {$b}, .22)";
            $soft = "rgba({$r}, {$g}, {$b}, .28)";
        @endphp
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm stat-card h-100"
                 style="--accent: {{ $card['color'] }}; --accent-bg: {{ $card['bg'] }}; --accent-tint: {{ $tint }}; --accent-glow: {{ $glow }}; --accent-soft: {{ $soft }};">
                <span class="stat-glow"></span>
                <span class="stat-shine"></span>
                <span class="stat-ribbon"></span>
                <div class="stat-accent"></div>
                <div class="card-body ps-4">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="small text-muted fw-bold">{{ $card['label'] }}</div>
                            <div class="fs-3 fw-bold" style="color: {{ $card['color'] }}">{{ number_format($card['value'], 0, ',', '.') }}</div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi {{ $card['icon'] }}"></i>
                        </div>
                    </div>
                    <div class="small text-muted mt-2">{{ $card['note'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ============== Charts + Calendar ============== --}}
<div class="row g-4 sa-chart-row">
    {{-- Chart 01: Tagihan per bulan (bar+line) --}}
    <div class="col-xl-8">
        <div class="sa-chart-card h-100">
            <div class="sa-chart-body">
                <div class="sa-chart-head">
                    <div class="sa-chart-title">
                        <span class="sa-chart-icon"><i class="bi bi-bar-chart-line"></i></span>
                        <div>
                            <h6>Tagihan 12 Bulan Terakhir</h6>
                            <small>Jumlah dan nominal tagihan jasa</small>
                        </div>
                    </div>
                    <span class="sa-chart-pill"><i class="bi bi-stars"></i> Tren</span>
                </div>
                <div class="sa-chart-canvas">
                    <canvas id="saChartMonthly"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart 02: Persentase Lunas (gauge) --}}
    <div class="col-xl-4">
        <div class="sa-chart-card h-100">
            <div class="sa-chart-body">
                <div class="sa-chart-head">
                    <div class="sa-chart-title">
                        <span class="sa-chart-icon" style="background: linear-gradient(135deg, #16a34a, #4ade80); box-shadow: 0 12px 24px rgba(22, 163, 74, .22);"><i class="bi bi-patch-check"></i></span>
                        <div>
                            <h6>Persentase Tagihan Lunas</h6>
                            <small>Dari total tagihan jasa</small>
                        </div>
                    </div>
                </div>
                @php
                    $circumference = 339.292;
                    $offset = $circumference - ($circumference * ($persentaseLunas / 100));
                @endphp
                <div class="sa-gauge" style="--gauge-circ: {{ $circumference }}; --gauge-target: {{ $offset }};">
                    <svg viewBox="0 0 120 120">
                        <defs>
                            <linearGradient id="saGaugeGradient" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#fbbf24"/>
                                <stop offset="55%" stop-color="#16a34a"/>
                                <stop offset="100%" stop-color="#0ea5e9"/>
                            </linearGradient>
                        </defs>
                        <circle class="sa-gauge-track" cx="60" cy="60" r="54"></circle>
                        <circle class="sa-gauge-bar" cx="60" cy="60" r="54"></circle>
                    </svg>
                    <div class="sa-gauge-text">
                        <div class="val" id="saGaugeVal">0%</div>
                        <div class="lbl">Lunas</div>
                    </div>
                </div>
                <div class="d-flex justify-content-around small fw-semibold text-muted">
                    <span><i class="bi bi-circle-fill text-success me-1"></i>Lunas {{ $stats['tagihan_lunas'] }}</span>
                    <span><i class="bi bi-circle-fill text-warning me-1"></i>Lainnya</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart 03: Top mitra by nominal (horizontal bar) --}}
    <div class="col-xl-4">
        <div class="sa-chart-card h-100">
            <div class="sa-chart-body">
                <div class="sa-chart-head">
                    <div class="sa-chart-title">
                        <span class="sa-chart-icon" style="background: linear-gradient(135deg, #db2777, #f472b6); box-shadow: 0 12px 24px rgba(219, 39, 119, .22);"><i class="bi bi-trophy"></i></span>
                        <div>
                            <h6>Top Mitra (Nominal)</h6>
                            <small>6 mitra dengan tagihan terbesar</small>
                        </div>
                    </div>
                </div>
                <div class="sa-chart-canvas sa-chart-sm">
                    <canvas id="saChartTopMitra"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart 04: Status tagihan donut --}}
    <div class="col-xl-4">
        <div class="sa-chart-card h-100">
            <div class="sa-chart-body">
                <div class="sa-chart-head">
                    <div class="sa-chart-title">
                        <span class="sa-chart-icon" style="background: linear-gradient(135deg, #7c3aed, #a78bfa); box-shadow: 0 12px 24px rgba(124, 58, 237, .22);"><i class="bi bi-pie-chart"></i></span>
                        <div>
                            <h6>Distribusi Status Tagihan</h6>
                            <small>Komposisi status tagihan</small>
                        </div>
                    </div>
                </div>
                <div class="sa-chart-canvas sa-chart-sm">
                    <canvas id="saChartStatus"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Calendar --}}
    <div class="col-xl-4">
        <div class="sa-chart-card h-100">
            <div class="sa-chart-body">
                <div class="sa-chart-head">
                    <div class="sa-chart-title">
                        <span class="sa-chart-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24); box-shadow: 0 12px 24px rgba(245, 158, 11, .25);"><i class="bi bi-calendar3"></i></span>
                        <div>
                            <h6>Kalender Tagihan</h6>
                            <small>{{ $calendar['monthLabel'] }}</small>
                        </div>
                    </div>
                </div>

                @php
                    $weekdays = ['M','S','S','R','K','J','S'];
                @endphp
                <div class="sa-cal-grid">
                    @foreach($weekdays as $w)
                        <div class="sa-cal-h">{{ $w }}</div>
                    @endforeach

                    @for($i = 0; $i < $calendar['firstWeekday']; $i++)
                        <div class="sa-cal-cell sa-cal-empty"></div>
                    @endfor

                    @for($d = 1; $d <= $calendar['daysInMonth']; $d++)
                        @php
                            $events = $calendar['events'][$d] ?? [];
                            $isToday = ($calendar['today'] === $d);
                            $hasEvent = !empty($events);
                            $types = collect($events)->pluck('type')->unique()->values();
                            $delay = ($d * 0.018);
                        @endphp
                        <div class="sa-cal-cell {{ $isToday ? 'sa-cal-today' : '' }} {{ $hasEvent ? 'sa-has-event' : '' }}"
                             style="animation-delay: {{ number_format($delay, 3) }}s;">
                            <span>{{ $d }}</span>

                            @if($hasEvent)
                                <span class="sa-cal-dots">
                                    @foreach($types as $type)
                                        @php
                                            $cls = match($type) {
                                                'terbit' => 'sa-dot-terbit',
                                                'jatuh_tempo' => 'sa-dot-jt',
                                                'lunas' => 'sa-dot-lunas',
                                                default => '',
                                            };
                                        @endphp
                                        <span class="sa-cal-dot {{ $cls }}"></span>
                                    @endforeach
                                </span>
                                <div class="sa-cal-tooltip">
                                    <div class="mb-1"><strong>Tanggal {{ $d }}</strong></div>
                                    @foreach(array_slice($events, 0, 3) as $ev)
                                        <div class="d-flex justify-content-between gap-2">
                                            <span>{{ $ev['label'] }}</span>
                                            <span class="text-white-50">{{ $ev['nomor'] ?: '-' }}</span>
                                        </div>
                                    @endforeach
                                    @if(count($events) > 3)
                                        <div class="text-white-50">+{{ count($events) - 3 }} lainnya</div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endfor
                </div>

                <div class="sa-cal-legend">
                    <span><i class="terbit"></i> Tagihan terbit</span>
                    <span><i class="jt"></i> Jatuh tempo</span>
                    <span><i class="lunas"></i> Lunas</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-4">
        <div class="card panel-card border-0 shadow-sm rounded-4 h-100"
             style="--panel-accent: #f59e0b; --panel-accent-bg: #fff7e6; --panel-accent-tint: rgba(245, 158, 11, .12); --panel-accent-glow: rgba(245, 158, 11, .25); --panel-accent-soft: rgba(245, 158, 11, .35);">
            <span class="panel-glow"></span>
            <span class="panel-shine"></span>
            <div class="card-header fw-bold d-flex align-items-center gap-2">
                <span class="panel-icon"><i class="bi bi-building-exclamation"></i></span>
                <span>Mitra Belum Punya Layanan</span>
            </div>
            <div class="card-body">
                @forelse($mitraTanpaLayanan as $mitra)
                    <div class="panel-row">
                        <div>
                            <div class="fw-semibold">{{ $mitra->nama_mitra }}</div>
                            <small class="text-muted">{{ $mitra->email ?: '-' }}</small>
                        </div>
                        <a href="{{ route('jasa.mitra.layanan.edit', $mitra) }}" class="btn btn-sm btn-outline-primary jasa-icon-btn" title="Atur layanan" aria-label="Atur layanan"><i class="bi bi-sliders"></i></a>
                    </div>
                @empty
                    <div class="text-muted small">Semua mitra aktif sudah memiliki layanan.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card panel-card border-0 shadow-sm rounded-4 h-100"
             style="--panel-accent: #2563eb; --panel-accent-bg: #eaf2ff; --panel-accent-tint: rgba(37, 99, 235, .12); --panel-accent-glow: rgba(37, 99, 235, .25); --panel-accent-soft: rgba(37, 99, 235, .35);">
            <span class="panel-glow"></span>
            <span class="panel-shine"></span>
            <div class="card-header fw-bold d-flex align-items-center gap-2">
                <span class="panel-icon"><i class="bi bi-person-gear"></i></span>
                <span>Admin Jasa Belum Punya Layanan</span>
            </div>
            <div class="card-body">
                @forelse($adminTanpaLayanan as $admin)
                    <div class="panel-row">
                        <div>
                            <div class="fw-semibold">{{ $admin->name }}</div>
                            <small class="text-muted">{{ $admin->email }}</small>
                        </div>
                        <a href="{{ route('jasa.admin.layanan.edit', $admin) }}" class="btn btn-sm btn-outline-primary jasa-icon-btn" title="Atur layanan" aria-label="Atur layanan"><i class="bi bi-sliders"></i></a>
                    </div>
                @empty
                    <div class="text-muted small">Semua Admin Jasa sudah memiliki layanan.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card panel-card border-0 shadow-sm rounded-4 h-100"
             style="--panel-accent: #16a34a; --panel-accent-bg: #e9fbf2; --panel-accent-tint: rgba(22, 163, 74, .12); --panel-accent-glow: rgba(22, 163, 74, .25); --panel-accent-soft: rgba(22, 163, 74, .35);">
            <span class="panel-glow"></span>
            <span class="panel-shine"></span>
            <div class="card-header fw-bold d-flex align-items-center gap-2">
                <span class="panel-icon"><i class="bi bi-pie-chart"></i></span>
                <span>Status Tagihan</span>
            </div>
            <div class="card-body">
                @forelse($tagihanByStatus as $row)
                    <div class="panel-row">
                        <span class="fw-semibold text-slate">{{ str_replace('_', ' ', $row->status) }}</span>
                        <span class="panel-status-pill">{{ $row->total }}</span>
                    </div>
                @empty
                    <div class="text-muted small">Belum ada tagihan jasa.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
<script src="{{ URL::asset('build/plugins/chartjs/js/chart.js') }}"></script>
<script>
(function () {
    const monthly = @json($chartTagihanBulanan);
    const status  = @json($chartStatus);
    const topMitra= @json($chartTopMitra);
    const persen  = {{ $persentaseLunas }};

    const moneyTick = v => 'Rp ' + new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 }).format(v);
    const grid = { color: 'rgba(148, 163, 184, .18)', borderDash: [4, 4], drawBorder: false };

    const gradient = (ctx, start, end) => {
        const chart = ctx.chart;
        const area = chart.chartArea;
        if (!area) return start;
        const g = chart.ctx.createLinearGradient(0, area.bottom, 0, area.top);
        g.addColorStop(0, end);
        g.addColorStop(1, start);
        return g;
    };

    const baseOpts = {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 1400,
            easing: 'easeOutQuart',
            delay: ctx => ctx.type === 'data' ? (ctx.dataIndex * 60) + (ctx.datasetIndex * 120) : 0,
        },
        interaction: { intersect: false, mode: 'index' },
        plugins: {
            legend: {
                labels: { usePointStyle: true, pointStyle: 'rectRounded', boxWidth: 10, boxHeight: 10, color: '#475569', font: { weight: '700' } }
            },
            tooltip: {
                backgroundColor: '#0f172a', borderColor: 'rgba(147, 197, 253, .35)', borderWidth: 1,
                padding: 12, titleColor: '#eff6ff', bodyColor: '#dbeafe', displayColors: true,
            },
        },
    };

    // Chart 01: tagihan bulanan (bar + line)
    const elMonthly = document.getElementById('saChartMonthly');
    if (elMonthly) {
        new Chart(elMonthly, {
            type: 'bar',
            data: {
                labels: monthly.labels,
                datasets: [
                    {
                        label: 'Jumlah Tagihan',
                        data: monthly.jumlah,
                        backgroundColor: ctx => gradient(ctx, '#2563eb', '#93c5fd'),
                        borderColor: 'rgba(37, 99, 235, .55)',
                        borderWidth: 1,
                        borderRadius: 10,
                        borderSkipped: false,
                        order: 2,
                    },
                    {
                        type: 'line',
                        label: 'Nominal',
                        data: monthly.nominal,
                        borderColor: '#db2777',
                        backgroundColor: 'rgba(219, 39, 119, .15)',
                        borderWidth: 3,
                        tension: .42,
                        pointRadius: 4,
                        pointBackgroundColor: '#db2777',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverRadius: 7,
                        fill: true,
                        yAxisID: 'y1',
                        order: 1,
                    },
                ],
            },
            options: {
                ...baseOpts,
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748b', font: { weight: '700' } } },
                    y: { beginAtZero: true, grid, ticks: { color: '#64748b', precision: 0 } },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false, drawBorder: false },
                        ticks: { callback: moneyTick, color: '#64748b' }
                    },
                },
            },
        });
    }

    // Chart 03: top mitra horizontal bar
    const elTopMitra = document.getElementById('saChartTopMitra');
    if (elTopMitra) {
        new Chart(elTopMitra, {
            type: 'bar',
            data: {
                labels: topMitra.labels,
                datasets: [{
                    label: 'Nominal',
                    data: topMitra.data,
                    backgroundColor: ctx => gradient(ctx, '#7c3aed', '#c4b5fd'),
                    borderColor: 'rgba(124, 58, 237, .55)',
                    borderWidth: 1,
                    borderRadius: 10,
                    borderSkipped: false,
                    hoverBackgroundColor: '#7c3aed',
                }],
            },
            options: {
                ...baseOpts,
                indexAxis: 'y',
                plugins: { ...baseOpts.plugins, legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, grid, ticks: { callback: moneyTick, color: '#64748b' } },
                    y: { grid: { display: false }, ticks: { color: '#475569', font: { weight: '700' } } },
                },
            },
        });
    }

    // Chart 04: status donut
    const elStatus = document.getElementById('saChartStatus');
    if (elStatus) {
        new Chart(elStatus, {
            type: 'doughnut',
            data: {
                labels: status.labels,
                datasets: [{
                    data: status.data,
                    backgroundColor: ['#2563eb', '#f59e0b', '#16a34a', '#ef4444', '#7c3aed', '#0ea5e9', '#db2777', '#475569'],
                    borderColor: '#ffffff',
                    borderWidth: 4,
                    hoverOffset: 14,
                    spacing: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '58%',
                animation: { animateRotate: true, animateScale: true, duration: 1500, easing: 'easeOutQuart' },
                plugins: {
                    ...baseOpts.plugins,
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 9, boxHeight: 9, color: '#475569', font: { weight: '700' } }
                    },
                },
            },
        });
    }

    // Gauge text count-up
    const gaugeEl = document.getElementById('saGaugeVal');
    if (gaugeEl) {
        const target = persen;
        const duration = 1400;
        const start = performance.now();
        function step(now) {
            const elapsed = now - start;
            const t = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - t, 3);
            const cur = (target * eased);
            gaugeEl.textContent = cur.toFixed(target % 1 === 0 ? 0 : 1) + '%';
            if (t < 1) requestAnimationFrame(step);
            else gaugeEl.textContent = target + '%';
        }
        requestAnimationFrame(step);
    }
})();
</script>
@endpush
