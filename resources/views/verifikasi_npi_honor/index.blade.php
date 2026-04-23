@extends('layouts.app')
@section('title', 'Verifikasi NPI Honorarium')

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
        <x-page-title title="Verifikasi NPI" subtitle="Honorarium" />
    </div>
    <div class="text-muted small mb-4">Periksa dan verifikasi NPI draf honorarium yang diajukan oleh Bendahara Pengeluaran.</div>

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
    <div class="row row-cols-2 row-cols-md-4 g-3 mb-4 mt-2">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #ef4444;"> {{-- Red / Action Required --}}
                <div class="card-body p-3">
                    <h6 class="card-title fw-medium mb-1 d-flex align-items-center"><i class="bi bi-exclamation-circle me-1"></i> Menunggu Aksi Saya</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $summary['pending'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #10b981;"> {{-- Green / Approved --}}
                <div class="card-body p-3">
                    <h6 class="card-title fw-medium mb-1 d-flex align-items-center"><i class="bi bi-check2-all me-1"></i> Sudah Saya Setujui</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $summary['approved'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-dark bg-warning">
                <div class="card-body p-3">
                    <h6 class="card-title fw-medium mb-1">Perlu Revisi</h6>
                    <h3 class="fw-bold mb-0">{{ $summary['revisi'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #6366f1;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-medium mb-1">Selesai (NPI Terbit)</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $summary['selesai'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-bar mb-4 d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('verifikasi-npi.honor.index', ['status' => 'semua', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'semua' ? 'active' : '' }}">Semua NPI</a>
            <a href="{{ route('verifikasi-npi.honor.index', ['status' => 'pending', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'pending' ? 'active' : '' }}">Menunggu Aksi Saya</a>
            <a href="{{ route('verifikasi-npi.honor.index', ['status' => 'approved', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'approved' ? 'active' : '' }}">Sudah Disetujui</a>
            <a href="{{ route('verifikasi-npi.honor.index', ['status' => 'revisi', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'revisi' ? 'active' : '' }}">Revisi</a>
            <a href="{{ route('verifikasi-npi.honor.index', ['status' => 'selesai', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'selesai' ? 'active' : '' }}">Selesai</a>
        </div>
        <form action="{{ route('verifikasi-npi.honor.index') }}" method="GET" class="d-flex gap-2" style="min-width: 280px;">
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari NPI, SPM, SPP, Deskripsi..." value="{{ $search }}">
            @if($search) <a href="{{ route('verifikasi-npi.honor.index', ['status' => $statusFilter]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a> @endif
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
        </form>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelNpiV" class="table table-hover table-bordered align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th width="3%">No</th>
                            <th>No. Hak (SPM/NPI)</th>
                            <th>Deskripsi Uraian honor</th>
                            <th>Bendahara Penerimaan Tujuan</th>
                            <th class="text-end">Jumlah NPI</th>
                            <th class="text-center">Status Total NPI</th>
                            <th class="text-center">Status Anda</th>
                            <th class="text-center" width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($viewNpis as $idx => $npi)
                            @php
                                $statusFinalClass = match($npi->statusFinal) {
                                    'Selesai', \App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL => 'bg-success',
                                    'Perlu Revisi', \App\Models\DokumenNpi::STATUS_REVISI => 'bg-danger',
                                    default => 'bg-info text-dark',
                                };

                                $myApprovalClass = match($npi->myApprovalStatus) {
                                    'PENDING' => 'bg-warning text-dark',
                                    'APPROVED' => 'bg-success',
                                    'REVISION', 'REJECTED' => 'bg-danger',
                                    default => 'bg-secondary',
                                };
                            @endphp
                            <tr class="{{ $npi->canAct ? 'table-warning' : '' }}">
                                <td>{{ $idx + 1 }}</td>
                                <td>
                                    <div class="fw-bold text-primary mb-1">{{ $npi->nomor_npi ?? '-' }}</div>
                                    <div class="text-muted small"><i class="bi bi-calendar3"></i> {{ optional($npi->tanggal_npi)->format('dM Y') ?? '-' }}</div>
                                    <hr class="my-1 border-secondary">
                                    <div class="text-dark fw-semibold small">SPM: {{ $npi->spmModel?->nomor_spm ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="fw-medium text-wrap">{{ Str::limit($npi->tagihanModel?->deskripsi ?? '-', 60) }}</div>
                                    <div class="small mt-1 text-muted">Tagihan: {{ $npi->tagihanModel?->nomor_tagihan ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $npi->bendaharaPenerimaan?->name ?? 'Belum ada' }}</div>
                                </td>
                                <td class="text-end fw-bold text-success">
                                    Rp {{ number_format($npi->nominal, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $statusFinalClass }} w-100 p-2" style="font-size: 0.75rem;">
                                        {{ str_replace('_', ' ', $npi->statusFinal) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $myApprovalClass }} px-2 py-1">
                                        {{ $npi->myApprovalStatus }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($npi->canAct)
                                        <a href="{{ route('verifikasi-npi.honor.detail', $npi->id) }}" class="btn btn-sm btn-danger w-100 shadow-sm mb-1 pulse-btn">
                                            <i class="bi bi-shield-check me-1"></i> Verifikasi Sekarang
                                        </a>
                                    @else
                                        <a href="{{ route('verifikasi-npi.honor.detail', $npi->id) }}" class="btn btn-sm btn-outline-secondary w-100 mb-1">
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
    <style>
        @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(220,53,69, 0.7); } 70% { box-shadow: 0 0 0 5px rgba(220,53,69, 0); } 100% { box-shadow: 0 0 0 0 rgba(220,53,69, 0); } }
        .pulse-btn { animation: pulse-red 2s infinite; }
    </style>
    <script>
        $(document).ready(function() {
            $('#tabelNpiV').DataTable({
                pageLength: 25,
                language: {
                    search: "Pencarian:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    paginate: { previous: "Sebelumnya", next: "Selanjutnya" },
                    emptyTable: "Tidak ada antrean Verifikasi NPI Honorarium saat ini.",
                }
            });
        });
    </script>
@endpush
