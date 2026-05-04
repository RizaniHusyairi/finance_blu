@extends('layouts.app')
@section('title')
    Tagihan Jasa (PNBP)
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h4 class="mb-0 fw-bold">Verifikasi Tagihan Jasa (PNBP)</h4>
            <p class="mb-0 small">Daftar tagihan jasa yang menunggu persetujuan Anda</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success text-white alert-dismissible fade show shadow-sm">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger text-white alert-dismissible fade show shadow-sm">
            <i class="bi bi-x-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="tableTagihanJasa" class="table table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="20%">No. Tagihan</th>
                            <th width="20%">Mitra</th>
                            <th width="20%">Total & Tgl Tagihan</th>
                            <th width="15%">Status</th>
                            <th width="20%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tagihans as $tagihan)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>
                                    <span class="fw-bold">{{ $tagihan->nomor_tagihan }}</span><br>
                                    @if($tagihan->nomor_kontrak)
                                    <small class="text-muted"><i class="bi bi-file-earmark-text me-1"></i>{{ $tagihan->nomor_kontrak }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-medium">{{ $tagihan->mitra->nama_pihak ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="fw-bold text-success">Rp {{ number_format($tagihan->total_tagihan, 0, ',', '.') }}</span><br>
                                    <small ><i class="bi bi-calendar-event me-1"></i>{{ \Carbon\Carbon::parse($tagihan->tanggal_tagihan)->format('d M Y') }}</small>
                                </td>
                                <td>
                                    @php
                                        $statusClass = match($tagihan->status) {
                                            'PUBLISHED', 'LUNAS' => 'bg-success',
                                            'DRAFT' => 'bg-secondary',
                                            'DITOLAK' => 'bg-danger',
                                            default => 'bg-warning text-dark',
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ str_replace('_', ' ', $tagihan->status) }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ route('verifikasi-tagihan-jasa.show', $tagihan->id) }}" class="btn btn-sm btn-light text-info border shadow-sm" title="Proses Verifikasi">
                                            <i class="bi bi-check2-square"></i> Proses Verifikasi
                                        </a>
                                        @if(in_array($tagihan->status, ['PUBLISHED', 'LUNAS', 'VERIFIKASI_KABANDARA']))
                                        <a href="{{ route('tagihan-jasa.pdf', $tagihan->id) }}" target="_blank" class="btn btn-sm btn-light text-danger border shadow-sm" title="Cetak PDF">
                                            <i class="bi bi-file-pdf"></i> PDF
                                        </a>
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
            $('#tableTagihanJasa').DataTable({
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
                }
            });
        });
    </script>
@endpush
