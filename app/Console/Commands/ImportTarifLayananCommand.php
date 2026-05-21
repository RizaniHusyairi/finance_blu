<?php

namespace App\Console\Commands;

use App\Services\TarifLayananImportService;
use Illuminate\Console\Command;

class ImportTarifLayananCommand extends Command
{
    protected $signature = 'tarif-layanan:import
        {file : Path file Excel tarif layanan}
        {--sheet= : Nama sheet yang akan diimpor}
        {--append : Tambahkan data tanpa menghapus master lama}';

    protected $description = 'Import Excel tarif layanan BLU/UPBU ke master jenis, kategori, dan item tarif layanan.';

    public function handle(TarifLayananImportService $service): int
    {
        $path = $this->argument('file');
        $fullPath = $this->resolvePath($path);

        $this->info('Memulai import tarif layanan...');
        $this->line('File  : ' . $fullPath);
        $this->line('Sheet : ' . ($this->option('sheet') ?: 'aktif/default'));

        try {
            $stats = $service->import(
                $fullPath,
                $this->option('sheet') ?: null,
                ! (bool) $this->option('append')
            );
        } catch (\Throwable $e) {
            $this->error('Import gagal: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Import tarif layanan selesai.');
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Jenis layanan berhasil dibuat', $stats['jenis']],
                ['Kategori berhasil dibuat', $stats['kategori']],
                ['Item tarif berhasil dibuat', $stats['item']],
                ['Item tidak billable', $stats['tidak_billable']],
                ['Item perlu verifikasi', $stats['perlu_verifikasi']],
            ]
        );

        return self::SUCCESS;
    }

    private function resolvePath(string $path): string
    {
        if (file_exists($path)) {
            return $path;
        }

        $basePath = base_path($path);
        if (file_exists($basePath)) {
            return $basePath;
        }

        $storagePath = storage_path('app/' . ltrim($path, '/\\'));
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        return $path;
    }
}
