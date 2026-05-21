<?php

use App\Enums\KategoriMutasiBank;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('detail_mutasi_bank', 'kategori_mutasi')) {
            Schema::table('detail_mutasi_bank', function (Blueprint $table) {
                $table->string('kategori_mutasi', 40)->nullable()->after('deskripsi');
                $table->index('kategori_mutasi');
            });
        }

        // Backfill klasifikasi awal untuk data existing berdasarkan deskripsi + arah.
        DB::table('detail_mutasi_bank')
            ->whereNull('kategori_mutasi')
            ->select('id', 'deskripsi', 'debit', 'kredit')
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $kategori = KategoriMutasiBank::classify(
                        $row->deskripsi,
                        (float) $row->debit,
                        (float) $row->kredit
                    );

                    if ($kategori !== null) {
                        DB::table('detail_mutasi_bank')
                            ->where('id', $row->id)
                            ->update(['kategori_mutasi' => $kategori->value]);
                    }
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('detail_mutasi_bank', 'kategori_mutasi')) {
            Schema::table('detail_mutasi_bank', function (Blueprint $table) {
                $table->dropIndex(['kategori_mutasi']);
                $table->dropColumn('kategori_mutasi');
            });
        }
    }
};
