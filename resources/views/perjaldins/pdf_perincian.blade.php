<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Perincian Biaya Perjalanan Dinas</title>
    <style>
        @page { margin: 30px 40px; }
        body { font-family: "Arial", sans-serif; font-size: 11px; margin: 0; padding: 0; color: #000; }

        .judul-doc { text-align: center; font-weight: bold; margin-bottom: 22px; }
        .judul-doc h4 { margin: 0; font-size: 12px; }

        .tbl-header { margin-bottom: 14px; font-size: 11px; }
        .tbl-header td { padding: 1px 4px 1px 0; vertical-align: top; }

        .tbl-rincian { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .tbl-rincian th, .tbl-rincian td { border: 1px solid #000; padding: 6px 8px; font-size: 11px; vertical-align: top; }
        .tbl-rincian th { text-align: center; font-weight: bold; }
        .td-no { width: 5%; text-align: center; }
        .td-jumlah { width: 14%; text-align: right; white-space: nowrap; }
        .td-ket { width: 28%; font-size: 9.5px; font-style: italic; text-align: center; vertical-align: middle; line-height: 1.45; }
        .row-jumlah td { font-weight: bold; }
        .row-jumlah .label { text-align: center; padding: 10px 8px; }
        .row-terbilang td { font-size: 11px; }
        .row-terbilang .isi { font-weight: bold; font-style: italic; }

        .rincian-sub { font-size: 10px; }

        .ttd-box { width: 100%; table-layout: fixed; margin-top: 28px; }
        .ttd-box td { vertical-align: top; font-size: 11px; }
        .ttd-kiri { width: 48%; padding-left: 30px; }
        .ttd-kanan { width: 52%; padding-left: 120px; }
        .ttd-space { height: 64px; }
        .ttd-nama { text-decoration: underline; font-weight: bold; margin: 0 0 2px 0; }
        .ttd-nip { margin: 0; }

        .tbl-bayar { border-collapse: collapse; font-size: 11px; }
        .tbl-bayar td { padding: 1px 6px 1px 0; vertical-align: top; }
    </style>
</head>
<body>

@php
    $tipeMap = [
        'luar_kota' => 'Luar Kota',
        'dalam_kota_lebih_8_jam' => 'Dalam Kota > 8 Jam',
        'diklat' => 'Diklat',
    ];

    $nama = $detail->nama_pegawai ?? $detail->pegawai?->nama_lengkap ?? '-';
    $nip = $detail->nip ?? $detail->pegawai?->nip ?? '-';
    $provinsiNama = $detail->provinsi?->provinsi;
    $tipeLabel = $tipeMap[$detail->tipe_perjalanan ?? ''] ?? null;
    $lamaHari = (int) ($detail->lama_hari ?? 0);

    // Tarif OH (orang-hari) dari master per provinsi sesuai tipe; fallback hitung balik.
    $tarifOh = match ($detail->tipe_perjalanan) {
        'luar_kota' => (float) ($detail->provinsi?->luar_kota ?? 0),
        'dalam_kota_lebih_8_jam' => (float) ($detail->provinsi?->dalam_kota_lebih_8_jam ?? 0),
        'diklat' => (float) ($detail->provinsi?->diklat ?? 0),
        default => 0.0,
    };
    $uangHarian = (float) ($detail->uang_harian ?? 0);
    if ($tarifOh <= 0 && $lamaHari > 0) {
        $tarifOh = $uangHarian / $lamaHari;
    }

    // Baris biaya: hanya komponen yang nilainya terisi.
    $rows = [];
    if ((float) ($detail->biaya_tiket ?? 0) > 0) {
        $rows[] = ['label' => 'Tiket', 'sub' => null, 'jumlah' => (float) $detail->biaya_tiket];
    }
    if ($uangHarian > 0) {
        $sub = trim(($tipeLabel ? "Uang Harian {$tipeLabel}" : 'Uang Harian')
            . ($provinsiNama ? " ({$provinsiNama})" : ''));
        $rows[] = [
            'label' => 'Uang Harian',
            'sub' => $sub . '  —  Rp ' . number_format($tarifOh, 0, ',', '.') . ' OH  x  ' . $lamaHari,
            'jumlah' => $uangHarian,
        ];
    }
    if ((float) ($detail->uang_representasi ?? 0) > 0) {
        $rows[] = ['label' => 'Uang Representasi', 'sub' => null, 'jumlah' => (float) $detail->uang_representasi];
    }
    if ((float) ($detail->uang_rapat ?? 0) > 0) {
        $rows[] = ['label' => 'Uang Rapat', 'sub' => null, 'jumlah' => (float) $detail->uang_rapat];
    }
    if ((float) ($detail->biaya_penginapan ?? 0) > 0) {
        $rows[] = ['label' => 'Hotel / Penginapan', 'sub' => null, 'jumlah' => (float) $detail->biaya_penginapan];
    }
    if ((float) ($detail->biaya_transport ?? 0) > 0) {
        $rows[] = ['label' => 'Transport / Taxi / Tol / Speed', 'sub' => null, 'jumlah' => (float) $detail->biaya_transport];
    }

    $total = collect($rows)->sum('jumlah');
    $terbilang = function_exists('terbilang_rupiah') ? terbilang_rupiah($total) : '-';

    $tglBerangkat = $detail->tgl_berangkat
        ? \Carbon\Carbon::parse($detail->tgl_berangkat)->locale('id')->isoFormat('D MMMM Y')
        : '-';
    $tglTtd = $tagihan->tanggal_ttd
        ? \Carbon\Carbon::parse($tagihan->tanggal_ttd)->locale('id')->isoFormat('D MMMM Y')
        : now()->locale('id')->isoFormat('D MMMM Y');
    $kotaTtd = $tagihan->kota_ttd ?? 'Samarinda';

    $bendaharaNama = $tagihan->bendahara_pengeluaran_nama_snapshot ?? '-';
    $bendaharaNip = $tagihan->bendahara_pengeluaran_nip_snapshot ?? '-';
    $ppkNama = $tagihan->ppk_nama_snapshot ?? '-';
    $ppkNip = $tagihan->ppk_nip_snapshot ?? '-';

    $keterangan = 'Perincian Uang Harian, Akomodasi, dan Transportasi Menuju Tempat Tugas '
        . 'Sampai ke Tempat Asal Sesuai Dengan Bukti-Bukti Pengeluaran yang Diserahkan. '
        . 'Bahwa segala berkas dan tanggung jawab diserahkan kepada pegawai yang '
        . 'melaksanakan perjalanan dinas.';
@endphp

<div class="judul-doc">
    <h4>PERINCIAN BIAYA PERJALANAN DINAS</h4>
</div>

<table class="tbl-header">
    <tr>
        <td style="width: 110px;">Lampiran SPD No.</td>
        <td style="width: 10px;">:</td>
        <td>{{ $detail->no_sppd ?: ($detail->no_spt ?: '-') }}</td>
    </tr>
    <tr>
        <td>Tanggal</td>
        <td>:</td>
        <td>{{ $tglBerangkat }}</td>
    </tr>
    <tr>
        <td>Nama / NIP</td>
        <td>:</td>
        <td>{{ $nama }}{{ $nip !== '-' ? ' / ' . $nip : '' }}</td>
    </tr>
</table>

<table class="tbl-rincian">
    <thead>
        <tr>
            <th class="td-no">NO.</th>
            <th>BIAYA - PERINCIAN</th>
            <th class="td-jumlah">JUMLAH<br>(Rp)</th>
            <th class="td-ket">KETERANGAN</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $i => $row)
            <tr>
                <td class="td-no">{{ $i + 1 }}.</td>
                <td>
                    {{ $row['label'] }}
                    @if($row['sub'])
                        <div class="rincian-sub">{{ $row['sub'] }}</div>
                    @endif
                </td>
                <td class="td-jumlah">{{ number_format($row['jumlah'], 0, ',', '.') }}</td>
                @if($i === 0)
                    <td class="td-ket" rowspan="{{ count($rows) }}">{{ $keterangan }}</td>
                @endif
            </tr>
        @empty
            <tr>
                <td class="td-no">-</td>
                <td>Tidak ada komponen biaya.</td>
                <td class="td-jumlah">0</td>
                <td class="td-ket">{{ $keterangan }}</td>
            </tr>
        @endforelse
        <tr class="row-jumlah">
            <td colspan="2" class="label">JUMLAH</td>
            <td class="td-jumlah">{{ number_format($total, 0, ',', '.') }}</td>
            <td></td>
        </tr>
        <tr class="row-terbilang">
            <td colspan="4"><em>Terbilang :</em> &nbsp; <span class="isi">{{ ucwords(strtolower($terbilang)) }}</span></td>
        </tr>
    </tbody>
</table>

{{-- ===== Blok pembayaran & tanda tangan (TTD basah) ===== --}}
<table class="ttd-box">
    <tr>
        <td class="ttd-kiri">
            <table class="tbl-bayar">
                <tr>
                    <td>Telah dibayarkan sejumlah</td>
                    <td>Rp.</td>
                    <td style="font-weight: bold;">{{ number_format($total, 0, ',', '.') }}</td>
                </tr>
            </table>
            <p style="margin: 8px 0 0 0;">Bendahara Pengeluaran</p>
            <div class="ttd-space"></div>
            <p class="ttd-nama">{{ $bendaharaNama }}</p>
            <p class="ttd-nip">NIP. {{ $bendaharaNip }}</p>
        </td>
        <td class="ttd-kanan">
            <p style="margin: 0 0 4px 0;">{{ $kotaTtd }}, {{ $tglTtd }}</p>
            <p style="margin: 0;">Yang Menerima,</p>
            <div class="ttd-space" style="height: 78px;"></div>
            <p class="ttd-nama">{{ $nama }}</p>
            <p class="ttd-nip">NIP. {{ $nip }}</p>
        </td>
    </tr>
    <tr>
        <td class="ttd-kiri" style="padding-top: 34px;">
            <table class="tbl-bayar">
                <tr>
                    <td style="width: 150px;">Diterima sejumlah</td>
                    <td>Rp.</td>
                    <td style="font-weight: bold;">{{ number_format($total, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Telah dibayar sejumlah</td>
                    <td>Rp.</td>
                    <td></td>
                </tr>
                <tr>
                    <td>SISA KURANG/LEBIH</td>
                    <td>Rp.</td>
                    <td></td>
                </tr>
            </table>
        </td>
        <td class="ttd-kanan" style="padding-top: 34px;">
            <p style="margin: 0;">PEJABAT PEMBUAT KOMITMEN</p>
            <div class="ttd-space" style="height: 78px;"></div>
            <p class="ttd-nama">{{ $ppkNama }}</p>
            <p class="ttd-nip">NIP. {{ $ppkNip }}</p>
        </td>
    </tr>
</table>

</body>
</html>
