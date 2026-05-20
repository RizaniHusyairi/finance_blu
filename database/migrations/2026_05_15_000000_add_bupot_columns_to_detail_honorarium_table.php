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
        Schema::table('detail_honorarium', function (Blueprint $table) {
            $table->string('bupot_status', 20)->default('DRAFT')->after('pph');
            $table->string('nomor_bupot', 50)->nullable()->after('bupot_status');

            $table->index('bupot_status', 'detail_honorarium_bupot_status_index');
            $table->unique('nomor_bupot', 'detail_honorarium_nomor_bupot_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_honorarium', function (Blueprint $table) {
            $table->dropUnique('detail_honorarium_nomor_bupot_unique');
            $table->dropIndex('detail_honorarium_bupot_status_index');
            $table->dropColumn(['bupot_status', 'nomor_bupot']);
        });
    }
};
