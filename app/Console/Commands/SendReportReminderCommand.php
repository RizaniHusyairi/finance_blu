<?php

namespace App\Console\Commands;

use App\Models\IntegrationSetting;
use App\Models\MitraJasaKonsesi;
use App\Models\MitraJasaPenjualan;
use App\Models\MitraJasaPjp2u;
use App\Services\EmailNotificationService;
use App\Services\WhatsappService;
use Illuminate\Console\Command;

/**
 * Ingatkan mitra yang BELUM melaporkan konsesi / PAX PJP2U untuk bulan yang baru berakhir.
 *
 * Toggle: integration_settings `jasa.reminder_pelaporan.enabled` (bool, default false).
 * Jadwal: lihat routes/console.php (mis. tanggal 3 tiap bulan).
 */
class SendReportReminderCommand extends Command
{
    protected $signature = 'jasa:reminder-pelaporan
                            {--dry-run : Tampilkan kandidat tanpa benar-benar mengirim}
                            {--force : Abaikan toggle aktif}';

    protected $description = 'Kirim pengingat ke mitra yang belum melaporkan konsesi/PAX PJP2U bulan lalu';

    public function handle(WhatsappService $whatsapp, EmailNotificationService $email): int
    {
        $enabled = (bool) IntegrationSetting::getValue('jasa.reminder_pelaporan.enabled', false);
        if (! $enabled && ! $this->option('force')) {
            $this->line('Reminder pelaporan nonaktif (jasa.reminder_pelaporan.enabled=false). Lewati.');
            return self::SUCCESS;
        }

        // Target: bulan lalu (yang sudah selesai).
        $target = now()->subMonthNoOverflow();
        $bulan = (int) $target->month;
        $tahun = (int) $target->year;
        $periodStart = $target->copy()->startOfMonth();
        $periodEnd = $target->copy()->endOfMonth();

        // Assignment aktif yang berlaku pada periode tsb.
        $rows = collect();
        $konsesi = MitraJasaKonsesi::with('mitraJasa')->where('status_aktif', true)
            ->where(fn ($q) => $q->whereNull('tanggal_mulai')->orWhereDate('tanggal_mulai', '<=', $periodEnd))
            ->where(fn ($q) => $q->whereNull('tanggal_selesai')->orWhereDate('tanggal_selesai', '>=', $periodStart))
            ->get();
        foreach ($konsesi as $k) {
            $rows->push(['mitra_id' => $k->mitra_jasa_id, 'layanan_id' => $k->layanan_jasa_id, 'jenis' => 'konsesi', 'mitra' => $k->mitraJasa]);
        }
        $pjp2u = MitraJasaPjp2u::with('mitraJasa')->where('status_aktif', true)
            ->where(fn ($q) => $q->whereNull('tanggal_mulai')->orWhereDate('tanggal_mulai', '<=', $periodEnd))
            ->where(fn ($q) => $q->whereNull('tanggal_selesai')->orWhereDate('tanggal_selesai', '>=', $periodStart))
            ->get();
        foreach ($pjp2u as $p) {
            $rows->push(['mitra_id' => $p->mitra_jasa_id, 'layanan_id' => $p->layanan_jasa_id, 'jenis' => 'pjp2u', 'mitra' => $p->mitraJasa]);
        }

        // Sudah lapor pada periode tsb.
        $reported = MitraJasaPenjualan::where('tahun', $tahun)->where('bulan', $bulan)
            ->get()
            ->map(fn ($r) => $r->mitra_jasa_id . '|' . $r->layanan_jasa_id)
            ->unique()
            ->flip();

        $belum = $rows->reject(fn ($r) => $reported->has($r['mitra_id'] . '|' . $r['layanan_id']));

        if ($belum->isEmpty()) {
            $this->info("Tidak ada mitra yang belum lapor untuk periode {$bulan}/{$tahun}.");
            return self::SUCCESS;
        }

        $periode = $target->translatedFormat('F Y');
        $portal = url('/login');
        $sent = 0;
        $skipped = 0;
        $dry = $this->option('dry-run');

        foreach ($belum->groupBy('mitra_id') as $group) {
            $mitra = $group->first()['mitra'];
            if (! $mitra) {
                $skipped++;
                continue;
            }

            $jenisText = collect($group->pluck('jenis')->unique())
                ->map(fn ($j) => $j === 'konsesi' ? 'omzet konsesi' : 'jumlah penumpang (PAX) PJP2U')
                ->implode(' dan ');

            if ($dry) {
                $this->line("[DRY] {$mitra->nama_mitra} | belum: {$jenisText}");
                continue;
            }

            $message = "*PENGINGAT PELAPORAN*\n\n"
                . 'Yth. ' . ($mitra->nama_mitra ?? 'Mitra') . ",\n\n"
                . "Mohon segera menyampaikan laporan {$jenisText} untuk periode *{$periode}* melalui portal mitra.\n\n"
                . "Portal: {$portal}\n\n"
                . "Terima kasih.\n_SIKEREN-BLU_";

            $kirim = false;
            if (filled($mitra->no_telepon)) {
                $whatsapp->sendMessage($mitra->no_telepon, $message);
                $kirim = true;
            }
            if (filled($mitra->email)) {
                $email->sendNotification(
                    $mitra->email,
                    "Pengingat Pelaporan Periode {$periode}",
                    $message,
                    $mitra,
                    'send_report_reminder_email'
                );
                $kirim = true;
            }

            $kirim ? $sent++ : $skipped++;
        }

        $this->info("Reminder pelaporan {$bulan}/{$tahun}: terkirim ke {$sent} mitra, dilewati {$skipped}.");
        return self::SUCCESS;
    }
}
