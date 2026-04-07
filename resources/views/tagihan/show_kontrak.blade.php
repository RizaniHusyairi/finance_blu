@extends('layouts.app')
@section('title', 'Detail Tagihan Termin')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Detail Tagihan Termin</h4>
            <p class="text-muted mb-0">Pusat persetujuan kelengkapan Berita Acara dan Tagihan (Status: <span class="badge bg-warning text-dark">{{ $tagihan->status }}</span>)</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm alert-dismissible bg-success text-white">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible bg-danger text-white">
            <i class="bi bi-exclamation-octagon me-2"></i> Terdapat kesalahan:
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- Area Kiri: Informasi Tagihan & Status --}}
        <div class="col-lg-8">
            {{-- Ringkasan Tagihan --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom pt-4 px-4 pb-3">
                    <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-receipt me-2"></i>Ringkasan Tagihan & Finansial</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Nomor Tagihan</div>
                            <div class="fw-bold">{{ $tagihan->nomor_tagihan }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Nomor SPK / Kontrak</div>
                            <div class="fw-bold">{{ $kontrak->nomor_spk ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Nama Pekerjaan</div>
                            <div class="fw-bold">{{ $kontrak->nama_pekerjaan ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Vendor</div>
                            <div class="fw-bold">{{ $kontrak->vendor->nama_pihak ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Termin</div>
                            <div class="fw-bold">Termin {{ $termin->termin_ke ?? '-' }} ({{ $termin->jenis_termin }})</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Data Invoice</div>
                            <div class="fw-bold">{{ $detailKontrak->nomor_invoice ?? '-' }}</div>
                            <div class="small text-muted">{{ optional($detailKontrak->tanggal_invoice)->format('d M Y') ?? '-' }}</div>
                        </div>
                    </div>
                    
                    <div class="p-3 bg-light rounded border">
                        <div class="row g-3 text-center">
                            <div class="col-md-4">
                                <div class="text-muted small mb-1">Total Bruto</div>
                                <div class="fw-bold fs-5">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-md-4 border-start border-end">
                                <div class="text-muted small mb-1">Total Potongan</div>
                                <div class="fw-bold text-danger fs-5">Rp {{ number_format($tagihan->total_potongan, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small mb-1">Total Netto</div>
                                <div class="fw-bold text-success fs-5">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Ringkasan Legalitas --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom pt-4 px-4 pb-3">
                    <h5 class="fw-bold mb-0 text-info"><i class="bi bi-file-earmark-check me-2"></i>Legalitas Pekerjaan</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small mb-1">Nomor BAPP</div>
                                <div class="fw-bold">{{ $detailKontrak->nomor_bapp ?? '-' }}</div>
                                <div class="small text-muted">Tgl: {{ optional($detailKontrak->tanggal_bapp)->format('d M Y') ?? '-' }}</div>
                            </div>
                        </div>
                        @if($wajibBast)
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small mb-1">Nomor BAST</div>
                                <div class="fw-bold">{{ $detailKontrak->nomor_bast ?? '-' }}</div>
                                <div class="small text-muted">Tgl: {{ optional($detailKontrak->tanggal_bast)->format('d M Y') ?? '-' }}</div>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small mb-1">Nomor BAP</div>
                                <div class="fw-bold">{{ $detailKontrak->nomor_bap ?? '-' }}</div>
                                <div class="small text-muted">Tgl: {{ optional($detailKontrak->tanggal_bap)->format('d M Y') ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-12 mt-4 pt-3 border-top">
                            <h6 class="fw-bold text-secondary mb-3">Data Pemeriksa Hasil Pekerjaan (BAPP)</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="text-muted small mb-1">Nama / NIP</div>
                                    <div class="fw-bold">{{ $detailKontrak->nama_pemeriksa ?? '-' }}</div>
                                    <div class="small text-muted">{{ $detailKontrak->nip_pemeriksa ?? '-' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small mb-1">Jabatan</div>
                                    <div class="fw-bold">{{ $detailKontrak->jabatan_pemeriksa ?? '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Dokumen Berita Acara Final --}}
            <h5 class="fw-bold mb-3 mt-5">Manajemen Dokumen Berita Acara</h5>
            <div class="row g-4">
                {{-- BAPP --}}
                <div class="col-md-6">
                    <div class="card border border-primary shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">Dokumen BAPP</h6>
                                @if($hasBappFinal)
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Final</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Belum Lengkap</span>
                                @endif
                            </div>
                            <p class="small text-muted mb-4">Cetak draft PDF BAPP, lakukan penandatanganan, scan, lalu unggah kembali versi finalnya.</p>
                            
                            <div class="d-grid gap-2">
                                @if($tagihan->status === 'DRAFT')
                                    <a href="{{ route('tagihan.kontrak.export-pdf', [$tagihan->id, 'BAPP']) }}" target="_blank" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-file-pdf me-1"></i> Cetak BAPP (PDF)
                                    </a>
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalBapp">
                                        <i class="bi bi-upload me-1"></i> Unggah BAPP Final
                                    </button>
                                @endif
                                @if($hasBappFinal)
                                    @php $file = $detailKontrak->arsipDokumen->firstWhere('jenis_dokumen', 'BAPP_FINAL_TTD'); @endphp
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($file->path_file) }}" target="_blank" class="btn btn-sm btn-success text-white">Lihat Final BAPP</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- BAST --}}
                <div class="col-md-6">
                    <div class="card border border-secondary shadow-sm h-100 {{ !$wajibBast ? 'bg-light' : '' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">Dokumen BAST</h6>
                                @if(!$wajibBast)
                                    <span class="badge bg-secondary">Tidak Wajib Di Termin Ini</span>
                                @elseif($hasBastFinal)
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Final</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Belum Lengkap</span>
                                @endif
                            </div>
                            <p class="small text-muted mb-4">Dokumen Berita Acara Serah Terima (Jika diperlukan untuk termin berjalan).</p>
                            
                            @if($wajibBast)
                            <div class="d-grid gap-2">
                                @if($tagihan->status === 'DRAFT')
                                    <a href="{{ route('tagihan.kontrak.export-pdf', [$tagihan->id, 'BAST']) }}" target="_blank" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-file-pdf me-1"></i> Cetak BAST (PDF)
                                    </a>
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalBast">
                                        <i class="bi bi-upload me-1"></i> Unggah BAST Final
                                    </button>
                                @endif
                                @if($hasBastFinal)
                                    @php $file = $detailKontrak->arsipDokumen->firstWhere('jenis_dokumen', 'BAST_FINAL_TTD'); @endphp
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($file->path_file) }}" target="_blank" class="btn btn-sm btn-success text-white">Lihat Final BAST</a>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- BAP --}}
                <div class="col-md-6">
                    <div class="card border border-primary shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">Dokumen BAP</h6>
                                @if($hasBapFinal)
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Final</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Belum Lengkap</span>
                                @endif
                            </div>
                            <p class="small text-muted mb-4">Cetak draft PDF BAP, lakukan penandatanganan, scan, lalu unggah kembali versi finalnya.</p>
                            
                            <div class="d-grid gap-2">
                                @if($tagihan->status === 'DRAFT')
                                    <a href="{{ route('tagihan.kontrak.export-pdf', [$tagihan->id, 'BAP']) }}" target="_blank" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-file-pdf me-1"></i> Cetak BAP (PDF)
                                    </a>
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalBap">
                                        <i class="bi bi-upload me-1"></i> Unggah BAP Final
                                    </button>
                                @endif
                                @if($hasBapFinal)
                                    @php $file = $detailKontrak->arsipDokumen->firstWhere('jenis_dokumen', 'BAP_FINAL_TTD'); @endphp
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($file->path_file) }}" target="_blank" class="btn btn-sm btn-success text-white">Lihat Final BAP</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Area Kanan: Status Kelengkapan --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 2rem;">
                <div class="card-body p-4">
                    @if($tagihan->status === 'DRAFT')
                        <h5 class="fw-bold mb-3">Syarat Pengajuan (PPK)</h5>
                        <p class="text-muted small">Lengkapi seluruh dokumen Berita Acara bertandatangan untuk bisa mengajukan tagihan.</p>
                        
                        <ul class="list-group mb-4">
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                <span><i class="bi bi-circle-fill me-2 small {{ $hasBappFinal ? 'text-success' : 'text-secondary' }}"></i>BAPP Final</span>
                                @if($hasBappFinal) <i class="bi bi-check fs-5 text-success"></i> @endif
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                <span><i class="bi bi-circle-fill me-2 small {{ $hasBapFinal ? 'text-success' : 'text-secondary' }}"></i>BAP Final</span>
                                @if($hasBapFinal) <i class="bi bi-check fs-5 text-success"></i> @endif
                            </li>
                            @if($wajibBast)
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                <span><i class="bi bi-circle-fill me-2 small {{ $hasBastFinal ? 'text-success' : 'text-secondary' }}"></i>BAST Final</span>
                                @if($hasBastFinal) <i class="bi bi-check fs-5 text-success"></i> @endif
                            </li>
                            @endif
                        </ul>

                        @if($isReadyToSubmit)
                            <div class="alert alert-success border-0 small mb-4 py-2">
                                <i class="bi bi-check-circle me-1"></i> Tagihan siap diajukan ke PPK.
                            </div>
                            <form action="{{ route('tagihan.kontrak.submit', $tagihan->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm">
                                    <i class="bi bi-send me-1"></i> Ajukan Tagihan ke PPK
                                </button>
                            </form>
                        @else
                            <div class="alert alert-warning border-0 small mb-4 py-2">
                                <i class="bi bi-exclamation-triangle me-1"></i> Tagihan belum siap diajukan. Mohon lengkapi finalisasi file Berita Acara yang kurang.
                            </div>
                            <button type="button" class="btn btn-secondary w-100 fw-bold py-2" disabled>
                                <i class="bi bi-send me-1"></i> Ajukan Tagihan ke PPK
                            </button>
                        @endif
                    @else
                        <h5 class="fw-bold mb-3">Informasi Status Tagihan</h5>
                        <div class="text-center py-3 mb-3 border-bottom">
                            @if($tagihan->status === 'PENDING_PPK')
                                <i class="bi bi-hourglass-split text-warning" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3 text-warning">Menunggu Verifikasi PPK</h6>
                                <p class="text-muted small">Tagihan Anda sedang antre untuk ditinjau dan divalidasi oleh Pejabat Pembuat Komitmen.</p>
                            @elseif($tagihan->status === 'REVISI_PEJABAT_PENGADAAN')
                                <i class="bi bi-x-circle text-danger" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3 text-danger">Revisi Diperlukan</h6>
                                <p class="text-muted small">Tagihan ditolak/dikembalikan oleh PPK karena ada hal yang perlu diperbaiki.</p>
                            @else
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3 text-success">Tagihan Disetujui</h6>
                                <p class="text-muted small">Dokumen verifikasi sudah diterima. Proses berikutnya dijalankan untuk pembuatan SPP.</p>
                            @endif
                        </div>
                        
                        <h6 class="fw-bold text-secondary mb-3 fs-6">Kelengkapan Terlampir:</h6>
                        <ul class="list-group mb-0">
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                <span><i class="bi bi-check-circle-fill me-2 small text-success"></i>BAPP Final</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                <span><i class="bi bi-check-circle-fill me-2 small text-success"></i>BAP Final</span>
                            </li>
                            @if($wajibBast)
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                <span><i class="bi bi-check-circle-fill me-2 small text-success"></i>BAST Final</span>
                            </li>
                            @endif
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modals for Uploads --}}
@include('tagihan.partials.modal_upload_arsip', ['id' => 'modalBapp', 'title' => 'Unggah BAPP Final', 'jenis' => 'BAPP_FINAL_TTD', 'mimes' => '.pdf'])
@if($wajibBast)
    @include('tagihan.partials.modal_upload_arsip', ['id' => 'modalBast', 'title' => 'Unggah BAST Final', 'jenis' => 'BAST_FINAL_TTD', 'mimes' => '.pdf'])
@endif
@include('tagihan.partials.modal_upload_arsip', ['id' => 'modalBap', 'title' => 'Unggah BAP Final', 'jenis' => 'BAP_FINAL_TTD', 'mimes' => '.pdf'])

@endsection
