<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsappMessage;
use App\Services\WhatsappService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsappQueueTest extends TestCase
{
    public function test_queue_message_dispatches_background_job_instead_of_sending_inline(): void
    {
        Queue::fake();

        app(WhatsappService::class)->queueMessage('08123456789', 'Halo SIKEREN');

        Queue::assertPushed(SendWhatsappMessage::class, function ($job) {
            return $job->target === '08123456789' && $job->message === 'Halo SIKEREN';
        });
    }

    public function test_whatsapp_send_job_is_queued_not_synchronous(): void
    {
        // Performa: job harus ShouldQueue agar panggilan HTTP gateway tidak
        // memblokir request pengguna.
        $this->assertInstanceOf(ShouldQueue::class, new SendWhatsappMessage('0812', 'hi'));
    }
}
