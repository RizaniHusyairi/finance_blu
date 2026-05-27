@extends('layouts.app')
@section('title', 'Rekap Pembayaran')

@section('content')
@include('super_admin_jasa.laporan._styles')
@php
    $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $datetime = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y H:i') : '-';
    $bulanLabel = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $periodeLabel = !empty($filters['month'])
        ? ($bulanLabel[(int) $filters['month']] ?? $filters['month']) . ' ' . $filters['year']
        : 'Tahun ' . $filters['year'];
@endphp

<div class="sa-report-page">
<div class="sa-report-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-credit-card-2-front me-2 text-info"></i>Rekap Pembayaran</h4>
        <p class="text-muted mb-0 small">Riwayat transaksi pembayaran mitra pada {{ $periodeLabel }} berdasarkan tanggal bayar.</p>
    </div>
</div>

@include('super_admin_jasa.laporan._filters', [
    'filters' => $filters,
    'filterOptions' => $filterOptions,
    'showMonth' => true,
    'showTipePnbp' => true,
    'showMitra' => true,
    'showChannel' => true,
])

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-stack me-1"></i>Jumlah Transaksi</div>
                <div class="fs-3 fw-bold text-dark mt-1">{{ number_format($summary['count']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-cash-coin me-1"></i>Total Nominal Dibayar</div>
                <div class="fs-3 fw-bold text-info mt-1">{{ $rupiah($summary['nominal']) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Breakdown per Kanal --}}
@if($perChannel->isNotEmpty())
    <div class="card border-0 shadow-sm mb-3" style="border-radius:1rem;">
        <div class="card-header bg-white border-0 pt-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-credit-card me-2 text-info"></i>Breakdown per Kanal Pembayaran - {{ $periodeLabel }}</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="small text-uppercase">Kanal</th>
                        <th class="small text-uppercase text-center">Jumlah</th>
                        <th class="small text-uppercase text-end">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($perChannel as $row)
                        <tr>
                            <td class="fw-bold">{{ $row->channel }}</td>
                            <td class="text-center">{{ $row->jumlah }}</td>
                            <td class="text-end fw-bold text-info">{{ $rupiah($row->nominal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- Daftar Transaksi --}}
<div class="card border-0 shadow-sm" style="border-radius:1rem;">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-list-check me-2 text-info"></i>Daftar Transaksi Pembayaran</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase">No</th>
                    <th class="small text-uppercase">No. Tagihan</th>
                    <th class="small text-uppercase">Mitra</th>
                    <th class="small text-uppercase">Kanal</th>
                    <th class="small text-uppercase">Referensi</th>
                    <th class="small text-uppercase">Tgl Bayar</th>
                    <th class="small text-uppercase text-end">Nominal</th>
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
                        <td><span class="badge bg-info text-dark">{{ $t->payment_channel ?? '-' }}</span></td>
                        <td><small class="text-muted">{{ $t->payment_reference ?? '-' }}</small></td>
                        <td>{{ $datetime($t->paid_at) }}</td>
                        <td class="text-end fw-bold text-info">{{ $rupiah($t->jumlah_dibayar) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-inbox me-1"></i>Tidak ada transaksi pembayaran pada periode ini.</td></tr>
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
