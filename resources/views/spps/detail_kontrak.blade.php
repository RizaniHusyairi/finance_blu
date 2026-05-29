@extends('layouts.app')
@section('title', 'Detail SPP Kontrak')

@php
    $vendor = $kontrak?->vendor;
    $rekening = $vendor?->rekening?->first();
    $dipa = $kontrak?->dipa;
    $activeRevision = $dipa?->activeRevision;
    $selectedBudgetItem = $selectedBudgetItem ?? ($sppModel?->dipaRevisionItem ?? $budgetItems->first());
    $potonganTagihans = collect($tagihan->potonganTagihan ?? $tagihan->potongans ?? []);
    $potonganAngsuranUm = $potonganTagihans->firstWhere('jenis_potongan', 'ANGSURAN_UANG_MUKA');
    $potonganPajak = $potonganTagihans->filter(fn ($item) => $item->jenis_potongan !== 'ANGSURAN_UANG_MUKA');
    $potonganPajakForm = $potonganPajak->map(fn ($item) => [
        'id' => $item->pajak_id,
        'dpp' => (float) ($item->dpp ?? 0),
        'nominal' => (float) ($item->nominal_potongan ?? 0),
    ])->values();
    $pajakOptionsSppData = collect($pajaks ?? [])->map(fn ($pj) => [
        'id' => $pj->id,
        'text' => ($pj->jenis_pajak ?? $pj->nama_pajak) . ' (' . $pj->persentase . '%)',
        'tarif' => (float) $pj->persentase,
    ])->values();
    $isPelunasan = ($termin->jenis_termin ?? null) === 'PELUNASAN';
    $statusTagihanClass = match ($tagihan->status) {
        'READY_FOR_SPP' => 'bg-info',
        'PROSES_SPP' => 'bg-primary',
        'SPP_TERBIT' => 'bg-success',
        default => 'bg-secondary',
    };
    $statusSppLabel = $sppModel?->status ?? 'Belum Dibuat';
    $statusSppClass = match ($statusSppLabel) {
        'Belum Dibuat' => 'bg-secondary',
        'DRAFT' => 'bg-warning text-dark',
        'Menunggu Verifikasi' => 'bg-info',
        'Disetujui PPK' => 'bg-success',
        'Revisi' => 'bg-danger',
        'APPROVED' => 'bg-success',
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
    } elseif ($sppModel && in_array($sppModel->status, ['APPROVED', 'Disetujui PPK'])) {
        $progressStep = 3;
        // If workflow is APPROVED
        if (optional($sppModel->workflowInstances->first())->status === 'APPROVED') {
            $progressStep = 4;
        }
    }
@endphp

@push('css')
    <style>
        body[data-bs-theme="blue-theme"] .main-content { background: #f4f6fa; }

        /* ============ ANIMATIONS ============ */
        @keyframes fadeUpIn { 0% { opacity: 0; transform: translateY(20px); } 100% { opacity: 1; transform: translateY(0); } }
        @keyframes scaleIn  { 0% { opacity: 0; transform: scale(0.95); } 100% { opacity: 1; transform: scale(1); } }
        @keyframes floatAnim { 0%, 100% { transform: translateY(0) rotate(-5deg); } 50% { transform: translateY(-15px) rotate(2deg); } }
        @keyframes pulseGlow { 0% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4); } 70% { box-shadow: 0 0 0 15px rgba(13, 110, 253, 0); } 100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); } }

        .anim-fade-up { animation: fadeUpIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }

        /* ============ HERO SECTION ============ */
        .spp-workspace-hero { 
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); 
            color: white;
            border-radius: 1.5rem; 
            padding: 2.5rem 2rem; 
            margin-bottom: 2.5rem; 
            position: relative; 
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(59, 130, 246, 0.4);
            animation: scaleIn 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .spp-workspace-hero::before, .spp-workspace-hero::after {
            content: ''; position: absolute; border-radius: 50%; z-index: 0;
        }
        .spp-workspace-hero::before { right: -50px; top: -50px; width: 300px; height: 300px; background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%); }
        .spp-workspace-hero::after { right: 150px; bottom: -100px; width: 250px; height: 250px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%); }
        .hero-content { position: relative; z-index: 1; }
        .hero-title { font-weight: 800; letter-spacing: -0.03em; margin-bottom: 0.5rem; text-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .hero-floating-icon { position: absolute; right: 2rem; top: 15%; font-size: 8rem; opacity: 0.15; z-index: 0; animation: floatAnim 8s ease-in-out infinite; }

        .spp-summary-tile { 
            background: rgba(255, 255, 255, 0.1); 
            backdrop-filter: blur(12px); 
            -webkit-backdrop-filter: blur(12px); 
            border: 1px solid rgba(255, 255, 255, 0.2); 
            border-radius: 1rem; 
            padding: 1.25rem; 
            height: 100%; 
            transition: all 0.3s ease;
        }
        .spp-summary-tile:hover { transform: translateY(-5px); background: rgba(255, 255, 255, 0.15); border-color: rgba(255, 255, 255, 0.3); }
        .spp-summary-tile .label { color: rgba(255,255,255,0.8); font-size: 0.75rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; margin-bottom: 0.4rem; }
        .spp-summary-tile .value { color: #fff; font-weight: 700; font-size: 1.1rem; line-height: 1.3; }

        /* ============ CARDS & GLASSMORPHISM ============ */
        .spp-section-card { 
            border: none; 
            border-radius: 1.25rem; 
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); 
            background: rgba(255, 255, 255, 0.85); 
            backdrop-filter: blur(20px); 
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.6);
            overflow: hidden; 
            transition: all 0.3s ease;
        }
        .spp-section-card:hover { transform: translateY(-3px); box-shadow: 0 15px 35px -10px rgba(0,0,0,0.08); }
        .spp-section-heading { color: #1e293b; font-size: 0.95rem; font-weight: 800; letter-spacing: 0.02em; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.6rem; }
        .spp-section-heading i { color: #3b82f6; font-size: 1.2rem; background: #eff6ff; padding: 0.5rem; border-radius: 0.75rem; }

        /* ============ INFO BLOCKS ============ */
        .spp-info-block { margin-bottom: 1.25rem; position: relative; padding-left: 1rem; border-left: 3px solid #e2e8f0; transition: border-color 0.2s ease; }
        .spp-info-block:hover { border-left-color: #3b82f6; }
        .spp-info-block .label { color: #64748b; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem; }
        .spp-info-block .value { font-weight: 700; color: #0f172a; font-size: 1rem; line-height: 1.4; }

        /* ============ READINESS & STATUS ============ */
        .spp-readiness-item { display: flex; align-items: center; gap: 1rem; padding: 0.85rem 1rem; border-radius: 0.75rem; background: #f8fafc; margin-bottom: 0.5rem; transition: background 0.2s ease; border: 1px solid transparent; }
        .spp-readiness-item:hover { background: #fff; border-color: #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .spp-readiness-icon { width: 2rem; height: 2rem; border-radius: 0.5rem; display: inline-flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .spp-icon-ready { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .spp-icon-missing { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }

        /* ============ INTERACTIVE BUTTONS ============ */
        .btn-pulse { animation: pulseGlow 2s infinite; }
        .btn-glow { position: relative; overflow: hidden; z-index: 1; }
        .btn-glow::after { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 60%); opacity: 0; transform: scale(0.5); transition: opacity 0.3s, transform 0.3s; z-index: -1; }
        .btn-glow:hover::after { opacity: 1; transform: scale(1); }

        /* ============ BADGES ============ */
        .badge-glass { background: rgba(255,255,255,0.2) !important; color: white !important; border: 1px solid rgba(255,255,255,0.4); backdrop-filter: blur(5px); }

        /* ============ VERIFICATOR LIST ============ */
        .verificator-list .list-group-item { border: none; padding: 1rem; margin-bottom: 0.5rem; border-radius: 1rem !important; background: #f8fafc; transition: all 0.3s ease; border: 1px solid transparent; }
        .verificator-list .list-group-item:hover { background: #fff; border-color: #e2e8f0; box-shadow: 0 5px 15px rgba(0,0,0,0.03); transform: scale(1.01); }
        .verificator-icon { width: 45px; height: 45px; border-radius: 1rem; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }

        .spp-activity-row { position: relative; padding-left: 2rem; padding-bottom: 1.5rem; }
        .spp-activity-row::before { content: ""; position: absolute; left: 0.35rem; top: 0.25rem; width: 0.8rem; height: 0.8rem; border-radius: 50%; background: #cbd5e1; z-index: 2; border: 2px solid #fff; box-shadow: 0 0 0 2px #e2e8f0; }
        .spp-activity-row::after { content: ""; position: absolute; left: 0.7rem; top: 1rem; bottom: 0; width: 2px; background: #e2e8f0; z-index: 1; }
        .spp-activity-row:last-child::after { display: none; }
        .spp-activity-active::before { background: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3); border-color: #fff; }

        .spp-doc-row { display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-radius: 1rem; background: #f8fafc; margin-bottom: 0.75rem; transition: all 0.2s ease; border: 1px solid transparent; }
        .spp-doc-row:hover { background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border-color: #e2e8f0; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <x-page-title title="Workspace Operator BLU" subtitle="Detail & Persiapan Draft SPP Kontrak" />
        <a href="{{ route('spps.kontrak.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
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

    <!-- A. HEADER KERJA (HERO SECTION) -->
    <div class="spp-workspace-hero p-5 shadow-sm">
        <i class="bi bi-file-earmark-medical hero-floating-icon"></i>
        <div class="hero-content d-flex flex-column flex-xl-row justify-content-between gap-4">
            <div class="flex-grow-1">
                <h2 class="hero-title text-white">{{ $kontrak->nama_pekerjaan ?? $tagihan->deskripsi ?? 'Pembuatan SPP Kontrak' }}</h2>
                <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
                    <span class="badge badge-glass px-3 py-2 fw-semibold"><i class="bi bi-tag-fill me-1"></i> Tagihan: {{ $tagihan->status }}</span>
                    <span class="badge bg-white text-dark shadow-sm px-3 py-2 fw-semibold"><i class="bi bi-layers-fill text-primary me-1"></i> SPP: {{ $statusSppLabel }}</span>
                    @if($sppModel && $sppModel->status === 'Revisi')
                        <span class="badge bg-danger shadow-sm px-3 py-2 anim-pulse"><i class="bi bi-exclamation-triangle-fill me-1"></i> Butuh Perbaikan</span>
                    @endif
                </div>

                <div class="row g-3">
                    <div class="col-md-3 col-6"><div class="spp-summary-tile"><div class="label">Nomor SPK</div><div class="value text-truncate">{{ $kontrak->nomor_spk ?? '-' }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="spp-summary-tile"><div class="label">Vendor</div><div class="value text-truncate">{{ $vendor->nama_pihak ?? $vendor->nama_perusahaan ?? '-' }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="spp-summary-tile"><div class="label">Termin</div><div class="value">Ke-{{ $termin->termin_ke ?? '-' }} @if(!empty($termin->persentase)) <span class="fs-6 opacity-75 fw-normal">({{ $termin->persentase }}%)</span>@endif</div></div></div>
                    <div class="col-md-3 col-6"><div class="spp-summary-tile" style="background: rgba(16, 185, 129, 0.2); border-color: rgba(16, 185, 129, 0.4);"><div class="label text-white">Nilai Netto</div><div class="value fs-5">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</div></div></div>
                </div>
            </div>

            @php
                $sppFullyApproved = $sppModel && in_array($sppModel->status, ['APPROVED', 'DISETUJUI_SPP', 'SPP_TERBIT']);
            @endphp
            <div class="d-flex flex-column gap-3 justify-content-center" style="min-width: 240px; z-index: 2;">
                @if($sppModel)
                    <a href="{{ route('spps.cetak-pdf', $sppModel->id) }}" target="_blank" class="btn btn-light text-danger fw-bold shadow-sm btn-glow rounded-pill py-2"><i class="bi bi-file-earmark-pdf-fill me-1"></i> Cetak PDF SPP</a>
                @endif

                @if(!$sppFullyApproved)
                    <button type="button" class="btn btn-warning text-dark fw-bold shadow-sm btn-glow rounded-pill py-2" data-bs-toggle="modal" data-bs-target="#modalSppKontrak" {{ $canEditSpp ? '' : 'disabled' }}>
                        <i class="bi bi-pencil-square me-1"></i> {{ $sppModel ? 'Edit Draft SPP' : 'Buat Draft Baru' }}
                    </button>
                @endif

                @if($sppModel)
                    @if($sppFullyApproved)
                        <div class="badge bg-success bg-opacity-25 border border-success text-white py-2 px-3 rounded-pill text-center w-100">
                            <i class="bi bi-patch-check-fill me-1"></i> SPP final ber-TTE otomatis
                        </div>
                        @hasanyrole('Super Admin|Operator BLU')
                            <a href="{{ route('spms.kontrak.detail', $sppModel->id) }}" class="btn btn-success fw-bold shadow btn-pulse rounded-pill py-2 mt-2">
                                <i class="bi bi-arrow-right-circle-fill me-1"></i> {{ $sppModel->spm ? 'Lanjutkan SPM' : 'Buat SPM Sekarang' }}
                            </a>
                        @endhasanyrole
                    @elseif($canSubmitToPpk && $isReadyToSubmit)
                        <form action="{{ route('spps.kontrak.submit', $tagihan->id) }}" method="POST" onsubmit="return confirm('Yakin akan mengajukan verifikasi SPP ini? Proses ini tidak bisa dibatalkan.')">
                            @csrf
                            <button type="submit" class="btn btn-success fw-bold shadow btn-pulse rounded-pill py-2 w-100"><i class="bi bi-send-fill me-1"></i> Ajukan Verifikasi</button>
                        </form>
                    @else
                        <button type="button" class="btn btn-success opacity-50 fw-bold shadow-sm rounded-pill py-2 w-100" disabled><i class="bi bi-send-fill me-1"></i> Ajukan Verifikasi</button>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <!-- B. PANEL STATUS & KESIAPAN -->
    <div class="card spp-section-card mb-4 anim-fade-up delay-1" style="background-color: #f8fbff; border: 1px solid rgba(13, 110, 253, 0.15);">
        <div class="card-body p-4">
            <h5 class="spp-section-heading text-primary mb-4"><i class="bi bi-shield-check-fill shadow-sm"></i> Status Kesiapan & Progress Verifikasi</h5>
            
            <div class="row g-4 align-items-center">
                <div class="col-xl-5">
                    <div class="bg-white p-3 rounded-3 border shadow-sm h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Checklist Operator</h6>
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
                    <div class="bg-white p-4 rounded-4 border shadow-sm h-100">
                        <h6 class="fw-bold text-secondary mb-4"><i class="bi bi-people-fill me-2 text-primary"></i> Tim Verifikator SPP (Paralel)</h6>
                        <div class="list-group list-group-flush verificator-list">
                            <!-- PPK -->
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="verificator-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-person-check-fill"></i></div>
                                    <div>
                                        <div class="fw-bold text-dark">Pejabat Pembuat Komitmen</div>
                                        <div class="small text-muted">{{ $ppkUser?->name ?? 'Belum Ditentukan' }}</div>
                                        @if($ppkUser?->nip)<div class="text-muted font-monospace opacity-75" style="font-size: .72rem;">NIP: {{ $ppkUser->nip }}</div>@endif
                                    </div>
                                </div>
                                <span class="badge {{ $ppkStatusClass }} bg-light border px-3 py-2 rounded-pill">{{ $ppkStatusLabel }}</span>
                            </div>
                            <!-- Koordinator -->
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="verificator-icon bg-info bg-opacity-10 text-info"><i class="bi bi-person-gear"></i></div>
                                    <div>
                                        <div class="fw-bold text-dark">Koordinator Keuangan</div>
                                        <div class="small text-muted">{{ $koordinatorUser?->name ?? 'Belum Ditentukan' }}</div>
                                        @if($koordinatorUser?->nip)<div class="text-muted font-monospace opacity-75" style="font-size: .72rem;">NIP: {{ $koordinatorUser->nip }}</div>@endif
                                    </div>
                                </div>
                                <span class="badge {{ $koordinatorStatusClass }} bg-light border px-3 py-2 rounded-pill">{{ $koordinatorStatusLabel }}</span>
                            </div>
                            <!-- Kasubbag -->
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="verificator-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-person-badge-fill"></i></div>
                                    <div>
                                        <div class="fw-bold text-dark">Kasubbag Keuangan</div>
                                        <div class="small text-muted">{{ $kasubbagUser?->name ?? 'Belum Ditentukan' }}</div>
                                        @if($kasubbagUser?->nip)<div class="text-muted font-monospace opacity-75" style="font-size: .72rem;">NIP: {{ $kasubbagUser->nip }}</div>@endif
                                    </div>
                                </div>
                                <span class="badge {{ $kasubbagStatusClass }} bg-light border px-3 py-2 rounded-pill">{{ $kasubbagStatusLabel }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>


    <div class="row g-4">
        <!-- C. KOLOM KIRI (SUMBER DATA) -->
        <div class="col-xl-7">
            
            <div class="card spp-section-card mb-4 anim-fade-up delay-2">
                <div class="card-body p-4 p-xl-5">
                    <div class="spp-section-heading"><i class="bi bi-receipt-cutoff shadow-sm"></i> 1. Ringkasan Tagihan</div>
                    <div class="row g-4">
                        <div class="col-md-6"><div class="spp-info-block"><div class="label">Nomor Tagihan</div><div class="value fs-6 font-monospace">{{ $tagihan->nomor_tagihan ?? '-' }}</div></div></div>
                        <div class="col-md-6"><div class="spp-info-block"><div class="label">Uraian / Deskripsi</div><div class="value">{{ $kontrak->nama_pekerjaan ?? ($tagihan->deskripsi ?? '-') }}</div></div></div>
                        <div class="col-md-4"><div class="spp-info-block"><div class="label">Nilai Bruto</div><div class="value">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="spp-info-block"><div class="label">Total Potongan</div><div class="value text-danger">Rp {{ number_format($tagihan->total_potongan, 0, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="spp-info-block border-success border-opacity-50"><div class="label text-success">Nilai Netto</div><div class="value text-success fs-5">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</div></div></div>
                    </div>
                </div>
            </div>

            <div class="card spp-section-card mb-4 anim-fade-up delay-2">
                <div class="card-body p-4 p-xl-5">
                    <div class="spp-section-heading"><i class="bi bi-file-earmark-ruled shadow-sm"></i> 2. Dasar Legal Termin & Dokumen Pribadi</div>
                    <div class="row g-4">
                        <div class="col-md-4"><div class="spp-info-block"><div class="label">Termin</div><div class="value fs-6">Termin {{ $termin->termin_ke ?? '-' }} <span class="badge bg-primary bg-opacity-10 text-primary ms-1 border">{{ $termin->jenis_termin ?? '-' }}</span></div></div></div>
                        <div class="col-md-4"><div class="spp-info-block"><div class="label">Persentase</div><div class="value fs-5">{{ $termin->persentase ?? 0 }}%</div></div></div>
                        <div class="col-md-4"><div class="spp-info-block"><div class="label">Nilai Bruto Termin</div><div class="value fs-6">Rp {{ number_format($termin->nilai_bruto_termin ?? 0, 0, ',', '.') }}</div></div></div>
                        
                        <div class="col-12"><hr class="my-1 border-secondary opacity-25"></div>
                        
                        <div class="col-md-6"><div class="spp-info-block"><div class="label">Berita Acara (BAPP)</div><div class="value font-monospace">{{ $detailKontrak->nomor_bapp ?? '-' }}</div><div class="small text-muted mt-1 opacity-75"><i class="bi bi-calendar-event me-1"></i>{{ optional($detailKontrak?->tanggal_bapp)->format('d M Y') ?? '-' }}</div></div></div>
                        <div class="col-md-6"><div class="spp-info-block"><div class="label">Berita Acara (BAP)</div><div class="value font-monospace">{{ $detailKontrak->nomor_bap ?? '-' }}</div><div class="small text-muted mt-1 opacity-75"><i class="bi bi-calendar-event me-1"></i>{{ optional($detailKontrak?->tanggal_bap)->format('d M Y') ?? '-' }}</div></div></div>
                        <div class="col-md-6"><div class="spp-info-block"><div class="label">Berita Acara (BAST) {{ $isPelunasan ? '' : '(Ops)' }}</div><div class="value font-monospace">{{ $detailKontrak->nomor_bast ?? '-' }}</div><div class="small text-muted mt-1 opacity-75"><i class="bi bi-calendar-event me-1"></i>{{ optional($detailKontrak?->tanggal_bast)->format('d M Y') ?? '-' }}</div></div></div>
                        <div class="col-md-6"><div class="spp-info-block"><div class="label">Nomor Invoice</div><div class="value font-monospace">{{ $detailKontrak->nomor_invoice ?? '-' }}</div><div class="small text-muted mt-1 opacity-75"><i class="bi bi-calendar-event me-1"></i>{{ optional($detailKontrak?->tanggal_invoice)->format('d M Y') ?? '-' }}</div></div></div>
                        
                        <div class="col-12"><hr class="my-1 border-secondary opacity-25"></div>
                        
                        <div class="col-md-4"><div class="spp-info-block border-warning border-opacity-50"><div class="label text-warning text-darken">Nama Pemeriksa</div><div class="value">{{ $detailKontrak->nama_pemeriksa ?? '-' }}</div></div></div>
                        <div class="col-md-4"><div class="spp-info-block border-warning border-opacity-50"><div class="label text-warning text-darken">Jabatan Pemeriksa</div><div class="value">{{ $detailKontrak->jabatan_pemeriksa ?? '-' }}</div></div></div>
                        <div class="col-md-4"><div class="spp-info-block border-warning border-opacity-50"><div class="label text-warning text-darken">NIP Pemeriksa</div><div class="value font-monospace">{{ $detailKontrak->nip_pemeriksa ?? '-' }}</div></div></div>
                    </div>
                </div>
            </div>

            <div class="card spp-section-card mb-4 anim-fade-up delay-3">
                <div class="card-body p-4 p-xl-5">
                    <div class="spp-section-heading"><i class="bi bi-percent shadow-sm text-danger"></i> 3. Ringkasan Potongan</div>
                    <div class="row g-4 mb-3">
                        <div class="col-md-4"><div class="spp-potongan-summary shadow-sm"><div class="label text-muted small fw-bold mb-1">Angsuran UM Tertahan</div><div class="fw-bold text-warning fs-5">Rp {{ number_format($potonganAngsuranUm->nominal_potongan ?? 0, 0, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="spp-potongan-summary shadow-sm"><div class="label text-muted small fw-bold mb-1">Total Pajak</div><div class="fw-bold text-danger fs-5">Rp {{ number_format($potonganPajak->sum('nominal_potongan'), 0, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="spp-potongan-summary shadow-sm" style="background: linear-gradient(135deg, #1e293b, #0f172a);"><div class="label text-white text-opacity-75 small fw-bold mb-1">Total Keseluruhan</div><div class="fw-bold text-white fs-5">Rp {{ number_format($tagihan->total_potongan, 0, ',', '.') }}</div></div></div>
                    </div>
                </div>
            </div>

            <div class="card spp-section-card mb-4 anim-fade-up delay-3">
                <div class="card-body p-4 p-xl-5">
                    <div class="spp-section-heading"><i class="bi bi-paperclip shadow-sm text-info"></i> 4. Lampiran Dokumen</div>
                    <div class="d-flex flex-column gap-3">
                        @foreach($documentStatuses as $document)
                            @php($docMeta = $documentStatusMeta[$document['status']] ?? $documentStatusMeta['missing'])
                            <div class="spp-doc-row shadow-sm">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge {{ $docMeta['class'] }} py-2 px-3 rounded-pill" style="min-width: 90px;">{{ $docMeta['label'] }}</span>
                                    <div class="fw-bold text-dark">{{ $document['label'] }}</div>
                                </div>
                                <div>
                                    @if($document['is_available'])
                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($document['path']) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold"><i class="bi bi-eye-fill me-1"></i> Lihat Dokumen</a>
                                    @else
                                        <span class="text-muted small opacity-50 fst-italic">Belum diunggah</span>
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
                
                <div class="card spp-section-card mb-4 anim-fade-up delay-4">
                    <div class="card-body p-4 p-xl-5">
                        <div class="spp-section-heading"><i class="bi bi-wallet2 shadow-sm text-primary"></i> Validasi Anggaran</div>
                        <div class="spp-info-block mb-4"><div class="label">Nomor DIPA / Tahun / Revisi</div><div class="value font-monospace">{{ $dipa->nomor_dipa ?? '-' }} <span class="text-muted fw-normal ms-2">(Thn: {{ $dipa->tahun_anggaran ?? '-' }}, Rev: {{ $dipa->revisi_aktif_ke ?? '-' }})</span></div></div>
                        
                        <div class="p-3 bg-light rounded-4 border border-primary border-opacity-25 shadow-sm">
                            <div class="label text-primary fw-bold small mb-1 text-uppercase">Item DIPA / COA Terpakai</div>
                            <div class="value fs-5 font-monospace fw-bold">@if($selectedBudgetItem?->coa) {{ $selectedBudgetItem->coa->kode_mak_lengkap }} @else <span class="text-danger">Belum Tersedia</span> @endif</div>
                            <div class="text-muted small lh-sm mt-1 fw-semibold">{{ $selectedBudgetItem?->coa?->nama_akun ?? 'SPP tidak memiliki tujuan mata anggaran.' }}</div>
                        </div>
                    </div>
                </div>

                <div class="card spp-section-card mb-4 anim-fade-up delay-4">
                    <div class="card-body p-4 p-xl-5">
                        <div class="spp-section-heading"><i class="bi bi-bank shadow-sm text-primary"></i> Vendor & Rekening</div>
                        <div class="spp-info-block mb-3"><div class="label">Vendor</div><div class="value">{{ $vendor->nama_pihak ?? $vendor->nama_perusahaan ?? '-' }}</div><div class="small text-muted mt-1 opacity-75">NPWP: {{ $vendor->npwp ?? '-' }}</div></div>
                        <hr class="border-secondary opacity-25 my-3">
                        <div class="spp-info-block mb-2"><div class="label">Info Bank</div><div class="value">{{ $rekening->nama_bank ?? '-' }}</div></div>
                        <div class="spp-info-block mb-2"><div class="label">Nomor Rekening</div><div class="value font-monospace fs-5 text-primary">{{ $rekening->nomor_rekening ?? 'BELUM ADA' }}</div></div>
                        <div class="spp-info-block mb-0"><div class="label">Atas Nama</div><div class="value">{{ $rekening->nama_rekening ?? '-' }}</div></div>
                    </div>
                </div>



                <!-- HIGHLIGHT: HASIL DRAFT SPP -->
                <div class="card spp-section-card mb-4 border-primary shadow-sm anim-fade-up delay-4" style="background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);">
                    <div class="card-header bg-primary bg-gradient text-white p-4 border-0">
                        <h6 class="mb-0 fw-bold fs-5"><i class="bi bi-file-earmark-check-fill me-2"></i> Ringkasan Draft SPP</h6>
                    </div>
                    <div class="card-body p-4 p-xl-5">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="text-muted fw-bold text-uppercase small">Status Dokumen</span>
                            <span class="badge {{ $statusSppClass }} fs-6 px-3 py-2 rounded-pill shadow-sm">{{ $statusSppLabel }}</span>
                        </div>
                        <div class="spp-info-block mb-3"><div class="label">Nomor SPP</div><div class="value font-monospace fs-6">{{ $sppModel->nomor_spp ?? '-' }}</div></div>
                        <div class="spp-info-block mb-3"><div class="label">Tanggal SPP</div><div class="value">{{ optional($sppModel?->tanggal_spp)->format('d F Y') ?? '-' }}</div></div>
                        <div class="spp-info-block mb-4"><div class="label">Nilai SPP</div><div class="value text-primary fs-3 fw-bold">Rp {{ number_format($nominalSpp, 0, ',', '.') }}</div></div>
                        
                        <div class="p-2 bg-white rounded-pill text-center small text-muted border border-primary border-opacity-25 shadow-sm fw-semibold">
                            <i class="bi bi-shield-lock-fill text-primary me-1"></i> Mode Dokumen: {{ $workflowLockLabel }}
                        </div>
                    </div>
                </div>

                <div class="card spp-section-card mb-4 anim-fade-up delay-4">
                    <div class="card-body p-4 p-xl-5">
                        <div class="spp-section-heading"><i class="bi bi-clock-history shadow-sm text-primary"></i> Aktivitas Workflow</div>
                        
                        <div class="mt-4">
                            @forelse($recentActivities as $idx => $activity)
                                <div class="spp-activity-row {{ $idx === 0 ? 'spp-activity-active' : '' }}">
                                    <div class="fw-bold text-dark mb-1">{{ $activity['title'] }}</div>
                                    <div class="small text-muted fw-semibold"><i class="bi bi-clock me-1"></i>{{ $activity['time'] ?? '-' }} &bull; <span class="text-primary">{{ $activity['actor'] }}</span></div>
                                    @if(!empty($activity['note']))
                                        <div class="small text-muted mt-2 lh-sm fst-italic p-2 bg-light rounded border-start border-3 border-primary">"{{ $activity['note'] }}"</div>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center text-muted small py-4 fw-semibold"><i class="bi bi-inbox fs-3 d-block mb-2 text-secondary opacity-50"></i> Belum ada aktivitas yang tercatat.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- F. MODAL DRAFT SPP -->
    <div class="modal fade" id="modalSppKontrak" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form action="{{ route('spps.kontrak.store', $tagihan->id) }}" method="POST" enctype="multipart/form-data" id="formSppKontrak" class="modal-content rounded-4 border-0 shadow-lg">
                @csrf
                <input type="hidden" name="jumlah_uang" id="jumlah_uang_spp" value="{{ old('jumlah_uang', $nominalSpp) }}">

                <div class="modal-header bg-primary bg-gradient text-white border-0 rounded-top-4 p-4">
                    <h5 class="modal-title fw-bold fs-4"><i class="bi bi-pencil-square me-2"></i>{{ $sppModel ? 'Edit Draft SPP Kontrak' : 'Buat Draft SPP Kontrak Baru' }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 p-xl-5 bg-light">
                    @if($sppModel && $sppModel->status === 'Revisi')
                        <div class="alert alert-danger mb-4 shadow-sm border-0 rounded-3"><i class="bi bi-exclamation-triangle-fill me-2"></i> Dokumen ini dikembalikan karena memerlukan revisi. Silakan sesuaikan data.</div>
                    @endif

                    <fieldset class="border-0 p-0 m-0" {{ $canEditSpp ? '' : 'disabled' }}>
                        
                        <!-- SEC 1: INFO DASAR -->
                        <div class="spp-modal-section shadow-sm bg-white p-4 rounded-4 border border-secondary border-opacity-10 mb-4">
                            <h6 class="fw-bold text-primary mb-4 border-bottom pb-2"><i class="bi bi-1-circle-fill me-2"></i> Informasi Dasar SPP</h6>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary">Nomor SPP <span class="text-danger">*</span></label>
                                    <input type="text" name="nomor_spp" class="form-control form-control-lg fw-bold text-primary bg-light border-0 shadow-none" required value="{{ old('nomor_spp', $sppModel->nomor_spp ?? $autoNomorSpp) }}" placeholder="Ketik nomor SPP">
                                    <small class="text-muted mt-2 d-block"><i class="bi bi-info-circle-fill text-info me-1"></i>Nomor digenerate otomatis, ubah jika perlu.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary">Tanggal SPP <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_spp" class="form-control form-control-lg" required value="{{ old('tanggal_spp', optional($sppModel?->tanggal_spp)->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary">Nominal SPP Akhir (Otomatis)</label>
                                    <input type="text" class="form-control form-control-lg fw-bold text-success bg-success bg-opacity-10 border-success border-opacity-25" id="jumlah_uang_spp_display" value="Rp {{ number_format($nominalSpp, 0, ',', '.') }}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary">Jenis Tagihan</label>
                                    <select name="jenis_tagihan" class="form-select form-select-lg">
                                        <option value="NON REMUNERASI" {{ old('jenis_tagihan', $sppModel?->jenis_tagihan) === 'NON REMUNERASI' ? 'selected' : '' }}>NON REMUNERASI</option>
                                        <option value="REMUNERASI" {{ old('jenis_tagihan', $sppModel?->jenis_tagihan) === 'REMUNERASI' ? 'selected' : '' }}>REMUNERASI</option>
                                    </select>
                                    <div class="form-text mt-1">Kategori tagihan untuk keperluan PDF.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold text-secondary">Uraian SPP</label>
                                    <textarea class="form-control bg-light border-0" rows="3" readonly>{{ $kontrak->nama_pekerjaan ?? ($tagihan->deskripsi ?? '-') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- SEC 2: PAJAK -->
                        <div class="spp-modal-section shadow-sm bg-white p-4 rounded-4 border border-secondary border-opacity-10 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                                <h6 class="fw-bold text-primary mb-0"><i class="bi bi-2-circle-fill me-2"></i> Komponen Potongan Pajak</h6>
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" id="btnTambahPajakSpp"><i class="bi bi-plus-lg me-1"></i> Tambah Pajak</button>
                            </div>
                            
                            <div class="row g-3 mb-4 pb-3 border-bottom bg-light p-3 rounded-3">
                                <div class="col-md-4"><div class="small fw-bold text-muted text-uppercase mb-1">Nilai Bruto Tagihan</div><div class="fs-6 font-monospace">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</div></div>
                                <div class="col-md-4"><div class="small fw-bold text-muted text-uppercase mb-1">Angsuran UM Tertahan</div><div class="fs-6 text-warning font-monospace">Rp {{ number_format($potonganAngsuranUm->nominal_potongan ?? 0, 0, ',', '.') }}</div></div>
                                <div class="col-md-4"><div class="small fw-bold text-muted text-uppercase mb-1">Total Potongan Pajak</div><div class="fs-5 text-danger fw-bold font-monospace" id="total_potongan_pajak_spp_display">Rp {{ number_format($potonganPajak->sum('nominal_potongan'), 0, ',', '.') }}</div></div>
                            </div>

                            <div id="containerPajakSpp">
                                <div class="text-center text-muted p-5 bg-light rounded-4 border border-dashed border-2 opacity-75" id="pajakSppKosong">
                                    <i class="bi bi-receipt fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    Tidak ada potongan pajak tambahan pada draft ini.
                                </div>
                            </div>
                        </div>

                        <!-- SEC 3: VERIFIKATOR -->
                        <div class="spp-modal-section shadow-sm bg-white p-4 rounded-4 border border-secondary border-opacity-10 mb-4">
                            <h6 class="fw-bold text-primary mb-4 border-bottom pb-2"><i class="bi bi-3-circle-fill me-2"></i> Penugasan Verifikator (Paralel)</h6>
                            <div class="alert alert-info border-0 p-3 rounded-3 small mb-4 d-flex gap-3 align-items-center bg-info bg-opacity-10">
                                <i class="bi bi-info-circle-fill text-info fs-3"></i>
                                <div>Mode verifikasi paralel aktif. Dokumen ini akan diperiksa oleh PPK, Koordinator Keuangan, dan Kasubbag secara bersamaan saat diajukan.</div>
                            </div>
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-secondary">Verifikator PPK</label>
                                    <input type="text" class="form-control bg-light border-0" value="{{ $ppkUser->name ?? 'PPK Tidak Tersedia (Otomatis)' }}" readonly>
                                    <input type="hidden" name="ppk_verifikator_id" value="{{ $ppkUser->id ?? '' }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-secondary">Koordinator Keuangan</label>
                                    <input type="text" class="form-control bg-light border-0" value="{{ $koordinatorUser->name ?? 'Koordinator Tidak Tersedia' }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-secondary">Kasubbag Keuangan</label>
                                    <input type="text" class="form-control bg-light border-0" value="{{ $kasubbagUser->name ?? 'Kasubbag Tidak Tersedia' }}" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- SEC 4: LAMPIRAN -->
                        <div class="spp-modal-section shadow-sm bg-white p-4 rounded-4 border border-secondary border-opacity-10 mb-0">
                            <h6 class="fw-bold text-primary mb-4 border-bottom pb-2"><i class="bi bi-4-circle-fill me-2"></i> Lampiran Ekstra SPP</h6>
                            <div class="row g-4">
                                <div class="col-12">
                                    <label class="form-label fw-bold text-secondary">Upload Faktur Pajak <span class="fw-normal text-muted small">(opsional)</span></label>
                                    <input type="file" name="file_faktur_pajak" class="form-control form-control-lg bg-light" accept=".pdf">
                                    @if($detailKontrak?->file_faktur_pajak)
                                        <div class="mt-3 p-2 bg-success bg-opacity-10 border border-success rounded-3 small d-inline-flex align-items-center gap-2">
                                            <i class="bi bi-check-circle-fill text-success"></i> 
                                            <a href="{{ \Illuminate\Support\Facades\Storage::url($detailKontrak->file_faktur_pajak) }}" target="_blank" class="text-decoration-none fw-bold text-success">File saat ini tersedia (Lihat)</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </fieldset>
                </div>

                <div class="modal-footer bg-white border-top p-4 rounded-bottom-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Batal</button>
                    @if($canEditSpp)
                        <button type="submit" class="btn btn-primary btn-pulse rounded-pill px-5 fw-bold"><i class="bi bi-save-fill me-2"></i> Simpan Draft SPP</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
<script>
    const pajakOptionsSpp = @json($pajakOptionsSppData);
    const existingPotonganPajakSpp = @json($potonganPajakForm);
    const brutoSpp = @json((float) $tagihan->total_bruto);
    const potonganUmSpp = @json((float) ($potonganAngsuranUm->nominal_potongan ?? 0));
    let pajakSppCounter = 0;

    document.addEventListener('DOMContentLoaded', function () {
        const tambahBtn = document.getElementById('btnTambahPajakSpp');
        if (tambahBtn) {
            tambahBtn.addEventListener('click', function () {
                tambahRowPajakSpp();
            });
        }

        if (existingPotonganPajakSpp.length > 0) {
            existingPotonganPajakSpp.forEach(function (item) {
                tambahRowPajakSpp(item);
            });
        }

        hitungTotalNettoSpp();

        @if($errors->any() && old('nomor_spp'))
            const modalElement = document.getElementById('modalSppKontrak');
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        @endif
    });

    function tambahRowPajakSpp(initialData = null) {
        const container = document.getElementById('containerPajakSpp');
        const emptyState = document.getElementById('pajakSppKosong');
        if (emptyState) {
            emptyState.style.display = 'none';
        }

        pajakSppCounter += 1;
        const rowId = pajakSppCounter;

        let optionsHtml = '<option value="">-- Pilih Jenis Pajak --</option>';
        pajakOptionsSpp.forEach(function (item) {
            const selected = String(initialData?.id ?? '') === String(item.id) ? 'selected' : '';
            optionsHtml += `<option value="${item.id}" data-tarif="${item.tarif}" ${selected}>${item.text}</option>`;
        });

        const dppValue = initialData?.dpp ?? 0;
        const nominalValue = initialData?.nominal ?? 0;

        const wrapper = document.createElement('div');
        wrapper.className = 'row g-3 mb-3 border p-3 rounded bg-white shadow-sm align-items-end pajak-row-spp';
        wrapper.id = `pajak_spp_row_${rowId}`;
        wrapper.innerHTML = `
            <div class="col-md-3">
                <label class="form-label small fw-bold">Jenis Pajak</label>
                <select class="form-select" name="pajak[${rowId}][id]" id="pajak_spp_sel_${rowId}" onchange="hitungPajakRowSpp(${rowId}, null, true)" required>
                    ${optionsHtml}
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Nilai DPP (Rp)</label>
                <input type="text" class="form-control" id="dpp_spp_display_${rowId}" value="${formatRupiahCustom(Math.round(dppValue))}" onkeyup="formatInputDppSpp(this, ${rowId})">
                <input type="hidden" name="pajak[${rowId}][dpp]" id="dpp_spp_val_${rowId}" value="${dppValue}">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-danger">Potongan Terhitung (Rp)</label>
                <input type="text" class="form-control bg-light fw-bold text-danger" id="potongan_spp_display_${rowId}" value="Rp ${formatRupiahCustom(Math.round(nominalValue))}" readonly>
                <input type="hidden" name="pajak[${rowId}][nominal]" id="potongan_spp_val_${rowId}" value="${nominalValue}" class="potongan-val-spp">
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-outline-danger w-100" onclick="hapusPajakRowSpp(${rowId})"><i class="bi bi-trash"></i></button>
            </div>
        `;

        container.appendChild(wrapper);
        hitungPajakRowSpp(rowId, initialData?.nominal ?? null);
    }

    function hapusPajakRowSpp(id) {
        const row = document.getElementById(`pajak_spp_row_${id}`);
        if (row) {
            row.remove();
        }

        if (document.querySelectorAll('.pajak-row-spp').length === 0) {
            document.getElementById('pajakSppKosong').style.display = 'block';
        }

        hitungTotalNettoSpp();
    }

    function formatInputDppSpp(input, id) {
        const clean = input.value.replace(/[^,\d]/g, '');
        document.getElementById(`dpp_spp_val_${id}`).value = clean || 0;
        input.value = formatRupiahCustom(clean || 0);
        hitungPajakRowSpp(id);
    }

    function hitungPajakRowSpp(id, presetNominal = null, isUserSelection = false) {
        const select = document.getElementById(`pajak_spp_sel_${id}`);
        const selected = select?.options[select.selectedIndex];
        const tarif = parseFloat(selected?.getAttribute('data-tarif') || 0);
        
        let dppInput = document.getElementById(`dpp_spp_val_${id}`);
        let dppDisplay = document.getElementById(`dpp_spp_display_${id}`);
        let dpp = parseFloat(dppInput?.value || 0);

        // Auto-fill DPP dengan nilai Bruto jika user baru memilih pajak dan DPP masih 0
        if (isUserSelection && dpp === 0 && tarif > 0) {
            dpp = brutoSpp;
            if (dppInput) dppInput.value = dpp;
            if (dppDisplay) dppDisplay.value = formatRupiahCustom(Math.round(dpp));
        }

        const hasil = presetNominal !== null ? parseFloat(presetNominal) || 0 : (dpp * tarif / 100);

        document.getElementById(`potongan_spp_val_${id}`).value = hasil;
        document.getElementById(`potongan_spp_display_${id}`).value = 'Rp ' + formatRupiahCustom(Math.round(hasil));
        hitungTotalNettoSpp();
    }

    function hitungTotalNettoSpp() {
        let totalPotonganPajak = 0;
        document.querySelectorAll('.potongan-val-spp').forEach(function (input) {
            totalPotonganPajak += parseFloat(input.value || 0);
        });

        const totalNetto = Math.max(0, brutoSpp - potonganUmSpp - totalPotonganPajak);
        document.getElementById('total_potongan_pajak_spp_display').textContent = 'Rp ' + formatRupiahCustom(Math.round(totalPotonganPajak));
        document.getElementById('jumlah_uang_spp_display').value = 'Rp ' + formatRupiahCustom(Math.round(totalNetto));
        document.getElementById('jumlah_uang_spp').value = totalNetto;
    }

    function formatRupiahCustom(angka) {
        let numberString = angka.toString().replace(/[^,\d]/g, '');
        let split = numberString.split(',');
        let sisa = split[0].length % 3;
        let rupiah = split[0].substr(0, sisa);
        let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        return split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
    }
</script>
@endpush
