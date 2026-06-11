@php
    $documentId = $document?->id;
    $nomorField = ['spp' => 'nomor_spp', 'spm' => 'nomor_spm', 'npi' => 'nomor_npi', 'sp2d' => 'nomor_sp2d'][$jenis] ?? null;
    $tanggalField = ['spp' => 'tanggal_spp', 'spm' => 'tanggal_spm', 'npi' => 'tanggal_npi', 'sp2d' => 'tanggal_sp2d'][$jenis] ?? null;
    $nomor = $document && $nomorField ? $document->{$nomorField} : null;
    $tanggalRaw = $document && $tanggalField ? $document->{$tanggalField} : null;
    $tanggal = $tanggalRaw ? \Carbon\Carbon::parse($tanggalRaw)->format('d/m/Y') : '-';
    $nominal = $document?->nominal_spp ?? $document?->nominal_spm ?? $document?->jumlah_uang ?? null;
    $status = $document?->status ?? 'BELUM ADA';
    $instanceStatus = $instance?->status;
    $submittable = $document && $canSubmit && in_array($document->status, ['DRAFT', 'Revisi', 'REVISI', 'REVISI_PPK', 'REVISI_KASUBBAG', 'DITOLAK_PPK', 'DITOLAK_KASUBBAG'], true);
@endphp

<div class="card process-card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <div class="process-section-title">{{ $label }}</div>
                <h5 class="mb-0">{{ $nomor ?: 'Draft belum tersedia' }}</h5>
            </div>
            <div class="text-end">
                <span class="badge bg-light text-dark border">{{ $status }}</span>
                @if($instanceStatus)
                    <span class="badge bg-secondary ms-1">{{ $instanceStatus }}</span>
                @endif
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="text-muted small">Tanggal</div>
                <div class="fw-semibold">{{ $tanggal }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Nominal</div>
                <div class="fw-semibold">{{ $nominal !== null ? 'Rp ' . number_format((float) $nominal, 0, ',', '.') : '-' }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Workflow</div>
                <div class="fw-semibold">{{ $instanceStatus ?: 'Belum diajukan' }}</div>
            </div>
        </div>

        @if($document)
            <div class="d-flex flex-wrap process-actions mb-3">
                @if($pdfRoute)
                    <a href="{{ $pdfRoute }}" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Cetak PDF
                    </a>
                @endif
                @if($submittable && $submitRoute)
                    <form method="POST" action="{{ $submitRoute }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-send me-1"></i>Ajukan {{ $label }}
                        </button>
                    </form>
                @endif
            </div>

            @if($jenis === 'spp' && Route::has('spps.upload-signed'))
                <form method="POST" action="{{ route('spps.upload-signed', $document->id) }}" enctype="multipart/form-data" class="row g-2 align-items-end mb-3">
                    @csrf
                    <div class="col-md-8">
                        <label class="form-label small text-muted mb-1">Upload SPP bertandatangan</label>
                        <input type="file" name="file_spp_ttd" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-sm btn-outline-secondary w-100" type="submit"><i class="bi bi-upload me-1"></i>Upload</button>
                    </div>
                </form>
            @endif
        @endif

        @if($myApprovals->isNotEmpty())
            <div class="border rounded p-3 bg-light">
                <div class="fw-semibold mb-2">Approval pending untuk Anda</div>
                @foreach($myApprovals as $approval)
                    <form method="POST" action="{{ route('proses-tagihan.dokumen.aksi', [$tagihan->id, $jenis]) }}" class="mb-3">
                        @csrf
                        <input type="hidden" name="approval_id" value="{{ $approval->id }}">
                        <input type="hidden" name="dokumen_id" value="{{ $documentId }}">
                        <div class="small text-muted mb-1">{{ $approval->nama_step }} - {{ $approval->role_code }}</div>
                        <textarea name="catatan" class="form-control form-control-sm mb-2" rows="2" placeholder="Catatan approval/revisi/penolakan"></textarea>
                        <div class="d-flex flex-wrap gap-2">
                            <button name="aksi" value="approve" class="btn btn-sm btn-success" type="submit"><i class="bi bi-check-lg me-1"></i>Approve</button>
                            <button name="aksi" value="revisi" class="btn btn-sm btn-warning" type="submit"><i class="bi bi-arrow-counterclockwise me-1"></i>Revisi</button>
                            <button name="aksi" value="reject" class="btn btn-sm btn-danger" type="submit"><i class="bi bi-x-lg me-1"></i>Reject</button>
                        </div>
                    </form>
                @endforeach
            </div>
        @elseif($document && $instance)
            <div class="small text-muted">Tidak ada approval pending untuk akun Anda pada {{ $label }}.</div>
        @endif
    </div>
</div>
