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
            MasterPihakSeeder::class,
            MasterCoaSeeder::class,
            MasterDipaSeeder::class,
            MasterPegawaiSeeder::class,
            UserAccountSeeder::class,
            MasterLayananJasaSeeder::class,
            WorkflowDefinitionSeeder::class,
            WorkflowTagihanJasaSeeder::class,
            SppPerjaldinWorkflowSeeder::class,
            MasterUangHarianPerjaldinSeeder::class,
            MasterTarifPajakSeeder::class,
        ]);
    }
}
