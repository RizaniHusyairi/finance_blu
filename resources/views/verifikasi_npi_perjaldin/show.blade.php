@extends('layouts.app')

@section('content')
<!-- Header/Breadcrumb -->
<div class="row mb-3">
    <div class="col-12 col-md-8">
        <h5 class="mb-0 text-primary fw-bold">Detail Verifikasi NPI Perjaldin</h5>
        <p class="text-muted mb-0">Role Aktif Antrean: <span class="badge bg-dark fw-normal">{{ $roleCode }}</span></p>
    </div>
    <div class="col-12 col-md-4 text-md-end mt-2 mt-md-0">
        <a href="{{ route('verifikasi-npi.perjaldin.index') }}" class="btn btn-outline-secondary btn-sm"><i class="material-icons-outlined" style="font-size: 16px; margin-bottom: -3px;">arrow_back</i> Kembali</a>
    </div>
</div>

<div class="row">
    <!-- KOLOM KIRI (Konteks & NPI/SPP/SPM) -->
    <div class="col-12 col-lg-7">
        <!-- 1. Card Status Utama NPI -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">1. Ringkasan NPI</h6>
        <div class="card radius-10 mb-4 border-top border-0 border-4 border-primary shadow-sm bg-primary bg-opacity-10">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3">
                    <div class="mb-3 mb-md-0">
                        <h6 class="mb-1 fw-bold">Nomor NPI</h6>
                        <h4 class="mb-0 text-primary">{{ $npi->nomor_npi }}</h4>
                        <div class="font-13 text-muted mt-1">Tanggal: {{ $npi->tanggal_npi?->format('d M Y') ?? '-' }}</div>
                    </div>
                    
                    <div class="d-flex gap-3 text-center">
                        <div>
                            <div class="text-muted" style="font-size: 11px;">Status Keseluruhan</div>
                            <span class="badge @if($npi->status == \App\Models\DokumenNpi::STATUS_DISETUJUI_FINAL || $npi->status == \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG) bg-success @elseif($npi->status == \App\Models\DokumenNpi::STATUS_REVISI) bg-danger @else bg-info text-dark @endif font-14">
                                {{ $npi->status }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-3 rounded shadow-sm border">
                    <div class="row">
                        <div class="col-sm-6 border-end">
                            <small class="text-muted d-block">Pembuat NPI</small>
                            <strong>{{ $npi->dibuatOleh?->name ?? 'Sistem' }}</strong>
                        </div>
                        <div class="col-sm-6 text-end">
                            <small class="text-muted d-block">Nilai Pemindahbukuan Internal</small>
                            <h5 class="mb-0 fw-bold text-primary">Rp {{ number_format($spm?->nominal_spm ?? 0, 0, ',', '.') }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 2. Source Documents -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">2. Dokumen Sumber (Tracing)</h6>
        <div class="card radius-10 shadow-sm mb-4">
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-4 py-3 d-flex justify-content-between align-items-center bg-light">
                        <div>
                            <div class="fw-bold"><i class="material-icons-outlined text-secondary font-18 me-1" style="vertical-align: text-bottom;">work</i> Dokumen SPM</div>
                            <div class="text-muted font-13">{{ $spm?->tanggal_spm?->format('d M Y') ?? '-' }}</div>
                        </div>
                        <div class="fw-bold">{{ $spm?->nomor_spm ?? '-' }}</div>
                    </div>
                    <div class="list-group-item px-4 py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold"><i class="material-icons-outlined text-secondary font-18 me-1" style="vertical-align: text-bottom;">description</i> Dokumen SPP</div>
                            <div class="text-muted font-13">{{ $spp?->tanggal_spp?->format('d M Y') ?? '-' }}</div>
                        </div>
                        <div class="fw-bold">{{ $spp?->nomor_spp ?? '-' }}</div>
                    </div>
                    <div class="list-group-item px-4 py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold"><i class="material-icons-outlined text-secondary font-18 me-1" style="vertical-align: text-bottom;">receipt_long</i> Tagihan</div>
                            <div class="text-muted font-13">{{ $tagihan?->tanggal_tagihan?->format('d M Y') ?? '-' }}</div>
                        </div>
                        <div class="fw-bold">{{ $tagihan?->nomor_tagihan ?? '-' }}</div>
                    </div>
                </div>
                
                <div class="px-4 py-3 bg-light border-top">
                    <label class="text-muted form-label mb-1">Uraian / Deskripsi Tujuan Perjalanan Dinas:</label>
                    <div class="fw-bold">{{ $tagihan?->deskripsi ?? '-' }}</div>
                </div>
            </div>
        </div>

        <!-- 3. Rincian Peserta Perjaldin -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">3. Rekapitulasi Rincian Perjalanan Dinas</h6>
        <div class="card radius-10 shadow-sm mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped font-12 mb-0">
                        <thead class="table-light align-middle text-center">
                            <tr>
                                <th>Nama Peserta (NIP)</th>
                                <th>Tujuan & Lama</th>
                                <th>Tiket & Transport</th>
                                <th>Penginapan & U.Harian</th>
                                <th>Representasi</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="align-middle">
                            @forelse($tagihan?->detailPerjaldin ?? [] as $det)
                            @php
                                $namaPeserta = $det->nama_pegawai ?: ($det->pegawai?->nama_lengkap ?? '-');
                                $nipPeserta = $det->nip ?: ($det->pegawai?->nip ?? null);
                                $tglBerangkat = $det->tgl_berangkat
                                    ? \Carbon\Carbon::parse($det->tgl_berangkat)->format('d M Y')
                                    : '-';
                                $subtotalPeserta = (float) ($det->biaya_tiket ?? 0)
                                    + (float) ($det->biaya_transport ?? 0)
                                    + (float) ($det->biaya_penginapan ?? 0)
                                    + (float) ($det->uang_harian ?? 0)
                                    + (float) ($det->uang_representasi ?? 0);
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $namaPeserta }}</div>
                                    <div class="text-muted" style="font-size: 10px;">{{ $nipPeserta ?: '-' }}</div>
                                </td>
                                <td>
                                    <div>Tujuan: <strong>{{ $det->tujuan ?? '-' }}</strong> <span class="text-muted">({{ $det->provinsi?->provinsi ?? '-' }})</span></div>
                                    <div>Brgkt: {{ $tglBerangkat }}</div>
                                    <div>Lama: <strong>{{ $det->lama_hari ?? 0 }} Hari</strong></div>
                                </td>
                                <td class="text-end">
                                    Tk: {{ number_format($det->biaya_tiket ?? 0, 0, ',', '.') }}<br>
                                    Tr: {{ number_format($det->biaya_transport ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    Pg: {{ number_format($det->biaya_penginapan ?? 0, 0, ',', '.') }}<br>
                                    UH: {{ number_format($det->uang_harian ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($det->uang_representasi ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="text-end fw-bold text-primary">
                                    {{ number_format($subtotalPeserta, 0, ',', '.') }}
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted">Belum ada rincian peserta Perjaldin.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold text-end">
                            <tr>
                                <td colspan="5">TOTAL NETTO NPI:</td>
                                <td class="text-primary text-end">Rp {{ number_format($spm?->nominal_spm ?? 0, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- KOLOM KANAN (Aksi & Timeline) -->
    <div class="col-12 col-lg-5">

        @if($canAct)
            <!-- Notice: Aksi diperlukan -->
            <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-2 mb-4 py-3 px-4">
                <span class="spinner-grow spinner-grow-sm text-warning" role="status" aria-hidden="true"></span>
                <div>
                    <div class="fw-bold text-dark">Menunggu Aksi Anda ({{ $roleCode }})</div>
                    <div class="font-12 text-muted">Gunakan tombol di bawah layar untuk memberikan keputusan.</div>
                </div>
            </div>

            {{-- Modal Approve --}}
            <div class="modal fade" id="modalApprove" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title text-white">Konfirmasi Persetujuan NPI</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('verifikasi-npi.perjaldin.approve', $npi->id) }}" method="POST">
                            @csrf
                            <div class="modal-body text-center py-4">
                                <i class="material-icons-outlined text-success" style="font-size: 4rem;">check_circle</i>
                                <h4 class="mt-3 mb-2">Apakah Anda yakin?</h4>
                                <p class="text-muted mb-3">Anda akan menyetujui NPI Perjalanan Dinas dengan nomor <strong>{{ $npi->nomor_npi }}</strong>.</p>
                                
                                <div class="text-start mt-3">
                                    <label class="form-label fw-bold small">Catatan Persetujuan (Opsional)</label>
                                    <textarea name="catatan" class="form-control" rows="2" placeholder="Tulis catatan jika diperlukan..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer justify-content-center border-0 pb-4 bg-light">
                                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-success px-4 fw-bold">Ya, Setujui NPI</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Modal Revisi --}}
            <div class="modal fade" id="modalRevisi" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title text-white">Kembalikan untuk Revisi</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('verifikasi-npi.perjaldin.reject', $npi->id) }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <p class="text-muted small">NPI ini akan dikembalikan kepada pembuatnya. Berikan catatan mengenai revisi yang diperlukan agar kesalahan dapat diperbaiki.</p>
                                <div class="mb-3 mt-3 text-start">
                                    <label class="form-label fw-bold">Catatan / Alasan Revisi <span class="text-danger">*</span></label>
                                    <textarea name="catatan" class="form-control" rows="4" required placeholder="Jelaskan secara detail apa yang harus diperbaiki..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-danger fw-bold">Kembalikan NPI</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <!-- Notice Status -->
            <div class="card radius-10 shadow-sm border-0 bg-light mb-4 text-center p-3">
                @if($myApproval?->status === 'APPROVED')
                    <i class="material-icons-outlined fs-1 text-success mb-2">check_circle</i>
                    <h6 class="fw-bold">Anda telah menyetujui dokumen ini.</h6>
                    <p class="text-muted font-12 mb-0">Pada {{ \Carbon\Carbon::parse($myApproval->acted_at)->format('d M Y - H:i:s') }}</p>
                @elseif($myApproval?->status === 'REVISION')
                    <i class="material-icons-outlined fs-1 text-danger mb-2">assignment_return</i>
                    <h6 class="fw-bold text-danger">Anda telah mengembalikan dokumen ini.</h6>
                    <p class="text-muted font-12 mb-0">Catatan: {{ $myApproval->catatan }}</p>
                @elseif($myApproval?->status === 'REJECTED')
                    <i class="material-icons-outlined fs-1 text-danger mb-2">cancel</i>
                    <h6 class="fw-bold text-danger">Anda telah menolak dokumen ini.</h6>
                @else
                    <i class="material-icons-outlined fs-1 text-secondary mb-2">pending</i>
                    <h6 class="fw-bold">Tidak Menunggu Aksi Anda.</h6>
                    <p class="text-muted font-12 mb-0">Dokumen tidak masuk antrean Anda saat ini.</p>
                @endif
            </div>
        @endif

        <!-- 4. Target Pembayaran / Bendahara -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">4. Disposisi Internal</h6>
        <div class="card radius-10 shadow-sm mb-4">
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted font-12 mb-0">Tujuan NPI (Bendahara Penerimaan)</label>
                    <div class="fw-bold pb-1">{{ $npi->bendaharaPenerimaan?->name ?? '-' }}</div>
                </div>
                <div class="mb-0">
                    <label class="text-muted font-12 mb-0">Uraian / Teks NPI (Pembuat)</label>
                    <div class="bg-light p-2 rounded text-muted font-12 fst-italic border">
                        "{{ $npi->catatan ?? '-' }}"
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Timeline Workflow Paralel -->
        <h6 class="text-uppercase text-secondary fw-bold mb-2">5. Skema Persetujuan (Paralel)</h6>
        <div class="card radius-10 shadow-sm mb-4">
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <!-- Benpen -->
                    <li class="list-group-item d-flex justify-content-between align-items-start py-3">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">Bendahara Penerimaan</div>
                            <div class="text-muted font-12">{{ $approverBenpen?->assignedUser?->name ?? 'Semua BenPen' }}</div>
                            @if($approverBenpen?->catatan)
                                <div class="text-muted font-11 mt-1"><i class="material-icons-outlined font-12" style="vertical-align: text-top;">chat</i> "{{ $approverBenpen->catatan }}"</div>
                            @endif
                            @if($approverBenpen?->acted_at)
                                <div class="font-10 text-muted mt-1">{{ \Carbon\Carbon::parse($approverBenpen->acted_at)->format('d M Y H:i') }}</div>
                            @endif
                        </div>
                        <span class="badge @if($approverBenpen?->status=='APPROVED') bg-success @elseif($approverBenpen?->status=='REVISION') bg-danger @else bg-warning text-dark @endif rounded-pill">
                            {{ $approverBenpen?->status ?? 'WAITING' }}
                        </span>
                    </li>

                    <!-- PPK -->
                    <li class="list-group-item d-flex justify-content-between align-items-start py-3">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">Pejabat Pembuat Komitmen</div>
                            <div class="text-muted font-12">{{ $approverPpk?->assignedUser?->name ?? 'Semua PPK' }}</div>
                            @if($approverPpk?->catatan)
                                <div class="text-muted font-11 mt-1"><i class="material-icons-outlined font-12" style="vertical-align: text-top;">chat</i> "{{ $approverPpk->catatan }}"</div>
                            @endif
                            @if($approverPpk?->acted_at)
                                <div class="font-10 text-muted mt-1">{{ \Carbon\Carbon::parse($approverPpk->acted_at)->format('d M Y H:i') }}</div>
                            @endif
                        </div>
                        <span class="badge @if($approverPpk?->status=='APPROVED') bg-success @elseif($approverPpk?->status=='REVISION') bg-danger @else bg-warning text-dark @endif rounded-pill">
                            {{ $approverPpk?->status ?? 'WAITING' }}
                        </span>
                    </li>

                    <!-- Kasubbag -->
                    <li class="list-group-item d-flex justify-content-between align-items-start py-3">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">Kepala Subbagian Keuangan & TU</div>
                            <div class="text-muted font-12">{{ $approverKasubbag?->assignedUser?->name ?? 'Semua Kasubbag' }}</div>
                            @if($approverKasubbag?->catatan)
                                <div class="text-muted font-11 mt-1"><i class="material-icons-outlined font-12" style="vertical-align: text-top;">chat</i> "{{ $approverKasubbag->catatan }}"</div>
                            @endif
                            @if($approverKasubbag?->acted_at)
                                <div class="font-10 text-muted mt-1">{{ \Carbon\Carbon::parse($approverKasubbag->acted_at)->format('d M Y H:i') }}</div>
                            @endif
                        </div>
                        <span class="badge @if($approverKasubbag?->status=='APPROVED') bg-success @elseif($approverKasubbag?->status=='REVISION') bg-danger @else bg-warning text-dark @endif rounded-pill">
                            {{ $approverKasubbag?->status ?? 'WAITING' }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        @if($npi->logs && $npi->logs->count() > 0)
            <!-- Riwayat Aktivitas Sistem -->
            <h6 class="text-uppercase text-secondary fw-bold mb-2">Riwayat Log Induk Dokumen NPI</h6>
            <div class="card radius-10 shadow-sm">
                <div class="card-body">
                    <div class="order-scroll" style="max-height: 300px; overflow-y: auto;">
                        @foreach($npi->logs as $log)
                            <div class="d-flex align-items-start mb-3 pb-2 border-bottom">
                                <div class="font-20 text-primary me-3 pt-1"><i class="material-icons-outlined font-20">history</i></div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 font-14">{{ $log->aksi }}</h6>
                                    <div class="text-muted font-12 my-1">Oleh: <strong>{{ $log->user?->name ?? 'System' }}</strong> ({{ $log->role_saat_itu }})</div>
                                    @if($log->catatan)
                                        <div class="text-muted font-12 fst-italic bg-light p-2 rounded mt-1">"{{ $log->catatan }}"</div>
                                    @endif
                                </div>
                                <div class="text-end ms-2">
                                    <span class="text-muted font-10">{{ $log->created_at->format('d/m/Y') }}</span><br>
                                    <span class="text-muted font-10">{{ $log->created_at->format('H:i:s') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>

{{-- FIXED BOTTOM ACTION BAR --}}
@if($canAct)
<div class="npi-fixed-action-bar" id="npiFixedBar">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-2 text-white">
                <i class="material-icons-outlined" style="font-size: 22px;">verified</i>
                <div>
                    <div class="fw-bold" style="font-size: 0.9rem;">NPI Perjaldin: {{ $npi->nomor_npi }}</div>
                    <div style="font-size: 0.75rem; opacity: 0.8;">Rp {{ number_format($spm?->nominal_spm ?? 0, 0, ',', '.') }} &bull; {{ $roleCode }}</div>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light fw-bold px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalRevisi">
                    <i class="material-icons-outlined me-1" style="font-size: 18px; vertical-align: middle;">assignment_return</i> Revisi
                </button>
                <button type="button" class="btn btn-success fw-bold px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalApprove">
                    <i class="material-icons-outlined me-1" style="font-size: 18px; vertical-align: middle;">check_circle</i> Setujui NPI Perjaldin
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
    /* Add bottom padding to page content so it doesn't hide behind the fixed bar */
    body { padding-bottom: 90px; }
</style>
@endpush
