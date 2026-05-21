<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $codes = [
        'PARK-ROOT',
        'PARK-DN',
        'PARK-DN-KU',
        'PARK-DN-K1',
        'PARK-DN-K2',
        'PARK-DN-K3-SP',
        'PARK-LN',
        'PARK-LN-KU',
        'PARK-LN-K1',
        'PARK-LN-K2-K3-SP',
    ];

    public function up(): void
    {
        $rootId = $this->upsertNode('PARK-ROOT', null, 1, 'D. JASA PENEMPATAN PESAWAT UDARA', false);
        $domesticId = $this->upsertNode('PARK-DN', $rootId, 2, '1. Dalam Negeri (Domestik)', false);
        $internationalId = $this->upsertNode('PARK-LN', $rootId, 2, '2. Luar Negeri', false);

        $items = [
            [$domesticId, 'PARK-DN-KU', 'a. Bandar Udara Kelas I Utama', 350],
            [$domesticId, 'PARK-DN-K1', 'b. Bandar Udara Kelas I', 325],
            [$domesticId, 'PARK-DN-K2', 'c. Bandar Udara Kelas II', 300],
            [$domesticId, 'PARK-DN-K3-SP', 'd. Bandar Udara Kelas III dan Satuan Pelayanan', 275],
            [$internationalId, 'PARK-LN-KU', 'a. Bandar Udara Kelas I Utama', 1690],
            [$internationalId, 'PARK-LN-K1', 'b. Bandar Udara Kelas I', 1560],
            [$internationalId, 'PARK-LN-K2-K3-SP', 'c. Bandar Udara kelas , III, dan satuan pelayanan', 1430],
        ];

        foreach ($items as [$parentId, $kode, $name, $tarif]) {
            $this->upsertNode($kode, $parentId, 3, $name, true, $tarif);
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
        int $tarif = 0
    ): int {
        $now = now();
        $payload = [
            'parent_id' => $parentId,
            'level' => $level,
            'nama_layanan' => $name,
            'pic_name' => null,
            'tarif_dasar' => $tarif,
            'satuan' => $isLeaf ? 'per jam per ton' : null,
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
