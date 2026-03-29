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
        Schema::create('transaksi_penerimaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->constrained('master_mitra_vendor')->restrictOnDelete();
            $table->foreignId('master_coa_id')->constrained('master_coas')->restrictOnDelete();
            $table->string('nomor_invoice', 100)->unique();
            $table->date('tanggal_jatuh_tempo');
            $table->decimal('nominal_tagihan', 15, 2);
            $table->decimal('nominal_denda_keterlambatan', 15, 2)->default(0);
            $table->decimal('total_dibayar', 15, 2)->default(0);
            $table->enum('status_pembayaran', ['UNPAID', 'PARTIAL', 'PAID'])->default('UNPAID');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_penerimaan');
    }
};
