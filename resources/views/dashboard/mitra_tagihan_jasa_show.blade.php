@extends('layouts.app')
@section('title', 'Detail Tagihan Jasa')

@push('css')
    @include('dashboard.partials.mitra-ui')
<style>
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

    /* ============ Payment proof modal ============ */
    .mp-pay-modal .modal-dialog { max-width: 760px; }
    .mp-pay-modal .modal-content {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 26px 70px rgba(15, 23, 42, .28);
    }
    .mp-pay-head {
        position: relative;
        display: flex;
        gap: 1rem;
        align-items: center;
        padding: 1.55rem 1.9rem 1.25rem;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
    }
    .mp-pay-icon {
        width: 56px;
        height: 56px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.55rem;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        box-shadow: 0 14px 28px rgba(37, 99, 235, .30);
        flex-shrink: 0;
    }
    .mp-pay-title {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 900;
        color: #153e8a;
        letter-spacing: 0;
    }
    .mp-pay-subtitle {
        margin: .25rem 0 0;
        font-size: .82rem;
        font-weight: 600;
        color: #64748b;
    }
    .mp-pay-close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        border: 0;
        background: transparent;
        color: #64748b;
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .mp-pay-close:hover { background: #f1f5f9; color: #0f172a; }
    .mp-pay-body {
        padding: 1.45rem 1.9rem 1.55rem;
        background: #ffffff;
    }
    .mp-pay-label {
        margin-bottom: .45rem;
        font-size: .78rem;
        font-weight: 900;
        color: #153e8a;
    }
    .mp-pay-input-wrap { position: relative; }
    .mp-pay-input-wrap .form-control {
        min-height: 46px;
        border-color: #cbd5e1;
        border-radius: 8px;
        color: #334155;
        font-weight: 650;
        padding-right: 2.45rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
    }
    .mp-pay-input-wrap .form-control:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 4px rgba(96, 165, 250, .16);
    }
    .mp-pay-input-wrap .form-control[readonly] {
        background: #f8fafc;
        color: #1e293b;
    }
    .mp-pay-input-icon {
        position: absolute;
        right: .85rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
    }
    .mp-pay-help {
        margin-top: .45rem;
        font-size: .74rem;
        line-height: 1.35;
        font-weight: 600;
        color: #64748b;
    }
    .mp-pay-dropzone {
        min-height: 158px;
        border: 1.5px dashed #cbd5e1;
        border-radius: 12px;
        background: linear-gradient(180deg, #f8fbff, #ffffff);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        text-align: center;
    }
    .mp-pay-dropzone.is-dragover {
        border-color: #3b82f6;
        background: #eff6ff;
        box-shadow: inset 0 0 0 1px rgba(59, 130, 246, .18);
    }
    .mp-pay-drop-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 58px;
        height: 48px;
        margin-bottom: .7rem;
        color: #1d4ed8;
        font-size: 2.65rem;
    }
    .mp-pay-drop-title {
        margin-bottom: .15rem;
        font-size: .88rem;
        font-weight: 850;
        color: #1e293b;
    }
    .mp-pay-drop-sub {
        margin-bottom: .85rem;
        font-size: .78rem;
        font-weight: 650;
        color: #64748b;
    }
    .mp-pay-file-btn {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #fff;
        color: #153e8a;
        font-weight: 800;
        padding: .5rem 1rem;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .06);
    }
    .mp-pay-file-btn:hover { border-color: #60a5fa; color: #1d4ed8; }
    .mp-pay-file-name {
        margin-top: .65rem;
        font-size: .76rem;
        font-weight: 650;
        color: #475569;
    }
    .mp-pay-note {
        min-height: 70px;
        border-color: #cbd5e1;
        border-radius: 8px;
        font-weight: 600;
        padding-right: 2.45rem;
    }
    .mp-pay-footer {
        display: flex;
        justify-content: flex-end;
        gap: .85rem;
        padding: 1rem 1.9rem;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }
    .mp-pay-cancel {
        min-width: 104px;
        border-radius: 9px;
        background: #e2e8f0;
        border-color: #cbd5e1;
        color: #0f172a;
        font-weight: 850;
    }
    .mp-pay-submit {
        min-width: 230px;
        border: 0;
        border-radius: 10px;
        background: linear-gradient(135deg, #22c55e, #10b981);
        color: #fff;
        font-weight: 900;
        box-shadow: 0 14px 24px rgba(16, 185, 129, .28);
    }
    .mp-pay-submit:hover {
        color: #fff;
        background: linear-gradient(135deg, #16a34a, #059669);
    }
    @media (max-width: 575.98px) {
        .mp-pay-head, .mp-pay-body, .mp-pay-footer { padding-left: 1rem; padding-right: 1rem; }
        .mp-pay-footer { flex-direction: column-reverse; }
        .mp-pay-cancel, .mp-pay-submit { width: 100%; }
    }
</style>
@endpush

@section('content')
@php
    $statusClass = match($tagihan->status) {
        'LUNAS' => 'success',
        'PUBLISHED' => 'warning',
        default => 'secondary',
    };
    $dueLabel = match($tagihan->status_jatuh_tempo) {
        'LEWAT_JATUH_TEMPO' => ['Lewat Jatuh Tempo', 'bg-danger'],
        'JATUH_TEMPO_HARI_INI' => ['Jatuh Tempo Hari Ini', 'bg-dark'],
        'MENDEKATI_JATUH_TEMPO' => ['Mendekati Jatuh Tempo', 'bg-warning text-dark'],
        'LUNAS' => ['Lunas', 'bg-success'],
        default => ['Normal', 'bg-success'],
    };
@endphp

<div class="mp-hero mb-4">
    <div class="d-flex flex-column flex-xl-row align-items-start align-items-xl-center justify-content-between gap-3">
        <div class="d-flex align-items-start gap-3">
            <span class="mp-hero-icon"><i class="bi bi-receipt fs-4"></i></span>
            <div>
                <div class="small text-white-50 fw-bold text-uppercase mb-1">Detail Tagihan</div>
                <h4 class="mb-1 fw-bold text-white">Detail Tagihan Jasa</h4>
                <p class="mb-0 small fw-semibold text-white-50">No. Tagihan: {{ $tagihan->nomor_tagihan }}</p>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('mitra.tagihan-jasa.pdf', $tagihan->id) }}" target="_blank" class="btn btn-outline-light fw-bold">
                <i class="bi bi-file-pdf me-1"></i>Nota Tagihan
            </a>
            @if($tagihan->file_surat_pengantar_final)
                <a href="{{ route('mitra.tagihan-jasa.surat-final', $tagihan->id) }}" class="btn btn-outline-light fw-bold">
                    <i class="bi bi-download me-1"></i>Surat Pengantar
                </a>
            @endif
            <a href="{{ route('mitra.dashboard') }}" class="btn btn-light fw-bold">Kembali</a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white p-4 border-bottom rounded-top-4 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-receipt text-primary me-2"></i>Informasi Tagihan</h5>
                <span class="badge bg-{{ $statusClass }} px-3 py-2">{{ str_replace('_', ' ', $tagihan->status) }}</span>
            </div>
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="small text-muted fw-bold">Mitra</div>
                        <div class="fw-semibold">{{ $mitra->nama_mitra }}</div>
                        <div class="small text-muted">{{ $mitra->alamat ?: '-' }}</div>
                        <div class="small text-muted">NPWP: {{ $mitra->npwp ?: '-' }}</div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="small text-muted fw-bold">Tanggal Tagihan</div>
                        <div class="fw-semibold">{{ \Carbon\Carbon::parse($tagihan->tanggal_tagihan)->format('d F Y') }}</div>
                        <div class="small text-muted fw-bold mt-3">Dokumen Dasar</div>
                        <div class="fw-semibold">{{ $tagihan->nomor_kontrak ?: '-' }}</div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Rincian Layanan</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" width="5%">No</th>
                                <th>Deskripsi Layanan</th>
                                <th>Kode Akun</th>
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
                                    <td>{{ $detail->kode_akun ?: ($detail->layananJasa->kode_pembayaran_lengkap ?? $detail->layananJasa->kode_akun ?? '-') }}</td>
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
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5" class="text-end">Total Tagihan</th>
                                <th class="text-end text-success fs-5">Rp {{ number_format((float) $tagihan->total_tagihan, 0, ',', '.') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white fw-bold">Pembayaran</div>
            <div class="card-body">
                <div class="small text-muted fw-bold">Nomor Virtual Account</div>
                <div class="fs-4 fw-bold text-primary">{{ $tagihan->nomor_va ?: '-' }}</div>
                <div class="small text-muted mt-2">Bank BTN</div>
                <hr>
                <div class="small text-muted fw-bold">Tanggal Jatuh Tempo</div>
                <div class="fw-semibold">{{ $tagihan->tanggal_jatuh_tempo ? \Carbon\Carbon::parse($tagihan->tanggal_jatuh_tempo)->format('d F Y') : '-' }}</div>
                <div class="mt-2"><span class="badge {{ $dueLabel[1] }}">{{ $dueLabel[0] }}</span></div>
                @if($tagihan->status_jatuh_tempo === 'LEWAT_JATUH_TEMPO')
                    <div class="small text-danger mt-2">Terlambat {{ $tagihan->hari_terlambat }} hari.</div>
                @elseif($tagihan->status !== 'LUNAS')
                    <div class="small text-muted mt-2">Umur piutang {{ $tagihan->umur_piutang_hari }} hari.</div>
                @endif
                @if($tagihan->status === 'PUBLISHED')
                    <div class="alert alert-warning small mt-3 mb-0">
                        Tagihan masih menunggu pembayaran.
                    </div>
                @else
                    <div class="alert alert-success small mt-3 mb-0">
                        Tagihan sudah lunas.
                    </div>
                @endif
            </div>
        </div>

        @php
            $isLunas = $tagihan->status === 'LUNAS';
            $dueDate = $tagihan->tanggal_jatuh_tempo
                ? \Carbon\Carbon::parse($tagihan->tanggal_jatuh_tempo)
                : null;
            $displayDeadline = $dueDate ? $dueDate->copy()->startOfDay() : null;
            $graceDeadline   = $dueDate ? $dueDate->copy()->endOfDay() : null;
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
                    <span class="pc-percent">denda 2%</span> dari total tagihan.
                </p>
            </div>
        </div>
        @endunless

        @php
            $paymentProofs = $tagihan->paymentProofs ?? collect();
            $pendingProof = $paymentProofs->firstWhere('status', \App\Models\TagihanJasaPaymentProof::STATUS_MENUNGGU);
        @endphp
        @unless($isLunas)
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-upload text-primary me-2"></i>Upload Bukti Pembayaran</span>
                @if($pendingProof)
                    <span class="badge {{ $pendingProof->status_badge_class }}">{{ $pendingProof->status_label }}</span>
                @endif
            </div>
            <div class="card-body">
                @if($pendingProof)
                    <div class="alert alert-info small mb-0">
                        Bukti pembayaran Anda sedang menunggu verifikasi. Jika perlu perbaikan, catatan admin akan tampil di riwayat bukti.
                    </div>
                @else
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div>
                            <div class="fw-bold">Konfirmasi pembayaran VA manual</div>
                            <div class="small text-muted">Unggah bukti setelah pembayaran dilakukan. Nominal dikunci mengikuti total tagihan berjalan.</div>
                        </div>
                        <button type="button" class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalUploadBuktiPembayaran">
                            <i class="bi bi-upload me-1"></i>Upload Bukti
                        </button>
                    </div>
                @endif
            </div>
        </div>

        @unless($pendingProof)
        <div class="modal fade mp-pay-modal" id="modalUploadBuktiPembayaran" tabindex="-1" aria-labelledby="modalUploadBuktiPembayaranLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form action="{{ route('mitra.tagihan-jasa.bukti-pembayaran.store', $tagihan->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mp-pay-head">
                            <span class="mp-pay-icon"><i class="bi bi-cloud-arrow-up"></i></span>
                            <div>
                                <h5 class="mp-pay-title" id="modalUploadBuktiPembayaranLabel">Upload Bukti Pembayaran</h5>
                                <p class="mp-pay-subtitle">Unggah bukti pembayaran yang telah dilakukan</p>
                            </div>
                            <button type="button" class="mp-pay-close" data-bs-dismiss="modal" aria-label="Tutup">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <div class="mp-pay-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="mp-pay-label">Tanggal Bayar</label>
                                    <div class="mp-pay-input-wrap">
                                        <input type="date" name="tanggal_bayar" class="form-control @error('tanggal_bayar') is-invalid @enderror" value="{{ old('tanggal_bayar', now()->toDateString()) }}" required>
                                        <i class="bi bi-calendar3 mp-pay-input-icon"></i>
                                    </div>
                                    @error('tanggal_bayar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="mp-pay-label">Nominal Bayar</label>
                                    <div class="mp-pay-input-wrap">
                                        <input type="number" name="nominal_bayar" class="form-control" value="{{ (int) $tagihan->total_dengan_denda }}" min="1" step="1" readonly aria-readonly="true">
                                        <i class="bi bi-lock mp-pay-input-icon"></i>
                                    </div>
                                    <div class="mp-pay-help">Nominal mengikuti total tagihan berjalan, termasuk denda jika ada.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="mp-pay-label">Bank Pengirim</label>
                                    <div class="mp-pay-input-wrap">
                                        <input type="text" name="bank_pengirim" class="form-control @error('bank_pengirim') is-invalid @enderror" value="{{ old('bank_pengirim') }}" placeholder="Contoh: BTN / BRI / Mandiri">
                                        <i class="bi bi-bank mp-pay-input-icon"></i>
                                    </div>
                                    @error('bank_pengirim')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="mp-pay-label">Nomor Referensi</label>
                                    <div class="mp-pay-input-wrap">
                                        <input type="text" name="nomor_referensi" class="form-control @error('nomor_referensi') is-invalid @enderror" value="{{ old('nomor_referensi') }}" placeholder="Nomor transaksi dari bank">
                                        <i class="bi bi-hash mp-pay-input-icon"></i>
                                    </div>
                                    @error('nomor_referensi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="mp-pay-label">File Bukti Transfer</label>
                                    <div class="mp-pay-dropzone">
                                        <div>
                                            <div class="mp-pay-drop-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                                            <div class="mp-pay-drop-title">Drag & drop file di sini atau klik untuk memilih</div>
                                            <div class="mp-pay-drop-sub">PDF, JPG atau PNG (maks. 5MB)</div>
                                            <input type="file" name="file_bukti" id="fileBuktiPembayaran" class="visually-hidden @error('file_bukti') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png" required>
                                            <label for="fileBuktiPembayaran" class="mp-pay-file-btn">
                                                <i class="bi bi-paperclip"></i>Pilih File
                                            </label>
                                            <div class="mp-pay-file-name" id="fileBuktiPembayaranName">Belum ada file dipilih</div>
                                        </div>
                                    </div>
                                    @error('file_bukti')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="mp-pay-label">Catatan (Opsional)</label>
                                    <div class="mp-pay-input-wrap">
                                        <textarea name="catatan_mitra" rows="2" class="form-control mp-pay-note @error('catatan_mitra') is-invalid @enderror" placeholder="Tulis catatan tambahan jika diperlukan...">{{ old('catatan_mitra') }}</textarea>
                                        <i class="bi bi-file-earmark-text mp-pay-input-icon"></i>
                                    </div>
                                    @error('catatan_mitra')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="mp-pay-footer">
                            <button type="button" class="btn mp-pay-cancel" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn mp-pay-submit">
                                <i class="bi bi-send-check me-1"></i>Kirim Bukti Pembayaran
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endunless
        @endunless

        @if($paymentProofs->isNotEmpty())
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white fw-bold">Riwayat Bukti Pembayaran</div>
            <div class="list-group list-group-flush">
                @foreach($paymentProofs as $proof)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <div class="fw-bold">Rp {{ number_format($proof->nominal_bayar, 0, ',', '.') }}</div>
                                <div class="small text-muted">
                                    {{ optional($proof->tanggal_bayar)->translatedFormat('d F Y') }} · {{ $proof->bank_pengirim ?: 'Bank tidak diisi' }}
                                    @if($proof->nomor_referensi)
                                        · Ref: {{ $proof->nomor_referensi }}
                                    @endif
                                </div>
                                @if($proof->catatan_verifikator)
                                    <div class="small text-danger mt-1">{{ $proof->catatan_verifikator }}</div>
                                @endif
                            </div>
                            <div class="text-end">
                                <span class="badge {{ $proof->status_badge_class }}">{{ $proof->status_label }}</span>
                                <a href="{{ route('mitra.tagihan-jasa.bukti-pembayaran.download', [$tagihan->id, $proof->id]) }}" class="btn btn-sm btn-outline-secondary mt-2 d-block">
                                    <i class="bi bi-download me-1"></i>Bukti
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white fw-bold">Dokumen</div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('mitra.tagihan-jasa.pdf', ['id' => $tagihan->id, 'download' => 1]) }}" class="btn btn-danger fw-bold">
                    <i class="bi bi-download me-1"></i> Download Nota Tagihan
                </a>
                @if($tagihan->file_surat_pengantar_final)
                    <a href="{{ route('mitra.tagihan-jasa.surat-final', $tagihan->id) }}" class="btn btn-primary fw-bold">
                        <i class="bi bi-download me-1"></i> Download Surat Pengantar TTD
                    </a>
                @else
                    <div class="alert alert-light border small mb-0">
                        Surat pengantar final belum tersedia.
                    </div>
                @endif
                @if($tagihan->kontrakMitraJasa?->file_kontrak)
                    <a href="{{ route('mitra.kontrak-jasa.download', $tagihan->kontrakMitraJasa) }}" class="btn btn-outline-secondary fw-bold">
                        <i class="bi bi-download me-1"></i> Download Dokumen Dasar
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
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

            let target;
            if (isLate)        target = graceDeadline;
            else if (inGrace)  target = graceDeadline;
            else               target = displayDeadline;

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

    (function () {
        const input = document.getElementById('fileBuktiPembayaran');
        const nameEl = document.getElementById('fileBuktiPembayaranName');
        const dropzone = input ? input.closest('.mp-pay-dropzone') : null;
        if (!input || !nameEl || !dropzone) return;

        const setFileName = function () {
            nameEl.textContent = input.files && input.files.length
                ? input.files[0].name
                : 'Belum ada file dipilih';
        };

        input.addEventListener('change', setFileName);

        ['dragenter', 'dragover'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone.addEventListener('drop', function (event) {
            if (event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files.length) {
                input.files = event.dataTransfer.files;
                setFileName();
            }
        });
    })();
</script>
@if($errors->hasAny(['tanggal_bayar', 'bank_pengirim', 'nomor_referensi', 'catatan_mitra', 'file_bukti']))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('modalUploadBuktiPembayaran');
        if (modalEl && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    });
</script>
@endif
@endpush
