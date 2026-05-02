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
        .spp-workspace-hero { background: linear-gradient(135deg, #f8f9fc, #f1f5f9); border-bottom: 1px solid rgba(15, 23, 42, 0.08); padding-bottom: 1.5rem; margin-bottom: 2rem; position: relative; }
        .spp-workspace-hero::before { content: ""; position: absolute; left: 0; top: 0; width: 4px; height: 100%; background: #0d6efd; }
        .spp-summary-tile { background: #fff; border: 1px solid rgba(15, 23, 42, 0.08); border-radius: 0.75rem; padding: 1rem; height: 100%; }
        .spp-summary-tile .label { color: #6c757d; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; margin-bottom: .35rem; }
        .spp-summary-tile .value { color: #212529; font-weight: 700; line-height: 1.35; }
        .spp-section-card { border: 0; border-radius: 1rem; box-shadow: 0 0.125rem 0.25rem rgba(15, 23, 42, 0.04); border: 1px solid rgba(15, 23, 42, 0.08); overflow: hidden; background: #fff;}
        .spp-section-heading { color: #475569; font-size: .8rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; margin-bottom: 1.2rem; display: flex; align-items: center; gap: .5rem; }
        .spp-info-block { margin-bottom: 1.2rem; }
        .spp-info-block .label { color: #64748b; font-size: .8rem; margin-bottom: .25rem; }
        .spp-info-block .value { font-weight: 600; color: #1e293b; line-height: 1.4; }
        .spp-readiness-item { display: flex; align-items: flex-start; gap: .85rem; padding: .65rem 0; border-bottom: 1px solid rgba(15, 23, 42, 0.04); }
        .spp-readiness-icon { width: 1.5rem; height: 1.5rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-size: .75rem; flex-shrink: 0; }
        .spp-icon-ready { background: rgba(25, 135, 84, .12); color: #198754; }
        .spp-icon-missing { background: rgba(220, 53, 69, .12); color: #dc3545; }
        .spp-activity-row { position: relative; padding-left: 1.25rem; margin-bottom: 1.25rem; }
        .spp-activity-row::before { content: ""; position: absolute; left: 0; top: .35rem; width: .5rem; height: .5rem; border-radius: 999px; background: #cbd5e1; }
        .spp-activity-active::before { background: #0d6efd; box-shadow: 0 0 0 3px rgba(13, 110, 253, .2); }
        
        .timeline-wrapper { display: flex; align-items: center; justify-content: space-between; position: relative; padding: 1.5rem 0; }
        .timeline-line { position: absolute; top: 2.75rem; left: 10%; right: 10%; height: 3px; background: #e2e8f0; z-index: 1; }
        .timeline-step { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; text-align: center; flex: 1; }
        .timeline-icon { width: 44px; height: 44px; border-radius: 999px; background: #fff; border: 3px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #94a3b8; font-weight: bold; margin-bottom: .75rem; transition: all 0.2s; }
        .timeline-label { font-weight: 600; color: #475569; font-size: .85rem; line-height: 1.3; }
        .timeline-sub { font-size: .75rem; color: #94a3b8; margin-top: .25rem; max-width: 150px; }
        
        .timeline-step.passed .timeline-icon { border-color: #10b981; background: #10b981; color: #fff; }
        .timeline-step.active .timeline-icon { border-color: #3b82f6; color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, .2); }
        .timeline-step.revision .timeline-icon { border-color: #ef4444; color: #ef4444; background: #fee2e2; }

        .auth-approval-panel { border: 2px dashed #0d6efd; border-radius: 12px; background: #f8fbff; padding: 1.5rem; position: relative;}
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
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
    <div class="spp-workspace-hero p-4 rounded-3 shadow-sm">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
            <div class="flex-grow-1">
                <h3 class="fw-bold mb-2 text-dark">{{ $sppModel->uraian ?? $tagihan->deskripsi }}</h3>
                <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
                    <span class="badge {{ $statusSppClass }} px-3 py-2">Status SPP: {{ str_replace('_', ' ', $sppModel->status) }}</span>
                    <span class="badge bg-secondary px-3 py-2">Role Anda: {{ $roleCode }}</span>
                    @if($canApprove)
                        <span class="badge bg-warning text-dark px-3 py-2"><i class="bi bi-exclamation-circle me-1"></i> Menunggu Aksi Anda</span>
                    @endif
                </div>

                <div class="row g-3">
                    <div class="col-md-3 col-6"><div class="spp-summary-tile"><div class="label">Nomor SPP</div><div class="value text-primary">{{ $sppModel->nomor_spp }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="spp-summary-tile"><div class="label">Nomor Tagihan Honorarium</div><div class="value">{{ $tagihan->nomor_tagihan }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="spp-summary-tile"><div class="label">Penerima</div><div class="value">{{ $tagihan->detailHonorarium->count() }} Orang</div></div></div>
                    <div class="col-md-3 col-6"><div class="spp-summary-tile"><div class="label">Nominal SPP (Netto)</div><div class="value text-success fs-6">Rp {{ number_format($sppModel->nominal_spp, 0, ',', '.') }}</div></div></div>
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
            
            <div class="card spp-section-card mb-4 border-primary border-opacity-25 shadow-sm">
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
            <div class="card spp-section-card mb-4">
                <div class="card-body p-4">
                    <div class="spp-section-heading text-primary"><i class="bi bi-people"></i> Rincian Personel Penerima Honorarium</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle border mb-0">
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
            <div class="card spp-section-card mb-4">
                <div class="card-body p-4">
                    <div class="spp-section-heading text-primary"><i class="bi bi-paperclip"></i> Dokumen Sumber & Lampiran Penunjang</div>
                    
                    @foreach(['SK Honorarium', 'Daftar Nominatif Bertandatangan', 'Dokumen Honorarium Bertandatangan'] as $jenis)
                        @php
                            $doc = $tagihan->arsipDokumen->firstWhere('jenis_dokumen', $jenis);
                        @endphp
                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom {{ $loop->last ? 'border-0 pb-0' : '' }}">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge {{ $doc ? 'bg-success' : 'bg-danger' }}" style="width: 85px;">{{ $doc ? 'Tersedia' : 'Belum Ada' }}</span>
                                <div class="fw-semibold text-dark">{{ $jenis }}</div>
                            </div>
                            <div>
                                @if($doc)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($doc->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i> Tinjau</a>
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
                        <div class="auth-approval-panel shadow-sm mb-4">
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
                
                <div class="card spp-section-card mb-4 bg-light border-0 shadow-sm">
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

                <div class="card spp-section-card mb-4">
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

                <div class="card spp-section-card mb-4">
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
@endsection
