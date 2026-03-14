<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'email' => 'garuda.tawakal.abadi@test.com',
                'user_name' => 'PT. GARUDA TAWAKAL ABADI',
                'supplier_name' => 'PT. GARUDA TAWAKAL ABADI',
                'type' => '02 Penyedia Barang Jasa',
                'address' => 'Jl. Griyo Mapan Sentosa EA1 No.2B RT.042 RW.005',
                'npwp' => '03.008.751.4-617.000',
                'bank_name' => 'Bank Tabungan Negara',
                'bank_account' => '0006201880003220',
                'account_name' => 'PT.GARUDA TAWAKAL ABADI',
                'phone' => null,
            ],
            [
                'email' => 'mahakam.raya.harmoni@test.com',
                'user_name' => 'CV. MAHAKAM RAYA HARMONI',
                'supplier_name' => 'CV. MAHAKAM RAYA HARMONI',
                'type' => '02 Penyedia Barang Jasa',
                'address' => 'Jl. Gunung Lingai, Kec. Sungai Pinang, Kota Samarinda, Provinsi Kalimantan Timur, Kode Pos 75119',
                'npwp' => '00.807.8608.0-722.000',
                'bank_name' => 'Bank Tabungan Negara',
                'bank_account' => '2001300006889',
                'account_name' => 'CV. MAHAKAM RAYA HARMONI',
                'phone' => null,
            ],
            [
                'email' => 'guna.akhlak.sukses@test.com',
                'user_name' => 'CV. GUNA AKHLAK SUKSES',
                'supplier_name' => 'CV. GUNA AKHLAK SUKSES',
                'type' => '02 Penyedia Barang Jasa',
                'address' => 'Jl. Harun Nafsi, RT. 15 Kel. Rapak Dalam, Kec. Loa Jalan Ilir Kota Samarinda, Kalimantan Timur 75131',
                'npwp' => '53.129.349.6-741.000',
                'bank_name' => 'Bank Tabungan Negara',
                'bank_account' => '2001300006805',
                'account_name' => 'CV. GUNA AKHLAK SUKSES',
                'phone' => null,
            ],
            [
                'email' => 'jaya.kencana@test.com',
                'user_name' => 'PT. JAYA KENCANA',
                'supplier_name' => 'PT. JAYA KENCANA',
                'type' => '02 Penyedia Barang Jasa',
                'address' => 'Jl. Rungkut Industri No.9 Surabaya',
                'npwp' => '01.307.296.2-073.000',
                'bank_name' => 'Bank Central Asia',
                'bank_account' => '0103171358',
                'account_name' => 'PT. JAYA KENCANA',
                'phone' => null,
            ],
            [
                'email' => 'anugerah.zikir.keluarga.utama@test.com',
                'user_name' => 'CV. ANUGERAH ZIKIR KELUARGA UTAMA',
                'supplier_name' => 'CV. ANUGERAH ZIKIR KELUARGA UTAMA',
                'type' => '02 Penyedia Barang Jasa',
                'address' => 'Perum. Sambutan Permai BJ No. 013 RT. 022 RW. 000 Kel. Sambutan, Kec. Sambutan Kota Samarinda.',
                'npwp' => '92.621.414.9-722.000',
                'bank_name' => 'Bank Mandiri',
                'bank_account' => '1480017380281',
                'account_name' => 'CV. ANUGERAH ZIKIR KELUARGA UTAMA',
                'phone' => null,
            ],
        ];

        foreach ($suppliers as $item) {
            $mitra = User::updateOrCreate(
                ['email' => $item['email']],
                [
                    'name' => $item['user_name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]
            );

            $mitra->syncRoles(['Mitra']);

            Supplier::updateOrCreate(
                ['user_id' => $mitra->id],
                [
                    'name' => $item['supplier_name'],
                    'type' => $item['type'],
                    'address' => $item['address'],
                    'npwp' => $item['npwp'],
                    'bank_name' => $item['bank_name'],
                    'bank_account' => $item['bank_account'],
                    'account_name' => $item['account_name'],
                    'phone' => $item['phone'],
                ]
            );
        }
    }
}