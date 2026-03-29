<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SPM BLU - {{ $spp->nomor_spm }}</title>
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

    <table class="table-main">
        <!-- HEADER 1 -->
        <tr>
            <td colspan="4" class="text-center">
                <div class="header-title">
                    <h5 class="fw-bold">KEMENTERIAN PERHUBUNGAN</h5>
                    <h5 class="fw-bold">BADAN LAYANAN UMUM</h5>
                    <h5 class="fw-bold">KANTOR UNIT PENYELENGGARA BANDAR UDARA AJI PANGERAN TUMENGGUNG PRANOTO</h5>
                    <br>
                    <h4 class="fw-bold">SURAT PERINTAH MEMBAYAR BLU</h4>
                </div>
                <table style="width: 100%; border: none; margin-top: 10px;">
                    <tr>
                        <td class="border-0 text-left" style="width: 33%;">Nomor : &nbsp; {{ $spp->nomor_spm }}</td>
                        <td class="border-0 text-center" style="width: 33%;">Tanggal : &nbsp; {{ $spp->tanggal_spm ? \Carbon\Carbon::parse($spp->tanggal_spm)->locale('id')->isoFormat('DD-MMM-YYYY') : date('d-M-Y') }}</td>
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
                **** {{ strtoupper($terbilang) }} ****
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
                        <td style="border: none; width: 35%; padding: 0;">No. Kontrak</td>
                        <td style="border: none; width: 5%; padding: 0;"></td>
                        <td style="border: none; width: 60%; padding: 0;"></td>
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
                        <td style="border: none; width: 75%; padding: 0;">{{ $spp->nomor_dipa }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 0;">Tanggal</td>
                        <td style="border: none; padding: 0;">:</td>
                        <td style="border: none; padding: 0;">{{ \Carbon\Carbon::parse($spp->tanggal_dipa)->locale('id')->isoFormat('D MMMM Y') }}</td>
                    </tr>
                </table>
            </td>
            <td style="padding: 5px; vertical-align: top;" class="border-bottom-0 border-top-0">
                <table style="width: 100%; border: none; font-size: 11px;">
                    <tr>
                        <td style="border: none; width: 35%; padding: 0;">Tgl. Kontrak</td>
                        <td style="border: none; width: 5%; padding: 0;">:</td>
                        <td style="border: none; width: 60%; padding: 0;"></td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 0;">No. BAST</td>
                        <td style="border: none; padding: 0;">:</td>
                        <td style="border: none; padding: 0;">{{ $sppable->no_bast ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="border: none; padding: 6px 0;"></td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 0;">Tgl. BAST</td>
                        <td style="border: none; padding: 0;">:</td>
                        <td style="border: none; padding: 0;">{{ $sppable->no_bast ? \Carbon\Carbon::parse($spp->tanggal_spp)->format('d/m/Y') : '' }}</td>
                    </tr>
                </table>
            </td>
            <td colspan="2" style="padding: 5px; vertical-align: top;" class="border-bottom-0 border-top-0">
                <table style="width: 100%; border: none; font-size: 11px;">
                    <tr>
                        <td style="border: none; width: 35%; padding: 0;">Jatuh Tempo</td>
                        <td style="border: none; width: 5%; padding: 0;">:</td>
                        <td style="border: none; width: 60%; padding: 0;">{{ $spp->jatuh_tempo }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 0;">Cara Bayar</td>
                        <td style="border: none; padding: 0;">:</td>
                        <td style="border: none; padding: 0;">{{ $spp->cara_bayar }}</td>
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
                {{ $spp->akun_mak }}
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
                <!-- Potongan kosong (seperti di format) -->
            </td>
            <td colspan="2" class="text-right">
                
            </td>
        </tr>
        <tr>
            <td colspan="2" class="text-right border-right-0" style="padding-right: 20px;">Jumlah Potongan</td>
            <td colspan="2" class="text-right">0,00</td>
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
                        <td style="border: none; width: 44%; vertical-align: top; padding: 2px;">PARA PEGAWAI KANTOR UPBU AJI PANGERAN TUMENGGUNG PRANOTO</td>
                        
                        <td style="border: none; width: 12%; vertical-align: top; padding: 2px;">Bank/Pos</td>
                        <td style="border: none; width: 2%; vertical-align: top; padding: 2px;">:</td>
                        <td style="border: none; width: 26%; vertical-align: top; padding: 2px;">Terlampir</td>
                    </tr>
                    <tr>
                        <td style="border: none; vertical-align: top; padding: 2px;">NPWP</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">:</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">Terlampir</td>
                        
                        <td style="border: none; vertical-align: top; padding: 2px;">Rekening</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">:</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">Terlampir</td>
                    </tr>
                    <tr>
                        <td style="border: none; vertical-align: top; padding: 2px;">Alamat</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">:</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">Jl. Poros Samarinda - Bontang, Kel. Sunga Siring,<br>Samarinda-Kalimantan Timur</td>
                        
                        <td style="border: none; vertical-align: top; padding: 2px;">Nama</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">:</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">Terlampir</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px;"></td>
                        <td style="border: none; padding: 2px;"></td>
                        <td style="border: none; padding: 2px;"></td>
                        
                        <td style="border: none; vertical-align: top; padding: 2px;">Uraian</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">:</td>
                        <td style="border: none; vertical-align: top; padding: 2px;">{{ $uraianSupplier }}</td>
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
                ditatausahakan oleh Pejabat Penandatangan SPM. <br><br>
                Kebenaran perhitungan dan isi yang tertuang dalam SPM ini menjadi tanggung <br>
                jawab Pejabat Penandatangan SPM.
            </td>
            <td colspan="2" style="padding: 10px; width: 40%; vertical-align: top; text-align: center;">
                Samarinda, {{ $spp->tanggal_spm ? \Carbon\Carbon::parse($spp->tanggal_spm)->locale('id')->isoFormat('D MMMM Y') : \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y') }} <br>
                A.n. Kuasa Pengguna Anggaran <br>
                Pejabat Penandatangan SPM <br><br><br><br><br><br>
                <span style="text-decoration: underline; font-weight: bold;">{{ strtoupper($spp->penandatangan_spm_nama) }}</span> <br>
                NIP {{ $spp->penandatangan_spm_nip }}
            </td>
        </tr>

    </table>

</body>
</html>
