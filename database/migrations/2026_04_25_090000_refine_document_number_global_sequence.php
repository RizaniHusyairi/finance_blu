<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_number_sequences') && ! Schema::hasColumn('document_number_sequences', 'sequence_group')) {
            Schema::table('document_number_sequences', function (Blueprint $table) {
                $table->string('sequence_group', 100)->nullable()->after('id');
            });
        }

        if (Schema::hasTable('document_numbers')) {
            if (! Schema::hasColumn('document_numbers', 'usage_source')) {
                Schema::table('document_numbers', function (Blueprint $table) {
                    $table->string('usage_source', 30)->nullable()->after('status');
                });
            }

            DB::table('document_numbers')
                ->whereIn('document_key', ['SPK', 'SPMK', 'BAPP', 'BAST', 'BAP'])
                ->whereNull('sequence_group')
                ->update(['sequence_group' => 'KONTRAK_PPK_BB_APTP']);

            DB::table('document_numbers')
                ->whereNull('usage_source')
                ->where('status', 'USED')
                ->update(['usage_source' => 'INTERNAL']);

            $this->detachDuplicateGlobalDocumentNumbers();

            Schema::table('document_numbers', function (Blueprint $table) {
                $table->unique(
                    ['sequence_group', 'tahun', 'running_number'],
                    'doc_numbers_group_year_run_unique'
                );
            });
        }

        if (Schema::hasTable('document_number_sequences')) {
            Schema::table('document_number_sequences', function (Blueprint $table) {
                $table->unique(
                    ['sequence_group', 'tahun'],
                    'doc_num_seq_group_year_unique'
                );
            });
        }
    }

    private function detachDuplicateGlobalDocumentNumbers(): void
    {
        $duplicates = DB::table('document_numbers')
            ->select(
                'sequence_group',
                'tahun',
                'running_number',
                DB::raw('MIN(id) as keep_id'),
                DB::raw('COUNT(*) as total')
            )
            ->whereNotNull('sequence_group')
            ->groupBy('sequence_group', 'tahun', 'running_number')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            $duplicateRows = DB::table('document_numbers')
                ->where('sequence_group', $duplicate->sequence_group)
                ->where('tahun', $duplicate->tahun)
                ->where('running_number', $duplicate->running_number)
                ->where('id', '!=', $duplicate->keep_id)
                ->orderBy('id')
                ->get(['id', 'notes']);

            foreach ($duplicateRows as $row) {
                $notes = trim((string) $row->notes);
                $legacyNote = 'Legacy duplicate sebelum sequence global; tetap dicatat agar nomor lama tidak dipakai ulang.';

                DB::table('document_numbers')
                    ->where('id', $row->id)
                    ->update([
                        'sequence_group' => null,
                        'notes' => $notes === '' ? $legacyNote : $notes . "\n" . $legacyNote,
                    ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('document_number_sequences')) {
            Schema::table('document_number_sequences', function (Blueprint $table) {
                $table->dropUnique('doc_num_seq_group_year_unique');
            });

            if (Schema::hasColumn('document_number_sequences', 'sequence_group')) {
                Schema::table('document_number_sequences', function (Blueprint $table) {
                    $table->dropColumn('sequence_group');
                });
            }
        }

        if (Schema::hasTable('document_numbers')) {
            Schema::table('document_numbers', function (Blueprint $table) {
                $table->dropUnique('doc_numbers_group_year_run_unique');
            });

            if (Schema::hasColumn('document_numbers', 'usage_source')) {
                Schema::table('document_numbers', function (Blueprint $table) {
                    $table->dropColumn('usage_source');
                });
            }
        }
    }
};
