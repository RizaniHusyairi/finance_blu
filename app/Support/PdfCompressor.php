<?php

namespace App\Support;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Kompresi PDF saat upload memakai Ghostscript.
 *
 * Dipakai untuk memperkecil dokumen yang diunggah (mis. file kontrak mitra)
 * sebelum disimpan, tanpa pernah menggagalkan upload: bila Ghostscript tidak
 * terpasang, gagal, atau hasilnya TIDAK lebih kecil, file ASLI yang disimpan.
 * Preset /ebook (150dpi) menjaga teks & tanda tangan tetap tajam — penting
 * untuk dokumen legal — sambil menurunkan ukuran PDF hasil scan secara nyata.
 *
 * Konfigurasi: config/pdf.php (key `compression`).
 */
class PdfCompressor
{
    /** Preset -dPDFSETTINGS yang diizinkan (mencegah injeksi argumen). */
    private const ALLOWED_PRESETS = ['/screen', '/ebook', '/printer', '/prepress', '/default'];

    /** Hasil resolusi biner Ghostscript di-cache per-request. */
    private static ?string $resolvedBinary = null;
    private static bool $resolutionAttempted = false;

    /**
     * Simpan PDF terunggah ke disk, dikompres bila memungkinkan.
     *
     * Mengembalikan path relatif file tersimpan (kompres bila lebih kecil,
     * selain itu file asli) — drop-in pengganti UploadedFile::store().
     */
    public static function storeCompressed(UploadedFile $file, string $directory, string $disk = 'local'): string|false
    {
        $directory = trim($directory, '/');
        $config = config('pdf.compression', []);

        // Jalur aman: simpan apa adanya. Dipakai sebagai fallback semua cabang.
        $storeOriginal = static fn () => $file->store($directory, $disk);

        if (! ($config['enabled'] ?? true)) {
            return $storeOriginal();
        }

        $originalSize = (int) ($file->getSize() ?: 0);
        if ($originalSize < (int) ($config['min_bytes'] ?? 51200)) {
            return $storeOriginal();
        }

        $source = $file->getRealPath();
        if ($source === false || ! is_file($source)) {
            return $storeOriginal();
        }

        // Hanya proses PDF asli (cek magic header). File lain — gambar, dll —
        // disimpan apa adanya, sehingga service ini aman dipakai pada field
        // campuran (mis. pdf,jpg,png) maupun lewat DocumentArchiveService.
        if (! self::isPdf($source)) {
            return $storeOriginal();
        }

        // Jangan sentuh PDF bertanda tangan digital / e-Meterai: kompresi menulis
        // ulang PDF dan akan MEMBATALKAN tanda tangannya. Simpan file asli.
        if (self::hasDigitalSignature($source)) {
            Log::info('PdfCompressor: PDF bertanda tangan digital terdeteksi — disimpan tanpa kompresi.');
            return $storeOriginal();
        }

        $binary = self::resolveBinary();
        if ($binary === null) {
            Log::info('PdfCompressor: Ghostscript tidak ditemukan — file disimpan tanpa kompresi.');
            return $storeOriginal();
        }

        $compressed = self::compress($source, $binary, $config);
        if ($compressed === null) {
            return $storeOriginal();
        }

        try {
            $compressedSize = (int) (filesize($compressed) ?: 0);

            // Pakai hasil hanya bila valid (non-kosong) DAN benar-benar lebih kecil.
            if ($compressedSize <= 0 || $compressedSize >= $originalSize) {
                return $storeOriginal();
            }

            $stored = Storage::disk($disk)->putFileAs($directory, new File($compressed), $file->hashName());
            if ($stored === false) {
                return $storeOriginal();
            }

            Log::info(sprintf(
                'PdfCompressor: PDF dikompres %s → %s (-%d%%).',
                self::humanBytes($originalSize),
                self::humanBytes($compressedSize),
                (int) round(($originalSize - $compressedSize) / $originalSize * 100)
            ));

            return $stored;
        } finally {
            @unlink($compressed);
        }
    }

    /**
     * Jalankan Ghostscript: kompres $source → file temp baru.
     * Mengembalikan path file temp hasil, atau null bila gagal.
     */
    private static function compress(string $source, string $binary, array $config): ?string
    {
        $output = tempnam(sys_get_temp_dir(), 'pdfc_');
        if ($output === false) {
            return null;
        }

        $preset = $config['preset'] ?? '/ebook';
        if (! in_array($preset, self::ALLOWED_PRESETS, true)) {
            $preset = '/ebook';
        }

        // Bentuk argv (array → tanpa shell), jadi path file aman dari injeksi.
        $command = [
            $binary,
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-dPDFSETTINGS=' . $preset,
            '-dNOPAUSE',
            '-dBATCH',
            '-dQUIET',
            '-dSAFER',
            '-sOutputFile=' . $output,
            $source,
        ];

        try {
            $result = Process::timeout((float) ($config['timeout'] ?? 60))->run($command);
        } catch (\Throwable $e) {
            Log::warning('PdfCompressor: Ghostscript gagal dijalankan — ' . $e->getMessage());
            @unlink($output);
            return null;
        }

        if (! $result->successful() || ! is_file($output) || filesize($output) === 0) {
            Log::warning(sprintf(
                'PdfCompressor: Ghostscript keluar dengan kode %s — %s',
                $result->exitCode(),
                trim($result->errorOutput()) ?: 'tanpa output error'
            ));
            @unlink($output);
            return null;
        }

        return $output;
    }

    /**
     * Cari biner Ghostscript: konfigurasi eksplisit → lokasi umum Windows → PATH.
     * Hasil (termasuk "tidak ada") di-cache agar deteksi hanya sekali per request.
     */
    private static function resolveBinary(): ?string
    {
        if (self::$resolutionAttempted) {
            return self::$resolvedBinary;
        }
        self::$resolutionAttempted = true;

        // 1. Konfigurasi eksplisit menang.
        $configured = config('pdf.compression.ghostscript_binary');
        if (is_string($configured) && $configured !== '') {
            if (is_file($configured)) {
                return self::$resolvedBinary = $configured;
            }

            $onPath = (new ExecutableFinder())->find($configured);
            if ($onPath !== null) {
                return self::$resolvedBinary = $onPath;
            }

            Log::warning('PdfCompressor: GHOSTSCRIPT_BINARY diset tetapi tidak ditemukan — ' . $configured);
        }

        // 2. Lokasi instalasi umum Windows (versi tertinggi lebih dulu).
        foreach (self::windowsCandidates() as $candidate) {
            if (is_file($candidate)) {
                return self::$resolvedBinary = $candidate;
            }
        }

        // 3. Cari di PATH (Linux/macOS: gs; Windows: gswin64c/gswin32c).
        $finder = new ExecutableFinder();
        foreach (['gs', 'gswin64c', 'gswin32c'] as $name) {
            $found = $finder->find($name);
            if ($found !== null) {
                return self::$resolvedBinary = $found;
            }
        }

        return self::$resolvedBinary = null;
    }

    /**
     * Kandidat path Ghostscript pada instalasi standar Windows, mis.
     * "C:\Program Files\gs\gs10.04.0\bin\gswin64c.exe".
     *
     * @return list<string>
     */
    private static function windowsCandidates(): array
    {
        $roots = array_filter([
            getenv('ProgramFiles') ?: 'C:\\Program Files',
            getenv('ProgramFiles(x86)') ?: 'C:\\Program Files (x86)',
        ]);

        $candidates = [];
        foreach ($roots as $root) {
            foreach (['gswin64c.exe', 'gswin32c.exe'] as $exe) {
                $pattern = $root . DIRECTORY_SEPARATOR . 'gs' . DIRECTORY_SEPARATOR
                    . '*' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $exe;
                $matches = glob($pattern) ?: [];
                rsort($matches); // versi terbaru dulu
                array_push($candidates, ...$matches);
            }
        }

        return $candidates;
    }

    /** Cek magic header %PDF- agar hanya PDF asli yang diproses Ghostscript. */
    private static function isPdf(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }

        $head = fread($handle, 1024);
        fclose($handle);

        return is_string($head) && str_contains($head, '%PDF-');
    }

    /**
     * Deteksi tanda tangan digital / e-Meterai pada PDF.
     *
     * Penanda /ByteRange (rentang byte yang dicakup tanda tangan) ada di hampir
     * semua PDF bertanda tangan kriptografis, termasuk e-Meterai Peruri; scan
     * TTD basah tidak memilikinya. Bersikap konservatif: bila ragu → anggap
     * bertanda tangan agar kompresi dilewati dan dokumen tidak rusak.
     */
    private static function hasDigitalSignature(string $path): bool
    {
        $content = @file_get_contents($path);
        if ($content === false) {
            return false;
        }

        foreach (['/ByteRange', '/Type/Sig', '/Type /Sig', 'adbe.pkcs7', 'ETSI.CAdES'] as $marker) {
            if (str_contains($content, $marker)) {
                return true;
            }
        }

        return false;
    }

    private static function humanBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }

        return round($bytes / 1024, 1) . ' KB';
    }

    /**
     * Reset cache deteksi biner — untuk keperluan pengujian.
     */
    public static function flushResolvedBinary(): void
    {
        self::$resolvedBinary = null;
        self::$resolutionAttempted = false;
    }
}
