<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rootName = 'B. PELAYANAN JASA PENUMPANG PESAWAT UDARA (PJP2U)';

        $root = DB::table('layanan_jasas')
            ->where('nama_layanan', $rootName)
            ->orWhere('nama_layanan', 'PJP2U')
            ->orderByRaw("CASE WHEN nama_layanan = 'PJP2U' THEN 0 ELSE 1 END")
            ->first();

        if ($root) {
            $rootId = $root->id;
            DB::table('layanan_jasas')->where('id', $rootId)->update([
                'parent_id' => null,
                'level' => 1,
                'nama_layanan' => $rootName,
                'tarif_dasar' => 0,
                'satuan' => null,
                'is_active' => true,
                'is_leaf' => false,
                'tipe_layanan' => 'PNBP',
                'mendukung_konsesi' => false,
                'jumlah_hari_jatuh_tempo' => 7,
                'masa_toleransi_hari' => 0,
                'wajib_tagihan_terpisah' => true,
                'catatan_jatuh_tempo' => 'PJP2U jatuh tempo 7 hari dan tidak digabung dengan layanan lain.',
                'updated_at' => $now,
            ]);
        } else {
            $rootId = DB::table('layanan_jasas')->insertGetId([
                'kode_layanan' => 'PJPU-ROOT',
                'parent_id' => null,
                'level' => 1,
                'nama_layanan' => $rootName,
                'pic_name' => 'Andini',
                'tarif_dasar' => 0,
                'satuan' => null,
                'is_active' => true,
                'is_leaf' => false,
                'tipe_layanan' => 'PNBP',
                'mendukung_konsesi' => false,
                'jumlah_hari_jatuh_tempo' => 7,
                'masa_toleransi_hari' => 0,
                'wajib_tagihan_terpisah' => true,
                'catatan_jatuh_tempo' => 'PJP2U jatuh tempo 7 hari dan tidak digabung dengan layanan lain.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $domesticId = $this->upsertNode('PJPU-DN', $rootId, 2, '1 Dalam Negeri', false);
        $internationalId = $this->upsertNode('PJPU-LN', $rootId, 2, '2 Luar Negeri', false);

        $items = [
            [$domesticId, 'PJPU-DN-IU', 'a. Bandar Udara Kelas I Utama', 40000],
            [$domesticId, 'PJPU-DN-I', 'b. Bandar Udara Kelas I', 30000],
            [$domesticId, 'PJPU-DN-II', 'c. Bandar Udara Kelas II', 25000],
            [$domesticId, 'PJPU-DN-III', 'd. Bandar Udara Kelas III', 20000],
            [$domesticId, 'PJPU-DN-SP', 'e. Bandar Udara Satuan Pelayanan', 15000],
            [$internationalId, 'PJPU-LN-IU', 'a. Bandar Udara Kelas I Utama', 180000],
            [$internationalId, 'PJPU-LN-I', 'b. Bandar Udara Kelas I', 80000],
            [$internationalId, 'PJPU-LN-II-III-SP', 'c. Bandar Udara Kelas II, III dan Satuan Pelayanan', 60000],
        ];

        foreach ($items as [$parentId, $kode, $name, $tarif]) {
            $this->upsertNode($kode, $parentId, 3, $name, true, $tarif, 'per penumpang');
        }
    }

    public function down(): void
    {
        DB::table('layanan_jasas')
            ->whereIn('kode_layanan', [
                'PJPU-DN',
                'PJPU-LN',
                'PJPU-DN-IU',
                'PJPU-DN-I',
                'PJPU-DN-II',
                'PJPU-DN-III',
                'PJPU-DN-SP',
                'PJPU-LN-IU',
                'PJPU-LN-I',
                'PJPU-LN-II-III-SP',
            ])
            ->delete();

        DB::table('layanan_jasas')
            ->where('nama_layanan', 'B. PELAYANAN JASA PENUMPANG PESAWAT UDARA (PJP2U)')
            ->update([
                'nama_layanan' => 'PJP2U',
                'parent_id' => null,
                'level' => 1,
                'is_leaf' => true,
                'tarif_dasar' => 0,
                'satuan' => null,
                'jumlah_hari_jatuh_tempo' => 30,
                'wajib_tagihan_terpisah' => false,
                'catatan_jatuh_tempo' => null,
                'updated_at' => now(),
            ]);
    }

    private function upsertNode(
        string $kode,
        int $parentId,
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
            'pic_name' => 'Andini',
            'tarif_dasar' => $tarif,
            'satuan' => $satuan,
            'is_active' => true,
            'is_leaf' => $isLeaf,
            'tipe_layanan' => 'PNBP',
            'mendukung_konsesi' => false,
            'jumlah_hari_jatuh_tempo' => 7,
            'masa_toleransi_hari' => 0,
            'wajib_tagihan_terpisah' => true,
            'catatan_jatuh_tempo' => 'PJP2U jatuh tempo 7 hari dan tidak digabung dengan layanan lain.',
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
