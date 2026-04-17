@extends('layouts.app')

@section('content')
<!--breadcrumb-->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Pembuatan NPI</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="javascript:;"><i class="material-icons-outlined" style="font-size: 16px; margin-bottom: -3px;">home</i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Perjaldin (Bendahara Pengeluaran)</li>
            </ol>
        </nav>
    </div>
</div>
<!--end breadcrumb-->

<!-- Summary Cards -->
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-4">
    <div class="col">
        <div class="card radius-10 border-start border-0 border-4 border-secondary">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div>
                        <p class="mb-0 text-secondary">Belum Ada NPI</p>
                        <h4 class="my-1 text-secondary">{{ $summary['belum_dibuat'] }}</h4>
                        <p class="mb-0 font-13">Draft perlu dibuat</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card radius-10 border-start border-0 border-4 border-warning">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div>
                        <p class="mb-0 text-secondary">Draft / Revisi</p>
                        <h4 class="my-1 text-warning">{{ $summary['draft_revisi'] }}</h4>
                        <p class="mb-0 font-13">Perlu dilengkapi / diperbaiki</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card radius-10 border-start border-0 border-4 border-info">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div>
                        <p class="mb-0 text-secondary">Menunggu Verifikasi</p>
                        <h4 class="my-1 text-info">{{ $summary['menunggu'] }}</h4>
                        <p class="mb-0 font-13">Proses paralel verifikator</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card radius-10 border-start border-0 border-4 border-success">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div>
                        <p class="mb-0 text-secondary">Selesai</p>
                        <h4 class="my-1 text-success">{{ $summary['selesai'] }}</h4>
                        <p class="mb-0 font-13">NPI siap diproses SP2D</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Data Table Card -->
<div class="card radius-10 shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <!-- Filter Form -->
        <form action="{{ route('npis.perjaldin.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label for="status" class="form-label d-none">Status NPI</label>
                <select name="status" id="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="semua" {{ $statusFilter == 'semua' ? 'selected' : '' }}>Semua Status</option>
                    <option value="belum_dibuat" {{ $statusFilter == 'belum_dibuat' ? 'selected' : '' }}>Belum Dibuat</option>
                    <option value="draft" {{ $statusFilter == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="revisi" {{ $statusFilter == 'revisi' ? 'selected' : '' }}>Revisi</option>
                    <option value="menunggu" {{ $statusFilter == 'menunggu' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                    <option value="selesai" {{ $statusFilter == 'selesai' ? 'selected' : '' }}>Selesai / Terbit</option>
                </select>
            </div>
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Cari NPI/SPM/Tagihan..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit"><i class="material-icons-outlined" style="font-size: 16px; margin-bottom: -3px;">search</i></button>
                    @if($search)
                        <a href="{{ route('npis.perjaldin.index') }}" class="btn btn-outline-danger" title="Clear Search"><i class="material-icons-outlined" style="font-size: 16px; margin-bottom: -3px;">close</i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0 table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th width="15%">No. NPI</th>
                        <th width="20%">Sumber Dokumen</th>
                        <th width="25%">Tagihan Perjaldin & Akun</th>
                        <th width="10%" class="text-end">Nilai (Rp)</th>
                        <th width="15%" class="text-center">Status Verifikasi</th>
                        <th width="10%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($spmList as $index => $spm)
                        @php
                            $npi = $spm->npi;
                            $spp = $spm->spp;
                            $tagihan = $spp?->tagihan;
                            $komponen = $spp?->tagihanPerjaldinKomponen;
                            
                            $nilai = $spm->nominal_spm ?? 0;
                            
                            $approvals = collect($npi?->workflowInstances?->first()?->approvals ?? []);
                            $bpStatus = $approvals->firstWhere('role_code', 'Bendahara Penerimaan')?->status ?? '-';
                            $ppkStatus = $approvals->firstWhere('role_code', 'PPK')?->status ?? '-';
                            $kasStatus = $approvals->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')?->status ?? '-';
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>
                                @if($npi && $npi->nomor_npi)
                                    <span class="fw-bold text-primary"><i class="material-icons-outlined text-primary" style="font-size: 14px; margin-bottom: -2px;">receipt</i> {{ $npi->nomor_npi }}</span>
                                @else
                                    <span class="badge bg-secondary">Belum Dibuat NPI</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <small class="mb-1"><strong class="text-secondary">SPM:</strong> {{ $spm->nomor_spm }}</small>
                                    <small class="mb-1"><strong class="text-secondary">SPP:</strong> {{ $spp?->nomor_spp }}</small>
                                </div>
                            </td>
                            <td>
                                <div class="text-wrap" style="max-width: 250px;">
                                    <div class="fw-bold mb-1">{{ $tagihan?->deskripsi ?? 'Perjalanan Dinas' }}</div>
                                    <small class="text-muted d-block">{{ $komponen?->nama_komponen ?? '-' }}</small>
                                    @if($komponen?->dipaRevisionItem?->coa)
                                        <small class="badge bg-light text-dark border">{{ $komponen->dipaRevisionItem->coa->kode_akun }}</small>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end fw-bold font-14 text-success">
                                {{ number_format($nilai, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if(!$npi)
                                    <span class="badge bg-warning text-dark mb-1">DRAFT REQUIRED</span>
                                @else
                                    @php
                                        $badgeClass = match($npi->status) {
                                            \App\Models\DokumenNpi::STATUS_DRAFT => 'bg-secondary',
                                            \App\Models\DokumenNpi::STATUS_REVISI => 'bg-danger',
                                            \App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL, \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG => 'bg-success',
                                            default => 'bg-info text-white'
                                        };
                                        $textBadge = match($npi->status) {
                                            \App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL, \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG  => 'Selesai',
                                            \App\Models\DokumenNpi::STATUS_DRAFT => 'Draft NPI',
                                            \App\Models\DokumenNpi::STATUS_REVISI => 'Perlu Revisi',
                                            default => 'Menunggu Verif'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} mb-1 d-block w-100 text-start">
                                        {{ $textBadge }}
                                    </span>
                                    
                                    @if(in_array($npi->status, [\App\Models\DokumenNpi::STATUS_MENUNGGU_VERIFIKASI, \App\Models\DokumenNpi::STATUS_SUBMITTED_BENPEN, \App\Models\DokumenNpi::STATUS_SUBMITTED_PPK, \App\Models\DokumenNpi::STATUS_SUBMITTED_KASUBAG]))
                                        <div class="d-flex flex-column gap-1 w-100 mt-1">
                                            <span class="badge {{ $bpStatus == 'APPROVED' ? 'bg-success' : ($bpStatus == 'REVISION' ? 'bg-danger' : 'bg-light text-dark border') }} w-100 text-start" style="font-size: 10px;">
                                                BENPEN: {{ $bpStatus }}
                                            </span>
                                            <span class="badge {{ $ppkStatus == 'APPROVED' ? 'bg-success' : ($ppkStatus == 'REVISION' ? 'bg-danger' : 'bg-light text-dark border') }} w-100 text-start" style="font-size: 10px;">
                                                PPK: {{ $ppkStatus }}
                                            </span>
                                            <span class="badge {{ $kasStatus == 'APPROVED' ? 'bg-success' : ($kasStatus == 'REVISION' ? 'bg-danger' : 'bg-light text-dark border') }} w-100 text-start" style="font-size: 10px;">
                                                KASUBBAG: {{ $kasStatus }}
                                            </span>
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="text-center">
                                @if(!$npi || in_array($npi->status, [\App\Models\DokumenNpi::STATUS_DRAFT, \App\Models\DokumenNpi::STATUS_REVISI]))
                                    <a href="{{ route('npis.perjaldin.detail', $spm->id) }}" class="btn btn-sm btn-primary w-100">
                                        {{ !$npi ? 'Buat NPI' : 'Lanjut Draft' }}
                                    </a>
                                @else
                                    <a href="{{ route('npis.perjaldin.detail', $spm->id) }}" class="btn btn-sm btn-outline-info w-100">
                                        Detail
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted"><i class="material-icons-outlined" style="font-size: 40px;">folder_open</i></div>
                                <p class="mb-0 mt-2">Tidak ada data SPM Perjaldin final yang ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
