@extends('layouts.app')
@section('title', 'Pencatatan SP2D Honorarium')

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
        <x-page-title title="Pencatatan SP2D" subtitle="Honorarium" />
    </div>
    <div class="text-muted small mb-4">Dasbor rekapitulasi pembuatan dan penerbitan SP2D Honorarium atas NPI yang telah disetujui_final.</div>

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
    <div class="row row-cols-2 row-cols-md-5 g-3 mb-4 mt-2">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-dark bg-info bg-opacity-25 border border-info">
                <div class="card-body p-3">
                    <h6 class="card-title fw-semibold mb-1"><i class="bi bi-inbox text-info me-1"></i> Siap Dicatat</h6>
                    <h3 class="fw-bold mb-0 text-info">{{ $summary['siap_dibuat'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-dark bg-warning bg-opacity-25 border border-warning">
                <div class="card-body p-3">
                    <h6 class="card-title fw-semibold mb-1"><i class="bi bi-pencil-square text-warning me-1"></i> Mode Draf</h6>
                    <h3 class="fw-bold mb-0 text-warning">{{ $summary['draft'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white bg-primary">
                <div class="card-body p-3">
                    <h6 class="card-title fw-semibold mb-1"><i class="bi bi-hourglass-split me-1"></i> Menunggu Verifikasi</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $summary['menunggu'] }}</h3>
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
            <div class="card h-100 border-0 shadow-sm text-white bg-success">
                <div class="card-body p-3">
                    <h6 class="card-title fw-semibold mb-1"><i class="bi bi-check2-all me-1"></i> Selesai Dicetak</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $summary['selesai'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-bar mb-4 d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('sp2ds.honor.index', ['status' => 'semua', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'semua' ? 'active' : '' }}">Semua Berkas</a>
            <a href="{{ route('sp2ds.honor.index', ['status' => 'siap_dibuat', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'siap_dibuat' ? 'active' : '' }}">Siap Dicatat</a>
            <a href="{{ route('sp2ds.honor.index', ['status' => 'draft', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'draft' ? 'active' : '' }}">Draf</a>
            <a href="{{ route('sp2ds.honor.index', ['status' => 'menunggu', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'menunggu' ? 'active' : '' }}">Verifikasi</a>
            <a href="{{ route('sp2ds.honor.index', ['status' => 'revisi', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'revisi' ? 'active' : '' }}">Revisi</a>
            <a href="{{ route('sp2ds.honor.index', ['status' => 'selesai', 'search' => $search]) }}" class="status-btn {{ $statusFilter === 'selesai' ? 'active' : '' }}">Selesai</a>
        </div>
        <form action="{{ route('sp2ds.honor.index') }}" method="GET" class="d-flex gap-2" style="min-width: 250px;">
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari NPI/SPP/Kegiatan..." value="{{ $search }}">
            @if($search) <a href="{{ route('sp2ds.honor.index', ['status' => $statusFilter]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a> @endif
            <button type="submit" class="btn btn-sm btn-dark"><i class="bi bi-search"></i></button>
        </form>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelSp2d" class="table table-hover table-bordered align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th width="3%">No</th>
                            <th>No. NPI Terbit/Final</th>
                            <th width="30%">Referensi Kegiatan</th>
                            <th>Distribusi Penerima</th>
                            <th class="text-end">Nilai Berkas</th>
                            <th class="text-center">Kesiapan SP2D</th>
                            <th class="text-center">Aksi B.Pengeluaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($viewNpis as $idx => $npi)
                            @php
                                $statusClass = match($npi->status_sp2d) {
                                    'SIAP_DIBUAT' => 'bg-info text-dark',
                                    \App\Models\DokumenSp2d::STATUS_DRAFT => 'bg-warning text-dark',
                                    \App\Models\DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI => 'bg-primary',
                                    \App\Models\DokumenSp2d::STATUS_REVISI => 'bg-danger',
                                    \App\Models\DokumenSp2d::STATUS_DISETUJUI_FINAL, \App\Models\DokumenSp2d::STATUS_EXECUTED => 'bg-success',
                                    default => 'bg-secondary',
                                };
                            @endphp
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>
                                    <div class="fw-bold text-primary mb-1">{{ $npi->nomor_npi ?? '-' }}</div>
                                    <div class="text-muted small"><i class="bi bi-calendar3"></i> {{ optional($npi->tanggal_npi)->format('d-M-Y') ?? '-' }}</div>
                                    @if($npi->status_sp2d !== 'SIAP_DIBUAT' && $npi->sp2d)
                                        <hr class="my-1 border-secondary">
                                        <div class="text-dark fw-bold small">No.SP2D: <span class="text-success">{{ $npi->sp2d->nomor_sp2d ?? 'DRAFT' }}</span></div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-medium text-wrap">{{ Str::limit($npi->tagihanModel?->deskripsi ?? '-', 65) }}</div>
                                    <div class="small mt-1 text-muted"><span class="badge border bg-light text-dark fw-normal">SPM: {{ $npi->spmModel?->nomor_spm ?? '-' }}</span></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $npi->bendaharaPenerimaan?->name ?? 'Belum ada' }}</div>
                                    <div class="small fw-medium mt-1"><i class="bi bi-people text-secondary"></i> {{ collect($npi->tagihanModel?->detailHonorarium)->count() }} Personel Tuju</div>
                                </td>
                                <td class="text-end fw-bold text-success fs-6">
                                    Rp {{ number_format($npi->nilai_npi, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $statusClass }} w-100 py-2 shadow-sm" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                        {{ str_replace('_', ' ', $npi->status_sp2d) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($npi->status_sp2d === 'SIAP_DIBUAT')
                                        <a href="{{ route('sp2ds.honor.detail', $npi->id) }}" class="btn btn-sm btn-primary w-100 shadow-sm mb-1">
                                            <i class="bi bi-plus-circle me-1"></i> Buat SP2D
                                        </a>
                                    @elseif(in_array($npi->status_sp2d, [\App\Models\DokumenSp2d::STATUS_DRAFT, \App\Models\DokumenSp2d::STATUS_REVISI]))
                                        <a href="{{ route('sp2ds.honor.detail', $npi->id) }}" class="btn btn-sm btn-warning w-100 shadow-sm mb-1 fw-bold">
                                            <i class="bi bi-pencil-square me-1"></i> Lanjut Draf
                                        </a>
                                    @else
                                        <a href="{{ route('sp2ds.honor.detail', $npi->id) }}" class="btn btn-sm btn-outline-secondary w-100 mb-1">
                                            <i class="bi bi-eye me-1"></i> Cek Dokumen
                                        </a>
                                        @if(in_array($npi->status_sp2d, [\App\Models\DokumenSp2d::STATUS_DISETUJUI_FINAL, \App\Models\DokumenSp2d::STATUS_EXECUTED]))
                                            <button class="btn btn-sm btn-danger w-100 opacity-50" disabled><i class="bi bi-printer"></i> Cetak</button>
                                        @endif
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
            $('#tabelSp2d').DataTable({
                pageLength: 25,
                language: {
                    search: "Pencarian:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ antrean",
                    paginate: { previous: "Belakang", next: "Lanjut" },
                    emptyTable: "Belum ada antrean pembuatan/status SP2D saat ini.",
                }
            });
        });
    </script>
@endpush
