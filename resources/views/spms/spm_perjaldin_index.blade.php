@extends('layouts.app')
@section('title', 'SPM Perjaldin — Operator BLU')

@php
    $statusSpmClass = fn($spp) => match ($spp->spm?->status ?? 'Belum Dibuat') {
        'Belum Dibuat' => 'bg-secondary',
        'DRAFT' => 'bg-warning text-dark',
        'Menunggu Verifikasi' => 'bg-info',
        'Revisi' => 'bg-danger',
        'Disetujui Final', 'Menunggu Upload SPM', 'SPM_TERBIT' => 'bg-success',
        default => 'bg-secondary',
    };
    $statusSpmLabel = fn($spp) => match ($spp->spm?->status ?? 'Belum Dibuat') {
        'DRAFT' => 'Draft',
        'Menunggu Verifikasi' => 'Menunggu Verifikasi',
        'Revisi' => 'Revisi',
        'Disetujui Final', 'Menunggu Upload SPM', 'SPM_TERBIT' => 'SPM Terbit',
        default => 'Belum Dibuat',
    };
    $verificationStatusClass = fn($status) => match ($status ?? '-') {
        'APPROVED' => 'bg-success-subtle text-success',
        'PENDING' => 'bg-warning-subtle text-warning',
        'REVISION', 'REJECTED' => 'bg-danger-subtle text-danger',
        'WAITING' => 'bg-light text-muted',
        default => 'bg-light text-muted',
    };
@endphp

@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .spm-verif-badge { display: inline-flex; align-items: center; gap: .25rem; font-size: .72rem; font-weight: 600; padding: .15rem .5rem; border-radius: .35rem; }
        .stat-card {
            transition: transform .2s ease, box-shadow .2s ease;
            min-height: 175px;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 .75rem 1.25rem rgba(0,0,0,.08) !important;
        }
        .stat-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: 1.25rem;
        }
        .stat-value {
            font-size: 1.65rem;
            line-height: 1.2;
            letter-spacing: -.5px;
        }
        .stat-deco {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 60px;
            overflow: hidden;
            pointer-events: none;
        }
        .stat-wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 200%;
            height: 100%;
        }
        .stat-wave-1 { opacity: .18; animation: stat-wave-scroll 9s linear infinite; }
        .stat-wave-2 { opacity: .28; animation: stat-wave-scroll 13s linear infinite reverse; }
        .stat-wave-3 { opacity: .40; animation: stat-wave-scroll 17s linear infinite; }
        @keyframes stat-wave-scroll {
            from { transform: translate3d(0, 0, 0); }
            to   { transform: translate3d(-50%, 0, 0); }
        }
        @media (prefers-reduced-motion: reduce) {
            .stat-wave-1, .stat-wave-2, .stat-wave-3 { animation: none; }
        }
    </style>
@endpush

@section('content')
    <x-page-title title="Pembuatan SPM" subtitle="SPM Perjaldin" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show">
            <div class="text-white">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4 mt-2">
        <div class="col">
            @include('spps.partials.stat-card', [
                'icon' => 'bi-hourglass-split',
                'color' => 'warning',
                'category' => 'Antrean SPM',
                'value' => $summary['belum_dibuat'] ?? 0,
                'description' => 'SPP siap dibuatkan SPM',
                'badge' => 'Pending',
                'badgeColor' => 'warning',
            ])
        </div>
        <div class="col">
            @include('spps.partials.stat-card', [
                'icon' => 'bi-pencil-square',
                'color' => 'primary',
                'category' => 'Draft / Revisi',
                'value' => $summary['draft_revisi'] ?? 0,
                'description' => 'Sedang disusun / revisi',
                'badge' => 'Draft',
                'badgeColor' => 'primary',
            ])
        </div>
        <div class="col">
            @include('spps.partials.stat-card', [
                'icon' => 'bi-arrow-repeat',
                'color' => 'info',
                'category' => 'Verifikasi',
                'value' => $summary['menunggu'] ?? 0,
                'description' => 'Menunggu verifikasi',
                'badge' => 'On Going',
                'badgeColor' => 'info',
            ])
        </div>
        <div class="col">
            @include('spps.partials.stat-card', [
                'icon' => 'bi-check2-all',
                'color' => 'success',
                'category' => 'Terbit',
                'value' => $summary['selesai'] ?? 0,
                'description' => 'SPM terbit final',
                'badge' => 'Done',
                'badgeColor' => 'success',
            ])
        </div>
    </div>

    {{-- Filter & Search --}}
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <form action="{{ route('spms.perjaldin.index') }}" method="GET" class="d-flex align-items-center gap-3 flex-wrap">
                <label class="fw-bold mb-0">Filter:</label>
                <select name="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <option value="semua" {{ $statusFilter === 'semua' ? 'selected' : '' }}>Semua</option>
                    <option value="belum_dibuat" {{ $statusFilter === 'belum_dibuat' ? 'selected' : '' }}>Belum Dibuat SPM</option>
                    <option value="draft" {{ $statusFilter === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="revisi" {{ $statusFilter === 'revisi' ? 'selected' : '' }}>Revisi</option>
                    <option value="menunggu" {{ $statusFilter === 'menunggu' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                    <option value="selesai" {{ $statusFilter === 'selesai' ? 'selected' : '' }}>SPM Terbit</option>
                </select>
                <div class="d-flex gap-2 ms-auto">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nomor SPP / SPM / tagihan..." value="{{ $search ?? '' }}" style="min-width: 250px;">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="mb-3 fw-bold">Daftar SPP Perjaldin & Status SPM</h6>
            <div class="table-responsive">
                <table id="tblSpmPerjaldin" class="table table-striped table-bordered align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No. SPP / Tanggal</th>
                            <th>Komponen Biaya</th>
                            <th>Tagihan Perjaldin</th>
                            <th>COA</th>
                            <th class="text-end">Nilai SPP (Rp)</th>
                            <th class="text-center">Status SPM</th>
                            <th class="text-center">Verifikasi Koor Keu</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sppList as $idx => $spp)
                        @php
                            $hasSpm = (bool) $spp->spm;
                            $spmStatus = $spp->spm?->status ?? 'Belum Dibuat';
                            $wfInstance = collect($spp->spm?->workflowInstances ?? [])->sortByDesc('created_at')->first();
                            $koordinatorApproval = collect($wfInstance?->approvals ?? [])->first(
                                fn ($approval) => in_array($approval->role_code, ['Koordinator Keuangan', 'KOORDINATOR_KEUANGAN'], true)
                            );
                        @endphp
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $spp->nomor_spp }}</strong><br>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-calendar-check"></i> {{ \Carbon\Carbon::parse($spp->tanggal_spp)->isoFormat('D MMM Y') }}
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold">{{ $spp->tagihanPerjaldinKomponen?->nama_komponen ?? '-' }}</span>
                            </td>
                            <td>
                                <span class="text-muted d-block small">{{ $spp->tagihan?->nomor_tagihan ?? '-' }}</span>
                                <span class="fw-bold text-truncate d-block" style="max-width: 200px;" title="{{ $spp->tagihan?->deskripsi }}">{{ $spp->tagihan?->deskripsi ?? '-' }}</span>
                            </td>
                            <td>
                                @if($spp->tagihanPerjaldinKomponen?->dipaRevisionItem?->coa)
                                    <span class="badge bg-primary-subtle text-primary small">{{ $spp->tagihanPerjaldinKomponen->dipaRevisionItem->coa->kode_akun }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end text-success fw-bold">
                                Rp {{ number_format($spp->nominal_spp, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $statusSpmClass($spp) }}">{{ $statusSpmLabel($spp) }}</span>
                                @if($hasSpm && $spp->spm->nomor_spm)
                                    <br><small class="text-muted">{{ $spp->spm->nomor_spm }}</small>
                                @endif
                                @if($hasSpm && $spmStatus === 'Revisi' && $spp->spm->catatan_revisi)
                                    <div class="text-danger small mt-1 fw-bold text-truncate" style="max-width: 150px;" title="{{ $spp->spm->catatan_revisi }}">"{{ $spp->spm->catatan_revisi }}"</div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($hasSpm && $wfInstance)
                                    <span class="spm-verif-badge {{ $verificationStatusClass($koordinatorApproval?->status) }}">
                                        <i class="bi bi-person-check"></i> {{ $koordinatorApproval?->status ?? '-' }}
                                    </span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('spms.perjaldin.detail', $spp->id) }}" class="btn btn-sm {{ $hasSpm ? 'btn-primary' : 'btn-success' }}">
                                    <i class="bi {{ $hasSpm ? 'bi-pencil-square' : 'bi-plus-circle' }}"></i>
                                    {{ $hasSpm ? 'Kelola SPM' : 'Buat SPM' }}
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>$(document).ready(function() { $('#tblSpmPerjaldin').DataTable({ "order": [], "searching": false }); });</script>
@endpush
