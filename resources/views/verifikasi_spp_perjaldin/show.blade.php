@extends('layouts.app')
@section('title', 'Detail SPP Perjaldin — ' . $roleLabel)
@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">
                <a href="{{ route($indexRoute) }}" class="text-decoration-none text-muted"><i class="bi bi-arrow-left"></i> Kembali</a>
                | Detail SPP Perjaldin
            </h4>
            <div class="text-muted small">{{ $roleLabel }}</div>
        </div>
        <div>
            @if($statusFinal === 'Selesai Diverifikasi')
                <span class="badge bg-success px-3 py-2 fs-6"><i class="bi bi-check-circle"></i> Selesai Diverifikasi</span>
            @elseif($statusFinal === 'Perlu Revisi')
                <span class="badge bg-danger px-3 py-2 fs-6"><i class="bi bi-x-circle"></i> Perlu Revisi</span>
            @else
                <span class="badge bg-warning text-dark px-3 py-2 fs-6"><i class="bi bi-hourglass-split"></i> {{ $statusFinal }}</span>
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
            <h6 class="card-title mb-3">Status Verifikasi Paralel</h6>
            <div class="d-flex w-100 justify-content-between align-items-center text-center">
                {{-- Operator --}}
                <div class="flex-fill">
                    <div class="d-inline-flex justify-content-center align-items-center rounded-circle bg-success text-white mb-2" style="width: 40px; height: 40px;">
                        <i class="bi bi-check-lg fs-5"></i>
                    </div>
                    <div class="fw-bold small">Operator BLU</div>
                    <div class="text-success small">Diajukan</div>
                    <div class="text-muted" style="font-size: 0.7rem;">{{ $spp->created_at?->format('d M Y H:i') }}</div>
                </div>

                <div class="flex-fill d-flex align-items-center px-2">
                    <div class="w-100 border-top border-2 border-success"></div>
                </div>

                {{-- PPK --}}
                <div class="flex-fill">
                    @php
                        $ppkColor = 'warning'; $ppkIcon = 'bi-hourglass-split'; $ppkText = 'Menunggu';
                        if ($ppkApproval) {
                            if ($ppkApproval->status === 'APPROVED') { $ppkColor = 'success'; $ppkIcon = 'bi-check-lg'; $ppkText = 'Disetujui'; }
                            if ($ppkApproval->status === 'REVISION') { $ppkColor = 'danger'; $ppkIcon = 'bi-x-lg'; $ppkText = 'Revisi'; }
                        }
                    @endphp
                    <div class="d-inline-flex justify-content-center align-items-center rounded-circle border border-2 border-{{ $ppkColor }} text-{{ $ppkColor }} mb-2 bg-{{ $ppkColor }} bg-opacity-10" style="width: 40px; height: 40px;">
                        <i class="bi {{ $ppkIcon }} fs-5"></i>
                    </div>
                    <div class="fw-bold small">PPK</div>
                    <div class="text-{{ $ppkColor }} small">{{ $ppkText }}</div>
                    @if($ppkApproval?->acted_at)
                        <div class="text-muted" style="font-size: 0.7rem;">{{ \Carbon\Carbon::parse($ppkApproval->acted_at)->format('d M Y H:i') }}</div>
                    @endif
                </div>

                <div class="flex-fill d-flex align-items-center px-2">
                    <div class="w-100 border-top border-2 border-muted" style="border-style: dashed !important;"></div>
                </div>

                {{-- Koordinator Keuangan --}}
                <div class="flex-fill">
                    @php
                        $koorColor = 'warning'; $koorIcon = 'bi-hourglass-split'; $koorText = 'Menunggu';
                        if (!empty($koordinatorApproval)) {
                            if ($koordinatorApproval->status === 'APPROVED') { $koorColor = 'success'; $koorIcon = 'bi-check-lg'; $koorText = 'Disetujui'; }
                            if ($koordinatorApproval->status === 'REVISION') { $koorColor = 'danger'; $koorIcon = 'bi-x-lg'; $koorText = 'Revisi'; }
                        }
                    @endphp
                    <div class="d-inline-flex justify-content-center align-items-center rounded-circle border border-2 border-{{ $koorColor }} text-{{ $koorColor }} mb-2 bg-{{ $koorColor }} bg-opacity-10" style="width: 40px; height: 40px;">
                        <i class="bi {{ $koorIcon }} fs-5"></i>
                    </div>
                    <div class="fw-bold small">Koord. Keuangan</div>
                    <div class="text-{{ $koorColor }} small">{{ $koorText }}</div>
                    @if(!empty($koordinatorApproval) && $koordinatorApproval->acted_at)
                        <div class="text-muted" style="font-size: 0.7rem;">{{ \Carbon\Carbon::parse($koordinatorApproval->acted_at)->format('d M Y H:i') }}</div>
                    @endif
                </div>

                <div class="flex-fill d-flex align-items-center px-2">
                    <div class="w-100 border-top border-2 border-muted" style="border-style: dashed !important;"></div>
                </div>

                {{-- Kasubbag --}}
                <div class="flex-fill">
                    @php
                        $kasColor = 'warning'; $kasIcon = 'bi-hourglass-split'; $kasText = 'Menunggu';
                        if ($kasApproval) {
                            if ($kasApproval->status === 'APPROVED') { $kasColor = 'success'; $kasIcon = 'bi-check-lg'; $kasText = 'Disetujui'; }
                            if ($kasApproval->status === 'REVISION') { $kasColor = 'danger'; $kasIcon = 'bi-x-lg'; $kasText = 'Revisi'; }
                        }
                    @endphp
                    <div class="d-inline-flex justify-content-center align-items-center rounded-circle border border-2 border-{{ $kasColor }} text-{{ $kasColor }} mb-2 bg-{{ $kasColor }} bg-opacity-10" style="width: 40px; height: 40px;">
                        <i class="bi {{ $kasIcon }} fs-5"></i>
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
                    <i class="bi bi-exclamation-triangle"></i> <strong>Catatan Revisi Terakhir ({{ $latestRevisionNote->role_code }}):</strong><br>
                    {{ $latestRevisionNote->catatan }}
                </div>
            @endif
        </div>
    </div>

    <div class="row g-4">
        {{-- Kolom Kiri: Informasi --}}
        <div class="col-xl-8">
            {{-- Info SPP --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text text-primary me-2"></i>Informasi SPP</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Nomor SPP</div>
                        <div class="col-sm-8 fw-bold">{{ $spp->nomor_spp }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Tanggal SPP</div>
                        <div class="col-sm-8">{{ $spp->tanggal_spp?->isoFormat('D MMMM Y') }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Tahun Anggaran</div>
                        <div class="col-sm-8">{{ $spp->tahun_anggaran ?? '-' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Kategori Pembayaran</div>
                        <div class="col-sm-8">{{ $spp->kategori_pembayaran ?? '-' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Komponen Biaya</div>
                        <div class="col-sm-8 fw-bold">{{ $komponen?->nama_komponen ?? '-' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Nilai SPP</div>
                        <div class="col-sm-8 fs-5 text-success fw-bold">Rp {{ number_format($spp->nominal_spp, 0, ',', '.') }}</div>
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
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Uraian</div>
                        <div class="col-sm-8">{{ $spp->uraian ?? '-' }}</div>
                    </div>
                </div>
            </div>

            {{-- Info Tagihan Perjaldin --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-airplane text-info me-2"></i>Dasar Tagihan Perjaldin</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Nomor Tagihan</div>
                        <div class="col-sm-8">
                            <span class="fw-bold">{{ $tagihan->nomor_tagihan ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Deskripsi</div>
                        <div class="col-sm-8">{{ $tagihan->deskripsi ?? '-' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Total Bruto</div>
                        <div class="col-sm-8 fw-bold">Rp {{ number_format($tagihan->total_bruto ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Total Netto</div>
                        <div class="col-sm-8 fw-bold text-success">Rp {{ number_format($tagihan->total_netto ?? 0, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            {{-- Daftar Peserta Perjaldin --}}
            @if($tagihan->detailPerjaldin && $tagihan->detailPerjaldin->count() > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-people text-primary me-2"></i>Peserta Perjalanan Dinas ({{ $tagihan->detailPerjaldin->count() }} Orang)</h6>
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
                    <h6 class="mb-0 fw-bold"><i class="bi bi-ui-radios-grid text-primary me-2"></i>Rekap Seluruh Komponen Biaya</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Komponen</th>
                                    <th>COA</th>
                                    <th class="text-end">Nominal</th>
                                    <th class="text-center">Status SPP</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tagihan->komponenPerjaldin->where('total_nominal', '>', 0) as $k)
                                <tr class="{{ $k->id === $komponen?->id ? 'table-primary' : '' }}">
                                    <td>
                                        <span class="fw-bold">{{ $k->nama_komponen }}</span>
                                        @if($k->id === $komponen?->id)
                                            <span class="badge bg-primary ms-1">SPP ini</span>
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
            @if($spp->logs && $spp->logs->count() > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history text-muted me-2"></i>Riwayat Aktivitas</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($spp->logs->sortByDesc('created_at')->take(10) as $log)
                        <div class="list-group-item py-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>{{ str_replace('_', ' ', $log->aksi) }}</strong>
                                    <span class="text-muted small ms-2">oleh {{ $log->user?->name ?? 'Sistem' }} ({{ $log->role_saat_itu }})</span>
                                </div>
                                <small class="text-muted">{{ $log->created_at?->format('d M Y H:i') }}</small>
                            </div>
                            @if($log->catatan)
                                <div class="text-muted small mt-1"><i class="bi bi-chat-left-text me-1"></i>{{ $log->catatan }}</div>
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
            <!-- Standing Instruction -->
            @include('spps.partials.standing_instruction_card')

            {{-- Info Verifikator --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-person-badge text-info me-2"></i>Verifikator & Pembuat</h6>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item py-3">
                        <div class="small text-muted">Dibuat oleh</div>
                        <div class="fw-bold">{{ $spp->dibuatOleh?->name ?? '-' }}</div>
                        <div class="small text-muted">{{ $spp->created_at?->format('d M Y H:i') }}</div>
                    </div>
                    <div class="list-group-item py-3">
                        <div class="small text-muted">Verifikator PPK</div>
                        <div class="fw-bold">{{ $spp->ppkVerifikator?->name ?? '-' }}</div>
                        @if($ppkApproval)
                            <span class="badge {{ $ppkApproval->status === 'APPROVED' ? 'bg-success' : ($ppkApproval->status === 'REVISION' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                {{ $ppkApproval->status }}
                            </span>
                        @endif
                    </div>
                    <div class="list-group-item py-3">
                        <div class="small text-muted">Koordinator Keuangan</div>
                        <div class="fw-bold">{{ ($koordinatorApproval ?? null)?->actedByUser?->name ?? (($koordinatorApproval ?? null)?->assignedUser?->name ?? '-') }}</div>
                        @if(!empty($koordinatorApproval))
                            <span class="badge {{ $koordinatorApproval->status === 'APPROVED' ? 'bg-success' : ($koordinatorApproval->status === 'REVISION' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                {{ $koordinatorApproval->status }}
                            </span>
                        @endif
                    </div>
                    <div class="list-group-item py-3">
                        <div class="small text-muted">Kepala Subbagian Keuangan dan Tata Usaha</div>
                        <div class="fw-bold">{{ $kasApproval?->actedByUser?->name ?? ($kasApproval?->assignedUser?->name ?? '-') }}</div>
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
                    <h6 class="mb-0 fw-bold"><i class="bi bi-file-pdf text-danger me-2"></i>Dokumen</h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('spps.cetak-pdf', $spp->id) }}" target="_blank" class="btn btn-outline-danger w-100">
                        <i class="bi bi-file-pdf me-1"></i> Cetak PDF SPP
                    </a>
                </div>
            </div>

            {{-- Panel Aksi Verifikasi --}}
            @if(isset($activeRoleApprovals) && count($activeRoleApprovals) > 1)
                <div class="card border-0 shadow-sm bg-light mb-4">
                    <div class="card-body py-4">
                        <div class="alert alert-primary mb-3 small">
                            <i class="bi bi-people-fill me-2"></i> Anda memiliki <strong>{{ count($activeRoleApprovals) }} peran verifikasi</strong> pada SPP ini.
                        </div>
                        
                        <div class="d-flex flex-column gap-3">
                            @foreach($activeRoleApprovals as $idx => $roleApproval)
                                <div class="border rounded bg-white p-3 shadow-sm">
                                    <h6 class="fw-bold mb-3">Aksi ({{ $roleApproval['role'] }})</h6>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-danger flex-fill fw-bold" data-bs-toggle="modal" data-bs-target="#modalRevisi{{ $idx }}">
                                            <i class="bi bi-x-circle me-1"></i> Revisi
                                        </button>
                                        <button type="button" class="btn btn-success flex-fill fw-bold" data-bs-toggle="modal" data-bs-target="#modalApprove{{ $idx }}">
                                            <i class="bi bi-check-circle me-1"></i> Setujui
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Modal Dual Role --}}
                @foreach($activeRoleApprovals as $idx => $roleApproval)
                    {{-- Modal Approve --}}
                    <div class="modal fade" id="modalApprove{{ $idx }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header bg-success text-white">
                                    <h5 class="modal-title text-white">Konfirmasi Persetujuan ({{ $roleApproval['role'] }})</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                                    <h4 class="mt-3 mb-2">Apakah Anda yakin?</h4>
                                    <p class="text-muted mb-0">Anda akan memberikan persetujuan sebagai <strong>{{ $roleApproval['role'] }}</strong> untuk SPP <strong>{{ $spp->nomor_spp }}</strong>.</p>
                                </div>
                                <div class="modal-footer justify-content-center border-0 pb-4">
                                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
                                    <form action="{{ $roleApproval['approveRoute'] }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="approval_id" value="{{ $roleApproval['approval_id'] }}">
                                        <button type="submit" class="btn btn-success px-4 fw-bold">Teruskan Proses</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Modal Revisi --}}
                    <div class="modal fade" id="modalRevisi{{ $idx }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title text-white">Kembalikan untuk Revisi ({{ $roleApproval['role'] }})</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="{{ $roleApproval['revisiRoute'] }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="approval_id" value="{{ $roleApproval['approval_id'] }}">
                                    <div class="modal-body">
                                        <p class="text-muted small">SPP ini akan dikembalikan ke Operator BLU. Operator harus memperbaiki dan mensubmit ulang SPP ini.</p>
                                        <div class="mb-3 mt-3 text-start">
                                            <label class="form-label fw-bold">Catatan / Alasan Revisi <span class="text-danger">*</span></label>
                                            <textarea name="catatan_revisi" class="form-control" rows="4" required placeholder="Jelaskan apa yang harus diperbaiki..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-danger fw-bold">Kembalikan ke Operator</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach

            @elseif($canAct)
                <div class="card border-0 shadow-sm bg-light">
                    <div class="card-body text-center py-4">
                        <h5 class="fw-bold mb-3">Aksi Verifikasi</h5>
                        <p class="text-muted small mb-4">Pastikan data SPP Perjaldin sudah sesuai sebelum memberikan persetujuan.</p>

                        <button type="button" class="btn btn-success w-100 mb-2 py-2 fs-6 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalApprove">
                            <i class="bi bi-check-circle me-1"></i> Setujui SPP
                        </button>
                        <button type="button" class="btn btn-outline-danger w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#modalRevisi">
                            <i class="bi bi-x-circle me-1"></i> Minta Revisi
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
                                <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                                <h4 class="mt-3 mb-2">Apakah Anda yakin?</h4>
                                <p class="text-muted mb-0">Anda akan memberikan persetujuan sebagai <strong>{{ $roleLabel }}</strong> untuk SPP <strong>{{ $spp->nomor_spp }}</strong>.</p>
                            </div>
                            <div class="modal-footer justify-content-center border-0 pb-4">
                                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
                                <form action="{{ route($approveRoute, $spp->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="approval_id" value="{{ count($activeRoleApprovals) === 1 ? $activeRoleApprovals[0]['approval_id'] : ($myApproval->id ?? '') }}">
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
                            <form action="{{ route($revisiRoute, $spp->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="approval_id" value="{{ count($activeRoleApprovals) === 1 ? $activeRoleApprovals[0]['approval_id'] : ($myApproval->id ?? '') }}">
                                <div class="modal-body">
                                    <p class="text-muted small">SPP ini akan dikembalikan ke Operator BLU. Operator harus memperbaiki dan mensubmit ulang SPP ini.</p>
                                    <div class="mb-3 mt-3 text-start">
                                        <label class="form-label fw-bold">Catatan / Alasan Revisi <span class="text-danger">*</span></label>
                                        <textarea name="catatan_revisi" class="form-control" rows="4" required placeholder="Jelaskan apa yang harus diperbaiki..."></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer bg-light">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-danger fw-bold">Kembalikan ke Operator</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
