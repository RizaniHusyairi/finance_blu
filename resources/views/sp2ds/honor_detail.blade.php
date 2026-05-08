@extends('layouts.app')
@section('title', 'Detail SP2D Honorarium')

@php
    $statusMap = [
        \App\Models\DokumenSp2d::STATUS_DRAFT => 'bg-warning text-dark',
        \App\Models\DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI => 'bg-primary',
        \App\Models\DokumenSp2d::STATUS_DISETUJUI_FINAL,
        \App\Models\DokumenSp2d::STATUS_EXECUTED => 'bg-success',
        \App\Models\DokumenSp2d::STATUS_REVISI => 'bg-danger',
    ];
    $statusSp2dClass = $statusMap[$sp2d?->status] ?? 'bg-info text-dark';
    $statusLabel = $sp2d ? str_replace('_', ' ', $sp2d->status) : 'SIAP DIBUAT DRAF';

    $canEdit = is_null($sp2d) || in_array($sp2d->status, [\App\Models\DokumenSp2d::STATUS_DRAFT, \App\Models\DokumenSp2d::STATUS_REVISI]);

    $statusClassMap = [
        'APPROVED' => 'text-success', 'PENDING' => 'text-warning', 'REVISION' => 'text-danger', 'REJECTED' => 'text-danger'
    ];
    $ppkStatusLabel = $ppkApproval?->status ?? 'Belum ada';
    $kasubbagStatusLabel = $kasubbagApproval?->status ?? 'Belum ada';
    $ppspmStatusLabel = $ppspmApproval?->status ?? 'Belum ada';
    $koordinatorStatusLabel = $koordinatorApproval?->status ?? 'Belum ada';
@endphp

@push('css')
    <style>
        .sp2d-hero { background: linear-gradient(135deg, #f0fdf4, #f8fafc); border-bottom: 1px solid rgba(15,23,42,.08); padding-bottom: 1.5rem; margin-bottom: 2rem; position: relative; }
        .sp2d-hero::before { content: ""; position: absolute; left: 0; top: 0; width: 4px; height: 100%; background: #10b981; }
        .info-cell { padding: .75rem 1rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .info-cell .label { color: #64748b; font-size: .8rem; font-weight: 600; width: 40%; }
        .info-cell .value { color: #1e293b; font-weight: 700; width: 60%; text-align: right; }
        .info-cell:last-child { border-bottom: none; }
        .card-custom { border: 0; border-radius: 1rem; box-shadow: 0 .125rem .25rem rgba(15,23,42,.04); border: 1px solid rgba(15,23,42,.08); overflow: hidden; background: #fff; margin-bottom: 1.5rem;}
        
        .progression { display: flex; align-items: center; justify-content: space-between; position: relative; padding: 2rem 0; margin-bottom: 1rem; }
        .progression-line { position: absolute; top: 3.25rem; left: 10%; right: 10%; height: 3px; background: #e2e8f0; z-index: 1; }
        .progression-step { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; text-align: center; flex: 1; min-width: 100px; }
        .progression-icon { width: 44px; height: 44px; border-radius: 999px; background: #fff; border: 3px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #94a3b8; font-weight: bold; margin-bottom: .75rem; transition: all .2s; }
        .progression-label { font-weight: 600; color: #475569; font-size: .85rem; line-height: 1.3; }
        .progression-step.passed .progression-icon { border-color: #10b981; background: #10b981; color: #fff; }
        .progression-step.active .progression-icon { border-color: #3b82f6; color: #3b82f6; box-shadow: 0 0 0 4px rgba(59,130,246,.2); }
        .progression-step.fail .progression-icon { border-color: #ef4444; color: #ef4444; background: #fee2e2; }

        .checker-box { border-radius: .75rem; border: 1px dashed rgba(15,23,42,.15); padding: 1.25rem; }
        .checker-item { display: flex; align-items: flex-start; gap: .75rem; margin-bottom: .8rem; }
        .checker-item:last-child { margin-bottom: 0; }
        .icon-ready { color: #10b981; font-size: 1.1rem; }
        .icon-missing { color: #ef4444; font-size: 1.1rem; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <x-page-title title="Workspace Pencatatan SP2D" subtitle="Honorarium" />
        <a href="{{ route('sp2ds.honor.index') }}" class="btn btn-outline-secondary btn-sm fw-bold"><i class="bi bi-arrow-left me-1"></i> Kembali ke Antrean</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- A. HEADER KERJA --}}
    <div class="sp2d-hero p-4 rounded-3 shadow-sm">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-1 text-dark">{{ $tagihan?->deskripsi ?? 'Pencairan SP2D Honorarium' }}</h4>
                <div class="text-muted small mb-3">Ref Tagihan: {{ $tagihan?->nomor_tagihan ?? '-' }}</div>
                
                <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
                    <span class="badge {{ $statusSp2dClass }} px-3 py-2 border">STATUS SP2D: {{ $statusLabel }}</span>
                    @if($sp2d && in_array($sp2d->status, [\App\Models\DokumenSp2d::STATUS_DRAFT, \App\Models\DokumenSp2d::STATUS_REVISI]))
                        <span class="badge bg-warning text-dark px-3 py-2 border"><i class="bi bi-bell-fill"></i> Draf Tersimpan. Ajukan segera!</span>
                    @endif
                </div>

                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="card-custom h-100 p-3 mb-0">
                            <div class="text-muted small fw-bold text-uppercase">No. SP2D Target</div>
                            <div class="text-success fw-bold">{{ $sp2d?->nomor_sp2d ?? '[ DRAF BARU ]' }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="card-custom h-100 p-3 mb-0 border-info">
                            <div class="text-muted small fw-bold text-uppercase">NPI Tersertifikasi</div>
                            <div class="text-dark fw-bold">{{ $npi->nomor_npi ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="card-custom h-100 p-3 mb-0">
                            <div class="text-muted small fw-bold text-uppercase">Nilai (Netto NPI)</div>
                            <div class="text-dark fw-bold fs-6">Rp {{ number_format($defaultNilai, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="card-custom h-100 p-3 mb-0">
                            <div class="text-muted small fw-bold text-uppercase">Tujuan (Distribusi)</div>
                            <div class="text-dark fw-bold"><i class="bi bi-diagram-3 text-secondary me-1"></i> Ben.Penerimaan</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-column justify-content-center gap-2" style="min-width: 200px;">
                @if($isSP2DFinal)
                 <button class="btn btn-primary shadow-sm"><i class="bi bi-printer me-1"></i> Cetak Dok. SP2D (WIP)</button>
                @endif
            </div>
        </div>
    </div>

    {{-- B. TIMELINE --}}
    <div class="card-custom mb-4 p-4">
        <h6 class="fw-bold text-dark mb-4"><i class="bi bi-bezier2 text-primary me-2"></i> Peta Keutuhan Prosedur SP2D</h6>
        <div class="progression">
            <div class="progression-line"></div>
            <div class="progression-step passed">
                <div class="progression-icon"><i class="bi bi-file-earmark-check"></i></div>
                <div class="progression-label">Integrasi NPI</div>
                <div class="text-muted small">Valid / Setara Final</div>
            </div>
            <div class="progression-step {{ $progressStep >= 1 ? 'active' : '' }} {{ $sp2d && $sp2d->status === \App\Models\DokumenSp2d::STATUS_DRAFT ? 'passed' : '' }}">
                <div class="progression-icon"><i class="bi bi-pencil-square"></i></div>
                <div class="progression-label">Pencatatan Draf</div>
                <div class="text-muted small">Bendahara Pengeluaran</div>
            </div>
            <div class="progression-step {{ $progressStep >= 2 ? ($ppkApproval?->status == 'APPROVED' && $kasubbagApproval?->status == 'APPROVED' && $ppspmApproval?->status == 'APPROVED' && $koordinatorApproval?->status == 'APPROVED' ? 'passed' : ($ppkApproval?->status == 'REVISION' || $kasubbagApproval?->status == 'REVISION' || $ppspmApproval?->status == 'REVISION' || $koordinatorApproval?->status == 'REVISION' ? 'fail' : 'active')) : '' }}">
                <div class="progression-icon"><i class="bi bi-shield-check"></i></div>
                <div class="progression-label">Validasi Keuangan</div>
                <div class="text-muted small">PPK, Kasubbag, PPSPM, Koordinator</div>
            </div>
            <div class="progression-step {{ $progressStep == 3 ? 'passed' : '' }}">
                <div class="progression-icon"><i class="bi bi-check-all"></i></div>
                <div class="progression-label">SP2D Terbit</div>
                <div class="text-success fw-bold small mt-1">Selesai</div>
            </div>
        </div>
    </div>

    @if($sp2d && $workflow)
    <div class="card-custom mb-4 p-4">
        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-person-check text-primary me-2"></i> Status Verifikator SP2D</h6>
        <div class="row g-2">
            @foreach([
                'PPK' => $ppkApproval,
                'Kasubbag' => $kasubbagApproval,
                'PPSPM' => $ppspmApproval,
                'Koordinator Keuangan' => $koordinatorApproval,
            ] as $label => $approval)
                <div class="col-md-3 col-6">
                    <div class="border rounded p-3 h-100 text-center {{ $approval?->status === 'APPROVED' ? 'border-success bg-success bg-opacity-10' : ($approval?->status === 'PENDING' ? 'border-warning bg-warning bg-opacity-10' : (in_array($approval?->status, ['REVISION','REJECTED']) ? 'border-danger bg-danger bg-opacity-10' : 'bg-light')) }}">
                        <div class="fw-bold small">{{ $label }}</div>
                        <div class="text-muted" style="font-size: .72rem;">{{ $approval?->assignedUser?->name ?? 'Verifikator role' }}</div>
                        <span class="badge mt-2 {{ $approval?->status === 'APPROVED' ? 'bg-success' : ($approval?->status === 'PENDING' ? 'bg-warning text-dark' : (in_array($approval?->status, ['REVISION','REJECTED']) ? 'bg-danger' : 'bg-light text-dark border')) }}">
                            {{ $approval?->status ?? 'WAITING' }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="row g-4">
        {{-- C. KIRI (Sumber Data) --}}
        <div class="col-xl-7">
            {{-- Tagihan & Anggaran --}}
            <div class="card-custom p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-folder2-open text-primary me-2"></i> Dokumen Sumber Anggaran</h6>
                <div class="bg-light rounded p-3 mb-3 border">
                    <div class="row">
                        <div class="col-sm-6 mb-2">
                            <div class="small text-muted fw-semibold">No. SPP / SPM</div>
                            <div class="fw-bold text-dark">{{ $spp?->nomor_spp ?? '-' }} <br> <span class="text-muted">{{ $spm?->nomor_spm ?? '-' }}</span></div>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <div class="small text-muted fw-semibold">Beban/COA Honorarium</div>
                            <div class="fw-bold text-dark">{{ $spp?->tagihanHonorarium?->dipaRevisionItem?->coa?->kode ?? '-' }}</div>
                            <div class="small text-muted">{{ $spp?->tagihanHonorarium?->dipaRevisionItem?->coa?->name ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-4">
                        <div class="small fw-semibold text-muted">Nilai Ajuan (Bruto)</div>
                        <div class="fw-bold">Rp {{ number_format($tagihan?->total_bruto ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-4">
                        <div class="small fw-semibold text-danger">Potongan PPh</div>
                        <div class="fw-bold text-danger">Rp {{ number_format($tagihan?->total_potongan ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-4">
                        <div class="small fw-semibold text-success">Netto Ril NPI (SP2D)</div>
                        <div class="fw-bold text-success fs-6">Rp {{ number_format($tagihan?->total_netto ?? 0, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            {{-- Detail Penerima Bank --}}
            <div class="card-custom p-0 overflow-hidden">
                <div class="p-4 border-bottom bg-light d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-people-fill text-primary me-2"></i> Rincian Personel ({{ $rekeningPenerima->count() }} Orang)</h6>
                </div>
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-secondary sticky-top">
                            <tr>
                                <th>Nama & Jabatan</th>
                                <th>Detail Rekening (Transfer)</th>
                                <th class="text-end pe-4">Nilai Transfer Netto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rekeningPenerima as $p)
                            <tr>
                                <td class="ps-3 py-2">
                                    <div class="fw-bold text-dark">{{ $p['nama'] }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $p['jabatan'] }}</div>
                                </td>
                                <td>
                                    @if($p['rekening'] === 'KOSONG')
                                        <span class="badge bg-danger"><i class="bi bi-exclamation-circle text-white"></i> Rekening Kosong</span>
                                    @else
                                        <div class="fw-bold text-primary">{{ $p['bank'] }} - {{ $p['rekening'] }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">A/n {{ $p['nama_rekening'] }}</div>
                                    @endif
                                </td>
                                <td class="text-end pe-4 fw-bold text-success">Rp {{ number_format($p['netto'], 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            {{-- Arsip Dokumen --}}
            <div class="card-custom p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-paperclip text-primary me-2"></i> Dokumen Lampiran NPI / SPP</h6>
                <div class="d-flex flex-wrap gap-2">
                    @forelse($tagihan?->arsipDokumen ?? [] as $arsip)
                        <a href="{{ Storage::url($arsip->file_path) }}" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-3">
                            <i class="bi bi-file-earmark-pdf text-danger me-1"></i> {{ Str::limit($arsip->nama_dokumen, 20) }}
                        </a>
                    @empty
                        <div class="text-muted small">Tidak ada dokumen sisipan.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- D. KANAN (Formulir Draf & Pemeriksaan) --}}
        <div class="col-xl-5">
            <div class="sticky-top" style="top: 1rem; z-index: 1;">
                
                {{-- Panel Ceklis Identifikasi Kesiapan NPI -> SP2D --}}
                <div class="checker-box bg-white shadow-sm mb-4">
                    <h6 class="fw-bold mb-3 text-secondary text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">Indikator Pengaman Data (Checklist)</h6>
                    
                    <div class="checker-item">
                        <i class="bi {{ $checks['npi_final'] ? 'bi-check-circle-fill icon-ready' : 'bi-x-circle-fill icon-missing' }}"></i>
                        <div><div class="fw-semibold small">NPI Disetujui Final</div></div>
                    </div>
                    <div class="checker-item">
                        <i class="bi {{ $checks['tagihan_ada'] && $checks['spp_tersedia'] ? 'bi-check-circle-fill icon-ready' : 'bi-x-circle-fill icon-missing' }}"></i>
                        <div><div class="fw-semibold small">Pohon Relasi Lengkap (SPP-SPM-NPI)</div></div>
                    </div>
                    <div class="checker-item">
                        <i class="bi {{ $checks['rekening_valid'] ? 'bi-check-circle-fill icon-ready' : 'bi-x-circle-fill icon-missing' }}"></i>
                        <div>
                            <div class="fw-semibold small">Validitas Rekening</div>
                            <div class="text-muted" style="font-size: 0.7rem;">(Memastikan tak ada rekening perorangan yg rumpang)</div>
                        </div>
                    </div>
                </div>

                {{-- PANEL RINGKASAN SP2D --}}
                <div class="card-custom shadow">
                    <div class="card-header bg-dark text-white p-3 border-0 rounded-top-3 text-center">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-card-text me-2"></i> PENCATATAN SP2D HONORARIUM</h6>
                    </div>
                    <div class="card-body p-4 bg-white rounded-bottom-3">
                        {{-- Ringkasan Data SP2D --}}
                        <div class="mb-3 info-cell px-0"><span class="label">Nomor SP2D</span><span class="value font-monospace text-primary">{{ $sp2d?->nomor_sp2d ?? '[ BELUM DIISI ]' }}</span></div>
                        <div class="mb-3 info-cell px-0"><span class="label">Tanggal SP2D</span><span class="value">{{ $sp2d?->tanggal_sp2d ? optional($sp2d->tanggal_sp2d)->format('d M Y') : '[ BELUM DIISI ]' }}</span></div>
                        <div class="mb-3 info-cell px-0"><span class="label">Nilai Netto</span><span class="value text-success fw-bold">Rp {{ number_format($defaultNilai, 0, ',', '.') }}</span></div>

                        @if($canEdit)
                            {{-- Tombol Edit Draft --}}
                            <button type="button" class="btn btn-warning fw-bold w-100 shadow-sm border border-warning mb-3" data-bs-toggle="modal" data-bs-target="#modalEditDraftSp2d">
                                <i class="bi bi-pencil-square me-1"></i> Edit Draft SP2D
                            </button>

                            <form action="{{ route('sp2ds.honor.submit', $npi->id) }}" method="POST" onsubmit="return confirm('Pengajuan akan mengunci Draf dan mengoper SP2D ini ke Verifikator (PPK, Kasubbag, PPSPM, dan Koordinator Keuangan). Lanjutkan?');">
                                @csrf
                                <button type="submit" class="btn btn-success fw-bold w-100 shadow py-2" {{ !$checks['sp2d_valid'] ? 'disabled' : '' }}>
                                    <i class="bi bi-send-check-fill me-1"></i> AJUKAN VERIFIKASI SP2D SEKARANG
                                </button>
                                @if(!$checks['sp2d_valid'])
                                 <div class="text-danger small mt-2 text-center text-decoration-underline">Rekam/Simpan Draf-nya terlebih dahulu melalui tombol kuning di atas!</div>
                                @endif
                            </form>
                        @else
                            {{-- State ketika sudah dikunci (Diajukan / Selesai) --}}
                            <div class="alert alert-secondary mt-3 border-0 d-flex gap-3 align-items-center mb-0">
                                <i class="bi bi-lock-fill fs-3 text-secondary opacity-75"></i>
                                <div><div class="fw-bold small">Draf Telah Terkunci (Read-only)</div><div class="text-muted" style="font-size:0.75rem;">Form SP2D sudah dalam lajur persetujuan dan tak dapat dimodifikasi.</div></div>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
@if($canEdit)
{{-- MODAL EDIT DRAFT SP2D --}}
<div class="modal fade" id="modalEditDraftSp2d" tabindex="-1" aria-labelledby="modalEditDraftSp2dLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem; overflow: hidden;">
            <div class="modal-header border-0 text-white p-4" style="background: linear-gradient(135deg, #1e293b, #334155);">
                <div>
                    <h5 class="modal-title fw-bold mb-1" id="modalEditDraftSp2dLabel"><i class="bi bi-pencil-square me-2"></i>Edit Draft SP2D</h5>
                    <div class="small opacity-75">Honorarium &mdash; {{ $tagihan?->deskripsi ?? 'Pencairan SP2D' }}</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('sp2ds.honor.store', $npi->id) }}" method="POST" id="formDraftSp2d">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 py-2 d-flex align-items-center gap-2 mb-4" style="font-size: 0.85rem;">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>Isi data pencatatan SP2D. Setelah disimpan, Anda dapat langsung mengajukan verifikasi.</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Nomor SP2D <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_sp2d" class="form-control font-monospace text-primary border-primary border-opacity-25 bg-light fw-bold" value="{{ old('nomor_sp2d', $sp2d?->nomor_sp2d ?? $autoNomorSp2d) }}" required placeholder="Contoh: 001/SP2D/2026">
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Nomor di atas diturunkan dari SPP, ubah jika perlu.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Tanggal SP2D <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_sp2d" class="form-control" value="{{ old('tanggal_sp2d', $sp2d?->tanggal_sp2d?->format('Y-m-d') ?? date('Y-m-d')) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Catatan Bendahara (Opsional)</label>
                        <textarea name="catatan" class="form-control form-control-sm" rows="3" placeholder="Informasi tambahan atas pencatatan pengeluaran SP2D ini.">{{ old('catatan', '') }}</textarea>
                    </div>

                    <div class="bg-light rounded p-3 border d-flex justify-content-between align-items-center">
                        <div class="small text-muted fw-semibold">Nilai Netto SP2D</div>
                        <div class="fw-bold text-success fs-5">Rp {{ number_format($defaultNilai, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold px-4 shadow-sm border border-warning">
                        <i class="bi bi-save me-1"></i> Simpan Draft SP2D
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection
