<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Schema;

/**
 * DB-05 — isi otomatis kolom audit `updated_by` (dan `created_by` bila ada &
 * masih kosong) dari user aktif. Dipasang hanya pada model yang memiliki
 * kolom `updated_by`. Pengecekan kolom di-cache per-kelas agar tidak
 * menambah query pada setiap penyimpanan.
 */
trait Blameable
{
    /** Cache keberadaan kolom audit, per-tabel (per-kelas karena trait). */
    protected static array $blameableColumns = [];

    public static function bootBlameable(): void
    {
        static::creating(function ($model): void {
            $userId = auth()->id();
            if ($userId === null) {
                return;
            }

            if (static::blameableHas($model, 'updated_by')) {
                $model->updated_by = $userId;
            }

            if (static::blameableHas($model, 'created_by') && empty($model->created_by)) {
                $model->created_by = $userId;
            }
        });

        static::updating(function ($model): void {
            $userId = auth()->id();
            if ($userId !== null && static::blameableHas($model, 'updated_by')) {
                $model->updated_by = $userId;
            }
        });
    }

    protected static function blameableHas($model, string $column): bool
    {
        $table = $model->getTable();

        return static::$blameableColumns[$table][$column]
            ??= Schema::hasColumn($table, $column);
    }
}
