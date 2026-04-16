<div class="card mb-4 border-0 shadow-sm bg-primary text-white">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center mb-2">
                    <span class="badge bg-light text-primary me-2 px-3 py-2 rounded-pill fw-bold">
                        {{ $tagihan->nomor_tagihan }}
                    </span>
                    <span class="badge bg-white text-dark opacity-75 rounded-pill">
                        <i class="bi bi-calendar-check me-1"></i> 
                        Disetujui: {{ $tagihan->waktu_verifikasi_ppk ? $tagihan->waktu_verifikasi_ppk->format('d M Y') : '-' }}
                    </span>
                </div>
                <h4 class="fw-bold mb-2 text-white">{{ $tagihan->deskripsi }}</h4>
                <p class="mb-0 text-white opacity-75 w-75">
                    Dokumen Perjalanan Dinas ini telah selesai diverifikasi dan setiap komponen biayanya 
                    siap untuk diproses menjadi SPP secara parsial (per item).
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <div class="p-3 bg-white bg-opacity-10 rounded">
                    <div class="text-white opacity-75 small mb-1">Total Nominal Perjaldin (Netto)</div>
                    <h3 class="fw-bold mb-0 text-white">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>
