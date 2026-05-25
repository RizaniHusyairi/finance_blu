<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeder Master Layanan Jasa.
 *
 * Sumber data: `database/data/layanan-jasa-seed.json` — snapshot dari struktur
 * tree layanan jasa (172 entry) yang sebelumnya di-seed melalui migration
 * `2026_05_15_*_seed_*_tree.php`.
 *
 * Karakteristik:
 *  - Idempotent: pakai `kode_layanan` sebagai natural key. Re-run aman, hanya
 *    update kalau ada perubahan field.
 *  - Auto-resolve parent: parent_id diisi via lookup `parent_kode` → id yang
 *    baru saja diinsert.
 *  - Urutan alfabet: data di-sort berdasarkan prefix root (`A.`, `B.`, …, `L.`)
 *    lalu level + kode_layanan secara natural — sehingga listing di UI tampil
 *    rapi.
 *
 * Cara pakai:
 *   php artisan db:seed --class=MasterLayananJasaSeeder
 */
class MasterLayananJasaSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/layanan-jasa-seed.json');

        if (! file_exists($path)) {
            $this->command->error("File seed tidak ditemukan: {$path}");
            return;
        }

        $rows = json_decode(file_get_contents($path), true);

        if (! is_array($rows) || empty($rows)) {
            $this->command->warn('File seed kosong atau tidak valid.');
            return;
        }

        // Sort: prefix abjad (A. B. C. ... L.), lalu level, lalu kode natural.
        usort($rows, function ($a, $b) {
            $pa = $this->extractRootLetter($a['nama']);
            $pb = $this->extractRootLetter($b['nama']);
            if ($pa !== $pb) {
                return strcmp($pa, $pb);
            }
            // Level lebih kecil duluan (parent sebelum anak).
            if ($a['level'] !== $b['level']) {
                return $a['level'] <=> $b['level'];
            }
            // Kode natural-sort agar PJPU-DN-I < PJPU-DN-II < PJPU-DN-III dst.
            return strnatcmp((string) ($a['kode'] ?? ''), (string) ($b['kode'] ?? ''));
        });

        $now = Carbon::now();
        $kodeToId = [];
        $created = 0;
        $updated = 0;

        // Pertama: pastikan semua row tertulis (dua-pass agar parent_id ter-resolve).
        // Pass 1: insert/update tanpa parent_id (set dulu ke null untuk yang non-root).
        foreach ($rows as $row) {
            $kode = (string) ($row['kode'] ?? '');
            if ($kode === '') {
                $this->command->warn("Skip row tanpa kode: " . ($row['nama'] ?? '?'));
                continue;
            }

            $payload = [
                'kode_layanan' => $kode,
                'nama_layanan' => (string) $row['nama'],
                'level' => (int) ($row['level'] ?? 1),
                'tarif_dasar' => (float) ($row['tarif'] ?? 0),
                'satuan' => $row['satuan'] ?? null,
                'is_leaf' => (bool) ($row['is_leaf'] ?? false),
                'is_active' => (bool) ($row['is_active'] ?? true),
                'tipe_layanan' => $row['tipe'] ?? 'PNBP',
                'kode_mak' => $row['kode_mak'] ?? null,
                'kode_akun' => $row['kode_akun'] ?? null,
                'mendukung_konsesi' => (bool) ($row['mendukung_konsesi'] ?? false),
                'jumlah_hari_jatuh_tempo' => (int) ($row['jth_tempo'] ?? 30),
                'wajib_tagihan_terpisah' => (bool) ($row['wajib_terpisah'] ?? false),
                'updated_at' => $now,
            ];

            $existing = DB::table('layanan_jasas')->where('kode_layanan', $kode)->first();
            if ($existing) {
                DB::table('layanan_jasas')->where('id', $existing->id)->update($payload);
                $kodeToId[$kode] = (int) $existing->id;
                $updated++;
            } else {
                $payload['created_at'] = $now;
                $kodeToId[$kode] = (int) DB::table('layanan_jasas')->insertGetId($payload);
                $created++;
            }
        }

        // Pass 2: set parent_id setelah semua kode tersedia.
        $parentLinked = 0;
        foreach ($rows as $row) {
            $kode = (string) ($row['kode'] ?? '');
            if ($kode === '' || ! isset($kodeToId[$kode])) {
                continue;
            }
            $parentKode = $row['parent_kode'] ?? null;
            $parentId = $parentKode ? ($kodeToId[$parentKode] ?? null) : null;

            DB::table('layanan_jasas')
                ->where('id', $kodeToId[$kode])
                ->update(['parent_id' => $parentId]);

            if ($parentId !== null) {
                $parentLinked++;
            }
        }

        $this->command->info("✓ Master Layanan Jasa: {$created} dibuat, {$updated} diupdate, {$parentLinked} parent-link diatur.");
    }

    /**
     * Ambil huruf prefix root: "B. Pelayanan…" → "B".
     * Untuk turunan, ambil dari kode bila pola `XYZ-…` (mis. PJPU → P).
     */
    private function extractRootLetter(string $nama): string
    {
        if (preg_match('/^([A-Z])\.\s/', $nama, $m)) {
            return strtoupper($m[1]);
        }
        return 'Z';
    }
}
