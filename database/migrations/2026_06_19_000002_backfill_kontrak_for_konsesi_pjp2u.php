<?php

use App\Models\MitraJasaKonsesi;
use App\Models\MitraJasaPjp2u;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Transisi "kontrak wajib": tautkan konsesi & hak PJP2U lama yang belum punya
 * kontrak ke kontrak mitra yang tersedia — diutamakan Kontrak Legacy aktif,
 * lalu kontrak aktif lain, lalu kontrak apa pun milik mitra tsb.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = [(new MitraJasaKonsesi())->getTable(), (new MitraJasaPjp2u())->getTable()];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'kontrak_mitra_jasa_id')) {
                continue;
            }

            DB::table($table)
                ->whereNull('kontrak_mitra_jasa_id')
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($table) {
                    foreach ($rows as $row) {
                        $kontrakId = $this->resolveKontrakId((int) $row->mitra_jasa_id);
                        if ($kontrakId) {
                            DB::table($table)->where('id', $row->id)
                                ->update(['kontrak_mitra_jasa_id' => $kontrakId]);
                        }
                    }
                });
        }
    }

    private function resolveKontrakId(int $mitraId): ?int
    {
        $base = fn () => DB::table('kontrak_mitra_jasa')
            ->where('mitra_jasa_id', $mitraId)
            ->whereNull('deleted_at');

        $legacy = (clone $base())->where('status_kontrak', 'AKTIF')
            ->where('nomor_kontrak', 'like', 'LEGACY-%')->value('id');
        if ($legacy) {
            return (int) $legacy;
        }

        $active = (clone $base())->where('status_kontrak', 'AKTIF')->value('id');
        if ($active) {
            return (int) $active;
        }

        return ($any = $base()->value('id')) ? (int) $any : null;
    }

    public function down(): void
    {
        // Penautan data tidak di-rollback otomatis (aman dibiarkan).
    }
};
