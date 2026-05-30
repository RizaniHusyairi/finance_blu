@extends('layouts.app')
@section('title', 'Profil Saya')

@php
    $profile = $user->profilable;
    $roleNames = $user->getRoleNames();
    // Inisial untuk avatar fallback.
    $initials = collect(explode(' ', trim($user->name ?? 'U')))
        ->filter()
        ->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->implode('');
@endphp

@push('css')
<style>
    :root {
        --pf-grad: linear-gradient(135deg, #6366f1 0%, #8b5cf6 45%, #ec4899 100%);
        --pf-grad-soft: linear-gradient(135deg, #38bdf8 0%, #6366f1 50%, #a855f7 100%);
    }

    /* ===== Hero ===== */
    .pf-hero {
        position: relative;
        border-radius: 1.75rem;
        overflow: hidden;
        padding: 2.6rem 2.2rem 5.5rem;
        background: var(--pf-grad);
        background-size: 220% 220%;
        animation: pfGradient 14s ease infinite, pfHeroIn .8s cubic-bezier(.22,1,.36,1) both;
        box-shadow: 0 24px 60px rgba(99, 102, 241, .35);
        isolation: isolate;
    }
    @keyframes pfGradient {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    @keyframes pfHeroIn {
        from { opacity: 0; transform: translateY(-24px) scale(.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* Glow orbs */
    .pf-hero::before,
    .pf-hero::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
        z-index: 0;
    }
    .pf-hero::before {
        width: 360px; height: 360px;
        right: -90px; top: -140px;
        background: radial-gradient(circle, rgba(255,255,255,.35), transparent 65%);
        animation: pfFloat 9s ease-in-out infinite;
    }
    .pf-hero::after {
        width: 260px; height: 260px;
        left: -80px; bottom: -120px;
        background: radial-gradient(circle, rgba(255,255,255,.22), transparent 70%);
        animation: pfFloat 11s ease-in-out infinite 1.5s;
    }
    @keyframes pfFloat {
        0%, 100% { transform: translateY(0) translateX(0); }
        50% { transform: translateY(-22px) translateX(12px); }
    }

    /* Floating particles */
    .pf-particles { position: absolute; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; }
    .pf-particles span {
        position: absolute;
        bottom: -20px;
        width: 8px; height: 8px;
        background: rgba(255,255,255,.55);
        border-radius: 50%;
        animation: pfRise linear infinite;
    }
    .pf-particles span:nth-child(1){ left: 8%;  width:6px;height:6px; animation-duration: 9s;  animation-delay: 0s; }
    .pf-particles span:nth-child(2){ left: 20%; width:10px;height:10px;animation-duration: 12s; animation-delay: 2s; opacity:.5; }
    .pf-particles span:nth-child(3){ left: 33%; width:5px;height:5px;  animation-duration: 8s;  animation-delay: 1s; }
    .pf-particles span:nth-child(4){ left: 48%; width:8px;height:8px;  animation-duration: 11s; animation-delay: 3s; opacity:.6; }
    .pf-particles span:nth-child(5){ left: 62%; width:6px;height:6px;  animation-duration: 10s; animation-delay: .5s; }
    .pf-particles span:nth-child(6){ left: 76%; width:12px;height:12px;animation-duration: 13s; animation-delay: 2.5s; opacity:.45; }
    .pf-particles span:nth-child(7){ left: 88%; width:7px;height:7px;  animation-duration: 9.5s;animation-delay: 1.5s; }
    .pf-particles span:nth-child(8){ left: 95%; width:5px;height:5px;  animation-duration: 8.5s;animation-delay: 4s; }
    @keyframes pfRise {
        0% { transform: translateY(0) scale(1); opacity: 0; }
        10% { opacity: .8; }
        90% { opacity: .5; }
        100% { transform: translateY(-360px) scale(.4); opacity: 0; }
    }

    .pf-hero-inner { position: relative; z-index: 2; }

    .pf-hero-title {
        color: #fff;
        font-weight: 800;
        letter-spacing: -.02em;
        text-shadow: 0 2px 12px rgba(0,0,0,.18);
    }
    .pf-hero-sub { color: rgba(255,255,255,.85); font-weight: 500; }

    .pf-status-pill {
        background: rgba(255,255,255,.18);
        border: 1px solid rgba(255,255,255,.35);
        backdrop-filter: blur(10px);
        color: #fff;
        font-weight: 700;
        font-size: .8rem;
        padding: .45rem .9rem;
        border-radius: 999px;
    }

    /* ===== Avatar card overlapping hero ===== */
    .pf-overlap { margin-top: -4.2rem; position: relative; z-index: 3; }

    .pf-avatar-wrap {
        position: relative;
        width: 128px; height: 128px;
        margin: -84px auto 0;
    }
    .pf-avatar-ring {
        position: absolute; inset: -7px;
        border-radius: 50%;
        background: conic-gradient(from 0deg, #6366f1, #ec4899, #38bdf8, #6366f1);
        animation: pfSpin 6s linear infinite;
        filter: blur(.5px);
    }
    @keyframes pfSpin { to { transform: rotate(360deg); } }
    .pf-avatar {
        position: relative;
        width: 128px; height: 128px;
        border-radius: 50%;
        border: 5px solid #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 2.6rem; font-weight: 800; color: #fff;
        background: var(--pf-grad-soft);
        box-shadow: 0 12px 30px rgba(0,0,0,.18);
        overflow: hidden;
        z-index: 1;
        transition: transform .35s cubic-bezier(.22,1,.36,1);
    }
    .pf-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .pf-avatar:hover { transform: scale(1.06) rotate(-3deg); }
    .pf-avatar-status {
        position: absolute; right: 6px; bottom: 6px;
        width: 26px; height: 26px;
        background: #22c55e;
        border: 4px solid #fff;
        border-radius: 50%;
        z-index: 2;
        box-shadow: 0 0 0 0 rgba(34,197,94,.5);
        animation: pfPulse 2s infinite;
    }
    @keyframes pfPulse {
        0% { box-shadow: 0 0 0 0 rgba(34,197,94,.5); }
        70% { box-shadow: 0 0 0 12px rgba(34,197,94,0); }
        100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
    }

    /* ===== Cards ===== */
    .pf-card {
        background: #fff;
        border: 1px solid rgba(99,102,241,.08);
        border-radius: 1.5rem;
        box-shadow: 0 14px 38px rgba(15,23,42,.06);
        opacity: 0;
        animation: pfUp .7s cubic-bezier(.22,1,.36,1) forwards;
    }
    /* Kartu identitas dibiarkan overflow visible supaya avatar yang naik ke
       atas tidak terpotong. Kartu lain tetap rapi tanpa elemen menonjol. */
    .pf-card-identity { overflow: visible; }
    @keyframes pfUp {
        from { opacity: 0; transform: translateY(26px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .pf-delay-1 { animation-delay: .15s; }
    .pf-delay-2 { animation-delay: .3s; }
    .pf-delay-3 { animation-delay: .45s; }

    .pf-role-badge {
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .3px;
        padding: .45em .95em;
        border-radius: 999px;
        background: linear-gradient(135deg, rgba(99,102,241,.12), rgba(168,85,247,.12));
        color: #6d28d9;
        border: 1px solid rgba(139,92,246,.25);
        display: inline-flex; align-items: center; gap: .3rem;
        transition: all .25s ease;
    }
    .pf-role-badge:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 18px rgba(139,92,246,.28);
        background: var(--pf-grad);
        color: #fff;
        border-color: transparent;
    }

    /* Info tile */
    .pf-tile {
        position: relative;
        padding: 1rem 1.1rem;
        border-radius: 1rem;
        background: #f8fafc;
        border: 1px solid #eef2f7;
        overflow: hidden;
        transition: all .28s cubic-bezier(.22,1,.36,1);
    }
    .pf-tile::before {
        content: '';
        position: absolute; left: 0; top: 0; bottom: 0;
        width: 4px;
        background: var(--pf-grad);
        transform: scaleY(0);
        transform-origin: top;
        transition: transform .3s ease;
    }
    .pf-tile:hover {
        transform: translateY(-4px);
        background: #fff;
        box-shadow: 0 12px 26px rgba(99,102,241,.12);
        border-color: rgba(99,102,241,.2);
    }
    .pf-tile:hover::before { transform: scaleY(1); }
    .pf-tile .pf-tile-icon {
        width: 38px; height: 38px;
        border-radius: 11px;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, rgba(99,102,241,.14), rgba(236,72,153,.14));
        color: #6366f1;
        flex-shrink: 0;
        transition: all .28s ease;
    }
    .pf-tile:hover .pf-tile-icon {
        background: var(--pf-grad);
        color: #fff;
        transform: rotate(-8deg) scale(1.05);
    }
    .pf-tile-label {
        font-size: .68rem; letter-spacing: 1px; text-transform: uppercase;
        font-weight: 700; color: #94a3b8;
    }
    .pf-tile-value { font-weight: 700; color: #1e293b; word-break: break-word; }

    .pf-section-title { font-weight: 800; color: #0f172a; letter-spacing: -.01em; }
    .pf-section-icon {
        width: 34px; height: 34px; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--pf-grad);
        color: #fff;
    }

    /* Buttons */
    .pf-btn-grad {
        background: var(--pf-grad);
        background-size: 180% 100%;
        background-position: 0% 0%;
        border: 0; color: #fff; font-weight: 700;
        border-radius: 999px;
        padding: .7rem 1.2rem;
        box-shadow: 0 10px 24px rgba(99,102,241,.32);
        transition: all .3s ease;
    }
    .pf-btn-grad:hover {
        background-position: 100% 0%;
        transform: translateY(-2px);
        box-shadow: 0 16px 32px rgba(99,102,241,.42);
        color: #fff;
    }

    /* Security row */
    .pf-security {
        display: flex; align-items: center; justify-content: space-between;
        gap: 1rem;
        padding: 1.1rem 1.2rem;
        border-radius: 1rem;
        border: 1px solid #eef2f7;
        background: linear-gradient(135deg, rgba(251,191,36,.06), rgba(236,72,153,.05));
        transition: all .28s ease;
    }
    .pf-security:hover {
        border-color: rgba(245,158,11,.35);
        box-shadow: 0 12px 26px rgba(245,158,11,.14);
        transform: translateY(-3px);
    }
    .pf-security-icon {
        width: 46px; height: 46px; border-radius: 13px;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: #fff; flex-shrink: 0;
        box-shadow: 0 8px 18px rgba(245,158,11,.3);
    }

    .pw-toggle { cursor: pointer; user-select: none; }

    .modal-content { border-radius: 1.25rem; }

    @media (max-width: 575.98px) {
        .pf-hero { padding: 2rem 1.3rem 5rem; }
    }
</style>
@endpush

@section('content')
<x-page-title title="Profil Saya" subtitle="Informasi Akun & Pengaturan" />

<div class="row">
    <div class="col-12">
        <!-- ===== HERO ===== -->
        <div class="pf-hero">
            <div class="pf-particles">
                <span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span><span></span>
            </div>
            <div class="pf-hero-inner d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h2 class="pf-hero-title mb-1">{{ $user->name }}</h2>
                    <p class="pf-hero-sub mb-0">
                        <i class="material-icons-outlined align-middle fs-6 me-1">mail</i>{{ $user->email ?? 'Tidak ada email' }}
                    </p>
                </div>
                <span class="pf-status-pill d-inline-flex align-items-center gap-2">
                    <span style="width:9px;height:9px;border-radius:50%;background:#bbf7d0;display:inline-block;box-shadow:0 0 8px #4ade80;"></span>
                    Akun Aktif
                </span>
            </div>
        </div>

        <div class="row g-4 pf-overlap">
            <!-- ===== LEFT: identity card ===== -->
            <div class="col-lg-4">
                <div class="pf-card pf-card-identity pf-delay-1 h-100">
                    <div class="card-body p-4 text-center">
                        <div class="pf-avatar-wrap">
                            <div class="pf-avatar-ring"></div>
                            <div class="pf-avatar">{{ $initials }}</div>
                            <span class="pf-avatar-status" title="Online"></span>
                        </div>

                        <h4 class="fw-bold mt-3 mb-1 text-dark">{{ $user->name }}</h4>
                        <p class="text-muted small mb-3">
                            <i class="material-icons-outlined align-middle" style="font-size:15px;">mail</i>
                            {{ $user->email ?? '-' }}
                        </p>

                        <hr class="opacity-25 my-3">

                        <h6 class="pf-tile-label text-start mb-2">Role Akun</h6>
                        <div class="d-flex flex-wrap gap-2 justify-content-center mb-4">
                            @forelse($roleNames as $role)
                                <span class="pf-role-badge">
                                    <i class="material-icons-outlined" style="font-size: 14px;">verified_user</i>
                                    {{ $role }}
                                </span>
                            @empty
                                <span class="text-muted fst-italic small">Belum ada role</span>
                            @endforelse
                        </div>

                        <button type="button" class="pf-btn-grad w-100"
                                data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="material-icons-outlined align-middle me-1 fs-6">edit</i> Edit Profil
                        </button>

                        <div class="d-flex justify-content-around mt-4 pt-3 border-top">
                            <div>
                                <div class="fw-bold text-dark fs-5">{{ $roleNames->count() }}</div>
                                <div class="pf-tile-label">Role</div>
                            </div>
                            <div>
                                <div class="fw-bold text-dark fs-5">{{ $user->created_at ? (int) $user->created_at->diffInDays(now()) : 0 }}</div>
                                <div class="pf-tile-label">Hari Aktif</div>
                            </div>
                            <div>
                                <div class="fw-bold text-success fs-5"><i class="bi bi-shield-check"></i></div>
                                <div class="pf-tile-label">Terverifikasi</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== RIGHT: details + security ===== -->
            <div class="col-lg-8">
                <div class="pf-card pf-delay-2 mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-4">
                            <span class="pf-section-icon"><i class="material-icons-outlined" style="font-size:19px;">badge</i></span>
                            <h5 class="pf-section-title mb-0">Ringkasan Akun</h5>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="pf-tile d-flex align-items-center gap-3">
                                    <span class="pf-tile-icon"><i class="material-icons-outlined" style="font-size:19px;">person</i></span>
                                    <div class="text-start">
                                        <div class="pf-tile-label">Nama Lengkap</div>
                                        <div class="pf-tile-value">{{ $user->name }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="pf-tile d-flex align-items-center gap-3">
                                    <span class="pf-tile-icon"><i class="material-icons-outlined" style="font-size:19px;">mail</i></span>
                                    <div class="text-start">
                                        <div class="pf-tile-label">Email</div>
                                        <div class="pf-tile-value">{{ $user->email ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>

                            @if($profile)
                                @foreach($fields as $name => $cfg)
                                    @if(! in_array($name, ['nama_lengkap', 'nama_mitra', 'nama_pihak']))
                                        <div class="col-md-6">
                                            <div class="pf-tile d-flex align-items-center gap-3">
                                                <span class="pf-tile-icon"><i class="material-icons-outlined" style="font-size:19px;">{{ $cfg['icon'] ?? 'edit' }}</i></span>
                                                <div class="text-start">
                                                    <div class="pf-tile-label">{{ $cfg['label'] }}</div>
                                                    <div class="pf-tile-value">{{ $profile->{$name} ?: '-' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            @endif

                            <div class="col-md-6">
                                <div class="pf-tile d-flex align-items-center gap-3">
                                    <span class="pf-tile-icon"><i class="material-icons-outlined" style="font-size:19px;">event_available</i></span>
                                    <div class="text-start">
                                        <div class="pf-tile-label">Terdaftar Sejak</div>
                                        <div class="pf-tile-value">{{ $user->created_at ? $user->created_at->translatedFormat('d F Y') : '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pf-card pf-delay-3">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-4">
                            <span class="pf-section-icon" style="background: linear-gradient(135deg,#fbbf24,#f59e0b);"><i class="material-icons-outlined" style="font-size:19px;">security</i></span>
                            <h5 class="pf-section-title mb-0">Pengaturan Keamanan</h5>
                        </div>

                        <div class="pf-security">
                            <div class="d-flex align-items-center gap-3">
                                <span class="pf-security-icon"><i class="material-icons-outlined">lock</i></span>
                                <div>
                                    <h6 class="mb-1 fw-bold">Password</h6>
                                    <small class="text-muted">
                                        Terakhir diperbarui:
                                        {{ $user->updated_at ? $user->updated_at->translatedFormat('d F Y, H:i') : 'Belum pernah' }}
                                    </small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-warning rounded-pill px-3 fw-semibold flex-shrink-0"
                                    data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                <i class="material-icons-outlined align-middle me-1" style="font-size: 16px;">lock_reset</i> Ganti Password
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============ Modal: Edit Profil ============ -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="editProfileModalLabel">
                        <i class="material-icons-outlined align-middle me-2 text-primary">edit</i>Edit Profil
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="material-icons-outlined fs-6">email</i></span>
                                <input type="email" name="email" class="form-control @error('email', 'updateProfile') is-invalid @enderror"
                                       value="{{ old('email', $user->email) }}" required>
                                @error('email', 'updateProfile')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        @forelse($fields as $name => $cfg)
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    {{ $cfg['label'] }}
                                    @if(in_array('required', $cfg['rules'])) <span class="text-danger">*</span> @endif
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="material-icons-outlined fs-6">{{ $cfg['icon'] ?? 'edit' }}</i></span>
                                    <input type="text" name="{{ $name }}"
                                           class="form-control @error($name, 'updateProfile') is-invalid @enderror"
                                           value="{{ old($name, $profile?->{$name}) }}"
                                           @if(in_array('required', $cfg['rules'])) required @endif>
                                    @error($name, 'updateProfile')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info mb-0 small">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Hanya email akun yang dapat diubah pada profil ini. Untuk perubahan data lain, hubungi Super Admin.
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="pf-btn-grad px-4">
                        <i class="material-icons-outlined align-middle me-1" style="font-size: 16px;">save</i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============ Modal: Ganti Password ============ -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('profile.password.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="changePasswordModalLabel">
                        <i class="material-icons-outlined align-middle me-2 text-warning">lock_reset</i>Ganti Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password Saat Ini <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="material-icons-outlined fs-6">lock</i></span>
                            <input type="password" name="current_password" id="current_password"
                                   class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" required>
                            <span class="input-group-text bg-light pw-toggle" data-target="current_password">
                                <i class="material-icons-outlined fs-6">visibility</i>
                            </span>
                            @error('current_password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password Baru <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="material-icons-outlined fs-6">vpn_key</i></span>
                            <input type="password" name="password" id="password"
                                   class="form-control @error('password', 'updatePassword') is-invalid @enderror" required>
                            <span class="input-group-text bg-light pw-toggle" data-target="password">
                                <i class="material-icons-outlined fs-6">visibility</i>
                            </span>
                            @error('password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="text-muted">Minimal 8 karakter.</small>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="material-icons-outlined fs-6">vpn_key</i></span>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   class="form-control" required>
                            <span class="input-group-text bg-light pw-toggle" data-target="password_confirmation">
                                <i class="material-icons-outlined fs-6">visibility</i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-semibold text-white">
                        <i class="material-icons-outlined align-middle me-1" style="font-size: 16px;">save</i> Perbarui Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Toggle visibilitas password.
        document.querySelectorAll('.pw-toggle').forEach(function (el) {
            el.addEventListener('click', function () {
                const input = document.getElementById(this.dataset.target);
                const icon = this.querySelector('i');
                if (!input) return;
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.textContent = 'visibility_off';
                } else {
                    input.type = 'password';
                    icon.textContent = 'visibility';
                }
            });
        });

        // Buka kembali modal yang relevan bila terdapat error validasi.
        @if($errors->updateProfile->any())
            new bootstrap.Modal(document.getElementById('editProfileModal')).show();
        @endif
        @if($errors->updatePassword->any())
            new bootstrap.Modal(document.getElementById('changePasswordModal')).show();
        @endif
    });
</script>
@endpush
