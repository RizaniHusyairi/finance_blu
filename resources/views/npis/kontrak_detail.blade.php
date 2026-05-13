@extends('layouts.app')

@push('css')
<style>
    /* === NPI Workspace Styles === */
    .npi-hero { border-top: 3px solid var(--bs-primary); }
    .npi-hero .hero-meta {
        background: rgba(13,110,253,.04);
        border: 1px solid rgba(13,110,253,.08);
    }
    .npi-status-pill {
        display: inline-flex; align-items: center; gap: .45rem;
        padding: .35rem .85rem; font-size: .72rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .04em;
        border-radius: 50rem; line-height: 1;
    }
    .npi-status-pill::before {
        content: ''; width: 8px; height: 8px; border-radius: 50%;
        background: currentColor;
    }
    .npi-status-pill.s-draft  { background: rgba(108,117,125,.12); color: #495057; }
    .npi-status-pill.s-wait   { background: rgba(13,110,253,.12);  color: #0d6efd; }
    .npi-status-pill.s-revisi { background: rgba(220,53,69,.12);   color: #dc3545; }
    .npi-status-pill.s-final  { background: rgba(25,135,84,.12);   color: #198754; }

    .npi-card {
        background: #fff; border: 1px solid #eef0f4; border-radius: .75rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
    }
    .npi-card .npi-card-head {
        padding: 1rem 1.15rem .55rem;
        display: flex; align-items: center; gap: .55rem;
    }
    .npi-card .npi-card-head h6 {
        margin: 0; font-size: .78rem; letter-spacing: .04em;
        text-transform: uppercase; font-weight: 700; color: #0f172a;
    }
    .npi-card .npi-card-head i { font-size: 1.15rem; color: var(--bs-primary); }
    .npi-card .npi-card-body { padding: .35rem 1.15rem 1.15rem; }

    .field-label {
        font-size: .7rem; font-weight: 600; text-transform: uppercase;
        letter-spacing: .04em; color: #6b7280; margin-bottom: .15rem;
        display: block;
    }
    .field-value { font-weight: 600; color: #0f172a; }

    .doc-row { padding: .55rem .8rem; border-radius: .55rem; }
    .doc-row + .doc-row { margin-top: .3rem; }
    .doc-row.is-ready    { background: rgba(25,135,84,.06); }
    .doc-row.is-missing  { background: rgba(220,53,69,.06); }
    .doc-row.is-optional { background: rgba(108,117,125,.05); }

    .approval-row {
        border: 1px solid #eef0f4; border-left-width: 4px;
        border-radius: .55rem; padding: .8rem 1rem; background: #fff;
    }
    .approval-row.is-approved { border-left-color: #198754; background: rgba(25,135,84,.04); }
    .approval-row.is-revision { border-left-color: #dc3545; background: rgba(220,53,69,.04); }
    .approval-row.is-pending  { border-left-color: #ffc107; background: rgba(255,193,7,.05); }
    .approval-row.is-waiting  { border-left-color: #cbd5e1; }

    .ready-list { list-style: none; padding: 0; margin: 0; }
    .ready-list li {
        display: flex; align-items: flex-start; gap: .55rem;
        padding: .45rem 0; font-size: .82rem;
        border-bottom: 1px dashed rgba(15,23,42,.07);
    }
    .ready-list li:last-child { border-bottom: 0; }
    .ready-list .ico {
        width: 22px; height: 22px; flex: 0 0 22px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .9rem; margin-top: 1px;
    }
    .ready-list .ico.ok { background: rgba(25,135,84,.12); color: #198754; }
    .ready-list .ico.no { background: rgba(220,53,69,.12); color: #dc3545; }

    .npi-timeline-item {
        position: relative; padding-left: 1.4rem;
        padding-bottom: 1rem; border-left: 2px solid #eef0f4;
    }
    .npi-timeline-item:last-child { padding-bottom: 0; }
    .npi-timeline-item::before {
        content: ''; position: absolute; left: -7px; top: 4px;
        width: 12px; height: 12px; border-radius: 50%;
        background: #fff; border: 2px solid #94a3b8;
    }
    .npi-timeline-item.is-active::before {
        border-color: var(--bs-primary); background: var(--bs-primary);
    }

    .npi-min-w-0 { min-width: 0; }

    /* Sticky action panel: aktif hanya di desktop xl+ */
    .npi-sticky-action { position: static; }
    @media (min-width: 1200px) {
        .npi-sticky-action { position: sticky; top: 88px; }
    }
</style>
@endpush

@section('content')
@php
    $finalStatuses = [
        \App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL,
        \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG,
        \App\Models\DokumenNpi::STATUS_NPI_TERBIT,
    ];

    if (in_array($statusNpi, $finalStatuses, true)) {
        $statusKey = 's-final';
    } elseif ($statusNpi === \App\Models\DokumenNpi::STATUS_REVISI) {
        $statusKey = 's-revisi';
    } elseif (empty($statusNpi) || in_array($statusNpi, [\App\Models\DokumenNpi::STATUS_DRAFT, 'Belum Dibuat', ''], true)) {
        $statusKey = 's-draft';
    } else {
        $statusKey = 's-wait';
    }

    $totalKotor    = $tagihan?->total_kotor ?? 0;
    $totalNetto    = $tagihan?->total_netto ?? 0;
    $potonganTotal = $totalKotor - $totalNetto;

    $readyCount  = collect($readinessChecklist)->where('status', 'ready')->count();
    $totalReady  = is_countable($readinessChecklist) ? count($readinessChecklist) : 0;

    $showUploadCard   = in_array($statusNpi, [
        \App\Models\DokumenNpi::STATUS_MENUNGGU_UPLOAD,
        \App\Models\DokumenNpi::STATUS_NPI_TERBIT,
        \App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL,
    ], true);
    $showProgressCard = !in_array($statusNpi, ['DRAFT', 'Belum Dibuat', ''], true);
@endphp

<!-- ============== HERO / WORKSPACE HEADER ============== -->
<div class="card npi-hero radius-10 border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3">
            <div class="npi-min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="npi-status-pill {{ $statusKey }}">{{ $statusNpi }}</span>
                    <span class="text-muted small fw-semibold text-uppercase">Penyusunan NPI Kontrak</span>
                </div>
                <h4 class="fw-bold mb-1 text-dark text-break">
                    {{ $npiModel->nomor_npi ?? 'NPI Belum Tersimpan' }}
                </h4>
                <div class="text-muted small text-break">
                    <i class='bx bx-briefcase-alt me-1'></i>{{ $kontrak?->nama_pekerjaan ?? '-' }}
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                <a href="{{ route('npis.kontrak.index') }}" class="btn btn-outline-secondary">
                    <i class='bx bx-arrow-back'></i> Kembali
                </a>
                @if($npiModel && !in_array($npiModel->status, [\App\Models\DokumenNpi::STATUS_DRAFT, \App\Models\DokumenNpi::STATUS_REVISI, '']))
                    <a href="{{ route('npis.cetak-pdf', $npiModel->id) }}" target="_blank" class="btn btn-outline-danger">
                        <i class='bx bxs-file-pdf'></i> Cetak PDF
                    </a>
                @endif
                @if($statusNpi === 'DISETUJUI_FINAL')
                    <a href="{{ route('sp2ds.kontrak.detail', $npiModel->id) }}" class="btn btn-success">
                        <i class='bx bx-receipt'></i> Buat SP2D
                    </a>
                @endif
            </div>
        </div>

        <div class="hero-meta rounded-3 p-3">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <span class="field-label">Nomor SPM</span>
                    <div class="field-value text-break">{{ $spmModel->nomor_spm }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <span class="field-label">Vendor</span>
                    <div class="field-value text-break">{{ $vendor?->nama_pihak ?? '-' }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <span class="field-label">Potongan</span>
                    <div class="field-value text-danger">Rp {{ number_format($potonganTotal, 0, ',', '.') }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <span class="field-label">Nilai NPI (Netto)</span>
                    <div class="fs-5 fw-bold text-primary">Rp {{ number_format($nominalNpi, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- ============== KOLOM KIRI: KONTEKS ============== -->
    <div class="col-12 col-xl-4">
        <!-- Dokumen Kontrak -->
        <div class="npi-card mb-3">
            <div class="npi-card-head"><i class='bx bx-file'></i><h6>Dokumen Kontrak</h6></div>
            <div class="npi-card-body">
                <div class="mb-3">
                    <span class="field-label">Nomor SPK</span>
                    <div class="field-value text-break">{{ $kontrak?->nomor_spk ?? '-' }}</div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <span class="field-label">Termin</span>
                        <div class="field-value">
                            <span class="badge bg-secondary me-1">{{ $termin?->termin ?? '-' }}</span>
                            <span class="small fw-normal">{{ $termin?->jenis_termin ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <span class="field-label">Nomor SPP</span>
                        <div class="field-value small text-break">{{ $sppModel->nomor_spp }}</div>
                    </div>
                </div>
                <div class="mb-3">
                    <span class="field-label">Mata Anggaran (COA)</span>
                    <div class="field-value">
                        @if($kontrak?->dipaRevisionItem?->coa)
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle me-1">
                                {{ $kontrak->dipaRevisionItem->coa->kode_mak_lengkap }}
                            </span>
                            <span class="small fw-normal">{{ $kontrak->dipaRevisionItem->coa->nama_akun }}</span>
                        @else
                            <span class="text-muted fst-italic">-</span>
                        @endif
                    </div>
                </div>

                @php
                    $kontrakDocs = [
                        ['label' => 'BAPP', 'no' => $detailKontrak?->nomor_bapp, 'tgl' => $detailKontrak?->tanggal_bapp],
                        ['label' => 'BAP',  'no' => $detailKontrak?->nomor_bap,  'tgl' => $detailKontrak?->tanggal_bap],
                        ['label' => 'BAST', 'no' => $detailKontrak?->nomor_bast, 'tgl' => $detailKontrak?->tanggal_bast],
                    ];
                @endphp
                <div class="bg-light rounded-3 p-3">
                    @foreach($kontrakDocs as $i => $doc)
                        <div class="d-flex justify-content-between align-items-start gap-2 {{ $i < count($kontrakDocs)-1 ? 'pb-2 mb-2 border-bottom' : '' }}">
                            <div class="flex-grow-1 npi-min-w-0">
                                <span class="field-label">Nomor {{ $doc['label'] }}</span>
                                <div class="field-value small text-break">{{ $doc['no'] ?? '-' }}</div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <span class="field-label">Tanggal</span>
                                <div class="field-value small">{{ $doc['tgl']?->format('d M Y') ?? '-' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Vendor & Rekening -->
        <div class="npi-card mb-3">
            <div class="npi-card-head"><i class='bx bx-building-house'></i><h6>Vendor & Rekening</h6></div>
            <div class="npi-card-body">
                <div class="mb-3">
                    <span class="field-label">Nama Vendor</span>
                    <div class="field-value text-break">{{ $vendor?->nama_pihak ?? 'Vendor Tidak Ditemukan' }}</div>
                </div>
                <div class="mb-3">
                    <span class="field-label">NPWP</span>
                    <div class="field-value">{{ $vendor?->npwp ?? '-' }}</div>
                </div>

                <div class="bg-light border rounded-3 p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="field-label mb-0">Rekening Tujuan</span>
                        @if($rekeningReady)
                            <span class="badge bg-success bg-opacity-10 text-success"><i class='bx bx-check'></i> Siap</span>
                        @else
                            <span class="badge bg-danger bg-opacity-10 text-danger"><i class='bx bx-x'></i> Belum Lengkap</span>
                        @endif
                    </div>
                    <div class="mb-2">
                        <span class="text-muted font-12 d-block">Bank</span>
                        <strong>{{ $rekening?->nama_bank ?? '-' }}</strong>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted font-12 d-block">Nomor Rekening</span>
                        <strong class="font-monospace">{{ $rekening?->nomor_rekening ?? '-' }}</strong>
                    </div>
                    <div>
                        <span class="text-muted font-12 d-block">Atas Nama</span>
                        <strong>{{ $rekening?->nama_rekening ?? '-' }}</strong>
                    </div>
                </div>

                @if(!$rekeningReady)
                    <div class="alert alert-danger border-0 d-flex align-items-start gap-2 mb-0 p-3">
                        <i class='bx bx-error-circle fs-4'></i>
                        <span class="font-13">
                            Rekening vendor belum lengkap di Master Data.
                            Pengajuan NPI akan <strong>diblokir</strong>.
                        </span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Kelengkapan Dokumen -->
        <div class="npi-card mb-3">
            <div class="npi-card-head"><i class='bx bx-folder-open'></i><h6>Kelengkapan Dokumen Pendukung</h6></div>
            <div class="npi-card-body">
                @foreach($documentStatuses as $doc)
                    @php
                        $rowCls = $doc['status'] === 'ready'
                            ? 'is-ready'
                            : ($doc['status'] === 'missing' ? 'is-missing' : 'is-optional');
                    @endphp
                    <div class="doc-row {{ $rowCls }} d-flex align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2 npi-min-w-0">
                            @if($doc['status'] === 'ready')
                                <i class='bx bx-check-circle text-success fs-5 flex-shrink-0'></i>
                            @elseif($doc['status'] === 'missing')
                                <i class='bx bx-x-circle text-danger fs-5 flex-shrink-0'></i>
                            @else
                                <i class='bx bx-minus-circle text-muted fs-5 flex-shrink-0'></i>
                            @endif
                            <div class="npi-min-w-0">
                                <div class="fw-semibold font-13 text-truncate">{{ $doc['label'] }}</div>
                                @if(!$doc['required'])
                                    <small class="text-muted font-11">Opsional</small>
                                @endif
                            </div>
                        </div>
                        @if($doc['status'] === 'ready' && is_string($doc['path']))
                            <a href="{{ filter_var($doc['path'], FILTER_VALIDATE_URL) ? $doc['path'] : \Illuminate\Support\Facades\Storage::url($doc['path']) }}"
                               target="_blank"
                               class="btn btn-sm btn-outline-primary py-0 px-2 flex-shrink-0"
                               title="Lihat dokumen">
                                <i class='bx bx-show m-0'></i>
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- ============== KOLOM TENGAH: FORM & DETAIL ============== -->
    <div class="col-12 col-xl-5">
        <!-- Form / Detail NPI -->
        <div class="npi-card mb-3">
            <div class="npi-card-head">
                <i class='bx bx-edit'></i>
                <h6>{{ $canEditNpi ? 'Form Penyusunan NPI' : 'Parameter NPI' }}</h6>
            </div>
            <div class="npi-card-body">
                @if($canEditNpi)
                    <form action="{{ route('npis.kontrak.store', $spmModel->id) }}" method="POST" id="form-draft-npi">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold mb-1">
                                    Nomor NPI <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="nomor_npi"
                                       class="form-control fw-bold text-primary"
                                       value="{{ old('nomor_npi', $npiModel?->nomor_npi ?? $autoNomorNpi) }}" required>
                                <small class="text-muted"><i class='bx bx-info-circle'></i> Otomatis dari SPP</small>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold mb-1">
                                    Tanggal NPI <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="tanggal_npi"
                                       class="form-control"
                                       value="{{ old('tanggal_npi', $npiModel?->tanggal_npi?->format('Y-m-d') ?? date('Y-m-d')) }}" required>
                            </div>

                            <div class="col-12"><hr class="my-1 opacity-25"></div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">
                                    Bendahara Penerimaan <span class="text-danger">*</span>
                                </label>
                                <input type="hidden" name="bendahara_penerimaan_id" value="{{ $bendaharaPenerimaanTagihan?->id }}">
                                <input type="text" class="form-control bg-light"
                                       value="{{ $bendaharaPenerimaanTagihan?->name ?? $tagihan?->bendahara_penerimaan_nama_snapshot ?? 'Belum ditentukan' }}"
                                       readonly>
                                @if(!$bendaharaPenerimaanTagihan)
                                    <small class="text-danger d-block mt-1"><i class='bx bx-error-circle'></i> Verifikator belum ada pada tagihan sumber.</small>
                                @else
                                    <small class="text-muted d-block mt-1">Diwariskan dari tagihan</small>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Verifikator PPK</label>
                                <input type="text" class="form-control bg-light"
                                       value="{{ $ppkSpp?->name ?? 'Belum Ditentukan' }}" readonly>
                                <small class="text-muted d-block mt-1">Diwariskan dari SPP</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Koordinator Keuangan</label>
                                <input type="text" class="form-control bg-light"
                                       value="{{ $koordinatorKeuanganUser?->name ?? $tagihan?->koordinator_keuangan_nama_snapshot ?? 'Belum Ditentukan' }}"
                                       readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Verifikator Kasubbag</label>
                                <input type="text" class="form-control bg-light"
                                       value="{{ $kasubbagUser?->name ?? 'Belum Ditentukan' }}" readonly>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold mb-1">
                                    Uraian / Catatan <span class="text-muted fw-normal">(Opsional)</span>
                                </label>
                                <textarea name="uraian_npi" class="form-control" rows="3"
                                          placeholder="Tambahkan catatan khusus jika diperlukan...">{{ old('uraian_npi', $npiModel?->catatan) }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3 pt-3 border-top">
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-save me-1'></i> Simpan Draft NPI
                            </button>
                        </div>
                    </form>
                @else
                    <div class="row g-3">
                        <div class="col-md-7">
                            <span class="field-label">Nomor NPI</span>
                            <div class="field-value">{{ $npiModel->nomor_npi }}</div>
                        </div>
                        <div class="col-md-5">
                            <span class="field-label">Tanggal NPI</span>
                            <div class="field-value">{{ $npiModel->tanggal_npi?->format('d M Y') }}</div>
                        </div>
                        <div class="col-12"><hr class="my-1 opacity-25"></div>
                        <div class="col-md-6">
                            <span class="field-label">Bendahara Penerimaan</span>
                            <div class="field-value">{{ $npiModel->bendaharaPenerimaan?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <span class="field-label">Verifikator PPK</span>
                            <div class="field-value">{{ $ppkSpp?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <span class="field-label">Koordinator Keuangan</span>
                            <div class="field-value">{{ $koordinatorKeuanganUser?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <span class="field-label">Verifikator Kasubbag</span>
                            <div class="field-value">{{ $kasubbagUser?->name ?? '-' }}</div>
                        </div>
                        <div class="col-12">
                            <span class="field-label">Uraian / Catatan</span>
                            <div class="bg-light p-3 rounded-3 border">{{ $npiModel->catatan ?: 'Tidak ada catatan.' }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Progress Verifikasi Paralel -->
        @if($showProgressCard)
        <div class="npi-card mb-3">
            <div class="npi-card-head">
                <i class='bx bx-git-branch text-warning'></i>
                <h6>Progress Verifikasi Paralel</h6>
            </div>
            <div class="npi-card-body">
                @php
                    $approvals = [
                        ['title' => 'Bendahara Penerimaan', 'icon' => 'bx-money-withdraw', 'approval' => $benpenApproval,      'default_name' => 'Semua Bendahara Penerimaan'],
                        ['title' => 'PPK',                  'icon' => 'bx-user-pin',       'approval' => $ppkApproval,         'default_name' => 'Semua PPK'],
                        ['title' => 'Koordinator Keuangan', 'icon' => 'bx-id-card',        'approval' => $koordinatorApproval, 'default_name' => $koordinatorKeuanganUser?->name ?? 'Semua Koordinator'],
                        ['title' => 'Kasubbag',             'icon' => 'bx-shield-quarter', 'approval' => $kasubbagApproval,    'default_name' => 'Semua Kasubbag'],
                    ];
                @endphp
                <div class="d-flex flex-column gap-2">
                    @foreach($approvals as $app)
                        @php
                            $st       = $app['approval']?->status;
                            $rowCls   = match($st) {
                                'APPROVED' => 'is-approved',
                                'REVISION' => 'is-revision',
                                'PENDING'  => 'is-pending',
                                default    => 'is-waiting',
                            };
                            $badgeCls = match($st) {
                                'APPROVED' => 'bg-success',
                                'REVISION' => 'bg-danger',
                                'PENDING'  => 'bg-warning text-dark',
                                default    => 'bg-secondary',
                            };
                            $badgeLabel = $st ?? 'WAITING';
                        @endphp
                        <div class="approval-row {{ $rowCls }}">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                <div class="d-flex align-items-center gap-2 npi-min-w-0">
                                    <i class='bx {{ $app["icon"] }} fs-5 text-secondary'></i>
                                    <h6 class="mb-0 fw-bold text-truncate">{{ $app['title'] }}</h6>
                                </div>
                                <span class="badge {{ $badgeCls }}">{{ $badgeLabel }}</span>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
                                <span class="text-truncate">
                                    <i class='bx bx-user'></i>
                                    {{ $app['approval']?->assignedUser?->name ?? $app['default_name'] }}
                                </span>
                                @if($app['approval']?->acted_at)
                                    <span class="ms-auto">
                                        <i class='bx bx-time-five'></i>
                                        {{ \Carbon\Carbon::parse($app['approval']->acted_at)->format('d M Y H:i') }}
                                    </span>
                                @endif
                            </div>
                            @if($app['approval']?->catatan)
                                <div class="mt-2 p-2 bg-white border rounded-2 font-13">
                                    <strong class="d-block font-11 text-uppercase text-muted mb-1">Catatan</strong>
                                    <em>"{{ $app['approval']->catatan }}"</em>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Upload NPI Bertanda Tangan -->
        @if($showUploadCard)
        <div class="npi-card mb-3">
            <div class="npi-card-head">
                <i class='bx bx-upload text-success'></i>
                <h6>Upload NPI Bertanda Tangan</h6>
            </div>
            <div class="npi-card-body">
                @if($npiModel->hasSignedNpiFile())
                    <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-3"
                         style="background: rgba(25,135,84,.06); border: 1px solid rgba(25,135,84,.2);">
                        <i class='bx bxs-file-pdf text-danger fs-1'></i>
                        <div class="flex-grow-1 npi-min-w-0">
                            <div class="fw-bold text-truncate">{{ $npiModel->signedNpiArsip->nama_file_asli ?? 'Dokumen NPI' }}</div>
                            <small class="text-muted">Diunggah {{ $npiModel->signedNpiArsip->created_at->format('d M Y H:i') }}</small>
                        </div>
                        <a href="{{ Storage::url($npiModel->signedNpiArsip->path_file) }}" target="_blank"
                           class="btn btn-outline-primary btn-sm flex-shrink-0">
                            <i class='bx bx-download'></i> Unduh
                        </a>
                    </div>
                    <p class="small fw-semibold text-muted mb-2">Upload Ulang File NPI Fisik (Opsional)</p>
                @else
                    <div class="alert alert-warning d-flex align-items-start gap-2 mb-3">
                        <i class='bx bx-time-five fs-4'></i>
                        <div class="font-13">
                            <strong class="d-block">Menunggu unggahan fisik.</strong>
                            Cetak NPI, tandatangani, lalu unggah scan/foto sebagai dasar penerbitan NPI dan SP2D.
                        </div>
                    </div>
                @endif

                <form action="{{ route('npis.kontrak.upload-signed-npi', $npiModel->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <label class="form-label small fw-semibold mb-1">File NPI Bertanda Tangan</label>
                    <div class="input-group">
                        <input type="file" class="form-control" name="file_npi_ttd"
                               accept=".pdf,.jpg,.jpeg,.png" required>
                        <button class="btn btn-success" type="submit">
                            <i class='bx bx-upload'></i> Unggah
                        </button>
                    </div>
                    <small class="text-muted d-block mt-1">Format: PDF / JPG / PNG &middot; Maks. 10MB</small>
                    @error('file_npi_ttd')
                        <div class="text-danger small mt-1"><i class='bx bx-error-circle'></i> {{ $message }}</div>
                    @enderror
                </form>
            </div>
        </div>
        @endif

        <!-- Riwayat Aktivitas -->
        @if($recentActivities->count() > 0)
        <div class="npi-card mb-3">
            <div class="npi-card-head">
                <i class='bx bx-history text-secondary'></i>
                <h6>Riwayat Aktivitas</h6>
            </div>
            <div class="npi-card-body">
                @foreach($recentActivities as $idx => $act)
                    <div class="npi-timeline-item {{ $idx === 0 ? 'is-active' : '' }}">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <h6 class="mb-0 fw-bold font-14 text-break">{{ $act['title'] }}</h6>
                            <span class="text-muted font-12 flex-shrink-0">
                                <i class='bx bx-time'></i> {{ $act['time'] }}
                            </span>
                        </div>
                        <div class="text-primary fw-semibold font-13">{{ $act['actor'] }}</div>
                        @if($act['note'])
                            <div class="bg-light border-start border-3 border-primary p-2 rounded-2 font-13 mt-2">
                                <em>"{{ $act['note'] }}"</em>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- ============== KOLOM KANAN: ACTION PANEL (STICKY) ============== -->
    <div class="col-12 col-xl-3">
        <div class="npi-sticky-action">
            <div class="npi-card" style="border-top: 3px solid var(--bs-primary);">
                <div class="npi-card-head" style="padding-bottom:.35rem;">
                    <i class='bx bx-task'></i><h6>Panel Aksi NPI</h6>
                </div>
                <div class="npi-card-body">
                    <div class="text-center bg-light rounded-3 p-3 mb-3">
                        <span class="npi-status-pill {{ $statusKey }}">{{ $statusNpi }}</span>
                        <div class="mt-2 text-muted font-12">Nilai Netto NPI</div>
                        <div class="fs-5 fw-bold text-primary">
                            Rp {{ number_format($nominalNpi, 0, ',', '.') }}
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="field-label mb-0">Kesiapan Pengajuan</span>
                        <span class="badge {{ $readyCount === $totalReady ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ $readyCount }}/{{ $totalReady }}
                        </span>
                    </div>
                    <ul class="ready-list mb-3">
                        @foreach($readinessChecklist as $check)
                            <li>
                                @if($check['status'] === 'ready')
                                    <span class="ico ok"><i class='bx bx-check'></i></span>
                                @else
                                    <span class="ico no"><i class='bx bx-x'></i></span>
                                @endif
                                <span class="flex-grow-1 npi-min-w-0">
                                    <span class="fw-semibold d-block text-break">{{ $check['label'] }}</span>
                                    <small class="text-muted font-11">{{ $check['hint'] }}</small>
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    @if($canSubmit)
                        <form action="{{ route('npis.kontrak.submit', $spmModel->id) }}" method="POST" id="form-submit-npi">
                            @csrf
                            <button type="submit"
                                    class="btn btn-success w-100 fw-bold"
                                    {{ !$isReadyToSubmit ? 'disabled' : '' }}>
                                <i class='bx bx-send me-1'></i> Ajukan Verifikasi
                            </button>
                        </form>

                        @if(!$isReadyToSubmit && !empty($readinessIssues))
                            <div class="alert alert-danger border-0 mt-3 p-2 mb-0">
                                <strong class="d-block font-13 mb-1">
                                    <i class='bx bx-error-alt'></i> Pengajuan Terkunci
                                </strong>
                                <ul class="mb-0 ps-3 font-12">
                                    @foreach($readinessIssues as $issue)
                                        <li>{{ $issue }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @elseif($statusNpi === \App\Models\DokumenNpi::STATUS_REVISI)
                        <div class="alert alert-danger border-0 mb-0 p-2 font-13">
                            <strong><i class='bx bx-revision'></i> Dokumen Revisi.</strong>
                            Lakukan perbaikan lalu simpan ulang.
                        </div>
                    @elseif($statusNpi === \App\Models\DokumenNpi::STATUS_MENUNGGU_UPLOAD)
                        <div class="alert alert-info border-0 mb-0 p-2 font-13">
                            <strong><i class='bx bx-time'></i> Menunggu Upload.</strong>
                            Unggah scan NPI bertanda tangan di kolom tengah.
                        </div>
                    @elseif(in_array($statusNpi, $finalStatuses, true))
                        <div class="alert alert-success border-0 mb-0 p-2 font-13">
                            <strong><i class='bx bx-check-shield'></i> NPI Final.</strong>
                            Siap dijadikan dasar SP2D.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
