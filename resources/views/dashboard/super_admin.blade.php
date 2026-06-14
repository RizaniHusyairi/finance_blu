@extends('layouts.app')
@section('title', 'Command Center — Super Admin')

@php
    // Formatter rupiah ringkas (Rp 1,2 M / 350 Jt / 12 Rb)
    $rpShort = function ($v) {
        $v = (float) $v;
        $abs = abs($v);
        if ($abs >= 1e12) return 'Rp ' . rtrim(rtrim(number_format($v / 1e12, 2, ',', '.'), '0'), ',') . ' T';
        if ($abs >= 1e9)  return 'Rp ' . rtrim(rtrim(number_format($v / 1e9, 2, ',', '.'), '0'), ',') . ' M';
        if ($abs >= 1e6)  return 'Rp ' . rtrim(rtrim(number_format($v / 1e6, 1, ',', '.'), '0'), ',') . ' Jt';
        if ($abs >= 1e3)  return 'Rp ' . number_format($v / 1e3, 0, ',', '.') . ' Rb';
        return 'Rp ' . number_format($v, 0, ',', '.');
    };
    $maxRole = max(1, optional($usersPerRole->first())->c ?? 1);
@endphp

@push('css')
<style>
/* ============================================================
   SUPER ADMIN — "COMMAND CENTER"
   Aurora hero · glass cards · neon pipeline · live feed
   ============================================================ */
.sa-dash {
    --sa-bg: #0b1020;
    --sa-ink: #0f172a;
    --sa-muted: #64748b;
    --sa-card: #ffffff;
    --sa-border: #e8ecf5;
    --sa-indigo: #6366f1;  --sa-violet: #8b5cf6;  --sa-blue: #3b82f6;
    --sa-cyan: #06b6d4;    --sa-teal: #14b8a6;    --sa-emerald: #10b981;
    --sa-amber: #f59e0b;   --sa-rose: #f43f5e;    --sa-pink: #ec4899;
    --sa-shadow: 0 18px 40px -22px rgba(30, 27, 75, .35);
    --sa-shadow-hover: 0 30px 60px -24px rgba(79, 70, 229, .45);
    --sa-radius: 1.25rem;
    --sa-radius-lg: 1.75rem;
}
body { background: radial-gradient(1200px 600px at 80% -10%, #eef2ff 0%, transparent 55%), #f5f7fc; }

/* ---------- Keyframes ---------- */
@keyframes saAurora   { 0% { background-position: 0% 50% } 50% { background-position: 100% 50% } 100% { background-position: 0% 50% } }
@keyframes saFloat    { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-14px) } }
@keyframes saPulse    { 0%,100% { box-shadow: 0 0 0 0 rgba(99,102,241,.5) } 50% { box-shadow: 0 0 0 12px rgba(99,102,241,0) } }
@keyframes saPulseG   { 0%,100% { box-shadow: 0 0 0 0 rgba(16,185,129,.55) } 50% { box-shadow: 0 0 0 9px rgba(16,185,129,0) } }
@keyframes saShimmer  { 0% { background-position: -200% 0 } 100% { background-position: 200% 0 } }
@keyframes saSpin     { to { transform: rotate(360deg) } }
@keyframes saFlow     { to { stroke-dashoffset: -1000 } }
@keyframes saPop      { 0% { transform: scale(.7); opacity: 0 } 70% { transform: scale(1.06) } 100% { transform: scale(1); opacity: 1 } }
@keyframes saBlink    { 0%,100% { opacity: 1 } 50% { opacity: .25 } }
@keyframes saGlowSweep{ to { transform: rotate(360deg) } }

/* ---------- Reveal on scroll ---------- */
.sa-dash .reveal { opacity: 0; transform: translateY(28px); transition: opacity .7s cubic-bezier(.16,1,.3,1), transform .7s cubic-bezier(.16,1,.3,1); }
.sa-dash .reveal.in { opacity: 1; transform: none; }
@media (prefers-reduced-motion: reduce) {
    .sa-dash .reveal { opacity: 1 !important; transform: none !important; }
    .sa-dash * { animation-duration: .001s !important; animation-iteration-count: 1 !important; }
}

/* ---------- HERO ---------- */
.sa-hero {
    position: relative; overflow: hidden;
    border-radius: var(--sa-radius-lg);
    padding: 2.4rem 2.2rem;
    margin-bottom: 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #0b1020, #1e1b4b 30%, #4338ca 60%, #6d28d9 80%, #0e7490);
    background-size: 360% 360%;
    animation: saAurora 18s ease infinite;
    box-shadow: 0 30px 60px -28px rgba(49,46,129,.7);
}
.sa-hero::before, .sa-hero::after {
    content: ''; position: absolute; border-radius: 50%; pointer-events: none;
    background: radial-gradient(circle, rgba(255,255,255,.18) 0%, transparent 70%);
}
.sa-hero::before { width: 420px; height: 420px; top: -55%; left: -4%; animation: saFloat 10s ease-in-out infinite; }
.sa-hero::after  { width: 320px; height: 320px; bottom: -65%; right: -2%; animation: saFloat 13s ease-in-out infinite reverse; }
.sa-hero .mesh {
    position: absolute; inset: 0; opacity: .16; pointer-events: none;
    background-image: linear-gradient(rgba(255,255,255,.4) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.4) 1px, transparent 1px);
    background-size: 46px 46px;
    mask-image: radial-gradient(ellipse at 25% 0%, #000 5%, transparent 60%);
}
.sa-hero-grid { position: relative; z-index: 2; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1.5rem; }
.sa-badge {
    display: inline-flex; align-items: center; gap: .45rem;
    background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.26);
    backdrop-filter: blur(10px); padding: .4rem 1rem; border-radius: 999px;
    font-weight: 700; font-size: .76rem; letter-spacing: .4px;
}
.sa-badge .dot { width: 8px; height: 8px; border-radius: 50%; background: #34d399; box-shadow: 0 0 0 0 #34d399; animation: saPulseG 2s infinite; }
.sa-hero h2 { font-weight: 800; letter-spacing: -.6px; font-size: clamp(1.5rem, 3vw, 2.1rem); margin: 0; }
.sa-clock {
    font-variant-numeric: tabular-nums; font-weight: 800;
    font-size: clamp(1.8rem, 4vw, 2.6rem); letter-spacing: 1px;
    text-shadow: 0 4px 22px rgba(0,0,0,.35);
}
.sa-pills { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .35rem; justify-content: flex-end; }
.sa-pill {
    display: inline-flex; align-items: center; gap: .4rem;
    background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2);
    padding: .3rem .7rem; border-radius: .7rem; font-size: .72rem; font-weight: 600;
}

/* ---------- KPI cards ---------- */
.sa-kpi {
    position: relative; overflow: hidden;
    background: var(--sa-card); border: 1px solid var(--sa-border);
    border-radius: var(--sa-radius); box-shadow: var(--sa-shadow);
    padding: 1.4rem 1.4rem 1.3rem;
    transition: transform .35s cubic-bezier(.25,.8,.25,1), box-shadow .35s, border-color .35s;
    height: 100%;
}
.sa-kpi:hover { transform: translateY(-6px); box-shadow: var(--sa-shadow-hover); border-color: #c7d2fe; }
.sa-kpi::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--g, linear-gradient(90deg, var(--sa-indigo), var(--sa-violet))); }
.sa-kpi .ic {
    width: 50px; height: 50px; border-radius: 15px; flex-shrink: 0;
    display: grid; place-items: center; font-size: 1.4rem; color: #fff;
    background: var(--g, linear-gradient(135deg, var(--sa-indigo), var(--sa-violet)));
    box-shadow: 0 10px 22px -8px var(--gs, rgba(99,102,241,.6));
    transition: transform .3s cubic-bezier(.34,1.56,.64,1);
}
.sa-kpi:hover .ic { transform: rotate(-8deg) scale(1.08); }
.sa-kpi .lbl { font-size: .74rem; text-transform: uppercase; letter-spacing: .06em; color: var(--sa-muted); font-weight: 700; }
.sa-kpi .val { font-size: clamp(1.35rem, 2.4vw, 1.7rem); font-weight: 800; color: var(--sa-ink); letter-spacing: -.5px; font-variant-numeric: tabular-nums; line-height: 1.15; }
.sa-kpi .sub { font-size: .78rem; color: var(--sa-muted); }
.sa-bar { height: 7px; border-radius: 99px; background: #eef1f8; overflow: hidden; }
.sa-bar > i { display: block; height: 100%; width: 0; border-radius: 99px; background: var(--g, linear-gradient(90deg, var(--sa-indigo), var(--sa-violet))); transition: width 1.4s cubic-bezier(.16,1,.3,1); }

/* ---------- Generic glass card ---------- */
.sa-card {
    background: var(--sa-card); border: 1px solid var(--sa-border);
    border-radius: var(--sa-radius); box-shadow: var(--sa-shadow);
    overflow: hidden; height: 100%;
}
.sa-card-head { padding: 1.1rem 1.4rem; border-bottom: 1px solid var(--sa-border); display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
.sa-card-head h6 { margin: 0; font-weight: 800; color: var(--sa-ink); display: flex; align-items: center; gap: .55rem; }
.sa-card-head .ttl-ic { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; color: #fff; font-size: .95rem; }
.sa-card-body { padding: 1.3rem 1.4rem; }

/* ---------- Pipeline ---------- */
.sa-pipeline { position: relative; display: flex; min-width: 720px; }
.sa-stage { flex: 1; position: relative; text-align: center; padding: 0 .3rem; }
.sa-stage .wire { position: absolute; top: 32px; left: 50%; width: 100%; height: 3px; background: #e7ebf5; z-index: 0; overflow: hidden; }
.sa-stage:last-child .wire { display: none; }
.sa-stage .wire i { position: absolute; inset: 0; background: linear-gradient(90deg, transparent, var(--sa-indigo), var(--sa-violet), transparent); background-size: 200% 100%; animation: saShimmer 2.4s linear infinite; }
.sa-node {
    position: relative; z-index: 1; width: 66px; height: 66px; margin: 0 auto;
    border-radius: 20px; display: grid; place-items: center; color: #fff; font-size: 1.5rem;
    background: var(--g); box-shadow: 0 14px 28px -10px var(--gs);
    transition: transform .3s cubic-bezier(.34,1.56,.64,1);
    animation: saPop .5s cubic-bezier(.16,1,.3,1) both;
}
.sa-stage:hover .sa-node { transform: translateY(-6px) scale(1.07); }
.sa-stage .cnt { margin-top: .55rem; font-size: 1.5rem; font-weight: 800; color: var(--sa-ink); font-variant-numeric: tabular-nums; }
.sa-stage .nm { font-size: .76rem; font-weight: 700; color: var(--sa-muted); text-transform: uppercase; letter-spacing: .04em; }

/* tones */
.tone-indigo  { --g: linear-gradient(135deg,#6366f1,#818cf8); --gs: rgba(99,102,241,.55); }
.tone-violet  { --g: linear-gradient(135deg,#8b5cf6,#a78bfa); --gs: rgba(139,92,246,.55); }
.tone-blue    { --g: linear-gradient(135deg,#3b82f6,#60a5fa); --gs: rgba(59,130,246,.55); }
.tone-cyan    { --g: linear-gradient(135deg,#06b6d4,#22d3ee); --gs: rgba(6,182,212,.55); }
.tone-teal    { --g: linear-gradient(135deg,#14b8a6,#2dd4bf); --gs: rgba(20,184,166,.55); }
.tone-emerald { --g: linear-gradient(135deg,#10b981,#34d399); --gs: rgba(16,185,129,.55); }
.tone-amber   { --g: linear-gradient(135deg,#f59e0b,#fbbf24); --gs: rgba(245,158,11,.55); }
.tone-rose    { --g: linear-gradient(135deg,#f43f5e,#fb7185); --gs: rgba(244,63,94,.55); }

/* ---------- mini stat chips ---------- */
.sa-mini { background: var(--sa-card); border: 1px solid var(--sa-border); border-radius: 1rem; padding: 1rem 1.1rem; box-shadow: var(--sa-shadow); height: 100%; transition: transform .3s, box-shadow .3s; }
.sa-mini:hover { transform: translateY(-4px); box-shadow: var(--sa-shadow-hover); }
.sa-mini .ic2 { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; color: #fff; font-size: 1.1rem; background: var(--g); }
.sa-mini .v { font-size: 1.3rem; font-weight: 800; color: var(--sa-ink); font-variant-numeric: tabular-nums; line-height: 1; }
.sa-mini .l { font-size: .72rem; color: var(--sa-muted); font-weight: 600; }

/* ---------- role bars ---------- */
.sa-role { display: flex; align-items: center; gap: .75rem; padding: .35rem 0; }
.sa-role .nm { width: 38%; font-size: .8rem; font-weight: 600; color: var(--sa-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sa-role .track { flex: 1; height: 10px; border-radius: 99px; background: #eef1f8; overflow: hidden; }
.sa-role .track > i { display: block; height: 100%; width: 0; border-radius: 99px; background: linear-gradient(90deg, var(--sa-indigo), var(--sa-cyan)); transition: width 1.3s cubic-bezier(.16,1,.3,1); }
.sa-role .c { width: 28px; text-align: right; font-weight: 800; color: var(--sa-ink); font-size: .82rem; }

/* ---------- activity feed ---------- */
.sa-feed { position: relative; margin-left: 6px; padding-left: 1.1rem; border-left: 2px dashed #e2e8f5; }
.sa-feed-item { position: relative; padding: .6rem 0; }
.sa-feed-item::before { content: ''; position: absolute; left: calc(-1.1rem - 7px); top: .85rem; width: 12px; height: 12px; border-radius: 50%; background: #fff; border: 2px solid var(--sa-indigo); }
.sa-feed-item:first-child::before { background: var(--sa-indigo); animation: saPulse 2.2s infinite; }
.sa-feed-ava { width: 30px; height: 30px; border-radius: 50%; display: grid; place-items: center; font-size: .72rem; font-weight: 800; color: #fff; background: linear-gradient(135deg,#6366f1,#8b5cf6); flex-shrink: 0; }
.sa-act-tag { font-size: .66rem; font-weight: 800; padding: .12rem .5rem; border-radius: .5rem; letter-spacing: .03em; }

/* ---------- table ---------- */
.sa-table { width: 100%; }
.sa-table thead th { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--sa-muted); font-weight: 800; padding: .7rem 1rem; border-bottom: 1px solid var(--sa-border); }
.sa-table tbody td { padding: .8rem 1rem; border-bottom: 1px solid #f1f4fa; vertical-align: middle; }
.sa-table tbody tr { transition: background .2s; }
.sa-table tbody tr:hover { background: #f8faff; }
.sa-chip { font-size: .7rem; font-weight: 800; padding: .25rem .65rem; border-radius: 999px; white-space: nowrap; }

/* count-up */
[data-cu] { font-variant-numeric: tabular-nums; }
.sa-section-title { font-weight: 800; color: var(--sa-ink); letter-spacing: -.3px; display: flex; align-items: center; gap: .6rem; }
.sa-section-title .glyph { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; color: #fff; font-size: .85rem; }
</style>
@endpush

@section('content')
<div class="sa-dash">
<script>document.documentElement.classList.add('pt-anim');</script>

{{-- ============ HERO ============ --}}
<div class="sa-hero">
    <div class="mesh"></div>
    <div class="sa-hero-grid">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="sa-badge"><span class="dot"></span> SISTEM ONLINE</span>
                <span class="sa-badge"><i class="bi bi-shield-lock-fill"></i> SUPER ADMIN</span>
                <span class="sa-badge"><i class="bi bi-database-fill-check"></i> TA {{ date('Y') }}</span>
            </div>
            <h2>Command Center 🛰️</h2>
            <div class="opacity-75 fw-semibold mt-1">Selamat datang kembali, {{ $user->name }} — pantau seluruh denyut keuangan BLU APTP.</div>
        </div>
        <div class="text-md-end">
            <div class="sa-clock" id="saClock">--:--:--</div>
            <div class="sa-pills">
                <span class="sa-pill"><i class="bi bi-calendar3"></i> {{ $now->translatedFormat('l, d F Y') }}</span>
                <span class="sa-pill"><i class="bi bi-people-fill"></i> {{ $totalUsers }} Pengguna</span>
                <span class="sa-pill"><i class="bi bi-diagram-3-fill"></i> {{ $totalRoles }} Peran</span>
            </div>
        </div>
    </div>
</div>

{{-- ============ KPI CARDS ============ --}}
<div class="row g-3 mb-2">
    <div class="col-xl-3 col-md-6 reveal">
        <div class="sa-kpi" style="--g: linear-gradient(135deg,#6366f1,#8b5cf6); --gs: rgba(99,102,241,.6);">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div><div class="lbl mb-1">Pagu DIPA</div><div class="val">{{ $rpShort($totalPagu) }}</div></div>
                <div class="ic"><i class="bi bi-bank2"></i></div>
            </div>
            <div class="sa-bar mb-2"><i data-w="100" style="--g: linear-gradient(90deg,#6366f1,#8b5cf6);"></i></div>
            <div class="sub">Total pagu seluruh DIPA aktif</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 reveal">
        <div class="sa-kpi" style="--g: linear-gradient(135deg,#10b981,#34d399); --gs: rgba(16,185,129,.6);">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div><div class="lbl mb-1">Realisasi</div><div class="val">{{ $rpShort($totalRealisasi) }}</div></div>
                <div class="ic"><i class="bi bi-graph-up-arrow"></i></div>
            </div>
            <div class="sa-bar mb-2"><i data-w="{{ $persenRealisasi }}" style="--g: linear-gradient(90deg,#10b981,#34d399);"></i></div>
            <div class="sub"><span class="fw-bold text-success">{{ $persenRealisasi }}%</span> terserap dari pagu</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 reveal">
        <div class="sa-kpi" style="--g: linear-gradient(135deg,#f59e0b,#fbbf24); --gs: rgba(245,158,11,.6);">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div><div class="lbl mb-1">Sisa Anggaran</div><div class="val">{{ $rpShort($sisaAnggaran) }}</div></div>
                <div class="ic"><i class="bi bi-wallet2"></i></div>
            </div>
            <div class="sa-bar mb-2"><i data-w="{{ max(0, 100 - $persenRealisasi) }}" style="--g: linear-gradient(90deg,#f59e0b,#fbbf24);"></i></div>
            <div class="sub">{{ round(max(0, 100 - $persenRealisasi), 1) }}% pagu tersisa</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 reveal">
        <div class="sa-kpi" style="--g: linear-gradient(135deg,#06b6d4,#22d3ee); --gs: rgba(6,182,212,.6);">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div><div class="lbl mb-1">Nilai Tagihan</div><div class="val">{{ $rpShort($nilaiTagihan) }}</div></div>
                <div class="ic"><i class="bi bi-receipt-cutoff"></i></div>
            </div>
            <div class="d-flex gap-3 mt-1">
                <span class="sub"><b class="text-dark" data-cu="{{ $totalTagihan }}">0</b> total</span>
                <span class="sub text-success"><b data-cu="{{ $tagihanSelesai }}">0</b> selesai</span>
                <span class="sub text-danger"><b data-cu="{{ $tagihanRevisi }}">0</b> revisi</span>
            </div>
        </div>
    </div>
</div>

{{-- ============ PIPELINE PENCAIRAN ============ --}}
<div class="row g-3 my-1">
    <div class="col-12 reveal">
        <div class="sa-card">
            <div class="sa-card-head">
                <h6><span class="ttl-ic tone-indigo"><i class="bi bi-diagram-3-fill"></i></span> Pipeline Pencairan Dokumen</h6>
                <span class="text-muted small fw-semibold d-none d-md-inline"><i class="bi bi-arrow-left-right me-1"></i>Tagihan → SPP → SPM → NPI → SP2D → Cair</span>
            </div>
            <div class="sa-card-body" style="overflow-x:auto;">
                <div class="sa-pipeline">
                    @foreach($pipeline as $stage)
                        <div class="sa-stage">
                            <div class="wire"><i></i></div>
                            <div class="sa-node tone-{{ $stage['tone'] }}"><i class="bi {{ $stage['icon'] }}"></i></div>
                            <div class="cnt" data-cu="{{ $stage['count'] }}">0</div>
                            <div class="nm">{{ $stage['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============ CHARTS: TREN + STATUS ============ --}}
<div class="row g-3 mb-1 mt-1">
    <div class="col-xl-8 reveal">
        <div class="sa-card">
            <div class="sa-card-head">
                <h6><span class="ttl-ic tone-emerald"><i class="bi bi-activity"></i></span> Tren Realisasi &amp; Tagihan (6 Bulan)</h6>
                <span class="sa-badge" style="background:#eef2ff;color:#4338ca;border-color:#c7d2fe;"><i class="bi bi-lightning-charge-fill"></i> Live</span>
            </div>
            <div class="sa-card-body"><div id="saTren" style="min-height:300px;"></div></div>
        </div>
    </div>
    <div class="col-xl-4 reveal">
        <div class="sa-card">
            <div class="sa-card-head"><h6><span class="ttl-ic tone-violet"><i class="bi bi-pie-chart-fill"></i></span> Status Tagihan</h6></div>
            <div class="sa-card-body">
                @if(count($statusCounts) > 0)
                    <div id="saStatus" style="min-height:240px;"></div>
                @else
                    <div class="text-center py-5 text-muted"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Belum ada tagihan.</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ============ SERAPAN + PAJAK + KAS ============ --}}
<div class="row g-3 mb-1 mt-1">
    <div class="col-xl-5 reveal">
        <div class="sa-card">
            <div class="sa-card-head"><h6><span class="ttl-ic tone-blue"><i class="bi bi-bar-chart-line-fill"></i></span> Serapan per Jenis Belanja</h6></div>
            <div class="sa-card-body"><div id="saSerapan" style="min-height:280px;"></div></div>
        </div>
    </div>
    <div class="col-xl-4 reveal">
        <div class="sa-card">
            <div class="sa-card-head"><h6><span class="ttl-ic tone-rose"><i class="bi bi-receipt"></i></span> Penyetoran Pajak (PPh)</h6></div>
            <div class="sa-card-body text-center">
                <div id="saPajak" style="min-height:200px;"></div>
                <div class="row g-2 mt-1">
                    <div class="col-6">
                        <div class="p-2 rounded-3" style="background:#ecfdf5;">
                            <div class="fw-bold text-success">{{ $rpShort($pajakDisetor) }}</div>
                            <div class="text-muted" style="font-size:.7rem;">Sudah Disetor</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded-3" style="background:#fff1f2;">
                            <div class="fw-bold text-danger">{{ $rpShort($pajakBelum) }}</div>
                            <div class="text-muted" style="font-size:.7rem;">{{ $pajakBelumCount }} belum disetor</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 reveal">
        <div class="d-flex flex-column gap-3 h-100">
            <div class="sa-mini" style="--g: linear-gradient(135deg,#10b981,#34d399);">
                <div class="d-flex align-items-center gap-2 mb-2"><div class="ic2"><i class="bi bi-arrow-down-left-circle-fill"></i></div><div class="l">Kas Masuk (BKU)</div></div>
                <div class="v text-success">{{ $rpShort($kasMasuk) }}</div>
            </div>
            <div class="sa-mini" style="--g: linear-gradient(135deg,#f43f5e,#fb7185);">
                <div class="d-flex align-items-center gap-2 mb-2"><div class="ic2"><i class="bi bi-arrow-up-right-circle-fill"></i></div><div class="l">Kas Keluar (BKU)</div></div>
                <div class="v text-danger">{{ $rpShort($kasKeluar) }}</div>
            </div>
            <div class="sa-mini" style="--g: linear-gradient(135deg,#6366f1,#8b5cf6);">
                <div class="d-flex align-items-center gap-2 mb-2"><div class="ic2"><i class="bi bi-wallet-fill"></i></div><div class="l">Saldo Akhir BKU</div></div>
                <div class="v {{ $saldoBku < 0 ? 'text-danger' : 'text-dark' }}">{{ $rpShort($saldoBku) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ============ MINI STATS ROW ============ --}}
<div class="row g-3 mb-1 mt-1">
    <div class="col-6 col-lg-3 reveal">
        <div class="sa-mini" style="--g: linear-gradient(135deg,#3b82f6,#60a5fa);">
            <div class="d-flex align-items-center gap-3"><div class="ic2"><i class="bi bi-briefcase-fill"></i></div>
                <div><div class="v" data-cu="{{ $totalKontrakAktif }}">0</div><div class="l">Kontrak Aktif</div></div></div>
            <div class="l mt-2 text-truncate">{{ $rpShort($nilaiKontrakAktif) }} nilai</div>
        </div>
    </div>
    <div class="col-6 col-lg-3 reveal">
        <div class="sa-mini" style="--g: linear-gradient(135deg,#14b8a6,#2dd4bf);">
            <div class="d-flex align-items-center gap-3"><div class="ic2"><i class="bi bi-people-fill"></i></div>
                <div><div class="v" data-cu="{{ $totalMitra }}">0</div><div class="l">Mitra / Vendor</div></div></div>
            <div class="l mt-2">Terdaftar di master pihak</div>
        </div>
    </div>
    <div class="col-6 col-lg-3 reveal">
        <div class="sa-mini" style="--g: linear-gradient(135deg,#8b5cf6,#a78bfa);">
            <div class="d-flex align-items-center gap-3"><div class="ic2"><i class="bi bi-hourglass-split"></i></div>
                <div><div class="v" data-cu="{{ $tagihanProses }}">0</div><div class="l">Tagihan Diproses</div></div></div>
            <div class="l mt-2">Dalam alur verifikasi</div>
        </div>
    </div>
    <div class="col-6 col-lg-3 reveal">
        <div class="sa-mini {{ $tagihanRevisi > 0 ? 'border-danger' : '' }}" style="--g: linear-gradient(135deg,#f43f5e,#fb7185); {{ $tagihanRevisi>0 ? 'border:1px solid #fecaca;' : '' }}">
            <div class="d-flex align-items-center gap-3"><div class="ic2"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div><div class="v {{ $tagihanRevisi>0?'text-danger':'' }}" data-cu="{{ $tagihanRevisi }}">0</div><div class="l">Perlu Revisi</div></div></div>
            <div class="l mt-2">{{ $tagihanRevisi>0 ? 'Butuh perhatian' : 'Semua aman' }}</div>
        </div>
    </div>
</div>

{{-- ============ DISTRIBUSI USER + AKTIVITAS ============ --}}
<div class="row g-3 mb-1 mt-1">
    {{-- Distribusi pengguna per peran --}}
    <div class="col-xl-5 reveal">
        <div class="sa-card">
            <div class="sa-card-head">
                <h6><span class="ttl-ic tone-cyan"><i class="bi bi-person-badge-fill"></i></span> Distribusi Pengguna per Peran</h6>
                <span class="sa-chip" style="background:#eef2ff;color:#4338ca;">{{ $totalUsers }} total</span>
            </div>
            <div class="sa-card-body" style="max-height:360px;overflow-y:auto;">
                @foreach($usersPerRole as $r)
                    <div class="sa-role">
                        <div class="nm" title="{{ $r->name }}">{{ $r->name }}</div>
                        <div class="track"><i data-w="{{ round($r->c / $maxRole * 100) }}"></i></div>
                        <div class="c">{{ $r->c }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Feed aktivitas --}}
    <div class="col-xl-7 reveal">
        <div class="sa-card">
            <div class="sa-card-head">
                <h6><span class="ttl-ic tone-amber"><i class="bi bi-broadcast"></i></span> Aktivitas Sistem Terkini</h6>
                <span class="sa-badge" style="background:#fff7ed;color:#b45309;border-color:#fed7aa;"><span class="dot" style="background:#f59e0b;"></span> Realtime</span>
            </div>
            <div class="sa-card-body" style="max-height:360px;overflow-y:auto;">
                @if($recentActivity->isEmpty())
                    <div class="text-center py-5 text-muted"><i class="bi bi-clock-history fs-1 d-block mb-2"></i>Belum ada aktivitas.</div>
                @else
                    <div class="sa-feed">
                        @foreach($recentActivity as $log)
                            @php
                                $nm = $log->user->name ?? 'Sistem';
                                $ini = collect(explode(' ', trim($nm)))->map(fn($w)=>mb_substr($w,0,1))->take(2)->implode('');
                                $doc = class_basename($log->dokumen_type ?? '');
                                $aksi = strtoupper($log->aksi ?? '');
                                $tone = match(true) {
                                    str_contains($aksi,'APPROVE') || str_contains($aksi,'SUBMIT') || str_contains($aksi,'GENERATE') => ['#ecfdf5','#047857'],
                                    str_contains($aksi,'REVIS') || str_contains($aksi,'TOLAK') || str_contains($aksi,'CANCEL') || str_contains($aksi,'KEMBALI') => ['#fff1f2','#be123c'],
                                    str_contains($aksi,'CREATE') || str_contains($aksi,'DIBUAT') || str_contains($aksi,'DIAJUKAN') => ['#eff6ff','#1d4ed8'],
                                    default => ['#f1f5f9','#475569'],
                                };
                            @endphp
                            <div class="sa-feed-item">
                                <div class="d-flex align-items-start gap-2">
                                    <div class="sa-feed-ava">{{ strtoupper($ini) ?: '?' }}</div>
                                    <div class="flex-grow-1" style="min-width:0;">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="fw-bold text-dark" style="font-size:.84rem;">{{ $nm }}</span>
                                            <span class="sa-act-tag" style="background:{{ $tone[0] }};color:{{ $tone[1] }};">{{ str_replace('_',' ',$aksi) ?: 'AKSI' }}</span>
                                            @if($doc)<span class="text-muted" style="font-size:.72rem;"><i class="bi bi-file-earmark me-1"></i>{{ $doc }}</span>@endif
                                        </div>
                                        <div class="text-muted text-truncate" style="font-size:.76rem;">
                                            {{ $log->catatan ? \Illuminate\Support\Str::limit($log->catatan, 70) : ($log->role_saat_itu ?? '-') }}
                                        </div>
                                    </div>
                                    <div class="text-muted text-nowrap" style="font-size:.7rem;">{{ optional($log->created_at)->diffForHumans(null, true) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ============ TABEL: JATUH TEMPO + TAGIHAN TERBARU ============ --}}
<div class="row g-3 mb-1 mt-1">
    <div class="col-xl-6 reveal">
        <div class="sa-card">
            <div class="sa-card-head">
                <h6><span class="ttl-ic tone-rose"><i class="bi bi-alarm-fill"></i></span> Kontrak Mendekati Jatuh Tempo</h6>
                <a href="{{ route('contracts.index') }}" class="btn btn-sm btn-light rounded-pill border fw-semibold">Semua</a>
            </div>
            <div class="sa-card-body p-0">
                <div class="table-responsive">
                    <table class="sa-table">
                        <thead><tr><th>SPK &amp; Pekerjaan</th><th>Vendor</th><th>Sisa</th><th></th></tr></thead>
                        <tbody>
                            @forelse($jatuhTempo as $k)
                                @php
                                    $end = \Carbon\Carbon::parse($k->tanggal_selesai);
                                    $diff = (int) round(now()->diffInDays($end, false));
                                    $late = $diff < 0;
                                @endphp
                                <tr>
                                    <td><div class="fw-bold text-dark" style="font-size:.84rem;">{{ $k->nomor_spk }}</div>
                                        <div class="text-muted" style="font-size:.74rem;">{{ Str::limit($k->nama_pekerjaan, 32) }}</div></td>
                                    <td style="font-size:.8rem;">{{ Str::limit($k->vendor->nama_pihak ?? '-', 20) }}</td>
                                    <td>
                                        @if($late)<span class="sa-chip" style="background:#fee2e2;color:#b91c1c;">Telat {{ abs($diff) }}h</span>
                                        @else<span class="sa-chip" style="background:#fef3c7;color:#92400e;">{{ $diff }} hari</span>@endif
                                    </td>
                                    <td><a href="{{ route('contracts.show', $k->id) }}" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-eye"></i></a></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4"><i class="bi bi-shield-check me-1"></i>Tidak ada kontrak mendesak.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6 reveal">
        <div class="sa-card">
            <div class="sa-card-head">
                <h6><span class="ttl-ic tone-indigo"><i class="bi bi-receipt-cutoff"></i></span> Tagihan Terbaru</h6>
                <a href="{{ route('proses-tagihan.index') }}" class="btn btn-sm btn-light rounded-pill border fw-semibold">Proses</a>
            </div>
            <div class="sa-card-body p-0">
                <div class="table-responsive">
                    <table class="sa-table">
                        <thead><tr><th>No. Tagihan</th><th>Tipe</th><th>Status</th><th class="text-end">Netto</th></tr></thead>
                        <tbody>
                            @forelse($recentTagihan as $t)
                                @php
                                    $st = $t->status;
                                    $tn = match(true) {
                                        str_contains($st,'REVISI')||str_contains($st,'TOLAK') => ['#fee2e2','#b91c1c'],
                                        str_contains($st,'SELESAI') => ['#dcfce7','#15803d'],
                                        str_contains($st,'PROSES')||str_contains($st,'PENDING') => ['#fef3c7','#92400e'],
                                        default => ['#e0e7ff','#4338ca'],
                                    };
                                    $tipeTone = match($t->tipe_tagihan) {
                                        'KONTRAK' => '#4338ca', 'HONORARIUM' => '#0e7490', 'PERJALDIN' => '#b45309', default => '#475569',
                                    };
                                @endphp
                                <tr>
                                    <td><a href="{{ route('proses-tagihan.show', $t->id) }}" class="fw-bold text-decoration-none" style="font-size:.82rem;color:#4338ca;">{{ $t->nomor_tagihan }}</a></td>
                                    <td><span class="sa-chip" style="background:#f1f5f9;color:{{ $tipeTone }};">{{ $t->tipe_tagihan }}</span></td>
                                    <td><span class="sa-chip" style="background:{{ $tn[0] }};color:{{ $tn[1] }};">{{ str_replace('_',' ',$st) }}</span></td>
                                    <td class="text-end fw-bold" style="font-size:.82rem;">Rp {{ number_format($t->total_netto, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada tagihan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============ KONTRAK AKTIF ============ --}}
<div class="row g-3 mb-2 mt-1">
    <div class="col-12 reveal">
        <div class="sa-card">
            <div class="sa-card-head">
                <h6><span class="ttl-ic tone-teal"><i class="bi bi-file-earmark-text-fill"></i></span> Kontrak Aktif Terbaru</h6>
                <a href="{{ route('contracts.index') }}" class="btn btn-sm btn-light rounded-pill border fw-semibold">Lihat Semua</a>
            </div>
            <div class="sa-card-body p-0">
                <div class="table-responsive">
                    <table class="sa-table">
                        <thead><tr><th>No. SPK</th><th>Pekerjaan</th><th>Vendor</th><th>Masa Kontrak</th><th class="text-end">Nilai</th><th></th></tr></thead>
                        <tbody>
                            @forelse($activeContracts as $c)
                                <tr>
                                    <td class="fw-bold" style="font-size:.82rem;">{{ $c->nomor_spk }}</td>
                                    <td style="font-size:.82rem;">{{ Str::limit($c->nama_pekerjaan, 36) }}</td>
                                    <td style="font-size:.8rem;">{{ Str::limit($c->vendor->nama_pihak ?? '-', 22) }}</td>
                                    <td style="font-size:.76rem;" class="text-muted">{{ \Carbon\Carbon::parse($c->tanggal_mulai)->format('d/m/y') }} – {{ \Carbon\Carbon::parse($c->tanggal_selesai)->format('d/m/y') }}</td>
                                    <td class="text-end fw-bold" style="font-size:.82rem;">Rp {{ number_format($c->nilai_total_kontrak, 0, ',', '.') }}</td>
                                    <td><a href="{{ route('contracts.show', $c->id) }}" class="btn btn-sm btn-outline-primary rounded-pill">Detail</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada kontrak aktif.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</div>{{-- /.sa-dash --}}
@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
(function () {
    'use strict';
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- Live clock ---------- */
    var clock = document.getElementById('saClock');
    function tick() {
        if (!clock) return;
        var d = new Date();
        var p = n => String(n).padStart(2, '0');
        clock.textContent = p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
    }
    tick(); setInterval(tick, 1000);

    /* ---------- Count-up ---------- */
    var fmt = new Intl.NumberFormat('id-ID');
    function countUp(el) {
        var target = parseFloat(el.getAttribute('data-cu')) || 0;
        if (reduced || target <= 0) { el.textContent = fmt.format(target); return; }
        var dur = 1200, start = null;
        function step(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / dur, 1);
            var e = 1 - Math.pow(1 - p, 3);
            el.textContent = fmt.format(Math.round(target * e));
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    /* ---------- Reveal + trigger bars/counters ---------- */
    function fillBars(scope) {
        (scope || document).querySelectorAll('.sa-bar > i[data-w], .sa-role .track > i[data-w]').forEach(function (b) {
            if (b.dataset.done) return; b.dataset.done = '1';
            requestAnimationFrame(function () { b.style.width = (b.getAttribute('data-w') || 0) + '%'; });
        });
    }
    var revealEls = document.querySelectorAll('.sa-dash .reveal');
    if ('IntersectionObserver' in window && !reduced) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) {
                    e.target.classList.add('in');
                    e.target.querySelectorAll('[data-cu]').forEach(countUp);
                    fillBars(e.target);
                    io.unobserve(e.target);
                }
            });
        }, { threshold: 0.12 });
        revealEls.forEach(function (el) { io.observe(el); });
    } else {
        revealEls.forEach(function (el) { el.classList.add('in'); });
        document.querySelectorAll('[data-cu]').forEach(countUp);
        fillBars(document);
    }
    // counters di pipeline / luar reveal langsung dipicu juga setelah sedikit delay
    setTimeout(function () { document.querySelectorAll('.sa-node ~ [data-cu]').forEach(countUp); }, 400);

    /* ---------- Charts ---------- */
    if (typeof ApexCharts === 'undefined') return;
    var rpAxis = function (v) {
        if (v >= 1e9) return (v / 1e9).toFixed(1) + ' M';
        if (v >= 1e6) return (v / 1e6).toFixed(0) + ' Jt';
        if (v >= 1e3) return (v / 1e3).toFixed(0) + ' Rb';
        return v;
    };
    var rpFull = function (v) { return 'Rp ' + Number(v).toLocaleString('id-ID'); };

    // Tren area
    new ApexCharts(document.querySelector('#saTren'), {
        series: [
            { name: 'Realisasi', type: 'area', data: @json($trenRealisasi) },
            { name: 'Tagihan Baru', type: 'line', data: @json($trenTagihan) }
        ],
        chart: { height: 300, type: 'line', toolbar: { show: false }, fontFamily: 'inherit', animations: { enabled: !reduced, easing: 'easeinout', speed: 900 } },
        stroke: { width: [0, 3], curve: 'smooth' },
        fill: { type: ['gradient', 'solid'], gradient: { shadeIntensity: 1, opacityFrom: .45, opacityTo: .05, stops: [0, 90] } },
        colors: ['#10b981', '#6366f1'],
        dataLabels: { enabled: false },
        markers: { size: [0, 5], colors: ['#6366f1'], strokeWidth: 2, hover: { size: 7 } },
        xaxis: { categories: @json($trenLabels), labels: { style: { fontSize: '12px', fontWeight: 600 } } },
        yaxis: [
            { labels: { formatter: rpAxis, style: { colors: '#10b981' } } },
            { opposite: true, labels: { formatter: function (v) { return Math.round(v); }, style: { colors: '#6366f1' } } }
        ],
        legend: { position: 'top', horizontalAlign: 'right' },
        grid: { borderColor: '#eef1f8', strokeDashArray: 4 },
        tooltip: { shared: true, intersect: false, y: { formatter: function (v, o) { return o.seriesIndex === 0 ? rpFull(v) : v + ' tagihan'; } } }
    }).render();

    // Status donut
    @if(count($statusCounts) > 0)
    (function () {
        var labels = @json(array_keys($statusCounts)).map(function (s) { return s.replace(/_/g, ' '); });
        var values = @json(array_values($statusCounts));
        var colors = @json(array_keys($statusCounts)).map(function (s) {
            if (s.includes('REVISI') || s.includes('TOLAK')) return '#f43f5e';
            if (s.includes('SELESAI') || s.includes('CAIR')) return '#10b981';
            if (s.includes('PROSES') || s.includes('PENDING')) return '#f59e0b';
            if (s.includes('DRAFT')) return '#94a3b8';
            return '#6366f1';
        });
        new ApexCharts(document.querySelector('#saStatus'), {
            series: values, labels: labels, colors: colors,
            chart: { type: 'donut', height: 260, fontFamily: 'inherit', animations: { enabled: !reduced, speed: 900 } },
            plotOptions: { pie: { donut: { size: '70%', labels: { show: true, total: { show: true, label: 'Total', fontWeight: 700, formatter: function (w) { return w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0); } } } } } },
            legend: { position: 'bottom', fontSize: '11px' },
            dataLabels: { enabled: false },
            stroke: { width: 2 },
            tooltip: { y: { formatter: function (v) { return v + ' tagihan'; } } }
        }).render();
    })();
    @endif

    // Serapan bar
    new ApexCharts(document.querySelector('#saSerapan'), {
        series: [{ name: 'Pagu', data: @json($chartBarPagu) }, { name: 'Realisasi', data: @json($chartBarRealisasi) }],
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'inherit', animations: { enabled: !reduced, speed: 900 } },
        plotOptions: { bar: { columnWidth: '52%', borderRadius: 7, borderRadiusApplication: 'end' } },
        colors: ['#c7d2fe', '#6366f1'],
        dataLabels: { enabled: false },
        xaxis: { categories: @json($chartBarLabels), labels: { style: { fontSize: '11px', fontWeight: 600 } } },
        yaxis: { labels: { formatter: rpAxis } },
        legend: { position: 'top', horizontalAlign: 'right' },
        grid: { borderColor: '#eef1f8', strokeDashArray: 4 },
        tooltip: { y: { formatter: rpFull } }
    }).render();

    // Pajak radial
    new ApexCharts(document.querySelector('#saPajak'), {
        series: [{{ $persenPajak }}],
        chart: { type: 'radialBar', height: 220, fontFamily: 'inherit', animations: { enabled: !reduced, speed: 1100 } },
        plotOptions: { radialBar: {
            hollow: { size: '62%' },
            track: { background: '#fee2e2' },
            dataLabels: {
                name: { offsetY: 22, color: '#64748b', fontSize: '12px' },
                value: { offsetY: -12, fontSize: '26px', fontWeight: 800, color: '#0f172a', formatter: function (v) { return v + '%'; } }
            }
        } },
        labels: ['Tersetor'],
        fill: { type: 'gradient', gradient: { shade: 'dark', type: 'horizontal', gradientToColors: ['#10b981'], stops: [0, 100] } },
        colors: ['#f43f5e'],
        stroke: { lineCap: 'round' }
    }).render();
})();
</script>
@endpush
