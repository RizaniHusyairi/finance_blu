<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Honorarium</title>
    <style>
        @page { margin: 25px 30px; }
        body { font-family: "Arial", sans-serif; font-size: 10px; margin: 0; padding: 0; color: #000; }

        /* KOP SURAT */
        .kop-wrapper { width: 100%; }
        .kop-left { float: left; width: 55%; text-align: left; font-weight: bold; }
        .kop-left span { display: block; }
        .kop-instansi { font-size: 11px; }
        .kop-uk { font-size: 10px; }

        .kop-right { float: right; width: 45%; text-align: right; font-size: 9px; }
        .kop-right span { display: block; }
        .kop-right-title { font-weight: bold; font-size: 10px; }

        .clearfix::after { content: ""; display: table; clear: both; }

        .separator { border-top: 2px solid black; margin: 10px 0 15px 0; clear: both; }

        /* TABLE */
        .tbl-data { width: 100%; border-collapse: collapse; border: 1px solid black; font-size: 8px; }
        .tbl-data th, .tbl-data td { border: 1px solid black; padding: 3px 4px; text-align: center; vertical-align: middle; }
        .tbl-data th { background-color: #e8e8e8; font-weight: bold; font-size: 8px; }
        .tbl-data td.text-left { text-align: left; }
        .tbl-data td.text-right { text-align: right; }
        .td-ttd { height: 25px; width: 40px; }

        /* TTD */
        .ttd-box { width: 100%; table-layout: fixed; margin-top: 25px; }
        .ttd-box td { vertical-align: top; }
        .ttd-kiri { width: 40%; text-align: left; }
        .ttd-tengah { width: 20%; }
        .ttd-kanan { width: 40%; text-align: right; }
        .ttd-nama { text-decoration: underline; font-weight: bold; margin-bottom: 2px; }
        .ttd-sig-space { height: 60px; }
        .qr-tte { width: 80px; height: 80px; display: block; margin: 4px 0; }
        .qr-label { font-size: 7px; color: #555; font-style: italic; margin-top: 2px; }
    </style>
</head>
<body>

@php
    $ppkNama = $tagihan->ppk_nama_snapshot ?? '-';
    $ppkNip = $tagihan->ppk_nip_snapshot ?? '-';
    $bendaharaNama = $tagihan->bendahara_pengeluaran_nama_snapshot ?? '-';
    $bendaharaNip = $tagihan->bendahara_pengeluaran_nip_snapshot ?? '-';

    $bulanMap = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $bulanNow = $bulanMap[(int)date('n')] ?? date('F');
    $tahunNow = date('Y');

    // Uraian deskripsi dari tagihan
    $uraianKegiatan = $tagihan->deskripsi ?? '-';

    // Akun DIPA (jika ada relasi)
    $akunDipa = '';
    if ($tagihan->dipaRevisionItem && $tagihan->dipaRevisionItem->coa) {
        $akunDipa = $tagihan->dipaRevisionItem->coa->kode_akun . ' ' . $tagihan->dipaRevisionItem->coa->nama_akun;
    }
@endphp

<!-- KOP -->
<div class="kop-wrapper clearfix">
    <div class="kop-left">
        <span class="kop-instansi">KEMENTERIAN PERHUBUNGAN DIREKTORAT</span>
        <span class="kop-instansi">JENDERAL PERHUBUNGAN UDARA KANTOR</span>
        <span class="kop-uk">UPBU KELAS I A.P.T PRANOTO SAMARINDA</span>
    </div>
    <div class="kop-right">
        <span class="kop-right-title">{{ strtoupper($uraianKegiatan) }}</span>
        @if($akunDipa)
            <span>HONOR OUTPUT KEGIATAN AKUN : {{ $akunDipa }}</span>
        @endif
    </div>
</div>
<div class="separator"></div>

<!-- TABEL DATA -->
<table class="tbl-data">
    <thead>
        <tr>
            <th style="width: 25px;">No</th>
            <th>NAMA</th>
            <th>NRP</th>
            <th>PANGKAT/KORP</th>
            <th>JABATAN</th>
            <th>HONOR<br>KEGIATAN</th>
            <th>PPH</th>
            <th>JUMLAH</th>
            <th>NO REKENING</th>
            <th>Jenis Bank</th>
            <th>Nama Rekening</th>
            <th>NO HP</th>
            <th style="width: 35px;">TTD</th>
        </tr>
    </thead>
    <tbody>
        @php
            $gtHonor = 0; $gtPph = 0; $gtJumlah = 0;
        @endphp

        @foreach($details as $idx => $dt)
            @php
                $netto = $dt->nilai_honor - $dt->pph;
                $gtHonor += $dt->nilai_honor;
                $gtPph += $dt->pph;
                $gtJumlah += $netto;
            @endphp
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td class="text-left" style="white-space:nowrap;">{{ $dt->nama_personel }}</td>
                <td>{{ $dt->nrp_nip ?: '-' }}</td>
                <td class="text-left">{{ $dt->pangkat_korp ?: '-' }}</td>
                <td class="text-left" style="font-size:7px;">{{ $dt->jabatan ?: '-' }}</td>
                <td class="text-right">{{ number_format($dt->nilai_honor, 0, ',', '.') }}</td>
                <td class="text-right">{{ $dt->pph > 0 ? number_format($dt->pph, 0, ',', '.') : '-' }}</td>
                <td class="text-right" style="font-weight:bold;">{{ number_format($netto, 0, ',', '.') }}</td>
                <td>{{ $dt->rekening ?: '-' }}</td>
                <td>{{ $dt->jenis_bank ?: '-' }}</td>
                <td class="text-left">{{ $dt->nama_rekening ?: '-' }}</td>
                <td>{{ $dt->no_hp ?: '-' }}</td>
                <td class="td-ttd"></td>
            </tr>
        @endforeach

        <tr style="font-weight: bold; background-color: #f2f2f2;">
            <td colspan="5" style="text-align:right;">TOTAL</td>
            <td class="text-right">{{ number_format($gtHonor, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($gtPph, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($gtJumlah, 0, ',', '.') }}</td>
            <td colspan="5"></td>
        </tr>
    </tbody>
</table>

<!-- TTD -->
<table class="ttd-box">
    <tr>
        <td class="ttd-kiri">
            <p style="margin-bottom:3px;">Mengetahui</p>
            <p style="margin-bottom:0;">Pejabat Pembuat Komitmen</p>
            @if(!empty($tteQrFilePath))
                <img src="{{ $tteQrFilePath }}" alt="QR TTE Rekap Honorarium" class="qr-tte">
                <div class="qr-label">Ditandatangani Secara Elektronik</div>
            @endif
        </td>
        <td class="ttd-tengah"></td>
        <td class="ttd-kanan">
            <p style="margin-bottom:3px;">Samarinda, &nbsp; {{ $bulanNow }} {{ $tahunNow }}</p>
            <p style="margin-bottom:0;">Bendahara Pengeluaran</p>
        </td>
    </tr>
    <tr>
        <td class="ttd-kiri" style="padding-top:{{ !empty($tteQrFilePath) ? '6px' : '60px' }};">
            <p class="ttd-nama">{{ strtoupper($ppkNama) }}</p>
            <p style="margin-top:0;">NIP. {{ $ppkNip }}</p>
        </td>
        <td class="ttd-tengah"></td>
        <td class="ttd-kanan" style="padding-top:60px;">
            <p class="ttd-nama">{{ strtoupper($bendaharaNama) }}</p>
            <p style="margin-top:0;">NIP. {{ $bendaharaNip }}</p>
        </td>
    </tr>
</table>

</body>
</html>
