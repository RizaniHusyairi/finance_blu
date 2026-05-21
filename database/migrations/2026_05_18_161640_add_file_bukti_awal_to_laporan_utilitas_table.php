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
        Schema::table('laporan_utilitas', function (Blueprint $table) {
            $table->string('file_bukti_awal')->nullable()->after('stan_akhir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_utilitas', function (Blueprint $table) {
            $table->dropColumn('file_bukti_awal');
        });
    }
};
