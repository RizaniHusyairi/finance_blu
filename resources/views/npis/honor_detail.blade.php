@extends('layouts.app')
@section('title', 'Workspace NPI Honorarium')

@push('css')
<style>
    /* === NPI Workspace — Modernized (Honorarium) === */

    /* Hero with gradient + decorative pattern */
    .npi-hero {
        position: relative; overflow: hidden;
        border: 0 !important;
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 55%, #6610f2 100%);
        color: #fff;
        border-radius: 1.1rem !important;
        box-shadow: 0 12px 30px -12px rgba(76,29,149,.45) !important;
    }
    .npi-hero::before {
        content: ''; position: absolute; inset: 0;
        background-image:
            radial-gradient(circle at 92% 8%,  rgba(255,255,255,.18) 0%, transparent 30%),
            radial-gradient(circle at 8%  100%, rgba(255,255,255,.08) 0%, transparent 40%);
        pointer-events: none;
    }
    .npi-hero::after {
        content: ''; position: absolute; right: -30px; top: -30px;
        width: 160px; height: 160px; border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,.12) 0%, transparent 70%);
        pointer-events: none;
    }
    .npi-hero .card-body { position: relative; z-index: 1; }
    .npi-hero h4 { color: #fff !important; }
    .npi-hero .hero-sub  { color: rgba(255,255,255,.85); }
    .npi-hero .hero-tag {
        background: rgba(255,255,255,.18); color: #fff;
        padding: .3rem .8rem; border-radius: 50rem;
        font-size: .68rem; font-weight: 700; letter-spacing: .06em;
        text-transform: uppercase; backdrop-filter: blur(6px);
        border: 1px solid rgba(255,255,255,.2);
    }
    .npi-hero .btn-outline-secondary {
        color: #fff; border-color: rgba(255,255,255,.4); background: rgba(255,255,255,.08);
    }
    .npi-hero .btn-outline-secondary:hover { background: rgba(255,255,255,.18); color: #fff; }
    .npi-hero .btn-outline-danger {
        color: #fff; border-color: rgba(255,255,255,.4); background: rgba(220,53,69,.25);
    }
    .npi-hero .btn-outline-danger:hover { background: #dc3545; border-color: #dc3545; }

    .hero-meta {
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.22) !important;
        backdrop-filter: blur(8px);
        border-radius: .85rem !important;
    }
    .hero-meta .field-label { color: rgba(255,255,255,.7) !important; }
    .hero-meta .field-value { color: #fff !important; }
    .hero-meta .nominal-hero {
        font-size: 1.35rem; font-weight: 800; color: #fff;
        letter-spacing: -.01em;
    }

    /* Status pill */
    .npi-status-pill {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .4rem .9rem; font-size: .72rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .06em;
        border-radius: 50rem; line-height: 1;
    }
    .npi-status-pill::before {
        content: ''; width: 8px; height: 8px; border-radius: 50%;
        background: currentColor; box-shadow: 0 0 0 3px rgba(255,255,255,.18);
    }
    .npi-status-pill.s-draft  { background: rgba(255,255,255,.18);  color: #fff; }
    .npi-status-pill.s-wait   { background: rgba(255,255,255,.22);  color: #fff; }
    .npi-status-pill.s-revisi { background: #dc3545; color: #fff; }
    .npi-status-pill.s-final  { background: #198754; color: #fff; }

    /* Cards with color variants */
    .npi-card {
        background: #fff; border: 1px solid #eef0f4;
        border-radius: .95rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
        transition: transform .15s ease, box-shadow .2s ease;
        overflow: hidden;
    }
    .npi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 22px -8px rgba(15,23,42,.12);
    }
    .npi-card .npi-card-head {
        padding: 1rem 1.15rem .6rem;
        display: flex; align-items: center; gap: .8rem;
    }
    .npi-card .npi-card-head .ico-wrap {
        width: 38px; height: 38px; flex-shrink: 0;
        border-radius: .65rem;
        background: var(--card-tint, rgba(13,110,253,.1));
        color: var(--card-accent, #0d6efd);
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.15rem;
    }
    .npi-card .npi-card-head h6 {
        margin: 0; font-size: .82rem; letter-spacing: .05em;
        text-transform: uppercase; font-weight: 800; color: #0f172a;
    }
    .npi-card .npi-card-head .head-sub {
        display: block; font-size: .72rem; font-weight: 500;
        color: #94a3b8; text-transform: none; letter-spacing: 0; margin-top: 2px;
    }
    .npi-card .npi-card-body { padding: .35rem 1.15rem 1.15rem; }

    .npi-card.c-blue   { --card-accent: #2563eb; --card-tint: rgba(37,99,235,.12); }
    .npi-card.c-purple { --card-accent: #7c3aed; --card-tint: rgba(124,58,237,.12); }
    .npi-card.c-teal   { --card-accent: #0d9488; --card-tint: rgba(13,148,136,.12); }
    .npi-card.c-amber  { --card-accent: #d97706; --card-tint: rgba(217,119,6,.12); }
    .npi-card.c-green  { --card-accent: #16a34a; --card-tint: rgba(22,163,74,.12); }
    .npi-card.c-slate  { --card-accent: #64748b; --card-tint: rgba(100,116,139,.12); }
    .npi-card.c-rose   { --card-accent: #e11d48; --card-tint: rgba(225,29,72,.12); }

    .field-label {
        font-size: .7rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .05em; color: #6b7280; margin-bottom: .2rem;
        display: block;
    }
    .field-value { font-weight: 600; color: #0f172a; }

    /* Mini doc cards */
    .mini-doc {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid #e2e8f0;
        border-radius: .65rem;
        padding: .65rem .8rem;
    }
    .mini-doc .doc-label {
        font-size: .65rem; font-weight: 800; color: var(--card-accent, #2563eb);
        text-transform: uppercase; letter-spacing: .08em;
    }

    /* Doc rows for kelengkapan */
    .doc-row {
        padding: .65rem .85rem; border-radius: .65rem;
        transition: transform .12s ease, box-shadow .12s ease;
        border: 1px solid transparent;
    }
    .doc-row + .doc-row { margin-top: .35rem; }
    .doc-row:hover { transform: translateX(3px); }
    .doc-row.is-ready    { background: rgba(22,163,74,.07);  border-color: rgba(22,163,74,.18); }
    .doc-row.is-missing  { background: rgba(225,29,72,.07);  border-color: rgba(225,29,72,.18); }
    .doc-row.is-optional { background: #f8fafc; border-color: #e2e8f0; }

    /* Personel table */
    .honor-table { font-size: .78rem; margin-bottom: 0; }
    .honor-table thead th {
        font-size: .65rem; text-transform: uppercase; letter-spacing: .04em;
        color: #64748b; background: #f8fafc; border-color: #e2e8f0; font-weight: 700;
    }
    .honor-table td { vertical-align: middle; border-color: #eef0f4; }

    /* Approval rows */
    .approval-row {
        border: 1px solid #eef0f4; border-left-width: 4px;
        border-radius: .7rem; padding: .85rem 1rem; background: #fff;
        transition: transform .12s ease, box-shadow .15s ease;
    }
    .approval-row:hover { transform: translateX(2px); box-shadow: 0 4px 12px -4px rgba(15,23,42,.08); }
    .approval-row.is-approved { border-left-color: #16a34a; background: linear-gradient(90deg, rgba(22,163,74,.07) 0%, #fff 60%); }
    .approval-row.is-revision { border-left-color: #e11d48; background: linear-gradient(90deg, rgba(225,29,72,.07) 0%, #fff 60%); }
    .approval-row.is-pending  { border-left-color: #f59e0b; background: linear-gradient(90deg, rgba(245,158,11,.08) 0%, #fff 60%); }
    .approval-row.is-waiting  { border-left-color: #cbd5e1; }

    .approval-row .role-avatar {
        width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: #f1f5f9; color: #475569; font-size: 1.05rem;
    }
    .approval-row.is-approved .role-avatar { background: rgba(22,163,74,.15); color: #16a34a; }
    .approval-row.is-revision .role-avatar { background: rgba(225,29,72,.15); color: #e11d48; }
    .approval-row.is-pending  .role-avatar { background: rgba(245,158,11,.15); color: #d97706; }

    /* Action hero (panel kanan) */
    .action-hero {
        background: linear-gradient(135deg, #4f46e5 0%, #6610f2 100%);
        color: #fff;
        border-radius: .85rem;
        padding: 1.1rem 1.15rem 1.25rem;
        position: relative; overflow: hidden;
        margin-bottom: 1rem;
    }
    .action-hero::before {
        content: ''; position: absolute; right: -30%; top: -50%;
        width: 80%; height: 200%;
        background: radial-gradient(ellipse, rgba(255,255,255,.16) 0%, transparent 60%);
    }
    .action-hero > * { position: relative; z-index: 1; }
    .action-hero .ah-label { color: rgba(255,255,255,.75); font-size: .68rem; letter-spacing: .08em; text-transform: uppercase; font-weight: 700; }
    .action-hero .ah-nominal { font-size: 1.5rem; font-weight: 800; color: #fff; letter-spacing: -.01em; }

    .action-hero .npi-status-pill { background: rgba(255,255,255,.22); color: #fff; }
    .action-hero .npi-status-pill.s-final  { background: #16a34a; }
    .action-hero .npi-status-pill.s-revisi { background: #e11d48; }

    /* Readiness checklist with progress bar */
    .readiness-progress {
        height: 8px; background: #eef0f4; border-radius: 50rem;
        overflow: hidden; margin-bottom: .85rem;
    }
    .readiness-progress .bar {
        height: 100%; border-radius: 50rem;
        background: linear-gradient(90deg, #f59e0b, #16a34a);
        transition: width .4s ease;
    }
    .ready-list { list-style: none; padding: 0; margin: 0; }
    .ready-list li {
        display: flex; align-items: flex-start; gap: .6rem;
        padding: .5rem 0; font-size: .82rem;
        border-bottom: 1px dashed rgba(15,23,42,.07);
    }
    .ready-list li:last-child { border-bottom: 0; padding-bottom: 0; }
    .ready-list .ico {
        width: 24px; height: 24px; flex: 0 0 24px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .95rem; margin-top: 1px;
    }
    .ready-list .ico.ok { background: rgba(22,163,74,.15); color: #16a34a; }
    .ready-list .ico.no { background: rgba(225,29,72,.15); color: #e11d48; }

    /* Timeline */
    .npi-timeline-item {
        position: relative; padding-left: 1.5rem;
        padding-bottom: 1.1rem; border-left: 2px solid #eef0f4;
    }
    .npi-timeline-item:last-child { padding-bottom: 0; }
    .npi-timeline-item::before {
        content: ''; position: absolute; left: -8px; top: 4px;
        width: 14px; height: 14px; border-radius: 50%;
        background: #fff; border: 2px solid #94a3b8;
        transition: all .2s ease;
    }
    .npi-timeline-item.is-active::before {
        border-color: #4f46e5; background: #4f46e5;
        box-shadow: 0 0 0 4px rgba(79,70,229,.15);
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

    $readyCount  = collect($readinessChecklist)->where('status', 'ready')->count();
    $totalReady  = is_countable($readinessChecklist) ? count($readinessChecklist) : 0;

    $showUploadCard   = in_array($statusNpi, [
        \App\Models\DokumenNpi::STATUS_MENUNGGU_UPLOAD,
        \App\Models\DokumenNpi::STATUS_NPI_TERBIT,
        \App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL,
    ], true);
    $showProgressCard = !in_array($statusNpi, ['DRAFT', 'Belum Dibuat', ''], true);

    $personelList = collect($tagihan?->detailHonorarium ?? []);
    $jumlahPersonel = $personelList->count();
@endphp

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

<!-- ============== HERO / WORKSPACE HEADER ============== -->
<div class="card npi-hero mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3">
            <div class="npi-min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="hero-tag"><i class='bx bx-money me-1'></i> Penyusunan NPI Honorarium</span>
                    <span class="npi-status-pill {{ $statusKey }}">{{ str_replace('_', ' ', $statusNpi) }}</span>
                </div>
                <h4 class="fw-bold mb-1 text-break">
                    {{ $npiModel->nomor_npi ?? 'NPI Belum Tersimpan' }}
                </h4>
                <div class="hero-sub small text-break">
                    <i class='bx bx-receipt me-1'></i>{{ $tagihan?->deskripsi ?? 'NPI Honorarium' }}
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                <a href="{{ route('npis.honor.index') }}" class="btn btn-outline-secondary">
                    <i class='bx bx-arrow-back'></i> Kembali
                </a>
                @if($canEditNpi)
                    <button type="button" class="btn btn-warning fw-semibold" data-bs-toggle="modal" data-bs-target="#modalFormNpi">
                        <i class='bx bx-edit-alt'></i> {{ $npiModel?->nomor_npi ? 'Edit Draft NPI' : 'Buat Draft NPI' }}
                    </button>
                @endif
                @if($npiModel && in_array($npiModel->status, [\App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL, \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG, \App\Models\DokumenNpi::STATUS_NPI_TERBIT]))
                    <a href="{{ route('npis.cetak-pdf', $npiModel->id) }}" target="_blank" class="btn btn-outline-danger">
                        <i class='bx bxs-file-pdf'></i> Cetak PDF
                    </a>
                @endif
                @if($npiModel && $statusNpi === \App\Models\DokumenNpi::STATUS_NPI_TERBIT)
                    <a href="{{ route('sp2ds.honor.detail', $npiModel->id) }}" class="btn btn-success fw-semibold">
                        <i class='bx bx-receipt'></i> Buat SP2D
                    </a>
                @endif
            </div>
        </div>

        <div class="hero-meta p-3">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <span class="field-label"><i class='bx bx-file-blank me-1'></i> Nomor SPM</span>
                    <div class="field-value text-break">{{ $spmModel->nomor_spm ?? '-' }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <span class="field-label"><i class='bx bx-receipt me-1'></i> Nomor SPP</span>
                    <div class="field-value text-break">{{ $sppModel->nomor_spp ?? '-' }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <span class="field-label"><i class='bx bx-group me-1'></i> Jumlah Penerima</span>
                    <div class="field-value">{{ $jumlahPersonel }} Orang</div>
                </div>
                <div class="col-6 col-md-3">
                    <span class="field-label"><i class='bx bx-wallet-alt me-1'></i> Nilai NPI (Netto)</span>
                    <div class="nominal-hero">Rp {{ number_format($nominalNpi, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- ============== KOLOM KIRI: KONTEKS ============== -->
    <div class="col-12 col-xl-4">
        <!-- Dokumen Sumber -->
        <div class="npi-card c-blue mb-3">
            <div class="npi-card-head">
                <span class="ico-wrap"><i class='bx bx-file'></i></span>
                <div>
                    <h6>Dokumen Sumber</h6>
                    <span class="head-sub">Bukti keuangan SPM / SPP / Tagihan</span>
                </div>
            </div>
            <div class="npi-card-body">
                <div class="mb-3">
                    <span class="field-label">Uraian Tagihan</span>
                    <div class="field-value text-break">{{ $tagihan?->deskripsi ?? 'NPI Honorarium' }}</div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <span class="field-label">Nomor Tagihan</span>
                        <div class="field-value small text-break">{{ $tagihan?->nomor_tagihan ?? '-' }}</div>
                    </div>
                    <div class="col-6">
                        <span class="field-label">Nomor SPP</span>
                        <div class="field-value small text-break">{{ $sppModel->nomor_spp ?? '-' }}</div>
                    </div>
                    <div class="col-6">
                        <span class="field-label">Tanggal SPP</span>
                        <div class="field-value small">{{ optional($sppModel->tanggal_spp)->format('d M Y') ?? '-' }}</div>
                    </div>
                    <div class="col-6">
                        <span class="field-label">Pemrakarsa (PPK)</span>
                        <div class="field-value small text-break">{{ $ppkSpp?->name ?? 'PPK' }}</div>
                    </div>
                </div>

                <div class="mb-3">
                    <span class="field-label">Kode COA (Mata Anggaran)</span>
                    <div class="field-value">
                        @if($spmModel->dipaRevisionItem?->coa)
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle me-1 font-monospace">
                                {{ $spmModel->dipaRevisionItem->coa->kode_mak_lengkap ?? $spmModel->dipaRevisionItem->coa->kd_akun }}
                            </span>
                            <span class="small fw-normal d-block mt-1">{{ $spmModel->dipaRevisionItem->coa->nama_akun }}</span>
                        @else
                            <span class="text-muted fst-italic">-</span>
                        @endif
                    </div>
                </div>

                <div class="mini-doc">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="doc-label">Bruto</span>
                        <span class="field-value small">Rp {{ number_format($tagihan?->total_bruto ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="doc-label" style="color:#e11d48;">Potongan (Pajak)</span>
                        <span class="field-value small text-danger">Rp {{ number_format($tagihan?->total_potongan ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <hr class="my-2 opacity-25">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="doc-label" style="color:#16a34a;">Netto (= SPM)</span>
                        <span class="fw-bold text-success">Rp {{ number_format($tagihan?->total_netto ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rincian Personel -->
        <div class="npi-card c-teal mb-3">
            <div class="npi-card-head">
                <span class="ico-wrap"><i class='bx bx-group'></i></span>
                <div>
                    <h6>Rincian Penerima</h6>
                    <span class="head-sub">{{ $jumlahPersonel }} personel penerima honorarium</span>
                </div>
            </div>
            <div class="npi-card-body">
                <div class="table-responsive">
                    <table class="table table-sm honor-table">
                        <thead>
                            <tr>
                                <th>Nama / Jabatan</th>
                                <th class="text-end">Bruto</th>
                                <th class="text-end">PPh</th>
                                <th class="text-end">Netto</th>
                                <th>Rekening</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($personelList as $personel)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $personel->nama_personel }}</div>
                                        <div class="text-muted" style="font-size:.68rem;">NIP: {{ $personel->nrp_nip ?? '-' }}</div>
                                        <div class="text-muted" style="font-size:.68rem;">{{ $personel->jabatan ?? '-' }}</div>
                                    </td>
                                    <td class="text-end">Rp {{ number_format($personel->nilai_honor ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end text-danger">Rp {{ number_format($personel->pph ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold text-success">Rp {{ number_format($personel->netto ?? 0, 0, ',', '.') }}</td>
                                    <td>
                                        @if($personel->rekening)
                                            <div class="fw-semibold">{{ $personel->jenis_bank ?? 'Bank' }}</div>
                                            <div class="font-monospace text-dark" style="font-size:.7rem;">{{ $personel->rekening }}</div>
                                            <div class="text-muted" style="font-size:.68rem;">a.n. {{ $personel->nama_rekening }}</div>
                                        @else
                                            <span class="badge bg-danger">KOSONG</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data personel.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kelengkapan Dokumen Pendukung -->
        <div class="npi-card c-purple mb-3">
            <div class="npi-card-head">
                <span class="ico-wrap"><i class='bx bx-folder-open'></i></span>
                <div>
                    <h6>Kelengkapan Dokumen Pendukung</h6>
                    <span class="head-sub">Dokumen sumber & lampiran tagihan</span>
                </div>
            </div>
            <div class="npi-card-body">
                @forelse($documentStatuses as $doc)
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
                                @if(empty($doc['required']))
                                    <small class="text-muted font-11">Lampiran</small>
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
                @empty
                    <div class="text-muted small text-center py-2">Belum ada dokumen pendukung.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- ============== KOLOM TENGAH: FORM & DETAIL ============== -->
    <div class="col-12 col-xl-5">
        <!-- Detail / Parameter NPI -->
        <div class="npi-card c-amber mb-3">
            <div class="npi-card-head">
                <span class="ico-wrap"><i class='bx bx-edit'></i></span>
                <div>
                    <h6>Parameter NPI</h6>
                    <span class="head-sub">
                        {{ $canEditNpi ? "Klik 'Buat Draft NPI' di atas untuk mengisi" : 'Detail yang telah tersimpan' }}
                    </span>
                </div>
            </div>
            <div class="npi-card-body">
                @php
                    $previewNomor   = $npiModel?->nomor_npi ?? ($canEditNpi ? $autoNomorNpi : '-');
                    $previewTanggal = $npiModel?->tanggal_npi?->format('d M Y')
                        ?? ($canEditNpi ? \Carbon\Carbon::now()->format('d M Y') : '-');
                    $previewBenpen  = $npiModel?->bendaharaPenerimaan?->name
                        ?? $bendaharaPenerimaanTagihan?->name
                        ?? $tagihan?->bendahara_penerimaan_nama_snapshot
                        ?? 'Belum Ditentukan';
                    $previewKoor    = $koordinatorKeuanganUser?->name
                        ?? $tagihan?->koordinator_keuangan_nama_snapshot
                        ?? 'Belum Ditentukan';
                    $previewCatatan = $npiModel?->uraian_npi ?? $npiModel?->catatan;
                @endphp
                <div class="row g-3">
                    <div class="col-md-7">
                        <span class="field-label">Nomor NPI</span>
                        <div class="field-value text-primary fw-bold text-break">{{ $previewNomor }}</div>
                        @if($canEditNpi && !$npiModel?->nomor_npi)
                            <small class="text-muted"><i class='bx bx-info-circle'></i> Otomatis dari SPP — bisa diedit di form</small>
                        @endif
                    </div>
                    <div class="col-md-5">
                        <span class="field-label">Tanggal NPI</span>
                        <div class="field-value">{{ $previewTanggal }}</div>
                    </div>
                    <div class="col-12"><hr class="my-1 opacity-25"></div>
                    <div class="col-md-6">
                        <span class="field-label">Bendahara Penerimaan</span>
                        <div class="field-value">{{ $previewBenpen }}</div>
                        @if($canEditNpi && !$bendaharaPenerimaanTagihan)
                            <small class="text-danger d-block mt-1"><i class='bx bx-error-circle'></i> Verifikator belum ada pada tagihan sumber.</small>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <span class="field-label">Verifikator PPK</span>
                        <div class="field-value">{{ $ppkSpp?->name ?? 'Belum Ditentukan' }}</div>
                        @if($canEditNpi)<small class="text-muted">Diwariskan dari SPP</small>@endif
                    </div>
                    <div class="col-md-6">
                        <span class="field-label">Koordinator Keuangan</span>
                        <div class="field-value">{{ $previewKoor }}</div>
                    </div>
                    <div class="col-md-6">
                        <span class="field-label">Verifikator Kasubbag</span>
                        <div class="field-value">{{ $kasubbagUser?->name ?? 'Belum Ditentukan' }}</div>
                    </div>
                    <div class="col-12">
                        <span class="field-label">Uraian / Catatan</span>
                        <div class="bg-light p-3 rounded-3 border small">
                            {{ $previewCatatan ?: ($canEditNpi ? 'Belum diisi — tambahkan via form (opsional)' : 'Tidak ada catatan.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Verifikasi Paralel -->
        @if($showProgressCard)
        <div class="npi-card c-rose mb-3">
            <div class="npi-card-head">
                <span class="ico-wrap"><i class='bx bx-git-branch'></i></span>
                <div>
                    <h6>Progress Verifikasi Paralel</h6>
                    <span class="head-sub">4 verifikator memproses secara simultan</span>
                </div>
            </div>
            <div class="npi-card-body">
                @php
                    $approvals = [
                        ['title' => 'Bendahara Penerimaan', 'icon' => 'bx-money-withdraw', 'approval' => $benpenApproval,      'default_name' => $npiModel?->bendaharaPenerimaan?->name ?? 'Semua Bendahara Penerimaan'],
                        ['title' => 'PPK',                  'icon' => 'bx-user-pin',       'approval' => $ppkApproval,         'default_name' => $ppkSpp?->name ?? 'Semua PPK'],
                        ['title' => 'Koordinator Keuangan', 'icon' => 'bx-id-card',        'approval' => $koordinatorApproval, 'default_name' => $koordinatorKeuanganUser?->name ?? 'Semua Koordinator'],
                        ['title' => 'Kasubbag',             'icon' => 'bx-shield-quarter', 'approval' => $kasubbagApproval,    'default_name' => $kasubbagUser?->name ?? 'Semua Kasubbag'],
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
                                    <span class="role-avatar"><i class='bx {{ $app["icon"] }}'></i></span>
                                    <h6 class="mb-0 fw-bold text-truncate">{{ $app['title'] }}</h6>
                                </div>
                                <span class="badge {{ $badgeCls }}">{{ $badgeLabel }}</span>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
                                <span class="text-truncate">
                                    <i class='bx bx-user'></i>
                                    {{ $app['approval']?->actedByUser?->name ?? $app['approval']?->assignedUser?->name ?? $app['default_name'] }}
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
        <div class="npi-card c-green mb-3">
            <div class="npi-card-head">
                <span class="ico-wrap"><i class='bx bx-cloud-upload'></i></span>
                <div>
                    <h6>Upload NPI Bertanda Tangan</h6>
                    <span class="head-sub">File fisik scan dokumen</span>
                </div>
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

                <form action="{{ route('npis.honor.upload-signed-npi', $npiModel->id) }}" method="POST" enctype="multipart/form-data">
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
        <div class="npi-card c-slate mb-3">
            <div class="npi-card-head">
                <span class="ico-wrap"><i class='bx bx-history'></i></span>
                <div>
                    <h6>Riwayat Aktivitas</h6>
                    <span class="head-sub">Jejak setiap aksi pada NPI ini</span>
                </div>
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
            <div class="npi-card c-blue" style="border-top: 3px solid var(--card-accent);">
                <div class="npi-card-head" style="padding-bottom:.35rem;">
                    <span class="ico-wrap"><i class='bx bx-task'></i></span>
                    <div>
                        <h6>Panel Aksi NPI</h6>
                        <span class="head-sub">Ringkasan & kontrol pengajuan</span>
                    </div>
                </div>
                <div class="npi-card-body">
                    <div class="action-hero">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="ah-label">Status NPI</span>
                            <span class="npi-status-pill {{ $statusKey }}">{{ str_replace('_', ' ', $statusNpi) }}</span>
                        </div>
                        <div class="ah-label mt-2">Nilai Netto</div>
                        <div class="ah-nominal">Rp {{ number_format($nominalNpi, 0, ',', '.') }}</div>
                    </div>

                    @php $readyPct = $totalReady > 0 ? round(($readyCount / $totalReady) * 100) : 0; @endphp
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="field-label mb-0">Kesiapan Pengajuan</span>
                        <span class="badge {{ $readyCount === $totalReady ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ $readyCount }}/{{ $totalReady }}
                        </span>
                    </div>
                    <div class="readiness-progress"><div class="bar" style="width: {{ $readyPct }}%;"></div></div>
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
                        <form action="{{ route('npis.honor.submit', $spmModel->id) }}" method="POST" id="form-submit-npi"
                              onsubmit="return confirm('Mengajukan NPI Honorarium ini akan mengunci draf dan memanggil verifikasi paralel Bendahara Penerimaan, PPK, Koordinator Keuangan, dan Kasubbag secara bersamaan. Lanjutkan?')">
                            @csrf
                            <button type="submit"
                                    class="btn btn-success w-100 fw-bold"
                                    {{ !$isReadyToSubmit ? 'disabled' : '' }}>
                                <i class='bx bx-send me-1'></i> Ajukan Verifikasi
                            </button>
                        </form>

                        @if(!$isReadyToSubmit && !empty($readinessIssues) && count($readinessIssues) > 0)
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

@if($canEditNpi)
<!-- ============== MODAL FORM PENYUSUNAN NPI ============== -->
<div class="modal fade" id="modalFormNpi" tabindex="-1" aria-labelledby="modalFormNpiLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem; overflow: hidden;">
            <form action="{{ route('npis.honor.store', $spmModel->id) }}" method="POST" id="form-draft-npi">
                @csrf
                <div class="modal-header border-0 text-white" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">
                    <div class="d-flex align-items-center gap-3">
                        <span class="d-inline-flex align-items-center justify-content-center"
                              style="width:42px;height:42px;border-radius:.65rem;background:rgba(255,255,255,.22);">
                            <i class='bx bx-edit fs-4'></i>
                        </span>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-white" id="modalFormNpiLabel">Form Penyusunan NPI Honorarium</h5>
                            <small class="opacity-75">Isi data NPI sebelum diajukan untuk verifikasi</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold mb-1">
                                Nomor Bukti NPI <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nomor_npi"
                                   class="form-control fw-bold text-primary"
                                   value="{{ old('nomor_npi', $npiModel?->nomor_npi ?? $autoNomorNpi) }}"
                                   placeholder="Contoh: NPI-HONOR-001/{{ date('Y') }}" required>
                            <small class="text-muted"><i class='bx bx-info-circle'></i> Otomatis dari SPP</small>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold mb-1">
                                Tanggal NPI <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="tanggal_npi"
                                   class="form-control"
                                   value="{{ old('tanggal_npi', optional($npiModel?->tanggal_npi)->format('Y-m-d') ?? date('Y-m-d')) }}" required>
                        </div>

                        <div class="col-12"><hr class="my-1 opacity-25"></div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold mb-1">Tahun Anggaran</label>
                            <input type="text" name="tahun_anggaran" class="form-control bg-light"
                                   value="{{ old('tahun_anggaran', $spmModel->tahun_anggaran ?? date('Y')) }}" readonly>
                        </div>
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
                                Uraian / Tujuan NPI <span class="text-muted fw-normal">(Opsional)</span>
                            </label>
                            <textarea name="uraian_npi" class="form-control" rows="3"
                                      placeholder="Salin tujuan NPI ke sini...">{{ old('uraian_npi', $npiModel?->uraian_npi ?? $tagihan?->deskripsi ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class='bx bx-x'></i> Batal
                    </button>
                    <button type="submit" class="btn btn-warning fw-semibold">
                        <i class='bx bx-save me-1'></i> Simpan Draft NPI
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('script')
<script>
    // Auto-open modal jika ada validation error dari server
    document.addEventListener('DOMContentLoaded', function () {
        @if($canEditNpi && ($errors->any() || old('nomor_npi')))
            const el = document.getElementById('modalFormNpi');
            if (el) new bootstrap.Modal(el).show();
        @endif
    });
</script>
@endpush
