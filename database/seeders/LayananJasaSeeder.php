<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LayananJasaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            ['nama_layanan' => 'Penyimpanan Pesawat - ( PJP4U )', 'pic_name' => 'Melly'],
            ['nama_layanan' => 'PJPC (counter)', 'pic_name' => 'Andini'],
            ['nama_layanan' => 'PJP2U', 'pic_name' => 'Andini'],
            ['nama_layanan' => 'JKP2U (Jasa Layanan Kargo dan Pos Pesawat Udara)', 'pic_name' => 'Andini'],
            ['nama_layanan' => 'GARBARATA', 'pic_name' => 'Zalfa'],
            ['nama_layanan' => 'EXTEND', 'pic_name' => 'Melly'],
            ['nama_layanan' => 'Penggunaan Bandara sebagai Bandara Alternatif', 'pic_name' => 'Melly'],
            ['nama_layanan' => 'Penanganan Jenazah', 'pic_name' => 'Andini'],
            ['nama_layanan' => 'Sewa Tanah - Penggunaan Lahan', 'pic_name' => 'Diah'],
            ['nama_layanan' => 'Sewa Ruangan', 'pic_name' => 'Kemal'],
            ['nama_layanan' => 'Penempatan Mesin ATM', 'pic_name' => 'Kemal'],
            ['nama_layanan' => 'Pemotretan Shooting', 'pic_name' => 'Kuling'],
            ['nama_layanan' => 'Tiang Pancang Reklame', 'pic_name' => 'Kemal'],
            ['nama_layanan' => 'Pemasangan Reklame', 'pic_name' => 'Kemal'],
            ['nama_layanan' => 'Bis Apron', 'pic_name' => 'Danti'],
            ['nama_layanan' => 'Kendaraan/Peralatan dan Mesin', 'pic_name' => 'Danti'],
            ['nama_layanan' => 'Sewa Kendaraan Bermotor Roda 4 atau lebih', 'pic_name' => 'Danti'],
            ['nama_layanan' => 'Penggunaan X-RAY', 'pic_name' => 'Diah'],
            ['nama_layanan' => 'Pemakaian Listrik', 'pic_name' => 'Kuling'],
            ['nama_layanan' => 'PAS Orang', 'pic_name' => 'Henny'],
            ['nama_layanan' => 'TIM', 'pic_name' => 'Risma'],
            ['nama_layanan' => 'Telekomunikasi', 'pic_name' => 'Danti'],
            ['nama_layanan' => 'Pemakaian Air', 'pic_name' => 'Kuling'],
            ['nama_layanan' => 'Pemeriksaan Kargo dan Pos', 'pic_name' => 'Diah'],
            ['nama_layanan' => 'FIELD TRIP', 'pic_name' => 'Zam zam'],
            ['nama_layanan' => 'Bengkel Kendaraan Bermotor', 'pic_name' => 'Danti'],
            ['nama_layanan' => 'Parkir Inap', 'pic_name' => 'Kemal'],
            ['nama_layanan' => 'Penggunaan Hanggar', 'pic_name' => 'Kemal'],
            ['nama_layanan' => 'KONSESI', 'pic_name' => 'Danti'],
            ['nama_layanan' => 'Sewa Ruang Rapat', 'pic_name' => 'Kemal'],
            ['nama_layanan' => 'SEWA PERALATAN WORKSHOP', 'pic_name' => 'Danti'],
            ['nama_layanan' => 'DENDA Pekerjaan', 'pic_name' => 'Keu'],
            ['nama_layanan' => 'Penggunaan Fasilitas dan Personil di Luar Jam Operasional Bandar Udara', 'pic_name' => null],
            ['nama_layanan' => 'REKLAME', 'pic_name' => null],
            ['nama_layanan' => 'PAS KENDARAAN', 'pic_name' => null],
            ['nama_layanan' => 'Bunga Rekening Pengeluaran', 'pic_name' => null],
        ];

        foreach ($services as $index => $service) {
            \App\Models\LayananJasa::updateOrCreate(
                ['nama_layanan' => $service['nama_layanan']],
                [
                    'kode_layanan' => 'JASA-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                    'pic_name' => $service['pic_name'],
                    'tarif_dasar' => 0,
                ]
            );
        }
    }
}
