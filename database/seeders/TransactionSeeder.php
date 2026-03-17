<?php

namespace Database\Seeders;

use App\Models\BluPaymentSubmission;
use App\Models\Budget;
use App\Models\Contract;
use App\Models\ContractTerm;
use App\Models\Supplier;
use App\Models\TransactionTax;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        // --- Helpers: look up FK values from seeded data ---
        $supplierGaruda    = Supplier::where('name', 'PT. GARUDA TAWAKAL ABADI')->first();
        $supplierJaya      = Supplier::where('name', 'PT. JAYA KENCANA')->first();
        $supplierMahakam   = Supplier::where('name', 'CV. MAHAKAM RAYA HARMONI')->first();
        $supplierGuna      = Supplier::where('name', 'CV. GUNA AKHLAK SUKSES')->first();
        $supplierAnugerah  = Supplier::where('name', 'CV. ANUGERAH ZIKIR KELUARGA UTAMA')->first();

        $contractGaruda   = Contract::where('contract_number', 'SPK-BLU/2026/001')->first();
        $contractJaya     = Contract::where('contract_number', 'SPK-BLU/2026/004')->first();
        $contractAnugerah = Contract::where('contract_number', 'SPK-BLU/2026/005')->first();

        $budgetTerminal = Budget::where('coa', 'GA.4645.CBE.001.054.A.537113.00001')->first();
        $budgetCctv     = Budget::where('coa', 'GA.4646.CAD.001.051.A.537112.00001')->first();
        $budgetPkppk    = Budget::where('coa', 'GA.4646.CBE.001.055.A.537113.00001')->first();
        $budgetFasilitas = Budget::where('coa', 'GA.4645.CBE.001.054.A.537113.00002')->first();

        // Term lookup helper
        $term2of3Garuda = $contractGaruda
            ? ContractTerm::where('contract_id', $contractGaruda->id)->where('term_name', 'like', '%Termin 2%')->first()
            : null;
        $term1of2Jaya = $contractJaya
            ? ContractTerm::where('contract_id', $contractJaya->id)->where('term_name', 'like', '%Termin 1%')->first()
            : null;
        $term3of3Anugerah = $contractAnugerah
            ? ContractTerm::where('contract_id', $contractAnugerah->id)->where('term_name', 'like', '%Termin 3%')->first()
            : null;

        // --- Transaction rows ---
        $transactions = [
            // 1) PGJ-BLU-2026-0012 — Menunggu Persetujuan (Approved SPP)
            [
                'transaction_number' => 'PGJ-BLU-2026-0012',
                'date' => '2026-03-15',
                'type' => 'LS',
                'payment_method' => 'Termin',
                'description' => 'Pembayaran Termin 2 Pemeliharaan Gedung Terminal',
                'contract_id' => $contractGaruda?->id,
                'term_id' => $term2of3Garuda?->id,
                'supplier_id' => $supplierGaruda?->id,
                'budget_id' => $budgetTerminal?->id,
                'bast_number' => 'BAST-2026-0012',
                'bast_date' => '2026-03-13',
                'npi_number' => 'NPI-2026-0012',
                'spp_number' => 'SPP-BLU-2026-0012',
                'spp_date' => '2026-03-15',
                'gross_amount' => 350000000,
                'net_amount' => 350000000 - 35000000 - 3500000,
                'status' => 'Approved SPP',
                'taxes' => [
                    ['tax_name' => 'PPN', 'tax_account' => 'PPN 11%', 'amount' => 35000000],
                    ['tax_name' => 'PPh 23', 'tax_account' => 'PPh 23 2%', 'amount' => 3500000],
                ],
            ],

            // 2) PGJ-BLU-2026-0011 — Menunggu Verifikasi (Verified)
            [
                'transaction_number' => 'PGJ-BLU-2026-0011',
                'date' => '2026-03-14',
                'type' => 'LS',
                'payment_method' => 'Termin',
                'description' => 'Pembayaran Pengadaan CCTV Area Operasional',
                'contract_id' => $contractJaya?->id,
                'term_id' => $term1of2Jaya?->id,
                'supplier_id' => $supplierJaya?->id,
                'budget_id' => $budgetCctv?->id,
                'bast_number' => 'BAST-2026-0011',
                'bast_date' => '2026-03-12',
                'npi_number' => 'NPI-2026-0011',
                'gross_amount' => 225000000,
                'net_amount' => 225000000 - 22500000 - 2250000,
                'status' => 'Verified',
                'taxes' => [
                    ['tax_name' => 'PPN', 'tax_account' => 'PPN 11%', 'amount' => 22500000],
                    ['tax_name' => 'PPh 23', 'tax_account' => 'PPh 23 2%', 'amount' => 2250000],
                ],
            ],

            // 3) PGJ-BLU-2026-0010 — Draft (Non-Kontrak)
            [
                'transaction_number' => 'PGJ-BLU-2026-0010',
                'date' => '2026-03-12',
                'type' => 'LS',
                'payment_method' => 'Sekali Bayar',
                'description' => 'Pembayaran Honorarium Narasumber Sosialisasi Layanan BLU',
                'contract_id' => null,
                'term_id' => null,
                'supplier_id' => $supplierMahakam?->id,
                'budget_id' => $budgetFasilitas?->id,
                'bast_number' => null,
                'bast_date' => '2026-03-11',
                'gross_amount' => 58000000,
                'net_amount' => 58000000 - 2900000,
                'status' => 'Draft',
                'taxes' => [
                    ['tax_name' => 'PPh 21', 'tax_account' => 'PPh 21 5%', 'amount' => 2900000],
                ],
            ],

            // 4) PGJ-BLU-2026-0009 — Rejected / Direvisi
            [
                'transaction_number' => 'PGJ-BLU-2026-0009',
                'date' => '2026-03-10',
                'type' => 'LS',
                'payment_method' => 'Termin',
                'description' => 'Pembayaran Termin 3 Pekerjaan Perluasan Halaman Parkir PKP-PK',
                'contract_id' => $contractAnugerah?->id,
                'term_id' => $term3of3Anugerah?->id,
                'supplier_id' => $supplierAnugerah?->id,
                'budget_id' => $budgetPkppk?->id,
                'bast_number' => 'BAST-2026-0009',
                'bast_date' => '2026-03-08',
                'npi_number' => 'NPI-2026-0009',
                'gross_amount' => 300000000,
                'net_amount' => 300000000 - 30000000 - 3000000,
                'status' => 'Rejected',
                'taxes' => [
                    ['tax_name' => 'PPN', 'tax_account' => 'PPN 11%', 'amount' => 30000000],
                    ['tax_name' => 'PPh 23', 'tax_account' => 'PPh 23 2%', 'amount' => 3000000],
                ],
            ],

            // 5) PGJ-BLU-2026-0008 — Paid SP2D (Sudah Cair)
            [
                'transaction_number' => 'PGJ-BLU-2026-0008',
                'date' => '2026-03-08',
                'type' => 'LS',
                'payment_method' => 'Sekali Bayar',
                'description' => 'Pembayaran Pengadaan Peralatan Pendukung Ruang Server',
                'contract_id' => null,
                'term_id' => null,
                'supplier_id' => $supplierGuna?->id,
                'budget_id' => $budgetCctv?->id,
                'bast_number' => 'BAST-2026-0008',
                'bast_date' => '2026-03-05',
                'npi_number' => 'NPI-2026-0008',
                'spp_number' => 'SPP-BLU-2026-0008',
                'spp_date' => '2026-03-08',
                'spm_number' => 'SPM-BLU-2026-0008',
                'spm_date' => '2026-03-09',
                'sp2d_number' => 'SP2D-BLU-2026-0145',
                'sp2d_date' => '2026-03-11',
                'gross_amount' => 148000000,
                'net_amount' => 148000000 - 14800000 - 2960000,
                'status' => 'Paid SP2D',
                'taxes' => [
                    ['tax_name' => 'PPN', 'tax_account' => 'PPN 11%', 'amount' => 14800000],
                    ['tax_name' => 'PPh 23', 'tax_account' => 'PPh 23 2%', 'amount' => 2960000],
                ],
            ],
        ];

        foreach ($transactions as $data) {
            $taxes = $data['taxes'] ?? [];
            unset($data['taxes']);

            $trx = BluPaymentSubmission::updateOrCreate(
                ['transaction_number' => $data['transaction_number']],
                $data
            );

            // Seed related taxes
            foreach ($taxes as $tax) {
                TransactionTax::updateOrCreate(
                    [
                        'transaction_id' => $trx->id,
                        'tax_name' => $tax['tax_name'],
                    ],
                    $tax
                );
            }
        }
    }
}
