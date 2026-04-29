<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SPMK {{ $kontrak->nomor_spmk ?? $kontrak->nomor_spk ?? '-' }}</title>
    <style>
        @page { margin: 30px 40px 50px 40px; }
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
        
        footer {
            position: fixed;
            bottom: -30px;
            left: 0px;
            right: 0px;
            text-align: center;
        }
    </style>
</head>
<body>
    <footer>
        <?php 
            $footerPath = public_path('logo/footer_pdf.png');
            $footerType = pathinfo($footerPath, PATHINFO_EXTENSION);
            if (file_exists($footerPath)) {
                $footerData = file_get_contents($footerPath);
                $footerBase64 = 'data:image/' . $footerType . ';base64,' . base64_encode($footerData);
            } else {
                $footerBase64 = '';
            }
        ?>
        <img src="{{ $footerBase64 }}" alt="Footer" style="width: 80%; height: auto; display: block; margin: 0 auto;">
    </footer>

    <div class="header" style="margin-bottom: 15px;">
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 5px; border: none;">
            <tr>
                <td style="width: 15%; text-align: center; vertical-align: middle; border: none; padding: 0;">
                    <?php 
                        $path = public_path('logo/Logo_Kementerian_Perhubungan_Indonesia_(Kemenhub).png');
                        $type = pathinfo($path, PATHINFO_EXTENSION);
                        if (file_exists($path)) {
                            $data = file_get_contents($path);
                            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                        } else {
                            $base64 = '';
                        }
                    ?>
                    <img src="{{ $base64 }}" alt="Logo Kemenhub" style="width: 90px; height: auto;">
                </td>
                <td style="width: 85%; text-align: center; vertical-align: middle; border: none; padding: 0;">
                    <div style="font-size: 18px; font-weight: bold; letter-spacing: 2px;">KEMENTERIAN PERHUBUNGAN</div>
                    <div style="font-size: 16px; font-weight: bold; letter-spacing: 2px;">DIREKTORAT JENDERAL PERHUBUNGAN UDARA</div>
                    <div style="font-size: 14px; font-weight: bold;">BADAN LAYANAN UMUM</div>
                    <div style="font-size: 14px; font-weight: bold;">KANTOR UNIT PENYELENGGARA BANDAR UDARA KELAS I</div>
                    <div style="font-size: 14px; font-weight: bold;">AJI PANGERAN TUMENGGUNG PRANOTO &ndash; SAMARINDA</div>
                </td>
            </tr>
        </table>
        
        <table style="width: 100%; border-collapse: collapse; border: none; font-weight: normal; font-size: 13px;">
            <tr>
                <td style="width: 44%; text-align: left; vertical-align: top; border: none; padding: 0 10px 0 55px;">
                    Jl. Poros Samarinda &ndash; Bontang, Kel. Sungai<br>
                    Siring, Samarinda &ndash; Kalimantan Timur
                </td>
                <td style="width: 1px; background-color: black; padding: 0;"></td>
                <td style="width: 16%; text-align: left; vertical-align: top; border: none; padding: 0 10px;">
                    TELP. (0541)<br>
                    2831593
                </td>
                <td style="width: 1px; background-color: black; padding: 0;"></td>
                <td style="width: 39%; text-align: left; vertical-align: top; border: none; padding: 0 10px;">
                    <table style="width: 100%; border-collapse: collapse; border: none; font-size: 13px;">
                        <tr>
                            <td style="width: 40px; border: none; padding: 0;">FAX</td>
                            <td style="width: 10px; border: none; padding: 0;">:</td>
                            <td style="border: none; padding: 0;">(0541) 743786</td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 0;">EMAIL</td>
                            <td style="border: none; padding: 0;">:</td>
                            <td style="border: none; padding: 0; color: blue; text-decoration: underline;">mail.aptpranotoairport@gmail.com</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div style="border-bottom: 2px solid black; margin-top: 5px; margin-bottom: 2px;"></div>
        <div style="border-bottom: 1px solid black;"></div>
    </div>

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
