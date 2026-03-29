@extends('layouts.app')
@section('title')
    Verifikasi Perjalanan Dinas
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Verifikasi" subtitle="Perjalanan Dinas" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 text-uppercase">Daftar Pengajuan Perjaldin</h6>
    </div>
    <hr>
    
    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show">
            <ul class="text-white mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>NO</th>
                            <th>Nama (NIP)</th>
                            <th>No SPT</th>
                            <th>No SPPD</th>
                            <th>Tujuan</th>
                            <th>Keberangkatan</th>
                            <th>Lama</th>
                            <th>Tanggal Pengajuan</th>
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
                                <td>{{ $pejabat->no_spt }}</td>
                                <td>{{ $pejabat->no_sppd }}</td>
                                <td>{{ $pejabat->tujuan }}</td>
                                <td>{{ \Carbon\Carbon::parse($pejabat->tanggal_berangkat)->format('d M Y') }}</td>
                                <td>{{ $pejabat->lama_perjalanan_dinas }} Hari</td>
                                <td>{{ \Carbon\Carbon::parse($pejabat->updated_at)->format('d M Y H:i') }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 justify-content-center">
                                        <!-- Lihat Detail -->
                                        <a href="{{ route('perjaldin-blu.show', $pejabat->pejabat_id) }}" class="btn btn-sm btn-outline-info" title="Lihat Detail">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>

                                        <!-- Approve -->
                                        <form action="{{ route('perjaldin-blu.approve', $pejabat->pejabat_id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui data Perjaldin ini?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Setujui">
                                                <i class="bi bi-check-circle-fill"></i> Setujui
                                            </button>
                                        </form>

                                        <!-- Reject Trigger Modal -->
                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Tolak" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $pejabat->pejabat_id }}">
                                            <i class="bi bi-x-circle-fill"></i> Tolak
                                        </button>
                                    </div>
                                    
                                    <!-- Reject Modal -->
                                    <div class="modal fade" id="rejectModal{{ $pejabat->pejabat_id }}" tabindex="-1" aria-labelledby="rejectModalLabel{{ $pejabat->pejabat_id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="rejectModalLabel{{ $pejabat->pejabat_id }}">Penolakan Perjaldin</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('perjaldin-blu.reject', $pejabat->pejabat_id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <p>Anda akan menolak pengajuan perjalanan dinas atas nama <strong>{{ $pejabat->nama_pejabat }}</strong>.</p>
                                                        <div class="mb-3">
                                                            <label for="alasan_penolakan" class="form-label">Silakan berikan Alasan Penolakan <span class="text-danger">*</span></label>
                                                            <textarea class="form-control" name="alasan_penolakan" rows="3" required placeholder="Contoh: Dokumen SPT tidak valid..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-danger">Tolak Pengajuan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- End Modal -->
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
