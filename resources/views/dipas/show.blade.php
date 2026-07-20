@extends('layouts.app')

@section('title', 'Detail DIPA')

@php
    $selisih = $summary['selisih_pagu_vs_item'];
    $hasSelisih = round((float) $selisih, 2) !== 0.0;
@endphp

@push('css')
<style>
/* ============================================================
   DETAIL DIPA — hero aurora · stat tiles · tabel interaktif
   ============================================================ */
.dipa-view { --dv-indigo:#4f46e5; --dv-violet:#8b5cf6; --dv-cyan:#06b6d4; --dv-emerald:#10b981;
    --dv-amber:#f59e0b; --dv-rose:#e11d48; --dv-ink:#0f172a; --dv-muted:#64748b;
    --dv-border:#e8ecf5; --dv-radius:1.1rem;
    --dv-shadow:0 16px 36px -20px rgba(30,27,75,.25);
    --dv-shadow-hover:0 26px 50px -22px rgba(79,70,229,.4); }

@keyframes dvAurora { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
@keyframes dvFloat  { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-11px)} }
@keyframes dvRise   { from{opacity:0; transform:translateY(18px)} to{opacity:1; transform:none} }
@keyframes dvRowIn  { from{opacity:0; transform:translateY(9px)} to{opacity:1; transform:none} }
@keyframes dvSheen  { 0%,55%{left:-70%} 85%,100%{left:140%} }
@keyframes dvPulse  { 0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.5)} 50%{box-shadow:0 0 0 8px rgba(16,185,129,0)} }
@keyframes dvPulseAmber { 0%,100%{box-shadow:0 0 0 0 rgba(245,158,11,.45)} 50%{box-shadow:0 0 0 9px rgba(245,158,11,0)} }
@media (prefers-reduced-motion: reduce) {
    .dipa-view * { animation-duration:.001s !important; animation-iteration-count:1 !important; transition-duration:.001s !important; }
}

/* ---------- HERO ---------- */
.dv-hero { position:relative; overflow:hidden; border-radius:1.4rem; padding:1.8rem 2rem;
    margin-bottom:1.4rem; color:#fff;
    background:linear-gradient(125deg,#0b1020,#1e1b4b 32%,#4338ca 64%,#7c3aed 84%,#0e7490);
    background-size:340% 340%; animation:dvAurora 18s ease infinite;
    box-shadow:0 26px 52px -24px rgba(49,46,129,.6); }
.dv-hero::before, .dv-hero::after { content:''; position:absolute; border-radius:50%; pointer-events:none;
    background:radial-gradient(circle, rgba(255,255,255,.15) 0%, transparent 70%); }
.dv-hero::before { width:340px; height:340px; top:-55%; left:-4%; animation:dvFloat 10s ease-in-out infinite; }
.dv-hero::after  { width:250px; height:250px; bottom:-58%; right:-3%; animation:dvFloat 13s ease-in-out infinite reverse; }
.dv-hero .mesh { position:absolute; inset:0; opacity:.14; pointer-events:none;
    background-image:linear-gradient(rgba(255,255,255,.4) 1px, transparent 1px),
                     linear-gradient(90deg, rgba(255,255,255,.4) 1px, transparent 1px);
    background-size:42px 42px; mask-image:radial-gradient(ellipse at 22% 0%, #000 5%, transparent 62%); }
.dv-hero-grid { position:relative; z-index:2; display:flex; flex-wrap:wrap; justify-content:space-between;
    align-items:center; gap:1.2rem; }
.dv-chip { display:inline-flex; align-items:center; gap:.4rem; background:rgba(255,255,255,.13);
    border:1px solid rgba(255,255,255,.25); backdrop-filter:blur(8px); padding:.3rem .85rem;
    border-radius:999px; font-weight:700; font-size:.72rem; letter-spacing:.4px; color:#fff; }
.dv-chip.on { background:rgba(16,185,129,.28); border-color:rgba(110,231,183,.55); }
.dv-chip.on .dot { width:8px; height:8px; border-radius:50%; background:#34d399; animation:dvPulse 2s infinite; }
.dv-chip.off { background:rgba(148,163,184,.25); border-color:rgba(203,213,225,.4); }
.dv-nomor { font-family:SFMono-Regular,Menlo,Consolas,monospace; font-weight:800; color:#fff;
    font-size:clamp(1.15rem,2.6vw,1.7rem); letter-spacing:-.02em; cursor:pointer;
    display:inline-flex; align-items:center; gap:.6rem; overflow-wrap:anywhere; transition:opacity .2s ease; }
.dv-nomor:hover { opacity:.85; }
.dv-nomor .bi-copy { font-size:.9rem; opacity:.6; }
.dv-sub { color:rgba(255,255,255,.85); font-weight:600; font-size:.88rem; margin-top:.3rem; }
.dv-btn { display:inline-flex; align-items:center; gap:.45rem; border-radius:999px; font-weight:700;
    font-size:.8rem; padding:.5rem 1.1rem; border:1px solid rgba(255,255,255,.35); color:#fff;
    background:rgba(255,255,255,.12); backdrop-filter:blur(8px); text-decoration:none; cursor:pointer;
    transition:transform .2s ease, background .2s ease; }
.dv-btn:hover { color:#fff; background:rgba(255,255,255,.24); transform:translateY(-2px); }
.dv-btn.solid { background:#fff; color:#4338ca; border-color:transparent;
    box-shadow:0 10px 24px -10px rgba(0,0,0,.5); font-weight:800; }
.dv-btn.solid:hover { color:#4338ca; }
.dv-btn.danger { background:rgba(225,29,72,.85); border-color:transparent; }
.dv-btn.danger:hover { background:#e11d48; }

/* ---------- STAT TILES ---------- */
.dv-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:.8rem; }
.dv-stat { position:relative; overflow:hidden; background:#fff; border:1px solid var(--dv-border);
    border-radius:var(--dv-radius); box-shadow:var(--dv-shadow); padding:1rem 1.1rem;
    transition:transform .3s cubic-bezier(.25,.8,.25,1), box-shadow .3s, border-color .3s;
    animation:dvRise .5s ease both; animation-delay:var(--d, 0s); }
.dv-stat:hover { transform:translateY(-5px); box-shadow:var(--dv-shadow-hover); border-color:#c7d2fe; }
.dv-stat .ic { width:38px; height:38px; border-radius:11px; display:grid; place-items:center;
    font-size:1rem; color:var(--t,#4f46e5); background:var(--ts,#eef2ff); margin-bottom:.6rem;
    transition:transform .25s cubic-bezier(.34,1.56,.64,1); }
.dv-stat:hover .ic { transform:scale(1.12) rotate(-5deg); }
.dv-stat .lbl { font-size:.64rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#94a3b8; }
.dv-stat .val { font-size:1.12rem; font-weight:800; color:var(--dv-ink); letter-spacing:-.01em;
    font-variant-numeric:tabular-nums; line-height:1.25; overflow-wrap:anywhere; }
.dv-stat .sub { font-size:.7rem; color:var(--dv-muted); font-weight:600; }
.dv-stat.ok { border-color:rgba(16,185,129,.35); background:linear-gradient(135deg, rgba(16,185,129,.08), rgba(16,185,129,.015)); }
.dv-stat.ok .val { color:#047857; }
.dv-stat.ok::after { content:''; position:absolute; top:0; bottom:0; width:38%; left:-55%;
    background:linear-gradient(100deg, transparent, rgba(255,255,255,.6), transparent);
    transform:skewX(-18deg); animation:dvSheen 3.8s ease-in-out 1.2s infinite; }
.dv-stat.warn { border-color:rgba(225,29,72,.35); background:linear-gradient(135deg, rgba(225,29,72,.07), transparent); }
.dv-stat.warn .val { color:#be123c; }

/* ---------- PANEL SELISIH ---------- */
.dv-warning { border:1px solid #f3e3bd; border-radius:var(--dv-radius);
    background:linear-gradient(180deg,#fffdf6,#fef8ec); box-shadow:0 14px 32px -24px rgba(180,122,9,.5);
    padding:1.1rem 1.3rem; display:flex; align-items:flex-start; gap:1rem;
    animation:dvRise .5s .1s ease both; }
.dv-warning .ic { width:44px; height:44px; border-radius:13px; flex-shrink:0; display:grid; place-items:center;
    font-size:1.2rem; color:#fff; background:linear-gradient(135deg,#d97706,#f59e0b);
    box-shadow:0 10px 20px -10px rgba(217,119,6,.8); animation:dvPulseAmber 2.4s ease-in-out infinite; }

/* ---------- KARTU UMUM ---------- */
.dv-card { background:#fff; border:1px solid var(--dv-border); border-radius:var(--dv-radius);
    box-shadow:var(--dv-shadow); overflow:hidden; animation:dvRise .55s ease both; animation-delay:var(--d, 0s); }
.dv-card-head { display:flex; align-items:center; gap:.75rem; padding:1.05rem 1.3rem .85rem; flex-wrap:wrap; }
.dv-card-ic { width:38px; height:38px; border-radius:11px; display:grid; place-items:center;
    color:#fff; font-size:1rem; background:linear-gradient(135deg, var(--t,#4f46e5), var(--t2,#818cf8));
    box-shadow:0 8px 18px -8px var(--t,#4f46e5); flex-shrink:0; }
.dv-card-title { font-weight:800; margin:0; letter-spacing:-.01em; color:var(--dv-ink); }
.dv-card-sub { font-size:.74rem; color:var(--dv-muted); font-weight:600; }

/* Tile info revisi aktif */
.dv-tile { display:flex; align-items:center; gap:.8rem; border:1px solid var(--dv-border);
    border-radius:.9rem; padding:.8rem .95rem; background:linear-gradient(180deg,#fff,#fafbff);
    height:100%; transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
.dv-tile:hover { transform:translateY(-2px); border-color:#dfe3f6; box-shadow:0 10px 22px -14px rgba(79,70,229,.45); }
.dv-tile .t-ic { width:36px; height:36px; flex-shrink:0; border-radius:10px; display:grid; place-items:center;
    font-size:.95rem; color:var(--t,#4f46e5); background:var(--ts,#eef2ff); }
.dipa-view .t-lbl { font-size:.62rem; font-weight:800; text-transform:uppercase; letter-spacing:.07em; color:#94a3b8; }
.dipa-view .t-val { font-size:.88rem; font-weight:800; color:var(--dv-ink); overflow-wrap:anywhere; }

/* Panel dokumen PDF */
.dv-doc { display:flex; align-items:center; gap:.9rem; border:1px solid #fecdd3; border-radius:1rem;
    padding:.9rem 1rem; background:linear-gradient(135deg,#fff,#fff1f2aa); height:100%;
    transition:transform .2s ease, box-shadow .2s ease; }
.dv-doc:hover { transform:translateY(-2px); box-shadow:0 12px 26px -16px rgba(225,29,72,.5); }
.dv-doc.missing { border:2px dashed #e2e8f0; background:repeating-linear-gradient(-45deg,#f8fafc 0 12px,#f1f5f9 12px 24px); }
.dv-doc.missing:hover { transform:none; box-shadow:none; }
.dv-doc .d-ic { width:42px; height:42px; flex-shrink:0; border-radius:12px; display:grid; place-items:center;
    font-size:1.2rem; color:#e11d48; background:#ffe4e6; }
.dv-doc.missing .d-ic { color:#94a3b8; background:#f1f5f9; }
.dv-doc-btn { display:inline-flex; align-items:center; gap:.4rem; flex-shrink:0; padding:.45rem .95rem;
    border-radius:999px; text-decoration:none; font-size:.74rem; font-weight:800; color:#fff;
    background:linear-gradient(135deg,#e11d48,#f43f5e); box-shadow:0 8px 18px -8px rgba(225,29,72,.7);
    transition:transform .2s ease, box-shadow .2s ease; }
.dv-doc-btn:hover { color:#fff; transform:translateY(-2px) scale(1.03); box-shadow:0 12px 24px -8px rgba(225,29,72,.8); }

/* ---------- FILTER ---------- */
.dipa-view .form-label { font-size:.7rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:var(--dv-muted); }
.dipa-view .form-control, .dipa-view .form-select { border-radius:.7rem; border-color:var(--dv-border);
    transition:border-color .2s ease, box-shadow .2s ease; }
.dipa-view .form-control:focus, .dipa-view .form-select:focus { border-color:#a5b4fc;
    box-shadow:0 0 0 .2rem rgba(99,102,241,.12); }

/* ---------- TABEL ---------- */
.dv-table { margin-bottom:0; }
.dv-table thead th { background:#f8faff !important; border-bottom:1px solid var(--dv-border) !important;
    font-size:.68rem; font-weight:800; letter-spacing:.09em; text-transform:uppercase; color:#7c8db5 !important; }
.dv-table tbody tr { animation:dvRowIn .4s ease both; animation-delay:calc(var(--i, 0) * 40ms);
    transition:background .2s ease, box-shadow .2s ease; }
.dv-table tbody tr:hover { background:#f5f7ff; box-shadow:inset 3px 0 0 var(--dv-indigo); }
.dv-table tbody tr.dv-row-active { background:linear-gradient(90deg, rgba(16,185,129,.07), transparent 70%); }
.dv-table tbody td { font-size:.84rem; border-color:rgba(124,141,181,.1) !important; }
.dv-num { font-variant-numeric:tabular-nums; }
.dv-kode { font-family:SFMono-Regular,Menlo,Consolas,monospace; font-weight:800; color:#4338ca;
    font-size:.8rem; cursor:pointer; display:inline-flex; align-items:center; gap:.4rem;
    overflow-wrap:anywhere; }
.dv-kode .bi-copy { font-size:.66rem; color:#cbd5e1; transition:color .2s ease; }
.dv-kode:hover .bi-copy { color:var(--dv-indigo); }
.dv-kode.copied { color:#047857; }

.dv-badge { display:inline-flex; align-items:center; gap:.35rem; font-size:.68rem; font-weight:700;
    padding:.28rem .65rem; border-radius:999px; letter-spacing:.03em; white-space:nowrap; }
.dv-badge-success { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
.dv-badge-success .dot { width:7px; height:7px; border-radius:50%; background:#10b981; animation:dvPulse 2s infinite; }
.dv-badge-neutral { background:#f8fafc; color:#64748b; border:1px solid #e2e8f0; }
.dv-badge-indigo  { background:#eef2ff; color:#4338ca; border:1px solid #e0e7ff; }
.dv-badge-akun    { background:#f1f5f9; color:#334155; border:1px solid #e2e8f0;
    font-family:SFMono-Regular,Menlo,Consolas,monospace; }

.dv-act { display:inline-flex; align-items:center; gap:.35rem; padding:.35rem .85rem; border-radius:999px;
    border:1px solid var(--dv-border); background:#fff; color:#64748b; font-size:.72rem; font-weight:700;
    cursor:pointer; text-decoration:none; transition:all .22s ease; white-space:nowrap; }
.dv-act:hover { transform:translateY(-2px); }
.dv-act-toggle:hover { background:#f59e0b; border-color:#f59e0b; color:#fff; box-shadow:0 8px 18px -8px rgba(245,158,11,.6); }
.dv-act-del:hover { background:#e11d48; border-color:#e11d48; color:#fff; box-shadow:0 8px 18px -8px rgba(225,29,72,.6); }
.dv-act-primary { border-color:#c7d2fe; color:#4338ca; background:#eef2ff; }
.dv-act-primary:hover { background:#4f46e5; border-color:#4f46e5; color:#fff; box-shadow:0 8px 18px -8px rgba(79,70,229,.6); }

/* Serapan bar mini per item */
.dv-serap { display:flex; align-items:center; gap:.5rem; min-width:110px; }
.dv-serap .track { flex:1; height:7px; border-radius:999px; background:#eef1f7; overflow:hidden; min-width:60px; }
.dv-serap .fill { height:100%; border-radius:999px; width:0; transition:width 1.1s cubic-bezier(.16,1,.3,1) .3s; }
.dv-serap.loaded .fill { width:var(--w); }
.dv-serap .pct { font-size:.7rem; font-weight:800; min-width:40px; color:#475569; }

/* Empty state */
.dv-empty { padding:3rem 1rem; text-align:center; color:var(--dv-muted); }
.dv-empty .glyph { width:66px; height:66px; margin:0 auto .9rem; border-radius:20px; display:grid;
    place-items:center; font-size:1.7rem; color:#a5b4fc; background:linear-gradient(135deg,#eef2ff,#e0e7ff);
    animation:dvFloat 5s ease-in-out infinite; }

/* Modal tambah item */
#modalTambahItem .modal-content { border:0; border-radius:1.1rem; overflow:hidden; }
#modalTambahItem .modal-header { border:0; background:linear-gradient(120deg,#4338ca,#7c3aed); }
</style>
@endpush

@section('content')
<div class="dipa-view">

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info border-0 bg-info alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('info') }}</div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm">
            <ul class="text-white mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ════════ HERO ════════ --}}
    <div class="dv-hero">
        <div class="mesh"></div>
        <div class="dv-hero-grid">
            <div style="max-width:720px;">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    @if($dipa->status_aktif)
                        <span class="dv-chip on"><span class="dot"></span> DIPA AKTIF</span>
                    @else
                        <span class="dv-chip off"><i class="bi bi-pause-circle"></i> DIPA NONAKTIF</span>
                    @endif
                    <span class="dv-chip"><i class="bi bi-calendar3"></i> TA {{ $dipa->tahun_anggaran }}</span>
                    <span class="dv-chip"><i class="bi bi-arrow-repeat"></i> Revisi Aktif {{ $dipa->revisi_aktif_ke ?? 0 }}</span>
                </div>
                <div class="dv-nomor" id="dvNomorCopy" data-copy="{{ $dipa->nomor_dipa }}" title="Klik untuk menyalin nomor">
                    <span id="dvNomorText">{{ $dipa->nomor_dipa }}</span>
                    <i class="bi bi-copy"></i>
                </div>
                <div class="dv-sub"><i class="bi bi-patch-check me-1"></i>Disahkan {{ optional($dipa->tanggal_disahkan)->translatedFormat('d F Y') ?? '-' }}</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="dv-btn solid" data-bs-toggle="modal" data-bs-target="#modalTambahItem">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Item
                </button>
                <a href="{{ route('dipas.revisions.create', $dipa) }}" class="dv-btn"><i class="bi bi-files"></i> Tambah Revisi</a>
                <a href="{{ route('dipas.edit', $dipa) }}" class="dv-btn"><i class="bi bi-pencil-square"></i> Edit Header</a>
                @if($dipa->revisions->flatMap->items->isEmpty())
                    <form action="{{ route('dipas.destroy', $dipa) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Hapus DIPA ini beserta seluruh revisinya secara permanen?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dv-btn danger border-0"><i class="bi bi-trash"></i> Hapus</button>
                    </form>
                @endif
                <a href="{{ route('dipas.index') }}" class="dv-btn"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>
        </div>
    </div>

    {{-- ════════ STAT TILES ════════ --}}
    <div class="dv-stats mb-4">
        <div class="dv-stat" style="--d:.02s; --t:#4f46e5; --ts:#eef2ff;">
            <div class="ic"><i class="bi bi-bank2"></i></div>
            <div class="lbl">Total Pagu Revisi Aktif</div>
            <div class="val">Rp <span class="dv-countup" data-target="{{ (int) $summary['total_pagu_revisi_aktif'] }}">0</span></div>
        </div>
        <div class="dv-stat" style="--d:.07s; --t:#0891b2; --ts:#ecfeff;">
            <div class="ic"><i class="bi bi-list-ol"></i></div>
            <div class="lbl">Total Item Anggaran</div>
            <div class="val">Rp <span class="dv-countup" data-target="{{ (int) $summary['total_item_anggaran'] }}">0</span></div>
        </div>
        <div class="dv-stat" style="--d:.12s; --t:#7c3aed; --ts:#f5f3ff;">
            <div class="ic"><i class="bi bi-check2-square"></i></div>
            <div class="lbl">Jumlah Item Aktif</div>
            <div class="val dv-countup" data-target="{{ $summary['jumlah_item_aktif'] }}">0</div>
        </div>
        <div class="dv-stat {{ $hasSelisih ? 'warn' : 'ok' }}" style="--d:.17s; --t:{{ $hasSelisih ? '#e11d48' : '#047857' }}; --ts:{{ $hasSelisih ? '#fff1f2' : '#ecfdf5' }};">
            <div class="ic"><i class="bi {{ $hasSelisih ? 'bi-exclamation-octagon' : 'bi-check-circle' }}"></i></div>
            <div class="lbl" style="color:{{ $hasSelisih ? '#be123c' : '#059669' }};">Selisih Pagu vs Item</div>
            <div class="val">Rp <span class="dv-countup" data-target="{{ (int) abs($selisih) }}">0</span></div>
            <div class="sub">{{ $hasSelisih ? 'pagu & item belum sinkron' : 'pagu & item sudah sinkron' }}</div>
        </div>
    </div>

    @if($hasSelisih)
        <div class="dv-warning mb-4" role="alert" data-sky-ignore>
            <div class="ic"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="small">
                <div class="fw-bolder text-dark mb-1">Pagu Belum Sinkron dengan Item Anggaran</div>
                <div class="text-secondary">
                    Total pagu revisi aktif belum sama dengan penjumlahan seluruh item anggaran — selisih saat ini
                    <strong>Rp {{ number_format($selisih, 0, ',', '.') }}</strong>.
                    Sesuaikan nilai item anggaran di bawah atau perbarui total pagu lewat revisi baru.
                </div>
            </div>
        </div>
    @endif

    {{-- ════════ INFO REVISI AKTIF ════════ --}}
    <div class="dv-card mb-4" style="--d:.1s;">
        <div class="dv-card-head">
            <span class="dv-card-ic" style="--t:#7c3aed; --t2:#a855f7;"><i class="bi bi-layers-fill"></i></span>
            <div>
                <h6 class="dv-card-title">Informasi Revisi Aktif</h6>
                <div class="dv-card-sub">Detail revisi yang saat ini dipakai sebagai dasar item anggaran.</div>
            </div>
            <a href="{{ route('dipas.revisions.create', $dipa) }}" class="dv-act dv-act-primary ms-auto text-decoration-none">
                <i class="bi bi-file-earmark-plus"></i> Tambah Revisi Baru
            </a>
        </div>
        <div class="px-4 pb-4 pt-1">
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <div class="dv-tile" style="--t:#4f46e5; --ts:#eef2ff;">
                        <span class="t-ic"><i class="bi bi-arrow-repeat"></i></span>
                        <div>
                            <div class="t-lbl">Nomor Revisi</div>
                            <div class="t-val">Revisi {{ $activeRevision->nomor_revisi ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="dv-tile" style="--t:#0891b2; --ts:#ecfeff;">
                        <span class="t-ic"><i class="bi bi-calendar3"></i></span>
                        <div>
                            <div class="t-lbl">Tanggal Revisi</div>
                            <div class="t-val">{{ optional($activeRevision?->tanggal_revisi)->translatedFormat('d F Y') ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="dv-tile" style="--t:#047857; --ts:#ecfdf5;">
                        <span class="t-ic"><i class="bi bi-cash-stack"></i></span>
                        <div>
                            <div class="t-lbl">Total Pagu</div>
                            <div class="t-val" style="color:#047857;">Rp {{ number_format($activeRevision->total_pagu ?? 0, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="dv-doc {{ $activeRevision?->file_dokumen_dipa ? '' : 'missing' }}">
                        <span class="d-ic"><i class="bi bi-file-earmark-pdf-fill"></i></span>
                        <div style="min-width:0; flex:1;">
                            <div class="t-lbl">Dokumen DIPA</div>
                            @if($activeRevision?->file_dokumen_dipa)
                                <a href="{{ route('secure-file', ['dipa-revision', $activeRevision->id, 'file_dokumen_dipa']) }}"
                                   target="_blank" rel="noopener noreferrer" class="dv-doc-btn mt-1">
                                    <i class="bi bi-box-arrow-up-right"></i> Buka
                                </a>
                            @else
                                <div class="t-val text-muted" style="font-size:.78rem;">Belum ada file</div>
                            @endif
                        </div>
                    </div>
                </div>
                @if($activeRevision?->keterangan)
                    <div class="col-12">
                        <div class="dv-tile" style="--t:#64748b; --ts:#f1f5f9;">
                            <span class="t-ic"><i class="bi bi-chat-left-text"></i></span>
                            <div>
                                <div class="t-lbl">Keterangan</div>
                                <div class="t-val fw-semibold">{{ $activeRevision->keterangan }}</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ════════ ITEM ANGGARAN ════════ --}}
    <div class="dv-card mb-4" style="--d:.15s;">
        <div class="dv-card-head">
            <span class="dv-card-ic" style="--t:#0891b2; --t2:#22d3ee;"><i class="bi bi-list-check"></i></span>
            <div>
                <h6 class="dv-card-title">Item Anggaran Revisi Aktif</h6>
                <div class="dv-card-sub">Kelola item COA beserta pagu, realisasi, dan serapannya.</div>
            </div>
            <button type="button" class="dv-act dv-act-primary ms-auto" data-bs-toggle="modal" data-bs-target="#modalTambahItem">
                <i class="bi bi-plus-lg"></i> Tambah Item Anggaran
            </button>
        </div>
        <div class="px-4 pb-2 pt-1">
            <form method="GET" action="{{ route('dipas.show', $dipa) }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Cari COA</label>
                        <input type="text" name="search_coa" class="form-control form-control-sm" value="{{ request('search_coa') }}" placeholder="Kode MAK lengkap">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cari Nama Akun</label>
                        <input type="text" name="search_nama_akun" class="form-control form-control-sm" value="{{ request('search_nama_akun') }}" placeholder="Nama akun">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Kode Akun</label>
                        <select name="kd_akun" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach($kdAkunOptions as $kdAkun)
                                <option value="{{ $kdAkun }}" {{ (string) request('kd_akun') === (string) $kdAkun ? 'selected' : '' }}>{{ $kdAkun }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status Aktif</label>
                        <select name="status_item" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option value="aktif" {{ request('status_item') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="nonaktif" {{ request('status_item') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="dv-act dv-act-primary flex-grow-1 justify-content-center"><i class="bi bi-funnel"></i> Filter</button>
                            <a href="{{ route('dipas.show', $dipa) }}" class="dv-act text-decoration-none"><i class="bi bi-arrow-counterclockwise"></i></a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        @if($items->isEmpty())
            <div class="dv-empty">
                <div class="glyph"><i class="bi bi-inbox"></i></div>
                <div class="fw-bold mb-1" style="color:#334155;">Belum ada item anggaran</div>
                <div class="small">Belum ada item anggaran pada revisi aktif ini — tambahkan lewat tombol di atas.</div>
            </div>
        @else
            <div class="table-responsive mt-2">
                <table class="table dv-table align-middle">
                    <thead>
                        <tr>
                            <th class="text-center px-3" width="4%">No</th>
                            <th width="19%">COA Lengkap</th>
                            <th width="15%">Nama Akun</th>
                            <th width="12%" class="text-end">Nilai Pagu</th>
                            <th width="12%" class="text-end">Realisasi</th>
                            <th width="13%">Serapan</th>
                            <th width="12%" class="text-end">Sisa Pagu</th>
                            <th width="6%" class="text-center">Status</th>
                            <th width="12%" class="text-center px-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            @php
                                $itemPagu = (float) $item->nilai_pagu;
                                $itemRealisasi = (float) $item->total_realisasi;
                                $itemSisa = (float) $item->sisa_pagu;
                                $itemPersen = $itemPagu > 0 ? round($itemRealisasi / $itemPagu * 100, 1) : 0;
                                $serapWarna = $itemPersen >= 100 ? '#e11d48' : ($itemPersen >= 90 ? '#f59e0b' : '#4f46e5');
                            @endphp
                            <tr style="--i: {{ $loop->index }};">
                                <td class="text-center px-3 text-muted fw-semibold">{{ $loop->iteration }}</td>
                                <td>
                                    <span class="dv-kode" data-copy="{{ $item->coa->kode_mak_lengkap ?? '' }}" title="Klik untuk menyalin kode">
                                        <span class="dv-kode-text">{{ $item->coa->kode_mak_lengkap ?? '-' }}</span>
                                        <i class="bi bi-copy"></i>
                                    </span>
                                    <div class="mt-1"><span class="dv-badge dv-badge-akun">{{ $item->coa->kd_akun ?? '-' }}</span></div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $item->coa->nama_akun ?? '-' }}</div>
                                    <div class="small text-muted">{{ $item->coa->jenis_akun ?? '-' }}</div>
                                </td>
                                <td class="text-end dv-num fw-semibold">Rp {{ number_format($itemPagu, 0, ',', '.') }}</td>
                                <td class="text-end dv-num fw-semibold" style="color:#4338ca;">Rp {{ number_format($itemRealisasi, 0, ',', '.') }}</td>
                                <td>
                                    <div class="dv-serap">
                                        <div class="track"><div class="fill" style="--w: {{ min($itemPersen, 100) }}%; background:{{ $serapWarna }};"></div></div>
                                        <span class="pct" style="color:{{ $itemPersen >= 100 ? '#be123c' : '#475569' }};">{{ $itemPersen }}%</span>
                                    </div>
                                </td>
                                <td class="text-end dv-num fw-bold {{ $itemSisa < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $itemSisa < 0 ? '−' : '' }}Rp {{ number_format(abs($itemSisa), 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    @if($item->status_aktif)
                                        <span class="dv-badge dv-badge-success"><span class="dot"></span>Aktif</span>
                                    @else
                                        <span class="dv-badge dv-badge-neutral">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-center px-3">
                                    <div class="d-flex justify-content-center gap-1 flex-wrap">
                                        <form action="{{ route('dipas.items.toggle', [$dipa, $item]) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dv-act dv-act-toggle" title="{{ $item->status_aktif ? 'Nonaktifkan item' : 'Aktifkan item' }}">
                                                <i class="bi {{ $item->status_aktif ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                                {{ $item->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        <form action="{{ route('dipas.items.destroy', [$dipa, $item]) }}" method="POST" onsubmit="return confirm('Hapus item ini dari revisi aktif?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dv-act dv-act-del" title="Hapus item">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ════════ HISTORI REVISI ════════ --}}
    <div class="dv-card mb-4" style="--d:.2s;">
        <div class="dv-card-head">
            <span class="dv-card-ic" style="--t:#059669; --t2:#34d399;"><i class="bi bi-clock-history"></i></span>
            <div>
                <h6 class="dv-card-title">Histori Revisi</h6>
                <div class="dv-card-sub">Seluruh revisi DIPA — aktifkan revisi tertentu untuk mengganti dasar anggaran.</div>
            </div>
            <span class="dv-badge dv-badge-neutral ms-auto">{{ $dipa->revisions->count() }} revisi</span>
        </div>
        <div class="table-responsive">
            <table class="table dv-table align-middle">
                <thead>
                    <tr>
                        <th class="px-3">Nomor Revisi</th>
                        <th>Tanggal Revisi</th>
                        <th class="text-end">Total Pagu</th>
                        <th class="text-center">Status</th>
                        <th>Keterangan</th>
                        <th class="text-center px-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dipa->revisions->sortByDesc('nomor_revisi')->values() as $revision)
                        <tr style="--i: {{ $loop->index }};" class="{{ $revision->is_active ? 'dv-row-active' : '' }}">
                            <td class="px-3">
                                <span class="dv-badge {{ $revision->is_active ? 'dv-badge-success' : 'dv-badge-indigo' }}">
                                    <i class="bi bi-arrow-repeat"></i>Revisi {{ $revision->nomor_revisi }}
                                </span>
                            </td>
                            <td class="fw-semibold">{{ optional($revision->tanggal_revisi)->translatedFormat('d F Y') ?? '-' }}</td>
                            <td class="text-end dv-num fw-bold">Rp {{ number_format($revision->total_pagu ?? 0, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if($revision->is_active)
                                    <span class="dv-badge dv-badge-success"><span class="dot"></span>Aktif</span>
                                @else
                                    <span class="dv-badge dv-badge-neutral">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $revision->keterangan ?: '—' }}</td>
                            <td class="text-center px-3">
                                @if(!$revision->is_active)
                                    <form action="{{ route('dipas.revisions.activate', [$dipa, $revision]) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dv-act dv-act-primary">
                                            <i class="bi bi-lightning-charge"></i> Aktifkan Revisi Ini
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small"><i class="bi bi-check-circle me-1 text-success"></i>Revisi aktif saat ini</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ════════ MODAL TAMBAH ITEM ════════ --}}
    <div class="modal fade" id="modalTambahItem" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="{{ route('dipas.items.store', $dipa) }}" method="POST">
                    @csrf
                    <div class="modal-header text-white">
                        <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Tambah Item Anggaran</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Pilih COA</label>
                                <select name="coa_id" id="coa_id" class="form-select select2-coa js-coa-select" required>
                                    <option value="">-- Pilih COA --</option>
                                    @foreach($coaOptions as $coa)
                                        <option value="{{ $coa->id }}"
                                            data-kode="{{ $coa->kode_mak_lengkap }}"
                                            data-kd-akun="{{ $coa->kd_akun }}"
                                            data-nama="{{ $coa->nama_akun }}"
                                            data-jenis="{{ $coa->jenis_akun }}">
                                            {{ $coa->kode_mak_lengkap }} - {{ $coa->nama_akun }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Nilai Pagu</label>
                                <input type="hidden" name="nilai_pagu" id="nilai_pagu" value="{{ old('nilai_pagu') }}">
                                <input type="text" id="nilai_pagu_display" class="form-control" inputmode="numeric" placeholder="Rp 0" value="{{ old('nilai_pagu') ? 'Rp ' . number_format((float) old('nilai_pagu'), 0, ',', '.') : '' }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Status Aktif</label>
                                <select name="status_aktif" class="form-select" required>
                                    <option value="1">Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="border rounded-4 p-3" style="background:linear-gradient(180deg,#f8faff,#eef2ff55); border-color:#e0e7ff !important;">
                                    <div class="small text-muted mb-2"><i class="bi bi-eye me-1"></i>Preview COA</div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="small text-muted">COA Lengkap</div>
                                            <div class="fw-bold font-monospace" style="color:#4338ca;" id="preview_kode_mak">-</div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="small text-muted">Kode Akun</div>
                                            <div class="fw-bold" id="preview_kd_akun">-</div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="small text-muted">Jenis Akun</div>
                                            <div class="fw-bold" id="preview_jenis_akun">-</div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="small text-muted">Nama Akun</div>
                                            <div class="fw-bold" id="preview_nama_akun">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        /* ── Count-up statistik ── */
        document.querySelectorAll('.dv-countup').forEach(function (el) {
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

        /* ── Bar serapan terisi setelah render ── */
        requestAnimationFrame(function () {
            document.querySelectorAll('.dv-serap').forEach(function (el) { el.classList.add('loaded'); });
        });

        /* ── Salin nomor DIPA / kode COA (delegated) ── */
        document.addEventListener('click', function (e) {
            var el = e.target.closest('[data-copy].dv-nomor, [data-copy].dv-kode');
            if (!el) return;
            var text = el.getAttribute('data-copy');
            if (!text) return;
            var label = el.querySelector('.dv-kode-text, #dvNomorText') || el.querySelector('span');
            var asli = label ? label.textContent : '';
            function done() {
                el.classList.add('copied');
                if (label) label.textContent = 'Tersalin!';
                setTimeout(function () {
                    el.classList.remove('copied');
                    if (label) label.textContent = asli;
                }, 1200);
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

        /* ── Modal Tambah Item: select2 + preview + format rupiah ── */
        const coaSelect = document.getElementById('coa_id');
        const nilaiPaguHidden = document.getElementById('nilai_pagu');
        const nilaiPaguDisplay = document.getElementById('nilai_pagu_display');

        if (!coaSelect || !nilaiPaguHidden || !nilaiPaguDisplay) {
            return;
        }

        if (window.jQuery && typeof window.jQuery.fn.select2 === 'function') {
            window.jQuery(coaSelect).select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: window.jQuery('#modalTambahItem'),
                placeholder: '-- Pilih COA --'
            });
        }

        const updatePreviewCoa = function () {
            const selected = coaSelect.options[coaSelect.selectedIndex];
            document.getElementById('preview_kode_mak').textContent = selected?.dataset?.kode || '-';
            document.getElementById('preview_kd_akun').textContent = selected?.dataset?.kdAkun || '-';
            document.getElementById('preview_nama_akun').textContent = selected?.dataset?.nama || '-';
            document.getElementById('preview_jenis_akun').textContent = selected?.dataset?.jenis || '-';
        };

        coaSelect.addEventListener('change', updatePreviewCoa);

        const formatRupiah = (value) => {
            return 'Rp ' + new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 0,
            }).format(value || 0);
        };

        const syncNilaiPagu = () => {
            const numeric = nilaiPaguDisplay.value.replace(/[^\d]/g, '');
            nilaiPaguHidden.value = numeric;
            nilaiPaguDisplay.value = numeric ? formatRupiah(parseInt(numeric, 10)) : '';
        };

        nilaiPaguDisplay.addEventListener('input', syncNilaiPagu);
        nilaiPaguDisplay.addEventListener('blur', syncNilaiPagu);

        updatePreviewCoa();
        syncNilaiPagu();
    });
</script>
@endpush
