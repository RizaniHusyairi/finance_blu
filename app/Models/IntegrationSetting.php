<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class IntegrationSetting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_encrypted' => 'boolean',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (! $setting || ! $setting->is_active || $setting->value === null) {
            return $default;
        }

        if ($setting->is_encrypted) {
            try {
                return Crypt::decryptString($setting->value);
            } catch (\Throwable) {
                return $default;
            }
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            default => $setting->value,
        };
    }

    public static function setValue(
        string $key,
        mixed $value,
        string $group,
        ?string $label = null,
        string $type = 'text',
        bool $encrypted = false,
        bool $active = true,
        ?array $meta = null
    ): self {
        $storedValue = $value;

        if ($type === 'boolean') {
            $storedValue = $value ? '1' : '0';
        }

        if ($encrypted && filled($storedValue)) {
            $storedValue = Crypt::encryptString((string) $storedValue);
        }

        return static::updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'label' => $label,
                'type' => $type,
                'value' => $storedValue,
                'is_encrypted' => $encrypted,
                'is_active' => $active,
                'meta' => $meta,
            ]
        );
    }

    public static function maskSecret(?string $value): string
    {
        if (! filled($value)) {
            return '';
        }

        $length = mb_strlen($value);

        return $length <= 8
            ? str_repeat('*', $length)
            : mb_substr($value, 0, 4) . str_repeat('*', max(4, $length - 8)) . mb_substr($value, -4);
    }
}
