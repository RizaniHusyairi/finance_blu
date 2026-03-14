<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>BKU {{ $year }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 10pt; color: #333; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 3px double #000; padding-bottom: 10px; }
        .header h2 { font-size: 14pt; margin-bottom: 2px; text-transform: uppercase; }
        .header h3 { font-size: 12pt; margin-bottom: 2px; }
        .header p { font-size: 9pt; color: #555; }
        .meta { margin-bottom: 10px; font-size: 9pt; }
        .meta td { padding: 2px 8px 2px 0; }
        table.bku { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        table.bku th, table.bku td { border: 1px solid #000; padding: 3px 5px; }
        table.bku th { background: #e0e0e0; text-align: center; font-weight: bold; }
        table.bku td.num { text-align: right; font-family: 'Courier New', monospace; }
        table.bku td.center { text-align: center; }
        table.bku .saldo-row { background: #f0f8ff; font-weight: bold; }
        table.bku .total-row { background: #d0d0d0; font-weight: bold; }
        .footer { margin-top: 20px; font-size: 9pt; }
        .signature { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature div { text-align: center; width: 30%; display: inline-block; }
        .page-info { text-align: right; font-size: 8pt; color: #888; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="page-info">Dicetak: {{ now()->format('d/m/Y H:i') }}</div>
    
    <div class="header">
        <h2>Kementerian Perhubungan RI</h2>
        <h3>Buku Kas Umum (BKU)</h3>
        <p>Satker: Kantor Otoritas Bandar Udara Wilayah — Tahun Anggaran {{ $year }}</p>
        <p>Periode: {{ $monthName }} {{ $year }}{{ $budgetId ? ' — Akun Terpilih' : '' }}</p>
    </div>

    <table class="meta">
        <tr><td><strong>Pagu Anggaran</strong></td><td>: Rp {{ number_format($totalPagu, 0, ',', '.') }}</td></tr>
        <tr><td><strong>Total Pencairan</strong></td><td>: Rp {{ number_format($runningDebit, 0, ',', '.') }}</td></tr>
        <tr><td><strong>Sisa Anggaran</strong></td><td>: Rp {{ number_format($runningSaldo, 0, ',', '.') }}</td></tr>
    </table>

    <table class="bku">
        <thead>
            <tr>
                <th style="width:25px">No</th>
                <th style="width:60px">Tanggal</th>
                <th>No. Transaksi</th>
                <th>Uraian</th>
                <th>Penyedia</th>
                <th>Akun</th>
                <th style="width:80px">Bruto (Rp)</th>
                <th style="width:70px">Pajak (Rp)</th>
                <th style="width:80px">Netto (Rp)</th>
                <th style="width:85px">Saldo (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr class="saldo-row">
                <td colspan="9"><strong>Saldo Awal (Pagu Anggaran)</strong></td>
                <td class="num"><strong>{{ number_format($totalPagu, 0, ',', '.') }}</strong></td>
            </tr>
            @forelse($bkuRows as $i => $row)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td class="center">{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                <td>{{ $row['transaction_number'] }}</td>
                <td>{{ Str::limit($row['description'], 30) }}</td>
                <td>{{ Str::limit($row['supplier'], 20) }}</td>
                <td class="center">{{ $row['budget_coa'] }}</td>
                <td class="num">{{ number_format($row['bruto'], 0, ',', '.') }}</td>
                <td class="num">{{ $row['tax'] > 0 ? number_format($row['tax'], 0, ',', '.') : '-' }}</td>
                <td class="num">{{ number_format($row['netto'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format($row['saldo'], 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align:center; padding: 15px;">Tidak ada transaksi pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
        @if(count($bkuRows) > 0)
        <tfoot>
            <tr class="total-row">
                <td colspan="6" style="text-align:right"><strong>JUMLAH</strong></td>
                <td class="num">{{ number_format(collect($bkuRows)->sum('bruto'), 0, ',', '.') }}</td>
                <td class="num">{{ number_format(collect($bkuRows)->sum('tax'), 0, ',', '.') }}</td>
                <td class="num">{{ number_format(collect($bkuRows)->sum('netto'), 0, ',', '.') }}</td>
                <td class="num">{{ number_format($runningSaldo, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        <table style="width:100%; margin-top: 30px;">
            <tr>
                <td style="width:33%; text-align:center;">
                    <p>Mengetahui,</p>
                    <p><strong>Kuasa Pengguna Anggaran</strong></p>
                    <br><br><br>
                    <p>_________________________</p>
                    <p>NIP.</p>
                </td>
                <td style="width:33%; text-align:center;">
                    <p>&nbsp;</p>
                    <p><strong>Bendahara Pengeluaran</strong></p>
                    <br><br><br>
                    <p>_________________________</p>
                    <p>NIP.</p>
                </td>
                <td style="width:33%; text-align:center;">
                    <p>&nbsp;</p>
                    <p><strong>Operator BLU / BKU</strong></p>
                    <br><br><br>
                    <p>_________________________</p>
                    <p>NIP.</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
