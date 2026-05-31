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
                        @foreach ($suppliers as $supplier)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>
                                    <span class="fw-bold">{{ $supplier->nama_perusahaan }}</span><br>
                                    <small class=""><i class="bi bi-person me-1"></i>Dir: {{ $supplier->nama_direktur ?: '-' }}</small>
                                </td>
                                <td>
                                    <span class="font-monospace">{{ $supplier->npwp ?: 'Belum Ada' }}</span>
                                </td>
                                <td>
                                    @php
                                        $rek = $supplier->rekening->first();
                                    @endphp
                                    @if($rek)
                                        <span class="fw-bold">{{ $rek->nama_bank }}</span><br>
                                        <small class=""><i class="bi bi-credit-card me-1"></i>{{ $rek->nomor_rekening }}</small>
                                    @else
                                        <span class="badge bg-secondary">Belum disetel</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group shadow-sm">
                                        <button type="button" class="btn btn-sm btn-light text-primary" data-bs-toggle="modal" data-bs-target="#detailModal{{ $supplier->id }}" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn btn-sm btn-light text-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Peringatan: Menghapus data vendor juga akan menghapus/merusak relasi SPP yang sedang berjalan. Apakah Anda yakin ingin menghapus data ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                    
                                    {{-- Modal Detail --}}
                                    <div class="modal fade" id="detailModal{{ $supplier->id }}" tabindex="-1" aria-labelledby="detailModalLabel{{ $supplier->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-centered text-start">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <div class="modal-header border-bottom-0 pb-0">
                                                    <h5 class="modal-title fw-bold" id="detailModalLabel{{ $supplier->id }}">
                                                        <i class="bi bi-building me-2 text-primary"></i>Detail Mitra/Vendor
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">Identitas Utama</h6>
                                                    <div class="row mb-3">
                                                        <div class="col-md-4 ">Nama Perusahaan</div>
                                                        <div class="col-md-8 fw-bold">{{ $supplier->nama_perusahaan }}</div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-md-4 ">Direktur / PIC</div>
                                                        <div class="col-md-8">{{ $supplier->nama_direktur ?: '-' }}</div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-md-4 ">Tipe Supplier</div>
                                                        <div class="col-md-8">{{ $supplier->tipe_supplier }}</div>
                                                    </div>
                                                    <div class="row mb-4">
                                                        <div class="col-md-4 ">NPWP</div>
                                                        <div class="col-md-8 font-monospace">{{ $supplier->npwp ?: 'Belum Ada' }}</div>
                                                    </div>

                                                    <h6 class="text-info fw-bold mb-3 border-bottom pb-2">Kontak & Alamat</h6>
                                                    <div class="row mb-3">
                                                        <div class="col-md-4 ">Email</div>
                                                        <div class="col-md-8">{{ $supplier->email ?: '-' }}</div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-md-4 ">No. Telepon</div>
                                                        <div class="col-md-8">{{ $supplier->no_telepon ?: '-' }}</div>
                                                    </div>
                                                    <div class="row mb-4">
                                                        <div class="col-md-4 ">Alamat Lengkap</div>
                                                        <div class="col-md-8">{{ $supplier->alamat ?: '-' }}</div>
                                                    </div>

                                                    <h6 class="text-success fw-bold mb-3 border-bottom pb-2">Rekening Bank Terdaftar</h6>
                                                    @if($rek)
                                                        <div class="row mb-2">
                                                            <div class="col-md-4 ">Nama Bank</div>
                                                            <div class="col-md-8">{{ $rek->nama_bank }}</div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-md-4 ">Nomor Rekening</div>
                                                            <div class="col-md-8 font-monospace">{{ $rek->nomor_rekening }}</div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-md-4 ">Nama Pemilik (A.N.)</div>
                                                            <div class="col-md-8">{{ $rek->nama_rekening }}</div>
                                                        </div>
                                                    @else
                                                        <p class=""><i class="bi bi-exclamation-triangle me-1"></i> Data rekening belum disetel untuk mitra ini.</p>
                                                    @endif
                                                </div>
                                                <div class="modal-footer border-top-0 pt-0">
                                                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Tutup</button>
                                                </div>
                                            </div>
                                        </div>
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
            $('#tableMitra').DataTable({
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
                }
            });
        });
    </script>
@endpush
