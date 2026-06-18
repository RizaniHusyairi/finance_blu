<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master "Kode Transaksi" — mengikuti sheet `Referensi` File 1 (SILABI).
 *
 * Tiap kode (A, B, C1, …, R2) menggerakkan distribusi satu baris jurnal
 * `transaksi_pembukuan` ke BKU + buku pembantu terkait. Aturan distribusi
 * disimpan pada kolom JSON `posting_rules`:
 *   [ { "kode_buku": 1, "arah": "TERIMA", "sign": 1 }, ... ]
 * di mana arah TERIMA = penerimaan (debit kas), KELUAR = pengeluaran (kredit kas),
 * sign -1 dipakai untuk koreksi (mis. terima sisa UM perjadin yang mengurangi).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kode_transaksi')) {
            Schema::create('kode_transaksi', function (Blueprint $table) {
                $table->id();
                $table->string('kode', 8)->unique();
                $table->string('uraian', 255);
                // UP | TU | GU | LS | NIHIL | PENGESAHAN | null
                $table->string('jenis_pembayaran', 20)->nullable();
                // 1 Pengeluaran Anggaran, 2 Pengembalian, 3 PFK, dst (sheet Referensi)
                $table->string('sifat_pembayaran', 50)->nullable();
                $table->json('posting_rules')->nullable();
                $table->unsignedSmallInteger('urutan')->default(0);
                $table->boolean('status_aktif')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kode_transaksi');
    }
};
