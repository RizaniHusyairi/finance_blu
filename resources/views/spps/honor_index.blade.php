@extends('layouts.app')
@section('title', 'SPP Honorarium')

@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
    <x-page-title title="Pembuatan SPP" subtitle="Honorarium" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $belumSpp = $honorariums->where('status', 'DISETUJUI')->count();
        $prosesSpp = $honorariums->whereIn('status', ['PROSES_SPP', 'SEBAGIAN_SPP_TERBIT'])->count();
        $selesaiSpp = $honorariums->where('status', 'SPP_TERBIT')->count();
        $tertahan = $honorariums->filter(fn ($item) => $item->spps->isNotEmpty() && optional($item->spps->first())->status === 'Revisi')->count();
    @endphp

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4 mt-2">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-secondary text-white">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Belum Dibuat SPP</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $belumSpp }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #0dcaf0;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Sedang Proses SPP</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $prosesSpp }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #20c997;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">SPP Terbit Lengkap</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $selesaiSpp }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-warning text-dark">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Draft SPP / Tertahan</h6>
                    <h3 class="fw-bold mb-0">{{ $tertahan }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-bold">Daftar Pengajuan Honorarium</h6>
        </div>
        <div class="card-body">
            @if($honorariums->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-folder2-open text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Belum Ada Pengajuan Honorarium</h5>
                    <p class="text-muted">Tagihan honorarium yang telah diverifikasi Bendahara akan muncul di sini.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table id="example" class="table table-striped table-bordered align-middle" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">No</th>
                                <th>No Tagihan / Deskripsi</th>
                                <th>Penerima</th>
                                <th class="text-end">Total Netto (Rp)</th>
                                <th class="text-center">Status Tagihan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($honorariums as $idx => $hon)
                                <tr>
                                    <td class="text-center">{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="fw-bold text-primary">{{ $hon->nomor_tagihan }}</div>
                                        <div class="small text-muted">{{ Str::limit($hon->deskripsi, 60) }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light border text-dark">{{ $hon->detailHonorarium->count() }} Orang</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-bold">Rp {{ number_format($hon->total_netto, 0, ',', '.') }}</div>
                                    </td>
                                    <td class="text-center">
                                        @if($hon->status === 'DISETUJUI')
                                            <span class="badge bg-warning text-dark px-2 py-1">Belum Dibuat SPP</span>
                                        @elseif($hon->status === 'PROSES_SPP')
                                            <span class="badge bg-info px-2 py-1">SPP Sedang Diproses</span>
                                        @elseif($hon->status === 'SPP_TERBIT')
                                            <span class="badge bg-success px-2 py-1">SPP Terbit</span>
                                        @else
                                            <span class="badge bg-secondary px-2 py-1">{{ str_replace('_', ' ', $hon->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('spps.honor.detail', $hon->id) }}" class="btn btn-sm btn-primary">
                                            Kelola SPP
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
            if ($('#example').length) {
                $('#example').DataTable({
                    "pageLength": 10
                });
            }
        });
    </script>
@endpush
