<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice PNBP - {{ $tagihan->nomor_tagihan }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; line-height: 1.5; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; font-size: 14px; }
        .info-table { width: 100%; margin-bottom: 30px; }
        .info-table td { padding: 5px; vertical-align: top; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 10px; }
        .items-table th { background-color: #f8f9fa; font-weight: bold; text-align: left; }
        .items-table .text-right { text-align: right; }
        .items-table .text-center { text-align: center; }
        .footer { margin-top: 50px; text-align: right; }
        .terbilang { background-color: #f8f9fa; padding: 10px; border-left: 4px solid #0d6efd; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <h1>INVOICE TAGIHAN JASA (PNBP)</h1>
        <p>Nomor: {{ $tagihan->nomor_tagihan }} | Tanggal: {{ \Carbon\Carbon::parse($tagihan->tanggal_tagihan)->format('d F Y') }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>Kepada Yth.</strong></td>
            <td width="35%">: {{ $tagihan->mitra->nama_pihak }}<br>&nbsp;&nbsp;{{ $tagihan->mitra->alamat ?? '' }}</td>
            <td width="20%"><strong>Nomor Kontrak</strong></td>
            <td width="30%">: {{ $tagihan->nomor_kontrak ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>NPWP</strong></td>
            <td>: {{ $tagihan->mitra->npwp ?? '-' }}</td>
            <td><strong>Virtual Account</strong></td>
            <td>: <strong>{{ $tagihan->nomor_va ?? 'BELUM TERSEDIA' }}</strong> (BTN)</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="45%">Deskripsi Layanan</th>
                <th width="10%" class="text-center">Qty</th>
                <th width="20%" class="text-right">Harga Satuan</th>
                <th width="20%" class="text-right">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tagihan->details as $detail)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td>{{ $detail->layananJasa->nama_layanan }}</td>
                <td class="text-center">{{ rtrim(rtrim(number_format($detail->qty, 2, ',', '.'), '0'), ',') }}</td>
                <td class="text-right">{{ number_format($detail->harga_satuan, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-right">TOTAL TAGIHAN</th>
                <th class="text-right">{{ number_format($tagihan->total_tagihan, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="terbilang">
        <strong>Terbilang:</strong> {{ ucwords($terbilang) }}
    </div>

    <div class="footer">
        <p>Mengetahui,</p>
        <p style="margin-bottom: 80px;"><strong>KPA / KABANDARA</strong></p>
        <p>_______________________</p>
    </div>
</body>
</html>
