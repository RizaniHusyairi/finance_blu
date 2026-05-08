@extends('layouts.app')
@section('title')
    Verifikasi SPM Kontrak
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Verifikasi SPM" subtitle="Kontrak" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning border-0 bg-warning alert-dismissible fade show">
            <div class="text-dark">{{ session('warning') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show">
            <div class="text-white">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4 mt-2">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #dc3545;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Menunggu Verifikasi Saya</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $countPending ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-white text-dark">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal text-muted mb-1">Sudah Saya Setujui</h6>
                    <h3 class="fw-bold mb-0">{{ $countApprovedMe ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-white text-dark">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal text-muted mb-1">Perlu Revisi</h6>
                    <h3 class="fw-bold mb-0">{{ $countRevisi ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #198754;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Selesai Diverifikasi</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $countSelesai ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route(($routePrefix ?? 'verifikasi-ppspm.spm.kontrak') . '.index') }}" method="GET" class="row g-3 align-items-center">
                <div class="col-md-3">
                    <label class="form-label mb-0 small text-muted">Status Saya ({{ $currentRole ?? 'PPSPM' }})</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="Semua" {{ request('status') == 'Semua' ? 'selected' : '' }}>Semua Status</option>
                        <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                        <option value="Approved" {{ request('status') == 'Approved' ? 'selected' : '' }}>Approved</option>
                        <option value="Revisi" {{ request('status') == 'Revisi' ? 'selected' : '' }}>Revisi</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-0 small text-muted">Status Kasubbag</label>
                    <select name="status_kasubbag" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="Semua" {{ request('status_kasubbag') == 'Semua' ? 'selected' : '' }}>Semua Status</option>
                        <option value="Pending" {{ request('status_kasubbag') == 'Pending' ? 'selected' : '' }}>Pending</option>
                        <option value="Approved" {{ request('status_kasubbag') == 'Approved' ? 'selected' : '' }}>Approved</option>
                        <option value="Revisi" {{ request('status_kasubbag') == 'Revisi' ? 'selected' : '' }}>Revisi</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-0 small text-muted">Pencarian</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="No SPM, SPK, Vendor, Pekerjaan..." value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Cari</button>
                    </div>
                </div>
                <div class="col-md-2 text-end">
                    <label class="form-label mb-0 small d-block">&nbsp;</label>
                    <a href="{{ route(($routePrefix ?? 'verifikasi-ppspm.spm.kontrak') . '.index') }}" class="btn btn-sm btn-light border">Reset Filter</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Antrean Verifikasi SPM Kontrak</h6>
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nomor SPM</th>
                            <th>SPP / Tagihan / SPK</th>
                            <th>Vendor / Pekerjaan</th>
                            <th>Nilai SPM</th>
                            <th>Status PPSPM</th>
                            <th>Status Kasubbag</th>
                            <th>Status Koordinator</th>
                            <th>Status Final</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($viewSpms as $idx => $spm)
                            @php
                                $tagihan = $spm->spp?->tagihan;
                                $kontrak = $tagihan?->detailKontrak?->kontrakTermin?->kontrak;
                            @endphp
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                @if($spm->nomor_spm)
                                    <strong class="text-primary">{{ $spm->nomor_spm }}</strong>
                                @else
                                    <span class="badge bg-secondary">Draft / Belum Bernomor</span>
                                @endif
                                <br>
                                <small class="text-muted">{{ \Carbon\Carbon::parse($spm->tanggal_spm)->isoFormat('D MMM Y') }}</small>
                            </td>
                            <td>
                                <div><small class="text-muted">SPP:</small> <br><strong>{{ $spm->spp?->nomor_spp ?? '-' }}</strong></div>
                                <div><small class="text-muted">Tagihan:</small> {{ $tagihan?->nomor_tagihan ?? '-' }}</div>
                                <div><small class="text-muted">SPK:</small> {{ $kontrak?->nomor_spk ?? '-' }}</div>
                            </td>
                            <td>
                                <strong class="text-danger">{{ $kontrak?->vendor?->nama_pihak ?? '-' }}</strong><br>
                                <small class="text-muted">{{ Str::limit($kontrak?->nama_pekerjaan ?? '-', 50) }}</small>
                            </td>
                            <td class="text-end text-success fw-bold">
                                Rp {{ number_format($spm->nominal_spm ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if($spm->ppspmApprovalStatus === 'PENDING')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($spm->ppspmApprovalStatus === 'APPROVED')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($spm->ppspmApprovalStatus === 'REVISION')
                                    <span class="badge bg-danger">Revisi</span>
                                @else
                                    <span class="badge bg-secondary">{{ $spm->ppspmApprovalStatus }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($spm->kasubbagApprovalStatus === 'PENDING')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($spm->kasubbagApprovalStatus === 'APPROVED')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($spm->kasubbagApprovalStatus === 'REVISION')
                                    <span class="badge bg-danger">Revisi</span>
                                @else
                                    <span class="badge bg-secondary">{{ $spm->kasubbagApprovalStatus }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($spm->koordinatorApprovalStatus === 'PENDING')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($spm->koordinatorApprovalStatus === 'APPROVED')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($spm->koordinatorApprovalStatus === 'REVISION')
                                    <span class="badge bg-danger">Revisi</span>
                                @else
                                    <span class="badge bg-secondary">{{ $spm->koordinatorApprovalStatus }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @php
                                    $fs = $spm->statusFinal;
                                    $badgeClass = 'bg-secondary';
                                    if(in_array($fs, ['Selesai Diverifikasi', 'APPROVED'])) $badgeClass = 'bg-success';
                                    if(in_array($fs, ['Menunggu Verifikasi', 'Menunggu PPSPM', 'Menunggu Kasubbag', 'Menunggu Koordinator'])) $badgeClass = 'bg-info text-dark';
                                    if(in_array($fs, ['Perlu Revisi', 'REVISION', 'REJECTED'])) $badgeClass = 'bg-danger';
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $fs }}</span>
                            </td>
                            <td class="text-center">
                                @if($spm->canAct)
                                    <a href="{{ route(($routePrefix ?? 'verifikasi-ppspm.spm.kontrak') . '.show', $spm->id) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-pencil-square"></i> Verifikasi
                                    </a>
                                @else
                                    <a href="{{ route(($routePrefix ?? 'verifikasi-ppspm.spm.kontrak') . '.show', $spm->id) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i> Lihat Detail
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">Tidak ada data untuk filter yang dipilih.</td>
                        </tr>
                        @endforelse
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
