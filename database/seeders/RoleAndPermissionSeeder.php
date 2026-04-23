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
            'name' => 'KARTIKA',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $operator->syncRoles(['Operator BLU']);

        // Create Pejabat Pengadaan for Testing
        $pengadaan = User::updateOrCreate([
            'email' => 'pengadaan@admin.com',
        ], [
            'name' => 'VERNALDI',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $pengadaan->syncRoles(['Pejabat Pengadaan']);
        
       
        
        $ppk = User::updateOrCreate([
            'email' => 'ppk@admin.com',
        ], [
            'name' => 'GUNAWAN',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $ppk->syncRoles(['PPK']);

        // Create Kasubag for Testing
        $kasubag = User::updateOrCreate([
            'email' => 'kasubbag@admin.com',
        ], [
            'name' => 'ZALDI ARDIAN',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $kasubag->syncRoles(['Kepala Subbagian Keuangan dan Tata Usaha']);

        // Create PPSPM for Testing
        $ppspm = User::updateOrCreate([
            'email' => 'ppspm@admin.com',
        ], [
            'name' => 'MUTIA RACHMI',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $ppspm->syncRoles(['PPSPM']);

        // Create Bendahara Pengeluaran for Testing
        $bendaharaPengeluaran = User::updateOrCreate([
            'email' => 'bendahara.pengeluaran@admin.com',
        ], [
            'name' => 'YENI PUJI ASTUTI',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);
        $bendaharaPengeluaran->syncRoles(['Bendahara Pengeluaran']);

        // Create Bendahara Penerimaan for Testing
        $bendaharaPenerimaan = User::updateOrCreate([
            'email' => 'bendahara.penerimaan@admin.com',
        ], [
            'name' => 'SITI KHOLIFAH',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);
        $bendaharaPenerimaan->syncRoles(['Bendahara Penerimaan']);

        

         // Create Pejabat Pengadaan for Testing
        $perjaldin = User::updateOrCreate([
            'email' => 'perjaldin@admin.com',
        ], [
            'name' => 'KHARISMA',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $perjaldin->syncRoles(['Operator Perjaldin']);
        
        $ppabp = User::updateOrCreate([
            'email' => 'ppabp@admin.com',
        ], [
            'name' => 'AULIA',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $ppabp->syncRoles(['PPABP']);
    }
}
