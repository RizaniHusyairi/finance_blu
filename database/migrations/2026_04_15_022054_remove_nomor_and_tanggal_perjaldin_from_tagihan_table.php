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
        Schema::table('tagihan', function (Blueprint $table) {
            $table->dropColumn(['nomor_perjaldin', 'tanggal_perjaldin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            $table->string('nomor_perjaldin', 100)->nullable()->after('nomor_tagihan');
            $table->date('tanggal_perjaldin')->nullable()->after('nomor_perjaldin');
        });
    }
};
