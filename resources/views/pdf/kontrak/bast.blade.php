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
        p { text-align: justify; margin-top: 0; margin-bottom: 10px; }
        footer {
            position: fixed;
            bottom: 0;
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

    <style>
        .qr-tte { width: 80px; height: 80px; margin-top: 5px; margin-bottom: 5px; }
        .ttd-placeholder { height: 80px; }
    </style>

    <table class="ttd-table mt-4" style="page-break-inside: avoid; border: none; width: 100%;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <strong>PIHAK PERTAMA</strong>,<br>
                <strong>{{ $vendor->nama_pihak }}</strong><br>
                @if(!empty($tteQrFilePath))
                    <img src="{{ $tteQrFilePath }}" alt="QR TTE BAST" class="qr-tte"><br>
                @else
                    <div class="ttd-placeholder"></div>
                @endif
                <u><strong>{{ strtoupper($vendor->nama_direktur ?? '-') }}</strong></u><br>
                Direktur
            </td>
            <td style="width: 50%; vertical-align: top;">
                <strong>PIHAK KEDUA</strong>,<br>
                Pejabat Pembuat Komitmen<br>
                Badan Layanan Umum<br>
                Kantor UPBU Kelas I A.P.T. Pranoto-Samarinda<br>
                @if(!empty($tteQrFilePath) && !empty($ppkSigned))
                    <img src="{{ $tteQrFilePath }}" alt="QR TTE BAST" class="qr-tte"><br>
                @else
                    <div class="ttd-placeholder"></div>
                @endif
                <u><strong>{{ strtoupper($kontrak->ppkUser->name ?? '-') }}</strong></u><br>
                NIP. {{ $kontrak->ppkUser->pegawai->nip ?? '-' }}
            </td>
        </tr>
    </table>

</body>
</html>
