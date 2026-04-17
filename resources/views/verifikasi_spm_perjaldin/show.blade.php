@extends('layouts.app')
@section('title', 'Detail SPM Perjaldin — ' . $roleLabel)
@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">
                <a href="{{ route($indexRoute) }}" class="text-decoration-none text-muted"><i class="material-icons-outlined" style="font-size: 20px; vertical-align: bottom;">arrow_back</i> Kembali</a>
                | Detail SPM Perjaldin
            </h4>
            <div class="text-muted small">{{ $roleLabel }}</div>
        </div>
        <div>
            @if($statusFinal === 'Selesai Diverifikasi')
                <span class="badge bg-success px-3 py-2 fs-6"> Selesai Diverifikasi</span>
            @elseif($statusFinal === 'Perlu Revisi')
                <span class="badge bg-danger px-3 py-2 fs-6"> Perlu Revisi</span>
            @else
                <span class="badge bg-warning text-dark px-3 py-2 fs-6"> {{ $statusFinal }}</span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">{{ session('warning') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Workflow Timeline --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="card-title mb-3">Status Verifikasi Paralel (SPM Perjaldin)</h6>
            <div class="d-flex w-100 justify-content-between align-items-center text-center">
                {{-- Operator --}}
                <div class="flex-fill">
                    <div class="d-inline-flex justify-content-center align-items-center rounded-circle bg-success text-white mb-2" style="width: 40px; height: 40px;">
                        <i class="material-icons-outlined fs-5">check</i>
                    </div>
                    <div class="fw-bold small">Operator BLU</div>
                    <div class="text-success small">Diajukan</div>
                    <div class="text-muted" style="font-size: 0.7rem;">{{ $spm->created_at?->format('d M Y H:i') }}</div>
                </div>

                <div class="flex-fill d-flex align-items-center px-2">
                    <div class="w-100 border-top border-2 border-success"></div>
                </div>

                {{-- PPSPM --}}
                <div class="flex-fill">
                    @php
                        $ppspmColor = 'warning'; $ppspmIcon = 'hourglass_empty'; $ppspmText = 'Menunggu';
                        if ($ppspmApproval) {
                            if ($ppspmApproval->status === 'APPROVED') { $ppspmColor = 'success'; $ppspmIcon = 'check'; $ppspmText = 'Disetujui'; }
                            if ($ppspmApproval->status === 'REVISION') { $ppspmColor = 'danger'; $ppspmIcon = 'close'; $ppspmText = 'Revisi'; }
                        }
                    @endphp
                    <div class="d-inline-flex justify-content-center align-items-center rounded-circle border border-2 border-{{ $ppspmColor }} text-{{ $ppspmColor }} mb-2 bg-{{ $ppspmColor }} bg-opacity-10" style="width: 40px; height: 40px;">
                        <i class="material-icons-outlined fs-5">{{ $ppspmIcon }}</i>
                    </div>
                    <div class="fw-bold small">PPSPM</div>
                    <div class="text-{{ $ppspmColor }} small">{{ $ppspmText }}</div>
                    @if($ppspmApproval?->acted_at)
                        <div class="text-muted" style="font-size: 0.7rem;">{{ \Carbon\Carbon::parse($ppspmApproval->acted_at)->format('d M Y H:i') }}</div>
                    @endif
                </div>

                <div class="flex-fill d-flex align-items-center px-2">
                    <div class="w-100 border-top border-2 border-muted" style="border-style: dashed !important;"></div>
                </div>

                {{-- Kasubbag --}}
                <div class="flex-fill">
                    @php
                        $kasColor = 'warning'; $kasIcon = 'hourglass_empty'; $kasText = 'Menunggu';
                        if ($kasApproval) {
                            if ($kasApproval->status === 'APPROVED') { $kasColor = 'success'; $kasIcon = 'check'; $kasText = 'Disetujui'; }
                            if ($kasApproval->status === 'REVISION') { $kasColor = 'danger'; $kasIcon = 'close'; $kasText = 'Revisi'; }
                        }
                    @endphp
                    <div class="d-inline-flex justify-content-center align-items-center rounded-circle border border-2 border-{{ $kasColor }} text-{{ $kasColor }} mb-2 bg-{{ $kasColor }} bg-opacity-10" style="width: 40px; height: 40px;">
                        <i class="material-icons-outlined fs-5">{{ $kasIcon }}</i>
                    </div>
                    <div class="fw-bold small">Kepala Subbagian</div>
                    <div class="text-{{ $kasColor }} small">{{ $kasText }}</div>
                    @if($kasApproval?->acted_at)
                        <div class="text-muted" style="font-size: 0.7rem;">{{ \Carbon\Carbon::parse($kasApproval->acted_at)->format('d M Y H:i') }}</div>
                    @endif
                </div>
            </div>

            @if($latestRevisionNote)
                <div class="mt-3 p-3 bg-danger bg-opacity-10 border border-danger rounded text-danger">
                    <i class="material-icons-outlined" style="font-size: 16px; vertical-align: middle;">warning</i> <strong>Catatan Revisi Terakhir ({{ $latestRevisionNote->role_code }}):</strong><br>
                    {{ $latestRevisionNote->catatan }}
                </div>
            @endif
        </div>
    </div>

    <div class="row g-4">
        {{-- Kolom Kiri: Informasi --}}
        <div class="col-xl-8">
            {{-- Info SPM --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="material-icons-outlined text-primary me-2" style="vertical-align: bottom;">description</i>Informasi SPM</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Nomor SPM</div>
                        <div class="col-sm-8 fw-bold">{{ $spm->nomor_spm }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Tanggal SPM</div>
                        <div class="col-sm-8">{{ $spm->tanggal_spm?->isoFormat('D MMMM Y') }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Jenis Tagihan</div>
                        <div class="col-sm-8">{{ $spm->jenis_tagihan ?? '-' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Cara Bayar</div>
                        <div class="col-sm-8">{{ $spm->cara_bayar ?? '-' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Nilai SPM</div>
                        <div class="col-sm-8 fs-5 text-success fw-bold">Rp {{ number_format($spm->nominal_spm, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            {{-- Info SPP Sumber --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="material-icons-outlined text-info me-2" style="vertical-align: bottom;">post_add</i>Data SPP Sumber</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Nomor SPP</div>
                        <div class="col-sm-8">
                            <span class="fw-bold">{{ $spp->nomor_spp ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Tahun Anggaran</div>
                        <div class="col-sm-8">{{ $spp->tahun_anggaran ?? '-' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Uraian SPP</div>
                        <div class="col-sm-8">{{ $spp->uraian ?? '-' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Kategori Pembayaran</div>
                        <div class="col-sm-8">{{ $spp->kategori_pembayaran ?? '-' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Beban Anggaran (COA)</div>
                        <div class="col-sm-8">
                            @if($komponen?->dipaRevisionItem?->coa)
                                <span class="badge bg-primary">{{ $komponen->dipaRevisionItem->coa->kode_akun }}</span>
                                {{ $komponen->dipaRevisionItem->coa->nama_akun }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Daftar Peserta Perjaldin --}}
            @if($tagihan->detailPerjaldin && $tagihan->detailPerjaldin->count() > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="material-icons-outlined text-primary me-2" style="vertical-align: bottom;">group</i>Peserta Perjalanan Dinas ({{ $tagihan->detailPerjaldin->count() }} Orang)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama / NIP</th>
                                    <th>Tujuan</th>
                                    <th>Tgl Berangkat</th>
                                    <th>Lama</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tagihan->detailPerjaldin as $peserta)
                                    @php
                                        $subtotal = ($peserta->biaya_tiket ?? 0) + ($peserta->biaya_transport ?? 0) + ($peserta->biaya_penginapan ?? 0) + ($peserta->uang_harian ?? 0) + ($peserta->uang_representasi ?? 0);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $peserta->nama_pegawai }}</div>
                                            <div class="small text-muted">{{ $peserta->nip ?: '-' }}</div>
                                        </td>
                                        <td>{{ $peserta->tujuan }}</td>
                                        <td>{{ \Carbon\Carbon::parse($peserta->tgl_berangkat)->format('d M Y') }}</td>
                                        <td>{{ $peserta->lama_hari }} Hari</td>
                                        <td class="text-end fw-bold">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            {{-- Daftar Komponen Biaya --}}
            @if($tagihan->komponenPerjaldin && $tagihan->komponenPerjaldin->where('total_nominal', '>', 0)->count() > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="material-icons-outlined text-primary me-2" style="vertical-align: bottom;">apps</i>Rekap Komponen Biaya</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Komponen</th>
                                    <th>COA</th>
                                    <th class="text-end">Nominal</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tagihan->komponenPerjaldin->where('total_nominal', '>', 0) as $k)
                                <tr class="{{ $k->id === $komponen?->id ? 'table-primary' : '' }}">
                                    <td>
                                        <span class="fw-bold">{{ $k->nama_komponen }}</span>
                                        @if($k->id === $komponen?->id)
                                            <span class="badge bg-primary ms-1">SPM ini</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($k->dipaRevisionItem?->coa)
                                            <span class="badge bg-light text-dark border">{{ $k->dipaRevisionItem->coa->kode_akun }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">Rp {{ number_format($k->total_nominal, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $k->status_badge_class }}">{{ $k->status_label }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            {{-- Riwayat Log --}}
            @if($spm->logs && $spm->logs->count() > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="material-icons-outlined text-muted me-2" style="vertical-align: bottom;">history</i>Riwayat Aktivitas SPM</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($spm->logs->sortByDesc('created_at')->take(10) as $log)
                        <div class="list-group-item py-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>{{ str_replace('_', ' ', $log->aksi) }}</strong>
                                    <span class="text-muted small ms-2">oleh {{ $log->user?->name ?? 'Sistem' }} ({{ $log->role_saat_itu }})</span>
                                </div>
                                <small class="text-muted">{{ $log->created_at?->format('d M Y H:i') }}</small>
                            </div>
                            @if($log->catatan)
                                <div class="text-muted small mt-1"><i class="material-icons-outlined me-1" style="font-size: 14px; vertical-align: middle;">chat</i>{{ $log->catatan }}</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Kolom Kanan: Verifikator & Aksi --}}
        <div class="col-xl-4">
            {{-- Info Verifikator --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="material-icons-outlined text-info me-2" style="vertical-align: bottom;">badge</i>Verifikator & Pembuat SPM</h6>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item py-3">
                        <div class="small text-muted">Dibuat oleh</div>
                        <div class="fw-bold">{{ $spm->dibuatOleh?->name ?? '-' }}</div>
                        <div class="small text-muted">{{ $spm->created_at?->format('d M Y H:i') }}</div>
                    </div>
                    <div class="list-group-item py-3">
                        <div class="small text-muted">PPSPM</div>
                        <div class="fw-bold">{{ $spm->ppspm?->name ?? '-' }}</div>
                        @if($ppspmApproval)
                            <span class="badge {{ $ppspmApproval->status === 'APPROVED' ? 'bg-success' : ($ppspmApproval->status === 'REVISION' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                {{ $ppspmApproval->status }}
                            </span>
                        @endif
                    </div>
                    <div class="list-group-item py-3">
                        <div class="small text-muted">Kepala Subbagian Keuangan dan Tata Usaha</div>
                        <div class="fw-bold">{{ $kasApproval?->actedByUser?->name ?? ($kasApproval?->assignedUser?->name ?? 'Ditugaskan ke Role') }}</div>
                        @if($kasApproval)
                            <span class="badge {{ $kasApproval->status === 'APPROVED' ? 'bg-success' : ($kasApproval->status === 'REVISION' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                {{ $kasApproval->status }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Cetak PDF --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="material-icons-outlined text-danger me-2" style="vertical-align: bottom;">picture_as_pdf</i>Dokumen Cetak</h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('spms.cetak-pdf', $spm->id) }}" target="_blank" class="btn btn-outline-danger w-100">
                        <i class="material-icons-outlined me-1" style="font-size: 16px; vertical-align: middle;">print</i> Cetak PDF SPM
                    </a>
                </div>
            </div>

            {{-- Panel Aksi Verifikasi --}}
            @if($canAct)
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body text-center py-4">
                    <h5 class="fw-bold mb-3">Aksi Verifikasi</h5>
                    <p class="text-muted small mb-4">Pastikan data SPM Perjaldin sudah sesuai sebelum memberikan persetujuan.</p>

                    <button type="button" class="btn btn-success w-100 mb-2 py-2 fs-6 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalApprove">
                        <i class="material-icons-outlined me-1" style="font-size: 18px; vertical-align: middle;">check_circle</i> Setujui SPM
                    </button>
                    <button type="button" class="btn btn-outline-danger w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#modalRevisi">
                        <i class="material-icons-outlined me-1" style="font-size: 18px; vertical-align: middle;">cancel</i> Minta Revisi
                    </button>
                </div>
            </div>

            {{-- Modal Approve --}}
            <div class="modal fade" id="modalApprove" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title text-white">Konfirmasi Persetujuan</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center py-4">
                            <i class="material-icons-outlined text-success" style="font-size: 4rem;">check_circle</i>
                            <h4 class="mt-3 mb-2">Apakah Anda yakin?</h4>
                            <p class="text-muted mb-0">Anda akan memberikan persetujuan sebagai <strong>{{ $roleLabel }}</strong> untuk SPM <strong>{{ $spm->nomor_spm }}</strong>.</p>
                        </div>
                        <div class="modal-footer justify-content-center border-0 pb-4">
                            <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
                            <form action="{{ route($approveRoute, $spm->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success px-4 fw-bold">Teruskan Proses</button>
                            </form>
                        </div>
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
                        <form action="{{ route($revisiRoute, $spm->id) }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <p class="text-muted small">SPM ini akan direvisi. Berikan catatan mengenai revisi yang diperlukan.</p>
                                <div class="mb-3 mt-3 text-start">
                                    <label class="form-label fw-bold">Catatan / Alasan Revisi <span class="text-danger">*</span></label>
                                    <textarea name="catatan_revisi" class="form-control" rows="4" required placeholder="Jelaskan apa yang harus diperbaiki..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-danger fw-bold">Kembalikan SPM</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection
