<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $root = $this->node('KSP-ROOT', null, 1, 'K. PENGGUNAAN SARANA DAN PRASARANA DI BANDAR UDARA BERDASARKAN TUGAS DAN FUNGSI');

        $this->node('KSP-TIANG-REKLAME', $root, 2, '1. Pemasangan Tiang Pancang Reklame', true, 25500, 'per m2 reklame per tahun');

        $konsesi = $this->node('KSP-KONSESI', $root, 2, '2. Konsesi');
        $fuel = $this->node('KSP-KONSESI-FUEL', $konsesi, 3, 'a. Konsesi Pengisian Bahan Bakar Pesawat Udara (Fuel Throughput)');
        $this->node('KSP-KONSESI-FUEL-KU', $fuel, 4, '1) Bandar udara kelas I utama', true, 20, 'per liter', true);
        $this->node('KSP-KONSESI-FUEL-K1', $fuel, 4, '2) Bandar udara kelas I', true, 17, 'per liter', true);
        $this->node('KSP-KONSESI-FUEL-K2', $fuel, 4, '3) Bandar udara kelas II', true, 15, 'per liter', true);
        $this->node('KSP-KONSESI-FUEL-K3-SP', $fuel, 4, '4) Bandar udara kelas III dan Satuan Pelayanan', true, 10, 'per liter', true);
        $this->node('KSP-KONSESI-TANAH-RUANGAN', $konsesi, 3, 'b. Konsesi atas Pengusahaan Tanah dan Ruangan', true, 5, '% per konsesioner', true);
        $this->node('KSP-KONSESI-KENDARAAN', $konsesi, 3, 'c. Konsesi Penyimpanan Kendaraan Bermotor', true, 15, '% per konsesioner', true);
        $this->node('KSP-KONSESI-IKLAN', $konsesi, 3, 'd. Konsesi Penyewaan Space Iklan yang Disewakan Kembali', true, 5, '% per konsesioner', true);
        $this->node('KSP-KONSESI-WRAPPING', $konsesi, 3, 'e. Konsesi atas jasa pengemasan barang bawaan atau (wrapping)', true, 15, '% per konsesioner', true);
        $this->node('KSP-KONSESI-GROUND-HANDLING', $konsesi, 3, 'f. Konsesi atas kegiatan Ground Handling', true, 15, '% per konsesioner', true);

        $tanah = $this->node('KSP-TANAH-UPBU', $root, 2, '3. Penggunaan Tanah pada Bandar Udara Unit Penyelenggara Bandar Udara (UPBU)');
        $this->leafMany($tanah, 3, [
            ['KSP-TANAH-KU', 'a. Bandar Udara Kelas I Utama', 17500, 'per m2 per bulan'],
            ['KSP-TANAH-K1', 'b. Bandar Udara Kelas I', 15000, 'per m2 per bulan'],
            ['KSP-TANAH-K2', 'c. Bandar Udara Kelas II', 12500, 'per m2 per bulan'],
            ['KSP-TANAH-K3-SP', 'd. Bandar Udara Kelas III dan Satuan Pelayanan', 10000, 'per m2 per bulan'],
        ]);

        $display = $this->node('KSP-DISPLAY', $root, 2, '4. Penggunaan Ruangan untuk Promosi berupa Peragaan (Display) Produk');
        $this->leafMany($display, 3, [
            ['KSP-DISPLAY-KU', 'a. Bandar udara kelas I utama', 10200, 'per m2 per hari'],
            ['KSP-DISPLAY-K1-K2', 'b. Bandar Udara Kelas I dan II', 5100, 'per m2 per hari'],
            ['KSP-DISPLAY-K3-SP', 'c. Bandar Udara Kelas III dan Satuan Pelayanan', 1700, 'per m2 per hari'],
        ]);

        $ruangan = $this->node('KSP-RUANGAN', $root, 2, '5. Penggunaan Ruangan');
        $inside = $this->node('KSP-RUANGAN-IN', $ruangan, 3, 'a. Di Dalam Terminal');
        $outside = $this->node('KSP-RUANGAN-OUT', $ruangan, 3, 'b. Di Luar Terminal');
        $this->roomClass($inside, 'KSP-RUANGAN-IN-KU', 4, '1) Bandar udara kelas I utama', [51000, 68000, 85000, 102000]);
        $this->roomClass($inside, 'KSP-RUANGAN-IN-K1', 4, '2) Bandar udara kelas I', [31000, 48000, 65000, 82000]);
        $this->roomClass($inside, 'KSP-RUANGAN-IN-K2', 4, '3) Bandar udara kelas II', [21000, 38000, 55000, 72000]);
        $this->roomClass($inside, 'KSP-RUANGAN-IN-K3-SP', 4, '4) Bandar udara kelas III dan satuan Pelayanan', [10500, 19000, 27500, 36000]);
        $this->roomClass($outside, 'KSP-RUANGAN-OUT-KU', 4, '1) Bandar udara kelas I utama', [41000, 58000, 75000, 92000]);
        $this->roomClass($outside, 'KSP-RUANGAN-OUT-K1', 4, '2) Bandar udara kelas I', [21000, 38000, 55000, 72000]);
        $this->roomClass($outside, 'KSP-RUANGAN-OUT-K2', 4, '3) Bandar udara kelas II', [11000, 28000, 45000, 62000]);
        $this->roomClass($outside, 'KSP-RUANGAN-OUT-K3-SP', 4, '4) Bandar udara kelas III dan satuan Pelayanan', [5500, 14000, 22500, 31000]);

        $atm = $this->node('KSP-ATM', $root, 2, '6. Penempatan Mesin Anjungan Tunai Mandiri (ATM) di Bandar Udara');
        $this->leafMany($atm, 3, [
            ['KSP-ATM-KU', 'a. Bandar udara kelas I utama', 1700000, 'per unit per bulan'],
            ['KSP-ATM-K1', 'b. Bandar udara kelas I', 1500000, 'per unit per bulan'],
            ['KSP-ATM-K2', 'c. Bandar udara Kelas II', 1350000, 'per unit per bulan'],
            ['KSP-ATM-K3', 'd. Bandar udara kelas III', 1000000, 'per unit per bulan'],
            ['KSP-ATM-SP', 'e. Satuan pelayanan', 800000, 'per unit per bulan'],
        ]);

        $shooting = $this->node('KSP-SHOOTING', $root, 2, '7. Shooting Film, Pemotretan, dan promosi di Bandar Udara Keals I Utama, II, III, dan Satuan Pelayanan.');
        $this->leafMany($shooting, 3, [
            ['KSP-SHOOTING-FILM', '1) Shooting film', 2000000, 'per hari'],
            ['KSP-SHOOTING-PHOTO', '2) Pemotretan Bandar Udara', 250000, 'per jam'],
            ['KSP-SHOOTING-PROMO', '3) Promosi', 100000, 'per kegiatan per hari'],
        ]);

        $cip = $this->node('KSP-CIP', $root, 2, '8. Pemakaian Ruang Tunggu Khusus/Commercial Important Person Room (CIP)');
        $this->leafMany($cip, 3, [
            ['KSP-CIP-KU', 'a. Bandar Udara Kelas I Utama', 300000, 'per jam per ruangan'],
            ['KSP-CIP-K1', 'b. Bandar Udara Kelas I', 250000, 'per jam per ruangan'],
            ['KSP-CIP-K2', 'c. Bandar Udara Kelas II', 200000, 'per jam per ruangan'],
            ['KSP-CIP-K3-SP', 'd. Bandar Udara Kelas III dan Satuan Pelayanan', 150000, 'per jam per ruangan'],
        ]);

        $this->node('KSP-HANGGAR', $root, 2, '9. Penggunaan hanggar untuk perbaikan pesawat udara', true, 28684, 'per m2 per bulan');
        $this->node('KSP-PUSHBACK', $root, 2, '10. Penggunaan Traktor Pendorong Pesawat/Push Back Tractor', true, 150000, 'per pesawat per sekali penggunaan');
        $this->node('KSP-BIS-APRON', $root, 2, '11. Penggunaan Bis Appron', true, 1000, 'per penumpang');

        $reklame = $this->node('KSP-REKLAME', $root, 2, '12. Pemasangan Reklame');
        $this->reklameClass($reklame, 'KSP-REKLAME-KU', 3, 'a. Bandar Udara Kelas I Utama', [102000, 42500, 20000, 20000, 50000, 25000, 30000, 34000, 180000, 6500, 8500], 0);
        $this->reklameClass($reklame, 'KSP-REKLAME-K1', 3, 'b. Bandar Udara Kelas I', [80000, 35000, 17000, 17000, 40000, 15000, 25000, 30000, 150000, 5000, 7000], 0);
        $this->reklameClass($reklame, 'KSP-REKLAME-K2', 3, 'c. Bandar Udara Kelas II', [70000, 25000, 15000, 15000, 35000, 10000, 20000, 25000, 120000, 4500, 6000], 0);
        $this->reklameClass($reklame, 'KSP-REKLAME-K3-SP', 3, 'd. Bandar Udara Kelas III dan Satuan Pelayanan', [50000, 20000, 10000, 12000, 25000, 5000, 15000, 20000, 100000, 3750, 5000], 1);

        $phone = $this->node('KSP-TELEPON', $root, 2, '13. Penyediaan Fasilitas Telepon');
        $this->node('KSP-TELEPON-INTERCOM', $phone, 3, 'a. Penggantian Pemakaian Intercom', true, 17000, 'per sambungan cabang per bulan');
        $this->node('KSP-TELEPON-KOTA', $phone, 3, 'b. Penggantian Pemakaian Telepon dalam Kota Perbulan Melalui Sentral Bandara', true, 110, '% per pulsa');
        $this->node('KSP-TELEPON-INTERLOKAL', $phone, 3, 'c. Penggantian Pemakaian Telepon Interlokal Perbulan melalui Sentral Bandara', true, 110, '% per pulsa');
        $this->node('KSP-AIR', $root, 2, '14. Penggunaan Air Bandar Udara', true, 110, '% per pulsa');
        $this->node('KSP-LISTRIK', $root, 2, '15. Penggunaan Listrik Bandar Udara', true, 110, '% per pulsa');
    }

    public function down(): void
    {
        DB::table('layanan_jasas')->where('kode_layanan', 'like', 'KSP-%')->delete();
    }

    private function roomClass(int $parent, string $kode, int $level, string $name, array $rates): void
    {
        $id = $this->node($kode, $parent, $level, $name);
        $labels = ['a) terbuka tanpa AC', 'b) tertutup tanpa AC', 'c) terbuka dengan AC', 'd) tertutup dengan AC'];
        foreach ($labels as $index => $label) {
            $this->node($kode . '-' . ($index + 1), $id, $level + 1, $label, true, $rates[$index], 'per m2 per bulan');
        }
    }

    private function reklameClass(int $parent, string $kode, int $level, string $name, array $rates, int $kotakNeonMode): void
    {
        $id = $this->node($kode, $parent, $level, $name);
        if ($kotakNeonMode === 1) {
            $neon = $this->node($kode . '-NEON', $id, $level + 1, '1) Kotak neon (neon box) satu sisi pandang');
            $this->node($kode . '-NEON-IN', $neon, $level + 2, 'a) dalam terminal', true, 60000, 'per m2 per bulan');
            $this->node($kode . '-NEON-OUT', $neon, $level + 2, 'b) luar terminal', true, 40000, 'per m2 per bulan');
        } else {
            $this->node($kode . '-NEON', $id, $level + 1, '1) Kotak neon (neon box) satu sisi pandang', true, 0, null);
        }

        $items = [
            ['BILLBOARD', '2) Papan reklame (billboard)', $rates[0], 'per m2 per bulan'],
            ['TROLLY', '3) Kereta dorong (trolly)', $rates[1], 'per unit per bulan per sisi pandang'],
            ['KURSI', '4) Kursi', $rates[2], 'per unit per bulan per sisi pandang'],
            ['ASBAK', '5) Asbak/tempat sampah', $rates[3], 'per unit per bulan per sisi pandang'],
            ['BANNER', '6) Spanduk/banner', $rates[4], 'per unit per hari'],
            ['UMBUL', '7) Umbul-umbul', $rates[5], 'per unit per hari per sisi pandang'],
            ['BALEHO', '8) Baliho', $rates[6], 'per m2 per hari'],
            ['STICKER', '9) Sticker', $rates[7], 'per m2 per bulan'],
            ['GARBARATA', '10) Garbarata/Bis Layanan Penumpang di Apron', $rates[8], 'per m2 per bulan'],
            ['BOOKLET', '11) Penempatan booklet', $rates[9], 'per 25 buku'],
            ['LEAFLET', '12) Penempatan brosur (leaflet)', $rates[10], 'per 100 eksemplar'],
        ];

        foreach ($items as [$suffix, $label, $tarif, $satuan]) {
            $this->node($kode . '-' . $suffix, $id, $level + 1, $label, true, $tarif, $satuan);
        }
    }

    private function leafMany(int $parent, int $level, array $items): void
    {
        foreach ($items as [$kode, $name, $tarif, $satuan]) {
            $this->node($kode, $parent, $level, $name, true, $tarif, $satuan);
        }
    }

    private function node(
        string $kode,
        ?int $parentId,
        int $level,
        string $name,
        bool $isLeaf = false,
        int $tarif = 0,
        ?string $satuan = null,
        bool $konsesi = false
    ): int {
        $now = now();
        $payload = [
            'parent_id' => $parentId,
            'level' => $level,
            'nama_layanan' => $name,
            'pic_name' => null,
            'tarif_dasar' => $tarif,
            'satuan' => $satuan,
            'is_active' => true,
            'is_leaf' => $isLeaf,
            'tipe_layanan' => 'PNBP',
            'mendukung_konsesi' => $konsesi,
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
};
