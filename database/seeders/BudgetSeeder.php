<?php

namespace Database\Seeders;

use App\Models\Budget;
use Illuminate\Database\Seeder;

class BudgetSeeder extends Seeder
{
    public function run(): void
    {
        $budgets = [
            [
                'program_code' => 'WA',
                'activity_code' => '4611',
                'output_code' => 'EBA',
                'suboutput_code' => '962',
                'component_code' => '001',
                'subcomponent_code' => '051',
                'account_code' => 'A',
                'item_code' => '524111',
                'coa' => 'WA.4611.EBA.962.001.051.A.524111',
                'description' => 'Belanja Barang Operasional - Pemeliharaan Runway',
                'initial_budget' => 2500000000,
                'realized_budget' => 875000000,
                'remaining_budget' => 1625000000,
                'year' => 2026,
            ],
            [
                'program_code' => 'WA',
                'activity_code' => '4611',
                'output_code' => 'EBA',
                'suboutput_code' => '962',
                'component_code' => '001',
                'subcomponent_code' => '051',
                'account_code' => 'A',
                'item_code' => '524119',
                'coa' => 'WA.4611.EBA.962.001.051.A.524119',
                'description' => 'Belanja Barang Non Operasional - Pemeliharaan Taxiway & Apron',
                'initial_budget' => 1800000000,
                'realized_budget' => 1800000000,
                'remaining_budget' => 0,
                'year' => 2026,
            ],
            [
                'program_code' => 'WA',
                'activity_code' => '4611',
                'output_code' => 'EBA',
                'suboutput_code' => '962',
                'component_code' => '002',
                'subcomponent_code' => '051',
                'account_code' => 'A',
                'item_code' => '525111',
                'coa' => 'WA.4611.EBA.962.002.051.A.525111',
                'description' => 'Belanja Jasa Profesi - Pengawasan Konstruksi Terminal',
                'initial_budget' => 950000000,
                'realized_budget' => 320000000,
                'remaining_budget' => 630000000,
                'year' => 2026,
            ],
        ];

        foreach ($budgets as $budget) {
            Budget::updateOrCreate(
                ['coa' => $budget['coa']],
                $budget
            );
        }
    }
}
