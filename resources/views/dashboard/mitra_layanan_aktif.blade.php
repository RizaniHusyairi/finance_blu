@extends('layouts.app')
@section('title', 'Layanan Jasa Aktif')

@push('css')
    @include('dashboard.partials.mitra-ui')
@endpush

@section('content')
<style>
    .layanan-tree-panel {
        max-height: 650px;
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
</style>

<div class="mp-hero mb-4">
    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
        <div class="d-flex align-items-start gap-3">
            <span class="mp-hero-icon"><i class="bi bi-diagram-3 fs-4"></i></span>
            <div>
                <h4 class="mb-1 fw-bold text-white">Layanan Jasa Aktif</h4>
                <p class="mb-0 small fw-semibold text-white-50">{{ $mitra->nama_mitra }}</p>
            </div>
        </div>
        <a href="{{ route('mitra.dashboard') }}" class="btn btn-light fw-bold text-primary shadow-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<div class="mp-card">
    <div class="mp-card-header">
        <div class="mp-card-title">
            <span class="mp-card-icon"><i class="bi bi-list-task"></i></span>
            <div>
                <h6>Daftar Layanan yang Berlaku</h6>
                <small>Jenis penerimaan dan tarif yang dapat digunakan untuk mitra.</small>
            </div>
        </div>
    </div>
    <div class="card-body">
        @if($selectedLayananIds === [])
            <div class="text-center mp-empty py-4">
                <span class="mp-empty-icon"><i class="bi bi-folder2-open"></i></span>
                <div class="fw-bold">Belum ada layanan jasa aktif</div>
                <div class="small">Hubungi Admin Jasa untuk mengaktifkan layanan.</div>
            </div>
        @else
            <div class="mb-3">
                <div class="fw-bold text-primary">Pilih Jenis Penerimaan</div>
                <div class="text-muted small">Struktur layanan aktif yang sudah ditugaskan kepada akun mitra.</div>
            </div>
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
@endsection
