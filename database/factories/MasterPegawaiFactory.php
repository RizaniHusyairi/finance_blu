<?php

namespace Database\Factories;

use App\Models\MasterPegawai;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MasterPegawai>
 */
class MasterPegawaiFactory extends Factory
{
    /**
     * The model that this factory builds.
     *
     * @var class-string<\App\Models\MasterPegawai>
     */
    protected $model = MasterPegawai::class;

    /**
     * Define the model's default state.
     *
     * Hanya `nama_lengkap` yang NOT NULL pada tabel `master_pegawai`
     * (lihat migration create_master_data_inti_v2). Kolom lain nullable,
     * tapi diisi nilai wajar agar record terlihat realistis di test.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_lengkap' => $this->faker->name(),
            'nip' => $this->faker->unique()->numerify('19##########'),
            'jabatan' => $this->faker->jobTitle(),
            'status_aktif' => true,
        ];
    }

    /**
     * State untuk pegawai nonaktif.
     */
    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_aktif' => false,
        ]);
    }
}
