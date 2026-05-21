<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $codes = [
        'LAND-ROOT',
        'LAND-JAM-OPERASI',
        'LAND-DN',
        'LAND-DN-KU',
        'LAND-DN-KU-A',
        'LAND-DN-KU-B',
        'LAND-DN-KU-C',
        'LAND-DN-K1',
        'LAND-DN-K1-A',
        'LAND-DN-K1-B',
        'LAND-DN-K1-C',
        'LAND-DN-K2',
        'LAND-DN-K2-A',
        'LAND-DN-K2-B',
        'LAND-DN-K3-SP',
        'LAND-LN',
        'LAND-LN-KU',
        'LAND-LN-KU-A',
        'LAND-LN-KU-B',
        'LAND-LN-KU-C',
        'LAND-LN-K1',
        'LAND-LN-K1-A',
        'LAND-LN-K1-B',
        'LAND-LN-K1-C',
        'LAND-LN-K2-K3-SP',
        'LAND-LN-K2-K3-SP-A',
        'LAND-LN-K2-K3-SP-B',
    ];

    public function up(): void
    {
        $rootId = $this->upsertNode(
            'LAND-ROOT',
            null,
            1,
            'C. JASA PENDARATAN PESAWAT UDARA',
            false
        );

        $jamOperasiId = $this->upsertNode(
            'LAND-JAM-OPERASI',
            $rootId,
            2,
            '1. Pelayanan Jasa atas Penggunaan Bandar Udara pada Jam Operasi',
            false
        );

        $domesticId = $this->upsertNode('LAND-DN', $jamOperasiId, 3, 'a. Dalam Negeri (Domestik)', false);
        $internationalId = $this->upsertNode('LAND-LN', $jamOperasiId, 3, 'b. Luar Negeri (Internasional)', false);

        $domesticUtamaId = $this->upsertNode('LAND-DN-KU', $domesticId, 4, '1) Bandar udara kelas I utama', false);
        $domesticKelasIId = $this->upsertNode('LAND-DN-K1', $domesticId, 4, '2) Bandar udara kelas I', false);
        $domesticKelasIIId = $this->upsertNode('LAND-DN-K2', $domesticId, 4, '3) Bandar udara kelas II', false);

        $internationalUtamaId = $this->upsertNode('LAND-LN-KU', $internationalId, 4, '1) Bandar udara kelas I utama', false);
        $internationalKelasIId = $this->upsertNode('LAND-LN-K1', $internationalId, 4, '2) Bandar udara kelas I', false);
        $internationalKelasIIId = $this->upsertNode('LAND-LN-K2-K3-SP', $internationalId, 4, '3) Bandar udara kelas II, III, dan Satuan Pelayanan', false);

        $items = [
            [$domesticUtamaId, 'LAND-DN-KU-A', 'a) bobot pesawat s.d. 40.000 kg', 5000],
            [$domesticUtamaId, 'LAND-DN-KU-B', 'b) bobot pesawat diatas 40.000 kg s.d. 100.000 kg', 6000],
            [$domesticUtamaId, 'LAND-DN-KU-C', 'c) bobot pesawat di atas 100.000 kg', 7000],
            [$domesticKelasIId, 'LAND-DN-K1-A', 'a) bobot pesawat s.d. 40.000 kg', 3000],
            [$domesticKelasIId, 'LAND-DN-K1-B', 'b) bobot pesawat diatas 40.000 kg s.d. 100.000 kg', 4000],
            [$domesticKelasIId, 'LAND-DN-K1-C', 'c) bobot pesawat di atas 100.000 kg', 5000],
            [$domesticKelasIIId, 'LAND-DN-K2-A', 'a) bobot pesawat s.d. 40.000 kg', 2500],
            [$domesticKelasIIId, 'LAND-DN-K2-B', 'b) bobot pesawat diatas 40.000 kg s.d. 100.000 kg', 3500],
            [$domesticId, 'LAND-DN-K3-SP', '4) Bandar Udara Kelas III dan satuan Pelayanan', 2000],
            [$internationalUtamaId, 'LAND-LN-KU-A', 'a) bobot pesawat s.d. 40.000 kg', 52000],
            [$internationalUtamaId, 'LAND-LN-KU-B', 'b) bobot pesawat diatas 40.000 kg s.d. 100.000 kg', 58500],
            [$internationalUtamaId, 'LAND-LN-KU-C', 'c) bobot pesawat di atas 100.000 kg', 66300],
            [$internationalKelasIId, 'LAND-LN-K1-A', 'a) bobot pesawat s.d. 40.000 kg', 46800],
            [$internationalKelasIId, 'LAND-LN-K1-B', 'b) bobot pesawat diatas 40.000 kg s.d. 100.000 kg', 53300],
            [$internationalKelasIId, 'LAND-LN-K1-C', 'c) bobot pesawat di atas 100.000 kg', 61100],
            [$internationalKelasIIId, 'LAND-LN-K2-K3-SP-A', 'a) bobot pesawat s.d. 40.000 kg', 35100],
            [$internationalKelasIIId, 'LAND-LN-K2-K3-SP-B', 'b) bobot pesawat diatas 40.000 kg s.d. 100.000 kg', 40300],
        ];

        foreach ($items as [$parentId, $kode, $name, $tarif]) {
            $this->upsertNode($kode, $parentId, $this->levelForParent($parentId), $name, true, $tarif);
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
            'satuan' => $isLeaf ? 'tiap 1000 kg atau bagiannya' : null,
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

    private function levelForParent(int $parentId): int
    {
        $parent = DB::table('layanan_jasas')->where('id', $parentId)->first(['level']);

        return ((int) ($parent->level ?? 0)) + 1;
    }
};
