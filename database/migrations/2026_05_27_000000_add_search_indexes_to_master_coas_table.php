<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_coas', function (Blueprint $table) {
            $table->index('jenis_akun', 'master_coas_jenis_akun_index');
            $table->index('status_aktif', 'master_coas_status_aktif_index');
            $table->index(['kd_akun', 'kode_mak_lengkap'], 'master_coas_kd_akun_kode_mak_index');
        });
    }

    public function down(): void
    {
        Schema::table('master_coas', function (Blueprint $table) {
            $table->dropIndex('master_coas_jenis_akun_index');
            $table->dropIndex('master_coas_status_aktif_index');
            $table->dropIndex('master_coas_kd_akun_kode_mak_index');
        });
    }
};
