<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterMitraVendor;
use App\Models\RekeningBank;

class MitraVendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vendors = [
            // ============================================================
            // VENDOR PENGELUARAN (Penyedia Barang/Jasa)
            // ============================================================
            [
                'vendor' => [
                    'kategori'         => 'VENDOR_PENGELUARAN',
                    'tipe_supplier'    => '02', // 02 = Penyedia
                    'npwp'             => '01.234.567.8-012.000',
                    'nama_perusahaan'  => 'PT Cahaya Teknologi Nusantara',
                    'nama_direktur'    => 'Budi Santoso',
                    'alamat'           => 'Jl. Jenderal Sudirman No. 45, Kelurahan Karet, Kecamatan Setiabudi, Jakarta Selatan 12920',
                    'no_telepon'       => '021-5201234',
                ],
                'rekening' => [
                    ['nama_bank' => 'BRI',     'nomor_rekening' => '0012-01-012345-50-8', 'nama_rekening' => 'PT Cahaya Teknologi Nusantara'],
                ],
            ],
            [
                'vendor' => [
                    'kategori'         => 'VENDOR_PENGELUARAN',
                    'tipe_supplier'    => '02',
                    'npwp'             => '02.345.678.9-023.000',
                    'nama_perusahaan'  => 'CV Mitra Abadi Sejahtera',
                    'nama_direktur'    => 'Siti Rahayu',
                    'alamat'           => 'Jl. Ahmad Yani No. 88, Kelurahan Sukaasih, Kecamatan Tangerang, Kota Tangerang, Banten 15111',
                    'no_telepon'       => '021-5523456',
                ],
                'rekening' => [
                    ['nama_bank' => 'BNI',     'nomor_rekening' => '0234567890', 'nama_rekening' => 'CV Mitra Abadi Sejahtera'],
                ],
            ],
            [
                'vendor' => [
                    'kategori'         => 'VENDOR_PENGELUARAN',
                    'tipe_supplier'    => '02',
                    'npwp'             => '03.456.789.0-034.000',
                    'nama_perusahaan'  => 'PT Bangun Jaya Konstruksi',
                    'nama_direktur'    => 'Ahmad Fauzan',
                    'alamat'           => 'Jl. Gatot Subroto Kav. 12, Kelurahan Menteng Dalam, Kecamatan Tebet, Jakarta Selatan 12870',
                    'no_telepon'       => '021-8301234',
                ],
                'rekening' => [
                    ['nama_bank' => 'Mandiri', 'nomor_rekening' => '1320012345678', 'nama_rekening' => 'PT Bangun Jaya Konstruksi'],
                ],
            ],
            [
                'vendor' => [
                    'kategori'         => 'VENDOR_PENGELUARAN',
                    'tipe_supplier'    => '02',
                    'npwp'             => '04.567.890.1-045.000',
                    'nama_perusahaan'  => 'PT Solusi Digital Indonesia',
                    'nama_direktur'    => 'Hendra Wijaya',
                    'alamat'           => 'Ruko Golden Boulevard Blok K No. 15, BSD City, Tangerang Selatan, Banten 15322',
                    'no_telepon'       => '021-5389012',
                ],
                'rekening' => [
                    ['nama_bank' => 'BCA',     'nomor_rekening' => '5270123456', 'nama_rekening' => 'PT Solusi Digital Indonesia'],
                ],
            ],
            [
                'vendor' => [
                    'kategori'         => 'VENDOR_PENGELUARAN',
                    'tipe_supplier'    => '02',
                    'npwp'             => '05.678.901.2-056.000',
                    'nama_perusahaan'  => 'CV Berkah Office Supply',
                    'nama_direktur'    => 'Dewi Kartika',
                    'alamat'           => 'Jl. Pemuda No. 22, Kelurahan Jati, Kecamatan Pulo Gadung, Jakarta Timur 13220',
                    'no_telepon'       => '021-4712345',
                ],
                'rekening' => [
                    ['nama_bank' => 'BRI',     'nomor_rekening' => '0056-01-067890-50-3', 'nama_rekening' => 'CV Berkah Office Supply'],
                ],
            ],
            [
                'vendor' => [
                    'kategori'         => 'VENDOR_PENGELUARAN',
                    'tipe_supplier'    => '02',
                    'npwp'             => '06.789.012.3-067.000',
                    'nama_perusahaan'  => 'PT Infrastruktur Mandiri Perkasa',
                    'nama_direktur'    => 'Rudi Hermawan',
                    'alamat'           => 'Jl. MT Haryono Kav. 33, Kelurahan Cawang, Kecamatan Kramat Jati, Jakarta Timur 13630',
                    'no_telepon'       => '021-8091234',
                ],
                'rekening' => [
                    ['nama_bank' => 'Mandiri', 'nomor_rekening' => '1560078901234', 'nama_rekening' => 'PT Infrastruktur Mandiri Perkasa'],
                ],
            ],

            // ============================================================
            // MITRA PENERIMAAN (Pihak yang membayar ke Satker)
            // ============================================================
            [
                'vendor' => [
                    'kategori'         => 'MITRA_PENERIMAAN',
                    'tipe_supplier'    => '05', // 05 = Mitra
                    'npwp'             => '10.123.456.7-101.000',
                    'nama_perusahaan'  => 'PT Astra International Tbk',
                    'nama_direktur'    => 'Djony Bunarto Tjondro',
                    'alamat'           => 'Jl. Gaya Motor Raya No. 8, Sunter II, Jakarta Utara 14330',
                    'no_telepon'       => '021-6520000',
                ],
                'rekening' => [
                    ['nama_bank' => 'BCA',     'nomor_rekening' => '0881234567', 'nama_rekening' => 'PT Astra International Tbk'],
                ],
            ],
            [
                'vendor' => [
                    'kategori'         => 'MITRA_PENERIMAAN',
                    'tipe_supplier'    => '05',
                    'npwp'             => '11.234.567.8-112.000',
                    'nama_perusahaan'  => 'Yayasan Pendidikan Bina Nusantara',
                    'nama_direktur'    => 'Dr. Harjanto Prabowo',
                    'alamat'           => 'Jl. K.H. Syahdan No. 9, Kemanggisan, Palmerah, Jakarta Barat 11480',
                    'no_telepon'       => '021-5345830',
                ],
                'rekening' => [
                    ['nama_bank' => 'BNI',     'nomor_rekening' => '0112789012', 'nama_rekening' => 'Yayasan Pendidikan Bina Nusantara'],
                ],
            ],

            // ============================================================
            // KEDUANYA (Vendor sekaligus Mitra)
            // ============================================================
            [
                'vendor' => [
                    'kategori'         => 'KEDUANYA',
                    'tipe_supplier'    => '02',
                    'npwp'             => '20.345.678.9-203.000',
                    'nama_perusahaan'  => 'PT Telekomunikasi Indonesia Tbk',
                    'nama_direktur'    => null,
                    'alamat'           => 'Jl. Japati No. 1, Bandung, Jawa Barat 40133',
                    'no_telepon'       => '022-4521234',
                ],
                'rekening' => [
                    ['nama_bank' => 'Mandiri', 'nomor_rekening' => '1300012345678', 'nama_rekening' => 'PT Telekomunikasi Indonesia Tbk'],
                    ['nama_bank' => 'BRI',     'nomor_rekening' => '0033-01-012345-50-2', 'nama_rekening' => 'PT Telkom Indonesia'],
                ],
            ],
            [
                'vendor' => [
                    'kategori'         => 'KEDUANYA',
                    'tipe_supplier'    => '01', // 01 = Satker
                    'npwp'             => '00.111.222.3-001.000',
                    'nama_perusahaan'  => 'Satker BPS Kabupaten Bandung',
                    'nama_direktur'    => null,
                    'alamat'           => 'Jl. Raya Soreang No. 58, Kabupaten Bandung, Jawa Barat 40911',
                    'no_telepon'       => '022-5891234',
                ],
                'rekening' => [
                    ['nama_bank' => 'BRI',     'nomor_rekening' => '0099-01-098765-50-1', 'nama_rekening' => 'Bendahara BPS Kab. Bandung'],
                ],
            ],

            // ============================================================
            // VENDOR TAMBAHAN
            // ============================================================
            [
                'vendor' => [
                    'kategori'         => 'VENDOR_PENGELUARAN',
                    'tipe_supplier'    => '02',
                    'npwp'             => '07.890.123.4-078.000',
                    'nama_perusahaan'  => 'PT Indah Printing & Packaging',
                    'nama_direktur'    => 'Rina Marlina',
                    'alamat'           => 'Jl. Industri Raya No. 5, Kawasan Industri Jababeka, Cikarang, Bekasi 17530',
                    'no_telepon'       => '021-8934567',
                ],
                'rekening' => [
                    ['nama_bank' => 'BCA',     'nomor_rekening' => '1234567890', 'nama_rekening' => 'PT Indah Printing & Packaging'],
                ],
            ],
            [
                'vendor' => [
                    'kategori'         => 'VENDOR_PENGELUARAN',
                    'tipe_supplier'    => '02',
                    'npwp'             => '08.901.234.5-089.000',
                    'nama_perusahaan'  => 'CV Sinar Jaya Catering',
                    'nama_direktur'    => 'Agus Prasetyo',
                    'alamat'           => 'Jl. Raya Bogor KM 30, Kelurahan Pekayon, Kecamatan Pasar Rebo, Jakarta Timur 13710',
                    'no_telepon'       => '021-8712345',
                ],
                'rekening' => [
                    ['nama_bank' => 'BNI',     'nomor_rekening' => '0345678901', 'nama_rekening' => 'CV Sinar Jaya Catering'],
                ],
            ],
            [
                'vendor' => [
                    'kategori'         => 'VENDOR_PENGELUARAN',
                    'tipe_supplier'    => '02',
                    'npwp'             => '09.012.345.6-090.000',
                    'nama_perusahaan'  => 'PT Global Sistem Integrasi',
                    'nama_direktur'    => 'Irfan Maulana',
                    'alamat'           => 'Gedung Cyber 2 Lt. 7, Jl. HR Rasuna Said Blok X-5, Kuningan, Jakarta Selatan 12950',
                    'no_telepon'       => '021-5220123',
                ],
                'rekening' => [
                    ['nama_bank' => 'CIMB Niaga', 'nomor_rekening' => '800123456789', 'nama_rekening' => 'PT Global Sistem Integrasi'],
                ],
            ],
            [
                'vendor' => [
                    'kategori'         => 'VENDOR_PENGELUARAN',
                    'tipe_supplier'    => '02',
                    'npwp'             => '12.345.678.9-123.000',
                    'nama_perusahaan'  => 'PT Sarana Multigriya Finansial',
                    'nama_direktur'    => 'Anggoro Eko Cahyo',
                    'alamat'           => 'Sona Topas Tower Lt. 10, Jl. Jenderal Sudirman Kav. 26, Jakarta Pusat 10250',
                    'no_telepon'       => '021-2500123',
                ],
                'rekening' => [
                    ['nama_bank' => 'BTN',     'nomor_rekening' => '00123-01-50-000012-8', 'nama_rekening' => 'PT Sarana Multigriya Finansial'],
                ],
            ],
            [
                'vendor' => [
                    'kategori'         => 'VENDOR_PENGELUARAN',
                    'tipe_supplier'    => '02',
                    'npwp'             => '13.456.789.0-134.000',
                    'nama_perusahaan'  => 'CV Prima Furniture',
                    'nama_direktur'    => 'Lina Susanti',
                    'alamat'           => 'Jl. Boulevard Raya Blok QJ-1 No. 28, Kelapa Gading, Jakarta Utara 14240',
                    'no_telepon'       => '021-4523456',
                ],
                'rekening' => [
                    ['nama_bank' => 'BCA',     'nomor_rekening' => '6789012345', 'nama_rekening' => 'CV Prima Furniture'],
                ],
            ],
        ];

        $vendorCount  = 0;
        $rekeningCount = 0;

        foreach ($vendors as $data) {
            $vendor = MasterMitraVendor::updateOrCreate(
                ['npwp' => $data['vendor']['npwp']],
                $data['vendor']
            );
            $vendorCount++;

            foreach ($data['rekening'] as $rek) {
                RekeningBank::updateOrCreate(
                    [
                        'pemilik_type'   => MasterMitraVendor::class,
                        'pemilik_id'     => $vendor->id,
                        'nomor_rekening' => $rek['nomor_rekening'],
                    ],
                    [
                        'nama_bank'      => $rek['nama_bank'],
                        'nama_rekening'  => $rek['nama_rekening'],
                    ]
                );
                $rekeningCount++;
            }
        }

        $this->command->info("✅ Mitra/Vendor seeded: {$vendorCount} vendors, {$rekeningCount} rekening bank");
    }
}
