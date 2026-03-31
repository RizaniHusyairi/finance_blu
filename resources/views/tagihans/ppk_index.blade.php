@extends('layouts.app')
@section('title', 'Verifikasi Tagihan Kontrak (BAST)')

@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
    {{-- Header --}}
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h4 class="mb-1 fw-bold text-dark">Verifikasi Tagihan Kontrak</h4>
            <div class="text-muted small">Periksa kesesuaian BAST dan Invoice sebelum menyetujui penerbitan SPP.</div>
        </div>
    </div>

    {{-- KPI Card Tunggal --}}
    <div class="row mb-4">
        <div class="col-md-6 col-lg-5 col-xl-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 border-start border-warning border-4">
                <div class="card-body p-4 d-flex justify-content-between align-items-center bg-white rounded-end-4">
                    <div>
                        <h6 class="text-muted fw-bold mb-2">Menunggu Verifikasi Anda</h6>
                        <h2 class="fw-black mb-0 text-dark">{{ $tagihans->count() }} <span class="fs-6 fw-normal text-muted ms-1">Berkas Tagihan</span></h2>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 60px; height: 60px;">
                        <i class="bi bi-hourglass-split fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Utama (Single Table) --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="tabelVerifikasiTagihan" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center text-muted" width="5%">No.</th>
                            <th width="15%">Waktu Pengajuan</th>
                            <th width="30%">No. Tagihan & Pekerjaan</th>
                            <th width="20%">Vendor & Termin</th>
                            <th width="18%">Nilai Tagihan</th>
                            <th class="text-center" width="12%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tagihans as $tagihan)
                            <tr>
                                <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                
                                {{-- Waktu Pengajuan --}}
                                <td>
                                    <div class="fw-bold text-dark">{{ \Carbon\Carbon::parse($tagihan->created_at)->format('d M Y') }}</div>
                                    <div class="small text-muted mt-1"><i class="bi bi-clock me-1"></i>{{ \Carbon\Carbon::parse($tagihan->created_at)->format('H:i') }} WIB</div>
                                </td>
                                
                                {{-- No Tagihan & Pekerjaan --}}
                                <td>
                                    <div class="fw-bold text-primary">{{ $tagihan->nomor_tagihan }}</div>
                                    <div class="small text-muted mt-1"><i class="bi bi-briefcase me-1"></i>{{ Str::limit($tagihan->detailKontrak->termin->kontrak->nama_pekerjaan ?? 'Pekerjaan Tidak Diketahui', 40, '...') }}</div>
                                </td>
                                
                                {{-- Vendor & Termin --}}
                                <td>
                                    <div class="fw-bold text-dark">{{ $tagihan->detailKontrak->termin->kontrak->vendor->nama_perusahaan ?? 'Vendor Tidak Diketahui' }}</div>
                                    <div class="mt-1">
                                        <span class="badge bg-light text-dark border"><i class="bi bi-diagram-3 me-1"></i>Termin ke-{{ $tagihan->detailKontrak->termin->termin_ke ?? '-' }} ({{ $tagihan->detailKontrak->termin->persentase ?? 0 }}%)</span>
                                    </div>
                                </td>
                                
                                {{-- Nilai Tagihan --}}
                                <td>
                                    <div class="fw-bold text-success fs-5">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</div>
                                    <div class="small text-muted mt-1">Bruto: Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</div>
                                </td>
                                
                                {{-- Aksi (Center) --}}
                                <td class="text-center">
                                    <a href="{{ route('ppk.tagihan.kontrak.verify', $tagihan->id) }}" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm rounded-pill">
                                        <i class="bi bi-search me-1"></i> Periksa
                                    </a>
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
            $('#tabelVerifikasiTagihan').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
                },
                "order": [[ 1, "desc" ]], // Mengurutkan berdasarkan kolom Waktu Pengajuan (Index 1) secara Descending
                "columnDefs": [
                    { "orderable": false, "targets": [0, 5] }, // Men-disable urutan/sorting pada kolom No dan Aksi
                    { "type": "date", "targets": 1 }
                ]
            });
        });
    </script>
@endpush
