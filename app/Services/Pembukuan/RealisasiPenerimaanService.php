<?php

namespace App\Services\Pembukuan;

use App\Enums\KodeBuku;
use App\Enums\PeranBuku;
use App\Models\AkunPendapatan;
use App\Models\BukuKasUmum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Rekap realisasi pendapatan per akun × bulan (sheet `REALISASI_PENERIMAAN_2026`
 * File 2). Sumber: baris BKU Penerimaan (DEBIT_MASUK) yang sudah diklasifikasi
 * ke akun pendapatan, diagregasi dengan SUM per (akun, bulan).
 */
class RealisasiPenerimaanService
{
    /**
     * @return array{
     *   tahun:int, months:array<int,string>, rows:array<int,array>,
     *   total_per_bulan:array<int,float>, grand_total:float
     * }
     */
    public function build(int $tahun, array $filters = []): array
    {
        $rekeningId = $filters['rekening_bank_id'] ?? null;

        // Agregasi SUM nominal per (akun_pendapatan_id, bulan).
        $rowsRaw = BukuKasUmum::query()
            ->where('peran', PeranBuku::PENERIMAAN->value)
            ->where('kode_buku', KodeBuku::BKU->value)
            ->where('arus_kas', 'DEBIT_MASUK')
            ->whereYear('tanggal_transaksi', $tahun)
            ->when($rekeningId, fn (Builder $q) => $q->where('sumber_rekening_id', $rekeningId))
            ->selectRaw('akun_pendapatan_id, MONTH(tanggal_transaksi) as bln, SUM(nominal) as total')
            ->groupBy('akun_pendapatan_id', DB::raw('MONTH(tanggal_transaksi)'))
            ->get();

        // Master akun untuk label & urutan stabil.
        $akunList = AkunPendapatan::query()->orderBy('kode_akun')->orderBy('kode_jenis')->get()->keyBy('id');

        // Pivot: [akun_id => [bln => total]].
        $pivot = [];
        foreach ($rowsRaw as $r) {
            $pivot[$r->akun_pendapatan_id ?? 0][(int) $r->bln] = (float) $r->total;
        }

        $rows = [];
        $totalPerBulan = array_fill(1, 12, 0.0);
        $grand = 0.0;

        foreach ($pivot as $akunId => $perBulan) {
            $akun = $akunId ? $akunList->get($akunId) : null;
            $bulan = array_fill(1, 12, 0.0);
            $totalRow = 0.0;
            foreach ($perBulan as $bln => $nilai) {
                $bulan[$bln] = $nilai;
                $totalRow += $nilai;
                $totalPerBulan[$bln] += $nilai;
            }
            $grand += $totalRow;

            $rows[] = [
                'akun_id' => $akunId ?: null,
                'kode' => $akun?->kode_gabungan ?? '-',
                'kode_akun' => $akun?->kode_akun ?? '-',
                'uraian' => $akun ? $akun->labelLengkap() : 'Tanpa Klasifikasi Akun',
                'bulan' => $bulan,
                'total' => $totalRow,
            ];
        }

        // Urutkan baris per kode akun (yang tanpa akun di akhir).
        usort($rows, fn ($a, $b) => [$a['akun_id'] === null, $a['kode']] <=> [$b['akun_id'] === null, $b['kode']]);

        return [
            'tahun' => $tahun,
            'months' => [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
                7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'],
            'rows' => $rows,
            'total_per_bulan' => $totalPerBulan,
            'grand_total' => $grand,
        ];
    }
}
