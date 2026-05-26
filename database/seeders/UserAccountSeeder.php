<?php

namespace Database\Seeders;

use App\Models\MasterPegawai;
use App\Models\User;
use App\Services\Admin\UserProvisioningService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UserAccountSeeder extends Seeder
{
    /**
     * Seed akun user untuk setiap role operasional.
     *
     * Mengandalkan UserProvisioningService agar logika create/sync identik dengan UI Super Admin.
     * Idempotent: kalau user sudah ada, hanya disinkronkan emailnya, password, dan role.
     */
    public function run(UserProvisioningService $provisioner): void
    {
        // 1) Akun sistem (Super Admin) — tanpa profilable
        $this->upsertSystemAccount(
            $provisioner,
            email: 'super.admin@sikeren.id',
            roles: ['Super Admin'],
            label: 'SUPER ADMIN SISTEM',
        );

        // 2) Akun yang menempel ke MasterPegawai
        $accounts = [
            ['I KADEK YULI SASTRAWAN',           'kpa@sikeren.id',                  ['KPA']],
            ['ZALDI ARDIAN',                     'kasubbag@sikeren.id',             ['Kepala Subbagian Keuangan dan Tata Usaha']],
            ['ROSLAN',                           'kasipk@sikeren.id',               ['Kepala Seksi Pelayanan dan Kerjasama']],
            ['GUNAWAN',                          'ppk@sikeren.id',                  ['PPK']],
            ['MUTIA RACHMI',                     'ppspm@sikeren.id',                ['PPSPM', 'Koordinator Keuangan']],
            ['YENY PUJI ASTUTI',                 'bendahara.pengeluaran@sikeren.id',['Bendahara Pengeluaran']],
            ['SITI KHOLIFAH',                    'bendahara.penerimaan@sikeren.id', ['Bendahara Penerimaan']],
            ['GUSTI AYU KHARISMA MAHARANI',      'perjaldin@sikeren.id',            ['Operator Perjaldin']],
            ['RIZKI AULIA',                      'ppabp@sikeren.id',                ['PPABP']],
            ['VERNALDY REVIMAPUTRA SAMPE LALAN', 'pengadaan@sikeren.id',            ['Pejabat Pengadaan']],
            ['KARTIKA FALITA',                   'operator@sikeren.id',             ['Operator BLU']],

            // Modul Jasa & Utilitas
            ['MELLYARTI RAHMAN',                 'super.admin.jasa@sikeren.id',     ['Super Admin Jasa']],
            ['DIAH DESTIANA',                    'admin.jasa@sikeren.id',           ['Admin Jasa']],
            ['ANDI AMIRAH AFIFAH',               'admin.konsesi@sikeren.id',        ['Admin Konsesi']],
            ['MUHAMMAD KEMAL HIKMA',             'koordinator.jasa@sikeren.id',     ['Koordinator Jasa']],
            ['FAJRUL SYAMSI',                    'admin.listrik@sikeren.id',        ['Admin Listrik']],
            ['AGOES YULIANTORO',                 'agoes.yuliantoro@sikeren.id',     ['Admin Listrik']],
            ['ANDHIKA SURYA PRADANA',            'andhika.surya@sikeren.id',        ['Admin Listrik']],
            ['PALUNG PURNAMA HENDRAYANA',        'admin.air@sikeren.id',            ['Admin Air']],
            ['DWI AHMAD NUR AZIZ',               'nur.aziz@sikeren.id',             ['Admin Air']],
        ];

        foreach ($accounts as [$nama, $email, $roles]) {
            $this->upsertPegawaiAccount($provisioner, $nama, $email, $roles);
        }
    }

    private function upsertPegawaiAccount(UserProvisioningService $provisioner, string $nama, string $email, array $roles): void
    {
        $pegawai = MasterPegawai::where('nama_lengkap', $nama)->first();
        if (! $pegawai) {
            $this->command->warn("Pegawai '{$nama}' tidak ditemukan di master_pegawai. Skipped.");
            return;
        }

        $existing = User::where('profilable_type', MasterPegawai::class)
            ->where('profilable_id', $pegawai->id)
            ->first();

        if ($existing) {
            // Sinkronkan email + password + role tanpa membuat user baru
            $existing->forceFill(array_merge([
                'email' => $email,
                'password' => Hash::make('password'),
                'email_verified_at' => $existing->email_verified_at ?: now(),
            ], $this->activeAccountAttributes()))->save();
            $provisioner->syncRoles($existing, $roles);
        } else {
            $user = $provisioner->createForPegawai($pegawai, $email, $roles, password: 'password');
            $this->markAccountActive($user);
        }

        $this->command->info("✓ {$nama} → {$email} [" . implode(', ', $roles) . ']');
    }

    private function upsertSystemAccount(UserProvisioningService $provisioner, string $email, array $roles, string $label): void
    {
        $existing = User::where('email', $email)->first();
        if ($existing) {
            $existing->forceFill(array_merge([
                'password' => Hash::make('password'),
                'email_verified_at' => $existing->email_verified_at ?: now(),
            ], $this->activeAccountAttributes()))->save();
            $provisioner->syncRoles($existing, $roles);
        } else {
            $user = $provisioner->createSystemAccount($email, $roles, password: 'password');
            $this->markAccountActive($user);
        }

        $this->command->info("✓ {$label} → {$email} [" . implode(', ', $roles) . ']');
    }

    private function markAccountActive(User $user): void
    {
        $attributes = $this->activeAccountAttributes();

        if ($attributes === []) {
            return;
        }

        $user->forceFill($attributes)->save();
    }

    private function activeAccountAttributes(): array
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'is_active')) {
            return [];
        }

        $attributes = ['is_active' => true];

        if (Schema::hasColumn('users', 'active_from')) {
            $attributes['active_from'] = null;
        }

        if (Schema::hasColumn('users', 'active_until')) {
            $attributes['active_until'] = null;
        }

        if (Schema::hasColumn('users', 'disabled_at')) {
            $attributes['disabled_at'] = null;
        }

        return $attributes;
    }
}
