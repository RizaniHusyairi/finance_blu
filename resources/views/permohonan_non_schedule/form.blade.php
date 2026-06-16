@extends('layouts.app')
@section('title', ($item->exists ? 'Ubah' : 'Ajukan') . ' Permohonan Non-Schedule')

@section('content')
@include('super_admin_jasa.laporan._styles')
@php
    $jenisOpt = \App\Models\PermohonanNonSchedule::JENIS_LABEL;
    $action = $item->exists
        ? route('permohonan-non-schedule.update', $item->id)
        : route('permohonan-non-schedule.store');
@endphp

<div class="sa-report-page">
    <div class="sa-report-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="small text-uppercase fw-bold" style="letter-spacing:.08em;color:#fbbf24;">AMC &middot; Permohonan Penerbangan</div>
            <h4 class="fw-bold mb-1"><i class="bi bi-airplane-engines me-2"></i>{{ $item->exists ? 'Ubah Permohonan' : 'Ajukan Permohonan Non-Schedule' }}</h4>
            <p class="mb-0 small">Unggah surat permohonan resmi & isi data penerbangan untuk direview Admin Jasa.</p>
        </div>
        <a href="{{ route('permohonan-non-schedule.index') }}" class="btn btn-light border fw-bold"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0">
            <ul class="mb-0 small">
                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="card border-0 shadow-sm">
        @csrf
        @if($item->exists) @method('PUT') @endif
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Mitra <span class="text-danger">*</span></label>
                    <select name="mitra_jasa_id" class="form-select" required>
                        <option value="">— Pilih mitra —</option>
                        @foreach($mitraOptions as $m)
                            <option value="{{ $m->id }}" @selected(old('mitra_jasa_id', $item->mitra_jasa_id) == $m->id)>{{ $m->nama_mitra }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Nomor Surat <span class="text-danger">*</span></label>
                    <input type="text" name="nomor_surat" class="form-control" value="{{ old('nomor_surat', $item->nomor_surat) }}" maxlength="100" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Tanggal Surat <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_surat" class="form-control" value="{{ old('tanggal_surat', optional($item->tanggal_surat)->toDateString()) }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Jenis Penerbangan <span class="text-danger">*</span></label>
                    <select name="jenis_penerbangan" class="form-select" required>
                        <option value="">— Pilih jenis —</option>
                        @foreach($jenisOpt as $v => $l)
                            <option value="{{ $v }}" @selected(old('jenis_penerbangan', $item->jenis_penerbangan) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Tanggal Penerbangan Dari <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_penerbangan_dari" class="form-control" value="{{ old('tanggal_penerbangan_dari', optional($item->tanggal_penerbangan_dari)->toDateString()) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Tanggal Penerbangan Sampai</label>
                    <input type="date" name="tanggal_penerbangan_sampai" class="form-control" value="{{ old('tanggal_penerbangan_sampai', optional($item->tanggal_penerbangan_sampai)->toDateString()) }}">
                    <div class="form-text small">Isi bila rentang multi-hari, kosongkan bila 1 hari.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Nomor Penerbangan</label>
                    <input type="text" name="nomor_penerbangan" class="form-control" value="{{ old('nomor_penerbangan', $item->nomor_penerbangan) }}" maxlength="50">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Registrasi Pesawat</label>
                    <input type="text" name="registrasi_pesawat" class="form-control" value="{{ old('registrasi_pesawat', $item->registrasi_pesawat) }}" maxlength="50" placeholder="PK-XXX">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Rute</label>
                    <input type="text" name="rute" class="form-control" value="{{ old('rute', $item->rute) }}" maxlength="150" placeholder="CGK – DJB – CGK">
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold">Keterangan</label>
                    <textarea name="keterangan" rows="3" class="form-control" maxlength="2000">{{ old('keterangan', $item->keterangan) }}</textarea>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-bold">Surat Permohonan {{ $item->exists ? '(kosongkan jika tidak diganti)' : '*' }}</label>
                    <input type="file" name="file_surat" class="form-control" accept=".pdf,.jpg,.jpeg,.png" {{ $item->exists ? '' : 'required' }}>
                    <div class="form-text small">PDF/JPG/PNG, maks 5MB.</div>
                    @if($item->exists && $item->file_surat)
                        <div class="small mt-2">
                            <i class="bi bi-paperclip"></i>
                            <a href="{{ route('permohonan-non-schedule.file', $item->id) }}" target="_blank">File terlampir</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-footer bg-light border-0 d-flex gap-2 justify-content-end">
            <a href="{{ route('permohonan-non-schedule.index') }}" class="btn btn-light border">Batal</a>
            <button class="btn btn-primary fw-bold"><i class="bi bi-check2-circle me-1"></i>Simpan Permohonan</button>
        </div>
    </form>
</div>
@endsection
