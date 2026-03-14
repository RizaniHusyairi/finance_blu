@extends('layouts.app')
@section('title')
    Supplier / Mitra
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Master Data" subtitle="Supplier & Mitra" />

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3 mb-4">
        <div class="col">
            <div class="card rounded-4 mb-0 h-100">
                <div class="card-body p-3">
                    <p class="mb-1 small">Total Supplier</p>
                    <h5 class="mb-0 fw-bold">{{ $totalSupplier }}</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 mb-0 h-100">
                <div class="card-body p-3">
                    <p class="mb-1 small">Supplier Aktif</p>
                    <h5 class="mb-0 fw-bold">{{ $supplierAktif }}</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 mb-0 h-100">
                <div class="card-body p-3">
                    <p class="mb-1 small">Penyedia Brg/Jasa</p>
                    <h5 class="mb-0 fw-bold">{{ $penyediaBarangJasa }}</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 mb-0 h-100">
                <div class="card-body p-3">
                    <p class="mb-1 small">Data Belum Lengkap</p>
                    <h5 class="mb-0 fw-bold">{{ $dataBelumLengkap }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 text-uppercase">Daftar Supplier / Mitra</h6>
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary"><i class="bi bi-plus"></i> Tambah Mitra</a>
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
                            <th>No</th>
                            <th>Nama Mitra / PT</th>
                            <th>Tipe</th>
                            <th>NPWP</th>
                            <th>Bank / Rekening</th>
                            <th>Phone</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suppliers as $supplier)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $supplier->name }}</td>
                                <td>{{ $supplier->type ?? '-' }}</td>
                                <td>{{ $supplier->npwp ?? '-' }}</td>
                                <td>{{ $supplier->bank_name ?? '-' }} ({{ $supplier->bank_account ?? '-' }})</td>
                                <td>{{ $supplier->phone ?? '-' }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil-fill"></i> Edit</a>
                                        <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash-fill"></i> Hapus</button>
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
