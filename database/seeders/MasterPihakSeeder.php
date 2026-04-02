<?php

namespace Database\Seeders;

use App\Models\MasterPihak;
use App\Models\RekeningBank;
use Illuminate\Database\Seeder;

class MasterPihakSeeder extends Seeder
{
    public function run(): void
    {
        $pihaks = [
            [
                'kategori' => 'PENGELUARAN',
                'jenis_entitas' => 'BADAN_USAHA',
                'kode_pihak' => 'VND-001',
                'npwp' => '01.234.567.8-901.000',
                'nama_pihak' => 'PT Maju Jaya Konstruksi',
                'nama_penanggung_jawab' => 'Andi Pratama',
                'tipe_supplier' => 'VENDOR',
                'alamat' => 'Jl. Ahmad Yani No. 10, Makassar',
                'email' => 'admin@majukonstruksi.co.id',
                'no_telepon' => '0411-555100',
                'status_aktif' => true,
                'rekening' => [
                    'nama_bank' => 'Bank Mandiri',
                    'nomor_rekening' => '1230001112223',
                    'nama_rekening' => 'PT Maju Jaya Konstruksi',
                    'kode_bank' => '008',
                ],
            ],
            [
                'kategori' => 'PENGELUARAN',
                'jenis_entitas' => 'BADAN_USAHA',
                'kode_pihak' => 'VND-002',
                'npwp' => '02.345.678.9-012.000',
                'nama_pihak' => 'CV Sinar Teknologi Nusantara',
                'nama_penanggung_jawab' => 'Rizal Hidayat',
                'tipe_supplier' => 'VENDOR',
                'alamat' => 'Jl. Perintis Kemerdekaan No. 25, Makassar',
                'email' => 'kontak@sinarteknologi.id',
                'no_telepon' => '0411-555200',
                'status_aktif' => true,
                'rekening' => [
                    'nama_bank' => 'BNI',
                    'nomor_rekening' => '9876543210',
                    'nama_rekening' => 'CV Sinar Teknologi Nusantara',
                    'kode_bank' => '009',
                ],
            ],
            [
                'kategori' => 'PENERIMAAN',
                'jenis_entitas' => 'INSTANSI',
                'kode_pihak' => 'MTR-001',
                'npwp' => null,
                'nama_pihak' => 'Dinas Kesehatan Provinsi Sulawesi Selatan',
                'nama_penanggung_jawab' => 'Dr. H. Rahmat Saleh',
                'tipe_supplier' => 'MITRA',
                'alamat' => 'Jl. Jenderal Sudirman No. 88, Makassar',
                'email' => 'dinkes@sulselprov.go.id',
                'no_telepon' => '0411-555300',
                'status_aktif' => true,
                'rekening' => [
                    'nama_bank' => 'Bank Sulselbar',
                    'nomor_rekening' => '1002003004005',
                    'nama_rekening' => 'Dinas Kesehatan Prov. Sulsel',
                    'kode_bank' => '126',
                ],
            ],
            [
                'kategori' => 'KEDUANYA',
                'jenis_entitas' => 'PERORANGAN',
                'kode_pihak' => 'PRG-001',
                'npwp' => '12.345.678.9-111.000',
                'nama_pihak' => 'Muhammad Fadli',
                'nama_penanggung_jawab' => 'Muhammad Fadli',
                'tipe_supplier' => 'PERSONAL',
                'alamat' => 'Jl. Emmy Saelan No. 7, Makassar',
                'email' => 'fadli@example.com',
                'no_telepon' => '081355500111',
                'status_aktif' => true,
                'rekening' => [
                    'nama_bank' => 'BRI',
                    'nomor_rekening' => '66001122334455',
                    'nama_rekening' => 'Muhammad Fadli',
                    'kode_bank' => '002',
                ],
            ],
        ];

        foreach ($pihaks as $item) {
            $rekening = $item['rekening'];
            unset($item['rekening']);

            $pihak = MasterPihak::updateOrCreate(
                ['kode_pihak' => $item['kode_pihak']],
                $item
            );

            RekeningBank::updateOrCreate(
                [
                    'pemilik_type' => MasterPihak::class,
                    'pemilik_id' => $pihak->id,
                    'nomor_rekening' => $rekening['nomor_rekening'],
                ],
                array_merge($rekening, [
                    'pemilik_type' => MasterPihak::class,
                    'pemilik_id' => $pihak->id,
                    'is_default' => true,
                    'status_aktif' => true,
                ])
            );
        }
    }
}
