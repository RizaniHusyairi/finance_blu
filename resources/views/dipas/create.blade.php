@extends('layouts.app')

@section('title', 'Tambah DIPA')

@push('css')
@include('dipas._form_styles')
@endpush

@section('content')
<div class="dipa-form">

    @if ($errors->any())
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm">
            <ul class="text-white mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ════════ HERO ════════ --}}
    <div class="df-hero">
        <div class="mesh"></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 position-relative" style="z-index:1;">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="df-chip"><i class="bi bi-journal-plus"></i> MASTER DATA</span>
                    <span class="df-chip"><i class="bi bi-magic"></i> HEADER + REVISI 0 SEKALIGUS</span>
                </div>
                <h4>Tambah DIPA Baru 📘</h4>
                <div class="sub">Header DIPA dan revisi awal aktif (Revisi 0) dibuat dalam satu proses penyimpanan.</div>
            </div>
            <a href="{{ route('dipas.index') }}" class="df-btn-back"><i class="bi bi-arrow-left"></i> Batal</a>
        </div>
    </div>

    <form action="{{ route('dipas.store') }}" method="POST" enctype="multipart/form-data" id="dipaCreateForm">
        @csrf

        <div class="row g-4">
            {{-- ════════ KOLOM FORM ════════ --}}
            <div class="col-lg-8">

                {{-- Step 1: Header --}}
                <div class="df-card mb-4" style="--d:.05s; --t:#4f46e5; --t2:#818cf8;">
                    <div class="df-card-head">
                        <span class="df-step">01</span>
                        <div>
                            <h6 class="df-card-title">Data Header DIPA</h6>
                            <div class="df-card-sub">Informasi utama dokumen induk DIPA.</div>
                        </div>
                    </div>
                    <div class="px-4 pb-4 pt-1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nomor DIPA</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                    <input type="text" name="nomor_dipa" id="dfNomor" class="form-control"
                                           value="{{ old('nomor_dipa') }}" placeholder="DIPA-025.01.2.400001/2026" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tahun Anggaran</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                                    <input type="number" name="tahun_anggaran" id="dfTahun" class="form-control"
                                           value="{{ old('tahun_anggaran', now()->year) }}" min="2000" max="2100" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Disahkan</label>
                                <input type="date" name="tanggal_disahkan" id="dfTanggal" class="form-control"
                                       value="{{ old('tanggal_disahkan', now()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status Aktif</label>
                                <select name="status_aktif" id="dfStatus" class="form-select" required>
                                    <option value="1" {{ old('status_aktif', '1') === '1' ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ old('status_aktif') === '0' ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 2: Revisi awal --}}
                <div class="df-card mb-4" style="--d:.12s; --t:#7c3aed; --t2:#a855f7;">
                    <div class="df-card-head">
                        <span class="df-step">02</span>
                        <div>
                            <h6 class="df-card-title">Revisi Awal (Revisi 0)</h6>
                            <div class="df-card-sub">Sistem otomatis membuat revisi awal aktif bernomor 0 saat disimpan.</div>
                        </div>
                    </div>
                    <div class="px-4 pb-4 pt-1">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Nomor Revisi</label>
                                <input type="text" class="form-control" value="0" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status Revisi</label>
                                <input type="text" class="form-control" value="Aktif" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Revisi</label>
                                <input type="date" name="tanggal_revisi" class="form-control"
                                       value="{{ old('tanggal_revisi', now()->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total Pagu Awal</label>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold">Rp</span>
                                    <input type="text" inputmode="decimal" autocomplete="off" name="total_pagu" id="dfPagu"
                                           class="form-control fw-bold" placeholder="0"
                                           value="{{ old('total_pagu', 0) != '' ? number_format((float) old('total_pagu', 0), 0, ',', '.') : '' }}" required>
                                </div>
                                <div class="df-hint mt-1" id="dfPaguHint">Nilai pagu keseluruhan revisi awal.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Dokumen DIPA (PDF)</label>
                                <div class="df-drop" id="dfDrop">
                                    <input type="file" name="file_dokumen_dipa" id="dfFile" accept=".pdf">
                                    <div class="df-drop-ic"><i class="bi bi-cloud-arrow-up-fill" id="dfDropIcon"></i></div>
                                    <div class="fw-bold small" id="dfDropText">Seret file ke sini atau klik untuk memilih</div>
                                    <div class="df-hint" id="dfDropHint">Opsional · PDF · maks. 5 MB</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="4"
                                          placeholder="Tambahkan catatan revisi awal bila diperlukan">{{ old('keterangan') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Aksi --}}
                <div class="df-card" style="--d:.18s;">
                    <div class="p-4 df-actions">
                        <a href="{{ route('dipas.index') }}" class="df-btn df-btn-ghost text-decoration-none">Batal</a>
                        <button type="submit" name="redirect_action" value="save" class="df-btn df-btn-primary js-df-submit">
                            <i class="bi bi-save"></i> Simpan
                        </button>
                        <button type="submit" name="redirect_action" value="save_and_detail" class="df-btn df-btn-success js-df-submit">
                            <i class="bi bi-arrow-right-circle"></i> Simpan &amp; Lanjut ke Detail DIPA
                        </button>
                    </div>
                </div>
            </div>

            {{-- ════════ PRATINJAU LIVE ════════ --}}
            <div class="col-lg-4">
                <div class="df-preview">
                    <div class="df-doc">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-journal-bookmark-fill fs-4" style="color:#a5b4fc;"></i>
                                <span class="fw-bold" style="letter-spacing:.08em; font-size:.78rem;">PRATINJAU DIPA</span>
                            </div>
                            <span class="df-doc-badge" id="pvStatus"><i class="bi bi-check-circle-fill"></i> AKTIF</span>
                        </div>

                        <div class="df-doc-lbl">Nomor DIPA</div>
                        <div class="df-doc-val mono mb-2" id="pvNomor"><span class="text-white-50">Belum diisi</span><span class="df-cursor"></span></div>

                        <div class="row g-2">
                            <div class="col-6">
                                <div class="df-doc-lbl">Tahun Anggaran</div>
                                <div class="df-doc-val" id="pvTahun">{{ now()->year }}</div>
                            </div>
                            <div class="col-6">
                                <div class="df-doc-lbl">Disahkan</div>
                                <div class="df-doc-val" id="pvTanggal">—</div>
                            </div>
                        </div>

                        <div class="df-doc-sep"></div>

                        <div class="df-doc-lbl">Total Pagu Revisi 0</div>
                        <div class="df-pagu" id="pvPagu">Rp 0</div>

                        <div class="d-flex align-items-center gap-2 mt-3 small" style="color:rgba(255,255,255,.75);" id="pvFile">
                            <i class="bi bi-paperclip"></i> Tanpa lampiran dokumen
                        </div>
                    </div>
                    <div class="df-hint mt-3 text-center">
                        <i class="bi bi-magic me-1"></i>Pratinjau terisi otomatis mengikuti isian formulir.
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var form = document.getElementById('dipaCreateForm');
    if (!form) return;

    /* ── Format rupiah live pada Total Pagu ── */
    var pagu = document.getElementById('dfPagu');
    function parseRp(v) {
        v = String(v || '').trim();
        if (!v) return 0;
        return parseFloat(v.replace(/\./g, '').replace(',', '.')) || 0;
    }
    function liveFormat(input) {
        var raw = input.value;
        var caretDigits = raw.slice(0, input.selectionStart || 0).replace(/[^\d,]/g, '').length;
        var clean = raw.replace(/[^\d,]/g, '');
        var firstComma = clean.indexOf(',');
        if (firstComma !== -1) clean = clean.slice(0, firstComma + 1) + clean.slice(firstComma + 1).replace(/,/g, '');
        var parts = clean.split(',');
        var grouped = parts[0].replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        var out = grouped + (parts.length > 1 ? ',' + parts[1].slice(0, 2) : '');
        input.value = out;
        var pos = 0, seen = 0;
        while (pos < out.length && seen < caretDigits) {
            if (/[\d,]/.test(out[pos])) seen++;
            pos++;
        }
        try { input.setSelectionRange(pos, pos); } catch (e) { /* tidak fokus */ }
    }

    /* ── Pratinjau live ── */
    var fmt = new Intl.NumberFormat('id-ID');
    var bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

    function syncPreview() {
        var nomor = document.getElementById('dfNomor').value.trim();
        document.getElementById('pvNomor').innerHTML = nomor
            ? escapeHtml(nomor)
            : '<span class="text-white-50">Belum diisi</span><span class="df-cursor"></span>';

        document.getElementById('pvTahun').textContent = document.getElementById('dfTahun').value || '—';

        var tgl = document.getElementById('dfTanggal').value;
        if (tgl) {
            var d = new Date(tgl + 'T00:00:00');
            document.getElementById('pvTanggal').textContent = d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
        } else {
            document.getElementById('pvTanggal').textContent = '—';
        }

        document.getElementById('pvPagu').textContent = 'Rp ' + fmt.format(Math.round(parseRp(pagu.value)));

        var aktif = document.getElementById('dfStatus').value === '1';
        var badge = document.getElementById('pvStatus');
        badge.classList.toggle('off', !aktif);
        badge.innerHTML = aktif
            ? '<i class="bi bi-check-circle-fill"></i> AKTIF'
            : '<i class="bi bi-pause-circle"></i> NONAKTIF';
    }

    function escapeHtml(s) {
        return s.replace(/[&<>"']/g, function (c) {
            return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
        });
    }

    ['dfNomor', 'dfTahun', 'dfTanggal', 'dfStatus'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', syncPreview);
        document.getElementById(id).addEventListener('change', syncPreview);
    });
    pagu.addEventListener('input', function () { liveFormat(pagu); syncPreview(); });
    syncPreview();

    /* ── Dropzone dokumen ── */
    var drop = document.getElementById('dfDrop');
    var file = document.getElementById('dfFile');
    ['dragenter', 'dragover'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('dragover'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('dragover'); });
    });
    file.addEventListener('change', function () {
        var f = file.files[0];
        var text = document.getElementById('dfDropText');
        var hint = document.getElementById('dfDropHint');
        var icon = document.getElementById('dfDropIcon');
        var pv = document.getElementById('pvFile');
        if (f) {
            drop.classList.add('has-file');
            icon.className = 'bi bi-file-earmark-check-fill';
            text.textContent = f.name;
            hint.textContent = (f.size / 1024 / 1024).toFixed(2).replace('.', ',') + ' MB · klik untuk mengganti';
            pv.innerHTML = '<i class="bi bi-file-earmark-pdf-fill"></i> ' + escapeHtml(f.name);
        } else {
            drop.classList.remove('has-file');
            icon.className = 'bi bi-cloud-arrow-up-fill';
            text.textContent = 'Seret file ke sini atau klik untuk memilih';
            hint.textContent = 'Opsional · PDF · maks. 5 MB';
            pv.innerHTML = '<i class="bi bi-paperclip"></i> Tanpa lampiran dokumen';
        }
    });

    /* ── Submit: un-format pagu + tombol loading ── */
    form.addEventListener('submit', function (e) {
        pagu.value = String(parseRp(pagu.value));

        // Tombol yang di-disable tidak ikut terkirim — amankan redirect_action
        // ke hidden input dulu sebelum tombol dimatikan (guard klik ganda).
        var btn = e.submitter && e.submitter.classList.contains('js-df-submit') ? e.submitter : null;
        if (btn && btn.name) {
            var hidden = form.querySelector('input[type=hidden][name=redirect_action]');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'redirect_action';
                form.appendChild(hidden);
            }
            hidden.value = btn.value;
        }

        form.querySelectorAll('.js-df-submit').forEach(function (b) { b.disabled = true; });
        if (btn) {
            btn.style.minWidth = Math.ceil(btn.getBoundingClientRect().width) + 'px';
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan…';
        }
    });
});
</script>
@endpush
