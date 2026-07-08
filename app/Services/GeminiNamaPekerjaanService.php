<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Merangkum daftar produk pada Surat Pesanan (INAPROC) menjadi judul
 * "nama pekerjaan" yang singkat dan formal memakai Google Gemini (free tier).
 *
 * Best-effort murni: tanpa API key, gagal jaringan, kuota habis, atau respons
 * aneh → mengembalikan null dan pemanggil memakai saran heuristik. Timeout
 * ketat agar form tidak terasa lambat.
 */
class GeminiNamaPekerjaanService
{
    private const TIMEOUT_SECONDS = 8;
    private const MAX_INPUT_CHARS = 2000;
    private const MAX_TITLE_CHARS = 150;

    public function isEnabled(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /** Judul pekerjaan dari teks ringkasan produk, atau null bila gagal. */
    public function suggest(string $ringkasanProduk): ?string
    {
        if (! $this->isEnabled() || trim($ringkasanProduk) === '') {
            return null;
        }

        $model = config('services.gemini.model', 'gemini-2.0-flash');
        $prompt = "Berikut daftar barang pada Surat Pesanan e-purchasing pemerintah:\n\n"
            . Str::limit($ringkasanProduk, self::MAX_INPUT_CHARS)
            . "\n\nBuat SATU judul paket pengadaan berbahasa Indonesia yang singkat, formal, dan mencakup barang-barang utama. "
            . 'Awali dengan kata "Pengadaan". Balas HANYA judulnya saja tanpa tanda kutip, tanpa penjelasan.';

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withHeaders(['x-goog-api-key' => config('services.gemini.key')])
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                    [
                        'contents' => [
                            ['parts' => [['text' => $prompt]]],
                        ],
                        'generationConfig' => [
                            'temperature' => 0.2,
                            // Model Gemini 2.5 memakai "thinking tokens" yang ikut
                            // memakan budget output — matikan agar judul tidak terpotong.
                            'maxOutputTokens' => 256,
                            'thinkingConfig' => ['thinkingBudget' => 0],
                        ],
                    ]
                );

            if (! $response->successful()) {
                Log::info('Gemini nama pekerjaan: respons tidak sukses.', ['status' => $response->status()]);

                return null;
            }

            $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');

            return $this->sanitize($text);
        } catch (\Throwable $e) {
            Log::info('Gemini nama pekerjaan: gagal dipanggil.', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /** Rapikan keluaran model menjadi satu baris judul yang layak simpan. */
    private function sanitize(string $text): ?string
    {
        // Satu baris pertama yang berisi; buang markdown/kutip/penomoran.
        $line = collect(preg_split('/\r?\n/', trim($text)) ?: [])
            ->map(fn ($l) => trim($l, " \t\"'`*#-–—.:1234567890)"))
            ->first(fn ($l) => $l !== '');

        if (! $line) {
            return null;
        }

        $line = trim(preg_replace('/\s+/u', ' ', $line) ?? '');
        if (mb_strlen($line) < 5) {
            return null;
        }

        if (! Str::startsWith(Str::lower($line), 'pengadaan')) {
            $line = 'Pengadaan ' . $line;
        }

        return Str::limit($line, self::MAX_TITLE_CHARS, '');
    }
}
