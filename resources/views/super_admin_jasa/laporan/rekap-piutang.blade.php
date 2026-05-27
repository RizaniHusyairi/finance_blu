@extends('layouts.app')
@section('title', 'Rekap Piutang')

@section('content')
@include('super_admin_jasa.laporan._styles')
@php
    $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $tanggal = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y') : '-';
    $bulanLabel = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $periodeLabel = !empty($filters['month'])
        ? ($bulanLabel[(int) $filters['month']] ?? $filters['month']) . ' ' . $filters['year']
        : 'Tahun ' . $filters['year'];

    $agingLabels = [
        'belum_jatuh_tempo' => ['Belum Jatuh Tempo', 'bg-secondary'],
        '1_30' => ['Telat 1–30 Hari', 'bg-warning text-dark'],
        '31_60' => ['Telat 31–60 Hari', 'bg-orange', 'background:#f59e0b;color:#fff;'],
        '61_90' => ['Telat 61–90 Hari', 'bg-danger'],
        'lebih_90' => ['Telat > 90 Hari', 'bg-dark'],
    ];
@endphp

<div class="sa-report-page">
<div class="sa-report-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-cash-stack me-2 text-danger"></i>Rekap Piutang</h4>
        <p class="text-muted mb-0 small">Outstanding tagihan jasa pada {{ $periodeLabel }} beserta aging berdasarkan tanggal jatuh tempo.</p>
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
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-stack me-1"></i>Jumlah Tagihan Outstanding</div>
                <div class="fs-3 fw-bold text-dark mt-1">{{ number_format($summary['count']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-cash-stack me-1"></i>Total Tagihan</div>
                <div class="fs-4 fw-bold text-primary mt-1">{{ $rupiah($summary['nominal_tagihan']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-hourglass-split me-1"></i>Sisa Piutang</div>
                <div class="fs-3 fw-bold text-danger mt-1">{{ $rupiah($summary['nominal_sisa']) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Aging Bucket --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:1rem;">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-danger"></i>Analisis Umur Piutang (Aging) - {{ $periodeLabel }}</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach($agingLabels as $key => $cfg)
                @php
                    $data = $agingSummary[$key] ?? ['count' => 0, 'nominal' => 0];
                    $style = $cfg[2] ?? '';
                @endphp
                <div class="col-md">
                    <div class="p-3 rounded-3 border h-100" style="background:#fafbfc;">
                        <span class="badge {{ $cfg[1] }} mb-2" style="{{ $style }}">{{ $cfg[0] }}</span>
                        <div class="fs-5 fw-bold text-dark">{{ $data['count'] }} tagihan</div>
                        <div class="small text-danger fw-bold">{{ $rupiah($data['nominal']) }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Daftar Piutang --}}
<div class="card border-0 shadow-sm" style="border-radius:1rem;">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-list-check me-2 text-danger"></i>Daftar Tagihan Outstanding</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase">No</th>
                    <th class="small text-uppercase">No. Tagihan</th>
                    <th class="small text-uppercase">Mitra</th>
                    <th class="small text-uppercase">Tgl Tagihan</th>
                    <th class="small text-uppercase">Jatuh Tempo</th>
                    <th class="small text-uppercase text-center">Umur</th>
                    <th class="small text-uppercase text-end">Total</th>
                    <th class="small text-uppercase text-end">Dibayar</th>
                    <th class="small text-uppercase text-end">Sisa</th>
                </tr>
            </thead>
            <tbody>
                @forelse($piutangs as $t)
                    @php
                        $hariTelat = $t->tanggal_jatuh_tempo
                            ? (int) now()->startOfDay()->diffInDays($t->tanggal_jatuh_tempo->copy()->startOfDay(), false)
                            : null;
                        $hariTelat = $hariTelat !== null ? -$hariTelat : null;
                    @endphp
                    <tr>
                        <td>{{ $piutangs->firstItem() + $loop->index }}</td>
                        <td class="fw-bold">
                            <a href="{{ route('tagihan-jasa.show', $t->id) }}" class="text-decoration-none">{{ $t->nomor_tagihan }}</a>
                        </td>
                        <td>{{ $t->mitra->nama_mitra ?? '-' }}</td>
                        <td>{{ $tanggal($t->tanggal_tagihan) }}</td>
                        <td>{{ $tanggal($t->tanggal_jatuh_tempo) }}</td>
                        <td class="text-center">
                            @if($hariTelat === null)
                                <span class="text-muted">-</span>
                            @elseif($hariTelat <= 0)
                                <span class="badge bg-secondary">{{ abs($hariTelat) }} hari lagi</span>
                            @elseif($hariTelat <= 30)
                                <span class="badge bg-warning text-dark">+{{ $hariTelat }} hari</span>
                            @elseif($hariTelat <= 60)
                                <span class="badge" style="background:#f59e0b;color:#fff;">+{{ $hariTelat }} hari</span>
                            @elseif($hariTelat <= 90)
                                <span class="badge bg-danger">+{{ $hariTelat }} hari</span>
                            @else
                                <span class="badge bg-dark">+{{ $hariTelat }} hari</span>
                            @endif
                        </td>
                        <td class="text-end">{{ $rupiah($t->total_tagihan) }}</td>
                        <td class="text-end text-success">{{ $rupiah($t->jumlah_dibayar) }}</td>
                        <td class="text-end fw-bold text-danger">{{ $rupiah($t->sisa_tagihan) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4"><i class="bi bi-check-circle me-1"></i>Tidak ada piutang outstanding.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($piutangs->hasPages())
        <div class="card-footer bg-white border-0">
            {{ $piutangs->links() }}
        </div>
    @endif
</div>
</div>
@endsection
