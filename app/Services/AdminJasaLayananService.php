<?php

namespace App\Services;

use App\Models\LayananJasa;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminJasaLayananService
{
    public function sync(User $admin, array $layananIds, ?int $createdBy = null): void
    {
        $billableIds = LayananJasa::query()
            ->whereIn('id', array_unique($layananIds))
            ->where('is_active', true)
            ->where('is_leaf', true)
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($admin, $billableIds, $createdBy) {
            $admin->layananJasaDikelola()->newPivotStatement()
                ->where('user_id', $admin->id)
                ->update(['status_aktif' => false, 'updated_at' => now()]);

            foreach ($billableIds as $layananId) {
                $admin->layananJasaDikelola()->syncWithoutDetaching([
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
