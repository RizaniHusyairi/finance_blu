<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tagihan') || ! Schema::hasColumn('tagihan', 'dipa_revision_item_id')) {
            return;
        }

        $fallbackItemMap = DB::table('master_dipas')
            ->join('dipa_revisions', function ($join) {
                $join->on('dipa_revisions.master_dipa_id', '=', 'master_dipas.id')
                    ->where('dipa_revisions.is_active', '=', 1)
                    ->whereNull('dipa_revisions.deleted_at');
            })
            ->join('dipa_revision_items', function ($join) {
                $join->on('dipa_revision_items.dipa_revision_id', '=', 'dipa_revisions.id')
                    ->where('dipa_revision_items.status_aktif', '=', 1);
            })
            ->where('master_dipas.status_aktif', 1)
            ->whereNull('master_dipas.deleted_at')
            ->select(
                'master_dipas.id as master_dipa_id',
                DB::raw('MIN(dipa_revision_items.id) as dipa_revision_item_id')
            )
            ->groupBy('master_dipas.id')
            ->pluck('dipa_revision_item_id', 'master_dipa_id');

        if ($fallbackItemMap->isEmpty()) {
            return;
        }

        DB::table('tagihan')
            ->whereNull('deleted_at')
            ->whereNull('dipa_revision_item_id')
            ->orderBy('id')
            ->get(['id', 'master_dipa_id'])
            ->each(function ($tagihan) use ($fallbackItemMap) {
                $mappedItemId = $fallbackItemMap->get($tagihan->master_dipa_id);

                if (! $mappedItemId) {
                    return;
                }

                DB::table('tagihan')
                    ->where('id', $tagihan->id)
                    ->update(['dipa_revision_item_id' => $mappedItemId]);
            });
    }

    public function down(): void
    {
        // Tidak dirollback agar tidak menghilangkan pemetaan hasil backfill.
    }
};
