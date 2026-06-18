<?php

namespace Database\Seeders;

use App\Models\PembukuanSetup;
use Illuminate\Database\Seeder;

/**
 * Identitas satker (kop dokumen BKU) — diambil dari kop File 2
 * (BKU Penerimaan BLU UPBU APT Pranoto Samarinda). Idempoten (singleton).
 */
class PembukuanSetupSeeder extends Seeder
{
    public function run(): void
    {
        if (PembukuanSetup::query()->exists()) {
            return;
        }

        PembukuanSetup::create([
            'nama_kl' => 'KEMENTERIAN PERHUBUNGAN',
            'kode_kl' => '022',
            'nama_unit_org' => 'DITJEN PERHUBUNGAN UDARA',
            'kode_unit_org' => '05',
            'nama_satker' => 'BLU KANTOR UPBU KELAS I AJI PANGERAN TUMENGGUNG PRANOTO',
            'kode_satker' => '288745',
            'propinsi' => 'KOTA SAMARINDA KALIMANTAN TIMUR',
            'nomor_dipa' => 'SP DIPA-022.05.2.288745/2026',
            'tanggal_dipa' => '2025-12-01',
            'nama_kppn' => 'KPPN SAMARINDA',
            'kode_kppn' => '047',
            'tahun_anggaran' => 2026,
        ]);
    }
}
