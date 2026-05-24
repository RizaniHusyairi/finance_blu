@extends('layouts.app')

@section('title', 'Detail Pegawai')

@push('css')
    @include('admin._partials.styles')
@endpush

@section('content')
    <x-page-title title="Data Pegawai" subtitle="Detail" />

    <div class="admin-hero d-flex align-items-center gap-3 mb-4"
         style="background: linear-gradient(135deg, #0ea5e9, #6366f1, #8b5cf6);">
        <div class="avatar-circle" style="width:64px; height:64px; font-size:1.5rem; background: rgba(255,255,255,.18);">
            {{ strtoupper(mb_substr($pegawai->nama_lengkap, 0, 1)) }}
        </div>
        <div class="flex-grow-1">
            <h1>{{ $pegawai->nama_lengkap }}</h1>
            <p>{{ $pegawai->jabatan ?: '—' }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.pegawai.edit', $pegawai) }}" class="btn btn-light fw-semibold">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="{{ route('admin.pegawai.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    @include('admin._partials.flash')

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="surface-card p-4 mb-3">
                <h6 class="fw-bold text-uppercase text-muted mb-3" style="letter-spacing: .08em; font-size: .75rem;">
                    Identitas
                </h6>
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">NIP</dt><dd class="col-sm-8 font-monospace">{{ $pegawai->nip ?: '—' }}</dd>
                    <dt class="col-sm-4 text-muted">NIK</dt><dd class="col-sm-8 font-monospace">{{ $pegawai->nik ?: '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Nomor HP</dt><dd class="col-sm-8">{{ $pegawai->nomor_hp ?: '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Jabatan</dt><dd class="col-sm-8">{{ $pegawai->jabatan ?: '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Status</dt>
                    <dd class="col-sm-8">
                        @if ($pegawai->status_aktif)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Nonaktif</span>
                        @endif
                    </dd>
                </dl>
            </div>

            <div class="surface-card p-4">
                <h6 class="fw-bold text-uppercase text-muted mb-3" style="letter-spacing: .08em; font-size: .75rem;">
                    Bank
                </h6>
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Nama Bank</dt><dd class="col-sm-8">{{ $pegawai->nama_bank ?: '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Nomor Rekening</dt><dd class="col-sm-8 font-monospace">{{ $pegawai->nomor_rekening ?: '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Atas Nama</dt><dd class="col-sm-8">{{ $pegawai->nama_rekening ?: '—' }}</dd>
                </dl>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="surface-card p-4 mb-3">
                <h6 class="fw-bold text-uppercase text-muted mb-3" style="letter-spacing: .08em; font-size: .75rem;">
                    Akun Tertaut
                </h6>
                @if ($pegawai->user)
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="avatar-circle">{{ strtoupper(mb_substr($pegawai->user->email, 0, 1)) }}</div>
                        <div>
                            <div class="fw-semibold">{{ $pegawai->user->email }}</div>
                            <small class="text-muted">
                                @foreach ($pegawai->user->roles as $r)
                                    <span class="role-chip">{{ $r->name }}</span>
                                @endforeach
                            </small>
                        </div>
                    </div>
                    <a href="{{ route('admin.users.show', $pegawai->user) }}" class="btn btn-light text-primary border w-100">
                        <i class="bi bi-arrow-up-right-square me-1"></i> Buka Akun
                    </a>
                @else
                    <p class="text-muted">Belum ada akun.</p>
                    <a href="{{ route('admin.users.create') }}" class="btn btn-gradient w-100">
                        <i class="bi bi-plus-lg me-1"></i> Buat Akun
                    </a>
                @endif
            </div>

            <div class="surface-card p-4">
                <h6 class="fw-bold text-uppercase text-muted mb-3" style="letter-spacing: .08em; font-size: .75rem;">
                    Aksi
                </h6>
                <form method="POST" action="{{ route('admin.pegawai.toggle', $pegawai) }}" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-light border w-100">
                        <i class="bi {{ $pegawai->status_aktif ? 'bi-toggle-off' : 'bi-toggle-on' }} me-1"></i>
                        {{ $pegawai->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.pegawai.destroy', $pegawai) }}"
                      onsubmit="return confirm('Hapus pegawai ini?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-light text-danger border w-100">
                        <i class="bi bi-trash me-1"></i> Hapus Pegawai
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
