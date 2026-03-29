@extends('layouts.app')
@section('title') SPP Kontrak @endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Pembuatan SPP" subtitle="Kontrak" />

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
                    <h3 class="fw-bold mb-0 text-white">{{ $contracts->where('status', 'Aktif')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #0dcaf0;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Sedang Proses SPP</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $contracts->where('status', 'Proses SPP')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #20c997;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">SPP Terbit</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $contracts->where('status', 'SPP Terbit')->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Daftar Kontrak Siap SPP (Kontrak Aktif)</h6>
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No Kontrak</th>
                            <th>Uraian Pekerjaan</th>
                            <th>Vendor</th>
                            <th class="text-end">Nilai Kontrak (Rp)</th>
                            <th class="text-center">Termin</th>
                            <th class="text-center">Status SPP</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contracts as $idx => $contract)
                        @php
                            $terminCount = $contract->terms->where('type', 'Termin')->count();
                            $sppCount = $contract->spps->count();
                        @endphp
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td><strong class="text-primary">{{ $contract->contract_number }}</strong></td>
                            <td><small>{{ Str::limit($contract->description, 60) }}</small></td>
                            <td>{{ $contract->supplier->name ?? '-' }}</td>
                            <td class="text-end"><strong>Rp {{ number_format($contract->total_amount, 0, ',', '.') }}</strong></td>
                            <td class="text-center">
                                <span class="badge bg-light border">{{ $terminCount }} Termin</span>
                            </td>
                            <td class="text-center">
                                @if($sppCount == 0)
                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Belum Dibuat SPP</span>
                                @elseif($sppCount < $terminCount)
                                    <span class="badge bg-info"><i class="bi bi-clock-history"></i> {{ $sppCount }}/{{ $terminCount }} SPP</span>
                                @else
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> {{ $sppCount }} SPP Lengkap</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('spps.kontrak.detail', $contract->id) }}" class="btn btn-sm btn-primary">
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
