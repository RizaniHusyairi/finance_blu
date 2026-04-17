@extends('layouts.app')

@section('content')
<!-- Header/Breadcrumb -->
<div class="row align-items-center mb-3">
    <div class="col-8">
        <h5 class="mb-0 text-primary fw-bold">Pemeriksaan & Verifikasi Dokumen SP2D</h5>
        <div class="text-muted font-13">Berdasarkan NPI: {{ $npi->nomor_npi ?? '-' }}</div>
    </div>
    <div class="col-4 text-end">
        <a href="{{ route('verifikasi-sp2d.perjaldin.index') }}" class="btn btn-outline-secondary btn-sm"><i class="material-icons-outlined" style="font-size: 16px; margin-bottom: -3px;">arrow_back</i> Kembali</a>
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
    <!-- KOLOM KACA MATA (Detail Data) -->
    <div class="col-12 col-xl-8">
        <!-- 1. Header Status SP2D & Rekap Nilai -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">1. Ringkasan Dokumen Target</h6>
        <div class="card radius-10 mb-4 shadow-sm border-0 border-top border-4 border-info">
            <div class="card-body">
                <div class="row align-items-center mb-3 pb-3 border-bottom border-light">
                    <div class="col-sm-6">
                        <small class="text-muted">Nomor Surat Perintah Pencairan Dana (SP2D)</small>
                        <h4 class="mb-0 text-info fw-bold">{{ $sp2d->nomor_sp2d }}</h4>
                        <div class="font-12 mt-1">Tanggal: {{ $sp2d->tanggal_sp2d?->format('d/m/Y') }}</div>
                    </div>
                    <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                        <small class="text-muted d-block">Status Keseluruhan</small>
                        @php
                            $sp2dStatusClass = match($sp2d->status) {
                                'MENUNGGU_VERIFIKASI' => 'bg-primary',
                                'DISETUJUI_FINAL' => 'bg-success',
                                'REVISI' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                        @endphp
                        <span class="badge {{ $sp2dStatusClass }} px-3 py-2 font-12">{{ $sp2d->status }}</span>
                    </div>
                </div>

                <div class="row text-center text-sm-start g-3">
                    <div class="col-6 col-md-4 border-end">
                        <small class="text-muted d-block">Nilai Tagihan Bruto</small>
                        <span class="fw-bold font-14">Rp {{ number_format($tagihan?->total_tagihan ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="col-6 col-md-4 border-end bg-light rounded p-2">
                        <small class="text-muted d-block">Dasar NPI / SPM (Netto)</small>
                        <span class="fw-bold fs-6 text-success">Rp {{ number_format($spm?->nominal_spm ?? 0, 0, ',', '.') }}</span>
                        <i class="material-icons-outlined text-success font-14" title="NPI Sudah Diverifikasi">verified</i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Informasi Dokumen Sumber -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">2. Penelusuran Hierarki Dokumen</h6>
        <div class="card radius-10 shadow-sm mb-4">
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-4 py-3 bg-light">
                        <div class="row align-items-center">
                            <div class="col-md-6">
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
                            <div class="col-md-6 mt-2 mt-md-0 text-md-end">
                                <div class="text-muted font-11">Uraian / Deskripsi Rencana</div>
                                <div class="fw-bold font-12 text-wrap">{{ Str::limit($tagihan?->deskripsi ?? '-', 70) }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="list-group-item px-4 py-2 d-flex justify-content-between align-items-center">
                        <div class="w-100 row align-items-center">
                            <div class="col-3 text-muted font-12">Nomor NPI</div>
                            <div class="col-6 fw-bold font-13">{{ $npi->nomor_npi }}</div>
                            <div class="col-3 text-end font-11">{{ optional($npi->tanggal_npi)->format('d M y') ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="list-group-item px-4 py-2 d-flex justify-content-between align-items-center">
                        <div class="w-100 row align-items-center">
                            <div class="col-3 text-muted font-12">Nomor SPM</div>
                            <div class="col-6 fw-bold font-13">{{ $spm?->nomor_spm ?? '-' }}</div>
                            <div class="col-3 text-end font-11">{{ optional($spm?->tanggal_spm)->format('d M y') ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="list-group-item px-4 py-2 d-flex justify-content-between align-items-center">
                        <div class="w-100 row align-items-center">
                            <div class="col-3 text-muted font-12">Nomor SPP</div>
                            <div class="col-6 fw-bold font-13">{{ $spp?->nomor_spp ?? '-' }}</div>
                            <div class="col-3 text-end font-11">{{ optional($spp?->tanggal_spp)->format('d M y') ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Rincian Peserta -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">3. Rekapitulasi Rincian Peserta</h6>
        <div class="card radius-10 shadow-sm mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped font-11 mb-0 align-middle">
                        <thead class="table-light align-middle">
                            <tr>
                                <th class="ps-3">Peserta / NIP</th>
                                <th>Tujuan & Tanggal</th>
                                <th class="text-center">Hari</th>
                                <th class="text-end">Tr & Tiket</th>
                                <th class="text-end">Pengp. & UH</th>
                                <th class="text-end border-end">Repr</th>
                                <th class="text-end text-primary fw-bold pe-3">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tagihan?->detailPerjaldin ?? [] as $det)
                            @php
                                $subtotal = ($det->biaya_tiket ?? 0) + ($det->biaya_transport ?? 0) + ($det->biaya_penginapan ?? 0) + ($det->uang_harian ?? 0) + ($det->uang_representasi ?? 0);
                            @endphp
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold">{{ $det->nama_pegawai ?: ($det->pegawai?->nama_lengkap ?? '-') }}</div>
                                    <div class="text-muted mt-1" style="font-size:9px;">{{ $det->nip ?: ($det->pegawai?->nip ?? '-') }}</div>
                                </td>
                                <td>
                                    <div>{{ $det->tujuan }} ({{ $det->provinsi?->provinsi ?? '-' }})</div>
                                    <div class="text-muted font-10">{{ $det->tgl_berangkat ? \Carbon\Carbon::parse($det->tgl_berangkat)->format('d/m/Y') : '-' }}</div>
                                </td>
                                <td class="text-center">{{ $det->lama_hari }}</td>
                                <td class="text-end text-muted">
                                    {{ number_format($det->biaya_tiket ?? 0, 0, ',', '.') }}<br>
                                    {{ number_format($det->biaya_transport ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="text-end text-muted">
                                    {{ number_format($det->biaya_penginapan ?? 0, 0, ',', '.') }}<br>
                                    {{ number_format($det->uang_harian ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="text-end border-end text-muted">{{ number_format($det->uang_representasi ?? 0, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold font-12 text-primary pe-3">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted">Belum ada rincian peserta.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- KOLOM PENILAIAN / AKSI KANAN -->
    <div class="col-12 col-xl-4">
        
        @if($canAct)
            <!-- Panel Aksi Verifikasi Aktif -->
            <div class="card radius-10 shadow border-0 border-top border-4 border-warning mb-4 sticky-top" style="top: 80px; z-index: 10;">
                <div class="card-header bg-light-warning border-bottom px-4 py-3">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center">
                        <span class="spinner-grow spinner-grow-sm text-warning me-2" role="status" aria-hidden="true"></span>
                        Status: Menunggu Aksi Anda
                    </h6>
                </div>
                <div class="card-body text-center py-4">
                    <div class="alert alert-info border-0 bg-light-info py-2 px-3 align-items-center d-flex mb-4 text-start">
                        <i class="material-icons-outlined text-info me-2">info</i>
                        <span class="font-12">Anda berperan sebagai verifikator <strong>{{ $roleCode }}</strong> pada dokumen SP2D ini.</span>
                    </div>

                    <h5 class="fw-bold mb-3">Tentukan Keputusan</h5>
                    <p class="text-muted small mb-4">Pastikan rincian dokumen di halaman samping sesuai dan sah.</p>

                    <button type="button" class="btn btn-success w-100 mb-3 py-2 fs-6 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalApprove">
                        <i class="material-icons-outlined me-1" style="font-size: 18px; vertical-align: middle;">check_circle</i> Setujui SP2D
                    </button>
                    <button type="button" class="btn btn-outline-danger w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#modalRevisi">
                        <i class="material-icons-outlined me-1" style="font-size: 18px; vertical-align: middle;">assignment_return</i> Kembalikan untuk Revisi
                    </button>
                </div>
            </div>

            <!-- Modal Approve -->
            <div class="modal fade" id="modalApprove" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white border-0">
                            <h5 class="modal-title text-white">Konfirmasi Persetujuan SP2D</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('verifikasi-sp2d.perjaldin.approve', $sp2d->id) }}" method="POST">
                            @csrf
                            <div class="modal-body text-center py-4 px-4 bg-light">
                                <i class="material-icons-outlined text-success" style="font-size: 4rem;">check_circle</i>
                                <h5 class="mt-3 mb-2 fw-bold">Setujui Dokumen</h5>
                                <p class="text-muted mb-3 font-13">Anda menyetujui SP2D Perjaldin Nomor <strong>{{ $sp2d->nomor_sp2d }}</strong>. Jika penugasan paralel selesai, SP2D akan beralih ke status final.</p>
                                
                                <div class="text-start mt-3">
                                    <label class="form-label fw-bold small text-dark">Catatan Persetujuan (Opsional)</label>
                                    <textarea name="catatan" class="form-control" rows="2" placeholder="Tulis instruksi tambahan..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer justify-content-center border-0 pb-4 bg-light">
                                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-success px-4 fw-bold">Ya, Setujui Lanjut</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Revisi -->
            <div class="modal fade" id="modalRevisi" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white border-0">
                            <h5 class="modal-title text-white">Kembalikan untuk Revisi</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('verifikasi-sp2d.perjaldin.reject', $sp2d->id) }}" method="POST">
                            @csrf
                            <div class="modal-body bg-light">
                                <div class="alert alert-warning border-0 p-2 font-11 mb-3">
                                    Status SP2D akan dikembalikan ke Bendahara Pengeluaran (Draft / Revisi).
                                </div>
                                <div class="mb-2 text-start">
                                    <label class="form-label fw-bold text-dark font-13">Alasan Revisi / Kesalahan <span class="text-danger">*</span></label>
                                    <textarea name="catatan" class="form-control" rows="4" required placeholder="Mohon jelaskan secara detail revisi yang diperlukan..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer justify-content-center border-0 pb-4 bg-light">
                                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Tutup</button>
                                <button type="submit" class="btn btn-danger px-4 fw-bold">Tolak & Kembalikan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <!-- Panel Info No Action -->
            <div class="card radius-10 mb-4 shadow-sm border-0 bg-light">
                <div class="card-body text-center p-4">
                    <i class="material-icons-outlined fs-1 text-muted mb-2">done_all</i>
                    <h6 class="fw-bold mb-2">Bukan Antrean Tindakan Anda</h6>
                    <p class="text-muted font-12 mb-0">Dokumen SP2D ini telah melewati tahapan Anda atau Anda bukan bagian dari verifikator paralel yang aktif saat ini.</p>
                </div>
            </div>
        @endif

        <!-- Timeline Workflow Paralel -->
        @if($wf)
        <h6 class="text-uppercase text-secondary fw-bold mb-2 font-13 mt-4">Antrean Verifikasi Paralel</h6>
        <div class="card radius-10 shadow-sm mb-4 border-0">
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($wf->approvals as $approval)
                    <li class="list-group-item d-flex justify-content-between align-items-start py-3">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold font-13 text-dark">{{ $approval->role_code }}</div>
                            <div class="text-muted font-11">{{ $approval->assignedUser?->name ?? 'Role Global' }}</div>
                            @if($approval->catatan)
                                <div class="text-muted font-11 mt-1 bg-light p-1 rounded border-start border-3 border-secondary text-wrap" style="max-width: 200px;">
                                    "{{ Str::limit($approval->catatan, 100) }}"
                                </div>
                            @endif
                            @if($approval->actedByUser)
                                <div class="text-primary font-10 mt-1"><i class="material-icons-outlined font-10">done</i> Diselesaikan oleh: {{ $approval->actedByUser->name }}</div>
                            @endif
                        </div>
                        <div class="text-end ms-2">
                            <span class="badge @if($approval->status=='APPROVED') bg-success @elseif($approval->status=='REVISION') bg-danger @elseif($approval->status=='PENDING') bg-warning text-dark @else bg-secondary @endif rounded-pill py-1 px-2" style="font-size:10px;">
                                {{ $approval->status }}
                            </span>
                            @if($approval->acted_at)
                                <div class="text-muted font-10 mt-1">{{ \Carbon\Carbon::parse($approval->acted_at)->format('d/m/y H:i') }}</div>
                            @endif
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <!-- Riwayat Aktivitas Log SP2D -->
        @if($sp2d->logs && $sp2d->logs->count() > 0)
        <h6 class="text-uppercase text-secondary fw-bold mb-2 font-13 mt-4">Riwayat Jejak Dokumen</h6>
        <div class="card radius-10 shadow-sm border-0">
            <div class="card-body p-3">
                <div class="order-scroll" style="max-height: 250px; overflow-y: auto; overflow-x: hidden;">
                    @foreach($sp2d->logs->sortByDesc('created_at') as $log)
                        <div class="d-flex align-items-start mb-3 pb-2 border-bottom border-light">
                            <div class="flex-grow-1">
                                <span class="badge bg-light text-dark font-10 mb-1 border border-secondary">{{ $log->aksi }}</span>
                                <div class="font-12 fw-bold text-dark text-wrap" style="word-break: break-word;">{{ $log->catatan }}</div>
                                <div class="text-muted font-10 mt-1">Oleh: <span class="text-primary font-11">{{ $log->user?->name ?? 'Sistem' }}</span> ({{ $log->role_saat_itu }})</div>
                            </div>
                            <div class="text-end ms-2 pl-2" style="min-width: 60px;">
                                <span class="text-muted font-10">{{ $log->created_at->format('d M') }}<br>{{ $log->created_at->format('H:i') }}</span>
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
