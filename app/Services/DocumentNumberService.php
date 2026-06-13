<?php

namespace App\Services;

use App\Models\DocumentNumber;
use App\Models\DocumentNumberSequence;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DocumentNumberService
{
    private const GLOBAL_SEQUENCE_SUFFIX = '__GLOBAL__';

    public function preview(string $seriesPrefix, string $suffixCode, ?int $year = null, int $offset = 0): string
    {
        $year ??= (int) now()->format('Y');
        $config = $this->inlineConfig($seriesPrefix, $suffixCode);

        return $this->previewFromConfig($config, $year, $offset);
    }

    public function generate(string $seriesPrefix, string $suffixCode, ?int $year = null, ?string $documentKey = null): string
    {
        $year ??= (int) now()->format('Y');
        $config = $documentKey
            ? $this->documentConfig($documentKey)
            : $this->inlineConfig($seriesPrefix, $suffixCode);

        return $this->generateFromConfig($config, $year);
    }

    public function generateByKey(string $documentKey, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        return $this->generateFromConfig($this->documentConfig($documentKey), $year);
    }

    public function previewByKey(string $documentKey, ?int $year = null, int $offset = 0): string
    {
        $year ??= (int) now()->format('Y');

        return $this->previewFromConfig($this->documentConfig($documentKey), $year, $offset);
    }

    /** Nomor urut berikutnya yang belum terpakai (untuk prefill input manual). */
    public function nextRunningNumberByKey(string $documentKey, ?int $year = null): int
    {
        $year ??= (int) now()->format('Y');
        $config = $this->documentConfig($documentKey);

        return $this->nextUnusedRunningNumber($config, $year, $this->currentMaxRunningNumber($config, $year) + 1);
    }

    /**
     * Pakai nomor urut pilihan user (input manual) untuk sebuah dokumen.
     * Transaksional: menolak bila nomor sudah pernah tercatat pada
     * sequence group yang sama (lintas tipe dokumen segrup).
     */
    public function generateByKeyWithNumber(string $documentKey, int $runningNumber, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $config = $this->documentConfig($documentKey);

        return DB::transaction(function () use ($config, $year, $runningNumber) {
            $sequence = $this->lockOrCreateSequence($config, $year);
            $this->syncSequenceLastNumber($sequence, $config, $year);

            if ($runningNumber < 1 || $runningNumber > 9999) {
                throw new InvalidArgumentException('Nomor urut harus berada di antara 0001 sampai 9999.');
            }

            if ($this->runningNumberExists($config, $year, $runningNumber)) {
                throw new InvalidArgumentException(
                    'Nomor urut ' . $this->padRunningNumber($runningNumber, $config) . ' sudah pernah digunakan pada tahun ' . $year . '. Pilih nomor lain.'
                );
            }

            $number = $this->createNumberRecord(
                $config,
                $year,
                $runningNumber,
                DocumentNumber::SOURCE_INTERNAL,
                'Nomor urut dipilih manual oleh pengguna saat membuat dokumen.'
            );

            if ($runningNumber > $sequence->last_number) {
                $sequence->update(['last_number' => $runningNumber]);
            }

            return $number->full_number;
        });
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

    public function documentConfig(string $documentKey, array $overrides = []): array
    {
        $documentKey = strtoupper(trim($documentKey));
        $config = config('document_numbers.documents.' . $documentKey);

        if (! $config) {
            throw new InvalidArgumentException('Konfigurasi nomor dokumen untuk key ' . $documentKey . ' tidak ditemukan.');
        }

        $merged = array_merge($config, [
            'document_key' => $documentKey,
            'sequence_group' => $config['sequence_group'] ?? config('document_numbers.default_sequence_group'),
            'number_padding' => (int) ($config['number_padding'] ?? config('document_numbers.default_padding', 4)),
        ]);

        // Override khusus (mis. LAINNYA dengan custom prefix dari user)
        if (! empty($overrides['series_prefix'])) {
            $customPrefix = trim($overrides['series_prefix']);
            $merged['series_prefix'] = $customPrefix;
            // Setiap custom prefix punya sequence_group sendiri agar nomor tidak bercampur
            $merged['sequence_group'] = ($overrides['sequence_group'] ?? null)
                ?: $documentKey . '|' . $customPrefix;
        }

        return $merged;
    }

    public function reserveByKey(
        string $documentKey,
        ?int $year = null,
        ?int $runningNumber = null,
        ?string $notes = null
    ): DocumentNumber {
        $year ??= (int) now()->format('Y');
        $config = $this->documentConfig($documentKey);

        return DB::transaction(function () use ($config, $year, $runningNumber, $notes) {
            $sequence = $this->lockOrCreateSequence($config, $year);
            $this->syncSequenceLastNumber($sequence, $config, $year);

            $runningNumber ??= $this->nextUnusedRunningNumber($config, $year, (int) $sequence->last_number + 1);

            if ($runningNumber < 1 || $runningNumber > 9999) {
                throw new InvalidArgumentException('Nomor urut harus berada di antara 0001 sampai 9999.');
            }

            if ($this->runningNumberExists($config, $year, $runningNumber)) {
                throw new InvalidArgumentException('Nomor urut ' . $this->padRunningNumber($runningNumber, $config) . ' sudah pernah dicatat.');
            }

            $number = $this->createNumberRecord(
                $config,
                $year,
                $runningNumber,
                DocumentNumber::SOURCE_EXTERNAL,
                $notes ?: 'Nomor dicatat sebagai penggunaan eksternal/request dari luar sistem.'
            );

            if ($runningNumber > $sequence->last_number) {
                $sequence->update(['last_number' => $runningNumber]);
            }

            return $number->refresh();
        });
    }

    public function checkNumberExists(string $documentKey, int $year, int $runningNumber, array $overrides = []): bool
    {
        $config = $this->documentConfig($documentKey, $overrides);
        return $this->runningNumberExists($config, $year, $runningNumber);
    }

    public function createAvailableRange(string $documentKey, int $year, ?int $startNumber, int $count, ?string $notes = null, array $overrides = []): array
    {
        if ($count < 1 || $count > 100) {
            throw new InvalidArgumentException('Jumlah nomor harus antara 1 sampai 100.');
        }

        $config = $this->documentConfig($documentKey, $overrides);

        return DB::transaction(function () use ($config, $year, $startNumber, $count, $notes) {
            $sequence = $this->lockOrCreateSequence($config, $year);
            $this->syncSequenceLastNumber($sequence, $config, $year);

            $current = $startNumber ?: ((int) $sequence->last_number + 1);
            $created = [];

            while (count($created) < $count) {
                if (! $this->runningNumberExists($config, $year, $current)) {
                    $created[] = $this->createNumberRecord(
                        $config,
                        $year,
                        $current,
                        DocumentNumber::SOURCE_EXTERNAL,
                        $notes ?: 'Nomor dicatat sebagai penggunaan eksternal/request dari luar sistem.'
                    );
                }

                $current++;
            }

            $maxCreatedNumber = collect($created)->max('running_number');
            if ($maxCreatedNumber && $maxCreatedNumber > $sequence->last_number) {
                $sequence->update(['last_number' => $maxCreatedNumber]);
            }

            return $created;
        });
    }

    protected function generateFromConfig(array $config, int $year): string
    {
        return DB::transaction(function () use ($config, $year) {
            $sequence = $this->lockOrCreateSequence($config, $year);
            $this->syncSequenceLastNumber($sequence, $config, $year);

            do {
                $sequence->increment('last_number');
                $sequence->refresh();

                $runningNumber = (int) $sequence->last_number;
            } while ($this->runningNumberExists($config, $year, $runningNumber));

            $number = $this->createNumberRecord(
                $config,
                $year,
                $runningNumber,
                DocumentNumber::SOURCE_INTERNAL,
                'Digenerate otomatis oleh sistem.'
            );

            return $number->full_number;
        });
    }

    protected function previewFromConfig(array $config, int $year, int $offset = 0): string
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Offset preview tidak boleh negatif.');
        }

        $runningNumber = $this->nextUnusedRunningNumber(
            $config,
            $year,
            $this->currentMaxRunningNumber($config, $year) + 1,
            $offset
        );

        return $this->formatNumber(
            $config['series_prefix'],
            $runningNumber,
            $config['suffix_code'],
            $year,
            $config['number_padding']
        );
    }

    protected function lockOrCreateSequence(array $config, int $year): DocumentNumberSequence
    {
        $sequence = $this->lockSequence($config, $year);

        if ($sequence) {
            return $sequence;
        }

        $this->createSequence($config, $year);

        $sequence = $this->lockSequence($config, $year);

        if (! $sequence) {
            throw new InvalidArgumentException('Sequence nomor dokumen gagal dibuat.');
        }

        return $sequence;
    }

    protected function lockSequence(array $config, int $year): ?DocumentNumberSequence
    {
        $identity = $this->sequenceIdentity($config);

        return DocumentNumberSequence::query()
            ->where('tahun', $year)
            ->where('sequence_group', $identity['sequence_group'])
            ->lockForUpdate()
            ->first();
    }

    protected function createSequence(array $config, int $year): void
    {
        $identity = $this->sequenceIdentity($config);

        try {
            DocumentNumberSequence::create([
                'sequence_group' => $identity['sequence_group'],
                'series_prefix' => $identity['series_prefix'],
                'suffix_code' => $identity['suffix_code'],
                'tahun' => $year,
                'last_number' => $this->currentMaxRunningNumber($config, $year),
                'number_padding' => $config['number_padding'],
                'is_active' => true,
                'keterangan' => 'Sequence global nomor dokumen pengadaan.',
            ]);
        } catch (QueryException $exception) {
            // Sequence bisa saja sudah dibuat oleh transaksi lain.
        }
    }

    protected function syncSequenceLastNumber(DocumentNumberSequence $sequence, array $config, int $year): void
    {
        $maxKnown = $this->currentMaxRunningNumber($config, $year);

        if ($maxKnown > $sequence->last_number) {
            $sequence->update(['last_number' => $maxKnown]);
            $sequence->refresh();
        }
    }

    protected function currentMaxRunningNumber(array $config, int $year): int
    {
        $documentMax = (int) (DocumentNumber::withTrashed()
            ->where('tahun', $year)
            ->where($this->groupScope($config))
            ->max('running_number') ?? 0);

        $sequenceMax = (int) (DocumentNumberSequence::query()
            ->where('tahun', $year)
            ->where(function ($query) use ($config) {
                $query->where('sequence_group', $config['sequence_group']);

                foreach ($this->configsInSameGroup($config) as $groupConfig) {
                    $query->orWhere(function ($nested) use ($groupConfig) {
                        $nested->where('series_prefix', $groupConfig['series_prefix'])
                            ->where('suffix_code', $groupConfig['suffix_code']);
                    });
                }
            })
            ->max('last_number') ?? 0);

        return max($documentMax, $sequenceMax);
    }

    protected function nextUnusedRunningNumber(array $config, int $year, int $startNumber, int $offset = 0): int
    {
        $current = max(1, $startNumber);
        $found = 0;

        while (true) {
            if (! $this->runningNumberExists($config, $year, $current)) {
                if ($found === $offset) {
                    return $current;
                }

                $found++;
            }

            $current++;
        }
    }

    protected function runningNumberExists(array $config, int $year, int $runningNumber): bool
    {
        return DocumentNumber::withTrashed()
            ->where('tahun', $year)
            ->where('running_number', $runningNumber)
            ->where($this->groupScope($config))
            ->exists();
    }

    protected function createNumberRecord(array $config, int $year, int $runningNumber, string $source, string $notes): DocumentNumber
    {
        $fullNumber = $this->formatNumber(
            $config['series_prefix'],
            $runningNumber,
            $config['suffix_code'],
            $year,
            $config['number_padding']
        );

        return DocumentNumber::create([
            'document_key' => $config['document_key'],
            'sequence_group' => $config['sequence_group'],
            'series_prefix' => $config['series_prefix'],
            'suffix_code' => $config['suffix_code'],
            'tahun' => $year,
            'running_number' => $runningNumber,
            'number_padding' => $config['number_padding'],
            'full_number' => $fullNumber,
            'status' => DocumentNumber::STATUS_USED,
            'usage_source' => $source,
            'used_by' => auth()->id(),
            'used_at' => now(),
            'notes' => $notes,
        ]);
    }

    protected function groupScope(array $config): callable
    {
        return function ($query) use ($config) {
            $query->where('sequence_group', $config['sequence_group'])
                ->orWhereIn('document_key', $this->documentKeysInSameGroup($config));
        };
    }

    protected function sequenceIdentity(array $config): array
    {
        if (! empty($config['sequence_group'])) {
            return [
                'sequence_group' => $config['sequence_group'],
                'series_prefix' => $config['sequence_group'],
                'suffix_code' => self::GLOBAL_SEQUENCE_SUFFIX,
            ];
        }

        return [
            'sequence_group' => $config['series_prefix'] . '|' . $config['suffix_code'],
            'series_prefix' => $config['series_prefix'],
            'suffix_code' => $config['suffix_code'],
        ];
    }

    protected function inlineConfig(string $seriesPrefix, string $suffixCode): array
    {
        $seriesPrefix = trim($seriesPrefix);
        $suffixCode = trim($suffixCode);

        if ($seriesPrefix === '') {
            throw new InvalidArgumentException('Series prefix wajib diisi.');
        }

        if ($suffixCode === '') {
            throw new InvalidArgumentException('Suffix code wajib diisi.');
        }

        return [
            'document_key' => 'CUSTOM',
            'sequence_group' => $seriesPrefix . '|' . $suffixCode,
            'series_prefix' => $seriesPrefix,
            'suffix_code' => $suffixCode,
            'number_padding' => (int) config('document_numbers.default_padding', 4),
        ];
    }

    protected function padRunningNumber(int $runningNumber, array $config): string
    {
        return str_pad((string) $runningNumber, $config['number_padding'], '0', STR_PAD_LEFT);
    }

    protected function documentKeysInSameGroup(array $config): array
    {
        return array_keys($this->configsInSameGroup($config));
    }

    protected function configsInSameGroup(array $config): array
    {
        $documents = config('document_numbers.documents', []);
        $group = $config['sequence_group'] ?? null;

        return collect($documents)
            ->filter(fn ($documentConfig) => ($documentConfig['sequence_group'] ?? config('document_numbers.default_sequence_group')) === $group)
            ->map(function ($documentConfig, $documentKey) {
                return array_merge($documentConfig, [
                    'document_key' => $documentKey,
                    'sequence_group' => $documentConfig['sequence_group'] ?? config('document_numbers.default_sequence_group'),
                    'number_padding' => (int) ($documentConfig['number_padding'] ?? config('document_numbers.default_padding', 4)),
                ]);
            })
            ->all();
    }
}
