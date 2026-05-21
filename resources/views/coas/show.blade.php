@extends('layouts.app')

@section('title', 'Detail COA: ' . ($coa->kode_mak_lengkap ?: $coa->nama_akun))

@push('css')
<style>
    .metric-tile {
        background-color: #fff;
        border: 1px solid rgba(0,0,0,.08);
        border-radius: 0.5rem;
        padding: 1.25rem;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .metric-tile .metric-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        color: #6c757d;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }
    .metric-tile .metric-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: #212529;
        line-height: 1.2;
    }
    .metric-tile.bg-light-success { background-color: #f0fdf4; border-color: #bbf7d0; }
    .metric-tile.bg-light-danger { background-color: #fef2f2; border-color: #fecaca; }
    .table-custom th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        font-weight: 600;
    }
    .table-custom td {
        vertical-align: middle;
        font-size: 0.875rem;
    }
    .code-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 1rem;
    }
    .code-item {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        padding: 0.75rem;
    }
    .code-item .code-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        color: #6c757d;
        margin-bottom: 0.25rem;
    }
    .code-item .code-value {
        font-weight: 600;
        color: #212529;
    }
</style>
@endpush

@section('content')
    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success border-0 bg-success text-white alert-dismissible fade show shadow-sm">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger text-white alert-dismissible fade show shadow-sm">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Header / COA Identity -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge {{ $coa->status_aktif ? 'bg-success' : 'bg-secondary' }}">
                            <i class="bi {{ $coa->status_aktif ? 'bi-check-circle' : 'bi-x-circle' }} me-1"></i>
                            {{ $coa->status_aktif ? 'COA AKTIF' : 'COA NONAKTIF' }}
                        </span>
                        @if($coa->jenis_akun)
                            <span class="badge bg-light text-dark border">{{ $coa->jenis_akun }}</span>
                        @endif
                    </div>
                    <h2 class="fw-bold mb-1 text-primary">{{ $coa->kode_mak_lengkap ?: ($coa->kd_akun ?: '-') }}</h2>
                    <p class="fs-5 text-dark mb-0">{{ $coa->nama_akun }}</p>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('coas.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                    <a href="{{ route('coas.edit', $coa) }}" class="btn btn-outline-primary">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    @if($statistics['jumlah_item_dipa'] === 0)
                        <form action="{{ route('coas.destroy', $coa) }}" method="POST" onsubmit="return confirm('Hapus COA ini secara permanen?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="metric-tile">
                <div class="metric-label">Jumlah Tagihan</div>
                <div class="metric-value">{{ number_format($billingStatistics['jumlah_tagihan']) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="metric-tile">
                <div class="metric-label">Dipakai di DIPA</div>
                <div class="metric-value">{{ number_format($statistics['jumlah_item_dipa']) }} <span class="text-muted fw-normal fs-6">Item</span></div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-2">
            <div class="metric-tile">
                <div class="metric-label">Terkait</div>
                <div class="metric-value">{{ number_format($statistics['jumlah_dipa']) }} <span class="text-muted fw-normal fs-6">DIPA</span></div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-2">
            <div class="metric-tile">
                <div class="metric-label">Total Pagu</div>
                <div class="metric-value text-dark fs-5">Rp {{ number_format($statistics['total_nilai_pagu'], 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-2">
            <div class="metric-tile">
                <div class="metric-label">Total Realisasi</div>
                <div class="metric-value text-primary fs-5">Rp {{ number_format($statistics['total_realisasi'], 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-2">
            <div class="metric-tile {{ $statistics['total_sisa_pagu'] < 0 ? 'bg-light-danger' : 'bg-light-success' }}">
                <div class="metric-label">Sisa Pagu</div>
                <div class="metric-value {{ $statistics['total_sisa_pagu'] < 0 ? 'text-danger' : 'text-success' }} fs-5">
                    @if($statistics['total_sisa_pagu'] < 0)
                        <i class="bi bi-arrow-down-circle-fill me-1"></i>
                    @else
                        <i class="bi bi-arrow-up-circle-fill me-1"></i>
                    @endif
                    Rp {{ number_format($statistics['total_sisa_pagu'], 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    <!-- COA Structure Code -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom pt-3 pb-3">
            <h6 class="mb-0 fw-bold"><i class="bi bi-diagram-2 me-2"></i> Struktur Kode COA</h6>
        </div>
        <div class="card-body">
            <div class="code-grid">
                <div class="code-item"><div class="code-label">Kd Program</div><div class="code-value">{{ $coa->kd_program ?: '-' }}</div></div>
                <div class="code-item"><div class="code-label">Kd Giat</div><div class="code-value">{{ $coa->kd_giat ?: '-' }}</div></div>
                <div class="code-item"><div class="code-label">Kd Output</div><div class="code-value">{{ $coa->kd_output ?: '-' }}</div></div>
                <div class="code-item"><div class="code-label">Kd Suboutput</div><div class="code-value">{{ $coa->kd_suboutput ?: '-' }}</div></div>
                <div class="code-item"><div class="code-label">Kd Komponen</div><div class="code-value">{{ $coa->kd_komponen ?: '-' }}</div></div>
                <div class="code-item"><div class="code-label">Kd Subkomponen</div><div class="code-value">{{ $coa->kd_subkomponen ?: '-' }}</div></div>
                <div class="code-item border-primary"><div class="code-label text-primary">Kode Akun</div><div class="code-value text-primary">{{ $coa->kd_akun ?: '-' }}</div></div>
                <div class="code-item"><div class="code-label">Kd Item</div><div class="code-value">{{ $coa->kd_item ?: '-' }}</div></div>
            </div>
        </div>
    </div>

    <!-- Usage on Bills (Tagihan) -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom pt-3 pb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0 fw-bold"><i class="bi bi-receipt me-2"></i> Tagihan yang Menggunakan COA</h6>
            <div class="d-flex gap-2">
                <span class="badge bg-light text-dark border">{{ number_format($billingStatistics['jumlah_pengeluaran']) }} Pengeluaran</span>
                <span class="badge bg-light text-dark border">{{ number_format($billingStatistics['jumlah_penerimaan']) }} Penerimaan</span>
            </div>
        </div>
        <div class="card-body p-0">
            @if($billingUsages->isEmpty())
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                    <p class="mb-0">Belum ada tagihan yang menggunakan COA ini.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center px-3" width="5%">No</th>
                                <th width="15%">Jenis & Tipe</th>
                                <th width="20%">No. Dokumen & Tanggal</th>
                                <th width="25%">Uraian & Pihak</th>
                                <th width="15%" class="text-end">Nominal</th>
                                <th width="10%" class="text-center">Status</th>
                                <th width="10%" class="text-center px-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($billingUsages as $usage)
                                @php
                                    $normalizedStatus = strtoupper((string) $usage['status']);
                                    $statusBadge = match (true) {
                                        str_contains($normalizedStatus, 'UNPAID'),
                                        str_contains($normalizedStatus, 'PENDING'),
                                        str_contains($normalizedStatus, 'MENUNGGU'),
                                        str_contains($normalizedStatus, 'DRAFT') => 'bg-warning text-dark',
                                        str_contains($normalizedStatus, 'PARTIAL'),
                                        str_contains($normalizedStatus, 'PROSES') => 'bg-info text-dark',
                                        $normalizedStatus === 'PAID',
                                        str_contains($normalizedStatus, 'SETUJUI'),
                                        str_contains($normalizedStatus, 'SELESAI'),
                                        str_contains($normalizedStatus, 'APPROVED') => 'bg-success',
                                        str_contains($normalizedStatus, 'REVISI'),
                                        str_contains($normalizedStatus, 'REVISION'),
                                        str_contains($normalizedStatus, 'TOLAK'),
                                        str_contains($normalizedStatus, 'REJECT') => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                    $kategoriBadge = $usage['kategori'] === 'Penerimaan' ? 'bg-success' : 'bg-primary';
                                @endphp
                                <tr>
                                    <td class="text-center px-3">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="badge {{ $kategoriBadge }} mb-1">{{ $usage['kategori'] }}</span><br>
                                        <span class="small text-muted">{{ $usage['tipe'] }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $usage['nomor'] }}</div>
                                        <div class="small text-muted">{{ optional($usage['tanggal'])->format('d M Y') ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 250px;" title="{{ $usage['uraian'] }}">{{ $usage['uraian'] ?: '-' }}</div>
                                        <div class="small fw-semibold text-muted text-truncate" style="max-width: 250px;" title="{{ $usage['pihak'] }}"><i class="bi bi-person me-1"></i>{{ $usage['pihak'] ?: '-' }}</div>
                                    </td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($usage['nominal'], 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $statusBadge }}">{{ $usage['status'] }}</span>
                                    </td>
                                    <td class="text-center px-3">
                                        @if($usage['detail_url'])
                                            <a href="{{ $usage['detail_url'] }}" class="btn btn-sm btn-outline-secondary py-1 px-2">Lihat</a>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Usage on DIPA -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom pt-3 pb-3">
            <h6 class="mb-0 fw-bold"><i class="bi bi-folder2-open me-2"></i> Pemakaian COA pada DIPA</h6>
        </div>
        <div class="card-body p-0">
            @if($usageItems->isEmpty())
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                    <p class="mb-0">COA ini belum dipakai pada item anggaran DIPA mana pun.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center px-3" width="5%">No</th>
                                <th width="25%">DIPA & Tahun</th>
                                <th width="15%">Revisi</th>
                                <th width="15%" class="text-end">Pagu</th>
                                <th width="15%" class="text-end">Realisasi</th>
                                <th width="15%" class="text-end">Sisa Pagu</th>
                                <th width="10%" class="text-center px-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($usageItems as $item)
                                @php
                                    $revision = $item->dipaRevision;
                                    $dipa = $revision?->masterDipa;
                                    $totalRealisasi = (float) $item->total_realisasi;
                                    $sisaPagu = (float) $item->sisa_pagu;
                                @endphp
                                <tr>
                                    <td class="text-center px-3">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $dipa?->nomor_dipa ?: '-' }}</div>
                                        <div class="small">
                                            <span class="badge bg-light text-dark border me-1">TA {{ $dipa?->tahun_anggaran ?: '-' }}</span>
                                            @if($dipa && !$dipa->status_aktif)
                                                <span class="badge bg-secondary text-white">Nonaktif</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">Revisi {{ $revision?->nomor_revisi ?? '-' }}</div>
                                        <span class="badge {{ $revision?->is_active ? 'bg-success' : 'bg-secondary' }} mt-1">
                                            {{ $revision?->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="text-end">Rp {{ number_format($item->nilai_pagu, 0, ',', '.') }}</td>
                                    <td class="text-end text-primary fw-semibold">Rp {{ number_format($totalRealisasi, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold {{ $sisaPagu < 0 ? 'text-danger' : 'text-success' }}">
                                        @if($sisaPagu < 0)
                                            <i class="bi bi-arrow-down-short text-danger"></i>
                                        @endif
                                        Rp {{ number_format($sisaPagu, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center px-3">
                                        @if($dipa)
                                            <a href="{{ route('dipas.show', $dipa) }}" class="btn btn-sm btn-outline-secondary py-1 px-2">Detail</a>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
