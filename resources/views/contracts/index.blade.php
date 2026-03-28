@extends('layouts.app')
@section('title', 'Manajemen Kontrak & Addendum')
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 text-uppercase fw-bold">Manajemen Kontrak & Addendum</h4>
        </div>
        <div>
            <a href="{{ route('contracts.create') }}" class="btn btn-primary px-4 fw-bold shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Tambah Kontrak Baru
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show">
            <div class="text-white">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @php
        $countDraft = $contracts->where('status', 'Draft')->count();
        $countMenungguPPK = $contracts->whereIn('status', ['Menunggu PPK', 'Menunggu Persetujuan PPK'])->count();
        $countRevisi = $contracts->whereIn('status', ['Revisi', 'Ditolak PPK', 'Ditolak'])->count();
        $countAktif = $contracts->whereIn('status', ['Aktif', 'Active'])->count();
        
        $allAddendums = $contracts->flatMap->addendums->sortByDesc('created_at');
    @endphp

    {{-- Workspace Widgets (Top Row) --}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4 mb-4">
        <div class="col">
            <div class="card bg-secondary text-white mb-0 h-100 rounded-4 border-0 shadow-sm">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <p class="mb-1 fw-semibold text-white-50">Draft Kontrak</p>
                        <h3 class="mb-0 fw-bold">{{ $countDraft }}</h3>
                    </div>
                    <div class="fs-1 text-white-50"><i class="bi bi-file-earmark"></i></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card bg-warning text-dark mb-0 h-100 rounded-4 border-0 shadow-sm">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <p class="mb-1 fw-semibold text-dark-50">Menunggu PPK</p>
                        <h3 class="mb-0 fw-bold">{{ $countMenungguPPK }}</h3>
                    </div>
                    <div class="fs-1 opacity-50"><i class="bi bi-hourglass-split"></i></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card bg-danger text-white mb-0 h-100 rounded-4 border-0 shadow-sm">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <p class="mb-1 fw-semibold text-white-50">Perlu Revisi</p>
                        <h3 class="mb-0 fw-bold">{{ $countRevisi }}</h3>
                    </div>
                    <div class="fs-1 text-white-50"><i class="bi bi-exclamation-triangle"></i></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card bg-success text-white mb-0 h-100 rounded-4 border-0 shadow-sm">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <p class="mb-1 fw-semibold text-white-50">Kontrak Aktif</p>
                        <h3 class="mb-0 fw-bold">{{ $countAktif }}</h3>
                    </div>
                    <div class="fs-1 text-white-50"><i class="bi bi-check-circle"></i></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content (Nav Tabs) --}}
    <div class="card rounded-4 shadow-sm border-0">
        <div class="card-header bg-transparent border-bottom p-0">
            <ul class="nav nav-tabs nav-fill mb-0" id="contractTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold py-3 border-0 border-bottom border-3 border-primary text-primary" id="tab-kontrak-utama" data-bs-toggle="tab" data-bs-target="#kontrak-utama" type="button" role="tab" aria-controls="kontrak-utama" aria-selected="true" onclick="document.querySelectorAll('.nav-link').forEach(el=>el.classList.remove('border-primary','text-primary')); this.classList.add('border-primary','text-primary');">
                        <i class="bi bi-journal-text me-2"></i>Kontrak Utama
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold py-3 border-0 border-bottom border-3 text-secondary" id="tab-addendum" data-bs-toggle="tab" data-bs-target="#addendum" type="button" role="tab" aria-controls="addendum" aria-selected="false" onclick="document.querySelectorAll('.nav-link').forEach(el=>el.classList.remove('border-primary','text-primary')); this.classList.add('border-primary','text-primary');">
                        <i class="bi bi-file-earmark-plus me-2"></i>Pengajuan Addendum
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content" id="contractTabsContent">
                
                {{-- TAB A: Kontrak Utama --}}
                <div class="tab-pane fade show active" id="kontrak-utama" role="tabpanel" aria-labelledby="tab-kontrak-utama">
                    <div class="table-responsive">
                        <table id="table-kontrak" class="table table-striped table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th>No. Kontrak</th>
                                    <th>Nama Vendor</th>
                                    <th>Nilai (Rp)</th>
                                    <th>Jangka Waktu</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contracts as $contract)
                                    @php
                                        // Merge status definitions for badges
                                        $badgeClass = match($contract->status) {
                                            'Draft' => 'secondary',
                                            'Menunggu PPK', 'Menunggu Persetujuan PPK' => 'warning',
                                            'Ditolak PPK', 'Ditolak', 'Revisi' => 'danger',
                                            'Aktif', 'Active', 'Completed', 'Selesai' => 'success',
                                            default => 'secondary'
                                        };
                                        $displayStatus = in_array($contract->status, ['Menunggu PPK', 'Menunggu Persetujuan PPK']) ? 'Menunggu PPK' : $contract->status;
                                        $displayStatus = in_array($contract->status, ['Aktif', 'Active']) ? 'Aktif' : $displayStatus;
                                        $displayStatus = in_array($contract->status, ['Ditolak PPK', 'Ditolak']) ? 'Ditolak' : $displayStatus;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-primary">{{ $contract->contract_number ?? 'Draft' }}</div>
                                            <div class="small text-wrap outline-none" style="max-width: 250px;">{{ Str::limit($contract->description, 50) }}</div>
                                        </td>
                                        <td>{{ $contract->supplier->name ?? '-' }}</td>
                                        <td class="fw-semibold">Rp {{ number_format($contract->total_amount, 0, ',', '.') }}</td>
                                        <td>
                                            @if($contract->date && $contract->end_date)
                                                {{ \Carbon\Carbon::parse($contract->date)->format('d/m/Y') }} - <br> {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $badgeClass }} px-3 py-2 rounded-pill">{{ $displayStatus }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if(in_array($displayStatus, ['Draft', 'Ditolak', 'Revisi']))
                                                    <a href="{{ route('contracts.edit', $contract->id) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pen"></i></a>
                                                    {{-- Ajukan form --}}
                                                    <form action="{{ route('contracts.submit', $contract->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Ajukan kontrak ini ke PPK?');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Ajukan"><i class="bi bi-send"></i></button>
                                                    </form>
                                                @elseif(in_array($displayStatus, ['Aktif', 'Selesai', 'Completed']))
                                                    {{-- Link point to the standalone create addendum page --}}
                                                    <a href="{{ route('addendums.create', $contract->id) }}" class="btn btn-sm btn-outline-info" title="Buat Addendum"><i class="bi bi-file-earmark-plus"></i></a>
                                                    <a href="{{ route('contracts.show', $contract->id) }}" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                                                @else
                                                    {{-- Default view --}}
                                                    <a href="{{ route('contracts.show', $contract->id) }}" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB B: Pengajuan Addendum --}}
                <div class="tab-pane fade" id="addendum" role="tabpanel" aria-labelledby="tab-addendum">
                    <div class="table-responsive">
                        <table id="table-addendum" class="table table-striped table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th>No. Addendum</th>
                                    <th>Ref Kontrak Lama</th>
                                    <th>Nilai Baru (Rp)</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allAddendums as $addendum)
                                    @php
                                        $addStatus = $addendum->status;
                                        $addBadge = match($addStatus) {
                                            'Draft' => 'secondary',
                                            'Menunggu PPK' => 'warning',
                                            'Ditolak' => 'danger',
                                            'Disetujui' => 'success',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <tr>
                                        <td class="fw-bold text-primary">{{ $addendum->addendum_number }}</td>
                                        <td>
                                            @if($addendum->contract)
                                                <a href="{{ route('contracts.show', $addendum->contract_id) }}" class="text-decoration-none fw-semibold">
                                                    {{ $addendum->contract->contract_number ?? 'Draft' }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="fw-semibold">{{ $addendum->new_total_amount ? 'Rp ' . number_format($addendum->new_total_amount, 0, ',', '.') : '-' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $addBadge }} px-3 py-2 rounded-pill">{{ $addStatus }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <a href="{{ route('contracts.show', $addendum->contract_id) }}" class="btn btn-sm btn-outline-secondary" title="View Contract"><i class="bi bi-eye"></i></a>
                                                
                                                @if(in_array($addStatus, ['Draft', 'Ditolak']))
                                                    <form action="{{ route('addendums.submit', [$addendum->contract_id, $addendum->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Ajukan addendum ini ke PPK?');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Ajukan"><i class="bi bi-send"></i></button>
                                                    </form>
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
        </div>
    </div>
@endsection
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#table-kontrak').DataTable();
            $('#table-addendum').DataTable();
        });
    </script>
@endpush
