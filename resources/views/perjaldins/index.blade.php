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
                    <h6 class="card-title text-muted fw-normal mb-2">Total Semua Tagihan</h6>
                    <h3 class="fw-bold mb-0">{{ $tagihans->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #0dcaf0;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-2">Draft & Revisi</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $tagihans->whereIn('status', ['DRAFT', 'REVISI_PPK', 'DITOLAK_PPK'])->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-warning text-dark">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-2">Menunggu Verifikasi PPK</h6>
                    <h3 class="fw-bold mb-0">{{ $tagihans->where('status', 'PENDING_PPK')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #20c997;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-2">Disetujui / Proses SPP</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $tagihans->whereIn('status', ['DISETUJUI_PPK', 'PROSES_SPP', 'SPP_TERBIT'])->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 text-uppercase fw-bold">Daftar Tagihan Perjaldin</h6>
        <div class="d-flex gap-2">
            <a href="{{ route('perjaldins.create') }}" class="btn btn-success"><i class="bi bi-plus-lg"></i> Tambah Perjaldin</a>
            <button type="button" class="btn btn-primary" onclick="submitBulk()"><i class="bi bi-send"></i> Ajukan ke PPK</button>
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
            <div class="text-white">{{ $errors->first() }}</div>
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
                                <th>No. Tagihan & Uraian</th>
                                <th>Peserta Perjaldin</th>
                                <th>Total Bruto</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tagihans as $tagihan)
                                <tr>
                                    <td class="text-center">
                                        @if(in_array($tagihan->status, ['DRAFT', 'REVISI_PPK', 'DITOLAK_PPK']))
                                            <input class="form-check-input row-checkbox" type="checkbox" name="tagihan_ids[]" value="{{ $tagihan->id }}">
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $tagihan->nomor_tagihan }}</strong><br>
                                        <small>{{ $tagihan->deskripsi }}</small>
                                    </td>
                                    <td>
                                        @foreach($tagihan->detailPerjaldin as $detail)
                                            <span class="badge bg-light text-dark border">{{ $detail->pegawai->nama_lengkap ?? '-' }}</span>
                                        @endforeach
                                    </td>
                                    <td class="fw-bold">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</td>
                                    <td>
                                        @switch($tagihan->status)
                                            @case('DRAFT')
                                                <span class="badge bg-secondary">Draft</span>
                                                @break
                                            @case('PENDING_PPK')
                                                <span class="badge bg-primary"><i class="bi bi-hourglass-split"></i> Menunggu PPK</span>
                                                @break
                                            @case('REVISI_PPK')
                                            @case('DITOLAK_PPK')
                                                <span class="badge bg-warning text-dark"><i class="bi bi-arrow-counterclockwise"></i> Revisi PPK</span>
                                                @php $lastLog = $tagihan->logs->first(); @endphp
                                                @if($lastLog && $lastLog->catatan)
                                                    <div class="mt-1 p-2 bg-warning bg-opacity-10 border border-warning rounded small text-dark">
                                                        <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                                                        {{ $lastLog->catatan }}
                                                    </div>
                                                @endif
                                                @break
                                            @case('DISETUJUI_PPK')
                                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Disetujui PPK</span>
                                                @break
                                            @case('PROSES_SPP')
                                            @case('SPP_TERBIT')
                                                <span class="badge bg-info text-dark"><i class="bi bi-file-earmark-check"></i> {{ $tagihan->status }}</span>
                                                @break
                                            @default
                                                <span class="badge bg-dark">{{ $tagihan->status }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                            <a href="{{ route('perjaldins.show', $tagihan->id) }}" class="btn btn-sm btn-outline-info"><i class="bi bi-eye-fill"></i></a>
                                            @if(in_array($tagihan->status, ['DRAFT', 'REVISI_PPK', 'DITOLAK_PPK']))
                                                <a href="{{ route('perjaldins.edit-perjaldin', $tagihan->id) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil-fill"></i> Edit</a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deletePerjaldin('{{ route('perjaldins.destroy-perjaldin', $tagihan->id) }}')"><i class="bi bi-trash-fill"></i></button>
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
                "columnDefs": [{ "orderable": false, "targets": [0, 5] }]
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

            $('#bulkForm').on('submit', function(e) {
                e.preventDefault();
                let checked = table.$('input[type="checkbox"].row-checkbox:checked');
                
                if (checked.length === 0) {
                    alert('Pilih minimal 1 tagihan perjaldin.');
                    return false;
                }

                if (confirm('Ajukan ' + checked.length + ' Perjaldin untuk verifikasi PPK?')) {
                    $('.dynamic-hidden-ids').remove();
                    
                    checked.each(function() {
                        $('#bulkForm').append(
                            $('<input>')
                                .attr('type', 'hidden')
                                .attr('name', 'tagihan_ids[]')
                                .addClass('dynamic-hidden-ids')
                                .val($(this).val())
                        );
                    });
                    
                    this.submit();
                }
            });
        });

        function submitBulk() {
            $('#bulkForm').trigger('submit');
        }

        function deletePerjaldin(url) {
            if (confirm('Hapus Perjaldin ini beserta semua data pesertanya?')) {
                document.getElementById('deleteForm').action = url;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
@endpush