@extends('layouts.app')
@section('title', 'Verifikasi SPM Honorarium')

@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .filter-bar { background: #f8f9fc; border: 1px solid rgba(15,23,42,.06); border-radius: .75rem; padding: 1rem 1.25rem; }
        .status-btn { border: 1px solid #dee2e6; background: #fff; border-radius: .5rem; padding: .35rem .85rem; font-size: .82rem; font-weight: 600; color: #475569; transition: all .15s; cursor: pointer; text-decoration: none;}
        .status-btn:hover { border-color: #0d6efd; color: #0d6efd; }
        .status-btn.active { background: #0d6efd; border-color: #0d6efd; color: #fff; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-0">
        <x-page-title title="Verifikasi SPM" subtitle="Honorarium" />
    </div>
    <div class="text-muted small mb-4">Periksa dan verifikasi SPM honorarium yang diajukan Operator BLU. Role Anda saat ini: <strong>{{ $roleCode }}</strong>.</div>

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success text-white alert-dismissible fade show">
            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 alert-dismissible fade show">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4 mt-2">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-warning text-dark">
                <div class="card-body p-3">
                    <h6 class="card-title fw-medium mb-1">Menunggu Aksi Saya</h6>
                    <h3 class="fw-bold mb-0">{{ $summary['pending'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #0d6efd;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-medium mb-1">Sudah Saya Setujui</h6>
                    <h3 class="fw-bold mb-0">{{ $summary['approved'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white bg-danger">
                <div class="card-body p-3">
                    <h6 class="card-title fw-medium mb-1">Perlu Revisi</h6>
                    <h3 class="fw-bold mb-0">{{ $summary['revisi'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white bg-success">
                <div class="card-body p-3">
                    <h6 class="card-title fw-medium mb-1">Selesai (Final)</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $summary['selesai'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-bar mb-4 d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('verifikasi-spm.honor.index', ['status' => 'semua', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'semua' ? 'active' : '' }}">Semua</a>
            <a href="{{ route('verifikasi-spm.honor.index', ['status' => 'pending', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'pending' ? 'active' : '' }}">Menunggu Aksi Saya</a>
            <a href="{{ route('verifikasi-spm.honor.index', ['status' => 'approved', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'approved' ? 'active' : '' }}">Sudah Disetujui</a>
            <a href="{{ route('verifikasi-spm.honor.index', ['status' => 'revisi', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'revisi' ? 'active' : '' }}">Revisi</a>
            <a href="{{ route('verifikasi-spm.honor.index', ['status' => 'selesai', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'selesai' ? 'active' : '' }}">Selesai</a>
        </div>
        <form action="{{ route('verifikasi-spm.honor.index') }}" method="GET" class="d-flex gap-2" style="min-width: 280px;">
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari SPM, SPP, Tagihan..." value="{{ $search }}">
            @if($search) <a href="{{ route('verifikasi-spm.honor.index', ['status' => $statusFilter]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a> @endif
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
        </form>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelSpmHonor" class="table table-hover table-bordered align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th width="3%">No</th>
                            <th>Nomor SPM & SPP</th>
                            <th>Deskripsi Pekerjaan</th>
                            <th>Anggaran</th>
                            <th class="text-end">Nilai SPM</th>
                            <th class="text-center">Status SPM & Verifikasi</th>
                            <th class="text-center" width="8%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($spmList as $idx => $spm)
                            @php
                                $spp = $spm->spp;
                                $tagihan = $spp?->tagihan;
                                $coa = $spm->dipaRevisionItem?->coa;
                                
                                $instance = $spm->workflowInstances->first();
                                $myApproval = collect($instance?->approvals ?? [])->firstWhere('role_code', $roleCode);
                                
                                $statusSpmClass = match($spm->status) {
                                    'DRAFT' => 'bg-warning text-dark',
                                    'Revisi' => 'bg-danger',
                                    'Menunggu Verifikasi' => 'bg-info',
                                    'Disetujui Final' => 'bg-success',
                                    default => 'bg-secondary',
                                };

                                $myApprovalClass = match($myApproval?->status ?? '-') {
                                    'APPROVED' => 'bg-success text-white',
                                    'PENDING' => 'bg-warning text-dark',
                                    'REVISION' => 'bg-danger text-white',
                                    default => 'bg-light text-muted',
                                };
                            @endphp
                            <tr class="{{ ($myApproval?->status === 'PENDING') ? 'table-warning' : '' }}">
                                <td>{{ $idx + 1 }}</td>
                                <td>
                                    <div class="fw-semibold text-primary mb-1">{{ $spm->nomor_spm ?? '-' }}</div>
                                    <div class="text-muted small">SPP: {{ $spp?->nomor_spp ?? '-' }}</div>
                                    <div class="text-muted small"><i class="bi bi-calendar3"></i> {{ optional($spm->tanggal_spm)->format('d/m/Y') }}</div>
                                </td>
                                <td>
                                    <div class="fw-medium text-wrap">{{ Str::limit($tagihan?->deskripsi ?? '-', 70) }}</div>
                                    <div class="small mt-1 text-muted">
                                        Penerima: <span class="badge bg-secondary-subtle text-secondary">{{ count($tagihan?->detailHonorarium ?? []) }} Orang</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $coa?->kode_mak_lengkap ?? '-' }}</div>
                                    <div class="small text-muted">{{ Str::limit($coa?->nama_akun ?? '-', 40) }}</div>
                                </td>
                                <td class="text-end fw-bold text-success">
                                    Rp {{ number_format($spm->nominal_spm ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <div class="mb-2"><span class="badge {{ $statusSpmClass }} w-100">{{ $spm->status }}</span></div>
                                    <div class="small text-muted fw-semibold mb-1">Aksi Anda:</div>
                                    <div><span class="badge {{ $myApprovalClass }} w-100">{{ $myApproval?->status ?? '-' }}</span></div>
                                </td>
                                <td class="text-center">
                                    @if($myApproval?->status === 'PENDING')
                                        <a href="{{ route('verifikasi-spm.honor.detail', $spm->id) }}" class="btn btn-sm btn-primary w-100">
                                            <i class="bi bi-pencil-square me-1"></i> Proses
                                        </a>
                                    @else
                                        <a href="{{ route('verifikasi-spm.honor.detail', $spm->id) }}" class="btn btn-sm btn-outline-primary w-100">
                                            <i class="bi bi-eye me-1"></i> Detail
                                        </a>
                                    @endif
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
    <script>
        $(document).ready(function() {
            $('#tabelSpmHonor').DataTable({
                pageLength: 25,
                order: [[5, 'desc'], [0, 'asc']], // Urutkan yang PENDING di atas secara native (table-warning)
                language: {
                    search: "Pencarian:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    paginate: { previous: "Sebelumnya", next: "Selanjutnya" },
                    emptyTable: "Tidak ada antrean SPM Honorarium.",
                }
            });
        });
    </script>
@endpush
