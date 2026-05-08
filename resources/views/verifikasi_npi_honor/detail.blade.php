@extends('layouts.app')
@section('title', 'Detail Verifikasi NPI Honorarium')

@php
    $statusMap = [
        \App\Models\DokumenNpi::STATUS_DRAFT => 'bg-warning text-dark',
        \App\Models\DokumenNpi::STATUS_MENUNGGU_VERIFIKASI,
        \App\Models\DokumenNpi::STATUS_SUBMITTED_KASUBAG,
        \App\Models\DokumenNpi::STATUS_SUBMITTED_PPK,
        \App\Models\DokumenNpi::STATUS_SUBMITTED_BENPEN => 'bg-info text-dark',
        \App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL,
        \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG => 'bg-success',
        \App\Models\DokumenNpi::STATUS_REVISI => 'bg-danger',
    ];
    $statusNpiClass = $statusMap[$npi->status] ?? 'bg-secondary';
    
    $statusClassMap = [
        'APPROVED' => 'text-success', 'PENDING' => 'text-warning', 'REVISION' => 'text-danger', 'REJECTED' => 'text-danger'
    ];

    $benpenStatusLabel = $benpenApproval?->status ?? 'Belum diajukan';
    $ppkStatusLabel = $ppkApproval?->status ?? 'Belum diajukan';
    $kasubbagStatusLabel = $kasubbagApproval?->status ?? 'Belum diajukan';
    
    $benpenStatusClass = $statusClassMap[$benpenStatusLabel] ?? 'text-muted';
    $ppkStatusClass = $statusClassMap[$ppkStatusLabel] ?? 'text-muted';
    $kasubbagStatusClass = $statusClassMap[$kasubbagStatusLabel] ?? 'text-muted';

    $isNpiFinal = in_array($npi->status, [\App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL, \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG]);
    $progressStep = $isNpiFinal ? 4 : 3;
@endphp

@push('css')
    <style>
        .npi-workspace-hero { background: linear-gradient(135deg, #f8f9ff, #eef2ff); border-bottom: 1px solid rgba(15,23,42,.08); padding-bottom: 1.5rem; margin-bottom: 2rem; position: relative; }
        .npi-workspace-hero::before { content: ""; position: absolute; left: 0; top: 0; width: 4px; height: 100%; background: #6366f1; }
        .npi-summary-tile { background: #fff; border: 1px solid rgba(15,23,42,.08); border-radius: .75rem; padding: 1rem; height: 100%; }
        .npi-summary-tile .label { color: #6c757d; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; margin-bottom: .35rem; }
        .npi-summary-tile .value { color: #212529; font-weight: 700; line-height: 1.35; }
        .npi-section-card { border: 0; border-radius: 1rem; box-shadow: 0 .125rem .25rem rgba(15,23,42,.04); border: 1px solid rgba(15,23,42,.08); overflow: hidden; background: #fff; }
        .npi-section-heading { color: #475569; font-size: .8rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; margin-bottom: 1.2rem; display: flex; align-items: center; gap: .5rem; }
        .npi-info-block { margin-bottom: 1.2rem; }
        .npi-info-block .label { color: #64748b; font-size: .8rem; margin-bottom: .25rem; }
        .npi-info-block .value { font-weight: 600; color: #1e293b; line-height: 1.4; }
        .npi-readiness-item { display: flex; align-items: flex-start; gap: .85rem; padding: .65rem 0; border-bottom: 1px solid rgba(15,23,42,.04); }
        .npi-readiness-icon { width: 1.5rem; height: 1.5rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-size: .75rem; flex-shrink: 0; }
        .npi-icon-ready { background: rgba(25,135,84,.12); color: #198754; }
        .npi-icon-missing { background: rgba(220,53,69,.12); color: #dc3545; }
        
        .timeline-wrapper { display: flex; align-items: center; justify-content: space-between; position: relative; padding: 2rem 0; margin-bottom: 1rem; }
        .timeline-line { position: absolute; top: 3.25rem; left: 5%; right: 5%; height: 3px; background: #e2e8f0; z-index: 1; }
        .timeline-step { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; text-align: center; flex: 1; min-width: 120px; }
        .timeline-icon { width: 44px; height: 44px; border-radius: 999px; background: #fff; border: 3px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #94a3b8; font-weight: bold; margin-bottom: .75rem; transition: all .2s; }
        .timeline-label { font-weight: 600; color: #475569; font-size: .85rem; line-height: 1.3; }
        .timeline-sub { font-size: .75rem; color: #94a3b8; margin-top: .25rem; max-width: 150px; }
        .timeline-step.passed .timeline-icon { border-color: #10b981; background: #10b981; color: #fff; }
        .timeline-step.active .timeline-icon { border-color: #6366f1; color: #6366f1; box-shadow: 0 0 0 4px rgba(99,102,241,.2); }
        .timeline-step.revision .timeline-icon { border-color: #ef4444; color: #ef4444; background: #fee2e2; }

        .action-card { border: 2px solid #10b981; background: #fcfdff; border-radius: 1rem; box-shadow: 0 .25rem 1rem rgba(16,185,129,.15); }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <x-page-title title="Workspace Verifikasi" subtitle="NPI Honorarium" />
        <a href="{{ route('verifikasi-npi.honor.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali ke Antrean</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
            <div class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill fs-5"></i><div class="mt-1">{{ session('success') }}</div></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
            <div class="d-flex align-items-start gap-2"><i class="bi bi-exclamation-triangle-fill fs-5"></i><div class="mt-1">{{ session('error') }}</div></div>
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
    <div class="npi-workspace-hero p-4 rounded-3 shadow-sm">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
            <div class="flex-grow-1">
                <h3 class="fw-bold mb-2 text-dark">{{ $tagihan?->deskripsi ?? 'NPI Honorarium' }}</h3>
                <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
                    <span class="badge {{ $statusNpiClass }} px-3 py-2">NPI: {{ str_replace('_', ' ', $npi->status) }}</span>
                    @if($canVerify) <span class="badge bg-danger text-light px-3 py-2 border border-danger fw-bold"><i class="bi bi-bell-fill"></i> MENUNGGU AKSI ANDA</span> @endif
                </div>
                <div class="row g-3">
                    <div class="col-md-3 col-6"><div class="npi-summary-tile"><div class="label">Nomor NPI</div><div class="value text-primary">{{ $npi->nomor_npi ?? 'DRAFT' }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="npi-summary-tile"><div class="label">Nomor SPM (Reff)</div><div class="value">{{ $spm->nomor_spm ?? '-' }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="npi-summary-tile"><div class="label">Penerima NPI (BenPen)</div><div class="value"><i class="bi bi-person-check text-success"></i> {{ $npi->bendaharaPenerimaan?->name ?? 'Kosong' }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="npi-summary-tile"><div class="label">Beban Netto Honor</div><div class="value text-success fs-6">Rp {{ number_format($spm->nominal_spm ?? $tagihan?->total_netto ?? 0, 0, ',', '.') }}</div></div></div>
                </div>
            </div>

            <div class="d-flex flex-column justify-content-center gap-2" style="min-width: 200px;">
                @if($isNpiFinal)
                <a href="{{ route('npis.cetak-pdf', $npi->id) }}" target="_blank" class="btn btn-danger shadow-sm"><i class="bi bi-file-earmark-pdf me-1"></i> Cetak / Buka PDF</a>
                @endif
            </div>
        </div>
    </div>

    {{-- B. TIMELINE PROGRESS NPI --}}
    <div class="card npi-section-card mb-4">
        <div class="card-body p-4 pb-2">
            <h5 class="fw-bold text-dark mb-4"><i class="bi bi-alt text-primary me-2"></i> Peta Verifikasi Paralel Mufakat</h5>
            
            <div class="timeline-wrapper">
                <div class="timeline-line"></div>
                <div class="timeline-step passed">
                    <div class="timeline-icon"><i class="bi bi-file-earmark-text"></i></div>
                    <div class="timeline-label">Draft Disubmit</div>
                    <div class="timeline-sub">Ben. Pengeluaran</div>
                </div>
                <div class="timeline-step {{ $benpenApproval?->status === 'APPROVED' ? 'passed' : ($benpenApproval?->status === 'REVISION' ? 'revision' : ($progressStep == 3 ? 'active' : '')) }}">
                    <div class="timeline-icon"><i class="bi bi-person-check"></i></div>
                    <div class="timeline-label">Ben. Penerimaan</div>
                    <div class="timeline-sub fw-semibold {{ $benpenStatusClass }}">{{ $benpenStatusLabel }}</div>
                    @if($benpenApproval) <div class="timeline-sub mt-0 opacity-75" style="font-size: 0.7rem;">{{ $benpenApproval->assignedUser?->name ?? 'Semua BenPen' }}</div> @endif
                </div>
                <div class="timeline-step {{ $ppkApproval?->status === 'APPROVED' ? 'passed' : ($ppkApproval?->status === 'REVISION' ? 'revision' : ($progressStep == 3 ? 'active' : '')) }}">
                    <div class="timeline-icon"><i class="bi bi-person-badge"></i></div>
                    <div class="timeline-label">PPK</div>
                    <div class="timeline-sub fw-semibold {{ $ppkStatusClass }}">{{ $ppkStatusLabel }}</div>
                    @if($ppkApproval) <div class="timeline-sub mt-0 opacity-75" style="font-size: 0.7rem;">{{ $ppkApproval->assignedUser?->name ?? 'Semua PPK' }}</div> @endif
                </div>
                <div class="timeline-step {{ $kasubbagApproval?->status === 'APPROVED' ? 'passed' : ($kasubbagApproval?->status === 'REVISION' ? 'revision' : ($progressStep == 3 ? 'active' : '')) }}">
                    <div class="timeline-icon"><i class="bi bi-building"></i></div>
                    <div class="timeline-label">Kasubbag</div>
                    <div class="timeline-sub fw-semibold {{ $kasubbagStatusClass }}">{{ $kasubbagStatusLabel }}</div>
                    @if($kasubbagApproval) <div class="timeline-sub mt-0 opacity-75" style="font-size: 0.7rem;">{{ $kasubbagApproval->assignedUser?->name ?? 'Semua Kasubbag' }}</div> @endif
                </div>
                <div class="timeline-step {{ $progressStep >= 4 ? 'passed' : '' }}">
                    <div class="timeline-icon"><i class="bi bi-check-all"></i></div>
                    <div class="timeline-label">Final NPI</div>
                    <div class="timeline-sub">Selesai</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- C. KOLOM KIRI (Data & Tabel Relasi) --}}
        <div class="col-xl-7">

            <div class="row g-4 mb-4">
                <div class="col-xl-12">
                    <div class="bg-white p-4 rounded-3 border shadow-sm h-100 border-info border-opacity-25">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-check2-square me-2 text-info"></i> Validasi Sistem Kesiapan NPI</h6>
                        </div>
                        <div style="font-size: 0.95rem;">
                            @foreach($checklist as $c)
                                <div class="npi-readiness-item py-2">
                                    <span class="npi-readiness-icon {{ $c['status'] === 'ready' ? 'npi-icon-ready' : 'npi-icon-missing' }}"><i class="bi {{ $c['status'] === 'ready' ? 'bi-check-lg' : 'bi-x-lg' }}"></i></span>
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $c['label'] }}</div>
                                        <div class="text-muted small lh-sm">{{ $c['message'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="card npi-section-card mb-4">
                <div class="card-body p-4">
                    <div class="npi-section-heading text-secondary"><i class="bi bi-journals"></i> Struktur Tagihan Acuan Honorarium</div>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="npi-info-block"><div class="label">Surat Tagihan & PPK</div><div class="value">{{ $tagihan?->nomor_tagihan ?? '-' }} <br><span class="badge bg-light text-dark fw-normal border mt-1">PPK: {{ $tagihan?->spp?->ppkVerifikator?->name ?? '-' }}</span></div></div></div>
                        <div class="col-md-6"><div class="npi-info-block"><div class="label">Beban Anggaran DIPA</div><div class="value"><span class="fw-bold">{{ $spp?->tagihanHonorarium?->dipaRevisionItem?->coa?->kode ?? '-' }}</span><br><span class="text-muted small">{{ $spp?->tagihanHonorarium?->dipaRevisionItem?->coa?->name ?? 'Belum ada akun Dipa.' }}</span></div></div></div>
                        <div class="col-md-4"><div class="npi-info-block"><div class="label">Nilai Bruto Surat</div><div class="value">Rp {{ number_format($tagihan?->total_bruto ?? 0, 0, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="npi-info-block"><div class="label text-danger">Potongan Pajak PPh</div><div class="value text-danger">Rp {{ number_format($tagihan?->total_potongan ?? 0, 0, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="npi-info-block"><div class="label text-success">Netto Pembayaran NPI</div><div class="value text-success fs-5">Rp {{ number_format($tagihan?->total_netto ?? 0, 0, ',', '.') }}</div></div></div>
                    </div>
                </div>
            </div>

            <div class="card npi-section-card mb-4">
                <div class="card-body p-4">
                    <div class="npi-section-heading text-secondary d-flex justify-content-between align-items-center">
                        <div><i class="bi bi-people"></i> Daftar Pembayaran Rekening Penerima Bank</div>
                        <span class="badge bg-light text-dark border">{{ count($tagihan?->detailHonorarium ?? []) }} Orang</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Personel</th>
                                    <th class="text-end">Bruto</th>
                                    <th class="text-end">PPh</th>
                                    <th class="text-end">Netto</th>
                                    <th>Rekening Pembayaran</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tagihan->detailHonorarium ?? [] as $personel)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $personel->nama_personel }}</div>
                                            <div class="text-muted" style="font-size: 0.75rem;">{{ $personel->jabatan ?? 'Pegawai' }}</div>
                                        </td>
                                        <td class="text-end">Rp {{ number_format($personel->nilai_honor ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end text-danger">Rp {{ number_format($personel->pph ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold text-success">Rp {{ number_format($personel->netto ?? 0, 0, ',', '.') }}</td>
                                        <td>
                                            @if($personel->rekening)
                                                <div class="fw-bold">{{ $personel->jenis_bank ?? 'BANK' }} - <span class="font-monospace fw-normal">{{ $personel->rekening }}</span></div>
                                                <div class="text-muted" style="font-size: 0.75rem;">A/n {{ $personel->nama_rekening }}</div>
                                            @else
                                                <span class="badge bg-danger">Rekening Hilang</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">Belum ada data personel</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Dokumen Pendukung --}}
            <div class="card npi-section-card mb-4">
                <div class="card-body p-4">
                    <div class="npi-section-heading text-secondary"><i class="bi bi-folder-check"></i> Arsip / Dokumen Tersemat</div>
                    <ul class="list-group list-group-flush border rounded">
                        @forelse($tagihan?->arsipDokumen ?? [] as $arsip)
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="fw-semibold text-dark">{{ $arsip->nama_dokumen }}</div>
                                    <div class="text-muted small">File Terlampir Tersedia.</div>
                                </div>
                                <a href="{{ Storage::url($arsip->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-cloud-download"></i> Unduh</a>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted py-4 small">Tidak ada dokumen pelengkap.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

        </div>

        {{-- D. KOLOM KANAN (Aksi & Log) --}}
        <div class="col-xl-5">

                {{-- STATUS NOTICE --}}
                @if($canVerify)
                    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-2 mb-4 py-3 px-4">
                        <span class="spinner-grow spinner-grow-sm text-warning" role="status" aria-hidden="true"></span>
                        <div>
                            <div class="fw-bold text-dark">Menunggu Aksi Anda ({{ $roleCode }})</div>
                            <div class="small text-muted">Gunakan tombol di bawah layar untuk memberikan keputusan.</div>
                        </div>
                    </div>
                @else
                    <div class="alert {{ $isNpiFinal ? 'alert-success' : 'alert-secondary' }} border-0 shadow-sm d-flex align-items-center gap-3 p-4 mb-4">
                        <i class="bi {{ $isNpiFinal ? 'bi-check-all text-success' : 'bi-hourglass-split text-secondary' }} fs-2 opacity-75"></i>
                        <div>
                            <h6 class="fw-bold mb-1">{{ $isNpiFinal ? 'Verifikasi NPI Selesai' : 'Sedang Menunggu Verifikator Lain' }}</h6>
                            <div class="small lh-sm">{{ $isNpiFinal ? 'Dokumen NPI Honorarium ini telah mutlak diresmikan.' : 'Tugas verifikasi per divisi Anda telah diketok, menunggu anggota verifikator lain bertindak.' }}</div>
                        </div>
                    </div>
                @endif
                
                {{-- Log Aktivitas --}}
                <div class="card npi-section-card mb-4 border-0">
                    <div class="card-body p-4 bg-white">
                        <div class="npi-section-heading text-muted"><i class="bi bi-list-task"></i> Log Riwayat Persetujuan</div>
                        <div class="mt-3" style="max-height: 350px; overflow-y: auto;">
                            @forelse($recentLogs as $idx => $log)
                                <div class="position-relative ps-4 mb-3">
                                    <div class="position-absolute top-0 start-0 w-2 h-100 ms-1 bg-light border-start border-2 border-primary"></div>
                                    <div class="position-absolute top-0 start-0 translate-middle mt-1 rounded-circle bg-primary" style="width: 10px; height: 10px; margin-left: 5px;"></div>
                                    <div class="fw-bold text-dark">{{ str_replace('_', ' ', $log->aksi) }}</div>
                                    <div class="small my-1"><span class="badge bg-light text-dark border">{{ $log->role_saat_itu }}</span> Oleh: <span class="fw-semibold text-secondary">{{ $log->user?->name ?? 'Sistem' }}</span></div>
                                    <div class="small text-muted">{{ optional($log->created_at)->format('d F Y H:i:s') }}</div>
                                    @if(!empty($log->catatan))
                                        <div class="small text-muted mt-2 lh-sm p-2 bg-light rounded text-wrap fst-italic">"{{ $log->catatan }}"</div>
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

{{-- MODALS --}}
@if($canVerify)
    {{-- Modal Approve --}}
    <div class="modal fade" id="modalApproveHonor" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-check-circle me-1"></i> Setujui NPI Honorarium?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('verifikasi-npi.honor.approve', $npi->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p>Anda akan menyetujui NPI Honorarium <strong>{{ $npi->nomor_npi ?? 'DRAFT' }}</strong> sebagai <strong>{{ $roleCode }}</strong>.</p>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Catatan Persetujuan (Opsional)</label>
                            <textarea name="catatan" class="form-control" rows="2" placeholder="Sampaikan pesan mufakat NPI..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success px-4 fw-bold">Ya, Setujui NPI</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Revisi --}}
    <div class="modal fade" id="modalRevisiHonor" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-x-circle me-1"></i> Tolak & Minta Revisi?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('verifikasi-npi.honor.reject', $npi->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted small">NPI <strong>{{ $npi->nomor_npi ?? 'DRAFT' }}</strong> akan dikembalikan untuk diperbaiki oleh Bendahara Pengeluaran.</p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Catatan Revisi <span class="text-danger">*</span></label>
                            <textarea name="catatan" class="form-control border-danger border-opacity-50" rows="3" required placeholder="Jelaskan secara detail apa yang harus diperbaiki..."></textarea>
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
@endif

{{-- FIXED BOTTOM ACTION BAR --}}
@if($canVerify)
<div class="npi-fixed-action-bar">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-2 text-white">
                <i class="bi bi-shield-check" style="font-size: 22px;"></i>
                <div>
                    <div class="fw-bold" style="font-size: 0.9rem;">NPI Honorarium: {{ $npi->nomor_npi ?? 'DRAFT' }}</div>
                    <div style="font-size: 0.75rem; opacity: 0.8;">Rp {{ number_format($spm->nominal_spm ?? $tagihan?->total_netto ?? 0, 0, ',', '.') }} &bull; {{ $roleCode }}</div>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light fw-bold px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalRevisiHonor">
                    <i class="bi bi-x-circle me-1"></i> Revisi
                </button>
                <button type="button" class="btn btn-success fw-bold px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalApproveHonor">
                    <i class="bi bi-check-circle me-1"></i> Setujui NPI Honorarium
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('css')
<style>
    .npi-fixed-action-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1050;
        background: linear-gradient(135deg, #1e293b, #334155);
        border-top: 3px solid #10b981;
        padding: 0.85rem 1.5rem;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.2);
        animation: slideUpBar 0.4s ease-out;
    }
    @keyframes slideUpBar {
        from { transform: translateY(100%); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .npi-fixed-action-bar .btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
        border: none;
    }
    .npi-fixed-action-bar .btn-success:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16,185,129,0.4);
    }
    .npi-fixed-action-bar .btn-light:hover {
        background: #fee2e2;
        color: #dc2626;
        border-color: #fca5a5;
    }
    body { padding-bottom: 90px; }
</style>
@endpush
