<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KP-02/KP-03/KP-04 — pemisahan tugas (SoD) kontrak pengadaan.
 *
 * Tambah kolom `diajukan_by` (id pengaju) pada kontrak & addendum agar gerbang
 * persetujuan dapat menegakkan maker != checker: pengaju tidak boleh menyetujui
 * dokumen yang ia ajukan sendiri — termasuk akun yang memegang peran ganda
 * (Pejabat Pengadaan + PPK) maupun Super Admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kontrak_pengadaan', function (Blueprint $table) {
            if (! Schema::hasColumn('kontrak_pengadaan', 'diajukan_by')) {
                $table->unsignedBigInteger('diajukan_by')->nullable()->after('diajukan_at');
            }
        });

        Schema::table('kontrak_addendum', function (Blueprint $table) {
            if (! Schema::hasColumn('kontrak_addendum', 'diajukan_by')) {
                $table->unsignedBigInteger('diajukan_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('kontrak_pengadaan', function (Blueprint $table) {
            if (Schema::hasColumn('kontrak_pengadaan', 'diajukan_by')) {
                $table->dropColumn('diajukan_by');
            }
        });

        Schema::table('kontrak_addendum', function (Blueprint $table) {
            if (Schema::hasColumn('kontrak_addendum', 'diajukan_by')) {
                $table->dropColumn('diajukan_by');
            }
        });
    }
};
