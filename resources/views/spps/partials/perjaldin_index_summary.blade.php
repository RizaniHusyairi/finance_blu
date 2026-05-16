@php
    $siapBuatSpp = 0;
    $sedangProsesSpp = 0;
    $lengkap = 0;
    $totalNominal = 0;

    foreach($perjaldins as $p) {
        $k = $p->komponenPerjaldin;
        $k_aktif = $k->where('total_nominal', '>', 0);

        $jmlAktif = $k_aktif->count();
        $jmlSpp = $k_aktif->filter(fn($x) => $x->hasDokumenTurunan())->count();
        $jmlPpk = $k_aktif->filter(fn($x) => in_array($x->status_proses, ['PENDING_PPK','REVISI_PPK', 'DISETUJUI_SPP', 'LANJUT_SPM', 'SELESAI']))->count();

        if ($jmlAktif > 0) {
            if ($jmlSpp < $jmlAktif) {
                $siapBuatSpp++;
                $totalNominal += $k_aktif->filter(fn($x) => !$x->hasDokumenTurunan())->sum('total_nominal');
            } elseif ($jmlSpp == $jmlAktif) {
                if ($jmlPpk == $jmlAktif) {
                    $lengkap++;
                } else {
                    $sedangProsesSpp++;
                }
            }
        }
    }
@endphp

<div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4 mt-2">
    <div class="col">
        @include('spps.partials.stat-card', [
            'icon' => 'bi-file-earmark-plus-fill',
            'color' => 'primary',
            'category' => 'Tahap Draft',
            'value' => $siapBuatSpp,
            'description' => 'Siap dibuat draft SPP',
            'badge' => 'Ready',
            'badgeColor' => 'primary',
        ])
    </div>
    <div class="col">
        @include('spps.partials.stat-card', [
            'icon' => 'bi-arrow-repeat',
            'color' => 'info',
            'category' => 'Verifikasi',
            'value' => $sedangProsesSpp,
            'description' => 'Sedang berlangsung',
            'badge' => 'On Going',
            'badgeColor' => 'info',
        ])
    </div>
    <div class="col">
        @include('spps.partials.stat-card', [
            'icon' => 'bi-check2-all',
            'color' => 'success',
            'category' => 'Lengkap',
            'value' => $lengkap,
            'description' => 'Semua item selesai',
            'badge' => 'Done',
            'badgeColor' => 'success',
        ])
    </div>
    <div class="col">
        @include('spps.partials.stat-card', [
            'icon' => 'bi-cash-stack',
            'color' => 'dark',
            'category' => 'Antrean Nominal',
            'value' => 'Rp ' . number_format($totalNominal, 0, ',', '.'),
            'description' => 'Total nominal siap draft',
        ])
    </div>
</div>
