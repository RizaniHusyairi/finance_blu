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
</style>
@endpush

@section('content')
<script>document.documentElement.classList.add('pt-anim');</script>

@php
    $pihak = $tagihan->detailKontrak?->kontrakTermin?->kontrak?->vendor?->nama_pihak
        ?? $tagihan->pihak?->nama_pihak
        ?? $tagihan->nama_supplier
        ?? '-';

    $selesai = $tagihan->status === 'SELESAI';

    // tone chip status hero
    $statusTone = match (true) {
        $selesai => 'tone-success',
        str_starts_with($tagihan->status, 'DITOLAK') => 'tone-danger',
        str_starts_with($tagihan->status, 'REVISI') => 'tone-warning',
        default => '',
    };

    // ------- Pipeline stages -------
    $pajakBeres = $state['potonganPajak']->isEmpty() || $state['pajakSettled'];
    $stages = [
        ['label' => 'Verifikasi', 'sub' => '6 Verifikator',      'icon' => 'bi-patch-check-fill',   'done' => $state['tagihanApproved'], 'anchor' => 'sec-ringkasan'],
        ['label' => 'COA',        'sub' => 'Pembebanan',         'icon' => 'bi-calculator-fill',    'done' => $state['coaDone'],         'anchor' => 'sec-coa'],
        ['label' => 'KPA',        'sub' => 'Persetujuan',        'icon' => 'bi-shield-fill-check',  'done' => $state['kpaDone'],         'anchor' => 'sec-kpa'],
        // Rantai lama (dibuat sebelum aturan pajak) dianggap melewati tahap ini.
        ...($state['pajakKontrak'] ?? false ? [
            ['label' => 'Pajak',  'sub' => 'Tipe & Faktur',      'icon' => 'bi-receipt-cutoff',     'done' => $state['pajakKontrakDone'] || (bool) $state['spp'], 'anchor' => 'sec-pajak-kontrak'],
        ] : []),
        ['label' => 'Dokumen',    'sub' => 'SPP · SPM · NPI',    'icon' => 'bi-layers-fill',        'done' => $state['dokumenSiapBayar'],'anchor' => 'sec-dokumen'],
        ['label' => 'Transfer',   'sub' => 'Bukti Bayar',        'icon' => 'bi-bank2',              'done' => (bool) $state['buktiTransfer'], 'anchor' => 'sec-penyelesaian'],
        ['label' => 'SP2D',       'sub' => 'Penerbitan',         'icon' => 'bi-award-fill',         'done' => $state['sp2dTerbit'],      'anchor' => 'sec-penyelesaian'],
        ['label' => 'Pembukuan',  'sub' => 'Pajak & BKU',        'icon' => 'bi-journal-check',      'done' => $state['bkuPosted'] && $pajakBeres, 'anchor' => 'sec-penyelesaian'],
    ];
    $currentIdx = count($stages);
    foreach ($stages as $i => $s) {
        if (! $s['done']) { $currentIdx = $i; break; }
    }
@endphp

{{-- Confetti saat tagihan selesai --}}
@if($selesai)
    <div class="pt-confetti" id="ptConfetti" data-tagihan="{{ $tagihan->id }}"></div>
@endif

<!-- Back -->
<div class="mb-3 reveal">
    <a href="{{ route('proses-tagihan.index') }}" class="btn btn-sm btn-light rounded-pill border shadow-sm fw-semibold px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
    </a>
</div>

<!-- ============ HERO ============ -->
<div class="pt-hero">
    <div class="grid-lines"></div>
    <div class="pt-hero-content">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="pt-chip"><i class="bi bi-tag-fill"></i> {{ $tagihan->tipe_tagihan }}</span>
                <span class="pt-chip {{ $statusTone }}">
                    @unless($selesai)<span class="dot"></span>@else<i class="bi bi-check-circle-fill"></i>@endunless
                    {{ str_replace('_', ' ', $tagihan->status) }}
                </span>
            </div>
            <h2 class="fw-bolder mb-1 text-white" style="font-size: clamp(1.4rem, 3vw, 1.9rem); letter-spacing: -.5px;">
                {{ $tagihan->nomor_tagihan }}
            </h2>
            <div class="text-white opacity-75 fw-semibold"><i class="bi bi-building me-1"></i> {{ $pihak }}</div>
        </div>
        <div class="text-md-end">
            <div class="text-white fs-8 text-uppercase fw-bold opacity-75 mb-1" style="letter-spacing: 1.6px;">Total Netto (Dibayarkan)</div>
            <div class="pt-amount">
                <span class="opacity-75" style="font-size:.55em; vertical-align: .45em;">Rp</span><span data-countup="{{ (float) $tagihan->total_netto }}">{{ number_format((float) $tagihan->total_netto, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>

<!-- ============ PIPELINE STEPPER ============ -->
<div class="pt-pipeline reveal" id="ptPipeline">
    <div class="pt-pipeline-track">
        @foreach($stages as $i => $stage)
            @php $cls = $stage['done'] ? 'done' : ($i === $currentIdx ? 'current' : 'todo'); @endphp
            <button type="button" class="pt-stage {{ $cls }} {{ $stage['done'] ? 'bar-full' : '' }}" data-scroll="{{ $stage['anchor'] }}"
                    title="{{ $stage['label'] }} — {{ $stage['done'] ? 'Selesai' : ($i === $currentIdx ? 'Tahap saat ini' : 'Belum dimulai') }}">
                <span class="bar"><i></i></span>
                <span class="node">
                    @if($stage['done'])<i class="bi bi-check-lg"></i>@else<i class="bi {{ $stage['icon'] }}"></i>@endif
                </span>
                <div class="lbl">{{ $stage['label'] }}</div>
                <div class="sub">{{ $stage['sub'] }}</div>
            </button>
        @endforeach
    </div>
</div>

{{-- Flash messages --}}
@if(session('success'))
    <div class="alert alert-success pt-alert d-flex align-items-center gap-3 reveal">
        <i class="bi bi-check-circle-fill fs-4"></i><div>{{ session('success') }}</div>
    </div>
@endif
@if(session('warning'))
    <div class="alert alert-warning pt-alert d-flex align-items-center gap-3 reveal">
        <i class="bi bi-exclamation-circle-fill fs-4"></i><div>{{ session('warning') }}</div>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger pt-alert d-flex align-items-center gap-3 reveal">
        <i class="bi bi-exclamation-triangle-fill fs-4"></i><div>{{ session('error') }}</div>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger pt-alert d-flex align-items-center gap-3 reveal">
        <i class="bi bi-exclamation-triangle-fill fs-4"></i><div>{{ $errors->first() }}</div>
    </div>
@endif

<div class="row g-4">
    <!-- ============ MAIN ============ -->
    <div class="col-lg-8">

        <!-- Ringkasan -->
        <div id="sec-ringkasan" class="process-card mb-4 reveal">
            <div class="process-card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3 pb-3 border-bottom border-light-subtle">
                    <div class="d-flex align-items-center gap-3">
                        <div class="doc-icon-tile" style="--tone: var(--pt-primary); --tone-soft: var(--tone-indigo-soft); width:44px;height:44px;font-size:1.2rem;">
                            <i class="bi bi-journal-text"></i>
                        </div>
                        <h6 class="mb-0 fw-bold text-dark">Ringkasan Tagihan</h6>
                    </div>
                    @if($state['tagihanApproved'])
                        <span class="pt-status success"><i class="bi bi-patch-check-fill"></i> Terverifikasi 6 Verifikator</span>
                    @else
                        <span class="pt-status warning shimmer"><i class="bi bi-hourglass-split"></i> Proses Verifikasi</span>
                    @endif
                </div>

                <div class="row g-4">
                    <div class="col-12">
                        <div class="process-muted mb-1">Uraian / Deskripsi</div>
                        <div class="process-value bg-light p-3 rounded-3 border border-light-subtle fs-6">{{ $tagihan->deskripsi }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="process-muted mb-1">Nilai Bruto</div>
                        <div class="process-value fs-5">Rp <span data-countup="{{ (float) $tagihan->total_bruto }}">{{ number_format((float) $tagihan->total_bruto, 0, ',', '.') }}</span></div>
                    </div>
                    <div class="col-md-4">
                        <div class="process-muted mb-1">Total Potongan</div>
                        <div class="process-value fs-5 text-danger">- Rp <span data-countup="{{ (float) $tagihan->total_potongan }}">{{ number_format((float) $tagihan->total_potongan, 0, ',', '.') }}</span></div>
                    </div>
                    <div class="col-md-4">
                        <div class="process-muted mb-1">Netto Dibayarkan</div>
                        <div class="process-value fs-5 text-success">Rp <span data-countup="{{ (float) $tagihan->total_netto }}">{{ number_format((float) $tagihan->total_netto, 0, ',', '.') }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informasi Vendor -->
        @php
            $vendor = $tagihan->detailKontrak?->kontrakTermin?->kontrak?->vendor ?? $tagihan->pihak;
            $rekening = $vendor ? $vendor->rekening()->where('status_aktif', true)->first() ?? $vendor->rekening()->first() : null;
        @endphp
        @if($vendor)
        <div class="process-card mb-4 reveal">
            <div class="process-card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3 pb-3 border-bottom border-light-subtle">
                    <div class="d-flex align-items-center gap-3">
                        <div class="doc-icon-tile" style="--tone: var(--pt-info); --tone-soft: var(--tone-info-soft); width:44px;height:44px;font-size:1.2rem;">
                            <i class="bi bi-building"></i>
                        </div>
                        <h6 class="mb-0 fw-bold text-dark">Informasi Vendor & Rekening</h6>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="process-muted mb-1">Nama Vendor</div>
                        <div class="process-value fs-6">{{ $vendor->nama_pihak ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="process-muted mb-1">NPWP</div>
                        <div class="process-value fs-6">{{ $vendor->npwp ?? '-' }}</div>
                    </div>
                    @if($rekening)
                    <div class="col-md-4">
                        <div class="process-muted mb-1">Bank</div>
                        <div class="process-value fs-6">{{ $rekening->nama_bank ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="process-muted mb-1">Nomor Rekening</div>
                        <div class="process-value fs-6 font-monospace text-primary fw-bolder">{{ $rekening->nomor_rekening ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="process-muted mb-1">Atas Nama</div>
                        <div class="process-value fs-6">{{ $rekening->nama_rekening ?? '-' }}</div>
                    </div>
                    @else
                    <div class="col-12">
                        <div class="alert alert-warning small mb-0 py-2 border-0 d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-triangle"></i> Data rekening bank vendor belum dilengkapi di Master Data.
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Daftar penerima honorarium (khusus tipe HONORARIUM) -->
        @if($tagihan->tipe_tagihan === 'HONORARIUM')
            <div id="sec-penerima-honor" class="reveal">
                @include('proses_tagihan._penerima_honorarium_card', ['tagihan' => $tagihan])
            </div>
        @endif

        <!-- Dokumen yang diunggah / di-generate saat pembuatan tagihan -->
        <div id="sec-dokumen-tagihan" class="reveal">
            @include('proses_tagihan._dokumen_pendukung_card', ['dokumenPendukung' => $dokumenPendukung])
        </div>

        @if($state['missingPrereqs'] && ! $state['spp'])
            <div class="alert alert-warning pt-alert d-flex align-items-start gap-3 reveal">
                <i class="bi bi-exclamation-circle-fill fs-3 text-warning"></i>
                <div>
                    <div class="fw-bold mb-1 text-dark fs-6">Draft Dokumen Belum Dapat Dibuat</div>
                    <ul class="mb-0 text-dark opacity-75 ps-3">
                        @foreach($state['missingPrereqs'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Prasyarat: COA + KPA -->
        <div id="sec-coa" class="reveal">
            @include('proses_tagihan._coa_card', ['tagihan' => $tagihan, 'state' => $state, 'coaOptions' => $coaOptions])
        </div>

        <div id="sec-kpa" class="reveal">
            @include('proses_tagihan._kpa_card', ['tagihan' => $tagihan, 'state' => $state])
        </div>

        @if($state['pajakKontrak'] ?? false)
            <div id="sec-pajak-kontrak" class="reveal">
                @include('proses_tagihan._pajak_kontrak_card', ['tagihan' => $tagihan, 'state' => $state, 'pajakOptions' => $pajakOptions])
            </div>
        @endif

        @php
            // Bagian yang dapat ditandai verifikator pada modal revisi
            // "kembalikan ke pembuat tagihan" (lihat _dokumen_card).
            $chainDocsForRevisi = array_filter([
                'tagihan' => ['label' => 'Data Tagihan & Dokumen Pendukung', 'nomor' => $tagihan->nomor_tagihan],
                'spp' => $state['spp'] ? ['label' => 'SPP', 'nomor' => $state['spp']->nomor_spp] : null,
                'spm' => $state['spm'] ? ['label' => 'SPM', 'nomor' => $state['spm']->nomor_spm] : null,
                'npi' => $state['npi'] ? ['label' => 'NPI', 'nomor' => $state['npi']->nomor_npi] : null,
            ]);
        @endphp

        <!-- Dokumen Pencairan -->
        <div id="sec-dokumen" class="d-flex align-items-center gap-3 mt-5 mb-3 reveal">
            <h5 class="fw-bolder text-dark mb-0"><i class="bi bi-layers-half me-2 text-primary"></i>Alur Dokumen Pencairan</h5>
            <div class="flex-grow-1 border-top border-2 border-light-subtle"></div>
            @if($state['dokumenSiapBayar'])
                <span class="pt-status success"><i class="bi bi-check-circle-fill"></i> Semua Disetujui</span>
            @endif
        </div>

        <div class="reveal">
            @include('proses_tagihan._dokumen_card', [
                'tagihan' => $tagihan,
                'jenis' => 'spp',
                'label' => 'SPP — Surat Permintaan Pembayaran',
                'icon' => 'bi-file-earmark-arrow-up-fill',
                'color' => 'indigo',
                'document' => $state['spp'],
                'instance' => $state['sppInstance'],
                'myApprovals' => $state['myApprovals']['spp'],
                'chainDocs' => $chainDocsForRevisi,
                'canSubmit' => auth()->user()?->hasAnyRole(['Operator BLU', 'Super Admin']),
                'submitRoute' => $state['spp'] ? route('proses-tagihan.spp.ajukan', $tagihan->id) : null,
                'pdfRoute' => $state['spp'] ? route('spps.cetak-pdf', $state['spp']->id) : null,
            ])
        </div>

        <div class="reveal">
            @include('proses_tagihan._dokumen_card', [
                'tagihan' => $tagihan,
                'jenis' => 'spm',
                'label' => 'SPM — Surat Perintah Membayar',
                'icon' => 'bi-file-earmark-check-fill',
                'color' => 'violet',
                'document' => $state['spm'],
                'instance' => $state['spmInstance'],
                'myApprovals' => $state['myApprovals']['spm'],
                'chainDocs' => $chainDocsForRevisi,
                'canSubmit' => auth()->user()?->hasAnyRole(['Operator BLU', 'Super Admin']),
                'submitRoute' => $state['spm'] ? route('proses-tagihan.spm.ajukan', $tagihan->id) : null,
                'pdfRoute' => $state['spm'] ? route('spms.cetak-pdf', $state['spm']->id) : null,
            ])
        </div>

        <div class="reveal">
            @include('proses_tagihan._dokumen_card', [
                'tagihan' => $tagihan,
                'jenis' => 'npi',
                'label' => 'NPI — Nota Pemindahbukuan Internal',
                'icon' => 'bi-file-earmark-ruled-fill',
                'color' => 'emerald',
                'document' => $state['npi'],
                'instance' => $state['npiInstance'],
                'myApprovals' => $state['myApprovals']['npi'],
                'chainDocs' => $chainDocsForRevisi,
                'canSubmit' => auth()->user()?->hasAnyRole(['Bendahara Pengeluaran', 'Super Admin'])
                    && $state['sppApproved'] && $state['spmApproved'],
                'lockedNote' => (! $state['sppApproved'] || ! $state['spmApproved'])
                    ? 'NPI baru dapat diajukan setelah SPP dan SPM disetujui oleh verifikatornya.'
                    : null,
                'submitRoute' => $state['npi'] ? route('proses-tagihan.npi.ajukan', $tagihan->id) : null,
                'pdfRoute' => $state['npi'] ? route('npis.cetak-pdf', $state['npi']->id) : null,
            ])
        </div>

        <!-- Penyelesaian -->
        <div id="sec-penyelesaian" class="d-flex align-items-center gap-3 mt-5 mb-3 reveal">
            <h5 class="fw-bolder text-dark mb-0"><i class="bi bi-wallet2 me-2 text-success"></i>Penyelesaian Pembayaran</h5>
            <div class="flex-grow-1 border-top border-2 border-light-subtle"></div>
            @if($selesai)
                <span class="pt-status success"><i class="bi bi-stars"></i> Tagihan Selesai</span>
            @endif
        </div>

        <div class="reveal">
            @include('proses_tagihan._bukti_transfer_card', ['tagihan' => $tagihan, 'state' => $state])
        </div>
        <div class="reveal">
            @include('proses_tagihan._sp2d_card', ['tagihan' => $tagihan, 'state' => $state])
        </div>
        <div class="reveal">
            @include('proses_tagihan._pajak_card', ['tagihan' => $tagihan, 'state' => $state])
        </div>
    </div>

    <!-- ============ SIDEBAR ============ -->
    <div class="col-lg-4">
        <div class="process-sticky reveal">
            @include('proses_tagihan._timeline', ['tagihan' => $tagihan, 'state' => $state])
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
(function () {
    'use strict';
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- Reveal on scroll ---------- */
    var revealEls = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && !reduced) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) {
                    e.target.classList.add('in');
                    io.unobserve(e.target);
                }
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
        revealEls.forEach(function (el) { io.observe(el); });
    } else {
        revealEls.forEach(function (el) { el.classList.add('in'); });
    }

    /* ---------- Count-up rupiah ---------- */
    var fmt = new Intl.NumberFormat('id-ID');
    function countUp(el) {
        var target = parseFloat(el.getAttribute('data-countup')) || 0;
        if (reduced || target <= 0) { el.textContent = fmt.format(target); return; }
        var dur = 1300, start = null;
        function tick(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = fmt.format(Math.round(target * eased));
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    }
    var cuObserver = ('IntersectionObserver' in window)
        ? new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) { countUp(e.target); cuObserver.unobserve(e.target); }
            });
        }, { threshold: 0.4 })
        : null;
    document.querySelectorAll('[data-countup]').forEach(function (el) {
        cuObserver ? cuObserver.observe(el) : countUp(el);
    });

    /* ---------- Stepper: klik untuk scroll ---------- */
    document.querySelectorAll('.pt-stage[data-scroll]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.getAttribute('data-scroll'));
            if (target) target.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
        });
    });

    /* ---------- Ripple pada tombol aksi ---------- */
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest('.btn-pt-action');
        if (!btn || reduced) return;
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

    /* ---------- Tombol salin (magic link, dsb) ---------- */
    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var text = btn.getAttribute('data-copy');
            navigator.clipboard.writeText(text).then(function () {
                var orig = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check-lg"></i> Tersalin!';
                btn.classList.add('btn-success', 'text-white');
                setTimeout(function () {
                    btn.innerHTML = orig;
                    btn.classList.remove('btn-success', 'text-white');
                }, 1600);
            });
        });
    });

    /* ---------- Confetti saat SELESAI (sekali per tagihan) ---------- */
    var conf = document.getElementById('ptConfetti');
    if (conf && !reduced) {
        var key = 'pt-confetti-' + conf.getAttribute('data-tagihan');
        if (!sessionStorage.getItem(key)) {
            sessionStorage.setItem(key, '1');
            var colors = ['#4f46e5', '#10b981', '#f59e0b', '#06b6d4', '#8b5cf6', '#f43f5e'];
            for (var i = 0; i < 90; i++) {
                var s = document.createElement('span');
                s.style.left = Math.random() * 100 + 'vw';
                s.style.background = colors[i % colors.length];
                s.style.animationDuration = (2.4 + Math.random() * 2.2) + 's';
                s.style.animationDelay = (Math.random() * 1.4) + 's';
                s.style.transform = 'rotate(' + Math.random() * 360 + 'deg)';
                conf.appendChild(s);
            }
            setTimeout(function () { conf.remove(); }, 6500);
        } else {
            conf.remove();
        }
    }
})();
</script>
@endpush
