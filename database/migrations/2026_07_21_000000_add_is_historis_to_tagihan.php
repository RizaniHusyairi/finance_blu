<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda tagihan historis: arsip yang sudah selesai diproses di luar sistem
 * (SILABI) dan direkam ulang utuh — tanpa melewati alur verifikasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            $table->boolean('is_historis')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            $table->dropColumn('is_historis');
        });
    }
};
