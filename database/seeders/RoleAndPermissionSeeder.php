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
            'Admin Jasa',
            'Koordinator Jasa',
        ];

        foreach ($roles as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
