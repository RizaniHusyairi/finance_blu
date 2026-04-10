<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BAST - {{ $detail->nomor_bast }}</title>
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
        p { text-align: justify; margin-top: 0; margin-bottom: 10px; }
    </style>
</head>
<body>

    <div class="text-center mb-4">
        <div style="font-size: 12pt;">
            KEMENTERIAN PERHUBUNGAN<br>
            DIREKTORAT JENDERAL PERHUBUNGAN UDARA<br>
            BADAN LAYANAN UMUM<br>
            KANTOR UNIT PENYELENGGARA BANDAR UDARA KELAS I<br>
            AJI PANGERAN TUMENGGUNG PRANOTO – SAMARINDA<br>
            <span style="font-size: 10pt;">Jl. Poros Samarinda – Bontang, Kel. Sungai Siring, Samarinda – Kalimantan Timur</span>
        </div>
        <hr style="border: 1px solid black; margin-top: 5px; margin-bottom: 5px;">
    </div>

    <div class="text-center mb-4 mt-3">
        <div class="fw-bold" style="font-size: 14pt; text-decoration: underline;">BERITA ACARA SERAH TERIMA PEKERJAAN</div>
        <div>Nomor : {{ $detail->nomor_bast }}</div>
    </div>

    @php
        \Carbon\Carbon::setLocale('id');
        $bastDate = \Carbon\Carbon::parse($detail->tanggal_bast ?? now());
        $hari = $bastDate->translatedFormat('l');
        $tanggal = $bastDate->format('d');
        $bulan = $bastDate->translatedFormat('F');
        $tahun = $bastDate->format('Y');
    @endphp
        
    <p>Pada hari ini <strong>{{ $hari }}</strong> tanggal <strong>{{ ucwords(terbilang($tanggal)) }}</strong> bulan <strong>{{ $bulan }}</strong> tahun <strong>{{ ucwords(terbilang($tahun)) }}</strong>, yang bertanda tangan di bawah ini:</p>

    <table class="mb-3">
        <tr>
            <td width="5%">I.</td>
            <td width="20%">Nama</td>
            <td width="2%">:</td>
            <td><strong>{{ strtoupper($vendor->nama_direktur ?? '-') }}</strong></td>
        </tr>
        <tr>
            <td></td>
            <td>Jabatan</td>
            <td>:</td>
            <td>Direktur {{ $vendor->nama_pihak ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td>Alamat</td>
            <td>:</td>
            <td>{{ $vendor->alamat ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="3">Selanjutnya dalam Berita Acara ini disebut <strong>PIHAK PERTAMA</strong>.</td>
        </tr>
    </table>

    <table class="mb-3">
        <tr>
            <td width="5%">II.</td>
            <td width="20%">Nama</td>
            <td width="2%">:</td>
            <td><strong>{{ strtoupper($kontrak->ppkUser->name ?? '-') }}</strong></td>
        </tr>
        <tr>
            <td></td>
            <td>Jabatan</td>
            <td>:</td>
            <td>Pejabat Pembuat Komitmen BLU Kantor UPBU Kelas I A.P.T. Pranoto Samarinda</td>
        </tr>
        <tr>
            <td></td>
            <td>Alamat</td>
            <td>:</td>
            <td>Jl. Poros Samarinda – Bontang, Kel. Sungai Siring, Samarinda – Kalimantan Timur</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="3">Selanjutnya dalam Berita Acara ini disebut <strong>PIHAK KEDUA</strong>.</td>
        </tr>
    </table>

    <p>Sehubungan dengan Surat Perintah Kerja (SPK) Nomor : {{ $kontrak->nomor_spk }} tanggal {{ optional($kontrak->tanggal_spk)->translatedFormat('d F Y') ?? '-' }} maka dengan ini <strong>PIHAK PERTAMA</strong> menyerahkan pekerjaan kepada <strong>PIHAK KEDUA</strong> berupa <strong>{{ $kontrak->nama_pekerjaan }}</strong> di Badan Layanan Umum Kantor UPBU Kelas I A.P.T. Pranoto Samarinda.</p>

    <p><strong>PIHAK KEDUA</strong> menerima pekerjaan dari <strong>PIHAK PERTAMA</strong> berdasarkan Berita Acara Pemeriksaan Pekerjaan Nomor : {{ $detail->nomor_bapp }} tanggal {{ optional($detail->tanggal_bapp)->translatedFormat('d F Y') ?? '-' }} dalam keadaan telah terselesaikan dengan baik.</p>

    <p>Demikian Berita Acara Serah Terima Hasil Pekerjaan ini dibuat untuk dipergunakan sebagaimana mestinya.</p>

    <table class="ttd-table mt-4" style="page-break-inside: avoid;">
        <tr>
            <td>
                <strong>PIHAK PERTAMA</strong>,<br>
                <strong>{{ $vendor->nama_pihak }}</strong>
                <div class="ttd-space"></div>
                <u><strong>{{ strtoupper($vendor->nama_direktur ?? '-') }}</strong></u><br>
                Direktur
            </td>
            <td>
                <strong>PIHAK KEDUA</strong>,<br>
                Pejabat Pembuat Komitmen<br>
                Badan Layanan Umum<br>
                Kantor UPBU Kelas I A.P.T. Pranoto-Samarinda
                <div class="ttd-space"></div>
                <u><strong>{{ strtoupper($kontrak->ppkUser->name ?? '-') }}</strong></u><br>
                NIP. {{ $kontrak->ppkUser->pegawai->nip ?? '-' }}
            </td>
        </tr>
    </table>

</body>
</html>
