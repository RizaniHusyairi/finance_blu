<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spps', function (Blueprint $table) {
            // Kolom NPI (Nota Pemindahbukuan Internal)
            $table->string('nomor_npi')->nullable()->after('penandatangan_spm_nip');
            $table->date('tanggal_npi')->nullable()->after('nomor_npi');

            // Kolom SP2D (Surat Perintah Pencairan Dana)
            $table->string('nomor_sp2d')->nullable()->after('tanggal_npi');
            $table->date('tanggal_sp2d')->nullable()->after('nomor_sp2d');

            // Catatan BKU
            $table->text('catatan_bku')->nullable()->after('tanggal_sp2d');
        });
    }

    public function down(): void
    {
        Schema::table('spps', function (Blueprint $table) {
            $table->dropColumn(['nomor_npi', 'tanggal_npi', 'nomor_sp2d', 'tanggal_sp2d', 'catatan_bku']);
        });
    }
};
