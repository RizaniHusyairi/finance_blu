<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buku_kas_umum', function (Blueprint $table) {
            $table->unique(['referensi_pengeluaran_id', 'nomor_bukti'], 'bku_ref_pengeluaran_nomor_bukti_unq');
        });
    }

    public function down(): void
    {
        Schema::table('buku_kas_umum', function (Blueprint $table) {
            $table->dropUnique('bku_ref_pengeluaran_nomor_bukti_unq');
        });
    }
};
