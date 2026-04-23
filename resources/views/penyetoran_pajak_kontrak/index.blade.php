@extends('layouts.app')
@section('title', 'Penyetoran Pajak Kontrak')

@push('css')
<style>
    .summary-card { background: #fff; border: 1px solid rgba(15,23,42,.08); border-radius: .75rem; padding: 1.25rem; transition: all .2s; cursor: pointer; text-decoration: none; display: block; }
    .summary-card:hover { box-shadow: 0 .25rem .75rem rgba(15,23,42,.08); transform: translateY(-2px); }
    .summary-card .sc-value { font-size: 1.75rem; font-weight: 800; line-height: 1; }
    .summary-card .sc-label { font-size: .78rem; color: #64748b; font-weight: 600; letter-spacing: .03em; margin-top: .35rem; }
    .filter-bar { background: #fff; border: 1px solid rgba(15,23,42,.06); border-radius: .75rem; padding: 1rem 1.25rem; margin-bottom: 1.25rem; }
    .table-pajak th { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #64748b; white-space: nowrap; }
    .table-pajak td { font-size: .85rem; vertical-align: middle; }
    .badge-billing { background: rgba(234,179,8,.15); color: #a16207; }
    .badge-setor { background: rgba(34,197,94,.15); color: #15803d; }
    .badge-belum { background: rgba(100,116,139,.12); color: #475569; }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <x-page-title title="Penyetoran Pajak Kontrak" subtitle="Kelola Kode Billing dan NTPN untuk potongan pajak dari tagihan kontrak yang sudah final." />
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
            <div class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill fs-5"></i><div class="mt-1">{{ session('success') }}</div></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <a href="{{ route('pajak-potongan.kontrak.index', ['status' => 'belum_billing']) }}" class="summary-card">
                <div class="sc-value text-secondary">{{ $summary['belum_billing'] }}</div>
                <div class="sc-label"><i class="bi bi-hourglass-split me-1"></i>Belum Billing</div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('pajak-potongan.kontrak.index', ['status' => 'sudah_billing']) }}" class="summary-card">
                <div class="sc-value text-warning">{{ $summary['sudah_billing'] }}</div>
                <div class="sc-label"><i class="bi bi-credit-card me-1"></i>Sudah Billing</div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('pajak-potongan.kontrak.index', ['status' => 'sudah_setor']) }}" class="summary-card">
                <div class="sc-value text-success">{{ $summary['sudah_setor'] }}</div>
                <div class="sc-label"><i class="bi bi-check-circle me-1"></i>Sudah Setor (NTPN)</div>
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-bar">
        <form method="GET" action="{{ route('pajak-potongan.kontrak.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Cari</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Tagihan, vendor, billing, NTPN..." value="{{ $search ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Status Setor</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="semua" {{ ($statusFilter ?? 'semua') === 'semua' ? 'selected' : '' }}>Semua</option>
                    <option value="belum_billing" {{ ($statusFilter ?? '') === 'belum_billing' ? 'selected' : '' }}>Belum Billing</option>
                    <option value="sudah_billing" {{ ($statusFilter ?? '') === 'sudah_billing' ? 'selected' : '' }}>Sudah Billing</option>
                    <option value="sudah_setor" {{ ($statusFilter ?? '') === 'sudah_setor' ? 'selected' : '' }}>Sudah Setor</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Filter</button>
                <a href="{{ route('pajak-potongan.kontrak.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-pajak align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tagihan / Kontrak</th>
                            <th>Vendor</th>
                            <th>Jenis Potongan</th>
                            <th class="text-end">Nominal</th>
                            <th>Kode Billing</th>
                            <th>NTPN</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($potonganList as $idx => $pot)
                            @php
                                $tagihan = $pot->tagihan;
                                $dk = $tagihan?->detailKontrak;
                                $kontrak = $dk?->kontrakTermin?->kontrak;
                                $vendor = $kontrak?->vendor ?? $tagihan?->pihak;
                                $statusSetor = 'Belum Billing';
                                $badgeClass = 'badge-belum';
                                if ($pot->ntpn) { $statusSetor = 'Sudah Setor'; $badgeClass = 'badge-setor'; }
                                elseif ($pot->kode_billing) { $statusSetor = 'Sudah Billing'; $badgeClass = 'badge-billing'; }
                            @endphp
                            <tr>
                                <td class="text-muted">{{ $idx + 1 }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $tagihan?->nomor_tagihan ?? '-' }}</div>
                                    <div class="text-muted small">{{ $kontrak?->nomor_kontrak ?? Str::limit($tagihan?->deskripsi, 40) }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold small">{{ $vendor?->nama ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $pot->jenis_potongan }}</div>
                                    <div class="text-muted small">{{ $pot->nama_pajak_snapshot ?? '-' }}</div>
                                </td>
                                <td class="text-end fw-bold">Rp {{ number_format($pot->nominal_potongan, 0, ',', '.') }}</td>
                                <td>
                                    @if($pot->kode_billing)
                                        <span class="font-monospace text-primary small">{{ $pot->kode_billing }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($pot->ntpn)
                                        <span class="font-monospace text-success small">{{ $pot->ntpn }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td><span class="badge {{ $badgeClass }} px-2 py-1">{{ $statusSetor }}</span></td>
                                <td class="text-center">
                                    <a href="{{ route('pajak-potongan.kontrak.detail', $pot->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i>Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                    Belum ada potongan pajak kontrak yang siap diproses.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
