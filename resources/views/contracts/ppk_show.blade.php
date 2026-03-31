@extends('layouts.app')
@section('title', 'Review Kontrak: ' . $kontrak->nomor_spk)

@push('css')
<style>
    .split-container {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
    }
    .pdf-viewer {
        flex: 1 1 55%;
        min-width: 300px;
        height: 85vh;
        background: #f8f9fa;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    }
    .action-panel {
        flex: 1 1 40%;
        min-width: 300px;
        height: 85vh;
        overflow-y: auto;
        padding-right: 0.5rem;
    }
    .action-panel::-webkit-scrollbar {
        width: 6px;
    }
    .action-panel::-webkit-scrollbar-thumb {
        background-color: #adb5bd;
        border-radius: 10px;
    }
    iframe {
        width: 100%;
        height: 100%;
        border: none;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('contracts.verifikasi') }}" class="btn btn-light text-secondary border shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" title="Kembali">
            <i class="bi bi-arrow-left fs-5"></i>
        </a>
        <div>
            <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-shield-check text-primary me-2"></i>Review & Keputusan Kontrak</h4>
            <div class="text-muted small mt-1">
                Ref: <strong class="text-dark">{{ $kontrak->nomor_spk }}</strong> &bull; {{ Str::limit($kontrak->nama_pekerjaan, 50) }}
            </div>
        </div>
    </div>
</div>

<div class="split-container">
    {{-- KIRI: PDF VIEWER (60%) --}}
    <div class="pdf-viewer d-flex flex-column bg-white border border-secondary-subtle">
        <div class="p-2 border-bottom shadow-sm bg-light">
            <ul class="nav nav-pills nav-fill" id="pdfTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold border" id="spk-tab" data-bs-toggle="tab" data-bs-target="#spk-pdf" type="button" role="tab">
                        <i class="bi bi-file-earmark-pdf text-danger me-1"></i> SPK
                    </button>
                </li>
                @if($kontrak->file_spmk)
                <li class="nav-item ms-2" role="presentation">
                    <button class="nav-link fw-bold border" id="spmk-tab" data-bs-toggle="tab" data-bs-target="#spmk-pdf" type="button" role="tab">
                        <i class="bi bi-file-earmark-pdf text-danger me-1"></i> SPMK
                    </button>
                </li>
                @endif
                @if($kontrak->file_ringkasan_kontrak)
                <li class="nav-item ms-2" role="presentation">
                    <button class="nav-link fw-bold border" id="ringkasan-tab" data-bs-toggle="tab" data-bs-target="#ringkasan-pdf" type="button" role="tab">
                        <i class="bi bi-file-earmark-pdf text-danger me-1"></i> Ringkasan
                    </button>
                </li>
                @endif
            </ul>
        </div>
        <div class="tab-content flex-grow-1" id="pdfTabsContent">
            <div class="tab-pane fade show active h-100" id="spk-pdf" role="tabpanel">
                @if($kontrak->file_spk)
                    <iframe src="{{ Storage::url($kontrak->file_spk) }}"></iframe>
                @else
                    <div class="d-flex justify-content-center align-items-center h-100 text-muted bg-light">
                        <div class="text-center">
                            <i class="bi bi-file-x display-1 d-block mb-3 text-secondary"></i> 
                            <h5 class="fw-bold">File SPK Belum Diunggah</h5>
                        </div>
                    </div>
                @endif
            </div>
            @if($kontrak->file_spmk)
            <div class="tab-pane fade h-100" id="spmk-pdf" role="tabpanel">
                <iframe src="{{ Storage::url($kontrak->file_spmk) }}"></iframe>
            </div>
            @endif
            @if($kontrak->file_ringkasan_kontrak)
            <div class="tab-pane fade h-100" id="ringkasan-pdf" role="tabpanel">
                <iframe src="{{ Storage::url($kontrak->file_ringkasan_kontrak) }}"></iframe>
            </div>
            @endif
        </div>
    </div>

    {{-- KANAN: PANEL AKSI (40%) --}}
    <div class="action-panel">
        
        {{-- BLOK 1: INFO VENDOR --}}
        <div class="card border-0 shadow-sm rounded-4 mb-3" style="border-left: 5px solid #0d6efd !important;">
            <div class="card-body p-4">
                <h6 class="fw-bold text-uppercase text-muted mb-3"><i class="bi bi-building me-2"></i>Identitas Mitra (Vendor)</h6>
                <h5 class="fw-bold text-dark">{{ $kontrak->vendor->nama_perusahaan ?? '-' }}</h5>
                <div class="row mt-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Nama Direktur</small>
                        <span class="fw-medium">{{ $kontrak->vendor->nama_direktur ?? '-' }}</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">NPWP</small>
                        <span class="fw-medium font-monospace">{{ $kontrak->vendor->npwp ?? '-' }}</span>
                    </div>
                </div>
                
                @php $rek = $kontrak->vendor->rekening->first(); @endphp
                <div class="mt-3 p-3 rounded-3 d-flex align-items-center gap-3 border shadow-sm" style="background-color: #f8f9fa;">
                    <div class="bg-white p-2 text-center rounded shadow-sm border border-secondary-subtle" style="width: 45px; height: 45px;">
                        <i class="bi bi-bank2 text-primary fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block fw-bold mb-1" style="font-size: 11px;">REKENING PENCAIRAN</small>
                        @if($rek)
                            <span class="fw-bold d-block text-dark font-monospace fs-6">{{ $rek->nomor_rekening }}</span>
                            <small class="text-muted">Bank {{ $rek->nama_bank }} (A.N: {{ Str::limit($rek->nama_rekening, 20) }})</small>
                        @else
                            <span class="text-danger small fw-bold fst-italic"><i class="bi bi-exclamation-triangle"></i> Data Bank Kosong!</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- BLOK 2: KALKULATOR ANGGARAN (KRUSIAL) --}}
        <div class="card border-0 shadow-sm rounded-4 mb-3" style="border-left: 5px solid #0dcaf0 !important;">
            <div class="card-body p-4">
                <h6 class="fw-bold text-uppercase text-muted mb-3"><i class="bi bi-calculator me-2"></i>Pengecekan Anggaran DIPA</h6>
                <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-light-subtle">
                    <span class="text-muted fw-medium">Beban DIPA:</span>
                    <span class="badge bg-light text-dark border shadow-sm font-monospace">{{ $kontrak->dipa->nomor_dipa ?? '-' }}</span>
                </div>
                
                <div class="d-flex justify-content-between mt-3 mb-2">
                    <span class="text-secondary fw-bold">Pagu Tersedia Saat Ini</span>
                    <span class="fw-bold fs-6">Rp {{ number_format($sisaPagu, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2 border-bottom border-2 border-secondary pb-3">
                    <span class="text-secondary fw-bold">Nilai SPK Ini <i class="bi bi-dash-circle-fill text-danger ms-1"></i></span>
                    <span class="fw-bold text-danger fs-6">Rp {{ number_format($kontrak->nilai_total_kontrak, 0, ',', '.') }}</span>
                </div>

                <div class="p-3 mt-3 rounded-4 shadow-sm position-relative overflow-hidden @if($sisaPaguNanti >= 0) bg-success bg-opacity-10 border border-success @else bg-danger border border-danger shadow @endif">
                    @if($sisaPaguNanti >= 0)
                        <div class="position-absolute end-0 top-0 mt-n1 me-n2 opacity-25" style="transform: rotate(15deg); font-size: 4rem;">
                            <i class="bi bi-shield-check text-success"></i>
                        </div>
                    @else
                        <div class="position-absolute end-0 top-0 mt-n1 me-n2 opacity-25" style="transform: rotate(15deg); font-size: 4rem;">
                            <i class="bi bi-exclamation-octagon-fill text-white"></i>
                        </div>
                    @endif
                    
                    <div class="d-flex justify-content-between align-items-center position-relative z-1">
                        <span class="fw-bold @if($sisaPaguNanti >= 0) text-success @else text-white @endif">Estimasi Sisa Pagu:</span>
                        <h4 class="fw-bold mb-0 @if($sisaPaguNanti >= 0) text-success @else text-white @endif">Rp {{ number_format($sisaPaguNanti, 0, ',', '.') }}</h4>
                    </div>
                </div>
                
                @if($sisaPaguNanti < 0)
                    <div class="alert alert-danger mt-3 mb-0 small border-0 fw-bold shadow-sm d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0"></i> 
                        <div>Kontrak ini menyebabkan <u>defisit anggaran (Minus)</u> pada DIPA. Evaluasi sangat dianjurkan!</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- BLOK 3: SKEMA TERMIN --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-left: 5px solid #ffc107 !important;">
            <div class="card-body p-4">
                <h6 class="fw-bold text-uppercase text-muted mb-3"><i class="bi bi-list-check me-2"></i>Rencana Skema Tagihan</h6>
                
                @if($kontrak->metode_pembayaran === 'LUMPSUM')
                    <div class="p-3 bg-light border rounded-3 d-flex align-items-center gap-3">
                        <i class="bi bi-box-seam-fill text-success fs-1"></i>
                        <div>
                            <span class="badge bg-success mb-1">LUMPSUM 100%</span>
                            <div class="text-dark fw-bold small">Pembayaran dilakukan secara penuh (sekaligus) setelah penyelesaian BAST.</div>
                        </div>
                    </div>
                @else
                    <div class="badge bg-primary px-3 py-2 rounded-pill shadow-sm mb-3">
                        <i class="bi bi-signpost-split me-1"></i> TERMIN BERTAHAP ({{ $kontrak->termin->count() }} Tahap)
                    </div>
                    <ul class="list-group list-group-flush small border rounded-3 overflow-hidden shadow-sm">
                        @foreach($kontrak->termin as $t)
                        <li class="list-group-item p-3 d-flex justify-content-between align-items-center bg-white border-bottom">
                            <div>
                                <span class="fw-bold text-dark fs-6 d-block mb-1">Termin Ke-{{ $t->termin_ke }} <span class="ms-1 badge bg-secondary">{{ $t->persentase }}%</span></span>
                                <span class="text-muted" style="font-size:11px;"><i class="bi bi-tag me-1"></i>{{ str_replace('_', ' ', $t->jenis_termin) }}</span>
                            </div>
                            <span class="fw-bold text-success fs-6">Rp {{ number_format($t->nilai_bruto_termin, 0, ',', '.') }}</span>
                        </li>
                        @endforeach
                    </ul>
                @endif
                
                @if($kontrak->ada_uang_muka)
                    <div class="alert alert-warning py-3 px-3 mt-3 mb-0 border-0 shadow-sm d-flex justify-content-between align-items-center rounded-3">
                        <span class="fw-bold text-dark"><i class="bi bi-wallet2 me-2"></i> Porsi Uang Muka</span>
                        <span class="fw-bold text-dark fs-6">Rp {{ number_format($kontrak->nilai_uang_muka, 0, ',', '.') }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- BLOK 4: THE VERDICT (KEPUTUSAN) --}}
        <div class="card border-0 shadow rounded-4 bg-dark text-white mb-2 sticky-bottom" style="bottom: 15px; z-index:10; border: 1px solid rgba(255,255,255,0.1) !important;">
            <div class="card-body p-4">
                <h6 class="fw-bold text-center text-uppercase tracking-wider text-light opacity-75 mb-3" style="letter-spacing: 2px; font-size:12px;">Pusat Keputusan</h6>
                <div class="d-flex flex-column gap-3">
                    
                    <button type="button" class="btn btn-outline-light btn-lg rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#modalTolak" style="border-width: 2px;">
                        <i class="bi bi-x-circle me-1"></i> Kembalikan untuk Revisi
                    </button>
                    
                    <form action="{{ route('contracts.approve', $kontrak->id) }}" method="POST" id="formApprove" class="m-0 d-grid">
                        @csrf
                        <button type="button" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-lg" onclick="confirmApproval()" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7); border:none;">
                            <i class="bi bi-check-circle-fill me-2"></i> Setujui & Aktifkan Kontrak
                        </button>
                    </form>

                </div>
            </div>
        </div>
        
    </div>
</div>

{{-- MODAL TOLAK (REVISI) --}}
<div class="modal fade" id="modalTolak" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('contracts.reject', $kontrak->id) }}" class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            @csrf
            <div class="modal-header bg-danger text-white border-bottom-0 pb-4 pt-4 px-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2 text-warning fs-4"></i> Tolak & Kembalikan</h5>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 mt-n3 bg-white rounded-top-4 relative" style="border-top-left-radius: 1.5rem !important; border-top-right-radius: 1.5rem !important; position: relative; z-index: 2;">
                <p class="mb-4 text-muted" style="font-size: 14px; line-height: 1.6;">Berkas Draf SPK <strong>{{ $kontrak->nomor_spk }}</strong> akan dikembalikan ke Pejabat Pengadaan. Silahkan isi catatan evaluasi minimum 10 karakter.</p>
                <div class="form-floating mb-2">
                    <textarea name="notes" class="form-control fw-medium bg-light border-0 shadow-inner" id="catatanRevisi" style="height: 140px; resize:none;" placeholder="Catatan untuk Pejabat Pengadaan" required minlength="10"></textarea>
                    <label for="catatanRevisi" class="text-danger fw-bold"><i class="bi bi-pencil me-1"></i> Alasan / Catatan Perbaikan*</label>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-3 pb-3 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-light fw-bold text-secondary px-4 rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger fw-bold shadow-sm px-4 rounded-pill d-flex align-items-center">
                    <i class="bi bi-send me-2"></i>Kirim Catatan
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('script')
<script>
    function confirmApproval() {
        if(confirm("Apakah Anda yakin menyetujui Pejanjian / Kontrak ini?\nMengeklik OK akan mengaktifkan kontrak dan mencatat Legitimasi Anda secara permanen.")) {
            document.getElementById('formApprove').submit();
        }
    }
</script>
@endpush
