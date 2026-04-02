<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>NPI BLU - {{ $npi->nomor_npi }}</title>
    <style>
        @page { margin: 30px 40px; }
        body { font-family: "Arial", sans-serif; font-size: 11px; margin: 0; padding: 0; }

        .table-main { width: 100%; border-collapse: collapse; border: 2px solid black; }
        .table-main td { border: 1px solid black; padding: 6px 8px; vertical-align: top; }

        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .text-left   { text-align: left; }
        .fw-bold     { font-weight: bold; }
        .border-0    { border: none !important; }

        .amount-box {
            border: 1px solid black;
            padding: 6px 10px;
            min-height: 24px;
            display: block;
            font-weight: bold;
        }
        .terbilang-box {
            border: 2px solid black;
            padding: 8px 12px;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }
        .sig-table { width: 100%; border-collapse: collapse; border: none; margin-top: 10px; }
        .sig-table td { border: none; padding: 4px 8px; vertical-align: top; }
    </style>
</head>
<body>

<table class="table-main">

    {{-- HEADER ROW --}}
    <tr>
        {{-- KIRI: Info instansi --}}
        <td style="width: 55%; vertical-align: middle; border-right: 2px solid black;">
            <p style="margin: 2px 0; font-weight: bold;">KEMENTERIAN PERHUBUNGAN</p>
            <p style="margin: 2px 0; font-weight: bold;">DIREKTORAT JENDERAL PERHUBUNGAN UDARA</p>
            <p style="margin: 2px 0; font-weight: bold;">BADAN LAYANAN UMUM</p>
            <p style="margin: 2px 0; font-weight: bold;">KANTOR UPBU AJI PANGERAN TUMENGGUNG PRANOTO</p>
        </td>
        {{-- KANAN: Judul --}}
        <td style="width: 45%; text-align: center; vertical-align: middle;">
            <h3 style="margin: 4px 0; font-size: 14px;">NOTA PEMINDAHBUKUAN INTERNAL</h3>
        </td>
    </tr>

    {{-- INFO ROW --}}
    <tr>
        {{-- KIRI: Info SPM --}}
        <td style="border-right: 2px solid black; padding: 6px 10px;">
            <table style="width: 100%; border: none; border-collapse: collapse;">
                <tr>
                    <td style="border: none; width: 90px; padding: 2px 0;">SPM No.</td>
                    <td style="border: none; padding: 2px 0;">: &nbsp;<strong>{{ $spm->nomor_spm ?? $spp->nomor_spp }}</strong></td>
                </tr>
                <tr>
                    <td style="border: none; padding: 2px 0;">Tanggal</td>
                    <td style="border: none; padding: 2px 0;">: &nbsp;{{ $spm->tanggal_spm ? \Carbon\Carbon::parse($spm->tanggal_spm)->locale('id')->isoFormat('D MMMM Y') : \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y') }}</td>
                </tr>
            </table>
        </td>
        {{-- KANAN: Info NPI --}}
        <td style="padding: 6px 10px;">
            <table style="width: 100%; border: none; border-collapse: collapse;">
                <tr>
                    <td style="border: none; width: 110px; padding: 2px 0;">Dari</td>
                    <td style="border: none; padding: 2px 0;">: &nbsp;Bendahara Pengeluaran</td>
                </tr>
                <tr>
                    <td style="border: none; padding: 2px 0;">Tanggal</td>
                    <td style="border: none; padding: 2px 0;">: &nbsp;{{ $npi->tanggal_npi ? \Carbon\Carbon::parse($npi->tanggal_npi)->locale('id')->isoFormat('D MMMM Y') : \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y') }}</td>
                </tr>
                <tr>
                    <td style="border: none; padding: 2px 0;">Nomor</td>
                    <td style="border: none; padding: 2px 0;">: &nbsp;<strong>{{ $npi->nomor_npi }}</strong></td>
                </tr>
                <tr>
                    <td style="border: none; padding: 2px 0;">Tahun Anggaran</td>
                    <td style="border: none; padding: 2px 0;">: &nbsp;<strong>{{ $spp->tahun_anggaran ?? date('Y') }}</strong></td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- BODY --}}
    <tr>
        <td colspan="2" style="padding: 14px 12px; line-height: 1.8;">
            <p style="margin: 0 0 6px 0;">Bendahara Penerimaan Kantor UPBU Aji Pangeran Tumenggung Pranoto</p>
            <p style="margin: 0 0 6px 0;">
                hendaklah memindahbukukan dari RPL 046 BLU UPBU APT PRANOTO UNTUK OPS Penerimaan<br>
                ke RPL 046 BLU UPBU APT PRANOTO UTK OPS PENGELUARAN,<br>
                PT Bank Tabungan Negara (Persero) Tbk Cabang Samarinda<br>
                Rekening Nomor 2001302885742 sesuai dengan
            </p>

            {{-- Jumlah Uang Box --}}
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <tr>
                    <td style="border: none; width: 120px; padding: 4px 0; vertical-align: middle;">Jumlah Uang</td>
                    <td style="border: none; padding: 4px 0; vertical-align: middle;">:</td>
                    <td style="padding: 4px 8px; border: 1px solid black; font-weight: bold; font-size: 12px; vertical-align: middle;">
                        Rp{{ number_format($jumlahUang, 0, ',', '.') }}
                    </td>
                </tr>
            </table>

            {{-- Terbilang Box --}}
            <div class="terbilang-box" style="margin-top: 12px;">
                ***{{ strtoupper($terbilang) }}***
            </div>

            {{-- Keterangan Keperluan --}}
            <p style="margin: 14px 0 0 0;">
                untuk keperluan pembayaran SPM Nomor &nbsp;&nbsp; <strong>{{ $spm->nomor_spm ?? $spp->nomor_spp }}</strong><br>
                SPM Tanggal {{ $spm->tanggal_spm ? \Carbon\Carbon::parse($spm->tanggal_spm)->locale('id')->isoFormat('D MMMM Y') : date('d F Y') }}
            </p>

            {{-- TANDA TANGAN --}}
            <table class="sig-table" style="margin-top: 30px;">
                <tr>
                    {{-- Kiri: Bendahara Penerimaan --}}
                    <td style="width: 33%; vertical-align: top; text-align: center; padding: 0 10px;">
                        <p style="margin: 0 0 60px 0;">Bendahara Penerimaan</p>
                        <p style="margin: 0;"><span style="text-decoration: underline; font-weight: bold;">{{ strtoupper($penandatanganPenerimaan ?? 'BENDAHARA PENERIMAAN') }}</span></p>
                        <p style="margin: 2px 0;">NIP {{ $nipPenerimaan ?? '-' }}</p>
                    </td>

                    {{-- Tengah: Kosong --}}
                    <td style="width: 34%;"></td>

                    {{-- Kanan: PPK --}}
                    <td style="width: 33%; vertical-align: top; text-align: center; padding: 0 10px;">
                        <p style="margin: 0 0 4px 0;">Samarinda, {{ $npi->tanggal_npi ? \Carbon\Carbon::parse($npi->tanggal_npi)->locale('id')->isoFormat('D MMMM Y') : \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y') }}</p>
                        <p style="margin: 0 0 60px 0;">Pejabat Pembuat Komitmen</p>
                        <p style="margin: 0;"><span style="text-decoration: underline; font-weight: bold;">{{ strtoupper($ppk->name ?? 'PPK') }}</span></p>
                        <p style="margin: 2px 0;">NIP -</p>
                    </td>
                </tr>

                {{-- Bawah Kiri: Bendahara Pengeluaran --}}
                <tr>
                    <td colspan="3" style="padding: 20px 10px 0 10px;">
                        <table style="width: 100%; border: none; border-collapse: collapse;">
                            <tr>
                                <td style="border: none; width: 33%; text-align: center; vertical-align: top;">
                                    <p style="margin: 0 0 60px 0;">Bendahara Pengeluaran</p>
                                    <p style="margin: 0;"><span style="text-decoration: underline; font-weight: bold;">{{ strtoupper($penandatanganPengeluaran ?? 'BENDAHARA PENGELUARAN') }}</span></p>
                                    <p style="margin: 2px 0;">NIP {{ $nipPengeluaran ?? '-' }}</p>
                                </td>
                                <td style="border: none; width: 67%;"></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

</table>

</body>
</html>
