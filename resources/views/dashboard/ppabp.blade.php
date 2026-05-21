@extends('layouts.app')

@section('title', 'Dashboard PPABP')

@push('css')
<style>
    /* ============== Welcome banner ============== */
    .welcome-banner {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%);
        border-radius: 1.25rem;
        padding: 1.75rem 2rem;
        color: #fff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 12px 32px rgba(79, 70, 229, .25);
    }
    .welcome-banner,
    .welcome-banner h3,
    .welcome-banner p,
    .welcome-banner span,
    .welcome-banner strong {
        color: #fff !important;
    }
    .welcome-banner h3 strong { color: #fde047 !important; }
    .welcome-banner::before {
        content: '';
        position: absolute;
        right: -80px; top: -80px;
        width: 280px; height: 280px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }
    .welcome-banner::after {
        content: '';
        position: absolute;
        right: 60px; bottom: -60px;
        width: 180px; height: 180px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.06);
    }
    .welcome-banner > * { position: relative; z-index: 1; }
    .welcome-banner h3 {
        font-weight: 800;
        margin: 0 0 .35rem;
        letter-spacing: -.01em;
        color: #ffffff !important;
        text-shadow: 0 1px 2px rgba(0,0,0,.15);
    }
    .welcome-banner p {
        margin: 0;
        opacity: 1;
        color: rgba(255,255,255,.92) !important;
    }
    .welcome-banner .badge-action {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        background: rgba(255, 255, 255, 0.18);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.25);
        padding: .55rem 1rem;
        border-radius: 999px;
        font-size: .82rem;
        font-weight: 600;
        margin-top: 1rem;
    }
    .welcome-banner .badge-action .dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #fde047;
        box-shadow: 0 0 0 4px rgba(253, 224, 71, .35);
        animation: pulse 1.8s infinite;
    }
    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(253, 224, 71, .55); }
        50%      { box-shadow: 0 0 0 8px rgba(253, 224, 71, 0); }
    }
    .welcome-illustration {
        position: absolute;
        right: 1.25rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 6rem;
        opacity: .15;
    }

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

    /* ============== KPI Card ============== */
    .kpi-card {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        padding: 1.25rem;
        height: 100%;
        transition: transform .18s ease, box-shadow .18s ease;
        position: relative;
        overflow: hidden;
        cursor: default;
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
        right: -50px; top: -50px;
        width: 140px; height: 140px;
        border-radius: 50%;
        background: var(--kpi-glow, radial-gradient(circle, rgba(99,102,241,.10), transparent 70%));
        z-index: 0;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px rgba(15, 23, 42, .08);
    }
    .kpi-card > * { position: relative; z-index: 1; }
    .kpi-card .kpi-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        color: #fff;
        background: var(--kpi-icon-bg, linear-gradient(135deg, #6366f1, #2563eb));
        box-shadow: 0 6px 16px var(--kpi-icon-shadow, rgba(99, 102, 241, .30));
    }
    .kpi-card .kpi-label {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .05em;
        color: #64748b;
        text-transform: uppercase;
        margin: .85rem 0 .15rem;
    }
    .kpi-card .kpi-value {
        font-size: 1.85rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
        margin: 0;
    }
    .kpi-card .kpi-foot {
        font-size: .78rem;
        color: #6b7280;
        margin-top: .65rem;
        display: flex;
        flex-wrap: wrap;
        gap: .35rem;
        align-items: center;
    }
    .kpi-card .kpi-pill {
        display: inline-flex;
        align-items: center;
        font-size: .7rem;
        font-weight: 600;
        padding: .15rem .65rem;
        border-radius: 999px;
        white-space: nowrap;
    }

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

    .tint-warning  { background: rgba(245, 158, 11, .14); color: #b45309; }
    .tint-info     { background: rgba(59, 130, 246, .14); color: #1d4ed8; }
    .tint-danger   { background: rgba(239, 68, 68,  .14); color: #b91c1c; }
    .tint-success  { background: rgba(16, 185, 129, .14); color: #047857; }
    .tint-primary  { background: rgba(99, 102, 241, .14); color: #4338ca; }
    .tint-slate    { background: rgba(100, 116, 139, .12); color: #334155; }

    /* ============== Panel (section card) ============== */
    .panel {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        position: relative;
        overflow: hidden;
    }
    .panel.h-fill { height: 100%; }
    .panel-head {
        padding: 1.1rem 1.25rem .9rem;
        border-bottom: 1px solid #f1f3f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(180deg, #fafbff 0%, #ffffff 100%);
    }
    .panel-head h6 {
        margin: 0;
        font-size: .95rem;
        font-weight: 700;
        color: #0f172a;
        display: inline-flex;
        align-items: center;
        gap: .55rem;
    }
    .panel-head h6 i {
        width: 32px; height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(99,102,241,.12);
        color: #4f46e5;
        font-size: 1rem;
    }
    .panel-head h6 i.ph-warning  { background: rgba(245,158,11,.15); color: #b45309; }
    .panel-head h6 i.ph-info     { background: rgba(59,130,246,.15); color: #1d4ed8; }
    .panel-head h6 i.ph-primary  { background: rgba(99,102,241,.15); color: #4338ca; }
    .panel-head h6 i.ph-danger   { background: rgba(239,68,68,.15);  color: #b91c1c; }
    .panel-head h6 i.ph-success  { background: rgba(16,185,129,.15); color: #047857; }
    .panel-body { padding: 1rem 1.25rem; }
    .panel-foot {
        padding: .75rem 1.25rem;
        border-top: 1px solid #f1f3f7;
        font-size: .8rem;
        color: #64748b;
        background: #fafbff;
    }

    /* ============== Action table ============== */
    .table-modern {
        margin: 0;
    }
    .table-modern thead th {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #64748b;
        background: #f8fafc;
        border-top: 0;
        border-bottom: 1px solid #eef0f4;
        padding: .85rem 1.25rem;
    }
    .table-modern tbody td {
        padding: .9rem 1.25rem;
        vertical-align: middle;
        border-color: #f1f3f7;
        font-size: .87rem;
    }
    .table-modern tbody tr:hover td {
        background: #fafbff;
    }
    .table-modern .doc-no {
        font-weight: 700;
        color: #1e293b;
        font-size: .87rem;
    }
    .table-modern .doc-desc {
        font-size: .76rem;
        color: #64748b;
        margin-top: .15rem;
    }

    /* ============== Status badges ============== */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .72rem;
        font-weight: 600;
        padding: .25rem .7rem;
        border-radius: 999px;
        white-space: nowrap;
    }
    .status-pill::before {
        content: '';
        width: 6px; height: 6px;
        border-radius: 50%;
        background: currentColor;
    }
    .status-draft       { background: rgba(100,116,139,.12); color: #475569; }
    .status-revisi      { background: rgba(239,68,68,.12);   color: #b91c1c; }
    .status-verifikasi  { background: rgba(59,130,246,.12);  color: #1d4ed8; }
    .status-disetujui   { background: rgba(16,185,129,.12);  color: #047857; }

    /* ============== Empty state ============== */
    .empty-state {
        text-align: center;
        padding: 2.5rem 1rem;
        color: #94a3b8;
    }
    .empty-state i {
        font-size: 2.5rem;
        margin-bottom: .75rem;
        opacity: .5;
        display: block;
    }

    /* ============== Quick actions ============== */
    .quick-action {
        display: flex;
        align-items: center;
        gap: .85rem;
        padding: .9rem 1rem;
        border-radius: .75rem;
        background: #fff;
        border: 1px solid #eef0f4;
        text-decoration: none;
        color: #1e293b;
        transition: all .18s ease;
    }
    .quick-action:hover {
        border-color: #6366f1;
        background: #fafbff;
        transform: translateX(4px);
        color: #4f46e5;
        text-decoration: none;
    }
    .quick-action .qa-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .quick-action .qa-title {
        font-weight: 600;
        font-size: .88rem;
        margin: 0;
    }
    .quick-action .qa-sub {
        font-size: .73rem;
        color: #94a3b8;
        margin: 0;
    }
    .quick-action .qa-arrow {
        margin-left: auto;
        color: #cbd5e1;
        transition: all .18s ease;
    }
    .quick-action:hover .qa-arrow {
        color: #6366f1;
        transform: translateX(2px);
    }

    /* ============== Personnel avatar ============== */
    .personel-avatar {
        width: 32px; height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #a5b4fc, #6366f1);
        color: #fff;
        font-weight: 700;
        font-size: .75rem;
        margin-right: -.6rem;
        border: 2px solid #fff;
    }
</style>
@endpush

@section('content')

@php
    $hour = (int) date('H');
    $greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 18 ? 'Selamat Sore' : 'Selamat Malam'));
    $needsActionTotal = $kpi['draft'] + $kpi['revisi'];
@endphp

{{-- ============================================================
     1. WELCOME BANNER
     ============================================================ --}}
<div class="welcome-banner mb-4">
    <i class="bi bi-person-badge welcome-illustration d-none d-md-block"></i>
    <div class="row align-items-center">
        <div class="col-md-8">
            <h3>{{ $greeting }}, {{ Auth::user()->name }} 👋</h3>
            <p class="fs-6">
                @if($needsActionTotal > 0)
                    Ada <strong>{{ $needsActionTotal }} tagihan honorarium</strong>
                    yang menunggu tindakan Anda hari ini.
                @else
                    Semua tagihan honorarium sudah tertangani. Saatnya menyiapkan honor periode berikutnya.
                @endif
            </p>
            <div class="badge-action">
                <span class="dot"></span>
                <span>{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</span>
            </div>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="{{ route('honorarium.create') }}" class="btn btn-light fw-bold px-4 py-2 rounded-pill shadow-sm">
                <i class="bi bi-plus-circle-fill me-1"></i> Buat Honorarium Baru
            </a>
        </div>
    </div>
</div>

{{-- ============================================================
     2. KPI CARDS — 4 fokus: Draft, Revisi, Verifikasi, Disetujui
     ============================================================ --}}
<div class="section-heading">
    <h6><i class="bi bi-speedometer2"></i> Ringkasan Tagihan Honorarium</h6>
    <span class="text-muted small">Tahun anggaran {{ $tahun }}</span>
</div>

<div class="row g-3 mb-2">
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card kpi-warning">
            <div class="kpi-icon"><i class="bi bi-pencil-square"></i></div>
            <div class="kpi-label">Tagihan Draft</div>
            <div class="kpi-value">{{ $kpi['draft'] }}</div>
            <div class="kpi-foot">
                <span class="kpi-pill tint-warning">
                    <i class="bi bi-cash-coin me-1"></i>
                    Rp {{ number_format($kpi['nominal_draft'], 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card kpi-danger">
            <div class="kpi-icon"><i class="bi bi-arrow-counterclockwise"></i></div>
            <div class="kpi-label">Perlu Revisi</div>
            <div class="kpi-value">{{ $kpi['revisi'] }}</div>
            <div class="kpi-foot">
                <span class="kpi-pill tint-danger">
                    <i class="bi bi-exclamation-triangle me-1"></i> Dikembalikan verifikator
                </span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card kpi-info">
            <div class="kpi-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="kpi-label">Dalam Verifikasi</div>
            <div class="kpi-value">{{ $kpi['verifikasi'] }}</div>
            <div class="kpi-foot">
                <span class="kpi-pill tint-info">
                    <i class="bi bi-cash-coin me-1"></i>
                    Rp {{ number_format($kpi['nominal_verifikasi'], 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card kpi-success">
            <div class="kpi-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="kpi-label">Disetujui / Selesai</div>
            <div class="kpi-value">{{ $kpi['disetujui'] }}</div>
            <div class="kpi-foot">
                <span class="kpi-pill tint-success">
                    <i class="bi bi-people me-1"></i>
                    {{ $kpi['total_penerima_tahun_ini'] }} penerima tahun {{ $tahun }}
                </span>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     3. CHARTS + QUICK ACTIONS
     ============================================================ --}}
<div class="section-heading">
    <h6><i class="bi bi-graph-up-arrow"></i> Analisis & Aksi Cepat</h6>
</div>

<div class="row g-3 mb-2">
    {{-- Tren Tagihan 6 Bulan --}}
    <div class="col-lg-7">
        <div class="panel h-fill">
            <div class="panel-head">
                <h6><i class="bi bi-bar-chart-line ph-primary"></i> Tren Tagihan Honorarium</h6>
                <span class="kpi-pill tint-primary">
                    {{ $kpi['tagihan_bulan_ini'] }} tagihan bulan ini
                </span>
            </div>
            <div class="panel-body">
                <div style="height: 280px; position: relative;">
                    <canvas id="trenChart"></canvas>
                </div>
            </div>
            <div class="panel-foot">
                <i class="bi bi-info-circle me-1"></i>
                Total nominal bulan ini: <strong class="text-dark">Rp {{ number_format($kpi['nominal_bulan_ini'], 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>

    {{-- Distribusi Status + Quick Actions --}}
    <div class="col-lg-5 d-flex flex-column gap-3">
        <div class="panel">
            <div class="panel-head">
                <h6><i class="bi bi-pie-chart-fill ph-info"></i> Distribusi Status</h6>
            </div>
            <div class="panel-body">
                <div style="height: 220px; position: relative;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h6><i class="bi bi-lightning-charge-fill ph-warning"></i> Aksi Cepat</h6>
            </div>
            <div class="panel-body d-flex flex-column gap-2">
                <a href="{{ route('honorarium.create') }}" class="quick-action">
                    <span class="qa-icon" style="background: rgba(99,102,241,.12); color: #4f46e5;">
                        <i class="bi bi-plus-lg"></i>
                    </span>
                    <div>
                        <p class="qa-title">Buat Tagihan Honorarium</p>
                        <p class="qa-sub">Mulai draft baru</p>
                    </div>
                    <i class="bi bi-arrow-right qa-arrow"></i>
                </a>
                <a href="{{ route('honorarium.index') }}" class="quick-action">
                    <span class="qa-icon" style="background: rgba(59,130,246,.12); color: #1d4ed8;">
                        <i class="bi bi-list-ul"></i>
                    </span>
                    <div>
                        <p class="qa-title">Daftar Honorarium</p>
                        <p class="qa-sub">Lihat semua tagihan</p>
                    </div>
                    <i class="bi bi-arrow-right qa-arrow"></i>
                </a>
                <a href="{{ route('employees.index') }}" class="quick-action">
                    <span class="qa-icon" style="background: rgba(16,185,129,.12); color: #047857;">
                        <i class="bi bi-people"></i>
                    </span>
                    <div>
                        <p class="qa-title">Master Pegawai</p>
                        <p class="qa-sub">Kelola data pegawai</p>
                    </div>
                    <i class="bi bi-arrow-right qa-arrow"></i>
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     4. PANEL: PERLU TINDAKAN (DRAFT + REVISI)
     ============================================================ --}}
<div class="section-heading">
    <h6><i class="bi bi-clipboard-check"></i> Meja Kerja Anda</h6>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="panel">
            <div class="panel-head">
                <h6><i class="bi bi-inbox-fill ph-warning"></i> Tagihan Perlu Tindakan</h6>
                <span class="kpi-pill tint-warning">
                    {{ $perlu_tindakan->count() }} dari {{ $needsActionTotal }} item
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-modern align-middle">
                    <thead>
                        <tr>
                            <th>Nomor Tagihan</th>
                            <th>Status</th>
                            <th>Nilai Bruto</th>
                            <th>Penerima</th>
                            <th>Diperbarui</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($perlu_tindakan as $t)
                            @php
                                $isDraft = $t->status === 'DRAFT';
                                $isRevisi = str_starts_with($t->status, 'DITOLAK_') || str_starts_with($t->status, 'REVISI_');
                                $statusClass = $isDraft ? 'status-draft' : 'status-revisi';
                                $statusLabel = $isDraft ? 'Draft' : str_replace(['DITOLAK_', 'REVISI_'], ['Ditolak ', 'Revisi '], $t->status);
                                $jumlahPenerima = $t->detailHonorarium->count();
                            @endphp
                            <tr>
                                <td>
                                    <div class="doc-no">{{ $t->nomor_tagihan }}</div>
                                    <div class="doc-desc">{{ \Illuminate\Support\Str::limit($t->deskripsi, 55) }}</div>
                                </td>
                                <td><span class="status-pill {{ $statusClass }}">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $statusLabel)) }}</span></td>
                                <td><span class="fw-bold text-dark">Rp {{ number_format($t->total_bruto, 0, ',', '.') }}</span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @foreach($t->detailHonorarium->take(3) as $d)
                                            <span class="personel-avatar" title="{{ $d->nama_personel }}">
                                                {{ \Illuminate\Support\Str::upper(substr($d->nama_personel, 0, 1)) }}
                                            </span>
                                        @endforeach
                                        @if($jumlahPenerima > 3)
                                            <span class="personel-avatar" style="background: linear-gradient(135deg,#94a3b8,#64748b);">
                                                +{{ $jumlahPenerima - 3 }}
                                            </span>
                                        @endif
                                        <span class="ms-3 text-muted small">{{ $jumlahPenerima }} orang</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted small">{{ $t->updated_at->diffForHumans() }}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('honorarium.edit', $t->id) }}" class="btn btn-sm btn-primary fw-semibold rounded-pill px-3">
                                        <i class="bi bi-pencil-square me-1"></i> {{ $isDraft ? 'Lengkapi' : 'Revisi' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <h6 class="text-muted mb-1">Tidak ada tagihan yang perlu tindakan</h6>
                                        <small>Semua draft sudah diajukan dan tidak ada revisi yang dikembalikan.</small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($perlu_tindakan->count() > 0)
                <div class="panel-foot text-end">
                    <a href="{{ route('honorarium.index') }}" class="text-decoration-none fw-semibold text-primary small">
                        Lihat semua tagihan <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ============================================================
     5. PANEL: DALAM VERIFIKASI + SELESAI TERBARU
     ============================================================ --}}
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="panel h-fill">
            <div class="panel-head">
                <h6><i class="bi bi-clock-history ph-info"></i> Sedang Diverifikasi</h6>
                <span class="kpi-pill tint-info">{{ $kpi['verifikasi'] }} aktif</span>
            </div>
            <div class="table-responsive">
                <table class="table table-modern align-middle">
                    <thead>
                        <tr>
                            <th>Nomor Tagihan</th>
                            <th>Tahap</th>
                            <th>Nilai</th>
                            <th class="text-center">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dalam_verifikasi as $t)
                            @php
                                $tahap = str_replace(['PENDING_', '_'], ['', ' '], $t->status);
                                $tahap = \Illuminate\Support\Str::title(strtolower($tahap));
                            @endphp
                            <tr>
                                <td>
                                    <div class="doc-no">{{ $t->nomor_tagihan }}</div>
                                    <div class="doc-desc">{{ \Illuminate\Support\Str::limit($t->deskripsi, 45) }}</div>
                                </td>
                                <td><span class="status-pill status-verifikasi">{{ $tahap }}</span></td>
                                <td><span class="fw-bold text-dark">Rp {{ number_format($t->total_bruto, 0, ',', '.') }}</span></td>
                                <td class="text-center">
                                    <a href="{{ route('honorarium.show', $t->id) }}" class="btn btn-sm btn-outline-primary rounded-pill">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <i class="bi bi-hourglass"></i>
                                        <h6 class="text-muted mb-0">Tidak ada tagihan sedang diverifikasi</h6>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="panel h-fill">
            <div class="panel-head">
                <h6><i class="bi bi-check2-circle ph-success"></i> Selesai Terbaru</h6>
            </div>
            <div class="panel-body p-0">
                <ul class="list-unstyled mb-0">
                    @forelse($selesai_terbaru as $t)
                        <li class="d-flex align-items-start gap-3 px-3 py-3 border-bottom" style="border-color: #f1f3f7 !important;">
                            <span class="qa-icon flex-shrink-0" style="width:38px;height:38px;background: rgba(16,185,129,.12); color: #047857; border-radius: 10px; display:inline-flex; align-items:center; justify-content:center;">
                                <i class="bi bi-check-lg"></i>
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="fw-semibold text-dark text-truncate">{{ $t->nomor_tagihan }}</div>
                                    <span class="status-pill status-disetujui flex-shrink-0">{{ \Illuminate\Support\Str::title(strtolower(str_replace('_', ' ', $t->status))) }}</span>
                                </div>
                                <div class="text-muted small text-truncate">{{ \Illuminate\Support\Str::limit($t->deskripsi, 45) }}</div>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <span class="small fw-semibold text-success">Rp {{ number_format($t->total_bruto, 0, ',', '.') }}</span>
                                    <span class="small text-muted">{{ $t->updated_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li>
                            <div class="empty-state">
                                <i class="bi bi-clipboard-check"></i>
                                <h6 class="text-muted mb-0">Belum ada honorarium yang selesai</h6>
                            </div>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const fmtRupiah = v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v);
    const fmtCompact = v => {
        if (v >= 1e9) return 'Rp ' + (v / 1e9).toFixed(1) + ' M';
        if (v >= 1e6) return 'Rp ' + (v / 1e6).toFixed(1) + ' Jt';
        if (v >= 1e3) return 'Rp ' + (v / 1e3).toFixed(0) + ' rb';
        return 'Rp ' + v;
    };

    // Tren Chart (Mixed: Bar untuk jumlah, Line untuk nominal)
    const trenCtx = document.getElementById('trenChart');
    if (trenCtx) {
        new Chart(trenCtx, {
            data: {
                labels: {!! json_encode($tren_labels) !!},
                datasets: [
                    {
                        type: 'bar',
                        label: 'Jumlah Tagihan',
                        data: {!! json_encode($tren_jumlah) !!},
                        backgroundColor: 'rgba(99, 102, 241, 0.85)',
                        borderRadius: 8,
                        yAxisID: 'y',
                        order: 2
                    },
                    {
                        type: 'line',
                        label: 'Nominal Bruto',
                        data: {!! json_encode($tren_nominal) !!},
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.12)',
                        borderWidth: 3,
                        tension: 0.35,
                        fill: true,
                        pointBackgroundColor: '#f97316',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        yAxisID: 'y1',
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: { boxWidth: 14, boxHeight: 14, padding: 14, font: { size: 12 } }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,.95)',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(ctx) {
                                if (ctx.dataset.yAxisID === 'y1') {
                                    return ctx.dataset.label + ': ' + fmtRupiah(ctx.raw);
                                }
                                return ctx.dataset.label + ': ' + ctx.raw + ' tagihan';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        ticks: { precision: 0, color: '#6366f1' },
                        title: { display: true, text: 'Jumlah', color: '#6366f1', font: { weight: 600 } },
                        grid: { color: '#f1f3f7' }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        ticks: { color: '#f97316', callback: fmtCompact },
                        title: { display: true, text: 'Nominal', color: '#f97316', font: { weight: 600 } },
                        grid: { drawOnChartArea: false }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Status Distribusi (Doughnut)
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($status_labels) !!},
                datasets: [{
                    data: {!! json_encode($status_data) !!},
                    backgroundColor: ['#f59e0b', '#ef4444', '#3b82f6', '#10b981'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, boxHeight: 12, padding: 12, font: { size: 11 } }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,.95)',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? Math.round(ctx.raw * 100 / total) : 0;
                                return ctx.label + ': ' + ctx.raw + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
