@extends('layouts.app')
@section('title', 'Detail Tagihan Kontrak')

@push('css')
<style>
    :root {
        --kx-p1: #5b4dff;
        --kx-p2: #7c4dff;
        --kx-p3: #845ef7;
        --kx-emerald: #00c853;
        --kx-success: #00d084;
        --kx-warning: #ffb020;
        --kx-danger: #ff4d6d;
        --kx-ink: #0f172a;
        --kx-muted: #64748b;
        --kx-bg: #f7f9fc;
        --kx-shadow: rgba(15, 23, 42, .08);
    }

    .kx-page { background: var(--kx-bg); margin: -1.5rem; padding: 1.75rem clamp(1rem, 3vw, 2.5rem) 3rem; min-height: 100vh; }

    /* ═══ Motion ═══ */
    @keyframes kxUp { from { opacity: 0; transform: translateY(22px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes kxBlob {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33%      { transform: translate(40px, -25px) scale(1.12); }
        66%      { transform: translate(-25px, 20px) scale(.94); }
    }
    @keyframes kxPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(255, 255, 255, .55); }
        60%      { box-shadow: 0 0 0 9px rgba(255, 255, 255, 0); }
    }
    @keyframes kxPulseP {
        0%, 100% { box-shadow: 0 0 0 0 rgba(91, 77, 255, .45); }
        60%      { box-shadow: 0 0 0 10px rgba(91, 77, 255, 0); }
    }
    @keyframes kxShine { 0% { transform: translateX(-140%) skewX(-16deg); } 100% { transform: translateX(260%) skewX(-16deg); } }
    @keyframes kxGrow { from { width: 0; } }
    @keyframes kxFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
    @keyframes kxDash { to { stroke-dashoffset: 0; } }
    .kx-up { opacity: 0; animation: kxUp .6s cubic-bezier(.22, 1, .36, 1) forwards; animation-delay: var(--d, 0s); }

    /* ═══ Breadcrumb ═══ */
    .kx-crumb { display: flex; align-items: center; gap: .45rem; font-size: .78rem; font-weight: 600; color: var(--kx-muted); }
    .kx-crumb a { color: var(--kx-muted); text-decoration: none; transition: color .2s ease; }
    .kx-crumb a:hover { color: var(--kx-p1); }
    .kx-crumb .sep { opacity: .45; }
    .kx-crumb .now { color: var(--kx-ink); font-weight: 700; }

    /* ═══ Hero ═══ */
    .kx-hero {
        position: relative; overflow: hidden;
        border-radius: 26px;
        padding: clamp(1.5rem, 3vw, 2.3rem);
        color: #fff;
        background: linear-gradient(115deg, #1e1b4b 0%, var(--kx-p1) 45%, var(--kx-p3) 100%);
        box-shadow: 0 24px 60px -20px rgba(91, 77, 255, .55);
        isolation: isolate;
    }
    /* Aksen halus: lingkaran putih transparan + satu blob gelap yang bergerak
       pelan — sengaja redup agar teks hero tetap kontras. */
    .kx-hero::before, .kx-hero::after {
        content: ''; position: absolute; border-radius: 50%; pointer-events: none; z-index: -1;
        background: rgba(255, 255, 255, .07);
    }
    .kx-hero::before { width: 320px; height: 320px; top: -160px; right: -70px; }
    .kx-hero::after  { width: 190px; height: 190px; bottom: -110px; right: 230px; background: rgba(255, 255, 255, .05); }
    .kx-blob {
        position: absolute; border-radius: 50%; pointer-events: none; z-index: -1;
        filter: blur(70px); animation: kxBlob 16s ease-in-out infinite;
    }
    .kx-blob.b1 { width: 300px; height: 300px; top: -120px; left: 34%; background: rgba(30, 27, 75, .55); }
    .kx-blob.b2 { width: 260px; height: 260px; bottom: -140px; right: -40px; background: rgba(56, 189, 248, .18); animation-delay: -6s; }
    .kx-blob.b3 { display: none; }
    .kx-hero-icon {
        width: 68px; height: 68px; flex-shrink: 0;
        display: grid; place-items: center;
        border-radius: 22px; font-size: 1.9rem;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .28);
        backdrop-filter: blur(10px);
        animation: kxFloat 5.5s ease-in-out infinite;
    }
    .kx-hero-title { font-size: clamp(1.3rem, 2.4vw, 1.7rem); font-weight: 800; letter-spacing: -.6px; overflow-wrap: anywhere; }
    .kx-hero-sub { font-size: .98rem; font-weight: 600; color: rgba(255, 255, 255, .88); }
    .kx-status {
        display: inline-flex; align-items: center; gap: .45rem;
        padding: .4rem .95rem; border-radius: 999px;
        font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;
        background: rgba(255, 255, 255, .16); border: 1px solid rgba(255, 255, 255, .32);
        backdrop-filter: blur(8px);
    }
    .kx-status .dot { width: 8px; height: 8px; border-radius: 50%; background: #4ade80; }
    .kx-status.live .dot { animation: kxPulse 1.8s ease-out infinite; }
    .kx-chip {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .34rem .8rem; border-radius: 999px;
        background: rgba(255, 255, 255, .12); border: 1px solid rgba(255, 255, 255, .2);
        backdrop-filter: blur(8px);
        font-size: .74rem; font-weight: 700; max-width: 100%;
        transition: background .2s ease, transform .2s ease;
    }
    .kx-chip:hover { background: rgba(255, 255, 255, .22); }
    .kx-chip .txt { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 300px; }
    .kx-chip.copyable { cursor: pointer; }
    .kx-btn-w {
        position: relative; overflow: hidden;
        border: 0; border-radius: 16px;
        padding: .78rem 1.5rem; font-weight: 800;
        color: var(--kx-p1); background: #fff;
        box-shadow: 0 12px 28px -10px rgba(0, 0, 0, .4);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .kx-btn-w:hover { transform: translateY(-2px) scale(1.02); color: var(--kx-p1); box-shadow: 0 18px 34px -12px rgba(0, 0, 0, .45); }
    .kx-btn-w::after { content: ''; position: absolute; inset: 0; width: 45%; background: linear-gradient(90deg, transparent, rgba(91, 77, 255, .14), transparent); animation: kxShine 3.2s ease-in-out infinite; }
    .kx-btn-g {
        border: 1px solid rgba(255, 255, 255, .4); border-radius: 16px;
        padding: .78rem 1.25rem; font-weight: 700; color: #fff;
        background: rgba(255, 255, 255, .08); backdrop-filter: blur(8px);
        transition: background .2s ease, transform .2s ease;
    }
    .kx-btn-g:hover { background: rgba(255, 255, 255, .2); color: #fff; transform: translateY(-2px); }

    /* ═══ KPI ═══ */
    .kx-kpi {
        position: relative; overflow: hidden;
        border-radius: 22px; height: 100%;
        background: rgba(255, 255, 255, .82);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, .9);
        box-shadow: 0 8px 30px var(--kx-shadow), inset 0 1px 0 #fff;
        padding: 1.3rem 1.4rem;
        transition: transform .25s ease, box-shadow .25s ease;
    }
    .kx-kpi:hover { transform: translateY(-5px); box-shadow: 0 22px 44px rgba(15, 23, 42, .13), inset 0 1px 0 #fff; }
    .kx-kpi::before { content: ''; position: absolute; width: 150px; height: 150px; border-radius: 50%; top: -75px; right: -45px; background: var(--tone-soft); filter: blur(6px); }
    .kx-kpi .lbl { font-size: .66rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--kx-muted); }
    .kx-kpi .val { font-size: clamp(1.3rem, 2vw, 1.6rem); font-weight: 800; letter-spacing: -.8px; color: var(--kx-ink); font-variant-numeric: tabular-nums; }
    .kx-kpi .sub { font-size: .72rem; color: #94a3b8; }
    .kx-kpi .ic {
        width: 46px; height: 46px;
        display: grid; place-items: center;
        border-radius: 15px; font-size: 1.3rem;
        color: #fff; background: linear-gradient(135deg, var(--tone), var(--tone-2));
        box-shadow: 0 10px 20px -8px var(--tone);
        transition: transform .25s ease;
    }
    .kx-kpi:hover .ic { transform: scale(1.1) rotate(-6deg); }
    .kx-kpi svg.spark { position: absolute; bottom: 0; left: 0; right: 0; width: 100%; height: 42px; opacity: .5; }
    .kx-kpi svg.spark path { stroke: var(--tone); stroke-width: 2.5; fill: none; stroke-dasharray: 320; stroke-dashoffset: 320; animation: kxDash 1.6s ease forwards .4s; }
    .kx-kpi.hero-kpi { color: #fff; background: linear-gradient(120deg, #047857, var(--kx-success)); border: 0; box-shadow: 0 18px 40px -14px rgba(0, 208, 132, .65); }
    .kx-kpi.hero-kpi .lbl { color: rgba(255, 255, 255, .8); }
    .kx-kpi.hero-kpi .val { color: #fff; }
    .kx-kpi.hero-kpi .sub { color: rgba(255, 255, 255, .75); }
    .kx-kpi.hero-kpi::after { content: ''; position: absolute; inset: 0; width: 40%; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .22), transparent); animation: kxShine 3.6s ease-in-out infinite; }
    .kx-kpi.hero-kpi svg.spark path { stroke: rgba(255, 255, 255, .85); }

    /* ═══ Stepper ═══ */
    .kx-stepper {
        display: flex; align-items: flex-start;
        border-radius: 22px;
        background: rgba(255, 255, 255, .82); backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, .9);
        box-shadow: 0 8px 30px var(--kx-shadow);
        padding: 1.35rem 1.2rem 1.15rem;
        overflow-x: auto;
    }
    .kx-step { flex: 1; min-width: 108px; text-align: center; position: relative; }
    .kx-step:not(:last-child)::after {
        content: ''; position: absolute; top: 21px; left: calc(50% + 26px); right: calc(-50% + 26px);
        height: 3px; border-radius: 3px; background: #e2e8f0;
    }
    .kx-step.done:not(:last-child)::after { background: linear-gradient(90deg, var(--kx-success), #34d399); animation: kxGrow .8s ease both; }
    .kx-step .bulb {
        width: 42px; height: 42px; margin: 0 auto .5rem;
        display: grid; place-items: center;
        border-radius: 50%; font-size: 1.05rem;
        background: #f1f5f9; color: #94a3b8; border: 2px solid #e2e8f0;
        transition: transform .25s ease;
        position: relative; z-index: 1;
    }
    .kx-step:hover .bulb { transform: scale(1.12); }
    .kx-step.done .bulb { background: linear-gradient(135deg, #047857, var(--kx-success)); color: #fff; border-color: transparent; box-shadow: 0 8px 18px -8px var(--kx-success); }
    .kx-step.now .bulb { background: linear-gradient(135deg, var(--kx-p1), var(--kx-p3)); color: #fff; border-color: transparent; animation: kxPulseP 1.9s ease-out infinite; }
    .kx-step .t { font-size: .72rem; font-weight: 800; color: var(--kx-ink); line-height: 1.2; }
    .kx-step .s { font-size: .64rem; color: #94a3b8; font-weight: 600; }
    .kx-step.pending .t { color: #94a3b8; }

    /* ═══ Kartu bento ═══ */
    .kx-card {
        border-radius: 22px;
        background: rgba(255, 255, 255, .86); backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, .9);
        box-shadow: 0 8px 30px var(--kx-shadow);
        overflow: hidden;
        transition: box-shadow .25s ease, transform .25s ease;
    }
    .kx-card:hover { box-shadow: 0 16px 40px rgba(15, 23, 42, .11); }
    .kx-card-head { display: flex; align-items: center; gap: .85rem; padding: 1.25rem 1.5rem .85rem; }
    .kx-card-head .ic {
        width: 42px; height: 42px; flex-shrink: 0;
        display: grid; place-items: center;
        border-radius: 14px; font-size: 1.15rem;
        color: #fff; background: linear-gradient(135deg, var(--tone, var(--kx-p1)), var(--tone-2, var(--kx-p3)));
        box-shadow: 0 8px 18px -8px var(--tone, var(--kx-p1));
    }
    .kx-card-title { font-weight: 800; font-size: 1rem; color: var(--kx-ink); margin: 0; letter-spacing: -.2px; }
    .kx-card-sub { font-size: .72rem; color: #94a3b8; }
    .kx-card-body { padding: .4rem 1.5rem 1.5rem; }

    .kx-info { display: flex; align-items: flex-start; gap: .7rem; padding: .55rem .1rem; }
    .kx-info .mi {
        width: 34px; height: 34px; flex-shrink: 0;
        display: grid; place-items: center;
        border-radius: 11px; font-size: .95rem;
        color: var(--kx-p1); background: rgba(91, 77, 255, .09);
    }
    .kx-info .k { font-size: .64rem; font-weight: 800; letter-spacing: .09em; text-transform: uppercase; color: #94a3b8; }
    .kx-info .v { font-weight: 700; font-size: .88rem; color: var(--kx-ink); overflow-wrap: anywhere; }

    /* ═══ Breakdown finansial ═══ */
    .kx-brk { display: flex; align-items: center; gap: .9rem; padding: .6rem 0; }
    .kx-brk .nm { flex: 0 0 150px; font-size: .78rem; font-weight: 700; color: var(--kx-ink); }
    .kx-brk .track { flex: 1; height: 9px; border-radius: 999px; background: #eef1f6; overflow: hidden; }
    .kx-brk .fillb { height: 100%; border-radius: 999px; width: var(--w); background: linear-gradient(90deg, var(--bc), var(--bc2)); animation: kxGrow 1.1s cubic-bezier(.22, 1, .36, 1) both; animation-delay: var(--bd, 0s); }
    .kx-brk .amt { flex: 0 0 auto; min-width: 128px; text-align: right; font-weight: 800; font-size: .84rem; color: var(--kx-ink); font-variant-numeric: tabular-nums; }
    .kx-brk.total { border-top: 2px dashed #e2e8f0; margin-top: .4rem; padding-top: .9rem; }
    .kx-brk.total .nm, .kx-brk.total .amt { font-size: .95rem; color: #047857; }

    /* ═══ Dokumen ═══ */
    .kx-doc {
        display: flex; flex-direction: column;
        border: 1px solid #eef1f6; border-radius: 18px;
        overflow: hidden; height: 100%;
        background: #fff; text-decoration: none;
        transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    }
    .kx-doc:hover { transform: translateY(-4px); border-color: #ddd6fe; box-shadow: 0 16px 30px rgba(91, 77, 255, .13); }
    .kx-doc .thumb {
        height: 86px;
        display: grid; place-items: center;
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        font-size: 2rem; color: #dc2626;
        position: relative;
    }
    .kx-doc .thumb .ext { position: absolute; top: .6rem; right: .6rem; font-size: .58rem; font-weight: 800; letter-spacing: .06em; background: #dc2626; color: #fff; border-radius: 6px; padding: .15rem .45rem; }
    .kx-doc .meta { padding: .8rem .95rem; }
    .kx-doc .t { font-weight: 800; font-size: .8rem; color: var(--kx-ink); }
    .kx-doc .s { font-size: .68rem; color: #94a3b8; overflow-wrap: anywhere; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
    .kx-doc .foot { margin-top: auto; display: flex; align-items: center; justify-content: space-between; padding: 0 .95rem .85rem; font-size: .66rem; color: #94a3b8; font-weight: 600; }
    .kx-doc .open { color: var(--kx-p1); font-weight: 800; display: inline-flex; align-items: center; gap: .25rem; transition: gap .2s ease; }
    .kx-doc:hover .open { gap: .5rem; }

    /* ═══ Vendor ═══ */
    .kx-vendor-logo {
        width: 58px; height: 58px; flex-shrink: 0;
        display: grid; place-items: center;
        border-radius: 18px; font-weight: 800; font-size: 1.25rem; color: #fff;
        background: linear-gradient(135deg, #047857, var(--kx-success));
        box-shadow: 0 10px 22px -8px var(--kx-success);
    }
    .kx-rek {
        border-radius: 16px; padding: .85rem 1rem;
        background: linear-gradient(135deg, #ecfdf5, #f0fdfa);
        border: 1px dashed #6ee7b7;
    }
    .kx-empty { text-align: center; padding: 1.6rem 1rem; }
    .kx-empty .art {
        width: 92px; height: 92px; margin: 0 auto 1rem; position: relative;
        display: grid; place-items: center;
        border-radius: 30px; font-size: 2.3rem; color: var(--kx-p1);
        background: linear-gradient(135deg, #eef2ff, #faf5ff);
        border: 1px dashed #c7d2fe;
        animation: kxFloat 4.5s ease-in-out infinite;
    }
    .kx-empty .art::before, .kx-empty .art::after {
        content: ''; position: absolute; border-radius: 50%;
        background: linear-gradient(135deg, var(--kx-p1), var(--kx-p3)); opacity: .18;
    }
    .kx-empty .art::before { width: 22px; height: 22px; top: -8px; right: -10px; }
    .kx-empty .art::after { width: 14px; height: 14px; bottom: -4px; left: -8px; }

    /* ═══ Verifikator per role (kartu info, bukan alur berjenjang) ═══ */
    .kx-vrf { display: flex; flex-direction: column; gap: .7rem; }
    .kx-vrf-item {
        display: flex; align-items: flex-start; gap: .75rem;
        border: 1px solid #eef1f6; border-radius: 16px;
        background: #fff; padding: .8rem .9rem;
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }
    .kx-vrf-item:hover { transform: translateY(-2px); border-color: color-mix(in srgb, var(--rt, var(--kx-p1)) 35%, #fff); box-shadow: 0 12px 24px -12px var(--rt, var(--kx-p1)); }
    .kx-vrf-item .ava {
        width: 40px; height: 40px; flex-shrink: 0;
        display: grid; place-items: center;
        border-radius: 13px; font-weight: 800; font-size: .74rem; color: #fff;
        background: linear-gradient(135deg, var(--rt, var(--kx-p1)), color-mix(in srgb, var(--rt, var(--kx-p1)) 60%, #fff));
        box-shadow: 0 6px 14px -6px var(--rt, var(--kx-p1));
        transition: transform .2s ease;
    }
    .kx-vrf-item:hover .ava { transform: scale(1.1) rotate(-4deg); }
    .kx-vrf-item .info { flex: 1 1 auto; min-width: 0; }
    .kx-vrf-item .role { font-size: .6rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--rt, var(--kx-p1)); }
    .kx-vrf-item .nm { font-weight: 800; font-size: .82rem; color: var(--kx-ink); line-height: 1.25; overflow-wrap: anywhere; }
    .kx-vrf-item .nip { font-size: .64rem; color: #94a3b8; font-variant-numeric: tabular-nums; margin-bottom: .35rem; }
    .kx-vrf-item .note { font-size: .62rem; color: #94a3b8; font-weight: 600; margin-bottom: .35rem; }
    .kx-docs { display: flex; flex-wrap: wrap; gap: .3rem; }
    .kx-doc-chip {
        display: inline-flex; align-items: center; gap: .28rem;
        font-size: .6rem; font-weight: 800; letter-spacing: .03em;
        padding: .2rem .5rem; border-radius: 8px; white-space: nowrap;
        color: var(--dc, var(--kx-p1)); background: color-mix(in srgb, var(--dc, var(--kx-p1)) 12%, #fff);
        border: 1px solid color-mix(in srgb, var(--dc, var(--kx-p1)) 25%, #fff);
    }
    .kx-doc-chip .bx { width: 6px; height: 6px; border-radius: 2px; background: var(--dc, var(--kx-p1)); }

    /* ═══ Quick actions ═══ */
    .kx-qa { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
    .kx-qa a, .kx-qa button {
        display: flex; align-items: center; justify-content: center; gap: .45rem;
        border: 1px solid #eef1f6; border-radius: 14px;
        background: #fff; padding: .7rem .5rem;
        font-size: .74rem; font-weight: 800; color: var(--kx-ink);
        text-decoration: none; width: 100%;
        transition: transform .18s ease, border-color .18s ease, color .18s ease, background .18s ease, box-shadow .18s ease;
    }
    .kx-qa a:hover, .kx-qa button:hover { transform: translateY(-2px); border-color: #ddd6fe; color: var(--kx-p1); background: #faf9ff; box-shadow: 0 10px 20px rgba(91, 77, 255, .1); }
    .kx-qa .danger:hover { border-color: #fecdd3; color: var(--kx-danger); background: #fff5f6; }

    /* ═══ Timeline aktivitas ═══ */
    .kx-log { position: relative; padding-left: 1.5rem; }
    .kx-log::before { content: ''; position: absolute; left: 7px; top: 8px; bottom: 8px; width: 2px; border-radius: 2px; background: #e8ecf3; }
    .kx-log-item { position: relative; padding-bottom: 1rem; }
    .kx-log-item:last-child { padding-bottom: 0; }
    .kx-log-item::before {
        content: ''; position: absolute; left: -1.5rem; top: .3rem;
        width: 16px; height: 16px; border-radius: 50%;
        background: #fff; border: 4px solid var(--lc, var(--kx-p1));
    }
    .kx-log-item:first-child::before { box-shadow: 0 0 0 5px color-mix(in srgb, var(--lc, var(--kx-p1)) 15%, transparent); }

    /* ═══ Sidebar sticky ═══ */
    @media (min-width: 992px) {
        .kx-sticky { position: sticky; top: 88px; }
    }

    .kx-focus a:focus-visible, .kx-focus button:focus-visible {
        outline: 3px solid rgba(91, 77, 255, .5); outline-offset: 2px; border-radius: 14px;
    }

    @media (prefers-reduced-motion: reduce) {
        .kx-up { animation: none; opacity: 1; }
        .kx-blob, .kx-hero-icon, .kx-btn-w::after, .kx-kpi.hero-kpi::after,
        .kx-status.live .dot, .kx-step.now .bulb, .kx-empty .art { animation: none; }
        .kx-kpi svg.spark path { animation: none; stroke-dashoffset: 0; }
        .kx-brk .fillb, .kx-step.done:not(:last-child)::after { animation: none; }
        .kx-kpi, .kx-card, .kx-doc, .kx-qa a, .kx-qa button, .kx-appr-item .ava { transition: none; }
    }
</style>
@endpush

@section('content')
@php
    $statusMeta = match (true) {
        $tagihan->status === 'DRAFT' => ['label' => 'Draft', 'live' => false],
        str_starts_with((string) $tagihan->status, 'REVISI_') => ['label' => 'Perlu Revisi', 'live' => true],
        $tagihan->status === 'READY_FOR_SPP' => ['label' => 'Siap Diproses', 'live' => true],
        $tagihan->status === 'PROSES_SPP' => ['label' => 'Proses Pencairan', 'live' => true],
        $tagihan->status === 'SELESAI' => ['label' => 'Selesai · Dibayar', 'live' => false],
        default => ['label' => str_replace('_', ' ', $tagihan->status), 'live' => false],
    };

    $vendor = $tagihan->pihak;
    $rekening = $vendor?->rekening?->firstWhere('is_default', true) ?? $vendor?->rekening?->first();
    $inisial = fn ($nama) => strtoupper(collect(explode(' ', (string) ($nama ?? '?')))
        ->filter()->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode(''));

    $bruto = (float) $tagihan->total_bruto;
    $potongan = (float) $tagihan->total_potongan;
    $netto = (float) $tagihan->total_netto;
    $persenPot = $bruto > 0 ? round($potongan / $bruto * 100, 1) : 0;
    $persenNet = $bruto > 0 ? round(100 - $persenPot, 1) : 100;

    $potonganRows = $tagihan->potonganTagihan->where('jenis_potongan', 'PAJAK')->values();

    // Stepper proses berdasar status tagihan.
    $stepIndex = match (true) {
        $tagihan->status === 'DRAFT', str_starts_with((string) $tagihan->status, 'REVISI_') => 0,
        $tagihan->status === 'READY_FOR_SPP' => 1,
        $tagihan->status === 'PROSES_SPP' => 3,
        $tagihan->status === 'SELESAI' => 5,
        default => 1,
    };
    $steps = [
        ['t' => 'Draft Dibuat', 's' => 'Surat Pesanan diunggah', 'i' => 'bi-file-earmark-plus'],
        ['t' => 'Diajukan', 's' => 'Siap diproses', 'i' => 'bi-send'],
        ['t' => 'COA · Pajak · KPA', 's' => 'Prasyarat rantai', 'i' => 'bi-clipboard-check'],
        ['t' => 'SPP · SPM · NPI', 's' => 'Verifikasi dokumen', 'i' => 'bi-diagram-3'],
        ['t' => 'SP2D Terbit', 's' => 'Pembayaran vendor', 'i' => 'bi-cash-stack'],
        ['t' => 'Selesai', 's' => 'Tercatat di BKU', 'i' => 'bi-check2-circle'],
    ];

    $fileMeta = [
        'SURAT_PESANAN' => ['label' => 'Surat Pesanan ber-TTE', 'field' => 'file_surat_pesanan'],
        'FAKTUR_PAJAK' => ['label' => 'Faktur Pajak', 'field' => 'file_faktur_pajak'],
        'INVOICE' => ['label' => 'Invoice', 'field' => 'file_invoice'],
        'KWITANSI' => ['label' => 'Kwitansi', 'field' => 'file_kwitansi'],
        'BAST' => ['label' => 'BAST / Serah Terima', 'field' => 'file_bast'],
    ];
    $arsipAktif = ($detail?->arsipDokumen ?? collect())->where('is_active', true);
    $ukuran = function ($bytes) {
        $bytes = (int) $bytes;
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 0) . ' KB';
        return $bytes > 0 ? $bytes . ' B' : '—';
    };

    // Warna khas per jenis dokumen pencairan.
    $docTone = ['SPP' => '#5b4dff', 'SPM' => '#0891b2', 'NPI' => '#845ef7', 'SP2D' => '#00a86b'];

    // Dokumen yang ditangani tiap role (mengikuti definisi workflow kode KONTRAK).
    $signers = [
        ['role' => 'PPK', 'tone' => '#5b4dff', 'nama' => $tagihan->ppk_nama_snapshot, 'nip' => $tagihan->ppk_nip_snapshot,
            'note' => 'Verifikator & penerbit SP2D', 'docs' => ['SPP', 'NPI', 'SP2D']],
        ['role' => 'PPSPM', 'tone' => '#0891b2', 'nama' => $tagihan->ppspm_nama_snapshot, 'nip' => $tagihan->ppspm_nip_snapshot,
            'note' => 'Verifikator SPM', 'docs' => ['SPM']],
        ['role' => 'Koordinator Keuangan', 'tone' => '#845ef7', 'nama' => $tagihan->koordinator_keuangan_nama_snapshot, 'nip' => $tagihan->koordinator_keuangan_nip_snapshot,
            'note' => 'Verifikator', 'docs' => ['SPP', 'SPM', 'NPI']],
        ['role' => 'Bendahara Pengeluaran', 'tone' => '#00a86b', 'nama' => $tagihan->bendahara_pengeluaran_nama_snapshot, 'nip' => $tagihan->bendahara_pengeluaran_nip_snapshot,
            'note' => 'TTD & pembayaran SP2D', 'docs' => ['SP2D']],
        ['role' => 'Bendahara Penerimaan', 'tone' => '#db2777', 'nama' => $tagihan->bendahara_penerimaan_nama_snapshot, 'nip' => $tagihan->bendahara_penerimaan_nip_snapshot,
            'note' => 'Verifikator NPI', 'docs' => ['NPI']],
        ['role' => 'Kasubbag Keu & TU', 'tone' => '#d97706', 'nama' => $tagihan->kasubbag_nama_snapshot, 'nip' => $tagihan->kasubbag_nip_snapshot,
            'note' => 'Verifikator', 'docs' => ['SPP', 'SPM', 'NPI']],
    ];

    $logTone = fn ($aksi) => match (true) {
        str_contains($aksi, 'DIAJUKAN') => '#5b4dff',
        str_contains($aksi, 'DISETUJUI') || str_contains($aksi, 'GENERATE') => '#00a86b',
        str_contains($aksi, 'REVISI') || str_contains($aksi, 'DITOLAK') => '#ffb020',
        default => '#94a3b8',
    };

    // Data kontrak & termin milik master Kontrak Eksternal.
    $terminM = $detail?->kontrakEksternalTermin;
    $kontrakM = $terminM?->kontrak;
    $metodeM = $kontrakM->metode_pembayaran ?? 'LUMPSUM';
@endphp

<div class="kx-page kx-focus">

    {{-- Breadcrumb --}}
    <div class="kx-crumb mb-3 kx-up" style="--d:.01s;">
        <a href="{{ route('tagihan-kontrak-eksternal.index') }}"><i class="bi bi-receipt"></i> Tagihan Kontrak</a>
        <span class="sep">/</span>
        <span class="now">{{ $tagihan->nomor_tagihan }}</span>
    </div>

    {{-- ═══════ HERO ═══════ --}}
    <div class="kx-hero mb-4 kx-up" style="--d:.03s;">
        <span class="kx-blob b1"></span><span class="kx-blob b2"></span><span class="kx-blob b3"></span>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-start gap-3">
                <span class="kx-hero-icon"><i class="bi bi-file-earmark-check"></i></span>
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="kx-hero-title">{{ $tagihan->nomor_tagihan }}</span>
                        <span class="kx-status {{ $statusMeta['live'] ? 'live' : '' }}"><span class="dot"></span> {{ $statusMeta['label'] }}</span>
                    </div>
                    <div class="kx-hero-sub mb-2">{{ $detail?->nama_pekerjaan ?? $tagihan->deskripsi }}</div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="kx-chip"><i class="bi bi-tag-fill"></i> Kontrak</span>
                        @if($detail?->sumber)<span class="kx-chip"><i class="bi bi-cart-check"></i> {{ $detail->sumber }}</span>@endif
                        @if($detail?->nomor_surat_pesanan)
                            <span class="kx-chip copyable" data-copy="{{ $detail->nomor_surat_pesanan }}" title="Klik untuk menyalin nomor Surat Pesanan" role="button" tabindex="0">
                                <i class="bi bi-hash"></i> <span class="txt">{{ $detail->nomor_surat_pesanan }}</span> <i class="bi bi-copy" style="opacity:.6; font-size:.66rem;"></i>
                            </span>
                        @endif
                        @if($metodeM === 'TERMIN')
                            <span class="kx-chip"><i class="bi bi-layers-half"></i> Termin {{ $detail->termin_ke }}{{ $detail->total_termin ? ' / ' . $detail->total_termin : '' }}{{ $terminM?->jenis_termin ? ' · ' . ucfirst(strtolower($terminM->jenis_termin)) : '' }}</span>
                        @else
                            <span class="kx-chip"><i class="bi bi-cash"></i> Lumpsum</span>
                        @endif
                        @if($kontrakM)
                            <a href="{{ route('kontrak-eksternal.show', $kontrakM->id) }}" class="kx-chip text-decoration-none" style="color:inherit;"><i class="bi bi-box-arrow-up-right"></i> Lihat Kontrak</a>
                        @endif
                        @if($detail?->tanggal_surat_pesanan)<span class="kx-chip"><i class="bi bi-calendar-event"></i> {{ $detail->tanggal_surat_pesanan->translatedFormat('d F Y') }}</span>@endif
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('tagihan-kontrak-eksternal.index') }}" class="btn kx-btn-g"><i class="bi bi-arrow-left"></i> Kembali</a>
                @if($isEditable)
                    <a href="{{ route('tagihan-kontrak-eksternal.edit', $tagihan->id) }}" class="btn kx-btn-g"><i class="bi bi-pencil-square"></i> Edit</a>
                    <form method="POST" action="{{ route('tagihan-kontrak-eksternal.submit', $tagihan->id) }}"
                          onsubmit="return confirm('Ajukan tagihan ini? Setelah diajukan tagihan langsung siap diproses dan tidak dapat diubah lagi.');">
                        @csrf
                        <button type="submit" class="btn kx-btn-w"><i class="bi bi-send me-1"></i> Ajukan Tagihan</button>
                    </form>
                @else
                    <a href="{{ route('proses-tagihan.show', $tagihan->id) }}" class="btn kx-btn-w"><i class="bi bi-diagram-3 me-1"></i> Proses Tagihan</a>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-4 kx-up" style="--d:.05s;" data-sky-ignore>
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger rounded-4 kx-up" style="--d:.05s;" data-sky-ignore>
            <ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- ═══════ KPI ═══════ --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4 kx-up" style="--d:.07s;">
            <div class="kx-kpi" style="--tone:#5b4dff; --tone-2:#845ef7; --tone-soft:rgba(91,77,255,.1);">
                <div class="d-flex align-items-start justify-content-between position-relative" style="z-index:1;">
                    <div>
                        <div class="lbl">Nilai Bruto</div>
                        <div class="val">Rp <span data-kx-count="{{ (int) $bruto }}">0</span></div>
                        <div class="sub">termasuk PPN · dasar tagihan</div>
                    </div>
                    <span class="ic"><i class="bi bi-cash-stack"></i></span>
                </div>
                <svg class="spark" viewBox="0 0 200 42" preserveAspectRatio="none" aria-hidden="true"><path d="M0,34 C25,30 35,18 55,20 S90,32 110,24 150,6 200,10"/></svg>
            </div>
        </div>
        <div class="col-md-4 kx-up" style="--d:.12s;">
            <div class="kx-kpi" style="--tone:#ff4d6d; --tone-2:#fb7185; --tone-soft:rgba(255,77,109,.1);">
                <div class="d-flex align-items-start justify-content-between position-relative" style="z-index:1;">
                    <div>
                        <div class="lbl">Total Potongan</div>
                        <div class="val">{{ $potongan > 0 ? '− ' : '' }}Rp <span data-kx-count="{{ (int) $potongan }}">0</span></div>
                        <div class="sub">{{ $persenPot }}% dari bruto{{ $potongan <= 0 ? ' · pajak diisi di Proses Tagihan' : '' }}</div>
                    </div>
                    <span class="ic"><i class="bi bi-scissors"></i></span>
                </div>
                <svg class="spark" viewBox="0 0 200 42" preserveAspectRatio="none" aria-hidden="true"><path d="M0,20 C30,24 50,12 75,16 S120,30 145,26 175,14 200,18"/></svg>
            </div>
        </div>
        <div class="col-md-4 kx-up" style="--d:.17s;">
            <div class="kx-kpi hero-kpi">
                <div class="d-flex align-items-start justify-content-between position-relative" style="z-index:1;">
                    <div>
                        <div class="lbl">Netto Dibayarkan</div>
                        <div class="val">Rp <span data-kx-count="{{ (int) $netto }}">0</span></div>
                        <div class="sub">{{ $persenNet }}% dari bruto · ke rekening vendor</div>
                    </div>
                    <span class="ic" style="background:rgba(255,255,255,.2); box-shadow:none;"><i class="bi bi-wallet2"></i></span>
                </div>
                <svg class="spark" viewBox="0 0 200 42" preserveAspectRatio="none" aria-hidden="true"><path d="M0,36 C30,32 55,26 80,22 S140,14 200,6"/></svg>
            </div>
        </div>
    </div>

    {{-- ═══════ STEPPER ═══════ --}}
    <div class="kx-stepper mb-4 kx-up" style="--d:.21s;" role="list" aria-label="Tahapan proses tagihan">
        @foreach($steps as $i => $st)
            <div class="kx-step {{ $i < $stepIndex ? 'done' : ($i === $stepIndex ? 'now' : 'pending') }}" role="listitem">
                <div class="bulb">
                    @if($i < $stepIndex)<i class="bi bi-check-lg"></i>@else<i class="bi {{ $st['i'] }}"></i>@endif
                </div>
                <div class="t">{{ $st['t'] }}</div>
                <div class="s">{{ $i === $stepIndex ? 'Tahap saat ini' : $st['s'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- ═══════ BENTO GRID ═══════ --}}
    <div class="row g-4">
        {{-- ───── KIRI 70% ───── --}}
        <div class="col-lg-8">

            {{-- Informasi pengadaan --}}
            <div class="kx-card mb-4 kx-up" style="--d:.25s; --tone:#5b4dff; --tone-2:#845ef7;">
                <div class="kx-card-head">
                    <span class="ic"><i class="bi bi-file-earmark-text"></i></span>
                    <div>
                        <h6 class="kx-card-title">Informasi Pengadaan</h6>
                        <div class="kx-card-sub">Identitas kontrak dari Surat Pesanan e-purchasing.</div>
                    </div>
                </div>
                <div class="kx-card-body">
                    <div class="row g-1">
                        <div class="col-md-6"><div class="kx-info"><span class="mi"><i class="bi bi-hash"></i></span><div><div class="k">Nomor Surat Pesanan</div><div class="v font-monospace">{{ $detail?->nomor_surat_pesanan ?? '-' }}</div></div></div></div>
                        <div class="col-md-3 col-6"><div class="kx-info"><span class="mi"><i class="bi bi-calendar-event"></i></span><div><div class="k">Tanggal</div><div class="v">{{ optional($detail?->tanggal_surat_pesanan)->translatedFormat('d M Y') ?? '-' }}</div></div></div></div>
                        <div class="col-md-3 col-6"><div class="kx-info"><span class="mi"><i class="bi bi-collection"></i></span><div><div class="k">Termin</div><div class="v">{{ $detail?->termin_ke ?? '-' }} dari {{ $detail?->total_termin ?? '-' }}{{ $terminM?->jenis_termin ? ' · ' . ucfirst(strtolower($terminM->jenis_termin)) : '' }}</div></div></div></div>
                        <div class="col-md-6"><div class="kx-info"><span class="mi"><i class="bi bi-cash-stack"></i></span><div><div class="k">Metode Pembayaran</div><div class="v">{{ $metodeM === 'TERMIN' ? 'Termin (Bertahap)' : 'Lumpsum (Sekaligus)' }}@if((float) ($kontrakM?->nilai_total_kontrak ?? 0) > 0) · Total Rp {{ number_format((float) $kontrakM->nilai_total_kontrak, 0, ',', '.') }}@endif</div></div></div></div>
                        <div class="col-md-6"><div class="kx-info"><span class="mi"><i class="bi bi-cart-check"></i></span><div><div class="k">Metode Pengadaan</div><div class="v">e-Purchasing · {{ $detail?->sumber ?? 'Katalog Elektronik' }}</div></div></div></div>
                        @if((float) ($kontrakM?->nilai_uang_muka ?? 0) > 0)
                            <div class="col-md-6"><div class="kx-info"><span class="mi"><i class="bi bi-cash-coin"></i></span><div><div class="k">Uang Muka Kontrak</div><div class="v">Rp {{ number_format((float) $kontrakM->nilai_uang_muka, 0, ',', '.') }} · dipotong bertahap</div></div></div></div>
                        @endif
                        <div class="col-12"><div class="kx-info"><span class="mi"><i class="bi bi-briefcase"></i></span><div><div class="k">Nama Pekerjaan</div><div class="v">{{ $detail?->nama_pekerjaan ?? '-' }}</div></div></div></div>
                        <div class="col-12"><div class="kx-info"><span class="mi"><i class="bi bi-card-text"></i></span><div><div class="k">Deskripsi</div><div class="v fw-normal text-secondary">{{ $tagihan->deskripsi }}</div></div></div></div>
                    </div>
                </div>
            </div>

            {{-- Rincian finansial --}}
            <div class="kx-card mb-4 kx-up" style="--d:.3s; --tone:#00a86b; --tone-2:#34d399;">
                <div class="kx-card-head">
                    <span class="ic"><i class="bi bi-bar-chart"></i></span>
                    <div>
                        <h6 class="kx-card-title">Rincian Finansial</h6>
                        <div class="kx-card-sub">Komposisi bruto → potongan → netto.</div>
                    </div>
                </div>
                <div class="kx-card-body">
                    @if($metodeM === 'TERMIN')
                        <div class="kx-brk">
                            <div class="nm text-secondary">Nilai Total Kontrak</div>
                            <div class="track"></div>
                            <div class="amt text-secondary">Rp {{ number_format((float) ($kontrakM?->nilai_total_kontrak ?? $bruto), 0, ',', '.') }}</div>
                        </div>
                    @endif
                    <div class="kx-brk">
                        <div class="nm">Bruto Termin{{ $metodeM === 'TERMIN' ? ' (' . rtrim(rtrim(number_format((float) ($terminM?->persentase ?? 100), 4, '.', ''), '0'), '.') . '%)' : '' }}</div>
                        <div class="track"><div class="fillb" style="--w:100%; --bc:#5b4dff; --bc2:#845ef7;"></div></div>
                        <div class="amt">Rp {{ number_format($bruto, 0, ',', '.') }}</div>
                    </div>
                    @php $iBar = 0; @endphp
                    @if((float) ($terminM?->potongan_angsuran_uang_muka ?? 0) > 0)
                        @php $um = (float) $terminM->potongan_angsuran_uang_muka; $pw = $bruto > 0 ? max(round($um / $bruto * 100, 1), 2) : 0; $iBar++; @endphp
                        <div class="kx-brk">
                            <div class="nm">Angsuran Uang Muka</div>
                            <div class="track"><div class="fillb" style="--w:{{ $pw }}%; --bc:#d97706; --bc2:#fbbf24; --bd:.15s;"></div></div>
                            <div class="amt" style="color:#b45309;">− Rp {{ number_format($um, 0, ',', '.') }}</div>
                        </div>
                    @endif
                    @forelse($potonganRows as $pot)
                        @php $pw = $bruto > 0 ? max(round((float) $pot->nominal_potongan / $bruto * 100, 1), 2) : 0; $iBar++; @endphp
                        <div class="kx-brk">
                            <div class="nm">{{ $pot->nama_pajak_snapshot ?? $pot->deskripsi }}</div>
                            <div class="track"><div class="fillb" style="--w:{{ $pw }}%; --bc:#ff4d6d; --bc2:#fb7185; --bd:{{ .15 + $iBar * .1 }}s;"></div></div>
                            <div class="amt text-danger">− Rp {{ number_format((float) $pot->nominal_potongan, 0, ',', '.') }}</div>
                        </div>
                    @empty
                        <div class="kx-brk">
                            <div class="nm text-secondary">Potongan Pajak</div>
                            <div class="track"></div>
                            <div class="amt text-secondary fw-normal fs-8">Diisi Operator di Proses Tagihan</div>
                        </div>
                    @endforelse
                    <div class="kx-brk total">
                        <div class="nm">Netto Dibayarkan</div>
                        <div class="track"><div class="fillb" style="--w:{{ max($persenNet, 2) }}%; --bc:#047857; --bc2:#34d399; --bd:.3s;"></div></div>
                        <div class="amt">Rp {{ number_format($netto, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            {{-- Dokumen --}}
            <div class="kx-card mb-4 kx-up" style="--d:.35s; --tone:#0891b2; --tone-2:#38bdf8;">
                <div class="kx-card-head">
                    <span class="ic"><i class="bi bi-folder2-open"></i></span>
                    <div>
                        <h6 class="kx-card-title">Dokumen</h6>
                        <div class="kx-card-sub">Arsip PDF tagihan — klik kartu untuk membuka.</div>
                    </div>
                    <span class="badge bg-light text-secondary border rounded-pill ms-auto">{{ $arsipAktif->count() }}</span>
                </div>
                <div class="kx-card-body">
                    @if($arsipAktif->isEmpty())
                        <div class="kx-empty">
                            <div class="art"><i class="bi bi-cloud-arrow-up"></i></div>
                            <div class="fw-bold mb-1">Belum ada dokumen</div>
                            <div class="text-secondary fs-8 mb-3">Unggah PDF Surat Pesanan melalui form edit tagihan.</div>
                            @if($isEditable)
                                <a href="{{ route('tagihan-kontrak-eksternal.edit', $tagihan->id) }}" class="btn btn-primary btn-sm rounded-3"><i class="bi bi-cloud-arrow-up"></i> Unggah Dokumen</a>
                            @endif
                        </div>
                    @else
                        <div class="row g-3">
                            @foreach($arsipAktif as $arsip)
                                <div class="col-md-4 col-sm-6">
                                    @php $meta = $fileMeta[$arsip->jenis_dokumen] ?? null; @endphp
                                    @if($meta)
                                        <a class="kx-doc" target="_blank"
                                           href="{{ route('secure-file', ['kind' => 'tagihan-kontrak-eksternal', 'id' => $detail->id, 'field' => $meta['field']]) }}">
                                            <div class="thumb"><i class="bi bi-file-earmark-pdf"></i><span class="ext">PDF</span></div>
                                            <div class="meta">
                                                <div class="t">{{ $meta['label'] }}</div>
                                                <div class="s">{{ $arsip->nama_file_asli }}</div>
                                            </div>
                                            <div class="foot">
                                                <span>{{ $ukuran($arsip->ukuran_file) }} · {{ optional($arsip->uploaded_at ?? $arsip->created_at)->format('d/m/Y') }}</span>
                                                <span class="open">Buka <i class="bi bi-arrow-right"></i></span>
                                            </div>
                                        </a>
                                    @else
                                        <div class="kx-doc">
                                            <div class="thumb"><i class="bi bi-file-earmark"></i></div>
                                            <div class="meta">
                                                <div class="t">{{ ucwords(strtolower(str_replace('_', ' ', $arsip->jenis_dokumen))) }}</div>
                                                <div class="s">{{ $arsip->nama_file_asli }}</div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if(! ($detail?->file_surat_pesanan))
                        <div class="mt-3 p-3 rounded-4 fw-semibold" style="background:#fff8e6; border:1px solid #fde68a; color:#92400e;" data-sky-ignore>
                            <i class="bi bi-exclamation-triangle"></i> PDF Surat Pesanan bertanda tangan wajib diunggah sebelum tagihan dapat diajukan.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Aktivitas --}}
            <div class="kx-card mb-4 kx-up" style="--d:.4s; --tone:#64748b; --tone-2:#94a3b8;">
                <div class="kx-card-head">
                    <span class="ic"><i class="bi bi-activity"></i></span>
                    <div>
                        <h6 class="kx-card-title">Aktivitas Terakhir</h6>
                        <div class="kx-card-sub">Jejak audit perubahan tagihan.</div>
                    </div>
                    <span class="badge bg-light text-secondary border rounded-pill ms-auto">{{ $tagihan->logs->count() }}</span>
                </div>
                <div class="kx-card-body">
                    @if($tagihan->logs->isEmpty())
                        <div class="kx-empty">
                            <div class="art"><i class="bi bi-journal-x"></i></div>
                            <div class="fw-bold mb-1">Belum ada aktivitas</div>
                            <div class="text-secondary fs-8">Aktivitas akan tercatat otomatis di sini.</div>
                        </div>
                    @else
                        <div class="kx-log">
                            @foreach($tagihan->logs->sortByDesc('created_at')->take(8) as $log)
                                <div class="kx-log-item" style="--lc: {{ $logTone((string) $log->aksi) }};">
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <span class="fw-bold fs-7 text-dark">{{ ucwords(strtolower(str_replace('_', ' ', $log->aksi))) }}</span>
                                        <span class="text-secondary fs-8">{{ optional($log->created_at)->diffForHumans() }}</span>
                                    </div>
                                    <div class="text-secondary fs-8"><i class="bi bi-person-circle"></i> {{ $log->user?->name ?? 'Sistem' }}</div>
                                    @if($log->catatan)
                                        <div class="rounded-3 p-2 fs-8 text-secondary fst-italic mt-1" style="background:#f8fafc;">"{{ \Illuminate\Support\Str::limit($log->catatan, 140) }}"</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ───── KANAN 30% (sticky) ───── --}}
        <div class="col-lg-4">
            <div class="kx-sticky">

                {{-- Vendor --}}
                <div class="kx-card mb-4 kx-up" style="--d:.28s; --tone:#00a86b; --tone-2:#34d399;">
                    <div class="kx-card-head">
                        <span class="ic"><i class="bi bi-shop"></i></span>
                        <h6 class="kx-card-title">Penyedia</h6>
                    </div>
                    <div class="kx-card-body">
                        @if($vendor)
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="kx-vendor-logo">{{ $inisial($vendor->nama_pihak) }}</span>
                                <div class="min-w-0">
                                    <div class="fw-bold" style="overflow-wrap:anywhere;">{{ $vendor->nama_pihak }}</div>
                                    <div class="text-secondary fs-8">{{ $vendor->nama_penanggung_jawab ?? '' }}</div>
                                </div>
                            </div>
                            <div class="kx-info px-0"><span class="mi"><i class="bi bi-credit-card-2-front"></i></span><div><div class="k">NPWP</div><div class="v font-monospace fw-normal">{{ $vendor->npwp ?? '-' }}</div></div></div>
                            @if($vendor->no_telepon)
                                <div class="kx-info px-0"><span class="mi"><i class="bi bi-telephone"></i></span><div><div class="k">Telepon</div><div class="v fw-normal">{{ $vendor->no_telepon }}</div></div></div>
                            @endif
                            <div class="kx-info px-0 mb-2"><span class="mi"><i class="bi bi-geo-alt"></i></span><div><div class="k">Alamat</div><div class="v fw-normal text-secondary fs-8">{{ $vendor->alamat ?? '-' }}</div></div></div>
                            @if($rekening)
                                <div class="kx-rek">
                                    <div class="fw-bold fs-7" style="color:#047857;"><i class="bi bi-bank"></i> {{ $rekening->nama_bank }}</div>
                                    <div class="font-monospace fw-bold fs-6" style="overflow-wrap:anywhere;">{{ $rekening->nomor_rekening }}</div>
                                    <div class="fs-8 text-secondary">a.n. {{ $rekening->nama_rekening }}</div>
                                </div>
                            @else
                                <div class="p-2 rounded-3 fs-8 fw-semibold" style="background:#fff8e6; border:1px solid #fde68a; color:#92400e;" data-sky-ignore>
                                    <i class="bi bi-exclamation-triangle"></i> Rekening vendor belum terdaftar.
                                </div>
                            @endif
                        @else
                            <div class="kx-empty">
                                <div class="art"><i class="bi bi-shop"></i></div>
                                <div class="fw-bold mb-1">Vendor belum terhubung</div>
                                <div class="text-secondary fs-8 mb-3">Pilih vendor terdaftar atau daftarkan penyedia baru dari Surat Pesanan.</div>
                                @if($isEditable)
                                    <a href="{{ route('tagihan-kontrak-eksternal.edit', $tagihan->id) }}" class="btn btn-primary btn-sm rounded-3">
                                        <i class="bi bi-link-45deg"></i> Hubungkan Vendor
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Verifikator per role & dokumen yang ditangani --}}
                <div class="kx-card mb-4 kx-up" style="--d:.33s; --tone:#d97706; --tone-2:#fbbf24;">
                    <div class="kx-card-head">
                        <span class="ic"><i class="bi bi-person-badge"></i></span>
                        <div>
                            <h6 class="kx-card-title">Verifikator &amp; Penanda Tangan</h6>
                            <div class="kx-card-sub">Dokumen pencairan yang ditangani tiap pejabat.</div>
                        </div>
                    </div>
                    <div class="kx-card-body">
                        <div class="kx-vrf">
                            @foreach($signers as $s)
                                <div class="kx-vrf-item" style="--rt: {{ $s['tone'] }};">
                                    <span class="ava">{{ $inisial($s['nama']) }}</span>
                                    <div class="info">
                                        <div class="role">{{ $s['role'] }}</div>
                                        <div class="nm">{{ $s['nama'] ?? '-' }}</div>
                                        <div class="nip">{{ $s['nip'] ? 'NIP ' . $s['nip'] : '' }}</div>
                                        <div class="note"><i class="bi bi-check2-circle"></i> {{ $s['note'] }}</div>
                                        <div class="kx-docs">
                                            @foreach($s['docs'] as $doc)
                                                <span class="kx-doc-chip" style="--dc: {{ $docTone[$doc] ?? '#5b4dff' }};"><span class="bx"></span> {{ $doc }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Aksi cepat --}}
                <div class="kx-card kx-up" style="--d:.38s; --tone:#5b4dff; --tone-2:#845ef7;">
                    <div class="kx-card-head">
                        <span class="ic"><i class="bi bi-lightning-charge"></i></span>
                        <h6 class="kx-card-title">Aksi Cepat</h6>
                    </div>
                    <div class="kx-card-body">
                        <div class="kx-qa">
                            @if($isEditable)
                                <a href="{{ route('tagihan-kontrak-eksternal.edit', $tagihan->id) }}"><i class="bi bi-pencil-square"></i> Edit Tagihan</a>
                            @else
                                <a href="{{ route('proses-tagihan.show', $tagihan->id) }}"><i class="bi bi-diagram-3"></i> Proses Tagihan</a>
                            @endif
                            @if($detail?->file_surat_pesanan)
                                <a href="{{ route('secure-file', ['kind' => 'tagihan-kontrak-eksternal', 'id' => $detail->id, 'field' => 'file_surat_pesanan']) }}" target="_blank"><i class="bi bi-file-earmark-pdf"></i> Buka SP</a>
                            @endif
                            <button type="button" class="copyable" data-copy="{{ $detail?->nomor_surat_pesanan ?? $tagihan->nomor_tagihan }}"><i class="bi bi-copy"></i> Salin No. SP</button>
                            <button type="button" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
                            <a href="{{ route('tagihan-kontrak-eksternal.index') }}"><i class="bi bi-list-ul"></i> Semua Tagihan</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    (function () {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Count-up nilai rupiah.
        document.querySelectorAll('[data-kx-count]').forEach(function (el) {
            const target = parseInt(el.getAttribute('data-kx-count'), 10) || 0;
            const format = (n) => n.toLocaleString('id-ID');
            if (reduceMotion || target === 0) { el.textContent = format(target); return; }
            const duration = 1000;
            const start = performance.now();
            const tick = (now) => {
                const p = Math.min((now - start) / duration, 1);
                el.textContent = format(Math.round(target * (1 - Math.pow(1 - p, 3))));
                if (p < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        });

        // Chip/tombol salin (nomor SP).
        document.querySelectorAll('.copyable[data-copy]').forEach(function (btn) {
            const salin = function () {
                const text = btn.getAttribute('data-copy') || '';
                const icon = btn.querySelector('.bi-copy, .bi-check-lg');
                const done = function () {
                    if (icon) { icon.classList.remove('bi-copy'); icon.classList.add('bi-check-lg'); }
                    setTimeout(function () {
                        if (icon) { icon.classList.remove('bi-check-lg'); icon.classList.add('bi-copy'); }
                    }, 1400);
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(done).catch(function () {});
                } else {
                    const ta = document.createElement('textarea');
                    ta.value = text; document.body.appendChild(ta); ta.select();
                    try { document.execCommand('copy'); done(); } catch (e) {}
                    document.body.removeChild(ta);
                }
            };
            btn.addEventListener('click', salin);
            btn.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); salin(); } });
        });
    })();
</script>
@endpush
