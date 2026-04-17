@extends('layouts.app')
@section('title') Master SPM Perjaldin @endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Pembuatan SPM" subtitle="Perjalanan Dinas" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show">
            <div class="text-white">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4 mt-2">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-warning text-dark">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">SPP Siap Dibuatkan SPM</h6>
                    <h3 class="fw-bold mb-0">{{ \App\Models\Spp::doesntHave('spm')->whereIn('status', ['Disetujui PPK', 'DISETUJUI_SPP', 'APPROVED'])->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #0dcaf0;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Menunggu Verifikasi PPSPM</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ \App\Models\DokumenSpm::where('status', \App\Models\DokumenSpm::STATUS_SUBMITTED_PPSPM)->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-danger text-white">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Dikembalikan (Revisi SPM)</h6>
                    <h3 class="fw-bold mb-0">{{ \App\Models\DokumenSpm::whereIn('status', [\App\Models\DokumenSpm::STATUS_REJECTED_PPSPM, \App\Models\DokumenSpm::STATUS_REJECTED_KASUBAG])->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #20c997;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">SPM Final</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ \App\Models\DokumenSpm::where('status', \App\Models\DokumenSpm::STATUS_APPROVED_KASUBAG)->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Daftar Perjaldin Siap SPM (SPP sudah Disetujui PPK)</h6>
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Uraian / BAST</th>
                            <th class="text-center">Jumlah SPP</th>
                            <th class="text-center">Status SPM</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($perjaldins as $idx => $perjaldin)
                        @php
                            $readySppStatuses = ['Disetujui PPK', 'DISETUJUI_SPP', 'APPROVED'];
                            $totalSppReady   = $perjaldin->spps->filter(fn($spp) => !$spp->spm && in_array($spp->status, $readySppStatuses, true))->count();
                            $totalSpmDikirim = $perjaldin->spps->filter(fn($spp) => optional($spp->spm)->status === \App\Models\DokumenSpm::STATUS_SUBMITTED_PPSPM)->count();
                            $totalSpmTerbit  = $perjaldin->spps->filter(fn($spp) => in_array(optional($spp->spm)->status, [
                                \App\Models\DokumenSpm::STATUS_SUBMITTED_KASUBAG,
                                \App\Models\DokumenSpm::STATUS_APPROVED_KASUBAG
                            ], true))->count();
                            $totalSpp = $perjaldin->spps->count();
                        @endphp
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $perjaldin->uraian }}</strong><br>
                                <small class="text-muted">No BAST: {{ $perjaldin->no_bast ?: '-' }}</small><br>
                                <small class="text-muted">{{ $perjaldin->pejabats->count() }} Pegawai</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $totalSpp }} SPP</span>
                            </td>
                            <td class="text-center">
                                @if($totalSppReady > 0)
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> {{ $totalSppReady }} Perlu SPM</span>
                                @endif
                                @if($totalSpmDikirim > 0)
                                    <span class="badge bg-info text-dark"><i class="bi bi-person-badge"></i> {{ $totalSpmDikirim }} Menunggu PPSPM</span>
                                @endif
                                @if($totalSpmTerbit > 0)
                                    <span class="badge bg-success"><i class="bi bi-check2-all"></i> {{ $totalSpmTerbit }} SPM Terbit</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('spms.perjaldin.detail', $perjaldin->perjaldin_id) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-gear"></i> Kelola SPM
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
