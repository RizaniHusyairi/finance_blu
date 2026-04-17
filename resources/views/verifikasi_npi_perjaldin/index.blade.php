@extends('layouts.app')

@section('content')
<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h5 class="mb-1 text-primary fw-bold">Antrean Verifikasi NPI Perjaldin</h5>
        <p class="text-muted mb-0">Role Aktif Antrean: <span class="badge bg-dark fw-normal">{{ $roleCode }}</span></p>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-3">
        <div class="card radius-10 border-start border-0 border-4 border-warning shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div>
                        <p class="mb-0 text-secondary font-13">Menunggu Aksi Saya</p>
                        <h4 class="my-1 text-warning fw-bold">{{ $summary['pending'] }}</h4>
                    </div>
                    <div class="widgets-icons-2 rounded-circle bg-light-warning text-warning ms-auto"><i class='material-icons-outlined'>pending_actions</i></div>
                </div>
            </div>
        </div>
    </div>
    f
    <div class="col-12 col-md-3">
        <div class="card radius-10 border-start border-0 border-4 border-success shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div>
                        <p class="mb-0 text-secondary font-13">Telah Disetujui (Saya)</p>
                        <h4 class="my-1 text-success fw-bold">{{ $summary['approved'] }}</h4>
                    </div>
                    <div class="widgets-icons-2 rounded-circle bg-light-success text-success ms-auto"><i class='material-icons-outlined'>thumb_up</i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card radius-10 border-start border-0 border-4 border-danger shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div>
                        <p class="mb-0 text-secondary font-13">Perlu Bukti/Revisi</p>
                        <h4 class="my-1 text-danger fw-bold">{{ $summary['revisi'] }}</h4>
                    </div>
                    <div class="widgets-icons-2 rounded-circle bg-light-danger text-danger ms-auto"><i class='material-icons-outlined'>assignment_return</i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card radius-10 border-start border-0 border-4 border-primary shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div>
                        <p class="mb-0 text-secondary font-13">NPI Final (Selesai)</p>
                        <h4 class="my-1 text-primary fw-bold">{{ $summary['selesai'] }}</h4>
                    </div>
                    <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary ms-auto"><i class='material-icons-outlined'>done_all</i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card radius-10 shadow-sm border-0">
    <div class="card-header bg-transparent border-bottom px-4 py-3 d-flex flex-column flex-md-row align-items-center justify-content-between">
        <div class="mb-2 mb-md-0 fw-bold text-secondary text-uppercase font-14">
            <i class="material-icons-outlined" style="font-size: 16px; margin-bottom: -3px;">list_alt</i> Daftar Dokumen NPI
        </div>
        <div class="d-flex gap-2 w-100 w-md-auto">
            <form action="{{ route('verifikasi-npi.perjaldin.index') }}" method="GET" class="d-flex flex-grow-1 gap-2">
                <select name="status" class="form-select form-select-sm border-secondary" style="width: 160px;" onchange="this.form.submit()">
                    <option value="semua" {{ $statusFilter == 'semua' ? 'selected' : '' }}>Semua Status</option>
                    <option value="pending" {{ $statusFilter == 'pending' ? 'selected' : '' }}>Menunggu Aksi Saya</option>
                    <option value="approved" {{ $statusFilter == 'approved' ? 'selected' : '' }}>Telah Saya Setujui</option>
                    <option value="revisi" {{ $statusFilter == 'revisi' ? 'selected' : '' }}>Revisi</option>
                    <option value="selesai" {{ $statusFilter == 'selesai' ? 'selected' : '' }}>Selesai / Final</option>
                </select>
                <div class="input-group input-group-sm flex-grow-1 border-secondary border rounded">
                    <span class="input-group-text bg-transparent border-0"><i class="material-icons-outlined" style="font-size: 16px;">search</i></span>
                    <input type="text" name="search" class="form-control border-0 px-1" placeholder="Cari Dokumen..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary border-0" type="submit">Cari</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">No. NPI</th>
                        <th>Tanggal NPI</th>
                        <th>Dokumen SPP/SPM</th>
                        <th>Uraian Tagihan</th>
                        <th class="text-end">Nilai (Rp)</th>
                        <th>Status Antrean (Saat Ini)</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($viewNpis as $npi)
                    <tr class="{{ $npi->canAct ? 'table-warning border-warning' : '' }}">
                        <td class="ps-4">
                            <span class="fw-bold text-dark">{{ $npi->nomor_npi }}</span><br>
                            @if($npi->statusFinal == 'Perlu Revisi')
                                <span class="badge bg-danger">Revisi</span>
                            @elseif($npi->statusFinal == 'Selesai' || $npi->status == \App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL)
                                <span class="badge bg-success">Disetujui Final</span>
                            @else
                                <span class="badge bg-info text-dark font-11">{{ $npi->statusFinal }}</span>
                            @endif
                        </td>
                        <td>{{ $npi->tanggal_npi?->format('d M Y') ?? '-' }}</td>
                        <td>
                            <div class="font-12 mb-1">
                                <span class="text-muted">SPM:</span> <span class="fw-bold">{{ $npi->spmModel?->nomor_spm ?? '-' }}</span>
                            </div>
                            <div class="font-12">
                                <span class="text-muted">SPP:</span> <span>{{ $npi->sppModel?->nomor_spp ?? '-' }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="text-wrap" style="max-width: 200px; font-size: 0.85rem;">
                                {{ Str::limit($npi->tagihanModel?->deskripsi ?? '-', 60) }}
                            </div>
                        </td>
                        <td class="text-end fw-bold font-14">
                            {{ number_format($npi->nominal, 0, ',', '.') }}
                        </td>
                        <td>
                            @php
                                $statusBadge = 'bg-secondary';
                                if($npi->myApprovalStatus == 'PENDING') $statusBadge = 'bg-warning text-dark';
                                if($npi->myApprovalStatus == 'APPROVED') $statusBadge = 'bg-success';
                                if($npi->myApprovalStatus == 'REVISION') $statusBadge = 'bg-danger';
                            @endphp
                            <div class="d-flex align-items-center">
                                @if($npi->canAct)
                                    <span class="spinner-grow spinner-grow-sm text-warning me-2" role="status" aria-hidden="true"></span>
                                @endif
                                <span class="badge {{ $statusBadge }} px-2 py-1">
                                    {{ $npi->myApprovalStatus }}
                                </span>
                            </div>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('verifikasi-npi.perjaldin.detail', $npi->id) }}" class="btn {{ $npi->canAct ? 'btn-primary' : 'btn-outline-primary' }} btn-sm px-3 rounded-pill shadow-sm">
                                <i class="material-icons-outlined" style="font-size: 14px; margin-bottom: -2px;">{{ $npi->canAct ? 'grading' : 'visibility' }}</i> 
                                {{ $npi->canAct ? 'Proses' : 'Detail' }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class='material-icons-outlined fs-2 mb-2'>inbox</i>
                            <p class="mb-0">Tidak ada data NPI yang ditemukan.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
