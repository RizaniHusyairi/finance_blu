<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = \App\Models\PotonganTagihan::where('jenis_potongan', 'PAJAK')
    ->whereHas('tagihan', fn ($q) => $q->where('tipe_tagihan', 'HONORARIUM'))
    ->with('tagihan')
    ->get();

$lines = ['PAJAK honor rows: '.$rows->count()];
foreach ($rows as $p) {
    $lines[] = 'pot='.$p->id.' tagihan='.($p->tagihan?->nomor_tagihan).' status='.($p->tagihan?->status).' nominal='.$p->nominal_potongan;
}
file_put_contents(__DIR__.'/storage/_diag_out.txt', implode("\n", $lines)."\n");
