<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Buku Pengesahan Belanja</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .text-end { text-align: right; }
    </style>
</head>
<body>
    <h2>Buku Pengesahan Belanja</h2>
    <table>
        <thead>
            <tr>
                <th>Nomor Laporan</th>
                <th>Periode</th>
                <th class="text-end">Penerimaan</th>
                <th class="text-end">Pengeluaran</th>
                <th class="text-end">Saldo Akhir</th>
                <th>Status</th>
                <th>SP3B</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reports as $report)
                <tr>
                    <td>{{ $report->nomor_laporan }}</td>
                    <td>{{ $months[$report->periode_bulan] ?? $report->periode_bulan }} {{ $report->tahun }}</td>
                    <td class="text-end">{{ number_format($report->total_penerimaan, 0, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($report->total_pengeluaran, 0, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($report->saldo_akhir_blu, 0, ',', '.') }}</td>
                    <td>{{ $report->status_pengesahan }}</td>
                    <td>{{ $report->status_sp3b ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
