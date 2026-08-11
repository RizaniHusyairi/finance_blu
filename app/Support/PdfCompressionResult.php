<?php

namespace App\Support;

/**
 * Hasil satu operasi PdfCompressor::store().
 *
 * Membawa alasan mengapa berkas dikompres atau tidak, supaya controller bisa
 * memberi tahu pengguna — sebelumnya informasi ini hanya masuk ke log server.
 *
 * @see PdfCompressor
 */
class PdfCompressionResult
{
    /** Berkas berhasil diperkecil oleh Ghostscript. */
    public const COMPRESSED = 'compressed';

    /** Kompresi dimatikan lewat config/pdf.php. */
    public const DISABLED = 'disabled';

    /** Di bawah `min_bytes` — tidak sepadan dikompres. */
    public const TOO_SMALL = 'too_small';

    /** Bukan PDF (mis. gambar) — dilewati. */
    public const NOT_PDF = 'not_pdf';

    /** PDF bertanda tangan digital / e-Meterai — sengaja tidak disentuh. */
    public const SIGNED = 'signed';

    /** Ghostscript tidak terpasang di server. */
    public const NO_BINARY = 'no_binary';

    /** Ghostscript gagal berjalan. */
    public const FAILED = 'failed';

    /** Hasil kompresi tidak lebih kecil dari aslinya — berkas asli dipakai. */
    public const NOT_SMALLER = 'not_smaller';

    public function __construct(
        /** Path relatif berkas tersimpan, atau false bila penyimpanan gagal. */
        public readonly string|false $path,
        public readonly string $reason,
        public readonly int $originalBytes = 0,
        public readonly int $storedBytes = 0,
    ) {
    }

    public function compressed(): bool
    {
        return $this->reason === self::COMPRESSED;
    }

    /** Persentase penghematan (0 bila tidak dikompres). */
    public function savedPercent(): int
    {
        if (! $this->compressed() || $this->originalBytes <= 0) {
            return 0;
        }

        return (int) round(($this->originalBytes - $this->storedBytes) / $this->originalBytes * 100);
    }

    /**
     * Pesan siap tampil untuk pengguna akhir — menjelaskan apa yang terjadi
     * pada berkas mereka, termasuk saat kompresi sengaja dilewati.
     */
    public function message(): string
    {
        return match ($this->reason) {
            self::COMPRESSED => sprintf(
                'File dikompres %s → %s (hemat %d%%).',
                self::humanBytes($this->originalBytes),
                self::humanBytes($this->storedBytes),
                $this->savedPercent(),
            ),
            self::SIGNED => 'File disimpan tanpa kompresi karena bertanda tangan digital/e-Meterai — mengompresnya akan membatalkan tanda tangan.',
            self::NO_BINARY => 'File disimpan tanpa kompresi karena Ghostscript belum terpasang di server.',
            self::TOO_SMALL => 'File sudah berukuran kecil, tidak perlu dikompres.',
            self::NOT_SMALLER => 'File disimpan apa adanya karena hasil kompresi tidak lebih kecil dari aslinya.',
            self::FAILED => 'File disimpan tanpa kompresi karena proses kompresi gagal.',
            self::DISABLED => 'File disimpan tanpa kompresi karena fitur kompresi sedang dinonaktifkan.',
            default => 'File disimpan tanpa kompresi.',
        };
    }

    public static function humanBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }

        return round($bytes / 1024, 1) . ' KB';
    }
}
