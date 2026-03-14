@extends('layouts.app')
@section('title')
    Pagu Anggaran
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Master Data" subtitle="Pagu Anggaran (DIPA)" />

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
                            <th>Tahun</th>
                            <th>COA/MAK</th>
                            <th>Uraian</th>
                            <th>Pagu Awal</th>
                            <th>Realisasi</th>
                            <th>Sisa Pagu</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($budgets as $budget)
                            <tr>
                                <td>{{ $budget->year }}</td>
                                <td>{{ $budget->coa }}</td>
                                <td>{{ Str::limit($budget->description, 50) }}</td>
                                <td>Rp {{ number_format($budget->initial_budget, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($budget->realized_budget, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($budget->remaining_budget, 0, ',', '.') }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="{{ route('budgets.edit', $budget->id) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil-fill"></i></a>
                                        <form action="{{ route('budgets.destroy', $budget->id) }}" method="POST" onsubmit="return confirm('Hapus pagu ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash-fill"></i></button>
                                        </form>
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
