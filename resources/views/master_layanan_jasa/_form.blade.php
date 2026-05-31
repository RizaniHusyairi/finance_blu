@php
    $isEdit = isset($layanan);
    $selectedNodeType = old('node_type', $isEdit && $layanan->is_leaf ? 'item' : 'category');
    $selectedTipe = old('tipe_layanan', $isEdit ? ($layanan->tipe_layanan ?? 'PNBP') : 'PNBP');
    $selectedKodeMak = old('kode_mak', $isEdit ? ($layanan->kode_mak ?? '') : '');
    $selectedKodeJenisPembayaran = old('kode_jenis_pembayaran', $isEdit ? ($layanan->kode_jenis_pembayaran ?? '') : '');
    $selectedKodePembayaran = $selectedKodeMak && $selectedKodeJenisPembayaran
        ? $selectedKodeMak . '.' . $selectedKodeJenisPembayaran
        : ($isEdit ? ($layanan->kode_pembayaran_lengkap ?? '') : '');
@endphp

@push('css')
    <style>
        .service-form-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(15, 76, 129, .18);
            border-radius: 8px;
            background: linear-gradient(120deg, #14375d 0%, #1d5d95 64%, #207560 100%);
            box-shadow: 0 14px 34px rgba(15, 23, 42, .12);
        }

        .service-form-hero,
        .service-form-hero h4,
        .service-form-hero p {
            color: #fff !important;
        }

        .service-form-hero p {
            opacity: .82;
        }

        .service-form-shell {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 360px;
            gap: 18px;
            align-items: start;
        }

        .service-form-card,
        .service-side-card {
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .07);
            overflow: hidden;
        }

        .service-form-section {
            border-bottom: 1px solid #e6edf5;
            padding: 20px 22px;
        }

        .service-form-section:last-child {
            border-bottom: 0;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        .section-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: #1e5b89;
            color: #fff;
            box-shadow: 0 10px 20px rgba(30, 91, 137, .16);
            flex: 0 0 36px;
        }

        .step-kicker {
            color: #0f766e;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 1px;
        }

        .section-title h6 {
            margin: 0;
            color: #14375d;
            font-weight: 900;
        }

        .section-title p {
            margin: 0;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .service-form-card .form-label {
            color: #334155;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .service-form-card .form-control,
        .service-form-card .form-select,
        .service-form-card .select2-selection {
            border-color: #dbe3ef !important;
            border-radius: 8px !important;
            min-height: 44px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
        }

        .service-form-card .form-control:focus,
        .service-form-card .form-select:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .12) !important;
        }

        .type-choice-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .type-choice {
            cursor: pointer;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            background: #f8fafc;
            padding: 15px;
            transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
        }

        .type-choice input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .type-choice-inner {
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .type-choice .type-choice-icon {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            border-radius: 8px;
            background: #e0ecff;
            color: #1d4ed8;
            font-size: 17px;
            line-height: 1;
        }

        .type-choice .type-choice-icon i {
            line-height: 1;
        }

        .type-choice strong {
            display: block;
            color: #0f172a;
            font-weight: 900;
        }

        .type-choice .type-choice-copy {
            display: block;
            color: #64748b;
            font-size: 12px;
            line-height: 1.35;
            margin-top: 2px;
        }

        .type-choice.is-selected {
            border-color: #1e5b89;
            background: #f0f7fb;
            box-shadow: 0 10px 22px rgba(30, 91, 137, .1);
        }

        .type-choice.is-selected .type-choice-icon {
            background: #1e5b89;
            color: #fff;
        }

        .field-note {
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            background: #f8fafc;
            padding: 12px 14px;
            color: #475569;
            font-size: 12px;
        }

        .field-note strong {
            color: #14375d;
        }

        .tariff-panel {
            border: 1px solid #cfe0ef;
            border-radius: 8px;
            background: #fbfdff;
            padding: 16px;
        }

        .tariff-group-title {
            color: #14375d;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            margin: 4px 0 10px;
        }

        .form-check-card {
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            background: #f8fafc;
            padding: 12px 14px;
            height: 100%;
        }

        .live-preview {
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            background: #f8fafc;
            padding: 14px;
        }

        .live-preview-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .live-preview-value {
            color: #0f172a;
            font-weight: 900;
            margin-top: 2px;
        }

        .service-side-card {
            position: sticky;
            top: 86px;
        }

        .side-section {
            padding: 18px;
            border-bottom: 1px solid #e6edf5;
        }

        .side-section:last-child {
            border-bottom: 0;
        }

        .side-title {
            color: #14375d;
            font-size: 13px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .side-list {
            margin: 0;
            padding-left: 18px;
            color: #475569;
            font-size: 12px;
            line-height: 1.55;
        }

        .decision-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #dbe3ef;
            border-radius: 999px;
            padding: 5px 9px;
            background: #fff;
            color: #334155;
            font-size: 11px;
            font-weight: 800;
        }

        .parent-path-preview {
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            background: #f8fafc;
            padding: 9px 11px;
            color: #475569;
            font-size: 12px;
            min-height: 38px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px;
            padding-left: 12px;
            color: #334155;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px;
        }

        .select2-dropdown {
            border-color: #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 16px 34px rgba(15, 23, 42, .14);
        }

        .select2-results__option {
            padding: 8px 10px;
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .service-form-shell {
                grid-template-columns: 1fr;
            }

            .service-side-card {
                position: static;
                order: -1;
            }

            .type-choice-grid {
                grid-template-columns: 1fr;
            }

            .service-form-section {
                padding: 18px 16px;
            }
        }
    </style>
@endpush

<div class="service-form-hero mb-4 px-4 py-4 text-white">
    <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center position-relative">
        <div class="d-flex gap-3 align-items-start">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-white text-primary shadow-sm" style="width:44px;height:44px;">
                <i class="bi bi-list-check fs-5"></i>
            </span>
            <div>
                <h4 class="mb-1 fw-black">{{ $isEdit ? 'Edit Master Layanan Jasa' : 'Tambah Master Layanan Jasa' }}</h4>
                <p class="mb-0 fw-semibold small">Mulai dari nama dan posisi, pilih jenis data, lalu isi tarif hanya jika layanan ini bisa ditagihkan.</p>
            </div>
        </div>
        <a href="{{ route('master-layanan-jasa.index') }}" class="btn btn-light text-primary fw-bold shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<form action="{{ $action }}" method="POST" id="masterLayananForm">
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <div class="service-form-shell">
    <div class="service-form-card">
        <div class="service-form-section">
            <div class="section-title">
                <span class="section-icon"><i class="bi bi-card-text"></i></span>
                <div>
                    <div class="step-kicker">Langkah 1</div>
                    <h6>Identitas Layanan</h6>
                    <p>Tentukan nama yang terlihat di daftar dan tempatnya di struktur layanan.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label">Nama Layanan <span class="text-danger">*</span></label>
                    <input type="text" name="nama_layanan" id="nama_layanan" class="form-control @error('nama_layanan') is-invalid @enderror" value="{{ old('nama_layanan', $isEdit ? $layanan->nama_layanan : '') }}" required placeholder="Contoh: a) bobot pesawat s.d. 40.000 kg">
                    <div class="form-text">Gunakan nama seperti yang akan muncul di struktur layanan.</div>
                    @error('nama_layanan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-lg-6">
                    <label class="form-label">Letakkan di Bawah Kategori</label>
                    <select name="parent_id" id="parent_id" class="form-select select2-parent @error('parent_id') is-invalid @enderror" data-placeholder="Cari kategori induk">
                        <option value="">Tidak ada - jadikan kategori utama</option>
                        @foreach($parentOptions as $option)
                            <option value="{{ $option['id'] }}" data-path="{{ $option['path'] }}" {{ old('parent_id', $isEdit ? $layanan->parent_id : null) == $option['id'] ? 'selected' : '' }}>
                                {{ str_repeat('  ', $option['depth']) }}{{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                    <div class="parent-path-preview mt-2" id="parentPathPreview">Kategori utama</div>
                    @error('parent_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="service-form-section">
            <div class="section-title">
                <span class="section-icon"><i class="bi bi-ui-checks-grid"></i></span>
                <div>
                    <div class="step-kicker">Langkah 2</div>
                    <h6>Jenis Data</h6>
                    <p>Pilih folder jika hanya untuk pengelompokan, atau item tarif jika dapat ditagihkan.</p>
                </div>
            </div>

            <div class="type-choice-grid">
                <label class="type-choice" data-type-choice="category">
                    <input type="radio" name="node_type" value="category" {{ $selectedNodeType === 'category' ? 'checked' : '' }}>
                    <span class="type-choice-inner">
                        <span class="type-choice-icon"><i class="bi bi-folder2"></i></span>
                        <span>
                            <strong>Kategori / Folder</strong>
                            <span class="type-choice-copy">Dipakai untuk mengelompokkan layanan. Tidak punya tarif dan tidak dipilih langsung di tagihan.</span>
                        </span>
                    </span>
                </label>

                <label class="type-choice" data-type-choice="item">
                    <input type="radio" name="node_type" value="item" {{ $selectedNodeType === 'item' ? 'checked' : '' }}>
                    <span class="type-choice-inner">
                        <span class="type-choice-icon"><i class="bi bi-receipt"></i></span>
                        <span>
                            <strong>Item Tarif</strong>
                            <span class="type-choice-copy">Baris akhir yang punya tarif, satuan, kode akun, dan bisa dipilih saat membuat tagihan.</span>
                        </span>
                    </span>
                </label>
            </div>
            @error('node_type')
                <div class="text-danger small fw-semibold mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="service-form-section tariff-field">
            <div class="section-title">
                <span class="section-icon"><i class="bi bi-cash-coin"></i></span>
                <div>
                    <div class="step-kicker">Langkah 3</div>
                    <h6>Tarif dan Akun</h6>
                    <p>Bagian ini hanya berlaku untuk item tarif yang ditagihkan.</p>
                </div>
            </div>

            <div class="tariff-panel">
                <div class="tariff-group-title">Kode pembayaran</div>
                <div class="row g-3">
                    <div class="col-lg-3">
                        <label class="form-label">Tipe Tagihan <span class="text-danger">*</span></label>
                        <select name="tipe_layanan" id="tipe_layanan" class="form-select @error('tipe_layanan') is-invalid @enderror" required>
                            <option value="PNBP" {{ $selectedTipe === 'PNBP' ? 'selected' : '' }}>PNBP</option>
                            <option value="KONSESI" {{ $selectedTipe === 'KONSESI' ? 'selected' : '' }}>Konsesi Saja</option>
                        </select>
                        @error('tipe_layanan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">Kode MAK</label>
                        <input type="text" id="kode_mak" name="kode_mak" class="form-control @error('kode_mak') is-invalid @enderror" value="{{ $selectedKodeMak }}" placeholder="Contoh: 424115">
                        @error('kode_mak')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">Kode Jenis Pembayaran</label>
                        <input type="text" id="kode_jenis_pembayaran" name="kode_jenis_pembayaran" class="form-control @error('kode_jenis_pembayaran') is-invalid @enderror" value="{{ $selectedKodeJenisPembayaran }}" placeholder="Contoh: 901" maxlength="3" inputmode="numeric">
                        @error('kode_jenis_pembayaran')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">Kode Pembayaran</label>
                        <input type="text" id="kode_pembayaran_preview" class="form-control bg-light fw-bold" value="{{ $selectedKodePembayaran }}" placeholder="Otomatis" readonly>
                        <div class="form-text">Gabungan Kode MAK + Kode Jenis.</div>
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">Kode Akun</label>
                        <input type="text" name="kode_akun" class="form-control @error('kode_akun') is-invalid @enderror" value="{{ old('kode_akun', $isEdit ? $layanan->kode_akun : '') }}" placeholder="Contoh: 425xxx">
                        @error('kode_akun')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">Kode Satker</label>
                        <input type="text" name="kode_satker" class="form-control @error('kode_satker') is-invalid @enderror" value="{{ old('kode_satker', $isEdit ? $layanan->kode_satker : '') }}" placeholder="Contoh: 288745" maxlength="20">
                        <div class="form-text">Kode satuan kerja untuk layanan ini.</div>
                        @error('kode_satker')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12"><hr class="my-1"></div>
                    <div class="col-12"><div class="tariff-group-title mb-0">Nilai tarif</div></div>

                    <div class="col-lg-3">
                        <label class="form-label">Tarif Dasar</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" id="tarif_dasar" name="tarif_dasar" class="form-control @error('tarif_dasar') is-invalid @enderror" value="{{ old('tarif_dasar', $isEdit ? (int) $layanan->tarif_dasar : 0) }}" min="0">
                        </div>
                        @error('tarif_dasar')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">Satuan Perhitungan</label>
                        <input type="text" id="satuan" name="satuan" class="form-control @error('satuan') is-invalid @enderror" value="{{ old('satuan', $isEdit ? $layanan->satuan : '') }}" placeholder="Contoh: tiap 1000 kg atau bagiannya">
                        <div class="form-text">Contoh lain: per jam per ton, per kg per hari, per m2 per bulan.</div>
                        @error('satuan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12"><hr class="my-1"></div>
                    <div class="col-12"><div class="tariff-group-title mb-0">Konsesi dan aturan khusus</div></div>

                    <div class="col-lg-4">
                        <div class="form-check-card">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="mendukung_konsesi" name="mendukung_konsesi" value="1" {{ old('mendukung_konsesi', $isEdit ? ($layanan->mendukung_konsesi ?? false) : false) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="mendukung_konsesi">Mendukung Konsesi</label>
                            </div>
                            <div class="form-text">Aktifkan kalau item ini juga dipakai untuk laporan penjualan mitra.</div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">Persentase Konsesi (%)</label>
                        <input type="number" step="0.0001" min="0" max="100" name="persentase_konsesi" class="form-control @error('persentase_konsesi') is-invalid @enderror" value="{{ old('persentase_konsesi', $isEdit ? $layanan->persentase_konsesi : '') }}" placeholder="Contoh: 5">
                        <div class="form-text">Dipakai otomatis untuk tagihan konsesi dari omzet.</div>
                        @error('persentase_konsesi')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <div class="form-check-card">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="wajib_tagihan_terpisah" name="wajib_tagihan_terpisah" value="1" {{ old('wajib_tagihan_terpisah', $isEdit ? $layanan->wajib_tagihan_terpisah : false) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="wajib_tagihan_terpisah">Wajib dibuat dalam tagihan terpisah</label>
                            </div>
                            <div class="form-text">Gunakan untuk layanan seperti PJP2U yang tidak boleh digabung dengan layanan lain.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="service-form-section">
            <div class="section-title">
                <span class="section-icon"><i class="bi bi-clock-history"></i></span>
                <div>
                    <div class="step-kicker">Langkah 4</div>
                    <h6>Aturan Tagihan</h6>
                    <p>Kontrol jatuh tempo, toleransi, dan status pemakaian layanan.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-3">
                    <label class="form-label">Jatuh Tempo (Hari) <span class="text-danger">*</span></label>
                    <input type="number" name="jumlah_hari_jatuh_tempo" class="form-control @error('jumlah_hari_jatuh_tempo') is-invalid @enderror" value="{{ old('jumlah_hari_jatuh_tempo', $isEdit ? ($layanan->jumlah_hari_jatuh_tempo ?? 30) : 30) }}" min="0" required>
                    @error('jumlah_hari_jatuh_tempo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-lg-3">
                    <label class="form-label">Toleransi (Hari)</label>
                    <input type="number" name="masa_toleransi_hari" class="form-control @error('masa_toleransi_hari') is-invalid @enderror" value="{{ old('masa_toleransi_hari', $isEdit ? ($layanan->masa_toleransi_hari ?? 0) : 0) }}" min="0" required>
                    @error('masa_toleransi_hari')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-lg-6">
                    <label class="form-label">Catatan Jatuh Tempo</label>
                    <input type="text" name="catatan_jatuh_tempo" class="form-control @error('catatan_jatuh_tempo') is-invalid @enderror" value="{{ old('catatan_jatuh_tempo', $isEdit ? $layanan->catatan_jatuh_tempo : '') }}" placeholder="Contoh: PJP2U jatuh tempo 7 hari dan tidak boleh digabung.">
                    @error('catatan_jatuh_tempo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-lg-5">
                    <div class="form-check-card">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', $isEdit ? $layanan->is_active : true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="is_active">Status Aktif</label>
                        </div>
                        <div class="form-text">Jika nonaktif, layanan tidak dapat dipilih saat membuat tagihan.</div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="field-note h-100">
                        <strong>Aturan ini ikut terbawa saat membuat tagihan.</strong>
                        <div class="mt-1">Jika beberapa layanan digabung dalam satu tagihan, sistem akan memakai jatuh tempo paling ketat dari layanan yang dipilih.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="service-form-section bg-light">
            <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                <a href="{{ route('master-layanan-jasa.index') }}" class="btn btn-light fw-bold text-secondary border px-4">Batal</a>
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="bi bi-save me-1"></i>{{ $submitLabel }}
                </button>
            </div>
        </div>
    </div>
    <aside class="service-side-card">
        <div class="side-section">
            <div class="side-title">Cara Membaca Form</div>
            <ol class="side-list">
                <li>Buat folder dulu untuk struktur besar.</li>
                <li>Buat item tarif di bawah folder paling spesifik.</li>
                <li>Isi tarif, satuan, dan kode hanya untuk item tarif.</li>
            </ol>
        </div>
        <div class="side-section">
            <div class="side-title">Folder vs Item Tarif</div>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="decision-chip"><i class="bi bi-folder2"></i> Folder: pengelompokan</span>
                <span class="decision-chip"><i class="bi bi-receipt"></i> Item: bisa ditagihkan</span>
            </div>
            <div class="field-note">
                <strong>Contoh struktur</strong>
                <div class="mt-1">Jasa Pendaratan Pesawat Udara &gt; Domestik &gt; Bobot pesawat s.d. 40.000 kg</div>
            </div>
        </div>
        <div class="side-section">
            <div class="side-title">Ringkasan Saat Ini</div>
            <div class="live-preview">
                <div class="live-preview-label">Pilihan</div>
                <div class="live-preview-value" id="serviceFormPreview">-</div>
                <div class="small text-muted mt-1" id="serviceFormPreviewHelp">-</div>
            </div>
        </div>
    </aside>
    </div>
</form>

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery && window.jQuery.fn.select2) {
            window.jQuery('.select2-parent').select2({
                width: '100%',
                allowClear: true,
                placeholder: 'Cari kategori induk',
                matcher: function (params, data) {
                    if (!params.term || !data.element) {
                        return data;
                    }

                    const term = params.term.toLowerCase();
                    const text = (data.text || '').toLowerCase();
                    const path = (data.element.dataset.path || '').toLowerCase();

                    return text.includes(term) || path.includes(term) ? data : null;
                }
            });
        }

        const typeChoices = document.querySelectorAll('[data-type-choice]');
        const tariffFields = document.querySelectorAll('.tariff-field');
        const preview = document.getElementById('serviceFormPreview');
        const previewHelp = document.getElementById('serviceFormPreviewHelp');
        const nameInput = document.getElementById('nama_layanan');
        const tipeInput = document.getElementById('tipe_layanan');
        const tarifInput = document.getElementById('tarif_dasar');
        const satuanInput = document.getElementById('satuan');
        const kodeMakInput = document.getElementById('kode_mak');
        const kodeJenisPembayaranInput = document.getElementById('kode_jenis_pembayaran');
        const kodePembayaranPreview = document.getElementById('kode_pembayaran_preview');
        const parentInput = document.getElementById('parent_id');
        const parentPathPreview = document.getElementById('parentPathPreview');
        const managedInputs = [
            document.querySelector('input[name="mendukung_konsesi"]'),
            document.querySelector('input[name="persentase_konsesi"]'),
            document.querySelector('input[name="kode_akun"]'),
            document.querySelector('input[name="kode_satker"]'),
            kodeMakInput,
            kodeJenisPembayaranInput,
            tarifInput,
            satuanInput,
            document.querySelector('input[name="wajib_tagihan_terpisah"]'),
        ].filter(Boolean);

        function selectedNodeType() {
            return document.querySelector('input[name="node_type"]:checked')?.value || 'category';
        }

        function updateTypeCards() {
            const selected = selectedNodeType();
            typeChoices.forEach((choice) => {
                choice.classList.toggle('is-selected', choice.dataset.typeChoice === selected);
            });
        }

        function updateVisibility() {
            const isCategory = selectedNodeType() === 'category';
            tariffFields.forEach((field) => field.classList.toggle('d-none', isCategory));
            managedInputs.forEach((input) => {
                input.disabled = isCategory;
                if (isCategory && ['checkbox', 'radio'].includes(input.type)) {
                    input.checked = false;
                }
            });
        }

        function formatRupiah(value) {
            const number = Number(value || 0);
            return 'Rp ' + number.toLocaleString('id-ID');
        }

        function updateKodePembayaranPreview() {
            const kodeMak = kodeMakInput?.value?.trim() || '';
            const kodeJenis = kodeJenisPembayaranInput?.value?.trim() || '';

            if (kodePembayaranPreview) {
                kodePembayaranPreview.value = kodeMak && kodeJenis ? kodeMak + '.' + kodeJenis : '';
            }
        }

        function updateParentPathPreview() {
            if (!parentInput || !parentPathPreview) {
                return;
            }

            const selectedOption = parentInput.options[parentInput.selectedIndex];
            const path = selectedOption?.dataset?.path || '';
            parentPathPreview.textContent = path ? 'Disimpan di: ' + path : 'Disimpan sebagai kategori utama';
        }

        function updatePreview() {
            updateKodePembayaranPreview();
            updateParentPathPreview();
            const name = nameInput?.value?.trim() || 'Nama layanan belum diisi';
            const isCategory = selectedNodeType() === 'category';
            const selectedOption = parentInput?.options[parentInput.selectedIndex];
            const parentPath = selectedOption?.dataset?.path || 'Kategori utama';

            if (isCategory) {
                preview.textContent = name + ' akan disimpan sebagai kategori/folder.';
                previewHelp.textContent = 'Lokasi: ' + parentPath + '. Kategori hanya mengatur struktur dan tidak muncul sebagai pilihan item tarif di tagihan.';
                return;
            }

            const tipe = tipeInput?.value || 'PNBP';
            const tarif = formatRupiah(tarifInput?.value || 0);
            const satuan = satuanInput?.value?.trim() || 'satuan belum diisi';
            preview.textContent = name + ' - ' + tipe + ' - ' + tarif + ' / ' + satuan;
            previewHelp.textContent = 'Lokasi: ' + parentPath + '. Item ini bisa dipilih di form Buat Tagihan jika aktif dan ditugaskan ke mitra/admin.';
        }

        function renderForm() {
            updateTypeCards();
            updateVisibility();
            updatePreview();
        }

        typeChoices.forEach((choice) => {
            choice.addEventListener('click', function () {
                const input = choice.querySelector('input[type="radio"]');
                if (input) {
                    input.checked = true;
                    renderForm();
                }
            });
        });

        [nameInput, tipeInput, tarifInput, satuanInput, kodeMakInput, kodeJenisPembayaranInput, parentInput].forEach((input) => {
            input?.addEventListener('input', updatePreview);
            input?.addEventListener('change', updatePreview);
        });

        if (window.jQuery) {
            window.jQuery(parentInput).on('select2:select select2:clear change', updatePreview);
        }

        renderForm();
    });
</script>
@endpush
