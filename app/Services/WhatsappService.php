<?php

namespace App\Services;

use App\Models\IntegrationLog;
use App\Models\IntegrationSetting;
use App\Models\TagihanJasa;
use App\Models\WhatsappNotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    /**
     * Mengirim pesan WhatsApp ke nomor target.
     *
     * Mendukung dua provider yang dapat diganti via UI Integrasi (atau env):
     *  - "fonnte"      : layanan SaaS Fonnte (header Authorization: <token>).
     *  - "wa_gateway"  : self-hosted gateway berbasis Bearer token, format
     *                    "Authorization: Bearer <API_KEY>" + body JSON
     *                    { target, message } yang masuk antrian worker
     *                    (anti-ban). Mendukung multi-akun via session.
     *
     * @param string $target Nomor tujuan (contoh: 08123456789 atau 628123456789)
     * @param string $message Isi pesan
     * @return bool
     */
    public function sendMessage(string $target, string $message, ?TagihanJasa $tagihan = null): bool
    {
        $provider = IntegrationSetting::getValue('whatsapp.provider', 'fonnte');
        $enabled = (bool) IntegrationSetting::getValue('whatsapp.enabled', filled(env('FONNTE_TOKEN')) || filled(env('WA_API_KEY')));

        $log = WhatsappNotificationLog::create([
            'tagihan_jasa_id' => $tagihan?->id,
            'provider' => $provider,
            'target' => $target,
            'message' => $message,
            'status' => 'pending',
        ]);

        // Jika feature dimatikan, log saja (mock).
        if (! $enabled) {
            $this->markAsMock($log, 'WhatsApp dinonaktifkan pada pengaturan integrasi.');
            return true;
        }

        return match ($provider) {
            'wa_gateway' => $this->sendViaGateway($log, $target, $message, $tagihan),
            default      => $this->sendViaFonnte($log, $target, $message, $tagihan),
        };
    }

    /**
     * Provider Fonnte (legacy).
     */
    private function sendViaFonnte(WhatsappNotificationLog $log, string $target, string $message, ?TagihanJasa $tagihan): bool
    {
        $token = IntegrationSetting::getValue('whatsapp.fonnte_token') ?: env('FONNTE_TOKEN');
        $endpoint = IntegrationSetting::getValue('whatsapp.fonnte_endpoint', 'https://api.fonnte.com/send');

        if (empty($token)) {
            $this->markAsMock($log, 'Fonnte token kosong — pesan tidak benar-benar dikirim.');
            return true;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post($endpoint, [
                'target' => $target,
                'message' => $message,
                'delay' => '1',
                'countryCode' => '62',
            ]);

            $result = $response->json() ?? [];

            if (($result['status'] ?? false) === true) {
                $log->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'response_payload' => $result,
                ]);

                $this->writeIntegrationLog('fonnte', $endpoint, $response->status(), 'success', $result, $tagihan);
                return true;
            }

            $log->update([
                'status' => 'failed',
                'response_payload' => $result,
                'error_message' => json_encode($result),
            ]);

            $this->writeIntegrationLog('fonnte', $endpoint, $response->status(), 'failed', $result, $tagihan);
            Log::error('Fonnte API Error: ' . json_encode($result));
            return false;
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Fonnte Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Provider WhatsApp Gateway berbasis Bearer token.
     *
     * Endpoint default: {GATEWAY_URL}/send/text
     * Multi-akun: {GATEWAY_URL}/sessions/{session}/send/text
     * Header: Authorization: Bearer <API_KEY>
     * Body  : { target, message }
     */
    private function sendViaGateway(WhatsappNotificationLog $log, string $target, string $message, ?TagihanJasa $tagihan): bool
    {
        $baseUrl = rtrim(
            (string) (IntegrationSetting::getValue('whatsapp.gateway_url') ?: env('WA_GATEWAY_URL')),
            '/'
        );
        $apiKey = IntegrationSetting::getValue('whatsapp.gateway_api_key') ?: env('WA_API_KEY');
        $session = IntegrationSetting::getValue('whatsapp.gateway_session') ?: env('WA_GATEWAY_SESSION');
        $countryCode = (string) (IntegrationSetting::getValue('whatsapp.default_country_code', '62'));

        if (empty($baseUrl) || empty($apiKey)) {
            $this->markAsMock($log, 'WA Gateway URL / API key belum dikonfigurasi.');
            return true;
        }

        $endpoint = $session
            ? "{$baseUrl}/sessions/{$session}/send/text"
            : "{$baseUrl}/send/text";

        $payload = [
            'target' => $this->normalizePhone($target, $countryCode),
            'message' => $message,
        ];

        try {
            $response = Http::withoutVerifying()
                ->withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(20)
                ->post($endpoint, $payload);

            $result = $response->json() ?? [];

            if ($response->successful() && ($result['queued'] ?? $result['status'] ?? false)) {
                $log->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'response_payload' => $result,
                ]);

                $this->writeIntegrationLog('wa_gateway', $endpoint, $response->status(), 'success', $result, $tagihan);
                return true;
            }

            $log->update([
                'status' => 'failed',
                'response_payload' => $result,
                'error_message' => json_encode($result),
            ]);

            $this->writeIntegrationLog('wa_gateway', $endpoint, $response->status(), 'failed', $result, $tagihan);
            Log::error('WA Gateway error', ['status' => $response->status(), 'body' => $result]);
            return false;
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('WA Gateway exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Normalisasi nomor telepon: hapus karakter non-angka, tambahkan country code
     * jika belum ada (mengganti leading 0 dengan country code).
     */
    private function normalizePhone(string $raw, string $countryCode = '62'): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return $raw;
        }

        if (str_starts_with($digits, '0')) {
            return $countryCode . substr($digits, 1);
        }

        if (! str_starts_with($digits, $countryCode)) {
            // Anggap sudah dalam format internasional lain — kembalikan apa adanya.
            return $digits;
        }

        return $digits;
    }

    private function writeIntegrationLog(string $provider, string $endpoint, int $statusCode, string $status, array $result, ?TagihanJasa $tagihan): void
    {
        IntegrationLog::create([
            'provider' => $provider,
            'action' => 'send_whatsapp',
            'direction' => 'outbound',
            'status' => $status,
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'reference_type' => $tagihan ? TagihanJasa::class : null,
            'reference_id' => $tagihan?->id,
            'response_payload' => $result,
            'created_by' => auth()->id(),
        ]);
    }

    private function markAsMock(WhatsappNotificationLog $log, string $reason): void
    {
        Log::info('=== MOCK WA SENDER === ' . $reason);
        Log::info("To: " . $log->target);
        Log::info("Message: \n" . $log->message);
        Log::info('======================');

        $log->update([
            'status' => 'mock',
            'sent_at' => now(),
            'response_payload' => ['message' => $reason],
        ]);
    }
}
