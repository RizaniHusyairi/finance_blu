<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Setup pembukuan: identitas satker (kop dokumen BKU) + saldo awal per buku.
 *
 * - `pembukuan_setup`     : satu baris identitas satker (kop) — sheet SETUP File 1
 *                           & kop File 2 (K/L, unit org, satker, DIPA, KPPN, TA).
 * - `pembukuan_saldo_awal`: saldo awal per (rekening, kode_buku, peran) berlaku
 *                           sejak tanggal tertentu — jadi titik mulai saldo berjalan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pembukuan_setup')) {
            Schema::create('pembukuan_setup', function (Blueprint $table) {
                $table->id();
                $table->string('nama_kl', 150)->nullable();
                $table->string('kode_kl', 10)->nullable();
                $table->string('nama_unit_org', 150)->nullable();
                $table->string('kode_unit_org', 10)->nullable();
                $table->string('nama_satker', 200)->nullable();
                $table->string('kode_satker', 12)->nullable();
                $table->string('propinsi', 150)->nullable();
                $table->string('nomor_dipa', 150)->nullable();
                $table->date('tanggal_dipa')->nullable();
                $table->string('nama_kppn', 100)->nullable();
                $table->string('kode_kppn', 10)->nullable();
                $table->unsignedSmallInteger('tahun_anggaran')->nullable();
                $table->string('nama_bendahara_penerimaan', 150)->nullable();
                $table->string('nama_bendahara_pengeluaran', 150)->nullable();
                $table->string('nama_kpa', 150)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pembukuan_saldo_awal')) {
            Schema::create('pembukuan_saldo_awal', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rekening_bank_id')->nullable()->constrained('rekening_bank')->nullOnDelete();
                $table->unsignedSmallInteger('kode_buku')->default(1);
                $table->string('peran', 20); // PENERIMAAN | PENGELUARAN
                $table->date('tanggal_berlaku');
                $table->decimal('nominal', 18, 2)->default(0);
                $table->timestamps();

                $table->index(['rekening_bank_id', 'kode_buku', 'peran']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pembukuan_saldo_awal');
        Schema::dropIfExists('pembukuan_setup');
    }
};
