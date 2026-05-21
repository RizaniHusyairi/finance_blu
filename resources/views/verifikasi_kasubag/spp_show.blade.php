@extends('layouts.app')
@section('title', 'Detail Verifikasi SPP — ' . ($roleLabel ?? 'Kasubbag'))

@push('css')
    <style>
        .auth-approval-panel { border: 2px dashed #0d6efd; border-radius: 12px; background: #f8fbff; padding: 1.5rem; position: relative;}
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1"><a href="{{ route($indexRoute ?? 'verifikasi-kasubag.spp.index') }}" class="text-decoration-none text-muted"><i class="bi bi-arrow-left"></i> Kembali</a> | Verifikasi SPP Kontrak</h4>
            <div class="text-muted small">{{ $roleLabel ?? 'Kepala Subbagian Keuangan dan Tata Usaha' }}</div>
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

    <!-- Workflow Status -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="card-title mb-3 fw-bold text-secondary"><i class="bi bi-people me-2"></i> Status Verifikasi Paralel</h6>
            
            @php
                $ppkUser = \App\Models\User::find($spp->tagihan->ppk_user_id) ?? \App\Models\User::role('PPK')->first();
                $koordinatorUser = \App\Models\User::find($spp->tagihan->koordinator_keuangan_user_id) ?? \App\Models\User::role('Koordinator Keuangan')->first();
                $kasubbagUser = \App\Models\User::find($spp->tagihan->kasubbag_user_id) ?? \App\Models\User::role('Kepala Subbagian Keuangan dan Tata Usaha')->first();

                // PPK Status
                $ppkColor = 'warning'; $ppkText = 'Menunggu';
                if ($ppkApproval) {
                    if ($ppkApproval->status === 'APPROVED') { $ppkColor = 'success'; $ppkText = 'Disetujui'; }
                    if ($ppkApproval->status === 'REVISION') { $ppkColor = 'danger'; $ppkText = 'Revisi'; }
                }

                // Koordinator Status
                $koorColor = 'warning'; $koorText = 'Menunggu';
                if (!empty($koordinatorApproval)) {
                    if ($koordinatorApproval->status === 'APPROVED') { $koorColor = 'success'; $koorText = 'Disetujui'; }
                    if ($koordinatorApproval->status === 'REVISION') { $koorColor = 'danger'; $koorText = 'Revisi'; }
                }

                // Kasubbag Status
                $kasColor = 'warning'; $kasText = 'Menunggu';
                if ($kasubbagApproval) {
                    if ($kasubbagApproval->status === 'APPROVED') { $kasColor = 'success'; $kasText = 'Disetujui'; }
                    if ($kasubbagApproval->status === 'REVISION') { $kasColor = 'danger'; $kasText = 'Revisi'; }
                }
            @endphp

            <ul class="list-group mb-0">
                <!-- PPK -->
                <li class="list-group-item px-3 py-2 border-start-0 border-end-0 border-top-0 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                <i class="bi bi-person-check fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-semibold text-dark">Pejabat Pembuat Komitmen</div>
                                <div class="small text-muted">{{ $ppkUser?->name ?? 'Belum Ditentukan' }}</div>
                                @if($ppkUser?->nip)<div class="text-muted font-monospace" style="font-size: .72rem;">NIP: {{ $ppkUser->nip }}</div>@endif
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-{{ $ppkColor }}">{{ $ppkText }}</span>
                            @if($ppkApproval && $ppkApproval->acted_at)
                                <div class="text-muted mt-1" style="font-size: 0.7rem;">{{ \Carbon\Carbon::parse($ppkApproval->acted_at)->format('d M Y H:i') }}</div>
                            @endif
                        </div>
                    </div>
                </li>
                <!-- Koordinator Keuangan -->
                <li class="list-group-item px-3 py-2 border-start-0 border-end-0 border-top-0 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                <i class="bi bi-person-gear fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-semibold text-dark">Koordinator Keuangan</div>
                                <div class="small text-muted">{{ $koordinatorUser?->name ?? 'Belum Ditentukan' }}</div>
                                @if($koordinatorUser?->nip)<div class="text-muted font-monospace" style="font-size: .72rem;">NIP: {{ $koordinatorUser->nip }}</div>@endif
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-{{ $koorColor }}">{{ $koorText }}</span>
                            @if(!empty($koordinatorApproval) && $koordinatorApproval->acted_at)
                                <div class="text-muted mt-1" style="font-size: 0.7rem;">{{ \Carbon\Carbon::parse($koordinatorApproval->acted_at)->format('d M Y H:i') }}</div>
                            @endif
                        </div>
                    </div>
                </li>
                <!-- Kasubbag -->
                <li class="list-group-item px-3 py-2 border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                <i class="bi bi-person-badge fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-semibold text-dark">Kepala Subbagian Keuangan dan Tata Usaha</div>
                                <div class="small text-muted">{{ $kasubbagUser?->name ?? 'Belum Ditentukan' }}</div>
                                @if($kasubbagUser?->nip)<div class="text-muted font-monospace" style="font-size: .72rem;">NIP: {{ $kasubbagUser->nip }}</div>@endif
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-{{ $kasColor }}">{{ $kasText }}</span>
                            @if($kasubbagApproval && $kasubbagApproval->acted_at)
                                <div class="text-muted mt-1" style="font-size: 0.7rem;">{{ \Carbon\Carbon::parse($kasubbagApproval->acted_at)->format('d M Y H:i') }}</div>
                            @endif
                        </div>
                    </div>
                </li>
            </ul>
            
            @if($latestRevisionNote)
                <div class="mt-3 p-3 bg-danger bg-opacity-10 border border-danger rounded text-danger">
                    <i class="bi bi-exclamation-triangle"></i> <strong>Catatan Revisi Terakhir ({{ $latestRevisionNote->role_code }}):</strong><br>
                    {{ $latestRevisionNote->catatan }}
                </div>
            @endif
        </div>
    </div>

    <div class="row row-cols-1 row-cols-xl-2 g-4">
        <!-- Kolom Kiri: Detail Informasi -->
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Informasi SPP</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Nomor SPP</div>
                        <div class="col-sm-8 fw-bold">{{ $spp->nomor_spp }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Tanggal SPP</div>
                        <div class="col-sm-8">{{ \Carbon\Carbon::parse($spp->tanggal_spp)->isoFormat('D MMMM Y') }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Sifat Pembayaran</div>
                        <div class="col-sm-8">{{ $spp->tagihan->sifat_pembayaran ?? '-' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Kategori Pembayaran</div>
                        <div class="col-sm-8">{{ $spp->kategori_pembayaran ?? '-' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Nilai SPP</div>
                        <div class="col-sm-8 fs-5 text-success fw-bold">Rp {{ number_format($spp->nominal_spp, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Dasar Tagihan / Kontrak</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Nomor Tagihan</div>
                        <div class="col-sm-8">
                            <span class="fw-bold">{{ $spp->tagihan->nomor_tagihan ?? '-' }}</span><br>
                            <small class="text-muted">{{ \Carbon\Carbon::parse($spp->tagihan->tanggal_tagihan)->isoFormat('d MMMM Y') }}</small>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Nomor Kontrak / SPK</div>
                        <div class="col-sm-8 fw-bold">{{ $spp->tagihan?->detailKontrak?->kontrakTermin?->kontrak?->nomor_spk ?? '-' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Vendor / Dibayarkan Kepada</div>
                        <div class="col-sm-8">{{ $spp->tagihan?->detailKontrak?->kontrakTermin?->kontrak?->vendor?->nama_pihak ?? $spp->tagihan?->pihak?->nama_pihak ?? '-' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Uraian / Termin Pekerjaan</div>
                        <div class="col-sm-8">{{ $spp->tagihan?->detailKontrak?->kontrakTermin?->kontrak?->nama_pekerjaan ?? $spp->tagihan?->deskripsi ?? $spp->tagihan?->detailKontrak?->kontrakTermin?->keterangan_termin ?? '-' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Beban Anggaran (COA)</div>
                        <div class="col-sm-8">
                            @php
                                $coaShow = $spp->dipaRevisionItem?->coa ?? $spp->tagihan?->dipaRevisionItem?->coa;
                            @endphp
                            <span class="badge bg-primary">{{ $coaShow?->kode_mak_lengkap ?? '-' }}</span>
                            {{ $coaShow?->nama_akun ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            @if($spp->tagihan->potonganTagihan && $spp->tagihan->potonganTagihan->count() > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Potongan</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Jenis Potongan</th>
                                    <th class="text-end">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalPotongan = 0; @endphp
                                @foreach($spp->tagihan->potonganTagihan as $potonganTagihan)
                                    @php $totalPotongan += (float) $potonganTagihan->nominal_potongan; @endphp
                                    <tr>
                                        <td>
                                            {{ $potonganTagihan->pajak->jenis_pajak ?? $potonganTagihan->nama_pajak_snapshot ?? $potonganTagihan->jenis_potongan ?? '-' }}
                                            @if($potonganTagihan->persentase_tarif_snapshot)
                                                <small class="text-muted">({{ rtrim(rtrim(number_format($potonganTagihan->persentase_tarif_snapshot, 2, ',', '.'), '0'), ',') }}%)</small>
                                            @endif
                                        </td>
                                        <td class="text-end text-danger fw-bold">Rp {{ number_format((float) $potonganTagihan->nominal_potongan, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="1" class="text-end">Total Potongan:</th>
                                    <th class="text-end text-danger fs-6">Rp {{ number_format($totalPotongan, 0, ',', '.') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Kolom Kanan: Dokumen & Aksi -->
        <div class="col-xl-4">
            <!-- Standing Instruction -->
            @include('spps.partials.standing_instruction_card')

            <!-- Dokumen Pendukung -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Dokumen Pendukung Kontrak</h6>
                </div>
                <div class="list-group list-group-flush list-group-borderless">
                    @php
                        $dokumenItems = [
                            ['title' => 'BAPP', 'file' => $spp->tagihan->detailKontrak->file_bapp],
                            ['title' => 'BAST', 'file' => $spp->tagihan->detailKontrak->file_bast],
                            ['title' => 'BAP', 'file' => $spp->tagihan->detailKontrak->file_bap],
                            ['title' => 'Invoice', 'file' => $spp->tagihan->detailKontrak->file_invoice],
                            ['title' => 'Kwitansi', 'file' => $spp->tagihan->detailKontrak->file_kwitansi],
                            ['title' => 'Faktur Pajak', 'file' => $spp->tagihan->detailKontrak->file_faktur_pajak],
                            ['title' => 'Lampiran Lain', 'file' => $spp->tagihan->detailKontrak->file_lampiran_lainnya],
                        ];
                    @endphp
                    @foreach($dokumenItems as $item)
                        @if($item['file'])
                        <a href="{{ Storage::url($item['file']) }}" target="_blank" class="list-group-item list-group-item-action d-flex align-items-center">
                            <i class="bi bi-file-earmark-pdf fs-4 text-danger me-3"></i>
                            <div>
                                <div class="fw-bold">{{ $item['title'] }}</div>
                                <small class="text-primary">Lihat Dokumen</small>
                            </div>
                        </a>
                        @endif
                    @endforeach
                    @if(collect($dokumenItems)->whereNotNull('file')->isEmpty())
                        <div class="list-group-item text-center text-muted py-4">
                            <i class="bi bi-folder-x fs-1 mb-2 d-block"></i>
                            Tidak ada dokumen yang diunggah
                        </div>
                    @endif
                </div>
            </div>

            <!-- Panel Aksi Verifikasi -->
            <div class="sticky-top" style="top: 1.5rem; z-index: 1020;">
            @if(isset($activeRoleApprovals) && count($activeRoleApprovals) > 1)
                @foreach($activeRoleApprovals as $approvalData)
                    <div class="auth-approval-panel shadow-sm mb-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-shield-lock-fill text-primary fs-3"></i>
                            <div>
                                <h5 class="fw-bold mb-0 text-primary">Tindakan Verifikasi</h5>
                                <div class="small text-muted">Menunggu persetujuan Anda sebagai <strong>{{ $approvalData['role'] }}</strong></div>
                            </div>
                        </div>

                        <form action="{{ $approvalData['approveRoute'] }}" method="POST" id="formVerifyApprove_{{ Str::slug($approvalData['role']) }}" onsubmit="return confirm('Apakah Anda yakin menyetujui SPP ini sebagai {{ $approvalData['role'] }}?');">
                            @csrf
                            <input type="hidden" name="approval_id" value="{{ $approvalData['approval_id'] }}">
                            <label class="form-label fw-semibold">Catatan Keputusan</label>
                            <textarea name="catatan" class="form-control mb-3" rows="2" placeholder="(Opsional) Tulis catatan persetujuan Anda..."></textarea>
                            <button type="submit" class="btn btn-success shadow-sm w-100 mb-2 py-2 fw-bold"><i class="bi bi-check-circle me-1"></i> Setujui sebagai {{ $approvalData['role'] }}</button>
                        </form>

                        <hr class="text-primary opacity-25">

                        <form action="{{ $approvalData['revisiRoute'] }}" method="POST" id="formVerifyReject_{{ Str::slug($approvalData['role']) }}" onsubmit="return confirm('Apakah Anda yakin mengembalikan SPP ini untuk revisi sebagai {{ $approvalData['role'] }}?');">
                            @csrf
                            <input type="hidden" name="approval_id" value="{{ $approvalData['approval_id'] }}">
                            <label class="form-label fw-semibold">Alasan Penolakan / Revisi <span class="text-danger">*</span></label>
                            <textarea name="catatan_revisi" class="form-control mb-3" rows="2" required placeholder="(Wajib) Tulis instruksi revisi untuk Operator..."></textarea>
                            <button type="submit" class="btn btn-outline-danger w-100 py-2"><i class="bi bi-x-circle me-1"></i> Kembalikan untuk Revisi</button>
                        </form>
                    </div>
                @endforeach

            @elseif($canAct)
                <div class="auth-approval-panel shadow-sm mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-shield-lock-fill text-primary fs-3"></i>
                        <div>
                            <h5 class="fw-bold mb-0 text-primary">Tindakan Verifikasi</h5>
                            <div class="small text-muted">Menunggu persetujuan Anda sebagai <strong>{{ $roleLabel ?? 'Kasubbag' }}</strong></div>
                        </div>
                    </div>

                    <form action="{{ route($approveRoute ?? 'verifikasi-kasubag.spp.approve', $spp->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin menyetujui SPP ini?');">
                        @csrf
                        <input type="hidden" name="approval_id" value="{{ count($activeRoleApprovals) === 1 ? $activeRoleApprovals[0]['approval_id'] : ($myApproval->id ?? '') }}">
                        <label class="form-label fw-semibold">Catatan Keputusan</label>
                        <textarea name="catatan" class="form-control mb-3" rows="2" placeholder="(Opsional) Tulis catatan persetujuan Anda..."></textarea>
                        <button type="submit" class="btn btn-success shadow-sm w-100 mb-2 py-2 fw-bold"><i class="bi bi-check-circle me-1"></i> Setujui SPP</button>
                    </form>

                    <hr class="text-primary opacity-25">

                    <form action="{{ route($revisiRoute ?? 'verifikasi-kasubag.spp.revisi', $spp->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin mengembalikan SPP ini untuk revisi?');">
                        @csrf
                        <input type="hidden" name="approval_id" value="{{ count($activeRoleApprovals) === 1 ? $activeRoleApprovals[0]['approval_id'] : ($myApproval->id ?? '') }}">
                        <label class="form-label fw-semibold">Alasan Penolakan / Revisi <span class="text-danger">*</span></label>
                        <textarea name="catatan_revisi" class="form-control mb-3" rows="2" required placeholder="(Wajib) Tulis instruksi revisi untuk Operator..."></textarea>
                        <button type="submit" class="btn btn-outline-danger w-100 py-2"><i class="bi bi-x-circle me-1"></i> Kembalikan untuk Revisi</button>
                    </form>
                </div>
            @endif
            </div>

        </div>
    </div>
@endsection
