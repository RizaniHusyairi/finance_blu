<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mitra_jasa_penjualan', function (Blueprint $table) {
            $table->json('penerbangan_details')->nullable()->after('nomor_penerbangan');
        });
    }

    public function down(): void
    {
        Schema::table('mitra_jasa_penjualan', function (Blueprint $table) {
            $table->dropColumn('penerbangan_details');
        });
    }
};
