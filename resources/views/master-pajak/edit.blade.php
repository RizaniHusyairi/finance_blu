@extends('layouts.app')

@section('title', 'Edit Pajak — ' . $pajak->kode_pajak)

@php
    $oldStatusAktif = old('status_aktif', $pajak->status_aktif ? '1' : '');
@endphp

@push('css')
@include('dipas._form_styles')
<style>
/* Tambahan khusus form pajak: kartu KAP/KJS + simulator (selaras form tambah) */
.df-switch { border:1px solid #e8ecf5; border-radius:1rem; padding:.9rem 1.1rem;
    background:linear-gradient(180deg,#fff,#fafbff); transition:border-color .2s ease, background .2s ease; }
.df-switch.on { border-color:#a7f3d0; background:linear-gradient(180deg,#f0fdf4,#ecfdf5); }
.df-switch .form-check-input { width:2.6em; height:1.4em; cursor:pointer; }
.df-switch .form-check-input:checked { background-color:#10b981; border-color:#10b981; }

.pj-ssp { display:flex; gap:.6rem; }
.pj-ssp .cell { flex:1; text-align:center; border:1px dashed rgba(255,255,255,.35); border-radius:.7rem;
    padding:.45rem .3rem; }
.pj-ssp .cell .v { font-family:SFMono-Regular,Menlo,Consolas,monospace; font-weight:800; font-size:.95rem; }
.pj-ssp .cell .l { font-size:.58rem; font-weight:800; letter-spacing:.1em; color:rgba(255,255,255,.65); }

.pj-sim { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.22); border-radius:.9rem;
    padding:.85rem .95rem; }
.pj-sim .form-control { background:rgba(255,255,255,.92); border:0; border-radius:.55rem;
    font-weight:800; font-size:.85rem; }
.pj-sim-hasil { font-size:1.05rem; font-weight:800; color:#a7f3d0; font-variant-numeric:tabular-nums;
    transition:transform .25s cubic-bezier(.34,1.56,.64,1); display:inline-block; }
.pj-sim-hasil.pop { transform:scale(1.12); }

.pj-persen-badge { font-size:clamp(1.6rem,3vw,2.1rem); font-weight:800; letter-spacing:-.02em;
    font-variant-numeric:tabular-nums; line-height:1; }
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
                    <span class="df-chip"><i class="bi bi-percent"></i> MASTER DATA PAJAK</span>
                    <span class="df-chip"><i class="bi bi-upc"></i> {{ $pajak->kode_pajak }}</span>
                </div>
                <h4>Edit Tarif Pajak ✏️</h4>
                <div class="sub">Perubahan berlaku untuk pemakaian tarif berikutnya — potongan pajak tagihan yang sudah tersimpan tidak ikut berubah.</div>
            </div>
            <a href="{{ route('master-pajak.index') }}" class="df-btn-back"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>
    </div>

    <form action="{{ route('master-pajak.update', $pajak) }}" method="POST" id="pajakEditForm">
        @csrf
        @method('PUT')

        <div class="row g-4">
            {{-- ════════ KOLOM FORM ════════ --}}
            <div class="col-lg-8">

                {{-- Step 1: Informasi utama --}}
                <div class="df-card mb-4" style="--d:.05s; --t:#4f46e5; --t2:#818cf8;">
                    <div class="df-card-head">
                        <span class="df-step">01</span>
                        <div>
                            <h6 class="df-card-title">Informasi Utama Tarif</h6>
                            <div class="df-card-sub">Identitas tarif yang tampil di dropdown pilihan pajak pada tagihan.</div>
                        </div>
                    </div>
                    <div class="px-4 pb-4 pt-1">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Kode Pajak <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-upc"></i></span>
                                    <input type="text" name="kode_pajak" id="pjKode" class="form-control font-monospace fw-bold text-uppercase @error('kode_pajak') is-invalid @enderror"
                                           value="{{ old('kode_pajak', $pajak->kode_pajak) }}" maxlength="30" required>
                                    @error('kode_pajak')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="df-hint mt-1"><i class="bi bi-info-circle me-1"></i>Kode unik internal — otomatis huruf kapital. Awali "PPN" untuk tarif PPN agar kalkulator DPP mengenalinya.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jenis Pajak <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-tag"></i></span>
                                    <input type="text" name="jenis_pajak" id="pjJenis" class="form-control @error('jenis_pajak') is-invalid @enderror"
                                           value="{{ old('jenis_pajak', $pajak->jenis_pajak) }}" maxlength="50" required>
                                    @error('jenis_pajak')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="df-hint mt-1"><i class="bi bi-info-circle me-1"></i>Nama yang dibaca user, mis. "PPN", "PPh Pasal 23 Jasa".</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Persentase Tarif <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="persentase" id="pjPersen" class="form-control fw-bold @error('persentase') is-invalid @enderror"
                                           value="{{ old('persentase', $pajak->persentase) }}" step="0.0001" min="0" required>
                                    <span class="input-group-text fw-bold">%</span>
                                    @error('persentase')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="df-hint mt-1"><i class="bi bi-info-circle me-1"></i>Angka saja tanpa simbol — desimal pakai titik, mis. <code>2.65</code> untuk 2,65%.</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 2: Kode billing --}}
                <div class="df-card mb-4" style="--d:.11s; --t:#7c3aed; --t2:#a855f7;">
                    <div class="df-card-head">
                        <span class="df-step">02</span>
                        <div>
                            <h6 class="df-card-title">Kode Setoran ke DJP (KAP / KJS)</h6>
                            <div class="df-card-sub">Dipakai Bendahara Pengeluaran saat membuat kode billing &amp; menyetor pajak (SSP). Boleh dikosongkan bila belum tahu.</div>
                        </div>
                    </div>
                    <div class="px-4 pb-4 pt-1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Kode Akun Pajak (KAP)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-safe"></i></span>
                                    <input type="text" name="kode_akun_pajak" id="pjKap" class="form-control font-monospace fw-bold @error('kode_akun_pajak') is-invalid @enderror"
                                           value="{{ old('kode_akun_pajak', $pajak->kode_akun_pajak) }}" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="411124">
                                    @error('kode_akun_pajak')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="df-hint mt-1"><i class="bi bi-info-circle me-1"></i>6 digit jenis pajak pada billing DJP: PPN <code>411211</code>, PPh 22 <code>411122</code>, PPh 23 <code>411124</code>, PPh 4(2) <code>411128</code>.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kode Jenis Setoran (KJS)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-123"></i></span>
                                    <input type="text" name="kode_jenis_setoran" id="pjKjs" class="form-control font-monospace fw-bold @error('kode_jenis_setoran') is-invalid @enderror"
                                           value="{{ old('kode_jenis_setoran', $pajak->kode_jenis_setoran) }}" maxlength="3" inputmode="numeric" pattern="[0-9]{3}" placeholder="104">
                                    @error('kode_jenis_setoran')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="df-hint mt-1"><i class="bi bi-info-circle me-1"></i>3 digit cara setor: <code>900</code> bendahara pemungut, <code>100</code> masa, <code>104</code> jasa konstruksi.</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 3: Rumus, masa berlaku, status --}}
                <div class="df-card mb-4" style="--d:.17s; --t:#059669; --t2:#34d399;">
                    <div class="df-card-head">
                        <span class="df-step">03</span>
                        <div>
                            <h6 class="df-card-title">Rumus, Masa Berlaku &amp; Status</h6>
                            <div class="df-card-sub">Catatan rumus tampil sebagai bantuan saat operator memilih tarif ini.</div>
                        </div>
                    </div>
                    <div class="px-4 pb-4 pt-1">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Rumus Perhitungan (Catatan)</label>
                                <textarea name="rumus" id="pjRumus" class="form-control @error('rumus') is-invalid @enderror" rows="2"
                                          placeholder="Contoh: DPP x 11% — DPP = nilai bruto x 100/111">{{ old('rumus', $pajak->rumus) }}</textarea>
                                @error('rumus')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="df-hint mt-1"><i class="bi bi-info-circle me-1"></i>Sekadar catatan referensi untuk operator — tidak dieksekusi sistem.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Berlaku Mulai</label>
                                <input type="date" name="berlaku_mulai" class="form-control @error('berlaku_mulai') is-invalid @enderror"
                                       value="{{ old('berlaku_mulai', $pajak->berlaku_mulai ? \Carbon\Carbon::parse($pajak->berlaku_mulai)->format('Y-m-d') : '') }}">
                                @error('berlaku_mulai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="df-hint mt-1">Kosongkan bila berlaku sejak sekarang.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Berlaku Sampai</label>
                                <input type="date" name="berlaku_sampai" class="form-control @error('berlaku_sampai') is-invalid @enderror"
                                       value="{{ old('berlaku_sampai', $pajak->berlaku_sampai ? \Carbon\Carbon::parse($pajak->berlaku_sampai)->format('Y-m-d') : '') }}">
                                @error('berlaku_sampai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="df-hint mt-1">Kosongkan bila tanpa batas akhir.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-block">Status Tarif</label>
                                <div class="df-switch {{ $oldStatusAktif ? 'on' : '' }}" id="pjSwitchWrap">
                                    <div class="form-check form-switch d-flex align-items-center gap-2 ps-0 mb-1">
                                        <input class="form-check-input ms-0 flex-shrink-0" type="checkbox" role="switch" id="status_aktif"
                                               name="status_aktif" value="1" {{ $oldStatusAktif ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold small" for="status_aktif" style="cursor:pointer;">
                                            Aktif &amp; siap dipakai
                                        </label>
                                    </div>
                                    <div class="df-hint">Hanya tarif aktif yang muncul di pilihan pajak tagihan.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Aksi --}}
                <div class="df-card" style="--d:.23s;">
                    <div class="p-4 df-actions">
                        <a href="{{ route('master-pajak.index') }}" class="df-btn df-btn-ghost text-decoration-none">Batal</a>
                        <button type="submit" class="df-btn df-btn-primary js-df-submit">
                            <i class="bi bi-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>

            {{-- ════════ PRATINJAU + SIMULATOR ════════ --}}
            <div class="col-lg-4">
                <div class="df-preview">
                    <div class="df-doc">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-percent fs-4" style="color:#a5b4fc;"></i>
                                <span class="fw-bold" style="letter-spacing:.08em; font-size:.78rem;">PRATINJAU TARIF</span>
                            </div>
                            <span class="df-doc-badge" id="pvStatus"><i class="bi bi-check-circle-fill"></i> AKTIF</span>
                        </div>

                        <div class="df-doc-lbl">Kode · Jenis Pajak</div>
                        <div class="df-doc-val mono mb-1" id="pvKode">{{ $pajak->kode_pajak }}</div>
                        <div class="df-doc-val mb-2" id="pvJenis" style="color:rgba(255,255,255,.8); font-weight:600;">{{ $pajak->jenis_pajak }}</div>

                        <div class="pj-persen-badge" id="pvPersen">0%</div>

                        <div class="df-doc-sep"></div>

                        <div class="df-doc-lbl mb-1">Kode Billing (SSP)</div>
                        <div class="pj-ssp mb-2">
                            <div class="cell"><div class="v" id="pvKap">——</div><div class="l">KAP</div></div>
                            <div class="cell"><div class="v" id="pvKjs">——</div><div class="l">KJS</div></div>
                        </div>

                        <div class="df-doc-sep"></div>

                        <div class="df-doc-lbl mb-1">Simulasi Potongan</div>
                        <div class="pj-sim">
                            <div class="df-doc-lbl mb-1" style="letter-spacing:.06em;">Contoh DPP (Dasar Pengenaan)</div>
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text fw-bold border-0" style="background:rgba(255,255,255,.75); border-radius:.55rem 0 0 .55rem;">Rp</span>
                                <input type="text" inputmode="numeric" id="pjSimDpp" class="form-control" value="10.000.000" autocomplete="off">
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="df-doc-lbl">Potongan pajak</span>
                                <span class="pj-sim-hasil" id="pjSimHasil">− Rp 0</span>
                            </div>
                            <div class="df-doc-lbl mt-1" style="text-transform:none; letter-spacing:0;">Dibulatkan ke atas ke ratusan, mengikuti kalkulator pajak aplikasi.</div>
                        </div>
                    </div>
                    <div class="df-hint mt-3 text-center">
                        <i class="bi bi-magic me-1"></i>Pratinjau &amp; simulasi menghitung otomatis saat Anda mengetik.
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
    var form = document.getElementById('pajakEditForm');
    if (!form) return;

    var fmt = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });
    var fmtPct = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 });

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
        });
    }
    function parseDigits(v) {
        return parseInt(String(v || '').replace(/[^\d]/g, ''), 10) || 0;
    }

    function syncPreview() {
        var kode = document.getElementById('pjKode').value.trim().toUpperCase();
        document.getElementById('pvKode').innerHTML = kode
            ? esc(kode)
            : '<span class="text-white-50">Belum diisi</span><span class="df-cursor"></span>';

        var jenis = document.getElementById('pjJenis').value.trim();
        document.getElementById('pvJenis').textContent = jenis || '—';

        var persen = parseFloat(document.getElementById('pjPersen').value) || 0;
        document.getElementById('pvPersen').textContent = fmtPct.format(persen) + '%';

        document.getElementById('pvKap').textContent = document.getElementById('pjKap').value.trim() || '——';
        document.getElementById('pvKjs').textContent = document.getElementById('pjKjs').value.trim() || '——';

        var aktif = document.getElementById('status_aktif').checked;
        var badge = document.getElementById('pvStatus');
        badge.classList.toggle('off', !aktif);
        badge.innerHTML = aktif
            ? '<i class="bi bi-check-circle-fill"></i> AKTIF'
            : '<i class="bi bi-pause-circle"></i> NONAKTIF';
        document.getElementById('pjSwitchWrap').classList.toggle('on', aktif);

        // Simulasi: potongan = ROUNDUP(DPP × tarif, ke ratusan) — konsisten kalkulator pajak.
        var dpp = parseDigits(document.getElementById('pjSimDpp').value);
        var potongan = Math.ceil((dpp * persen / 100) / 100) * 100;
        var hasil = document.getElementById('pjSimHasil');
        hasil.textContent = '− Rp ' + fmt.format(potongan);
        hasil.classList.remove('pop');
        void hasil.offsetWidth;
        hasil.classList.add('pop');
    }

    ['pjKode', 'pjJenis', 'pjPersen', 'pjKap', 'pjKjs'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', syncPreview);
    });
    document.getElementById('status_aktif').addEventListener('change', syncPreview);

    var simDpp = document.getElementById('pjSimDpp');
    simDpp.addEventListener('input', function () {
        var n = parseDigits(simDpp.value);
        simDpp.value = n ? fmt.format(n) : '';
        syncPreview();
    });

    syncPreview();

    form.addEventListener('submit', function (e) {
        var btn = e.submitter && e.submitter.classList.contains('js-df-submit') ? e.submitter : null;
        form.querySelectorAll('.js-df-submit').forEach(function (b) { b.disabled = true; });
        if (btn) {
            btn.style.minWidth = Math.ceil(btn.getBoundingClientRect().width) + 'px';
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan…';
        }
    });
});
</script>
@endpush
