@extends('layouts.app')

@section('title', 'Proses Tagihan - ' . $tagihan->nomor_tagihan)

@push('css')
<style>
    /* ==========================================================
       PROSES TAGIHAN — "PIPELINE PENCAIRAN"
       Design system: aurora hero, pipeline stepper, glass cards,
       reveal-on-scroll, micro-interactions.
       ========================================================== */
    :root {
        --pt-primary: #4f46e5;
        --pt-primary-2: #7c3aed;
        --pt-primary-hover: #4338ca;
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

        /* tone palettes untuk kartu dokumen */
        --tone-indigo: #4f46e5;   --tone-indigo-soft: rgba(79,70,229,.10);
        --tone-violet: #8b5cf6;   --tone-violet-soft: rgba(139,92,246,.10);
        --tone-emerald: #10b981;  --tone-emerald-soft: rgba(16,185,129,.10);
        --tone-info: #06b6d4;     --tone-info-soft: rgba(6,182,212,.10);
        --tone-amber: #f59e0b;    --tone-amber-soft: rgba(245,158,11,.12);
        --tone-slate: #64748b;    --tone-slate-soft: rgba(100,116,139,.12);
    }

    body { background-color: var(--pt-bg); }
    .fs-7 { font-size: .8rem !important; }
    .fs-8 { font-size: .7rem !important; }
    .z-index-1 { z-index: 1; }
    .letter-spacing-1 { letter-spacing: 1px; }
    html { scroll-behavior: smooth; }
    [id^="sec-"] { scroll-margin-top: 96px; }

    /* ---------- Keyframes ---------- */
    @keyframes ptFadeUp   { from { opacity: 0; transform: translateY(26px); } to { opacity: 1; transform: none; } }
    @keyframes ptPop      { 0% { transform: scale(.6); opacity: 0; } 70% { transform: scale(1.08); } 100% { transform: scale(1); opacity: 1; } }
    @keyframes ptFloat    { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-10px) } }
    @keyframes ptAurora   { 0% { background-position: 0% 50% } 50% { background-position: 100% 50% } 100% { background-position: 0% 50% } }
    @keyframes ptShimmer  { 0% { background-position: -200% 0 } 100% { background-position: 200% 0 } }
    @keyframes ptPulse    { 0%,100% { box-shadow: 0 0 0 0 rgba(245,158,11,.45) } 50% { box-shadow: 0 0 0 9px rgba(245,158,11,0) } }
    @keyframes ptPulseBlue{ 0%,100% { box-shadow: 0 0 0 0 rgba(79,70,229,.45) } 50% { box-shadow: 0 0 0 10px rgba(79,70,229,0) } }
    @keyframes ptSpin     { to { transform: rotate(360deg) } }
    @keyframes ptBounce   { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-4px) } }
    @keyframes ptDash     { to { stroke-dashoffset: var(--ring-offset, 0) } }
    @keyframes ptConfetti { to { transform: translateY(110vh) rotate(720deg); opacity: .9; } }
    @keyframes ptGlowSweep{ 0% { transform: rotate(0deg) } 100% { transform: rotate(360deg) } }

    /* ---------- Reveal on scroll (aktif hanya bila JS jalan) ---------- */
    .pt-anim .reveal { opacity: 0; transform: translateY(26px); transition: opacity .7s cubic-bezier(.16,1,.3,1), transform .7s cubic-bezier(.16,1,.3,1); }
    .pt-anim .reveal.in { opacity: 1; transform: none; }
    @media (prefers-reduced-motion: reduce) {
        .pt-anim .reveal { opacity: 1 !important; transform: none !important; transition: none !important; }
        * { animation-duration: .001s !important; animation-iteration-count: 1 !important; }
    }

    /* ---------- Hero ---------- */
    .pt-hero {
        position: relative; overflow: hidden;
        border-radius: var(--pt-radius-lg);
        padding: 2.4rem 2.2rem 2.1rem;
        margin-bottom: 1.5rem;
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
    .pt-hero-content { position: relative; z-index: 2; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 1.5rem; }
    .pt-chip {
        display: inline-flex; align-items: center; gap: .4rem;
        background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.28);
        backdrop-filter: blur(8px);
        padding: .38rem .95rem; border-radius: 999px;
        font-weight: 700; font-size: .78rem; letter-spacing: .4px; color: #fff;
    }
    .pt-chip.tone-success { background: rgba(16,185,129,.25); border-color: rgba(110,231,183,.6); }
    .pt-chip.tone-warning { background: rgba(245,158,11,.25); border-color: rgba(253,230,138,.6); }
    .pt-chip.tone-danger  { background: rgba(239,68,68,.3); border-color: rgba(252,165,165,.6); }
    .pt-chip .dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; animation: ptPulseBlue 2s infinite; }
    .pt-amount { font-size: clamp(1.9rem, 4vw, 2.7rem); font-weight: 800; line-height: 1.1; letter-spacing: -1px; text-shadow: 0 4px 18px rgba(0,0,0,.25); font-variant-numeric: tabular-nums; }

    /* ---------- Pipeline Stepper ---------- */
    .pt-pipeline {
        position: relative; z-index: 2;
        background: var(--pt-card-bg);
        border: 1px solid var(--pt-border);
        border-radius: var(--pt-radius-lg);
        box-shadow: var(--pt-shadow);
        padding: 1.35rem 1.25rem 1.1rem;
        margin-bottom: 1.75rem;
        overflow-x: auto;
    }
    .pt-pipeline-track { display: flex; min-width: 720px; }
    .pt-stage { flex: 1; position: relative; text-align: center; cursor: pointer; padding: 0 .35rem; background: none; border: 0; }
    .pt-stage .bar { position: absolute; top: 21px; left: 50%; width: 100%; height: 4px; background: var(--pt-border); z-index: 0; border-radius: 2px; overflow: hidden; }
    .pt-stage:last-child .bar { display: none; }
    .pt-stage .bar i {
        display: block; height: 100%; width: 0%;
        background: linear-gradient(90deg, var(--pt-success), #34d399);
        border-radius: 2px;
        transition: width 1s cubic-bezier(.16,1,.3,1);
    }
    .pt-stage.bar-full .bar i { width: 100%; }
    .pt-stage .node {
        position: relative; z-index: 1;
        width: 44px; height: 44px; margin: 0 auto;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; font-weight: 700;
        background: #fff; color: var(--pt-secondary);
        border: 2px solid var(--pt-border);
        transition: transform .25s cubic-bezier(.34,1.56,.64,1), box-shadow .25s, background .3s, color .3s, border-color .3s;
    }
    .pt-stage:hover .node { transform: translateY(-4px) scale(1.07); box-shadow: 0 10px 20px -8px rgba(15,23,42,.25); }
    .pt-stage.done .node { background: linear-gradient(135deg, var(--pt-success), #059669); border-color: transparent; color: #fff; }
    .pt-stage.current .node { background: linear-gradient(135deg, var(--pt-primary), var(--pt-primary-2)); border-color: transparent; color: #fff; animation: ptPulseBlue 2.2s infinite; }
    .pt-stage .lbl { margin-top: .55rem; font-size: .78rem; font-weight: 800; color: var(--pt-ink); white-space: nowrap; }
    .pt-stage .sub { font-size: .67rem; color: var(--pt-secondary); font-weight: 600; white-space: nowrap; }
    .pt-stage.todo .lbl, .pt-stage.todo .sub { color: #94a3b8; }
    .pt-stage.current .lbl { color: var(--pt-primary); }

    /* ---------- Cards ---------- */
    .process-card {
        position: relative;
        background: var(--pt-card-bg);
        border: 1px solid var(--pt-border);
        border-radius: var(--pt-radius);
        box-shadow: var(--pt-shadow);
        transition: transform .35s cubic-bezier(.25,.8,.25,1), box-shadow .35s, border-color .35s;
        overflow: hidden;
    }
    .process-card:hover { transform: translateY(-5px); box-shadow: var(--pt-shadow-hover); border-color: #c7d2fe; }
    .process-card-header {
        padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--pt-border);
        background: rgba(248, 250, 252, .6);
        display: flex; align-items: center; gap: .75rem;
    }
    .process-card-body { padding: 1.5rem; }
    .process-section-title { font-size: .8rem; letter-spacing: .08em; text-transform: uppercase; color: var(--pt-secondary); font-weight: 700; margin-bottom: 1rem; }
    .process-value { font-weight: 700; color: var(--pt-ink); font-size: 1.05rem; }
    .process-muted { color: var(--pt-secondary); font-size: .85rem; }

    /* aksen atas berwarna pada kartu dokumen */
    .doc-card { border-top: 0; }
    .doc-card::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
        background: linear-gradient(90deg, var(--tone, var(--pt-primary)), transparent 85%);
    }
    .doc-icon-tile {
        width: 52px; height: 52px; flex-shrink: 0;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.45rem;
        background: var(--tone-soft, var(--tone-indigo-soft));
        color: var(--tone, var(--pt-primary));
        transition: transform .3s cubic-bezier(.34,1.56,.64,1);
    }
    .process-card:hover .doc-icon-tile { transform: rotate(-6deg) scale(1.08); }
    .doc-icon-tile.waiting { animation: ptBounce 2.4s ease-in-out infinite; }

    /* status chips */
    .pt-status {
        display: inline-flex; align-items: center; gap: .4rem;
        font-size: .74rem; font-weight: 800; letter-spacing: .3px;
        padding: .42rem .9rem; border-radius: 999px; border: 1px solid transparent;
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

    /* verifikator mini chips */
    .pt-approver {
        display: inline-flex; align-items: center; gap: .45rem;
        background: #f8fafc; border: 1px solid var(--pt-border);
        border-radius: 999px; padding: .35rem .8rem .35rem .45rem;
        font-size: .76rem; font-weight: 700; color: #334155;
        transition: transform .2s, box-shadow .2s;
    }
    .pt-approver:hover { transform: translateY(-2px); box-shadow: 0 6px 14px -8px rgba(15,23,42,.3); }
    .pt-approver .ava {
        width: 24px; height: 24px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .72rem; color: #fff;
    }
    .pt-approver.ok .ava { background: var(--pt-success); }
    .pt-approver.wait .ava { background: var(--pt-warning); animation: ptPulse 2s infinite; }
    .pt-approver.bad .ava { background: var(--pt-danger); }
    .pt-approver.idle .ava { background: #94a3b8; }

    /* glowing attention box (persetujuan saya) */
    .pt-attn { position: relative; border-radius: 1.2rem; padding: 2px; overflow: hidden; }
    .pt-attn::before {
        content: ''; position: absolute; inset: -150%;
        background: conic-gradient(from 0deg, transparent 0 60deg, var(--pt-warning) 90deg, #fbbf24 120deg, transparent 150deg 360deg);
        animation: ptGlowSweep 3.2s linear infinite;
    }
    .pt-attn-inner { position: relative; z-index: 1; background: #fffbeb; border-radius: calc(1.2rem - 2px); padding: 1.4rem; }

    /* tombol aksi + efek ripple */
    .btn-pt-action {
        position: relative; overflow: hidden;
        border-radius: 999px; font-weight: 700; padding: .55rem 1.3rem;
        transition: transform .2s, box-shadow .2s; display: inline-flex; align-items: center; gap: .5rem;
    }
    .btn-pt-action:hover { transform: translateY(-2px); }
    .btn-pt-action:active { transform: scale(.96); }
    .pt-ripple {
        position: absolute; border-radius: 50%; pointer-events: none;
        background: rgba(255,255,255,.55); transform: scale(0); opacity: 1;
        transition: transform .55s ease-out, opacity .6s ease-out;
    }
    .pt-ripple.go { transform: scale(4); opacity: 0; }

    /* count-up angka */
    [data-countup] { font-variant-numeric: tabular-nums; }

    /* dropzone-ish upload panel */
    .pt-upload {
        border: 2px dashed #c7d2fe; border-radius: 1.1rem;
        background: linear-gradient(180deg, #f8faff, #eef2ff66);
        padding: 1.35rem; transition: border-color .25s, background .25s, transform .25s;
    }
    .pt-upload:hover { border-color: var(--pt-primary); transform: translateY(-2px); }

    /* locked / empty state */
    .pt-locked {
        border: 2px dashed var(--pt-border); border-radius: 1.1rem;
        background: repeating-linear-gradient(-45deg, #f8fafc 0 14px, #f1f5f9 14px 28px);
        padding: 1.25rem 1.4rem; color: #64748b;
        display: flex; align-items: center; gap: .9rem;
    }

    /* sticky sidebar */
    .process-sticky { position: sticky; top: 88px; height: max-content; }

    /* progress ring */
    .pt-ring-wrap { position: relative; width: 150px; height: 150px; margin: 0 auto; }
    .pt-ring-wrap svg { transform: rotate(-90deg); }
    .pt-ring-bg { fill: none; stroke: #eef2f7; stroke-width: 11; }
    .pt-ring-bar {
        fill: none; stroke: url(#ptRingGrad); stroke-width: 11; stroke-linecap: round;
        stroke-dasharray: var(--ring-circ); stroke-dashoffset: var(--ring-circ);
        animation: ptDash 1.6s cubic-bezier(.16,1,.3,1) .35s forwards;
        filter: drop-shadow(0 4px 6px rgba(79,70,229,.35));
    }
    .pt-ring-label { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }

    /* checklist timeline */
    .pt-check { position: relative; padding-left: 34px; }
    .pt-check::before { content: ''; position: absolute; left: 11px; top: 8px; bottom: 8px; width: 2px; background: var(--pt-border); border-radius: 2px; }
    .pt-check-item { position: relative; padding: .42rem 0; display: flex; align-items: center; gap: .65rem; }
    .pt-check-item .pin {
        position: absolute; left: -34px; top: 50%; transform: translateY(-50%);
        width: 24px; height: 24px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: #fff; border: 2px solid var(--pt-border); color: #cbd5e1; font-size: .72rem;
        transition: all .3s;
    }
    .pt-check-item.done .pin { background: var(--pt-success); border-color: var(--pt-success); color: #fff; }
    .pt-check-item.next .pin { border-color: var(--pt-primary); color: var(--pt-primary); animation: ptPulseBlue 2s infinite; }
    .pt-check-item .txt { font-size: .86rem; font-weight: 600; color: #94a3b8; }
    .pt-check-item.done .txt { color: var(--pt-ink); }
    .pt-check-item.next .txt { color: var(--pt-primary); font-weight: 800; }

    /* log feed */
    .pt-log { border-left: 2px solid var(--pt-border); margin-left: 8px; }
    .pt-log-item { position: relative; padding: .65rem 0 .65rem 1.15rem; }
    .pt-log-item::before {
        content: ''; position: absolute; left: -6px; top: 1rem;
        width: 10px; height: 10px; border-radius: 50%;
        background: #c7d2fe; border: 2px solid #fff; box-shadow: 0 0 0 2px #c7d2fe55;
    }
    .pt-log-item:first-child::before { background: var(--pt-primary); animation: ptPulseBlue 2.4s infinite; }

    .pt-alert { border-radius: var(--pt-radius); border: none; box-shadow: 0 4px 15px rgba(0,0,0,.04); }

    /* confetti */
    .pt-confetti { position: fixed; inset: 0; pointer-events: none; z-index: 2000; overflow: hidden; }
    .pt-confetti span {
        position: absolute; top: -4vh; width: 9px; height: 14px; border-radius: 2px;
        animation: ptConfetti linear forwards;
    }

    /* ── Panel prasyarat draft (pw) ─────────────────────────────── */
    .pw-panel {
        border: 1px solid #f3e3bd;
        border-radius: var(--pt-radius, 18px);
        background: linear-gradient(180deg, #fffdf6, #fef8ec);
        box-shadow: 0 14px 32px -24px rgba(180, 122, 9, .5);
        overflow: hidden;
    }
    .pw-head {
        display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
        padding: 1.15rem 1.4rem;
        border-bottom: 1px dashed #f0deb2;
    }
    .pw-ic {
        width: 46px; height: 46px; border-radius: 13px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 1.3rem; color: #fff;
        background: linear-gradient(135deg, #d97706, #f59e0b);
        box-shadow: 0 10px 20px -10px rgba(217, 119, 6, .8);
        animation: pwPulse 2.4s ease-in-out infinite;
    }
    @keyframes pwPulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.07); } }
    .pw-count {
        margin-left: auto;
        font-size: .74rem; font-weight: 800; letter-spacing: .04em;
        color: #92600a; background: #fdeec0; border: 1px solid #f0deb2; border-radius: 999px;
        padding: .35rem .85rem; white-space: nowrap;
    }
    .pw-item {
        display: flex; align-items: flex-start; gap: .85rem;
        padding: .8rem 1.4rem;
        opacity: 0; transform: translateX(-10px);
        animation: pwIn .45s cubic-bezier(.22,1,.36,1) forwards;
    }
    .pw-item + .pw-item { border-top: 1px solid #faf0d8; }
    .pw-item:nth-child(2) { animation-delay: .08s; }
    .pw-item:nth-child(3) { animation-delay: .16s; }
    .pw-item:nth-child(4) { animation-delay: .24s; }
    .pw-item:nth-child(5) { animation-delay: .32s; }
    .pw-item:nth-child(6) { animation-delay: .40s; }
    @keyframes pwIn { to { opacity: 1; transform: translateX(0); } }
    .pw-item-ic {
        width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: .95rem;
        background: #fff; border: 1.5px dashed #ecd9a8; color: #b45309;
    }
    .pw-item-text { font-size: .86rem; color: #57534e; overflow-wrap: anywhere; }
    .pw-role {
        display: inline-flex; align-items: center; gap: .3rem;
        font-size: .66rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
        color: #92600a; background: #fdeec0; border-radius: 999px; padding: .18rem .6rem;
        margin-top: .3rem;
    }
    @media (prefers-reduced-motion: reduce) {
        .pw-ic { animation: none; }
        .pw-item { animation: none; opacity: 1; transform: none; }
    }

    /* ── Ringkasan Tagihan (rk) ─────────────────────────────────── */
    .rk-identity {
        position: relative;
        border: 1px solid #e7eaf3;
        border-radius: 14px;
        padding: 1.1rem 1.25rem;
        background: linear-gradient(135deg, rgba(79,70,229,.05), rgba(124,58,237,.02) 55%, transparent);
        overflow: hidden;
    }
    .rk-identity::before {
        content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
        background: linear-gradient(180deg, var(--pt-primary), var(--pt-primary-2));
    }
    .rk-job {
        font-weight: 800; font-size: 1.15rem; color: #0f172a;
        letter-spacing: -.01em; line-height: 1.35; overflow-wrap: anywhere;
    }
    .rk-desc { font-size: .82rem; color: #64748b; margin-top: .3rem; overflow-wrap: anywhere; }
    .rk-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        font-size: .74rem; font-weight: 700;
        border-radius: 999px; padding: .3rem .75rem;
        background: #fff; border: 1px solid #e7eaf3; color: #475569;
        transition: transform .18s, box-shadow .18s, border-color .18s;
    }
    .rk-chip i { color: var(--pt-primary); }
    .rk-chip.rk-copy { cursor: pointer; }
    .rk-chip.rk-copy:hover { transform: translateY(-2px); border-color: var(--pt-primary); box-shadow: 0 8px 18px -12px rgba(79,70,229,.6); }
    .rk-chip.rk-copied { background: #e8f5ec; border-color: #cbe7d3; color: #15803d; }
    .rk-chip.rk-copied i { color: #15803d; }

    .rk-stat {
        position: relative;
        border: 1px solid #e7eaf3;
        border-radius: 14px;
        padding: 1rem 1.15rem;
        height: 100%;
        background: #fff;
        transition: transform .2s, box-shadow .2s, border-color .2s;
        overflow: hidden;
    }
    .rk-stat:hover { transform: translateY(-3px); box-shadow: 0 14px 28px -18px rgba(15,23,42,.35); }
    .rk-stat .rk-ic {
        width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: .95rem;
        background: var(--tone-slate-soft); color: var(--tone-slate);
    }
    .rk-stat .rk-lbl { font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #94a3b8; }
    .rk-stat .rk-val { font-size: 1.25rem; font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; letter-spacing: -.01em; }
    .rk-stat.rk-potongan .rk-ic { background: rgba(225,29,72,.10); color: #e11d48; }
    .rk-stat.rk-potongan .rk-val { color: #e11d48; }
    .rk-stat.rk-netto {
        border-color: rgba(16,185,129,.35);
        background: linear-gradient(135deg, rgba(16,185,129,.10), rgba(16,185,129,.02));
    }
    .rk-stat.rk-netto:hover { box-shadow: 0 16px 32px -18px rgba(16,185,129,.55); }
    .rk-stat.rk-netto .rk-ic { background: rgba(16,185,129,.15); color: #059669; }
    .rk-stat.rk-netto .rk-val { color: #047857; font-size: 1.6rem; }
    .rk-stat.rk-netto::after {
        content: ''; position: absolute; top: 0; bottom: 0; width: 40%; left: -60%;
        background: linear-gradient(100deg, transparent, rgba(255,255,255,.55), transparent);
        transform: skewX(-18deg);
    }
    .pt-anim .reveal.in .rk-stat.rk-netto::after { animation: rkShine 3.4s ease-in-out 1.2s infinite; }
    @keyframes rkShine { 0%, 60% { left: -60%; } 90%, 100% { left: 130%; } }

    .rk-bar-wrap { margin-top: .35rem; }
    .rk-bar {
        display: flex; height: 12px; border-radius: 999px; overflow: hidden;
        background: #eef1f7; box-shadow: inset 0 1px 2px rgba(15,23,42,.06);
    }
    .rk-bar span { width: 0; transition: width 1.3s cubic-bezier(.16,1,.3,1) .35s; }
    .rk-bar .rk-seg-netto { background: linear-gradient(90deg, #10b981, #34d399); }
    .rk-bar .rk-seg-potongan { background: linear-gradient(90deg, #fb7185, #e11d48); }
    .pt-anim .reveal.in .rk-bar span { width: var(--w); }
    html:not(.pt-anim) .rk-bar span { width: var(--w); }
    .rk-legend { display: flex; flex-wrap: wrap; gap: 1rem; font-size: .74rem; color: #64748b; margin-top: .45rem; }
    .rk-legend .dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; margin-right: .3rem; }
    @media (prefers-reduced-motion: reduce) {
        .rk-bar span { transition: none; width: var(--w) !important; }
        .rk-stat, .rk-stat:hover, .rk-chip.rk-copy:hover { transition: none; transform: none; }
        .rk-stat.rk-netto::after { animation: none !important; }
    }
</style>
@endpush

@section('content')
<script>document.documentElement.classList.add('pt-anim');</script>

{{-- Seluruh konten dinamis hidup di _show_content agar bisa dirender ulang
     sebagai fragment (?partial=1) dan di-swap tanpa reload oleh form async. --}}
<div id="ptShowContent">
    @include('proses_tagihan._show_content')
</div>

@include('proses_tagihan._async_forms')
@include('proses_tagihan._page_scripts')
@endsection
