<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tagihan {{ $tagihan->nomor_tagihan }} &middot; SIKEREN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(180deg, #f1f5f9 0%, #fff 50%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .page-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%);
            color: #fff;
            border-radius: 0 0 1.4rem 1.4rem;
            box-shadow: 0 12px 28px rgba(79, 70, 229, .25);
        }
        .page-header .badge-public {
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255, 255, 255, .30);
            color: #fff;
            font-weight: 600;
            padding: .35rem .85rem;
            border-radius: 999px;
            font-size: .72rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            backdrop-filter: blur(8px);
        }
        .va-card {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 12px 28px rgba(79, 70, 229, .25);
            position: relative;
            overflow: hidden;
        }
        .va-card::after {
            content: '';
            position: absolute;
            right: -60px; top: -60px;
            width: 180px; height: 180px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .10);
        }
        .va-card .va-label {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            opacity: .85;
        }
        .va-card .va-number {
            font-size: 1.65rem;
            font-weight: 800;
            font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
            letter-spacing: .04em;
            margin-top: .25rem;
        }
        .va-card .va-bank {
            font-size: .85rem;
            font-weight: 600;
            opacity: .9;
            margin-top: .35rem;
        }
        .info-card {
            background: #fff;
            border: 1px solid #eef0f4;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .04);
        }
        .info-card .ic-head {
            padding: .85rem 1.25rem;
            border-bottom: 1px solid #f1f3f7;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .info-card .ic-head i {
            color: #4f46e5;
        }
        .info-card .ic-body { padding: 1.15rem 1.25rem; }

        .label-soft {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #94a3b8;
        }
        .value-strong {
            font-weight: 600;
            color: #0f172a;
            font-size: .92rem;
        }
        .table-rincian thead th {
            background: #f8fafc;
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
            border-bottom: 1px solid #eef0f4;
            white-space: nowrap;
        }
        .table-rincian tbody td {
            border-bottom: 1px solid #f1f3f7;
            font-size: .88rem;
            vertical-align: middle;
        }
        .total-row {
            background: linear-gradient(135deg, rgba(16, 185, 129, .08), rgba(16, 185, 129, .02));
            font-weight: 800;
        }
        .total-row td {
            border-top: 2px solid rgba(16, 185, 129, .25) !important;
            color: #0f172a;
        }
        .total-row .total-amount {
            color: #047857;
            font-size: 1.15rem;
            font-variant-numeric: tabular-nums;
        }
        .due-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .72rem;
            font-weight: 700;
            padding: .35rem .75rem;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .due-normal { background: rgba(16, 185, 129, .12); color: #047857; }
        .due-warn   { background: rgba(245, 158, 11, .14); color: #b45309; }
        .due-late   { background: rgba(244, 63, 94, .12); color: #b91c1c; }
        .due-paid   { background: rgba(99, 102, 241, .12); color: #4338ca; }
        .btn-pdf {
            background: linear-gradient(135deg, #f43f5e, #ec4899);
            color: #fff;
            font-weight: 700;
            padding: .65rem 1.2rem;
            border-radius: .65rem;
            border: 0;
            line-height: 1.25;
            text-align: center;
            box-shadow: 0 8px 20px rgba(244, 63, 94, .30);
            transition: all .2s ease;
        }
        .btn-pdf:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(244, 63, 94, .40);
        }
        .btn-secondary-action {
            background: #fff;
            color: #4338ca;
            border: 1px solid #c7d2fe;
            font-weight: 700;
            padding: .65rem 1.2rem;
            border-radius: .65rem;
            line-height: 1.25;
            text-align: center;
            transition: all .2s ease;
        }
        .btn-secondary-action:hover {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: #fff;
            border-color: transparent;
        }
        .footer-note {
            font-size: .8rem;
            color: #94a3b8;
            text-align: center;
            margin-top: 2rem;
            padding: 1rem 0;
        }

        /* ============ Countdown ============ */
        .countdown-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid rgba(245, 158, 11, .35);
            border-radius: 1rem;
            padding: 1.1rem 1.25rem;
            box-shadow: 0 8px 22px rgba(245, 158, 11, .18);
            position: relative;
            overflow: hidden;
        }
        .countdown-card.is-late {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-color: rgba(244, 63, 94, .35);
            box-shadow: 0 8px 22px rgba(244, 63, 94, .20);
        }
        .countdown-card.is-paid {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-color: rgba(16, 185, 129, .35);
            box-shadow: 0 8px 22px rgba(16, 185, 129, .20);
        }
        .countdown-head {
            display: flex;
            align-items: center;
            gap: .55rem;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #92400e;
            margin-bottom: .85rem;
        }
        .countdown-card.is-late .countdown-head { color: #991b1b; }
        .countdown-card.is-paid .countdown-head { color: #065f46; }
        .countdown-head .pulse-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #f59e0b;
            box-shadow: 0 0 0 0 rgba(245, 158, 11, .55);
            animation: pulseDot 1.6s ease-in-out infinite;
        }
        .countdown-card.is-late .pulse-dot {
            background: #f43f5e;
            box-shadow: 0 0 0 0 rgba(244, 63, 94, .55);
        }
        .countdown-card.is-paid .pulse-dot {
            background: #10b981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, .55);
        }
        @keyframes pulseDot {
            0%   { box-shadow: 0 0 0 0 rgba(245, 158, 11, .55); }
            70%  { box-shadow: 0 0 0 8px rgba(245, 158, 11, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }
        .countdown-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: .5rem;
        }
        .countdown-cell {
            background: #ffffff;
            border: 1px solid rgba(245, 158, 11, .25);
            border-radius: .75rem;
            padding: .65rem .35rem .55rem;
            text-align: center;
            box-shadow: 0 4px 10px rgba(245, 158, 11, .10);
        }
        .countdown-card.is-late .countdown-cell {
            border-color: rgba(244, 63, 94, .25);
            box-shadow: 0 4px 10px rgba(244, 63, 94, .12);
        }
        .countdown-card.is-paid .countdown-cell {
            border-color: rgba(16, 185, 129, .25);
            box-shadow: 0 4px 10px rgba(16, 185, 129, .12);
        }
        .countdown-num {
            font-size: 1.55rem;
            font-weight: 800;
            color: #b45309;
            font-variant-numeric: tabular-nums;
            line-height: 1;
            font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
            letter-spacing: -.01em;
        }
        .countdown-card.is-late .countdown-num { color: #b91c1c; }
        .countdown-card.is-paid .countdown-num { color: #047857; }
        .countdown-label {
            margin-top: .35rem;
            font-size: .62rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #92400e;
        }
        .countdown-card.is-late .countdown-label { color: #991b1b; }
        .countdown-card.is-paid .countdown-label { color: #065f46; }
        .countdown-foot {
            margin-top: .85rem;
            font-size: .76rem;
            color: #92400e;
            text-align: center;
            font-weight: 600;
        }
        .countdown-card.is-late .countdown-foot { color: #991b1b; }
        .countdown-card.is-paid .countdown-foot { color: #065f46; }

        /* ============ Late penalty warning ============ */
        .penalty-card {
            display: flex;
            align-items: flex-start;
            gap: .85rem;
            background: #ffffff;
            border: 1px solid rgba(244, 63, 94, .22);
            border-left: 4px solid #f43f5e;
            border-radius: 1rem;
            padding: 1rem 1.15rem;
            box-shadow: 0 6px 16px rgba(244, 63, 94, .08);
        }
        .penalty-card .pc-icon {
            width: 38px; height: 38px;
            border-radius: 12px;
            background: linear-gradient(135deg, #fb7185, #f43f5e);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
            flex-shrink: 0;
            box-shadow: 0 6px 14px rgba(244, 63, 94, .25);
        }
        .penalty-card .pc-title {
            font-weight: 800;
            color: #991b1b;
            font-size: .82rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin: 0 0 .35rem;
        }
        .penalty-card .pc-text {
            font-size: .82rem;
            color: #475569;
            line-height: 1.55;
            margin: 0;
        }
        .penalty-card .pc-text strong { color: #b91c1c; }
        .penalty-card .pc-text .pc-percent {
            display: inline-block;
            background: linear-gradient(135deg, #f43f5e, #e11d48);
            color: #fff;
            font-weight: 800;
            padding: .1rem .55rem;
            border-radius: .35rem;
            font-size: .78rem;
            margin: 0 .15rem;
            box-shadow: 0 2px 6px rgba(244, 63, 94, .25);
        }
    </style>
    <link rel="icon" href="{{ asset('logo/minilogo-sikeren.png') }}" type="image/png">
</head>
<body>

@php
    $statusClass = match($tagihan->status) {
        'LUNAS' => 'success',
        'PUBLISHED' => 'warning',
        default => 'secondary',
    };
    $dueLabel = match($tagihan->status_jatuh_tempo ?? null) {
        'LEWAT_JATUH_TEMPO' => ['Lewat Jatuh Tempo', 'due-late'],
        'JATUH_TEMPO_HARI_INI' => ['Jatuh Tempo Hari Ini', 'due-late'],
        'MENDEKATI_JATUH_TEMPO' => ['Mendekati Jatuh Tempo', 'due-warn'],
        'LUNAS' => ['Lunas', 'due-paid'],
        default => ['Normal', 'due-normal'],
    };
@endphp

<div class="page-header py-4 mb-4">
    <div class="container py-2">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-white bg-opacity-25 rounded-circle d-inline-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                    <i class="bi bi-receipt-cutoff text-white fs-3"></i>
                </div>
                <div>
                    <span class="badge-public mb-2 d-inline-flex"><i class="bi bi-link-45deg me-1"></i>Tautan Publik</span>
                    <h4 class="m-0 text-white fw-bold">Tagihan PNBP</h4>
                    <div class="small text-white-50 mt-1">No. Tagihan: <strong class="text-white">{{ $tagihan->nomor_tagihan }}</strong></div>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ url()->signedRoute('public.tagihan-jasa.pdf', ['id' => $tagihan->id]) }}" target="_blank" class="btn-pdf">
                    <i class="bi bi-file-pdf me-1"></i> Lihat Surat Pengantar dan Nota Tagihan
                </a>
                <a href="{{ url()->signedRoute('public.tagihan-jasa.pdf', ['id' => $tagihan->id, 'download' => 1]) }}" class="btn-secondary-action">
                    <i class="bi bi-download me-1"></i> Unduh Surat Pengantar dan Nota Tagihan
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="info-card mb-4">
                <div class="ic-head d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div><i class="bi bi-info-circle-fill"></i>Informasi Tagihan</div>
                    <span class="badge bg-{{ $statusClass }}">{{ str_replace('_', ' ', $tagihan->status) }}</span>
                </div>
                <div class="ic-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="label-soft mb-1">Mitra</div>
                            <div class="value-strong">{{ $mitra->nama_mitra ?? $tagihan->mitra->nama_mitra ?? '-' }}</div>
                            <div class="small text-muted mt-1">{{ $mitra->alamat ?? $tagihan->mitra->alamat ?? '-' }}</div>
                            <div class="small text-muted">NPWP: {{ $mitra->npwp ?? $tagihan->mitra->npwp ?? '-' }}</div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="label-soft mb-1">Tanggal Tagihan</div>
                            <div class="value-strong">{{ \Carbon\Carbon::parse($tagihan->tanggal_tagihan)->translatedFormat('d F Y') }}</div>
                            <div class="label-soft mt-3 mb-1">Dokumen Dasar</div>
                            <div class="value-strong">{{ $tagihan->nomor_kontrak ?: '-' }}</div>
                        </div>
                    </div>

                    <div class="label-soft mb-2">Rincian Layanan</div>
                    <div class="table-responsive">
                        <table class="table table-rincian align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" width="5%">No</th>
                                    <th>Deskripsi Layanan</th>
                                    <th class="text-center">Volume</th>
                                    <th class="text-end">Tarif</th>
                                    <th class="text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tagihan->details as $detail)
                                    @php
                                        $layanan = $detail->layananJasa;
                                        $expectedPercentageSubtotal = ((float) $detail->qty * (float) $detail->harga_satuan / 100) * (float) ($detail->kurs ?? 1);
                                        $isPercentageDetail = ($layanan?->tipe_layanan === 'KONSESI')
                                            || str_contains((string) ($layanan?->satuan), '%')
                                            || ((bool) ($layanan?->mendukung_konsesi) && abs($expectedPercentageSubtotal - (float) $detail->subtotal) < 0.01);
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $detail->layananJasa->nama_lengkap ?? $detail->layananJasa->nama_layanan ?? '-' }}</div>
                                            @if($detail->layananJasa?->satuan)
                                                <div class="small text-muted">Satuan: {{ $detail->layananJasa->satuan }}</div>
                                            @endif
                                            @if($detail->keterangan)
                                                <div class="small text-muted">Keterangan: {{ $detail->keterangan }}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ rtrim(rtrim(number_format($detail->qty, 2, ',', '.'), '0'), ',') }}</td>
                                        <td class="text-end">
                                            @if($isPercentageDetail)
                                                {{ rtrim(rtrim(number_format((float) $detail->harga_satuan, 4, ',', '.'), '0'), ',') }}%
                                            @else
                                                Rp {{ number_format((float) $detail->harga_satuan, 0, ',', '.') }}
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold">Rp {{ number_format((float) $detail->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                                <tr class="total-row">
                                    <td colspan="4" class="text-end">Total Tagihan</td>
                                    <td class="text-end total-amount">Rp {{ number_format((float) $tagihan->total_tagihan, 0, ',', '.') }}</td>
                                </tr>
                                @if($tagihan->nominal_denda_keterlambatan > 0)
                                    <tr>
                                        <td colspan="4" class="text-end text-danger fw-bold">Denda 2% x {{ $tagihan->hari_terlambat }} hari</td>
                                        <td class="text-end text-danger fw-bold">Rp {{ number_format($tagihan->nominal_denda_keterlambatan, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="total-row">
                                        <td colspan="4" class="text-end">Total Harus Dibayar</td>
                                        <td class="text-end total-amount">Rp {{ number_format($tagihan->total_dengan_denda, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="info-card mb-4">
                <div class="ic-head"><i class="bi bi-cloud-download"></i>Dokumen</div>
                <div class="ic-body">
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <a href="{{ url()->signedRoute('public.tagihan-jasa.pdf', ['id' => $tagihan->id]) }}" target="_blank" class="btn-pdf w-100 d-inline-flex align-items-center justify-content-center">
                                <i class="bi bi-file-pdf me-1"></i> Lihat Surat Pengantar dan Nota Tagihan
                            </a>
                        </div>
                        <div class="col-sm-6">
                            <a href="{{ url()->signedRoute('public.tagihan-jasa.pdf', ['id' => $tagihan->id, 'download' => 1]) }}" class="btn-secondary-action w-100 d-inline-flex align-items-center justify-content-center">
                                <i class="bi bi-download me-1"></i> Unduh Surat Pengantar dan Nota Tagihan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="va-card mb-4">
                <div class="va-label"><i class="bi bi-bank2 me-1"></i>Virtual Account</div>
                <div class="va-number">{{ $tagihan->nomor_va ?: '-' }}</div>
                <div class="va-bank">Bank BTN</div>
            </div>

            @php
                $isLunas = $tagihan->status === 'LUNAS';
                $dueDate = $tagihan->tanggal_jatuh_tempo
                    ? \Carbon\Carbon::parse($tagihan->tanggal_jatuh_tempo)
                    : null;
                // Display deadline: awal hari jatuh tempo (biar countdown menampilkan jumlah hari penuh
                // tersisa, bukan termasuk fraksional hari ini).
                $displayDeadline = $dueDate ? $dueDate->copy()->startOfDay() : null;
                // Grace deadline: akhir hari jatuh tempo (user masih on-time selama hari itu).
                $graceDeadline = $dueDate ? $dueDate->copy()->endOfDay() : null;
                $isLate = $graceDeadline && now()->greaterThan($graceDeadline) && ! $isLunas;
                $countdownClass = $isLunas ? 'is-paid' : ($isLate ? 'is-late' : '');
                $countdownLabel = $isLunas ? 'Pembayaran Lunas' : ($isLate ? 'Tagihan Lewat Jatuh Tempo' : 'Sisa Waktu Pembayaran');
            @endphp

            @if($displayDeadline)
            <div class="countdown-card {{ $countdownClass }} mb-3" id="countdownCard"
                 data-deadline="{{ $displayDeadline->toIso8601String() }}"
                 data-grace-deadline="{{ $graceDeadline->toIso8601String() }}"
                 data-is-paid="{{ $isLunas ? '1' : '0' }}">
                <div class="countdown-head">
                    <span class="pulse-dot"></span>
                    <i class="bi {{ $isLunas ? 'bi-check-circle-fill' : ($isLate ? 'bi-exclamation-triangle-fill' : 'bi-stopwatch-fill') }}"></i>
                    <span id="countdownLabel">{{ $countdownLabel }}</span>
                </div>
                <div class="countdown-grid">
                    <div class="countdown-cell"><div class="countdown-num" id="cdDays">00</div><div class="countdown-label">Hari</div></div>
                    <div class="countdown-cell"><div class="countdown-num" id="cdHours">00</div><div class="countdown-label">Jam</div></div>
                    <div class="countdown-cell"><div class="countdown-num" id="cdMinutes">00</div><div class="countdown-label">Menit</div></div>
                    <div class="countdown-cell"><div class="countdown-num" id="cdSeconds">00</div><div class="countdown-label">Detik</div></div>
                </div>
                <div class="countdown-foot">
                    <i class="bi bi-calendar-event me-1"></i>
                    Jatuh tempo: <strong>{{ $dueDate->translatedFormat('d F Y') }}</strong>
                </div>
            </div>
            @endif

            @unless($isLunas)
            <div class="penalty-card mb-4">
                <span class="pc-icon"><i class="bi bi-exclamation-lg"></i></span>
                <div>
                    <div class="pc-title"><i class="bi bi-info-circle me-1"></i>Informasi Denda Keterlambatan</div>
                    <p class="pc-text">
                        Jatuh tempo tagihan adalah <strong>30 (tiga puluh) hari</strong> sesuai dengan nota tagihan,
                        sehingga apabila pada tanggal tersebut tagihan belum dibayar maka akan dikenakan
                        <span class="pc-percent">denda 2% per hari</span> dari total tagihan.
                    </p>
                    @if($tagihan->nominal_denda_keterlambatan > 0)
                        <div class="alert alert-danger small mt-3 mb-0">
                            Terlambat {{ $tagihan->hari_terlambat }} hari:
                            denda Rp {{ number_format($tagihan->nominal_denda_keterlambatan, 0, ',', '.') }}.
                            Total harus dibayar Rp {{ number_format($tagihan->total_dengan_denda, 0, ',', '.') }}.
                        </div>
                    @endif
                </div>
            </div>
            @endunless

            <div class="info-card mb-4">
                <div class="ic-head"><i class="bi bi-calendar2-event"></i>Jatuh Tempo</div>
                <div class="ic-body">
                    <div class="value-strong">
                        {{ $tagihan->tanggal_jatuh_tempo ? \Carbon\Carbon::parse($tagihan->tanggal_jatuh_tempo)->translatedFormat('d F Y') : '-' }}
                    </div>
                    <span class="due-pill {{ $dueLabel[1] }} mt-2">{{ $dueLabel[0] }}</span>
                    @if(($tagihan->status_jatuh_tempo ?? null) === 'LEWAT_JATUH_TEMPO')
                        <div class="small text-danger mt-2">Terlambat {{ $tagihan->hari_terlambat }} hari.</div>
                    @elseif($tagihan->status !== 'LUNAS')
                        <div class="small text-muted mt-2">Umur piutang {{ $tagihan->umur_piutang_hari }} hari.</div>
                    @endif
                    @if($tagihan->status === 'PUBLISHED')
                        <div class="alert alert-warning small mt-3 mb-0">
                            <i class="bi bi-hourglass-split me-1"></i>Tagihan masih menunggu pembayaran.
                        </div>
                    @elseif($tagihan->status === 'LUNAS')
                        <div class="alert alert-success small mt-3 mb-0">
                            <i class="bi bi-check-circle-fill me-1"></i>Tagihan sudah lunas.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="footer-note">
        <i class="bi bi-shield-lock me-1"></i>
        Halaman ini hanya dapat diakses melalui tautan resmi yang ditandatangani secara digital.
        <br>
        &copy; {{ date('Y') }} SIKEREN — Sistem Informasi Keuangan
    </div>
</div>

<script>
    (function () {
        const card = document.getElementById('countdownCard');
        if (!card) return;

        const isPaid = card.dataset.isPaid === '1';
        const displayDeadline = new Date(card.dataset.deadline);
        const graceDeadline = new Date(card.dataset.graceDeadline);
        const elDays = document.getElementById('cdDays');
        const elHours = document.getElementById('cdHours');
        const elMinutes = document.getElementById('cdMinutes');
        const elSeconds = document.getElementById('cdSeconds');
        const elLabel = document.getElementById('countdownLabel');

        const pad = (n) => String(Math.max(0, n)).padStart(2, '0');

        function render() {
            if (isPaid) {
                elDays.textContent = '00';
                elHours.textContent = '00';
                elMinutes.textContent = '00';
                elSeconds.textContent = '00';
                elLabel.textContent = 'Pembayaran Lunas';
                return;
            }

            const now = new Date();
            const isLate = now > graceDeadline;
            const inGrace = ! isLate && now >= displayDeadline;

            // Hitung sisa waktu menuju AWAL hari jatuh tempo. Jika sudah memasuki hari
            // jatuh tempo (grace period), countdown terkunci di 00:HH:MM:SS sampai akhir hari.
            // Jika sudah lewat jatuh tempo, countdown menampilkan durasi keterlambatan
            // dari akhir hari jatuh tempo (grace deadline).
            let target;
            if (isLate) {
                target = graceDeadline;
            } else if (inGrace) {
                target = graceDeadline; // tampilkan sisa jam menuju midnight hari ini
            } else {
                target = displayDeadline;
            }

            let diff = Math.floor((target - now) / 1000);
            diff = Math.abs(diff);

            const days    = Math.floor(diff / 86400);
            const hours   = Math.floor((diff % 86400) / 3600);
            const minutes = Math.floor((diff % 3600) / 60);
            const seconds = diff % 60;

            elDays.textContent = pad(inGrace ? 0 : days);
            elHours.textContent = pad(hours);
            elMinutes.textContent = pad(minutes);
            elSeconds.textContent = pad(seconds);

            if (isLate) {
                if (! card.classList.contains('is-late')) {
                    card.classList.remove('is-paid');
                    card.classList.add('is-late');
                }
                elLabel.textContent = 'Tagihan Lewat Jatuh Tempo';
            } else if (inGrace) {
                if (card.classList.contains('is-late')) card.classList.remove('is-late');
                elLabel.textContent = 'Hari Terakhir Pembayaran';
            } else {
                if (card.classList.contains('is-late')) card.classList.remove('is-late');
                elLabel.textContent = 'Sisa Waktu Pembayaran';
            }
        }

        render();
        if (! isPaid) setInterval(render, 1000);
    })();
</script>

</body>
</html>
