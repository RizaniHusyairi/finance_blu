@extends('layouts.app')
@section('title', $kontrak->exists ? 'Edit Kontrak Mitra Jasa' : 'Tambah Kontrak Mitra Jasa')

@include('super_admin_jasa.partials.form-style')

@push('css')
<style>
    /* ===== Area unggah berkas kontrak =====
       Mengikuti bahasa visual jasa-form-card: sudut membulat, palet biru,
       bayangan lembut. Input file asli disembunyikan; seluruh zona menjadi
       target klik sekaligus target seret-lepas. */
    .kontrak-upload { position: relative; }

    .kontrak-upload input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }
    /* Zona tetap bisa dijangkau keyboard: fokus input mewarnai zona. */
    .kontrak-upload input[type="file"]:focus-visible + .kontrak-dropzone {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
    }

    .kontrak-dropzone {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 18px;
        border: 1.5px dashed #bfdbfe;
        border-radius: 16px;
        background: linear-gradient(135deg, #eff6ff 0%, #f8fbff 100%);
        transition: border-color .18s ease, background .18s ease, transform .18s ease;
    }
    .kontrak-upload:hover .kontrak-dropzone { border-color: #60a5fa; }
    .kontrak-upload.is-dragging .kontrak-dropzone {
        border-color: #2563eb;
        background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
        transform: translateY(-1px);
    }

    .kontrak-dropzone-ikon {
        flex: 0 0 46px;
        width: 46px; height: 46px;
        display: grid; place-items: center;
        border-radius: 14px;
        font-size: 1.25rem;
        color: #fff;
        background: #1d4ed8;
        box-shadow: 0 12px 24px rgba(37, 99, 235, .18);
    }
    .kontrak-dropzone-teks { min-width: 0; }
    .kontrak-dropzone-judul { color: #1e3a8a; font-weight: 800; font-size: 14px; line-height: 1.35; }
    .kontrak-dropzone-sub   { color: #64748b; font-weight: 600; font-size: 12px; margin-top: 2px; }

    /* ===== Kartu berkas terpilih ===== */
    .kontrak-berkas {
        border: 1px solid #dbe3ef;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .06);
        overflow: hidden;
    }
    .kontrak-berkas-atas {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
    }
    .kontrak-berkas-ikon {
        flex: 0 0 42px;
        width: 42px; height: 42px;
        display: grid; place-items: center;
        border-radius: 12px;
        font-size: 1.15rem;
        color: #fff;
        background: var(--kb-aksen, #64748b);
        transition: background .2s ease;
    }
    .kontrak-berkas-info { min-width: 0; flex: 1 1 auto; }
    .kontrak-berkas-nama {
        color: #1e293b; font-weight: 800; font-size: 13.5px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .kontrak-berkas-meta {
        display: flex; flex-wrap: wrap; align-items: center; gap: 6px;
        color: #64748b; font-size: 12px; font-weight: 600; margin-top: 3px;
    }
    .kontrak-berkas-hapus {
        flex: 0 0 auto;
        border: 0; background: transparent;
        color: #94a3b8; font-size: 1.1rem; line-height: 1;
        padding: 6px; border-radius: 9px;
        transition: color .15s ease, background .15s ease;
    }
    .kontrak-berkas-hapus:hover { color: #dc2626; background: #fef2f2; }

    /* Lencana ringkas: "hemat 83%", "e-Meterai", dst. */
    .kontrak-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 11px; font-weight: 800; letter-spacing: .01em;
        background: var(--kb-lembut, #f1f5f9);
        color: var(--kb-aksen, #475569);
    }

    /* Baris status di kaki kartu — warnanya mengikuti keadaan. */
    .kontrak-berkas-status {
        display: flex; align-items: flex-start; gap: 8px;
        padding: 10px 16px;
        border-top: 1px solid #eef2f7;
        background: var(--kb-lembut, #f8fafc);
        color: #475569;
        font-size: 12.5px; font-weight: 600;
        line-height: 1.45;
    }
    .kontrak-berkas-status i { color: var(--kb-aksen, #64748b); margin-top: 1px; }

    /* Keadaan: netral (memproses), sukses, peringatan, galat. */
    .kontrak-berkas.is-proses  { --kb-aksen: #2563eb; --kb-lembut: #eff6ff; }
    .kontrak-berkas.is-ok      { --kb-aksen: #059669; --kb-lembut: #ecfdf5; }
    .kontrak-berkas.is-warning { --kb-aksen: #d97706; --kb-lembut: #fffbeb; }
    .kontrak-berkas.is-danger  { --kb-aksen: #dc2626; --kb-lembut: #fef2f2; }

    /* Meter: dipakai untuk progres unggah, lalu untuk porsi ukuran akhir. */
    .kontrak-meter { padding: 0 16px 14px; }
    .kontrak-meter-bar {
        height: 6px; border-radius: 999px;
        background: #e8eef7; overflow: hidden;
    }
    .kontrak-meter-bar > span {
        display: block; height: 100%; width: 0;
        border-radius: inherit;
        background: var(--kb-aksen, #2563eb);
        transition: width .45s ease;
    }
    .kontrak-meter-label {
        display: flex; justify-content: space-between; gap: 10px;
        color: #64748b; font-size: 11.5px; font-weight: 700;
        margin-top: 6px;
    }

    .kontrak-spin { animation: kontrak-putar 1s linear infinite; }
    @keyframes kontrak-putar { to { transform: rotate(360deg); } }

    @media (max-width: 576px) {
        .kontrak-dropzone { padding: 14px; }
        .kontrak-dropzone-sub { font-size: 11.5px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .kontrak-meter-bar > span,
        .kontrak-dropzone { transition: none; }
        .kontrak-spin { animation: none; }
    }
</style>
@endpush

@push('css')
<style>
    /* ===== Scope Layanan Kontrak: pohon per kategori ===== */
    .ks-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 12px; }
    .ks-search { position: relative; flex: 1 1 320px; }
    .ks-search i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
    .ks-search .form-control { padding-left: 38px; min-height: 42px; border-radius: 12px; }
    .ks-tools { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .ks-only { display: inline-flex; align-items: center; gap: 6px; margin: 0 6px 0 0; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer; }
    .ks-scope .form-check-input { flex: none; width: 1.15em; height: 1.15em; margin: 0; cursor: pointer; }

    .ks-summary { margin-bottom: 12px; padding: 12px 14px; border: 1px solid #bfdbfe; border-radius: 14px; background: #eff6ff; }
    .ks-summary-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .ks-count { font-size: 14px; font-weight: 700; color: #1e3a8a; }
    .ks-count b { font-size: 16px; }
    .ks-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .ks-chips:empty { display: none; }
    .ks-chip {
        display: inline-flex; align-items: center; gap: 6px; max-width: 100%; padding: 4px 6px 4px 10px;
        border-radius: 999px; background: #fff; border: 1px solid #93c5fd; color: #1d4ed8; font-size: 12.5px; font-weight: 600;
    }
    .ks-chip span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ks-chip button { border: 0; background: #dbeafe; color: #1d4ed8; width: 20px; height: 20px; border-radius: 50%; line-height: 1; padding: 0; flex: none; }
    .ks-chip button:hover { background: #bfdbfe; }
    .ks-empty-warn { display: none; gap: 8px; margin-top: 8px; padding: 8px 10px; border-radius: 10px; background: #fffbeb; border: 1px solid #fde68a; color: #92400e; font-size: 13px; }
    .ks-scope.is-empty .ks-empty-warn { display: flex; }
    .ks-scope.is-empty .ks-summary { background: #fffdf5; border-color: #fde68a; }
    .ks-scope.is-empty #ksClear { display: none; }

    .ks-groups { display: grid; gap: 8px; }
    .ks-group { border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; overflow: hidden; }
    .ks-group[open] { border-color: #c7d2fe; box-shadow: 0 6px 18px rgba(37, 99, 235, .06); }
    .ks-group > summary {
        display: flex; align-items: center; gap: 8px; padding: 11px 14px; cursor: pointer; list-style: none;
        background: #f8fafc; font-weight: 700; color: #1e293b; user-select: none;
    }
    .ks-group > summary::-webkit-details-marker { display: none; }
    .ks-group[open] > summary { background: #f5f8ff; border-bottom: 1px solid #e0e7ff; }
    .ks-chev { transition: transform .15s; color: #64748b; }
    .ks-group[open] .ks-chev { transform: rotate(90deg); }
    .ks-group-name { flex: 1; min-width: 0; font-size: 14px; }
    .ks-badge { flex: none; padding: 2px 9px; border-radius: 999px; background: #e2e8f0; color: #475569; font-size: 12px; font-weight: 800; }
    .ks-badge.has { background: #2563eb; color: #fff; }
    .ks-all { flex: none; display: inline-flex; align-items: center; gap: 6px; margin: 0; padding: 3px 8px; border: 0; background: transparent; border-radius: 8px; font-size: 12px; font-weight: 700; color: #1d4ed8; cursor: pointer; }
    .ks-all i { font-size: 15px; }
    .ks-all:hover { background: #e0e7ff; }
    .ks-group-body { padding: 10px 14px 14px; }
    .ks-sub { margin: 8px 0 6px; font-size: 12px; font-weight: 700; color: #64748b; }
    .ks-sub:first-child { margin-top: 0; }
    .ks-items { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 6px; }
    .ks-item {
        display: flex; align-items: flex-start; gap: 10px; margin: 0; padding: 9px 11px; border: 1px solid #e2e8f0;
        border-radius: 11px; background: #fff; cursor: pointer; transition: border-color .12s, background .12s, box-shadow .12s;
    }
    .ks-item:hover { border-color: #93c5fd; background: #f8fbff; }
    .ks-item .form-check-input { margin-top: 2px; }
    .ks-item:has(input:checked) { border-color: #2563eb; background: #eff6ff; box-shadow: inset 3px 0 0 #2563eb; }
    .ks-item-text { min-width: 0; }
    .ks-item-nama { display: block; font-size: 13.5px; font-weight: 600; color: #0f172a; line-height: 1.35; }
    .ks-item-meta { display: block; margin-top: 2px; font-size: 11.5px; color: #64748b; }
    .ks-kode { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: #3730a3; }
    .ks-noresult { padding: 22px; text-align: center; color: #64748b; border: 1px dashed #cbd5e1; border-radius: 14px; }
    [data-ks-item][hidden], [data-ks-group][hidden], [data-ks-sub][hidden], [data-ks-items][hidden] { display: none !important; }

    /* Bilah simpan menempel di bawah layar (kartu induk perlu overflow visible agar sticky bekerja). */
    .jasa-form-card:has(.ks-sticky-footer) { overflow: visible; }
    .ks-sticky-footer {
        position: sticky; bottom: 0; z-index: 30; border-top: 1px solid #e2e8f0;
        border-radius: 0 0 20px 20px; box-shadow: 0 -10px 26px rgba(15, 23, 42, .08); background: rgba(248, 250, 252, .97);
        backdrop-filter: blur(6px);
    }
    .ks-footer-count { font-size: 13.5px; font-weight: 600; color: #334155; }
    .ks-footer-count b { color: #1d4ed8; }
    .ks-footer-count.is-empty, .ks-footer-count.is-empty b { color: #b45309; }
    @media (max-width: 575.98px) {
        .ks-sticky-footer { padding: 10px 14px !important; }
        .ks-sticky-footer > div { flex-direction: row !important; flex-wrap: wrap; }
        .ks-sticky-footer .ks-footer-count { flex-basis: 100%; }
        .ks-sticky-footer .btn { flex: 1 1 0; padding: 8px 10px !important; white-space: nowrap; font-size: 14px; }
        .ks-sticky-footer .btn-primary { flex-grow: 2; }
        .ks-items { grid-template-columns: 1fr; }
        .ks-group > summary { flex-wrap: wrap; }
        .ks-group-name { flex-basis: calc(100% - 60px); }
        .ks-all { margin-left: auto; }
    }
</style>
@endpush

@section('content')
@php
    $requiredMark = '<span class="text-danger ms-1">*</span>';
    $selectedKontrakLayananIds = collect(old('layanan_ids', $selectedLayananIds ?? []))->map(fn ($id) => (int) $id)->all();
    $layananPath = function ($layanan) {
        $names = [$layanan->nama_layanan];
        $parent = $layanan->parent;
        $guard = 0;

        while ($parent && $guard < 10) {
            array_unshift($names, $parent->nama_layanan);
            $parent = $parent->parent;
            $guard++;
        }

        return implode(' > ', $names);
    };

    // Scope layanan dikelompokkan per kategori puncak (root); jalur di antara root
    // dan item tampil sebagai subjudul agar tidak diulang di setiap item.
    $grupScope = collect($layanans ?? [])
        ->map(function ($layanan) {
            $leluhur = [];
            $parent = $layanan->parent;
            $guard = 0;
            while ($parent && $guard < 10) {
                array_unshift($leluhur, $parent);
                $parent = $parent->parent;
                $guard++;
            }
            $root = $leluhur[0] ?? $layanan;

            return [
                'layanan' => $layanan,
                'root_id' => $root->id,
                'root_nama' => $root->nama_layanan,
                'sub' => collect(array_slice($leluhur, 1))->pluck('nama_layanan')->implode(' › '),
            ];
        })
        ->groupBy('root_id')
        ->map(function ($items) use ($selectedKontrakLayananIds) {
            return [
                'nama' => $items->first()['root_nama'],
                'sub' => $items->sortBy(fn ($i) => $i['sub'] . '|' . $i['layanan']->nama_layanan)->groupBy('sub'),
                'jumlah' => $items->count(),
                'terpilih' => $items->filter(fn ($i) => in_array((int) $i['layanan']->id, $selectedKontrakLayananIds, true))->count(),
            ];
        })
        ->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE);
@endphp

<div class="jasa-form-hero mb-4 px-4 py-4">
    <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center position-relative">
        <div class="d-flex gap-3 align-items-start">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-white text-primary shadow-sm" style="width:44px;height:44px;">
                <i class="bi bi-file-earmark-ruled fs-5"></i>
            </span>
            <div>
                <h4 class="mb-1 fw-black">{{ $kontrak->exists ? 'Edit Kontrak Mitra Jasa' : 'Tambah Kontrak Mitra Jasa' }}</h4>
                <p class="mb-0 fw-semibold small">{{ $mitra->nama_mitra }} - dokumen dasar untuk tagihan dan layanan mitra.</p>
            </div>
        </div>
        <a href="{{ route('jasa.mitra.show', $mitra) }}" class="btn btn-light text-primary fw-bold shadow-sm jasa-icon-btn" title="Kembali" aria-label="Kembali">
            <i class="bi bi-arrow-left"></i>
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" enctype="multipart/form-data" action="{{ $kontrak->exists ? route('jasa.mitra.kontrak.update', [$mitra, $kontrak]) : route('jasa.mitra.kontrak.store', $mitra) }}">
    @csrf
    @if($kontrak->exists)
        @method('PUT')
    @endif

    <div class="jasa-form-card">
        <div class="jasa-form-section">
            <div class="jasa-section-title">
                <span class="jasa-section-icon"><i class="bi bi-card-heading"></i></span>
                <div>
                    <h6>Identitas Dokumen</h6>
                    <p>Nomor, nama, jenis, dan status dokumen dasar.</p>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Nomor Kontrak {!! $requiredMark !!}</label>
                    <input type="text" name="nomor_kontrak" class="form-control" value="{{ old('nomor_kontrak', $kontrak->nomor_kontrak) }}" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-bold">Nama Kontrak/Dokumen {!! $requiredMark !!}</label>
                    <input type="text" name="nama_kontrak" class="form-control" value="{{ old('nama_kontrak', $kontrak->nama_kontrak) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Jenis Dokumen {!! $requiredMark !!}</label>
                    <select name="jenis_dokumen" class="form-select" required>
                        <option value="">Pilih dokumen</option>
                        @foreach(\App\Models\KontrakMitraJasa::JENIS_DOKUMEN as $jenis => $label)
                            <option value="{{ $jenis }}" @selected(old('jenis_dokumen', $kontrak->jenis_dokumen) === $jenis)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Tanggal Kontrak {!! $requiredMark !!}</label>
                    <input type="date" name="tanggal_kontrak" class="form-control" value="{{ old('tanggal_kontrak', optional($kontrak->tanggal_kontrak)->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Status Kontrak {!! $requiredMark !!}</label>
                    <select name="status_kontrak" class="form-select" required>
                        @foreach(['DRAFT', 'AKTIF', 'BERAKHIR', 'DIBATALKAN'] as $status)
                            <option value="{{ $status }}" @selected(old('status_kontrak', $kontrak->status_kontrak ?: 'AKTIF') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="jasa-form-section">
            <div class="jasa-section-title">
                <span class="jasa-section-icon"><i class="bi bi-calendar-range"></i></span>
                <div>
                    <h6>Masa Berlaku dan File</h6>
                    <p>Tanggal berlaku dokumen dan unggahan PDF pendukung.</p>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Tanggal Mulai {!! $requiredMark !!}</label>
                    <input type="date" name="tanggal_mulai" class="form-control" value="{{ old('tanggal_mulai', optional($kontrak->tanggal_mulai)->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Tanggal Selesai {!! $requiredMark !!}</label>
                    <input type="date" name="tanggal_selesai" class="form-control" value="{{ old('tanggal_selesai', optional($kontrak->tanggal_selesai)->format('Y-m-d')) }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold" for="fileKontrakInput">
                        File Kontrak PDF @unless($kontrak->exists){!! $requiredMark !!}@endunless
                    </label>

                    {{-- Zona seret-lepas. Input file asli disembunyikan menutupi zona,
                         sehingga klik di mana pun membuka pemilih berkas dan fokus
                         keyboard tetap bekerja. --}}
                    <div class="kontrak-upload" id="kontrakUpload">
                        <input type="file" name="file_kontrak" accept=".pdf"
                               id="fileKontrakInput"
                               data-max-kb="20480"
                               data-url-pratinjau="{{ route('jasa.mitra.kontrak.pratinjau-kompresi', $mitra) }}"
                               {{ $kontrak->exists ? '' : 'required' }}>
                        <div class="kontrak-dropzone">
                            <span class="kontrak-dropzone-ikon"><i class="bi bi-cloud-arrow-up"></i></span>
                            <span class="kontrak-dropzone-teks">
                                <span class="d-block kontrak-dropzone-judul">Seret berkas PDF ke sini, atau klik untuk memilih</span>
                                <span class="d-block kontrak-dropzone-sub">
                                    Maksimal 20 MB &middot; dikompres otomatis saat diunggah &middot; dokumen ber-e-Meterai disimpan utuh
                                </span>
                            </span>
                        </div>
                    </div>

                    {{-- Diisi setelah berkas selesai diunggah & dikompres di latar belakang.
                         Saat terisi, input file dikosongkan agar berkas tidak terkirim dua kali. --}}
                    <input type="hidden" name="file_kontrak_token" id="fileKontrakToken" value="">

                    {{-- Kartu berkas terpilih: nama, ukuran, status, dan meter.
                         Seluruh isinya diisi oleh JS — lihat blok script di bawah. --}}
                    <div class="kontrak-berkas mt-2 d-none" id="kontrakBerkas" aria-live="polite">
                        <div class="kontrak-berkas-atas">
                            <span class="kontrak-berkas-ikon"><i class="bi bi-filetype-pdf" data-peran="ikon"></i></span>
                            <span class="kontrak-berkas-info">
                                <span class="d-block kontrak-berkas-nama" data-peran="nama"></span>
                                <span class="kontrak-berkas-meta" data-peran="meta"></span>
                            </span>
                            <button type="button" class="kontrak-berkas-hapus" data-peran="hapus"
                                    title="Hapus berkas" aria-label="Hapus berkas">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <div class="kontrak-meter d-none" data-peran="meter">
                            <div class="kontrak-meter-bar"><span></span></div>
                            <div class="kontrak-meter-label">
                                <span data-peran="meter-kiri"></span>
                                <span data-peran="meter-kanan"></span>
                            </div>
                        </div>
                        <div class="kontrak-berkas-status" data-peran="status">
                            <i class="bi bi-info-circle-fill" data-peran="status-ikon"></i>
                            <span data-peran="status-teks"></span>
                        </div>
                    </div>

                    @if($kontrak->file_kontrak)
                        <div class="kontrak-berkas mt-2 is-ok">
                            <div class="kontrak-berkas-atas">
                                <span class="kontrak-berkas-ikon"><i class="bi bi-file-earmark-check"></i></span>
                                <span class="kontrak-berkas-info">
                                    <span class="d-block kontrak-berkas-nama">Berkas kontrak tersimpan</span>
                                    <span class="kontrak-berkas-meta">
                                        @if($ukuranFileKontrak ?? null)
                                            <span class="kontrak-badge"><i class="bi bi-hdd"></i>{{ $ukuranFileKontrak }}</span>
                                        @endif
                                        <span>Unggah berkas baru di atas untuk menggantinya.</span>
                                    </span>
                                </span>
                                <a href="{{ route('jasa.mitra.kontrak.download', [$mitra, $kontrak]) }}"
                                   class="btn btn-sm btn-light border fw-bold text-secondary">
                                    <i class="bi bi-download me-1"></i>Unduh
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="3">{{ old('keterangan', $kontrak->keterangan) }}</textarea>
                </div>
            </div>
        </div>

        <div class="jasa-form-section" id="scope-layanan" style="scroll-margin-top: 90px;">
            <div class="jasa-section-title">
                <span class="jasa-section-icon"><i class="bi bi-diagram-3"></i></span>
                <div>
                    <h6>Scope Layanan Kontrak</h6>
                    <p>Layanan yang dicentang di sini menjadi <b>layanan aktif mitra</b> selama kontrak ini aktif — dipakai untuk konsesi, PJP2U, dan tagihan.</p>
                </div>
            </div>

            @if($grupScope->isEmpty())
                <div class="alert alert-info mb-0">Belum ada item tarif aktif di master layanan. Kontrak tetap dapat disimpan sebagai dokumen umum.</div>
            @else
                <div class="ks-scope" id="ksScope">
                    <div class="ks-toolbar">
                        <div class="ks-search">
                            <i class="bi bi-search"></i>
                            <input type="search" id="ksSearch" class="form-control" placeholder="Cari kode, nama layanan, atau kategori…" autocomplete="off">
                        </div>
                        <div class="ks-tools">
                            <label class="ks-only">
                                <input type="checkbox" id="ksOnlySelected" class="form-check-input"> Tampilkan yang terpilih saja
                            </label>
                            <button type="button" class="btn btn-sm btn-light border fw-semibold" data-ks-expand="1"><i class="bi bi-arrows-expand"></i> Buka semua</button>
                            <button type="button" class="btn btn-sm btn-light border fw-semibold" data-ks-expand="0"><i class="bi bi-arrows-collapse"></i> Tutup semua</button>
                        </div>
                    </div>

                    <div class="ks-summary" id="ksSummary">
                        <div class="ks-summary-head">
                            <span class="ks-count"><b id="ksCount">{{ count($selectedKontrakLayananIds) }}</b> layanan dalam scope</span>
                            <button type="button" class="btn btn-link btn-sm p-0 text-danger fw-semibold" id="ksClear">Kosongkan pilihan</button>
                        </div>
                        <div class="ks-chips" id="ksChips"></div>
                        <div class="ks-empty-warn" id="ksEmptyWarn">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <span>Belum ada layanan dipilih. Kontrak tanpa scope <b>tidak memberi layanan apa pun</b> ke mitra — layanan, konsesi, dan PJP2U mitra hanya berasal dari scope kontrak aktif.</span>
                        </div>
                    </div>

                    <div class="ks-groups">
                        @foreach($grupScope as $rootId => $grup)
                            <details class="ks-group" data-ks-group @if($grup['terpilih'] > 0) open @endif>
                                <summary>
                                    <i class="bi bi-chevron-right ks-chev"></i>
                                    <i class="bi bi-folder2-open text-primary"></i>
                                    <span class="ks-group-name">{{ $grup['nama'] }}</span>
                                    <span class="ks-badge" data-ks-badge>{{ $grup['terpilih'] }}/{{ $grup['jumlah'] }}</span>
                                    <button type="button" class="ks-all" data-ks-all aria-pressed="false" title="Pilih / lepas semua layanan di kategori ini (yang sedang tampil)">
                                        <i class="bi bi-square"></i> Pilih semua
                                    </button>
                                </summary>
                                <div class="ks-group-body">
                                    @foreach($grup['sub'] as $sub => $items)
                                        @if($sub !== '')
                                            <div class="ks-sub" data-ks-sub><i class="bi bi-arrow-return-right"></i> {{ $sub }}</div>
                                        @endif
                                        <div class="ks-items" data-ks-items>
                                            @foreach($items as $item)
                                                @php($l = $item['layanan'])
                                                @php($kode = $l->kode_layanan ?: str_pad($l->id, 6, '0', STR_PAD_LEFT))
                                                <label class="ks-item" data-ks-item data-search="{{ \Illuminate\Support\Str::lower($kode . ' ' . $l->nama_layanan . ' ' . $sub . ' ' . $grup['nama']) }}" title="{{ $layananPath($l) }}">
                                                    <input type="checkbox" name="layanan_ids[]" value="{{ $l->id }}" class="form-check-input" data-nama="{{ $l->nama_layanan }}" @checked(in_array((int) $l->id, $selectedKontrakLayananIds, true))>
                                                    <span class="ks-item-text">
                                                        <span class="ks-item-nama">{{ $l->nama_layanan }}</span>
                                                        <span class="ks-item-meta"><span class="ks-kode">{{ $kode }}</span>@if($l->satuan) · {{ $l->satuan }}@endif</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </div>
                    <div class="ks-noresult" id="ksNoResult" hidden><i class="bi bi-search"></i> Tidak ada layanan yang cocok dengan pencarian.</div>
                </div>
            @endif
        </div>

        <div class="jasa-action-footer ks-sticky-footer">
            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-end gap-2">
                @if($grupScope->isNotEmpty())
                    <span class="ks-footer-count me-sm-auto" id="ksFooterCount"><i class="bi bi-diagram-3"></i> <b>{{ count($selectedKontrakLayananIds) }}</b> layanan dalam scope</span>
                @endif
                <a href="{{ route('jasa.mitra.show', $mitra) }}" class="btn btn-light fw-bold text-secondary border px-4">Batal</a>
                <button class="btn btn-primary fw-bold px-4" type="submit">
                    <i class="bi bi-save me-1"></i>Simpan Kontrak
                </button>
            </div>
        </div>
    </div>
</form>

<script>
/**
 * Area unggah berkas kontrak: seret-lepas, unggah latar belakang, dan kartu status.
 *
 * Begitu berkas dipilih, ia langsung dikirim ke endpoint pratinjau. Server
 * mengompresnya dengan Ghostscript lalu mengembalikan angka SEBENARNYA — bukan
 * perkiraan — beserta token. Token itu yang dikirim saat form disimpan, jadi
 * berkas tidak diunggah dua kali dan Simpan terasa instan.
 *
 * Bila JavaScript gagal atau unggahan latar belakang bermasalah, berkas
 * dikembalikan ke input sehingga form tetap bekerja seperti biasa.
 */
(function () {
    const zona    = document.getElementById('kontrakUpload');
    const input   = document.getElementById('fileKontrakInput');
    const tokenEl = document.getElementById('fileKontrakToken');
    const kartu   = document.getElementById('kontrakBerkas');
    if (!zona || !input || !tokenEl || !kartu) return;

    const el = (peran) => kartu.querySelector(`[data-peran="${peran}"]`);
    const ikon       = el('ikon');
    const nama       = el('nama');
    const meta       = el('meta');
    const tombolHapus= el('hapus');
    const meter      = el('meter');
    const bar        = kartu.querySelector('.kontrak-meter-bar > span');
    const meterKiri  = el('meter-kiri');
    const meterKanan = el('meter-kanan');
    const statusIkon = el('status-ikon');
    const statusTeks = el('status-teks');

    const form   = input.closest('form');
    const submit = form ? form.querySelector('button[type="submit"]') : null;

    const urlPratinjau = input.dataset.urlPratinjau;
    const maxBytes = parseInt(input.dataset.maxKb, 10) * 1024;
    const csrf = document.querySelector('meta[name="csrf-token"]');
    const wajib = input.hasAttribute('required');

    let berkasTerpilih = null;
    let permintaan = null;

    const ukuran = (b) => b >= 1048576
        ? (b / 1048576).toFixed(2) + ' MB'
        : (b / 1024).toFixed(1) + ' KB';

    /** Render kartu berkas. Bagian yang tidak diisi akan disembunyikan. */
    function render({ nadaKartu, ikonBerkas, spin, namaBerkas, lencana, status, ikonStatus, persen, kiri, kanan }) {
        kartu.classList.remove('d-none', 'is-proses', 'is-ok', 'is-warning', 'is-danger');
        kartu.classList.add('is-' + nadaKartu);

        ikon.className = 'bi ' + ikonBerkas + (spin ? ' kontrak-spin' : '');
        nama.textContent = namaBerkas;

        meta.innerHTML = '';
        (lencana || []).forEach((b) => {
            const s = document.createElement('span');
            s.className = b.polos ? '' : 'kontrak-badge';
            s.innerHTML = (b.ikon ? `<i class="bi ${b.ikon}"></i>` : '') + b.teks;
            meta.appendChild(s);
        });

        statusIkon.className = 'bi ' + (ikonStatus || 'bi-info-circle-fill');
        statusTeks.textContent = status || '';

        if (typeof persen === 'number') {
            meter.classList.remove('d-none');
            meterKiri.textContent = kiri || '';
            meterKanan.textContent = kanan || '';
            requestAnimationFrame(() => { bar.style.width = Math.max(0, Math.min(100, persen)) + '%'; });
        } else {
            meter.classList.add('d-none');
            bar.style.width = '0';
        }
    }

    function kunciSimpan(terkunci, teks) {
        if (!submit) return;
        submit.disabled = terkunci;
        submit.innerHTML = terkunci
            ? `<span class="spinner-border spinner-border-sm me-1"></span>${teks}`
            : '<i class="bi bi-save me-1"></i>Simpan Kontrak';
    }

    /** Kembalikan berkas ke input agar form tetap bisa dikirim tanpa token. */
    function pulihkanBerkas() {
        if (!berkasTerpilih) return;
        const dt = new DataTransfer();
        dt.items.add(berkasTerpilih);
        input.files = dt.files;
        if (wajib) input.setAttribute('required', 'required');
    }

    function bersihkan() {
        if (permintaan) { permintaan.abort(); permintaan = null; }
        berkasTerpilih = null;
        tokenEl.value = '';
        input.value = '';
        if (wajib) input.setAttribute('required', 'required');
        kartu.classList.add('d-none');
        kunciSimpan(false);
    }

    tombolHapus.addEventListener('click', bersihkan);

    // ---- Seret-lepas ----
    ['dragenter', 'dragover'].forEach((ev) => {
        zona.addEventListener(ev, (e) => { e.preventDefault(); zona.classList.add('is-dragging'); });
    });
    ['dragleave', 'drop'].forEach((ev) => {
        zona.addEventListener(ev, (e) => { e.preventDefault(); zona.classList.remove('is-dragging'); });
    });
    zona.addEventListener('drop', (e) => {
        const berkas = e.dataTransfer && e.dataTransfer.files;
        if (!berkas || !berkas.length) return;
        const dt = new DataTransfer();
        dt.items.add(berkas[0]);
        input.files = dt.files;
        input.dispatchEvent(new Event('change'));
    });

    input.addEventListener('change', function () {
        const file = this.files && this.files[0];

        if (permintaan) { permintaan.abort(); permintaan = null; }

        if (!file) {
            bersihkan();
            return;
        }

        berkasTerpilih = file;
        const dasar = { namaBerkas: file.name };

        if (!/\.pdf$/i.test(file.name)) {
            tokenEl.value = '';
            render({
                ...dasar, nadaKartu: 'danger', ikonBerkas: 'bi-file-earmark-x',
                lencana: [{ teks: ukuran(file.size), ikon: 'bi-hdd' }],
                ikonStatus: 'bi-x-octagon-fill',
                status: 'Hanya berkas PDF yang bisa diunggah. Pilih berkas lain.',
            });
            return;
        }

        if (file.size > maxBytes) {
            tokenEl.value = '';
            render({
                ...dasar, nadaKartu: 'danger', ikonBerkas: 'bi-file-earmark-x',
                lencana: [{ teks: ukuran(file.size), ikon: 'bi-hdd' }],
                ikonStatus: 'bi-exclamation-octagon-fill',
                status: 'Ukuran melampaui batas 20 MB. Silakan pindai ulang dengan resolusi lebih rendah.',
            });
            return;
        }

        // ---- Unggah latar belakang ----
        const data = new FormData();
        data.append('file_kontrak', file);
        if (tokenEl.value) data.append('token_lama', tokenEl.value);

        tokenEl.value = '';
        kunciSimpan(true, 'Memproses berkas…');

        render({
            ...dasar, nadaKartu: 'proses', ikonBerkas: 'bi-arrow-repeat', spin: true,
            lencana: [{ teks: ukuran(file.size), ikon: 'bi-hdd' }],
            ikonStatus: 'bi-cloud-arrow-up-fill',
            status: 'Mengunggah berkas dan mengompresnya di server…',
            persen: 5, kiri: 'Mengunggah', kanan: '0%',
        });

        const xhr = new XMLHttpRequest();
        permintaan = xhr;
        xhr.open('POST', urlPratinjau);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        if (csrf) xhr.setRequestHeader('X-CSRF-TOKEN', csrf.content);

        xhr.upload.addEventListener('progress', (e) => {
            if (!e.lengthComputable) return;
            const persen = Math.round((e.loaded / e.total) * 100);
            bar.style.width = Math.max(5, persen * 0.9) + '%';
            if (persen < 100) {
                meterKiri.textContent = 'Mengunggah';
                meterKanan.textContent = persen + '%';
            } else {
                meterKiri.textContent = 'Mengompres di server';
                meterKanan.textContent = 'mohon tunggu…';
            }
        });

        xhr.addEventListener('load', () => {
            permintaan = null;
            kunciSimpan(false);

            let res = null;
            try { res = JSON.parse(xhr.responseText); } catch (e) { /* ditangani di bawah */ }

            if (xhr.status !== 200 || !res || !res.ok) {
                const pesan = (res && (res.pesan || (res.errors && res.errors.file_kontrak && res.errors.file_kontrak[0])))
                    || 'Berkas gagal diproses di server.';
                pulihkanBerkas();
                render({
                    ...dasar, nadaKartu: 'warning', ikonBerkas: 'bi-exclamation-triangle',
                    lencana: [{ teks: ukuran(file.size), ikon: 'bi-hdd' }],
                    ikonStatus: 'bi-exclamation-triangle-fill',
                    status: pesan + ' Berkas tetap akan diunggah saat Anda menekan Simpan.',
                });
                return;
            }

            // Berhasil: pakai token, kosongkan input agar tidak terkirim ulang.
            tokenEl.value = res.token;
            input.value = '';
            input.removeAttribute('required');

            if (res.dikompres) {
                render({
                    ...dasar, nadaKartu: 'ok', ikonBerkas: 'bi-file-earmark-zip',
                    lencana: [
                        { teks: res.ukuran_akhir, ikon: 'bi-hdd' },
                        { teks: `hemat ${res.hemat_persen}%`, ikon: 'bi-graph-down-arrow' },
                    ],
                    ikonStatus: 'bi-check-circle-fill',
                    status: 'Berkas siap. Tekan Simpan untuk menyelesaikan.',
                    persen: res.hemat_persen,
                    kiri: `Sebelum ${res.ukuran_asli}`,
                    kanan: `Sesudah ${res.ukuran_akhir}`,
                });
                return;
            }

            const bertandaTangan = res.alasan === 'signed';
            render({
                ...dasar,
                nadaKartu: bertandaTangan ? 'warning' : 'ok',
                ikonBerkas: bertandaTangan ? 'bi-patch-check' : 'bi-file-earmark-check',
                lencana: [
                    { teks: res.ukuran_akhir, ikon: 'bi-hdd' },
                    bertandaTangan
                        ? { teks: 'e-Meterai / TTD digital', ikon: 'bi-shield-check' }
                        : { teks: 'tanpa kompresi', ikon: 'bi-dash-circle' },
                ],
                ikonStatus: bertandaTangan ? 'bi-shield-fill-check' : 'bi-check-circle-fill',
                status: res.pesan + ' Berkas siap, tekan Simpan untuk menyelesaikan.',
            });
        });

        xhr.addEventListener('error', () => {
            permintaan = null;
            kunciSimpan(false);
            pulihkanBerkas();
            render({
                ...dasar, nadaKartu: 'warning', ikonBerkas: 'bi-wifi-off',
                lencana: [{ teks: ukuran(file.size), ikon: 'bi-hdd' }],
                ikonStatus: 'bi-exclamation-triangle-fill',
                status: 'Koneksi terputus saat memproses. Berkas tetap akan diunggah saat Anda menekan Simpan.',
            });
        });

        xhr.addEventListener('abort', () => { kunciSimpan(false); });

        xhr.send(data);
    });
})();
</script>

<script>
/**
 * Scope Layanan Kontrak: pohon per kategori dengan pencarian, filter "terpilih saja",
 * pilih-semua per kategori, ringkasan chip, dan penghitung di bilah simpan.
 * Checkbox asli (name="layanan_ids[]") tetap menjadi sumber data yang dikirim form.
 */
(function () {
    const scope = document.getElementById('ksScope');
    if (!scope) return;

    const boxes = Array.from(scope.querySelectorAll('input[name="layanan_ids[]"]'));
    const groups = Array.from(scope.querySelectorAll('[data-ks-group]'));
    const search = document.getElementById('ksSearch');
    const onlySelected = document.getElementById('ksOnlySelected');
    const chips = document.getElementById('ksChips');
    const count = document.getElementById('ksCount');
    const footer = document.getElementById('ksFooterCount');
    const noResult = document.getElementById('ksNoResult');

    function itemsOf(group) { return Array.from(group.querySelectorAll('[data-ks-item]')); }
    function boxOf(item) { return item.querySelector('input[name="layanan_ids[]"]'); }

    function renderSummary() {
        const selected = boxes.filter((b) => b.checked);
        count.textContent = selected.length;
        if (footer) {
            footer.querySelector('b').textContent = selected.length;
            footer.classList.toggle('is-empty', selected.length === 0);
        }
        scope.classList.toggle('is-empty', selected.length === 0);

        chips.innerHTML = '';
        selected.forEach((b) => {
            const chip = document.createElement('span');
            chip.className = 'ks-chip';
            const text = document.createElement('span');
            text.textContent = b.dataset.nama;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.title = 'Hapus dari scope';
            btn.setAttribute('aria-label', 'Hapus ' + b.dataset.nama + ' dari scope');
            btn.innerHTML = '&times;';
            btn.addEventListener('click', () => { b.checked = false; update(); });
            chip.append(text, btn);
            chips.appendChild(chip);
        });

        groups.forEach((g) => {
            const items = itemsOf(g);
            const picked = items.filter((i) => boxOf(i).checked).length;
            const badge = g.querySelector('[data-ks-badge]');
            badge.textContent = picked + '/' + items.length;
            badge.classList.toggle('has', picked > 0);

            const visible = items.filter((i) => !i.hidden);
            const visPicked = visible.filter((i) => boxOf(i).checked).length;
            const all = g.querySelector('[data-ks-all]');
            const state = visible.length && visPicked === visible.length ? 'all' : (visPicked > 0 ? 'some' : 'none');
            all.dataset.state = state;
            all.setAttribute('aria-pressed', state === 'all' ? 'true' : 'false');
            all.querySelector('i').className = state === 'all' ? 'bi bi-check-square-fill' : (state === 'some' ? 'bi bi-dash-square-fill' : 'bi bi-square');
        });
    }

    function applyFilter() {
        const terms = (search.value || '').toLowerCase().split(/\s+/).filter(Boolean);
        const only = onlySelected.checked;
        let anyVisible = false;

        groups.forEach((g) => {
            let groupVisible = 0;
            g.querySelectorAll('[data-ks-items]').forEach((list) => {
                let listVisible = 0;
                list.querySelectorAll('[data-ks-item]').forEach((item) => {
                    const hay = item.dataset.search;
                    const show = terms.every((t) => hay.includes(t)) && (!only || boxOf(item).checked);
                    item.hidden = !show;
                    if (show) listVisible++;
                });
                list.hidden = listVisible === 0;
                const sub = list.previousElementSibling;
                if (sub && sub.hasAttribute('data-ks-sub')) sub.hidden = listVisible === 0;
                groupVisible += listVisible;
            });
            g.hidden = groupVisible === 0;
            if (terms.length || only) g.open = groupVisible > 0;
            anyVisible = anyVisible || groupVisible > 0;
        });
        noResult.hidden = anyVisible;
        renderSummary();
    }

    function update() {
        if (onlySelected.checked) applyFilter(); else renderSummary();
    }

    boxes.forEach((b) => b.addEventListener('change', update));

    groups.forEach((g) => {
        g.querySelector('[data-ks-all]').addEventListener('click', (e) => {
            e.preventDefault(); // jangan ikut membuka/menutup kategori
            e.stopPropagation();
            const visible = itemsOf(g).filter((i) => !i.hidden);
            const target = e.currentTarget.dataset.state !== 'all';
            visible.forEach((i) => { boxOf(i).checked = target; });
            update();
        });
    });

    let timer;
    search.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(applyFilter, 120); });
    search.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });
    onlySelected.addEventListener('change', applyFilter);

    scope.querySelectorAll('[data-ks-expand]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const open = btn.dataset.ksExpand === '1';
            groups.forEach((g) => { if (!g.hidden) g.open = open; });
        });
    });

    document.getElementById('ksClear').addEventListener('click', () => {
        if (!confirm('Kosongkan semua layanan dari scope kontrak ini?')) return;
        boxes.forEach((b) => { b.checked = false; });
        update();
    });

    renderSummary();
})();
</script>
@endsection
