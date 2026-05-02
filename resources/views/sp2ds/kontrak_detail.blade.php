@extends('layouts.app')
@section('title', 'Detail SP2D Kontrak')

@php
    $badgeStatus = match($statusSp2d) {
        'BELUM DIBUAT' => 'bg-warning text-dark',
        'DRAFT' => 'bg-secondary',
        'REVISI' => 'bg-danger',
        'MENUNGGU_VERIFIKASI' => 'bg-info text-dark',
        'DISETUJUI_FINAL' => 'bg-success',
        'EXECUTED' => 'bg-primary',
        default => 'bg-light text-dark'
    };

    $formatStatus = fn($status) => match($status) {
        'BELUM DIBUAT' => 'Belum Dibuat',
        'DRAFT' => 'Draft',
        'REVISI' => 'Draft Revisi',
        'MENUNGGU_VERIFIKASI' => 'Menunggu Verifikasi',
        'DISETUJUI_FINAL' => 'Selesai / Terbit',
        'EXECUTED' => 'Lunas / BKU',
        default => $status
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

{{-- 1. HEADER KERJA --}}
<div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #f8f9fc, #eef2ff); border-left: 4px solid #4361ee !important;">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <h4 class="fw-bold mb-0 text-dark">Detail SP2D Kontrak</h4>
                    <span class="badge {{ $badgeStatus }} px-2 py-1">{{ $formatStatus($statusSp2d) }}</span>
                </div>
                <div class="row g-2 mt-2" style="font-size: 13px;">
                    <div class="col-md-6"><span class="text-muted">Nomor SP2D:</span> <strong class="text-primary">{{ $sp2d?->nomor_sp2d ?? 'Belum ada' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Nomor NPI:</span> <strong>{{ $npi->nomor_npi ?? '-' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Nomor SPM:</span> <strong>{{ $spm?->nomor_spm ?? '-' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Nomor SPP:</span> <strong>{{ $spp?->nomor_spp ?? '-' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Pekerjaan:</span> <strong>{{ $kontrak?->nama_pekerjaan ?? '-' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Vendor:</span> <strong>{{ $vendor?->nama_pihak ?? '-' }}</strong></div>
                </div>
                <div class="mt-3 fs-5">
                    <span class="text-muted" style="font-size: 14px;">Nilai SP2D:</span> <strong class="text-success">Rp {{ number_format($nominalSp2d, 0, ',', '.') }}</strong>
                </div>
            </div>

            <div class="d-flex flex-column gap-2" style="min-width: 200px;">
                <div class="d-flex flex-wrap gap-1 justify-content-end mb-2">
                    @if($sp2d && !in_array($statusSp2d, ['BELUM DIBUAT', 'DRAFT']))
                        <span class="badge {{ $ppkApproval?->status === 'APPROVED' ? 'bg-success' : ($ppkApproval?->status === 'PENDING' ? 'bg-warning text-dark' : (in_array($ppkApproval?->status, ['REVISION','REJECTED']) ? 'bg-danger' : 'bg-light text-dark border')) }}">
                            PPK: {{ $ppkApproval?->status ?? 'N/A' }}
                        </span>
                        <span class="badge {{ $kasubbagApproval?->status === 'APPROVED' ? 'bg-success' : ($kasubbagApproval?->status === 'PENDING' ? 'bg-warning text-dark' : (in_array($kasubbagApproval?->status, ['REVISION','REJECTED']) ? 'bg-danger' : 'bg-light text-dark border')) }}">
                            KSB: {{ $kasubbagApproval?->status ?? 'N/A' }}
                        </span>
                        <span class="badge {{ $ppspmApproval?->status === 'APPROVED' ? 'bg-success' : ($ppspmApproval?->status === 'PENDING' ? 'bg-warning text-dark' : (in_array($ppspmApproval?->status, ['REVISION','REJECTED']) ? 'bg-danger' : 'bg-light text-dark border')) }}">
                            PPSPM: {{ $ppspmApproval?->status ?? 'N/A' }}
                        </span>
                    @endif
                </div>

                <a href="{{ route('sp2ds.kontrak.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="material-icons-outlined" style="font-size:14px; vertical-align: middle;">arrow_back</i> Kembali
                </a>

                @if($statusSp2d === 'DISETUJUI_FINAL' || $statusSp2d === 'EXECUTED')
                    <a href="{{ route('sp2ds.cetak-pdf', $sp2d->id) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="material-icons-outlined" style="font-size:14px; vertical-align: middle;">print</i> Cetak PDF
                    </a>
                @endif

                @if($canSubmit)
                    <button type="button" class="btn btn-success btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#modalDraftSave" onclick="$('#submitFormFlag').val('0')">
                        <i class="material-icons-outlined" style="font-size:14px; vertical-align: middle;">save</i> Simpan Draft
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalSubmit">
                        <i class="material-icons-outlined" style="font-size:14px; vertical-align: middle;">publish</i> Ajukan Verifikasi
                    </button>
                @elseif($isEditable)
                    <button type="button" class="btn btn-success btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#modalDraftSave" onclick="$('#submitFormFlag').val('0')">
                        <i class="material-icons-outlined" style="font-size:14px; vertical-align: middle;">save</i> Simpan Draft
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- 2. PANEL STATUS & PROGRESS --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h6 class="fw-bold text-primary mb-3"><i class="material-icons-outlined align-middle me-1" style="font-size: 20px;">checklist</i> Status & Kesiapan SP2D</h6>
        <div class="row g-4">
            {{-- Bagian Kiri: Checklist Visual --}}
            <div class="col-md-6 border-end">
                <ul class="list-unstyled mb-0" style="font-size: 13px;">
                    <li class="mb-2 d-flex align-items-center">
                        <i class="material-icons-outlined {{ $npi ? 'text-success' : 'text-danger' }} me-2" style="font-size: 18px;">{{ $npi ? 'check_circle' : 'cancel' }}</i> NPI Sumber Tersedia
                    </li>
                    <li class="mb-2 d-flex align-items-center">
                        <i class="material-icons-outlined {{ $sp2d?->nomor_sp2d && $sp2d?->tanggal_sp2d ? 'text-success' : 'text-danger' }} me-2" style="font-size: 18px;">{{ $sp2d?->nomor_sp2d && $sp2d?->tanggal_sp2d ? 'check_circle' : 'cancel' }}</i> Nomor & Tanggal Tersedia
                    </li>
                    <li class="mb-2 d-flex align-items-center">
                        <i class="material-icons-outlined {{ $nominalSp2d > 0 ? 'text-success' : 'text-warning' }} me-2" style="font-size: 18px;">{{ $nominalSp2d > 0 ? 'check_circle' : 'warning' }}</i> Nominal SP2D Terkalibrasi (Rp {{ number_format($nominalSp2d,0,',','.') }})
                    </li>
                    <li class="mb-0 d-flex align-items-center">
                        <i class="material-icons-outlined {{ count($documentStatuses ?? []) > 0 ? 'text-success' : 'text-secondary' }} me-2" style="font-size: 18px;">folder</i> Dokumen Pendukung Lengkap
                    </li>
                </ul>
            </div>
            {{-- Bagian Kanan: Progress Verifikasi Paralel --}}
            <div class="col-md-6">
                <div class="fw-semibold mb-2" style="font-size: 13px;">Progress Verifikasi SP2D:</div>
                <div class="d-flex flex-wrap gap-2">
                    <div class="border rounded p-2 text-center {{ $sp2d ? 'border-primary bg-primary bg-opacity-10' : 'bg-light' }}" style="flex: 1; min-width: 100px;">
                        <div class="fw-bold" style="font-size: 12px;">Bend. Peng.</div>
                        <div style="font-size: 10px;">{{ $sp2d ? 'SUBMITTED' : 'DRAFT' }}</div>
                    </div>
                    <div class="border rounded p-2 text-center {{ $ppkApproval?->status === 'APPROVED' ? 'border-success bg-success bg-opacity-10' : ($ppkApproval?->status === 'PENDING' ? 'border-warning bg-warning bg-opacity-10' : (in_array($ppkApproval?->status, ['REVISION','REJECTED']) ? 'border-danger bg-danger bg-opacity-10' : 'bg-light')) }}" style="flex: 1; min-width: 100px;">
                        <div class="fw-bold" style="font-size: 12px;">PPK</div>
                        <div style="font-size: 10px;">{{ $ppkApproval?->status ?? 'WAITING' }}</div>
                    </div>
                    <div class="border rounded p-2 text-center {{ $kasubbagApproval?->status === 'APPROVED' ? 'border-success bg-success bg-opacity-10' : ($kasubbagApproval?->status === 'PENDING' ? 'border-warning bg-warning bg-opacity-10' : (in_array($kasubbagApproval?->status, ['REVISION','REJECTED']) ? 'border-danger bg-danger bg-opacity-10' : 'bg-light')) }}" style="flex: 1; min-width: 100px;">
                        <div class="fw-bold" style="font-size: 12px;">Kasubbag</div>
                        <div style="font-size: 10px;">{{ $kasubbagApproval?->status ?? 'WAITING' }}</div>
                    </div>
                    <div class="border rounded p-2 text-center {{ $ppspmApproval?->status === 'APPROVED' ? 'border-success bg-success bg-opacity-10' : ($ppspmApproval?->status === 'PENDING' ? 'border-warning bg-warning bg-opacity-10' : (in_array($ppspmApproval?->status, ['REVISION','REJECTED']) ? 'border-danger bg-danger bg-opacity-10' : 'bg-light')) }}" style="flex: 1; min-width: 100px;">
                        <div class="fw-bold" style="font-size: 12px;">PPSPM</div>
                        <div style="font-size: 10px;">{{ $ppspmApproval?->status ?? 'WAITING' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- KOLOM KIRI: Data Sumber --}}
    <div class="col-xl-6">
        {{-- Card NPI & SP2D --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="material-icons-outlined align-middle me-1" style="font-size:18px;">receipt</i> Ringkasan SP2D & NPI</h6>
            </div>
            <div class="card-body">
                <div class="row g-2" style="font-size: 13px;">
                    <div class="col-6"><span class="text-muted d-block">Nomor SP2D</span><strong class="text-primary">{{ $sp2d?->nomor_sp2d ?? '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Tanggal SP2D</span><strong>{{ $sp2d?->tanggal_sp2d ? \Carbon\Carbon::parse($sp2d->tanggal_sp2d)->format('d M Y') : '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Nomor NPI</span><strong>{{ $npi->nomor_npi ?? '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Tanggal NPI</span><strong>{{ $npi->tanggal_npi ? \Carbon\Carbon::parse($npi->tanggal_npi)->format('d M Y') : '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Status NPI</span><span class="badge bg-secondary">{{ $npi->status }}</span></div>
                    <div class="col-6"><span class="text-muted d-block">Uraian SP2D</span><strong>{{ $kontrak?->nama_pekerjaan ?? '-' }}</strong></div>
                </div>
            </div>
        </div>

        {{-- Card SPM & SPP --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="material-icons-outlined align-middle me-1" style="font-size:18px;">request_quote</i> Ringkasan SPM & SPP </h6>
            </div>
            <div class="card-body">
                <div class="row g-2" style="font-size: 13px;">
                    <div class="col-6"><span class="text-muted d-block">Nomor SPM</span><strong>{{ $spm?->nomor_spm ?? '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Nomor SPP</span><strong>{{ $spp?->nomor_spp ?? '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Nomor Tagihan</span><strong>{{ $tagihan?->nomor_tagihan ?? '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Nilai Tagihan Akhir</span><strong class="text-success">Rp {{ number_format($nominalSp2d, 0, ',', '.') }}</strong></div>
                </div>
            </div>
        </div>

        {{-- Card Kontrak & BAST --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="material-icons-outlined align-middle me-1" style="font-size:18px;">assignment</i> Dasar Kontrak</h6>
            </div>
            <div class="card-body">
                <div class="row g-2" style="font-size: 13px;">
                    <div class="col-12"><span class="text-muted d-block">Nama Pekerjaan</span><strong>{{ $kontrak?->nama_pekerjaan ?? '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Nomor SPK</span><strong>{{ $kontrak?->nomor_spk ?? '-' }}</strong></div>
                    <div class="col-6"><span class="text-muted d-block">Termin</span><strong>{{ $termin?->termin_ke ?? '-' }} ({{ $termin?->jenis_termin ?? '-' }})</strong></div>
                    <div class="col-4"><span class="text-muted d-block">BAST</span><strong>{{ $detailKontrak?->nomor_bast ?? '-' }}</strong></div>
                    <div class="col-4"><span class="text-muted d-block">BAPP</span><strong>{{ $detailKontrak?->nomor_bapp ?? '-' }}</strong></div>
                    <div class="col-4"><span class="text-muted d-block">BAP</span><strong>{{ $detailKontrak?->nomor_bap ?? '-' }}</strong></div>
                </div>
            </div>
        </div>
    </div>

    {{-- KOLOM KANAN: Form Validasi & Draft --}}
    <div class="col-xl-6">
        <div class="sticky-top" style="top: 1rem;">
            
            {{-- Form SP2D Kontrak --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header {{ $isEditable ? 'bg-primary text-white' : 'bg-white text-dark' }} border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="material-icons-outlined align-middle me-1" style="font-size:18px;">draw</i> Informasi SP2D</h6>
                </div>
                <div class="card-body bg-light">
                    @if($isEditable)
                        <form id="formDraftSp2d" action="{{ route('sp2ds.kontrak.store', $npi->id) }}" method="POST">
                            @csrf
                            <input type="hidden" id="submitFormFlag" name="is_submit" value="0">
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nomor SP2D <span class="text-danger">*</span></label>
                                <input type="text" name="nomor_sp2d" class="form-control fw-bold text-primary bg-light" required value="{{ old('nomor_sp2d', $sp2d?->nomor_sp2d ?? $autoNomorSp2d) }}" placeholder="Contoh: 1234/SP2D/2026">
                                <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Nomor di atas diturunkan dari SPP, ubah jika perlu.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Tanggal SP2D <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_sp2d" class="form-control" required value="{{ old('tanggal_sp2d', $sp2d?->tanggal_sp2d ? \Carbon\Carbon::parse($sp2d->tanggal_sp2d)->format('Y-m-d') : '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nominal NPI/SP2D Sah</label>
                                <input type="text" class="form-control bg-white" readonly value="Rp {{ number_format($nominalSp2d, 0, ',', '.') }}">
                                <div class="form-text text-muted">Nilai ini mengambil dari tagihan netto disetujui, tidak dapat diubah di sini.</div>
                            </div>
                        
                            <!-- Ini form dummy-submit buat disambar sama JS -->
                            <button type="submit" class="d-none" id="btnHiddenSubmit"></button>
                        </form>
                    @else
                        {{-- Mode Read-Only --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">Nomor SP2D</label>
                            <div class="fw-bold fs-5">{{ $sp2d->nomor_sp2d }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">Tanggal SP2D</label>
                            <div class="fw-bold">{{ \Carbon\Carbon::parse($sp2d->tanggal_sp2d)->format('d M Y') }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">Nominal SP2D</label>
                            <div class="fw-bold text-success fs-5">Rp {{ number_format($nominalSp2d, 0, ',', '.') }}</div>
                        </div>
                        @if($statusSp2d === 'DISETUJUI_FINAL')
                            <div class="alert alert-success border-0 mb-0 d-flex align-items-center">
                                <i class="material-icons-outlined me-2">check_circle</i>
                                Laporan SP2D Telah Diverifikasi.
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Card Vendor & Rekening --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="material-icons-outlined align-middle me-1" style="font-size:18px;">account_balance</i> Vendor & Rekening Tujuan</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2" style="font-size: 13px;">
                        <div class="col-12"><span class="text-muted d-block">Nama Vendor</span><strong>{{ $vendor?->nama_pihak ?? '-' }}</strong></div>
                        <div class="col-6"><span class="text-muted d-block">Bank</span><strong>{{ $rekening?->nama_bank ?? '-' }}</strong></div>
                        <div class="col-6"><span class="text-muted d-block">No. Rekening</span><strong class="font-monospace text-primary">{{ $rekening?->nomor_rekening ?? '-' }}</strong></div>
                        <div class="col-12"><span class="text-muted d-block">Atas Nama</span><strong>{{ $rekening?->nama_rekening ?? '-' }}</strong></div>
                    </div>
                </div>
            </div>

            {{-- Riwayat Workflow/Revisi --}}
            @if($revisionNotes->count() > 0)
            <div class="card border-0 shadow-sm mb-4 border-start border-4 border-danger">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-danger"><i class="material-icons-outlined align-middle me-1" style="font-size:18px;">replay</i> Catatan Revisi Verifier</h6>
                </div>
                <div class="card-body p-3">
                    @foreach($revisionNotes as $note)
                        <div class="mb-3 {{ !$loop->last ? 'border-bottom pb-3' : 'mb-0' }}">
                            <div class="fw-bold" style="font-size: 13px;">{{ $note['role'] }}</div>
                            <div class="text-muted" style="font-size: 11px;">{{ $note['user'] }} • {{ $note['time'] }}</div>
                            <div class="mt-1 fst-italic text-dark px-2 border-start border-2 border-danger bg-danger bg-opacity-10 py-1" style="font-size: 13px;">
                                "{{ $note['catatan'] }}"
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- MODAL AREA --}}

{{-- Modal Simpan Draft --}}
@if($isEditable)
<div class="modal fade" id="modalDraftSave" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 bg-success text-white">
                <h5 class="modal-title fw-bold">Simpan SP2D sebagai Draft?</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Data SP2D akan disimpan. Dokumen belum akan diajukan untuk diverifikasi, sehingga belum terlihat oleh PPK atau Kasubbag.
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success px-4" onclick="$('#btnHiddenSubmit').click()">Simpan Form</button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Modal Ajukan Verifikasi --}}
@if($canSubmit)
<div class="modal fade" id="modalSubmit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('sp2ds.kontrak.submit', $npi->id) }}" method="POST">
                @csrf
                <div class="modal-header border-0 bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="material-icons-outlined me-1">publish</i> Ajukan Verifikasi SP2D?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Setelah pengajuan, form SP2D akan dikunci sementara.</p>
                    <p>Notifikasi verifikasi paralel akan otomatis dikirimkan ke:</p>
                        <li><strong>PPK</strong></li>
                        <li><strong>Kepala Subbagian Keuangan dan Tata Usaha</strong></li>
                        <li><strong>PPSPM</strong></li>
                    </ul>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary px-4">Ajukan Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection
