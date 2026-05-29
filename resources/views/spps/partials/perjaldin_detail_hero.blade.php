@php
    $statusMeta = [
        'DISETUJUI_PERJALDIN'   => ['cls' => 'hero-info',     'label' => 'Disetujui — Siap SPP',       'icon' => 'bi-patch-check-fill', 'desc' => 'Dokumen telah diverifikasi PPK. Setiap komponen biaya siap diproses menjadi SPP secara parsial.'],
        'PROSES_COA'            => ['cls' => 'hero-warning',  'label' => 'Proses Pemetaan COA',        'icon' => 'bi-diagram-3-fill',   'desc' => 'Lengkapi pemetaan COA untuk setiap komponen sebelum membuat SPP.'],
        'PROSES_SPP'            => ['cls' => 'hero-pending',  'label' => 'Proses Pembuatan SPP',       'icon' => 'bi-hourglass-split',  'desc' => 'Pembuatan SPP per komponen sedang berlangsung.'],
        'SEBAGIAN_SPP_TERBIT'   => ['cls' => 'hero-info',     'label' => 'Sebagian SPP Terbit',        'icon' => 'bi-layers-half',      'desc' => 'Sebagian komponen telah memiliki SPP. Lanjutkan untuk komponen lainnya.'],
        'SPP_LENGKAP'           => ['cls' => 'hero-approved', 'label' => 'Seluruh SPP Terbit',         'icon' => 'bi-check2-all',       'desc' => 'Seluruh komponen biaya telah memiliki SPP.'],
        'DIKEMBALIKAN'          => ['cls' => 'hero-rejected', 'label' => 'Dikembalikan untuk Revisi',  'icon' => 'bi-arrow-counterclockwise', 'desc' => 'Dokumen dikembalikan. Mohon lakukan perbaikan sesuai catatan revisi.'],
    ];
    $meta = $statusMeta[$tagihan->status] ?? ['cls' => 'hero-info', 'label' => str_replace('_', ' ', $tagihan->status), 'icon' => 'bi-info-circle-fill', 'desc' => 'Detail dokumen perjalanan dinas untuk pembuatan Multi-SPP.'];

    $komponensAktif = $tagihan->komponenPerjaldin->where('total_nominal', '>', 0);
    $totalKomponen  = $komponensAktif->count();
    $komponenSudahSpp = $komponensAktif->filter(fn ($k) => $k->hasDokumenTurunan())->count();
    $progress = $totalKomponen > 0 ? round(($komponenSudahSpp / $totalKomponen) * 100) : 0;

    $bulanList = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $periodeLabel = ($bulanList[(int) $tagihan->periode_bulan] ?? $tagihan->periode_bulan) . ' ' . $tagihan->periode_tahun;
@endphp

<div class="msp-hero {{ $meta['cls'] }}">
    <i class="bi bi-airplane-engines plane-illust"></i>
    <div class="row align-items-center g-4">
        <div class="col-lg-8">
            <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                <span class="hero-doc-badge"><i class="bi bi-receipt me-1"></i>{{ $tagihan->nomor_tagihan }}</span>
                <span class="hero-status-pill"><i class="bi {{ $meta['icon'] }}"></i>{{ $meta['label'] }}</span>
            </div>
            <h3 class="hero-title">{{ $tagihan->deskripsi }}</h3>
            <p class="hero-desc">{{ $meta['desc'] }}</p>

            <div class="hero-meta">
                <span class="meta-item"><i class="bi bi-calendar3"></i> Periode {{ $periodeLabel }}</span>
                <span class="meta-item"><i class="bi bi-people-fill"></i> {{ $tagihan->detailPerjaldin->count() }} Peserta</span>
                <span class="meta-item"><i class="bi bi-ui-radios-grid"></i> {{ $totalKomponen }} Komponen Biaya</span>
                <span class="meta-item"><i class="bi bi-calendar-check"></i> Disetujui {{ optional($tagihan->updated_at)->format('d M Y') }}</span>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="hero-total-card">
                <div class="htc-label">Total Nominal (Netto)</div>
                <div class="htc-value">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</div>
                <div class="htc-progress">
                    <div class="htc-progress-top">
                        <span>Progres SPP</span>
                        <span class="fw-bold">{{ $komponenSudahSpp }}/{{ $totalKomponen }} Komponen</span>
                    </div>
                    <div class="htc-bar"><span style="width: {{ $progress }}%"></span></div>
                </div>
            </div>
        </div>
    </div>
</div>
