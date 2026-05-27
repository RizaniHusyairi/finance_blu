@extends('layouts.app')
@section('title', 'Rekap Terima Setor')

@section('content')
@include('super_admin_jasa.laporan._styles')
@php
    $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $tanggal = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y') : '-';
    $bulanLabel = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $periodeLabel = !empty($filters['month'])
        ? ($bulanLabel[(int) $filters['month']] ?? $filters['month']) . ' ' . $filters['year']
        : 'Tahun ' . $filters['year'];
@endphp

<div class="sa-report-page">
<div class="sa-report-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-bank me-2 text-success"></i>Rekap Terima Setor</h4>
        <p class="text-muted mb-0 small">Daftar PNBP yang sudah diterima pada {{ $periodeLabel }} sebagai dasar setoran ke kas negara.</p>
    </div>
</div>

@include('super_admin_jasa.laporan._filters', [
    'filters' => $filters,
    'filterOptions' => $filterOptions,
    'showMonth' => true,
    'showTipePnbp' => true,
    'showMitra' => true,
    'extraNotes' => 'Periode mengacu pada tanggal lunas (penerimaan).',
])

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-receipt-cutoff me-1"></i>Jumlah Tagihan Diterima</div>
                <div class="fs-3 fw-bold text-dark mt-1">{{ number_format($summary['count']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-cash-coin me-1"></i>Total Nominal Diterima</div>
                <div class="fs-3 fw-bold text-success mt-1">{{ $rupiah($summary['nominal_diterima']) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Rekap per Bulan --}}
@if($perBulan->isNotEmpty())
    <div class="card border-0 shadow-sm mb-3" style="border-radius:1rem;">
        <div class="card-header bg-white border-0 pt-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-calendar3 me-2 text-success"></i>Rekap Terima Setor - {{ $periodeLabel }}</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="small text-uppercase">Bulan</th>
                        <th class="small text-uppercase text-center">Jumlah</th>
                        <th class="small text-uppercase text-end">Nominal Diterima</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($perBulan as $row)
                        <tr>
                            <td class="fw-bold">{{ $bulanLabel[$row->bulan] ?? $row->bulan }}</td>
                            <td class="text-center">{{ $row->jumlah }}</td>
                            <td class="text-end text-success fw-bold">{{ $rupiah($row->nominal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- Daftar Tagihan Lunas --}}
<div class="card border-0 shadow-sm" style="border-radius:1rem;">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-list-check me-2 text-success"></i>Daftar Penerimaan</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase">No</th>
                    <th class="small text-uppercase">No. Tagihan</th>
                    <th class="small text-uppercase">Mitra</th>
                    <th class="small text-uppercase">Tipe</th>
                    <th class="small text-uppercase">Tgl Tagihan</th>
                    <th class="small text-uppercase">Tgl Lunas</th>
                    <th class="small text-uppercase text-end">Nominal Diterima</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tagihans as $t)
                    <tr>
                        <td>{{ $tagihans->firstItem() + $loop->index }}</td>
                        <td class="fw-bold">
                            <a href="{{ route('tagihan-jasa.show', $t->id) }}" class="text-decoration-none">{{ $t->nomor_tagihan }}</a>
                        </td>
                        <td>{{ $t->mitra->nama_mitra ?? '-' }}</td>
                        <td><span class="badge bg-secondary">{{ $t->tipe_pnbp }}</span></td>
                        <td>{{ $tanggal($t->tanggal_tagihan) }}</td>
                        <td>{{ $tanggal($t->tanggal_lunas) }}</td>
                        <td class="text-end fw-bold text-success">{{ $rupiah($t->jumlah_dibayar) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-inbox me-1"></i>Tidak ada penerimaan pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tagihans->hasPages())
        <div class="card-footer bg-white border-0">
            {{ $tagihans->links() }}
        </div>
    @endif
</div>
</div>
@endsection
