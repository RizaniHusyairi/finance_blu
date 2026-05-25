<?php

namespace App\Http\Controllers;

use App\Models\IntegrationLog;
use App\Models\IntegrationSetting;
use App\Models\WhatsappNotificationLog;
use App\Services\WhatsappService;
use Illuminate\Http\Request;

class JasaIntegrationSettingController extends Controller
{
    public function index()
    {
        $settings = $this->settings();
        $logs = IntegrationLog::latest()->limit(12)->get();
        $waLogs = WhatsappNotificationLog::latest()->limit(8)->get();

        return view('super_admin_jasa.integrasi.index', compact('settings', 'logs', 'waLogs'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'btn_enabled' => ['nullable', 'boolean'],
            'btn_mode' => ['required', 'in:mock,sandbox,production'],
            'btn_base_url' => ['nullable', 'url', 'max:255'],
            'btn_client_id' => ['nullable', 'string', 'max:255'],
            'btn_client_secret' => ['nullable', 'string', 'max:255'],
            'btn_merchant_id' => ['nullable', 'string', 'max:255'],
            'btn_va_prefix' => ['nullable', 'string', 'max:20'],
            'btn_va_expiry_days' => ['required', 'integer', 'min:1', 'max:365'],
            'whatsapp_enabled' => ['nullable', 'boolean'],
            'whatsapp_provider' => ['required', 'in:fonnte,wa_gateway'],
            'whatsapp_fonnte_endpoint' => ['nullable', 'url', 'max:255'],
            'whatsapp_fonnte_token' => ['nullable', 'string', 'max:500'],
            'whatsapp_gateway_url' => ['nullable', 'url', 'max:255'],
            'whatsapp_gateway_api_key' => ['nullable', 'string', 'max:500'],
            'whatsapp_gateway_session' => ['nullable', 'string', 'max:50'],
            'whatsapp_default_country_code' => ['nullable', 'string', 'max:5'],
            'whatsapp_invoice_template' => ['nullable', 'string', 'max:4000'],
            'whatsapp_receipt_template' => ['nullable', 'string', 'max:4000'],
        ]);

        IntegrationSetting::setValue('btn.enabled', $request->boolean('btn_enabled'), 'btn', 'Status integrasi BTN', 'boolean');
        IntegrationSetting::setValue('btn.mode', $validated['btn_mode'], 'btn', 'Mode BTN');
        IntegrationSetting::setValue('btn.base_url', $validated['btn_base_url'] ?? null, 'btn', 'Base URL BTN');
        IntegrationSetting::setValue('btn.client_id', $validated['btn_client_id'] ?? null, 'btn', 'Client ID BTN');
        $this->setSecretIfFilled('btn.client_secret', $validated['btn_client_secret'] ?? null, 'btn', 'Client Secret BTN');
        IntegrationSetting::setValue('btn.merchant_id', $validated['btn_merchant_id'] ?? null, 'btn', 'Merchant ID BTN');
        IntegrationSetting::setValue('btn.va_prefix', $validated['btn_va_prefix'] ?: '88', 'btn', 'Prefix VA BTN');
        IntegrationSetting::setValue('btn.va_expiry_days', $validated['btn_va_expiry_days'], 'btn', 'Masa aktif VA', 'integer');

        IntegrationSetting::setValue('whatsapp.enabled', $request->boolean('whatsapp_enabled'), 'whatsapp', 'Status WhatsApp', 'boolean');
        IntegrationSetting::setValue('whatsapp.provider', $validated['whatsapp_provider'], 'whatsapp', 'Provider WhatsApp');
        IntegrationSetting::setValue('whatsapp.fonnte_endpoint', $validated['whatsapp_fonnte_endpoint'] ?: 'https://api.fonnte.com/send', 'whatsapp', 'Endpoint Fonnte');
        $this->setSecretIfFilled('whatsapp.fonnte_token', $validated['whatsapp_fonnte_token'] ?? null, 'whatsapp', 'Token Fonnte');
        IntegrationSetting::setValue('whatsapp.gateway_url', $validated['whatsapp_gateway_url'] ?? null, 'whatsapp', 'URL WA Gateway');
        $this->setSecretIfFilled('whatsapp.gateway_api_key', $validated['whatsapp_gateway_api_key'] ?? null, 'whatsapp', 'API Key WA Gateway');
        IntegrationSetting::setValue('whatsapp.gateway_session', $validated['whatsapp_gateway_session'] ?? null, 'whatsapp', 'Session WA Gateway');
        IntegrationSetting::setValue('whatsapp.default_country_code', $validated['whatsapp_default_country_code'] ?: '62', 'whatsapp', 'Kode Negara WhatsApp');
        IntegrationSetting::setValue('whatsapp.invoice_template', $validated['whatsapp_invoice_template'] ?? null, 'whatsapp', 'Template Invoice WhatsApp', 'textarea');
        IntegrationSetting::setValue('whatsapp.receipt_template', $validated['whatsapp_receipt_template'] ?? null, 'whatsapp', 'Template Struk WhatsApp', 'textarea');

        return back()->with('success', 'Pengaturan integrasi berhasil disimpan.');
    }

    public function testWhatsapp(Request $request, WhatsappService $whatsappService)
    {
        $validated = $request->validate([
            'target' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $sent = $whatsappService->sendMessage($validated['target'], $validated['message']);

        return back()->with($sent ? 'success' : 'error', $sent
            ? 'Pesan test WhatsApp berhasil diproses.'
            : 'Pesan test WhatsApp gagal diproses. Cek log integrasi.');
    }

    private function settings(): array
    {
        return [
            'btn_enabled' => (bool) IntegrationSetting::getValue('btn.enabled', false),
            'btn_mode' => IntegrationSetting::getValue('btn.mode', 'mock'),
            'btn_base_url' => IntegrationSetting::getValue('btn.base_url', ''),
            'btn_client_id' => IntegrationSetting::getValue('btn.client_id', ''),
            'btn_client_secret_masked' => IntegrationSetting::maskSecret(IntegrationSetting::getValue('btn.client_secret', '')),
            'btn_merchant_id' => IntegrationSetting::getValue('btn.merchant_id', ''),
            'btn_va_prefix' => IntegrationSetting::getValue('btn.va_prefix', '88'),
            'btn_va_expiry_days' => IntegrationSetting::getValue('btn.va_expiry_days', 30),
            'whatsapp_enabled' => (bool) IntegrationSetting::getValue('whatsapp.enabled', filled(env('FONNTE_TOKEN')) || filled(env('WA_API_KEY'))),
            'whatsapp_provider' => IntegrationSetting::getValue('whatsapp.provider', 'fonnte'),
            'whatsapp_fonnte_endpoint' => IntegrationSetting::getValue('whatsapp.fonnte_endpoint', 'https://api.fonnte.com/send'),
            'whatsapp_fonnte_token_masked' => IntegrationSetting::maskSecret(IntegrationSetting::getValue('whatsapp.fonnte_token', env('FONNTE_TOKEN', ''))),
            'whatsapp_gateway_url' => IntegrationSetting::getValue('whatsapp.gateway_url', env('WA_GATEWAY_URL', '')),
            'whatsapp_gateway_api_key_masked' => IntegrationSetting::maskSecret(IntegrationSetting::getValue('whatsapp.gateway_api_key', env('WA_API_KEY', ''))),
            'whatsapp_gateway_session' => IntegrationSetting::getValue('whatsapp.gateway_session', env('WA_GATEWAY_SESSION', '')),
            'whatsapp_default_country_code' => IntegrationSetting::getValue('whatsapp.default_country_code', '62'),
            'whatsapp_invoice_template' => IntegrationSetting::getValue('whatsapp.invoice_template', ''),
            'whatsapp_receipt_template' => IntegrationSetting::getValue('whatsapp.receipt_template', ''),
        ];
    }

    private function setSecretIfFilled(string $key, ?string $value, string $group, string $label): void
    {
        if (filled($value)) {
            IntegrationSetting::setValue($key, $value, $group, $label, 'password', true);
        }
    }
}
