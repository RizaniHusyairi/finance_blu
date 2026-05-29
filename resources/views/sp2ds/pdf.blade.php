<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SP2D BLU - {{ $sp2d->nomor_sp2d }}</title>
    <style>
        @page { margin: 28px 36px; }
        body { font-family: "Arial", sans-serif; font-size: 11px; margin: 0; padding: 0; color: #000; }

        table { border-collapse: collapse; }

        .sp2d-main { border: 2px solid #000; }
        .sp2d-main > tbody > tr > td { border: 1px solid #000; vertical-align: top; }

        .cell-pad { padding: 6px 10px; }

        .instansi p { margin: 1px 0; font-weight: bold; font-size: 11px; line-height: 1.35; }
        .title-doc { text-align: center; font-weight: bold; font-size: 13px; }

        .inner { width: 100%; border: none; }
        .inner td { border: none; padding: 1px 0; vertical-align: top; }

        .lbl { width: 88px; }
        .lbl-wide { width: 110px; }
        .sep { width: 8px; }

        .amount-box {
            border: 1px solid #000;
            padding: 7px 12px;
            text-align: center;
            font-weight: bold;
            font-size: 13px;
        }
        .terbilang-box {
            border: 1px solid #000;
            padding: 9px 12px;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            margin-top: 12px;
        }

        .sig-table { width: 100%; border: none; margin-top: 6px; }
        .sig-table td { border: none; vertical-align: top; text-align: center; width: 50%; padding: 0 10px; }
        .sig-name { text-decoration: underline; font-weight: bold; }
    </style>
</head>
<body>
@php
    use Carbon\Carbon;
    $fmtTgl = fn($d) => $d ? Carbon::parse($d)->locale('id')->isoFormat('D MMMM Y') : '-';
    $activityQrFilePath = \App\Support\DocumentTte::activityQrFilePath(\App\Support\DocumentTte::tagihanIdFor($sp2d));
    $tteQrPpk = \App\Support\DocumentTte::tteQrFilePath($sp2d, 'ppk');
    $tteQrPengeluaran = \App\Support\DocumentTte::tteQrFilePath($sp2d, 'pengeluaran');
@endphp

<table class="sp2d-main">
    {{-- ===== HEADER ROW ===== --}}
    <tr>
        {{-- KIRI: Instansi --}}
        <td class="cell-pad instansi" style=" text-align: center;">
            <p>KEMENTERIAN PERHUBUNGAN</p>
            <p>DIREKTORAT JENDERAL PERHUBUNGAN UDARA</p>
            <p>BADAN LAYANAN UMUM</p>
            <p>KANTOR UPBU AJI PANGERAN TUMENGGUNG PRANOTO</p>
        </td>
        {{-- KANAN: NSS + Judul --}}
        <td style=" padding: 0;">
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="border: none; border-bottom: 0px; padding: 4px 10px; font-size: 11px;">NSS :</td>
                </tr>
                <tr>
                    <td class="title-doc" style="border: none; padding: 12px 10px;">SURAT PERINTAH PENCAIRAN DANA BLU</td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- ===== INFO ROW (SPM | Dari/Tanggal/Nomor/TA) ===== --}}
    <tr>
        <td class="cell-pad" style="width: 48%;">
            <table class="inner">
                <tr>
                    <td class="lbl">SPM No.</td><td class="sep">:</td>
                    <td><strong>{{ $spm?->nomor_spm ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <td class="lbl">Tanggal</td><td class="sep">:</td>
                    <td>{{ $fmtTgl($spm?->tanggal_spm) }}</td>
                </tr>
            </table>
        </td>
        <td class="cell-pad" style="width: 52%;">
            <table class="inner">
                <tr>
                    <td class="lbl-wide">Dari</td><td class="sep">:</td>
                    <td>Kantor UPBU Aji Pangeran Tumenggung Pranoto</td>
                </tr>
                <tr>
                    <td class="lbl-wide">Tanggal</td><td class="sep">:</td>
                    <td>{{ $fmtTgl($sp2d->tanggal_sp2d) }}</td>
                </tr>
                <tr>
                    <td class="lbl-wide">Nomor</td><td class="sep">:</td>
                    <td><strong>{{ $sp2d->nomor_sp2d ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <td class="lbl-wide">Tahun Anggaran</td><td class="sep">:</td>
                    <td>{{ $tahunAnggaran }}</td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- ===== KLASIFIKASI + INSTRUKSI + NOMINAL + TERBILANG ===== --}}
    <tr>
        <td colspan="2" class="cell-pad" style="padding: 12px 14px;">
            <table class="inner" style="margin-bottom: 4px;">
                <tr>
                    <td class="lbl-wide">KLASIFIKASI</td><td class="sep"></td>
                    <td>{{ $klasifikasi }}</td>
                </tr>
                <tr>
                    <td class="lbl-wide">Bank/Pos</td><td class="sep"></td>
                    <td>PT BANK TABUNGAN NEGARA (PERSERO) TBK CABANG SAMARINDA</td>
                </tr>
            </table>

            <p style="margin: 6px 0 0 0; line-height: 1.5;">
                Hendaklah mencairkan/memindahbukukan dari RPL 046 BLU UPBU APT PRANOTO UTK OPS PENGELUARAN<br>
                Rekening Nomor 2001302885742 sesuai dengan
            </p>

            <table style="width: 100%; border: none; margin-top: 14px;">
                <tr>
                    <td style="border: none; width: 130px; vertical-align: middle;">{{ $caraBayar }}</td>
                    <td style="border: none; width: 110px; vertical-align: middle;">Uang Sebesar</td>
                    <td style="border: none; vertical-align: middle;">
                        <div class="amount-box">Rp{{ number_format($nominalSp2d, 0, ',', '.') }}</div>
                    </td>
                </tr>
            </table>

            <div class="terbilang-box">***{{ strtoupper($terbilang) }}***</div>
        </td>
    </tr>

    {{-- ===== KEPADA + DETAIL PENERIMA + TTD ===== --}}
    <tr>
        <td colspan="2" class="cell-pad" style="padding: 12px 14px;">
            <table class="inner">
                <tr>
                    <td class="lbl-wide">Kepada</td><td class="sep">:</td>
                    <td style="font-weight: bold;">{{ strtoupper($penerimaNama) }}</td>
                </tr>
                <tr>
                    <td class="lbl-wide">Alamat</td><td class="sep">:</td>
                    <td>{{ $penerimaAlamat }}</td>
                </tr>
                <tr><td colspan="3" style="height: 8px;"></td></tr>
                <tr>
                    <td class="lbl-wide">NPWP</td><td class="sep">:</td>
                    <td>{{ $penerimaNpwp }}</td>
                </tr>
                <tr>
                    <td class="lbl-wide">No. Rek.</td><td class="sep">:</td>
                    <td>{{ $penerimaNoRek }}</td>
                </tr>
                <tr>
                    <td class="lbl-wide">Bank/Pos</td><td class="sep">:</td>
                    <td>{{ $penerimaBank }}</td>
                </tr>
                <tr>
                    <td class="lbl-wide">Nama Rek.</td><td class="sep">:</td>
                    <td>{{ $penerimaNamaRek }}</td>
                </tr>
                <tr>
                    <td class="lbl-wide">Uraian</td><td class="sep">:</td>
                    <td>{{ $uraian }}</td>
                </tr>
            </table>

            {{-- TANDA TANGAN --}}
            <table class="sig-table" style="margin-top:50px;">
                <tr>
                    {{-- KIRI: PPK --}}
                    <td>
                        <div style="margin-bottom: 4px;">PEJABAT PEMBUAT KOMITMEN</div>
                        @if($tteQrPpk)
                            <div style="margin: 4px auto 4px; width: 70px; text-align: center;">
                                <img src="{{ $tteQrPpk }}" alt="QR TTE SP2D PPK" style="width: 70px; height: 70px;">
                            </div>
                        @else
                            <div style="height: 64px;"></div>
                        @endif
                        <div class="sig-name">{{ strtoupper($ppk?->name ?? 'PEJABAT PEMBUAT KOMITMEN') }}</div>
                        <div>NIP {{ $ppkNip }}</div>
                    </td>
                    {{-- KANAN: Bendahara Pengeluaran --}}
                    <td>
                        <div style="margin-bottom: 2px;">Samarinda, {{ $fmtTgl($sp2d->tanggal_sp2d) }}</div>
                        <div style="margin-bottom: 4px;">BENDAHARA PENGELUARAN</div>
                        @if($tteQrPengeluaran)
                            <div style="margin: 4px auto 4px; width: 70px; text-align: center;">
                                <img src="{{ $tteQrPengeluaran }}" alt="QR TTE SP2D Bendahara Pengeluaran" style="width: 70px; height: 70px;">
                            </div>
                        @else
                            <div style="height: 64px;"></div>
                        @endif
                        <div class="sig-name">{{ strtoupper($bendaharaPengeluaran?->name ?? 'BENDAHARA PENGELUARAN') }}</div>
                        <div>NIP {{ $bendaharaNip }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

@if($activityQrFilePath)
    <div style="margin-top: 8px; padding: 6px 8px; border: 1px solid #cfd7e3; font-size: 8px; color: #333;">
        <table style="width: 100%; border: none;">
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
