<?php

namespace App\Services;

use App\Models\MitraJasa;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class MitraAccountService
{
    public function createOrGetAccount(MitraJasa $mitra): array
    {
        if ($mitra->user) {
            return ['user' => $mitra->user, 'password' => null, 'is_new' => false];
        }

        if (! $mitra->email) {
            throw new \InvalidArgumentException('Email mitra wajib diisi sebelum membuat akun.');
        }

        if (User::where('email', $mitra->email)->exists()) {
            throw new \InvalidArgumentException('Email mitra sudah digunakan oleh akun lain.');
        }

        $password = Str::password(10);
        Role::findOrCreate('Mitra Jasa', 'web');

        $user = User::create([
            'email' => $mitra->email,
            'password' => Hash::make($password),
            'profilable_type' => MitraJasa::class,
            'profilable_id' => $mitra->id,
        ]);
        $user->assignRole('Mitra Jasa');

        return ['user' => $user, 'password' => $password, 'is_new' => true];
    }

    public function resetPassword(MitraJasa $mitra): array
    {
        $user = $mitra->user;

        if (! $user) {
            throw new \InvalidArgumentException('Akun mitra belum tersedia.');
        }

        $password = Str::password(10);
        $user->forceFill([
            'password' => Hash::make($password),
        ])->save();

        return ['user' => $user, 'password' => $password];
    }
}
