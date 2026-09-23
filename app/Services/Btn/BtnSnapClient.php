<?php

namespace App\Services\Btn;

use App\Models\IntegrationLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class BtnSnapClient
{
    public function __construct(private BtnSnapConfig $config, private BtnSnapSignature $signature) {}

    public function token(): string
    {
        $this->config->assertOutbound();
        $cacheKey = $this->tokenCacheKey();
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return Crypt::decryptString($cached);
        }
        $timestamp = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $result = $this->send('/snap/v1/access-token/b2b', ['grantType' => 'client_credentials', 'additionalInfo' => (object) []], [
            'X-TIMESTAMP' => $timestamp,
            'X-CLIENT-KEY' => $this->config->get('client_id'),
            'X-SIGNATURE' => $this->signature->rsa($this->config->get('client_id').'|'.$timestamp, $this->config->get('private_key')),
        ], '73');
        if (! is_string($result['accessToken'] ?? null) || blank($result['accessToken'])
            || ($result['tokenType'] ?? '') !== 'Bearer' || ! is_numeric($result['expiresIn'] ?? null)
            || (int) $result['expiresIn'] <= 0) {
            throw new BtnSnapException('Respons token BTN tidak valid.');
        }
        $ttl = min(900, (int) $result['expiresIn']) - 60;
        if ($ttl > 0) {
            Cache::put($cacheKey, Crypt::encryptString($result['accessToken']), $ttl);
        }

        return $result['accessToken'];
    }

    public function request(string $action, array $payload): array
    {
        $services = ['create-va' => '27', 'inquiry-va' => '30', 'update-va' => '28', 'delete-va' => '31', 'report' => '35', 'status' => '26'];
        if (! isset($services[$action])) {
            throw new BtnSnapException('Layanan BTN tidak dikenal.');
        }
        $this->config->assertOutbound();
        $path = '/snap/v1/transfer-va/'.$action;
        $token = $this->token();
        $timestamp = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $body = $this->encode($payload);

        return $this->send($path, $payload, [
            'Authorization' => 'Bearer '.$token,
            'X-TIMESTAMP' => $timestamp,
            'X-PARTNER-ID' => $this->config->get('partner_id'),
            'X-EXTERNAL-ID' => strtoupper(bin2hex(random_bytes(8))),
            'CHANNEL-ID' => $this->config->get('channel_id'),
            'X-SIGNATURE' => $this->signature->hmac($this->signature->stringToSign('POST', $path, $body, $timestamp, $token), $this->config->get('client_secret')),
        ], $services[$action]);
    }

    private function encode(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function tokenCacheKey(): string
    {
        return 'btn:token:'.hash('sha256', $this->config->fingerprint().$this->config->get('client_id')
            .$this->config->get('client_secret').$this->config->get('private_key'));
    }

    private function send(string $path, array $payload, array $headers, string $service): array
    {
        // Never retry a mutation blindly: a timeout does not mean the bank rejected it.
        $url = rtrim($this->config->get('base_url'), '/').$path;
        try {
            $response = Http::withHeaders($headers + ['Origin' => $this->config->get('origin')])
                ->acceptJson()->connectTimeout(5)->timeout(20)->withoutRedirecting()
                ->withBody($this->encode($payload), 'application/json')->post($url);
        } catch (ConnectionException) {
            $this->log($path, null, null, 'unknown');
            throw new BtnSnapException('Koneksi BTN terputus/timeout. Status permintaan belum pasti; lakukan inquiry sebelum mengulang.');
        }
        $data = $response->json();
        $code = is_array($data) && is_string($data['responseCode'] ?? null) ? $data['responseCode'] : '';
        $ok = $response->successful() && $code === '200'.$service.'00';
        if ($code === '401'.$service.'01') {
            Cache::forget($this->tokenCacheKey());
        }
        $this->log($path, $response->status(), $code, $ok ? 'success' : 'failed');
        if (! $ok) {
            throw new BtnSnapException('BTN menolak permintaan atau memberikan respons tidak valid (HTTP '.$response->status().', kode '.($code ?: '-').').', $code ?: '500'.$service.'00');
        }

        return $data;
    }

    private function log(string $path, ?int $http, ?string $code, string $status): void
    {
        // Do not log credentials, signatures, tokens or bank customer payloads.
        IntegrationLog::create([
            'provider' => 'btn', 'action' => basename($path), 'direction' => 'outbound',
            'endpoint' => $path, 'status' => $status, 'status_code' => $http,
            'response_payload' => ['responseCode' => $code],
        ]);
    }
}
