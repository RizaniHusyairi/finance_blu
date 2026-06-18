@extends('layouts.app')
@section('title', 'Riwayat Pembayaran')

@push('css')
    @include('dashboard.partials.mitra-ui')
@endpush

@section('content')
@php
    $rupiah = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $tanggal = fn ($value) => $value ? \Carbon\Carbon::parse($value)->translatedFormat('d M Y') : '-';
    $statusOptions = [
        'MENUNGGU_VERIFIKASI' => 'Menunggu Verifikasi',
        'DITERIMA' => 'Diterima',
        'DITOLAK' => 'Ditolak',
        'PERLU_PERBAIKAN' => 'Perlu Perbaikan',
    ];
@endphp

<div class="mp-hero mb-4">
    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
        <div class="d-flex align-items-start gap-3">
            <span class="mp-hero-icon"><i class="bi bi-cash-coin fs-4"></i></span>
            <div>
                <h4 class="mb-1 fw-bold text-white">Riwayat Pembayaran</h4>
                <p class="mb-0 small fw-semibold text-white-50">{{ $mitra->nama_mitra }}</p>
            </div>
        </div>
        <a href="{{ route('mitra.dashboard') }}" class="btn btn-light fw-bold"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="small text-muted fw-bold text-uppercase">Total Bukti</div>
            <div class="fs-4 fw-bold text-dark">{{ $ringkas['total'] }}</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="small text-muted fw-bold text-uppercase">Diterima</div>
            <div class="fs-4 fw-bold text-success">{{ $ringkas['diterima'] }}</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="small text-muted fw-bold text-uppercase">Menunggu</div>
            <div class="fs-4 fw-bold text-info">{{ $ringkas['menunggu'] }}</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="small text-muted fw-bold text-uppercase">Nominal Diterima</div>
            <div class="fs-5 fw-bold text-success">{{ $rupiah($ringkas['nominal_diterima']) }}</div>
        </div></div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    @foreach($statusOptions as $val => $label)
                        <option value="{{ $val }}" @selected(($filters['status'] ?? '') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Tanggal Dari</label>
                <input type="date" name="dari" value="{{ $filters['dari'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Tanggal Sampai</label>
                <input type="date" name="sampai" value="{{ $filters['sampai'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('mitra.riwayat-pembayaran') }}" class="btn btn-light border fw-bold">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase">Tanggal Bayar</th>
                    <th class="small text-uppercase">No. Tagihan</th>
                    <th class="small text-uppercase text-end">Nominal</th>
                    <th class="small text-uppercase">Bank</th>
                    <th class="small text-uppercase">Referensi</th>
                    <th class="small text-uppercase">Status</th>
                    <th class="small text-uppercase text-center">Bukti</th>
                </tr>
            </thead>
            <tbody>
                @forelse($proofs as $proof)
                    <tr>
                        <td class="fw-semibold">{{ $tanggal($proof->tanggal_bayar) }}</td>
                        <td>
                            <a href="{{ route('mitra.tagihan-jasa.show', $proof->tagihan_jasa_id) }}" class="fw-bold text-decoration-none">
                                {{ $proof->tagihanJasa->nomor_tagihan ?? '-' }}
                            </a>
                        </td>
                        <td class="text-end fw-bold">{{ $rupiah($proof->nominal_bayar) }}</td>
                        <td>{{ $proof->bank_pengirim ?: '-' }}</td>
                        <td>{{ $proof->nomor_referensi ?: '-' }}</td>
                        <td><span class="badge {{ $proof->status_badge_class }}">{{ $proof->status_label }}</span></td>
                        <td class="text-center">
                            <a href="{{ route('mitra.tagihan-jasa.bukti-pembayaran.download', ['id' => $proof->tagihan_jasa_id, 'proof' => $proof->id]) }}"
                               class="btn btn-sm btn-light border" title="Unduh bukti"><i class="bi bi-download"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-inbox d-block fs-1 mb-2"></i>Belum ada bukti pembayaran.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($proofs->hasPages())
        <div class="card-body border-top">{{ $proofs->links() }}</div>
    @endif
</div>
@endsection
