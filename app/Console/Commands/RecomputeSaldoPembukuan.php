<?php

namespace App\Console\Commands;

use App\Models\BukuKasUmum;
use App\Models\RekeningBank;
use Illuminate\Console\Command;

/**
 * Hitung ulang saldo berjalan seluruh buku_kas_umum per (rekening, peran, kode_buku).
 * Berguna sebagai backfill setelah perubahan saldo awal (pembukuan_saldo_awal) atau
 * impor data, agar kolom saldo_akhir konsisten (sumber kebenaran = recompute kronologis).
 */
class RecomputeSaldoPembukuan extends Command
{
    protected $signature = 'pembukuan:recompute-saldo {--rekening= : Batasi ke satu ID rekening}';
    protected $description = 'Hitung ulang saldo berjalan BKU & buku pembantu (per rekening/peran/buku).';

    public function handle(): int
    {
        $rekeningIds = $this->option('rekening')
            ? [(int) $this->option('rekening')]
            : BukuKasUmum::query()->distinct()->pluck('sumber_rekening_id')->filter()->values()->all();

        if (empty($rekeningIds)) {
            $this->warn('Tidak ada baris BKU untuk dihitung ulang.');

            return self::SUCCESS;
        }

        $this->withProgressBar($rekeningIds, function ($id) {
            BukuKasUmum::recalculateRunningBalance((int) $id);
        });

        $this->newLine(2);
        $this->info('Selesai. ' . count($rekeningIds) . ' rekening dihitung ulang.');

        return self::SUCCESS;
    }
}
