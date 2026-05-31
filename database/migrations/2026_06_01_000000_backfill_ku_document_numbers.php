<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill nomor surat berawalan KU (KU.201 Honorarium & Perjaldin, KU.102 Surat
 * Pengantar Jasa) yang sudah ada di tabel domain (tagihan / tagihan_jasas) ke
 * register terpusat `document_numbers` di bawah satu sequence_group: KU_APTP.
 *
 * Tujuan: setelah backfill, generator otomatis (DocumentNumberService) akan
 * MELEWATI nomor urut 4 digit yang sudah dipakai sehingga ketiga jenis surat
 * tidak akan memakai nomor urut yang sama untuk tahun yang sama.
 *
 * Catatan: jika ada bentrok historis (nomor urut sama dipakai >1 surat lintas
 * tipe sebelum aturan ini), hanya 1 yang tercatat di ledger (karena unique
 * index sequence_group+tahun+running_number). Surat lama tetap memakai nomornya
 * masing-masing; ledger cukup menandai nomor urut itu "terpakai" agar tidak
 * dipakai lagi ke depan.
 */
return new class extends Migration
{
    private const GROUP = 'KU_APTP';

    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('document_numbers')) {
            return;
        }

        $rows = [];

        // 1) KU.201 dari tabel tagihan (Honorarium & Perjaldin)
        DB::table('tagihan')
            ->whereNull('deleted_at')
            ->where('nomor_tagihan', 'like', 'KU.201/%/APTP/%')
            ->orderBy('id')
            ->get(['nomor_tagihan', 'tipe_tagihan'])
            ->each(function ($t) use (&$rows) {
                if (preg_match('#^KU\.201/(\d+)/APTP/(\d+)$#', (string) $t->nomor_tagihan, $m)) {
                    $rows[] = [
                        'document_key' => $t->tipe_tagihan === 'PERJALDIN' ? 'KU_PERJALDIN' : 'KU_HONOR',
                        'series_prefix' => 'KU.201',
                        'suffix_code' => 'APTP',
                        'tahun' => (int) $m[2],
                        'running_number' => (int) $m[1],
                        'full_number' => $t->nomor_tagihan,
                    ];
                }
            });

        // 2) KU.102 dari tabel tagihan_jasas (Surat Pengantar Jasa)
        if (DB::getSchemaBuilder()->hasTable('tagihan_jasas')) {
            DB::table('tagihan_jasas')
                ->whereNull('deleted_at')
                ->whereNotNull('nomor_surat_pengantar')
                ->where('nomor_surat_pengantar', 'like', 'KU.102/%/APTP/%')
                ->orderBy('id')
                ->pluck('nomor_surat_pengantar')
                ->each(function ($nomor) use (&$rows) {
                    if (preg_match('#^KU\.102/(\d+)/APTP/(\d+)$#', (string) $nomor, $m)) {
                        $rows[] = [
                            'document_key' => 'KU_SURAT_PENGANTAR_JASA',
                            'series_prefix' => 'KU.102',
                            'suffix_code' => 'APTP',
                            'tahun' => (int) $m[2],
                            'running_number' => (int) $m[1],
                            'full_number' => $nomor,
                        ];
                    }
                });
        }

        $seenPerYear = [];   // tahun => [running_number => true]
        $maxPerYear = [];    // tahun => max running_number

        foreach ($rows as $row) {
            $tahun = $row['tahun'];
            $run = $row['running_number'];

            // Lewati duplikat (group, tahun, running) agar tidak melanggar unique index.
            if (isset($seenPerYear[$tahun][$run])) {
                continue;
            }

            $alreadyInLedger = DB::table('document_numbers')
                ->where('sequence_group', self::GROUP)
                ->where('tahun', $tahun)
                ->where('running_number', $run)
                ->exists();

            if (! $alreadyInLedger) {
                DB::table('document_numbers')->insert([
                    'document_key' => $row['document_key'],
                    'sequence_group' => self::GROUP,
                    'series_prefix' => $row['series_prefix'],
                    'suffix_code' => $row['suffix_code'],
                    'tahun' => $tahun,
                    'running_number' => $run,
                    'number_padding' => 4,
                    'full_number' => $row['full_number'],
                    'status' => 'USED',
                    'usage_source' => 'INTERNAL',
                    'notes' => 'Backfill nomor KU yang sudah dipakai sebelum register terpusat.',
                    'used_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $seenPerYear[$tahun][$run] = true;
            $maxPerYear[$tahun] = max($maxPerYear[$tahun] ?? 0, $run);
        }

        // Set/refresh sequence terpusat per tahun agar generator mulai dari max+0.
        if (DB::getSchemaBuilder()->hasTable('document_number_sequences')) {
            foreach ($maxPerYear as $tahun => $maxRun) {
                $existing = DB::table('document_number_sequences')
                    ->where('sequence_group', self::GROUP)
                    ->where('tahun', $tahun)
                    ->first();

                if ($existing) {
                    if ((int) $existing->last_number < $maxRun) {
                        DB::table('document_number_sequences')
                            ->where('id', $existing->id)
                            ->update(['last_number' => $maxRun, 'updated_at' => now()]);
                    }
                } else {
                    DB::table('document_number_sequences')->insert([
                        'sequence_group' => self::GROUP,
                        'series_prefix' => self::GROUP,
                        'suffix_code' => '__GLOBAL__',
                        'tahun' => $tahun,
                        'last_number' => $maxRun,
                        'number_padding' => 4,
                        'is_active' => true,
                        'keterangan' => 'Sequence global nomor surat KU (Honorarium, Perjaldin, Surat Pengantar Jasa).',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (DB::getSchemaBuilder()->hasTable('document_numbers')) {
            DB::table('document_numbers')->where('sequence_group', self::GROUP)->delete();
        }
        if (DB::getSchemaBuilder()->hasTable('document_number_sequences')) {
            DB::table('document_number_sequences')->where('sequence_group', self::GROUP)->delete();
        }
    }
};
