@extends('layouts.app')

@section('title', 'Detail Honorarium')

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
        $ppkStatus = $ppkApproval?->status ?? 'N/A';
        $bendaharaStatus = $bendaharaApproval?->status ?? 'N/A';
        $badgeClass = fn($s) => match($s) {
            'APPROVED' => 'bg-success',
            'PENDING' => 'bg-warning text-dark',
            'REVISION', 'REJECTED' => 'bg-danger',
            default => 'bg-light text-dark border',
        };
    @endphp
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body p-4">
            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-diagram-3 me-2"></i> Progress Verifikasi (Paralel)</h6>
            <div class="row justify-content-center border py-4 rounded bg-light">
                {{-- PPABP (Submitter) --}}
                <div class="col-4 position-relative">
                    <div class="text-center rounded mx-auto border-success bg-success bg-opacity-10 d-flex flex-column justify-content-center p-3" style="max-width: 280px; height: 100%;">
                        <div class="fw-bold text-success mb-1" style="font-size: 14px;">Operator PPABP</div>
                        <div class="text-muted mb-2" style="font-size: 12px;">{{ $tagihan->creator?->name ?? 'SYSTEM' }}</div>
                        <span class="badge bg-success mx-auto">DIAJUKAN</span>
                    </div>
                    <div class="position-absolute align-items-center d-flex fw-bold text-success" style="right: -10px; top: 50%; transform: translateY(-50%); font-size:24px;">
                        <i class="bi bi-arrow-right"></i>
                    </div>
                </div>

                <div class="col-8">
                    <div class="row h-100 g-3">
                        {{-- PPK --}}
                        <div class="col-sm-6">
                            <div class="border rounded p-3 text-center h-100 {{ $ppkStatus === 'APPROVED' ? 'border-success bg-success bg-opacity-10' : ($ppkStatus === 'PENDING' ? 'border-warning bg-warning bg-opacity-10' : (in_array($ppkStatus, ['REVISION','REJECTED']) ? 'border-danger bg-danger bg-opacity-10' : '')) }}">
                                <div class="fw-bold mb-1" style="font-size: 13px;">Pejabat Pembuat Komitmen</div>
                                <div class="text-muted mb-2" style="font-size: 11px;">{{ $ppkApproval?->assignedUser?->name ?? 'Verifikator PPK' }}</div>
                                <span class="badge {{ $badgeClass($ppkStatus) }}">{{ str_replace('_', ' ', $ppkStatus) }}</span>
                                @if($ppkApproval?->acted_at)
                                    <div class="mt-2" style="font-size: 11px; color: #6c757d;">{{ \Carbon\Carbon::parse($ppkApproval->acted_at)->format('d M Y H:i') }}</div>
                                @endif
                            </div>
                        </div>

                        {{-- Bendahara --}}
                        <div class="col-sm-6">
                            <div class="border rounded p-3 text-center h-100 {{ $bendaharaStatus === 'APPROVED' ? 'border-success bg-success bg-opacity-10' : ($bendaharaStatus === 'PENDING' ? 'border-warning bg-warning bg-opacity-10' : (in_array($bendaharaStatus, ['REVISION','REJECTED']) ? 'border-danger bg-danger bg-opacity-10' : '')) }}">
                                <div class="fw-bold mb-1" style="font-size: 13px;">Bendahara Pengeluaran</div>
                                <div class="text-muted mb-2" style="font-size: 11px;">{{ $bendaharaApproval?->assignedUser?->name ?? 'Verifikator Bendahara' }}</div>
                                <span class="badge {{ $badgeClass($bendaharaStatus) }}">{{ str_replace('_', ' ', $bendaharaStatus) }}</span>
                                @if($bendaharaApproval?->acted_at)
                                    <div class="mt-2" style="font-size: 11px; color: #6c757d;">{{ \Carbon\Carbon::parse($bendaharaApproval->acted_at)->format('d M Y H:i') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
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
                    <div class="row g-4">
                        @if(!$hasDaftarNominatif)
                        <div class="col-md-6">
                            <form action="{{ route('honorarium.dokumen.upload', $tagihan->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="jenis_dokumen" value="Daftar Nominatif Bertandatangan">
                                <label class="form-label fw-semibold text-primary">1. Daftar Nominatif Bertandatangan</label>
                                <div class="input-group input-group-sm">
                                    <input type="file" name="file_dokumen" class="form-control" accept=".pdf" required>
                                    <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-upload"></i> Unggah</button>
                                </div>
                                <small class="text-muted d-block mt-1"><i class="bi bi-info-circle"></i> Format PDF (Maks 10MB)</small>
                            </form>
                        </div>
                        @endif

                        @if(!$hasDokumenHonorarium)
                        <div class="col-md-6">
                            <form action="{{ route('honorarium.dokumen.upload', $tagihan->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="jenis_dokumen" value="Dokumen Honorarium Bertandatangan">
                                <label class="form-label fw-semibold text-primary">2. Dokumen Honorarium Bertandatangan</label>
                                <div class="input-group input-group-sm">
                                    <input type="file" name="file_dokumen" class="form-control" accept=".pdf" required>
                                    <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-upload"></i> Unggah</button>
                                </div>
                                <small class="text-muted d-block mt-1"><i class="bi bi-info-circle"></i> Format PDF (Maks 10MB)</small>
                            </form>
                        </div>
                        @endif
                    </div>
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