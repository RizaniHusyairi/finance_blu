<?php

namespace Database\Seeders;

use App\Models\MasterPegawai;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 14 roles berdasarkan PRD
        $roles = [
            'Super Admin',
            'KPA',
            'Kepala Subbagian Keuangan dan Tata Usaha',
            'Kepala Seksi Pelayanan dan Kerjasama',
            'PPK',
            'PPSPM',
            'Bendahara Pengeluaran',
            'Bendahara Penerimaan',
            'Pejabat Pengadaan',
            'Operator BLU',
            'PPABP',
            'Operator Perjaldin',
            'Koordinator Keuangan',
            'Mitra',
        ];

        foreach ($roles as $role) {
            Role::findOrCreate($role, 'web');
        }

        /*
         * Setiap user sistem WAJIB terhubung ke 1 pegawai (profilable).
         * Daftar di bawah memetakan: email user → role → data pegawai.
         * Pegawai dibuat (firstOrCreate by nip), kemudian user dibuat
         * dengan profilable_type/profilable_id menunjuk ke pegawai tersebut.
         *
         * Display name user diambil dari pegawai.nama_lengkap (kolom users.name sudah dihapus).
         */
        $userPegawaiMap = [
            [
                'email' => 'admin@sikeren.id',
                'role'  => 'Super Admin',
                'pegawai' => [
                    'nip'          => '000000000000000001',
                    'nama_lengkap' => 'Super Admin',
                    'jabatan'      => 'System Administrator',
                    'npwp'         => null,
                    'status_aktif' => true,
                ],
            ],
            [
                'email' => 'operator@sikeren.id',
                'role'  => 'Operator BLU',
                'pegawai' => [
                    'nip'          => '198706122010121001',
                    'nama_lengkap' => 'KARTIKA',
                    'jabatan'      => 'Operator BLU',
                    'npwp'         => '71.234.567.8-901.000',
                    'status_aktif' => true,
                ],
            ],
            [
                'email' => 'pengadaan@sikeren.id',
                'role'  => 'Pejabat Pengadaan',
                'pegawai' => [
                    'nip'          => '198508192011011011',
                    'nama_lengkap' => 'VERNALDI',
                    'jabatan'      => 'Pejabat Pengadaan',
                    'npwp'         => '81.234.567.8-901.000',
                    'status_aktif' => true,
                ],
            ],
            [
                'email' => 'ppk@sikeren.id',
                'role'  => 'PPK',
                'pegawai' => [
                    'nip'          => '197905082008011002',
                    'nama_lengkap' => 'GUNAWAN',
                    'jabatan'      => 'Pejabat Pembuat Komitmen',
                    'npwp'         => '72.234.567.8-901.000',
                    'status_aktif' => true,
                ],
            ],
            [
                'email' => 'kasubbag@sikeren.id',
                'role'  => 'Kepala Subbagian Keuangan dan Tata Usaha',
                'pegawai' => [
                    'nip'          => '197611212005011003',
                    'nama_lengkap' => 'ZALDI ARDIAN',
                    'jabatan'      => 'Kepala Subbagian Keuangan dan Tata Usaha',
                    'npwp'         => '73.234.567.8-901.000',
                    'status_aktif' => true,
                ],
            ],
            [
                'email' => 'ppspm@sikeren.id',
                'role'  => 'PPSPM',
                'pegawai' => [
                    'nip'          => '198102142009021004',
                    'nama_lengkap' => 'MUTIA RACHMI',
                    'jabatan'      => 'PPSPM',
                    'npwp'         => '74.234.567.8-901.000',
                    'status_aktif' => true,
                ],
            ],
            [
                'email' => 'bendahara.pengeluaran@sikeren.id',
                'role'  => 'Bendahara Pengeluaran',
                'pegawai' => [
                    'nip'          => '198305172011011005',
                    'nama_lengkap' => 'YENI PUJI ASTUTI',
                    'jabatan'      => 'Bendahara Pengeluaran',
                    'npwp'         => '75.234.567.8-901.000',
                    'status_aktif' => true,
                ],
            ],
            [
                'email' => 'bendahara.penerimaan@sikeren.id',
                'role'  => 'Bendahara Penerimaan',
                'pegawai' => [
                    'nip'          => '198409242012011006',
                    'nama_lengkap' => 'SITI KHOLIFAH',
                    'jabatan'      => 'Bendahara Penerimaan',
                    'npwp'         => '76.234.567.8-901.000',
                    'status_aktif' => true,
                ],
            ],
            [
                'email' => 'perjaldin@sikeren.id',
                'role'  => 'Operator Perjaldin',
                'pegawai' => [
                    'nip'          => '198812102014021007',
                    'nama_lengkap' => 'KHARISMA',
                    'jabatan'      => 'Operator Perjaldin',
                    'npwp'         => '77.234.567.8-901.000',
                    'status_aktif' => true,
                ],
            ],
            [
                'email' => 'ppabp@sikeren.id',
                'role'  => 'PPABP',
                'pegawai' => [
                    'nip'          => '199107302015032012',
                    'nama_lengkap' => 'AULIA',
                    'jabatan'      => 'PPABP',
                    'npwp'         => '82.234.567.8-901.000',
                    'status_aktif' => true,
                ],
            ],
            [
                'email' => 'koordinator.keuangan@sikeren.id',
                'role'  => 'Koordinator Keuangan',
                'pegawai' => [
                    'nip'          => '198404252010011013',
                    'nama_lengkap' => 'RAHMAT HIDAYAT',
                    'jabatan'      => 'Koordinator Keuangan',
                    'npwp'         => '83.234.567.8-901.000',
                    'status_aktif' => true,
                ],
            ],
        ];

        foreach ($userPegawaiMap as $entry) {
            // 1) Pastikan role ada
            Role::findOrCreate($entry['role'], 'web');

            // 2) Buat / update pegawai (firstOrCreate by NIP)
            $pegawai = MasterPegawai::updateOrCreate(
                ['nip' => $entry['pegawai']['nip']],
                $entry['pegawai']
            );

            // 3) Buat / update user dengan profilable menunjuk ke pegawai
            $user = User::updateOrCreate(
                ['email' => $entry['email']],
                [
                    'email_verified_at' => now(),
                    'password'          => Hash::make('password'),
                    'profilable_type'   => MasterPegawai::class,
                    'profilable_id'     => $pegawai->id,
                ]
            );

            // 4) Sinkronkan role (replace, bukan tambah) sesuai mapping
            $user->syncRoles([$entry['role']]);

            $this->command?->info(sprintf(
                '  ✓ %s  (%s)  →  role: %s',
                str_pad($entry['email'], 35),
                $entry['pegawai']['nama_lengkap'],
                $entry['role']
            ));
        }
    }
}
