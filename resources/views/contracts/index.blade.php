@extends('layouts.app')
@section('title')
    Manajemen Kontrak
@endsection

@push('css')
<link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
<style>
    body[data-bs-theme="blue-theme"] .main-content { background: #f6f7fb; }

    /* ============ HERO BANNER ============ */
    .kontrak-hero {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%);
        border-radius: 1.25rem;
        padding: 1.5rem 2rem;
        color: #fff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 14px 32px rgba(79,70,229,.25);
        margin-bottom: 1.25rem;
        animation: heroIn .55s cubic-bezier(.22,1,.36,1) both;
    }
    .kontrak-hero::before, .kontrak-hero::after {
        content: ''; position: absolute; border-radius: 50%;
    }
    .kontrak-hero::before {
        right: -90px; top: -90px;
        width: 280px; height: 280px;
        background: rgba(255,255,255,.10);
    }
    .kontrak-hero::after {
        right: 60px; bottom: -70px;
        width: 180px; height: 180px;
        background: rgba(255,255,255,.07);
    }
    .kontrak-hero > * { position: relative; z-index: 1; }
    .kontrak-hero h2 {
        font-weight: 800; color: #fff !important;
        font-size: 1.55rem;
        margin: 0 0 .35rem;
        letter-spacing: -.01em;
        text-shadow: 0 1px 2px rgba(0,0,0,.15);
    }
    .kontrak-hero p { color: rgba(255,255,255,.92) !important; margin: 0; }
    .briefcase-illust {
        position: absolute; right: 1.5rem; top: 50%;
        transform: translateY(-50%) rotate(-8deg);
        font-size: 7rem; opacity: .15;
    }
    .btn-hero-primary {
        background: #fff;
        color: #4f46e5;
        font-weight: 700;
        padding: .65rem 1.25rem;
        border-radius: 999px;
        font-size: .88rem;
        transition: all .2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        box-shadow: 0 6px 16px rgba(0,0,0,.15);
    }
    .btn-hero-primary:hover {
        color: #4338ca; transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(0,0,0,.20);
    }

    /* ============ KPI MINI BAR ============ */
    .kpi-bar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: .75rem;
        margin-bottom: 1.25rem;
    }
    .kpi-mini {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: .85rem;
        padding: .85rem 1rem;
        position: relative;
        overflow: hidden;
        transition: all .25s ease;
        animation: fadeUp .55s cubic-bezier(.22,1,.36,1) both;
    }
    .kpi-mini:nth-child(1) { animation-delay: .12s; }
    .kpi-mini:nth-child(2) { animation-delay: .19s; }
    .kpi-mini:nth-child(3) { animation-delay: .26s; }
    .kpi-mini:nth-child(4) { animation-delay: .33s; }
    .kpi-mini::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 3px;
        background: var(--km-accent, #6366f1);
    }
    .kpi-mini:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(15,23,42,.06);
    }
    .kpi-mini .km-row {
        display: flex; align-items: center; gap: .65rem;
    }
    .kpi-mini .km-icon {
        width: 38px; height: 38px;
        border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.05rem;
        background: var(--km-soft, rgba(99,102,241,.10));
        color: var(--km-accent, #4f46e5);
        flex-shrink: 0;
    }
    .kpi-mini .km-label {
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
    }
    .kpi-mini .km-value {
        font-size: 1.2rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        line-height: 1.1;
        font-variant-numeric: tabular-nums;
    }
    .km-aktif    { --km-accent: #10b981; --km-soft: rgba(16,185,129,.12); }
    .km-pending  { --km-accent: #f59e0b; --km-soft: rgba(245,158,11,.12); }
    .km-draft    { --km-accent: #64748b; --km-soft: rgba(100,116,139,.12); }
    .km-selesai  { --km-accent: #6366f1; --km-soft: rgba(99,102,241,.12); }

    /* ============ MAIN CARD WITH TABS ============ */
    .main-card {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1.25rem;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(15,23,42,.04);
        animation: fadeUp .55s cubic-bezier(.22,1,.36,1) .35s both;
    }
    .tabs-pill {
        display: flex;
        gap: .35rem;
        padding: .65rem .85rem;
        background: linear-gradient(180deg, #fafbff 0%, #fff 100%);
        border-bottom: 1px solid #f1f3f7;
        overflow-x: auto;
        scrollbar-width: thin;
    }
    .tabs-pill .tab-btn {
        flex: 0 0 auto;
        min-width: max-content;
        padding: .65rem 1.25rem;
        border-radius: .65rem;
        background: transparent;
        border: 0;
        color: #64748b;
        font-size: .87rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        transition: all .25s cubic-bezier(.22,1,.36,1);
        white-space: nowrap;
    }
    .tabs-pill .tab-btn:hover {
        color: #1e293b;
        background: #f1f5f9;
    }
    .tabs-pill .tab-btn.active {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        box-shadow: 0 6px 14px rgba(99,102,241,.30);
    }
    .tabs-pill .tab-btn .tab-count {
        background: rgba(255,255,255,.25);
        padding: .1rem .55rem;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 700;
    }
    .tabs-pill .tab-btn:not(.active) .tab-count {
        background: rgba(99,102,241,.12);
        color: #4f46e5;
    }

    /* ============ MODERN TABLE ============ */
    .dt-modern { padding: .85rem 1.25rem; }
    .dt-modern .dataTables_length,
    .dt-modern .dataTables_filter {
        margin-bottom: .85rem;
    }
    .dt-modern .dataTables_length label,
    .dt-modern .dataTables_filter label {
        font-size: .82rem;
        font-weight: 500;
        color: #475569;
    }
    .dt-modern .dataTables_filter input,
    .dt-modern .dataTables_length select {
        border: 1px solid #e2e8f0;
        border-radius: .55rem;
        padding: .35rem .75rem;
        font-size: .85rem;
        background: #f8fafc;
        transition: all .18s ease;
    }
    .dt-modern .dataTables_filter input {
        min-width: 250px;
        padding: .4rem .85rem;
    }
    .dt-modern .dataTables_filter input:focus,
    .dt-modern .dataTables_length select:focus {
        outline: 0;
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(99,102,241,.15);
    }

    table.table-modern-c {
        border-collapse: separate !important;
        border-spacing: 0;
        width: 100% !important;
        border: 0 !important;
    }
    table.table-modern-c thead th {
        background: #f8fafc !important;
        color: #64748b !important;
        font-size: .7rem !important;
        font-weight: 700 !important;
        letter-spacing: .06em;
        text-transform: uppercase;
        padding: .85rem 1rem !important;
        border-top: 1px solid #eef0f4 !important;
        border-bottom: 1px solid #eef0f4 !important;
        white-space: nowrap;
    }
    table.table-modern-c tbody td {
        padding: 1rem !important;
        font-size: .85rem;
        border-bottom: 1px solid #f1f3f7 !important;
        background: #fff;
        vertical-align: middle;
        transition: background .18s ease;
    }
    table.table-modern-c tbody tr:hover td { background: #fafbff; }
    table.table-modern-c tbody tr:last-child td { border-bottom: 0 !important; }

    /* Row number badge */
    .row-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px; height: 28px;
        border-radius: 8px;
        background: #f1f5f9;
        color: #64748b;
        font-weight: 700;
        font-size: .78rem;
    }

    /* Doc cells */
    .doc-no {
        font-weight: 700;
        color: #1e293b;
        font-size: .87rem;
    }
    .doc-desc {
        font-size: .76rem;
        color: #64748b;
        margin-top: .15rem;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
    }

    /* Vendor cell */
    .vendor-cell {
        display: flex; align-items: center; gap: .65rem;
    }
    .vendor-avatar {
        width: 36px; height: 36px;
        border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #818cf8, #6366f1);
        color: #fff;
        font-weight: 700;
        font-size: .82rem;
        flex-shrink: 0;
    }
    .vendor-avatar.va-2 { background: linear-gradient(135deg, #fda4af, #f43f5e); }
    .vendor-avatar.va-3 { background: linear-gradient(135deg, #6ee7b7, #10b981); }
    .vendor-avatar.va-4 { background: linear-gradient(135deg, #fcd34d, #f59e0b); }
    .vendor-avatar.va-5 { background: linear-gradient(135deg, #93c5fd, #3b82f6); }
    .vendor-name {
        font-weight: 600;
        color: #1e293b;
        font-size: .85rem;
    }

    /* Money + timeline */
    .money-pos {
        color: #047857;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .timeline-info {
        font-size: .73rem;
        color: #64748b;
        margin-top: .15rem;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
    }

    /* Status pill */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .7rem;
        font-weight: 700;
        padding: .3rem .75rem;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: .05em;
        white-space: nowrap;
    }
    .status-pill::before {
        content: '';
        width: 6px; height: 6px;
        border-radius: 50%;
        background: currentColor;
        box-shadow: 0 0 0 3px currentColor;
        opacity: .25;
    }
    .status-aktif       { background: rgba(16,185,129,.12); color: #047857; }
    .status-selesai     { background: rgba(99,102,241,.12); color: #4338ca; }
    .status-draft       { background: rgba(100,116,139,.12); color: #475569; }
    .status-dibatalkan  { background: rgba(244,63,94,.10); color: #b91c1c; }
    .status-pending     { background: rgba(245,158,11,.12); color: #b45309; }
    .status-approved    { background: rgba(16,185,129,.12); color: #047857; }
    .status-rejected    { background: rgba(244,63,94,.10); color: #b91c1c; }
    .status-submitted   { background: rgba(59,130,246,.12); color: #1d4ed8; }

    /* Action buttons */
    .action-bar-cell {
        display: inline-flex;
        gap: .35rem;
        flex-wrap: wrap;
        justify-content: center;
    }
    .btn-act {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .72rem;
        font-weight: 600;
        padding: .35rem .7rem;
        border-radius: .55rem;
        border: 1px solid transparent;
        background: #f8fafc;
        color: #475569;
        text-decoration: none;
        transition: all .15s ease;
        cursor: pointer;
        white-space: nowrap;
    }
    .btn-act:hover { transform: translateY(-1px); }
    .btn-act-detail  { background: rgba(59,130,246,.10); color: #1d4ed8; border-color: rgba(59,130,246,.18); }
    .btn-act-detail:hover { background: #1d4ed8; color: #fff; }
    .btn-act-addm    { background: rgba(245,158,11,.10); color: #b45309; border-color: rgba(245,158,11,.18); }
    .btn-act-addm:hover { background: #d97706; color: #fff; }
    .btn-act-tagih   { background: linear-gradient(135deg, #34d399, #10b981); color: #fff; box-shadow: 0 4px 10px rgba(16,185,129,.30); }
    .btn-act-tagih:hover { color: #fff; box-shadow: 0 8px 18px rgba(16,185,129,.40); }
    .btn-act-tagih:disabled { background: #e2e8f0; color: #94a3b8; box-shadow: none; cursor: not-allowed; }
    .btn-act-delete  { background: rgba(244,63,94,.10); color: #be123c; border-color: rgba(244,63,94,.18); padding: .35rem .55rem; }
    .btn-act-delete:hover { background: #f43f5e; color: #fff; }

    /* Pagination + footer */
    .dt-modern .dataTables_wrapper .row:last-child {
        padding: .65rem 1.25rem .9rem;
        border-top: 1px solid #f1f3f7;
        background: #fafbff;
        margin: 0 !important;
        align-items: center;
    }
    .dt-modern .dataTables_paginate { padding: 0 !important; margin: 0 !important; display: flex; justify-content: flex-end; }
    .dt-modern .dataTables_info { padding: 0 !important; color: #64748b; font-size: .78rem; font-weight: 500; }
    .dt-modern .dataTables_paginate .pagination {
        display: inline-flex;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        padding: 3px;
        margin: 0 !important;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
        gap: 2px;
    }
    .dt-modern .dataTables_paginate .paginate_button {
        min-width: 28px; height: 28px;
        padding: 0 .55rem !important;
        margin: 0 !important;
        border-radius: 999px !important;
        border: 0 !important;
        background: transparent !important;
        color: #64748b !important;
        font-size: .76rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        transition: all .15s ease;
    }
    .dt-modern .dataTables_paginate .paginate_button:hover {
        background: #f1f5f9 !important;
        color: #1e293b !important;
        border: 0 !important;
    }
    .dt-modern .dataTables_paginate .paginate_button.current,
    .dt-modern .dataTables_paginate .paginate_button.current:hover {
        background: linear-gradient(135deg, #6366f1, #4f46e5) !important;
        color: #fff !important;
        border: 0 !important;
        box-shadow: 0 3px 8px rgba(99,102,241,.30);
    }
    .dt-modern .dataTables_paginate .paginate_button.disabled,
    .dt-modern .dataTables_paginate .paginate_button.disabled:hover {
        background: transparent !important;
        color: #cbd5e1 !important;
        cursor: not-allowed;
        box-shadow: none !important;
    }

    /* Tab content fade */
    .tab-pane-c { display: none; animation: fadeUp .35s cubic-bezier(.22,1,.36,1) both; }
    .tab-pane-c.active { display: block; }

    /* Modal upgrade */
    .modal-tagih .modal-content {
        border: 0;
        border-radius: 1.25rem;
        overflow: hidden;
    }
    .modal-tagih .modal-header {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff;
        border: 0;
        padding: 1.25rem 1.5rem;
    }
    .modal-tagih .modal-header .btn-close { filter: invert(1); }
    .termin-row {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: .85rem;
        padding: 1rem 1.15rem;
        margin-bottom: .65rem;
        display: flex;
        gap: 1rem;
        align-items: center;
        transition: all .2s ease;
    }
    .termin-row:hover {
        border-color: #10b981;
        box-shadow: 0 8px 18px rgba(16,185,129,.10);
        transform: translateY(-1px);
    }
    .termin-row .tr-icon {
        width: 44px; height: 44px;
        border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, rgba(16,185,129,.15), rgba(5,150,105,.05));
        color: #047857;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .termin-row .tr-money {
        font-weight: 800;
        color: #047857;
        font-size: 1rem;
        font-variant-numeric: tabular-nums;
    }
    .btn-tagih-modal {
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: #fff;
        border: 0;
        font-weight: 700;
        padding: .55rem 1.1rem;
        border-radius: .65rem;
        font-size: .82rem;
        text-decoration: none;
        box-shadow: 0 4px 12px rgba(99,102,241,.30);
        transition: all .15s ease;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .btn-tagih-modal:hover {
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(99,102,241,.40);
    }

    /* Animations */
    @keyframes heroIn {
        from { opacity: 0; transform: translateY(-12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush


@section('content')

@php
    $kontrakAktif = $contracts->where('status_kontrak', 'AKTIF')->count();
    $kontrakSelesai = $contracts->where('status_kontrak', 'SELESAI')->count();
    $kontrakDraft = $contracts->where('status_kontrak', 'DRAFT')->count();
    $kontrakDibatalkan = $contracts->where('status_kontrak', 'DIBATALKAN')->count();
@endphp

{{-- HERO --}}
<div class="kontrak-hero">
    <i class="bi bi-briefcase-fill briefcase-illust d-none d-md-block"></i>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2><i class="bi bi-folder2-open me-2"></i>Manajemen Kontrak</h2>
            <p>Pantau status pelaksanaan kontrak dan persetujuan addendum</p>
        </div>
        <a href="{{ route('contracts.create') }}" class="btn-hero-primary">
            <i class="bi bi-plus-circle-fill"></i> Tambah Kontrak
        </a>
    </div>
</div>

{{-- KPI MINI BAR --}}
<div class="kpi-bar">
    <div class="kpi-mini km-aktif">
        <div class="km-row">
            <div class="km-icon"><i class="bi bi-shield-check"></i></div>
            <div>
                <div class="km-label">Aktif</div>
                <div class="km-value">{{ $kontrakAktif }}</div>
            </div>
        </div>
    </div>
    <div class="kpi-mini km-pending">
        <div class="km-row">
            <div class="km-icon"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="km-label">Pending Review</div>
                <div class="km-value">{{ $contracts->where('status_kontrak', 'PENDING_REVIEW')->count() }}</div>
            </div>
        </div>
    </div>
    <div class="kpi-mini km-draft">
        <div class="km-row">
            <div class="km-icon"><i class="bi bi-pencil-square"></i></div>
            <div>
                <div class="km-label">Draft</div>
                <div class="km-value">{{ $kontrakDraft }}</div>
            </div>
        </div>
    </div>
    <div class="kpi-mini km-selesai">
        <div class="km-row">
            <div class="km-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="km-label">Selesai</div>
                <div class="km-value">{{ $kontrakSelesai }}</div>
            </div>
        </div>
    </div>
</div>

{{-- FLASH MESSAGE --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" style="background: rgba(16,185,129,.08); color:#047857; border-left: 4px solid #10b981 !important;">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- MAIN CARD --}}
<div class="main-card">
    {{-- Tabs --}}
    <div class="tabs-pill" id="contractTabs">
        <button class="tab-btn active" data-tab="kontrak">
            <i class="bi bi-file-earmark-text-fill"></i> Daftar Kontrak Utama
            <span class="tab-count">{{ $contracts->count() }}</span>
        </button>
        <button class="tab-btn" data-tab="addendum">
            <i class="bi bi-journal-plus"></i> Riwayat Addendum
            <span class="tab-count">{{ $addendums->count() }}</span>
        </button>
    </div>

    {{-- TAB: KONTRAK UTAMA --}}
    <div class="tab-pane-c active dt-modern" data-pane="kontrak">
        <div class="table-responsive">
            <table id="tableKontrak" class="table table-modern-c align-middle w-100">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>Nomor SPK & Pekerjaan</th>
                        <th>Vendor</th>
                        <th>Nilai & Timeline</th>
                        <th>Status</th>
                        <th class="text-center" style="min-width:200px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contracts as $kontrak)
                        @php
                            $av = ($loop->iteration % 5) ?: 5;
                            $statusCls = match($kontrak->status_kontrak) {
                                'AKTIF' => 'status-aktif',
                                'SELESAI' => 'status-selesai',
                                'DRAFT' => 'status-draft',
                                'DIBATALKAN' => 'status-dibatalkan',
                                'PENDING_REVIEW' => 'status-pending',
                                default => 'status-draft',
                            };
                            $endDate = \Carbon\Carbon::parse($kontrak->tanggal_selesai);
                            $isLate = $endDate->isPast() && $kontrak->status_kontrak === 'AKTIF';
                            $readyTerms = $kontrak->termin->where('status_termin', 'READY_TO_BILL')->values();
                        @endphp
                        <tr>
                            <td><span class="row-num">{{ $loop->iteration }}</span></td>
                            <td>
                                <div class="doc-no">{{ $kontrak->nomor_spk }}</div>
                                <div class="doc-desc">
                                    <i class="bi bi-briefcase"></i>
                                    {{ Str::limit($kontrak->nama_pekerjaan, 50) }}
                                </div>
                            </td>
                            <td>
                                <div class="vendor-cell">
                                    <span class="vendor-avatar va-{{ $av }}">
                                        {{ \Illuminate\Support\Str::upper(mb_substr($kontrak->vendor->nama_perusahaan ?? '?', 0, 1)) }}
                                    </span>
                                    <span class="vendor-name">{{ Str::limit($kontrak->vendor->nama_perusahaan ?? 'N/A', 26) }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="money-pos">Rp {{ number_format($kontrak->nilai_total_kontrak, 0, ',', '.') }}</span>
                                <div class="timeline-info">
                                    <i class="bi bi-calendar-event {{ $isLate ? 'text-danger' : '' }}"></i>
                                    {{ $isLate ? 'Terlambat' : 'Selesai' }}: {{ $endDate->isoFormat('D MMM YYYY') }}
                                </div>
                            </td>
                            <td>
                                <span class="status-pill {{ $statusCls }}">
                                    {{ str_replace('_', ' ', $kontrak->status_kontrak) }}
                                </span>
                            </td>
                            <td>
                                <div class="action-bar-cell">
                                    <a href="{{ route('contracts.show', $kontrak->id) }}" class="btn-act btn-act-detail" title="Detail">
                                        <i class="bi bi-search"></i> Detail
                                    </a>
                                    <a href="{{ route('addendums.index', $kontrak->id) }}" class="btn-act btn-act-addm" title="Kelola Addendum">
                                        <i class="bi bi-journal-text"></i> Addm. <span>{{ $kontrak->addendums->count() }}</span>
                                    </a>
                                    @if(Auth::user()->hasAnyRole(['Super Admin', 'Pejabat Pengadaan']) && in_array($kontrak->status_kontrak, ['DRAFT', 'REVISI'], true))
                                        <form action="{{ route('contracts.submit', $kontrak->id) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('Ajukan kontrak ini ke PPK?')">
                                            @csrf
                                            <button type="submit" class="btn-act btn-act-tagih" title="Ajukan ke PPK">
                                                <i class="bi bi-send"></i> Ajukan
                                            </button>
                                        </form>
                                    @endif
                                    @if(Auth::user()->hasRole('Pejabat Pengadaan') && $kontrak->status_kontrak === 'DRAFT')
                                        <form action="{{ route('contracts.destroy', $kontrak->id) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('Yakin hapus draf kontrak ini? Arsip terkait akan ikut terhapus permanen.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-act btn-act-delete" title="Hapus Draf">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if($kontrak->status_kontrak == 'AKTIF')
                                        <button type="button" class="btn-act btn-act-tagih" title="Buat Tagihan"
                                                data-bs-toggle="modal" data-bs-target="#modalTagihKontrak{{ $kontrak->id }}"
                                                {{ $readyTerms->isEmpty() ? 'disabled' : '' }}>
                                            <i class="bi bi-cash-stack"></i> Tagih
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- TAB: ADDENDUM --}}
    <div class="tab-pane-c dt-modern" data-pane="addendum">
        <div class="table-responsive">
            <table id="tableAddendum" class="table table-modern-c align-middle w-100">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>Ref. Kontrak</th>
                        <th>No. Addendum & Tanggal</th>
                        <th>Jenis Perubahan</th>
                        <th>Nilai/Waktu Baru</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($addendums as $addm)
                        @php
                            $statusWorkflow = $addm->status_workflow ?? ($addm->status_addendum ?? 'DRAFT');
                            $statusCls = match($statusWorkflow) {
                                'APPROVED' => 'status-approved',
                                'SUBMITTED' => 'status-submitted',
                                'REJECTED' => 'status-rejected',
                                default => 'status-draft',
                            };
                        @endphp
                        <tr>
                            <td><span class="row-num">{{ $loop->iteration }}</span></td>
                            <td>
                                <div class="doc-no">{{ $addm->kontrakUtama->nomor_spk ?? '-' }}</div>
                            </td>
                            <td>
                                <div class="doc-no" style="color:#4f46e5;">{{ $addm->nomor_addendum ?? 'ADD.XXX' }}</div>
                                <div class="doc-desc">
                                    <i class="bi bi-calendar3"></i>
                                    {{ $addm->tanggal_addendum ? \Carbon\Carbon::parse($addm->tanggal_addendum)->isoFormat('D MMM YYYY') : '-' }}
                                </div>
                            </td>
                            <td>
                                <span class="status-pill status-draft">
                                    {{ str_replace('_', ' ', $addm->jenis_addendum ?? 'Perubahan') }}
                                </span>
                            </td>
                            <td>
                                @if($addm->nilai_kontrak_baru)
                                    <span class="money-pos">Rp {{ number_format($addm->nilai_kontrak_baru, 0, ',', '.') }}</span>
                                @else
                                    <div class="timeline-info"><i class="bi bi-calendar-event"></i> Selesai:</div>
                                    <strong class="text-dark">{{ \Carbon\Carbon::parse($addm->tanggal_selesai_baru)->isoFormat('D MMM YYYY') }}</strong>
                                @endif
                            </td>
                            <td>
                                <span class="status-pill {{ $statusCls }}">
                                    {{ str_replace('_', ' ', $statusWorkflow) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('addendums.show', [$addm->kontrak_pengadaan_id, $addm->id]) }}" class="btn-act btn-act-detail">
                                    <i class="bi bi-search"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL TAGIH (per kontrak) --}}
@foreach($contracts as $kontrak)
    @php $readyTerms = $kontrak->termin->where('status_termin', 'READY_TO_BILL')->values(); @endphp
    <div class="modal fade modal-tagih" id="modalTagihKontrak{{ $kontrak->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-1"><i class="bi bi-cash-stack me-2"></i>Pilih Termin / Lumpsum untuk Ditagih</h5>
                        <div class="small opacity-90">{{ $kontrak->nomor_spk }} · {{ Str::limit($kontrak->nama_pekerjaan, 70) }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" style="background:#fafbff;">
                    @if($readyTerms->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size:2.5rem; color:#cbd5e1;"></i>
                            <p class="mb-0 mt-2">Belum ada termin atau lumpsum yang siap ditagih.</p>
                        </div>
                    @else
                        @foreach($readyTerms as $termin)
                            <div class="termin-row">
                                <div class="tr-icon"><i class="bi bi-receipt"></i></div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark">Termin {{ $termin->termin_ke }} · {{ str_replace('_', ' ', $termin->jenis_termin) }}</div>
                                    <div class="small text-muted">{{ $termin->keterangan_termin }}</div>
                                    <div class="d-flex gap-2 align-items-center mt-2">
                                        <span class="status-pill status-info" style="background: rgba(99,102,241,.10); color:#4338ca;">{{ $termin->persentase }}%</span>
                                        <span class="tr-money">Rp {{ number_format($termin->nilai_bruto_termin, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                                <a href="{{ route('tagihan.kontrak.create', ['kontrak_id' => $kontrak->id, 'termin_id' => $termin->id]) }}" class="btn-tagih-modal">
                                    <i class="bi bi-send-plus-fill"></i> Tagih
                                </a>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
@endforeach

@endsection


@push('script')
<script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script>
$(document).ready(function () {
    // ===== Custom tabs =====
    document.querySelectorAll('#contractTabs .tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const target = this.dataset.tab;
            document.querySelectorAll('#contractTabs .tab-btn').forEach(b => b.classList.toggle('active', b === this));
            document.querySelectorAll('.tab-pane-c').forEach(p => p.classList.toggle('active', p.dataset.pane === target));
            // adjust DataTables ketika tab di-show
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });
    });

    const dtConfig = {
        "pagingType": "simple_numbers",
        "dom": "<'row align-items-center'<'col-md-6'l><'col-md-6'f>>" +
               "<'row'<'col-12'tr>>" +
               "<'row'<'col-md-6 d-flex align-items-center'i><'col-md-6 d-flex justify-content-md-end'p>>",
        "language": {
            "search": "",
            "searchPlaceholder": "🔍  Cari nomor SPK, vendor, status...",
            "lengthMenu": "Tampilkan _MENU_",
            "info": "Menampilkan <strong>_START_–_END_</strong> dari <strong>_TOTAL_</strong> data",
            "infoEmpty": "Tidak ada data",
            "emptyTable": "Tidak ada data",
            "zeroRecords": "Tidak ditemukan data yang cocok",
            "paginate": {
                "first": "«",
                "last": "»",
                "next": "<i class='bi bi-chevron-right'></i>",
                "previous": "<i class='bi bi-chevron-left'></i>"
            }
        }
    };

    $('#tableKontrak').DataTable(dtConfig);
    $('#tableAddendum').DataTable(dtConfig);
});
</script>
@endpush
