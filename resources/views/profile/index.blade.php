@extends('layouts.app')
@section('title', 'My Profile')

@push('css')
<style>
    .profile-header {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        height: 250px;
        border-radius: 1rem;
        position: relative;
        overflow: hidden;
        animation: fadeInDown 0.6s ease-out;
    }
    
    .profile-header::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 50%;
        background: linear-gradient(to top, rgba(0,0,0,0.3) 0%, transparent 100%);
    }

    .profile-avatar-container {
        position: absolute;
        bottom: -50px;
        left: 50px;
        z-index: 10;
        animation: slideUpFade 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.2s both;
    }

    .profile-avatar {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        border: 5px solid #fff;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        background: #fff;
        transition: transform 0.3s ease;
    }

    .profile-avatar:hover {
        transform: scale(1.05) rotate(3deg);
    }

    .profile-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: none;
        border-radius: 1rem;
        box-shadow: 0 15px 35px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        animation: fadeInUp 0.7s ease-out 0.4s both;
        overflow: hidden;
    }

    .profile-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    }

    .role-badge {
        font-size: 0.85rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        padding: 0.5em 1em;
        border-radius: 50rem;
        transition: all 0.2s;
    }
    
    .role-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 10px rgba(13, 110, 253, 0.2);
    }

    .info-list-item {
        transition: all 0.2s;
        border-left: 3px solid transparent;
    }
    
    .info-list-item:hover {
        background: rgba(13, 110, 253, 0.03);
        border-left-color: #0d6efd;
        padding-left: 1rem;
    }

    /* Keyframes */
    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes slideUpFade {
        from { opacity: 0; transform: translateY(40px) scale(0.9); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    
    .pulse-dot {
        width: 10px; height: 10px;
        background: #28a745;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 0 rgba(40, 167, 69, 0.4);
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
    }
</style>
@endpush

@section('content')
<x-page-title title="My Profile" subtitle="Account Information & Settings" />

<div class="row mb-5">
    <div class="col-12">
        <!-- Header Banner -->
        <div class="profile-header shadow-sm mb-5">
            <div class="profile-avatar-container">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&size=256&background=0d6efd&color=fff&bold=true" class="profile-avatar" alt="User Avatar">
            </div>
            <div class="position-absolute" style="top: 20px; right: 20px; z-index: 10;">
                <span class="badge bg-white text-dark rounded-pill shadow-sm px-3 py-2 fw-semibold d-flex align-items-center gap-2">
                    <span class="pulse-dot"></span> Online Status: Active
                </span>
            </div>
        </div>

        <div class="row" style="margin-top: 60px;">
            <!-- Left Column: Main Info -->
            <div class="col-lg-4 mb-4">
                <div class="card profile-card h-100">
                    <div class="card-body p-4 text-center mt-3">
                        <h3 class="fw-bold mb-1 text-dark">{{ $user->name }}</h3>
                        <p class="text-muted mb-4"><i class="material-icons-outlined align-middle fs-6 me-1">email</i>{{ $user->email ?? 'No email provided' }}</p>
                        
                        <hr class="border-light opacity-50 mb-4">
                        
                        <div class="d-flex flex-column gap-3 text-start">
                            <h6 class="text-uppercase fw-bold text-muted" style="font-size: 0.75rem; letter-spacing: 1px;">Account Roles</h6>
                            <div class="d-flex flex-wrap gap-2">
                                @forelse($user->getRoleNames() as $role)
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 role-badge">
                                        <i class="material-icons-outlined align-middle me-1" style="font-size: 14px;">verified_user</i>
                                        {{ $role }}
                                    </span>
                                @empty
                                    <span class="text-muted fst-italic">No active roles</span>
                                @endforelse
                            </div>
                        </div>
                        
                        <div class="mt-5">
                            <button class="btn btn-outline-primary rounded-pill w-100 fw-semibold shadow-sm" style="transition: all 0.3s;">
                                <i class="material-icons-outlined align-middle me-1 fs-5">edit</i> Edit Profile Info
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Details & Settings -->
            <div class="col-lg-8">
                <div class="card profile-card h-100" style="animation-delay: 0.5s;">
                    <div class="card-header bg-transparent border-bottom p-4">
                        <h5 class="mb-0 fw-bold"><i class="material-icons-outlined align-middle me-2 text-primary">account_circle</i>Account Overview</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-4 info-list-item">
                                    <small class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">Full Name</small>
                                    <span class="fw-semibold text-dark fs-6">{{ $user->name }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-4 info-list-item">
                                    <small class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">Email Address</small>
                                    <span class="fw-semibold text-dark fs-6">{{ $user->email ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-4 info-list-item">
                                    <small class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">Account Status</small>
                                    <span class="fw-semibold text-success fs-6"><i class="bi bi-check-circle-fill me-1"></i> Active User</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-4 info-list-item">
                                    <small class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">Member Since</small>
                                    <span class="fw-semibold text-dark fs-6">{{ $user->created_at ? $user->created_at->format('d F Y') : '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <h6 class="fw-bold mb-3"><i class="material-icons-outlined align-middle me-2 text-warning">security</i>Security Settings</h6>
                            <div class="d-flex align-items-center justify-content-between p-3 border rounded-4 hover-shadow" style="transition: all 0.3s;">
                                <div>
                                    <h6 class="mb-1 fw-bold">Password</h6>
                                    <small class="text-muted">Last changed: Never</small>
                                </div>
                                <button class="btn btn-sm btn-light rounded-pill px-3 fw-semibold border">Change Password</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
