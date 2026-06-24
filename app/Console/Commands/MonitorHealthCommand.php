<?php

namespace App\Console\Commands;

use App\Models\IntegrationSetting;
use App\Services\WhatsappService;
use App\Support\HealthCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * MON-01/MON-03 — pemantauan kesehatan sistem & alerting proaktif.
 *
 * Menjalankan seluruh probe observability (lihat App\Support\HealthCheck:
 * database, cache, antrian, storage, disk, backup, heartbeat scheduler, laju
 * error, integrasi) lalu MENGALERT setiap anomali (warn/critical) lewat:
 *   1) Log level `critical` — backbone alerting; mengalir ke Slack/Sentry begitu
 *      channel-nya diaktifkan di LOG_STACK (lihat config/logging).
 *   2) WhatsApp langsung ke admin (opsional, throttled) via IntegrationSetting
 *      `monitoring.alert_*`.
 *
 * Dijadwalkan tiap jam (routes/console.php). Exit non-zero saat ada anomali agar
 * terlihat oleh monitor scheduler eksternal. Endpoint `/health` memakai service
 * yang sama untuk monitor uptime/observability.
 */
class MonitorHealthCommand extends Command
{
    protected $signature = 'monitor:health
                            {--quiet-ok : Jangan cetak apa-apa bila sehat}';

    protected $description = 'Pantau kesehatan sistem (DB, cache, antrian, storage, disk, backup, scheduler, error, integrasi) & kirim alert (MON-01/03)';

    public function handle(HealthCheck $health): int
    {
        $report = $health->run();
        $checks = $report['checks'];
        $anomalies = array_values(array_filter($checks, fn ($c) => $c['status'] !== 'ok'));

        if (! ($this->option('quiet-ok') && $anomalies === [])) {
            $this->table(
                ['Probe', 'Status', 'Detail', 'ms'],
                array_map(fn ($c) => [
                    $c['label'],
                    strtoupper($c['status']),
                    Str::limit((string) $c['detail'], 70),
                    $c['latency_ms'],
                ], $checks)
            );
        }

        if ($anomalies === []) {
            if (! $this->option('quiet-ok')) {
                $this->info('Sistem sehat: ' . $report['summary']['ok'] . ' probe OK.');
            }

            return self::SUCCESS;
        }

        $lines = array_map(
            fn ($c) => sprintf('[%s] %s: %s', strtoupper($c['status']), $c['label'], $c['detail']),
            $anomalies
        );

        $message = "*ALERT SIKEREN — Monitoring*\nStatus keseluruhan: " . strtoupper($report['status']) . "\n"
            . implode("\n", array_map(fn ($l) => '• ' . $l, $lines));

        // Jalur 1: log critical (→ Slack/Sentry bila dikonfigurasi di LOG_STACK).
        Log::critical('MON-03: anomali kesehatan sistem', [
            'status' => $report['status'],
            'anomalies' => $lines,
        ]);
        foreach ($lines as $l) {
            $this->warn($l);
        }

        // Jalur 2: WhatsApp langsung ke admin (opsional, throttled per 6 jam).
        $this->maybeNotifyWhatsapp($message, $lines);

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
            Log::error('MON-03: gagal mengirim alert WhatsApp: ' . $e->getMessage());
        }
    }
}
