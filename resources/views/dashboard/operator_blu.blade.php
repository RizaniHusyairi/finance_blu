@extends('layouts.app')
@section('title', 'Dashboard Operator BLU')

@section('content')
@php
    $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');

    // Peta status tagihan → label ringkas + kelas warna chip.
    $statusChip = function ($status) {
        return match (true) {
            $status === 'SELESAI' => ['Selesai', 'emerald'],
            $status === 'PROSES_SPP' => ['Proses SPP', 'blue'],
            str_starts_with((string) $status, 'REVISI') || str_starts_with((string) $status, 'DITOLAK') => ['Revisi', 'rose'],
            default => ['Siap diproses', 'amber'],
        };
    };
    $tipeChip = fn ($tipe) => match ($tipe) {
        'KONTRAK' => ['SPK', 'violet'],
        'KONTRAK_EKSTERNAL' => ['Kontrak', 'indigo'],
        'PERJALDIN' => ['Perjaldin', 'teal'],
        'HONORARIUM' => ['Honor', 'amber'],
        default => [$tipe ?: '-', 'slate'],
    };
    $maxPipeline = max(1, collect($pipeline)->max('count'));
@endphp

<style>
    :root {
        --ob-ink: #0f172a; --ob-muted: #64748b; --ob-line: #e8edf4;
        --ob-emerald: #10b981; --ob-cyan: #06b6d4; --ob-amber: #f59e0b;
        --ob-blue: #2563eb; --ob-rose: #f43f5e; --ob-violet: #8b5cf6;
    }

    /* ===== entrance & ambience animations ===== */
    @keyframes obUp { from { opacity: 0; transform: translateY(22px); } to { opacity: 1; transform: none; } }
    @keyframes obOrb { 0%,100% { transform: translate3d(0,0,0) scale(1); opacity: .75; } 50% { transform: translate3d(-18px,14px,0) scale(1.14); opacity: 1; } }
    @keyframes obSweep { 0% { transform: translateX(-130%) skewX(-18deg); opacity: 0; } 22% { opacity: .32; } 60%,100% { transform: translateX(240%) skewX(-18deg); opacity: 0; } }
    @keyframes obFloat { 0%,100% { transform: translateY(0) rotate(3deg); } 50% { transform: translateY(-13px) rotate(3deg); } }
    @keyframes obPulse { 0%,100% { box-shadow: 0 0 0 0 rgba(245,158,11,.45); } 50% { box-shadow: 0 0 0 9px rgba(245,158,11,0); } }
    @keyframes obFlow { from { background-position: 0 0; } to { background-position: 26px 0; } }
    @keyframes obBlink { 0%,100% { opacity: 1; } 50% { opacity: .25; } }
    .ob-rise { opacity: 0; animation: obUp .6s cubic-bezier(.22,.61,.36,1) both; animation-delay: var(--d, 0s); }
    @media (prefers-reduced-motion: reduce) {
        .ob-rise, .ob-hero .orb, .ob-hero .sweep, .ob-hero .gear, .ob-pipe-link, .ob-live-dot { animation: none !important; opacity: 1 !important; }
    }

    /* ===== hero ===== */
    .ob-hero {
        position: relative; overflow: hidden; border-radius: 24px; padding: 30px 32px; margin-bottom: 1.5rem;
        background: linear-gradient(125deg, #071a2e 0%, #0d3a52 46%, #10756c 100%);
        color: #fff; box-shadow: 0 24px 60px rgba(7, 26, 46, .35);
    }
    .ob-hero::before { content: ""; position: absolute; inset: 0; pointer-events: none;
        background: linear-gradient(90deg, rgba(2,12,22,.45) 0%, rgba(2,12,22,.18) 46%, transparent 78%); }
    .ob-hero .orb { position: absolute; border-radius: 999px; filter: blur(2px); pointer-events: none; }
    .ob-hero .orb-1 { width: 270px; height: 270px; right: 5%; top: -130px; background: radial-gradient(circle, rgba(45,212,191,.45), transparent 68%); animation: obOrb 6.5s ease-in-out infinite; }
    .ob-hero .orb-2 { width: 190px; height: 190px; right: 24%; bottom: -100px; background: radial-gradient(circle, rgba(56,189,248,.34), transparent 70%); animation: obOrb 8s ease-in-out infinite reverse; }
    .ob-hero .sweep { position: absolute; inset: 0; width: 46%; pointer-events: none;
        background: linear-gradient(100deg, transparent, rgba(255,255,255,.15), transparent); animation: obSweep 6.5s ease-in-out 1.2s infinite; }
    .ob-hero .gear { position: absolute; right: 34px; bottom: 8px; font-size: 5.6rem; color: rgba(255,255,255,.12); animation: obFloat 5.5s ease-in-out infinite; }
    .ob-hero .eyebrow { letter-spacing: .14em; text-transform: uppercase; font-size: .72rem; font-weight: 800; color: #5eead4; }
    .ob-hero h3 { color: #fff !important; font-weight: 900; margin: .3rem 0 .3rem; font-size: 1.68rem; line-height: 1.16; text-shadow: 0 2px 12px rgba(0,0,0,.28); }
    .ob-hero p { color: rgba(255,255,255,.86) !important; max-width: 620px; margin-bottom: 1rem; }
    .ob-hero .hero-copy, .ob-hero .hero-side { position: relative; z-index: 1; }
    .ob-chip { display: inline-flex; align-items: center; gap: .42rem; border-radius: 999px; padding: .38rem .8rem;
        background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.22); color: #fff; font-size: .78rem; font-weight: 700; }
    .ob-chip.warn { background: rgba(245,158,11,.22); border-color: rgba(251,191,36,.55); animation: obPulse 2.2s ease-out infinite; }
    .ob-live-dot { width: 8px; height: 8px; border-radius: 999px; background: #34d399; animation: obBlink 1.6s ease-in-out infinite; }
    .ob-hero .btn-hero { border-radius: 12px; font-weight: 700; padding: .5rem .95rem; }
    .ob-hero .btn-hero.primary { background: #fff; color: #0d3a52; border: 0; }
    .ob-hero .btn-hero.primary:hover { background: #d9fbf3; }
    .ob-hero .btn-hero.ghost { background: rgba(255,255,255,.10); color: #fff; border: 1px solid rgba(255,255,255,.28); }
    .ob-hero .btn-hero.ghost:hover { background: rgba(255,255,255,.2); }

    /* ===== KPI cards ===== */
    .ob-kpi { border: 1px solid var(--ob-line); border-radius: 18px; background: #fff; padding: 1.05rem 1.2rem; height: 100%;
        position: relative; overflow: hidden; transition: transform .2s ease, box-shadow .2s ease; }
    .ob-kpi::after { content: ""; position: absolute; inset: auto 0 0 0; height: 3px;
        background: linear-gradient(90deg, var(--kpi, var(--ob-cyan)), transparent 85%); }
    .ob-kpi:hover { transform: translateY(-4px); box-shadow: 0 16px 36px rgba(15,23,42,.10); }
    .ob-kpi .kpi-ic { width: 44px; height: 44px; border-radius: 13px; display: grid; place-items: center; font-size: 1.25rem;
        color: var(--kpi, var(--ob-cyan)); background: color-mix(in srgb, var(--kpi, var(--ob-cyan)) 12%, #fff); }
    .ob-kpi .kpi-label { font-size: .74rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--ob-muted); }
    .ob-kpi .kpi-value { font-size: 1.5rem; font-weight: 900; color: var(--ob-ink); line-height: 1.1; white-space: nowrap; }
    .ob-kpi .kpi-sub { font-size: .76rem; color: var(--ob-muted); }
    .ob-kpi .kpi-bar { height: 7px; border-radius: 999px; background: #eef2f7; overflow: hidden; margin-top: .55rem; }
    .ob-kpi .kpi-bar > span { display: block; height: 100%; width: 0;
        background: linear-gradient(90deg, var(--ob-emerald), var(--ob-cyan)); border-radius: inherit; transition: width 1.2s cubic-bezier(.22,.61,.36,1); }

    /* ===== pipeline stepper ===== */
    .ob-panel { border: 1px solid var(--ob-line); border-radius: 18px; background: #fff; padding: 1.15rem 1.25rem; height: 100%; }
    .ob-panel .panel-title { font-size: .95rem; font-weight: 800; color: var(--ob-ink); margin: 0; }
    .ob-panel .panel-sub { font-size: .78rem; color: var(--ob-muted); }
    .ob-pipe { display: flex; align-items: stretch; gap: 0; overflow-x: auto; padding: .35rem .1rem .2rem; }
    .ob-pipe-stage { flex: 1 1 0; min-width: 118px; text-decoration: none; border: 1px solid var(--ob-line); border-radius: 14px;
        padding: .8rem .6rem; text-align: center; background: #fff; transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
    .ob-pipe-stage:hover { transform: translateY(-4px); border-color: #99f6e4; box-shadow: 0 12px 26px rgba(13,116,108,.14); }
    .ob-pipe-stage .st-ic { width: 40px; height: 40px; margin: 0 auto .35rem; border-radius: 12px; display: grid; place-items: center;
        font-size: 1.1rem; color: #0d746c; background: #ecfdf8; }
    .ob-pipe-stage .st-count { font-size: 1.3rem; font-weight: 900; color: var(--ob-ink); }
    .ob-pipe-stage .st-label { font-size: .76rem; font-weight: 800; color: var(--ob-ink); }
    .ob-pipe-stage .st-bar { height: 4px; border-radius: 999px; background: #eef2f7; margin-top: .45rem; overflow: hidden; }
    .ob-pipe-stage .st-bar > span { display: block; height: 100%; width: 0; background: linear-gradient(90deg, #14b8a6, #06b6d4);
        transition: width 1s cubic-bezier(.22,.61,.36,1) .35s; }
    .ob-pipe-link { flex: 0 0 26px; align-self: center; height: 3px; margin: 0 2px;
        background-image: linear-gradient(90deg, #99f6e4 55%, transparent 0); background-size: 13px 3px; animation: obFlow 1.1s linear infinite; }

    /* ===== master data health ===== */
    .ob-health { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; }
    .ob-health-tile { border: 1px solid var(--ob-line); border-radius: 14px; padding: .85rem .95rem; text-decoration: none;
        display: flex; gap: .7rem; align-items: flex-start; transition: transform .18s ease, box-shadow .18s ease; background: #fff; }
    .ob-health-tile:hover { transform: translateY(-3px); box-shadow: 0 12px 26px rgba(15,23,42,.10); }
    .ob-health-tile .h-ic { width: 40px; height: 40px; flex: 0 0 40px; border-radius: 12px; display: grid; place-items: center; font-size: 1.05rem; }
    .ob-health-tile.ok .h-ic { color: #0f766e; background: #ecfdf8; }
    .ob-health-tile.warn .h-ic { color: #b45309; background: #fffbeb; animation: obPulse 2.4s ease-out infinite; }
    .ob-health-tile .h-count { font-size: 1.15rem; font-weight: 900; color: var(--ob-ink); line-height: 1; }
    .ob-health-tile .h-label { font-size: .78rem; font-weight: 800; color: var(--ob-ink); }
    .ob-health-tile .h-note { font-size: .72rem; color: var(--ob-muted); line-height: 1.25; }
    .ob-health-tile .h-dot { width: 9px; height: 9px; border-radius: 999px; margin-left: auto; margin-top: .3rem; }
    .ob-health-tile.ok .h-dot { background: var(--ob-emerald); }
    .ob-health-tile.warn .h-dot { background: var(--ob-amber); }

    /* ===== antrean & tabel ===== */
    .ob-row-item { display: flex; align-items: center; gap: .75rem; padding: .68rem .35rem; border-bottom: 1px dashed var(--ob-line); }
    .ob-row-item:last-child { border-bottom: 0; }
    .ob-row-item .r-title { font-weight: 700; color: var(--ob-ink); font-size: .85rem; }
    .ob-row-item .r-sub { font-size: .74rem; color: var(--ob-muted); }
    .ob-badge { display: inline-block; border-radius: 999px; font-size: .68rem; font-weight: 800; padding: .18rem .55rem; white-space: nowrap; }
    .ob-badge.emerald { color: #047857; background: #ecfdf5; }
    .ob-badge.blue { color: #1d4ed8; background: #eff6ff; }
    .ob-badge.amber { color: #b45309; background: #fffbeb; }
    .ob-badge.rose { color: #be123c; background: #fff1f2; }
    .ob-badge.violet { color: #6d28d9; background: #f5f3ff; }
    .ob-badge.indigo { color: #4338ca; background: #eef2ff; }
    .ob-badge.teal { color: #0f766e; background: #f0fdfa; }
    .ob-badge.slate { color: #475569; background: #f1f5f9; }
    .min-w-0 { min-width: 0; }
    .ob-empty { text-align: center; padding: 1.6rem .5rem; color: var(--ob-muted); }
    .ob-empty i { font-size: 2rem; color: var(--ob-emerald); display: block; margin-bottom: .4rem; }
    .ob-btn-mini { border-radius: 9px; font-size: .72rem; font-weight: 700; padding: .28rem .6rem; }

    /* ===== tren toggle ===== */
    .ob-toggle { display: inline-flex; border: 1px solid var(--ob-line); border-radius: 10px; overflow: hidden; }
    .ob-toggle button { border: 0; background: #fff; font-size: .74rem; font-weight: 800; color: var(--ob-muted); padding: .35rem .7rem; }
    .ob-toggle button.active { background: #0d746c; color: #fff; }
</style>

{{-- ═══════════════════ HERO ═══════════════════ --}}
<div class="ob-hero ob-rise" style="--d:.02s">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="sweep"></div>
    <i class="bi bi-database-gear gear"></i>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div class="hero-copy">
            <div class="eyebrow"><i class="bi bi-sliders me-1"></i> Pusat Kendali Operator BLU</div>
            <h3>{{ $now->format('H') < 11 ? 'Selamat pagi' : ($now->format('H') < 15 ? 'Selamat siang' : 'Selamat sore') }}, {{ $user->name }} 👋</h3>
            <p>Pantau kesehatan master data anggaran dan kawal setiap tagihan menyusuri pipeline
               SPP → SPM → NPI → SP2D sampai dananya cair.</p>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('proses-tagihan.index') }}" class="btn btn-hero primary btn-sm">
                    <i class="bi bi-diagram-3 me-1"></i> Proses Tagihan
                </a>
                <a href="{{ route('dipas.index') }}" class="btn btn-hero ghost btn-sm"><i class="bi bi-safe2 me-1"></i> DIPA</a>
                <a href="{{ route('coas.index') }}" class="btn btn-hero ghost btn-sm"><i class="bi bi-list-columns-reverse me-1"></i> COA</a>
                <a href="{{ route('master-pajak.index') }}" class="btn btn-hero ghost btn-sm"><i class="bi bi-percent me-1"></i> Pajak</a>
            </div>
        </div>
        <div class="hero-side d-flex flex-column align-items-end gap-2">
            <span class="ob-chip"><span class="ob-live-dot"></span> <span id="obClock">--:--:--</span> &middot; {{ $now->translatedFormat('l, d F Y') }}</span>
            @if($perluAksi > 0)
                <span class="ob-chip warn"><i class="bi bi-lightning-charge-fill"></i> {{ $perluAksi }} tagihan menunggu aksi Anda</span>
            @else
                <span class="ob-chip"><i class="bi bi-check2-circle"></i> Tidak ada antrean aksi</span>
            @endif
            <span class="ob-chip"><i class="bi bi-file-earmark-arrow-up"></i> {{ $sppBulanIni }} SPP terbit bulan ini</span>
        </div>
    </div>
</div>

{{-- ═══════════════════ KPI ═══════════════════ --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3 ob-rise" style="--d:.08s">
        <div class="ob-kpi" style="--kpi: var(--ob-blue)">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-ic"><i class="bi bi-safe2"></i></div>
                <div>
                    <div class="kpi-label">Total Pagu Aktif</div>
                    <div class="kpi-value ob-count" data-target="{{ (float) $totalPagu }}" data-fmt="rupiah">Rp 0</div>
                </div>
            </div>
            <div class="kpi-sub mt-2" title="{{ $rupiah($totalPagu) }}">{{ $rupiah($totalPagu) }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3 ob-rise" style="--d:.14s">
        <div class="ob-kpi" style="--kpi: var(--ob-emerald)">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-ic"><i class="bi bi-graph-up-arrow"></i></div>
                <div>
                    <div class="kpi-label">Realisasi</div>
                    <div class="kpi-value ob-count" data-target="{{ (float) $totalRealisasi }}" data-fmt="rupiah">Rp 0</div>
                </div>
            </div>
            <div class="kpi-bar"><span data-width="{{ min(100, $persenRealisasi) }}"></span></div>
            <div class="kpi-sub mt-1">{{ $persenRealisasi }}% dari pagu &middot; {{ $rupiah($totalRealisasi) }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3 ob-rise" style="--d:.20s">
        <div class="ob-kpi" style="--kpi: var(--ob-cyan)">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-ic"><i class="bi bi-wallet2"></i></div>
                <div>
                    <div class="kpi-label">Sisa Anggaran</div>
                    <div class="kpi-value ob-count" data-target="{{ (float) $sisaAnggaran }}" data-fmt="rupiah">Rp 0</div>
                </div>
            </div>
            <div class="kpi-sub mt-2" title="{{ $rupiah($sisaAnggaran) }}">{{ $rupiah($sisaAnggaran) }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3 ob-rise" style="--d:.26s">
        <div class="ob-kpi" style="--kpi: var(--ob-amber)">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-ic"><i class="bi bi-lightning-charge"></i></div>
                <div>
                    <div class="kpi-label">Perlu Aksi Anda</div>
                    <div class="kpi-value ob-count" data-target="{{ (int) $perluAksi }}" data-fmt="int">0</div>
                </div>
            </div>
            <div class="kpi-sub mt-2">{{ $menungguCoa }} menunggu COA &middot; {{ $menungguRantai }} siap dibuat rantai dokumen</div>
        </div>
    </div>
</div>

{{-- ═══════════════════ PIPELINE ═══════════════════ --}}
<div class="ob-panel ob-rise mb-3" style="--d:.32s">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
        <div>
            <p class="panel-title mb-0"><i class="bi bi-diagram-3 me-1 text-success"></i> Pipeline Pencairan</p>
            <span class="panel-sub">Klik tahap mana pun untuk membuka Proses Tagihan.</span>
        </div>
        <a href="{{ route('proses-tagihan.index') }}" class="btn btn-outline-success btn-sm ob-btn-mini">
            Buka Proses Tagihan <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="ob-pipe">
        @foreach($pipeline as $i => $stage)
            <a href="{{ route('proses-tagihan.index') }}" class="ob-pipe-stage" title="{{ $stage['hint'] }}">
                <div class="st-ic"><i class="bi {{ $stage['icon'] }}"></i></div>
                <div class="st-count ob-count" data-target="{{ (int) $stage['count'] }}" data-fmt="int">0</div>
                <div class="st-label">{{ $stage['label'] }}</div>
                <div class="st-bar"><span data-width="{{ round($stage['count'] / $maxPipeline * 100) }}"></span></div>
            </a>
            @if(! $loop->last)
                <div class="ob-pipe-link"></div>
            @endif
        @endforeach
    </div>
</div>

{{-- ═══════════════════ CHARTS: SERAPAN + GAUGE ═══════════════════ --}}
<div class="row g-3 mb-3">
    <div class="col-lg-8 ob-rise" style="--d:.38s">
        <div class="ob-panel">
            <p class="panel-title"><i class="bi bi-bar-chart-line me-1 text-primary"></i> Serapan per Jenis Belanja</p>
            <span class="panel-sub">Pagu vs realisasi berdasarkan prefiks akun (51 / 52 / 53 / 525).</span>
            <div id="obSerapanChart" class="mt-2"></div>
        </div>
    </div>
    <div class="col-lg-4 ob-rise" style="--d:.44s">
        <div class="ob-panel text-center">
            <p class="panel-title"><i class="bi bi-speedometer2 me-1 text-success"></i> Tingkat Serapan</p>
            <span class="panel-sub">Persentase realisasi terhadap pagu aktif.</span>
            <div id="obGauge" class="mt-1"></div>
            <div class="d-flex justify-content-center gap-3 mt-1">
                <span class="panel-sub"><i class="bi bi-circle-fill me-1" style="color: var(--ob-emerald); font-size: .55rem;"></i>Realisasi {{ $rupiah($totalRealisasi) }}</span>
                <span class="panel-sub"><i class="bi bi-circle-fill me-1" style="color: #e2e8f0; font-size: .55rem;"></i>Sisa {{ $rupiah($sisaAnggaran) }}</span>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════ ANTREAN AKSI + MASTER DATA ═══════════════════ --}}
<div class="row g-3 mb-3">
    <div class="col-lg-7 ob-rise" style="--d:.50s">
        <div class="ob-panel">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="panel-title mb-0"><i class="bi bi-lightning-charge me-1 text-warning"></i> Antrean Aksi Anda</p>
                    <span class="panel-sub">Tagihan siap proses yang belum punya rantai dokumen — lengkapi COA & pajaknya.</span>
                </div>
                <span class="ob-badge amber">{{ $antreanAksi->count() }} tagihan</span>
            </div>
            <div class="mt-2">
                @forelse($antreanAksi as $t)
                    @php [$tipeLabel, $tipeTone] = $tipeChip($t->tipe_tagihan); @endphp
                    <div class="ob-row-item">
                        <span class="ob-badge {{ $tipeTone }}">{{ $tipeLabel }}</span>
                        <div class="flex-grow-1 min-w-0">
                            <div class="r-title text-truncate">{{ $t->nomor_tagihan ?: 'Tanpa nomor' }}</div>
                            <div class="r-sub text-truncate">
                                {{ $t->nama_supplier ?: ($t->deskripsi ?: '-') }} &middot; {{ $rupiah($t->total_netto) }}
                                @if(empty($t->dipa_revision_item_id)) &middot; <span class="text-danger fw-bold">COA belum dipilih</span> @endif
                            </div>
                        </div>
                        <span class="r-sub d-none d-md-inline">{{ $t->updated_at?->diffForHumans() }}</span>
                        <a href="{{ route('proses-tagihan.show', $t) }}" class="btn btn-success btn-sm ob-btn-mini">Proses</a>
                    </div>
                @empty
                    <div class="ob-empty">
                        <i class="bi bi-emoji-sunglasses"></i>
                        Semua tagihan sudah dikawal — tidak ada antrean untuk Anda. 🎉
                    </div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-5 ob-rise" style="--d:.56s">
        <div class="ob-panel">
            <p class="panel-title mb-0"><i class="bi bi-heart-pulse me-1 text-danger"></i> Kesehatan Master Data</p>
            <span class="panel-sub d-block mb-2">Fondasi yang Anda rawat — klik untuk membuka masternya.</span>
            <div class="ob-health">
                @foreach($masterHealth as $h)
                    <a href="{{ $h['route'] }}" class="ob-health-tile {{ $h['ok'] ? 'ok' : 'warn' }}">
                        <div class="h-ic"><i class="bi {{ $h['icon'] }}"></i></div>
                        <div>
                            <div class="h-count ob-count" data-target="{{ (int) $h['count'] }}" data-fmt="int">0</div>
                            <div class="h-label">{{ $h['label'] }}</div>
                            <div class="h-note">{{ $h['note'] }}</div>
                        </div>
                        <span class="h-dot"></span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════ TREN + TAGIHAN TERBARU ═══════════════════ --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8 ob-rise" style="--d:.62s">
        <div class="ob-panel">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <p class="panel-title mb-0"><i class="bi bi-activity me-1 text-info"></i> Tren 6 Bulan Terakhir</p>
                    <span class="panel-sub">Ganti tampilan antara nominal realisasi dan jumlah tagihan masuk.</span>
                </div>
                <div class="ob-toggle" role="group" aria-label="Ganti seri tren">
                    <button type="button" class="active" data-tren="realisasi"><i class="bi bi-cash-stack me-1"></i>Realisasi</button>
                    <button type="button" data-tren="tagihan"><i class="bi bi-receipt me-1"></i>Tagihan</button>
                </div>
            </div>
            <div id="obTrenChart" class="mt-2"></div>
        </div>
    </div>
    <div class="col-lg-4 ob-rise" style="--d:.68s">
        <div class="ob-panel">
            <p class="panel-title mb-0"><i class="bi bi-clock-history me-1 text-secondary"></i> Terbaru di Pipeline</p>
            <span class="panel-sub d-block mb-1">Tagihan yang terakhir bergerak.</span>
            @forelse($tagihanTerbaru as $t)
                @php [$stLabel, $stTone] = $statusChip($t->status); @endphp
                <div class="ob-row-item">
                    <div class="flex-grow-1 min-w-0">
                        <a href="{{ route('proses-tagihan.show', $t) }}" class="r-title text-truncate d-block text-decoration-none">
                            {{ $t->nomor_tagihan ?: 'Tanpa nomor' }}
                        </a>
                        <div class="r-sub">{{ $rupiah($t->total_netto) }} &middot; {{ $t->updated_at?->diffForHumans() }}</div>
                    </div>
                    <span class="ob-badge {{ $stTone }}">{{ $stLabel }}</span>
                </div>
            @empty
                <div class="ob-empty"><i class="bi bi-inbox"></i> Belum ada tagihan pada pipeline.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('script')
<script src="{{ URL::asset('build/plugins/apexchart/apexcharts.min.js') }}"></script>
<script>
(function () {
    // ===== Jam hidup di hero =====
    const clockEl = document.getElementById('obClock');
    function tickClock() {
        if (!clockEl) return;
        clockEl.textContent = new Date().toLocaleTimeString('id-ID', { hour12: false });
    }
    tickClock();
    setInterval(tickClock, 1000);

    // ===== Count-up KPI (int & rupiah ringkas) =====
    const fmtRupiahCompact = v => {
        const abs = Math.abs(v);
        if (abs >= 1e12) return 'Rp ' + (v / 1e12).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' T';
        if (abs >= 1e9)  return 'Rp ' + (v / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' M';
        if (abs >= 1e6)  return 'Rp ' + (v / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' Jt';
        return 'Rp ' + Math.round(v).toLocaleString('id-ID');
    };
    function countUp(el) {
        const target = parseFloat(el.dataset.target) || 0;
        const isRupiah = el.dataset.fmt === 'rupiah';
        const dur = 1200, t0 = performance.now();
        function frame(nowTs) {
            const p = Math.min(1, (nowTs - t0) / dur);
            const eased = 1 - Math.pow(1 - p, 3);
            const val = target * eased;
            el.textContent = isRupiah ? fmtRupiahCompact(val) : Math.round(val).toLocaleString('id-ID');
            if (p < 1) requestAnimationFrame(frame);
        }
        requestAnimationFrame(frame);
    }
    document.querySelectorAll('.ob-count').forEach(el => setTimeout(() => countUp(el), 260));

    // ===== Bar progres (KPI realisasi & pipeline) =====
    setTimeout(() => {
        document.querySelectorAll('[data-width]').forEach(el => { el.style.width = el.dataset.width + '%'; });
    }, 420);

    if (typeof ApexCharts === 'undefined') return;

    const fmtAxisRupiah = v => {
        if (!isFinite(v)) return '0';
        const abs = Math.abs(v);
        if (abs >= 1e12) return (v / 1e12).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' T';
        if (abs >= 1e9)  return (v / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' M';
        if (abs >= 1e6)  return (v / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' Jt';
        return Math.round(v).toLocaleString('id-ID');
    };
    const fmtFullRupiah = v => 'Rp ' + Math.round(isFinite(v) ? v : 0).toLocaleString('id-ID');

    // ===== Bar: serapan per jenis belanja =====
    const serapanEl = document.getElementById('obSerapanChart');
    if (serapanEl) {
        new ApexCharts(serapanEl, {
            chart: { type: 'bar', height: 300, fontFamily: 'inherit', toolbar: { show: false },
                animations: { enabled: true, easing: 'easeinout', speed: 1100, animateGradually: { enabled: true, delay: 180 } } },
            series: [
                { name: 'Pagu', data: @json($chartBarPagu) },
                { name: 'Realisasi', data: @json($chartBarRealisasi) },
            ],
            colors: ['#94a3b8', '#10b981'],
            plotOptions: { bar: { columnWidth: '52%', borderRadius: 7, borderRadiusApplication: 'end' } },
            dataLabels: { enabled: false },
            grid: { borderColor: '#eef2f7', strokeDashArray: 4 },
            legend: { fontWeight: 700, markers: { radius: 99 } },
            xaxis: { categories: @json($chartBarLabels), axisBorder: { show: false }, axisTicks: { show: false },
                labels: { style: { colors: '#94a3b8', fontWeight: 700 } } },
            yaxis: { labels: { style: { colors: '#94a3b8' }, formatter: fmtAxisRupiah } },
            tooltip: { theme: 'light', y: { formatter: fmtFullRupiah } },
        }).render();
    }

    // ===== Radial gauge: tingkat serapan =====
    const gaugeEl = document.getElementById('obGauge');
    if (gaugeEl) {
        new ApexCharts(gaugeEl, {
            chart: { type: 'radialBar', height: 268, fontFamily: 'inherit',
                animations: { enabled: true, easing: 'easeinout', speed: 1300 } },
            series: [{{ (float) min(100, $persenRealisasi) }}],
            labels: ['Serapan'],
            colors: ['#10b981'],
            fill: { type: 'gradient', gradient: { shade: 'light', type: 'horizontal', gradientToColors: ['#06b6d4'], stops: [0, 100] } },
            stroke: { lineCap: 'round' },
            plotOptions: { radialBar: {
                hollow: { size: '62%' },
                track: { background: '#eef2f7' },
                dataLabels: {
                    name: { show: true, fontSize: '12px', color: '#94a3b8', offsetY: 22 },
                    value: { show: true, fontSize: '30px', fontWeight: 900, color: '#0f172a', offsetY: -14,
                        formatter: v => v.toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '%' },
                },
            } },
        }).render();
    }

    // ===== Area: tren 6 bulan (interaktif: ganti seri) =====
    const trenEl = document.getElementById('obTrenChart');
    if (trenEl) {
        const LABELS = @json($trenLabels);
        const SERI = {
            realisasi: { name: 'Realisasi', data: @json($trenRealisasi), color: '#10b981', money: true },
            tagihan: { name: 'Tagihan masuk', data: @json($trenTagihan), color: '#2563eb', money: false },
        };
        let aktif = 'realisasi';
        const tren = new ApexCharts(trenEl, {
            chart: { type: 'area', height: 285, fontFamily: 'inherit', toolbar: { show: false },
                animations: { enabled: true, easing: 'easeinout', speed: 900 } },
            series: [{ name: SERI.realisasi.name, data: SERI.realisasi.data }],
            colors: [SERI.realisasi.color],
            stroke: { curve: 'smooth', width: 3 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .42, opacityTo: .04, stops: [0, 92, 100] } },
            dataLabels: { enabled: false },
            markers: { size: 5, colors: ['#fff'], strokeColors: SERI.realisasi.color, strokeWidth: 3, hover: { size: 7 } },
            grid: { borderColor: '#eef2f7', strokeDashArray: 4, padding: { left: 8, right: 8 } },
            xaxis: { categories: LABELS, axisBorder: { show: false }, axisTicks: { show: false },
                labels: { style: { colors: '#94a3b8', fontWeight: 600 } } },
            yaxis: { min: 0, forceNiceScale: true, labels: { style: { colors: '#94a3b8' }, formatter: fmtAxisRupiah } },
            tooltip: { theme: 'light', y: { formatter: fmtFullRupiah } },
        });
        tren.render();

        document.querySelectorAll('.ob-toggle button[data-tren]').forEach(btn => {
            btn.addEventListener('click', function () {
                if (this.dataset.tren === aktif) return;
                aktif = this.dataset.tren;
                document.querySelectorAll('.ob-toggle button[data-tren]').forEach(b => b.classList.toggle('active', b === this));
                const s = SERI[aktif];
                tren.updateOptions({
                    series: [{ name: s.name, data: s.data }],
                    colors: [s.color],
                    markers: { strokeColors: s.color },
                    yaxis: { min: 0, forceNiceScale: true, labels: { style: { colors: '#94a3b8' },
                        formatter: s.money ? fmtAxisRupiah : (v => Math.round(v).toLocaleString('id-ID')) } },
                    tooltip: { theme: 'light', y: { formatter: s.money ? fmtFullRupiah : (v => Math.round(v) + ' tagihan') } },
                });
            });
        });
    }
})();
</script>
@endpush
