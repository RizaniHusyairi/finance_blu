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
        Schema::create('master_mitra_vendor', function (Blueprint $table) {
            $table->id();
            $table->enum('kategori', ['VENDOR_PENGELUARAN', 'MITRA_PENERIMAAN', 'KEDUANYA'])->default('VENDOR_PENGELUARAN');
            $table->string('tipe_supplier', 50)->nullable(); // 01 Satker, 02 Penyedia, dll
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('npwp', 30)->nullable();
            $table->string('nama_perusahaan', 150);
            $table->string('nama_direktur', 100)->nullable(); // Nullable karena bisa Satker/Pegawai
            $table->text('alamat')->nullable();
            $table->string('no_telepon', 30)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_mitra_vendor');
    }
};
