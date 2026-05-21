@extends('layouts.app')
@section('title')
    Daftar Honorarium
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        /* ===== Stat cards (clean & minimal) ===== */
        .stat-card {
            position: relative;
            overflow: hidden;
            border-radius: 1rem;
            border: 1px solid #eef0f4;
            background: #fff;
            min-height: 110px;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
            box-shadow: 0 2px 6px rgba(15,23,42,.04);
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 28px rgba(15,23,42,.08);
            border-color: #e2e8f0;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            inset: 0 0 auto 0;
            height: 3px;
            background: var(--stat-accent, #6366f1);
            opacity: .85;
        }
        .stat-card .card-body {
            padding: 1.1rem 1.2rem;
            position: relative;
            z-index: 1;
        }

        /* Icon bubble — soft tinted */
        .stat-card .stat-icon {
            width: 48px; height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: var(--stat-icon-bg, rgba(99,102,241,.10));
            color: var(--stat-accent, #6366f1);
            font-size: 1.4rem;
            flex-shrink: 0;
            transition: transform .25s ease;
        }
        .stat-card:hover .stat-icon { transform: scale(1.05); }

        .stat-card .stat-label {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b !important;
            margin: 0 0 .15rem;
        }
        .stat-card .stat-value {
            font-size: 1.85rem;
            font-weight: 800;
            color: #0f172a !important;
            line-height: 1.1;
            margin: 0;
        }
        .stat-card .stat-trend {
            display: inline-flex; align-items: center; gap: .3rem;
            font-size: .72rem;
            font-weight: 600;
            color: var(--stat-accent, #6366f1);
            background: var(--stat-icon-bg, rgba(99,102,241,.10));
            padding: .15rem .55rem;
            border-radius: 999px;
            margin-top: .55rem;
        }

        /* Color variants — subtle accents only */
        .stat-primary { --stat-accent: #6366f1; --stat-icon-bg: rgba(99,102,241,.10); }
        .stat-slate   { --stat-accent: #475569; --stat-icon-bg: rgba(71,85,105,.10); }
        .stat-warning { --stat-accent: #d97706; --stat-icon-bg: rgba(217,119,6,.10); }
        .stat-success { --stat-accent: #059669; --stat-icon-bg: rgba(5,150,105,.10); }
        .stat-danger  { --stat-accent: #dc2626; --stat-icon-bg: rgba(220,38,38,.10); }

        /* ===== Section header (above table) ===== */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .section-title {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            margin: 0;
        }
        .section-title::before {
            content: '';
            width: 4px; height: 24px;
            border-radius: 4px;
            background: linear-gradient(180deg, #6366f1, #2563eb);
        }
        .section-title h6 {
            margin: 0;
            font-size: .82rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #1e293b;
        }
        .section-title small {
            display: block;
            font-size: .72rem;
            color: #94a3b8;
            font-weight: 500;
            text-transform: none;
            letter-spacing: 0;
            margin-top: .15rem;
        }
        .btn-add-honor {
            background: linear-gradient(135deg, #10b981, #059669);
            border: 0;
            color: #fff;
            font-weight: 600;
            padding: .55rem 1.15rem;
            border-radius: .65rem;
            box-shadow: 0 6px 14px rgba(16,185,129,.30);
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .btn-add-honor:hover {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(16,185,129,.40);
        }

        /* ===== Modern table card ===== */
        .table-card {
            background: #fff;
            border: 1px solid #eef0f4;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(15,23,42,.04);
        }
        .table-card .card-body { padding: 0; }

        /* DataTables filter & length wrapper polish */
        .table-card .dataTables_wrapper {
            padding: 1.1rem 1.25rem .75rem;
        }
        .table-card .dataTables_wrapper .dataTables_length,
        .table-card .dataTables_wrapper .dataTables_filter {
            font-size: .82rem;
            color: #475569;
            margin-bottom: .85rem;
        }
        .table-card .dataTables_wrapper .dataTables_length select,
        .table-card .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #e2e8f0;
            border-radius: .55rem;
            padding: .35rem .7rem;
            font-size: .82rem;
            color: #0f172a;
            background: #f8fafc;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .table-card .dataTables_wrapper .dataTables_filter input {
            min-width: 280px;
            padding: .4rem .85rem;
        }
        .table-card .dataTables_wrapper .dataTables_filter label {
            margin-bottom: 0;
        }
        .table-card .dataTables_wrapper .dataTables_filter input::placeholder {
            color: #94a3b8;
            font-size: .82rem;
        }
        .table-card .dataTables_wrapper .dataTables_length select:focus,
        .table-card .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,.12);
            background: #fff;
            outline: 0;
        }

        /* Modern table */
        .table-modern {
            margin: 0 !important;
            border-collapse: separate;
            border-spacing: 0;
            width: 100% !important;
        }
        .table-modern thead th {
            background: #f8fafc;
            border-top: 1px solid #eef0f4;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            padding: .85rem 1rem;
            white-space: nowrap;
        }
        .table-modern thead th:first-child { padding-left: 1.25rem; }
        .table-modern thead th:last-child  { padding-right: 1.25rem; }
        .table-modern tbody td {
            padding: 1rem;
            border-top: 0;
            border-bottom: 1px solid #f1f5f9;
            background: #fff;
            vertical-align: middle;
            font-size: .87rem;
            color: #0f172a;
        }
        .table-modern tbody td:first-child { padding-left: 1.25rem; }
        .table-modern tbody td:last-child  { padding-right: 1.25rem; }
        .table-modern tbody tr {
            transition: background-color .15s ease;
        }
        .table-modern tbody tr:hover td {
            background: #fafbff;
        }
        .table-modern tbody tr:last-child td { border-bottom: 0; }

        /* Doc cell */
        .doc-no {
            font-weight: 700;
            color: #1e293b;
            font-size: .88rem;
            text-decoration: none;
        }
        .doc-no:hover { color: #4f46e5; }
        .doc-desc {
            font-size: .76rem;
            color: #64748b;
            margin-top: .15rem;
            line-height: 1.4;
        }
        .doc-rownum {
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

        /* Personnel chips with avatar */
        .personel-stack {
            display: inline-flex;
            align-items: center;
        }
        .personel-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            background: #f1f5f9;
            border-radius: 999px;
            padding: .15rem .65rem .15rem .15rem;
            font-size: .72rem;
            color: #334155;
            font-weight: 600;
            margin-right: .25rem;
            margin-bottom: .25rem;
        }
        .personel-chip .avatar {
            width: 22px; height: 22px;
            border-radius: 50%;
            background: linear-gradient(135deg, #a5b4fc, #6366f1);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .65rem;
            font-weight: 800;
        }
        .personel-more {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            font-size: .72rem;
            color: #6366f1;
            background: rgba(99,102,241,.10);
            padding: .2rem .55rem;
            border-radius: 999px;
            font-weight: 700;
        }

        /* Money cells */
        .money {
            font-variant-numeric: tabular-nums;
            font-weight: 600;
            white-space: nowrap;
            color: #0f172a;
        }
        .money-pos { color: #047857; font-weight: 700; }
        .money-neg { color: #b91c1c; font-weight: 600; }

        /* Date cell */
        .date-cell {
            display: inline-flex;
            flex-direction: column;
            line-height: 1.2;
        }
        .date-cell .day {
            font-size: 1.05rem;
            font-weight: 800;
            color: #1e293b;
        }
        .date-cell .my {
            font-size: .7rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .05em;
            font-weight: 600;
        }

        /* Status chips */
        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .72rem;
            font-weight: 700;
            padding: .3rem .7rem;
            border-radius: 999px;
            white-space: nowrap;
            border: 1px solid transparent;
        }
        .status-chip i { font-size: .8rem; }
        .status-draft     { background: rgba(100,116,139,.10); color: #475569;  border-color: rgba(100,116,139,.18); }
        .status-pending   { background: rgba(217,119,6,.10);   color: #b45309;  border-color: rgba(217,119,6,.20); }
        .status-approved  { background: rgba(5,150,105,.10);   color: #047857;  border-color: rgba(5,150,105,.20); }
        .status-rejected  { background: rgba(220,38,38,.10);   color: #b91c1c;  border-color: rgba(220,38,38,.20); }
        .status-info      { background: rgba(59,130,246,.10);  color: #1d4ed8;  border-color: rgba(59,130,246,.20); }

        /* Action buttons */
        .btn-action {
            width: 34px; height: 34px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
            transition: all .15s ease;
            font-size: .9rem;
            text-decoration: none;
            padding: 0;
        }
        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(15,23,42,.08);
        }
        .btn-action.action-view:hover    { background: #eff6ff; border-color: #93c5fd; color: #1d4ed8; }
        .btn-action.action-edit:hover    { background: #fffbeb; border-color: #fcd34d; color: #b45309; }
        .btn-action.action-delete:hover  { background: #fef2f2; border-color: #fca5a5; color: #b91c1c; }
        .btn-action.action-print:hover   { background: #fef2f2; border-color: #fca5a5; color: #b91c1c; }

        /* Pagination polish */
        .table-card .dataTables_wrapper .row:last-child {
            padding: .65rem 1.25rem .9rem;
            border-top: 1px solid #f1f3f7;
            background: #fafbff;
            margin: 0 !important;
            align-items: center;
        }
        .table-card .dataTables_paginate {
            padding: 0 !important;
            font-size: .78rem;
            margin: 0 !important;
            display: flex;
            justify-content: flex-end;
        }
        .table-card .dataTables_info {
            padding: 0 !important;
            color: #64748b;
            font-size: .78rem;
            font-weight: 500;
            padding-top: 0 !important;
        }
        .table-card .dataTables_paginate .pagination {
            display: inline-flex;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 3px;
            margin: 0 !important;
            box-shadow: 0 1px 2px rgba(15,23,42,.04);
            gap: 2px;
        }
        .table-card .dataTables_paginate .paginate_button {
            min-width: 28px;
            height: 28px;
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
        .table-card .dataTables_paginate .paginate_button:hover {
            background: #f1f5f9 !important;
            color: #1e293b !important;
            border: 0 !important;
        }
        .table-card .dataTables_paginate .paginate_button.current,
        .table-card .dataTables_paginate .paginate_button.current:hover {
            background: linear-gradient(135deg, #6366f1, #4f46e5) !important;
            color: #fff !important;
            border: 0 !important;
            box-shadow: 0 3px 8px rgba(99,102,241,.30);
        }
        .table-card .dataTables_paginate .paginate_button.disabled,
        .table-card .dataTables_paginate .paginate_button.disabled:hover {
            background: transparent !important;
            color: #cbd5e1 !important;
            cursor: not-allowed;
            box-shadow: none !important;
        }
        .table-card .dataTables_paginate .paginate_button.previous,
        .table-card .dataTables_paginate .paginate_button.next {
            font-size: .9rem;
            min-width: 28px;
            padding: 0 !important;
        }
        .table-card .dataTables_paginate .ellipsis {
            color: #94a3b8;
            padding: 0 .25rem;
            display: inline-flex;
            align-items: center;
        }

        /* Revisi note */
        .revisi-note {
            display: flex;
            align-items: flex-start;
            gap: .4rem;
            margin-top: .4rem;
            padding: .5rem .65rem;
            background: rgba(217,119,6,.08);
            border-left: 3px solid #f59e0b;
            border-radius: .4rem;
            color: #92400e;
            font-size: .72rem;
            line-height: 1.4;
        }

        /* Empty state */
        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: #94a3b8;
        }
        .empty-state .empty-icon {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #64748b;
            margin-bottom: 1rem;
        }
        .empty-state h6 {
            color: #1e293b;
            margin-bottom: .35rem;
        }
    </style>
@endpush
@section('content')
    @php
        $editableStatuses = ['DRAFT', 'DITOLAK_PPK'];
        $pendingStatuses = ['PENDING_PPK'];
        $approvedStatuses = ['DISETUJUI_PPK', 'PROSES_SPP', 'SPP_TERBIT'];
        $rejectedStatuses = ['DITOLAK_PPK'];
    @endphp
    <x-page-title title="Manajemen Honor" subtitle="Daftar Honorarium" />

    {{-- Summary Cards --}}
    @php
        $totalPengajuan = $tagihans->count();
        $countDraft = $tagihans->where('status', 'DRAFT')->count();
        $countPending = $tagihans->whereIn('status', $pendingStatuses)->count();
        $countApproved = $tagihans->whereIn('status', $approvedStatuses)->count();
        $countRejected = $tagihans->whereIn('status', $rejectedStatuses)->count();
    @endphp
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-3 mb-4">
        {{-- Total Pengajuan --}}
        <div class="col">
            <div class="card stat-card stat-primary h-100">                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-stack"></i></div>
                    <div>
                        <p class="stat-label">Total Pengajuan</p>
                        <h3 class="stat-value">{{ $totalPengajuan }}</h3>
                        <span class="stat-trend"><i class="bi bi-collection"></i> Semua status</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Draft --}}
        <div class="col">
            <div class="card stat-card stat-slate h-100">                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-pencil-square"></i></div>
                    <div>
                        <p class="stat-label">Draft</p>
                        <h3 class="stat-value">{{ $countDraft }}</h3>
                        <span class="stat-trend"><i class="bi bi-clipboard"></i> Belum diajukan</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Menunggu Verifikasi --}}
        <div class="col">
            <div class="card stat-card stat-warning h-100">                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <p class="stat-label">Menunggu Verifikasi</p>
                        <h3 class="stat-value">{{ $countPending }}</h3>
                        <span class="stat-trend"><i class="bi bi-clock-history"></i> Dalam antrian</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Disetujui --}}
        <div class="col">
            <div class="card stat-card stat-success h-100">                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                    <div>
                        <p class="stat-label">Disetujui</p>
                        <h3 class="stat-value">{{ $countApproved }}</h3>
                        <span class="stat-trend"><i class="bi bi-shield-check"></i> Siap proses</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Revisi / Ditolak --}}
        <div class="col">
            <div class="card stat-card stat-danger h-100">                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-arrow-counterclockwise"></i></div>
                    <div>
                        <p class="stat-label">Revisi / Ditolak</p>
                        <h3 class="stat-value">{{ $countRejected }}</h3>
                        <span class="stat-trend"><i class="bi bi-exclamation-triangle"></i> Perlu tindakan</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Header & Actions --}}
    <div class="section-header">
        <div class="section-title">
            <div>
                <h6>Daftar Tagihan Honorarium</h6>
                <small>Kelola seluruh pengajuan honorarium dalam satu tempat</small>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('honorarium.create') }}" class="btn btn-add-honor">
                <i class="bi bi-plus-lg me-1"></i> Tambah Honorarium
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show py-2">
            <div class="text-white"><i class="bi bi-check-circle me-1"></i> {{ session('success') }}</div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show py-2">
            <div class="text-white"><i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}</div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Table --}}
    <div class="table-card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="example" class="table table-modern align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width:54px;">#</th>
                            <th>No. Tagihan & Uraian</th>
                            <th style="width:90px;">Tanggal</th>
                            <th>Penerima</th>
                            <th class="text-end">Total Bruto</th>
                            <th class="text-end">PPh</th>
                            <th class="text-end">Total Netto</th>
                            <th>Status</th>
                            <th class="text-center" style="width:170px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tagihans as $item)
                            @php
                                $locked = !in_array($item->status, $editableStatuses);
                                $penerima = $item->detailHonorarium->take(3);
                                $sisaPenerima = $item->detailHonorarium->count() - 3;
                                $createdDay = $item->created_at?->format('d') ?? '-';
                                $createdMy  = $item->created_at?->format('M Y') ?? '-';
                            @endphp
                            <tr>
                                <td><span class="doc-rownum">{{ $loop->iteration }}</span></td>
                                <td>
                                    <a href="{{ route('honorarium.show', $item->id) }}" class="doc-no">{{ $item->nomor_tagihan }}</a>
                                    <div class="doc-desc">{{ Str::limit($item->deskripsi, 60) }}</div>
                                </td>
                                <td>
                                    <span class="date-cell">
                                        <span class="day">{{ $createdDay }}</span>
                                        <span class="my">{{ $createdMy }}</span>
                                    </span>
                                </td>
                                <td>
                                    @if($item->detailHonorarium->isEmpty())
                                        <span class="text-muted small fst-italic">Belum ada penerima</span>
                                    @else
                                        <div class="personel-stack flex-wrap">
                                            @foreach($penerima as $detail)
                                                @php
                                                    $namaPersonel = $detail->nama_personel ?? '-';
                                                    $initial = strtoupper(mb_substr($namaPersonel, 0, 1));
                                                @endphp
                                                <span class="personel-chip" title="{{ $namaPersonel }}">
                                                    <span class="avatar">{{ $initial }}</span>
                                                    {{ Str::limit($namaPersonel, 14) }}
                                                </span>
                                            @endforeach
                                            @if($sisaPenerima > 0)
                                                <span class="personel-more">
                                                    <i class="bi bi-people-fill"></i> +{{ $sisaPenerima }} lainnya
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end money">Rp {{ number_format($item->total_bruto, 0, ',', '.') }}</td>
                                <td class="text-end money money-neg">Rp {{ number_format($item->total_potongan, 0, ',', '.') }}</td>
                                <td class="text-end money money-pos">Rp {{ number_format($item->total_netto, 0, ',', '.') }}</td>
                                <td>
                                    @switch($item->status)
                                        @case('DRAFT')
                                            <span class="status-chip status-draft"><i class="bi bi-pencil-square"></i>Draft</span>
                                            @break
                                        @case('PENDING_PPK')
                                            <span class="status-chip status-pending"><i class="bi bi-hourglass-split"></i>Menunggu PPK</span>
                                            @break
                                        @case('DISETUJUI_PPK')
                                            <span class="status-chip status-approved"><i class="bi bi-check-circle"></i>Disetujui PPK</span>
                                            @break
                                        @case('DITOLAK_PPK')
                                            <span class="status-chip status-rejected"><i class="bi bi-arrow-counterclockwise"></i>Dikembalikan</span>
                                            @php $lastLog = $item->logs->first(); @endphp
                                            @if($lastLog && $lastLog->catatan)
                                                <div class="revisi-note">
                                                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                                                    <span>{{ Str::limit($lastLog->catatan, 80) }}</span>
                                                </div>
                                            @endif
                                            @break
                                        @case('PROSES_SPP')
                                        @case('SPP_TERBIT')
                                            <span class="status-chip status-info"><i class="bi bi-file-earmark-check"></i>{{ $item->status }}</span>
                                            @break
                                        @default
                                            @php
                                                $st = (string) $item->status;
                                                if (str_starts_with($st, 'PENDING_')) {
                                                    $cls = 'status-pending'; $ic = 'bi-hourglass-split';
                                                } elseif (str_starts_with($st, 'DITOLAK_') || str_starts_with($st, 'REVISI_')) {
                                                    $cls = 'status-rejected'; $ic = 'bi-arrow-counterclockwise';
                                                } elseif (in_array($st, ['DISETUJUI', 'SELESAI', 'SPP_LENGKAP', 'SEBAGIAN_SPP_TERBIT'])) {
                                                    $cls = 'status-approved'; $ic = 'bi-check-circle';
                                                } else {
                                                    $cls = 'status-draft'; $ic = 'bi-circle';
                                                }
                                            @endphp
                                            <span class="status-chip {{ $cls }}"><i class="bi {{ $ic }}"></i>{{ Str::title(strtolower(str_replace('_', ' ', $st))) }}</span>
                                    @endswitch
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <a href="{{ route('honorarium.show', $item->id) }}" class="btn-action action-view" title="Lihat Detail">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                        @if(!$locked)
                                            <a href="{{ route('honorarium.edit', $item->id) }}" class="btn-action action-edit" title="Edit">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <button type="button" class="btn-action action-delete" title="Hapus"
                                                onclick="deleteHonorarium('{{ route('honorarium.destroy', $item->id) }}')">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        @endif
                                        @if(in_array($item->status, ['DISETUJUI_PPK', 'PROSES_SPP', 'SPP_TERBIT']))
                                            <a href="{{ route('honorarium.pdf-nominatif', $item->id) }}" target="_blank" class="btn-action action-print" title="Cetak Nominatif">
                                                <i class="bi bi-file-earmark-pdf"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                                        <h6 class="fw-bold">Belum ada pengajuan honorarium</h6>
                                        <p class="small mb-3">Mulai buat pengajuan honorarium pertama Anda.</p>
                                        <a href="{{ route('honorarium.create') }}" class="btn btn-add-honor btn-sm">
                                            <i class="bi bi-plus-lg me-1"></i> Tambah Honorarium
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Global Delete Form --}}
    <form id="deleteForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('#example').DataTable({
                "columnDefs": [{ "orderable": false, "targets": [8] }],
                "pagingType": "simple_numbers",
                "dom": "<'row align-items-center'<'col-md-6'l><'col-md-6'f>>" +
                       "<'row'<'col-12'tr>>" +
                       "<'row'<'col-md-6 d-flex align-items-center'i><'col-md-6 d-flex justify-content-md-end'p>>",
                "language": {
                    "search": "",
                    "searchPlaceholder": "🔍  Cari tagihan, penerima, status...",
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
            });
        });

        function deleteHonorarium(url) {
            if (confirm('Hapus data honorarium ini? Tindakan ini tidak dapat dibatalkan.')) {
                document.getElementById('deleteForm').action = url;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
@endpush