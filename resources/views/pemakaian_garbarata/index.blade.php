@extends('layouts.app')
@section('title', 'Pemakaian Garbarata')

@section('content')
@include('super_admin_jasa.laporan._styles')

<style>
    .mitra-card { border: 0; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 28px rgba(15, 47, 87, .08); }
    .mitra-header { background: linear-gradient(135deg, #0f2f57, #1d4ed8); color: #fff; padding: 16px 20px; cursor: pointer; --bs-heading-color: #fff; }
    .mitra-header.collapsed { background: linear-gradient(135deg, #334155, #475569); }
    .mitra-header h5 { margin: 0; font-weight: 800; color: #fff; }
    .mitra-header .mitra-meta { color: rgba(255,255,255,.85); font-size: .82rem; }
    .mitra-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px; background: rgba(255,255,255,.18); font-weight: 700; font-size: .78rem; }
    .mitra-pill.warning { background: rgba(251, 191, 36, .25); color: #fde68a; }
    .mitra-pill.success { background: rgba(16, 185, 129, .25); color: #a7f3d0; }
    .mitra-body { background: #fff; }
    .mitra-body table { font-size: .85rem; margin-bottom: 0; }
    .mitra-body thead th { background: #f1f5f9; color: #334155; text-transform: uppercase; font-size: .7rem; letter-spacing: .04em; white-space: nowrap; }
    .mitra-body tbody td { vertical-align: middle; }
    .stat-tile { border: 0; border-radius: 14px; box-shadow: 0 8px 22px rgba(15, 47, 87, .08); padding: 14px 18px; background: #fff; }
    .stat-tile .label { font-size: .7rem; letter-spacing: .08em; text-transform: uppercase; color: #64748b; font-weight: 700; }
    .stat-tile .value { font-weight: 800; font-size: 1.5rem; color: #0f172a; }
    .stat-tile .value.success { color: #059669; }
    .chevron { transition: transform .2s; }
    .chevron.collapsed { transform: rotate(-90deg); }
</style>

<div class="sa-report-page">
    <div class="sa-report-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="small text-uppercase fw-bold" style="letter-spacing:.08em;color:#fbbf24;">AMC &middot; Operasional Apron</div>
            <h4 class="fw-bold mb-1"><i class="bi bi-bridge me-2"></i>Pemakaian Garbarata (Aviobridge)</h4>
            <p class="mb-0 small">Data dikelompokkan per maskapai. Klik baris maskapai untuk melihat detail penerbangan.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('pemakaian-garbarata.rekap-harian') }}" class="btn btn-outline-light fw-bold">
                <i class="bi bi-calendar-week me-1"></i>Rekap Harian
            </a>
            @if(auth()->user()?->hasAnyRole(['Super Admin', 'AMC']))
                <a href="{{ route('pemakaian-garbarata.create') }}" class="btn btn-warning fw-bold">
                    <i class="bi bi-plus-lg me-1"></i>Catat Pemakaian
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-uppercase">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach($statusOpt as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase">Mitra</label>
                    <select name="mitra_jasa_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach($mitraOptions as $m)
                            <option value="{{ $m->id }}" @selected(($filters['mitra_jasa_id'] ?? '') == $m->id)>{{ $m->nama_mitra }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-uppercase">Tgl Dari</label>
                    <input type="date" name="tanggal_dari" class="form-control" value="{{ $filters['tanggal_dari'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-uppercase">Tgl Sampai</label>
                    <input type="date" name="tanggal_sampai" class="form-control" value="{{ $filters['tanggal_sampai'] }}">
                </div>
                <div class="col-md-3 d-grid">
                    <button class="btn btn-primary fw-bold"><i class="bi bi-funnel me-1"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-6">
            <div class="stat-tile">
                <div class="label">Total Maskapai</div>
                <div class="value">{{ $summary['total_mitra'] }}</div>
            </div>
        </div>
        <div class="col-md-6 col-6">
            <div class="stat-tile">
                <div class="label">Total Penerbangan</div>
                <div class="value">{{ $summary['total_flight'] }}</div>
            </div>
        </div>
    </div>

    @forelse($grouped as $idx => $g)
        @php
            $cardId = 'mitra-block-' . $idx;
            $eligibleCount = ($g['status_counts']['DRAFT'] ?? 0) + ($g['status_counts']['SIAP_DITAGIH'] ?? 0);
            $periodeAjuan = \Carbon\Carbon::parse($filters['tanggal_dari'])->format('Y-m');
            $canAjukan = auth()->user()?->hasAnyRole(['Super Admin', 'AMC']) && $eligibleCount > 0 && $g['mitra'];
        @endphp
        <div class="card mitra-card mb-3">
            <div class="mitra-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3 flex-grow-1" data-bs-toggle="collapse" data-bs-target="#{{ $cardId }}" aria-expanded="true" style="cursor:pointer;">
                    <i class="bi bi-chevron-down chevron"></i>
                    <div>
                        <h5><i class="bi bi-airplane-fill me-2"></i>{{ $g['mitra']?->nama_mitra ?? 'Mitra tidak diketahui' }}</h5>
                        <div class="mitra-meta">{{ $g['count'] }} penerbangan &middot; {{ $g['total_rentang'] }} rentang &middot; {{ $g['total_durasi'] }} menit</div>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap justify-content-end align-items-center">
                    @foreach($g['status_counts'] as $stCode => $stCount)
                        <span class="mitra-pill {{ $stCode === 'TERTAGIH' ? 'success' : ($stCode === 'DIAJUKAN' ? 'warning' : '') }}">{{ $statusOpt[$stCode] ?? $stCode }}: {{ $stCount }}</span>
                    @endforeach
                    @if($canAjukan)
                        <a href="{{ route('pengajuan-penagihan-garbarata.create', ['mitra_jasa_id' => $g['mitra']->id, 'periode_bulan' => $periodeAjuan]) }}"
                           class="btn btn-sm btn-warning fw-bold"
                           title="Ambil {{ $eligibleCount }} pemakaian eligible untuk rekap tagihan">
                            <i class="bi bi-download me-1"></i>Ambil Data Tagihan
                        </a>
                    @endif
                </div>
            </div>
            <div id="{{ $cardId }}" class="collapse show">
                <div class="mitra-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:40px;">#</th>
                                    <th>Tanggal</th>
                                    <th>Flight</th>
                                    <th>Reg</th>
                                    <th>Route</th>
                                    <th class="text-center">Docking</th>
                                    <th class="text-center">Undocking</th>
                                    <th class="text-end">Durasi</th>
                                    <th class="text-center">Rentang</th>
                                    <th class="text-center">Avio</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($g['rows'] as $i => $row)
                                    <tr>
                                        <td class="text-center text-muted small">{{ $i + 1 }}</td>
                                        <td>
                                            <div class="fw-bold small">{{ $row->tanggal?->format('d/m/Y') }}</div>
                                            @if($row->permohonan)
                                                <span class="badge bg-info-subtle text-info-emphasis small"><i class="bi bi-paperclip"></i> {{ $row->permohonan->nomor_surat }}</span>
                                            @endif
                                        </td>
                                        <td class="fw-bold">{{ $row->nomor_penerbangan ?? '-' }}</td>
                                        <td>{{ $row->registrasi_pesawat ?? '-' }}</td>
                                        <td class="small text-muted">{{ $row->route ?? '-' }}</td>
                                        <td class="text-center small">{{ $row->docking_at?->format('H:i') }}</td>
                                        <td class="text-center small">{{ $row->undocking_at?->format('H:i') }}</td>
                                        <td class="text-end">{{ $row->durasi_menit }} mnt</td>
                                        <td class="text-center fw-bold text-primary">{{ $row->jumlah_rentang }}</td>
                                        <td class="text-center">{{ $row->nomor_avio ?? '-' }}</td>
                                        <td class="text-end fw-bold text-success">Rp {{ number_format((float) $row->total_garbarata, 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $row->status_badge }}">{{ $row->status_label }}</span>
                                            @if($row->tagihan)
                                                <div class="small text-muted mt-1">{{ $row->tagihan->nomor_tagihan }}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                @if($row->file_pendukung)
                                                    <a href="{{ route('pemakaian-garbarata.file', $row->id) }}" target="_blank" class="btn btn-outline-secondary" title="File"><i class="bi bi-paperclip"></i></a>
                                                @endif
                                                @if(auth()->user()?->hasRole('Super Admin') || (auth()->id() === $row->created_by && $row->status !== 'TERTAGIH'))
                                                    <a href="{{ route('pemakaian-garbarata.edit', $row->id) }}" class="btn btn-outline-primary" title="Ubah"><i class="bi bi-pencil"></i></a>
                                                    <form method="POST" action="{{ route('pemakaian-garbarata.destroy', $row->id) }}" onsubmit="return confirm('Hapus catatan ini?')" class="d-inline">
                                                        @csrf @method('DELETE')
                                                        <button class="btn btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
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
    @empty
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                Belum ada catatan pemakaian garbarata pada rentang tanggal ini.
            </div>
        </div>
    @endforelse
</div>

@push('script')
<script>
    document.querySelectorAll('.mitra-header').forEach(function (el) {
        const targetSel = el.getAttribute('data-bs-target');
        if (!targetSel) return;
        const target = document.querySelector(targetSel);
        const chev = el.querySelector('.chevron');
        if (!target || !chev) return;
        target.addEventListener('hide.bs.collapse', function () { chev.classList.add('collapsed'); el.classList.add('collapsed'); });
        target.addEventListener('show.bs.collapse', function () { chev.classList.remove('collapsed'); el.classList.remove('collapsed'); });
    });
</script>
@endpush
@endsection
