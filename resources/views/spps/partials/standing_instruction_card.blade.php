@php
    $si = $spp->standingInstruction;
    $isPpk = Auth::user()->hasRole('PPK') || Auth::user()->hasRole('Super Admin');
    $warningNominal = false;
    if ($si && $si->status === 'FINAL') {
        if (abs($si->nominal_transfer - $spp->nominal_spp) > 0.01) {
            $warningNominal = true;
        }
    }
    $siSignedFile = null;
    if ($si) {
        $siSignedFile = $spp->relationLoaded('arsipDokumen')
            ? $spp->arsipDokumen
                ->where('jenis_dokumen', \App\Models\DokumenSpp::STANDING_INSTRUCTION_SIGNED_ARCHIVE_TYPE)
                ->where('is_active', true)
                ->sortByDesc('uploaded_at')
                ->first()
            : $spp->arsipDokumen()
                ->where('jenis_dokumen', \App\Models\DokumenSpp::STANDING_INSTRUCTION_SIGNED_ARCHIVE_TYPE)
                ->where('is_active', true)
                ->latest('uploaded_at')
                ->first();
    }
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-envelope-paper me-2 text-primary"></i> Standing Instruction (Surat Perintah Transfer)</h6>
        <div>
            @if(!$si)
                <span class="badge bg-secondary">Belum Dibuat</span>
            @elseif($si->status === 'DRAFT')
                <span class="badge bg-warning text-dark">DRAFT</span>
            @elseif($siSignedFile)
                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> SI TTD Terunggah</span>
            @else
                <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i> FINAL Tanpa File</span>
            @endif
        </div>
    </div>
    <div class="card-body">
        @if($warningNominal)
            <div class="alert alert-warning mb-3 small">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> Peringatan: Nominal Standing Instruction (Rp {{ number_format($si->nominal_transfer, 0, ',', '.') }}) berbeda dengan nominal SPP saat ini (Rp {{ number_format($spp->nominal_spp, 0, ',', '.') }}). Mohon sesuaikan kembali.
            </div>
        @endif

        @if($si)
            <div class="row mb-2">
                <div class="col-sm-4 text-muted">Nomor Surat</div>
                <div class="col-sm-8 fw-bold">{{ $si->nomor_surat ?? '-' }}</div>
            </div>
            <div class="row mb-2">
                <div class="col-sm-4 text-muted">Tanggal Surat</div>
                <div class="col-sm-8">{{ $si->tanggal_surat ? \Carbon\Carbon::parse($si->tanggal_surat)->isoFormat('D MMMM Y') : '-' }}</div>
            </div>
            <div class="row mb-2">
                <div class="col-sm-4 text-muted">KPA</div>
                <div class="col-sm-8">{{ $si->nama_kpa_snapshot ?? '-' }} <small class="text-muted">({{ $si->kpaUser?->name ?? 'Belum ada' }})</small></div>
            </div>
            <div class="row mb-2">
                <div class="col-sm-4 text-muted">Rekening Sumber</div>
                <div class="col-sm-8">
                    {{ $si->rekening_sumber_nomor ?? '-' }} a.n. {{ $si->rekening_sumber_nama ?? '-' }} <br>
                    <small class="text-muted">{{ $si->rekening_sumber_bank ?? '-' }}</small>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-sm-4 text-muted">Nominal Transfer</div>
                <div class="col-sm-8 fw-bold text-success">Rp {{ number_format($si->nominal_transfer, 0, ',', '.') }}</div>
            </div>
            <div class="row mb-2">
                <div class="col-sm-4 text-muted">Rekening Tujuan</div>
                <div class="col-sm-8">
                    {{ $si->rekening_tujuan_nomor }} a.n. {{ $si->rekening_tujuan_nama }} <br>
                    <small class="text-muted">{{ $si->rekening_tujuan_bank }}</small>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-sm-4 text-muted">File SI Bertanda Tangan</div>
                <div class="col-sm-8">
                    @if($siSignedFile)
                        <a href="{{ route('standing-instructions.signed-file', $spp->id) }}" target="_blank" class="btn btn-sm btn-outline-success fw-bold">
                            <i class="bi bi-file-earmark-check me-1"></i> Lihat File TTD
                        </a>
                        <div class="small text-muted mt-1">
                            {{ $siSignedFile->nama_file_asli }} - {{ optional($siSignedFile->uploaded_at)->translatedFormat('d M Y H:i') }}
                        </div>
                    @else
                        <span class="badge bg-secondary">Belum diunggah</span>
                    @endif
                </div>
            </div>
        @else
            <p class="text-muted small mb-0">Standing Instruction belum dibuat. Wajib dibuat sebelum PPK dapat menyetujui SPP ini.</p>
        @endif
        
        <hr>
        <div class="d-flex gap-2 justify-content-end">
            @if($si)
                <a href="{{ route('standing-instructions.print', $spp->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm fw-bold">
                    <i class="bi bi-printer me-1"></i>
                </a>
            @endif

            @if($isPpk)
                <a href="{{ route('standing-instructions.form', ['spp' => $spp->id, 'return_to' => request()->fullUrl()]) }}" class="btn btn-primary btn-sm fw-bold">
                    <i class="bi {{ $si ? 'bi-pencil' : 'bi-plus-circle' }} me-1"></i> {{ $si ? '' : 'Buat SI' }}
                </a>
                @if($si && ($si->status === 'DRAFT' || !$siSignedFile))
                    <form action="{{ route('standing-instructions.finalize', $spp->id) }}" method="POST" enctype="multipart/form-data" class="d-flex flex-wrap align-items-center gap-2">
                        @csrf
                        <input type="file" name="file_si_bertanda_tangan" class="form-control form-control-sm" accept=".pdf,application/pdf" required style="max-width: 260px;">
                        <button type="submit" class="btn btn-success btn-sm fw-bold">
                            <i class="bi bi-cloud-upload me-1"></i> Unggah SI TTD
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>
</div>
