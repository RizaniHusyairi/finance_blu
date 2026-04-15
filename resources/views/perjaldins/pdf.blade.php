<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nominatif Perjalanan Dinas</title>
    <style>
        @page { margin: 30px 40px; }
        body { font-family: "Arial", sans-serif; font-size: 11px; margin: 0; padding: 0; color: #000; }
        
        .page-break { page-break-after: always; }

        /* KOP SURAT */
        .kop-surat { text-align: center; font-weight: bold; margin-bottom: 5px; }
        .kop-surat span { display: block; }
        .kop-instansi { font-size: 14px; }
        .kop-uk { font-size: 13px; }
        .kop-blu { font-size: 13px; margin: 3px 0; }
        .kop-bandara { font-size: 13px; }
        .kop-alamat { font-size: 9px; font-weight: normal; margin-top: 5px; }
        .separator-thick { border-top: 3px solid black; border-bottom: 1px solid black; height: 1px; margin-bottom: 20px; }

        /* HAL 1 SECTION */
        .hal1-container { width: 100%; max-width: 800px; margin: 0 auto; }
        .judul-doc { text-align: center; font-weight: bold; margin-bottom: 25px; }
        .judul-doc h4 { margin: 0; text-decoration: underline; font-size: 13px; }
        .judul-doc p { margin: 3px 0 0 0; font-size: 12px; }

        .deskripsi { margin-bottom: 15px; font-size: 12px; line-height: 1.5; }
        
        .tbl-summary { width: 100%; border-collapse: collapse; border: 1px solid black; margin-bottom: 20px; }
        .tbl-summary td { padding: 15px 12px; border-bottom: 1px solid #ddd; font-size: 12px; }
        .tbl-summary-total { background-color: #ffeba1; font-weight: bold; border-top: 1px solid black !important; }
        
        .box-terbilang { border: 1px solid black; background-color: #d1ecf1; padding: 15px; margin-bottom: 40px; }
        .box-terbilang p { margin: 0 0 10px 0; font-size: 12px; }
        .box-terbilang h4 { margin: 0; text-align: center; font-size: 13px; font-style: italic; }

        .ttd-box { width: 100%; table-layout: fixed; }
        .ttd-box td { text-align: left; vertical-align: bottom; height: 120px; } /* height for signature space */
        .ttd-kiri { width: 40%; }
        .ttd-tengah { width: 20%; }
        .ttd-kanan { width: 40%; }
        
        /* HAL 2 SECTION */
        .tbl-data { width: 100%; border-collapse: collapse; border: 1px solid black; font-size: 9px; }
        .tbl-data th, .tbl-data td { border: 1px solid black; padding: 4px; text-align: center; vertical-align: middle; }
        .tbl-data th { background-color: #e6dbf2; font-weight: bold; }
        .tbl-data td.text-left { text-align: left; }
        .tbl-data td.text-right { text-align: right; }
        .td-ttd { border-bottom: 1px solid #ccc; height: 20px; width: 45px; }
    </style>
</head>
<body>

@php
    $bulanMap = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $namaBulan = $bulanMap[$tagihan->periode_bulan] ?? '-';
    $tahun = $tagihan->periode_tahun ?? '-';
    $ppkNama = $tagihan->ppk_nama_snapshot ?? '-';
    $ppkNip = $tagihan->ppk_nip_snapshot ?? '-';
    $bendaharaNama = $tagihan->bendahara_pengeluaran_nama_snapshot ?? '-';
    $bendaharaNip = $tagihan->bendahara_pengeluaran_nip_snapshot ?? '-';
    $tglTtd = $tagihan->tanggal_ttd ? \Carbon\Carbon::parse($tagihan->tanggal_ttd)->locale('id')->isoFormat('D MMMM Y') : date('d F Y');
    $kotaTtd = $tagihan->kota_ttd ?? 'Samarinda';

    $terbilangHelper = function_exists('terbilang_rupiah') ? terbilang_rupiah($tagihan->total_bruto) : '-';
@endphp

<!-- HALAMAN 1 -->
<div class="hal1-container">
    <div class="kop-surat">
        <span class="kop-instansi">KEMENTERIAN PERHUBUNGAN</span>
        <span class="kop-instansi">DIREKTORAT JENDERAL PERHUBUNGAN UDARA</span>
        <span class="kop-blu">BADAN LAYANAN UMUM</span>
        <span class="kop-bandara">KANTOR UNIT PENYELENGGARA BANDAR UDARA KELAS I</span>
        <span class="kop-bandara">AJI PANGERAN TUMENGGUNG PRANOTO-SAMARINDA</span>
        <span class="kop-alamat">
            Jl. Poros Samarinda-Bontang, Kel. Sungai Siring, Samarinda-Kalimantan Timur &nbsp;|&nbsp;
            TELP. (0541) 2831593 &nbsp;|&nbsp; 
            FAX : (0541) 743786 &nbsp;|&nbsp; 
            EMAIL : mail.aptpranotoairport@gmail.com
        </span>
    </div>
    <div class="separator-thick"></div>

    <div class="judul-doc">
        <h4>NOMINATIF PERJALANAN DINAS</h4>
        <p>Nomor: {{ $tagihan->nomor_tagihan }}</p>
    </div>

    <div class="deskripsi">
        Berikut daftar pencairan perjalanan dinas pada Badan Layanan Umum Kantor UPBU<br>
        Kelas I A.P.T. Pranoto-Samarinda untuk Bulan {{ $namaBulan }} {{ $tahun }} dengan data perjalanan<br>
        dinas sebagai berikut:
    </div>

    <table class="tbl-summary">
        <tr>
            <td style="width: 70%;">1. Daftar Nominatif Perjalanan Dinas</td>
            <td style="width: 30%; text-align: right; font-weight: bold;">Rp. {{ number_format($tagihan->total_bruto, 0, ',', '.') }},-</td>
        </tr>
        <tr class="tbl-summary-total">
            <td>Total Sebesar</td>
            <td style="text-align: right;">Rp. {{ number_format($tagihan->total_bruto, 0, ',', '.') }},-</td>
        </tr>
    </table>

    <div class="box-terbilang">
        <p>Terbilang:</p>
        <h4>== {{ ucwords($terbilangHelper) }} Rupiah ==</h4>
    </div>

    <table class="ttd-box">
        <tr>
            <td class="ttd-kiri">
                <p>Mengetahui,</p>
                <p>Pejabat Pembuat Komitmen</p>
            </td>
            <td class="ttd-tengah"></td>
            <td class="ttd-kanan">
                <p>{{ $kotaTtd }}, {{ $tglTtd }}</p>
                <p>Bendahara Pengeluaran</p>
            </td>
        </tr>
        <tr>
            <td class="ttd-kiri">
                <p style="text-decoration: underline; font-weight: bold; margin-bottom: 2px;">{{ $ppkNama }}</p>
                <p style="margin-top: 0;">NIP. {{ $ppkNip }}</p>
            </td>
            <td class="ttd-tengah"></td>
            <td class="ttd-kanan">
                <p style="text-decoration: underline; font-weight: bold; margin-bottom: 2px;">{{ $bendaharaNama }}</p>
                <p style="margin-top: 0;">NIP. {{ $bendaharaNip }}</p>
            </td>
        </tr>
    </table>
</div>

<div class="page-break"></div>

<!-- HALAMAN 2 -->
<div class="judul-doc" style="margin-top: 20px;">
    <h4>DAFTAR NOMINATIF PEMBAYARAN PERJALANAN DINAS</h4>
    <p>Nomor: {{ $tagihan->nomor_tagihan }}</p>
</div>

<table class="tbl-data">
    <thead>
        <tr>
            <th rowspan="2">NO</th>
            <th rowspan="2">NAMA</th>
            <th rowspan="2">NO SPT</th>
            <th rowspan="2">NO SPPD</th>
            <th rowspan="2">TUJUAN</th>
            <th rowspan="2">TANGGAL<br>BERANGKAT</th>
            <th rowspan="2">LAMA<br>PERJALANAN<br>DINAS</th>
            <th colspan="5">BIAYA PERJALANAN DINAS</th>
            <th rowspan="2">JUMLAH</th>
            <th rowspan="2">REKENING</th>
            <th rowspan="2">TTD</th>
        </tr>
        <tr>
            <th>TIKET</th>
            <th>TRANSPORT</th>
            <th>PENGINAPAN</th>
            <th>UANG<br>HARIAN</th>
            <th>UANG<br>REPRESENTASI</th>
        </tr>
    </thead>
    <tbody>
        @php
            $gtTiket = 0; $gtTransport = 0; $gtPenginapan = 0; $gtHarian = 0; $gtRepresentasi = 0; $gtJumlah = 0;
        @endphp

        @foreach($details as $idx => $dt)
            @php
                $jumlahRow = $dt->biaya_tiket + $dt->biaya_transport + $dt->biaya_penginapan + $dt->uang_harian + $dt->uang_representasi;
                
                $gtTiket += $dt->biaya_tiket;
                $gtTransport += $dt->biaya_transport;
                $gtPenginapan += $dt->biaya_penginapan;
                $gtHarian += $dt->uang_harian;
                $gtRepresentasi += $dt->uang_representasi;
                $gtJumlah += $jumlahRow;
                
                $nama = $dt->nama_pegawai ?? ($dt->pegawai?->nama_lengkap ?? '-');
                $nip = $dt->nip ?? ($dt->pegawai?->nip ?? '-');
            @endphp
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td class="text-left" style="white-space:nowrap;">
                    <strong>{{ $nama }}</strong><br>
                    <span style="font-size: 8px; color: #555;">{{ $nip }}</span>
                </td>
                <td>{{ $dt->no_spt }}</td>
                <td>{{ $dt->no_sppd }}</td>
                <td style="white-space:nowrap;">
                    {{ collect([$dt->provinsi?->provinsi, $dt->tujuan])->filter()->join('<br>') }}
                </td>
                <td style="white-space:nowrap;">
                    {{ $dt->tgl_berangkat ? \Carbon\Carbon::parse($dt->tgl_berangkat)->format('d F Y') : '-' }}
                </td>
                <td>{{ $dt->lama_hari }}</td>
                <td class="text-right">{{ number_format($dt->biaya_tiket, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($dt->biaya_transport, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($dt->biaya_penginapan, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($dt->uang_harian, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($dt->uang_representasi, 0, ',', '.') }}</td>
                <td class="text-right" style="font-weight: bold;">{{ number_format($jumlahRow, 0, ',', '.') }}</td>
                <td style="font-size: 8px;">
                    {{ $dt->rekening ?: '-' }}<br>
                    @if($dt->nama_bank)({{ $dt->nama_bank }})@endif
                </td>
                <td style="padding: 0;">
                    <!-- Simulasi kotak ttd di dlm sel -->
                    <div class="td-ttd" style="text-align:left; font-size:8px; padding:2px;">{{ $idx + 1 }}.</div>
                </td>
            </tr>
        @endforeach
        
        <tr style="font-weight: bold; background-color: #f2f2f2;">
            <td colspan="7">JUMLAH</td>
            <td class="text-right">{{ number_format($gtTiket, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($gtTransport, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($gtPenginapan, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($gtHarian, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($gtRepresentasi, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($gtJumlah, 0, ',', '.') }}</td>
            <td colspan="2"></td>
        </tr>
    </tbody>
</table>

<table class="ttd-box" style="margin-top: 30px;">
    <tr>
        <td class="ttd-kiri">
            <p>Mengetahui,</p>
            <p>Pejabat Pembuat Komitmen</p>
        </td>
        <td class="ttd-tengah"></td>
        <td class="ttd-kanan">
            <p>{{ $kotaTtd }}, {{ $tglTtd }}</p>
            <p>Bendahara Pengeluaran</p>
        </td>
    </tr>
    <tr>
        <td class="ttd-kiri">
            <p style="text-decoration: underline; font-weight: bold; margin-bottom: 2px;">{{ $ppkNama }}</p>
            <p style="margin-top: 0;">NIP. {{ $ppkNip }}</p>
        </td>
        <td class="ttd-tengah"></td>
        <td class="ttd-kanan">
            <p style="text-decoration: underline; font-weight: bold; margin-bottom: 2px;">{{ $bendaharaNama }}</p>
            <p style="margin-top: 0;">NIP. {{ $bendaharaNip }}</p>
        </td>
    </tr>
</table>

</body>
</html>
