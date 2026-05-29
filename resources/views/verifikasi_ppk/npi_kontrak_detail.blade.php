@extends('layouts.app')
@section('title', 'Detail Verifikasi NPI Kontrak - ' . ($currentRole ?? 'Verifikator'))

@push('css')
    @include('verifikasi_npi._styles')
@endpush

@section('content')
@php
    use App\Models\DokumenNpi;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $isNpiFinal = in_array($npi->status, [
        DokumenNpi::STATUS_DISETUJUI_FINAL,
        DokumenNpi::STATUS_APPROVED_KASUBAG,
        DokumenNpi::STATUS_MENUNGGU_UPLOAD,
        DokumenNpi::STATUS_NPI_TERBIT,
    ], true);
    $isRevisi = $npi->status === DokumenNpi::STATUS_REVISI || $statusFinal === 'Perlu Revisi';

    if ($isNpiFinal) { $heroClass = 'is-final'; $statusKey = 's-final'; }
    elseif ($isRevisi) { $heroClass = 'is-revisi'; $statusKey = 's-revisi'; }
    else { $heroClass = ''; $statusKey = 's-wait'; }

    $allApprovals = collect($activeWorkflowInstance?->approvals ?? [])->sortBy('urutan_step');
    $actionApprovals = collect($activeRoleApprovals ?? []);
    $myRoleNames = collect(explode(' / ', (string) ($currentRole ?? '')))->filter()->values();

    $statusMeta = [
        'APPROVED' => ['badge' => 'bg-success', 'cls' => 'is-approved', 'ico' => 'bx-check', 'label' => 'Disetujui'],
        'PENDING'  => ['badge' => 'bg-warning text-dark', 'cls' => 'is-pending', 'ico' => 'bx-time-five', 'label' => 'Menunggu'],
        'REVISION' => ['badge' => 'bg-danger', 'cls' => 'is-revision', 'ico' => 'bx-undo', 'label' => 'Revisi'],
        'REJECTED' => ['badge' => 'bg-danger', 'cls' => 'is-revision', 'ico' => 'bx-x', 'label' => 'Ditolak'],
        'WAITING'  => ['badge' => 'bg-light text-dark border', 'cls' => 'is-waiting', 'ico' => 'bx-minus', 'label' => 'Antri'],
        'N/A'      => ['badge' => 'bg-light text-dark border', 'cls' => 'is-waiting', 'ico' => 'bx-minus', 'label' => 'Belum Ada'],
    ];

    $requiredDocs = collect($documentStatuses ?? [])->where('required', true);
    $readyRequiredDocs = $requiredDocs->where('status', 'ready')->count();
    $checklist = collect([
        ['label' => 'Status SPM Awal', 'status' => filled($spm?->nomor_spm) ? 'ready' : 'missing', 'message' => filled($spm?->nomor_spm) ? 'SPM sumber tersedia.' : 'SPM sumber belum lengkap.'],
        ['label' => 'Tujuan Penerimaan NPI', 'status' => filled($npi->bendahara_penerimaan_id) ? 'ready' : 'missing', 'message' => filled($npi->bendahara_penerimaan_id) ? 'Bendahara Penerimaan tervalidasi.' : 'Penunjukan bendahara rumpang.'],
        ['label' => 'Vendor dan Rekening', 'status' => filled($vendor?->nama_pihak) && filled($rekening?->nomor_rekening) ? 'ready' : 'missing', 'message' => filled($rekening?->nomor_rekening) ? 'Data vendor dan rekening pembayaran tersedia.' : 'Rekening vendor belum lengkap.'],
        ['label' => 'Dokumen Wajib Kontrak', 'status' => ($requiredDocs->count() === 0 || $readyRequiredDocs === $requiredDocs->count()) ? 'ready' : 'missing', 'message' => "{$readyRequiredDocs} dari {$requiredDocs->count()} dokumen wajib tersedia."],
    ])->values();
    $readyCount = $checklist->where('status', 'ready')->count();
    $totalReady = $checklist->count();
    $readyPct = $totalReady > 0 ? round($readyCount / $totalReady * 100) : 0;

    $coa = $spm?->dipaRevisionItem?->coa ?? null;
    $kontrakLabel = $kontrak?->nama_pekerjaan ?? $tagihan?->deskripsi ?? 'NPI Kontrak';
@endphp

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
        <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
        <i class="bx bx-error-circle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
        <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ====== HERO ====== --}}
<div class="card vnpi-hero {{ $heroClass }} mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3">
            <div class="npi-min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="hero-tag"><i class="bx bx-briefcase me-1"></i> Verifikasi NPI Kontrak</span>
                    <span class="vnpi-pill {{ $statusKey }}">{{ str_replace('_', ' ', $npi->status) }}</span>
                    @if($canApprove)<span class="vnpi-pill s-alert"><i class="bx bx-bell"></i> Menunggu Aksi Anda</span>@endif
                </div>
                <h3 class="fw-bold mb-1 text-break">{{ $npi->nomor_npi ?? 'NPI Belum Bernomor' }}</h3>
                <div class="hero-sub small text-break"><i class="bx bx-receipt me-1"></i>{{ $kontrakLabel }}</div>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                <a href="{{ route(($routePrefix ?? 'verifikasi-ppk.npi.kontrak') . '.index') }}" class="btn btn-hero-light"><i class="bx bx-arrow-back"></i> Kembali</a>
                @if($isNpiFinal)
                    <a href="{{ route('npis.cetak-pdf', $npi->id) }}" target="_blank" class="btn btn-light fw-semibold"><i class="bx bxs-file-pdf text-danger"></i> Cetak PDF</a>
                @endif
            </div>
        </div>

        <div class="hero-meta p-3">
            <div class="row g-3">
                <div class="col-6 col-md-3"><div class="field-label">Nomor SPM</div><div class="field-value text-truncate">{{ $spm?->nomor_spm ?? '-' }}</div></div>
                <div class="col-6 col-md-3"><div class="field-label">Nomor SPP</div><div class="field-value text-truncate">{{ $spp?->nomor_spp ?? '-' }}</div></div>
                <div class="col-6 col-md-2"><div class="field-label">Termin</div><div class="field-value">{{ $termin?->termin_ke ?? '-' }}</div></div>
                <div class="col-6 col-md-4">
                    <div class="field-label">Nilai NPI (Netto)</div>
                    <div class="nominal-hero">Rp {{ number_format($nominalNpi, 0, ',', '.') }}</div>
                </div>
            </div>
            <hr class="my-3" style="border-color: rgba(255,255,255,.18);">
            <div class="row g-3">
                <div class="col-md-6"><div class="field-label">Tujuan Penerimaan (Bendahara Penerimaan)</div><div class="field-value"><i class="bx bx-user-check"></i> {{ $npi->bendaharaPenerimaan?->name ?? 'Belum ditunjuk' }}</div></div>
                <div class="col-md-6"><div class="field-label">Tanggal NPI</div><div class="field-value">{{ optional($npi->tanggal_npi)->format('d F Y') ?? '-' }}</div></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- ====== KOLOM KIRI ====== --}}
    <div class="col-xl-8">

        {{-- Progress Verifikasi Paralel --}}
        <div class="vnpi-card c-rose mb-4">
            <div class="vnpi-card-head">
                <span class="ico-wrap"><i class="bx bx-git-merge"></i></span>
                <div><h6>Progress Verifikasi Paralel</h6><span class="head-sub">Persetujuan mufakat seluruh verifikator</span></div>
            </div>
            <div class="vnpi-card-body">
                @forelse($allApprovals as $ap)
                    @php
                        $meta = $statusMeta[$ap->status] ?? $statusMeta['WAITING'];
                        $isMine = $myRoleNames->contains($ap->role_code)
                            && (!$ap->assigned_user_id || (int) $ap->assigned_user_id === (int) auth()->id());
                    @endphp
                    <div class="approval-row {{ $meta['cls'] }} {{ $isMine ? 'is-mine' : '' }}">
                        <div class="d-flex align-items-center gap-3">
                            <span class="role-avatar"><i class="bx {{ $meta['ico'] }}"></i></span>
                            <div class="flex-grow-1 npi-min-w-0">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="fw-bold text-dark">
                                        {{ $ap->role_code }}
                                        @if($isMine)<span class="badge bg-primary bg-opacity-10 text-primary ms-1" style="font-size:9px;">PERAN ANDA</span>@endif
                                    </div>
                                    <span class="approval-badge {{ $meta['badge'] }}">{{ $meta['label'] }}</span>
                                </div>
                                <div class="text-muted small">{{ $ap->actedByUser?->name ?? $ap->assignedUser?->name ?? 'Semua pemegang peran' }}</div>
                                @if($ap->acted_at)
                                    <div class="text-muted" style="font-size:.7rem;"><i class="bx bx-time"></i> {{ \Carbon\Carbon::parse($ap->acted_at)->format('d M Y H:i') }}</div>
                                @endif
                                @if($ap->catatan)
                                    <div class="small fst-italic text-muted mt-1 p-2 bg-light rounded">"{{ $ap->catatan }}"</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted small py-3">Belum ada antrean verifikasi.</div>
                @endforelse
            </div>
        </div>

        {{-- Dokumen Sumber --}}
        <div class="vnpi-card c-blue mb-4">
            <div class="vnpi-card-head">
                <span class="ico-wrap"><i class="bx bx-file"></i></span>
                <div><h6>Dokumen Sumber & Nilai</h6><span class="head-sub">Penelusuran SPM / SPP / Tagihan</span></div>
            </div>
            <div class="vnpi-card-body">
                <div class="row g-3">
                    <div class="col-md-4"><span class="field-label">Nomor SPM</span><span class="field-value">{{ $spm?->nomor_spm ?? '-' }}</span><div class="text-muted small">{{ optional($spm?->tanggal_spm)->format('d M Y') ?? '-' }}</div></div>
                    <div class="col-md-4"><span class="field-label">Nomor SPP</span><span class="field-value">{{ $spp?->nomor_spp ?? '-' }}</span><div class="text-muted small">{{ optional($spp?->tanggal_spp)->format('d M Y') ?? '-' }}</div></div>
                    <div class="col-md-4"><span class="field-label">Nomor Tagihan</span><span class="field-value">{{ $tagihan?->nomor_tagihan ?? '-' }}</span><div class="text-muted small">PPK: {{ $spp?->ppkVerifikator?->name ?? '-' }}</div></div>
                    <div class="col-12">
                        <span class="field-label">Kode COA (Mata Anggaran)</span>
                        @if($coa)
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 font-monospace">{{ $coa->kode_mak_lengkap ?? $coa->kd_akun }}</span>
                            <span class="small fw-normal d-block mt-1 text-muted">{{ $coa->nama_akun }}</span>
                        @else
                            <span class="text-muted fst-italic">Belum ada akun DIPA tertaut.</span>
                        @endif
                    </div>
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-md-4"><div class="mini-doc" style="--card-accent:#2563eb;"><div class="doc-label">Bruto</div><div class="fw-bold">Rp {{ number_format($tagihan?->total_bruto ?? 0, 0, ',', '.') }}</div></div></div>
                    <div class="col-md-4"><div class="mini-doc" style="--card-accent:#e11d48;"><div class="doc-label text-danger">Potongan / Pajak</div><div class="fw-bold text-danger">Rp {{ number_format($tagihan?->total_potongan ?? 0, 0, ',', '.') }}</div></div></div>
                    <div class="col-md-4"><div class="mini-doc" style="--card-accent:#16a34a;"><div class="doc-label text-success">Netto NPI</div><div class="fw-bold text-success">Rp {{ number_format($nominalNpi, 0, ',', '.') }}</div></div></div>
                </div>
            </div>
        </div>

        {{-- Rincian Kontrak --}}
        <div class="vnpi-card c-teal mb-4">
            <div class="vnpi-card-head">
                <span class="ico-wrap"><i class="bx bx-briefcase"></i></span>
                <div class="flex-grow-1 d-flex justify-content-between align-items-center gap-2">
                    <div><h6>Rincian Kontrak & Termin</h6><span class="head-sub">Dasar pembayaran dan penerima dana</span></div>
                    <span class="badge bg-light text-dark border text-truncate">{{ $kontrak?->nomor_spk ?? 'SPK belum tersedia' }}</span>
                </div>
            </div>
            <div class="vnpi-card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <span class="field-label">Nama Pekerjaan</span>
                        <div class="field-value text-break">{{ $kontrak?->nama_pekerjaan ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <span class="field-label">Termin</span>
                        <div class="field-value">{{ $termin?->termin_ke ?? '-' }} {{ $termin?->jenis_termin ? '(' . str_replace('_', ' ', $termin->jenis_termin) . ')' : '' }}</div>
                    </div>
                    <div class="col-md-4"><div class="mini-doc" style="--card-accent:#0d9488;"><div class="doc-label">BAPP</div><div class="fw-bold">{{ $detailKontrak?->nomor_bapp ?? '-' }}</div></div></div>
                    <div class="col-md-4"><div class="mini-doc" style="--card-accent:#0d9488;"><div class="doc-label">BAST</div><div class="fw-bold">{{ $detailKontrak?->nomor_bast ?? '-' }}</div></div></div>
                    <div class="col-md-4"><div class="mini-doc" style="--card-accent:#0d9488;"><div class="doc-label">BAP</div><div class="fw-bold">{{ $detailKontrak?->nomor_bap ?? '-' }}</div></div></div>
                </div>
            </div>
        </div>

        {{-- Kelengkapan Dokumen --}}
        <div class="vnpi-card c-purple mb-4">
            <div class="vnpi-card-head">
                <span class="ico-wrap"><i class="bx bx-folder-open"></i></span>
                <div><h6>Kelengkapan Dokumen Pendukung</h6><span class="head-sub">Dokumen yang melekat pada NPI Kontrak</span></div>
            </div>
            <div class="vnpi-card-body">
                @forelse($documentStatuses as $doc)
                    @php
                        $isReady = $doc['status'] === 'ready';
                        $path = $doc['path'] ?? null;
                        $docUrl = null;
                        if (($doc['key'] ?? null) === 'spm' && $spm) {
                            $docUrl = route('spms.cetak-pdf', $spm->id);
                        } elseif (($doc['key'] ?? null) === 'spp' && $spp) {
                            $docUrl = route('spps.cetak-pdf', $spp->id);
                        } elseif (is_string($path) && filled($path)) {
                            $docUrl = filter_var($path, FILTER_VALIDATE_URL) ? $path : Storage::url($path);
                        }
                    @endphp
                    <div class="doc-row {{ $isReady ? 'is-ready' : 'is-optional' }} d-flex justify-content-between align-items-center">
                        <div class="npi-min-w-0">
                            <div class="fw-semibold text-truncate">
                                <i class="bx {{ $isReady ? 'bx-check-circle text-success' : 'bx-x-circle text-danger' }} me-1"></i>{{ $doc['label'] }}
                                @if(!($doc['required'] ?? true))<small class="text-muted">(Opsional)</small>@endif
                            </div>
                            <div class="text-muted small">{{ $isReady ? 'Dokumen tersedia untuk diperiksa.' : (($doc['required'] ?? true) ? 'Dokumen wajib belum tersedia.' : 'Dokumen tidak wajib.') }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-2 ms-2">
                            <span class="badge {{ $isReady ? 'bg-success bg-opacity-10 text-success' : (($doc['status'] ?? '') === 'missing' ? 'bg-danger' : 'bg-light text-dark border') }}">
                                {{ $isReady ? 'Tersedia' : (($doc['status'] ?? '') === 'missing' ? 'Belum Ada' : 'Tidak Wajib') }}
                            </span>
                            @if($docUrl)
                                <a href="{{ $docUrl }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bx bx-link-external"></i> Lihat</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted small py-3">Belum ada dokumen pendukung.</div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ====== KOLOM KANAN ====== --}}
    <div class="col-xl-4">
        <div class="vnpi-sticky">

            {{-- Action Hero --}}
            @if($canApprove && $actionApprovals->isNotEmpty())
                <div class="action-hero">
                    <div class="ah-label"><i class="bx bx-shield-quarter"></i> Aksi Verifikasi Anda</div>
                    <div class="small mb-3" style="color:rgba(255,255,255,.85);">Anda memegang <strong>{{ $actionApprovals->count() }}</strong> peran aktif pada NPI ini.</div>
                    @foreach($actionApprovals as $approval)
                        @php
                            $roleName = $approval['role'];
                            $approvalId = $approval['approval_id'];
                            $modalSuffix = Str::slug($roleName) . '_' . $approvalId;
                        @endphp
                        <div class="role-action-box">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold text-white"><i class="bx bx-id-card me-1"></i>{{ $roleName }}</span>
                                <span class="badge bg-warning text-dark">Menunggu</span>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-light btn-sm flex-fill fw-bold text-success" data-bs-toggle="modal" data-bs-target="#modalApproveKontrak{{ $modalSuffix }}"><i class="bx bx-check-circle"></i> Setujui</button>
                                <button type="button" class="btn btn-sm flex-fill fw-bold text-white" style="background:rgba(225,29,72,.85);" data-bs-toggle="modal" data-bs-target="#modalRevisiKontrak{{ $modalSuffix }}"><i class="bx bx-x-circle"></i> Revisi</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="vnpi-card {{ $isNpiFinal ? 'c-green' : ($isRevisi ? 'c-rose' : 'c-slate') }} mb-3">
                    <div class="vnpi-card-body pt-3 text-center">
                        <i class="bx {{ $isNpiFinal ? 'bx-check-shield text-success' : ($isRevisi ? 'bx-error-circle text-danger' : 'bx-hourglass text-secondary') }}" style="font-size:2.5rem;"></i>
                        <h6 class="fw-bold mt-2 mb-1">
                            {{ $isNpiFinal ? 'Verifikasi NPI Selesai' : ($isRevisi ? 'NPI Dikembalikan untuk Revisi' : 'Menunggu Verifikator Lain') }}
                        </h6>
                        <p class="text-muted small mb-0">
                            @if($isNpiFinal) Seluruh verifikator telah menyetujui NPI ini.
                            @elseif($isRevisi) NPI dikembalikan ke meja Bendahara Pengeluaran.
                            @elseif($currentUserApproval?->status === 'APPROVED') Anda telah menyetujui. Menunggu verifikator lain.
                            @else Tidak ada aksi yang menunggu peran Anda saat ini.
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            {{-- Readiness --}}
            <div class="vnpi-card c-amber mb-3">
                <div class="vnpi-card-head">
                    <span class="ico-wrap"><i class="bx bx-task"></i></span>
                    <div><h6>Validasi Kesiapan</h6><span class="head-sub">{{ $readyCount }} dari {{ $totalReady }} terpenuhi</span></div>
                </div>
                <div class="vnpi-card-body">
                    <div class="readiness-progress"><div class="bar" style="width: {{ $readyPct }}%;"></div></div>
                    <ul class="ready-list">
                        @foreach($checklist as $c)
                            <li>
                                <span class="ico {{ $c['status'] === 'ready' ? 'ok' : 'no' }}"><i class="bx {{ $c['status'] === 'ready' ? 'bx-check' : 'bx-x' }}"></i></span>
                                <div><div class="fw-semibold text-dark">{{ $c['label'] }}</div><div class="text-muted small lh-sm">{{ $c['message'] }}</div></div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Vendor --}}
            <div class="vnpi-card c-blue mb-3">
                <div class="vnpi-card-head">
                    <span class="ico-wrap"><i class="bx bx-buildings"></i></span>
                    <div><h6>Vendor & Rekening</h6><span class="head-sub">Penerima pembayaran kontrak</span></div>
                </div>
                <div class="vnpi-card-body">
                    <div class="field-label">Nama Vendor</div>
                    <div class="field-value text-break mb-3">{{ $vendor?->nama_pihak ?? '-' }}</div>
                    <div class="row g-2">
                        <div class="col-6"><span class="field-label">Bank</span><span class="field-value">{{ $rekening?->nama_bank ?? '-' }}</span></div>
                        <div class="col-6"><span class="field-label">No. Rekening</span><span class="field-value font-monospace">{{ $rekening?->nomor_rekening ?? '-' }}</span></div>
                        <div class="col-12"><span class="field-label">Atas Nama</span><span class="field-value">{{ $rekening?->nama_rekening ?? '-' }}</span></div>
                    </div>
                </div>
            </div>

            {{-- Catatan --}}
            <div class="vnpi-card c-purple mb-3">
                <div class="vnpi-card-head">
                    <span class="ico-wrap"><i class="bx bx-message-square-detail"></i></span>
                    <div><h6>Disposisi Internal</h6><span class="head-sub">Uraian dari pembuat NPI</span></div>
                </div>
                <div class="vnpi-card-body">
                    <div class="field-label">Uraian / Teks NPI</div>
                    <div class="bg-light p-2 rounded text-muted small fst-italic border mt-1">"{{ $npi->catatan ?? $kontrakLabel }}"</div>
                </div>
            </div>

            {{-- Log --}}
            <div class="vnpi-card c-slate mb-3">
                <div class="vnpi-card-head">
                    <span class="ico-wrap"><i class="bx bx-history"></i></span>
                    <div><h6>Riwayat Persetujuan</h6><span class="head-sub">Jejak audit NPI</span></div>
                </div>
                <div class="vnpi-card-body" style="max-height: 360px; overflow-y: auto;">
                    @forelse($recentActivities as $act)
                        <div class="vnpi-timeline-item is-active">
                            <div class="fw-bold text-dark small">{{ $act['title'] }}</div>
                            <div class="small my-1"><span class="badge bg-light text-dark border">{{ $act['actor'] }}</span></div>
                            <div class="text-muted" style="font-size:.7rem;">{{ $act['time'] }}</div>
                            @if(!empty($act['note']))
                                <div class="small text-muted fst-italic mt-1 p-2 bg-light rounded">"{{ $act['note'] }}"</div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center text-muted small py-3">Belum ada riwayat tercatat.</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ====== MODALS PER ROLE ====== --}}
@if($canApprove && $actionApprovals->isNotEmpty())
    @foreach($actionApprovals as $approval)
        @php
            $roleName = $approval['role'];
            $approvalId = $approval['approval_id'];
            $approveRouteDynamic = $approval['approveRoute'];
            $revisiRouteDynamic = $approval['revisiRoute'];
            $modalSuffix = Str::slug($roleName) . '_' . $approvalId;
        @endphp

        <div class="modal fade" id="modalApproveKontrak{{ $modalSuffix }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-success text-white border-0">
                        <h5 class="modal-title fw-bold"><i class="bx bx-check-circle me-1"></i> Setujui sebagai {{ $roleName }}?</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ $approveRouteDynamic }}" method="POST">
                        @csrf
                        <input type="hidden" name="approval_id" value="{{ $approvalId }}">
                        <div class="modal-body">
                            <p>Anda akan menyetujui NPI Kontrak <strong>{{ $npi->nomor_npi ?? 'DRAFT' }}</strong> sebagai <strong>{{ $roleName }}</strong>.</p>
                            <div class="mb-2">
                                <label class="field-label">Catatan Persetujuan (Opsional)</label>
                                <textarea name="catatan" class="form-control" rows="2" placeholder="Tulis catatan jika ada..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success px-4 fw-bold">Ya, Setujui</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalRevisiKontrak{{ $modalSuffix }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-danger text-white border-0">
                        <h5 class="modal-title fw-bold"><i class="bx bx-x-circle me-1"></i> Minta Revisi ({{ $roleName }})?</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ $revisiRouteDynamic }}" method="POST">
                        @csrf
                        <input type="hidden" name="approval_id" value="{{ $approvalId }}">
                        <div class="modal-body">
                            <p class="text-muted small">NPI <strong>{{ $npi->nomor_npi ?? 'DRAFT' }}</strong> akan dikembalikan untuk diperbaiki oleh Bendahara Pengeluaran.</p>
                            <div class="mb-2">
                                <label class="field-label">Catatan Revisi <span class="text-danger">*</span></label>
                                <textarea name="catatan_revisi" class="form-control border-danger border-opacity-50" rows="3" required placeholder="Jelaskan detail yang harus diperbaiki..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger px-4 fw-bold">Kembalikan NPI</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endif
@endsection
