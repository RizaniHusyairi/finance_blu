<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tipe tagihan baru KONTRAK_EKSTERNAL: tagihan atas kontrak yang dibuat dan
 * ditandatangani di luar sistem (mis. Surat Pesanan e-Purchasing/INAPROC).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tagihan MODIFY tipe_tagihan ENUM('PERJALDIN', 'KONTRAK', 'HONORARIUM', 'KONTRAK_EKSTERNAL') NOT NULL");
        }
        // sqlite (testing) menyimpan enum sebagai string + CHECK; tabel baru pada
        // sqlite test dibuat ulang dari awal, namun CHECK lama tetap menolak nilai
        // baru — bangun ulang kolom sebagai string agar nilai baru diterima.
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('tagihan', function (Blueprint $table) {
                $table->string('tipe_tagihan_tmp', 30)->default('KONTRAK');
            });
            DB::statement('UPDATE tagihan SET tipe_tagihan_tmp = tipe_tagihan');
            DB::statement('DROP INDEX IF EXISTS tagihan_tipe_tagihan_status_index');
            Schema::table('tagihan', function (Blueprint $table) {
                $table->dropColumn('tipe_tagihan');
            });
            Schema::table('tagihan', function (Blueprint $table) {
                $table->renameColumn('tipe_tagihan_tmp', 'tipe_tagihan');
            });
            try {
                Schema::table('tagihan', function (Blueprint $table) {
                    $table->index(['tipe_tagihan', 'status']);
                });
            } catch (\Throwable) {
                // Index masih ada dari rebuild sebelumnya — abaikan.
            }
        }

        if (! Schema::hasTable('detail_kontrak_eksternal')) {
            Schema::create('detail_kontrak_eksternal', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
                $table->string('nomor_surat_pesanan', 150);
                $table->date('tanggal_surat_pesanan')->nullable();
                $table->string('sumber', 100)->default('INAPROC');
                $table->string('nama_pekerjaan', 255);
                $table->unsignedSmallInteger('termin_ke')->nullable();
                $table->unsignedSmallInteger('total_termin')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_kontrak_eksternal');

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tagihan MODIFY tipe_tagihan ENUM('PERJALDIN', 'KONTRAK', 'HONORARIUM') NOT NULL");
        }
    }
};
