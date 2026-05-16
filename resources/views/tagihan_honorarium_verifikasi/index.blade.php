@extends('layouts.app')
@section('title') Verifikasi Tagihan Honorarium @endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .doc-row:hover { background: #f8f9ff; }
        .tab-btn.active { border-bottom: 3px solid #0d6efd; color: #0d6efd; font-weight: 600; }
        .tab-btn { border: none; background: none; padding: 10px 20px; color: #6c757d; cursor: pointer; }
    </style>
@endpush
@section('content')
<x-page-title title="Verifikasi Tagihan Honorarium" subtitle="Daftar Pengajuan Honorarium yang Menunggu / Sudah Anda Verifikasi" />

{{-- Flash --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-3">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-3">
        <i class="bi bi-x-circle-fill me-2"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Summary Cards --}}
@include('verifikasi_perjaldin.partials.list-summary', [
    'tagihans' => $tagihans,
    'pendingStatuses' => $pendingStatuses,
    'revisiStatuses'  => $revisiStatuses,
    'selesaiStatuses' => $selesaiStatuses,
])

{{-- Role Info Banner --}}
<div class="alert alert-light border shadow-sm d-flex align-items-center gap-3 mb-4 py-2">
    <i class="bi bi-person-badge fs-4 text-primary"></i>
    <div class="small">
        Anda login sebagai <strong>{{ $userRole }}</strong>.
        Dokumen yang ditampilkan adalah tagihan honorarium yang relevan untuk tahap verifikasi Anda.
    </div>
</div>

{{-- Tabs --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom px-4 pt-3 pb-0">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-2">
            <div class="d-flex gap-0">
                <button class="tab-btn active" id="tab-perlu" onclick="switchTab('perlu')">
                    <i class="bi bi-hourglass-split me-1"></i>Perlu Aksi Saya
                    @if($tagihansPerlu->count() > 0)
                        <span class="badge bg-primary rounded-pill ms-1">{{ $tagihansPerlu->count() }}</span>
                    @endif
                </button>
                <button class="tab-btn" id="tab-riwayat" onclick="switchTab('riwayat')">
                    <i class="bi bi-clock-history me-1"></i>Riwayat Verifikasi
                </button>
            </div>
            {{-- Filter --}}
            <form method="GET" class="d-flex gap-2 align-items-center flex-wrap pb-2">
                <input type="text" name="search" class="form-control form-control-sm" style="width:180px;"
                       placeholder="Cari nomor / uraian / supplier..." value="{{ request('search') }}">
                <input type="month" name="periode" class="form-control form-control-sm" style="width:140px;"
                       value="{{ request('periode') }}">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
                @if(request()->hasAny(['search','periode']))
                    <a href="{{ request()->url() }}" class="btn btn-sm btn-light border"><i class="bi bi-x"></i> Reset</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Tab: Perlu Aksi --}}
    <div id="pane-perlu">
        <div class="card-body p-0">
            @if($tagihansPerlu->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox display-5 d-block mb-3 text-secondary"></i>
                    <h6 class="text-secondary">Tidak ada dokumen yang perlu aksi Anda saat ini</h6>
                    <p class="small text-muted">Tagihan akan muncul di sini setelah PPABP mengajukan tagihan honorarium untuk diverifikasi.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablePerlu">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width:40px;">No</th>
                                <th>Dokumen</th>
                                <th>Supplier & Penerima</th>
                                <th>Total Netto</th>
                                <th>Diajukan</th>
                                <th>Status</th>
                                <th class="text-center" style="width:160px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tagihansPerlu as $i => $tagihan)
                                <tr class="doc-row">
                                    <td class="ps-4 text-muted small">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $tagihan->nomor_tagihan }}</div>
                                        <div class="text-muted small text-truncate" style="max-width:260px;">{{ $tagihan->deskripsi }}</div>
                                        <span class="badge bg-light text-secondary border small mt-1">
                                            <i class="bi bi-cash-coin me-1"></i>Honorarium
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width:260px;" title="{{ $tagihan->nama_supplier ?? '-' }}">
                                            <i class="bi bi-building me-1 text-primary"></i>{{ $tagihan->nama_supplier ?: '-' }}
                                        </div>
                                        <div class="text-muted small">
                                            <i class="bi bi-people me-1"></i>{{ $tagihan->detailHonorarium->count() }} penerima
                                        </div>
                                        @if($tagihan->mekanisme_pembayaran)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle small mt-1">
                                                {{ optional($tagihan->mekanisme_pembayaran)->label() ?? '-' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold text-success">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</td>
                                    <td>
                                        @php $submitLog = $tagihan->logs->firstWhere('aksi', 'DIAJUKAN') ?? $tagihan->logs->firstWhere('aksi', 'SUBMIT'); @endphp
                                        <span class="small">{{ $submitLog ? $submitLog->created_at->format('d M Y') : '-' }}</span>
                                        @if($submitLog)
                                            <div class="text-muted" style="font-size:.70rem;">{{ $submitLog->created_at->format('H:i') }}</div>
                                        @endif
                                    </td>
                                    <td>@include('verifikasi_perjaldin.partials.status-badge', ['status' => $tagihan->status])</td>
                                    <td class="text-center">
                                        <a href="{{ route($detailRoute, $tagihan->id) }}" class="btn btn-sm btn-primary px-3">
                                            <i class="bi bi-shield-check me-1"></i>Verifikasi
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Tab: Riwayat --}}
    <div id="pane-riwayat" style="display:none;">
        <div class="card-body p-0">
            @if($tagihansRiwayat->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-clock-history display-5 d-block mb-3 text-secondary"></i>
                    <p class="small">Belum ada dokumen yang pernah Anda proses.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tableRiwayat">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width:40px;">No</th>
                                <th>Dokumen</th>
                                <th>Supplier & Penerima</th>
                                <th>Total Netto</th>
                                <th>Status</th>
                                <th class="text-center" style="width:120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tagihansRiwayat as $i => $tagihan)
                                <tr class="doc-row">
                                    <td class="ps-4 text-muted small">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $tagihan->nomor_tagihan }}</div>
                                        <div class="text-muted small text-truncate" style="max-width:260px;">{{ $tagihan->deskripsi }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width:260px;">
                                            <i class="bi bi-building me-1 text-primary"></i>{{ $tagihan->nama_supplier ?: '-' }}
                                        </div>
                                        <div class="text-muted small">
                                            <i class="bi bi-people me-1"></i>{{ $tagihan->detailHonorarium->count() }} penerima
                                        </div>
                                    </td>
                                    <td class="fw-semibold">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</td>
                                    <td>@include('verifikasi_perjaldin.partials.status-badge', ['status' => $tagihan->status])</td>
                                    <td class="text-center">
                                        <a href="{{ route($detailRoute, $tagihan->id) }}" class="btn btn-sm btn-outline-secondary px-3">
                                            <i class="bi bi-eye me-1"></i>Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('script')
<script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script>
function switchTab(tab) {
    document.getElementById('pane-perlu').style.display = tab === 'perlu' ? '' : 'none';
    document.getElementById('pane-riwayat').style.display = tab === 'riwayat' ? '' : 'none';
    document.getElementById('tab-perlu').classList.toggle('active', tab === 'perlu');
    document.getElementById('tab-riwayat').classList.toggle('active', tab === 'riwayat');
}
$(document).ready(function() {
    $('#tablePerlu').DataTable({ paging: false, searching: false, info: false, order: [] });
    $('#tableRiwayat').DataTable({ paging: false, searching: false, info: false, order: [] });
});
</script>
@endpush
