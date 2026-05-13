@extends('layouts.app')
@section('title') Verifikasi Tagihan Honorarium @endsection

@push('css')
    <style>
        .doc-row:hover { background: #f8f9ff; }
        .tab-btn.active { border-bottom: 3px solid #0d6efd; color: #0d6efd; font-weight: 600; }
        .tab-btn { border: none; background: none; padding: 10px 20px; color: #6c757d; cursor: pointer; }
    </style>
@endpush

@section('content')
<x-page-title title="Verifikasi Tagihan Honorarium" subtitle="Daftar Pengajuan Honorarium yang Menunggu / Sudah Anda Verifikasi" />

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

@php
    $totalPerlu    = $tagihansPerlu->count();
    $totalRiwayat  = $tagihansRiwayat->count();
    $totalPending  = $tagihans->whereIn('status', $pendingStatuses)->count();
    $totalRevisi   = $tagihans->whereIn('status', $revisiStatuses)->count();
    $totalSelesai  = $tagihans->whereIn('status', $selesaiStatuses)->count();
@endphp

<div class="row g-3 mb-3">
    @foreach([
        ['label' => 'Perlu Aksi Saya', 'value' => $totalPerlu,   'class' => 'text-primary'],
        ['label' => 'Sedang Menunggu', 'value' => $totalPending, 'class' => 'text-warning'],
        ['label' => 'Perlu Revisi',    'value' => $totalRevisi,  'class' => 'text-danger'],
        ['label' => 'Sudah Disetujui', 'value' => $totalSelesai, 'class' => 'text-success'],
    ] as $c)
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ $c['label'] }}</div>
                    <div class="fs-3 fw-bold {{ $c['class'] }}">{{ $c['value'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="alert alert-light border shadow-sm d-flex align-items-center gap-3 mb-4 py-2">
    <i class="bi bi-person-badge fs-4 text-primary"></i>
    <div class="small">
        Anda login sebagai <strong>{{ $userRole }}</strong>.
        Dokumen yang ditampilkan adalah tagihan honorarium yang relevan untuk tahap verifikasi Anda.
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom px-4 pt-3 pb-0">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-2">
            <div class="d-flex gap-0">
                <button class="tab-btn active" id="tab-perlu" onclick="switchTab('perlu')">
                    <i class="bi bi-hourglass-split me-1"></i>Perlu Aksi Saya
                    @if($totalPerlu > 0)<span class="badge bg-primary rounded-pill ms-1">{{ $totalPerlu }}</span>@endif
                </button>
                <button class="tab-btn" id="tab-riwayat" onclick="switchTab('riwayat')">
                    <i class="bi bi-clock-history me-1"></i>Riwayat Verifikasi
                    @if($totalRiwayat > 0)<span class="badge bg-secondary rounded-pill ms-1">{{ $totalRiwayat }}</span>@endif
                </button>
            </div>
            <form method="GET" class="d-flex gap-2 align-items-center flex-wrap pb-2">
                <input type="text" name="search" class="form-control form-control-sm" style="width:220px;"
                       placeholder="Cari nomor / uraian / supplier..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
                @if(request()->filled('search'))
                    <a href="{{ request()->url() }}" class="btn btn-sm btn-light border"><i class="bi bi-x"></i> Reset</a>
                @endif
            </form>
        </div>
    </div>

    @php
        $rowRenderer = function ($tagihan) use ($detailRoute) {
            $status = $tagihan->status;
            $badgeClass = match(true) {
                str_starts_with($status, 'REVISI_')  => 'bg-danger',
                str_starts_with($status, 'DITOLAK_') => 'bg-dark',
                str_starts_with($status, 'PENDING_') => 'bg-warning text-dark',
                $status === 'DISETUJUI'              => 'bg-success',
                $status === 'DRAFT'                  => 'bg-secondary',
                default                              => 'bg-info text-dark',
            };
            return [$status, $badgeClass];
        };
    @endphp

    <div id="pane-perlu">
        <div class="card-body p-0">
            @if($tagihansPerlu->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox display-5 d-block mb-3 text-secondary"></i>
                    <h6 class="text-secondary">Tidak ada dokumen yang perlu aksi Anda saat ini</h6>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nomor Tagihan</th>
                                <th>Supplier / Penerima</th>
                                <th>Uraian</th>
                                <th class="text-end">Nilai Netto</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tagihansPerlu as $tagihan)
                                @php [$status, $badgeClass] = $rowRenderer($tagihan); @endphp
                                <tr class="doc-row">
                                    <td><strong class="font-monospace">{{ $tagihan->nomor_tagihan }}</strong></td>
                                    <td>
                                        <div>{{ $tagihan->nama_supplier ?: '-' }}</div>
                                        <div class="small text-muted">{{ $tagihan->detailHonorarium->count() }} penerima</div>
                                    </td>
                                    <td class="text-muted small">{{ \Illuminate\Support\Str::limit($tagihan->deskripsi, 70) }}</td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</td>
                                    <td><span class="badge {{ $badgeClass }}">{{ $status }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route($detailRoute, $tagihan->id) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-check2-square me-1"></i> Verifikasi
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

    <div id="pane-riwayat" style="display: none;">
        <div class="card-body p-0">
            @if($tagihansRiwayat->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-clock-history display-5 d-block mb-3 text-secondary"></i>
                    <h6 class="text-secondary">Anda belum pernah memverifikasi tagihan honorarium.</h6>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nomor Tagihan</th>
                                <th>Supplier / Penerima</th>
                                <th>Uraian</th>
                                <th class="text-end">Nilai Netto</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tagihansRiwayat as $tagihan)
                                @php [$status, $badgeClass] = $rowRenderer($tagihan); @endphp
                                <tr class="doc-row">
                                    <td><strong class="font-monospace">{{ $tagihan->nomor_tagihan }}</strong></td>
                                    <td>
                                        <div>{{ $tagihan->nama_supplier ?: '-' }}</div>
                                        <div class="small text-muted">{{ $tagihan->detailHonorarium->count() }} penerima</div>
                                    </td>
                                    <td class="text-muted small">{{ \Illuminate\Support\Str::limit($tagihan->deskripsi, 70) }}</td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</td>
                                    <td><span class="badge {{ $badgeClass }}">{{ $status }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route($detailRoute, $tagihan->id) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye me-1"></i> Lihat
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

<script>
    function switchTab(tab) {
        const panes = ['perlu', 'riwayat'];
        panes.forEach(p => {
            document.getElementById('pane-' + p).style.display = (p === tab) ? 'block' : 'none';
            document.getElementById('tab-' + p).classList.toggle('active', p === tab);
        });
    }
</script>
@endsection
