@extends('layouts.app')
@section('title', 'Verifikasi SPP Honorarium')

@php
    $dipa = $selectedBudgetItem?->revision?->dipa;
    
    // Status SPP logic
    $statusSppClass = match ($sppModel->status) {
        'Menunggu Verifikasi' => 'bg-info',
        'Disetujui PPK' => 'bg-primary',
        'Revisi' => 'bg-danger',
        'DISETUJUI_SPP', 'SPP_TERBIT' => 'bg-success',
        default => 'bg-secondary',
    };

    $ppkStatusLabel = $ppkApproval->status ?? 'BELUM DIAJUKAN';
    $kasubbagStatusLabel = $kasubbagApproval->status ?? 'BELUM DIAJUKAN';
    $koordinatorStatusLabel = ($koordinatorApproval ?? null)?->status ?? 'BELUM DIAJUKAN';

    $ppkStatusClass = match($ppkStatusLabel) {
        'APPROVED' => 'text-success',
        'PENDING' => 'text-warning',
        'REVISION', 'REJECTED' => 'text-danger',
        default => 'text-muted'
    };

    $kasubbagStatusClass = match($kasubbagStatusLabel) {
        'APPROVED' => 'text-success',
        'PENDING' => 'text-warning',
        'REVISION', 'REJECTED' => 'text-danger',
        default => 'text-muted'
    };

    $koordinatorStatusClass = match($koordinatorStatusLabel) {
        'APPROVED' => 'text-success',
        'PENDING' => 'text-warning',
        'REVISION', 'REJECTED' => 'text-danger',
        default => 'text-muted'
    };
    
    // Overall Progress Step
    $progressStep = 1; // Draft
    if (in_array($sppModel->status, ['Menunggu Verifikasi', 'Revisi'])) {
        $progressStep = 2; // Proses
    } elseif (in_array($sppModel->status, ['Disetujui PPK'])) {
        $progressStep = 3; // Lanjut tahap Kasubbag
    } elseif (in_array($sppModel->status, ['APPROVED', 'DISETUJUI_SPP', 'SPP_TERBIT'])) {
        $progressStep = 4; // Final
    }

    $canApprove = $myApproval && $myApproval->status === 'PENDING';
@endphp

@push('css')
    <style>
        .spp-verify-page {
            --spp-ink: #172033;
            --spp-muted: #667085;
            --spp-line: rgba(15, 23, 42, .09);
            --spp-blue: #2563eb;
            --spp-cyan: #0891b2;
            --spp-green: #059669;
            --spp-red: #dc2626;
            --spp-amber: #d97706;
        }

        @keyframes sppFadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes sppSoftPulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(37, 99, 235, .22); }
            50% { transform: scale(1.03); box-shadow: 0 0 0 8px rgba(37, 99, 235, 0); }
        }

        @keyframes sppProgressFlow {
            from { background-position: 0 0; }
            to { background-position: 36px 0; }
        }

        .spp-anim { animation: sppFadeUp .45s ease both; }
        .spp-delay-1 { animation-delay: .05s; }
        .spp-delay-2 { animation-delay: .1s; }
        .spp-delay-3 { animation-delay: .15s; }

        .spp-workspace-hero {
            background:
                radial-gradient(circle at 88% 18%, rgba(20, 184, 166, .15), transparent 24rem),
                linear-gradient(135deg, #ffffff 0%, #f7fbff 52%, #eef7f5 100%);
            border: 1px solid var(--spp-line);
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        .spp-workspace-hero::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 5px;
            background: linear-gradient(180deg, var(--spp-blue), var(--spp-cyan), var(--spp-green));
        }
        .spp-hero-title {
            color: var(--spp-ink);
            font-size: clamp(1.2rem, 1.8vw, 1.65rem);
            line-height: 1.25;
            max-width: 900px;
        }
        .spp-status-pill {
            border-radius: 999px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .07);
        }
        .spp-summary-tile {
            background: rgba(255, 255, 255, .86);
            border: 1px solid var(--spp-line);
            border-radius: 8px;
            padding: 1rem;
            height: 100%;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }
        .spp-summary-tile:hover {
            transform: translateY(-3px);
            border-color: rgba(37, 99, 235, .22);
            box-shadow: 0 14px 32px rgba(15, 23, 42, .08);
        }
        .spp-summary-tile .label { color: var(--spp-muted); font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; margin-bottom: .35rem; }
        .spp-summary-tile .value { color: var(--spp-ink); font-weight: 800; line-height: 1.35; overflow-wrap: anywhere; }
        .spp-section-card {
            border-radius: 8px;
            border: 1px solid var(--spp-line);
            overflow: hidden;
            background: #fff;
            box-shadow: 0 14px 36px rgba(15, 23, 42, .06);
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .spp-section-card:hover { transform: translateY(-2px); box-shadow: 0 18px 44px rgba(15, 23, 42, .09); }
        .spp-section-heading { color: #475569; font-size: .78rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; margin-bottom: 1.2rem; display: flex; align-items: center; gap: .5rem; }
        .spp-section-heading i { color: var(--spp-blue); font-size: 1rem; }
        .spp-info-block { margin-bottom: 1.2rem; }
        .spp-info-block .label { color: var(--spp-muted); font-size: .78rem; margin-bottom: .25rem; }
        .spp-info-block .value { font-weight: 700; color: var(--spp-ink); line-height: 1.4; }
        .spp-readiness-item { display: flex; align-items: flex-start; gap: .75rem; padding: .58rem .25rem; border-radius: 8px; }
        .spp-readiness-item:hover { background: rgba(37, 99, 235, .045); }
        .spp-readiness-icon { width: 1.55rem; height: 1.55rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-size: .75rem; flex-shrink: 0; }
        .spp-icon-ready { background: rgba(5, 150, 105, .12); color: var(--spp-green); }
        .spp-icon-missing { background: rgba(220, 38, 38, .12); color: var(--spp-red); }
        .spp-activity-row { position: relative; padding-left: 1.35rem; margin-bottom: 1.25rem; }
        .spp-activity-row::before { content: ""; position: absolute; left: 0; top: .35rem; width: .55rem; height: .55rem; border-radius: 999px; background: #cbd5e1; }
        .spp-activity-row::after { content: ""; position: absolute; left: .24rem; top: 1rem; bottom: -1.1rem; width: 1px; background: #e2e8f0; }
        .spp-activity-row:last-child::after { display: none; }
        .spp-activity-active::before { background: var(--spp-blue); animation: sppSoftPulse 2s ease-in-out infinite; }
        
        .timeline-wrapper { display: flex; align-items: center; justify-content: space-between; position: relative; padding: 1.5rem 0; }
        .timeline-line { position: absolute; top: 2.75rem; left: 10%; right: 10%; height: 3px; background: #e2e8f0; z-index: 1; }
        .timeline-line::after { content: ""; position: absolute; inset: 0; width: {{ $progressStep >= 4 ? '100%' : ($progressStep === 3 ? '68%' : ($progressStep === 2 ? '34%' : '8%')) }}; background: repeating-linear-gradient(90deg, var(--spp-blue) 0 18px, var(--spp-cyan) 18px 36px); background-size: 36px 3px; animation: sppProgressFlow 1.2s linear infinite; border-radius: 999px; }
        .timeline-step { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; text-align: center; flex: 1; }
        .timeline-icon { width: 46px; height: 46px; border-radius: 999px; background: #fff; border: 3px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #94a3b8; font-weight: bold; margin-bottom: .75rem; transition: all 0.2s; }
        .timeline-label { font-weight: 600; color: #475569; font-size: .85rem; line-height: 1.3; }
        .timeline-sub { font-size: .75rem; color: #94a3b8; margin-top: .25rem; max-width: 150px; }
        
        .timeline-step.passed .timeline-icon { border-color: var(--spp-green); background: var(--spp-green); color: #fff; }
        .timeline-step.active .timeline-icon { border-color: var(--spp-blue); color: var(--spp-blue); box-shadow: 0 0 0 4px rgba(37, 99, 235, .16); animation: sppSoftPulse 2.2s ease-in-out infinite; }
        .timeline-step.revision .timeline-icon { border-color: var(--spp-red); color: var(--spp-red); background: #fee2e2; }

        .auth-approval-panel {
            border: 1px solid rgba(37, 99, 235, .22);
            border-radius: 8px;
            background: linear-gradient(180deg, #f8fbff, #ffffff);
            padding: 1.5rem;
            position: relative;
            box-shadow: 0 18px 42px rgba(37, 99, 235, .1);
        }
        .auth-approval-panel::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 4px;
            background: linear-gradient(90deg, var(--spp-blue), var(--spp-cyan), var(--spp-green));
        }
        .honor-table thead th { color: #475569; font-size: .72rem; letter-spacing: .04em; text-transform: uppercase; }
        .honor-table tbody tr { transition: background-color .16s ease; }
        .honor-table tbody tr:hover { background: #f8fbff; }
        .spp-doc-row { border: 1px solid var(--spp-line); border-radius: 8px; padding: .9rem; margin-bottom: .65rem; transition: transform .16s ease, border-color .16s ease; }
        .spp-doc-row:hover { transform: translateX(3px); border-color: rgba(37, 99, 235, .24); }
        .spp-doc-row.is-ready { background: linear-gradient(90deg, rgba(5, 150, 105, .055), #fff); border-color: rgba(5, 150, 105, .22); }
        .spp-doc-row.is-system { background: linear-gradient(90deg, rgba(37, 99, 235, .055), #fff); border-color: rgba(37, 99, 235, .2); }

        @media (max-width: 991.98px) {
            .timeline-wrapper { align-items: stretch; flex-direction: column; gap: 1rem; padding: .25rem 0; }
            .timeline-line { left: 22px; right: auto; top: 1rem; bottom: 1rem; width: 3px; height: auto; }
            .timeline-line::after { width: 3px; height: {{ $progressStep >= 4 ? '100%' : ($progressStep === 3 ? '68%' : ($progressStep === 2 ? '34%' : '8%')) }}; background: linear-gradient(180deg, var(--spp-blue), var(--spp-cyan)); animation: none; }
            .timeline-step { flex-direction: row; text-align: left; align-items: flex-start; gap: .9rem; }
            .timeline-icon { margin-bottom: 0; width: 44px; height: 44px; flex: 0 0 44px; }
            .timeline-sub { max-width: none; }
            .sticky-top { position: static !important; }
        }

        @media (prefers-reduced-motion: reduce) {
            .spp-anim, .timeline-step.active .timeline-icon, .spp-activity-active::before, .timeline-line::after { animation: none !important; }
            .spp-summary-tile, .spp-section-card, .spp-doc-row { transition: none !important; }
        }
    </style>
@endpush

@section('content')
<div class="spp-verify-page">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
        <x-page-title title="Verifikasi SPP Honorarium" subtitle="Periksa kesesuaian nilai, relasi anggaran, dan kelengkapan dokumen" />
        <a href="{{ route('verifikasi-spp.honor.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success shadow-sm border-0"><i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}</div>
    @endif

    <!-- A. HEADER INFO -->
    <div class="spp-workspace-hero spp-anim p-4 rounded-3 shadow-sm">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
            <div class="flex-grow-1">
                <h3 class="spp-hero-title fw-bold mb-2">{{ $sppModel->uraian ?? $tagihan->deskripsi }}</h3>
                <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
                    <span class="badge {{ $statusSppClass }} spp-status-pill px-3 py-2">Status SPP: {{ str_replace('_', ' ', $sppModel->status) }}</span>
                    <span class="badge bg-dark spp-status-pill px-3 py-2">Role Anda: {{ $roleCode }}</span>
                    @if($canApprove)
                        <span class="badge bg-warning text-dark spp-status-pill px-3 py-2"><i class="bi bi-exclamation-circle me-1"></i> Menunggu Aksi Anda</span>
                    @endif
                </div>

                <div class="row g-3">
                    <div class="col-md-3 col-6"><div class="spp-summary-tile spp-anim spp-delay-1"><div class="label">Nomor SPP</div><div class="value text-primary">{{ $sppModel->nomor_spp }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="spp-summary-tile spp-anim spp-delay-1"><div class="label">Nomor Tagihan Honorarium</div><div class="value">{{ $tagihan->nomor_tagihan }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="spp-summary-tile spp-anim spp-delay-2"><div class="label">Penerima</div><div class="value">{{ $tagihan->detailHonorarium->count() }} Orang</div></div></div>
                    <div class="col-md-3 col-6"><div class="spp-summary-tile spp-anim spp-delay-3"><div class="label">Nominal SPP (Netto)</div><div class="value text-success fs-6">Rp {{ number_format($sppModel->nominal_spp, 0, ',', '.') }}</div></div></div>
                </div>
            </div>
            
            <div class="d-flex flex-column gap-2" style="min-width: 200px;">
                <a href="{{ route('spps.cetak-pdf', $sppModel->id) }}" target="_blank" class="btn btn-outline-danger shadow-sm"><i class="bi bi-file-earmark-pdf me-1"></i> Lihat Draf PDF SPP</a>
            </div>
        </div>
    </div>


    <div class="row g-4">
        <!-- B. KOLOM KIRI (SUMMARY TAGIHAN & KESEDIAAN) -->
        <div class="col-xl-7">
            
            <div class="card spp-section-card spp-anim mb-4 border-primary border-opacity-25 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-primary mb-4"><i class="bi bi-person-check me-2"></i> Progress Workflow & Persetujuan</h5>
                    
                    <div class="timeline-wrapper pt-0">
                        <div class="timeline-line"></div>
                        
                        <div class="timeline-step passed">
                            <div class="timeline-icon"><i class="bi bi-file-earmark-plus"></i></div>
                            <div class="timeline-label">Draft Dibuat</div>
                            <div class="timeline-sub">Operator BLU<br>{{ $sppModel->dibuatOleh?->name }}</div>
                        </div>

                        <div class="timeline-step {{ $ppkApproval?->status === 'APPROVED' ? 'passed' : ($ppkApproval?->status === 'REVISION' ? 'revision' : ($progressStep >= 2 ? 'active' : '')) }}">
                            <div class="timeline-icon"><i class="bi bi-person-badge"></i></div>
                            <div class="timeline-label">Verifikasi PPK</div>
                            <div class="timeline-sub fw-bold {{ $ppkStatusClass }}">{{ $ppkStatusLabel }}</div>
                            <div class="timeline-sub mt-0" style="font-size:0.7rem">{{ $sppModel->ppkVerifikator?->name }}</div>
                        </div>

                        <div class="timeline-step {{ ($koordinatorApproval ?? null)?->status === 'APPROVED' ? 'passed' : (($koordinatorApproval ?? null)?->status === 'REVISION' ? 'revision' : ($progressStep >= 2 ? 'active' : '')) }}">
                            <div class="timeline-icon"><i class="bi bi-cash-coin"></i></div>
                            <div class="timeline-label">Verifikasi Koord. Keuangan</div>
                            <div class="timeline-sub fw-bold {{ $koordinatorStatusClass }}">{{ $koordinatorStatusLabel }}</div>
                        </div>

                        <div class="timeline-step {{ $kasubbagApproval?->status === 'APPROVED' ? 'passed' : ($kasubbagApproval?->status === 'REVISION' ? 'revision' : ($progressStep >= 2 ? 'active' : '')) }}">
                            <div class="timeline-icon"><i class="bi bi-check-circle"></i></div>
                            <div class="timeline-label">Verifikasi Kasubbag</div>
                            <div class="timeline-sub fw-bold {{ $kasubbagStatusClass }}">{{ $kasubbagStatusLabel }}</div>
                        </div>

                        <div class="timeline-step {{ $progressStep >= 4 ? 'passed' : '' }}">
                            <div class="timeline-icon"><i class="bi bi-check-all"></i></div>
                            <div class="timeline-label">Selesai</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RINCIAN PENERIMA HONOR -->
            <div class="card spp-section-card spp-anim spp-delay-1 mb-4">
                <div class="card-body p-4">
                    <div class="spp-section-heading text-primary"><i class="bi bi-people"></i> Rincian Personel Penerima Honorarium</div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle border mb-0 honor-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-2 text-center">No</th>
                                    <th class="py-2">Personel</th>
                                    <th class="py-2 text-end">Nilai Netto</th>
                                    <th class="py-2">Rekening Penerima</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tagihan->detailHonorarium as $idx => $detail)
                                    @php
                                        $nilaiNetto = $detail->nilai_honor - $detail->pph;
                                        $rekeningValid = filled($detail->rekening) && filled($detail->nama_rekening);
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $detail->nama_personel }}</div>
                                            <div class="text-muted small">NRP/NIP: {{ $detail->nrp_nip ?? '-' }}</div>
                                            <div class="text-muted small">Pangkat: {{ $detail->pangkat_korp ?? '-' }}</div>
                                        </td>
                                        <td class="text-end fw-semibold text-success lh-sm">
                                            Rp {{ number_format($nilaiNetto, 0, ',', '.') }}<br>
                                            <span class="text-muted small font-weight-normal">(Bruto: {{ number_format($detail->nilai_honor, 0, ',', '.') }})</span>
                                        </td>
                                        <td>
                                            @if($rekeningValid)
                                                <div class="fw-bold text-dark">{{ $detail->jenis_bank ?? 'BANK' }} - {{ $detail->rekening }}</div>
                                                <div class="small text-muted">a.n. {{ $detail->nama_rekening }}</div>
                                            @else
                                                <span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> Rekening Kosong/Invalid</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">Belum ada rincian penerima</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ARSIP DOKUMEN -->
            <div class="card spp-section-card spp-anim spp-delay-2 mb-4">
                <div class="card-body p-4">
                    <div class="spp-section-heading text-primary"><i class="bi bi-paperclip"></i> Dokumen Sumber & Lampiran Penunjang</div>

                    @php
                        $skHonorarium = $tagihan->arsipDokumen->firstWhere('jenis_dokumen', 'SK Honorarium');
                        $skPath = $skHonorarium?->path_file ?? $skHonorarium?->file_path ?? null;
                        $documentRows = collect([
                            [
                                'label' => 'SK Honorarium',
                                'badge' => $skPath ? 'Tersedia' : 'Belum Ada',
                                'badge_class' => $skPath ? 'bg-success' : 'bg-danger',
                                'url' => $skPath ? \Illuminate\Support\Facades\Storage::url($skPath) : null,
                                'is_system' => false,
                            ],
                            [
                                'label' => 'Daftar Nominatif',
                                'badge' => 'Dapat Dilihat',
                                'badge_class' => 'bg-primary',
                                'url' => route('honorarium.pdf-nominatif', $tagihan->id),
                                'is_system' => true,
                            ],
                            [
                                'label' => 'Dokumen Honorarium',
                                'badge' => 'Dapat Dilihat',
                                'badge_class' => 'bg-primary',
                                'url' => route('honorarium.pdf', $tagihan->id),
                                'is_system' => true,
                            ],
                        ]);
                    @endphp

                    @foreach($documentRows as $document)
                        <div class="spp-doc-row {{ $document['url'] ? 'is-ready' : '' }} {{ $document['is_system'] ? 'is-system' : '' }} d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 {{ $loop->last ? 'mb-0' : '' }}">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge {{ $document['badge_class'] }}" style="width: 105px;">{{ $document['badge'] }}</span>
                                <div>
                                    <div class="fw-semibold text-dark">
                                        {{ $document['label'] }}
                                        @if($document['is_system'])
                                            <i class="bi bi-patch-check-fill text-primary ms-1" title="Dokumen otomatis dari sistem"></i>
                                        @endif
                                    </div>
                                    @if($document['is_system'])
                                        <div class="text-muted small">Dokumen otomatis dari sistem</div>
                                    @endif
                                </div>
                            </div>
                            <div>
                                @if($document['url'])
                                    <a href="{{ $document['url'] }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3"><i class="bi bi-eye me-1"></i> Lihat</a>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        <!-- C. KOLOM KANAN (ANGGARAN, CHECKLIST & AKSI VERIF) -->
        <div class="col-xl-5">
            <div class="sticky-top" style="top: 1.5rem; z-index: 1;">
                
                @if(!empty($activeRoleApprovals))
                    <!-- PANEL AKSI VERIFIKASI DUAL-ROLE -->
                    @foreach($activeRoleApprovals as $approvalData)
                        <div class="auth-approval-panel spp-anim shadow-sm mb-4">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <i class="bi bi-shield-lock-fill text-primary fs-3"></i>
                                <div>
                                    <h5 class="fw-bold mb-0 text-primary">Tindakan Verifikasi</h5>
                                    <div class="small text-muted">Menunggu persetujuan Anda sebagai <strong>{{ $approvalData['role'] }}</strong></div>
                                </div>
                            </div>

                            <form action="{{ $approvalData['approveRoute'] }}" method="POST" id="formVerifyApprove_{{ Str::slug($approvalData['role']) }}" onsubmit="return confirm('Apakah Anda yakin menyetujui SPP Honorarium ini sebagai {{ $approvalData['role'] }}?');">
                                @csrf
                                <input type="hidden" name="approval_id" value="{{ $approvalData['approval_id'] }}">
                                <label class="form-label fw-semibold">Catatan Keputusan</label>
                                <textarea name="catatan" class="form-control mb-3" rows="2" placeholder="(Opsional) Tulis catatan persetujuan Anda..."></textarea>
                                <button type="submit" class="btn btn-success shadow-sm w-100 mb-2 py-2 fw-bold"><i class="bi bi-check-circle me-1"></i> Setujui sebagai {{ $approvalData['role'] }}</button>
                            </form>

                            <hr class="text-primary opacity-25">

                            <form action="{{ $approvalData['revisiRoute'] }}" method="POST" id="formVerifyReject_{{ Str::slug($approvalData['role']) }}" onsubmit="return confirm('Apakah Anda yakin mengembalikan SPP Honorarium ini untuk revisi sebagai {{ $approvalData['role'] }}?');">
                                @csrf
                                <input type="hidden" name="approval_id" value="{{ $approvalData['approval_id'] }}">
                                <label class="form-label fw-semibold">Alasan Penolakan / Revisi <span class="text-danger">*</span></label>
                                <textarea name="catatan" class="form-control mb-3" rows="2" required placeholder="(Wajib) Tulis instruksi revisi untuk Operator..."></textarea>
                                <button type="submit" class="btn btn-outline-danger w-100 py-2"><i class="bi bi-x-circle me-1"></i> Kembalikan untuk Revisi</button>
                            </form>
                        </div>
                    @endforeach
                @endif
                
                <div class="card spp-section-card spp-anim spp-delay-1 mb-4 bg-light border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-journal-check me-2"></i> Checklist Pemeriksaan Dokumen</h6>
                        @foreach($readinessChecklist as $item)
                            <div class="spp-readiness-item py-1 bg-transparent border-0 mb-1">
                                <span class="spp-readiness-icon {{ $item['status'] === 'ready' ? 'spp-icon-ready' : 'spp-icon-missing' }} shadow-sm"><i class="bi {{ $item['status'] === 'ready' ? 'bi-check2' : 'bi-x-lg' }}"></i></span>
                                <div class="fw-medium text-dark">{{ $item['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card spp-section-card spp-anim spp-delay-2 mb-4">
                    <div class="card-body p-4">
                        <div class="spp-section-heading text-primary"><i class="bi bi-wallet2"></i> Data Anggaran</div>
                        <div class="spp-info-block mb-3"><div class="label">Beban DIPA</div><div class="value">{{ $dipa->nomor_dipa ?? '-' }} <span class="text-muted fw-normal">(Thn: {{ $dipa->tahun_anggaran ?? '-' }})</span></div></div>
                        <div class="p-3 bg-light rounded border border-primary border-opacity-25">
                            <div class="label text-primary fw-bold small mb-1">Akun (COA) / Kode MAK</div>
                            <div class="value fs-5">@if($selectedBudgetItem?->coa) {{ $selectedBudgetItem->coa->kode_mak_lengkap }} @else <span class="text-danger">Belum Tersedia</span> @endif</div>
                            <div class="text-muted small lh-sm mt-1">{{ $selectedBudgetItem?->coa?->nama_akun ?? 'Item COA DIPA tidak dilampirkan.' }}</div>
                        </div>
                    </div>
                </div>

                <div class="card spp-section-card spp-anim spp-delay-3 mb-4">
                    <div class="card-body p-4">
                        <div class="spp-section-heading text-primary"><i class="bi bi-clock-history"></i> Log Pergerakan Dokumen</div>
                        <div class="mt-2">
                            @forelse($sppModel->logs as $idx => $log)
                                <div class="spp-activity-row {{ $idx === 0 ? 'spp-activity-active' : '' }}">
                                    <div class="fw-bold text-dark">{{ $log->status_baru }}</div>
                                    <div class="small text-muted">{{ $log->created_at->format('d M Y, H:i') }} &bull; <span class="fw-medium text-dark">{{ $log->user?->name ?? 'System' }}</span> ({{ $log->role_saat_itu }})</div>
                                    @if(!empty($log->catatan))
                                        <div class="small text-muted mt-1 lh-sm fst-italic">"{{ $log->catatan }}"</div>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center text-muted small py-3">Belum ada historical log status.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
