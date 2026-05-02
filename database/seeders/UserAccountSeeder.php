<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\MasterPegawai;
use Illuminate\Support\Facades\Hash;

class UserAccountSeeder extends Seeder
{
    /**
     * Buat akun user untuk pegawai yang menjalankan fungsi operasional di SIKEREN-BLU.
     * Setiap user di-link ke MasterPegawai via polymorphic profilable.
     */
    public function run(): void
    {
        $accounts = [
            [
                'nama' => 'I KADEK YULI SASTRAWAN',
                'email' => 'kpa@sikeren.id',
                'roles' => ['KPA'],
            ],
            [
                'nama' => 'ZALDI ARDIAN',
                'email' => 'kasubbag@sikeren.id',
                'roles' => ['Kepala Subbagian Keuangan dan Tata Usaha'],
            ],
            [
                'nama' => 'ROSLAN',
                'email' => 'kasipk@sikeren.id',
                'roles' => ['Kepala Seksi Pelayanan dan Kerjasama'],
            ],
            [
                'nama' => 'GUNAWAN',
                'email' => 'ppk@sikeren.id',
                'roles' => ['PPK'],
            ],
            [
                'nama' => 'MUTIA RACHMI',
                'email' => 'ppspm@sikeren.id',
                'roles' => ['PPSPM', 'Koordinator Keuangan'], // dual-role
            ],
            [
                'nama' => 'YENY PUJI ASTUTI',
                'email' => 'bendahara.pengeluaran@sikeren.id',
                'roles' => ['Bendahara Pengeluaran'],
            ],
            [
                'nama' => 'SITI KHOLIFAH',
                'email' => 'bendahara.penerimaan@sikeren.id',
                'roles' => ['Bendahara Penerimaan'],
            ],
            [
                'nama' => 'GUSTI AYU KHARISMA MAHARANI',
                'email' => 'perjaldin@sikeren.id',
                'roles' => ['Operator Perjaldin'],
            ],
            [
                'nama' => 'RIZKI AULIA',
                'email' => 'ppabp@sikeren.id',
                'roles' => ['PPABP'],
            ],
            [
                'nama' => 'VERNALDY REVIMAPUTRA SAMPE LALAN',
                'email' => 'pengadaan@sikeren.id',
                'roles' => ['Pejabat Pengadaan'],
            ],
            [
                'nama' => 'KARTIKA FALITA',
                'email' => 'operator@sikeren.id',
                'roles' => ['Operator BLU'],
            ]
        ];

        foreach ($accounts as $account) {
            // Cari pegawai di master_pegawai berdasarkan nama
            $pegawai = MasterPegawai::where('nama_lengkap', $account['nama'])->first();

            if (!$pegawai) {
                $this->command->warn("Pegawai '{$account['nama']}' tidak ditemukan di master_pegawai. Skipped.");
                continue;
            }

            // Buat atau update user
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'password' => Hash::make('password'),
                    'profilable_type' => MasterPegawai::class,
                    'profilable_id' => $pegawai->id,
                    'email_verified_at' => now(),
                ]
            );

            // Sinkronisasi role (syncRoles menghapus role lama, assign yang baru)
            $user->syncRoles($account['roles']);

            $roleStr = implode(', ', $account['roles']);
            $this->command->info("✓ {$account['nama']} → {$account['email']} [{$roleStr}]");
        }
    }
}
