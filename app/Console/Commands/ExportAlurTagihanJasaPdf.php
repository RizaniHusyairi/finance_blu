<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

class ExportAlurTagihanJasaPdf extends Command
{
    protected $signature = 'docs:export-alur-tagihan-jasa
                            {--out=docs/alur-tagihan-jasa/Alur-Tagihan-Jasa.pdf : Path output relatif ke base_path}';

    protected $description = 'Generate PDF dokumentasi alur Tagihan Jasa ke Mitra.';

    public function handle(): int
    {
        $outRel = (string) $this->option('out');
        $out = base_path($outRel);

        @mkdir(dirname($out), 0755, true);

        $pdf = Pdf::loadView('docs.alur-tagihan-jasa-pdf')
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'chroot' => base_path(),
            ]);

        file_put_contents($out, $pdf->output());

        $this->info('PDF berhasil dibuat: ' . $out);
        $this->line('Ukuran: ' . number_format(filesize($out) / 1024, 1) . ' KB');

        return self::SUCCESS;
    }
}
