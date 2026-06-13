@extends('layouts.app')
@section('title', 'Dashboard Bendahara Pengeluaran')

@push('css')
<style>
    /* ============== Section heading ============== */
    .section-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 1.75rem 0 1rem;
    }
    .section-heading h6 {
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #475569;
        margin: 0;
        display: inline-flex;
        align-items: center;
        gap: .6rem;
    }
    .section-heading h6::before {
        content: '';
        width: 4px; height: 18px;
        border-radius: 4px;
        background: linear-gradient(180deg, #6366f1, #2563eb);
    }

    /* ============== KPI Card (colorful variants) ============== */
    .kpi-card {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        padding: 1.15rem 1.2rem 1.1rem;
        height: 100%;
        transition: transform .18s ease, box-shadow .18s ease;
        position: relative;
        overflow: hidden;
    }
    .kpi-card::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: var(--kpi-accent, linear-gradient(90deg, #6366f1, #2563eb));
    }
    .kpi-card::after {
        content: '';
        position: absolute;
        right: -45px; top: -45px;
        width: 130px; height: 130px;
        border-radius: 50%;
        background: var(--kpi-glow, radial-gradient(circle, rgba(99,102,241,.10), transparent 70%));
        z-index: 0;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(15,23,42,.08);
    }
    .kpi-card > * { position: relative; z-index: 1; }

    .kpi-card .kpi-icon {
        width: 46px; height: 46px;
        border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
        color: #fff;
        background: var(--kpi-icon-bg, linear-gradient(135deg, #6366f1, #2563eb));
        box-shadow: 0 6px 16px var(--kpi-icon-shadow, rgba(99,102,241,.30));
    }
    .kpi-card .kpi-label {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .05em;
        color: #64748b;
        text-transform: uppercase;
        margin: .8rem 0 .15rem;
    }
    .kpi-card .kpi-value {
        font-size: 1.75rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
        margin: 0;
    }
    .kpi-card .kpi-foot {
        font-size: .78rem;
        color: #6b7280;
        margin-top: .65rem;
        display: flex; flex-wrap: wrap; gap: .3rem;
    }
    .kpi-card .kpi-pill {
        display: inline-flex; align-items: center;
        font-size: .7rem;
        font-weight: 600;
        padding: .15rem .6rem;
        border-radius: 999px;
        white-space: nowrap;
    }

    /* KPI accent variants — applied via class */
    .kpi-warning {
        --kpi-accent: linear-gradient(90deg, #f59e0b, #f97316);
        --kpi-glow:   radial-gradient(circle, rgba(245,158,11,.18), transparent 70%);
        --kpi-icon-bg: linear-gradient(135deg, #fbbf24, #f97316);
        --kpi-icon-shadow: rgba(245,158,11,.35);
    }
    .kpi-info {
        --kpi-accent: linear-gradient(90deg, #06b6d4, #3b82f6);
        --kpi-glow:   radial-gradient(circle, rgba(59,130,246,.18), transparent 70%);
        --kpi-icon-bg: linear-gradient(135deg, #38bdf8, #3b82f6);
        --kpi-icon-shadow: rgba(59,130,246,.35);
    }
    .kpi-primary {
        --kpi-accent: linear-gradient(90deg, #8b5cf6, #6366f1);
        --kpi-glow:   radial-gradient(circle, rgba(99,102,241,.18), transparent 70%);
        --kpi-icon-bg: linear-gradient(135deg, #a78bfa, #6366f1);
        --kpi-icon-shadow: rgba(99,102,241,.35);
    }
    .kpi-danger {
        --kpi-accent: linear-gradient(90deg, #f43f5e, #ef4444);
        --kpi-glow:   radial-gradient(circle, rgba(239,68,68,.18), transparent 70%);
        --kpi-icon-bg: linear-gradient(135deg, #fb7185, #ef4444);
        --kpi-icon-shadow: rgba(239,68,68,.35);
    }
    .kpi-success {
        --kpi-accent: linear-gradient(90deg, #10b981, #14b8a6);
        --kpi-glow:   radial-gradient(circle, rgba(16,185,129,.18), transparent 70%);
        --kpi-icon-bg: linear-gradient(135deg, #34d399, #10b981);
        --kpi-icon-shadow: rgba(16,185,129,.35);
    }

    /* Soft tint pills (used in foot & badges) */
    .tint-warning  { background: rgba(245, 158, 11, .14); color: #b45309; }
    .tint-info     { background: rgba(59, 130, 246, .14); color: #1d4ed8; }
    .tint-danger   { background: rgba(239, 68, 68,  .14); color: #b91c1c; }
    .tint-success  { background: rgba(16, 185, 129, .14); color: #047857; }
    .tint-primary  { background: rgba(99, 102, 241, .14); color: #4338ca; }
    .tint-slate    { background: rgba(100, 116, 139, .12); color: #334155; }

    /* Panel (section card) */
    .panel {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        height: 100%;
        position: relative;
        overflow: hidden;
    }
    .panel-head {
        padding: 1rem 1.25rem .75rem;
        border-bottom: 1px solid #f1f3f7;
        display: flex; align-items: center; justify-content: space-between;
        background: linear-gradient(180deg, #fafbff 0%, #ffffff 100%);
    }
    .panel-head h6 {
        margin: 0;
        font-size: .95rem;
        font-weight: 700;
        color: #0f172a;
        display: inline-flex; align-items: center; gap: .5rem;
    }
    .panel-head h6 i {
        width: 30px; height: 30px;
        border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(99,102,241,.12);
        color: #4f46e5;
        font-size: .95rem;
    }
    /* Panel head icon variants — applied per section */
    .panel-head h6 i.ph-warning  { background: rgba(245,158,11,.15); color: #b45309; }
    .panel-head h6 i.ph-info     { background: rgba(59,130,246,.15); color: #1d4ed8; }
    .panel-head h6 i.ph-primary  { background: rgba(99,102,241,.15); color: #4338ca; }
    .panel-head h6 i.ph-danger   { background: rgba(239,68,68,.15);  color: #b91c1c; }
    .panel-head h6 i.ph-success  { background: rgba(16,185,129,.15); color: #047857; }
    .panel-head h6 i.ph-purple   { background: rgba(139,92,246,.15); color: #6d28d9; }
    .panel-body { padding: .25rem 0; }
    .panel-foot {
        padding: .65rem 1.25rem;
        border-top: 1px solid #f1f3f7;
        font-size: .78rem;
        color: #64748b;
    }

    /* Tabs inside panel */
    .panel-tabs {
        display: flex; gap: .35rem;
        padding: .25rem 1rem 0;
        border-bottom: 1px solid #f1f3f7;
        margin-bottom: .25rem;
    }
    .panel-tab {
        background: transparent;
        border: 0;
        padding: .65rem .95rem;
        font-size: .82rem;
        font-weight: 600;
        color: #64748b;
        border-bottom: 2px solid transparent;
        cursor: pointer;
        display: inline-flex; align-items: center; gap: .35rem;
        transition: color .15s;
    }
    .panel-tab:hover { color: #334155; }
    .panel-tab.active {
        color: #4f46e5;
        border-bottom-color: #6366f1;
    }
    .panel-tab .count {
        font-size: .68rem;
        font-weight: 700;
        background: #f1f5f9;
        color: #475569;
        padding: .05rem .45rem;
        border-radius: 999px;
    }
    .panel-tab.active .count {
        background: linear-gradient(135deg, #6366f1, #2563eb);
        color: #fff;
    }

    /* Compact list rows inside panels */
    .compact-list { list-style: none; padding: 0; margin: 0; }
    .compact-list > li {
        display: flex; align-items: center; gap: .85rem;
        padding: .85rem 1.25rem;
        border-bottom: 1px solid #f4f5f8;
        transition: background .15s;
    }
    .compact-list > li:hover { background: #fafbff; }
    .compact-list > li:last-child { border-bottom: 0; }
    .compact-list .row-icon {
        width: 38px; height: 38px;
        border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        color: #fff;
        font-size: 1rem;
    }
    .compact-list .row-icon.bg-info-grad    { background: linear-gradient(135deg, #38bdf8, #2563eb); }
    .compact-list .row-icon.bg-primary-grad { background: linear-gradient(135deg, #a78bfa, #6366f1); }
    .compact-list .row-icon.bg-danger-grad  { background: linear-gradient(135deg, #fb7185, #ef4444); }
    .compact-list .row-icon.bg-warning-grad { background: linear-gradient(135deg, #fbbf24, #f97316); }
    .compact-list .row-icon.bg-success-grad { background: linear-gradient(135deg, #34d399, #10b981); }
    .compact-list .row-title { font-weight: 600; color: #0f172a; font-size: .9rem; }
    .compact-list .row-sub   { font-size: .78rem; color: #64748b; }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 2.5rem 1rem;
        color: #94a3b8;
    }
    .empty-state i { font-size: 2.4rem; opacity: .35; display: block; margin-bottom: .35rem; }

    /* Table */
    .panel table.table { margin-bottom: 0; }
    .panel table.table > thead > tr > th {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        background: #fafbfc;
        border-bottom: 1px solid #f1f3f7;
        padding: .65rem 1rem;
    }
    .panel table.table > tbody > tr > td {
        padding: .75rem 1rem;
        font-size: .85rem;
        border-bottom: 1px solid #f4f5f8;
    }
    .panel table.table > tbody > tr:last-child > td { border-bottom: 0; }

    /* Timeline */
    .timeline { position: relative; padding-left: 1.25rem; }
    .timeline::before {
        content: ''; position: absolute;
        left: 11px; top: 4px; bottom: 4px; width: 2px;
        background: linear-gradient(180deg, #c7d2fe, #ddd6fe);
    }
    .timeline-item { position: relative; padding-bottom: 1.1rem; }
    .timeline-item::before {
        content: ''; position: absolute;
        left: -1.25rem; top: 5px;
        width: 12px; height: 12px;
        border-radius: 50%;
        background: #fff;
        border: 3px solid;
        border-image: linear-gradient(135deg, #6366f1, #2563eb) 1;
        box-shadow: 0 0 0 3px rgba(99,102,241,.12);
    }
    .timeline-item:last-child { padding-bottom: 0; }

    /* Pajak summary chip */
    .pajak-stat {
        display: flex; align-items: center; justify-content: space-between;
        padding: .85rem 1rem;
        border-radius: .75rem;
        background: #fafbff;
        border: 1px solid #eef0f4;
        transition: transform .15s, box-shadow .15s;
    }
    .pajak-stat:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15,23,42,.05); }
    .pajak-stat + .pajak-stat { margin-top: .55rem; }
    .pajak-stat .label { font-size: .82rem; font-weight: 600; color: #334155; }
    .pajak-stat .value { font-size: 1.15rem; font-weight: 800; }

    .pajak-stat.pajak-billing  { background: linear-gradient(135deg, #fff1f2, #ffe4e6); border-color: #fecdd3; }
    .pajak-stat.pajak-ntpn     { background: linear-gradient(135deg, #fffbeb, #fef3c7); border-color: #fde68a; }
    .pajak-stat.pajak-setor    { background: linear-gradient(135deg, #ecfdf5, #d1fae5); border-color: #a7f3d0; }

    /* Header brand */
    .dashboard-hero {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #2563eb 100%);
        color: #fff;
        border-radius: 1rem;
        padding: 1.4rem 1.6rem;
        margin-bottom: 1rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 12px 30px rgba(79, 70, 229, .25);
    }
    .dashboard-hero::before {
        content: '';
        position: absolute; right: -60px; top: -60px;
        width: 220px; height: 220px;
        border-radius: 50%;
        background: rgba(255,255,255,.10);
    }
    .dashboard-hero::after {
        content: '';
        position: absolute; right: 80px; bottom: -100px;
        width: 200px; height: 200px;
        border-radius: 50%;
        background: rgba(255,255,255,.06);
    }
    .dashboard-hero h4 { color: #fff; font-weight: 800; }
    .dashboard-hero .meta { color: rgba(255,255,255,.85); }
    .dashboard-hero .quick-btn {
        background: rgba(255,255,255,.18);
        color: #fff !important;
        border: 1px solid rgba(255,255,255,.25);
        backdrop-filter: blur(6px);
        font-weight: 600;
    }
    .dashboard-hero .quick-btn:hover {
        background: rgba(255,255,255,.30);
        color: #fff;
    }
    .dashboard-hero .quick-btn.btn-primary-light {
        background: #fff;
        color: #4f46e5 !important;
        border-color: #fff;
    }
    .dashboard-hero .quick-btn.btn-primary-light:hover {
        background: #f1f5ff;
    }

    /* Action buttons inside compact-list */
    .btn-grad-info    { background: linear-gradient(135deg, #38bdf8, #2563eb); color: #fff; border: 0; }
    .btn-grad-primary { background: linear-gradient(135deg, #8b5cf6, #4f46e5); color: #fff; border: 0; }
    .btn-grad-danger  { background: linear-gradient(135deg, #fb7185, #ef4444); color: #fff; border: 0; }
    .btn-grad-info:hover, .btn-grad-primary:hover, .btn-grad-danger:hover {
        color: #fff; opacity: .92; transform: translateY(-1px);
    }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="dashboard-hero d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center">
    <div>
        <h4 class="mb-1">Dashboard Bendahara Pengeluaran</h4>
        <div class="meta small">
            <i class="bi bi-calendar3 me-1"></i> {{ $now->isoFormat('dddd, D MMMM YYYY') }}
            <span class="mx-2">·</span>
            <i class="bi bi-person me-1"></i> {{ $user->name }}
        </div>
    </div>
    <div class="mt-3 mt-lg-0 d-flex flex-wrap gap-2">
        <a href="{{ route('verifikasi-bendahara.perjaldin.index') }}" class="btn btn-sm quick-btn"><i class="bi bi-airplane me-1"></i> Perjaldin</a>
        <a href="{{ route('verifikasi-bendahara.honorarium.index') }}" class="btn btn-sm quick-btn"><i class="bi bi-people me-1"></i> Honor</a>
        <a href="{{ route('proses-tagihan.index') }}" class="btn btn-sm quick-btn"><i class="bi bi-file-earmark-text me-1"></i> Proses Tagihan</a>
        <a href="{{ route('pembukuan.bku.index') }}" class="btn btn-sm quick-btn btn-primary-light"><i class="bi bi-book me-1"></i> Buka BKU</a>
    </div>
</div>

{{-- ============ KPI ROW ============ --}}
<div class="section-heading">
    <h6>Ringkasan Antrean</h6>
</div>

<div class="row g-3">
    {{-- KPI 1: Verifikasi Tagihan --}}
    @php
        $totalVerif = $tagihanPerjaldin->count() + $tagihanHonorarium->count();
        $totalRevisi = $tagihanPerjaldin->where('status', 'REVISI_BENDAHARA')->count()
                     + $tagihanHonorarium->where('status', 'REVISI_BENDAHARA')->count();
    @endphp
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card kpi-warning">
            <span class="kpi-icon"><i class="bi bi-list-check"></i></span>
            <div class="kpi-label">Verifikasi Tagihan</div>
            <h3 class="kpi-value">{{ $totalVerif }}</h3>
            <div class="kpi-foot">
                <span class="kpi-pill tint-warning">{{ $tagihanPerjaldin->count() }} Perjaldin</span>
                <span class="kpi-pill tint-info">{{ $tagihanHonorarium->count() }} Honor</span>
                @if($totalRevisi > 0)
                    <span class="kpi-pill tint-danger">{{ $totalRevisi }} Revisi</span>
                @endif
            </div>
        </div>
    </div>

    {{-- KPI 2: NPI Siap Dibuat --}}
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card kpi-info">
            <span class="kpi-icon"><i class="bi bi-file-earmark-plus"></i></span>
            <div class="kpi-label">NPI Siap Dibuat</div>
            <h3 class="kpi-value">{{ $totalNpiSiap }}</h3>
            <div class="kpi-foot">
                <span class="kpi-pill tint-slate">{{ $spmKontrakSiapNpi->count() }} Kontrak</span>
                <span class="kpi-pill tint-slate">{{ $spmPerjaldinSiapNpi->count() }} Perjaldin</span>
                <span class="kpi-pill tint-slate">{{ $spmHonorSiapNpi->count() }} Honor</span>
            </div>
        </div>
    </div>

    {{-- KPI 3: SP2D Siap Dicatat --}}
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card kpi-primary">
            <span class="kpi-icon"><i class="bi bi-journal-check"></i></span>
            <div class="kpi-label">SP2D Siap Dicatat</div>
            <h3 class="kpi-value">{{ $totalSp2dSiap }}</h3>
            <div class="kpi-foot">
                <span class="kpi-pill tint-slate">{{ $npiKontrakSiapSp2d->count() }} Kontrak</span>
                <span class="kpi-pill tint-slate">{{ $npiPerjaldinSiapSp2d->count() }} Perjaldin</span>
                <span class="kpi-pill tint-slate">{{ $npiHonorSiapSp2d->count() }} Honor</span>
            </div>
        </div>
    </div>

    {{-- KPI 4: Perpajakan --}}
    @php $totalPajakAktif = $pajakBelumBilling->count() + $pajakSudahBilling->count(); @endphp
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card {{ $totalPajakAktif === 0 ? 'kpi-success' : 'kpi-danger' }}">
            <span class="kpi-icon"><i class="bi bi-receipt"></i></span>
            <div class="kpi-label">Pajak Perlu Tindakan</div>
            <h3 class="kpi-value">{{ $totalPajakAktif }}</h3>
            <div class="kpi-foot">
                @if($pajakBelumBilling->count() > 0)
                    <span class="kpi-pill tint-danger">{{ $pajakBelumBilling->count() }} Buat Billing</span>
                @endif
                @if($pajakSudahBilling->count() > 0)
                    <span class="kpi-pill tint-warning">{{ $pajakSudahBilling->count() }} Input NTPN</span>
                @endif
                @if($totalPajakAktif === 0)
                    <span class="kpi-pill tint-success">Semua disetor</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ============ ANTREAN + PRIORITAS ============ --}}
<div class="section-heading">
    <h6>Antrean Verifikasi & Prioritas</h6>
</div>

<div class="row g-3">
    {{-- Antrean (with tabs) --}}
    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-head">
                <h6><i class="bi bi-list-check ph-warning"></i> Antrean Verifikasi Saya</h6>
                <span class="text-muted small">5 teratas / kategori</span>
            </div>
            <div class="panel-tabs">
                <button class="panel-tab active" type="button" data-tab="perjaldin">
                    Perjaldin <span class="count">{{ $tagihanPerjaldin->count() }}</span>
                </button>
                <button class="panel-tab" type="button" data-tab="honorarium">
                    Honorarium <span class="count">{{ $tagihanHonorarium->count() }}</span>
                </button>
            </div>

            <div class="panel-body">
                @foreach(['perjaldin', 'honorarium'] as $tab)
                    <div class="tab-content-pane" data-pane="{{ $tab }}" style="{{ $tab === 'perjaldin' ? '' : 'display:none' }}">
                        @php $items = $antreanList->where('tab', $tab)->take(5); @endphp
                        @if($items->isEmpty())
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                Tidak ada antrean {{ $tab === 'perjaldin' ? 'Perjaldin' : 'Honorarium' }}.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>No Tagihan</th>
                                            <th>Uraian</th>
                                            <th class="text-end">Nominal</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $item)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold text-primary">{{ $item->nomor }}</div>
                                                    <div class="text-muted" style="font-size:.72rem;">{{ $item->usia }} hari lalu</div>
                                                </td>
                                                <td>
                                                    <div class="text-truncate" style="max-width:240px;" title="{{ $item->uraian }}">{{ $item->uraian }}</div>
                                                </td>
                                                <td class="text-end fw-semibold">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                                                <td class="text-center">
                                                    @if($item->is_revisi)
                                                        <span class="kpi-pill tint-danger">Revisi</span>
                                                    @else
                                                        <span class="kpi-pill tint-warning">Pending</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <a href="{{ $item->url }}" class="btn btn-sm btn-outline-primary">Proses</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Prioritas --}}
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-head">
                <h6><i class="bi bi-exclamation-triangle ph-danger"></i> Prioritas Hari Ini</h6>
            </div>
            <div class="panel-body">
                @if($prioritasHariIni->isEmpty())
                    <div class="empty-state">
                        <i class="bi bi-check2-circle text-success"></i>
                        Tidak ada verifikasi mendesak.
                    </div>
                @else
                    <ul class="compact-list">
                        @foreach($prioritasHariIni as $p)
                            <li>
                                <span class="row-icon {{ $p->is_revisi ? 'bg-danger-grad' : 'bg-warning-grad' }}">
                                    <i class="bi {{ $p->is_revisi ? 'bi-arrow-counterclockwise' : 'bi-hourglass-split' }}"></i>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="row-title text-truncate">{{ $p->jenis }}</div>
                                    <div class="row-sub text-truncate">
                                        {{ $p->nomor }} · Rp {{ number_format($p->nominal, 0, ',', '.') }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="text-danger fw-bold" style="font-size:.78rem;">{{ $p->usia }}h</div>
                                    <a href="{{ $p->url }}" class="btn btn-sm btn-link p-0">Buka</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ============ NPI & SP2D ============ --}}
<div class="section-heading">
    <h6>Penerbitan Dokumen</h6>
</div>

<div class="row g-3">
    {{-- NPI --}}
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-head">
                <h6><i class="bi bi-file-earmark-plus ph-info"></i> Pembuatan NPI</h6>
                <span class="kpi-pill tint-info">{{ $totalNpiSiap }} siap</span>
            </div>
            <div class="panel-body">
                @if($totalNpiSiap === 0)
                    <div class="empty-state"><i class="bi bi-check2-circle text-success"></i>Tidak ada NPI yang perlu dibuat.</div>
                @else
                    <ul class="compact-list">
                        @if($spmKontrakSiapNpi->count() > 0)
                            <li>
                                <span class="row-icon bg-info-grad"><i class="bi bi-file-earmark-text"></i></span>
                                <div class="flex-grow-1">
                                    <div class="row-title">NPI Kontrak</div>
                                    <div class="row-sub">{{ $spmKontrakSiapNpi->count() }} SPM Kontrak Final menunggu NPI</div>
                                </div>
                                <a href="#" class="btn btn-sm btn-grad-info">Buat</a>
                            </li>
                        @endif
                        @if($spmPerjaldinSiapNpi->count() > 0)
                            <li>
                                <span class="row-icon bg-info-grad"><i class="bi bi-airplane"></i></span>
                                <div class="flex-grow-1">
                                    <div class="row-title">NPI Perjaldin</div>
                                    <div class="row-sub">{{ $spmPerjaldinSiapNpi->count() }} SPM Perjaldin Final menunggu NPI</div>
                                </div>
                                <a href="{{ route('proses-tagihan.index') }}" class="btn btn-sm btn-grad-info">Buat</a>
                            </li>
                        @endif
                        @if($spmHonorSiapNpi->count() > 0)
                            <li>
                                <span class="row-icon bg-info-grad"><i class="bi bi-people"></i></span>
                                <div class="flex-grow-1">
                                    <div class="row-title">NPI Honor</div>
                                    <div class="row-sub">{{ $spmHonorSiapNpi->count() }} SPM Honor Final menunggu NPI</div>
                                </div>
                                <a href="#" class="btn btn-sm btn-grad-info">Buat</a>
                            </li>
                        @endif
                    </ul>
                @endif
            </div>
        </div>
    </div>

    {{-- SP2D --}}
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-head">
                <h6><i class="bi bi-journal-check ph-primary"></i> Pencatatan SP2D</h6>
                <span class="kpi-pill tint-primary">{{ $totalSp2dSiap }} siap</span>
            </div>
            <div class="panel-body">
                @if($totalSp2dSiap === 0)
                    <div class="empty-state"><i class="bi bi-check2-circle text-success"></i>Tidak ada SP2D yang perlu dicatat.</div>
                @else
                    <ul class="compact-list">
                        @if($npiKontrakSiapSp2d->count() > 0)
                            <li>
                                <span class="row-icon bg-primary-grad"><i class="bi bi-file-earmark-text"></i></span>
                                <div class="flex-grow-1">
                                    <div class="row-title">SP2D Kontrak</div>
                                    <div class="row-sub">{{ $npiKontrakSiapSp2d->count() }} NPI Kontrak Final menunggu SP2D</div>
                                </div>
                                <a href="#" class="btn btn-sm btn-grad-primary">Catat</a>
                            </li>
                        @endif
                        @if($npiPerjaldinSiapSp2d->count() > 0)
                            <li>
                                <span class="row-icon bg-primary-grad"><i class="bi bi-airplane"></i></span>
                                <div class="flex-grow-1">
                                    <div class="row-title">SP2D Perjaldin</div>
                                    <div class="row-sub">{{ $npiPerjaldinSiapSp2d->count() }} NPI Perjaldin Final menunggu SP2D</div>
                                </div>
                                <a href="{{ route('proses-tagihan.index') }}" class="btn btn-sm btn-grad-primary">Catat</a>
                            </li>
                        @endif
                        @if($npiHonorSiapSp2d->count() > 0)
                            <li>
                                <span class="row-icon bg-primary-grad"><i class="bi bi-people"></i></span>
                                <div class="flex-grow-1">
                                    <div class="row-title">SP2D Honor</div>
                                    <div class="row-sub">{{ $npiHonorSiapSp2d->count() }} NPI Honor Final menunggu SP2D</div>
                                </div>
                                <a href="#" class="btn btn-sm btn-grad-primary">Catat</a>
                            </li>
                        @endif
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ============ PAJAK ============ --}}
<div class="section-heading">
    <h6>Penyetoran Pajak Kontrak</h6>
</div>

<div class="panel">
    <div class="row g-0">
        <div class="col-lg-4 border-end">
            <div class="p-3">
                <div class="pajak-stat pajak-billing">
                    <span class="label"><i class="bi bi-receipt-cutoff me-1 text-danger"></i>Belum Billing</span>
                    <span class="value text-danger">{{ $pajakBelumBilling->count() }}</span>
                </div>
                <div class="pajak-stat pajak-ntpn">
                    <span class="label"><i class="bi bi-bank2 me-1 text-warning"></i>Menunggu NTPN</span>
                    <span class="value" style="color:#b45309;">{{ $pajakSudahBilling->count() }}</span>
                </div>
                <div class="pajak-stat pajak-setor">
                    <span class="label"><i class="bi bi-check2-circle me-1 text-success"></i>Sudah Setor</span>
                    <span class="value text-success">{{ $pajakSudahSetor->count() }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="d-flex align-items-center justify-content-between px-3 pt-3">
                <div class="fw-bold" style="font-size:.85rem;">Pajak Perlu Tindakan</div>
                <a href="{{ route('pajak-potongan.kontrak.index') }}" class="btn btn-sm btn-link text-decoration-none">Kelola Pajak Kontrak <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="table-responsive mt-2">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Jenis Pajak</th>
                            <th>No Tagihan</th>
                            <th class="text-end">Nominal</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($potonganPajak->whereNull('ntpn')->take(4) as $pajak)
                            <tr>
                                <td class="fw-semibold">{{ $pajak->pajak?->nama_pajak ?? $pajak->nama_pajak_snapshot }}</td>
                                <td class="text-muted">{{ $pajak->tagihan?->nomor_tagihan ?? '-' }}</td>
                                <td class="text-end fw-semibold">Rp {{ number_format($pajak->nominal_potongan, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    @if(!$pajak->kode_billing)
                                        <span class="kpi-pill tint-danger">Buat Billing</span>
                                    @else
                                        <span class="kpi-pill tint-warning">Input NTPN</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('pajak-potongan.kontrak.detail', $pajak->id) }}" class="btn btn-sm btn-grad-danger">Proses</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <i class="bi bi-check2-circle text-success"></i>
                                    Semua tagihan pajak telah disetor.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ============ PAJAK HONORARIUM ============ --}}
<div class="section-heading">
    <h6>Penyetoran Pajak Honorarium</h6>
</div>

<div class="panel">
    <div class="row g-0">
        <div class="col-lg-4 border-end">
            <div class="p-3">
                <div class="pajak-stat pajak-billing">
                    <span class="label"><i class="bi bi-receipt-cutoff me-1 text-danger"></i>Belum Billing</span>
                    <span class="value text-danger">{{ $pajakHonorBelumBilling->count() }}</span>
                </div>
                <div class="pajak-stat pajak-ntpn">
                    <span class="label"><i class="bi bi-bank2 me-1 text-warning"></i>Menunggu NTPN</span>
                    <span class="value" style="color:#b45309;">{{ $pajakHonorSudahBilling->count() }}</span>
                </div>
                <div class="pajak-stat pajak-setor">
                    <span class="label"><i class="bi bi-check2-circle me-1 text-success"></i>Sudah Setor</span>
                    <span class="value text-success">{{ $pajakHonorSudahSetor->count() }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="d-flex align-items-center justify-content-between px-3 pt-3">
                <div class="fw-bold" style="font-size:.85rem;">PPh 21 Perlu Tindakan</div>
                <a href="{{ route('pajak-potongan.honor.index') }}" class="btn btn-sm btn-link text-decoration-none">Kelola Pajak Honorarium <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="table-responsive mt-2">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Jenis Pajak</th>
                            <th>No Tagihan</th>
                            <th class="text-end">Nominal</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($potonganPajakHonor->whereNull('ntpn')->take(4) as $pajak)
                            <tr>
                                <td class="fw-semibold">{{ $pajak->pajak?->nama_pajak ?? ($pajak->nama_pajak_snapshot ?? 'PPh 21') }}</td>
                                <td class="text-muted">{{ $pajak->tagihan?->nomor_tagihan ?? '-' }}</td>
                                <td class="text-end fw-semibold">Rp {{ number_format($pajak->nominal_potongan, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    @if(!$pajak->kode_billing)
                                        <span class="kpi-pill tint-danger">Buat Billing</span>
                                    @else
                                        <span class="kpi-pill tint-warning">Input NTPN</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('pajak-potongan.honor.detail', $pajak->id) }}" class="btn btn-sm btn-grad-danger">Proses</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <i class="bi bi-check2-circle text-success"></i>
                                    Semua PPh 21 honorarium telah disetor.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ============ PEMBUKUAN ============ --}}
<div class="section-heading">
    <h6>Pembukuan & Bank</h6>
</div>

<div class="row g-3">
    {{-- Saldo Summary --}}
    <div class="col-lg-4">
        <div class="panel h-100">
            <div class="panel-head">
                <h6><i class="bi bi-wallet2 ph-purple"></i> Ringkasan Pembukuan</h6>
            </div>
            <div class="panel-body p-3">
                <div class="p-3 mb-3 rounded-3" style="background: linear-gradient(135deg, #eef2ff, #e0e7ff); border:1px solid #c7d2fe;">
                    <div class="kpi-label mb-1" style="margin-top:0; color:#4338ca;">Saldo BKU Terakhir</div>
                    <div class="fw-bold mb-0" style="font-size:1.4rem; color:#312e81;">Rp {{ number_format($saldoTerakhirBku, 0, ',', '.') }}</div>
                </div>

                <div class="p-3 mb-3 rounded-3" style="background: linear-gradient(135deg, #fef2f2, #fee2e2); border:1px solid #fecaca;">
                    <div class="kpi-label mb-1" style="margin-top:0; color:#b91c1c;">Pengeluaran Bulan Ini</div>
                    <div class="fw-bold mb-0" style="font-size:1.2rem; color:#991b1b;">Rp {{ number_format($totalPengeluaranBulanIni, 0, ',', '.') }}</div>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <div class="pajak-stat flex-column align-items-start {{ $mutasiBelumRekon > 0 ? 'pajak-billing' : 'pajak-setor' }}">
                            <span class="label">Mutasi Belum Rekon</span>
                            <span class="value {{ $mutasiBelumRekon > 0 ? 'text-danger' : 'text-success' }}">{{ $mutasiBelumRekon }}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="pajak-stat flex-column align-items-start pajak-setor">
                            <span class="label">Pajak Disetor</span>
                            <span class="value text-success">{{ $pajakSudahSetor->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BKU terbaru --}}
    <div class="col-lg-8">
        <div class="panel h-100">
            <div class="panel-head">
                <h6><i class="bi bi-journal-text ph-primary"></i> Buku Kas Umum Terbaru</h6>
                <a href="{{ route('pembukuan.bku.index') }}" class="btn btn-sm btn-link text-decoration-none">Lihat Semua <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No Bukti</th>
                                <th>Uraian</th>
                                <th class="text-end">Nominal</th>
                                <th class="text-end pe-3">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bkuTerbaru as $bku)
                                <tr>
                                    <td class="text-muted">{{ $bku->tanggal_transaksi->format('d/m/Y') }}</td>
                                    <td class="fw-semibold text-primary">{{ $bku->nomor_bukti ?? '-' }}</td>
                                    <td>
                                        <div class="text-truncate" style="max-width:200px;" title="{{ $bku->uraian }}">{{ $bku->uraian }}</div>
                                    </td>
                                    <td class="text-end">
                                        @if($bku->debit > 0)
                                            <span class="text-success fw-semibold">+Rp {{ number_format($bku->debit, 0, ',', '.') }}</span>
                                        @elseif($bku->kredit > 0)
                                            <span class="text-danger fw-semibold">-Rp {{ number_format($bku->kredit, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3 fw-semibold">Rp {{ number_format($bku->saldo_akhir, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        Belum ada transaksi BKU.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============ LAPORAN & AKTIVITAS ============ --}}
<div class="section-heading">
    <h6>Laporan & Aktivitas</h6>
</div>

<div class="row g-3 mb-4">
    {{-- Bunga + Pengesahan --}}
    <div class="col-lg-7 d-flex flex-column gap-3">
        <div class="panel">
            <div class="panel-head">
                <h6><i class="bi bi-graph-up-arrow ph-success"></i> Buku Pembantu Bunga Rekening</h6>
                <span class="text-muted small">5 transaksi terakhir</span>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Deskripsi</th>
                                <th class="text-end pe-3">Bunga</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transaksiBunga as $bunga)
                                <tr>
                                    <td class="text-muted">{{ $bunga->tanggal_transaksi->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="text-truncate" style="max-width:280px;">{{ $bunga->deskripsi }}</div>
                                    </td>
                                    <td class="text-end pe-3 fw-semibold text-success">+Rp {{ number_format($bunga->kredit, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="empty-state"><i class="bi bi-inbox"></i>Belum ada bunga bulan ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h6><i class="bi bi-file-earmark-check ph-info"></i> Buku Pengesahan Belanja</h6>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>No Laporan</th>
                                <th>Periode</th>
                                <th class="text-end">Pengeluaran</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($laporanPengesahan as $lp)
                                <tr>
                                    <td class="fw-semibold text-primary">{{ $lp->nomor_laporan ?? 'LP-'.$lp->id }}</td>
                                    <td class="text-muted">Bulan {{ $lp->periode_bulan }} / {{ $lp->tahun }}</td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($lp->total_pengeluaran, 0, ',', '.') }}</td>
                                    <td class="text-end pe-3">
                                        <a href="#" class="btn btn-sm btn-outline-secondary">Lihat</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="empty-state"><i class="bi bi-inbox"></i>Belum ada Laporan Pengesahan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Aktivitas --}}
    <div class="col-lg-5">
        <div class="panel h-100">
            <div class="panel-head">
                <h6><i class="bi bi-clock-history ph-purple"></i> Aktivitas Terbaru</h6>
                <span class="kpi-pill tint-primary">{{ $aktivitasTerbaru->where('created_at', '>=', $now->copy()->startOfDay())->count() }} hari ini</span>
            </div>
            <div class="panel-body p-3">
                @if($aktivitasTerbaru->isEmpty())
                    <div class="empty-state"><i class="bi bi-inbox"></i>Belum ada aktivitas.</div>
                @else
                    <div class="timeline">
                        @foreach($aktivitasTerbaru->take(7) as $log)
                            <div class="timeline-item">
                                <div class="fw-semibold text-dark" style="font-size:.88rem;">{{ $log->aksi ?? 'Melakukan aksi' }}</div>
                                @if($log->catatan)
                                    <div class="text-muted" style="font-size:.78rem;">{{ \Illuminate\Support\Str::limit($log->catatan, 80) }}</div>
                                @endif
                                <div class="text-muted mt-1" style="font-size:.72rem;">
                                    <i class="bi bi-clock me-1"></i>{{ $log->created_at->diffForHumans() }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.panel-tabs').forEach(function (tabsEl) {
        var panel = tabsEl.closest('.panel');
        tabsEl.querySelectorAll('.panel-tab').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = btn.getAttribute('data-tab');
                tabsEl.querySelectorAll('.panel-tab').forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                panel.querySelectorAll('.tab-content-pane').forEach(function (p) {
                    p.style.display = (p.getAttribute('data-pane') === target) ? '' : 'none';
                });
            });
        });
    });
});
</script>
@endpush
