<?php

namespace Database\Seeders;

use App\Models\MasterPegawai;
use App\Models\User;
use Illuminate\Database\Seeder;

class MasterPegawaiSeeder extends Seeder
{
    public function run(): void
    {
        $pegawai = [
            [
                'email' => 'operator@admin.com',
                'nip' => '198706122010121001',
                'nama_lengkap' => 'Operator BLU',
                'jabatan' => 'Operator BLU',
                'npwp' => '71.234.567.8-901.000',
                'status_aktif' => true,
            ],
            [
                'email' => 'ppk@admin.com',
                'nip' => '197905082008011002',
                'nama_lengkap' => 'Pejabat Pembuat Komitmen Test',
                'jabatan' => 'Pejabat Pembuat Komitmen',
                'npwp' => '72.234.567.8-901.000',
                'status_aktif' => true,
            ],
            [
                'email' => 'kasubag@admin.com',
                'nip' => '197611212005011003',
                'nama_lengkap' => 'Kepala Subbagian Keuangan Test',
                'jabatan' => 'Kepala Subbagian Keuangan dan Tata Usaha',
                'npwp' => '73.234.567.8-901.000',
                'status_aktif' => true,
            ],
            [
                'email' => 'ppspm@admin.com',
                'nip' => '198102142009021004',
                'nama_lengkap' => 'PPSPM Test',
                'jabatan' => 'PPSPM',
                'npwp' => '74.234.567.8-901.000',
                'status_aktif' => true,
            ],
            [
                'email' => 'bendahara.pengeluaran@admin.com',
                'nip' => '198305172011011005',
                'nama_lengkap' => 'Bendahara Pengeluaran Test',
                'jabatan' => 'Bendahara Pengeluaran',
                'npwp' => '75.234.567.8-901.000',
                'status_aktif' => true,
            ],
            [
                'email' => 'bendahara.penerimaan@admin.com',
                'nip' => '198409242012011006',
                'nama_lengkap' => 'Bendahara Penerimaan Test',
                'jabatan' => 'Bendahara Penerimaan',
                'npwp' => '76.234.567.8-901.000',
                'status_aktif' => true,
            ],
            [
                'email' => 'perjaldin@admin.com',
                'nip' => '198812102014021007',
                'nama_lengkap' => 'Operator Perjaldin Test',
                'jabatan' => 'Operator Perjaldin',
                'npwp' => '77.234.567.8-901.000',
                'status_aktif' => true,
            ],
            [
                'email' => null,
                'nip' => '199001012018011008',
                'nama_lengkap' => 'Siti Rahmawati',
                'jabatan' => 'Analis Keuangan',
                'npwp' => '78.234.567.8-901.000',
                'status_aktif' => true,
            ],
            [
                'email' => null,
                'nip' => '199203152019031009',
                'nama_lengkap' => 'Muhammad Arsyad',
                'jabatan' => 'Staf Administrasi',
                'npwp' => '79.234.567.8-901.000',
                'status_aktif' => true,
            ],
            [
                'email' => null,
                'nip' => '198911272017021010',
                'nama_lengkap' => 'Nur Aini',
                'jabatan' => 'Pejabat Pelaksana Teknis Kegiatan',
                'npwp' => '80.234.567.8-901.000',
                'status_aktif' => true,
            ],
        ];

        foreach ($pegawai as $item) {
            $email = $item['email'];
            unset($item['email']);

            $userId = null;
            if ($email) {
                $userId = User::where('email', $email)->value('id');
            }

            MasterPegawai::updateOrCreate(
                ['nip' => $item['nip']],
                array_merge($item, [
                    'user_id' => $userId,
                ])
            );
        }
    }
}
