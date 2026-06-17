<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan klasifikasi PNBP (AERO / NON_AERO) ke pohon layanan_jasas.
 *
 * Klasifikasi diturunkan dari root (level 1) sesuai PM Perhubungan:
 *   - "Tarif Jasa Kebandarudaraan"      => AERO     (jasa aeronautika)
 *   - "Tarif Jasa Terkait Bandar Udara" => NON_AERO (jasa non-aeronautika)
 *   - "Izin Daerah Keamanan Terbatas"   => NON_AERO (PAS, izin mengemudi)
 * Nilai didenormalisasi ke seluruh keturunan agar agregasi monitoring ringan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('layanan_jasas', function (Blueprint $table) {
            if (! Schema::hasColumn('layanan_jasas', 'kelompok_pnbp')) {
                $table->string('kelompok_pnbp', 12)->nullable()->index()->after('tipe_layanan');
            }
        });

        // Tetapkan kelompok pada root (level 1) berdasar nama layanan.
        $roots = DB::table('layanan_jasas')->whereNull('parent_id')->get(['id', 'nama_layanan']);

        foreach ($roots as $root) {
            $kelompok = stripos($root->nama_layanan, 'Kebandarudaraan') !== false ? 'AERO' : 'NON_AERO';

            $ids = collect([$root->id]);
            // Telusuri ke bawah pohon, isi seluruh keturunan dengan kelompok root-nya.
            while ($ids->isNotEmpty()) {
                DB::table('layanan_jasas')->whereIn('id', $ids)->update(['kelompok_pnbp' => $kelompok]);
                $ids = DB::table('layanan_jasas')->whereIn('parent_id', $ids)->pluck('id');
            }
        }
    }

    public function down(): void
    {
        Schema::table('layanan_jasas', function (Blueprint $table) {
            if (Schema::hasColumn('layanan_jasas', 'kelompok_pnbp')) {
                $table->dropColumn('kelompok_pnbp');
            }
        });
    }
};
