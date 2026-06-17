<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master item PNBP Umum (non-jasa) — 8 baris sesuai sheet "PNBP UMUM"
 * pada Rincian PNBP BLU. Nilainya tidak berasal dari modul jasa sehingga
 * disimpan/di-input tersendiri (lihat tabel pnbp_umum_realisasis).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pnbp_umum_items', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->nullable();
            $table->string('uraian');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $items = [
            'Pendapatan dari Pemindahtanganan BMN Lainnya',
            'Pendapatan Penggunaan Sarana dan Prasarana sesuai dengan Tusi',
            'Pendapatan Sewa Tanah, Gedung, dan Bangunan',
            'Pendapatan Denda Penyelesaian Pekerjaan Pemerintah',
            'Penerimaan Kembali Belanja Pegawai Tahun Anggaran Yang Lalu',
            'Penerimaan Kembali Belanja Barang Anggaran Tahun Lalu',
            'Penerimaan Kembali Belanja Modal Tahun Anggaran Yang Lalu',
            'Pendapatan Anggaran Lain-lain',
        ];

        $now = now();
        foreach ($items as $i => $uraian) {
            DB::table('pnbp_umum_items')->insert([
                'uraian' => $uraian,
                'urutan' => $i + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pnbp_umum_items');
    }
};
