@extends('layouts.app')
@section('title', 'Workspace NPI Honorarium')

@php
    $statusNpiClass = match ($statusNpi) {
        'Belum Dibuat' => 'bg-secondary',
        'DRAFT' => 'bg-warning text-dark',
        'Menunggu Verifikasi' => 'bg-info',
        'Revisi' => 'bg-danger',
        'Selesai', 'NPI Terbit' => 'bg-success',
        \App\Models\DokumenNpi::STATUS_SUBMITTED_KASUBAG,
        \App\Models\DokumenNpi::STATUS_SUBMITTED_PPK,
        \App\Models\DokumenNpi::STATUS_SUBMITTED_BENPEN => 'bg-info text-dark',
        \App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL,
        \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG => 'bg-success',
        default => 'bg-secondary',
    };

    $benpenStatusLabel = $benpenApproval?->status ?? 'Belum diajukan';
    $ppkStatusLabel = $ppkApproval?->status ?? 'Belum diajukan';
    $koordinatorStatusLabel = $koordinatorApproval?->status ?? 'Belum diajukan';
    $kasubbagStatusLabel = $kasubbagApproval?->status ?? 'Belum diajukan';

    $statusClassMap = [
        'APPROVED' => 'text-success', 'PENDING' => 'text-warning', 'REVISION' => 'text-danger', 'REJECTED' => 'text-danger'
    ];
    
    $benpenStatusClass = $statusClassMap[$benpenStatusLabel] ?? 'text-muted';
    $ppkStatusClass = $statusClassMap[$ppkStatusLabel] ?? 'text-muted';
    $koordinatorStatusClass = $statusClassMap[$koordinatorStatusLabel] ?? 'text-muted';
    $kasubbagStatusClass = $statusClassMap[$kasubbagStatusLabel] ?? 'text-muted';

    $progressStep = 3; 
    if (in_array($statusNpi, [\App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL, \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG])) $progressStep = 4;
@endphp

@push('css')
    <style>
        .npi-workspace-hero { background: linear-gradient(135deg, #f8f9ff, #eef2ff); border-bottom: 1px solid rgba(15,23,42,.08); padding-bottom: 1.5rem; margin-bottom: 2rem; position: relative; }
        .npi-workspace-hero::before { content: ""; position: absolute; left: 0; top: 0; width: 4px; height: 100%; background: #6366f1; }
        .npi-summary-tile { background: #fff; border: 1px solid rgba(15,23,42,.08); border-radius: .75rem; padding: 1rem; height: 100%; }
        .npi-summary-tile .label { color: #6c757d; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; margin-bottom: .35rem; }
        .npi-summary-tile .value { color: #212529; font-weight: 700; line-height: 1.35; }
        .npi-section-card { border: 0; border-radius: 1rem; box-shadow: 0 .125rem .25rem rgba(15,23,42,.04); border: 1px solid rgba(15,23,42,.08); overflow: hidden; background: #fff; }
        .npi-section-heading { color: #475569; font-size: .8rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; margin-bottom: 1.2rem; display: flex; align-items: center; gap: .5rem; }
        .npi-info-block { margin-bottom: 1.2rem; }
        .npi-info-block .label { color: #64748b; font-size: .8rem; margin-bottom: .25rem; }
        .npi-info-block .value { font-weight: 600; color: #1e293b; line-height: 1.4; }
        .npi-readiness-item { display: flex; align-items: flex-start; gap: .85rem; padding: .65rem 0; border-bottom: 1px solid rgba(15,23,42,.04); }
        .npi-readiness-item:last-child { border-bottom: 0; }
        .npi-readiness-icon { width: 1.5rem; height: 1.5rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-size: .75rem; flex-shrink: 0; }
        .npi-icon-ready { background: rgba(25,135,84,.12); color: #198754; }
        .npi-icon-missing { background: rgba(220,53,69,.12); color: #dc3545; }
        .npi-activity-row { position: relative; padding-left: 1.25rem; margin-bottom: 1.25rem; }
        .npi-activity-row:last-child { margin-bottom: 0; }
        .npi-activity-row::before { content: ""; position: absolute; left: 0; top: .35rem; width: .5rem; height: .5rem; border-radius: 999px; background: #cbd5e1; }
        .npi-activity-active::before { background: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.2); }

        .timeline-wrapper { display: flex; align-items: center; justify-content: space-between; position: relative; padding: 2rem 0; margin-bottom: 1rem; }
        .timeline-line { position: absolute; top: 3.25rem; left: 5%; right: 5%; height: 3px; background: #e2e8f0; z-index: 1; }
        .timeline-step { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; text-align: center; flex: 1; min-width: 120px; }
        .timeline-icon { width: 44px; height: 44px; border-radius: 999px; background: #fff; border: 3px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #94a3b8; font-weight: bold; margin-bottom: .75rem; transition: all .2s; }
        .timeline-label { font-weight: 600; color: #475569; font-size: .85rem; line-height: 1.3; }
        .timeline-sub { font-size: .75rem; color: #94a3b8; margin-top: .25rem; max-width: 150px; }
        .timeline-step.passed .timeline-icon { border-color: #10b981; background: #10b981; color: #fff; }
        .timeline-step.active .timeline-icon { border-color: #6366f1; color: #6366f1; box-shadow: 0 0 0 4px rgba(99,102,241,.2); }
        .timeline-step.revision .timeline-icon { border-color: #ef4444; color: #ef4444; background: #fee2e2; }

        .action-card { border: 2px solid #6366f1; background: #fcfdff; border-radius: 1rem; box-shadow: 0 .25rem 1rem rgba(99,102,241,.15); }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <x-page-title title="Workspace Bendahara" subtitle="Pembuatan NPI Honorarium" />
        <a href="{{ route('npis.honor.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar NPI</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
            <div class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill fs-5"></i><div class="mt-1">{{ session('success') }}</div></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
            <div class="d-flex align-items-start gap-2"><i class="bi bi-exclamation-triangle-fill fs-5"></i><div class="mt-1">{{ session('error') }}</div></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- A. HEADER KERJA --}}
    <div class="npi-workspace-hero p-4 rounded-3 shadow-sm">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
            <div class="flex-grow-1">
                <h3 class="fw-bold mb-2 text-dark">{{ $tagihan?->deskripsi ?? 'NPI Honorarium' }}</h3>
                <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
                    <span class="badge {{ $statusNpiClass }} px-3 py-2">NPI: {{ str_replace('_', ' ', $statusNpi) }}</span>
                    @if($spmModel->status === 'Disetujui Final') <span class="badge bg-light text-dark px-3 py-2 border"><i class="bi bi-shield-check text-success"></i> SPM Disahkan</span> @endif
                </div>
                <div class="row g-3">
                    <div class="col-md-3 col-6"><div class="npi-summary-tile"><div class="label">Nomor NPI</div><div class="value">{{ $npiModel?->nomor_npi ?? 'DRAFT' }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="npi-summary-tile"><div class="label">Nomor SPM</div><div class="value">{{ $spmModel->nomor_spm ?? '-' }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="npi-summary-tile"><div class="label">Role Pembuat</div><div class="value"><i class="bi bi-wallet2 text-muted"></i> Ben. Pengeluaran</div></div></div>
                    <div class="col-md-3 col-6"><div class="npi-summary-tile"><div class="label">Nilai Netto (SPM)</div><div class="value text-success fs-6">Rp {{ number_format($nominalNpi, 0, ',', '.') }}</div></div></div>
                </div>
            </div>

            <div class="d-flex flex-column justify-content-center gap-2" style="min-width: 200px;">
                @if($npiModel && in_array($npiModel->status, [\App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL, \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG]))
                <a href="{{ route('npis.cetak-pdf', $npiModel->id) }}" target="_blank" class="btn btn-danger shadow-sm"><i class="bi bi-file-earmark-pdf me-1"></i> Buka / Cetak PDF NPI</a>
                @endif
            </div>
        </div>
    </div>

    {{-- B. PANEL TIMELINE & PROGRESS PARALEL --}}
    <div class="card npi-section-card mb-4">
        <div class="card-body p-4 pb-2">
            <h5 class="fw-bold text-dark mb-4"><i class="bi bi-shield-check text-primary me-2"></i> Progress Verifikasi NPI Parallel</h5>
            
            <div class="timeline-wrapper">
                <div class="timeline-line"></div>
                {{-- Step 1: Draft SPM --}}
                <div class="timeline-step passed">
                    <div class="timeline-icon"><i class="bi bi-file-earmark-text"></i></div>
                    <div class="timeline-label">Draft</div>
                    <div class="timeline-sub">Belum Tersedia</div>
                </div>
                {{-- Step 2: Verifikasi Benpen --}}
                <div class="timeline-step {{ $benpenApproval?->status === 'APPROVED' ? 'passed' : ($benpenApproval?->status === 'REVISION' ? 'revision' : ($progressStep == 3 ? 'active' : '')) }}">
                    <div class="timeline-icon"><i class="bi bi-person-check"></i></div>
                    <div class="timeline-label">Ben. Penerimaan</div>
                    <div class="timeline-sub fw-semibold {{ $benpenStatusClass }}">{{ $benpenStatusLabel }}</div>
                    <div class="timeline-sub mt-0 opacity-75" style="font-size: 0.7rem;">{{ $benpenApproval?->actedByUser?->name ?? $benpenApproval?->assignedUser?->name ?? $npiModel?->bendaharaPenerimaan?->name ?? 'Belum Ditentukan' }}</div>
                </div>
                {{-- Step 2: Verifikasi PPK --}}
                <div class="timeline-step {{ $ppkApproval?->status === 'APPROVED' ? 'passed' : ($ppkApproval?->status === 'REVISION' ? 'revision' : ($progressStep == 3 ? 'active' : '')) }}">
                    <div class="timeline-icon"><i class="bi bi-person-badge"></i></div>
                    <div class="timeline-label">PPK</div>
                    <div class="timeline-sub fw-semibold {{ $ppkStatusClass }}">{{ $ppkStatusLabel }}</div>
                    <div class="timeline-sub mt-0 opacity-75" style="font-size: 0.7rem;">{{ $ppkApproval?->actedByUser?->name ?? $ppkApproval?->assignedUser?->name ?? $ppkSpp?->name ?? 'PPK' }}</div>
                </div>
                {{-- Step 2: Verifikasi Koordinator Keuangan --}}
                <div class="timeline-step {{ $koordinatorApproval?->status === 'APPROVED' ? 'passed' : ($koordinatorApproval?->status === 'REVISION' ? 'revision' : ($progressStep == 3 ? 'active' : '')) }}">
                    <div class="timeline-icon"><i class="bi bi-diagram-3"></i></div>
                    <div class="timeline-label">Koord. Keuangan</div>
                    <div class="timeline-sub fw-semibold {{ $koordinatorStatusClass }}">{{ $koordinatorStatusLabel }}</div>
                    <div class="timeline-sub mt-0 opacity-75" style="font-size: 0.7rem;">{{ $koordinatorApproval?->actedByUser?->name ?? $koordinatorApproval?->assignedUser?->name ?? $koordinatorKeuanganUser?->name ?? 'Koordinator' }}</div>
                </div>
                {{-- Step 2: Verifikasi Kasubbag --}}
                <div class="timeline-step {{ $kasubbagApproval?->status === 'APPROVED' ? 'passed' : ($kasubbagApproval?->status === 'REVISION' ? 'revision' : ($progressStep == 3 ? 'active' : '')) }}">
                    <div class="timeline-icon"><i class="bi bi-building"></i></div>
                    <div class="timeline-label">Kasubbag</div>
                    <div class="timeline-sub fw-semibold {{ $kasubbagStatusClass }}">{{ $kasubbagStatusLabel }}</div>
                    <div class="timeline-sub mt-0 opacity-75" style="font-size: 0.7rem;">{{ $kasubbagApproval?->actedByUser?->name ?? $kasubbagApproval?->assignedUser?->name ?? $kasubbagUser?->name ?? 'Kasubbag' }}</div>
                </div>
                {{-- Step 3: Final --}}
                <div class="timeline-step {{ $progressStep >= 4 ? 'passed' : '' }}">
                    <div class="timeline-icon"><i class="bi bi-check-all"></i></div>
                    <div class="timeline-label">NPI Terbit</div>
                    <div class="timeline-sub">Selesai</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- C. KOLOM KIRI (SUMBER DATA & Checklist) --}}
        <div class="col-xl-7">

            <div class="row g-4 mb-4">
                <div class="col-xl-12">
                    <div class="bg-white p-4 rounded-3 border shadow-sm h-100 border-info border-opacity-25">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-check me-2 text-info"></i> Checklist Kesiapan Berkas</h6>
                        </div>
                        <div style="font-size: 0.95rem;">
                            @foreach($readinessChecklist as $item)
                                <div class="npi-readiness-item py-2">
                                    <span class="npi-readiness-icon {{ $item['status'] === 'ready' ? 'npi-icon-ready' : 'npi-icon-missing' }}"><i class="bi {{ $item['status'] === 'ready' ? 'bi-check-lg' : 'bi-x-lg' }}"></i></span>
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $item['label'] }}</div>
                                        <div class="text-muted small lh-sm">{{ $item['hint'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Ringkasan Tagihan & NPI --}}
            <div class="card npi-section-card mb-4">
                <div class="card-body p-4">
                    <div class="npi-section-heading text-secondary"><i class="bi bi-wallet2"></i> Data Bukti Keuangan (SPM/SPP/Tagihan)</div>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="npi-info-block"><div class="label">Nomor SPP Asli</div><div class="value">{{ $sppModel->nomor_spp ?? '-' }}</div></div></div>
                        <div class="col-md-6"><div class="npi-info-block"><div class="label">Tanggal SPP</div><div class="value">{{ optional($sppModel->tanggal_spp)->format('d F Y') ?? '-' }}</div></div></div>
                        <div class="col-md-6"><div class="npi-info-block"><div class="label">Nomor Tagihan Honorarium</div><div class="value">{{ $tagihan?->nomor_tagihan ?? '-' }}</div></div></div>
                        <div class="col-md-6"><div class="npi-info-block"><div class="label">Pemrakarsa Honorarium</div><div class="value"><i class="bi bi-person-badge text-muted me-1"></i> {{ $ppkSpp?->name ?? 'PPK' }}</div></div></div>
                        <div class="col-md-4"><div class="npi-info-block"><div class="label">Nilai Bruto Tagihan</div><div class="value">Rp {{ number_format($tagihan?->total_bruto ?? 0, 0, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="npi-info-block"><div class="label">Total Potongan (Pajak)</div><div class="value text-danger">Rp {{ number_format($tagihan?->total_potongan ?? 0, 0, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="npi-info-block"><div class="label">Nilai Netto (Sama dgn SPM)</div><div class="value text-success fs-5">Rp {{ number_format($tagihan?->total_netto ?? 0, 0, ',', '.') }}</div></div></div>
                    </div>
                </div>
            </div>

            {{-- Rincian Penerima Honorarium --}}
            <div class="card npi-section-card mb-4">
                <div class="card-body p-4">
                    <div class="npi-section-heading text-secondary d-flex justify-content-between align-items-center">
                        <div><i class="bi bi-people"></i> Rincian Personel / Penerima</div>
                        <span class="badge bg-light text-dark border">{{ count($tagihan?->detailHonorarium ?? []) }} Orang</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Personel</th>
                                    <th>Jabatan</th>
                                    <th class="text-end">Bruto</th>
                                    <th class="text-end">PPh</th>
                                    <th class="text-end">Netto</th>
                                    <th>Rekening</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tagihan->detailHonorarium ?? [] as $personel)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $personel->nama_personel }}</div>
                                            <div class="text-muted" style="font-size: 0.75rem;">NIP: {{ $personel->nrp_nip ?? '-' }}</div>
                                        </td>
                                        <td>{{ $personel->jabatan ?? '-' }}</td>
                                        <td class="text-end">Rp {{ number_format($personel->nilai_honor ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end text-danger">Rp {{ number_format($personel->pph ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold text-success">Rp {{ number_format($personel->netto ?? 0, 0, ',', '.') }}</td>
                                        <td>
                                            @if($personel->rekening)
                                                <div class="fw-semibold">{{ $personel->jenis_bank ?? 'Bank' }}</div>
                                                <div class="font-monospace text-dark">{{ $personel->rekening }}</div>
                                                <div class="text-muted" style="font-size: 0.75rem;">a.n. {{ $personel->nama_rekening }}</div>
                                            @else
                                                <span class="badge bg-danger">KOSONG</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">Belum ada data personel</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        {{-- D. KOLOM KANAN (Aksi & Form) --}}
        <div class="col-xl-5">
            <div class="sticky-top" style="top: 1.5rem; z-index: 1;">

                {{-- FORM PEMBUATAN DRAF NPI --}}
                @if($canEditNpi)
                    <div class="card action-card mb-4">
                        <div class="card-header bg-primary text-white p-3 rounded-top-3 border-0">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2"></i> Pendaftaran Draf NPI</h6>
                        </div>
                        <div class="card-body p-4 bg-white rounded-bottom-3">
                            <form action="{{ route('npis.honor.store', $spmModel->id) }}" method="POST">
                                @csrf
                                <div class="row g-3 mb-4">
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-dark">Nomor Bukti NPI <span class="text-danger">*</span></label>
                                        <input type="text" name="nomor_npi" class="form-control text-primary bg-light fw-bold" value="{{ old('nomor_npi', $npiModel->nomor_npi ?? $autoNomorNpi) }}" placeholder="Contoh: NPI-HONOR-001/{{ date('Y') }}" required>
                                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Nomor di atas diturunkan dari SPP, ubah jika perlu.</small>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold text-dark">Tanggal Penerbitan <span class="text-danger">*</span></label>
                                        <input type="date" name="tanggal_npi" class="form-control" value="{{ old('tanggal_npi', optional($npiModel?->tanggal_npi)->format('Y-m-d') ?? date('Y-m-d')) }}" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold text-dark">Tahun Anggaran</label>
                                        <input type="text" name="tahun_anggaran" class="form-control" value="{{ old('tahun_anggaran', $spmModel->tahun_anggaran ?? date('Y')) }}" readonly>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-dark">Bendahara Penerimaan Tujuan <span class="text-danger">*</span></label>
                                        <input type="hidden" name="bendahara_penerimaan_id" value="{{ $bendaharaPenerimaanTagihan?->id }}">
                                        <input type="text" class="form-control bg-light border-primary" value="{{ $bendaharaPenerimaanTagihan?->name ?? $tagihan?->bendahara_penerimaan_nama_snapshot ?? 'Belum ditentukan pada tagihan' }}" readonly>
                                        <small class="text-muted">Diwariskan dari verifikator Bendahara Penerimaan yang dipilih saat tagihan diajukan.</small>
                                        @if(!$bendaharaPenerimaanTagihan)
                                            <div class="text-danger small mt-1">Verifikator Bendahara Penerimaan belum ada pada tagihan sumber.</div>
                                        @endif
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-dark">Koordinator Keuangan</label>
                                        <input type="text" class="form-control bg-light" value="{{ $koordinatorKeuanganUser?->name ?? $tagihan?->koordinator_keuangan_nama_snapshot ?? 'Belum Ditentukan' }}" readonly>
                                        <small class="text-muted">Verifikator Koordinator Keuangan.</small>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-dark">Uraian / Tujuan NPI</label>
                                        <textarea name="uraian_npi" class="form-control" rows="2" placeholder="Salin tujuan NPI ke sini">{{ old('uraian_npi', $npiModel->uraian_npi ?? $tagihan?->deskripsi ?? '') }}</textarea>
                                    </div>
                                </div>
                                <button class="btn btn-outline-primary w-100 fw-bold"><i class="bi bi-save me-1"></i> SIMPAN DRAF FORMASI NPI</button>
                            </form>

                            {{-- BUTTON SUBMIT VERIFIKASI (JIKA DRAF SUDAH ADA & READY) --}}
                            @if($canSubmit)
                            <hr class="my-4 text-muted border-dashed">
                            <div>
                                @if($isReadyToSubmit)
                                    <form action="{{ route('npis.honor.submit', $spmModel->id) }}" method="POST">
                                        @csrf
                                        <button class="btn btn-success w-100 py-3 fw-bold fs-6 shadow-sm" onclick="return confirm('Mengajukan NPI Honorarium ini akan mengunci draf form NPI ini, dan segera memanggil verifikasi paralel dari Kasubbag, Bendahara Penerimaan, Koordinator Keuangan, dan PPK secara bersamaan. Lanjutkan?')">
                                            <i class="bi bi-send me-1"></i> AJUKAN UNTUK VERIFIKASI
                                        </button>
                                    </form>
                                @else
                                    <div class="alert alert-warning border-0 small mb-0">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Draf belum dapat diajukan! Harap periksa tanda silang pada Checklist Kesiapan Berkas.
                                    </div>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-3 p-4 mb-4">
                        <i class="bi bi-lock-fill fs-2 text-success opacity-75"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Draf NPI Honorarium Terkunci</h6>
                            <div class="small">Dokumen NPI telah disubmit ke Workflow Verifikasi (atau Selesai) dan *read-only*.</div>
                        </div>
                    </div>
                @endif
                
                @if(in_array($statusNpi, [\App\Models\DokumenNpi::STATUS_MENUNGGU_UPLOAD, \App\Models\DokumenNpi::STATUS_NPI_TERBIT, \App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL]))
                <div class="card action-card mb-4 border-success">
                    <div class="card-header bg-success text-white p-3 rounded-top-3 border-0">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-upload me-2"></i> Upload NPI Bertandatangan</h6>
                    </div>
                    <div class="card-body p-4 bg-white rounded-bottom-3">
                        @if($npiModel->hasSignedNpiFile())
                            <div class="alert alert-success d-flex align-items-center mb-3 border-0">
                                <i class='bi bi-check-circle fs-3 me-3'></i>
                                <div>
                                    <h6 class="alert-heading fw-bold mb-1">NPI Telah Terbit</h6>
                                    <span class="font-13">File NPI fisik bertandatangan telah diunggah dan disimpan.</span>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center mb-3 bg-light p-3 rounded">
                                <i class='bi bi-file-earmark-pdf text-danger fs-1 me-3'></i>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 fw-bold">{{ $npiModel->signedNpiArsip->nama_file_asli ?? 'Dokumen NPI' }}</h6>
                                    <small class="text-muted">Diunggah pada {{ $npiModel->signedNpiArsip->created_at->format('d M Y H:i') }}</small>
                                </div>
                                <a href="{{ Storage::url($npiModel->signedNpiArsip->path_file) }}" target="_blank" class="btn btn-primary btn-sm px-3"><i class='bi bi-download me-1'></i> Unduh File</a>
                            </div>
                            
                            <hr class="border-dashed">
                            <p class="mb-2 fw-bold font-13 text-muted">Upload Ulang File NPI Fisik (Opsional)</p>
                        @else
                            <div class="alert alert-warning d-flex align-items-center mb-4 border-0">
                                <i class='bi bi-exclamation-triangle fs-3 me-3'></i>
                                <div>
                                    <h6 class="alert-heading fw-bold mb-1">Menunggu Upload Fisik</h6>
                                    <span class="font-13">NPI telah diverifikasi penuh. Silakan cetak, tandatangani, dan unggah scan/foto dokumen NPI untuk menerbitkan NPI dan bisa digunakan sebagai dasar SP2D.</span>
                                </div>
                            </div>
                        @endif
                        
                        <form action="{{ route('npis.honor.upload-signed-npi', $npiModel->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="input-group">
                                <input type="file" class="form-control" name="file_npi_ttd" accept=".pdf,.jpg,.jpeg,.png" required>
                                <button class="btn btn-success px-4 fw-bold" type="submit"><i class='bi bi-upload me-1'></i> Unggah & Terbitkan NPI</button>
                            </div>
                            <small class="text-muted mt-2 d-block">Format: PDF/JPG/PNG. Maks: 10MB.</small>
                            @error('file_npi_ttd')
                                <span class="text-danger small mt-1 d-block"><i class='bi bi-x-circle'></i> {{ $message }}</span>
                            @enderror
                        </form>
                    </div>
                </div>
                @endif
                
                {{-- Aktivitas Workflow --}}
                <div class="card npi-section-card mb-4 border-0">
                    <div class="card-body p-4 bg-white">
                        <div class="npi-section-heading text-muted"><i class="bi bi-clock-history"></i> Log Perubahan / Transaksi NPI</div>
                        <div class="mt-3" style="max-height: 350px; overflow-y: auto;">
                            @forelse($recentActivities as $idx => $activity)
                                <div class="npi-activity-row {{ $idx === 0 ? 'npi-activity-active' : '' }}">
                                    <div class="fw-bold text-dark">{{ $activity['title'] }}</div>
                                    <div class="small text-muted">{{ $activity['time'] }} &bull; <span class="fw-semibold text-secondary">{{ $activity['actor'] }}</span></div>
                                    @if(!empty($activity['note']))
                                        <div class="small text-muted mt-1 lh-sm p-2 bg-light rounded text-wrap fst-italic">"{{ $activity['note'] }}"</div>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center text-muted small py-3">Belum ada aktivitas.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
