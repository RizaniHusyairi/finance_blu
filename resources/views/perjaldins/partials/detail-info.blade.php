{{-- partial: detail-info.blade.php --}}
{{-- Variables: $tagihan --}}
@php
    $bulanMap = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                 7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
@endphp

<div class="modern-card">
    <div class="mc-head mc-icon-info">
        <div class="mc-head-left">
            <div class="mc-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
            <div>
                <h6 class="mc-title">Informasi Dokumen</h6>
                <p class="mc-sub">Detail header dan periode tagihan perjalanan dinas</p>
            </div>
        </div>
    </div>
    <div class="mc-body">
        <div class="info-grid">
            <div class="info-cell ic-primary">
                <span class="ic-label"><i class="bi bi-hash"></i> Nomor Tagihan</span>
                <span class="ic-value is-mono">{{ $tagihan->nomor_tagihan ?? '-' }}</span>
            </div>
            <div class="info-cell ic-primary" style="grid-column: span 2;">
                <span class="ic-label"><i class="bi bi-journal-text"></i> Uraian / Judul Perjalanan</span>
                <span class="ic-value">{{ $tagihan->deskripsi ?? '-' }}</span>
            </div>
            <div class="info-cell ic-info">
                <span class="ic-label"><i class="bi bi-calendar-month"></i> Periode</span>
                <span class="ic-value">
                    {{ isset($tagihan->periode_bulan) ? ($bulanMap[$tagihan->periode_bulan] ?? $tagihan->periode_bulan) : '-' }}
                    {{ $tagihan->periode_tahun ?? '' }}
                </span>
            </div>
            <div class="info-cell ic-violet">
                <span class="ic-label"><i class="bi bi-geo-alt-fill"></i> Kota TTD</span>
                <span class="ic-value">{{ $tagihan->kota_ttd ?? '-' }}</span>
            </div>
            <div class="info-cell ic-warning">
                <span class="ic-label"><i class="bi bi-calendar-date-fill"></i> Tanggal TTD</span>
                <span class="ic-value">{{ isset($tagihan->tanggal_ttd) ? \Carbon\Carbon::parse($tagihan->tanggal_ttd)->translatedFormat('d M Y') : '-' }}</span>
            </div>
            <div class="info-cell ic-info">
                <span class="ic-label"><i class="bi bi-people-fill"></i> Jumlah Peserta</span>
                <span class="ic-value">{{ $tagihan->detailPerjaldin->count() }} orang</span>
            </div>
            <div class="info-cell ic-success">
                <span class="ic-label"><i class="bi bi-cash-stack"></i> Total Bruto</span>
                <span class="ic-value is-money">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>
