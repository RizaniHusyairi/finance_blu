@extends('layouts.app')
@section('title', 'Manajemen SPP Perjaldin')

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
    <x-page-title title="Pembuatan SPP Perjaldin" subtitle="Kelola item biaya Perjaldin yang siap diproses menjadi SPP" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show shadow-sm text-white">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error') || $errors->any())
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm text-white">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ session('error') ?? $errors->first() }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @include('spps.partials.perjaldin_index_summary')

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-list-check me-2 text-primary"></i>Daftar Tagihan Perjaldin Siap SPP</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive p-2">
                <table id="example" class="table table-custom-hover align-middle mb-0" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Dokumen Perjaldin</th>
                            <th>Total Netto</th>
                            <th>Info Item Aktif</th>
                            <th>Status Kesiapan SPP</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($perjaldins as $idx => $perjaldin)
                            @php
                                $komponens = $perjaldin->komponenPerjaldin->where('total_nominal', '>', 0);
                                $jmlAktif = $komponens->count();
                                $jmlSpp = $komponens->filter(fn($x) => $x->hasDokumenTurunan())->count();

                                if($jmlAktif == 0) {
                                    $progressStatus = ['text' => 'Kosong', 'bg' => 'bg-secondary', 'icon' => 'bi-dash-circle'];
                                } elseif($jmlSpp < $jmlAktif) {
                                    $progressStatus = ['text' => 'Siap Buat SPP', 'bg' => 'bg-primary', 'icon' => 'bi-file-earmark-plus'];
                                } elseif($jmlSpp == $jmlAktif) {
                                    $isAllDone = $komponens->every(fn($x) => in_array($x->status_proses, ['DISETUJUI_SPP', 'LANJUT_SPM', 'SELESAI']));
                                    if ($isAllDone) {
                                        $progressStatus = ['text' => 'SPP Lengkap', 'bg' => 'bg-success', 'icon' => 'bi-check-circle'];
                                    } else {
                                        $progressStatus = ['text' => 'Dalam Verifikasi SPP', 'bg' => 'bg-info text-white', 'icon' => 'bi-hourglass-split'];
                                    }
                                }
                            @endphp
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold text-dark">{{ $perjaldin->nomor_tagihan }}</div>
                                    <div class="small text-muted mb-1 text-truncate" style="max-width: 300px;">{{ $perjaldin->deskripsi }}</div>
                                    <div class="small text-secondary">
                                        <i class="bi bi-calendar-check me-1"></i> Disetujui: {{ $perjaldin->waktu_verifikasi_ppk ? $perjaldin->waktu_verifikasi_ppk->format('d M Y') : '-' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">Rp {{ number_format($perjaldin->total_netto, 0, ',', '.') }}</div>
                                    <div class="small text-muted">{{ $perjaldin->detailPerjaldin->count() }} Peserta</div>
                                </td>
                                <td>
                                    @if($jmlAktif > 0)
                                        <div class="d-flex flex-column gap-1">
                                            <div class="small"><strong>{{ $jmlAktif }}</strong> Item Biaya</div>
                                            <div class="small text-muted"><i class="bi bi-file-text-fill me-1 text-primary"></i>{{ $jmlSpp }} draft SPP</div>
                                        </div>
                                    @else
                                        <span class="text-muted small">Tidak ada item > Rp 0</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $progressStatus['bg'] }} px-3 py-2 rounded-pill">
                                        <i class="bi {{ $progressStatus['icon'] }} me-1"></i> {{ $progressStatus['text'] }}
                                    </span>
                                </td>
                                <td class="text-center pe-4">
                                    <a href="{{ route('spps.perjaldin.detail', $perjaldin->id) }}" class="btn btn-sm btn-outline-primary px-3 rounded-pill fw-bold">
                                        <i class="bi bi-gear me-1"></i> Kelola Item
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="bi bi-folder2-open text-muted" style="font-size: 3rem;"></i>
                                    <h6 class="mt-3 fw-bold">Belum Ada Dokumen Perjaldin Siap SPP</h6>
                                    <p class="text-muted small mb-0">Tagihan perjaldin yang telah diverifikasi akan muncul di sini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function() { 
            $('#example').DataTable({
                "pageLength": 10,
                "ordering": false, /* Custom ordering visually implies a priority, so disable default DataTable ordering which messes up with badges */
                "language": {
                    "search": "Cari Dokumen:",
                    "lengthMenu": "Tampilkan _MENU_ data",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    "infoEmpty": "Tidak ada data yang tersedia",
                    "paginate": {
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    }
                }
            }); 
        });
    </script>
@endpush
