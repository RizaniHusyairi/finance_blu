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
            if (!Schema::hasColumn('tagihan', 'ppspm_user_id')) {
                $table->foreignId('ppspm_user_id')->nullable()->after('ppk_nip_snapshot')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('tagihan', 'ppspm_nama_snapshot')) {
                $table->string('ppspm_nama_snapshot', 150)->nullable()->after('ppspm_user_id');
            }

            if (!Schema::hasColumn('tagihan', 'ppspm_nip_snapshot')) {
                $table->string('ppspm_nip_snapshot', 100)->nullable()->after('ppspm_nama_snapshot');
            }

            if (!Schema::hasColumn('tagihan', 'kasubbag_user_id')) {
                $table->foreignId('kasubbag_user_id')->nullable()->after('bendahara_pengeluaran_nip_snapshot')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('tagihan', 'kasubbag_nama_snapshot')) {
                $table->string('kasubbag_nama_snapshot', 150)->nullable()->after('kasubbag_user_id');
            }

            if (!Schema::hasColumn('tagihan', 'kasubbag_nip_snapshot')) {
                $table->string('kasubbag_nip_snapshot', 100)->nullable()->after('kasubbag_nama_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                if (Schema::hasColumn('tagihan', 'ppspm_user_id')) {
                    $table->dropForeign(['ppspm_user_id']);
                }

                if (Schema::hasColumn('tagihan', 'kasubbag_user_id')) {
                    $table->dropForeign(['kasubbag_user_id']);
                }
            }

            $columns = array_filter([
                Schema::hasColumn('tagihan', 'ppspm_user_id') ? 'ppspm_user_id' : null,
                Schema::hasColumn('tagihan', 'ppspm_nama_snapshot') ? 'ppspm_nama_snapshot' : null,
                Schema::hasColumn('tagihan', 'ppspm_nip_snapshot') ? 'ppspm_nip_snapshot' : null,
                Schema::hasColumn('tagihan', 'kasubbag_user_id') ? 'kasubbag_user_id' : null,
                Schema::hasColumn('tagihan', 'kasubbag_nama_snapshot') ? 'kasubbag_nama_snapshot' : null,
                Schema::hasColumn('tagihan', 'kasubbag_nip_snapshot') ? 'kasubbag_nip_snapshot' : null,
            ]);

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
