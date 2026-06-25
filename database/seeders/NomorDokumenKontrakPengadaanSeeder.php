<?php

namespace Database\Seeders;

use App\Models\DocumentNumber;
use App\Models\DocumentNumberSequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Register nomor dokumen PPK Belanja Barang & Modal (kontrak pengadaan).
 *
 * Sumber: database/data/nomor-dokumen-kontrak-pengadaan-seed.json — snapshot dari
 * "Nomor PPK Belanja Barang dan Modal.xlsx". Kolom "No" pada Excel = nomor urut
 * GLOBAL pada sequence_group KONTRAK_PPK_BB_APTP. PL.107/PL.108/PL.109 berbagi
 * satu sequence; prefix ditentukan dari perihal (sesuai legenda Excel):
 *   - Kontrak / Surat ke Penyedia (SPK/SPMK/IP/SPPBJ/PO/Adendum) → PL.107
 *   - BAPP / BAST                                                → PL.108
 *   - BAP                                                        → PL.109
 * Semua bersuffix PPK.BB/APTP, tahun mengikuti file (2026).
 *
 * Tiap nomor dicatat status USED (sumber EXTERNAL = impor register manual) supaya
 * penomoran otomatis sistem TIDAK menerbitkan ulang nomor yang sudah dipakai, dan
 * register lama tetap terlihat di menu Manajemen Nomor Dokumen.
 *
 * Idempoten: nomor (sequence_group + tahun + running_number) yang sudah tercatat
 * dilewati; sequence last_number diset ke max(existing, file).
 */
class NomorDokumenKontrakPengadaanSeeder extends Seeder
{
    private const GLOBAL_SUFFIX = '__GLOBAL__';

    public function run(): void
    {
        if (! Schema::hasTable('document_numbers') || ! Schema::hasTable('document_number_sequences')) {
            $this->command?->warn('Tabel penomoran dokumen belum ada. Seeder dilewati.');
            return;
        }

        $path = database_path('data/nomor-dokumen-kontrak-pengadaan-seed.json');
        if (! file_exists($path)) {
            $this->command?->error("File seed tidak ditemukan: {$path}");
            return;
        }

        $data = json_decode(file_get_contents($path), true);
        if (! is_array($data) || empty($data['entries'])) {
            $this->command?->warn('File seed nomor dokumen kosong / tidak valid.');
            return;
        }

        $tahun = (int) ($data['tahun'] ?? now()->year);
        $group = (string) ($data['sequence_group'] ?? 'KONTRAK_PPK_BB_APTP');
        $suffix = (string) ($data['suffix_code'] ?? 'PPK.BB/APTP');
        $padding = (int) ($data['number_padding'] ?? 4);
        $now = Carbon::now();

        // Nomor urut yang sudah tercatat (termasuk soft-deleted) pada grup+tahun ini.
        $existing = DB::table('document_numbers')
            ->where('sequence_group', $group)
            ->where('tahun', $tahun)
            ->pluck('running_number')
            ->flip();

        $rows = [];
        $skipped = 0;
        $maxRunning = 0;

        foreach ($data['entries'] as $entry) {
            $no = (int) ($entry['no'] ?? 0);
            $prefix = trim((string) ($entry['series_prefix'] ?? ''));
            $key = trim((string) ($entry['document_key'] ?? 'SPK')) ?: 'SPK';
            $perihal = trim((string) ($entry['perihal'] ?? ''));
            if ($no < 1 || $prefix === '') {
                continue;
            }
            $maxRunning = max($maxRunning, $no);

            if (isset($existing[$no])) {
                $skipped++;
                continue;
            }
            $existing[$no] = true; // jaga-jaga bila ada duplikat dalam file

            $rows[] = [
                'document_key' => $key,
                'sequence_group' => $group,
                'series_prefix' => $prefix,
                'suffix_code' => $suffix,
                'tahun' => $tahun,
                'running_number' => $no,
                'number_padding' => $padding,
                'full_number' => sprintf('%s/%s/%s/%d', $prefix, str_pad((string) $no, $padding, '0', STR_PAD_LEFT), $suffix, $tahun),
                'status' => DocumentNumber::STATUS_USED,
                'usage_source' => DocumentNumber::SOURCE_EXTERNAL,
                'notes' => 'Impor register PPK BB ' . $tahun . ($perihal !== '' ? ': ' . $perihal : ''),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('document_numbers')->insert($chunk);
        }

        // Sequence global: pastikan ada & last_number = max(existing, file).
        $sequence = DocumentNumberSequence::firstOrNew([
            'sequence_group' => $group,
            'tahun' => $tahun,
        ]);
        $sequence->series_prefix = $group;
        $sequence->suffix_code = self::GLOBAL_SUFFIX;
        $sequence->number_padding = $padding;
        $sequence->is_active = true;
        $sequence->keterangan = $sequence->keterangan ?: 'Sequence global nomor dokumen pengadaan (impor register PPK).';
        $sequence->last_number = max((int) ($sequence->last_number ?? 0), $maxRunning);
        $sequence->save();

        $this->command?->info(sprintf(
            '✓ Nomor dokumen PPK %d: %d dibuat, %d dilewati. last_number=%d.',
            $tahun,
            count($rows),
            $skipped,
            $sequence->last_number,
        ));
    }
}
