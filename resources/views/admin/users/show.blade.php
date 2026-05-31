@extends('layouts.app')

@section('title', 'Detail User')

@push('css')
    @include('admin._partials.styles')
@endpush

@section('content')
    <x-page-title title="Manajemen User" subtitle="Detail User" />

    @php
        $tipe = is_null($user->profilable_type) ? 'sistem'
            : (str_contains($user->profilable_type, 'MitraJasa') ? 'mitra' : 'pegawai');
        $initial = strtoupper(mb_substr($user->name ?? $user->email, 0, 1));
    @endphp

    <div class="admin-hero d-flex align-items-center gap-3 mb-4">
        <div class="avatar-circle" style="width:64px; height:64px; font-size:1.5rem; background: rgba(255,255,255,.18);">
            {{ $initial }}
        </div>
        <div class="flex-grow-1">
            <h1>{{ $user->name ?? 'Akun Sistem' }}</h1>
            <p>{{ $user->email }} • <span class="text-uppercase" style="letter-spacing:.08em;">{{ $tipe }}</span></p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-light fw-semibold">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="{{ route('admin.users.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    @include('admin._partials.flash')

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="surface-card p-4 mb-3">
                <h6 class="fw-bold text-uppercase text-muted mb-3" style="letter-spacing: .08em; font-size: .75rem;">
                    Informasi Akun
                </h6>
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Email</dt>
                    <dd class="col-sm-8">{{ $user->email }}</dd>

                    <dt class="col-sm-4 text-muted">Tipe</dt>
                    <dd class="col-sm-8">
                        <span class="tipe-pill {{ $tipe }}">{{ ucfirst($tipe) }}</span>
                    </dd>

                    <dt class="col-sm-4 text-muted">Status Akun</dt>
                    <dd class="col-sm-8">
                        @if ($user->isAccountActive())
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Nonaktif</span>
                            <small class="text-muted ms-1">{{ $user->accountInactiveMessage() }}</small>
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Masa Aktif</dt>
                    <dd class="col-sm-8">
                        @if ($user->active_from || $user->active_until)
                            {{ $user->active_from?->format('d M Y') ?? '-' }}
                            sampai
                            {{ $user->active_until?->format('d M Y') ?? '-' }}
                            @if ($user->disabled_at)
                                <small class="text-muted ms-1">Dinonaktifkan {{ $user->disabled_at->format('d M Y H:i') }}</small>
                            @endif
                        @else
                            <span class="text-muted">Tidak dibatasi</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Verifikasi</dt>
                    <dd class="col-sm-8">
                        @if ($user->email_verified_at)
                            <span class="badge bg-success">Terverifikasi</span>
                            <small class="text-muted ms-1">{{ $user->email_verified_at->format('d M Y H:i') }}</small>
                        @else
                            <span class="badge bg-warning text-dark">Belum verifikasi</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Dibuat</dt>
                    <dd class="col-sm-8">{{ $user->created_at?->format('d M Y H:i') }}</dd>

                    <dt class="col-sm-4 text-muted">Diperbarui</dt>
                    <dd class="col-sm-8">{{ $user->updated_at?->format('d M Y H:i') }}</dd>
                </dl>
            </div>

            <div class="surface-card p-4">
                <h6 class="fw-bold text-uppercase text-muted mb-3" style="letter-spacing: .08em; font-size: .75rem;">
                    Profil Tertaut
                </h6>
                @if ($user->profilable instanceof \App\Models\MasterPegawai)
                    @php $p = $user->profilable; @endphp
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Nama</dt>
                        <dd class="col-sm-8 fw-semibold">{{ $p->nama_lengkap }}</dd>
                        <dt class="col-sm-4 text-muted">NIP</dt>
                        <dd class="col-sm-8">{{ $p->nip ?: '-' }}</dd>
                        <dt class="col-sm-4 text-muted">Jabatan</dt>
                        <dd class="col-sm-8">{{ $p->jabatan ?: '-' }}</dd>
                        <dt class="col-sm-4 text-muted">Bank</dt>
                        <dd class="col-sm-8">{{ $p->nama_bank ?: '-' }} • {{ $p->nomor_rekening ?: '-' }}</dd>
                    </dl>
                    <a href="{{ route('admin.pegawai.show', $p) }}" class="btn btn-sm btn-light text-primary mt-3">
                        <i class="bi bi-arrow-up-right-square me-1"></i> Buka Master Pegawai
                    </a>
                @elseif ($user->profilable instanceof \App\Models\MitraJasa)
                    @php $m = $user->profilable; @endphp
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Nama Mitra</dt>
                        <dd class="col-sm-8 fw-semibold">{{ $m->nama_mitra }}</dd>
                        <dt class="col-sm-4 text-muted">Kode</dt>
                        <dd class="col-sm-8">{{ $m->kode_mitra ?: '-' }}</dd>
                    </dl>
                @else
                    <div class="alert alert-info d-flex align-items-center gap-2 mb-0">
                        <i class="bi bi-shield-check"></i>
                        Akun ini tidak terhubung ke pegawai atau mitra (akun sistem).
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="surface-card p-4 mb-3">
                <h6 class="fw-bold text-uppercase text-muted mb-3" style="letter-spacing: .08em; font-size: .75rem;">
                    Roles Aktif
                </h6>
                @forelse ($user->roles as $role)
                    @php
                        $cls = 'role-chip';
                        if ($role->name === 'Super Admin') $cls .= ' is-superadmin';
                        elseif (str_contains($role->name, 'Mitra')) $cls .= ' is-mitra';
                        elseif (str_contains($role->name, 'Jasa')) $cls .= ' is-jasa';
                        elseif (in_array($role->name, ['Admin Listrik', 'Admin Air'])) $cls .= ' is-utilitas';
                    @endphp
                    <span class="{{ $cls }} mb-1">{{ $role->name }}</span>
                @empty
                    <p class="text-muted mb-0">Belum ada role.</p>
                @endforelse
            </div>

            <div class="surface-card p-4">
                <h6 class="fw-bold text-uppercase text-muted mb-3" style="letter-spacing: .08em; font-size: .75rem;">
                    Aksi Cepat
                </h6>
                @php $waNumber = $user->whatsappNumber(); @endphp
                <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="mb-2"
                      onsubmit="return confirm('Reset password akun {{ $user->email }} ke password default?{{ $waNumber ? ' Email & password baru akan dikirim via WhatsApp ke ' . $waNumber . '.' : ' Nomor WhatsApp tidak tersedia, sampaikan password secara manual.' }}');">
                    @csrf
                    <button class="btn btn-light text-primary border w-100">
                        <i class="bi bi-shield-lock me-1"></i> Reset Password
                        @if ($waNumber)
                            <span class="d-block small text-muted mt-1">
                                <i class="bi bi-whatsapp me-1"></i> Notifikasi ke {{ $waNumber }}
                            </span>
                        @else
                            <span class="d-block small text-muted mt-1">
                                <i class="bi bi-exclamation-circle me-1"></i> Tanpa nomor WhatsApp
                            </span>
                        @endif
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                      onsubmit="return confirm('Hapus akun {{ $user->email }}? Aksi ini tidak bisa dibatalkan.');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-light text-danger border w-100">
                        <i class="bi bi-trash me-1"></i> Hapus Akun
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
