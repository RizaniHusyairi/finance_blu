@extends('layouts.app')
@section('title', 'Verifikasi NPI Kontrak — Bendahara Penerimaan')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
    <div>
        <h5 class="fw-bold text-primary mb-0">Verifikasi NPI</h5>
        <p class="text-muted mb-0">Kontrak — Bendahara Penerimaan</p>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="material-icons-outlined text-warning" style="font-size: 24px;">hourglass_top</i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold">{{ $summary['pending'] }}</h3>
                        <small class="text-muted">Menunggu Verifikasi Saya</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="material-icons-outlined text-success" style="font-size: 24px;">check_circle</i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold">{{ $summary['approved'] }}</h3>
                        <small class="text-muted">Sudah Saya Setujui</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="material-icons-outlined text-danger" style="font-size: 24px;">replay</i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold">{{ $summary['revision'] }}</h3>
                        <small class="text-muted">Perlu Revisi</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="material-icons-outlined text-primary" style="font-size: 24px;">verified</i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold">{{ $summary['selesai'] }}</h3>
                        <small class="text-muted">Selesai Diverifikasi</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('verifikasi-bendahara-penerimaan.npi.kontrak.index') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Status Saya</label>
                <select name="status_benpen" class="form-select form-select-sm">
                    <option value="semua" {{ $filterBenpen === 'semua' ? 'selected' : '' }}>Semua</option>
                    <option value="pending" {{ $filterBenpen === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ $filterBenpen === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="revision" {{ $filterBenpen === 'revision' ? 'selected' : '' }}>Revisi</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Status PPK</label>
                <select name="status_ppk" class="form-select form-select-sm">
                    <option value="semua" {{ $filterPpk === 'semua' ? 'selected' : '' }}>Semua</option>
                    <option value="pending" {{ $filterPpk === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ $filterPpk === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="revision" {{ $filterPpk === 'revision' ? 'selected' : '' }}>Revisi</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Status Kasubbag</label>
                <select name="status_kasubbag" class="form-select form-select-sm">
                    <option value="semua" {{ $filterKasubbag === 'semua' ? 'selected' : '' }}>Semua</option>
                    <option value="pending" {{ $filterKasubbag === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ $filterKasubbag === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="revision" {{ $filterKasubbag === 'revision' ? 'selected' : '' }}>Revisi</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Pencarian</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Nomor NPI, SPM, SPP, SPK, vendor, pekerjaan..." value="{{ $search }}">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="material-icons-outlined" style="font-size:16px; vertical-align: middle;">search</i> Filter</button>
                <a href="{{ route('verifikasi-bendahara-penerimaan.npi.kontrak.index') }}" class="btn btn-outline-secondary btn-sm"><i class="material-icons-outlined" style="font-size:16px; vertical-align: middle;">refresh</i></a>
            </div>
        </form>
    </div>
</div>

{{-- Tabel Antrean --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 40px;">No</th>
                        <th>Nomor NPI</th>
                        <th>SPM / SPP / SPK</th>
                        <th>Vendor / Pekerjaan</th>
                        <th class="text-end">Nilai NPI</th>
                        <th class="text-center">BenPen</th>
                        <th class="text-center">PPK</th>
                        <th class="text-center">Kasubbag</th>
                        <th class="text-center">Status Final</th>
                        <th class="text-center" style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($npiList as $idx => $npi)
                        @php
                            $spm = $npi->spm;
                            $spp = $spm?->spp;
                            $kontrak = $spp?->tagihan?->detailKontrak?->kontrakTermin?->kontrak;
                            $vendor = $kontrak?->vendor;
                            $nominal = (float) ($spp?->nominal_spp ?? $spp?->tagihan?->total_netto ?? 0);
                        @endphp
                        <tr>
                            <td class="text-center text-muted">{{ $idx + 1 }}</td>
                            <td>
                                <span class="fw-bold text-primary">{{ $npi->nomor_npi ?? 'Draft / Belum Bernomor' }}</span>
                                <div class="text-muted" style="font-size: 11px;">{{ optional($npi->tanggal_npi)->format('d M Y') ?? '-' }}</div>
                            </td>
                            <td>
                                <div style="font-size: 12px; line-height: 1.6;">
                                    <span class="text-muted">SPM:</span> <span class="fw-semibold">{{ $spm?->nomor_spm ?? '-' }}</span><br>
                                    <span class="text-muted">SPP:</span> {{ $spp?->nomor_spp ?? '-' }}<br>
                                    <span class="text-muted">SPK:</span> {{ $kontrak?->nomor_spk ?? '-' }}
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-truncate" style="max-width: 180px;">{{ $vendor?->nama_pihak ?? '-' }}</div>
                                <div class="text-muted text-truncate" style="max-width: 180px; font-size: 12px;">{{ $kontrak?->nama_pekerjaan ?? '-' }}</div>
                            </td>
                            <td class="text-end fw-bold">Rp {{ number_format($nominal, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @php($bs = $npi->_benpenApproval?->status)
                                <span class="badge {{ $bs === 'APPROVED' ? 'bg-success' : ($bs === 'PENDING' ? 'bg-warning text-dark' : (in_array($bs, ['REVISION', 'REJECTED']) ? 'bg-danger' : 'bg-light text-dark border')) }}">
                                    {{ $bs ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="text-center">
                                @php($ps = $npi->_ppkApproval?->status)
                                <span class="badge {{ $ps === 'APPROVED' ? 'bg-success' : ($ps === 'PENDING' ? 'bg-warning text-dark' : (in_array($ps, ['REVISION', 'REJECTED']) ? 'bg-danger' : 'bg-light text-dark border')) }}">
                                    {{ $ps ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="text-center">
                                @php($ks = $npi->_kasubbagApproval?->status)
                                <span class="badge {{ $ks === 'APPROVED' ? 'bg-success' : ($ks === 'PENDING' ? 'bg-warning text-dark' : (in_array($ks, ['REVISION', 'REJECTED']) ? 'bg-danger' : 'bg-light text-dark border')) }}">
                                    {{ $ks ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="text-center">
                                @php($sf = $npi->_statusFinal)
                                <span class="badge {{ $sf === 'Selesai Diverifikasi' ? 'bg-success' : (str_contains($sf, 'Revisi') ? 'bg-danger' : 'bg-info') }}" style="font-size: 11px;">
                                    {{ $sf }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($npi->_benpenApproval?->status === 'PENDING')
                                    <a href="{{ route('verifikasi-bendahara-penerimaan.npi.kontrak.show', $npi->id) }}" class="btn btn-sm btn-primary px-3">
                                        <i class="material-icons-outlined" style="font-size:14px; vertical-align: middle;">fact_check</i> Verifikasi
                                    </a>
                                @else
                                    <a href="{{ route('verifikasi-bendahara-penerimaan.npi.kontrak.show', $npi->id) }}" class="btn btn-sm btn-outline-secondary px-3">
                                        <i class="material-icons-outlined" style="font-size:14px; vertical-align: middle;">visibility</i> Detail
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="material-icons-outlined" style="font-size: 48px; opacity: 0.3;">inbox</i>
                                <div class="mt-2">Tidak ada NPI Kontrak yang memenuhi filter.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
