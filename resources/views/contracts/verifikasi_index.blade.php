@extends('layouts.app')
@section('title', 'Verifikasi Perikatan & Draft SPK')

@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div>
        <h4 class="mb-0 fw-bold">Verifikasi Perikatan & Draft SPK</h4>
        <p class="text-muted mb-0 small">Daftar SPK yang diajukan oleh Pejabat Pengadaan untuk direview</p>
    </div>
    
    <div>
        <form method="GET" action="{{ route('contracts.verifikasi') }}" class="d-flex align-items-center gap-2">
            <label class="text-muted small fw-bold text-nowrap"><i class="bi bi-funnel"></i> Status:</label>
            <select name="status" class="form-select border-0 shadow-sm badge bg-primary text-start fs-6 p-2 pe-4" style="min-width: 240px; border-radius: 8px; cursor:pointer;" onchange="this.form.submit()">
                <option value="ALL" class="bg-white text-dark" {{ $filter == 'ALL' ? 'selected' : '' }}>Semua Draft (Tertunda & Revisi)</option>
                <option value="PENDING_PPK" class="bg-white text-dark" {{ $filter == 'PENDING_PPK' ? 'selected' : '' }}>Menunggu Review PPK</option>
                <option value="REVISI" class="bg-white text-dark" {{ $filter == 'REVISI' ? 'selected' : '' }}>Sudah Direvisi Pejabat</option>
            </select>
        </form>
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
        <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="tableVerifikasi">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th width="30%">Nomor SPK & Pekerjaan</th>
                        <th width="20%">Vendor Pelaksana</th>
                        <th width="15%">Nilai Kontrak</th>
                        <th width="15%">Beban DIPA</th>
                        <th width="15%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contracts as $k)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>
                            <span class="fw-bold text-dark fs-6">{{ $k->nomor_spk }}</span><br>
                            <small class="text-muted"><i class="bi bi-briefcase me-1"></i>{{ Str::limit($k->nama_pekerjaan, 50) }}</small>
                            @if($k->status_kontrak == 'REVISI')
                                <br><span class="badge bg-danger mt-1"><i class="bi bi-arrow-repeat"></i> KEMBALI DARI REVISI</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-light p-2 rounded-circle">
                                    <i class="bi bi-building text-primary"></i>
                                </div>
                                <span class="fw-medium text-dark">{{ $k->vendor->nama_perusahaan ?? 'N/A' }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="fw-bold text-success fs-6">Rp {{ number_format($k->nilai_total_kontrak, 0, ',', '.') }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border border-secondary shadow-sm font-monospace px-2 py-1">{{ $k->dipa->nomor_dipa ?? 'N/A' }}</span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('contracts.verifikasi.show', $k->id) }}" class="btn btn-primary btn-sm rounded-pill shadow-sm fw-bold px-3">
                                <i class="bi bi-eye me-1"></i> Review & Verifikasi
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
            $('#tableVerifikasi').DataTable({
                language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
                order: [[ 0, "asc" ]]
            });
        });
    </script>
@endpush
