@extends('layouts.app')
@section('title', 'Detail Verifikasi SPP — ' . ($roleLabel ?? 'Kasubbag'))

@push('css')
    <style>
        body[data-bs-theme="blue-theme"] .main-content { background: #f4f6fa; }

        /* ============ ANIMATIONS ============ */
        @keyframes fadeUpIn { 0% { opacity: 0; transform: translateY(20px); } 100% { opacity: 1; transform: translateY(0); } }
        .anim-fade-up { animation: fadeUpIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }

        /* ============ CARDS ============ */
        .verif-card { border: none; border-radius: 1.25rem; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 1.5rem; background: #fff; overflow: hidden; }
        .verif-card-header { padding: 1.25rem 1.5rem; background: transparent; border-bottom: 1px solid rgba(0,0,0,0.04); font-weight: 700; font-size: 1.1rem; }
        
        /* ============ AUTH PANEL ============ */
        .auth-approval-panel { 
            border: 0; 
            border-radius: 1.25rem; 
            background: linear-gradient(145deg, #ffffff 0%, #f8faff 100%); 
            padding: 2rem; 
            box-shadow: 0 10px 30px rgba(13, 110, 253, 0.08); 
            position: relative;
            border-top: 5px solid #0d6efd;
        }
        .btn-pulse { position: relative; z-index: 1; overflow: hidden; }
        .btn-pulse::after { content: ''; position: absolute; top: 50%; left: 50%; width: 5px; height: 5px; background: rgba(255,255,255,.5); opacity: 0; border-radius: 100%; transform: scale(1, 1) translate(-50%); transform-origin: 50% 50%; }
        .btn-pulse:hover::after { animation: ripple 1s ease-out; }
        @keyframes ripple { 0% { transform: scale(0, 0); opacity: 0.5; } 100% { transform: scale(40, 40); opacity: 0; } }
        
        /* Info Block */
        .verif-info-row { display: flex; align-items: flex-start; margin-bottom: 1rem; }
        .verif-info-label { width: 35%; color: #6c757d; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .verif-info-value { width: 65%; font-weight: 500; color: #212529; }
    </style>
@endpush

@section('content')
    <!-- A. HEADER VERIFIKASI -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom anim-fade-up">
        <div>
            <a href="{{ route($indexRoute ?? 'verifikasi-kasubag.spp.index') }}" class="btn btn-sm btn-light rounded-pill px-3 mb-2 fw-bold text-muted shadow-sm hover-primary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Antrean
            </a>
            <h4 class="fw-bold mb-1 mt-2 text-dark">Detail Verifikasi SPP Kontrak</h4>
            <div class="text-secondary small fw-semibold"><i class="bi bi-person-badge me-1"></i> Akses login sebagai: <span class="text-primary">{{ $roleLabel ?? 'Kepala Subbagian Keuangan dan Tata Usaha' }}</span></div>
        </div>
        <div class="mt-3 mt-md-0">
            @if($statusFinal === 'Selesai Diverifikasi')
                <span class="badge bg-success bg-gradient px-4 py-2 rounded-pill fs-6 shadow-sm"><i class="bi bi-check-circle-fill me-2"></i>Selesai Diverifikasi</span>
            @elseif($statusFinal === 'Perlu Revisi')
                <span class="badge bg-danger bg-gradient px-4 py-2 rounded-pill fs-6 shadow-sm"><i class="bi bi-x-circle-fill me-2"></i>Perlu Revisi</span>
            @else
                <span class="badge bg-warning bg-gradient text-dark px-4 py-2 rounded-pill fs-6 shadow-sm border border-warning"><i class="bi bi-hourglass-split me-2"></i>{{ $statusFinal }}</span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- B. Workflow Status Panel -->
    <div class="verif-card anim-fade-up delay-1">
        <div class="verif-card-header d-flex align-items-center gap-2 text-primary">
            <div class="bg-primary bg-opacity-10 p-2 rounded-circle d-inline-flex"><i class="bi bi-diagram-3-fill"></i></div> Status Verifikasi Paralel
        </div>
        <div class="card-body p-4 p-xl-5">
            @php
                $ppkUser = \App\Models\User::find($spp->tagihan->ppk_user_id) ?? \App\Models\User::role('PPK')->first();
                $koordinatorUser = \App\Models\User::find($spp->tagihan->koordinator_keuangan_user_id) ?? \App\Models\User::role('Koordinator Keuangan')->first();
                $kasubbagUser = \App\Models\User::find($spp->tagihan->kasubbag_user_id) ?? \App\Models\User::role('Kepala Subbagian Keuangan dan Tata Usaha')->first();

                $getColor = function($status) {
                    if ($status === 'APPROVED') return 'success';
                    if ($status === 'REVISION') return 'danger';
                    return 'warning';
                };
                $getText = function($status) {
                    if ($status === 'APPROVED') return 'Disetujui';
                    if ($status === 'REVISION') return 'Revisi';
                    return 'Menunggu';
                };

                $ppkStatus = $ppkApproval ? $ppkApproval->status : 'WAITING';
                $koorStatus = $koordinatorApproval ? $koordinatorApproval->status : 'WAITING';
                $kasStatus = $kasubbagApproval ? $kasubbagApproval->status : 'WAITING';
            @endphp

            <div class="row g-4">
                <!-- PPK -->
                <div class="col-md-4">
                    <div class="p-4 rounded-4 border {{ $ppkStatus === 'APPROVED' ? 'border-success bg-success bg-opacity-10' : ($ppkStatus === 'REVISION' ? 'border-danger bg-danger bg-opacity-10' : 'border-warning bg-warning bg-opacity-10') }} position-relative h-100 transition-all">
                        <div class="d-flex justify-content-between mb-3">
                            <div class="text-uppercase small fw-bold text-muted">Tahap 1</div>
                            <span class="badge bg-{{ $getColor($ppkStatus) }} rounded-pill">{{ $getText($ppkStatus) }}</span>
                        </div>
                        <h6 class="fw-bold mb-1">PPK</h6>
                        <div class="small text-muted mb-3">{{ $ppkUser?->name ?? 'Belum Ditentukan' }}</div>
                        @if($ppkApproval && $ppkApproval->acted_at)
                            <div class="small fw-semibold text-dark"><i class="bi bi-clock-history me-1"></i>{{ \Carbon\Carbon::parse($ppkApproval->acted_at)->format('d M Y H:i') }}</div>
                        @else
                            <div class="small text-muted fst-italic"><i class="bi bi-hourglass me-1"></i>Belum ada tindakan</div>
                        @endif
                    </div>
                </div>
                <!-- Koordinator Keuangan -->
                <div class="col-md-4">
                    <div class="p-4 rounded-4 border {{ $koorStatus === 'APPROVED' ? 'border-success bg-success bg-opacity-10' : ($koorStatus === 'REVISION' ? 'border-danger bg-danger bg-opacity-10' : 'border-warning bg-warning bg-opacity-10') }} position-relative h-100 transition-all">
                        <div class="d-flex justify-content-between mb-3">
                            <div class="text-uppercase small fw-bold text-muted">Tahap 2</div>
                            <span class="badge bg-{{ $getColor($koorStatus) }} rounded-pill">{{ $getText($koorStatus) }}</span>
                        </div>
                        <h6 class="fw-bold mb-1">Koordinator Keuangan</h6>
                        <div class="small text-muted mb-3">{{ $koordinatorUser?->name ?? 'Belum Ditentukan' }}</div>
                        @if($koordinatorApproval && $koordinatorApproval->acted_at)
                            <div class="small fw-semibold text-dark"><i class="bi bi-clock-history me-1"></i>{{ \Carbon\Carbon::parse($koordinatorApproval->acted_at)->format('d M Y H:i') }}</div>
                        @else
                            <div class="small text-muted fst-italic"><i class="bi bi-hourglass me-1"></i>Belum ada tindakan</div>
                        @endif
                    </div>
                </div>
                <!-- Kasubbag -->
                <div class="col-md-4">
                    <div class="p-4 rounded-4 border {{ $kasStatus === 'APPROVED' ? 'border-success bg-success bg-opacity-10' : ($kasStatus === 'REVISION' ? 'border-danger bg-danger bg-opacity-10' : 'border-warning bg-warning bg-opacity-10') }} position-relative h-100 transition-all">
                        <div class="d-flex justify-content-between mb-3">
                            <div class="text-uppercase small fw-bold text-muted">Tahap 3</div>
                            <span class="badge bg-{{ $getColor($kasStatus) }} rounded-pill">{{ $getText($kasStatus) }}</span>
                        </div>
                        <h6 class="fw-bold mb-1">Kasubbag Keuangan</h6>
                        <div class="small text-muted mb-3">{{ $kasubbagUser?->name ?? 'Belum Ditentukan' }}</div>
                        @if($kasubbagApproval && $kasubbagApproval->acted_at)
                            <div class="small fw-semibold text-dark"><i class="bi bi-clock-history me-1"></i>{{ \Carbon\Carbon::parse($kasubbagApproval->acted_at)->format('d M Y H:i') }}</div>
                        @else
                            <div class="small text-muted fst-italic"><i class="bi bi-hourglass me-1"></i>Belum ada tindakan</div>
                        @endif
                    </div>
                </div>
            </div>
            
            @if($latestRevisionNote)
                <div class="mt-4 p-4 bg-danger bg-opacity-10 border border-danger rounded-4 text-danger shadow-sm">
                    <h6 class="fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Catatan Revisi Terakhir dari {{ $latestRevisionNote->role_code }}:</h6>
                    <div class="fst-italic">"{{ $latestRevisionNote->catatan }}"</div>
                </div>
            @endif
        </div>
    </div>

    <div class="row row-cols-1 row-cols-xl-2 g-4">
        <!-- Kolom Kiri: Detail Informasi -->
        <div class="col-xl-8">
            
            <!-- Info SPP -->
            <div class="verif-card anim-fade-up delay-2">
                <div class="verif-card-header d-flex align-items-center gap-2"><i class="bi bi-file-text-fill text-primary"></i> Informasi Dokumen SPP</div>
                <div class="card-body p-4">
                    <div class="verif-info-row">
                        <div class="verif-info-label">Nomor SPP</div>
                        <div class="verif-info-value font-monospace fs-6 text-primary">{{ $spp->nomor_spp }}</div>
                    </div>
                    <div class="verif-info-row">
                        <div class="verif-info-label">Tanggal SPP</div>
                        <div class="verif-info-value">{{ \Carbon\Carbon::parse($spp->tanggal_spp)->isoFormat('D MMMM Y') }}</div>
                    </div>
                    <div class="verif-info-row">
                        <div class="verif-info-label">Sifat Pembayaran</div>
                        <div class="verif-info-value">{{ $spp->tagihan->sifat_pembayaran ?? '-' }}</div>
                    </div>
                    <div class="verif-info-row">
                        <div class="verif-info-label">Kategori Pembayaran</div>
                        <div class="verif-info-value">{{ $spp->kategori_pembayaran ?? '-' }}</div>
                    </div>
                    <hr class="border-secondary opacity-10 my-3">
                    <div class="verif-info-row align-items-center mb-0">
                        <div class="verif-info-label">Nilai SPP (Netto)</div>
                        <div class="verif-info-value fs-4 text-success fw-bold font-monospace">Rp {{ number_format($spp->nominal_spp, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            <!-- Dasar Tagihan -->
            <div class="verif-card anim-fade-up delay-3">
                <div class="verif-card-header d-flex align-items-center gap-2"><i class="bi bi-journal-bookmark-fill text-primary"></i> Dasar Tagihan & Kontrak</div>
                <div class="card-body p-4">
                    <div class="verif-info-row">
                        <div class="verif-info-label">Nomor Tagihan</div>
                        <div class="verif-info-value">
                            <div class="fw-bold">{{ $spp->tagihan->nomor_tagihan ?? '-' }}</div>
                            <div class="small text-muted">{{ \Carbon\Carbon::parse($spp->tagihan->tanggal_tagihan)->isoFormat('d MMMM Y') }}</div>
                        </div>
                    </div>
                    <div class="verif-info-row">
                        <div class="verif-info-label">Nomor Kontrak / SPK</div>
                        <div class="verif-info-value fw-bold">{{ $spp->tagihan?->detailKontrak?->kontrakTermin?->kontrak?->nomor_spk ?? '-' }}</div>
                    </div>
                    <div class="verif-info-row">
                        <div class="verif-info-label">Vendor / Rekanan</div>
                        <div class="verif-info-value">{{ $spp->tagihan?->detailKontrak?->kontrakTermin?->kontrak?->vendor?->nama_pihak ?? $spp->tagihan?->pihak?->nama_pihak ?? '-' }}</div>
                    </div>
                    <div class="verif-info-row">
                        <div class="verif-info-label">Uraian / Termin</div>
                        <div class="verif-info-value">{{ $spp->tagihan?->detailKontrak?->kontrakTermin?->kontrak?->nama_pekerjaan ?? $spp->tagihan?->deskripsi ?? $spp->tagihan?->detailKontrak?->kontrakTermin?->keterangan_termin ?? '-' }}</div>
                    </div>
                    <div class="verif-info-row mb-0">
                        <div class="verif-info-label">Beban Anggaran (COA)</div>
                        <div class="verif-info-value">
                            @php
                                $coaShow = $spp->dipaRevisionItem?->coa ?? $spp->tagihan?->dipaRevisionItem?->coa;
                            @endphp
                            <div class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fs-6 mb-1">{{ $coaShow?->kode_mak_lengkap ?? '-' }}</div>
                            <div class="small fw-semibold text-muted lh-sm">{{ $coaShow?->nama_akun ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if($spp->tagihan->potonganTagihan && $spp->tagihan->potonganTagihan->count() > 0)
            <div class="verif-card anim-fade-up delay-3">
                <div class="verif-card-header d-flex align-items-center gap-2"><i class="bi bi-percent text-danger"></i> Rincian Potongan</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 text-uppercase small text-muted">Jenis Potongan</th>
                                    <th class="pe-4 text-end text-uppercase small text-muted">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalPotongan = 0; @endphp
                                @foreach($spp->tagihan->potonganTagihan as $potonganTagihan)
                                    @php $totalPotongan += (float) $potonganTagihan->nominal_potongan; @endphp
                                    <tr>
                                        <td class="ps-4 fw-semibold">
                                            {{ $potonganTagihan->pajak->jenis_pajak ?? $potonganTagihan->nama_pajak_snapshot ?? $potonganTagihan->jenis_potongan ?? '-' }}
                                            @if($potonganTagihan->persentase_tarif_snapshot)
                                                <span class="badge bg-light text-dark ms-1">({{ rtrim(rtrim(number_format($potonganTagihan->persentase_tarif_snapshot, 2, ',', '.'), '0'), ',') }}%)</span>
                                            @endif
                                        </td>
                                        <td class="pe-4 text-end text-danger fw-bold font-monospace">Rp {{ number_format((float) $potonganTagihan->nominal_potongan, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot style="background-color: #f8f9fa;">
                                <tr>
                                    <th class="ps-4 py-3 text-end fw-bold">Total Potongan:</th>
                                    <th class="pe-4 py-3 text-end text-danger fs-5 font-monospace">Rp {{ number_format($totalPotongan, 0, ',', '.') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>        <!-- Kolom Kanan: Dokumen & Aksi -->
        <div class="col-xl-4">
            
            <!-- Card Standing Instruction -->
            @include('spps.partials.standing_instruction_card')

            <!-- Dokumen Pendukung -->
            <div class="verif-card anim-fade-up delay-4 mb-4">
                <div class="verif-card-header d-flex align-items-center gap-2"><i class="bi bi-folder2-open text-primary"></i> Dokumen Pendukung</div>
                <div class="list-group list-group-flush list-group-borderless p-2">
                    @php
                        $dokumenItems = [
                            ['title' => 'Berita Acara (BAPP)', 'file' => $spp->tagihan->detailKontrak->file_bapp],
                            ['title' => 'Berita Acara (BAST)', 'file' => $spp->tagihan->detailKontrak->file_bast],
                            ['title' => 'Berita Acara (BAP)', 'file' => $spp->tagihan->detailKontrak->file_bap],
                            ['title' => 'Invoice Tagihan', 'file' => $spp->tagihan->detailKontrak->file_invoice],
                            ['title' => 'Kwitansi Pembayaran', 'file' => $spp->tagihan->detailKontrak->file_kwitansi],
                            ['title' => 'Faktur Pajak', 'file' => $spp->tagihan->detailKontrak->file_faktur_pajak],
                            ['title' => 'Lampiran Ekstra', 'file' => $spp->tagihan->detailKontrak->file_lampiran_lainnya],
                        ];
                    @endphp
                    @foreach($dokumenItems as $item)
                        @if($item['file'])
                        <a href="{{ Storage::url($item['file']) }}" target="_blank" class="list-group-item list-group-item-action d-flex align-items-center rounded-3 mb-1 border-0 hover-bg-light">
                            <div class="bg-danger bg-opacity-10 p-2 rounded me-3 text-danger"><i class="bi bi-file-earmark-pdf-fill fs-5"></i></div>
                            <div>
                                <div class="fw-bold text-dark">{{ $item['title'] }}</div>
                                <small class="text-primary fw-semibold">Buka Dokumen <i class="bi bi-box-arrow-up-right ms-1"></i></small>
                            </div>
                        </a>
                        @endif
                    @endforeach
                    @if(collect($dokumenItems)->whereNotNull('file')->isEmpty())
                        <div class="list-group-item text-center text-muted py-5 border-0">
                            <div class="bg-secondary bg-opacity-10 rounded-circle d-inline-flex p-3 mb-2"><i class="bi bi-folder-x fs-2"></i></div>
                            <div class="fw-semibold">Tidak ada lampiran dokumen</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Panel Aksi Verifikasi -->
            <div class="sticky-top anim-fade-up delay-4" style="top: 1.5rem; z-index: 1020;">
            @if(isset($activeRoleApprovals) && count($activeRoleApprovals) > 1)
                @foreach($activeRoleApprovals as $approvalData)
                    <div class="auth-approval-panel mb-4">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 45px; height: 45px;">
                                <i class="bi bi-shield-lock-fill fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">Tindakan Verifikasi</h5>
                                <div class="small text-muted fw-semibold">Peran Anda: <span class="text-primary">{{ $approvalData['role'] }}</span></div>
                            </div>
                        </div>

                        <form action="{{ $approvalData['approveRoute'] }}" method="POST" id="formVerifyApprove_{{ Str::slug($approvalData['role']) }}" onsubmit="return confirm('Apakah Anda yakin menyetujui SPP ini sebagai {{ $approvalData['role'] }}?');">
                            @csrf
                            <input type="hidden" name="approval_id" value="{{ $approvalData['approval_id'] }}">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary small text-uppercase">Catatan Persetujuan (Opsional)</label>
                                <textarea name="catatan" class="form-control form-control-lg bg-white border-0 shadow-sm" rows="2" placeholder="Tulis instruksi tambahan..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-success btn-pulse shadow-sm w-100 mb-3 py-3 fw-bold rounded-pill"><i class="bi bi-check-circle-fill me-2"></i> Setujui sebagai {{ $approvalData['role'] }}</button>
                        </form>

                        <div class="position-relative text-center my-4">
                            <hr class="text-secondary opacity-25">
                            <span class="position-absolute top-50 start-50 translate-middle bg-light px-3 small fw-bold text-muted text-uppercase rounded-pill border">Atau</span>
                        </div>

                        <form action="{{ $approvalData['revisiRoute'] }}" method="POST" id="formVerifyReject_{{ Str::slug($approvalData['role']) }}" onsubmit="return confirm('Apakah Anda yakin mengembalikan SPP ini untuk revisi sebagai {{ $approvalData['role'] }}?');">
                            @csrf
                            <input type="hidden" name="approval_id" value="{{ $approvalData['approval_id'] }}">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-danger small text-uppercase">Alasan Revisi / Penolakan <span class="text-danger">*</span></label>
                                <textarea name="catatan_revisi" class="form-control form-control-lg border-danger border-opacity-25 shadow-sm" rows="2" required placeholder="(Wajib) Tulis instruksi revisi..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-outline-danger w-100 py-3 fw-bold rounded-pill"><i class="bi bi-x-circle-fill me-2"></i> Kembalikan untuk Revisi</button>
                        </form>
                    </div>
                @endforeach

            @elseif($canAct)
                <div class="auth-approval-panel mb-4">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 45px; height: 45px;">
                            <i class="bi bi-shield-lock-fill fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Tindakan Verifikasi</h5>
                            <div class="small text-muted fw-semibold">Peran Anda: <span class="text-primary">{{ $roleLabel ?? 'Kasubbag' }}</span></div>
                        </div>
                    </div>

                    <form action="{{ route($approveRoute ?? 'verifikasi-kasubag.spp.approve', $spp->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin menyetujui SPP ini?');">
                        @csrf
                        <input type="hidden" name="approval_id" value="{{ count($activeRoleApprovals) === 1 ? $activeRoleApprovals[0]['approval_id'] : ($myApproval->id ?? '') }}">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase">Catatan Persetujuan (Opsional)</label>
                            <textarea name="catatan" class="form-control form-control-lg bg-white border-0 shadow-sm" rows="2" placeholder="Tulis instruksi tambahan..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success btn-pulse shadow-sm w-100 mb-3 py-3 fw-bold rounded-pill"><i class="bi bi-check-circle-fill me-2"></i> Setujui Dokumen SPP</button>
                    </form>

                    <div class="position-relative text-center my-4">
                        <hr class="text-secondary opacity-25">
                        <span class="position-absolute top-50 start-50 translate-middle bg-light px-3 small fw-bold text-muted text-uppercase rounded-pill border">Atau</span>
                    </div>

                    <form action="{{ route($revisiRoute ?? 'verifikasi-kasubag.spp.revisi', $spp->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin mengembalikan SPP ini untuk revisi?');">
                        @csrf
                        <input type="hidden" name="approval_id" value="{{ count($activeRoleApprovals) === 1 ? $activeRoleApprovals[0]['approval_id'] : ($myApproval->id ?? '') }}">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-danger small text-uppercase">Alasan Revisi / Penolakan <span class="text-danger">*</span></label>
                            <textarea name="catatan_revisi" class="form-control form-control-lg border-danger border-opacity-25 shadow-sm" rows="2" required placeholder="(Wajib) Tulis instruksi revisi..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100 py-3 fw-bold rounded-pill"><i class="bi bi-x-circle-fill me-2"></i> Kembalikan untuk Revisi</button>
                    </form>
                </div>
            @endif
            </div>
        </div>
    </div>
@endsection
