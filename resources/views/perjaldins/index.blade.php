@extends('layouts.app')
@section('title')
    Manajemen Perjaldin
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    @php
        $revisiDraftStatuses = [
            'DRAFT',
            'REVISI_PPK',
            'REVISI_PPSPM',
            'REVISI_BENDAHARA',
            'REVISI_BENDAHARA_PENERIMAAN',
            'REVISI_BENDAHARA_PENGELUARAN',
            'REVISI_KASUBBAG',
            'DITOLAK_PPK',
            'DITOLAK_PPSPM',
            'DITOLAK_BENDAHARA_PENERIMAAN',
            'DITOLAK_BENDAHARA_PENGELUARAN',
            'DITOLAK_KASUBBAG',
        ];
    @endphp
    <x-page-title title="Manajemen" subtitle="Perjaldin" />

    @php
        $totalAll      = $tagihans->count();
        $countDraft    = $tagihans->whereIn('status', $revisiDraftStatuses)->count();
        $countMenunggu = $tagihans->whereIn('status', ['PENDING_VERIFIKASI_PERJALDIN', 'PENDING_PPK', 'PENDING_PPSPM', 'PENDING_BENDAHARA', 'PENDING_BENDAHARA_PENERIMAAN', 'PENDING_BENDAHARA_PENGELUARAN', 'PENDING_KASUBBAG', 'MENUNGGU_UPLOAD_NOMINATIF_TTD'])->count();
        $countSelesai  = $tagihans->whereIn('status', ['DISETUJUI_PERJALDIN', 'PROSES_COA', 'PROSES_SPP', 'SEBAGIAN_SPP_TERBIT', 'SPP_LENGKAP'])->count();

        $pct = fn ($n) => $totalAll > 0 ? round(($n / $totalAll) * 100) : 0;

        $cards = [
            [
                'label' => 'Total Tagihan',
                'value' => $totalAll,
                'sub'   => 'Seluruh pengajuan perjalanan dinas',
                'icon'  => 'bi-collection',
                'color' => 'primary',
                'pct'   => 100,
            ],
            [
                'label' => 'Draft & Revisi',
                'value' => $countDraft,
                'sub'   => 'Sedang disiapkan / dikembalikan',
                'icon'  => 'bi-pencil-square',
                'color' => 'info',
                'pct'   => $pct($countDraft),
            ],
            [
                'label' => 'Menunggu Verifikasi',
                'value' => $countMenunggu,
                'sub'   => 'Dalam antrean PPK / PPSPM / Bendahara / Kasubbag',
                'icon'  => 'bi-hourglass-split',
                'color' => 'warning',
                'pct'   => $pct($countMenunggu),
            ],
            [
                'label' => 'Disetujui & Lanjut SPP',
                'value' => $countSelesai,
                'sub'   => 'Sudah lolos verifikasi multi-pejabat',
                'icon'  => 'bi-check2-circle',
                'color' => 'success',
                'pct'   => $pct($countSelesai),
            ],
        ];
    @endphp

    <div class="row g-3 mb-4">
        @foreach($cards as $c)
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden">
                    <div class="position-absolute top-0 start-0 bottom-0" style="width: 4px; background: var(--bs-{{ $c['color'] }});"></div>
                    <div class="card-body p-3 ps-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width: 48px; height: 48px; background: var(--bs-{{ $c['color'] }}-bg-subtle, rgba(0,0,0,.05));">
                                <i class="bi {{ $c['icon'] }} fs-4 text-{{ $c['color'] }}"></i>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: .04em;">{{ $c['label'] }}</div>
                                <div class="fw-bold text-{{ $c['color'] }}" style="font-size: 1.75rem; line-height: 1.1;">
                                    {{ number_format($c['value']) }}
                                </div>
                                <div class="small text-muted">{{ $c['sub'] }}</div>
                            </div>
                        </div>
                        <div class="progress mt-3" style="height: 4px; background: rgba(0,0,0,.05);">
                            <div class="progress-bar bg-{{ $c['color'] }}" role="progressbar" style="width: {{ $c['pct'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 text-uppercase fw-bold">Daftar Tagihan Perjaldin</h6>
        <div class="d-flex gap-2">
            <a href="{{ route('perjaldins.create') }}" class="btn btn-success"><i class="bi bi-plus-lg"></i> Tambah Perjaldin</a>
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
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
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
                                <td>
                                    <strong>{{ $tagihan->nomor_tagihan }}</strong><br>
                                    <small>{{ $tagihan->deskripsi }}</small>
                                </td>
                                <td>
                                    @foreach($tagihan->detailPerjaldin as $detail)
                                        <span class="badge bg-light text-dark border">{{ $detail->nama_pegawai ?? ($detail->pegawai->nama_lengkap ?? '-') }}</span>
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
                                        @case('PENDING_VERIFIKASI_PERJALDIN')
                                            <span class="badge bg-primary"><i class="bi bi-hourglass-split"></i> Menunggu Verifikator</span>
                                            @break
                                        @case('PENDING_KASUBBAG')
                                            <span class="badge bg-info text-dark"><i class="bi bi-hourglass-split"></i> Menunggu Kasubbag</span>
                                            @break
                                        @case('PENDING_BENDAHARA')
                                        @case('PENDING_BENDAHARA_PENERIMAAN')
                                        @case('PENDING_BENDAHARA_PENGELUARAN')
                                        @case('PENDING_PPSPM')
                                            <span class="badge bg-primary"><i class="bi bi-hourglass-split"></i> Menunggu Verifikasi</span>
                                            @break
                                        @case('MENUNGGU_UPLOAD_NOMINATIF_TTD')
                                            <span class="badge bg-warning text-dark"><i class="bi bi-cloud-upload"></i> Menunggu Upload Nominatif TTD</span>
                                            @break
                                        @case('REVISI_PPK')
                                        @case('DITOLAK_PPK')
                                        @case('REVISI_PPSPM')
                                        @case('DITOLAK_PPSPM')
                                        @case('REVISI_BENDAHARA')
                                        @case('REVISI_BENDAHARA_PENERIMAAN')
                                        @case('DITOLAK_BENDAHARA_PENERIMAAN')
                                        @case('REVISI_BENDAHARA_PENGELUARAN')
                                        @case('DITOLAK_BENDAHARA_PENGELUARAN')
                                        @case('REVISI_KASUBBAG')
                                        @case('DITOLAK_KASUBBAG')
                                            <span class="badge bg-warning text-dark"><i class="bi bi-arrow-counterclockwise"></i> Perlu Revisi</span>
                                            @php $lastLog = $tagihan->logs->first(); @endphp
                                            @if($lastLog && $lastLog->catatan)
                                                <div class="mt-1 p-2 bg-warning bg-opacity-10 border border-warning rounded small text-dark">
                                                    <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                                                    {{ $lastLog->catatan }}
                                                </div>
                                            @endif
                                            @break
                                        @case('DISETUJUI_PPK')
                                        @case('DISETUJUI_PERJALDIN')
                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Disetujui</span>
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
                                        @if(in_array($tagihan->status, $revisiDraftStatuses))
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
            $('#example').DataTable({
                "columnDefs": [{ "orderable": false, "targets": [4] }]
            });
        });

        function deletePerjaldin(url) {
            if (confirm('Hapus Perjaldin ini beserta semua data pesertanya?')) {
                document.getElementById('deleteForm').action = url;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
@endpush
