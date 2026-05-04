<?php

namespace App\Services;

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
    public function sendMessage(string $target, string $message): bool
    {
        $token = env('FONNTE_TOKEN');

        // Jika token tidak ada, log pesan saja untuk keperluan testing/development
        if (empty($token)) {
            Log::info("=== MOCK WA SENDER ===");
            Log::info("To: " . $target);
            Log::info("Message: \n" . $message);
            Log::info("======================");
            return true;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
                'delay' => '1',
                'countryCode' => '62',
            ]);

            $result = $response->json();
            
            if (isset($result['status']) && $result['status'] == true) {
                return true;
            }

            Log::error('Fonnte API Error: ' . json_encode($result));
            return false;
        } catch (\Exception $e) {
            Log::error('Fonnte Exception: ' . $e->getMessage());
            return false;
        }
    }
}
