<?php

namespace App\Jobs;

use App\Models\TagihanJasa;
use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Performa — kirim pesan WhatsApp di LATAR BELAKANG (queue) agar panggilan HTTP
 * ke gateway tidak memblokir request pengguna saat submit/approve/publish dll.
 *
 * Bersifat fire-and-forget: kegagalan dicatat ke log, TIDAK menggagalkan alur
 * bisnis (sama seperti perilaku sinkron lama yang membungkus sendMessage dalam
 * try/catch).
 *
 * PRASYARAT: saat QUEUE_CONNECTION=database, worker antrian WAJIB berjalan
 * (`php artisan queue:work`, idealnya via supervisor/systemd) agar pesan benar-
 * benar terkirim. Bila QUEUE_CONNECTION=sync, job berjalan inline (perilaku sama
 * seperti sebelumnya, tanpa manfaat async). Penumpukan job (worker mati)
 * terdeteksi oleh probe `queue_backlog` pada `monitor:health` (MON-03).
 */
class SendWhatsappMessage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Coba ulang beberapa kali bila gateway sedang bermasalah. */
    public int $tries = 3;

    /** Jeda antar percobaan (detik). */
    public int $backoff = 30;

    public function __construct(
        public string $target,
        public string $message,
        public ?TagihanJasa $tagihan = null,
    ) {
    }

    public function handle(WhatsappService $whatsapp): void
    {
        try {
            $whatsapp->sendMessage($this->target, $this->message, $this->tagihan);
        } catch (\Throwable $e) {
            Log::warning('SendWhatsappMessage gagal: ' . $e->getMessage(), [
                'target' => $this->target,
            ]);
        }
    }
}
