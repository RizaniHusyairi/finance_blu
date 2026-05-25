<?php

namespace App\Console\Commands;

use App\Models\IntegrationSetting;
use App\Models\ShortLink;
use App\Models\TagihanJasa;
use App\Models\WhatsappNotificationLog;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Kirim reminder WhatsApp ke mitra untuk tagihan yang mendekati jatuh tempo.
 *
 * Konfigurasi (table integration_settings, group=whatsapp):
 *  - whatsapp.reminder.enabled       (bool)   on/off seluruh reminder
 *  - whatsapp.reminder.days_before   (csv)    contoh "7,3,1,0" — sisa hari yang dikirimi reminder
 *  - whatsapp.reminder.template      (text)   template pesan dengan placeholder {mitra_nama}, dll
 *
 * Untuk mencegah duplikat, command ini mengecek WhatsappNotificationLog —
 * sebuah tagihan + offset_hari hanya akan dikirim sekali per hari.
 *
 * Jadwal eksekusi: per jam (lihat routes/console.php). Filter waktu via
 * `whatsapp.reminder.send_time` untuk memastikan benar-benar dikirim sekali
 * di jam yang ditentukan.
 */
class SendDueDateReminderCommand extends Command
{
    protected $signature = 'wa:reminder-due-date
                            {--dry-run : Tampilkan kandidat tanpa benar-benar mengirim}
                            {--force : Abaikan jam pengiriman dan toggle aktif}';

    protected $description = 'Kirim WA reminder ke mitra untuk tagihan yang mendekati jatuh tempo';

    public function handle(WhatsappService $whatsappService): int
    {
        $enabled = (bool) IntegrationSetting::getValue('whatsapp.reminder.enabled', true);
        $force = $this->option('force');
        if (! $enabled && ! $force) {
            $this->line('Reminder dinonaktifkan (whatsapp.reminder.enabled=false). Lewati.');
            return self::SUCCESS;
        }

        $sendTime = (string) IntegrationSetting::getValue('whatsapp.reminder.send_time', '08:00');
        $sendHour = (int) explode(':', $sendTime)[0];
        if (! $force && now()->hour !== $sendHour) {
            $this->line("Jam saat ini bukan jam pengiriman ({$sendTime}). Lewati.");
            return self::SUCCESS;
        }

        $offsets = collect(explode(',', (string) IntegrationSetting::getValue('whatsapp.reminder.days_before', '7,3,1,0')))
            ->map(fn ($v) => trim($v))
            ->filter(fn ($v) => $v !== '' && ctype_digit($v))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        if ($offsets->isEmpty()) {
            $this->warn('Tidak ada offset hari yang dikonfigurasi.');
            return self::SUCCESS;
        }

        $template = (string) IntegrationSetting::getValue('whatsapp.reminder.template', '');
        if ($template === '') {
            $this->error('Template reminder kosong.');
            return self::FAILURE;
        }

        $today = now()->startOfDay();
        $sent = 0;
        $skipped = 0;
        $dry = $this->option('dry-run');

        foreach ($offsets as $offset) {
            $targetDate = $today->copy()->addDays($offset);

            $tagihans = TagihanJasa::query()
                ->with('mitra')
                ->where('status', 'PUBLISHED')
                ->where('status_pembayaran', 'belum_dibayar')
                ->whereDate('tanggal_jatuh_tempo', $targetDate)
                ->get();

            foreach ($tagihans as $tagihan) {
                $phone = $tagihan->mitra?->no_telepon;
                if (! $phone) {
                    $skipped++;
                    continue;
                }

                if ($this->alreadySentToday($tagihan->id, $offset)) {
                    $skipped++;
                    continue;
                }

                $message = $this->buildMessage($template, $tagihan, $offset);

                if ($dry) {
                    $this->line(sprintf("[DRY] %s | %s | jt=%s | offset=%d", $tagihan->nomor_tagihan, $phone, $targetDate->toDateString(), $offset));
                    continue;
                }

                $whatsappService->sendMessage($phone, $message, $tagihan);
                // Simpan jejak offset untuk dedupe.
                WhatsappNotificationLog::where('tagihan_jasa_id', $tagihan->id)
                    ->latest('id')
                    ->limit(1)
                    ->update(['error_message' => "reminder_offset_{$offset}"]);

                $sent++;
            }
        }

        $this->info("Selesai. Dikirim: {$sent} | Dilewati: {$skipped}");
        return self::SUCCESS;
    }

    private function alreadySentToday(int $tagihanId, int $offset): bool
    {
        return WhatsappNotificationLog::where('tagihan_jasa_id', $tagihanId)
            ->where('error_message', "reminder_offset_{$offset}")
            ->whereDate('created_at', now()->toDateString())
            ->exists();
    }

    private function buildMessage(string $template, TagihanJasa $tagihan, int $offset): string
    {
        $jatuhTempo = $tagihan->tanggal_jatuh_tempo
            ? Carbon::parse($tagihan->tanggal_jatuh_tempo)->translatedFormat('d F Y')
            : '-';

        $sisaHari = match (true) {
            $offset === 0 => 'jatuh tempo HARI INI',
            $offset === 1 => 'jatuh tempo BESOK',
            default       => "tinggal {$offset} hari lagi",
        };

        $shortLink = ShortLink::forTarget('tagihan_jasa', $tagihan->id);

        $vars = [
            '{mitra_nama}'    => $tagihan->mitra->nama_mitra ?? '-',
            '{nomor_tagihan}' => $tagihan->nomor_tagihan,
            '{total}'         => 'Rp ' . number_format((float) $tagihan->total_tagihan, 0, ',', '.'),
            '{nomor_va}'      => $tagihan->nomor_va ?: '-',
            '{jatuh_tempo}'   => $jatuhTempo,
            '{sisa_hari}'     => $sisaHari,
            '{link_invoice}'  => $shortLink->publicUrl(),
        ];

        return strtr($template, $vars);
    }
}
