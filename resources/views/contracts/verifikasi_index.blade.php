@extends('layouts.app')
@section('title', 'Verifikasi Kontrak & Addendum')
@section('content')
    <x-page-title title="Verifikasi" subtitle="Kontrak & Addendum" />

    {{-- Widget Cards --}}
    <div class="row mb-4">
        <div class="col-12 col-md-3">
            <div class="card bg-warning-subtle text-warning border-0 h-100 shadow-sm rounded-4">
                <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center text-center">
                    <div class="display-5 fw-bold mb-2">{{ $totalMenunggu }}</div>
                    <div class="small fw-semibold text-uppercase">Wait Kontrak</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card bg-info-subtle text-info border-0 h-100 shadow-sm rounded-4">
                <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center text-center">
                    <div class="display-5 fw-bold mb-2">{{ $totalAddendumMenunggu }}</div>
                    <div class="small fw-semibold text-uppercase">Wait Addendum</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card bg-success-subtle text-success border-0 h-100 shadow-sm rounded-4">
                <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center text-center">
                    <div class="display-5 fw-bold mb-2">{{ $totalDisetujui }}</div>
                    <div class="small fw-semibold text-uppercase">Kontrak Disetujui</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card bg-danger-subtle text-danger border-0 h-100 shadow-sm rounded-4">
                <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center text-center">
                    <div class="display-5 fw-bold mb-2">{{ $totalDitolak }}</div>
                    <div class="small fw-semibold text-uppercase">Ditolak</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="card rounded-4 border-0 shadow-sm">
        <div class="card-header bg-transparent border-bottom p-0">
            <ul class="nav nav-tabs nav-fill border-0 fw-semibold" id="verifikasiTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-3 rounded-0 border-0" id="tab-menunggu" data-bs-toggle="tab" data-bs-target="#menunggu" type="button" role="tab" aria-controls="menunggu" aria-selected="true">
                        <i class="bi bi-hourglass-split me-2"></i>Menunggu Persetujuan
                        @if($totalMenunggu > 0) <span class="badge bg-danger rounded-pill ms-2">{{ $totalMenunggu }}</span> @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 rounded-0 border-0" id="tab-addendum" data-bs-toggle="tab" data-bs-target="#addendum" type="button" role="tab" aria-controls="addendum" aria-selected="false">
                        <i class="bi bi-file-earmark-plus me-2"></i>Addendum
                        @if($totalAddendumMenunggu > 0) <span class="badge bg-danger rounded-pill ms-2">{{ $totalAddendumMenunggu }}</span> @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 rounded-0 border-0" id="tab-riwayat" data-bs-toggle="tab" data-bs-target="#riwayat" type="button" role="tab" aria-controls="riwayat" aria-selected="false">
                        <i class="bi bi-clock-history me-2"></i>Riwayat Verifikasi
                    </button>
                </li>
            </ul>
        </div>
        
        <div class="card-body p-4">
            <div class="tab-content" id="verifikasiTabsContent">
                
                {{-- TAB: Menunggu --}}
                <div class="tab-pane fade show active" id="menunggu" role="tabpanel" aria-labelledby="tab-menunggu">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle w-100 datatable">
                            <thead>
                                <tr>
                                    <th>No. Kontrak</th>
                                    <th>Pekerjaan</th>
                                    <th>Vendor</th>
                                    <th>Nilai (Rp)</th>
                                    <th>Waktu Pelaksanaan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contractsMenunggu as $contract)
                                    <tr>
                                        <td><div class="fw-bold text-primary">{{ $contract->contract_number }}</div></td>
                                        <td><div class="small text-wrap" style="max-width: 250px;">{{ Str::limit($contract->description, 50) }}</div></td>
                                        <td>{{ $contract->supplier->name ?? '-' }}</td>
                                        <td class="fw-semibold text-end">Rp {{ number_format($contract->total_amount, 0, ',', '.') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }} <br> s.d <br> {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <form action="{{ route('contracts.approve', $contract->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Setujui kontrak ini?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success text-white" title="Setujui"><i class="bi bi-check-lg"></i></button>
                                                </form>
                                                <form action="{{ route('contracts.reject', $contract->id) }}" method="POST" class="d-inline" onsubmit="var notes = prompt('Alasan penolakan:'); if(notes) { this.notes.value = notes; return true; } return false;">
                                                    @csrf
                                                    <input type="hidden" name="notes" value="">
                                                    <button type="submit" class="btn btn-sm btn-danger text-white" title="Tolak"><i class="bi bi-x-lg"></i></button>
                                                </form>
                                                <a href="{{ route('contracts.show', $contract->id) }}" class="btn btn-sm btn-outline-secondary" title="View Detail"><i class="bi bi-eye"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB: Addendum --}}
                <div class="tab-pane fade" id="addendum" role="tabpanel" aria-labelledby="tab-addendum">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle w-100 datatable">
                            <thead>
                                <tr>
                                    <th>No. Addendum</th>
                                    <th>Ref Kontrak</th>
                                    <th>Nilai Baru (Rp)</th>
                                    <th>Alasan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($addendumsMenunggu as $addendum)
                                    <tr>
                                        <td><div class="fw-bold text-info">{{ $addendum->addendum_number }}</div></td>
                                        <td><a href="{{ route('contracts.show', $addendum->contract_id) }}">{{ $addendum->contract->contract_number ?? '-' }}</a></td>
                                        <td class="fw-semibold text-end">
                                            @if($addendum->new_total_amount)
                                                Rp {{ number_format($addendum->new_total_amount, 0, ',', '.') }}
                                            @else
                                                <span class="text-muted">Tetap</span>
                                            @endif
                                        </td>
                                        <td><div class="small text-wrap" style="max-width: 250px;">{{ Str::limit($addendum->reason, 50) }}</div></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <form action="{{ route('addendums.approve', [$addendum->contract_id, $addendum->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Setujui addendum ini?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success text-white" title="Setujui"><i class="bi bi-check-lg"></i></button>
                                                </form>
                                                <form action="{{ route('addendums.reject', [$addendum->contract_id, $addendum->id]) }}" method="POST" class="d-inline" onsubmit="var notes = prompt('Alasan penolakan:'); if(notes) { this.notes.value = notes; return true; } return false;">
                                                    @csrf
                                                    <input type="hidden" name="notes" value="">
                                                    <button type="submit" class="btn btn-sm btn-danger text-white" title="Tolak"><i class="bi bi-x-lg"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB: Riwayat --}}
                <div class="tab-pane fade" id="riwayat" role="tabpanel" aria-labelledby="tab-riwayat">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle w-100 datatable">
                            <thead>
                                <tr>
                                    <th>No. Kontrak</th>
                                    <th>Pekerjaan</th>
                                    <th>Vendor</th>
                                    <th>Nilai (Rp)</th>
                                    <th>Status Saat Ini</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contractsRiwayat as $contract)
                                    @php
                                        $badgeClass = match($contract->status) {
                                            'Ditolak PPK' => 'danger',
                                            'Aktif' => 'success',
                                            'Selesai' => 'primary',
                                            default => 'secondary'
                                        };
                                        $displayStatus = in_array($contract->status, ['Ditolak PPK']) ? 'Ditolak' : $contract->status;
                                    @endphp
                                    <tr>
                                        <td><div class="fw-bold">{{ $contract->contract_number }}</div></td>
                                        <td><div class="small text-wrap" style="max-width: 200px;">{{ Str::limit($contract->description, 50) }}</div></td>
                                        <td>{{ $contract->supplier->name ?? '-' }}</td>
                                        <td class="fw-semibold text-end">Rp {{ number_format($contract->total_amount, 0, ',', '.') }}</td>
                                        <td><span class="badge bg-{{ $badgeClass }} px-2 py-1 rounded-pill">{{ $displayStatus }}</span></td>
                                        <td>
                                            <a href="{{ route('contracts.show', $contract->id) }}" class="btn btn-sm btn-outline-secondary" title="View Detail"><i class="bi bi-eye"></i></a>
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

@push('style')
<link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush

@push('script')
<script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $('.datatable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json',
            },
        });
    });
</script>
@endpush
