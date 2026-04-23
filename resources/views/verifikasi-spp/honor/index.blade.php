@extends('layouts.app')
@section('title', 'Verifikasi SPP Honorarium')

@push('css')
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .summary-card { transition: all 0.2s; border: none; border-radius: 12px; }
        .summary-card:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); }
        .summary-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .status-badge { font-weight: 500; padding: 0.35em 0.8em; border-radius: 6px; }
    </style>
@endpush

@section('content')
<div class="row align-items-center mb-4">
    <div class="col-xl-8 col-lg-7">
        <h3 class="fw-bold mb-1">Verifikasi SPP Honorarium</h3>
        <p class="text-muted mb-0">Kelola dan verifikasi SPP honorarium yang diajukan oleh Operator BLU (Role Anda: {{ $roleCode }}).</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Menunggu Aksi Saya -->
    <div class="col-xl-3 col-sm-6">
        <a href="?status_filter=Menunggu Aksi Saya" class="text-decoration-none">
            <div class="card summary-card {{ $statusFilter == 'Menunggu Aksi Saya' ? 'bg-primary text-white shadow' : 'bg-white shadow-sm' }} h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="summary-icon {{ $statusFilter == 'Menunggu Aksi Saya' ? 'bg-white text-primary' : 'bg-light-primary text-primary' }}">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <h3 class="fw-bold mb-0 {{ $statusFilter == 'Menunggu Aksi Saya' ? 'text-white' : 'text-primary' }}">{{ $listMenunggu->count() }}</h3>
                    </div>
                    <h6 class="mb-0 fw-semibold {{ $statusFilter == 'Menunggu Aksi Saya' ? 'text-white' : 'text-muted' }}">Menunggu Aksi Saya</h6>
                </div>
            </div>
        </a>
    </div>

    <!-- Sudah Saya Setujui -->
    <div class="col-xl-3 col-sm-6">
        <a href="?status_filter=Sudah Saya Setujui" class="text-decoration-none">
            <div class="card summary-card {{ $statusFilter == 'Sudah Saya Setujui' ? 'bg-info text-white shadow' : 'bg-white shadow-sm' }} h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="summary-icon {{ $statusFilter == 'Sudah Saya Setujui' ? 'bg-white text-info' : 'bg-light-info text-info' }}">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h3 class="fw-bold mb-0 {{ $statusFilter == 'Sudah Saya Setujui' ? 'text-white' : 'text-info' }}">{{ $listDisetujui->count() }}</h3>
                    </div>
                    <h6 class="mb-0 fw-semibold {{ $statusFilter == 'Sudah Saya Setujui' ? 'text-white' : 'text-muted' }}">Sudah Saya Setujui</h6>
                </div>
            </div>
        </a>
    </div>

    <!-- Revisi -->
    <div class="col-xl-3 col-sm-6">
        <a href="?status_filter=Perlu Revisi" class="text-decoration-none">
            <div class="card summary-card {{ $statusFilter == 'Perlu Revisi' ? 'bg-danger text-white shadow' : 'bg-white shadow-sm' }} h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="summary-icon {{ $statusFilter == 'Perlu Revisi' ? 'bg-white text-danger' : 'bg-light-danger text-danger' }}">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                        <h3 class="fw-bold mb-0 {{ $statusFilter == 'Perlu Revisi' ? 'text-white' : 'text-danger' }}">{{ $listRevisi->count() }}</h3>
                    </div>
                    <h6 class="mb-0 fw-semibold {{ $statusFilter == 'Perlu Revisi' ? 'text-white' : 'text-muted' }}">Dikembalikan (Revisi)</h6>
                </div>
            </div>
        </a>
    </div>

    <!-- Selesai / Full Approved -->
    <div class="col-xl-3 col-sm-6">
        <a href="?status_filter=Selesai" class="text-decoration-none">
            <div class="card summary-card {{ $statusFilter == 'Selesai' ? 'bg-success text-white shadow' : 'bg-white shadow-sm' }} h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="summary-icon {{ $statusFilter == 'Selesai' ? 'bg-white text-success' : 'bg-light-success text-success' }}">
                            <i class="bi bi-check2-all"></i>
                        </div>
                        <h3 class="fw-bold mb-0 {{ $statusFilter == 'Selesai' ? 'text-white' : 'text-success' }}">{{ $listSelesai->count() }}</h3>
                    </div>
                    <h6 class="mb-0 fw-semibold {{ $statusFilter == 'Selesai' ? 'text-white' : 'text-muted' }}">Final / SPP Terbit</h6>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-3 mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
        <h5 class="mb-0 fw-bold"><i class="bi bi-list-task text-primary me-2"></i> Daftar Dokumen SPP Honorarium</h5>
        <form action="{{ route('verifikasi-spp.honor.index') }}" method="GET" class="d-flex gap-2">
            <input type="hidden" name="status_filter" value="{{ $statusFilter }}">
            <div class="input-group">
                <input type="text" class="form-control bg-light border-0" placeholder="Cari No SPP / Uraian..." name="search" value="{{ $search ?? '' }}">
                <button class="btn btn-primary px-3" type="submit"><i class="bi bi-search"></i></button>
                @if($search || $statusFilter !== 'Semua')
                    <a href="{{ route('verifikasi-spp.honor.index') }}" class="btn btn-outline-secondary px-3" title="Reset Filter"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </form>
    </div>
    
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="data-table" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4" width="20%">No. SPP / Tanggal</th>
                        <th class="py-3">Uraian / Deskripsi</th>
                        <th class="py-3 text-end" width="15%">Nilai Netto</th>
                        <th class="py-3 text-center" width="15%">Status Approval Anda</th>
                        <th class="py-3 text-center" width="15%">Status Dokumen</th>
                        <th class="py-3 text-center" width="10%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($filteredSpps as $spp)
                        @php
                            $badgeApproveClass = match($spp->my_approval_status) {
                                'PENDING' => 'bg-warning text-dark',
                                'APPROVED' => 'bg-success',
                                'REVISION' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                            
                            $badgeSppClass = match($spp->status) {
                                'Menunggu Verifikasi' => 'bg-info',
                                'Disetujui PPK' => 'bg-primary',
                                'Revisi' => 'bg-danger',
                                'DISETUJUI_SPP', 'SPP_TERBIT' => 'bg-success',
                                default => 'bg-secondary'
                            };
                        @endphp
                        <tr>
                            <td class="px-4">
                                <div class="fw-bold text-primary">{{ $spp->nomor_spp }}</div>
                                <div class="text-muted small">{{ $spp->tanggal_spp?->format('d M Y') ?? '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ Str::limit($spp->uraian ?? $spp->tagihan->deskripsi, 50) }}</div>
                                <div class="text-muted small">No Tagihan: {{ $spp->tagihan->nomor_tagihan }} | Penerima: {{ $spp->tagihan->detailHonorarium->count() }} Orang</div>
                            </td>
                            <td class="text-end fw-bold text-success">
                                Rp {{ number_format($spp->nominal_spp, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $badgeApproveClass }} status-badge">{{ $spp->my_approval_status }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $badgeSppClass }} status-badge">{{ $spp->status }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('verifikasi-spp.honor.detail', $spp->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    <i class="bi bi-search me-1"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                    Tidak ada dokumen SPP Honorarium yang sesuai filter saat ini.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('script')
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#data-table').DataTable({
                "pageLength": 10,
                "ordering": false,
                "bFilter": false,
                "lengthChange": false,
                "language": {
                    "emptyTable": "Tidak ada data yang tersedia",
                    "infoEmpty": "Menampilkan 0 data",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    "paginate": {
                        "first": "Awal",
                        "last": "Akhir",
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    }
                }
            });
        });
    </script>
@endpush
