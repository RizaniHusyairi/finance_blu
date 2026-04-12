<?php

namespace Database\Seeders;

use App\Models\MasterTarifPajak;
use Illuminate\Database\Seeder;

class MasterTarifPajakSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'kode_pajak'     => 'PPN11',
                'jenis_pajak'    => 'PPN',
                'persentase'     => 11,
                'rumus'          => 'DPP x 11%',
                'berlaku_mulai'  => '2022-04-01',
                'berlaku_sampai' => null,
                'status_aktif'   => true,
            ],
            [
                'kode_pajak'     => 'PPN12',
                'jenis_pajak'    => 'PPN',
                'persentase'     => 12,
                'rumus'          => 'DPP x 12%',
                'berlaku_mulai'  => '2025-01-01',
                'berlaku_sampai' => null,
                'status_aktif'   => true,
            ],
            [
                'kode_pajak'     => 'PPH21-TER',
                'jenis_pajak'    => 'PPh Pasal 21',
                'persentase'     => 5,
                'rumus'          => 'Penghasilan bruto x tarif efektif rata-rata (TER)',
                'berlaku_mulai'  => '2024-01-01',
                'berlaku_sampai' => null,
                'status_aktif'   => true,
            ],
            [
                'kode_pajak'     => 'PPH22-BEND',
                'jenis_pajak'    => 'PPh Pasal 22 Bendaharawan',
                'persentase'     => 1.5,
                'rumus'          => 'Harga pembelian (tidak termasuk PPN) x 1,5%',
                'berlaku_mulai'  => '2022-01-01',
                'berlaku_sampai' => null,
                'status_aktif'   => true,
            ],
            [
                'kode_pajak'     => 'PPH23-JASA',
                'jenis_pajak'    => 'PPh Pasal 23 Jasa',
                'persentase'     => 2,
                'rumus'          => 'Jumlah bruto x 2%',
                'berlaku_mulai'  => '2022-01-01',
                'berlaku_sampai' => null,
                'status_aktif'   => true,
            ],
            [
                'kode_pajak'     => 'PPH23-SEWA',
                'jenis_pajak'    => 'PPh Pasal 23 Sewa',
                'persentase'     => 2,
                'rumus'          => 'Jumlah bruto sewa x 2%',
                'berlaku_mulai'  => '2022-01-01',
                'berlaku_sampai' => null,
                'status_aktif'   => true,
            ],
            [
                'kode_pajak'     => 'PPH4A2-KONST',
                'jenis_pajak'    => 'PPh Final Pasal 4(2) Jasa Konstruksi',
                'persentase'     => 1.75,
                'rumus'          => 'Nilai kontrak (tidak termasuk PPN) x 1,75%',
                'berlaku_mulai'  => '2022-01-01',
                'berlaku_sampai' => null,
                'status_aktif'   => true,
            ],
            [
                'kode_pajak'     => 'PPH4A2-SEWA-TB',
                'jenis_pajak'    => 'PPh Final Pasal 4(2) Sewa Tanah/Bangunan',
                'persentase'     => 10,
                'rumus'          => 'Jumlah bruto sewa x 10%',
                'berlaku_mulai'  => '2022-01-01',
                'berlaku_sampai' => null,
                'status_aktif'   => true,
            ],
            [
                'kode_pajak'     => 'PPH15-CHARTER',
                'jenis_pajak'    => 'PPh Pasal 15 Charter Penerbangan',
                'persentase'     => 1.8,
                'rumus'          => 'Jumlah bruto imbalan x 1,8%',
                'berlaku_mulai'  => '2022-01-01',
                'berlaku_sampai' => null,
                'status_aktif'   => true,
            ],
            [
                'kode_pajak'     => 'PPN-WAPU',
                'jenis_pajak'    => 'PPN Wapu Bendaharawan',
                'persentase'     => 11,
                'rumus'          => 'DPP x 11% (dipungut bendaharawan)',
                'berlaku_mulai'  => '2022-04-01',
                'berlaku_sampai' => null,
                'status_aktif'   => true,
            ],
        ];

        foreach ($data as $item) {
            MasterTarifPajak::updateOrCreate(
                ['kode_pajak' => $item['kode_pajak']],
                $item
            );
        }
    }
}
