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
        Schema::table('detail_perjaldin', function (Blueprint $table) {
            $table->unsignedBigInteger('provinsi_id')->nullable()->after('no_spt');
            $table->string('tipe_perjalanan')->nullable()->after('provinsi_id');
            
            $table->foreign('provinsi_id')->references('id')->on('master_uang_harian_perjaldins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_perjaldin', function (Blueprint $table) {
            $table->dropForeign(['provinsi_id']);
            $table->dropColumn(['provinsi_id', 'tipe_perjalanan']);
        });
    }
};
