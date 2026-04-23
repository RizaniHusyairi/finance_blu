<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Buku Pembantu Bendahara</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .summary td { width: 25%; }
        .text-end { text-align: right; }
    </style>
</head>
<body>
    <h2>Buku Pembantu Bendahara</h2>
    <p>Periode: {{ $filters['start_date'] ?: '-' }} s.d. {{ $filters['end_date'] ?: '-' }}</p>

    <table class="summary" style="margin-bottom: 14px;">
        <tr>
            <td><strong>Saldo Awal</strong><br>Rp {{ number_format($summary['saldo_awal'] ?? 0, 0, ',', '.') }}</td>
            <td><strong>Total Penerimaan</strong><br>Rp {{ number_format($summary['total_penerimaan'] ?? 0, 0, ',', '.') }}</td>
            <td><strong>Total Pengeluaran</strong><br>Rp {{ number_format($summary['total_pengeluaran'] ?? 0, 0, ',', '.') }}</td>
            <td><strong>Saldo Akhir</strong><br>Rp {{ number_format($summary['saldo_akhir'] ?? 0, 0, ',', '.') }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Nomor Bukti</th>
                <th>Uraian</th>
                <th>Sumber / Tujuan</th>
                <th class="text-end">Masuk</th>
                <th class="text-end">Keluar</th>
                <th class="text-end">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $entry)
                <tr>
                    <td>{{ optional($entry->tanggal_transaksi)->format('d/m/Y') }}</td>
                    <td>{{ $entry->nomor_bukti }}</td>
                    <td>{{ $entry->uraian }}</td>
                    <td>{{ $entry->referensiPengeluaran?->pihak?->nama_pihak ?? $entry->referensiPenerimaan?->mitra?->nama_pihak ?? '-' }}</td>
                    <td class="text-end">{{ $entry->arus_kas === 'DEBIT_MASUK' ? number_format($entry->nominal, 0, ',', '.') : '-' }}</td>
                    <td class="text-end">{{ $entry->arus_kas === 'KREDIT_KELUAR' ? number_format($entry->nominal, 0, ',', '.') : '-' }}</td>
                    <td class="text-end">{{ number_format($entry->saldo_akhir, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
