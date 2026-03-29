@extends('layouts.app')
@section('title')
    Riwayat Verifikasi Perjaldin
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Verifikasi" subtitle="Riwayat (Log) Perjalanan Dinas" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 text-uppercase">Log Riwayat Keputusan Verifikasi</h6>
    </div>
    <hr>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>NO</th>
                            <th>Nama (NIP)</th>
                            <th>No SPPD</th>
                            <th>Tujuan</th>
                            <th>Status Verifikasi</th>
                            <th>Catatan / Alasan</th>
                            <th>Waktu Eksekusi</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pejabats as $index => $pejabat)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $pejabat->nama_pejabat }}</strong><br>
                                    <small class="text-muted">{{ $pejabat->nip ?? '-' }}</small>
                                </td>
                                <td>{{ $pejabat->no_sppd }}</td>
                                <td>{{ Str::limit($pejabat->tujuan, 30) }}</td>
                                <td>
                                    @if($pejabat->status == 'Disetujui PPK')
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> {{ $pejabat->status }}</span>
                                    @else
                                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> {{ $pejabat->status }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($pejabat->status == 'Ditolak' && $pejabat->alasan_penolakan)
                                        <span class="text-danger small">{{ $pejabat->alasan_penolakan }}</span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($pejabat->updated_at)->format('d M Y H:i:s') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('perjaldin-blu.show', $pejabat->pejabat_id) }}" class="btn btn-sm btn-outline-info" title="Lihat Detail Lengkap">
                                        <i class="bi bi-eye-fill"></i> View
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
            $('#example').DataTable({
                "order": [[ 6, "desc" ]] // Sort by Waktu Eksekusi desc by default
            });
        });
    </script>
@endpush
