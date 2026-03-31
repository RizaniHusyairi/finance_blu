@extends('layouts.app')

@section('title')
    Data Honorarium
@endsection

@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
    <x-page-title title="Manajemen Honor" subtitle="Daftar Data Honorarium" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 text-uppercase">Data Honorarium</h6>
        <a href="{{ route('honorarium.create') }}" class="btn btn-primary">
            <i class="bi bi-plus"></i> Input Honorarium
        </a>
    </div>
    <hr>

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

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="example" class="table table-bordered table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No Tagihan</th>
                            <th>Uraian</th>
                            <th>Penerima</th>
                            <th>Total Bruto</th>
                            <th>PPh</th>
                            <th>Netto</th>
                            <th>Status</th>
                            <th width="200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tagihans as $item)
                            @php
                                $locked = in_array($item->status, ['PENDING_PPK', 'DISETUJUI_PPK', 'PROSES_SPP', 'SPP_TERBIT']);
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>{{ $item->nomor_tagihan }}</strong></td>
                                <td>{{ $item->deskripsi }}</td>
                                <td>
                                    @foreach($item->detailHonorarium as $detail)
                                        <span class="badge bg-light text-dark border">{{ $detail->nama_personel ?? '-' }}</span>
                                    @endforeach
                                </td>
                                <td>Rp {{ number_format($item->total_bruto, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($item->total_potongan, 0, ',', '.') }}</td>
                                <td class="fw-bold">Rp {{ number_format($item->total_netto, 0, ',', '.') }}</td>
                                <td>
                                    @switch($item->status)
                                        @case('DRAFT')
                                            <span class="badge bg-secondary">Draft</span>
                                            @break
                                        @case('PENDING_PPK')
                                            <span class="badge bg-primary">Menunggu PPK</span>
                                            @break
                                        @case('DISETUJUI_PPK')
                                            <span class="badge bg-success">Disetujui PPK</span>
                                            @break
                                        @case('DITOLAK_PPK')
                                            <span class="badge bg-warning text-dark">Dikembalikan</span>
                                            @break
                                        @default
                                            <span class="badge bg-info text-dark">{{ $item->status }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="{{ route('honorarium.show', $item->id) }}" class="btn btn-sm btn-info text-white">Lihat</a>
                                        @if(!$locked)
                                            <a href="{{ route('honorarium.edit', $item->id) }}" class="btn btn-sm btn-warning text-dark">Edit</a>
                                            <form action="{{ route('honorarium.destroy', $item->id) }}" method="POST"
                                                onsubmit="return confirm('Yakin mau hapus data honorarium ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-sm btn-warning" disabled>Edit</button>
                                            <button type="button" class="btn btn-sm btn-danger" disabled>Hapus</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">Belum ada data honorarium.</td>
                            </tr>
                        @endforelse
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