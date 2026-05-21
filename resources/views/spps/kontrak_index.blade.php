@extends('layouts.app')
@section('title', 'SPP Kontrak')

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
    <x-page-title title="Pembuatan SPP Kontrak" subtitle="Kelola tagihan kontrak yang siap diproses menjadi SPP" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show shadow-sm text-white">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $belumSpp = $contracts->where('status', 'READY_FOR_SPP')->count();
        $prosesSpp = $contracts->where('status', 'PROSES_SPP')->count();
        $selesaiSpp = $contracts->where('status', 'SPP_TERBIT')->count();
        $tertahan = $contracts->filter(fn ($item) => $item->spps->isNotEmpty() && optional($item->spps->first())->status_spp === 'Revisi')->count();
        $totalNominal = $contracts->where('status', 'READY_FOR_SPP')->sum('total_netto');
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
                'description' => 'SPP terbit',
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
            Ada <strong>{{ $tertahan }}</strong> draft SPP kontrak yang perlu tindak lanjut (revisi PPK).
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Daftar Tagihan Kontrak Siap SPP</h6>
        </div>
        <div class="card-body p-0">
            @if($contracts->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-folder2-open text-muted" style="font-size: 3rem;"></i>
                    <h6 class="mt-3 fw-bold">Belum Ada Tagihan Kontrak Siap SPP</h6>
                    <p class="text-muted small">Tagihan kontrak yang sudah diverifikasi akan muncul di sini.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table id="example" class="table table-custom-hover align-middle mb-0" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Dokumen Tagihan</th>
                                <th>Kontrak / Pekerjaan</th>
                                <th>Vendor</th>
                                <th>Nilai Netto</th>
                                <th>Status SPP</th>
                                <th class="text-center pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($contracts as $idx => $contractTagihan)
                                @php
                                    $kontrak = $contractTagihan->detailKontrak?->kontrakTermin?->kontrak;
                                    $hasSpp = $contractTagihan->spps->isNotEmpty();

                                    if ($contractTagihan->status === 'READY_FOR_SPP' && !$hasSpp) {
                                        $statusMeta = ['bg' => 'bg-warning text-dark', 'icon' => 'bi-hourglass', 'label' => 'Belum Dibuat SPP'];
                                    } elseif ($contractTagihan->status === 'PROSES_SPP' || $hasSpp) {
                                        $statusMeta = ['bg' => 'bg-info text-white', 'icon' => 'bi-arrow-repeat', 'label' => 'SPP Dibuat'];
                                    } elseif ($contractTagihan->status === 'SPP_TERBIT') {
                                        $statusMeta = ['bg' => 'bg-success', 'icon' => 'bi-check-circle', 'label' => 'SPP Terbit'];
                                    } else {
                                        $statusMeta = ['bg' => 'bg-secondary', 'icon' => 'bi-dash-circle', 'label' => str_replace('_',' ',$contractTagihan->status)];
                                    }
                                @endphp
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="fw-bold text-dark">{{ $contractTagihan->nomor_tagihan }}</div>
                                        <div class="small text-muted text-truncate" style="max-width: 280px;">{{ Str::limit($contractTagihan->deskripsi, 60) }}</div>
                                        <div class="small text-secondary">
                                            <i class="bi bi-calendar-check me-1"></i> {{ optional($contractTagihan->updated_at)->format('d M Y') ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $kontrak->nomor_spk ?? '-' }}</div>
                                        <div class="small text-muted text-truncate" style="max-width: 240px;">{{ Str::limit($kontrak->nama_pekerjaan ?? '-', 50) }}</div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <i class="bi bi-building me-1 text-secondary"></i>{{ Str::limit($kontrak?->vendor?->nama_pihak ?? '-', 30) }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">Rp {{ number_format($contractTagihan->total_netto, 0, ',', '.') }}</div>
                                        <div class="small text-muted">Netto</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $statusMeta['bg'] }} px-3 py-2 rounded-pill">
                                            <i class="bi {{ $statusMeta['icon'] }} me-1"></i> {{ $statusMeta['label'] }}
                                        </span>
                                    </td>
                                    <td class="text-center pe-4">
                                        @if($kontrak)
                                            <a href="{{ route('spps.kontrak.detail', $contractTagihan->id) }}" class="btn btn-sm btn-outline-primary px-3 rounded-pill fw-bold">
                                                <i class="bi bi-gear me-1"></i> Kelola SPP
                                            </a>
                                        @else
                                            <span class="badge bg-light text-dark border"><i class="bi bi-exclamation-triangle me-1"></i>Relasi tidak ditemukan</span>
                                        @endif
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
