<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            if (!Schema::hasColumn('tagihan', 'bendahara_penerimaan_user_id')) {
                $table->foreignId('bendahara_penerimaan_user_id')->nullable()->after('ppspm_nip_snapshot')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('tagihan', 'bendahara_penerimaan_nama_snapshot')) {
                $table->string('bendahara_penerimaan_nama_snapshot', 150)->nullable()->after('bendahara_penerimaan_user_id');
            }

            if (!Schema::hasColumn('tagihan', 'bendahara_penerimaan_nip_snapshot')) {
                $table->string('bendahara_penerimaan_nip_snapshot', 100)->nullable()->after('bendahara_penerimaan_nama_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite' && Schema::hasColumn('tagihan', 'bendahara_penerimaan_user_id')) {
                $table->dropForeign(['bendahara_penerimaan_user_id']);
            }

            $columns = array_filter([
                Schema::hasColumn('tagihan', 'bendahara_penerimaan_user_id') ? 'bendahara_penerimaan_user_id' : null,
                Schema::hasColumn('tagihan', 'bendahara_penerimaan_nama_snapshot') ? 'bendahara_penerimaan_nama_snapshot' : null,
                Schema::hasColumn('tagihan', 'bendahara_penerimaan_nip_snapshot') ? 'bendahara_penerimaan_nip_snapshot' : null,
            ]);

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
