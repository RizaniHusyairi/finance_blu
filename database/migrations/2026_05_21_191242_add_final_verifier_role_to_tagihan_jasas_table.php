<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahkan kolom final_verifier_role ke tabel tagihan_jasas.
 *
 * Kolom ini menentukan role yang melakukan verifikasi final (TTD Kabandara)
 * pada workflow tagihan jasa: bisa "KPA" atau "PLT/PLH".
 *
 * Default 'KPA' agar data eksisting tetap kompatibel dengan workflow lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan_jasas', function (Blueprint $table) {
            if (! Schema::hasColumn('tagihan_jasas', 'final_verifier_role')) {
                $table->string('final_verifier_role', 30)->default('KPA')->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tagihan_jasas', function (Blueprint $table) {
            if (Schema::hasColumn('tagihan_jasas', 'final_verifier_role')) {
                $table->dropColumn('final_verifier_role');
            }
        });
    }
};
