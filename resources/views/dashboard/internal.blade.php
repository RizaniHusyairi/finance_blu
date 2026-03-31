@extends('layouts.app')
@section('title', 'Dashboard Keuangan')

@push('css')
<style>
    .card-stats { transition: all 0.3s ease; }
    .card-stats:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.12) !important; }
    .icon-circle { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .stat-mini { font-size: 0.75rem; }
    .live-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #198754; animation: livePulse 1.5s infinite; margin-right: 6px; }
    @keyframes livePulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(1.3); } }
</style>
@endpush

@section('content')
<x-page-title title="Dashboard" subtitle="Keuangan BLU" />

{{-- WELCOME BANNER --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card rounded-4 border-0 shadow-sm overflow-hidden" style="background: linear-gradient(135deg, #0d6efd, #6610f2);">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-1 text-white fw-bold">Selamat Datang, {{ auth()->user()->name }}! 👋</h4>
                    <p class="mb-0 text-white-50">
                        <span class="live-dot"></span>
                        Ringkasan performa keuangan dan anggaran APTP — TA {{ date('Y') }}.
                    </p>
                </div>
                <div class="d-none d-md-block text-white-50">
                    <i class="bi bi-calendar3 me-1"></i> {{ now()->translatedFormat('l, d F Y') }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- KPI CARDS --}}
<div class="row g-3 mb-4">
    {{-- Total Pagu --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-stats w-100 rounded-4 border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="icon-circle bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-bank2"></i>
                    </div>
                    <span class="badge bg-light text-primary border border-primary-subtle">Pagu DIPA</span>
                </div>
                <h4 class="fw-bold mb-1">Rp {{ number_format($totalPagu, 0, ',', '.') }}</h4>
                <div class="progress mt-2" style="height:5px;">
                    <div class="progress-bar bg-primary" style="width: 100%"></div>
                </div>
                <p class="mb-0 mt-1 stat-mini text-muted">Total pagu seluruh DIPA aktif.</p>
            </div>
        </div>
    </div>

    {{-- Realisasi --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-stats w-100 rounded-4 border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="icon-circle bg-success bg-opacity-10 text-success">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <span class="badge bg-light text-success border border-success-subtle">Realisasi</span>
                </div>
                <h4 class="fw-bold mb-1 text-success">Rp {{ number_format($totalRealisasi, 0, ',', '.') }}</h4>
                <div class="progress mt-2" style="height:5px;">
                    <div class="progress-bar bg-success" style="width: {{ $persenRealisasi }}%"></div>
                </div>
                <p class="mb-0 mt-1 stat-mini text-muted"><span class="fw-bold text-success">{{ $persenRealisasi }}%</span> dari total pagu.</p>
            </div>
        </div>
    </div>

    {{-- Sisa Anggaran --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-stats w-100 rounded-4 border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="icon-circle bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <span class="badge bg-light text-warning border border-warning-subtle">Sisa</span>
                </div>
                <h4 class="fw-bold mb-1 text-warning">Rp {{ number_format($sisaAnggaran, 0, ',', '.') }}</h4>
                <div class="progress mt-2" style="height:5px;">
                    <div class="progress-bar bg-warning" style="width: {{ 100 - $persenRealisasi }}%"></div>
                </div>
                <p class="mb-0 mt-1 stat-mini text-muted">{{ round(100 - $persenRealisasi, 1) }}% anggaran tersisa.</p>
            </div>
        </div>
    </div>

    {{-- Stat Mini Cards --}}
    <div class="col-xl-3 col-md-6">
        <div class="row g-3 h-100">
            <div class="col-6">
                <div class="card card-stats rounded-4 border-0 shadow-sm h-100">
                    <div class="card-body p-3 text-center">
                        <div class="icon-circle bg-info bg-opacity-10 text-info mx-auto mb-2" style="width:38px;height:38px;font-size:18px;">
                            <i class="bi bi-briefcase-fill"></i>
                        </div>
                        <h5 class="fw-bold mb-0">{{ $totalKontrakAktif }}</h5>
                        <p class="mb-0 stat-mini text-muted">Kontrak Aktif</p>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card card-stats rounded-4 border-0 shadow-sm h-100">
                    <div class="card-body p-3 text-center">
                        <div class="icon-circle bg-secondary bg-opacity-10 text-secondary mx-auto mb-2" style="width:38px;height:38px;font-size:18px;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h5 class="fw-bold mb-0">{{ $totalMitra }}</h5>
                        <p class="mb-0 stat-mini text-muted">Mitra/Vendor</p>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card card-stats rounded-4 border-0 shadow-sm h-100">
                    <div class="card-body p-3 text-center">
                        <div class="icon-circle bg-danger bg-opacity-10 text-danger mx-auto mb-2" style="width:38px;height:38px;font-size:18px;">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <h5 class="fw-bold mb-0">{{ $tagihanPending }}</h5>
                        <p class="mb-0 stat-mini text-muted">Tagihan Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card card-stats rounded-4 border-0 shadow-sm h-100 {{ $tagihanRevisi > 0 ? 'border border-danger' : '' }}">
                    <div class="card-body p-3 text-center">
                        <div class="icon-circle bg-danger bg-opacity-10 text-danger mx-auto mb-2" style="width:38px;height:38px;font-size:18px;">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <h5 class="fw-bold mb-0 {{ $tagihanRevisi > 0 ? 'text-danger' : '' }}">{{ $tagihanRevisi }}</h5>
                        <p class="mb-0 stat-mini text-muted">Perlu Revisi</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- CHARTS ROW --}}
<div class="row g-3 mb-4">
    {{-- Bar Chart: Serapan per Jenis Belanja --}}
    <div class="col-xl-8">
        <div class="card w-100 rounded-4 border-0 shadow-sm h-100">
            <div class="card-header bg-transparent p-4 border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart-fill text-primary me-2"></i>Serapan Anggaran per Jenis Belanja</h6>
            </div>
            <div class="card-body p-4">
                <div id="chartSerapan" style="min-height: 320px;"></div>
            </div>
        </div>
    </div>

    {{-- Donut: Status Tagihan --}}
    <div class="col-xl-4">
        <div class="card w-100 rounded-4 border-0 shadow-sm h-100">
            <div class="card-header bg-transparent p-4 border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-pie-chart-fill text-info me-2"></i>Status Tagihan</h6>
            </div>
            <div class="card-body p-4">
                @if(count($statusCounts) > 0)
                    <div id="chartStatusTagihan" style="min-height: 260px;"></div>
                    <div class="d-flex flex-column gap-2 mt-3">
                        @foreach($statusCounts as $status => $count)
                            @php
                                $c = match(true) {
                                    str_contains($status, 'REVISI') || str_contains($status, 'TOLAK') => 'danger',
                                    str_contains($status, 'PENDING') => 'warning',
                                    str_contains($status, 'DRAFT') => 'secondary',
                                    str_contains($status, 'CAIR') || str_contains($status, 'SP2D') || str_contains($status, 'SELESAI') => 'success',
                                    default => 'info',
                                };
                            @endphp
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-{{ $c }}">{{ str_replace('_', ' ', $status) }}</span>
                                <span class="fw-bold">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        <p>Belum ada data tagihan.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- TABLES ROW --}}
<div class="row g-3 mb-4">
    {{-- Tagihan Pending --}}
    <div class="col-xl-6">
        <div class="card w-100 rounded-4 border-0 shadow-sm h-100 border-start border-warning border-4">
            <div class="card-header bg-transparent p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history text-warning me-2"></i>Tagihan Menunggu Proses</h6>
                <span class="badge bg-warning text-dark">{{ $pendingTagihan->count() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">No. Tagihan</th>
                                <th>Tipe</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Nilai Netto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingTagihan as $t)
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold">{{ $t->nomor_tagihan }}</span>
                                </td>
                                <td><span class="badge bg-secondary">{{ $t->tipe_tagihan }}</span></td>
                                <td>
                                    @php
                                        $sc = match($t->status) {
                                            'PENDING_PPK' => 'warning',
                                            'PENDING_BENDAHARA' => 'info',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $sc }}">{{ str_replace('_', ' ', $t->status) }}</span>
                                </td>
                                <td class="text-end pe-4 fw-bold">Rp {{ number_format($t->total_netto, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4"><i class="bi bi-check-circle me-1"></i>Tidak ada tagihan menunggu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Kontrak Jatuh Tempo --}}
    <div class="col-xl-6">
        <div class="card w-100 rounded-4 border-0 shadow-sm h-100 border-start border-danger border-4">
            <div class="card-header bg-transparent p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-alarm text-danger me-2"></i>Kontrak Hampir Jatuh Tempo (H-14)</h6>
                <a href="{{ route('contracts.index') }}" class="btn btn-sm btn-light">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">SPK & Pekerjaan</th>
                                <th>Vendor</th>
                                <th>Sisa Waktu</th>
                                <th class="text-center pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jatuhTempo as $k)
                                @php
                                    $diff = \Carbon\Carbon::parse($k->tanggal_selesai)->diffInDays(now());
                                    $isLate = \Carbon\Carbon::parse($k->tanggal_selesai)->isPast();
                                @endphp
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold">{{ $k->nomor_spk }}</div>
                                        <small class="text-muted">{{ Str::limit($k->nama_pekerjaan, 30) }}</small>
                                    </td>
                                    <td><small>{{ $k->vendor->nama_perusahaan ?? '-' }}</small></td>
                                    <td>
                                        @if($isLate)
                                            <span class="badge bg-danger">Terlambat {{ $diff }} Hari</span>
                                        @else
                                            <span class="badge bg-warning text-dark">{{ $diff }} Hari Lagi</span>
                                        @endif
                                    </td>
                                    <td class="text-center pe-4">
                                        <a href="{{ route('contracts.show', $k->id) }}" class="btn btn-sm btn-outline-primary" title="Tinjau">
                                            <i class="bi bi-search"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4"><i class="bi bi-shield-check me-1"></i>Tidak ada kontrak yang mendesak.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- KONTRAK AKTIF --}}
<div class="row g-3">
    <div class="col-12">
        <div class="card rounded-4 border-0 shadow-sm">
            <div class="card-header bg-transparent p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text text-primary me-2"></i>Kontrak Aktif Terbaru</h6>
                <a href="{{ route('contracts.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">No. SPK</th>
                                <th>Pekerjaan</th>
                                <th>Penyedia / Mitra</th>
                                <th>Masa Kontrak</th>
                                <th class="text-end">Nilai Kontrak</th>
                                <th class="text-center pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeContracts as $c)
                            <tr>
                                <td class="ps-4 fw-bold">{{ $c->nomor_spk }}</td>
                                <td>{{ Str::limit($c->nama_pekerjaan, 35) }}</td>
                                <td>{{ $c->vendor->nama_perusahaan ?? '-' }}</td>
                                <td>
                                    <small>{{ \Carbon\Carbon::parse($c->tanggal_mulai)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($c->tanggal_selesai)->format('d/m/Y') }}</small>
                                </td>
                                <td class="text-end fw-bold">Rp {{ number_format($c->nilai_total_kontrak, 0, ',', '.') }}</td>
                                <td class="text-center pe-4">
                                    <a href="{{ route('contracts.show', $c->id) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada kontrak aktif.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    // ================================================
    // Bar Chart: Serapan per Jenis Belanja
    // ================================================
    var optionsBar = {
        series: [{
            name: 'Pagu',
            data: @json($chartBarPagu)
        }, {
            name: 'Realisasi',
            data: @json($chartBarRealisasi)
        }],
        chart: {
            type: 'bar',
            height: 320,
            toolbar: { show: false },
            fontFamily: 'inherit',
        },
        plotOptions: {
            bar: { horizontal: false, columnWidth: '55%', borderRadius: 6 }
        },
        dataLabels: { enabled: false },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        xaxis: {
            categories: @json($chartBarLabels),
            labels: { style: { fontSize: '11px' } }
        },
        yaxis: {
            labels: {
                formatter: function(val) {
                    if (val >= 1e9) return 'Rp ' + (val / 1e9).toFixed(1) + ' M';
                    if (val >= 1e6) return 'Rp ' + (val / 1e6).toFixed(0) + ' Jt';
                    return 'Rp ' + val.toLocaleString('id-ID');
                }
            }
        },
        fill: { opacity: 1 },
        colors: ['#0d6efd', '#198754'],
        legend: { position: 'top' },
        tooltip: {
            y: {
                formatter: function(val) {
                    return 'Rp ' + val.toLocaleString('id-ID');
                }
            }
        }
    };
    new ApexCharts(document.querySelector("#chartSerapan"), optionsBar).render();

    // ================================================
    // Donut Chart: Status Tagihan
    // ================================================
    @if(count($statusCounts) > 0)
    var statusLabels = @json(array_keys($statusCounts));
    var statusValues = @json(array_values($statusCounts));
    var statusColors = statusLabels.map(function(s) {
        if (s.includes('REVISI') || s.includes('TOLAK')) return '#dc3545';
        if (s.includes('PENDING')) return '#ffc107';
        if (s.includes('DRAFT')) return '#6c757d';
        if (s.includes('CAIR') || s.includes('SP2D') || s.includes('SELESAI')) return '#198754';
        return '#0dcaf0';
    });

    var optionsDonut = {
        series: statusValues,
        chart: { type: 'donut', height: 260, fontFamily: 'inherit' },
        labels: statusLabels.map(l => l.replace(/_/g, ' ')),
        colors: statusColors,
        legend: { position: 'bottom', fontSize: '11px' },
        plotOptions: { pie: { donut: { size: '65%' } } },
        tooltip: {
            y: { formatter: function(val) { return val + ' tagihan'; } }
        }
    };
    new ApexCharts(document.querySelector("#chartStatusTagihan"), optionsDonut).render();
    @endif
});
</script>
@endpush
