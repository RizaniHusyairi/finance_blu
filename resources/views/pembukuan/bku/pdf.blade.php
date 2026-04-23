<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Buku Kas Umum</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        .summary { width: 100%; margin: 14px 0; border-collapse: collapse; }
        .summary td { border: 1px solid #d1d5db; padding: 8px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        .table th { background: #f3f4f6; text-align: left; }
        .text-end { text-align: right; }
    </style>
</head>
<body>
    <h1>Buku Kas Umum</h1>
    <div class="muted">Dicetak pada {{ now()->format('d M Y H:i') }}</div>
    <div class="muted">Periode: {{ $filters['start_date'] ?: '-' }} s.d. {{ $filters['end_date'] ?: '-' }}</div>

    <table class="summary">
        <tr>
            <td><strong>Total Debit</strong><br>Rp {{ number_format($summary['total_debit'] ?? 0, 0, ',', '.') }}</td>
            <td><strong>Total Kredit</strong><br>Rp {{ number_format($summary['total_kredit'] ?? 0, 0, ',', '.') }}</td>
            <td><strong>Saldo Akhir</strong><br>Rp {{ number_format($summary['saldo_akhir'] ?? 0, 0, ',', '.') }}</td>
            <td><strong>Jumlah Transaksi</strong><br>{{ number_format($summary['jumlah_transaksi'] ?? 0, 0, ',', '.') }}</td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Nomor Bukti</th>
                <th>Uraian</th>
                <th>Rekening</th>
                <th>Arus Kas</th>
                <th class="text-end">Nominal</th>
                <th class="text-end">Saldo</th>
                <th>Referensi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $entry)
                <tr>
                    <td>{{ optional($entry->tanggal_transaksi)->format('d/m/Y') }}</td>
                    <td>{{ $entry->nomor_bukti }}</td>
                    <td>{{ $entry->uraian }}</td>
                    <td>{{ $entry->sumberRekening?->nama_bank ?? '-' }} / {{ $entry->sumberRekening?->nomor_rekening ?? '-' }}</td>
                    <td>{{ $entry->arus_kas }}</td>
                    <td class="text-end">{{ number_format($entry->nominal, 0, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($entry->saldo_akhir, 0, ',', '.') }}</td>
                    <td>{{ $entry->referensiPengeluaran?->nomor_tagihan ?? $entry->referensiPenerimaan?->nomor_invoice ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
