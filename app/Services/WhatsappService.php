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
     * Mengirim pesan WhatsApp menggunakan API Fonnte.
     * 
     * @param string $target Nomor tujuan (contoh: 08123456789 atau 628123456789)
     * @param string $message Isi pesan
     * @return bool
     */
    public function sendMessage(string $target, string $message, ?TagihanJasa $tagihan = null): bool
    {
        $provider = IntegrationSetting::getValue('whatsapp.provider', 'fonnte');
        $enabled = (bool) IntegrationSetting::getValue('whatsapp.enabled', filled(env('FONNTE_TOKEN')));
        $token = IntegrationSetting::getValue('whatsapp.fonnte_token') ?: env('FONNTE_TOKEN');
        $endpoint = IntegrationSetting::getValue('whatsapp.fonnte_endpoint', 'https://api.fonnte.com/send');

        $log = WhatsappNotificationLog::create([
            'tagihan_jasa_id' => $tagihan?->id,
            'provider' => $provider,
            'target' => $target,
            'message' => $message,
            'status' => 'pending',
        ]);

        // Jika token tidak ada, log pesan saja untuk keperluan testing/development
        if (! $enabled || empty($token)) {
            Log::info("=== MOCK WA SENDER ===");
            Log::info("To: " . $target);
            Log::info("Message: \n" . $message);
            Log::info("======================");

            $log->update([
                'status' => 'mock',
                'sent_at' => now(),
                'response_payload' => ['message' => 'WhatsApp mock: provider belum aktif atau token kosong.'],
            ]);

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

            $result = $response->json();
            
            if (isset($result['status']) && $result['status'] == true) {
                $log->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'response_payload' => $result,
                ]);

                IntegrationLog::create([
                    'provider' => $provider,
                    'action' => 'send_whatsapp',
                    'direction' => 'outbound',
                    'status' => 'success',
                    'endpoint' => $endpoint,
                    'status_code' => $response->status(),
                    'reference_type' => $tagihan ? TagihanJasa::class : null,
                    'reference_id' => $tagihan?->id,
                    'response_payload' => $result,
                    'created_by' => auth()->id(),
                ]);

                return true;
            }

            $log->update([
                'status' => 'failed',
                'response_payload' => $result,
                'error_message' => json_encode($result),
            ]);

            Log::error('Fonnte API Error: ' . json_encode($result));
            return false;
        } catch (\Exception $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Fonnte Exception: ' . $e->getMessage());
            return false;
        }
    }
}
