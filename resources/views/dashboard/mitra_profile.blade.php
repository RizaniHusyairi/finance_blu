@extends('layouts.app')
@section('title', 'Profil Mitra')

@push('css')
    @include('dashboard.partials.mitra-ui')
@endpush

@section('content')
<div class="mp-hero mb-4">
    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
        <div class="d-flex align-items-start gap-3">
            <span class="mp-hero-icon"><i class="bi bi-person-vcard fs-4"></i></span>
            <div>
                <h4 class="mb-1 fw-bold text-white">Profil Mitra</h4>
                <p class="mb-0 small fw-semibold text-white-50">{{ $mitra->nama_mitra }}</p>
            </div>
        </div>
        <a href="{{ route('mitra.dashboard') }}" class="btn btn-light fw-bold text-primary shadow-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="mp-card">
            <div class="mp-card-header">
                <div class="mp-card-title">
                    <span class="mp-card-icon"><i class="bi bi-building"></i></span>
                    <div>
                        <h6>Informasi Mitra</h6>
                        <small>Data perusahaan bersifat readonly dari pengelola jasa.</small>
                    </div>
                </div>
                <span class="mp-soft-badge {{ $mitra->status_aktif ? 'success' : 'muted' }}">{{ $mitra->status_aktif ? 'Aktif' : 'Nonaktif' }}</span>
            </div>
            <div class="card-body p-4">
                <div class="mp-info-grid">
                    <div class="mp-info-item"><div class="mp-info-label">Kode Mitra</div><div class="mp-info-value">{{ $mitra->kode_mitra ?: '-' }}</div></div>
                    <div class="mp-info-item"><div class="mp-info-label">Jenis Mitra</div><div class="mp-info-value">{{ str_replace('_', ' ', $mitra->jenis_mitra ?: '-') }}</div></div>
                    <div class="mp-info-item"><div class="mp-info-label">Nama Mitra</div><div class="mp-info-value">{{ $mitra->nama_mitra }}</div></div>
                    <div class="mp-info-item"><div class="mp-info-label">NPWP</div><div class="mp-info-value">{{ $mitra->npwp ?: '-' }}</div></div>
                    <div class="mp-info-item"><div class="mp-info-label">Email Mitra</div><div class="mp-info-value">{{ $mitra->email ?: '-' }}</div></div>
                    <div class="mp-info-item"><div class="mp-info-label">No HP/WA</div><div class="mp-info-value">{{ $mitra->no_telepon ?: '-' }}</div></div>
                    <div class="mp-info-item"><div class="mp-info-label">Penanggung Jawab</div><div class="mp-info-value">{{ $mitra->nama_penanggung_jawab ?: '-' }}</div></div>
                    <div class="mp-info-item"><div class="mp-info-label">Jabatan PJ</div><div class="mp-info-value">{{ $mitra->jabatan_penanggung_jawab ?: '-' }}</div></div>
                    <div class="mp-info-item" style="grid-column:1/-1;"><div class="mp-info-label">Alamat</div><div class="mp-info-value">{{ $mitra->alamat ?: '-' }}</div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="mp-card mb-4">
            <div class="mp-card-header">
                <div class="mp-card-title">
                    <span class="mp-card-icon"><i class="bi bi-person-lock"></i></span>
                    <div>
                        <h6>Akun Pengguna</h6>
                        <small>Akses login portal mitra.</small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="small text-muted fw-bold">Email Login</div>
                    <div class="fw-semibold">{{ $user->email }}</div>
                </div>
                <div class="alert alert-light border small mb-0">
                    Data mitra bersifat readonly. Jika terdapat perubahan data perusahaan, hubungi Admin Jasa/Super Admin Jasa.
                </div>
            </div>
        </div>

        <div class="mp-card">
            <div class="mp-card-header">
                <div class="mp-card-title">
                    <span class="mp-card-icon"><i class="bi bi-shield-lock"></i></span>
                    <div>
                        <h6>Ubah Password</h6>
                        <small>Gunakan minimal 8 karakter.</small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('mitra.profile.password.update') }}" method="POST" class="mp-form">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-bold">Password Lama <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required autocomplete="current-password">
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Password Baru <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Minimal 8 karakter.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold" onclick="return confirm('Ubah password akun Anda?')">
                        <i class="bi bi-shield-lock me-1"></i> Simpan Password Baru
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
