@extends('layouts.app')
@section('title')
    Tambah Data Perjaldin
@endsection

@push('css')
@include('perjaldins.partials._form-styles')
@endpush


@section('content')
<x-page-title title="Manajemen Perjaldin" subtitle="Tambah Data" />

{{-- HERO --}}
<div class="perj-hero">
    <i class="bi bi-airplane-engines plane-illust d-none d-md-block"></i>
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div class="flex-grow-1">
            <span class="hero-tag"><i class="bi bi-stars"></i> Form Pengajuan Baru</span>
            <h2><i class="bi bi-airplane me-2"></i>Perjalanan Dinas</h2>
            <p>Lengkapi data dokumen, verifikator, dan daftar nominatif peserta untuk pengajuan perjaldin.</p>
        </div>
        <a href="{{ route('perjaldins.index') }}" class="btn-back-perj">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>
</div>

{{-- STEPPER --}}
<div class="form-stepper" id="formStepper">
    <div class="stepper-progress-wrap">
        <div class="stepper-progress-fill" id="progressFill"></div>
    </div>
    <div class="stepper-list" id="stepperList">
        <div class="stepper-item" data-step="dokumen">
            <span class="dot"><i class="bi bi-file-earmark-text"></i></span>
            <span>Dokumen</span>
        </div>
        <i class="bi bi-chevron-right text-muted small"></i>
        <div class="stepper-item" data-step="verifikator">
            <span class="dot"><i class="bi bi-person-check"></i></span>
            <span>Verifikator</span>
        </div>
        <i class="bi bi-chevron-right text-muted small"></i>
        <div class="stepper-item" data-step="peserta">
            <span class="dot"><i class="bi bi-people"></i></span>
            <span>Peserta</span>
        </div>
        <span class="ms-auto small fw-bold text-muted" id="progressPct">0% lengkap</span>
    </div>
</div>

{{-- VALIDATION ERRORS --}}
@if ($errors->any())
    <div class="alert-modern-error">
        <div class="alert-title">
            <i class="bi bi-exclamation-octagon-fill"></i>
            Terdapat kesalahan pada formulir
        </div>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('perjaldins.store') }}" method="POST" enctype="multipart/form-data" id="perjaldinForm">
    @csrf

    {{-- ============ A: DOKUMEN ============ --}}
    <div class="sec-card" data-section="dokumen">
        <div class="sec-head">
            <div class="head-left">
                <span class="sec-icon icon-info"><i class="bi bi-file-earmark-text-fill"></i></span>
                <div>
                    <h6>Informasi Dokumen & Anggaran</h6>
                    <small>Header dokumen perjalanan dinas dan periode</small>
                </div>
            </div>
            <span class="sec-letter">Step A</span>
        </div>
        <div class="sec-body">
            <div class="row g-3">
                <div class="col-md-5 col-lg-4">
                    <label class="form-label-modern">Nomor Perjalanan Dinas <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text font-monospace fw-bold">KU.201/</span>
                        <input type="text" name="nomor_urut" id="inp_nomor_urut"
                               class="form-control modern text-primary fw-bold font-monospace text-center @error('nomor_urut') is-invalid @enderror"
                               inputmode="numeric" pattern="[0-9]{1,4}" maxlength="4" required
                               value="{{ old('nomor_urut', str_pad($nextUrut, 4, '0', STR_PAD_LEFT)) }}"
                               data-ku-check="{{ route('ku-numbers.check') }}"
                               data-ku-key="KU_PERJALDIN"
                               data-ku-tahun="{{ date('Y') }}">
                        <span class="input-group-text font-monospace fw-bold">/APTP/{{ date('Y') }}</span>
                    </div>
                    @error('nomor_urut')
                        <div class="text-danger small mt-1 fw-semibold">{{ $message }}</div>
                    @enderror
                    <small class="text-muted d-block mt-1" id="nomorUrutStatus"><i class="bi bi-pencil-square me-1"></i>Isi nomor urut 4 digit — nomor yang sudah pernah digunakan akan ditolak.</small>
                </div>
                <div class="col-md-7 col-lg-6">
                    <label class="form-label-modern">Uraian / Judul Perjalanan <span class="text-danger">*</span></label>
                    <input type="text" name="deskripsi" id="inp_deskripsi" class="form-control modern" placeholder="Contoh: Rapat Koordinasi Anggaran..." required value="{{ old('deskripsi') }}">
                </div>

                {{-- Periode & TTD memakai nilai default otomatis (tidak ditampilkan di form). --}}
                <input type="hidden" name="periode_bulan" id="inp_bulan" value="{{ old('periode_bulan', date('n')) }}">
                <input type="hidden" name="periode_tahun" id="inp_tahun" value="{{ old('periode_tahun', date('Y')) }}">
                <input type="hidden" name="kota_ttd" id="inp_kota" value="{{ old('kota_ttd', 'Samarinda') }}">
                <input type="hidden" name="tanggal_ttd" id="inp_tanggal" value="{{ old('tanggal_ttd', date('Y-m-d')) }}">

                <div class="col-md-12 col-lg-6">
                    <label class="form-label-modern"><i class="bi bi-credit-card-2-front"></i> Mekanisme Pembayaran <span class="text-danger">*</span></label>
                    <select name="mekanisme_pembayaran" class="form-select modern" required>
                        @foreach(\App\Enums\MekanismePembayaran::optionsFor('PERJALDIN') as $val => $lbl)
                            <option value="{{ $val }}" {{ old('mekanisme_pembayaran', \App\Enums\MekanismePembayaran::defaultFor('PERJALDIN')->value) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted small mt-1 d-block"><i class="bi bi-info-circle me-1"></i>LS - Pihak Ketiga: ditransfer langsung ke rekening peserta. LS - Via Bendahara: diteruskan melalui Bendahara Pengeluaran.</small>
                </div>
            </div>
        </div>
    </div>


    {{-- ============ B: VERIFIKATOR ============ --}}
    @php
        $kasubbagId = old('kasubbag_user_id', optional($kasubbagUser)->id);
        $kasubbagNama = old('kasubbag_nama_snapshot', optional($kasubbagUser)->name);
        $kasubbagNip = old('kasubbag_nip_snapshot', optional(optional($kasubbagUser)->pegawai)->nip);
    @endphp

    <div class="sec-card" data-section="verifikator">
        <div class="sec-head">
            <div class="head-left">
                <span class="sec-icon icon-primary"><i class="bi bi-pen-fill"></i></span>
                <div>
                    <h6>Verifikator Dokumen</h6>
                    <small>Pejabat yang akan memverifikasi & menandatangani</small>
                </div>
            </div>
            <span class="sec-letter">Step B</span>
        </div>
        <div class="sec-body">
            <div class="row g-3">
                {{-- PPK --}}
                <div class="col-md-6 col-lg-4">
                    <div class="verif-mini vm-primary">
                        <div class="vm-head">
                            <span class="vm-icon"><i class="bi bi-person-badge"></i></span>
                            <p class="vm-title">PPK</p>
                        </div>
                        <div class="field-mini">
                            <label>Pilih User</label>
                            <select name="ppk_user_id" class="form-select select2" id="ppkUserId">
                                <option value="">-- Pilih --</option>
                                @foreach($ppkUsers as $user)
                                    <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('ppk_user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="ppk_nama_snapshot" id="ppkNamaSnapshot" value="{{ old('ppk_nama_snapshot') }}">
                        <div class="field-mini">
                            <label>NIP <span class="text-danger">*</span></label>
                            <input type="text" name="ppk_nip_snapshot" id="ppkNipSnapshot" class="form-control" required value="{{ old('ppk_nip_snapshot') }}">
                        </div>
                    </div>
                </div>

                {{-- PPSPM --}}
                <div class="col-md-6 col-lg-4">
                    <div class="verif-mini vm-success">
                        <div class="vm-head">
                            <span class="vm-icon"><i class="bi bi-person-check"></i></span>
                            <p class="vm-title">PPSPM</p>
                        </div>
                        <div class="field-mini">
                            <label>Pilih User <span class="text-danger">*</span></label>
                            <select name="ppspm_user_id" class="form-select select2" id="ppspmUserId" required>
                                <option value="">-- Pilih --</option>
                                @foreach($ppspmUsers as $user)
                                    <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('ppspm_user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="ppspm_nama_snapshot" id="ppspmNamaSnapshot" value="{{ old('ppspm_nama_snapshot') }}">
                        <div class="field-mini">
                            <label>NIP</label>
                            <input type="text" name="ppspm_nip_snapshot" id="ppspmNipSnapshot" class="form-control" value="{{ old('ppspm_nip_snapshot') }}">
                        </div>
                    </div>
                </div>

                {{-- Koordinator Keuangan --}}
                <div class="col-md-6 col-lg-4">
                    <div class="verif-mini vm-info">
                        <div class="vm-head">
                            <span class="vm-icon"><i class="bi bi-clipboard-check"></i></span>
                            <p class="vm-title">Koordinator Keuangan</p>
                        </div>
                        <div class="field-mini">
                            <label>Pilih User <span class="text-danger">*</span></label>
                            <select name="koordinator_keuangan_user_id" class="form-select select2" id="koorKeuanganUserId" required>
                                <option value="">-- Pilih --</option>
                                @foreach($koorKeuanganUsers as $user)
                                    <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('koordinator_keuangan_user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="koordinator_keuangan_nama_snapshot" id="koorKeuanganNamaSnapshot" value="{{ old('koordinator_keuangan_nama_snapshot') }}">
                        <div class="field-mini">
                            <label>NIP</label>
                            <input type="text" name="koordinator_keuangan_nip_snapshot" id="koorKeuanganNipSnapshot" class="form-control" value="{{ old('koordinator_keuangan_nip_snapshot') }}">
                        </div>
                    </div>
                </div>

                {{-- Bendahara Penerimaan --}}
                <div class="col-md-6 col-lg-4">
                    <div class="verif-mini vm-warning">
                        <div class="vm-head">
                            <span class="vm-icon"><i class="bi bi-wallet2"></i></span>
                            <p class="vm-title">Bendahara Penerimaan</p>
                        </div>
                        <div class="field-mini">
                            <label>Pilih User <span class="text-danger">*</span></label>
                            <select name="bendahara_penerimaan_user_id" class="form-select select2" id="bendaharaPenerimaanUserId" required>
                                <option value="">-- Pilih --</option>
                                @foreach($bendaharaPenerimaanUsers as $user)
                                    <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('bendahara_penerimaan_user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="bendahara_penerimaan_nama_snapshot" id="bendaharaPenerimaanNamaSnapshot" value="{{ old('bendahara_penerimaan_nama_snapshot') }}">
                        <div class="field-mini">
                            <label>NIP</label>
                            <input type="text" name="bendahara_penerimaan_nip_snapshot" id="bendaharaPenerimaanNipSnapshot" class="form-control" value="{{ old('bendahara_penerimaan_nip_snapshot') }}">
                        </div>
                    </div>
                </div>

                {{-- Bendahara Pengeluaran --}}
                <div class="col-md-6 col-lg-4">
                    <div class="verif-mini vm-danger">
                        <div class="vm-head">
                            <span class="vm-icon"><i class="bi bi-cash-stack"></i></span>
                            <p class="vm-title">Bendahara Pengeluaran</p>
                        </div>
                        <div class="field-mini">
                            <label>Pilih User <span class="text-danger">*</span></label>
                            <select name="bendahara_pengeluaran_user_id" class="form-select select2" id="bendaharaUserId" required>
                                <option value="">-- Pilih --</option>
                                @foreach($bendaharaUsers as $user)
                                    <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('bendahara_pengeluaran_user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="bendahara_pengeluaran_nama_snapshot" id="bendaharaNamaSnapshot" value="{{ old('bendahara_pengeluaran_nama_snapshot') }}">
                        <div class="field-mini">
                            <label>NIP <span class="text-danger">*</span></label>
                            <input type="text" name="bendahara_pengeluaran_nip_snapshot" id="bendaharaNipSnapshot" class="form-control" required value="{{ old('bendahara_pengeluaran_nip_snapshot') }}">
                        </div>
                    </div>
                </div>

                {{-- Kasubbag (Auto) --}}
                <div class="col-md-6 col-lg-4">
                    <div class="verif-mini vm-violet is-auto {{ $kasubbagId ? 'is-filled' : '' }}">
                        <div class="vm-head">
                            <span class="vm-icon"><i class="bi bi-person-gear"></i></span>
                            <p class="vm-title">Kasubbag</p>
                            <span class="vm-auto-pill"><i class="bi bi-magic"></i> Otomatis</span>
                        </div>
                        <input type="hidden" name="kasubbag_user_id" value="{{ $kasubbagId }}">
                        <div class="field-mini">
                            <label>User Kasubbag</label>
                            <input type="text" class="form-control" readonly value="{{ $kasubbagNama ?: 'Belum ada user Kasubbag' }}">
                            <input type="hidden" name="kasubbag_nama_snapshot" value="{{ $kasubbagNama }}">
                        </div>
                        <div class="field-mini">
                            <label>NIP Kasubbag</label>
                            <input type="text" class="form-control" readonly value="{{ $kasubbagNip ?: '-' }}">
                            <input type="hidden" name="kasubbag_nip_snapshot" value="{{ $kasubbagNip }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ C: PESERTA ============ --}}
    <div class="sec-card" data-section="peserta">
        <div class="sec-head">
            <div class="head-left">
                <span class="sec-icon icon-success"><i class="bi bi-people-fill"></i></span>
                <div>
                    <h6>Daftar Nominatif Peserta</h6>
                    <small>Tambahkan peserta perjalanan dinas dan rincian biaya</small>
                </div>
            </div>
            <button type="button" class="btn-add-peserta btn-add-row-trigger">
                <i class="bi bi-plus-lg"></i> Tambah Peserta
            </button>
        </div>
        <div class="sec-body">
            <div class="peserta-summary">
                <div class="ps-icon"><i class="bi bi-people-fill"></i></div>
                <div class="ps-text">
                    <strong id="summaryCount">1</strong> peserta terdaftar dalam pengajuan ini
                </div>
                <div class="ps-grand">
                    <div class="ps-label">Grand Total</div>
                    <div class="ps-value" id="summaryGrandTotal">Rp 0</div>
                </div>
                <input type="hidden" id="grandTotal" name="total_bruto" value="0">
            </div>

            <div id="pesertaRepeater">
                @php
                    $oldPeserta = old('peserta', [0 => []]);
                    $isCreate = true;
                @endphp
                @foreach($oldPeserta as $index => $row)
                    @include('perjaldins.partials.peserta-card', ['index' => $index, 'row' => $row, 'masterProvinsi' => $masterProvinsi, 'masterPegawai' => $masterPegawai, 'isCreate' => true])
                @endforeach
            </div>

            <div class="text-center mt-3">
                <button type="button" class="btn-add-peserta btn-add-row-trigger">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Baris Peserta
                </button>
            </div>
        </div>
    </div>

    {{-- ============ STICKY SUBMIT BAR ============ --}}
    <div class="submit-bar-perj" id="submitBar">
        <div class="sb-status">
            <div class="sb-status-icon"><i class="bi bi-shield-exclamation" id="sbIcon"></i></div>
            <div>
                <h6 class="fw-bold mb-1 text-dark" id="sbTitle">Lengkapi formulir</h6>
                <p class="small text-muted mb-0" id="sbDesc">Isi semua field wajib pada Step A–C untuk siap submit.</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('perjaldins.index') }}" class="btn-cancel-perj">
                <i class="bi bi-x-circle me-1"></i> Batal
            </a>
            <button type="submit" class="btn-submit-perj">
                <i class="bi bi-cloud-upload-fill"></i> Simpan Pengajuan
            </button>
        </div>
    </div>
</form>
@endsection


@push('script')
@include('perjaldins.partials._form-scripts')
<script>
// Cek ketersediaan nomor urut KU saat diketik (validasi final tetap di server).
(function () {
    'use strict';
    var inp = document.getElementById('inp_nomor_urut');
    var status = document.getElementById('nomorUrutStatus');
    if (!inp || !status) return;

    var timer;
    function setStatus(cls, icon, text) {
        status.className = cls + ' small d-block mt-1';
        status.innerHTML = '<i class="bi ' + icon + ' me-1"></i>' + text;
    }

    function check() {
        var v = (inp.value || '').replace(/\D/g, '');
        inp.value = v;
        if (!v || parseInt(v, 10) < 1) {
            setStatus('text-danger fw-semibold', 'bi-exclamation-circle', 'Nomor urut wajib diisi (0001–9999).');
            return;
        }
        var url = inp.dataset.kuCheck
            + '?document_key=' + encodeURIComponent(inp.dataset.kuKey)
            + '&tahun=' + encodeURIComponent(inp.dataset.kuTahun)
            + '&start_number=' + encodeURIComponent(v);
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var padded = v.padStart(4, '0');
                if (d.exists) {
                    inp.classList.add('is-invalid');
                    setStatus('text-danger fw-semibold', 'bi-x-circle-fill', 'Nomor ' + padded + ' sudah pernah digunakan — pilih nomor lain.');
                } else {
                    inp.classList.remove('is-invalid');
                    setStatus('text-success fw-semibold', 'bi-check-circle-fill', 'Nomor ' + padded + ' tersedia.');
                }
            })
            .catch(function () {
                setStatus('text-muted', 'bi-info-circle', 'Tidak dapat mengecek nomor — validasi tetap dilakukan saat disimpan.');
            });
    }

    inp.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(check, 400); });
    check();
})();
</script>
@endpush
