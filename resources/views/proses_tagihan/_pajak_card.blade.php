@php
    $canEditPajak = auth()->user()?->hasAnyRole(['Bendahara Pengeluaran', 'Super Admin']);
    $billingRoute = match ($tagihan->tipe_tagihan) {
        'KONTRAK' => 'pajak-potongan.kontrak.billing',
        'HONORARIUM' => 'pajak-potongan.honor.billing',
        default => 'pajak-potongan.billing',
    };
    $ntpnRoute = match ($tagihan->tipe_tagihan) {
        'KONTRAK' => 'pajak-potongan.kontrak.ntpn',
        'HONORARIUM' => 'pajak-potongan.honor.ntpn',
        default => 'pajak-potongan.ntpn',
    };
    $hasPajak = $state['potonganPajak']->isNotEmpty();
    $isPajakDone = $state['pajakSettled'];

    $toneVar = ! $hasPajak ? 'var(--tone-slate)' : ($isPajakDone ? 'var(--pt-success)' : 'var(--pt-warning)');
    $toneSoft = ! $hasPajak ? 'var(--tone-slate-soft)' : ($isPajakDone ? 'var(--tone-emerald-soft)' : 'var(--tone-amber-soft)');
@endphp

<div class="process-card doc-card mb-4" style="--tone: {{ $toneVar }}; --tone-soft: {{ $toneSoft }};">
    <div class="process-card-body p-4">
        {{-- Header --}}
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div class="d-flex align-items-center gap-3">
                <div class="doc-icon-tile">
                    <i class="bi bi-receipt-cutoff"></i>
                </div>
                <div>
                    <div class="text-secondary fw-bold fs-8 text-uppercase letter-spacing-1">Kewajiban Perpajakan</div>
                    <div class="fw-bolder text-dark" style="font-size: 1.05rem;">Penyetoran Pajak</div>
                    <div class="text-secondary fs-8 mt-1"><i class="bi bi-123 me-1"></i>Kode Billing → NTPN + bukti setor, dicatat Bendahara Pengeluaran</div>
                </div>
            </div>
            <span class="pt-status {{ !$hasPajak ? 'neutral' : ($isPajakDone ? 'success' : 'warning shimmer') }}">
                @if(!$hasPajak)
                    <i class="bi bi-dash-circle"></i> Tidak Ada Pajak
                @elseif($isPajakDone)
                    <i class="bi bi-check-circle-fill"></i> Seluruh Pajak Disetor
                @else
                    <i class="bi bi-hourglass-split"></i> Menunggu Penyetoran
                @endif
            </span>
        </div>

        @if(!$hasPajak)
            <div class="pt-locked">
                <i class="bi bi-emoji-smile fs-4"></i>
                <div class="small fw-semibold">Tagihan ini tidak memiliki potongan pajak yang harus disetor.</div>
            </div>
        @else
            <div class="d-flex flex-column gap-3">
                @foreach($state['potonganPajak'] as $potongan)
                    @php
                        $stepBilling = filled($potongan->kode_billing);
                        $stepNtpn = filled($potongan->ntpn);
                        $arsipBilling = optional($potongan->arsipDokumen)->firstWhere('jenis_dokumen', 'KODE_BILLING');
                        $arsipBpn = optional($potongan->arsipDokumen)->firstWhere('jenis_dokumen', 'BUKTI_SETOR_PAJAK');
                        $arsipBppu = optional($potongan->arsipDokumen)->firstWhere('jenis_dokumen', 'BPPU');
                        $canViewArsip = Route::has('arsip-sensitif.download');
                    @endphp
                    <div class="border rounded-4 p-3 {{ $stepNtpn ? 'border-success-subtle' : 'border-light-subtle' }}" style="background: {{ $stepNtpn ? 'linear-gradient(135deg,#f0fdf4,#fff)' : '#fcfcfe' }};">
                        {{-- Baris info pajak + langkah mini --}}
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <div class="fw-bold text-dark">{{ $potongan->deskripsi }}</div>
                                <div class="text-danger fw-bolder">Rp {{ number_format((float) $potongan->nominal_potongan, 0, ',', '.') }}</div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="pt-approver {{ $stepBilling ? 'ok' : 'idle' }}" title="Langkah 1: Kode Billing">
                                    <span class="ava"><i class="bi {{ $stepBilling ? 'bi-check-lg' : 'bi-1-circle' }}"></i></span> Billing
                                </span>
                                <i class="bi bi-arrow-right text-secondary opacity-50"></i>
                                <span class="pt-approver {{ $stepNtpn ? 'ok' : ($stepBilling ? 'wait' : 'idle') }}" title="Langkah 2: NTPN & Bukti Setor">
                                    <span class="ava"><i class="bi {{ $stepNtpn ? 'bi-check-lg' : 'bi-2-circle' }}"></i></span> NTPN
                                </span>
                            </div>
                        </div>

                        <div class="row g-3">
                            {{-- Kode Billing --}}
                            <div class="col-md-6">
                                @if($canEditPajak && !$stepNtpn && Route::has($billingRoute))
                                    <form method="POST" action="{{ route($billingRoute, $potongan->id) }}" enctype="multipart/form-data" class="bg-white p-3 rounded-3 border h-100 d-flex flex-column">
                                        @csrf
                                        <div class="fw-bold fs-7 text-secondary text-uppercase mb-2"><i class="bi bi-upc-scan me-1 text-primary"></i>Kode Billing</div>
                                        <input type="text" name="kode_billing" class="form-control form-control-sm border-primary-subtle font-monospace fw-bold mb-2" value="{{ $potongan->kode_billing }}" placeholder="Masukkan kode billing...">
                                        <input type="file" name="file_billing" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                                        <div class="text-muted fs-8 mt-1 mb-2">
                                            {{ $arsipBilling ? 'Unggah ulang hanya bila ingin mengganti lampiran.' : 'Lampiran E-Billing (cetakan DJP)' }}
                                        </div>
                                        @if($arsipBilling && $canViewArsip)
                                            <a href="{{ route('arsip-sensitif.download', $arsipBilling->id) }}" target="_blank" class="d-inline-flex align-items-center gap-1 text-success fw-semibold fs-8 text-decoration-none mb-2">
                                                <i class="bi bi-paperclip"></i> Lihat E-Billing
                                                <span class="text-muted fw-normal">({{ \Illuminate\Support\Str::limit($arsipBilling->nama_file_asli, 22) }})</span>
                                            </a>
                                        @endif
                                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold mt-auto">
                                            <i class="bi bi-save-fill me-1"></i>Simpan Kode Billing
                                        </button>
                                    </form>
                                @else
                                    <div class="bg-white p-3 rounded-3 border h-100">
                                        <div class="fw-bold fs-7 text-secondary text-uppercase mb-2"><i class="bi bi-upc-scan me-1"></i>Kode Billing</div>
                                        @if($stepBilling)
                                            <span class="fw-bold font-monospace bg-light px-2 py-1 rounded border d-inline-block">{{ $potongan->kode_billing }}</span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border">Belum diinput</span>
                                        @endif
                                        @if($arsipBilling && $canViewArsip)
                                            <div class="mt-2">
                                                <a href="{{ route('arsip-sensitif.download', $arsipBilling->id) }}" target="_blank" class="d-inline-flex align-items-center gap-1 text-success fw-semibold fs-8 text-decoration-none">
                                                    <i class="bi bi-paperclip"></i> Lihat E-Billing
                                                    <span class="text-muted fw-normal">({{ \Illuminate\Support\Str::limit($arsipBilling->nama_file_asli, 22) }})</span>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            {{-- NTPN --}}
                            <div class="col-md-6">
                                @if($canEditPajak && !$stepNtpn && Route::has($ntpnRoute))
                                    <form method="POST" action="{{ route($ntpnRoute, $potongan->id) }}" enctype="multipart/form-data" class="bg-white p-3 rounded-3 border h-100 d-flex flex-column {{ !$stepBilling ? 'opacity-50' : '' }}">
                                        @csrf
                                        <div class="fw-bold fs-7 text-secondary text-uppercase mb-2"><i class="bi bi-patch-check me-1 text-success"></i>NTPN & Bukti Setor</div>
                                        <input type="text" name="ntpn" class="form-control form-control-sm border-success-subtle font-monospace fw-bold mb-2" value="{{ $potongan->ntpn }}" placeholder="Masukkan NTPN..." {{ !$stepBilling ? 'disabled' : '' }}>
                                        <input type="file" name="file_bukti_setor" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" {{ !$stepBilling ? 'disabled' : '' }}>
                                        <div class="text-muted fs-8 mt-1 mb-2">{{ $stepBilling ? 'Bukti setor (BPN) wajib dilampirkan' : 'Isi kode billing terlebih dahulu' }}</div>
                                        @if(($arsipBpn || $arsipBppu) && $canViewArsip)
                                            <div class="d-flex flex-column gap-1 mb-2">
                                                @if($arsipBpn)
                                                    <a href="{{ route('arsip-sensitif.download', $arsipBpn->id) }}" target="_blank" class="d-inline-flex align-items-center gap-1 text-success fw-semibold fs-8 text-decoration-none">
                                                        <i class="bi bi-paperclip"></i> Lihat Bukti Setor (BPN)
                                                    </a>
                                                @endif
                                                @if($arsipBppu)
                                                    <a href="{{ route('arsip-sensitif.download', $arsipBppu->id) }}" target="_blank" class="d-inline-flex align-items-center gap-1 text-success fw-semibold fs-8 text-decoration-none">
                                                        <i class="bi bi-paperclip"></i> Lihat BPPU
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                        <button type="submit" class="btn btn-success btn-sm w-100 fw-bold mt-auto" {{ !$stepBilling ? 'disabled' : '' }}>
                                            <i class="bi bi-save-fill me-1"></i>Simpan NTPN &amp; Bukti Setor
                                        </button>
                                    </form>
                                @else
                                    <div class="bg-white p-3 rounded-3 border h-100">
                                        <div class="fw-bold fs-7 text-secondary text-uppercase mb-2"><i class="bi bi-patch-check me-1"></i>NTPN</div>
                                        @if($stepNtpn)
                                            <span class="fw-bold font-monospace bg-light px-2 py-1 rounded border d-inline-block">{{ $potongan->ntpn }}</span>
                                            <div class="text-success fs-8 fw-bold mt-2"><i class="bi bi-check-circle-fill me-1"></i>Sudah disetor</div>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border">Belum disetor</span>
                                        @endif
                                        @if(($arsipBpn || $arsipBppu) && $canViewArsip)
                                            <div class="d-flex flex-column gap-1 mt-2">
                                                @if($arsipBpn)
                                                    <a href="{{ route('arsip-sensitif.download', $arsipBpn->id) }}" target="_blank" class="d-inline-flex align-items-center gap-1 text-success fw-semibold fs-8 text-decoration-none">
                                                        <i class="bi bi-paperclip"></i> Lihat Bukti Setor (BPN)
                                                    </a>
                                                @endif
                                                @if($arsipBppu)
                                                    <a href="{{ route('arsip-sensitif.download', $arsipBppu->id) }}" target="_blank" class="d-inline-flex align-items-center gap-1 text-success fw-semibold fs-8 text-decoration-none">
                                                        <i class="bi bi-paperclip"></i> Lihat BPPU
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @unless($isPajakDone)
                <div class="d-flex align-items-center gap-2 text-muted fs-8 mt-3 px-1">
                    <i class="bi bi-info-circle"></i> Tagihan tercatat di BKU (pembukuan) setelah seluruh pajak ber-NTPN.
                </div>
            @endunless
        @endif
    </div>
</div>
