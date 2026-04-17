{{-- partial: detail-info.blade.php --}}
{{-- Variables: $tagihan --}}
@php
    $bulanMap = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                 7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
@endphp
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-file-earmark-text text-primary me-2"></i>Informasi Dokumen
        </h6>
    </div>
    <div class="card-body py-4">
        <div class="row g-4">
            <div class="col-md-4">
                <label class="text-muted small d-block mb-1"><i class="bi bi-hash me-1"></i>Nomor Tagihan</label>
                <span class="fw-bold text-primary">{{ $tagihan->nomor_tagihan ?? '-' }}</span>
            </div>
            <div class="col-md-4">
                <label class="text-muted small d-block mb-1"><i class="bi bi-journal-text me-1"></i>Uraian / Judul Perjalanan</label>
                <span class="fw-bold">{{ $tagihan->deskripsi ?? '-' }}</span>
            </div>
            <div class="col-md-4">
                <label class="text-muted small d-block mb-1"><i class="bi bi-calendar-month me-1"></i>Periode</label>
                <span class="fw-bold">
                    {{ isset($tagihan->periode_bulan) ? ($bulanMap[$tagihan->periode_bulan] ?? $tagihan->periode_bulan) : '-' }}
                    {{ $tagihan->periode_tahun ?? '' }}
                </span>
            </div>
            <div class="col-md-4">
                <label class="text-muted small d-block mb-1"><i class="bi bi-geo-alt me-1"></i>Kota TTD</label>
                <span class="fw-bold">{{ $tagihan->kota_ttd ?? '-' }}</span>
            </div>
            <div class="col-md-4">
                <label class="text-muted small d-block mb-1"><i class="bi bi-calendar-date me-1"></i>Tanggal TTD</label>
                <span class="fw-bold">{{ isset($tagihan->tanggal_ttd) ? \Carbon\Carbon::parse($tagihan->tanggal_ttd)->format('d M Y') : '-' }}</span>
            </div>
            <div class="col-md-4">
                <label class="text-muted small d-block mb-1"><i class="bi bi-people me-1"></i>Jumlah Peserta</label>
                <span class="fw-bold">{{ $tagihan->detailPerjaldin->count() }} orang</span>
            </div>
            <div class="col-md-4">
                <label class="text-muted small d-block mb-1"><i class="bi bi-cash-stack me-1"></i>Total Bruto</label>
                <span class="fw-semibold fs-5 text-success">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>
