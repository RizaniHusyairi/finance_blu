<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('layanan_jasas', 'kode_satker')) {
            Schema::table('layanan_jasas', function (Blueprint $table) {
                $table->string('kode_satker', 20)->nullable()->after('kode_akun');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('layanan_jasas', 'kode_satker')) {
            Schema::table('layanan_jasas', function (Blueprint $table) {
                $table->dropColumn('kode_satker');
            });
        }
    }
};
