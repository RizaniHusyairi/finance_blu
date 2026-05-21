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
            $table->enum('tipe_perhitungan', ['kwh', 'flat'])->default('kwh')->after('jenis');
            $table->string('file_bukti')->nullable()->after('stan_akhir');
            
            // Make existing columns nullable
            $table->integer('stan_awal')->nullable()->change();
            $table->integer('stan_akhir')->nullable()->change();
            $table->decimal('tarif_per_unit', 15, 2)->nullable()->change();
            $table->decimal('total_biaya', 15, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_utilitas', function (Blueprint $table) {
            $table->dropColumn(['tipe_perhitungan', 'file_bukti']);
            
            $table->integer('stan_awal')->nullable(false)->change();
            $table->integer('stan_akhir')->nullable(false)->change();
            $table->decimal('tarif_per_unit', 15, 2)->nullable(false)->change();
            $table->decimal('total_biaya', 15, 2)->nullable(false)->change();
        });
    }
};
