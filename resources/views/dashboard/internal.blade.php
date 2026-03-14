@extends('layouts.app')
@section('title')
    Dashboard Keuangan
@endsection
@section('content')
    <x-page-title title="Dashboard" subtitle="Keuangan APTP" />

    {{-- Summary Cards Row --}}
    <div class="row">
        <div class="col-xl-3 col-md-6 d-flex align-items-stretch">
            <div class="card w-100 rounded-4 overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="wh-42 d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary">
                            <span class="material-icons-outlined fs-5">account_balance_wallet</span>
                        </div>
                        <div>
                            <p class="mb-0 text-muted small">Total Pagu Anggaran</p>
                            <h4 class="mb-0 fw-bold">Rp {{ number_format($totalPagu, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                    <div class="progress" style="height:5px;">
                        <div class="progress-bar bg-primary" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 d-flex align-items-stretch">
            <div class="card w-100 rounded-4 overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="wh-42 d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 text-success">
                            <span class="material-icons-outlined fs-5">payments</span>
                        </div>
                        <div>
                            <p class="mb-0 text-muted small">Realisasi (SP2D)</p>
                            <h4 class="mb-0 fw-bold text-success">Rp {{ number_format($totalRealisasi, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                    <div class="progress" style="height:5px;">
                        <div class="progress-bar bg-success" style="width: {{ $persenRealisasi }}%"></div>
                    </div>
                    <p class="mb-0 mt-1 small text-muted">{{ $persenRealisasi }}% dari Pagu</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 d-flex align-items-stretch">
            <div class="card w-100 rounded-4 overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="wh-42 d-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 text-warning">
                            <span class="material-icons-outlined fs-5">savings</span>
                        </div>
                        <div>
                            <p class="mb-0 text-muted small">Sisa Anggaran</p>
                            <h4 class="mb-0 fw-bold text-warning">Rp {{ number_format($sisaAnggaran, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                    <div class="progress" style="height:5px;">
                        <div class="progress-bar bg-warning" style="width: {{ 100 - $persenRealisasi }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 d-flex align-items-stretch">
            <div class="card w-100 rounded-4 overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="wh-42 d-flex align-items-center justify-content-center rounded-circle bg-info bg-opacity-10 text-info">
                            <span class="material-icons-outlined fs-5">description</span>
                        </div>
                        <div>
                            <p class="mb-0 text-muted small">Kontrak Aktif / Mitra</p>
                            <h4 class="mb-0 fw-bold">{{ $totalKontrakAktif }} <span class="text-muted fs-6">/ {{ $totalMitra }} mitra</span></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row">
        {{-- Realisasi per Akun Bar Chart --}}
        <div class="col-xl-8 d-flex align-items-stretch">
            <div class="card w-100 rounded-4">
                <div class="card-body">
                    <h6 class="mb-3 fw-bold">Realisasi Anggaran per Akun (MAK)</h6>
                    <div id="chartRealisasi"></div>
                </div>
            </div>
        </div>
        {{-- Transaction Status Donut --}}
        <div class="col-xl-4 d-flex align-items-stretch">
            <div class="card w-100 rounded-4">
                <div class="card-body">
                    <h6 class="mb-3 fw-bold">Status Transaksi</h6>
                    <div id="chartStatus"></div>
                    <div class="d-flex flex-column gap-2 mt-3">
                        @foreach($statusCounts as $status => $count)
                            @php
                                $colors = [
                                    'Draft' => 'warning', 'Verified' => 'info', 'Approved SPP' => 'primary',
                                    'Approved SPM' => 'primary', 'Paid SP2D' => 'success', 'Rejected' => 'danger'
                                ];
                                $c = $colors[$status] ?? 'secondary';
                            @endphp
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-{{ $c }}">{{ $status }}</span>
                                <span class="fw-bold">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tables Row --}}
    <div class="row">
        {{-- Pending Approval --}}
        <div class="col-xl-6 d-flex align-items-stretch">
            <div class="card w-100 rounded-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history"></i> Menunggu Persetujuan</h6>
                    <span class="badge bg-danger">{{ $pendingApproval->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Transaksi</th>
                                    <th>Status</th>
                                    <th class="text-end">Nilai</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingApproval as $t)
                                <tr>
                                    <td>{{ $t->transaction_number }}</td>
                                    <td><span class="badge bg-info text-dark">{{ $t->status }}</span></td>
                                    <td class="text-end">Rp {{ number_format($t->amount, 0, ',', '.') }}</td>
                                    <td><a href="{{ route('transactions.show', $t->id) }}" class="btn btn-sm btn-outline-primary">Proses</a></td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">Tidak ada yang perlu diproses.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        {{-- Recent Transactions --}}
        <div class="col-xl-6 d-flex align-items-stretch">
            <div class="card w-100 rounded-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-receipt"></i> Transaksi Terbaru</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Transaksi</th>
                                    <th>Tipe</th>
                                    <th>Status</th>
                                    <th class="text-end">Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentTransactions as $t)
                                <tr>
                                    <td><a href="{{ route('transactions.show', $t->id) }}">{{ $t->transaction_number }}</a></td>
                                    <td><span class="badge bg-secondary">{{ $t->type }}</span></td>
                                    <td>
                                        @php
                                            $sc = match($t->status) {
                                                'Draft' => 'warning', 'Verified' => 'info',
                                                'Approved SPP', 'Approved SPM' => 'primary',
                                                'Paid SP2D' => 'success', 'Rejected' => 'danger',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $sc }}">{{ $t->status }}</span>
                                    </td>
                                    <td class="text-end">Rp {{ number_format($t->amount, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Active Contracts --}}
    <div class="row">
        <div class="col-12">
            <div class="card rounded-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text"></i> Kontrak Aktif</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Kontrak</th>
                                    <th>Penyedia / Mitra</th>
                                    <th>Masa Kontrak</th>
                                    <th class="text-end">Nilai Kontrak</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeContracts as $c)
                                <tr>
                                    <td>{{ $c->contract_number }}</td>
                                    <td>{{ $c->supplier->name ?? '-' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($c->start_date)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($c->end_date)->format('d/m/Y') }}</td>
                                    <td class="text-end">Rp {{ number_format($c->total_amount, 0, ',', '.') }}</td>
                                    <td><a href="{{ route('contracts.show', $c->id) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                                </tr>
                                @endforeach
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
    // Realisasi per Budget/COA Bar Chart
    var optionsRealisasi = {
        series: [{
            name: 'Pagu',
            data: @json($budgetPagu)
        }, {
            name: 'Realisasi',
            data: @json($budgetRealisasi)
        }],
        chart: { type: 'bar', height: 320, toolbar: { show: false } },
        plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 4 } },
        dataLabels: { enabled: false },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        xaxis: { categories: @json($budgetLabels) },
        yaxis: {
            labels: { formatter: function(val) { return 'Rp ' + (val / 1000000).toFixed(0) + ' Jt'; } }
        },
        fill: { opacity: 1 },
        colors: ['#0d6efd', '#198754'],
        tooltip: {
            y: { formatter: function(val) { return 'Rp ' + val.toLocaleString('id-ID'); } }
        }
    };
    new ApexCharts(document.querySelector("#chartRealisasi"), optionsRealisasi).render();

    // Transaction Status Donut
    var statusLabels = @json(array_keys($statusCounts));
    var statusValues = @json(array_values($statusCounts));
    var statusColors = statusLabels.map(function(s) {
        var map = { 'Draft': '#ffc107', 'Verified': '#0dcaf0', 'Approved SPP': '#0d6efd', 'Approved SPM': '#6610f2', 'Paid SP2D': '#198754', 'Rejected': '#dc3545' };
        return map[s] || '#6c757d';
    });

    var optionsStatus = {
        series: statusValues,
        chart: { type: 'donut', height: 280 },
        labels: statusLabels,
        colors: statusColors,
        legend: { position: 'bottom' },
        plotOptions: { pie: { donut: { size: '65%' } } }
    };
    new ApexCharts(document.querySelector("#chartStatus"), optionsStatus).render();
</script>
@endpush
