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
        Schema::create('spps', function (Blueprint $table) {
            $table->id('spp_id');
            // Polymorphic relation
            $table->morphs('sppable'); 
            
            // Kategori dan Nominal SPP
            $table->string('kategori_biaya')->nullable(); // Tiket, Transport, Penginapan, Uang Harian, Uang Representasi
            $table->decimal('jumlah_uang', 20, 2)->default(0); // Nominal yang di claim khusus SPP ini
            $table->text('uraian')->nullable(); // Belanja Barang Perjalanan Dinas Pegawai - [Kategori]
            
            // Data Form SPP
            $table->string('nomor_spp');
            $table->date('tanggal_spp');
            $table->string('tahun_anggaran')->default(date('Y'));
            $table->string('nomor_dipa');
            $table->date('tanggal_dipa');
            $table->string('no_kontrak')->nullable();
            $table->date('tgl_kontrak')->nullable();
            $table->string('akun_mak');
            $table->string('jenis_tagihan')->default('NON REMUNERASI');
            $table->string('jatuh_tempo')->default('Segera');
            $table->string('cara_bayar')->default('SP2D BLU - TRF');
            $table->string('penandatangan_nama');
            $table->string('penandatangan_nip');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spps');
    }
};
