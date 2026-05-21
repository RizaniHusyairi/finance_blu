@extends('layouts.app')
@section('title') Detail Verifikasi Perjaldin — {{ $tagihan->nomor_tagihan }} @endsection

@push('css')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    /* Premium Visual Tokens */
    body {
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    .font-mono-premium {
        font-family: 'JetBrains Mono', SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
        letter-spacing: -0.02em;
    }
    .info-label { 
        font-size: 0.7rem; 
        color: #8a99ad; 
        text-transform: uppercase; 
        letter-spacing: .06em; 
        margin-bottom: 4px; 
        font-weight: 700;
    }
    .info-value { 
        font-weight: 650; 
        font-size: 0.92rem; 
        color: #1e293b; 
    }
    .sticky-panel { 
        position: sticky; 
        top: 90px; 
        transition: all 0.3s ease;
    }
    @media(max-width:991px){ .sticky-panel { position: static; } }

    /* Hero Header Card Styles */
    .detail-hero-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
        border-radius: 16px !important;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .detail-hero-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(to bottom, #0f52ba, #3b82f6);
    }
    .detail-hero-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px -10px rgba(0, 0, 0, 0.05);
    }

    /* Info Document Card Styles */
    .info-doc-card {
        border-radius: 16px !important;
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
    }
    .info-item-box {
        padding: 10px 12px;
        border-radius: 8px;
        transition: all 0.2s ease;
        background: transparent;
    }
    .info-item-box:hover {
        background: rgba(59, 130, 246, 0.04);
        transform: translateX(2px);
    }
    .info-item-box i {
        color: #94a3b8;
        transition: color 0.2s ease;
    }
    .info-item-box:hover i {
        color: #3b82f6;
    }

    /* ══════ Parallel Flow Stepper ══════ */
    .parallel-flow {
        display: flex;
        align-items: center;
        gap: 0;
        position: relative;
    }
    /* Phase columns */
    .pf-phase {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
        z-index: 2;
    }
    .pf-phase-start, .pf-phase-end { flex: 0 0 auto; min-width: 110px; }
    .pf-phase-parallel { flex: 1 1 auto; }

    /* Node circle (start & end) */
    .pf-node {
        width: 52px; height: 52px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
        border: 3px solid #e2e8f0;
        background: #fff;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow: 0 4px 12px -2px rgba(0,0,0,0.06);
        position: relative;
    }
    .pf-node:hover {
        transform: scale(1.15) translateY(-3px);
        box-shadow: 0 8px 20px -4px rgba(59,130,246,0.18);
    }
    .pf-node.done   { background: #d1fae5; border-color: #10b981; color: #059669; }
    .pf-node.active  { background: #dbeafe; border-color: #3b82f6; color: #2563eb; animation: pf-pulse 2s infinite; }
    .pf-node.pending { background: #f1f5f9; border-color: #cbd5e1; color: #94a3b8; }

    @keyframes pf-pulse {
        0%,100% { box-shadow: 0 0 0 0 rgba(59,130,246,0.35); }
        50%     { box-shadow: 0 0 0 10px rgba(59,130,246,0); }
    }

    /* Connector arrows */
    .pf-connector {
        flex: 0 0 40px;
        height: 4px;
        position: relative;
        z-index: 1;
    }
    .pf-connector::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        border-radius: 2px;
    }
    .pf-connector.done::before   { background: linear-gradient(90deg, #10b981, #34d399); }
    .pf-connector.active::before { background: linear-gradient(90deg, #34d399, #3b82f6); }
    .pf-connector.pending::before{ background: #e2e8f0; }
    /* Animated dash for active */
    .pf-connector.active::after {
        content: '';
        position: absolute;
        top: -1px; left: 0; right: 0; height: 6px;
        background: repeating-linear-gradient(90deg, transparent, transparent 6px, rgba(59,130,246,0.25) 6px, rgba(59,130,246,0.25) 12px);
        border-radius: 3px;
        animation: pf-dash 1s linear infinite;
    }
    @keyframes pf-dash { 0%{transform:translateX(0)} 100%{transform:translateX(12px)} }

    /* ── Parallel Track Container ── */
    .parallel-track {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1.5px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px 16px;
        position: relative;
        width: 100%;
    }
    .parallel-track-label {
        position: absolute;
        top: -11px; left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 3px 14px;
        border-radius: 20px;
        white-space: nowrap;
        box-shadow: 0 2px 8px -1px rgba(59,130,246,0.25);
    }

    /* Individual verifier row inside parallel track */
    .pf-verifier-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border-radius: 10px;
        transition: all 0.25s ease;
        margin-bottom: 4px;
    }
    .pf-verifier-row:last-child { margin-bottom: 0; }
    .pf-verifier-row:hover {
        background: rgba(59,130,246,0.04);
        transform: translateX(3px);
    }
    .pf-verifier-dot {
        width: 30px; height: 30px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.78rem;
        flex-shrink: 0;
        border: 2.5px solid;
        background: #fff;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .pf-verifier-row:hover .pf-verifier-dot {
        transform: scale(1.18);
    }
    .pf-verifier-dot.done     { border-color: #10b981; color: #059669; background: #d1fae5; }
    .pf-verifier-dot.active   { border-color: #3b82f6; color: #2563eb; background: #dbeafe; animation: pf-pulse 2s infinite; }
    .pf-verifier-dot.revision { border-color: #f59e0b; color: #d97706; background: #fef3c7; }
    .pf-verifier-dot.rejected { border-color: #ef4444; color: #dc2626; background: #fee2e2; }
    .pf-verifier-dot.pending  { border-color: #cbd5e1; color: #94a3b8; background: #f8fafc; }

    /* Progress summary bar at bottom of parallel track */
    .pf-progress-bar-track {
        height: 6px;
        background: #e2e8f0;
        border-radius: 3px;
        overflow: hidden;
        margin-top: 12px;
    }
    .pf-progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #10b981, #34d399);
        border-radius: 3px;
        transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Responsive: stack vertically on small screens */
    @media (max-width: 767px) {
        .parallel-flow {
            flex-direction: column;
            gap: 0;
        }
        .pf-connector {
            width: 4px; height: 28px;
            flex: 0 0 28px;
        }
        .pf-connector::before {
            width: 4px; height: 100%;
        }
        .pf-phase-start, .pf-phase-end { min-width: auto; }
    }

    @keyframes pulse-glow {
        0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
        100% { box-shadow: 0 0 0 8px rgba(59, 130, 246, 0); }
    }

    /* Accordion & Slate Board Styles */
    .peserta-accordion-item {
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
        border-radius: 12px !important;
        margin-bottom: 12px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: #fff;
    }
    .peserta-accordion-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -6px rgba(0, 0, 0, 0.04);
        border-color: rgba(59, 130, 246, 0.3) !important;
    }
    .biaya-board {
        background-color: #f8fafc !important;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 16px;
        transition: background-color 0.2s;
    }
    .biaya-board:hover {
        background-color: #f1f5f9 !important;
    }

    /* Timeline & Chat Bubble Styles */
    .timeline-line {
        position: absolute;
        left: 20px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e2e8f0;
    }
    .timeline-node {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: #fff;
        border: 3px solid #cbd5e1;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        z-index: 2;
    }
    .timeline-item:hover .timeline-node {
        transform: scale(1.22);
        box-shadow: 0 0 0 5px rgba(203, 213, 225, 0.2);
    }
    .chat-bubble-timeline {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 16px;
        position: relative;
        transition: all 0.25s ease;
    }
    .chat-bubble-timeline:hover {
        background-color: #fff;
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px -2px rgba(0,0,0,0.03);
    }

    /* Verification Action Panel Styles */
    .action-panel-premium {
        border-radius: 16px !important;
        transition: all 0.3s ease;
        overflow: hidden;
    }
    .action-panel-active {
        border: 2px solid #3b82f6 !important;
        box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.15) !important;
    }
    .action-panel-inactive {
        border: 1px solid #cbd5e1 !important;
    }
    .action-textarea {
        border-radius: 10px !important;
        border: 1px solid #cbd5e1;
        transition: all 0.2s ease;
    }
    .action-textarea:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12) !important;
    }
    .btn-approve-modern {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: none !important;
        transition: all 0.25s ease;
        box-shadow: 0 4px 12px -2px rgba(16, 185, 129, 0.2);
    }
    .btn-approve-modern:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px -1px rgba(16, 185, 129, 0.35);
        filter: brightness(1.05);
    }
    .btn-revision-modern {
        background-color: #fff;
        border: 1.5px solid #d97706 !important;
        color: #d97706 !important;
        transition: all 0.25s ease;
    }
    .btn-revision-modern:hover {
        background-color: #fffbeb;
        color: #b45309 !important;
        border-color: #b45309 !important;
        transform: translateY(-1px);
    }
</style>
@endpush

@section('content')
<x-page-title title="Detail Verifikasi Perjaldin" subtitle="{{ $tagihan->nomor_tagihan }} — {{ $tagihan->deskripsi }}" />

{{-- Flash --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
        <i class="bi bi-x-circle-fill me-2"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ② Tombol Kembali --}}
<div class="mb-4">
    <a href="{{ route($indexRoute) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
    </a>
</div>

{{-- ═══ SECTION 1 — HERO HEADER ═══ --}}
@php
    $statusMap = [
        'DRAFT'               => ['class'=>'bg-secondary',          'text'=>'Draft',                             'helper'=>'Belum diajukan.'],
        'PENDING_VERIFIKASI_PERJALDIN' => ['class'=>'bg-primary',    'text'=>'Menunggu Verifikator',              'helper'=>'Menunggu PPSPM, Bendahara Penerimaan, Bendahara Pengeluaran, dan PPK.'],
        'PENDING_PPK'         => ['class'=>'bg-primary',            'text'=>'Menunggu Verifikasi PPK',           'helper'=>'Dokumen sedang menunggu persetujuan PPK.'],
        'PENDING_PPSPM'       => ['class'=>'bg-primary',            'text'=>'Menunggu Verifikasi PPSPM',         'helper'=>'Dokumen sedang menunggu persetujuan PPSPM.'],
        'REVISI_PPK'          => ['class'=>'bg-warning text-dark',  'text'=>'Revisi oleh PPK',                  'helper'=>'Dokumen dikembalikan. Operator perlu merevisi.'],
        'REVISI_PPSPM'        => ['class'=>'bg-warning text-dark',  'text'=>'Revisi oleh PPSPM',                'helper'=>'Dokumen dikembalikan oleh PPSPM. Operator perlu merevisi.'],
        'DITOLAK_PPK'         => ['class'=>'bg-danger',             'text'=>'Ditolak PPK',                      'helper'=>'Dokumen ditolak oleh PPK.'],
        'DITOLAK_PPSPM'       => ['class'=>'bg-danger',             'text'=>'Ditolak PPSPM',                    'helper'=>'Dokumen ditolak oleh PPSPM.'],
        'PENDING_BENDAHARA'   => ['class'=>'bg-info text-dark',     'text'=>'Menunggu Verifikasi Bendahara',     'helper'=>'Menunggu verifikasi Bendahara Pengeluaran.'],
        'PENDING_BENDAHARA_PENERIMAAN' => ['class'=>'bg-info text-dark', 'text'=>'Menunggu Bendahara Penerimaan', 'helper'=>'Menunggu verifikasi Bendahara Penerimaan.'],
        'PENDING_BENDAHARA_PENGELUARAN' => ['class'=>'bg-info text-dark', 'text'=>'Menunggu Bendahara Pengeluaran', 'helper'=>'Menunggu verifikasi Bendahara Pengeluaran.'],
        'REVISI_BENDAHARA'    => ['class'=>'bg-warning text-dark',  'text'=>'Revisi oleh Bendahara',            'helper'=>'Dokumen dikembalikan oleh Bendahara Pengeluaran.'],
        'REVISI_BENDAHARA_PENERIMAAN' => ['class'=>'bg-warning text-dark', 'text'=>'Revisi Bendahara Penerimaan', 'helper'=>'Dokumen dikembalikan oleh Bendahara Penerimaan.'],
        'REVISI_BENDAHARA_PENGELUARAN' => ['class'=>'bg-warning text-dark', 'text'=>'Revisi Bendahara Pengeluaran', 'helper'=>'Dokumen dikembalikan oleh Bendahara Pengeluaran.'],
        'DITOLAK_BENDAHARA'   => ['class'=>'bg-danger',             'text'=>'Ditolak Bendahara',                'helper'=>'Dokumen ditolak oleh Bendahara Pengeluaran.'],
        'DITOLAK_BENDAHARA_PENERIMAAN' => ['class'=>'bg-danger',     'text'=>'Ditolak Bendahara Penerimaan',     'helper'=>'Dokumen ditolak oleh Bendahara Penerimaan.'],
        'DITOLAK_BENDAHARA_PENGELUARAN' => ['class'=>'bg-danger',    'text'=>'Ditolak Bendahara Pengeluaran',    'helper'=>'Dokumen ditolak oleh Bendahara Pengeluaran.'],
        'PENDING_KASUBBAG'    => ['class'=>'bg-info text-dark',      'text'=>'Menunggu Kasubbag',                'helper'=>'Seluruh verifikator sudah menyetujui. Menunggu persetujuan Kasubbag.'],
        'REVISI_KASUBBAG'     => ['class'=>'bg-warning text-dark',   'text'=>'Revisi oleh Kasubbag',             'helper'=>'Dokumen dikembalikan oleh Kasubbag.'],
        'DITOLAK_KASUBBAG'    => ['class'=>'bg-danger',              'text'=>'Ditolak Kasubbag',                 'helper'=>'Dokumen ditolak oleh Kasubbag.'],
        'DISETUJUI_PERJALDIN' => ['class'=>'bg-success',            'text'=>'Disetujui — Verifikasi Selesai',   'helper'=>'Dokumen telah disetujui oleh seluruh verifikator dan Kasubbag.'],
    ];
    $sc = $statusMap[$tagihan->status] ?? ['class'=>'bg-secondary','text'=>$tagihan->status,'helper'=>''];
    $bulanMap=[1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $submitLog = $tagihan->logs->firstWhere('aksi','SUBMIT');
@endphp

<div class="card detail-hero-card mb-4 shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div>
                <div class="mb-3">
                    @include('verifikasi_perjaldin.partials.status-badge', ['status' => $tagihan->status, 'size' => 'fs-6'])
                </div>
                <h3 class="fw-extrabold text-dark mb-2" style="letter-spacing: -0.01em;">{{ $tagihan->deskripsi }}</h3>
                <div class="d-flex flex-wrap gap-3 text-muted small mt-2">
                    <span class="d-flex align-items-center gap-1"><i class="bi bi-hash text-primary"></i> <span class="font-mono-premium fw-semibold">{{ $tagihan->nomor_tagihan }}</span></span>
                    <span class="d-flex align-items-center gap-1"><i class="bi bi-people text-primary"></i> <strong>{{ $tagihan->detailPerjaldin->count() }}</strong> Peserta</span>
                    <span class="d-flex align-items-center gap-1"><i class="bi bi-calendar3 text-primary"></i> {{ ($bulanMap[$tagihan->periode_bulan] ?? '-') . ' ' . ($tagihan->periode_tahun ?? '') }}</span>
                    @if($submitLog)
                        <span class="d-flex align-items-center gap-1"><i class="bi bi-send text-primary"></i> Diajukan {{ $submitLog->created_at->format('d M Y, H:i') }}</span>
                    @endif
                </div>
                <p class="text-muted small mt-3 mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle text-primary"></i> <span>{{ $sc['helper'] }}</span>
                </p>
            </div>
            <div class="text-md-end mt-3 mt-md-0 bg-success-subtle bg-opacity-10 border border-success-subtle border-opacity-30 rounded-3 p-3 text-start">
                <div class="text-muted small mb-1 fw-bold text-uppercase tracking-wider" style="font-size: 0.65rem;">Total Bruto</div>
                <div class="text-success fw-bold font-mono-premium" style="font-size: 1.85rem; line-height: 1.1;">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</div>
                <div class="text-muted small mt-2" style="font-size: 0.72rem;"><i class="bi bi-clock me-1"></i>Dibuat {{ \Carbon\Carbon::parse($tagihan->created_at)->format('d M Y') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ MAIN 2-COL LAYOUT ═══ --}}
<div class="row g-4">
    {{-- Kolom Kiri: Konten Review --}}
    <div class="col-lg-8">

        {{-- SECTION 2: Workflow Stepper --}}
        @include('verifikasi_perjaldin.partials.workflow-stepper', ['tagihan' => $tagihan, 'userRole' => $userRole])

        <div class="card info-doc-card mb-4 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-file-earmark-text text-primary me-2"></i>Informasi Dokumen</h6>
                <span class="badge bg-light text-secondary border small">ID: {{ $tagihan->id }}</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="info-item-box">
                            <div class="info-label"><i class="bi bi-hash me-1"></i>Nomor Tagihan</div>
                            <div class="info-value font-mono-premium small">{{ $tagihan->nomor_tagihan ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-item-box">
                            <div class="info-label"><i class="bi bi-file-earmark-text me-1"></i>Uraian / Judul</div>
                            <div class="info-value text-truncate" title="{{ $tagihan->deskripsi }}">{{ $tagihan->deskripsi ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-item-box">
                            <div class="info-label"><i class="bi bi-calendar3 me-1"></i>Periode</div>
                            <div class="info-value">{{ ($bulanMap[$tagihan->periode_bulan] ?? '-') . ' ' . ($tagihan->periode_tahun ?? '') }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-item-box">
                            <div class="info-label"><i class="bi bi-geo-alt me-1"></i>Kota TTD</div>
                            <div class="info-value">{{ $tagihan->kota_ttd ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-item-box">
                            <div class="info-label"><i class="bi bi-calendar-event me-1"></i>Tanggal TTD</div>
                            <div class="info-value font-mono-premium small">{{ isset($tagihan->tanggal_ttd) ? \Carbon\Carbon::parse($tagihan->tanggal_ttd)->format('d M Y') : '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-item-box">
                            <div class="info-label"><i class="bi bi-people me-1"></i>Jumlah Peserta</div>
                            <div class="info-value font-mono-premium small">{{ $tagihan->detailPerjaldin->count() }} Orang</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-item-box">
                            <div class="info-label"><i class="bi bi-person-badge me-1"></i>Nama PPK</div>
                            <div class="info-value">{{ $tagihan->ppk_nama_snapshot ?? '-' }}</div>
                            @if($tagihan->ppk_nip_snapshot)
                                <div class="text-muted font-mono-premium" style="font-size:.68rem;">NIP: {{ $tagihan->ppk_nip_snapshot }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-item-box">
                            <div class="info-label"><i class="bi bi-person-badge me-1"></i>PPSPM</div>
                            <div class="info-value">{{ $tagihan->ppspm_nama_snapshot ?? '-' }}</div>
                            @if($tagihan->ppspm_nip_snapshot)
                                <div class="text-muted font-mono-premium" style="font-size:.68rem;">NIP: {{ $tagihan->ppspm_nip_snapshot }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-item-box">
                            <div class="info-label"><i class="bi bi-person-badge me-1"></i>Bendahara Penerimaan</div>
                            <div class="info-value">{{ $tagihan->bendahara_penerimaan_nama_snapshot ?? '-' }}</div>
                            @if($tagihan->bendahara_penerimaan_nip_snapshot)
                                <div class="text-muted font-mono-premium" style="font-size:.68rem;">NIP: {{ $tagihan->bendahara_penerimaan_nip_snapshot }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-item-box">
                            <div class="info-label"><i class="bi bi-person-badge me-1"></i>Bendahara Pengeluaran</div>
                            <div class="info-value">{{ $tagihan->bendahara_pengeluaran_nama_snapshot ?? '-' }}</div>
                            @if($tagihan->bendahara_pengeluaran_nip_snapshot)
                                <div class="text-muted font-mono-premium" style="font-size:.68rem;">NIP: {{ $tagihan->bendahara_pengeluaran_nip_snapshot }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-item-box">
                            <div class="info-label"><i class="bi bi-person-badge me-1"></i>Kasubbag</div>
                            <div class="info-value">{{ $tagihan->kasubbag_nama_snapshot ?? '-' }}</div>
                            @if($tagihan->kasubbag_nip_snapshot)
                                <div class="text-muted font-mono-premium" style="font-size:.68rem;">NIP: {{ $tagihan->kasubbag_nip_snapshot }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-item-box bg-success-subtle bg-opacity-10 border border-success-subtle border-opacity-20 rounded-3">
                            <div class="info-label"><i class="bi bi-cash-coin me-1 text-success"></i>Total Bruto</div>
                            <div class="info-value text-success font-mono-premium fs-5">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 4: Daftar Peserta --}}
        @include('verifikasi_perjaldin.partials.peserta-accordion', ['tagihan' => $tagihan])

        {{-- SECTION 5: Catatan Revisi (jika ada) --}}
        @php
            $revisiLogs = $tagihan->logs->filter(fn($l) => in_array($l->aksi, ['REVISION','REJECT']))->sortByDesc('created_at');
        @endphp
        @if($revisiLogs->isNotEmpty())
            <div class="card info-doc-card shadow-sm mb-4">
                <div class="card-header bg-warning bg-opacity-10 border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold text-warning"><i class="bi bi-exclamation-triangle-fill me-2"></i>Riwayat Revisi / Penolakan</h6>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill small px-3">{{ $revisiLogs->count() }} Catatan</span>
                </div>
                <div class="card-body py-3 px-4">
                    @foreach($revisiLogs as $rl)
                        <div class="d-flex gap-3 align-items-start {{ !$loop->last ? 'mb-4 pb-4 border-bottom border-opacity-25' : '' }}">
                            <div class="rounded-circle bg-warning bg-opacity-15 d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;">
                                <i class="bi bi-exclamation-triangle text-warning"></i>
                            </div>
                            <div class="flex-fill">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-1 mb-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle small rounded-pill px-2 py-1">{{ $rl->aksi }}</span>
                                        <span class="fw-semibold small text-dark">{{ $rl->user?->name ?? 'Sistem' }}</span>
                                        @if($rl->role_saat_itu)
                                            <span class="badge bg-light text-secondary border rounded-pill ms-1" style="font-size:.62rem;">{{ $rl->role_saat_itu }}</span>
                                        @endif
                                    </div>
                                    <small class="text-muted font-mono-premium" style="font-size: 0.68rem;">{{ $rl->created_at->format('d M Y, H:i') }}</small>
                                </div>
                                <div class="mt-2 chat-bubble-timeline small" style="background: #fef3c7; border-color: #fbbf24;">
                                    <i class="bi bi-chat-quote text-warning me-1"></i>{{ $rl->catatan ?? '-' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- SECTION 6: Audit Trail --}}
        @include('verifikasi_perjaldin.partials.audit-timeline', ['tagihan' => $tagihan])
    </div>

    {{-- Kolom Kanan: Panel Aksi (Sticky) --}}
    <div class="col-lg-4">
        <div class="sticky-panel">
            {{-- Panel Verifikasi --}}
            @include('verifikasi_perjaldin.partials.verification-action-panel', [
                'tagihan'      => $tagihan,
                'userRole'     => $userRole,
                'currentApproval' => $currentApproval ?? null,
                'approveRoute' => $approveRoute,
                'revisiRoute'  => $revisiRoute,
                'allRoleApprovals' => $allRoleApprovals ?? [],
            ])

            {{-- Info Dokumen Ringkas --}}
            <div class="card info-doc-card shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-card-list text-primary me-2"></i>Ringkasan Dokumen</h6>
                </div>
                <div class="card-body py-3 px-3">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="info-item-box">
                                <div class="info-label"><i class="bi bi-hash me-1"></i>No. Tagihan</div>
                                <div class="info-value small font-mono-premium">{{ $tagihan->nomor_tagihan }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-item-box">
                                <div class="info-label"><i class="bi bi-calendar3 me-1"></i>Periode</div>
                                <div class="info-value small">{{ ($bulanMap[$tagihan->periode_bulan] ?? '-') . ' ' . ($tagihan->periode_tahun ?? '') }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-item-box">
                                <div class="info-label"><i class="bi bi-people me-1"></i>Peserta</div>
                                <div class="info-value small font-mono-premium">{{ $tagihan->detailPerjaldin->count() }} orang</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-item-box">
                                <div class="info-label"><i class="bi bi-cash-coin me-1 text-success"></i>Total Bruto</div>
                                <div class="info-value small text-success font-mono-premium">Rp {{ number_format($tagihan->total_bruto,0,',','.') }}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="info-item-box">
                                <div class="info-label"><i class="bi bi-flag me-1"></i>Status</div>
                                <div class="mt-1">@include('verifikasi_perjaldin.partials.status-badge', ['status' => $tagihan->status])</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
