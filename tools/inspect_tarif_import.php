<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo App\Models\JenisLayanan::count() . ' jenis, '
    . App\Models\KategoriLayanan::count() . ' kategori, '
    . App\Models\ItemTarifLayanan::count() . ' item' . PHP_EOL;

$jenis = App\Models\JenisLayanan::with('kategoriLayanan.itemTarifLayanan')
    ->orderBy('urutan')
    ->first();

if (! $jenis) {
    exit;
}

echo $jenis->nama_jenis . PHP_EOL;
foreach ($jenis->kategoriLayanan->take(8) as $kategori) {
    echo '- ' . $kategori->nama_kategori . ' (' . $kategori->itemTarifLayanan->count() . ' item)' . PHP_EOL;
    foreach ($kategori->itemTarifLayanan->take(3) as $item) {
        echo '  * ' . $item->nama_item
            . ' | billable=' . (int) $item->is_billable
            . ' | ' . ($item->satuan ?? '-')
            . PHP_EOL;
    }
}
