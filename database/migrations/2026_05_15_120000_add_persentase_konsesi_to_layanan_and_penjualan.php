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
            if (! Schema::hasColumn('layanan_jasas', 'persentase_konsesi')) {
                $table->decimal('persentase_konsesi', 8, 4)->nullable()->after('mendukung_konsesi');
            }
        });

        Schema::table('mitra_jasa_penjualan', function (Blueprint $table) {
            if (! Schema::hasColumn('mitra_jasa_penjualan', 'persentase_konsesi')) {
                $table->decimal('persentase_konsesi', 8, 4)->nullable()->after('total_transaksi');
            }
        });

        DB::table('layanan_jasas')
            ->where('mendukung_konsesi', true)
            ->whereNull('persentase_konsesi')
            ->where('satuan', 'like', '%\\%%')
            ->update([
                'persentase_konsesi' => DB::raw('tarif_dasar'),
                'updated_at' => now(),
            ]);

        DB::table('layanan_jasas')
            ->where('mendukung_konsesi', true)
            ->whereNull('persentase_konsesi')
            ->update([
                'persentase_konsesi' => 5,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('mitra_jasa_penjualan', function (Blueprint $table) {
            if (Schema::hasColumn('mitra_jasa_penjualan', 'persentase_konsesi')) {
                $table->dropColumn('persentase_konsesi');
            }
        });

        Schema::table('layanan_jasas', function (Blueprint $table) {
            if (Schema::hasColumn('layanan_jasas', 'persentase_konsesi')) {
                $table->dropColumn('persentase_konsesi');
            }
        });
    }
};
