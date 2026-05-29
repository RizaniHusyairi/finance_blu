<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SPP BLU - {{ $spp->nomor_spp }}</title>
    <style>
        @page { margin: 30px 40px; }
        body { font-family: "Arial", sans-serif; font-size: 11px; margin: 0; padding: 0; }
        
        .table-main { width: 100%; border-collapse: collapse; border: 2px solid black; }
        .table-main td { border: 1px solid black; padding: 5px; vertical-align: top; }
        
        .header-title { text-align: center; margin-bottom: 5px; }
        .header-title h4, .header-title h3, .header-title h5 { margin: 2px 0; }
        
        /* Utility classes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        .border-0 { border: none !important; }
        .border-bottom-0 { border-bottom: none !important; }
        .border-top-0 { border-top: none !important; }
        .border-right-0 { border-right: none !important; }
        .border-left-0 { border-left: none !important; }

        .signature-box { float: right; width: 350px; text-align: left; padding: 10px; }
    </style>
</head>
<body>
@php
    $pdfReference = $pdfReference ?? [
        'primary_label' => 'No. Tagihan',
        'primary_value' => '-',
        'primary_date_label' => 'Tgl. Tagihan',
        'primary_date_value' => '-',
        'secondary_label' => null,
        'secondary_value' => null,
        'secondary_date_label' => null,
        'secondary_date_value' => null,
    ];
    $hasSecondaryReference = !empty($pdfReference['secondary_label']);
    $dipaInfo = $dipaInfo ?? [
        'nomor' => $spp->nomor_dipa ?? '-',
        'tanggal' => $spp->tanggal_dipa ? \Carbon\Carbon::parse($spp->tanggal_dipa)->locale('id')->isoFormat('D MMMM Y') : '-',
    ];
    $supplierInfo = $supplierInfo ?? [
        'nama_supplier' => 'PARA PEGAWAI KANTOR UPBU AJI PANGERAN TUMENGGUNG PRANOTO',
        'bank_pos' => 'Terlampir',
        'npwp' => 'Terlampir',
        'rekening' => 'Terlampir',
        'alamat' => "Jl. Poros Samarinda - Bontang, Kel. Sunga Siring,\nSamarinda-Kalimantan Timur",
        'nama_rekening' => 'Terlampir',
        'uraian' => $uraianSupplier ?? '-',
    ];

    // DomPDF tidak handle inline <svg> dari simple-qrcode dengan stabil
    // (XML prolog & viewBox-only sizing), jadi kita tulis ke file SVG temp
    // dan reference via <img src="{absolute_path}">.
    $buildQrFilePath = function (string $url, string $filePrefix) {
        $qrCacheDir = storage_path('app/qr-cache');
        if (! is_dir($qrCacheDir)) {
            @mkdir($qrCacheDir, 0775, true);
        }

        $qrFilePath = $qrCacheDir . DIRECTORY_SEPARATOR . $filePrefix . '_' . md5($url) . '.svg';
        if (! file_exists($qrFilePath)) {
            $qrSvg = (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(300)->margin(1)->errorCorrection('M')->generate($url);
            file_put_contents($qrFilePath, $qrSvg);
        }

        return str_replace('\\', '/', $qrFilePath);
    };

    $activityQrFilePath = null;
    $qrTagihanId = $spp->tagihan_id ?? optional($spp->tagihan)->id;
    if ($qrTagihanId) {
        $activityQrUrl = \Illuminate\Support\Facades\URL::signedRoute('public.tagihan.aktivitas', ['id' => $qrTagihanId]);
        $activityQrFilePath = $buildQrFilePath($activityQrUrl, 'tagihan_' . $qrTagihanId);
    }

    $tteQrFilePath = null;
    $isSppTteReady = method_exists($spp, 'isFullyVerifiedForTte') && $spp->isFullyVerifiedForTte();
    if ($isSppTteReady) {
        $tteQrUrl = \Illuminate\Support\Facades\URL::signedRoute('public.spp-tte.show', [
            'id' => $spp->id,
            'hash' => method_exists($spp, 'tteHash') ? $spp->tteHash() : null,
        ]);
        $tteQrFilePath = $buildQrFilePath($tteQrUrl, 'spp_tte_' . $spp->id);
    }

    $cleanSignerValue = function ($value) {
        $value = trim((string) $value);

        return in_array($value, ['', '-', 'NIP', 'NIP.'], true) ? null : $value;
    };

    $penandatanganUser = $spp->ppkVerifikator ?? null;
    $penandatanganPegawai = $penandatanganUser?->pegawai;
    $penandatanganNama = $cleanSignerValue($spp->penandatangan_nama ?? null)
        ?? $cleanSignerValue($penandatanganPegawai?->nama_lengkap ?? null)
        ?? $cleanSignerValue($penandatanganUser?->name ?? null)
        ?? '-';
    $penandatanganNip = $cleanSignerValue($spp->penandatangan_nip ?? null)
        ?? $cleanSignerValue($penandatanganPegawai?->nip ?? null)
        ?? '-';
@endphp

    <table class="table-main">
        <!-- HEADER 1 -->
        <tr>
            <td colspan="4" class="text-center">
                <div class="header-title">
                    <h5 class="fw-bold">KEMENTERIAN PERHUBUNGAN</h5>
                    <h5 class="fw-bold">BADAN LAYANAN UMUM</h5>
                    <h5 class="fw-bold">KANTOR UNIT PENYELENGGARA BANDAR UDARA AJI PANGERAN TUMENGGUNG PRANOTO</h5>
                    <br>
                    <h4 class="fw-bold">SURAT PERMINTAAN PEMBAYARAN BLU</h4>
                </div>
                <table style="width: 100%; border: none; margin-top: 10px;">
                    <tr>
                        <td class="border-0 text-left" style="width: 33%;">Nomor : &nbsp; {{ $spp->nomor_spp }}</td>
                        <td class="border-0 text-center" style="width: 33%;">Tanggal : &nbsp; {{ \Carbon\Carbon::parse($spp->tanggal_spp)->locale('id')->isoFormat('DD-MMM-YYYY') }}</td>
                        <td class="border-0 text-right" style="width: 33%;">Hal. 1 dari 1</td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <!-- HEADER 2 (Bendahara) -->
        <tr>
            <td colspan="4" style="padding: 5px;">
                <table style="width: 100%; border: none;">
                    <tr>
                        <td colspan="2" style="border: none; padding: 0;">Bendahara Pengeluaran, Badan Layanan Umum Kantor UPBU Aji Pangeran Tumenggung Pranoto <br><br></td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 0; width: 35%;">Agar melakukan pembayaran tagihan sejumlah</td>
                        <td style="border: none; padding: 0;">Rp{{ number_format($jumlahUang, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- TERBILANG -->
        <tr>
            <td colspan="4" class="text-center fw-bold" style="padding: 15px 5px;">
                **** {{ $terbilang }} ****
            </td>
        </tr>

        <!-- ROW DATA DIPA -->
        <tr>
            <td style="width: 35%; padding: 5px; vertical-align: top;" class="border-bottom-0">
                <table style="width: 100%; border: none; font-size: 11px;">
                    <tr>
                        <td style="border: none; width: 40%; padding: 0;">Tahun Anggaran</td>
                        <td style="border: none; width: 5%; padding: 0;">:</td>
                        <td style="border: none; width: 55%; padding: 0;">{{ $spp->tahun_anggaran }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 30%; padding: 5px; vertical-align: top;" class="border-bottom-0">
                <table style="width: 100%; border: none; font-size: 11px;">
                    <tr>
                        <td style="border: none; width: 35%; padding: 0;">{{ $pdfReference['primary_label'] }}</td>
                        <td style="border: none; width: 5%; padding: 0;">:</td>
                        <td style="border: none; width: 60%; padding: 0;">{{ $pdfReference['primary_value'] }}</td>
                    </tr>
                </table>
            </td>
            <td colspan="2" style="width: 35%; padding: 5px; vertical-align: top;" class="border-bottom-0">
                <table style="width: 100%; border: none; font-size: 11px;">
                    <tr>
                        <td style="border: none; width: 35%; padding: 0;">Jenis Tagihan</td>
                        <td style="border: none; width: 5%; padding: 0;">:</td>
                        <td style="border: none; width: 60%; padding: 0;">{{ $spp->jenis_tagihan }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 5px; vertical-align: top;" class="border-bottom-0 border-top-0">
                <table style="width: 100%; border: none; font-size: 11px;">
                    <tr><td colspan="3" style="border: none; padding: 0;">Dasar Pembayaran</td></tr>
                    <tr><td colspan="3" style="border: none; padding: 0;">DIPA</td></tr>
                    <tr>
                        <td style="border: none; width: 20%; padding: 0;">Nomor</td>
                        <td style="border: none; width: 5%; padding: 0;">:</td>
                        <td style="border: none; width: 75%; padding: 0;">{{ $dipaInfo['nomor'] }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 0;">Tanggal</td>
                        <td style="border: none; padding: 0;">:</td>
                        <td style="border: none; padding: 0;">{{ $dipaInfo['tanggal'] }}</td>
                    </tr>
                </table>
            </td>
            <td style="padding: 5px; vertical-align: top;" class="border-bottom-0 border-top-0">
                <table style="width: 100%; border: none; font-size: 11px;">
                    <tr>
                        <td style="border: none; width: 35%; padding: 0;">{{ $pdfReference['primary_date_label'] }}</td>
                        <td style="border: none; width: 5%; padding: 0;">:</td>
                        <td style="border: none; width: 60%; padding: 0;">{{ $pdfReference['primary_date_value'] }}</td>
                    </tr>
                    @if($hasSecondaryReference)
                        <tr>
                            <td style="border: none; padding: 0;">{{ $pdfReference['secondary_label'] }}</td>
                            <td style="border: none; padding: 0;">:</td>
                            <td style="border: none; padding: 0;">{{ $pdfReference['secondary_value'] }}</td>
                        </tr>
                        <tr>
                            <td colspan="3" style="border: none; padding: 6px 0;"></td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 0;">{{ $pdfReference['secondary_date_label'] }}</td>
                            <td style="border: none; padding: 0;">:</td>
                            <td style="border: none; padding: 0;">{{ $pdfReference['secondary_date_value'] }}</td>
                        </tr>
                    @endif
                </table>
            </td>
            <td colspan="2" style="padding: 5px; vertical-align: top;" class="border-bottom-0 border-top-0">
                <table style="width: 100%; border: none; font-size: 11px;">
                    <tr>
                        <td style="border: none; width: 35%; padding: 0;">Jatuh Tempo</td>
                        <td style="border: none; width: 5%; padding: 0;">:</td>
                        <td style="border: none; width: 60%; padding: 0;">{{ $spp->jatuh_tempo ?? 'Segera' }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 0;">Cara Bayar</td>
                        <td style="border: none; padding: 0;">:</td>
                        <td style="border: none; padding: 0;">{{ $spp->cara_bayar ?? 'SP2D BLU - TRF' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 5px;" class="border-top-0">
                UU NOMOR 17 TAHUN 2025 TENTANG <br>APBN TAHUN ANGGARAN 2026
            </td>
            <td class="border-top-0"></td>
            <td colspan="2" class="border-top-0"></td>
        </tr>

        <!-- PENGELUARAN SECTION -->
        <tr>
            <td colspan="2" class="text-center fw-bold">PENGELUARAN</td>
            <td colspan="2" class="text-center fw-bold">JUMLAH UANG</td>
        </tr>
        <tr>
            <td colspan="2" style="height: 100px;">
                {{ $kodeCoa ?? '-' }}
            </td>
            <td colspan="2" class="text-right">
                {{ number_format($jumlahUang, 2, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td colspan="2" class="text-right border-right-0" style="padding-right: 20px;">Jumlah Pengeluaran</td>
            <td colspan="2" class="text-right">{{ number_format($jumlahUang, 2, ',', '.') }}</td>
        </tr>

        <!-- POTONGAN SECTION -->
        <tr>
            <td colspan="2" class="text-center fw-bold">POTONGAN</td>
            <td colspan="2" class="text-center fw-bold">JUMLAH UANG</td>
        </tr>
        <tr>
            <td colspan="2" style="height: 60px;">
                @foreach(($potonganPajak ?? collect()) as $potongan)
                    {{ $potongan->pajak?->jenis_pajak ?? $potongan->nama_pajak_snapshot ?? $potongan->jenis_potongan }}<br>
                @endforeach
            </td>
            <td colspan="2" class="text-right">
                @foreach(($potonganPajak ?? collect()) as $potongan)
                    {{ number_format($potongan->nominal_potongan, 2, ',', '.') }}<br>
                @endforeach
            </td>
        </tr>
        <tr>
            <td colspan="2" class="text-right border-right-0" style="padding-right: 20px;">Jumlah Potongan</td>
            <td colspan="2" class="text-right">{{ number_format($jumlahPotonganPajak ?? 0, 2, ',', '.') }}</td>
        </tr>

        <!-- TOTAL PEMBAYARAN -->
        <tr>
            <td colspan="2" class="text-center fw-bold">TOTAL PEMBAYARAN</td>
            <td colspan="2" class="text-right fw-bold">{{ number_format($jumlahUang, 2, ',', '.') }}</td>
        </tr>

        <!-- INFO SUPPLIER -->
        <tr>
            <td colspan="4" style="height: 110px; padding: 5px;">
                <table style="width: 100%; border: none; font-size: 11px;">
                    <tr>
                        <td style="border: none; width: 14%; vertical-align: top; padding: 2px;">Nama Supplier</td>
                        <td style="border: none; width: 2%; vertical-align: top; padding: 2px;">:</td>
                        <td style="border: none; width: 44%; vertical-align: top; padding: 2px;">{{ $supplierInfo['nama_supplier'] }}</td>
                        
                        <td style="border: none; width: 12%; vertical-align: top; padding: 2px;">Bank/Pos</td>
                        <td style="border: none; width: 2%; vertical-align: top; padding: 2px;">:</td>
                        <td style="border: none; width: 26%; vertical-align: top; padding: 2px;">{{ $supplierInfo['bank_pos'] }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; vertical-align: top; padding: 2px;">NPWP</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">:</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">{{ $supplierInfo['npwp'] }}</td>
                        
                        <td style="border: none; vertical-align: top; padding: 2px;">Rekening</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">:</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">{{ $supplierInfo['rekening'] }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; vertical-align: top; padding: 2px;">Alamat</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">:</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">{!! nl2br(e($supplierInfo['alamat'])) !!}</td>
                        
                        <td style="border: none; vertical-align: top; padding: 2px;">Nama</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">:</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">{{ $supplierInfo['nama_rekening'] }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px;"></td>
                        <td style="border: none; padding: 2px;"></td>
                        <td style="border: none; padding: 2px;"></td>
                        
                        <td style="border: none; vertical-align: top; padding: 2px;">Uraian</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">:</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">{!! nl2br(e($supplierInfo['uraian'])) !!}</td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- TANDA TANGAN -->
        <tr>
            <td colspan="2" style="font-size: 10px; padding: 10px; width: 60%; vertical-align: top;">
                Semua bukti-bukti pengeluaran yang disahkan Pejabat Pembuat Komitmen telah <br>
                diuji dan dinyatakan memenuhi persyaratan untuk dilakukan pembayaran atas <br>
                beban BLU, selanjutnya bukti-bukti pengeluaran dimaksud disimpan dan <br>
                ditatausahakan oleh Pejabat Pembuat Komitmen <br><br>
                Kebenaran perhitungan dan isi yang tertuang dalam SPP ini menjadi tanggung <br>
                jawab Pejabat Pembuat Komitmen.

            </td>
            <td colspan="2" style="padding: 10px; width: 40%; vertical-align: top; text-align: center;">
                Samarinda, {{ \Carbon\Carbon::parse($spp->tanggal_spp)->locale('id')->isoFormat('D MMMM Y') }} <br>
                A.n. Kuasa Pengguna Anggaran <br>
                Pejabat Pembuat Komitmen <br>
                @if($tteQrFilePath)
                    <div style="margin: 8px auto 5px; width: 86px; text-align: center;">
                        <img src="{{ $tteQrFilePath }}" alt="QR TTE SPP" style="width: 86px; height: 86px;">
                    </div>
                @else
                    <br><br><br><br><br><br>
                @endif
                <span style="text-decoration: underline; font-weight: bold;">{{ strtoupper($penandatanganNama) }}</span> <br>
                NIP {{ $penandatanganNip }}
            </td>
        </tr>

    </table>

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
