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
            $table->string('nomor_spm')->nullable()->after('status_spp');
            $table->date('tanggal_spm')->nullable()->after('nomor_spm');
            $table->string('penandatangan_spm_nama')->nullable()->after('tanggal_spm');
            $table->string('penandatangan_spm_nip')->nullable()->after('penandatangan_spm_nama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spps', function (Blueprint $table) {
            $table->dropColumn([
                'nomor_spm',
                'tanggal_spm',
                'penandatangan_spm_nama',
                'penandatangan_spm_nip'
            ]);
        });
    }
};
