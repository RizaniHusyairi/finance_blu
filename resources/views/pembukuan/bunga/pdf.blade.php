<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Buku Pembantu Bunga Rekening</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .text-end { text-align: right; }
    </style>
</head>
<body>
    <h2>Buku Pembantu Bunga Rekening</h2>
    <p>Periode: {{ $filters['start_date'] ?: '-' }} s.d. {{ $filters['end_date'] ?: '-' }}</p>
    <p>Total bunga bulan ini: Rp {{ number_format($summary['bulan_ini'] ?? 0, 0, ',', '.') }} · Total tahun berjalan: Rp {{ number_format($summary['tahun_berjalan'] ?? 0, 0, ',', '.') }}</p>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Rekening</th>
                <th>Deskripsi</th>
                <th>Referensi Bank</th>
                <th class="text-end">Nominal</th>
                <th>Status Rekonsiliasi</th>
                <th>Status BKU</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $entry)
                @php $matchedBku = $matchedBkuMap[$entry->id] ?? null; @endphp
                <tr>
                    <td>{{ optional($entry->tanggal_transaksi)->format('d/m/Y') }}</td>
                    <td>{{ $entry->importMutasiBank?->rekeningBank?->nama_bank ?? '-' }} / {{ $entry->importMutasiBank?->rekeningBank?->nomor_rekening ?? '-' }}</td>
                    <td>{{ $entry->deskripsi ?? '-' }}</td>
                    <td>{{ $entry->nomor_referensi_bank ?? '-' }}</td>
                    <td class="text-end">{{ number_format($entry->debit, 0, ',', '.') }}</td>
                    <td>{{ $entry->status_rekonsiliasi }}</td>
                    <td>{{ $matchedBku ? 'SUDAH_MASUK_BKU' : 'BELUM_MASUK_BKU' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
