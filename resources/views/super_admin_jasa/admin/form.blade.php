@extends('layouts.app')
@section('title', $admin->exists ? 'Edit Admin Jasa' : 'Tambah Admin Jasa')

@include('super_admin_jasa.partials.form-style')

@section('content')
<div class="jasa-form-hero mb-4 px-4 py-4">
    <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center position-relative">
        <div class="d-flex gap-3 align-items-start">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-white text-primary shadow-sm" style="width:44px;height:44px;">
                <i class="bi bi-person-badge fs-5"></i>
            </span>
            <div>
                <h4 class="mb-1 fw-black">{{ $admin->exists ? 'Edit Admin Jasa' : 'Tambah Admin Jasa' }}</h4>
                <p class="mb-0 fw-semibold small">Admin Jasa mengelola layanan, mitra terkait, dan pembuatan tagihan sesuai scope tugas.</p>
            </div>
        </div>
        <a href="{{ route('jasa.admin.index') }}" class="btn btn-light text-primary fw-bold shadow-sm jasa-icon-btn" title="Kembali" aria-label="Kembali">
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

<form method="POST" action="{{ $admin->exists ? route('jasa.admin.update', $admin) : route('jasa.admin.store') }}">
    @csrf
    @if($admin->exists)
        @method('PUT')
    @endif

    <div class="jasa-form-card">
        <div class="jasa-form-section">
            <div class="jasa-section-title">
                <span class="jasa-section-icon"><i class="bi bi-person-vcard"></i></span>
                <div>
                    <h6>Profil Pegawai</h6>
                    <p>Identitas admin yang akan diberi akses pada modul jasa.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-8">
                    <label class="form-label">Nama Admin Jasa <span class="text-danger">*</span></label>
                    <input type="text" name="nama_lengkap" class="form-control" value="{{ old('nama_lengkap', $pegawai->nama_lengkap) }}" required placeholder="Nama lengkap admin">
                </div>
                <div class="col-lg-4">
                    <label class="form-label">NIP</label>
                    <input type="text" name="nip" class="form-control" value="{{ old('nip', $pegawai->nip) }}" placeholder="NIP pegawai">
                </div>
                <div class="col-lg-4">
                    <label class="form-label">Jabatan</label>
                    <input type="text" name="jabatan" class="form-control" value="{{ old('jabatan', $pegawai->jabatan ?: 'Admin Jasa') }}" placeholder="Admin Jasa">
                </div>
                <div class="col-lg-4">
                    <label class="form-label">NPWP</label>
                    <input type="text" name="npwp" class="form-control" value="{{ old('npwp', $pegawai->npwp) }}" placeholder="NPWP">
                </div>
                <div class="col-lg-4">
                    <label class="form-label">Status Profil</label>
                    <select name="status_aktif" class="form-select">
                        <option value="1" @selected(old('status_aktif', $pegawai->status_aktif ?? true))>Aktif</option>
                        <option value="0" @selected(old('status_aktif', $admin->exists ? !($pegawai->status_aktif ?? true) : false))>Nonaktif</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="jasa-form-section">
            <div class="jasa-section-title">
                <span class="jasa-section-icon"><i class="bi bi-shield-lock"></i></span>
                <div>
                    <h6>Akun Login</h6>
                    <p>Email dan password untuk masuk sebagai Admin Jasa.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label">Email Login <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $admin->email) }}" required placeholder="admin@domain.go.id">
                </div>
                <div class="col-lg-6">
                    <label class="form-label">{{ $admin->exists ? 'Password Baru' : 'Password Awal' }} @if(!$admin->exists)<span class="text-danger">*</span>@endif</label>
                    <input type="password" name="password" class="form-control" {{ $admin->exists ? '' : 'required' }} placeholder="{{ $admin->exists ? 'Kosongkan jika tidak diubah' : 'Masukkan password awal' }}">
                    @if($admin->exists)
                        <div class="form-text">Kosongkan jika password tidak diubah.</div>
                    @endif
                </div>
                <div class="col-12">
                    <div class="jasa-helper-panel">
                        <strong>Scope layanan</strong>
                        <div class="small mt-1">Setelah akun dibuat, atur layanan yang dikelola admin melalui halaman detail Admin Jasa.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="jasa-action-footer">
            <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                <a href="{{ route('jasa.admin.index') }}" class="btn btn-light fw-bold text-secondary border px-4">Batal</a>
                <button class="btn btn-primary fw-bold px-4" type="submit">
                    <i class="bi bi-save me-1"></i>Simpan Admin Jasa
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
