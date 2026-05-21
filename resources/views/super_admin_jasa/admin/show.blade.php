@extends('layouts.app')
@section('title', 'Detail Admin Jasa')

@section('content')
<style>
    .layanan-tree-panel {
        max-height: 520px;
        overflow: auto;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 14px 18px;
    }
    .layanan-tree-node summary {
        cursor: pointer;
        list-style: none;
    }
    .layanan-tree-node summary::-webkit-details-marker {
        display: none;
    }
    .layanan-tree-row {
        display: flex;
        align-items: flex-start;
        gap: 6px;
        color: #3469a4;
        line-height: 1.8;
        font-size: 15px;
    }
    .layanan-tree-row:hover .layanan-tree-title {
        text-decoration: underline;
    }
    .layanan-tree-branch {
        width: 12px;
        height: 14px;
        border-left: 1px solid #1f2937;
        border-bottom: 1px solid #1f2937;
        flex: 0 0 12px;
        margin-top: 2px;
    }
    .layanan-tree-icon {
        color: #2f669b;
        width: 16px;
        flex: 0 0 16px;
    }
    .layanan-tree-title {
        flex: 1;
    }
    .layanan-tree-children {
        margin-left: 16px;
    }
    .layanan-tree-leaf {
        margin-bottom: 4px;
    }
    .layanan-tree-meta {
        margin-left: 34px;
    }
    .admin-profile-header {
        background: linear-gradient(135deg, #0f2f57, #1d6fb8);
    }
    .admin-info-item {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 12px 14px;
        height: 100%;
    }
    .admin-info-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e8f1ff;
        color: #0d6efd;
        flex: 0 0 34px;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div>
        <h4 class="mb-0 fw-bold">Detail Admin Jasa</h4>
        <p class="mb-0 small">{{ $admin->name }} | {{ $admin->email }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('jasa.admin.edit', $admin) }}" class="btn btn-light border fw-bold jasa-icon-btn" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
        <a href="{{ route('jasa.admin.index') }}" class="btn btn-secondary fw-bold jasa-icon-btn" title="Kembali" aria-label="Kembali"><i class="bi bi-arrow-left"></i></a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
            <div class="admin-profile-header p-4 text-white">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="small text-white-50 fw-bold text-uppercase mb-1">Informasi Admin Jasa</div>
                        <h5 class="fw-bold mb-1 text-white">{{ $admin->name }}</h5>
                        <div class="small text-white-50">{{ $admin->email }}</div>
                    </div>
                    <span class="badge {{ ($admin->profilable->status_aktif ?? false) ? 'bg-success' : 'bg-secondary' }} px-3 py-2">
                        {{ ($admin->profilable->status_aktif ?? false) ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    @foreach([
                        ['Nama', $admin->name, 'bi-person-badge'],
                        ['Email', $admin->email, 'bi-envelope'],
                        ['NIP', $admin->profilable->nip ?? '-', 'bi-card-text'],
                        ['Jabatan', $admin->profilable->jabatan ?? '-', 'bi-briefcase'],
                    ] as $item)
                        <div class="col-12">
                            <div class="admin-info-item d-flex gap-3">
                                <span class="admin-info-icon"><i class="bi {{ $item[2] }}"></i></span>
                                <div class="flex-grow-1">
                                    <div class="small text-muted fw-bold">{{ $item[0] }}</div>
                                    <div class="fw-semibold">{{ $item[1] ?: '-' }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <div class="col-12">
                        <div class="admin-info-item">
                            <div class="small text-muted fw-bold mb-1"><i class="bi bi-shield-check me-1 text-primary"></i>Role</div>
                            <span class="badge bg-primary">Admin Jasa</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                <span>Layanan yang Dikelola</span>
                <a href="{{ route('jasa.admin.layanan.edit', $admin) }}" class="btn btn-sm btn-primary fw-bold jasa-icon-btn" title="Atur layanan" aria-label="Atur layanan"><i class="bi bi-sliders"></i></a>
            </div>
            <div class="card-body">
                @if($admin->layananJasaDikelola->isEmpty())
                    <div class="text-muted">Belum ada layanan yang ditugaskan.</div>
                @else
                    <div class="layanan-tree-panel">
                        @php
                            $childrenByParent = $layananTreeItems->groupBy(fn ($item) => $item->parent_id ?: 'root');
                        @endphp
                        @include('super_admin_jasa.mitra.partials.layanan-tree-readonly', [
                            'childrenByParent' => $childrenByParent,
                            'parentId' => 'root',
                            'depth' => 0,
                            'selectedLayananIds' => $selectedLayananIds,
                            'visibleLayananIds' => $visibleLayananIds,
                        ])
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
