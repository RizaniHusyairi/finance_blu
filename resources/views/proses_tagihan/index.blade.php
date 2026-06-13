@extends('layouts.app')

@section('title', 'Proses Tagihan')

@push('css')
<style>
    /* ==========================================================
       PROSES TAGIHAN — INDEX "PIPELINE PENCAIRAN"
       Selaras dengan halaman detail: aurora hero, stat cards,
       kartu tagihan + mini stepper, reveal-on-scroll, count-up.
       ========================================================== */
    :root {
        --pt-primary: #4f46e5;
        --pt-primary-2: #7c3aed;
        --pt-secondary: #64748b;
        --pt-success: #10b981;
        --pt-warning: #f59e0b;
        --pt-danger: #ef4444;
        --pt-info: #06b6d4;
        --pt-ink: #0f172a;
        --pt-bg: #f4f6fb;
        --pt-card-bg: #ffffff;
        --pt-border: #e2e8f0;
        --pt-shadow: 0 10px 30px -12px rgba(15, 23, 42, .12);
        --pt-shadow-hover: 0 24px 45px -18px rgba(79, 70, 229, .25);
        --pt-radius: 1.1rem;
        --pt-radius-lg: 1.6rem;

        --tone-indigo: #4f46e5;   --tone-indigo-soft: rgba(79,70,229,.10);
        --tone-violet: #8b5cf6;   --tone-violet-soft: rgba(139,92,246,.10);
        --tone-emerald: #10b981;  --tone-emerald-soft: rgba(16,185,129,.10);
        --tone-info: #06b6d4;     --tone-info-soft: rgba(6,182,212,.10);
        --tone-amber: #f59e0b;    --tone-amber-soft: rgba(245,158,11,.12);
        --tone-slate: #64748b;    --tone-slate-soft: rgba(100,116,139,.12);
    }

    body { background-color: var(--pt-bg); }
    .min-w-0 { min-width: 0; }

    @keyframes ptFadeUp   { from { opacity: 0; transform: translateY(26px); } to { opacity: 1; transform: none; } }
    @keyframes ptPop      { 0% { transform: scale(.6); opacity: 0; } 70% { transform: scale(1.08); } 100% { transform: scale(1); opacity: 1; } }
    @keyframes ptFloat    { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-10px) } }
    @keyframes ptAurora   { 0% { background-position: 0% 50% } 50% { background-position: 100% 50% } 100% { background-position: 0% 50% } }
    @keyframes ptShimmer  { 0% { background-position: -200% 0 } 100% { background-position: 200% 0 } }
    @keyframes ptPulse    { 0%,100% { box-shadow: 0 0 0 0 rgba(245,158,11,.45) } 50% { box-shadow: 0 0 0 9px rgba(245,158,11,0) } }
    @keyframes ptPulseBlue{ 0%,100% { box-shadow: 0 0 0 0 rgba(79,70,229,.45) } 50% { box-shadow: 0 0 0 10px rgba(79,70,229,0) } }
    @keyframes ptBounce   { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-4px) } }
    @keyframes ptSlideIn  { from { opacity: 0; transform: translateY(-14px) } to { opacity: 1; transform: none } }

    /* reveal-on-scroll (aktif hanya bila JS jalan) */
    .pt-anim .reveal { opacity: 0; transform: translateY(24px); transition: opacity .65s cubic-bezier(.16,1,.3,1), transform .65s cubic-bezier(.16,1,.3,1); transition-delay: var(--d, 0s); }
    .pt-anim .reveal.in { opacity: 1; transform: none; }
    @media (prefers-reduced-motion: reduce) {
        .pt-anim .reveal { opacity: 1 !important; transform: none !important; transition: none !important; }
        * { animation-duration: .001s !important; animation-iteration-count: 1 !important; }
    }

    /* ---------- Hero ---------- */
    .pt-hero {
        position: relative; overflow: hidden;
        border-radius: var(--pt-radius-lg);
        padding: 2rem 2.1rem 1.9rem;
        margin-bottom: 1.4rem;
        color: #fff;
        background: linear-gradient(-45deg, #0f172a, #312e81, #4f46e5, #7c3aed, #1d4ed8);
        background-size: 420% 420%;
        animation: ptAurora 16s ease infinite, ptPop .6s cubic-bezier(.16,1,.3,1) both;
        box-shadow: 0 22px 45px -18px rgba(49, 46, 129, .55);
    }
    .pt-hero::before, .pt-hero::after {
        content: ''; position: absolute; border-radius: 50%; pointer-events: none;
        background: radial-gradient(circle, rgba(255,255,255,.16) 0%, transparent 70%);
    }
    .pt-hero::before { width: 340px; height: 340px; top: -45%; left: -6%; animation: ptFloat 9s ease-in-out infinite; }
    .pt-hero::after  { width: 260px; height: 260px; bottom: -55%; right: -4%; animation: ptFloat 12s ease-in-out infinite reverse; }
    .pt-hero .grid-lines {
        position: absolute; inset: 0; opacity: .14; pointer-events: none;
        background-image: linear-gradient(rgba(255,255,255,.35) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.35) 1px, transparent 1px);
        background-size: 44px 44px;
        mask-image: radial-gradient(ellipse at 30% 0%, #000 10%, transparent 65%);
    }
    .pt-hero-content { position: relative; z-index: 2; }
    .pt-chip {
        display: inline-flex; align-items: center; gap: .4rem;
        background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.28);
        backdrop-filter: blur(8px);
        padding: .38rem .95rem; border-radius: 999px;
        font-weight: 700; font-size: .78rem; letter-spacing: .4px; color: #fff;
    }
    .pt-chip .dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; animation: ptPulseBlue 2s infinite; }
    .pt-hero h1 { color: #fff !important; font-size: clamp(1.5rem, 3vw, 2.1rem); font-weight: 800; letter-spacing: -.5px; margin: .6rem 0 .15rem; }
    .pt-hero .sub { color: rgba(255,255,255,.78) !important; font-weight: 500; font-size: .92rem; }
    .pt-amount { color: #fff !important; font-size: clamp(1.5rem, 3.2vw, 2.2rem); font-weight: 800; line-height: 1.1; letter-spacing: -1px; text-shadow: 0 4px 18px rgba(0,0,0,.25); font-variant-numeric: tabular-nums; }

    /* search bar kaca di dalam hero */
    .pt-search {
        position: relative; z-index: 2;
        display: flex; flex-wrap: wrap; gap: .6rem;
        margin-top: 1.3rem;
        background: rgba(255,255,255,.13);
        border: 1px solid rgba(255,255,255,.25);
        backdrop-filter: blur(10px);
        border-radius: 999px; padding: .45rem;
        transition: background .25s, box-shadow .25s;
    }
    .pt-search:focus-within { background: rgba(255,255,255,.2); box-shadow: 0 0 0 4px rgba(255,255,255,.12); }
    .pt-search .grp { position: relative; flex: 1 1 260px; }
    .pt-search .grp > i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,.75); }
    .pt-search input[type="search"] {
        width: 100%; border: 0; outline: 0; background: transparent;
        color: #fff; font-weight: 600; padding: .55rem 1rem .55rem 2.5rem;
    }
    .pt-search input[type="search"]::placeholder { color: rgba(255,255,255,.6); font-weight: 500; }
    .pt-search select {
        border: 0; outline: 0; cursor: pointer;
        background: rgba(255,255,255,.14); color: #fff; font-weight: 700; font-size: .85rem;
        border-radius: 999px; padding: .55rem 2.2rem .55rem 1.1rem;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='white' viewBox='0 0 16 16'%3E%3Cpath d='M1.5 5.5l6.5 6 6.5-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right .9rem center;
    }
    .pt-search select option { color: var(--pt-ink); }
    .pt-search .btn-go {
        border: 0; border-radius: 999px; padding: .55rem 1.4rem;
        background: #fff; color: var(--pt-primary); font-weight: 800; font-size: .85rem;
        display: inline-flex; align-items: center; gap: .45rem;
        transition: transform .2s, box-shadow .2s;
    }
    .pt-search .btn-go:hover { transform: translateY(-2px); box-shadow: 0 10px 22px -10px rgba(0,0,0,.45); }
    .pt-search .btn-reset {
        border: 1px solid rgba(255,255,255,.35); border-radius: 999px;
        background: transparent; color: #fff; padding: .55rem .95rem;
        display: inline-flex; align-items: center; transition: background .2s, transform .2s;
    }
    .pt-search .btn-reset:hover { background: rgba(255,255,255,.15); transform: rotate(90deg); }

    /* ---------- Stat cards ---------- */
    .pt-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.4rem; }
    @media (max-width: 991.98px) { .pt-stats { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 575.98px) { .pt-stats { grid-template-columns: 1fr; } }
    .pt-stat {
        position: relative; overflow: hidden;
        background: var(--pt-card-bg);
        border: 1px solid var(--pt-border);
        border-radius: var(--pt-radius);
        box-shadow: var(--pt-shadow);
        padding: 1.1rem 1.25rem;
        display: flex; align-items: center; gap: .95rem;
        transition: transform .3s cubic-bezier(.25,.8,.25,1), box-shadow .3s, border-color .3s;
    }
    .pt-stat:hover { transform: translateY(-4px); box-shadow: var(--pt-shadow-hover); border-color: #c7d2fe; }
    .pt-stat::after {
        content: ''; position: absolute; right: -28px; top: -28px;
        width: 86px; height: 86px; border-radius: 50%;
        background: var(--tone-soft, var(--tone-indigo-soft));
        transition: transform .35s;
    }
    .pt-stat:hover::after { transform: scale(1.35); }
    .pt-stat .ico {
        width: 50px; height: 50px; flex-shrink: 0; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
        background: var(--tone-soft, var(--tone-indigo-soft)); color: var(--tone, var(--pt-primary));
        transition: transform .3s cubic-bezier(.34,1.56,.64,1);
    }
    .pt-stat:hover .ico { transform: rotate(-6deg) scale(1.1); }
    .pt-stat .num { font-size: 1.45rem; font-weight: 800; color: var(--pt-ink); line-height: 1.15; font-variant-numeric: tabular-nums; }
    .pt-stat .lbl { font-size: .76rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--pt-secondary); }

    /* ---------- Tabs segmented ---------- */
    .pt-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .8rem; margin-bottom: 1.1rem; }
    .pt-seg {
        display: inline-flex; gap: 4px;
        background: var(--pt-card-bg); border: 1px solid var(--pt-border);
        border-radius: 999px; padding: 4px;
        box-shadow: var(--pt-shadow);
    }
    .pt-seg a {
        position: relative; border-radius: 999px; text-decoration: none;
        padding: .5rem 1.25rem; font-weight: 700; font-size: .85rem; color: var(--pt-secondary);
        display: inline-flex; align-items: center; gap: .45rem;
        transition: color .25s, background .25s, box-shadow .25s, transform .2s;
    }
    .pt-seg a:hover { color: var(--pt-primary); transform: translateY(-1px); }
    .pt-seg a.active {
        background: linear-gradient(135deg, var(--pt-primary), var(--pt-primary-2));
        color: #fff; box-shadow: 0 8px 18px -8px rgba(79,70,229,.6);
    }
    .pt-seg .cnt {
        font-size: .68rem; font-weight: 800; min-width: 20px; height: 20px; padding: 0 6px;
        border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
        background: var(--pt-warning); color: #fff;
    }
    .pt-seg a.active .cnt { background: rgba(255,255,255,.25); }
    .pt-result-info { font-size: .82rem; font-weight: 600; color: var(--pt-secondary); }

    /* ---------- Kartu tagihan ---------- */
    .tg-list { display: flex; flex-direction: column; gap: .9rem; }
    .tg-card {
        position: relative;
        background: var(--pt-card-bg);
        border: 1px solid var(--pt-border);
        border-radius: var(--pt-radius);
        box-shadow: var(--pt-shadow);
        padding: 1.15rem 1.35rem;
        transition: transform .3s cubic-bezier(.25,.8,.25,1), box-shadow .3s, border-color .3s;
        overflow: hidden;
    }
    .tg-card::before {
        content: ''; position: absolute; top: 0; left: 0; bottom: 0; width: 4px;
        background: linear-gradient(180deg, var(--tone, var(--pt-primary)), transparent 90%);
        opacity: .85;
    }
    .tg-card:hover { transform: translateY(-4px); box-shadow: var(--pt-shadow-hover); border-color: #c7d2fe; }
    .tg-card.attn { border-color: rgba(245,158,11,.45); }
    .tg-card.attn::before { background: linear-gradient(180deg, var(--pt-warning), transparent 90%); }

    .tg-grid { display: grid; grid-template-columns: minmax(260px, 1.6fr) minmax(180px, 1fr) minmax(220px, 1.2fr) auto; gap: 1.1rem; align-items: center; }
    @media (max-width: 1199.98px) { .tg-grid { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 767.98px) { .tg-grid { grid-template-columns: 1fr; gap: .85rem; } }

    .tg-ident { display: flex; gap: .9rem; align-items: flex-start; min-width: 0; }
    .tg-icon {
        width: 48px; height: 48px; flex-shrink: 0; border-radius: 13px;
        display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
        background: var(--tone-soft, var(--tone-indigo-soft)); color: var(--tone, var(--pt-primary));
        transition: transform .3s cubic-bezier(.34,1.56,.64,1);
    }
    .tg-card:hover .tg-icon { transform: rotate(-6deg) scale(1.1); }
    .tg-no { font-weight: 800; color: var(--pt-ink); font-size: .95rem; letter-spacing: -.2px; }
    .tg-no a { color: inherit; text-decoration: none; }
    .tg-no a:hover { color: var(--pt-primary); }
    .tg-desc { font-size: .8rem; color: var(--pt-secondary); margin-top: 1px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .tg-pihak { font-size: .77rem; font-weight: 700; color: #475569; margin-top: 3px; display: inline-flex; align-items: center; gap: .35rem; }
    .tg-tipe {
        display: inline-flex; align-items: center; gap: .3rem;
        font-size: .66rem; font-weight: 800; letter-spacing: .06em;
        padding: .2rem .6rem; border-radius: 999px; margin-top: 6px;
        background: var(--tone-soft, var(--tone-indigo-soft)); color: var(--tone, var(--pt-primary));
        border: 1px solid color-mix(in srgb, var(--tone, var(--pt-primary)) 25%, transparent);
    }

    .tg-nominal-lbl { font-size: .68rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--pt-secondary); }
    .tg-nominal { font-size: 1.08rem; font-weight: 800; color: var(--pt-ink); font-variant-numeric: tabular-nums; white-space: nowrap; }

    /* mini stepper per kartu */
    .tg-steps { min-width: 0; }
    .tg-steps .track { display: flex; align-items: center; gap: 0; margin-bottom: .45rem; }
    .tg-steps .nd {
        width: 22px; height: 22px; flex-shrink: 0; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: .6rem; font-weight: 800;
        background: #fff; border: 2px solid var(--pt-border); color: #94a3b8;
        transition: transform .25s cubic-bezier(.34,1.56,.64,1);
        position: relative; z-index: 1;
    }
    .tg-steps .nd:hover { transform: scale(1.25); }
    .tg-steps .nd.done { background: linear-gradient(135deg, var(--pt-success), #059669); border-color: transparent; color: #fff; }
    .tg-steps .nd.current { background: linear-gradient(135deg, var(--pt-primary), var(--pt-primary-2)); border-color: transparent; color: #fff; animation: ptPulseBlue 2.2s infinite; }
    .tg-steps .ln { flex: 1; height: 3px; background: var(--pt-border); border-radius: 2px; overflow: hidden; min-width: 12px; }
    .tg-steps .ln i { display: block; height: 100%; width: 0; background: linear-gradient(90deg, var(--pt-success), #34d399); transition: width .9s cubic-bezier(.16,1,.3,1); transition-delay: var(--ld, 0s); }
    .reveal.in .tg-steps .ln.fill i, .no-anim .tg-steps .ln.fill i { width: 100%; }
    .tg-steps .tahap { font-size: .78rem; font-weight: 700; color: var(--pt-ink); display: inline-flex; align-items: center; gap: .4rem; }
    .tg-steps .tahap .spin {
        width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0;
        background: var(--pt-primary); animation: ptPulseBlue 2s infinite;
    }
    .tg-steps .tahap.ok .spin { background: var(--pt-success); animation: none; }

    /* status & badges */
    .pt-status {
        display: inline-flex; align-items: center; gap: .4rem;
        font-size: .7rem; font-weight: 800; letter-spacing: .3px;
        padding: .35rem .8rem; border-radius: 999px; border: 1px solid transparent;
        white-space: nowrap;
    }
    .pt-status.success { background: rgba(16,185,129,.12); color: #047857; border-color: rgba(16,185,129,.3); }
    .pt-status.warning { background: rgba(245,158,11,.13); color: #b45309; border-color: rgba(245,158,11,.3); }
    .pt-status.danger  { background: rgba(239,68,68,.12); color: #b91c1c; border-color: rgba(239,68,68,.3); }
    .pt-status.info    { background: rgba(6,182,212,.12); color: #0e7490; border-color: rgba(6,182,212,.3); }
    .pt-status.neutral { background: rgba(100,116,139,.1); color: #475569; border-color: rgba(100,116,139,.25); }
    .pt-status.shimmer {
        background-image: linear-gradient(110deg, rgba(245,158,11,.10) 35%, rgba(245,158,11,.35) 50%, rgba(245,158,11,.10) 65%);
        background-size: 200% 100%;
        animation: ptShimmer 2.4s linear infinite;
    }

    /* tombol buka */
    .tg-actions { display: flex; flex-direction: column; align-items: flex-end; gap: .55rem; }
    @media (max-width: 767.98px) { .tg-actions { flex-direction: row; align-items: center; justify-content: space-between; } }
    .btn-pt-action {
        position: relative; overflow: hidden; text-decoration: none;
        border: 0; border-radius: 999px; font-weight: 800; font-size: .85rem; padding: .55rem 1.25rem;
        background: linear-gradient(135deg, var(--pt-primary), var(--pt-primary-2)); color: #fff;
        display: inline-flex; align-items: center; gap: .5rem;
        box-shadow: 0 10px 20px -10px rgba(79,70,229,.65);
        transition: transform .2s, box-shadow .2s;
        z-index: 2;
    }
    .btn-pt-action:hover { transform: translateY(-2px); color: #fff; box-shadow: 0 16px 28px -12px rgba(79,70,229,.7); }
    .btn-pt-action:active { transform: scale(.96); }
    .btn-pt-action .bi-arrow-right { transition: transform .25s; }
    .btn-pt-action:hover .bi-arrow-right { transform: translateX(4px); }
    .pt-ripple {
        position: absolute; border-radius: 50%; pointer-events: none;
        background: rgba(255,255,255,.55); transform: scale(0); opacity: 1;
        transition: transform .55s ease-out, opacity .6s ease-out;
    }
    .pt-ripple.go { transform: scale(4); opacity: 0; }

    /* link kartu penuh tanpa menutup tombol */
    .tg-card .stretched { position: absolute; inset: 0; z-index: 1; }

    /* empty state */
    .pt-empty {
        background: var(--pt-card-bg); border: 2px dashed var(--pt-border);
        border-radius: var(--pt-radius-lg); padding: 3.2rem 1.5rem; text-align: center;
    }
    .pt-empty .big {
        width: 86px; height: 86px; margin: 0 auto 1rem; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; font-size: 2.2rem;
        background: var(--tone-indigo-soft); color: var(--pt-primary);
        animation: ptBounce 2.6s ease-in-out infinite;
    }
    .pt-empty h5 { font-weight: 800; color: var(--pt-ink); }
    .pt-empty p { color: var(--pt-secondary); font-size: .9rem; max-width: 420px; margin: 0 auto .85rem; }

    /* alerts / toast */
    .pt-alert {
        border: 0; border-radius: var(--pt-radius);
        box-shadow: 0 10px 25px -12px rgba(15,23,42,.25);
        animation: ptSlideIn .45s cubic-bezier(.16,1,.3,1) both;
        display: flex; align-items: center; gap: .7rem;
        font-weight: 600;
        transition: opacity .5s, transform .5s;
    }
    .pt-alert.bye { opacity: 0; transform: translateY(-12px); }

    /* pagination */
    .pt-pagination { display: flex; justify-content: center; margin-top: 1.3rem; }
    .pt-pagination .pagination { gap: .35rem; }
    .pt-pagination .page-link {
        border-radius: 10px !important; border: 1px solid var(--pt-border);
        color: var(--pt-secondary); font-weight: 700; font-size: .85rem;
        transition: transform .2s, background .2s, color .2s;
    }
    .pt-pagination .page-link:hover { transform: translateY(-2px); color: var(--pt-primary); }
    .pt-pagination .page-item.active .page-link {
        background: linear-gradient(135deg, var(--pt-primary), var(--pt-primary-2));
        border-color: transparent; color: #fff;
        box-shadow: 0 8px 16px -8px rgba(79,70,229,.6);
    }

    /* highlight kata pencarian live */
    .tg-card.dim { opacity: .35; filter: grayscale(.6); transform: scale(.99); }
    .pt-live-hint { display: none; font-size: .8rem; color: var(--pt-secondary); font-weight: 600; }
    .pt-live-hint.show { display: block; animation: ptFadeUp .35s both; }
</style>
@endpush

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');

    $toneByTipe = [
        'KONTRAK' => ['tone' => 'var(--tone-indigo)', 'soft' => 'var(--tone-indigo-soft)', 'icon' => 'bi-file-earmark-text'],
        'PERJALDIN' => ['tone' => 'var(--tone-info)', 'soft' => 'var(--tone-info-soft)', 'icon' => 'bi-airplane'],
        'HONORARIUM' => ['tone' => 'var(--tone-violet)', 'soft' => 'var(--tone-violet-soft)', 'icon' => 'bi-cash-coin'],
    ];

    // Pemetaan tahap → indeks stepper mini (0..4)
    $stageMap = [
        'Menunggu COA & KPA' => 0,
        'Proses SPP/SPM/NPI' => 1,
        'Proses Dokumen (Alur Lama)' => 1,
        'Menunggu Penerbitan SP2D' => 2,
        'SP2D Terbit' => 3,
        'Selesai' => 4,
    ];
    $stageLabels = ['COA & KPA', 'SPP/SPM/NPI', 'SP2D', 'Terbit', 'Selesai'];
@endphp

<div class="pt-anim" id="ptRoot">

    {{-- ============ HERO ============ --}}
    <div class="pt-hero">
        <div class="grid-lines"></div>
        <div class="pt-hero-content d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="pt-chip"><i class="bi bi-diagram-3"></i> SPP / SPM / NPI / SP2D</span>
                    @if(($perluAksiCount ?? 0) > 0)
                        <span class="pt-chip tone-warning" style="background: rgba(245,158,11,.3); border-color: rgba(253,230,138,.6);">
                            <span class="dot"></span> {{ $perluAksiCount }} menunggu tindakan Anda
                        </span>
                    @endif
                </div>
                <h1>Proses Tagihan</h1>
                <div class="sub">Pantau dan kerjakan seluruh rantai pencairan — dari COA &amp; KPA hingga SP2D terbit — dalam satu halaman.</div>
            </div>
            <div class="text-lg-end">
                <div class="sub mb-1"><i class="bi bi-wallet2 me-1"></i>Total nominal ({{ $summary['total'] }} tagihan)</div>
                <div class="pt-amount">Rp <span data-countup data-target="{{ (int) $summary['nominal'] }}">0</span></div>
            </div>
        </div>

        {{-- search kaca --}}
        <form method="GET" class="pt-search" id="ptFilterForm">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="grp">
                <i class="bi bi-search"></i>
                <input type="search" name="search" id="ptSearch" value="{{ $search }}" placeholder="Cari nomor tagihan, uraian, atau pihak… (mengetik = saring langsung, Enter = cari semua)" autocomplete="off">
            </div>
            <select name="tipe" onchange="this.form.submit()">
                <option value="">Semua tipe</option>
                @foreach(['KONTRAK' => 'Kontrak', 'PERJALDIN' => 'Perjaldin', 'HONORARIUM' => 'Honorarium'] as $value => $label)
                    <option value="{{ $value }}" @selected(strtoupper((string) $tipeFilter) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn-go" type="submit"><i class="bi bi-funnel-fill"></i>Filter</button>
            <a href="{{ route('proses-tagihan.index') }}" class="btn-reset" title="Reset filter"><i class="bi bi-x-lg"></i></a>
        </form>
    </div>

    {{-- ============ FLASH ============ --}}
    @if(session('success'))
        <div class="alert alert-success pt-alert" data-autohide><i class="bi bi-check-circle-fill fs-5"></i><div>{{ session('success') }}</div></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger pt-alert" data-autohide><i class="bi bi-exclamation-triangle-fill fs-5"></i><div>{{ session('error') }}</div></div>
    @endif

    {{-- ============ STAT CARDS ============ --}}
    <div class="pt-stats">
        <div class="pt-stat reveal" style="--tone: var(--tone-indigo); --tone-soft: var(--tone-indigo-soft); --d: .05s;">
            <div class="ico"><i class="bi bi-collection"></i></div>
            <div>
                <div class="num" data-countup data-target="{{ $summary['total'] }}">0</div>
                <div class="lbl">Total Tagihan</div>
            </div>
        </div>
        <div class="pt-stat reveal" style="--tone: var(--tone-amber); --tone-soft: var(--tone-amber-soft); --d: .12s;">
            <div class="ico"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="num" data-countup data-target="{{ $summary['proses'] }}">0</div>
                <div class="lbl">Dalam Proses</div>
            </div>
        </div>
        <div class="pt-stat reveal" style="--tone: var(--tone-emerald); --tone-soft: var(--tone-emerald-soft); --d: .19s;">
            <div class="ico"><i class="bi bi-patch-check"></i></div>
            <div>
                <div class="num" data-countup data-target="{{ $summary['selesai'] }}">0</div>
                <div class="lbl">Selesai</div>
            </div>
        </div>
        <div class="pt-stat reveal" style="--tone: var(--tone-violet); --tone-soft: var(--tone-violet-soft); --d: .26s;">
            <div class="ico"><i class="bi bi-lightning-charge"></i></div>
            <div>
                <div class="num" data-countup data-target="{{ $perluAksiCount ?? 0 }}">0</div>
                <div class="lbl">Perlu Tindakan Saya</div>
            </div>
        </div>
    </div>

    {{-- ============ TABS + INFO ============ --}}
    <div class="pt-toolbar">
        <div class="pt-seg">
            <a class="{{ $tab === 'perlu-saya' ? 'active' : '' }}" href="{{ route('proses-tagihan.index', array_filter(['tab' => 'perlu-saya', 'search' => $search, 'tipe' => $tipeFilter])) }}">
                <i class="bi bi-person-check"></i> Perlu Tindakan Saya
                @if(($perluAksiCount ?? 0) > 0)<span class="cnt">{{ $perluAksiCount }}</span>@endif
            </a>
            <a class="{{ $tab !== 'perlu-saya' ? 'active' : '' }}" href="{{ route('proses-tagihan.index', array_filter(['tab' => 'semua', 'search' => $search, 'tipe' => $tipeFilter])) }}">
                <i class="bi bi-grid"></i> Semua
            </a>
        </div>
        <div class="pt-result-info">
            <i class="bi bi-list-check me-1"></i>Menampilkan <strong>{{ $tagihans->count() }}</strong> dari <strong>{{ $tab === 'perlu-saya' ? $tagihans->count() : $tagihans->total() }}</strong> tagihan
        </div>
    </div>

    <div class="pt-live-hint mb-2" id="ptLiveHint"><i class="bi bi-funnel me-1"></i>Saringan langsung aktif — tekan <kbd>Enter</kbd> untuk mencari di seluruh data.</div>

    {{-- ============ DAFTAR TAGIHAN ============ --}}
    <div class="tg-list" id="ptList">
        @forelse($tagihans as $tagihan)
            @php
                $state = $tagihan->proses_state ?? [];
                $tahap = data_get($state, 'tahap', '-');
                $perluSaya = (bool) data_get($state, 'perluSaya');
                $stageIdx = $stageMap[$tahap] ?? 0;
                $isSelesai = $tahap === 'Selesai';

                $pihak = $tagihan->detailKontrak?->kontrakTermin?->kontrak?->vendor?->nama_pihak
                    ?? $tagihan->pihak?->nama_pihak
                    ?? $tagihan->nama_supplier
                    ?? '-';

                $tone = $toneByTipe[$tagihan->tipe_tagihan] ?? ['tone' => 'var(--tone-slate)', 'soft' => 'var(--tone-slate-soft)', 'icon' => 'bi-receipt'];

                $statusTone = match (true) {
                    $tagihan->status === 'SELESAI' => 'success',
                    str_contains((string) $tagihan->status, 'TOLAK') || str_contains((string) $tagihan->status, 'BATAL') => 'danger',
                    str_contains((string) $tagihan->status, 'PROSES') => 'info',
                    default => 'neutral',
                };

                $searchHaystack = strtolower($tagihan->nomor_tagihan . ' ' . $tagihan->deskripsi . ' ' . $pihak . ' ' . $tagihan->tipe_tagihan);
            @endphp
            <div class="tg-card reveal {{ $perluSaya ? 'attn' : '' }}"
                 style="--tone: {{ $tone['tone'] }}; --tone-soft: {{ $tone['soft'] }}; --d: {{ min($loop->index * 0.07, 0.6) }}s;"
                 data-search="{{ $searchHaystack }}">
                <a class="stretched" href="{{ route('proses-tagihan.show', $tagihan->id) }}" aria-label="Buka {{ $tagihan->nomor_tagihan }}"></a>
                <div class="tg-grid">
                    {{-- identitas --}}
                    <div class="tg-ident">
                        <div class="tg-icon"><i class="bi {{ $tone['icon'] }}"></i></div>
                        <div class="min-w-0">
                            <div class="tg-no">{{ $tagihan->nomor_tagihan }}</div>
                            <div class="tg-desc">{{ \Illuminate\Support\Str::limit($tagihan->deskripsi, 110) }}</div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="tg-pihak"><i class="bi bi-building"></i>{{ $pihak }}</span>
                                <span class="tg-tipe"><i class="bi {{ $tone['icon'] }}"></i>{{ $tagihan->tipe_tagihan }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- nominal --}}
                    <div>
                        <div class="tg-nominal-lbl">Nominal</div>
                        <div class="tg-nominal">Rp {{ $fmt($tagihan->total_netto) }}</div>
                        <div class="text-muted" style="font-size: .72rem;">
                            <i class="bi bi-clock-history me-1"></i>{{ $tagihan->updated_at?->diffForHumans() }}
                        </div>
                    </div>

                    {{-- mini pipeline --}}
                    <div class="tg-steps">
                        <div class="track">
                            @foreach($stageLabels as $i => $lbl)
                                @php
                                    $cls = $isSelesai || $i < $stageIdx ? 'done' : ($i === $stageIdx ? 'current' : '');
                                @endphp
                                <span class="nd {{ $cls }}" title="{{ $lbl }}">
                                    @if($cls === 'done')<i class="bi bi-check"></i>@else{{ $i + 1 }}@endif
                                </span>
                                @if(! $loop->last)
                                    <span class="ln {{ $isSelesai || $i < $stageIdx ? 'fill' : '' }}" style="--ld: {{ .15 + $i * .12 }}s;"><i></i></span>
                                @endif
                            @endforeach
                        </div>
                        <span class="tahap {{ $isSelesai ? 'ok' : '' }}">
                            <span class="spin"></span>{{ $tahap }}
                        </span>
                    </div>

                    {{-- status + aksi --}}
                    <div class="tg-actions">
                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                            <span class="pt-status {{ $statusTone }}">{{ str_replace('_', ' ', $tagihan->status) }}</span>
                            @if($perluSaya)
                                <span class="pt-status warning shimmer"><i class="bi bi-bell-fill"></i>Perlu aksi</span>
                            @endif
                        </div>
                        <a href="{{ route('proses-tagihan.show', $tagihan->id) }}" class="btn-pt-action" data-ripple>
                            Buka <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="pt-empty reveal">
                @if($tab === 'perlu-saya')
                    <div class="big" style="background: var(--tone-emerald-soft); color: var(--pt-success);"><i class="bi bi-emoji-sunglasses"></i></div>
                    <h5>Tidak ada yang menunggu Anda 🎉</h5>
                    <p>Semua tagihan pada filter ini sudah ditindaklanjuti. Cek tab <strong>Semua</strong> untuk memantau progres keseluruhan.</p>
                    <a href="{{ route('proses-tagihan.index', array_filter(['search' => $search, 'tipe' => $tipeFilter])) }}" class="btn-pt-action"><i class="bi bi-grid"></i> Lihat Semua Tagihan</a>
                @else
                    <div class="big"><i class="bi bi-inbox"></i></div>
                    <h5>Belum ada tagihan</h5>
                    <p>Tidak ditemukan tagihan pada filter ini. Coba ubah kata kunci pencarian atau reset filter.</p>
                    <a href="{{ route('proses-tagihan.index') }}" class="btn-pt-action"><i class="bi bi-arrow-counterclockwise"></i> Reset Filter</a>
                @endif
            </div>
        @endforelse

        {{-- empty state khusus hasil saringan live (JS) --}}
        <div class="pt-empty" id="ptLiveEmpty" style="display: none;">
            <div class="big" style="background: var(--tone-amber-soft); color: var(--pt-warning);"><i class="bi bi-search"></i></div>
            <h5>Tidak ada yang cocok di halaman ini</h5>
            <p>Tekan <kbd>Enter</kbd> untuk mencari di seluruh data, bukan hanya halaman ini.</p>
        </div>
    </div>

    @if($tagihans->hasPages())
        <div class="pt-pagination">
            {{ $tagihans->links() }}
        </div>
    @endif
</div>
@endsection

@push('script')
<script>
(function () {
    'use strict';

    var root = document.getElementById('ptRoot');
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- Reveal on scroll ---------- */
    var reveals = root.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && !reduceMotion) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
            });
        }, { threshold: .12 });
        reveals.forEach(function (el) { io.observe(el); });
    } else {
        root.classList.add('no-anim');
        reveals.forEach(function (el) { el.classList.add('in'); });
    }

    /* ---------- Count-up angka ---------- */
    function countUp(el) {
        var target = parseInt(el.dataset.target || '0', 10);
        if (reduceMotion || target === 0) { el.textContent = target.toLocaleString('id-ID'); return; }
        var dur = 1200, t0 = null;
        function tick(t) {
            if (!t0) t0 = t;
            var p = Math.min((t - t0) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(target * eased).toLocaleString('id-ID');
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    }
    document.querySelectorAll('[data-countup]').forEach(countUp);

    /* ---------- Ripple tombol ---------- */
    root.addEventListener('click', function (ev) {
        var btn = ev.target.closest('[data-ripple]');
        if (!btn) return;
        var rect = btn.getBoundingClientRect();
        var r = document.createElement('span');
        var size = Math.max(rect.width, rect.height);
        r.className = 'pt-ripple';
        r.style.width = r.style.height = size + 'px';
        r.style.left = (ev.clientX - rect.left - size / 2) + 'px';
        r.style.top = (ev.clientY - rect.top - size / 2) + 'px';
        btn.appendChild(r);
        requestAnimationFrame(function () { r.classList.add('go'); });
        setTimeout(function () { r.remove(); }, 650);
    });

    /* ---------- Saringan langsung (client-side, halaman ini) ---------- */
    var input = document.getElementById('ptSearch');
    var hint = document.getElementById('ptLiveHint');
    var liveEmpty = document.getElementById('ptLiveEmpty');
    var cards = Array.prototype.slice.call(root.querySelectorAll('.tg-card'));
    var debounce;

    if (input) {
        input.addEventListener('input', function () {
            clearTimeout(debounce);
            var q = input.value.trim().toLowerCase();
            debounce = setTimeout(function () {
                var visible = 0;
                cards.forEach(function (card) {
                    var match = !q || (card.dataset.search || '').indexOf(q) !== -1;
                    card.classList.toggle('dim', !match);
                    card.style.display = match ? '' : 'none';
                    if (match) visible++;
                });
                hint.classList.toggle('show', !!q);
                if (liveEmpty) liveEmpty.style.display = (q && visible === 0 && cards.length > 0) ? '' : 'none';
            }, 120);
        });
    }

    /* ---------- Auto-hide flash alert ---------- */
    document.querySelectorAll('[data-autohide]').forEach(function (el) {
        setTimeout(function () {
            el.classList.add('bye');
            setTimeout(function () { el.remove(); }, 550);
        }, 4500);
    });
})();
</script>
@endpush
