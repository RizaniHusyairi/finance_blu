<?php

namespace Database\Seeders;

use App\Models\MasterCoa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

class MasterCoaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->extractRowsFromExcel() as $row) {
            MasterCoa::updateOrCreate(
                ['kode_mak_lengkap' => $row['kode_mak_lengkap']],
                $row
            );
        }
    }

    private function extractRowsFromExcel(): array
    {
        $excelPath = base_path('Dumy dat.xlsx');

        if (! File::exists($excelPath)) {
            throw new RuntimeException('File Excel seeder COA tidak ditemukan: ' . $excelPath);
        }

        $zip = new ZipArchive();
        if ($zip->open($excelPath) !== true) {
            throw new RuntimeException('File Excel COA tidak dapat dibuka.');
        }

        $sharedStrings = $this->readSharedStrings($zip);
        $sheetPath = $this->resolveSheetPath($zip, 'Pagu_BLU');
        $rows = $this->readSheetRows($zip, $sheetPath, $sharedStrings);

        $zip->close();

        if (count($rows) < 2) {
            return [];
        }

        $header = $rows[0];
        $records = [];

        foreach (array_slice($rows, 1) as $row) {
            $row = array_slice(array_pad($row, count($header), ''), 0, count($header));
            $record = array_combine($header, $row);

            $kodeMakLengkap = trim((string) ($record['coa'] ?? ''));
            $namaAkun = trim((string) ($record['uraian'] ?? ''));

            if ($kodeMakLengkap === '' || $namaAkun === '') {
                continue;
            }

            $kdAkun = $this->normalize($record['kd_akun'] ?? null);

            $records[] = [
                'kd_program' => $this->normalize($record['kd_program'] ?? null),
                'kd_giat' => $this->normalize($record['kd_giat'] ?? null),
                'kd_output' => $this->normalize($record['kd_output'] ?? null),
                'kd_suboutput' => $this->normalize($record['kd_suboutput'] ?? null),
                'kd_komponen' => $this->normalize($record['kd_komponen'] ?? null),
                'kd_subkomponen' => $this->normalize($record['kd_subkomponen'] ?? null),
                'kd_akun' => $kdAkun,
                'kd_item' => $this->normalize($record['kd_item'] ?? null),
                'kode_mak_lengkap' => $this->normalize($kodeMakLengkap),
                'nama_akun' => $namaAkun,
                'jenis_akun' => $kdAkun ? substr($kdAkun, 0, 3) : null,
                'status_aktif' => true,
            ];
        }

        return $records;
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $path = 'xl/sharedStrings.xml';
        if ($zip->locateName($path) === false) {
            return [];
        }

        $xml = simplexml_load_string($zip->getFromName($path));

        $strings = [];
        foreach ($xml->xpath('//*[local-name()="si"]') as $item) {
            $textParts = $item->xpath('.//*[local-name()="t"]');
            $strings[] = implode('', array_map(fn ($part) => (string) $part, $textParts));
        }

        return $strings;
    }

    private function resolveSheetPath(ZipArchive $zip, string $sheetName): string
    {
        $workbook = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
        $workbook->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $targetRelationshipId = null;
        foreach ($workbook->xpath('//main:sheets/main:sheet') as $sheet) {
            if ((string) $sheet['name'] === $sheetName) {
                $attributes = $sheet->attributes('r', true);
                $targetRelationshipId = (string) $attributes['id'];
                break;
            }
        }

        if (! $targetRelationshipId) {
            throw new RuntimeException('Sheet Excel tidak ditemukan: ' . $sheetName);
        }

        $rels = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));
        $rels->registerXPathNamespace('rel', 'http://schemas.openxmlformats.org/package/2006/relationships');

        foreach ($rels->xpath('//rel:Relationship') as $relationship) {
            if ((string) $relationship['Id'] === $targetRelationshipId) {
                $target = (string) $relationship['Target'];
                return str_starts_with($target, 'xl/') ? $target : 'xl/' . ltrim($target, '/');
            }
        }

        throw new RuntimeException('Relasi sheet Excel tidak ditemukan untuk: ' . $sheetName);
    }

    private function readSheetRows(ZipArchive $zip, string $sheetPath, array $sharedStrings): array
    {
        $sheet = simplexml_load_string($zip->getFromName($sheetPath));

        $rows = [];
        foreach ($sheet->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') as $row) {
            $values = [];
            foreach ($row->xpath('./*[local-name()="c"]') as $cell) {
                $type = (string) $cell['t'];
                $valueNode = $cell->xpath('./*[local-name()="v"]');
                $inlineNode = $cell->xpath('./*[local-name()="is"]/*[local-name()="t"]');

                if ($inlineNode) {
                    $values[] = (string) $inlineNode[0];
                    continue;
                }

                if (! $valueNode) {
                    $values[] = '';
                    continue;
                }

                $raw = (string) $valueNode[0];
                $values[] = $type === 's' ? ($sharedStrings[(int) $raw] ?? '') : $raw;
            }

            if (collect($values)->filter(fn ($value) => $value !== '')->isNotEmpty()) {
                $rows[] = $values;
            }
        }

        return $rows;
    }

    private function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(trim($value));

        return $normalized === '' ? null : $normalized;
    }
}
