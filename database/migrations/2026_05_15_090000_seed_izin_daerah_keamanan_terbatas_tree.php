<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $root = $this->node('SEC-ROOT', null, 1, 'L. IZIN DI DAERAH KEAMANAN TERBATAS');
        $entryPermit = $this->node('SEC-ENTRY', $root, 2, '1. Izin Masuk Daerah Keamanan Terbatas');
        $epas = $this->node('SEC-EPAS', $entryPermit, 3, 'a. E-Pas Bandara');
        $epasPerson = $this->node('SEC-EPAS-PERSON', $epas, 4, '1) Orang');

        $this->periodGroup($epasPerson, 'SEC-EPAS-PERSON-OPERATOR', 5, 'a) Penyelenggara bandar udara, navigasi dan penerbangan', [
            ['BULANAN', '(1) bulanan', 100000, 'per orang per bulan'],
            ['TAHUNAN', '(2) tahunan', 150000, 'per orang per tahun'],
        ]);
        $this->periodGroup($epasPerson, 'SEC-EPAS-PERSON-COMPANY', 5, 'b) perusahaan terkait dan penunjang penerbangan', [
            ['MINGGUAN', '(1) mingguan', 75000, 'per orang per minggu'],
            ['BULANAN', '(2) bulanan', 150000, 'per orang per bulan'],
            ['TAHUNAN', '(3) tahunan', 400000, 'per orang per tahun'],
        ]);
        $this->periodGroup($epasPerson, 'SEC-EPAS-PERSON-GOV', 5, 'c) Instansi penyelenggara pemerintahan di bandar udara', [
            ['MINGGUAN', '(1) mingguan', 60000, 'per orang per minggu'],
            ['BULANAN', '(2) bulanan', 100000, 'per orang per bulan'],
            ['TAHUNAN', '(3) tahunan', 150000, 'per orang per tahun'],
        ]);
        $this->periodGroup($epasPerson, 'SEC-EPAS-PERSON-PUBLIC', 5, 'd) umum', [
            ['MINGGUAN', '(1) mingguan', 100000, 'per orang per minggu'],
            ['BULANAN', '(2) bulanan', 300000, 'per orang per bulan'],
            ['TAHUNAN', '(3) tahunan', 2500000, 'per orang per tahun'],
        ]);

        $manual = $this->node('SEC-MANUAL', $entryPermit, 3, 'b. Pas Bandara Manual');
        $manualPerson = $this->node('SEC-MANUAL-PERSON', $manual, 4, '1) Orang');
        $this->periodGroup($manualPerson, 'SEC-MANUAL-PERSON-OPERATOR', 5, 'a) Penyelenggara bandar udara, navigasi dan penerbangan', [
            ['BULANAN', '(1) bulanan', 35000, 'per orang per bulan'],
            ['TAHUNAN', '(2) tahunan', 100000, 'per orang per tahun'],
        ]);
        $this->periodGroup($manualPerson, 'SEC-MANUAL-PERSON-COMPANY', 5, 'b) perusahaan terkait dan penunjang penerbangan', [
            ['MINGGUAN', '(1) mingguan', 35000, 'per orang per minggu'],
            ['BULANAN', '(2) bulanan', 70000, 'per orang per bulan'],
            ['TAHUNAN', '(3) tahunan', 200000, 'per orang per tahun'],
        ]);
        $this->periodGroup($manualPerson, 'SEC-MANUAL-PERSON-GOV', 5, 'c) Instansi penyelenggara pemerintahan di bandar udara', [
            ['BULANAN', '(1) bulanan', 35000, 'per orang per bulan'],
            ['TAHUNAN', '(2) tahunan', 100000, 'per orang per tahun'],
        ]);
        $this->periodGroup($manualPerson, 'SEC-MANUAL-PERSON-PUBLIC', 5, 'd) umum', [
            ['MINGGUAN', '(1) mingguan', 70000, 'per orang per minggu'],
            ['BULANAN', '(2) bulanan', 200000, 'per orang per bulan'],
            ['TAHUNAN', '(3) tahunan', 1600000, 'per orang per tahun'],
        ]);

        $manualVehicle = $this->node('SEC-MANUAL-VEHICLE', $manual, 4, '2) Kendaraan');
        $this->node('SEC-MANUAL-VEHICLE-OPERATOR', $manualVehicle, 5, 'a) Penyelenggara bandar udara, navigasi dan penerbangan', true, 200000, 'per kendaraan per tahun');
        $this->periodGroup($manualVehicle, 'SEC-MANUAL-VEHICLE-COMPANY', 5, 'b) Perusahaan terkait dan penunjang penerbangan', [
            ['MINGGUAN', '(1) mingguan', 30000, 'per kendaraan per minggu'],
            ['BULANAN', '(2) bulanan', 50000, 'per kendaraan per bulan'],
            ['TAHUNAN', '(3) tahunan', 200000, 'per kendaraan per tahun'],
        ]);
        $this->periodGroup($manualVehicle, 'SEC-MANUAL-VEHICLE-GOV', 5, 'c) Instansi penyelenggara pemerintahan di bandar udara', [
            ['BULANAN', '(1) bulanan', 50000, 'per kendaraan per bulan'],
            ['TAHUNAN', '(2) tahunan', 150000, 'per kendaraan per tahun'],
        ]);

        $stayPermit = $this->node('SEC-STAY', $root, 2, '2. Izin Mengemudi');
        $this->node('SEC-STAY-BUBU', $stayPermit, 3, 'a. Pada Badan Usaha Bandar Udara', true, 150000, 'per orang per tahun');
        $this->node('SEC-STAY-UPBU', $stayPermit, 3, 'b. Pada Unit Penyelenggara Bandar Udara', true, 100000, 'per orang per tahun');
    }

    public function down(): void
    {
        DB::table('layanan_jasas')->where('kode_layanan', 'like', 'SEC-%')->delete();
    }

    private function periodGroup(int $parent, string $kode, int $level, string $name, array $items): void
    {
        $group = $this->node($kode, $parent, $level, $name);

        foreach ($items as [$suffix, $label, $tarif, $satuan]) {
            $this->node($kode . '-' . $suffix, $group, $level + 1, $label, true, $tarif, $satuan);
        }
    }

    private function node(
        string $kode,
        ?int $parentId,
        int $level,
        string $name,
        bool $isLeaf = false,
        int $tarif = 0,
        ?string $satuan = null
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
            'mendukung_konsesi' => false,
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
