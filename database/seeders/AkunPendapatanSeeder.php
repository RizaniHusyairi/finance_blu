<?php

namespace Database\Seeders;

use App\Models\AkunPendapatan;
use Illuminate\Database\Seeder;

/**
 * Master akun pendapatan BLU APT Pranoto — diambil dari sheet `Akun Pendapatan`
 * File 2 (`2. BKU_PENERIMAAN_18 FEBRUARI_2026.xls`).
 *
 * Idempoten: updateOrCreate by (kode_akun, kode_jenis).
 */
class AkunPendapatanSeeder extends Seeder
{
    public function run(): void
    {
        $uraianAkun = [
            '424115' => 'Pendapatan Jasa Bandar Udara, Kepelabuhan dan Kenavigasian',
            '424921' => 'Pendapatan BLU Lainnya dari Sewa Tanah',
            '424923' => 'Pendapatan BLU Lainnya dari Sewa Ruangan',
            '424924' => 'Pendapatan BLU Lainnya dari Sewa Peralatan dan Mesin',
            '424919' => 'Pendapatan Lain-lain BLU',
            '424312' => 'Pendapatan Hasil Kerja Sama Lembaga/Badan Usaha',
            '424922' => 'Pendapatan BLU Lainnya dari Sewa Gedung',
        ];

        // [kode_akun, kode_jenis, kode_gabungan, uraian_jenis]
        $rows = [
            ['424115', '901', '424115.9',  'Pendaratan Pesawat'],
            ['424115', '902', '424115.9',  'Penempatan Pesawat'],
            ['424115', '903', '424115.9',  'Penyimpanan Pesawat'],
            ['424115', '904', '424115.9',  'Konter Pelaporan (PJPC)'],
            ['424115', '905', '424115.91', 'PJP2U'],
            ['424115', '906', '424115.91', 'JKP2U (Jasa Layanan Kargo dan Pos Pesawat Udara)'],
            ['424115', '907', '424115.91', 'Garbarata'],
            ['424115', '908', '424115.91', 'Penggunaan Bandar Udara Diluar Jam Operasional (Extend)'],
            ['424115', '909', '424115.91', 'Penggunaan Bandara sebagai Bandara Alternatif (Standby Alternate Aerodrome)'],
            ['424115', '910', '424115.91', 'Penanganan Jenazah'],
            ['424921', '911', '424921.91', 'Penggunaan Lahan'],
            ['424923', '912', '424923.91', 'Penggunaan Ruangan'],
            ['424923', '913', '424923.91', 'Penempatan Mesin ATM'],
            ['424923', '914', '424923.91', 'Pemotretan Shooting'],
            ['424924', '915', '424924.92', 'Tiang Pancang Reklame'],
            ['424924', '916', '424924.92', 'Pemasangan Reklame'],
            ['424924', '917', '424924.92', 'Bis Apron'],
            ['424924', '918', '424924.92', 'Kendaraan/Peralatan dan Mesin'],
            ['424924', '919', '424924.92', 'Sewa Kendaraan Bermotor Roda 4 atau lebih'],
            ['424924', '920', '424924.92', 'Penggunaan Xray Cabin'],
            ['424919', '921', '424919.92', 'Tagihan Listrik'],
            ['424919', '922', '424919.92', 'PAS ORANG'],
            ['424919', '923', '424919.92', 'PAS TIM'],
            ['424919', '924', '424919.92', 'Telekomunikasi'],
            ['424919', '925', '424919.93', 'Tagihan Air'],
            ['424919', '926', '424919.93', 'Pemeriksaan Kargo dan Pos'],
            ['424919', '927', '424919.93', 'Field Trip'],
            ['424919', '928', '424919.93', 'Bengkel Kendaraan Bermotor'],
            ['424919', '929', '424919.93', 'Parkir Inap'],
            ['424923', '930', '424923.93', 'Penggunaan Hanggar'],
            ['424312', '931', '424312.93', 'Konsesi'],
            ['424922', '932', '424922.93', 'Gedung/Bangunan'],
            ['424919', '933', '424919.93', 'BUNGA Rek. OPS PENERIMAAN'],
            ['424919', '934', '424919.93', 'Pengembalian Belanja'],
            ['424924', '935', '424924.94', 'Pemasangan REKLAME'],
            ['424919', '936', '424919.94', 'Penggunaan Fasilitas dan Personil di Luar Jam Operasional Bandar Udara'],
            ['424919', '937', '424919.94', 'PAS KENDARAAN'],
            ['424919', '938', '424919.94', 'Bunga Rekening BEND PENGELUARAN'],
            ['424919', '939', '424919.94', 'DENDA Pekerjaan Tahun lalu'],
        ];

        foreach ($rows as [$akun, $jenis, $gabungan, $uraianJenis]) {
            AkunPendapatan::updateOrCreate(
                ['kode_akun' => $akun, 'kode_jenis' => $jenis],
                [
                    'kode_gabungan' => $gabungan,
                    'uraian_akun' => $uraianAkun[$akun] ?? $akun,
                    'uraian_jenis' => $uraianJenis,
                    'status_aktif' => true,
                ],
            );
        }
    }
}
