<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Pengantar - {{ $tagihan->nomor_tagihan }}</title>
    <style>
        @page { margin: 32px 42px 36px 42px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; line-height: 1.45; }
        .brand { font-size: 9px; color: #1d4ed8; font-style: italic; margin-bottom: 3px; }
        .kop { text-align: center; border-bottom: 3px solid #111; padding-bottom: 7px; margin-bottom: 18px; }
        .kop h1, .kop h2, .kop h3 { margin: 0; text-transform: uppercase; font-weight: bold; }
        .kop h1 { font-size: 13px; }
        .kop h2 { font-size: 12px; }
        .kop h3 { font-size: 11px; }
        .kop p { margin: 2px 0 0; font-size: 9.5px; }
        .letter-meta { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .letter-meta td { padding: 1px 0; vertical-align: top; }
        .date-cell { text-align: right; white-space: nowrap; }
        .recipient { margin: 12px 0 18px 0; }
        .content { text-align: justify; }
        .content p { margin: 0 0 11px 0; }
        .bill-list { margin: 8px 0 12px 26px; padding: 0; }
        .bill-list li { margin-bottom: 4px; }
        .signature { width: 100%; border-collapse: collapse; margin-top: 22px; page-break-inside: avoid; }
        .signature td { vertical-align: top; }
        .signature-box { text-align: left; }
        .qr-sign { width: 76px; height: 76px; margin: 10px 0 6px 0; }
        .qr-verify { width: 72px; height: 72px; margin-top: 12px; }
        .verify-note { font-size: 8.5px; color: #444; margin-top: 3px; line-height: 1.25; }
        .name { font-weight: bold; text-decoration: underline; }
        .small { font-size: 10px; }
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
    $isSignedFinal = ($signed ?? false)
        || $tagihan->status_dokumen_pengantar === 'SUDAH_DITANDATANGANI'
        || ! empty($tagihan->file_surat_pengantar_final);
    $sealHash = $tagihan->digitalSealHash();
    $qrFilePath = null;

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
@endphp

    <div class="brand">Transform to Excellence</div>
    <div class="kop">
        <h1>Kementerian Perhubungan</h1>
        <h2>Direktorat Jenderal Perhubungan Udara</h2>
        <h2>Badan Layanan Umum</h2>
        <h3>Kantor Unit Penyelenggara Bandar Udara Kelas I</h3>
        <h3>Aji Pangeran Tumenggung Pranoto - Samarinda</h3>
        <p>Jl. Poros Samarinda - Bontang, Kel. Sungai Siring, Samarinda - Kalimantan Timur</p>
        <p>Telp. (0541) 2831593 | Email: mail.aptpranotoairport@gmail.com</p>
    </div>

    <table class="letter-meta">
        <tr>
            <td width="12%">Nomor</td>
            <td width="3%">:</td>
            <td width="50%">{{ $tagihan->nomor_surat_pengantar ?: '-' }}</td>
            <td width="35%" class="date-cell">Samarinda, {{ \Carbon\Carbon::parse($tanggalSurat)->translatedFormat('d F Y') }}</td>
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
        Yth. {{ $namaMitra }}<br>
        {{ $alamatMitra }}
    </div>

    <div class="content">
        <p>
            Berdasarkan Nota Tagihan PNBP Nomor: {{ $tagihan->nomor_tagihan }}.
            Bersama ini terlampir disampaikan tagihan:
        </p>

        <ul class="bill-list">
            @foreach($tagihan->details as $detail)
                @php
                    $layanan = $detail->layananJasa;
                    $uraian = $detail->keterangan
                        ?: ($layanan->nama_lengkap ?? $layanan->nama_layanan ?? 'Tagihan PNBP Jasa');
                @endphp
                <li>{{ $uraian }} : <strong>{{ $rupiah($detail->subtotal) }}</strong></li>
            @endforeach
        </ul>

        <p>
            Sehubungan hal tersebut di atas, dimohon untuk dapat melakukan pembayaran tepat waktu
            @if($tagihan->nomor_va)
                ke nomor Virtual Account: <strong>{{ $tagihan->nomor_va }}</strong> melalui PT Bank Tabungan Negara,
            @else
                melalui kanal pembayaran yang akan ditetapkan,
            @endif
            guna menghindari denda keterlambatan.
        </p>

        <p>
            Jatuh tempo tagihan adalah {{ $tagihan->jumlah_hari_jatuh_tempo ?: 30 }} ({{ $tagihan->jumlah_hari_jatuh_tempo ?: 30 }}) hari
            sesuai nota tagihan sehingga apabila pada tanggal tersebut tagihan belum dibayar maka akan dikenakan denda
            sebesar 2% per hari dari total tagihan.
        </p>

        <p>Demikian disampaikan, atas perhatian dan kerja samanya diucapkan terima kasih.</p>
    </div>

    <table class="signature">
        <tr>
            <td width="58%">
                @if(! $isSignedFinal && $qrFilePath)
                    <img src="{{ $qrFilePath }}" alt="QR Verifikasi" class="qr-verify">
                    <div class="verify-note">Verifikasi keaslian dokumen</div>
                @endif
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
</body>
</html>
