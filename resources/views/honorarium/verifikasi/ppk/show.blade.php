@extends('layouts.app')
@section('title', 'Detail Verifikasi Honorarium — ' . $currentRole)

@php
    $ppkStatus = $ppkApproval?->status ?? 'N/A';
    $bendaharaStatus = $bendaharaApproval?->status ?? 'N/A';

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
            <i class="bi bi-check-circle fs-4"></i>
            <div>{{ session('success') }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show mb-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle fs-4"></i>
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
                    <h4 class="fw-bold mb-0 text-dark">Detail Verifikasi Honorarium</h4>
                    <span class="badge bg-primary px-2 py-1">{{ $currentRole }}</span>
                </div>
                <div class="row g-2 mt-2" style="font-size: 13px;">
                    <div class="col-md-6"><span class="text-muted">Nomor Tagihan:</span> <strong>{{ $tagihan->nomor_tagihan ?? '-' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Uraian / Deskripsi:</span> <strong>{{ $tagihan->deskripsi ?? '-' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Nilai Netto:</span> <strong class="text-success">Rp {{ number_format($nominalTotal, 0, ',', '.') }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">Penerima Honor:</span> <strong>{{ $tagihan->detailHonorarium->count() }} Orang</strong></div>
                </div>
            </div>

            <div class="d-flex flex-column gap-2" style="min-width: 200px;">
                <div class="d-flex flex-wrap gap-1 justify-content-end mb-2">
                    <span class="badge {{ $badgeClass($ppkStatus) }}" title="PPK">PPK: {{ $ppkStatus }}</span>
                    <span class="badge {{ $badgeClass($bendaharaStatus) }}" title="Bendahara">Bendahara: {{ $bendaharaStatus }}</span>
                    <span class="badge {{ $finalBadge }}">{{ $statusFinal }}</span>
                </div>

                <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali ke Antrean
                </a>

            </div>
        </div>
    </div>
</div>

{{-- PANEL PROGRESS VERIFIKASI PARALEL --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-diagram-3 me-2"></i> Progress Verifikasi (Paralel)</h6>
        <div class="row justify-content-center border py-4 rounded bg-light">
            {{-- PPABP (Submitter) --}}
            <div class="col-4 position-relative">
                <div class="text-center rounded mx-auto border-success bg-success bg-opacity-10 d-flex flex-column justify-content-center p-3" style="max-width: 280px; height: 100%;">
                    <div class="fw-bold text-success mb-1" style="font-size: 14px;">Operator PPABP</div>
                    <div class="text-muted mb-2" style="font-size: 12px;">{{ $tagihan->creator?->name ?? 'SYSTEM' }}</div>
                    <span class="badge bg-success mx-auto">DIAJUKAN</span>
                </div>
                <div class="position-absolute align-items-center d-flex fw-bold text-success" style="right: -10px; top: 50%; transform: translateY(-50%); font-size:24px;">
                    <i class="bi bi-arrow-right"></i>
                </div>
            </div>

            <div class="col-8">
                <div class="row h-100 g-3">
                    {{-- PPK --}}
                    <div class="col-sm-6">
                        <div class="border rounded p-3 text-center h-100 {{ $ppkStatus === 'APPROVED' ? 'border-success bg-success bg-opacity-10' : ($ppkStatus === 'PENDING' ? 'border-warning bg-warning bg-opacity-10' : (in_array($ppkStatus, ['REVISION','REJECTED']) ? 'border-danger bg-danger bg-opacity-10' : '')) }}">
                            <div class="fw-bold mb-1" style="font-size: 13px;">Pejabat Pembuat Komitmen</div>
                            <div class="text-muted mb-2" style="font-size: 11px;">{{ $ppkApproval?->assignedUser?->name ?? 'Verifikator PPK' }}</div>
                            <span class="badge {{ $badgeClass($ppkStatus) }}">{{ $ppkStatus }}</span>
                            @if($ppkApproval?->acted_at)
                                <div class="mt-2" style="font-size: 11px; color: #6c757d;">{{ \Carbon\Carbon::parse($ppkApproval->acted_at)->format('d M Y H:i') }}</div>
                            @endif
                        </div>
                    </div>

                    {{-- Bendahara --}}
                    <div class="col-sm-6">
                        <div class="border rounded p-3 text-center h-100 {{ $bendaharaStatus === 'APPROVED' ? 'border-success bg-success bg-opacity-10' : ($bendaharaStatus === 'PENDING' ? 'border-warning bg-warning bg-opacity-10' : (in_array($bendaharaStatus, ['REVISION','REJECTED']) ? 'border-danger bg-danger bg-opacity-10' : '')) }}">
                            <div class="fw-bold mb-1" style="font-size: 13px;">Bendahara Pengeluaran</div>
                            <div class="text-muted mb-2" style="font-size: 11px;">{{ $bendaharaApproval?->assignedUser?->name ?? 'Verifikator Bendahara' }}</div>
                            <span class="badge {{ $badgeClass($bendaharaStatus) }}">{{ $bendaharaStatus }}</span>
                            @if($bendaharaApproval?->acted_at)
                                <div class="mt-2" style="font-size: 11px; color: #6c757d;">{{ \Carbon\Carbon::parse($bendaharaApproval->acted_at)->format('d M Y H:i') }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- KOLOM KIRI --}}
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm mb-4 border-top border-4 border-primary">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-wallet2 text-primary me-2"></i> Ringkasan Nilai</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 text-center">
                        <div class="bg-primary bg-opacity-10 rounded p-4">
                            <span class="text-primary d-block mb-1">Total Netto yang Dibayarkan</span>
                            <h2 class="text-primary fw-bold mb-0">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</h2>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-3 text-center">
                            <span class="text-muted d-block small mb-1">Total Bruto</span>
                            <h5 class="mb-0 fw-bold">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</h5>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-3 text-center">
                            <span class="text-muted d-block small mb-1">Total PPh (Potongan)</span>
                            <h5 class="mb-0 fw-bold text-danger">Rp {{ number_format($tagihan->total_potongan, 0, ',', '.') }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-people text-secondary me-2"></i> Rincian Penerima</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="text-center" style="width: 50px;">No</th>
                                <th>Nama Penerima</th>
                                <th>Rekening</th>
                                <th class="text-end">Netto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tagihan->detailHonorarium as $idx => $detail)
                                <tr>
                                    <td class="text-center">{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="fw-bold" style="font-size: 13px;">{{ $detail->nama_personel }}</div>
                                        <div class="text-muted" style="font-size: 11px;">{{ $detail->nrp_nip ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <div style="font-size: 13px;">{{ $detail->nama_rekening }}</div>
                                        <div class="text-muted font-monospace" style="font-size: 11px;">{{ $detail->jenis_bank }} {{ $detail->rekening }}</div>
                                    </td>
                                    <td class="text-end fw-bold text-success" style="font-size: 13px;">Rp {{ number_format($detail->nilai_honor - $detail->pph, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada penerima.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- KOLOM KANAN --}}
    <div class="col-xl-6">
        <div class="sticky-top" style="top: 1rem; z-index: 1;">
            
            {{-- Dokumen Pendukung --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-folder text-success me-2"></i> Dokumen Pendukung</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($documentStatuses as $doc)
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <span class="fw-medium d-block" style="font-size: 14px;">{{ $doc['label'] }}</span>
                                    @if(!$doc['required'])
                                        <small class="text-muted">(Tidak Wajib)</small>
                                    @endif
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    @if($doc['status'] === 'ready')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle me-1"></i> Tersedia</span>
                                        @if($doc['path'])
                                            <a href="{{ Storage::url($doc['path']) }}" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2" title="Unduh" style="font-size: 12px;"><i class="bi bi-download"></i> Unduh</a>
                                        @endif
                                    @elseif($doc['status'] === 'missing')
                                        <span class="badge bg-danger">Belum Diunggah</span>
                                    @else
                                        <span class="badge bg-light text-dark border">Tidak Ada</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Catatan Workflow --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-chat-left-text text-secondary me-2"></i> Catatan Riwayat Revisi</h6>
                </div>
                <div class="card-body">
                    @forelse($revisionNotes as $note)
                        <div class="border-start border-3 {{ str_contains($note['role'], 'PPK') ? 'border-primary' : 'border-info' }} ps-3 mb-3">
                            <div class="fw-semibold" style="font-size: 13px;">{{ $note['role'] }}</div>
                            <div class="text-muted" style="font-size: 12px;">{{ $note['user'] }} · {{ $note['time'] }}</div>
                            <div class="mt-1 fst-italic" style="font-size: 13px;">"{{ $note['catatan'] }}"</div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-chat-square text-muted opacity-50 fs-2 mb-2"></i>
                            <div style="font-size: 13px;">Belum ada riwayat penolakan/revisi pada dokumen honorarium ini.</div>
                        </div>
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
                    <h5 class="fw-bold mb-1"><i class="bi bi-patch-question text-warning me-2"></i> Keputusan Anda: {{ $currentRole }}</h5>
                    <p class="text-muted mb-0" style="font-size: 14px;">Pastikan rincian penerima dan dokumen pendukung sudah benar sebelum memberikan persetujuan.</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-danger px-4 fw-medium" data-bs-toggle="modal" data-bs-target="#modalRevisi">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Minta Revisi
                    </button>
                    <button type="button" class="btn btn-success px-5 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalApprove">
                        <i class="bi bi-check-circle me-1"></i> Setujui Honorarium
                    </button>
                </div>
            </div>
        </div>
    </div>
@elseif($currentUserApproval?->status === 'APPROVED')
    <div class="card border-0 shadow-sm mb-4 border-top border-4 border-success">
        <div class="card-body p-4 text-center">
            <i class="bi bi-check-circle text-success" style="font-size: 48px;"></i>
            <h5 class="fw-bold mt-2">Anda telah menyetujui dokumen Honorarium ini</h5>
            <p class="text-muted mb-0">Disetujui pada {{ $currentUserApproval?->acted_at ? \Carbon\Carbon::parse($currentUserApproval->acted_at)->format('d M Y H:i') : '-' }}</p>
            <div class="mt-3 d-flex gap-2 justify-content-center">
                <span class="badge {{ $badgeClass($ppkStatus) }}">PPK: {{ $ppkStatus }}</span>
                <span class="badge {{ $badgeClass($bendaharaStatus) }}">Bendahara: {{ $bendaharaStatus }}</span>
            </div>
        </div>
    </div>
@elseif(in_array($currentUserApproval?->status ?? '', ['REVISION', 'REJECTED']))
    <div class="card border-0 shadow-sm mb-4 border-top border-4 border-danger">
        <div class="card-body p-4 text-center">
            <i class="bi bi-arrow-counterclockwise text-danger" style="font-size: 48px;"></i>
            <h5 class="fw-bold mt-2">Anda mengembalikan Honorarium ini untuk direvisi</h5>
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
            <form action="{{ route($routePrefix . '.approve', $tagihan->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-check-circle me-2"></i> Setujui Honorarium?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan menyetujui Tagihan Honorarium Nomor <strong>{{ $tagihan->nomor_tagihan }}</strong> dengan nilai Netto <strong>Rp {{ number_format($nominalTotal, 0, ',', '.') }}</strong>.</p>
                    <div class="alert alert-info border-0 py-3 small d-flex">
                        <i class="bi bi-info-circle fs-4 me-3 text-info"></i>
                        <div>
                            Tindakan ini akan menandai persetujuan Anda sebagai <strong>{{ $currentRole }}</strong>. Dokumen hanya akan dapat diajukan ke NPI jika disetujui juga oleh pihak yang lainnya secara paralel.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold">Ya, Setujui Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL REVISI --}}
<div class="modal fade" id="modalRevisi" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route($routePrefix . '.revisi', $tagihan->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-arrow-counterclockwise me-2"></i> Minta Revisi Honorarium</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tagihan <strong>{{ $tagihan->nomor_tagihan }}</strong> akan dikembalikan ke PPABP pembuat data untuk diperbaiki.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan Revisi <span class="text-danger">*</span></label>
                        <textarea name="catatan_revisi" class="form-control" rows="4" required placeholder="Tuliskan alasan mengapa dokumen ini ditolak/dikembalikan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4 fw-bold">Kembalikan untuk Revisi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
