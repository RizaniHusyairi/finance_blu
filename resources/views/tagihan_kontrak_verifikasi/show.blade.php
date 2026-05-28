@extends('layouts.app')
@section('title', 'Verifikasi Tagihan Kontrak — ' . $tagihan->nomor_tagihan)

@push('css')
<style>
    /* Import Google Fonts */
    @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    /* Color Palette */
    :root {
        --primary-gradient: linear-gradient(135deg, #2563eb, #1d4ed8);
        --dark-navy-gradient: linear-gradient(135deg, #1e293b, #0f172a);
        --emerald-gradient: linear-gradient(135deg, #10b981, #059669);
        --rose-gradient: linear-gradient(135deg, #f43f5e, #be123c);
        --amber-gradient: linear-gradient(135deg, #f59e0b, #b45309);
        --soft-gray-bg: #f8fafc;
        --card-border: rgba(226, 232, 240, 0.8);
    }

    /* Keyframe Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(24px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    @keyframes slowPulse {
        0%, 100% {
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.3);
            transform: scale(1);
        }
        50% {
            box-shadow: 0 0 0 8px rgba(245, 158, 11, 0);
            transform: scale(1.02);
        }
    }
    @keyframes emeraldPulse {
        0%, 100% {
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);
        }
        50% {
            box-shadow: 0 6px 25px rgba(16, 185, 129, 0.45);
        }
    }
    @keyframes iconBounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
    }
    @keyframes borderPulse {
        0%, 100% { border-color: rgba(245, 158, 11, 0.2); }
        50% { border-color: rgba(245, 158, 11, 0.6); }
    }

    /* Staggered Entry Animation Classes */
    .animate-fade-in-up {
        animation: fadeInUp 0.75s cubic-bezier(0.16, 1, 0.3, 1) both;
    }
    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }
    .delay-4 { animation-delay: 0.4s; }

    /* Custom Back button */
    .btn-back-pill {
        border-radius: 30px !important;
        padding: 8px 20px !important;
        font-weight: 600 !important;
        font-size: 0.875rem !important;
        border: 1px solid #cbd5e1 !important;
        background: #ffffff !important;
        color: #475569 !important;
        transition: all 0.25s ease !important;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none !important;
    }
    .btn-back-pill:hover {
        background: #f8fafc !important;
        color: #0f172a !important;
        border-color: #94a3b8 !important;
        transform: translateX(-3px) !important;
    }

    /* Header Card Premium & Animated */
    .header-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%) !important;
        border: 1px solid rgba(226, 232, 240, 0.9) !important;
        border-radius: 20px !important;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.03), 0 1px 3px rgba(15, 23, 42, 0.01) !important;
        padding: 24px 32px !important;
        position: relative;
        overflow: hidden;
        transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1) !important;
        z-index: 1;
    }
    .header-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 6px;
        height: 100%;
        background: linear-gradient(180deg, #2563eb, #10b981);
        border-top-left-radius: 20px;
        border-bottom-left-radius: 20px;
    }
    .header-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 35px rgba(15, 23, 42, 0.06), 0 3px 6px rgba(15, 23, 42, 0.02) !important;
        border-color: rgba(37, 99, 235, 0.2) !important;
    }
    .header-card:hover .header-icon-box {
        background: #2563eb !important;
        color: #ffffff !important;
        transform: rotate(6deg) scale(1.05);
    }
    .header-card-glow {
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(37, 99, 235, 0.05) 0%, rgba(255, 255, 255, 0) 70%);
        pointer-events: none;
        z-index: -1;
        animation: floatGlow 10s infinite alternate ease-in-out;
    }
    .header-icon-animate {
        animation: floatIcon 3s infinite alternate ease-in-out;
        display: inline-block;
    }
    @keyframes floatGlow {
        0% { transform: translate(0, 0) scale(1); }
        100% { transform: translate(-30px, 20px) scale(1.15); }
    }
    @keyframes floatIcon {
        0% { transform: translateY(0); }
        100% { transform: translateY(-3px); }
    }

    /* High-contrast Glow Financial Cards */
    .fin-card {
        border-radius: 18px !important;
        padding: 24px !important;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1) !important;
        border: 1px solid var(--card-border) !important;
        position: relative;
        overflow: hidden;
        z-index: 1;
        background: #ffffff;
    }
    .fin-card:hover {
        transform: translateY(-5px);
    }
    .fin-card-bruto {
        background: var(--dark-navy-gradient) !important;
        color: #ffffff !important;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08) !important;
        border: none !important;
    }
    .fin-card-potongan {
        background: rgba(239, 68, 68, 0.03) !important;
        border-color: rgba(239, 68, 68, 0.15) !important;
        color: #0f172a !important;
    }
    .fin-card-netto {
        background: var(--emerald-gradient) !important;
        color: #ffffff !important;
        border: none !important;
        animation: emeraldPulse 3s infinite alternate ease-in-out;
    }
    .fin-card .label-text {
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        letter-spacing: 0.08em !important;
        text-transform: uppercase !important;
        opacity: 0.8;
        margin-bottom: 8px;
    }
    .fin-card-bruto .label-text { color: #94a3b8 !important; }
    .fin-card-potongan .label-text { color: #ef4444 !important; }
    .fin-card-netto .label-text { color: #a7f3d0 !important; }
    
    .fin-card .amount-text {
        font-family: 'JetBrains Mono', monospace !important;
        font-weight: 700 !important;
        font-size: 1.45rem !important;
        letter-spacing: -0.02em !important;
    }

    /* Step Stepper Timeline layout */
    .stepper-timeline {
        position: relative;
        padding-left: 10px;
    }
    .stepper-timeline::before {
        content: '';
        position: absolute;
        left: 26px;
        top: 20px;
        bottom: 20px;
        width: 2px;
        background: #e2e8f0;
        z-index: 1;
    }

    /* Advanced Interactive Vertical Stepper */
    .approval-row {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px 20px;
        border-radius: 14px;
        margin-bottom: 14px;
        background: #ffffff;
        border: 1px solid var(--card-border);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        z-index: 2;
        box-shadow: 0 4px 10px rgba(0,0,0,0.01);
    }
    .approval-row:hover {
        transform: translateX(6px);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05) !important;
    }

    /* Indicator lines on hover */
    .approval-row::after {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 0px;
        border-radius: 14px 0 0 14px;
        transition: width 0.2s ease;
    }
    .approval-row:hover::after {
        width: 4px;
    }

    /* Style variants based on status */
    .approval-row.is-approved {
        border-left: 4px solid #10b981 !important;
        background: rgba(16, 185, 129, 0.01) !important;
    }
    .approval-row.is-approved::after { background-color: #10b981; }

    .approval-row.is-pending {
        border-left: 4px solid #f59e0b !important;
        background: rgba(245, 158, 11, 0.02) !important;
        animation: borderPulse 3s infinite ease-in-out;
    }
    .approval-row.is-pending::after { background-color: #f59e0b; }
    .approval-row.is-pending:hover {
        animation: slowPulse 2.5s infinite ease-in-out;
    }

    .approval-row.is-revision {
        border-left: 4px solid #ef4444 !important;
        background: rgba(239, 68, 68, 0.01) !important;
    }
    .approval-row.is-revision::after { background-color: #ef4444; }

    .approval-row.is-rejected {
        border-left: 4px solid #ef4444 !important;
        background: rgba(239, 68, 68, 0.01) !important;
    }
    .approval-row.is-rejected::after { background-color: #ef4444; }

    .approval-row.is-waiting {
        border-left: 4px solid #94a3b8 !important;
        background: rgba(241, 245, 249, 0.4) !important;
        opacity: 0.75;
    }
    .approval-row.is-waiting::after { background-color: #94a3b8; }

    /* Timeline Ikon */
    .approval-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #ffffff;
        flex-shrink: 0;
        z-index: 3;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }
    .approval-row:hover .approval-icon {
        transform: scale(1.15) rotate(5deg);
    }

    /* Document Card Visual Revamp */
    .document-card-slot {
        border: 1px solid var(--card-border);
        border-radius: 12px;
        padding: 16px 20px;
        background: #ffffff;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .document-card-slot:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(37, 99, 235, 0.06) !important;
        border-color: rgba(37, 99, 235, 0.3) !important;
        background: linear-gradient(180deg, #ffffff, #f8fafc) !important;
    }
    .document-card-slot .bi-file-earmark-text,
    .document-card-slot .bi-file-earmark-check,
    .document-card-slot .bi-file-earmark-bar-graph,
    .document-card-slot .bi-file-earmark-pdf {
        transition: all 0.3s ease;
    }
    .document-card-slot:hover .bi-file-earmark-text,
    .document-card-slot:hover .bi-file-earmark-check,
    .document-card-slot:hover .bi-file-earmark-bar-graph,
    .document-card-slot:hover .bi-file-earmark-pdf {
        animation: iconBounce 0.5s ease;
        transform: scale(1.1) translateY(-2px);
    }

    /* Premium Pill Buttons for Documents */
    .btn-doc-pill {
        border-radius: 30px !important;
        padding: 7px 16px !important;
        font-size: 0.8rem !important;
        font-weight: 600 !important;
        transition: all 0.25s ease !important;
        text-decoration: none !important;
    }

    /* Modern Glassmorphic Action Panel (Right Side) */
    .glass-action-panel {
        background: rgba(255, 255, 255, 0.95) !important;
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
        box-shadow: 0 10px 35px rgba(15, 23, 42, 0.04) !important;
        border-radius: 18px !important;
        overflow: hidden;
        backdrop-filter: blur(8px);
    }
    .glass-action-panel .card-header {
        background: #ffffff !important;
        border-bottom: 1px solid #f1f5f9 !important;
    }

    /* Soft Verifiers list items */
    .glass-action-panel .list-group-item {
        border-bottom: 1px solid #f1f5f9 !important;
        transition: all 0.2s ease !important;
    }
    .glass-action-panel .list-group-item:hover {
        background-color: #f8fafc !important;
    }

    /* Glowing Action Buttons */
    .btn-approve-gradient {
        background: var(--emerald-gradient) !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        border: none !important;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2) !important;
        padding: 10px 20px !important;
        border-radius: 30px !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        text-decoration: none !important;
    }
    .btn-approve-gradient:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 24px rgba(16, 185, 129, 0.35) !important;
        color: #ffffff !important;
    }

    .btn-revisi-pill {
        border-radius: 30px !important;
        padding: 8px 18px !important;
        font-weight: 600 !important;
        font-size: 0.85rem !important;
        border: 1px solid #f59e0b !important;
        color: #b45309 !important;
        background: transparent !important;
        transition: all 0.25s ease !important;
    }
    .btn-revisi-pill:hover {
        background: #fffbeb !important;
        transform: translateY(-1px) !important;
    }

    .btn-tolak-pill {
        border-radius: 30px !important;
        padding: 8px 18px !important;
        font-weight: 600 !important;
        font-size: 0.85rem !important;
        border: 1px solid #ef4444 !important;
        color: #be123c !important;
        background: transparent !important;
        transition: all 0.25s ease !important;
    }
    .btn-tolak-pill:hover {
        background: #fff5f5 !important;
        transform: translateY(-1px) !important;
    }

    /* Soft chips */
    .role-chip {
        font-size: 0.68rem !important;
        letter-spacing: 0.04em;
        font-weight: 700 !important;
        border-radius: 30px !important;
        padding: 4px 10px !important;
        text-transform: uppercase !important;
    }

    /* Custom styled scroll behavior */
    .sticky-top {
        top: 24px !important;
    }
</style>
@endpush

@section('content')
@php
    $kontrak = $tagihan->detailKontrak?->termin?->kontrak;
    $termin = $tagihan->detailKontrak?->termin;

    $statusMeta = [
        'PENDING'   => ['icon' => 'hourglass-split', 'color' => 'warning',   'label' => 'Menunggu',  'cls' => 'is-pending'],
        'APPROVED'  => ['icon' => 'check-lg',        'color' => 'success',   'label' => 'Disetujui', 'cls' => 'is-approved'],
        'REVISION'  => ['icon' => 'arrow-counterclockwise', 'color' => 'danger', 'label' => 'Revisi', 'cls' => 'is-revision'],
        'REJECTED'  => ['icon' => 'x-lg',            'color' => 'danger',    'label' => 'Ditolak',   'cls' => 'is-rejected'],
        'WAITING'   => ['icon' => 'clock-history',   'color' => 'secondary', 'label' => 'Menunggu Step Sebelumnya', 'cls' => 'is-waiting'],
    ];

    $roleColors = [
        'PPK' => '#0d6efd', 'PPSPM' => '#6610f2',
        'KOORDINATOR_KEUANGAN' => '#198754',
        'BENDAHARA_PENGELUARAN' => '#d63384',
        'BENDAHARA_PENERIMAAN' => '#fd7e14',
        'KASUBBAG' => '#0dcaf0',
    ];

    $roleLabels = [
        'PPK' => 'PPK', 'PPSPM' => 'PPSPM',
        'KOORDINATOR_KEUANGAN' => 'Koordinator Keuangan',
        'BENDAHARA_PENGELUARAN' => 'Bendahara Pengeluaran',
        'BENDAHARA_PENERIMAAN' => 'Bendahara Penerimaan',
        'KASUBBAG' => 'Kepala Subbagian Keuangan & Tata Usaha',
    ];

    // === Hitung progres step 1 (paralel) ===
    $step1Approvals = $approvalsByStep->get(1) ?? collect();
    $step1Total     = $step1Approvals->count();
    $step1Approved  = $step1Approvals->where('status', 'APPROVED');
    $step1Pending   = $step1Approvals->where('status', 'PENDING');
    $step1Revision  = $step1Approvals->whereIn('status', ['REVISION','REJECTED']);
    $step1Done      = $step1Approved->count() === $step1Total && $step1Total > 0;
    $step1Progress  = $step1Total > 0 ? round(($step1Approved->count() / $step1Total) * 100) : 0;

    // Step 2 (Kasubbag)
    $step2Approval  = ($approvalsByStep->get(2) ?? collect())->first();

    // Apakah user yang login adalah Kasubbag dan workflow masih di step 1
    $userRoles      = auth()->user()?->getRoleNames()->toArray() ?? [];
    $isKasubbag     = in_array('Kepala Subbagian Keuangan dan Tata Usaha', $userRoles, true);
    $kasubbagWaiting = $isKasubbag && $instance && $instance->step_saat_ini === 1 && $instance->status === 'IN_PROGRESS';

    // Apakah user ini salah satu dari 5 verifikator step 1 yang SUDAH approve tapi step 1 belum tuntas
    $alreadyApprovedButWaiting = false;
    if ($instance && $instance->step_saat_ini === 1 && ! $isKasubbag) {
        $myStep1 = $step1Approvals->first(fn ($a) =>
            $a->assigned_user_id === auth()->id() ||
            in_array($roleLabels[strtoupper(str_replace([' ','-'],'_',$a->role_code))] ?? $a->role_code, $userRoles, true)
        );
        if ($myStep1 && $myStep1->status === 'APPROVED' && ! $step1Done) {
            $alreadyApprovedButWaiting = true;
        }
    }
@endphp

<div class="container-fluid py-4 animate-fade-in-up">
    {{-- Header Card Premium & Beranimasi --}}
    <div class="header-card mb-4">
        <div class="header-card-glow"></div>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="header-icon-box shadow-sm d-none d-md-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background: rgba(37, 99, 235, 0.08); border: 1px solid rgba(37, 99, 235, 0.15); border-radius: 14px; color: #2563eb; font-size: 1.5rem; transition: all 0.3s ease;">
                    <i class="bi bi-file-earmark-check-fill header-icon-animate"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2 flex-wrap">
                        Verifikasi Tagihan Kontrak
                        @php
                            $statusColors = [
                                'PROGRESS' => 'primary',
                                'APPROVED' => 'success',
                                'REVISION' => 'warning',
                                'REJECTED' => 'danger',
                            ];
                            $badgeColor = $statusColors[$tagihan->status] ?? 'secondary';
                        @endphp
                        <span class="badge bg-{{ $badgeColor }} bg-opacity-10 text-{{ $badgeColor }} border border-{{ $badgeColor }} rounded-pill" style="font-size: 0.72rem; font-weight: 700; padding: 4px 12px; letter-spacing: 0.05em; text-transform: uppercase;">
                            {{ $tagihan->status }}
                        </span>
                    </h4>
                    <div class="small text-muted d-flex align-items-center flex-wrap gap-2">
                        <span class="font-monospace text-primary fw-semibold px-2 py-0.5 rounded" style="background: rgba(37, 99, 235, 0.06); font-size: 0.82rem;">{{ $tagihan->nomor_tagihan }}</span>
                        <span class="text-secondary opacity-50">·</span>
                        <span class="fw-medium text-dark"><i class="bi bi-file-earmark-text me-1 text-secondary"></i>{{ $kontrak->nomor_spk ?? '-' }}</span>
                        <span class="text-secondary opacity-50">·</span>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-10 rounded-pill px-2.5 py-0.5" style="font-size: 0.72rem;">
                            Termin {{ $termin->termin_ke ?? '-' }} ({{ $termin->jenis_termin ?? '-' }})
                        </span>
                    </div>
                </div>
            </div>
            <div>
                <a href="{{ route('verifikasi-tagihan-kontrak.index') }}" class="btn-back-pill shadow-sm">
                    <i class="bi bi-arrow-left"></i>Kembali ke Antrean
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success border-0 shadow-sm rounded-4 mb-4"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}</div>@endif

    {{-- Banner kontekstual untuk Kasubbag yang menunggu step 1 selesai --}}
    @if($kasubbagWaiting)
        <div class="alert border-0 shadow-sm rounded-4 mb-4 animate-fade-in-up" style="background: linear-gradient(135deg, #e7f5ff 0%, #fff8e1 100%); border-left: 5px solid #0dcaf0 !important;">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background: #0dcaf0; color: #fff;">
                    <i class="bi bi-clock-history fs-3"></i>
                </div>
                <div class="flex-grow-1 min-width-0">
                    <h6 class="fw-bold mb-1 text-dark">Menunggu Verifikasi 5 Pejabat (Paralel)</h6>
                    <div class="small text-muted">
                        Persetujuan Anda sebagai <strong>Kepala Subbagian Keuangan dan Tata Usaha</strong> akan tersedia
                        setelah seluruh 5 verifikator (PPK, PPSPM, Koordinator Keuangan, Bendahara Pengeluaran, Bendahara Penerimaan)
                        menyelesaikan verifikasi mereka.
                    </div>
                </div>
                <div class="text-end">
                    <div class="fs-3 fw-bold text-{{ $step1Approved->count() === $step1Total ? 'success' : 'warning' }}">
                        {{ $step1Approved->count() }}<small class="text-muted">/{{ $step1Total }}</small>
                    </div>
                    <div class="small text-muted fw-semibold">sudah verifikasi</div>
                </div>
            </div>
            <div class="progress mt-3 animate-pulse" style="height: 8px; border-radius: 4px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $step1Progress }}%"></div>
            </div>
        </div>
    @endif

    {{-- Banner untuk verifikator step 1 yang sudah approve tapi rekan-rekannya belum --}}
    @if($alreadyApprovedButWaiting)
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 animate-fade-in-up" style="background: rgba(16, 185, 129, 0.05); border-left: 5px solid #10b981 !important;">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1 text-dark">Verifikasi Anda sudah tercatat</h6>
                    <div class="small mb-0 text-muted">
                        Menunggu <strong>{{ $step1Pending->count() }} verifikator lain</strong> menyelesaikan persetujuan paralel ini.
                        Setelah semua selesai, dokumen akan diteruskan ke Kepala Subbagian Keuangan dan Tata Usaha untuk finalisasi.
                    </div>
                </div>
                <div class="badge bg-success text-white rounded-pill px-3 py-2 fs-7">{{ $step1Approved->count() }}/{{ $step1Total }} Terselesaikan</div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        {{-- Kolom Kiri --}}
        <div class="col-lg-8">
            {{-- Ringkasan --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4 animate-fade-in-up delay-1">
                <div class="card-header bg-white border-bottom pt-4 px-4 pb-3">
                    <h5 class="fw-bold mb-0 text-primary d-flex align-items-center gap-2">
                        <i class="bi bi-receipt"></i>Ringkasan Tagihan
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="text-muted small fw-semibold mb-1"><i class="bi bi-briefcase me-1"></i>Pekerjaan</div>
                            <div class="fw-bold text-dark" style="font-size: 1.05rem;">{{ $kontrak->nama_pekerjaan ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small fw-semibold mb-1"><i class="bi bi-building me-1"></i>Vendor</div>
                            <div class="fw-bold text-dark" style="font-size: 1.05rem;">{{ $kontrak->vendor->nama_pihak ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="fin-card fin-card-bruto shadow-sm">
                                <div class="label-text">Total Bruto</div>
                                <div class="amount-text">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="fin-card fin-card-potongan shadow-sm">
                                <div class="label-text">Total Potongan</div>
                                <div class="amount-text">Rp {{ number_format($tagihan->total_potongan, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="fin-card fin-card-netto shadow-sm">
                                <div class="label-text">Total Netto</div>
                                <div class="amount-text">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-12">
                            <div class="text-muted small fw-semibold mb-2"><i class="bi bi-credit-card me-1"></i>Mekanisme Pembayaran</div>
                            <span class="badge bg-primary-subtle text-primary fs-6 px-3 py-2" style="border-radius: 30px;">
                                <i class="bi bi-bank me-1"></i>{{ optional($tagihan->mekanisme_pembayaran)->label() ?? 'LS - Pihak Ketiga' }}
                            </span>
                            <div class="small text-muted mt-2">Kontrak selalu dibayar secara LS - Pihak Ketiga.</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status Approval per Step --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4 animate-fade-in-up delay-2">
                <div class="card-header bg-white border-bottom pt-4 px-4 pb-3">
                    <h5 class="fw-bold mb-0 text-success"><i class="bi bi-list-check me-2"></i>Status Verifikasi</h5>
                    <p class="text-muted small mb-0 mt-1">Step 1 dilakukan paralel oleh 5 verifikator. Step 2 (Kasubbag) berjalan setelah step 1 selesai.</p>
                </div>
                <div class="card-body p-4">
                    <div class="stepper-timeline">
                        @forelse($approvalsByStep as $stepNo => $approvals)
                            <div class="mb-4">
                                <div class="text-uppercase fw-bold small text-muted mb-3 d-flex align-items-center gap-2" style="letter-spacing: .5px; margin-left: 20px;">
                                    <span>Step {{ $stepNo }}</span>
                                    @if($stepNo == 1)
                                        <span class="badge bg-info-subtle text-info rounded-pill px-2 py-1">Paralel · {{ $approvals->count() }} verifikator</span>
                                    @else
                                        <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1">Final Approval</span>
                                    @endif
                                </div>
                                @foreach($approvals as $a)
                                    @php
                                        $meta = $statusMeta[$a->status] ?? $statusMeta['WAITING'];
                                        $color = $roleColors[$a->role_code] ?? '#6c757d';
                                    @endphp
                                    <div class="approval-row {{ $meta['cls'] }}">
                                        <div class="approval-icon" style="background: {{ $color }};">
                                            <i class="bi bi-{{ $meta['icon'] }}"></i>
                                        </div>
                                        <div class="flex-grow-1 min-width-0">
                                            <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                                <span>{{ $a->nama_step }}</span>
                                                @if($a->status === 'APPROVED')
                                                    <span class="badge bg-success-subtle text-success small role-chip" style="font-size: 0.65rem !important;">Selesai</span>
                                                @elseif($a->status === 'PENDING')
                                                    <span class="badge bg-warning-subtle text-warning small role-chip" style="font-size: 0.65rem !important;">Menunggu</span>
                                                @elseif($a->status === 'REVISION')
                                                    <span class="badge bg-danger-subtle text-danger small role-chip" style="font-size: 0.65rem !important;">Revisi</span>
                                                @elseif($a->status === 'REJECTED')
                                                    <span class="badge bg-danger-subtle text-danger small role-chip" style="font-size: 0.65rem !important;">Ditolak</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary small role-chip" style="font-size: 0.65rem !important;">Antrean</span>
                                                @endif
                                            </div>
                                            <div class="small text-muted mt-1">
                                                <i class="bi bi-person me-1"></i>
                                                {{ $a->assignedUser?->name ?? '— belum ditentukan —' }}
                                                @if($a->acted_at)
                                                    · <span class="text-{{ $meta['color'] }} fw-semibold">{{ $meta['label'] }}</span>
                                                    · {{ $a->acted_at->format('d M Y H:i') }}
                                                    @if($a->actedByUser)oleh {{ $a->actedByUser->name }}@endif
                                                @else
                                                    · <span class="text-{{ $meta['color'] }}">{{ $meta['label'] }}</span>
                                                @endif
                                            </div>
                                            @if($a->catatan)
                                                <div class="small text-dark bg-light rounded-3 p-2 mt-2 border-start border-3 border-secondary fst-italic">
                                                    <i class="bi bi-chat-left-text me-1 text-muted"></i>"{{ $a->catatan }}"
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @empty
                            <p class="text-muted ms-3">Belum ada step approval.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Detail Berita Acara & Vendor --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4 animate-fade-in-up delay-3">
                <div class="card-header bg-white border-bottom pt-4 px-4 pb-3">
                    <h5 class="fw-bold mb-0 text-info d-flex align-items-center gap-2"><i class="bi bi-file-earmark-check"></i>Detail Berita Acara</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @php
                            $baDocs = [
                                [
                                    'label' => 'BAPP',
                                    'nomor' => $tagihan->detailKontrak->nomor_bapp,
                                    'tanggal' => $tagihan->detailKontrak->tanggal_bapp,
                                    'jenis' => 'BAPP_FINAL_TTD',
                                    'icon' => 'file-earmark-text',
                                    'color' => 'info'
                                ],
                                [
                                    'label' => 'BAST',
                                    'nomor' => $tagihan->detailKontrak->nomor_bast,
                                    'tanggal' => $tagihan->detailKontrak->tanggal_bast,
                                    'jenis' => 'BAST_FINAL_TTD',
                                    'icon' => 'file-earmark-check',
                                    'color' => 'success'
                                ],
                                [
                                    'label' => 'BAP',
                                    'nomor' => $tagihan->detailKontrak->nomor_bap,
                                    'tanggal' => $tagihan->detailKontrak->tanggal_bap,
                                    'jenis' => 'BAP_FINAL_TTD',
                                    'icon' => 'file-earmark-bar-graph',
                                    'color' => 'primary'
                                ],
                            ];
                        @endphp

                        @foreach($baDocs as $doc)
                            @php
                                $f = $tagihan->detailKontrak->arsipDokumen->where('is_active', true)->firstWhere('jenis_dokumen', $doc['jenis']);
                            @endphp
                            <div class="col-md-4">
                                <div class="document-card-slot shadow-sm">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="bi bi-{{ $doc['icon'] }} fs-4 text-{{ $doc['color'] }}"></i>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $doc['label'] }}</div>
                                            <div class="small text-muted">{{ $doc['nomor'] ?? 'Tidak ada nomor' }}</div>
                                        </div>
                                    </div>
                                    <div class="small text-muted mb-3">
                                        Tanggal: {{ optional($doc['tanggal'])->format('d M Y') ?? '-' }}
                                    </div>
                                    @if($f)
                                        <div class="small text-success mb-2 text-truncate" title="{{ $f->nama_file_asli }}">
                                            <i class="bi bi-check-circle-fill me-1"></i>{{ $f->nama_file_asli }}
                                        </div>
                                        <a href="{{ route('verifikasi-tagihan-kontrak.arsip', [$tagihan->id, $f->id]) }}"
                                           target="_blank"
                                           class="btn-doc-pill btn btn-sm btn-outline-{{ $doc['color'] }} mt-auto w-100 text-center">
                                            <i class="bi bi-eye me-1"></i>Lihat Dokumen
                                        </a>
                                    @else
                                        <div class="small text-muted mb-2"><i class="bi bi-x-circle me-1"></i>File tidak tersedia</div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary mt-auto w-100" disabled style="border-radius: 30px;">
                                            <i class="bi bi-eye-slash me-1"></i>Tidak Tersedia
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @php
                        $invoiceFile = $tagihan->detailKontrak->arsipDokumen->where('is_active', true)->firstWhere('jenis_dokumen', 'INVOICE');
                    @endphp
                    @if($invoiceFile)
                        <div class="mt-4 p-3 border rounded-3 bg-light d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                                    <i class="bi bi-receipt fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">Invoice & Kwitansi Tagihan</div>
                                    <div class="small text-muted">{{ $invoiceFile->nama_file_asli }}</div>
                                </div>
                            </div>
                            <a href="{{ route('verifikasi-tagihan-kontrak.arsip', [$tagihan->id, $invoiceFile->id]) }}"
                               target="_blank"
                               class="btn btn-sm btn-primary rounded-pill px-4">
                                <i class="bi bi-file-earmark-arrow-down me-1"></i>Unduh Invoice
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Dokumen Kontrak Induk --}}
            @php
                $kontrakArsip = $kontrak?->arsipDokumen->where('is_active', true) ?? collect();
                $isTteApproved = $kontrak ? \App\Support\ContractDocumentTte::isApproved($kontrak) : false;
                
                $spkFinal       = $kontrakArsip->firstWhere('jenis_dokumen', 'SPK_FINAL_TTD');
                $spmkFinal      = $kontrakArsip->firstWhere('jenis_dokumen', 'SPMK_FINAL_TTD');
                $ringkasanFinal = $kontrakArsip->firstWhere('jenis_dokumen', 'RINGKASAN_KONTRAK_FINAL_TTD');
            @endphp
            <div class="card border-0 shadow-sm rounded-4 mb-4 animate-fade-in-up delay-4">
                <div class="card-header bg-white border-bottom pt-4 px-4 pb-3">
                    <h5 class="fw-bold mb-0 text-primary d-flex align-items-center gap-2"><i class="bi bi-file-earmark-pdf"></i>Dokumen Kontrak Induk</h5>
                    <p class="text-muted small mb-0 mt-1">Lampiran SPK, SPMK, dan Ringkasan Kontrak (versi final bertandatangan)</p>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @php
                            $kontrakDocs = [
                                [
                                    'jenis' => 'SPK_FINAL_TTD',                
                                    'label' => 'SPK Final',               
                                    'arsip' => $spkFinal,       
                                    'icon' => 'file-earmark-text',  
                                    'color' => 'primary',
                                    'tte_type' => 'spk',
                                    'tte_url' => $isTteApproved && $kontrak ? \Illuminate\Support\Facades\URL::signedRoute('public.contract-tte.document', ['type' => 'spk', 'id' => $kontrak->id]) : null,
                                ],
                                [
                                    'jenis' => 'SPMK_FINAL_TTD',               
                                    'label' => 'SPMK Final',              
                                    'arsip' => $spmkFinal,      
                                    'icon' => 'file-earmark-check', 
                                    'color' => 'info',
                                    'tte_type' => 'spmk',
                                    'tte_url' => $isTteApproved && $kontrak ? \Illuminate\Support\Facades\URL::signedRoute('public.contract-tte.document', ['type' => 'spmk', 'id' => $kontrak->id]) : null,
                                ],
                                [
                                    'jenis' => 'RINGKASAN_KONTRAK_FINAL_TTD',  
                                    'label' => 'Ringkasan Kontrak Final', 
                                    'arsip' => $ringkasanFinal, 
                                    'icon' => 'file-earmark-bar-graph', 
                                    'color' => 'success',
                                    'tte_type' => 'ringkasan_kontrak',
                                    'tte_url' => $isTteApproved && $kontrak ? \Illuminate\Support\Facades\URL::signedRoute('public.contract-tte.document', ['type' => 'ringkasan_kontrak', 'id' => $kontrak->id]) : null,
                                ],
                            ];
                        @endphp
                        @foreach($kontrakDocs as $doc)
                            @php
                                $hasDoc = $doc['arsip'] || $doc['tte_url'];
                            @endphp
                            <div class="col-md-4">
                                <div class="document-card-slot shadow-sm">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="bi bi-{{ $doc['icon'] }} fs-4 text-{{ $doc['color'] }}"></i>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $doc['label'] }}</div>
                                            @if($hasDoc)
                                                <div class="small text-success"><i class="bi bi-check-circle-fill me-1"></i>Tersedia</div>
                                            @else
                                                <div class="small text-muted"><i class="bi bi-x-circle me-1"></i>Belum diunggah</div>
                                            @endif
                                        </div>
                                    </div>
                                    @if($hasDoc)
                                        <div class="small text-muted text-truncate mb-3" title="{{ $doc['arsip'] ? $doc['arsip']->nama_file_asli : 'TTE PDF Dinamis' }}">
                                            {{ $doc['arsip'] ? $doc['arsip']->nama_file_asli : 'TTE PDF Dinamis' }}
                                        </div>
                                        @php
                                            $viewUrl = $doc['arsip'] 
                                                ? route('verifikasi-tagihan-kontrak.kontrak-arsip', [$tagihan->id, $doc['jenis']])
                                                : $doc['tte_url'];
                                        @endphp
                                        <a href="{{ $viewUrl }}"
                                           target="_blank"
                                           class="btn-doc-pill btn btn-sm btn-outline-{{ $doc['color'] }} mt-auto w-100 text-center">
                                            <i class="bi bi-eye me-1"></i>Lihat Dokumen
                                        </a>
                                    @else
                                        <div class="small text-muted mb-3">Dokumen belum diunggah</div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary mt-auto w-100" disabled style="border-radius: 30px;">
                                            <i class="bi bi-eye-slash me-1"></i>Tidak Tersedia
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Kolom Kanan: Aksi + Ringkasan --}}
        <div class="col-lg-4">
            <div class="sticky-top topbar-safe-sticky z-1">

                {{-- Card Aksi / Status Anda --}}
                <div class="card glass-action-panel mb-3 shadow-sm">
                    <div class="card-body p-4">
                        @if($canAct && ($myApprovals ?? collect())->count() > 1)
                            {{-- === DUAL-ROLE: Tampilkan tombol verifikasi per role === --}}
                            <h5 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2"><i class="bi bi-gear text-primary"></i>Aksi Verifikasi</h5>
                            <div class="alert alert-info border-0 small py-2 mb-3 rounded-3" style="background: rgba(37, 99, 235, 0.08); color: #1e3a8a;">
                                <i class="bi bi-people-fill me-1"></i>
                                Anda memiliki <strong>{{ ($myApprovals ?? collect())->count() }} peran verifikasi</strong> pada tagihan ini.
                                Silakan verifikasi masing-masing peran secara terpisah.
                            </div>

                            @foreach(($myApprovals ?? collect()) as $approval)
                                @php
                                    $roleName = $roleLabels[$approval->role_code] ?? $approval->role_code;
                                    $roleColor = $roleColors[$approval->role_code] ?? '#6c757d';
                                    $approvalIdx = $loop->index;
                                @endphp
                                <div class="border rounded-4 p-3 mb-3 animate-fade-in-up" style="border-color: {{ $roleColor }}30 !important; background: {{ $roleColor }}08;">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <span class="role-chip rounded-pill px-3 py-1 text-white" style="background: {{ $roleColor }};">
                                            {{ $roleName }}
                                        </span>
                                        <span class="badge bg-warning text-dark small" style="border-radius: 30px; font-size: 0.68rem; font-weight: 700;">Perlu Tindakan</span>
                                    </div>

                                    {{-- Approve --}}
                                    <form action="{{ route('verifikasi-tagihan-kontrak.approve', $tagihan->id) }}" method="POST" class="mb-2"
                                          onsubmit="return confirm('Setujui tagihan sebagai {{ $roleName }}?');">
                                        @csrf
                                        <input type="hidden" name="approval_id" value="{{ $approval->id }}">
                                        <textarea name="catatan" rows="1" class="form-control form-control-sm mb-2 rounded-3"
                                                  placeholder="Catatan persetujuan {{ $roleName }} (opsional)..."></textarea>
                                        <button type="submit" class="btn-approve-gradient w-100 mb-2">
                                            <i class="bi bi-check-lg me-1"></i>Verifikasi ({{ $roleName }})
                                        </button>
                                    </form>

                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn-revisi-pill flex-fill mb-2"
                                                data-bs-toggle="modal" data-bs-target="#modalRevisi{{ $approvalIdx }}">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Revisi
                                        </button>
                                        <button type="button" class="btn-tolak-pill flex-fill mb-2"
                                                data-bs-toggle="modal" data-bs-target="#modalReject{{ $approvalIdx }}">
                                            <i class="bi bi-x-lg me-1"></i>Tolak
                                        </button>
                                    </div>
                                </div>
                            @endforeach

                        @elseif($canAct && $myApproval)
                            <h5 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2"><i class="bi bi-gear text-primary"></i>Aksi Verifikasi</h5>
                            <div class="alert alert-info border-0 small py-2 mb-3 rounded-3" style="background: rgba(37, 99, 235, 0.08); color: #1e3a8a;">
                                Sebagai <strong>{{ $roleLabels[$myApproval->role_code] ?? $myApproval->role_code }}</strong>,
                                Anda dapat melakukan aksi pada tagihan ini.
                            </div>

                            {{-- Approve --}}
                            <form action="{{ route('verifikasi-tagihan-kontrak.approve', $tagihan->id) }}" method="POST" class="mb-3" onsubmit="return confirm('Setujui tagihan ini?');">
                                @csrf
                                <input type="hidden" name="approval_id" value="{{ $myApproval->id }}">
                                <label class="form-label fw-bold text-dark small">Catatan (opsional)</label>
                                <textarea name="catatan" rows="2" class="form-control form-control-sm mb-3 rounded-3" placeholder="Catatan persetujuan..."></textarea>
                                <button type="submit" class="btn-approve-gradient w-100">
                                    <i class="bi bi-check-lg me-1"></i>Setujui Tagihan
                                </button>
                            </form>

                            <button type="button" class="btn-revisi-pill w-100 mb-2" data-bs-toggle="modal" data-bs-target="#modalRevisi">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Minta Revisi
                            </button>
                            <button type="button" class="btn-tolak-pill w-100" data-bs-toggle="modal" data-bs-target="#modalReject">
                                <i class="bi bi-x-lg me-1"></i>Tolak
                            </button>
                        @elseif($kasubbagWaiting)
                            <h5 class="fw-bold mb-2 text-dark d-flex align-items-center gap-2"><i class="bi bi-clock-history text-info"></i>Antrean Kasubbag</h5>
                            <p class="text-muted small mb-3">
                                Tombol persetujuan akan aktif setelah seluruh 5 verifikator paralel selesai.
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-semibold text-muted">Progres Step 1</span>
                                <span class="small fw-bold text-dark">{{ $step1Approved->count() }}/{{ $step1Total }}</span>
                            </div>
                            <div class="progress mb-3" style="height: 8px; border-radius: 4px;">
                                <div class="progress-bar bg-success" style="width: {{ $step1Progress }}%"></div>
                            </div>
                            <button type="button" class="btn btn-secondary w-100 fw-bold rounded-pill py-2" disabled style="opacity: 0.6;">
                                <i class="bi bi-lock me-1"></i>Setujui (Belum Tersedia)
                            </button>
                        @elseif($alreadyApprovedButWaiting)
                            <h5 class="fw-bold mb-2 text-success d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i>Verifikasi Selesai</h5>
                            <p class="text-muted small mb-3">
                                Menunggu verifikator lain menyelesaikan persetujuan paralel.
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-semibold text-muted">Progres Step 1</span>
                                <span class="small fw-bold text-dark">{{ $step1Approved->count() }}/{{ $step1Total }}</span>
                            </div>
                            <div class="progress" style="height: 8px; border-radius: 4px;">
                                <div class="progress-bar bg-success" style="width: {{ $step1Progress }}%"></div>
                            </div>
                        @else
                            <h5 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2"><i class="bi bi-info-circle text-muted"></i>Status Anda</h5>
                            <div class="alert alert-secondary border-0 small py-2 mb-0 rounded-3 text-center" style="background: #f8fafc; color: #64748b; border: 1px dashed #cbd5e1 !important;">
                                <i class="bi bi-lock-fill me-1"></i>Anda tidak memiliki tugas verifikasi pada tagihan ini saat ini.
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Card Ringkasan Verifikator (sudah/belum) --}}
                <div class="card glass-action-panel mt-3 shadow-sm">
                    <div class="card-header bg-white border-bottom pt-3 px-4 pb-2">
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2"><i class="bi bi-people text-success"></i>Ringkasan Verifikator</h6>
                    </div>
                    <div class="card-body p-4">
                        {{-- Step 1: 5 paralel --}}
                        <div class="text-uppercase fw-bold text-muted small mb-2" style="letter-spacing: .5px;">
                            Step 1 — Paralel
                            <span class="badge bg-{{ $step1Done ? 'success' : 'warning text-dark' }} ms-1" style="border-radius:30px;">{{ $step1Approved->count() }}/{{ $step1Total }}</span>
                        </div>
                        <ul class="list-group list-group-flush mb-3">
                            @foreach($step1Approvals as $a)
                                @php
                                    $rcKey = strtoupper(str_replace([' ','-'],'_',$a->role_code));
                                    $rColor = $roleColors[$rcKey] ?? '#6c757d';
                                    $rLabel = $roleLabels[$rcKey] ?? $a->role_code;
                                    $sMeta  = $statusMeta[$a->status] ?? $statusMeta['WAITING'];
                                @endphp
                                <li class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="role-chip mt-1" style="background: {{ $rColor }}1a; color: {{ $rColor }}; flex-shrink:0; font-size: .68rem; padding: 2px 8px; border-radius: 999px; font-weight: 600;">
                                            {{ \Illuminate\Support\Str::limit(strtoupper($rLabel), 12, '') }}
                                        </span>
                                        <div class="flex-grow-1 min-width-0">
                                            <div class="small fw-semibold text-truncate text-dark">{{ $a->assignedUser?->name ?? '— belum ditentukan —' }}</div>
                                            <div class="small text-{{ $sMeta['color'] }}">
                                                <i class="bi bi-{{ $sMeta['icon'] }} me-1"></i>{{ $sMeta['label'] }}
                                                @if($a->acted_at)<span class="text-muted">· {{ $a->acted_at->diffForHumans() }}</span>@endif
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        {{-- Step 2: Kasubbag --}}
                        <div class="text-uppercase fw-bold text-muted small mb-2" style="letter-spacing: .5px;">
                            Step 2 — Final
                            <span class="badge bg-{{ $step2Approval && $step2Approval->status === 'APPROVED' ? 'success' : 'secondary' }} ms-1" style="border-radius:30px;">
                                {{ $step2Approval ? ($statusMeta[$step2Approval->status]['label'] ?? $step2Approval->status) : '—' }}
                            </span>
                        </div>
                        @if($step2Approval)
                            @php $sMeta2 = $statusMeta[$step2Approval->status] ?? $statusMeta['WAITING']; @endphp
                            <div class="d-flex align-items-start gap-2">
                                <span class="role-chip mt-1" style="background: #0dcaf01a; color: #0dcaf0; flex-shrink:0; font-size: .68rem; padding: 2px 8px; border-radius: 999px; font-weight: 600;">KASUBBAG</span>
                                <div class="flex-grow-1 min-width-0">
                                    <div class="small fw-semibold text-truncate text-dark">{{ $step2Approval->assignedUser?->name ?? '— belum ditentukan —' }}</div>
                                    <div class="small text-{{ $sMeta2['color'] }}">
                                        <i class="bi bi-{{ $sMeta2['icon'] }} me-1"></i>{{ $sMeta2['label'] }}
                                        @if($step2Approval->acted_at)<span class="text-muted">· {{ $step2Approval->acted_at->diffForHumans() }}</span>@endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@if($canAct && ($myApprovals ?? collect())->count() > 1)
    {{-- Dual-role modals: satu set modal per role --}}
    @foreach(($myApprovals ?? collect()) as $approval)
        @php
            $roleName = $roleLabels[$approval->role_code] ?? $approval->role_code;
            $approvalIdx = $loop->index;
        @endphp
        {{-- Modal Revisi per role --}}
        <div class="modal fade" id="modalRevisi{{ $approvalIdx }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('verifikasi-tagihan-kontrak.revisi', $tagihan->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="approval_id" value="{{ $approval->id }}">
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Minta Revisi ({{ $roleName }})</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <label class="form-label fw-bold">Catatan Revisi <span class="text-danger">*</span></label>
                            <textarea name="catatan" rows="4" class="form-control" placeholder="Tuliskan apa yang perlu diperbaiki..." required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-arrow-counterclockwise me-1"></i>Kirim Revisi ({{ $roleName }})</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Reject per role --}}
        <div class="modal fade" id="modalReject{{ $approvalIdx }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('verifikasi-tagihan-kontrak.reject', $tagihan->id) }}" method="POST" onsubmit="return confirm('Tolak tagihan sebagai {{ $roleName }}?');">
                    @csrf
                    <input type="hidden" name="approval_id" value="{{ $approval->id }}">
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Tolak Tagihan ({{ $roleName }})</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="alert alert-danger border-0 small">Penolakan akan menghentikan workflow tagihan secara permanen.</div>
                            <label class="form-label fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea name="catatan" rows="4" class="form-control" placeholder="Tuliskan alasan penolakan..." required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-x-lg me-1"></i>Tolak ({{ $roleName }})</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@elseif($canAct && $myApproval)
    {{-- Modal Revisi --}}
    <div class="modal fade" id="modalRevisi" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('verifikasi-tagihan-kontrak.revisi', $tagihan->id) }}" method="POST">
                @csrf
                <input type="hidden" name="approval_id" value="{{ $myApproval->id }}">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Minta Revisi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <label class="form-label fw-bold">Catatan Revisi <span class="text-danger">*</span></label>
                        <textarea name="catatan" rows="4" class="form-control" placeholder="Tuliskan apa yang perlu diperbaiki..." required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-arrow-counterclockwise me-1"></i>Kirim Permintaan Revisi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Reject --}}
    <div class="modal fade" id="modalReject" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('verifikasi-tagihan-kontrak.reject', $tagihan->id) }}" method="POST" onsubmit="return confirm('Tolak tagihan ini secara permanen?');">
                @csrf
                <input type="hidden" name="approval_id" value="{{ $myApproval->id }}">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Tolak Tagihan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="alert alert-danger border-0 small">Penolakan akan menghentikan workflow tagihan secara permanen.</div>
                        <label class="form-label fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="catatan" rows="4" class="form-control" placeholder="Tuliskan alasan penolakan..." required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-x-lg me-1"></i>Tolak Tagihan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
