@extends('layouts.app')
@section('title', 'Detail Kontrak: ' . Str::limit($kontrak->nama_pekerjaan, 30))

@push('css')
@include('partials.modern-css')
@endpush

@section('content')
@php
    $readyTerms = $kontrak->termin->where('status_termin', 'READY_TO_BILL')->values();
    $ringkasanFinalArsip = $kontrak->ringkasan_kontrak_final_ttd_arsip;
    $spkFinalArsip = $kontrak->spk_final_ttd_arsip;
    $spmkFinalArsip = $kontrak->spmk_final_ttd_arsip;
    $gambarRabArsip = $kontrak->gambar_rab_arsip;
    $selectedCoa = optional($kontrak->dipaRevisionItem)->coa;
    $isContractTteApproved = $kontrak->isTteApproved();
@endphp

@if(session('success'))
    <div class="status-banner status-banner-success mb-3" role="alert">
        <div class="sb-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div><strong>Berhasil.</strong> {{ session('success') }}</div>
    </div>
@endif

@if(session('error'))
    <div class="status-banner status-banner-warning mb-3" role="alert" style="background: rgba(244,63,94,.06); border-color: rgba(244,63,94,.25); color: #991b1b;">
        <div class="sb-icon" style="background: rgba(244,63,94,.18); color: #b91c1c;"><i class="bi bi-x-circle-fill"></i></div>
        <div><strong>Terjadi kesalahan.</strong> {{ session('error') }}</div>
    </div>
@endif

@if($errors->any())
    <div class="status-banner status-banner-warning mb-3" role="alert" style="background: rgba(244,63,94,.06); border-color: rgba(244,63,94,.25); color: #991b1b;">
        <div class="sb-icon" style="background: rgba(244,63,94,.18); color: #b91c1c;"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div>{{ $errors->first() }}</div>
    </div>
@endif

@php
    $statusKontrak = $kontrak->status_kontrak;
    $heroCls = match($statusKontrak) {
        'AKTIF'      => 'hero-aktif',
        'SELESAI'    => 'hero-selesai',
        'DRAFT'      => 'hero-draft',
        'REVISI'     => 'hero-revisi',
        'PENDING_REVIEW' => 'hero-pending',
        default      => 'hero-draft',
    };
    $heroIcon = match($statusKontrak) {
        'AKTIF'      => 'bi-play-circle-fill',
        'SELESAI'    => 'bi-check-circle-fill',
        'DRAFT'      => 'bi-pencil-square',
        'REVISI'     => 'bi-arrow-counterclockwise',
        default      => 'bi-info-circle-fill',
    };
@endphp

{{-- ═══ HERO HEADER ═══ --}}
<div class="kontrak-hero {{ $heroCls }}">
    <i class="bi bi-briefcase-fill briefcase-illust d-none d-md-block"></i>
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div class="flex-grow-1 min-w-0">
            <div class="d-flex gap-2 align-items-center mb-2 flex-wrap">
                <span class="hero-status-pill"><i class="bi {{ $heroIcon }}"></i> {{ str_replace('_',' ',$statusKontrak) }}</span>
                <span class="hero-status-pill" style="opacity:.85;">
                    <i class="bi bi-folder2-open"></i> Kontrak Pengadaan
                </span>
            </div>
            <h2 class="hero-title">{{ $kontrak->nama_pekerjaan }}</h2>
            <p class="hero-meta">
                <i class="bi bi-hash"></i> Nomor SPK <strong>{{ $kontrak->nomor_spk }}</strong>
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-start">
            <a href="{{ route('contracts.index') }}" class="btn-hero">
                <i class="bi bi-arrow-left"></i> Kembali ke Daftar
            </a>
            @if(Auth::user()->hasAnyRole(['Super Admin', 'Pejabat Pengadaan']) && in_array($kontrak->status_kontrak, ['DRAFT', 'REVISI'], true))
                <form action="{{ route('contracts.submit', $kontrak->id) }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="btn-hero btn-hero-primary" onclick="return confirm('Ajukan kontrak ini ke PPK?')">
                        <i class="bi bi-send"></i> Ajukan ke PPK
                    </button>
                </form>
            @endif
            @if($kontrak->status_kontrak === 'AKTIF')
                <button type="button" class="btn-hero btn-hero-primary" data-bs-toggle="modal" data-bs-target="#modalTagihKontrakDetail" {{ $readyTerms->isEmpty() ? 'disabled' : '' }}>
                    <i class="bi bi-cash-stack"></i> Buat Tagihan
                </button>
            @endif
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- KOLOM KIRI: MAIN KONTEN -->
    <div class="col-lg-8 col-xl-9">
        
        {{-- BAGIAN: STATUS TTE --}}
        <div class="modern-card" style="animation: secIn .55s cubic-bezier(.22,1,.36,1) .12s both;">
            <div class="mc-head">
                <h6><i class="bi bi-shield-check mc-h-icon icon-success"></i> Status Persetujuan PPK & TTE Dokumen</h6>
            </div>
            <div class="mc-body">
                <div class="activation-grid">
                    <div class="check-item {{ $isContractTteApproved ? 'is-done' : 'is-pending' }}">
                        <div class="ci-icon">
                            <i class="bi {{ $isContractTteApproved ? 'bi-check-lg' : 'bi-hourglass-split' }}"></i>
                        </div>
                        <div>
                            <div class="ci-label">Ringkasan Kontrak</div>
                            <div class="ci-status">{{ $isContractTteApproved ? 'TTE QR aktif' : 'Menunggu persetujuan PPK' }}</div>
                        </div>
                    </div>
                    <div class="check-item {{ $isContractTteApproved ? 'is-done' : 'is-pending' }}">
                        <div class="ci-icon">
                            <i class="bi {{ $isContractTteApproved ? 'bi-check-lg' : 'bi-hourglass-split' }}"></i>
                        </div>
                        <div>
                            <div class="ci-label">SPK</div>
                            <div class="ci-status">{{ $isContractTteApproved ? 'TTE QR aktif' : 'Menunggu persetujuan PPK' }}</div>
                        </div>
                    </div>
                    <div class="check-item {{ $isContractTteApproved ? 'is-done' : 'is-pending' }}">
                        <div class="ci-icon">
                            <i class="bi {{ $isContractTteApproved ? 'bi-check-lg' : 'bi-hourglass-split' }}"></i>
                        </div>
                        <div>
                            <div class="ci-label">SPMK</div>
                            <div class="ci-status">{{ $isContractTteApproved ? 'TTE QR aktif' : 'Menunggu persetujuan PPK' }}</div>
                        </div>
                    </div>
                </div>
                @if($kontrak->status_kontrak === 'AKTIF')
                    <div class="status-banner status-banner-success">
                        <div class="sb-icon"><i class="bi bi-check-circle-fill"></i></div>
                        <div><strong>Kontrak Aktif.</strong> SPK, SPMK, dan Ringkasan Kontrak akan dicetak dengan TTE QR persetujuan PPK.</div>
                    </div>
                @else
                    <div class="status-banner status-banner-warning">
                        <div class="sb-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                        <div><strong>Belum Aktif.</strong> Kontrak berstatus <strong>{{ $kontrak->status_kontrak }}</strong>. Ajukan ke PPK lalu setelah disetujui dokumen otomatis ber-TTE QR.</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- BAGIAN: PROGRESS SERAPAN --}}
        <div class="progress-card">
            <div class="d-flex justify-content-between align-items-end gap-2 flex-wrap">
                <div>
                    <div class="progress-label"><i class="bi bi-cash-coin"></i> Serapan Dana (Realisasi)</div>
                    <div>
                        <span class="progress-amount">Rp {{ number_format($kontrak->total_terserap, 0, ',', '.') }}</span>
                        <span class="progress-amount-total">/ Rp {{ number_format($kontrak->nilai_total_kontrak, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="progress-percent-pill">
                    <i class="bi bi-graph-up-arrow"></i> {{ number_format($kontrak->persentase_serapan, 1) }}% Tercapai
                </div>
            </div>
            <div class="progress-track">
                <div class="progress-fill" style="width: {{ $kontrak->persentase_serapan }}%"></div>
            </div>
        </div>

        {{-- BAGIAN: SUMMARY GRID --}}
        <div class="summary-grid">
            <div class="summary-card sc-primary">
                <div class="sc-head">
                    <span class="sc-icon"><i class="bi bi-file-earmark-text-fill"></i></span>
                    <div class="sc-label">Identitas Perikatan</div>
                </div>
                <div class="sc-mono mb-1">{{ $kontrak->nomor_spk }}</div>
                <div class="sc-row">Tgl: <strong>{{ \Carbon\Carbon::parse($kontrak->tanggal_spk)->isoFormat('D MMM YYYY') }}</strong></div>
                <div class="sc-row">DIPA: <span class="sc-rek-chip">{{ $kontrak->dipa->nomor_dipa ?? 'N/A' }}</span></div>
            </div>
            <div class="summary-card sc-info">
                <div class="sc-head">
                    <span class="sc-icon"><i class="bi bi-building-fill"></i></span>
                    <div class="sc-label">Vendor & Rekening</div>
                </div>
                <div class="fw-bold text-dark text-truncate" title="{{ $kontrak->vendor->nama_pihak ?? $kontrak->vendor->nama_perusahaan ?? '-' }}">{{ $kontrak->vendor->nama_pihak ?? $kontrak->vendor->nama_perusahaan ?? '-' }}</div>
                <div class="sc-row">NPWP: <strong>{{ $kontrak->vendor->npwp ?? '-' }}</strong></div>
                @php $rek = $kontrak->vendor->rekening->first(); @endphp
                <div class="sc-rek-chip text-truncate" style="max-width: 100%;">{{ $rek ? $rek->nama_bank . ' · ' . $rek->nomor_rekening : 'Belum Ada Rekening' }}</div>
            </div>
            <div class="summary-card sc-success">
                <div class="sc-head">
                    <span class="sc-icon"><i class="bi bi-cash-stack"></i></span>
                    <div class="sc-label">Nilai & Skema</div>
                </div>
                <div class="sc-money mb-1">Rp {{ number_format($kontrak->nilai_total_kontrak, 0, ',', '.') }}</div>
                <div class="sc-row">Metode: <strong>{{ $kontrak->metode_pembayaran }}</strong></div>
                <div class="sc-row">Uang Muka:
                    @if($kontrak->ada_uang_muka)
                        <strong>Rp {{ number_format($kontrak->nilai_uang_muka, 0, ',', '.') }}</strong>
                    @else
                        <span class="text-muted">Tidak ada</span>
                    @endif
                </div>
            </div>
            <div class="summary-card sc-warning">
                <div class="sc-head">
                    <span class="sc-icon"><i class="bi bi-calendar-range-fill"></i></span>
                    <div class="sc-label">Garis Waktu</div>
                </div>
                <div class="fw-bold text-dark mb-1">{{ $kontrak->jangka_waktu }} {{ ucfirst(strtolower($kontrak->satuan_waktu)) }}</div>
                <div class="sc-row">Mulai: <strong>{{ \Carbon\Carbon::parse($kontrak->tanggal_mulai)->isoFormat('D MMM YYYY') }}</strong></div>
                <div class="sc-row" style="color:#b91c1c;">Selesai: <strong>{{ \Carbon\Carbon::parse($kontrak->tanggal_selesai)->isoFormat('D MMM YYYY') }}</strong></div>
            </div>
        </div>

        <div class="modern-card" style="animation: secIn .55s cubic-bezier(.22,1,.36,1) .50s both;">
            <div class="mc-head">
                <h6><i class="bi bi-file-earmark-medical mc-h-icon"></i> Metadata Dokumen Kontrak</h6>
                <span class="termin-pill tp-locked">Data Sumber Dokumen</span>
            </div>
            <div class="mc-body">
                
                <div class="row g-3 mb-4 bg-light p-3 rounded-4 border">
                    <div class="col-md-6">
                        <div class="small text-muted text-uppercase fw-bold"><i class="bi bi-file-text me-1"></i> Identitas SPK</div>
                        <div class="fw-bold text-primary mt-1">{{ $kontrak->nomor_spk ?: '-' }}</div>
                        <div class="small text-muted">Tgl: {{ $kontrak->tanggal_spk ? \Carbon\Carbon::parse($kontrak->tanggal_spk)->translatedFormat('d M Y') : '-' }}</div>
                    </div>
                    <div class="col-md-6 border-start-md">
                        <div class="small text-muted text-uppercase fw-bold"><i class="bi bi-file-text me-1"></i> Identitas SPMK</div>
                        <div class="fw-bold text-info mt-1">{{ $kontrak->nomor_spmk ?: '-' }}</div>
                        <div class="small text-muted">Tgl: {{ $kontrak->tanggal_spmk ? \Carbon\Carbon::parse($kontrak->tanggal_spmk)->translatedFormat('d M Y') : '-' }}</div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="small text-muted">Nama PPK</div>
                        <div class="fw-bold">{{ $kontrak->nama_ppk ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">NIP PPK</div>
                        <div class="fw-bold">{{ $kontrak->nip_ppk ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Nomor Surat Undangan Pengadaan Langsung</div>
                        <div class="fw-bold">{{ $kontrak->nomor_surat_undangan_pengadaan ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Nomor BA Hasil Pengadaan Langsung</div>
                        <div class="fw-bold">{{ $kontrak->nomor_ba_hasil_pengadaan ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Nama Penandatangan Vendor</div>
                        <div class="fw-bold">{{ $kontrak->vendor->nama_penanggung_jawab ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Jabatan Penandatangan Vendor</div>
                        <div class="fw-bold">{{ $kontrak->vendor->jabatan_penandatangan ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Vendor / Mitra</div>
                        <div class="fw-bold">{{ $kontrak->vendor->nama_pihak ?? ($kontrak->vendor->nama_perusahaan ?? '-') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Masa Pemeliharaan</div>
                        <div class="fw-bold">
                            {{ (int) ($kontrak->masa_pemeliharaan_hari ?? 0) > 0 ? number_format((int) $kontrak->masa_pemeliharaan_hari, 0, ',', '.') . ' hari kalender' : '-' }}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Periode Pemeliharaan</div>
                        <div class="fw-bold">
                            @if($kontrak->tanggal_mulai_pemeliharaan && $kontrak->tanggal_selesai_pemeliharaan)
                                {{ \Carbon\Carbon::parse($kontrak->tanggal_mulai_pemeliharaan)->translatedFormat('d M Y') }}
                                s.d.
                                {{ \Carbon\Carbon::parse($kontrak->tanggal_selesai_pemeliharaan)->translatedFormat('d M Y') }}
                            @else
                                -
                            @endif
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="small text-muted">Ketentuan Denda</div>
                        <div class="fw-bold">{{ $kontrak->ketentuan_denda ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modern-card" style="animation: secIn .55s cubic-bezier(.22,1,.36,1) .56s both;">
            <div class="mc-head">
                <div>
                    <h6><i class="bi bi-journal-richtext mc-h-icon icon-secondary"></i> Dokumen Ringkasan Kontrak</h6>
                    <small class="text-muted d-block mt-1">Ringkasan Kontrak dicetak langsung dari data kontrak. TTE QR muncul otomatis setelah PPK menyetujui kontrak.</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('contracts.ringkasan.export-pdf', $kontrak->id) }}" target="_blank" class="btn-act-modern btn-act-pdf">
                        <i class="bi bi-filetype-pdf"></i> {{ $isContractTteApproved ? 'Export PDF TTE' : 'Export PDF Draft' }}
                    </a>
                    @if($ringkasanFinalArsip)
                        <a href="{{ Storage::url($ringkasanFinalArsip->path_file) }}" target="_blank" class="btn-act-modern btn-act-success">
                            <i class="bi bi-file-earmark-check"></i> Lihat Dokumen Final
                        </a>
                    @endif
                </div>
            </div>
            <div class="mc-body">

                <div class="row g-3 align-items-stretch">
                    <div class="col-md-4">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-check2-circle me-1"></i> Status Dokumen Final</div>
                            @if($isContractTteApproved)
                                <span class="badge-doc badge-doc-success"><i class="bi bi-check-circle-fill"></i> TTE QR Aktif</span>
                                <div class="dsc-meta mt-2">Disetujui PPK: {{ optional($kontrak->ppk_approved_at)->translatedFormat('d M Y H:i') }}</div>
                            @else
                                <span class="badge-doc badge-doc-danger"><i class="bi bi-hourglass-split"></i> Menunggu PPK</span>
                                <div class="dsc-meta mt-2">PDF dapat diexport sebagai draft sebelum persetujuan PPK.</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-hash me-1"></i> Identitas Ringkasan</div>
                            <div class="dsc-value">{{ $kontrak->nomor_spk ?? '-' }}</div>
                            <div class="dsc-meta">{{ $kontrak->tanggal_spk ? \Carbon\Carbon::parse($kontrak->tanggal_spk)->translatedFormat('d M Y') : '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-shield-check me-1"></i> Masa Pemeliharaan</div>
                            <div class="dsc-value">{{ (int) ($kontrak->masa_pemeliharaan_hari ?? 0) > 0 ? number_format((int) $kontrak->masa_pemeliharaan_hari, 0, ',', '.') . ' hari kalender' : '-' }}</div>
                            <div class="dsc-meta">{{ $selectedCoa->kode_mak_lengkap ?? 'COA belum terhubung' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modern-card" style="animation: secIn .55s cubic-bezier(.22,1,.36,1) .62s both;">
            <div class="mc-head">
                <div>
                    <h6><i class="bi bi-file-earmark-check mc-h-icon icon-success"></i> Dokumen SPK</h6>
                    <small class="text-muted d-block mt-1">SPK dicetak langsung dari data kontrak. TTE QR muncul otomatis setelah PPK menyetujui kontrak.</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if($gambarRabArsip)
                        <a href="{{ route('contracts.spk.export-pdf', $kontrak->id) }}" target="_blank" class="btn-act-modern btn-act-pdf">
                            <i class="bi bi-filetype-pdf"></i> {{ $isContractTteApproved ? 'Export PDF TTE' : 'Export PDF Draft' }}
                        </a>
                    @else
                        <button type="button" class="btn-act-modern btn-act-pdf" disabled style="opacity:.5; cursor:not-allowed;" title="Upload Gambar RAB terlebih dahulu">
                            <i class="bi bi-filetype-pdf"></i> {{ $isContractTteApproved ? 'Export PDF TTE' : 'Export PDF Draft' }}
                        </button>
                    @endif
                    @if($gambarRabArsip)
                        <a href="{{ route('contracts.spk.gambar-rab', $kontrak->id) }}" target="_blank" class="btn-act-modern btn-act-success">
                            <i class="bi bi-image"></i> Lihat RAB
                        </a>
                    @endif
                    @if($spkFinalArsip)
                        <a href="{{ Storage::url($spkFinalArsip->path_file) }}" target="_blank" class="btn-act-modern btn-act-success">
                            <i class="bi bi-file-earmark-check"></i> Lihat Dokumen Final
                        </a>
                    @endif
                </div>
            </div>
            <div class="mc-body">

                <div class="row g-3 align-items-stretch">
                    <div class="col-md-6 col-lg-3">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-check2-circle me-1"></i> Status SPK Final</div>
                            @if($isContractTteApproved)
                                <span class="badge-doc badge-doc-success"><i class="bi bi-check-circle-fill"></i> TTE QR Aktif</span>
                                <div class="dsc-meta mt-2">Disetujui PPK: {{ optional($kontrak->ppk_approved_at)->translatedFormat('d M Y H:i') }}</div>
                            @else
                                <span class="badge-doc badge-doc-danger"><i class="bi bi-hourglass-split"></i> Menunggu PPK</span>
                                <div class="dsc-meta mt-2">PDF dapat diexport sebagai draft sebelum persetujuan PPK.</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-image-alt me-1"></i> Gambar RAB</div>
                            @if($gambarRabArsip)
                                <span class="badge-doc badge-doc-success"><i class="bi bi-check-circle-fill"></i> Tersedia</span>
                                <div class="dsc-meta mt-2">{{ optional($gambarRabArsip->uploaded_at)->translatedFormat('d M Y H:i') ?? optional($gambarRabArsip->created_at)->translatedFormat('d M Y H:i') }}</div>
                            @else
                                <span class="badge-doc badge-doc-danger"><i class="bi bi-x-circle-fill"></i> Wajib Upload</span>
                                <div class="dsc-meta mt-2">Upload gambar RAB sebelum export PDF Draft SPK.</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-bank me-1"></i> DIPA / Revisi Aktif</div>
                            <div class="dsc-value">{{ $kontrak->dipa->nomor_dipa ?? '-' }}</div>
                            <div class="dsc-meta">TA {{ $kontrak->dipa->tahun_anggaran ?? '-' }} · Revisi {{ optional($kontrak->dipa->activeRevision)->nomor_revisi ?? $kontrak->dipa->revisi_aktif_ke ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-tag-fill me-1"></i> Item Anggaran / COA</div>
                            <div class="dsc-value">{{ $selectedCoa->kode_mak_lengkap ?? '-' }}</div>
                            <div class="dsc-meta">{{ $selectedCoa->nama_akun ?? 'Item anggaran belum terhubung' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modern-card" style="animation: secIn .55s cubic-bezier(.22,1,.36,1) .68s both;">
            <div class="mc-head">
                <div>
                    <h6><i class="bi bi-file-earmark-text mc-h-icon icon-info"></i> Dokumen SPMK</h6>
                    <small class="text-muted d-block mt-1">SPMK dicetak langsung dari data kontrak. TTE QR muncul otomatis setelah PPK menyetujui kontrak.</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('contracts.spmk.export-pdf', $kontrak->id) }}" target="_blank" class="btn-act-modern btn-act-pdf">
                        <i class="bi bi-filetype-pdf"></i> {{ $isContractTteApproved ? 'Export PDF TTE' : 'Export PDF Draft' }}
                    </a>
                    @if($spmkFinalArsip)
                        <a href="{{ Storage::url($spmkFinalArsip->path_file) }}" target="_blank" class="btn-act-modern btn-act-success">
                            <i class="bi bi-file-earmark-check"></i> Lihat Dokumen Final
                        </a>
                    @endif
                </div>
            </div>
            <div class="mc-body">

                <div class="row g-3 align-items-stretch">
                    <div class="col-md-4">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-check2-circle me-1"></i> Status SPMK Final</div>
                            @if($isContractTteApproved)
                                <span class="badge-doc badge-doc-success"><i class="bi bi-check-circle-fill"></i> TTE QR Aktif</span>
                                <div class="dsc-meta mt-2">Disetujui PPK: {{ optional($kontrak->ppk_approved_at)->translatedFormat('d M Y H:i') }}</div>
                            @else
                                <span class="badge-doc badge-doc-danger"><i class="bi bi-hourglass-split"></i> Menunggu PPK</span>
                                <div class="dsc-meta mt-2">PDF dapat diexport sebagai draft sebelum persetujuan PPK.</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-hash me-1"></i> Identitas SPMK</div>
                            <div class="dsc-value">{{ $kontrak->nomor_spmk ?? '-' }}</div>
                            <div class="dsc-meta">{{ $kontrak->tanggal_spmk ? \Carbon\Carbon::parse($kontrak->tanggal_spmk)->translatedFormat('d M Y') : '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="doc-status-card">
                            <div class="dsc-label"><i class="bi bi-person-badge me-1"></i> Penandatangan Vendor</div>
                            <div class="dsc-value">{{ $kontrak->vendor->nama_penanggung_jawab ?? '-' }}</div>
                            <div class="dsc-meta">{{ $kontrak->vendor->jabatan_penandatangan ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ BAGIAN: KIRIM AKSES VENDOR (Portal Upload TTD Basah) ═══ --}}
        @if($isContractTteApproved)
            @php
                $vendorNoHp = $kontrak->vendor->no_telepon ?? null;
            @endphp
            <div class="modern-card" style="animation: secIn .55s cubic-bezier(.22,1,.36,1) .72s both;">
                <div class="mc-head">
                    <div>
                        <h6><i class="bi bi-whatsapp mc-h-icon" style="color:#25D366;"></i> Kirim Akses Upload ke Vendor</h6>
                        <small class="text-muted d-block mt-1">
                            Kirim link portal publik ke WhatsApp vendor untuk mengunggah <strong>SPK</strong>, <strong>SPMK</strong>, dan <strong>Ringkasan Kontrak</strong> yang sudah ditandatangani (TTD basah).
                        </small>
                    </div>
                </div>
                <div class="mc-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <div class="doc-status-card">
                                <div class="dsc-label"><i class="bi bi-telephone me-1"></i> Nomor WhatsApp Vendor</div>
                                @if($vendorNoHp)
                                    <div class="dsc-value">{{ $vendorNoHp }}</div>
                                    <div class="dsc-meta">{{ $kontrak->vendor->nama_pihak ?? '-' }}</div>
                                @else
                                    <span class="badge-doc badge-doc-danger"><i class="bi bi-exclamation-triangle-fill"></i> Nomor Belum Diisi</span>
                                    <div class="dsc-meta mt-2">Lengkapi nomor telepon vendor di Master Pihak terlebih dahulu.</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <form action="{{ route('contracts.send-wa-vendor', $kontrak->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit"
                                        class="btn-act-modern"
                                        style="{{ $vendorNoHp ? 'background-color:#25D366; color:white;' : 'background-color:#9ca3af; color:white; opacity:.6; cursor:not-allowed;' }} border:none; padding:.65rem 1.25rem; font-weight:600;"
                                        onclick="return confirm('Kirim link portal upload ke WhatsApp vendor?')"
                                        @disabled(!$vendorNoHp)>
                                    <i class="bi bi-whatsapp"></i> Kirim Akses Vendor
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ═══ BAGIAN: TABS DETAIL ═══ --}}
        <div class="modern-card" style="animation: secIn .55s cubic-bezier(.22,1,.36,1) .74s both;">
            <div class="tabs-bar" id="detailTabs">
                <button class="tab-btn" data-tab="dokumen">
                    <i class="bi bi-folder2-open"></i> Dokumen Awal & Jaminan
                </button>
                <button class="tab-btn active" data-tab="termin">
                    <i class="bi bi-list-check"></i> Skema Termin & Tagihan
                </button>
                <button class="tab-btn" data-tab="addendum">
                    <i class="bi bi-journal-text"></i> Riwayat Addendum
                </button>
            </div>
            <div class="mc-body">

                {{-- TAB DOKUMEN AWAL --}}
                <div class="tab-pane-c" data-pane="dokumen">
                    <div class="hint-banner">
                        <i class="bi bi-info-circle-fill" style="color:#0ea5e9 !important;"></i>
                        <div>Tab ini berisi salinan dokumen jaminan atau dokumen awal pendukung lainnya (sebelum kontrak aktif). Untuk Ringkasan Kontrak, SPK dan SPMK dikelola di area kartu atas.</div>
                    </div>
                    <h6 class="fw-bold mb-3 text-dark">Arsip Dokumen Pendukung & Jaminan</h6>
                    <div class="list-group mb-2">
                        @if($kontrak->file_jaminan_uang_muka)
                        <a href="{{ Storage::url($kontrak->file_jaminan_uang_muka) }}" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center border rounded-3 mb-2 shadow-sm">
                            <div><i class="bi bi-file-earmark-pdf-fill text-danger fs-4 me-2 align-middle"></i> <span class="fw-bold">Jaminan Uang Muka</span></div>
                            <span class="btn-act-modern btn-act-primary"><i class="bi bi-download"></i> Unduh</span>
                        </a>
                        @else
                        <div class="empty-cell-state">
                            <i class="bi bi-folder-x"></i>
                            <h6 class="text-secondary fw-bold mb-1">Tidak ada dokumen jaminan</h6>
                            <small>Belum ada dokumen jaminan/awal yang diunggah.</small>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- TAB TERMIN & TAGIHAN --}}
                <div class="tab-pane-c active" data-pane="termin">
                    <div class="hint-banner">
                        <i class="bi bi-lightbulb-fill"></i>
                        <div><strong>Catatan Skema Termin:</strong> Hanya termin berstatus <span class="badge-mini bm-ready">READY_TO_BILL</span> yang dapat dibuat menjadi tagihan. Termin <span class="badge-mini bm-locked">LOCKED</span> belum bisa diproses, dan termin <span class="badge-mini bm-locked">DRAFT</span> atau <span class="badge-mini bm-billed">SUDAH_DITAGIH</span> telah terikat pada pengajuan tagihan di sistem.</div>
                    </div>
                    <div class="table-responsive">
                        <table class="termin-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:90px;">Termin</th>
                                    <th>Keterangan</th>
                                    <th class="text-center" style="width:130px;">Persentase</th>
                                    <th>Nilai Bruto</th>
                                    <th class="text-center" style="width:130px;">Status</th>
                                    <th class="text-center" style="width:200px;">Aksi Tagihan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kontrak->termin as $termin)
                                <tr>
                                    <td class="text-center"><span class="termin-num">{{ $termin->termin_ke }}</span></td>
                                    <td>
                                        <p class="termin-keterangan">{{ $termin->keterangan_termin }}</p>
                                        <span class="termin-jenis-pill"><i class="bi bi-tag-fill"></i> {{ str_replace('_', ' ', $termin->jenis_termin) }}</span>
                                    </td>
                                    <td class="text-center"><span class="termin-percent-chip">{{ $termin->persentase }}%</span></td>
                                    <td><span class="termin-money">Rp {{ number_format($termin->nilai_bruto_termin, 0, ',', '.') }}</span></td>
                                    <td class="text-center">
                                        @if($termin->status_termin == 'LOCKED')
                                            <span class="termin-pill tp-locked"><i class="bi bi-lock-fill"></i> Locked</span>
                                        @elseif($termin->status_termin == 'READY_TO_BILL')
                                            <span class="termin-pill tp-ready"><i class="bi bi-bell-fill"></i> Ready</span>
                                        @elseif($termin->status_termin == 'DRAFT')
                                            <span class="termin-pill tp-draft"><i class="bi bi-file-earmark-text"></i> Draft</span>
                                        @elseif($termin->status_termin == 'SUDAH_DITAGIH')
                                            <span class="termin-pill tp-billed"><i class="bi bi-check-circle-fill"></i> Ditagih</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $isDocsComplete = $kontrak->hasVendorUploadedFinalDocs();
                                        @endphp
                                        @if($kontrak->status_kontrak === 'AKTIF' && $termin->status_termin === 'READY_TO_BILL')
                                            @if($isDocsComplete)
                                                <button type="button" class="btn-act-modern btn-act-success" title="Buat Tagihan" data-bs-toggle="modal" data-bs-target="#modalTagihTermin{{ $termin->id }}">
                                                    <i class="bi bi-cash-stack"></i> Buat Tagihan
                                                </button>
                                            @else
                                                <button type="button" class="btn-act-modern btn-act-soft" title="Menunggu Vendor mengunggah dokumen final (TTD Basah)" disabled style="opacity:.6; cursor:not-allowed;">
                                                    <i class="bi bi-clock-history"></i> Menunggu Vendor
                                                </button>
                                            @endif
                                        @elseif($kontrak->status_kontrak === 'AKTIF' && $termin->status_termin === 'LOCKED')
                                            <button disabled class="btn-act-modern btn-act-soft" style="opacity:.55; cursor:not-allowed;" title="Termin masih terkunci">
                                                <i class="bi bi-lock-fill"></i> Terkunci
                                            </button>
                                        @elseif(in_array($termin->status_termin, ['DRAFT', 'SUDAH_DITAGIH']))
                                            @php $tagihanLinked = $termin->detailKontrak->tagihan ?? null; @endphp
                                            @if($tagihanLinked)
                                                <div class="d-inline-flex gap-1 flex-wrap justify-content-center">
                                                    <a href="{{ route('tagihan.kontrak.show', $tagihanLinked->id) }}" class="btn-act-modern btn-act-soft" title="Detail Tagihan">
                                                        <i class="bi bi-file-text"></i> Detail
                                                    </a>
                                                    <button type="button" class="btn-act-modern btn-act-info" data-bs-toggle="modal" data-bs-target="#modalAktivitasTagihan{{ $tagihanLinked->id }}" title="Riwayat Aktivitas">
                                                        <i class="bi bi-clock-history"></i>
                                                    </button>
                                                </div>
                                            @else
                                                <span class="text-danger small"><i class="bi bi-exclamation-triangle"></i> Data Tagihan Hilang</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-cell-state">
                                            <i class="bi bi-list-task"></i>
                                            <h6 class="text-secondary fw-bold mb-1">Belum ada skema termin</h6>
                                            <small>Skema termin akan muncul setelah kontrak dibuat.</small>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB ADDENDUM --}}
                <div class="tab-pane-c" data-pane="addendum">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">Riwayat dan Workspace Addendum</h6>
                            <div class="small text-muted">Pantau perubahan kontrak, status addendum, dan buka workspace detail untuk approval atau revisi.</div>
                        </div>
                        <a href="{{ route('addendums.index', $kontrak->id) }}" class="btn-act-modern btn-act-primary">
                            <i class="bi bi-journal-text"></i> Kelola Addendum
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="termin-table">
                            <thead>
                                <tr>
                                    <th>No. Addendum</th>
                                    <th>Tanggal</th>
                                    <th>Jenis Perubahan</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kontrak->addendums as $addm)
                                <tr>
                                    <td><span class="termin-keterangan">{{ $addm->nomor_addendum }}</span></td>
                                    <td>{{ \Carbon\Carbon::parse($addm->tanggal_addendum)->isoFormat('D MMM YYYY') }}</td>
                                    <td><span class="termin-jenis-pill">{{ str_replace('_', ' ', $addm->jenis_addendum) }}</span></td>
                                    <td>
                                        @php
                                            $statusWorkflow = $addm->status_workflow ?? ($addm->status_addendum ?? 'DRAFT');
                                            $stCls = match($statusWorkflow) {
                                                'APPROVED'  => 'tp-billed',
                                                'SUBMITTED' => 'tp-ready',
                                                'REJECTED'  => 'tp-locked',
                                                default     => 'tp-draft',
                                            };
                                        @endphp
                                        <span class="termin-pill {{ $stCls }}">{{ str_replace('_', ' ', $statusWorkflow) }}</span>
                                    </td>
                                    <td><small class="text-muted">{{ Str::limit($addm->keterangan_alasan, 50) }}</small></td>
                                    <td class="text-center">
                                        <a href="{{ route('addendums.show', [$kontrak->id, $addm->id]) }}" class="btn-act-modern btn-act-soft">
                                            <i class="bi bi-search"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-cell-state">
                                            <i class="bi bi-journal-x"></i>
                                            <h6 class="text-secondary fw-bold mb-1">Belum ada addendum</h6>
                                            <small>Riwayat addendum akan tampil di sini ketika ada perubahan kontrak.</small>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- KOLOM KANAN: AUDIT TRAIL / RIWAYAT AKTIVITAS -->
    <div class="col-lg-4 col-xl-3">
        <div class="timeline-card">
            <div class="tl-head">
                <h6><i class="bi bi-clock-history"></i> Riwayat Aktivitas Proyek</h6>
            </div>
            <div class="tl-body">
                <div class="activity-list">
                    @foreach($semuaAktivitas as $idx => $akt)
                        <div class="timeline-mod">
                            <span class="tl-dot"></span>
                            <div class="tl-time">
                                <span class="tl-rel">{{ \Carbon\Carbon::parse($akt['tanggal'])->diffForHumans() }}</span>
                                <span class="text-muted">· {{ \Carbon\Carbon::parse($akt['tanggal'])->isoFormat('D MMM HH:mm') }}</span>
                            </div>
                            <p class="tl-title">{{ $akt['judul'] }}</p>
                            <div class="tl-actor"><i class="bi bi-person-circle"></i> Oleh: {{ $akt['aktor'] }}</div>
                            @if(isset($akt['catatan']) && $akt['catatan'] !== '-')
                                <div class="tl-note">"{{ $akt['catatan'] }}"</div>
                            @endif
                        </div>
                    @endforeach

                    <div class="timeline-end">Awal Inisiasi</div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>



@if($kontrak->status_kontrak === 'AKTIF')
<div class="modal fade" id="modalTagihKontrakDetail" tabindex="-1" aria-labelledby="modalTagihKontrakDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header modal-grad-success">
                <div>
                    <h5 class="modal-title fw-bold" id="modalTagihKontrakDetailLabel"><i class="bi bi-cash-stack me-2"></i>Pilih Termin / Lumpsum untuk Ditagih</h5>
                    <div class="small opacity-90">{{ $kontrak->nomor_spk }} · {{ Str::limit($kontrak->nama_pekerjaan, 80) }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                @if($readyTerms->isEmpty())
                    <div class="alert alert-light border mb-0">Belum ada termin atau lumpsum yang siap ditagih untuk kontrak ini.</div>
                @else
                    <div class="list-group">
                        @foreach($readyTerms as $termin)
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <div class="fw-bold">Termin {{ $termin->termin_ke }} - {{ str_replace('_', ' ', $termin->jenis_termin) }}</div>
                                    <div class="small text-muted">{{ $termin->keterangan_termin }}</div>
                                    <div class="small mt-1">
                                        <span class="badge bg-light text-dark border">{{ $termin->persentase }}%</span>
                                        <span class="ms-2 fw-semibold text-success">Rp {{ number_format($termin->nilai_bruto_termin, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                                <a href="{{ route('tagihan.kontrak.create', ['kontrak_id' => $kontrak->id, 'termin_id' => $termin->id]) }}" class="btn btn-primary btn-sm fw-bold">
                                    <i class="bi bi-send-plus me-1"></i> Tagih
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@foreach($readyTerms as $termin)
<div class="modal fade" id="modalTagihTermin{{ $termin->id }}" tabindex="-1" aria-labelledby="modalTagihTerminLabel{{ $termin->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header modal-grad-success">
                <h5 class="modal-title fw-bold" id="modalTagihTerminLabel{{ $termin->id }}"><i class="bi bi-cash-stack me-2"></i>Konfirmasi Buat Tagihan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="fw-bold mb-1">Termin {{ $termin->termin_ke }} - {{ str_replace('_', ' ', $termin->jenis_termin) }}</div>
                <div class="text-muted small mb-2">{{ $termin->keterangan_termin }}</div>
                <div class="small">Nilai bruto: <span class="fw-bold text-success">Rp {{ number_format($termin->nilai_bruto_termin, 0, ',', '.') }}</span></div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="{{ route('tagihan.kontrak.create', ['kontrak_id' => $kontrak->id, 'termin_id' => $termin->id]) }}" class="btn btn-primary fw-bold">
                    <i class="bi bi-send-plus me-1"></i> Lanjut Buat Tagihan
                </a>
            </div>
        </div>
    </div>
</div>
@endforeach
@endif

@foreach($kontrak->termin as $termin)
    @php
        $tagihanLinked = $termin->detailKontrak->tagihan ?? null;
    @endphp
    @if(in_array($termin->status_termin, ['DRAFT', 'SUDAH_DITAGIH']) && $tagihanLinked)
        <div class="modal fade text-start" id="modalAktivitasTagihan{{ $tagihanLinked->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header modal-grad-primary">
                        <div>
                            <h6 class="modal-title fw-bold m-0"><i class="bi bi-clock-history me-2"></i>Riwayat Aktivitas Termin {{ $termin->termin_ke }}</h6>
                            <div class="small opacity-90">{{ $tagihanLinked->nomor_tagihan ?? '' }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4" style="background: #fafbff;">
                        @forelse($tagihanLinked->logs as $log)
                            <div class="d-flex mb-3 gap-3">
                                <div style="width:10px; height:10px; border-radius:50%; background:linear-gradient(135deg,#818cf8,#6366f1); margin-top:8px; flex-shrink:0; box-shadow: 0 0 0 3px rgba(99,102,241,.15);"></div>
                                <div>
                                    <div class="fw-bold text-dark">{{ \Illuminate\Support\Str::title(strtolower(str_replace('_',' ',$log->status_baru))) }}</div>
                                    <div class="small text-muted">{{ \Carbon\Carbon::parse($log->created_at)->translatedFormat('d M Y H:i') }} · {{ $log->user ? $log->user->name : 'Sistem' }}</div>
                                    @if($log->catatan)
                                        <div class="small fst-italic mt-1 p-2 rounded" style="background: rgba(99,102,241,.06); border-left: 3px solid #818cf8; color: #475569;">"{{ $log->catatan }}"</div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="empty-cell-state">
                                <i class="bi bi-journal-x"></i>
                                <small>Belum ada riwayat aktivitas.</small>
                            </div>
                        @endforelse
                    </div>
                    <div class="modal-footer border-0 bg-light">
                        <button type="button" class="btn-act-modern btn-act-soft" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach



<div class="modal fade upload-modal" id="modalUploadSpkFinal" tabindex="-1" aria-labelledby="modalUploadSpkFinalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('contracts.spk.upload-final', $kontrak->id) }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-hero" style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 50%, #8b5cf6 100%);">
                <i class="bi bi-file-earmark-check-fill um-illust"></i>
                <button type="button" class="btn-close-um" data-bs-dismiss="modal" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
                <span class="um-tag"><i class="bi bi-stars"></i> Upload Dokumen</span>
                <h5 class="um-title" id="modalUploadSpkFinalLabel">
                    <i class="bi bi-file-earmark-check-fill me-1"></i>SPK Bertandatangan
                </h5>
                <p class="um-sub">
                    <i class="bi bi-bookmark-check"></i>
                    Nomor SPK: <strong>{{ $kontrak->nomor_spk }}</strong>
                </p>
            </div>
            <div class="modal-body">
                <div class="um-banner">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>
                        Upload file <strong>PDF SPK final bertandatangan</strong>.
                        Jika diunggah ulang, file aktif sebelumnya akan dinonaktifkan dan file baru otomatis menjadi dokumen aktif.
                    </div>
                </div>

                <label class="um-label" for="file_spk_final_ttd">
                    <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                    File SPK Final Bertandatangan
                    <span class="text-danger ms-1">*</span>
                </label>
                <label class="um-drop" data-max-mb="5">
                    <input type="file" id="file_spk_final_ttd" name="file_spk_final_ttd" accept=".pdf" required>
                    <div class="ud-default">
                        <div class="ud-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                        <div class="ud-title">Tarik &amp; lepaskan, atau <strong>klik untuk memilih</strong></div>
                        <div class="ud-sub">Pilih dokumen SPK final yang sudah ditandatangani lengkap.</div>
                        <div class="ud-meta"><i class="bi bi-file-earmark-pdf"></i> PDF &middot; Maks 5MB</div>
                    </div>
                    <div class="ud-preview">
                        <div class="ud-fp-icon"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                        <div class="ud-fp-info">
                            <div class="ud-fp-name">-</div>
                            <div class="ud-fp-detail">
                                <span class="ud-fp-size">0 KB</span>
                                <span class="ud-fp-type text-muted">PDF</span>
                            </div>
                            <div class="ud-bar"><span style="width:0%"></span></div>
                        </div>
                        <button type="button" class="ud-fp-remove" title="Hapus berkas"><i class="bi bi-x-lg"></i></button>
                    </div>
                </label>

                @if($spkFinalArsip)
                    <div class="um-current">
                        <span class="uc-icon"><i class="bi bi-file-earmark-pdf-fill"></i></span>
                        <div class="uc-info">
                            <div class="uc-label">File aktif saat ini</div>
                            <div class="uc-name">SPK Final Bertandatangan &mdash; Tersimpan</div>
                        </div>
                        <a href="{{ Storage::url($spkFinalArsip->path_file) }}" target="_blank" class="uc-link">
                            <i class="bi bi-box-arrow-up-right"></i> Lihat Dokumen
                        </a>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-um-cancel" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Batal
                </button>
                <button type="submit" class="btn-um-submit">
                    <i class="bi bi-cloud-arrow-up-fill"></i> Simpan SPK Final
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade upload-modal" id="modalUploadRingkasanKontrakFinal" tabindex="-1" aria-labelledby="modalUploadRingkasanKontrakFinalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('contracts.ringkasan.upload-final', $kontrak->id) }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-hero" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);">
                <i class="bi bi-journal-richtext um-illust"></i>
                <button type="button" class="btn-close-um" data-bs-dismiss="modal" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
                <span class="um-tag"><i class="bi bi-stars"></i> Upload Dokumen</span>
                <h5 class="um-title" id="modalUploadRingkasanKontrakFinalLabel">
                    <i class="bi bi-journal-richtext me-1"></i>Ringkasan Kontrak Bertandatangan
                </h5>
                <p class="um-sub">
                    <i class="bi bi-bookmark-check"></i>
                    Nomor SPK: <strong>{{ $kontrak->nomor_spk }}</strong>
                </p>
            </div>
            <div class="modal-body">
                <div class="um-banner">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>
                        Upload file <strong>PDF Ringkasan Kontrak final bertandatangan</strong>.
                        Jika diunggah ulang, file aktif sebelumnya akan dinonaktifkan dan file baru otomatis menjadi dokumen aktif.
                    </div>
                </div>

                <label class="um-label" for="file_ringkasan_kontrak_final_ttd">
                    <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                    File Ringkasan Kontrak Final
                    <span class="text-danger ms-1">*</span>
                </label>
                <label class="um-drop" data-max-mb="5">
                    <input type="file" id="file_ringkasan_kontrak_final_ttd" name="file_ringkasan_kontrak_final_ttd" accept=".pdf" required>
                    <div class="ud-default">
                        <div class="ud-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                        <div class="ud-title">Tarik &amp; lepaskan, atau <strong>klik untuk memilih</strong></div>
                        <div class="ud-sub">Pilih dokumen Ringkasan Kontrak yang sudah ditandatangani lengkap.</div>
                        <div class="ud-meta"><i class="bi bi-file-earmark-pdf"></i> PDF &middot; Maks 5MB</div>
                    </div>
                    <div class="ud-preview">
                        <div class="ud-fp-icon"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                        <div class="ud-fp-info">
                            <div class="ud-fp-name">-</div>
                            <div class="ud-fp-detail">
                                <span class="ud-fp-size">0 KB</span>
                                <span class="ud-fp-type text-muted">PDF</span>
                            </div>
                            <div class="ud-bar"><span style="width:0%"></span></div>
                        </div>
                        <button type="button" class="ud-fp-remove" title="Hapus berkas"><i class="bi bi-x-lg"></i></button>
                    </div>
                </label>

                @if($ringkasanFinalArsip)
                    <div class="um-current">
                        <span class="uc-icon"><i class="bi bi-file-earmark-pdf-fill"></i></span>
                        <div class="uc-info">
                            <div class="uc-label">File aktif saat ini</div>
                            <div class="uc-name">Ringkasan Kontrak Final &mdash; Tersimpan</div>
                        </div>
                        <a href="{{ Storage::url($ringkasanFinalArsip->path_file) }}" target="_blank" class="uc-link">
                            <i class="bi bi-box-arrow-up-right"></i> Lihat Dokumen
                        </a>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-um-cancel" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Batal
                </button>
                <button type="submit" class="btn-um-submit">
                    <i class="bi bi-cloud-arrow-up-fill"></i> Simpan Ringkasan Kontrak
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade upload-modal" id="modalUploadSpmkFinal" tabindex="-1" aria-labelledby="modalUploadSpmkFinalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('contracts.spmk.upload-final', $kontrak->id) }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-hero" style="background: linear-gradient(135deg, #06b6d4 0%, #0ea5e9 50%, #2563eb 100%);">
                <i class="bi bi-file-earmark-text-fill um-illust"></i>
                <button type="button" class="btn-close-um" data-bs-dismiss="modal" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
                <span class="um-tag"><i class="bi bi-stars"></i> Upload Dokumen</span>
                <h5 class="um-title" id="modalUploadSpmkFinalLabel">
                    <i class="bi bi-file-earmark-text-fill me-1"></i>SPMK Bertandatangan
                </h5>
                <p class="um-sub">
                    <i class="bi bi-bookmark-check"></i>
                    Nomor SPMK: <strong>{{ $kontrak->nomor_spmk ?? '-' }}</strong>
                </p>
            </div>
            <div class="modal-body">
                <div class="um-banner" style="background: linear-gradient(135deg, rgba(14,165,233,.06), rgba(14,165,233,.02)); border-color: rgba(14,165,233,.20); border-left-color: #0ea5e9;">
                    <i class="bi bi-info-circle-fill" style="color:#0369a1;"></i>
                    <div>
                        Upload file <strong>PDF SPMK final bertandatangan</strong>.
                        Jika diunggah ulang, file aktif sebelumnya akan dinonaktifkan dan file baru otomatis menjadi dokumen aktif.
                    </div>
                </div>

                <label class="um-label" for="file_spmk_final_ttd">
                    <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                    File SPMK Final Bertandatangan
                    <span class="text-danger ms-1">*</span>
                </label>
                <label class="um-drop" data-max-mb="5">
                    <input type="file" id="file_spmk_final_ttd" name="file_spmk_final_ttd" accept=".pdf" required>
                    <div class="ud-default">
                        <div class="ud-icon" style="background: linear-gradient(135deg, #38bdf8, #0ea5e9); box-shadow: 0 8px 20px rgba(14,165,233,.30);"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                        <div class="ud-title">Tarik &amp; lepaskan, atau <strong>klik untuk memilih</strong></div>
                        <div class="ud-sub">Pilih dokumen SPMK final yang sudah ditandatangani lengkap.</div>
                        <div class="ud-meta" style="background: rgba(14,165,233,.10); color:#0369a1;"><i class="bi bi-file-earmark-pdf"></i> PDF &middot; Maks 5MB</div>
                    </div>
                    <div class="ud-preview">
                        <div class="ud-fp-icon"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                        <div class="ud-fp-info">
                            <div class="ud-fp-name">-</div>
                            <div class="ud-fp-detail">
                                <span class="ud-fp-size">0 KB</span>
                                <span class="ud-fp-type text-muted">PDF</span>
                            </div>
                            <div class="ud-bar"><span style="width:0%"></span></div>
                        </div>
                        <button type="button" class="ud-fp-remove" title="Hapus berkas"><i class="bi bi-x-lg"></i></button>
                    </div>
                </label>

                @if($spmkFinalArsip)
                    <div class="um-current">
                        <span class="uc-icon" style="background: linear-gradient(135deg, rgba(14,165,233,.15), rgba(99,102,241,.10)); color:#0369a1;"><i class="bi bi-file-earmark-pdf-fill"></i></span>
                        <div class="uc-info">
                            <div class="uc-label">File aktif saat ini</div>
                            <div class="uc-name">SPMK Final Bertandatangan &mdash; Tersimpan</div>
                        </div>
                        <a href="{{ Storage::url($spmkFinalArsip->path_file) }}" target="_blank" class="uc-link" style="border-color: rgba(14,165,233,.30); color:#0369a1;">
                            <i class="bi bi-box-arrow-up-right"></i> Lihat Dokumen
                        </a>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-um-cancel" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Batal
                </button>
                <button type="submit" class="btn-um-submit" style="background: linear-gradient(135deg, #06b6d4, #0ea5e9, #2563eb); box-shadow: 0 8px 22px rgba(14,165,233,.35);">
                    <i class="bi bi-cloud-arrow-up-fill"></i> Simpan SPMK Final
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('script')
<script>
document.addEventListener("DOMContentLoaded", function () {
    // ============ Upload Modal Dropzones ============
    document.querySelectorAll('.um-drop').forEach(function (zone) {
        const input = zone.querySelector('input[type="file"]');
        if (!input) return;
        const preview = zone.querySelector('.ud-preview');
        const fpIcon = preview.querySelector('.ud-fp-icon');
        const fpName = preview.querySelector('.ud-fp-name');
        const fpSize = preview.querySelector('.ud-fp-size');
        const fpType = preview.querySelector('.ud-fp-type');
        const fpBar = preview.querySelector('.ud-bar > span');
        const fpRemove = preview.querySelector('.ud-fp-remove');
        const maxMb = parseFloat(zone.dataset.maxMb || '5');
        const maxBytes = maxMb * 1024 * 1024;

        // Snapshot original icon HTML so we can restore after a thumbnail.
        const originalIconHtml = fpIcon ? fpIcon.innerHTML : '';
        const originalIconStyle = fpIcon ? fpIcon.getAttribute('style') || '' : '';

        const fmtSize = function (bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        };

        const resetIcon = function () {
            if (!fpIcon) return;
            fpIcon.innerHTML = originalIconHtml;
            fpIcon.setAttribute('style', originalIconStyle);
        };

        const setThumbnail = function (file) {
            if (!fpIcon) return;
            const url = URL.createObjectURL(file);
            fpIcon.innerHTML = '';
            fpIcon.removeAttribute('style');
            const img = document.createElement('img');
            img.src = url;
            img.alt = file.name;
            img.className = 'ud-thumb';
            img.onload = function () { URL.revokeObjectURL(url); };
            fpIcon.appendChild(img);
        };

        const adaptIconForFile = function (file) {
            if (!fpIcon) return;
            const name = (file.name || '').toLowerCase();
            const isImg = (file.type || '').startsWith('image/') || /\.(jpe?g|png|gif|webp|bmp)$/i.test(name);
            const isZip = /\.zip$/i.test(name) || file.type === 'application/zip' || file.type === 'application/x-zip-compressed';

            if (isImg) {
                setThumbnail(file);
                return;
            }
            // Non-image: choose icon set by extension
            resetIcon();
            if (isZip) {
                fpIcon.innerHTML = '<i class="bi bi-file-earmark-zip-fill"></i>';
                fpIcon.setAttribute('style', 'background: linear-gradient(135deg, #fbbf24, #f59e0b); box-shadow: 0 6px 14px rgba(245,158,11,.30);');
            }
        };

        const renderFile = function (file) {
            if (!file) {
                zone.classList.remove('is-filled');
                resetIcon();
                return;
            }
            const size = file.size || 0;
            const ratio = Math.min(size / maxBytes, 1);
            const ext = (file.name.split('.').pop() || '').toUpperCase();
            fpName.textContent = file.name;
            fpSize.textContent = fmtSize(size);
            if (fpType) fpType.textContent = ext;
            fpBar.style.width = (ratio * 100).toFixed(0) + '%';
            adaptIconForFile(file);
            zone.classList.add('is-filled');
        };

        input.addEventListener('change', function () {
            const file = input.files && input.files[0];
            renderFile(file || null);
        });

        if (fpRemove) {
            fpRemove.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                input.value = '';
                zone.classList.remove('is-filled');
                fpName.textContent = '-';
                fpSize.textContent = '0 KB';
                fpBar.style.width = '0%';
                resetIcon();
            });
        }

        ['dragenter', 'dragover'].forEach(function (evt) {
            zone.addEventListener(evt, function (e) {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.add('is-drag');
            });
        });
        ['dragleave', 'dragend', 'drop'].forEach(function (evt) {
            zone.addEventListener(evt, function (e) {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.remove('is-drag');
            });
        });
        zone.addEventListener('drop', function (e) {
            const dt = e.dataTransfer;
            if (!dt || !dt.files || !dt.files.length) return;
            try {
                input.files = dt.files;
            } catch (err) {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(dt.files[0]);
                input.files = dataTransfer.files;
            }
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    // Custom tabs
    const tabs = document.querySelectorAll('#detailTabs .tab-btn');
    const panes = document.querySelectorAll('.tab-pane-c');
    tabs.forEach(t => {
        t.addEventListener('click', function () {
            const target = this.dataset.tab;
            tabs.forEach(b => b.classList.toggle('active', b.dataset.tab === target));
            panes.forEach(p => p.classList.toggle('active', p.dataset.pane === target));
        });
    });
});
</script>
@endpush
