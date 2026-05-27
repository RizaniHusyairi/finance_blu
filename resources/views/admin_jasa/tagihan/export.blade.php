<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111827; }
        h2 { margin: 0 0 4px; font-size: 18px; }
        h3 { margin: 14px 0 6px; font-size: 12px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 6px; vertical-align: top; }
        th { background: #e5eefb; font-weight: 700; text-align: left; }
        .summary th { width: 28%; background: #f8fafc; }
        .right { text-align: right; }
        .center { text-align: center; }
        .text { mso-number-format: "\@"; }
    </style>
</head>
<body>
@php
    $isExcel = ($exportFormat ?? '') === 'excel';
    $rupiah = fn ($value) => $isExcel
        ? number_format((float) $value, 2, '.', '')
        : 'Rp ' . number_format((float) $value, 0, ',', '.');
    $angka = fn ($value) => $isExcel ? (string) $value : number_format((float) $value, 0, ',', '.');
    $tanggal = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '-';
    $badge = function ($tagihan) {
        return match ($tagihan->status_jatuh_tempo) {
            'LEWAT_JATUH_TEMPO' => 'Lewat Jatuh Tempo',
            'JATUH_TEMPO_HARI_INI' => 'Jatuh Tempo Hari Ini',
            'MENDEKATI_JATUH_TEMPO' => 'Mendekati Jatuh Tempo',
            'NORMAL' => 'Normal',
            'LUNAS' => 'Lunas',
            default => 'Belum Diset',
        };
    };
@endphp

<h2>{{ $title }}</h2>
<div class="muted">Dicetak pada {{ $generatedAt->format('d/m/Y H:i') }}</div>

<h3>Filter</h3>
<table class="summary">
    <tbody>
    @foreach($filterLabels as $label => $value)
        <tr>
            <th>{{ $label }}</th>
            <td>{{ $value }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<h3>Ringkasan</h3>
<table class="summary">
    <tbody>
        <tr><th>Total Tagihan</th><td class="right">{{ $angka($summary['count']) }}</td></tr>
        <tr><th>Nominal Tagihan</th><td class="right">{{ $rupiah($summary['nominal']) }}</td></tr>
        <tr><th>Sisa Tagihan</th><td class="right">{{ $rupiah($summary['sisa']) }}</td></tr>
    </tbody>
</table>

<h3>Daftar Tagihan</h3>
<table>
    <thead>
        <tr>
            <th>No</th>
            <th>No Tagihan</th>
            <th>Mitra Jasa</th>
            <th>Layanan</th>
            <th>Tanggal</th>
            <th>Jatuh Tempo</th>
            <th class="right">Total</th>
            <th class="right">Dibayar</th>
            <th class="right">Sisa</th>
            <th>Status</th>
            <th>Pembayaran</th>
            <th>Status Jatuh Tempo</th>
        </tr>
    </thead>
    <tbody>
        @forelse($tagihans as $tagihan)
            <tr>
                <td class="center">{{ $loop->iteration }}</td>
                <td class="text">{{ $tagihan->nomor_tagihan }}</td>
                <td>{{ $tagihan->mitra->nama_mitra ?? '-' }}</td>
                <td>
                    {{ $tagihan->details->map(fn ($detail) => $detail->layananJasa?->nama_layanan)->filter()->unique()->implode(', ') ?: '-' }}
                </td>
                <td>{{ $tanggal($tagihan->tanggal_tagihan) }}</td>
                <td>{{ $tanggal($tagihan->tanggal_jatuh_tempo) }}</td>
                <td class="right">{{ $rupiah($tagihan->total_tagihan) }}</td>
                <td class="right">{{ $rupiah($tagihan->jumlah_dibayar) }}</td>
                <td class="right">{{ $rupiah($tagihan->sisa_tagihan) }}</td>
                <td>{{ str_replace('_', ' ', $tagihan->status ?? '-') }}</td>
                <td>{{ str_replace('_', ' ', $tagihan->status_pembayaran ?? 'belum_dibayar') }}</td>
                <td>{{ $badge($tagihan) }}</td>
            </tr>
        @empty
            <tr><td colspan="12" class="center muted">Tidak ada tagihan jasa pada periode ini.</td></tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
