<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Buku Pembantu Pajak</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .text-end { text-align: right; }
    </style>
</head>
<body>
    <h2>Buku Pembantu Pajak</h2>
    <p>Total potongan: Rp {{ number_format($summary['total_potongan'] ?? 0, 0, ',', '.') }} · Sudah setor: Rp {{ number_format($summary['sudah_setor'] ?? 0, 0, ',', '.') }}</p>
    <table>
        <thead>
            <tr>
                <th>Nomor Tagihan</th>
                <th>Tipe</th>
                <th>Jenis Pajak</th>
                <th class="text-end">Nominal</th>
                <th>Billing</th>
                <th>NTPN</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $entry)
                <tr>
                    <td>{{ $entry->tagihan?->nomor_tagihan ?? '-' }}</td>
                    <td>{{ $entry->tagihan?->tipe_tagihan ?? '-' }}</td>
                    <td>{{ $entry->nama_pajak_snapshot ?? $entry->jenis_potongan }}</td>
                    <td class="text-end">{{ number_format($entry->nominal_potongan, 0, ',', '.') }}</td>
                    <td>{{ $entry->kode_billing ?? '-' }}</td>
                    <td>{{ $entry->ntpn ?? '-' }}</td>
                    <td>{{ $entry->ntpn ? 'SUDAH_SETOR' : ($entry->kode_billing ? 'SUDAH_BILLING' : 'BELUM_SETOR') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
