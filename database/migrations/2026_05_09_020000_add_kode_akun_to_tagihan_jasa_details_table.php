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
        Schema::table('tagihan_jasa_details', function (Blueprint $table) {
            if (! Schema::hasColumn('tagihan_jasa_details', 'kode_akun')) {
                $table->string('kode_akun')->nullable()->after('layanan_jasa_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tagihan_jasa_details', function (Blueprint $table) {
            if (Schema::hasColumn('tagihan_jasa_details', 'kode_akun')) {
                $table->dropColumn('kode_akun');
            }
        });
    }
};
