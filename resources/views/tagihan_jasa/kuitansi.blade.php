<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kuitansi - {{ $tagihan->nomor_tagihan }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 13px; color: #222; line-height: 1.6; }
        .header { text-align: center; border-bottom: 3px double #333; padding-bottom: 10px; margin-bottom: 22px; }
        .header h1 { margin: 0; font-size: 26px; letter-spacing: 6px; }
        .header .sub { font-size: 12px; color: #555; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 2px 0; font-size: 12px; }
        .rowline td { padding: 7px 4px; vertical-align: top; }
        .label { width: 26%; font-weight: bold; }
        .sep { width: 2%; }
        .val { width: 72%; border-bottom: 1px dotted #999; }
        .amount-box { border: 2px solid #0d6efd; border-radius: 6px; padding: 8px 18px; font-size: 20px; font-weight: bold; color: #0d6efd; }
        .terbilang { background: #f3f6fb; border-left: 4px solid #0d6efd; padding: 9px 12px; font-style: italic; }
        .lunas-stamp { color: #16a34a; border: 3px solid #16a34a; border-radius: 8px; padding: 4px 16px; font-weight: bold; letter-spacing: 3px; }
        .foot { margin-top: 28px; font-size: 10px; color: #777; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>
@php
    $mitraTagihan = $tagihan->mitra ?? $tagihan->mitraLegacy;
    $proof = $tagihan->latestPaymentProof ?? null;
    $tglLunas = $tagihan->tanggal_lunas ?: $tagihan->paid_at;
@endphp
    <div class="header">
        <h1>KUITANSI</h1>
        <div class="sub">Bukti Pembayaran PNBP &mdash; Layanan Jasa Kebandarudaraan</div>
    </div>

    <table class="meta">
        <tr>
            <td><strong>No. Kuitansi</strong> : {{ $tagihan->kode_verifikasi_digital }}</td>
            <td style="text-align: right;"><strong>No. Tagihan</strong> : {{ $tagihan->nomor_tagihan }}</td>
        </tr>
    </table>

    <table class="rowline" style="margin-top: 10px;">
        <tr>
            <td class="label">Telah terima dari</td><td class="sep">:</td>
            <td class="val">{{ $mitraTagihan->nama_pihak ?? $mitraTagihan->nama_mitra ?? '-' }}@if($mitraTagihan?->npwp) &middot; NPWP {{ $mitraTagihan->npwp }}@endif</td>
        </tr>
        <tr>
            <td class="label">Uang sejumlah</td><td class="sep">:</td>
            <td style="width: 72%;"><div class="terbilang">{{ $terbilang }}</div></td>
        </tr>
        <tr>
            <td class="label">Untuk pembayaran</td><td class="sep">:</td>
            <td class="val">Tagihan PNBP jasa No. {{ $tagihan->nomor_tagihan }}@if($tagihan->nomor_kontrak) (dasar: {{ $tagihan->nomor_kontrak }})@endif</td>
        </tr>
        <tr>
            <td class="label">Metode bayar</td><td class="sep">:</td>
            <td class="val">{{ $tagihan->nomor_va ? 'Virtual Account BTN ' . $tagihan->nomor_va : 'Transfer' }}@if($proof?->nomor_referensi) &middot; Ref: {{ $proof->nomor_referensi }}@endif</td>
        </tr>
        <tr>
            <td class="label">Tanggal lunas</td><td class="sep">:</td>
            <td class="val">{{ $tglLunas ? \Carbon\Carbon::parse($tglLunas)->translatedFormat('d F Y') : '-' }}</td>
        </tr>
    </table>

    <table style="margin-top: 18px;">
        <tr>
            <td style="vertical-align: middle;"><span class="amount-box">Rp {{ number_format($jumlahDibayar, 0, ',', '.') }}</span></td>
            <td style="text-align: center; vertical-align: middle;"><span class="lunas-stamp">LUNAS</span></td>
        </tr>
    </table>

    <table style="margin-top: 36px;">
        <tr>
            <td style="width: 60%;">&nbsp;</td>
            <td style="width: 40%; text-align: center;">
                {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                Bendahara Penerimaan,
                <br><br><br><br>
                <strong>( ............................... )</strong>
            </td>
        </tr>
    </table>

    <div class="foot">
        Kuitansi ini dihasilkan otomatis oleh SIKEREN-BLU dan sah tanpa tanda tangan basah.
        Kode verifikasi: {{ $tagihan->kode_verifikasi_digital }}.
    </div>
</body>
</html>
