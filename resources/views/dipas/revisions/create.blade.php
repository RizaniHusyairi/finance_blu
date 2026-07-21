@extends('layouts.app')

@section('title', 'Form Revisi DIPA')

@php
    $paguAktif = (float) $summary['total_pagu_revisi_aktif'];
    $paguSebelumnya = old('total_pagu', $paguAktif);
@endphp

@push('css')
@include('dipas._form_styles')
<style>
/* Tambahan khusus form revisi (tile ringkasan + pembanding pagu) */
.df-tile { display:flex; align-items:center; gap:.8rem; border:1px solid #e8ecf5; border-radius:.9rem;
    padding:.8rem .95rem; background:linear-gradient(180deg,#fff,#fafbff); height:100%;
    transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
.df-tile:hover { transform:translateY(-2px); border-color:#dfe3f6; box-shadow:0 10px 22px -14px rgba(79,70,229,.45); }
.df-tile .t-ic { width:36px; height:36px; flex-shrink:0; border-radius:10px; display:grid; place-items:center;
    font-size:.95rem; color:var(--t,#4f46e5); background:var(--ts,#eef2ff); }
.df-tile .t-lbl { font-size:.62rem; font-weight:800; text-transform:uppercase; letter-spacing:.07em; color:#94a3b8; }
.df-tile .t-val { font-size:.9rem; font-weight:800; color:#0f172a; overflow-wrap:anywhere; }

.df-switch { border:1px solid #e8ecf5; border-radius:1rem; padding:.9rem 1.1rem;
    background:linear-gradient(180deg,#fff,#fafbff); transition:border-color .2s ease, background .2s ease; }
.df-switch.on { border-color:#a7f3d0; background:linear-gradient(180deg,#f0fdf4,#ecfdf5); }
.df-switch .form-check-input { width:2.6em; height:1.4em; cursor:pointer; }
.df-switch .form-check-input:checked { background-color:#10b981; border-color:#10b981; }

.df-delta { display:inline-flex; align-items:center; gap:.4rem; font-size:.78rem; font-weight:800;
    padding:.32rem .8rem; border-radius:999px; transition:transform .25s cubic-bezier(.34,1.56,.64,1); }
.df-delta.pop { transform:scale(1.12); }
.df-delta.up   { background:rgba(52,211,153,.22); border:1px solid rgba(110,231,183,.5); color:#a7f3d0; }
.df-delta.down { background:rgba(251,113,133,.22); border:1px solid rgba(253,164,175,.5); color:#fecdd3; }
.df-delta.flat { background:rgba(148,163,184,.22); border:1px solid rgba(203,213,225,.4); color:#e2e8f0; }
</style>
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
                    <span class="df-chip"><i class="bi bi-hash"></i> {{ $dipa->nomor_dipa }}</span>
                    <span class="df-chip"><i class="bi bi-arrow-repeat"></i> REVISI AKTIF: {{ $summary['revisi_aktif_saat_ini'] }}</span>
                </div>
                <h4>Buat Revisi {{ $nextRevisionNumber }} 🔄</h4>
                <div class="sub">Revisi baru dibuat sebagai <strong>draft nonaktif</strong> — revisi lama tetap berjalan sampai revisi baru diaktifkan manual dari halaman detail DIPA.</div>
            </div>
            <a href="{{ route('dipas.show', $dipa) }}" class="df-btn-back"><i class="bi bi-arrow-left"></i> Batal</a>
        </div>
    </div>

    <form action="{{ route('dipas.revisions.store', $dipa) }}" method="POST" enctype="multipart/form-data" id="dipaRevisiForm">
        @csrf
        <input type="hidden" name="nomor_revisi" value="{{ $nextRevisionNumber }}">

        <div class="row g-4">
            {{-- ════════ KOLOM FORM ════════ --}}
            <div class="col-lg-8">

                {{-- Step 0: Dropzone POK revisi — form terisi otomatis --}}
                @include('dipas._partials.pok_dropzone', [
                    'dzJudul' => 'Unggah POK Revisi — Form Terisi Otomatis',
                    'dzSub' => 'Total pagu & tanggal revisi terisi sendiri; saat disimpan seluruh COA revisi dibuat dari POK (opsi salin dimatikan otomatis).',
                    'dzWarna' => '#0891b2',
                    'dzFlow' => [
                        ['bi-file-earmark-arrow-up', 'Unggah PDF POK revisi'],
                        ['bi-eye', 'Dibaca otomatis'],
                        ['bi-magic', 'Pagu & tanggal terisi'],
                        ['bi-table', 'Pratinjau COA tampil'],
                        ['bi-database-add', 'Simpan → item revisi terbentuk'],
                    ],
                ])

                {{-- Step 1: Ringkasan induk --}}
                <div class="df-card mb-4" style="--d:.05s; --t:#0891b2; --t2:#22d3ee;">
                    <div class="df-card-head">
                        <span class="df-step">01</span>
                        <div>
                            <h6 class="df-card-title">Ringkasan DIPA Induk</h6>
                            <div class="df-card-sub">Kondisi DIPA yang sedang direvisi saat ini.</div>
                        </div>
                    </div>
                    <div class="px-4 pb-4 pt-1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="df-tile" style="--t:#4f46e5; --ts:#eef2ff;">
                                    <span class="t-ic"><i class="bi bi-journal-bookmark-fill"></i></span>
                                    <div>
                                        <div class="t-lbl">Nomor DIPA · TA {{ $dipa->tahun_anggaran }}</div>
                                        <div class="t-val font-monospace">{{ $dipa->nomor_dipa }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="df-tile" style="--t:#0891b2; --ts:#ecfeff;">
                                    <span class="t-ic"><i class="bi bi-calendar3"></i></span>
                                    <div>
                                        <div class="t-lbl">Disahkan</div>
                                        <div class="t-val">{{ optional($dipa->tanggal_disahkan)->translatedFormat('d M Y') ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="df-tile" style="--t:{{ $dipa->status_aktif ? '#047857' : '#64748b' }}; --ts:{{ $dipa->status_aktif ? '#ecfdf5' : '#f1f5f9' }};">
                                    <span class="t-ic"><i class="bi {{ $dipa->status_aktif ? 'bi-check-circle' : 'bi-pause-circle' }}"></i></span>
                                    <div>
                                        <div class="t-lbl">Status DIPA</div>
                                        <div class="t-val" style="color:{{ $dipa->status_aktif ? '#047857' : '#64748b' }};">{{ $dipa->status_aktif ? 'Aktif' : 'Nonaktif' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-6">
                                <div class="df-tile" style="--t:#7c3aed; --ts:#f5f3ff;">
                                    <span class="t-ic"><i class="bi bi-arrow-repeat"></i></span>
                                    <div>
                                        <div class="t-lbl">Revisi Aktif Saat Ini</div>
                                        <div class="t-val">Revisi {{ $summary['revisi_aktif_saat_ini'] }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-6">
                                <div class="df-tile" style="--t:#047857; --ts:#ecfdf5;">
                                    <span class="t-ic"><i class="bi bi-cash-stack"></i></span>
                                    <div>
                                        <div class="t-lbl">Pagu Revisi Aktif</div>
                                        <div class="t-val" style="color:#047857;">Rp {{ number_format($paguAktif, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="df-tile" style="--t:#b45309; --ts:#fffbeb;">
                                    <span class="t-ic"><i class="bi bi-list-ol"></i></span>
                                    <div>
                                        <div class="t-lbl">COA</div>
                                        <div class="t-val">{{ number_format($summary['jumlah_item_anggaran_revisi_aktif']) }} COA</div>
                                        <div class="df-hint">{{ number_format($summary['jumlah_item_anggaran_aktif']) }} berstatus aktif</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 2: Form revisi --}}
                <div class="df-card mb-4" style="--d:.12s; --t:#7c3aed; --t2:#a855f7;">
                    <div class="df-card-head">
                        <span class="df-step">02</span>
                        <div>
                            <h6 class="df-card-title">Detail Revisi Baru</h6>
                            <div class="df-card-sub">Revisi lama tetap aktif sampai Anda mengaktifkan revisi baru secara manual.</div>
                        </div>
                    </div>
                    <div class="px-4 pb-4 pt-1">
                        <div class="row g-3">
                            <div class="col-md-3 col-6">
                                <label class="form-label">Nomor Revisi Baru</label>
                                <input type="text" class="form-control" value="{{ $nextRevisionNumber }}" readonly>
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label">Status Revisi</label>
                                <input type="text" class="form-control" value="Draft Nonaktif" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Revisi</label>
                                <input type="date" name="tanggal_revisi" id="dfTanggalRevisi" class="form-control"
                                       value="{{ old('tanggal_revisi', now()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Total Pagu Revisi</label>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold">Rp</span>
                                    <input type="text" inputmode="decimal" autocomplete="off" name="total_pagu" id="dfPagu"
                                           class="form-control fw-bold" placeholder="0"
                                           value="{{ $paguSebelumnya !== '' ? number_format((float) $paguSebelumnya, 0, ',', '.') : '' }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label d-block">Salin COA</label>
                                <div class="df-switch {{ old('salin_item_anggaran', '1') ? 'on' : '' }}" id="dfSwitchWrap">
                                    <div class="form-check form-switch d-flex align-items-center gap-2 ps-0 mb-1">
                                        <input class="form-check-input ms-0 flex-shrink-0" type="checkbox" role="switch"
                                               id="salin_item_anggaran" name="salin_item_anggaran" value="1"
                                               {{ old('salin_item_anggaran', '1') ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold small" for="salin_item_anggaran" style="cursor:pointer;">
                                            Salin COA dari revisi aktif sebelumnya
                                        </label>
                                    </div>
                                    <div class="df-hint">Jika aktif, {{ number_format($summary['jumlah_item_anggaran_revisi_aktif']) }} COA dari revisi aktif saat ini akan dikloning ke revisi baru.</div>
                                </div>
                            </div>
                            <div class="col-12">
                                @include('dipas._partials.pok_preview')
                            </div>
                            <div class="col-12">
                                <label class="form-label">Keterangan Revisi</label>
                                <textarea name="keterangan" class="form-control" rows="4"
                                          placeholder="Tambahkan alasan atau ringkasan perubahan revisi ini">{{ old('keterangan') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Aksi --}}
                <div class="df-card" style="--d:.18s;">
                    <div class="p-4 df-actions">
                        <a href="{{ route('dipas.show', $dipa) }}" class="df-btn df-btn-ghost text-decoration-none">Batal</a>
                        <button type="submit" name="redirect_action" value="save" class="df-btn df-btn-primary js-df-submit">
                            <i class="bi bi-save"></i> Simpan Revisi
                        </button>
                        <button type="submit" name="redirect_action" value="save_and_manage" class="df-btn df-btn-success js-df-submit">
                            <i class="bi bi-arrow-right-circle"></i> Simpan &amp; Kelola COA
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
                                <i class="bi bi-arrow-repeat fs-4" style="color:#a5b4fc;"></i>
                                <span class="fw-bold" style="letter-spacing:.08em; font-size:.78rem;">PRATINJAU REVISI {{ $nextRevisionNumber }}</span>
                            </div>
                            <span class="df-doc-badge off"><i class="bi bi-pencil"></i> DRAFT</span>
                        </div>

                        <div class="df-doc-lbl">Nomor DIPA</div>
                        <div class="df-doc-val mono mb-2">{{ $dipa->nomor_dipa }}</div>

                        <div class="row g-2">
                            <div class="col-6">
                                <div class="df-doc-lbl">Tanggal Revisi</div>
                                <div class="df-doc-val" id="pvTanggal">—</div>
                            </div>
                            <div class="col-6">
                                <div class="df-doc-lbl">COA Disalin</div>
                                <div class="df-doc-val" id="pvSalin">{{ number_format($summary['jumlah_item_anggaran_revisi_aktif']) }} COA</div>
                            </div>
                        </div>

                        <div class="df-doc-sep"></div>

                        <div class="df-doc-lbl">Pagu Revisi {{ $summary['revisi_aktif_saat_ini'] }} (aktif)</div>
                        <div class="df-doc-val" style="color:rgba(255,255,255,.75);">Rp {{ number_format($paguAktif, 0, ',', '.') }}</div>

                        <div class="d-flex align-items-center gap-2 my-1" style="color:#a5b4fc;">
                            <i class="bi bi-arrow-down-short fs-5"></i>
                            <span class="df-delta flat" id="pvDelta"><i class="bi bi-dash-lg"></i> Tetap</span>
                        </div>

                        <div class="df-doc-lbl">Pagu Revisi {{ $nextRevisionNumber }} (baru)</div>
                        <div class="df-pagu" id="pvPagu">Rp {{ number_format((float) $paguSebelumnya, 0, ',', '.') }}</div>

                        <div class="d-flex align-items-center gap-2 mt-3 small" style="color:rgba(255,255,255,.75);" id="pvFile">
                            <i class="bi bi-paperclip"></i> Tanpa lampiran dokumen
                        </div>
                    </div>
                    <div class="df-hint mt-3 text-center">
                        <i class="bi bi-magic me-1"></i>Pratinjau &amp; selisih pagu terhitung otomatis saat Anda mengetik.
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
    var form = document.getElementById('dipaRevisiForm');
    if (!form) return;

    var paguAktif = {{ $paguAktif }};
    var fmt = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });
    var bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
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

    /* ── Pratinjau + selisih live ── */
    function syncPreview() {
        var paguBaru = parseRp(pagu.value);
        document.getElementById('pvPagu').textContent = 'Rp ' + fmt.format(Math.round(paguBaru));

        var selisih = paguBaru - paguAktif;
        var delta = document.getElementById('pvDelta');
        delta.classList.remove('up', 'down', 'flat');
        if (Math.round(selisih) > 0) {
            delta.classList.add('up');
            delta.innerHTML = '<i class="bi bi-arrow-up-right"></i> Naik Rp ' + fmt.format(Math.round(selisih));
        } else if (Math.round(selisih) < 0) {
            delta.classList.add('down');
            delta.innerHTML = '<i class="bi bi-arrow-down-right"></i> Turun Rp ' + fmt.format(Math.abs(Math.round(selisih)));
        } else {
            delta.classList.add('flat');
            delta.innerHTML = '<i class="bi bi-dash-lg"></i> Tetap (tanpa perubahan pagu)';
        }
        delta.classList.remove('pop');
        void delta.offsetWidth;
        delta.classList.add('pop');
        setTimeout(function () { delta.classList.remove('pop'); }, 260);

        var tgl = document.getElementById('dfTanggalRevisi').value;
        if (tgl) {
            var d = new Date(tgl + 'T00:00:00');
            document.getElementById('pvTanggal').textContent = d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
        } else {
            document.getElementById('pvTanggal').textContent = '—';
        }

        var salin = document.getElementById('salin_item_anggaran');
        document.getElementById('pvSalin').textContent = salin.checked
            ? '{{ number_format($summary['jumlah_item_anggaran_revisi_aktif']) }} COA'
            : 'Tidak disalin';
        document.getElementById('dfSwitchWrap').classList.toggle('on', salin.checked);
    }

    pagu.addEventListener('input', function () { liveFormat(pagu); syncPreview(); });
    document.getElementById('dfTanggalRevisi').addEventListener('change', syncPreview);
    document.getElementById('salin_item_anggaran').addEventListener('change', syncPreview);
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
        function esc(s) {
            return s.replace(/[&<>"']/g, function (c) {
                return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
            });
        }
        if (f) {
            drop.classList.add('has-file');
            icon.className = 'bi bi-file-earmark-check-fill';
            text.textContent = f.name;
            hint.textContent = (f.size / 1024 / 1024).toFixed(2).replace('.', ',') + ' MB · klik untuk mengganti';
            pv.innerHTML = '<i class="bi bi-file-earmark-pdf-fill"></i> ' + esc(f.name);
            bacaPok(f);
        } else {
            drop.classList.remove('has-file');
            icon.className = 'bi bi-cloud-arrow-up-fill';
            text.textContent = 'Seret file ke sini atau klik untuk memilih';
            hint.textContent = 'Opsional · PDF · maks. 5 MB';
            pv.innerHTML = '<i class="bi bi-paperclip"></i> Tanpa lampiran dokumen';
        }
    });

    /* ── Baca POK revisi otomatis: isi pagu + tanggal, siapkan token impor COA ── */
    var pokToken = document.getElementById('dfPokToken');
    var pokInfo = document.getElementById('dfPokInfo');
    var tanggalInput = document.getElementById('dfTanggalRevisi');
    var salinInput = document.getElementById('salin_item_anggaran');

    function tampilkanPokInfo(html, warna) {
        pokInfo.classList.remove('d-none');
        pokInfo.style.borderColor = warna === 'ok' ? '#a7f3d0' : (warna === 'err' ? '#fecdd3' : '#c7d2fe');
        pokInfo.style.background = warna === 'ok' ? '#ecfdf5' : (warna === 'err' ? '#fff1f2' : '#eef2ff');
        pokInfo.innerHTML = html;
    }

    function bacaPok(f) {
        pokToken.value = '';
        if (window.clearPokPreview) window.clearPokPreview();
        drop.classList.remove('done');
        drop.classList.add('reading');
        tampilkanPokInfo('<i class="bi bi-hourglass-split me-1"></i>Membaca PDF sebagai POK…', 'info');

        var fd = new FormData();
        fd.append('file_pok', f);

        fetch('{{ route('dipas.parse-pok') }}', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content
                    || '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(function (res) { return res.json().then(function (data) { return { status: res.status, data: data }; }); })
        .then(function (r) {
            drop.classList.remove('reading');
            if (r.status !== 200 || !r.data.ok) {
                tampilkanPokInfo('<i class="bi bi-info-circle me-1"></i>' + (r.data.pesan || 'File bukan POK — form diisi manual seperti biasa.'), 'err');
                return;
            }
            drop.classList.add('done');

            var d = r.data;
            pokToken.value = d.token;

            if (d.total != null) {
                pagu.value = fmt.format(d.total);
                liveFormat(pagu);
            }
            if (d.tanggal_ttd) {
                tanggalInput.value = d.tanggal_ttd;
                tanggalInput.dispatchEvent(new Event('change'));
            }
            if (salinInput.checked) {
                salinInput.checked = false;
                salinInput.dispatchEvent(new Event('change'));
            }
            syncPreview();

            var kontrol = d.seimbang
                ? '<span class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> SEIMBANG dengan alokasi header</span>'
                : '<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> tidak sama dengan alokasi header</span>';

            tampilkanPokInfo(
                '<div class="fw-bold mb-1"><i class="bi bi-magic me-1"></i>POK terbaca: ' + d.jumlah_baris + ' baris detil · Rp ' + fmt.format(d.total) + '</div>'
                + '<div>' + kontrol + '</div>'
                + '<div class="mt-1">Total pagu' + (d.tanggal_ttd ? ' dan tanggal revisi' : '') + ' terisi otomatis. Saat disimpan, <b>' + d.jumlah_baris + ' COA + item revisi dibuat dari POK ini</b> — opsi salin COA dimatikan karena nilai POK yang dipakai. Rinciannya di tabel pratinjau bawah.</div>',
                'ok'
            );
            if (window.renderPokPreview) window.renderPokPreview(d);
        })
        .catch(function () {
            drop.classList.remove('reading');
            tampilkanPokInfo('<i class="bi bi-info-circle me-1"></i>Gagal membaca file — form diisi manual seperti biasa.', 'err');
        });
    }

    /* ── Submit: un-format pagu + amankan redirect_action + loading ── */
    form.addEventListener('submit', function (e) {
        pagu.value = String(parseRp(pagu.value));

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
