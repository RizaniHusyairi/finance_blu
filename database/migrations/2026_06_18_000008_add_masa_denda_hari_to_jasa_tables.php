<?php

use App\Models\LayananJasa;
use App\Models\TagihanJasa;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Periode denda PJP2U: denda 2% dihitung per `masa_denda_hari` hari keterlambatan
 * (PJP2U = 30), dibulatkan ke atas dan terus berjalan. `masa_denda_hari = 0`
 * berarti memakai default 30 hari di accessor TagihanJasa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('layanan_jasas', function (Blueprint $table) {
            if (! Schema::hasColumn('layanan_jasas', 'masa_denda_hari')) {
                $table->unsignedSmallInteger('masa_denda_hari')->default(0)->after('masa_toleransi_hari');
            }
        });

        Schema::table('tagihan_jasas', function (Blueprint $table) {
            if (! Schema::hasColumn('tagihan_jasas', 'masa_denda_hari')) {
                $table->unsignedSmallInteger('masa_denda_hari')->default(0)->after('masa_toleransi_hari');
            }
        });

        // Layanan PJP2U: masa denda 30 hari.
        LayananJasa::query()
            ->with('parent.parent.parent')
            ->get()
            ->each(function (LayananJasa $layanan) {
                if ($layanan->isPjp2u()) {
                    DB::table('layanan_jasas')
                        ->where('id', $layanan->id)
                        ->update(['masa_denda_hari' => 30]);
                }
            });

        // Snapshot ke tagihan yang sudah ada: ambil masa denda terbesar dari layanan pada detailnya.
        TagihanJasa::withTrashed()
            ->with('details.layananJasa')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $tagihan) {
                    $masa = (int) $tagihan->details
                        ->pluck('layananJasa')
                        ->filter()
                        ->max('masa_denda_hari');

                    if ($masa > 0) {
                        DB::table('tagihan_jasas')
                            ->where('id', $tagihan->id)
                            ->update(['masa_denda_hari' => $masa]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('layanan_jasas', function (Blueprint $table) {
            if (Schema::hasColumn('layanan_jasas', 'masa_denda_hari')) {
                $table->dropColumn('masa_denda_hari');
            }
        });

        Schema::table('tagihan_jasas', function (Blueprint $table) {
            if (Schema::hasColumn('tagihan_jasas', 'masa_denda_hari')) {
                $table->dropColumn('masa_denda_hari');
            }
        });
    }
};
