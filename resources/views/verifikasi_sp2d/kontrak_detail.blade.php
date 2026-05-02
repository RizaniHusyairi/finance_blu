@extends('layouts.app')
@section('title', 'Detail Verifikasi SP2D Kontrak — ' . $currentRole)

@php
    $ppkStatus = $ppkApproval?->status ?? 'N/A';
    $kasubbagStatus = $kasubbagApproval?->status ?? 'N/A';
    $koordinatorStatus = $koordinatorApproval?->status ?? 'N/A';

    $badgeClass = fn($s) => match($s) {
        'APPROVED' => 'bg-success',
        'PENDING' => 'bg-warning text-dark',
        'REVISION', 'REJECTED' => 'bg-danger',
        default => 'bg-light text-dark border',
    };

    $finalBadge = match($statusFinal) {
        'Selesai Diverifikasi' => 'bg-success',
        'Perlu Revisi' => 'bg-danger',
        default => 'bg-info text-dark',
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
                    <h4 class="fw-bold mb-0 text-dark">Detail Verifikasi SP2D</h4>
                    <span class="badge bg-primary px-2 py-1">Kontrak — {{ $currentRole }}</span>
                </div>
                <div class="row g-2 mt-2" style="font-size: 13px;">
                    <div class="col-md-6"><span class="text-muted">Nomor SP2D:</span> <strong>{{ $sp2d->nomor_sp2d ?? '-' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Nomor NPI:</span> <strong>{{ $npi?->nomor_npi ?? '-' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Nomor SPM:</span> <strong>{{ $spm?->nomor_spm ?? '-' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Nomor SPP:</span> <strong>{{ $spp?->nomor_spp ?? '-' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Nomor SPK:</span> <strong>{{ $kontrak?->nomor_spk ?? '-' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Vendor:</span> <strong>{{ $vendor?->nama_pihak ?? '-' }}</strong></div>
                </div>
            </div>

            <div class="d-flex flex-column gap-2" style="min-width: 200px;">
                <div class="d-flex flex-wrap gap-1 justify-content-end mb-2">
                    <span class="badge bg-secondary" title="Status SP2D">SP2D: {{ $sp2d->status }}</span>
                    <span class="badge {{ $badgeClass($ppkStatus) }}" title="PPK">PPK: {{ $ppkStatus }}</span>
                    <span class="badge {{ $badgeClass($kasubbagStatus) }}" title="Kasubbag">KSB: {{ $kasubbagStatus }}</span>
                    <span class="badge {{ $badgeClass($koordinatorStatus) }}" title="Koordinator Keuangan">Koor: {{ $koordinatorStatus }}</span>
                    <span class="badge {{ $finalBadge }}">{{ $statusFinal }}</span>
                </div>

                <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="material-icons-outlined" style="font-size:14px; vertical-align: middle;">arrow_back</i> Kembali ke Antrean
                </a>
                <a href="{{ route('sp2ds.cetak-pdf', $sp2d->id) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i class="material-icons-outlined" style="font-size:14px; vertical-align: middle;">print</i> Cetak PDF
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
        <div class="row justify-content-center border py-4 rounded bg-light">
            {{-- Bendahara Pengeluaran (Submitter) --}}
            <div class="col-4 position-relative">
                <div class="text-center rounded mx-auto border-success bg-success bg-opacity-10 d-flex flex-column justify-content-center p-3" style="max-width: 280px; height: 100%;">
                    <div class="fw-bold text-success mb-1" style="font-size: 14px;">Bendahara Pengeluaran</div>
                    <div class="text-muted mb-2" style="font-size: 12px;">{{ $sp2d->bendaharaPengeluaran?->name ?? 'SYSTEM' }}</div>
                    <span class="badge bg-success mx-auto">DIAJUKAN</span>
                </div>
                <div class="position-absolute align-items-center d-flex fw-bold text-success" style="right: -10px; top: 50%; transform: translateY(-50%); font-size:24px;">
                    <i class="material-icons-outlined">arrow_forward</i>
                </div>
            </div>

            <div class="col-8">
                <div class="row h-100 g-3">
                    {{-- PPK --}}
                    <div class="col-sm-4">
                        <div class="border rounded p-3 text-center h-100 {{ $ppkStatus === 'APPROVED' ? 'border-success bg-success bg-opacity-10' : ($ppkStatus === 'PENDING' ? 'border-warning bg-warning bg-opacity-10' : (in_array($ppkStatus, ['REVISION','REJECTED']) ? 'border-danger bg-danger bg-opacity-10' : '')) }}">
                            <div class="fw-bold mb-1" style="font-size: 13px;">PPK</div>
                            <div class="text-muted mb-2" style="font-size: 11px;">{{ $ppkApproval?->assignedUser?->name ?? 'Verifikator PPK' }}</div>
                            <span class="badge {{ $badgeClass($ppkStatus) }}">{{ $ppkStatus }}</span>
                            @if($ppkApproval?->acted_at)
                                <div class="mt-2" style="font-size: 11px; color: #6c757d;">{{ \Carbon\Carbon::parse($ppkApproval->acted_at)->format('d M Y H:i') }}</div>
                            @endif
                        </div>
                    </div>

                    {{-- Kasubbag --}}
                    <div class="col-sm-4">
                        <div class="border rounded p-3 text-center h-100 {{ $kasubbagStatus === 'APPROVED' ? 'border-success bg-success bg-opacity-10' : ($kasubbagStatus === 'PENDING' ? 'border-warning bg-warning bg-opacity-10' : (in_array($kasubbagStatus, ['REVISION','REJECTED']) ? 'border-danger bg-danger bg-opacity-10' : '')) }}">
                            <div class="fw-bold mb-1" style="font-size: 13px;">Kasubbag</div>
                            <div class="text-muted mb-2" style="font-size: 11px;">{{ $kasubbagApproval?->assignedUser?->name ?? 'Verifikator Kasubbag' }}</div>
                            <span class="badge {{ $badgeClass($kasubbagStatus) }}">{{ $kasubbagStatus }}</span>
                            @if($kasubbagApproval?->acted_at)
                                <div class="mt-2" style="font-size: 11px; color: #6c757d;">{{ \Carbon\Carbon::parse($kasubbagApproval->acted_at)->format('d M Y H:i') }}</div>
                            @endif
                        </div>
                    </div>
                    {{-- Koordinator Keuangan --}}
                    <div class="col-sm-4">
                        <div class="border rounded p-3 text-center h-100 {{ $koordinatorStatus === 'APPROVED' ? 'border-success bg-success bg-opacity-10' : ($koordinatorStatus === 'PENDING' ? 'border-warning bg-warning bg-opacity-10' : (in_array($koordinatorStatus, ['REVISION','REJECTED']) ? 'border-danger bg-danger bg-opacity-10' : '')) }}">
                            <div class="fw-bold mb-1" style="font-size: 13px;">Koordinator</div>
                            <div class="text-muted mb-2" style="font-size: 11px;">{{ $koordinatorApproval?->assignedUser?->name ?? 'Verifikator Koordinator' }}</div>
                            <span class="badge {{ $badgeClass($koordinatorStatus) }}">{{ $koordinatorStatus }}</span>
                            @if($koordinatorApproval?->acted_at)
                                <div class="mt-2" style="font-size: 11px; color: #6c757d;">{{ \Carbon\Carbon::parse($koordinatorApproval->acted_at)->format('d M Y H:i') }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- KOLOM KIRI — Data Sumber --}}
    <div class="col-xl-6">
        {{-- 1. Card Ringkasan SP2D --}}
        <div class="card border-0 shadow-sm mb-4 border-top border-4 border-primary">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="material-icons-outlined align-middle me-1 text-primary" style="font-size:18px;">account_balance</i> Ringkasan SP2D</h6>
            </div>
            <div class="card-body">
                <div class="row g-2" style="font-size: 13px;">
                    <div class="col-6"><span class="text-muted d-block">Nomor SP2D</span><strong class="text-primary">{{ $sp2d->nomor_sp2d ?? '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Tanggal SP2D</span><strong>{{ optional($sp2d->tanggal_sp2d)->format('d M Y') ?? '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Status SP2D</span><span class="badge bg-secondary">{{ $sp2d->status }}</span></div>
                    <div class="col-12 mt-3"><div class="bg-primary p-3 rounded text-center bg-opacity-10"><span class="text-muted me-2">Nilai Pencairan SP2D:</span><strong class="text-primary fs-3">Rp {{ number_format($nominalSp2d, 0, ',', '.') }}</strong></div></div>
                </div>
            </div>
        </div>

        {{-- 2. Card Ringkasan NPI --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="material-icons-outlined align-middle me-1" style="font-size:18px;">receipt_long</i> Ringkasan NPI</h6>
            </div>
            <div class="card-body">
                <div class="row g-2" style="font-size: 13px;">
                    <div class="col-6"><span class="text-muted d-block">Nomor NPI</span><strong>{{ $npi?->nomor_npi ?? '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Tanggal NPI</span><strong>{{ optional($npi?->tanggal_npi)->format('d M Y') ?? '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Nilai NPI</span><strong class="text-primary">Rp {{ number_format($nominalSp2d, 0, ',', '.') }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Status NPI</span><span class="badge bg-success">{{ $npi?->status ?? '-' }}</span></div>
                </div>
            </div>
        </div>

        {{-- 3. Card Ringkasan SPM / SPP / Tagihan --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="material-icons-outlined align-middle me-1" style="font-size:18px;">description</i> Ringkasan SPM & Tagihan</h6>
            </div>
            <div class="card-body">
                <div class="row g-2" style="font-size: 13px;">
                    <div class="col-6"><span class="text-muted d-block">Nomor SPM</span><strong>{{ $spm?->nomor_spm ?? '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Nomor SPP</span><strong>{{ $spp?->nomor_spp ?? '-' }}</strong></div>
                    <div class="col-6 mt-2"><span class="text-muted d-block">Nomor Tagihan</span><strong>{{ $tagihan?->nomor_tagihan ?? '-' }}</strong></div>
                    <div class="col-6 mt-2"><span class="text-muted d-block">Nilai Tagihan Bruto</span><strong>Rp {{ number_format($tagihan?->total_bruto ?? 0, 0, ',', '.') }}</strong></div>
                    <div class="col-6 mt-2"><span class="text-muted d-block">Total Potongan</span><strong class="text-danger">Rp {{ number_format($tagihan?->total_potongan ?? 0, 0, ',', '.') }}</strong></div>
                    <div class="col-6 mt-2"><span class="text-muted d-block">Nilai Pencairan Netto</span><strong class="text-success fs-6">Rp {{ number_format($tagihan?->total_netto ?? 0, 0, ',', '.') }}</strong></div>
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
                    <div class="col-12"><span class="text-muted d-block">Nama Pekerjaan</span><strong>{{ $kontrak?->nama_pekerjaan ?? '-' }}</strong></div>
                    <div class="col-6 mt-2"><span class="text-muted d-block">Nomor SPK</span><strong>{{ $kontrak?->nomor_spk ?? '-' }}</strong></div>
                    <div class="col-6 mt-2"><span class="text-muted d-block">Termin</span><strong>{{ $termin?->termin_ke ?? '-' }} ({{ str_replace('_', ' ', $termin?->jenis_termin ?? '-') }})</strong></div>
                    <div class="col-4 mt-2"><span class="text-muted d-block">BAST</span><strong>{{ $detailKontrak?->nomor_bast ?? '-' }}</strong></div>
                    <div class="col-4 mt-2"><span class="text-muted d-block">BAPP</span><strong>{{ $detailKontrak?->nomor_bapp ?? '-' }}</strong></div>
                    <div class="col-4 mt-2"><span class="text-muted d-block">BAP</span><strong>{{ $detailKontrak?->nomor_bap ?? '-' }}</strong></div>
                </div>
            </div>
        </div>
    </div>

    {{-- KOLOM KANAN — Validasi & Kelayakan --}}
    <div class="col-xl-6">
        <div class="sticky-top" style="top: 1rem; z-index: 1;">

            {{-- 1. Card Vendor & Rekening --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="material-icons-outlined align-middle me-1" style="font-size:18px;">business</i> Vendor & Rekening</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2" style="font-size: 14px;">
                        <div class="col-12"><span class="text-muted d-block">Penerima Dana (Vendor)</span><strong class="fs-6">{{ $vendor?->nama_pihak ?? '-' }}</strong></div>
                        <div class="col-6 mt-3"><span class="text-muted d-block">Bank Vendor</span><strong>{{ $rekening?->nama_bank ?? '-' }}</strong></div>
                        <div class="col-6 mt-3"><span class="text-muted d-block">Nomor Rekening</span><strong class="font-monospace text-primary" style="font-size: 16px;">{{ $rekening?->nomor_rekening ?? '-' }}</strong></div>
                        <div class="col-12 mt-3"><span class="text-muted d-block">Atas Nama Rekening</span><strong>{{ $rekening?->nama_rekening ?? '-' }}</strong></div>
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
                                <div class="d-flex align-items-center gap-2">
                                    @if($doc['status'] === 'ready')
                                        <span class="badge bg-success">Tersedia</span>
                                        @if(is_string($doc['path']))
                                            <a href="{{ Storage::url($doc['path']) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 11px;">Buka</a>
                                        @endif
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
                        <div class="border-start border-3 {{ str_contains($note['role'], 'PPK') ? 'border-primary' : 'border-info' }} ps-3 mb-3">
                            <div class="fw-semibold" style="font-size: 13px;">{{ $note['role'] }}</div>
                            <div class="text-muted" style="font-size: 12px;">{{ $note['user'] }} · {{ $note['time'] }}</div>
                            <div class="mt-1 fst-italic" style="font-size: 13px;">"{{ $note['catatan'] }}"</div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-3" style="font-size: 13px;">Belum ada catatan revisi pada workflow ini.</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>

{{-- PANEL KEPUTUSAN BAWAH --}}
@if($canApprove)
    <div class="card border-0 shadow-lg mb-4 border-top border-4 border-warning position-sticky" style="bottom: 1rem; z-index: 10;">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="fw-bold mb-1"><i class="material-icons-outlined align-middle text-warning me-1">fact_check</i> Keputusan Verifikasi: {{ $currentRole }}</h5>
                    <p class="text-muted mb-0" style="font-size: 14px;">Tentukan persetujuan Anda setelah memeriksa kesesuaian dokumen SP2D Kontrak ini.</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-danger px-4" data-bs-toggle="modal" data-bs-target="#modalRevisi">
                        <i class="material-icons-outlined" style="font-size:18px; vertical-align: middle;">replay</i> Minta Revisi
                    </button>
                    <button type="button" class="btn btn-success px-5 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalApprove">
                        <i class="material-icons-outlined" style="font-size:18px; vertical-align: middle;">check_circle</i> Setujui SP2D
                    </button>
                </div>
            </div>
        </div>
    </div>
@elseif($currentUserApproval?->status === 'APPROVED')
    <div class="card border-0 shadow-sm mb-4 border-top border-4 border-success">
        <div class="card-body p-4 text-center">
            <i class="material-icons-outlined text-success" style="font-size: 48px;">verified</i>
            <h5 class="fw-bold mt-2">Anda telah menyetujui dokumen SP2D ini</h5>
            <p class="text-muted mb-0">Disetujui pada {{ $currentUserApproval?->acted_at ? \Carbon\Carbon::parse($currentUserApproval->acted_at)->format('d M Y H:i') : '-' }}</p>
            <div class="mt-3 d-flex gap-2 justify-content-center">
                <span class="badge {{ $badgeClass($ppkStatus) }}">PPK: {{ $ppkStatus }}</span>
                <span class="badge {{ $badgeClass($kasubbagStatus) }}">Kasubbag: {{ $kasubbagStatus }}</span>
                <span class="badge {{ $badgeClass($koordinatorStatus) }}">Koordinator: {{ $koordinatorStatus }}</span>
            </div>
        </div>
    </div>
@elseif(in_array($currentUserApproval?->status ?? '', ['REVISION', 'REJECTED']))
    <div class="card border-0 shadow-sm mb-4 border-top border-4 border-danger">
        <div class="card-body p-4 text-center">
            <i class="material-icons-outlined text-danger" style="font-size: 48px;">replay</i>
            <h5 class="fw-bold mt-2">Anda mengembalikan SP2D ini untuk diperbaiki</h5>
            @if($currentUserApproval?->catatan)
                <p class="text-muted fst-italic mt-2 p-3 bg-light rounded text-start mx-auto" style="max-width: 500px;">"{{ $currentUserApproval->catatan }}"</p>
            @endif
        </div>
    </div>
@endif

{{-- MODAL APPROVE --}}
<div class="modal fade" id="modalApprove" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route($routePrefix . '.approve', $sp2d->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="material-icons-outlined me-1">check_circle</i> Setujui SP2D Kontrak?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan memverifikasi dan menyetujui SP2D Nomor <strong>{{ $sp2d->nomor_sp2d }}</strong> dengan nilai pencairan <strong>Rp {{ number_format($nominalSp2d, 0, ',', '.') }}</strong>.</p>
                    <div class="alert alert-info border-0 py-3 small">
                        <i class="material-icons-outlined align-middle me-1 mb-1" style="font-size:24px;">info</i>
                        Tindakan ini akan menandai persetujuan Anda sebagai <strong>{{ $currentRole }}</strong>. Dokumen hanya akan sepenuhnya terbit menjadi SP2D Selesai apabila seluruh verifikator telah menyetujuinya.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4">Ya, Setujui Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL REVISI --}}
<div class="modal fade" id="modalRevisi" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route($routePrefix . '.revisi', $sp2d->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="material-icons-outlined me-1">replay</i> Minta Revisi SP2D Kontrak</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>SP2D <strong>{{ $sp2d->nomor_sp2d }}</strong> akan ditandai perlu perbaikan dan dikembalikan ke antrean draf Bendahara Pengeluaran.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan Revisi <span class="text-danger">*</span></label>
                        <textarea name="catatan_revisi" class="form-control" rows="4" required placeholder="Tuliskan secara jelas apa yang perlu diperbaiki oleh Bendahara Pengeluaran..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4">Kirim Minta Revisi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
