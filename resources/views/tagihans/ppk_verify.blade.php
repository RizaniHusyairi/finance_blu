@extends('layouts.app')
@section('title', 'Verifikasi Tagihan Termin Kontrak')

@php
    $termin = $tagihan->detailKontrak->termin ?? null;
    $kontrak = $termin?->kontrak ?? null;
    $arsip = optional($tagihan->detailKontrak)->arsipDokumen ? $tagihan->detailKontrak->arsipDokumen->where('is_active', true) : collect();
    
    $fileBapp = $arsip->firstWhere('jenis_dokumen', 'BAPP_FINAL_TTD');
    $fileBast = $arsip->firstWhere('jenis_dokumen', 'BAST_FINAL_TTD');
    $fileBap = $arsip->firstWhere('jenis_dokumen', 'BAP_FINAL_TTD');
    $fileInvoice = $arsip->firstWhere('jenis_dokumen', 'INVOICE');
    
    $hasBapp = $fileBapp || optional($tagihan->detailKontrak)->file_bapp;
    $wajibBast = ($termin?->jenis_termin === 'PELUNASAN');
    $hasBast = $fileBast || optional($tagihan->detailKontrak)->file_bast;
    $hasBap = $fileBap || optional($tagihan->detailKontrak)->file_bap;
    $hasInvoice = $fileInvoice || optional($tagihan->detailKontrak)->file_invoice;
    
    $potonganTagihans = collect($tagihan->potonganTagihan ?? []);
    $potonganAngsuranUm = $potonganTagihans->firstWhere('jenis_potongan', 'ANGSURAN_UANG_MUKA');
    $potonganPajak = $potonganTagihans->filter(fn ($item) => $item->jenis_potongan !== 'ANGSURAN_UANG_MUKA');
    $totalPotonganPajak = $potonganPajak->sum('nominal_potongan');
    $fakturPajakPath = optional($tagihan->detailKontrak)->file_faktur_pajak ?? $potonganPajak->first(fn ($item) => !empty($item->file_faktur_pajak))?->file_faktur_pajak;
    $hasFakturPajak = !empty($fakturPajakPath);

    $isDokumenLengkap = $hasBapp && $hasBap && $hasInvoice && (!$wajibBast || $hasBast);

    function getFileViewerPath($fileObj, $legacyPath) {
        if ($fileObj && isset($fileObj->path_file)) return \Illuminate\Support\Facades\Storage::url($fileObj->path_file);
        if ($legacyPath) return \Illuminate\Support\Facades\Storage::url($legacyPath);
        return null;
    }
    
    $urlBapp = getFileViewerPath($fileBapp, optional($tagihan->detailKontrak)->file_bapp);
    $urlBast = getFileViewerPath($fileBast, optional($tagihan->detailKontrak)->file_bast);
    $urlBap = getFileViewerPath($fileBap, optional($tagihan->detailKontrak)->file_bap);
    $urlInvoice = getFileViewerPath($fileInvoice, optional($tagihan->detailKontrak)->file_invoice);
    $urlFaktur = $fakturPajakPath ? \Illuminate\Support\Facades\Storage::url($fakturPajakPath) : null;
@endphp

@section('content')
<div class="container-fluid py-4">
    
    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm alert-dismissible bg-success text-white">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error') || $errors->any())
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible bg-danger text-white">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') ?? 'Terdapat kesalahan input' }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- 1. Header Keputusan --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4 pb-3">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h4 class="fw-bold mb-1"><i class="bi bi-shield-check text-primary me-2"></i> Detail Verifikasi Tagihan Termin</h4>
                    <p class="text-muted small mb-0">Cek kelengkapan dokumen dan nilai finansial sebelum meneruskan tagihan ke SPP.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-warning text-dark px-3 py-2 border rounded-pill shadow-sm"><i class="bi bi-hourglass-split me-1"></i> MENUNGGU VERIFIKASI</span>
                    <a href="{{ route('ppk.tagihan.kontrak.index') }}" class="btn btn-outline-secondary rounded-pill fw-bold">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            
            <div class="row g-3 py-3 border-top mt-2">
                <div class="col-md-3">
                    <div class="text-muted small">Nomor Tagihan</div>
                    <div class="fw-bold">{{ $tagihan->nomor_tagihan }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">SPK / Rekanan</div>
                    <div class="fw-bold text-truncate" title="{{ $kontrak?->nama_pekerjaan }}">{{ $kontrak?->nomor_spk ?? '-' }}</div>
                    <div class="small fw-semibold text-primary">{{ $kontrak?->vendor?->nama_pihak ?? '-' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Termin & Progres</div>
                    <div class="fw-bold">Termin {{ $termin?->termin_ke ?? '-' }} ({{ $termin?->jenis_termin }})</div>
                    <div class="small text-muted">Progres: {{ $termin?->persentase ?? 0 }}%</div>
                </div>
                <div class="col-md-3 border-start">
                    <div class="text-muted small">Nilai Netto (Dibayar)</div>
                    <div class="fw-bold fs-5 text-success">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Checklist Verifikasi (Ringkasan Cepat) --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-light">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3 text-secondary text-uppercase small"><i class="bi bi-list-check me-2"></i>Checklist Verifikasi Cepat</h6>
            <div class="row g-4 align-items-center">
                <div class="col-md-8">
                    <div class="d-flex flex-wrap gap-4">
                        <div>
                            <div class="small text-muted mb-1">Berita Acara (BAPP & BAP)</div>
                            @if($hasBapp && $hasBap) <span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="bi bi-check-circle-fill me-1"></i>Tersedia</span>
                            @else <span class="badge bg-danger bg-opacity-10 text-danger border border-danger"><i class="bi bi-x-circle-fill me-1"></i>Belum Lengkap</span> @endif
                        </div>
                        @if($wajibBast)
                        <div>
                            <div class="small text-muted mb-1">Berita Acara Serah Terima (BAST)</div>
                            @if($hasBast) <span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="bi bi-check-circle-fill me-1"></i>Tersedia</span>
                            @else <span class="badge bg-danger bg-opacity-10 text-danger border border-danger"><i class="bi bi-x-circle-fill me-1"></i>Wajib Ada</span> @endif
                        </div>
                        @endif
                        <div>
                            <div class="small text-muted mb-1">Invoice Tagihan</div>
                            @if($hasInvoice) <span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="bi bi-check-circle-fill me-1"></i>Tersedia</span>
                            @else <span class="badge bg-danger bg-opacity-10 text-danger border border-danger"><i class="bi bi-x-circle-fill me-1"></i>Wajib Ada</span> @endif
                        </div>
                        <div>
                            <div class="small text-muted mb-1">Faktur Pajak</div>
                            @if($hasFakturPajak) <span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="bi bi-check-circle-fill me-1"></i>Tersedia</span>
                            @else <span class="badge bg-secondary"><i class="bi bi-dash me-1"></i>Opsional</span> @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-4 border-start text-center">
                    @if($isDokumenLengkap)
                        <div class="fw-bold text-success mb-1"><i class="bi bi-check-circle-fill me-1"></i>Dokumen Lengkap</div>
                        <div class="small text-muted">Tagihan layak diteruskan ke SPP</div>
                    @else
                        <div class="fw-bold text-danger mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Dokumen Tidak Lengkap</div>
                        <div class="small text-muted">Sebaiknya dikembalikan untuk revisi</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Layout Utama: Kiri Workspace, Kanan Ringkasan --}}
    <div class="row g-4">
        
        {{-- KOLOM KIRI: WORKSPACE DOKUMEN --}}
        <div class="col-lg-7 col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white pt-3 px-3 border-bottom-0 pb-0">
                    <ul class="nav nav-tabs border-bottom-0 gap-1" id="docTabs" role="tablist">
                        @if($urlBapp)
                        <li class="nav-item">
                            <button class="nav-link active fw-bold py-2 border-top border-start border-end" id="bapp-tab" data-bs-toggle="tab" data-bs-target="#bapp" type="button" role="tab">
                                <i class="bi bi-file-earmark-pdf text-danger me-1"></i> BAPP Final
                            </button>
                        </li>
                        @endif
                        @if($urlBap)
                        <li class="nav-item">
                            <button class="nav-link fw-bold py-2 border-top border-start border-end {{ !$urlBapp ? 'active' : '' }}" id="bap-tab" data-bs-toggle="tab" data-bs-target="#bap" type="button" role="tab">
                                <i class="bi bi-file-earmark-pdf text-danger me-1"></i> BAP Final
                            </button>
                        </li>
                        @endif
                        @if($urlBast)
                        <li class="nav-item">
                            <button class="nav-link fw-bold py-2 border-top border-start border-end {{ (!$urlBapp && !$urlBap) ? 'active' : '' }}" id="bast-tab" data-bs-toggle="tab" data-bs-target="#bast" type="button" role="tab">
                                <i class="bi bi-file-earmark-pdf text-danger me-1"></i> BAST Final
                            </button>
                        </li>
                        @endif
                        @if($urlInvoice)
                        <li class="nav-item ms-auto">
                            <button class="nav-link fw-bold py-2 border-top border-start border-end text-success {{ (!$urlBapp && !$urlBap && !$urlBast) ? 'active' : '' }}" id="invoice-tab" data-bs-toggle="tab" data-bs-target="#invoice" type="button" role="tab">
                                <i class="bi bi-receipt me-1"></i> Invoice
                            </button>
                        </li>
                        @endif
                        @if($urlFaktur)
                        <li class="nav-item">
                            <button class="nav-link fw-bold py-2 border-top border-start border-end text-success" id="pajak-tab" data-bs-toggle="tab" data-bs-target="#pajak" type="button" role="tab">
                                <i class="bi bi-receipt me-1"></i> Faktur Pajak
                            </button>
                        </li>
                        @endif
                    </ul>
                </div>
                
                <div class="card-body p-0 border-top">
                    @if(!$urlBapp && !$urlBap && !$urlBast && !$urlInvoice)
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-file-earmark-x fs-1 opacity-50 mb-3 d-block"></i>
                            <h6 class="fw-bold">Tidak ada dokumen yang diunggah.</h6>
                        </div>
                    @else
                        <div class="tab-content h-100" id="docTabsContent" style="min-height: 70vh;">
                            @if($urlBapp)
                            <div class="tab-pane fade show active h-100" id="bapp" role="tabpanel" style="height: 100%;">
                                <div class="bg-light px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                                    <div class="small fw-bold text-dark"><i class="bi bi-file-pdf"></i> Dokumen BAPP TTD Final - {{ $tagihan->detailKontrak->nomor_bapp ?? '-' }}</div>
                                    <a href="{{ $urlBapp }}" target="_blank" class="btn btn-sm btn-outline-secondary py-1"><i class="bi bi-box-arrow-up-right me-1"></i> Buka Penuh</a>
                                </div>
                                <iframe src="{{ $urlBapp }}#toolbar=0" class="w-100 h-100" style="min-height: 70vh; border: none;"></iframe>
                            </div>
                            @endif
                            
                            @if($urlBap)
                            <div class="tab-pane fade h-100 {{ !$urlBapp ? 'show active' : '' }}" id="bap" role="tabpanel" style="height: 100%;">
                                <div class="bg-light px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                                    <div class="small fw-bold text-dark"><i class="bi bi-file-pdf"></i> Dokumen BAP TTD Final - {{ $tagihan->detailKontrak->nomor_bap ?? '-' }}</div>
                                    <a href="{{ $urlBap }}" target="_blank" class="btn btn-sm btn-outline-secondary py-1"><i class="bi bi-box-arrow-up-right me-1"></i> Buka Penuh</a>
                                </div>
                                <iframe src="{{ $urlBap }}#toolbar=0" class="w-100 h-100" style="min-height: 70vh; border: none;"></iframe>
                            </div>
                            @endif

                            @if($urlBast)
                            <div class="tab-pane fade h-100 {{ (!$urlBapp && !$urlBap) ? 'show active' : '' }}" id="bast" role="tabpanel" style="height: 100%;">
                                <div class="bg-light px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                                    <div class="small fw-bold text-dark"><i class="bi bi-file-pdf"></i> Dokumen BAST TTD Final - {{ $tagihan->detailKontrak->nomor_bast ?? '-' }}</div>
                                    <a href="{{ $urlBast }}" target="_blank" class="btn btn-sm btn-outline-secondary py-1"><i class="bi bi-box-arrow-up-right me-1"></i> Buka Penuh</a>
                                </div>
                                <iframe src="{{ $urlBast }}#toolbar=0" class="w-100 h-100" style="min-height: 70vh; border: none;"></iframe>
                            </div>
                            @endif

                            @if($urlInvoice)
                            <div class="tab-pane fade h-100 {{ (!$urlBapp && !$urlBap && !$urlBast) ? 'show active' : '' }}" id="invoice" role="tabpanel" style="height: 100%;">
                                <div class="bg-light px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                                    <div class="small fw-bold text-dark"><i class="bi bi-receipt"></i> Salinan Invoice/Kwitansi - {{ $tagihan->detailKontrak->nomor_invoice ?? '-' }}</div>
                                    <a href="{{ $urlInvoice }}" target="_blank" class="btn btn-sm btn-outline-secondary py-1"><i class="bi bi-box-arrow-up-right me-1"></i> Buka Penuh</a>
                                </div>
                                <iframe src="{{ $urlInvoice }}#toolbar=0" class="w-100 h-100" style="min-height: 70vh; border: none;"></iframe>
                            </div>
                            @endif

                            @if($urlFaktur)
                            <div class="tab-pane fade h-100" id="pajak" role="tabpanel" style="height: 100%;">
                                <div class="bg-light px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                                    <div class="small fw-bold text-dark"><i class="bi bi-receipt"></i> File Faktur Pajak Pendukung</div>
                                    <a href="{{ $urlFaktur }}" target="_blank" class="btn btn-sm btn-outline-secondary py-1"><i class="bi bi-box-arrow-up-right me-1"></i> Buka Penuh</a>
                                </div>
                                <iframe src="{{ $urlFaktur }}#toolbar=0" class="w-100 h-100" style="min-height: 70vh; border: none;"></iframe>
                            </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: PANEL VERIFIKASI PPK --}}
        <div class="col-lg-5 col-xl-4 d-flex flex-column gap-4">
            
            {{-- Card A: Ringkasan Dasar --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white pt-4 px-4 pb-0 border-0">
                    <h6 class="fw-bold text-primary mb-0"><i class="bi bi-card-heading me-2"></i> Identitas Pekerjaan</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <div class="small text-muted mb-1">Nama Pekerjaan</div>
                        <div class="fw-bold fs-6">{{ $kontrak?->nama_pekerjaan ?? '-' }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="small text-muted mb-1">Catatan Tagihan / Deskripsi</div>
                        <div class="small fw-semibold">{{ $tagihan->deskripsi ?? '-' }}</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="small text-muted mb-1">No Invoice</div>
                            <div class="fw-bold small">{{ $tagihan->detailKontrak->nomor_invoice ?? '-' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted mb-1">Tgl Invoice</div>
                            <div class="fw-bold small">{{ optional($tagihan->detailKontrak->tanggal_invoice)->format('d M Y') ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card B: Ringkasan Finansial --}}
            <div class="card border-0 shadow-sm rounded-4 border-top border-4 border-success">
                <div class="card-header bg-white pt-4 px-4 pb-0 border-0">
                    <h6 class="fw-bold text-success mb-0"><i class="bi bi-cash-stack me-2"></i> Rincian Finansial</h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Nilai Bruto (Kotor) Termin</span>
                        <span class="fw-bold">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</span>
                    </div>
                    @if($potonganAngsuranUm)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Potongan Uang Muka</span>
                        <span class="fw-bold text-warning">- Rp {{ number_format($potonganAngsuranUm->nominal_potongan, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <span class="text-muted small">Total Pajak Dipotong</span>
                        <span class="fw-bold text-danger">- Rp {{ number_format($totalPotonganPajak, 0, ',', '.') }}</span>
                    </div>
                    
                    <div class="bg-success bg-opacity-10 rounded-3 p-3 text-center border border-success border-opacity-25 mt-2">
                        <div class="small text-success fw-bold text-uppercase mb-1">Nilai Netto Riil Dibayar</div>
                        <div class="fs-3 fw-black text-success mb-0 lh-1">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            {{-- Card D: Panel Keputusan PPK --}}
            <div class="card border-0 shadow-sm rounded-4 bg-dark text-white sticky-top" style="top: 2rem;">
                <div class="card-body p-4 p-xl-5">
                    <h5 class="fw-bold mb-2 text-center"><i class="bi bi-shield-lock me-2"></i> Keputusan Final</h5>
                    <p class="text-white-50 small text-center mb-4">Pastikan Anda telah melakukan reviu mendalam terhadap Berita Acara dan total pembayaran netto.</p>
                    
                    <div class="d-grid gap-3">
                        <form action="{{ route('ppk.tagihan.kontrak.approve', $tagihan->id) }}" method="POST" id="formApprove" class="m-0">
                            @csrf
                            <button type="button" class="btn btn-success py-3 fw-bold w-100 rounded-pill shadow" onclick="confirmApproval()" {{ !$isDokumenLengkap ? 'disabled' : '' }}>
                                <i class="bi bi-check2-all me-1 fs-5 align-middle"></i> Setujui & Teruskan SPP
                            </button>
                        </form>
                        
                        <button type="button" class="btn btn-outline-light py-2 fw-bold w-100 rounded-pill" data-bs-toggle="modal" data-bs-target="#modalRevisi">
                            <i class="bi bi-arrow-return-left me-1"></i> Kembalikan untuk Revisi
                        </button>
                    </div>
                    @if(!$isDokumenLengkap)
                        <div class="text-center mt-3 small text-warning"><i class="bi bi-info-circle me-1"></i> Tombol setuju dikunci karena dokumen tidak lengkap.</div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

{{-- MODAL: Kembalikan (Revisi) --}}
<div class="modal fade" id="modalRevisi" tabindex="-1" aria-labelledby="modalRevisiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('ppk.tagihan.kontrak.reject', $tagihan->id) }}" method="POST" class="modal-content border-0 rounded-4 shadow">
            @csrf
            <div class="modal-header bg-danger text-white border-bottom-0">
                <h5 class="modal-title fw-bold" id="modalRevisiLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i> Kembalikan untuk Revisi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-3 text-muted">Silakan tuliskan alasan penolakan/revisi untuk Pejabat Pengadaan yang memproses tagihan ini.</p>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-danger">Catatan Revisi Penting <span class="text-danger">*</span></label>
                    <textarea class="form-control bg-light" name="catatan_revisi" rows="4" placeholder="Cth: File BAST tidak ditandatangani dengan benar, ada salah hitung pajak..." required></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-send-x me-1"></i> Kirim Revisi</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Styling tabs on active
    document.querySelectorAll('#docTabs .nav-link').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('#docTabs .nav-link').forEach(t => {
                t.classList.remove('bg-light');
            });
            this.classList.add('bg-light');
        });
    });

    function confirmApproval() {
        Swal.fire({
            title: 'Verifikasi Final?',
            text: "Anda akan menyetujui seluruh keabsahan tagihan dan volume pekerjaan ini. Tagihan ini akan dilimpahkan keproses pencairan SPP.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-check-circle me-1"></i> Ya, Verifikasi',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            customClass: {
                confirmButton: 'btn btn-success px-4 py-2 fw-bold',
                cancelButton: 'btn btn-outline-secondary px-4 py-2 fw-bold',
                actions: 'gap-3'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formApprove').submit();
            }
        });
    }
</script>
@endpush
