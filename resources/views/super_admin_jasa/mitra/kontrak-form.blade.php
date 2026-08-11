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

        <div class="jasa-form-section">
            <div class="jasa-section-title">
                <span class="jasa-section-icon"><i class="bi bi-diagram-3"></i></span>
                <div>
                    <h6>Scope Layanan Kontrak</h6>
                    <p>Pilih layanan yang dicakup dokumen ini. Kosongkan jika kontrak berlaku untuk semua layanan aktif mitra.</p>
                </div>
            </div>

            @if(($layanans ?? collect())->isEmpty())
                <div class="alert alert-info mb-0">Mitra belum memiliki layanan aktif. Kontrak tetap dapat disimpan sebagai dokumen umum.</div>
            @else
                <div class="row g-2">
                    @foreach($layanans as $layanan)
                        <div class="col-md-6">
                            <label class="d-flex gap-2 rounded-4 border bg-light p-3 h-100">
                                <input type="checkbox" name="layanan_ids[]" value="{{ $layanan->id }}" class="form-check-input mt-1" @checked(in_array((int) $layanan->id, $selectedKontrakLayananIds, true))>
                                <span>
                                    <span class="d-block fw-bold text-primary">{{ $layanan->kode_layanan ?: str_pad($layanan->id, 6, '0', STR_PAD_LEFT) }}</span>
                                    <span class="d-block small fw-semibold text-dark">{{ $layanan->nama_layanan }}</span>
                                    <span class="d-block small text-muted">{{ $layananPath($layanan) }}</span>
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="jasa-action-footer">
            <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
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
@endsection
