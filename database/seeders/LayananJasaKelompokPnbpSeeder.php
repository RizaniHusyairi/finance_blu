<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Klasifikasi PNBP (AERO / NON_AERO) pada pohon layanan_jasas — diturunkan dari
 * root (level 1) sesuai PM Perhubungan:
 *   - "…Jasa Kebandarudaraan…"        => AERO     (jasa aeronautika)
 *   - "…Jasa Terkait Bandar Udara…"   => NON_AERO (jasa non-aeronautika)
 *   - "…Izin Daerah Keamanan…"        => NON_AERO (PAS / izin)
 *
 * Nilai didenormalisasi ke seluruh keturunan agar agregasi monitoring PNBP
 * (lihat App\Http\Controllers\ManajemenPnbpController) ringan.
 *
 * Dijalankan SETELAH MasterLayananJasaSeeder (yang me-replace seluruh pohon dan
 * tidak mengisi kelompok_pnbp). NON-DESTRUKTIF & idempoten — hanya meng-update
 * kolom kelompok_pnbp, aman dipanggil ulang tanpa menyentuh relasi lain.
 */
class LayananJasaKelompokPnbpSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasColumn('layanan_jasas', 'kelompok_pnbp')) {
            $this->command?->warn('⚠ Kolom kelompok_pnbp belum ada. Jalankan migrasi dulu.');
            return;
        }

        $roots = DB::table('layanan_jasas')->whereNull('parent_id')->get(['id', 'nama_layanan']);
        $total = 0;

        foreach ($roots as $root) {
            $kelompok = stripos($root->nama_layanan, 'Kebandarudaraan') !== false ? 'AERO' : 'NON_AERO';

            // Telusuri ke bawah pohon, isi seluruh keturunan dengan kelompok root-nya.
            $ids = collect([$root->id]);
            while ($ids->isNotEmpty()) {
                $total += DB::table('layanan_jasas')->whereIn('id', $ids)->update(['kelompok_pnbp' => $kelompok]);
                $ids = DB::table('layanan_jasas')->whereIn('parent_id', $ids)->pluck('id');
            }

            $this->command?->info("✓ {$root->nama_layanan} → {$kelompok}");
        }

        $this->command?->info("Selesai: {$total} layanan diklasifikasi AERO/NON-AERO.");
    }
}
