<?php

namespace Database\Seeders;

use App\Models\MasterPegawai;
use App\Models\User;
use App\Services\Admin\UserProvisioningService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Seeder akun user — direkonstruksi dari data produksi (db_sikeren).
 *
 * Memetakan user → pegawai lewat NIP (stabil lintas environment), BUKAN id
 * mentah. Mengandalkan UserProvisioningService agar logika create/sync identik
 * dengan UI Super Admin.
 *
 * Catatan:
 *  - Akun MITRA (profilable = MitraJasa) sengaja TIDAK diseed — dibuat lewat
 *    alur penerbitan tagihan jasa / pendaftaran mitra.
 *  - Password awal = self::DEFAULT_PASSWORD ('password'), kecuali akun tertentu
 *    yang dioverride lewat elemen ke-4 pada $accounts → WAJIB diganti pasca
 *    deploy. Idempoten: bila user sudah ada, hanya email + status aktif + role
 *    yang disinkronkan; password TIDAK direset (perubahan admin tidak hilang).
 *  - Penugasan Admin Jasa → layanan (tabel admin_jasa_layanan) diseed terpisah
 *    oleh [[AdminJasaLayananSeeder]] (perlu layanan_jasas sudah tersedia).
 */
class UserAccountSeeder extends Seeder
{
    /** Password awal default. WAJIB diganti pasca-deploy. */
    private const DEFAULT_PASSWORD = 'password';

    public function run(UserProvisioningService $provisioner): void
    {
        // 1) Akun sistem (Super Admin) — tanpa profilable.
        $this->upsertSystemAccount($provisioner, 'super.admin@sikeren.id', ['Super Admin'], 'SUPER ADMIN SISTEM');

        // 2) Akun menempel ke MasterPegawai: [NIP, email, [roles], password?].
        //    Elemen ke-4 (password) opsional → default self::DEFAULT_PASSWORD.
        $accounts = [
            // — Keuangan & pengadaan —
            ['197607041998031001', 'kpa@sikeren.id',                   ['KPA']],
            ['198109172002121002', 'kasubbag@sikeren.id',              ['PLT/PLH', 'Kepala Subbagian Keuangan dan Tata Usaha']],
            ['197409191998031001', 'kasipk@sikeren.id',                ['PLT/PLH', 'Kepala Seksi Pelayanan dan Kerjasama']],
            ['198411052007121001', 'ppk@sikeren.id',                   ['PPK']],
            ['198201112002122002', 'ppspm@sikeren.id',                 ['PPSPM', 'Koordinator Keuangan']],
            ['199202202010122001', 'bendahara.pengeluaran@sikeren.id', ['Bendahara Pengeluaran']],
            ['198709042009122007', 'bendahara.penerimaan@sikeren.id',  ['Bendahara Penerimaan']],
            ['200102252022102001', 'perjaldin@sikeren.id',             ['Operator Perjaldin']],
            ['200105192022102001', 'ppabp@sikeren.id',                 ['PPABP']],
            ['200101152022101001', 'pengadaan@sikeren.id',             ['Pejabat Pengadaan']],
            ['199604172019022006', 'operator@sikeren.id',              ['Operator BLU']],
            // Akun multi-peran keuangan (uji alur). Pertimbangkan HAPUS untuk
            // produksi — melanggar pemisahan tugas (SoD: maker ≠ checker).
            ['199303112022031008', 'demo.keuangan@sikeren.id',         ['PPK', 'PPSPM', 'Koordinator Keuangan', 'Bendahara Pengeluaran', 'Bendahara Penerimaan']],

            // — Penandatangan (Plt./Plh. Kepala BLU) —
            ['197803192000121001', 'kasito@sikeren.id',                ['PLT/PLH'], 'password123'],
            ['198110112002121002', 'kampenyandar@sikeren.id',          ['PLT/PLH'], 'password123'],

            // — Modul Jasa: koordinator & admin —
            ['196904061992011001', 'koordinator.jasa@sikeren.id',      ['Super Admin Jasa', 'Koordinator Jasa']],
            ['199506282025212019', 'andi.amirah@gmail.com',            ['Super Admin Jasa']],
            ['197905172010122001', 'mellyarti.rahman@gmail.com',       ['Admin Jasa']],
            ['199012052025212031', 'admin.jasa@sikeren.id',            ['Admin Jasa']],
            ['199305112025212023', 'andini@gmail.com',                 ['Admin Jasa']],
            ['200206302023102001', 'zalfa@gmail.com',                  ['Admin Jasa']],
            ['199009042025211025', 'kemal@gmail.com',                  ['Admin Jasa']],
            ['199004202025212024', 'tri.hardanti@sikeren.id',          ['Admin Jasa']],
            ['200303262022031006', 'fikrilisanizamzam@gmail.com',      ['Admin Jasa']],
            ['199003202025212031', 'marcelinakuling@gmail.com',        ['Admin Jasa']],
            ['200011022025212011', 'rismaamanda@gmail.com',            ['Admin Jasa']],
            ['198708232007122001', 'hennyagustina@gmail.com',          ['Admin Jasa']],

            // — Utilitas (listrik & air) —
            ['197809232000031001', 'admin.listrik@sikeren.id',         ['Admin Listrik']],
            ['197407011998031001', 'agoes.yuliantoro@sikeren.id',      ['Admin Listrik']],
            ['199507302022031011', 'andhika.surya@sikeren.id',         ['Admin Listrik']],
            ['198606222009121003', 'admin.air@sikeren.id',             ['Admin Air']],
            ['199611242020121005', 'nur.aziz@sikeren.id',              ['Admin Air']],

            // — AMC —
            ['197812082000121003', 'amc01@gmail.com',                  ['AMC'], 'password123'],
        ];

        foreach ($accounts as $account) {
            [$nip, $email, $roles] = $account;
            $password = $account[3] ?? self::DEFAULT_PASSWORD;
            $this->upsertPegawaiAccount($provisioner, $nip, $email, $roles, $password);
        }
    }

    private function upsertPegawaiAccount(
        UserProvisioningService $provisioner,
        string $nip,
        string $email,
        array $roles,
        string $password = self::DEFAULT_PASSWORD,
    ): void {
        $pegawai = MasterPegawai::where('nip', $nip)->first();
        if (! $pegawai) {
            $this->command?->warn("⚠ Pegawai NIP {$nip} tidak ditemukan di master_pegawai. {$email} dilewati.");
            return;
        }

        $existing = User::where('profilable_type', MasterPegawai::class)
            ->where('profilable_id', $pegawai->id)
            ->first()
            ?? User::where('email', $email)->first();

        if ($existing) {
            // Idempoten: sinkronkan email (bila bebas) + status aktif + role.
            // Password sengaja TIDAK direset agar perubahan admin tetap aman.
            if ($existing->email !== $email
                && ! User::where('email', $email)->where('id', '!=', $existing->id)->exists()) {
                $existing->forceFill(['email' => $email])->save();
            }
            $existing->forceFill($this->activeAccountAttributes())->save();
            $provisioner->syncRoles($existing, $roles);
            $this->command?->info("↻ {$pegawai->nama_lengkap} → {$email} (disinkronkan)");

            return;
        }

        $user = $provisioner->createForPegawai(
            $pegawai,
            $email,
            $roles,
            password: $password,
            limitActivePeriod: false, // permanen aktif (PLT/PLH tanpa batas, sesuai data produksi)
        );
        $this->markAccountActive($user);
        $this->command?->info("✓ {$pegawai->nama_lengkap} → {$email} [" . implode(', ', $roles) . ']');
    }

    private function upsertSystemAccount(UserProvisioningService $provisioner, string $email, array $roles, string $label): void
    {
        $existing = User::where('email', $email)->first();
        if ($existing) {
            $existing->forceFill($this->activeAccountAttributes())->save();
            $provisioner->syncRoles($existing, $roles);
            $this->command?->info("↻ {$label} → {$email} (disinkronkan)");

            return;
        }

        $user = $provisioner->createSystemAccount($email, $roles, password: self::DEFAULT_PASSWORD);
        $this->markAccountActive($user);
        $this->command?->info("✓ {$label} → {$email} [" . implode(', ', $roles) . ']');
    }

    private function markAccountActive(User $user): void
    {
        $attributes = $this->activeAccountAttributes();
        if ($attributes !== []) {
            $user->forceFill($attributes)->save();
        }
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
