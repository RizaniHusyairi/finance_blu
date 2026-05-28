<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Pengantar - {{ $tagihan->nomor_tagihan }}</title>
    <style>
        @page { margin: 22px 58px 96px 58px; }
        body { font-family: Arial, DejaVu Sans, sans-serif; font-size: 11px; color: #000; line-height: 1.23; }
        .kop { width: 100%; border-bottom: 3px solid #111; padding-bottom: 2px; margin-bottom: 13px; }
        .kop-table { width: 100%; border-collapse: collapse; }
        .kop-table td { vertical-align: top; padding: 0; }
        .kop-logo { width: 74px; text-align: left; }
        .kop-logo img { width: 64px; height: auto; }
        .kop-text { text-align: center; padding-top: 2px; }
        .kop h1, .kop h2, .kop h3 { margin: 0; text-transform: uppercase; font-weight: bold; line-height: 1.04; }
        .kop h1 { font-size: 13px; }
        .kop h2 { font-size: 13px; }
        .kop h3 { font-size: 11px; }
        .kop-contact { width: 86%; margin: 2px auto 0; border-collapse: collapse; font-size: 6.8px; line-height: 1.05; }
        .kop-contact td { padding: 0 5px; vertical-align: top; }
        .kop-contact td + td { border-left: 1px solid #111; }
        .letter-meta { width: 100%; border-collapse: collapse; margin: 6px 0 18px; }
        .letter-meta td { padding: 0 0 1px; vertical-align: top; }
        .date-cell { text-align: left; white-space: nowrap; }
        .recipient { margin: 10px 0 18px 0; }
        .recipient table { border-collapse: collapse; }
        .recipient td { vertical-align: top; padding: 0; }
        .content { text-align: justify; }
        .content p { margin: 0 0 12px 0; text-indent: 36px; }
        .bill-list { width: 100%; border-collapse: collapse; margin: 2px 0 18px 24px; }
        .bill-list td { padding: 1px 0; vertical-align: top; }
        .signature { width: 100%; border-collapse: collapse; margin-top: 4px; page-break-inside: avoid; }
        .signature td { vertical-align: top; }
        .signature-box { text-align: left; }
        .qr-sign { width: 54px; height: 54px; margin: 5px 0 2px 0; }
        .name { font-weight: bold; text-decoration: underline; }
        .small { font-size: 10px; }
        .page-break { page-break-before: always; }
        .nota-title { text-align: center; font-weight: bold; font-size: 14px; margin: 4px 0 10px; }
        .data-title { font-weight: bold; margin-bottom: 1px; }
        .data-table { width: 58%; border-collapse: collapse; margin-bottom: 6px; font-size: 8.5px; line-height: 1.05; }
        .data-table td { padding: 0 2px 1px 0; vertical-align: top; }
        .label { width: 88px; }
        .colon { width: 8px; text-align: center; }
        .detail-caption { font-weight: bold; margin: 4px 0 1px; font-size: 8.5px; }
        .detail-table { width: 100%; border-collapse: collapse; margin: 0 0 6px; font-size: 7.7px; line-height: 1.06; }
        .detail-table th, .detail-table td { border: 1px solid #111; padding: 2px 3px; vertical-align: top; }
        .detail-table th { text-align: center; font-weight: bold; }
        .garbarata-title { font-weight: bold; font-size: 10px; margin: 2px 0 7px; }
        .garbarata-print-table { width: 100%; border-collapse: collapse; font-size: 6.2px; line-height: 1.05; }
        .garbarata-print-table th,
        .garbarata-print-table td { border: 1px solid #111; padding: 1.5px 2px; vertical-align: middle; }
        .garbarata-print-table th { text-align: center; font-weight: bold; }
        .garbarata-print-table .subhead th { padding: 1px 2px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .nowrap { white-space: nowrap; }
        .terbilang-row td { font-weight: bold; font-style: italic; }
        .payment-note { font-size: 8.2px; font-weight: bold; margin-top: 8px; }
        .attention { font-size: 7.2px; margin-top: 12px; width: 62%; line-height: 1.18; }
        .attention ol { margin: 2px 0 0 12px; padding: 0; }
        .nota-signature { width: 100%; border-collapse: collapse; margin-top: 42px; }
        .nota-signature td { vertical-align: top; }
        .tembusan { font-size: 7.5px; line-height: 1.18; padding-top: 38px; }
        .officer-sign { font-size: 7.8px; line-height: 1.12; }
        .document-footer { position: fixed; left: 0; right: 0; bottom: -62px; height: 70px; }
        .footer-table { width: 100%; border-collapse: collapse; }
        .footer-table td { vertical-align: middle; text-align: center; }
        .footer-script { color: #006fbd; font-style: italic; font-size: 13px; }
        .footer-blu img { width: 54px; height: 54px; }
    </style>
</head>
<body>
@php
    $mitraTagihan = $tagihan->mitra ?? $tagihan->mitraLegacy;
    $namaMitra = $mitraTagihan->nama_mitra ?? $mitraTagihan->nama_pihak ?? '-';
    $alamatMitra = $mitraTagihan->alamat ?? '-';
    $tanggalSurat = $tagihan->tanggal_surat_pengantar ?: $tagihan->tanggal_tagihan;
    $perihal = $tagihan->perihal_surat_pengantar ?: 'Tagihan PNBP Jasa';
    $rupiah = fn ($value) => 'Rp' . number_format((float) $value, 0, ',', '.') . ',-';
    $angka = fn ($value) => number_format((float) $value, 0, ',', '.');
    $terbilangTagihan = function_exists('terbilang_rupiah')
        ? terbilang_rupiah((float) $tagihan->total_tagihan)
        : trim(terbilang((float) $tagihan->total_tagihan)) . ' Rupiah';
    $hariJatuhTempo = (int) ($tagihan->jumlah_hari_jatuh_tempo ?: 30);
    $jatuhTempoText = function_exists('terbilang') ? trim(terbilang($hariJatuhTempo)) : (string) $hariJatuhTempo;
    $tanggalTagihan = $tagihan->tanggal_tagihan ?: $tanggalSurat;
    $tanggalJatuhTempo = $tagihan->tanggal_jatuh_tempo
        ?: \Carbon\Carbon::parse($tanggalTagihan)->copy()->addDays($hariJatuhTempo);
    $firstDetail = $tagihan->details->first();
    $jenisPenerimaan = $firstDetail?->layananJasa?->nama_layanan
        ?: $firstDetail?->layananJasa?->nama_lengkap
        ?: $perihal;
    $jenisPenerimaanNota = 'PIUTANG BLU PENYEDIA BARANG DAN JASA LAINNYA - ' . $jenisPenerimaan;
    $npwp = $mitraTagihan->npwp ?? '-';
    $kemenhubLogo = str_replace('\\', '/', public_path('logo/Logo_Kementerian_Perhubungan_Indonesia_(Kemenhub).png'));
    $bluLogo = str_replace('\\', '/', public_path('logo/Logo-BLU-Speed.png'));
    $isSignedFinal = (bool) ($signed ?? false);
    $sealHash = $isSignedFinal ? $tagihan->digitalSealHash() : null;
    $qrFilePath = null;

    if ($isSignedFinal) {
        $verificationUrl = \Illuminate\Support\Facades\URL::signedRoute('public.tagihan-jasa.verify', [
            'id' => $tagihan->id,
            'seal' => $sealHash,
        ]);
        $qrCacheDir = storage_path('app/qr-cache');
        if (! is_dir($qrCacheDir)) {
            @mkdir($qrCacheDir, 0775, true);
        }
        $qrFilePath = $qrCacheDir . DIRECTORY_SEPARATOR . 'tagihan_jasa_verify_' . $tagihan->id . '_' . md5($verificationUrl . $sealHash) . '.svg';
        if (! file_exists($qrFilePath)) {
            $qrSvg = (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(260)
                ->margin(1)
                ->errorCorrection('M')
                ->generate($verificationUrl);
            file_put_contents($qrFilePath, $qrSvg);
        }
        $qrFilePath = str_replace('\\', '/', $qrFilePath);
    }

    $garbarataDetails = $tagihan->details->filter(function ($detail) {
        $payload = is_array($detail->calculation_payload) ? $detail->calculation_payload : [];
        $rows = $payload['rows'] ?? ($payload['inputs']['rows'] ?? []);

        return ($payload['rule'] ?? null) === 'GARBARATA_DETAIL' && is_array($rows) && count($rows) > 0;
    });
@endphp

    <div class="document-footer">
        <table class="footer-table">
            <tr>
                <td width="42%"></td>
                <td width="30%" class="footer-script">Transform to Excellent</td>
                <td width="18%" class="footer-blu">
                    @if(is_file($bluLogo))
                        <img src="{{ $bluLogo }}" alt="BLU">
                    @endif
                </td>
                <td width="10%"></td>
            </tr>
        </table>
    </div>

    <div class="kop">
        <table class="kop-table">
            <tr>
                <td class="kop-logo">
                    @if(is_file($kemenhubLogo))
                        <img src="{{ $kemenhubLogo }}" alt="Kemenhub">
                    @endif
                </td>
                <td class="kop-text">
                    <h1>Kementerian Perhubungan</h1>
                    <h2>Direktorat Jenderal Perhubungan Udara</h2>
                    <h2>Badan Layanan Umum</h2>
                    <h3>Kantor Unit Penyelenggara Bandar Udara Kelas I</h3>
                    <h3>Aji Pangeran Tumenggung Pranoto - Samarinda</h3>
                    <table class="kop-contact">
                        <tr>
                            <td width="46%">Jl. Poros Samarinda - Bontang, Kel. Sungai Siring, Samarinda - Kalimantan Timur</td>
                            <td width="18%">TELP. (0541) 2831593</td>
                            <td width="18%">FAX : (0541) 743786</td>
                            <td width="18%">EMAIL : mail.aptpranotoairport@gmail.com</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <table class="letter-meta">
        <tr>
            <td width="12%">Nomor</td>
            <td width="3%">:</td>
            <td width="58%">{{ $tagihan->nomor_surat_pengantar ?: '-' }}</td>
            <td width="27%" class="date-cell">Samarinda, {{ \Carbon\Carbon::parse($tanggalSurat)->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td>Klasifikasi</td>
            <td>:</td>
            <td colspan="2">Segera</td>
        </tr>
        <tr>
            <td>Lampiran</td>
            <td>:</td>
            <td colspan="2">1 (Satu) Berkas</td>
        </tr>
        <tr>
            <td>Hal</td>
            <td>:</td>
            <td colspan="2">{{ $perihal }}</td>
        </tr>
    </table>

    <div class="recipient">
        <table>
            <tr>
                <td width="35">Yth.</td>
                <td>{{ $namaMitra }}<br>{{ $alamatMitra }}</td>
            </tr>
        </table>
    </div>

    <div class="content">
        <p>
            Berdasarkan Nota Tagihan PNBP Nomor : {{ $tagihan->nomor_tagihan }}.
            Bersama ini terlampir disampaikan tagihan:
        </p>

        <table class="bill-list">
            @foreach($tagihan->details as $detail)
                @php
                    $layanan = $detail->layananJasa;
                    $uraian = $detail->keterangan
                        ?: ($layanan->nama_lengkap ?? $layanan->nama_layanan ?? 'Tagihan PNBP Jasa');
                @endphp
                <tr>
                    <td width="18">-</td>
                    <td>{{ $uraian }}</td>
                    <td width="16">:</td>
                    <td width="116"><strong>{{ $rupiah($detail->subtotal) }}</strong></td>
                </tr>
            @endforeach
        </table>

        <p>
            Sehubungan hal tersebut di atas, dimohon untuk dapat melakukan pembayaran tepat waktu
            @if($tagihan->nomor_va)
                ke nomor Virtual Account : <strong>{{ $tagihan->nomor_va }}</strong> melalui PT Bank Tabungan Negara,
            @else
                melalui kanal pembayaran yang akan ditetapkan,
            @endif
            guna menghindari denda keterlambatan.
        </p>

        <p>
            Jatuh tempo tagihan adalah {{ $hariJatuhTempo }} ({{ $jatuhTempoText }}) hari
            sesuai nota tagihan sehingga apabila pada tanggal tersebut tagihan belum dibayar maka akan dikenakan denda
            sebesar 2% dari total tagihan.
        </p>

        <p>Demikian disampaikan, atas perhatian dan kerja samanya diucapkan terima kasih.</p>
    </div>

    <table class="signature">
        <tr>
            <td width="58%">
            </td>
            <td width="42%" class="signature-box">
                {{ $tagihan->pejabat_penandatangan_jabatan ?: 'Kepala Badan Layanan Umum' }}<br>
                Kantor Unit Penyelenggara Bandar Udara<br>
                Kelas I Aji Pangeran Tumenggung Pranoto - Samarinda
                <br>
                @if($isSignedFinal && $qrFilePath)
                    <img src="{{ $qrFilePath }}" alt="QR Verifikasi" class="qr-sign">
                    <br>
                @else
                    <br><br><br><br>
                @endif
                <span class="name">{{ $tagihan->pejabat_penandatangan_nama ?: '................................' }}</span><br>
                NIP. {{ $tagihan->pejabat_penandatangan_nip ?: '................................' }}
            </td>
        </tr>
    </table>

    <div class="page-break"></div>

    <div class="kop">
        <table class="kop-table">
            <tr>
                <td class="kop-logo">
                    @if(is_file($kemenhubLogo))
                        <img src="{{ $kemenhubLogo }}" alt="Kemenhub">
                    @endif
                </td>
                <td class="kop-text">
                    <h1>Kementerian Perhubungan</h1>
                    <h2>Direktorat Jenderal Perhubungan Udara</h2>
                    <h2>Badan Layanan Umum</h2>
                    <h3>Kantor Unit Penyelenggara Bandar Udara Kelas I</h3>
                    <h3>Aji Pangeran Tumenggung Pranoto - Samarinda</h3>
                    <table class="kop-contact">
                        <tr>
                            <td width="46%">Jl. Poros Samarinda - Bontang, Kel. Sungai Siring, Samarinda - Kalimantan Timur</td>
                            <td width="18%">TELP. (0541) 2831593</td>
                            <td width="18%">FAX : (0541) 743786</td>
                            <td width="18%">EMAIL : mail.aptpranotoairport@gmail.com</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <div class="nota-title">NOTA TAGIHAN PNBP</div>

    <div class="data-title">Data Tagihan</div>
    <table class="data-table">
        <tr>
            <td class="label">Nomor Tagihan</td>
            <td class="colon">:</td>
            <td>{{ $tagihan->nomor_tagihan }}</td>
        </tr>
        <tr>
            <td class="label">Nama Wajib Bayar</td>
            <td class="colon">:</td>
            <td>{{ strtoupper($namaMitra) }}</td>
        </tr>
        <tr>
            <td class="label">Nomor NPWP</td>
            <td class="colon">:</td>
            <td>{{ $npwp }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Tagihan</td>
            <td class="colon">:</td>
            <td>{{ \Carbon\Carbon::parse($tanggalTagihan)->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Jatuh Tempo</td>
            <td class="colon">:</td>
            <td>{{ \Carbon\Carbon::parse($tanggalJatuhTempo)->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Total Tagihan</td>
            <td class="colon">:</td>
            <td><strong>Rp&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $angka($tagihan->total_tagihan) }}</strong></td>
        </tr>
    </table>

    <div class="detail-caption">Detail Tagihan</div>
    <table class="detail-table">
        <thead>
            <tr>
                <th width="16%">Jenis Penerimaan</th>
                <th width="9%">Akun</th>
                <th width="10%">Tarif</th>
                <th width="8%">Volume</th>
                <th width="10%">Satuan</th>
                <th width="13%">Jumlah</th>
                <th width="10%">Denda</th>
                <th width="13%">Total</th>
                <th width="11%">Ket</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tagihan->details as $detail)
                @php
                    $layanan = $detail->layananJasa;
                    $satuan = $layanan?->satuan ?: '-';
                    $kodeAkun = $detail->kode_akun ?: ($layanan?->kode_pembayaran_lengkap ?? $layanan?->kode_akun ?? '-');
                    $ket = $detail->keterangan ?: ($layanan?->nama_lengkap ?? $layanan?->nama_layanan ?? '-');
                @endphp
                <tr>
                    <td><strong>{{ $jenisPenerimaanNota }}</strong></td>
                    <td class="text-center">{{ $kodeAkun }}</td>
                    <td class="text-right nowrap">Rp&nbsp;{{ $angka($detail->harga_satuan) }}</td>
                    <td class="text-center">{{ rtrim(rtrim(number_format((float) $detail->qty, 2, ',', '.'), '0'), ',') }}</td>
                    <td class="text-center">{{ $satuan }}</td>
                    <td class="text-right nowrap">Rp&nbsp;{{ $angka($detail->subtotal) }}</td>
                    <td class="text-right nowrap">Rp&nbsp;-</td>
                    <td class="text-right nowrap">Rp&nbsp;{{ $angka($detail->subtotal) }}</td>
                    <td>{{ $ket }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="6" class="text-center"><strong>Jumlah Tagihan</strong></td>
                <td class="text-right nowrap"><strong>Rp&nbsp;-</strong></td>
                <td class="text-right nowrap"><strong>Rp&nbsp;{{ $angka($tagihan->total_tagihan) }}</strong></td>
                <td></td>
            </tr>
            <tr class="terbilang-row">
                <td colspan="2">Terbilang :</td>
                <td colspan="7">{{ ucwords($terbilangTagihan) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="payment-note">Pembayaran melalui <em>Virtual Account</em> dengan nomor : {{ $tagihan->nomor_va ?: '-' }}</div>

    <table class="nota-signature">
        <tr>
            <td width="58%" class="tembusan">
                <strong>Tembusan :</strong><br>
                a. Lembar 1 untuk Wajib Bayar;<br>
                b. Lembar 2 untuk Bendahara Penerima;<br>
                c. Lembar 3 untuk Petugas Akuntansi.
            </td>
            <td width="42%" class="text-center">
                <div class="officer-sign">
                a.n. Kepala Seksi<br>
                Pelayanan dan Kerjasama<br>
                Petugas Operasional
                <br><br><br><br>
                <strong>{{ $tagihan->creator?->pegawai?->nama_lengkap ?? $tagihan->creator?->name ?? '-' }}</strong><br>
                @if($tagihan->creator?->pegawai?->nip)
                    NIP. {{ $tagihan->creator?->pegawai?->nip }}
                @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="attention">
        <strong>Perhatian :</strong>
        <ol>
            <li>Pembayaran hanya dapat dilakukan sebelum tanggal jatuh tempo. Jika tanggal jatuh tempo telah tercapai, anda akan dikenakan denda 2%.</li>
            <li>Jatuh Tempo Tagihan Ke-1 Jasa adalah {{ $hariJatuhTempo }} Hari, Jatuh Tempo Tagihan Ke-2 dst adalah {{ $hariJatuhTempo }} Hari.</li>
            <li>Kurs yang digunakan pada saat tagihan ini dibuat adalah kurs tengah Bank Indonesia pada saat transaksi.</li>
            <li>Apabila sudah melakukan pembayaran harap menyampaikan bukti pembayaran atau bukti setor ke Bendahara Penerima.</li>
        </ol>
    </div>

    @foreach($garbarataDetails as $detail)
        @php
            $payload = is_array($detail->calculation_payload) ? $detail->calculation_payload : [];
            $garbarataRows = $payload['rows'] ?? ($payload['inputs']['rows'] ?? []);
            $garbarataCaption = $detail->keterangan
                ?: ($detail->layananJasa?->nama_lengkap ?? $detail->layananJasa?->nama_layanan ?? 'Pelayanan Jasa Penggunaan Garbarata');
        @endphp
        <div class="page-break"></div>

        <div class="garbarata-title">Rincian: {{ $garbarataCaption }}</div>
        <table class="garbarata-print-table">
            <thead>
                <tr>
                    <th rowspan="2" width="4%">No</th>
                    <th rowspan="2" width="8%">Tanggal</th>
                    <th rowspan="2" width="8%">Reg</th>
                    <th colspan="2" width="12%">Flight Number</th>
                    <th rowspan="2" width="11%">Route</th>
                    <th colspan="2" width="12%">Waktu (LT)</th>
                    <th rowspan="2" width="8%">Type Pesawat</th>
                    <th rowspan="2" width="8%">Bobot Pesawat<br>(Ton)</th>
                    <th rowspan="2" width="10%">Jasa Pemakaian<br>Garbarata</th>
                    <th rowspan="2" width="7%">Waktu</th>
                    <th rowspan="2" width="7%">Rentang Pemakaian<br>(Per 2 Jam)</th>
                    <th rowspan="2" width="9%">Total</th>
                </tr>
                <tr class="subhead">
                    <th>ARR</th>
                    <th>DEP</th>
                    <th>Docking</th>
                    <th>Undocking</th>
                </tr>
            </thead>
            <tbody>
                @foreach($garbarataRows as $row)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="text-center">{{ $row['tanggal'] ?? '' }}</td>
                        <td class="text-center">{{ $row['reg'] ?? '' }}</td>
                        <td class="text-center">{{ $row['flight_arr'] ?? '' }}</td>
                        <td class="text-center">{{ $row['flight_dep'] ?? '' }}</td>
                        <td class="text-center">{{ $row['route'] ?? '' }}</td>
                        <td class="text-center">{{ $row['docking'] ?? '' }}</td>
                        <td class="text-center">{{ $row['undocking'] ?? '' }}</td>
                        <td class="text-center">{{ $row['type_pesawat'] ?? '' }}</td>
                        <td class="text-center">{{ rtrim(rtrim(number_format((float) ($row['bobot_ton'] ?? 0), 2, ',', '.'), '0'), ',') }}</td>
                        <td class="text-right nowrap">{{ $angka($row['jasa_pemakaian_garbarata'] ?? $detail->harga_satuan) }}</td>
                        <td class="text-center">{{ $row['waktu'] ?? '00:00' }}</td>
                        <td class="text-center">{{ $row['rentang_pemakaian'] ?? 0 }}</td>
                        <td class="text-right nowrap">{{ $angka($row['total'] ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
