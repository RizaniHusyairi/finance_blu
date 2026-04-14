<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MasterUangHarianPerjaldinSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['provinsi' => 'ACEH', 'luar_kota' => 360000, 'dalam_kota_lebih_8_jam' => 140000, 'diklat' => 110000],
            ['provinsi' => 'SUMATRA UTARA', 'luar_kota' => 370000, 'dalam_kota_lebih_8_jam' => 150000, 'diklat' => 110000],
            ['provinsi' => 'RIAU', 'luar_kota' => 370000, 'dalam_kota_lebih_8_jam' => 150000, 'diklat' => 110000],
            ['provinsi' => 'KEPULAUAN RIAU', 'luar_kota' => 370000, 'dalam_kota_lebih_8_jam' => 150000, 'diklat' => 110000],
            ['provinsi' => 'JAMBI', 'luar_kota' => 370000, 'dalam_kota_lebih_8_jam' => 150000, 'diklat' => 110000],
            ['provinsi' => 'SUMATRA BARAT', 'luar_kota' => 380000, 'dalam_kota_lebih_8_jam' => 150000, 'diklat' => 110000],
            ['provinsi' => 'SUMATRA SELATAN', 'luar_kota' => 380000, 'dalam_kota_lebih_8_jam' => 150000, 'diklat' => 110000],
            ['provinsi' => 'LAMPUNG', 'luar_kota' => 380000, 'dalam_kota_lebih_8_jam' => 150000, 'diklat' => 110000],
            ['provinsi' => 'BENGKULU', 'luar_kota' => 380000, 'dalam_kota_lebih_8_jam' => 150000, 'diklat' => 110000],
            ['provinsi' => 'BANGKA BELITUNG', 'luar_kota' => 410000, 'dalam_kota_lebih_8_jam' => 160000, 'diklat' => 120000],
            ['provinsi' => 'BANTEN', 'luar_kota' => 370000, 'dalam_kota_lebih_8_jam' => 150000, 'diklat' => 110000],
            ['provinsi' => 'JAWA BARAT', 'luar_kota' => 430000, 'dalam_kota_lebih_8_jam' => 170000, 'diklat' => 130000],
            ['provinsi' => 'D.K.I. JAKARTA', 'luar_kota' => 530000, 'dalam_kota_lebih_8_jam' => 210000, 'diklat' => 160000],
            ['provinsi' => 'JAWA TENGAH', 'luar_kota' => 370000, 'dalam_kota_lebih_8_jam' => 150000, 'diklat' => 110000],
            ['provinsi' => 'D.I. YOGYAKARTA', 'luar_kota' => 420000, 'dalam_kota_lebih_8_jam' => 170000, 'diklat' => 130000],
            ['provinsi' => 'JAWA TIMUR', 'luar_kota' => 410000, 'dalam_kota_lebih_8_jam' => 160000, 'diklat' => 120000],
            ['provinsi' => 'BALI', 'luar_kota' => 480000, 'dalam_kota_lebih_8_jam' => 190000, 'diklat' => 140000],
            ['provinsi' => 'NUSA TENGGARA BARAT', 'luar_kota' => 440000, 'dalam_kota_lebih_8_jam' => 180000, 'diklat' => 130000],
            ['provinsi' => 'NUSA TENGGARA TIMUR', 'luar_kota' => 430000, 'dalam_kota_lebih_8_jam' => 170000, 'diklat' => 130000],
            ['provinsi' => 'KALIMANTAN BARAT', 'luar_kota' => 380000, 'dalam_kota_lebih_8_jam' => 150000, 'diklat' => 110000],
            ['provinsi' => 'KALIMANTAN TENGAH', 'luar_kota' => 360000, 'dalam_kota_lebih_8_jam' => 140000, 'diklat' => 110000],
            ['provinsi' => 'KALIMANTAN SELATAN', 'luar_kota' => 380000, 'dalam_kota_lebih_8_jam' => 150000, 'diklat' => 110000],
            ['provinsi' => 'KALIMANTAN TIMUR', 'luar_kota' => 430000, 'dalam_kota_lebih_8_jam' => 170000, 'diklat' => 130000],
            ['provinsi' => 'KALIMANTAN UTARA', 'luar_kota' => 430000, 'dalam_kota_lebih_8_jam' => 170000, 'diklat' => 130000],
            ['provinsi' => 'SULAWESI UTARA', 'luar_kota' => 370000, 'dalam_kota_lebih_8_jam' => 150000, 'diklat' => 110000],
            ['provinsi' => 'GORONTALO', 'luar_kota' => 370000, 'dalam_kota_lebih_8_jam' => 150000, 'diklat' => 110000],
            ['provinsi' => 'SULAWESI BARAT', 'luar_kota' => 410000, 'dalam_kota_lebih_8_jam' => 160000, 'diklat' => 120000],
            ['provinsi' => 'SULAWESI SELATAN', 'luar_kota' => 430000, 'dalam_kota_lebih_8_jam' => 170000, 'diklat' => 130000],
            ['provinsi' => 'SULAWESI TENGAH', 'luar_kota' => 370000, 'dalam_kota_lebih_8_jam' => 150000, 'diklat' => 110000],
            ['provinsi' => 'SULAWESI TENGGARA', 'luar_kota' => 380000, 'dalam_kota_lebih_8_jam' => 150000, 'diklat' => 110000],
            ['provinsi' => 'MALUKU', 'luar_kota' => 380000, 'dalam_kota_lebih_8_jam' => 150000, 'diklat' => 110000],
            ['provinsi' => 'MALUKU UTARA', 'luar_kota' => 430000, 'dalam_kota_lebih_8_jam' => 170000, 'diklat' => 130000],
            ['provinsi' => 'PAPUA', 'luar_kota' => 580000, 'dalam_kota_lebih_8_jam' => 230000, 'diklat' => 170000],
            ['provinsi' => 'PAPUA BARAT', 'luar_kota' => 480000, 'dalam_kota_lebih_8_jam' => 190000, 'diklat' => 140000],
            ['provinsi' => 'PAPUA BARAT DAYA', 'luar_kota' => 480000, 'dalam_kota_lebih_8_jam' => 190000, 'diklat' => 140000],
            ['provinsi' => 'PAPUA TENGAH', 'luar_kota' => 580000, 'dalam_kota_lebih_8_jam' => 230000, 'diklat' => 170000],
            ['provinsi' => 'PAPUA SELATAN', 'luar_kota' => 580000, 'dalam_kota_lebih_8_jam' => 230000, 'diklat' => 170000],
            ['provinsi' => 'PAPUA PEGUNUNGAN', 'luar_kota' => 580000, 'dalam_kota_lebih_8_jam' => 230000, 'diklat' => 170000],
        ];

        foreach ($data as $row) {
            \App\Models\MasterUangHarianPerjaldin::updateOrCreate(
                ['provinsi' => $row['provinsi']],
                $row
            );
        }
    }
}
