@extends('layouts.app')
@section('title', 'Detail SPP Honorarium')

@php
    $selectedBudgetItem = $selectedBudgetItem ?? ($sppModel?->dipaRevisionItem ?? null);
    $dipa = $selectedBudgetItem?->revision?->dipa;
    $statusTagihanClass = match ($tagihan->status) {
        'DISETUJUI_PPK', 'DISETUJUI' => 'bg-primary',
        'PROSES_SPP' => 'bg-info',
        'SPP_TERBIT', 'SEBAGIAN_SPP_TERBIT', 'SPP_LENGKAP' => 'bg-success',
        default => 'bg-secondary',
    };
    $statusSppLabel = $sppModel?->status ?? 'Belum Dibuat';
    $statusSppClass = match ($statusSppLabel) {
        'Belum Dibuat' => 'bg-secondary',
        'DRAFT' => 'bg-warning text-dark',
        'Menunggu Verifikasi' => 'bg-info',
        'Disetujui PPK' => 'bg-success',
        'Revisi' => 'bg-danger',
        'APPROVED', 'DISETUJUI_SPP' => 'bg-success',
        default => 'bg-secondary',
    };
    $canEditSpp = !$sppModel || in_array($sppModel->status ?? '', ['DRAFT', 'Revisi', '']);
    $canSubmitToPpk = $sppModel && in_array($sppModel->status ?? '', ['DRAFT', 'Revisi']);
    $ppkVerifikatorNama = $sppModel?->ppkVerifikator?->name ?? null;

    $workflowLockLabel = ($workflowSummary['edit_state'] ?? 'editable') === 'locked' ? 'Terkunci / readonly' : 'Dapat diedit';
    $nominalSpp = (float) ($sppModel->nominal_spp ?? $tagihan->total_netto);
    $documentStatusMeta = [
        'ready' => ['label' => 'Tersedia', 'class' => 'bg-success'],
        'missing' => ['label' => 'Belum Ada', 'class' => 'bg-danger'],
        'not_required' => ['label' => 'Tidak Wajib', 'class' => 'bg-secondary'],
    ];
    $oldPpkVerifikator = old('ppk_verifikator_id', $sppModel?->ppk_verifikator_id);

    // Parallel workflow status interpretation
    $ppkStatusLabel = $ppkApproval->status ?? 'Belum diajukan';
    $koordinatorStatusLabel = $koordinatorApproval->status ?? 'Belum diajukan';
    $kasubbagStatusLabel = $kasubbagApproval->status ?? 'Belum diajukan';

    $ppkStatusClass = match($ppkStatusLabel) {
        'APPROVED' => 'text-success',
        'PENDING' => 'text-warning',
        'REVISION', 'REJECTED' => 'text-danger',
        default => 'text-muted'
    };

    $koordinatorStatusClass = match($koordinatorStatusLabel) {
        'APPROVED' => 'text-success',
        'PENDING' => 'text-warning',
        'REVISION', 'REJECTED' => 'text-danger',
        default => 'text-muted'
    };

    $kasubbagStatusClass = match($kasubbagStatusLabel) {
        'APPROVED' => 'text-success',
        'PENDING' => 'text-warning',
        'REVISION', 'REJECTED' => 'text-danger',
        default => 'text-muted'
    };
    
    // Overall Progress Step
    $progressStep = 1; // 1: Draft, 2: Verifikasi, 3: Final
    if ($sppModel && in_array($sppModel->status, ['Menunggu Verifikasi', 'Revisi'])) {
        $progressStep = 2;
    } elseif ($sppModel && in_array($sppModel->status, ['APPROVED', 'DISETUJUI_SPP', 'Disetujui PPK'])) {
        $progressStep = 3;
        // If workflow is APPROVED
        if (optional($sppModel->workflowInstances->first())->status === 'APPROVED') {
            $progressStep = 4;
        }
    }
@endphp

@push('css')
    <style>
        .spp-workspace-hero { background: linear-gradient(135deg, #f8f9fc, #f1f5f9); border-bottom: 1px solid rgba(15, 23, 42, 0.08); padding-bottom: 1.5rem; margin-bottom: 2rem; position: relative; }
        .spp-workspace-hero::before { content: ""; position: absolute; left: 0; top: 0; width: 4px; height: 100%; background: #0d6efd; }
        .spp-summary-tile { background: #fff; border: 1px solid rgba(15, 23, 42, 0.08); border-radius: 0.75rem; padding: 1rem; height: 100%; }
        .spp-summary-tile .label { color: #6c757d; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; margin-bottom: .35rem; }
        .spp-summary-tile .value { color: #212529; font-weight: 700; line-height: 1.35; }
        .spp-section-card { border: 0; border-radius: 1rem; box-shadow: 0 0.125rem 0.25rem rgba(15, 23, 42, 0.04); border: 1px solid rgba(15, 23, 42, 0.08); overflow: hidden; background: #fff;}
        .spp-section-heading { color: #475569; font-size: .8rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; margin-bottom: 1.2rem; display: flex; align-items: center; gap: .5rem; }
        .spp-info-block { margin-bottom: 1.2rem; }
        .spp-info-block .label { color: #64748b; font-size: .8rem; margin-bottom: .25rem; }
        .spp-info-block .value { font-weight: 600; color: #1e293b; line-height: 1.4; }
        .spp-readiness-item { display: flex; align-items: flex-start; gap: .85rem; padding: .65rem 0; border-bottom: 1px solid rgba(15, 23, 42, 0.04); }
        .spp-readiness-item:last-child { border-bottom: 0; }
        .spp-readiness-icon { width: 1.5rem; height: 1.5rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-size: .75rem; flex-shrink: 0; }
        .spp-icon-ready { background: rgba(25, 135, 84, .12); color: #198754; }
        .spp-icon-missing { background: rgba(220, 53, 69, .12); color: #dc3545; }
        .spp-potongan-summary { background: #f8fafc; border: 1px solid rgba(15, 23, 42, 0.06); border-radius: .75rem; padding: 1rem; height: 100%; }
        .spp-doc-row { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: .85rem 0; border-bottom: 1px solid rgba(15, 23, 42, 0.04); }
        .spp-doc-row:last-child { border-bottom: 0; }
        .spp-activity-row { position: relative; padding-left: 1.25rem; margin-bottom: 1.25rem; }
        .spp-activity-row:last-child { margin-bottom: 0; }
        .spp-activity-row::before { content: ""; position: absolute; left: 0; top: .35rem; width: .5rem; height: .5rem; border-radius: 999px; background: #cbd5e1; }
        .spp-activity-active::before { background: #0d6efd; box-shadow: 0 0 0 3px rgba(13, 110, 253, .2); }
        .spp-modal-section { border: 1px solid rgba(15, 23, 42, 0.08); border-radius: .75rem; padding: 1.25rem; background: #fff; margin-bottom: 1rem; }
        
        .timeline-wrapper { display: flex; align-items: center; justify-content: space-between; position: relative; padding: 2rem 0; }
        .timeline-line { position: absolute; top: 3.25rem; left: 10%; right: 10%; height: 3px; background: #e2e8f0; z-index: 1; }
        .timeline-step { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; text-align: center; flex: 1; }
        .timeline-icon { width: 44px; height: 44px; border-radius: 999px; background: #fff; border: 3px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #94a3b8; font-weight: bold; margin-bottom: .75rem; transition: all 0.2s; }
        .timeline-label { font-weight: 600; color: #475569; font-size: .85rem; line-height: 1.3; }
        .timeline-sub { font-size: .75rem; color: #94a3b8; margin-top: .25rem; max-width: 150px; }
        
        /* Active / Passed Steps */
        .timeline-step.passed .timeline-icon { border-color: #10b981; background: #10b981; color: #fff; }
        .timeline-step.active .timeline-icon { border-color: #3b82f6; color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, .2); }
        .timeline-step.revision .timeline-icon { border-color: #ef4444; color: #ef4444; background: #fee2e2; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <x-page-title title="Workspace Operator BLU" subtitle="Detail & Persiapan Draft SPP Honorarium" />
        <a href="{{ route('spps.honor.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div>{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                <div>
                    <div class="fw-semibold mb-1">Masih ada data yang perlu diperbaiki.</div>
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- A. HEADER KERJA -->
    <div class="spp-workspace-hero p-4 rounded-3 shadow-sm">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
            <div class="flex-grow-1">
                <h3 class="fw-bold mb-2 text-dark">{{ $tagihan->deskripsi ?? 'Pembuatan SPP Honorarium' }}</h3>
                <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
                    <span class="badge {{ $statusTagihanClass }} px-3 py-2">Tagihan: {{ str_replace('_', ' ', $tagihan->status) }}</span>
                    <span class="badge {{ $statusSppClass }} px-3 py-2">SPP: {{ $statusSppLabel }}</span>
                    @if($sppModel && $sppModel->status === 'Revisi')
                        <span class="badge bg-danger px-3 py-2"><i class="bi bi-exclamation-circle me-1"></i> Butuh Perbaikan</span>
                    @endif
                </div>

                <div class="row g-3">
                    <div class="col-md-3 col-6"><div class="spp-summary-tile"><div class="label">Nomor Tagihan</div><div class="value">{{ $tagihan->nomor_tagihan ?? '-' }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="spp-summary-tile"><div class="label">Total Penerima</div><div class="value">{{ $tagihan->detailHonorarium->count() }} Orang</div></div></div>
                    <div class="col-md-3 col-6"><div class="spp-summary-tile"><div class="label">Tanggal Pengajuan</div><div class="value">{{ $tagihan->created_at->format('d M Y') }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="spp-summary-tile"><div class="label">Nilai Netto</div><div class="value text-success fs-6">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</div></div></div>
                </div>
            </div>

            @php
                $sppFullyApproved = $sppModel && in_array($sppModel->status, ['APPROVED', 'DISETUJUI_SPP', 'SPP_TERBIT']);
            @endphp
            <div class="d-flex flex-column gap-2" style="min-width: 200px;">
                @if($sppModel)
                    <a href="{{ route('spps.cetak-pdf', $sppModel->id) }}" target="_blank" class="btn btn-outline-danger shadow-sm"><i class="bi bi-file-earmark-pdf me-1"></i> Cetak PDF SPP</a>
                @endif

                @if(!$sppFullyApproved)
                    <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalSppHonor" {{ $canEditSpp ? '' : 'disabled' }}>
                        <i class="bi bi-pencil-square me-1"></i> {{ $sppModel ? 'Edit Draft SPP' : 'Buat Draft Baru' }}
                    </button>
                @endif

                @if($sppModel)
                    @if($sppFullyApproved)
                        <button type="button" class="btn btn-outline-primary shadow-sm w-100" data-bs-toggle="modal" data-bs-target="#modalUploadSignedSpp">
                            <i class="bi bi-upload me-1"></i> {{ $sppModel->hasSignedSppFile() ? 'Upload Ulang SPP Scan' : 'Upload Scan SPP' }}
                        </button>
                        
                        @if($sppModel->hasSignedSppFile())
                            @hasanyrole('Super Admin|Operator BLU')
                                <a href="{{ route('spms.honor.detail', $sppModel->id) }}" class="btn btn-success shadow-sm w-100 mt-1">
                                    <i class="bi bi-arrow-right-circle me-1"></i> {{ $sppModel->spm ? 'Lanjutkan SPM' : 'Lanjut Buat SPM' }}
                                </a>
                                <div class="small text-success text-center mt-1">
                                    <i class="bi bi-check-circle-fill me-1"></i> SPP disetujui & scan diunggah.
                                </div>
                            @else
                                <div class="alert alert-success border-0 small mb-0 py-2 text-center mt-1">
                                    <i class="bi bi-check-circle-fill me-1"></i> SPP telah disetujui & scan diunggah.
                                </div>
                            @endhasanyrole
                        @else
                            <div class="small text-danger text-center mt-1">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Wajib upload SPP scan untuk lanjut.
                            </div>
                        @endif
                    @elseif($canSubmitToPpk && $isReadyToSubmit)
                        <form action="{{ route('spps.honor.submit', $tagihan->id) }}" method="POST" onsubmit="return confirm('Ajukan SPP ini untuk verifikasi PPK dan Kasubbag secara paralel?')">
                            @csrf
                            <button type="submit" class="btn btn-success shadow-sm w-100"><i class="bi bi-send me-1"></i> Ajukan Verifikasi</button>
                        </form>
                    @else
                        <button type="button" class="btn btn-success shadow-sm w-100" disabled><i class="bi bi-send me-1"></i> Ajukan Verifikasi</button>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <!-- B. PANEL STATUS & KESIAPAN -->
    <div class="card spp-section-card mb-4 border-primary border-opacity-25" style="background-color: #f8fbff;">
        <div class="card-body p-4">
            <h5 class="fw-bold text-primary mb-4"><i class="bi bi-shield-check me-2"></i> Status Kesiapan & Progress Verifikasi</h5>
            
            <div class="row g-4 align-items-center">
                <div class="col-xl-5">
                    <div class="bg-white p-3 rounded-3 border shadow-sm h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Checklist Draft SPP</h6>
                            <span class="badge {{ $readinessStatus['class'] ?? 'bg-secondary' }}">{{ $readinessStatus['label'] ?? 'Status Tidak Tersedia' }}</span>
                        </div>
                        
                        <div style="font-size: 0.9rem;">
                            @foreach($readinessChecklist as $item)
                                <div class="spp-readiness-item py-1">
                                    <span class="spp-readiness-icon {{ $item['status'] === 'ready' ? 'spp-icon-ready' : 'spp-icon-missing' }}"><i class="bi {{ $item['status'] === 'ready' ? 'bi-check2' : 'bi-x-lg' }}"></i></span>
                                    <div>{{ $item['label'] }}</div>
                                </div>
                            @endforeach
                        </div>

                        @if(($readinessStatus['label'] ?? null) === 'Belum Lengkap' && $readinessIssues->isNotEmpty())
                            <div class="alert alert-warning mt-3 mb-0 p-2 py-1 small border-0">
                                <ul class="mb-0 ps-3">
                                    @foreach($readinessIssues as $issue)<li>{{ $issue }}</li>@endforeach
                                </ul>
                            </div>
                        @endif

                        @if(!empty($readinessStatus['message']))
                            <div class="small text-muted mt-3">{{ $readinessStatus['message'] }}</div>
                        @endif
                    </div>
                </div>

                <div class="col-xl-7">
                    <div class="bg-white p-3 rounded-3 border shadow-sm h-100">
                        <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-people me-2"></i> Status Verifikator SPP</h6>
                        <ul class="list-group mb-0">
                            <!-- PPK -->
                            <li class="list-group-item px-3 py-2 border-start-0 border-end-0 border-top-0 border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            <i class="bi bi-person-check fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">Pejabat Pembuat Komitmen</div>
                                            <div class="small text-muted">{{ $ppkUser?->name ?? 'Belum Ditentukan' }}</div>
                                            @if($ppkUser?->nip)<div class="text-muted font-monospace" style="font-size: .72rem;">NIP: {{ $ppkUser->nip }}</div>@endif
                                        </div>
                                    </div>
                                    <span class="badge {{ $ppkStatusClass }}">{{ $ppkStatusLabel }}</span>
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
        <!-- C. KOLOM KIRI (SUMBER DATA) -->
        <div class="col-xl-7">
            
            <div class="card spp-section-card mb-4">
                <div class="card-body p-4">
                    <div class="spp-section-heading text-primary"><i class="bi bi-receipt"></i> 1. Rincian Tagihan Honorarium</div>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="spp-info-block"><div class="label">Nomor Tagihan</div><div class="value">{{ $tagihan->nomor_tagihan ?? '-' }}</div></div></div>
                        <div class="col-md-6"><div class="spp-info-block"><div class="label">Uraian / Deskripsi</div><div class="value">{{ $tagihan->deskripsi ?? '-' }}</div></div></div>
                        <div class="col-md-4"><div class="spp-info-block"><div class="label">Nilai Bruto (Honor)</div><div class="value fw-bold text-dark">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="spp-info-block"><div class="label">Total Potongan (PPh 21)</div><div class="value fw-bold text-danger">Rp {{ number_format($tagihan->total_potongan, 0, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="spp-info-block"><div class="label">Nilai Netto (Dibayarkan)</div><div class="value text-success fs-5 fw-bold">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</div></div></div>
                    </div>
                </div>
            </div>

            <div class="card spp-section-card mb-4">
                <div class="card-body p-4">
                    <div class="spp-section-heading text-primary"><i class="bi bi-people"></i> 2. Daftar Penerima Honorarium</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle border mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-2 text-center" style="width: 5%">No</th>
                                    <th class="py-2">Penerima</th>
                                    <th class="py-2 text-end">Bruto</th>
                                    <th class="py-2 text-end">PPh</th>
                                    <th class="py-2 text-end">Netto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tagihan->detailHonorarium as $idx => $detail)
                                    <tr>
                                        <td class="text-center">{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $detail->nama_personel }}</div>
                                            <div class="text-muted small">{{ $detail->nrp_nip ?? '-' }}</div>
                                        </td>
                                        <td class="text-end fw-semibold">Rp {{ number_format($detail->nilai_honor, 0, ',', '.') }}</td>
                                        <td class="text-end text-danger small">Rp {{ number_format($detail->pph, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold text-success">Rp {{ number_format($detail->nilai_honor - $detail->pph, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Belum ada rincian penerima</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card spp-section-card mb-4">
                <div class="card-body p-4">
                    <div class="spp-section-heading text-primary"><i class="bi bi-paperclip"></i> 3. Dokumen Lampiran Penunjang</div>
                    <div>
                        @foreach($documentStatuses as $document)
                            @php($docMeta = $documentStatusMeta[$document['status']] ?? $documentStatusMeta['missing'])
                            <div class="spp-doc-row">
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

        </div>

        <!-- D. KOLOM KANAN (HASIL & VALIDASI) -->
        <div class="col-xl-5">
            <div class="sticky-top" style="top: 1.5rem; z-index: 1;">
                
                <div class="card spp-section-card mb-4">
                    <div class="card-body p-4">
                        <div class="spp-section-heading text-primary"><i class="bi bi-wallet2"></i> Validasi Anggaran</div>
                        <div class="spp-info-block mb-3"><div class="label">DIPA / Tahun / Revisi (Dari Verifikasi)</div><div class="value">{{ $dipa->nomor_dipa ?? '-' }} <span class="text-muted fw-normal">(Thn: {{ $dipa->tahun_anggaran ?? '-' }}, Rev: {{ $dipa->revisi_aktif_ke ?? '-' }})</span></div></div>
                        
                        <div class="p-3 bg-light rounded border border-primary border-opacity-25">
                            <div class="label text-primary fw-bold small mb-1">Item DIPA / Akun Terpakai</div>
                            <div class="value fs-5">@if($selectedBudgetItem?->coa) {{ $selectedBudgetItem->coa->kode_mak_lengkap }} @else <span class="text-danger">Belum Tersedia</span> @endif</div>
                            <div class="text-muted small lh-sm mt-1">{{ $selectedBudgetItem?->coa?->nama_akun ?? 'Tagihan Honorarium belum terkait dgn Item DIPA.' }}</div>
                        </div>
                    </div>
                </div>



                <!-- HIGHLIGHT: HASIL DRAFT SPP -->
                <div class="card spp-section-card mb-4 border-primary shadow-sm">
                    <div class="card-header bg-primary text-white p-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-check me-2"></i> Ringkasan Draft SPP</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted fw-semibold">Status</span>
                            <span class="badge {{ $statusSppClass }} fs-6">{{ $statusSppLabel }}</span>
                        </div>
                        <div class="spp-info-block mb-2"><div class="label">Nomor SPP</div><div class="value">{{ $sppModel->nomor_spp ?? '-' }}</div></div>
                        <div class="spp-info-block mb-2"><div class="label">Tanggal SPP</div><div class="value">{{ optional($sppModel?->tanggal_spp)->format('d F Y') ?? '-' }}</div></div>
                        <div class="spp-info-block mb-3"><div class="label">Nilai SPP Kategori BLU - TRF</div><div class="value text-primary fs-5">Rp {{ number_format($nominalSpp, 0, ',', '.') }}</div></div>
                        
                        <div class="p-2 bg-light rounded text-center small text-muted">
                            Mode Dokumen: {{ $workflowLockLabel }}
                        </div>
                    </div>
                </div>

                <div class="card spp-section-card mb-4">
                    <div class="card-body p-4">
                        <div class="spp-section-heading text-primary"><i class="bi bi-clock-history"></i> Aktivitas Workflow</div>
                        
                        <div class="mt-2">
                            @forelse($recentActivities as $idx => $activity)
                                <div class="spp-activity-row {{ $idx === 0 ? 'spp-activity-active' : '' }}">
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

    <!-- F. MODAL DRAFT SPP -->
    <div class="modal fade" id="modalSppHonor" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form action="{{ route('spps.honor.store', $tagihan->id) }}" method="POST" class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>{{ $sppModel ? 'Edit Draft SPP Honorarium' : 'Buat Draft SPP Honorarium Baru' }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    @if($sppModel && $sppModel->status === 'Revisi')
                        <div class="alert alert-danger mb-4 shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Dokumen ini dikembalikan karena memerlukan revisi. Silakan sesuaikan data.</div>
                    @endif

                    <fieldset class="border-0 p-0 m-0" {{ $canEditSpp ? '' : 'disabled' }}>
                        
                        <!-- SEC 1: INFO DASAR -->
                        <div class="spp-modal-section shadow-sm">
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-1-circle me-1"></i> Informasi Dasar SPP</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nomor SPP <span class="text-danger">*</span></label>
                                    <input type="text" name="nomor_spp" class="form-control fw-bold text-primary bg-light" required value="{{ old('nomor_spp', $sppModel->nomor_spp ?? $autoNomorSpp) }}" placeholder="Ketik nomor SPP">
                                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Nomor di atas digenerate otomatis, ubah jika perlu.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Tanggal SPP <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_spp" class="form-control" required value="{{ old('tanggal_spp', optional($sppModel?->tanggal_spp)->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nominal SPP Akhir (Otomatis)</label>
                                    <input type="text" class="form-control fw-bold text-success bg-white" value="Rp {{ number_format($nominalSpp, 0, ',', '.') }}" readonly>
                                    <div class="form-text">Nilai netto hasil pengurangan potongan pajak.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Uraian SPP</label>
                                    <textarea name="uraian" class="form-control" rows="2">{{ old('uraian', $sppModel->uraian ?? $tagihan->deskripsi) }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Jenis Tagihan</label>
                                    <select name="jenis_tagihan" class="form-select">
                                        <option value="NON REMUNERASI" {{ old('jenis_tagihan', $sppModel?->jenis_tagihan) === 'NON REMUNERASI' ? 'selected' : '' }}>NON REMUNERASI</option>
                                        <option value="REMUNERASI" {{ old('jenis_tagihan', $sppModel?->jenis_tagihan) === 'REMUNERASI' ? 'selected' : '' }}>REMUNERASI</option>
                                    </select>
                                    <div class="form-text">Kategori tagihan yang akan ditampilkan pada PDF SPP & SPM.</div>
                                </div>
                            </div>
                        </div>

                        <!-- SEC 2: VERIFIKATOR -->
                        <div class="spp-modal-section shadow-sm mb-0">
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-2-circle me-1"></i> Penugasan Verifikator (Paralel)</h6>
                            <div class="alert alert-info border-0 py-2 small mb-3">
                                <i class="bi bi-info-circle me-1"></i> Mode verifikasi paralel aktif. Dokumen ini akan diperiksa secara bersamaan setelah diajukan.
                            </div>
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-dark">Verifikator PPK</label>
                                    <input type="text" class="form-control bg-light" value="{{ $ppkUser->name ?? 'PPK Tidak Tersedia (Otomatis)' }}" readonly>
                                    <input type="hidden" name="ppk_verifikator_id" value="{{ $ppkUser->id ?? '' }}">
                                    <div class="form-text">Otomatis berdasarkan verifikator tagihan.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-dark">Koordinator Keuangan</label>
                                    <input type="text" class="form-control bg-light" value="{{ $koordinatorUser->name ?? 'Koordinator Keuangan Tidak Tersedia (Otomatis)' }}" readonly>
                                    <div class="form-text">Koordinator Keuangan otomatis ditentukan oleh sistem.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-dark">Verifikator Kasubbag</label>
                                    <input type="text" class="form-control bg-light" value="{{ $kasubbagUser->name ?? 'Kasubbag Tidak Tersedia (Otomatis)' }}" readonly>
                                </div>
                            </div>
                        </div>

                    </fieldset>
                </div>

                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    @if($canEditSpp)
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Simpan Draft SPP</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
    @if($sppModel && in_array($sppModel->status, ['APPROVED', 'DISETUJUI_SPP', 'Disetujui PPK', 'SPP_TERBIT']))
    <!-- Modal Upload Scan SPP -->
    <div class="modal fade" id="modalUploadSignedSpp" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog">
            <form action="{{ route('spps.upload-signed', $sppModel->id) }}" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-upload me-2"></i> Upload Scan SPP</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="alert alert-info border-0 p-3 mb-4 small shadow-sm">
                        <i class="bi bi-info-circle-fill me-1"></i> Silakan unggah dokumen SPP yang telah dicetak dan ditandatangani basah oleh seluruh pihak terkait.
                    </div>
                    @if($sppModel->hasSignedSppFile())
                        <div class="d-flex align-items-center justify-content-between mb-4 p-3 bg-success bg-opacity-10 border border-success rounded shadow-sm">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-check-lg fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-success mb-0">File Terunggah</div>
                                    <div class="small text-muted">{{ $sppModel->signedSppArsip?->nama_file_asli ?? 'Dokumen SPP Final' }}</div>
                                </div>
                            </div>
                            <a href="{{ \Illuminate\Support\Facades\Storage::url($sppModel->signedSppArsip?->path_file) }}" target="_blank" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-search"></i> Lihat
                            </a>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih File Scan (PDF/JPG/PNG) <span class="text-danger">*</span></label>
                        <input type="file" name="file_spp_ttd" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-upload me-1"></i> {{ $sppModel->hasSignedSppFile() ? 'Timpa File' : 'Unggah File' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if($errors->any() && old('nomor_spp'))
            const modalElement = document.getElementById('modalSppHonor');
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        @endif
    });
</script>
@endpush
