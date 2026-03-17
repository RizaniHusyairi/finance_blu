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

    {{-- Cards Summary --}}
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-6 g-3 mb-4">
        <div class="col">
            <div class="card rounded-4 mb-0 h-100">
                <div class="card-body p-3">
                    <p class="mb-1 small">Total Kontrak Aktif</p>
                    <h5 class="mb-0 fw-bold">{{ $totalAktif }}</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 bg-info text-white mb-0 border-0 h-100">
                <div class="card-body p-3">
                    <p class="mb-1 small">Menunggu Persetujuan PPK</p>
                    <h5 class="mb-0 fw-bold">{{ $contracts->where('status', 'Menunggu Persetujuan PPK')->count() }}</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 mb-0 h-100">
                <div class="card-body p-3">
                    <p class="mb-1 small">Total Kontrak Selesai</p>
                    <h5 class="mb-0 fw-bold">{{ $totalSelesai }}</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 mb-0 h-100">
                <div class="card-body p-3">
                    <p class="mb-1 small">Total Nilai Seluruh Kontrak</p>
                    <h5 class="mb-0 fw-bold">Rp {{ number_format($totalNilaiAll/1000000, 1, ',', '.') }} Jt</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 bg-warning mb-0 border-0 h-100">
                <div class="card-body p-3">
                    <p class="mb-1 small">Hampir Habis Nilainya (&lt;20%)</p>
                    <h5 class="mb-0 fw-bold">{{ $hampirHabisNilai }}</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 bg-danger text-white mb-0 border-0 h-100">
                <div class="card-body p-3">
                    <p class="mb-1 small">Hampir Habis Masa (&lt;30 Hr)</p>
                    <h5 class="mb-0 fw-bold">{{ $hampirHabisMasa }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nomor/Uraian Kontrak</th>
                            <th>Nama Supplier/Mitra</th>
                            <th>Tanggal Kontrak</th>
                            <th class="text-end">Nilai Awal</th>
                            <th class="text-end">Realisasi</th>
                            <th class="text-end">Sisa Kontrak</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contracts as $index => $contract)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <div class="fw-bold text-primary">{{ $contract->contract_number }}</div>
                                    <div class="small text-muted text-wrap" style="max-width: 200px;">{{ Str::limit($contract->description, 50) }}</div>
                                </td>
                                <td>{{ $contract->supplier->name ?? '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($contract->date)->format('d/m/Y') }}</td>
                                <td class="text-end">Rp {{ number_format($contract->total_amount, 0, ',', '.') }}</td>
                                <td class="text-end text-success">Rp {{ number_format($contract->realisasi_pembayaran, 0, ',', '.') }}</td>
                                <td class="text-end text-warning">Rp {{ number_format($contract->sisa_kontrak, 0, ',', '.') }}</td>
                                <td>
                                    @php
                                        $sc = match($contract->status) {
                                            'Draft' => 'secondary',
                                            'Active', 'Aktif' => 'success',
                                            'Menunggu Persetujuan PPK' => 'info',
                                            'Ditolak PPK' => 'danger',
                                            'Completed', 'Selesai' => 'primary',
                                            'Dibatalkan', 'Cancelled' => 'danger',
                                            default => 'warning'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $sc }}">
                                        {{ $contract->status }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="{{ route('contracts.show', $contract->id) }}" class="btn btn-sm btn-outline-info" title="Detail"><i class="bi bi-eye-fill"></i> Detail</a>
                                        <a href="{{ route('contracts.edit', $contract->id) }}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil-fill"></i> Edit</a>
                                        <!-- Note: Real implementation for tambahkan adendum can pop a modal or redirect to detail page segment, linking to detail page for now -->
                                        <a href="{{ route('contracts.show', $contract->id) }}#adendum" class="btn btn-sm btn-outline-primary" title="Tambah Adendum"><i class="bi bi-journal-plus"></i> Adendum</a>
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
