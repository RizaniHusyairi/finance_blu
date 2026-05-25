<?php

namespace Database\Seeders;

use App\Models\LayananJasa;
use App\Models\MasterPegawai;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder dua user dummy bertipe Admin Jasa, masing-masing dipasangkan
 * ke beberapa layanan jasa berbeda. Berguna untuk testing fitur
 * "Atur Layanan Admin Jasa" tanpa harus klik manual lewat UI.
 *
 * Strategi pemilihan layanan:
 *  - Admin 1 → kelompok layanan "Pendaratan + Penempatan + Penyimpanan + Garbarata"
 *  - Admin 2 → kelompok layanan "PJP2U + JKP2U + Check-in Counter + Sarana Prasarana"
 *
 * HANYA layanan yang berstatus leaf (is_leaf=true) dan memiliki tarif_dasar > 0
 * yang akan ditugaskan kepada admin — yaitu item tarif yang benar-benar dapat
 * dipakai untuk membuat tagihan. Layanan yang merupakan grouping/kategori
 * (parent) atau tidak memiliki tarif akan dilewati.
 *
 * Layanan ditemukan dengan cara: cari root group berdasarkan kode/nama, lalu
 * BFS turun ke seluruh keturunan dan filter yang `is_leaf` + `tarif_dasar > 0`.
 */
class AdminJasaSampleSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            [
                'nama'  => 'DIMAS ADITYARAMA',
                'email' => 'admin.jasa1@sikeren.id',
                'label' => 'ADMIN JASA #1',
                // Root group yang dikelola — seluruh leaf bertarif di bawahnya akan diassign.
                'roots' => [
                    ['kode' => 'LAND-ROOT',  'nama' => 'C. JASA PENDARATAN PESAWAT UDARA'],
                    ['kode' => 'PARK-ROOT',  'nama' => 'D. JASA PENEMPATAN PESAWAT UDARA'],
                    ['kode' => 'STORE-ROOT', 'nama' => 'E. JASA PENYIMPANAN PESAWAT UDARA'],
                    ['kode' => 'AVIO-ROOT',  'nama' => 'H. JASA PEMAKAIAN GARBARATA (AVIOBRIDGE)'],
                ],
            ],
            [
                'nama'  => 'YOGIK MARSANTIA HERMAWAN',
                'email' => 'admin.jasa2@sikeren.id',
                'label' => 'ADMIN JASA #2',
                'roots' => [
                    ['kode' => 'PJPU-ROOT',    'nama' => 'B. PELAYANAN JASA PENUMPANG PESAWAT UDARA (PJP2U)'],
                    ['kode' => 'JKP2U-ROOT',   'nama' => 'J. JASA KARGO DAN POS PESAWAT UDARA (JKP2U)'],
                    ['kode' => 'CHECKIN-ROOT', 'nama' => 'I. JASA PEMAKAIAN TEMPAT PELAPORAN KEBERANGKATAN (CHECK-IN COUNTER)'],
                    ['kode' => 'KSP-ROOT',     'nama' => 'K. PENGGUNAAN SARANA DAN PRASARANA DI BANDAR UDARA BERDASARKAN TUGAS DAN FUNGSI'],
                ],
            ],
        ];

        foreach ($admins as $admin) {
            $user = $this->seedAdminAccount($admin['nama'], $admin['email'], $admin['label']);

            if (! $user) {
                continue;
            }

            $layananIds = $this->resolveLeafLayananIds($admin['roots']);

            if (empty($layananIds)) {
                $this->command->warn("  ! Tidak ada leaf layanan bertarif di bawah root yang dipilih untuk {$admin['email']}.");
                continue;
            }

            $this->syncAssignment($user, $layananIds);

            $this->command->info("  → terhubung ke " . count($layananIds) . " layanan bertarif.");
        }
    }

    /**
     * Buat / update user dengan role Admin Jasa, terhubung ke MasterPegawai
     * (sama seperti pola di UserAccountSeeder).
     */
    private function seedAdminAccount(string $nama, string $email, string $label): ?User
    {
        $pegawai = MasterPegawai::where('nama_lengkap', $nama)->first();

        if (! $pegawai) {
            $this->command->warn("Pegawai '{$nama}' tidak ditemukan di master_pegawai. {$label} dilewati.");
            return null;
        }

        $user = User::where('profilable_type', MasterPegawai::class)
            ->where('profilable_id', $pegawai->id)
            ->first();

        if ($user) {
            $user->update([
                'email' => $email,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        } else {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'password' => Hash::make('password'),
                    'profilable_type' => MasterPegawai::class,
                    'profilable_id' => $pegawai->id,
                    'email_verified_at' => now(),
                ]
            );
        }

        $user->syncRoles(['Admin Jasa']);

        $this->command->info("✓ {$label} → {$email} [{$nama}]");

        return $user;
    }

    /**
     * Mengembalikan ID seluruh leaf-node bertarif (tarif_dasar > 0) di bawah
     * setiap root yang diberikan. Root sendiri tidak ikut diassign.
     *
     * @param array<int, array{kode?: string, nama?: string}> $roots
     * @return array<int, int>
     */
    private function resolveLeafLayananIds(array $roots): array
    {
        // 1. Cari root IDs.
        $rootIds = [];

        foreach ($roots as $key) {
            $layanan = null;

            if (! empty($key['kode'])) {
                $layanan = LayananJasa::where('kode_layanan', $key['kode'])->first();
            }

            if (! $layanan && ! empty($key['nama'])) {
                $layanan = LayananJasa::where('nama_layanan', $key['nama'])->first();
            }

            if ($layanan) {
                $rootIds[] = $layanan->id;
            }
        }

        if (empty($rootIds)) {
            return [];
        }

        // 2. BFS turun ke seluruh keturunan, kumpulkan id semua node.
        $allIds = [];
        $frontier = $rootIds;
        $guard = 0;

        while (! empty($frontier) && $guard < 12) {
            $allIds = array_merge($allIds, $frontier);

            $frontier = LayananJasa::whereIn('parent_id', $frontier)
                ->pluck('id')
                ->all();

            $guard++;
        }

        $allIds = array_values(array_unique($allIds));

        // 3. Filter hanya leaf yang punya tarif > 0 dan aktif.
        return LayananJasa::whereIn('id', $allIds)
            ->where('is_leaf', true)
            ->where('is_active', true)
            ->where('tarif_dasar', '>', 0)
            ->pluck('id')
            ->all();
    }

    /**
     * Sinkron assignment user → layanan_jasa pada tabel admin_jasa_layanan.
     */
    private function syncAssignment(User $user, array $layananIds): void
    {
        $now = Carbon::now();

        // Hapus assignment yang tidak ada di list baru.
        DB::table('admin_jasa_layanan')
            ->where('user_id', $user->id)
            ->whereNotIn('layanan_jasa_id', $layananIds)
            ->delete();

        foreach ($layananIds as $layananId) {
            DB::table('admin_jasa_layanan')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'layanan_jasa_id' => $layananId,
                ],
                [
                    'status_aktif' => true,
                    'tanggal_mulai' => $now->toDateString(),
                    'tanggal_selesai' => null,
                    'keterangan' => 'Seeded by AdminJasaSampleSeeder',
                    'created_by' => $user->id,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
