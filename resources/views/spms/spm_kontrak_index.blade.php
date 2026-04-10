@extends('layouts.app')
@section('title', 'SPM Kontrak')

@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .spm-filter-bar { background: #f8f9fc; border: 1px solid rgba(15,23,42,.06); border-radius: .75rem; padding: 1rem 1.25rem; }
        .spm-status-btn { border: 1px solid #dee2e6; background: #fff; border-radius: .5rem; padding: .35rem .85rem; font-size: .82rem; font-weight: 600; color: #475569; transition: all .15s; cursor: pointer; }
        .spm-status-btn:hover { border-color: #0d6efd; color: #0d6efd; }
        .spm-status-btn.active { background: #0d6efd; border-color: #0d6efd; color: #fff; }
        .spm-verif-badge { display: inline-flex; align-items: center; gap: .25rem; font-size: .72rem; font-weight: 600; padding: .15rem .5rem; border-radius: .35rem; }
    </style>
@endpush

@section('content')
    <x-page-title title="Pembuatan SPM" subtitle="Kontrak" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4 mt-2">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-secondary text-white">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Belum Ada SPM</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $summary['belum_dibuat'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-warning text-dark">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Draft / Revisi</h6>
                    <h3 class="fw-bold mb-0">{{ $summary['draft_revisi'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #0dcaf0;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Menunggu Verifikasi</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $summary['menunggu'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #20c997;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Selesai</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $summary['selesai'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="spm-filter-bar mb-4 d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('spms.kontrak.index', ['status' => 'semua']) }}" class="spm-status-btn {{ $statusFilter === 'semua' ? 'active' : '' }}">Semua</a>
            <a href="{{ route('spms.kontrak.index', ['status' => 'belum_dibuat', 'search' => $search]) }}" class="spm-status-btn {{ $statusFilter === 'belum_dibuat' ? 'active' : '' }}">Belum Dibuat</a>
            <a href="{{ route('spms.kontrak.index', ['status' => 'draft', 'search' => $search]) }}" class="spm-status-btn {{ $statusFilter === 'draft' ? 'active' : '' }}">Draft</a>
            <a href="{{ route('spms.kontrak.index', ['status' => 'revisi', 'search' => $search]) }}" class="spm-status-btn {{ $statusFilter === 'revisi' ? 'active' : '' }}">Revisi</a>
            <a href="{{ route('spms.kontrak.index', ['status' => 'menunggu', 'search' => $search]) }}" class="spm-status-btn {{ $statusFilter === 'menunggu' ? 'active' : '' }}">Menunggu Verifikasi</a>
            <a href="{{ route('spms.kontrak.index', ['status' => 'selesai', 'search' => $search]) }}" class="spm-status-btn {{ $statusFilter === 'selesai' ? 'active' : '' }}">Disetujui Final</a>
        </div>
        <form action="{{ route('spms.kontrak.index') }}" method="GET" class="d-flex gap-2" style="min-width: 280px;">
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nomor SPM, SPP, tagihan, SPK, vendor..." value="{{ $search }}">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
        </form>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelSpmKontrak" class="table table-hover table-bordered align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th width="3%">No</th>
                            <th>Nomor SPM</th>
                            <th>SPP / Tagihan / SPK</th>
                            <th>Vendor / Pekerjaan</th>
                            <th class="text-end">Nilai SPM</th>
                            <th class="text-center">Status SPM</th>
                            <th class="text-center">Status Verifikasi</th>
                            <th class="text-center" width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sppList as $idx => $spp)
                            @php
                                $spm = $spp->spm;
                                $tagihan = $spp->tagihan;
                                $kontrak = $tagihan?->detailKontrak?->kontrakTermin?->kontrak;
                                $vendor = $kontrak?->vendor;

                                $statusLabel = $spm?->status ?? 'Belum Dibuat';
                                $statusClass = match($statusLabel) {
                                    'Belum Dibuat' => 'bg-secondary',
                                    'DRAFT' => 'bg-warning text-dark',
                                    'Revisi' => 'bg-danger',
                                    'Menunggu Verifikasi' => 'bg-info',
                                    'Disetujui Final' => 'bg-success',
                                    default => 'bg-secondary',
                                };

                                $wfInstance = collect($spm?->workflowInstances ?? [])->sortByDesc('created_at')->first();
                                $ppspmApproval = collect($wfInstance?->approvals ?? [])->firstWhere('role_code', 'PPSPM');
                                $kasubbagApproval = collect($wfInstance?->approvals ?? [])->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');
                            @endphp
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>
                                    @if($spm && $spm->nomor_spm)
                                        <strong class="text-primary">{{ $spm->nomor_spm }}</strong>
                                        <div class="text-muted small">{{ optional($spm->tanggal_spm)->format('d M Y') }}</div>
                                    @else
                                        <span class="text-muted fst-italic">Belum Dibuat</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold small">SPP: {{ $spp->nomor_spp ?? '-' }}</div>
                                    <div class="text-muted small">Tagihan: {{ $tagihan?->nomor_tagihan ?? '-' }}</div>
                                    <div class="text-muted small">SPK: {{ $kontrak?->nomor_spk ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $vendor?->nama_pihak ?? '-' }}</div>
                                    <div class="text-muted small">{{ Str::limit($kontrak?->nama_pekerjaan ?? $tagihan?->deskripsi, 50) }}</div>
                                </td>
                                <td class="text-end">
                                    <strong>Rp {{ number_format($spm?->nominal_spm ?? $spp->nominal_spp ?? $tagihan?->total_netto ?? 0, 0, ',', '.') }}</strong>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="text-center">
                                    @if($spm && $wfInstance)
                                        <div class="d-flex flex-column gap-1">
                                            @php
                                                $ppspmStatusClass = match($ppspmApproval?->status ?? '-') {
                                                    'APPROVED' => 'bg-success-subtle text-success',
                                                    'PENDING' => 'bg-warning-subtle text-warning',
                                                    'REVISION' => 'bg-danger-subtle text-danger',
                                                    default => 'bg-light text-muted',
                                                };
                                                $kasubbagStatusClass = match($kasubbagApproval?->status ?? '-') {
                                                    'APPROVED' => 'bg-success-subtle text-success',
                                                    'PENDING' => 'bg-warning-subtle text-warning',
                                                    'REVISION' => 'bg-danger-subtle text-danger',
                                                    default => 'bg-light text-muted',
                                                };
                                            @endphp
                                            <span class="spm-verif-badge {{ $ppspmStatusClass }}"><i class="bi bi-person-check"></i> PPSPM: {{ $ppspmApproval?->status ?? '-' }}</span>
                                            <span class="spm-verif-badge {{ $kasubbagStatusClass }}"><i class="bi bi-person-badge"></i> Kasubbag: {{ $kasubbagApproval?->status ?? '-' }}</span>
                                        </div>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(!$spm)
                                        <a href="{{ route('spms.kontrak.detail', $spp->id) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-plus-circle me-1"></i> Buat SPM
                                        </a>
                                    @elseif(in_array($spm->status, ['DRAFT', 'Revisi']))
                                        <a href="{{ route('spms.kontrak.detail', $spp->id) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil me-1"></i> Lanjutkan
                                        </a>
                                    @else
                                        <a href="{{ route('spms.kontrak.detail', $spp->id) }}" class="btn btn-sm btn-outline-primary">
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
            $('#tabelSpmKontrak').DataTable({
                pageLength: 25,
                order: [[0, 'asc']],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    paginate: { previous: "Sebelumnya", next: "Selanjutnya" },
                    emptyTable: "Tidak ada data SPM kontrak.",
                }
            });
        });
    </script>
@endpush
