@php
    /** @var array $buku */
    $isPenerimaan = $buku['peran'] === 'PENERIMAAN';
    $kolomKlasifikasi = $isPenerimaan ? 'Kode Akun & Jenis Pelayanan' : 'Kode Transaksi';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $buku['nama_buku'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111827; }
        .kop { text-align: center; margin-bottom: 8px; }
        .kop h2 { font-size: 13px; margin: 0; text-transform: uppercase; }
        .kop h3 { font-size: 12px; margin: 2px 0; text-transform: uppercase; }
        .kop .periode { font-size: 10px; margin-top: 2px; }
        .ident { width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 9px; }
        .ident td { padding: 1px 4px; vertical-align: top; }
        .ident .lbl { width: 130px; }
        .ident .sep { width: 8px; }
        .saldo-box { float: right; }
        .table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .table th, .table td { border: 1px solid #9ca3af; padding: 4px 5px; vertical-align: top; }
        .table th { background: #e5e7eb; text-align: center; font-size: 9px; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .opening td { background: #f9fafb; font-style: italic; }
        .total-row td { background: #eef2ff; font-weight: bold; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <div class="kop">
        <h2>{{ $setup?->nama_satker ?: 'KANTOR BLU' }}</h2>
        <h3>Buku Kas Umum {{ $isPenerimaan ? 'Bendahara Penerimaan' : 'Bendahara Pengeluaran' }}</h3>
        @if($buku['kode_buku'] != 1)
            <div>{{ $buku['nama_buku'] }}</div>
        @endif
        <div class="periode">PERIODE : {{ $periodeLabel }}</div>
    </div>

    <table class="ident">
        <tr>
            <td class="lbl">Kementerian/Lembaga</td><td class="sep">:</td>
            <td>{{ $setup?->nama_kl ?: '-' }} @if($setup?->kode_kl)({{ $setup->kode_kl }})@endif</td>
            <td rowspan="4" class="saldo-box">
                <table class="ident" style="border:1px solid #9ca3af;">
                    <tr><td class="lbl"><strong>Saldo Awal</strong></td><td class="text-end">Rp {{ number_format($buku['saldo_awal'], 0, ',', '.') }}</td></tr>
                    <tr><td class="lbl"><strong>Saldo Akhir</strong></td><td class="text-end">Rp {{ number_format($buku['saldo_akhir'], 0, ',', '.') }}</td></tr>
                </table>
            </td>
        </tr>
        <tr><td class="lbl">Unit Organisasi</td><td class="sep">:</td><td>{{ $setup?->nama_unit_org ?: '-' }} @if($setup?->kode_unit_org)({{ $setup->kode_unit_org }})@endif</td></tr>
        <tr><td class="lbl">Satuan Kerja</td><td class="sep">:</td><td>{{ $setup?->nama_satker ?: '-' }} @if($setup?->kode_satker)({{ $setup->kode_satker }})@endif</td></tr>
        <tr><td class="lbl">KPPN</td><td class="sep">:</td><td>{{ $setup?->nama_kppn ?: '-' }} @if($setup?->kode_kppn)({{ $setup->kode_kppn }})@endif</td></tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th style="width:22px;">No</th>
                <th style="width:60px;">Tanggal</th>
                <th style="width:150px;">{{ $kolomKlasifikasi }}</th>
                <th>Uraian Transaksi</th>
                <th style="width:90px;">Penerimaan</th>
                <th style="width:90px;">Pengeluaran</th>
                <th style="width:95px;">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <tr class="opening">
                <td></td><td></td><td></td>
                <td>SALDO AWAL BULAN BERJALAN</td>
                <td class="text-end">{{ number_format($buku['saldo_awal'], 0, ',', '.') }}</td>
                <td class="text-end">0</td>
                <td class="text-end">{{ number_format($buku['saldo_awal'], 0, ',', '.') }}</td>
            </tr>
            @foreach($buku['entries'] as $i => $e)
                @php
                    $masuk = $e->arus_kas === 'DEBIT_MASUK';
                    $klas = $isPenerimaan
                        ? trim(($e->akunPendapatan?->kode_gabungan ?? '') . ' ' . ($e->akunPendapatan?->uraian_jenis ?? ''))
                        : ($e->kode_transaksi ?? '');
                @endphp
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ optional($e->tanggal_transaksi)->format('d/m/Y') }}</td>
                    <td>{{ $klas ?: '-' }}</td>
                    <td>{{ $e->uraian }}</td>
                    <td class="text-end">{{ $masuk ? number_format($e->nominal, 0, ',', '.') : '' }}</td>
                    <td class="text-end">{{ $masuk ? '' : number_format($e->nominal, 0, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($e->saldo_berjalan ?? 0, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="text-center">TOTAL</td>
                <td class="text-end">{{ number_format($buku['total_terima'], 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format($buku['total_keluar'], 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format($buku['saldo_akhir'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="muted" style="margin-top:8px;">Dicetak {{ now()->translatedFormat('d F Y H:i') }} — {{ $buku['jumlah_transaksi'] }} transaksi.</div>
</body>
</html>
