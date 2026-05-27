<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111827; }
        h2 { margin: 0 0 4px; font-size: 18px; }
        h3 { margin: 14px 0 6px; font-size: 12px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 6px; vertical-align: top; }
        th { background: #e5eefb; font-weight: 700; text-align: left; }
        .summary th { width: 28%; background: #f8fafc; }
        .right { text-align: right; }
        .center { text-align: center; }
        .text { mso-number-format: "\@"; }
        .no-border td { border: 0; padding: 2px 0; }
    </style>
</head>
<body>
@php
    $isExcel = ($exportFormat ?? '') === 'excel';
    $rupiah = fn ($value) => $isExcel
        ? number_format((float) $value, 2, '.', '')
        : 'Rp ' . number_format((float) $value, 0, ',', '.');
    $angka = fn ($value) => $isExcel ? (string) $value : number_format((float) $value, 0, ',', '.');
    $tanggal = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '-';
    $datetime = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '-';
    $bulanLabel = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $agingLabels = [
        'belum_jatuh_tempo' => 'Belum Jatuh Tempo',
        '1_30' => 'Telat 1-30 Hari',
        '31_60' => 'Telat 31-60 Hari',
        '61_90' => 'Telat 61-90 Hari',
        'lebih_90' => 'Telat > 90 Hari',
    ];
    $statusLabels = [
        'BARU' => 'Baru',
        'LANCAR' => 'Lancar',
        'CUKUP_LANCAR' => 'Cukup Lancar',
        'PERLU_PERHATIAN' => 'Perlu Perhatian',
        'MACET' => 'Macet',
    ];
@endphp

<h2>{{ $title }}</h2>
<div class="muted">Dicetak pada {{ $generatedAt->format('d/m/Y H:i') }}</div>

<h3>Filter</h3>
<table class="summary">
    <tbody>
    @forelse($filterLabels as $label => $value)
        <tr>
            <th>{{ $label }}</th>
            <td>{{ $value }}</td>
        </tr>
    @empty
        <tr>
            <th>Filter</th>
            <td>Semua data</td>
        </tr>
    @endforelse
    </tbody>
</table>

@switch($report)
    @case('rekap-tagihan')
        <h3>Ringkasan</h3>
        <table class="summary">
            <tbody>
                <tr><th>Jumlah Tagihan</th><td class="right">{{ $angka($summary['count']) }}</td></tr>
                <tr><th>Total Tagihan</th><td class="right">{{ $rupiah($summary['nominal']) }}</td></tr>
                <tr><th>Diterima (Lunas)</th><td class="right">{{ $rupiah($summary['lunas']) }}</td></tr>
                <tr><th>Sisa Outstanding</th><td class="right">{{ $rupiah($summary['sisa']) }}</td></tr>
            </tbody>
        </table>

        <h3>Breakdown per Bulan</h3>
        <table>
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th class="center">Jumlah Tagihan</th>
                    <th class="right">Total Nominal</th>
                    <th class="center">Lunas</th>
                    <th class="center">Belum Lunas</th>
                    <th class="right">Diterima</th>
                    <th class="right">Sisa</th>
                </tr>
            </thead>
            <tbody>
                @forelse($perBulan as $row)
                    <tr>
                        <td>{{ $bulanLabel[$row->bulan] ?? $row->bulan }}</td>
                        <td class="center">{{ $angka($row->jumlah_tagihan) }}</td>
                        <td class="right">{{ $rupiah($row->total_nominal) }}</td>
                        <td class="center">{{ $angka($row->jumlah_lunas) }}</td>
                        <td class="center">{{ $angka($row->jumlah_belum_lunas) }}</td>
                        <td class="right">{{ $rupiah($row->nominal_lunas) }}</td>
                        <td class="right">{{ $rupiah($row->nominal_sisa) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="center muted">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        @break

    @case('rekap-layanan')
        <h3>Ringkasan</h3>
        <table class="summary">
            <tbody>
                <tr><th>Jumlah Layanan</th><td class="right">{{ $angka($summary['layanan_count']) }}</td></tr>
                <tr><th>Jumlah Baris Tagihan</th><td class="right">{{ $angka($summary['jumlah_tagihan']) }}</td></tr>
                <tr><th>Total Nominal</th><td class="right">{{ $rupiah($summary['nominal']) }}</td></tr>
                <tr><th>Nominal Lunas</th><td class="right">{{ $rupiah($summary['nominal_lunas']) }}</td></tr>
                <tr><th>Nominal Belum Lunas</th><td class="right">{{ $rupiah($summary['nominal_belum_lunas']) }}</td></tr>
            </tbody>
        </table>

        <h3>Ranking Pendapatan Layanan per Bulan</h3>
        <table>
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th class="center">Rank</th>
                    <th>Layanan</th>
                    <th>Kode</th>
                    <th class="center">Jumlah Tagihan</th>
                    <th class="right">Total Pendapatan</th>
                    <th class="right">Lunas</th>
                    <th class="right">Belum Lunas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($months as $month)
                    @php
                        $rankingRows = $rankingBulanan->get($month, collect());
                    @endphp
                    @forelse($rankingRows as $row)
                        <tr>
                            <td>{{ $bulanLabel[$month] ?? $month }}</td>
                            <td class="center">{{ $loop->iteration }}</td>
                            <td>{{ $row->nama_layanan }}</td>
                            <td class="text">{{ $row->kode_layanan ?: '-' }}</td>
                            <td class="center">{{ $angka($row->jumlah_tagihan) }}</td>
                            <td class="right">{{ $rupiah($row->total_nominal) }}</td>
                            <td class="right">{{ $rupiah($row->nominal_lunas) }}</td>
                            <td class="right">{{ $rupiah($row->nominal_belum_lunas) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td>{{ $bulanLabel[$month] ?? $month }}</td>
                            <td colspan="7" class="center muted">Tidak ada data.</td>
                        </tr>
                    @endforelse
                @empty
                    <tr><td colspan="8" class="center muted">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        @break

    @case('rekap-terima-setor')
        <h3>Ringkasan</h3>
        <table class="summary">
            <tbody>
                <tr><th>Jumlah Tagihan Diterima</th><td class="right">{{ $angka($summary['count']) }}</td></tr>
                <tr><th>Total Nominal Diterima</th><td class="right">{{ $rupiah($summary['nominal_diterima']) }}</td></tr>
            </tbody>
        </table>

        <h3>Rekap per Bulan</h3>
        <table>
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th class="center">Jumlah</th>
                    <th class="right">Nominal Diterima</th>
                </tr>
            </thead>
            <tbody>
                @forelse($perBulan as $row)
                    <tr>
                        <td>{{ $bulanLabel[$row->bulan] ?? $row->bulan }}</td>
                        <td class="center">{{ $angka($row->jumlah) }}</td>
                        <td class="right">{{ $rupiah($row->nominal) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="center muted">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h3>Daftar Penerimaan</h3>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>No. Tagihan</th>
                    <th>Mitra</th>
                    <th>Tipe</th>
                    <th>Tgl Tagihan</th>
                    <th>Tgl Lunas</th>
                    <th class="right">Nominal Diterima</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tagihans as $t)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td class="text">{{ $t->nomor_tagihan }}</td>
                        <td>{{ $t->mitra->nama_mitra ?? '-' }}</td>
                        <td>{{ $t->tipe_pnbp ?? '-' }}</td>
                        <td>{{ $tanggal($t->tanggal_tagihan) }}</td>
                        <td>{{ $tanggal($t->tanggal_lunas) }}</td>
                        <td class="right">{{ $rupiah($t->jumlah_dibayar) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="center muted">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        @break

    @case('rekap-pembayaran')
        <h3>Ringkasan</h3>
        <table class="summary">
            <tbody>
                <tr><th>Jumlah Transaksi</th><td class="right">{{ $angka($summary['count']) }}</td></tr>
                <tr><th>Total Nominal Dibayar</th><td class="right">{{ $rupiah($summary['nominal']) }}</td></tr>
            </tbody>
        </table>

        <h3>Breakdown per Kanal Pembayaran</h3>
        <table>
            <thead>
                <tr>
                    <th>Kanal</th>
                    <th class="center">Jumlah</th>
                    <th class="right">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($perChannel as $row)
                    <tr>
                        <td>{{ $row->channel }}</td>
                        <td class="center">{{ $angka($row->jumlah) }}</td>
                        <td class="right">{{ $rupiah($row->nominal) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="center muted">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h3>Daftar Transaksi Pembayaran</h3>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>No. Tagihan</th>
                    <th>Mitra</th>
                    <th>Kanal</th>
                    <th>Referensi</th>
                    <th>Tgl Bayar</th>
                    <th class="right">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tagihans as $t)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td class="text">{{ $t->nomor_tagihan }}</td>
                        <td>{{ $t->mitra->nama_mitra ?? '-' }}</td>
                        <td>{{ $t->payment_channel ?? '-' }}</td>
                        <td class="text">{{ $t->payment_reference ?? '-' }}</td>
                        <td>{{ $datetime($t->paid_at) }}</td>
                        <td class="right">{{ $rupiah($t->jumlah_dibayar) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="center muted">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        @break

    @case('rekap-piutang')
        <h3>Ringkasan</h3>
        <table class="summary">
            <tbody>
                <tr><th>Jumlah Tagihan Outstanding</th><td class="right">{{ $angka($summary['count']) }}</td></tr>
                <tr><th>Total Tagihan</th><td class="right">{{ $rupiah($summary['nominal_tagihan']) }}</td></tr>
                <tr><th>Total Dibayar</th><td class="right">{{ $rupiah($summary['nominal_dibayar']) }}</td></tr>
                <tr><th>Sisa Piutang</th><td class="right">{{ $rupiah($summary['nominal_sisa']) }}</td></tr>
            </tbody>
        </table>

        <h3>Analisis Umur Piutang</h3>
        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th class="center">Jumlah</th>
                    <th class="right">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($agingLabels as $key => $label)
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="center">{{ $angka($agingSummary[$key]['count'] ?? 0) }}</td>
                        <td class="right">{{ $rupiah($agingSummary[$key]['nominal'] ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h3>Daftar Tagihan Outstanding</h3>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>No. Tagihan</th>
                    <th>Mitra</th>
                    <th>Tgl Tagihan</th>
                    <th>Jatuh Tempo</th>
                    <th class="center">Umur</th>
                    <th class="right">Total</th>
                    <th class="right">Dibayar</th>
                    <th class="right">Sisa</th>
                </tr>
            </thead>
            <tbody>
                @forelse($piutangs as $t)
                    @php
                        $hariTelat = $t->tanggal_jatuh_tempo
                            ? (int) now()->startOfDay()->diffInDays($t->tanggal_jatuh_tempo->copy()->startOfDay(), false)
                            : null;
                        $hariTelat = $hariTelat !== null ? -$hariTelat : null;
                        $umur = $hariTelat === null ? '-' : ($hariTelat <= 0 ? abs($hariTelat) . ' hari lagi' : '+' . $hariTelat . ' hari');
                    @endphp
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td class="text">{{ $t->nomor_tagihan }}</td>
                        <td>{{ $t->mitra->nama_mitra ?? '-' }}</td>
                        <td>{{ $tanggal($t->tanggal_tagihan) }}</td>
                        <td>{{ $tanggal($t->tanggal_jatuh_tempo) }}</td>
                        <td class="center">{{ $umur }}</td>
                        <td class="right">{{ $rupiah($t->total_tagihan) }}</td>
                        <td class="right">{{ $rupiah($t->jumlah_dibayar) }}</td>
                        <td class="right">{{ $rupiah($t->sisa_tagihan) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="center muted">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        @break

    @case('performa-mitra')
        <h3>Ringkasan Status</h3>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th class="center">Jumlah Mitra</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statusLabels as $key => $label)
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="center">{{ $angka($statusCount[$key] ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h3>Daftar Mitra dan Status Performa</h3>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Mitra</th>
                    <th>Status</th>
                    <th class="center">Jml Tagihan</th>
                    <th class="center">Lunas</th>
                    <th class="center">Outstanding</th>
                    <th class="right">Sisa Piutang</th>
                    <th class="center">Telat Saat Ini</th>
                    <th class="center">Rata-rata Telat Lunas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $row->nama_mitra }}</td>
                        <td>{{ $statusLabels[$row->status_performa] ?? $row->status_performa }}</td>
                        <td class="center">{{ $angka($row->jumlah_tagihan) }}</td>
                        <td class="center">{{ $angka($row->jumlah_lunas) }}</td>
                        <td class="center">{{ $angka($row->jumlah_outstanding) }}</td>
                        <td class="right">{{ $rupiah($row->sisa_outstanding) }}</td>
                        <td class="center">{{ $row->outstanding_max_overdue > 0 ? '+' . $row->outstanding_max_overdue . ' hari' : '-' }}</td>
                        <td class="center">{{ $row->jumlah_lunas > 0 ? $row->rata_late . ' hari' : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="center muted">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        @break
@endswitch
</body>
</html>
