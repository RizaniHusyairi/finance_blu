@extends('layouts.app')
@section('title')
    Manajemen Perjaldin
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap');

        :root {
            --primary-gradient: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            --accent-gradient: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            --card-shadow: 0 16px 40px rgba(15, 23, 42, 0.03), 0 1px 3px rgba(15, 23, 42, 0.01);
            --border-glass: rgba(226, 232, 240, 0.8);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
        }

        /* Entry Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .delay-1 { animation-delay: 0.05s; }
        .delay-2 { animation-delay: 0.1s; }
        .delay-3 { animation-delay: 0.15s; }
        .delay-4 { animation-delay: 0.2s; }
        .delay-5 { animation-delay: 0.25s; }

        /* Stats Grid Dashboard */
        .stat-card {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 1.25rem;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.01);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            filter: blur(40px);
            opacity: 0.12;
            z-index: 1;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 35px rgba(15, 23, 42, 0.05);
            border-color: rgba(59, 130, 246, 0.2);
        }

        .stat-card:hover::after {
            transform: scale(1.2);
            opacity: 0.22;
        }

        .stat-blue::after { background: #2563eb; }
        .stat-blue .stat-icon-wrapper { background: rgba(37, 99, 235, 0.08); color: #2563eb; }
        .stat-blue:hover { border-color: rgba(37, 99, 235, 0.25); }

        .stat-teal::after { background: #0d9488; }
        .stat-teal .stat-icon-wrapper { background: rgba(13, 148, 136, 0.08); color: #0d9488; }
        .stat-teal:hover { border-color: rgba(13, 148, 136, 0.25); }

        .stat-amber::after { background: #d97706; }
        .stat-amber .stat-icon-wrapper { background: rgba(245, 158, 11, 0.08); color: #d97706; }
        .stat-amber:hover { border-color: rgba(245, 158, 11, 0.25); }

        .stat-emerald::after { background: #10b981; }
        .stat-emerald .stat-icon-wrapper { background: rgba(16, 185, 129, 0.08); color: #10b981; }
        .stat-emerald:hover { border-color: rgba(16, 185, 129, 0.25); }

        .stat-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-icon-wrapper {
            transform: scale(1.1) rotate(5deg);
        }

        .stat-info {
            display: flex;
            flex-direction: column;
            z-index: 2;
            width: 100%;
        }

        .stat-label {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 0.15rem 0;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .stat-desc {
            font-size: 0.75rem;
            font-weight: 600;
        }

        .text-teal { color: #0d9488 !important; }
        .text-blue { color: #2563eb !important; }
        .text-amber { color: #d97706 !important; }
        .text-emerald { color: #10b981 !important; }

        /* Page Title Header */
        .page-header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .page-title-wrapper {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .page-title-badge {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.02em;
            display: inline-flex;
            align-items: center;
            gap: .75rem;
        }

        .page-title-badge::before {
            content: '';
            width: 4px;
            height: 24px;
            border-radius: 4px;
            background: linear-gradient(180deg, #2563eb, #0d9488);
        }

        .page-subtitle {
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 600;
            margin-left: 1.05rem;
        }

        /* Button premium style */
        .btn-premium {
            background: var(--primary-gradient);
            border: none;
            color: #fff !important;
            font-weight: 700;
            font-size: 0.88rem;
            padding: .65rem 1.4rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.15);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
            color: #fff !important;
        }

        /* Card and Table Style */
        .page-card {
            background: #fff;
            border: 1px solid var(--border-glass);
            border-radius: 1.5rem;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            padding: 1.75rem;
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
            animation-delay: 0.25s;
        }

        .table-modern {
            width: 100% !important;
            margin: 1rem 0 !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid #f1f5f9;
        }

        .table-modern thead th {
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #475569;
            background: #f8fafc !important;
            border-top: none !important;
            border-bottom: 2px solid #e2e8f0 !important;
            padding: 1.1rem 1.5rem !important;
            vertical-align: middle;
        }

        .table-modern tbody td {
            padding: 1.1rem 1.5rem !important;
            vertical-align: middle !important;
            border-color: #f1f5f9 !important;
            font-size: .88rem;
            color: #334155;
            background-color: #fff !important;
            transition: all 0.2s ease;
        }

        .table-modern tbody tr {
            transition: all 0.2s ease;
        }

        .table-modern tbody tr:hover td {
            background-color: #f8fafc !important;
            color: #0f172a;
        }

        /* Subtle left glowing border on row hover */
        .table-modern tbody tr td:first-child {
            position: relative;
        }
        
        .table-modern tbody tr td:first-child::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 4px;
            background: transparent;
            transition: all 0.25s ease;
        }
        
        .table-modern tbody tr:hover td:first-child::before {
            background: linear-gradient(180deg, #2563eb, #0d9488);
        }

        /* Bruto Capsule styled */
        .bruto-col {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            color: #0f172a;
            font-size: .88rem;
            background: rgba(15, 23, 42, 0.04) !important;
            padding: 0.45rem 0.9rem !important;
            border-radius: 0.6rem;
            display: inline-block;
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.02);
            border: 1px solid rgba(15, 23, 42, 0.06);
            transition: all 0.25s ease;
        }

        .table-modern tbody tr:hover .bruto-col {
            transform: scale(1.03);
            background: rgba(37, 99, 235, 0.05) !important;
            color: #2563eb;
            border-color: rgba(37, 99, 235, 0.1);
        }

        /* Status badges capsules */
        .status-badge-premium {
            font-size: 0.78rem;
            font-weight: 700;
            padding: 0.4rem 0.85rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border: 1px solid transparent;
            text-transform: capitalize;
            box-shadow: 0 2px 4px rgba(0,0,0,0.01);
            transition: all 0.2s ease;
        }

        .status-badge-premium i {
            font-size: 0.85rem;
        }

        .status-draft {
            background: rgba(100, 116, 139, 0.08);
            border-color: rgba(100, 116, 139, 0.15);
            color: #475569;
        }

        .status-pending {
            background: rgba(37, 99, 235, 0.08);
            border-color: rgba(37, 99, 235, 0.15);
            color: #1d4ed8;
            animation: pulseGlowBlue 2s infinite ease-in-out;
        }

        @keyframes pulseGlowBlue {
            0% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.15); }
            50% { box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.03); }
            100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.15); }
        }

        .status-revisi {
            background: rgba(239, 68, 68, 0.08);
            border-color: rgba(239, 68, 68, 0.15);
            color: #dc2626;
            animation: pulseGlowRed 2s infinite ease-in-out;
        }

        @keyframes pulseGlowRed {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.15); }
            50% { box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.03); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.15); }
        }

        .status-disetujui {
            background: rgba(16, 185, 129, 0.08);
            border-color: rgba(16, 185, 129, 0.15);
            color: #065f46;
        }

        .status-spp {
            background: rgba(139, 92, 246, 0.08);
            border-color: rgba(139, 92, 246, 0.15);
            color: #6d28d9;
        }

        .revisi-catatan-box {
            background: rgba(239, 68, 68, 0.04);
            border: 1px solid rgba(239, 68, 68, 0.08);
            color: #b91c1c;
            font-weight: 600;
            line-height: 1.4;
        }

        /* Participant modern tags */
        .participant-tag {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.06);
            border: 1px solid rgba(148, 163, 184, 0.15);
            color: #475569;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin: 0.15rem;
            transition: all 0.2s ease;
        }

        .table-modern tbody tr:hover .participant-tag {
            background: rgba(37, 99, 235, 0.04);
            border-color: rgba(37, 99, 235, 0.12);
            color: #2563eb;
        }

        /* Action Buttons with 3D scale transforms */
        .btn-action-view {
            border-radius: 50%;
            width: 34px;
            height: 34px;
            padding: 0;
            justify-content: center;
            font-size: 0.95rem;
            border: 1.5px solid rgba(13, 148, 136, 0.4);
            color: #0d9488 !important;
            background: transparent;
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-action-view:hover {
            background: #0d9488;
            color: #fff !important;
            border-color: #0d9488;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2);
            transform: translateY(-2px) scale(1.05);
        }

        .btn-action-edit-warning {
            border-radius: 50%;
            width: 34px;
            height: 34px;
            padding: 0;
            justify-content: center;
            font-size: 0.95rem;
            border: 1.5px solid rgba(245, 158, 11, 0.4);
            color: #d97706 !important;
            background: transparent;
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-action-edit-warning:hover {
            background: #f59e0b;
            color: #fff !important;
            border-color: #f59e0b;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
            transform: translateY(-2px) scale(1.05);
        }

        .btn-action-delete-danger {
            border-radius: 50%;
            width: 34px;
            height: 34px;
            padding: 0;
            justify-content: center;
            font-size: 0.95rem;
            border: 1.5px solid rgba(239, 68, 68, 0.4);
            color: #dc2626 !important;
            background: transparent;
            display: inline-flex;
            align-items: center;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-action-delete-danger:hover {
            background: #ef4444;
            color: #fff !important;
            border-color: #ef4444;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
            transform: translateY(-2px) scale(1.05);
        }

        /* Override DataTables elements */
        .dataTables_wrapper .dataTables_length {
            margin-bottom: 1.25rem;
        }

        .dataTables_wrapper .dataTables_length select {
            border-radius: 0.75rem;
            border: 1px solid #cbd5e1;
            padding: 0.4rem 2rem 0.4rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            outline: none;
            background-color: #fff;
            transition: all 0.2s ease;
        }

        .dataTables_wrapper .dataTables_length select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1.25rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            padding: 0.45rem 1.25rem 0.45rem 2.25rem;
            font-size: 0.85rem;
            font-weight: 500;
            outline: none;
            width: 220px;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' class='bi bi-search' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0'/%3E%3C/svg%3E") no-repeat 12px center;
            background-size: 14px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #3b82f6;
            width: 280px;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .dataTables_wrapper .dataTables_info {
            font-size: 0.82rem;
            color: #64748b;
            font-weight: 600;
            padding-top: 1.25rem;
        }

        .dataTables_wrapper .dataTables_paginate {
            padding-top: 1.25rem;
        }

        .dataTables_wrapper .dataTables_paginate .pagination {
            gap: 0.3rem;
            margin: 0;
        }

        .dataTables_wrapper .dataTables_paginate .page-item .page-link {
            border-radius: 0.6rem !important;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-size: 0.82rem;
            font-weight: 700;
            padding: 0.45rem 0.85rem;
            transition: all 0.2s ease;
        }

        .dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
            background: var(--primary-gradient) !important;
            border-color: transparent !important;
            color: #fff !important;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.15);
        }

        .dataTables_wrapper .dataTables_paginate .page-item:not(.active):hover .page-link {
            background: #f1f5f9;
            color: #0f172a;
            transform: translateY(-1px);
        }
    </style>
@endpush
@section('content')
    @php
        $revisiDraftStatuses = [
            'DRAFT',
            'REVISI_PPK',
            'REVISI_PPSPM',
            'REVISI_KOORDINATOR_KEUANGAN',
            'REVISI_BENDAHARA',
            'REVISI_BENDAHARA_PENERIMAAN',
            'REVISI_BENDAHARA_PENGELUARAN',
            'REVISI_KASUBBAG',
            'DITOLAK_PPK',
            'DITOLAK_PPSPM',
            'DITOLAK_KOORDINATOR_KEUANGAN',
            'DITOLAK_BENDAHARA_PENERIMAAN',
            'DITOLAK_BENDAHARA_PENGELUARAN',
            'DITOLAK_KASUBBAG',
        ];
    @endphp
    <x-page-title title="Manajemen" subtitle="Perjaldin" />

    @php
        $totalAll      = $tagihans->count();
        $countDraft    = $tagihans->whereIn('status', $revisiDraftStatuses)->count();
        $countMenunggu = $tagihans->whereIn('status', ['PENDING_VERIFIKASI_PERJALDIN', 'PENDING_PPK', 'PENDING_PPSPM', 'PENDING_KOORDINATOR_KEUANGAN', 'PENDING_BENDAHARA', 'PENDING_BENDAHARA_PENERIMAAN', 'PENDING_BENDAHARA_PENGELUARAN', 'PENDING_KASUBBAG', 'MENUNGGU_UPLOAD_NOMINATIF_TTD'])->count();
        $countSelesai  = $tagihans->whereIn('status', ['DISETUJUI_PERJALDIN', 'PROSES_COA', 'PROSES_SPP', 'SEBAGIAN_SPP_TERBIT', 'SPP_LENGKAP'])->count();

        $pct = fn ($n) => $totalAll > 0 ? round(($n / $totalAll) * 100) : 0;

        $cards = [
            [
                'label' => 'Total Tagihan',
                'value' => $totalAll,
                'sub'   => 'Seluruh pengajuan dinas',
                'icon'  => 'bi-collection',
                'theme' => 'stat-blue',
                'color' => 'blue',
                'pct'   => 100,
                'delay' => 'delay-1'
            ],
            [
                'label' => 'Draft & Revisi',
                'value' => $countDraft,
                'sub'   => 'Sedang disiapkan / dikembalikan',
                'icon'  => 'bi-pencil-square',
                'theme' => 'stat-teal',
                'color' => 'teal',
                'pct'   => $pct($countDraft),
                'delay' => 'delay-2'
            ],
            [
                'label' => 'Menunggu Verifikasi',
                'value' => $countMenunggu,
                'sub'   => 'Dalam antrean verifikator',
                'icon'  => 'bi-hourglass-split',
                'theme' => 'stat-amber',
                'color' => 'amber',
                'pct'   => $pct($countMenunggu),
                'delay' => 'delay-3'
            ],
            [
                'label' => 'Disetujui & Lanjut SPP',
                'value' => $countSelesai,
                'sub'   => 'Lolos verifikasi multi-pejabat',
                'icon'  => 'bi-check2-circle',
                'theme' => 'stat-emerald',
                'color' => 'emerald',
                'pct'   => $pct($countSelesai),
                'delay' => 'delay-4'
            ],
        ];
    @endphp

    {{-- Header Section --}}
    <div class="page-header-container">
        <div class="page-title-wrapper animate-fade-in-up delay-1">
            <h5 class="page-title-badge">Daftar Tagihan Perjaldin</h5>
            <span class="page-subtitle">Kelola dan lacak pengajuan pertanggungjawaban dinas</span>
        </div>
        <a href="{{ route('perjaldins.create') }}" class="btn-premium animate-fade-in-up delay-1">
            <i class="bi bi-plus-circle-fill"></i> Tambah Perjaldin
        </a>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm p-3 mb-4 d-flex align-items-center gap-3 animate-fade-in-up delay-1" role="alert">
            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink:0;">
                <i class="bi bi-check-lg"></i>
            </div>
            <div class="flex-fill">
                <strong class="text-success small d-block">Berhasil!</strong>
                <span class="small text-dark">{{ session('success') }}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm p-3 mb-4 d-flex align-items-center gap-3 animate-fade-in-up delay-1" role="alert">
            <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink:0;">
                <i class="bi bi-exclamation-lg"></i>
            </div>
            <div class="flex-fill">
                <strong class="text-danger small d-block">Kesalahan!</strong>
                <span class="small text-dark">{{ $errors->first() }}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Stats Grid Section --}}
    <div class="row g-4 mb-4">
        @foreach($cards as $c)
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card {{ $c['theme'] }} {{ $c['delay'] }}">
                    <div class="stat-icon-wrapper">
                        <i class="bi {{ $c['icon'] }}"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">{{ $c['label'] }}</span>
                        <h3 class="stat-value text-{{ $c['color'] }}">{{ number_format($c['value']) }}</h3>
                        <span class="stat-desc text-muted mb-2 d-block">{{ $c['sub'] }}</span>
                        <div class="progress" style="height: 4px; background: rgba(0, 0, 0, 0.05); border-radius: 99px;">
                            <div class="progress-bar bg-{{ $c['color'] }}" role="progressbar" style="width: {{ $c['pct'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Main Table Card --}}
    <div class="page-card delay-5">
        <div class="table-responsive">
            <table id="example" class="table table-modern align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>No. Tagihan & Uraian</th>
                        <th>Peserta Perjaldin</th>
                        <th>Total Bruto</th>
                        <th>Status</th>
                        <th width="180" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tagihans as $tagihan)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark mb-1" style="font-size: 0.92rem; letter-spacing: -0.01em;">
                                    {{ $tagihan->nomor_tagihan }}
                                </div>
                                <div class="text-muted small fw-semibold" style="max-width: 320px; line-height: 1.4;">
                                    {{ $tagihan->deskripsi }}
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($tagihan->detailPerjaldin as $detail)
                                        <span class="participant-tag">
                                            <i class="bi bi-person-fill"></i>
                                            {{ $detail->nama_pegawai ?? ($detail->pegawai->nama_lengkap ?? '-') }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <span class="bruto-col">
                                    Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}
                                </span>
                            </td>
                            <td>
                                @switch($tagihan->status)
                                    @case('DRAFT')
                                        <span class="status-badge-premium status-draft">
                                            <i class="bi bi-file-earmark-text"></i> Draft
                                        </span>
                                        @break
                                    @case('PENDING_PPK')
                                        <span class="status-badge-premium status-pending">
                                            <i class="bi bi-hourglass-split"></i> Menunggu PPK
                                        </span>
                                        @break
                                    @case('PENDING_VERIFIKASI_PERJALDIN')
                                        <span class="status-badge-premium status-pending">
                                            <i class="bi bi-hourglass-split"></i> Menunggu Verifikator
                                        </span>
                                        @break
                                    @case('PENDING_KASUBBAG')
                                        <span class="status-badge-premium status-pending">
                                            <i class="bi bi-hourglass-split"></i> Menunggu Kasubbag
                                        </span>
                                        @break
                                    @case('PENDING_BENDAHARA')
                                    @case('PENDING_BENDAHARA_PENERIMAAN')
                                    @case('PENDING_BENDAHARA_PENGELUARAN')
                                    @case('PENDING_KOORDINATOR_KEUANGAN')
                                    @case('PENDING_PPSPM')
                                        <span class="status-badge-premium status-pending">
                                            <i class="bi bi-hourglass-split"></i> Menunggu Verifikasi
                                        </span>
                                        @break
                                    @case('MENUNGGU_UPLOAD_NOMINATIF_TTD')
                                        <span class="status-badge-premium status-pending" style="color: #d97706; background: rgba(245, 158, 11, 0.08); border-color: rgba(245, 158, 11, 0.15);">
                                            <i class="bi bi-cloud-upload"></i> Upload TTD
                                        </span>
                                        @break
                                    @case('REVISI_PPK')
                                    @case('DITOLAK_PPK')
                                    @case('REVISI_PPSPM')
                                    @case('DITOLAK_PPSPM')
                                    @case('REVISI_KOORDINATOR_KEUANGAN')
                                    @case('DITOLAK_KOORDINATOR_KEUANGAN')
                                    @case('REVISI_BENDAHARA')
                                    @case('REVISI_BENDAHARA_PENERIMAAN')
                                    @case('DITOLAK_BENDAHARA_PENERIMAAN')
                                    @case('REVISI_BENDAHARA_PENGELUARAN')
                                    @case('DITOLAK_BENDAHARA_PENGELUARAN')
                                    @case('REVISI_KASUBBAG')
                                    @case('DITOLAK_KASUBBAG')
                                        <span class="status-badge-premium status-revisi">
                                            <i class="bi bi-arrow-counterclockwise"></i> Perlu Revisi
                                        </span>
                                        @php $lastLog = $tagihan->logs->first(); @endphp
                                        @if($lastLog && $lastLog->catatan)
                                            <div class="revisi-catatan-box mt-2 p-2 rounded-3 small">
                                                <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>
                                                <strong>Catatan:</strong> {{ $lastLog->catatan }}
                                            </div>
                                        @endif
                                        @break
                                    @case('DISETUJUI_PPK')
                                    @case('DISETUJUI_PERJALDIN')
                                        <span class="status-badge-premium status-disetujui">
                                            <i class="bi bi-check-circle-fill"></i> Disetujui
                                        </span>
                                        @break
                                    @case('PROSES_SPP')
                                    @case('SPP_TERBIT')
                                        <span class="status-badge-premium status-spp">
                                            <i class="bi bi-file-earmark-check-fill"></i> {{ str_replace('_', ' ', $tagihan->status) }}
                                        </span>
                                        @break
                                    @default
                                        <span class="status-badge-premium status-draft">
                                            {{ str_replace('_', ' ', $tagihan->status) }}
                                        </span>
                                @endswitch
                            </td>
                            <td>
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <a href="{{ route('perjaldins.show', $tagihan->id) }}" class="btn-action-view" title="Detail Tagihan">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                    @if(in_array($tagihan->status, $revisiDraftStatuses))
                                        <a href="{{ route('perjaldins.edit-perjaldin', $tagihan->id) }}" class="btn-action-edit-warning" title="Edit Tagihan">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        <button type="button" class="btn-action-delete-danger" onclick="deletePerjaldin('{{ route('perjaldins.destroy-perjaldin', $tagihan->id) }}')" title="Hapus Tagihan">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Global Delete Form -->
        <form id="deleteForm" method="POST" style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    </div>
@endsection
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('#example').DataTable({
                "columnDefs": [{ "orderable": false, "targets": [4] }],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                    infoFiltered: "(disaring dari _MAX_ total data)",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Berikutnya",
                        previous: "Sebelumnya"
                    }
                }
            });
        });

        function deletePerjaldin(url) {
            if (confirm('Hapus Perjaldin ini beserta semua data pesertanya?')) {
                document.getElementById('deleteForm').action = url;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
@endpush
