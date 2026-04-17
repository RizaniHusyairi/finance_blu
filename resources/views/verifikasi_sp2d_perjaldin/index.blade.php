@extends('layouts.app')

@section('content')
<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h5 class="mb-1 text-primary fw-bold">Verifikasi SP2D Perjalanan Dinas</h5>
        <p class="text-muted mb-0">Role Aktif: <span class="badge bg-dark fw-normal">{{ $roleCode }}</span></p>
    </div>
</div>

<!-- Summary Cards -->
<div class="row custom-cards mb-4">
    <div class="col-md-3 col-6 d-flex">
        <div class="card radius-10 w-100 border-start border-0 border-4 border-primary shadow-sm bg-primary bg-opacity-10">
            <div class="card-body p-3">
                <p class="mb-1 text-primary font-12 fw-bold">Menunggu Aksi Saya</p>
                <h4 class="mb-0 text-primary fw-bold">{{ $summary['pending'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 d-flex">
        <div class="card radius-10 w-100 border-start border-0 border-4 border-success shadow-sm">
            <div class="card-body p-3">
                <p class="mb-1 text-secondary font-12">Telah Saya Setujui</p>
                <h4 class="mb-0 text-success fw-bold">{{ $summary['approved'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 d-flex">
        <div class="card radius-10 w-100 border-start border-0 border-4 border-danger shadow-sm">
            <div class="card-body p-3">
                <p class="mb-1 text-secondary font-12">Revisi (Dikembalikan)</p>
                <h4 class="mb-0 text-danger fw-bold">{{ $summary['revisi'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 d-flex">
        <div class="card radius-10 w-100 border-start border-0 border-4 border-dark shadow-sm bg-light">
            <div class="card-body p-3">
                <p class="mb-1 font-12 fw-bold text-dark">Selesai Keseluruhan</p>
                <h4 class="mb-0 text-dark fw-bold">{{ $summary['selesai'] }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card radius-10 shadow-sm border-0">
    <div class="card-header bg-transparent border-bottom px-4 py-3 d-flex flex-column flex-md-row align-items-center justify-content-between">
        <div class="mb-2 mb-md-0 fw-bold text-secondary text-uppercase font-14">
            <i class="material-icons-outlined" style="font-size: 16px; margin-bottom: -3px;">fact_check</i> Antrean Verifikasi SP2D
        </div>
        <div class="d-flex gap-2 w-100 w-md-auto">
            <form action="{{ route('verifikasi-sp2d.perjaldin.index') }}" method="GET" class="d-flex flex-grow-1 gap-2">
                <select name="status" class="form-select form-select-sm border-secondary" style="width: 170px;" onchange="this.form.submit()">
                    <option value="semua" {{ $statusFilter == 'semua' ? 'selected' : '' }}>Semua Status</option>
                    <option value="pending" {{ $statusFilter == 'pending' ? 'selected' : '' }}>1. Menunggu Aksi Saya</option>
                    <option value="approved" {{ $statusFilter == 'approved' ? 'selected' : '' }}>2. Telah Saya Setujui</option>
                    <option value="revisi" {{ $statusFilter == 'revisi' ? 'selected' : '' }}>3. Revisi</option>
                    <option value="selesai" {{ $statusFilter == 'selesai' ? 'selected' : '' }}>4. Selesai Final</option>
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
            <table class="table table-hover align-middle mb-0 font-13">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">No. SP2D & Tgl</th>
                        <th>Dok. Dasar (NPI/SPM)</th>
                        <th>Uraian Tagihan Perjaldin</th>
                        <th class="text-end">Nilai (Rp)</th>
                        <th>Status Workflow</th>
                        <th class="text-center">Aksi / Verif</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($viewSp2ds as $sp2d)
                    <tr class="{{ $sp2d->canAct ? 'table-primary border-primary' : '' }}">
                        <td class="ps-4">
                            <span class="fw-bold text-dark">{{ $sp2d->nomor_sp2d }}</span><br>
                            <span class="text-muted" style="font-size: 11px;">Tgl SP2D: {{ optional($sp2d->tanggal_sp2d)->format('d/m/Y') ?? '-' }}</span><br>
                            <span class="badge bg-secondary font-10">Tahun: {{ $sp2d->tahun_anggaran ?? '-' }}</span>
                        </td>
                        <td>
                            <div class="font-12 mb-1">
                                <span class="text-muted">NPI:</span> <span class="fw-bold">{{ $sp2d->npiModel?->nomor_npi ?? '-' }}</span>
                            </div>
                            <div class="font-12 mb-1">
                                <span class="text-muted">SPM:</span> <span>{{ $sp2d->spmModel?->nomor_spm ?? '-' }}</span>
                            </div>
                            <div class="font-12">
                                <span class="text-muted">SPP:</span> <span>{{ $sp2d->sppModel?->nomor_spp ?? '-' }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="text-wrap" style="max-width: 250px; font-size: 0.8rem;">
                                {{ Str::limit($sp2d->tagihanModel?->deskripsi ?? '-', 70) }}
                            </div>
                            <div class="font-10 text-muted mt-1">Pembuat: {{ $sp2d->bendaharaPengeluaran?->name ?? '-' }}</div>
                        </td>
                        <td class="text-end fw-bold font-14">
                            {{ number_format($sp2d->nominal, 0, ',', '.') }}
                        </td>
                        <td>
                            @if($sp2d->canAct)
                                <span class="badge bg-primary text-white mb-1"><i class="material-icons-outlined font-12" style="vertical-align: middle;">touch_app</i> Menunggu Anda</span><br>
                            @else
                                @php
                                    $bClass = match ($sp2d->myApprovalStatus) {
                                        'APPROVED' => 'bg-success',
                                        'REVISION' => 'bg-danger',
                                        'PENDING'  => 'bg-warning text-dark',
                                        default    => 'bg-secondary'
                                    };
                                    $sLabel = match ($sp2d->myApprovalStatus) {
                                        'APPROVED' => 'Anda Setujui',
                                        'REVISION' => 'Anda Tolak/Revisi',
                                        'PENDING'  => 'Menunggu Antrean',
                                        default    => 'Tidak Berlaku'
                                    };
                                @endphp
                                <span class="badge {{ $bClass }} font-10 mb-1">{{ $sLabel }}</span><br>
                            @endif

                            @if($sp2d->status == 'DISETUJUI_FINAL')
                                <span class="badge bg-dark font-10 fw-normal">Selesai Keseluruhan</span>
                            @else
                                <span class="badge border border-secondary text-secondary font-10 fw-normal">{{ $sp2d->status }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($sp2d->canAct)
                                <a href="{{ route('verifikasi-sp2d.perjaldin.detail', $sp2d->id) }}" class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm py-1 fw-bold">
                                    <i class="material-icons-outlined" style="font-size: 14px; margin-bottom: -2px;">fact_check</i> Cek SP2D
                                </a>
                            @else
                                <a href="{{ route('verifikasi-sp2d.perjaldin.detail', $sp2d->id) }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill py-1">
                                    <i class="material-icons-outlined" style="font-size: 14px; margin-bottom: -2px;">visibility</i> Lihat
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class='material-icons-outlined fs-2 mb-2'>check_circle_outline</i>
                            <p class="mb-0">Tidak ada SP2D Perjaldin yang butuh tindakan Anda saat ini.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
