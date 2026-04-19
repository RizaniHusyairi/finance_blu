@extends('layouts.app')
@section('title')
    Daftar Honorarium
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    @php
        $editableStatuses = ['DRAFT', 'DITOLAK_PPK'];
        $pendingStatuses = ['PENDING_PPK'];
        $approvedStatuses = ['DISETUJUI_PPK', 'PROSES_SPP', 'SPP_TERBIT'];
        $rejectedStatuses = ['DITOLAK_PPK'];
    @endphp
    <x-page-title title="Manajemen Honor" subtitle="Daftar Honorarium" />

    {{-- Summary Cards --}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-3 mb-4">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-primary text-white">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                        <i class="bi bi-stack fs-4 text-white"></i>
                    </div>
                    <div>
                        <div class="text-white-50 small mb-1">Total Pengajuan</div>
                        <h4 class="fw-bold mb-0 text-white">{{ $tagihans->count() }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-secondary text-white">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                        <i class="bi bi-pencil-square fs-4 text-white"></i>
                    </div>
                    <div>
                        <div class="text-white-50 small mb-1">Draft</div>
                        <h4 class="fw-bold mb-0 text-white">{{ $tagihans->where('status', 'DRAFT')->count() }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-warning text-dark">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-dark bg-opacity-10 d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                        <i class="bi bi-hourglass-split fs-4 text-dark"></i>
                    </div>
                    <div>
                        <div class="text-dark small mb-1" style="opacity:.7">Menunggu Verifikasi</div>
                        <h4 class="fw-bold mb-0">{{ $tagihans->whereIn('status', $pendingStatuses)->count() }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color:#20c997;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                        <i class="bi bi-check-circle fs-4 text-white"></i>
                    </div>
                    <div>
                        <div class="text-white-50 small mb-1">Disetujui</div>
                        <h4 class="fw-bold mb-0 text-white">{{ $tagihans->whereIn('status', $approvedStatuses)->count() }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-danger text-white">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                        <i class="bi bi-arrow-counterclockwise fs-4 text-white"></i>
                    </div>
                    <div>
                        <div class="text-white-50 small mb-1">Revisi / Ditolak</div>
                        <h4 class="fw-bold mb-0 text-white">{{ $tagihans->whereIn('status', $rejectedStatuses)->count() }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Header & Actions --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="mb-0 text-uppercase fw-bold"><i class="bi bi-list-ul me-1"></i> Daftar Tagihan Honorarium</h6>
        <div class="d-flex gap-2">
            <a href="{{ route('honorarium.create') }}" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> Tambah Honorarium
            </a>
        </div>
    </div>
    <hr>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show py-2">
            <div class="text-white"><i class="bi bi-check-circle me-1"></i> {{ session('success') }}</div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show py-2">
            <div class="text-white"><i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}</div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width:50px;">No</th>
                            <th>No. Tagihan & Uraian</th>
                            <th>Tanggal</th>
                            <th>Penerima</th>
                            <th>Total Bruto</th>
                            <th>PPh</th>
                            <th>Total Netto</th>
                            <th>Status</th>
                            <th style="width:160px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tagihans as $item)
                            @php
                                $locked = !in_array($item->status, $editableStatuses);
                                $penerima = $item->detailHonorarium->take(3);
                                $sisaPenerima = $item->detailHonorarium->count() - 3;
                            @endphp
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>
                                    <strong class="text-primary">{{ $item->nomor_tagihan }}</strong><br>
                                    <small class="text-muted">{{ Str::limit($item->deskripsi, 50) }}</small>
                                </td>
                                <td class="text-nowrap">
                                    <small>{{ $item->created_at?->format('d M Y') ?? '-' }}</small>
                                </td>
                                <td>
                                    @foreach($penerima as $detail)
                                        <span class="badge bg-light text-dark border mb-1" style="font-size:11px;">{{ $detail->nama_personel ?? '-' }}</span>
                                    @endforeach
                                    @if($sisaPenerima > 0)
                                        <span class="badge bg-primary mb-1" style="font-size:10px;">+{{ $sisaPenerima }} lainnya</span>
                                    @endif
                                    @if($item->detailHonorarium->isEmpty())
                                        <span class="text-muted small">Belum ada penerima</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">Rp {{ number_format($item->total_bruto, 0, ',', '.') }}</td>
                                <td class="text-nowrap text-danger">Rp {{ number_format($item->total_potongan, 0, ',', '.') }}</td>
                                <td class="text-nowrap fw-bold text-success">Rp {{ number_format($item->total_netto, 0, ',', '.') }}</td>
                                <td>
                                    @switch($item->status)
                                        @case('DRAFT')
                                            <span class="badge bg-secondary"><i class="bi bi-pencil-square me-1"></i>Draft</span>
                                            @break
                                        @case('PENDING_PPK')
                                            <span class="badge bg-primary"><i class="bi bi-hourglass-split me-1"></i>Menunggu PPK</span>
                                            @break
                                        @case('DISETUJUI_PPK')
                                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Disetujui PPK</span>
                                            @break
                                        @case('DITOLAK_PPK')
                                            <span class="badge bg-warning text-dark"><i class="bi bi-arrow-counterclockwise me-1"></i>Dikembalikan</span>
                                            @php $lastLog = $item->logs->first(); @endphp
                                            @if($lastLog && $lastLog->catatan)
                                                <div class="mt-1 p-2 bg-warning bg-opacity-10 border border-warning rounded small text-dark">
                                                    <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                                                    {{ Str::limit($lastLog->catatan, 80) }}
                                                </div>
                                            @endif
                                            @break
                                        @case('PROSES_SPP')
                                        @case('SPP_TERBIT')
                                            <span class="badge bg-info text-dark"><i class="bi bi-file-earmark-check me-1"></i>{{ $item->status }}</span>
                                            @break
                                        @default
                                            <span class="badge bg-dark">{{ $item->status }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <a href="{{ route('honorarium.show', $item->id) }}" class="btn btn-sm btn-outline-info" title="Lihat Detail">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                        @if(!$locked)
                                            <a href="{{ route('honorarium.edit', $item->id) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" title="Hapus"
                                                onclick="deleteHonorarium('{{ route('honorarium.destroy', $item->id) }}')">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        @endif
                                        @if(in_array($item->status, ['DISETUJUI_PPK', 'PROSES_SPP', 'SPP_TERBIT']))
                                            <a href="{{ route('honorarium.pdf-nominatif', $item->id) }}" target="_blank" class="btn btn-sm btn-outline-danger" title="Cetak Nominatif">
                                                <i class="bi bi-file-earmark-pdf"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                        <h6 class="text-muted mb-1">Belum ada pengajuan honorarium.</h6>
                                        <p class="text-muted small mb-2">Mulai buat pengajuan honorarium pertama Anda.</p>
                                        <a href="{{ route('honorarium.create') }}" class="btn btn-sm btn-success">
                                            <i class="bi bi-plus-lg"></i> Tambah Honorarium
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Global Delete Form --}}
    <form id="deleteForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('#example').DataTable({
                "columnDefs": [{ "orderable": false, "targets": [8] }],
                "language": {
                    "search": "Cari:",
                    "lengthMenu": "Tampilkan _MENU_ data",
                    "info": "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    "emptyTable": "Tidak ada data",
                    "zeroRecords": "Tidak ditemukan data yang cocok",
                    "paginate": { "first": "Awal", "last": "Akhir", "next": "›", "previous": "‹" }
                }
            });
        });

        function deleteHonorarium(url) {
            if (confirm('Hapus data honorarium ini? Tindakan ini tidak dapat dibatalkan.')) {
                document.getElementById('deleteForm').action = url;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
@endpush