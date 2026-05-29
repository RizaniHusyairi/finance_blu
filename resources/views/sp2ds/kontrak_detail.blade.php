@extends('layouts.app')
@section('title', 'Detail SP2D Kontrak')

@php
    $badgeStatus = match($statusSp2d) {
        'BELUM DIBUAT' => 'bg-warning text-dark',
        'DRAFT' => 'bg-secondary',
        'REVISI' => 'bg-danger',
        'MENUNGGU_VERIFIKASI' => 'bg-info text-dark',
        'DISETUJUI_FINAL' => 'bg-success',
        'MENUNGGU_UPLOAD' => 'bg-success',
        'SP2D_TERBIT' => 'bg-success',
        'EXECUTED' => 'bg-primary',
        default => 'bg-light text-dark'
    };

    $formatStatus = fn($status) => match($status) {
        'BELUM DIBUAT' => 'Belum Dibuat',
        'DRAFT' => 'Draft',
        'REVISI' => 'Draft Revisi',
        'MENUNGGU_VERIFIKASI' => 'Menunggu Verifikasi',
        'DISETUJUI_FINAL' => 'Disetujui Final',
        'MENUNGGU_UPLOAD' => 'Menunggu Upload SP2D',
        'SP2D_TERBIT' => 'SP2D Terbit',
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
                        <span class="badge {{ $koordinatorApproval?->status === 'APPROVED' ? 'bg-success' : ($koordinatorApproval?->status === 'PENDING' ? 'bg-warning text-dark' : (in_array($koordinatorApproval?->status, ['REVISION','REJECTED']) ? 'bg-danger' : 'bg-light text-dark border')) }}">
                            Koor: {{ $koordinatorApproval?->status ?? 'N/A' }}
                        </span>
                    @endif
                </div>

                <a href="{{ route('sp2ds.kontrak.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="material-icons-outlined" style="font-size:14px; vertical-align: middle;">arrow_back</i> Kembali
                </a>

                @if(in_array($statusSp2d, ['DISETUJUI_FINAL', 'MENUNGGU_UPLOAD', 'SP2D_TERBIT', 'EXECUTED']))
                    <a href="{{ route('sp2ds.cetak-pdf', $sp2d->id) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="material-icons-outlined" style="font-size:14px; vertical-align: middle;">print</i> Cetak PDF
                    </a>
                @endif

                @if($canSubmit)
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalSubmit">
                        <i class="material-icons-outlined" style="font-size:14px; vertical-align: middle;">publish</i> Ajukan Verifikasi
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

@include('sp2ds.partials.bku-status', ['tagihan' => $tagihan, 'sp2d' => $sp2d])

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
                    <div class="border rounded p-2 text-center {{ $koordinatorApproval?->status === 'APPROVED' ? 'border-success bg-success bg-opacity-10' : ($koordinatorApproval?->status === 'PENDING' ? 'border-warning bg-warning bg-opacity-10' : (in_array($koordinatorApproval?->status, ['REVISION','REJECTED']) ? 'border-danger bg-danger bg-opacity-10' : 'bg-light')) }}" style="flex: 1; min-width: 100px;">
                        <div class="fw-bold" style="font-size: 12px;">Koordinator</div>
                        <div style="font-size: 10px;">{{ $koordinatorApproval?->status ?? 'WAITING' }}</div>
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
                        {{-- Read-Only Summary + Edit Button --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">Nomor SP2D</label>
                            <div class="fw-bold fs-5 text-primary">{{ $sp2d?->nomor_sp2d ?? '[ BELUM DIISI ]' }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">Tanggal SP2D</label>
                            <div class="fw-bold">{{ $sp2d?->tanggal_sp2d ? \Carbon\Carbon::parse($sp2d->tanggal_sp2d)->format('d M Y') : '[ BELUM DIISI ]' }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">Nominal SP2D</label>
                            <div class="fw-bold text-success fs-5">Rp {{ number_format($nominalSp2d, 0, ',', '.') }}</div>
                        </div>
                        <button type="button" class="btn btn-warning fw-bold w-100 shadow-sm border border-warning" data-bs-toggle="modal" data-bs-target="#modalDraftSave">
                            <i class="material-icons-outlined me-1" style="font-size: 16px; vertical-align: middle;">edit</i> Edit Draft SP2D
                        </button>
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
                        @php $signedSp2dArsip = $sp2d?->signed_arsip; @endphp

                        @if($statusSp2d === 'DISETUJUI_FINAL')
                            <div class="alert alert-success border-0 d-flex align-items-start gap-2">
                                <i class="material-icons-outlined">check_circle</i>
                                <div>
                                    <div class="fw-semibold">SP2D telah disetujui seluruh verifikator.</div>
                                    <div class="small">Unggah file SP2D bertandatangan terlebih dahulu, kemudian lanjutkan upload bukti transfer.</div>
                                </div>
                            </div>
                            <form action="{{ route('sp2ds.kontrak.upload-signed-sp2d', $sp2d->id) }}" method="POST" enctype="multipart/form-data" class="border rounded-3 bg-white p-3">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">File SP2D Bertandatangan <span class="text-danger">*</span></label>
                                    <input type="file" name="file_sp2d_ttd" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <div class="form-text">PDF / JPG / PNG, maksimal 10MB.</div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 fw-semibold" onclick="return confirm('Unggah file SP2D bertandatangan sekarang?')">
                                    <i class="material-icons-outlined align-middle me-1" style="font-size: 18px;">upload_file</i>
                                    Unggah SP2D Bertandatangan
                                </button>
                            </form>
                        @elseif(in_array($statusSp2d, ['SP2D_TERBIT', 'MENUNGGU_UPLOAD']))
                            <div class="alert alert-success border-0 d-flex align-items-start gap-2">
                                <i class="material-icons-outlined">check_circle</i>
                                <div>
                                    <div class="fw-semibold">SP2D bertandatangan sudah diunggah.</div>
                                    @if($signedSp2dArsip)
                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($signedSp2dArsip->path_file) }}" target="_blank" class="small fw-semibold text-primary d-block">
                                            Lihat file SP2D: {{ $signedSp2dArsip->nama_file_asli }}
                                        </a>
                                    @endif
                                    <div class="small mt-1">Selanjutnya upload bukti transfer untuk menyelesaikan tagihan.</div>
                                </div>
                            </div>

                            <form action="{{ route('sp2ds.kontrak.upload-signed-sp2d', $sp2d->id) }}" method="POST" enctype="multipart/form-data" class="border rounded-3 bg-light p-3 mb-3">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label fw-semibold small">Ganti File SP2D Bertandatangan <span class="text-muted">(opsional)</span></label>
                                    <input type="file" name="file_sp2d_ttd" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                                </div>
                                <button type="submit" class="btn btn-outline-secondary btn-sm" onclick="return confirm('Ganti file SP2D bertandatangan?')">
                                    <i class="material-icons-outlined align-middle me-1" style="font-size: 14px;">refresh</i>
                                    Ganti File
                                </button>
                            </form>

                            <form action="{{ route('sp2ds.catat-bku', $sp2d->id) }}" method="POST" enctype="multipart/form-data" class="border rounded-3 bg-white p-3">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Keterangan Transfer <span class="text-muted">(opsional)</span></label>
                                    <textarea name="catatan_bku" class="form-control" rows="3" placeholder="Contoh: Transfer pembayaran kontrak {{ $tagihan?->nomor_tagihan }}"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Bukti Transfer SP2D <span class="text-danger">*</span></label>
                                    <input type="file" name="bukti_transfer" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <div class="form-text">PDF / JPG / PNG, maksimal 5MB.</div>
                                </div>
                                <button type="submit" class="btn btn-success w-100 fw-semibold" onclick="return confirm('Upload bukti transfer dan selesaikan tagihan ini?')">
                                    <i class="material-icons-outlined align-middle me-1" style="font-size: 18px;">upload_file</i>
                                    Upload Bukti Transfer & Selesaikan Tagihan
                                </button>
                            </form>
                        @elseif($statusSp2d === 'EXECUTED')
                            @php $buktiTransferSp2d = $sp2d?->bukti_transfer; @endphp
                            <div class="alert alert-primary border-0 mb-0">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="material-icons-outlined">task_alt</i>
                                    <div>
                                        <div class="fw-semibold">Bukti transfer sudah diunggah dan tagihan sudah SELESAI.</div>
                                        <div class="small">Lanjutkan penyetoran pajak kontrak. Setelah NTPN lengkap, tagihan akan masuk BKU.</div>
                                        @if($signedSp2dArsip)
                                            <a href="{{ \Illuminate\Support\Facades\Storage::url($signedSp2dArsip->path_file) }}" target="_blank" class="small fw-semibold text-primary d-block">
                                                Lihat SP2D bertandatangan: {{ $signedSp2dArsip->nama_file_asli }}
                                            </a>
                                        @endif
                                        @if($buktiTransferSp2d)
                                            <a href="{{ \Illuminate\Support\Facades\Storage::url($buktiTransferSp2d->path_file) }}" target="_blank" class="small fw-semibold text-primary d-block">
                                                Lihat bukti transfer: {{ $buktiTransferSp2d->nama_file_asli }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
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
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem; overflow: hidden;">
            <div class="modal-header border-0 text-white p-4" style="background: linear-gradient(135deg, #1e293b, #334155);">
                <div>
                    <h5 class="modal-title fw-bold mb-1"><i class="material-icons-outlined me-1" style="font-size: 20px; vertical-align: middle;">edit</i> Edit Draft SP2D</h5>
                    <div class="small opacity-75">Kontrak &mdash; {{ $kontrak?->nama_pekerjaan ?? 'Pencairan SP2D' }}</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formDraftSp2d" action="{{ route('sp2ds.kontrak.store', $npi->id) }}" method="POST">
                @csrf
                <input type="hidden" id="submitFormFlag" name="is_submit" value="0">
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 py-2 d-flex align-items-center gap-2 mb-4" style="font-size: 0.85rem;">
                        <i class="material-icons-outlined" style="font-size: 18px;">info</i>
                        <span>Isi data pencatatan SP2D. Setelah disimpan, Anda dapat mengajukan verifikasi.</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nomor SP2D <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_sp2d" class="form-control fw-bold text-primary bg-light" required value="{{ old('nomor_sp2d', $sp2d?->nomor_sp2d ?? $autoNomorSp2d) }}" placeholder="Contoh: 1234/SP2D/2026">
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Nomor di atas diturunkan dari SPP, ubah jika perlu.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal SP2D <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_sp2d" class="form-control" required value="{{ old('tanggal_sp2d', $sp2d?->tanggal_sp2d ? \Carbon\Carbon::parse($sp2d->tanggal_sp2d)->format('Y-m-d') : date('Y-m-d')) }}">
                    </div>

                    <div class="bg-light rounded p-3 border d-flex justify-content-between align-items-center">
                        <div class="small text-muted fw-semibold">Nilai Netto SP2D</div>
                        <div class="fw-bold text-success fs-5">Rp {{ number_format($nominalSp2d, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold px-4 shadow-sm border border-warning">
                        <i class="material-icons-outlined me-1" style="font-size: 16px; vertical-align: middle;">save</i> Simpan Draft SP2D
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Modal Ajukan Verifikasi --}}
@if($canSubmit)
<div class="modal fade" id="modalSubmit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem; overflow: hidden;">
            <form action="{{ route('sp2ds.kontrak.submit', $npi->id) }}" method="POST">
                @csrf
                <div class="modal-header border-0 text-white p-4" style="background: linear-gradient(135deg, #1d4ed8, #3b82f6);">
                    <div>
                        <h5 class="modal-title fw-bold mb-1"><i class="material-icons-outlined me-1" style="font-size: 20px; vertical-align: middle;">publish</i> Ajukan Verifikasi SP2D</h5>
                        <div class="small opacity-75">{{ $sp2d?->nomor_sp2d ?? '-' }} &bull; {{ $kontrak?->nama_pekerjaan ?? 'Kontrak' }}</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning border-0 py-2 d-flex align-items-start gap-2 mb-4" style="font-size: 0.85rem;">
                        <i class="material-icons-outlined mt-1" style="font-size: 18px;">warning</i>
                        <span>Setelah pengajuan, draft SP2D akan <strong>dikunci sementara</strong> dan masuk ke proses verifikasi paralel.</span>
                    </div>

                    <div class="bg-light rounded p-3 border mb-4">
                        <div class="row g-2" style="font-size: 13px;">
                            <div class="col-6"><span class="text-muted d-block">Nomor SP2D</span><strong class="text-primary">{{ $sp2d?->nomor_sp2d ?? '-' }}</strong></div>
                            <div class="col-6"><span class="text-muted d-block">Tanggal SP2D</span><strong>{{ $sp2d?->tanggal_sp2d ? \Carbon\Carbon::parse($sp2d->tanggal_sp2d)->format('d M Y') : '-' }}</strong></div>
                            <div class="col-12 mt-1"><span class="text-muted d-block">Nominal</span><strong class="text-success fs-6">Rp {{ number_format($nominalSp2d, 0, ',', '.') }}</strong></div>
                        </div>
                    </div>

                    <div class="fw-semibold small mb-2">Notifikasi verifikasi akan dikirim ke:</div>
                    <ul class="list-unstyled mb-0" style="font-size: 13px;">
                        <li class="d-flex align-items-center gap-2 mb-2"><i class="material-icons-outlined text-primary" style="font-size: 16px;">person</i> <strong>PPK</strong></li>
                        <li class="d-flex align-items-center gap-2 mb-2"><i class="material-icons-outlined text-primary" style="font-size: 16px;">person</i> <strong>Kepala Subbagian Keuangan dan Tata Usaha</strong></li>
                        <li class="d-flex align-items-center gap-2 mb-2"><i class="material-icons-outlined text-primary" style="font-size: 16px;">person</i> <strong>PPSPM</strong></li>
                        <li class="d-flex align-items-center gap-2"><i class="material-icons-outlined text-primary" style="font-size: 16px;">person</i> <strong>Koordinator Keuangan</strong></li>
                    </ul>
                </div>
                <div class="modal-footer border-0 bg-light px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm">
                        <i class="material-icons-outlined me-1" style="font-size: 16px; vertical-align: middle;">send</i> Ajukan Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection
