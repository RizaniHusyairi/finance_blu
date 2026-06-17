@extends('layouts.app')
@section('title', 'Manajemen PNBP')

@section('content')
@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $kelompokMeta = [
        'AERO' => ['Aeronautika (AERO)', 'primary', 'bi-airplane-engines', '#0d6efd', '#e9f0fe'],
        'NON_AERO' => ['Non-Aeronautika (NON-AERO)', 'success', 'bi-building', '#198754', '#e7f3ec'],
    ];
@endphp

<style>
    /* Wadah scroll: isi tabel yang bergulir (vertikal & horizontal), bukan halaman */
    .pnbp-matrix {
        --pnbp-accent: #0d6efd;
        --pnbp-tint: #f5f8fd;
        max-height: 62vh;
        overflow: auto;
        border: 1px solid #e6ecf5;
        border-radius: 14px;
        background: #fff;
        -webkit-overflow-scrolling: touch;
    }
    .pnbp-matrix::-webkit-scrollbar { width: 10px; height: 10px; }
    .pnbp-matrix::-webkit-scrollbar-track { background: transparent; }
    .pnbp-matrix::-webkit-scrollbar-thumb { background: #c7d2e3; border: 2px solid #fff; border-radius: 8px; }
    .pnbp-matrix::-webkit-scrollbar-thumb:hover { background: #aab9d4; }

    .pnbp-table { min-width: 1200px; font-size: .84rem; margin: 0; border-collapse: separate; border-spacing: 0; }
    .pnbp-table th, .pnbp-table td { padding: .58rem .75rem; border-bottom: 1px solid #eef2f8; white-space: nowrap; vertical-align: middle; }
    .pnbp-table .num { font-variant-numeric: tabular-nums; text-align: right; color: #1f2d3d; }
    .pnbp-table tbody td.num { color: #475569; }
    .pnbp-table .pnbp-nama { font-weight: 500; white-space: normal; color: #1f2937; }

    /* Header sticky atas + garis aksen kelompok */
    .pnbp-table thead th {
        position: sticky; top: 0; z-index: 3;
        background: var(--pnbp-tint); color: #475569;
        font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
        box-shadow: inset 0 -2px 0 var(--pnbp-accent);
    }

    /* Zebra + hover */
    .pnbp-table tbody tr:nth-child(2n) td { background: #fafbfe; }
    .pnbp-table tbody tr:hover td { background: #eef4ff; }

    /* Kolom Layanan: sticky kiri */
    .pnbp-table .col-layanan {
        position: sticky; left: 0; z-index: 2; background: #fff;
        min-width: 320px; max-width: 400px; white-space: normal;
        box-shadow: 10px 0 14px -10px rgba(15,23,42,.22);
    }
    .pnbp-table tbody tr:nth-child(2n) .col-layanan { background: #fafbfe; }
    .pnbp-table tbody tr:hover .col-layanan { background: #eef4ff; }

    /* Kolom Total: sticky kanan, tegas dengan warna aksen */
    .pnbp-table .col-total {
        position: sticky; right: 0; z-index: 2; background: #fff;
        font-weight: 700; color: var(--pnbp-accent);
        box-shadow: -10px 0 14px -10px rgba(15,23,42,.22);
    }
    .pnbp-table tbody tr:nth-child(2n) .col-total { background: #fafbfe; }
    .pnbp-table tbody tr:hover .col-total { background: #eef4ff; }

    /* Sudut header (kiri/kanan) harus menumpuk di atas kolom sticky */
    .pnbp-table thead .col-layanan, .pnbp-table thead .col-total { z-index: 5; background: var(--pnbp-tint); }

    /* Footer JUMLAH: sticky bawah */
    .pnbp-table tfoot td {
        position: sticky; bottom: 0; z-index: 3;
        background: #eef3fb; color: #0f172a; font-weight: 700;
        border-top: 2px solid var(--pnbp-accent); border-bottom: none;
    }
    .pnbp-table tfoot .col-layanan { z-index: 5; left: 0; background: #eef3fb; box-shadow: 10px 0 14px -10px rgba(15,23,42,.22); }
    .pnbp-table tfoot .col-total { z-index: 5; right: 0; background: #eef3fb; color: var(--pnbp-accent); box-shadow: -10px 0 14px -10px rgba(15,23,42,.22); }

    /* Nomor urut sebagai chip kecil */
    .pnbp-no { display: inline-flex; align-items: center; justify-content: center;
        min-width: 22px; height: 22px; padding: 0 6px; border-radius: 6px;
        font-size: .7rem; font-weight: 700; }

    @media (max-width: 575.98px) {
        .pnbp-table .col-layanan { min-width: 210px; max-width: 230px; }
        .pnbp-matrix { max-height: 70vh; }
    }
</style>

<div class="page-breadcrumb d-none d-md-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Manajemen PNBP</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Monitoring PNBP</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
        <form method="GET" class="d-flex align-items-center gap-2 mb-0">
            <label class="text-secondary small mb-0">Tahun</label>
            <select name="tahun" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                @foreach ($tahunOptions as $th)
                    <option value="{{ $th }}" @selected($th == $tahun)>{{ $th }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('manajemen-pnbp.export', ['tahun' => $tahun]) }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export Excel
        </a>
    </div>
</div>

<h5 class="mb-1 fw-bold">Manajemen &amp; Monitoring PNBP</h5>
<p class="text-secondary mb-4">Rekap realisasi penerimaan PNBP kelompok AERO, Non-AERO, dan PNBP Umum &middot; Tahun {{ $tahun }}.</p>

{{-- ── KPI ── --}}
<div class="row g-3 mb-2">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card radius-10 border-start border-0 border-3 border-primary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div>
                        <p class="mb-0 text-secondary">Realisasi AERO</p>
                        <h6 class="my-1 fw-bold">{{ $rp($totalAero) }}</h6>
                    </div>
                    <div class="ms-auto widget-icon bg-primary text-white"><i class="bi bi-airplane-engines"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card radius-10 border-start border-0 border-3 border-success h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div>
                        <p class="mb-0 text-secondary">Realisasi Non-AERO</p>
                        <h6 class="my-1 fw-bold">{{ $rp($totalNonAero) }}</h6>
                    </div>
                    <div class="ms-auto widget-icon bg-success text-white"><i class="bi bi-building"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card radius-10 border-start border-0 border-3 border-warning h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div>
                        <p class="mb-0 text-secondary">Realisasi PNBP Umum</p>
                        <h6 class="my-1 fw-bold">{{ $rp($totalUmum) }}</h6>
                    </div>
                    <div class="ms-auto widget-icon bg-warning text-white"><i class="bi bi-cash-stack"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card radius-10 border-start border-0 border-3 border-dark h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div>
                        <p class="mb-0 text-secondary">Total Penerimaan</p>
                        <h6 class="my-1 fw-bold">{{ $rp($grandTotal) }}</h6>
                    </div>
                    <div class="ms-auto widget-icon bg-dark text-white"><i class="bi bi-graph-up-arrow"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Grafik ── --}}
<div class="row g-3 mb-2">
    <div class="col-12 col-xl-8">
        <div class="card radius-10 h-100">
            <div class="card-body">
                <h6 class="mb-3 fw-bold"><i class="bi bi-bar-chart-line me-1"></i>Tren Realisasi Bulanan</h6>
                <canvas id="chartPnbpBulanan" height="110"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card radius-10 h-100">
            <div class="card-body">
                <h6 class="mb-3 fw-bold"><i class="bi bi-pie-chart me-1"></i>Komposisi Kelompok</h6>
                <canvas id="chartPnbpKomposisi" height="220"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- ── Matriks nilai tagihan per bulan (Jan–Des) per kelompok ── --}}
@foreach (['AERO', 'NON_AERO'] as $kel)
    @php([$judul, $warna, $ikon, $accent, $tint] = $kelompokMeta[$kel])
    @php($rows = $layananGrouped[$kel] ?? collect())
    <div class="card radius-10 mt-3 border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white d-flex align-items-center flex-wrap gap-2 py-3"
             style="border-left:4px solid {{ $accent }}">
            <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-white"
                  style="width:34px;height:34px;background:{{ $accent }}"><i class="bi {{ $ikon }}"></i></span>
            <div class="lh-1">
                <h6 class="mb-1 fw-bold text-dark">{{ $judul }}</h6>
                <small class="text-secondary">{{ $rows->count() }} layanan &middot; nilai tagihan (Rp) &middot; Tahun {{ $tahun }}</small>
            </div>
            <span class="ms-auto badge rounded-pill px-3 py-2"
                  style="background:{{ $accent }}1a;color:{{ $accent }};font-size:.78rem">
                Total {{ number_format($rows->sum('total'), 0, ',', '.') }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="pnbp-matrix" style="--pnbp-accent:{{ $accent }};--pnbp-tint:{{ $tint }}">
                <table class="table align-middle mb-0 pnbp-table">
                    <thead>
                        <tr class="text-center">
                            <th class="col-layanan text-start">Layanan</th>
                            @foreach ($bulanLabels as $bl)
                                <th>{{ $bl }}</th>
                            @endforeach
                            <th class="col-total text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $i => $r)
                            <tr>
                                <td class="col-layanan">
                                    <span class="pnbp-no me-2" style="background:{{ $accent }}1a;color:{{ $accent }}">{{ $i + 1 }}</span>
                                    <span class="pnbp-nama">{{ $r->nama_lengkap }}</span>
                                    @if ($r->is_intl)
                                        <span class="badge ms-1 align-middle" style="background:#cff4fc;color:#055160;font-size:.62rem;font-weight:700" title="Layanan Internasional">INTL</span>
                                    @endif
                                </td>
                                @for ($m = 1; $m <= 12; $m++)
                                    <td class="num">
                                        @if ($r->bulan[$m] > 0)
                                            {{ number_format($r->bulan[$m], 0, ',', '.') }}
                                        @else
                                            <span class="text-black-50">&ndash;</span>
                                        @endif
                                    </td>
                                @endfor
                                <td class="col-total num">{{ number_format($r->total, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="14" class="text-center text-secondary py-4">Belum ada data pada tahun {{ $tahun }}.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($rows->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td class="col-layanan text-end text-uppercase" style="letter-spacing:.5px">Jumlah</td>
                                @for ($m = 1; $m <= 12; $m++)
                                    @php($colSum = $rows->sum(fn ($r) => $r->bulan[$m]))
                                    <td class="num">{{ $colSum > 0 ? number_format($colSum, 0, ',', '.') : '–' }}</td>
                                @endfor
                                <td class="col-total num">{{ number_format($rows->sum('total'), 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endforeach

{{-- ── PNBP Umum (data input Bendahara Penerimaan) ── --}}
@php($umAccent = '#b45309')
@php($umTint = '#fdf2e4')
<div class="card radius-10 mt-3 border-0 shadow-sm overflow-hidden">
    <div class="card-header bg-white d-flex align-items-center flex-wrap gap-2 py-3"
         style="border-left:4px solid {{ $umAccent }}">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-white"
              style="width:34px;height:34px;background:{{ $umAccent }}"><i class="bi bi-cash-stack"></i></span>
        <div class="lh-1">
            <h6 class="mb-1 fw-bold text-dark">PNBP Umum</h6>
            <small class="text-secondary">{{ $umumRows->count() }} item &middot; realisasi (Rp) &middot; Tahun {{ $tahun }}</small>
        </div>
        <span class="ms-auto badge rounded-pill px-3 py-2"
              style="background:{{ $umAccent }}1a;color:{{ $umAccent }};font-size:.78rem">
            Total {{ number_format($umumRows->sum('total'), 0, ',', '.') }}
        </span>
        <a href="{{ route('manajemen-pnbp.umum.edit', ['tahun' => $tahun]) }}"
           class="btn btn-sm text-white" style="background:{{ $umAccent }}">
            <i class="bi bi-pencil-square me-1"></i>Input / Edit
        </a>
    </div>
    <div class="card-body p-0">
        <div class="pnbp-matrix" style="--pnbp-accent:{{ $umAccent }};--pnbp-tint:{{ $umTint }}">
            <table class="table align-middle mb-0 pnbp-table">
                <thead>
                    <tr class="text-center">
                        <th class="col-layanan text-start">Uraian</th>
                        @foreach ($bulanLabels as $bl)
                            <th>{{ $bl }}</th>
                        @endforeach
                        <th class="col-total text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($umumRows as $i => $r)
                        <tr>
                            <td class="col-layanan">
                                <span class="pnbp-no me-2" style="background:{{ $umAccent }}1a;color:{{ $umAccent }}">{{ $i + 1 }}</span>
                                <span class="pnbp-nama">{{ $r->uraian }}</span>
                            </td>
                            @for ($m = 1; $m <= 12; $m++)
                                <td class="num">
                                    @if ($r->bulan[$m] > 0)
                                        {{ number_format($r->bulan[$m], 0, ',', '.') }}
                                    @else
                                        <span class="text-black-50">&ndash;</span>
                                    @endif
                                </td>
                            @endfor
                            <td class="col-total num">{{ number_format($r->total, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td class="col-layanan text-end text-uppercase" style="letter-spacing:.5px">Jumlah</td>
                        @for ($m = 1; $m <= 12; $m++)
                            @php($colSum = $umumRows->sum(fn ($r) => $r->bulan[$m]))
                            <td class="num">{{ $colSum > 0 ? number_format($colSum, 0, ',', '.') : '–' }}</td>
                        @endfor
                        <td class="col-total num">{{ number_format($umumRows->sum('total'), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@push('script')
<script src="{{ URL::asset('build/plugins/chartjs/js/chart.js') }}"></script>
<script>
(function () {
    const labels = @json($bulanLabels);
    const fmt = (v) => 'Rp ' + Number(v).toLocaleString('id-ID');

    const lineCtx = document.getElementById('chartPnbpBulanan');
    if (lineCtx) {
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    { label: 'AERO', data: @json($seriesAero), borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,.1)', tension: .35, fill: true },
                    { label: 'Non-AERO', data: @json($seriesNonAero), borderColor: '#198754', backgroundColor: 'rgba(25,135,84,.1)', tension: .35, fill: true },
                    { label: 'PNBP Umum', data: @json($seriesUmum), borderColor: '#ffc107', backgroundColor: 'rgba(255,193,7,.1)', tension: .35, fill: true },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + fmt(c.parsed.y) } },
                },
                scales: { y: { ticks: { callback: (v) => 'Rp ' + Number(v).toLocaleString('id-ID') } } },
            },
        });
    }

    const pieCtx = document.getElementById('chartPnbpKomposisi');
    if (pieCtx) {
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['AERO', 'Non-AERO', 'PNBP Umum'],
                datasets: [{
                    data: [{{ $totalAero }}, {{ $totalNonAero }}, {{ $totalUmum }}],
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107'],
                }],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: (c) => c.label + ': ' + fmt(c.parsed) } },
                },
            },
        });
    }
})();
</script>
@endpush
