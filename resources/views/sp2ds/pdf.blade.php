<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SP2D BLU - {{ $sp2d->nomor_sp2d }}</title>
    <style>
        @page { margin: 30px 40px; }
        body { font-family: "Arial", sans-serif; font-size: 11px; margin: 0; padding: 0; }
        
        .box-container {
            width: 100%;
            border: 1px solid black;
            padding: 15px;
            box-sizing: border-box;
        }

        .table-no-border { width: 100%; border-collapse: collapse; border: none; }
        .table-no-border td { border: none; padding: 3px 5px; vertical-align: top; }
        
        .table-info { width: 100%; border-collapse: collapse; border: 1px solid black; margin-top: 15px; margin-bottom: 20px;}
        .table-info td { border: 1px solid black; padding: 6px 10px; vertical-align: top; width: 50%; }
        
        .header-left { text-align: left; }
        .header-left p { margin: 2px 0; font-weight: bold; }
        
        .header-right { text-align: center; vertical-align: middle; }
        .header-right h3 { margin: 0; font-size: 14px; font-weight: bold; text-decoration: underline; }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        
        .amount-box { margin-top: 10px; border: 1px solid black; padding: 8px 12px; font-weight: bold; display: inline-block; min-width: 150px; text-align: center; font-size: 13px; }
        .terbilang-box { margin-top: 10px; border: 2px solid black; padding: 10px 15px; text-align: center; font-weight: bold; font-size: 11px; }

        .sig-table { width: 100%; border-collapse: collapse; border: none; margin-top: 30px; }
        .sig-table td { border: none; padding: 5px; text-align: center; vertical-align: top; width: 50%; }
        .sig-title { margin-bottom: 70px; }
    </style>
</head>
<body>
@php
    $activityQrFilePath = \App\Support\DocumentTte::activityQrFilePath(\App\Support\DocumentTte::tagihanIdFor($sp2d));
    $tteQrFilePath = \App\Support\DocumentTte::tteQrFilePath($sp2d);
@endphp

<div class="box-container">
    {{-- HEADER --}}
    <table class="table-no-border" style="margin-bottom: 10px;">
        <tr>
            <td class="header-left" style="width: 50%;">
                <p>KEMENTERIAN PERHUBUNGAN</p>
                <p>DIREKTORAT JENDERAL PERHUBUNGAN UDARA</p>
                <p>BADAN LAYANAN UMUM</p>
                <p>KANTOR UPBU AJI PANGERAN TUMENGGUNG PRANOTO</p>
            </td>
            <td class="header-right" style="width: 50%;">
                <h3>SURAT PERINTAH PENCAIRAN DANA BLU</h3>
            </td>
        </tr>
    </table>

    {{-- INFO BOXES (SPM vs SP2D) --}}
    <table class="table-info">
        <tr>
            <td>
                <strong>Dari SPM:</strong><br>
                <table class="table-no-border" style="margin-top: 5px;">
                    <tr><td style="width: 60px; padding-left: 0;">Nomor</td><td>: {{ $spm?->nomor_spm ?? '-' }}</td></tr>
                    <tr><td style="padding-left: 0;">Tanggal</td><td>: {{ $spm?->tanggal_spm ? \Carbon\Carbon::parse($spm->tanggal_spm)->locale('id')->isoFormat('D MMMM Y') : '-' }}</td></tr>
                </table>
            </td>
            <td>
                <strong>Kantor UPBU APT PRANOTO:</strong><br>
                <table class="table-no-border" style="margin-top: 5px;">
                    <tr><td style="width: 90px; padding-left: 0;">Tanggal SP2D</td><td>: {{ $sp2d->tanggal_sp2d ? \Carbon\Carbon::parse($sp2d->tanggal_sp2d)->locale('id')->isoFormat('D MMMM Y') : '-' }}</td></tr>
                    <tr><td style="padding-left: 0;">Nomor SP2D</td><td>: <strong>{{ $sp2d->nomor_sp2d ?? '-' }}</strong></td></tr>
                    <tr><td style="padding-left: 0;">Tahun Anggaran</td><td>: {{ $spp?->tahun_anggaran ?? date('Y') }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- PAYMENT INSTRUCTION --}}
    <p>Bank Indonesia / PT Bank Tabungan Negara (Persero) Tbk Cabang Samarinda agar memindahbukukan pembayaran atas beban Beban BLU (Belanja Barang) dari Rekening Nomor 2001302885742 sebesar:</p>
    
    <div class="amount-box">Rp {{ number_format($nominalSp2d, 0, ',', '.') }}</div>
    
    <div class="terbilang-box">*** {{ strtoupper($terbilang) }} ***</div>

    {{-- RECIPIENT INFO --}}
    <p style="margin-top: 20px; font-weight: bold;">Kepada:</p>
    <table class="table-no-border" style="margin-left: 10px;">
        <tr><td style="width: 150px;">Nama Penerima (Vendor)</td><td>: &nbsp;<strong>{{ $vendor?->nama_pihak ?? '-' }}</strong></td></tr>
        <tr><td>Alamat</td><td>: &nbsp;{{ $vendor?->alamat ?? '-' }}</td></tr>
        <tr><td>Bank Tujuan</td><td>: &nbsp;{{ $rekening?->nama_bank ?? '-' }}</td></tr>
        <tr><td>Nomor Rekening</td><td>: &nbsp;<strong>{{ $rekening?->nomor_rekening ?? '-' }}</strong></td></tr>
        <tr><td>NPWP</td><td>: &nbsp;{{ $vendor?->npwp ?? '-' }}</td></tr>
        <tr><td>Untuk Keperluan (Uraian)</td><td>: &nbsp;{{ $npi?->catatan ?? $kontrak?->nama_pekerjaan ?? '-' }}</td></tr>
    </table>

    {{-- SIGNATURES --}}
    <table class="sig-table">
        <tr>
            <td>
                <div class="sig-title">Pejabat Pembuat Komitmen</div>
                <div><span style="text-decoration: underline; font-weight: bold;">{{ strtoupper($ppk->name ?? 'PPK BLU') }}</span></div>
                <div>NIP {{ $ppk->nip ?? '-' }}</div>
            </td>
            <td>
                <div style="margin-bottom: 5px;">Samarinda, {{ $sp2d->tanggal_sp2d ? \Carbon\Carbon::parse($sp2d->tanggal_sp2d)->locale('id')->isoFormat('D MMMM Y') : date('d M Y') }}</div>
                <div style="margin-bottom: 4px;">Bendahara Pengeluaran</div>
                @if($tteQrFilePath)
                    <div style="margin: 4px auto 5px; width: 82px; text-align: center;">
                        <img src="{{ $tteQrFilePath }}" alt="QR TTE SP2D" style="width: 82px; height: 82px;">
                        <div style="font-size: 8px; line-height: 1.2; margin-top: 2px;">QR TTE SP2D</div>
                    </div>
                @else
                    <div style="height: 70px;"></div>
                @endif
                <div><span style="text-decoration: underline; font-weight: bold;">{{ strtoupper($bendaharaPengeluaran->name ?? 'BENDAHARA PENGELUARAN') }}</span></div>
                <div>NIP {{ $bendaharaPengeluaran->nip ?? '-' }}</div>
            </td>
        </tr>
    </table>

    @if(false && $activityQrFilePath)
        <table style="width: 100%; border: none; border-collapse: collapse; margin-top: 25px;">
            <tr>
                <td style="border: none; width: 90px; vertical-align: top; padding: 0;">
                    <img src="{{ $qrFilePath }}" alt="QR Aktivitas Tagihan" style="width: 90px; height: 90px;">
                </td>
                <td style="border: none; vertical-align: top; padding: 4px 0 0 10px; font-size: 9px; color: #333;">
                    <strong>Scan untuk lihat aktivitas tagihan</strong><br>
                    Status verifikasi, SPP, SPM, NPI, hingga SP2D — beserta verifikator di tiap tahap.
                </td>
            </tr>
        </table>
    @endif
</div>

@if($activityQrFilePath)
    <div style="margin-top: 8px; padding: 6px 8px; border: 1px solid #cfd7e3; font-size: 8px; color: #333;">
        <table style="width: 100%; border: none; border-collapse: collapse;">
            <tr>
                <td style="border: none; width: 54px; vertical-align: top; padding: 0;">
                    <img src="{{ $activityQrFilePath }}" alt="QR Aktivitas Tagihan" style="width: 50px; height: 50px;">
                </td>
                <td style="border: none; vertical-align: top; padding: 3px 0 0 7px;">
                    <strong>Scan untuk lihat aktivitas tagihan</strong><br>
                    Status verifikasi, SPP, SPM, NPI, hingga SP2D — beserta verifikator di tiap tahap.
                </td>
            </tr>
        </table>
    </div>
@endif

</body>
</html>
