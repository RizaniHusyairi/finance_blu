<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('layanan_jasas', function (Blueprint $table) {
            if (! Schema::hasColumn('layanan_jasas', 'kode_jenis_pembayaran')) {
                $table->string('kode_jenis_pembayaran', 20)->nullable()->after('kode_mak');
            }
        });
    }

    public function down(): void
    {
        Schema::table('layanan_jasas', function (Blueprint $table) {
            if (Schema::hasColumn('layanan_jasas', 'kode_jenis_pembayaran')) {
                $table->dropColumn('kode_jenis_pembayaran');
            }
        });
    }
};
