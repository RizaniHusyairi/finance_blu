<?php

namespace App\Services\Pembukuan;

use App\Models\TransaksiPenerimaan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Penilaian kualitas piutang & penyisihan (sheet `Februari` File 2).
 *
 * Kualitas ditentukan dari umur tunggakan sejak tanggal jatuh tempo, dengan
 * persentase penyisihan berjenjang (pendekatan baku piutang pemerintah):
 *   Lancar (belum jatuh tempo) 0% · Kurang Lancar (1–90 hr) 10%
 *   Diragukan (91–180 hr) 50% · Macet (>180 hr) 100%.
 * Penyisihan = persentase × sisa piutang. Piutang lunas → Lancar, 0.
 */
class PiutangAgingService
{
    /** @var array<int, array{0:int|null,1:string,2:float}> [maxUmurHari|null, kualitas, persen] */
    private const TANGGA = [
        [0,    'LANCAR',        0.0],
        [90,   'KURANG_LANCAR', 10.0],
        [180,  'DIRAGUKAN',     50.0],
        [null, 'MACET',         100.0],
    ];

    /**
     * Hitung kualitas/penyisihan tanpa menyimpan.
     *
     * @return array{kualitas:string, persentase:float, penyisihan:float, umur_hari:int, sisa:float}
     */
    public function evaluate(TransaksiPenerimaan $p, ?Carbon $asOf = null): array
    {
        $asOf ??= Carbon::today();
        $sisa = $p->sisaPiutang();

        if ($p->status_pembayaran === 'PAID' || $sisa <= 0.0) {
            return ['kualitas' => 'LANCAR', 'persentase' => 0.0, 'penyisihan' => 0.0, 'umur_hari' => 0, 'sisa' => 0.0];
        }

        $umur = $p->tanggal_jatuh_tempo
            ? max(0, Carbon::parse($p->tanggal_jatuh_tempo)->diffInDays($asOf, false))
            : 0;

        [$kualitas, $persen] = $this->klasifikasiUmur($umur);
        $penyisihan = round($persen / 100 * $sisa, 2);

        return ['kualitas' => $kualitas, 'persentase' => $persen, 'penyisihan' => $penyisihan, 'umur_hari' => $umur, 'sisa' => $sisa];
    }

    /** Hitung lalu simpan kualitas/penyisihan ke baris piutang. */
    public function refresh(TransaksiPenerimaan $p, ?Carbon $asOf = null): TransaksiPenerimaan
    {
        $e = $this->evaluate($p, $asOf);

        $p->forceFill([
            'kualitas' => $e['kualitas'],
            'persentase_penyisihan' => $e['persentase'],
            'penyisihan' => $e['penyisihan'],
        ])->save();

        return $p;
    }

    /** Refresh seluruh piutang (opsional filter rentang invoice). @return int jumlah diperbarui */
    public function refreshAll(array $filters = [], ?Carbon $asOf = null): int
    {
        $n = 0;

        TransaksiPenerimaan::query()
            ->when($filters['start_date'] ?? null, fn (Builder $q, $d) => $q->whereDate('tanggal_invoice', '>=', $d))
            ->when($filters['end_date'] ?? null, fn (Builder $q, $d) => $q->whereDate('tanggal_invoice', '<=', $d))
            ->chunkById(500, function ($rows) use (&$n, $asOf) {
                foreach ($rows as $p) {
                    $this->refresh($p, $asOf);
                    $n++;
                }
            });

        return $n;
    }

    /** @return array{0:string,1:float} [kualitas, persentase] */
    private function klasifikasiUmur(int $umur): array
    {
        foreach (self::TANGGA as [$maxUmur, $kualitas, $persen]) {
            if ($maxUmur === null || $umur <= $maxUmur) {
                return [$kualitas, $persen];
            }
        }

        return ['MACET', 100.0];
    }
}
