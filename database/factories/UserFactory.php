<?php

namespace Database\Factories;

use App\Models\MasterPegawai;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * Kolom `name` sudah di-drop dari tabel `users`
     * (migration 2026_04_27_020000_drop_name_from_users_table), jadi tidak
     * boleh diset lagi. Selain itu User::booted() mewajibkan setiap user
     * terhubung ke profilable yang sah; secara default kita pasang
     * `MasterPegawai` agar `User::factory()->create()` lolos guard tanpa
     * butuh env SKIP_USER_PROFILABLE_GUARD.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'profilable_type' => MasterPegawai::class,
            'profilable_id' => MasterPegawai::factory(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Hubungkan user ke seorang MasterPegawai.
     *
     * Tanpa argumen: membuat pegawai baru (sama seperti state default, tapi
     * eksplisit). Dengan argumen: pakai pegawai yang sudah ada.
     */
    public function pegawai(?MasterPegawai $pegawai = null): static
    {
        return $this->forProfilable($pegawai ?? MasterPegawai::factory());
    }

    /**
     * Pasang profilable polymorphic apa pun (MasterPegawai, MasterPihak, dll.)
     * yang diizinkan oleh guard di User::booted().
     *
     * @param  \App\Models\MasterPegawai|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Factories\Factory  $profilable
     */
    public function forProfilable(Model|Factory $profilable): static
    {
        if ($profilable instanceof Factory) {
            return $this->state(fn (array $attributes) => [
                'profilable_type' => $profilable->modelName(),
                'profilable_id' => $profilable,
            ]);
        }

        return $this->state(fn (array $attributes) => [
            'profilable_type' => $profilable->getMorphClass(),
            'profilable_id' => $profilable->getKey(),
        ]);
    }
}
