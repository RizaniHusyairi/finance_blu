@extends('layouts.app')
@section('title') Detail Verifikasi Perjaldin — {{ $tagihan->nomor_tagihan }} @endsection

@push('css')
<style>
    .info-label { font-size: 0.72rem; color: #6c757d; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 2px; }
    .info-value { font-weight: 600; font-size: 0.9rem; color: #212529; }
    .sticky-panel { position: sticky; top: 80px; }
    @media(max-width:991px){ .sticky-panel { position: static; } }
</style>
@endpush

@section('content')
<x-page-title title="Detail Verifikasi Perjaldin" subtitle="{{ $tagihan->nomor_tagihan }} — {{ $tagihan->deskripsi }}" />

{{-- Flash --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
        <i class="bi bi-x-circle-fill me-2"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ② Tombol Kembali --}}
<div class="mb-4">
    <a href="{{ route($indexRoute) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
    </a>
</div>

{{-- ═══ SECTION 1 — HERO HEADER ═══ --}}
@php
    $statusMap = [
        'DRAFT'               => ['class'=>'bg-secondary',          'text'=>'Draft',                             'helper'=>'Belum diajukan.'],
        'PENDING_VERIFIKASI_PERJALDIN' => ['class'=>'bg-primary',    'text'=>'Menunggu Verifikator',              'helper'=>'Menunggu PPSPM, Bendahara Penerimaan, Bendahara Pengeluaran, dan PPK.'],
        'PENDING_PPK'         => ['class'=>'bg-primary',            'text'=>'Menunggu Verifikasi PPK',           'helper'=>'Dokumen sedang menunggu persetujuan PPK.'],
        'PENDING_PPSPM'       => ['class'=>'bg-primary',            'text'=>'Menunggu Verifikasi PPSPM',         'helper'=>'Dokumen sedang menunggu persetujuan PPSPM.'],
        'REVISI_PPK'          => ['class'=>'bg-warning text-dark',  'text'=>'Revisi oleh PPK',                  'helper'=>'Dokumen dikembalikan. Operator perlu merevisi.'],
        'REVISI_PPSPM'        => ['class'=>'bg-warning text-dark',  'text'=>'Revisi oleh PPSPM',                'helper'=>'Dokumen dikembalikan oleh PPSPM. Operator perlu merevisi.'],
        'DITOLAK_PPK'         => ['class'=>'bg-danger',             'text'=>'Ditolak PPK',                      'helper'=>'Dokumen ditolak oleh PPK.'],
        'DITOLAK_PPSPM'       => ['class'=>'bg-danger',             'text'=>'Ditolak PPSPM',                    'helper'=>'Dokumen ditolak oleh PPSPM.'],
        'PENDING_BENDAHARA'   => ['class'=>'bg-info text-dark',     'text'=>'Menunggu Verifikasi Bendahara',     'helper'=>'Menunggu verifikasi Bendahara Pengeluaran.'],
        'PENDING_BENDAHARA_PENERIMAAN' => ['class'=>'bg-info text-dark', 'text'=>'Menunggu Bendahara Penerimaan', 'helper'=>'Menunggu verifikasi Bendahara Penerimaan.'],
        'PENDING_BENDAHARA_PENGELUARAN' => ['class'=>'bg-info text-dark', 'text'=>'Menunggu Bendahara Pengeluaran', 'helper'=>'Menunggu verifikasi Bendahara Pengeluaran.'],
        'REVISI_BENDAHARA'    => ['class'=>'bg-warning text-dark',  'text'=>'Revisi oleh Bendahara',            'helper'=>'Dokumen dikembalikan oleh Bendahara Pengeluaran.'],
        'REVISI_BENDAHARA_PENERIMAAN' => ['class'=>'bg-warning text-dark', 'text'=>'Revisi Bendahara Penerimaan', 'helper'=>'Dokumen dikembalikan oleh Bendahara Penerimaan.'],
        'REVISI_BENDAHARA_PENGELUARAN' => ['class'=>'bg-warning text-dark', 'text'=>'Revisi Bendahara Pengeluaran', 'helper'=>'Dokumen dikembalikan oleh Bendahara Pengeluaran.'],
        'DITOLAK_BENDAHARA'   => ['class'=>'bg-danger',             'text'=>'Ditolak Bendahara',                'helper'=>'Dokumen ditolak oleh Bendahara Pengeluaran.'],
        'DITOLAK_BENDAHARA_PENERIMAAN' => ['class'=>'bg-danger',     'text'=>'Ditolak Bendahara Penerimaan',     'helper'=>'Dokumen ditolak oleh Bendahara Penerimaan.'],
        'DITOLAK_BENDAHARA_PENGELUARAN' => ['class'=>'bg-danger',    'text'=>'Ditolak Bendahara Pengeluaran',    'helper'=>'Dokumen ditolak oleh Bendahara Pengeluaran.'],
        'PENDING_KASUBBAG'    => ['class'=>'bg-info text-dark',      'text'=>'Menunggu Kasubbag',                'helper'=>'Seluruh verifikator sudah menyetujui. Menunggu persetujuan Kasubbag.'],
        'REVISI_KASUBBAG'     => ['class'=>'bg-warning text-dark',   'text'=>'Revisi oleh Kasubbag',             'helper'=>'Dokumen dikembalikan oleh Kasubbag.'],
        'DITOLAK_KASUBBAG'    => ['class'=>'bg-danger',              'text'=>'Ditolak Kasubbag',                 'helper'=>'Dokumen ditolak oleh Kasubbag.'],
        'DISETUJUI_PERJALDIN' => ['class'=>'bg-success',            'text'=>'Disetujui — Verifikasi Selesai',   'helper'=>'Dokumen telah disetujui oleh seluruh verifikator dan Kasubbag.'],
    ];
    $sc = $statusMap[$tagihan->status] ?? ['class'=>'bg-secondary','text'=>$tagihan->status,'helper'=>''];
    $bulanMap=[1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $submitLog = $tagihan->logs->firstWhere('aksi','SUBMIT');
@endphp

<div class="card border-0 shadow mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap gap-3 align-items-start justify-content-between">
            <div>
                <div class="mb-2">
                    <span class="badge {{ $sc['class'] }} rounded-pill px-3 py-2 fs-6">{{ $sc['text'] }}</span>
                </div>
                <h4 class="fw-bold mb-1">{{ $tagihan->deskripsi }}</h4>
                <div class="d-flex flex-wrap gap-3 text-muted small mt-2">
                    <span><i class="bi bi-hash me-1"></i>{{ $tagihan->nomor_tagihan }}</span>
                    <span><i class="bi bi-people me-1"></i>{{ $tagihan->detailPerjaldin->count() }} Peserta</span>
                    <span><i class="bi bi-calendar3 me-1"></i>{{ ($bulanMap[$tagihan->periode_bulan] ?? '-') . ' ' . ($tagihan->periode_tahun ?? '') }}</span>
                    @if($submitLog)<span><i class="bi bi-send me-1"></i>Diajukan {{ $submitLog->created_at->format('d M Y, H:i') }}</span>@endif
                </div>
                <p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>{{ $sc['helper'] }}</p>
            </div>
            <div class="text-end">
                <div class="text-muted small mb-1">Total Bruto</div>
                <div class="text-success fw-bold" style="font-size:1.6rem;">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</div>
                <div class="text-muted small">Dibuat {{ \Carbon\Carbon::parse($tagihan->created_at)->format('d M Y') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ MAIN 2-COL LAYOUT ═══ --}}
<div class="row g-4">
    {{-- Kolom Kiri: Konten Review --}}
    <div class="col-lg-8">

        {{-- SECTION 2: Workflow Stepper --}}
        @include('verifikasi_perjaldin.partials.workflow-stepper', ['tagihan' => $tagihan, 'userRole' => $userRole])

        {{-- SECTION 3: Informasi Dokumen --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text text-primary me-2"></i>Informasi Dokumen</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="info-label">Nomor Tagihan</div>
                        <div class="info-value">{{ $tagihan->nomor_tagihan ?? '-' }}</div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-label">Uraian / Judul</div>
                        <div class="info-value">{{ $tagihan->deskripsi ?? '-' }}</div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-label">Periode</div>
                        <div class="info-value">{{ ($bulanMap[$tagihan->periode_bulan] ?? '-') . ' ' . ($tagihan->periode_tahun ?? '') }}</div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-label">Kota TTD</div>
                        <div class="info-value">{{ $tagihan->kota_ttd ?? '-' }}</div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-label">Tanggal TTD</div>
                        <div class="info-value">{{ isset($tagihan->tanggal_ttd) ? \Carbon\Carbon::parse($tagihan->tanggal_ttd)->format('d M Y') : '-' }}</div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-label">Jumlah Peserta</div>
                        <div class="info-value">{{ $tagihan->detailPerjaldin->count() }} Orang</div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-label">Nama PPK</div>
                        <div class="info-value">{{ $tagihan->ppk_nama_snapshot ?? '-' }}</div>
                        @if($tagihan->ppk_nip_snapshot)
                            <div class="text-muted" style="font-size:.72rem;">NIP: {{ $tagihan->ppk_nip_snapshot }}</div>
                        @endif
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-label">PPSPM</div>
                        <div class="info-value">{{ $tagihan->ppspm_nama_snapshot ?? '-' }}</div>
                        @if($tagihan->ppspm_nip_snapshot)
                            <div class="text-muted" style="font-size:.72rem;">NIP: {{ $tagihan->ppspm_nip_snapshot }}</div>
                        @endif
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-label">Bendahara Penerimaan</div>
                        <div class="info-value">{{ $tagihan->bendahara_penerimaan_nama_snapshot ?? '-' }}</div>
                        @if($tagihan->bendahara_penerimaan_nip_snapshot)
                            <div class="text-muted" style="font-size:.72rem;">NIP: {{ $tagihan->bendahara_penerimaan_nip_snapshot }}</div>
                        @endif
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-label">Bendahara Pengeluaran</div>
                        <div class="info-value">{{ $tagihan->bendahara_pengeluaran_nama_snapshot ?? '-' }}</div>
                        @if($tagihan->bendahara_pengeluaran_nip_snapshot)
                            <div class="text-muted" style="font-size:.72rem;">NIP: {{ $tagihan->bendahara_pengeluaran_nip_snapshot }}</div>
                        @endif
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-label">Kasubbag</div>
                        <div class="info-value">{{ $tagihan->kasubbag_nama_snapshot ?? '-' }}</div>
                        @if($tagihan->kasubbag_nip_snapshot)
                            <div class="text-muted" style="font-size:.72rem;">NIP: {{ $tagihan->kasubbag_nip_snapshot }}</div>
                        @endif
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-label">Total Bruto</div>
                        <div class="info-value text-success">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 4: Daftar Peserta --}}
        @include('verifikasi_perjaldin.partials.peserta-accordion', ['tagihan' => $tagihan])

        {{-- SECTION 5: Catatan Revisi (jika ada) --}}
        @php
            $revisiLogs = $tagihan->logs->filter(fn($l) => in_array($l->aksi, ['REVISION','REJECT']))->sortByDesc('created_at');
        @endphp
        @if($revisiLogs->isNotEmpty())
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-warning bg-opacity-10 border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-warning"><i class="bi bi-exclamation-triangle-fill me-2"></i>Riwayat Revisi / Penolakan</h6>
                </div>
                <div class="card-body py-3 px-4">
                    @foreach($revisiLogs as $rl)
                        <div class="d-flex gap-3 align-items-start {{ !$loop->last ? 'mb-4 pb-4 border-bottom' : '' }}">
                            <div class="rounded-circle bg-warning bg-opacity-20 d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;">
                                <i class="bi bi-exclamation text-warning fs-5"></i>
                            </div>
                            <div class="flex-fill">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-1 mb-1">
                                    <div>
                                        <span class="badge bg-warning text-dark small me-2">{{ $rl->aksi }}</span>
                                        <span class="fw-semibold small">{{ $rl->user?->name ?? 'Sistem' }}</span>
                                        @if($rl->role_saat_itu)
                                            <span class="badge bg-light text-secondary border ms-1 small">{{ $rl->role_saat_itu }}</span>
                                        @endif
                                    </div>
                                    <small class="text-muted">{{ $rl->created_at->format('d M Y, H:i') }}</small>
                                </div>
                                <div class="mt-2 p-3 bg-warning bg-opacity-10 rounded-3 small">
                                    {{ $rl->catatan ?? '-' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- SECTION 6: Audit Trail --}}
        @include('verifikasi_perjaldin.partials.audit-timeline', ['tagihan' => $tagihan])
    </div>

    {{-- Kolom Kanan: Panel Aksi (Sticky) --}}
    <div class="col-lg-4">
        <div class="sticky-panel">
            {{-- Panel Verifikasi --}}
            @include('verifikasi_perjaldin.partials.verification-action-panel', [
                'tagihan'      => $tagihan,
                'userRole'     => $userRole,
                'currentApproval' => $currentApproval ?? null,
                'approveRoute' => $approveRoute,
                'revisiRoute'  => $revisiRoute,
                'allRoleApprovals' => $allRoleApprovals ?? [],
            ])

            {{-- Info Dokumen Ringkas --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-secondary"><i class="bi bi-card-list me-2"></i>Ringkasan Dokumen</h6>
                </div>
                <div class="card-body py-3 px-3">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="info-label">No. Tagihan</div>
                            <div class="info-value small">{{ $tagihan->nomor_tagihan }}</div>
                        </div>
                        <div class="col-6">
                            <div class="info-label">Periode</div>
                            <div class="info-value small">{{ ($bulanMap[$tagihan->periode_bulan] ?? '-') . ' ' . ($tagihan->periode_tahun ?? '') }}</div>
                        </div>
                        <div class="col-6">
                            <div class="info-label">Peserta</div>
                            <div class="info-value small">{{ $tagihan->detailPerjaldin->count() }} orang</div>
                        </div>
                        <div class="col-6">
                            <div class="info-label">Total Bruto</div>
                            <div class="info-value small text-success">Rp {{ number_format($tagihan->total_bruto,0,',','.') }}</div>
                        </div>
                        <div class="col-12">
                            <div class="info-label">Status</div>
                            @include('verifikasi_perjaldin.partials.status-badge', ['status' => $tagihan->status])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
