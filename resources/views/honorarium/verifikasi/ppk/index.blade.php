@extends('layouts.app')
@section('title', 'Verifikasi Honorarium — ' . $currentRole)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
    <div>
        <h5 class="fw-bold text-primary mb-0">Verifikasi Honorarium</h5>
        <p class="text-muted mb-0">{{ $currentRole }}</p>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-hourglass-top text-warning fs-4"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold">{{ $summary['pending'] }}</h3>
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
                        <i class="bi bi-check-circle text-success fs-4"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold">{{ $summary['approved'] }}</h3>
                        <small class="text-muted">Sudah Disetujui</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-arrow-counterclockwise text-danger fs-4"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold">{{ $summary['revision'] }}</h3>
                        <small class="text-muted">Perlu Revisi</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-patch-check text-primary fs-4"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold">{{ $summary['selesai'] }}</h3>
                        <small class="text-muted">Selesai Diverifikasi</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route($routePrefix . '.index') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Status PPK</label>
                <select name="status_ppk" class="form-select form-select-sm">
                    <option value="semua" {{ $filterPpk === 'semua' ? 'selected' : '' }}>Semua</option>
                    <option value="pending" {{ $filterPpk === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ $filterPpk === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="revision" {{ $filterPpk === 'revision' ? 'selected' : '' }}>Revisi</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Status Bendahara</label>
                <select name="status_bendahara" class="form-select form-select-sm">
                    <option value="semua" {{ $filterBendahara === 'semua' ? 'selected' : '' }}>Semua</option>
                    <option value="pending" {{ $filterBendahara === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ $filterBendahara === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="revision" {{ $filterBendahara === 'revision' ? 'selected' : '' }}>Revisi</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold mb-1">Pencarian</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Nomor Tagihan, Uraian, dll..." value="{{ $search }}">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-search"></i> Filter</button>
                <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>
</div>

{{-- Tabel Antrean --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 40px;">No</th>
                        <th>Nomor Tagihan</th>
                        <th>Uraian Kegiatan</th>
                        <th class="text-end">Total Netto</th>
                        <th class="text-center">PPK</th>
                        <th class="text-center">Bendahara</th>
                        <th class="text-center">Status Final</th>
                        <th class="text-center" style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tagihanList as $idx => $tagihan)
                        <tr>
                            <td class="text-center text-muted">{{ $idx + 1 }}</td>
                            <td>
                                <span class="fw-bold text-primary">{{ $tagihan->nomor_tagihan }}</span>
                                <div class="text-muted" style="font-size: 11px;">{{ $tagihan->created_at->format('d M Y') }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-truncate" style="max-width: 250px;" title="{{ $tagihan->deskripsi }}">{{ $tagihan->deskripsi }}</div>
                                <div class="text-muted" style="font-size: 11px;">{{ $tagihan->detailHonorarium->count() }} Penerima</div>
                            </td>
                            <td class="text-end fw-bold">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @php($ps = $tagihan->_ppkApproval?->status)
                                <span class="badge {{ $ps === 'APPROVED' ? 'bg-success' : ($ps === 'PENDING' ? 'bg-warning text-dark' : (in_array($ps, ['REVISION', 'REJECTED']) ? 'bg-danger' : 'bg-light text-dark border')) }}">{{ $ps ?? 'N/A' }}</span>
                            </td>
                            <td class="text-center">
                                @php($bs = $tagihan->_bendaharaApproval?->status)
                                <span class="badge {{ $bs === 'APPROVED' ? 'bg-success' : ($bs === 'PENDING' ? 'bg-warning text-dark' : (in_array($bs, ['REVISION', 'REJECTED']) ? 'bg-danger' : 'bg-light text-dark border')) }}">{{ $bs ?? 'N/A' }}</span>
                            </td>
                            <td class="text-center">
                                @php($sf = $tagihan->_statusFinal)
                                <span class="badge {{ $sf === 'Selesai Diverifikasi' ? 'bg-success' : (str_contains($sf, 'Revisi') ? 'bg-danger' : 'bg-info text-dark') }}" style="font-size: 11px;">{{ $sf }}</span>
                            </td>
                            <td class="text-center">
                                @if(($tagihan->_currentApproval ?? null)?->status === 'PENDING')
                                    <a href="{{ route($routePrefix . '.show', $tagihan->id) }}" class="btn btn-sm btn-primary px-3">
                                        <i class="bi bi-ui-checks"></i> Verifikasi
                                    </a>
                                @else
                                    <a href="{{ route($routePrefix . '.show', $tagihan->id) }}" class="btn btn-sm btn-outline-secondary px-3">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 text-muted opacity-50"></i>
                                <div class="mt-2">Tidak ada Tagihan Honorarium yang memenuhi filter.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
