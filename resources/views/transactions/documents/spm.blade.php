<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SPM - {{ $transaction->transaction_number }}</title>
    <style>
        body { font-family: 'Times New Roman', serif; font-size: 12pt; margin: 2cm; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px double #000; padding-bottom: 10px; }
        .header h3 { margin: 0; font-size: 14pt; text-transform: uppercase; letter-spacing: 1px; }
        .header p { margin: 2px 0; font-size: 10pt; }
        .doc-title { text-align: center; margin: 20px 0; }
        .doc-title h2 { margin: 0; font-size: 14pt; text-decoration: underline; }
        .doc-title p { margin: 4px 0; font-size: 10pt; }
        table.info { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table.info td { padding: 4px 8px; vertical-align: top; }
        table.info td:first-child { width: 35%; font-weight: bold; }
        table.detail { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table.detail th, table.detail td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        table.detail th { background-color: #f0f0f0; font-size: 10pt; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .signature-area { margin-top: 40px; }
        .signature-area table { width: 100%; }
        .signature-area td { text-align: center; padding: 5px; vertical-align: top; width: 50%; }
        .bold { font-weight: bold; }
        .mt-2 { margin-top: 20px; }
        .info-box { border: 1px solid #ccc; padding: 10px; margin: 10px 0; background: #fafafa; }
    </style>
</head>
<body>
    <div class="header">
        <h3>KEMENTERIAN PERHUBUNGAN</h3>
        <p>DIREKTORAT JENDERAL PERHUBUNGAN UDARA</p>
        <p>KANTOR OTORITAS BANDAR UDARA WILAYAH</p>
        <p><small>Alamat: Jl. Contoh No. 1, Kota, Provinsi | Telp: (021) xxx-xxxx</small></p>
    </div>

    <div class="doc-title">
        <h2>SURAT PERINTAH MEMBAYAR (SPM)</h2>
        <p>Nomor: SPM-{{ $transaction->transaction_number }}</p>
        <p>Tanggal: {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }}</p>
    </div>

    <div class="info-box">
        <p><strong>Dasar:</strong> Surat Permintaan Pembayaran (SPP) Nomor {{ $transaction->transaction_number }} tanggal {{ \Carbon\Carbon::parse($transaction->date)->isoFormat('D MMMM Y') }}</p>
    </div>

    <table class="info">
        <tr>
            <td>Jenis SPM</td>
            <td>: SPM-{{ $transaction->type }}</td>
        </tr>
        <tr>
            <td>Uraian Pembayaran</td>
            <td>: {{ $transaction->description }}</td>
        </tr>
        <tr>
            <td>Tahun Anggaran</td>
            <td>: {{ $transaction->budget ? $transaction->budget->year : '-' }}</td>
        </tr>
        <tr>
            <td>Kode Akun / MAK</td>
            <td>: {{ $transaction->budget ? $transaction->budget->coa : '-' }}</td>
        </tr>
        @if($transaction->contract && $transaction->contract->supplier)
        <tr>
            <td>Penerima / Penyedia</td>
            <td>: {{ $transaction->contract->supplier->name }}</td>
        </tr>
        <tr>
            <td>NPWP Penerima</td>
            <td>: {{ $transaction->contract->supplier->npwp ?? '-' }}</td>
        </tr>
        <tr>
            <td>No. Rekening</td>
            <td>: {{ $transaction->contract->supplier->bank_account ?? '-' }}</td>
        </tr>
        <tr>
            <td>Bank</td>
            <td>: {{ $transaction->contract->supplier->bank_name ?? '-' }}</td>
        </tr>
        @endif
    </table>

    <h4>Rincian Pembayaran</h4>
    <table class="detail">
        <thead>
            <tr>
                <th>No</th>
                <th>Uraian</th>
                <th class="text-right">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">1</td>
                <td>Nilai Bruto Tagihan</td>
                <td class="text-right">{{ number_format($transaction->amount, 2, ',', '.') }}</td>
            </tr>
            @php $totalTax = 0; $no = 2; @endphp
            @foreach($transaction->taxes as $tax)
                @php $totalTax += $tax->tax_amount; @endphp
                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td>Potongan {{ $tax->tax_type }} ({{ $tax->percentage }}%)</td>
                    <td class="text-right">({{ number_format($tax->tax_amount, 2, ',', '.') }})</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="bold">Total Potongan Pajak</td>
                <td class="text-right bold">({{ number_format($totalTax, 2, ',', '.') }})</td>
            </tr>
            <tr>
                <td colspan="2" class="bold" style="font-size: 13pt;">JUMLAH BERSIH YANG DIBAYARKAN</td>
                <td class="text-right bold" style="font-size: 13pt;">Rp {{ number_format($transaction->amount - $totalTax, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <p class="mt-2"><strong>Dengan ini diperintahkan untuk membayar sejumlah:</strong></p>
    <p style="font-size: 13pt; font-weight: bold; text-align: center; border: 1px solid #000; padding: 8px; margin: 10px 0;">
        Rp {{ number_format($transaction->amount - $totalTax, 2, ',', '.') }}
    </p>

    <div class="signature-area">
        <table>
            <tr>
                <td>
                    <p>Mengetahui,</p>
                    <p class="bold">Kuasa Pengguna Anggaran (KPA)</p>
                    <br><br><br><br>
                    <p class="bold" style="border-top: 1px solid #000; display: inline-block; padding-top: 4px;">( ............................ )</p>
                    <p>NIP. ...............................</p>
                </td>
                <td>
                    <p>{{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }}</p>
                    <p class="bold">Pejabat Penandatangan SPM (PPSPM)</p>
                    <br><br><br><br>
                    <p class="bold" style="border-top: 1px solid #000; display: inline-block; padding-top: 4px;">( ............................ )</p>
                    <p>NIP. ...............................</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
