<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $codes = [
        'STORE-ROOT',
        'STORE-DN',
        'STORE-DN-KU',
        'STORE-DN-K1',
        'STORE-DN-K2',
        'STORE-DN-K3-SP',
        'STORE-LN',
        'STORE-LN-KU',
        'STORE-LN-K1',
        'STORE-LN-K2-K3-SP',
        'OUT-OPS-ROOT',
        'ALT-STANDBY-ROOT',
        'BRIDGE-ROOT',
        'BRIDGE-DN',
        'BRIDGE-LN',
        'BRIDGE-LN-A',
        'BRIDGE-LN-B',
        'BRIDGE-LN-C',
        'BRIDGE-LN-D',
    ];

    public function up(): void
    {
        $storageRootId = $this->upsertNode('STORE-ROOT', null, 1, 'E. JASA PENYIMPANAN PESAWAT UDARA', false);
        $storageDomesticId = $this->upsertNode('STORE-DN', $storageRootId, 2, '1. Dalam Negeri (Domestik)', false);
        $storageInternationalId = $this->upsertNode('STORE-LN', $storageRootId, 2, '2. Luar Negeri', false);

        $storageItems = [
            [$storageDomesticId, 'STORE-DN-KU', 'a. Bandar Udara Kelas I Utama', 1600],
            [$storageDomesticId, 'STORE-DN-K1', 'b. Bandar Udara Kelas I', 1400],
            [$storageDomesticId, 'STORE-DN-K2', 'c. Bandar Udara Kelas II', 1200],
            [$storageDomesticId, 'STORE-DN-K3-SP', 'd. Bandar Udara Kelas III dan Satuan Pelayanan', 1000],
            [$storageInternationalId, 'STORE-LN-KU', 'a. Bandar Udara Kelas I Utama', 10400],
            [$storageInternationalId, 'STORE-LN-K1', 'b. Bandar Udara Kelas I', 7800],
            [$storageInternationalId, 'STORE-LN-K2-K3-SP', 'c. Bandar Udara kelas II, III, dan satuan pelayanan', 5200],
        ];

        foreach ($storageItems as [$parentId, $kode, $name, $tarif]) {
            $this->upsertNode($kode, $parentId, 3, $name, true, $tarif, 'tiap 1000 kg per 12 jam atau bagiannya');
        }

        $this->upsertNode(
            'OUT-OPS-ROOT',
            null,
            1,
            'F. PENGGUNAAN BANDAR UDARA UNTUK PESAWAT UDARA DI LUAR JAM OPERASI',
            true,
            1,
            'per sekali lepas landas dan/atau pendaratan'
        );

        $this->upsertNode(
            'ALT-STANDBY-ROOT',
            null,
            1,
            'G. STANDBY BANDAR UDARA ALTERNATIF (STANDBY ALTERTE AERODROME) DILUAR JAM OPERASI BANDARA',
            true,
            33,
            '% sekali lintas'
        );

        $bridgeRootId = $this->upsertNode('BRIDGE-ROOT', null, 1, 'H. JASA PEMAKAIAN GARBARATA (AVIOBRIDGE)', false);
        $this->upsertNode('BRIDGE-DN', $bridgeRootId, 2, '1. Penerbangan Dalam Negeri', true, 200000, 'per jam');
        $bridgeInternationalId = $this->upsertNode('BRIDGE-LN', $bridgeRootId, 2, '2. Penerbangan Luar Negeri', false);

        $bridgeItems = [
            [$bridgeInternationalId, 'BRIDGE-LN-A', 'a. s.d. 100.000 kg', 900000],
            [$bridgeInternationalId, 'BRIDGE-LN-B', 'b. Diatas 100.000 s.d. 200.000 kg', 2300000],
            [$bridgeInternationalId, 'BRIDGE-LN-C', 'c. Diatas 200.000 s.d. 300.000 kg', 3800000],
            [$bridgeInternationalId, 'BRIDGE-LN-D', 'd. Di atas 300.000 kg', 4300000],
        ];

        foreach ($bridgeItems as [$parentId, $kode, $name, $tarif]) {
            $this->upsertNode($kode, $parentId, 3, $name, true, $tarif, 'per jam');
        }
    }

    public function down(): void
    {
        DB::table('layanan_jasas')
            ->whereIn('kode_layanan', $this->codes)
            ->delete();
    }

    private function upsertNode(
        string $kode,
        ?int $parentId,
        int $level,
        string $name,
        bool $isLeaf,
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

        $existing = DB::table('layanan_jasas')
            ->where('kode_layanan', $kode)
            ->first();

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
