@extends('layouts.app')
@section('title', 'Detail Tagihan Termin')

@push('css')
<style>
    .verifikator-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: .9rem;
        flex-shrink: 0;
        text-shadow: 0 1px 1px rgba(0,0,0,.15);
    }
    .verifikator-card {
        transition: all .15s ease;
        border-left: 4px solid transparent;
        position: relative;
    }
    .verifikator-card.is-filled { border-left-color: var(--bs-success); }
    .verifikator-card.is-empty  { border-left-color: var(--bs-warning); background: #fff8e1; }
    .verifikator-step-no {
        position: absolute;
        top: -10px; left: -10px;
        width: 26px; height: 26px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid var(--bs-primary);
        color: var(--bs-primary);
        font-size: .75rem;
        font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        z-index: 2;
        box-shadow: 0 2px 4px rgba(0,0,0,.08);
    }
    .role-chip {
        font-size: .68rem;
        padding: 2px 8px;
        border-radius: 999px;
        font-weight: 600;
        letter-spacing: .3px;
    }
</style>
@endpush

@section('content')
@php
    $statusBadge = match($tagihan->status) {
        'DRAFT'                          => ['class' => 'bg-warning text-dark', 'icon' => 'pencil-square',         'label' => 'Draft — Sedang Disusun'],
        'PENDING_VERIFIKASI_KONTRAK'     => ['class' => 'bg-info text-dark',    'icon' => 'people-fill',           'label' => 'Verifikasi Paralel Berjalan'],
        'PENDING_PPK'                    => ['class' => 'bg-info text-white',   'icon' => 'hourglass-split',       'label' => 'Menunggu PPK'],
        'PENDING_PPSPM'                  => ['class' => 'bg-info text-white',   'icon' => 'hourglass-split',       'label' => 'Menunggu PPSPM'],
        'PENDING_KOORDINATOR_KEUANGAN'   => ['class' => 'bg-info text-white',   'icon' => 'hourglass-split',       'label' => 'Menunggu Koordinator Keuangan'],
        'PENDING_BENDAHARA_PENGELUARAN'  => ['class' => 'bg-info text-white',   'icon' => 'hourglass-split',       'label' => 'Menunggu Bendahara Pengeluaran'],
        'PENDING_BENDAHARA_PENERIMAAN'   => ['class' => 'bg-info text-white',   'icon' => 'hourglass-split',       'label' => 'Menunggu Bendahara Penerimaan'],
        'PENDING_KASUBBAG'               => ['class' => 'bg-primary text-white','icon' => 'hourglass-split',       'label' => 'Menunggu Kasubbag (Final)'],
        'REVISI_PPK', 'REVISI_PPSPM', 'REVISI_KOORDINATOR_KEUANGAN', 'REVISI_BENDAHARA_PENGELUARAN', 'REVISI_BENDAHARA_PENERIMAAN', 'REVISI_KASUBBAG', 'REVISI_PEJABAT_PENGADAAN'
                                         => ['class' => 'bg-warning text-dark', 'icon' => 'arrow-counterclockwise','label' => 'Perlu Revisi'],
        'DITOLAK_PPK', 'DITOLAK_PPSPM', 'DITOLAK_KOORDINATOR_KEUANGAN', 'DITOLAK_BENDAHARA_PENGELUARAN', 'DITOLAK_BENDAHARA_PENERIMAAN', 'DITOLAK_KASUBBAG'
                                         => ['class' => 'bg-danger text-white', 'icon' => 'x-octagon',             'label' => 'Ditolak'],
        'APPROVED', 'DISETUJUI_KONTRAK', 'READY_FOR_SPP'
                                         => ['class' => 'bg-success text-white','icon' => 'check-circle',          'label' => 'Disetujui — Siap SPP'],
        default                          => ['class' => 'bg-secondary text-white', 'icon' => 'circle',             'label' => $tagihan->status],
    };

    // Ambil status approval per role_code dari workflow_approvals (untuk indikator visual)
    $approvalStatusByRole = collect();
    if ($tagihan->relationLoaded('workflowInstance') ? $tagihan->workflowInstance : ($tagihan->workflowInstance ?? null)) {
        $instance = $tagihan->workflowInstance;
        if ($instance) {
            $approvalStatusByRole = $instance->approvals
                ->keyBy(fn ($a) => strtoupper(str_replace([' ', '-'], '_', $a->role_code)));
        }
    }

    $approvalMeta = [
        'PENDING'  => ['cls' => 'pending',  'icon' => 'hourglass-split',         'label' => 'Menunggu',  'color' => 'warning'],
        'APPROVED' => ['cls' => 'approved', 'icon' => 'check-circle-fill',       'label' => 'Disetujui', 'color' => 'success'],
        'REVISION' => ['cls' => 'revision', 'icon' => 'arrow-counterclockwise',  'label' => 'Revisi',    'color' => 'danger'],
        'REJECTED' => ['cls' => 'rejected', 'icon' => 'x-circle-fill',           'label' => 'Ditolak',   'color' => 'danger'],
        'WAITING'  => ['cls' => 'waiting',  'icon' => 'clock-history',           'label' => 'Belum aktif','color' => 'secondary'],
    ];

    // Daftar verifikator + meta untuk styling (urutan = alur tanda-tangan dokumen)
    $verifikatorList = [
        ['key' => 'ppk',                  'role_code' => 'PPK',                   'label' => 'PPK',                                          'short' => 'PPK',          'color' => '#0d6efd', 'nama' => $tagihan->ppk_nama_snapshot,                  'nip' => $tagihan->ppk_nip_snapshot,                  'auto' => true],
        ['key' => 'ppspm',                'role_code' => 'PPSPM',                 'label' => 'PPSPM',                                        'short' => 'PPSPM',        'color' => '#6610f2', 'nama' => $tagihan->ppspm_nama_snapshot,                'nip' => $tagihan->ppspm_nip_snapshot,                'auto' => false],
        ['key' => 'bendahara_pengeluaran','role_code' => 'BENDAHARA_PENGELUARAN', 'label' => 'Bendahara Pengeluaran',                        'short' => 'BEND. KELUAR', 'color' => '#d63384', 'nama' => $tagihan->bendahara_pengeluaran_nama_snapshot,'nip' => $tagihan->bendahara_pengeluaran_nip_snapshot,'auto' => false],
        ['key' => 'bendahara_penerimaan', 'role_code' => 'BENDAHARA_PENERIMAAN',  'label' => 'Bendahara Penerimaan',                         'short' => 'BEND. TERIMA', 'color' => '#fd7e14', 'nama' => $tagihan->bendahara_penerimaan_nama_snapshot, 'nip' => $tagihan->bendahara_penerimaan_nip_snapshot, 'auto' => false],
        ['key' => 'koordinator_keuangan', 'role_code' => 'KOORDINATOR_KEUANGAN',  'label' => 'Koordinator Keuangan',                         'short' => 'KOOR. KEU',    'color' => '#198754', 'nama' => $tagihan->koordinator_keuangan_nama_snapshot, 'nip' => $tagihan->koordinator_keuangan_nip_snapshot, 'auto' => false],
        ['key' => 'kasubbag',             'role_code' => 'KASUBBAG',              'label' => 'Kepala Subbagian Keuangan dan Tata Usaha',     'short' => 'KASUBBAG',     'color' => '#0dcaf0', 'nama' => $tagihan->kasubbag_nama_snapshot,             'nip' => $tagihan->kasubbag_nip_snapshot,             'auto' => false],
    ];

    $initials = function ($name) {
        $name = trim((string) $name);
        if ($name === '') return '?';
        $parts = preg_split('/\s+/', $name);
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
        return mb_strtoupper($first . $last);
    };

    $verifikatorTerisi = collect($verifikatorList)->filter(fn($v) => !empty($v['nama']))->count();
    $verifikatorTotal  = count($verifikatorList);
    $verifikatorLengkap = $verifikatorTerisi === $verifikatorTotal;
@endphp

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Detail Tagihan Termin</h4>
            <div class="d-flex align-items-center flex-wrap gap-2 mt-2">
                <span class="badge {{ $statusBadge['class'] }} px-3 py-2">
                    <i class="bi bi-{{ $statusBadge['icon'] }} me-1"></i> {{ $statusBadge['label'] }}
                </span>
                <span class="text-muted small font-monospace">{{ $tagihan->nomor_tagihan }}</span>
                <span class="text-muted small">·</span>
                <span class="text-muted small">{{ $kontrak->nomor_spk ?? '-' }}</span>
                <span class="text-muted small">·</span>
                <span class="text-muted small">Termin {{ $termin->termin_ke ?? '-' }} ({{ $termin->jenis_termin }})</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAktivitasTagihan">
                <i class="bi bi-activity me-1"></i> Lihat Aktivitas
            </button>
            <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm alert-dismissible bg-success text-white">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible bg-danger text-white">
            <i class="bi bi-exclamation-octagon me-2"></i> Terdapat kesalahan:
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- Area Kiri: Informasi Tagihan & Status --}}
        <div class="col-lg-8">
            {{-- Ringkasan Tagihan --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom pt-4 px-4 pb-3">
                    <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-receipt me-2"></i>Ringkasan Tagihan & Finansial</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Nomor Tagihan</div>
                            <div class="fw-bold">{{ $tagihan->nomor_tagihan }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Nomor SPK / Kontrak</div>
                            <div class="fw-bold">{{ $kontrak->nomor_spk ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Nama Pekerjaan</div>
                            <div class="fw-bold">{{ $kontrak->nama_pekerjaan ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Vendor</div>
                            <div class="fw-bold">{{ $kontrak->vendor->nama_pihak ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Termin</div>
                            <div class="fw-bold">Termin {{ $termin->termin_ke ?? '-' }} ({{ $termin->jenis_termin }})</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Data Invoice</div>
                            <div class="fw-bold">{{ $detailKontrak->nomor_invoice ?? '-' }}</div>
                            <div class="small text-muted">{{ optional($detailKontrak->tanggal_invoice)->format('d M Y') ?? '-' }}</div>
                        </div>
                    </div>
                    
                    <div class="p-3 bg-light rounded border">
                        <div class="row g-3 text-center">
                            <div class="col-md-4">
                                <div class="text-muted small mb-1">Total Bruto</div>
                                <div class="fw-bold fs-5">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-md-4 border-start border-end">
                                <div class="text-muted small mb-1">Total Potongan</div>
                                <div class="fw-bold text-danger fs-5">Rp {{ number_format($tagihan->total_potongan, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small mb-1">Total Netto</div>
                                <div class="fw-bold text-success fs-5">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Ringkasan Legalitas --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom pt-4 px-4 pb-3">
                    <h5 class="fw-bold mb-0 text-info"><i class="bi bi-file-earmark-check me-2"></i>Legalitas Pekerjaan</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small mb-1">Nomor BAPP</div>
                                <div class="fw-bold">{{ $detailKontrak->nomor_bapp ?? '-' }}</div>
                                <div class="small text-muted">Tgl: {{ optional($detailKontrak->tanggal_bapp)->format('d M Y') ?? '-' }}</div>
                            </div>
                        </div>
                        @if($wajibBast)
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small mb-1">Nomor BAST</div>
                                <div class="fw-bold">{{ $detailKontrak->nomor_bast ?? '-' }}</div>
                                <div class="small text-muted">Tgl: {{ optional($detailKontrak->tanggal_bast)->format('d M Y') ?? '-' }}</div>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small mb-1">Nomor BAP</div>
                                <div class="fw-bold">{{ $detailKontrak->nomor_bap ?? '-' }}</div>
                                <div class="small text-muted">Tgl: {{ optional($detailKontrak->tanggal_bap)->format('d M Y') ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-12 mt-4 pt-3 border-top">
                            <h6 class="fw-bold text-secondary mb-3">Data Pemeriksa Hasil Pekerjaan (BAPP)</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="text-muted small mb-1">Nama / NIP</div>
                                    <div class="fw-bold">{{ $detailKontrak->nama_pemeriksa ?? '-' }}</div>
                                    <div class="small text-muted">{{ $detailKontrak->nip_pemeriksa ?? '-' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small mb-1">Jabatan</div>
                                    <div class="fw-bold">{{ $detailKontrak->jabatan_pemeriksa ?? '-' }}</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Verifikator Penagihan (snapshot saat tagihan dibuat) --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom pt-4 px-4 pb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h5 class="fw-bold mb-0 text-success"><i class="bi bi-people-fill me-2"></i>Verifikator Penagihan</h5>
                        <p class="text-muted small mb-0 mt-1">Daftar pejabat penanda tangan dokumen — diurutkan sesuai alur verifikasi</p>
                    </div>
                    <div class="text-end">
                        <span class="badge {{ $verifikatorLengkap ? 'bg-success' : 'bg-warning text-dark' }} fs-6">
                            <i class="bi bi-{{ $verifikatorLengkap ? 'check-circle' : 'exclamation-triangle' }} me-1"></i>
                            {{ $verifikatorTerisi }}/{{ $verifikatorTotal }} terisi
                        </span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @foreach($verifikatorList as $idx => $v)
                            @php
                                $filled = !empty($v['nama']);
                                $approval = $approvalStatusByRole->get($v['role_code']);
                                $apvMeta = $approval ? ($approvalMeta[$approval->status] ?? null) : null;
                            @endphp
                            <div class="col-md-6 col-xl-4">
                                <div class="verifikator-card border rounded-3 p-3 h-100 {{ $filled ? 'is-filled' : 'is-empty' }}">
                                    <span class="verifikator-step-no" style="border-color: {{ $v['color'] }}; color: {{ $v['color'] }};">{{ $idx + 1 }}</span>

                                    @if($apvMeta)
                                        <span class="badge bg-{{ $apvMeta['color'] }} position-absolute" style="top: -8px; right: 8px;">
                                            <i class="bi bi-{{ $apvMeta['icon'] }} me-1"></i>{{ $apvMeta['label'] }}
                                        </span>
                                    @endif

                                    <div class="d-flex align-items-start gap-3">
                                        <div class="verifikator-avatar" style="background: {{ $v['color'] }};">
                                            {{ $initials($v['nama']) }}
                                        </div>
                                        <div class="flex-grow-1 min-width-0">
                                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                                <span class="role-chip" style="background: {{ $v['color'] }}1a; color: {{ $v['color'] }};">{{ $v['short'] }}</span>
                                                @if($v['auto'])
                                                    <span class="role-chip" style="background: #e9ecef; color: #495057;" title="Diambil otomatis dari kontrak">
                                                        <i class="bi bi-link-45deg"></i> auto
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="fw-bold text-truncate" title="{{ $v['nama'] }}">{{ $v['nama'] ?: '— belum dipilih —' }}</div>
                                            @if($v['nip'])
                                                <div class="small text-muted font-monospace">NIP: {{ $v['nip'] }}</div>
                                            @else
                                                <div class="small text-muted fst-italic">NIP belum tersedia</div>
                                            @endif
                                            <div class="small text-muted mt-1" title="{{ $v['label'] }}">{{ \Illuminate\Support\Str::limit($v['label'], 38) }}</div>
                                            @if($approval && $approval->acted_at)
                                                <div class="small text-muted mt-1">
                                                    <i class="bi bi-clock me-1"></i>{{ $approval->acted_at->format('d M Y H:i') }}
                                                    @if($approval->catatan)
                                                        · <span class="fst-italic">"{{ \Illuminate\Support\Str::limit($approval->catatan, 40) }}"</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @unless($verifikatorLengkap)
                        <div class="alert alert-warning border-0 small mb-0 mt-3 py-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Beberapa verifikator belum terisi. Tagihan lama mungkin dibuat sebelum fitur ini ada.
                        </div>
                    @endunless
                </div>
            </div>

            {{-- Dokumen Berita Acara Final --}}
            <h5 class="fw-bold mb-3 mt-5">Manajemen Dokumen Berita Acara</h5>
            <div class="row g-4">
                {{-- BAPP --}}
                <div class="col-md-6">
                    <div class="card border border-primary shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">Dokumen BAPP</h6>
                                @if($hasBappFinal)
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Final</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Belum Lengkap</span>
                                @endif
                            </div>
                            <p class="small text-muted mb-3">Unggah <strong>Gambar RAB</strong> terlebih dahulu, lalu cetak draft PDF BAPP yang akan menampilkan gambar tersebut. Setelah ditandatangani, scan & unggah versi finalnya.</p>

                            {{-- Status Gambar RAB --}}
                            <div class="border rounded p-2 mb-3 d-flex align-items-center justify-content-between {{ $hasGambarRabBapp ? 'bg-light border-success' : 'bg-warning-subtle border-warning' }}">
                                <div class="small">
                                    @if($hasGambarRabBapp)
                                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                                        <span class="fw-semibold">Gambar RAB sudah diunggah</span>
                                    @else
                                        <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                                        <span class="fw-semibold">Gambar RAB belum diunggah</span>
                                    @endif
                                </div>
                                @if($hasGambarRabBapp && $gambarRabBapp)
                                    <a href="{{ route('tagihan.kontrak.view-arsip', [$tagihan->id, $gambarRabBapp->id]) }}" target="_blank" class="btn btn-sm btn-link p-0 text-decoration-none">
                                        <i class="bi bi-eye"></i> Lihat
                                    </a>
                                @endif
                            </div>

                            <div class="d-grid gap-2">
                                @if($tagihan->status === 'DRAFT')
                                    <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalBappGambarRab">
                                        <i class="bi bi-image me-1"></i> {{ $hasGambarRabBapp ? 'Ganti Gambar RAB' : 'Unggah Gambar RAB' }}
                                    </button>
                                    @if($hasGambarRabBapp)
                                        <a href="{{ route('tagihan.kontrak.export-pdf', [$tagihan->id, 'BAPP']) }}" target="_blank" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-file-pdf me-1"></i> Cetak BAPP (PDF)
                                        </a>
                                    @else
                                        <button type="button" class="btn btn-outline-danger btn-sm" disabled title="Unggah Gambar RAB terlebih dahulu">
                                            <i class="bi bi-file-pdf me-1"></i> Cetak BAPP (PDF)
                                        </button>
                                    @endif
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalBapp">
                                        <i class="bi bi-upload me-1"></i> Unggah BAPP Final
                                    </button>
                                @endif
                                @if($hasBappFinal)
                                    @php $file = $detailKontrak->arsipDokumen->firstWhere('jenis_dokumen', 'BAPP_FINAL_TTD'); @endphp
                                    <a href="{{ route('tagihan.kontrak.view-arsip', [$tagihan->id, $file->id]) }}" target="_blank" class="btn btn-sm btn-success text-white">Lihat Final BAPP</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- BAST --}}
                <div class="col-md-6">
                    <div class="card border border-secondary shadow-sm h-100 {{ !$wajibBast ? 'bg-light' : '' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">Dokumen BAST</h6>
                                @if(!$wajibBast)
                                    <span class="badge bg-secondary">Tidak Wajib Di Termin Ini</span>
                                @elseif($hasBastFinal)
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Final</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Belum Lengkap</span>
                                @endif
                            </div>
                            <p class="small text-muted mb-4">Dokumen Berita Acara Serah Terima (Jika diperlukan untuk termin berjalan).</p>
                            
                            @if($wajibBast)
                            <div class="d-grid gap-2">
                                @if($tagihan->status === 'DRAFT')
                                    <a href="{{ route('tagihan.kontrak.export-pdf', [$tagihan->id, 'BAST']) }}" target="_blank" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-file-pdf me-1"></i> Cetak BAST (PDF)
                                    </a>
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalBast">
                                        <i class="bi bi-upload me-1"></i> Unggah BAST Final
                                    </button>
                                @endif
                                @if($hasBastFinal)
                                    @php $file = $detailKontrak->arsipDokumen->firstWhere('jenis_dokumen', 'BAST_FINAL_TTD'); @endphp
                                    <a href="{{ route('tagihan.kontrak.view-arsip', [$tagihan->id, $file->id]) }}" target="_blank" class="btn btn-sm btn-success text-white">Lihat Final BAST</a>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- BAP --}}
                <div class="col-md-6">
                    <div class="card border border-primary shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">Dokumen BAP</h6>
                                @if($hasBapFinal)
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Final</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Belum Lengkap</span>
                                @endif
                            </div>
                            <p class="small text-muted mb-4">Cetak draft PDF BAP, lakukan penandatanganan, scan, lalu unggah kembali versi finalnya.</p>
                            
                            <div class="d-grid gap-2">
                                @if($tagihan->status === 'DRAFT')
                                    <a href="{{ route('tagihan.kontrak.export-pdf', [$tagihan->id, 'BAP']) }}" target="_blank" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-file-pdf me-1"></i> Cetak BAP (PDF)
                                    </a>
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalBap">
                                        <i class="bi bi-upload me-1"></i> Unggah BAP Final
                                    </button>
                                @endif
                                @if($hasBapFinal)
                                    @php $file = $detailKontrak->arsipDokumen->firstWhere('jenis_dokumen', 'BAP_FINAL_TTD'); @endphp
                                    <a href="{{ route('tagihan.kontrak.view-arsip', [$tagihan->id, $file->id]) }}" target="_blank" class="btn btn-sm btn-success text-white">Lihat Final BAP</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Area Kanan: Status Kelengkapan --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 sticky-top topbar-safe-sticky z-1">
                <div class="card-body p-4">
                    @if($tagihan->status === 'DRAFT')
                        <h5 class="fw-bold mb-1">Syarat Pengajuan</h5>
                        <p class="text-muted small mb-3">Pastikan seluruh checklist di bawah terpenuhi sebelum mengajukan tagihan ke PPK.</p>

                        {{-- Checklist Berita Acara --}}
                        <div class="text-uppercase text-muted small fw-bold mb-2" style="letter-spacing: .5px;">Berita Acara</div>
                        <ul class="list-group mb-3">
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-1">
                                <span><i class="bi bi-{{ $hasBappFinal ? 'check-circle-fill text-success' : 'circle text-secondary' }} me-2"></i>BAPP Final bertandatangan</span>
                                @if($hasBappFinal)<span class="badge bg-success-subtle text-success">OK</span>@endif
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-1">
                                <span><i class="bi bi-{{ $hasBapFinal ? 'check-circle-fill text-success' : 'circle text-secondary' }} me-2"></i>BAP Final bertandatangan</span>
                                @if($hasBapFinal)<span class="badge bg-success-subtle text-success">OK</span>@endif
                            </li>
                            @if($wajibBast)
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-1">
                                <span><i class="bi bi-{{ $hasBastFinal ? 'check-circle-fill text-success' : 'circle text-secondary' }} me-2"></i>BAST Final bertandatangan</span>
                                @if($hasBastFinal)<span class="badge bg-success-subtle text-success">OK</span>@endif
                            </li>
                            @endif
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-1">
                                <span><i class="bi bi-{{ $hasGambarRabBapp ? 'check-circle-fill text-success' : 'circle text-secondary' }} me-2"></i>Gambar RAB BAPP</span>
                                @if($hasGambarRabBapp)<span class="badge bg-success-subtle text-success">OK</span>@endif
                            </li>
                        </ul>

                        {{-- Checklist Verifikator --}}
                        <div class="text-uppercase text-muted small fw-bold mb-2 mt-3" style="letter-spacing: .5px;">Verifikator</div>
                        <ul class="list-group mb-3">
                            @foreach($verifikatorList as $v)
                                <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-1">
                                    <span class="text-truncate" style="max-width: 75%;">
                                        <i class="bi bi-{{ !empty($v['nama']) ? 'check-circle-fill text-success' : 'circle text-secondary' }} me-2"></i>{{ $v['short'] }}
                                    </span>
                                    @if(!empty($v['nama']))
                                        <span class="badge bg-success-subtle text-success" title="{{ $v['nama'] }}">OK</span>
                                    @else
                                        <span class="badge bg-warning text-dark">—</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        {{-- Action button --}}
                        @if($isReadyToSubmit && $verifikatorLengkap)
                            <div class="alert alert-success border-0 small mb-3 py-2">
                                <i class="bi bi-check-circle me-1"></i> Tagihan siap diajukan.
                            </div>
                            <form action="{{ route('tagihan.kontrak.submit', $tagihan->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm">
                                    <i class="bi bi-send me-1"></i> Ajukan Tagihan
                                </button>
                            </form>
                        @else
                            <div class="alert alert-warning border-0 small mb-3 py-2">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                @if(!$verifikatorLengkap)
                                    Verifikator belum lengkap. Hubungi Pejabat Pengadaan untuk melengkapi.
                                @else
                                    Lengkapi finalisasi dokumen Berita Acara yang masih kurang.
                                @endif
                            </div>
                            <button type="button" class="btn btn-secondary w-100 fw-bold py-2" disabled>
                                <i class="bi bi-send me-1"></i> Ajukan Tagihan
                            </button>
                        @endif
                    @else
                        <h5 class="fw-bold mb-3">Informasi Status Tagihan</h5>
                        <div class="text-center py-3 mb-3 border-bottom">
                            @php
                                $st = $tagihan->status;
                                $isApproved  = in_array($st, ['APPROVED','DISETUJUI_KONTRAK','READY_FOR_SPP'], true);
                                $isInSpp     = in_array($st, ['PROSES_SPP','SEBAGIAN_SPP_TERBIT','SPP_TERBIT','SPP_LENGKAP'], true);
                                $isRejected  = str_starts_with($st, 'DITOLAK_');
                                $isRevisi    = str_starts_with($st, 'REVISI_');
                                $isPending   = str_starts_with($st, 'PENDING_');
                            @endphp

                            @if($isApproved)
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3 text-success">Tagihan Disetujui</h6>
                                <p class="text-muted small">Seluruh verifikator menyetujui. Tagihan siap diproses ke pembuatan SPP.</p>
                            @elseif($isInSpp)
                                <i class="bi bi-arrow-right-circle-fill text-primary" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3 text-primary">
                                    @switch($st)
                                        @case('PROSES_SPP') Sedang Diproses ke SPP @break
                                        @case('SEBAGIAN_SPP_TERBIT') Sebagian SPP Terbit @break
                                        @case('SPP_TERBIT') SPP Terbit @break
                                        @case('SPP_LENGKAP') SPP Lengkap @break
                                    @endswitch
                                </h6>
                                <p class="text-muted small">
                                    Tagihan telah disetujui dan sudah masuk tahap pembuatan SPP oleh Operator BLU.
                                    Verifikasi pengadaan untuk tagihan ini sudah selesai.
                                </p>
                            @elseif($isRejected)
                                <i class="bi bi-x-octagon text-danger" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3 text-danger">Tagihan Ditolak</h6>
                                <p class="text-muted small">{{ $statusBadge['label'] }}. Workflow telah dihentikan.</p>
                            @elseif($isRevisi)
                                <i class="bi bi-arrow-counterclockwise text-warning" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3 text-warning">Revisi Diperlukan</h6>
                                <p class="text-muted small">{{ $statusBadge['label'] }}. Silakan perbaiki tagihan dan ajukan ulang.</p>
                            @elseif($st === 'PENDING_VERIFIKASI_KONTRAK')
                                @php
                                    $wfStep1 = optional($tagihan->workflowInstance)->approvals?->where('urutan_step', 1) ?? collect();
                                    $wfStep1Approved = $wfStep1->where('status', 'APPROVED')->count();
                                    $wfStep1Total = $wfStep1->count();
                                @endphp
                                <i class="bi bi-people-fill text-info" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3 text-info">Verifikasi Paralel Berjalan</h6>
                                <p class="text-muted small">
                                    <strong>{{ $wfStep1Approved }} dari {{ $wfStep1Total }}</strong> verifikator paralel sudah menyetujui.
                                    Tagihan akan lanjut ke Kasubbag setelah semua selesai.
                                </p>
                                @if($wfStep1Total > 0)
                                    <div class="progress mt-2 mb-3" style="height: 6px;">
                                        <div class="progress-bar bg-info" style="width: {{ round(($wfStep1Approved / $wfStep1Total) * 100) }}%"></div>
                                    </div>
                                @endif
                            @elseif($st === 'PENDING_KASUBBAG')
                                <i class="bi bi-shield-check text-primary" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3 text-primary">Menunggu Persetujuan Kasubbag</h6>
                                <p class="text-muted small">5 verifikator paralel sudah menyetujui. Menunggu finalisasi Kepala Subbagian Keuangan dan Tata Usaha.</p>
                            @elseif($isPending)
                                <i class="bi bi-hourglass-split text-info" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3 text-info">{{ $statusBadge['label'] }}</h6>
                                <p class="text-muted small">Tagihan sedang dalam proses verifikasi.</p>
                            @else
                                <i class="bi bi-question-circle text-muted" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3 text-muted">{{ $statusBadge['label'] }}</h6>
                                <p class="text-muted small">Status tagihan tidak dikenali.</p>
                            @endif
                        </div>
                        
                        <h6 class="fw-bold text-secondary mb-2 fs-6">Kelengkapan Terlampir</h6>
                        <ul class="list-group mb-3">
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-1">
                                <span><i class="bi bi-check-circle-fill me-2 small text-success"></i>BAPP Final</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-1">
                                <span><i class="bi bi-check-circle-fill me-2 small text-success"></i>BAP Final</span>
                            </li>
                            @if($wajibBast)
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-1">
                                <span><i class="bi bi-check-circle-fill me-2 small text-success"></i>BAST Final</span>
                            </li>
                            @endif
                        </ul>

                        <h6 class="fw-bold text-secondary mb-2 fs-6">Verifikator Tercatat</h6>
                        <ul class="list-group mb-0">
                            @foreach($verifikatorList as $v)
                                @if(!empty($v['nama']))
                                    <li class="list-group-item border-0 px-0 py-1">
                                        <div class="d-flex align-items-start gap-2">
                                            <span class="role-chip mt-1" style="background: {{ $v['color'] }}1a; color: {{ $v['color'] }}; flex-shrink:0;">{{ $v['short'] }}</span>
                                            <div class="small flex-grow-1">
                                                <div class="fw-semibold text-truncate">{{ $v['nama'] }}</div>
                                                @if($v['nip'])<div class="text-muted font-monospace" style="font-size: .72rem;">NIP: {{ $v['nip'] }}</div>@endif
                                            </div>
                                        </div>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Lihat Aktivitas Tagihan --}}
@include('tagihan.partials.aktivitas-modal')

{{-- Modals for Uploads --}}
@include('tagihan.partials.modal_upload_arsip', ['id' => 'modalBappGambarRab', 'title' => 'Unggah Gambar RAB (BAPP)', 'jenis' => 'BAPP_GAMBAR_RAB', 'mimes' => '.jpg,.jpeg,.png'])
@include('tagihan.partials.modal_upload_arsip', ['id' => 'modalBapp', 'title' => 'Unggah BAPP Final', 'jenis' => 'BAPP_FINAL_TTD', 'mimes' => '.pdf'])
@if($wajibBast)
    @include('tagihan.partials.modal_upload_arsip', ['id' => 'modalBast', 'title' => 'Unggah BAST Final', 'jenis' => 'BAST_FINAL_TTD', 'mimes' => '.pdf'])
@endif
@include('tagihan.partials.modal_upload_arsip', ['id' => 'modalBap', 'title' => 'Unggah BAP Final', 'jenis' => 'BAP_FINAL_TTD', 'mimes' => '.pdf'])

@endsection
