@extends('layouts.app')
@section('title', 'Detail Penyetoran Pajak Kontrak')

@php
    $statusClass = match($statusSetor) {
        'Sudah Setor' => 'bg-success',
        'Sudah Billing' => 'bg-warning text-dark',
        default => 'bg-secondary',
    };
@endphp

@push('css')
<style>
    .detail-hero { background: linear-gradient(135deg, #f8f9ff, #eef2ff); border: 1px solid rgba(15,23,42,.08); padding: 1.5rem; margin-bottom: 1.5rem; position: relative; border-radius: .75rem; }
    .detail-hero::before { content: ""; position: absolute; left: 0; top: 0; width: 4px; height: 100%; background: #6366f1; border-radius: .75rem 0 0 .75rem; }
    .info-tile { background: #fff; border: 1px solid rgba(15,23,42,.08); border-radius: .75rem; padding: 1rem; height: 100%; }
    .info-tile .label { color: #6c757d; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; margin-bottom: .35rem; }
    .info-tile .value { color: #212529; font-weight: 700; line-height: 1.35; }
    .section-card { border: 0; border-radius: 1rem; box-shadow: 0 .125rem .25rem rgba(15,23,42,.04); border: 1px solid rgba(15,23,42,.08); overflow: hidden; background: #fff; }
    .section-heading { color: #475569; font-size: .8rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; margin-bottom: 1.2rem; display: flex; align-items: center; gap: .5rem; }
    .info-block { margin-bottom: 1rem; }
    .info-block .label { color: #64748b; font-size: .8rem; margin-bottom: .15rem; }
    .info-block .value { font-weight: 600; color: #1e293b; line-height: 1.4; }
    .checklist-item { display: flex; align-items: flex-start; gap: .85rem; padding: .6rem 0; border-bottom: 1px solid rgba(15,23,42,.04); }
    .checklist-item:last-child { border-bottom: 0; }
    .check-icon { width: 1.5rem; height: 1.5rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-size: .75rem; flex-shrink: 0; }
    .check-ready { background: rgba(25,135,84,.12); color: #198754; }
    .check-missing { background: rgba(220,53,69,.12); color: #dc3545; }
    .action-card { border: 2px solid #6366f1; background: #fcfdff; border-radius: 1rem; box-shadow: 0 .25rem 1rem rgba(99,102,241,.15); }
    .timeline-pipe { position: relative; padding-left: 2rem; }
    .timeline-pipe::before { content: ""; position: absolute; left: .75rem; top: 0; bottom: 0; width: 2px; background: #e2e8f0; }
    .pipe-step { position: relative; padding-bottom: 1.25rem; }
    .pipe-step:last-child { padding-bottom: 0; }
    .pipe-dot { position: absolute; left: -1.55rem; top: .2rem; width: .65rem; height: .65rem; border-radius: 999px; background: #cbd5e1; border: 2px solid #fff; z-index: 1; }
    .pipe-dot.done { background: #10b981; }
    .pipe-dot.active { background: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.25); }
    .pipe-dot.pending { background: #94a3b8; }
    .lampiran-item { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .5rem; padding: .75rem 1rem; }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <x-page-title title="Detail Penyetoran Pajak Kontrak" subtitle="Workspace billing & NTPN potongan pajak kontrak" />
        <a href="{{ route('pajak-potongan.kontrak.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
            <div class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill fs-5"></i><div class="mt-1">{{ session('success') }}</div></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- HEADER --}}
    <div class="detail-hero p-4 rounded-3 shadow-sm">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
            <div class="flex-grow-1">
                <h3 class="fw-bold mb-2 text-dark">{{ $potongan->jenis_potongan }} — {{ $potongan->nama_pajak_snapshot ?? 'Pajak' }}</h3>
                <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
                    <span class="badge {{ $statusClass }} px-3 py-2">{{ $statusSetor }}</span>
                    <span class="badge bg-light text-dark px-3 py-2 border">Kontrak</span>
                    @if($isReadyForPenyetoran)
                        <span class="badge bg-light text-dark px-3 py-2 border"><i class="bi bi-shield-check text-success"></i> Bukti Transfer SP2D Selesai</span>
                    @else
                        <span class="badge bg-danger px-3 py-2"><i class="bi bi-exclamation-triangle"></i> Menunggu Bukti Transfer SP2D</span>
                    @endif
                </div>
                <div class="row g-3">
                    <div class="col-md-3 col-6"><div class="info-tile"><div class="label">Nominal Potongan</div><div class="value text-danger fs-6">Rp {{ number_format($potongan->nominal_potongan, 0, ',', '.') }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="info-tile"><div class="label">DPP</div><div class="value">Rp {{ number_format($potongan->dpp, 0, ',', '.') }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="info-tile"><div class="label">Tarif</div><div class="value">{{ $potongan->persentase_tarif_snapshot ? number_format($potongan->persentase_tarif_snapshot, 2) . '%' : '-' }}</div></div></div>
                    <div class="col-md-3 col-6"><div class="info-tile"><div class="label">Vendor</div><div class="value">{{ $vendorName }}</div></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- KOLOM KIRI --}}
        <div class="col-xl-7">
            {{-- Info Kontrak --}}
            <div class="card section-card mb-4">
                <div class="card-body p-4">
                    <div class="section-heading text-secondary"><i class="bi bi-file-earmark-text"></i> Informasi Kontrak & Tagihan</div>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="info-block"><div class="label">Nomor Kontrak</div><div class="value">{{ $nomorKontrak }}</div></div></div>
                        <div class="col-md-6"><div class="info-block"><div class="label">Vendor</div><div class="value">{{ $vendorName }}</div></div></div>
                        <div class="col-md-6"><div class="info-block"><div class="label">Judul Kontrak</div><div class="value">{{ $judulKontrak }}</div></div></div>
                        <div class="col-md-6"><div class="info-block"><div class="label">Termin</div><div class="value">{{ $terminText }}</div></div></div>
                        <div class="col-12"><div class="info-block"><div class="label">COA</div><div class="value">{{ $coaCode }}@if($coaName)<div class="small text-muted fw-normal">{{ $coaName }}</div>@endif</div></div></div>
                        <div class="col-md-6"><div class="info-block"><div class="label">Nomor Tagihan</div><div class="value">{{ $tagihan?->nomor_tagihan ?? '-' }}</div></div></div>
                        <div class="col-md-6"><div class="info-block"><div class="label">Status Tagihan</div><div class="value"><span class="badge bg-light text-dark border">{{ $tagihan?->status ?? '-' }}</span></div></div></div>
                        <div class="col-md-4"><div class="info-block"><div class="label">Total Bruto</div><div class="value">Rp {{ number_format($tagihan?->total_bruto ?? 0, 0, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="info-block"><div class="label">Total Potongan</div><div class="value text-danger">Rp {{ number_format($tagihan?->total_potongan ?? 0, 0, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="info-block"><div class="label">Total Netto</div><div class="value text-success">Rp {{ number_format($tagihan?->total_netto ?? 0, 0, ',', '.') }}</div></div></div>
                    </div>
                </div>
            </div>

            {{-- Detail Potongan Pajak --}}
            <div class="card section-card mb-4">
                <div class="card-body p-4">
                    <div class="section-heading text-secondary"><i class="bi bi-percent"></i> Detail Potongan Pajak</div>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="info-block"><div class="label">Jenis Potongan</div><div class="value">{{ $potongan->jenis_potongan }}</div></div></div>
                        <div class="col-md-6"><div class="info-block"><div class="label">Nama Pajak (Snapshot)</div><div class="value">{{ $potongan->nama_pajak_snapshot ?? '-' }}</div></div></div>
                        <div class="col-md-4"><div class="info-block"><div class="label">DPP</div><div class="value">Rp {{ number_format($potongan->dpp, 0, ',', '.') }}</div></div></div>
                        <div class="col-md-4"><div class="info-block"><div class="label">Tarif (%)</div><div class="value">{{ $potongan->persentase_tarif_snapshot ? number_format($potongan->persentase_tarif_snapshot, 2) . '%' : '-' }}</div></div></div>
                        <div class="col-md-4"><div class="info-block"><div class="label">Nominal Potongan</div><div class="value text-danger fw-bold">Rp {{ number_format($potongan->nominal_potongan, 0, ',', '.') }}</div></div></div>
                        <div class="col-md-6"><div class="info-block"><div class="label">Akun Potongan (COA)</div><div class="value">{{ $potongan->akunPotongan?->kode_akun ?? '-' }} {{ $potongan->akunPotongan?->nama_akun ?? '' }}</div></div></div>
                        <div class="col-md-3"><div class="info-block"><div class="label">Kode Billing</div><div class="value font-monospace text-primary">{{ $potongan->kode_billing ?? '—' }}</div></div></div>
                        <div class="col-md-3"><div class="info-block"><div class="label">NTPN</div><div class="value font-monospace text-success">{{ $potongan->ntpn ?? '—' }}</div></div></div>
                    </div>
                </div>
            </div>

            {{-- Timeline Dokumen Pencairan --}}
            <div class="card section-card mb-4">
                <div class="card-body p-4">
                    <div class="section-heading text-secondary"><i class="bi bi-diagram-3"></i> Alur Dokumen Pencairan</div>
                    <div class="timeline-pipe">
                        <div class="pipe-step">
                            <div class="pipe-dot done"></div>
                            <div class="fw-semibold text-dark">Tagihan Kontrak</div>
                            <div class="small text-muted">{{ $tagihan?->nomor_tagihan ?? '-' }} &bull; {{ $tagihan?->status ?? '-' }}</div>
                        </div>
                        <div class="pipe-step">
                            <div class="pipe-dot {{ $spp ? 'done' : 'pending' }}"></div>
                            <div class="fw-semibold text-dark">SPP</div>
                            <div class="small text-muted">{{ $spp?->nomor_spp ?? '-' }} &bull; {{ $spp?->status ?? '-' }}</div>
                        </div>
                        <div class="pipe-step">
                            <div class="pipe-dot {{ $spm ? 'done' : 'pending' }}"></div>
                            <div class="fw-semibold text-dark">SPM</div>
                            <div class="small text-muted">{{ $spm?->nomor_spm ?? '-' }} &bull; {{ $spm?->status ?? '-' }}</div>
                        </div>
                        <div class="pipe-step">
                            <div class="pipe-dot {{ $npi ? 'done' : 'pending' }}"></div>
                            <div class="fw-semibold text-dark">NPI</div>
                            <div class="small text-muted">{{ $npi?->nomor_npi ?? '-' }} &bull; {{ $npi?->status ?? '-' }}</div>
                        </div>
                        <div class="pipe-step">
                            <div class="pipe-dot {{ $isSp2dExecuted ? 'done' : ($sp2d ? 'active' : 'pending') }}"></div>
                            <div class="fw-semibold text-dark">SP2D</div>
                            <div class="small text-muted">{{ $sp2d?->nomor_sp2d ?? '-' }} &bull; {{ $sp2d?->status ?? '-' }}</div>
                        </div>
                        <div class="pipe-step">
                            <div class="pipe-dot {{ $potongan->kode_billing ? 'done' : ($isReadyForPenyetoran ? 'active' : 'pending') }}"></div>
                            <div class="fw-semibold text-dark">Input Billing</div>
                            <div class="small text-muted">{{ $potongan->kode_billing ? 'Terisi' : 'Menunggu' }}</div>
                        </div>
                        <div class="pipe-step">
                            <div class="pipe-dot {{ $potongan->ntpn ? 'done' : ($potongan->kode_billing ? 'active' : 'pending') }}"></div>
                            <div class="fw-semibold text-dark">Input NTPN</div>
                            <div class="small text-muted">{{ $potongan->ntpn ? 'Terisi' : 'Menunggu' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Lampiran Terkait --}}
            <div class="card section-card mb-4">
                <div class="card-body p-4">
                    <div class="section-heading text-secondary"><i class="bi bi-paperclip"></i> Lampiran Dokumen Pajak</div>
                    @php $allArsip = $potongan->arsipDokumen ?? collect(); @endphp
                    @forelse($allArsip as $arsip)
                        <div class="lampiran-item mb-2 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold small">{{ $arsip->jenis_dokumen }}</div>
                                <div class="text-muted small">{{ $arsip->nama_file_asli }} &bull; {{ $arsip->uploader?->name ?? '-' }} &bull; {{ $arsip->uploaded_at?->format('d/m/Y H:i') ?? '-' }}</div>
                            </div>
                            <a href="{{ route('arsip-sensitif.download', $arsip->id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
                        </div>
                    @empty
                        <div class="text-muted small text-center py-3"><i class="bi bi-folder2-open d-block fs-3 mb-2 opacity-25"></i>Belum ada lampiran.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN --}}
        <div class="col-xl-5">
            <div class="sticky-top" style="top: 1.5rem; z-index: 1;">

                {{-- Checklist --}}
                <div class="card section-card mb-4">
                    <div class="card-body p-4">
                        <div class="section-heading text-secondary"><i class="bi bi-list-check"></i> Checklist Kesiapan</div>
                        @php
                            $checks = [
                                ['label' => 'Tagihan kontrak valid', 'ok' => (bool) $tagihan],
                                ['label' => 'Potongan pajak valid', 'ok' => $potongan->nominal_potongan > 0],
                                ['label' => 'Bukti transfer SP2D sudah diunggah', 'ok' => $isSp2dExecuted],
                                ['label' => 'Tagihan berstatus SELESAI', 'ok' => $isTagihanSelesai],
                                ['label' => 'Kode Billing', 'ok' => filled($potongan->kode_billing)],
                                ['label' => 'NTPN', 'ok' => filled($potongan->ntpn)],
                                ['label' => 'Bukti Setor (BPN)', 'ok' => (bool) $arsipBpn],
                            ];
                        @endphp
                        @foreach($checks as $c)
                            <div class="checklist-item">
                                <span class="check-icon {{ $c['ok'] ? 'check-ready' : 'check-missing' }}"><i class="bi {{ $c['ok'] ? 'bi-check-lg' : 'bi-x-lg' }}"></i></span>
                                <div>
                                    <div class="fw-semibold text-dark">{{ $c['label'] }}</div>
                                    <div class="text-muted small">{{ $c['ok'] ? 'Terpenuhi' : 'Belum terpenuhi' }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- FORM KODE BILLING --}}
                @if($canInputBilling)
                <div class="card action-card mb-4">
                    <div class="card-header bg-primary text-white p-3 rounded-top-3 border-0">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-credit-card-2-front me-2"></i> Input Kode Billing</h6>
                    </div>
                    <div class="card-body p-4 bg-white rounded-bottom-3">
                        <form action="{{ route('pajak-potongan.kontrak.billing', $potongan->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Kode Billing <span class="text-danger">*</span></label>
                                <input type="text" name="kode_billing" class="form-control font-monospace text-primary fw-bold" value="{{ old('kode_billing', $potongan->kode_billing) }}" placeholder="Masukkan Kode Billing" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">
                                    E-Billing (Cetakan DJP)
                                    <span class="text-danger">{{ $arsipBilling ? '' : '*' }}</span>
                                </label>
                                <input type="file" name="file_billing" class="form-control form-control-sm"
                                       accept=".pdf,.jpg,.jpeg,.png" {{ $arsipBilling ? '' : 'required' }}>
                                <div class="form-text">Unggah file E-Billing (cetakan DJP) untuk Kode Billing di atas. PDF / JPG / PNG, maks 5MB.</div>
                                @if($arsipBilling)
                                    <div class="mt-1 small text-success">
                                        <i class="bi bi-paperclip"></i>
                                        <a href="{{ route('arsip-sensitif.download', $arsipBilling->id) }}" class="text-success fw-semibold">
                                            {{ $arsipBilling->nama_file_asli }}
                                        </a>
                                        <span class="text-muted">— upload baru untuk mengganti.</span>
                                    </div>
                                @endif
                            </div>
                            <button class="btn btn-primary w-100 fw-bold"><i class="bi bi-save me-1"></i> SIMPAN KODE BILLING & E-BILLING</button>
                        </form>
                    </div>
                </div>
                @endif

                {{-- FORM NTPN --}}
                @if($canInputNtpn && !$potongan->ntpn)
                <div class="card action-card mb-4" style="border-color: #10b981;">
                    <div class="card-header bg-success text-white p-3 rounded-top-3 border-0">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-patch-check me-2"></i> Input NTPN (Setelah Setor)</h6>
                    </div>
                    <div class="card-body p-4 bg-white rounded-bottom-3">
                        <form action="{{ route('pajak-potongan.kontrak.ntpn', $potongan->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">NTPN <span class="text-danger">*</span></label>
                                <input type="text" name="ntpn" class="form-control font-monospace text-success fw-bold" value="{{ old('ntpn') }}" placeholder="Masukkan NTPN" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Bukti Penerimaan Negara (BPN) <span class="text-danger">*</span></label>
                                <input type="file" name="file_bukti_setor" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                                <div class="form-text">Wajib. PDF / JPG / PNG, maks 5MB.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">BPPU (Bukti Pemotongan/Pemungutan) <span class="text-muted">(opsional)</span></label>
                                <input type="file" name="file_bppu" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                                <div class="form-text">Lampiran pendukung bukti potong/pungut.</div>
                                @if($arsipBppu)
                                    <div class="mt-1 small text-success"><i class="bi bi-paperclip"></i> {{ $arsipBppu->nama_file_asli }}</div>
                                @endif
                            </div>
                            <button class="btn btn-success w-100 fw-bold" onclick="return confirm('Pastikan data NTPN dan bukti setor sudah benar. Lanjutkan?')"><i class="bi bi-check-circle me-1"></i> SIMPAN NTPN & BUKTI SETOR</button>
                        </form>
                    </div>
                </div>
                @endif

                {{-- STATUS SELESAI --}}
                @if($potongan->ntpn)
                <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-3 p-4 mb-4">
                    <i class="bi bi-check-circle-fill fs-2 text-success opacity-75"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Penyetoran Pajak Selesai</h6>
                        <div class="small">NTPN: <span class="font-monospace fw-bold">{{ $potongan->ntpn }}</span></div>
                        <div class="small">Billing: <span class="font-monospace fw-bold">{{ $potongan->kode_billing }}</span></div>
                        @if($arsipBpn)
                            <div class="small mt-1"><a href="{{ route('arsip-sensitif.download', $arsipBpn->id) }}" class="text-success"><i class="bi bi-file-earmark-pdf"></i> Lihat BPN</a></div>
                        @endif
                        @if($arsipBppu)
                            <div class="small"><a href="{{ route('arsip-sensitif.download', $arsipBppu->id) }}" class="text-success"><i class="bi bi-file-earmark-pdf"></i> Lihat BPPU</a></div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Peringatan bukti transfer belum lengkap --}}
                @if(!$isReadyForPenyetoran)
                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-3 p-4 mb-4">
                    <i class="bi bi-exclamation-triangle-fill fs-2 text-warning opacity-75"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Bukti Transfer SP2D Belum Lengkap</h6>
                        <div class="small">Upload bukti transfer pada menu SP2D terlebih dahulu sampai status SP2D menjadi EXECUTED dan tagihan menjadi SELESAI. Setelah itu Kode Billing dan NTPN dapat diinput.</div>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
@endsection
