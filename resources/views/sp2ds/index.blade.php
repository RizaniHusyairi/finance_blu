@extends('layouts.app')
@section('title') Pencatatan SP2D Perjaldin @endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Pencatatan SP2D" subtitle="Surat Perintah Pencairan Dana" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row row-cols-1 row-cols-md-3 g-3 mb-4 mt-2">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-warning text-dark">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">NPI Final (Perlu SP2D)</h6>
                    <h3 class="fw-bold mb-0">{{ \App\Models\Spp::whereHas('spm.npi', fn($q) => $q->where('status', \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG))->whereDoesntHave('spm.npi.sp2d')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #0dcaf0;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">SP2D Terbit (Perlu Eksekusi)</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ \App\Models\DokumenSp2d::whereIn('status', [\App\Models\DokumenSp2d::STATUS_DRAFT, \App\Models\DokumenSp2d::STATUS_APPROVED])->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #20c997;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Lunas (BKU Tercatat)</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ \App\Models\DokumenSp2d::where('status', \App\Models\DokumenSp2d::STATUS_EXECUTED)->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Daftar Perjaldin Siap Pencatatan SP2D</h6>
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Uraian / BAST</th>
                            <th class="text-center">Jumlah Dokumen</th>
                            <th class="text-center">Status SP2D</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($perjaldins as $idx => $perjaldin)
                        @php
                            $totalNpiTerbit = $perjaldin->spps->filter(fn($spp) => optional($spp->spm?->npi)->status === \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG && !$spp->spm?->npi?->sp2d)->count();
                            $totalSp2dTerbit = $perjaldin->spps->filter(fn($spp) => in_array(optional($spp->spm?->npi?->sp2d)->status, [\App\Models\DokumenSp2d::STATUS_DRAFT, \App\Models\DokumenSp2d::STATUS_APPROVED], true))->count();
                            $totalLunas = $perjaldin->spps->filter(fn($spp) => optional($spp->spm?->npi?->sp2d)->status === \App\Models\DokumenSp2d::STATUS_EXECUTED)->count();
                        @endphp
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $perjaldin->uraian }}</strong><br>
                                <small class="text-muted">{{ $perjaldin->pejabats->count() }} Pegawai</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $totalNpiTerbit + $totalSp2dTerbit + $totalLunas }} Dokumen</span>
                            </td>
                            <td class="text-center">
                                @if($totalNpiTerbit > 0)
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> {{ $totalNpiTerbit }} Perlu SP2D</span>
                                @endif
                                @if($totalSp2dTerbit > 0)
                                    <span class="badge bg-info text-dark"><i class="bi bi-book"></i> {{ $totalSp2dTerbit }} Perlu BKU</span>
                                @endif
                                @if($totalLunas > 0)
                                    <span class="badge bg-success"><i class="bi bi-check2-all"></i> {{ $totalLunas }} Lunas</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('sp2ds.perjaldin.detail', $perjaldin->perjaldin_id) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-cash-stack"></i> Kelola SP2D & BKU
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
