@extends('layouts.app')

@section('content')
<!-- Header Workspace -->
<div class="card radius-10 border-top border-0 border-4 border-primary shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between mb-3">
            <div class="mb-3 mb-md-0">
                <h4 class="mb-1 fw-bold text-primary"><i class='bx bx-edit-alt me-2'></i>Penyusunan NPI Kontrak</h4>
                <div class="d-flex align-items-center gap-2 mt-2">
                    <span class="badge @if($statusNpi == \App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL || $statusNpi == \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG) bg-success @elseif($statusNpi == \App\Models\DokumenNpi::STATUS_REVISI) bg-danger @elseif($statusNpi == \App\Models\DokumenNpi::STATUS_DRAFT) bg-secondary @else bg-info @endif fs-6">
                        {{ $statusNpi }}
                    </span>
                    <span class="text-secondary fw-bold fs-6 border-start ps-2">{{ $npiModel->nomor_npi ?? 'DRAFT (Belum Tersimpan)' }}</span>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap justify-content-end">
                <a href="{{ route('npis.kontrak.index') }}" class="btn btn-outline-secondary px-3"><i class='bx bx-arrow-back'></i> Kembali</a>
                @if($npiModel && !in_array($npiModel->status, [\App\Models\DokumenNpi::STATUS_DRAFT, \App\Models\DokumenNpi::STATUS_REVISI, '']))
                    <a href="{{ route('npis.cetak-pdf', $npiModel->id) }}" target="_blank" class="btn btn-danger px-3"><i class='bx bxs-file-pdf'></i> Cetak PDF</a>
                @endif
                @if($statusNpi === 'DISETUJUI_FINAL')
                    <a href="{{ route('sp2ds.kontrak.detail', $npiModel->id) }}" class="btn btn-success px-3"><i class='bx bx-receipt'></i> Buat SP2D</a>
                @endif
            </div>
        </div>
        
        <div class="bg-light p-3 rounded-3 mt-3">
            <div class="row g-3">
                <div class="col-6 col-md-3 border-end">
                    <p class="mb-1 text-muted font-12 text-uppercase fw-semibold">Nomor SPM</p>
                    <h6 class="mb-0 fw-bold text-dark text-break">{{ $spmModel->nomor_spm }}</h6>
                </div>
                <div class="col-6 col-md-3 border-end">
                    <p class="mb-1 text-muted font-12 text-uppercase fw-semibold">Vendor / Pihak Ketiga</p>
                    <h6 class="mb-0 fw-bold text-dark text-break">{{ $vendor?->nama_pihak ?? '-' }}</h6>
                </div>
                <div class="col-6 col-md-3 border-end">
                    <p class="mb-1 text-muted font-12 text-uppercase fw-semibold">Potongan Tagihan</p>
                    <h6 class="mb-0 fw-bold text-danger">Rp {{ number_format(($tagihan?->total_kotor ?? 0) - ($tagihan?->total_netto ?? 0), 0, ',', '.') }}</h6>
                </div>
                <div class="col-6 col-md-3">
                    <p class="mb-1 text-muted font-12 text-uppercase fw-semibold">Nilai NPI (Netto)</p>
                    <h5 class="mb-0 fw-bold text-primary">Rp {{ number_format($nominalNpi, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- KOLOM KIRI -->
    <div class="col-12 col-xl-4">
        
        <!-- Dokumen Dasar Kontrak -->
        <div class="card radius-10 mb-4 shadow-sm">
            <div class="card-header bg-transparent border-bottom-0 pt-4 pb-2">
                <h6 class="mb-0 fw-bold text-uppercase"><i class='bx bx-file text-primary me-2'></i>Dokumen Dasar Kontrak</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted form-label mb-0 font-12">Nama Pekerjaan</label>
                    <div class="fw-bold text-dark">{{ $kontrak?->nama_pekerjaan ?? '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="text-muted form-label mb-0 font-12">Nomor SPK</label>
                        <div class="fw-bold text-dark text-break">{{ $kontrak?->nomor_spk ?? '-' }}</div>
                    </div>
                    <div class="col-6">
                        <label class="text-muted form-label mb-0 font-12">Termin</label>
                        <div class="fw-bold text-dark"><span class="badge bg-secondary me-1">{{ $termin?->termin ?? '-' }}</span> {{ $termin?->jenis_termin ?? '-' }}</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="text-muted form-label mb-0 font-12">Mata Anggaran (COA)</label>
                    <div class="fw-bold text-dark">
                        @if($kontrak?->dipaRevisionItem?->coa)
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle me-1">{{ $kontrak->dipaRevisionItem->coa->kode_mak_lengkap }}</span> 
                            {{ $kontrak->dipaRevisionItem->coa->nama_akun }}
                        @else
                            <span class="text-muted fst-italic">-</span>
                        @endif
                    </div>
                </div>
                <div class="bg-light rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                        <div>
                            <span class="text-muted d-block font-12">Nomor BAPP</span>
                            <span class="fw-bold text-dark text-break">{{ $detailKontrak?->nomor_bapp ?? '-' }}</span>
                        </div>
                        <div class="text-end">
                            <span class="text-muted d-block font-12">Tanggal</span>
                            <span class="fw-bold text-dark">{{ $detailKontrak?->tanggal_bapp?->format('d M Y') ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                        <div>
                            <span class="text-muted d-block font-12">Nomor BAP</span>
                            <span class="fw-bold text-dark text-break">{{ $detailKontrak?->nomor_bap ?? '-' }}</span>
                        </div>
                        <div class="text-end">
                            <span class="text-muted d-block font-12">Tanggal</span>
                            <span class="fw-bold text-dark">{{ $detailKontrak?->tanggal_bap?->format('d M Y') ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted d-block font-12">Nomor BAST</span>
                            <span class="fw-bold text-dark text-break">{{ $detailKontrak?->nomor_bast ?? '-' }}</span>
                        </div>
                        <div class="text-end">
                            <span class="text-muted d-block font-12">Tanggal</span>
                            <span class="fw-bold text-dark">{{ $detailKontrak?->tanggal_bast?->format('d M Y') ?? '-' }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between border-top pt-2 mt-2">
                    <span class="text-muted font-12">Nomor SPP</span>
                    <span class="fw-bold text-end text-break">{{ $sppModel->nomor_spp }}</span>
                </div>
            </div>
        </div>

        <!-- Penerima Pembayaran -->
        <div class="card radius-10 mb-4 shadow-sm">
            <div class="card-header bg-transparent border-bottom-0 pt-4 pb-2">
                <h6 class="mb-0 fw-bold text-uppercase"><i class='bx bx-building-house text-primary me-2'></i>Penerima Pembayaran</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted form-label mb-0 font-12">Nama Vendor</label>
                    <div class="fw-bold text-dark">{{ $vendor?->nama_pihak ?? 'Vendor Tidak Ditemukan' }}</div>
                </div>
                <div class="mb-3">
                    <label class="text-muted form-label mb-0 font-12">NPWP</label>
                    <div class="fw-bold text-dark">{{ $vendor?->npwp ?? '-' }}</div>
                </div>
                
                <div class="bg-light p-3 rounded border mb-3">
                    <div class="row">
                        <div class="col-12 mb-2">
                            <small class="text-muted d-block">Bank</small>
                            <strong class="text-dark">{{ $rekening?->nama_bank ?? 'Belum ada' }}</strong>
                        </div>
                        <div class="col-12 mb-2">
                            <small class="text-muted d-block">Nomor Rekening</small>
                            <strong class="text-dark fs-6">{{ $rekening?->nomor_rekening ?? 'Belum ada' }}</strong>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">Atas Nama Rekening</small>
                            <strong class="text-dark">{{ $rekening?->nama_rekening ?? '-' }}</strong>
                        </div>
                    </div>
                </div>

                @if(!$rekeningReady)
                    <div class="alert alert-danger border-0 d-flex align-items-center mb-0 p-3">
                        <i class='bx bx-error-circle fs-3 me-3'></i>
                        <span class="font-13">Data rekening vendor belum lengkap di Master Data. Ini akan <strong>memblokir pengajuan NPI</strong>.</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Kelengkapan Dokumen -->
        <div class="card radius-10 mb-4 shadow-sm">
            <div class="card-header bg-transparent border-bottom-0 pt-4 pb-2">
                <h6 class="mb-0 fw-bold text-uppercase"><i class='bx bx-folder-open text-primary me-2'></i>Kelengkapan Dokumen</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($documentStatuses as $doc)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div class="d-flex align-items-center">
                            <i class='bx {{ $doc["required"] ? "bx-file text-primary" : "bx-file-blank text-muted" }} fs-5 me-3'></i>
                            <div>
                                <h6 class="mb-0 font-14">{{ $doc['label'] }}</h6>
                                @if(!$doc['required']) <small class="text-muted">Opsional</small> @endif
                            </div>
                        </div>
                        <div>
                            @if($doc['status'] == 'ready')
                                <span class="badge bg-success"><i class='bx bx-check'></i> Ada</span>
                                @if(is_string($doc['path']))
                                    <a href="{{ filter_var($doc['path'], FILTER_VALIDATE_URL) ? $doc['path'] : \Illuminate\Support\Facades\Storage::url($doc['path']) }}" target="_blank" class="btn btn-sm btn-outline-primary ms-1 px-2 py-0"><i class='bx bx-search m-0'></i></a>
                                @endif
                            @elseif($doc['status'] == 'missing')
                                <span class="badge bg-danger"><i class='bx bx-x'></i> Belum Ada</span>
                            @else
                                <span class="badge bg-light text-dark border">Tidak Butuh</span>
                            @endif
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <!-- KOLOM KANAN -->
    <div class="col-12 col-xl-8">
        
        <!-- Entri & Parameter NPI -->
        <div class="card radius-10 mb-4 shadow-sm">
            <div class="card-header bg-transparent border-bottom-0 pt-4 pb-2">
                <h6 class="mb-0 fw-bold text-uppercase"><i class='bx bx-edit text-primary me-2'></i>Entri & Parameter NPI</h6>
            </div>
            <div class="card-body">
                @if($canEditNpi)
                    <form action="{{ route('npis.kontrak.store', $spmModel->id) }}" method="POST" id="form-draft-npi">
                        @csrf
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label font-13 fw-bold text-secondary mb-1">Nomor NPI <span class="text-danger">*</span></label>
                                <input type="text" name="nomor_npi" class="form-control form-control-lg fs-6 fw-bold text-primary bg-light" value="{{ old('nomor_npi', $npiModel?->nomor_npi ?? $autoNomorNpi) }}" required>
                                <small class="text-muted mt-1 d-block"><i class="bx bx-info-circle me-1"></i>Diturunkan otomatis dari SPP.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-13 fw-bold text-secondary mb-1">Tanggal NPI <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_npi" class="form-control form-control-lg fs-6" value="{{ old('tanggal_npi', $npiModel?->tanggal_npi?->format('Y-m-d') ?? date('Y-m-d')) }}" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label font-13 fw-bold text-secondary mb-1">Bendahara Penerimaan <span class="text-danger">*</span></label>
                                <input type="hidden" name="bendahara_penerimaan_id" value="{{ $bendaharaPenerimaanTagihan?->id }}">
                                <input type="text" class="form-control bg-light" value="{{ $bendaharaPenerimaanTagihan?->name ?? $tagihan?->bendahara_penerimaan_nama_snapshot ?? 'Belum ditentukan' }}" readonly>
                                @if(!$bendaharaPenerimaanTagihan)
                                    <div class="text-danger small mt-1"><i class='bx bx-error-circle'></i> Verifikator belum ada pada tagihan sumber.</div>
                                @else
                                    <small class="text-muted mt-1 d-block">Diwariskan dari tagihan.</small>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-13 fw-bold text-secondary mb-1">Verifikator PPK</label>
                                <input type="text" class="form-control bg-light" value="{{ $ppkSpp?->name ?? 'Belum Ditentukan' }}" readonly>
                                <small class="text-muted mt-1 d-block">Diwariskan dari SPP.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-13 fw-bold text-secondary mb-1">Koordinator Keuangan</label>
                                <input type="text" class="form-control bg-light" value="{{ $koordinatorKeuanganUser?->name ?? $tagihan?->koordinator_keuangan_nama_snapshot ?? 'Belum Ditentukan' }}" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-13 fw-bold text-secondary mb-1">Verifikator Kasubbag</label>
                                <input type="text" class="form-control bg-light" value="{{ $kasubbagUser?->name ?? 'Belum Ditentukan' }}" readonly>
                            </div>

                            <div class="col-12">
                                <label class="form-label font-13 fw-bold text-secondary mb-1">Uraian / Catatan <small class="text-muted fw-normal">(Opsional)</small></label>
                                <textarea name="uraian_npi" class="form-control" rows="3" placeholder="Tambahkan catatan khusus jika diperlukan...">{{ old('uraian_npi', $npiModel?->catatan) }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="submit" class="btn btn-primary px-4 py-2"><i class='bx bx-save me-2'></i>Simpan Draft NPI</button>
                        </div>
                    </form>
                @else
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label font-12 fw-bold text-muted text-uppercase mb-1">Nomor NPI</label>
                            <div class="fw-bold text-dark fs-6">{{ $npiModel->nomor_npi }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-12 fw-bold text-muted text-uppercase mb-1">Tanggal NPI</label>
                            <div class="fw-bold text-dark fs-6">{{ $npiModel->tanggal_npi?->format('d M Y') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-12 fw-bold text-muted text-uppercase mb-1">Bendahara Penerimaan</label>
                            <div class="fw-bold text-dark">{{ $npiModel->bendaharaPenerimaan?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-12 fw-bold text-muted text-uppercase mb-1">Verifikator PPK</label>
                            <div class="fw-bold text-dark">{{ $ppkSpp?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-12 fw-bold text-muted text-uppercase mb-1">Koordinator Keuangan</label>
                            <div class="fw-bold text-dark">{{ $koordinatorKeuanganUser?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-12 fw-bold text-muted text-uppercase mb-1">Verifikator Kasubbag</label>
                            <div class="fw-bold text-dark">{{ $kasubbagUser?->name ?? '-' }}</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label font-12 fw-bold text-muted text-uppercase mb-1">Uraian / Catatan</label>
                            <div class="text-dark bg-light p-3 rounded border">{{ $npiModel->catatan ?: 'Tidak ada catatan.' }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Kesiapan Pengajuan -->
        <div class="card radius-10 mb-4 shadow-sm border-0 border-start border-4 border-info">
            <div class="card-header bg-transparent pt-4 pb-2">
                <h6 class="mb-0 fw-bold text-uppercase"><i class='bx bx-check-shield text-info me-2'></i>Kesiapan Pengajuan</h6>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    @foreach($readinessChecklist as $check)
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 border rounded h-100 {{ $check['status'] === 'ready' ? 'bg-light-success border-success' : 'bg-light-danger border-danger' }}">
                                @if($check['status'] === 'ready')
                                    <i class='bx bxs-check-circle text-success fs-3 me-3'></i>
                                @else
                                    <i class='bx bxs-x-circle text-danger fs-3 me-3'></i>
                                @endif
                                <div>
                                    <h6 class="mb-1 font-14 fw-bold">{{ $check['label'] }}</h6>
                                    <small class="text-muted">{{ $check['hint'] }}</small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($canSubmit)
                    <div class="border-top pt-4 text-end">
                        <form action="{{ route('npis.kontrak.submit', $spmModel->id) }}" method="POST" id="form-submit-npi">
                            @csrf
                            <button type="submit" class="btn btn-success px-4 py-2" {{ !$isReadyToSubmit ? 'disabled' : '' }}>
                                <i class='bx bx-send me-2'></i> Ajukan Verifikasi
                            </button>
                        </form>
                        @if(!$isReadyToSubmit)
                            <div class="alert alert-danger mt-3 text-start mb-0 p-3">
                                <h6 class="alert-heading font-14 fw-bold mb-2"><i class='bx bx-error-alt me-1'></i> Pengajuan Terkunci</h6>
                                <ul class="mb-0 ps-3 font-13">
                                    @foreach($readinessIssues as $issue)
                                        <li>{{ $issue }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Progress Persetujuan Paralel -->
        @if(in_array($statusNpi, [\App\Models\DokumenNpi::STATUS_MENUNGGU_UPLOAD, \App\Models\DokumenNpi::STATUS_NPI_TERBIT, \App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL]))
        <div class="card radius-10 mb-4 shadow-sm border-0 border-start border-4 border-success">
            <div class="card-header bg-transparent pt-4 pb-2">
                <h6 class="mb-0 fw-bold text-uppercase"><i class='bx bx-upload text-success me-2'></i>Upload NPI Bertandatangan</h6>
            </div>
            <div class="card-body">
                @if($npiModel->hasSignedNpiFile())
                    <div class="alert alert-success d-flex align-items-center mb-3">
                        <i class='bx bx-check-circle fs-3 me-3'></i>
                        <div>
                            <h6 class="alert-heading fw-bold mb-1">NPI Telah Terbit</h6>
                            <span class="font-13">File NPI fisik bertandatangan telah diunggah dan disimpan.</span>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center mb-3">
                        <i class='bx bxs-file-pdf text-danger fs-1 me-3'></i>
                        <div class="flex-grow-1">
                            <h6 class="mb-0 fw-bold">{{ $npiModel->signedNpiArsip->nama_file_asli ?? 'Dokumen NPI' }}</h6>
                            <small class="text-muted">Diunggah pada {{ $npiModel->signedNpiArsip->created_at->format('d M Y H:i') }}</small>
                        </div>
                        <a href="{{ Storage::url($npiModel->signedNpiArsip->path_file) }}" target="_blank" class="btn btn-primary btn-sm px-3"><i class='bx bx-download me-1'></i> Unduh File</a>
                    </div>
                    
                    <hr>
                    <p class="mb-2 fw-bold font-13 text-muted">Upload Ulang File NPI Fisik (Opsional)</p>
                @else
                    <div class="alert alert-warning d-flex align-items-center mb-4">
                        <i class='bx bx-error fs-3 me-3'></i>
                        <div>
                            <h6 class="alert-heading fw-bold mb-1">Menunggu Upload Fisik</h6>
                            <span class="font-13">NPI telah diverifikasi penuh. Silakan cetak, tandatangani, dan unggah scan/foto dokumen NPI untuk menerbitkan NPI dan bisa digunakan sebagai dasar SP2D.</span>
                        </div>
                    </div>
                @endif
                
                <form action="{{ route('npis.kontrak.upload-signed-npi', $npiModel->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="input-group">
                        <input type="file" class="form-control" name="file_npi_ttd" accept=".pdf,.jpg,.jpeg,.png" required>
                        <button class="btn btn-success px-4" type="submit"><i class='bx bx-upload me-1'></i> Unggah & Terbitkan NPI</button>
                    </div>
                    <small class="text-muted mt-2 d-block">Format: PDF/JPG/PNG. Maks: 10MB.</small>
                    @error('file_npi_ttd')
                        <span class="text-danger small mt-1 d-block"><i class='bx bx-error-circle'></i> {{ $message }}</span>
                    @enderror
                </form>
            </div>
        </div>
        @endif

        @if(!in_array($statusNpi, ['DRAFT', 'Belum Dibuat', '']))
        <div class="card radius-10 mb-4 shadow-sm border-0 border-start border-4 border-warning">
            <div class="card-header bg-transparent pt-4 pb-2">
                <h6 class="mb-0 fw-bold text-uppercase"><i class='bx bx-git-branch text-warning me-2'></i>Progress Persetujuan</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @php
                        $approvals = [
                            ['title' => 'Bendahara Penerimaan', 'approval' => $benpenApproval, 'default_name' => 'Semua BenPen'],
                            ['title' => 'PPK', 'approval' => $ppkApproval, 'default_name' => 'Semua PPK'],
                            ['title' => 'Koordinator Keuangan', 'approval' => $koordinatorApproval, 'default_name' => $koordinatorKeuanganUser?->name ?? 'Semua Koordinator'],
                            ['title' => 'Kasubbag', 'approval' => $kasubbagApproval, 'default_name' => 'Semua Kasubbag'],
                        ];
                    @endphp

                    @foreach($approvals as $app)
                        <div class="col-12 col-md-6">
                            <div class="border rounded p-3 h-100 d-flex flex-column 
                                @if($app['approval']?->status == 'APPROVED') border-success bg-light-success 
                                @elseif($app['approval']?->status == 'REVISION') border-danger bg-light-danger 
                                @elseif($app['approval']?->status == 'PENDING') border-warning bg-light-warning 
                                @endif">
                                
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0 fw-bold">{{ $app['title'] }}</h6>
                                    <span class="badge 
                                        @if($app['approval']?->status == 'APPROVED') bg-success 
                                        @elseif($app['approval']?->status == 'REVISION') bg-danger 
                                        @elseif($app['approval']?->status == 'PENDING') bg-warning text-dark 
                                        @else bg-secondary @endif">
                                        {{ $app['approval']?->status ?? 'WAITING' }}
                                    </span>
                                </div>

                                <div class="text-muted font-13 mb-3 flex-grow-1"><i class='bx bx-user me-1'></i>{{ $app['approval']?->assignedUser?->name ?? $app['default_name'] }}</div>
                                
                                <div class="mt-auto">
                                    @if($app['approval']?->catatan)
                                        <div class="text-start font-12 text-dark bg-white bg-opacity-50 p-2 rounded border mb-2">
                                            <strong>Catatan:</strong><br>
                                            "{{ $app['approval']->catatan }}"
                                        </div>
                                    @endif
                                    
                                    @if($app['approval']?->acted_at)
                                        <div class="text-end text-muted font-11">
                                            <i class='bx bx-time-five me-1'></i>{{ \Carbon\Carbon::parse($app['approval']->acted_at)->format('d M Y H:i') }}
                                        </div>
                                    @else
                                        <div class="text-end text-muted font-11">
                                            Belum ada aksi
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif


        <!-- Riwayat Aktivitas -->
        @if($recentActivities->count() > 0)
        <div class="card radius-10 shadow-sm border-0 border-start border-4 border-secondary">
            <div class="card-header bg-transparent pt-4 pb-2">
                <h6 class="mb-0 fw-bold text-uppercase"><i class='bx bx-history text-secondary me-2'></i>Riwayat Aktivitas</h6>
            </div>
            <div class="card-body">
                <div class="position-relative ms-3 border-start border-2 border-light py-2">
                    @foreach($recentActivities as $act)
                        <div class="d-flex mb-4 position-relative">
                            <div class="position-absolute top-0 start-0 translate-middle ms-n1">
                                <div class="bg-white border border-secondary border-2 rounded-circle" style="width: 14px; height: 14px;"></div>
                            </div>
                            <div class="ms-4 w-100">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0 fw-bold font-14">{{ $act['title'] }}</h6>
                                    <span class="text-muted font-12"><i class='bx bx-time me-1'></i>{{ $act['time'] }}</span>
                                </div>
                                <div class="text-primary font-13 fw-semibold mb-1">{{ $act['actor'] }}</div>
                                @if($act['note'])
                                    <div class="text-dark font-13 mt-2 bg-light p-2 rounded border-start border-3 border-primary">
                                        <em>"{{ $act['note'] }}"</em>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
