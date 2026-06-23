<?php

namespace App\Console\Commands;

use App\Models\IntegrationSetting;
use App\Services\WhatsappService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MON-01 — pemantauan kesehatan sistem & alerting proaktif.
 *
 * Mendeteksi anomali operasional (job antrian gagal, backup DB basi) lalu
 * MENGALERT lewat dua jalur:
 *   1) Log level `critical` — backbone alerting; mengalir ke Slack/Sentry/
 *      papertrail begitu channel-nya diaktifkan di LOG_STACK (lihat config/logging).
 *   2) WhatsApp langsung ke kontak admin (opsional, throttled) bila dikonfigurasi
 *      via IntegrationSetting `monitoring.alert_*`.
 *
 * Dijadwalkan tiap jam (routes/console.php). Exit non-zero saat ada anomali agar
 * terlihat oleh monitor scheduler eksternal.
 */
class MonitorHealthCommand extends Command
{
    protected $signature = 'monitor:health
                            {--quiet-ok : Jangan cetak apa-apa bila sehat}';

    protected $description = 'Pantau kesehatan sistem (failed jobs, kesegaran backup) & kirim alert (MON-01)';

    public function handle(): int
    {
        $alerts = [];

        // 1) Job antrian gagal — notifikasi/email/WA yang gagal mengendap di sini.
        if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
            $failed = DB::table('failed_jobs')->count();
            if ($failed > 0) {
                $alerts[] = "Antrian: {$failed} job di tabel failed_jobs (notifikasi/email mungkin tidak terkirim).";
            }
        }

        // 2) Kesegaran backup DB (BR-01).
        $backupDir = rtrim((string) env('DB_BACKUP_PATH', storage_path('app/backups')), '/\\');
        $latest = 0;
        foreach (glob($backupDir . DIRECTORY_SEPARATOR . 'sikeren-*.gz') ?: [] as $f) {
            $latest = max($latest, (int) filemtime($f));
        }
        if ($latest === 0) {
            $alerts[] = "Backup DB: tidak ada berkas backup di {$backupDir} (jadwalkan `db:backup`).";
        } elseif ((time() - $latest) > 26 * 3600) {
            $jam = (int) round((time() - $latest) / 3600);
            $alerts[] = "Backup DB: backup terakhir {$jam} jam lalu (> 26 jam) — kemungkinan scheduler mati.";
        }

        if ($alerts === []) {
            if (! $this->option('quiet-ok')) {
                $this->info('Sistem sehat: tidak ada anomali terdeteksi.');
            }

            return self::SUCCESS;
        }

        $message = "*ALERT SIKEREN — Monitoring*\n" . implode("\n", array_map(fn ($a) => '• ' . $a, $alerts));

        // Jalur 1: log critical (→ Slack/Sentry bila dikonfigurasi di LOG_STACK).
        Log::critical('MON-01: anomali kesehatan sistem', ['alerts' => $alerts]);
        foreach ($alerts as $a) {
            $this->warn($a);
        }

        // Jalur 2: WhatsApp langsung ke admin (opsional, throttled per 6 jam).
        $this->maybeNotifyWhatsapp($message, $alerts);

        return self::FAILURE;
    }

    private function maybeNotifyWhatsapp(string $message, array $alerts): void
    {
        if (! (bool) IntegrationSetting::getValue('monitoring.alert_enabled', false)) {
            return;
        }

        $target = IntegrationSetting::getValue('monitoring.alert_wa');
        if (blank($target)) {
            return;
        }

        // Throttle: satu pengiriman per kombinasi anomali per 6 jam agar tidak spam.
        $key = 'mon:alert:' . md5(implode('|', $alerts));
        if (Cache::has($key)) {
            return;
        }
        Cache::put($key, 1, now()->addHours(6));

        try {
            app(WhatsappService::class)->sendMessage((string) $target, $message);
        } catch (\Throwable $e) {
            Log::error('MON-01: gagal mengirim alert WhatsApp: ' . $e->getMessage());
        }
    }
}
