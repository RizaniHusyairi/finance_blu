<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dokumen_spp', function (Blueprint $table) {
            $table->foreignId('tagihan_perjaldin_komponen_id')
                ->nullable()
                ->after('tagihan_id')
                ->constrained('tagihan_perjaldin_komponen')
                ->nullOnDelete();

            $table->string('komponen_biaya', 50)
                ->nullable()
                ->after('tagihan_perjaldin_komponen_id')
                ->comment('TIKET, TRANSPORT, PENGINAPAN, UANG_HARIAN, UANG_REPRESENTASI');
        });

        // Unique: satu komponen hanya boleh punya max 1 SPP aktif (non-soft-deleted).
        // Karena kolom nullable dan bisa ada SPP kontrak/honor tanpa komponen,
        // kita gunakan unique index biasa. MySQL mengizinkan multiple NULL pada unique index.
        Schema::table('dokumen_spp', function (Blueprint $table) {
            $table->unique('tagihan_perjaldin_komponen_id', 'uk_spp_komponen');
        });
    }

    public function down(): void
    {
        Schema::table('dokumen_spp', function (Blueprint $table) {
            $table->dropUnique('uk_spp_komponen');
            $table->dropForeign(['tagihan_perjaldin_komponen_id']);
            $table->dropColumn(['tagihan_perjaldin_komponen_id', 'komponen_biaya']);
        });
    }
};
