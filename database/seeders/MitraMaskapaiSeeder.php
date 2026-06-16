<?php

namespace Database\Seeders;

use App\Models\MitraJasa;
use Illuminate\Database\Seeder;

class MitraMaskapaiSeeder extends Seeder
{
    public function run(): void
    {
        $airlines = [
            [
                'kode_mitra' => 'GIA',
                'nama_mitra' => 'GARUDA INDONESIA',
                'npwp' => '01.001.634.6-093.000',
                'email' => 'finance@garuda-indonesia.com',
                'no_telepon' => '0218011801',
                'alamat' => 'Garuda City Center, Soekarno-Hatta Intl Airport, Tangerang',
                'nama_penanggung_jawab' => 'Station Manager Garuda YIA',
                'jabatan_penanggung_jawab' => 'Station Manager',
            ],
            [
                'kode_mitra' => 'CIT',
                'nama_mitra' => 'CITILINK INDONESIA',
                'npwp' => '02.654.215.3-093.000',
                'email' => 'finance@citilink.co.id',
                'no_telepon' => '08041080808',
                'alamat' => 'Citilink Operation Center, Soekarno-Hatta Intl Airport',
                'nama_penanggung_jawab' => 'Station Manager Citilink YIA',
                'jabatan_penanggung_jawab' => 'Station Manager',
            ],
            [
                'kode_mitra' => 'LNI',
                'nama_mitra' => 'LION AIR',
                'npwp' => '01.748.911.5-093.000',
                'email' => 'finance@lionair.co.id',
                'no_telepon' => '08041778899',
                'alamat' => 'Lion Air Tower, Jl. Gajah Mada No. 7, Jakarta',
                'nama_penanggung_jawab' => 'Station Manager Lion Air YIA',
                'jabatan_penanggung_jawab' => 'Station Manager',
            ],
            [
                'kode_mitra' => 'BTK',
                'nama_mitra' => 'BATIK AIR',
                'npwp' => '03.215.456.7-093.000',
                'email' => 'finance@batikair.com',
                'no_telepon' => '08041780808',
                'alamat' => 'Batik Air Office, Soekarno-Hatta Intl Airport',
                'nama_penanggung_jawab' => 'Station Manager Batik Air YIA',
                'jabatan_penanggung_jawab' => 'Station Manager',
            ],
            [
                'kode_mitra' => 'WIA',
                'nama_mitra' => 'WINGS AIR',
                'npwp' => '02.987.654.3-093.000',
                'email' => 'finance@wingsair.co.id',
                'no_telepon' => '08041778800',
                'alamat' => 'Wings Air Operation Office, Soekarno-Hatta Intl Airport',
                'nama_penanggung_jawab' => 'Station Manager Wings Air YIA',
                'jabatan_penanggung_jawab' => 'Station Manager',
            ],
            [
                'kode_mitra' => 'SAJ',
                'nama_mitra' => 'SUPER AIR JET',
                'npwp' => '04.111.222.3-093.000',
                'email' => 'finance@superairjet.com',
                'no_telepon' => '02129507777',
                'alamat' => 'Super Air Jet Office, Soekarno-Hatta Intl Airport',
                'nama_penanggung_jawab' => 'Station Manager Super Air Jet YIA',
                'jabatan_penanggung_jawab' => 'Station Manager',
            ],
            [
                'kode_mitra' => 'SJY',
                'nama_mitra' => 'SRIWIJAYA AIR',
                'npwp' => '01.762.345.6-093.000',
                'email' => 'finance@sriwijayaair.co.id',
                'no_telepon' => '08041777777',
                'alamat' => 'Sriwijaya Air Tower, Jl. Pangeran Jayakarta No. 68, Jakarta',
                'nama_penanggung_jawab' => 'Station Manager Sriwijaya Air YIA',
                'jabatan_penanggung_jawab' => 'Station Manager',
            ],
            [
                'kode_mitra' => 'NAM',
                'nama_mitra' => 'NAM AIR',
                'npwp' => '03.444.555.6-093.000',
                'email' => 'finance@namair.co.id',
                'no_telepon' => '08041886677',
                'alamat' => 'NAM Air Office, Jl. Pangeran Jayakarta No. 68, Jakarta',
                'nama_penanggung_jawab' => 'Station Manager NAM Air YIA',
                'jabatan_penanggung_jawab' => 'Station Manager',
            ],
            [
                'kode_mitra' => 'AWQ',
                'nama_mitra' => 'INDONESIA AIRASIA',
                'npwp' => '02.123.789.4-093.000',
                'email' => 'finance@airasia.co.id',
                'no_telepon' => '02129270999',
                'alamat' => 'AirAsia Operation Office, Soekarno-Hatta Intl Airport',
                'nama_penanggung_jawab' => 'Station Manager AirAsia YIA',
                'jabatan_penanggung_jawab' => 'Station Manager',
            ],
            [
                'kode_mitra' => 'TGN',
                'nama_mitra' => 'TRANSNUSA AVIATION MANDIRI',
                'npwp' => '03.876.543.2-093.000',
                'email' => 'finance@transnusa.co.id',
                'no_telepon' => '02129507788',
                'alamat' => 'TransNusa Office, Halim Perdanakusuma Airport, Jakarta',
                'nama_penanggung_jawab' => 'Station Manager TransNusa YIA',
                'jabatan_penanggung_jawab' => 'Station Manager',
            ],
            [
                'kode_mitra' => 'PAS',
                'nama_mitra' => 'PELITA AIR SERVICE',
                'npwp' => '01.555.666.7-093.000',
                'email' => 'finance@pelita-air.com',
                'no_telepon' => '02129507766',
                'alamat' => 'Pelita Air Office, Pondok Cabe Airport, Tangerang Selatan',
                'nama_penanggung_jawab' => 'Station Manager Pelita Air YIA',
                'jabatan_penanggung_jawab' => 'Station Manager',
            ],
            [
                'kode_mitra' => 'SQS',
                'nama_mitra' => 'SUSI AIR',
                'npwp' => '02.333.444.5-093.000',
                'email' => 'finance@susiair.com',
                'no_telepon' => '02129500001',
                'alamat' => 'Susi Air Office, Nusawiru Airport, Pangandaran',
                'nama_penanggung_jawab' => 'Station Manager Susi Air YIA',
                'jabatan_penanggung_jawab' => 'Station Manager',
            ],
        ];

        foreach ($airlines as $airline) {
            MitraJasa::updateOrCreate(
                ['kode_mitra' => $airline['kode_mitra']],
                array_merge($airline, [
                    'jenis_mitra' => 'Maskapai',
                    'status_aktif' => true,
                ]),
            );
        }

        $this->command?->info('Seeded ' . count($airlines) . ' mitra maskapai.');
    }
}
