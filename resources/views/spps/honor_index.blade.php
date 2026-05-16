@extends('layouts.app')
@section('title', 'SPP Honorarium')

@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .table-custom-hover tbody tr:hover { background-color: #f8f9fa; }
        .stat-card {
            transition: transform .2s ease, box-shadow .2s ease;
            min-height: 175px;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 .75rem 1.25rem rgba(0,0,0,.08) !important;
        }
        .stat-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: 1.25rem;
        }
        .stat-value {
            font-size: 1.65rem;
            line-height: 1.2;
            font-family: "Georgia", "Times New Roman", serif;
            letter-spacing: -.5px;
        }
        .stat-deco {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 60px;
            overflow: hidden;
            pointer-events: none;
        }
        .stat-wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 200%;
            height: 100%;
        }
        .stat-wave-1 { opacity: .18; animation: stat-wave-scroll 9s linear infinite; }
        .stat-wave-2 { opacity: .28; animation: stat-wave-scroll 13s linear infinite reverse; }
        .stat-wave-3 { opacity: .40; animation: stat-wave-scroll 17s linear infinite; }
        @keyframes stat-wave-scroll {
            from { transform: translate3d(0, 0, 0); }
            to   { transform: translate3d(-50%, 0, 0); }
        }
        @media (prefers-reduced-motion: reduce) {
            .stat-wave-1, .stat-wave-2, .stat-wave-3 { animation: none; }
        }
    </style>
@endpush

@section('content')
    <x-page-title title="Pembuatan SPP Honorarium" subtitle="Kelola tagihan honorarium yang siap diproses menjadi SPP" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show shadow-sm text-white">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $belumSpp = $honorariums->where('status', 'DISETUJUI')->count();
        $prosesSpp = $honorariums->whereIn('status', ['PROSES_SPP', 'SEBAGIAN_SPP_TERBIT'])->count();
        $selesaiSpp = $honorariums->where('status', 'SPP_TERBIT')->count();
        $tertahan = $honorariums->filter(fn ($item) => $item->spps->isNotEmpty() && optional($item->spps->first())->status === 'Revisi')->count();
        $totalNominal = $honorariums->where('status', 'DISETUJUI')->sum('total_netto');
    @endphp

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4 mt-2">
        <div class="col">
            @include('spps.partials.stat-card', [
                'icon' => 'bi-hourglass-split',
                'color' => 'warning',
                'category' => 'Antrean SPP',
                'value' => $belumSpp,
                'description' => 'Belum dibuat SPP',
                'badge' => 'Pending',
                'badgeColor' => 'warning',
            ])
        </div>
        <div class="col">
            @include('spps.partials.stat-card', [
                'icon' => 'bi-arrow-repeat',
                'color' => 'info',
                'category' => 'Verifikasi',
                'value' => $prosesSpp,
                'description' => 'Sedang proses SPP',
                'badge' => 'On Going',
                'badgeColor' => 'info',
            ])
        </div>
        <div class="col">
            @include('spps.partials.stat-card', [
                'icon' => 'bi-check2-all',
                'color' => 'success',
                'category' => 'Terbit',
                'value' => $selesaiSpp,
                'description' => 'SPP terbit lengkap',
                'badge' => 'Done',
                'badgeColor' => 'success',
            ])
        </div>
        <div class="col">
            @include('spps.partials.stat-card', [
                'icon' => 'bi-cash-stack',
                'color' => 'dark',
                'category' => 'Antrean Nominal',
                'value' => 'Rp ' . number_format($totalNominal, 0, ',', '.'),
                'description' => 'Total nominal siap SPP',
            ])
        </div>
    </div>

    @if($tertahan > 0)
        <div class="alert alert-warning border-0 shadow-sm py-2 px-3 mb-3 small">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Ada <strong>{{ $tertahan }}</strong> draft SPP honorarium yang perlu tindak lanjut (revisi PPK).
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-people-fill me-2 text-primary"></i>Daftar Pengajuan Honorarium</h6>
        </div>
        <div class="card-body p-0">
            @if($honorariums->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-folder2-open text-muted" style="font-size: 3rem;"></i>
                    <h6 class="mt-3 fw-bold">Belum Ada Pengajuan Honorarium</h6>
                    <p class="text-muted small">Tagihan honorarium yang telah diverifikasi Bendahara akan muncul di sini.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table id="example" class="table table-custom-hover align-middle mb-0" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Dokumen Honorarium</th>
                                <th>Penerima</th>
                                <th>Total Netto</th>
                                <th>Status Tagihan</th>
                                <th class="text-center pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($honorariums as $idx => $hon)
                                @php
                                    $statusMeta = match($hon->status) {
                                        'DISETUJUI'           => ['bg' => 'bg-warning text-dark', 'icon' => 'bi-hourglass', 'label' => 'Belum Dibuat SPP'],
                                        'PROSES_SPP'          => ['bg' => 'bg-info text-white',   'icon' => 'bi-arrow-repeat', 'label' => 'SPP Sedang Diproses'],
                                        'SEBAGIAN_SPP_TERBIT' => ['bg' => 'bg-info text-white',   'icon' => 'bi-clock-history', 'label' => 'Sebagian SPP Terbit'],
                                        'SPP_TERBIT'          => ['bg' => 'bg-success',           'icon' => 'bi-check-circle', 'label' => 'SPP Terbit'],
                                        default               => ['bg' => 'bg-secondary',         'icon' => 'bi-dash-circle',  'label' => str_replace('_',' ',$hon->status)],
                                    };
                                @endphp
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="fw-bold text-dark">{{ $hon->nomor_tagihan }}</div>
                                        <div class="small text-muted text-truncate" style="max-width: 320px;">{{ Str::limit($hon->deskripsi, 70) }}</div>
                                        <div class="small text-secondary">
                                            <i class="bi bi-calendar-check me-1"></i> {{ optional($hon->updated_at)->format('d M Y') ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-people me-1 text-secondary"></i>{{ $hon->detailHonorarium->count() }} Orang
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">Rp {{ number_format($hon->total_netto, 0, ',', '.') }}</div>
                                        <div class="small text-muted">Netto</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $statusMeta['bg'] }} px-3 py-2 rounded-pill">
                                            <i class="bi {{ $statusMeta['icon'] }} me-1"></i> {{ $statusMeta['label'] }}
                                        </span>
                                    </td>
                                    <td class="text-center pe-4">
                                        <a href="{{ route('spps.honor.detail', $hon->id) }}" class="btn btn-sm btn-outline-primary px-3 rounded-pill fw-bold">
                                            <i class="bi bi-gear me-1"></i> Kelola SPP
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            if ($('#example').length && $('#example tbody tr').length) {
                $('#example').DataTable({
                    "pageLength": 10,
                    "ordering": false,
                    "language": {
                        "search": "Cari Dokumen:",
                        "lengthMenu": "Tampilkan _MENU_ data",
                        "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                        "infoEmpty": "Tidak ada data yang tersedia",
                        "paginate": { "next": "Selanjutnya", "previous": "Sebelumnya" }
                    }
                });
            }
        });
    </script>
@endpush
