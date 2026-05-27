@props([
    'filters' => [],
    'filterOptions' => [],
    'showMonth' => false,
    'showTipePnbp' => true,
    'showMitra' => true,
    'showChannel' => false,
    'showStatusPerforma' => false,
    'extraNotes' => null,
    'exportRoute' => 'super-admin-jasa.laporan.export',
    'exportReport' => null,
])

@php
    $bulanLabel = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $routeReportMap = [
        'super-admin-jasa.laporan.rekap-tagihan' => 'rekap-tagihan',
        'super-admin-jasa.laporan.rekap-layanan' => 'rekap-layanan',
        'super-admin-jasa.laporan.rekap-terima-setor' => 'rekap-terima-setor',
        'super-admin-jasa.laporan.rekap-pembayaran' => 'rekap-pembayaran',
        'super-admin-jasa.laporan.rekap-piutang' => 'rekap-piutang',
        'super-admin-jasa.laporan.performa-mitra' => 'performa-mitra',
    ];
    $activeExportReport = $exportReport ?: ($routeReportMap[request()->route()?->getName()] ?? null);
    $exportPdfUrl = $activeExportReport
        ? route($exportRoute, array_merge(request()->query(), ['report' => $activeExportReport, 'format' => 'pdf']))
        : null;
    $exportExcelUrl = $activeExportReport
        ? route($exportRoute, array_merge(request()->query(), ['report' => $activeExportReport, 'format' => 'excel']))
        : null;
    $activeMonth = (int)($filters['month'] ?? 0);
    $currentRouteName = request()->route()?->getName();
    $monthQuery = \Illuminate\Support\Arr::except(request()->query(), ['page']);
    $allMonthQuery = \Illuminate\Support\Arr::except($monthQuery, ['month']);
    $canShowMonthlyMenu = $showMonth && $currentRouteName;
@endphp

<div class="card border-0 shadow-sm mb-4 overflow-hidden sa-report-filter">
    <div class="card-header bg-white border-0 p-3 pb-0">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary text-white" style="width:36px;height:36px;">
                    <i class="bi bi-funnel"></i>
                </span>
                <div>
                    <div class="fw-bold text-dark">Filter Laporan</div>
                    <div class="small text-muted">Saring periode, mitra, tipe PNBP, dan data pembayaran.</div>
                </div>
            </div>
            @if($activeExportReport)
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ $exportPdfUrl }}" class="btn btn-danger btn-sm fw-bold px-3">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </a>
                    <a href="{{ $exportExcelUrl }}" class="btn btn-success btn-sm fw-bold px-3">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel
                    </a>
                </div>
            @endif
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label fw-bold small text-uppercase text-muted">Tahun</label>
                <select name="year" class="form-select">
                    @foreach($filterOptions['tahuns'] ?? [now()->year] as $y)
                        <option value="{{ $y }}" @selected((int)($filters['year'] ?? 0) === (int)$y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            @if($showMonth)
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-uppercase text-muted">Bulan</label>
                    <select name="month" class="form-select">
                        <option value="">Semua Bulan</option>
                        @foreach($bulanLabel as $num => $name)
                            <option value="{{ $num }}" @selected((int)($filters['month'] ?? 0) === $num)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($showTipePnbp)
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-uppercase text-muted">Tipe PNBP</label>
                    <select name="tipe_pnbp" class="form-select">
                        <option value="">Semua Tipe</option>
                        @foreach($filterOptions['tipe_pnbps'] ?? [] as $t)
                            <option value="{{ $t }}" @selected(($filters['tipe_pnbp'] ?? '') === $t)>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($showMitra)
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-uppercase text-muted">Mitra Jasa</label>
                    <select name="mitra_jasa_id" class="form-select">
                        <option value="">Semua Mitra</option>
                        @foreach($filterOptions['mitras'] ?? [] as $m)
                            <option value="{{ $m->id }}" @selected((int)($filters['mitra_jasa_id'] ?? 0) === (int)$m->id)>{{ $m->nama_mitra }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($showChannel)
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-uppercase text-muted">Kanal Bayar</label>
                    <select name="payment_channel" class="form-select">
                        <option value="">Semua Kanal</option>
                        @foreach($filterOptions['channels'] ?? [] as $c)
                            <option value="{{ $c }}" @selected(($filters['payment_channel'] ?? '') === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($showStatusPerforma)
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-uppercase text-muted">Status Performa</label>
                    <select name="status_performa" class="form-select">
                        <option value="">Semua</option>
                        @foreach(['BARU'=>'Baru','LANCAR'=>'Lancar','CUKUP_LANCAR'=>'Cukup Lancar','PERLU_PERHATIAN'=>'Perlu Perhatian','MACET'=>'Macet'] as $k => $v)
                            <option value="{{ $k }}" @selected(($filters['status_performa'] ?? '') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-md-auto ms-auto d-flex flex-wrap gap-2">
                <button class="btn btn-primary fw-bold px-4"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ url()->current() }}" class="btn btn-light border fw-bold px-3">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </a>
            </div>
        </form>

        @if($canShowMonthlyMenu)
            <div class="border-top mt-3 pt-3">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2">
                    <div class="small fw-bold text-uppercase text-muted me-lg-2">Data Bulanan</div>
                    <div class="d-flex flex-wrap gap-2 sa-report-months">
                        <a href="{{ route($currentRouteName, $allMonthQuery) }}"
                           class="btn btn-sm {{ $activeMonth === 0 ? 'btn-primary' : 'btn-light border' }} fw-bold">
                            Semua
                        </a>
                        @foreach($bulanLabel as $num => $name)
                            <a href="{{ route($currentRouteName, array_merge($monthQuery, ['month' => $num])) }}"
                               class="btn btn-sm {{ $activeMonth === $num ? 'btn-primary' : 'btn-light border' }} fw-bold">
                                {{ $name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if($extraNotes)
            <div class="small text-muted mt-3"><i class="bi bi-info-circle me-1"></i>{{ $extraNotes }}</div>
        @endif
    </div>
</div>
