@extends('layouts.app')
@section('title')
    Manajemen Perjaldin
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Manajemen" subtitle="Perjaldin" />

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-white">
                <div class="card-body p-3">
                    <h6 class="card-title text-muted fw-normal mb-2">Total Semua Dokumen</h6>
                    <h3 class="fw-bold mb-0">{{ $perjaldins->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #0dcaf0;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-2">Draf & Revisi</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $perjaldins->whereIn('status', ['Draft', 'Revisi'])->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-warning text-dark">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-2">Proses Verifikasi</h6>
                    <h3 class="fw-bold mb-0">{{ $perjaldins->where('status', 'Proses Verifikasi')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #20c997;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-2">Selesai (Siap Cair)</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $perjaldins->whereIn('status', ['Disetujui', 'Proses SPP', 'SPP Terbit'])->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 text-uppercase fw-bold">Daftar Individu Perjaldin</h6>
        <div class="d-flex gap-2">
            <a href="{{ route('perjaldins.create') }}" class="btn btn-success"><i class="bi bi-plus-lg"></i> Tambah Perjaldin</a>
            <button type="button" class="btn btn-primary" onclick="submitBulk()"><i class="bi bi-send"></i> Ajukan Perjaldin</button>
        </div>
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
            <div class="text-white">Ada kesalahan saat mengajukan data.</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form id="bulkForm" action="{{ route('perjaldins.bulk-submit') }}" method="POST">
                @csrf
                <div class="table-responsive">
                    <table id="example" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                </th>
                                <th>Uraian</th>
                                <th>Peserta (Pejabat)</th>
                                <th>Status & Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($perjaldins as $perjaldin)
                                <tr>
                                    <td class="text-center">
                                        @if($perjaldin->status == 'Draft' || $perjaldin->status == 'Revisi')
                                            <input class="form-check-input row-checkbox" type="checkbox" name="perjaldin_ids[]" value="{{ $perjaldin->perjaldin_id }}">
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $perjaldin->uraian }}</strong><br>
                                        <small class="text-muted">No BAST: {{ $perjaldin->no_bast ?: '-' }}</small>
                                    </td>
                                    <td>
                                        @foreach($perjaldin->pejabats as $pejabat)
                                            <span class="badge bg-light text-dark border">{{ $pejabat->nama_pejabat }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        @if($perjaldin->status == 'Draft')
                                            <span class="badge bg-secondary">Draft</span>
                                        @elseif($perjaldin->status == 'Proses Verifikasi')
                                            <span class="badge bg-primary"><i class="bi bi-hourglass-split"></i> Proses Verifikasi</span>
                                            <div class="d-flex gap-1 mt-1 flex-wrap">
                                                @if($perjaldin->is_ppk_approved)
                                                    <span class="badge bg-success"><i class="bi bi-check"></i> PPK ✓</span>
                                                @else
                                                    <span class="badge bg-secondary">PPK: Pending</span>
                                                @endif
                                                @if($perjaldin->is_kasubag_approved)
                                                    <span class="badge bg-success"><i class="bi bi-check"></i> Kasubag ✓</span>
                                                @else
                                                    <span class="badge bg-secondary">Kasubag: Pending</span>
                                                @endif
                                            </div>
                                        @elseif($perjaldin->status == 'Revisi')
                                            <span class="badge bg-warning text-dark"><i class="bi bi-arrow-counterclockwise"></i> Revisi</span>
                                            @if($perjaldin->catatan_revisi)
                                                <div class="mt-1 p-2 bg-warning bg-opacity-10 border border-warning rounded small text-dark">
                                                    <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                                                    <strong>{{ $perjaldin->revisi_oleh }}:</strong> {{ $perjaldin->catatan_revisi }}
                                                </div>
                                            @endif
                                        @elseif($perjaldin->status == 'Disetujui')
                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Disetujui PPK & Kasubag</span>
                                        @elseif($perjaldin->status == 'Proses SPP' || $perjaldin->status == 'SPP Terbit')
                                            @php
                                                $latestSpp = $perjaldin->spps->sortByDesc('updated_at')->first();
                                                $lbl = 'Proses / SPP Terbit';
                                                $cls = 'bg-info text-dark';
                                                
                                                if ($latestSpp) {
                                                    if ($latestSpp->status_spp == 'Lunas') {
                                                        $lbl = 'Lunas (BKU)';
                                                        $cls = 'bg-success';
                                                    } elseif ($latestSpp->status_spp == 'SP2D Terbit') {
                                                        $lbl = 'SP2D Terbit';
                                                        $cls = 'bg-success';
                                                    } elseif (strpos($latestSpp->status_spp, 'NPI') !== false) {
                                                        $lbl = 'NPI Terbit';
                                                        $cls = 'bg-primary';
                                                    } elseif (strpos($latestSpp->status_spp, 'SPM') !== false) {
                                                        $lbl = 'SPM Terbit';
                                                        $cls = 'bg-primary';
                                                    }
                                                }
                                            @endphp
                                            <span class="badge {{ $cls }}"><i class="bi bi-file-earmark-check"></i> {{ $lbl }}</span>
                                            @if($latestSpp && $latestSpp->nomor_sp2d)
                                                <div class="small mt-1 text-muted">SP2D: {{ $latestSpp->nomor_sp2d }}</div>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                            @if($perjaldin->pejabats->count())
                                            <a href="{{ route('perjaldins.show', $perjaldin->pejabats->first()->pejabat_id) }}" class="btn btn-sm btn-outline-info"><i class="bi bi-eye-fill"></i></a>
                                            @endif
                                            @if($perjaldin->status == 'Draft' || $perjaldin->status == 'Revisi')
                                                <a href="{{ route('perjaldins.edit-perjaldin', $perjaldin->perjaldin_id) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil-fill"></i> Edit</a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deletePerjaldin('{{ route('perjaldins.destroy-perjaldin', $perjaldin->perjaldin_id) }}')"><i class="bi bi-trash-fill"></i></button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>

            <!-- Global Delete Form -->
            <form id="deleteForm" method="POST" style="display: none;">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
@endsection
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            var table = $('#example').DataTable({
                "columnDefs": [{ "orderable": false, "targets": [0, 4] }]
            });
            $('#selectAll').on('click', function () {
                var rows = table.rows({ 'search': 'applied' }).nodes();
                $('input[type="checkbox"].row-checkbox', rows).prop('checked', this.checked);
            });
            $('#example tbody').on('change', 'input[type="checkbox"].row-checkbox', function () {
                if (!this.checked) {
                    var el = $('#selectAll').get(0);
                    if (el && el.checked && ('indeterminate' in el)) el.indeterminate = true;
                }
            });
        });

        function submitBulk() {
            var checkedCount = $('.row-checkbox:checked').length;
            if (checkedCount === 0) { alert('Pilih minimal 1 perjaldin.'); return; }
            if (confirm('Ajukan ' + checkedCount + ' Perjaldin untuk verifikasi PPK & Kasubag?')) $('#bulkForm').submit();
        }

        function deletePerjaldin(url) {
            if (confirm('Hapus Perjaldin ini beserta semua data pejabatnya?')) {
                document.getElementById('deleteForm').action = url;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
@endpush