@extends('layouts.app')

@section('title', 'Detail Honorarium')

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
<x-page-title title="Manajemen Honor" subtitle="Detail Honorarium" />

{{-- Panel Status / Tindak Lanjut --}}
@if($tagihan->status === 'DRAFT')
    <div class="alert alert-secondary d-flex align-items-center" role="alert">
        <i class="bi bi-info-circle-fill me-2 fs-4"></i>
        <div>
            <strong>Status: Draft.</strong> Dokumen ini belum diajukan. Silakan lengkapi data dan pastikan rincian penerima sudah benar.
        </div>
    </div>
@elseif($tagihan->status === 'PENDING_PPK')
    <div class="alert alert-primary d-flex align-items-center" role="alert">
        <i class="bi bi-hourglass-split me-2 fs-4"></i>
        <div>
            <strong>Menunggu Verifikasi PPK.</strong> Dokumen sedang dalam telaah oleh PPK. Data tidak dapat diubah (Read-Only).
        </div>
    </div>
@elseif($tagihan->status === 'DITOLAK_PPK')
    <div class="alert alert-warning border-warning text-dark d-flex align-items-center shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-4 text-warning"></i>
        <div>
            <strong class="d-block">Dikembalikan untuk Revisi!</strong>
            Dokumen ini ditolak oleh PPK. Silakan periksa catatan revisi pada riwayat di bawah, dan klik <strong>Edit Data</strong> untuk memperbaiki rincian.
        </div>
    </div>
@elseif(in_array($tagihan->status, ['DISETUJUI_PPK', 'PROSES_SPP', 'SPP_TERBIT']))
    <div class="alert alert-success d-flex align-items-center" role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-4"></i>
        <div>
            <strong>Disetujui.</strong> Dokumen ini telah melewati tahap verifikasi PPK dan sedang diproses ke tahap selanjutnya. Data tidak dapat diubah (Read-Only).
        </div>
    </div>
@endif

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white pt-4 pb-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="card-title text-primary mb-1 fw-bold">{{ $tagihan->nomor_tagihan }}</h5>
                <span class="badge 
                    @switch($tagihan->status)
                        @case('DRAFT') bg-secondary @break
                        @case('PENDING_PPK') bg-primary @break
                        @case('DISETUJUI_PPK') bg-success @break
                        @case('DITOLAK_PPK') bg-warning text-dark @break
                        @case('PROSES_SPP') bg-info text-dark @break
                        @case('SPP_TERBIT') bg-success @break
                        @default bg-secondary
                    @endswitch
                    rounded-pill px-3 py-2 fw-medium">
                    {{ str_replace('_', ' ', $tagihan->status) }}
                </span>
            </div>
            
            <div class="btn-group-sm d-flex">
                <a href="{{ route('honorarium.index') }}" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                
                <div class="dropdown me-2">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownCetak" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-printer"></i> Cetak
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownCetak">
                        <li>
                            <a class="dropdown-item" href="{{ route('honorarium.pdf', $tagihan->id) }}" target="_blank">
                                <i class="bi bi-file-earmark-pdf text-danger me-2"></i> Cetak Dokumen Honorarium
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('honorarium.pdf-nominatif', $tagihan->id) }}" target="_blank">
                                <i class="bi bi-file-earmark-person text-danger me-2"></i> Cetak Daftar Nominatif
                            </a>
                        </li>
                    </ul>
                </div>

                @if(in_array($tagihan->status, ['DRAFT', 'DITOLAK_PPK']))
                    <a href="{{ route('honorarium.edit', $tagihan->id) }}" class="btn btn-primary shadow-sm">
                        <i class="bi bi-pencil-square"></i> Edit Data
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="card-body bg-light">
        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-3 text-center">
                        <p class="text-muted mb-1 small">Total Bruto</p>
                        <h6 class="mb-0 fw-bold">Rp {{ number_format($tagihan->detailHonorarium->sum('nilai_honor'), 0, ',', '.') }}</h6>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-3 text-center">
                        <p class="text-muted mb-1 small">Total PPh</p>
                        <h6 class="mb-0 fw-bold text-danger">Rp {{ number_format($tagihan->detailHonorarium->sum('pph'), 0, ',', '.') }}</h6>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm bg-primary bg-opacity-10">
                    <div class="card-body p-3 text-center">
                        <p class="text-primary mb-1 small fw-bold">Total Netto</p>
                        <h5 class="mb-0 fw-bold text-primary">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</h5>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-3 text-center">
                        <p class="text-muted mb-1 small">Jumlah Penerima</p>
                        <h6 class="mb-0 fw-bold">{{ $tagihan->detailHonorarium->count() }} Orang</h6>
                    </div>
                </div>
            </div>
        </div>

        {{-- Utama Informasi --}}
        <div class="card border-0 shadow-sm mb-0">
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-8">
                        <p class="text-muted small text-uppercase mb-1">Uraian / Deskripsi Kegiatan</p>
                        <p class="fw-medium mb-4">{{ $tagihan->deskripsi }}</p>

                        <p class="text-muted small text-uppercase mb-1">Sumber Anggaran / DIPA</p>
                        <p class="fw-medium mb-4">
                            {{ $tagihan->sumber_dana ?? 'Sumber Dana / DIPA (belum tersedia)' }}
                        </p>

                        <p class="text-muted small text-uppercase mb-1">Dokumen Pendukung (SK Honorarium)</p>
                        @php
                            $skDoc = $tagihan->arsipDokumen->where('jenis_dokumen', 'SK Honorarium')->first();
                        @endphp
                        @if($skDoc)
                            <a href="{{ Storage::url($skDoc->path_file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-file-earmark-pdf me-1"></i> {{ $skDoc->nama_file_asli }}
                            </a>
                        @else
                            <p class="fw-medium text-muted small"><i class="bi bi-dash-circle me-1"></i> Tidak ada file SK yang dilampirkan</p>
                        @endif
                    </div>
                    <div class="col-md-4 border-start d-flex flex-column justify-content-center">
                        <p class="text-muted small text-uppercase mb-1 text-md-end">Tanggal Dibuat</p>
                        <p class="fw-medium mb-4 text-md-end">{{ $tagihan->created_at ? $tagihan->created_at->format('d F Y') : '-' }}</p>
                        
                        <p class="text-muted small text-uppercase mb-1 text-md-end">Terakhir Diperbarui</p>
                        <p class="fw-medium mb-0 text-md-end">{{ $tagihan->updated_at ? $tagihan->updated_at->format('d F Y, H:i') : '-' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tabel Rincian --}}
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white pt-4 pb-3">
        <h6 class="card-title fw-bold mb-0">Daftar Rincian Penerima</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light text-muted small">
                    <tr>
                        <th class="ps-4">No</th>
                        <th>Data Personel</th>
                        <th>Rekening Bank</th>
                        <th class="text-end">Honor Bruto</th>
                        <th class="text-end">PPh</th>
                        <th class="text-end pe-4">Netto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tagihan->detailHonorarium as $detail)
                        <tr>
                            <td class="ps-4">{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-bold">{{ $detail->nama_personel ?? '-' }}</div>
                                <div class="small text-muted">{{ $detail->nrp_nip ?? '-' }}</div>
                                <div class="small text-muted">{{ $detail->pangkat_korp ?? '-' }} &bull; {{ $detail->jabatan ?? '-' }}</div>
                            </td>
                            <td>
                                <div>{{ $detail->nama_rekening }}</div>
                                <div class="small text-muted">{{ $detail->jenis_bank }} &bull; {{ $detail->rekening }}</div>
                            </td>
                            <td class="text-end text-muted">Rp {{ number_format($detail->nilai_honor, 0, ',', '.') }}</td>
                            <td class="text-end text-danger small">Rp {{ number_format($detail->pph, 0, ',', '.') }}</td>
                            <td class="text-end pe-4 fw-bold text-success">Rp {{ number_format($detail->nilai_honor - $detail->pph, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada rincian penerima yang ditambahkan.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="3" class="text-end fw-bold ps-4">TOTAL KESELURUHAN</td>
                        <td class="text-end fw-bold">Rp {{ number_format($tagihan->detailHonorarium->sum('nilai_honor'), 0, ',', '.') }}</td>
                        <td class="text-end fw-bold text-danger">Rp {{ number_format($tagihan->detailHonorarium->sum('pph'), 0, ',', '.') }}</td>
                        <td class="text-end fw-bold text-primary pe-4">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
</div>

{{-- PANEL PROGRESS VERIFIKASI PARALEL --}}
@if($tagihan->status !== 'DRAFT')
    @php
        $approvalStatusByRole = collect();
        if ($activeWorkflowInstance ?? null) {
            $approvalStatusByRole = collect($activeWorkflowInstance->approvals ?? [])
                ->keyBy(fn ($a) => strtoupper(str_replace([' ', '-'], '_', $a->role_code)));
        }

        $approvalMeta = [
            'PENDING'  => ['cls' => 'pending',  'icon' => 'hourglass-split',         'label' => 'Menunggu',   'color' => 'warning'],
            'APPROVED' => ['cls' => 'approved', 'icon' => 'check-circle-fill',       'label' => 'Disetujui',  'color' => 'success'],
            'REVISION' => ['cls' => 'revision', 'icon' => 'arrow-counterclockwise',  'label' => 'Revisi',     'color' => 'danger'],
            'REJECTED' => ['cls' => 'rejected', 'icon' => 'x-circle-fill',           'label' => 'Ditolak',    'color' => 'danger'],
            'WAITING'  => ['cls' => 'waiting',  'icon' => 'clock-history',           'label' => 'Belum aktif','color' => 'secondary'],
        ];

        $verifikatorList = [
            ['key' => 'ppk',                  'role_code' => 'PPK',                  'label' => 'Pejabat Pembuat Komitmen',                 'short' => 'PPK',          'color' => '#0d6efd', 'nama' => $tagihan->ppk_nama_snapshot,                  'nip' => $tagihan->ppk_nip_snapshot],
            ['key' => 'ppspm',                'role_code' => 'PPSPM',                'label' => 'PPSPM',                                    'short' => 'PPSPM',        'color' => '#6610f2', 'nama' => $tagihan->ppspm_nama_snapshot,                'nip' => $tagihan->ppspm_nip_snapshot],
            ['key' => 'bendahara_pengeluaran','role_code' => 'BENDAHARA_PENGELUARAN','label' => 'Bendahara Pengeluaran',                    'short' => 'BEND. KELUAR', 'color' => '#d63384', 'nama' => $tagihan->bendahara_pengeluaran_nama_snapshot,'nip' => $tagihan->bendahara_pengeluaran_nip_snapshot],
            ['key' => 'bendahara_penerimaan', 'role_code' => 'BENDAHARA_PENERIMAAN', 'label' => 'Bendahara Penerimaan',                     'short' => 'BEND. TERIMA', 'color' => '#fd7e14', 'nama' => $tagihan->bendahara_penerimaan_nama_snapshot, 'nip' => $tagihan->bendahara_penerimaan_nip_snapshot],
            ['key' => 'koordinator_keuangan', 'role_code' => 'KOORDINATOR_KEUANGAN', 'label' => 'Koordinator Keuangan',                     'short' => 'KOOR. KEU',    'color' => '#198754', 'nama' => $tagihan->koordinator_keuangan_nama_snapshot, 'nip' => $tagihan->koordinator_keuangan_nip_snapshot],
            ['key' => 'kasubbag',             'role_code' => 'KASUBBAG',             'label' => 'Kepala Subbagian Keuangan dan Tata Usaha', 'short' => 'KASUBBAG',     'color' => '#0dcaf0', 'nama' => $tagihan->kasubbag_nama_snapshot,             'nip' => $tagihan->kasubbag_nip_snapshot],
        ];

        $initials = function ($name) {
            $name = trim((string) $name);
            if ($name === '') return '?';
            $parts = preg_split('/\s+/', $name);
            $first = mb_substr($parts[0] ?? '', 0, 1);
            $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
            return mb_strtoupper($first . $last);
        };

        $step1Approvals = collect($activeWorkflowInstance?->approvals ?? [])->where('urutan_step', 1);
        $step1Total = $step1Approvals->count();
        $step1Approved = $step1Approvals->where('status', 'APPROVED')->count();
    @endphp
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom pt-4 px-4 pb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-diagram-3 me-2"></i>Progress Verifikasi (Paralel)</h5>
                <p class="text-muted small mb-0 mt-1">Daftar pejabat penanda tangan dokumen — diurutkan sesuai alur verifikasi</p>
            </div>
            @if($step1Total > 0)
                <div class="text-end">
                    <span class="badge {{ $step1Approved === $step1Total ? 'bg-success' : 'bg-info' }} fs-6">
                        <i class="bi bi-{{ $step1Approved === $step1Total ? 'check-circle' : 'people-fill' }} me-1"></i>
                        {{ $step1Approved }}/{{ $step1Total }} Step 1 disetujui
                    </span>
                </div>
            @endif
        </div>
        <div class="card-body p-4">
            @if($step1Total > 0 && $step1Approved < $step1Total)
                <div class="progress mb-4" style="height: 6px;">
                    <div class="progress-bar bg-info" style="width: {{ round(($step1Approved / $step1Total) * 100) }}%"></div>
                </div>
            @endif

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
                                        @if($v['key'] === 'kasubbag')
                                            <span class="role-chip" style="background: #e9ecef; color: #495057;" title="Verifikasi final setelah Step 1 selesai">
                                                <i class="bi bi-shield-check"></i> final
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
                                            <i class="bi bi-clock me-1"></i>{{ \Carbon\Carbon::parse($approval->acted_at)->format('d M Y H:i') }}
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
        </div>
    </div>
@endif

{{-- Dokumen Wajib & Pengajuan Verifikasi --}}
<div class="card mb-4 shadow-sm border-0">
    <div class="card-header bg-white pt-4 pb-3">
        <h6 class="card-title fw-bold mb-0 text-primary"><i class="bi bi-file-earmark-check me-2"></i>Unggah Dokumen Wajib & Pengajuan Verifikasi</h6>
    </div>
    <div class="card-body p-4 bg-light">
        @php
            $uploadedTypes = $tagihan->arsipDokumen->pluck('jenis_dokumen')->toArray();
            $hasDaftarNominatif = in_array('Daftar Nominatif Bertandatangan', $uploadedTypes);
            $hasDokumenHonorarium = in_array('Dokumen Honorarium Bertandatangan', $uploadedTypes);
            $isReady = $hasDaftarNominatif && $hasDokumenHonorarium;
        @endphp

        @if($tagihan->status === 'DRAFT')
            <div class="alert alert-warning border-warning d-flex align-items-center mb-4 p-3 shadow-sm rounded">
                <i class="bi bi-exclamation-triangle-fill fs-3 text-warning me-3"></i>
                <div>
                    <strong>Perhatian!</strong> Anda wajib mengunggah salinan PDF pindaian (scan) dari <strong>Daftar Nominatif</strong> dan <strong>Dokumen Honorarium</strong> yang telah ditandatangani sebelum bisa mengajukan verifikasi ke tahap selanjutnya.
                </div>
            </div>

            @if(!$isReady)
            <div class="card border border-primary shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-cloud-upload"></i> Form Upload Dokumen Wajib</h6>
                    <form action="{{ route('honorarium.dokumen.upload-wajib', $tagihan->id) }}" method="POST" enctype="multipart/form-data" id="formUploadWajib">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-primary">1. Daftar Nominatif Bertandatangan</label>
                                @if($hasDaftarNominatif)
                                    <div class="form-control bg-success bg-opacity-10 text-success fw-semibold d-flex align-items-center gap-2">
                                        <i class="bi bi-check-circle-fill"></i> Sudah diunggah
                                    </div>
                                @else
                                    <input type="file" name="file_nominatif" id="fileNominatif" accept=".pdf" class="form-control upload-wajib-input" required>
                                    <small class="text-muted d-block mt-1"><i class="bi bi-info-circle"></i> Format PDF (Maks 10MB)</small>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-primary">2. Dokumen Honorarium Bertandatangan</label>
                                @if($hasDokumenHonorarium)
                                    <div class="form-control bg-success bg-opacity-10 text-success fw-semibold d-flex align-items-center gap-2">
                                        <i class="bi bi-check-circle-fill"></i> Sudah diunggah
                                    </div>
                                @else
                                    <input type="file" name="file_honorarium" id="fileHonorarium" accept=".pdf" class="form-control upload-wajib-input" required>
                                    <small class="text-muted d-block mt-1"><i class="bi bi-info-circle"></i> Format PDF (Maks 10MB)</small>
                                @endif
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary fw-bold w-100" id="btnUploadWajib" disabled>
                                    <i class="bi bi-upload me-1"></i> Unggah Dokumen Wajib
                                </button>
                            </div>
                        </div>
                    </form>

                    <script>
                    (function () {
                        const inputs = document.querySelectorAll('.upload-wajib-input');
                        const btn = document.getElementById('btnUploadWajib');
                        if (!btn) return;

                        function toggle() {
                            const allFilled = Array.from(inputs).every(i => i.files && i.files.length > 0);
                            btn.disabled = !allFilled;
                        }

                        inputs.forEach(i => i.addEventListener('change', toggle));
                        toggle();
                    })();
                    </script>
                </div>
            </div>
            @endif
        @endif

        <h6 class="fw-bold mb-3"><i class="bi bi-folder-check text-success me-2"></i>Dokumen Tersimpan:</h6>
        <div class="table-responsive mb-4">
            <table class="table table-bordered bg-white shadow-sm mb-0">
                <thead class="table-light text-center small">
                    <tr>
                        <th width="50" class="py-3">No</th>
                        <th class="py-3">Jenis Dokumen</th>
                        <th class="py-3">Nama File</th>
                        <th class="py-3">Waktu Unggah</th>
                        <th width="120" class="py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="align-middle">
                    @forelse($tagihan->arsipDokumen as $index => $arsip)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="fw-medium text-dark">{{ $arsip->jenis_dokumen }}</td>
                            <td>{{ $arsip->nama_file_asli }}</td>
                            <td class="text-center small text-muted">{{ $arsip->created_at->format('d M Y H:i') }}</td>
                            <td class="text-center">
                                <a href="{{ Storage::url($arsip->path_file) }}" target="_blank" class="btn btn-sm btn-outline-info" title="Unduh File">
                                    <i class="bi bi-download"></i>
                                </a>
                                @if($tagihan->status === 'DRAFT')
                                    <form action="{{ route('honorarium.dokumen.delete', ['id' => $tagihan->id, 'arsip_id' => $arsip->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus dokumen ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus File"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4"><i class="bi bi-inbox-fill text-black-50 me-2 fs-5"></i> Belum ada dokumen yang diunggah.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tagihan->status === 'DRAFT')

            <div class="d-flex flex-column align-items-end border-top pt-4">
                <form action="{{ route('honorarium.submit-verifikasi', $tagihan->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin akan mengajukan tagihan honorarium ini untuk verifikasi? Pastikan form tidak ada yang salah karena tidak dapat diedit lagi setelah pengajuan.')">
                    @csrf
                    <button type="submit" class="btn {{ $isReady ? 'btn-success' : 'btn-secondary disabled' }} btn-lg px-4 fw-bold shadow-sm" {{ !$isReady ? 'disabled' : '' }}>
                        <i class="bi bi-send-check me-2"></i> Ajukan Verifikasi
                    </button>
                </form>
                @if(!$isReady)
                    <div class="text-end text-danger mt-2 small fw-medium">
                        * Tombol pengajuan akan aktif setelah Anda mengunggah <strong>2 (dua) dokumen wajib</strong> di atas.
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

{{-- Riwayat Proses / Audit Trail --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-white pt-4 pb-3 border-bottom">
        <h6 class="card-title fw-bold mb-0">Riwayat Proses Dokumen</h6>
    </div>
    <div class="card-body p-4">
        @if($tagihan->logs->isEmpty())
            <div class="alert alert-light text-center text-muted mb-0 border"><small>Belum ada riwayat aktivitas tercatat.</small></div>
        @else
            <div class="position-relative">
                <ul class="list-group list-group-flush border-start border-2 border-primary ms-3">
                    @foreach($tagihan->logs as $log)
                        <li class="list-group-item bg-transparent border-0 position-relative pb-4">
                            <span class="position-absolute bg-primary rounded-circle border border-white border-2"
                                  style="width: 14px; height: 14px; left: -8px; top: 18px;"></span>
                            <div class="ps-3 pt-1 border-bottom pb-3">
                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-2">
                                    <strong class="text-primary fs-6">{{ str_replace('_', ' ', $log->status_baru) }}</strong>
                                    <span class="badge bg-light text-secondary border mt-1 mt-sm-0"><i class="bi bi-clock"></i> {{ $log->created_at->format('d M Y, H:i:s') }}</span>
                                </div>
                                <div class="text-muted small">Diperbarui oleh Sistem/User</div>
                                
                                @if($log->catatan)
                                    @php
                                        // Pembedaan warna catatan: jika terkait penolakan (misal status baru DITOLAK_PPK), beri warna alert warning kemerahan
                                        $isRejection = strpos($log->status_baru, 'DITOLAK') !== false;
                                        $noteClass = $isRejection ? 'bg-danger bg-opacity-10 border-danger text-danger' : 'bg-light border-secondary text-dark';
                                    @endphp
                                    <div class="mt-3 p-3 border-start border-3 rounded {{ $noteClass }}">
                                        <strong>Catatan:</strong><br>
                                        {{ $log->catatan }}
                                    </div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
@endsection