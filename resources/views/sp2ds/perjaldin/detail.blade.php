@extends('layouts.app')

@section('content')
<!-- Header/Breadcrumb -->
<div class="row mb-3">
    <div class="col-12 col-md-8">
        <h5 class="mb-0 text-primary fw-bold">Detail Pencatatan SP2D Perjaldin</h5>
        <div class="text-muted font-13">NPI: {{ $npi->nomor_npi }}</div>
    </div>
    <div class="col-12 col-md-4 text-md-end mt-2 mt-md-0">
        <a href="{{ route('sp2ds.perjaldin.index') }}" class="btn btn-outline-secondary btn-sm"><i class="material-icons-outlined" style="font-size: 16px; margin-bottom: -3px;">arrow_back</i> Kembali ke Antrean</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row">
    <!-- KOLOM KIRI (Data & Tabel Rincian) -->
    <div class="col-12 col-lg-8">
        
        <!-- 1. Header Status SP2D & Rekap Nilai -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">1. Ringkasan Dokumen (Netto)</h6>
        <div class="card radius-10 mb-4 border-top border-0 border-4 border-primary shadow-sm bg-primary bg-opacity-10">
            <div class="card-body">
                <div class="row align-items-center mb-3 pb-3 border-bottom border-light">
                    <div class="col-sm-6 mb-3 mb-sm-0">
                        <small class="text-muted">Nomor SP2D Saat Ini</small>
                        <h4 class="mb-0 text-primary fw-bold">{{ $sp2d?->nomor_sp2d ?? 'Draft (Belum Dinyatakan)' }}</h4>
                        <div class="font-12 mt-1">Status SP2D: 
                            <span class="badge {{ $sp2d ? ($sp2d->status == 'DISETUJUI_FINAL' ? 'bg-success' : ($sp2d->status == 'MENUNGGU_VERIFIKASI' ? 'bg-primary' : 'bg-warning text-dark')) : 'bg-secondary' }}">
                                {{ $sp2d->status ?? 'BELUM_DIBUAT' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row text-center text-sm-start g-3">
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Nilai Tagihan Bruto</small>
                        <span class="fw-bold font-14">Rp {{ number_format($tagihan?->total_tagihan ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Nilai SPP / SPM</small>
                        <span class="fw-bold font-14">Rp {{ number_format($defaultNilai, 0, ',', '.') }}</span>
                    </div>
                    <div class="col-6 col-md-3 border-start">
                        <small class="text-muted d-block">Dasar NPI Final</small>
                        <span class="fw-bold fs-6 text-success">Rp {{ number_format($spm?->nominal_spm ?? 0, 0, ',', '.') }}</span>
                        @if(in_array($npi->status, [\App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL, \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG]))
                            <i class="material-icons-outlined text-success font-14" title="NPI Sudah Diverifikasi">verified</i>
                        @endif
                    </div>
                    <div class="col-6 col-md-3 border-start bg-white p-2 rounded shadow-sm">
                        <small class="text-muted d-block">Nilai SP2D (Draft)</small>
                        <span class="fw-bold fs-5 text-primary">Rp {{ number_format($sp2d?->nilai_sp2d ?? $defaultNilai, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Informasi Dokumen Sumber -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">2. Penelusuran Dokumen Anggaran</h6>
        <div class="card radius-10 shadow-sm mb-4">
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-4 py-3 bg-light">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <div class="text-muted font-11">Beban COA (DIPA)</div>
                                <div class="fw-bold font-12">
                                    @if($komponen?->dipaRevisionItem?->coa)
                                        <span class="badge bg-dark">{{ $komponen->dipaRevisionItem->coa->kode_akun }}</span>
                                        {{ Str::limit($komponen->dipaRevisionItem->coa->nama_akun, 30) }}
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4 mt-2 mt-md-0">
                                <div class="text-muted font-11">Bendahara Penerimaan</div>
                                <div class="fw-bold font-12"><i class="material-icons-outlined font-14" style="vertical-align: sub;">person</i> {{ $npi->bendaharaPenerimaan?->name ?? '-' }}</div>
                            </div>
                            <div class="col-md-4 mt-2 mt-md-0 text-md-end">
                                <div class="text-muted font-11">Tagihan Perjaldin Tujuan</div>
                                <div class="fw-bold font-12 text-wrap">{{ Str::limit($tagihan?->deskripsi ?? '-', 50) }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="list-group-item px-4 py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="me-3 p-2 bg-light rounded text-center" style="width: 50px;">
                                <div class="font-10 text-muted text-uppercase">Tgl NPI</div>
                                <div class="font-12 fw-bold">{{ optional($npi->tanggal_npi)->format('d M') ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="fw-bold text-dark mb-0 form-label">NPI Pemindahbukuan Internal</div>
                                <div class="text-muted font-13">{{ $npi->nomor_npi }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item px-4 py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="me-3 p-2 bg-light rounded text-center" style="width: 50px;">
                                <div class="font-10 text-muted text-uppercase">Tgl SPM</div>
                                <div class="font-12 fw-bold">{{ optional($spm?->tanggal_spm)->format('d M') ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="fw-bold text-dark mb-0 form-label">Dokumen SPM</div>
                                <div class="text-muted font-13">{{ $spm?->nomor_spm ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item px-4 py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="me-3 p-2 bg-light rounded text-center" style="width: 50px;">
                                <div class="font-10 text-muted text-uppercase">Tgl SPP</div>
                                <div class="font-12 fw-bold">{{ optional($spp?->tanggal_spp)->format('d M') ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="fw-bold text-dark mb-0 form-label">Surat Permintaan Pembayaran (SPP)</div>
                                <div class="text-muted font-13">{{ $spp?->nomor_spp ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Rincian Peserta -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">3. Rekapitulasi Pembayaran per Peserta</h6>
        <div class="card radius-10 shadow-sm mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped font-11 mb-0 align-middle">
                        <thead class="table-light align-middle">
                            <tr>
                                <th>Peserta (NIP)</th>
                                <th>Tujuan & Berangkat</th>
                                <th>Lama</th>
                                <th class="text-end">Tr & Tiket</th>
                                <th class="text-end">Pengp. & UH</th>
                                <th class="text-end border-end">Repr</th>
                                <th class="text-end text-primary fw-bold">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tagihan?->detailPerjaldin ?? [] as $det)
                            @php
                                $subtotal = ($det->biaya_tiket ?? 0) + ($det->biaya_transport ?? 0) + ($det->biaya_penginapan ?? 0) + ($det->uang_harian ?? 0) + ($det->uang_representasi ?? 0);
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $det->nama_pegawai ?: ($det->pegawai?->nama_lengkap ?? '-') }}</div>
                                    <div class="text-muted text-opacity-50" style="font-size:9px;">{{ $det->nip ?: ($det->pegawai?->nip ?? '-') }}</div>
                                </td>
                                <td>
                                    <div>{{ $det->tujuan }} ({{ $det->provinsi?->provinsi ?? '-' }})</div>
                                    <div class="text-muted font-10">{{ $det->tgl_berangkat ? \Carbon\Carbon::parse($det->tgl_berangkat)->format('d/m/Y') : '-' }}</div>
                                </td>
                                <td>{{ $det->lama_hari }} hr</td>
                                <td class="text-end">
                                    Tk: {{ number_format($det->biaya_tiket ?? 0, 0, ',', '.') }}<br>
                                    Tr: {{ number_format($det->biaya_transport ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    Pg: {{ number_format($det->biaya_penginapan ?? 0, 0, ',', '.') }}<br>
                                    UH: {{ number_format($det->uang_harian ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="text-end border-end">{{ number_format($det->uang_representasi ?? 0, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold font-12 text-primary">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted">Belum ada rincian peserta Perjaldin.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="6" class="text-end fw-bold">TOTAL NOMINAL KESELURUHAN:</th>
                                <th class="text-end fw-bold text-primary font-14">Rp {{ number_format($defaultNilai, 0, ',', '.') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- KOLOM KANAN (Aksi, Form SP2D, Checklist) -->
    <div class="col-12 col-lg-4">

        <!-- A. FORM SP2D (Hanya muncul jika DRAFT/REVISI/BLM DIBUAT) -->
        @if(!$sp2d || in_array($sp2d->status, [\App\Models\DokumenSp2d::STATUS_DRAFT, \App\Models\DokumenSp2d::STATUS_REVISI]))
            <div class="card radius-10 shadow-sm border-0 border-top border-4 border-info mb-4">
                <div class="card-header bg-white px-4 py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="material-icons-outlined text-info me-2" style="vertical-align: sub;">edit_document</i> Form Pengisian SP2D</h6>
                </div>
                <div class="card-body px-4 pb-4">
                    <form action="{{ route('sp2ds.perjaldin.store', $npi->id) }}" method="POST" id="form-draft-sp2d">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nomor SP2D <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_sp2d" class="form-control fw-bold text-primary bg-light" value="{{ old('nomor_sp2d', $sp2d?->nomor_sp2d ?? $autoNomorSp2d) }}" required placeholder="Contoh: 12345/SP2D/2026">
                            <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Nomor di atas diturunkan dari SPP, ubah jika perlu.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tanggal SP2D <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_sp2d" class="form-control" value="{{ old('tanggal_sp2d', $sp2d?->tanggal_sp2d?->format('Y-m-d') ?? date('Y-m-d')) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nilai SP2D (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">Rp</span>
                                <input type="number" step="0.01" min="0" name="nilai_sp2d" class="form-control border-start-0 ps-0" value="{{ old('nilai_sp2d', floatval($sp2d?->nilai_sp2d ?? $defaultNilai)) }}" required>
                            </div>
                            <div class="form-text font-10 text-muted">Nilai tercantum di atas mengikuti Netto NPI/SPM. Dapat disesuaikan jika ada selisih biaya admin bank khusus.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tahun Anggaran <span class="text-danger">*</span></label>
                            <input type="text" name="tahun_anggaran" class="form-control" value="{{ old('tahun_anggaran', $sp2d?->tahun_anggaran ?? $defaultTahun) }}" required maxlength="4">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Catatan Pembukuan <small class="text-muted">(Opsional)</small></label>
                            <textarea name="catatan" rows="2" class="form-control" placeholder="Tulis catatan jika ada...">{{ old('catatan', $sp2d?->catatan ?? '') }}</textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-outline-primary fw-bold w-100">
                                <i class="material-icons-outlined" style="font-size: 16px; margin-bottom: -3px;">save</i> 
                                Simpan Referensi SP2D (Draft)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @else
            <!-- B. PANEL CETAK & STATUS (Jika Sudah Final/Verifikasi) -->
            <div class="card radius-10 shadow-sm border-0 bg-light mb-4 text-center p-3">
                @if($sp2d->status === 'DISETUJUI_FINAL' || $sp2d->status === 'EXECUTED')
                    <i class="material-icons-outlined fs-1 text-success mb-2">check_circle</i>
                    <h6 class="fw-bold text-success">SP2D Disetujui & Selesai</h6>
                    <p class="text-muted font-12 mb-3">Dokumen telah Diverifikasi Paralel. Tersedia untuk dicetak/arsip.</p>
                    <a href="{{ route('sp2ds.perjaldin.cetak', $sp2d->id) }}" target="_blank" class="btn btn-danger btn-sm px-4 fw-bold rounded-pill mx-auto">
                        <i class="material-icons-outlined font-14" style="vertical-align: sub;">print</i> Cetak PDF SP2D
                    </a>
                @elseif($sp2d->status === 'MENUNGGU_VERIFIKASI')
                    <i class="material-icons-outlined fs-1 text-primary mb-2">pending_actions</i>
                    <h6 class="fw-bold text-primary">Sedang Diverifikasi Paralel</h6>
                    <p class="text-muted font-12 mb-0">Menunggu PPK dan Kasubbag memverifikasi draft ini.</p>
                @endif
            </div>
        @endif

        <!-- C. Panel Kesiapan (Checklist Ajukan SP2D) -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">Validasi Pengajuan SP2D</h6>
        <div class="card radius-10 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex flex-column gap-2 font-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted">Status NPI Disetujui Final / Selesai</span>
                        @if($checks['npi_final']) <i class="material-icons-outlined text-success font-18">check_circle</i>
                        @else <i class="material-icons-outlined text-danger font-18">cancel</i> @endif
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted">Lampiran Dokumen Dasar (SPP & SPM) Valid</span>
                        @if($checks['spp_tersedia'] && $checks['spm_tersedia']) <i class="material-icons-outlined text-success font-18">check_circle</i>
                        @else <i class="material-icons-outlined text-danger font-18">cancel</i> @endif
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted">Rincian Perjalanan & Peserta Terdata</span>
                        @if($checks['tagihan_ada'] && $checks['peserta_ada']) <i class="material-icons-outlined text-success font-18">check_circle</i>
                        @else <i class="material-icons-outlined text-danger font-18">cancel</i> @endif
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted">Draft SP2D Berhasil Disimpan (Memiliki Referensi)</span>
                        @if($checks['sp2d_valid'] && $checks['sp2d_tersimpan']) <i class="material-icons-outlined text-success font-18">check_circle</i>
                        @else <i class="material-icons-outlined text-danger font-18">cancel</i> @endif
                    </div>
                </div>

                <hr class="my-3">

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="fw-bold">Status Persiapan:</span>
                    @if($isLengkap)
                        <span class="badge bg-success">LENGKAP</span>
                    @else
                        <span class="badge bg-warning text-dark">BELUM LENGKAP</span>
                    @endif
                </div>

                <!-- Submit Button -->
                <form action="{{ route('sp2ds.perjaldin.submit', $npi->id) }}" method="POST" id="form-submit-sp2d" onsubmit="return confirm('Apakah Anda yakin ingin mengajukan pencairan SP2D ini ke tahap verifikasi PPK, Kasubbag, PPSPM, dan Koordinator Keuangan?')">
                    @csrf
                    <button type="submit" class="btn btn-success fw-bold w-100" 
                        {{ (!$isLengkap || ($sp2d && !in_array($sp2d->status, [\App\Models\DokumenSp2d::STATUS_DRAFT, \App\Models\DokumenSp2d::STATUS_REVISI]))) ? 'disabled' : '' }}>
                        <i class="material-icons-outlined" style="font-size: 18px; margin-bottom: -3px;">send</i> Ajukan SP2D Sekarang
                    </button>
                    @if(!$isLengkap && (!$sp2d || in_array($sp2d->status, [\App\Models\DokumenSp2d::STATUS_DRAFT, \App\Models\DokumenSp2d::STATUS_REVISI])))
                        <div class="text-danger font-10 mt-2 text-center">Lengkapi Draft Parameter SP2D di atas terlebih dahulu.</div>
                    @endif
                </form>
            </div>
        </div>

        <!-- D. Timeline Paralel Workflow SP2D -->
        @if($sp2d && $wf)
        <h6 class="text-uppercase text-secondary fw-bold mb-2">Antrean Verifikasi SP2D</h6>
        <div class="card radius-10 shadow-sm mb-4">
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($wf->approvals as $approval)
                    <li class="list-group-item d-flex justify-content-between align-items-start py-3">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold font-13">{{ $approval->role_code }}</div>
                            <div class="text-muted font-11">{{ $approval->assignedUser?->name ?? 'Semua ' . $approval->role_code }}</div>
                            @if($approval->catatan)
                                <div class="text-muted font-11 mt-1 fst-italic">"{{ $approval->catatan }}"</div>
                            @endif
                        </div>
                        <span class="badge @if($approval->status=='APPROVED') bg-success @elseif($approval->status=='REVISION') bg-danger @else bg-warning text-dark @endif rounded-pill" style="font-size:9px;">
                            {{ $approval->status }}
                        </span>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        @if($sp2d && $sp2d->logs && $sp2d->logs->count() > 0)
            <div class="card radius-10 shadow-sm">
                <div class="card-header bg-white pb-0 border-0 pt-3">
                    <h6 class="text-uppercase text-muted fw-bold mb-0 font-12"><i class="material-icons-outlined font-14 me-1">history</i> Riwayat Log SP2D</h6>
                </div>
                <div class="card-body">
                    <div class="order-scroll" style="max-height: 250px; overflow-y: auto;">
                        @foreach($sp2d->logs as $log)
                            <div class="d-flex align-items-start mb-3 pb-2 border-bottom">
                                <div class="flex-grow-1">
                                    <span class="badge bg-light text-dark font-10 mb-1 border border-secondary">{{ $log->aksi }}</span>
                                    <h6 class="mb-0 font-12 fw-bold text-wrap">{{ $log->catatan }}</h6>
                                    <div class="text-muted font-10 my-1">Oleh: <strong>{{ $log->user?->name ?? 'Sistem' }}</strong> ({{ $log->role_saat_itu }})</div>
                                </div>
                                <div class="text-end ms-2">
                                    <span class="text-muted font-10">{{ $log->created_at->format('d/m/y H:i') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>

@endsection
