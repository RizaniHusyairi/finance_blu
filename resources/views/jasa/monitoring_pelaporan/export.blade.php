@php
    $bulanNama = \Carbon\Carbon::create()->month((int) $bulan)->translatedFormat('F');
    $statusLabel = fn ($s) => match ($s) {
        'belum' => 'Belum Lapor',
        'draft' => 'Draft',
        'diajukan' => 'Diajukan',
        'diverifikasi' => 'Terverifikasi',
        'ditagihkan' => 'Ditagihkan',
        'ditolak' => 'Ditolak',
        default => ucfirst((string) $s),
    };
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; }
        h2 { margin: 0 0 4px; font-size: 16px; }
        .muted { color: #555; font-size: 11px; margin-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 10px; }
        th, td { border: 1px solid #999; padding: 5px 7px; text-align: left; }
        th { background: #eff6ff; color: #1d4ed8; }
        .belum { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Monitoring Pelaporan Mitra</h2>
    <div class="muted">Periode: {{ $bulanNama }} {{ $tahun }} &middot; Dicetak: {{ $generatedAt->format('d/m/Y H:i') }}</div>
    <div class="muted">Wajib lapor: {{ $summary['total'] }} &middot; Belum lapor: {{ $summary['belum'] }} &middot; Sudah: {{ $summary['sudah'] }} &middot; Menunggu verifikasi: {{ $summary['diajukan'] }}</div>

    <table>
        <thead>
            <tr>
                <th style="width:5%;">No</th>
                <th style="width:30%;">Mitra</th>
                <th style="width:35%;">Layanan</th>
                <th style="width:13%;">Jenis</th>
                <th style="width:17%;">Status Pelaporan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['mitra_nama'] }}</td>
                    <td>{{ $row['layanan_nama'] }}</td>
                    <td>{{ $row['jenis'] === 'konsesi' ? 'Konsesi' : 'PAX PJP2U' }}</td>
                    <td class="{{ $row['status'] === 'belum' ? 'belum' : '' }}">{{ $statusLabel($row['status']) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;">Tidak ada data sesuai filter.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
