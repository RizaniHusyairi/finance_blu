<?php

namespace App\Services\Btn;

class BtnSnapMoney
{
    public static function cents(mixed $value): int
    {
        if (! is_string($value) || ! preg_match('/^\d{1,12}\.\d{2}$/D', $value)) {
            throw new BtnSnapException('Nominal BTN harus berupa string desimal dengan dua angka pecahan.');
        }
        [$whole, $fraction] = explode('.', $value);

        return ((int) $whole * 100) + (int) $fraction;
    }

    public static function decimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
