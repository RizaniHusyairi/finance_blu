<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $root = DB::table('layanan_jasas')
            ->where('kode_layanan', 'KSP-ROOT')
            ->orWhere('nama_layanan', 'K. PENGGUNAAN SARANA DAN PRASARANA DI BANDAR UDARA BERDASARKAN TUGAS DAN FUNGSI')
            ->first();

        if (! $root) {
            return;
        }

        $workshop = $this->node(
            'KSP-WORKSHOP',
            (int) $root->id,
            2,
            '16. Sewa Peralatan Workshop'
        );

        foreach ($this->items() as [$suffix, $label, $tarif]) {
            $this->node(
                'KSP-WORKSHOP-' . $suffix,
                $workshop,
                3,
                $label,
                true,
                $tarif,
                'per jam',
                '424924'
            );
        }
    }

    public function down(): void
    {
        DB::table('layanan_jasas')->where('kode_layanan', 'like', 'KSP-WORKSHOP%')->delete();
    }

    private function items(): array
    {
        return [
            ['AIR-COMPRESSOR', 'a. Air Compressor', 35000],
            ['BATTERY-CHARGER', 'b. Battery Charger', 21000],
            ['HAND-DRILL', 'c. Hand Drill', 21000],
            ['GENSET', 'd. Genset', 37000],
            ['ANGLE-GRINDER', 'e. Angle Grinder', 21000],
            ['BENCH-GRINDER', 'f. Bench Grinder', 30000],
            ['HEAT-GUN', 'g. Heat Gun', 21000],
            ['ELECTRIC-WELDING', 'h. Electric Welding', 30000],
            ['TROLLY-HYDRAULIC-JACK', 'i. Trolly Hydraulic Jack', 30000],
            ['TOLLKIT-SET', 'j. Tollkit Set', 31000],
            ['JACK-STAND', 'k. Jack Stand', 13000],
            ['JACK-HAMMER', 'l. Jack Hammer', 30000],
            ['SCISSOR-LIFT', 'm. Scissor Lift', 47000],
            ['HAND-PALLET', 'n. Hand Pallet', 20000],
            ['CAR-LIFT', 'o. Car Lift', 47000],
        ];
    }

    private function node(
        string $kode,
        ?int $parentId,
        int $level,
        string $name,
        bool $isLeaf = false,
        int $tarif = 0,
        ?string $satuan = null,
        ?string $kodeAkun = null
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
