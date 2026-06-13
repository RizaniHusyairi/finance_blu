<?php

namespace Database\Seeders;

use App\Models\MasterTarifPajak;
use Illuminate\Database\Seeder;

/**
 * Tarif pajak instansi pemerintah mengikuti Leaflet Pajak Instansi Pemerintah
 * 2022 (PMK 59/PMK.03/2022 jo. PMK 231/PMK.03/2019):
 *
 * - PPN 11% (KAP 411211), PPh 22 belanja barang 1,5% / tanpa NPWP 3% (411122)
 *   — KJS pemungut Bendahara APBN = 910.
 * - PPh 23 jasa 2% / tanpa NPWP 4% (411124-104), tanpa nilai minimum,
 *   termasuk katering.
 * - PPh 4(2) Final konstruksi (PP 9/2022): pelaksana kecil 1,75%,
 *   menengah/besar 2,65%, tanpa kualifikasi 4%; perencanaan & pengawasan
 *   berkualifikasi 3,65%, tanpa kualifikasi 6% (411128-409).
 * - PPh 4(2) Final sewa tanah/bangunan 10% (411128-403).
 * - PPh 21 honor: PNS final per golongan — Gol I/II 0%, Gol III 5%,
 *   Gol IV 15% (411121-402); Non-PNS peserta kegiatan 5%, tanpa NPWP 6%
 *   (411121-100).
 *
 * Cara hitung mengikuti kalkulator pajak Subbag Keuangan: DPP = bruto ×
 * 100/(100+PPN) (PPN diekstrak dari nilai tagihan), nominal dibulatkan ke
 * atas ke ratusan terdekat (ROUNDUP -2).
 */
class MasterTarifPajakSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // ===== PPN — pemungutan oleh Instansi Pemerintah (APBN) =====
            [
                'kode_pajak'         => 'PPN11',
                'jenis_pajak'        => 'PPN Belanja Barang & Jasa',
                'persentase'         => 11,
                'kode_akun_pajak'    => '411211',
                'kode_jenis_setoran' => '910',
                'rumus'              => 'DPP (bruto x 100/111) x 11% — hanya bila pembayaran > Rp 2 juta',
                'berlaku_mulai'      => '2022-04-01',
                'berlaku_sampai'     => null,
                'status_aktif'       => true,
            ],

            // ===== PPh Pasal 22 — belanja barang =====
            [
                'kode_pajak'         => 'PPH22-BEND',
                'jenis_pajak'        => 'PPh 22 Belanja Barang',
                'persentase'         => 1.5,
                'kode_akun_pajak'    => '411122',
                'kode_jenis_setoran' => '910',
                'rumus'              => 'DPP (bruto x 100/111) x 1,5% — hanya bila pembayaran > Rp 2 juta; disetor a.n. NPWP rekanan',
                'berlaku_mulai'      => '2022-01-01',
                'berlaku_sampai'     => null,
                'status_aktif'       => true,
            ],
            [
                'kode_pajak'         => 'PPH22-TNPWP',
                'jenis_pajak'        => 'PPh 22 Belanja Barang (Rekanan Tanpa NPWP)',
                'persentase'         => 3,
                'kode_akun_pajak'    => '411122',
                'kode_jenis_setoran' => '910',
                'rumus'              => 'DPP (bruto x 100/111) x 3% — tarif 100% lebih tinggi bagi rekanan tanpa NPWP',
                'berlaku_mulai'      => '2022-01-01',
                'berlaku_sampai'     => null,
                'status_aktif'       => true,
            ],
            [
                'kode_pajak'         => 'PPH22-JUAL',
                'jenis_pajak'        => 'PPh 22 Transaksi Penjualan Barang',
                'persentase'         => 0.5,
                'kode_akun_pajak'    => '411122',
                'kode_jenis_setoran' => '910',
                'rumus'              => 'DPP (bruto x 100/111) x 0,5%',
                'berlaku_mulai'      => '2022-01-01',
                'berlaku_sampai'     => null,
                'status_aktif'       => true,
            ],

            // ===== PPh Pasal 23 — pengeluaran jasa (tanpa nilai minimum) =====
            [
                'kode_pajak'         => 'PPH23',
                'jenis_pajak'        => 'PPh 23 Pengeluaran Jasa',
                'persentase'         => 2,
                'kode_akun_pajak'    => '411124',
                'kode_jenis_setoran' => '104',
                'rumus'              => 'DPP (bruto x 100/111) x 2% — tanpa nilai minimum, termasuk katering',
                'berlaku_mulai'      => '2022-01-01',
                'berlaku_sampai'     => null,
                'status_aktif'       => true,
            ],
            [
                'kode_pajak'         => 'PPH23-TNPWP',
                'jenis_pajak'        => 'PPh 23 Pengeluaran Jasa (Rekanan Tanpa NPWP)',
                'persentase'         => 4,
                'kode_akun_pajak'    => '411124',
                'kode_jenis_setoran' => '104',
                'rumus'              => 'DPP (bruto x 100/111) x 4% — tarif 100% lebih tinggi bagi rekanan tanpa NPWP',
                'berlaku_mulai'      => '2022-01-01',
                'berlaku_sampai'     => null,
                'status_aktif'       => true,
            ],

            // ===== PPh Pasal 4(2) Final — jasa konstruksi (PP 9/2022) =====
            [
                'kode_pajak'         => 'PPH4A2-175',
                'jenis_pajak'        => 'PPh 4(2) Pelaksana Konstruksi — Kualifikasi Kecil',
                'persentase'         => 1.75,
                'kode_akun_pajak'    => '411128',
                'kode_jenis_setoran' => '409',
                'rumus'              => 'DPP (bruto x 100/111) x 1,75%',
                'berlaku_mulai'      => '2022-01-01',
                'berlaku_sampai'     => null,
                'status_aktif'       => true,
            ],
            [
                'kode_pajak'         => 'PPH4A2-265',
                'jenis_pajak'        => 'PPh 4(2) Pelaksana Konstruksi — Menengah & Besar',
                'persentase'         => 2.65,
                'kode_akun_pajak'    => '411128',
                'kode_jenis_setoran' => '409',
                'rumus'              => 'DPP (bruto x 100/111) x 2,65%',
                'berlaku_mulai'      => '2022-01-01',
                'berlaku_sampai'     => null,
                'status_aktif'       => true,
            ],
            [
                'kode_pajak'         => 'PPH4A2-4',
                'jenis_pajak'        => 'PPh 4(2) Pelaksana Konstruksi — Tanpa Kualifikasi',
                'persentase'         => 4,
                'kode_akun_pajak'    => '411128',
                'kode_jenis_setoran' => '409',
                'rumus'              => 'DPP (bruto x 100/111) x 4%',
                'berlaku_mulai'      => '2022-01-01',
                'berlaku_sampai'     => null,
                'status_aktif'       => true,
            ],
            [
                'kode_pajak'         => 'PPH4A2-365',
                'jenis_pajak'        => 'PPh 4(2) Perencanaan/Pengawasan Konstruksi — Berkualifikasi',
                'persentase'         => 3.65,
                'kode_akun_pajak'    => '411128',
                'kode_jenis_setoran' => '409',
                'rumus'              => 'DPP (bruto x 100/111) x 3,65%',
                'berlaku_mulai'      => '2022-01-01',
                'berlaku_sampai'     => null,
                'status_aktif'       => true,
            ],
            [
                'kode_pajak'         => 'PPH4A2-6',
                'jenis_pajak'        => 'PPh 4(2) Perencanaan/Pengawasan Konstruksi — Tanpa Kualifikasi',
                'persentase'         => 6,
                'kode_akun_pajak'    => '411128',
                'kode_jenis_setoran' => '409',
                'rumus'              => 'DPP (bruto x 100/111) x 6%',
                'berlaku_mulai'      => '2022-01-01',
                'berlaku_sampai'     => null,
                'status_aktif'       => true,
            ],

            // ===== PPh Pasal 4(2) Final — sewa tanah/bangunan =====
            [
                'kode_pajak'         => 'PPH4A2-SEWA-TB',
                'jenis_pajak'        => 'PPh 4(2) Sewa Tanah/Bangunan',
                'persentase'         => 10,
                'kode_akun_pajak'    => '411128',
                'kode_jenis_setoran' => '403',
                'rumus'              => 'DPP (bruto x 100/111) x 10%',
                'berlaku_mulai'      => '2022-01-01',
                'berlaku_sampai'     => null,
                'status_aktif'       => true,
            ],

            // ===== PPh Pasal 15 (kalkulator internal, di luar leaflet) =====
            [
                'kode_pajak'         => 'PPH15',
                'jenis_pajak'        => 'PPh Pasal 15',
                'persentase'         => 1.8,
                'kode_akun_pajak'    => null,
                'kode_jenis_setoran' => null,
                'rumus'              => 'DPP (bruto x 100/111) x 1,8%',
                'berlaku_mulai'      => '2022-01-01',
                'berlaku_sampai'     => null,
                'status_aktif'       => true,
            ],

            // ===== PPh Pasal 21 — honor (form honorarium, filter PPH21%) =====
            // PNS: final per golongan (KJS 402, disetor a.n. NPWP instansi).
            [
                'kode_pajak'         => 'PPH21-GOL12',
                'jenis_pajak'        => 'PPh 21 Final Honor PNS Gol I & II (0%)',
                'persentase'         => 0,
                'kode_akun_pajak'    => '411121',
                'kode_jenis_setoran' => '402',
                'rumus'              => 'Honor bruto x 0% (final, PNS Gol I & II)',
                'berlaku_mulai'      => '2022-01-01',
                'berlaku_sampai'     => null,
                'status_aktif'       => true,
            ],
            [
                'kode_pajak'         => 'PPH21-GOL3',
                'jenis_pajak'        => 'PPh 21 Final Honor PNS Gol III (5%)',
                'persentase'         => 5,
                'kode_akun_pajak'    => '411121',
                'kode_jenis_setoran' => '402',
                'rumus'              => 'Honor bruto x 5% (final, PNS Gol III)',
                'berlaku_mulai'      => '2022-01-01',
                'berlaku_sampai'     => null,
                'status_aktif'       => true,
            ],
            [
                'kode_pajak'         => 'PPH21-GOL4',
                'jenis_pajak'        => 'PPh 21 Final Honor PNS Gol IV (15%)',
                'persentase'         => 15,
                'kode_akun_pajak'    => '411121',
                'kode_jenis_setoran' => '402',
                'rumus'              => 'Honor bruto x 15% (final, PNS Gol IV)',
                'berlaku_mulai'      => '2022-01-01',
                'berlaku_sampai'     => null,
                'status_aktif'       => true,
            ],
            // Non-PNS peserta kegiatan (KJS 100). Kode PPH21-TER dipertahankan
            // karena dirujuk alur penyetoran pajak honorarium.
            [
                'kode_pajak'         => 'PPH21-TER',
                'jenis_pajak'        => 'PPh 21 Non-PNS Peserta Kegiatan (5%)',
                'persentase'         => 5,
                'kode_akun_pajak'    => '411121',
                'kode_jenis_setoran' => '100',
                'rumus'              => 'Honor bruto x 5% (Non-PNS ber-NPWP)',
                'berlaku_mulai'      => '2024-01-01',
                'berlaku_sampai'     => null,
                'status_aktif'       => true,
            ],
            [
                'kode_pajak'         => 'PPH21-TNPWP',
                'jenis_pajak'        => 'PPh 21 Non-PNS Tanpa NPWP (6%)',
                'persentase'         => 6,
                'kode_akun_pajak'    => '411121',
                'kode_jenis_setoran' => '100',
                'rumus'              => 'Honor bruto x 6% (Non-PNS tanpa NPWP, 20% lebih tinggi)',
                'berlaku_mulai'      => '2022-01-01',
                'berlaku_sampai'     => null,
                'status_aktif'       => true,
            ],
        ];

        foreach ($data as $item) {
            MasterTarifPajak::updateOrCreate(
                ['kode_pajak' => $item['kode_pajak']],
                $item
            );
        }

        // Nonaktifkan tarif yang tidak lagi sesuai leaflet/PP 9/2022
        // (mis. PPH4A2-3 & PPH4A2-35 tarif konstruksi lama, PPN12, dst).
        MasterTarifPajak::whereNotIn('kode_pajak', array_column($data, 'kode_pajak'))
            ->update(['status_aktif' => false]);
    }
}
