<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>STANDING INSTRUCTION - {{ $si->nomor_surat ?? 'DRAFT' }}</title>
    <style>
        @page { margin: 40px 50px; }
        body { font-family: "Arial", sans-serif; font-size: 14px; line-height: 1.5; color: #000; }
        .text-center { text-align: center; }
        .text-justify { text-align: justify; }
        .fw-bold { font-weight: bold; }
        .mt-5 { margin-top: 50px; }
        .mb-2 { margin-bottom: 20px; }
        .mb-4 { margin-bottom: 40px; }
        table { width: 100%; border-collapse: collapse; }
        table td { vertical-align: top; padding: 5px 0; }
        .table-data td { padding: 8px 0; }
        .col-label { width: 200px; }
        .col-colon { width: 20px; text-align: center; }
        .ttd-box { width: 50%; float: left; text-align: center; }
        .clearfix::after { content: ""; clear: both; display: table; }
        .footer-logo { position: absolute; bottom: 20px; right: 20px; width: 100px; }
        .footer-text { position: absolute; bottom: 30px; left: 0; right: 0; text-align: center; font-style: italic; color: #4DA1D1; font-family: "Georgia", serif; font-size: 16px; }
        footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <footer>
        <?php 
            $footerPath = public_path('logo/footer_pdf-removebg-preview.png');
            $footerType = pathinfo($footerPath, PATHINFO_EXTENSION);
            if (file_exists($footerPath)) {
                $footerData = file_get_contents($footerPath);
                $footerBase64 = 'data:image/' . $footerType . ';base64,' . base64_encode($footerData);
            } else {
                $footerBase64 = '';
            }
        ?>
        <img src="{{ $footerBase64 }}" alt="Footer" style="width: 65%; height: auto; opacity: 0.8;">
    </footer>
    @if($si->status !== 'FINAL')
    <div style="position: absolute; top: 30%; left: 10%; width: 80%; transform: rotate(-45deg); opacity: 0.1; font-size: 100px; text-align: center; font-weight: bold; color: red; z-index: -1;">
        DRAFT
    </div>
    @endif
    @php
        $rekeningSumberNomor = $si->rekening_sumber_nomor ?: '-';
        $rekeningSumberNama = $si->rekening_sumber_nama ?: '-';
        $rekeningSumberBank = $si->rekening_sumber_bank ? ' pada ' . $si->rekening_sumber_bank : '';
    @endphp

    <div class="text-center mb-4">
        <div class="fw-bold" style="font-size: 16px;">STANDING INSTRUCTION</div>
        <div class="fw-bold" style="font-size: 16px; margin-top: 5px;">SURAT PERINTAH TRANSFER DANA</div>
    </div>

    <div class="mb-4">
        Yang Bertandatangan di bawah ini:
    </div>

    <table class="mb-4 table-data">
        <tr>
            <td class="col-label">Nama</td>
            <td class="col-colon">:</td>
            <td>{{ $si->nama_ppk_snapshot }}</td>
        </tr>
        <tr>
            <td class="col-label">Jabatan</td>
            <td class="col-colon">:</td>
            <td>{{ $si->jabatan_ppk_snapshot }}</td>
        </tr>
    </table>

    <div class="text-justify mb-2">
        Bersama ini meminta Bendahara Penerimaan agar dapat melakukan transfer dana dari nomor rekening {{ $rekeningSumberNomor }} atas nama {{ $rekeningSumberNama }}{{ $rekeningSumberBank }} sebesar Rp{{ number_format($si->nominal_transfer, 0, ',', '.') }},- ({{ ucwords(terbilang($si->nominal_transfer)) }} Rupiah) ke rekening:
    </div>

    <table class="mb-4 table-data">
        <tr>
            <td class="col-label">Nomor Rekening</td>
            <td class="col-colon">:</td>
            <td>{{ $si->rekening_tujuan_nomor }}</td>
        </tr>
        <tr>
            <td class="col-label">Nama Rekening</td>
            <td class="col-colon">:</td>
            <td>{{ $si->rekening_tujuan_nama }}</td>
        </tr>
        <tr>
            <td class="col-label">Nama Bank</td>
            <td class="col-colon">:</td>
            <td>{{ $si->rekening_tujuan_bank }}</td>
        </tr>
    </table>

    <div class="text-justify mb-4">
        Dana tersebut akan dipergunakan untuk keperluan {{ $si->uraian_penggunaan ?? 'kegiatan operasional Bandar Udara' }} <i>(daftar terlampir)</i>.
    </div>

    <div class="mt-5 clearfix">
        <div class="ttd-box">
            <div style="margin-bottom: 10px;">Mengetahui,</div>
            <div style="margin-bottom: 70px;">{{ $si->jabatan_kpa_snapshot }}</div>
            <div>{{ $si->nama_kpa_snapshot }}</div>
        </div>
        <div class="ttd-box">
            <div style="margin-bottom: 10px;">Samarinda, {{ $si->tanggal_surat ? \Carbon\Carbon::parse($si->tanggal_surat)->locale('id')->isoFormat('D MMMM Y') : '......................' }}</div>
            <div style="margin-bottom: 70px;">Yang Membuat,</div>
            <div>{{ $si->nama_ppk_snapshot }}</div>
        </div>
    </div>

</body>
</html>
