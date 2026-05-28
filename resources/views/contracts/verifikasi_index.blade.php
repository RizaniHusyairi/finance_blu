@extends('layouts.app')
@section('title', 'Approve Kontrak')

@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .approve-page {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --soft: #f8fafc;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --secondary: #ec4899;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --surface: rgba(255, 255, 255, 0.9);
            --glass: rgba(255, 255, 255, 0.7);
        }

        .approve-hero {
            background: linear-gradient(135deg, #4f46e5 0%, #ec4899 100%);
            border-radius: 16px;
            box-shadow: 0 20px 40px -10px rgba(79, 70, 229, 0.3);
            padding: 32px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            color: white;
        }

        .approve-hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
            z-index: 0;
        }

        .approve-hero-content {
            position: relative;
            z-index: 1;
        }

        .approve-title {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .approve-title-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            font-size: 28px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }

        .approve-title h4 {
            color: white;
            font-weight: 800;
            font-size: 24px;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }

        .approve-subtitle {
            color: rgba(255, 255, 255, 0.8);
            margin: 0;
            font-size: 14px;
            font-weight: 500;
        }

        .approve-filter {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            padding: 10px 16px;
            transition: all 0.3s ease;
        }

        .approve-filter:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .approve-filter label {
            color: white;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .approve-filter .form-select {
            min-width: 200px;
            border: 0;
            box-shadow: none;
            background-color: transparent;
            font-weight: 600;
            color: white;
            cursor: pointer;
        }
        
        .approve-filter .form-select option {
            color: var(--ink);
        }

        .stat-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .stat-tile {
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-tile:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .stat-tile::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 50%; height: 100%;
            background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
            transform: skewX(-20deg);
            transition: all 0.5s ease;
        }
        
        .stat-tile:hover::after {
            left: 200%;
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            color: white;
            font-size: 32px;
            line-height: 1.2;
            font-weight: 800;
            margin-top: 8px;
        }

        .approve-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.05);
            overflow: hidden;
            background: #ffffff;
            transition: all 0.3s ease;
        }

        .approve-tabs-wrap {
            padding: 18px 30px 0;
            background: #ffffff;
        }

        .approve-tabs {
            gap: 10px;
            border-bottom: 1px solid var(--line);
        }

        .approve-tabs .nav-link {
            border: 0;
            border-radius: 12px 12px 0 0;
            color: var(--muted);
            font-weight: 800;
            padding: 12px 18px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            letter-spacing: 0;
        }

        .approve-tabs .nav-link.active {
            color: var(--primary);
            background: #eef2ff;
            box-shadow: inset 0 -3px 0 var(--primary);
        }

        .tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 26px;
            height: 22px;
            padding: 0 7px;
            border-radius: 999px;
            background: rgba(100, 116, 139, .12);
            color: inherit;
            font-size: 12px;
            font-weight: 900;
        }

        .approve-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 24px 30px;
            border-bottom: 1px solid var(--line);
            background: #ffffff;
        }

        .approve-card-title {
            color: var(--ink);
            font-weight: 800;
            font-size: 20px;
            margin: 0;
            letter-spacing: -0.3px;
        }

        .approve-card-note {
            color: var(--muted);
            font-size: 14px;
            margin: 4px 0 0 0;
        }

        .approval-table {
            margin-bottom: 0 !important;
        }

        .approval-table thead th {
            background: var(--soft);
            color: var(--muted);
            border-bottom: 2px solid var(--line) !important;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 18px 20px;
        }

        .approval-table tbody tr {
            transition: all 0.2s ease;
        }

        .approval-table tbody tr:hover {
            background-color: #f8fafc;
            transform: scale(1.002);
            box-shadow: inset 4px 0 0 var(--primary);
        }

        .approval-table tbody td {
            padding: 20px;
            border-bottom: 1px solid var(--line);
            vertical-align: middle;
        }

        .contract-code {
            font-weight: 800;
            color: var(--ink);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-block;
            font-size: 13px;
        }

        .work-title {
            color: var(--ink);
            font-size: 14px;
            font-weight: 600;
            margin-top: 8px;
            display: flex;
            align-items: center;
        }
        
        .work-title i {
            color: var(--primary);
        }

        .vendor-mark {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: var(--primary);
            font-size: 18px;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.1);
        }

        .money-strong {
            color: var(--success);
            font-weight: 800;
            font-size: 15px;
            white-space: nowrap;
            background: #ecfdf5;
            padding: 6px 12px;
            border-radius: 8px;
            display: inline-block;
        }

        .dipa-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            background: #f8fafc;
            color: var(--ink);
            font-size: 13px;
            font-weight: 600;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            transition: all 0.2s ease;
        }
        
        .dipa-chip:hover {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 20px;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .status-chip.pending {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }

        .status-chip.draft {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        .status-chip.revisi {
            background: #fff7ed;
            color: #ea580c;
            border: 1px solid #fed7aa;
        }

        .status-chip.approved {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        
        .status-chip-head {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            color: var(--primary);
            border: none;
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 14px;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.1);
        }

        .btn-review {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 140px;
            border-radius: 10px;
            padding: 10px 16px;
            background: var(--primary);
            color: #fff !important;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 8px 16px rgba(79, 70, 229, 0.25);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
        }

        .btn-review:hover {
            background: white;
            color: var(--primary) !important;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 12px 20px rgba(79, 70, 229, 0.3);
        }
        
        .btn-review:active {
            transform: translateY(0);
        }

        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: var(--muted);
        }

        .empty-state i {
            font-size: 56px;
            background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: block;
            margin-bottom: 16px;
        }

        .approve-card .dataTables_wrapper .row:first-child,
        .approve-card .dataTables_wrapper .row:last-child {
            padding: 20px 30px;
            align-items: center;
        }

        .approve-card .dataTables_length label,
        .approve-card .dataTables_filter label,
        .approve-card .dataTables_info {
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
        }

        .approve-card .dataTables_filter input,
        .approve-card .dataTables_length select {
            border-radius: 8px;
            border: 1px solid var(--line);
            padding: 6px 12px;
            transition: all 0.2s ease;
        }
        
        .approve-card .dataTables_filter input:focus,
        .approve-card .dataTables_length select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            outline: none;
        }

        @media (max-width: 768px) {
            .approve-hero {
                padding: 20px;
            }

            .approve-filter {
                width: 100%;
                align-items: stretch;
                flex-direction: column;
            }

            .approve-filter .form-select {
                min-width: 100%;
            }

            .stat-strip {
                grid-template-columns: 1fr;
            }

            .approve-card-head {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
@endpush

@section('content')
@php
    $pendingCount = $contracts->where('status_kontrak', 'PENDING_REVIEW')->count();
    $draftCount = $contracts->where('status_kontrak', 'DRAFT')->count();
    $revisiCount = $contracts->where('status_kontrak', 'REVISI')->count();
    $totalValue = $contracts->sum('nilai_total_kontrak');
    $historyContracts = $historyContracts ?? collect();
    $historyValue = $historyContracts->sum('nilai_total_kontrak');
@endphp

<div class="approve-page">
    <div class="approve-hero">
        <div class="approve-hero-content">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div class="approve-title">
                    <div class="approve-title-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">Approve Kontrak</h4>
                        <p class="approve-subtitle">Daftar kontrak pengadaan yang masuk ke meja persetujuan PPK.</p>
                    </div>
                </div>

                <form method="GET" action="{{ route('contracts.verifikasi') }}" class="approve-filter">
                    <label for="statusFilter"><i class="bi bi-funnel me-1"></i>Status</label>
                    <select id="statusFilter" name="status" class="form-select" onchange="this.form.submit()">
                        <option value="ALL" {{ $filter === 'ALL' ? 'selected' : '' }}>Semua Pengajuan</option>
                        <option value="PENDING_REVIEW" {{ $filter === 'PENDING_REVIEW' ? 'selected' : '' }}>Menunggu Review PPK</option>
                        <option value="DRAFT" {{ $filter === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                        <option value="REVISI" {{ $filter === 'REVISI' ? 'selected' : '' }}>Revisi</option>
                    </select>
                </form>
            </div>

            <div class="stat-strip">
                <div class="stat-tile">
                    <div class="stat-label">Menunggu PPK</div>
                    <div class="stat-value">{{ number_format($pendingCount, 0, ',', '.') }}</div>
                </div>
                <div class="stat-tile">
                    <div class="stat-label">Revisi / Draft</div>
                    <div class="stat-value">{{ number_format($draftCount + $revisiCount, 0, ',', '.') }}</div>
                </div>
                <div class="stat-tile">
                    <div class="stat-label">Nilai Dalam Daftar</div>
                    <div class="stat-value" style="font-size: 24px;">Rp {{ number_format($totalValue, 0, ',', '.') }}</div>
                </div>
                <div class="stat-tile">
                    <div class="stat-label">Riwayat Approve</div>
                    <div class="stat-value">{{ number_format($historyContracts->count(), 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card approve-card">
        <div class="approve-card-head">
            <div>
                <h5 class="approve-card-title">Pengajuan & Riwayat Kontrak</h5>
                <p class="approve-card-note">Review kontrak baru atau lihat arsip kontrak yang sudah pernah disetujui.</p>
            </div>
            <span class="status-chip status-chip-head">
                <i class="bi bi-stack me-1"></i>{{ number_format($contracts->count() + $historyContracts->count(), 0, ',', '.') }} Dokumen
            </span>
        </div>

        <div class="approve-tabs-wrap">
            <ul class="nav nav-tabs approve-tabs" id="approveContractTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="pengajuan-tab" data-bs-toggle="tab" data-bs-target="#pengajuan-pane" type="button" role="tab" aria-controls="pengajuan-pane" aria-selected="true">
                        <i class="bi bi-inbox"></i> Pengajuan
                        <span class="tab-count">{{ number_format($contracts->count(), 0, ',', '.') }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="riwayat-tab" data-bs-toggle="tab" data-bs-target="#riwayat-pane" type="button" role="tab" aria-controls="riwayat-pane" aria-selected="false">
                        <i class="bi bi-clock-history"></i> Riwayat Approve
                        <span class="tab-count">{{ number_format($historyContracts->count(), 0, ',', '.') }}</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content" id="approveContractTabsContent">
            <div class="tab-pane fade show active" id="pengajuan-pane" role="tabpanel" aria-labelledby="pengajuan-tab" tabindex="0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100 approval-table" id="tableVerifikasi">
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th width="30%">Nomor SPK & Pekerjaan</th>
                                <th width="18%">Vendor</th>
                                <th width="14%">Nilai Kontrak</th>
                                <th width="15%">Beban DIPA</th>
                                <th width="10%">Status</th>
                                <th width="8%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($contracts as $k)
                                @php
                                    $statusClass = match($k->status_kontrak) {
                                        'PENDING_REVIEW' => 'pending',
                                        'REVISI' => 'revisi',
                                        default => 'draft',
                                    };
                                    $statusLabel = match($k->status_kontrak) {
                                        'PENDING_REVIEW' => 'Menunggu',
                                        'REVISI' => 'Revisi',
                                        default => 'Draft',
                                    };
                                @endphp
                                <tr>
                                    <td class="text-center text-muted fw-semibold">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="contract-code">{{ $k->nomor_spk }}</div>
                                        <div class="work-title">
                                            <i class="bi bi-briefcase me-1"></i>{{ Str::limit($k->nama_pekerjaan, 64) }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="vendor-mark">
                                                <i class="bi bi-building"></i>
                                            </div>
                                            <div class="fw-bold text-dark">{{ $k->vendor->nama_pihak ?? $k->vendor->nama_perusahaan ?? 'N/A' }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="money-strong">Rp {{ number_format($k->nilai_total_kontrak, 0, ',', '.') }}</span>
                                    </td>
                                    <td>
                                        <span class="dipa-chip">
                                            <i class="bi bi-bank"></i>{{ $k->dipa->nomor_dipa ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-chip {{ $statusClass }}">
                                            <i class="bi bi-circle-fill" style="font-size: 7px;"></i>{{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('contracts.verifikasi.show', $k->id) }}" class="btn-review">
                                            <i class="bi bi-eye"></i> Review
                                        </a>
                                    </td>
                                </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($contracts->isEmpty())
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <div class="fw-bold text-dark">Belum ada kontrak untuk ditinjau.</div>
                        <div>Kontrak yang diajukan oleh Pejabat Pengadaan akan tampil di sini.</div>
                    </div>
                @endif
            </div>

            <div class="tab-pane fade" id="riwayat-pane" role="tabpanel" aria-labelledby="riwayat-tab" tabindex="0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100 approval-table" id="tableRiwayatApprove">
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th width="28%">Nomor SPK & Pekerjaan</th>
                                <th width="18%">Vendor</th>
                                <th width="14%">Nilai Kontrak</th>
                                <th width="15%">Beban DIPA</th>
                                <th width="12%">Disetujui</th>
                                <th width="8%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($historyContracts as $k)
                                <tr>
                                    <td class="text-center text-muted fw-semibold">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="contract-code">{{ $k->nomor_spk }}</div>
                                        <div class="work-title">
                                            <i class="bi bi-briefcase me-1"></i>{{ Str::limit($k->nama_pekerjaan, 64) }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="vendor-mark">
                                                <i class="bi bi-building-check"></i>
                                            </div>
                                            <div class="fw-bold text-dark">{{ $k->vendor->nama_pihak ?? $k->vendor->nama_perusahaan ?? 'N/A' }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="money-strong">Rp {{ number_format($k->nilai_total_kontrak, 0, ',', '.') }}</span>
                                    </td>
                                    <td>
                                        <span class="dipa-chip">
                                            <i class="bi bi-bank"></i>{{ $k->dipa->nomor_dipa ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-chip approved">
                                            <i class="bi bi-check-circle-fill"></i>{{ optional($k->ppk_approved_at)->translatedFormat('d M Y') ?? '-' }}
                                        </span>
                                        <div class="small text-muted mt-1">{{ optional($k->ppk_approved_at)->format('H:i') ?? '' }}</div>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('contracts.verifikasi.show', $k->id) }}" class="btn-review">
                                            <i class="bi bi-folder2-open"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($historyContracts->isEmpty())
                    <div class="empty-state">
                        <i class="bi bi-clock-history"></i>
                        <div class="fw-bold text-dark">Belum ada riwayat approve.</div>
                        <div>Kontrak yang sudah disetujui PPK akan tersimpan di tab ini.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            const tableOptions = {
                language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
                order: [[0, "asc"]],
                pageLength: 10,
                autoWidth: false
            };

            const tableVerifikasi = $('#tableVerifikasi').DataTable(tableOptions);
            const tableRiwayat = $('#tableRiwayatApprove').DataTable({
                ...tableOptions,
                order: [[5, "desc"]]
            });

            document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(function (tab) {
                tab.addEventListener('shown.bs.tab', function () {
                    tableVerifikasi.columns.adjust();
                    tableRiwayat.columns.adjust();
                });
            });
        });
    </script>
@endpush
