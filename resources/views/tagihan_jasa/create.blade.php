@extends('layouts.app')
@php
    $isKonsesiMode = ($mode ?? 'pnbp') === 'konsesi' || !empty($prefillTagihan['penjualan_id']);
    $isKonsesiSetupMode = $isKonsesiMode && empty($prefillTagihan['penjualan_id']);
    $pageTitle = $isKonsesiMode
        ? ($isKonsesiSetupMode ? 'Penetapan Layanan Konsesi' : 'Buat Tagihan Konsesi')
        : 'Buat Tagihan Jasa';
    $detailTitle = $isKonsesiSetupMode
        ? 'Daftar Layanan Konsesi Awal'
        : ($isKonsesiMode ? 'Daftar Tagihan Konsesi' : 'Daftar Layanan Jasa');
@endphp
@section('title', $pageTitle)

@push('css')
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = { corePlugins: { preflight: false } };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        @keyframes invoiceCardIn {
            from {
                opacity: 0;
                transform: translateY(18px) scale(.985);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        @keyframes blueHeaderGlow {
            0%, 100% { opacity: .55; transform: translate3d(-28px, 0, 0) scale(1); }
            50% { opacity: .95; transform: translate3d(72px, -18px, 0) scale(1.12); }
        }
        @keyframes blueHeaderSweep {
            0% { transform: translateX(-120%) skewX(-18deg); opacity: 0; }
            18% { opacity: .35; }
            45%, 100% { transform: translateX(220%) skewX(-18deg); opacity: 0; }
        }
        .tw-invoice-card {
            animation: invoiceCardIn .58s cubic-bezier(.2,.8,.2,1) both;
            transform-origin: center top;
            transition: transform .24s ease, box-shadow .24s ease, border-color .24s ease;
        }
        .tw-invoice-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 24px 60px rgba(15, 23, 42, .12);
            border-color: rgba(59, 130, 246, .24);
        }
        .tw-invoice-card:nth-of-type(2) {
            animation-delay: .08s;
        }
        .blue-animated-header {
            position: relative;
            isolation: isolate;
        }
        .blue-animated-header::before,
        .blue-animated-header::after,
        .blue-animated-header .blue-header-wave {
            content: "";
            position: absolute;
            pointer-events: none;
            z-index: -1;
        }
        .blue-animated-header::before {
            width: 360px;
            height: 360px;
            right: 8%;
            top: -170px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(125, 211, 252, .32), rgba(59, 130, 246, .22) 42%, transparent 68%);
            animation: blueHeaderGlow 4.5s ease-in-out infinite;
        }
        .blue-animated-header::after {
            inset: 0;
            width: 48%;
            background: linear-gradient(90deg, transparent, rgba(125,211,252,.16), rgba(255,255,255,.24), rgba(96,165,250,.14), transparent);
            animation: blueHeaderSweep 3.8s ease-in-out infinite;
        }
        .blue-animated-header .blue-header-wave {
            left: -90px;
            bottom: -120px;
            width: 420px;
            height: 230px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(125, 211, 252, .22), transparent 65%);
            animation: blueHeaderGlow 5.2s ease-in-out infinite reverse;
        }
        .tw-invoice-input :is(.form-control, .form-select, .select2-selection) {
            border-color: #dbe3ef !important;
            border-radius: .85rem !important;
            min-height: 42px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
        }
        .tw-invoice-input :is(.form-control:focus, .form-select:focus) {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, .12) !important;
        }
        .invoice-primary-select {
            border: 1px solid #bfdbfe;
            border-radius: 1.15rem;
            background: linear-gradient(135deg, #eff6ff 0%, #ffffff 70%);
            padding: 14px;
            box-shadow: 0 16px 32px rgba(37, 99, 235, .08);
            overflow: hidden;
        }
        .invoice-primary-select .select2-container {
            display: block;
            max-width: 100%;
            width: 100% !important;
            box-sizing: border-box;
        }
        .invoice-primary-select .select2-container--bootstrap-5 .select2-selection,
        .invoice-primary-select .select2-container .select2-selection--single {
            min-height: 48px !important;
            border-color: #bfdbfe !important;
            background: #ffffff !important;
            font-weight: 800;
            width: 100% !important;
        }
        .invoice-info-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }
        .invoice-info-tile {
            border: 1px solid #dbeafe;
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff, #f8fbff);
            padding: 12px 14px;
            min-height: 74px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .04);
        }
        .invoice-info-tile label {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
            font-size: 11px;
            font-weight: 900;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .invoice-info-tile label i {
            color: #2563eb;
        }
        .invoice-info-tile .form-control {
            min-height: 0;
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            color: #0f172a;
            font-weight: 800;
            padding: 0;
        }
        .invoice-info-tile textarea.form-control {
            line-height: 1.45;
            resize: none;
        }
        .invoice-date-card,
        .invoice-document-card {
            border: 1px solid #dbeafe;
            border-radius: 18px;
            background: linear-gradient(135deg, #f8fbff, #ffffff);
            padding: 16px;
            box-shadow: 0 14px 30px rgba(37, 99, 235, .06);
        }
        .invoice-document-card {
            border-color: #fed7aa;
            background: linear-gradient(135deg, #fff7ed, #ffffff 72%);
        }
        .invoice-section-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #1e40af;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .02em;
            text-transform: uppercase;
        }
        @media (max-width: 991.98px) {
            .invoice-info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 575.98px) {
            .invoice-info-grid {
                grid-template-columns: 1fr;
            }
        }
        .tw-invoice-table {
            border-collapse: separate;
            border-spacing: 0;
        }
        .tw-invoice-table thead th {
            background: #f8fafc;
            border-color: #e2e8f0 !important;
            color: #475569;
            font-size: .72rem;
            letter-spacing: .02em;
            text-transform: uppercase;
        }
        .tw-invoice-table tbody tr {
            transition: background .2s ease, transform .2s ease;
        }
        .tw-invoice-table tbody tr:hover {
            background: #f0f9ff;
        }
        .tw-invoice-table tbody td {
            padding: .75rem .5rem;
            vertical-align: top;
        }
        .invoice-service-row {
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        }
        .invoice-field {
            position: relative;
        }
        .invoice-field-label {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            margin-bottom: .35rem;
            color: #64748b;
            font-size: .68rem;
            font-weight: 800;
            text-transform: uppercase;
        }
        .invoice-field-label i {
            color: #2563eb;
        }
        .invoice-field .form-control {
            background: #ffffff;
        }
        .invoice-muted-input {
            background: #f8fafc !important;
            color: #475569;
            font-weight: 700;
        }
        .invoice-subtotal {
            background: linear-gradient(135deg, #eff6ff, #ffffff) !important;
            border-color: #bfdbfe !important;
            color: #1d4ed8 !important;
            font-weight: 900 !important;
        }
        .smart-calc-wrapper {
            display: grid;
            gap: .5rem;
        }
        .smart-calc-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(92px, 1fr));
            gap: .45rem;
        }
        .smart-calc-field label {
            color: #64748b;
            font-size: .66rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: .22rem;
            text-transform: uppercase;
        }
        .smart-calc-field .form-control {
            min-height: 38px;
            padding-inline: .55rem;
        }
        .smart-calc-result {
            border: 1px solid #bfdbfe;
            border-radius: .85rem;
            background: linear-gradient(135deg, #eff6ff, #ffffff);
            color: #1e40af;
            font-size: .78rem;
            font-weight: 800;
            padding: .45rem .6rem;
        }
        .smart-calc-formula {
            color: #64748b;
            font-size: .72rem;
            line-height: 1.35;
        }
        .smart-calc-warning {
            border: 1px solid #fbbf24;
            border-radius: .75rem;
            background: #fffbeb;
            color: #92400e;
            font-size: .72rem;
            font-weight: 800;
            line-height: 1.35;
            padding: .45rem .6rem;
        }
        .invoice-search-button {
            border-color: #bfdbfe !important;
            background: #eff6ff !important;
            color: #1d4ed8 !important;
        }
        .invoice-search-button:hover {
            background: #dbeafe !important;
        }
        .invoice-remove-button {
            border-radius: .85rem !important;
            width: 38px;
            height: 38px;
        }
        .service-tree {
            max-height: 58vh;
            overflow: auto;
            border: 1px solid #dbe3ef;
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            padding: 12px;
        }
        .service-tree details {
            margin: 4px 0;
        }
        .service-tree summary {
            cursor: pointer;
            list-style: none;
            border-radius: 8px;
            padding: 8px 10px;
        }
        .service-tree summary::-webkit-details-marker {
            display: none;
        }
        .service-tree summary:hover,
        .service-tree .service-item:hover {
            background: #f8fafc;
        }
        .service-tree details .tree-folder-open {
            display: none;
        }
        .service-tree details[open] .tree-folder-open {
            display: inline-block;
        }
        .service-tree details[open] .tree-folder-closed {
            display: none;
        }
        .service-tree .tree-children {
            margin-left: 20px;
            padding-left: 14px;
            border-left: 1px solid #e2e8f0;
        }
        .service-tree .tree-label {
            font-weight: 700;
            color: #0f172a;
        }
        .service-tree .tree-meta {
            font-size: 11px;
            color: #64748b;
        }
        .service-tree .service-item {
            width: 100%;
            border: 0;
            background: transparent;
            text-align: left;
            border-radius: 8px;
            padding: 8px 10px;
        }
        .service-tree .service-item.active {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .service-tree .tree-branch {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            color: #64748b;
            margin-right: 6px;
        }
    </style>
@endpush

@section('content')
<div class="tw-scope">
<div class="blue-animated-header mb-6 overflow-hidden rounded-3xl border border-blue-900/20 bg-gradient-to-r from-[#12355c] via-[#174f86] to-[#1d65a6] px-6 py-6 shadow-[0_18px_50px_rgba(18,53,92,.22)] lg:px-8">
    <span class="blue-header-wave"></span>
    <div class="relative z-10 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-600/20">
                <i class="bi bi-receipt-cutoff text-2xl"></i>
            </div>
            <div>
                <div class="mb-2 inline-flex items-center rounded-full bg-white/12 px-3 py-1 text-xs font-bold text-blue-100 ring-1 ring-white/20">
                    {{ $isKonsesiMode ? 'Konsesi' : 'Tagihan Jasa' }}
                </div>
                <h4 class="mb-1 text-2xl font-black text-white lg:text-3xl">{{ $pageTitle }}</h4>
                <p class="mb-0 max-w-2xl text-sm font-semibold text-blue-100/80">
                    {{ $isKonsesiSetupMode ? 'Tetapkan layanan/persentase konsesi yang dapat dilaporkan mitra' : 'Masukkan informasi mitra dan layanan jasa yang ditagihkan' }}
                </p>
            </div>
        </div>
        <a href="{{ route('admin-jasa.tagihan.log-bulanan') }}" class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-sm font-bold text-blue-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-blue-50">
            <i class="bi bi-arrow-left me-2"></i>Kembali
        </a>
    </div>
</div>

@if($errors->any() || session('error'))
    <div class="alert alert-danger border-0 bg-danger text-white alert-dismissible fade show shadow-sm">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-2"></i>Terdapat kesalahan:</div>
        <ul class="mb-0">
            @if(session('error'))
                <li>{{ session('error') }}</li>
            @endif
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('tagihan-jasa.store') }}" method="POST" enctype="multipart/form-data" class="tw-invoice-input">
    @csrf
    <input type="hidden" name="tipe_pnbp" value="{{ $tipe }}">
    @if(!empty($prefillTagihan['penjualan_id']))
        <input type="hidden" name="penjualan_id" value="{{ $prefillTagihan['penjualan_id'] }}">
    @endif
    @if(!empty($prefillTagihan['utilitas_id']))
        <input type="hidden" name="utilitas_id" value="{{ $prefillTagihan['utilitas_id'] }}">
    @endif
    
    <div class="row">
        <!-- Section 1: Informasi Mitra & Dokumen Dasar -->
        <div class="col-12">
            <div class="tw-invoice-card mb-4 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_16px_42px_rgba(15,23,42,.07)]">
                <div class="border-b border-blue-200 bg-gradient-to-r from-blue-50 via-sky-50 to-white px-4 py-2.5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-lg bg-blue-700 text-white shadow-sm shadow-blue-600/20">
                                <i class="bi bi-building"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-base font-black text-blue-900">Informasi Mitra & Dokumen Dasar Tagihan</h6>
                                <p class="mb-0 text-xs font-bold text-slate-500">Pilih mitra, tanggal, dan dokumen pendukung bila tersedia.</p>
                            </div>
                        </div>
                        <span class="inline-flex w-fit items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700 ring-1 ring-blue-200">
                            Langkah 1
                        </span>
                    </div>
                </div>
                <div class="p-5">
                    <div class="invoice-primary-select mb-4">
                        <div class="mb-2 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <span class="invoice-section-kicker"><i class="bi bi-person-check"></i> Data Mitra</span>
                                <label class="form-label mb-0 mt-1 fw-bold">Pilih Mitra <span class="text-danger">*</span></label>
                            </div>
                            <a href="#" id="linkAturLayananMitra" class="d-none text-sm font-bold text-blue-700 no-underline">
                                <i class="bi bi-sliders me-1"></i>Atur layanan mitra
                            </a>
                        </div>
                        <select name="mitra_jasa_id" id="mitraSelect" class="form-select select2" required>
                            <option value="">-- Pilih Mitra --</option>
                            @foreach($mitras as $mitra)
                                <option value="{{ $mitra->id }}" {{ (string) old('mitra_jasa_id', $prefillTagihan['mitra_jasa_id'] ?? '') === (string) $mitra->id ? 'selected' : '' }}>
                                    {{ $mitra->nama_mitra }}
                                </option>
                            @endforeach
                        </select>
                        <div class="mt-2 text-xs font-semibold text-slate-500">
                            Pilih mitra terlebih dahulu agar detail identitas, kontrak, dan layanan aktif dapat dimuat otomatis.
                        </div>
                    </div>

                    <div class="mb-4 d-none" id="mitraInfoPanel">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <span class="invoice-section-kicker"><i class="bi bi-card-checklist"></i> Ringkasan Mitra</span>
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">Terpilih</span>
                        </div>
                        <div class="invoice-info-grid">
                            <div class="invoice-info-tile">
                                <label><i class="bi bi-hash"></i>Kode Mitra</label>
                                <input type="text" id="mitraKode" class="form-control" readonly>
                            </div>
                            <div class="invoice-info-tile">
                                <label><i class="bi bi-building"></i>Jenis Mitra</label>
                                <input type="text" id="mitraJenis" class="form-control" readonly>
                            </div>
                            <div class="invoice-info-tile">
                                <label><i class="bi bi-file-text"></i>NPWP</label>
                                <input type="text" id="mitraNpwp" class="form-control" readonly>
                            </div>
                            <div class="invoice-info-tile">
                                <label><i class="bi bi-envelope"></i>Email</label>
                                <input type="text" id="mitraEmail" class="form-control" readonly>
                            </div>
                            <div class="invoice-info-tile">
                                <label><i class="bi bi-whatsapp"></i>No Telepon/WA</label>
                                <input type="text" id="mitraTelepon" class="form-control" readonly>
                            </div>
                            <div class="invoice-info-tile">
                                <label><i class="bi bi-person-badge"></i>Penanggung Jawab</label>
                                <input type="text" id="mitraPenanggungJawab" class="form-control" readonly>
                            </div>
                            <div class="invoice-info-tile">
                                <label><i class="bi bi-briefcase"></i>Jabatan PJ</label>
                                <input type="text" id="mitraJabatanPenanggungJawab" class="form-control" readonly>
                            </div>
                            <div class="invoice-info-tile md:col-span-2">
                                <label><i class="bi bi-geo-alt"></i>Alamat</label>
                                <textarea id="mitraAlamat" class="form-control" rows="2" readonly></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="invoice-date-card mb-4">
                        <div class="row align-items-center g-3">
                            <div class="col-lg-4">
                                <span class="invoice-section-kicker"><i class="bi bi-calendar3"></i> Tanggal Tagihan</span>
                                <div class="mt-1 text-xs font-semibold text-slate-500">Tanggal ini menjadi dasar periode pencatatan tagihan.</div>
                            </div>
                            <div class="col-lg-8">
                                <label class="form-label fw-bold">Tanggal Tagihan <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_tagihan" class="form-control" value="{{ old('tanggal_tagihan', date('Y-m-d')) }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="invoice-document-card">
                        <div class="mb-3 flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <span class="invoice-section-kicker text-amber-700"><i class="bi bi-file-earmark-text"></i> Dokumen Dasar</span>
                                <h6 class="mb-1 mt-1 text-sm font-black text-slate-800">Dokumen Dasar Tagihan (Opsional)</h6>
                                <p class="mb-0 text-sm font-semibold text-amber-900">
                                    Isi bagian ini hanya jika tagihan memakai kontrak, perjanjian kerja sama, berita acara, rekap pemakaian, atau dokumen pendukung lainnya.
                                </p>
                            </div>
                            <span class="inline-flex w-fit rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800 ring-1 ring-amber-200">Boleh dikosongkan</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-7">
                                <label class="form-label fw-bold">Nomor Dokumen Dasar</label>
                                <select name="kontrak_mitra_jasa_id" id="kontrakSelect" class="form-select">
                                    <option value="">-- Tidak menggunakan dokumen dasar --</option>
                                </select>
                                <div class="form-text">Nomor dokumen berasal dari kontrak/dokumen yang sudah diunggah pada data Mitra Jasa.</div>
                            </div>
                            <div class="col-lg-5">
                                <label class="form-label fw-bold">Dokumen Dasar</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="text" id="dokumenDasarInfo" class="form-control bg-white" readonly value="Belum ada dokumen dipilih">
                                    <a href="#" id="linkLihatDokumen" target="_blank" class="btn btn-outline-primary fw-bold d-none">
                                        <i class="bi bi-file-pdf me-1"></i>Lihat
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Tanggal Mulai Berlaku</label>
                                <input type="date" id="tanggalMulaiKontrak" class="form-control bg-white" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Tanggal Selesai Berlaku</label>
                                <input type="date" id="tanggalSelesaiKontrak" class="form-control bg-white" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Input Daftar Tagihan -->
        <div class="col-12">
            <div class="tw-invoice-card mb-4 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_16px_42px_rgba(15,23,42,.07)]">
                <div class="border-b border-blue-200 bg-gradient-to-r from-blue-50 via-sky-50 to-white px-4 py-2.5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-lg bg-blue-700 text-white shadow-sm shadow-blue-600/20">
                                <i class="bi bi-list-check"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-base font-black text-blue-900">{{ $detailTitle }}</h6>
                                <p class="mb-0 text-xs font-bold text-slate-500">Tambahkan layanan, tarif, volume, satuan, dan keterangan perhitungan.</p>
                            </div>
                        </div>
                        <button type="button" class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-3 py-2 text-sm font-bold text-blue-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-blue-50 hover:shadow-md" id="btnAddService">
                            <i class="bi bi-plus-lg me-1"></i>Tambah Layanan
                        </button>
                    </div>
                </div>
                <div class="p-5">
                    <div class="alert alert-warning d-none" id="allowedServiceWarning">
                        Mitra ini belum memiliki layanan jasa aktif atau Anda belum ditugaskan untuk mengelola layanan jasa mitra ini.
                    </div>

                    @if(!empty($prefillTagihan['utilitas_id']))
                    <div class="alert border-primary bg-primary bg-opacity-10 shadow-sm mb-4">
                        <div class="fw-bold mb-2 text-primary">
                            <i class="bi bi-calculator me-2"></i>Kalkulator Tarif Tagihan Utilitas (110%)
                        </div>
                        <div class="small mb-3">
                            Laporan utilitas yang dipilih mengharuskan pengisian tarif dasar dari PLN/PDAM. Sistem akan mengalikan tarif tersebut sebesar 110% secara otomatis.
                        </div>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <div>
                                <label class="small fw-bold text-muted d-block">Tarif Dasar PLN (Rp)</label>
                                <input type="number" id="kalkulator-tarif-dasar" class="form-control" placeholder="Misal: 1444.70" min="0" step="0.01">
                            </div>
                            <div class="mt-3">
                                <i class="bi bi-arrow-right text-muted"></i>
                            </div>
                            <div>
                                <label class="small fw-bold text-muted d-block">Tarif Tagihan (110%)</label>
                                <input type="text" id="kalkulator-tarif-final-display" class="form-control bg-light text-primary fw-bold" readonly placeholder="Rp 0">
                                <input type="hidden" id="kalkulator-tarif-final-val">
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-primary" id="btn-terapkan-tarif">Terapkan ke Tabel Layanan</button>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="mb-4 rounded-2xl border border-blue-200 bg-gradient-to-br from-blue-50 to-sky-50 p-4 text-slate-700 shadow-sm">
                        <div class="mb-2 flex items-center gap-2 font-black text-blue-800">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white"><i class="bi bi-info-circle"></i></span>
                            Catatan Pengisian Tarif, Volume, dan Satuan
                        </div>
                        <ul class="mb-0 ps-3 text-sm leading-relaxed">
                            @if($isKonsesiMode)
                                @if($isKonsesiSetupMode)
                                    <li>Mode ini hanya menetapkan layanan/persentase konsesi untuk mitra, belum membuka daftar tagihan final.</li>
                                    <li>Setelah mitra mengirim laporan pendapatan dan laporan diverifikasi, Admin Jasa membuat tagihan konsesi dari laporan tersebut.</li>
                                    <li>Volume konsesi default 0 karena omzet final akan berasal dari laporan pendapatan mitra.</li>
                                @else
                                    <li>Tagihan konsesi ini dibuat dari laporan pendapatan mitra yang sudah diverifikasi.</li>
                                    <li>Volume konsesi berisi total omzet/nilai transaksi laporan mitra.</li>
                                @endif
                            @endif
                            <li>Tagihan PJP2U tidak digabung dengan tarif layanan lain karena memiliki ketentuan jatuh tempo 7 hari.</li>
                            <li>Untuk satuan khusus seperti per jam per ton, per kg per hari, per m2 per bulan, dan tiap 1000 kg, sistem akan menampilkan input perhitungan otomatis.</li>
                            <li>Untuk tarif yang memakai kata "atau bagiannya", sistem membulatkan ke atas sesuai ketentuan satuan. Contoh 80.001 kg pada tarif tiap 1000 kg dihitung 81 volume tagih.</li>
                            <li>Untuk layanan manual seperti per penumpang, per jam, atau per kg, isi angka sesuai volume yang benar-benar ditagihkan.</li>
                            <li>Untuk layanan konsesi/persentase, kolom tarif diisi persen dan volume diisi total omzet/nilai transaksi. Jumlah dihitung otomatis: omzet x persen.</li>
                            <li>Kolom keterangan dapat digunakan untuk mencatat dasar perhitungan, periode layanan, nomor berita acara, atau referensi dokumen pendukung.</li>
                        </ul>
                    </div>

                    <div class="mb-3 grid gap-3 md:grid-cols-3">
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3">
                            <div class="text-xs font-black uppercase text-blue-700">1. Pilih Layanan</div>
                            <div class="mt-1 text-sm text-slate-600">Gunakan tombol pencarian untuk memilih item tarif.</div>
                        </div>
                        <div class="rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3">
                            <div class="text-xs font-black uppercase text-sky-700">2. Isi Tarif & Volume</div>
                            <div class="mt-1 text-sm text-slate-600">Input volume menyesuaikan satuan layanan.</div>
                        </div>
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3">
                            <div class="text-xs font-black uppercase text-blue-700">Rumus</div>
                            <div class="mt-1 text-sm font-bold text-blue-700">Tarif x Volume Tagih = Jumlah</div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
                        <div class="table-responsive mb-0">
                        <table class="table table-bordered align-middle tw-invoice-table mb-0" id="tableServices">
                            <thead>
                                <tr>
                                    <th width="3%" class="text-center"><input type="checkbox" class="form-check-input" disabled></th>
                                    <th width="18%">Jenis Penerimaan</th>
                                    <th width="10%">Akun</th>
                                    <th width="11%">Tarif</th>
                                    <th width="20%">Volume</th>
                                    <th width="11%">Satuan</th>
                                    <th width="13%">Jumlah</th>
                                    <th width="13%">Keterangan</th>
                                    <th width="4%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="serviceList">
                                <!-- Dynamic rows go here -->
                            </tbody>
                            <tfoot class="bg-slate-50">
                                <tr>
                                    <td colspan="6" class="text-end fw-bold text-slate-600">Jumlah Pembayaran:</td>
                                    <td colspan="3">
                                        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-2">
                                            <input type="text" class="form-control fw-bold text-primary bg-white border-0 shadow-sm" id="grandTotal" readonly value="0.00">
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                        </div>
                    </div>
                    
                    <div class="mt-4 flex flex-col gap-2 md:flex-row md:justify-end">
                        <button type="button" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50" onclick="window.history.back();">Batal</button>
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-blue-600 to-cyan-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:-translate-y-0.5 hover:shadow-xl" id="btnSubmit">
                            <i class="bi bi-save me-2"></i>Simpan Draf Tagihan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
</div>

<!-- Template for new row -->
<template id="rowTemplate">
    <tr class="service-row invoice-service-row" data-index="__INDEX__">
        <td class="text-center align-middle">
            <input type="checkbox" class="form-check-input row-check">
        </td>
        <td>
            <input type="hidden" name="layanan[__INDEX__][id]" class="layanan-id-input" required>
            <input type="hidden" name="layanan[__INDEX__][mode]" class="calculation-mode-input" value="TARIF">
            <input type="hidden" name="layanan[__INDEX__][kurs]" class="kurs-input" value="1">
            <input type="hidden" name="layanan[__INDEX__][calculation_payload]" class="calculation-payload-input">
            <div class="invoice-field-label"><i class="bi bi-grid"></i>Layanan</div>
            <div class="input-group">
                <input type="text" class="form-control jenis-penerimaan-input" readonly placeholder="Pilih layanan">
                <button type="button" class="btn invoice-search-button btn-search-service" title="Cari layanan">
                    <i class="bi bi-search"></i>
                </button>
            </div>
            <div class="invalid-feedback d-none invalid-layanan-__INDEX__">Pilih layanan sampai level paling akhir (tarif/satuan).</div>
        </td>
        <td>
            <div class="invoice-field">
                <div class="invoice-field-label"><i class="bi bi-bank"></i>Akun</div>
                <input type="text" name="layanan[__INDEX__][kode_akun]" class="form-control invoice-muted-input kode-akun-input" readonly placeholder="Otomatis">
            </div>
        </td>
        <td>
            <div class="invoice-field">
                <div class="invoice-field-label"><i class="bi bi-cash-coin"></i>Tarif</div>
                <input type="number" name="layanan[__INDEX__][harga_satuan]" class="form-control price-input text-end" value="0" step="1" min="0" required>
                <div class="form-text calc-mode-help">Rp per satuan</div>
            </div>
        </td>
        <td>
            <div class="invoice-field">
                <div class="invoice-field-label"><i class="bi bi-123"></i>Volume</div>
                <input type="number" name="layanan[__INDEX__][qty]" class="form-control qty-input text-end" value="1" step="0.01" min="0.01" required>
                <div class="smart-calc-wrapper d-none"></div>
                <div class="form-text qty-mode-help">Volume</div>
            </div>
        </td>
        <td>
            <div class="invoice-field">
                <div class="invoice-field-label"><i class="bi bi-rulers"></i>Satuan</div>
                <input type="text" class="form-control invoice-muted-input satuan-input" readonly placeholder="Otomatis">
            </div>
        </td>
        <td>
            <div class="invoice-field">
                <div class="invoice-field-label"><i class="bi bi-calculator"></i>Jumlah</div>
                <input type="text" class="form-control invoice-subtotal subtotal-display text-end" readonly value="0.00">
            </div>
        </td>
        <td>
            <div class="invoice-field">
                <div class="invoice-field-label"><i class="bi bi-pencil-square"></i>Catatan</div>
                <input type="text" name="layanan[__INDEX__][keterangan]" class="form-control keterangan-input" placeholder="Catatan">
            </div>
        </td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-sm btn-outline-danger invoice-remove-button btn-remove-service" title="Hapus baris layanan">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>

<div class="modal fade" id="modalPilihLayanan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold">Pilih Jenis Penerimaan / Layanan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <label class="form-label fw-bold">Cari Layanan Tarif</label>
                <input type="search" id="serviceTreeSearch" class="form-control mb-3" placeholder="Ketik nama jenis, kategori, atau item tarif">
                <div id="serviceTree" class="service-tree"></div>
                <div class="form-text mt-2">Buka struktur Jenis Layanan > Kategori Layanan > Item Tarif. Klik item tarif paling akhir untuk memilih layanan.</div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <div class="me-auto small text-muted" id="selectedServiceInfo">Belum ada item tarif dipilih.</div>
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary fw-bold" id="btnUseSelectedService">Gunakan Layanan</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });

        let layanansData = @json($layanans);
        let mitraLayananMap = @json($mitraLayananMap ?? []);
        let mitraMetaMap = @json($mitraMetaMap ?? []);
        let prefillTagihan = @json($prefillTagihan ?? null);
        let oldKontrakMitraJasaId = @json(old('kontrak_mitra_jasa_id', $prefillTagihan['kontrak_mitra_jasa_id'] ?? null));
        let layanansById = {};
        let layanansByParent = { root: [] };

        layanansData.forEach(item => {
            layanansById[item.id] = item;
            let parentId = item.parent_id || 'root';
            if (!layanansByParent[parentId]) {
                layanansByParent[parentId] = [];
            }
            layanansByParent[parentId].push(item);
        });

        Object.keys(layanansByParent).forEach(parentId => {
            layanansByParent[parentId].sort((a, b) => {
                return String(a.nama_layanan || '').localeCompare(String(b.nama_layanan || ''), undefined, {
                    numeric: true,
                    sensitivity: 'base'
                });
            });
        });

        let rowIndex = 0;
        let activeRow = null;
        let selectedServiceId = null;
        let currentAllowedIds = new Set();
        
        function formatMoney(number) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(number || 0);
        }

        function padCode(value) {
            return String(value || '').padStart(6, '0');
        }

        function formatServiceTariff(item) {
            const value = serviceRateValue(item);
            if (!value) {
                return '';
            }

            const unit = item.satuan ? String(item.satuan) : '';
            if (isPercentageService(item)) {
                return 'Tarif ' + value.toLocaleString('id-ID') + '%';
            }

            return 'Tarif Rp ' + value.toLocaleString('id-ID') + (unit ? ' / ' + unit : '');
        }

        function isPercentageService(item) {
            if (!item) {
                return false;
            }

            let satuan = String(item.satuan || '').toLowerCase();
            if (satuan.includes('% per pulsa')) {
                return false; // Listrik dll yang punya karakter % tapi bukan persentase
            }

            return item.tipe_layanan === 'KONSESI' || satuan.includes('%');
        }

        function serviceRateValue(item) {
            if (!item) {
                return 0;
            }

            if (isPercentageService(item)) {
                return parseFloat(item.persentase_konsesi || item.tarif_dasar || 0);
            }

            return parseFloat(item.tarif_dasar || 0);
        }

        const calculatorDefinitions = [
            {
                rule: 'WEIGHT_PER_1000_PER_12_HOURS_ROUND_UP',
                label: 'Tiap 1000 kg per 12 jam',
                match: unit => unit.includes('tiap 1000 kg per 12 jam'),
                fields: [
                    { key: 'berat_kg', label: 'Bobot pesawat (kg)', defaultValue: 1000, step: 0.01 },
                    { key: 'durasi_jam', label: 'Durasi (jam)', defaultValue: 12, step: 0.01 },
                ],
                compute: inputs => ceilPart(inputs.berat_kg, 1000) * ceilPart(inputs.durasi_jam, 12),
                formula: (inputs, qty) => `ceil(${formatCleanNumber(inputs.berat_kg)} kg / 1000 kg) x ceil(${formatCleanNumber(inputs.durasi_jam)} jam / 12 jam) = ${formatCleanNumber(qty)} unit tagih`,
            },
            {
                rule: 'WEIGHT_PER_1000_ROUND_UP',
                label: 'Tiap 1000 kg',
                match: unit => unit.includes('tiap 1000 kg'),
                fields: [
                    { key: 'berat_kg', label: 'Bobot pesawat (kg)', defaultValue: 1000, step: 0.01 },
                ],
                compute: inputs => ceilPart(inputs.berat_kg, 1000),
                formula: (inputs, qty) => `ceil(${formatCleanNumber(inputs.berat_kg)} kg / 1000 kg) = ${formatCleanNumber(qty)} unit tagih`,
            },
            {
                rule: 'PACKAGE_100_EKSEMPLAR_ROUND_UP',
                label: 'Per 100 eksemplar',
                match: unit => unit.includes('per 100 eksemplar'),
                fields: [
                    { key: 'jumlah_eksemplar', label: 'Eksemplar', defaultValue: 100, step: 1 },
                ],
                compute: inputs => ceilPart(inputs.jumlah_eksemplar, 100),
                formula: (inputs, qty) => `ceil(${formatCleanNumber(inputs.jumlah_eksemplar)} / 100) = ${formatCleanNumber(qty)}`,
            },
            {
                rule: 'PACKAGE_25_BUKU_ROUND_UP',
                label: 'Per 25 buku',
                match: unit => unit.includes('per 25 buku'),
                fields: [
                    { key: 'jumlah_buku', label: 'Buku', defaultValue: 25, step: 1 },
                ],
                compute: inputs => ceilPart(inputs.jumlah_buku, 25),
                formula: (inputs, qty) => `ceil(${formatCleanNumber(inputs.jumlah_buku)} / 25) = ${formatCleanNumber(qty)}`,
            },
            productRule('PER_UNIT_PER_BULAN_PER_SISI_PANDANG', 'Per unit/bulan/sisi', unit => unit.includes('per unit per bulan per sisi pandang'), [
                ['unit', 'Unit'], ['bulan', 'Bulan'], ['sisi_pandang', 'Sisi pandang'],
            ]),
            productRule('PER_UNIT_PER_HARI_PER_SISI_PANDANG', 'Per unit/hari/sisi', unit => unit.includes('per unit per hari per sisi pandang'), [
                ['unit', 'Unit'], ['hari', 'Hari'], ['sisi_pandang', 'Sisi pandang'],
            ]),
            productRule('PER_M2_REKLAME_PER_TAHUN', 'Per m2 reklame/tahun', unit => unit.includes('per m2 reklame per tahun'), [
                ['luas_m2', 'Luas (m2)'], ['tahun', 'Tahun'],
            ]),
            productRule('PER_SAMBUNGAN_CABANG_PER_BULAN', 'Per sambungan cabang/bulan', unit => unit.includes('per sambungan cabang per bulan'), [
                ['sambungan_cabang', 'Sambungan'], ['bulan', 'Bulan'],
            ]),
            productRule('PER_PESAWAT_PER_SEKALI_PENGGUNAAN', 'Per pesawat/penggunaan', unit => unit.includes('per pesawat per sekali penggunaan'), [
                ['pesawat', 'Pesawat'], ['sekali_penggunaan', 'Penggunaan'],
            ]),
            productRule('PER_KEGIATAN_PER_HARI', 'Per kegiatan/hari', unit => unit.includes('per kegiatan per hari'), [
                ['kegiatan', 'Kegiatan'], ['hari', 'Hari'],
            ]),
            productRule('PER_JAM_PER_RUANGAN', 'Per jam/ruangan', unit => unit.includes('per jam per ruangan'), [
                ['jam', 'Jam'], ['ruangan', 'Ruangan'],
            ]),
            productRule('PER_JAM_PER_TON', 'Per jam/ton', unit => unit.includes('per jam per ton'), [
                ['jam', 'Jam'], ['ton', 'Ton'],
            ]),
            productRule('PER_KG_PER_HARI', 'Per kg/hari', unit => unit.includes('per kg per hari'), [
                ['kg', 'Kg'], ['hari', 'Hari'],
            ]),
            productRule('PER_M2_PER_BULAN', 'Per m2/bulan', unit => unit.includes('per m2 per bulan'), [
                ['luas_m2', 'Luas (m2)'], ['bulan', 'Bulan'],
            ]),
            productRule('PER_M2_PER_HARI', 'Per m2/hari', unit => unit.includes('per m2 per hari'), [
                ['luas_m2', 'Luas (m2)'], ['hari', 'Hari'],
            ]),
            productRule('PER_KENDARAAN_PER_TAHUN', 'Per kendaraan/tahun', unit => unit.includes('per kendaraan per tahun'), [
                ['kendaraan', 'Kendaraan'], ['tahun', 'Tahun'],
            ]),
            productRule('PER_KENDARAAN_PER_BULAN', 'Per kendaraan/bulan', unit => unit.includes('per kendaraan per bulan'), [
                ['kendaraan', 'Kendaraan'], ['bulan', 'Bulan'],
            ]),
            productRule('PER_KENDARAAN_PER_MINGGU', 'Per kendaraan/minggu', unit => unit.includes('per kendaraan per minggu'), [
                ['kendaraan', 'Kendaraan'], ['minggu', 'Minggu'],
            ]),
            productRule('PER_ORANG_PER_TAHUN', 'Per orang/tahun', unit => unit.includes('per orang per tahun'), [
                ['orang', 'Orang'], ['tahun', 'Tahun'],
            ]),
            productRule('PER_ORANG_PER_BULAN', 'Per orang/bulan', unit => unit.includes('per orang per bulan'), [
                ['orang', 'Orang'], ['bulan', 'Bulan'],
            ]),
            productRule('PER_ORANG_PER_MINGGU', 'Per orang/minggu', unit => unit.includes('per orang per minggu'), [
                ['orang', 'Orang'], ['minggu', 'Minggu'],
            ]),
            productRule('PER_UNIT_PER_BULAN', 'Per unit/bulan', unit => unit.includes('per unit per bulan'), [
                ['unit', 'Unit'], ['bulan', 'Bulan'],
            ]),
            productRule('PER_UNIT_PER_HARI', 'Per unit/hari', unit => unit.includes('per unit per hari'), [
                ['unit', 'Unit'], ['hari', 'Hari'],
            ]),
            directRule('PER_SEKALI_LEPAS_LANDAS_PENDARATAN', 'Gerakan pesawat', unit => unit.includes('per sekali lepas landas') || unit.includes('pendaratan'), 'gerakan', 'Gerakan'),
            directRule('PER_PENUMPANG', 'Per penumpang', unit => unit === 'per penumpang', 'penumpang', 'Penumpang'),
            directRule('PER_JAM', 'Per jam', unit => unit === 'per jam', 'jam', 'Jam'),
            directRule('PER_KG', 'Per kg', unit => unit === 'per kg', 'kg', 'Kg'),
            directRule('PER_HARI', 'Per hari', unit => unit === 'per hari', 'hari', 'Hari'),
        ];

        function productRule(rule, label, match, fields) {
            const mappedFields = fields.map(([key, fieldLabel]) => ({ key, label: fieldLabel, defaultValue: 1, step: 0.01 }));

            return {
                rule,
                label,
                match,
                fields: mappedFields,
                compute: inputs => mappedFields.reduce((total, field) => total * positiveNumber(inputs[field.key]), 1),
                formula: (inputs, qty) => mappedFields.map(field => `${formatCleanNumber(inputs[field.key])} ${field.label.toLowerCase()}`).join(' x ') + ` = ${formatCleanNumber(qty)}`,
            };
        }

        function directRule(rule, label, match, key, fieldLabel) {
            return {
                rule,
                label,
                match,
                fields: [{ key, label: fieldLabel, defaultValue: 1, step: 0.01 }],
                compute: inputs => positiveNumber(inputs[key]),
                formula: (inputs, qty) => `${formatCleanNumber(inputs[key])} ${fieldLabel.toLowerCase()} = ${formatCleanNumber(qty)}`,
            };
        }

        function normalizeUnit(value) {
            return String(value || '')
                .toLowerCase()
                .replace(/m(?:\u00c2)?\u00b2/g, 'm2')
                .replace(/\s+/g, ' ')
                .trim();
        }

        function positiveNumber(value) {
            const number = parseFloat(value);
            return Number.isFinite(number) && number > 0 ? number : 0;
        }

        function ceilPart(value, divisor) {
            const number = positiveNumber(value);
            return number > 0 ? Math.ceil(number / divisor) : 0;
        }

        function formatCleanNumber(value) {
            const number = Number(value || 0);
            if (Number.isInteger(number)) {
                return number.toLocaleString('id-ID');
            }

            return number.toLocaleString('id-ID', { maximumFractionDigits: 2 });
        }

        function isAutoCalculatorExcluded(service) {
            if (!service || isPercentageService(service)) {
                return true;
            }

            const text = `${buildServicePath(service)} ${service.satuan || ''}`.toLowerCase();
            return text.includes('pjp2u')
                || text.includes('pax pjp2u')
                || text.includes('penggunaan listrik')
                || text.includes('penggunaan air bandar');
        }

        function getCalculationDefinition(service) {
            if (!service || isAutoCalculatorExcluded(service)) {
                return null;
            }

            const unit = normalizeUnit(service.satuan);
            return calculatorDefinitions.find(definition => definition.match(unit)) || null;
        }

        function parseLandingWeightRange(service) {
            if (!service) {
                return null;
            }

            const text = `${buildServicePath(service)} ${service.nama_layanan || ''}`.toLowerCase();
            const unit = normalizeUnit(service.satuan);
            if (!unit.includes('tiap 1000 kg') || !text.includes('bobot pesawat')) {
                return null;
            }

            if (text.includes('s.d. 40.000 kg') || text.includes('s.d. 40000 kg')) {
                return {
                    min: 0,
                    max: 40000,
                    minExclusive: false,
                    maxInclusive: true,
                    label: 'bobot pesawat s.d. 40.000 kg',
                };
            }

            if ((text.includes('diatas 40.000 kg') || text.includes('di atas 40.000 kg') || text.includes('diatas 40000 kg') || text.includes('di atas 40000 kg'))
                && (text.includes('s.d. 100.000 kg') || text.includes('s.d. 100000 kg'))) {
                return {
                    min: 40000,
                    max: 100000,
                    minExclusive: true,
                    maxInclusive: true,
                    label: 'bobot pesawat di atas 40.000 kg s.d. 100.000 kg',
                };
            }

            if (text.includes('di atas 100.000 kg') || text.includes('diatas 100.000 kg') || text.includes('di atas 100000 kg') || text.includes('diatas 100000 kg')) {
                return {
                    min: 100000,
                    max: null,
                    minExclusive: true,
                    maxInclusive: false,
                    label: 'bobot pesawat di atas 100.000 kg',
                };
            }

            return null;
        }

        function isWeightInRange(weight, range) {
            if (!range || !weight) {
                return true;
            }

            const aboveMin = range.minExclusive ? weight > range.min : weight >= range.min;
            const belowMax = range.max === null ? true : (range.maxInclusive ? weight <= range.max : weight < range.max);

            return aboveMin && belowMax;
        }

        function findLandingServiceForWeight(currentService, weight) {
            if (!currentService || !currentService.parent_id || !weight) {
                return null;
            }

            return (layanansByParent[currentService.parent_id] || []).find(candidate => {
                if (!currentAllowedIds.has(Number(candidate.id))) {
                    return false;
                }

                const range = parseLandingWeightRange(candidate);
                return range && isWeightInRange(weight, range);
            }) || null;
        }

        function showSmartCalcWarning(row, message) {
            const wrapper = row.find('.smart-calc-wrapper');
            let warning = wrapper.find('.smart-calc-warning');

            if (!message) {
                warning.remove();
                return;
            }

            if (!warning.length) {
                warning = $('<div class="smart-calc-warning"></div>');
                wrapper.append(warning);
            }

            warning.text(message);
        }

        function collectCalculatorInputs(row, definition) {
            let inputs = {};

            definition.fields.forEach(field => {
                const input = row.find(`.calc-factor-input[data-factor="${field.key}"]`);
                inputs[field.key] = positiveNumber(input.val() || field.defaultValue);
            });

            return inputs;
        }

        function renderSmartCalculator(row, service) {
            const definition = getCalculationDefinition(service);
            const wrapper = row.find('.smart-calc-wrapper');
            const qtyInput = row.find('.qty-input');

            if (!definition) {
                wrapper.addClass('d-none').empty();
                qtyInput.removeClass('d-none').prop('readonly', false);
                return;
            }

            const fieldsHtml = definition.fields.map(field => `
                <div class="smart-calc-field">
                    <label>${escapeHtml(field.label)}</label>
                    <input type="number" class="form-control calc-factor-input text-end" data-factor="${escapeHtml(field.key)}" value="${field.defaultValue}" step="${field.step}" min="0">
                </div>
            `).join('');

            wrapper.html(`
                <div class="smart-calc-grid">${fieldsHtml}</div>
                <div class="smart-calc-result">Unit/volume tagih: <span class="calc-billable-qty">0</span></div>
                <div class="smart-calc-formula">Rumus akan muncul otomatis.</div>
            `).removeClass('d-none');

            qtyInput.addClass('d-none').prop('readonly', true);
        }

        function syncRowCalculation(row) {
            const service = layanansById[row.find('.layanan-id-input').val()];
            const definition = getCalculationDefinition(service);
            const mode = row.find('.calculation-mode-input').val();
            let qty = positiveNumber(row.find('.qty-input').val());

            if (definition) {
                const inputs = collectCalculatorInputs(row, definition);
                qty = definition.compute(inputs);
                const formula = definition.formula(inputs, qty);
                const range = parseLandingWeightRange(service);
                const weight = positiveNumber(inputs.berat_kg);

                row.find('.qty-input').val(qty);
                row.find('.calc-billable-qty').text(formatCleanNumber(qty));
                row.find('.smart-calc-formula').text(formula);

                if (range && weight && !isWeightInRange(weight, range)) {
                    const replacement = findLandingServiceForWeight(service, weight);
                    const message = replacement
                        ? `Bobot ${formatCleanNumber(weight)} kg tidak sesuai ${range.label}. Sistem mengarahkan ke ${replacement.nama_layanan}.`
                        : `Bobot ${formatCleanNumber(weight)} kg tidak sesuai ${range.label}. Pilih bracket bobot yang sesuai.`;

                    showSmartCalcWarning(row, message);
                    row.data('weight-range-invalid', replacement ? '' : message);

                    if (replacement && String(replacement.id) !== String(service.id)) {
                        applyServiceToRow(row, replacement, {
                            calculatorInputs: inputs,
                            skipCalculate: true,
                        });
                        showSmartCalcWarning(row, message);
                        return {
                            qty: positiveNumber(row.find('.qty-input').val()),
                            definition: getCalculationDefinition(layanansById[row.find('.layanan-id-input').val()]),
                        };
                    }
                } else {
                    row.data('weight-range-invalid', '');
                    showSmartCalcWarning(row, '');
                }

                row.find('.calculation-payload-input').val(JSON.stringify({
                    rule: definition.rule,
                    label: definition.label,
                    inputs,
                    billable_qty: qty,
                    formula,
                }));

                return { qty, definition };
            }

            row.find('.calculation-payload-input').val(JSON.stringify({
                rule: mode === 'PERSENTASE' ? 'PERSENTASE' : 'MANUAL_VOLUME',
                label: mode === 'PERSENTASE' ? 'Persentase omzet' : 'Volume manual',
                inputs: { volume: qty },
                billable_qty: qty,
                formula: mode === 'PERSENTASE' ? 'Omzet x persentase' : `Volume manual ${formatCleanNumber(qty)}`,
            }));
            row.data('weight-range-invalid', '');
            showSmartCalcWarning(row, '');

            return { qty, definition: null };
        }

        function buildServicePath(item) {
            let names = [];
            let current = item;
            let guard = 0;

            while (current && guard < 10) {
                names.unshift(current.nama_layanan);
                current = current.parent_id ? layanansById[current.parent_id] : null;
                guard++;
            }

            return names.join(' > ');
        }

        function calculateTotals() {
            let grandTotal = 0;
            $('.service-row').each(function() {
                let mode = $(this).find('.calculation-mode-input').val();
                let { qty } = syncRowCalculation($(this));
                let price = parseFloat($(this).find('.price-input').val()) || 0;
                let subtotal = mode === 'PERSENTASE'
                    ? (qty * price / 100)
                    : qty * price;
                
                $(this).find('.subtotal-display').val(formatMoney(subtotal));
                grandTotal += subtotal;
            });
            $('#grandTotal').val(formatMoney(grandTotal));
        }

        function renderCalculationMode(row, service) {
            const isPercentage = isPercentageService(service);
            row.find('.calculation-mode-input').val(isPercentage ? 'PERSENTASE' : 'TARIF');
            row.toggleClass('percentage-service-row', isPercentage);
            row.find('.price-input')
                .attr('step', isPercentage ? '0.0001' : '1')
                .attr('placeholder', isPercentage ? 'Persen' : 'Tarif Rp');
            row.find('.qty-input')
                .attr('min', isPercentage ? '0' : '0.01')
                .attr('placeholder', isPercentage ? 'Dasar hitung/omzet' : 'Volume');
            row.find('.calc-mode-help').text(isPercentage ? 'Persen (%)' : 'Rp per satuan');
            row.find('.qty-mode-help').text(isPercentage ? 'Dasar hitung: total omzet/nilai transaksi' : 'Volume');
            renderSmartCalculator(row, service);

            const definition = getCalculationDefinition(service);
            if (definition) {
                row.find('.qty-mode-help').text('Isi faktor perhitungan, volume tagih dihitung otomatis.');
            }
        }

        function applyServiceToRow(row, service, options = {}) {
            if (!service) return;

            row.find('.layanan-id-input').val(service.id);
            row.find('.jenis-penerimaan-input').val(service.kode_layanan || padCode(service.id));
            row.find('.kode-akun-input').val(service.kode_akun || '');
            row.find('.price-input').val(serviceRateValue(service));
            row.find('.kurs-input').val(1);
            row.find('.qty-input').val(isPercentageService(service) ? 0 : 1);
            row.find('.satuan-input').val(service.satuan || '');
            row.find('.keterangan-input').val(buildServicePath(service));
            row.find('.invalid-feedback').addClass('d-none').hide();
            renderCalculationMode(row, service);

            if (options.calculatorInputs) {
                Object.entries(options.calculatorInputs).forEach(([key, value]) => {
                    row.find(`.calc-factor-input[data-factor="${key}"]`).val(value);
                });
            }

            syncRowCalculation(row);

            if (!options.skipCalculate) {
                calculateTotals();
            }
            refreshKontrakOptions();
        }

        function addServiceRow() {
            let template = $('#rowTemplate').html();
            template = template.replace(/__INDEX__/g, rowIndex);
            $('#serviceList').append(template);

            rowIndex++;
            calculateTotals();
        }

        function escapeHtml(value) {
            return $('<div>').text(value || '').html();
        }

        function matchesSearch(item, searchTerm) {
            if (!searchTerm) return true;

            let ownText = `${item.nama_layanan || ''} ${item.kode_layanan || ''} ${item.kode_akun || ''}`.toLowerCase();
            if (ownText.includes(searchTerm)) return true;

            return (layanansByParent[item.id] || []).some(child => matchesSearch(child, searchTerm));
        }

        function isAllowedLeaf(item) {
            let isLeaf = item.is_leaf == 1 || item.is_leaf === true;
            return isLeaf && currentAllowedIds.has(Number(item.id));
        }

        function hasAllowedDescendant(item) {
            if (isAllowedLeaf(item)) return true;
            return (layanansByParent[item.id] || []).some(child => hasAllowedDescendant(child));
        }

        function renderServiceNodes(parentId, depth, searchTerm) {
            let nodes = layanansByParent[parentId] || [];
            let html = '';

            nodes.forEach(item => {
                if (!matchesSearch(item, searchTerm) || !hasAllowedDescendant(item)) {
                    return;
                }

                let childrenHtml = renderServiceNodes(item.id, depth + 1, searchTerm);
                let isLeaf = item.is_leaf == 1 || item.is_leaf === true;
                let badge = depth === 0 ? 'Jenis Layanan' : (isLeaf ? 'Item Tarif' : 'Kategori Layanan');
                let badgeClass = depth === 0 ? 'bg-primary' : (isLeaf ? 'bg-success' : 'bg-warning text-dark');
                let branch = depth === 0 ? '' : '|_';
                let tariffText = formatServiceTariff(item);

                if (isLeaf) {
                    if (!isAllowedLeaf(item)) {
                        return;
                    }

                    html += `
                        <button type="button" class="service-item ${selectedServiceId == item.id ? 'active' : ''}" data-service-id="${item.id}">
                            <div class="d-flex justify-content-between gap-3">
                                <div>
                                    <span class="tree-branch">${branch}</span>
                                    <i class="bi bi-folder2-open text-primary me-1"></i>
                                    <span class="tree-label">${escapeHtml(item.nama_layanan)}</span>
                                    <div class="tree-meta ms-4">
                                        ${escapeHtml(item.kode_layanan || padCode(item.id))}
                                        ${item.kode_akun ? ' | Akun ' + escapeHtml(item.kode_akun) : ''}
                                        ${tariffText ? ' | ' + escapeHtml(tariffText) : (item.satuan ? ' | ' + escapeHtml(item.satuan) : '')}
                                    </div>
                                </div>
                                <span class="badge ${badgeClass} align-self-start">${badge}</span>
                            </div>
                        </button>
                    `;
                    return;
                }

                html += `
                    <details ${searchTerm ? 'open' : ''}>
                        <summary>
                            <div class="d-flex justify-content-between gap-3">
                                <div>
                                    <span class="tree-branch">${branch}</span>
                                    <i class="bi bi-folder text-primary me-1 tree-folder-closed"></i>
                                    <i class="bi bi-folder2-open text-primary me-1 tree-folder-open"></i>
                                    <span class="tree-label">${escapeHtml(item.nama_layanan)}</span>
                                    <div class="tree-meta ms-4">${escapeHtml(item.kode_layanan || padCode(item.id))}</div>
                                </div>
                                <span class="badge ${badgeClass} align-self-start">${badge}</span>
                            </div>
                        </summary>
                        <div class="tree-children">${childrenHtml || '<div class="text-muted small px-2 py-1">Belum ada item di bawah kategori ini.</div>'}</div>
                    </details>
                `;
            });

            return html;
        }

        function renderServiceTree() {
            let searchTerm = ($('#serviceTreeSearch').val() || '').toLowerCase().trim();
            $('#serviceTree').html(renderServiceNodes('root', 0, searchTerm) || '<div class="text-center text-muted py-4">Layanan tidak ditemukan.</div>');
        }

        function resetServiceRows() {
            $('#serviceList').empty();
            rowIndex = 0;
            addServiceRow();
        }

        function formatText(value) {
            return value ? String(value).replaceAll('_', ' ') : '-';
        }

        function updateMitraInfo() {
            let mitraId = $('#mitraSelect').val();
            let mitra = mitraId ? mitraMetaMap[mitraId] : null;

            $('#mitraInfoPanel').toggleClass('d-none', !mitra);
            $('#mitraKode').val(mitra?.kode_mitra || '-');
            $('#mitraJenis').val(formatText(mitra?.jenis_mitra));
            $('#mitraNpwp').val(mitra?.npwp || '-');
            $('#mitraEmail').val(mitra?.email || '-');
            $('#mitraTelepon').val(mitra?.no_telepon || '-');
            $('#mitraPenanggungJawab').val(mitra?.nama_penanggung_jawab || '-');
            $('#mitraJabatanPenanggungJawab').val(mitra?.jabatan_penanggung_jawab || '-');
            $('#mitraAlamat').val(mitra?.alamat || '-');

            refreshKontrakOptions();
        }

        function selectedServiceIds() {
            let ids = [];
            $('.service-row').each(function () {
                let serviceId = parseInt($(this).find('.layanan-id-input').val(), 10);
                if (serviceId) {
                    ids.push(serviceId);
                }
            });

            return [...new Set(ids)];
        }

        function kontrakMatchesSelectedServices(kontrak, serviceIds) {
            let scopeIds = (kontrak.layanan_ids || []).map(Number);
            if (scopeIds.length === 0 || serviceIds.length === 0) {
                return true;
            }

            return serviceIds.every(id => scopeIds.includes(Number(id)));
        }

        function refreshKontrakOptions() {
            let mitraId = $('#mitraSelect').val();
            let mitra = mitraId ? mitraMetaMap[mitraId] : null;
            let serviceIds = selectedServiceIds();
            let currentValue = $('#kontrakSelect').val() || oldKontrakMitraJasaId || '';
            let kontrakOptions = '<option value="">-- Tidak menggunakan dokumen dasar --</option>';

            (mitra?.kontrak || []).forEach(kontrak => {
                if (!kontrakMatchesSelectedServices(kontrak, serviceIds)) {
                    return;
                }

                let selected = String(currentValue || '') === String(kontrak.id) ? 'selected' : '';
                let label = `${kontrak.nomor_kontrak || '-'} - ${kontrak.nama_kontrak || 'Dokumen Mitra Jasa'}`;
                if ((kontrak.layanan_ids || []).length > 0) {
                    label += ' (sesuai layanan)';
                }
                kontrakOptions += `<option value="${kontrak.id}" ${selected}>${escapeHtml(label)}</option>`;
            });

            $('#kontrakSelect').html(kontrakOptions);
            if (currentValue && !$('#kontrakSelect').val()) {
                oldKontrakMitraJasaId = null;
            }
            refreshKontrakInfo();
        }

        function refreshKontrakInfo() {
            let mitraId = $('#mitraSelect').val();
            let kontrakId = $('#kontrakSelect').val();
            let mitra = mitraId ? mitraMetaMap[mitraId] : null;
            let kontrak = (mitra?.kontrak || []).find(item => String(item.id) === String(kontrakId));

            $('#tanggalMulaiKontrak').val(kontrak?.tanggal_mulai || '');
            $('#tanggalSelesaiKontrak').val(kontrak?.tanggal_selesai || '');

            if (kontrak) {
                let dokumenInfo = `${formatText(kontrak.jenis_dokumen)} | ${kontrak.status_kontrak || '-'}`;
                $('#dokumenDasarInfo').val(dokumenInfo);

                if (kontrak.file_url) {
                    $('#linkLihatDokumen').removeClass('d-none').attr('href', kontrak.file_url);
                } else {
                    $('#linkLihatDokumen').addClass('d-none').attr('href', '#');
                }
            } else {
                $('#dokumenDasarInfo').val('Belum ada dokumen dipilih');
                $('#linkLihatDokumen').addClass('d-none').attr('href', '#');
            }
        }

        function refreshAllowedServices() {
            let mitraId = $('#mitraSelect').val();
            let allowed = mitraId ? (mitraLayananMap[mitraId] || []) : [];
            currentAllowedIds = new Set(allowed.map(Number));

            $('#allowedServiceWarning').toggleClass('d-none', !mitraId || allowed.length > 0);
            $('#btnAddService, #btnSubmit').prop('disabled', !!mitraId && allowed.length === 0);

            if (mitraId) {
                $('#linkAturLayananMitra')
                    .removeClass('d-none')
                    .attr('href', "{{ url('/jasa/mitra') }}/" + mitraId + "/layanan");
            } else {
                $('#linkAturLayananMitra').addClass('d-none').attr('href', '#');
            }

            updateMitraInfo();
            resetServiceRows();
        }

        function applyPrefillTagihan() {
            if (!prefillTagihan || !prefillTagihan.layanan_jasa_id) {
                return;
            }

            let service = layanansById[prefillTagihan.layanan_jasa_id];
            if (!service) {
                return;
            }

            let row = $('.service-row').first();
            applyServiceToRow(row, service);
            row.find('.qty-input').val(prefillTagihan.qty || 1);
            row.find('.price-input').val(prefillTagihan.harga_satuan || 0);
            row.find('.kurs-input').val(1);
            row.find('.satuan-input').val(prefillTagihan.satuan || service.satuan || '');
            row.find('.keterangan-input').val(prefillTagihan.keterangan || buildServicePath(service));
            renderCalculationMode(row, service);
            
            // Only force PERSENTASE overrides if it's from Konsesi/Penjualan, not PAX PJP2U.
            if (prefillTagihan.penjualan_id && prefillTagihan.calculation_mode !== 'TARIF') {
                row.find('.calculation-mode-input').val('PERSENTASE');
                row.find('.price-input').attr('step', '0.0001').attr('placeholder', 'Persen');
                row.find('.qty-input').attr('min', '0').attr('placeholder', 'Dasar hitung/omzet');
                row.find('.calc-mode-help').text('Persen (%)');
                row.find('.qty-mode-help').text('Dasar hitung: total omzet/nilai transaksi');
            } else if (prefillTagihan.calculation_mode === 'TARIF') {
                row.find('.calculation-mode-input').val('TARIF');
                row.find('.price-input').attr('step', '1').attr('placeholder', 'Tarif Rp');
                row.find('.qty-input').attr('min', '0.01').attr('placeholder', 'Volume');
                row.find('.calc-mode-help').text('Rp per satuan');
                row.find('.qty-mode-help').text('Volume');
            }
            
            syncRowCalculation(row);
            calculateTotals();
        }

        function updateSelectedServiceInfo() {
            let service = selectedServiceId ? layanansById[selectedServiceId] : null;
            $('#selectedServiceInfo').text(service ? `Dipilih: ${buildServicePath(service)}` : 'Belum ada item tarif dipilih.');
        }

        addServiceRow();
        refreshAllowedServices();
        applyPrefillTagihan();
        $('#mitraSelect').on('change', refreshAllowedServices);
        $('#kontrakSelect').on('change', function() {
            oldKontrakMitraJasaId = null;
            refreshKontrakInfo();
        });

        $('#btnAddService').click(function() {
            addServiceRow();
        });

        $(document).on('click', '.btn-search-service', function() {
            activeRow = $(this).closest('tr');
            selectedServiceId = activeRow.find('.layanan-id-input').val() || null;
            $('#serviceTreeSearch').val('');
            renderServiceTree();
            updateSelectedServiceInfo();
            $('#modalPilihLayanan').modal('show');
        });

        $(document).on('click', '.service-item', function() {
            selectedServiceId = $(this).data('service-id');
            $('.service-item').removeClass('active');
            $(this).addClass('active');
            updateSelectedServiceInfo();
        });

        $('#serviceTreeSearch').on('input', function() {
            renderServiceTree();
            updateSelectedServiceInfo();
        });

        $('#btnUseSelectedService').click(function() {
            if (!selectedServiceId) {
                alert('Silakan pilih item tarif terlebih dahulu.');
                return;
            }

            applyServiceToRow(activeRow, layanansById[selectedServiceId]);
            $('#modalPilihLayanan').modal('hide');
        });

        $(document).on('click', '.btn-remove-service', function() {
            if ($('.service-row').length > 1) {
                $(this).closest('tr').remove();
                calculateTotals();
                refreshKontrakOptions();
            } else {
                alert('Minimal harus ada 1 layanan.');
            }
        });

        $(document).on('input', '.qty-input, .price-input, .calc-factor-input', function() {
            calculateTotals();
        });
        
        $('form').submit(function(e) {
            calculateTotals();

            if ($('.service-row').length === 0) {
                alert('Silakan tambahkan minimal 1 layanan jasa.');
                return false;
            }
            
            let isValid = true;
            $('.service-row').each(function() {
                let hiddenVal = $(this).find('.layanan-id-input').val();
                let index = $(this).data('index');
                if (!hiddenVal) {
                    isValid = false;
                    $(this).find('.invalid-layanan-' + index).removeClass('d-none').show();
                } else {
                    $(this).find('.invalid-layanan-' + index).addClass('d-none').hide();
                }

                const mode = $(this).find('.calculation-mode-input').val();
                const qty = parseFloat($(this).find('.qty-input').val()) || 0;
                if (mode === 'TARIF' && qty <= 0) {
                    isValid = false;
                }

                if ($(this).data('weight-range-invalid')) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Periksa kembali pilihan layanan dan input perhitungan. Layanan tarif rupiah harus punya volume tagih lebih dari 0.');
                return false;
            }
            
            $('#btnSubmit').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Menyimpan...');
            return true;
        });

        // Kalkulator Utilitas
        $('#kalkulator-tarif-dasar').on('input', function() {
            let dasar = parseFloat($(this).val()) || 0;
            let finalTarif = dasar * 1.1;
            $('#kalkulator-tarif-final-val').val(finalTarif);
            $('#kalkulator-tarif-final-display').val('Rp ' + formatMoney(finalTarif));
        });

        $('#btn-terapkan-tarif').click(function() {
            let finalTarif = parseFloat($('#kalkulator-tarif-final-val').val()) || 0;
            if (finalTarif <= 0) {
                alert('Silakan masukkan tarif dasar terlebih dahulu.');
                return;
            }

            // Terapkan ke row pertama
            let row = $('.service-row').first();
            if (row.length > 0) {
                row.find('.price-input').val(finalTarif);
                calculateTotals();
                
                // Highlight row agar user tahu sudah diupdate
                row.css('background-color', '#e0f2fe');
                setTimeout(() => row.css('background-color', ''), 1500);
            }
        });
    });
</script>
@endpush
