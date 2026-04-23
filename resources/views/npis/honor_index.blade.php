@extends('layouts.app')
@section('title', 'Pembuatan NPI Honorarium')

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
        <x-page-title title="Pembuatan NPI" subtitle="Honorarium" />
    </div>
    <div class="text-muted small mb-4">Buat dan kelola NPI dari SPM Honorarium yang sudah disetujui.</div>

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
    <div class="row row-cols-2 row-cols-md-4 row-cols-xl-5 g-3 mb-4 mt-2">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-warning text-dark">
                <div class="card-body p-3">
                    <h6 class="card-title fw-medium mb-1">Siap Dibuat NPI</h6>
                    <h3 class="fw-bold mb-0">{{ $summary['belum_dibuat'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #6366f1;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-medium mb-1">Draft & Revisi</h6>
                    <h3 class="fw-bold mb-0">{{ $summary['draft_revisi'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white bg-info">
                <div class="card-body p-3">
                    <h6 class="card-title fw-medium mb-1 d-flex align-items-center"><i class="bi bi-clock-history me-1"></i> Menunggu</h6>
                    <h3 class="fw-bold mb-0">{{ $summary['menunggu'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white bg-success">
                <div class="card-body p-3">
                    <h6 class="card-title fw-medium mb-1">Selesai (Terbit)</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $summary['selesai'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-bar mb-4 d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('npis.honor.index', ['status' => 'semua', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'semua' ? 'active' : '' }}">Semua</a>
            <a href="{{ route('npis.honor.index', ['status' => 'belum_dibuat', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'belum_dibuat' ? 'active' : '' }}">Siap Dibuat NPI</a>
            <a href="{{ route('npis.honor.index', ['status' => 'draft', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'draft' ? 'active' : '' }}">Draft NPI</a>
            <a href="{{ route('npis.honor.index', ['status' => 'menunggu', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'menunggu' ? 'active' : '' }}">Menunggu Verifikasi</a>
            <a href="{{ route('npis.honor.index', ['status' => 'revisi', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'revisi' ? 'active' : '' }}">Revisi</a>
            <a href="{{ route('npis.honor.index', ['status' => 'selesai', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'selesai' ? 'active' : '' }}">Selesai</a>
        </div>
        <form action="{{ route('npis.honor.index') }}" method="GET" class="d-flex gap-2" style="min-width: 280px;">
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari NPI, SPM, SPP, Tagihan..." value="{{ $search }}">
            @if($search) <a href="{{ route('npis.honor.index', ['status' => $statusFilter]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a> @endif
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
        </form>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelNpiHonor" class="table table-hover table-bordered align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th width="3%">No</th>
                            <th>Info NPI & SPM</th>
                            <th>Deskripsi Tagihan Honor</th>
                            <th>Penerima</th>
                            <th class="text-end">Nilai Netto (SPM)</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($spmList as $idx => $spm)
                            @php
                                $npi = $spm->npi;
                                $spp = $spm->spp;
                                $tagihan = $spp?->tagihan;
                                
                                $statusSpmClass = match($spm->status) {
                                    'Disetujui Final' => 'bg-success',
                                    default => 'bg-secondary',
                                };

                                $statusNpiLabel = $npi?->status_spp ?? 'Belum Dibuat';
                                $statusNpiClass = match($npi?->status ?? '') {
                                    \App\Models\DokumenNpi::STATUS_DRAFT => 'bg-warning text-dark border',
                                    \App\Models\DokumenNpi::STATUS_REVISI => 'bg-danger text-white',
                                    \App\Models\DokumenNpi::STATUS_SUBMITTED_KASUBAG,
                                    \App\Models\DokumenNpi::STATUS_SUBMITTED_PPK,
                                    \App\Models\DokumenNpi::STATUS_SUBMITTED_BENPEN,
                                    \App\Models\DokumenNpi::STATUS_MENUNGGU_VERIFIKASI => 'bg-info text-dark',
                                    \App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL,
                                    \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG => 'bg-success text-white',
                                    default => 'bg-light text-dark border',
                                };
                            @endphp
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>
                                    @if($npi)
                                        <div class="fw-bold text-primary mb-1">{{ $npi->nomor_npi ?? 'Draft NPI' }}</div>
                                        <div class="text-muted small"><i class="bi bi-calendar3"></i> {{ optional($npi->tanggal_npi)->format('d F Y') ?? '-' }}</div>
                                    @else
                                        <div class="fw-bold text-muted fst-italic mb-1">Belum Ada NPI</div>
                                    @endif
                                    <hr class="my-1 border-secondary">
                                    <div class="text-dark fw-semibold small">SPM: {{ $spm->nomor_spm ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="fw-medium text-wrap">{{ Str::limit($tagihan?->deskripsi ?? '-', 70) }}</div>
                                    <div class="small mt-1 text-muted">No Tagihan: {{ $tagihan?->nomor_tagihan ?? '-' }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary">{{ count($tagihan?->detailHonorarium ?? []) }} Orang</span>
                                </td>
                                <td class="text-end fw-bold text-success">
                                    Rp {{ number_format($spm->nominal_spm ?? $tagihan?->total_netto ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <div class="mb-1">
                                        <span class="badge {{ $statusNpiClass }} w-100 p-2" style="font-size: 0.75rem;">
                                            {{ $npi ? $npi->status : 'Siap Dibuat NPI' }}
                                        </span>
                                    </div>
                                    @if($npi && in_array($npi->status, [\App\Models\DokumenNpi::STATUS_REVISI, \App\Models\DokumenNpi::STATUS_DRAFT]))
                                        <div class="mt-1 small fw-semibold text-warning">Menunggu Aksi Anda</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(!$npi || in_array($npi->status, [\App\Models\DokumenNpi::STATUS_DRAFT, \App\Models\DokumenNpi::STATUS_REVISI]))
                                        <a href="{{ route('npis.honor.detail', $spm->id) }}" class="btn btn-sm btn-primary w-100 shadow-sm mb-1">
                                            <i class="bi bi-pencil-square me-1"></i> Proses
                                        </a>
                                    @else
                                        <a href="{{ route('npis.honor.detail', $spm->id) }}" class="btn btn-sm btn-outline-secondary w-100 mb-1">
                                            <i class="bi bi-eye me-1"></i> Detail
                                        </a>
                                    @endif
                                    
                                    @if($npi && in_array($npi->status, [\App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL, \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG]))
                                        <a href="{{ route('npis.cetak-pdf', $npi->id) }}" target="_blank" class="btn btn-sm btn-outline-danger w-100">
                                            <i class="bi bi-file-pdf"></i> Cetak
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
            $('#tabelNpiHonor').DataTable({
                pageLength: 25,
                language: {
                    search: "Pencarian:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    paginate: { previous: "Sebelumnya", next: "Selanjutnya" },
                    emptyTable: "Tidak ada antrean Pembuatan NPI.",
                }
            });
        });
    </script>
@endpush
