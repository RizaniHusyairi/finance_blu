<?php

namespace App\Support;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * MON-03 — observability kesehatan sistem yang lebih dalam.
 *
 * Mengumpulkan sejumlah probe (DB, cache, antrian, storage, disk, backup,
 * heartbeat scheduler, laju error log, keterjangkauan integrasi eksternal)
 * menjadi satu laporan terstruktur. Dipakai bersama oleh:
 *   - endpoint HTTP `/health` (untuk monitor uptime/observability eksternal)
 *   - command `monitor:health` (alerting terjadwal: log critical + WhatsApp)
 *
 * Setiap probe ter-isolasi (try/catch): satu probe gagal tidak menggagalkan
 * yang lain, dan exception apa pun dipetakan ke status `critical` — bukan
 * melempar. Tingkat status: `ok` < `warn` (degraded) < `critical` (down).
 */
class HealthCheck
{
    /** Probe yang menandakan aplikasi TIDAK dapat melayani → HTTP 503. */
    private const HARD = ['database', 'cache', 'storage', 'disk'];

    /**
     * Jalankan seluruh probe dan kembalikan laporan terstruktur.
     *
     * @return array{status:string,checked_at:string,summary:array,checks:array}
     */
    public function run(): array
    {
        $checks = [
            $this->check('database', 'Database', fn () => $this->database()),
            $this->check('cache', 'Cache', fn () => $this->cache()),
            $this->check('storage', 'Storage', fn () => $this->storage()),
            $this->check('disk', 'Ruang disk', fn () => $this->disk()),
            $this->check('queue_failed', 'Antrian (failed jobs)', fn () => $this->queueFailed()),
            $this->check('queue_backlog', 'Antrian (backlog)', fn () => $this->queueBacklog()),
            $this->check('scheduler', 'Scheduler heartbeat', fn () => $this->scheduler()),
            $this->check('backup', 'Backup database', fn () => $this->backup()),
            $this->check('error_rate', 'Laju error (log)', fn () => $this->errorRate()),
            $this->check('integrations', 'Integrasi eksternal', fn () => $this->integrations()),
        ];

        $hasCritical = collect($checks)->contains('status', 'critical');
        $hasWarn = collect($checks)->contains('status', 'warn');
        $status = $hasCritical ? 'down' : ($hasWarn ? 'degraded' : 'ok');

        return [
            'status' => $status,
            'checked_at' => now()->toIso8601String(),
            'summary' => [
                'ok' => collect($checks)->where('status', 'ok')->count(),
                'warn' => collect($checks)->where('status', 'warn')->count(),
                'critical' => collect($checks)->where('status', 'critical')->count(),
            ],
            'checks' => $checks,
        ];
    }

    /** HTTP status: 503 hanya bila ada probe HARD yang critical. */
    public function httpStatusFor(array $report): int
    {
        return $report['status'] === 'down' ? 503 : 200;
    }

    private function check(string $key, string $label, callable $fn): array
    {
        $start = microtime(true);
        try {
            [$status, $detail, $value] = $fn();
        } catch (\Throwable $e) {
            // Probe non-HARD yang error tidak boleh menjatuhkan sistem ke "down".
            $status = in_array($key, self::HARD, true) ? 'critical' : 'warn';
            $detail = 'Probe gagal: ' . $e->getMessage();
            $value = null;
        }

        return [
            'key' => $key,
            'label' => $label,
            'status' => $status,
            'detail' => $detail,
            'value' => $value,
            'latency_ms' => (int) round((microtime(true) - $start) * 1000),
        ];
    }

    private function database(): array
    {
        DB::select('select 1');

        return ['ok', 'Koneksi database OK (' . DB::getDefaultConnection() . ').', null];
    }

    private function cache(): array
    {
        $token = (string) now()->getTimestamp() . ':' . bin2hex(random_bytes(4));
        Cache::put('health:probe', $token, 30);

        return Cache::get('health:probe') === $token
            ? ['ok', 'Cache tulis/baca OK.', null]
            : ['critical', 'Cache tidak konsisten saat tulis/baca.', null];
    }

    private function storage(): array
    {
        foreach (['local' => storage_path('app'), 'public' => storage_path('app/public')] as $name => $path) {
            if (! is_dir($path) || ! is_writable($path)) {
                return ['critical', "Direktori storage '{$name}' tidak dapat ditulis: {$path}", null];
            }
        }

        return ['ok', 'Storage local & public dapat ditulis.', null];
    }

    private function disk(): array
    {
        $free = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());
        if (! $free || ! $total) {
            return ['warn', 'Tidak dapat membaca info ruang disk.', null];
        }

        $pct = round($free / $total * 100, 1);
        $minWarn = (float) env('MONITORING_DISK_MIN_PCT', 15);
        $minCrit = (float) env('MONITORING_DISK_CRIT_PCT', 5);
        $detail = "Sisa {$pct}% (" . $this->humanBytes($free) . ' dari ' . $this->humanBytes($total) . ').';

        if ($pct < $minCrit) {
            return ['critical', "Ruang disk kritis: {$detail}", $pct];
        }
        if ($pct < $minWarn) {
            return ['warn', "Ruang disk menipis: {$detail}", $pct];
        }

        return ['ok', $detail, $pct];
    }

    private function queueFailed(): array
    {
        if (! DB::getSchemaBuilder()->hasTable('failed_jobs')) {
            return ['ok', 'Tabel failed_jobs tidak ada (queue non-DB).', 0];
        }

        $count = DB::table('failed_jobs')->count();
        if ($count === 0) {
            return ['ok', 'Tidak ada job gagal.', 0];
        }

        $status = $count >= (int) env('MONITORING_FAILED_JOBS_CRIT', 100) ? 'critical' : 'warn';

        return [$status, "{$count} job di failed_jobs (notifikasi/email mungkin tidak terkirim).", $count];
    }

    private function queueBacklog(): array
    {
        if (! DB::getSchemaBuilder()->hasTable('jobs')) {
            return ['ok', 'Tabel jobs tidak ada (queue non-DB).', 0];
        }

        $pending = DB::table('jobs')->count();
        $oldest = DB::table('jobs')->whereNull('reserved_at')->min('available_at');
        if (! $oldest) {
            return ['ok', "Antrian lancar ({$pending} job menunggu).", $pending];
        }

        $ageMin = (int) round((time() - (int) $oldest) / 60);
        $maxMin = (int) env('MONITORING_QUEUE_BACKLOG_MINUTES', 15);
        if ($ageMin > $maxMin) {
            return ['warn', "Job tertua menunggu {$ageMin} menit (> {$maxMin}) — worker mungkin mati/lambat.", $pending];
        }

        return ['ok', "Antrian lancar ({$pending} job, tertua {$ageMin} mnt).", $pending];
    }

    private function scheduler(): array
    {
        $ts = Cache::get('health:scheduler_last_run');
        if (! $ts) {
            return ['warn', 'Belum ada heartbeat scheduler (cron schedule:run mungkin belum berjalan).', null];
        }

        $ageMin = (int) round((time() - (int) $ts) / 60);
        $maxMin = (int) env('MONITORING_SCHEDULER_STALE_MINUTES', 5);
        if ($ageMin > $maxMin) {
            return ['warn', "Heartbeat scheduler basi ({$ageMin} mnt > {$maxMin}) — cron `schedule:run` kemungkinan mati.", $ageMin];
        }

        return ['ok', "Scheduler aktif (heartbeat {$ageMin} mnt lalu).", $ageMin];
    }

    private function backup(): array
    {
        $dir = rtrim((string) env('DB_BACKUP_PATH', storage_path('app/backups')), '/\\');
        $latest = 0;
        foreach (glob($dir . DIRECTORY_SEPARATOR . 'sikeren-*.gz') ?: [] as $f) {
            $latest = max($latest, (int) filemtime($f));
        }

        if ($latest === 0) {
            return ['warn', "Tidak ada berkas backup di {$dir} (jadwalkan `db:backup`).", null];
        }

        $ageH = (int) round((time() - $latest) / 3600);
        if ($ageH > (int) env('MONITORING_BACKUP_STALE_HOURS', 26)) {
            return ['warn', "Backup terakhir {$ageH} jam lalu (> 26 jam) — scheduler mungkin mati.", $ageH];
        }

        return ['ok', "Backup terakhir {$ageH} jam lalu.", $ageH];
    }

    private function errorRate(): array
    {
        $files = glob(storage_path('logs') . DIRECTORY_SEPARATOR . 'laravel*.log') ?: [];
        if ($files === []) {
            return ['ok', 'Tidak ada berkas log.', 0];
        }

        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        $file = $files[0];
        $size = filesize($file);
        $tail = 131072; // 128 KB terakhir
        $fh = fopen($file, 'rb');
        if ($size > $tail) {
            fseek($fh, -$tail, SEEK_END);
        }
        $content = (string) stream_get_contents($fh);
        fclose($fh);

        $count = preg_match_all('/\.(ERROR|CRITICAL|EMERGENCY|ALERT):/', $content);
        $max = (int) env('MONITORING_ERROR_RATE_MAX', 25);
        if ($count > $max) {
            return ['warn', "{$count} error/critical pada ~128KB log terbaru (> {$max}).", $count];
        }

        return ['ok', "{$count} error/critical pada log terbaru.", $count];
    }

    private function integrations(): array
    {
        if (! (bool) IntegrationSetting::getValue('monitoring.check_integrations', false)) {
            return ['ok', 'Pengecekan integrasi dimatikan (monitoring.check_integrations).', null];
        }

        $raw = IntegrationSetting::getValue('monitoring.endpoints');
        $endpoints = is_array($raw) ? $raw : (json_decode((string) $raw, true) ?: []);
        if ($endpoints === []) {
            return ['ok', 'Tidak ada endpoint integrasi yang dikonfigurasi.', null];
        }

        $down = [];
        foreach ($endpoints as $ep) {
            $name = $ep['name'] ?? ($ep['url'] ?? 'endpoint');
            $url = $ep['url'] ?? null;
            if (! $url) {
                continue;
            }
            try {
                $resp = Http::timeout(4)->connectTimeout(3)->get($url);
                if ($resp->serverError()) {
                    $down[] = "{$name} (HTTP {$resp->status()})";
                }
            } catch (\Throwable $e) {
                $down[] = "{$name} (tak terjangkau)";
            }
        }

        return $down === []
            ? ['ok', 'Semua endpoint integrasi terjangkau.', null]
            : ['warn', 'Integrasi bermasalah: ' . implode(', ', $down) . '.', $down];
    }

    private function humanBytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1) . ' ' . $units[$i];
    }
}
