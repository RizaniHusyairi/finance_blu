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
        Schema::table('spps', function (Blueprint $table) {
            $table->string('status_spp')->default('Menunggu Verifikasi')->after('uraian');
            $table->text('catatan_revisi')->nullable()->after('status_spp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spps', function (Blueprint $table) {
            $table->dropColumn(['status_spp', 'catatan_revisi']);
        });
    }
};
