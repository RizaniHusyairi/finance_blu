@extends('layouts.app')
@section('title', 'Catat Meter ' . ucfirst($jenis))

@php
    $isListrik = $jenis === 'listrik';
    $unitLabel = $isListrik ? 'kWh' : 'm&sup3;';
    $unitPlain = $isListrik ? 'kWh' : 'm³';
    $editLaporan = $editLaporan ?? null;
    $editing = (bool) $editLaporan;
    $laporanCollection = method_exists($laporans, 'items') ? collect($laporans->items()) : collect($laporans);
    $statusMeta = fn ($status) => match ($status) {
        'draft' => ['Draft', 'muted', 'bi-pencil-square'],
        'dikirim_ke_admin_jasa' => ['Menunggu Tagihan', 'warning', 'bi-hourglass-split'],
        'ditolak' => ['Ditolak', 'danger', 'bi-x-circle'],
        'ditagihkan' => ['Ditagihkan', 'success', 'bi-check-circle'],
        default => [str($status)->headline(), 'muted', 'bi-circle'],
    };
    $maxUsage = max(1, (float) $laporanCollection->max('pemakaian'));
@endphp

@push('css')
@include('dashboard.partials.mitra-ui')
<style>
    /* ============ CATAT METER CONSOLE — palet amber (sama untuk listrik & air) ============ */
    .cm-scope {
        --cm-accent: #f59e0b;
        --cm-accent-2: #fbbf24;
        --cm-accent-deep: #b45309;
        --cm-glow: rgba(245,158,11,.55);
        --cm-soft: rgba(245,158,11,.14);
        --cm-panel: #0a1626;
        --cm-panel-2: #0f2138;
    }

    @keyframes cmReveal { from { opacity:0; transform:translateY(18px); } to { opacity:1; transform:translateY(0); } }
    @keyframes cmGridShift { from { background-position:0 0, 0 0; } to { background-position:40px 0, 0 40px; } }
    @keyframes cmScan { 0% { transform:translateX(-120%) skewX(-16deg); opacity:0; } 18% { opacity:.5; } 55%,100% { transform:translateX(240%) skewX(-16deg); opacity:0; } }
    @keyframes cmBolt { 0%,100% { transform:translateY(0) rotate(0); } 42% { transform:translateY(-3px) rotate(-6deg); } 60% { transform:translateY(-3px) rotate(5deg); } }
    @keyframes cmFlicker { 0%,100% { opacity:1; } 47% { opacity:1; } 48% { opacity:.74; } 49% { opacity:1; } 92% { opacity:.86; } 93% { opacity:1; } }
    @keyframes cmLiveDot { 0%,100% { box-shadow:0 0 0 0 var(--cm-glow); opacity:1; } 50% { box-shadow:0 0 0 8px transparent; opacity:.55; } }
    @keyframes cmPop { from { opacity:0; transform:translateY(12px) scale(.97); } to { opacity:1; transform:translateY(0) scale(1); } }

    /* ---------- Hero / command bar ---------- */
    .cm-hero {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        border-radius: 22px;
        padding: 26px 28px;
        color: #fff;
        background:
            radial-gradient(120% 140% at 88% -10%, var(--cm-soft), transparent 46%),
            linear-gradient(115deg, #071423 0%, #0c2138 46%, #133a63 100%);
        border: 1px solid rgba(148,163,184,.18);
        border-top: 2px solid var(--cm-accent);
        box-shadow: 0 22px 56px rgba(7,20,35,.32);
        animation: cmReveal .55s cubic-bezier(.2,.8,.2,1) both;
    }
    .cm-hero::before {
        content: ""; position: absolute; inset: 0; z-index: -2; opacity: .5;
        background-image:
            linear-gradient(to right, rgba(148,163,184,.10) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(148,163,184,.10) 1px, transparent 1px);
        background-size: 40px 40px;
        animation: cmGridShift 6s linear infinite;
        -webkit-mask-image: linear-gradient(90deg, #000, transparent 78%);
                mask-image: linear-gradient(90deg, #000, transparent 78%);
    }
    .cm-hero::after {
        content: ""; position: absolute; inset: 0; z-index: -1; width: 44%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.16), transparent);
        animation: cmScan 4.6s ease-in-out infinite;
    }
    .cm-hero-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 54px; height: 54px; flex: 0 0 54px; border-radius: 16px;
        font-size: 1.6rem; color: #07121f;
        background: linear-gradient(140deg, var(--cm-accent-2), var(--cm-accent));
        box-shadow: 0 14px 30px var(--cm-soft), inset 0 1px 0 rgba(255,255,255,.4);
    }
    .cm-hero-icon i { animation: cmBolt 2.8s ease-in-out infinite; }
    .cm-kicker { letter-spacing: .14em; font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--cm-accent-2); }
    .cm-hero h4 { font-weight: 900; letter-spacing: -.01em; }
    .cm-period-chip {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 9px 14px; border-radius: 12px;
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.16);
        color: #fff; font-weight: 800; font-size: 13px; backdrop-filter: blur(6px);
    }
    .cm-period-chip .cm-live { width: 8px; height: 8px; border-radius: 999px; background: var(--cm-accent); animation: cmLiveDot 1.8s ease-in-out infinite; }

    /* ---------- Stat tiles ---------- */
    .cm-stat {
        position: relative; overflow: hidden; height: 100%;
        border-radius: 16px; background: #fff; padding: 16px 18px;
        border: 1px solid rgba(15,23,42,.07);
        box-shadow: 0 12px 30px rgba(15,23,42,.06);
        animation: cmPop .5s ease both;
    }
    .cm-stat::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background: var(--st, #2563eb); }
    .cm-stat:nth-child(1){ animation-delay:.04s; } .cm-stat:nth-child(2){ animation-delay:.10s; } .cm-stat:nth-child(3){ animation-delay:.16s; }
    .cm-stat-ico { width:40px; height:40px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; color: var(--st); background: var(--st-soft); font-size: 1.1rem; }
    .cm-stat-val { font-size: 28px; font-weight: 900; color: #0f2138; line-height: 1; }
    .cm-stat-lbl { font-size: 10px; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; color: #94a3b8; }

    /* ---------- Console card ---------- */
    .cm-card { overflow: hidden; border: 1px solid rgba(15,23,42,.08); border-radius: 20px; background: #fff; box-shadow: 0 18px 46px rgba(15,23,42,.08); }
    .cm-card-head { display:flex; align-items:center; gap:12px; padding: 14px 18px; border-bottom: 1px solid #eef2f7; background: linear-gradient(90deg, #f8fafc, #fff); }
    .cm-card-head .ic { width:36px; height:36px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; color:#fff; background: linear-gradient(140deg, var(--cm-accent), var(--cm-accent-deep)); box-shadow: 0 8px 18px var(--cm-soft); }
    .cm-card-head h6 { margin:0; font-weight:900; color:#0f2138; }
    .cm-card-head small { color:#94a3b8; font-weight:700; }

    /* ---------- Digital meter readout (the centerpiece) ---------- */
    .cm-meter {
        position: relative; overflow: hidden;
        border-radius: 16px; padding: 18px 20px;
        background: radial-gradient(120% 130% at 85% -20%, rgba(255,255,255,.06), transparent 50%), linear-gradient(160deg, var(--cm-panel), var(--cm-panel-2));
        border: 1px solid rgba(148,163,184,.20);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.06), 0 16px 34px rgba(7,20,35,.28);
    }
    .cm-meter::after {
        content:""; position:absolute; inset:0; pointer-events:none; opacity:.5;
        background: repeating-linear-gradient(to bottom, rgba(255,255,255,.035) 0 1px, transparent 1px 3px);
    }
    .cm-meter-top { display:flex; align-items:center; justify-content:space-between; gap:10px; position:relative; z-index:1; }
    .cm-meter-tag { display:inline-flex; align-items:center; gap:7px; font-size:10px; font-weight:900; letter-spacing:.12em; text-transform:uppercase; color: var(--cm-accent-2); }
    .cm-meter-tag .dot { width:7px; height:7px; border-radius:999px; background: var(--cm-accent); animation: cmLiveDot 1.8s ease-in-out infinite; }
    .cm-meter-mode { font-size:10px; font-weight:900; letter-spacing:.1em; text-transform:uppercase; color:#7c93ad; }
    .cm-readout { position:relative; z-index:1; display:flex; align-items:baseline; gap:10px; margin-top:6px; }
    .cm-digits {
        font-family: "DS-Digital", ui-monospace, "SFMono-Regular", Menlo, Consolas, monospace;
        font-size: clamp(40px, 9vw, 58px); font-weight: 800; line-height: 1; letter-spacing: .04em;
        color: var(--cm-accent-2);
        text-shadow: 0 0 8px var(--cm-glow), 0 0 22px var(--cm-glow);
        animation: cmFlicker 5s infinite;
    }
    .cm-digits.zero { color:#41566e; text-shadow:none; }
    .cm-unit { font-size: 15px; font-weight: 900; color:#8fa6bd; }
    .cm-formula { position:relative; z-index:1; margin-top:10px; font-size:12px; font-weight:700; color:#7c93ad; font-family: ui-monospace, Menlo, Consolas, monospace; }
    .cm-formula b { color:#cde0f1; }
    .cm-warn { position:relative; z-index:1; display:none; margin-top:10px; padding:7px 11px; border-radius:9px; font-size:12px; font-weight:800; color:#fecaca; background:rgba(239,68,68,.14); border:1px solid rgba(239,68,68,.32); }
    .cm-warn.show { display:block; }

    /* ---------- Form bits ---------- */
    .cm-field-group { border:1px solid #eef2f7; border-radius:14px; background:linear-gradient(180deg,#fbfdff,#fff); padding:14px 15px; }
    .cm-field-title { display:flex; align-items:center; gap:7px; font-size:11px; font-weight:900; letter-spacing:.04em; text-transform:uppercase; color:#0f2138; }
    .cm-field-title i { color: var(--cm-accent-deep); }

    .cm-form :is(.form-control,.form-select) { border-color:#e2e8f0; border-radius:11px; min-height:44px; font-weight:600; box-shadow:0 1px 2px rgba(15,23,42,.03); }
    .cm-form :is(.form-control:focus,.form-select:focus) { border-color: var(--cm-accent); box-shadow:0 0 0 4px var(--cm-soft); }
    .cm-form .form-label { color:#475569; font-weight:800; margin-bottom:.4rem; font-size:.82rem; }
    .cm-form .select2-container--bootstrap-5 .select2-selection { border-color:#e2e8f0; border-radius:11px; min-height:44px; box-shadow:0 1px 2px rgba(15,23,42,.03); }
    .cm-form .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    .cm-form .select2-container--bootstrap-5.select2-container--open .select2-selection { border-color: var(--cm-accent); box-shadow:0 0 0 4px var(--cm-soft); }

    /* segmented mode toggle (keeps .util-type-card for JS) */
    .cm-seg { display:grid; grid-auto-flow:column; grid-auto-columns:1fr; gap:8px; padding:5px; border-radius:13px; background:#f1f5f9; }
    .util-type-card { cursor:pointer; display:block; margin:0; border-radius:10px; padding:11px 12px; text-align:center; border:1px solid transparent; background:transparent; transition:all .18s ease; user-select:none; }
    .util-type-card .form-check-input { position:absolute; opacity:0; pointer-events:none; }
    .util-type-card .seg-title { font-weight:900; color:#475569; line-height:1.05; }
    .util-type-card .seg-sub { font-size:10px; font-weight:800; letter-spacing:.04em; text-transform:uppercase; color:#94a3b8; }
    .util-type-card.selected { background:#fff; border-color:var(--cm-accent); box-shadow:0 8px 18px var(--cm-soft); }
    .util-type-card.selected .seg-title { color: var(--cm-accent-deep); }
    .util-type-card.selected .seg-sub { color: var(--cm-accent); }

    /* stan awal -> akhir connected pair */
    .cm-meter-pair { display:grid; grid-template-columns:1fr 34px 1fr; align-items:end; gap:8px; }
    .cm-meter-pair .arrow { display:flex; align-items:center; justify-content:center; height:44px; color:var(--cm-accent); font-size:1.2rem; }

    /* photo capture tiles (keep .util-file-* hooks for JS) */
    .util-file { position:relative; overflow:hidden; cursor:pointer; display:flex; align-items:center; gap:12px; min-height:78px; border:1.5px dashed #cbd5e1; border-radius:14px; background:#f8fafc; padding:10px 12px; transition:all .2s ease; }
    .util-file:hover { border-color:var(--cm-accent); background:var(--cm-soft); }
    .util-file-icon { display:inline-flex; align-items:center; justify-content:center; width:42px; height:42px; flex:0 0 42px; border-radius:12px; color:var(--cm-accent-deep); background:var(--cm-soft); font-size:1.15rem; overflow:hidden; }
    .util-file-icon img { width:100%; height:100%; object-fit:cover; }
    .util-file-name { color:#64748b; font-size:12px; font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:100%; }
    .util-file.has-file { border-style:solid; border-color:#22c55e; background:#f0fdf4; }
    .util-file.has-file .util-file-icon { color:#15803d; background:#dcfce7; }
    .util-file.has-file .util-file-name { color:#15803d; }
    .cm-mini-btn { border-radius:10px; font-weight:800; font-size:.78rem; }

    .cm-submit { border:0; border-radius:13px; font-weight:900; padding:13px; color:#07121f; background:linear-gradient(135deg,var(--cm-accent-2),var(--cm-accent)); box-shadow:0 14px 30px var(--cm-soft); transition:transform .15s ease, box-shadow .15s ease; }
    .cm-submit:hover { transform:translateY(-1px); box-shadow:0 18px 38px var(--cm-soft); color:#07121f; }

    /* ---------- History ---------- */
    .cm-usage-bar { height:6px; border-radius:999px; background:#eef2f7; overflow:hidden; margin-top:5px; }
    .cm-usage-bar span { display:block; height:100%; border-radius:999px; background:linear-gradient(90deg,var(--cm-accent),var(--cm-accent-2)); }
    .cm-thumb-link { display:inline-flex; width:38px; height:38px; border-radius:9px; overflow:hidden; border:1px solid #e2e8f0; background:#f8fafc; align-items:center; justify-content:center; color:var(--cm-accent-deep); }
    .cm-thumb-link img { width:100%; height:100%; object-fit:cover; }
    .cm-meter-trail { font-family:ui-monospace,Menlo,Consolas,monospace; font-weight:800; color:#0f2138; white-space:nowrap; }

    /* reading history as compact cards (fits the narrow column without cramping a 7-col table) */
    .cm-reading-list { display:flex; flex-direction:column; gap:10px; padding:14px 16px; }
    .cm-reading-item { border:1px solid #eef2f7; border-radius:14px; background:linear-gradient(180deg,#fbfdff,#fff); padding:13px 14px; transition:border-color .18s ease, box-shadow .18s ease; animation:cmPop .4s ease both; }
    .cm-reading-item:hover { border-color:#e2e8f0; box-shadow:0 10px 24px rgba(15,23,42,.07); }
    .cm-reading-head { display:flex; align-items:flex-start; gap:10px; }
    .cm-reading-period { flex:0 0 auto; display:inline-flex; flex-direction:column; align-items:center; justify-content:center; min-width:46px; padding:6px 8px; border-radius:11px; background:var(--cm-soft); color:var(--cm-accent-deep); font-family:ui-monospace,Menlo,Consolas,monospace; line-height:1.05; }
    .cm-reading-period .mo { font-size:17px; font-weight:900; }
    .cm-reading-period .yr { font-size:10px; font-weight:800; opacity:.85; }
    .cm-reading-mitra { flex:1 1 auto; min-width:0; }
    .cm-reading-mitra .nm { font-weight:800; color:#0f2138; line-height:1.2; }
    .cm-reading-mitra .sv { font-size:12px; color:#94a3b8; font-weight:600; }
    .cm-reading-meta { display:flex; align-items:center; flex-wrap:wrap; gap:8px; margin-top:11px; }
    .cm-reading-meta .small { white-space:nowrap; }
    .cm-reading-foot { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-top:11px; }
    .cm-reading-proofs { display:flex; gap:6px; align-items:center; }
    .cm-reading-actions { display:flex; gap:6px; align-items:center; }

    /* ---------- Multi-entry (batch) ---------- */
    .cm-tips-strip { display:flex; align-items:flex-start; gap:10px; padding:11px 14px; border-radius:12px; background:var(--cm-soft); color:#475569; font-size:13px; font-weight:600; }
    .cm-tips-strip i { color:var(--cm-accent-deep); margin-top:2px; }
    .cm-entry { position:relative; border:1px solid #e6ebf2; border-radius:16px; background:linear-gradient(180deg,#fcfdff,#fff); padding:16px; box-shadow:0 8px 22px rgba(15,23,42,.04); animation:cmPop .4s ease both; }
    .cm-entry + .cm-entry { margin-top:16px; }
    .cm-entry-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; padding-bottom:12px; border-bottom:1px dashed #e2e8f0; }
    .cm-entry-badge { display:inline-flex; align-items:center; gap:8px; font-weight:900; color:#0f2138; font-size:13px; }
    .cm-entry-badge .n { width:26px; height:26px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; background:var(--cm-soft); color:var(--cm-accent-deep); font-size:13px; }
    .je-remove { border-radius:9px; font-weight:800; }
    .cm-actionbar { display:flex; align-items:stretch; gap:12px; flex-wrap:wrap; }
    .cm-add { display:inline-flex; align-items:center; justify-content:center; border:1.5px dashed var(--cm-accent); background:var(--cm-soft); color:var(--cm-accent-deep); border-radius:13px; font-weight:900; padding:13px 22px; transition:transform .15s ease, background .15s ease; }
    .cm-add:hover { background:#fff; transform:translateY(-1px); }

    @media (max-width:575.98px){ .util-file{ align-items:flex-start; } .cm-meter-pair{ grid-template-columns:1fr; } .cm-meter-pair .arrow{ height:auto; transform:rotate(90deg); } .cm-actionbar .cm-add, .cm-actionbar .cm-submit{ width:100%; flex:1 1 100%; } }
    @media (prefers-reduced-motion: reduce){ .cm-hero, .cm-hero::before, .cm-hero::after, .cm-hero-icon i, .cm-digits, .cm-stat, .cm-meter-tag .dot, .cm-period-chip .cm-live { animation:none !important; } }
</style>
@endpush

@section('content')
<div class="cm-scope">

    {{-- ===== Hero / command bar ===== --}}
    <div class="cm-hero mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
            <div class="d-flex align-items-start gap-3">
                <span class="cm-hero-icon"><i class="bi {{ $isListrik ? 'bi-lightning-charge-fill' : 'bi-droplet-fill' }}"></i></span>
                <div>
                    <div class="cm-kicker mb-1">Konsol Pencatatan Utilitas</div>
                    <h4 class="mb-1">Catat Meter {{ ucfirst($jenis) }}</h4>
                    <p class="mb-0 small fw-semibold text-white-50">Rekam stan meter bulanan, lampirkan bukti foto, lalu teruskan ke Admin Jasa untuk ditagihkan.</p>
                </div>
            </div>
            <div class="d-flex flex-column align-items-start align-items-lg-end gap-2">
                <span class="cm-period-chip"><span class="cm-live"></span><i class="bi bi-broadcast"></i>Periode aktif · {{ now()->translatedFormat('F Y') }}</span>
                <span class="small fw-semibold text-white-50"><i class="bi bi-rulers me-1"></i>Satuan terukur: {!! $unitLabel !!}</span>
            </div>
        </div>
    </div>

    {{-- ===== Alerts ===== --}}
    @if(session('success'))
        <div class="alert alert-success rounded-3"><i class="bi bi-check-circle me-1"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger rounded-3"><i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger rounded-3">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===== Stat tiles ===== --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="cm-stat" style="--st:#2563eb;--st-soft:#dbeafe;">
                <div class="d-flex align-items-start justify-content-between gap-3">
                    <div>
                        <div class="cm-stat-lbl mb-2">Laporan Ditampilkan</div>
                        <div class="cm-stat-val">{{ $laporanCollection->count() }}</div>
                        <div class="small fw-semibold text-muted mt-2">Riwayat pada halaman ini</div>
                    </div>
                    <span class="cm-stat-ico"><i class="bi bi-table"></i></span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="cm-stat" style="--st:#d97706;--st-soft:#fef3c7;">
                <div class="d-flex align-items-start justify-content-between gap-3">
                    <div>
                        <div class="cm-stat-lbl mb-2">Menunggu Proses</div>
                        <div class="cm-stat-val">{{ $laporanCollection->where('status', 'dikirim_ke_admin_jasa')->count() }}</div>
                        <div class="small fw-semibold text-muted mt-2">Sudah dikirim ke Admin Jasa</div>
                    </div>
                    <span class="cm-stat-ico"><i class="bi bi-hourglass-split"></i></span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="cm-stat" style="--st:#15803d;--st-soft:#dcfce7;">
                <div class="d-flex align-items-start justify-content-between gap-3">
                    <div>
                        <div class="cm-stat-lbl mb-2">Sudah Ditagihkan</div>
                        <div class="cm-stat-val">{{ $laporanCollection->where('status', 'ditagihkan')->count() }}</div>
                        <div class="small fw-semibold text-muted mt-2">Laporan sudah menjadi tagihan</div>
                    </div>
                    <span class="cm-stat-ico"><i class="bi bi-receipt-cutoff"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7 col-lg-7">
        {{-- ===== Reading console (kiri) ===== --}}
        <div class="cm-card">
        <div class="cm-card-head">
            <span class="ic"><i class="bi {{ $editing ? 'bi-pencil' : 'bi-plus-lg' }}"></i></span>
            <div>
                <h6>{{ $editing ? 'Ubah Pembacaan Meter' : 'Pembacaan Meter Baru' }}</h6>
                <small>{{ $editing ? 'Periode '.str_pad($editLaporan->bulan, 2, '0', STR_PAD_LEFT).'/'.$editLaporan->tahun : 'Catat stan & bukti meter' }}</small>
            </div>
        </div>
        <div class="p-3 p-md-4">
            @if($editing)
                <div class="alert alert-info d-flex align-items-center justify-content-between rounded-3 py-2 px-3 mb-3">
                    <span class="small fw-semibold"><i class="bi bi-info-circle me-1"></i>Anda sedang mengubah laporan.</span>
                    <a href="{{ route('utilitas.dashboard') }}" class="btn btn-sm btn-light border">Batal</a>
                </div>
            @endif

            @if($editing)
            {{-- ============ EDIT: single entry (flat fields, posting ke update) ============ --}}
            <div class="row g-4">
                {{-- ---- LEFT: live digital meter readout + tips ---- --}}
                <div class="col-lg-5 col-xl-4">
                    <div class="cm-meter">
                        <div class="cm-meter-top">
                            <span class="cm-meter-tag"><span class="dot"></span>Live · Pemakaian</span>
                            <span class="cm-meter-mode" id="cmMode">MODE METER</span>
                        </div>
                        <div class="cm-readout">
                            <span class="cm-digits zero" id="cmReadout">0</span>
                            <span class="cm-unit">{!! $unitLabel !!}</span>
                        </div>
                        <div class="cm-formula" id="cmFormula">Isi stan awal &amp; akhir untuk menghitung otomatis.</div>
                        <div class="cm-warn" id="cmWarn"><i class="bi bi-exclamation-triangle me-1"></i>Stan akhir lebih kecil dari stan awal.</div>
                    </div>

                    <div class="cm-field-group mt-3">
                        <div class="cm-field-title mb-2"><i class="bi bi-lightbulb"></i> Tips Pencatatan</div>
                        <ul class="small text-muted mb-0 ps-3" style="line-height:1.75;">
                            <li>Pastikan angka pada foto meter terbaca jelas.</li>
                            <li>Stan awal terisi otomatis dari periode sebelumnya.</li>
                            <li>Gunakan mode <b>Flat</b> bila pelanggan tanpa meter.</li>
                        </ul>
                    </div>
                </div>

                {{-- ---- RIGHT: input form (lebih lebar) ---- --}}
                <div class="col-lg-7 col-xl-8">
                    <form action="{{ $editing ? route('utilitas.update', $editLaporan->id) : route('utilitas.store') }}" method="POST" enctype="multipart/form-data" class="cm-form" id="utilitasForm">
                        @csrf
                        @if($editing) @method('PUT') @endif
                        <input type="hidden" name="jenis" value="{{ $jenis }}">
                        <input type="hidden" name="layanan_jasa_id" value="{{ $layanan->id }}">

                        {{-- mode toggle (segmented, keeps .util-type-card for JS) --}}
                        <div class="cm-field-group mb-3">
                            <div class="cm-field-title mb-2"><i class="bi bi-speedometer2"></i> Jenis Pencatatan</div>
                            <div class="cm-seg">
                                <label class="util-type-card {{ old('tipe_perhitungan', $editing ? $editLaporan->tipe_perhitungan : 'kwh') === 'kwh' ? 'selected' : '' }}" for="tipe_kwh">
                                    <input class="form-check-input" type="radio" name="tipe_perhitungan" id="tipe_kwh" value="kwh" {{ old('tipe_perhitungan', $editing ? $editLaporan->tipe_perhitungan : 'kwh') === 'kwh' ? 'checked' : '' }}>
                                    <div class="seg-title">{!! $unitLabel !!} Meter</div>
                                    <div class="seg-sub">Stan awal &amp; akhir</div>
                                </label>
                                @if($isListrik || ($editing && $editLaporan->tipe_perhitungan === 'flat'))
                                <label class="util-type-card {{ old('tipe_perhitungan', $editing ? $editLaporan->tipe_perhitungan : 'kwh') === 'flat' ? 'selected' : '' }}" for="tipe_flat">
                                    <input class="form-check-input" type="radio" name="tipe_perhitungan" id="tipe_flat" value="flat" {{ old('tipe_perhitungan', $editing ? $editLaporan->tipe_perhitungan : 'kwh') === 'flat' ? 'checked' : '' }}>
                                    <div class="seg-title">Flat</div>
                                    <div class="seg-sub">Input manual</div>
                                </label>
                                @endif
                            </div>
                        </div>

                        {{-- customer + period --}}
                        <div class="cm-field-group mb-3">
                            <div class="cm-field-title mb-3"><i class="bi bi-building"></i> Pelanggan &amp; Periode</div>
                            <div class="mb-3">
                                <label class="form-label">Mitra / Pelanggan</label>
                                <select name="mitra_jasa_id" class="form-select select2" required>
                                    <option value="">Pilih Mitra...</option>
                                    @foreach($mitras as $mitra)
                                        <option value="{{ $mitra->id }}" {{ old('mitra_jasa_id', $editing ? $editLaporan->mitra_jasa_id : '') == $mitra->id ? 'selected' : '' }}>{{ $mitra->nama_mitra }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-3">
                                <div class="col-7">
                                    <label class="form-label">Bulan</label>
                                    <select name="bulan" class="form-select" required>
                                        @for($i=1; $i<=12; $i++)
                                            <option value="{{ $i }}" {{ (old('bulan', $editing ? $editLaporan->bulan : now()->month) == $i) ? 'selected' : '' }}>
                                                {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-5">
                                    <label class="form-label">Tahun</label>
                                    <input type="number" name="tahun" class="form-control" value="{{ old('tahun', $editing ? $editLaporan->tahun : now()->year) }}" required>
                                </div>
                            </div>
                        </div>

                        {{-- flat manual --}}
                        <div id="section-flat" class="cm-field-group mb-3" style="display:none;">
                            <div class="cm-field-title mb-3"><i class="bi bi-keyboard"></i> Pemakaian Manual</div>
                            <label class="form-label">Jumlah Pemakaian ({!! $unitLabel !!})</label>
                            <input type="number" name="pemakaian_manual" class="form-control" min="0" step="0.01" value="{{ old('pemakaian_manual', $editing && $editLaporan->tipe_perhitungan === 'flat' ? $editLaporan->pemakaian : '') }}" placeholder="Masukkan jumlah pemakaian">
                        </div>

                        {{-- kwh meter + photos --}}
                        <div id="section-kwh" class="cm-field-group mb-3">
                            <div class="cm-field-title mb-3"><i class="bi bi-calculator"></i> Data Meter</div>
                            <div class="cm-meter-pair mb-3">
                                <div>
                                    <label class="form-label">Stan Awal</label>
                                    <input type="number" id="stan_awal" name="stan_awal" class="form-control" min="0" value="{{ old('stan_awal', $editing ? $editLaporan->stan_awal : '') }}" placeholder="0">
                                </div>
                                <div class="arrow"><i class="bi bi-arrow-right"></i></div>
                                <div>
                                    <label class="form-label">Stan Akhir</label>
                                    <input type="number" id="stan_akhir" name="stan_akhir" class="form-control" min="0" value="{{ old('stan_akhir', $editing ? $editLaporan->stan_akhir : '') }}" placeholder="0">
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Bukti Awal {!! $unitLabel !!} @unless($editing && $editLaporan->file_bukti_awal)<span class="text-danger">*</span>@endunless</label>
                                    <div class="util-file util-file-trigger" data-target="file_bukti_awal" role="button" tabindex="0">
                                        <span class="util-file-icon" data-thumb="file_bukti_awal"><i class="bi bi-cloud-arrow-up"></i></span>
                                        <span class="min-w-0">
                                            <span class="d-block fw-bold">Foto Meteran Awal</span>
                                            <span class="d-block util-file-name">Pilih file atau ambil foto. Max 5MB.</span>
                                        </span>
                                    </div>
                                    <div class="d-flex gap-2 mt-2">
                                        <button type="button" class="btn btn-sm btn-light border flex-fill cm-mini-btn util-pick" data-target="file_bukti_awal"><i class="bi bi-folder2-open me-1"></i>File</button>
                                        <button type="button" class="btn btn-sm btn-light border flex-fill cm-mini-btn util-cam" data-target="file_bukti_awal"><i class="bi bi-camera me-1"></i>Kamera</button>
                                    </div>
                                    <input type="file" id="file_bukti_awal" name="file_bukti_awal" class="d-none util-file-input" accept="image/*">
                                    @if($editing && $editLaporan->file_bukti_awal)
                                        <div class="small text-muted mt-1"><i class="bi bi-paperclip me-1"></i><a href="{{ route('secure-file', ['utilitas', $editLaporan->id, 'file_bukti_awal']) }}" target="_blank">Foto saat ini</a> — biarkan kosong untuk dipertahankan.</div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Bukti Akhir {!! $unitLabel !!} @unless($editing && $editLaporan->file_bukti)<span class="text-danger">*</span>@endunless</label>
                                    <div class="util-file util-file-trigger" data-target="file_bukti" role="button" tabindex="0">
                                        <span class="util-file-icon" data-thumb="file_bukti"><i class="bi bi-cloud-arrow-up"></i></span>
                                        <span class="min-w-0">
                                            <span class="d-block fw-bold">Foto Meteran Akhir</span>
                                            <span class="d-block util-file-name">Pilih file atau ambil foto. Max 5MB.</span>
                                        </span>
                                    </div>
                                    <div class="d-flex gap-2 mt-2">
                                        <button type="button" class="btn btn-sm btn-light border flex-fill cm-mini-btn util-pick" data-target="file_bukti"><i class="bi bi-folder2-open me-1"></i>File</button>
                                        <button type="button" class="btn btn-sm btn-light border flex-fill cm-mini-btn util-cam" data-target="file_bukti"><i class="bi bi-camera me-1"></i>Kamera</button>
                                    </div>
                                    <input type="file" id="file_bukti" name="file_bukti" class="d-none util-file-input" accept="image/*">
                                    @if($editing && $editLaporan->file_bukti)
                                        <div class="small text-muted mt-1"><i class="bi bi-paperclip me-1"></i><a href="{{ route('secure-file', ['utilitas', $editLaporan->id, 'file_bukti']) }}" target="_blank">Foto saat ini</a> — biarkan kosong untuk dipertahankan.</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <button class="cm-submit w-100">
                            <i class="bi {{ $editing ? 'bi-check2-circle' : 'bi-save' }} me-1"></i>{{ $editing ? 'Perbarui Laporan' : 'Simpan Laporan' }}
                        </button>
                    </form>
                </div>
            </div>

            @else
            {{-- ============ CREATE: multi-entry batch (banyak laporan sekaligus) ============ --}}
            <div class="cm-tips-strip mb-3">
                <i class="bi bi-lightbulb"></i>
                <span>Atur <b>mitra, jenis pencatatan, dan periode</b> sekali di atas. Tombol <b>Tambah Data Meter</b> hanya menambah baris pembacaan meter, lalu <b>Simpan Laporan</b> menyimpan semuanya.</span>
            </div>
            <form action="{{ route('utilitas.store') }}" method="POST" enctype="multipart/form-data" class="cm-form" id="utilitasForm">
                @csrf
                <input type="hidden" name="jenis" value="{{ $jenis }}">
                <input type="hidden" name="layanan_jasa_id" value="{{ $layanan->id }}">
                @php $sharedTipe = $isListrik ? old('tipe_perhitungan', 'kwh') : 'kwh'; @endphp

                {{-- Pengaturan bersama: berlaku untuk semua data meter di bawah --}}
                <div class="cm-field-group mb-3">
                    <div class="cm-field-title mb-2"><i class="bi bi-building"></i> Mitra / Pelanggan</div>
                    <select name="mitra_jasa_id" id="cmMitra" class="form-select select2" required>
                        <option value="">Pilih Mitra...</option>
                        @foreach($mitras as $mitra)
                            <option value="{{ $mitra->id }}" {{ old('mitra_jasa_id') == $mitra->id ? 'selected' : '' }}>{{ $mitra->nama_mitra }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Semua data meter di bawah dicatat untuk mitra ini.</div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-lg-6">
                        <div class="cm-field-group h-100">
                            <div class="cm-field-title mb-2"><i class="bi bi-speedometer2"></i> Jenis Pencatatan</div>
                            <div class="cm-seg">
                                <label class="util-type-card {{ $sharedTipe === 'kwh' ? 'selected' : '' }}">
                                    <input class="form-check-input" type="radio" name="tipe_perhitungan" value="kwh" {{ $sharedTipe === 'kwh' ? 'checked' : '' }}>
                                    <div class="seg-title">{!! $unitLabel !!} Meter</div>
                                    <div class="seg-sub">Stan awal &amp; akhir</div>
                                </label>
                                @if($isListrik)
                                <label class="util-type-card {{ $sharedTipe === 'flat' ? 'selected' : '' }}">
                                    <input class="form-check-input" type="radio" name="tipe_perhitungan" value="flat" {{ $sharedTipe === 'flat' ? 'checked' : '' }}>
                                    <div class="seg-title">Flat</div>
                                    <div class="seg-sub">Input manual</div>
                                </label>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="cm-field-group h-100">
                            <div class="cm-field-title mb-2"><i class="bi bi-calendar-range"></i> Periode</div>
                            <div class="row g-3">
                                <div class="col-7">
                                    <label class="form-label">Bulan</label>
                                    <select name="bulan" id="cmBulan" class="form-select" required>
                                        @for($i=1; $i<=12; $i++)
                                            <option value="{{ $i }}" {{ old('bulan', now()->month) == $i ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-5">
                                    <label class="form-label">Tahun</label>
                                    <input type="number" name="tahun" id="cmTahun" class="form-control" value="{{ old('tahun', now()->year) }}" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Readout TUNGGAL: total pemakaian gabungan semua data meter --}}
                <div class="cm-meter mb-3" id="cmTotalMeter">
                    <div class="cm-meter-top">
                        <span class="cm-meter-tag"><span class="dot"></span>Live · Total Pemakaian</span>
                        <span class="cm-meter-mode" id="cmTotalMode">MODE METER · {{ strtoupper($unitPlain) }}</span>
                    </div>
                    <div class="cm-readout">
                        <span class="cm-digits zero" id="cmTotalReadout">0</span>
                        <span class="cm-unit">{!! $unitLabel !!}</span>
                    </div>
                    <div class="cm-formula" id="cmTotalFormula">Isi data meter untuk menghitung otomatis.</div>
                    <div class="cm-warn" id="cmTotalWarn"><i class="bi bi-exclamation-triangle me-1"></i>Ada data meter dengan stan akhir lebih kecil dari stan awal.</div>
                </div>

                <div id="cmEntries"></div>
                <div class="cm-actionbar mt-2">
                    <button type="button" id="cmAddEntry" class="cm-add"><i class="bi bi-plus-lg me-1"></i>Tambah Data Meter</button>
                    <button type="submit" class="cm-submit flex-fill"><i class="bi bi-save me-1"></i>Simpan Laporan</button>
                </div>
            </form>
            @endif
        </div>
        </div>
        </div>{{-- /col kiri --}}

    @unless($editing)
    {{-- Template entri laporan (di-clone oleh tombol Tambah Laporan) --}}
    <template id="cmEntryTemplate">
        <div class="cm-entry" data-index="__IDX__">
            <div class="cm-entry-head">
                <span class="cm-entry-badge"><span class="n">1</span> Data Meter <span class="cm-entry-no">1</span></span>
                <button type="button" class="btn btn-sm btn-outline-danger je-remove" title="Hapus data meter ini"><i class="bi bi-x-lg me-1"></i>Hapus</button>
            </div>
            <div class="cm-field-group mb-3 je-flat" style="display:none;">
                        <div class="cm-field-title mb-3"><i class="bi bi-keyboard"></i> Pemakaian Manual</div>
                        <label class="form-label">Jumlah Pemakaian ({!! $unitLabel !!})</label>
                        <input type="number" name="laporan[__IDX__][pemakaian_manual]" class="form-control je-manual" min="0" step="0.01" placeholder="Masukkan jumlah pemakaian">
                    </div>

                    <div class="cm-field-group mb-0 je-kwh">
                        <div class="cm-field-title mb-3"><i class="bi bi-calculator"></i> Data Meter</div>
                        <div class="cm-meter-pair mb-3">
                            <div>
                                <label class="form-label">Stan Awal</label>
                                <input type="number" name="laporan[__IDX__][stan_awal]" class="form-control je-stan-awal" min="0" placeholder="0">
                            </div>
                            <div class="arrow"><i class="bi bi-arrow-right"></i></div>
                            <div>
                                <label class="form-label">Stan Akhir</label>
                                <input type="number" name="laporan[__IDX__][stan_akhir]" class="form-control je-stan-akhir" min="0" placeholder="0">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Bukti Awal {!! $unitLabel !!} <span class="text-danger">*</span></label>
                                <div class="util-file util-file-trigger" data-role="awal" role="button" tabindex="0">
                                    <span class="util-file-icon" data-thumb-role="awal"><i class="bi bi-cloud-arrow-up"></i></span>
                                    <span class="min-w-0">
                                        <span class="d-block fw-bold">Foto Meteran Awal</span>
                                        <span class="d-block util-file-name">Pilih file atau ambil foto. Max 5MB.</span>
                                    </span>
                                </div>
                                <div class="d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-sm btn-light border flex-fill cm-mini-btn util-pick" data-role="awal"><i class="bi bi-folder2-open me-1"></i>File</button>
                                    <button type="button" class="btn btn-sm btn-light border flex-fill cm-mini-btn util-cam" data-role="awal"><i class="bi bi-camera me-1"></i>Kamera</button>
                                </div>
                                <input type="file" name="laporan[__IDX__][file_bukti_awal]" class="d-none util-file-input" data-role="awal" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bukti Akhir {!! $unitLabel !!} <span class="text-danger">*</span></label>
                                <div class="util-file util-file-trigger" data-role="akhir" role="button" tabindex="0">
                                    <span class="util-file-icon" data-thumb-role="akhir"><i class="bi bi-cloud-arrow-up"></i></span>
                                    <span class="min-w-0">
                                        <span class="d-block fw-bold">Foto Meteran Akhir</span>
                                        <span class="d-block util-file-name">Pilih file atau ambil foto. Max 5MB.</span>
                                    </span>
                                </div>
                                <div class="d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-sm btn-light border flex-fill cm-mini-btn util-pick" data-role="akhir"><i class="bi bi-folder2-open me-1"></i>File</button>
                                    <button type="button" class="btn btn-sm btn-light border flex-fill cm-mini-btn util-cam" data-role="akhir"><i class="bi bi-camera me-1"></i>Kamera</button>
                                </div>
                                <input type="file" name="laporan[__IDX__][file_bukti]" class="d-none util-file-input" data-role="akhir" accept="image/*">
                            </div>
                        </div>
                    </div>
        </div>
    </template>
    @endunless

        <div class="col-xl-5 col-lg-5">
        {{-- ===== Riwayat Pembacaan (kanan) ===== --}}
        <div class="cm-card">
                <div class="cm-card-head">
                    <span class="ic"><i class="bi bi-clock-history"></i></span>
                    <div>
                        <h6>Riwayat Pembacaan {{ ucfirst($jenis) }}</h6>
                        <small>Stan meter dan status proses tiap periode</small>
                    </div>
                </div>
                <div class="cm-reading-list">
                    @forelse($laporans as $lap)
                        @php($badge = $statusMeta($lap->status))
                        @php($pct = min(100, round(((float) $lap->pemakaian / $maxUsage) * 100)))
                        <div class="cm-reading-item">
                            <div class="cm-reading-head">
                                <div class="cm-reading-period">
                                    <span class="mo">{{ str_pad($lap->bulan, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="yr">{{ $lap->tahun }}</span>
                                </div>
                                <div class="cm-reading-mitra">
                                    <div class="nm">{{ $lap->mitraJasa->nama_mitra ?? '-' }}</div>
                                    <div class="sv">{{ $lap->layananJasa->nama_layanan ?? 'Utilitas' }}</div>
                                </div>
                                <span class="mp-soft-badge {{ $badge[1] }}" title="{{ $lap->status == 'ditolak' ? $lap->catatan_admin_jasa : '' }}">
                                    <i class="bi {{ $badge[2] }}"></i>{{ $badge[0] }}
                                </span>
                            </div>

                            <div class="cm-reading-meta">
                                <span class="mp-soft-badge {{ $lap->tipe_perhitungan == 'kwh' ? 'info' : 'muted' }}">
                                    {{ $lap->tipe_perhitungan == 'kwh' ? ($lap->jenis == 'listrik' ? 'KWH' : 'M3') : 'FLAT' }}
                                </span>
                                @if($lap->tipe_perhitungan == 'kwh')
                                    <span class="cm-meter-trail">{{ $lap->stan_awal }} &rarr; {{ $lap->stan_akhir }}</span>
                                @else
                                    <span class="cm-meter-trail">Flat manual</span>
                                @endif
                                <span class="small text-muted ms-auto">= {{ number_format((float) $lap->pemakaian, 0, ',', '.') }} {{ $isListrik ? 'kWh' : 'm³' }}</span>
                            </div>
                            <div class="cm-usage-bar"><span style="width:{{ $pct }}%"></span></div>

                            <div class="cm-reading-foot">
                                <div class="cm-reading-proofs">
                                    @if($lap->file_bukti_awal)
                                        <a href="{{ route('secure-file', ['utilitas', $lap->id, 'file_bukti_awal']) }}" target="_blank" class="cm-thumb-link" title="Bukti awal" aria-label="Bukti awal"><img src="{{ route('secure-file', ['utilitas', $lap->id, 'file_bukti_awal']) }}" alt="Bukti awal" loading="lazy"></a>
                                    @endif
                                    @if($lap->file_bukti)
                                        <a href="{{ route('secure-file', ['utilitas', $lap->id, 'file_bukti']) }}" target="_blank" class="cm-thumb-link" title="Bukti akhir" aria-label="Bukti akhir"><img src="{{ route('secure-file', ['utilitas', $lap->id, 'file_bukti']) }}" alt="Bukti akhir" loading="lazy"></a>
                                    @endif
                                    @if(!$lap->file_bukti && !$lap->file_bukti_awal)
                                        <span class="text-muted small">Tanpa bukti</span>
                                    @endif
                                </div>
                                <div class="cm-reading-actions">
                                    @if($lap->status == 'draft' || $lap->status == 'ditolak')
                                        <a href="{{ route('utilitas.dashboard', ['edit' => $lap->id]) }}#utilitasForm" class="btn btn-sm btn-light border text-primary jasa-icon-btn" title="Ubah laporan" aria-label="Ubah laporan"><i class="bi bi-pencil"></i></a>
                                        <form action="{{ route('utilitas.submit', $lap->id) }}" method="POST">
                                            @csrf
                                            <button class="btn btn-sm btn-primary jasa-icon-btn" title="Kirim ke Admin Jasa" aria-label="Kirim ke Admin Jasa"><i class="bi bi-send"></i></button>
                                        </form>
                                        <form action="{{ route('utilitas.destroy', $lap->id) }}" method="POST" onsubmit="return confirm('Hapus laporan?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger jasa-icon-btn" title="Hapus laporan" aria-label="Hapus laporan"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @elseif($lap->status == 'ditagihkan' && $lap->tagihan_jasa_id)
                                        <span class="mp-soft-badge success"><i class="bi bi-check-circle"></i>Selesai</span>
                                    @else
                                        <span class="text-muted small">&mdash;</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="mp-empty d-flex flex-column align-items-center justify-content-center text-center py-4">
                            <span class="mp-empty-icon"><i class="bi bi-inbox"></i></span>
                            <div class="fw-bold">Belum ada riwayat laporan.</div>
                            <div class="small">Laporan yang disimpan akan tampil di sini.</div>
                        </div>
                    @endforelse
                </div>
                @if($laporans->hasPages())
                    <div class="card-footer bg-white border-0 pt-3">
                        {{ $laporans->links() }}
                    </div>
                @endif
            </div>
        </div>{{-- /col kanan --}}
    </div>{{-- /row --}}
</div>
@endsection

@push('script')
@if($editing)
{{-- ===== EDIT MODE: single-entry script (id-based) ===== --}}
<script>
    $(document).ready(function() {
        var utilEditing = {{ $editing ? 'true' : 'false' }};
        var UNIT = @json($unitPlain);
        var nf = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });

        // Aktifkan select2 bila tersedia (guarded agar tidak dobel-init dengan global).
        if ($.fn && typeof $.fn.select2 === 'function') {
            $('.cm-form .select2').each(function () {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({ theme: 'bootstrap-5', width: '100%' });
                }
            });
        }

        var $readout = $('#cmReadout');
        var $formula = $('#cmFormula');
        var $warn = $('#cmWarn');
        var $mode = $('#cmMode');

        function paintReadout(value, formulaHtml) {
            var v = isNaN(value) ? 0 : value;
            $readout.text(nf.format(Math.max(0, v))).toggleClass('zero', !(v > 0));
            if (formulaHtml !== undefined) $formula.html(formulaHtml);
        }

        function refreshTypeCards() {
            $('.util-type-card').removeClass('selected');
            $('input[name="tipe_perhitungan"]:checked').closest('.util-type-card').addClass('selected');
        }

        function recalcKwh() {
            var awal = parseFloat($('#stan_awal').val());
            var akhir = parseFloat($('#stan_akhir').val());
            var hasBoth = !isNaN(awal) && !isNaN(akhir);
            var pemakaian = hasBoth ? (akhir - awal) : 0;

            $warn.toggleClass('show', hasBoth && akhir < awal);

            if (!hasBoth) {
                paintReadout(0, 'Isi stan awal &amp; akhir untuk menghitung otomatis.');
                return;
            }
            paintReadout(pemakaian, '<b>' + nf.format(akhir) + '</b> &minus; <b>' + nf.format(awal) + '</b> = <b>' + nf.format(Math.max(0, pemakaian)) + '</b> ' + UNIT);
        }

        function recalcFlat() {
            var val = parseFloat($('[name="pemakaian_manual"]').val());
            $warn.removeClass('show');
            if (isNaN(val)) {
                paintReadout(0, 'Masukkan jumlah pemakaian flat (manual).');
            } else {
                paintReadout(val, 'Input manual = <b>' + nf.format(Math.max(0, val)) + '</b> ' + UNIT);
            }
        }

        function toggleSections() {
            var tipe = $('input[name="tipe_perhitungan"]:checked').val();
            refreshTypeCards();

            if (tipe === 'kwh') {
                $('#section-kwh').show();
                $('#section-flat').hide();
                $('[name="pemakaian_manual"]').removeAttr('required');
                $('#stan_awal, #stan_akhir').attr('required', true);
                $mode.text('MODE METER · ' + UNIT);
                recalcKwh();
            } else {
                $('#section-kwh').hide();
                $('#section-flat').show();
                $('[name="pemakaian_manual"]').attr('required', true);
                $('#stan_awal, #stan_akhir').removeAttr('required');
                $mode.text('MODE FLAT · MANUAL');
                recalcFlat();
            }
        }

        $('input[name="tipe_perhitungan"]').on('change', toggleSections);
        $('#stan_awal, #stan_akhir').on('input', recalcKwh);
        $('[name="pemakaian_manual"]').on('input', recalcFlat);
        toggleSections();

        // ---- File pick / camera + live thumbnail preview ----
        function triggerInput(id, useCamera) {
            var inp = document.getElementById(id);
            if (!inp) return;
            if (useCamera) { inp.setAttribute('capture', 'environment'); }
            else { inp.removeAttribute('capture'); }
            inp.click();
        }

        $('.util-file-trigger, .util-pick').on('click', function() {
            triggerInput($(this).data('target'), false);
        });
        $('.util-cam').on('click', function() {
            triggerInput($(this).data('target'), true);
        });
        $('.util-file-trigger').on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                triggerInput($(this).data('target'), false);
            }
        });

        $('.util-file-input').on('change', function() {
            var file = this.files && this.files.length ? this.files[0] : null;
            var fileName = file ? file.name : '';
            var zone = $('.util-file-trigger[data-target="' + this.id + '"]');
            var thumb = $('.util-file-icon[data-thumb="' + this.id + '"]');

            zone.toggleClass('has-file', !!fileName);
            zone.find('.util-file-name').text(fileName || 'Pilih file atau ambil foto. Max 5MB.');

            if (file && /^image\//.test(file.type)) {
                var url = URL.createObjectURL(file);
                thumb.html('<img src="' + url + '" alt="preview">');
            } else {
                thumb.html('<i class="bi bi-cloud-arrow-up"></i>');
            }
        });

        // ---- Auto-fetch stan awal from previous period ----
        function fetchLastStanAkhir() {
            // Saat mengubah laporan, jangan timpa stan awal yang sudah dimuat.
            if (utilEditing && $('#stan_awal').val() !== '') {
                return;
            }
            var mitra_id = $('select[name="mitra_jasa_id"]').val();
            var bulan = $('select[name="bulan"]').val();
            var tahun = $('input[name="tahun"]').val();
            var tipe = $('input[name="tipe_perhitungan"]:checked').val();

            if (mitra_id && bulan && tahun && tipe === 'kwh') {
                $.ajax({
                    url: '{{ route("utilitas.last-stan-akhir") }}',
                    type: 'GET',
                    data: {
                        mitra_jasa_id: mitra_id,
                        layanan_jasa_id: '{{ $layanan->id }}',
                        bulan: bulan,
                        tahun: tahun
                    },
                    success: function(res) {
                        $('#stan_awal').val(res.stan_akhir);
                        recalcKwh();
                    }
                });
            }
        }

        $('select[name="mitra_jasa_id"], select[name="bulan"], input[name="tahun"]').on('change', fetchLastStanAkhir);
        $('input[name="tipe_perhitungan"]').on('change', function() {
            if ($(this).val() === 'kwh') {
                fetchLastStanAkhir();
            }
        });
    });
</script>
@else
{{-- ===== CREATE MODE: multi-entry batch script (class-based, per-entry) ===== --}}
<script>
    $(document).ready(function () {
        var UNIT = @json($unitPlain);
        var LAYANAN_ID = '{{ $layanan->id }}';
        var LAST_STAN_URL = '{{ route("utilitas.last-stan-akhir") }}';
        var nf = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
        var seq = 0;

        function sharedTipe() { return $('input[name="tipe_perhitungan"]:checked').val() || 'kwh'; }

        // Readout TUNGGAL: jumlahkan pemakaian seluruh data meter.
        function recalc() {
            var tipe = sharedTipe();
            var total = 0, anyWarn = false, n = 0;
            $('#cmEntries .cm-entry').each(function () {
                var $e = $(this);
                n++;
                if (tipe === 'kwh') {
                    var awal = parseFloat($e.find('.je-stan-awal').val());
                    var akhir = parseFloat($e.find('.je-stan-akhir').val());
                    if (!isNaN(awal) && !isNaN(akhir)) {
                        if (akhir < awal) anyWarn = true;
                        total += Math.max(0, akhir - awal);
                    }
                } else {
                    var v = parseFloat($e.find('.je-manual').val());
                    if (!isNaN(v)) total += Math.max(0, v);
                }
            });

            $('#cmTotalMode').text(tipe === 'kwh' ? ('MODE METER · ' + UNIT) : 'MODE FLAT · MANUAL');
            $('#cmTotalReadout').text(nf.format(total)).toggleClass('zero', !(total > 0));
            $('#cmTotalWarn').toggleClass('show', anyWarn);
            var label = tipe === 'kwh' ? 'data meter' : 'input flat';
            $('#cmTotalFormula').html(total > 0
                ? ('Total dari <b>' + n + '</b> ' + label + ' = <b>' + nf.format(total) + '</b> ' + UNIT)
                : 'Isi data meter untuk menghitung otomatis.');
        }

        function syncEntry($e) {
            var tipe = sharedTipe();
            if (tipe === 'kwh') {
                $e.find('.je-kwh').show();
                $e.find('.je-flat').hide();
                $e.find('.je-manual').removeAttr('required');
                $e.find('.je-stan-awal, .je-stan-akhir').attr('required', true);
            } else {
                $e.find('.je-kwh').hide();
                $e.find('.je-flat').show();
                $e.find('.je-manual').attr('required', true);
                $e.find('.je-stan-awal, .je-stan-akhir').removeAttr('required');
            }
        }

        function renumber() {
            var $entries = $('#cmEntries .cm-entry');
            $entries.each(function (i) {
                $(this).find('.cm-entry-no').text(i + 1);
                $(this).find('.cm-entry-badge .n').text(i + 1);
            });
            $('#cmEntries .je-remove').toggle($entries.length > 1);
        }

        function getMitra() { return $('#cmMitra').val(); }

        function fetchStan($e) {
            var mitra = getMitra();
            var bulan = $('#cmBulan').val();
            var tahun = $('#cmTahun').val();
            if (mitra && bulan && tahun && sharedTipe() === 'kwh') {
                $.ajax({
                    url: LAST_STAN_URL, type: 'GET',
                    data: { mitra_jasa_id: mitra, layanan_jasa_id: LAYANAN_ID, bulan: bulan, tahun: tahun },
                    success: function (res) { $e.find('.je-stan-awal').val(res.stan_akhir); recalc(); }
                });
            }
        }

        function pickFile($e, role, cam) {
            var inp = $e.find('.util-file-input[data-role="' + role + '"]')[0];
            if (!inp) return;
            if (cam) inp.setAttribute('capture', 'environment'); else inp.removeAttribute('capture');
            inp.click();
        }

        function previewFile($input) {
            var role = $input.data('role');
            var $e = $input.closest('.cm-entry');
            var file = $input[0].files && $input[0].files.length ? $input[0].files[0] : null;
            var $zone = $e.find('.util-file-trigger[data-role="' + role + '"]');
            var $thumb = $e.find('.util-file-icon[data-thumb-role="' + role + '"]');
            $zone.toggleClass('has-file', !!file);
            $zone.find('.util-file-name').text(file ? file.name : 'Pilih file atau ambil foto. Max 5MB.');
            if (file && /^image\//.test(file.type)) { $thumb.html('<img src="' + URL.createObjectURL(file) + '" alt="preview">'); }
            else { $thumb.html('<i class="bi bi-cloud-arrow-up"></i>'); }
        }

        function populateEntry($e, d) {
            if (!d) return;
            if (d.stan_awal != null && d.stan_awal !== '') $e.find('.je-stan-awal').val(d.stan_awal);
            if (d.stan_akhir != null && d.stan_akhir !== '') $e.find('.je-stan-akhir').val(d.stan_akhir);
            if (d.pemakaian_manual != null && d.pemakaian_manual !== '') $e.find('.je-manual').val(d.pemakaian_manual);
        }

        function addEntry(d) {
            var html = document.getElementById('cmEntryTemplate').innerHTML.split('__IDX__').join(seq++);
            var $node = $($.parseHTML(html));
            $('#cmEntries').append($node);
            populateEntry($node, d);
            syncEntry($node);
            renumber();
            recalc();
            return $node;
        }

        // Mitra dipilih sekali — select2 + re-fetch stan awal semua entri saat mitra berubah.
        if ($.fn && typeof $.fn.select2 === 'function') {
            var $cm = $('#cmMitra');
            if (!$cm.hasClass('select2-hidden-accessible')) $cm.select2({ theme: 'bootstrap-5', width: '100%' });
        }
        // Pengaturan bersama → terapkan ke semua entri.
        function syncAll() { $('#cmEntries .cm-entry').each(function () { syncEntry($(this)); }); }
        function fetchAll() { $('#cmEntries .cm-entry').each(function () { fetchStan($(this)); }); }

        $('#cmMitra').on('change', fetchAll);
        $('#cmBulan, #cmTahun').on('change', fetchAll);
        $('input[name="tipe_perhitungan"]').on('change', function () {
            $('.util-type-card').removeClass('selected');
            $(this).closest('.util-type-card').addClass('selected');
            syncAll();
            fetchAll();
            recalc();
        });

        $('#cmEntries')
            .on('input', '.je-stan-awal, .je-stan-akhir, .je-manual', function () { recalc(); })
            .on('click', '.util-file-trigger, .util-pick', function () { pickFile($(this).closest('.cm-entry'), $(this).data('role'), false); })
            .on('click', '.util-cam', function () { pickFile($(this).closest('.cm-entry'), $(this).data('role'), true); })
            .on('keydown', '.util-file-trigger', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pickFile($(this).closest('.cm-entry'), $(this).data('role'), false); } })
            .on('change', '.util-file-input', function () { previewFile($(this)); })
            .on('click', '.je-remove', function () {
                if ($('#cmEntries .cm-entry').length > 1) { $(this).closest('.cm-entry').remove(); renumber(); recalc(); }
            });

        $('#cmAddEntry').on('click', function () { addEntry(); });

        // Entri awal — repopulasi old() bila ada error validasi, jika tidak satu entri kosong.
        var oldLaporan = @json(old('laporan', []));
        if (Array.isArray(oldLaporan) && oldLaporan.length) { oldLaporan.forEach(function (d) { addEntry(d); }); }
        else { addEntry(); }
    });
</script>
@endif
@endpush
