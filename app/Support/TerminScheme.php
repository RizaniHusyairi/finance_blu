<?php

namespace App\Support;

/**
 * Pembentuk skema termin kontrak (LUMPSUM / TERMIN) + alokasi uang muka.
 *
 * Rumus disalin dari alur SPK (ContractController::buildTerminScheme /
 * calculateTermValue / attachAngsuranUangMuka) agar perilakunya identik,
 * namun murni (tanpa Request) sehingga dapat dipakai ulang oleh alur tagihan
 * kontrak eksternal. Setiap baris hasil berbentuk:
 *   ['jenis_termin', 'keterangan_termin', 'persentase', 'nilai_bruto_termin',
 *    'potongan_angsuran_uang_muka' (setelah allocateUangMuka)]
 */
class TerminScheme
{
    private const TOLERANCE = 0.01;

    /**
     * Skema LUMPSUM: satu baris pelunasan 100%.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function lumpsum(float $nilaiTotal): array
    {
        return [[
            'jenis_termin' => 'PELUNASAN',
            'keterangan_termin' => 'Pelunasan (Lumpsum)',
            'persentase' => 100.0,
            'nilai_bruto_termin' => round($nilaiTotal, 2),
        ]];
    }

    /**
     * Skema TERMIN: baris PROGRESS (dari input) + PELUNASAN otomatis
     * (100 − Σprogress − retensi) + RETENSI opsional. Total selalu 100%.
     *
     * @param  array<int, array{keterangan: ?string, persentase: float|int|string}>  $progress
     * @return array<int, array<string, mixed>>
     *
     * @throws \InvalidArgumentException
     */
    public static function build(array $progress, ?float $retensiPct, ?string $retensiKet, float $nilaiTotal): array
    {
        $progressRows = [];
        foreach ($progress as $row) {
            $pct = (float) ($row['persentase'] ?? 0);
            if ($pct <= 0) {
                continue;
            }
            $ket = trim((string) ($row['keterangan'] ?? ''));
            $progressRows[] = [
                'persentase' => $pct,
                'keterangan_termin' => $ket !== '' ? $ket : 'Termin Progress ' . (count($progressRows) + 1),
            ];
        }

        if (count($progressRows) === 0) {
            throw new \InvalidArgumentException('Minimal harus ada 1 termin progress jika metode pembayaran TERMIN.');
        }

        $retensiPct = $retensiPct !== null ? (float) $retensiPct : 0.0;
        $gunakanRetensi = $retensiPct > 0;

        $totalProgress = array_sum(array_column($progressRows, 'persentase'));
        $totalProgressRetensi = $totalProgress + $retensiPct;

        if ($totalProgressRetensi > (100 + self::TOLERANCE)) {
            throw new \InvalidArgumentException($gunakanRetensi
                ? 'Total persentase progress dan retensi tidak boleh melebihi 100%.'
                : 'Total persentase progress tidak boleh melebihi 100%.');
        }

        $persentasePelunasan = round(100 - $totalProgressRetensi, 4);
        if ($persentasePelunasan < -self::TOLERANCE) {
            throw new \InvalidArgumentException('Urutan termin tidak valid. Total progress dan retensi menghasilkan pelunasan negatif.');
        }
        if (abs($persentasePelunasan) <= self::TOLERANCE) {
            $persentasePelunasan = 0.0;
        }

        $rows = [];
        foreach ($progressRows as $row) {
            $rows[] = [
                'jenis_termin' => 'PROGRESS',
                'keterangan_termin' => $row['keterangan_termin'],
                'persentase' => round($row['persentase'], 4),
                'nilai_bruto_termin' => self::nilaiTermin($nilaiTotal, $row['persentase']),
            ];
        }

        $rows[] = [
            'jenis_termin' => 'PELUNASAN',
            'keterangan_termin' => 'Pelunasan',
            'persentase' => $persentasePelunasan,
            'nilai_bruto_termin' => self::nilaiTermin($nilaiTotal, $persentasePelunasan),
        ];

        if ($gunakanRetensi) {
            $ket = trim((string) $retensiKet);
            $rows[] = [
                'jenis_termin' => 'RETENSI',
                'keterangan_termin' => $ket !== '' ? $ket : 'Retensi',
                'persentase' => round($retensiPct, 4),
                'nilai_bruto_termin' => self::nilaiTermin($nilaiTotal, $retensiPct),
            ];
        }

        $totalPersentase = array_sum(array_column($rows, 'persentase'));
        if (abs($totalPersentase - 100) > self::TOLERANCE) {
            throw new \InvalidArgumentException('Total persentase termin harus 100% setelah sistem membentuk progress, pelunasan'
                . ($gunakanRetensi ? ', dan retensi.' : '.'));
        }

        return $rows;
    }

    /**
     * Bagikan uang muka proporsional ke tiap termin PROGRESS/PELUNASAN
     * (RETENSI dikecualikan); baris eligible terakhir menyerap sisa pembulatan.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public static function allocateUangMuka(array $rows, float $nilaiUangMuka): array
    {
        foreach ($rows as &$row) {
            $row['potongan_angsuran_uang_muka'] = 0.0;
            $row['nilai_retensi'] = $row['jenis_termin'] === 'RETENSI' ? (float) $row['nilai_bruto_termin'] : 0.0;
        }
        unset($row);

        if ($nilaiUangMuka <= 0) {
            return $rows;
        }

        $eligibleIndexes = [];
        $eligibleTotal = 0.0;
        foreach ($rows as $index => $row) {
            if (in_array($row['jenis_termin'], ['PROGRESS', 'PELUNASAN'], true) && (float) $row['nilai_bruto_termin'] > 0) {
                $eligibleIndexes[] = $index;
                $eligibleTotal += (float) $row['nilai_bruto_termin'];
            }
        }

        if ($eligibleTotal <= 0 || count($eligibleIndexes) === 0) {
            return $rows;
        }

        $remaining = round($nilaiUangMuka, 2);
        $lastEligible = end($eligibleIndexes);

        foreach ($rows as $index => &$row) {
            if (! in_array($index, $eligibleIndexes, true)) {
                continue;
            }

            if ($index === $lastEligible) {
                $row['potongan_angsuran_uang_muka'] = max(0, round($remaining, 2));
                continue;
            }

            $alloc = round(((float) $row['nilai_bruto_termin'] / $eligibleTotal) * $nilaiUangMuka, 2);
            $alloc = min($alloc, $remaining);
            $row['potongan_angsuran_uang_muka'] = $alloc;
            $remaining = round($remaining - $alloc, 2);
        }
        unset($row);

        return $rows;
    }

    private static function nilaiTermin(float $nilaiTotal, float $persentase): float
    {
        return round(($persentase / 100) * $nilaiTotal, 2);
    }
}
