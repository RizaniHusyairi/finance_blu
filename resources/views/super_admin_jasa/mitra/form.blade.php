@extends('layouts.app')
@section('title', $mitra->exists ? 'Edit Mitra Jasa' : 'Tambah Mitra Jasa')

@include('super_admin_jasa.partials.form-style')

@section('content')
<div class="jasa-form-hero mb-4 px-4 py-4">
    <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center position-relative">
        <div class="d-flex gap-3 align-items-start">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-white text-primary shadow-sm" style="width:44px;height:44px;">
                <i class="bi bi-buildings fs-5"></i>
            </span>
            <div>
                <h4 class="mb-1 fw-black">{{ $mitra->exists ? 'Edit Mitra Jasa' : 'Tambah Mitra Jasa' }}</h4>
                <p class="mb-0 fw-semibold small">Data mitra dipakai untuk kontrak, layanan aktif, tagihan, dan akun portal mitra.</p>
            </div>
        </div>
        <a href="{{ route('jasa.mitra.index') }}" class="btn btn-light text-primary fw-bold shadow-sm jasa-icon-btn" title="Kembali" aria-label="Kembali">
            <i class="bi bi-arrow-left"></i>
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger rounded-4">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $mitra->exists ? route('jasa.mitra.update', $mitra) : route('jasa.mitra.store') }}">
    @csrf
    @if($mitra->exists)
        @method('PUT')
    @endif

    <div class="jasa-form-card">
        <div class="jasa-form-section">
            <div class="jasa-section-title">
                <span class="jasa-section-icon"><i class="bi bi-card-text"></i></span>
                <div>
                    <h6>Identitas Mitra</h6>
                    <p>Kode, jenis, NPWP, dan nama resmi mitra jasa.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-3">
                    <label class="form-label">Kode Mitra</label>
                    <input type="text" name="kode_mitra" class="form-control" value="{{ old('kode_mitra', $mitra->kode_mitra) }}" placeholder="Contoh: 00011">
                </div>
                <div class="col-lg-3">
                    <label class="form-label">Jenis Mitra</label>
                    <select name="jenis_mitra" class="form-select">
                        <option value="">Pilih jenis mitra</option>
                        @foreach(['BADAN_USAHA', 'PERORANGAN', 'INSTANSI', 'MASKAPAI', 'TENANT', 'OPERATOR', 'LAINNYA'] as $jenis)
                            <option value="{{ $jenis }}" @selected(old('jenis_mitra', $mitra->jenis_mitra ?: 'BADAN_USAHA') === $jenis)>{{ str_replace('_', ' ', $jenis) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3">
                    <label class="form-label">NPWP</label>
                    <input type="text" name="npwp" class="form-control" value="{{ old('npwp', $mitra->npwp) }}" placeholder="Nomor NPWP">
                </div>
                <div class="col-lg-3">
                    <label class="form-label">Status</label>
                    <select name="status_aktif" class="form-select">
                        <option value="1" @selected(old('status_aktif', $mitra->status_aktif ?? true))>Aktif</option>
                        <option value="0" @selected(old('status_aktif', $mitra->exists ? !$mitra->status_aktif : false))>Nonaktif</option>
                    </select>
                </div>
                <div class="col-lg-8">
                    <label class="form-label">Nama Mitra <span class="text-danger">*</span></label>
                    <input type="text" name="nama_mitra" class="form-control" value="{{ old('nama_mitra', $mitra->nama_mitra) }}" required placeholder="Contoh: PT Kinan Jaya">
                </div>
                <div class="col-lg-4">
                    <div class="jasa-helper-panel">
                        <strong>Catatan</strong>
                        <div class="small mt-1">Status aktif menentukan apakah mitra dapat dipilih saat pembuatan tagihan jasa.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="jasa-form-section">
            <div class="jasa-section-title">
                <span class="jasa-section-icon"><i class="bi bi-person-lines-fill"></i></span>
                <div>
                    <h6>Kontak dan Penanggung Jawab</h6>
                    <p>Email digunakan sebagai login portal jika akun mitra dibuat.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $mitra->email) }}" placeholder="email@mitra.co.id">
                    <div class="form-text">Dipakai sebagai email login mitra jika akun dibuat.</div>
                </div>
                <div class="col-lg-6">
                    <label class="form-label">No HP/WhatsApp</label>
                    <input type="text" name="no_telepon" class="form-control" value="{{ old('no_telepon', $mitra->no_telepon) }}" placeholder="08xxxxxxxxxx">
                </div>
                <div class="col-lg-6">
                    <label class="form-label">Nama Penanggung Jawab</label>
                    <input type="text" name="nama_penanggung_jawab" class="form-control" value="{{ old('nama_penanggung_jawab', $mitra->nama_penanggung_jawab) }}" placeholder="Nama PIC">
                </div>
                <div class="col-lg-6">
                    <label class="form-label">Jabatan Penanggung Jawab</label>
                    <input type="text" name="jabatan_penanggung_jawab" class="form-control" value="{{ old('jabatan_penanggung_jawab', $mitra->jabatan_penanggung_jawab) }}" placeholder="Direktur, Manager, atau PIC">
                </div>
                <div class="col-12">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="3" placeholder="Alamat lengkap mitra">{{ old('alamat', $mitra->alamat) }}</textarea>
                </div>
            </div>
        </div>

        <div class="jasa-action-footer">
            <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                <a href="{{ route('jasa.mitra.index') }}" class="btn btn-light fw-bold text-secondary border px-4">Batal</a>
                <button class="btn btn-primary fw-bold px-4" type="submit">
                    <i class="bi bi-save me-1"></i>Simpan Mitra
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
