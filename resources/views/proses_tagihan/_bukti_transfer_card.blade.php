@php
    $canUpload = auth()->user()?->hasAnyRole(['Bendahara Pengeluaran', 'Super Admin'])
        && $state['dokumenSiapBayar']
        && ! $state['buktiTransfer']
        && $state['sp2d'];
@endphp

<div class="card process-card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
            <div>
                <div class="process-section-title">Bukti Transfer</div>
                <div class="text-muted small">Diunggah Bendahara Pengeluaran sebelum SP2D diajukan ke PPK.</div>
            </div>
            <span class="badge {{ $state['buktiTransfer'] ? 'bg-success' : 'bg-secondary' }}">
                {{ $state['buktiTransfer'] ? 'Ada' : 'Belum ada' }}
            </span>
        </div>

        @if($state['buktiTransfer'])
            <a href="{{ route('arsip-sensitif.download', $state['buktiTransfer']->id) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-download me-1"></i>Unduh Bukti Transfer
            </a>
        @elseif($canUpload)
            <form method="POST" action="{{ route('proses-tagihan.bukti-transfer', $tagihan->id) }}" enctype="multipart/form-data" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-5">
                    <label class="form-label small text-muted mb-1">Tanggal SP2D</label>
                    <input type="date" name="tanggal_sp2d" class="form-control" value="{{ now()->toDateString() }}">
                </div>
                <div class="col-md-5">
                    <label class="form-label small text-muted mb-1">File bukti</label>
                    <input type="file" name="bukti_transfer" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-upload"></i></button>
                </div>
            </form>
        @else
            <div class="text-muted small">Bukti transfer aktif setelah SPP, SPM, dan NPI semuanya disetujui.</div>
        @endif
    </div>
</div>
