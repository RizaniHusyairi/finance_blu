@extends('layouts.app')
@section('title', 'Detail Verifikasi SPM Honorarium')

@php
    $statusSpmClass = match ($spmModel->status) {
        'Belum Dibuat' => 'bg-secondary',
        'DRAFT' => 'bg-warning text-dark',
        'Menunggu Verifikasi' => 'bg-info',
        'Revisi' => 'bg-danger',
        'Disetujui Final' => 'bg-success',
        default => 'bg-secondary',
    };

    $ppspmStatusLabel = $ppspmApproval?->status ?? 'Belum diajukan';
    $kasubbagStatusLabel = $kasubbagApproval?->status ?? 'Belum diajukan';
    $koordinatorStatusLabel = $koordinatorApproval?->status ?? 'Belum diajukan';
    $ppspmStatusClass = match($ppspmStatusLabel) {
        'APPROVED' => 'text-success', 'PENDING' => 'text-warning', 'REVISION','REJECTED' => 'text-danger', default => 'text-muted'
    };
    $kasubbagStatusClass = match($kasubbagStatusLabel) {
        'APPROVED' => 'text-success', 'PENDING' => 'text-warning', 'REVISION','REJECTED' => 'text-danger', default => 'text-muted'
    };
    $koordinatorStatusClass = match($koordinatorStatusLabel) {
        'APPROVED' => 'text-success', 'PENDING' => 'text-warning', 'REVISION','REJECTED' => 'text-danger', default => 'text-muted'
    };

    $documentStatusMeta = [
        'ready' => ['label' => 'Tersedia', 'class' => 'bg-success'],
        'missing' => ['label' => 'Belum Ada', 'class' => 'bg-danger'],
        'not_required' => ['label' => 'Tidak Wajib', 'class' => 'bg-secondary'],
    ];

    $progressStep = 2; // Default verifikasi berjalan
    if ($spmModel->status === \App\Models\DokumenSpm::STATUS_DISETUJUI_FINAL) $progressStep = 4;
@endphp

@push('css')
    <style>
        .spm-workspace-hero { background: linear-gradient(135deg, #f0f4ff, #f1f5f9); border-bottom: 1px solid rgba(15,23,42,.08); padding-bottom: 1.5rem; margin-bottom: 2rem; position: relative; }
        .spm-workspace-hero::before { content: ""; position: absolute; left: 0; top: 0; width: 4px; height: 100%; background: #6366f1; }
        .spm-summary-tile { background: #fff; border: 1px solid rgba(15,23,42,.08); border-radius: .75rem; padding: 1rem; height: 100%; }
        .spm-summary-tile .label { color: #6c757d; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; margin-bottom: .35rem; }
        .spm-summary-tile .value { color: #212529; font-weight: 700; line-height: 1.35; }
        .spm-section-card { border: 0; border-radius: 1rem; box-shadow: 0 .125rem .25rem rgba(15,23,42,.04); border: 1px solid rgba(15,23,42,.08); overflow: hidden; background: #fff; }
        .spm-section-heading { color: #475569; font-size: .8rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; margin-bottom: 1.2rem; display: flex; align-items: center; gap: .5rem; }
        .spm-info-block { margin-bottom: 1.2rem; }
        .spm-info-block .label { color: #64748b; font-size: .8rem; margin-bottom: .25rem; }
        .spm-info-block .value { font-weight: 600; color: #1e293b; line-height: 1.4; }
        .spm-readiness-item { display: flex; align-items: flex-start; gap: .85rem; padding: .65rem 0; border-bottom: 1px solid rgba(15,23,42,.04); }
        .spm-readiness-item:last-child { border-bottom: 0; }
        .spm-readiness-icon { width: 1.5rem; height: 1.5rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-size: .75rem; flex-shrink: 0; }
        .spm-icon-ready { background: rgba(25,135,84,.12); color: #198754; }
        .spm-icon-missing { background: rgba(220,53,69,.12); color: #dc3545; }
        .spm-doc-row { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: .85rem 0; border-bottom: 1px solid rgba(15,23,42,.04); }
        .spm-doc-row:last-child { border-bottom: 0; }
        .spm-activity-row { position: relative; padding-left: 1.25rem; margin-bottom: 1.25rem; }
        .spm-activity-row:last-child { margin-bottom: 0; }
        .spm-activity-row::before { content: ""; position: absolute; left: 0; top: .35rem; width: .5rem; height: .5rem; border-radius: 999px; background: #cbd5e1; }
        .spm-activity-active::before { background: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.2); }

        .timeline-wrapper { display: flex; align-items: center; justify-content: space-between; position: relative; padding: 2rem 0; }
        .timeline-line { position: absolute; top: 3.25rem; left: 10%; right: 10%; height: 3px; background: #e2e8f0; z-index: 1; }
        .timeline-step { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; text-align: center; flex: 1; }
        .timeline-icon { width: 44px; height: 44px; border-radius: 999px; background: #fff; border: 3px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #94a3b8; font-weight: bold; margin-bottom: .75rem; transition: all .2s; }
        .timeline-label { font-weight: 600; color: #475569; font-size: .85rem; line-height: 1.3; }
        .timeline-sub { font-size: .75rem; color: #94a3b8; margin-top: .25rem; max-width: 150px; }
        .timeline-step.passed .timeline-icon { border-color: #10b981; background: #10b981; color: #fff; }
        .timeline-step.active .timeline-icon { border-color: #6366f1; color: #6366f1; box-shadow: 0 0 0 4px rgba(99,102,241,.2); }
        .timeline-step.revision .timeline-icon { border-color: #ef4444; color: #ef4444; background: #fee2e2; }

        .action-card { border: 2px solid #0d6efd; background: #f8fbff; border-radius: 1rem; box-shadow: 0 .25rem 1rem rgba(13,110,253,.15); }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <x-page-title title="Workspace Verifikator" subtitle="Detail SPM Honorarium" />
        <a href="{{ route('verifikasi-spm.honor.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali ke Antrean</a>
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
    <div class="spm-workspace-hero p-4 rounded-3 shadow-sm">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
            <div class="flex-grow-1">
                <h3 class="fw-bold mb-2 text-dark">{{ $tagihan?->deskripsi ?? 'SPM Honorarium' }}</h3>
                <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
                    <span class="badge {{ $statusSpmClass }} px-3 py-2">SPM: {{ $spmModel->status }}</span>
                    <span class="badge bg-light text-dark px-3 py-2 border"><i class="bi bi-person-badge"></i> Saya: {{ $roleCode }}</span>
                    @if($isMyPendingApproval)
                        <span class="badge bg-warning text-dark px-3 py-2 animate__animated animate__pulse animate__infinite"><i class="bi bi-bell-fill me-1"></i> Menunggu Aksi Anda</span>
                    @endif
                </div>
                <div class="row g-3">
                    <div class="col-md-3 col-6"><div class="spm-summary-tile"><div class="label">Nomor SPM</div><div class="value">{{ $spmModel?->nomor_spm ?? '-' }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="spm-summary-tile"><div class="label">Nomor SPP</div><div class="value">{{ $sppModel->nomor_spp ?? '-' }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="spm-summary-tile"><div class="label">Pembuat SPM</div><div class="value">{{ $spmModel->dibuatOleh?->name ?? 'Operator' }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="spm-summary-tile"><div class="label">Nilai Pembayaran</div><div class="value text-success fs-6">Rp {{ number_format($nominalSpm, 0, ',', '.') }}</div></div></div>
                </div>
            </div>

            <div class="d-flex flex-column justify-content-center gap-2" style="min-width: 200px;">
                <a href="{{ route('spms.cetak-pdf', $spmModel->id) }}" target="_blank" class="btn btn-danger shadow-sm"><i class="bi bi-file-earmark-pdf me-1"></i> Buka / Cetak PDF SPM</a>
            </div>
        </div>
    </div>

    {{-- B. PANEL STATUS & KESIAPAN --}}
    <div class="card spm-section-card mb-4 border-info border-opacity-25" style="background-color: #f8f9ff;">
        <div class="card-body p-4">
            <h5 class="fw-bold text-info-emphasis mb-4"><i class="bi bi-shield-check me-2"></i> Progress Workflow & Pengecekan Sistem</h5>
            <div class="row g-4 align-items-center">
                <div class="col-xl-5">
                    <div class="bg-white p-3 rounded-3 border shadow-sm h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Checklist Validasi</h6>
                        </div>
                        <div style="font-size: 0.9rem;">
                            @foreach($readinessChecklist as $item)
                                <div class="spm-readiness-item py-1">
                                    <span class="spm-readiness-icon {{ $item['status'] === 'ready' ? 'spm-icon-ready' : 'spm-icon-missing' }}"><i class="bi {{ $item['status'] === 'ready' ? 'bi-check2' : 'bi-x-lg' }}"></i></span>
                                    <div>{{ $item['label'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-xl-7">
                    <div class="timeline-wrapper pt-0">
                        <div class="timeline-line"></div>
                        {{-- Step 1: Draft --}}
                        <div class="timeline-step passed">
                            <div class="timeline-icon"><i class="bi bi-file-earmark-text"></i></div>
                            <div class="timeline-label">Draft Dibuat</div>
                            <div class="timeline-sub">{{ $spmModel->dibuatOleh?->name ?? 'Operator BLU' }}</div>
                        </div>
                        {{-- Step 2: Verifikasi PPSPM --}}
                        <div class="timeline-step {{ $ppspmApproval?->status === 'APPROVED' ? 'passed' : ($ppspmApproval?->status === 'REVISION' ? 'revision' : ($progressStep == 2 ? 'active' : '')) }}">
                            <div class="timeline-icon"><i class="bi bi-person-check"></i></div>
                            <div class="timeline-label">Verifikasi PPSPM</div>
                            <div class="timeline-sub fw-semibold {{ $ppspmStatusClass }}">{{ $ppspmStatusLabel }}</div>
                            @if($ppspmApproval) <div class="timeline-sub mt-0 opacity-75" style="font-size: 0.7rem;">{{ $ppspmApproval->assignedUser?->name ?? 'All Ppspm' }}</div> @endif
                        </div>
                        {{-- Step 2: Verifikasi Koordinator Keuangan --}}
                        <div class="timeline-step {{ $koordinatorApproval?->status === 'APPROVED' ? 'passed' : ($koordinatorApproval?->status === 'REVISION' ? 'revision' : ($progressStep == 2 ? 'active' : '')) }}">
                            <div class="timeline-icon"><i class="bi bi-person-gear"></i></div>
                            <div class="timeline-label">Verifikasi Koordinator Keuangan</div>
                            <div class="timeline-sub fw-semibold {{ $koordinatorStatusClass }}">{{ $koordinatorStatusLabel }}</div>
                            @if($koordinatorApproval) <div class="timeline-sub mt-0 opacity-75" style="font-size: 0.7rem;">{{ $koordinatorApproval->assignedUser?->name ?? 'Koordinator' }}</div> @endif
                        </div>
                        {{-- Step 2: Verifikasi Kasubbag --}}
                        <div class="timeline-step {{ $kasubbagApproval?->status === 'APPROVED' ? 'passed' : ($kasubbagApproval?->status === 'REVISION' ? 'revision' : ($progressStep == 2 ? 'active' : '')) }}">
                            <div class="timeline-icon"><i class="bi bi-person-badge"></i></div>
                            <div class="timeline-label">Verifikasi Kasubbag</div>
                            <div class="timeline-sub fw-semibold {{ $kasubbagStatusClass }}">{{ $kasubbagStatusLabel }}</div>
                            @if($kasubbagApproval) <div class="timeline-sub mt-0 opacity-75" style="font-size: 0.7rem;">{{ $kasubbagApproval->assignedUser?->name ?? 'Kasubbag' }}</div> @endif
                        </div>
                        {{-- Step 3: Final --}}
                        <div class="timeline-step {{ $progressStep >= 4 ? 'passed' : '' }}">
                            <div class="timeline-icon"><i class="bi bi-check-all"></i></div>
                            <div class="timeline-label">Selesai</div>
                            <div class="timeline-sub">SPM Disetujui Final</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- C. KOLOM KIRI (SUMBER DATA) --}}
        <div class="col-xl-7">
            {{-- Ringkasan Honorarium --}}
            <div class="card spm-section-card mb-4">
                <div class="card-body p-4">
                    <div class="spm-section-heading text-primary"><i class="bi bi-receipt"></i> 1. Ringkasan Honorarium</div>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="spm-info-block"><div class="label">Nomor Tagihan Honorarium</div><div class="value">{{ $tagihan?->nomor_tagihan ?? '-' }}</div></div></div>
                        <div class="col-md-6"><div class="spm-info-block"><div class="label">Uraian / Deskripsi</div><div class="value">{{ $tagihan?->deskripsi ?? '-' }}</div></div></div>
                        <div class="col-md-4"><div class="spm-info-block"><div class="label">Nilai Bruto</div><div class="value">Rp {{ number_format($tagihan?->total_bruto ?? 0, 0, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="spm-info-block"><div class="label">Potongan Pajak</div><div class="value text-danger">Rp {{ number_format($tagihan?->total_potongan ?? 0, 0, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="spm-info-block"><div class="label">Nilai Netto (Cair)</div><div class="value text-success fs-5">Rp {{ number_format($tagihan?->total_netto ?? 0, 0, ',', '.') }}</div></div></div>
                        <div class="col-md-6"><div class="spm-info-block"><div class="label">Status SPP Asli</div><div class="value"><span class="badge bg-success">{{ str_replace('_', ' ', $sppModel->status) }}</span></div></div></div>
                        <div class="col-md-6"><div class="spm-info-block"><div class="label">PPK Pemrakarsa</div><div class="value"><i class="bi bi-person-badge text-muted me-1"></i> {{ $sppModel?->ppkVerifikator?->name ?? '-' }}</div></div></div>
                    </div>
                </div>
            </div>

            {{-- Anggaran & Akun --}}
            <div class="card spm-section-card mb-4">
                <div class="card-body p-4">
                    <div class="spm-section-heading text-primary"><i class="bi bi-wallet2"></i> 2. Anggaran & Pembebanan Akun</div>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="spm-info-block"><div class="label">DIPA / Tahun / Revisi</div><div class="value">{{ $dipa?->nomor_dipa ?? '-' }} <span class="text-muted fw-normal">(Thn: {{ $dipa?->tahun_anggaran ?? '-' }}, Rev: {{ $dipa?->revisi_aktif_ke ?? '-' }})</span></div></div></div>
                        <div class="col-md-6">
                            <div class="p-2 bg-light rounded border border-primary border-opacity-25">
                                <div class="label text-primary fw-bold small mb-1">Mata Anggaran (COA)</div>
                                <div class="value fs-6">@if($selectedBudgetItem?->coa) {{ $selectedBudgetItem->coa->kode_mak_lengkap }} @else <span class="text-danger">Belum Tersedia</span> @endif</div>
                                <div class="text-muted small mt-1">{{ optional($selectedBudgetItem?->coa)->nama_akun ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Dokter/Penerima Honor --}}
            <div class="card spm-section-card mb-4">
                <div class="card-body p-4">
                    <div class="spm-section-heading text-primary d-flex justify-content-between align-items-center">
                        <div><i class="bi bi-people"></i> 3. Penerima Honor & Rekening Bank</div>
                        <span class="badge bg-light text-dark border">{{ count($tagihan?->detailHonorarium ?? []) }} Personel</span>
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
                                @forelse($tagihan->detailHonorarium as $personel)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-primary">{{ $personel->nama_personel }}</div>
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

            {{-- Dokumen Pendukung --}}
            <div class="card spm-section-card mb-4">
                <div class="card-body p-4">
                    <div class="spm-section-heading text-primary"><i class="bi bi-paperclip"></i> 4. Dokumen Pendukung Fisik</div>
                    @foreach($documentStatuses as $document)
                        @php($docMeta = $documentStatusMeta[$document['status']] ?? $documentStatusMeta['missing'])
                        <div class="spm-doc-row">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge {{ $docMeta['class'] }}" style="width: 80px;">{{ $docMeta['label'] }}</span>
                                <div class="fw-semibold text-dark">{{ $document['label'] }}</div>
                            </div>
                            <div>
                                @if($document['is_available'])
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($document['path']) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-search me-1"></i> Lihat</a>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- D. KOLOM KANAN --}}
        <div class="col-xl-5">
            <div class="sticky-top" style="top: 1.5rem; z-index: 1;">

                {{-- PANEL AKSI VERIFIKATOR JIKA PENDING --}}
                @if($isMyPendingApproval && !empty($activeRoleApprovals))
                    @foreach($activeRoleApprovals as $approvalData)
                    <div class="card action-card mb-4">
                        <div class="card-header bg-primary text-white p-3 rounded-top-3 border-0">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-shield-lock me-2"></i> Papan Keputusan ({{ $approvalData['role'] }})</h6>
                        </div>
                        <div class="card-body p-4 bg-white rounded-bottom-3">
                            <div class="alert alert-light border small text-dark mb-4">
                                Teliti Draf Form SPM dan kelengkapan dokumen yang diajukan oleh Opertor. Pastikan nilai Netto Cair sama persis dengan angka SPM.
                            </div>
                            
                            {{-- Button Setujui --}}
                            <form action="{{ $approvalData['approveRoute'] }}" method="POST" class="mb-3" id="formVerifyApprove_{{ Str::slug($approvalData['role']) }}">
                                @csrf
                                <input type="hidden" name="approval_id" value="{{ $approvalData['approval_id'] }}">
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-muted">Catatan Persetujuan (Opsional)</label>
                                    <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Contoh: Dokumen lengkap dan valid. Ok.">
                                </div>
                                <button type="submit" class="btn btn-success w-100 py-2 fs-6 fw-bold shadow-sm" onclick="return confirm('Apakah Anda yakin menyetujui dokumen SPM Honorarium ini sebagai {{ $approvalData['role'] }}?')">
                                    <i class="bi bi-check-circle-fill me-1"></i> SETUJUI SEBAGAI {{ strtoupper($approvalData['role']) }}
                                </button>
                            </form>
                            
                            <hr class="my-4 text-muted border-dashed">
                            
                            <div class="fw-bold text-danger mb-2 small"><i class="bi bi-arrow-return-left me-1"></i> Terdapat Kesalahan Data?</div>
                            {{-- Form Tolak --}}
                            <form action="{{ $approvalData['revisiRoute'] }}" method="POST" id="formVerifyReject_{{ Str::slug($approvalData['role']) }}">
                                @csrf
                                <input type="hidden" name="approval_id" value="{{ $approvalData['approval_id'] }}">
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Catatan Revisi <span class="text-danger">*</span></label>
                                    <textarea name="catatan" rows="2" class="form-control form-control-sm border-danger" required placeholder="Tulis instruksi revisi sejelas mungkin untuk Operator..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-outline-danger w-100 fw-bold" onclick="return confirm('Kembalikan dokumen ini dalam mode Revisi ke Operator BLU sebagai {{ $approvalData['role'] }}?')">
                                    TOLAK & MINTA REVISI
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="alert alert-secondary border-0 shadow-sm d-flex align-items-center gap-3 p-4 mb-4">
                        <i class="bi bi-info-circle-fill fs-2 text-secondary opacity-50"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Tidak Ada Aksi Verifikasi</h6>
                            <div class="small">Dokumen tidak memerlukan tindakan verifikasi dari posisi Anda saat ini. Status persetujuan Anda: <strong class="{{ $myApprovalClass ?? 'text-secondary' }}">{{ $myApproval?->status ?? 'Tidak Terlibat' }}</strong></div>
                        </div>
                    </div>
                @endif

                {{-- Ringkasan Draft SPM --}}
                <div class="card spm-section-card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-light p-3">
                        <h6 class="mb-0 fw-bold text-secondary"><i class="bi bi-card-text me-2"></i> Ringkasan Draf SPM (Input Operator)</h6>
                    </div>
                    <div class="card-body p-4 bg-white">
                        <div class="spm-info-block mb-2"><div class="label">Nomor SPM</div><div class="value">{{ $spmModel?->nomor_spm ?? '-' }}</div></div>
                        <div class="spm-info-block mb-2"><div class="label">Tanggal SPM</div><div class="value">{{ optional($spmModel?->tanggal_spm)->format('d F Y') ?? '-' }}</div></div>
                        <div class="spm-info-block mb-2"><div class="label">Tahun Anggaran</div><div class="value">{{ $spmModel?->tahun_anggaran ?? date('Y') }}</div></div>
                        <div class="spm-info-block mb-2"><div class="label">Jenis Tagihan</div><div class="value">{{ $spmModel?->jenis_tagihan ?? 'NON REMUNERASI' }}</div></div>
                        <div class="spm-info-block mb-2"><div class="label">Cara Bayar</div><div class="value">{{ $spmModel?->cara_bayar ?? 'SP2D BLU' }}</div></div>
                        <div class="spm-info-block mb-0"><div class="label">Nilai Nominal SPM</div><div class="value text-primary fw-bold fs-5">Rp {{ number_format($nominalSpm, 0, ',', '.') }}</div></div>
                    </div>
                </div>

                {{-- Aktivitas Workflow --}}
                <div class="card spm-section-card mb-4">
                    <div class="card-body p-4 bg-white">
                        <div class="spm-section-heading text-primary"><i class="bi bi-clock-history"></i> Log Aktivitas Dokumen</div>
                        <div class="mt-3" style="max-height: 400px; overflow-y: auto;">
                            @forelse($activities as $idx => $activity)
                                <div class="spm-activity-row {{ $idx === 0 ? 'spm-activity-active' : '' }}">
                                    <div class="fw-bold text-dark">{{ str_replace('_', ' ', $activity->aksi) }}</div>
                                    <div class="small text-muted">{{ optional($activity->created_at)->format('d M Y, H:i') }} &bull; <span class="fw-semibold text-secondary">{{ $activity->user?->name ?? 'System' }}</span> ({{ $activity->role_saat_itu }})</div>
                                    @if(!empty($activity->catatan))
                                        <div class="small text-muted mt-1 lh-sm p-2 bg-light rounded text-wrap fst-italic">"{{ $activity->catatan }}"</div>
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
