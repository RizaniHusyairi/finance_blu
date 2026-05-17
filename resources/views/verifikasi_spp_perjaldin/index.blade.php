@extends('layouts.app')
@section('title', 'Verifikasi SPP Perjaldin — ' . $roleLabel)
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Verifikasi SPP Perjaldin" subtitle="{{ $roleLabel }}" />

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
                    <div class="stat-sub mt-1">SPP perjaldin siap dicek</div>
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
                    <div class="stat-sub mt-1">Final, siap proses SPM</div>
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
            <h6 class="mb-3">Daftar SPP Perjaldin</h6>
            <div class="table-responsive">
                <table id="tblSppPerjaldin" class="table table-striped table-bordered align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No. SPP / Tanggal</th>
                            <th>Komponen Biaya</th>
                            <th>Tagihan Perjaldin</th>
                            <th class="text-end">Nilai SPP (Rp)</th>
                            <th class="text-center">Status PPK</th>
                            <th class="text-center">Status Koord. Keuangan</th>
                            <th class="text-center">Status Kasubbag</th>
                            <th class="text-center">Status Final</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($viewSpps as $idx => $spp)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $spp->nomor_spp }}</strong><br>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-calendar-check"></i> {{ \Carbon\Carbon::parse($spp->tanggal_spp)->isoFormat('D MMM Y') }}
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold">{{ $spp->tagihanPerjaldinKomponen?->nama_komponen ?? '-' }}</span><br>
                                @if($spp->tagihanPerjaldinKomponen?->dipaRevisionItem?->coa)
                                    <span class="badge bg-primary-subtle text-primary small">{{ $spp->tagihanPerjaldinKomponen->dipaRevisionItem->coa->kode_akun }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-muted d-block small">{{ $spp->tagihan?->nomor_tagihan ?? '-' }}</span>
                                <span class="fw-bold text-truncate d-block" style="max-width: 200px;" title="{{ $spp->tagihan?->deskripsi }}">{{ $spp->tagihan?->deskripsi ?? '-' }}</span>
                            </td>
                            <td class="text-end text-success fw-bold">
                                Rp {{ number_format($spp->nominal_spp, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if($spp->ppkApprovalStatus === 'APPROVED')
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Disetujui</span>
                                @elseif($spp->ppkApprovalStatus === 'REVISION')
                                    <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Revisi</span>
                                @else
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Pending</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($spp->koordinatorApprovalStatus === 'APPROVED')
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Disetujui</span>
                                @elseif($spp->koordinatorApprovalStatus === 'REVISION')
                                    <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Revisi</span>
                                @else
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Pending</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($spp->kasApprovalStatus === 'APPROVED')
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Disetujui</span>
                                @elseif($spp->kasApprovalStatus === 'REVISION')
                                    <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Revisi</span>
                                @else
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Pending</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($spp->statusFinal === 'Selesai Diverifikasi')
                                    <span class="badge bg-success"><i class="bi bi-check2-all"></i> Selesai</span>
                                @elseif($spp->statusFinal === 'Perlu Revisi')
                                    <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Direvisi</span>
                                @else
                                    <span class="badge bg-info text-white"><i class="bi bi-arrow-repeat"></i> {{ $spp->statusFinal }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route($showRoute, $spp->id) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i> Detail
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
    <script>$(document).ready(function() { $('#tblSppPerjaldin').DataTable({ "order": [] }); });</script>
@endpush
