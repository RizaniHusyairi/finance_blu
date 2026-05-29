@extends('layouts.app')
@section('title', 'Verifikasi NPI Perjaldin')

@push('css')
    @include('verifikasi_npi._styles')
@endpush

@section('content')
@php
    $totalQueue = $summary['pending'] + $summary['approved'] + $summary['revisi'] + $summary['selesai'];
@endphp

{{-- ====== HERO ====== --}}
<div class="card vnpi-hero mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div class="npi-min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="hero-tag"><i class='bx bx-trip me-1'></i> Verifikasi NPI Perjalanan Dinas</span>
                    @if($summary['pending'] > 0)
                        <span class="vnpi-pill s-alert"><i class="bx bx-bell"></i> {{ $summary['pending'] }} Menunggu Anda</span>
                    @endif
                </div>
                <h3 class="fw-bold mb-1">Meja Verifikasi NPI</h3>
                <div class="hero-sub small d-flex flex-wrap align-items-center gap-2">
                    <span><i class='bx bx-id-card me-1'></i> Peran Anda:</span>
                    @forelse(($roleCodes ?? [$roleCode]) as $rc)
                        <span class="hero-tag">{{ $rc }}</span>
                    @empty
                        <span class="hero-tag">-</span>
                    @endforelse
                </div>
            </div>
            <div class="hero-meta p-3 text-center" style="min-width: 150px;">
                <div class="field-label">Total Dokumen</div>
                <div class="nominal-hero">{{ $totalQueue }}</div>
                <div class="field-value small">NPI Perjaldin</div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
        <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('warning'))
    <div class="alert alert-warning border-0 shadow-sm alert-dismissible fade show">
        <i class="bx bx-error me-1"></i> {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
        <i class="bx bx-error-circle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ====== STAT TILES ====== --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="vnpi-stat t-amber">
            <div class="d-flex align-items-center justify-content-between">
                <div><div class="stat-num">{{ $summary['pending'] }}</div><div class="stat-label mt-1">Menunggu Verifikasi Saya</div></div>
                <span class="stat-ico"><i class="bx bx-hourglass"></i></span>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="vnpi-stat t-green">
            <div class="d-flex align-items-center justify-content-between">
                <div><div class="stat-num">{{ $summary['approved'] }}</div><div class="stat-label mt-1">Sudah Saya Setujui</div></div>
                <span class="stat-ico"><i class="bx bx-check-double"></i></span>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="vnpi-stat t-rose">
            <div class="d-flex align-items-center justify-content-between">
                <div><div class="stat-num">{{ $summary['revisi'] }}</div><div class="stat-label mt-1">Perlu Revisi</div></div>
                <span class="stat-ico"><i class="bx bx-undo"></i></span>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="vnpi-stat t-blue">
            <div class="d-flex align-items-center justify-content-between">
                <div><div class="stat-num">{{ $summary['selesai'] }}</div><div class="stat-label mt-1">NPI Final / Selesai</div></div>
                <span class="stat-ico"><i class="bx bx-medal"></i></span>
            </div>
        </div>
    </div>
</div>

{{-- ====== FILTER ====== --}}
<div class="vnpi-card mb-4">
    <div class="vnpi-card-body pt-3">
        <form method="GET" action="{{ route('verifikasi-npi.perjaldin.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="field-label">Status Saya</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="semua" {{ $statusFilter === 'semua' ? 'selected' : '' }}>Semua Status</option>
                    <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Menunggu Aksi Saya</option>
                    <option value="approved" {{ $statusFilter === 'approved' ? 'selected' : '' }}>Telah Saya Setujui</option>
                    <option value="revisi" {{ $statusFilter === 'revisi' ? 'selected' : '' }}>Revisi</option>
                    <option value="selesai" {{ $statusFilter === 'selesai' ? 'selected' : '' }}>Selesai / Final</option>
                </select>
            </div>
            <div class="col-md-7">
                <label class="field-label">Pencarian</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bx bx-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Nomor NPI, SPM, SPP, Tagihan, deskripsi..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bx bx-filter-alt"></i> Filter</button>
                <a href="{{ route('verifikasi-npi.perjaldin.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-refresh"></i></a>
            </div>
        </form>
    </div>
</div>

{{-- ====== TABLE ====== --}}
<div class="vnpi-card">
    <div class="vnpi-card-head">
        <span class="ico-wrap" style="--card-accent:#2563eb;--card-tint:rgba(37,99,235,.12);"><i class="bx bx-list-check"></i></span>
        <div><h6>Antrean Dokumen NPI Perjaldin</h6><span class="head-sub">{{ $viewNpis->count() }} dokumen ditampilkan</span></div>
    </div>
    <div class="vnpi-card-body px-0 pb-0">
        <div class="table-responsive">
            <table class="table vnpi-list align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width: 40px;">#</th>
                        <th>Nomor NPI</th>
                        <th>Dokumen Sumber</th>
                        <th>Uraian Tagihan</th>
                        <th class="text-end">Nilai NPI</th>
                        <th class="text-center">Status Saya</th>
                        <th class="text-center">Status Final</th>
                        <th class="text-center pe-3" style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($viewNpis as $idx => $npi)
                        @php
                            $ms = $npi->myApprovalStatus;
                            $msClass = $ms === 'APPROVED' ? 'bg-success' : ($ms === 'PENDING' ? 'bg-warning text-dark' : (in_array($ms, ['REVISION','REJECTED']) ? 'bg-danger' : 'bg-light text-dark border'));
                            $sf = $npi->statusFinal;
                            $sfClass = $sf === 'Selesai' ? 'bg-success' : ($sf === 'Perlu Revisi' ? 'bg-danger' : 'bg-info');
                        @endphp
                        <tr class="{{ $npi->canAct ? 'row-actionable' : '' }}">
                            <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                            <td>
                                <span class="fw-bold text-primary">{{ $npi->nomor_npi ?? 'Draft' }}</span>
                                <div class="text-muted small">{{ optional($npi->tanggal_npi)->format('d M Y') ?? '-' }}</div>
                            </td>
                            <td>
                                <div class="small lh-sm">
                                    <span class="text-muted">SPM:</span> <span class="fw-semibold">{{ $npi->spmModel?->nomor_spm ?? '-' }}</span><br>
                                    <span class="text-muted">SPP:</span> {{ $npi->sppModel?->nomor_spp ?? '-' }}<br>
                                    <span class="text-muted">Tagihan:</span> {{ $npi->tagihanModel?->nomor_tagihan ?? '-' }}
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-truncate" style="max-width: 230px;" title="{{ $npi->tagihanModel?->deskripsi }}">
                                    {{ \Illuminate\Support\Str::limit($npi->tagihanModel?->deskripsi ?? '-', 55) }}
                                </div>
                                <div class="text-muted small text-truncate" style="max-width: 230px;">
                                    <i class="bx bx-user-check"></i> {{ $npi->bendaharaPenerimaan?->name ?? 'Belum ada' }}
                                </div>
                            </td>
                            <td class="text-end fw-bold">Rp {{ number_format($npi->nominal, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <span class="badge {{ $msClass }}">{{ $ms ?? 'N/A' }}</span>
                                @if(!empty($npi->myRoles) && count($npi->myRoles) > 1)
                                    <div class="mt-1 d-flex flex-wrap gap-1 justify-content-center">
                                        @foreach($npi->myRoles as $r)
                                            <span class="badge bg-light text-dark border" style="font-size: 9px;">{{ $r }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $sfClass }}" style="font-size: 11px;">{{ str_replace('_', ' ', $sf) }}</span>
                            </td>
                            <td class="text-center pe-3">
                                <a href="{{ route('verifikasi-npi.perjaldin.detail', $npi->id) }}" class="btn btn-sm {{ $npi->canAct ? 'btn-primary' : 'btn-outline-secondary' }} px-3 rounded-pill">
                                    <i class="bx {{ $npi->canAct ? 'bx-task' : 'bx-show' }}"></i> {{ $npi->canAct ? 'Proses' : 'Detail' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bx bx-inbox" style="font-size: 48px; opacity: 0.3;"></i>
                                <div class="mt-2">Tidak ada NPI Perjaldin yang memenuhi filter.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
