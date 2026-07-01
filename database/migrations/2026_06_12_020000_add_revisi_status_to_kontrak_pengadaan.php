<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tombol "Kembalikan / Revisi" PPK menyetel status_kontrak = REVISI,
 * tetapi nilai itu tidak ada di ENUM sehingga query selalu gagal
 * (SQLSTATE 01000: Data truncated). Tambahkan REVISI ke ENUM.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ENUM lewat DDL mentah hanya didukung MySQL; di sqlite (test) kolom
        // status_kontrak sudah berupa string sehingga langkah ini dilewati.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE kontrak_pengadaan MODIFY status_kontrak " .
            "ENUM('DRAFT','PENDING_REVIEW','REVISI','AKTIF','SELESAI','DIBATALKAN') NOT NULL DEFAULT 'DRAFT'"
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Kembalikan kontrak REVISI ke DRAFT sebelum menghapus nilai dari ENUM.
        DB::table('kontrak_pengadaan')->where('status_kontrak', 'REVISI')->update(['status_kontrak' => 'DRAFT']);

        DB::statement(
            "ALTER TABLE kontrak_pengadaan MODIFY status_kontrak " .
            "ENUM('DRAFT','PENDING_REVIEW','AKTIF','SELESAI','DIBATALKAN') NOT NULL DEFAULT 'DRAFT'"
        );
    }
};
