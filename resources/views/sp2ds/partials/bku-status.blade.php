@php
    $bkuPengeluaran = $tagihan?->bkuPengeluaran?->sortByDesc('tanggal_transaksi')->sortByDesc('id')->first();
    $isExecuted = $sp2d?->status === \App\Models\DokumenSp2d::STATUS_EXECUTED;
    $isFinal = $sp2d && in_array($sp2d->status, [
        \App\Models\DokumenSp2d::STATUS_DISETUJUI_FINAL,
        \App\Models\DokumenSp2d::STATUS_MENUNGGU_UPLOAD,
        \App\Models\DokumenSp2d::STATUS_SP2D_TERBIT,
    ], true);
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="d-flex align-items-start gap-3">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 {{ $bkuPengeluaran ? 'bg-success' : ($isExecuted ? 'bg-warning' : 'bg-secondary') }} text-white" style="width:42px;height:42px;">
                <i class="bi {{ $bkuPengeluaran ? 'bi-journal-check' : 'bi-journal-text' }}"></i>
            </span>
            <div class="flex-grow-1">
                <div class="fw-bold">
                    @if($bkuPengeluaran)
                        BKU Keluar Tercatat
                    @elseif($isExecuted)
                        Menunggu Posting BKU
                    @elseif($isFinal)
                        Siap Eksekusi SP2D
                    @else
                        Belum Masuk BKU
                    @endif
                </div>
                <div class="small text-muted">
                    @if($bkuPengeluaran)
                        {{ $bkuPengeluaran->nomor_bukti }} | {{ optional($bkuPengeluaran->tanggal_transaksi)->format('d M Y') }} | Rp {{ number_format($bkuPengeluaran->nominal, 0, ',', '.') }}
                    @elseif($isExecuted)
                        SP2D sudah executed. Jika kontrak memiliki pajak, BKU dibuat setelah seluruh NTPN tersetor.
                    @elseif($isFinal)
                        Upload bukti transfer SP2D untuk mencatat arus kas keluar.
                    @else
                        BKU keluar baru dibuat setelah SP2D selesai dieksekusi.
                    @endif
                </div>
                @if($bkuPengeluaran)
                    <a href="{{ route('pembukuan.bku.show', $bkuPengeluaran->id) }}" class="btn btn-sm btn-outline-success mt-2">
                        <i class="bi bi-eye me-1"></i>Lihat BKU
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
