@extends('layouts.app')
@section('title', 'Verifikasi SPM Perjaldin — ' . $roleLabel)
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Verifikasi SPM Perjaldin" subtitle="{{ $roleLabel }}" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show">
            <div class="text-white">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning border-0 bg-warning alert-dismissible fade show">
            <div class="text-dark">{{ session('warning') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <style>
        .stat-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-left: 4px solid var(--accent, #6c757d);
            border-radius: .5rem;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.075) !important;
        }
        .stat-card .stat-icon {
            width: 36px; height: 36px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: .5rem;
            background: var(--accent-bg, rgba(108,117,125,.1));
            color: var(--accent, #6c757d);
            font-size: 1.1rem;
        }
        .stat-card .stat-label { font-size: .8rem; color: #6c757d; text-transform: uppercase; letter-spacing: .03em; font-weight: 600; }
        .stat-card .stat-value { font-size: 1.85rem; font-weight: 700; line-height: 1.1; color: #212529; }
        .stat-card .stat-sub   { font-size: .75rem; color: #adb5bd; }
    </style>

    {{-- Summary Cards --}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4 mt-2">
        <div class="col">
            <div class="card stat-card h-100 shadow-sm" style="--accent: #f59f00; --accent-bg: rgba(245,159,0,.12);">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
                        <span class="stat-label">Menunggu Verifikasi Saya</span>
                    </div>
                    <div class="stat-value">{{ $countPending ?? 0 }}</div>
                    <div class="stat-sub mt-1">SPM perjaldin siap dicek</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card h-100 shadow-sm" style="--accent: #1c7ed6; --accent-bg: rgba(28,126,214,.12);">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
                        <span class="stat-label">Sudah Saya Setujui</span>
                    </div>
                    <div class="stat-value">{{ $countApprovedMe ?? 0 }}</div>
                    <div class="stat-sub mt-1">Lanjut ke tahap berikutnya</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card h-100 shadow-sm" style="--accent: #e03131; --accent-bg: rgba(224,49,49,.12);">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="stat-icon"><i class="bi bi-arrow-counterclockwise"></i></span>
                        <span class="stat-label">Perlu Revisi</span>
                    </div>
                    <div class="stat-value">{{ $countRevisi ?? 0 }}</div>
                    <div class="stat-sub mt-1">Dikembalikan ke operator</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card h-100 shadow-sm" style="--accent: #20c997; --accent-bg: rgba(32,201,151,.12);">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="stat-icon"><i class="bi bi-check2-all"></i></span>
                        <span class="stat-label">Selesai Diverifikasi</span>
                    </div>
                    <div class="stat-value">{{ $countSelesai ?? 0 }}</div>
                    <div class="stat-sub mt-1">SPM terbit, siap NPI</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <form action="{{ route($indexRoute) }}" method="GET" class="d-flex align-items-center gap-3">
                <label class="fw-bold mb-0">Filter Status Saya:</label>
                <select name="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <option value="Semua" {{ request('status') == 'Semua' ? 'selected' : '' }}>Semua Pengajuan</option>
                    <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Approved" {{ request('status') == 'Approved' ? 'selected' : '' }}>Disetujui</option>
                    <option value="Revisi" {{ request('status') == 'Revisi' ? 'selected' : '' }}>Revisi</option>
                </select>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="mb-3">Daftar SPM Perjaldin</h6>
            <div class="table-responsive">
                <table id="tblSpmPerjaldin" class="table table-striped table-bordered align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No. SPM / Tanggal</th>
                            <th>No. SPP</th>
                            <th>Komponen / COA</th>
                            <th class="text-end">Nilai SPM (Rp)</th>
                            <th class="text-center">Status PPSPM</th>
                            <th class="text-center">Status Kasubbag</th>
                            <th class="text-center">Status Final</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($viewSpms as $idx => $spm)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $spm->nomor_spm }}</strong><br>
                                <span class="badge bg-light text-dark border">
                                    <i class="material-icons-outlined" style="font-size: 12px; vertical-align: middle;">calendar_today</i>
                                    {{ \Carbon\Carbon::parse($spm->tanggal_spm)->isoFormat('D MMM Y') }}
                                </span>
                            </td>
                            <td>
                                <span class="text-muted d-block small">{{ $spm->spp?->nomor_spp ?? '-' }}</span>
                            </td>
                            <td>
                                <span class="fw-bold">{{ $spm->spp?->tagihanPerjaldinKomponen?->nama_komponen ?? '-' }}</span><br>
                                @if($spm->spp?->tagihanPerjaldinKomponen?->dipaRevisionItem?->coa)
                                    <span class="badge bg-primary-subtle text-primary small">{{ $spm->spp->tagihanPerjaldinKomponen->dipaRevisionItem->coa->kode_akun }}</span>
                                @endif
                            </td>
                            <td class="text-end text-success fw-bold">
                                Rp {{ number_format($spm->nominal_spm, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if($spm->ppspmApprovalStatus === 'APPROVED')
                                    <span class="badge bg-success">Disetujui</span>
                                @elseif($spm->ppspmApprovalStatus === 'REVISION')
                                    <span class="badge bg-danger">Revisi</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($spm->kasApprovalStatus === 'APPROVED')
                                    <span class="badge bg-success">Disetujui</span>
                                @elseif($spm->kasApprovalStatus === 'REVISION')
                                    <span class="badge bg-danger">Revisi</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($spm->statusFinal === 'Selesai Diverifikasi')
                                    <span class="badge bg-success">Selesai</span>
                                @elseif($spm->statusFinal === 'Perlu Revisi')
                                    <span class="badge bg-danger">Direvisi</span>
                                @else
                                    <span class="badge bg-info text-white">{{ $spm->statusFinal }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route($showRoute, $spm->id) }}" class="btn btn-sm btn-primary">
                                    <i class="material-icons-outlined" style="font-size: 16px; vertical-align: middle;">visibility</i> Detail
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>$(document).ready(function() { $('#tblSpmPerjaldin').DataTable({ "order": [] }); });</script>
@endpush
