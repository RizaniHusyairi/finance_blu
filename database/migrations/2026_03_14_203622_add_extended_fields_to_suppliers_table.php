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
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('id_supplier')->nullable()->after('id');
            $table->string('npwp_status')->default('Belum Ada')->after('npwp'); // Tersedia, Terlampir, Belum Ada
            $table->string('rekening_status')->default('Belum Ada')->after('account_name'); // Tersedia, Terlampir, Belum Ada
            $table->string('status')->default('Aktif')->after('phone'); // Aktif, Nonaktif
            $table->text('catatan')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['id_supplier', 'npwp_status', 'rekening_status', 'status', 'catatan']);
        });
    }
};
