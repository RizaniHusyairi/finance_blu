<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mitra_jasa_penjualan', function (Blueprint $table) {
            if (! Schema::hasColumn('mitra_jasa_penjualan', 'source_tagihan_jasa_id')) {
                $table->foreignId('source_tagihan_jasa_id')
                    ->nullable()
                    ->after('layanan_jasa_id')
                    ->constrained('tagihan_jasas')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('mitra_jasa_penjualan', function (Blueprint $table) {
            if (Schema::hasColumn('mitra_jasa_penjualan', 'source_tagihan_jasa_id')) {
                $table->dropConstrainedForeignId('source_tagihan_jasa_id');
            }
        });
    }
};
