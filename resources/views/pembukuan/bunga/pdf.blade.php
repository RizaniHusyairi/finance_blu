<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Buku Pembantu Bunga Rekening</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color:#111; }
        h2, h3 { margin: 0; }
        .header { text-align: center; margin-bottom: 10px; }
        .meta-box { border: 1px solid #000; padding: 4px 8px; width: 100%; margin-bottom: 6px; border-collapse: collapse; }
        .meta-box td { padding: 2px 6px; }
        table.ledger { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.ledger th, table.ledger td { border: 1px solid #333; padding: 4px 6px; }
        table.ledger th { background: #e8e8e8; text-align: center; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .saldo-row { background: #f4f4f4; font-weight: bold; }
        tfoot td { border: 1px solid #333; padding: 4px 6px; background: #e8e8e8; font-weight: bold; }
    </style>
</head>
<body>
@php
    $periodeLabel = '';
    if (!empty($filters['start_date']) || !empty($filters['end_date'])) {
        $start = $filters['start_date'] ? \Carbon\Carbon::parse($filters['start_date'])->format('d F Y') : '—';
        $end   = $filters['end_date']   ? \Carbon\Carbon::parse($filters['end_date'])->format('d F Y')   : '—';
        $periodeLabel = $start . ' S.D ' . $end;
    }

    $rekeningAktif = null;
    if (!empty($filters['rekening_bank_id'])) {
        $rekeningAktif = $rekeningOptions->firstWhere('id', (int) $filters['rekening_bank_id']);
    }

    $saldoAwal = (float) ($summary['saldo_awal'] ?? 0);
    $saldoAkhir = (float) ($summary['saldo_akhir'] ?? 0);
    $totalPenerimaan = (float) ($summary['total_penerimaan'] ?? 0);
    $totalPengeluaran = (float) ($summary['total_pengeluaran'] ?? 0);
@endphp

<div class="header">
    <div>BLU Kantor UPBU A.P.T. Pranoto Samarinda</div>
    <h3>BUKU PEMBANTU BUNGA REKENING</h3>
    @if($periodeLabel)<div>PERIODE : {{ strtoupper($periodeLabel) }}</div>@endif
</div>

<table class="meta-box">
    <tr>
        <td style="width:18%;"><strong>Kode Buku</strong></td>
        <td style="width:32%;">9</td>
        <td style="width:18%;"><strong>Saldo Awal</strong></td>
        <td class="text-end">Rp {{ number_format($saldoAwal, 2, ',', '.') }}</td>
    </tr>
    <tr>
        <td><strong>Nama Buku</strong></td>
        <td>BUKU PEMBANTU BUNGA REKENING</td>
        <td><strong>Saldo Akhir</strong></td>
        <td class="text-end">Rp {{ number_format($saldoAkhir, 2, ',', '.') }}</td>
    </tr>
    @if($rekeningAktif)
    <tr>
        <td><strong>Rekening</strong></td>
        <td colspan="3">{{ $rekeningAktif->nama_bank }} - {{ $rekeningAktif->nomor_rekening }}</td>
    </tr>
    @endif
</table>

<table class="ledger">
    <thead>
        <tr>
            <th style="width:10%;">TANGGAL</th>
            <th style="width:10%;">NO BUKTI</th>
            <th>URAIAN TRANSAKSI</th>
            <th style="width:14%;">PENERIMAAN</th>
            <th style="width:14%;">PENGELUARAN</th>
            <th style="width:14%;">SALDO</th>
        </tr>
    </thead>
    <tbody>
        <tr class="saldo-row">
            <td class="text-center">{{ $filters['start_date'] ? \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') : '-' }}</td>
            <td class="text-center">—</td>
            <td>SALDO AWAL BULAN BERJALAN</td>
            <td class="text-end">Rp {{ number_format($saldoAwal, 2, ',', '.') }}</td>
            <td class="text-end">—</td>
            <td class="text-end">Rp {{ number_format($saldoAwal, 2, ',', '.') }}</td>
        </tr>

        @forelse($entries as $entry)
            <tr>
                <td class="text-center">{{ optional($entry->tanggal_transaksi)->format('d M Y') }}</td>
                <td class="text-center">{{ $entry->nomor_referensi_bank ?? '—' }}</td>
                <td>{{ $entry->deskripsi ?? '-' }}</td>
                <td class="text-end">{{ $entry->nominal_penerimaan > 0 ? 'Rp ' . number_format($entry->nominal_penerimaan, 2, ',', '.') : '—' }}</td>
                <td class="text-end">{{ $entry->nominal_pengeluaran > 0 ? 'Rp ' . number_format($entry->nominal_pengeluaran, 2, ',', '.') : '—' }}</td>
                <td class="text-end">Rp {{ number_format($entry->saldo_berjalan, 2, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center">Tidak ada transaksi pada periode ini.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" class="text-end">TOTAL</td>
            <td class="text-end">Rp {{ number_format($totalPenerimaan, 2, ',', '.') }}</td>
            <td class="text-end">Rp {{ number_format($totalPengeluaran, 2, ',', '.') }}</td>
            <td class="text-end">Rp {{ number_format($saldoAkhir, 2, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>

</body>
</html>
