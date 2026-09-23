<?php

namespace App\Services\Btn;

class BtnSnapSignature
{
    /** Remove JSON whitespace without changing numbers, escapes or property order. */
    public function minify(string $json): string
    {
        json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $result = '';
        $quoted = false;
        $escaped = false;
        foreach (str_split($json) as $char) {
            if ($quoted) {
                $result .= $char;
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $quoted = false;
                }
            } elseif (! in_array($char, [' ', "\r", "\n", "\t"], true)) {
                $result .= $char;
                $quoted = $char === '"';
            }
        }

        return $result;
    }

    public function stringToSign(string $method, string $path, string $body, string $timestamp, ?string $token = null): string
    {
        return strtoupper($method).':'.$path.':'.($token === null ? '' : $token.':')
            .hash('sha256', $this->minify($body)).':'.$timestamp;
    }

    public function rsa(string $value, string $privateKey): string
    {
        $key = openssl_pkey_get_private($privateKey);
        $details = $key ? openssl_pkey_get_details($key) : false;
        if (! $details || $details['type'] !== OPENSSL_KEYTYPE_RSA || $details['bits'] < 2048
            || ! openssl_sign($value, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new BtnSnapException('Private key BTN harus berupa PEM RSA minimal 2048 bit.');
        }

        return base64_encode($signature);
    }

    public function verify(string $value, string $signature, string $publicKey): bool
    {
        $decoded = base64_decode($signature, true);
        $key = openssl_pkey_get_public($publicKey);
        $details = $key ? openssl_pkey_get_details($key) : false;

        return $decoded !== false && $details && $details['type'] === OPENSSL_KEYTYPE_RSA
            && $details['bits'] >= 2048 && openssl_verify($value, $decoded, $key, OPENSSL_ALGO_SHA256) === 1;
    }

    public function hmac(string $value, string $secret): string
    {
        return base64_encode(hash_hmac('sha512', $value, $secret, true));
    }
}
