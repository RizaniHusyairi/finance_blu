<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * BR-01 — backup database terotomasi + retensi.
 *
 * Mendukung MySQL/MariaDB (mysqldump → gzip) dan SQLite (salin → gzip).
 * Berkas disimpan di direktori privat (default storage/app/backups; dapat
 * diarahkan via env DB_BACKUP_PATH ke lokasi yang disinkronkan off-site).
 * Tidak memakai pipe shell sehingga aman lintas-platform (Windows & Linux).
 *
 * Lihat prosedur restore & DR: docs/RUNBOOK-BACKUP-RESTORE.md
 */
class BackupDatabaseCommand extends Command
{
    protected $signature = 'db:backup
                            {--keep=14 : Jumlah berkas backup terbaru yang dipertahankan}';

    protected $description = 'Backup database ke direktori privat dengan retensi (BR-01)';

    public function handle(): int
    {
        $connection = config('database.default');
        $cfg = config("database.connections.{$connection}");
        $driver = $cfg['driver'] ?? null;

        $dir = rtrim((string) env('DB_BACKUP_PATH', storage_path('app/backups')), '/\\');
        if (! is_dir($dir) && ! @mkdir($dir, 0750, true) && ! is_dir($dir)) {
            $this->error("Gagal membuat direktori backup: {$dir}");

            return self::FAILURE;
        }

        $stamp = now()->format('Y-m-d_His');

        try {
            $file = match ($driver) {
                'mysql', 'mariadb' => $this->backupMysql($cfg, $dir, $stamp),
                'sqlite' => $this->backupSqlite($cfg, $dir, $stamp),
                default => throw new \RuntimeException("Driver database '{$driver}' belum didukung command backup."),
            };
        } catch (\Throwable $e) {
            $this->error('Backup GAGAL: ' . $e->getMessage());
            Log::error('db:backup gagal', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        $sizeMb = is_file($file) ? round(filesize($file) / 1048576, 2) : 0;
        $this->info('Backup sukses: ' . basename($file) . " ({$sizeMb} MB)");

        $this->applyRetention($dir, (int) $this->option('keep'));

        return self::SUCCESS;
    }

    private function backupMysql(array $cfg, string $dir, string $stamp): string
    {
        $database = (string) ($cfg['database'] ?? '');
        if ($database === '') {
            throw new \RuntimeException('Nama database kosong pada konfigurasi.');
        }

        $sqlFile = $dir . DIRECTORY_SEPARATOR . "sikeren-{$database}-{$stamp}.sql";
        $gzFile = $sqlFile . '.gz';

        // Kredensial lewat defaults-extra-file — JANGAN taruh password di argumen
        // CLI (terlihat di daftar proses). File dihapus segera setelah dipakai.
        $credFile = tempnam(sys_get_temp_dir(), 'sikeren_my');
        file_put_contents($credFile, implode("\n", [
            '[client]',
            'host=' . ($cfg['host'] ?? '127.0.0.1'),
            'port=' . ($cfg['port'] ?? 3306),
            'user=' . ($cfg['username'] ?? 'root'),
            'password=' . ($cfg['password'] ?? ''),
            '',
        ]));
        @chmod($credFile, 0600);

        $mysqldump = (string) env('MYSQLDUMP_PATH', 'mysqldump');

        try {
            $result = Process::timeout(900)->run([
                $mysqldump,
                '--defaults-extra-file=' . $credFile,
                '--single-transaction',
                '--quick',
                '--routines',
                '--no-tablespaces',
                '--default-character-set=utf8mb4',
                '--result-file=' . $sqlFile,
                $database,
            ]);
        } finally {
            @unlink($credFile);
        }

        if (! $result->successful()) {
            @unlink($sqlFile);
            $msg = trim($result->errorOutput()) ?: trim($result->output()) ?: 'exit code ' . $result->exitCode();
            throw new \RuntimeException('mysqldump gagal: ' . $msg);
        }

        $this->gzipFile($sqlFile, $gzFile);
        @unlink($sqlFile);

        return $gzFile;
    }

    private function backupSqlite(array $cfg, string $dir, string $stamp): string
    {
        $src = (string) ($cfg['database'] ?? '');
        if (! is_file($src)) {
            throw new \RuntimeException("Berkas SQLite tidak ditemukan: {$src}");
        }

        $gzFile = $dir . DIRECTORY_SEPARATOR . "sikeren-sqlite-{$stamp}.sqlite.gz";
        $this->gzipFile($src, $gzFile);

        return $gzFile;
    }

    private function gzipFile(string $source, string $target): void
    {
        $in = fopen($source, 'rb');
        $out = gzopen($target, 'wb9');
        if ($in === false || $out === false) {
            if (is_resource($in)) {
                fclose($in);
            }
            if (is_resource($out)) {
                gzclose($out);
            }
            throw new \RuntimeException('Gagal membuka berkas untuk kompresi gzip.');
        }

        while (! feof($in)) {
            gzwrite($out, (string) fread($in, 1 << 20));
        }

        fclose($in);
        gzclose($out);
    }

    private function applyRetention(string $dir, int $keep): void
    {
        if ($keep <= 0) {
            return;
        }

        $files = glob($dir . DIRECTORY_SEPARATOR . 'sikeren-*.gz') ?: [];
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        foreach (array_slice($files, $keep) as $old) {
            @unlink($old);
            $this->line('Retensi: hapus ' . basename($old));
        }
    }
}
