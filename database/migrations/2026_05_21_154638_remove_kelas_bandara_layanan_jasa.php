<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Soft-delete (atau hard-delete jika tidak ada deleted_at) seluruh entri layanan
 * jasa yang merupakan varian klasifikasi bandara: Kelas I Utama, Kelas I, Kelas II,
 * Kelas III, dan Satuan Pelayanan. Termasuk seluruh turunannya.
 *
 * Pendekatan non-destruktif: kita pakai softDelete sehingga relasi historis pada
 * tagihan/mitra tetap valid, tetapi data tidak akan tampil di tree picker.
 */
return new class extends Migration
{
    private const KEYWORDS = [
        'kelas I utama',
        'kelas i utama',
        'kelas-1-utama',
        'kelas 1 utama',
        'kelas II',
        'kelas 2',
        'kelas III',
        'kelas 3',
        'Satuan Pelayanan',
        'satuan pelayanan',
    ];

    public function up(): void
    {
        $hasSoftDelete = Schema::hasColumn('layanan_jasas', 'deleted_at');

        // Step 1: Cari semua node yang nama_layanan mengandung salah satu keyword.
        $query = DB::table('layanan_jasas');

        if ($hasSoftDelete) {
            $query->whereNull('deleted_at');
        }

        $query->where(function ($q) {
            foreach (self::KEYWORDS as $kw) {
                $q->orWhere('nama_layanan', 'like', '%' . $kw . '%');
            }
        });

        $matchedIds = $query->pluck('id')->all();

        if (empty($matchedIds)) {
            return;
        }

        // Step 2: Kumpulkan semua keturunan dari ID yang cocok.
        $allIds = $matchedIds;
        $frontier = $matchedIds;
        $guard = 0;

        while (! empty($frontier) && $guard < 12) {
            $childIds = DB::table('layanan_jasas')
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->all();

            $childIds = array_values(array_diff($childIds, $allIds));

            if (empty($childIds)) {
                break;
            }

            $allIds = array_merge($allIds, $childIds);
            $frontier = $childIds;
            $guard++;
        }

        $allIds = array_values(array_unique($allIds));

        if (empty($allIds)) {
            return;
        }

        // Step 3: Soft-delete semua node tersebut.
        if ($hasSoftDelete) {
            DB::table('layanan_jasas')
                ->whereIn('id', $allIds)
                ->update(['deleted_at' => Carbon::now()]);
        } else {
            DB::table('layanan_jasas')
                ->whereIn('id', $allIds)
                ->delete();
        }
    }

    public function down(): void
    {
        // Pulihkan node yang ter-softDelete oleh migration ini.
        if (! Schema::hasColumn('layanan_jasas', 'deleted_at')) {
            return;
        }

        $query = DB::table('layanan_jasas')->whereNotNull('deleted_at');

        $query->where(function ($q) {
            foreach (self::KEYWORDS as $kw) {
                $q->orWhere('nama_layanan', 'like', '%' . $kw . '%');
            }
        });

        $matchedIds = $query->pluck('id')->all();

        if (empty($matchedIds)) {
            return;
        }

        $allIds = $matchedIds;
        $frontier = $matchedIds;
        $guard = 0;

        while (! empty($frontier) && $guard < 12) {
            $childIds = DB::table('layanan_jasas')
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->all();

            $childIds = array_values(array_diff($childIds, $allIds));

            if (empty($childIds)) {
                break;
            }

            $allIds = array_merge($allIds, $childIds);
            $frontier = $childIds;
            $guard++;
        }

        DB::table('layanan_jasas')
            ->whereIn('id', $allIds)
            ->update(['deleted_at' => null]);
    }
};
