@extends('layouts.app')
@section('title')
    Detail Perjalanan Dinas
@endsection

@push('style')
<style>
    .hero-status-badge { font-size: 0.85rem; }
    .status-helper { font-size: 0.8rem; line-height: 1.4; }
    .info-field label { font-size: 0.75rem; font-weight: 500; letter-spacing: 0.03em; }
    .accordion-button:not(.collapsed) { background-color: #f0f6ff; color: #0d6efd; box-shadow: none; }
    .accordion-button:focus { box-shadow: none; }
</style>
@endpush

@section('content')
<x-page-title title="Manajemen Perjaldin" subtitle="Detail Dokumen" />

@php
    $status = $tagihan->status;

    $statusConfig = [
        'DRAFT'                => ['badge' => 'secondary', 'icon' => 'bi-circle',           'text' => 'Data belum diajukan. Silakan lengkapi dan ajukan dokumen.'],
        'PENDING_VERIFIKASI_PERJALDIN' => ['badge' => 'primary', 'icon' => 'bi-hourglass-split', 'text' => 'Dokumen sedang diverifikasi oleh PPSPM, Bendahara Penerimaan, Bendahara Pengeluaran, dan PPK.'],
        'PENDING_PPK'          => ['badge' => 'primary',   'icon' => 'bi-hourglass-split',  'text' => 'Dokumen sedang menunggu verifikasi oleh PPK.'],
        'PENDING_PPSPM'        => ['badge' => 'primary',   'icon' => 'bi-hourglass-split',  'text' => 'Dokumen sedang menunggu verifikasi oleh PPSPM.'],
        'REVISI_PPK'           => ['badge' => 'warning',   'icon' => 'bi-arrow-counterclockwise', 'text' => 'PPK meminta perbaikan. Silakan edit data dan ajukan kembali.'],
        'REVISI_PPSPM'         => ['badge' => 'warning',   'icon' => 'bi-arrow-counterclockwise', 'text' => 'PPSPM meminta perbaikan. Silakan edit data dan ajukan kembali.'],
        'DITOLAK_PPK'          => ['badge' => 'danger',    'icon' => 'bi-x-octagon',        'text' => 'Dokumen ditolak oleh PPK.'],
        'DITOLAK_PPSPM'        => ['badge' => 'danger',    'icon' => 'bi-x-octagon',        'text' => 'Dokumen ditolak oleh PPSPM.'],
        'DISETUJUI_PPK'        => ['badge' => 'info',      'icon' => 'bi-check-circle',     'text' => 'Disetujui PPK. Menunggu verifikasi Bendahara Pengeluaran.'],
        'PENDING_BENDAHARA'    => ['badge' => 'primary',   'icon' => 'bi-hourglass-split',  'text' => 'Menunggu verifikasi oleh Bendahara Pengeluaran.'],
        'PENDING_BENDAHARA_PENERIMAAN' => ['badge' => 'primary', 'icon' => 'bi-hourglass-split', 'text' => 'Menunggu verifikasi oleh Bendahara Penerimaan.'],
        'PENDING_BENDAHARA_PENGELUARAN' => ['badge' => 'primary', 'icon' => 'bi-hourglass-split', 'text' => 'Menunggu verifikasi oleh Bendahara Pengeluaran.'],
        'REVISI_BENDAHARA'     => ['badge' => 'warning',   'icon' => 'bi-arrow-counterclockwise', 'text' => 'Bendahara meminta perbaikan. Silakan edit data dan ajukan kembali.'],
        'REVISI_BENDAHARA_PENERIMAAN' => ['badge' => 'warning', 'icon' => 'bi-arrow-counterclockwise', 'text' => 'Bendahara Penerimaan meminta perbaikan. Silakan edit data dan ajukan kembali.'],
        'REVISI_BENDAHARA_PENGELUARAN' => ['badge' => 'warning', 'icon' => 'bi-arrow-counterclockwise', 'text' => 'Bendahara Pengeluaran meminta perbaikan. Silakan edit data dan ajukan kembali.'],
        'DITOLAK_BENDAHARA'    => ['badge' => 'danger',    'icon' => 'bi-x-octagon',        'text' => 'Dokumen ditolak oleh Bendahara Pengeluaran.'],
        'DITOLAK_BENDAHARA_PENERIMAAN' => ['badge' => 'danger', 'icon' => 'bi-x-octagon', 'text' => 'Dokumen ditolak oleh Bendahara Penerimaan.'],
        'DITOLAK_BENDAHARA_PENGELUARAN' => ['badge' => 'danger', 'icon' => 'bi-x-octagon', 'text' => 'Dokumen ditolak oleh Bendahara Pengeluaran.'],
        'PENDING_KASUBBAG'     => ['badge' => 'info',      'icon' => 'bi-hourglass-split',  'text' => 'Seluruh verifikator sudah menyetujui. Menunggu persetujuan Kasubbag.'],
        'REVISI_KASUBBAG'      => ['badge' => 'warning',   'icon' => 'bi-arrow-counterclockwise', 'text' => 'Kasubbag meminta perbaikan. Silakan edit data dan ajukan kembali.'],
        'DITOLAK_KASUBBAG'     => ['badge' => 'danger',    'icon' => 'bi-x-octagon',        'text' => 'Dokumen ditolak oleh Kasubbag.'],
        'DISETUJUI_PERJALDIN'  => ['badge' => 'success',   'icon' => 'bi-check-circle-fill','text' => 'Verifikasi selesai. Dokumen telah diteruskan ke tahap berikutnya (Operator BLU).'],
    ];

    $cfg = $statusConfig[$status] ?? ['badge' => 'secondary', 'icon' => 'bi-question-circle', 'text' => 'Status tidak dikenali.'];

    $canEdit = in_array($status, ['DRAFT', 'REVISI_PPK', 'REVISI_PPSPM', 'REVISI_BENDAHARA', 'REVISI_BENDAHARA_PENERIMAAN', 'REVISI_BENDAHARA_PENGELUARAN', 'REVISI_KASUBBAG', 'DITOLAK_PPK', 'DITOLAK_PPSPM', 'DITOLAK_BENDAHARA_PENERIMAAN', 'DITOLAK_BENDAHARA_PENGELUARAN', 'DITOLAK_KASUBBAG']);
    $canSubmit = $canEdit;
    $isApprovedPerjaldin = in_array($status, ['DISETUJUI_PERJALDIN', 'PROSES_COA', 'PROSES_SPP', 'SEBAGIAN_SPP_TERBIT', 'SPP_LENGKAP']);
    $isOperatorPerjaldin = auth()->user()->hasRole('Operator Perjaldin');
    $isOperatorBlu = auth()->user()->hasRole('Operator BLU');
@endphp

{{-- Flash Messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
        <i class="bi bi-x-circle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ═══ SECTION 1: HERO HEADER ═══ --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-4 px-4">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="badge bg-{{ $cfg['badge'] }} hero-status-badge px-3 py-2 rounded-pill">
                        <i class="bi {{ $cfg['icon'] }} me-1"></i>{{ $status }}
                    </span>
                </div>
                <h5 class="fw-bold mb-1">{{ $tagihan->deskripsi ?? 'Tanpa Judul' }}</h5>
                <div class="text-muted small mb-2">
                    <i class="bi bi-hash me-1"></i><strong>{{ $tagihan->nomor_tagihan ?? '-' }}</strong>
                    <span class="mx-2">·</span>
                    <i class="bi bi-people me-1"></i>{{ $tagihan->detailPerjaldin->count() }} Peserta
                    @if($tagihan->periode_bulan && $tagihan->periode_tahun)
                        <span class="mx-2">·</span>
                        <i class="bi bi-calendar3 me-1"></i>
                        {{ \Carbon\Carbon::createFromDate($tagihan->periode_tahun, $tagihan->periode_bulan, 1)->translatedFormat('F Y') }}
                    @endif
                </div>
                <div class="alert alert-{{ $cfg['badge'] === 'warning' || $cfg['badge'] === 'danger' ? $cfg['badge'] : 'light' }} border-0 py-2 px-3 mb-0 d-inline-block status-helper rounded-3">
                    <i class="bi {{ $cfg['icon'] }} me-1"></i>{{ $cfg['text'] }}
                </div>
            </div>
            <div class="col-md-5 text-md-end">
                <div class="mb-2">
                    <div class="text-muted small">Total Bruto</div>
                    <div class="fs-3 fw-bold text-success">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</div>
                </div>
                @if($tagihan->created_at)
                    <small class="text-muted">
                        <i class="bi bi-calendar-plus me-1"></i>Dibuat {{ $tagihan->created_at->format('d M Y') }}
                    </small>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ═══ SECTION 2: QUICK ACTIONS ═══ --}}
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="{{ route('perjaldins.index') }}" class="btn btn-light border">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>

    <a href="{{ route('perjaldins.pdf-nominatif', $tagihan->id) }}" target="_blank" class="btn btn-outline-danger bg-white">
        <i class="bi bi-file-earmark-pdf me-1"></i>Cetak PDF Nominatif
    </a>

    <a href="{{ route('perjaldins.pdf-lampiran', $tagihan->id) }}" target="_blank" class="btn btn-outline-danger bg-white">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Cetak PDF Daftar Nominatif Pembayaran
    </a>

    @if($isOperatorPerjaldin && $canEdit)
        <a href="{{ route('perjaldins.edit-perjaldin', $tagihan->id) }}" class="btn btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i>Edit Dokumen
        </a>
    @endif

    @if($isOperatorPerjaldin && $canSubmit)
        <form action="{{ route('perjaldin.workflow.submit', $tagihan->id) }}" method="POST"
              onsubmit="return confirm('Ajukan dokumen Perjaldin ke PPSPM, Bendahara Penerimaan, Bendahara Pengeluaran, dan PPK?')">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-send-check me-1"></i>Ajukan Perjaldin
            </button>
        </form>
    @endif

    @if($isApprovedPerjaldin)
        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 d-flex align-items-center" style="font-size:0.82rem;">
            <i class="bi bi-check-circle-fill me-1"></i>Diteruskan ke Proses Berikutnya
        </span>
    @endif

    @if($status === 'MENUNGGU_UPLOAD_NOMINATIF_TTD')
        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 d-flex align-items-center" style="font-size:0.82rem;">
            <i class="bi bi-cloud-upload-fill me-1"></i>Menunggu Upload Nominatif Bertandatangan
        </span>
    @endif
</div>

{{-- ═══ SECTION 3: INFORMASI DOKUMEN ═══ --}}
@include('perjaldins.partials.detail-info', ['tagihan' => $tagihan])

{{-- ═══ SECTION 3.5: INFORMASI VERIFIKATOR ═══ --}}
@include('perjaldins.partials.verifikator-info', ['tagihan' => $tagihan])

{{-- ═══ SECTION 4: WORKFLOW PROGRESS ═══ --}}
@include('perjaldins.partials.workflow-progress', ['tagihan' => $tagihan])

{{-- ═══ SECTION 5: DAFTAR PESERTA ═══ --}}
@include('perjaldins.partials.peserta-list', ['tagihan' => $tagihan])

{{-- ═══ SECTION 5.5: UPLOAD NOMINATIF BERTANDATANGAN ═══ --}}
@php
    $waitingNominatifTtd = $status === 'MENUNGGU_UPLOAD_NOMINATIF_TTD';
    $arsipNominatif = $tagihan->arsipDokumen()
        ->whereIn('jenis_dokumen', ['NOMINATIF_PERJALDIN_TTD', 'DAFTAR_NOMINATIF_PEMBAYARAN_PERJALDIN_TTD'])
        ->where('is_active', true)
        ->get()
        ->keyBy('jenis_dokumen');
    $hasNominatif = isset($arsipNominatif['NOMINATIF_PERJALDIN_TTD']);
    $hasDaftarNominatif = isset($arsipNominatif['DAFTAR_NOMINATIF_PEMBAYARAN_PERJALDIN_TTD']);
@endphp

@if($waitingNominatifTtd || $hasNominatif || $hasDaftarNominatif)
<div class="card border-0 shadow-sm mb-4 rounded-4
    {{ $waitingNominatifTtd ? 'border-start border-4 border-warning' : '' }}">
    <div class="card-header bg-white border-bottom py-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-cloud-upload-fill fs-4 text-warning"></i>
            <div>
                <h6 class="fw-bold mb-0">Upload Nominatif Bertandatangan</h6>
                <div class="small text-muted">Wajib diunggah oleh Operator Perjaldin setelah Kasubbag menyetujui tagihan.</div>
            </div>
        </div>
    </div>
    <div class="card-body">
        @if($waitingNominatifTtd)
            <div class="alert alert-warning border-0 small">
                Tagihan telah disetujui Kasubbag. Silakan unggah <strong>Nominatif Perjalanan Dinas</strong> dan
                <strong>Daftar Nominatif Pembayaran Perjalanan Dinas</strong> yang sudah ditandatangani.
                Setelah keduanya lengkap, Operator BLU akan diberi notifikasi untuk membuat SPP.
            </div>
        @elseif($status === 'DISETUJUI_PERJALDIN' || $isApprovedPerjaldin)
            <div class="alert alert-success border-0 small">
                Kedua dokumen bertandatangan sudah lengkap. Tagihan sudah diteruskan ke Operator BLU untuk dibuatkan SPP.
            </div>
        @endif

        <div class="row g-3">
            @php
                $slots = [
                    [
                        'jenis' => 'NOMINATIF_PERJALDIN_TTD',
                        'label' => 'Nominatif Perjalanan Dinas (TTD)',
                        'desc'  => 'A4 portrait — sumber dari tombol "Cetak PDF Nominatif".',
                    ],
                    [
                        'jenis' => 'DAFTAR_NOMINATIF_PEMBAYARAN_PERJALDIN_TTD',
                        'label' => 'Daftar Nominatif Pembayaran Perjaldin (TTD)',
                        'desc'  => 'A4 landscape — sumber dari tombol "Cetak PDF Daftar Nominatif Pembayaran".',
                    ],
                ];
                $canUploadNominatif = $isOperatorPerjaldin && in_array($status, ['MENUNGGU_UPLOAD_NOMINATIF_TTD', 'DISETUJUI_PERJALDIN'], true);
            @endphp
            @foreach($slots as $slot)
                @php $arsip = $arsipNominatif[$slot['jenis']] ?? null; @endphp
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100 {{ $arsip ? 'border-success bg-success bg-opacity-10' : '' }}">
                        <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                            <div>
                                <div class="fw-semibold">{{ $slot['label'] }}</div>
                                <div class="small text-muted">{{ $slot['desc'] }}</div>
                            </div>
                            @if($arsip)
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Tersedia</span>
                            @else
                                <span class="badge bg-secondary"><i class="bi bi-clock-history me-1"></i>Belum diunggah</span>
                            @endif
                        </div>

                        @if($arsip)
                            <div class="d-flex gap-2 align-items-center small mb-2">
                                <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                                <a href="{{ route('perjaldins.view-nominatif-ttd', [$tagihan->id, $arsip->id]) }}" target="_blank"
                                   class="text-decoration-none">{{ $arsip->nama_file_asli }}</a>
                            </div>
                        @endif

                        @if($canUploadNominatif)
                            <form action="{{ route('perjaldins.upload-nominatif-ttd', $tagihan->id) }}" method="POST" enctype="multipart/form-data" class="mt-2">
                                @csrf
                                <input type="hidden" name="jenis_dokumen" value="{{ $slot['jenis'] }}">
                                <div class="input-group input-group-sm">
                                    <input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <button type="submit" class="btn btn-warning fw-semibold">
                                        <i class="bi bi-upload me-1"></i>{{ $arsip ? 'Ganti' : 'Unggah' }}
                                    </button>
                                </div>
                                <div class="form-text small">PDF / JPG / PNG, maksimal 10MB.</div>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ═══ SECTION 6: AREA PROSES LANJUTAN OPERATOR BLU (Tersembunyi / Collapsed) ═══ --}}
@if($tagihan->komponenPerjaldin?->where('total_nominal', '>', 0)->isNotEmpty())
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-2">
            <button class="btn btn-sm btn-link text-decoration-none text-muted w-100 text-start d-flex align-items-center gap-2"
                    type="button" data-bs-toggle="collapse" data-bs-target="#sectionBLU" aria-expanded="false">
                <i class="bi bi-grid-3x3-gap text-secondary"></i>
                <span class="small fw-semibold">Informasi Komponen Biaya (Proses Operator BLU)</span>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-auto">
                    {{ $tagihan->komponenPerjaldin->where('total_nominal', '>', 0)->count() }} Komponen
                </span>
                <i class="bi bi-chevron-down ms-1"></i>
            </button>
        </div>
        <div class="collapse" id="sectionBLU">
            <div class="card-body py-3">
                @if(!$isApprovedPerjaldin)
                    <div class="alert alert-secondary py-2 small mb-0">
                        <i class="bi bi-lock me-1"></i>
                        Rekapitulasi komponen biaya akan tersedia setelah dokumen Perjaldin disetujui dan diteruskan ke <strong>Operator BLU</strong>.
                    </div>
                @else
                    @foreach($tagihan->komponenPerjaldin->where('total_nominal', '>', 0)->sortBy('kode_komponen') as $komponen)
                        @include('perjaldins.partials.komponen-card', [
                            'komponen' => $komponen,
                            'budgetGroups' => $budgetGroups,
                            'tagihan' => $tagihan,
                            'isApprovedPerjaldin' => $isApprovedPerjaldin,
                        ])
                    @endforeach
                @endif
            </div>
        </div>
    </div>
@endif

{{-- ═══ SECTION 7: AUDIT TRAIL ═══ --}}
@include('perjaldins.partials.audit-timeline', ['tagihan' => $tagihan])

@endsection
