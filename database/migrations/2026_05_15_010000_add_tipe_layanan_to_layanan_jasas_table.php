<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('layanan_jasas', function (Blueprint $table) {
            if (! Schema::hasColumn('layanan_jasas', 'tipe_layanan')) {
                $table->string('tipe_layanan', 20)->default('PNBP')->after('is_active');
            }
        });

        $konsesiIds = DB::table('layanan_jasas')
            ->where('nama_layanan', 'like', '%Konsesi%')
            ->pluck('id');

        while ($konsesiIds->isNotEmpty()) {
            DB::table('layanan_jasas')
                ->whereIn('id', $konsesiIds)
                ->update(['tipe_layanan' => 'KONSESI']);

            $konsesiIds = DB::table('layanan_jasas')
                ->whereIn('parent_id', $konsesiIds)
                ->pluck('id');
        }
    }

    public function down(): void
    {
        Schema::table('layanan_jasas', function (Blueprint $table) {
            if (Schema::hasColumn('layanan_jasas', 'tipe_layanan')) {
                $table->dropColumn('tipe_layanan');
            }
        });
    }
};
