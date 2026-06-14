@extends('layouts.app')
@section('title', 'Admin Jasa')

@section('content')
<style>
    @keyframes blueHeaderGlow {
        0%, 100% { opacity: .55; transform: translate3d(-28px, 0, 0) scale(1); }
        50% { opacity: .95; transform: translate3d(72px, -18px, 0) scale(1.12); }
    }
    @keyframes blueHeaderSweep {
        0% { transform: translateX(-120%) skewX(-18deg); opacity: 0; }
        18% { opacity: .35; }
        45%, 100% { transform: translateX(220%) skewX(-18deg); opacity: 0; }
    }
    .sa-hero {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        border: 1px solid rgba(30, 64, 175, .20);
        border-radius: 18px;
        background: linear-gradient(110deg, #12355c, #174f86 55%, #1d65a6);
        color: #fff;
        padding: 24px;
        box-shadow: 0 18px 50px rgba(18, 53, 92, .22);
    }
    .sa-hero::before,
    .sa-hero::after {
        content: "";
        position: absolute;
        pointer-events: none;
        z-index: -1;
    }
    .sa-hero::before {
        width: 360px;
        height: 360px;
        right: 8%;
        top: -170px;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(125, 211, 252, .32), rgba(59, 130, 246, .22) 42%, transparent 68%);
        animation: blueHeaderGlow 4.5s ease-in-out infinite;
    }
    .sa-hero::after {
        inset: 0;
        width: 48%;
        background: linear-gradient(90deg, transparent, rgba(125,211,252,.16), rgba(255,255,255,.24), rgba(96,165,250,.14), transparent);
        animation: blueHeaderSweep 3.8s ease-in-out infinite;
    }
    .sa-hero-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 46px;
        height: 46px;
        flex: 0 0 46px;
        border-radius: 14px;
        background: #2563eb;
        color: #fff;
        box-shadow: 0 14px 28px rgba(37, 99, 235, .26);
    }
    .sa-card {
        overflow: hidden;
        border: 1px solid rgba(37, 99, 235, .12);
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 16px 42px rgba(37, 99, 235, .08);
    }
    .sa-table-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 10px 14px;
        border-bottom: 1px solid #bfdbfe;
        background: linear-gradient(90deg, #eff6ff 0%, #f8fbff 58%, #ffffff 100%);
    }
    .sa-table-title {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .sa-table-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        border-radius: 9px;
        color: #fff;
        background: #1d4ed8;
        box-shadow: 0 10px 20px rgba(37, 99, 235, .18);
    }
    .sa-table-title h6 {
        margin: 0;
        color: #1e3a8a;
        font-weight: 800;
    }
    .sa-table-title small {
        color: #64748b;
        font-weight: 700;
    }
    .sa-table {
        border-collapse: separate;
        border-spacing: 0;
    }
    .sa-table thead th {
        padding: 13px 16px;
        border-bottom: 1px solid rgba(148, 163, 184, .20);
        color: #64748b;
        background: rgba(248, 250, 252, .86);
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .03em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .sa-table tbody td {
        padding: 14px 16px;
        border-bottom: 1px solid rgba(226, 232, 240, .88);
        color: #475569;
        vertical-align: middle;
    }
    .sa-table tbody tr:hover {
        background: rgba(239, 246, 255, .72);
    }
    .sa-table tbody tr:last-child td {
        border-bottom: 0;
    }
    .sa-name {
        color: #12355c;
        font-weight: 900;
        letter-spacing: .02em;
    }
    .sa-soft-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .02em;
    }
    .sa-soft-badge.success {
        color: #047857;
        background: #d1fae5;
    }
    .sa-soft-badge.muted {
        color: #475569;
        background: #e2e8f0;
    }
    .sa-action {
        border-radius: 10px;
        font-weight: 800;
    }
</style>

<div class="sa-hero mb-4">
    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
        <div class="d-flex align-items-start gap-3">
            <span class="sa-hero-icon"><i class="bi bi-person-gear fs-4"></i></span>
            <div>
                <h4 class="mb-1 fw-bold text-white">Admin Jasa</h4>
                <p class="mb-0 small fw-semibold text-white-50">Kelola akun Admin Jasa dan layanan yang dikelola masing-masing admin.</p>
            </div>
        </div>
        <a href="{{ route('jasa.admin.create') }}" class="btn btn-light fw-bold text-primary shadow-sm jasa-icon-btn" title="Tambah admin jasa" aria-label="Tambah admin jasa">
            <i class="bi bi-plus-lg"></i>
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="sa-card">
    <div class="sa-table-header">
        <div class="sa-table-title">
            <span class="sa-table-icon"><i class="bi bi-table"></i></span>
            <div>
                <h6>Daftar Admin Jasa</h6>
                <small>Akun admin, jabatan, layanan yang dikelola, dan status profil.</small>
            </div>
        </div>
        <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2">{{ number_format($admins->total(), 0, ',', '.') }} admin</span>
    </div>
    <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Jabatan</th>
                        <th>Layanan Dikelola</th>
                        <th>Status Profil</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($admins as $admin)
                        <tr>
                            <td class="sa-name">{{ $admin->name }}</td>
                            <td>{{ $admin->email }}</td>
                            <td>{{ $admin->profilable->jabatan ?? '-' }}</td>
                            <td>{{ $admin->layananJasaDikelola->count() }}</td>
                            <td>
                                <span class="sa-soft-badge {{ ($admin->profilable->status_aktif ?? false) ? 'success' : 'muted' }}">
                                    {{ ($admin->profilable->status_aktif ?? false) ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{ route('jasa.admin.show', $admin) }}" class="btn btn-sm btn-light border sa-action jasa-icon-btn" title="Detail" aria-label="Detail"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('jasa.admin.edit', $admin) }}" class="btn btn-sm btn-light border sa-action jasa-icon-btn" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                                    <a href="{{ route('jasa.admin.layanan.edit', $admin) }}" class="btn btn-sm btn-primary sa-action jasa-icon-btn" title="Atur layanan" aria-label="Atur layanan"><i class="bi bi-sliders"></i></a>
                                    <form method="POST" action="{{ route('jasa.admin.destroy', $admin) }}" class="d-inline" onsubmit="return confirm('Hapus admin jasa ini? Assignment layanan admin akan dilepas.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border text-danger sa-action jasa-icon-btn" title="Hapus" aria-label="Hapus"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada Admin Jasa.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
    </div>
    @if($admins->hasPages())
        <div class="px-3 py-2 border-top">
            {{ $admins->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
