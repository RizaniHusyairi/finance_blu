@extends('layouts.app')
@section('title')
    Pagu Anggaran
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Master Data" subtitle="Daftar Pagu Anggaran BLU" />

    {{-- Summary Widgets --}}
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3 mb-4">
        <div class="col">
            <div class="card rounded-4 mb-0 h-100">
                <div class="card-body p-3">
                    <p class="mb-1 small">Total Pagu BLU</p>
                    <h5 class="mb-0 fw-bold">Rp {{ number_format($totalPagu, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 mb-0 h-100">
                <div class="card-body p-3">
                    <p class="mb-1 small">Total Realisasi</p>
                    <h5 class="mb-0 fw-bold">Rp {{ number_format($totalRealisasi, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 mb-0 h-100">
                <div class="card-body p-3">
                    <p class="mb-1 small">Sisa Pagu</p>
                    <h5 class="mb-0 fw-bold">Rp {{ number_format($sisaPagu, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 mb-0 h-100">
                <div class="card-body p-3">
                    <p class="mb-1 small">Persentase Serapan</p>
                    <h5 class="mb-0 fw-bold">{{ $persenSerapan }}%</h5>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar" role="progressbar" style="width: {{ $persenSerapan }}%;" aria-valuenow="{{ $persenSerapan }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 text-uppercase">Daftar Pagu Anggaran</h6>
        <a href="{{ route('budgets.create') }}" class="btn btn-primary"><i class="bi bi-plus"></i> Tambah Pagu Anggaran</a>
    </div>
    <hr>
    
    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>Kode MAK / COA</th>
                            <th>Uraian / Nama Akun</th>
                            <th>Pagu Alokasi</th>
                            <th>Realisasi</th>
                            <th>Sisa Saldo</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($budgets as $budget)
                            <tr>
                                <td><span class="fw-semibold">{{ $budget->coa }}</span></td>
                                <td>{{ Str::limit($budget->description, 60) }}</td>
                                <td>Rp {{ number_format($budget->initial_budget, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($budget->realized_budget, 0, ',', '.') }}</td>
                                <td>
                                    @if($budget->remaining_budget <= 0)
                                        <span class="badge bg-danger">Rp {{ number_format($budget->remaining_budget, 0, ',', '.') }}</span>
                                    @else
                                        <span class="badge bg-success">Rp {{ number_format($budget->remaining_budget, 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <a href="{{ route('budgets.edit', $budget->id) }}" class="btn btn-sm btn-outline-warning" title="Revisi"><i class="bi bi-pencil-fill"></i> Revisi</a>
                                        <a href="{{ route('budgets.show', $budget->id) }}" class="btn btn-sm btn-outline-primary" title="Log Transaksi"><i class="bi bi-eye-fill"></i> Lihat Detail</a>
                                    </div>
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
    <script>
        $(document).ready(function() {
            $('#example').DataTable();
        });
    </script>
@endpush
