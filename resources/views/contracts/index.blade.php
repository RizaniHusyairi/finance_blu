@extends('layouts.app')
@section('title')
    Manajemen Kontrak
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Manajemen Kontrak" subtitle="Daftar Kontrak" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 text-uppercase">Data Kontrak</h6>
        <a href="{{ route('contracts.create') }}" class="btn btn-primary"><i class="bi bi-plus"></i> Tambah Kontrak</a>
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
                            <th>No Kontrak</th>
                            <th>Tanggal</th>
                            <th>Mitra</th>
                            <th>Pagu/COA</th>
                            <th>Total Nilai</th>
                            <th>Periode</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contracts as $contract)
                            <tr>
                                <td>{{ $contract->contract_number }}</td>
                                <td>{{ \Carbon\Carbon::parse($contract->date)->format('d/m/Y') }}</td>
                                <td>{{ $contract->supplier->name ?? '-' }}</td>
                                <td>{{ $contract->budget->coa ?? '-' }}</td>
                                <td>Rp {{ number_format($contract->total_amount, 0, ',', '.') }}</td>
                                <td>
                                    {{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/y') }} - 
                                    {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/y') }}
                                </td>
                                <td>
                                    <span class="badge bg-{{ $contract->status == 'Active' ? 'success' : ($contract->status == 'Draft' ? 'warning' : 'secondary') }}">
                                        {{ $contract->status }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="{{ route('contracts.show', $contract->id) }}" class="btn btn-sm btn-outline-info" title="Detail"><i class="bi bi-eye-fill"></i></a>
                                        <a href="{{ route('contracts.edit', $contract->id) }}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                        <form action="{{ route('contracts.destroy', $contract->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kontrak ini? Data terkait mungkin ikut terhapus!');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash-fill"></i></button>
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
