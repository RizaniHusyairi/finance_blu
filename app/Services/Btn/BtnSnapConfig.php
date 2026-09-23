<?php

namespace App\Services\Btn;

use App\Models\IntegrationSetting;

class BtnSnapConfig
{
    public function get(string $key, mixed $default = ''): mixed
    {
        return IntegrationSetting::getValue('btn.'.$key, $default);
    }

    public function mode(): string
    {
        return $this->get('mode', 'mock');
    }

    public function assertOutbound(): void
    {
        if (! $this->get('enabled', false) || ! in_array($this->mode(), ['sandbox', 'production'], true)) {
            throw new BtnSnapException('Aktifkan BTN dan pilih sandbox atau production untuk mengakses SNAP.');
        }
        foreach (['base_url', 'client_id', 'partner_id', 'client_secret', 'private_key', 'origin', 'channel_id', 'partner_service_id'] as $key) {
            if (blank($this->get($key))) {
                throw new BtnSnapException('Konfigurasi BTN belum lengkap: '.$key.'.');
            }
        }
        $url = parse_url($this->get('base_url'));
        if (! $url || ($url['scheme'] ?? '') !== 'https' || empty($url['host'])
            || ! empty($url['user']) || ! empty($url['pass']) || ! empty($url['query'])
            || ! empty($url['fragment']) || ! in_array($url['path'] ?? '', ['', '/'], true)) {
            throw new BtnSnapException('Base URL BTN harus berupa origin HTTPS tanpa path, query, atau kredensial.');
        }
        if (! preg_match('/^\d{5}$/D', (string) $this->get('channel_id'))
            || ! preg_match('/^\d{5}$/D', trim($this->get('partner_service_id')))) {
            throw new BtnSnapException('CHANNEL-ID dan kode institusi VA BTN harus masing-masing 5 digit; konfirmasikan ke BTN.');
        }
    }

    public function partnerServiceId(): string
    {
        return str_pad(trim($this->get('partner_service_id')), 8, ' ', STR_PAD_LEFT);
    }

    public function fingerprint(): string
    {
        return hash('sha256', implode('|', [$this->mode(), $this->get('base_url'), $this->get('partner_id'), $this->partnerServiceId()]));
    }
}
