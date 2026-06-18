@extends('layouts.app')
@section('title', 'Tagihan Jatuh Tempo & Denda')

@push('css')
    @include('dashboard.partials.mitra-ui')
@endpush

@section('content')
@php
    $rupiah = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $tanggal = fn ($value) => $value ? \Carbon\Carbon::parse($value)->translatedFormat('d M Y') : '-';
    $dueBadge = fn ($status) => match ($status) {
        'MACET' => ['Macet', 'bg-dark'],
        'LEWAT_JATUH_TEMPO' => ['Lewat Tempo', 'bg-danger'],
        'JATUH_TEMPO_HARI_INI' => ['Jatuh Tempo Hari Ini', 'bg-warning text-dark'],
        'MENDEKATI_JATUH_TEMPO' => ['Mendekati', 'bg-warning text-dark'],
        'LUNAS' => ['Lunas', 'bg-success'],
        default => ['Normal', 'bg-success'],
    };
@endphp

<div class="mp-hero mb-4">
    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
        <div class="d-flex align-items-start gap-3">
            <span class="mp-hero-icon"><i class="bi bi-calendar2-x fs-4"></i></span>
            <div>
                <h4 class="mb-1 fw-bold text-white">Tagihan Jatuh Tempo & Denda</h4>
                <p class="mb-0 small fw-semibold text-white-50">{{ $mitra->nama_mitra }}</p>
            </div>
        </div>
        <a href="{{ route('mitra.dashboard') }}" class="btn btn-light fw-bold"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="small text-muted fw-bold text-uppercase">Tunggakan Pokok</div>
            <div class="fs-5 fw-bold text-dark">{{ $rupiah($ringkas['pokok']) }}</div>
            <div class="small text-muted">{{ $ringkas['jumlah'] }} tagihan belum lunas</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="small text-muted fw-bold text-uppercase">Denda Berjalan</div>
            <div class="fs-5 fw-bold text-danger">{{ $rupiah($ringkas['denda']) }}</div>
            <div class="small text-muted">2% per 30 hari</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="small text-muted fw-bold text-uppercase">Total Harus Dibayar</div>
            <div class="fs-5 fw-bold text-primary">{{ $rupiah($ringkas['total']) }}</div>
            <div class="small text-muted">pokok + denda</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="small text-muted fw-bold text-uppercase">Lewat Tempo</div>
            <div class="fs-4 fw-bold {{ $ringkas['lewat_tempo'] > 0 ? 'text-danger' : 'text-success' }}">{{ $ringkas['lewat_tempo'] }}</div>
            <div class="small text-muted">tagihan</div>
        </div></div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase">No. Tagihan</th>
                    <th class="small text-uppercase">Jatuh Tempo</th>
                    <th class="small text-uppercase">Status</th>
                    <th class="small text-uppercase text-center">Telat</th>
                    <th class="small text-uppercase text-end">Pokok</th>
                    <th class="small text-uppercase text-end">Denda Berjalan</th>
                    <th class="small text-uppercase text-end">Total Bayar</th>
                    <th class="small text-uppercase text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tagihan as $t)
                    @php([$dueLabel, $dueClass] = $dueBadge($t->status_jatuh_tempo))
                    <tr>
                        <td>
                            <a href="{{ route('mitra.tagihan-jasa.show', $t->id) }}" class="fw-bold text-decoration-none">{{ $t->nomor_tagihan }}</a>
                        </td>
                        <td class="fw-semibold">{{ $tanggal($t->tanggal_jatuh_tempo) }}</td>
                        <td><span class="badge {{ $dueClass }}">{{ $dueLabel }}</span></td>
                        <td class="text-center">{{ $t->hari_terlambat > 0 ? $t->hari_terlambat . ' hari' : '-' }}</td>
                        <td class="text-end">{{ $rupiah($t->total_tagihan) }}</td>
                        <td class="text-end {{ $t->nominal_denda_keterlambatan > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                            {{ $t->nominal_denda_keterlambatan > 0 ? $rupiah($t->nominal_denda_keterlambatan) : '-' }}
                            @if($t->jumlah_periode_denda > 0)
                                <div class="small text-muted">2% x {{ $t->jumlah_periode_denda }} periode</div>
                            @endif
                        </td>
                        <td class="text-end fw-bold">{{ $rupiah($t->total_dengan_denda) }}</td>
                        <td class="text-center">
                            <a href="{{ route('mitra.tagihan-jasa.show', $t->id) }}" class="btn btn-sm btn-primary">Bayar</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">
                        <i class="bi bi-emoji-smile d-block fs-1 mb-2"></i>Tidak ada tagihan yang belum lunas. Mantap!
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
