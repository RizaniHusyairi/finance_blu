<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = __DIR__ . '/../outputs/klasifikasi_layanan_jasa_blu_upbu_upbu_apt_pranoto.xlsx';
$spreadsheet = IOFactory::load($file);

echo "RINGKASAN\n";
foreach ($spreadsheet->getSheetByName('Ringkasan')->toArray() as $row) {
    echo implode(' | ', array_map(static fn ($value) => $value ?? '', $row)) . PHP_EOL;
}

echo PHP_EOL . "CONTOH 20 BARIS\n";
$sheet = $spreadsheet->getSheetByName('Klasifikasi Layanan');
for ($row = 1; $row <= 20; $row++) {
    $values = $sheet->rangeToArray("A{$row}:I{$row}")[0];
    echo implode(' | ', array_map(static fn ($value) => $value ?? '', $values)) . PHP_EOL;
}

echo PHP_EOL . "PERLU VERIFIKASI\n";
foreach ($sheet->toArray() as $idx => $row) {
    if (($row[3] ?? '') === 'Perlu Verifikasi') {
        echo ($idx + 1) . ' | ' . ($row[1] ?? '') . ' | ' . ($row[5] ?? '') . ' | ' . ($row[9] ?? '') . PHP_EOL;
    }
}
