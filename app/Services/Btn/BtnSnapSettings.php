<?php

namespace App\Services\Btn;

use App\Models\IntegrationSetting;

class BtnSnapSettings
{
    public const FIELDS = [
        'partner_id' => ['label' => 'API Key ID / X-PARTNER-ID', 'rules' => ['nullable', 'string', 'max:255']],
        'partner_service_id' => ['label' => 'Kode institusi VA (5 digit)', 'rules' => ['nullable', 'regex:/^\d{5}$/D']],
        'channel_id' => ['label' => 'CHANNEL-ID (5 digit)', 'rules' => ['nullable', 'regex:/^\d{5}$/D']],
        'origin' => ['label' => 'Origin / domain terdaftar', 'rules' => ['nullable', 'string', 'max:255']],
        'current_account_no' => ['label' => 'Nomor giro tujuan (opsional)', 'rules' => ['nullable', 'regex:/^\d{1,16}$/D']],
        'trx_type' => ['label' => 'Kode transaksi VA (F = Full)', 'default' => 'F', 'rules' => ['nullable', 'regex:/^[A-Za-z0-9]$/D']],
        'private_key' => ['label' => 'Private key aplikasi (PEM RSA)', 'secret' => true, 'rules' => ['nullable', 'string', 'max:16000']],
        'inbound_partner_id' => ['label' => 'X-PARTNER-ID pengirim BTN', 'rules' => ['nullable', 'string', 'max:255']],
        'bank_public_key' => ['label' => 'Public key BTN (PEM RSA)', 'secret' => true, 'rules' => ['nullable', 'string', 'max:16000']],
        'inbound_auth' => ['label' => 'Autentikasi panggilan masuk', 'default' => 'disabled', 'rules' => ['nullable', 'in:disabled,rsa']],
    ];

    public static function rules(): array
    {
        $rules = [];
        foreach (self::FIELDS as $key => $field) {
            $rules['btn_'.$key] = $field['rules'];
        }

        return $rules;
    }

    public static function save(array $validated): void
    {
        foreach (self::FIELDS as $key => $field) {
            if (! array_key_exists('btn_'.$key, $validated)) {
                continue;
            }
            $value = $validated['btn_'.$key];
            if (($field['secret'] ?? false) && blank($value)) {
                continue;
            }
            IntegrationSetting::setValue('btn.'.$key, $value, 'btn', $field['label'], 'text', $field['secret'] ?? false);
        }
    }

    public static function values(): array
    {
        $values = [];
        foreach (self::FIELDS as $key => $field) {
            $value = IntegrationSetting::getValue('btn.'.$key, $field['default'] ?? '');
            $values['btn_'.$key] = ($field['secret'] ?? false) ? filled($value) : $value;
        }

        return $values;
    }
}
