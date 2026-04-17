@extends('layouts.app')

@section('content')
<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h5 class="mb-1 text-primary fw-bold">Pencatatan SP2D Perjalanan Dinas</h5>
        <p class="text-muted mb-0">Role Aktif: <span class="badge bg-dark fw-normal">Bendahara Pengeluaran</span></p>
    </div>
</div>

<!-- Summary Cards -->
<div class="row custom-cards mb-4">
    <div class="col-md-2 col-12 d-flex">
        <div class="card radius-10 w-100 border-start border-0 border-4 border-info shadow-sm">
            <div class="card-body p-3">
                <p class="mb-1 text-secondary font-12">Siap Dibuat</p>
                <h4 class="mb-0 text-info fw-bold">{{ $summary['siap_dibuat'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-12 d-flex">
        <div class="card radius-10 w-100 border-start border-0 border-4 border-warning shadow-sm">
            <div class="card-body p-3">
                <p class="mb-1 text-secondary font-12">Draft SP2D</p>
                <h4 class="mb-0 text-warning fw-bold">{{ $summary['draft'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-12 d-flex">
        <div class="card radius-10 w-100 border-start border-0 border-4 border-danger shadow-sm">
            <div class="card-body p-3">
                <p class="mb-1 text-secondary font-12">Perlu Revisi</p>
                <h4 class="mb-0 text-danger fw-bold">{{ $summary['revisi'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-12 d-flex">
        <div class="card radius-10 w-100 border-start border-0 border-4 border-primary shadow-sm bg-primary bg-opacity-10">
            <div class="card-body p-3">
                <p class="mb-1 font-12 fw-bold text-primary">Menunggu Verifikasi (Paralel)</p>
                <h4 class="mb-0 text-primary fw-bold">{{ $summary['menunggu'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-12 d-flex">
        <div class="card radius-10 w-100 border-start border-0 border-4 border-success shadow-sm bg-success bg-opacity-10">
            <div class="card-body p-3">
                <p class="mb-1 font-12 fw-bold text-success">SP2D Final / Selesai</p>
                <h4 class="mb-0 text-success fw-bold">{{ $summary['selesai'] }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card radius-10 shadow-sm border-0">
    <div class="card-header bg-transparent border-bottom px-4 py-3 d-flex flex-column flex-md-row align-items-center justify-content-between">
        <div class="mb-2 mb-md-0 fw-bold text-secondary text-uppercase font-14">
            <i class="material-icons-outlined" style="font-size: 16px; margin-bottom: -3px;">account_balance_wallet</i> Daftar Antrean NPI Siap SP2D
        </div>
        <div class="d-flex gap-2 w-100 w-md-auto">
            <form action="{{ route('sp2ds.perjaldin.index') }}" method="GET" class="d-flex flex-grow-1 gap-2">
                <select name="status" class="form-select form-select-sm border-secondary" style="width: 170px;" onchange="this.form.submit()">
                    <option value="semua" {{ $statusFilter == 'semua' ? 'selected' : '' }}>Semua Status</option>
                    <option value="siap_dibuat" {{ $statusFilter == 'siap_dibuat' ? 'selected' : '' }}>1. Siap Dibuat (Belum Ada Draft)</option>
                    <option value="draft" {{ $statusFilter == 'draft' ? 'selected' : '' }}>2. Draft SP2D</option>
                    <option value="revisi" {{ $statusFilter == 'revisi' ? 'selected' : '' }}>3. Revisi</option>
                    <option value="menunggu" {{ $statusFilter == 'menunggu' ? 'selected' : '' }}>4. Menunggu Verifikasi</option>
                    <option value="selesai" {{ $statusFilter == 'selesai' ? 'selected' : '' }}>5. Selesai / Final</option>
                </select>
                <div class="input-group input-group-sm flex-grow-1 border-secondary border rounded">
                    <span class="input-group-text bg-transparent border-0"><i class="material-icons-outlined" style="font-size: 16px;">search</i></span>
                    <input type="text" name="search" class="form-control border-0 px-1" placeholder="Cari Dokumen (NPI, SPM...)" value="{{ $search }}">
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
                        <th class="ps-4">No. NPI & Tanggal</th>
                        <th>No. SP2D</th>
                        <th>Dokumen SPP/SPM</th>
                        <th>Uraian Tagihan Perjaldin</th>
                        <th class="text-end">Nilai (Rp)</th>
                        <th>Status SP2D</th>
                        <th class="text-center">Aksi / Verif</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($viewNpis as $npi)
                    <tr class="{{ in_array($npi->status_sp2d, ['SIAP_DIBUAT', 'DRAFT', 'REVISI']) ? 'table-warning border-warning' : '' }}">
                        <td class="ps-4">
                            <span class="fw-bold text-dark">{{ $npi->nomor_npi }}</span><br>
                            <span class="text-muted" style="font-size: 11px;">Tgl NPI: {{ optional($npi->tanggal_npi)->format('d M Y') ?? '-' }}</span><br>
                            <span class="badge bg-success font-10">NPI Final</span>
                        </td>
                        <td>
                            @if($npi->sp2d)
                                <div class="fw-bold text-primary">{{ $npi->sp2d->nomor_sp2d }}</div>
                                <div class="text-muted font-11">Tgl: {{ optional($npi->sp2d->tanggal_sp2d)->format('d M Y') ?? '-' }}</div>
                            @else
                                <span class="text-muted fst-italic">Belum terbentuk</span>
                            @endif
                        </td>
                        <td>
                            <div class="font-12 mb-1">
                                <span class="text-muted">SPM:</span> <span class="fw-bold">{{ $npi->spmModel?->nomor_spm ?? '-' }}</span>
                            </div>
                            <div class="font-12">
                                <span class="text-muted">SPP:</span> <span>{{ $npi->sppModel?->nomor_spp ?? '-' }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="text-wrap" style="max-width: 220px; font-size: 0.8rem;">
                                {{ Str::limit($npi->tagihanModel?->deskripsi ?? '-', 60) }}
                            </div>
                        </td>
                        <td class="text-end fw-bold font-14">
                            {{ number_format($npi->nilai_npi, 0, ',', '.') }}
                        </td>
                        <td>
                            @php
                                $badgeClass = match ($npi->status_sp2d) {
                                    'SIAP_DIBUAT'                               => 'bg-info text-dark',
                                    \App\Models\DokumenSp2d::STATUS_DRAFT       => 'bg-warning text-dark',
                                    \App\Models\DokumenSp2d::STATUS_REVISI      => 'bg-danger',
                                    \App\Models\DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI => 'bg-primary text-white',
                                    \App\Models\DokumenSp2d::STATUS_DISETUJUI_FINAL => 'bg-success',
                                    \App\Models\DokumenSp2d::STATUS_EXECUTED    => 'bg-success',
                                    default => 'bg-secondary'
                                };
                                $statusLabel = match ($npi->status_sp2d) {
                                    'SIAP_DIBUAT'                               => 'Belum Dibuat',
                                    \App\Models\DokumenSp2d::STATUS_DRAFT       => 'Draft SP2D',
                                    \App\Models\DokumenSp2d::STATUS_REVISI      => 'Revisi',
                                    \App\Models\DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI => 'Menunggu Verifikasi',
                                    \App\Models\DokumenSp2d::STATUS_DISETUJUI_FINAL => 'Disetujui Final',
                                    \App\Models\DokumenSp2d::STATUS_EXECUTED    => 'Telah Dibayar (Lunas)',
                                    default => $npi->status_sp2d
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }} px-2 py-1">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if(in_array($npi->status_sp2d, ['SIAP_DIBUAT']))
                                <a href="{{ route('sp2ds.perjaldin.detail', $npi->id) }}" class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm py-1 fw-bold">
                                    <i class="material-icons-outlined" style="font-size: 14px; margin-bottom: -2px;">add_circle</i> Buat SP2D
                                </a>
                            @elseif(in_array($npi->status_sp2d, [\App\Models\DokumenSp2d::STATUS_DRAFT, \App\Models\DokumenSp2d::STATUS_REVISI]))
                                <a href="{{ route('sp2ds.perjaldin.detail', $npi->id) }}" class="btn btn-warning btn-sm px-3 rounded-pill shadow-sm py-1 fw-bold text-dark">
                                    <i class="material-icons-outlined" style="font-size: 14px; margin-bottom: -2px;">edit_document</i> Lanjutkan
                                </a>
                            @else
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <a href="{{ route('sp2ds.perjaldin.detail', $npi->id) }}" class="btn btn-outline-primary btn-sm px-3 rounded-pill py-1">
                                        Detail
                                    </a>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class='material-icons-outlined fs-2 mb-2'>inbox</i>
                            <p class="mb-0">Tidak ditemukan antrean SPM yang memenuhi syarat untuk pembuatan SP2D.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
