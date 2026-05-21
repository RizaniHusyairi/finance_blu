@extends('layouts.app')
@section('title', 'Pencatatan SP2D Honorarium')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
    <div>
        <h5 class="fw-bold text-primary mb-0">Pencatatan SP2D</h5>
        <p class="text-muted mb-0">Honorarium — Bendahara Pengeluaran</p>
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

@if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show mb-3">
        <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@php
    // Konsolidasi Draft + Revisi supaya sama dengan struktur kontrak (4 kartu)
    $cardSummary = [
        'belum_dibuat' => $summary['siap_dibuat'] ?? 0,
        'draft_revisi' => ($summary['draft'] ?? 0) + ($summary['revisi'] ?? 0),
        'menunggu'     => $summary['menunggu'] ?? 0,
        'selesai'      => $summary['selesai'] ?? 0,
    ];
@endphp

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
                        <h3 class="mb-0 fw-bold">{{ $cardSummary['belum_dibuat'] }}</h3>
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
                        <h3 class="mb-0 fw-bold">{{ $cardSummary['draft_revisi'] }}</h3>
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
                        <h3 class="mb-0 fw-bold">{{ $cardSummary['menunggu'] }}</h3>
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
                        <h3 class="mb-0 fw-bold">{{ $cardSummary['selesai'] }}</h3>
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
        <form method="GET" action="{{ route('sp2ds.honor.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Status SP2D</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="semua" {{ $statusFilter === 'semua' ? 'selected' : '' }}>Semua</option>
                    <option value="siap_dibuat" {{ $statusFilter === 'siap_dibuat' ? 'selected' : '' }}>Belum Dibuat</option>
                    <option value="draft" {{ $statusFilter === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="revisi" {{ $statusFilter === 'revisi' ? 'selected' : '' }}>Revisi</option>
                    <option value="menunggu" {{ $statusFilter === 'menunggu' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                    <option value="selesai" {{ $statusFilter === 'selesai' ? 'selected' : '' }}>Disetujui Final / Selesai</option>
                </select>
            </div>
            <div class="col-md-7">
                <label class="form-label small fw-semibold mb-1">Pencarian</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Nomor NPI, SP2D, SPM, SPP, deskripsi..." value="{{ $search }}">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="material-icons-outlined" style="font-size:16px; vertical-align: middle;">search</i> Filter</button>
                <a href="{{ route('sp2ds.honor.index') }}" class="btn btn-outline-secondary btn-sm"><i class="material-icons-outlined" style="font-size:16px; vertical-align: middle;">refresh</i></a>
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
                        <th>NPI / SPM / SPP</th>
                        <th>Deskripsi / Penerima</th>
                        <th class="text-end">Nilai SP2D</th>
                        <th class="text-center">Status SP2D</th>
                        <th class="text-center">Status Verifikasi</th>
                        <th class="text-center" style="width:130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($viewNpis as $idx => $npi)
                        @php
                            $sp2d = $npi->sp2d;
                            $statusSp2d = $npi->status_sp2d;
                            $isBelumDibuat = $statusSp2d === 'SIAP_DIBUAT';
                            $isDraft = in_array($statusSp2d, [\App\Models\DokumenSp2d::STATUS_DRAFT, \App\Models\DokumenSp2d::STATUS_REVISI]);
                            $isFinal = in_array($statusSp2d, [\App\Models\DokumenSp2d::STATUS_DISETUJUI_FINAL, \App\Models\DokumenSp2d::STATUS_EXECUTED]);

                            $statusBadge = match($statusSp2d) {
                                'SIAP_DIBUAT' => 'Belum Dibuat',
                                \App\Models\DokumenSp2d::STATUS_DRAFT => 'Draft',
                                \App\Models\DokumenSp2d::STATUS_REVISI => 'Revisi',
                                \App\Models\DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI => 'Menunggu Verifikasi',
                                \App\Models\DokumenSp2d::STATUS_DISETUJUI_FINAL => 'Disetujui Final',
                                \App\Models\DokumenSp2d::STATUS_EXECUTED => 'Selesai',
                                default => str_replace('_', ' ', $statusSp2d),
                            };
                            $statusClass = match(true) {
                                $isBelumDibuat => 'bg-warning text-dark',
                                $statusSp2d === \App\Models\DokumenSp2d::STATUS_DRAFT => 'bg-secondary',
                                $statusSp2d === \App\Models\DokumenSp2d::STATUS_REVISI => 'bg-danger',
                                $statusSp2d === \App\Models\DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI => 'bg-info',
                                $isFinal => 'bg-success',
                                default => 'bg-secondary',
                            };

                            // Approval breakdown (PPK / Kasubbag / PPSPM)
                            $approvals = collect($sp2d?->workflowInstances?->first()?->approvals ?? []);
                            $ppkStatus = $approvals->firstWhere('role_code', 'PPK')?->status;
                            $kasubbagStatus = $approvals->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')?->status;
                            $ppspmStatus = $approvals->firstWhere('role_code', 'PPSPM')?->status;

                            $jumlahPenerima = collect($npi->tagihanModel?->detailHonorarium)->count();
                        @endphp
                        <tr>
                            <td class="text-center text-muted">{{ $idx + 1 }}</td>
                            <td>
                                @if($sp2d?->nomor_sp2d)
                                    <span class="fw-bold text-primary">{{ $sp2d->nomor_sp2d }}</span>
                                    <div class="text-muted" style="font-size:11px;">{{ optional($sp2d->tanggal_sp2d)->format('d M Y') ?? '-' }}</div>
                                @else
                                    <span class="text-muted fst-italic">Belum Dibuat</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-size: 12px; line-height: 1.6;">
                                    <span class="text-muted">NPI:</span> <span class="fw-semibold">{{ $npi->nomor_npi ?? '-' }}</span><br>
                                    <span class="text-muted">SPM:</span> {{ $npi->spmModel?->nomor_spm ?? '-' }}<br>
                                    <span class="text-muted">SPP:</span> {{ $npi->sppModel?->nomor_spp ?? '-' }}
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-truncate" style="max-width: 200px;" title="{{ $npi->tagihanModel?->deskripsi }}">
                                    {{ \Illuminate\Support\Str::limit($npi->tagihanModel?->deskripsi ?? '-', 55) }}
                                </div>
                                <div class="text-muted text-truncate" style="max-width: 200px; font-size: 12px;">
                                    <i class="material-icons-outlined" style="font-size:12px; vertical-align: middle;">groups</i>
                                    {{ $jumlahPenerima }} Penerima Honor
                                </div>
                            </td>
                            <td class="text-end fw-bold">Rp {{ number_format($npi->nilai_npi, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <span class="badge {{ $statusClass }}">{{ $statusBadge }}</span>
                            </td>
                            <td class="text-center" style="font-size: 11px;">
                                @if($isBelumDibuat || $statusSp2d === \App\Models\DokumenSp2d::STATUS_DRAFT)
                                    <span class="text-muted">-</span>
                                @else
                                    <div><span class="text-muted">PPK:</span>
                                        @if($ppkStatus == 'APPROVED') <span class="text-success"><i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">check</i></span>
                                        @elseif($ppkStatus == 'PENDING') <span class="text-warning"><i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">hourglass_empty</i></span>
                                        @elseif(in_array($ppkStatus, ['REVISION', 'REJECTED'])) <span class="text-danger"><i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">close</i></span>
                                        @else <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                    <div><span class="text-muted">KSB:</span>
                                        @if($kasubbagStatus == 'APPROVED') <span class="text-success"><i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">check</i></span>
                                        @elseif($kasubbagStatus == 'PENDING') <span class="text-warning"><i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">hourglass_empty</i></span>
                                        @elseif(in_array($kasubbagStatus, ['REVISION', 'REJECTED'])) <span class="text-danger"><i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">close</i></span>
                                        @else <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                    <div><span class="text-muted">PPSPM:</span>
                                        @if($ppspmStatus == 'APPROVED') <span class="text-success"><i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">check</i></span>
                                        @elseif($ppspmStatus == 'PENDING') <span class="text-warning"><i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">hourglass_empty</i></span>
                                        @elseif(in_array($ppspmStatus, ['REVISION', 'REJECTED'])) <span class="text-danger"><i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">close</i></span>
                                        @else <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($isBelumDibuat)
                                    <a href="{{ route('sp2ds.honor.detail', $npi->id) }}" class="btn btn-sm btn-primary px-3">
                                        Buat SP2D
                                    </a>
                                @elseif($isDraft)
                                    <a href="{{ route('sp2ds.honor.detail', $npi->id) }}" class="btn btn-sm btn-warning px-3">
                                        Kelola Draft
                                    </a>
                                @else
                                    <a href="{{ route('sp2ds.honor.detail', $npi->id) }}" class="btn btn-sm btn-outline-secondary px-3">
                                        Lihat Detail
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="material-icons-outlined" style="font-size: 48px; opacity: 0.3;">inbox</i>
                                <div class="mt-2">Tidak ada SP2D Honorarium yang memenuhi filter.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
