<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            MasterCoaSeeder::class,
            MasterDipaSeeder::class,
            MasterPegawaiSeeder::class,
            UserAccountSeeder::class,
            RekeningBankDefaultSeeder::class,
            MasterLayananJasaSeeder::class,
            LayananJasaKelompokPnbpSeeder::class,
            WorkflowDefinitionSeeder::class,
            WorkflowTagihanJasaSeeder::class,
            SppPerjaldinWorkflowSeeder::class,
            MasterUangHarianPerjaldinSeeder::class,
            MasterTarifPajakSeeder::class,
            MitraMaskapaiSeeder::class,
            // Pembukuan SILABI — master kode transaksi, akun pendapatan, kop satker.
            KodeTransaksiSeeder::class,
            AkunPendapatanSeeder::class,
            PembukuanSetupSeeder::class,
        ]);
    }
}
