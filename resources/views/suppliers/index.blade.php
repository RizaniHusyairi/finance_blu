@extends('layouts.app')
@section('title')
    Master Data Mitra & Vendor
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3 mb-4">
        <div class="col">
            <div class="card rounded-4 mb-0 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-primary rounded-4">
                    <p class="mb-1 small ">Total Mitra/Vendor</p>
                    <h5 class="mb-0 fw-bold">{{ $totalSupplier }}</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 mb-0 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-danger rounded-4">
                    <p class="mb-1 small ">Vendor Pengeluaran</p>
                    <h5 class="mb-0 fw-bold">{{ $supplierAktif }}</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 mb-0 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-success rounded-4">
                    <p class="mb-1 small ">Penyedia Badan Usaha</p>
                    <h5 class="mb-0 fw-bold">{{ $penyediaBarangJasa }}</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 mb-0 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-warning rounded-4">
                    <p class="mb-1 small ">Tanpa NPWP</p>
                    <h5 class="mb-0 fw-bold">{{ $dataBelumLengkap }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 fw-bold">Master Data Mitra & Vendor</h5>
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary shadow-sm"><i class="bi bi-plus-lg me-1"></i> Tambah Mitra/Vendor</a>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success border-0 bg-success text-white alert-dismissible fade show shadow-sm">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="tableMitra" class="table table-hover align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" width="5%">No</th>
                            <th width="30%">Nama Perusahaan / Mitra</th>
                            <th width="20%">NPWP</th>
                            <th width="20%">Informasi Bank</th>
                            <th class="text-center" width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Performa: baris diisi SERVER-SIDE via DataTables AJAX (route suppliers.index-data). --}}
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
            // Performa: tabel SERVER-SIDE (pencarian/urut/paginate di DB).
            $('#tableMitra').DataTable({
                serverSide: true,
                processing: true,
                ajax: "{{ route('suppliers.index-data') }}",
                order: [],
                columns: [
                    { data: 0, orderable: false, searchable: false, className: 'text-center' },
                    { data: 1 },
                    { data: 2 },
                    { data: 3, orderable: false },
                    { data: 4, orderable: false, searchable: false, className: 'text-center' }
                ],
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
                }
            });
        });
    </script>
@endpush
