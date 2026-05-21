<?php

namespace App\Services;

use App\Models\LayananJasa;
use App\Models\MitraJasa;
use Illuminate\Support\Facades\DB;

class MitraLayananService
{
    public function sync(MitraJasa $mitra, array $layananIds, ?int $createdBy = null): void
    {
        $billableIds = LayananJasa::query()
            ->whereIn('id', array_unique($layananIds))
            ->where('is_active', true)
            ->where('is_leaf', true)
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($mitra, $billableIds, $createdBy) {
            $mitra->layananJasa()->newPivotStatement()
                ->where('mitra_jasa_id', $mitra->id)
                ->update(['status_aktif' => false, 'updated_at' => now()]);

            foreach ($billableIds as $layananId) {
                $mitra->layananJasa()->syncWithoutDetaching([
                    $layananId => [
                        'status_aktif' => true,
                        'created_by' => $createdBy,
                        'updated_at' => now(),
                    ],
                ]);
            }
        });
    }
}
