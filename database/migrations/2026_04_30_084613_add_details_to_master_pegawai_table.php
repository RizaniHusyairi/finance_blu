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
        Schema::table('master_pegawai', function (Blueprint $table) {
            $table->string('nik', 50)->nullable()->after('nip');
            $table->string('nomor_hp', 50)->nullable()->after('npwp');
            $table->string('nama_bank', 100)->nullable()->after('nomor_hp');
            $table->string('nomor_rekening', 100)->nullable()->after('nama_bank');
            $table->string('nama_rekening', 150)->nullable()->after('nomor_rekening');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_pegawai', function (Blueprint $table) {
            $table->dropColumn(['nik', 'nomor_hp', 'nama_bank', 'nomor_rekening', 'nama_rekening']);
        });
    }
};
