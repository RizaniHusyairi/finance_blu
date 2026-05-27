<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mitra_jasa_penjualan', function (Blueprint $table) {
            $table->unsignedInteger('pax_transit')->default(0)->after('pax_bayi');
        });
    }

    public function down(): void
    {
        Schema::table('mitra_jasa_penjualan', function (Blueprint $table) {
            $table->dropColumn('pax_transit');
        });
    }
};
