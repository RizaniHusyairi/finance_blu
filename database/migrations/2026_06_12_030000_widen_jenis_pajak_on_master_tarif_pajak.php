<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nama jenis pajak per leaflet PMK 59/2022 lebih deskriptif
 * (mis. "PPh 4(2) Perencanaan/Pengawasan Konstruksi — Berkualifikasi"),
 * melebihi 50 karakter.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_tarif_pajak', function (Blueprint $table) {
            $table->string('jenis_pajak', 120)->change();
        });
    }

    public function down(): void
    {
        Schema::table('master_tarif_pajak', function (Blueprint $table) {
            $table->string('jenis_pajak', 50)->change();
        });
    }
};
