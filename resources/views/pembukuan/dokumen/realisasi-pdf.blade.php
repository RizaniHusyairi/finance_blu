<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Realisasi Penerimaan {{ $realisasi['tahun'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111827; }
        .kop { text-align: center; margin-bottom: 8px; }
        .kop h2 { font-size: 12px; margin: 0; text-transform: uppercase; }
        .kop h3 { font-size: 11px; margin: 2px 0; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #9ca3af; padding: 3px 4px; }
        .table th { background: #e5e7eb; text-align: center; }
        .text-end { text-align: right; }
        .grand td { background: #eef2ff; font-weight: bold; }
    </style>
</head>
<body>
    <div class="kop">
        <h2>{{ $setup?->nama_satker ?: 'KANTOR BLU' }}</h2>
        <h3>Realisasi Penerimaan Melalui Rekening Bendahara Penerimaan — Tahun {{ $realisasi['tahun'] }}</h3>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width:18px;">No</th>
                <th style="width:55px;">Akun</th>
                <th>Jenis Pelayanan</th>
                @foreach($realisasi['months'] as $m)<th>{{ $m }}</th>@endforeach
                <th style="width:80px;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($realisasi['rows'] as $i => $row)
                <tr>
                    <td class="text-end">{{ $i + 1 }}</td>
                    <td>{{ $row['kode'] }}</td>
                    <td>{{ $row['uraian'] }}</td>
                    @foreach($realisasi['months'] as $mi => $m)
                        <td class="text-end">{{ $row['bulan'][$mi] ? number_format($row['bulan'][$mi], 0, ',', '.') : '' }}</td>
                    @endforeach
                    <td class="text-end">{{ number_format($row['total'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="grand">
                <td colspan="3" class="text-end">TOTAL</td>
                @foreach($realisasi['months'] as $mi => $m)
                    <td class="text-end">{{ $realisasi['total_per_bulan'][$mi] ? number_format($realisasi['total_per_bulan'][$mi], 0, ',', '.') : '' }}</td>
                @endforeach
                <td class="text-end">{{ number_format($realisasi['grand_total'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
    <div style="margin-top:8px; color:#6b7280;">Dicetak {{ now()->translatedFormat('d F Y H:i') }}.</div>
</body>
</html>
