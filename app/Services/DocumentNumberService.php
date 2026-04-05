<?php

namespace App\Services;

use App\Models\DocumentNumberSequence;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DocumentNumberService
{
    public function preview(string $seriesPrefix, string $suffixCode, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $seriesPrefix = trim($seriesPrefix);
        $suffixCode = trim($suffixCode);

        if ($seriesPrefix === '') {
            throw new InvalidArgumentException('Series prefix wajib diisi.');
        }

        if ($suffixCode === '') {
            throw new InvalidArgumentException('Suffix code wajib diisi.');
        }

        $sequence = DocumentNumberSequence::query()
            ->where('series_prefix', $seriesPrefix)
            ->where('suffix_code', $suffixCode)
            ->where('tahun', $year)
            ->first();

        $nextNumber = ((int) optional($sequence)->last_number) + 1;
        $padding = (int) (optional($sequence)->number_padding ?? config('document_numbers.default_padding', 4));

        return $this->formatNumber($seriesPrefix, $nextNumber, $suffixCode, $year, $padding);
    }

    public function generate(string $seriesPrefix, string $suffixCode, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $seriesPrefix = trim($seriesPrefix);
        $suffixCode = trim($suffixCode);

        if ($seriesPrefix === '') {
            throw new InvalidArgumentException('Series prefix wajib diisi.');
        }

        if ($suffixCode === '') {
            throw new InvalidArgumentException('Suffix code wajib diisi.');
        }

        return DB::transaction(function () use ($seriesPrefix, $suffixCode, $year) {
            $sequence = $this->lockSequence($seriesPrefix, $suffixCode, $year);

            if (! $sequence) {
                $this->createSequence($seriesPrefix, $suffixCode, $year);
                $sequence = $this->lockSequence($seriesPrefix, $suffixCode, $year);
            }

            $sequence->increment('last_number');
            $sequence->refresh();

            return $this->formatNumber(
                $sequence->series_prefix,
                $sequence->last_number,
                $sequence->suffix_code,
                (int) $sequence->tahun,
                (int) $sequence->number_padding
            );
        });
    }

    public function generateByKey(string $documentKey, ?int $year = null): string
    {
        $config = config('document_numbers.documents.' . strtoupper($documentKey));

        if (! $config) {
            throw new InvalidArgumentException('Konfigurasi nomor dokumen untuk key ' . $documentKey . ' tidak ditemukan.');
        }

        return $this->generate(
            $config['series_prefix'],
            $config['suffix_code'],
            $year
        );
    }

    public function previewByKey(string $documentKey, ?int $year = null): string
    {
        $config = config('document_numbers.documents.' . strtoupper($documentKey));

        if (! $config) {
            throw new InvalidArgumentException('Konfigurasi nomor dokumen untuk key ' . $documentKey . ' tidak ditemukan.');
        }

        return $this->preview(
            $config['series_prefix'],
            $config['suffix_code'],
            $year
        );
    }

    public function formatNumber(
        string $seriesPrefix,
        int $runningNumber,
        string $suffixCode,
        int $year,
        int $padding = 4
    ): string {
        return sprintf(
            '%s/%s/%s/%s',
            trim($seriesPrefix),
            str_pad((string) $runningNumber, $padding, '0', STR_PAD_LEFT),
            trim($suffixCode),
            $year
        );
    }

    protected function lockSequence(string $seriesPrefix, string $suffixCode, int $year): ?DocumentNumberSequence
    {
        return DocumentNumberSequence::query()
            ->where('series_prefix', $seriesPrefix)
            ->where('suffix_code', $suffixCode)
            ->where('tahun', $year)
            ->lockForUpdate()
            ->first();
    }

    protected function createSequence(string $seriesPrefix, string $suffixCode, int $year): void
    {
        try {
            DocumentNumberSequence::create([
                'series_prefix' => $seriesPrefix,
                'suffix_code' => $suffixCode,
                'tahun' => $year,
                'last_number' => 0,
                'number_padding' => (int) config('document_numbers.default_padding', 4),
                'is_active' => true,
            ]);
        } catch (QueryException $exception) {
            // Sequence bisa saja sudah dibuat oleh transaksi lain.
        }
    }
}
