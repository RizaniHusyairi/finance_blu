@extends('layouts.app')
@section('title', ($item->exists ? 'Ubah' : 'Catat') . ' Pemakaian Garbarata')

@section('content')
@include('super_admin_jasa.laporan._styles')
@php
    $action = $item->exists
        ? route('pemakaian-garbarata.update', $item->id)
        : route('pemakaian-garbarata.store');
    $rowDefaults = [[
        'tanggal' => old('rows.0.tanggal', optional($item->tanggal)->toDateString() ?: now()->toDateString()),
        'registrasi_pesawat' => old('rows.0.registrasi_pesawat', $item->registrasi_pesawat),
        'flight_arr' => old('rows.0.flight_arr', $item->flight_arr),
        'flight_dep' => old('rows.0.flight_dep', $item->flight_dep),
        'route' => old('rows.0.route', $item->route),
        'docking' => old('rows.0.docking', optional($item->docking_at)->format('H:i')),
        'undocking' => old('rows.0.undocking', optional($item->undocking_at)->format('H:i')),
        'type_pesawat' => old('rows.0.type_pesawat', $item->type_pesawat),
        'bobot_ton' => old('rows.0.bobot_ton', $item->bobot_ton ?? 77),
        'tarif_garbarata' => old('rows.0.tarif_garbarata', $item->tarif_garbarata ?? 280000),
        'existing_file_url' => ($item->exists && $item->file_pendukung) ? route('pemakaian-garbarata.file', $item->id) : null,
    ]];
    $oldRows = old('rows');
    if (is_array($oldRows) && count($oldRows) > 0) {
        $rowDefaults = $oldRows;
    }
@endphp

<style>
    @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation-duration: .001s !important; animation-delay: 0s !important; animation-iteration-count: 1 !important; transition-duration: .001s !important; } }
    /* ═══════════════ HERO ═══════════════ */
    .ib-hero {
        position: relative; overflow: hidden;
        background: linear-gradient(135deg, #0a1f3c 0%, #11366b 50%, #1d4ed8 100%);
        border-radius: 22px; color: #fff;
        padding: 26px 32px 28px; margin-bottom: 22px;
        box-shadow: 0 18px 50px rgba(15,47,87,.25);
        animation: ibFadeDown .55s cubic-bezier(.16,1,.3,1) both;
    }
    .ib-hero::before {
        content: ''; position: absolute; inset: 0;
        background: radial-gradient(circle at 78% 50%, rgba(96,165,250,.25), transparent 55%);
        pointer-events: none;
    }
    .ib-hero-decor {
        position: absolute; right: 16px; top: 50%; transform: translateY(-50%);
        width: 380px; height: 150px; opacity: .55; pointer-events: none;
    }
    .ib-hero-decor .ib-plane {
        animation: ibPlaneFloat 6s ease-in-out infinite;
        transform-origin: center;
    }
    @keyframes ibPlaneFloat {
        0%,100% { transform: translate(0,0) rotate(-4deg); }
        50%     { transform: translate(-14px,-7px) rotate(0deg); }
    }
    .ib-breadcrumb {
        font-size: .7rem; letter-spacing: .1em; text-transform: uppercase;
        font-weight: 700; color: rgba(255,255,255,.7);
        display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap;
    }
    .ib-breadcrumb .crumb-sep { color: rgba(255,255,255,.4); }
    .ib-breadcrumb .crumb-current { color: #fbbf24; }
    .ib-hero h3 {
        font-weight: 800; margin: 8px 0 4px;
        background: linear-gradient(180deg,#fff,#cfe1ff);
        -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
        position: relative; z-index: 1;
    }
    .ib-hero p { color: rgba(255,255,255,.85); position: relative; z-index: 1; }
    .ib-hero-btn {
        background: rgba(255,255,255,.95); color: #0f2f57; border: 0;
        border-radius: 10px; padding: .55rem 1.1rem; font-weight: 700;
        display: inline-flex; align-items: center; gap: 6px;
        transition: transform .15s ease, box-shadow .15s ease;
        position: relative; z-index: 2;
    }
    .ib-hero-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,.2); color: #1d4ed8; }

    /* ═══════════════ SECTIONS ═══════════════ */
    .ib-section {
        background: #fff; border-radius: 18px; padding: 22px 24px;
        box-shadow: 0 4px 16px rgba(15,47,87,.06);
        margin-bottom: 18px;
        animation: ibFadeUp .55s cubic-bezier(.16,1,.3,1) both;
    }
    .ib-section:nth-of-type(1) { animation-delay: .08s; }
    .ib-section:nth-of-type(2) { animation-delay: .18s; }
    .ib-section:nth-of-type(3) { animation-delay: .28s; }
    @keyframes ibFadeUp { from { opacity:0; transform: translateY(16px); } to { opacity:1; transform: translateY(0); } }
    @keyframes ibFadeDown { from { opacity:0; transform: translateY(-16px); } to { opacity:1; transform: translateY(0); } }

    .ib-section-head { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
    .ib-step-num {
        width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
        background: linear-gradient(135deg,#3b82f6,#1d4ed8); color: #fff;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: .95rem;
        box-shadow: 0 4px 12px rgba(29,78,216,.3);
        animation: ibStepPulse 2.8s ease-in-out infinite;
    }
    @keyframes ibStepPulse {
        0%,100% { box-shadow: 0 4px 12px rgba(29,78,216,.3), 0 0 0 0 rgba(59,130,246,.4); }
        50%     { box-shadow: 0 4px 12px rgba(29,78,216,.35), 0 0 0 6px rgba(59,130,246,0); }
    }
    .ib-section-title { font-weight: 800; color: #0f2f57; font-size: 1.05rem; margin: 0; }
    .ib-section-sub { font-size: .8rem; color: #64748b; margin-top: 2px; }

    .ib-section .form-label {
        font-size: .78rem; font-weight: 700; color: #0f2f57;
        text-transform: none; letter-spacing: 0; margin-bottom: 6px;
    }
    .ib-section .form-control,
    .ib-section .form-select {
        border-radius: 10px; border-color: #e2e8f0;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .ib-section .form-control:focus,
    .ib-section .form-select:focus {
        border-color: #1d4ed8; box-shadow: 0 0 0 .2rem rgba(29,78,216,.12);
    }

    .ib-btn-add {
        background: linear-gradient(135deg,#1d4ed8,#3b82f6); color: #fff; border: 0;
        border-radius: 10px; padding: .55rem 1rem; font-weight: 700;
        display: inline-flex; align-items: center; gap: 6px;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .ib-btn-add:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(29,78,216,.3); color: #fff; }

    /* ═══════════════ TABLE ═══════════════ */
    .amc-table { margin: 0; }
    .amc-table th {
        background:#f8fafc; color:#475569; font-size:.7rem;
        letter-spacing:.04em; text-transform:uppercase; white-space:nowrap;
        padding: 12px 8px; border-color: #e2e8f0;
    }
    .amc-table td { vertical-align:middle; padding: 10px 8px; border-color: #f1f5f9; }
    .amc-table .form-control,
    .amc-table .form-select { min-width:88px; border-radius:10px; font-size:.84rem; border-color:#e2e8f0; }
    .amc-table .amc-small { min-width:72px; }
    .amc-table tbody tr {
        transition: background .2s ease;
        animation: ibFadeRow .35s ease both;
    }
    @keyframes ibFadeRow { from { opacity:0; transform: translateX(-6px); } to { opacity:1; transform: translateX(0); } }
    .amc-table tbody tr:hover { background: #f8fafc; }
    .amc-no {
        display: inline-flex; width: 28px; height: 28px; border-radius: 50%;
        background: linear-gradient(135deg,#3b82f6,#1d4ed8); color: #fff;
        align-items: center; justify-content: center; font-weight: 800; font-size: .8rem;
        box-shadow: 0 2px 6px rgba(29,78,216,.25);
        line-height: 1;
    }
    .amc-total-pill {
        display:inline-flex; align-items:center; justify-content:center; min-width:74px;
        border:1px solid #bfdbfe; border-radius:10px; background:#eff6ff;
        padding:.4rem .55rem; color:#1d4ed8; font-weight:800; font-size:.84rem;
    }
    .ib-empty {
        background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 12px;
        padding: 14px; text-align: center; color: #64748b; font-size: .85rem;
        margin-top: 10px;
    }
    .btn-remove-row {
        width: 30px; height: 30px; border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        background: #fff; color: #dc2626; border: 1px solid #fecaca;
        transition: background .15s ease, color .15s ease;
    }
    .btn-remove-row:hover { background: #dc2626; color: #fff; }

    /* ═══════════════ KETERANGAN ═══════════════ */
    .ib-keterangan { position: relative; }
    .ib-char-count {
        position: absolute; right: 12px; bottom: 8px;
        font-size: .72rem; color: #94a3b8; font-weight: 600;
        background: #fff; padding: 0 4px;
    }

    /* ═══════════════ FOOTER ACTIONS ═══════════════ */
    .ib-footer {
        background: #fff; border-radius: 18px; padding: 16px 24px;
        display: flex; gap: 10px; justify-content: flex-end; align-items: center;
        box-shadow: 0 4px 16px rgba(15,47,87,.06);
        animation: ibFadeUp .55s cubic-bezier(.16,1,.3,1) both;
        animation-delay: .38s;
    }
    .ib-btn-cancel {
        background: #fff; color: #475569; border: 1.5px solid #e2e8f0;
        border-radius: 10px; padding: .55rem 1.1rem; font-weight: 700;
        display: inline-flex; align-items: center; gap: 6px;
        transition: background .15s ease, border-color .15s ease;
    }
    .ib-btn-cancel:hover { background: #f8fafc; border-color: #94a3b8; color: #334155; }
    .ib-btn-submit {
        background: linear-gradient(135deg,#1d4ed8,#3b82f6); color: #fff; border: 0;
        border-radius: 10px; padding: .55rem 1.4rem; font-weight: 700;
        display: inline-flex; align-items: center; gap: 6px;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .ib-btn-submit:hover { transform: translateY(-1px); box-shadow: 0 10px 22px rgba(29,78,216,.35); color: #fff; }

    /* ═══════════════ SIDEBAR ═══════════════ */
    .ib-side-card {
        background: #fff; border-radius: 16px; padding: 18px 18px;
        box-shadow: 0 4px 16px rgba(15,47,87,.06);
        margin-bottom: 14px;
        animation: ibFadeRight .55s cubic-bezier(.16,1,.3,1) both;
    }
    .ib-side-card:nth-of-type(1) { animation-delay: .15s; }
    .ib-side-card:nth-of-type(2) { animation-delay: .25s; }
    .ib-side-card:nth-of-type(3) { animation-delay: .35s; }
    .ib-side-card:nth-of-type(4) { animation-delay: .45s; }
    @keyframes ibFadeRight { from { opacity:0; transform: translateX(16px); } to { opacity:1; transform: translateX(0); } }

    .ib-side-head { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
    .ib-side-icon {
        width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: #eff6ff; color: #1d4ed8; font-size: 1rem;
    }
    .ib-side-title { font-weight: 800; color: #0f2f57; font-size: .9rem; margin: 0; }

    .ib-big-number {
        font-size: 2.6rem; font-weight: 900; color: #1d4ed8; line-height: 1;
        background: linear-gradient(135deg,#1d4ed8,#3b82f6);
        -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
        transition: transform .3s cubic-bezier(.16,1,.3,1);
        display: inline-block;
    }
    .ib-big-unit { font-size: .85rem; color: #64748b; font-weight: 600; margin-left: 6px; }
    .ib-big-sub { font-size: .78rem; color: #64748b; margin-top: 4px; }

    .ib-stat-list { margin-top: 14px; border-top: 1px dashed #e2e8f0; padding-top: 12px; }
    .ib-stat-list .row-stat {
        display: flex; justify-content: space-between; align-items: center;
        padding: 6px 0; font-size: .82rem;
    }
    .ib-stat-list .row-stat .lbl { color: #64748b; }
    .ib-stat-list .row-stat .val { font-weight: 800; color: #0f2f57; }

    .ib-tip {
        display: flex; align-items: flex-start; gap: 8px;
        font-size: .8rem; color: #334155; padding: 4px 0;
    }
    .ib-tip i { color: #059669; flex-shrink: 0; margin-top: 2px; font-size: .9rem; }

    .ib-side-link {
        display: inline-flex; align-items: center; gap: 6px;
        background: #eff6ff; color: #1d4ed8; border: 0;
        border-radius: 8px; padding: .4rem .8rem; font-weight: 700; font-size: .78rem;
        text-decoration: none; margin-top: 8px;
        transition: background .15s ease;
    }
    .ib-side-link:hover { background: #dbeafe; color: #1d4ed8; }

    /* Pulse on stat update */
    .ib-pulse-update { animation: ibPulseUpdate .4s ease; }
    @keyframes ibPulseUpdate {
        0%   { transform: scale(1); }
        50%  { transform: scale(1.15); }
        100% { transform: scale(1); }
    }
</style>

<div class="sa-report-page">

    {{-- ═════════════ HERO ═════════════ --}}
    <div class="ib-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="flex-grow-1" style="position:relative; z-index:1;">
            <div class="ib-breadcrumb">
                <span>AMC</span>
                <span class="crumb-sep">›</span>
                <span>Pemakaian Garbarata</span>
                <span class="crumb-sep">›</span>
                <span class="crumb-current">{{ $item->exists ? 'Ubah' : 'Input Batch' }}</span>
            </div>
            <h3 class="ib-hero-title">{{ $item->exists ? 'Ubah Pemakaian Garbarata' : 'Input Batch Pemakaian Garbarata' }}</h3>
            <p class="mb-0 small">Input per maskapai dan periode. Sistem menghitung durasi, rentang per 2 jam, dan total otomatis.</p>
        </div>
        {{-- Decorative airport silhouette + airplane --}}
        <svg class="ib-hero-decor d-none d-md-block" viewBox="0 0 380 150" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <g stroke="#cfe1ff" stroke-width="1.2" opacity=".5" fill="none">
                <path d="M0 125 L380 125"/>
                <path d="M210 125 L220 95 L240 95 L246 125"/>
                <path d="M255 125 L260 105 L290 105 L295 125"/>
                <path d="M310 125 L315 80 L325 80 L330 125"/>
                <path d="M340 125 L345 90 L358 90 L363 125"/>
                <circle cx="230" cy="100" r="1.8"/>
                <circle cx="275" cy="110" r="1.8"/>
                <circle cx="320" cy="85" r="1.8"/>
                <circle cx="352" cy="95" r="1.8"/>
            </g>
            <g class="ib-plane" fill="#fff" opacity=".95">
                <path d="M70 70 L170 64 L185 50 L200 64 L255 60 L260 72 L200 78 L185 92 L170 78 L70 74 Z"/>
                <path d="M100 72 L118 92 L128 92 L118 72 Z" opacity=".75"/>
            </g>
        </svg>
        <a href="{{ route('pemakaian-garbarata.index') }}" class="ib-hero-btn"><i class="bi bi-arrow-left"></i>Kembali</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 rounded-3">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Periksa input berikut:</div>
            <ul class="mb-0 small">@foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
        </div>
    @endif

    {{-- ═════════════ MAIN + SIDEBAR LAYOUT ═════════════ --}}
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
        @csrf
        @if($item->exists) @method('PUT') @endif

        <div class="row g-3">
            {{-- ═════ MAIN ═════ --}}
            <div class="col-lg-9">

                {{-- Section 1: Data Dasar Batch --}}
                <div class="ib-section">
                    <div class="ib-section-head">
                        <span class="ib-step-num">1</span>
                        <div>
                            <h6 class="ib-section-title">Data Dasar Batch</h6>
                        </div>
                    </div>
                    @php
                        // AMC (operasional) tidak menentukan layanan tarif — ditetapkan Admin Jasa saat membuat tagihan.
                        $hideLayananTarif = auth()->user()?->hasRole('AMC') && ! auth()->user()?->hasRole('Super Admin');
                    @endphp
                    <div class="row g-3">
                        <div class="{{ $hideLayananTarif ? 'col-lg-6' : 'col-lg-4' }}">
                            <label class="form-label">Mitra / Maskapai <span class="text-danger">*</span></label>
                            <select name="mitra_jasa_id" class="form-select" required>
                                <option value="">-- Pilih mitra --</option>
                                @foreach($mitraOptions as $m)
                                    <option value="{{ $m->id }}" @selected(old('mitra_jasa_id', $item->mitra_jasa_id) == $m->id)>{{ $m->nama_mitra }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="{{ $hideLayananTarif ? 'col-lg-6' : 'col-lg-3' }}">
                            <label class="form-label">Periode Bulan</label>
                            <input type="month" name="periode_bulan" id="periodeBulan" class="form-control" value="{{ old('periode_bulan', optional($item->tanggal)->format('Y-m') ?: now()->format('Y-m')) }}">
                            <div class="form-text small">Dipakai untuk memudahkan input tanggal sebulan.</div>
                        </div>
                        @unless($hideLayananTarif)
                        <div class="col-lg-5">
                            <label class="form-label">Layanan Tarif Garbarata</label>
                            <select name="layanan_jasa_id" class="form-select">
                                <option value="">-- Tentukan saat tagihan --</option>
                                @foreach($garbarataLayanan as $l)
                                    <option value="{{ $l->id }}" @selected(old('layanan_jasa_id', $item->layanan_jasa_id) == $l->id)>{{ $l->nama_layanan }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        {{-- Pertahankan nilai lama agar tidak terhapus saat AMC mengedit --}}
                        <input type="hidden" name="layanan_jasa_id" value="{{ old('layanan_jasa_id', $item->layanan_jasa_id) }}">
                        @endunless
                        <div class="col-12">
                            <label class="form-label">Permohonan Non-Schedule (opsional)</label>
                            <select name="permohonan_non_schedule_id" class="form-select">
                                <option value="">-- Tidak terkait --</option>
                                @foreach($permohonanOptions as $p)
                                    <option value="{{ $p->id }}" @selected(old('permohonan_non_schedule_id', $item->permohonan_non_schedule_id) == $p->id)>
                                        {{ $p->nomor_surat }} ({{ $p->tanggal_surat?->format('d/m/Y') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Rincian --}}
                <div class="ib-section">
                    <div class="ib-section-head justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <span class="ib-step-num">2</span>
                            <div>
                                <h6 class="ib-section-title">Rincian Pemakaian Garbarata</h6>
                                <div class="ib-section-sub">Format mengikuti rekap bulanan: tanggal, reg, ARR/DEP, route, docking, undocking, type, bobot, waktu, rentang.</div>
                            </div>
                        </div>
                        @unless($item->exists)
                            <button type="button" class="ib-btn-add" id="btnAddRow"><i class="bi bi-plus-lg"></i>Tambah Rincian</button>
                        @endunless
                    </div>

                    <div class="table-responsive rounded-3" style="border:1px solid #e2e8f0;">
                        <table class="table amc-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">No</th>
                                    <th>Tanggal</th>
                                    <th>Reg</th>
                                    <th>ARR</th>
                                    <th>DEP</th>
                                    <th>Route</th>
                                    <th>Docking</th>
                                    <th>Undocking</th>
                                    <th>Type</th>
                                    <th>Bobot Ton</th>
                                    <th>Waktu</th>
                                    <th>Avio</th>
                                    <th class="text-center">Rentang</th>
                                    <th>File Pendukung</th>
                                    @unless($item->exists)<th class="text-center">Aksi</th>@endunless
                                </tr>
                            </thead>
                            <tbody id="garbarataRows">
                                @foreach($rowDefaults as $index => $row)
                                    @include('pemakaian_garbarata._row', ['index' => $index, 'row' => $row, 'editableMany' => ! $item->exists])
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="ib-empty" id="ibEmptyHint" style="display:none;">
                        <i class="bi bi-file-earmark-plus me-1"></i>Belum ada rincian. Klik <b>Tambah Rincian</b> untuk menambahkan data.
                    </div>
                </div>

                {{-- Section 3: Keterangan --}}
                <div class="ib-section">
                    <label class="form-label fw-bold" for="ibKeterangan">Keterangan Batch</label>
                    <div class="ib-keterangan">
                        <textarea name="keterangan" id="ibKeterangan" rows="2" class="form-control" maxlength="500" placeholder="Contoh: Rekap pemakaian Garbarata bulan Maret 2026">{{ old('keterangan', $item->keterangan) }}</textarea>
                        <span class="ib-char-count"><span id="ibCharCount">0</span> / 500</span>
                    </div>
                </div>

                {{-- Footer Actions --}}
                <div class="ib-footer">
                    <a href="{{ route('pemakaian-garbarata.index') }}" class="ib-btn-cancel"><i class="bi bi-x-lg"></i>Batal</a>
                    <button class="ib-btn-submit"><i class="bi bi-check2-circle"></i>{{ $item->exists ? 'Simpan Perubahan' : 'Simpan Siap Ditagih' }}</button>
                </div>
            </div>

            {{-- ═════ SIDEBAR ═════ --}}
            <div class="col-lg-3">

                {{-- Ringkasan Batch --}}
                <div class="ib-side-card">
                    <div class="ib-side-head">
                        <span class="ib-side-icon"><i class="bi bi-clipboard-data"></i></span>
                        <h6 class="ib-side-title">Ringkasan Batch</h6>
                    </div>
                    <div>
                        <span class="ib-big-number" id="batchRentang">0</span><span class="ib-big-unit">rentang</span>
                    </div>
                    <div class="ib-big-sub"><span id="batchFlight">0</span> rincian penerbangan</div>

                    <div class="ib-stat-list">
                        <div class="row-stat"><span class="lbl">Total Waktu</span><span class="val" id="batchWaktu">00:00</span></div>
                        <div class="row-stat"><span class="lbl">Total Rentang (2 Jam)</span><span class="val" id="batchRentangSum">0</span></div>
                        <div class="row-stat"><span class="lbl">Total Avio</span><span class="val" id="batchAvio">0</span></div>
                        <div class="row-stat"><span class="lbl">Total Bobot Ton</span><span class="val" id="batchBobot">0</span></div>
                    </div>
                </div>

                {{-- Tips Input --}}
                <div class="ib-side-card">
                    <div class="ib-side-head">
                        <span class="ib-side-icon" style="background:#fef3c7;color:#b45309;"><i class="bi bi-lightbulb"></i></span>
                        <h6 class="ib-side-title">Tips Input</h6>
                    </div>
                    <div class="ib-tip"><i class="bi bi-check-circle-fill"></i>Gunakan format tanggal dd/mm/yyyy.</div>
                    <div class="ib-tip"><i class="bi bi-check-circle-fill"></i>Docking & Undocking dalam format 24 jam.</div>
                    <div class="ib-tip"><i class="bi bi-check-circle-fill"></i>Waktu dihitung otomatis berdasarkan Docking & Undocking.</div>
                    <div class="ib-tip"><i class="bi bi-check-circle-fill"></i>Pastikan tipe pesawat dan bobot sesuai dokumen penerbangan.</div>
                </div>

                {{-- Cepat & Mudah --}}
                <div class="ib-side-card">
                    <div class="ib-side-head">
                        <span class="ib-side-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="bi bi-rocket-takeoff"></i></span>
                        <h6 class="ib-side-title">Cepat & Mudah</h6>
                    </div>
                    <div class="small text-muted">Gunakan periode bulan untuk mempercepat input data dalam satu bulan.</div>
                    <a href="#" class="ib-side-link">Pelajari Lebih Lanjut <i class="bi bi-box-arrow-up-right"></i></a>
                </div>

                {{-- Data aman --}}
                <div class="ib-side-card">
                    <div class="ib-side-head">
                        <span class="ib-side-icon" style="background:#ecfdf5;color:#059669;"><i class="bi bi-shield-check"></i></span>
                        <h6 class="ib-side-title">Data Anda aman</h6>
                    </div>
                    <div class="small text-muted">Setiap input tercatat dan dapat ditelusuri via audit.</div>
                </div>
            </div>
        </div>
    </form>
</div>

<template id="garbarataRowTemplate">
    @include('pemakaian_garbarata._row', ['index' => '__INDEX__', 'row' => [], 'editableMany' => true])
</template>

<script>
    (function () {
        const tbody = document.getElementById('garbarataRows');
        const template = document.getElementById('garbarataRowTemplate');
        const addButton = document.getElementById('btnAddRow');
        const periode = document.getElementById('periodeBulan');
        let rowIndex = tbody ? tbody.querySelectorAll('tr').length : 0;

        function money(value) {
            return new Intl.NumberFormat('id-ID').format(Math.round(Number(value) || 0));
        }

        function parseMinutes(value) {
            const match = String(value || '').match(/^(\d{1,2}):(\d{2})$/);
            if (!match) return null;
            const hour = parseInt(match[1], 10);
            const minute = parseInt(match[2], 10);
            if (hour > 23 || minute > 59) return null;
            return hour * 60 + minute;
        }

        function formatDuration(minutes) {
            const hour = Math.floor(minutes / 60);
            const rest = minutes % 60;
            return `${String(hour).padStart(2, '0')}:${String(rest).padStart(2, '0')}`;
        }

        function recalcRow(row) {
            const docking = parseMinutes(row.querySelector('.amc-docking')?.value);
            let undocking = parseMinutes(row.querySelector('.amc-undocking')?.value);
            let duration = 0;

            if (docking !== null && undocking !== null) {
                if (undocking < docking) undocking += 24 * 60;
                duration = Math.max(0, undocking - docking);
            }

            const rentang = duration > 0 ? Math.max(1, Math.ceil(duration / 120)) : 0;
            const avio = parseInt(row.querySelector('.amc-avio')?.value || '0', 10) || 0;
            const bobot = parseFloat(row.querySelector('input[name$="[bobot_ton]"]')?.value || '0') || 0;

            row.querySelector('.amc-waktu').textContent = formatDuration(duration);
            const rentangEl = row.querySelector('.amc-rentang');
            if (rentangEl) rentangEl.textContent = rentang;
            return { rentang, duration, avio, bobot };
        }

        function pulseUpdate(el) {
            if (!el) return;
            el.classList.remove('ib-pulse-update');
            void el.offsetWidth;
            el.classList.add('ib-pulse-update');
        }

        function recalcAll() {
            let totalRentang = 0;
            let totalFlight = 0;
            let totalDuration = 0;
            let totalAvio = 0;
            let totalBobot = 0;
            const rows = tbody.querySelectorAll('tr');
            rows.forEach((row, index) => {
                row.querySelector('.amc-no').textContent = index + 1;
                const t = recalcRow(row);
                totalRentang += t.rentang;
                totalDuration += t.duration;
                totalAvio += t.avio;
                totalBobot += t.bobot;
                totalFlight += 1;
            });

            const rEl = document.getElementById('batchRentang');
            const fEl = document.getElementById('batchFlight');
            const wEl = document.getElementById('batchWaktu');
            const rsEl = document.getElementById('batchRentangSum');
            const aEl = document.getElementById('batchAvio');
            const bEl = document.getElementById('batchBobot');
            const emptyHint = document.getElementById('ibEmptyHint');

            if (rEl && rEl.textContent != totalRentang) { rEl.textContent = totalRentang; pulseUpdate(rEl); }
            if (fEl) fEl.textContent = totalFlight;
            if (wEl) wEl.textContent = formatDuration(totalDuration);
            if (rsEl) rsEl.textContent = totalRentang;
            if (aEl) aEl.textContent = totalAvio;
            if (bEl) bEl.textContent = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(totalBobot);

            if (emptyHint) emptyHint.style.display = rows.length === 0 ? 'block' : 'none';
        }

        // Char counter for keterangan
        const ket = document.getElementById('ibKeterangan');
        const ketCount = document.getElementById('ibCharCount');
        function updateCharCount() {
            if (ket && ketCount) ketCount.textContent = ket.value.length;
        }
        ket?.addEventListener('input', updateCharCount);
        updateCharCount();

        function addRow() {
            const html = template.innerHTML.replaceAll('__INDEX__', rowIndex);
            tbody.insertAdjacentHTML('beforeend', html);
            const row = tbody.lastElementChild;
            const month = periode?.value || '';
            if (month && row.querySelector('.amc-tanggal')) {
                row.querySelector('.amc-tanggal').value = `${month}-01`;
            }
            rowIndex++;
            recalcAll();
        }

        addButton?.addEventListener('click', addRow);
        tbody?.addEventListener('input', recalcAll);
        tbody?.addEventListener('click', function (event) {
            const button = event.target.closest('.btn-remove-row');
            if (!button) return;
            if (tbody.querySelectorAll('tr').length <= 1) {
                alert('Minimal harus ada 1 rincian.');
                return;
            }
            button.closest('tr').remove();
            recalcAll();
        });

        recalcAll();
    })();
</script>

{{-- Select2 IIFE pushed to @stack('script') so it runs AFTER jQuery + Select2 are loaded by app-scripts. --}}
@push('script')
<script>
    (function () {
        const mitraSelect = document.querySelector('select[name="mitra_jasa_id"]');
        const tbody = document.getElementById('garbarataRows');
        const lookupUrl = @json(route('pemakaian-garbarata.jadwal-lookup'));
        let jadwalRows = [];
        let jadwalByArr = new Map();
        let jadwalByDep = new Map();

        function hasJq() {
            return !!(window.jQuery && typeof window.jQuery.fn.select2 === 'function');
        }

        function buildOptionsHtml(field) {
            // field = 'flight_arr' | 'flight_dep'
            let html = '<option value=""></option>';
            jadwalRows.forEach(r => {
                const v = r[field];
                if (!v) return;
                html += `<option value="${v}" data-route="${r.route || ''}" data-type="${r.aircraft_type || ''}" data-reg="${r.registrasi_pesawat || ''}" data-arr="${r.flight_arr || ''}" data-dep="${r.flight_dep || ''}">${v}</option>`;
            });
            return html;
        }

        function fillRowFromJadwal(rowEl, jadwal, isArr) {
            if (!jadwal) return;
            const reg = rowEl.querySelector('.amc-reg');
            const route = rowEl.querySelector('.amc-route');
            const type = rowEl.querySelector('.amc-type');
            const arrSel = rowEl.querySelector('.amc-flight-arr');
            const depSel = rowEl.querySelector('.amc-flight-dep');
            const otherVal = isArr ? jadwal.flight_dep : jadwal.flight_arr;
            const otherSel = isArr ? depSel : arrSel;
            if (otherSel && otherVal) {
                if (![...otherSel.options].some(o => o.value === otherVal)) {
                    const o = document.createElement('option');
                    o.value = otherVal;
                    o.text = otherVal;
                    otherSel.appendChild(o);
                }
                otherSel.value = otherVal;
                if (hasJq()) window.jQuery(otherSel).trigger('change.select2');
            }
            if (route && jadwal.route) route.value = jadwal.route;
            if (type && jadwal.aircraft_type) type.value = jadwal.aircraft_type;
            if (reg && jadwal.registrasi_pesawat) reg.value = jadwal.registrasi_pesawat;
        }

        function initSelect2OnRow(rowEl) {
            if (!hasJq()) return;
            const $ = window.jQuery;
            ['amc-flight-arr', 'amc-flight-dep'].forEach(cls => {
                const el = rowEl.querySelector('.' + cls);
                if (!el) return;
                const $el = $(el);
                if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
                $el.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    tags: true,
                    placeholder: cls === 'amc-flight-arr' ? 'Pilih ARR' : 'Pilih DEP',
                    allowClear: true,
                    createTag: function (params) {
                        const term = (params.term || '').trim();
                        if (!term) return null;
                        return { id: term, text: term, newTag: true };
                    },
                });
            });
        }

        function populateRowOptions(rowEl) {
            ['flight_arr', 'flight_dep'].forEach(field => {
                const cls = field === 'flight_arr' ? '.amc-flight-arr' : '.amc-flight-dep';
                const sel = rowEl.querySelector(cls);
                if (!sel) return;
                const current = sel.value;

                // Destroy Select2 before mutating native select.
                if (hasJq()) {
                    const $el = window.jQuery(sel);
                    if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
                }

                sel.innerHTML = buildOptionsHtml(field);
                // Preserve current value (incl. manual entries not in jadwal).
                if (current && ![...sel.options].some(o => o.value === current)) {
                    const o = document.createElement('option');
                    o.value = current;
                    o.text = current;
                    o.selected = true;
                    sel.appendChild(o);
                } else if (current) {
                    sel.value = current;
                }
            });
            // Re-init Select2 once after both selects in row are updated.
            initSelect2OnRow(rowEl);
        }

        function refreshAllRows() {
            jadwalByArr.clear();
            jadwalByDep.clear();
            jadwalRows.forEach(r => {
                if (r.flight_arr) jadwalByArr.set(String(r.flight_arr).toUpperCase(), r);
                if (r.flight_dep) jadwalByDep.set(String(r.flight_dep).toUpperCase(), r);
            });
            tbody.querySelectorAll('tr').forEach(populateRowOptions);
        }

        async function loadJadwal(mitraId) {
            if (!mitraId) { jadwalRows = []; refreshAllRows(); return; }
            try {
                const url = `${lookupUrl}?mitra_jasa_id=${encodeURIComponent(mitraId)}`;
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('lookup failed');
                const json = await res.json();
                jadwalRows = json.data || [];
            } catch (e) {
                jadwalRows = [];
            }
            refreshAllRows();
        }

        function handleFlightChange(selectEl, isArr) {
            const rowEl = selectEl.closest('tr');
            if (!rowEl) return;
            const val = (selectEl.value || '').trim().toUpperCase();
            if (!val) return;
            const lookup = isArr ? jadwalByArr : jadwalByDep;
            const jadwal = lookup.get(val);
            if (jadwal) fillRowFromJadwal(rowEl, jadwal, isArr);
        }

        // Init Select2 on existing rows.
        tbody.querySelectorAll('tr').forEach(initSelect2OnRow);

        mitraSelect?.addEventListener('change', e => loadJadwal(e.target.value));

        // Event delegation via jQuery (Select2 triggers jQuery change).
        if (hasJq()) {
            window.jQuery(tbody)
                .on('change', '.amc-flight-arr', function () { handleFlightChange(this, true); })
                .on('change', '.amc-flight-dep', function () { handleFlightChange(this, false); });
        }

        // When a new row is added via "Tambah Rincian", init Select2 + populate options.
        const origAdd = document.getElementById('btnAddRow');
        if (origAdd) {
            origAdd.addEventListener('click', () => {
                // Defer to allow DOM insertion by the first IIFE to complete.
                setTimeout(() => {
                    const lastRow = tbody.lastElementChild;
                    if (!lastRow) return;
                    populateRowOptions(lastRow);
                    initSelect2OnRow(lastRow);
                }, 0);
            });
        }

        if (mitraSelect?.value) loadJadwal(mitraSelect.value);
    })();
</script>
@endpush
@endsection
