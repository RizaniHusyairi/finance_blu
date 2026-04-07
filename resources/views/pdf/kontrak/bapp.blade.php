<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BAPP - {{ $detail->nomor_bapp }}</title>
    <style>
        @page { margin: 2.54cm 2.54cm 2.54cm 2.54cm; }
        body { font-family: "Times New Roman", Times, serif; font-size: 12pt; line-height: 1.5; color: #000; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .mb-1 { margin-bottom: 5px; }
        .mb-2 { margin-bottom: 10px; }
        .mb-3 { margin-bottom: 15px; }
        .mb-4 { margin-bottom: 20px; }
        .mt-3 { margin-top: 15px; }
        .mt-4 { margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        .ttd-table td { width: 50%; text-align: center; }
        .ttd-space { height: 80px; }
        ul { margin-top: 0; padding-left: 20px; }
        .indent { padding-left: 30px; }
        p { text-align: justify; margin-top: 0; margin-bottom: 10px; }
    </style>
</head>
<body>

    <div class="text-center mb-4">
        <div class="fw-bold" style="font-size: 14pt; text-decoration: underline;">BERITA ACARA PEMERIKSAAN PEKERJAAN</div>
        <div>Nomor : {{ $detail->nomor_bapp }}</div>
    </div>

    @php
        \Carbon\Carbon::setLocale('id');
        $bappDate = \Carbon\Carbon::parse($detail->tanggal_bapp ?? now());
        $hari = $bappDate->translatedFormat('l');
        $tanggal = $bappDate->format('d');
        $bulan = $bappDate->translatedFormat('F');
        $tahun = $bappDate->format('Y');
        
    <p>Pada hari ini <strong>{{ $hari }}</strong> tanggal <strong>{{ ucwords(terbilang($tanggal)) }}</strong> bulan <strong>{{ $bulan }}</strong> tahun <strong>{{ ucwords(terbilang($tahun)) }}</strong>, yang bertanda tangan di bawah ini:</p>

    <div class="mb-2">
        <strong>1. {{ strtoupper($detail->nama_pemeriksa ?? '') }}</strong>, dalam hal ini sebagai {{ $detail->jabatan_pemeriksa ?? '' }} Kantor UPBU Kelas I A.P.T. Pranoto Samarinda, bertindak dan ditunjuk oleh Pejabat Pembuat Komitmen sebagai pemeriksa hasil pekerjaan.
    </div>
    
    <div class="mb-3">
        <strong>2. {{ strtoupper($vendor->nama_direktur ?? ' DIREKTUR VENDOR') }}</strong>, dalam hal ini sebagai Penyedia, bertindak sebagai Direktur mewakili <strong>{{ $vendor->nama_pihak }}</strong>.
    </div>

    <p>Secara bersama-sama telah melakukan pemeriksaan atas pelaksanaan pekerjaan yang dilaksanakan oleh <strong>{{ $vendor->nama_pihak }}</strong> berupa <strong>{{ $kontrak->nama_pekerjaan }}</strong>, sesuai Surat Perintah Kerja (SPK) Nomor: {{ $kontrak->nomor_spk }} tanggal {{ optional($kontrak->tanggal_spk)->translatedFormat('d F Y') ?? '-' }} yang dilaksanakan oleh:</p>

    <table class="mb-3">
        <tr>
            <td width="30%">Nama</td>
            <td width="2%">:</td>
            <td><strong>{{ strtoupper($vendor->nama_direktur ?? '-') }}</strong></td>
        </tr>
        <tr>
            <td>Jabatan</td>
            <td>:</td>
            <td>Direktur {{ $vendor->nama_pihak }}</td>
        </tr>
        <tr>
            <td>Alamat</td>
            <td>:</td>
            <td>{{ $vendor->alamat ?? '-' }}</td>
        </tr>
        <tr>
            <td>Harga Pekerjaan</td>
            <td>:</td>
            <td>Rp. {{ number_format($kontrak->nilai_total_kontrak, 0, ',', '.') }},- ({{ $terbilang }})</td>
        </tr>
        <tr>
            <td>Sumber Dana</td>
            <td>:</td>
            <td>APBN DIPA Badan Layanan Umum Kantor UPBU Kelas I A.P.T. Pranoto Samarinda Nomor {{ $kontrak->dipa->nomor_dipa ?? '-' }} tanggal {{ optional($kontrak->dipa->tanggal_disahkan)->translatedFormat('d F Y') ?? '-' }}</td>
        </tr>
    </table>

    <p>Dengan ini menyatakan bahwa pekerjaan seperti tersebut di atas telah selesai dan dilaksanakan dengan baik dan benar sesuai yang tercantum dalam Surat Perintah Kerja (SPK).</p>

    <p>Demikian Berita Acara Pemeriksaan Pekerjaan ini dibuat untuk dipergunakan sebagaimana mestinya.</p>

    <table class="ttd-table mt-4">
        <tr>
            <td>
                Penyedia,<br>
                <strong>{{ $vendor->nama_pihak }}</strong>
                <div class="ttd-space"></div>
                <u><strong>{{ strtoupper($vendor->nama_direktur ?? '-') }}</strong></u><br>
                Direktur
            </td>
            <td>
                Pemeriksa Hasil Pekerjaan,<br>
                Kantor UPBU Kelas I A.P.T. Pranoto-Samarinda
                <div class="ttd-space"></div>
                <u><strong>{{ strtoupper($detail->nama_pemeriksa ?? '-') }}</strong></u><br>
                NIP. {{ $detail->nip_pemeriksa ?? '-' }}
            </td>
        </tr>
    </table>

    <table class="mt-4" style="width: 100%;">
        <tr>
            <td class="text-center">
                Mengetahui,<br>
                Pejabat Pembuat Komitmen<br>
                Badan Layanan Umum<br>
                Kantor UPBU Kelas I A.P.T. Pranoto-Samarinda
                <div class="ttd-space"></div>
                <u><strong>{{ $kontrak->ppkUser->name ?? '-' }}</strong></u><br>
                NIP. {{ $kontrak->ppkUser->pegawai->nip ?? '-' }}
            </td>
        </tr>
    </table>

</body>
</html>
