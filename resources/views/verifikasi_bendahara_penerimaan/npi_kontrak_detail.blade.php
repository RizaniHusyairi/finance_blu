@extends('layouts.app')
@section('title', 'Detail Verifikasi NPI Kontrak')

@php
    $benpenStatus = $benpenApproval?->status ?? 'N/A';
    $ppkStatus = $ppkApproval?->status ?? 'N/A';
    $kasubbagStatus = $kasubbagApproval?->status ?? 'N/A';

    $badgeClass = fn($s) => match($s) {
        'APPROVED' => 'bg-success',
        'PENDING' => 'bg-warning text-dark',
        'REVISION', 'REJECTED' => 'bg-danger',
        default => 'bg-light text-dark border',
    };

    $finalBadge = match($statusFinal) {
        'Selesai Diverifikasi' => 'bg-success',
        'Perlu Revisi' => 'bg-danger',
        default => 'bg-info',
    };
@endphp

@section('content')
{{-- Alerts --}}
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

{{-- HEADER KEPUTUSAN --}}
<div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #f8f9fc, #eef2ff); border-left: 4px solid #4361ee !important;">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <h4 class="fw-bold mb-0 text-dark">Detail Verifikasi NPI</h4>
                    <span class="badge bg-primary px-2 py-1">Kontrak</span>
                </div>
                <div class="row g-2 mt-2" style="font-size: 13px;">
                    <div class="col-md-6"><span class="text-muted">Nomor NPI:</span> <strong>{{ $npi->nomor_npi ?? '-' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Nomor SPM:</span> <strong>{{ $spm?->nomor_spm ?? '-' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Nomor SPP:</span> <strong>{{ $spp?->nomor_spp ?? '-' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Nomor SPK:</span> <strong>{{ $kontrak?->nomor_spk ?? '-' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Pekerjaan:</span> <strong>{{ $kontrak?->nama_pekerjaan ?? '-' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Vendor:</span> <strong>{{ $vendor?->nama_pihak ?? '-' }}</strong></div>
                </div>
            </div>

            <div class="d-flex flex-column gap-2" style="min-width: 200px;">
                <div class="d-flex flex-wrap gap-1 justify-content-end mb-2">
                    <span class="badge {{ $badgeClass($benpenStatus) }}" title="Bendahara Penerimaan">BenPen: {{ $benpenStatus }}</span>
                    <span class="badge {{ $badgeClass($ppkStatus) }}" title="PPK">PPK: {{ $ppkStatus }}</span>
                    <span class="badge {{ $badgeClass($kasubbagStatus) }}" title="Kasubbag">KSB: {{ $kasubbagStatus }}</span>
                    <span class="badge {{ $finalBadge }}">{{ $statusFinal }}</span>
                </div>

                <a href="{{ route('verifikasi-bendahara-penerimaan.npi.kontrak.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="material-icons-outlined" style="font-size:14px; vertical-align: middle;">arrow_back</i> Kembali ke Antrean
                </a>

                @if($canApprove)
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalApprove">
                        <i class="material-icons-outlined" style="font-size:14px; vertical-align: middle;">check_circle</i> Setujui
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalRevisi">
                        <i class="material-icons-outlined" style="font-size:14px; vertical-align: middle;">replay</i> Minta Revisi
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- PANEL PROGRESS VERIFIKASI PARALEL --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h6 class="fw-bold text-primary mb-3"><i class="material-icons-outlined align-middle me-1" style="font-size: 20px;">account_tree</i> Progress Verifikasi (Paralel)</h6>
        <div class="row g-3">
            {{-- Bendahara Pengeluaran (Submitter) --}}
            <div class="col-6 col-lg-3">
                <div class="border rounded p-3 text-center h-100 border-success bg-success bg-opacity-10">
                    <div class="fw-bold mb-1" style="font-size: 13px;">Bend. Pengeluaran</div>
                    <div class="text-muted mb-2" style="font-size: 11px;">{{ $bendaharaPengeluaran?->name ?? '-' }}</div>
                    <span class="badge bg-success">SUBMITTED</span>
                </div>
            </div>
            {{-- Bendahara Penerimaan --}}
            <div class="col-6 col-lg-3">
                <div class="border rounded p-3 text-center h-100 {{ $benpenStatus === 'APPROVED' ? 'border-success bg-success bg-opacity-10' : ($benpenStatus === 'PENDING' ? 'border-warning bg-warning bg-opacity-10' : (in_array($benpenStatus, ['REVISION','REJECTED']) ? 'border-danger bg-danger bg-opacity-10' : '')) }}">
                    <div class="fw-bold mb-1" style="font-size: 13px;">Bend. Penerimaan</div>
                    <div class="text-muted mb-2" style="font-size: 11px;">{{ $benpenApproval?->assignedUser?->name ?? $npi->bendaharaPenerimaan?->name ?? '-' }}</div>
                    <span class="badge {{ $badgeClass($benpenStatus) }}">{{ $benpenStatus }}</span>
                    @if($benpenApproval?->acted_at)
                        <div class="mt-1" style="font-size: 10px; color: #6c757d;">{{ \Carbon\Carbon::parse($benpenApproval->acted_at)->format('d M Y H:i') }}</div>
                    @endif
                </div>
            </div>
            {{-- PPK --}}
            <div class="col-6 col-lg-3">
                <div class="border rounded p-3 text-center h-100 {{ $ppkStatus === 'APPROVED' ? 'border-success bg-success bg-opacity-10' : ($ppkStatus === 'PENDING' ? 'border-warning bg-warning bg-opacity-10' : (in_array($ppkStatus, ['REVISION','REJECTED']) ? 'border-danger bg-danger bg-opacity-10' : '')) }}">
                    <div class="fw-bold mb-1" style="font-size: 13px;">PPK</div>
                    <div class="text-muted mb-2" style="font-size: 11px;">{{ $ppkApproval?->assignedUser?->name ?? $spp?->ppkVerifikator?->name ?? '-' }}</div>
                    <span class="badge {{ $badgeClass($ppkStatus) }}">{{ $ppkStatus }}</span>
                    @if($ppkApproval?->acted_at)
                        <div class="mt-1" style="font-size: 10px; color: #6c757d;">{{ \Carbon\Carbon::parse($ppkApproval->acted_at)->format('d M Y H:i') }}</div>
                    @endif
                </div>
            </div>
            {{-- Kasubbag --}}
            <div class="col-6 col-lg-3">
                <div class="border rounded p-3 text-center h-100 {{ $kasubbagStatus === 'APPROVED' ? 'border-success bg-success bg-opacity-10' : ($kasubbagStatus === 'PENDING' ? 'border-warning bg-warning bg-opacity-10' : (in_array($kasubbagStatus, ['REVISION','REJECTED']) ? 'border-danger bg-danger bg-opacity-10' : '')) }}">
                    <div class="fw-bold mb-1" style="font-size: 13px;">Kasubbag</div>
                    <div class="text-muted mb-2" style="font-size: 11px;">{{ $kasubbagApproval?->assignedUser?->name ?? '-' }}</div>
                    <span class="badge {{ $badgeClass($kasubbagStatus) }}">{{ $kasubbagStatus }}</span>
                    @if($kasubbagApproval?->acted_at)
                        <div class="mt-1" style="font-size: 10px; color: #6c757d;">{{ \Carbon\Carbon::parse($kasubbagApproval->acted_at)->format('d M Y H:i') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- KOLOM KIRI — Data Sumber --}}
    <div class="col-xl-6">
        {{-- 1. Card Ringkasan NPI --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="material-icons-outlined align-middle me-1" style="font-size:18px;">receipt_long</i> Ringkasan NPI</h6>
            </div>
            <div class="card-body">
                <div class="row g-2" style="font-size: 13px;">
                    <div class="col-6"><span class="text-muted d-block">Nomor NPI</span><strong>{{ $npi->nomor_npi ?? '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Tanggal NPI</span><strong>{{ optional($npi->tanggal_npi)->format('d M Y') ?? '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Status NPI</span><span class="badge {{ $npi->status === 'MENUNGGU_VERIFIKASI' ? 'bg-info' : ($npi->status === 'DISETUJUI_FINAL' ? 'bg-success' : ($npi->status === 'REVISI' ? 'bg-danger' : 'bg-secondary')) }}">{{ $npi->status }}</span></div>
                    <div class="col-6"><span class="text-muted d-block">Uraian</span><strong>{{ $npi->catatan ?? $kontrak?->nama_pekerjaan ?? '-' }}</strong></div>
                    <div class="col-12 mt-2"><div class="bg-light p-2 rounded text-center"><span class="text-muted me-2">Nilai NPI:</span><strong class="text-primary fs-5">Rp {{ number_format($nominalNpi, 0, ',', '.') }}</strong></div></div>
                </div>
            </div>
        </div>

        {{-- 2. Card Ringkasan SPM --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="material-icons-outlined align-middle me-1" style="font-size:18px;">description</i> Ringkasan SPM</h6>
            </div>
            <div class="card-body">
                <div class="row g-2" style="font-size: 13px;">
                    <div class="col-6"><span class="text-muted d-block">Nomor SPM</span><strong>{{ $spm?->nomor_spm ?? '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Tanggal SPM</span><strong>{{ optional($spm?->tanggal_spm)->format('d M Y') ?? '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Nilai SPM</span><strong>Rp {{ number_format($spm?->nominal_spm ?? 0, 0, ',', '.') }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Status SPM</span><span class="badge bg-secondary">{{ $spm?->status ?? '-' }}</span></div>
                </div>
            </div>
        </div>

        {{-- 3. Card Ringkasan SPP / Tagihan --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="material-icons-outlined align-middle me-1" style="font-size:18px;">request_quote</i> Ringkasan SPP / Tagihan</h6>
            </div>
            <div class="card-body">
                <div class="row g-2" style="font-size: 13px;">
                    <div class="col-6"><span class="text-muted d-block">Nomor SPP</span><strong>{{ $spp?->nomor_spp ?? '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Nomor Tagihan</span><strong>{{ $tagihan?->nomor_tagihan ?? '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Nilai Netto Tagihan</span><strong class="text-success">Rp {{ number_format($tagihan?->total_netto ?? 0, 0, ',', '.') }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Total Potongan</span><strong class="text-danger">Rp {{ number_format($tagihan?->total_potongan ?? 0, 0, ',', '.') }}</strong></div>
                </div>
            </div>
        </div>

        {{-- 4. Card Dasar Kontrak & Termin --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="material-icons-outlined align-middle me-1" style="font-size:18px;">assignment</i> Dasar Kontrak & Termin</h6>
            </div>
            <div class="card-body">
                <div class="row g-2" style="font-size: 13px;">
                    <div class="col-6"><span class="text-muted d-block">Nomor SPK</span><strong>{{ $kontrak?->nomor_spk ?? '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Nama Pekerjaan</span><strong>{{ $kontrak?->nama_pekerjaan ?? '-' }}</strong></div>
                    <div class="col-4"><span class="text-muted d-block">Termin</span><strong>{{ $termin?->termin_ke ?? '-' }} ({{ $termin?->jenis_termin ?? '-' }})</strong></div>
                    <div class="col-4"><span class="text-muted d-block">BAST</span><strong>{{ $detailKontrak?->nomor_bast ?? '-' }}</strong></div>
                    <div class="col-4"><span class="text-muted d-block">BAPP</span><strong>{{ $detailKontrak?->nomor_bapp ?? '-' }}</strong></div>
                    <div class="col-4"><span class="text-muted d-block">BAP</span><strong>{{ $detailKontrak?->nomor_bap ?? '-' }}</strong></div>
                </div>
            </div>
        </div>
    </div>

    {{-- KOLOM KANAN — Validasi & Kelayakan --}}
    <div class="col-xl-6">
        <div class="sticky-top" style="top: 1rem; z-index: 1;">

            {{-- 1. Card Informasi NPI --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="material-icons-outlined align-middle me-1" style="font-size:18px;">info</i> Informasi NPI</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2" style="font-size: 13px;">
                        <div class="col-6"><span class="text-muted d-block">Nomor NPI</span><strong>{{ $npi->nomor_npi ?? '-' }}</strong></div>
                        <div class="col-6"><span class="text-muted d-block">Tanggal NPI</span><strong>{{ optional($npi->tanggal_npi)->format('d M Y') ?? '-' }}</strong></div>
                        <div class="col-6"><span class="text-muted d-block">Tahun Anggaran</span><strong>{{ $npi->tahun_anggaran ?? date('Y') }}</strong></div>
                        <div class="col-6"><span class="text-muted d-block">Bend. Pengeluaran</span><strong>{{ $bendaharaPengeluaran?->name ?? '-' }}</strong></div>
                        <div class="col-6"><span class="text-muted d-block">Bend. Penerimaan</span><strong>{{ $npi->bendaharaPenerimaan?->name ?? '-' }}</strong></div>
                        <div class="col-6"><span class="text-muted d-block">Nominal NPI</span><strong class="text-primary">Rp {{ number_format($nominalNpi, 0, ',', '.') }}</strong></div>
                    </div>
                </div>
            </div>

            {{-- 2. Card Vendor & Rekening --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="material-icons-outlined align-middle me-1" style="font-size:18px;">account_balance</i> Vendor & Rekening</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2" style="font-size: 13px;">
                        <div class="col-12"><span class="text-muted d-block">Nama Vendor</span><strong>{{ $vendor?->nama_pihak ?? '-' }}</strong></div>
                        <div class="col-6"><span class="text-muted d-block">Bank</span><strong>{{ $rekening?->nama_bank ?? '-' }}</strong></div>
                        <div class="col-6"><span class="text-muted d-block">No. Rekening</span><strong class="font-monospace">{{ $rekening?->nomor_rekening ?? '-' }}</strong></div>
                        <div class="col-12"><span class="text-muted d-block">Atas Nama</span><strong>{{ $rekening?->nama_rekening ?? '-' }}</strong></div>
                    </div>
                </div>
            </div>

            {{-- 3. Dokumen Pendukung --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="material-icons-outlined align-middle me-1" style="font-size:18px;">folder_open</i> Dokumen Pendukung</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($documentStatuses as $doc)
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <span style="font-size: 13px;">{{ $doc['label'] }} @if(!$doc['required'])<small class="text-muted">(Opsional)</small>@endif</span>
                                <div>
                                    @if($doc['status'] === 'ready')
                                        <span class="badge bg-success">Tersedia</span>
                                    @elseif($doc['status'] === 'missing')
                                        <span class="badge bg-danger">Belum Ada</span>
                                    @else
                                        <span class="badge bg-light text-dark border">Tidak Wajib</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- 4. Catatan Workflow / Revisi --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="material-icons-outlined align-middle me-1" style="font-size:18px;">comment</i> Catatan Workflow / Revisi</h6>
                </div>
                <div class="card-body">
                    @forelse($revisionNotes as $note)
                        <div class="border-start border-3 {{ str_contains($note['role'], 'PPK') ? 'border-primary' : (str_contains($note['role'], 'Penerimaan') ? 'border-warning' : 'border-info') }} ps-3 mb-3">
                            <div class="fw-semibold" style="font-size: 13px;">{{ $note['role'] }}</div>
                            <div class="text-muted" style="font-size: 12px;">{{ $note['user'] }} · {{ $note['time'] }}</div>
                            <div class="mt-1 fst-italic" style="font-size: 13px;">"{{ $note['catatan'] }}"</div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-3" style="font-size: 13px;">Belum ada catatan revisi.</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>

{{-- PANEL KEPUTUSAN --}}
@if($canApprove)
    <div class="card border-0 shadow-sm mb-4 border-top border-4 border-warning">
        <div class="card-body p-4 text-center">
            <h5 class="fw-bold mb-2"><i class="material-icons-outlined align-middle text-warning me-1">gavel</i> Keputusan Anda</h5>
            <p class="text-muted mb-3">NPI ini menunggu tindakan verifikasi dari Anda sebagai Bendahara Penerimaan.</p>
            <div class="d-flex gap-3 justify-content-center">
                <button type="button" class="btn btn-success px-4" data-bs-toggle="modal" data-bs-target="#modalApprove">
                    <i class="material-icons-outlined" style="font-size:16px; vertical-align: middle;">check_circle</i> Setujui NPI
                </button>
                <button type="button" class="btn btn-outline-danger px-4" data-bs-toggle="modal" data-bs-target="#modalRevisi">
                    <i class="material-icons-outlined" style="font-size:16px; vertical-align: middle;">replay</i> Minta Revisi
                </button>
            </div>
        </div>
    </div>
@elseif($benpenStatus === 'APPROVED')
    <div class="card border-0 shadow-sm mb-4 border-top border-4 border-success">
        <div class="card-body p-4 text-center">
            <i class="material-icons-outlined text-success" style="font-size: 48px;">verified</i>
            <h5 class="fw-bold mt-2">Anda telah menyetujui dokumen ini</h5>
            <p class="text-muted mb-0">Disetujui pada {{ $benpenApproval?->acted_at ? \Carbon\Carbon::parse($benpenApproval->acted_at)->format('d M Y H:i') : '-' }}</p>
            <div class="mt-3 d-flex gap-2 justify-content-center">
                <span class="badge {{ $badgeClass($ppkStatus) }}">PPK: {{ $ppkStatus }}</span>
                <span class="badge {{ $badgeClass($kasubbagStatus) }}">Kasubbag: {{ $kasubbagStatus }}</span>
            </div>
        </div>
    </div>
@elseif(in_array($benpenStatus, ['REVISION', 'REJECTED']))
    <div class="card border-0 shadow-sm mb-4 border-top border-4 border-danger">
        <div class="card-body p-4 text-center">
            <i class="material-icons-outlined text-danger" style="font-size: 48px;">replay</i>
            <h5 class="fw-bold mt-2">Anda mengembalikan dokumen ini untuk revisi</h5>
            @if($benpenApproval?->catatan)
                <p class="text-muted fst-italic">"{{ $benpenApproval->catatan }}"</p>
            @endif
        </div>
    </div>
@endif

{{-- Riwayat Log --}}
@if($recentActivities->count() > 0)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-bold"><i class="material-icons-outlined align-middle me-1" style="font-size:18px;">history</i> Riwayat Aktivitas</h6>
        </div>
        <div class="card-body">
            @foreach($recentActivities as $act)
                <div class="d-flex align-items-start mb-3 pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <i class="material-icons-outlined text-primary me-3 mt-1" style="font-size: 20px;">schedule</i>
                    <div class="flex-grow-1">
                        <div class="fw-semibold" style="font-size: 13px;">{{ $act['title'] }}</div>
                        <div class="text-muted" style="font-size: 12px;">{{ $act['actor'] }} · {{ $act['time'] }}</div>
                        @if($act['note'])
                            <div class="text-muted fst-italic mt-1" style="font-size: 12px;">"{{ $act['note'] }}"</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- MODAL APPROVE --}}
<div class="modal fade" id="modalApprove" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('verifikasi-bendahara-penerimaan.npi.kontrak.approve', $npi->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="material-icons-outlined me-1">check_circle</i> Setujui NPI ini?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tindakan ini akan menyelesaikan verifikasi Bendahara Penerimaan untuk dokumen <strong>{{ $npi->nomor_npi }}</strong>.</p>
                    <div class="alert alert-info border-0 py-2 small">
                        <i class="material-icons-outlined align-middle me-1" style="font-size:16px;">info</i>
                        Verifikasi dari PPK dan Kasubbag berjalan secara paralel. Persetujuan Anda tidak tergantung pada mereka.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan (Opsional)</label>
                        <textarea name="catatan" class="form-control" rows="2" placeholder="Tulis catatan jika ada..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4">Ya, Setujui</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL REVISI --}}
<div class="modal fade" id="modalRevisi" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('verifikasi-bendahara-penerimaan.npi.kontrak.revisi', $npi->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="material-icons-outlined me-1">replay</i> Minta Revisi NPI</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Dokumen <strong>{{ $npi->nomor_npi }}</strong> akan dikembalikan ke Bendahara Pengeluaran untuk diperbaiki.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan Revisi <span class="text-danger">*</span></label>
                        <textarea name="catatan_revisi" class="form-control" rows="3" required placeholder="Jelaskan apa yang perlu diperbaiki..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4">Kirim Revisi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
