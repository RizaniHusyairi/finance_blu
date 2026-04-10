@extends('layouts.app')
@section('title', 'Detail Verifikasi SPM Kontrak')

@php
    $statusSpmClass = match ($spmModel->status) {
        'Belum Dibuat' => 'bg-secondary',
        'DRAFT' => 'bg-warning text-dark',
        'Menunggu Verifikasi' => 'bg-info text-dark',
        'Revisi' => 'bg-danger',
        \App\Models\DokumenSpm::STATUS_DISETUJUI_FINAL => 'bg-success',
        default => 'bg-secondary',
    };

    $ppspmStatusLabel = $ppspmApproval?->status ?? 'Belum diajukan';
    $kasubbagStatusLabel = $kasubbagApproval?->status ?? 'Belum diajukan';
    $ppspmStatusClass = match($ppspmStatusLabel) {
        'APPROVED' => 'text-success', 'PENDING' => 'text-warning text-dark', 'REVISION','REJECTED' => 'text-danger', default => 'text-muted'
    };
    $kasubbagStatusClass = match($kasubbagStatusLabel) {
        'APPROVED' => 'text-success', 'PENDING' => 'text-warning text-dark', 'REVISION','REJECTED' => 'text-danger', default => 'text-muted'
    };

    $documentStatusMeta = [
        'ready' => ['label' => 'Tersedia', 'class' => 'bg-success'],
        'missing' => ['label' => 'Belum Ada', 'class' => 'bg-danger'],
        'not_required' => ['label' => 'Tak Wajib', 'class' => 'bg-secondary'],
    ];

    $sppModel = $spmModel->spp;
    $tagihan = $sppModel?->tagihan;
    $detailKontrak = $tagihan?->detailKontrak;
    $termin = $detailKontrak?->kontrakTermin;
    $kontrak = $termin?->kontrak;
    $vendor = $kontrak?->vendor;
    $rekening = $vendor?->rekening?->first();
    $dipa = $kontrak?->dipa;
    $selectedBudgetItem = $spmModel->dipaRevisionItem;
@endphp

@push('css')
    <style>
        .verif-hero { background: linear-gradient(135deg, #f8f9fa, #ffffff); border-bottom: 3px solid #6366f1; padding-bottom: 1.5rem; margin-bottom: 2rem; position: relative; }
        .verif-card { border: 0; border-radius: 1rem; box-shadow: 0 .125rem .25rem rgba(15,23,42,.04); border: 1px solid rgba(15,23,42,.08); overflow: hidden; background: #fff; }
        .verif-heading { color: #475569; font-size: .85rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; margin-bottom: 1rem; padding-bottom: .75rem; border-bottom: 1px solid rgba(15,23,42,.06); display: flex; align-items: center; gap: .5rem; }
        .info-block { margin-bottom: 1rem; }
        .info-block .label { color: #64748b; font-size: .8rem; margin-bottom: .25rem; font-weight: 600;}
        .info-block .value { font-weight: 600; color: #1e293b; line-height: 1.4; }
        .doc-row { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: .65rem 0; border-bottom: 1px solid rgba(15,23,42,.04); }
        .doc-row:last-child { border-bottom: 0; }
        .progress-box { padding: 1.5rem; border-radius: 1rem; border: 1px solid rgba(15,23,42,.08); text-align: center; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <x-page-title title="Detail Verifikasi SPM" subtitle="Kontrak" />
        <a href="{{ route('verifikasi-ppspm.spm.kontrak.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali ke Antrean</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
            <div class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill fs-5"></i><div>{{ session('success') }}</div></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- A. HEADER KEPUTUSAN --}}
    <div class="verif-hero p-4 rounded-3 shadow-sm">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
            <div class="flex-grow-1">
                <div class="mb-3">
                    <span class="badge bg-primary px-3 py-2 fs-6 mb-2">{{ $spmModel->nomor_spm ?? 'Draft SPM' }}</span>
                    <h4 class="fw-bold text-dark">{{ $kontrak?->nama_pekerjaan ?? 'Pekerjaan Tidak Diketahui' }}</h4>
                    <div class="text-muted fw-semibold">{{ $vendor?->nama_pihak ?? '-' }}</div>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-auto"><div class="info-block mb-0"><div class="label">Status SPM</div><div class="value"><span class="badge {{ $statusSpmClass }}">{{ $spmModel->status }}</span></div></div></div>
                    <div class="col-md-auto ms-md-4"><div class="info-block mb-0"><div class="label">Status Anda (PPSPM)</div><div class="value fw-bold {{ $ppspmStatusClass }}">{{ $ppspmStatusLabel }}</div></div></div>
                    <div class="col-md-auto ms-md-4"><div class="info-block mb-0"><div class="label">Status Kasubbag</div><div class="value fw-bold {{ $kasubbagStatusClass }}">{{ $kasubbagStatusLabel }}</div></div></div>
                    <div class="col-md-auto ms-md-4"><div class="info-block mb-0"><div class="label">Workflow Final</div><div class="value"><span class="badge bg-dark">{{ $statusFinal }}</span></div></div></div>
                </div>
            </div>

            <div class="d-flex flex-column gap-2" style="min-width: 250px;">
                <a href="{{ route('spms.cetak-pdf', $spmModel->id) }}" target="_blank" class="btn btn-outline-primary shadow-sm"><i class="bi bi-file-earmark-pdf me-1"></i> Pratinjau PDF SPM</a>
                
            </div>
        </div>
    </div>

    {{-- B. PANEL PROGRESS PARALEL --}}
    <div class="card verif-card mb-4 border-primary border-opacity-25" style="background-color: #f8f9ff;">
        <div class="card-body p-4">
            <h6 class="fw-bold text-primary mb-4"><i class="bi bi-diagram-3 me-2"></i> Progress Verifikasi Paralel</h6>
            <div class="row g-4 justify-content-center">
                <div class="col-md-4">
                    <div class="progress-box bg-white shadow-sm border-success">
                        <div class="mb-2"><i class="bi bi-person-workspace fs-2 text-success"></i></div>
                        <h6 class="fw-bold mb-1">Operator BLU</h6>
                        <span class="badge bg-success">Selesai (Diajukan)</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="progress-box bg-white shadow-sm {{ $ppspmApproval?->status === 'APPROVED' ? 'border-success' : ($ppspmApproval?->status === 'REVISION' ? 'border-danger' : 'border-warning') }}">
                        <div class="mb-2"><i class="bi bi-person-check fs-2 {{ $ppspmStatusClass }}"></i></div>
                        <h6 class="fw-bold mb-1">PPSPM (Anda)</h6>
                        <span class="badge {{ match($ppspmApproval?->status) { 'APPROVED' => 'bg-success', 'REVISION' => 'bg-danger', default => 'bg-warning text-dark' } }}">{{ $ppspmStatusLabel }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="progress-box bg-white shadow-sm {{ $kasubbagApproval?->status === 'APPROVED' ? 'border-success' : ($kasubbagApproval?->status === 'REVISION' ? 'border-danger' : 'border-warning') }}">
                        <div class="mb-2"><i class="bi bi-person-badge fs-2 {{ $kasubbagStatusClass }}"></i></div>
                        <h6 class="fw-bold mb-1">Kasubbag</h6>
                        <span class="badge {{ match($kasubbagApproval?->status) { 'APPROVED' => 'bg-success', 'REVISION' => 'bg-danger', default => 'bg-warning text-dark' } }}">{{ $kasubbagStatusLabel }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        {{-- ========================================== --}}
        {{-- KOLOM KIRI (SUMBER DATA) --}}
        {{-- ========================================== --}}
        <div class="col-xl-6">
            
            {{-- Card SPM --}}
            <div class="card verif-card mb-4">
                <div class="card-body p-4">
                    <div class="verif-heading text-primary"><i class="bi bi-file-earmark-check"></i> 1. Ringkasan SPM</div>
                    <div class="row g-3">
                        <div class="col-sm-6"><div class="info-block"><div class="label">Nomor SPM</div><div class="value">{{ $spmModel->nomor_spm ?? '-' }}</div></div></div>
                        <div class="col-sm-6"><div class="info-block"><div class="label">Tanggal SPM</div><div class="value">{{ optional($spmModel->tanggal_spm)->format('d F Y') ?? '-' }}</div></div></div>
                        <div class="col-sm-6"><div class="info-block"><div class="label">Nilai SPM</div><div class="value fs-5 fw-bold text-success">Rp {{ number_format($spmModel->nominal_spm ?? 0, 0, ',', '.') }}</div></div></div>
                        <div class="col-sm-6"><div class="info-block"><div class="label">Uraian / Catatan</div><div class="value fst-italic text-muted">{{ $spmModel->catatan ?? '-' }}</div></div></div>
                    </div>
                </div>
            </div>

            {{-- Card SPP & Tagihan --}}
            <div class="card verif-card mb-4">
                <div class="card-body p-4">
                    <div class="verif-heading text-primary"><i class="bi bi-receipt"></i> 2. SPP & Tagihan</div>
                    <div class="row g-3">
                        <div class="col-sm-6"><div class="info-block"><div class="label">Nomor SPP</div><div class="value">{{ $sppModel->nomor_spp ?? '-' }}</div></div></div>
                        <div class="col-sm-6"><div class="info-block"><div class="label">Tanggal SPP</div><div class="value">{{ optional($sppModel->tanggal_spp)->format('d F Y') ?? '-' }}</div></div></div>
                        <div class="col-sm-6"><div class="info-block"><div class="label">Nomor Tagihan</div><div class="value">{{ $tagihan?->nomor_tagihan ?? '-' }}</div></div></div>
                        <div class="col-sm-6"><div class="info-block"><div class="label">Sifat / Jenis Tagihan</div><div class="value">{{ $spmModel->jenis_tagihan ?? '-' }}</div></div></div>
                        <div class="col-12"><hr class="my-1"></div>
                        <div class="col-sm-4"><div class="info-block"><div class="label">Nilai Bruto</div><div class="value">Rp {{ number_format($tagihan?->total_bruto ?? 0, 0, ',', '.') }}</div></div></div>
                        <div class="col-sm-4"><div class="info-block"><div class="label">Potongan</div><div class="value text-danger">Rp {{ number_format($tagihan?->total_potongan ?? 0, 0, ',', '.') }}</div></div></div>
                        <div class="col-sm-4"><div class="info-block"><div class="label">Nilai Netto</div><div class="value text-success fw-bold">Rp {{ number_format($tagihan?->total_netto ?? 0, 0, ',', '.') }}</div></div></div>
                    </div>
                </div>
            </div>

            {{-- Card Dasar Kontrak --}}
            <div class="card verif-card mb-4">
                <div class="card-body p-4">
                    <div class="verif-heading text-primary"><i class="bi bi-file-ruled"></i> 3. Dasar Kontrak & Termin</div>
                    <div class="row g-3">
                        <div class="col-sm-6"><div class="info-block"><div class="label">Nomor SPK</div><div class="value">{{ $kontrak?->nomor_spk ?? '-' }}</div></div></div>
                        <div class="col-sm-6"><div class="info-block"><div class="label">Termin</div><div class="value">Ke-{{ $termin?->termin_ke ?? '-' }} ({{ $termin?->jenis_termin ?? '-' }})</div></div></div>
                        <div class="col-12"><div class="info-block"><div class="label">BAPP</div><div class="value">{{ $detailKontrak?->nomor_bapp ?? '-' }}</div></div></div>
                        <div class="col-12"><div class="info-block"><div class="label">BAST</div><div class="value">{{ $detailKontrak?->nomor_bast ?? '-' }}</div></div></div>
                        <div class="col-12"><div class="info-block"><div class="label">BAP</div><div class="value">{{ $detailKontrak?->nomor_bap ?? '-' }}</div></div></div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ========================================== --}}
        {{-- KOLOM KANAN (VALIDASI) --}}
        {{-- ========================================== --}}
        <div class="col-xl-6">
            
            {{-- Card Anggaran & Akun --}}
            <div class="card verif-card mb-4">
                <div class="card-body p-4">
                    <div class="verif-heading text-primary"><i class="bi bi-wallet2"></i> 4. Validasi Anggaran & Akun</div>
                    <div class="info-block mb-3"><div class="label">DIPA / Tahun</div><div class="value">{{ $dipa?->nomor_dipa ?? '-' }} <span class="text-muted fw-normal">({{ $spmModel->tahun_anggaran ?? '-' }})</span></div></div>
                    
                    <div class="p-3 bg-light rounded border border-primary border-opacity-25 mb-3">
                        <div class="label text-primary fw-bold small mb-1">Item COA / MAK</div>
                        <div class="value fs-5">@if($selectedBudgetItem?->coa) {{ $selectedBudgetItem->coa->kode_mak_lengkap }} @else <span class="text-danger">Belum Tersedia</span> @endif</div>
                        <div class="text-muted small lh-sm mt-1">{{ $selectedBudgetItem?->coa?->nama_akun ?? '-' }}</div>
                    </div>

                    <div class="d-flex justify-content-between p-3 rounded" style="background-color: #e8f5e9;">
                        <span class="fw-bold text-success">Total Pembayaran:</span>
                        <span class="fw-bold text-success fs-5">Rp {{ number_format($spmModel->nominal_spm, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Card Vendor & Rekening --}}
            <div class="card verif-card mb-4">
                <div class="card-body p-4">
                    <div class="verif-heading text-primary"><i class="bi bi-bank"></i> 5. Validasi Transferee / Vendor</div>
                    <div class="info-block mb-3"><div class="label">Nama Vendor</div><div class="value">{{ $vendor?->nama_pihak ?? '-' }}</div></div>
                    <div class="info-block mb-3"><div class="label">NPWP</div><div class="value font-monospace">{{ $vendor?->npwp ?? '-' }}</div></div>
                    <hr class="text-muted my-3">
                    <div class="p-3 bg-light rounded border border-warning border-opacity-50">
                        <div class="info-block mb-2"><div class="label">Bank Tujuan</div><div class="value">{{ $rekening?->nama_bank ?? '-' }}</div></div>
                        <div class="info-block mb-2"><div class="label">Nomor Rekening</div><div class="value font-monospace fs-5 text-primary">{{ $rekening?->nomor_rekening ?? 'BELUM ADA' }}</div></div>
                        <div class="info-block mb-0"><div class="label">A.N. Rekening</div><div class="value fw-bold">{{ $rekening?->nama_rekening ?? '-' }}</div></div>
                    </div>
                </div>
            </div>

            {{-- Card Dokumen Pendukung --}}
            <div class="card verif-card mb-4">
                <div class="card-body p-4">
                    <div class="verif-heading text-primary"><i class="bi bi-paperclip"></i> 6. Dokumen Pendukung Fisik/Digital</div>
                    @foreach($documentStatuses as $document)
                        @php($docMeta = $documentStatusMeta[$document['status']] ?? $documentStatusMeta['missing'])
                        <div class="doc-row">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge {{ $docMeta['class'] }}" style="width: 80px;">{{ $docMeta['label'] }}</span>
                                <div class="fw-semibold text-dark">{{ $document['label'] }}</div>
                            </div>
                            <div>
                                @if($document['is_available'])
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($document['path']) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i> Buka</a>
                                @else
                                    <span class="text-muted small px-2">-</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Card Catatan Resolusi --}}
            @if($latestPpspmRevisionNote || $latestKasubbagRevisionNote)
            <div class="card verif-card mb-4 border-danger border-opacity-50">
                <div class="card-body p-4">
                    <div class="verif-heading text-danger"><i class="bi bi-exclamation-triangle"></i> Catatan Revisi</div>
                    @if($latestPpspmRevisionNote)
                        <div class="alert alert-danger mb-2">
                            <strong class="d-block mb-1">Catatan Anda (PPSPM):</strong>
                            {{ $latestPpspmRevisionNote->catatan ?? '-' }}
                        </div>
                    @endif
                    @if($latestKasubbagRevisionNote)
                        <div class="alert alert-warning mb-0">
                            <strong class="d-block mb-1">Catatan Kasubbag:</strong>
                            {{ $latestKasubbagRevisionNote->catatan ?? '-' }}
                        </div>
                    @endif
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- C. PANEL KEPUTUSAN BAWAH --}}
    @if($canAct)
        <div class="card shadow border-0 bg-white sticky-bottom mb-4" style="bottom: 1rem; z-index: 10;">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-1">Keputusan Anda (PPSPM)</h5>
                    <div class="text-muted small">Berdasarkan hasil verifikasi dokumen.</div>
                </div>
                <div class="d-flex gap-3">
                    <button type="button" class="btn btn-danger btn-lg px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bi bi-x-circle me-1"></i> Minta Revisi
                    </button>
                    <button type="button" class="btn btn-success btn-lg px-5 shadow-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                        <i class="bi bi-check-circle me-1"></i> Setujui Dokumen
                    </button>
                </div>
            </div>
        </div>
    @elseif($ppspmApproval?->status === 'APPROVED')
        <div class="alert alert-success border-0 shadow-sm p-4 d-flex align-items-center mb-4">
             <i class="bi bi-check-circle-fill fs-1 me-4 text-success"></i>
             <div>
                 <h5 class="fw-bold mb-1 text-success">Anda telah menyetujui dokumen SPM ini.</h5>
                 <div>Tanggal Tindakan: {{ optional($ppspmApproval->acted_at)->format('d M Y H:i') }}</div>
                 <div class="mt-1 small">Status Kasubbag saat ini: <strong>{{ $kasubbagStatusLabel }}</strong></div>
             </div>
        </div>
    @elseif($ppspmApproval?->status === 'REVISION')
        <div class="alert alert-danger border-0 shadow-sm p-4 d-flex align-items-center mb-4">
             <i class="bi bi-exclamation-circle-fill fs-1 me-4 text-danger"></i>
             <div>
                 <h5 class="fw-bold mb-1 text-danger">Anda mengembalikan dokumen ini untuk revisi.</h5>
                 <div>Tanggal Tindakan: {{ optional($ppspmApproval->acted_at)->format('d M Y H:i') }}</div>
                 <div class="mt-1 text-dark fst-italic">Catatan: "{{ $latestPpspmRevisionNote?->catatan ?? '-' }}"</div>
             </div>
        </div>
    @endif

    {{-- MODAL APPROVE --}}
    @if($canAct)
    <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title fw-bold">Setujui SPM ini?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <i class="bi bi-shield-check text-success" style="font-size: 4rem;"></i>
                    <h5 class="fw-bold mt-3">Konfirmasi Persetujuan</h5>
                    <p class="text-muted mb-0">Tindakan ini akan menyelesaikan dan mengunci tahap verifikasi milik PPSPM. Pastikan Anda telah memeriksa kesesuaian nilai SPM dan Rekening Tujuan.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
                    <form action="{{ route('verifikasi-ppspm.spm.kontrak.approve', $spmModel->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success px-5 fw-bold">Ya, Setujui Sekarang</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL REJECT / REVISI --}}
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('verifikasi-ppspm.spm.kontrak.revisi', $spmModel->id) }}" method="POST" class="modal-content border-0">
                @csrf
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold">Kembalikan untuk Revisi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-3">Tindakan ini akan mengembalikan dokumen SPM ke tahap pembuatan. Dokumen tidak bisa dilanjutkan hingga Operator BLU memperbaiki catatan Anda.</p>
                    <div class="mb-0">
                        <label class="form-label fw-bold text-danger">Catatan Revisi <span class="text-danger">*</span></label>
                        <textarea name="catatan_revisi" class="form-control form-control-lg" rows="4" placeholder="Jelaskan alasan pengembalian secara detail..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4 fw-bold">Konfirmasi Penolakan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

@endsection
