@extends('layouts.app')

@section('title', 'Proses Tagihan')

@push('css')
<style>
    .ptagihan-card { border: 1px solid #e5e7eb; border-radius: 8px; }
    .ptagihan-badge { font-size: .75rem; }
    .ptagihan-filter { gap: .5rem; }
    @media (max-width: 767.98px) {
        .ptagihan-filter { flex-direction: column; align-items: stretch !important; }
    }
</style>
@endpush

@section('content')
<div class="page-breadcrumb d-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Proses Tagihan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">SPP/SPM/NPI/SP2D</li>
            </ol>
        </nav>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
@endif

<div class="card ptagihan-card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex align-items-center ptagihan-filter">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="flex-grow-1">
                <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Cari nomor tagihan, uraian, atau pihak">
            </div>
            <select name="tipe" class="form-select" style="max-width: 210px;">
                <option value="">Semua tipe</option>
                @foreach(['KONTRAK' => 'Kontrak', 'PERJALDIN' => 'Perjaldin', 'HONORARIUM' => 'Honorarium'] as $value => $label)
                    <option value="{{ $value }}" @selected(strtoupper((string) $tipeFilter) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Filter</button>
            <a href="{{ route('proses-tagihan.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
        </form>
    </div>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'perlu-saya' ? 'active' : '' }}" href="{{ route('proses-tagihan.index', array_filter(['tab' => 'perlu-saya', 'search' => $search, 'tipe' => $tipeFilter])) }}">
            Perlu Tindakan Saya
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab !== 'perlu-saya' ? 'active' : '' }}" href="{{ route('proses-tagihan.index', array_filter(['tab' => 'semua', 'search' => $search, 'tipe' => $tipeFilter])) }}">
            Semua
        </a>
    </li>
</ul>

<div class="card ptagihan-card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tagihan</th>
                    <th>Tipe</th>
                    <th>Nominal</th>
                    <th>Tahap</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tagihans as $tagihan)
                    @php
                        $state = $tagihan->proses_state ?? [];
                        $pihak = $tagihan->detailKontrak?->kontrakTermin?->kontrak?->vendor?->nama_pihak
                            ?? $tagihan->pihak?->nama_pihak
                            ?? $tagihan->nama_supplier
                            ?? '-';
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $tagihan->nomor_tagihan }}</div>
                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($tagihan->deskripsi, 90) }}</div>
                            <div class="text-muted small">{{ $pihak }}</div>
                        </td>
                        <td><span class="badge bg-light text-dark border ptagihan-badge">{{ $tagihan->tipe_tagihan }}</span></td>
                        <td class="fw-semibold">Rp {{ number_format((float) $tagihan->total_netto, 0, ',', '.') }}</td>
                        <td>{{ data_get($state, 'tahap', '-') }}</td>
                        <td>
                            <span class="badge bg-secondary ptagihan-badge">{{ $tagihan->status }}</span>
                            @if(data_get($state, 'perluSaya'))
                                <span class="badge bg-warning text-dark ptagihan-badge ms-1">Perlu aksi</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('proses-tagihan.show', $tagihan->id) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-arrow-right-circle me-1"></i>Buka
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">Belum ada tagihan pada filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tagihans->hasPages())
        <div class="card-footer bg-white">
            {{ $tagihans->links() }}
        </div>
    @endif
</div>
@endsection
