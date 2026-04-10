@extends('layouts.app')
@section('title', 'Verifikasi SPP — Kasubbag')
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Antrean Verifikasi SPP" subtitle="Kepala Subbagian Keuangan dan Tata Usaha" />

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

    <div class="row row-cols-1 row-cols-md-4 g-3 mb-4 mt-2">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-warning text-dark">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Menunggu Verifikasi Saya</h6>
                    <h3 class="fw-bold mb-0">{{ $countPending ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-info text-white">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Sudah Saya Setujui</h6>
                    <h3 class="fw-bold mb-0">{{ $countApprovedMe ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-danger text-white">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Perlu Revisi</h6>
                    <h3 class="fw-bold mb-0">{{ $countRevisi ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #20c997;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Selesai Diverifikasi (Final)</h6>
                    <h3 class="fw-bold mb-0">{{ $countSelesai ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tab -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <form action="{{ route('verifikasi-kasubag.spp.index') }}" method="GET" class="d-flex align-items-center gap-3">
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

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Daftar Antrean SPP</h6>
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No. SPP / Tanggal</th>
                            <th>No. Tagihan / Dasar Kontrak</th>
                            <th>Vendor / Pekerjaan</th>
                            <th class="text-end">Nilai SPP (Rp)</th>
                            <th class="text-center">Status PPK</th>
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
                                <span class="text-muted d-block small">Tagihan: {{ $spp->tagihan->nomor_tagihan ?? '-' }}</span>
                                <span class="fw-bold">{{ $spp->tagihan->detailKontrak->kontrakTermin->kontrak->nomor_kontrak ?? '-' }}</span>
                            </td>
                            <td>
                                <span class="d-block fw-bold">{{ $spp->tagihan->pihak->nama_pihak ?? '-' }}</span>
                                <small class="text-muted d-block text-truncate" style="max-width: 200px;" title="{{ $spp->tagihan->detailKontrak->kontrakTermin->uraian_termin ?? '-' }}">
                                    {{ $spp->tagihan->detailKontrak->kontrakTermin->uraian_termin ?? '-' }}
                                </small>
                            </td>
                            <td class="text-end text-success fw-bold">
                                Rp {{ number_format($spp->nominal_spp, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if($spp->ppkApprovalStatus === 'APPROVED')
                                    <span class="badge bg-success border border-success"><i class="bi bi-check-circle"></i> Disetujui</span>
                                @elseif($spp->ppkApprovalStatus === 'REVISION')
                                    <span class="badge bg-danger border border-danger"><i class="bi bi-x-circle"></i> Revisi</span>
                                @else
                                    <span class="badge bg-warning text-dark border border-warning"><i class="bi bi-hourglass-split"></i> Pending</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($spp->kasubbagApprovalStatus === 'APPROVED')
                                    <span class="badge bg-success border border-success"><i class="bi bi-check-circle"></i> Disetujui</span>
                                @elseif($spp->kasubbagApprovalStatus === 'REVISION')
                                    <span class="badge bg-danger border border-danger"><i class="bi bi-x-circle"></i> Revisi</span>
                                @else
                                    <span class="badge bg-warning text-dark border border-warning"><i class="bi bi-hourglass-split"></i> Pending</span>
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
                                <a href="{{ route('verifikasi-kasubag.spp.show', $spp->id) }}" class="btn btn-sm btn-primary">
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
    <script>$(document).ready(function() { $('#example').DataTable({ "order": [] }); });</script>
@endpush
