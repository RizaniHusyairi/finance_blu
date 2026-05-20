<?php

namespace Database\Factories;

use App\Models\MasterTarifPajak;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MasterTarifPajak>
 */
class MasterTarifPajakFactory extends Factory
{
    /**
     * The model that this factory builds.
     *
     * @var class-string<\App\Models\MasterTarifPajak>
     */
    protected $model = MasterTarifPajak::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_pajak' => strtoupper($this->faker->unique()->lexify('PJK???')),
            'jenis_pajak' => 'PPh Pasal 21',
            'persentase' => 5,
            'rumus' => null,
            'berlaku_mulai' => now()->subYear()->toDateString(),
            'berlaku_sampai' => null,
            'status_aktif' => true,
        ];
    }

    /**
     * State untuk PPh 21 aktif yang siap dipakai oleh modul penyetoran pajak honorarium.
     *
     * Memenuhi kontrak agar `MasterTarifPajak` aktif untuk PPh 21 dapat di-resolve oleh
     * `SppController::storeHonor` dan `PenyetoranPajakHonorController` (kode `PPH21-TER`,
     * `status_aktif = true`, `persentase > 0`, `jenis_pajak = 'PPh Pasal 21'`).
     */
    public function pph21Aktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'kode_pajak' => 'PPH21-TER',
            'jenis_pajak' => 'PPh Pasal 21',
            'persentase' => 5,
            'status_aktif' => true,
            'berlaku_mulai' => now()->subYear()->toDateString(),
            'berlaku_sampai' => null,
        ]);
    }

    /**
     * State untuk tarif pajak nonaktif.
     */
    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_aktif' => false,
        ]);
    }
}
