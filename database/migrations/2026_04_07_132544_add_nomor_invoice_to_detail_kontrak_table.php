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
        Schema::table('detail_kontrak', function (Blueprint $table) {
            $table->string('nomor_invoice', 100)->nullable()->after('tanggal_invoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_kontrak', function (Blueprint $table) {
            $table->dropColumn('nomor_invoice');
        });
    }
};
