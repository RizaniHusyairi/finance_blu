<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KspAdditionalLayananSeeder extends Seeder
{
    public function run(): void
    {
        $root = DB::table('layanan_jasas')
            ->where('kode_layanan', 'KSP-ROOT')
            ->orWhere('nama_layanan', 'K. PENGGUNAAN SARANA DAN PRASARANA DI BANDAR UDARA BERDASARKAN TUGAS DAN FUNGSI')
            ->first();

        if (! $root) {
            $this->command?->warn('Root KSP tidak ditemukan, seeder dilewati.');
            return;
        }

        $studi = $this->node(
            'KSP-STUDI-LAPANGAN',
            (int) $root->id,
            2,
            '17. Studi Lapangan'
        );

        $this->node('KSP-STUDI-REGULER-NASI-KOTAK', $studi, 3, 'a. Studi Lapangan Reguler dengan Nasi Kotak', true, 70000, 'OH', '424919');
        $this->node('KSP-STUDI-REGULER-SNACK', $studi, 3, 'b. Studi Lapangan Reguler dengan Snack', true, 55000, 'OH', '424919');
        $this->node('KSP-STUDI-PENELITIAN-NASI-KOTAK', $studi, 3, 'c. Studi Lapangan Dalam Rangka Penelitian dan Observasi dengan Nasi Kotak', true, 73000, 'OH', '424919');
        $this->node('KSP-STUDI-PENELITIAN-SNACK', $studi, 3, 'd. Studi Lapangan Dalam Rangka Penelitian dan Observasi dengan Snack', true, 57000, 'OH', '424919');

        $pemeriksaan = $this->node(
            'KSP-PEMERIKSAAN-ORANG-BARANG-KARGO-POS',
            (int) $root->id,
            2,
            '18. Pemeriksaan Orang dan Barang serta Pemeriksaan Kargo dan Pos'
        );

        $this->node('KSP-PEMERIKSAAN-ORANG-BARANG', $pemeriksaan, 3, 'a. Pemeriksaan Orang dan Barang', true, 705000, 'per hari', '424924');
        $this->node('KSP-PEMERIKSAAN-KARGO-POS', $pemeriksaan, 3, 'b. Pemeriksaan Kargo dan Pos', true, 550, 'per kg', '424919');

        $this->node(
            'KSP-FASILITAS-PERSONIL-LUAR-JAM',
            (int) $root->id,
            2,
            '19. Penggunaan Fasilitas dan Personil di Luar Jam Operasional Bandar Udara',
            true,
            1750000,
            'per jam',
            '424919'
        );

        $this->node(
            'KSP-FIDS',
            (int) $root->id,
            2,
            '20. Flight Information Display System (FIDS)',
            true,
            1735000,
            'per bulan',
            '424924'
        );

        $kendaraan = $this->node(
            'KSP-PENGGUNAAN-KENDARAAN',
            (int) $root->id,
            2,
            '21. Penggunaan Kendaraan'
        );

        $this->node('KSP-KENDARAAN-BUS', $kendaraan, 3, 'a. Kendaraan Bermotor Roda Empat/Lebih (bus)', true, 1800000, 'per hari', '424924');
        $this->node('KSP-KENDARAAN-MOBIL-PEMADAM', $kendaraan, 3, 'b. Kendaraan Mobil Pemadam', true, 5581000, 'per bulan', '424924');
        $this->node('KSP-KENDARAAN-DUMP-TRUCK', $kendaraan, 3, 'c. Dump Truck', true, 750000, 'per hari', '424924');
        $this->node('KSP-KENDARAAN-CRAWLER-EXCAVATOR', $kendaraan, 3, 'd. Crawler Excavator + Attachment', true, 1581000, 'per hari (8 jam)', '424924');

        $ruangRapat = $this->node(
            'KSP-PENGGUNAAN-RUANG-RAPAT',
            (int) $root->id,
            2,
            '22. Penggunaan Ruang Rapat'
        );

        $this->node('KSP-RUANG-RAPAT-DIRGANTARA', $ruangRapat, 3, 'a. Ruang Rapat Dirgantara', true, 108000, 'per jam', '424923');
        $this->node('KSP-RUANG-RAPAT-BOEING', $ruangRapat, 3, 'b. Ruang Rapat Boeing', true, 65000, 'per jam', '424923');
        $this->node('KSP-RUANG-RAPAT-N25', $ruangRapat, 3, 'c. Ruang Rapat N- 25', true, 66000, 'per jam', '424923');
        $this->node('KSP-RUANG-RAPAT-CASSA', $ruangRapat, 3, 'd. Ruang Rapat Cassa', true, 63000, 'per jam', '424923');

        $this->seedKonsesiPenyimpananKendaraan();
    }

    private function seedKonsesiPenyimpananKendaraan(): void
    {
        $parent = DB::table('layanan_jasas')
            ->where('kode_layanan', 'KSP-KONSESI-KENDARAAN')
            ->first();

        if (! $parent) {
            return;
        }

        DB::table('layanan_jasas')
            ->where('id', $parent->id)
            ->update([
                'is_leaf' => false,
                'tarif_dasar' => 0,
                'satuan' => null,
                'kode_akun' => null,
                'mendukung_konsesi' => false,
                'persentase_konsesi' => null,
                'updated_at' => now(),
            ]);

        $this->node('KSP-KONSESI-KENDARAAN-PARKIR-UTAMA', (int) $parent->id, 4, '1) Parkir Utama Kendaraan', true, 25, '% x total pendapatan operasional', '424312', true, 25);
        $this->node('KSP-KONSESI-KENDARAAN-PARKIR-VIP', (int) $parent->id, 4, '2) Parkir VIP', true, 15, '% x total pendapatan operasional', '424312', true, 15);
        $this->node('KSP-KONSESI-KENDARAAN-PARKIR-INAP', (int) $parent->id, 4, '3) Parkir Inap', true, 15, '% x total pendapatan operasional', '424312', true, 15);
    }

    private function node(
        string $kode,
        ?int $parentId,
        int $level,
        string $name,
        bool $isLeaf = false,
        int $tarif = 0,
        ?string $satuan = null,
        ?string $kodeAkun = null,
        bool $mendukungKonsesi = false,
        ?float $persentaseKonsesi = null
    ): int {
        $now = now();
        $payload = [
            'parent_id' => $parentId,
            'level' => $level,
            'nama_layanan' => $name,
            'kode_akun' => $kodeAkun,
            'pic_name' => null,
            'tarif_dasar' => $tarif,
            'satuan' => $satuan,
            'is_active' => true,
            'is_leaf' => $isLeaf,
            'tipe_layanan' => 'PNBP',
            'mendukung_konsesi' => $mendukungKonsesi,
            'persentase_konsesi' => $persentaseKonsesi,
            'jumlah_hari_jatuh_tempo' => 30,
            'masa_toleransi_hari' => 0,
            'wajib_tagihan_terpisah' => false,
            'catatan_jatuh_tempo' => null,
            'updated_at' => $now,
        ];

        $existing = DB::table('layanan_jasas')->where('kode_layanan', $kode)->first();
        if ($existing) {
            DB::table('layanan_jasas')->where('id', $existing->id)->update($payload);
            return (int) $existing->id;
        }

        return (int) DB::table('layanan_jasas')->insertGetId(array_merge($payload, [
            'kode_layanan' => $kode,
            'created_at' => $now,
        ]));
    }
}
