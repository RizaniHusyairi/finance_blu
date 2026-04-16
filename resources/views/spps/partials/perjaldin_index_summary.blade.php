@php
    $siapInputCoa = 0;
    $siapBuatSpp = 0;
    $sedangProsesSpp = 0;
    $lengkap = 0;
    $totalNominal = 0;

    foreach($perjaldins as $p) {
        $k = $p->komponenPerjaldin;
        $k_aktif = $k->where('total_nominal', '>', 0);
        
        $jmlAktif = $k_aktif->count();
        $jmlCoa = $k_aktif->whereNotNull('dipa_revision_item_id')->count();
        $jmlSpp = $k_aktif->filter(fn($x) => $x->hasDokumenTurunan())->count();
        $jmlPpk = $k_aktif->filter(fn($x) => in_array($x->status_proses, ['PENDING_PPK','REVISI_PPK', 'DISETUJUI_SPP', 'LANJUT_SPM', 'SELESAI']))->count();

        if ($jmlAktif > 0) {
            if ($jmlCoa < $jmlAktif) {
                $siapInputCoa++;
            } elseif ($jmlCoa == $jmlAktif && $jmlSpp < $jmlAktif) {
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

<div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-3 mb-4 mt-2">
    <div class="col">
        <div class="card h-100 border-0 shadow-sm bg-warning text-dark">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h6 class="card-title fw-normal mb-0">Siap Input COA</h6>
                    <i class="bi bi-tag opacity-50 fs-5"></i>
                </div>
                <h3 class="fw-bold mb-0 text-dark">{{ $siapInputCoa }}</h3>
                <small class="opacity-75">Perlu dipilih COA</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 border-0 shadow-sm bg-primary text-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h6 class="card-title fw-normal mb-0">Siap Buat SPP</h6>
                    <i class="bi bi-file-earmark-plus opacity-50 fs-5"></i>
                </div>
                <h3 class="fw-bold mb-0 text-white">{{ $siapBuatSpp }}</h3>
                <small class="opacity-75">Draft SPP belum dicetak</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 border-0 shadow-sm bg-info text-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h6 class="card-title fw-normal mb-0">Sedang Proses</h6>
                    <i class="bi bi-arrow-repeat opacity-50 fs-5"></i>
                </div>
                <h3 class="fw-bold mb-0 text-white">{{ $sedangProsesSpp }}</h3>
                <small class="opacity-75">Dalam verifikasi</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 border-0 shadow-sm bg-success text-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h6 class="card-title fw-normal mb-0">SPP Lengkap</h6>
                    <i class="bi bi-check2-all opacity-50 fs-5"></i>
                </div>
                <h3 class="fw-bold mb-0 text-white">{{ $lengkap }}</h3>
                <small class="opacity-75">Semua item selesai</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 border-0 shadow-sm bg-light text-dark">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h6 class="card-title fw-normal mb-0">Antrean Nominal</h6>
                    <i class="bi bi-cash opacity-50 fs-5"></i>
                </div>
                <h4 class="fw-bold mb-0 text-dark">Rp {{ number_format($totalNominal, 0, ',', '.') }}</h4>
                <small class="text-muted">Siap dibuat draft</small>
            </div>
        </div>
    </div>
</div>
