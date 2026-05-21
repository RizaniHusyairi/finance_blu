@extends('layouts.app')
@section('title', 'Pencatatan SP2D Kontrak')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
    <div>
        <h5 class="fw-bold text-primary mb-0">Pencatatan SP2D</h5>
        <p class="text-muted mb-0">Kontrak — Bendahara Pengeluaran</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show mb-3">
        <div class="d-flex align-items-center gap-2">
            <i class="material-icons-outlined">check_circle</i>
            <div>{{ session('success') }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show mb-3">
        <div class="d-flex align-items-center gap-2">
            <i class="material-icons-outlined">error</i>
            <div>{{ session('error') }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="material-icons-outlined text-warning" style="font-size: 24px;">note_add</i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold">{{ $summary['belum_dibuat'] }}</h3>
                        <small class="text-muted">Belum Ada SP2D</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="material-icons-outlined text-secondary" style="font-size: 24px;">draw</i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold">{{ $summary['draft_revisi'] }}</h3>
                        <small class="text-muted">Draft / Revisi</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="material-icons-outlined text-info" style="font-size: 24px;">hourglass_top</i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold">{{ $summary['menunggu'] }}</h3>
                        <small class="text-muted">Menunggu Verifikasi</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="material-icons-outlined text-success" style="font-size: 24px;">verified</i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold">{{ $summary['selesai'] }}</h3>
                        <small class="text-muted">Selesai</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('sp2ds.kontrak.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Status SP2D</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="semua" {{ $statusFilter === 'semua' ? 'selected' : '' }}>Semua</option>
                    <option value="belum_dibuat" {{ $statusFilter === 'belum_dibuat' ? 'selected' : '' }}>Belum Dibuat</option>
                    <option value="draft" {{ $statusFilter === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="revisi" {{ $statusFilter === 'revisi' ? 'selected' : '' }}>Revisi</option>
                    <option value="menunggu" {{ $statusFilter === 'menunggu' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                    <option value="selesai" {{ $statusFilter === 'selesai' ? 'selected' : '' }}>Disetujui Final / Selesai</option>
                </select>
            </div>
            <div class="col-md-7">
                <label class="form-label small fw-semibold mb-1">Pencarian</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Nomor NPI, SP2D, SPM, SPP, vendor..." value="{{ $search }}">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="material-icons-outlined" style="font-size:16px; vertical-align: middle;">search</i> Filter</button>
                <a href="{{ route('sp2ds.kontrak.index') }}" class="btn btn-outline-secondary btn-sm"><i class="material-icons-outlined" style="font-size:16px; vertical-align: middle;">refresh</i></a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:40px;">No</th>
                        <th>Nomor SP2D</th>
                        <th>NPI / SPM / SPK</th>
                        <th>Vendor / Pekerjaan</th>
                        <th class="text-end">Nilai SP2D</th>
                        <th class="text-center">Status SP2D</th>
                        <th class="text-center">Status Verifikasi</th>
                        <th class="text-center" style="width:120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($listSp2d as $idx => $item)
                        <tr>
                            <td class="text-center text-muted">{{ $idx + 1 }}</td>
                            <td>
                                @if($item->nomor_sp2d)
                                    <span class="fw-bold text-primary">{{ $item->nomor_sp2d }}</span>
                                    <div class="text-muted" style="font-size:11px;">{{ \Carbon\Carbon::parse($item->tanggal_sp2d)->format('d M Y') }}</div>
                                @else
                                    <span class="text-muted fst-italic">Belum Dibuat</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-size: 12px; line-height: 1.6;">
                                    <span class="text-muted">NPI:</span> <span class="fw-semibold">{{ $item->nomor_npi ?? '-' }}</span><br>
                                    <span class="text-muted">SPM:</span> {{ $item->nomor_spm ?? '-' }}<br>
                                    <span class="text-muted">SPK:</span> {{ $item->nomor_spk ?? '-' }}
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-truncate" style="max-width: 180px;">{{ $item->nama_vendor ?? '-' }}</div>
                                <div class="text-muted text-truncate" style="max-width: 180px; font-size: 12px;">{{ $item->nama_pekerjaan ?? '-' }}</div>
                            </td>
                            <td class="text-end fw-bold">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <span class="badge {{ $item->status_class }}">{{ $item->status_badge }}</span>
                            </td>
                            <td class="text-center" style="font-size: 11px;">
                                @if(in_array($item->status_badge, ['Belum Dibuat', 'Draft']))
                                    <span class="text-muted">-</span>
                                @else
                                    <div><span class="text-muted">PPK:</span> 
                                        @if($item->ppk_status == 'APPROVED') <span class="text-success"><i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">check</i></span>
                                        @elseif($item->ppk_status == 'PENDING') <span class="text-warning"><i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">hourglass_empty</i></span>
                                        @elseif(in_array($item->ppk_status, ['REVISION', 'REJECTED'])) <span class="text-danger"><i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">close</i></span>
                                        @else {{ $item->ppk_status }}
                                        @endif
                                    </div>
                                    <div><span class="text-muted">KSB:</span>
                                        @if($item->kasubbag_status == 'APPROVED') <span class="text-success"><i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">check</i></span>
                                        @elseif($item->kasubbag_status == 'PENDING') <span class="text-warning"><i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">hourglass_empty</i></span>
                                        @elseif(in_array($item->kasubbag_status, ['REVISION', 'REJECTED'])) <span class="text-danger"><i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">close</i></span>
                                        @else {{ $item->kasubbag_status }}
                                        @endif
                                    </div>
                                    <div><span class="text-muted">PPSPM:</span>
                                        @if($item->ppspm_status == 'APPROVED') <span class="text-success"><i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">check</i></span>
                                        @elseif($item->ppspm_status == 'PENDING') <span class="text-warning"><i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">hourglass_empty</i></span>
                                        @elseif(in_array($item->ppspm_status, ['REVISION', 'REJECTED'])) <span class="text-danger"><i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">close</i></span>
                                        @else {{ $item->ppspm_status }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($item->status_badge === 'Belum Dibuat')
                                    <a href="{{ route('sp2ds.kontrak.detail', $item->npi_id) }}" class="btn btn-sm btn-primary px-3">
                                        Buat SP2D
                                    </a>
                                @elseif($item->is_draft)
                                    <a href="{{ route('sp2ds.kontrak.detail', $item->npi_id) }}" class="btn btn-sm btn-warning px-3">
                                        Kelola Draft
                                    </a>
                                @else
                                    <a href="{{ route('sp2ds.kontrak.detail', $item->npi_id) }}" class="btn btn-sm btn-outline-secondary px-3">
                                        Lihat Detail
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="material-icons-outlined" style="font-size: 48px; opacity: 0.3;">inbox</i>
                                <div class="mt-2">Tidak ada SP2D Kontrak yang memenuhi filter.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
