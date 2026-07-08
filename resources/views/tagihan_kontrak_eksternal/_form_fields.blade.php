{{--
    Field bersama form create/edit tagihan kontrak eksternal (desain kf-*).
    Variabel: $verifikatorOptions, $vendorOptions, opsional $tagihan & $detail (mode edit).
    PENTING: name/id input dipakai oleh JS auto-isi Surat Pesanan & test — jangan diubah.
--}}
@php
    $isEdit = isset($tagihan);
    $old = fn ($key, $default = null) => old($key, $default);
    $spSudahAda = $isEdit && ($detail->file_surat_pesanan ?? null);
@endphp

@push('css')
<style>
    :root {
        --kf-primary: #4f46e5;
        --kf-primary-2: #a855f7;
        --kf-ink: #0f172a;
    }

    /* ===== Animasi dasar ===== */
    @keyframes kfIn { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes kfShine { 0% { transform: translateX(-130%) skewX(-18deg); } 100% { transform: translateX(240%) skewX(-18deg); } }
    @keyframes kfFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
    @keyframes keAutofillPulse {
        0%   { box-shadow: 0 0 0 0 rgba(16, 185, 129, .45); background-color: #ecfdf5; }
        100% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); background-color: transparent; }
    }
    .ke-autofilled { animation: keAutofillPulse 1.5s ease-out 1; border-color: #10b981 !important; }
    .kf-reveal { opacity: 0; animation: kfIn .55s cubic-bezier(.22, 1, .36, 1) forwards; animation-delay: var(--d, 0s); }

    /* ===== Kartu seksi ===== */
    .kf-card {
        position: relative;
        border: 1px solid #eef0f4; border-radius: 1.25rem; background: #fff;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        overflow: hidden;
        transition: box-shadow .25s ease, border-color .25s ease;
    }
    .kf-card:focus-within { border-color: #c7d2fe; box-shadow: 0 10px 30px rgba(79, 70, 229, .10); }
    .kf-card::before {
        content: ''; position: absolute; inset: 0 0 auto 0; height: 3px;
        background: linear-gradient(90deg, var(--tone, var(--kf-primary)), var(--tone-2, var(--kf-primary-2)));
        opacity: .9;
    }
    .kf-card-head { display: flex; align-items: center; gap: .9rem; padding: 1.15rem 1.4rem .9rem; }
    .kf-step {
        width: 46px; height: 46px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 1rem; font-size: 1.25rem; color: #fff;
        background: linear-gradient(135deg, var(--tone, var(--kf-primary)), var(--tone-2, var(--kf-primary-2)));
        box-shadow: 0 8px 18px -8px var(--tone, var(--kf-primary));
        transition: transform .25s ease;
    }
    .kf-card:hover .kf-step { transform: scale(1.07) rotate(-4deg); }
    .kf-step-no { font-size: .62rem; font-weight: 800; letter-spacing: .12em; color: var(--tone, var(--kf-primary)); text-transform: uppercase; }
    .kf-card-title { font-weight: 800; color: var(--kf-ink); margin: 0; letter-spacing: -.2px; }
    .kf-card-sub { font-size: .76rem; color: #94a3b8; }
    .kf-card-body { padding: .35rem 1.4rem 1.4rem; }

    /* ===== Input ===== */
    .kf-card .form-label { font-weight: 700; font-size: .8rem; color: #334155; }
    .kf-card .form-control, .kf-card .form-select {
        border-radius: .75rem; border-color: #e2e8f0; padding: .6rem .85rem;
        transition: border-color .2s ease, box-shadow .2s ease, transform .15s ease;
    }
    .kf-card .form-control:focus, .kf-card .form-select:focus { border-color: var(--kf-primary); box-shadow: 0 0 0 .22rem rgba(79, 70, 229, .12); }
    .kf-card .input-group-text { border-radius: .75rem 0 0 .75rem; border-color: #e2e8f0; background: #f8fafc; font-weight: 700; color: #64748b; }
    .kf-card .input-group .form-control { border-radius: 0 .75rem .75rem 0; }

    /* ===== Metode pembayaran (kartu radio) ===== */
    .kf-metode { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
    .kf-metode label {
        display: flex; align-items: flex-start; gap: .6rem;
        border: 2px solid #e2e8f0; border-radius: 1rem; padding: .85rem 1rem;
        cursor: pointer; transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
    }
    .kf-metode input { margin-top: .15rem; }
    .kf-metode label:hover { border-color: #c7d2fe; }
    .kf-metode input:checked + .kf-metode-txt { color: var(--kf-primary); }
    .kf-metode label:has(input:checked) { border-color: var(--kf-primary); background: #f5f6ff; box-shadow: 0 8px 18px -12px var(--kf-primary); }
    .kf-metode-txt .t { font-weight: 800; font-size: .86rem; color: var(--kf-ink); }
    .kf-metode-txt .s { font-size: .7rem; color: #94a3b8; }

    /* ===== Switch card ===== */
    .kf-switch { border: 1px dashed #dbe3f0; border-radius: 1rem; padding: .8rem 1rem; background: #fbfcff; }

    /* ===== Tabel termin ===== */
    .kf-termin-table { width: 100%; }
    .kf-termin-table th { font-size: .66rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; padding: .4rem .5rem; }
    .kf-termin-table td { padding: .35rem .5rem; vertical-align: middle; }
    .kf-termin-table .idx { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 11px; font-weight: 800; color: #fff; background: linear-gradient(135deg, #4f46e5, #818cf8); }
    .kf-preview-row { border-radius: .85rem; background: linear-gradient(135deg, #eef2ff, #faf5ff); border: 1px dashed #c7d2fe; padding: .7rem .9rem; }
    .kf-btn-addrow {
        border: 1px solid #c7d2fe; border-radius: .7rem; background: #eef2ff; color: var(--kf-primary);
        font-weight: 800; font-size: .78rem; padding: .45rem .9rem; transition: transform .15s ease, background .2s ease;
    }
    .kf-btn-addrow:hover { transform: translateY(-1px); background: #e0e7ff; }
    .kf-btn-delrow { border: 1px solid #fecdd3; border-radius: .6rem; background: #fff5f6; color: #e11d48; width: 34px; height: 34px; }
    .kf-btn-delrow:disabled { opacity: .35; cursor: not-allowed; }
    .kf-num { text-align: right; font-variant-numeric: tabular-nums; }
    .kf-warn { color: #b45309; font-weight: 700; font-size: .74rem; }

    /* ===== Ringkasan termin read-only (edit) ===== */
    .kf-ro { display: flex; flex-wrap: wrap; gap: .6rem; }
    .kf-ro-item { flex: 1 1 140px; border: 1px solid #eef0f4; border-radius: .9rem; padding: .6rem .8rem; background: #f8fafc; }
    .kf-ro-item .k { font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; }
    .kf-ro-item .v { font-weight: 800; color: var(--kf-ink); font-variant-numeric: tabular-nums; }

    /* ===== Dropzone file ===== */
    .kf-drop {
        position: relative; border: 2px dashed #dbe3f0; border-radius: 1rem; background: #fbfcff;
        padding: 1.05rem 1rem; display: flex; align-items: center; gap: .85rem;
        transition: border-color .2s ease, background .2s ease, transform .2s ease; min-height: 86px;
    }
    .kf-drop:hover { border-color: var(--kf-primary); background: #f5f6ff; }
    .kf-drop.dragover { border-color: var(--kf-primary); background: #eef2ff; transform: scale(1.01); }
    .kf-drop.picked { border-style: solid; border-color: #10b981; background: #f0fdf9; }
    .kf-drop input[type="file"] { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
    .kf-drop .ic {
        width: 44px; height: 44px; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center;
        border-radius: .85rem; font-size: 1.25rem; color: var(--kf-primary); background: #eef2ff; transition: transform .25s ease;
    }
    .kf-drop.picked .ic { color: #059669; background: #d1fae5; }
    .kf-drop:hover .ic { transform: translateY(-2px); }
    .kf-drop .t { font-weight: 700; font-size: .84rem; color: var(--kf-ink); margin-bottom: .1rem; }
    .kf-drop .s { font-size: .72rem; color: #94a3b8; overflow-wrap: anywhere; }
    .kf-drop.picked .s { color: #059669; font-weight: 600; }
    .kf-badge-ai {
        display: inline-flex; align-items: center; gap: .3rem; padding: .18rem .55rem; border-radius: 999px;
        font-size: .64rem; font-weight: 800; letter-spacing: .04em; color: #7c3aed; background: #f3e8ff; border: 1px solid #e9d5ff;
        animation: kfFloat 3.5s ease-in-out infinite;
    }

    /* ===== Chip role penanda tangan ===== */
    .kf-role { display: inline-flex; align-items: center; gap: .45rem; font-weight: 700; font-size: .8rem; color: #334155; }
    .kf-role .dot { width: 9px; height: 9px; border-radius: 50%; background: var(--rt, var(--kf-primary)); box-shadow: 0 0 0 3px color-mix(in srgb, var(--rt, var(--kf-primary)) 18%, #fff); }

    /* ===== Blok vendor baru (slide) ===== */
    #keVendorBaru { overflow: hidden; transition: max-height .45s cubic-bezier(.22, 1, .36, 1), opacity .35s ease; max-height: 700px; opacity: 1; }
    #keVendorBaru.kf-hidden { max-height: 0; opacity: 0; }
    .kf-hide { display: none !important; }
    .kf-vendor-hint {
        display: flex; align-items: center; gap: .5rem; padding: .55rem .85rem; border-radius: .75rem;
        background: #eef2ff; border: 1px dashed #c7d2fe; font-size: .75rem; color: #4338ca; font-weight: 600;
    }

    /* ===== Bar submit lengket + progres ===== */
    .kf-submitbar {
        position: sticky; bottom: 1rem; z-index: 50; border-radius: 1.1rem;
        background: rgba(255, 255, 255, .92); backdrop-filter: blur(8px); border: 1px solid #e2e8f0;
        box-shadow: 0 14px 40px -12px rgba(15, 23, 42, .25); padding: .9rem 1.2rem;
        display: flex; flex-wrap: wrap; align-items: center; gap: 1rem;
    }
    .kf-progress { flex: 1 1 220px; min-width: 200px; }
    .kf-progress .lbl { display: flex; justify-content: space-between; font-size: .72rem; font-weight: 700; color: #64748b; margin-bottom: .3rem; }
    .kf-progress .track { height: 8px; border-radius: 999px; background: #eef0f4; overflow: hidden; }
    .kf-progress .fill {
        height: 100%; width: 0; border-radius: 999px;
        background: linear-gradient(90deg, var(--kf-primary), var(--kf-primary-2));
        transition: width .5s cubic-bezier(.22, 1, .36, 1), background .3s ease;
    }
    .kf-progress.done .fill { background: linear-gradient(90deg, #059669, #34d399); }
    .kf-btn-submit {
        position: relative; overflow: hidden; border: 0; border-radius: .9rem; padding: .8rem 1.7rem;
        font-weight: 800; color: #fff; background: linear-gradient(120deg, var(--kf-primary), var(--kf-primary-2));
        box-shadow: 0 10px 24px -10px var(--kf-primary); transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
    }
    .kf-btn-submit:hover { transform: translateY(-2px); box-shadow: 0 16px 30px -10px var(--kf-primary); color: #fff; }
    .kf-btn-submit::after {
        content: ''; position: absolute; inset: 0; width: 45%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .35), transparent); animation: kfShine 3s ease-in-out infinite;
    }

    @media (max-width: 575.98px) { .kf-metode { grid-template-columns: 1fr; } }
    @media (prefers-reduced-motion: reduce) {
        .kf-reveal { animation: none; opacity: 1; }
        .ke-autofilled, .kf-btn-submit::after, .kf-badge-ai { animation: none; }
        .kf-card, .kf-step, .kf-drop, .kf-drop .ic, .kf-btn-submit, .kf-progress .fill, #keVendorBaru { transition: none; }
    }
</style>
@endpush

{{-- ══════════ 01 · Data Surat Pesanan ══════════ --}}
<div class="kf-card mb-4 kf-reveal" style="--d:.03s; --tone:#4f46e5; --tone-2:#818cf8;">
    <div class="kf-card-head">
        <span class="kf-step"><i class="bi bi-file-earmark-text"></i></span>
        <div>
            <div class="kf-step-no">Langkah 01</div>
            <h6 class="kf-card-title">Data Surat Pesanan / Kontrak Eksternal</h6>
            <div class="kf-card-sub">Unggah PDF Surat Pesanan di Langkah 05 — kolom di sini akan terisi otomatis.</div>
        </div>
    </div>
    <div class="kf-card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nomor Surat Pesanan <span class="text-danger">*</span></label>
                <input type="text" name="nomor_surat_pesanan" class="form-control @error('nomor_surat_pesanan') is-invalid @enderror"
                       value="{{ $old('nomor_surat_pesanan', $detail->nomor_surat_pesanan ?? '') }}" placeholder="EP-01KNNRTK5Z59..." required>
                @error('nomor_surat_pesanan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Surat Pesanan <span class="text-danger">*</span></label>
                <input type="date" name="tanggal_surat_pesanan" class="form-control @error('tanggal_surat_pesanan') is-invalid @enderror"
                       value="{{ $old('tanggal_surat_pesanan', isset($detail) ? optional($detail->tanggal_surat_pesanan)->format('Y-m-d') : '') }}" required>
                @error('tanggal_surat_pesanan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Sumber</label>
                <input type="text" name="sumber" class="form-control" value="{{ $old('sumber', $detail->sumber ?? 'INAPROC') }}" placeholder="INAPROC / e-Katalog">
            </div>
            <div class="col-md-12">
                <label class="form-label">Nama Pekerjaan / Paket <span class="text-danger">*</span>
                    <span class="kf-badge-ai ms-1"><i class="bi bi-stars"></i> Diisi AI dari PDF</span>
                </label>
                <input type="text" name="nama_pekerjaan" class="form-control @error('nama_pekerjaan') is-invalid @enderror"
                       value="{{ $old('nama_pekerjaan', $detail->nama_pekerjaan ?? '') }}" placeholder="Pengadaan CCTV dan Perangkat Jaringan" required>
                @error('nama_pekerjaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-12">
                <label class="form-label">Deskripsi (opsional)</label>
                <textarea name="deskripsi" rows="2" class="form-control" placeholder="Uraian singkat pembayaran">{{ $old('deskripsi', $tagihan->deskripsi ?? '') }}</textarea>
            </div>
        </div>
    </div>
</div>

{{-- ══════════ 02 · Metode Pembayaran & Termin ══════════ --}}
<div class="kf-card mb-4 kf-reveal" style="--d:.09s; --tone:#7c3aed; --tone-2:#a855f7;">
    <div class="kf-card-head">
        <span class="kf-step"><i class="bi bi-cash-stack"></i></span>
        <div>
            <div class="kf-step-no" style="color:#7c3aed;">Langkah 02</div>
            <h6 class="kf-card-title">Metode Pembayaran &amp; Termin</h6>
            <div class="kf-card-sub">
                @if($isEdit) Skema termin dikunci saat pembuatan — hanya data deskriptif yang dapat diubah.
                @else Lumpsum = 1 pembayaran. Termin = sistem membuat beberapa tagihan sekaligus (satu per termin). @endif
            </div>
        </div>
    </div>
    <div class="kf-card-body">
        @if($isEdit)
            {{-- Read-only: ringkasan termin milik tagihan ini --}}
            @php
                $labelMetode = ['LUMPSUM' => 'Lumpsum', 'TERMIN' => 'Termin'][$detail->metode_pembayaran ?? 'LUMPSUM'] ?? $detail->metode_pembayaran;
            @endphp
            <div class="kf-ro">
                <div class="kf-ro-item"><div class="k">Metode</div><div class="v">{{ $labelMetode }}</div></div>
                <div class="kf-ro-item"><div class="k">Nilai Total Kontrak</div><div class="v">Rp {{ number_format((float) ($detail->nilai_total_kontrak ?? $tagihan->total_bruto), 0, ',', '.') }}</div></div>
                <div class="kf-ro-item"><div class="k">Termin</div><div class="v">{{ $detail->termin_ke ?? 1 }} / {{ $detail->total_termin ?? 1 }} · {{ $detail->jenis_termin ?? 'PELUNASAN' }}</div></div>
                <div class="kf-ro-item"><div class="k">Persentase Termin</div><div class="v">{{ rtrim(rtrim(number_format((float) ($detail->persentase ?? 100), 4, '.', ''), '0'), '.') }}%</div></div>
                <div class="kf-ro-item"><div class="k">Nilai Bruto Termin</div><div class="v">Rp {{ number_format((float) $tagihan->total_bruto, 0, ',', '.') }}</div></div>
                @if((float) ($detail->potongan_angsuran_uang_muka ?? 0) > 0)
                    <div class="kf-ro-item"><div class="k">Angsuran Uang Muka</div><div class="v" style="color:#b45309;">− Rp {{ number_format((float) $detail->potongan_angsuran_uang_muka, 0, ',', '.') }}</div></div>
                @endif
            </div>
        @else
            {{-- CREATE: builder termin --}}
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Nilai Total Kontrak (termasuk PPN) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" name="nilai_total_kontrak" id="kfNilaiTotal" class="form-control fw-bold kf-rupiah @error('nilai_total_kontrak') is-invalid @enderror"
                               value="{{ $old('nilai_total_kontrak') }}" inputmode="numeric" placeholder="435,675,000" required>
                        @error('nilai_total_kontrak')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-7">
                    <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                    <div class="kf-metode">
                        <label>
                            <input type="radio" name="metode_pembayaran" value="LUMPSUM" id="kfMetodeLumpsum" {{ $old('metode_pembayaran', 'LUMPSUM') === 'LUMPSUM' ? 'checked' : '' }}>
                            <span class="kf-metode-txt"><span class="t">Lumpsum</span><span class="s d-block">Dibayar sekaligus (1 tagihan).</span></span>
                        </label>
                        <label>
                            <input type="radio" name="metode_pembayaran" value="TERMIN" id="kfMetodeTermin" {{ $old('metode_pembayaran') === 'TERMIN' ? 'checked' : '' }}>
                            <span class="kf-metode-txt"><span class="t">Termin</span><span class="s d-block">Bertahap (beberapa tagihan).</span></span>
                        </label>
                    </div>
                </div>

                {{-- Uang muka --}}
                <div class="col-12">
                    <div class="kf-switch">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="kfAdaUm" name="ada_uang_muka" value="1" {{ $old('ada_uang_muka') ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="kfAdaUm">Kontrak menerapkan Uang Muka (DP)?</label>
                        </div>
                        <div id="kfUmWrap" class="mt-3 {{ $old('ada_uang_muka') ? '' : 'kf-hide' }}">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label">Nilai Uang Muka (Rp)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" name="nilai_uang_muka" id="kfNilaiUm" class="form-control fw-bold kf-rupiah @error('nilai_uang_muka') is-invalid @enderror"
                                               value="{{ $old('nilai_uang_muka') }}" inputmode="numeric" placeholder="0">
                                        @error('nilai_uang_muka')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="form-text"><i class="bi bi-info-circle"></i> Uang muka dianggap sudah dibayar di luar sistem; nilainya dipotong proporsional (Angsuran Uang Muka) dari tiap termin progress/pelunasan.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Skema termin (hanya saat TERMIN) --}}
                <div class="col-12 {{ $old('metode_pembayaran') === 'TERMIN' ? '' : 'kf-hide' }}" id="kfTerminWrap">
                    <div class="border rounded-4 p-3" style="border-color:#eef0f4 !important;">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <div>
                                <div class="fw-bold"><i class="bi bi-list-columns-reverse text-primary"></i> Rincian Termin Progress</div>
                                <div class="kf-card-sub">Baris Pelunasan (= 100% − Σprogress − retensi) ditambahkan otomatis.</div>
                            </div>
                            <button type="button" class="kf-btn-addrow" id="kfAddRow"><i class="bi bi-plus-lg"></i> Tambah Progress</button>
                        </div>
                        <div class="table-responsive">
                            <table class="kf-termin-table">
                                <thead>
                                    <tr>
                                        <th style="width:46px;">#</th>
                                        <th>Keterangan Progress</th>
                                        <th style="width:130px;">Persentase</th>
                                        <th style="width:150px;" class="kf-num">Nilai Bruto</th>
                                        <th style="width:150px;" class="kf-num">Angsuran UM</th>
                                        <th style="width:44px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="kfTerminBody">
                                    @php $oldPct = $old('progress_persentase', ['']); $oldKet = $old('progress_keterangan', ['']); @endphp
                                    @foreach($oldPct as $i => $pct)
                                        <tr class="kf-termin-row">
                                            <td><span class="idx">{{ $i + 1 }}</span></td>
                                            <td><input type="text" name="progress_keterangan[]" class="form-control" placeholder="Contoh: Progress Tahap {{ $i + 1 }}" value="{{ $oldKet[$i] ?? '' }}"></td>
                                            <td>
                                                <div class="input-group">
                                                    <input type="number" name="progress_persentase[]" class="form-control text-center kf-pct" min="0" max="100" step="0.0001" placeholder="0" value="{{ $pct }}">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </td>
                                            <td class="kf-num fw-bold text-success kf-cell-val">Rp 0</td>
                                            <td class="kf-num fw-bold kf-cell-um" style="color:#b45309;">Rp 0</td>
                                            <td class="text-center"><button type="button" class="kf-btn-delrow kf-del"><i class="bi bi-trash3"></i></button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Preview baris otomatis --}}
                        <div class="kf-preview-row mt-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <div class="fw-bold"><i class="bi bi-flag-fill text-primary"></i> Pelunasan (otomatis) — <span id="kfPelunasanPct">100</span>%</div>
                                <div class="kf-card-sub">Sisa persentase setelah progress &amp; retensi.</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-success">Rp <span id="kfPelunasanVal">0</span></div>
                                <div class="kf-card-sub">Angsuran UM: <span id="kfPelunasanUm" style="color:#b45309;">Rp 0</span></div>
                            </div>
                        </div>
                        <div id="kfTerminWarn" class="kf-warn mt-2 kf-hide"><i class="bi bi-exclamation-triangle-fill"></i> Total progress + retensi tidak boleh melebihi 100%.</div>

                        {{-- Retensi --}}
                        <div class="kf-switch mt-3">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="kfGunakanRetensi" name="gunakan_retensi" value="1" {{ $old('gunakan_retensi') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="kfGunakanRetensi">Gunakan retensi? <span class="text-muted fw-normal">(ditahan sampai masa pemeliharaan)</span></label>
                            </div>
                            <div id="kfRetensiWrap" class="row g-2 mt-1 {{ $old('gunakan_retensi') ? '' : 'kf-hide' }}">
                                <div class="col-md-5">
                                    <label class="form-label">Keterangan Retensi</label>
                                    <input type="text" name="retensi_keterangan" class="form-control" value="{{ $old('retensi_keterangan', 'Retensi Masa Pemeliharaan') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Retensi (%)</label>
                                    <div class="input-group">
                                        <input type="number" name="retensi_persentase" id="kfRetensiPct" class="form-control text-center" min="0" max="100" step="0.0001" placeholder="5" value="{{ $old('retensi_persentase') }}">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nilai Retensi</label>
                                    <input type="text" class="form-control bg-light fw-bold" id="kfRetensiVal" value="Rp 0" readonly style="color:#b91c1c;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- ══════════ 03 · Penyedia / Vendor ══════════ --}}
<div class="kf-card mb-4 kf-reveal" style="--d:.15s; --tone:#059669; --tone-2:#34d399;">
    <div class="kf-card-head">
        <span class="kf-step"><i class="bi bi-shop"></i></span>
        <div>
            <div class="kf-step-no" style="color:#059669;">Langkah 03</div>
            <h6 class="kf-card-title">Penyedia / Vendor</h6>
            <div class="kf-card-sub">Bila NPWP di PDF cocok dengan vendor terdaftar, dropdown terpilih otomatis.</div>
        </div>
    </div>
    <div class="kf-card-body">
        <div class="row g-3">
            <div class="col-md-7">
                <label class="form-label">Pilih Vendor Terdaftar</label>
                <select name="pihak_id" id="keVendorSelect" class="form-select">
                    <option value="">— Vendor baru (isi data di bawah) —</option>
                    @foreach($vendorOptions as $v)
                        <option value="{{ $v['id'] }}" @selected((string) $old('pihak_id', $tagihan->pihak_id ?? '') === (string) $v['id'])>
                            {{ $v['nama'] }}{{ $v['npwp'] ? ' — NPWP ' . $v['npwp'] : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5 d-flex align-items-end">
                <div class="kf-vendor-hint w-100" id="kfVendorHint">
                    <i class="bi bi-lightbulb"></i> <span>Kosongkan bila penyedia belum terdaftar — lengkapi data vendor baru di bawah.</span>
                </div>
            </div>
        </div>
        <div id="keVendorBaru" class="mt-1">
            <div class="row g-3 pt-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Penyedia</label>
                    <input type="text" name="vendor_nama" class="form-control @error('vendor_nama') is-invalid @enderror" value="{{ $old('vendor_nama') }}" placeholder="BERKAT DAMAI SEJAHTERA INDONESIA">
                    @error('vendor_nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">NPWP</label>
                    <input type="text" name="vendor_npwp" class="form-control" value="{{ $old('vendor_npwp') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Penanggung Jawab</label>
                    <input type="text" name="vendor_penanggung_jawab" class="form-control" value="{{ $old('vendor_penanggung_jawab') }}">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Alamat</label>
                    <input type="text" name="vendor_alamat" class="form-control" value="{{ $old('vendor_alamat') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Bank</label>
                    <input type="text" name="vendor_nama_bank" class="form-control @error('vendor_nama_bank') is-invalid @enderror" value="{{ $old('vendor_nama_bank') }}" placeholder="Bank Mandiri">
                    @error('vendor_nama_bank')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nomor Rekening</label>
                    <input type="text" name="vendor_nomor_rekening" class="form-control @error('vendor_nomor_rekening') is-invalid @enderror" value="{{ $old('vendor_nomor_rekening') }}">
                    @error('vendor_nomor_rekening')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nama Pemilik Rekening</label>
                    <input type="text" name="vendor_nama_rekening" class="form-control @error('vendor_nama_rekening') is-invalid @enderror" value="{{ $old('vendor_nama_rekening') }}">
                    @error('vendor_nama_rekening')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════ 04 · Penanda Tangan ══════════ --}}
<div class="kf-card mb-4 kf-reveal" style="--d:.21s; --tone:#d97706; --tone-2:#fbbf24;">
    <div class="kf-card-head">
        <span class="kf-step"><i class="bi bi-pen"></i></span>
        <div>
            <div class="kf-step-no" style="color:#d97706;">Langkah 04</div>
            <h6 class="kf-card-title">Pejabat Penanda Tangan / Verifikator</h6>
            <div class="kf-card-sub">Menandatangani &amp; memverifikasi dokumen pencairan SPP · SPM · NPI · SP2D.</div>
        </div>
    </div>
    <div class="kf-card-body">
        <div class="row g-3">
            @php
                $verifFields = [
                    'ppk' => ['label' => 'PPK', 'field' => 'ppk_user_id', 'tone' => '#4f46e5'],
                    'ppspm' => ['label' => 'PPSPM', 'field' => 'ppspm_user_id', 'tone' => '#0891b2'],
                    'koordinator_keuangan' => ['label' => 'Koordinator Keuangan', 'field' => 'koordinator_keuangan_user_id', 'tone' => '#7c3aed'],
                    'bendahara_pengeluaran' => ['label' => 'Bendahara Pengeluaran', 'field' => 'bendahara_pengeluaran_user_id', 'tone' => '#059669'],
                    'bendahara_penerimaan' => ['label' => 'Bendahara Penerimaan', 'field' => 'bendahara_penerimaan_user_id', 'tone' => '#db2777'],
                    'kasubbag' => ['label' => 'Kepala Subbagian Keuangan dan TU', 'field' => 'kasubbag_user_id', 'tone' => '#d97706'],
                ];
            @endphp
            @foreach($verifFields as $key => $cfg)
                <div class="col-md-4">
                    <label class="form-label kf-role" style="--rt: {{ $cfg['tone'] }};"><span class="dot"></span> {{ $cfg['label'] }} <span class="text-danger">*</span></label>
                    <select name="{{ $cfg['field'] }}" class="form-select @error($cfg['field']) is-invalid @enderror" required>
                        <option value="">— Pilih —</option>
                        @foreach(($verifikatorOptions[$key] ?? []) as $u)
                            <option value="{{ $u['id'] }}" @selected((string) $old($cfg['field'], $tagihan->{$cfg['field']} ?? '') === (string) $u['id'])>
                                {{ $u['name'] }} ({{ $u['nip'] }})
                            </option>
                        @endforeach
                    </select>
                    @error($cfg['field'])<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ══════════ 05 · Dokumen ══════════ --}}
<div class="kf-card mb-4 kf-reveal" style="--d:.27s; --tone:#0891b2; --tone-2:#38bdf8;">
    <div class="kf-card-head">
        <span class="kf-step"><i class="bi bi-cloud-arrow-up"></i></span>
        <div>
            <div class="kf-step-no" style="color:#0891b2;">Langkah 05</div>
            <h6 class="kf-card-title">Dokumen</h6>
            <div class="kf-card-sub">Klik atau tarik file PDF ke dalam kotak. Surat Pesanan otomatis dibaca sistem.</div>
        </div>
    </div>
    <div class="kf-card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="kf-drop @if($spSudahAda) picked @endif" data-kf-drop>
                    <input type="file" name="file_surat_pesanan" id="keFileSuratPesanan" accept="application/pdf" @if(! $isEdit) required @endif>
                    <span class="ic"><i class="bi bi-file-earmark-pdf"></i></span>
                    <div class="flex-grow-1">
                        <div class="t">Surat Pesanan / Kontrak ber-TTE @if(! $isEdit)<span class="text-danger">*</span>@endif
                            <span class="kf-badge-ai ms-1"><i class="bi bi-magic"></i> Auto-isi</span>
                        </div>
                        <div class="s" data-kf-filename>
                            @if($spSudahAda)
                                <i class="bi bi-check-circle"></i> Sudah terunggah — pilih file baru hanya bila ingin mengganti.
                            @else
                                PDF ditandatangani kedua pihak (TTE BSrE/Privy atau basah).
                            @endif
                        </div>
                    </div>
                </div>
                @error('file_surat_pesanan')<div class="text-danger fs-8 fw-semibold mt-1">{{ $message }}</div>@enderror
                <div id="keParseStatus" class="form-text d-none mt-2"></div>
            </div>
            <div class="col-md-6">
                <div class="kf-drop" data-kf-drop>
                    <input type="file" name="file_invoice" accept="application/pdf">
                    <span class="ic"><i class="bi bi-receipt"></i></span>
                    <div class="flex-grow-1">
                        <div class="t">Invoice <span class="text-secondary fw-normal">(opsional)</span></div>
                        <div class="s" data-kf-filename>PDF invoice dari penyedia.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="kf-drop" data-kf-drop>
                    <input type="file" name="file_kwitansi" accept="application/pdf">
                    <span class="ic"><i class="bi bi-cash-coin"></i></span>
                    <div class="flex-grow-1">
                        <div class="t">Kwitansi <span class="text-secondary fw-normal">(opsional)</span></div>
                        <div class="s" data-kf-filename>PDF kwitansi pembayaran.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="kf-drop" data-kf-drop>
                    <input type="file" name="file_bast" accept="application/pdf">
                    <span class="ic"><i class="bi bi-box-seam"></i></span>
                    <div class="flex-grow-1">
                        <div class="t">BAST / Serah Terima <span class="text-secondary fw-normal">(opsional)</span></div>
                        <div class="s" data-kf-filename>PDF bukti serah terima barang.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
    // ===== Toggle blok vendor baru (slide halus) =====
    (function () {
        const sel = document.getElementById('keVendorSelect');
        const blok = document.getElementById('keVendorBaru');
        const hint = document.getElementById('kfVendorHint');
        if (!sel || !blok) return;
        const sync = () => {
            blok.classList.toggle('kf-hidden', !!sel.value);
            if (hint) {
                hint.innerHTML = sel.value
                    ? '<i class="bi bi-check-circle"></i> <span>Memakai vendor terdaftar — rekening default vendor dipakai untuk pembayaran.</span>'
                    : '<i class="bi bi-lightbulb"></i> <span>Kosongkan bila penyedia belum terdaftar — lengkapi data vendor baru di bawah.</span>';
            }
        };
        sel.addEventListener('change', sync);
        sync();
    })();

    // ===== Dropzone: nama file + status terpilih + drag & drop =====
    (function () {
        document.querySelectorAll('[data-kf-drop]').forEach(function (zone) {
            const input = zone.querySelector('input[type="file"]');
            const nameEl = zone.querySelector('[data-kf-filename]');
            if (!input) return;
            const defaultText = nameEl ? nameEl.innerHTML : '';
            const sync = () => {
                const file = input.files && input.files[0];
                if (file) {
                    zone.classList.add('picked');
                    if (nameEl) nameEl.innerHTML = '<i class="bi bi-check-circle"></i> ' + file.name;
                } else if (! zone.dataset.kfKeep) {
                    zone.classList.remove('picked');
                    if (nameEl) nameEl.innerHTML = defaultText;
                }
            };
            if (zone.classList.contains('picked')) zone.dataset.kfKeep = '1';
            input.addEventListener('change', sync);
            ['dragenter', 'dragover'].forEach(ev => zone.addEventListener(ev, () => zone.classList.add('dragover')));
            ['dragleave', 'drop'].forEach(ev => zone.addEventListener(ev, () => zone.classList.remove('dragover')));
        });
    })();

    // ===== Format ribuan otomatis untuk semua input rupiah =====
    (function () {
        document.querySelectorAll('.kf-rupiah').forEach(function (el) {
            el.addEventListener('input', function () {
                const digits = this.value.replace(/[^\d]/g, '');
                this.value = digits ? Number(digits).toLocaleString('en-US') : '';
                this.dispatchEvent(new Event('kf-num-change', { bubbles: true }));
            });
        });
    })();

    // ===== Builder termin (metode, uang muka, progress rows, retensi) =====
    (function () {
        const wrap = document.getElementById('kfTerminWrap');
        const nilaiEl = document.getElementById('kfNilaiTotal');
        if (!nilaiEl) return; // mode edit: tidak ada builder

        const digits = (el) => el ? (parseFloat((el.value || '0').replace(/[^\d.]/g, '')) || 0) : 0;
        const rupiah = (n) => Math.round(n).toLocaleString('id-ID');

        const metodeTermin = document.getElementById('kfMetodeTermin');
        const metodeLump = document.getElementById('kfMetodeLumpsum');
        const adaUm = document.getElementById('kfAdaUm');
        const umWrap = document.getElementById('kfUmWrap');
        const gunakanRetensi = document.getElementById('kfGunakanRetensi');
        const retensiWrap = document.getElementById('kfRetensiWrap');
        const body = document.getElementById('kfTerminBody');
        const addBtn = document.getElementById('kfAddRow');

        const toggleMetode = () => wrap.classList.toggle('kf-hide', !(metodeTermin && metodeTermin.checked));
        const toggleUm = () => umWrap.classList.toggle('kf-hide', !adaUm.checked);
        const toggleRetensi = () => { if (retensiWrap) retensiWrap.classList.toggle('kf-hide', !gunakanRetensi.checked); recalc(); };

        function renumber() {
            body.querySelectorAll('.kf-termin-row').forEach((row, i) => {
                row.querySelector('.idx').textContent = i + 1;
                const del = row.querySelector('.kf-del');
                if (del) del.disabled = body.querySelectorAll('.kf-termin-row').length <= 1;
            });
        }

        function addRow(pct, ket) {
            const tr = document.createElement('tr');
            tr.className = 'kf-termin-row';
            tr.innerHTML =
                '<td><span class="idx"></span></td>' +
                '<td><input type="text" name="progress_keterangan[]" class="form-control" placeholder="Keterangan progress" value="' + (ket || '') + '"></td>' +
                '<td><div class="input-group"><input type="number" name="progress_persentase[]" class="form-control text-center kf-pct" min="0" max="100" step="0.0001" placeholder="0" value="' + (pct || '') + '"><span class="input-group-text">%</span></div></td>' +
                '<td class="kf-num fw-bold text-success kf-cell-val">Rp 0</td>' +
                '<td class="kf-num fw-bold kf-cell-um" style="color:#b45309;">Rp 0</td>' +
                '<td class="text-center"><button type="button" class="kf-btn-delrow kf-del"><i class="bi bi-trash3"></i></button></td>';
            body.appendChild(tr);
            renumber();
        }

        function recalc() {
            const nilaiTotal = digits(nilaiEl);
            const nilaiUm = adaUm.checked ? digits(document.getElementById('kfNilaiUm')) : 0;
            const retensiPct = (gunakanRetensi && gunakanRetensi.checked) ? digits(document.getElementById('kfRetensiPct')) : 0;

            const rows = Array.from(body.querySelectorAll('.kf-termin-row'));
            let sumProgress = 0;
            const eligible = []; // {nilai, cell, cellUm}

            rows.forEach((row) => {
                const pct = digits(row.querySelector('.kf-pct'));
                const nilai = Math.round(pct / 100 * nilaiTotal * 100) / 100;
                sumProgress += pct;
                row.querySelector('.kf-cell-val').textContent = 'Rp ' + rupiah(nilai);
                if (pct > 0) eligible.push({ nilai, cellUm: row.querySelector('.kf-cell-um') });
                else row.querySelector('.kf-cell-um').textContent = 'Rp 0';
            });

            const pelunasanPct = Math.round((100 - sumProgress - retensiPct) * 10000) / 10000;
            const pelunasanNilai = Math.round(Math.max(pelunasanPct, 0) / 100 * nilaiTotal * 100) / 100;
            document.getElementById('kfPelunasanPct').textContent = (pelunasanPct >= 0 ? pelunasanPct : 0);
            document.getElementById('kfPelunasanVal').textContent = rupiah(pelunasanNilai);

            const retensiNilai = Math.round(retensiPct / 100 * nilaiTotal * 100) / 100;
            const retVal = document.getElementById('kfRetensiVal');
            if (retVal) retVal.value = 'Rp ' + rupiah(retensiNilai);

            const over = (sumProgress + retensiPct) > 100.0001;
            document.getElementById('kfTerminWarn').classList.toggle('kf-hide', !over);

            // Preview alokasi uang muka proporsional (progress + pelunasan).
            const pelunasanCellUm = document.getElementById('kfPelunasanUm');
            const elig = eligible.slice();
            if (pelunasanNilai > 0) elig.push({ nilai: pelunasanNilai, cellUm: pelunasanCellUm });
            const eligTotal = elig.reduce((s, e) => s + e.nilai, 0);
            let remaining = Math.round(nilaiUm * 100) / 100;
            elig.forEach((e, i) => {
                let alloc = 0;
                if (nilaiUm > 0 && eligTotal > 0) {
                    if (i === elig.length - 1) alloc = Math.max(0, Math.round(remaining * 100) / 100);
                    else { alloc = Math.round(e.nilai / eligTotal * nilaiUm * 100) / 100; alloc = Math.min(alloc, remaining); remaining = Math.round((remaining - alloc) * 100) / 100; }
                }
                if (e.cellUm) e.cellUm.textContent = 'Rp ' + rupiah(alloc);
            });
            if (pelunasanNilai <= 0 && pelunasanCellUm) pelunasanCellUm.textContent = 'Rp 0';
        }

        if (metodeTermin) metodeTermin.addEventListener('change', () => { toggleMetode(); recalc(); });
        if (metodeLump) metodeLump.addEventListener('change', () => { toggleMetode(); recalc(); });
        adaUm.addEventListener('change', () => { toggleUm(); recalc(); });
        if (gunakanRetensi) gunakanRetensi.addEventListener('change', toggleRetensi);
        if (addBtn) addBtn.addEventListener('click', () => { addRow(); recalc(); });
        if (body) body.addEventListener('click', (e) => {
            const del = e.target.closest('.kf-del');
            if (del && body.querySelectorAll('.kf-termin-row').length > 1) { del.closest('tr').remove(); renumber(); recalc(); }
        });
        document.addEventListener('input', (e) => {
            if (e.target.matches('.kf-pct, #kfNilaiTotal, #kfNilaiUm, #kfRetensiPct')) recalc();
        });
        document.addEventListener('kf-num-change', recalc);

        renumber(); toggleMetode(); toggleUm(); if (retensiWrap) retensiWrap.classList.toggle('kf-hide', !(gunakanRetensi && gunakanRetensi.checked)); recalc();
    })();

    // ===== Progres kelengkapan form (bar di submit bar) =====
    (function () {
        const form = document.querySelector('form[data-kf-form]');
        const fill = document.getElementById('kfProgressFill');
        const pct = document.getElementById('kfProgressPct');
        const wrap = document.getElementById('kfProgress');
        if (!form || !fill) return;

        const isEdit = @json($isEdit);
        const spSudahAda = @json((bool) $spSudahAda);

        const hitung = () => {
            const f = (n) => form.querySelector('[name="' + n + '"]');
            const has = (n) => !!f(n);
            const filled = (n) => { const el = f(n); return el && el.value.trim() !== ''; };

            const cek = [];
            const req = ['nomor_surat_pesanan', 'tanggal_surat_pesanan', 'nama_pekerjaan', 'nilai_total_kontrak',
                'ppk_user_id', 'ppspm_user_id', 'koordinator_keuangan_user_id',
                'bendahara_pengeluaran_user_id', 'bendahara_penerimaan_user_id', 'kasubbag_user_id'];
            req.forEach(n => { if (has(n)) cek.push(filled(n)); });

            const vendorOk = filled('pihak_id')
                || (filled('vendor_nama') && filled('vendor_nama_bank') && filled('vendor_nomor_rekening') && filled('vendor_nama_rekening'));
            cek.push(vendorOk);

            const sp = f('file_surat_pesanan');
            cek.push((sp && sp.files && sp.files.length > 0) || (isEdit && spSudahAda));

            const done = cek.filter(Boolean).length;
            const persen = cek.length ? Math.round(done / cek.length * 100) : 0;
            fill.style.width = persen + '%';
            if (pct) pct.textContent = persen + '%';
            if (wrap) wrap.classList.toggle('done', persen >= 100);
        };

        form.addEventListener('input', hitung);
        form.addEventListener('change', hitung);
        hitung();
    })();

    // ===== Auto-isi form dari PDF Surat Pesanan (INAPROC) =====
    (function () {
        const fileInput = document.getElementById('keFileSuratPesanan');
        const status = document.getElementById('keParseStatus');
        const form = fileInput ? fileInput.closest('form') : null;
        if (!fileInput || !status || !form) return;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const field = (name) => form.querySelector('[name="' + name + '"]');

        const showStatus = (html, tone) => {
            status.classList.remove('d-none', 'text-success', 'text-danger', 'text-secondary');
            status.classList.add(tone);
            status.innerHTML = html;
        };

        const setValue = (name, value) => {
            const el = field(name);
            if (!el || value === null || value === undefined || value === '') return false;
            el.value = value;
            el.classList.remove('ke-autofilled');
            void el.offsetWidth;
            el.classList.add('ke-autofilled');
            el.dispatchEvent(new Event('input', { bubbles: true }));
            return true;
        };

        fileInput.addEventListener('change', async function () {
            const file = this.files && this.files[0];
            if (!file || file.type !== 'application/pdf') return;
            showStatus('<span class="spinner-border spinner-border-sm me-1"></span> Membaca Surat Pesanan…', 'text-secondary');

            const body = new FormData();
            body.append('file', file);
            let payload;
            try {
                const res = await fetch(@json(route('tagihan-kontrak-eksternal.parse')), {
                    method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body,
                });
                payload = await res.json();
            } catch (e) {
                showStatus('<i class="bi bi-info-circle"></i> PDF tidak dapat dibaca otomatis. Silakan isi form secara manual.', 'text-secondary');
                return;
            }
            if (!payload || !payload.ok) {
                showStatus('<i class="bi bi-info-circle"></i> ' + ((payload && payload.message) || 'PDF tidak dapat dibaca otomatis. Silakan isi form secara manual.'), 'text-secondary');
                return;
            }

            const d = payload.data || {};
            let filled = 0;
            if (setValue('nomor_surat_pesanan', d.nomor_surat_pesanan)) filled++;
            if (setValue('tanggal_surat_pesanan', d.tanggal_surat_pesanan)) filled++;
            if (setValue('sumber', 'INAPROC')) filled++;
            if (d.total_bruto && setValue('nilai_total_kontrak', Number(d.total_bruto).toLocaleString('en-US'))) filled++;

            const namaPekerjaan = field('nama_pekerjaan');
            if (namaPekerjaan && !namaPekerjaan.value && d.nama_pekerjaan_saran) {
                if (setValue('nama_pekerjaan', d.nama_pekerjaan_saran)) filled++;
            }

            const vendorSelect = document.getElementById('keVendorSelect');
            if (vendorSelect) {
                if (d.pihak_id && vendorSelect.querySelector('option[value="' + d.pihak_id + '"]')) {
                    vendorSelect.value = String(d.pihak_id);
                    vendorSelect.classList.add('ke-autofilled');
                    vendorSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    filled++;
                } else {
                    vendorSelect.value = '';
                    vendorSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    if (setValue('vendor_nama', d.vendor_nama)) filled++;
                    if (setValue('vendor_npwp', d.vendor_npwp)) filled++;
                    if (setValue('vendor_penanggung_jawab', d.vendor_penanggung_jawab)) filled++;
                    if (setValue('vendor_alamat', d.vendor_alamat)) filled++;
                }
            }

            if (filled > 0) {
                showStatus('<i class="bi bi-magic"></i> <strong>' + filled + ' kolom terisi otomatis</strong> dari Surat Pesanan — periksa kembali sebelum menyimpan.', 'text-success');
            } else {
                showStatus('<i class="bi bi-info-circle"></i> Tidak ada kolom yang dapat diisi otomatis. Silakan isi manual.', 'text-secondary');
            }
        });
    })();
</script>
@endpush
