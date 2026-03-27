<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\ContractTerm;
use Illuminate\Database\Seeder;

class ContractTermSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil semua kontrak yang cara_bayar = Termin
        $terminContracts = Contract::where('cara_bayar', 'Termin')->get();

        foreach ($terminContracts as $contract) {
            $jumlahTermin = $contract->jumlah_termin ?? 1;
            $totalAmount  = (float) $contract->total_amount;
            $adaUM        = $contract->ada_uang_muka;
            $nilaiUM      = (float) ($contract->nilai_uang_muka ?? 0);
            $angsuranUM   = (int) ($contract->jumlah_angsuran_um ?? 0);

            $sisaSetelahUM = $totalAmount - $nilaiUM;

            // ── 1. Uang Muka ──
            // Data uang muka disimpan di tabel contracts saja
            // (kolom: nilai_uang_muka, persentase_uang_muka, dll.)

            // ── 2. Termin Pekerjaan ──
            // Bagi sisa setelah uang muka ke jumlah termin
            $perTermin    = round($sisaSetelahUM / $jumlahTermin, 2);
            $terminSisa   = $sisaSetelahUM; // track rounding

            for ($i = 1; $i <= $jumlahTermin; $i++) {
                // Termin terakhir ambil sisa agar total pas
                $amount = ($i === $jumlahTermin) ? $terminSisa : $perTermin;
                $terminSisa -= $perTermin;

                // Hitung persentase terhadap total kontrak
                $pct = round(($amount / $totalAmount) * 100, 2);

                // Status: untuk kontrak Completed, set termin pertama Paid
                $status = ($contract->status === 'Selesai' && $i === 1) ? 'Paid' : 'Pending';

                ContractTerm::updateOrCreate(
                    ['contract_id' => $contract->id, 'term_name' => "Termin {$i}"],
                    [
                        'percentage' => $pct,
                        'amount'     => $amount,
                        'status'     => $status,
                    ]
                );
            }

            // ── 3. Angsuran Uang Muka (potongan per termin) ──
            if ($adaUM && $nilaiUM > 0 && $angsuranUM > 0) {
                $angsuranPerTermin = round($nilaiUM / $angsuranUM, 2);

                for ($j = 1; $j <= $angsuranUM; $j++) {
                    $amt = ($j === $angsuranUM)
                        ? $nilaiUM - ($angsuranPerTermin * ($angsuranUM - 1))
                        : $angsuranPerTermin;

                    ContractTerm::updateOrCreate(
                        ['contract_id' => $contract->id, 'term_name' => "Angsuran UM {$j}"],
                        [
                            'type'       => 'Angsuran',
                            'percentage' => round(($amt / $totalAmount) * 100, 2),
                            'amount'     => $amt,
                            'status'     => 'Pending',
                        ]
                    );
                }
            }
        }
    }
}
