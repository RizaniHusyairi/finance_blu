{{-- Kartu Persetujuan KPA (Standing Instruction) — versi halaman Proses Tagihan terpadu.
     PPK mengajukan via WA setelah COA lengkap; KPA menyetujui lewat magic link. --}}
@php
    $kpaStatus = $tagihan->kpa_approval_status;
    $coaDone = $state['coaDone'] ?? app(\App\Services\DokumenChainService::class)->isCoaComplete($tagihan);
    // Tagihan yang dikembalikan untuk revisi (REVISI_*) tidak boleh diajukan
    // ke KPA sampai diverifikasi ulang — COA-nya masih terisi, jadi cek
    // coaDone saja tidak cukup.
    $tagihanApproved = $state['tagihanApproved'] ?? app(\App\Services\DokumenChainService::class)->isTagihanFullyApproved($tagihan);
    $isPpk = auth()->user()?->hasAnyRole(['PPK', 'Super Admin']);

    [$kpaChip, $kpaIcon, $kpaText] = match ($kpaStatus) {
        'PENDING_KPA' => ['warning shimmer', 'bi-hourglass-split', 'Menunggu KPA'],
        'APPROVED' => ['success', 'bi-check-circle-fill', 'Disetujui KPA'],
        'REJECTED' => ['danger', 'bi-x-circle-fill', 'Ditolak KPA'],
        default => ['neutral', 'bi-dash-circle', 'Belum Diajukan'],
    };

@endphp

<div class="process-card doc-card mb-4" style="--tone: var(--pt-primary); --tone-soft: var(--tone-indigo-soft);">
    @if($kpaStatus === 'APPROVED')
        <div class="position-absolute top-0 end-0 p-3 opacity-25" style="transform: scale(2.4) translate(12%, -12%); pointer-events: none;">
            <i class="bi bi-shield-fill-check text-success" style="font-size: 4rem;"></i>
        </div>
    @endif

    <div class="process-card-body p-4 position-relative z-index-1">
        {{-- Header --}}
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div class="d-flex align-items-center gap-3">
                <div class="doc-icon-tile {{ $kpaStatus === 'PENDING_KPA' ? 'waiting' : '' }}">
                    <i class="bi bi-shield-fill-check"></i>
                </div>
                <div>
                    <div class="text-secondary fw-bold fs-8 text-uppercase letter-spacing-1">Standing Instruction</div>
                    <div class="fw-bolder text-dark" style="font-size: 1.05rem;">Persetujuan KPA</div>
                    <div class="text-secondary fs-8 mt-1"><i class="bi bi-whatsapp me-1"></i>Diajukan PPK via WhatsApp setelah COA dibebankan</div>
                </div>
            </div>
            <span class="pt-status {{ $kpaChip }}"><i class="bi {{ $kpaIcon }}"></i> {{ $kpaText }}</span>
        </div>

        {{-- ===== Belum diajukan / Ditolak ===== --}}
        @if(! $kpaStatus || $kpaStatus === 'REJECTED')
            @if($kpaStatus === 'REJECTED')
                <div class="alert alert-danger pt-alert d-flex align-items-start gap-3 mb-3">
                    <i class="bi bi-exclamation-octagon-fill fs-4"></i>
                    <div>
                        <div class="fw-bold">Tagihan ditolak oleh KPA</div>
                        <div class="small fst-italic">"{{ $tagihan->kpa_approval_notes ?? 'Tidak ada catatan.' }}"</div>
                    </div>
                </div>
            @endif

            @if($isPpk)
                @if(! $tagihanApproved)
                    <div class="pt-locked mb-3">
                        <i class="bi bi-lock-fill fs-4"></i>
                        <div class="small fw-semibold">
                            Tagihan sedang dalam proses <span class="text-dark">verifikasi / revisi</span>.
                            Standing Instruction dapat diajukan ke KPA setelah tagihan disetujui seluruh verifikator.
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary w-100 btn-pt-action justify-content-center py-2" disabled>
                        <i class="bi bi-whatsapp"></i> Ajukan Persetujuan ke KPA via WA
                    </button>
                @elseif($tagihan->chain_correction_target)
                    <div class="pt-locked mb-3">
                        <i class="bi bi-lock-fill fs-4"></i>
                        <div class="small fw-semibold">
                            Selesaikan perbaikan
                            <span class="text-dark">{{ $tagihan->chain_correction_target === 'PAJAK' ? 'pajak & faktur pajak (Operator BLU)' : 'pembebanan COA' }}</span>
                            yang diminta verifikator terlebih dahulu sebelum mengajukan persetujuan ke KPA.
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary w-100 btn-pt-action justify-content-center py-2" disabled>
                        <i class="bi bi-whatsapp"></i> Ajukan Persetujuan ke KPA via WA
                    </button>
                @elseif(! $coaDone)
                    <div class="pt-locked mb-3">
                        <i class="bi bi-lock-fill fs-4"></i>
                        <div class="small fw-semibold">Lengkapi <span class="text-dark">pembebanan COA</span> terlebih dahulu sebelum mengajukan persetujuan ke KPA.</div>
                    </div>
                    <button type="button" class="btn btn-secondary w-100 btn-pt-action justify-content-center py-2" disabled>
                        <i class="bi bi-whatsapp"></i> Ajukan Persetujuan ke KPA via WA
                    </button>
                @else
                    <form action="{{ route('kpa.approval.send-wa', $tagihan->id) }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100 btn-pt-action justify-content-center py-2 shadow">
                            <i class="bi bi-whatsapp fs-5"></i> {{ $kpaStatus === 'REJECTED' ? 'Ajukan Ulang' : 'Ajukan Persetujuan' }} ke KPA via WA
                        </button>
                    </form>
                @endif
            @else
                <div class="d-flex align-items-center gap-2 text-muted small bg-light p-2 px-3 rounded-3 border border-light-subtle">
                    <i class="bi bi-info-circle"></i> Menunggu PPK mengajukan Standing Instruction ke KPA.
                </div>
            @endif

        {{-- ===== Menunggu KPA ===== --}}
        @elseif($kpaStatus === 'PENDING_KPA' && ! $tagihanApproved)
            {{-- SI terlanjur dikirim tetapi tagihan kemudian dikembalikan untuk
                 revisi — permintaan lama tidak berlaku; tautan & kirim ulang
                 disembunyikan dan aksi KPA ditolak server. --}}
            <div class="pt-locked">
                <i class="bi bi-lock-fill fs-4"></i>
                <div class="small fw-semibold">
                    Permintaan persetujuan KPA <span class="text-dark">ditangguhkan</span> karena tagihan sedang
                    dalam proses verifikasi / revisi. Tautan yang sudah terkirim tidak dapat dipakai menyetujui —
                    ajukan ulang ke KPA setelah tagihan disetujui seluruh verifikator.
                </div>
            </div>

        @elseif($kpaStatus === 'PENDING_KPA')
            <div class="p-3 rounded-4 border border-warning-subtle mb-3" style="background: linear-gradient(135deg, #fffbeb, #fef3c7aa);">
                <div class="d-flex align-items-start gap-3">
                    <div class="spinner-border spinner-border-sm text-warning mt-1" role="status"></div>
                    <div class="w-100">
                        <div class="fw-bold text-dark mb-1">Tautan persetujuan telah dikirim ke WhatsApp KPA</div>
                        <div class="text-secondary small mb-0">
                            Draft dokumen pencairan dibuat otomatis begitu KPA menyetujui.
                            Tautan persetujuan berlaku 24 jam — gunakan <span class="fw-semibold">Kirim Ulang Pesan WA</span> bila KPA belum menerima.
                        </div>
                    </div>
                </div>
            </div>

            @if($isPpk)
                <form action="{{ route('kpa.approval.send-wa', $tagihan->id) }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary w-100 btn-pt-action justify-content-center">
                        <i class="bi bi-arrow-clockwise"></i> Kirim Ulang Pesan WA
                    </button>
                </form>
            @endif

        {{-- ===== Disetujui ===== --}}
        @elseif($kpaStatus === 'APPROVED')
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="process-muted mb-1">Disetujui Oleh</div>
                    <div class="fw-bold text-dark">{{ $tagihan->kpaApprover?->profilable?->nama_lengkap ?? $tagihan->kpaApprover?->name ?? 'KPA' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="process-muted mb-1">Waktu Persetujuan</div>
                    <div class="fw-bold text-dark">{{ $tagihan->kpa_approved_at ? \Carbon\Carbon::parse($tagihan->kpa_approved_at)->translatedFormat('d M Y H:i') : '-' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="process-muted mb-1">Catatan KPA</div>
                    <div class="fw-semibold text-dark fst-italic">"{{ $tagihan->kpa_approval_notes ?? 'Tidak ada catatan.' }}"</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-3 p-2 px-3 rounded-pill bg-success bg-opacity-10 text-success fw-bold small">
                <i class="bi bi-check-circle-fill"></i> KPA telah menyetujui — draft SPP/SPM/NPI/SP2D dibuat otomatis.
            </div>
        @endif
    </div>
</div>
