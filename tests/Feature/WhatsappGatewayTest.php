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

    public function test_gateway_respects_zero_device_id_even_with_environment_fallback(): void
    {
        $this->configureGateway();
        IntegrationSetting::setValue('whatsapp.gateway_device_id', 0, 'whatsapp', 'Device ID WA Gateway', 'integer');

        $environment = \Illuminate\Support\Env::getRepository();
        $previousDeviceId = $environment->get('WA_DEVICE_ID');
        $environment->set('WA_DEVICE_ID', '1');

        try {
            Http::fake([
                'wg.aptpairport.id/*' => Http::response(['success' => true], 200),
            ]);

            $this->assertTrue(app(WhatsappService::class)->sendMessage('08123456789', 'test'));
            Http::assertSent(fn ($request) => $request->url() === 'https://wg.aptpairport.id/api/v1/messages/send'
                && ! array_key_exists('deviceId', $request->data()));
        } finally {
            if ($previousDeviceId === null) {
                $environment->clear('WA_DEVICE_ID');
            } else {
                $environment->set('WA_DEVICE_ID', $previousDeviceId);
            }
        }
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
