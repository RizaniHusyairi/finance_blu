@extends('layouts.app')
@section('title', 'Rekap Tagihan')

@section('content')
@include('super_admin_jasa.laporan._styles')
@php
    $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $bulanLabel = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $periodeLabel = !empty($filters['month'])
        ? ($bulanLabel[(int) $filters['month']] ?? $filters['month']) . ' ' . $filters['year']
        : 'Tahun ' . $filters['year'];
@endphp

<div class="sa-report-page">
<div class="sa-report-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-receipt me-2 text-primary"></i>Rekap Tagihan</h4>
        <p class="text-muted mb-0 small">Agregat tagihan jasa untuk {{ $periodeLabel }} beserta breakdown status pembayaran.</p>
    </div>
</div>

@include('super_admin_jasa.laporan._filters', [
    'filters' => $filters,
    'filterOptions' => $filterOptions,
    'showMonth' => true,
    'showTipePnbp' => true,
    'showMitra' => true,
])

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-stack me-1"></i>Jumlah Tagihan</div>
                <div class="fs-3 fw-bold text-dark mt-1">{{ number_format($summary['count']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-cash-stack me-1"></i>Total Tagihan</div>
                <div class="fs-4 fw-bold text-primary mt-1">{{ $rupiah($summary['nominal']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-check-circle me-1"></i>Diterima (Lunas)</div>
                <div class="fs-4 fw-bold text-success mt-1">{{ $rupiah($summary['lunas']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-hourglass-split me-1"></i>Sisa (Outstanding)</div>
                <div class="fs-4 fw-bold text-danger mt-1">{{ $rupiah($summary['sisa']) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Tabel Rekap per Bulan --}}
<div class="card border-0 shadow-sm" style="border-radius:1rem;">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-calendar3 me-2 text-primary"></i>Breakdown Tagihan - {{ $periodeLabel }}</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase">Bulan</th>
                    <th class="small text-uppercase text-center">Jml Tagihan</th>
                    <th class="small text-uppercase text-end">Total Nominal</th>
                    <th class="small text-uppercase text-center">Lunas</th>
                    <th class="small text-uppercase text-center">Belum Lunas</th>
                    <th class="small text-uppercase text-end">Diterima</th>
                    <th class="small text-uppercase text-end">Sisa</th>
                </tr>
            </thead>
            <tbody>
                @forelse($perBulan as $row)
                    <tr>
                        <td class="fw-bold">{{ $bulanLabel[$row->bulan] ?? $row->bulan }}</td>
                        <td class="text-center">{{ number_format($row->jumlah_tagihan) }}</td>
                        <td class="text-end fw-bold">{{ $rupiah($row->total_nominal) }}</td>
                        <td class="text-center"><span class="badge bg-success">{{ $row->jumlah_lunas }}</span></td>
                        <td class="text-center"><span class="badge bg-warning text-dark">{{ $row->jumlah_belum_lunas }}</span></td>
                        <td class="text-end text-success fw-bold">{{ $rupiah($row->nominal_lunas) }}</td>
                        <td class="text-end text-danger fw-bold">{{ $rupiah($row->nominal_sisa) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-inbox me-1"></i>Belum ada data tagihan pada tahun ini.</td></tr>
                @endforelse
            </tbody>
            @if($perBulan->isNotEmpty())
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td>TOTAL</td>
                        <td class="text-center">{{ number_format($perBulan->sum('jumlah_tagihan')) }}</td>
                        <td class="text-end">{{ $rupiah($perBulan->sum('total_nominal')) }}</td>
                        <td class="text-center">{{ $perBulan->sum('jumlah_lunas') }}</td>
                        <td class="text-center">{{ $perBulan->sum('jumlah_belum_lunas') }}</td>
                        <td class="text-end text-success">{{ $rupiah($perBulan->sum('nominal_lunas')) }}</td>
                        <td class="text-end text-danger">{{ $rupiah($perBulan->sum('nominal_sisa')) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
</div>
@endsection
