<?php

namespace App\Services\Admin;

use App\Models\MasterPegawai;
use App\Models\MitraJasa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Single source of truth untuk membuat / mengubah user beserta role-nya.
 *
 * Service ini dipakai oleh:
 *  - UserManagementController (UI Super Admin)
 *  - UserAccountSeeder (data awal)
 *
 * Sehingga aturan bisnis tentang profilable, password, dan role tidak terduplikasi.
 */
class UserProvisioningService
{
    /**
     * Buat user baru yang menempel ke MasterPegawai.
     */
    public function createForPegawai(
        MasterPegawai $pegawai,
        string $email,
        array $roles,
        ?string $password = null,
    ): User {
        $this->guardEmailUnique($email);
        $this->guardPegawaiBelumPunyaUser($pegawai);

        return DB::transaction(function () use ($pegawai, $email, $roles, $password) {
            $user = User::create([
                'email' => $email,
                'password' => Hash::make($password ?? $this->randomPassword()),
                'profilable_type' => MasterPegawai::class,
                'profilable_id' => $pegawai->id,
                'email_verified_at' => now(),
            ]);

            $user->syncRoles($this->sanitizeRoles($roles));

            return $user->fresh(['roles', 'profilable']);
        });
    }

    /**
     * Buat user baru yang menempel ke MitraJasa.
     */
    public function createForMitraJasa(
        MitraJasa $mitra,
        string $email,
        array $roles,
        ?string $password = null,
    ): User {
        $this->guardEmailUnique($email);
        $this->guardMitraBelumPunyaUser($mitra);

        return DB::transaction(function () use ($mitra, $email, $roles, $password) {
            $user = User::create([
                'email' => $email,
                'password' => Hash::make($password ?? $this->randomPassword()),
                'profilable_type' => MitraJasa::class,
                'profilable_id' => $mitra->id,
                'email_verified_at' => now(),
            ]);

            // Mitra harus punya role Mitra Jasa minimal; tambahkan jika tidak disertakan.
            $roles = array_unique(array_merge($this->sanitizeRoles($roles), ['Mitra Jasa']));
            $user->syncRoles($roles);

            return $user->fresh(['roles', 'profilable']);
        });
    }

    /**
     * Buat akun sistem yang TIDAK terhubung ke pegawai/mitra.
     * Hanya boleh untuk role 'Super Admin'.
     */
    public function createSystemAccount(
        string $email,
        array $roles,
        ?string $password = null,
    ): User {
        $this->guardEmailUnique($email);

        $sanitized = $this->sanitizeRoles($roles);
        if (! in_array('Super Admin', $sanitized, true)) {
            throw new \DomainException('Akun sistem hanya diperbolehkan untuk role Super Admin.');
        }

        return DB::transaction(function () use ($email, $sanitized, $password) {
            $user = User::withoutEvents(function () use ($email, $password) {
                return User::create([
                    'email' => $email,
                    'password' => Hash::make($password ?? $this->randomPassword()),
                    'profilable_type' => null,
                    'profilable_id' => null,
                    'email_verified_at' => now(),
                ]);
            });

            $user->syncRoles($sanitized);

            return $user->fresh('roles');
        });
    }

    /**
     * Update email user (profilable tidak boleh diganti dari halaman ini).
     */
    public function updateEmail(User $user, string $email): User
    {
        if ($email !== $user->email) {
            $this->guardEmailUnique($email, exceptUserId: $user->id);
            $user->update(['email' => $email]);
        }

        return $user->fresh();
    }

    /**
     * Sinkronisasi role user. Berikan array nama role.
     */
    public function syncRoles(User $user, array $roles): User
    {
        $sanitized = $this->sanitizeRoles($roles);

        // Jaga: minimal harus selalu ada satu Super Admin di sistem.
        $isLastSuperAdmin = $user->hasRole('Super Admin')
            && ! in_array('Super Admin', $sanitized, true)
            && User::role('Super Admin')->count() <= 1;

        if ($isLastSuperAdmin) {
            throw new \DomainException('Tidak bisa mencabut role Super Admin terakhir di sistem.');
        }

        // Mitra Jasa wajib tetap punya role Mitra Jasa selama profilable-nya MitraJasa.
        if ($user->profilable_type === MitraJasa::class
            && ! in_array('Mitra Jasa', $sanitized, true)
        ) {
            $sanitized[] = 'Mitra Jasa';
        }

        $user->syncRoles($sanitized);

        return $user->fresh('roles');
    }

    /**
     * Reset password user dan kembalikan password plaintext baru.
     */
    public function resetPassword(User $user, ?string $password = null): string
    {
        $plain = $password ?? $this->randomPassword();
        $user->forceFill(['password' => Hash::make($plain)])->save();

        return $plain;
    }

    /**
     * Hapus user dengan beberapa pengaman:
     *  - Tidak boleh menghapus diri sendiri.
     *  - Tidak boleh menghapus Super Admin terakhir.
     */
    public function deleteUser(User $user, ?int $actorId = null): void
    {
        if ($actorId !== null && (int) $actorId === (int) $user->id) {
            throw new \DomainException('Anda tidak bisa menghapus akun Anda sendiri.');
        }

        if ($user->hasRole('Super Admin') && User::role('Super Admin')->count() <= 1) {
            throw new \DomainException('Tidak bisa menghapus Super Admin terakhir di sistem.');
        }

        $user->delete();
    }

    /* ----------------------------------------------------------------------
     | Helpers
     |---------------------------------------------------------------------- */

    private function guardEmailUnique(string $email, ?int $exceptUserId = null): void
    {
        $query = User::where('email', $email);
        if ($exceptUserId !== null) {
            $query->where('id', '!=', $exceptUserId);
        }

        if ($query->exists()) {
            throw new \DomainException("Email {$email} sudah dipakai oleh user lain.");
        }
    }

    private function guardPegawaiBelumPunyaUser(MasterPegawai $pegawai): void
    {
        $exists = User::where('profilable_type', MasterPegawai::class)
            ->where('profilable_id', $pegawai->id)
            ->exists();

        if ($exists) {
            throw new \DomainException("Pegawai {$pegawai->nama_lengkap} sudah memiliki akun.");
        }
    }

    private function guardMitraBelumPunyaUser(MitraJasa $mitra): void
    {
        $exists = User::where('profilable_type', MitraJasa::class)
            ->where('profilable_id', $mitra->id)
            ->exists();

        if ($exists) {
            throw new \DomainException("Mitra {$mitra->nama_mitra} sudah memiliki akun.");
        }
    }

    /**
     * Filter role hanya yang benar-benar terdaftar di guard 'web'.
     */
    private function sanitizeRoles(array $roles): array
    {
        $valid = Role::where('guard_name', 'web')
            ->whereIn('name', $roles)
            ->pluck('name')
            ->all();

        return array_values(array_unique($valid));
    }

    private function randomPassword(int $length = 12): string
    {
        return Str::password($length, symbols: false);
    }
}
