@extends('layouts.app')
@section('title', 'Detail SPM Kontrak')

@php
    $statusSpmClass = match ($statusSpm) {
        'Belum Dibuat' => 'bg-secondary',
        'DRAFT' => 'bg-warning text-dark',
        'Menunggu Verifikasi' => 'bg-info',
        'Revisi' => 'bg-danger',
        'Disetujui Final' => 'bg-success',
        default => 'bg-secondary',
    };

    $ppspmStatusLabel = $ppspmApproval?->status ?? 'Belum diajukan';
    $kasubbagStatusLabel = $kasubbagApproval?->status ?? 'Belum diajukan';
    $koordinatorStatusLabel = $koordinatorApproval?->status ?? 'Belum diajukan';
    $ppspmStatusClass = match($ppspmStatusLabel) {
        'APPROVED' => 'bg-success', 'PENDING' => 'bg-warning text-dark', 'REVISION','REJECTED' => 'bg-danger', default => 'bg-secondary'
    };
    $kasubbagStatusClass = match($kasubbagStatusLabel) {
        'APPROVED' => 'bg-success', 'PENDING' => 'bg-warning text-dark', 'REVISION','REJECTED' => 'bg-danger', default => 'bg-secondary'
    };
    $koordinatorStatusClass = match($koordinatorStatusLabel) {
        'APPROVED' => 'bg-success', 'PENDING' => 'bg-warning text-dark', 'REVISION','REJECTED' => 'bg-danger', default => 'bg-secondary'
    };

    $workflowLockLabel = ($canEditSpm) ? 'Dapat diedit' : 'Terkunci / readonly';
    $documentStatusMeta = [
        'ready' => ['label' => 'Tersedia', 'class' => 'bg-success'],
        'missing' => ['label' => 'Belum Ada', 'class' => 'bg-danger'],
        'not_required' => ['label' => 'Tidak Wajib', 'class' => 'bg-secondary'],
    ];
@endphp

@push('css')
    <style>
        .spm-workspace-hero { background: linear-gradient(135deg, #f0f4ff, #f1f5f9); border-bottom: 1px solid rgba(15,23,42,.08); padding-bottom: 1.5rem; margin-bottom: 2rem; position: relative; }
        .spm-workspace-hero::before { content: ""; position: absolute; left: 0; top: 0; width: 4px; height: 100%; background: #6366f1; }
        .spm-summary-tile { background: #fff; border: 1px solid rgba(15,23,42,.08); border-radius: .75rem; padding: 1rem; height: 100%; }
        .spm-summary-tile .label { color: #6c757d; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; margin-bottom: .35rem; }
        .spm-summary-tile .value { color: #212529; font-weight: 700; line-height: 1.35; }
        .spm-section-card { border: 0; border-radius: 1rem; box-shadow: 0 .125rem .25rem rgba(15,23,42,.04); border: 1px solid rgba(15,23,42,.08); overflow: hidden; background: #fff; }
        .spm-section-heading { color: #475569; font-size: .8rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; margin-bottom: 1.2rem; display: flex; align-items: center; gap: .5rem; }
        .spm-info-block { margin-bottom: 1.2rem; }
        .spm-info-block .label { color: #64748b; font-size: .8rem; margin-bottom: .25rem; }
        .spm-info-block .value { font-weight: 600; color: #1e293b; line-height: 1.4; }
        .spm-readiness-item { display: flex; align-items: flex-start; gap: .85rem; padding: .65rem 0; border-bottom: 1px solid rgba(15,23,42,.04); }
        .spm-readiness-item:last-child { border-bottom: 0; }
        .spm-readiness-icon { width: 1.5rem; height: 1.5rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-size: .75rem; flex-shrink: 0; }
        .spm-icon-ready { background: rgba(25,135,84,.12); color: #198754; }
        .spm-icon-missing { background: rgba(220,53,69,.12); color: #dc3545; }
        .spm-doc-row { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: .85rem 0; border-bottom: 1px solid rgba(15,23,42,.04); }
        .spm-doc-row:last-child { border-bottom: 0; }
        .spm-activity-row { position: relative; padding-left: 1.25rem; margin-bottom: 1.25rem; }
        .spm-activity-row:last-child { margin-bottom: 0; }
        .spm-activity-row::before { content: ""; position: absolute; left: 0; top: .35rem; width: .5rem; height: .5rem; border-radius: 999px; background: #cbd5e1; }
        .spm-activity-active::before { background: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.2); }
        .spm-modal-section { border: 1px solid rgba(15,23,42,.08); border-radius: .75rem; padding: 1.25rem; background: #fff; margin-bottom: 1rem; }

        .timeline-wrapper { display: flex; align-items: center; justify-content: space-between; position: relative; padding: 2rem 0; }
        .timeline-line { position: absolute; top: 3.25rem; left: 10%; right: 10%; height: 3px; background: #e2e8f0; z-index: 1; }
        .timeline-step { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; text-align: center; flex: 1; }
        .timeline-icon { width: 44px; height: 44px; border-radius: 999px; background: #fff; border: 3px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #94a3b8; font-weight: bold; margin-bottom: .75rem; transition: all .2s; }
        .timeline-label { font-weight: 600; color: #475569; font-size: .85rem; line-height: 1.3; }
        .timeline-sub { font-size: .75rem; color: #94a3b8; margin-top: .25rem; max-width: 150px; }
        .timeline-step.passed .timeline-icon { border-color: #10b981; background: #10b981; color: #fff; }
        .timeline-step.active .timeline-icon { border-color: #6366f1; color: #6366f1; box-shadow: 0 0 0 4px rgba(99,102,241,.2); }
        .timeline-step.revision .timeline-icon { border-color: #ef4444; color: #ef4444; background: #fee2e2; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <x-page-title title="Workspace Operator BLU" subtitle="Detail & Penyusunan SPM Kontrak" />
        <a href="{{ route('spms.kontrak.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
            <div class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill fs-5"></i><div>{{ session('success') }}</div></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
            <div class="d-flex align-items-start gap-2"><i class="bi bi-exclamation-triangle-fill fs-5"></i><div>{{ session('error') }}</div></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- A. HEADER KERJA --}}
    <div class="spm-workspace-hero p-4 rounded-3 shadow-sm">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
            <div class="flex-grow-1">
                <h3 class="fw-bold mb-2 text-dark">{{ $kontrak?->nama_pekerjaan ?? $tagihan?->deskripsi ?? 'SPM Kontrak' }}</h3>
                <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
                    <span class="badge {{ $statusSpmClass }} px-3 py-2">SPM: {{ $statusSpm }}</span>
                    @if($spmModel && $spmModel->status === 'Revisi')
                        <span class="badge bg-danger px-3 py-2"><i class="bi bi-exclamation-circle me-1"></i> Butuh Perbaikan</span>
                    @endif
                </div>
                <div class="row g-3">
                    <div class="col-md-3 col-6"><div class="spm-summary-tile"><div class="label">Nomor SPM</div><div class="value">{{ $spmModel?->nomor_spm ?? '-' }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="spm-summary-tile"><div class="label">Nomor SPP</div><div class="value">{{ $sppModel->nomor_spp ?? '-' }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="spm-summary-tile"><div class="label">Vendor</div><div class="value text-truncate">{{ $vendor?->nama_pihak ?? '-' }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="spm-summary-tile"><div class="label">Nilai Pembayaran</div><div class="value text-success fs-6">Rp {{ number_format($nominalSpm, 0, ',', '.') }}</div></div></div>
                </div>
            </div>

            <div class="d-flex flex-column gap-2" style="min-width: 200px;">
                @if($spmModel)
                    <a href="{{ route('spms.cetak-pdf', $spmModel->id) }}" target="_blank" class="btn btn-outline-danger shadow-sm"><i class="bi bi-file-earmark-pdf me-1"></i> Cetak PDF SPM</a>
                @endif

                {{-- Upload SPM Bertandatangan --}}
                @if($spmModel && in_array($spmModel->status, [\App\Models\DokumenSpm::STATUS_MENUNGGU_UPLOAD, \App\Models\DokumenSpm::STATUS_SPM_TERBIT]))
                    <button type="button" class="btn btn-warning shadow-sm" data-bs-toggle="modal" data-bs-target="#modalUploadSpmSigned">
                        <i class="bi bi-upload me-1"></i> {{ $hasSignedSpmFile ? 'Re-upload SPM Bertandatangan' : 'Upload Scan SPM Bertandatangan' }}
                    </button>

                    @if($hasSignedSpmFile)
                        <div class="alert alert-success p-2 mb-0 small border-0">
                            <i class="bi bi-check-circle-fill me-1"></i> File SPM bertandatangan sudah diunggah.
                        </div>
                    @else
                        <div class="alert alert-warning p-2 mb-0 small border-0">
                            <i class="bi bi-exclamation-triangle me-1"></i> Upload scan SPM bertandatangan untuk menyelesaikan proses.
                        </div>
                    @endif
                @endif



                @if($canEditSpm)
                    <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalSpmKontrak">
                        <i class="bi bi-pencil-square me-1"></i> {{ $spmModel ? 'Edit Draft SPM' : 'Buat Draft Baru' }}
                    </button>
                @endif

                @if($spmModel)
                    @if($canSubmit)
                        @if($isReadyToSubmit)
                            <form action="{{ route('spms.kontrak.submit', $sppModel->id) }}" method="POST" onsubmit="return confirm('Ajukan SPM ini untuk verifikasi PPSPM dan Kasubbag secara paralel?')">
                                @csrf
                                <button type="submit" class="btn btn-success shadow-sm w-100"><i class="bi bi-send me-1"></i> Ajukan Verifikasi</button>
                            </form>
                        @else
                            <button type="button" class="btn btn-success shadow-sm w-100" disabled><i class="bi bi-send me-1"></i> Ajukan Verifikasi</button>
                        @endif
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- B. PANEL STATUS & KESIAPAN --}}
    <div class="card spm-section-card mb-4 border-primary border-opacity-25" style="background-color: #f8f9ff;">
        <div class="card-body p-4">
            <h5 class="fw-bold text-primary mb-4"><i class="bi bi-shield-check me-2"></i> Status Kesiapan & Progress Verifikasi</h5>
            <div class="row g-4 align-items-center">
                <div class="col-xl-5">
                    <div class="bg-white p-3 rounded-3 border shadow-sm h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Checklist Operator</h6>
                            <span class="badge {{ $isChecklistComplete ? 'bg-success' : 'bg-warning text-dark' }}">{{ $isChecklistComplete ? 'Lengkap' : 'Belum Lengkap' }}</span>
                        </div>
                        <div style="font-size: 0.9rem;">
                            @foreach($readinessChecklist as $item)
                                <div class="spm-readiness-item py-1">
                                    <span class="spm-readiness-icon {{ $item['status'] === 'ready' ? 'spm-icon-ready' : 'spm-icon-missing' }}"><i class="bi {{ $item['status'] === 'ready' ? 'bi-check2' : 'bi-x-lg' }}"></i></span>
                                    <div>{{ $item['label'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        @if(!$isChecklistComplete && $readinessIssues->isNotEmpty())
                            <div class="alert alert-warning mt-3 mb-0 p-2 py-1 small border-0">
                                <ul class="mb-0 ps-3">@foreach($readinessIssues as $issue)<li>{{ $issue }}</li>@endforeach</ul>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-xl-7">
                    <div class="bg-white p-3 rounded-3 border shadow-sm h-100">
                        <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-people me-2"></i> Status Verifikator SPM</h6>
                        <ul class="list-group mb-0">
                            <!-- PPSPM -->
                            <li class="list-group-item px-3 py-2 border-start-0 border-end-0 border-top-0 border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            <i class="bi bi-person-check fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">PPSPM</div>
                                            <div class="small text-muted">{{ $spmModel?->ppspm?->name ?? 'Belum Ditentukan' }}</div>
                                            @if($spmModel?->ppspm?->nip)<div class="text-muted font-monospace" style="font-size: .72rem;">NIP: {{ $spmModel->ppspm->nip }}</div>@endif
                                        </div>
                                    </div>
                                    <span class="badge {{ $ppspmStatusClass }}">{{ $ppspmStatusLabel }}</span>
                                </div>
                            </li>
                            <!-- Koordinator Keuangan -->
                            <li class="list-group-item px-3 py-2 border-start-0 border-end-0 border-top-0 border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            <i class="bi bi-person-gear fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">Koordinator Keuangan</div>
                                            <div class="small text-muted">{{ $koordinatorUser?->name ?? 'Belum Ditentukan' }}</div>
                                            @if($koordinatorUser?->nip)<div class="text-muted font-monospace" style="font-size: .72rem;">NIP: {{ $koordinatorUser->nip }}</div>@endif
                                        </div>
                                    </div>
                                    <span class="badge {{ $koordinatorStatusClass }}">{{ $koordinatorStatusLabel }}</span>
                                </div>
                            </li>
                            <!-- Kasubbag -->
                            <li class="list-group-item px-3 py-2 border-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            <i class="bi bi-person-badge fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">Kepala Subbagian Keuangan dan Tata Usaha</div>
                                            <div class="small text-muted">{{ $kasubbagUser?->name ?? 'Belum Ditentukan' }}</div>
                                            @if($kasubbagUser?->nip)<div class="text-muted font-monospace" style="font-size: .72rem;">NIP: {{ $kasubbagUser->nip }}</div>@endif
                                        </div>
                                    </div>
                                    <span class="badge {{ $kasubbagStatusClass }}">{{ $kasubbagStatusLabel }}</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- C. KOLOM KIRI (SUMBER DATA) --}}
        <div class="col-xl-7">
            {{-- Ringkasan SPP --}}
            <div class="card spm-section-card mb-4">
                <div class="card-body p-4">
                    <div class="spm-section-heading text-primary"><i class="bi bi-file-earmark-check"></i> 1. Ringkasan SPP Sumber</div>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="spm-info-block"><div class="label">Nomor SPP</div><div class="value">{{ $sppModel->nomor_spp ?? '-' }}</div></div></div>
                        <div class="col-md-6"><div class="spm-info-block"><div class="label">Tanggal SPP</div><div class="value">{{ optional($sppModel->tanggal_spp)->format('d F Y') ?? '-' }}</div></div></div>
                        <div class="col-md-6"><div class="spm-info-block"><div class="label">Nilai SPP</div><div class="value text-success">Rp {{ number_format($sppModel->nominal_spp ?? 0, 0, ',', '.') }}</div></div></div>
                        <div class="col-md-6"><div class="spm-info-block"><div class="label">Status SPP</div><div class="value"><span class="badge bg-success">{{ $sppModel->status }}</span></div></div></div>
                    </div>
                </div>
            </div>

            {{-- Ringkasan Tagihan --}}
            <div class="card spm-section-card mb-4">
                <div class="card-body p-4">
                    <div class="spm-section-heading text-primary"><i class="bi bi-receipt"></i> 2. Ringkasan Tagihan</div>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="spm-info-block"><div class="label">Nomor Tagihan</div><div class="value">{{ $tagihan?->nomor_tagihan ?? '-' }}</div></div></div>
                        <div class="col-md-6"><div class="spm-info-block"><div class="label">Status Tagihan</div><div class="value"><span class="badge bg-info">{{ $tagihan?->status ?? '-' }}</span></div></div></div>
                        <div class="col-md-4"><div class="spm-info-block"><div class="label">Nilai Bruto</div><div class="value">Rp {{ number_format($tagihan?->total_bruto ?? 0, 0, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="spm-info-block"><div class="label">Total Potongan</div><div class="value text-danger">Rp {{ number_format($tagihan?->total_potongan ?? 0, 0, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="spm-info-block"><div class="label">Nilai Netto</div><div class="value text-success fs-5">Rp {{ number_format($tagihan?->total_netto ?? 0, 0, ',', '.') }}</div></div></div>
                    </div>
                </div>
            </div>

            {{-- Dasar Kontrak & Termin --}}
            <div class="card spm-section-card mb-4">
                <div class="card-body p-4">
                    <div class="spm-section-heading text-primary"><i class="bi bi-file-ruled"></i> 3. Dasar Kontrak & Termin</div>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="spm-info-block"><div class="label">Nomor SPK</div><div class="value">{{ $kontrak?->nomor_spk ?? '-' }}</div></div></div>
                        <div class="col-md-6"><div class="spm-info-block"><div class="label">Tanggal SPK</div><div class="value">{{ optional($kontrak?->tanggal_spk)->format('d F Y') ?? '-' }}</div></div></div>
                        <div class="col-12"><div class="spm-info-block"><div class="label">Nama Pekerjaan</div><div class="value">{{ $kontrak?->nama_pekerjaan ?? '-' }}</div></div></div>
                        <div class="col-md-4"><div class="spm-info-block"><div class="label">Termin</div><div class="value">Termin {{ $termin?->termin_ke ?? '-' }} <span class="badge bg-light text-dark ms-1">{{ $termin?->jenis_termin ?? '-' }}</span></div></div></div>
                        <div class="col-md-4"><div class="spm-info-block"><div class="label">Persentase</div><div class="value">{{ $termin?->persentase ?? 0 }}%</div></div></div>
                        <div class="col-md-4"><div class="spm-info-block"><div class="label">Nilai Bruto Termin</div><div class="value">Rp {{ number_format($termin?->nilai_bruto_termin ?? 0, 0, ',', '.') }}</div></div></div>
                        <div class="col-12"><hr class="my-2 text-muted"></div>
                        <div class="col-md-4"><div class="spm-info-block"><div class="label">BAPP</div><div class="value">{{ $detailKontrak?->nomor_bapp ?? '-' }}</div></div></div>
                        <div class="col-md-4"><div class="spm-info-block"><div class="label">BAST</div><div class="value">{{ $detailKontrak?->nomor_bast ?? '-' }}</div></div></div>
                        <div class="col-md-4"><div class="spm-info-block"><div class="label">BAP</div><div class="value">{{ $detailKontrak?->nomor_bap ?? '-' }}</div></div></div>
                    </div>
                </div>
            </div>

            {{-- Potongan & Total --}}
            <div class="card spm-section-card mb-4">
                <div class="card-body p-4">
                    <div class="spm-section-heading text-primary"><i class="bi bi-percent"></i> 4. Potongan & Total Pembayaran</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded border"><div class="label text-muted small fw-bold mb-1">Jumlah Pengeluaran</div><div class="fw-bold fs-5">Rp {{ number_format($tagihan?->total_bruto ?? 0, 0, ',', '.') }}</div></div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded border"><div class="label text-muted small fw-bold mb-1">Total Potongan</div><div class="fw-bold text-danger fs-5">Rp {{ number_format($tagihan?->total_potongan ?? 0, 0, ',', '.') }}</div></div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded border" style="background: #e8f5e9;"><div class="label text-muted small fw-bold mb-1">Total Pembayaran</div><div class="fw-bold text-success fs-5">Rp {{ number_format($nominalSpm, 0, ',', '.') }}</div></div>
                        </div>
                    </div>
                    @if($potonganPajak->isNotEmpty())
                        <div class="small text-muted fw-bold mb-2">Rincian Potongan:</div>
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light"><tr><th>Jenis</th><th class="text-end">DPP</th><th class="text-center">Tarif</th><th class="text-end">Nominal</th></tr></thead>
                            <tbody>
                                @if($potonganAngsuranUm)
                                    <tr><td>Angsuran Uang Muka</td><td class="text-end">-</td><td class="text-center">-</td><td class="text-end text-warning fw-bold">Rp {{ number_format($potonganAngsuranUm->nominal_potongan, 0, ',', '.') }}</td></tr>
                                @endif
                                @foreach($potonganPajak as $pot)
                                    <tr>
                                        <td>{{ $pot->pajak?->jenis_pajak ?? $pot->nama_pajak_snapshot ?? '-' }}</td>
                                        <td class="text-end">Rp {{ number_format($pot->dpp ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-center">{{ $pot->persentase_tarif_snapshot ?? '-' }}%</td>
                                        <td class="text-end text-danger fw-bold">Rp {{ number_format($pot->nominal_potongan, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            {{-- Dokumen Pendukung --}}
            <div class="card spm-section-card mb-4">
                <div class="card-body p-4">
                    <div class="spm-section-heading text-primary"><i class="bi bi-paperclip"></i> 5. Dokumen Pendukung SPM</div>
                    @foreach($documentStatuses as $document)
                        @php($docMeta = $documentStatusMeta[$document['status']] ?? $documentStatusMeta['missing'])
                        <div class="spm-doc-row">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge {{ $docMeta['class'] }}" style="width: 80px;">{{ $docMeta['label'] }}</span>
                                <div class="fw-semibold text-dark">{{ $document['label'] }}</div>
                            </div>
                            <div>
                                @if($document['is_available'])
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($document['path']) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-search me-1"></i> Lihat</a>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- D. KOLOM KANAN --}}
        <div class="col-xl-5">
            <div class="sticky-top" style="top: 1.5rem; z-index: 1;">



                {{-- Ringkasan Draft SPM --}}
                <div class="card spm-section-card mb-4 border-primary shadow-sm">
                    <div class="card-header bg-primary text-white p-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-check me-2"></i> Ringkasan Draft SPM</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted fw-semibold">Status</span>
                            <span class="badge {{ $statusSpmClass }} fs-6">{{ $statusSpm }}</span>
                        </div>
                        <div class="spm-info-block mb-2"><div class="label">Nomor SPM</div><div class="value">{{ $spmModel?->nomor_spm ?? '-' }}</div></div>
                        <div class="spm-info-block mb-2"><div class="label">Tanggal SPM</div><div class="value">{{ optional($spmModel?->tanggal_spm)->format('d F Y') ?? '-' }}</div></div>
                        <div class="spm-info-block mb-3"><div class="label">Nilai SPM</div><div class="value text-primary fs-5">Rp {{ number_format($nominalSpm, 0, ',', '.') }}</div></div>
                        <div class="p-2 bg-light rounded text-center small text-muted">Mode Dokumen: {{ $workflowLockLabel }}</div>
                    </div>
                </div>

                {{-- Anggaran & Akun --}}
                <div class="card spm-section-card mb-4">
                    <div class="card-body p-4">
                        <div class="spm-section-heading text-primary"><i class="bi bi-wallet2"></i> Anggaran & Akun Pengeluaran</div>
                        <div class="spm-info-block mb-3"><div class="label">DIPA / Tahun / Revisi</div><div class="value">{{ $dipa?->nomor_dipa ?? '-' }} <span class="text-muted fw-normal">(Thn: {{ $dipa?->tahun_anggaran ?? '-' }}, Rev: {{ $dipa?->revisi_aktif_ke ?? '-' }})</span></div></div>
                        <div class="p-3 bg-light rounded border border-primary border-opacity-25">
                            <div class="label text-primary fw-bold small mb-1">Item DIPA / COA</div>
                            <div class="value fs-5">@if($selectedBudgetItem?->coa) {{ $selectedBudgetItem->coa->kode_mak_lengkap }} @else <span class="text-danger">Belum Tersedia</span> @endif</div>
                            <div class="text-muted small lh-sm mt-1">{{ $selectedBudgetItem?->coa?->nama_akun ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                {{-- Vendor & Rekening --}}
                <div class="card spm-section-card mb-4">
                    <div class="card-body p-4">
                        <div class="spm-section-heading text-primary"><i class="bi bi-bank"></i> Vendor & Rekening Tujuan</div>
                        <div class="spm-info-block mb-3"><div class="label">Vendor</div><div class="value">{{ $vendor?->nama_pihak ?? '-' }}</div><div class="small text-muted">NPWP: {{ $vendor?->npwp ?? '-' }}</div></div>
                        <hr class="text-muted my-2">
                        <div class="spm-info-block mb-2"><div class="label">Bank</div><div class="value">{{ $rekening?->nama_bank ?? '-' }}</div></div>
                        <div class="spm-info-block mb-2"><div class="label">Nomor Rekening</div><div class="value font-monospace fs-6">{{ $rekening?->nomor_rekening ?? 'BELUM ADA' }}</div></div>
                        <div class="spm-info-block mb-0"><div class="label">Atas Nama</div><div class="value">{{ $rekening?->nama_rekening ?? '-' }}</div></div>
                    </div>
                </div>

                {{-- Aktivitas Workflow --}}
                <div class="card spm-section-card mb-4">
                    <div class="card-body p-4">
                        <div class="spm-section-heading text-primary"><i class="bi bi-clock-history"></i> Aktivitas Workflow</div>
                        <div class="mt-2">
                            @forelse($recentActivities as $idx => $activity)
                                <div class="spm-activity-row {{ $idx === 0 ? 'spm-activity-active' : '' }}">
                                    <div class="fw-bold text-dark">{{ $activity['title'] }}</div>
                                    <div class="small text-muted">{{ $activity['time'] ?? '-' }} &bull; <span>{{ $activity['actor'] }}</span></div>
                                    @if(!empty($activity['note']))
                                        <div class="small text-muted mt-1 lh-sm fst-italic">"{{ $activity['note'] }}"</div>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center text-muted small py-3">Belum ada aktivitas.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- MODAL DRAFT SPM --}}
    <div class="modal fade" id="modalSpmKontrak" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form action="{{ route('spms.kontrak.store', $sppModel->id) }}" method="POST" class="modal-content border-0 shadow">
                @csrf

                <div class="modal-header text-white border-0" style="background: #6366f1;">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>{{ $spmModel ? 'Edit Draft SPM Kontrak' : 'Buat Draft SPM Kontrak Baru' }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    @if($spmModel && $spmModel->status === 'Revisi')
                        <div class="alert alert-danger mb-4 shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Dokumen ini dikembalikan karena memerlukan revisi. Silakan sesuaikan data.</div>
                        @if($spmModel->catatan_revisi)
                            <div class="alert alert-warning mb-4 border-0"><strong>Catatan revisi:</strong> {{ $spmModel->catatan_revisi }}</div>
                        @endif
                    @endif

                    <fieldset class="border-0 p-0 m-0" {{ $canEditSpm ? '' : 'disabled' }}>

                        {{-- SEC 1: INFORMASI DASAR --}}
                        <div class="spm-modal-section shadow-sm">
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-1-circle me-1"></i> Informasi Dasar SPM</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nomor SPM <span class="text-danger">*</span></label>
                                    <input type="text" name="nomor_spm" class="form-control fw-bold text-primary bg-light" required value="{{ old('nomor_spm', $spmModel?->nomor_spm ?? $autoNomorSpm) }}" placeholder="Ketik nomor SPM">
                                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Nomor di atas diturunkan dari SPP, ubah jika perlu.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Tanggal SPM <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_spm" class="form-control" required value="{{ old('tanggal_spm', optional($spmModel?->tanggal_spm)->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nilai SPM (Otomatis dari SPP)</label>
                                    <input type="text" class="form-control fw-bold text-success bg-white" value="Rp {{ number_format($nominalSpm, 0, ',', '.') }}" readonly>
                                </div>
                            </div>
                        </div>

                        {{-- SEC 2: REFERENSI DASAR PEMBAYARAN --}}
                        <div class="spm-modal-section shadow-sm">
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-2-circle me-1"></i> Referensi Dasar Pembayaran</h6>
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label fw-semibold">Nomor DIPA</label><input type="text" class="form-control bg-light" value="{{ $dipa?->nomor_dipa ?? '-' }}" readonly></div>
                                <div class="col-md-6"><label class="form-label fw-semibold">Tanggal DIPA</label><input type="text" class="form-control bg-light" value="{{ optional($dipa?->tanggal_dipa ?? null)->format('d F Y') ?? '-' }}" readonly></div>
                                <div class="col-md-6"><label class="form-label fw-semibold">Nomor Kontrak / SPK</label><input type="text" class="form-control bg-light" value="{{ $kontrak?->nomor_spk ?? '-' }}" readonly></div>
                                <div class="col-md-6"><label class="form-label fw-semibold">Tanggal SPK</label><input type="text" class="form-control bg-light" value="{{ optional($kontrak?->tanggal_spk)->format('d F Y') ?? '-' }}" readonly></div>
                                <div class="col-md-6"><label class="form-label fw-semibold">Nomor BAST</label><input type="text" class="form-control bg-light" value="{{ $detailKontrak?->nomor_bast ?? '-' }}" readonly></div>
                                <div class="col-md-6"><label class="form-label fw-semibold">Nomor SPP</label><input type="text" class="form-control bg-light" value="{{ $sppModel->nomor_spp ?? '-' }}" readonly></div>
                            </div>
                        </div>

                        {{-- SEC 3: KOMPONEN KEUANGAN --}}
                        <div class="spm-modal-section shadow-sm">
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-3-circle me-1"></i> Komponen Keuangan</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded border"><div class="small fw-bold text-muted mb-1">Jumlah Pengeluaran</div><div class="fs-6">Rp {{ number_format($tagihan?->total_bruto ?? 0, 0, ',', '.') }}</div></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded border"><div class="small fw-bold text-muted mb-1">Total Potongan</div><div class="fs-6 text-danger">Rp {{ number_format($tagihan?->total_potongan ?? 0, 0, ',', '.') }}</div></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 rounded border" style="background: #e8f5e9;"><div class="small fw-bold text-muted mb-1">Total Pembayaran</div><div class="fs-5 fw-bold text-success">Rp {{ number_format($nominalSpm, 0, ',', '.') }}</div></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Akun Pengeluaran / COA</label>
                                    <input type="text" class="form-control bg-light" value="{{ $selectedBudgetItem?->coa?->kode_mak_lengkap ?? '-' }} — {{ $selectedBudgetItem?->coa?->nama_akun ?? '-' }}" readonly>
                                </div>
                            </div>
                        </div>

                        {{-- SEC 4: VERIFIKATOR --}}
                        <div class="spm-modal-section shadow-sm mb-0">
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-4-circle me-1"></i> Verifikasi & Penandatangan</h6>
                            <div class="alert alert-info border-0 py-2 small mb-3">
                                <i class="bi bi-info-circle me-1"></i> Mode verifikasi paralel aktif. SPM akan diperiksa oleh PPSPM dan Kasubbag secara bersamaan saat diajukan.
                            </div>
                            <div class="row g-4">
                                <div class="col-md-6 border-end">
                                    <label class="form-label fw-semibold">Verifikator PPSPM <span class="text-danger">*</span></label>
                                    @php($tagihanPpspmId = $tagihan?->ppspm_user_id)
                                    @php($tagihanPpspmNama = $tagihan?->ppspm_nama_snapshot)
                                    @php($tagihanPpspmNip = $tagihan?->ppspm_nip_snapshot)
                                    @php($ppspmDisplay = $tagihanPpspmNama ? trim($tagihanPpspmNama . ($tagihanPpspmNip ? ' (NIP: ' . $tagihanPpspmNip . ')' : '')) : 'PPSPM belum ditentukan pada pengajuan tagihan')
                                    <input type="text" class="form-control bg-light" value="{{ $ppspmDisplay }}" readonly>
                                    <input type="hidden" name="ppspm_id" value="{{ $tagihanPpspmId }}">
                                    <div class="form-text">
                                        @if($tagihanPpspmId)
                                            PPSPM otomatis mengikuti verifikator yang ditentukan saat pengajuan tagihan.
                                        @else
                                            <span class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> Tagihan belum memiliki verifikator PPSPM. Hubungi pembuat tagihan untuk melengkapi.</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Verifikator Kasubbag</label>
                                    <input type="text" class="form-control bg-light" value="{{ $kasubbagUser?->name ?? 'Kasubbag Tidak Tersedia (Otomatis)' }}" readonly>
                                    <div class="form-text">Kasubbag otomatis ditentukan oleh sistem.</div>
                                </div>
                            </div>
                        </div>

                    </fieldset>
                </div>

                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    @if($canEditSpm)
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Simpan Draft SPM</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection

{{-- Modal Upload SPM Bertandatangan --}}
@if($spmModel && in_array($spmModel->status, [\App\Models\DokumenSpm::STATUS_MENUNGGU_UPLOAD, \App\Models\DokumenSpm::STATUS_SPM_TERBIT]))
<div class="modal fade" id="modalUploadSpmSigned" tabindex="-1" aria-labelledby="modalUploadSpmSignedLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning bg-opacity-10 border-0">
                <h5 class="modal-title fw-bold" id="modalUploadSpmSignedLabel">
                    <i class="bi bi-upload me-2 text-warning"></i> Upload Scan SPM Bertandatangan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('spms.kontrak.upload-signed-spm', $spmModel->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info border-0 small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Unggah file scan SPM yang sudah <strong>dicetak dan ditandatangani</strong> oleh pihak berwenang.
                        Setelah file diunggah, status SPM akan otomatis berubah menjadi <strong>SPM Terbit</strong>.
                    </div>
                    <div class="mb-3">
                        <label for="file_spm_ttd" class="form-label fw-semibold">File SPM Bertandatangan <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="file_spm_ttd" name="file_spm_ttd" accept=".pdf,.jpg,.jpeg,.png" required>
                        <div class="form-text">Format: PDF, JPG, PNG. Maks: 10MB</div>
                    </div>
                    @if($hasSignedSpmFile)
                        <div class="alert alert-success border-0 small mb-0">
                            <i class="bi bi-check-circle me-1"></i> File sebelumnya sudah ada. Upload ulang akan menggantikan file lama.
                        </div>
                    @endif
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-upload me-1"></i> Upload SPM</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if($errors->any() && old('nomor_spm'))
            const modalElement = document.getElementById('modalSpmKontrak');
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        @endif
    });
</script>
@endpush
