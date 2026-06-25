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
            MasterLayananJasaSeeder::class,
            LayananJasaKelompokPnbpSeeder::class,
            // Penugasan Admin Jasa → layanan (perlu users + layanan_jasas siap).
            AdminJasaLayananSeeder::class,
            WorkflowDefinitionSeeder::class,
            WorkflowTagihanJasaSeeder::class,
            SppPerjaldinWorkflowSeeder::class,
            MasterUangHarianPerjaldinSeeder::class,
            MasterTarifPajakSeeder::class,
            // Pembukuan SILABI — master kode transaksi, akun pendapatan, kop satker.
            KodeTransaksiSeeder::class,
            AkunPendapatanSeeder::class,
            PembukuanSetupSeeder::class,
            // Register nomor dokumen PPK Belanja Barang & Modal (PL.107/108/109).
            NomorDokumenKontrakPengadaanSeeder::class,
        ]);
    }
}
