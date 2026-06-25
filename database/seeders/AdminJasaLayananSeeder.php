<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penugasan Admin Jasa → Layanan Jasa (tabel admin_jasa_layanan).
 *
 * Direkonstruksi dari produksi. Dipetakan via `kode_layanan` (BUKAN id) karena
 * id layanan_jasas tidak stabil — MasterLayananJasaSeeder bersifat replace-only
 * sehingga id berubah tiap kali diseed ulang.
 *
 * Prasyarat: [[UserAccountSeeder]] + [[MasterLayananJasaSeeder]] sudah jalan.
 * Karena itu seeder ini dipanggil SETELAH MasterLayananJasaSeeder di
 * DatabaseSeeder.
 *
 * Aman: user / layanan yang tidak ditemukan dilewati + diperingatkan (mis.
 * layanan "E. Lainnya" yang dibuat manual lewat UI dan di luar berkas seed).
 *
 * Idempoten: penugasan (user_id + layanan_jasa_id) yang sudah ada dilewati.
 */
class AdminJasaLayananSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('admin_jasa_layanan')) {
            $this->command?->warn('Tabel admin_jasa_layanan belum ada. Seeder dilewati.');
            return;
        }

        $path = database_path('data/admin-jasa-layanan-seed.json');
        if (! file_exists($path)) {
            $this->command?->error("File seed tidak ditemukan: {$path}");
            return;
        }

        $map = json_decode(file_get_contents($path), true);
        if (! is_array($map) || $map === []) {
            $this->command?->warn('File seed admin_jasa_layanan kosong atau tidak valid.');
            return;
        }

        $now = Carbon::now();
        $inserted = 0;
        $skippedUser = 0;
        $missingLayanan = [];

        foreach ($map as $email => $kodeList) {
            $userId = User::where('email', $email)->value('id');
            if (! $userId) {
                $this->command?->warn("⚠ User {$email} tidak ditemukan. Penugasannya dilewati.");
                $skippedUser++;
                continue;
            }

            foreach ((array) $kodeList as $kode) {
                $layananId = DB::table('layanan_jasas')->where('kode_layanan', $kode)->value('id');
                if (! $layananId) {
                    $missingLayanan[$kode] = true;
                    continue;
                }

                $already = DB::table('admin_jasa_layanan')
                    ->where('user_id', $userId)
                    ->where('layanan_jasa_id', $layananId)
                    ->exists();
                if ($already) {
                    continue;
                }

                DB::table('admin_jasa_layanan')->insert([
                    'user_id' => $userId,
                    'layanan_jasa_id' => $layananId,
                    'status_aktif' => true,
                    'created_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $inserted++;
            }
        }

        $this->command?->info("✓ admin_jasa_layanan: {$inserted} penugasan dibuat, {$skippedUser} user dilewati.");

        if ($missingLayanan !== []) {
            $kodes = array_keys($missingLayanan);
            $preview = implode(', ', array_slice($kodes, 0, 8));
            $this->command?->warn('  ' . count($kodes) . ' kode layanan tidak ditemukan (dilewati): ' . $preview . (count($kodes) > 8 ? ', …' : ''));
        }
    }
}
