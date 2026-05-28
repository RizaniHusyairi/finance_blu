@extends('layouts.app')
@section('title', 'Detail Tagihan Jasa (PNBP)')

@section('content')
@php
    $mitraTagihan = $tagihan->mitra ?? $tagihan->mitraLegacy;
    $wfInstance = $tagihan->workflowInstance;
    $currentApproval = $wfInstance ? $wfInstance->approvals->where('urutan_step', $wfInstance->step_saat_ini)->first() : null;
    $canApprove = false;
    $workflowApproved = $wfInstance && $wfInstance->status === 'APPROVED';
    $canManageTagihanJasa = Auth::user()->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Admin Jasa']);
    $suratFinalReady = ! empty($tagihan->file_surat_pengantar_final)
        && $tagihan->status_dokumen_pengantar === 'SUDAH_DITANDATANGANI';
    $canReviseTagihanJasa = Auth::user()->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Admin Jasa', 'Admin Konsesi'])
        && in_array($tagihan->status, ['REVISI', 'DITOLAK'], true);
    $canEditSuratPengantar = $canManageTagihanJasa
        && ! in_array($tagihan->status, ['PUBLISHED', 'LUNAS']);
    $canUploadSuratPengantarFinal = $canManageTagihanJasa
        && $workflowApproved
        && ! in_array($tagihan->status, ['PUBLISHED', 'LUNAS']);

    $currentApprovalRole = $currentApproval?->role_code;
    $userCanActAsCurrentRole = $currentApprovalRole && (
        Auth::user()->hasRole($currentApprovalRole)
        || ($currentApprovalRole === 'KPA' && Auth::user()->hasRole('PLT/PLH'))
        || ($currentApprovalRole === 'PLT/PLH' && Auth::user()->hasRole('KPA'))
    );

    if ($wfInstance && $wfInstance->status === 'IN_PROGRESS' && $currentApproval && $userCanActAsCurrentRole) {
        $canApprove = true;
    }

    $formatWorkflowLabel = function (?string $label): string {
        return strtr($label ?: '-', [
            'Kasi Jasa PNBP' => 'Kepala Seksi Pelayanan dan Kerjasama',
            'KASUBAG' => 'KASUBBAG',
            'Kasubag' => 'KASUBBAG',
            'kasubag' => 'KASUBBAG',
        ]);
    };
@endphp
<style>
    /* ===================== Tagihan Jasa Detail â€“ Themed UI ===================== */
    @keyframes tjReveal {
        from { opacity: 0; transform: translateY(18px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes tjShine {
        0%        { transform: translateX(-130%) skewX(-22deg); opacity: 0; }
        24%       { opacity: .55; }
        58%, 100% { transform: translateX(220%) skewX(-22deg); opacity: 0; }
    }
    @keyframes tjHeroSweep {
        0%        { transform: translateX(-120%) skewX(-18deg); opacity: 0; }
        20%       { opacity: .32; }
        55%, 100% { transform: translateX(220%) skewX(-18deg); opacity: 0; }
    }
    @keyframes tjContourDrift {
        0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg); opacity: .68; }
        50%      { transform: translate3d(-14px, 10px, 0) rotate(2deg); opacity: .95; }
    }
    @keyframes tjBadgePulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, .35); }
        50%      { box-shadow: 0 0 0 8px transparent; }
    }
    @keyframes tjVaScan {
        0%   { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }

    /* Hero header */
    .tj-hero {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        background:
            radial-gradient(circle at 18% 30%, rgba(96, 165, 250, .28), transparent 28%),
            linear-gradient(110deg, #071421 0%, #0d2744 42%, #174f86 100%);
        border: 1px solid rgba(147, 197, 253, .28);
        border-radius: 0 24px 24px 0;
        color: #fff;
        padding: 22px 26px;
        box-shadow: 0 18px 50px rgba(18, 53, 92, .22);
        margin-bottom: 1.5rem;
        animation: tjReveal .55s cubic-bezier(.2,.8,.2,1) both;
    }
    .tj-hero::before,
    .tj-hero::after {
        content: "";
        position: absolute;
        pointer-events: none;
        z-index: -1;
    }
    .tj-hero::before {
        width: 430px;
        height: 220px;
        right: -90px;
        top: -80px;
        border-radius: 0 0 0 999px;
        border-left: 2px solid rgba(251, 191, 36, .44);
        border-bottom: 2px solid rgba(147, 197, 253, .24);
        background: radial-gradient(circle at 50% 30%, rgba(96, 165, 250, .18), transparent 62%);
        animation: tjContourDrift 5.6s ease-in-out infinite;
    }
    .tj-hero::after {
        inset: 0;
        width: 46%;
        background: linear-gradient(90deg, transparent, rgba(125, 211, 252, .12), rgba(255, 255, 255, .20), rgba(96, 165, 250, .10), transparent);
        animation: tjHeroSweep 4.2s ease-in-out infinite;
    }
    .tj-hero h4 { color: #fff; font-weight: 800; }
    .tj-hero p, .tj-hero small { color: rgba(255, 255, 255, .82); }
    .tj-hero .btn { border-radius: 999px; font-weight: 700; padding: .45rem 1rem; }
    .tj-hero .btn-light-translucent {
        background: rgba(255, 255, 255, .10);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, .25);
        backdrop-filter: blur(8px);
        transition: background .2s ease, transform .2s ease;
    }
    .tj-hero .btn-light-translucent:hover {
        background: rgba(255, 255, 255, .22);
        transform: translateY(-1px);
    }
    .tj-hero .tj-tipe-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
        background: rgba(251, 191, 36, .18);
        color: #fde68a;
        border: 1px solid rgba(251, 191, 36, .35);
        border-radius: 999px;
    }

    /* Themed cards (apply via .tj-card on existing cards) */
    .tj-card {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        border-radius: 18px !important;
        background: #ffffff !important;
        border: 1px solid rgba(15, 23, 42, .06) !important;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .07) !important;
        animation: tjReveal .55s cubic-bezier(.2,.8,.2,1) both;
    }
    .tj-card::after {
        content: "";
        position: absolute;
        inset: 0;
        width: 38%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .55), rgba(251, 191, 36, .12), rgba(255, 255, 255, .55), transparent);
        pointer-events: none;
        animation: tjShine 6.4s ease-in-out infinite;
        z-index: 0;
    }
    .tj-card > * { position: relative; z-index: 1; }
    .tj-card .card-header {
        background: linear-gradient(90deg, #eff6ff 0%, #f8fbff 58%, #ffffff 100%) !important;
        border-bottom: 1px solid #bfdbfe !important;
    }
    .tj-card .card-header h5,
    .tj-card .card-header h6 { color: #0f2f57; font-weight: 800; }

    /* Stagger reveal across columns */
    .row > .col-lg-8 .tj-card { animation-delay: .04s; }
    .row > .col-lg-4 .tj-card { animation-delay: .12s; }

    /* Status badge with pulse */
    .tj-status-badge {
        animation: tjBadgePulse 2.4s ease-in-out infinite;
    }

    /* VA box sheen */
    .tj-va-box {
        position: relative;
        overflow: hidden;
        border-radius: 14px;
        background: linear-gradient(120deg, #eff6ff 0%, #ffffff 50%, #ecfdf5 100%);
        border: 1px solid #bfdbfe;
        padding: 16px 18px;
    }
    .tj-va-box::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(110deg, transparent 30%, rgba(96, 165, 250, .25) 50%, transparent 70%);
        background-size: 200% 100%;
        animation: tjVaScan 6s linear infinite;
        pointer-events: none;
    }
    .tj-va-number {
        font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, monospace;
        font-size: 1.6rem;
        font-weight: 800;
        letter-spacing: .04em;
        color: #0f2f57;
    }
    .tj-va-copy {
        border-radius: 999px;
        font-weight: 700;
    }

    /* Riwayat / Timeline themed */
    .tj-timeline { position: relative; padding: 8px 4px 4px 8px; }
    .tj-timeline::before {
        content: "";
        position: absolute;
        left: 21px;
        top: 8px;
        bottom: 8px;
        width: 2px;
        border-radius: 999px;
        background: linear-gradient(180deg, #cbd5e1, #93c5fd, #2563eb, #93c5fd);
        background-size: 100% 140px;
        animation: tjFlow 2.8s linear infinite;
    }
    @keyframes tjFlow {
        0% { background-position: 0 0; }
        100% { background-position: 0 140px; }
    }
    .tj-timeline-item {
        position: relative;
        display: grid;
        grid-template-columns: 36px 1fr;
        gap: 12px;
        margin-bottom: 14px;
        animation: tjReveal .4s ease both;
    }
    .tj-timeline-item:nth-child(2) { animation-delay: .05s; }
    .tj-timeline-item:nth-child(3) { animation-delay: .1s; }
    .tj-timeline-item:nth-child(4) { animation-delay: .15s; }
    .tj-timeline-item:nth-child(5) { animation-delay: .2s; }
    .tj-timeline-item:last-child { margin-bottom: 0; }
    .tj-timeline-dot {
        position: relative;
        z-index: 1;
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        border: 4px solid #fff;
        color: #fff;
        font-size: 13px;
        box-shadow: 0 12px 22px rgba(15, 23, 42, .14);
    }
    .tj-timeline-dot.primary  { background: #2563eb; }
    .tj-timeline-dot.success  { background: #10b981; }
    .tj-timeline-dot.warning  { background: #f59e0b; }
    .tj-timeline-dot.danger   { background: #ef4444; }
    .tj-timeline-dot.info     { background: #0ea5e9; }
    .tj-timeline-dot.secondary{ background: #64748b; }
    .tj-timeline-dot::after {
        content: "";
        position: absolute;
        inset: -6px;
        z-index: -1;
        border-radius: inherit;
        border: 1px solid currentColor;
        animation: tjRing 2.4s ease-out infinite;
    }
    @keyframes tjRing {
        0% { opacity: .5; transform: scale(.78); }
        70%, 100% { opacity: 0; transform: scale(1.55); }
    }
    .tj-timeline-content {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 11px 12px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .05);
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }
    .tj-timeline-content:hover {
        transform: translateY(-2px);
        border-color: #bfdbfe;
        box-shadow: 0 16px 32px rgba(37, 99, 235, .1);
    }
    .tj-timeline-title { color: #0f172a; font-weight: 800; font-size: 13px; }
    .tj-timeline-time {
        display: inline-flex; align-items: center; gap: 6px;
        margin-top: 4px;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }
    .tj-timeline-note {
        margin-top: 6px;
        background: #f8fafc;
        border-left: 3px solid #cbd5e1;
        border-radius: 8px;
        padding: 6px 10px;
        color: #475569;
        font-size: 12px;
        font-style: italic;
    }

    /* Modern table */
    .tj-card .table {
        border-collapse: separate;
        border-spacing: 0;
    }
    .tj-card .table thead.table-light th {
        background: rgba(248, 250, 252, .9);
        color: #475569;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: .03em;
        border-bottom: 1px solid #e2e8f0;
    }
    .tj-card .table tbody tr {
        transition: background-color .2s ease;
    }
    .tj-card .table tbody tr:hover {
        background: rgba(239, 246, 255, .55);
    }
    .tj-card .table tfoot td {
        background: linear-gradient(180deg, #ffffff, #eff6ff);
    }

    /* Service tree */
    .tagihan-service-toggle {
        color: #2563eb;
        text-decoration: none;
        white-space: normal;
        text-align: left;
        font-weight: 700;
    }
    .tagihan-service-toggle:hover { color: #1d4ed8; text-decoration: underline; }
    .tagihan-service-tree {
        border-left: 1px dashed #bfdbfe;
        margin-left: 10px;
        padding: 6px 0 2px 12px;
    }
    .tagihan-service-node {
        position: relative;
        padding: 3px 0 3px 14px;
    }
    .tagihan-service-node::before {
        content: "";
        position: absolute;
        left: -12px;
        top: 14px;
        width: 18px;
        border-top: 1px dashed #bfdbfe;
    }
    .tagihan-service-node .node-title { color: #1d4ed8; }

    @media (prefers-reduced-motion: reduce) {
        .tj-hero,
        .tj-hero::before,
        .tj-hero::after,
        .tj-card,
        .tj-card::after,
        .tj-status-badge,
        .tj-va-box::before,
        .tj-timeline::before,
        .tj-timeline-item,
        .tj-timeline-dot::after { animation: none !important; }
    }
</style>
<div class="tj-hero d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <span class="tj-tipe-pill mb-2"><i class="bi bi-receipt-cutoff"></i>Tagihan Jasa</span>
        <h4 class="mb-1">Detail Tagihan Jasa</h4>
        <p class="mb-0 small">No. Tagihan: <strong>{{ $tagihan->nomor_tagihan }}</strong></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('tagihan-jasa.pdf', ['id' => $tagihan->id, 'download' => 1]) }}" class="btn btn-light fw-bold text-danger shadow-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
        </a>
        <a href="{{ route('tagihan-jasa.surat-pengantar', ['id' => $tagihan->id, 'download' => 1]) }}" class="btn btn-light-translucent">
            <i class="bi bi-envelope-paper me-1"></i> Download Surat
        </a>
        <a href="{{ route('tagihan-jasa.pdf', $tagihan->id) }}" target="_blank" class="btn btn-light-translucent">
            <i class="bi bi-eye me-1"></i> Preview PDF
        </a>
        <a href="{{ route('admin-jasa.tagihan.log-bulanan') }}" class="btn btn-light-translucent">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 bg-success text-white alert-dismissible fade show shadow-sm mb-4">
        <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('wa_message_preview'))
    <div class="alert alert-info border-0 shadow-sm mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-whatsapp me-2 text-success"></i>Preview Pesan WhatsApp / Notifikasi</h5>
        <div class="bg-white p-3 rounded border" style="white-space: pre-wrap; font-family: monospace; font-size: 0.9rem;">{{ session('wa_message_preview') }}</div>
        
        @if(session('is_new_mitra'))
            <hr class="my-3">
            <h6 class="fw-bold text-danger mb-2"><i class="bi bi-exclamation-triangle me-2"></i>PENTING: Akun Mitra Baru Terbuat!</h6>
            <p class="small mb-2">Sistem baru saja membuatkan akun portal untuk Mitra ini. Karena alasan keamanan, password ini hanya ditampilkan satu kali ini saja. Harap salin dan berikan kredensial ini kepada Mitra:</p>
            <div class="bg-white p-3 rounded border border-danger">
                <div><strong>Email Login:</strong> {{ session('mitra_email') }}</div>
                <div><strong>Password:</strong> <span class="fw-bold text-danger">{{ session('mitra_password') }}</span></div>
                <div class="mt-2 text-muted small">Mitra dapat mengubah password ini setelah login di menu Profile.</div>
            </div>
        @else
            <hr class="my-3">
            <h6 class="fw-bold text-success mb-2"><i class="bi bi-check-circle me-2"></i>Akun Mitra Terdaftar</h6>
            <p class="small mb-2">Mitra ini sudah memiliki akun terdaftar sebelumnya di dalam sistem.</p>
            <div class="bg-white p-2 px-3 rounded border">
                <div><strong>Email Login:</strong> {{ session('mitra_email') }}</div>
                <div class="mt-1 text-muted small">Mitra dapat login dengan password yang sudah mereka miliki sebelumnya.</div>
            </div>
        @endif
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger border-0 bg-danger text-white alert-dismissible fade show shadow-sm mb-4">
        <i class="bi bi-x-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <!-- Kolom Kiri: Detail -->
    <div class="col-lg-8">
        <div class="card tj-card border-0 mb-4">
            <div class="card-header bg-white p-4 border-bottom rounded-top-4 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text text-primary me-2"></i>Informasi Tagihan</h5>
                <span class="badge tj-status-badge {{ match($tagihan->status) {
                    'PUBLISHED', 'LUNAS', 'DISETUJUI' => 'bg-success',
                    'DRAFT' => 'bg-secondary',
                    'REVISI' => 'bg-info text-dark',
                    'DITOLAK' => 'bg-danger',
                    default => 'bg-warning text-dark',
                } }} px-3 py-2 fs-6">{{ str_replace('_', ' ', $tagihan->status) }}</span>
            </div>
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-sm-6 mb-3 mb-sm-0">
                        <p class="mb-1 text-muted small fw-bold">Diterbitkan Kepada (Mitra)</p>
                        <h6 class="fw-bold">{{ $mitraTagihan->nama_pihak ?? '-' }}</h6>
                        <div class="small">
                            {{ $mitraTagihan->alamat ?? '-' }}<br>
                            NPWP: {{ $mitraTagihan->npwp ?? '-' }}
                        </div>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <p class="mb-1 text-muted small fw-bold">Nomor Tagihan</p>
                        <h6 class="fw-bold text-primary">{{ $tagihan->nomor_tagihan }}</h6>
                        <p class="mb-1 mt-3 text-muted small fw-bold">Tanggal Tagihan</p>
                        <h6 class="fw-bold">{{ \Carbon\Carbon::parse($tagihan->tanggal_tagihan)->format('d F Y') }}</h6>
                    </div>
                </div>

                @if($tagihan->nomor_kontrak)
                <div class="bg-light p-3 rounded-3 mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <p class="mb-1 text-muted small fw-bold">Nomor Dokumen Dasar Tagihan</p>
                            <h6 class="mb-0 fw-bold">{{ $tagihan->nomor_kontrak }}</h6>
                            <small class="text-muted">
                                {{ $tagihan->tanggal_mulai_kontrak ? \Carbon\Carbon::parse($tagihan->tanggal_mulai_kontrak)->format('d M Y') : '-' }} s/d 
                                {{ $tagihan->tanggal_selesai_kontrak ? \Carbon\Carbon::parse($tagihan->tanggal_selesai_kontrak)->format('d M Y') : '-' }}
                            </small>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            @if($tagihan->file_kontrak)
                                <a href="{{ Storage::url($tagihan->file_kontrak) }}" target="_blank" class="btn btn-sm btn-outline-primary fw-bold">
                                    <i class="bi bi-file-pdf me-1"></i> Lihat Dokumen Dasar
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <h6 class="fw-bold mb-3">Rincian Layanan Jasa</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th width="40%">Deskripsi Layanan</th>
                                <th width="15%">Kode Akun</th>
                                <th width="10%" class="text-center">Qty</th>
                                <th width="15%" class="text-end">Harga Satuan</th>
                                <th width="15%" class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tagihan->details as $detail)
                                @php
                                    $layanan = $detail->layananJasa;
                                    $servicePath = collect();
                                    $currentService = $layanan;
                                    $depthGuard = 0;

                                    while ($currentService && $depthGuard < 10) {
                                        $servicePath->prepend($currentService);
                                        $currentService = $currentService->parent;
                                        $depthGuard++;
                                    }

                                    $treeId = 'layananTreeDetail' . $detail->id;
                                    $expectedPercentageSubtotal = ((float) $detail->qty * (float) $detail->harga_satuan / 100) * (float) ($detail->kurs ?? 1);
                                    $isPercentageDetail = ($layanan?->tipe_layanan === 'KONSESI')
                                        || str_contains((string) ($layanan?->satuan), '%')
                                        || ((bool) ($layanan?->mendukung_konsesi) && abs($expectedPercentageSubtotal - (float) $detail->subtotal) < 0.01);
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        @if($layanan)
                                            <button type="button" class="btn btn-link p-0 fw-semibold tagihan-service-toggle" data-bs-toggle="collapse" data-bs-target="#{{ $treeId }}" aria-expanded="false" aria-controls="{{ $treeId }}">
                                                <i class="bi bi-folder2-open me-1"></i>{{ $layanan->nama_layanan }}
                                            </button>
                                            <div class="collapse mt-2" id="{{ $treeId }}">
                                                <div class="tagihan-service-tree small">
                                                    @foreach($servicePath as $node)
                                                        @php
                                                            $nodeLevel = (int) ($node->level ?? $loop->iteration);
                                                            $nodeLabel = match ($nodeLevel) {
                                                                1 => 'Jenis Layanan',
                                                                2 => 'Kategori',
                                                                default => $loop->last ? 'Item Tarif' : 'Subkategori',
                                                            };
                                                        @endphp
                                                        <div class="tagihan-service-node" style="margin-left: {{ max(0, $loop->index) * 12 }}px;">
                                                            <div class="d-flex flex-wrap align-items-center gap-1">
                                                                <i class="bi {{ $loop->last ? 'bi-receipt' : 'bi-folder-fill' }} text-primary"></i>
                                                                <span class="node-title fw-semibold">{{ $node->nama_layanan }}</span>
                                                                <span class="badge bg-light text-secondary border">{{ $nodeLabel }}</span>
                                                            </div>
                                                            @if($loop->last && $node->satuan)
                                                                <div class="text-muted ms-4">Satuan: {{ $node->satuan }}</div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <div class="fw-semibold">Layanan tidak ditemukan</div>
                                        @endif
                                        @if($layanan?->satuan)
                                            <small class="text-muted d-block mt-1">Satuan: {{ $layanan->satuan }}</small>
                                        @endif
                                        @if($detail->keterangan && $detail->keterangan !== ($layanan->nama_lengkap ?? null))
                                            <br><small class="text-muted">Keterangan: {{ $detail->keterangan }}</small>
                                        @endif
                                        @if(!empty($detail->calculation_payload['formula']))
                                            <br><small class="text-primary fw-semibold">Perhitungan: {{ $detail->calculation_payload['formula'] }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $detail->kode_akun ?: ($layanan->kode_pembayaran_lengkap ?? $layanan->kode_akun ?? '-') }}</td>
                                    <td class="text-center">{{ rtrim(rtrim(number_format($detail->qty, 2, ',', '.'), '0'), ',') }}</td>
                                    <td class="text-end">
                                        @if($isPercentageDetail)
                                            {{ rtrim(rtrim(number_format((float) $detail->harga_satuan, 4, ',', '.'), '0'), ',') }}%
                                        @else
                                            Rp {{ number_format($detail->harga_satuan, 0, ',', '.') }}
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end fw-bold">TOTAL TAGIHAN :</td>
                                <td class="text-end fw-bold text-success fs-5">Rp {{ number_format($tagihan->total_tagihan, 0, ',', '.') }}</td>
                            </tr>
                            @if($tagihan->nominal_denda_keterlambatan > 0)
                                <tr>
                                    <td colspan="5" class="text-end fw-bold text-danger">
                                        DENDA KETERLAMBATAN 2% x {{ $tagihan->hari_terlambat }} HARI :
                                    </td>
                                    <td class="text-end fw-bold text-danger">Rp {{ number_format($tagihan->nominal_denda_keterlambatan, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">TOTAL HARUS DIBAYAR :</td>
                                    <td class="text-end fw-bold text-primary fs-5">Rp {{ number_format($tagihan->total_dengan_denda, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>

                @if($tagihan->nomor_va)
                    <div class="tj-va-box mt-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary text-white" style="width: 44px; height: 44px;">
                                <i class="bi bi-credit-card fs-4"></i>
                            </span>
                            <div>
                                <div class="small text-muted fw-bold text-uppercase" style="letter-spacing:.04em;">Virtual Account Â· Bank BTN</div>
                                <div class="tj-va-number" id="tjVaNumber">{{ $tagihan->nomor_va }}</div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary tj-va-copy" onclick="(function(btn){const t=document.getElementById('tjVaNumber').textContent.trim();navigator.clipboard&&navigator.clipboard.writeText(t);btn.classList.add('btn-success');btn.classList.remove('btn-primary');btn.innerHTML='<i class=&quot;bi bi-check2 me-1&quot;></i>Tersalin';setTimeout(()=>{btn.classList.add('btn-primary');btn.classList.remove('btn-success');btn.innerHTML='<i class=&quot;bi bi-clipboard me-1&quot;></i>Salin VA';},1500);})(this)">
                            <i class="bi bi-clipboard me-1"></i>Salin VA
                        </button>
                    </div>
                @endif

                @if($tagihan->tanggal_jatuh_tempo)
                    @php
                        $dueLabel = match($tagihan->status_jatuh_tempo) {
                            'LEWAT_JATUH_TEMPO' => ['Lewat Jatuh Tempo', 'bg-danger'],
                            'JATUH_TEMPO_HARI_INI' => ['Jatuh Tempo Hari Ini', 'bg-dark'],
                            'MENDEKATI_JATUH_TEMPO' => ['Mendekati Jatuh Tempo', 'bg-warning text-dark'],
                            'LUNAS' => ['Lunas', 'bg-success'],
                            default => ['Normal', 'bg-success'],
                        };
                    @endphp
                    <div class="alert alert-light border mt-4">
                        <div class="d-flex flex-wrap justify-content-between gap-3">
                            <div>
                                <div class="small text-muted fw-bold">Tanggal Publish</div>
                                <div class="fw-semibold">{{ $tagihan->tanggal_publish ? \Carbon\Carbon::parse($tagihan->tanggal_publish)->format('d M Y') : '-' }}</div>
                            </div>
                            <div>
                                <div class="small text-muted fw-bold">Jatuh Tempo</div>
                                <div class="fw-semibold">{{ \Carbon\Carbon::parse($tagihan->tanggal_jatuh_tempo)->format('d M Y') }}</div>
                            </div>
                            <div>
                                <div class="small text-muted fw-bold">Status Jatuh Tempo</div>
                                <span class="badge {{ $dueLabel[1] }}">{{ $dueLabel[0] }}</span>
                            </div>
                        </div>
                        @if($tagihan->catatan_jatuh_tempo)
                            <div class="small text-muted mt-2">{{ $tagihan->catatan_jatuh_tempo }}</div>
                        @endif
                        @if($tagihan->nominal_denda_keterlambatan > 0)
                            <div class="alert alert-danger small mt-3 mb-0">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Denda berjalan: 2% per hari x {{ $tagihan->hari_terlambat }} hari = 
                                <strong>Rp {{ number_format($tagihan->nominal_denda_keterlambatan, 0, ',', '.') }}</strong>.
                                Total tagihan berjalan menjadi
                                <strong>Rp {{ number_format($tagihan->total_dengan_denda, 0, ',', '.') }}</strong>.
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Approval & Timeline -->
    <div class="col-lg-4">
        <!-- Kotak Aksi Approval -->
        @if($canApprove)
            <div class="card tj-card border-0 mb-4 border-start border-4 border-warning">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-shield-check text-warning me-2"></i>Tindakan Verifikasi</h5>
                    <p class="small text-muted mb-4">Anda bertugas sebagai <strong>{{ $currentApproval->role_code }}</strong> untuk memverifikasi dokumen tagihan ini.</p>
                    
                    <form action="{{ route('tagihan-jasa.approve', $tagihan->id) }}" method="POST" id="formApprove" class="mb-2">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Catatan (Opsional)</label>
                            <textarea name="catatan" class="form-control" rows="2" placeholder="Tuliskan catatan jika ada..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold" onclick="return confirm('Apakah Anda yakin menyetujui dokumen ini?')">
                            <i class="bi bi-check-lg me-1"></i> Setujui Dokumen
                        </button>
                    </form>
                    
                    <button type="button" class="btn btn-outline-danger w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#modalTolak">
                        <i class="bi bi-x-lg me-1"></i> Tolak Dokumen
                    </button>
                    <button type="button" class="btn btn-outline-warning w-100 fw-bold mt-2" data-bs-toggle="modal" data-bs-target="#modalRevisi">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Minta Revisi
                    </button>
                </div>
            </div>
        @endif

        @if($canReviseTagihanJasa)
            <div class="card tj-card border-0 mb-4 border-start border-4 border-info">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-pencil-square text-info me-2"></i>Edit Ulang Tagihan Jasa</h5>
                    <p class="small text-muted mb-4">Tagihan sedang dikembalikan untuk perbaikan. Simpan perubahan terlebih dahulu, lalu kirim ulang agar workflow verifikasi dimulai lagi dari awal.</p>
                    <a href="{{ route('tagihan-jasa.edit', $tagihan->id) }}" class="btn btn-outline-primary w-100 fw-bold mb-2">
                        <i class="bi bi-pencil me-1"></i> Edit Ulang Tagihan Jasa
                    </a>
                    <form action="{{ route('tagihan-jasa.resubmit', $tagihan->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-info text-white w-100 fw-bold" onclick="return confirm('Kirim ulang tagihan ini ke workflow verifikasi?')">
                            <i class="bi bi-send-check me-1"></i> Kirim Ulang ke Verifikasi
                        </button>
                    </form>
                </div>
            </div>
        @endif

        @if(Auth::user()->hasRole(['Super Admin', 'Admin Jasa']) && $wfInstance && $wfInstance->status === 'IN_PROGRESS')
            <div class="card tj-card border-0 mb-4 border-start border-4 border-info">
                <div class="card-body p-4 text-center">
                    <h5 class="fw-bold mb-2"><i class="bi bi-lightning-charge text-info me-2"></i>Mode Cepat (Testing)</h5>
                    <p class="small text-muted mb-3">Approve semua step verifikasi sekaligus tanpa perlu login satu-satu.</p>
                    <form action="{{ route('tagihan-jasa.auto-approve', $tagihan->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-info text-white w-100 fw-bold" onclick="return confirm('Auto-approve semua step verifikasi yang tersisa?')">
                            <i class="bi bi-fast-forward-fill me-1"></i> Auto-Approve Semua Step
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <div class="card tj-card border-0 mb-4">
            <div class="card-header bg-white p-3 border-bottom rounded-top-4 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-envelope-paper text-primary me-2"></i>Surat Pengantar Tagihan</h6>
                @if($tagihan->file_surat_pengantar_final)
                    <span class="badge bg-success">Sudah Ditandatangani</span>
                @else
                    <span class="badge bg-warning text-dark">Draft</span>
                @endif
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <div class="small text-muted fw-bold">Nomor Surat</div>
                    <div class="fw-semibold">{{ $tagihan->nomor_surat_pengantar ?: '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="small text-muted fw-bold">Tanggal Surat</div>
                    <div class="fw-semibold">
                        {{ $tagihan->tanggal_surat_pengantar ? \Carbon\Carbon::parse($tagihan->tanggal_surat_pengantar)->format('d M Y') : '-' }}
                    </div>
                </div>
                <div class="mb-3">
                    <div class="small text-muted fw-bold">Pejabat Penandatangan</div>
                    <div class="fw-semibold">{{ $tagihan->pejabat_penandatangan_nama ?: '-' }}</div>
                    <div class="small text-muted">
                        {{ $tagihan->pejabat_penandatangan_jabatan ?: '-' }}
                        @if($tagihan->pejabat_penandatangan_nip)
                            <br>NIP. {{ $tagihan->pejabat_penandatangan_nip }}
                        @endif
                    </div>
                </div>
                <div class="d-grid gap-2 mb-3">
                    <a href="{{ route('tagihan-jasa.surat-pengantar', $tagihan->id) }}" target="_blank" class="btn btn-outline-primary fw-bold">
                        <i class="bi bi-eye me-1"></i> Preview Draft Surat Pengantar
                    </a>
                    @if($tagihan->file_surat_pengantar_final)
                        <a href="{{ route('tagihan-jasa.surat-pengantar-final.view', $tagihan->id) }}" target="_blank" class="btn btn-success fw-bold">
                            <i class="bi bi-file-earmark-check me-1"></i> Lihat Surat Pengantar TTD
                        </a>
                    @endif
                </div>

                @php
                    $arsipSuratPengantar = $tagihan->arsipDokumen
                        ->whereIn('jenis_dokumen', ['SURAT_PENGANTAR_DRAFT', 'SURAT_PENGANTAR_FINAL_TTD'])
                        ->sortByDesc('uploaded_at');
                @endphp
                @if($arsipSuratPengantar->isNotEmpty())
                    <hr>
                    <div class="mb-3">
                        <div class="small text-muted fw-bold mb-2">
                            <i class="bi bi-archive me-1"></i> Arsip Surat Pengantar
                        </div>
                        <div class="list-group small">
                            @foreach($arsipSuratPengantar->take(5) as $arsip)
                                <a href="{{ route('tagihan-jasa.surat-pengantar-arsip.view', [$tagihan->id, $arsip->id]) }}" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between gap-2 align-items-start">
                                    <span>
                                        <span class="fw-bold d-block">
                                            {{ $arsip->jenis_dokumen === 'SURAT_PENGANTAR_FINAL_TTD' ? 'Final TTD' : 'Draft' }}
                                            @if($arsip->is_active)
                                                <span class="badge bg-success ms-1">Aktif</span>
                                            @endif
                                        </span>
                                        <span class="text-muted">{{ $arsip->uploaded_at?->format('d M Y H:i') ?? '-' }} oleh {{ $arsip->uploader->name ?? 'Sistem' }}</span>
                                    </span>
                                    <i class="bi bi-box-arrow-up-right text-primary"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($canEditSuratPengantar)
                    <hr>
                    <button class="btn btn-light border w-100 fw-bold mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#formEditSuratPengantar" aria-expanded="false" aria-controls="formEditSuratPengantar">
                        <i class="bi bi-pencil-square me-1"></i> Edit Data Surat Pengantar
                    </button>
                    <div class="collapse" id="formEditSuratPengantar">
                        <form action="{{ route('tagihan-jasa.surat-pengantar.update', $tagihan->id) }}" method="POST" class="mb-3">
                            @csrf
                            @method('PUT')
                            <div class="mb-2">
                                <label class="form-label small fw-bold">Nomor Surat</label>
                                <input type="text" name="nomor_surat_pengantar" class="form-control form-control-sm" value="{{ old('nomor_surat_pengantar', $tagihan->nomor_surat_pengantar) }}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-bold">Tanggal Surat</label>
                                <input type="date" name="tanggal_surat_pengantar" class="form-control form-control-sm" value="{{ old('tanggal_surat_pengantar', $tagihan->tanggal_surat_pengantar ? \Carbon\Carbon::parse($tagihan->tanggal_surat_pengantar)->format('Y-m-d') : '') }}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-bold">Perihal</label>
                                <input type="text" name="perihal_surat_pengantar" class="form-control form-control-sm" value="{{ old('perihal_surat_pengantar', $tagihan->perihal_surat_pengantar) }}">
                            </div>
                            <div class="alert alert-light border small mb-3">
                                Pejabat penandatangan otomatis mengikuti data user role KPA:
                                <strong>{{ $tagihan->pejabat_penandatangan_nama ?: '-' }}</strong>
                                @if($tagihan->pejabat_penandatangan_jabatan)
                                    ({{ $tagihan->pejabat_penandatangan_jabatan }})
                                @endif
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-bold">
                                <i class="bi bi-save me-1"></i> Simpan Perubahan Surat
                            </button>
                        </form>
                    </div>
                @endif

                @if($canUploadSuratPengantarFinal && ! $tagihan->file_surat_pengantar_final)
                    <hr>
                    <form action="{{ route('tagihan-jasa.surat-pengantar-final.upload', $tagihan->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="alert alert-info small">
                            Upload final hanya untuk PDF surat yang sudah ditandatangani. Nomor surat, tanggal surat, dan pejabat diatur pada draft surat pengantar.
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Upload Surat TTD <span class="text-danger">*</span></label>
                            <input type="file" name="file_surat_pengantar_final" class="form-control form-control-sm" accept="application/pdf" required>
                            <small class="text-muted">Unggah PDF surat pengantar yang sudah ditandatangani pejabat.</small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            <i class="bi bi-upload me-1"></i> Simpan Surat Final
                        </button>
                    </form>
                @elseif($tagihan->file_surat_pengantar_final)
                    <div class="alert alert-success small mb-0">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        Surat pengantar final sudah tersimpan otomatis setelah persetujuan final.
                    </div>
                @elseif(! $workflowApproved)
                    <div class="alert alert-light border small mb-0">
                        Upload surat pengantar final tersedia setelah seluruh verifikasi disetujui.
                    </div>
                @endif
            </div>
        </div>

        @if($canManageTagihanJasa && $wfInstance && $wfInstance->status === 'APPROVED' && !in_array($tagihan->status, ['PUBLISHED', 'LUNAS']))
            <div class="card tj-card border-0 mb-4 border-start border-4 border-success">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-send text-success me-2"></i>Terbitkan Tagihan</h5>
                    <p class="small text-muted mb-4">Tagihan telah disetujui sepenuhnya oleh Kabandara. Anda dapat mem-publish tagihan ini untuk men-generate VA dan mengirim notifikasi otomatis ke Mitra.</p>

                    @if(! $suratFinalReady)
                        <div class="alert alert-warning small">
                            Generate surat pengantar final TTD terlebih dahulu sebelum publish ke mitra.
                        </div>
                        <button type="button" class="btn btn-secondary w-100 fw-bold mb-2" disabled>
                            <i class="bi bi-lock me-1"></i> Publish Terkunci
                        </button>
                    @else
                        <button type="button" class="btn btn-success w-100 fw-bold mb-2" data-bs-toggle="modal" data-bs-target="#modalPublish">
                            <i class="bi bi-rocket-takeoff me-1"></i> Publish & Kirim Notifikasi WA
                        </button>
                    @endif
                    <a href="{{ route('tagihan-jasa.pdf', $tagihan->id) }}" target="_blank" class="btn btn-outline-primary w-100 fw-bold">
                        <i class="bi bi-file-pdf me-1"></i> Cek PDF Sebelum Publish
                    </a>
                </div>
            </div>
        @endif

        @if($canManageTagihanJasa && $tagihan->status === 'PUBLISHED')
            <div class="card tj-card border-0 mb-4 border-start border-4 border-primary">
                <div class="card-body p-4 text-center">
                    <h5 class="fw-bold mb-3"><i class="bi bi-cash-coin text-primary me-2"></i>Status Pembayaran</h5>
                    <p class="small text-muted mb-4">Tagihan ini sedang menunggu pembayaran dari Mitra via Virtual Account. Untuk keperluan simulasi, Anda dapat menandai tagihan ini menjadi LUNAS secara manual.</p>
                    
                    <form action="{{ route('tagihan-jasa.mark-lunas', $tagihan->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100 fw-bold" onclick="return confirm('Tandai tagihan ini sebagai LUNAS? Ini mensimulasikan callback sukses dari Bank BTN.')">
                            <i class="bi bi-check-circle me-1"></i> Simulasi: Konfirmasi Lunas
                        </button>
                    </form>
                </div>
            </div>
        @endif

        @if($tagihan->status === 'LUNAS')
            <div class="card tj-card border-0 mb-4 border-start border-4 border-success bg-success-subtle">
                <div class="card-body p-4 text-center">
                    <h5 class="fw-bold mb-2 text-success"><i class="bi bi-patch-check-fill me-2"></i>PEMBAYARAN LUNAS</h5>
                    <p class="small text-success mb-0">Pembayaran untuk tagihan ini telah dikonfirmasi oleh sistem Bank.</p>
                </div>
            </div>
        @endif

        <!-- Timeline Log -->
        <div class="card tj-card border-0 mb-4">
            <div class="card-header bg-white p-3 border-bottom rounded-top-4">
                <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history text-primary me-2"></i>Riwayat Proses</h6>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="tj-timeline">
                    {{-- Dibuat --}}
                    <div class="tj-timeline-item">
                        <div class="tj-timeline-dot primary"><i class="bi bi-file-earmark-plus"></i></div>
                        <div class="tj-timeline-content">
                            <div class="tj-timeline-title">Dibuat oleh {{ $tagihan->creator->name ?? 'Admin' }}</div>
                            <div class="tj-timeline-time"><i class="bi bi-calendar2-check"></i>{{ \Carbon\Carbon::parse($tagihan->created_at)->format('d M Y, H:i') }}</div>
                        </div>
                    </div>

                    @if($wfInstance)
                        @foreach($wfInstance->approvals as $approval)
                            @php
                                $isApproved = $approval->status === 'APPROVED';
                                $isRejected = $approval->status === 'REJECTED';
                                $isRevision = $approval->status === 'REVISION';
                                $color = $isApproved ? 'success' : ($isRejected ? 'danger' : ($isRevision ? 'info' : 'warning'));
                                $icon = $isApproved ? 'bi-check2' : ($isRejected ? 'bi-x-lg' : ($isRevision ? 'bi-arrow-counterclockwise' : 'bi-hourglass-split'));
                            @endphp
                            <div class="tj-timeline-item">
                                <div class="tj-timeline-dot {{ $color }}"><i class="bi {{ $icon }}"></i></div>
                                <div class="tj-timeline-content">
                                    <div class="tj-timeline-title">{{ $formatWorkflowLabel($approval->nama_step) }}</div>
                                    <div class="small fw-semibold text-slate" style="color:#334155;">{{ $approval->actedByUser->name ?? $formatWorkflowLabel($approval->role_code) }}</div>
                                    @if($approval->catatan)
                                        <div class="tj-timeline-note">"{{ $approval->catatan }}"</div>
                                    @endif
                                    <div class="tj-timeline-time"><i class="bi bi-clock"></i>{{ \Carbon\Carbon::parse($approval->created_at)->format('d M Y, H:i') }}</div>
                                </div>
                            </div>
                        @endforeach
                    @endif

                    @if(in_array($tagihan->status, ['PUBLISHED', 'LUNAS']))
                        <div class="tj-timeline-item">
                            <div class="tj-timeline-dot info"><i class="bi bi-send"></i></div>
                            <div class="tj-timeline-content">
                                <div class="tj-timeline-title">Tagihan Dipublish (Terkirim ke Mitra)</div>
                                <div class="tj-timeline-time"><i class="bi bi-calendar2-check"></i>{{ \Carbon\Carbon::parse($tagihan->updated_at)->format('d M Y, H:i') }}</div>
                            </div>
                        </div>
                    @endif

                    @if($tagihan->status === 'LUNAS' && $tagihan->tanggal_lunas)
                        <div class="tj-timeline-item">
                            <div class="tj-timeline-dot success"><i class="bi bi-patch-check"></i></div>
                            <div class="tj-timeline-content">
                                <div class="tj-timeline-title">Tagihan Lunas</div>
                                <div class="tj-timeline-time"><i class="bi bi-calendar2-check"></i>{{ \Carbon\Carbon::parse($tagihan->tanggal_lunas)->format('d M Y, H:i') }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if(in_array($tagihan->status, ['PUBLISHED', 'LUNAS']))
        <!-- Persistent WA Preview for Published Bills -->
        <div class="card tj-card border-0 mb-4 border-start border-4 border-success">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-whatsapp text-success me-2"></i>Pesan Tagihan WA Mitra</h6>
                @php
                    $persistentPesan = "*PEMBERITAHUAN TAGIHAN PNBP*\n\n";
                    $persistentPesan .= "Yth. " . ($mitraTagihan->nama_pihak ?? '-') . ",\n\n";
                    $persistentPesan .= "Berikut adalah informasi tagihan layanan Anda:\n";
                    $persistentPesan .= "No Tagihan : *" . $tagihan->nomor_tagihan . "*\n";
                    $persistentPesan .= "Total Tagihan : *Rp " . number_format((float) $tagihan->total_tagihan, 0, ',', '.') . "*\n\n";
                    $persistentPesan .= "Silakan lakukan pembayaran melalui Virtual Account Bank BTN berikut:\n";
                    $persistentPesan .= "No VA : *" . ($tagihan->nomor_va ?? '-') . "*\n";
                    if ($tagihan->tanggal_jatuh_tempo) {
                        $persistentPesan .= "Jatuh Tempo : *" . \Carbon\Carbon::parse($tagihan->tanggal_jatuh_tempo)->translatedFormat('d F Y') . "*\n";
                    }
                    $persistentPesan .= "Link Invoice : " . App\Models\ShortLink::forTarget('tagihan_jasa', $tagihan->id)->publicUrl() . "\n\n";
                    $persistentPesan .= "----------------------------------------\n";
                    $persistentPesan .= "*AKUN PORTAL MITRA*\n";
                    $persistentPesan .= "Silakan login menggunakan akun Mitra Anda yang sudah terdaftar.\n";
                    $persistentPesan .= "Login Portal : " . route('login') . "\n";
                    $persistentPesan .= "----------------------------------------\n\n";
                    $persistentPesan .= "Terima kasih atas kerja sama Anda.\n";
                    $persistentPesan .= "_Sistem Informasi Keuangan (SIKEREN)_";
                @endphp
                <div class="bg-light p-3 rounded border" style="white-space: pre-wrap; font-family: monospace; font-size: 0.8rem;">{{ $persistentPesan }}</div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal Revisi -->
<div class="modal fade" id="modalRevisi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ route('tagihan-jasa.revision', $tagihan->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-warning text-dark border-0">
                    <h5 class="modal-title fw-bold">Minta Revisi Tagihan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border mb-4 small">
                        Tagihan akan masuk status REVISI. Admin Jasa dapat mengedit detail tagihan lalu mengirim ulang ke alur verifikasi.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Catatan Revisi <span class="text-danger">*</span></label>
                        <textarea name="catatan" class="form-control" rows="3" required placeholder="Tuliskan bagian yang perlu diperbaiki..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-arrow-counterclockwise me-1"></i> Minta Revisi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tolak -->
<div class="modal fade" id="modalTolak" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ route('tagihan-jasa.reject', $tagihan->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold">Tolak Dokumen Tagihan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border mb-4 small">
                        Penolakan menutup workflow berjalan dan memberi status DITOLAK. Untuk perbaikan dengan workflow revisi, gunakan tombol Minta Revisi.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="catatan" class="form-control" rows="3" required placeholder="Jelaskan alasan dokumen ditolak..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-x-circle me-1"></i> Tolak Tagihan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal Publish -->
<div class="modal fade" id="modalPublish" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ route('tagihan-jasa.publish', $tagihan->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-send me-1"></i> Publish Tagihan & Kirim WA</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    @php
                        $mitraTagihanModal = $tagihan->mitra ?? $tagihan->mitraLegacy;
                        $defaultWa = $mitraTagihanModal->no_telepon ?? '';
                        $namaMitraModal = $mitraTagihanModal->nama_pihak ?? $mitraTagihanModal->nama_mitra ?? '';
                    @endphp

                    <div class="alert alert-light border mb-4 small">
                        Tagihan akan diterbitkan (VA di-generate) dan notifikasi pesan instan otomatis dikirimkan via WhatsApp ke nomor mitra di bawah.
                    </div>

                    <div class="mb-2 d-flex justify-content-between align-items-end flex-wrap gap-2">
                        <label class="form-label fw-bold m-0" for="waTujuanInput">
                            <i class="bi bi-whatsapp text-success me-1"></i>
                            No WhatsApp Tujuan <span class="text-danger">*</span>
                        </label>
                        @if($defaultWa)
                            <button type="button" class="btn btn-sm btn-link text-success p-0 fw-bold text-decoration-none" id="resetWaToMitra">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Pakai nomor mitra
                            </button>
                        @endif
                    </div>

                    <div class="input-group">
                        <span class="input-group-text bg-success-subtle border-success-subtle text-success fw-bold">+62</span>
                        <input type="text"
                            id="waTujuanInput"
                            name="wa_tujuan"
                            class="form-control fw-semibold"
                            value="{{ $defaultWa }}"
                            placeholder="08xxxxxxxxxx atau 628xxxxxxxxxx"
                            data-default="{{ $defaultWa }}"
                            inputmode="numeric"
                            readonly
                            required>
                        <button type="button"
                            id="waTujuanLockBtn"
                            class="btn btn-success d-inline-flex align-items-center gap-1"
                            title="Klik untuk membuka kunci dan mengedit nomor"
                            aria-pressed="true">
                            <i class="bi bi-lock-fill" id="waTujuanLockIcon"></i>
                            <span id="waTujuanLockText">Terkunci</span>
                        </button>
                    </div>

                    <div class="mt-2">
                        @if($defaultWa)
                            <div class="small text-muted">
                                <i class="bi bi-bookmark-check-fill text-success me-1"></i>
                                Nomor default diambil dari data mitra
                                <strong class="text-dark">{{ $namaMitraModal }}</strong>.
                                Klik tombol <strong>Terkunci</strong> di samping input untuk mengganti nomor.
                            </div>
                        @else
                            <div class="alert alert-warning small d-flex align-items-start gap-2 mb-0">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <span>Mitra <strong>{{ $namaMitraModal ?: '-' }}</strong> belum memiliki nomor telepon. Klik tombol kunci di samping input untuk mengisi manual.</span>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-bold"
                        data-sky-confirm="Apakah Anda yakin ingin mem-publish dan mengirim WA?"
                        data-sky-confirm-title="Publish Tagihan"
                        data-sky-confirm-text="Ya, Publish & Kirim">
                        <i class="bi bi-send me-1"></i> Publish & Kirim WA
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script')
<script>
    (function () {
        const input = document.getElementById('waTujuanInput');
        const reset = document.getElementById('resetWaToMitra');
        const lockBtn = document.getElementById('waTujuanLockBtn');
        const lockIcon = document.getElementById('waTujuanLockIcon');
        const lockText = document.getElementById('waTujuanLockText');
        if (! input || ! lockBtn) return;

        // Reset to default mitra phone
        if (reset) {
            reset.addEventListener('click', () => {
                input.value = input.dataset.default || '';
                input.focus();
            });
        }

        // Lock toggle
        const setLocked = (locked) => {
            input.readOnly = locked;
            lockBtn.setAttribute('aria-pressed', locked ? 'true' : 'false');

            if (locked) {
                input.classList.remove('is-unlocked');
                lockIcon.className = 'bi bi-lock-fill';
                lockText.textContent = 'Terkunci';
                lockBtn.classList.remove('btn-warning');
                lockBtn.classList.add('btn-success');
                lockBtn.title = 'Klik untuk membuka kunci dan mengedit nomor';
            } else {
                input.classList.add('is-unlocked');
                lockIcon.className = 'bi bi-unlock-fill';
                lockText.textContent = 'Bisa diedit';
                lockBtn.classList.remove('btn-success');
                lockBtn.classList.add('btn-warning');
                lockBtn.title = 'Klik untuk mengunci kembali';
                // Beri fokus saat dibuka supaya user langsung bisa mengetik.
                setTimeout(() => input.focus(), 80);
            }
        };

        lockBtn.addEventListener('click', () => setLocked(! input.readOnly));
        // Init: default terkunci.
        setLocked(true);
    })();
</script>
<style>
    /* Visual feedback saat input terbuka */
    #waTujuanInput.is-unlocked {
        background: #fff;
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245,158,11,.18);
    }
    #waTujuanInput[readonly] {
        background: #f8fafc;
        cursor: not-allowed;
        color: #475569;
    }
</style>
@endpush
@endsection
