@extends('layouts.app')

@section('content')
<!-- Header/Breadcrumb -->
<div class="row mb-3">
    <div class="col-12 col-md-8">
        <h5 class="mb-0 text-primary fw-bold">Penyusunan NPI Kontrak</h5>
        <p class="text-muted mb-0">SPM: {{ $spmModel->nomor_spm }} | Kontrak: {{ $kontrak?->nomor_spk ?? '-' }}</p>
    </div>
    <div class="col-12 col-md-4 text-md-end mt-2 mt-md-0">
        <a href="{{ route('npis.kontrak.index') }}" class="btn btn-outline-secondary btn-sm"><i class='bx bx-arrow-back'></i> Kembali</a>
        @if($npiModel && !in_array($npiModel->status, [\App\Models\DokumenNpi::STATUS_DRAFT, \App\Models\DokumenNpi::STATUS_REVISI, '']))
            <a href="{{ route('npis.cetak-pdf', $npiModel->id) }}" target="_blank" class="btn btn-danger btn-sm"><i class='bx bxs-file-pdf'></i> Cetak PDF</a>
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
        <h6 class="text-uppercase text-secondary fw-bold mb-2">1. Ringkasan Nilai & SPM</h6>
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
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total Potongan (Rp)</span>
                    <span class="fw-bold text-danger">{{ number_format(($tagihan?->total_kotor ?? 0) - ($tagihan?->total_netto ?? 0), 0, ',', '.') }}</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded">
                    <span class="text-primary fw-bold">Nilai NPI (Netto)</span>
                    <h5 class="mb-0 text-primary fw-bold">Rp {{ number_format($nominalNpi, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
        
        <!-- 2. Card Pekerjaan & Termin -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">2. Dasar Pembayaran Kontrak</h6>
        <div class="card radius-10 shadow-sm mb-4">
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted form-label mb-0">Nama Pekerjaan</label>
                    <div class="fw-bold">{{ $kontrak?->nama_pekerjaan ?? '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="text-muted form-label mb-0">Nomor SPK</label>
                        <div class="fw-bold">{{ $kontrak?->nomor_spk ?? '-' }}</div>
                    </div>
                    <div class="col-6">
                        <label class="text-muted form-label mb-0">Termin</label>
                        <div class="fw-bold"><span class="badge bg-secondary">{{ $termin?->termin ?? '-' }}</span> {{ $termin?->jenis_termin ?? '-' }}</div>
                    </div>
                </div>
                <hr>
                <div class="row mb-2 text-center text-sm-start">
                    <div class="col-4">
                        <small class="text-muted d-block">BAPP</small>
                        <span class="fw-bold font-13">{{ $detailKontrak?->nomor_bapp ?? '-' }}</span>
                    </div>
                    <div class="col-4 border-start">
                        <small class="text-muted d-block">BAST</small>
                        <span class="fw-bold font-13">{{ $detailKontrak?->nomor_bast ?? '-' }}</span>
                    </div>
                    <div class="col-4 border-start">
                        <small class="text-muted d-block">BAP</small>
                        <span class="fw-bold font-13">{{ $detailKontrak?->nomor_bap ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Dokumen Pendukung -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">3. Kelengkapan Dokumen</h6>
        <div class="card radius-10 shadow-sm mb-4">
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($documentStatuses as $doc)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <div>
                            <i class='bx {{ $doc["required"] ? "bx-file" : "bx-file-blank text-muted" }} me-2'></i>
                            {{ $doc['label'] }}
                            @if(!$doc['required']) <small class="text-muted">(Opsional)</small> @endif
                        </div>
                        <div>
                            @if($doc['status'] == 'ready')
                                <span class="badge bg-success"><i class='bx bx-check'></i> Ada</span>
                                @if(is_string($doc['path']) && filter_var($doc['path'], FILTER_VALIDATE_URL))
                                    <a href="{{ $doc['path'] }}" target="_blank" class="btn btn-sm btn-outline-primary ms-1 px-2 py-0"><i class='bx bx-search m-0'></i></a>
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

    <!-- KOLOM KANAN (Aksi & Validasi NPI) -->
    <div class="col-12 col-lg-7">

        <!-- 4. Penentuan Bendahara dan Form NPI -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">4. Entri & Parameter NPI</h6>
        <div class="card radius-10 shadow-sm mb-4">
            <div class="card-body">
                @if($canEditNpi)
                    <form action="{{ route('npis.kontrak.store', $spmModel->id) }}" method="POST" id="form-draft-npi">
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
                                <select name="bendahara_penerimaan_id" class="form-select single-select" required>
                                    <option value="">-- Pilih Bendahara Penerimaan --</option>
                                    @foreach($bendaharaPenerimaans as $user)
                                        <option value="{{ $user->id }}" {{ old('bendahara_penerimaan_id', $npiModel?->bendahara_penerimaan_id) == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Target setoran pemindahbukuan internal.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Verifikator PPK</label>
                                <input type="text" class="form-control bg-light" value="{{ $ppkSpp?->name ?? 'Belum Ditentukan' }}" readonly>
                                <small class="text-muted">Diwariskan dari SPP.</small>
                            </div>

                            <div class="col-md-6">
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
                            <button type="submit" class="btn btn-primary px-4"><i class='bx bx-save'></i> Simpan Draft</button>
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
                        <div class="col-md-6 mt-2">
                            <label class="form-label text-muted mb-0">Verifikator PPK</label>
                            <div class="fw-bold border-bottom pb-1">{{ $ppkSpp?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label text-muted mb-0">Verifikator Kasubbag</label>
                            <div class="fw-bold border-bottom pb-1">{{ $kasubbagUser?->name ?? '-' }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- 5. Tujuan Rekening Vendor -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">5. Destinasi Pembayaran</h6>
        <div class="card radius-10 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary me-3" style="width: 40px; height: 40px;">
                        <i class='bx bx-building-house d-flex justify-content-center align-items-center h-100 font-24'></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">{{ $vendor?->nama_pihak ?? 'Vendor Tidak Ditemukan' }}</h6>
                        <span class="text-muted">NPWP: {{ $vendor?->npwp ?? '-' }}</span>
                    </div>
                </div>
                
                <div class="bg-light p-3 rounded border">
                    <div class="row">
                        <div class="col-sm-4">
                            <small class="text-muted d-block">Bank</small>
                            <strong>{{ $rekening?->nama_bank ?? 'Belum ada' }}</strong>
                        </div>
                        <div class="col-sm-8 border-start">
                            <small class="text-muted d-block">Rekening Penerima</small>
                            <strong>{{ $rekening?->nomor_rekening ?? 'Belum ada' }}</strong><br>
                            <small>A.N: {{ $rekening?->nama_rekening ?? '-' }}</small>
                        </div>
                    </div>
                </div>

                @if(!$rekeningReady)
                    <div class="alert alert-warning mt-3 py-2 mb-0 border-0">
                        <div class="d-flex align-items-center">
                            <i class='bx bx-error-circle fs-5 me-2'></i>
                            <span>Data rekening vendor belum lengkap di Master Data. Ini akan memblokir pengajuan NPI.</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- 6. Persiapan & Workflow (Jika sudah ada NPI Draft/Submitted) -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">6. Kesiapan Verifikasi</h6>
        <div class="card radius-10 shadow-sm mb-4">
            <div class="card-body">
                
                <div class="mb-4">
                    <ul class="list-group list-group-flush">
                        @foreach($readinessChecklist as $check)
                            <li class="list-group-item d-flex align-items-start px-0 border-0 pb-1">
                                @if($check['status'] === 'ready')
                                    <i class='bx bxs-check-circle text-success fs-5 me-2 mt-1'></i>
                                @else
                                    <i class='bx bxs-x-circle text-danger fs-5 me-2 mt-1'></i>
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
                        <form action="{{ route('npis.kontrak.submit', $spmModel->id) }}" method="POST" id="form-submit-npi">
                            @csrf
                            <button type="submit" class="btn btn-success px-4" {{ !$isReadyToSubmit ? 'disabled' : '' }}>
                                <i class='bx bx-send'></i> Ajukan Verifikasi Paralel
                            </button>
                        </form>
                        @if(!$isReadyToSubmit)
                            <div class="text-danger mt-2 text-start font-12 bg-light-danger p-2 rounded border border-danger">
                                <i class='bx bx-error pb-1'></i> <strong>Pengajuan terkunci karena:</strong>
                                <ul class="mb-0 ps-3 mt-1">
                                    @foreach($readinessIssues as $issue)
                                        <li>{{ $issue }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                @if(!in_array($statusNpi, ['DRAFT', 'Belum Dibuat', '']))
                    <hr>
                    <h6 class="fw-bold mb-3"><i class='bx bx-git-branch text-primary'></i> Progress Persetujuan (Paralel)</h6>
                    
                    <div class="row g-3">
                        <!-- Bendahara Penerimaan -->
                        <div class="col-12 col-md-4">
                            <div class="border rounded p-3 h-100 text-center @if($benpenApproval?->status == 'APPROVED') border-success bg-light-success @elseif($benpenApproval?->status == 'REVISION') border-danger bg-light-danger @elseif($benpenApproval?->status == 'PENDING') border-warning bg-light-warning @endif">
                                <div class="badge bg-secondary mb-2 d-inline-block">URUTAN 1</div>
                                <h6 class="mb-1 fw-bold">Bendahara Penerimaan</h6>
                                <p class="mb-2 text-muted font-12" style="height: 35px; overflow: hidden;">{{ $benpenApproval?->assignedUser?->name ?? 'Semua BenPen' }}</p>
                                
                                <span class="badge @if($benpenApproval?->status == 'APPROVED') bg-success @elseif($benpenApproval?->status == 'REVISION') bg-danger @elseif($benpenApproval?->status == 'PENDING') bg-warning text-dark @else bg-light text-dark border @endif d-block px-2 py-2">
                                    {{ $benpenApproval?->status ?? 'WAITING' }}
                                </span>
                                
                                @if($benpenApproval?->catatan)
                                    <div class="mt-2 text-start font-11 text-muted border-top pt-2">
                                        <i class='bx bx-comment-detail'></i> "{{ $benpenApproval->catatan }}"
                                    </div>
                                @endif
                                @if($benpenApproval?->acted_at)
                                    <small class="d-block mt-2 font-10 text-muted">{{ \Carbon\Carbon::parse($benpenApproval->acted_at)->format('d M Y H:i') }}</small>
                                @endif
                            </div>
                        </div>

                        <!-- PPK -->
                        <div class="col-12 col-md-4">
                            <div class="border rounded p-3 h-100 text-center @if($ppkApproval?->status == 'APPROVED') border-success bg-light-success @elseif($ppkApproval?->status == 'REVISION') border-danger bg-light-danger @elseif($ppkApproval?->status == 'PENDING') border-warning bg-light-warning @endif">
                                <div class="badge bg-secondary mb-2 d-inline-block">URUTAN 1</div>
                                <h6 class="mb-1 fw-bold">PPK</h6>
                                <p class="mb-2 text-muted font-12" style="height: 35px; overflow: hidden;">{{ $ppkApproval?->assignedUser?->name ?? 'Semua PPK' }}</p>
                                
                                <span class="badge @if($ppkApproval?->status == 'APPROVED') bg-success @elseif($ppkApproval?->status == 'REVISION') bg-danger @elseif($ppkApproval?->status == 'PENDING') bg-warning text-dark @else bg-light text-dark border @endif d-block px-2 py-2">
                                    {{ $ppkApproval?->status ?? 'WAITING' }}
                                </span>

                                @if($ppkApproval?->catatan)
                                    <div class="mt-2 text-start font-11 text-muted border-top pt-2">
                                        <i class='bx bx-comment-detail'></i> "{{ $ppkApproval->catatan }}"
                                    </div>
                                @endif
                                @if($ppkApproval?->acted_at)
                                    <small class="d-block mt-2 font-10 text-muted">{{ \Carbon\Carbon::parse($ppkApproval->acted_at)->format('d M Y H:i') }}</small>
                                @endif
                            </div>
                        </div>

                        <!-- Kasubbag -->
                        <div class="col-12 col-md-4">
                            <div class="border rounded p-3 h-100 text-center @if($kasubbagApproval?->status == 'APPROVED') border-success bg-light-success @elseif($kasubbagApproval?->status == 'REVISION') border-danger bg-light-danger @elseif($kasubbagApproval?->status == 'PENDING') border-warning bg-light-warning @endif">
                                <div class="badge bg-secondary mb-2 d-inline-block">URUTAN 1</div>
                                <h6 class="mb-1 fw-bold">Kasubbag</h6>
                                <p class="mb-2 text-muted font-12" style="height: 35px; overflow: hidden;">{{ $kasubbagApproval?->assignedUser?->name ?? 'Semua Kasubbag' }}</p>
                                
                                <span class="badge @if($kasubbagApproval?->status == 'APPROVED') bg-success @elseif($kasubbagApproval?->status == 'REVISION') bg-danger @elseif($kasubbagApproval?->status == 'PENDING') bg-warning text-dark @else bg-light text-dark border @endif d-block px-2 py-2">
                                    {{ $kasubbagApproval?->status ?? 'WAITING' }}
                                </span>

                                @if($kasubbagApproval?->catatan)
                                    <div class="mt-2 text-start font-11 text-muted border-top pt-2">
                                        <i class='bx bx-comment-detail'></i> "{{ $kasubbagApproval->catatan }}"
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
            <h6 class="text-uppercase text-secondary fw-bold mb-2">Riwayat Log / Sistem NPI</h6>
            <div class="card radius-10 shadow-sm">
                <div class="card-body">
                    <div class="order-scroll">
                        @foreach($recentActivities as $act)
                            <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                <div class="font-20 text-primary me-3"><i class='bx bx-time'></i></div>
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
