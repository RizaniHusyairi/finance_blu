<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Pengantar - {{ $tagihan->nomor_tagihan }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; line-height: 1.6; }
        .kop { text-align: center; border-bottom: 3px solid #111; padding-bottom: 10px; margin-bottom: 24px; }
        .kop h2 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .kop p { margin: 2px 0; font-size: 11px; }
        .meta { width: 100%; margin-bottom: 20px; }
        .meta td { vertical-align: top; padding: 2px 4px; }
        .content { text-align: justify; }
        .summary { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .summary th, .summary td { border: 1px solid #555; padding: 7px; }
        .summary th { background: #f0f0f0; }
        .signature { margin-top: 36px; width: 100%; }
        .signature td { vertical-align: top; }
    </style>
</head>
<body>
@php
    $mitraTagihan = $tagihan->mitra ?? $tagihan->mitraLegacy;
@endphp
    <div class="kop">
        <h2>Unit Penyelenggara Bandar Udara / BLU</h2>
        <p>Surat Pengantar Tagihan PNBP Jasa</p>
    </div>

    <table class="meta">
        <tr>
            <td width="12%">Nomor</td>
            <td width="3%">:</td>
            <td width="45%">{{ $tagihan->nomor_surat_pengantar ?: '-' }}</td>
            <td width="12%">Tanggal</td>
            <td width="3%">:</td>
            <td width="25%">{{ $tagihan->tanggal_surat_pengantar ? \Carbon\Carbon::parse($tagihan->tanggal_surat_pengantar)->format('d F Y') : \Carbon\Carbon::parse($tagihan->tanggal_tagihan)->format('d F Y') }}</td>
        </tr>
        <tr>
            <td>Perihal</td>
            <td>:</td>
            <td colspan="4">{{ $tagihan->perihal_surat_pengantar ?: 'Penyampaian Tagihan PNBP Jasa' }}</td>
        </tr>
        <tr>
            <td>Lampiran</td>
            <td>:</td>
            <td colspan="4">1 berkas Nota Tagihan</td>
        </tr>
    </table>

    <p>Kepada Yth.</p>
    <p>
        <strong>{{ $mitraTagihan->nama_pihak ?? '-' }}</strong><br>
        {{ $mitraTagihan->alamat ?? '-' }}
    </p>

    <div class="content">
        <p>Dengan hormat,</p>
        <p>
            Bersama ini kami sampaikan tagihan PNBP atas layanan jasa yang telah digunakan oleh
            {{ $mitraTagihan->nama_pihak ?? 'mitra' }} dengan rincian ringkas sebagai berikut:
        </p>
    </div>

    <table class="summary">
        <tr>
            <th width="35%">Nomor Tagihan</th>
            <td>{{ $tagihan->nomor_tagihan }}</td>
        </tr>
        <tr>
            <th>Nomor Dokumen Dasar</th>
            <td>{{ $tagihan->nomor_kontrak ?: '-' }}</td>
        </tr>
        <tr>
            <th>Tanggal Tagihan</th>
            <td>{{ \Carbon\Carbon::parse($tagihan->tanggal_tagihan)->format('d F Y') }}</td>
        </tr>
        <tr>
            <th>Total Tagihan</th>
            <td><strong>Rp {{ number_format((float) $tagihan->total_tagihan, 0, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <th>Nomor Virtual Account</th>
            <td>{{ $tagihan->nomor_va ?: 'Belum tersedia' }}</td>
        </tr>
    </table>

    <div class="content">
        <p>
            Sehubungan dengan hal tersebut, dimohon kepada pihak mitra untuk melakukan pembayaran
            sesuai ketentuan yang tercantum pada Nota Tagihan. Nota Tagihan menjadi bagian tidak
            terpisahkan dari surat pengantar ini.
        </p>
        <p>Demikian disampaikan. Atas perhatian dan kerja sama Saudara, kami ucapkan terima kasih.</p>
    </div>

    <table class="signature">
        <tr>
            <td width="55%"></td>
            <td width="45%">
                {{ $tagihan->pejabat_penandatangan_jabatan ?: 'Kepala Kantor UPBU' }},
                <br><br><br><br>
                <strong>{{ $tagihan->pejabat_penandatangan_nama ?: '................................' }}</strong><br>
                NIP. {{ $tagihan->pejabat_penandatangan_nip ?: '................................' }}
            </td>
        </tr>
    </table>
</body>
</html>
