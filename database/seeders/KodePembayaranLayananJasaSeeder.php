<?php

namespace Database\Seeders;

use App\Models\LayananJasa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class KodePembayaranLayananJasaSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasColumn('layanan_jasas', 'kode_jenis_pembayaran')) {
            $this->command?->warn('Kolom kode_jenis_pembayaran belum tersedia. Jalankan migration terlebih dahulu.');
            return;
        }

        $path = database_path('data/kode-pembayaran-layanan-jasa.json');
        if (! file_exists($path)) {
            $this->command?->error("File referensi kode pembayaran tidak ditemukan: {$path}");
            return;
        }

        $references = json_decode(file_get_contents($path), true);
        if (! is_array($references) || empty($references)) {
            $this->command?->warn('File referensi kode pembayaran kosong atau tidak valid.');
            return;
        }

        $referencesByMak = [];
        foreach ($references as $reference) {
            $kodeMak = trim((string) ($reference['kode_mak'] ?? ''));
            if ($kodeMak === '') {
                continue;
            }
            $referencesByMak[$kodeMak][] = $reference;
        }

        $updated = 0;
        $unchanged = 0;
        $unmatched = 0;
        $ambiguous = [];

        LayananJasa::with('parent.parent.parent.parent.parent')
            ->orderBy('level')
            ->orderBy('id')
            ->get()
            ->each(function (LayananJasa $layanan) use ($referencesByMak, &$updated, &$unchanged, &$unmatched, &$ambiguous) {
                $kodeMak = trim((string) ($layanan->kode_mak ?? ''));
                if ($kodeMak === '' || empty($referencesByMak[$kodeMak])) {
                    $unmatched++;
                    return;
                }

                $match = $this->matchReference($layanan, $referencesByMak[$kodeMak]);
                if (($match['status'] ?? null) === 'none') {
                    $unmatched++;
                    return;
                }

                if (($match['status'] ?? null) === 'ambiguous') {
                    $ambiguous[] = $layanan->nama_lengkap . ' -> ' . implode(', ', $match['codes']);
                    return;
                }

                $reference = $match['reference'];
                $kodeJenis = (string) $reference['kode_jenis_pembayaran'];

                if ((string) ($layanan->kode_jenis_pembayaran ?? '') === $kodeJenis) {
                    $unchanged++;
                    return;
                }

                $layanan->forceFill([
                    'kode_jenis_pembayaran' => $kodeJenis,
                ])->save();

                $updated++;
            });

        $this->command?->info("Kode Pembayaran Layanan Jasa: {$updated} layanan diupdate, {$unchanged} sudah sesuai, {$unmatched} belum cocok.");

        if (! empty($ambiguous)) {
            $this->command?->warn('Layanan ambigu, tidak dipaksa update:');
            foreach (array_slice($ambiguous, 0, 20) as $item) {
                $this->command?->line(' - ' . $item);
            }
            if (count($ambiguous) > 20) {
                $this->command?->line(' - ... ' . (count($ambiguous) - 20) . ' lainnya');
            }
        }
    }

    private function matchReference(LayananJasa $layanan, array $references): array
    {
        $path = $this->normalize($layanan->nama_lengkap . ' ' . $layanan->nama_layanan);
        $candidates = [];

        foreach ($references as $reference) {
            $bestScore = 0;
            foreach (($reference['aliases'] ?? []) as $alias) {
                $normalizedAlias = $this->normalize($alias);
                if ($normalizedAlias === '') {
                    continue;
                }

                if (str_contains(' ' . $path . ' ', ' ' . $normalizedAlias . ' ')) {
                    $bestScore = max($bestScore, strlen($normalizedAlias));
                }
            }

            if ($bestScore > 0) {
                $candidates[] = [
                    'score' => $bestScore,
                    'reference' => $reference,
                ];
            }
        }

        if (empty($candidates)) {
            return ['status' => 'none'];
        }

        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);
        $topScore = $candidates[0]['score'];
        $top = array_values(array_filter($candidates, fn ($candidate) => $candidate['score'] === $topScore));

        if (count($top) === 1) {
            return [
                'status' => 'matched',
                'reference' => $top[0]['reference'],
            ];
        }

        return [
            'status' => 'ambiguous',
            'codes' => array_map(fn ($candidate) => $candidate['reference']['kode_pembayaran_lengkap'], $top),
        ];
    }

    private function normalize(?string $value): string
    {
        $value = strtolower((string) $value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }
}
