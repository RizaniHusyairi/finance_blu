<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BudgetRealizationService dulu membaca DetailDipa::master_coa_id (kolom yang
 * tidak ada), sehingga realisasi_anggaran.master_coa_id selalu NULL. Isi dari
 * coa_id item DIPA yang dirujuk.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('realisasi_anggaran')
            ->whereNull('master_coa_id')
            ->update([
                'master_coa_id' => DB::raw('(select dipa_revision_items.coa_id from dipa_revision_items where dipa_revision_items.id = realisasi_anggaran.dipa_revision_item_id)'),
            ]);
    }

    public function down(): void
    {
        // Data koreksi; tidak dikembalikan ke NULL.
    }
};
