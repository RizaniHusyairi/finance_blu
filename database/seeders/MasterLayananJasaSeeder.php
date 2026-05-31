<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeder Master Layanan Jasa.
 *
 * Sumber data: `database/data/layanan-jasa-seed.json` — snapshot dari
 * `jasa_apt.xlsx`; nilai `level` mengikuti kolom `kdlevel`.
 *
 * Karakteristik:
 *  - Replace-only: data lama di `layanan_jasas` dihapus dulu, lalu diisi ulang
 *    hanya dari file seed ini.
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

        // Guard: kolom kode_satker mungkin belum ada bila migrasi belum dijalankan.
        $hasKodeSatker = \Illuminate\Support\Facades\Schema::hasColumn('layanan_jasas', 'kode_satker');

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

        $deleted = DB::table('layanan_jasas')->delete();

        $now = Carbon::now();
        $kodeToId = [];
        $created = 0;

        // Pass 1: insert semua row tanpa parent_id (dua-pass agar parent ter-resolve).
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
                'kode_jenis_pembayaran' => $row['kode_jenis_pembayaran'] ?? null,
                'kode_akun' => $row['kode_akun'] ?? null,
                'mendukung_konsesi' => (bool) ($row['mendukung_konsesi'] ?? false),
                'persentase_konsesi' => $row['persentase_konsesi'] ?? null,
                'jumlah_hari_jatuh_tempo' => (int) ($row['jth_tempo'] ?? 30),
                'wajib_tagihan_terpisah' => (bool) ($row['wajib_terpisah'] ?? false),
                'updated_at' => $now,
            ];

            if ($hasKodeSatker) {
                $payload['kode_satker'] = $row['kode_satker'] ?? null;
            }

            $payload['created_at'] = $now;
            $kodeToId[$kode] = (int) DB::table('layanan_jasas')->insertGetId($payload);
            $created++;
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

        $this->command->info("✓ Master Layanan Jasa: {$deleted} data lama dihapus, {$created} dibuat dari seed Excel, {$parentLinked} parent-link diatur.");
        $this->call(KodePembayaranLayananJasaSeeder::class);
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
