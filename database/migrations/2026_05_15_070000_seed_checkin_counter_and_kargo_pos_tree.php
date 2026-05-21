<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $codes = [
        'CHECKIN-ROOT',
        'CHECKIN-DN',
        'CHECKIN-DN-KU',
        'CHECKIN-DN-K1-K2',
        'CHECKIN-DN-K3-SP',
        'CHECKIN-LN',
        'CHECKIN-LN-KU',
        'CHECKIN-LN-K1-K2',
        'CHECKIN-LN-K3-SP',
        'CARGO-ROOT',
        'CARGO-STORE',
        'CARGO-STORE-IMPORT',
        'CARGO-STORE-IMPORT-M1',
        'CARGO-STORE-IMPORT-M2',
        'CARGO-STORE-IMPORT-M3',
        'CARGO-STORE-IMPORT-M4',
        'CARGO-STORE-EXPORT',
        'CARGO-STORE-EXPORT-M1',
        'CARGO-STORE-EXPORT-M2',
        'CARGO-STORE-DOMESTIC',
        'CARGO-STORE-DOMESTIC-M1',
        'CARGO-STORE-DOMESTIC-M2',
        'CARGO-SERVICE',
        'CARGO-SERVICE-KU',
    ];

    public function up(): void
    {
        $checkinRootId = $this->upsertNode('CHECKIN-ROOT', null, 1, 'I. JASA PEMAKAIAN TEMPAT PELAPORAN KEBERANGKATAN (CHECK-IN COUNTER)', false);
        $checkinDomesticId = $this->upsertNode('CHECKIN-DN', $checkinRootId, 2, '1. Penerbangan Dalam Negeri', false);
        $checkinInternationalId = $this->upsertNode('CHECKIN-LN', $checkinRootId, 2, '2. Penerbangan Luar Negeri', false);

        $checkinItems = [
            [$checkinDomesticId, 'CHECKIN-DN-KU', 'a. Bandar Udara Kelas I Utama', 1200],
            [$checkinDomesticId, 'CHECKIN-DN-K1-K2', 'b. Bandar Udara Kelas I dan II', 1100],
            [$checkinDomesticId, 'CHECKIN-DN-K3-SP', 'c. Bandar Udara Kelas III dan Satuan Pelayanan', 1000],
            [$checkinInternationalId, 'CHECKIN-LN-KU', 'a. Bandar Udara Kelas I Utama', 6500],
            [$checkinInternationalId, 'CHECKIN-LN-K1-K2', 'b. Bandar Udara Kelas I dan II', 6000],
            [$checkinInternationalId, 'CHECKIN-LN-K3-SP', 'c. Bandar Udara Kelas III dan Satuan Pelayanan', 5500],
        ];

        foreach ($checkinItems as [$parentId, $kode, $name, $tarif]) {
            $this->upsertNode($kode, $parentId, 3, $name, true, $tarif, 'per penumpang');
        }

        $cargoRootId = $this->upsertNode('CARGO-ROOT', null, 1, 'J. JASA KARGO DAN POS PESAWAT UDARA (JKP2U)', false);
        $cargoStorageId = $this->upsertNode('CARGO-STORE', $cargoRootId, 2, '1. Jasa Penyimpanan', false);
        $cargoImportId = $this->upsertNode('CARGO-STORE-IMPORT', $cargoStorageId, 3, 'a. Barang Impor', false);
        $cargoExportId = $this->upsertNode('CARGO-STORE-EXPORT', $cargoStorageId, 3, 'b. Barang Ekspor', false);
        $cargoDomesticId = $this->upsertNode('CARGO-STORE-DOMESTIC', $cargoStorageId, 3, 'c. Barang Antar Bandara Dalam Negeri', false);

        $cargoStorageItems = [
            [$cargoImportId, 'CARGO-STORE-IMPORT-M1', '1) Masa I (hari ke 1 s.d. hari ke 3)', 500],
            [$cargoImportId, 'CARGO-STORE-IMPORT-M2', '2) Masa II (hari ke 4 s.d. hari ke 10)', 550],
            [$cargoImportId, 'CARGO-STORE-IMPORT-M3', '3) Masa III (hari ke 11 s.d. hari ke 20)', 780],
            [$cargoImportId, 'CARGO-STORE-IMPORT-M4', '4) Masa IV (hari ke 21 dan seterusnya)', 1000],
            [$cargoExportId, 'CARGO-STORE-EXPORT-M1', '1) Masa I (hari ke 1 s.d. hari ke 3)', 320],
            [$cargoExportId, 'CARGO-STORE-EXPORT-M2', '2) Masa II (hari ke 4 dan seterusnya)', 375],
            [$cargoDomesticId, 'CARGO-STORE-DOMESTIC-M1', '1) Masa I (hari ke 1 s.d. hari ke 3)', 51],
            [$cargoDomesticId, 'CARGO-STORE-DOMESTIC-M2', '2) Masa II (hari ke 4 dan seterusnya)', 75],
        ];

        foreach ($cargoStorageItems as [$parentId, $kode, $name, $tarif]) {
            $this->upsertNode($kode, $parentId, 4, $name, true, $tarif, 'per kg per hari');
        }

        $cargoServiceId = $this->upsertNode('CARGO-SERVICE', $cargoRootId, 2, '2. Jasa Layanan Kargo dan Pos Pesawat Udara', false);
        $this->upsertNode('CARGO-SERVICE-KU', $cargoServiceId, 3, 'a. Bandar Udara Kelas I Utama', true, 50, 'per kg');
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
