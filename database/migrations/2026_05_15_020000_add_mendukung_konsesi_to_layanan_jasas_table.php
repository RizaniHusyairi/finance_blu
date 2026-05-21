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
            if (! Schema::hasColumn('layanan_jasas', 'mendukung_konsesi')) {
                $table->boolean('mendukung_konsesi')->default(false)->after('tipe_layanan');
            }
        });

        DB::table('layanan_jasas')
            ->where('tipe_layanan', 'KONSESI')
            ->update([
                'tipe_layanan' => 'PNBP',
                'mendukung_konsesi' => true,
            ]);
    }

    public function down(): void
    {
        Schema::table('layanan_jasas', function (Blueprint $table) {
            if (Schema::hasColumn('layanan_jasas', 'mendukung_konsesi')) {
                $table->dropColumn('mendukung_konsesi');
            }
        });
    }
};
