<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Spatie\Permission\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 13 roles based on PRD
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
            'Mitra'
        ];

        foreach ($roles as $role) {
            Role::findOrCreate($role, 'web');
        }

        // Create Super Admin User
        $superAdmin = User::updateOrCreate([
            'email' => 'admin@admin.com',
        ], [
            'name' => 'Super Admin',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $superAdmin->syncRoles(['Super Admin']);
        
        // Create an Operator for Testing
        $operator = User::updateOrCreate([
            'email' => 'operator@admin.com',
        ], [
            'name' => 'Operator BLU',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $operator->syncRoles(['Operator BLU']);

        // Create Pejabat Pengadaan for Testing
        $pengadaan = User::updateOrCreate([
            'email' => 'pengadaan@admin.com',
        ], [
            'name' => 'Pejabat Pengadaan Test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $pengadaan->syncRoles(['Pejabat Pengadaan']);

        // Create a Mitra for Testing
        $mitra = User::updateOrCreate([
            'email' => 'vendor@test.com',
        ], [
            'name' => 'Vendor Test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $mitra->syncRoles(['Mitra']);

        Supplier::updateOrCreate(
            ['user_id' => $mitra->id],
            ['name' => 'Vendor Test']
        );
    }
}
