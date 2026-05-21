<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan_jasas', function (Blueprint $table) {
            if (! Schema::hasColumn('tagihan_jasas', 'mitra_jasa_id')) {
                $table->foreignId('mitra_jasa_id')
                    ->nullable()
                    ->after('mitra_id')
                    ->constrained('mitra_jasa')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('tagihan_jasas', 'kontrak_mitra_jasa_id')) {
                $table->foreignId('kontrak_mitra_jasa_id')
                    ->nullable()
                    ->after('mitra_jasa_id')
                    ->constrained('kontrak_mitra_jasa')
                    ->nullOnDelete();
            }
        });

        if (Schema::hasColumn('tagihan_jasas', 'mitra_id') && DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE tagihan_jasas MODIFY mitra_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        Schema::table('tagihan_jasas', function (Blueprint $table) {
            if (Schema::hasColumn('tagihan_jasas', 'kontrak_mitra_jasa_id')) {
                $table->dropConstrainedForeignId('kontrak_mitra_jasa_id');
            }

            if (Schema::hasColumn('tagihan_jasas', 'mitra_jasa_id')) {
                $table->dropConstrainedForeignId('mitra_jasa_id');
            }
        });
    }
};
