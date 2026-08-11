<?php

namespace App\Services;

use App\Support\PdfCompressionResult;
use App\Support\PdfCompressor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Penampungan sementara berkas kontrak yang diunggah di latar belakang.
 *
 * Alur: berkas dikirim lewat AJAX saat pengguna memilihnya, dikompres
 * Ghostscript di sini, lalu MENUNGGU sampai form benar-benar disimpan.
 * Dengan begitu pengguna melihat hasil kompresi yang sebenarnya — bukan
 * perkiraan — dan penyimpanan akhir tinggal memindahkan berkas.
 *
 * Keamanan: berkas dipisah per-pengguna (`staging/{userId}/`) dan token hanya
 * berupa UUID. Token dari pengguna lain tidak akan pernah teresolusi, dan
 * karena UUID divalidasi ketat, token tidak bisa dipakai menembus direktori.
 */
class KontrakStagingService
{
    private const DISK = 'local';
    private const ROOT = 'mitra-jasa/kontrak-staging';

    /** Berkas titipan yang tak pernah disimpan dibuang setelah ini. */
    private const KEDALUWARSA_JAM = 6;

    /**
     * Kompres lalu titipkan berkas. Mengembalikan token + hasil kompresi.
     *
     * @return array{token: string, hasil: PdfCompressionResult}
     */
    public function titipkan(UploadedFile $file, int $userId): array
    {
        $this->bersihkanKedaluwarsa($userId);

        $token = (string) Str::uuid();
        $hasil = PdfCompressor::store($file, $this->direktori($userId), self::DISK);

        if ($hasil->path === false) {
            return ['token' => '', 'hasil' => $hasil];
        }

        // Namai ulang ke token supaya penyelesaian nanti cukup bermodal UUID.
        Storage::disk(self::DISK)->move($hasil->path, $this->jalur($userId, $token));

        return ['token' => $token, 'hasil' => $hasil];
    }

    /**
     * Pindahkan berkas titipan ke lokasi permanen dan kembalikan path-nya.
     * Mengembalikan null bila token tidak sah, bukan milik pengguna ini,
     * atau berkasnya sudah tidak ada.
     */
    public function tuntaskan(?string $token, int $userId, string $tujuanDir): ?string
    {
        if (! $this->tokenSah($token)) {
            return null;
        }

        $asal = $this->jalur($userId, $token);
        if (! Storage::disk(self::DISK)->exists($asal)) {
            return null;
        }

        $tujuan = trim($tujuanDir, '/') . '/' . $token . '.pdf';
        Storage::disk(self::DISK)->move($asal, $tujuan);

        return $tujuan;
    }

    /** Buang berkas titipan tanpa memindahkannya (mis. pengguna ganti berkas). */
    public function buang(?string $token, int $userId): void
    {
        if (! $this->tokenSah($token)) {
            return;
        }

        Storage::disk(self::DISK)->delete($this->jalur($userId, $token));
    }

    /**
     * Token wajib UUID. Ini sekaligus yang membuat jalur() aman: tidak ada
     * garis miring, titik ganda, atau karakter lain yang bisa keluar direktori.
     */
    private function tokenSah(?string $token): bool
    {
        return is_string($token) && $token !== '' && Str::isUuid($token);
    }

    private function direktori(int $userId): string
    {
        return self::ROOT . '/' . $userId;
    }

    private function jalur(int $userId, string $token): string
    {
        return $this->direktori($userId) . '/' . $token . '.pdf';
    }

    /**
     * Buang titipan lama milik pengguna ini. Dipanggil setiap kali menitipkan,
     * jadi tidak perlu job terjadwal terpisah: pengguna yang aktif membersihkan
     * sampahnya sendiri.
     */
    private function bersihkanKedaluwarsa(int $userId): void
    {
        $disk = Storage::disk(self::DISK);
        $batas = Carbon::now()->subHours(self::KEDALUWARSA_JAM)->getTimestamp();

        foreach ($disk->files($this->direktori($userId)) as $berkas) {
            if ($disk->lastModified($berkas) < $batas) {
                $disk->delete($berkas);
            }
        }
    }
}
