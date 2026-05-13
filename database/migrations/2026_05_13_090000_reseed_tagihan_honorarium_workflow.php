<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus semua step lama dari workflow TAGIHAN_HONORARIUM.
        // Seeder akan membuat ulang struktur yang baru: 5 paralel + Kasubbag step 2.
        $definitionId = DB::table('workflow_definitions')
            ->where('kode', 'TAGIHAN_HONORARIUM')
            ->value('id');

        if ($definitionId) {
            DB::table('workflow_definition_steps')
                ->where('workflow_definition_id', $definitionId)
                ->delete();
        }

        \Artisan::call('db:seed', [
            '--class' => \Database\Seeders\WorkflowDefinitionSeeder::class,
            '--force' => true,
        ]);
    }

    public function down(): void
    {
        // No-op: data workflow_definition_steps dapat dibangun ulang via seeder.
    }
};
