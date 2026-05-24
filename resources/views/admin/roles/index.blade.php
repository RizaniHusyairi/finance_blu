@extends('layouts.app')

@section('title', 'Manajemen Role')

@push('css')
    @include('admin._partials.styles')
    <style>
        .role-card {
            border: 0;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 6px 22px -16px rgba(15, 23, 42, .25);
            transition: all .25s ease;
            overflow: hidden;
            position: relative;
        }
        .role-card:hover { transform: translateY(-3px); box-shadow: 0 16px 32px -20px rgba(79, 70, 229, .4); }
        .role-card .role-card-icon {
            width: 48px; height: 48px; border-radius: 14px;
            display: inline-flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1.4rem;
            background: linear-gradient(135deg, var(--c1), var(--c2));
            box-shadow: 0 8px 18px -10px rgba(79, 70, 229, .55);
        }
        .role-card .role-tag {
            font-size: .68rem; font-weight: 700; letter-spacing: .08em;
            text-transform: uppercase; color: #6366f1;
        }
    </style>
@endpush

@section('content')
    <x-page-title title="Administrasi" subtitle="Manajemen Role" />

    <div class="admin-hero d-flex align-items-center gap-3 mb-4">
        <div class="hero-icon"><i class="material-icons-outlined">workspace_premium</i></div>
        <div class="flex-grow-1">
            <h1>Manajemen Role</h1>
            <p>
                Daftar role yang berlaku di SIKEREN-BLU. Penambahan/penghapusan role dilakukan
                lewat <code class="text-light">RoleAndPermissionSeeder</code> agar tetap selaras dengan kode aplikasi.
            </p>
        </div>
    </div>

    @include('admin._partials.flash')

    @php
        $palette = [
            'Super Admin'                                => ['#a21caf', '#ec4899', 'shield_moon'],
            'Super Admin Jasa'                           => ['#7c3aed', '#a78bfa', 'workspace_premium'],
            'KPA'                                        => ['#1d4ed8', '#3b82f6', 'gavel'],
            'Kepala Subbagian Keuangan dan Tata Usaha'   => ['#0369a1', '#0ea5e9', 'apartment'],
            'Kepala Seksi Pelayanan dan Kerjasama'       => ['#0891b2', '#22d3ee', 'apartment'],
            'PPK'                                        => ['#15803d', '#22c55e', 'edit_document'],
            'PPSPM'                                      => ['#b45309', '#f59e0b', 'verified'],
            'Bendahara Pengeluaran'                      => ['#be123c', '#fb7185', 'payments'],
            'Bendahara Penerimaan'                       => ['#9d174d', '#ec4899', 'savings'],
            'Pejabat Pengadaan'                          => ['#854d0e', '#eab308', 'shopping_bag'],
            'Operator BLU'                               => ['#1f2937', '#475569', 'computer'],
            'PPABP'                                      => ['#0e7490', '#06b6d4', 'how_to_reg'],
            'Operator Perjaldin'                         => ['#0d9488', '#14b8a6', 'flight_takeoff'],
            'Koordinator Keuangan'                       => ['#1d4ed8', '#60a5fa', 'pie_chart'],
            'Mitra'                                      => ['#0369a1', '#38bdf8', 'storefront'],
            'Mitra Jasa'                                 => ['#0c4a6e', '#0ea5e9', 'storefront'],
            'Admin Jasa'                                 => ['#15803d', '#4ade80', 'support_agent'],
            'Admin Konsesi'                              => ['#65a30d', '#a3e635', 'storefront'],
            'Koordinator Jasa'                           => ['#166534', '#22c55e', 'hub'],
            'Admin Listrik'                              => ['#b45309', '#facc15', 'bolt'],
            'Admin Air'                                  => ['#0369a1', '#06b6d4', 'water_drop'],
        ];
    @endphp

    <div class="row g-3 stagger">
        @foreach ($roles as $role)
            @php
                [$c1, $c2, $icon] = $palette[$role->name] ?? ['#4f46e5', '#7c3aed', 'star'];
            @endphp
            <div class="col-md-6 col-lg-4">
                <div class="role-card p-3 h-100" style="--c1: {{ $c1 }}; --c2: {{ $c2 }};">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="role-card-icon"><i class="material-icons-outlined">{{ $icon }}</i></div>
                        <div class="flex-grow-1">
                            <span class="role-tag">{{ $role->guard_name }}</span>
                            <h6 class="mb-0 fw-bold text-dark">{{ $role->name }}</h6>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="bi bi-people-fill me-1"></i>
                            {{ $role->users_count }} user
                        </small>
                        <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-sm btn-light text-primary">
                            Lihat User <i class="bi bi-chevron-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
