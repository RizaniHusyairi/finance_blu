@extends('layouts.app')
@section('title')
    Master Data Uang Harian
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Master Data" subtitle="Uang Harian" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 text-uppercase">Daftar Uang Harian Perjaldin</h6>
        <a href="{{ route('master-uang-harian-perjaldin.create') }}" class="btn btn-primary"><i class="bi bi-plus"></i> Tambah Data</a>
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
                            <th>Provinsi</th>
                            <th>Luar Kota</th>
                            <th>Dalam Kota > 8 Jam</th>
                            <th>Diklat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->provinsi }}</td>
                                <td>Rp {{ number_format($item->luar_kota, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($item->dalam_kota_lebih_8_jam, 0, ',', '.') }}</td>
                                <td>{{ $item->diklat ? 'Rp ' . number_format($item->diklat, 0, ',', '.') : '-' }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="{{ route('master-uang-harian-perjaldin.edit', $item->id) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil-fill"></i> Edit</a>
                                        <form action="{{ route('master-uang-harian-perjaldin.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
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
