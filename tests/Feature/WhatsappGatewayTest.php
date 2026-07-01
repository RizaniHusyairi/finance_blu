<?php

namespace Tests\Feature;

use App\Models\IntegrationSetting;
use App\Models\WhatsappNotificationLog;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappGatewayTest extends TestCase
{
    use RefreshDatabase;

    private function configureGateway(): void
    {
        IntegrationSetting::setValue('whatsapp.enabled', true, 'whatsapp', 'Status WhatsApp', 'boolean');
        IntegrationSetting::setValue('whatsapp.provider', 'wa_gateway', 'whatsapp', 'Provider WhatsApp');
        IntegrationSetting::setValue('whatsapp.gateway_url', 'https://wg.aptpairport.id', 'whatsapp', 'URL WA Gateway');
        IntegrationSetting::setValue('whatsapp.gateway_api_key', 'wag_xxx.yyy', 'whatsapp', 'API Key WA Gateway');
        IntegrationSetting::setValue('whatsapp.gateway_device_id', 1, 'whatsapp', 'Device ID WA Gateway', 'integer');
    }

    public function test_gateway_request_matches_aptpairport_api_contract(): void
    {
        $this->configureGateway();

        Http::fake([
            'wg.aptpairport.id/*' => Http::response([
                'success' => true,
                'message' => 'Pesan dimasukkan ke antrean',
                'data' => ['id' => 123, 'status' => 'QUEUED', 'to' => '628123456789'],
            ], 200),
        ]);

        $ok = app(WhatsappService::class)->sendMessage('08123456789', 'Halo dari WhatsApp Gateway');

        $this->assertTrue($ok);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://wg.aptpairport.id/api/v1/messages/send'
                && $request->hasHeader('X-API-Key', 'wag_xxx.yyy')
                && (int) $request['deviceId'] === 1
                && $request['to'] === '628123456789'
                && $request['body'] === 'Halo dari WhatsApp Gateway';
        });

        $log = WhatsappNotificationLog::latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('sent', $log->status);
    }

    public function test_gateway_marks_failed_when_success_flag_absent(): void
    {
        $this->configureGateway();

        Http::fake([
            'wg.aptpairport.id/*' => Http::response(['success' => false, 'message' => 'Nomor tidak valid'], 422),
        ]);

        $ok = app(WhatsappService::class)->sendMessage('08123456789', 'test');

        $this->assertFalse($ok);
        $this->assertSame('failed', WhatsappNotificationLog::latest('id')->first()->status);
    }
}
