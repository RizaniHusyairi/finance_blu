@extends('layouts.app')
@section('title') SPP Honor @endsection
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

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 mb-4 mt-2">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-secondary text-white">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Belum Ada SPP</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $honorariums->where('status', 'Disetujui PPK')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #0dcaf0;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Sedang Proses SPP</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $honorariums->where('status', 'Proses SPP')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #20c997;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">SPP Terbit</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $honorariums->where('status', 'SPP Terbit')->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Daftar Honorarium Siap SPP (Disetujui PPK)</h6>
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No Transaksi / Uraian</th>
                            <th>No BAST</th>
                            <th class="text-end">Total Bruto (Rp)</th>
                            <th>Penerima</th>
                            <th class="text-center">Status SPP</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($honorariums as $idx => $hon)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $hon->transaction_number }}</strong><br>
                                <small>{{ Str::limit($hon->description, 60) }}</small>
                            </td>
                            <td>
                                <small>{{ $hon->bast_number ?? '-' }}</small>
                            </td>
                            <td class="text-end">
                                <strong>Rp {{ number_format($hon->gross_amount, 0, ',', '.') }}</strong>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light border">{{ $hon->honorariumItems->count() }} Orang</span>
                            </td>
                            <td class="text-center">
                                @if($hon->status == 'Disetujui PPK' && $hon->spps->isEmpty())
                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Belum Dibuat SPP</span>
                                @elseif($hon->spps->isNotEmpty())
                                    <span class="badge bg-info"><i class="bi bi-clock-history"></i> SPP Dibuat</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('spps.honor.detail', $hon->id) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-gear"></i> Kelola SPP
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
    <script>$(document).ready(function() { $('#example').DataTable(); });</script>
@endpush
