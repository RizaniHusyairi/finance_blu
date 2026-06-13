@php
    // PPK meminta perbaikan bukti transfer → form upload dibuka kembali
    // walau bukti lama masih tersimpan (file baru menggantikan yang lama).
    $sp2dRevisi = $state['sp2d']?->status === \App\Models\DokumenSp2d::STATUS_REVISI;

    $canUpload = auth()->user()?->hasAnyRole(['Bendahara Pengeluaran', 'Super Admin'])
        && $state['dokumenSiapBayar']
        && (! $state['buktiTransfer'] || $sp2dRevisi)
        && $state['sp2d'];

    $uploaded = (bool) $state['buktiTransfer'] && ! $sp2dRevisi;
@endphp

<div class="process-card doc-card mb-4" style="--tone: {{ $uploaded ? 'var(--pt-success)' : 'var(--pt-primary)' }}; --tone-soft: {{ $uploaded ? 'var(--tone-emerald-soft)' : 'var(--tone-indigo-soft)' }};">
    <div class="process-card-body p-4">
        {{-- Header --}}
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div class="d-flex align-items-center gap-3">
                <div class="doc-icon-tile {{ $canUpload ? 'waiting' : '' }}">
                    <i class="bi bi-bank2"></i>
                </div>
                <div>
                    <div class="text-secondary fw-bold fs-8 text-uppercase letter-spacing-1">Pembayaran Bank</div>
                    <div class="fw-bolder text-dark" style="font-size: 1.05rem;">Bukti Transfer</div>
                    <div class="text-secondary fs-8 mt-1"><i class="bi bi-person-badge me-1"></i>Diunggah Bendahara Pengeluaran — sekaligus mengajukan SP2D ke PPK</div>
                </div>
            </div>
            <span class="pt-status {{ $uploaded ? 'success' : ($canUpload ? 'warning shimmer' : 'neutral') }}">
                @if($uploaded)
                    <i class="bi bi-check-circle-fill"></i> Terunggah
                @elseif($canUpload)
                    <i class="bi bi-hourglass-split"></i> Menunggu Upload
                @else
                    <i class="bi bi-dash-circle"></i> Belum Aktif
                @endif
            </span>
        </div>

        @if($uploaded)
            <div class="p-3 rounded-4 border border-success-subtle d-flex flex-wrap justify-content-between align-items-center gap-3" style="background: linear-gradient(135deg, #ecfdf5, #d1fae5aa);">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 46px; height: 46px;">
                        <i class="bi bi-file-earmark-check-fill fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">Bukti transfer tersimpan aman</div>
                        <div class="text-muted small">{{ $state['buktiTransfer']->nama_file_asli ?? 'bukti_transfer.pdf' }}</div>
                    </div>
                </div>
                <a href="{{ route('arsip-sensitif.download', $state['buktiTransfer']->id) }}" class="btn btn-success fw-bold shadow-sm btn-pt-action">
                    <i class="bi bi-download"></i> Unduh File
                </a>
            </div>
        @elseif($canUpload)
            @if($sp2dRevisi)
                {{-- data-sky-ignore: panel kontekstual, jangan diubah jadi toast oleh sky-alerts. --}}
                <div class="alert alert-warning border-warning-subtle d-flex gap-2 align-items-center mb-3 py-2" data-sky-ignore>
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <div class="small fw-semibold">PPK meminta perbaikan bukti transfer — unggah file pengganti di bawah (lihat catatan revisi pada kartu SP2D).</div>
                </div>
            @endif
            <form method="POST" action="{{ route('proses-tagihan.bukti-transfer', $tagihan->id) }}" enctype="multipart/form-data" class="pt-upload">
                @csrf
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-cloud-arrow-up-fill text-primary fs-4"></i>
                    <div>
                        <div class="fw-bold text-dark">{{ $sp2dRevisi ? 'Unggah Ulang Bukti Transfer Bank' : 'Unggah Bukti Transfer Bank' }}</div>
                        <div class="text-muted fs-8">PDF/JPG/PNG maks. 5 MB — setelah terkirim, SP2D otomatis diajukan {{ $sp2dRevisi ? 'kembali ' : '' }}ke PPK untuk diterbitkan.</div>
                    </div>
                </div>
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="date" name="tanggal_sp2d" id="tanggal_sp2d" class="form-control border-primary-subtle fw-bold" value="{{ now()->toDateString() }}">
                            <label for="tanggal_sp2d">Tanggal Transfer (SP2D)</label>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="input-group input-group-lg">
                            <input type="file" name="bukti_transfer" class="form-control border-primary-subtle" accept=".pdf,.jpg,.jpeg,.png" required>
                            <button class="btn btn-primary fw-bold px-4 btn-pt-action shadow" type="submit" style="border-radius: 0 .5rem .5rem 0;">
                                <i class="bi bi-send-fill"></i> Kirim & Ajukan SP2D
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        @else
            <div class="pt-locked">
                <i class="bi bi-lock-fill fs-4"></i>
                <div class="small fw-semibold">
                    Formulir unggah aktif setelah <span class="text-dark">SPP, SPM, dan NPI</span> disetujui seluruh verifikator.
                </div>
            </div>
        @endif
    </div>
</div>
