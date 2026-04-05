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
            if (!Schema::hasColumn('kontrak_pengadaan', 'tanggal_mulai_pemeliharaan')) {
                $table->date('tanggal_mulai_pemeliharaan')
                    ->nullable()
                    ->after('masa_pemeliharaan_hari');
            }

            if (!Schema::hasColumn('kontrak_pengadaan', 'tanggal_selesai_pemeliharaan')) {
                $table->date('tanggal_selesai_pemeliharaan')
                    ->nullable()
                    ->after('tanggal_mulai_pemeliharaan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kontrak_pengadaan', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('kontrak_pengadaan', 'tanggal_selesai_pemeliharaan')) {
                $dropColumns[] = 'tanggal_selesai_pemeliharaan';
            }

            if (Schema::hasColumn('kontrak_pengadaan', 'tanggal_mulai_pemeliharaan')) {
                $dropColumns[] = 'tanggal_mulai_pemeliharaan';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
