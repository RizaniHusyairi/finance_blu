@extends('layouts.app')

@section('content')
<!-- Header/Breadcrumb -->
<div class="row mb-3">
    <div class="col-12 col-md-8">
        <h5 class="mb-0 text-primary fw-bold">Penyusunan NPI Perjalanan Dinas</h5>
        <p class="text-muted mb-0">SPM: {{ $spmModel->nomor_spm }} | Tagihan: {{ $tagihan?->nomor_tagihan ?? '-' }}</p>
    </div>
    <div class="col-12 col-md-4 text-md-end mt-2 mt-md-0">
        <a href="{{ route('npis.perjaldin.index') }}" class="btn btn-outline-secondary btn-sm"><i class="material-icons-outlined" style="font-size: 16px; margin-bottom: -3px;">arrow_back</i> Kembali</a>
        @if($npiModel && !in_array($npiModel->status, [\App\Models\DokumenNpi::STATUS_DRAFT, \App\Models\DokumenNpi::STATUS_REVISI, '']))
            <a href="{{ route('npis.cetak-pdf', $npiModel->id) }}" target="_blank" class="btn btn-danger btn-sm"><i class="material-icons-outlined" style="font-size: 16px; margin-bottom: -3px;">picture_as_pdf</i> Cetak PDF</a>
        @endif
    </div>
</div>

<!-- Header Status Panel -->
<div class="card radius-10 mb-4 border-top border-0 border-4 border-primary shadow-sm bg-primary bg-opacity-10">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
            <div class="mb-3 mb-md-0">
                <h6 class="mb-1 fw-bold">Nomor NPI</h6>
                <h4 class="mb-0 text-primary">{{ $npiModel->nomor_npi ?? 'DRAFT (Belum Tersimpan)' }}</h4>
            </div>
            
            <div class="d-flex gap-3 text-center">
                <div>
                    <div class="text-muted" style="font-size: 11px;">Status Bisnis</div>
                    <span class="badge @if($statusNpi == \App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL || $statusNpi == \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG) bg-success @elseif($statusNpi == \App\Models\DokumenNpi::STATUS_REVISI) bg-danger @elseif($statusNpi == \App\Models\DokumenNpi::STATUS_DRAFT) bg-secondary @else bg-info @endif font-14">
                        {{ $statusNpi }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- KOLOM KIRI (Konteks & SPP/SPM) -->
    <div class="col-12 col-lg-5">
        <!-- 1. Card Ringkasan NPI -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">1. Ringkasan Nilai & Dokumen Sumber</h6>
        <div class="card radius-10 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Nomor SPM</span>
                    <span class="fw-bold">{{ $spmModel->nomor_spm }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Nomor SPP</span>
                    <span class="fw-bold">{{ $sppModel->nomor_spp }}</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded border border-light">
                    <span class="text-primary fw-bold">Nilai NPI Perjaldin</span>
                    <h5 class="mb-0 text-primary fw-bold">Rp {{ number_format($nominalNpi, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
        
        <!-- 2. Card Komponen Biaya -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">2. Dasar Tagihan Perjaldin</h6>
        <div class="card radius-10 shadow-sm mb-4">
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted form-label mb-0">Uraian Tagihan / Tujuan</label>
                    <div class="fw-bold">{{ $tagihan?->deskripsi ?? 'Perjalanan Dinas' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="text-muted form-label mb-0">Komponen Anggaran</label>
                        <div class="fw-bold">{{ $komponenSpp?->nama_komponen ?? '-' }}</div>
                        @if($komponenSpp?->dipaRevisionItem?->coa)
                            <div class="badge bg-light text-dark font-12 mt-1 border">Akun: {{ $komponenSpp->dipaRevisionItem->coa->kode_akun }} - {{ $komponenSpp->dipaRevisionItem->coa->nama_akun }}</div>
                        @endif
                    </div>
                </div>
                <hr>
                <!-- Rincian Peserta -->
                <div class="mb-2">
                    <label class="text-muted form-label mb-1">Rincian Detail Peserta</label>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered font-12 mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama / NIP</th>
                                    <th>Tujuan</th>
                                    <th>Tgl Berangkat</th>
                                    <th>Lama</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tagihan?->detailPerjaldin ?? [] as $det)
                                @php
                                    $namaPeserta = $det->nama_pegawai ?: ($det->pegawai?->nama_lengkap ?? '-');
                                    $nipPeserta = $det->nip ?: ($det->pegawai?->nip ?? null);
                                    $tglBerangkat = $det->tgl_berangkat
                                        ? \Carbon\Carbon::parse($det->tgl_berangkat)->format('d M Y')
                                        : '-';
                                    $subtotalPeserta = (float) ($det->biaya_tiket ?? 0)
                                        + (float) ($det->biaya_transport ?? 0)
                                        + (float) ($det->biaya_penginapan ?? 0)
                                        + (float) ($det->uang_harian ?? 0)
                                        + (float) ($det->uang_representasi ?? 0);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $namaPeserta }}</div>
                                        <div class="small text-muted">{{ $nipPeserta ?: '-' }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $det->tujuan ?? '-' }}</div>
                                        @if($det->provinsi?->provinsi)
                                            <div class="small text-muted">{{ $det->provinsi->provinsi }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $tglBerangkat }}</td>
                                    <td>{{ $det->lama_hari ?? 0 }} Hari</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($subtotalPeserta, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada detail.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- KOLOM KANAN (Aksi & Validasi NPI) -->
    <div class="col-12 col-lg-7">

        <!-- 3. Penentuan Bendahara dan Form NPI -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">3. Entri & Parameter NPI</h6>
        <div class="card radius-10 shadow-sm mb-4">
            <div class="card-body">
                @if($canEditNpi)
                    <form action="{{ route('npis.perjaldin.store', $spmModel->id) }}" method="POST" id="form-draft-npi">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nomor NPI <span class="text-danger">*</span></label>
                                <input type="text" name="nomor_npi" class="form-control fw-bold text-primary bg-light" value="{{ old('nomor_npi', $npiModel?->nomor_npi ?? $autoNomorNpi) }}" required>
                                <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Nomor di atas diturunkan dari SPP, ubah jika perlu.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal NPI <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_npi" class="form-control" value="{{ old('tanggal_npi', $npiModel?->tanggal_npi?->format('Y-m-d') ?? date('Y-m-d')) }}" required>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label">Bendahara Penerimaan <span class="text-danger">*</span></label>
                                <input type="hidden" name="bendahara_penerimaan_id" value="{{ $bendaharaPenerimaanTagihan?->id }}">
                                <input type="text" class="form-control bg-light" value="{{ $bendaharaPenerimaanTagihan?->name ?? $tagihan?->bendahara_penerimaan_nama_snapshot ?? 'Belum ditentukan pada tagihan' }}" readonly>
                                <small class="text-muted">Diwariskan dari verifikator Bendahara Penerimaan yang dipilih saat tagihan diajukan.</small>
                                @if(!$bendaharaPenerimaanTagihan)
                                    <div class="text-danger small mt-1">Verifikator Bendahara Penerimaan belum ada pada tagihan sumber.</div>
                                @endif
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Verifikator PPK</label>
                                <input type="text" class="form-control bg-light" value="{{ $ppkSpp?->name ?? 'Belum Ditentukan' }}" readonly>
                                <small class="text-muted">Diwariskan dari SPP.</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Koordinator Keuangan</label>
                                <input type="text" class="form-control bg-light" value="{{ $koordinatorKeuanganUser?->name ?? $tagihan?->koordinator_keuangan_nama_snapshot ?? 'Belum Ditentukan' }}" readonly>
                                <small class="text-muted">Verifikator Koordinator.</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Verifikator Kasubbag</label>
                                <input type="text" class="form-control bg-light" value="{{ $kasubbagUser?->name ?? 'Belum Ditentukan' }}" readonly>
                                <small class="text-muted">Terisi Otomatis.</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Uraian / Catatan <small class="text-muted">(Opsional)</small></label>
                                <textarea name="uraian_npi" class="form-control" rows="2">{{ old('uraian_npi', $npiModel?->catatan) }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary px-4"><i class="material-icons-outlined" style="font-size: 16px; margin-bottom: -3px;">save</i> Simpan Draft</button>
                        </div>
                    </form>
                @else
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted mb-0">Nomor NPI</label>
                            <div class="fw-bold border-bottom pb-1">{{ $npiModel->nomor_npi }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted mb-0">Tanggal NPI</label>
                            <div class="fw-bold border-bottom pb-1">{{ $npiModel->tanggal_npi?->format('d M Y') }}</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted mb-0">Bendahara Penerimaan</label>
                            <div class="fw-bold border-bottom pb-1">{{ $npiModel->bendaharaPenerimaan?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-4 mt-2">
                            <label class="form-label text-muted mb-0">Verifikator PPK</label>
                            <div class="fw-bold border-bottom pb-1">{{ $ppkSpp?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-4 mt-2">
                            <label class="form-label text-muted mb-0">Koordinator Keuangan</label>
                            <div class="fw-bold border-bottom pb-1">{{ $koordinatorKeuanganUser?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-4 mt-2">
                            <label class="form-label text-muted mb-0">Verifikator Kasubbag</label>
                            <div class="fw-bold border-bottom pb-1">{{ $kasubbagUser?->name ?? '-' }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- 4. Persiapan & Workflow -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">4. Kesiapan Verifikasi</h6>
        <div class="card radius-10 shadow-sm mb-4">
            <div class="card-body">
                
                <div class="mb-4">
                    <ul class="list-group list-group-flush">
                        @foreach($readinessChecklist as $check)
                            <li class="list-group-item d-flex align-items-start px-0 border-0 pb-1">
                                @if($check['status'] === 'ready')
                                    <i class="material-icons-outlined text-success fs-5 me-2 mt-1" style="font-size: 20px;">check_circle</i>
                                @else
                                    <i class="material-icons-outlined text-danger fs-5 me-2 mt-1" style="font-size: 20px;">cancel</i>
                                @endif
                                <div>
                                    <h6 class="mb-0">{{ $check['label'] }}</h6>
                                    <small class="text-muted">{{ $check['hint'] }}</small>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                @if($canSubmit)
                    <hr>
                    <div class="text-end">
                        <form action="{{ route('npis.perjaldin.submit', $spmModel->id) }}" method="POST" id="form-submit-npi">
                            @csrf
                            <button type="submit" class="btn btn-success px-4" {{ !$isReadyToSubmit ? 'disabled' : '' }}>
                                <i class="material-icons-outlined" style="font-size: 16px; margin-bottom: -3px;">send</i> Ajukan Verifikasi
                            </button>
                        </form>
                        @if(!$isReadyToSubmit)
                            <div class="text-danger mt-2 text-start font-12 bg-light-danger p-2 rounded border border-danger">
                                <i class="material-icons-outlined" style="font-size: 16px; margin-bottom: -3px;">error</i> <strong>Pengajuan terkunci karena:</strong>
                                <ul class="mb-0 ps-3 mt-1">
                                    @foreach($readinessIssues as $issue)
                                        <li>{{ $issue }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                @if(in_array($statusNpi, [\App\Models\DokumenNpi::STATUS_MENUNGGU_UPLOAD, \App\Models\DokumenNpi::STATUS_NPI_TERBIT, \App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL]))
                    <hr>
                    <h6 class="fw-bold mb-3 text-success"><i class="material-icons-outlined text-success" style="font-size: 16px; margin-bottom: -3px;">upload_file</i> Upload NPI Bertandatangan</h6>
                    
                    <div class="card bg-light border-0 shadow-none mb-4">
                        <div class="card-body">
                            @if($npiModel->hasSignedNpiFile())
                                <div class="alert alert-success border-0 d-flex align-items-center mb-3">
                                    <i class="material-icons-outlined fs-3 me-3">check_circle</i>
                                    <div>
                                        <h6 class="alert-heading fw-bold mb-1">NPI Telah Terbit</h6>
                                        <span class="font-13">File NPI fisik bertandatangan telah diunggah dan disimpan.</span>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3 bg-white p-3 rounded border">
                                    <i class="material-icons-outlined text-danger fs-1 me-3">picture_as_pdf</i>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-bold">{{ $npiModel->signedNpiArsip->nama_file_asli ?? 'Dokumen NPI' }}</h6>
                                        <small class="text-muted">Diunggah pada {{ $npiModel->signedNpiArsip->created_at->format('d M Y H:i') }}</small>
                                    </div>
                                    <a href="{{ Storage::url($npiModel->signedNpiArsip->path_file) }}" target="_blank" class="btn btn-primary btn-sm px-3"><i class="material-icons-outlined" style="font-size: 16px; margin-bottom: -3px;">download</i> Unduh</a>
                                </div>
                                
                                <hr class="border-dashed">
                                <p class="mb-2 fw-bold font-13 text-muted">Upload Ulang File NPI Fisik (Opsional)</p>
                            @else
                                <div class="alert alert-warning border-0 d-flex align-items-center mb-3">
                                    <i class="material-icons-outlined fs-3 me-3">warning</i>
                                    <div>
                                        <h6 class="alert-heading fw-bold mb-1">Menunggu Upload Fisik</h6>
                                        <span class="font-13">NPI telah diverifikasi penuh. Silakan cetak, tandatangani, dan unggah scan/foto dokumen NPI untuk menerbitkan NPI dan bisa digunakan sebagai dasar SP2D.</span>
                                    </div>
                                </div>
                            @endif
                            
                            <form action="{{ route('npis.perjaldin.upload-signed-npi', $npiModel->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="input-group">
                                    <input type="file" class="form-control" name="file_npi_ttd" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <button class="btn btn-success px-4 fw-bold" type="submit"><i class="material-icons-outlined" style="font-size: 16px; margin-bottom: -3px;">upload</i> Unggah</button>
                                </div>
                                <small class="text-muted mt-2 d-block">Format: PDF/JPG/PNG. Maks: 10MB.</small>
                                @error('file_npi_ttd')
                                    <span class="text-danger small mt-1 d-block"><i class="material-icons-outlined" style="font-size: 14px;">error</i> {{ $message }}</span>
                                @enderror
                            </form>
                        </div>
                    </div>
                @endif

                @if(!in_array($statusNpi, ['DRAFT', 'Belum Dibuat', '']))
                    <hr>
                    <h6 class="fw-bold mb-3"><i class="material-icons-outlined text-primary" style="font-size: 16px; margin-bottom: -3px;">account_tree</i> Progress Persetujuan (Paralel)</h6>
                    
                    <div class="row g-3">
                        <!-- Bendahara Penerimaan -->
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="border rounded p-3 h-100 text-center @if($benpenApproval?->status == 'APPROVED') border-success bg-light-success @elseif($benpenApproval?->status == 'REVISION') border-danger bg-light-danger @elseif($benpenApproval?->status == 'PENDING') border-warning bg-light-warning @endif">
                                <div class="badge bg-secondary mb-2 d-inline-block">URUTAN 1</div>
                                <h6 class="mb-1 fw-bold">Bendahara Penerimaan</h6>
                                <p class="mb-2 text-muted font-12" style="height: 35px; overflow: hidden;">{{ $benpenApproval?->assignedUser?->name ?? 'Semua BenPen' }}</p>
                                
                                <span class="badge @if($benpenApproval?->status == 'APPROVED') bg-success @elseif($benpenApproval?->status == 'REVISION') bg-danger @elseif($benpenApproval?->status == 'PENDING') bg-warning text-dark @else bg-light text-dark border @endif d-block px-2 py-2">
                                    {{ $benpenApproval?->status ?? 'WAITING' }}
                                </span>
                                
                                @if($benpenApproval?->catatan)
                                    <div class="mt-2 text-start font-11 text-muted border-top pt-2">
                                        <i class="material-icons-outlined" style="font-size: 12px;">chat</i> "{{ $benpenApproval->catatan }}"
                                    </div>
                                @endif
                                @if($benpenApproval?->acted_at)
                                    <small class="d-block mt-2 font-10 text-muted">{{ \Carbon\Carbon::parse($benpenApproval->acted_at)->format('d M Y H:i') }}</small>
                                @endif
                            </div>
                        </div>

                        <!-- PPK -->
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="border rounded p-3 h-100 text-center @if($ppkApproval?->status == 'APPROVED') border-success bg-light-success @elseif($ppkApproval?->status == 'REVISION') border-danger bg-light-danger @elseif($ppkApproval?->status == 'PENDING') border-warning bg-light-warning @endif">
                                <div class="badge bg-secondary mb-2 d-inline-block">URUTAN 1</div>
                                <h6 class="mb-1 fw-bold">PPK</h6>
                                <p class="mb-2 text-muted font-12" style="height: 35px; overflow: hidden;">{{ $ppkApproval?->assignedUser?->name ?? 'Semua PPK' }}</p>
                                
                                <span class="badge @if($ppkApproval?->status == 'APPROVED') bg-success @elseif($ppkApproval?->status == 'REVISION') bg-danger @elseif($ppkApproval?->status == 'PENDING') bg-warning text-dark @else bg-light text-dark border @endif d-block px-2 py-2">
                                    {{ $ppkApproval?->status ?? 'WAITING' }}
                                </span>

                                @if($ppkApproval?->catatan)
                                    <div class="mt-2 text-start font-11 text-muted border-top pt-2">
                                        <i class="material-icons-outlined" style="font-size: 12px;">chat</i> "{{ $ppkApproval->catatan }}"
                                    </div>
                                @endif
                                @if($ppkApproval?->acted_at)
                                    <small class="d-block mt-2 font-10 text-muted">{{ \Carbon\Carbon::parse($ppkApproval->acted_at)->format('d M Y H:i') }}</small>
                                @endif
                            </div>
                        </div>

                        <!-- Koordinator Keuangan -->
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="border rounded p-3 h-100 text-center @if($koordinatorApproval?->status == 'APPROVED') border-success bg-light-success @elseif($koordinatorApproval?->status == 'REVISION') border-danger bg-light-danger @elseif($koordinatorApproval?->status == 'PENDING') border-warning bg-light-warning @endif">
                                <div class="badge bg-secondary mb-2 d-inline-block">URUTAN 1</div>
                                <h6 class="mb-1 fw-bold">Koordinator Keuangan</h6>
                                <p class="mb-2 text-muted font-12" style="height: 35px; overflow: hidden;">{{ $koordinatorApproval?->assignedUser?->name ?? $koordinatorKeuanganUser?->name ?? 'Semua Koordinator' }}</p>

                                <span class="badge @if($koordinatorApproval?->status == 'APPROVED') bg-success @elseif($koordinatorApproval?->status == 'REVISION') bg-danger @elseif($koordinatorApproval?->status == 'PENDING') bg-warning text-dark @else bg-light text-dark border @endif d-block px-2 py-2">
                                    {{ $koordinatorApproval?->status ?? 'WAITING' }}
                                </span>

                                @if($koordinatorApproval?->catatan)
                                    <div class="mt-2 text-start font-11 text-muted border-top pt-2">
                                        <i class="material-icons-outlined" style="font-size: 12px;">chat</i> "{{ $koordinatorApproval->catatan }}"
                                    </div>
                                @endif
                                @if($koordinatorApproval?->acted_at)
                                    <small class="d-block mt-2 font-10 text-muted">{{ \Carbon\Carbon::parse($koordinatorApproval->acted_at)->format('d M Y H:i') }}</small>
                                @endif
                            </div>
                        </div>

                        <!-- Kasubbag -->
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="border rounded p-3 h-100 text-center @if($kasubbagApproval?->status == 'APPROVED') border-success bg-light-success @elseif($kasubbagApproval?->status == 'REVISION') border-danger bg-light-danger @elseif($kasubbagApproval?->status == 'PENDING') border-warning bg-light-warning @endif">
                                <div class="badge bg-secondary mb-2 d-inline-block">URUTAN 1</div>
                                <h6 class="mb-1 fw-bold">Kasubbag</h6>
                                <p class="mb-2 text-muted font-12" style="height: 35px; overflow: hidden;">{{ $kasubbagApproval?->assignedUser?->name ?? 'Semua Kasubbag' }}</p>
                                
                                <span class="badge @if($kasubbagApproval?->status == 'APPROVED') bg-success @elseif($kasubbagApproval?->status == 'REVISION') bg-danger @elseif($kasubbagApproval?->status == 'PENDING') bg-warning text-dark @else bg-light text-dark border @endif d-block px-2 py-2">
                                    {{ $kasubbagApproval?->status ?? 'WAITING' }}
                                </span>

                                @if($kasubbagApproval?->catatan)
                                    <div class="mt-2 text-start font-11 text-muted border-top pt-2">
                                        <i class="material-icons-outlined" style="font-size: 12px;">chat</i> "{{ $kasubbagApproval->catatan }}"
                                    </div>
                                @endif
                                @if($kasubbagApproval?->acted_at)
                                    <small class="d-block mt-2 font-10 text-muted">{{ \Carbon\Carbon::parse($kasubbagApproval->acted_at)->format('d M Y H:i') }}</small>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
                
            </div>
        </div>

        @if($recentActivities->count() > 0)
            <h6 class="text-uppercase text-secondary fw-bold mb-2">Riwayat Sistem NPI</h6>
            <div class="card radius-10 shadow-sm">
                <div class="card-body">
                    <div class="order-scroll">
                        @foreach($recentActivities as $act)
                            <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                <div class="font-20 text-primary me-3"><i class="material-icons-outlined" style="font-size: 20px;">history</i></div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ $act['title'] }}</h6>
                                    <span class="text-muted font-13">{{ $act['actor'] }}</span>
                                    @if($act['note'])
                                        <div class="text-muted font-12 fst-italic mt-1">"{{ $act['note'] }}"</div>
                                    @endif
                                </div>
                                <div class="text-end">
                                    <span class="text-muted font-12">{{ $act['time'] }}</span>
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
