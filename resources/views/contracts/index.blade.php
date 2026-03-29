@extends('layouts.app')
@section('title')
    Manajemen Kontrak
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .nav-tabs .nav-link {
            font-weight: 600;
            color: #6c757d;
            border: none;
            padding: 1rem 1.5rem;
            border-bottom: 3px solid transparent;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            background: transparent;
            border-color: #0d6efd;
        }
    </style>
@endpush
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h4 class="mb-0 fw-bold">Manajemen Kontrak</h4>
            <p class="text-muted mb-0 small">Pantau status pelaksanaan kontrak dan persetujuan addendum</p>
        </div>
        <a href="{{ route('contracts.create') }}" class="btn btn-primary shadow-sm fw-bold">
            <i class="bi bi-plus-lg me-1"></i> Tambah Kontrak
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success text-white alert-dismissible fade show shadow-sm">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white p-0 border-bottom rounded-top-4">
            <ul class="nav nav-tabs px-3" id="contractTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold py-3 border-0 border-bottom border-3 border-primary text-primary" id="kontrak-tab" data-bs-toggle="tab" data-bs-target="#kontrak" type="button" role="tab" aria-controls="kontrak" aria-selected="true">
                        <i class="bi bi-file-earmark-text me-2"></i>Daftar Kontrak Utama
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold py-3 border-0 border-bottom border-3 text-secondary" id="addendum-tab" data-bs-toggle="tab" data-bs-target="#addendum" type="button" role="tab" aria-controls="addendum" aria-selected="false">
                        <i class="bi bi-journal-plus me-2"></i>Riwayat Addendum
                    </button>
                </li>
            </ul>
        </div>
        
        <div class="card-body p-4">
            <div class="tab-content" id="contractTabsContent">
                
                {{-- TAB 1: KONTRAK UTAMA --}}
                <div class="tab-pane fade show active" id="kontrak" role="tabpanel" aria-labelledby="kontrak-tab">
                    <div class="table-responsive">
                        <table id="tableKontrak" class="table table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th width="5%" class="text-center">No</th>
                                    <th width="30%">Nomor SPK & Pekerjaan</th>
                                    <th width="15%">Vendor</th>
                                    <th width="20%">Nilai & Timeline</th>
                                    <th width="10%">Status</th>
                                    <th width="20%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contracts as $kontrak)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="fw-bold">{{ $kontrak->nomor_spk }}</span><br>
                                            <small><i class="bi bi-briefcase me-1"></i>{{ Str::limit($kontrak->nama_pekerjaan, 50) }}</small>
                                        </td>
                                        <td>
                                            <span class="fw-medium">{{ $kontrak->vendor->nama_perusahaan ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success">Rp {{ number_format($kontrak->nilai_total_kontrak, 0, ',', '.') }}</span><br>
                                            <small ><i class="bi bi-calendar-event me-1"></i>Selesai: {{ \Carbon\Carbon::parse($kontrak->tanggal_selesai)->format('d M Y') }}</small>
                                        </td>
                                        <td>
                                            @if($kontrak->status_kontrak == 'AKTIF')
                                                <span class="badge bg-primary">AKTIF</span>
                                            @elseif($kontrak->status_kontrak == 'SELESAI')
                                                <span class="badge bg-success">SELESAI</span>
                                            @else
                                                <span class="badge bg-danger">{{ $kontrak->status_kontrak }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="{{ route('contracts.show', $kontrak->id) }}" class="btn btn-sm btn-light text-info border shadow-sm" title="Detail">
                                                    <i class="bi bi-search"></i> Detail
                                                </a>
                                                <a href="#" class="btn btn-sm btn-light text-warning border shadow-sm" title="Buat Addendum">
                                                    <i class="bi bi-plus-circle"></i> Addm.
                                                </a>
                                                @if($kontrak->status_kontrak == 'AKTIF')
                                                    <a href="{{ route('tagihan.kontrak.create', ['kontrak_id' => $kontrak->id]) }}" class="btn btn-sm btn-white text-success border border-success shadow-sm" title="Buat Tagihan (SPP Termins/BAST)">
                                                        <i class="bi bi-cash-stack"></i> Tagih
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

                {{-- TAB 2: ADDENDUM --}}
                <div class="tab-pane fade" id="addendum" role="tabpanel" aria-labelledby="addendum-tab">
                    <div class="table-responsive">
                        <table id="tableAddendum" class="table table-striped table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th width="5%" class="text-center">No</th>
                                    <th width="15%">Ref. Kontrak Utama</th>
                                    <th width="25%">No. Addendum & Tanggal</th>
                                    <th width="15%">Jenis Perubahan</th>
                                    <th width="15%">Nilai/Waktu Baru</th>
                                    <th width="15%">Status Addendum</th>
                                    <th width="10%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($addendums as $addm)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="fw-bold">{{ $addm->kontrakUtama->nomor_spk ?? '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-primary">{{ $addm->nomor_addendum ?? 'ADD.XXX' }}</span><br>
                                            <small><i class="bi bi-calendar me-1"></i>{{ $addm->tanggal_addendum ? \Carbon\Carbon::parse($addm->tanggal_addendum)->format('d M Y') : '-' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $addm->jenis_perubahan ?? 'Perubahan' }}</span>
                                        </td>
                                        <td>
                                            @if($addm->nilai_kontrak_baru)
                                                <span class="fw-bold text-success">Rp {{ number_format($addm->nilai_kontrak_baru, 0, ',', '.') }}</span>
                                            @else
                                                <small>Selesai:</small><br>
                                                <span class="fw-bold">{{ \Carbon\Carbon::parse($addm->tanggal_selesai_baru)->format('d M Y') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $addm->status ?? 'PROSES' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="#" class="btn btn-sm btn-light text-primary border shadow-sm" title="View File">
                                                <i class="bi bi-search"></i> File
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            let dtConfig = {
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
                }
            };
            
            $('#tableKontrak').DataTable(dtConfig);
            $('#tableAddendum').DataTable(dtConfig);
            
            // Adjust column sizings on tab show, standard fix for DataTables inside un-active bootstrap tabs
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            });
        });
    </script>
@endpush
