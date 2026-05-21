<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('layanan_jasas')
            ->whereIn('kode_layanan', [
                'KSP-REKLAME-KU-NEON',
                'KSP-REKLAME-K1-NEON',
                'KSP-REKLAME-K2-NEON',
            ])
            ->update([
                'is_leaf' => false,
                'tarif_dasar' => 0,
                'satuan' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('layanan_jasas')
            ->whereIn('kode_layanan', [
                'KSP-REKLAME-KU-NEON',
                'KSP-REKLAME-K1-NEON',
                'KSP-REKLAME-K2-NEON',
            ])
            ->update([
                'is_leaf' => true,
                'updated_at' => now(),
            ]);
    }
};
