<?php

namespace Database\Seeders;

use App\Models\MasterPegawai;
use Illuminate\Database\Seeder;

class MasterPegawaiSeeder extends Seeder
{
    /**
     * Seeder ini hanya membuat pegawai yang TIDAK punya akun user.
     * Pegawai yang terhubung ke user (operator, ppk, kasubag, dll)
     * dibuat di RoleAndPermissionSeeder berbarengan dengan user-nya
     * agar relasi morph (users.profilable_*) langsung tersambung.
     */
    public function run(): void
    {
        $pegawai = [
            [
                'nip' => '199001012018011008',
                'nama_lengkap' => 'Siti Rahmawati',
                'jabatan' => 'Analis Keuangan',
                'npwp' => '78.234.567.8-901.000',
                'status_aktif' => true,
            ],
            [
                'nip' => '199203152019031009',
                'nama_lengkap' => 'Muhammad Arsyad',
                'jabatan' => 'Staf Administrasi',
                'npwp' => '79.234.567.8-901.000',
                'status_aktif' => true,
            ],
            [
                'nip' => '198911272017021010',
                'nama_lengkap' => 'Nur Aini',
                'jabatan' => 'Pejabat Pelaksana Teknis Kegiatan',
                'npwp' => '80.234.567.8-901.000',
                'status_aktif' => true,
            ],
        ];

        foreach ($pegawai as $item) {
            MasterPegawai::updateOrCreate(['nip' => $item['nip']], $item);
        }
    }
}
