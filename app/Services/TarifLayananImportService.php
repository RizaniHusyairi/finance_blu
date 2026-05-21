<?php

namespace App\Services;

use App\Models\ItemTarifLayanan;
use App\Models\JenisLayanan;
use App\Models\KategoriLayanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TarifLayananImportService
{
    /**
     * Import Excel tarif layanan BLU/UPBU menjadi struktur:
     * Jenis Layanan -> Kategori Layanan -> Item Tarif Layanan.
     *
     * @return array{jenis:int,kategori:int,item:int,tidak_billable:int,perlu_verifikasi:int}
     */
    public function import(string $filePath, ?string $sheetName = null, bool $truncate = true): array
    {
        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("File Excel tidak ditemukan: {$filePath}");
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $sheetName
            ? $spreadsheet->getSheetByName($sheetName)
            : $spreadsheet->getActiveSheet();

        if (! $sheet instanceof Worksheet) {
            throw new \InvalidArgumentException("Sheet Excel tidak ditemukan: {$sheetName}");
        }

        $headers = $this->resolveHeaders($sheet);
        $rows = $this->readRows($sheet, $headers);

        return DB::transaction(function () use ($rows, $truncate) {
            if ($truncate) {
                ItemTarifLayanan::query()->delete();
                KategoriLayanan::query()->delete();
                JenisLayanan::query()->delete();
            }

            $stats = [
                'jenis' => 0,
                'kategori' => 0,
                'item' => 0,
                'tidak_billable' => 0,
                'perlu_verifikasi' => 0,
            ];

            $currentJenis = null;
            $currentKategori = null;

            foreach ($rows as $index => $row) {
                $nextRow = $rows[$index + 1] ?? null;
                $kdlevel = (int) $row['kdlevel'];
                $nama = $this->normalizeNameForLevel($row['nmitem'], $kdlevel);

                if ($nama === '' || ! in_array($kdlevel, [1, 2, 3], true)) {
                    continue;
                }

                if ($kdlevel === 1) {
                    $currentJenis = JenisLayanan::create([
                        'kode_jenis' => $this->makeCode('JNS', $stats['jenis'] + 1),
                        'nama_jenis' => $nama,
                        'urutan' => $row['excel_row'],
                        'status_aktif' => true,
                    ]);
                    $currentKategori = null;
                    $stats['jenis']++;
                    continue;
                }

                if ($kdlevel === 2) {
                    if (! $currentJenis) {
                        $stats['perlu_verifikasi']++;
                        continue;
                    }

                    $currentKategori = KategoriLayanan::create([
                        'jenis_layanan_id' => $currentJenis->id,
                        'kode_kategori' => $this->makeCode('KTG', $currentJenis->id, $stats['kategori'] + 1),
                        'nama_kategori' => $nama,
                        'urutan' => $row['excel_row'],
                        'status_aktif' => true,
                    ]);
                    $stats['kategori']++;

                    $hasChildLevel3 = $nextRow && (int) $nextRow['kdlevel'] === 3;
                    if (! $hasChildLevel3 && $this->hasTariffInformation($row)) {
                        $this->createItem($currentJenis, $currentKategori, $row, $stats, 2);
                    }

                    continue;
                }

                if ($kdlevel === 3) {
                    if (! $currentJenis || ! $currentKategori) {
                        $stats['perlu_verifikasi']++;
                        continue;
                    }

                    $this->createItem($currentJenis, $currentKategori, $row, $stats, 3);
                }
            }

            return $stats;
        });
    }

    private function createItem(JenisLayanan $jenis, KategoriLayanan $kategori, array $row, array &$stats, int $kdlevel): ItemTarifLayanan
    {
        $isBillable = $this->hasTariffInformation($row);

        if (! $isBillable) {
            $stats['tidak_billable']++;
        }

        $stats['item']++;

        return ItemTarifLayanan::create([
            'jenis_layanan_id' => $jenis->id,
            'kategori_layanan_id' => $kategori->id,
            'kode_item' => $this->makeCode('ITM', $kategori->id, $stats['item']),
            'nama_item' => $this->normalizeNameForLevel($row['nmitem'], $kdlevel),
            'kdlevel' => $kdlevel,
            'kdmak' => $this->nullableText($row['kdmak']),
            'kdakunplus' => $this->nullableText($row['kdakunplus']),
            'satuan' => $this->nullableText($row['satuan']),
            'kdmu' => $this->nullableText($row['kdmu']),
            'nmmu' => $this->nullableText($row['nmmu']),
            'tarif' => $this->normalizeNumber($row['tarif']),
            'is_billable' => $isBillable,
            'sumber_excel_row' => $row['excel_row'],
            'urutan' => $row['excel_row'],
            'status_aktif' => true,
        ]);
    }

    private function resolveHeaders(Worksheet $sheet): array
    {
        $highestColumn = $sheet->getHighestColumn();
        $headerMap = [];

        foreach ($sheet->rangeToArray("A1:{$highestColumn}1", null, true, true, true)[1] as $column => $header) {
            $normalized = $this->normalizeHeader((string) $header);
            if ($normalized !== '') {
                $headerMap[$normalized] = $column;
            }
        }

        $aliases = [
            'kdlevel' => ['kdlevel', 'kdlvl', 'level'],
            'nmitem' => ['nmitem', 'jenislayanan', 'jenis layanan', 'namaitem', 'nama item'],
            'kdmak' => ['kdmak', 'kode mak'],
            'kdakunplus' => ['kdakunplus', 'kdakun', 'kodeakunplus', 'kode akun plus', 'akunplus'],
            'satuan' => ['satuan', 'unit'],
            'kdmu' => ['kdmu', 'kodematauang', 'kode mata uang'],
            'nmmu' => ['nmmu', 'nmmatauang', 'nama mata uang', 'matauang', 'mata uang'],
            'tarif' => ['tarif', 'tarifmax', 'tarifbaru', 'nilai', 'nilaitarif'],
        ];

        $resolved = [];
        foreach ($aliases as $field => $candidates) {
            foreach ($candidates as $candidate) {
                $key = $this->normalizeHeader($candidate);
                if (isset($headerMap[$key])) {
                    $resolved[$field] = $headerMap[$key];
                    break;
                }
            }
        }

        $fallbacks = [
            'kdlevel' => 'A',
            'nmitem' => 'B',
            'kdmak' => 'C',
            'satuan' => 'D',
            'kdmu' => 'E',
            'nmmu' => 'F',
            'tarif' => 'H',
            'kdakunplus' => 'L',
        ];

        return array_merge($fallbacks, $resolved);
    }

    private function readRows(Worksheet $sheet, array $headers): array
    {
        $rows = [];
        $highestRow = $sheet->getHighestRow();

        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            $kdlevel = $this->cleanText($sheet->getCell($headers['kdlevel'] . $rowNumber)->getCalculatedValue());
            $nmitem = $this->cleanText($sheet->getCell($headers['nmitem'] . $rowNumber)->getCalculatedValue());

            if ($kdlevel === '' && $nmitem === '') {
                continue;
            }

            $rows[] = [
                'excel_row' => $rowNumber,
                'kdlevel' => is_numeric($kdlevel) ? (int) $kdlevel : 0,
                'nmitem' => $nmitem,
                'kdmak' => $this->cleanText($sheet->getCell($headers['kdmak'] . $rowNumber)->getCalculatedValue()),
                'kdakunplus' => $this->cleanText($sheet->getCell($headers['kdakunplus'] . $rowNumber)->getCalculatedValue()),
                'satuan' => $this->cleanText($sheet->getCell($headers['satuan'] . $rowNumber)->getCalculatedValue()),
                'kdmu' => $this->cleanText($sheet->getCell($headers['kdmu'] . $rowNumber)->getCalculatedValue()),
                'nmmu' => $this->cleanText($sheet->getCell($headers['nmmu'] . $rowNumber)->getCalculatedValue()),
                'tarif' => $sheet->getCell($headers['tarif'] . $rowNumber)->getCalculatedValue(),
            ];
        }

        return $rows;
    }

    private function hasTariffInformation(array $row): bool
    {
        return $this->normalizeNumber($row['tarif']) !== null
            || $this->nullableText($row['satuan']) !== null
            || $this->nullableText($row['kdmak']) !== null
            || $this->nullableText($row['kdakunplus']) !== null
            || $this->nullableText($row['kdmu']) !== null
            || $this->nullableText($row['nmmu']) !== null;
    }

    private function normalizeHeader(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();
    }

    private function cleanText(mixed $value): string
    {
        $value = str_replace("\xc2\xa0", ' ', (string) $value);
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private function normalizeNameForLevel(mixed $value, int $kdlevel): string
    {
        $value = $this->cleanText($value);

        if ($kdlevel === 1) {
            return $value;
        }

        $value = preg_replace('/^\d+[\.\)]\s*/u', '', $value);
        $value = preg_replace('/^[a-z][\.\)]\s*/iu', '', $value);

        return trim((string) $value);
    }

    private function nullableText(mixed $value): ?string
    {
        $value = $this->cleanText($value);
        return $value === '' ? null : $value;
    }

    private function normalizeNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = preg_replace('/[^0-9,\.-]/', '', (string) $value);
        if ($clean === '' || $clean === null) {
            return null;
        }

        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? (float) $clean : null;
    }

    private function makeCode(string $prefix, int ...$parts): string
    {
        return $prefix . '-' . implode('-', array_map(
            static fn (int $part) => str_pad((string) $part, 4, '0', STR_PAD_LEFT),
            $parts
        ));
    }
}
