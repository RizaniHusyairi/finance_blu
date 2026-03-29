@extends('layouts.app')
@section('title')
    Master SPP Perjaldin
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Pembuatan SPP" subtitle="Perjalanan Dinas" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4 mt-2">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-secondary text-white">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Belum Ada SPP</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ \App\Models\Perjaldin::where('status', 'Disetujui')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #0dcaf0;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Sedang Proses SPP</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ \App\Models\Perjaldin::where('status', 'Proses SPP')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #20c997;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Selesai SPP Terbit</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ \App\Models\Perjaldin::where('status', 'SPP Terbit')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-warning text-dark">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Potensi Delay (Tertahan)</h6>
                    <h3 class="fw-bold mb-0">{{ \App\Models\Spp::whereIn('status_spp', ['Menunggu Verifikasi', 'Revisi'])->count() }} Lembar</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Daftar Perjaldin Siap SPP (Disetujui PPK & Kasubag)</h6>
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Uraian / BAST</th>
                            <th>Total Tagihan</th>
                            <th>Status SPP</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($perjaldins as $idx => $perjaldin)
                        @php
                            $totalBiaya = 0;
                            foreach($perjaldin->pejabats as $p) {
                                $totalBiaya += ($p->tiket + $p->transport + $p->penginapan + $p->uang_harian + $p->uang_representasi);
                            }
                        @endphp
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $perjaldin->uraian }}</strong><br>
                                <small class="text-muted">No BAST: {{ $perjaldin->no_bast ?: '-' }}</small><br>
                                <small class="text-muted">{{ $perjaldin->pejabats->count() }} Pegawai</small>
                            </td>
                            <td class="text-end">
                                <strong>Rp {{ number_format($totalBiaya, 0, ',', '.') }}</strong>
                            </td>
                            <td>
                                @if($perjaldin->status == 'Disetujui')
                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Belum Dibuat SPP</span>
                                @elseif(in_array($perjaldin->status, ['Proses SPP', 'SPP Terbit']))
                                    <span class="badge bg-info"><i class="bi bi-clock-history"></i> Proses SPP ({{ $perjaldin->spps->count() }} dibuat)</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('spps.perjaldin.detail', $perjaldin->perjaldin_id) }}" class="btn btn-sm btn-primary">
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
