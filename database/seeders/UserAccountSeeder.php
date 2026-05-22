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
     * Setiap user di-link ke MasterPegawai via polymorphic profilable, kecuali Super Admin
     * yang merupakan akun sistem dan tidak terikat ke data pegawai/mitra.
     */
    public function run(): void
    {
        // 1) Akun sistem tanpa profilable — khusus Super Admin.
        //    Dibuat lewat withoutEvents() supaya guard "wajib profilable" di model User
        //    tidak men-throw exception (Super Admin memang akun sistem, bukan pegawai).
        $this->seedSystemAccount(
            email: 'super.admin@sikeren.id',
            roles: ['Super Admin'],
            label: 'SUPER ADMIN SISTEM',
        );

        // 1b) Akun PLT/PLH — Pelaksana Tugas / Pelaksana Harian.
        //     Tidak terikat ke MasterPegawai (akun jabatan sementara) sehingga dibuat
        //     sebagai system account. Memiliki menu yang sama persis dengan KPA.
        $this->seedSystemAccount(
            email: 'plt.plh@sikeren.id',
            roles: ['PLT/PLH'],
            label: 'PLT / PLH',
        );

        // 2) Akun yang terhubung ke MasterPegawai.
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
            ],

            // === Modul Jasa & Utilitas ===
            [
                'nama' => 'MELLYARTI RAHMAN',
                'email' => 'super.admin.jasa@sikeren.id',
                'roles' => ['Super Admin Jasa'],
            ],
            [
                'nama' => 'DIAH DESTIANA',
                'email' => 'admin.jasa@sikeren.id',
                'roles' => ['Admin Jasa'],
            ],
            [
                'nama' => 'ANDI AMIRAH AFIFAH',
                'email' => 'admin.konsesi@sikeren.id',
                'roles' => ['Admin Konsesi'],
            ],
            [
                'nama' => 'MUHAMMAD KEMAL HIKMA',
                'email' => 'koordinator.jasa@sikeren.id',
                'roles' => ['Koordinator Jasa'],
            ],
            [
                'nama' => 'FAJRUL SYAMSI',
                'email' => 'admin.listrik@sikeren.id',
                'roles' => ['Admin Listrik'],
            ],
            [
                'nama' => 'PALUNG PURNAMA HENDRAYANA',
                'email' => 'admin.air@sikeren.id',
                'roles' => ['Admin Air'],
            ],
        ];

        foreach ($accounts as $account) {
            $this->seedPegawaiAccount($account);
        }
    }

    /**
     * Buat / sinkronkan akun untuk user yang TERHUBUNG ke MasterPegawai.
     */
    private function seedPegawaiAccount(array $account): void
    {
        // Cari pegawai di master_pegawai berdasarkan nama
        $pegawai = MasterPegawai::where('nama_lengkap', $account['nama'])->first();

        if (!$pegawai) {
            $this->command->warn("Pegawai '{$account['nama']}' tidak ditemukan di master_pegawai. Skipped.");
            return;
        }

        // Cari user berdasarkan profilable (pegawai), fallback ke email
        $user = User::where('profilable_type', MasterPegawai::class)
            ->where('profilable_id', $pegawai->id)
            ->first();

        if ($user) {
            $user->update([
                'email' => $account['email'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        } else {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'password' => Hash::make('password'),
                    'profilable_type' => MasterPegawai::class,
                    'profilable_id' => $pegawai->id,
                    'email_verified_at' => now(),
                ]
            );
        }

        // Sinkronisasi role (syncRoles menghapus role lama, assign yang baru)
        $user->syncRoles($account['roles']);

        $roleStr = implode(', ', $account['roles']);
        $this->command->info("✓ {$account['nama']} → {$account['email']} [{$roleStr}]");
    }

    /**
     * Buat / sinkronkan akun SISTEM yang TIDAK terhubung ke pegawai/mitra.
     * Menggunakan User::withoutEvents() agar guard model (yang mewajibkan profilable)
     * tidak ikut berjalan saat pembuatan akun ini.
     */
    private function seedSystemAccount(string $email, array $roles, string $label): void
    {
        $user = User::withoutEvents(function () use ($email) {
            return User::updateOrCreate(
                ['email' => $email],
                [
                    'password' => Hash::make('password'),
                    'profilable_type' => null,
                    'profilable_id' => null,
                    'email_verified_at' => now(),
                ]
            );
        });

        $user->syncRoles($roles);

        $roleStr = implode(', ', $roles);
        $this->command->info("✓ {$label} → {$email} [{$roleStr}]");
    }
}
