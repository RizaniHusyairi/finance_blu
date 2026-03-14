@extends('layouts.app')
@section('title')
    Daftar Transaksi
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Tagihan & Pembayaran" subtitle="Data Transaksi" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 text-uppercase">Daftar Transaksi (Tagihan/SPP)</h6>
        <a href="{{ route('transactions.create') }}" class="btn btn-primary"><i class="bi bi-plus"></i> Input Tagihan Baru</a>
    </div>
    <hr>
    
    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show">
            <div class="text-white">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>No Transaksi</th>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Uraian</th>
                            <th>Nilai Bruto</th>
                            <th>Status (State)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $t)
                            <tr>
                                <td>{{ $t->transaction_number }}</td>
                                <td>{{ \Carbon\Carbon::parse($t->date)->format('d/m/Y') }}</td>
                                <td><span class="badge bg-secondary">{{ $t->type }}</span></td>
                                <td>{{ Str::limit($t->description, 40) }}</td>
                                <td>Rp {{ number_format($t->amount, 0, ',', '.') }}</td>
                                <td>
                                    @php
                                        $statusClass = 'secondary';
                                        if($t->status == 'Draft') $statusClass = 'warning text-dark';
                                        elseif($t->status == 'Verified') $statusClass = 'info text-dark';
                                        elseif($t->status == 'Approved SPP' || $t->status == 'Approved SPM') $statusClass = 'primary';
                                        elseif($t->status == 'Paid SP2D') $statusClass = 'success';
                                        elseif($t->status == 'Rejected') $statusClass = 'danger';
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">{{ $t->status }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="{{ route('transactions.show', $t->id) }}" class="btn btn-sm btn-outline-info" title="Detail/Proses"><i class="bi bi-gear"></i> Proses</a>
                                        @if($t->status == 'Draft' || $t->status == 'Rejected')
                                            <a href="{{ route('transactions.edit', $t->id) }}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                            <form action="{{ route('transactions.destroy', $t->id) }}" method="POST" onsubmit="return confirm('Hapus transaksi ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash-fill"></i></button>
                                            </form>
                                        @endif
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
