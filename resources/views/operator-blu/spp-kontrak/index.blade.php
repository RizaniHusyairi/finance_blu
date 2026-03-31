@extends('layouts.app')

@section('title', 'Antrean Pembuatan SPP Kontrak')

@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
    <x-page-title title="Antrean Pembuatan SPP Kontrak" subtitle="Taskbox Operator BLU" />

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

    @if($errors->any())
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show">
            <ul class="text-white mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 bg-info bg-gradient mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <p class="text-white text-opacity-75 fw-semibold mb-2">Siap Diterbitkan SPP</p>
                    <h2 class="fw-bold text-white mb-0">{{ $tagihans->count() }}</h2>
                </div>
                <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center p-3">
                    <i class="bi bi-check2-circle fs-1 text-white"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card rounded-4">
        <div class="card-header bg-transparent border-bottom-0 pt-4 px-4">
            <h5 class="mb-1 fw-bold">Daftar Tagihan Siap Proses SPP</h5>
            <p class="text-muted mb-0">Hanya menampilkan tagihan kontrak yang sudah diverifikasi PPK dan berstatus <span class="fw-semibold">READY_FOR_SPP</span>.</p>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table id="spp-kontrak-table" class="table table-hover align-middle mb-0" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">No</th>
                            <th style="width: 165px;">Waktu Verifikasi PPK</th>
                            <th>No. BAST &amp; Pekerjaan</th>
                            <th style="width: 220px;">Vendor / Mitra</th>
                            <th style="width: 190px;">Nilai Netto (Cair)</th>
                            <th style="width: 220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tagihans as $tagihan)
                            <tr>
                                <td class="text-center fw-semibold">{{ $loop->iteration }}</td>
                                <td data-order="{{ optional($tagihan->waktu_verifikasi_ppk)->timestamp }}">
                                    <div class="fw-bold">{{ optional($tagihan->waktu_verifikasi_ppk)->format('d M Y') ?? '-' }}</div>
                                    <small class="text-muted">{{ optional($tagihan->waktu_verifikasi_ppk)->format('H:i') ?? '-' }}</small>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary mb-1">{{ $tagihan->nomor_tagihan }}</div>
                                    <div class="text-muted small">
                                        {{ \Illuminate\Support\Str::limit(optional(optional(optional($tagihan->detailKontrak)->termin)->kontrak)->nama_pekerjaan ?? '-', 50) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">
                                        {{ optional(optional(optional($tagihan->detailKontrak)->termin)->kontrak->vendor)->nama_perusahaan ?? '-' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-success fs-6">
                                        Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('operator.spp.kontrak.show', $tagihan->id) }}"
                                       class="btn btn-outline-primary w-100">
                                        [🔍 Detail &amp; Periksa]
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Belum ada tagihan kontrak yang siap diterbitkan SPP.
                                </td>
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
    <script>
        $(document).ready(function () {
            $('#spp-kontrak-table').DataTable({
                order: [[1, 'desc']],
                pageLength: 10,
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    infoEmpty: "Tidak ada data",
                    zeroRecords: "Tidak ditemukan data yang sesuai",
                    paginate: {
                        previous: "Sebelumnya",
                        next: "Berikutnya"
                    }
                }
            });
        });
    </script>
@endpush
