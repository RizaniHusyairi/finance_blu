@extends('layouts.app')
@section('title', 'Rekap Tagihan Garbarata')

@section('content')
@include('super_admin_jasa.laporan._styles')

@php
    $statusBadgeClass = [
        'DIAJUKAN' => 'pp-badge-warning',
        'DISETUJUI' => 'pp-badge-success',
        'DITOLAK' => 'pp-badge-danger',
    ];
    $statusIcon = [
        'DIAJUKAN' => 'bi-hourglass-split',
        'DISETUJUI' => 'bi-check2-circle',
        'DITOLAK' => 'bi-x-circle',
    ];

    $maxTrend = max(array_column($trend, 'nominal')) ?: 1;
    $sparkPoints = [];
    $w = 280; $h = 50;
    $step = count($trend) > 1 ? $w / (count($trend) - 1) : 0;
    foreach ($trend as $i => $t) {
        $x = round($i * $step, 1);
        $y = round($h - ($t['nominal'] / $maxTrend) * ($h - 6) - 3, 1);
        $sparkPoints[] = "$x,$y";
    }
    $sparkPath = implode(' ', $sparkPoints);

    $formatRpShort = function ($n) {
        $n = (float) $n;
        if ($n >= 1_000_000_000) return 'Rp ' . number_format($n / 1_000_000_000, 2, ',', '.') . ' M';
        if ($n >= 1_000_000) return 'Rp ' . number_format($n / 1_000_000, 2, ',', '.') . ' Jt';
        if ($n >= 1_000) return 'Rp ' . number_format($n / 1_000, 0, ',', '.') . ' rb';
        return 'Rp ' . number_format($n, 0, ',', '.');
    };
@endphp

<style>
    .pp-stat-card {
        border-radius: 18px;
        background: #fff;
        border: 1px solid rgba(15, 23, 42, .06);
        box-shadow: 0 14px 32px rgba(15, 23, 42, .06);
        padding: 16px;
        height: 100%;
    }
    .pp-stat-icon {
        width: 44px; height: 44px; border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
    }
    .pp-stat-icon.bg-blue { background: #dbeafe; color: #1d4ed8; }
    .pp-stat-icon.bg-amber { background: #fef3c7; color: #b45309; }
    .pp-stat-icon.bg-emerald { background: #d1fae5; color: #047857; }
    .pp-stat-icon.bg-violet { background: #ede9fe; color: #6d28d9; }
    .pp-stat-label { font-size: .72rem; font-weight: 700; color: #64748b; letter-spacing: .04em; }
    .pp-stat-value { font-size: 1.6rem; font-weight: 900; color: #0f2f57; line-height: 1.1; margin-top: 2px; }
    .pp-stat-delta { font-size: .72rem; font-weight: 700; margin-top: 6px; }
    .pp-stat-delta.up { color: #047857; }
    .pp-stat-delta.down { color: #b91c1c; }
    .pp-stat-delta.flat { color: #64748b; }

    .pp-filter-bar { display: flex; gap: 12px; align-items: end; flex-wrap: wrap; }
    .pp-filter-bar label { font-size: .7rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }

    .pp-list-card { display: flex; flex-direction: column; height: 100%; }
    .pp-list-head { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; border-bottom: 1px solid #eef2f7; }
    .pp-list-head h6 { margin: 0; font-weight: 800; color: #0f2f57; }
    .pp-list-body { padding: 8px; max-height: 560px; overflow-y: auto; }

    .pp-item { display: flex; gap: 12px; padding: 12px; border-radius: 14px; cursor: pointer; transition: all .15s; border: 1px solid transparent; }
    .pp-item:hover { background: #f1f5f9; }
    .pp-item.active { background: #eff6ff; border-color: #2563eb; box-shadow: 0 0 0 1px #2563eb inset; }
    .pp-item-logo { width: 56px; height: 56px; border-radius: 12px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: 800; color: #475569; font-size: .85rem; text-align: center; line-height: 1.05; }
    .pp-item-main { flex: 1; min-width: 0; }
    .pp-item-mitra { font-weight: 800; color: #0f2f57; font-size: .95rem; }
    .pp-item-period { font-size: .72rem; color: #64748b; }
    .pp-item-meta { font-size: .72rem; color: #475569; margin-top: 4px; line-height: 1.35; }
    .pp-item-stats { display: flex; gap: 8px; align-items: center; font-size: .68rem; color: #475569; margin-top: 6px; }
    .pp-item-stats span strong { color: #0f2f57; font-weight: 800; }
    .pp-item-chevron { color: #94a3b8; align-self: center; font-size: 1.1rem; }

    .pp-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px; font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
    .pp-badge-warning { background: #fef3c7; color: #b45309; }
    .pp-badge-success { background: #d1fae5; color: #047857; }
    .pp-badge-danger { background: #fee2e2; color: #b91c1c; }
    .pp-badge-neutral { background: #e2e8f0; color: #475569; }

    .pp-detail-card { padding: 20px; border-radius: 18px; background: #fff; border: 1px solid rgba(15, 23, 42, .06); box-shadow: 0 14px 32px rgba(15, 23, 42, .06); }
    .pp-detail-head { display: flex; justify-content: space-between; align-items: start; padding-bottom: 12px; border-bottom: 1px solid #eef2f7; }
    .pp-detail-title { font-size: 1.1rem; font-weight: 800; color: #0f2f57; margin: 0; }
    .pp-detail-sub { font-size: .78rem; color: #64748b; margin-top: 3px; }

    .pp-timeline { display: flex; justify-content: space-between; padding: 22px 8px 8px; position: relative; }
    .pp-timeline::before { content: ""; position: absolute; left: 8%; right: 8%; top: 38px; height: 2px; background: #e2e8f0; z-index: 0; }
    .pp-step { display: flex; flex-direction: column; align-items: center; position: relative; z-index: 1; flex: 1; }
    .pp-step-dot { width: 32px; height: 32px; border-radius: 999px; background: #e2e8f0; color: #94a3b8; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .85rem; border: 3px solid #fff; }
    .pp-step.done .pp-step-dot { background: #2563eb; color: #fff; }
    .pp-step.active .pp-step-dot { background: #f59e0b; color: #fff; box-shadow: 0 0 0 4px rgba(245, 158, 11, .2); }
    .pp-step.rejected .pp-step-dot { background: #dc2626; color: #fff; }
    .pp-step-label { font-size: .75rem; font-weight: 800; color: #0f2f57; margin-top: 8px; }
    .pp-step-date { font-size: .68rem; color: #64748b; margin-top: 2px; }

    .pp-mini-card { background: #f8fafc; border-radius: 14px; padding: 12px; display: flex; gap: 10px; align-items: center; }
    .pp-mini-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
    .pp-mini-icon.bg-blue { background: #dbeafe; color: #1d4ed8; }
    .pp-mini-icon.bg-rose { background: #ffe4e6; color: #be123c; }
    .pp-mini-icon.bg-slate { background: #e2e8f0; color: #475569; }
    .pp-mini-icon.bg-amber { background: #fef3c7; color: #b45309; }
    .pp-mini-label { font-size: .68rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .pp-mini-value { font-weight: 800; color: #0f2f57; }

    .pp-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 24px; font-size: .82rem; }
    .pp-info-grid .pp-info-row { display: grid; grid-template-columns: 85px 1fr; gap: 6px; align-items: center; }
    .pp-info-grid .pp-info-row .label { color: #64748b; }
    .pp-info-grid .pp-info-row .value { font-weight: 700; color: #0f2f57; }
</style>

<div class="sa-report-page">
    <div class="sa-report-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="small text-uppercase fw-bold" style="letter-spacing:.08em;color:#fbbf24;">Jasa &middot; Penagihan Bulanan Garbarata</div>
            <h4 class="fw-bold mb-1"><i class="bi bi-receipt-cutoff me-2"></i>Rekap Tagihan Garbarata</h4>
            <p class="mb-0 small">Admin Jasa mengambil data pemakaian Garbarata AMC, mengunci rekap bulanan, lalu membuat Tagihan Jasa.</p>
        </div>
        @if(auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Admin Jasa']))
            <a href="{{ route('pengajuan-penagihan-garbarata.create') }}" class="btn btn-warning fw-bold">
                <i class="bi bi-download me-1"></i>Ambil Data Garbarata
            </a>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0">{{ session('success') }}</div>
    @endif

    {{-- Stat Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-6 col-lg">
            <div class="pp-stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="pp-stat-label">TOTAL REKAP</div>
                        <div class="pp-stat-value">{{ number_format($stats['total']) }}</div>
                    </div>
                    <div class="pp-stat-icon bg-blue"><i class="bi bi-file-earmark-text"></i></div>
                </div>
                @php $d = $stats['delta_total']; @endphp
                <div class="pp-stat-delta {{ $d === null ? 'flat' : ($d > 0 ? 'up' : ($d < 0 ? 'down' : 'flat')) }}">
                    @if($d === null) baseline bulan lalu
                    @elseif($d > 0) <i class="bi bi-arrow-up-right"></i> {{ $d }}% dari bulan lalu
                    @elseif($d < 0) <i class="bi bi-arrow-down-right"></i> {{ abs($d) }}% dari bulan lalu
                    @else — sama seperti bulan lalu
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg">
            <div class="pp-stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="pp-stat-label">PERLU VALIDASI</div>
                        <div class="pp-stat-value">{{ number_format($stats['menunggu']) }}</div>
                    </div>
                    <div class="pp-stat-icon bg-amber"><i class="bi bi-hourglass-split"></i></div>
                </div>
                @php $d = $stats['delta_menunggu']; @endphp
                <div class="pp-stat-delta {{ $d === null ? 'flat' : ($d > 0 ? 'up' : ($d < 0 ? 'down' : 'flat')) }}">
                    @if($d === null) baseline bulan lalu
                    @elseif($d > 0) <i class="bi bi-arrow-up-right"></i> {{ $d }}% dari bulan lalu
                    @elseif($d < 0) <i class="bi bi-arrow-down-right"></i> {{ abs($d) }}% dari bulan lalu
                    @else — sama seperti bulan lalu
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg">
            <div class="pp-stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="pp-stat-label">SIAP DITAGIH</div>
                        <div class="pp-stat-value">{{ number_format($stats['siap']) }}</div>
                    </div>
                    <div class="pp-stat-icon bg-emerald"><i class="bi bi-check2-circle"></i></div>
                </div>
                @php $d = $stats['delta_siap']; @endphp
                <div class="pp-stat-delta {{ $d === null ? 'flat' : ($d > 0 ? 'up' : ($d < 0 ? 'down' : 'flat')) }}">
                    @if($d === null) baseline bulan lalu
                    @elseif($d > 0) <i class="bi bi-arrow-up-right"></i> {{ $d }}% dari bulan lalu
                    @elseif($d < 0) <i class="bi bi-arrow-down-right"></i> {{ abs($d) }}% dari bulan lalu
                    @else — sama seperti bulan lalu
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg">
            <div class="pp-stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="pp-stat-label">TOTAL NOMINAL</div>
                        <div class="pp-stat-value">{{ $formatRpShort($stats['nominal']) }}</div>
                    </div>
                    <div class="pp-stat-icon bg-violet"><i class="bi bi-cash-coin"></i></div>
                </div>
                @php $d = $stats['delta_nominal']; @endphp
                <div class="pp-stat-delta {{ $d === null ? 'flat' : ($d > 0 ? 'up' : ($d < 0 ? 'down' : 'flat')) }}">
                    @if($d === null) baseline bulan lalu
                    @elseif($d > 0) <i class="bi bi-arrow-up-right"></i> {{ $d }}% dari bulan lalu
                    @elseif($d < 0) <i class="bi bi-arrow-down-right"></i> {{ abs($d) }}% dari bulan lalu
                    @else — sama seperti bulan lalu
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-12 col-lg">
            <div class="pp-stat-card">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div class="pp-stat-label">TREN NOMINAL (6 BULAN)</div>
                    <div class="small fw-bold text-muted">6 Bulan</div>
                </div>
                <svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" style="width:100%;height:60px;display:block;">
                    <defs>
                        <linearGradient id="ppSpark" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#3b82f6" stop-opacity=".35"/>
                            <stop offset="100%" stop-color="#3b82f6" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <polygon fill="url(#ppSpark)" points="0,{{ $h }} {{ $sparkPath }} {{ $w }},{{ $h }}"/>
                    <polyline fill="none" stroke="#2563eb" stroke-width="2" points="{{ $sparkPath }}"/>
                </svg>
                <div class="d-flex justify-content-between mt-1" style="font-size:.62rem;color:#94a3b8;font-weight:700;">
                    @foreach($trend as $t)
                        <span>{{ $t['label'] }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="pp-filter-bar">
                <div style="flex:1;min-width:140px;">
                    <label>Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        @foreach($statusOpt as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex:1.5;min-width:180px;">
                    <label>Mitra</label>
                    <select name="mitra_jasa_id" class="form-select form-select-sm">
                        <option value="">Semua Mitra</option>
                        @foreach($mitraOptions as $m)
                            <option value="{{ $m->id }}" @selected(($filters['mitra_jasa_id'] ?? '') == $m->id)>{{ $m->nama_mitra }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex:1;min-width:140px;">
                    <label>Periode</label>
                    <select name="periode_bulan" class="form-select form-select-sm">
                        <option value="">Semua Periode</option>
                        @foreach($bulanOpt as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['periode_bulan'] ?? '') == $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex:.8;min-width:110px;">
                    <label>Tahun</label>
                    <input type="number" name="periode_tahun" min="2020" max="2100" class="form-control form-control-sm" value="{{ $filters['periode_tahun'] ?? now()->year }}">
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('pengajuan-penagihan-garbarata.index') }}" class="btn btn-sm btn-outline-primary fw-bold">Reset</a>
                    <button class="btn btn-sm btn-primary fw-bold"><i class="bi bi-funnel me-1"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Two-column: list + detail --}}
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm pp-list-card h-100">
                <div class="pp-list-head">
                    <h6>Daftar Rekap ({{ $items->total() }})</h6>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
                <div class="pp-list-body">
                    @forelse($items as $row)
                        @php
                            $kode = $row->mitra?->kode_mitra ?: '—';
                            $namaDisp = $row->mitra?->nama_mitra ?? '—';
                        @endphp
                        <div class="pp-item" data-pengajuan-id="{{ $row->id }}">
                            <div class="pp-item-logo">{{ $kode }}</div>
                            <div class="pp-item-main">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="pp-item-mitra">{{ $namaDisp }}</div>
                                        <div class="pp-item-period">Periode {{ $row->periode_label }}</div>
                                    </div>
                                    <span class="pp-badge {{ $statusBadgeClass[$row->status] ?? 'pp-badge-neutral' }}">
                                        <i class="bi {{ $statusIcon[$row->status] ?? 'bi-circle' }}"></i>{{ $row->status_label }}
                                    </span>
                                </div>
                                <div class="pp-item-meta">
                                    <i class="bi bi-calendar3"></i> {{ optional($row->created_at)->format('d M Y H:i') }}<br>
                                    <span class="text-muted">Dibuat oleh</span> <strong>{{ strtoupper($row->creator?->name ?? '—') }}</strong>
                                </div>
                                <div class="pp-item-stats">
                                    <span>Pemakaian <strong>{{ number_format($row->jumlah_pemakaian) }}</strong></span>
                                    <span>·</span>
                                    <span>Rentang <strong>{{ number_format($row->total_rentang) }}</strong></span>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right pp-item-chevron"></i>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Belum ada rekap.
                        </div>
                    @endforelse
                </div>
                @if($items->hasPages())
                    <div class="card-footer bg-white border-0 d-flex justify-content-center">{{ $items->links() }}</div>
                @endif
            </div>
        </div>

        <div class="col-lg-7">
            <div id="ppDetailEmpty" class="pp-detail-card text-center text-muted py-5 h-100 d-flex flex-column align-items-center justify-content-center">
                <i class="bi bi-hand-index-thumb fs-1 mb-2"></i>
                <div class="fw-bold">Pilih salah satu rekap</div>
                <div class="small">Klik item di kiri untuk melihat detail rekap.</div>
            </div>
            <div id="ppDetailPanel" class="pp-detail-card h-100 d-none">
                <div class="pp-detail-head">
                    <div>
                        <h5 class="pp-detail-title" id="ppDetailTitle">—</h5>
                        <div class="pp-detail-sub" id="ppDetailSub">—</div>
                    </div>
                    <span class="pp-badge" id="ppDetailBadge">—</span>
                </div>

                <div class="pp-timeline" id="ppTimeline">
                    <div class="pp-step" data-step="1">
                        <div class="pp-step-dot">1</div>
                        <div class="pp-step-label">Draft</div>
                        <div class="pp-step-date" data-date>—</div>
                    </div>
                    <div class="pp-step" data-step="2">
                        <div class="pp-step-dot">2</div>
                        <div class="pp-step-label">Dikunci</div>
                        <div class="pp-step-date" data-date>—</div>
                    </div>
                    <div class="pp-step" data-step="3">
                        <div class="pp-step-dot">3</div>
                        <div class="pp-step-label">Siap Ditagih</div>
                        <div class="pp-step-date" data-date>—</div>
                    </div>
                    <div class="pp-step" data-step="4">
                        <div class="pp-step-dot">4</div>
                        <div class="pp-step-label">Ditagihkan</div>
                        <div class="pp-step-date" data-date>—</div>
                    </div>
                </div>

                <div class="mt-3 mb-2 fw-bold" style="color:#0f2f57;">Ringkasan Pemakaian</div>
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="pp-mini-card">
                            <div class="pp-mini-icon bg-blue"><i class="bi bi-airplane-engines"></i></div>
                            <div>
                                <div class="pp-mini-label">Pemakaian</div>
                                <div class="pp-mini-value" id="ppMiniPemakaian">0</div>
                                <div style="font-size:.62rem;color:#94a3b8;">Batch</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="pp-mini-card">
                            <div class="pp-mini-icon bg-rose"><i class="bi bi-stopwatch"></i></div>
                            <div>
                                <div class="pp-mini-label">Rentang</div>
                                <div class="pp-mini-value" id="ppMiniRentang">0</div>
                                <div style="font-size:.62rem;color:#94a3b8;">Batch</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="pp-mini-card">
                            <div class="pp-mini-icon bg-slate"><i class="bi bi-collection"></i></div>
                            <div>
                                <div class="pp-mini-label">Total Batch</div>
                                <div class="pp-mini-value" id="ppMiniBatch">0</div>
                                <div style="font-size:.62rem;color:#94a3b8;">Batch</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="pp-mini-card">
                            <div class="pp-mini-icon bg-amber"><i class="bi bi-cash"></i></div>
                            <div>
                                <div class="pp-mini-label">Total Nominal</div>
                                <div class="pp-mini-value" id="ppMiniNominal">Rp 0</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-2 mb-2 fw-bold" style="color:#0f2f57;">Informasi Rekap</div>
                <div class="pp-info-grid">
                    <div class="pp-info-row"><span class="label">Mitra</span><span class="value">: <span id="ppInfoMitra">—</span></span></div>
                    <div class="pp-info-row"><span class="label">Tgl Rekap</span><span class="value">: <span id="ppInfoTanggal">—</span></span></div>
                    <div class="pp-info-row"><span class="label">Periode</span><span class="value">: <span id="ppInfoPeriode">—</span></span></div>
                    <div class="pp-info-row"><span class="label">Dibuat oleh</span><span class="value">: <span id="ppInfoCreator">—</span></span></div>
                    <div class="pp-info-row"><span class="label">Tahun</span><span class="value">: <span id="ppInfoTahun">—</span></span></div>
                    <div class="pp-info-row"><span class="label">Status</span><span class="value">: <span id="ppInfoStatus">—</span></span></div>
                </div>

                <div id="ppCatatanWrapper" class="mt-3 d-none">
                    <div class="small fw-bold" style="color:#64748b;">Catatan Rekap</div>
                    <div class="small p-2 rounded" style="background:#f8fafc;color:#334155;" id="ppCatatanAmc">—</div>
                </div>
                <div id="ppCatatanAdminWrapper" class="mt-2 d-none">
                    <div class="small fw-bold" style="color:#64748b;">Catatan Sistem</div>
                    <div class="small p-2 rounded" style="background:#fef3c7;color:#92400e;" id="ppCatatanAdmin">—</div>
                </div>

                <div class="d-flex gap-2 mt-4 flex-wrap">
                    <a href="#" id="ppBtnDetail" class="btn btn-outline-primary fw-bold flex-grow-1">
                        <i class="bi bi-eye me-1"></i>Lihat Detail
                    </a>
                    <a href="#" id="ppBtnVerifikasi" class="btn btn-primary fw-bold flex-grow-1 d-none">
                        <i class="bi bi-check2-square me-1"></i>Validasi
                    </a>
                    <a href="#" id="ppBtnBuatTagihan" class="btn btn-success fw-bold flex-grow-1 d-none">
                        <i class="bi bi-receipt me-1"></i>Buat Tagihan
                    </a>
                    <button type="button" id="ppBtnUnduh" class="btn btn-outline-secondary fw-bold flex-grow-1" onclick="window.print()">
                        <i class="bi bi-download me-1"></i>Unduh
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const DATA = @json($detailData);
        const CREATE_TAGIHAN_URL = @json(route('tagihan-jasa.create'));

        const formatRp = v => 'Rp ' + (Math.round(Number(v) || 0)).toLocaleString('id-ID');
        const formatNum = v => (Number(v) || 0).toLocaleString('id-ID');

        const empty = document.getElementById('ppDetailEmpty');
        const panel = document.getElementById('ppDetailPanel');

        const STATUS_BADGE = {
            DIAJUKAN: { cls: 'pp-badge-warning', icon: 'bi-hourglass-split' },
            DISETUJUI: { cls: 'pp-badge-success', icon: 'bi-check2-circle' },
            DITOLAK: { cls: 'pp-badge-danger', icon: 'bi-x-circle' },
        };

        function setStep(stepNum, mode, date) {
            const step = panel.querySelector(`.pp-step[data-step="${stepNum}"]`);
            step.classList.remove('done', 'active', 'rejected');
            if (mode) step.classList.add(mode);
            step.querySelector('[data-date]').textContent = date || '—';
        }

        function selectPengajuan(id) {
            const d = DATA[id];
            if (!d) return;

            empty.classList.add('d-none');
            panel.classList.remove('d-none');

            document.querySelectorAll('.pp-item').forEach(el => el.classList.toggle('active', String(el.dataset.pengajuanId) === String(id)));

            document.getElementById('ppDetailTitle').textContent = `${d.mitra_nama || '—'} – ${d.periode_label || ''}`;
            document.getElementById('ppDetailSub').textContent = `Dibuat pada ${d.created_at || '—'} oleh ${d.created_by || '—'}`;

            const badge = STATUS_BADGE[d.status] || { cls: 'pp-badge-neutral', icon: 'bi-circle' };
            const badgeEl = document.getElementById('ppDetailBadge');
            badgeEl.className = 'pp-badge ' + badge.cls;
            badgeEl.innerHTML = `<i class="bi ${badge.icon}"></i>${d.status_label}`;

            // Timeline
            setStep(1, 'done', d.created_at || '—');
            if (d.status === 'DIAJUKAN') {
                setStep(2, 'active', d.created_at || '—');
                setStep(3, null, '—');
                setStep(4, null, '—');
            } else if (d.status === 'DISETUJUI') {
                setStep(2, 'done', d.created_at || '—');
                if (d.tagihan_id) {
                    setStep(3, 'done', d.reviewed_at || '—');
                    setStep(4, 'active', d.tagihan_at || '—');
                } else {
                    setStep(3, 'active', d.reviewed_at || '—');
                    setStep(4, null, '—');
                }
            } else if (d.status === 'DITOLAK') {
                setStep(2, 'done', d.created_at || '—');
                setStep(3, 'rejected', d.reviewed_at || '—');
                setStep(4, null, '—');
            } else {
                setStep(2, null, '—');
                setStep(3, null, '—');
                setStep(4, null, '—');
            }

            document.getElementById('ppMiniPemakaian').textContent = formatNum(d.jumlah_pemakaian);
            document.getElementById('ppMiniRentang').textContent = formatNum(d.total_rentang);
            document.getElementById('ppMiniBatch').textContent = formatNum(d.total_batch);
            document.getElementById('ppMiniNominal').textContent = formatRp(d.nominal);

            document.getElementById('ppInfoMitra').textContent = d.mitra_nama || '—';
            document.getElementById('ppInfoTanggal').textContent = d.created_at || '—';
            document.getElementById('ppInfoPeriode').textContent = d.periode_label?.split(' ')[0] || '—';
            document.getElementById('ppInfoCreator').textContent = d.created_by || '—';
            document.getElementById('ppInfoTahun').textContent = d.periode_tahun || '—';
            document.getElementById('ppInfoStatus').innerHTML = `<span class="pp-badge ${badge.cls}"><i class="bi ${badge.icon}"></i>${d.status_label}</span>`;

            const cAmc = document.getElementById('ppCatatanWrapper');
            if (d.catatan_amc) { cAmc.classList.remove('d-none'); document.getElementById('ppCatatanAmc').textContent = d.catatan_amc; }
            else cAmc.classList.add('d-none');

            const cAdm = document.getElementById('ppCatatanAdminWrapper');
            if (d.catatan_admin) { cAdm.classList.remove('d-none'); document.getElementById('ppCatatanAdmin').textContent = d.catatan_admin; }
            else cAdm.classList.add('d-none');

            document.getElementById('ppBtnDetail').href = d.detail_url;
            const verBtn = document.getElementById('ppBtnVerifikasi');
            if (d.can_review) {
                verBtn.classList.remove('d-none');
                verBtn.href = d.detail_url;
            } else {
                verBtn.classList.add('d-none');
            }

            const buatTagihanBtn = document.getElementById('ppBtnBuatTagihan');
            if (d.status === 'DISETUJUI' && !d.tagihan_id) {
                const url = new URL(CREATE_TAGIHAN_URL, window.location.origin);
                url.searchParams.set('amc_garbarata_pengajuan_id', d.id);
                buatTagihanBtn.href = url.toString();
                buatTagihanBtn.classList.remove('d-none');
            } else {
                buatTagihanBtn.classList.add('d-none');
            }
        }

        document.querySelectorAll('.pp-item').forEach(el => {
            el.addEventListener('click', () => selectPengajuan(el.dataset.pengajuanId));
        });

        // Auto-select first item if available
        const firstItem = document.querySelector('.pp-item');
        if (firstItem) selectPengajuan(firstItem.dataset.pengajuanId);
    })();
</script>
@endsection
