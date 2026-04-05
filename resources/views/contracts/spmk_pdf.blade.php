<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SPMK {{ $kontrak->nomor_spmk ?? $kontrak->nomor_spk ?? '-' }}</title>
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 14px;
            line-height: 1.5;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-justify { text-align: justify; }
        .fw-bold { font-weight: bold; }
        
        .header-section { margin-bottom: 30px; margin-top: 20px;}
        
        table { width: 100%; border-collapse: collapse; }
        
        .info-table { margin-left: 0px; }
        .info-table td { padding: 4px 6px; vertical-align: top; }
        .info-table td:first-child { width: 120px; }
        .info-table td:nth-child(2) { width: 10px; text-align: center; }

        .signatures { margin-top: 40px; }
        .signatures td { width: 50%; text-align: center; vertical-align: top; padding-top: 20px;}
    </style>
</head>
<body>
    <div class="header-section text-center fw-bold">
        <div style="font-size: 16px; margin-bottom: 5px; text-decoration: underline;">SURAT PERINTAH MULAI KERJA (SPMK)</div>
        <div style="font-weight: normal;">Nomor: {{ $kontrak->nomor_spmk ?? $kontrak->nomor_spk ?? '-' }}</div>
        <div style="font-weight: normal; margin-top: 5px;">Paket Pekerjaan: {{ $kontrak->nama_pekerjaan ?? '-' }}</div>
    </div>

    <div style="margin-top: 30px;">Yang bertanda tangan di bawah ini :</div>
    <table class="info-table" style="margin-bottom: 15px;">
        <tr>
            <td>Nama</td>
            <td>:</td>
            <td class="fw-bold">{{ strtoupper($kontrak->nama_ppk ?? '-') }}</td>
        </tr>
        <tr>
            <td>Jabatan</td>
            <td>:</td>
            <td>Pejabat Pembuat Komitmen Kantor Kelas I UPBU A.P.T. Pranoto-Samarinda</td>
        </tr>
        <tr>
            <td>Alamat</td>
            <td>:</td>
            <td class="text-justify">
                Kantor UPBU Kelas I A.P.T. Pranoto Samarinda<br>
                Jl. Poros Samarinda – Bontang, Kel. Sungai Siring, Samarinda - Kalimantan Timur
            </td>
        </tr>
    </table>

    <div class="text-justify">Selanjutnya disebut sebagai Pejabat Penandatangan Kontrak;</div>

    <div class="text-justify" style="margin-top: 15px;">
        Berdasarkan Surat Perintah Kerja (SPK) Nomor: {{ $kontrak->nomor_spk ?? '-' }} tanggal {{ $kontrak->tanggal_spk ? \Carbon\Carbon::parse($kontrak->tanggal_spk)->translatedFormat('d F Y') : '-' }}, bersama ini memerintahkan:
    </div>

    <table class="info-table" style="margin-top: 10px; margin-bottom: 15px;">
        <tr>
            <td>Nama Penyedia</td>
            <td>:</td>
            <td class="fw-bold">{{ strtoupper($vendor->nama_pihak ?? '-') }}</td>
        </tr>
        <tr>
            <td>Alamat</td>
            <td>:</td>
            <td class="text-justify">{{ $vendor->alamat ?? '-' }}</td>
        </tr>
        <tr>
            <td colspan="3" style="padding-top: 10px;">Yang dalam hal ini diwakili oleh : {{ $vendor->nama_penanggung_jawab ?? '-' }}</td>
        </tr>
    </table>

    <div class="text-justify">Selanjutnya disebut sebagai “Penyedia”;</div>

    <div class="text-justify" style="margin-top: 15px;">
        untuk segera memulai pelaksanaan pekerjaan dengan memperhatikan ketentuan-ketentuan sebagai berikut:
    </div>

    <ol class="text-justify" style="padding-left: 20px;">
        <li style="margin-bottom: 5px;">Paket pengadaan: {{ $kontrak->nama_pekerjaan ?? '-' }};</li>
        <li style="margin-bottom: 5px;">Syarat-syarat pekerjaan: sesuai dengan persyaratan dan ketentuan SPK;</li>
        <li style="margin-bottom: 5px;">
            Waktu penyelesaian: selama {{ $kontrak->jangka_waktu ?? '-' }} {{ isset($terbilangJangkaWaktu) ? '('.ucwords($terbilangJangkaWaktu).')' : '' }} {{ strtolower($kontrak->satuan_waktu ?? 'hari') }} kalender dan pekerjaan harus sudah selesai pada tanggal {{ $kontrak->tanggal_selesai ? \Carbon\Carbon::parse($kontrak->tanggal_selesai)->translatedFormat('d F Y') : '-' }};
        </li>
        <li style="margin-bottom: 5px;">Denda: Terhadap setiap hari keterlambatan pelaksanaan/penyelesaian pekerjaan Penyedia akan dikenakan Denda Keterlambatan sebesar 1/1000 (satu permil) dari nilai SPK atau dari nilai bagian SPK (tidak termasuk PPN) sesuai ketentuan dalam SPK.</li>
    </ol>

    <table class="signatures">
        <tr>
            <td>
                Menerima dan Menyetujui,<br>
                Untuk dan atas nama<br>
                <b>{{ strtoupper($vendor->nama_pihak ?? '-') }}</b><br>
                <br><br><br><br>
                <b><u>{{ $vendor->nama_penanggung_jawab ?? '-' }}</u></b><br>
                Direktur
            </td>
            <td>
                Samarinda, {{ $kontrak->tanggal_spmk ? \Carbon\Carbon::parse($kontrak->tanggal_spmk)->translatedFormat('d F Y') : ($kontrak->tanggal_spk ? \Carbon\Carbon::parse($kontrak->tanggal_spk)->translatedFormat('d F Y') : '-') }}<br>
                Untuk dan atas nama<br>
                Badan Layanan Umum<br>
                Kantor UPBU Kelas I A.P.T. Pranoto Samarinda<br>
                Pejabat Penandatangan Kontrak<br>
                <br><br><br><br>
                <b><u>{{ $kontrak->nama_ppk ?? '-' }}</u></b><br>
                NIP. {{ $kontrak->nip_ppk ?? '-' }}
            </td>
        </tr>
    </table>
</body>
</html>
