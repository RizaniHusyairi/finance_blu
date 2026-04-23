@extends('layouts.app')
@section('title', 'Verifikasi SP2D Honorarium')

@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .filter-bar { background: #f8fafc; border: 1px solid rgba(15,23,42,.06); border-radius: .75rem; padding: 1rem 1.25rem; }
        .status-btn { border: 1px solid #e2e8f0; background: #fff; border-radius: .5rem; padding: .35rem .85rem; font-size: .82rem; font-weight: 600; color: #475569; transition: all .15s; cursor: pointer; text-decoration: none;}
        .status-btn:hover { border-color: #3b82f6; color: #3b82f6; }
        .status-btn.active { background: #3b82f6; border-color: #3b82f6; color: #fff; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-0">
        <x-page-title title="Verifikasi Bendahara SP2D" subtitle="Honorarium" />
    </div>
    <div class="text-muted small mb-4">Daftar verifikasi penguncian rekening SP2D. Role: <span class="badge bg-secondary">{{ $roleCode }}</span></div>

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success text-white alert-dismissible fade show">
            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
            <div class="card h-100 border-0 shadow-sm text-dark bg-warning bg-opacity-25 border border-warning">
                <div class="card-body p-3">
                    <h6 class="card-title fw-semibold mb-1"><i class="bi bi-hourglass-split text-warning me-1"></i> Menunggu Aksi Saya</h6>
                    <h3 class="fw-bold mb-0 text-warning">{{ $summary['pending'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-dark bg-success bg-opacity-25 border border-success">
                <div class="card-body p-3">
                    <h6 class="card-title fw-semibold mb-1"><i class="bi bi-check-circle text-success me-1"></i> Sudah Saya Setujui</h6>
                    <h3 class="fw-bold mb-0 text-success">{{ $summary['approved'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white bg-danger">
                <div class="card-body p-3">
                    <h6 class="card-title fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Revisi</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $summary['revisi'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white bg-primary">
                <div class="card-body p-3">
                    <h6 class="card-title fw-semibold mb-1"><i class="bi bi-check2-all me-1"></i> Selesai (Final)</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $summary['selesai'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-bar mb-4 d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('verifikasi-sp2d.honor.index', ['status' => 'semua', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'semua' ? 'active' : '' }}">Semua</a>
            <a href="{{ route('verifikasi-sp2d.honor.index', ['status' => 'pending', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'pending' ? 'active' : '' }}">Menunggu Tindakan</a>
            <a href="{{ route('verifikasi-sp2d.honor.index', ['status' => 'approved', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'approved' ? 'active' : '' }}">Disetujui Saya</a>
            <a href="{{ route('verifikasi-sp2d.honor.index', ['status' => 'revisi', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'revisi' ? 'active' : '' }}">Revisi</a>
            <a href="{{ route('verifikasi-sp2d.honor.index', ['status' => 'selesai', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'selesai' ? 'active' : '' }}">Selesai</a>
        </div>
        <form action="{{ route('verifikasi-sp2d.honor.index') }}" method="GET" class="d-flex gap-2" style="min-width: 250px;">
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari SP2D/NPI/Kegiatan..." value="{{ $search }}">
            @if($search) <a href="{{ route('verifikasi-sp2d.honor.index', ['status' => $statusFilter]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a> @endif
            <button type="submit" class="btn btn-sm btn-dark"><i class="bi bi-search"></i></button>
        </form>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelVerifSp2d" class="table table-hover table-bordered align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th width="3%">No</th>
                            <th>Identitas Dokumen SP2D</th>
                            <th width="30%">Referensi Kegiatan Tagihan</th>
                            <th>Distribusi Penerima</th>
                            <th class="text-end">Nilai SP2D Ril</th>
                            <th class="text-center">Status Anda</th>
                            <th class="text-center">Aksi Lanjut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($viewSp2ds as $idx => $sp2d)
                            @php
                                $statusClass = match($sp2d->myApprovalStatus) {
                                    'PENDING'  => 'bg-warning text-dark',
                                    'APPROVED' => 'bg-success',
                                    'REVISION' => 'bg-danger',
                                    default    => 'bg-secondary',
                                };

                                if ($sp2d->statusFinal === 'Selesai') $statusClass = 'bg-primary';
                            @endphp
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>
                                    <div class="fw-bold text-success mb-1">{{ $sp2d->nomor_sp2d ?? '-' }}</div>
                                    <div class="text-muted small"><i class="bi bi-calendar3"></i> {{ optional($sp2d->tanggal_sp2d)->format('d-M-Y') ?? '-' }}</div>
                                    <hr class="my-1 border-secondary">
                                    <div class="text-dark fw-bold small">Dari NPI: <span class="text-primary">{{ $sp2d->npiModel?->nomor_npi ?? '-' }}</span></div>
                                </td>
                                <td>
                                    <div class="fw-medium text-wrap">{{ Str::limit($sp2d->tagihanModel?->deskripsi ?? '-', 65) }}</div>
                                    <div class="small mt-1 text-muted"><span class="badge border bg-light text-dark fw-normal">Asal SPP: {{ $sp2d->sppModel?->nomor_spp ?? '-' }}</span></div>
                                </td>
                                <td>
                                    <div class="small fw-medium mt-1"><i class="bi bi-people text-secondary"></i> {{ collect($sp2d->tagihanModel?->detailHonorarium)->count() }} Personel Tercatat</div>
                                </td>
                                <td class="text-end fw-bold text-dark fs-6">
                                    Rp {{ number_format($sp2d->nominal, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $statusClass }} w-100 py-2 shadow-sm" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                        {{ $sp2d->statusFinal === 'Selesai' ? 'SELESAI_SP2D' : ($sp2d->myApprovalStatus !== 'N/A' ? $sp2d->myApprovalStatus : 'DALAM_ANTREAN') }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($sp2d->canAct)
                                        <a href="{{ route('verifikasi-sp2d.honor.detail', $sp2d->id) }}" class="btn btn-sm btn-danger w-100 shadow-sm mb-1 fw-bold pulse-danger">
                                            <i class="bi bi-shield-check me-1"></i> Verifikasi Sekarang
                                        </a>
                                    @else
                                        <a href="{{ route('verifikasi-sp2d.honor.detail', $sp2d->id) }}" class="btn btn-sm btn-outline-secondary w-100 mb-1">
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

@push('css')
<style>
@keyframes shadow-pulse-danger {
  0% { box-shadow: 0 0 0 0px rgba(220, 53, 69, 0.4); }
  100% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
}
.pulse-danger { animation: shadow-pulse-danger 2s infinite; }
</style>
@endpush

@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#tabelVerifSp2d').DataTable({
                pageLength: 25,
                language: {
                    search: "Pencarian:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ arsip",
                    paginate: { previous: "Terdahulu", next: "Berikut" },
                    emptyTable: "Data verifikasi SP2D kosong dalam rak ini.",
                }
            });
        });
    </script>
@endpush
