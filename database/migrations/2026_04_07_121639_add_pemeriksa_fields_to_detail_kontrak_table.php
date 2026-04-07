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
            $table->date('tanggal_invoice')->nullable()->after('tagihan_id');
            $table->string('nama_pemeriksa', 150)->nullable()->after('tanggal_bap');
            $table->string('nip_pemeriksa', 50)->nullable()->after('nama_pemeriksa');
            $table->string('jabatan_pemeriksa', 150)->nullable()->after('nip_pemeriksa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_kontrak', function (Blueprint $table) {
            $table->dropColumn(['tanggal_invoice', 'nama_pemeriksa', 'nip_pemeriksa', 'jabatan_pemeriksa']);
        });
    }
};
