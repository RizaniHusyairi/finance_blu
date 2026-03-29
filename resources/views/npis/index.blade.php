@extends('layouts.app')
@section('title') Pembuatan NPI Perjaldin @endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Pembuatan NPI" subtitle="Nota Pemindahbukuan Internal" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4 mt-2">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-warning text-dark">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">SPM Terbit (Perlu NPI)</h6>
                    <h3 class="fw-bold mb-0">{{ \App\Models\Spp::where('status_spp', 'SPM Terbit')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #0dcaf0;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Menunggu Verifikasi</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ \App\Models\Spp::whereIn('status_spp', ['Menunggu TTD Bendahara Penerimaan', 'Menunggu Verifikasi PPK NPI'])->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-danger text-white">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Revisi dari PPK</h6>
                    <h3 class="fw-bold mb-0">{{ \App\Models\Spp::where('status_spp', 'Revisi NPI')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #20c997;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">NPI Terbit</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ \App\Models\Spp::where('status_spp', 'NPI Terbit')->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Daftar Perjaldin Siap NPI (SPM Sudah Disetujui PPSPM)</h6>
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Uraian / BAST</th>
                            <th class="text-center">Jumlah SPM</th>
                            <th class="text-center">Status NPI</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($perjaldins as $idx => $perjaldin)
                        @php
                            $totalPerluNpi   = $perjaldin->spps->whereIn('status_spp', ['SPM Terbit', 'Revisi NPI'])->count();
                            $totalMenunggu   = $perjaldin->spps->whereIn('status_spp', ['Menunggu TTD Bendahara Penerimaan', 'Menunggu Verifikasi PPK NPI'])->count();
                            $totalNpiTerbit  = $perjaldin->spps->where('status_spp', 'NPI Terbit')->count();
                            $totalSpm = $perjaldin->spps->whereIn('status_spp', ['SPM Terbit', 'Menunggu TTD Bendahara Penerimaan', 'Menunggu Verifikasi PPK NPI', 'Revisi NPI', 'NPI Terbit'])->count();
                        @endphp
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $perjaldin->uraian }}</strong><br>
                                <small class="text-muted">No BAST: {{ $perjaldin->no_bast ?: '-' }}</small><br>
                                <small class="text-muted">{{ $perjaldin->pejabats->count() }} Pegawai</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $totalSpm }} Dokumen</span>
                            </td>
                            <td class="text-center">
                                @if($totalPerluNpi > 0)
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> {{ $totalPerluNpi }} Perlu NPI</span>
                                @endif
                                @if($totalMenunggu > 0)
                                    <span class="badge bg-info text-dark"><i class="bi bi-person-badge"></i> {{ $totalMenunggu }} Menunggu Validasi</span>
                                @endif
                                @if($totalNpiTerbit > 0)
                                    <span class="badge bg-success"><i class="bi bi-check2-all"></i> {{ $totalNpiTerbit }} NPI Terbit</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('npis.perjaldin.detail', $perjaldin->perjaldin_id) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-bank"></i> Kelola NPI
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>$(document).ready(function() { $('#example').DataTable({ "order": [] }); });</script>
@endpush
