<?php

namespace Tests\Unit;

use App\Support\PdfCompressor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Uji jalur aman PdfCompressor: kompresi TIDAK boleh menggagalkan upload.
 *
 * Ghostscript tidak diasumsikan tersedia di lingkungan CI, jadi pengujian
 * difokuskan pada kontrak fallback yang deterministik — file selalu tersimpan,
 * baik saat kompresi dinonaktifkan maupun saat file terlalu kecil untuk diproses.
 */
class PdfCompressorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        PdfCompressor::flushResolvedBinary();
    }

    public function test_menyimpan_file_asli_saat_kompresi_dinonaktifkan(): void
    {
        config(['pdf.compression.enabled' => false]);
        Storage::fake('local');

        $file = UploadedFile::fake()->create('kontrak.pdf', 200, 'application/pdf');

        $path = PdfCompressor::storeCompressed($file, 'mitra-jasa/kontrak', 'local');

        $this->assertNotFalse($path);
        $this->assertStringStartsWith('mitra-jasa/kontrak/', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_file_kecil_dilewati_dan_tetap_tersimpan(): void
    {
        // min_bytes default 50 KB — file 10 KB harus dilewati (disimpan apa adanya).
        config(['pdf.compression.enabled' => true, 'pdf.compression.min_bytes' => 51200]);
        Storage::fake('local');

        $file = UploadedFile::fake()->create('kecil.pdf', 10, 'application/pdf');

        $path = PdfCompressor::storeCompressed($file, 'mitra-jasa/kontrak', 'local');

        $this->assertNotFalse($path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_fallback_menyimpan_file_saat_ghostscript_tidak_ditemukan(): void
    {
        // Paksa biner yang pasti tidak ada → harus jatuh ke penyimpanan file asli.
        config([
            'pdf.compression.enabled' => true,
            'pdf.compression.min_bytes' => 1024,
            'pdf.compression.ghostscript_binary' => '/jalur/tidak/ada/gs-xyz',
        ]);
        Storage::fake('local');

        $file = UploadedFile::fake()->create('kontrak.pdf', 300, 'application/pdf');

        $path = PdfCompressor::storeCompressed($file, 'mitra-jasa/kontrak', 'local');

        $this->assertNotFalse($path);
        Storage::disk('local')->assertExists($path);
    }
}
