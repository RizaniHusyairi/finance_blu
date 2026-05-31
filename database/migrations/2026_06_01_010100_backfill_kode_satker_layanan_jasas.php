<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill kode_satker pada layanan_jasas yang sudah ada di DB.
 *
 * Sumber: database/data/layanan-jasa-seed.json (snapshot jasa_apt.xlsx) yang
 * sudah memuat kode_satker per layanan. Dicocokkan ke baris DB berdasarkan
 * kode_layanan; bila tidak ketemu, dicocokkan berdasarkan nama_layanan.
 *
 * Seluruh layanan PNBP pada Excel memakai satker yang sama (288745).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('layanan_jasas', 'kode_satker')) {
            return;
        }

        $path = database_path('data/layanan-jasa-seed.json');
        if (! file_exists($path)) {
            return;
        }

        $rows = json_decode(file_get_contents($path), true);
        if (! is_array($rows)) {
            return;
        }

        $byKode = [];
        $byNama = [];
        foreach ($rows as $r) {
            $satker = $r['kode_satker'] ?? null;
            if (! $satker) {
                continue;
            }
            if (! empty($r['kode'])) {
                $byKode[(string) $r['kode']] = $satker;
            }
            if (! empty($r['nama'])) {
                $byNama[$this->norm($r['nama'])] = $satker;
            }
        }

        $updated = 0;
        DB::table('layanan_jasas')->orderBy('id')->select('id', 'kode_layanan', 'nama_layanan')
            ->chunkById(200, function ($layanans) use ($byKode, $byNama, &$updated) {
                foreach ($layanans as $l) {
                    $satker = $byKode[(string) $l->kode_layanan]
                        ?? ($byNama[$this->norm($l->nama_layanan)] ?? null);

                    if ($satker) {
                        DB::table('layanan_jasas')->where('id', $l->id)->update(['kode_satker' => $satker]);
                        $updated++;
                    }
                }
            });

        // Fallback: seluruh layanan leaf yang masih kosong diberi satker default
        // yang sama (semua layanan pada Excel memakai 288745).
        $defaultSatker = collect($byKode)->first() ?? collect($byNama)->first();
        if ($defaultSatker) {
            DB::table('layanan_jasas')
                ->whereNull('kode_satker')
                ->where('is_leaf', true)
                ->update(['kode_satker' => $defaultSatker]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('layanan_jasas', 'kode_satker')) {
            DB::table('layanan_jasas')->update(['kode_satker' => null]);
        }
    }

    private function norm(?string $s): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $s)));
    }
};
