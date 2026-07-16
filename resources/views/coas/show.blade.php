@extends('layouts.app')

@section('title', 'Detail COA: ' . ($coa->kode_mak_lengkap ?: $coa->nama_akun))

@php
    $pagu = (float) $statistics['total_nilai_pagu'];
    $realisasi = (float) $statistics['total_realisasi'];
    $sisa = (float) $statistics['total_sisa_pagu'];
    $persenRealisasi = $pagu > 0 ? round($realisasi / $pagu * 100, 1) : 0;
    $persenSisa = $pagu > 0 ? max(0, round(100 - $persenRealisasi, 1)) : 0;
    $overBudget = $sisa < 0;
@endphp

@push('css')
<style>
/* ============================================================
   DETAIL COA — hero aurora · stat tiles · struktur kode visual
   ============================================================ */
.coa-page { --cp-indigo:#4f46e5; --cp-violet:#8b5cf6; --cp-cyan:#06b6d4; --cp-emerald:#10b981;
    --cp-amber:#f59e0b; --cp-rose:#e11d48; --cp-ink:#0f172a; --cp-muted:#64748b;
    --cp-border:#e8ecf5; --cp-radius:1.1rem;
    --cp-shadow:0 16px 36px -20px rgba(30,27,75,.25);
    --cp-shadow-hover:0 26px 50px -22px rgba(79,70,229,.4); }

@keyframes cpAurora { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
@keyframes cpFloat  { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-11px)} }
@keyframes cpRise   { from{opacity:0; transform:translateY(18px)} to{opacity:1; transform:none} }
@keyframes cpRowIn  { from{opacity:0; transform:translateY(9px)} to{opacity:1; transform:none} }
@keyframes cpSheen  { 0%,55%{left:-70%} 85%,100%{left:140%} }
@keyframes cpPulse  { 0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.5)} 50%{box-shadow:0 0 0 8px rgba(16,185,129,0)} }
@media (prefers-reduced-motion: reduce) {
    .coa-page * { animation-duration:.001s !important; animation-iteration-count:1 !important; transition-duration:.001s !important; }
}

/* ---------- HERO ---------- */
.cp-hero { position:relative; overflow:hidden; border-radius:1.4rem; padding:1.8rem 2rem;
    margin-bottom:1.4rem; color:#fff;
    background:linear-gradient(125deg,#0b1020,#1e1b4b 32%,#4338ca 64%,#7c3aed 84%,#0e7490);
    background-size:340% 340%; animation:cpAurora 18s ease infinite;
    box-shadow:0 26px 52px -24px rgba(49,46,129,.6); }
.cp-hero::before, .cp-hero::after { content:''; position:absolute; border-radius:50%; pointer-events:none;
    background:radial-gradient(circle, rgba(255,255,255,.15) 0%, transparent 70%); }
.cp-hero::before { width:340px; height:340px; top:-55%; left:-4%; animation:cpFloat 10s ease-in-out infinite; }
.cp-hero::after  { width:250px; height:250px; bottom:-58%; right:-3%; animation:cpFloat 13s ease-in-out infinite reverse; }
.cp-hero .mesh { position:absolute; inset:0; opacity:.14; pointer-events:none;
    background-image:linear-gradient(rgba(255,255,255,.4) 1px, transparent 1px),
                     linear-gradient(90deg, rgba(255,255,255,.4) 1px, transparent 1px);
    background-size:42px 42px; mask-image:radial-gradient(ellipse at 22% 0%, #000 5%, transparent 62%); }
.cp-hero-grid { position:relative; z-index:2; display:flex; flex-wrap:wrap; justify-content:space-between;
    align-items:center; gap:1.2rem; }
.cp-chip { display:inline-flex; align-items:center; gap:.4rem; background:rgba(255,255,255,.13);
    border:1px solid rgba(255,255,255,.25); backdrop-filter:blur(8px); padding:.3rem .85rem;
    border-radius:999px; font-weight:700; font-size:.72rem; letter-spacing:.4px; color:#fff; }
.cp-chip.on { background:rgba(16,185,129,.28); border-color:rgba(110,231,183,.55); }
.cp-chip.on .dot { width:8px; height:8px; border-radius:50%; background:#34d399; animation:cpPulse 2s infinite; }
.cp-chip.off { background:rgba(148,163,184,.25); border-color:rgba(203,213,225,.4); }
.cp-kode { font-family:SFMono-Regular,Menlo,Consolas,monospace; font-weight:800; color:#fff;
    font-size:clamp(1.15rem,2.6vw,1.75rem); letter-spacing:-.02em; cursor:pointer;
    display:inline-flex; align-items:center; gap:.6rem; overflow-wrap:anywhere;
    transition:opacity .2s ease; }
.cp-kode:hover { opacity:.85; }
.cp-kode .bi-copy { font-size:.9rem; opacity:.6; }
.cp-nama { color:rgba(255,255,255,.88); font-weight:600; font-size:.95rem; margin-top:.25rem; overflow-wrap:anywhere; }
.cp-btn { display:inline-flex; align-items:center; gap:.45rem; border-radius:999px; font-weight:700;
    font-size:.82rem; padding:.5rem 1.15rem; border:1px solid rgba(255,255,255,.35); color:#fff;
    background:rgba(255,255,255,.12); backdrop-filter:blur(8px); text-decoration:none;
    transition:transform .2s ease, background .2s ease; }
.cp-btn:hover { color:#fff; background:rgba(255,255,255,.24); transform:translateY(-2px); }
.cp-btn.solid { background:#fff; color:#4338ca; border-color:transparent; box-shadow:0 10px 24px -10px rgba(0,0,0,.5); }
.cp-btn.solid:hover { color:#4338ca; }
.cp-btn.danger { background:rgba(225,29,72,.85); border-color:transparent; }
.cp-btn.danger:hover { background:#e11d48; }

/* ---------- STAT TILES ---------- */
.cp-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:.8rem; }
.cp-stat { position:relative; overflow:hidden; background:#fff; border:1px solid var(--cp-border);
    border-radius:var(--cp-radius); box-shadow:var(--cp-shadow); padding:1rem 1.1rem;
    transition:transform .3s cubic-bezier(.25,.8,.25,1), box-shadow .3s, border-color .3s;
    animation:cpRise .5s ease both; animation-delay:var(--d, 0s); }
.cp-stat:hover { transform:translateY(-5px); box-shadow:var(--cp-shadow-hover); border-color:#c7d2fe; }
.cp-stat .ic { width:38px; height:38px; border-radius:11px; display:grid; place-items:center;
    font-size:1rem; color:var(--t,#4f46e5); background:var(--ts,#eef2ff); margin-bottom:.6rem;
    transition:transform .25s cubic-bezier(.34,1.56,.64,1); }
.cp-stat:hover .ic { transform:scale(1.12) rotate(-5deg); }
.cp-stat .lbl { font-size:.64rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#94a3b8; }
.cp-stat .val { font-size:1.15rem; font-weight:800; color:var(--cp-ink); letter-spacing:-.01em;
    font-variant-numeric:tabular-nums; line-height:1.2; overflow-wrap:anywhere; }
.cp-stat .sub { font-size:.7rem; color:var(--cp-muted); font-weight:600; }
.cp-stat.sisa-ok { border-color:rgba(16,185,129,.35); background:linear-gradient(135deg, rgba(16,185,129,.08), rgba(16,185,129,.015)); }
.cp-stat.sisa-ok .val { color:#047857; }
.cp-stat.sisa-ok::after { content:''; position:absolute; top:0; bottom:0; width:38%; left:-55%;
    background:linear-gradient(100deg, transparent, rgba(255,255,255,.6), transparent);
    transform:skewX(-18deg); animation:cpSheen 3.8s ease-in-out 1.2s infinite; }
.cp-stat.sisa-minus { border-color:rgba(225,29,72,.35); background:linear-gradient(135deg, rgba(225,29,72,.07), transparent); }
.cp-stat.sisa-minus .val { color:#be123c; }

/* ---------- KARTU UMUM ---------- */
.cp-card { background:#fff; border:1px solid var(--cp-border); border-radius:var(--cp-radius);
    box-shadow:var(--cp-shadow); overflow:hidden; animation:cpRise .55s ease both; animation-delay:var(--d, 0s); }
.cp-card-head { display:flex; align-items:center; gap:.75rem; padding:1.05rem 1.3rem .85rem; flex-wrap:wrap; }
.cp-card-ic { width:38px; height:38px; border-radius:11px; display:grid; place-items:center;
    color:#fff; font-size:1rem; background:linear-gradient(135deg, var(--t,#4f46e5), var(--t2,#818cf8));
    box-shadow:0 8px 18px -8px var(--t,#4f46e5); flex-shrink:0; }
.cp-card-title { font-weight:800; margin:0; letter-spacing:-.01em; color:var(--cp-ink); }
.cp-card-sub { font-size:.74rem; color:var(--cp-muted); font-weight:600; }

/* ---------- BAR REALISASI ---------- */
.cp-bar { display:flex; height:14px; border-radius:999px; overflow:hidden; background:#eef1f7;
    box-shadow:inset 0 1px 2px rgba(15,23,42,.06); }
.cp-bar span { width:0; transition:width 1.3s cubic-bezier(.16,1,.3,1) .3s; }
.cp-bar .seg-realisasi { background:linear-gradient(90deg,#4f46e5,#818cf8); }
.cp-bar .seg-sisa { background:linear-gradient(90deg,#10b981,#34d399); }
.cp-bar.loaded span { width:var(--w); }
.cp-legend { display:flex; flex-wrap:wrap; gap:1rem; font-size:.74rem; color:var(--cp-muted); margin-top:.5rem; font-weight:600; }
.cp-legend .dot { display:inline-block; width:9px; height:9px; border-radius:50%; margin-right:.3rem; }

/* ---------- STRUKTUR KODE ---------- */
.cp-segments { display:flex; flex-wrap:wrap; align-items:stretch; gap:.45rem; }
.cp-seg { min-width:86px; flex:1 1 96px; text-align:center; border:1px solid var(--cp-border);
    border-radius:.85rem; padding:.65rem .5rem; background:linear-gradient(180deg,#fff,#fafbff);
    transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    animation:cpRowIn .45s ease both; animation-delay:var(--d, 0s); }
.cp-seg:hover { transform:translateY(-3px); border-color:#c7d2fe; box-shadow:0 10px 20px -14px rgba(79,70,229,.5); }
.cp-seg .seg-val { font-family:SFMono-Regular,Menlo,Consolas,monospace; font-weight:800; color:var(--cp-ink);
    font-size:.95rem; letter-spacing:-.01em; }
.cp-seg .seg-lbl { font-size:.6rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em;
    color:#94a3b8; margin-top:.2rem; }
.cp-seg.akun { border-color:#a5b4fc; background:linear-gradient(180deg,#eef2ff,#e0e7ff66); }
.cp-seg.akun .seg-val { color:#4338ca; font-size:1.05rem; }
.cp-seg.akun .seg-lbl { color:#4f46e5; }
.cp-fullcode { font-family:SFMono-Regular,Menlo,Consolas,monospace; font-weight:700; font-size:.85rem;
    color:#4338ca; background:#eef2ff; border:1px dashed #c7d2fe; border-radius:.75rem;
    padding:.6rem .9rem; overflow-wrap:anywhere; }

/* ---------- TABEL ---------- */
.cp-table { margin-bottom:0; }
.cp-table thead th { background:#f8faff !important; border-bottom:1px solid var(--cp-border) !important;
    font-size:.68rem; font-weight:800; letter-spacing:.09em; text-transform:uppercase; color:#7c8db5 !important; }
.cp-table tbody tr { animation:cpRowIn .4s ease both; transition:background .2s ease, box-shadow .2s ease; }
.cp-table tbody tr:nth-child(-n+10) { animation-delay:calc(var(--i, 0) * 45ms); }
.cp-table tbody tr:hover { background:#f5f7ff; box-shadow:inset 3px 0 0 var(--cp-indigo); }
.cp-table tbody td { font-size:.84rem; border-color:rgba(124,141,181,.1) !important; }
.cp-num { font-variant-numeric:tabular-nums; }

.cp-badge { display:inline-flex; align-items:center; gap:.35rem; font-size:.68rem; font-weight:700;
    padding:.28rem .65rem; border-radius:999px; letter-spacing:.03em; white-space:nowrap; }
.cp-badge-success { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
.cp-badge-warning { background:#fffbeb; color:#b45309; border:1px solid #fde68a; }
.cp-badge-info    { background:#ecfeff; color:#0e7490; border:1px solid #a5f3fc; }
.cp-badge-danger  { background:#fff1f2; color:#be123c; border:1px solid #fecdd3; }
.cp-badge-neutral { background:#f8fafc; color:#64748b; border:1px solid #e2e8f0; }
.cp-badge-indigo  { background:#eef2ff; color:#4338ca; border:1px solid #e0e7ff; }

.cp-act { display:inline-flex; align-items:center; gap:.35rem; padding:.35rem .85rem; border-radius:999px;
    border:1px solid var(--cp-border); background:#fff; color:#64748b; font-size:.74rem; font-weight:700;
    text-decoration:none; transition:all .22s ease; white-space:nowrap; }
.cp-act:hover { background:#4f46e5; border-color:#4f46e5; color:#fff; transform:translateY(-2px);
    box-shadow:0 8px 18px -8px rgba(79,70,229,.6); }

.cp-empty { padding:3rem 1rem; text-align:center; color:var(--cp-muted); }
.cp-empty .glyph { width:66px; height:66px; margin:0 auto .9rem; border-radius:20px; display:grid;
    place-items:center; font-size:1.7rem; color:#a5b4fc; background:linear-gradient(135deg,#eef2ff,#e0e7ff);
    animation:cpFloat 5s ease-in-out infinite; }
</style>
@endpush

@section('content')
<div class="coa-page">

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success text-white alert-dismissible fade show shadow-sm">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger text-white alert-dismissible fade show shadow-sm">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ════════ HERO ════════ --}}
    <div class="cp-hero">
        <div class="mesh"></div>
        <div class="cp-hero-grid">
            <div style="max-width: 760px;">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    @if($coa->status_aktif)
                        <span class="cp-chip on"><span class="dot"></span> COA AKTIF</span>
                    @else
                        <span class="cp-chip off"><i class="bi bi-pause-circle"></i> COA NONAKTIF</span>
                    @endif
                    @if($coa->jenis_akun)
                        <span class="cp-chip"><i class="bi bi-bookmark-fill"></i> {{ $coa->jenis_akun }}</span>
                    @endif
                    <span class="cp-chip"><i class="bi bi-folder2-open"></i> {{ number_format($statistics['jumlah_dipa']) }} DIPA</span>
                </div>
                <div class="cp-kode" id="cpKodeCopy" data-copy="{{ $coa->kode_mak_lengkap ?: $coa->kd_akun }}" title="Klik untuk menyalin kode">
                    <span id="cpKodeText">{{ $coa->kode_mak_lengkap ?: ($coa->kd_akun ?: '-') }}</span>
                    <i class="bi bi-copy"></i>
                </div>
                <div class="cp-nama"><i class="bi bi-tag me-1"></i>{{ $coa->nama_akun }}</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('coas.index') }}" class="cp-btn"><i class="bi bi-arrow-left"></i> Kembali</a>
                <a href="{{ route('coas.edit', $coa) }}" class="cp-btn solid"><i class="bi bi-pencil"></i> Edit</a>
                @if($statistics['jumlah_item_dipa'] === 0)
                    <form action="{{ route('coas.destroy', $coa) }}" method="POST" onsubmit="return confirm('Hapus COA ini secara permanen?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="cp-btn danger border-0"><i class="bi bi-trash"></i> Hapus</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- ════════ STAT TILES ════════ --}}
    <div class="cp-stats mb-4">
        <div class="cp-stat" style="--d:.02s; --t:#4f46e5; --ts:#eef2ff;">
            <div class="ic"><i class="bi bi-receipt"></i></div>
            <div class="lbl">Jumlah Tagihan</div>
            <div class="val cp-countup" data-target="{{ $billingStatistics['jumlah_tagihan'] }}">0</div>
            <div class="sub">{{ number_format($billingStatistics['jumlah_pengeluaran']) }} keluar · {{ number_format($billingStatistics['jumlah_penerimaan']) }} masuk</div>
        </div>
        <div class="cp-stat" style="--d:.07s; --t:#7c3aed; --ts:#f5f3ff;">
            <div class="ic"><i class="bi bi-list-ol"></i></div>
            <div class="lbl">Item Anggaran DIPA</div>
            <div class="val cp-countup" data-target="{{ $statistics['jumlah_item_dipa'] }}">0</div>
            <div class="sub">pada {{ number_format($statistics['jumlah_dipa']) }} DIPA</div>
        </div>
        <div class="cp-stat" style="--d:.12s; --t:#0891b2; --ts:#ecfeff;">
            <div class="ic"><i class="bi bi-bank2"></i></div>
            <div class="lbl">Total Pagu</div>
            <div class="val">Rp <span class="cp-countup" data-target="{{ (int) $pagu }}">0</span></div>
        </div>
        <div class="cp-stat" style="--d:.17s; --t:#4f46e5; --ts:#eef2ff;">
            <div class="ic"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="lbl">Total Realisasi</div>
            <div class="val" style="color:#4338ca;">Rp <span class="cp-countup" data-target="{{ (int) $realisasi }}">0</span></div>
            <div class="sub">{{ $persenRealisasi }}% dari pagu</div>
        </div>
        <div class="cp-stat {{ $overBudget ? 'sisa-minus' : 'sisa-ok' }}" style="--d:.22s; --t:{{ $overBudget ? '#e11d48' : '#047857' }}; --ts:{{ $overBudget ? '#fff1f2' : '#ecfdf5' }};">
            <div class="ic"><i class="bi {{ $overBudget ? 'bi-exclamation-octagon' : 'bi-wallet2' }}"></i></div>
            <div class="lbl" style="color:{{ $overBudget ? '#be123c' : '#059669' }};">Sisa Pagu</div>
            <div class="val">{{ $overBudget ? '−' : '' }}Rp <span class="cp-countup" data-target="{{ (int) abs($sisa) }}">0</span></div>
            <div class="sub">{{ $overBudget ? 'melebihi pagu — perlu revisi anggaran' : $persenSisa . '% pagu masih tersedia' }}</div>
        </div>
    </div>

    {{-- ════════ SERAPAN ANGGARAN ════════ --}}
    <div class="cp-card mb-4" style="--d:.1s;">
        <div class="cp-card-head">
            <span class="cp-card-ic" style="--t:#4f46e5; --t2:#818cf8;"><i class="bi bi-bar-chart-fill"></i></span>
            <div>
                <h6 class="cp-card-title">Serapan Anggaran COA</h6>
                <div class="cp-card-sub">Perbandingan realisasi terhadap total pagu seluruh DIPA yang memakai COA ini.</div>
            </div>
            <span class="cp-badge {{ $overBudget ? 'cp-badge-danger' : ($persenRealisasi >= 90 ? 'cp-badge-warning' : 'cp-badge-indigo') }} ms-auto">
                <i class="bi bi-speedometer2"></i> Terserap {{ $persenRealisasi }}%
            </span>
        </div>
        <div class="px-4 pb-4 pt-1">
            @if($pagu > 0)
                <div class="cp-bar" id="cpBar" role="img" aria-label="Realisasi {{ $persenRealisasi }}%, sisa {{ $persenSisa }}%">
                    <span class="seg-realisasi" style="--w: {{ min($persenRealisasi, 100) }}%;"></span>
                    <span class="seg-sisa" style="--w: {{ $persenSisa }}%;"></span>
                </div>
                <div class="cp-legend">
                    <span><span class="dot" style="background:#4f46e5;"></span>Realisasi · Rp {{ number_format($realisasi, 0, ',', '.') }} ({{ $persenRealisasi }}%)</span>
                    @if($overBudget)
                        <span class="text-danger"><span class="dot" style="background:#e11d48;"></span>Melebihi pagu Rp {{ number_format(abs($sisa), 0, ',', '.') }}</span>
                    @else
                        <span><span class="dot" style="background:#10b981;"></span>Sisa tersedia · Rp {{ number_format($sisa, 0, ',', '.') }} ({{ $persenSisa }}%)</span>
                    @endif
                </div>
            @else
                <div class="text-muted small"><i class="bi bi-info-circle me-1"></i>COA ini belum memiliki pagu pada DIPA aktif mana pun, sehingga serapan belum dapat dihitung.</div>
            @endif
        </div>
    </div>

    {{-- ════════ STRUKTUR KODE ════════ --}}
    <div class="cp-card mb-4" style="--d:.14s;">
        <div class="cp-card-head">
            <span class="cp-card-ic" style="--t:#7c3aed; --t2:#a855f7;"><i class="bi bi-diagram-2-fill"></i></span>
            <div>
                <h6 class="cp-card-title">Struktur Kode COA</h6>
                <div class="cp-card-sub">Kode MAK tersusun dari segmen Program → Kegiatan → Output → Akun → Item. Segmen <strong>Kode Akun</strong> menentukan jenis belanja.</div>
            </div>
        </div>
        <div class="px-4 pb-4 pt-1">
            <div class="cp-segments mb-3">
                @foreach([
                    ['Program', $coa->kd_program], ['Kegiatan', $coa->kd_giat], ['Output', $coa->kd_output],
                    ['Suboutput', $coa->kd_suboutput], ['Komponen', $coa->kd_komponen], ['Subkomponen', $coa->kd_subkomponen],
                    ['Kode Akun', $coa->kd_akun, true], ['Item', $coa->kd_item],
                ] as $i => $seg)
                    <div class="cp-seg {{ ($seg[2] ?? false) ? 'akun' : '' }}" style="--d:{{ .05 + $i * .05 }}s;" title="{{ $seg[0] }}">
                        <div class="seg-val">{{ $seg[1] ?: '—' }}</div>
                        <div class="seg-lbl">{{ $seg[0] }}</div>
                    </div>
                @endforeach
            </div>
            @if($coa->kode_mak_lengkap)
                <div class="cp-fullcode"><i class="bi bi-braces me-1"></i>{{ $coa->kode_mak_lengkap }}</div>
            @endif
        </div>
    </div>

    {{-- ════════ TAGIHAN PEMAKAI COA ════════ --}}
    <div class="cp-card mb-4" style="--d:.18s;">
        <div class="cp-card-head">
            <span class="cp-card-ic" style="--t:#0891b2; --t2:#22d3ee;"><i class="bi bi-receipt-cutoff"></i></span>
            <div>
                <h6 class="cp-card-title">Tagihan yang Menggunakan COA</h6>
                <div class="cp-card-sub">Transaksi pengeluaran &amp; penerimaan yang dibebankan ke kode akun ini.</div>
            </div>
            <div class="d-flex gap-2 ms-auto">
                <span class="cp-badge cp-badge-indigo"><i class="bi bi-arrow-up-right"></i>{{ number_format($billingStatistics['jumlah_pengeluaran']) }} Pengeluaran</span>
                <span class="cp-badge cp-badge-success"><i class="bi bi-arrow-down-left"></i>{{ number_format($billingStatistics['jumlah_penerimaan']) }} Penerimaan</span>
            </div>
        </div>
        @if($billingUsages->isEmpty())
            <div class="cp-empty">
                <div class="glyph"><i class="bi bi-inbox"></i></div>
                <div class="fw-bold mb-1" style="color:#334155;">Belum ada tagihan</div>
                <div class="small">Belum ada tagihan yang menggunakan COA ini.</div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table cp-table align-middle">
                    <thead>
                        <tr>
                            <th class="text-center px-3" width="4%">No</th>
                            <th width="14%">Jenis &amp; Tipe</th>
                            <th width="20%">No. Dokumen &amp; Tanggal</th>
                            <th width="27%">Uraian &amp; Pihak</th>
                            <th width="14%" class="text-end">Nominal</th>
                            <th width="11%" class="text-center">Status</th>
                            <th width="10%" class="text-center px-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($billingUsages as $usage)
                            @php
                                $normalizedStatus = strtoupper((string) $usage['status']);
                                $statusClass = match (true) {
                                    str_contains($normalizedStatus, 'UNPAID'),
                                    str_contains($normalizedStatus, 'PENDING'),
                                    str_contains($normalizedStatus, 'MENUNGGU'),
                                    str_contains($normalizedStatus, 'DRAFT') => 'cp-badge-warning',
                                    str_contains($normalizedStatus, 'PARTIAL'),
                                    str_contains($normalizedStatus, 'PROSES') => 'cp-badge-info',
                                    $normalizedStatus === 'PAID',
                                    str_contains($normalizedStatus, 'SETUJUI'),
                                    str_contains($normalizedStatus, 'SELESAI'),
                                    str_contains($normalizedStatus, 'APPROVED') => 'cp-badge-success',
                                    str_contains($normalizedStatus, 'REVISI'),
                                    str_contains($normalizedStatus, 'REVISION'),
                                    str_contains($normalizedStatus, 'TOLAK'),
                                    str_contains($normalizedStatus, 'REJECT') => 'cp-badge-danger',
                                    default => 'cp-badge-neutral',
                                };
                                $isPenerimaan = $usage['kategori'] === 'Penerimaan';
                            @endphp
                            <tr style="--i: {{ $loop->index }};">
                                <td class="text-center px-3 text-muted fw-semibold">{{ $loop->iteration }}</td>
                                <td>
                                    <span class="cp-badge {{ $isPenerimaan ? 'cp-badge-success' : 'cp-badge-indigo' }}">
                                        <i class="bi {{ $isPenerimaan ? 'bi-arrow-down-left' : 'bi-arrow-up-right' }}"></i>{{ $usage['kategori'] }}
                                    </span>
                                    <div class="small text-muted mt-1">{{ $usage['tipe'] }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold font-monospace" style="font-size:.8rem; color:#4338ca;">{{ $usage['nomor'] }}</div>
                                    <div class="small text-muted"><i class="bi bi-calendar3 me-1"></i>{{ optional($usage['tanggal'])->format('d M Y') ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="text-truncate fw-semibold" style="max-width: 260px;" title="{{ $usage['uraian'] }}">{{ $usage['uraian'] ?: '-' }}</div>
                                    <div class="small text-muted text-truncate" style="max-width: 260px;" title="{{ $usage['pihak'] }}"><i class="bi bi-person me-1"></i>{{ $usage['pihak'] ?: '-' }}</div>
                                </td>
                                <td class="text-end fw-bold cp-num">Rp {{ number_format($usage['nominal'], 0, ',', '.') }}</td>
                                <td class="text-center"><span class="cp-badge {{ $statusClass }}">{{ $usage['status'] }}</span></td>
                                <td class="text-center px-3">
                                    @if($usage['detail_url'])
                                        <a href="{{ $usage['detail_url'] }}" class="cp-act"><i class="bi bi-eye"></i> Lihat</a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ════════ PEMAKAIAN PADA DIPA ════════ --}}
    <div class="cp-card mb-4" style="--d:.22s;">
        <div class="cp-card-head">
            <span class="cp-card-ic" style="--t:#059669; --t2:#34d399;"><i class="bi bi-folder2-open"></i></span>
            <div>
                <h6 class="cp-card-title">Pemakaian COA pada DIPA</h6>
                <div class="cp-card-sub">Pagu, realisasi, dan sisa per item anggaran — beserta tingkat serapannya.</div>
            </div>
            <span class="cp-badge cp-badge-neutral ms-auto">{{ number_format($statistics['jumlah_item_dipa']) }} item</span>
        </div>
        @if($usageItems->isEmpty())
            <div class="cp-empty">
                <div class="glyph"><i class="bi bi-folder-x"></i></div>
                <div class="fw-bold mb-1" style="color:#334155;">Belum dipakai pada DIPA</div>
                <div class="small">COA ini belum dipakai pada item anggaran DIPA mana pun.</div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table cp-table align-middle">
                    <thead>
                        <tr>
                            <th class="text-center px-3" width="4%">No</th>
                            <th width="22%">DIPA &amp; Tahun</th>
                            <th width="12%">Revisi</th>
                            <th width="14%" class="text-end">Pagu</th>
                            <th width="14%" class="text-end">Realisasi</th>
                            <th width="16%">Serapan</th>
                            <th width="12%" class="text-end">Sisa Pagu</th>
                            <th width="8%" class="text-center px-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($usageItems as $item)
                            @php
                                $revision = $item->dipaRevision;
                                $dipa = $revision?->masterDipa;
                                $itemPagu = (float) $item->nilai_pagu;
                                $itemRealisasi = (float) $item->total_realisasi;
                                $itemSisa = (float) $item->sisa_pagu;
                                $itemPersen = $itemPagu > 0 ? round($itemRealisasi / $itemPagu * 100, 1) : 0;
                            @endphp
                            <tr style="--i: {{ $loop->index }};">
                                <td class="text-center px-3 text-muted fw-semibold">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-bold" style="color:#4338ca;">{{ $dipa?->nomor_dipa ?: '-' }}</div>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        <span class="cp-badge cp-badge-neutral"><i class="bi bi-calendar3"></i>TA {{ $dipa?->tahun_anggaran ?: '-' }}</span>
                                        @if($dipa && !$dipa->status_aktif)
                                            <span class="cp-badge cp-badge-neutral"><i class="bi bi-pause-circle"></i>Nonaktif</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($revision?->is_active)
                                        <span class="cp-badge cp-badge-success"><i class="bi bi-check-circle"></i>Revisi {{ $revision?->nomor_revisi ?? '-' }}</span>
                                    @else
                                        <span class="cp-badge cp-badge-neutral">Revisi {{ $revision?->nomor_revisi ?? '-' }}</span>
                                    @endif
                                </td>
                                <td class="text-end cp-num fw-semibold">Rp {{ number_format($itemPagu, 0, ',', '.') }}</td>
                                <td class="text-end cp-num fw-semibold" style="color:#4338ca;">Rp {{ number_format($itemRealisasi, 0, ',', '.') }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:7px; border-radius:999px; min-width:70px;">
                                            <div class="progress-bar {{ $itemPersen >= 100 ? 'bg-danger' : ($itemPersen >= 90 ? 'bg-warning' : 'bg-primary') }}"
                                                 style="width: {{ min($itemPersen, 100) }}%; border-radius:999px;"></div>
                                        </div>
                                        <span class="small fw-bold cp-num" style="min-width:44px; color:{{ $itemPersen >= 100 ? '#be123c' : '#475569' }};">{{ $itemPersen }}%</span>
                                    </div>
                                </td>
                                <td class="text-end cp-num fw-bold {{ $itemSisa < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $itemSisa < 0 ? '−' : '' }}Rp {{ number_format(abs($itemSisa), 0, ',', '.') }}
                                </td>
                                <td class="text-center px-3">
                                    @if($dipa)
                                        <a href="{{ route('dipas.show', $dipa) }}" class="cp-act"><i class="bi bi-box-arrow-up-right"></i> DIPA</a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* Count-up angka statistik */
    document.querySelectorAll('.cp-countup').forEach(function (el) {
        var target = parseInt(el.dataset.target || '0', 10);
        if (reduced || target <= 0) { el.textContent = target.toLocaleString('id-ID'); return; }
        var dur = 1200, start = performance.now();
        (function step(now) {
            var p = Math.min((now - start) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(target * eased).toLocaleString('id-ID');
            if (p < 1) requestAnimationFrame(step);
        })(performance.now());
    });

    /* Bar serapan terisi setelah render (transisi CSS) */
    var bar = document.getElementById('cpBar');
    if (bar) requestAnimationFrame(function () { bar.classList.add('loaded'); });

    /* Salin kode MAK dari hero */
    var kode = document.getElementById('cpKodeCopy');
    if (kode) {
        kode.addEventListener('click', function () {
            var text = kode.getAttribute('data-copy');
            var label = document.getElementById('cpKodeText');
            var asli = label.textContent;
            function done() {
                label.textContent = 'Tersalin!';
                setTimeout(function () { label.textContent = asli; }, 1200);
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done);
            } else {
                var ta = document.createElement('textarea');
                ta.value = text; document.body.appendChild(ta);
                ta.select(); document.execCommand('copy'); ta.remove();
                done();
            }
        });
    }
});
</script>
@endpush
