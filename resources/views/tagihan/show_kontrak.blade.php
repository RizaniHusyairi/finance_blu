@extends('layouts.app')
@section('title', 'Detail Tagihan Termin')

@push('css')
@include('partials.modern-css')
<style>
    /* ── Kartu Pejabat Penanda Tangan ─────────────────────────────── */
    .pj-card {
        --pj-color: #0d6efd;
        position: relative;
        background: #fff;
        border: 1px solid #e8ecf3;
        border-radius: 16px;
        padding: 1.1rem 1.15rem 0;
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }
    .pj-card::before {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--pj-color), color-mix(in srgb, var(--pj-color) 35%, #fff));
    }
    .pj-card:hover {
        transform: translateY(-3px);
        border-color: color-mix(in srgb, var(--pj-color) 35%, #e8ecf3);
        box-shadow: 0 16px 32px -18px color-mix(in srgb, var(--pj-color) 55%, transparent);
    }
    .pj-card.is-empty {
        border-style: dashed;
        background: #fffdf5;
    }
    .pj-avatar {
        width: 52px; height: 52px;
        border-radius: 14px;
        flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        color: #fff;
        font-weight: 800;
        font-size: 1rem;
        letter-spacing: .02em;
        background: linear-gradient(135deg, var(--pj-color), color-mix(in srgb, var(--pj-color) 65%, #1e293b));
        box-shadow: 0 8px 16px -8px color-mix(in srgb, var(--pj-color) 80%, transparent);
        text-shadow: 0 1px 1px rgba(0,0,0,.18);
    }
    .pj-card.is-empty .pj-avatar {
        background: #f1f5f9;
        color: #94a3b8;
        box-shadow: none;
        border: 1.5px dashed #cbd5e1;
    }
    .pj-role {
        display: inline-flex; align-items: center; gap: .3rem;
        font-size: .66rem;
        font-weight: 800;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: var(--pj-color);
        background: color-mix(in srgb, var(--pj-color) 10%, #fff);
        border-radius: 999px;
        padding: .18rem .6rem;
    }
    .pj-name {
        font-weight: 700;
        font-size: .95rem;
        color: #0f172a;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }
    .pj-nip {
        display: inline-flex; align-items: center; gap: .35rem;
        font-family: var(--bs-font-monospace);
        font-size: .74rem;
        color: #475569;
        background: #f4f6fa;
        border: 1px solid #e8ecf3;
        border-radius: 7px;
        padding: .14rem .5rem;
        margin-top: .3rem;
        overflow-wrap: anywhere;
    }
    .pj-jabatan {
        font-size: .76rem;
        color: #94a3b8;
        margin-top: .35rem;
        overflow-wrap: anywhere;
    }
    .pj-tasks {
        margin-top: auto;
        border-top: 1px dashed #e8ecf3;
        padding: .7rem 0 .9rem;
    }
    .pj-card > .d-flex { margin-bottom: .85rem; }
    .pj-tasks-label {
        font-size: .62rem;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: .4rem;
        display: flex; align-items: center; gap: .3rem;
    }
    .pj-task {
        display: inline-flex; align-items: center; gap: .28rem;
        font-size: .7rem;
        font-weight: 600;
        color: #334155;
        background: #f6f8fb;
        border: 1px solid #e8ecf3;
        border-radius: 999px;
        padding: .18rem .55rem;
        margin: 0 .3rem .3rem 0;
        white-space: nowrap;
    }
    .pj-task i { color: var(--pj-color); font-size: .72rem; }
    /* ── Blok Tanda Tangan Vendor ─────────────────────────────────── */
    .ttv-card {
        border: 1px solid #e8ecf3;
        border-radius: 16px;
        background: linear-gradient(180deg, #fbfcfe, #f5f7fb);
        overflow: hidden;
    }
    .ttv-head-ic {
        width: 46px; height: 46px;
        border-radius: 13px;
        flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem;
        color: #fff;
        background: linear-gradient(135deg, #2563eb, #1e40af);
        box-shadow: 0 10px 20px -10px rgba(37, 99, 235, .7);
    }
    .ttv-doc {
        display: inline-flex; align-items: center; gap: .35rem;
        font-size: .72rem; font-weight: 700;
        border-radius: 999px;
        padding: .28rem .7rem;
        border: 1px solid #e8ecf3;
        background: #fff;
        color: #64748b;
    }
    .ttv-doc.is-signed { background: #e8f5ec; border-color: #cbe7d3; color: #15803d; }
    .ttv-doc.is-wajib { border-color: #f2caca; color: #b91c1c; background: #fdf1f1; }
    .ttv-choice {
        position: relative;
        display: flex; gap: .9rem; align-items: flex-start;
        width: 100%; height: 100%;
        margin: 0;
        padding: 1.05rem 2.4rem 1.05rem 1.05rem;
        background: #fff;
        border: 1.5px solid #e8ecf3;
        border-radius: 14px;
        cursor: pointer;
        transition: border-color .2s, background .2s, transform .2s, box-shadow .2s;
    }
    .ttv-choice input { position: absolute; opacity: 0; pointer-events: none; }
    .ttv-choice:hover { transform: translateY(-2px); box-shadow: 0 12px 26px -18px rgba(15, 23, 42, .45); }
    .ttv-choice:focus-within { outline: 3px solid #bfd3fa; outline-offset: 2px; }
    .ttv-choice-ic {
        width: 42px; height: 42px;
        border-radius: 12px;
        flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.15rem;
        background: #eef4ff; color: #2563eb;
        transition: background .2s, color .2s, transform .2s;
    }
    .ttv-choice.is-manual .ttv-choice-ic { background: #fdf3e3; color: #b45309; }
    .ttv-choice:hover .ttv-choice-ic { transform: scale(1.08) rotate(-4deg); }
    .ttv-check {
        position: absolute;
        top: .8rem; right: .8rem;
        width: 21px; height: 21px;
        border-radius: 50%;
        border: 2px solid #d6dce7;
        background: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: .7rem; color: transparent;
        transition: all .2s;
    }
    .ttv-choice.sel { border-color: #2563eb; background: linear-gradient(180deg, #f5f9ff, #eef4ff); }
    .ttv-choice.sel .ttv-check { border-color: #2563eb; background: #2563eb; color: #fff; }
    .ttv-choice.sel.is-manual { border-color: #b45309; background: linear-gradient(180deg, #fefaf3, #fdf3e3); }
    .ttv-choice.sel.is-manual .ttv-check { border-color: #b45309; background: #b45309; }
    .ttv-actions {
        border-top: 1px dashed #e2e8f0;
        margin-top: 1rem;
        padding-top: 1rem;
    }
    .btn-ttv-manual {
        background: linear-gradient(120deg, #b45309, #d97706);
        color: #fff;
        border: 0;
        box-shadow: 0 10px 20px -10px rgba(180, 83, 9, .65);
    }
    .btn-ttv-manual:hover { color: #fff; filter: brightness(1.06); transform: translateY(-1px); }
    @media (prefers-reduced-motion: reduce) {
        .ttv-choice, .ttv-choice:hover, .ttv-choice-ic { transition: none; transform: none; }
    }
    /* Bootstrap tidak punya utilitas .min-width-0 — tanpa ini anak flex tidak
       boleh menyusut sehingga text-truncate gagal dan teks meluber keluar kartu. */
    .min-width-0 { min-width: 0; }
    /* Nomor dokumen (PL.108/...), NIP, dan string panjang tanpa spasi lainnya
       dipatahkan hanya bila melebihi lebar kartunya. */
    .mc-body .fw-bold,
    .mc-body .font-monospace {
        overflow-wrap: anywhere;
    }
    @media (prefers-reduced-motion: reduce) {
        .pj-card, .pj-card:hover { transition: none; transform: none; }
    }
</style>
@endpush

@section('content')
@php
    $statusBadge = match($tagihan->status) {
        'DRAFT'                          => ['class' => 'bg-warning text-dark', 'icon' => 'pencil-square',         'label' => 'Draft — Sedang Disusun'],
        'PENDING_VERIFIKASI_KONTRAK'     => ['class' => 'bg-info text-dark',    'icon' => 'people-fill',           'label' => 'Verifikasi Paralel Berjalan'],
        'PENDING_PPK'                    => ['class' => 'bg-info text-white',   'icon' => 'hourglass-split',       'label' => 'Menunggu PPK'],
        'PENDING_PPSPM'                  => ['class' => 'bg-info text-white',   'icon' => 'hourglass-split',       'label' => 'Menunggu PPSPM'],
        'PENDING_KOORDINATOR_KEUANGAN'   => ['class' => 'bg-info text-white',   'icon' => 'hourglass-split',       'label' => 'Menunggu Koordinator Keuangan'],
        'PENDING_BENDAHARA_PENGELUARAN'  => ['class' => 'bg-info text-white',   'icon' => 'hourglass-split',       'label' => 'Menunggu Bendahara Pengeluaran'],
        'PENDING_BENDAHARA_PENERIMAAN'   => ['class' => 'bg-info text-white',   'icon' => 'hourglass-split',       'label' => 'Menunggu Bendahara Penerimaan'],
        'PENDING_KASUBBAG'               => ['class' => 'bg-primary text-white','icon' => 'hourglass-split',       'label' => 'Menunggu Kasubbag (Final)'],
        'REVISI_PPK', 'REVISI_PPSPM', 'REVISI_KOORDINATOR_KEUANGAN', 'REVISI_BENDAHARA_PENGELUARAN', 'REVISI_BENDAHARA_PENERIMAAN', 'REVISI_KASUBBAG', 'REVISI_PEJABAT_PENGADAAN'
                                         => ['class' => 'bg-warning text-dark', 'icon' => 'arrow-counterclockwise','label' => 'Perlu Revisi'],
        'DITOLAK_PPK', 'DITOLAK_PPSPM', 'DITOLAK_KOORDINATOR_KEUANGAN', 'DITOLAK_BENDAHARA_PENGELUARAN', 'DITOLAK_BENDAHARA_PENERIMAAN', 'DITOLAK_KASUBBAG'
                                         => ['class' => 'bg-danger text-white', 'icon' => 'x-octagon',             'label' => 'Ditolak'],
        'APPROVED', 'DISETUJUI_KONTRAK', 'READY_FOR_SPP'
                                         => ['class' => 'bg-success text-white','icon' => 'check-circle',          'label' => 'Siap Diproses — Menuju SPP'],
        default                          => ['class' => 'bg-secondary text-white', 'icon' => 'circle',             'label' => $tagihan->status],
    };

    $heroCls = match($tagihan->status) {
        'APPROVED', 'DISETUJUI_KONTRAK', 'READY_FOR_SPP' => 'hero-aktif',
        'PROSES_SPP', 'SEBAGIAN_SPP_TERBIT', 'SPP_TERBIT', 'SPP_LENGKAP' => 'hero-selesai',
        'DRAFT' => 'hero-draft',
        'REVISI_PPK', 'REVISI_PPSPM', 'REVISI_KOORDINATOR_KEUANGAN', 'REVISI_BENDAHARA_PENGELUARAN', 'REVISI_BENDAHARA_PENERIMAAN', 'REVISI_KASUBBAG', 'REVISI_PEJABAT_PENGADAAN' => 'hero-revisi',
        'DITOLAK_PPK', 'DITOLAK_PPSPM', 'DITOLAK_KOORDINATOR_KEUANGAN', 'DITOLAK_BENDAHARA_PENGELUARAN', 'DITOLAK_BENDAHARA_PENERIMAAN', 'DITOLAK_KASUBBAG' => 'hero-revisi',
        default => 'hero-pending',
    };
    
    $heroIcon = match($tagihan->status) {
        'APPROVED', 'DISETUJUI_KONTRAK', 'READY_FOR_SPP' => 'bi-check-circle-fill',
        'PROSES_SPP', 'SEBAGIAN_SPP_TERBIT', 'SPP_TERBIT', 'SPP_LENGKAP' => 'bi-check-all',
        'DRAFT' => 'bi-pencil-square',
        'REVISI_PPK', 'REVISI_PPSPM', 'REVISI_KOORDINATOR_KEUANGAN', 'REVISI_BENDAHARA_PENGELUARAN', 'REVISI_BENDAHARA_PENERIMAAN', 'REVISI_KASUBBAG', 'REVISI_PEJABAT_PENGADAAN' => 'bi-arrow-counterclockwise',
        'DITOLAK_PPK', 'DITOLAK_PPSPM', 'DITOLAK_KOORDINATOR_KEUANGAN', 'DITOLAK_BENDAHARA_PENGELUARAN', 'DITOLAK_BENDAHARA_PENERIMAAN', 'DITOLAK_KASUBBAG' => 'bi-x-octagon-fill',
        default => 'bi-info-circle-fill',
    };

    // Daftar verifikator + meta untuk styling (urutan = alur tanda-tangan dokumen)
    $verifikatorList = [
        ['key' => 'ppk',                  'role_code' => 'PPK',                   'label' => 'PPK',                                          'short' => 'PPK',          'color' => '#0d6efd', 'nama' => $tagihan->ppk_nama_snapshot,                  'nip' => $tagihan->ppk_nip_snapshot,                  'auto' => true],
        ['key' => 'ppspm',                'role_code' => 'PPSPM',                 'label' => 'PPSPM',                                        'short' => 'PPSPM',        'color' => '#6610f2', 'nama' => $tagihan->ppspm_nama_snapshot,                'nip' => $tagihan->ppspm_nip_snapshot,                'auto' => false],
        ['key' => 'bendahara_pengeluaran','role_code' => 'BENDAHARA_PENGELUARAN', 'label' => 'Bendahara Pengeluaran',                        'short' => 'BEND. KELUAR', 'color' => '#d63384', 'nama' => $tagihan->bendahara_pengeluaran_nama_snapshot,'nip' => $tagihan->bendahara_pengeluaran_nip_snapshot,'auto' => false],
        ['key' => 'bendahara_penerimaan', 'role_code' => 'BENDAHARA_PENERIMAAN',  'label' => 'Bendahara Penerimaan',                         'short' => 'BEND. TERIMA', 'color' => '#fd7e14', 'nama' => $tagihan->bendahara_penerimaan_nama_snapshot, 'nip' => $tagihan->bendahara_penerimaan_nip_snapshot, 'auto' => false],
        ['key' => 'koordinator_keuangan', 'role_code' => 'KOORDINATOR_KEUANGAN',  'label' => 'Koordinator Keuangan',                         'short' => 'KOOR. KEU',    'color' => '#198754', 'nama' => $tagihan->koordinator_keuangan_nama_snapshot, 'nip' => $tagihan->koordinator_keuangan_nip_snapshot, 'auto' => false],
        ['key' => 'kasubbag',             'role_code' => 'KASUBBAG',              'label' => 'Kepala Subbagian Keuangan dan Tata Usaha',     'short' => 'KASUBBAG',     'color' => '#0dcaf0', 'nama' => $tagihan->kasubbag_nama_snapshot,             'nip' => $tagihan->kasubbag_nip_snapshot,             'auto' => false],
    ];

    $initials = function ($name) {
        $name = trim((string) $name);
        if ($name === '') return '?';
        $parts = preg_split('/\s+/', $name);
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
        return mb_strtoupper($first . $last);
    };

    $verifikatorTerisi = collect($verifikatorList)->filter(fn($v) => !empty($v['nama']))->count();
    $verifikatorTotal  = count($verifikatorList);
    $verifikatorLengkap = $verifikatorTerisi === $verifikatorTotal;
@endphp

<div class="container-fluid py-4">
    {{-- ═══ HERO HEADER ═══ --}}
    <div class="kontrak-hero {{ $heroCls }}">
        <i class="bi bi-receipt briefcase-illust d-none d-md-block"></i>
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex gap-2 align-items-center mb-2 flex-wrap">
                    <span class="hero-status-pill"><i class="bi {{ $heroIcon }}"></i> {{ $statusBadge['label'] }}</span>
                    <span class="hero-status-pill" style="opacity:.85;">
                        <i class="bi bi-receipt"></i> Detail Tagihan Termin
                    </span>
                </div>
                <h2 class="hero-title">{{ $tagihan->nomor_tagihan }}</h2>
                <p class="hero-meta">
                    <i class="bi bi-hash"></i> Termin {{ $termin->termin_ke ?? '-' }} ({{ str_replace('_', ' ', $termin->jenis_termin) }})
                    <span class="mx-2 opacity-50">|</span>
                    <i class="bi bi-briefcase"></i> SPK: <strong>{{ $kontrak->nomor_spk ?? '-' }}</strong>
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-start">
                @if($tagihan->status === 'DRAFT' || str_starts_with((string) $tagihan->status, 'REVISI_'))
                    <a href="{{ route('tagihan.kontrak.edit', $tagihan->id) }}" class="btn-hero btn-hero-primary">
                        <i class="bi bi-pencil-square"></i> Edit Tagihan
                    </a>
                @endif
                <button type="button" class="btn-hero" data-bs-toggle="modal" data-bs-target="#modalAktivitasTagihan">
                    <i class="bi bi-activity"></i> Lihat Aktivitas
                </button>
                <a href="{{ route('contracts.index') }}" class="btn-hero">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
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
            <div class="modern-card" style="animation: secIn .55s cubic-bezier(.22,1,.36,1) .12s both;">
                <div class="mc-head">
                    <h6><i class="bi bi-receipt mc-h-icon icon-info"></i> Ringkasan Tagihan & Finansial</h6>
                </div>
                <div class="mc-body">
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
            <div class="modern-card" style="animation: secIn .55s cubic-bezier(.22,1,.36,1) .24s both;">
                <div class="mc-head">
                    <h6><i class="bi bi-file-earmark-check mc-h-icon icon-success"></i> Legalitas Pekerjaan</h6>
                </div>
                <div class="mc-body">
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

            {{-- Pejabat penanda tangan dokumen pencairan (snapshot saat tagihan dibuat) --}}
            <div class="modern-card" style="animation: secIn .55s cubic-bezier(.22,1,.36,1) .36s both;">
                <div class="mc-head">
                    <div>
                        <h6><i class="bi bi-people-fill mc-h-icon icon-primary"></i> Pejabat Penanda Tangan</h6>
                        <small class="text-muted d-block mt-1">Daftar pejabat penanda tangan dokumen pencairan (SPP/SPM/NPI/SP2D)</small>
                    </div>
                    <div class="text-end">
                        <span class="badge {{ $verifikatorLengkap ? 'bg-success' : 'bg-warning text-dark' }} fs-6">
                            <i class="bi bi-{{ $verifikatorLengkap ? 'check-circle' : 'exclamation-triangle' }} me-1"></i>
                            {{ $verifikatorTerisi }}/{{ $verifikatorTotal }} terisi
                        </span>
                    </div>
                </div>
                <div class="mc-body">
                    @php
                        // Peran nyata tiap pejabat pada rantai dokumen pencairan —
                        // supaya jelas mengapa mereka tercantum di tagihan ini.
                        $tugasMap = [
                            'ppk' => [
                                ['ikon' => 'bi-file-earmark-text', 'label' => 'Verifikasi SPP'],
                                ['ikon' => 'bi-receipt', 'label' => 'Verifikasi NPI'],
                                ['ikon' => 'bi-patch-check', 'label' => 'Terbit SP2D'],
                            ],
                            'ppspm' => [
                                ['ikon' => 'bi-file-earmark-ruled', 'label' => 'Verifikasi SPM'],
                            ],
                            'bendahara_pengeluaran' => [
                                ['ikon' => 'bi-send', 'label' => 'Ajukan NPI'],
                                ['ikon' => 'bi-cash-coin', 'label' => 'Bukti Transfer SP2D'],
                            ],
                            'bendahara_penerimaan' => [
                                ['ikon' => 'bi-receipt', 'label' => 'Verifikasi NPI'],
                            ],
                            'koordinator_keuangan' => [
                                ['ikon' => 'bi-file-earmark-text', 'label' => 'Verifikasi SPP'],
                                ['ikon' => 'bi-file-earmark-ruled', 'label' => 'Verifikasi SPM'],
                                ['ikon' => 'bi-receipt', 'label' => 'Verifikasi NPI'],
                            ],
                            'kasubbag' => [
                                ['ikon' => 'bi-file-earmark-text', 'label' => 'Verifikasi SPP'],
                                ['ikon' => 'bi-file-earmark-ruled', 'label' => 'Verifikasi SPM'],
                                ['ikon' => 'bi-receipt', 'label' => 'Verifikasi NPI'],
                            ],
                        ];
                    @endphp
                    <div class="row g-3">
                        @foreach($verifikatorList as $v)
                            @php $filled = !empty($v['nama']); @endphp
                            <div class="col-md-6 col-xl-4">
                                <div class="pj-card {{ $filled ? '' : 'is-empty' }}" style="--pj-color: {{ $v['color'] }};">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="pj-avatar">
                                            @if($filled)
                                                {{ $initials($v['nama']) }}
                                            @else
                                                <i class="bi bi-person-dash"></i>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1 min-width-0">
                                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                                <span class="pj-role">{{ $v['short'] }}</span>
                                            </div>
                                            <div class="pj-name">{{ $v['nama'] ?: 'Belum dipilih' }}</div>
                                            @if($v['nip'])
                                                <span class="pj-nip"><i class="bi bi-person-vcard"></i>{{ $v['nip'] }}</span>
                                            @elseif($filled)
                                                <span class="pj-nip fst-italic"><i class="bi bi-person-vcard"></i>NIP belum tersedia</span>
                                            @endif
                                            <div class="pj-jabatan" title="{{ $v['label'] }}">{{ $v['label'] }}</div>
                                        </div>
                                    </div>

                                    <div class="pj-tasks">
                                        <div class="pj-tasks-label"><i class="bi bi-vector-pen"></i> Menandatangani</div>
                                        <div>
                                            @foreach($tugasMap[$v['key']] ?? [] as $tugas)
                                                <span class="pj-task"><i class="bi {{ $tugas['ikon'] }}"></i>{{ $tugas['label'] }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @unless($verifikatorLengkap)
                        <div class="alert alert-warning border-0 small mb-0 mt-3 py-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Beberapa verifikator belum terisi. Tagihan lama mungkin dibuat sebelum fitur ini ada.
                        </div>
                    @endunless
                </div>
            </div>

            {{-- Dokumen Berita Acara Final --}}
            <div class="modern-card" style="animation: secIn .55s cubic-bezier(.22,1,.36,1) .48s both;">
                <div class="mc-head">
                    <h6><i class="bi bi-folder-check mc-h-icon icon-secondary"></i> Manajemen Dokumen Berita Acara</h6>
                </div>
                <div class="mc-body">
                    @php
                        // Dokumen dianggap FINAL (ber-TTE PPK) setelah seluruh verifikator,
                        // termasuk PPK, menyetujui tagihan di workflow.
                        $dokumenFinalTte = \App\Support\TagihanDocumentTte::isApproved($tagihan);
                    @endphp
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
                            <p class="small text-muted mb-3">Unggah <strong>Gambar RAB</strong> terlebih dahulu, lalu cetak draft PDF BAPP yang akan menampilkan gambar tersebut. Setelah ditandatangani, scan & unggah versi finalnya.</p>

                            {{-- Status Gambar RAB --}}
                            <div class="border rounded p-2 mb-3 d-flex align-items-center justify-content-between {{ $hasGambarRabBapp ? 'bg-light border-success' : 'bg-warning-subtle border-warning' }}">
                                <div class="small">
                                    @if($hasGambarRabBapp)
                                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                                        <span class="fw-semibold">Gambar RAB sudah diunggah</span>
                                    @else
                                        <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                                        <span class="fw-semibold">Gambar RAB belum diunggah</span>
                                    @endif
                                </div>
                                @if($hasGambarRabBapp && $gambarRabBapp)
                                    <a href="{{ route('tagihan.kontrak.view-arsip', [$tagihan->id, $gambarRabBapp->id]) }}" target="_blank" class="btn btn-sm btn-link p-0 text-decoration-none">
                                        <i class="bi bi-eye"></i> Lihat
                                    </a>
                                @endif
                            </div>

                            <div class="d-grid gap-2">
                                @if($tagihan->status === 'DRAFT' && !$hasBappFinal)
                                    @if($hasGambarRabBapp)
                                        <a href="{{ route('tagihan.kontrak.export-pdf', [$tagihan->id, 'BAPP']) }}" target="_blank" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-file-pdf me-1"></i> Preview Draft BAPP
                                        </a>
                                    @else
                                        <button type="button" class="btn btn-outline-danger btn-sm" disabled title="Gambar RAB belum diunggah saat pembuatan">
                                            <i class="bi bi-file-pdf me-1"></i> Preview Draft BAPP
                                        </button>
                                    @endif
                                @endif
                                @if($dokumenFinalTte)
                                    <a href="{{ route('tagihan.kontrak.export-pdf', [$tagihan->id, 'BAPP']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="bi bi-patch-check-fill me-1"></i> Lihat Dokumen Final (TTE PPK)
                                    </a>
                                @elseif($hasBappFinal)
                                    <a href="{{ route('tagihan.kontrak.export-pdf', [$tagihan->id, 'BAPP']) }}" target="_blank" class="btn btn-sm btn-success text-white">
                                        <i class="bi bi-file-pdf me-1"></i> Preview BAPP (Menunggu PPK)
                                    </a>
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
                                @if($tagihan->status === 'DRAFT' && !$hasBastFinal)
                                    <a href="{{ route('tagihan.kontrak.export-pdf', [$tagihan->id, 'BAST']) }}" target="_blank" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-file-pdf me-1"></i> Preview Draft BAST
                                    </a>
                                @endif
                                @if($dokumenFinalTte)
                                    <a href="{{ route('tagihan.kontrak.export-pdf', [$tagihan->id, 'BAST']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="bi bi-patch-check-fill me-1"></i> Lihat Dokumen Final (TTE PPK)
                                    </a>
                                @elseif($hasBastFinal)
                                    <a href="{{ route('tagihan.kontrak.export-pdf', [$tagihan->id, 'BAST']) }}" target="_blank" class="btn btn-sm btn-success text-white">
                                        <i class="bi bi-file-pdf me-1"></i> Preview BAST (Menunggu PPK)
                                    </a>
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
                                @if($tagihan->status === 'DRAFT' && !$hasBapFinal)
                                    <a href="{{ route('tagihan.kontrak.export-pdf', [$tagihan->id, 'BAP']) }}" target="_blank" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-file-pdf me-1"></i> Preview Draft BAP
                                    </a>
                                @endif
                                @if($dokumenFinalTte)
                                    <a href="{{ route('tagihan.kontrak.export-pdf', [$tagihan->id, 'BAP']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="bi bi-patch-check-fill me-1"></i> Lihat Dokumen Final (TTE PPK)
                                    </a>
                                @elseif($hasBapFinal)
                                    <a href="{{ route('tagihan.kontrak.export-pdf', [$tagihan->id, 'BAP']) }}" target="_blank" class="btn btn-sm btn-success text-white">
                                        <i class="bi bi-file-pdf me-1"></i> Preview BAP (Menunggu PPK)
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                    </div>

                    {{-- Pengiriman Akses TTE Terpadu via WhatsApp --}}
                    @php
                        $allSigs = $tagihan->documentSignatures;
                        $vendorSigs = $allSigs->where('role', 'vendor')->sortBy('id');
                        $pemeriksaSigs = $allSigs->where('role', 'tim_pemeriksa')->sortBy('id');
                        $isTteSent = $allSigs->count() > 0;
                        $allTteSigned = $isTteSent && $allSigs->every(fn($s) => $s->status === 'signed');
                        $vendorGroupToken = optional($vendorSigs->first())->group_token;
                        $pemeriksaGroupToken = optional($pemeriksaSigs->first())->group_token;
                        $vendorDocLabels = $vendorSigs->pluck('document_label')->implode(', ');
                        $vendorSigned = $vendorSigs->count() > 0 && $vendorSigs->every(fn($s) => $s->status === 'signed');
                        $pemeriksaSigned = $pemeriksaSigs->count() > 0 && $pemeriksaSigs->every(fn($s) => $s->status === 'signed');
                    @endphp

                    @if(in_array($tagihan->status, ['APPROVED', 'DISETUJUI_KONTRAK', 'READY_FOR_SPP', 'PROSES_SPP', 'SELESAI']))
                        @php
                            $sigByLabel = $vendorSigs->keyBy('document_label');
                            $bapSignedVendor = optional($sigByLabel->get('BAP'))->status === 'signed';
                            $vendorDocPlan = collect(['BAP', 'BAPP'])
                                ->when($wajibBast, fn ($c) => $c->push('BAST'))
                                ->map(fn ($label) => [
                                    'label' => $label,
                                    'sig' => $sigByLabel->get($label),
                                    'wajib' => $label === 'BAP',
                                ]);
                        @endphp
                        <div class="ttv-card shadow-sm mt-4">
                            <div class="card-body p-4">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="ttv-head-ic"><i class="bi bi-vector-pen"></i></div>
                                        <div>
                                            <h6 class="fw-bold mb-1">Tanda Tangan Vendor</h6>
                                            <p class="small text-muted mb-0" style="max-width: 56ch;">
                                                Draft dokumen pencairan (SPP) baru dibuat setelah <strong>BAP</strong> vendor terunggah;
                                                BAPP{{ $wajibBast ? '/BAST' : '' }} dapat menyusul. Pada jalur TTE online, dokumen
                                                <strong>BAPP</strong> menunggu persetujuan TTE <strong>Tim Pemeriksa</strong>;
                                                pada unggah manual, scan BAPP harus sudah memuat TTD Pemeriksa.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1 align-items-center">
                                        @foreach($vendorDocPlan as $doc)
                                            @php $docSigned = optional($doc['sig'])->status === 'signed'; @endphp
                                            <span class="ttv-doc {{ $docSigned ? 'is-signed' : ($doc['wajib'] ? 'is-wajib' : '') }}"
                                                  title="{{ $docSigned ? 'Sudah ditandatangani vendor' : ($doc['wajib'] ? 'Wajib sebelum draft SPP' : 'Dapat menyusul') }}">
                                                <i class="bi {{ $docSigned ? 'bi-check-circle-fill' : ($doc['wajib'] ? 'bi-exclamation-circle-fill' : 'bi-clock-history') }}"></i>
                                                {{ $doc['label'] }}{{ $docSigned ? '' : ($doc['wajib'] ? ' · wajib' : ' · menyusul') }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>

                                @unless($vendorSigned)
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="ttv-choice sel" id="pilihTteCard">
                                                <input type="radio" name="metode_ttd" value="tte" checked onchange="gantiMetodeTtd()">
                                                <span class="ttv-check"><i class="bi bi-check-lg"></i></span>
                                                <span class="ttv-choice-ic"><i class="bi bi-whatsapp"></i></span>
                                                <span>
                                                    <span class="fw-bold d-flex align-items-center gap-2 flex-wrap">Vendor TTE Online
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Direkomendasikan</span>
                                                    </span>
                                                    <span class="small text-muted d-block mt-1">Sistem mengirim tautan tanda tangan elektronik ke WhatsApp/email vendor. Vendor meninjau dokumen lalu menyetujui &amp; mengunggah sendiri.</span>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="ttv-choice is-manual" id="pilihManualCard">
                                                <input type="radio" name="metode_ttd" value="manual" onchange="gantiMetodeTtd()">
                                                <span class="ttv-check"><i class="bi bi-check-lg"></i></span>
                                                <span class="ttv-choice-ic"><i class="bi bi-cloud-arrow-up-fill"></i></span>
                                                <span>
                                                    <span class="fw-bold">Unggah Manual oleh Staf</span>
                                                    <span class="small text-muted d-block mt-1">Vendor sudah menandatangani basah &amp; menstempel dokumen fisik. Staf mengunggah hasil scan-nya atas nama vendor — tercatat di log audit.</span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="ttv-actions d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                        <span class="small text-muted d-flex align-items-center gap-2" style="max-width: 56ch;">
                                            <i class="bi bi-info-circle flex-shrink-0"></i>
                                            <span id="metodeTtdNote">Tautan berlaku 24 jam dan hanya bisa dibuka penerima. Metode dapat diganti selama dokumen belum selesai.</span>
                                        </span>
                                        <div id="aksiMetodeTte">
                                            <form action="{{ route('tagihan.kontrak.send-tte', $tagihan->id) }}" method="POST" class="m-0">
                                                @csrf
                                                <button type="submit" class="btn px-4 fw-semibold {{ $isTteSent ? 'btn-outline-warning' : 'btn-success text-white' }}">
                                                    <i class="bi bi-whatsapp me-1"></i> {{ $isTteSent ? 'Kirim Ulang Akses TTE' : 'Kirim Akses TTE' }}
                                                </button>
                                            </form>
                                        </div>
                                        <div id="aksiMetodeManual" class="d-none">
                                            <button type="button" class="btn btn-ttv-manual px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTtdManual">
                                                <i class="bi bi-cloud-arrow-up-fill me-1"></i> Unggah Dokumen Manual…
                                            </button>
                                        </div>
                                    </div>
                                @endunless

                                @if($isTteSent)
                                    <div class="row g-3">
                                        {{-- Vendor --}}
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 h-100 bg-white">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="fw-semibold"><i class="bi bi-shop me-1 text-primary"></i> Vendor</span>
                                                    @if($vendorSigned)
                                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Selesai</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Menunggu</span>
                                                    @endif
                                                </div>
                                                <div class="small text-muted mb-1">{{ $vendorSigs->first()->signer_name ?? '-' }}</div>
                                                <div class="small text-muted mb-2"><i class="bi bi-whatsapp me-1"></i>{{ $vendorSigs->first()->signer_phone ?? '-' }}</div>
                                                <div class="small mb-2 d-flex flex-wrap align-items-center gap-1">
                                                    <span>Dokumen:</span>
                                                    @foreach($vendorSigs as $vs)
                                                        @if($vs->status === 'signed')
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle-fill me-1"></i>{{ $vs->document_label }}</span>
                                                            @if($vs->signed_via === 'MANUAL')
                                                                <span class="badge border" style="background:#FDF3E3;color:#B45309;border-color:#F1DFBB!important;" title="Diunggah manual oleh {{ $vs->signedByUser->name ?? 'staf' }}"><i class="bi bi-vector-pen me-1"></i>Manual</span>
                                                            @else
                                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-link-45deg me-1"></i>TTE Online</span>
                                                            @endif
                                                        @else
                                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle"><i class="bi bi-clock me-1"></i>{{ $vs->document_label }}{{ $vs->document_label === 'BAP' ? ' (wajib)' : ' (menyusul)' }}</span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                                @if($vendorGroupToken)
                                                    <div class="small d-flex"><i class="bi bi-link-45deg me-1"></i>
                                                        <a href="{{ url('/public/tte/sign/' . $vendorGroupToken) }}" target="_blank" class="text-decoration-none text-truncate d-inline-block align-middle" style="max-width: 240px;" title="{{ url('/public/tte/sign/' . $vendorGroupToken) }}">{{ url('/public/tte/sign/' . $vendorGroupToken) }}</a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        {{-- Pemeriksa --}}
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 h-100 bg-white">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="fw-semibold"><i class="bi bi-person-badge me-1 text-primary"></i> Pemeriksa</span>
                                                    @if($pemeriksaSigned)
                                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Selesai</span>
                                                    @elseif($pemeriksaSigs->isEmpty())
                                                        <span class="badge bg-secondary"><i class="bi bi-send-slash me-1"></i>Belum Dikirim</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Menunggu</span>
                                                    @endif
                                                </div>
                                                {{-- Fallback ke data pemeriksa pada tagihan bila akses TTE
                                                     belum pernah dikirim (mis. jalur unggah manual). --}}
                                                <div class="small text-muted mb-1">{{ $pemeriksaSigs->first()->signer_name ?? $detailKontrak->nama_pemeriksa ?? '-' }}</div>
                                                <div class="small text-muted mb-2"><i class="bi bi-whatsapp me-1"></i>{{ $pemeriksaSigs->first()->signer_phone ?? $detailKontrak->wa_pemeriksa ?? '-' }}</div>
                                                <div class="small mb-2">Dokumen: <strong>BAPP</strong></div>
                                                @if($pemeriksaGroupToken)
                                                    <div class="small d-flex"><i class="bi bi-link-45deg me-1"></i>
                                                        <a href="{{ url('/public/tte/sign/' . $pemeriksaGroupToken) }}" target="_blank" class="text-decoration-none text-truncate d-inline-block align-middle" style="max-width: 240px;" title="{{ url('/public/tte/sign/' . $pemeriksaGroupToken) }}">{{ url('/public/tte/sign/' . $pemeriksaGroupToken) }}</a>
                                                    </div>
                                                @elseif(! $pemeriksaSigned)
                                                    <div class="small text-muted fst-italic">
                                                        <i class="bi bi-info-circle me-1"></i>Kirim akses TTE untuk meminta persetujuan Pemeriksa, atau unggah BAPP manual (scan ber-TTD Pemeriksa).
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Modal Unggah Manual TTD Basah Vendor --}}
                        @include('partials.unggah-manual-assets')
                        <div class="modal fade um-modal" id="modalTtdManual" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <form action="{{ route('tagihan.kontrak.ttd-manual', $tagihan->id) }}" method="POST" enctype="multipart/form-data" class="modal-content">
                                    @csrf
                                    <div class="modal-header">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="um-head-ic"><i class="bi bi-vector-pen"></i></div>
                                            <div>
                                                <h6 class="modal-title mb-0">Unggah Manual — TTD Basah Vendor</h6>
                                                <small>{{ $kontrak->vendor->nama_pihak ?? '-' }} · {{ $tagihan->nomor_tagihan }}</small>
                                            </div>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                    </div>
                                    <div class="modal-body d-flex flex-column gap-3">
                                        @foreach($vendorDocPlan as $doc)
                                            @php
                                                $docSelesai = optional($doc['sig'])->status === 'signed';
                                                $docIkon = ['BAP' => 'bi-file-earmark-check', 'BAPP' => 'bi-clipboard-check', 'BAST' => 'bi-box-seam'][$doc['label']] ?? 'bi-file-earmark';
                                            @endphp
                                            @if($docSelesai)
                                                <div class="um-doc um-done">
                                                    <div class="um-doc-ic"><i class="bi bi-check-circle-fill"></i></div>
                                                    <div>
                                                        <div class="um-doc-name">{{ $doc['label'] }}
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Selesai</span>
                                                        </div>
                                                        <div class="um-doc-sub">Sudah ditandatangani — tidak perlu diunggah lagi.</div>
                                                    </div>
                                                </div>
                                            @else
                                                <label class="um-doc">
                                                    <div class="um-doc-ic"><i class="bi {{ $docIkon }}"></i></div>
                                                    <div>
                                                        <div class="um-doc-name">
                                                            {{ $doc['label'] }}
                                                            @if($doc['wajib'])
                                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Wajib</span>
                                                            @else
                                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Dapat menyusul</span>
                                                            @endif
                                                        </div>
                                                        <div class="um-doc-sub">
                                                            @if($doc['label'] === 'BAPP')
                                                                Scan PDF ber-TTD <strong>vendor &amp; Tim Pemeriksa</strong> + stempel · maks 10 MB · bisa seret &amp; lepas
                                                            @else
                                                                Scan PDF ber-TTD &amp; stempel · maks 10 MB · bisa seret &amp; lepas
                                                            @endif
                                                        </div>
                                                        <span class="um-doc-file"><i class="bi bi-check-circle-fill"></i><span class="um-file-name"></span><span class="um-file-size text-muted fw-normal"></span></span>
                                                    </div>
                                                    <span class="um-doc-action">Pilih PDF</span>
                                                    <input type="file" name="dokumen[{{ $doc['label'] }}_FINAL_TTD]" accept="application/pdf,.pdf" hidden class="{{ $doc['wajib'] ? 'um-wajib' : '' }}">
                                                </label>
                                            @endif
                                        @endforeach

                                        <div class="row g-2">
                                            <div class="col-md-5">
                                                <label class="form-label small fw-bold mb-1">Tanggal TTD vendor <span class="text-danger">*</span></label>
                                                <input type="date" name="tanggal_ttd_vendor" class="form-control form-control-sm" required max="{{ now()->toDateString() }}">
                                            </div>
                                            <div class="col-md-7">
                                                <label class="form-label small fw-bold mb-1">Keterangan <span class="text-muted fw-normal">(opsional)</span></label>
                                                <input type="text" name="keterangan" class="form-control form-control-sm" maxlength="1000" placeholder="Contoh: ditandatangani saat serah terima di lokasi.">
                                            </div>
                                        </div>

                                        <label class="um-declare">
                                            <input type="checkbox" name="pernyataan" value="1" id="pernyataanTtdManual">
                                            <span class="small">
                                                <i class="bi bi-patch-check-fill um-declare-ic me-1"></i>
                                                Saya menyatakan dokumen yang diunggah <strong>benar telah ditandatangani basah dan distempel oleh vendor</strong>
                                                — untuk BAPP, <strong>termasuk TTD Tim Pemeriksa</strong> — dan saya bertanggung jawab atas keasliannya.
                                            </span>
                                        </label>

                                        <div class="small text-muted d-flex gap-2">
                                            <i class="bi bi-shield-check flex-shrink-0"></i>
                                            <span>Tindakan ini dicatat di log audit (nama Anda, waktu, alamat IP) dan terlihat oleh seluruh verifikator sebagai unggahan <strong>Manual</strong>.</span>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <span class="um-counter"><i class="bi bi-files"></i><span>0 dari 0 dokumen dipilih</span></span>
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="um-submit" id="btnSimpanTtdManual" disabled>
                                            <i class="bi bi-cloud-arrow-up-fill me-1"></i> Simpan &amp; Tandai Ditandatangani
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <script>
                            function gantiMetodeTtd() {
                                var manual = document.querySelector('input[name="metode_ttd"]:checked')?.value === 'manual';
                                document.getElementById('pilihTteCard')?.classList.toggle('sel', !manual);
                                document.getElementById('pilihManualCard')?.classList.toggle('sel', manual);
                                document.getElementById('aksiMetodeTte')?.classList.toggle('d-none', manual);
                                document.getElementById('aksiMetodeManual')?.classList.toggle('d-none', !manual);
                                var note = document.getElementById('metodeTtdNote');
                                if (note) {
                                    note.textContent = manual
                                        ? 'Membuka formulir unggah dengan pernyataan tanggung jawab. Tindakan dicatat di log audit dan berlencana "Manual".'
                                        : 'Tautan berlaku 24 jam dan hanya bisa dibuka penerima. Metode dapat diganti selama dokumen belum selesai.';
                                }
                            }
                        </script>
                    @endif
                </div>
            </div>
        </div>

        {{-- Area Kanan: Status Kelengkapan --}}
        <div class="col-lg-4">
            <div class="timeline-card z-1">
                <div class="tl-head modal-grad-primary">
                    <h6><i class="bi bi-ui-checks"></i> Status & Kelengkapan</h6>
                </div>
                <div class="tl-body" style="padding: 1.25rem 1.5rem;">
                    @if($tagihan->status === 'DRAFT')
                        <h5 class="fw-bold mb-1">Syarat Pengajuan</h5>
                        <p class="text-muted small mb-3">Pastikan seluruh checklist di bawah terpenuhi. Setelah diajukan, tagihan langsung siap diproses — tanpa tahap verifikasi — dan berlanjut ke dokumen Berita Acara (BAP wajib; BAPP/BAST dapat menyusul).</p>

                        {{-- Checklist Berita Acara --}}
                        <div class="text-uppercase text-muted small fw-bold mb-2" style="letter-spacing: .5px;">Kelengkapan Awal</div>
                        <ul class="list-group mb-3">
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-1">
                                <span><i class="bi bi-{{ $hasGambarRabBapp ? 'check-circle-fill text-success' : 'circle text-secondary' }} me-2"></i>Gambar RAB BAPP</span>
                                @if($hasGambarRabBapp)<span class="badge bg-success-subtle text-success">OK</span>@endif
                            </li>
                        </ul>

                        {{-- Checklist Pejabat Penanda Tangan --}}
                        <div class="text-uppercase text-muted small fw-bold mb-2 mt-3" style="letter-spacing: .5px;">Pejabat Penanda Tangan</div>
                        <ul class="list-group mb-3">
                            @foreach($verifikatorList as $v)
                                <li class="list-group-item d-flex justify-content-between align-items-start gap-2 border-0 px-0 py-1">
                                    <span style="max-width: 78%;">
                                        <i class="bi bi-{{ !empty($v['nama']) ? 'check-circle-fill text-success' : 'circle text-secondary' }} me-2"></i>{{ $v['label'] }}
                                    </span>
                                    @if(!empty($v['nama']))
                                        <span class="badge bg-success-subtle text-success" title="{{ $v['nama'] }}">OK</span>
                                    @else
                                        <span class="badge bg-warning text-dark">—</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        {{-- Action button --}}
                        @if($isReadyToSubmit && $verifikatorLengkap)
                            <div class="alert alert-success border-0 small mb-3 py-2">
                                <i class="bi bi-check-circle me-1"></i> Tagihan siap diajukan.
                            </div>
                            <form action="{{ route('tagihan.kontrak.submit', $tagihan->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm">
                                    <i class="bi bi-send me-1"></i> Ajukan Tagihan
                                </button>
                            </form>
                        @else
                            <div class="alert alert-warning border-0 small mb-3 py-2">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                @if(!$verifikatorLengkap)
                                    Verifikator belum lengkap. Hubungi Pejabat Pengadaan untuk melengkapi.
                                @else
                                    Unggah dokumen Gambar RAB BAPP untuk melanjutkan.
                                @endif
                            </div>
                            <button type="button" class="btn btn-secondary w-100 fw-bold py-2" disabled>
                                <i class="bi bi-send me-1"></i> Ajukan Tagihan
                            </button>
                        @endif
                    @else
                        <h5 class="fw-bold mb-3">Informasi Status Tagihan</h5>
                        <div class="text-center py-3 mb-3 border-bottom">
                            @php
                                $st = $tagihan->status;
                                $isApproved  = in_array($st, ['APPROVED','DISETUJUI_KONTRAK','READY_FOR_SPP'], true);
                                $isInSpp     = in_array($st, ['PROSES_SPP','SEBAGIAN_SPP_TERBIT','SPP_TERBIT','SPP_LENGKAP'], true);
                                $isRejected  = str_starts_with($st, 'DITOLAK_');
                                $isRevisi    = str_starts_with($st, 'REVISI_');
                                $isPending   = str_starts_with($st, 'PENDING_');
                            @endphp

                            @if($isApproved)
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3 text-success">Tagihan Siap Diproses</h6>
                                <p class="text-muted small">Lengkapi tanda tangan vendor pada dokumen Berita Acara — BAP wajib sebelum draft SPP dibuat; BAPP/BAST dapat menyusul.</p>
                            @elseif($isInSpp)
                                <i class="bi bi-arrow-right-circle-fill text-primary" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3 text-primary">
                                    @switch($st)
                                        @case('PROSES_SPP') Sedang Diproses ke SPP @break
                                        @case('SEBAGIAN_SPP_TERBIT') Sebagian SPP Terbit @break
                                        @case('SPP_TERBIT') SPP Terbit @break
                                        @case('SPP_LENGKAP') SPP Lengkap @break
                                    @endswitch
                                </h6>
                                <p class="text-muted small">
                                    Tagihan telah disetujui dan sudah masuk tahap pembuatan SPP oleh Operator BLU.
                                    Verifikasi pengadaan untuk tagihan ini sudah selesai.
                                </p>
                            @elseif($isRejected)
                                <i class="bi bi-x-octagon text-danger" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3 text-danger">Tagihan Ditolak</h6>
                                <p class="text-muted small">{{ $statusBadge['label'] }}. Workflow telah dihentikan.</p>
                            @elseif($isRevisi)
                                <i class="bi bi-arrow-counterclockwise text-warning" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3 text-warning">Revisi Diperlukan</h6>
                                <p class="text-muted small">{{ $statusBadge['label'] }}. Silakan perbaiki tagihan dan ajukan ulang.</p>
                            @elseif($st === 'PENDING_VERIFIKASI_KONTRAK')
                                @php
                                    $wfStep1 = optional($tagihan->workflowInstance)->approvals?->where('urutan_step', 1) ?? collect();
                                    $wfStep1Approved = $wfStep1->where('status', 'APPROVED')->count();
                                    $wfStep1Total = $wfStep1->count();
                                @endphp
                                <i class="bi bi-people-fill text-info" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3 text-info">Verifikasi Paralel Berjalan</h6>
                                <p class="text-muted small">
                                    <strong>{{ $wfStep1Approved }} dari {{ $wfStep1Total }}</strong> verifikator paralel sudah menyetujui.
                                    Tagihan akan lanjut ke Kasubbag setelah semua selesai.
                                </p>
                                @if($wfStep1Total > 0)
                                    <div class="progress mt-2 mb-3" style="height: 6px;">
                                        <div class="progress-bar bg-info" style="width: {{ round(($wfStep1Approved / $wfStep1Total) * 100) }}%"></div>
                                    </div>
                                @endif
                            @elseif($st === 'PENDING_KASUBBAG')
                                <i class="bi bi-shield-check text-primary" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3 text-primary">Menunggu Persetujuan Kasubbag</h6>
                                <p class="text-muted small">5 verifikator paralel sudah menyetujui. Menunggu finalisasi Kepala Subbagian Keuangan dan Tata Usaha.</p>
                            @elseif($isPending)
                                <i class="bi bi-hourglass-split text-info" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3 text-info">{{ $statusBadge['label'] }}</h6>
                                <p class="text-muted small">Tagihan sedang dalam proses verifikasi.</p>
                            @else
                                <i class="bi bi-question-circle text-muted" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3 text-muted">{{ $statusBadge['label'] }}</h6>
                                <p class="text-muted small">Status tagihan tidak dikenali.</p>
                            @endif
                        </div>
                        
                        <h6 class="fw-bold text-secondary mb-2 fs-6">Kelengkapan Terlampir</h6>
                        <ul class="list-group mb-3">
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-1">
                                <span><i class="bi bi-check-circle-fill me-2 small text-success"></i>BAPP Final</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-1">
                                <span><i class="bi bi-check-circle-fill me-2 small text-success"></i>BAP Final</span>
                            </li>
                            @if($wajibBast)
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-1">
                                <span><i class="bi bi-check-circle-fill me-2 small text-success"></i>BAST Final</span>
                            </li>
                            @endif
                        </ul>

                        <h6 class="fw-bold text-secondary mb-2 fs-6">Verifikator Tercatat</h6>
                        <ul class="list-group mb-0">
                            @foreach($verifikatorList as $v)
                                @if(!empty($v['nama']))
                                    <li class="list-group-item border-0 px-0 py-1">
                                        <div class="d-flex align-items-start gap-2">
                                            <span class="role-chip mt-1" style="background: {{ $v['color'] }}1a; color: {{ $v['color'] }}; flex-shrink:0;">{{ $v['short'] }}</span>
                                            <div class="small flex-grow-1">
                                                <div class="fw-semibold text-truncate">{{ $v['nama'] }}</div>
                                                @if($v['nip'])<div class="text-muted font-monospace" style="font-size: .72rem;">NIP: {{ $v['nip'] }}</div>@endif
                                            </div>
                                        </div>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Lihat Aktivitas Tagihan --}}
@include('tagihan.partials.aktivitas-modal')

{{-- Modals for Uploads (Removed as TTE replaces manual uploads) --}}

@endsection
