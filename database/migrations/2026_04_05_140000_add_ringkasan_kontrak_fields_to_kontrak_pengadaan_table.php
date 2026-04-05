<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kontrak_pengadaan', function (Blueprint $table) {
            if (!Schema::hasColumn('kontrak_pengadaan', 'masa_pemeliharaan_hari')) {
                $table->unsignedInteger('masa_pemeliharaan_hari')
                    ->default(0)
                    ->after('tanggal_selesai');
            }

            if (!Schema::hasColumn('kontrak_pengadaan', 'ketentuan_denda')) {
                $table->text('ketentuan_denda')
                    ->nullable()
                    ->after('masa_pemeliharaan_hari');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kontrak_pengadaan', function (Blueprint $table) {
            if (Schema::hasColumn('kontrak_pengadaan', 'ketentuan_denda')) {
                $table->dropColumn('ketentuan_denda');
            }

            if (Schema::hasColumn('kontrak_pengadaan', 'masa_pemeliharaan_hari')) {
                $table->dropColumn('masa_pemeliharaan_hari');
            }
        });
    }
};
