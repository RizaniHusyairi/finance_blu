<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ringkasan Kontrak {{ $kontrak->nomor_spk ?? '-' }}</title>
    <style>
        body { font-family: "Times New Roman", Times, serif; font-size: 13px; line-height: 1.5; color: #000; }
        .text-center { text-align: center; }
        .text-justify { text-align: justify; }
        .fw-bold { font-weight: bold; }
        
        .header { text-align: center; font-weight: bold; margin-bottom: 20px; line-height: 1.2;}
        .title { text-align: center; font-weight: bold; text-decoration: underline; margin-bottom: 10px; font-size: 14px;}
        
        table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px 6px; vertical-align: top; }
        .info-table td.num { width: 20px; text-align: right; font-weight: normal; }
        .info-table td.label { width: 200px; }
        .info-table td.colon { width: 10px; text-align: center; }

        .signatures { margin-top: 40px; }
        .signatures td { width: 50%; text-align: center; vertical-align: top; padding-top: 20px;}
    </style>
</head>
<body>
    <div class="header">
        <div style="font-size: 16px;">KEMENTERIAN PERHUBUNGAN</div>
        <div style="font-size: 15px;">DIREKTORAT JENDERAL PERHUBUNGAN UDARA</div>
        <div style="font-size: 15px;">BADAN LAYANAN UMUM</div>
        <div style="font-size: 15px;">KANTOR UNIT PENYELENGGARA BANDAR UDARA KELAS I</div>
        <div style="font-weight: normal; font-size: 12px; margin-top: 5px;">
            Jl. Poros Samarinda – Bontang, Kel. Sungai Siring, Samarinda – Kalimantan Timur<br>
            TELP. (0541) 2831593 &nbsp; FAX : (0541) 743786 &nbsp; EMAIL : mail.aptpranotoairport@gmail.com
        </div>
        <div style="border-bottom: 3px solid black; margin-top: 10px; margin-bottom: 2px;"></div>
        <div style="border-bottom: 1px solid black; margin-bottom: 15px;"></div>
    </div>

    <div class="title">RINGKASAN KONTRAK</div>
    <div style="margin-bottom: 10px;">Untuk Kegiatan yang bersumber dari APBN (BLU) :</div>

    <table class="info-table">
        <tr>
            <td class="num">1.</td>
            <td class="label">Nomor dan Tanggal DIPA</td>
            <td class="colon">:</td>
            <td>SP DIPA- {{ $dipa->nomor_dipa ?? '-' }} tanggal {{ $dipa && $dipa->tanggal_disahkan ? \Carbon\Carbon::parse($dipa->tanggal_disahkan)->translatedFormat('d F Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="num">2.</td>
            <td class="label">Nama dan Kode Program Kegiatan</td>
            <td class="colon">:</td>
            <td>{{ $coa->kode_mak_lengkap ?? '-' }}</td>
        </tr>
        <tr>
            <td class="num">3.</td>
            <td class="label">Nomor dan Tanggal SPK</td>
            <td class="colon">:</td>
            <td>{{ $kontrak->nomor_spk ?? '-' }} tanggal {{ $kontrak->tanggal_spk ? \Carbon\Carbon::parse($kontrak->tanggal_spk)->translatedFormat('d F Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="num">4.</td>
            <td class="label">Penyedia</td>
            <td class="colon">:</td>
            <td class="fw-bold">{{ strtoupper($vendor->nama_pihak ?? '-') }}</td>
        </tr>
        <tr>
            <td class="num">5.</td>
            <td class="label">Alamat Penyedia</td>
            <td class="colon">:</td>
            <td class="text-justify">{{ $vendor->alamat ?? '-' }}</td>
        </tr>
        <tr>
            <td class="num">6.</td>
            <td class="label">NPWP</td>
            <td class="colon">:</td>
            <td>{{ $vendor->npwp ?? '-' }}</td>
        </tr>
        <tr>
            <td class="num">7.</td>
            <td class="label">Nilai Kontrak/SPK</td>
            <td class="colon">:</td>
            <td>Rp. {{ number_format((float) ($kontrak->nilai_total_kontrak ?? 0), 0, ',', '.') }},- ({{ ucwords($terbilangNilaiKontrak ?? '-') }} Rupiah)</td>
        </tr>
        <tr>
            <td class="num">8.</td>
            <td class="label">Uraian/Volume Pekerjaan</td>
            <td class="colon">:</td>
            <td>{{ $kontrak->nama_pekerjaan ?? '-' }}</td>
        </tr>
        <tr>
            <td class="num">9.</td>
            <td class="label">Cara Pembayaran</td>
            <td class="colon">:</td>
            <td class="text-justify">
                {{ $caraPembayaran ?? 'LUMPSUM' }} rekening nomor : {{ $rekeningVendor->nomor_rekening ?? '-' }} pada Bank {{ $rekeningVendor->nama_bank ?? '-' }} atas nama {{ $rekeningVendor->nama_rekening ?? '-' }}
            </td>
        </tr>
        <tr>
            <td class="num">10.</td>
            <td class="label">Jangka Waktu Pelaksanaan</td>
            <td class="colon">:</td>
            <td>{{ $kontrak->jangka_waktu ?? '-' }} {{ isset($terbilangJangkaWaktu) ? '('.ucwords($terbilangJangkaWaktu).')' : '' }} {{ strtolower($kontrak->satuan_waktu ?? 'hari') }} kalender</td>
        </tr>
        <tr>
            <td class="num">11.</td>
            <td class="label">Tanggal Penyelesaian Pekerjaan</td>
            <td class="colon">:</td>
            <td>{{ $kontrak->tanggal_selesai ? \Carbon\Carbon::parse($kontrak->tanggal_selesai)->translatedFormat('d F Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="num">12.</td>
            <td class="label">Jangka Waktu Pemeliharaan</td>
            <td class="colon">:</td>
            <td>{{ (int) ($kontrak->masa_pemeliharaan_hari ?? 0) > 0 ? (int) $kontrak->masa_pemeliharaan_hari . ' hari kalender' : '-' }}</td>
        </tr>
        <tr>
            <td class="num">13.</td>
            <td class="label">Ketentuan Denda</td>
            <td class="colon">:</td>
            <td class="text-justify">{{ $kontrak->ketentuan_denda ?: 'Jika pekerjaan tidak dapat diselesaikan dalam jangka waktu pelaksanaan pekerjaan karena kesalahan atau kelalaian Penyedia maka Penyedia berkewajiban untuk membayar denda kepada PPK sebesar 1/1000 (satu permil) dari nilai SPK (tidak termasuk PPN) untuk setiap hari keterlambatan.' }}</td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <br>
                Pejabat Pembuat Komitmen<br>
                Kantor UPBU Kelas I A.P.T. Pranoto Samarinda<br>
                <br><br><br><br>
                <b><u>{{ $kontrak->nama_ppk ?? '-' }}</u></b><br>
                NIP. {{ $kontrak->nip_ppk ?? '-' }}
            </td>
            <td>
                Samarinda, {{ $kontrak->tanggal_spk ? \Carbon\Carbon::parse($kontrak->tanggal_spk)->translatedFormat('d F Y') : '-' }}<br>
                Penyedia<br>
                <b>{{ strtoupper($vendor->nama_pihak ?? '-') }}</b><br>
                <br><br><br><br>
                <b><u>{{ $vendor->nama_penanggung_jawab ?? '-' }}</u></b><br>
                {{ $vendor->jabatan_penandatangan ?? 'Direktur' }}
            </td>
        </tr>
    </table>
</body>
</html>
