<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mitra_jasa_penjualan', function (Blueprint $table) {
            $table->string('nomor_penerbangan')->nullable()->after('total_transaksi');
            $table->unsignedInteger('pax_dewasa')->default(0)->after('nomor_penerbangan');
            $table->unsignedInteger('pax_anak')->default(0)->after('pax_dewasa');
            $table->unsignedInteger('pax_bayi')->default(0)->after('pax_anak');
            $table->unsignedInteger('total_pax')->default(0)->after('pax_bayi');
        });
    }

    public function down(): void
    {
        Schema::table('mitra_jasa_penjualan', function (Blueprint $table) {
            $table->dropColumn(['nomor_penerbangan', 'pax_dewasa', 'pax_anak', 'pax_bayi', 'total_pax']);
        });
    }
};
