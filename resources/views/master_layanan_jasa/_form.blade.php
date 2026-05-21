@php
    $isEdit = isset($layanan);
    $selectedNodeType = old('node_type', $isEdit && $layanan->is_leaf ? 'item' : 'category');
    $selectedTipe = old('tipe_layanan', $isEdit ? ($layanan->tipe_layanan ?? 'PNBP') : 'PNBP');
@endphp

@push('css')
    <style>
        .service-form-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(37, 99, 235, .16);
            border-radius: 22px;
            background: linear-gradient(120deg, #12355c 0%, #174f86 52%, #1d65a6 100%);
            box-shadow: 0 18px 48px rgba(18, 53, 92, .18);
        }

        .service-form-hero,
        .service-form-hero h4,
        .service-form-hero p {
            color: #fff !important;
        }

        .service-form-hero p {
            opacity: .82;
        }

        .service-form-hero::before {
            content: "";
            position: absolute;
            width: 360px;
            height: 360px;
            right: 8%;
            top: -190px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(125, 211, 252, .28), rgba(59, 130, 246, .18) 48%, transparent 70%);
            pointer-events: none;
        }

        .service-form-card {
            border: 1px solid rgba(37, 99, 235, .12);
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 18px 46px rgba(15, 23, 42, .08);
            overflow: hidden;
        }

        .service-form-section {
            border-bottom: 1px solid #e2e8f0;
            padding: 22px 24px;
        }

        .service-form-section:last-child {
            border-bottom: 0;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .section-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: #1d4ed8;
            color: #fff;
            box-shadow: 0 12px 24px rgba(37, 99, 235, .18);
        }

        .section-title h6 {
            margin: 0;
            color: #1e3a8a;
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
            border-radius: 13px !important;
            min-height: 42px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
        }

        .service-form-card .form-control:focus,
        .service-form-card .form-select:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .12) !important;
        }

        .choice-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .type-choice {
            cursor: pointer;
            border: 1px solid #dbe3ef;
            border-radius: 16px;
            background: #f8fafc;
            padding: 14px;
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
            border-radius: 11px;
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
            border-color: #2563eb;
            background: #eff6ff;
            box-shadow: 0 12px 28px rgba(37, 99, 235, .1);
        }

        .type-choice.is-selected .type-choice-icon {
            background: #1d4ed8;
            color: #fff;
        }

        .helper-panel {
            border: 1px solid #bfdbfe;
            border-radius: 16px;
            background: linear-gradient(135deg, #eff6ff 0%, #f8fbff 100%);
            color: #475569;
            padding: 14px;
            height: 100%;
        }

        .helper-panel strong {
            color: #1e3a8a;
        }

        .tariff-panel {
            border: 1px solid #bfdbfe;
            border-radius: 18px;
            background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
            padding: 16px;
        }

        .form-check-card {
            border: 1px solid #dbe3ef;
            border-radius: 16px;
            background: #f8fafc;
            padding: 12px 14px;
            height: 100%;
        }

        .live-preview {
            border: 1px solid #dbe3ef;
            border-radius: 16px;
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

        @media (max-width: 768px) {
            .choice-grid {
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
                <p class="mb-0 fw-semibold small">Susun struktur layanan sebagai folder, lalu isi item tarif yang dapat dipilih saat membuat tagihan.</p>
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

    <div class="service-form-card">
        <div class="service-form-section">
            <div class="section-title">
                <span class="section-icon"><i class="bi bi-card-text"></i></span>
                <div>
                    <h6>Identitas Layanan</h6>
                    <p>Nama dan posisi layanan di dalam struktur tree.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-5">
                    <label class="form-label">Nama Layanan <span class="text-danger">*</span></label>
                    <input type="text" name="nama_layanan" id="nama_layanan" class="form-control @error('nama_layanan') is-invalid @enderror" value="{{ old('nama_layanan', $isEdit ? $layanan->nama_layanan : '') }}" required placeholder="Contoh: a) bobot pesawat s.d. 40.000 kg">
                    <div class="form-text">Gunakan nama seperti yang akan muncul di struktur layanan.</div>
                    @error('nama_layanan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Letakkan di Bawah Kategori</label>
                    <select name="parent_id" id="parent_id" class="form-select select2-parent @error('parent_id') is-invalid @enderror" data-placeholder="Cari kategori induk">
                        <option value="">Tidak ada - jadikan kategori utama</option>
                        @foreach($parentOptions as $option)
                            <option value="{{ $option['id'] }}" data-path="{{ $option['path'] }}" {{ old('parent_id', $isEdit ? $layanan->parent_id : null) == $option['id'] ? 'selected' : '' }}>
                                {{ str_repeat('-- ', $option['depth']) }}{{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Cari jalur kategori, contoh: C. Jasa Pendaratan &gt; Dalam Negeri.</div>
                    @error('parent_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-lg-3">
                    <div class="helper-panel">
                        <strong>Tips struktur</strong>
                        <div class="small mt-1">Kategori hanya menjadi folder. Item tarif adalah baris akhir yang punya tarif dan bisa dipakai di tagihan.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="service-form-section">
            <div class="section-title">
                <span class="section-icon"><i class="bi bi-ui-checks-grid"></i></span>
                <div>
                    <h6>Jenis Data</h6>
                    <p>Pilih apakah data ini folder struktur atau item tarif final.</p>
                </div>
            </div>

            <div class="choice-grid">
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
                    <h6>Tarif dan Akun</h6>
                    <p>Bagian ini hanya berlaku untuk item tarif yang ditagihkan.</p>
                </div>
            </div>

            <div class="tariff-panel">
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
                        <input type="text" name="kode_mak" class="form-control @error('kode_mak') is-invalid @enderror" value="{{ old('kode_mak', $isEdit ? $layanan->kode_mak : '') }}" placeholder="Contoh: 424115">
                        @error('kode_mak')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">Kode Akun</label>
                        <input type="text" name="kode_akun" class="form-control @error('kode_akun') is-invalid @enderror" value="{{ old('kode_akun', $isEdit ? $layanan->kode_akun : '') }}" placeholder="Contoh: 425xxx">
                        @error('kode_akun')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

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
                    <div class="live-preview">
                        <div class="live-preview-label">Ringkasan pilihan</div>
                        <div class="live-preview-value" id="serviceFormPreview">-</div>
                        <div class="small text-muted mt-1" id="serviceFormPreviewHelp">-</div>
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
        const managedInputs = [
            document.querySelector('input[name="mendukung_konsesi"]'),
            document.querySelector('input[name="persentase_konsesi"]'),
            tarifInput,
            satuanInput,
            document.querySelector('input[name="wajib_tagihan_terpisah"]'),
            tipeInput,
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

        function updatePreview() {
            const name = nameInput?.value?.trim() || 'Nama layanan belum diisi';
            const isCategory = selectedNodeType() === 'category';

            if (isCategory) {
                preview.textContent = name + ' akan disimpan sebagai kategori/folder.';
                previewHelp.textContent = 'Kategori hanya mengatur struktur dan tidak muncul sebagai pilihan item tarif di tagihan.';
                return;
            }

            const tipe = tipeInput?.value || 'PNBP';
            const tarif = formatRupiah(tarifInput?.value || 0);
            const satuan = satuanInput?.value?.trim() || 'satuan belum diisi';
            preview.textContent = name + ' - ' + tipe + ' - ' + tarif + ' / ' + satuan;
            previewHelp.textContent = 'Item tarif ini akan bisa dipilih di form Buat Tagihan jika statusnya aktif dan ditugaskan ke mitra/admin.';
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

        [nameInput, tipeInput, tarifInput, satuanInput].forEach((input) => {
            input?.addEventListener('input', updatePreview);
            input?.addEventListener('change', updatePreview);
        });

        renderForm();
    });
</script>
@endpush
