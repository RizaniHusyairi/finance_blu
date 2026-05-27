@extends('layouts.app')
@section('title', 'Rekap Tagihan per Layanan')

@section('content')
@include('super_admin_jasa.laporan._styles')
@php
    $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $angka = fn ($v) => number_format((float) $v, 0, ',', '.');
    $bulanLabel = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $periodeLabel = !empty($filters['month'])
        ? ($bulanLabel[(int) $filters['month']] ?? $filters['month']) . ' ' . $filters['year']
        : 'Tahun ' . $filters['year'];
@endphp

<div class="sa-report-page">
<div class="sa-report-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-grid-3x3-gap me-2 text-primary"></i>Rekap Tagihan per Layanan</h4>
        <p class="text-muted mb-0 small">Ranking pendapatan layanan per bulan dari nominal terbesar untuk {{ $periodeLabel }}.</p>
    </div>
</div>

@include('super_admin_jasa.laporan._filters', [
    'filters' => $filters,
    'filterOptions' => $filterOptions,
    'showMonth' => true,
    'showTipePnbp' => true,
    'showMitra' => true,
])

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-tags me-1"></i>Jumlah Layanan</div>
                <div class="fs-3 fw-bold text-dark mt-1">{{ $angka($summary['layanan_count']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-receipt me-1"></i>Baris Tagihan</div>
                <div class="fs-3 fw-bold text-dark mt-1">{{ $angka($summary['jumlah_tagihan']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-cash-stack me-1"></i>Total Nominal</div>
                <div class="fs-4 fw-bold text-primary mt-1">{{ $rupiah($summary['nominal']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-check-circle me-1"></i>Nominal Lunas</div>
                <div class="fs-4 fw-bold text-success mt-1">{{ $rupiah($summary['nominal_lunas']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius:1rem;">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-trophy me-2 text-primary"></i>Ranking Pendapatan Layanan - {{ $periodeLabel }}</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase">Bulan</th>
                    <th class="small text-uppercase text-center">Rank</th>
                    <th class="small text-uppercase" style="min-width:280px;">Layanan</th>
                    <th class="small text-uppercase text-center">Jml Tagihan</th>
                    <th class="small text-uppercase text-end">Total Pendapatan</th>
                    <th class="small text-uppercase text-end">Lunas</th>
                    <th class="small text-uppercase text-end">Belum Lunas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($months as $month)
                    @php
                        $rankingRows = $rankingBulanan->get($month, collect());
                    @endphp
                    @forelse($rankingRows as $row)
                        <tr>
                            <td class="fw-bold">{{ $bulanLabel[$month] ?? $month }}</td>
                            <td class="text-center">
                                <span class="badge {{ $loop->iteration === 1 ? 'bg-warning text-dark' : 'bg-light text-dark border' }}">
                                    #{{ $loop->iteration }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $row->nama_layanan }}</div>
                                @if($row->kode_layanan)
                                    <div class="small text-muted">{{ $row->kode_layanan }}</div>
                                @endif
                            </td>
                            <td class="text-center">{{ $angka($row->jumlah_tagihan) }}</td>
                            <td class="text-end fw-bold text-primary">{{ $rupiah($row->total_nominal) }}</td>
                            <td class="text-end fw-bold text-success">{{ $rupiah($row->nominal_lunas) }}</td>
                            <td class="text-end fw-bold text-danger">{{ $rupiah($row->nominal_belum_lunas) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="fw-bold">{{ $bulanLabel[$month] ?? $month }}</td>
                            <td colspan="6" class="text-muted py-3">Belum ada pendapatan layanan pada bulan ini.</td>
                        </tr>
                    @endforelse
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-inbox me-1"></i>Belum ada data layanan pada periode ini.</td></tr>
                @endforelse
            </tbody>
            @if($layanans->isNotEmpty())
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="3">TOTAL</td>
                        <td class="text-center">{{ $angka($summary['jumlah_tagihan']) }}</td>
                        <td class="text-end text-primary">{{ $rupiah($summary['nominal']) }}</td>
                        <td class="text-end text-success">{{ $rupiah($summary['nominal_lunas']) }}</td>
                        <td class="text-end text-danger">{{ $rupiah($summary['nominal_belum_lunas']) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
</div>
@endsection
