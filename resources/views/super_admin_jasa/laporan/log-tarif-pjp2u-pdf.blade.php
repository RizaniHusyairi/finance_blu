<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Log Perubahan Tarif PJP2U</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h2 { margin: 0 0 4px; }
        .meta { color: #555; font-size: 10px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 4px 6px; vertical-align: top; }
        th { background: #f0f0f0; text-align: left; font-size: 9px; text-transform: uppercase; }
        .num { text-align: right; }
        .pos { color: #0f7a23; }
        .neg { color: #b91c1c; }
        .small { font-size: 9px; color: #555; }
    </style>
</head>
<body>
    @php
        $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
        $tipeLabel = [
            'revisi_resmi' => 'Revisi Resmi',
            'diskon' => 'Diskon',
            'koreksi' => 'Koreksi',
        ];
    @endphp
    <h2>Log Perubahan Tarif PJP2U</h2>
    <div class="meta">
        Filter:
        Tanggal {{ $filters['tanggal_dari'] ?: '-' }} s.d. {{ $filters['tanggal_sampai'] ?: '-' }}
        | Tipe: {{ $filters['tipe_perubahan'] ? ($tipeLabel[$filters['tipe_perubahan']] ?? $filters['tipe_perubahan']) : 'Semua' }}
        | Total Perubahan: {{ $summary['total_perubahan'] }}
        | Dicetak: {{ now()->format('d/m/Y H:i') }}
    </div>
    <table>
        <thead>
            <tr>
                <th>Berlaku</th>
                <th>Layanan</th>
                <th class="num">Tarif Lama</th>
                <th class="num">Tarif Baru</th>
                <th class="num">Selisih</th>
                <th>Tipe</th>
                <th>Referensi</th>
                <th>Alasan</th>
                <th>Diubah</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                @php $selisih = $log->selisih; @endphp
                <tr>
                    <td>
                        {{ $log->berlaku_mulai?->format('d/m/Y') }}
                        @if($log->berlaku_sampai)
                            <div class="small">s.d. {{ $log->berlaku_sampai->format('d/m/Y') }}</div>
                        @endif
                    </td>
                    <td>
                        {{ $log->layananJasa?->nama_layanan ?? '-' }}
                        <div class="small">{{ $log->layananJasa?->nama_lengkap }}</div>
                    </td>
                    <td class="num">{{ $rupiah($log->tarif_lama) }}</td>
                    <td class="num">{{ $rupiah($log->tarif_baru) }}</td>
                    <td class="num {{ $selisih >= 0 ? 'pos' : 'neg' }}">{{ ($selisih >= 0 ? '+' : '') . $rupiah($selisih) }}</td>
                    <td>{{ $log->tipe_label }}</td>
                    <td>{{ $log->nomor_referensi ?? '-' }}</td>
                    <td>{{ $log->alasan }}</td>
                    <td>
                        {{ $log->creator?->name ?? '-' }}
                        <div class="small">{{ $log->created_at?->format('d/m/Y H:i') }}</div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center;color:#888;padding:14px;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
