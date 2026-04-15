<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambahkan field header pada tabel tagihan
        Schema::table('tagihan', function (Blueprint $table) {
            $table->string('nomor_perjaldin', 100)->nullable()->after('nomor_tagihan');
            $table->date('tanggal_perjaldin')->nullable()->after('nomor_perjaldin');
        });

        // 2. Tambahkan field pada tabel detail_perjaldin
        Schema::table('detail_perjaldin', function (Blueprint $table) {
            $table->string('rekening', 100)->nullable()->after('tujuan');
        });

        // 3. Backfill ringan pada tabel tagihan
        // Cari tagihan PERJALDIN dengan deskripsi yang mungkin punya pola nomor surat legacy
        $tagihans = DB::table('tagihan')
            ->where('tipe_tagihan', 'PERJALDIN')
            ->whereNotNull('deskripsi')
            ->get();

        foreach ($tagihans as $tagihan) {
            $deskripsi = $tagihan->deskripsi;
            $newNomor = null;
            
            // Coba cari pola legacy
            if (str_contains($deskripsi, ' | Nomor Perjaldin: ')) {
                $parts = explode(' | Nomor Perjaldin: ', $deskripsi);
                if (count($parts) > 1) {
                    $newNomor = trim($parts[1]);
                }
            } elseif (str_contains($deskripsi, ' | BAST: ')) {
                $parts = explode(' | BAST: ', $deskripsi);
                if (count($parts) > 1) {
                    $newNomor = trim($parts[1]);
                }
            }

            if ($newNomor) {
                DB::table('tagihan')
                    ->where('id', $tagihan->id)
                    ->update([
                        'nomor_perjaldin' => $newNomor
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_perjaldin', function (Blueprint $table) {
            $table->dropColumn('rekening');
        });

        Schema::table('tagihan', function (Blueprint $table) {
            $table->dropColumn(['tanggal_perjaldin', 'nomor_perjaldin']);
        });
    }
};
