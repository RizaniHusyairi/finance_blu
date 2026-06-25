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

    /* ---------- Live AJAX loading ---------- */
    #pt-results { position: relative; transition: opacity .15s ease; }
    #pt-results.pt-loading { opacity: .5; pointer-events: none; }
    #pt-results.pt-loading::after {
        content: ""; position: absolute; left: calc(50% - 1.1rem); top: 80px;
        width: 2.2rem; height: 2.2rem;
        border: 3px solid var(--pt-primary); border-right-color: transparent; border-radius: 50%;
        animation: ptSpin .7s linear infinite; z-index: 5;
    }
    @keyframes ptSpin { to { transform: rotate(360deg); } }
</style>
@endpush

@section('content')

<div class="pt-anim" id="ptRoot">

    {{-- ============ HERO ============ --}}
    <div class="pt-hero">
        <div class="grid-lines"></div>
        <div class="pt-hero-content d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="pt-chip"><i class="bi bi-diagram-3"></i> SPP / SPM / NPI / SP2D</span>
                    <span class="pt-chip tone-warning" id="ptHeroChip" style="background: rgba(245,158,11,.3); border-color: rgba(253,230,138,.6); {{ ($perluAksiCount ?? 0) > 0 ? '' : 'display:none;' }}">
                        <span class="dot"></span> <span id="ptHeroChipCount">{{ $perluAksiCount ?? 0 }}</span> menunggu tindakan Anda
                    </span>
                </div>
                <h1>Proses Tagihan</h1>
                <div class="sub">Pantau dan kerjakan seluruh rantai pencairan — dari COA &amp; KPA hingga SP2D terbit — dalam satu halaman.</div>
            </div>
            <div class="text-lg-end">
                <div class="sub mb-1"><i class="bi bi-wallet2 me-1"></i>Total nominal (<span id="ptHeroCount">{{ $summary['total'] }}</span> tagihan)</div>
                <div class="pt-amount">Rp <span id="ptHeroNominal" data-countup data-target="{{ (int) $summary['nominal'] }}">0</span></div>
            </div>
        </div>

        {{-- search kaca --}}
        <form method="GET" class="pt-search" id="ptFilterForm">
            <input type="hidden" name="tab" id="ptTabInput" value="{{ $tab }}">
            <div class="grp">
                <i class="bi bi-search"></i>
                <input type="search" name="search" id="ptSearch" value="{{ $search }}" placeholder="Cari nomor tagihan, uraian, atau pihak… (otomatis saat mengetik)" autocomplete="off">
            </div>
            <select name="tipe" id="ptTipe">
                <option value="">Semua tipe</option>
                @foreach(['KONTRAK' => 'Kontrak', 'PERJALDIN' => 'Perjaldin', 'HONORARIUM' => 'Honorarium'] as $value => $label)
                    <option value="{{ $value }}" @selected(strtoupper((string) $tipeFilter) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn-go" type="submit"><i class="bi bi-funnel-fill"></i>Filter</button>
            <a href="{{ route('proses-tagihan.index') }}" class="btn-reset" data-pt-reset title="Reset filter"><i class="bi bi-x-lg"></i></a>
        </form>
    </div>

    {{-- ============ FLASH ============ --}}
    @if(session('success'))
        <div class="alert alert-success pt-alert" data-autohide><i class="bi bi-check-circle-fill fs-5"></i><div>{{ session('success') }}</div></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger pt-alert" data-autohide><i class="bi bi-exclamation-triangle-fill fs-5"></i><div>{{ session('error') }}</div></div>
    @endif

    <div id="pt-results">
        @include('proses_tagihan._results')
    </div>
</div>
@endsection

@push('script')
<script>
(function () {
    'use strict';

    var root = document.getElementById('ptRoot');
    if (!root) return;
    var results = document.getElementById('pt-results');
    var form = document.getElementById('ptFilterForm');
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var baseUrl = "{{ route('proses-tagihan.index') }}";

    /* ---------- Count-up angka ---------- */
    function countUp(el) {
        var target = parseInt(el.dataset.target || '0', 10);
        if (reduceMotion || target === 0) { el.textContent = target.toLocaleString('id-ID'); return; }
        var dur = 1100, t0 = null;
        function tick(t) {
            if (!t0) t0 = t;
            var p = Math.min((t - t0) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(target * eased).toLocaleString('id-ID');
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    }

    /* ---------- Reveal on scroll ---------- */
    var io = null;
    if ('IntersectionObserver' in window && !reduceMotion) {
        io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
            });
        }, { threshold: .12 });
    } else {
        root.classList.add('no-anim');
    }
    function revealIn(scope) {
        var els = scope.querySelectorAll('.reveal');
        if (io) { els.forEach(function (el) { io.observe(el); }); }
        else { els.forEach(function (el) { el.classList.add('in'); }); }
    }

    /* pass awal */
    revealIn(root);
    document.querySelectorAll('[data-countup]').forEach(countUp);

    /* ---------- Ripple tombol (delegated, stabil walau konten diganti) ---------- */
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

    /* ---------- Auto-hide flash alert ---------- */
    document.querySelectorAll('[data-autohide]').forEach(function (el) {
        setTimeout(function () {
            el.classList.add('bye');
            setTimeout(function () { el.remove(); }, 550);
        }, 4500);
    });

    /* ====================================================
       LIVE-SEARCH AJAX — search, tipe, tab & paginasi tanpa
       reload. Form tetap di hero (di luar #pt-results) →
       fokus & kursor input aman saat mengetik.
       ==================================================== */
    if (!results || !form) return;

    var search = document.getElementById('ptSearch');
    var tipe = document.getElementById('ptTipe');
    var tabInput = document.getElementById('ptTabInput');
    var heroNominal = document.getElementById('ptHeroNominal');
    var heroCount = document.getElementById('ptHeroCount');
    var heroChip = document.getElementById('ptHeroChip');
    var heroChipCount = document.getElementById('ptHeroChipCount');
    var debounce, controller;

    function buildUrl() {
        var params = new URLSearchParams();
        new FormData(form).forEach(function (v, k) {
            if (String(v).trim() !== '') params.append(k, v);
        });
        var qs = params.toString();
        return qs ? (baseUrl + '?' + qs) : baseUrl;
    }

    function syncHero() {
        var data = document.getElementById('ptResultsData');
        if (!data) return;
        if (heroNominal) { heroNominal.dataset.target = data.dataset.nominal || '0'; countUp(heroNominal); }
        if (heroCount) { heroCount.textContent = parseInt(data.dataset.total || '0', 10).toLocaleString('id-ID'); }
        var perlu = parseInt(data.dataset.perlu || '0', 10);
        if (heroChip) { heroChip.style.display = perlu > 0 ? '' : 'none'; }
        if (heroChipCount) { heroChipCount.textContent = perlu.toLocaleString('id-ID'); }
    }

    function afterSwap() {
        revealIn(results);
        results.querySelectorAll('[data-countup]').forEach(countUp);
        syncHero();
    }

    function load(url) {
        if (controller) controller.abort();
        controller = new AbortController();
        results.classList.add('pt-loading');
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, signal: controller.signal })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                results.innerHTML = html;
                results.classList.remove('pt-loading');
                window.history.replaceState(null, '', url);
                afterSwap();
            })
            .catch(function (err) { if (err.name !== 'AbortError') results.classList.remove('pt-loading'); });
    }

    function refresh() { load(buildUrl()); }

    /* ketik di kotak cari → debounce 300ms (server-side, seluruh data) */
    if (search) {
        search.addEventListener('input', function () {
            clearTimeout(debounce);
            debounce = setTimeout(refresh, 300);
        });
    }

    /* dropdown tipe → filter instan */
    if (tipe) { tipe.addEventListener('change', refresh); }

    /* tombol Filter / Enter → AJAX, bukan reload penuh */
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearTimeout(debounce);
        refresh();
    });

    /* klik tab / paginasi di dalam hasil → AJAX (delegated, konten bisa diganti) */
    results.addEventListener('click', function (e) {
        var tab = e.target.closest('[data-tab]');
        if (tab) {
            e.preventDefault();
            if (tabInput) tabInput.value = tab.getAttribute('data-tab');
            refresh();
            return;
        }
        var page = e.target.closest('.pagination a');
        if (page && page.getAttribute('href')) {
            e.preventDefault();
            load(page.getAttribute('href'));
        }
    });

    /* tombol Reset (di hero & empty-state) → kosongkan filter lalu muat ulang */
    document.addEventListener('click', function (e) {
        if (!e.target.closest('[data-pt-reset]')) return;
        e.preventDefault();
        if (search) search.value = '';
        if (tipe) tipe.value = '';
        if (tabInput) tabInput.value = 'semua';
        clearTimeout(debounce);
        refresh();
        if (search) search.focus();
    });
})();
</script>
@endpush
