@extends('layouts.app')
@section('title', 'Setup Pembukuan')
@include('pembukuan.partials.styles')

@section('content')
<div class="container-fluid bku-page">

    <div class="bku-hero">
        <div class="bku-hero__info">
            <div class="bku-hero__eyebrow">Pembukuan</div>
            <h3 class="bku-hero__title">Setup Pembukuan</h3>
            <span class="bku-hero__rek"><i class="bi bi-building"></i> Identitas Satker &amp; Saldo Awal</span>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card book-card mb-3">
        <div class="card-header"><h6 class="mb-0 fw-bold"><i class="bi bi-building text-primary me-1"></i> Identitas Satker (Kop Dokumen)</h6></div>
        <div class="card-body">
            <form method="POST" action="{{ route('pembukuan.setup.update') }}" class="row g-3">
                @csrf
                @php
                    $fields = [
                        'nama_satker' => 'Nama Satuan Kerja', 'kode_satker' => 'Kode Satker',
                        'nama_kl' => 'Kementerian/Lembaga', 'kode_kl' => 'Kode K/L',
                        'nama_unit_org' => 'Unit Organisasi', 'kode_unit_org' => 'Kode Unit Org',
                        'propinsi' => 'Propinsi/Kab/Kota',
                        'nomor_dipa' => 'Nomor DIPA', 'tanggal_dipa' => 'Tanggal DIPA',
                        'nama_kppn' => 'Nama KPPN', 'kode_kppn' => 'Kode KPPN',
                        'tahun_anggaran' => 'Tahun Anggaran',
                        'nama_bendahara_penerimaan' => 'Bendahara Penerimaan',
                        'nama_bendahara_pengeluaran' => 'Bendahara Pengeluaran',
                        'nama_kpa' => 'KPA',
                    ];
                @endphp
                @foreach($fields as $name => $label)
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">{{ $label }}</label>
                        @if($name === 'tanggal_dipa')
                            <input type="date" name="{{ $name }}" value="{{ old($name, optional($setup->tanggal_dipa)->toDateString()) }}" class="form-control form-control-sm @error($name) is-invalid @enderror">
                        @elseif($name === 'tahun_anggaran')
                            <input type="number" name="{{ $name }}" value="{{ old($name, $setup->tahun_anggaran) }}" class="form-control form-control-sm @error($name) is-invalid @enderror">
                        @else
                            <input type="text" name="{{ $name }}" value="{{ old($name, $setup->{$name}) }}" class="form-control form-control-sm @error($name) is-invalid @enderror">
                        @endif
                        @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endforeach
                <div class="col-12"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button></div>
            </form>
        </div>
    </div>

    <div class="card book-card">
        <div class="card-header"><h6 class="mb-0 fw-bold"><i class="bi bi-cash-stack text-success me-1"></i> Saldo Awal per Rekening</h6></div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Saldo BKU pada <b>tanggal mulai pembukuan</b> (go-live) tiap rekening. Cukup diinput
                <b>sekali</b> — bulan berikutnya saldo awal terbawa otomatis dari saldo akhir bulan
                sebelumnya. Menyimpan akan <b>menghitung ulang saldo berjalan</b>. Peran buku mengikuti
                jenis rekening (Penerimaan/Pengeluaran). Anda juga bisa <b>mengubah Nama Bank, Nomor &amp;
                Atas Nama rekening</b> langsung di tabel ini.
            </p>
            @if(session('error'))<div class="alert alert-warning py-2 small">{{ session('error') }}</div>@endif
            <form method="POST" action="{{ route('pembukuan.setup.saldo-awal') }}">
                @csrf
                <div class="table-responsive">
                    <table class="table table-sm align-middle book-table">
                        <thead>
                            <tr><th style="width:240px;">Rekening</th><th style="width:240px;">Atas Nama (Nama Rekening)</th><th>Peran / Buku</th><th style="width:150px;">Tanggal Berlaku</th><th style="width:180px;" class="text-end">Saldo Awal (Rp)</th></tr>
                        </thead>
                        <tbody>
                            @forelse($rekeningSaldo as $r)
                                <tr>
                                    <td>
                                        <input type="text" name="saldo[{{ $r->id }}][nama_bank]" value="{{ $r->nama_bank }}" maxlength="100" class="form-control form-control-sm mb-1" placeholder="Nama bank">
                                        <input type="text" name="saldo[{{ $r->id }}][nomor_rekening]" value="{{ $r->nomor_rekening }}" maxlength="50" class="form-control form-control-sm" placeholder="Nomor rekening">
                                    </td>
                                    <td><input type="text" name="saldo[{{ $r->id }}][nama_rekening]" value="{{ $r->nama_rekening }}" maxlength="150" class="form-control form-control-sm" placeholder="Atas nama rekening"></td>
                                    <td><span class="badge {{ $r->peran_bku === 'PENERIMAAN' ? 'bg-success' : 'bg-primary' }}">{{ $r->peran_bku }}</span> <span class="text-muted small">/ BKU</span></td>
                                    <td><input type="date" name="saldo[{{ $r->id }}][tanggal]" value="{{ $r->saldo_awal_tanggal }}" class="form-control form-control-sm"></td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" inputmode="numeric" autocomplete="off"
                                                   class="form-control text-end js-rupiah"
                                                   data-target="sa_nom_{{ $r->id }}"
                                                   value="{{ ($r->saldo_awal_nominal !== null && $r->saldo_awal_nominal !== '') ? number_format((float) $r->saldo_awal_nominal, 0, ',', '.') : '' }}"
                                                   placeholder="0">
                                        </div>
                                        <input type="hidden" name="saldo[{{ $r->id }}][nominal]" id="sa_nom_{{ $r->id }}" value="{{ $r->saldo_awal_nominal !== null ? (int) $r->saldo_awal_nominal : '' }}">
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">Belum ada rekening Penerimaan/Pengeluaran aktif.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($rekeningSaldo->isNotEmpty())
                    <button class="btn btn-success btn-sm"><i class="bi bi-save me-1"></i> Simpan Rekening &amp; Saldo Awal</button>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    // Input saldo awal berformat rupiah (pemisah ribuan); nilai mentah disimpan
    // ke input hidden agar validasi numeric di server tetap jalan.
    (function () {
        'use strict';
        var fmt = new Intl.NumberFormat('id-ID');
        document.querySelectorAll('.js-rupiah').forEach(function (display) {
            var hidden = document.getElementById(display.dataset.target);
            if (!hidden) return;
            function sync() {
                var digits = display.value.replace(/\D+/g, '').replace(/^0+(?=\d)/, '');
                var before = display.value.slice(0, display.selectionStart || 0).replace(/\D+/g, '').length;
                display.value = digits === '' ? '' : fmt.format(parseInt(digits, 10));
                hidden.value = digits;
                var pos = 0, seen = 0;
                while (pos < display.value.length && seen < before) { if (/\d/.test(display.value[pos])) seen++; pos++; }
                display.setSelectionRange(pos, pos);
            }
            display.addEventListener('input', sync);
            if (display.value !== '') {
                var d = display.value.replace(/\D+/g, '');
                display.value = d === '' ? '' : fmt.format(parseInt(d, 10));
                hidden.value = d;
            }
        });
    })();
</script>
@endpush
