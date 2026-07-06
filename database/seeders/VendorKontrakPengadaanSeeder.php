<?php

namespace Database\Seeders;

use App\Models\MasterMitraVendor;
use Illuminate\Database\Seeder;

/**
 * Contoh 2 vendor untuk modul Kontrak Pengadaan (master_pihak kategori
 * PENGELUARAN) beserta rekening bank default masing-masing — mengikuti pola
 * SupplierController::store. no_telepon diisi nomor valid karena wajib
 * untuk pengiriman akses TTE (BAP wajib) via WhatsApp.
 *
 * Tidak didaftarkan di DatabaseSeeder (data contoh, bukan master).
 * Jalankan manual: php artisan db:seed --class=VendorKontrakPengadaanSeeder
 */
class VendorKontrakPengadaanSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = [
            [
                'pihak' => [
                    'kategori' => 'PENGELUARAN',
                    'jenis_entitas' => 'BADAN_USAHA',
                    'nama_pihak' => 'PT Karya Borneo Sejahtera',
                    'nama_penanggung_jawab' => 'Andi Prasetyo',
                    'jabatan_penandatangan' => 'Direktur Utama',
                    'npwp' => '01.234.567.8-722.000',
                    'alamat' => 'Jl. Ir. H. Juanda No. 88, Samarinda, Kalimantan Timur',
                    'email' => 'admin@karyaborneo.co.id',
                    'no_telepon' => '081250123456',
                    'status_aktif' => true,
                ],
                'rekening' => [
                    'nama_bank' => 'Bank Mandiri',
                    'nomor_rekening' => '1480011223344',
                    'nama_rekening' => 'PT Karya Borneo Sejahtera',
                ],
            ],
            [
                'pihak' => [
                    'kategori' => 'PENGELUARAN',
                    'jenis_entitas' => 'BADAN_USAHA',
                    'nama_pihak' => 'CV Mahakam Teknik Mandiri',
                    'nama_penanggung_jawab' => 'Siti Rahmawati',
                    'jabatan_penandatangan' => 'Direktur',
                    'npwp' => '02.345.678.9-722.000',
                    'alamat' => 'Jl. P. Antasari No. 15, Samarinda, Kalimantan Timur',
                    'email' => 'kontak@mahakamteknik.co.id',
                    'no_telepon' => '082154987654',
                    'status_aktif' => true,
                ],
                'rekening' => [
                    'nama_bank' => 'Bank BNI',
                    'nomor_rekening' => '0987654321',
                    'nama_rekening' => 'CV Mahakam Teknik Mandiri',
                ],
            ],
        ];

        foreach ($vendors as $data) {
            $vendor = MasterMitraVendor::updateOrCreate(
                ['npwp' => $data['pihak']['npwp']],
                $data['pihak'],
            );

            $vendor->rekening()->updateOrCreate(
                ['nomor_rekening' => $data['rekening']['nomor_rekening']],
                $data['rekening'] + ['is_default' => true, 'status_aktif' => true],
            );
        }
    }
}
