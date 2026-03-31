@extends('layouts.app')
@section('title', 'Verifikasi Dokumen Tagihan (BAST)')

@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('ppk.tagihan.kontrak.index') }}" class="btn btn-sm btn-outline-secondary mb-2 rounded-pill shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Antrean
            </a>
            <h4 class="mb-0 fw-bold text-dark">Verifikasi Dokumen Tagihan (BAST)</h4>
        </div>
        <div>
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm fs-6"><i class="bi bi-hourglass-split me-1"></i> MENUNGGU VERIFIKASI PPK</span>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 alert-dismissible fade show shadow-sm">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Layout Utama Split-Screen --}}
    <div class="row g-4">
        
        {{-- KOLOM KIRI: PDF Viewer & Tabs (col-lg-8) --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-0 border-bottom rounded-top-4">
                    <ul class="nav nav-tabs px-3" id="docTabs" role="tablist">
                        @if($tagihan->detailKontrak && $tagihan->detailKontrak->file_bast)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold py-3 border-0 border-bottom border-3" id="bast-tab" data-bs-toggle="tab" data-bs-target="#bast" type="button" role="tab" aria-controls="bast" aria-selected="true" style="border-bottom-color: #0d6efd !important;">
                                <i class="bi bi-file-earmark-pdf text-danger me-1"></i> BAST
                            </button>
                        </li>
                        @endif

                        @if($tagihan->detailKontrak && $tagihan->detailKontrak->file_bapp)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold py-3 border-0 border-bottom border-3 text-secondary" id="bapp-tab" data-bs-toggle="tab" data-bs-target="#bapp" type="button" role="tab" aria-controls="bapp" aria-selected="false">
                                <i class="bi bi-file-earmark-pdf text-danger me-1"></i> BAPP
                            </button>
                        </li>
                        @endif

                        @if($tagihan->detailKontrak && $tagihan->detailKontrak->file_invoice)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold py-3 border-0 border-bottom border-3 text-secondary" id="invoice-tab" data-bs-toggle="tab" data-bs-target="#invoice" type="button" role="tab" aria-controls="invoice" aria-selected="false">
                                <i class="bi bi-receipt me-1"></i> Invoice/Kwitansi
                            </button>
                        </li>
                        @endif

                        @if($tagihan->potongans && $tagihan->potongans->count() > 0 && $tagihan->potongans->first()->file_faktur_pajak)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold py-3 border-0 border-bottom border-3 text-secondary" id="pajak-tab" data-bs-toggle="tab" data-bs-target="#pajak" type="button" role="tab" aria-controls="pajak" aria-selected="false">
                                <i class="bi bi-file-earmark-pdf text-danger me-1"></i> Faktur Pajak
                            </button>
                        </li>
                        @endif
                    </ul>
                </div>
                
                <div class="card-body p-0">
                    <div class="tab-content" id="docTabsContent">
                        
                        {{-- Tab BAST --}}
                        @if($tagihan->detailKontrak && $tagihan->detailKontrak->file_bast)
                        <div class="tab-pane fade show active h-100" id="bast" role="tabpanel" aria-labelledby="bast-tab">
                            <iframe src="{{ Storage::url($tagihan->detailKontrak->file_bast) }}#toolbar=0" class="w-100 rounded-bottom-4" style="height: 75vh; border: none;"></iframe>
                        </div>
                        @endif

                        {{-- Tab BAPP --}}
                        @if($tagihan->detailKontrak && $tagihan->detailKontrak->file_bapp)
                        <div class="tab-pane fade h-100" id="bapp" role="tabpanel" aria-labelledby="bapp-tab">
                            <iframe src="{{ Storage::url($tagihan->detailKontrak->file_bapp) }}#toolbar=0" class="w-100 rounded-bottom-4" style="height: 75vh; border: none;"></iframe>
                        </div>
                        @endif

                        {{-- Tab Invoice --}}
                        @if($tagihan->detailKontrak && $tagihan->detailKontrak->file_invoice)
                        <div class="tab-pane fade h-100" id="invoice" role="tabpanel" aria-labelledby="invoice-tab">
                            <iframe src="{{ Storage::url($tagihan->detailKontrak->file_invoice) }}#toolbar=0" class="w-100 rounded-bottom-4" style="height: 75vh; border: none;"></iframe>
                        </div>
                        @endif

                        {{-- Tab Pajak --}}
                        @if($tagihan->potongans && $tagihan->potongans->count() > 0 && $tagihan->potongans->first()->file_faktur_pajak)
                        <div class="tab-pane fade h-100" id="pajak" role="tabpanel" aria-labelledby="pajak-tab">
                            <iframe src="{{ Storage::url($tagihan->potongans->first()->file_faktur_pajak) }}#toolbar=0" class="w-100 rounded-bottom-4" style="height: 75vh; border: none;"></iframe>
                        </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: Panel Info & Aksi (col-lg-4) --}}
        <div class="col-lg-4">
            <div class="position-sticky top-0" style="padding-top: 1rem; z-index: 10;">
                
                <div class="card border-0 shadow-sm rounded-4 w-100">
                    <div class="card-header bg-dark text-white p-4 rounded-top-4">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2"></i> Ringkasan Tagihan</h6>
                    </div>
                    <div class="card-body p-4">
                        
                        {{-- Blok Info --}}
                        <div class="mb-4">
                            <div class="small text-muted mb-1">Nomor Tagihan:</div>
                            <div class="fw-bold fs-6 text-primary mb-3">{{ $tagihan->nomor_tagihan }}</div>
                            
                            <div class="small text-muted mb-1">Nomor BAST:</div>
                            <div class="fw-bold mb-3">{{ $tagihan->detailKontrak->nomor_bast ?? '-' }}</div>
                            
                            <div class="small text-muted mb-1">Pekerjaan & Relasi:</div>
                            <div class="fw-bold mb-1">{{ $tagihan->detailKontrak->termin->kontrak->nama_pekerjaan ?? 'N/A' }}</div>
                            <div><span class="badge bg-light text-dark border"><i class="bi bi-diagram-3 me-1"></i>Termin ke-{{ $tagihan->detailKontrak->termin->termin_ke ?? '-' }} ({{ $tagihan->detailKontrak->termin->persentase ?? 0 }}%)</span></div>
                        </div>

                        <hr class="text-muted">

                        {{-- Blok Finansial --}}
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3 text-muted text-uppercase small">Kalkulasi Pembayaran</h6>
                            <ul class="list-group list-group-flush border-0">
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <span class="text-muted">Nilai Bruto (Kotor)</span>
                                    <span class="fw-bold text-dark">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <span class="text-muted">Total Potongan/Pajak</span>
                                    <span class="fw-bold text-danger">- Rp {{ number_format($tagihan->total_potongan, 0, ',', '.') }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent border-top border-2 mt-2 pt-3">
                                    <span class="fw-bold text-dark">Nilai Netto (Dibayar)</span>
                                    <span class="fw-black text-success fs-5">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</span>
                                </li>
                            </ul>
                        </div>

                        {{-- Blok Tombol Aksi --}}
                        <div class="d-grid gap-3 mt-4">
                            <button type="button" class="btn btn-outline-danger py-2 fw-bold rounded-pill" data-bs-toggle="modal" data-bs-target="#modalRevisi">
                                <i class="bi bi-arrow-return-left me-1"></i> Kembalikan (Revisi)
                            </button>
                            
                            {{-- Route aksi setuju ditambahkan nanti di Controller --}}
                            <form action="#" method="POST" id="formApproveBAST" class="m-0">
                                @csrf
                                <button type="button" class="btn btn-primary py-3 fw-bold w-100 rounded-pill fs-6 shadow-sm" onclick="confirmBASTApproval()">
                                    <i class="bi bi-check-circle me-1"></i> Setujui & Teruskan ke SPP
                                </button>
                            </form>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Kembalikan (Revisi) --}}
    <div class="modal fade" id="modalRevisi" tabindex="-1" aria-labelledby="modalRevisiLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            {{-- Route aksi tolak/revisi ditambahkan nanti di Controller --}}
            <form action="#" method="POST" class="modal-content border-0 rounded-4 shadow">
                @csrf
                <div class="modal-header bg-danger text-white border-bottom-0">
                    <h5 class="modal-title fw-bold" id="modalRevisiLabel"><i class="bi bi-exclamation-triangle me-2"></i> Kembalikan untuk Revisi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-3 text-muted">Silakan tuliskan alasan penolakan atau instruksi revisi untuk Pejabat Pengadaan terkait dokumen tagihan ini.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-danger">Catatan Revisi <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="catatan_revisi" rows="4" placeholder="Cth: File BAST tidak ditandatangani, hitungan pajak keliru..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-send-x me-1"></i> Proses Revisi</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Logika sederhana untuk mengatur visual garis biru navigasi tab Maxton
        document.querySelectorAll('#docTabs .nav-link').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('#docTabs .nav-link').forEach(t => {
                    t.classList.add('text-secondary');
                    t.style.borderBottomColor = 'transparent';
                });
                this.classList.remove('text-secondary');
                this.style.setProperty('border-bottom-color', '#0d6efd', 'important');
            });
        });

        // Trigger konfirmasi SweetAlert 2 
        function confirmBASTApproval() {
            Swal.fire({
                title: 'Menyetujui BAST?',
                text: "Anda akan mengotorisasi dokumen ini. Selanjutnya dokumen akan otomatis dilimpahkan ke proses penerbitan Surat Permintaan Pembayaran (SPP).",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd', // Warna dasar Maxton Primary
                cancelButtonColor: '#6c757d',  // Warna dasar Maxton Secondary
                confirmButtonText: '<i class="bi bi-check-circle"></i> Ya, Setujui',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-primary px-4 py-2 fw-bold',
                    cancelButton: 'btn btn-outline-secondary px-4 py-2 fw-bold',
                    actions: 'gap-3'
                },
                buttonsStyling: false // Mematikan stylesheet bawaan Sweetalert agar .btn bootstrap efektif membulatkan tombol
            }).then((result) => {
                if (result.isConfirmed) {
                    // Eksekusi POST ke Controller Setuju SPP
                    document.getElementById('formApproveBAST').submit();
                }
            });
        }
    </script>
@endpush
