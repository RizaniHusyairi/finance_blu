<?php

namespace App\Console\Commands;

use App\Models\ArsipDokumen;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * INF-01 — pindahkan arsip dokumen keuangan internal yang terlanjur tersimpan
 * di disk publik (`storage/app/public`, ter-symlink ke `public/storage` sehingga
 * dapat diunduh tanpa login) ke disk privat (`local`).
 *
 * Dibatasi ke daftar jenis dokumen internal yang sudah dialihkan ke disk privat
 * pada kode (INVOICE, FAKTUR_PAJAK, BAPP_GAMBAR_RAB, LAMPIRAN_LAINNYA). Dokumen
 * alur vendor/magic-link dan dokumen TTE (*_FINAL_TTD, SURAT_PENGANTAR_FINAL_TTD,
 * dokumen kontrak) sengaja TIDAK disentuh agar akses publik/mitra-nya tidak rusak.
 */
class MoveArsipToPrivateDiskCommand extends Command
{
    protected $signature = 'arsip:secure-private
                            {--dry-run : Tampilkan kandidat tanpa memindahkan file}';

    protected $description = 'Pindahkan arsip dokumen internal sensitif dari disk publik ke disk privat (INF-01)';

    /**
     * Jenis dokumen internal yang aman dipindah ke disk privat. Harus selaras
     * dengan call site upload yang sudah memakai disk `local`.
     */
    private const JENIS_PRIVAT = [
        'INVOICE',
        'FAKTUR_PAJAK',
        'BAPP_GAMBAR_RAB',
        'LAMPIRAN_LAINNYA',
        // Surat Pengantar Tagihan Jasa — kini disajikan via streaming terproteksi
        // (internal & mitra pemilik), bukan URL publik. File final/­draft dipindah
        // ke disk privat. Kolom `file_surat_pengantar_final` berbagi path dengan
        // arsip SURAT_PENGANTAR_FINAL_TTD sehingga ikut termigrasi.
        'SURAT_PENGANTAR_FINAL_TTD',
        'SURAT_PENGANTAR_DRAFT',
        // Perjaldin — arsip nominatif bertanda tangan (kini disk privat, disajikan
        // via viewNominatifTtd yang sudah disk-aware).
        'NOMINATIF_PERJALDIN_TTD',
        'DAFTAR_NOMINATIF_PEMBAYARAN_PERJALDIN_TTD',
        // Kontrak Pengadaan (INF-01 step 3) — disajikan via arsip.view (disk-aware)
        // & secure-file. Berbagi path dengan kolom kontrak terkait.
        'SPK_FINAL_TTD',
        'SPMK_FINAL_TTD',
        'RINGKASAN_KONTRAK_FINAL_TTD',
        'GAMBAR_RAB',
        'JAMINAN_UANG_MUKA',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $records = ArsipDokumen::where('disk', 'public')
            ->whereIn('jenis_dokumen', self::JENIS_PRIVAT)
            ->orderBy('id')
            ->get();

        if ($records->isEmpty()) {
            $this->info('Tidak ada arsip publik yang perlu dipindahkan.');
            return self::SUCCESS;
        }

        $public = Storage::disk('public');
        $local = Storage::disk('local');

        $moved = 0;
        $missing = 0;
        $failed = 0;

        foreach ($records as $arsip) {
            $line = sprintf('#%d %s | %s', $arsip->id, $arsip->jenis_dokumen, $arsip->path_file);

            if (! $public->exists($arsip->path_file)) {
                // File tidak ada di disk publik (mis. sudah dipindah manual).
                // Tetap samakan kolom disk agar serving membaca dari disk privat.
                $this->warn('[FILE HILANG] ' . $line);
                $missing++;

                if (! $dryRun) {
                    $arsip->update(['disk' => 'local']);
                }
                continue;
            }

            if ($dryRun) {
                $this->line('[DRY] ' . $line);
                continue;
            }

            try {
                if (! $local->exists($arsip->path_file)) {
                    $stream = $public->readStream($arsip->path_file);
                    $local->writeStream($arsip->path_file, $stream);

                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }

                // Verifikasi salinan privat ada sebelum menghapus yang publik.
                if (! $local->exists($arsip->path_file)) {
                    throw new \RuntimeException('Salinan privat gagal dibuat.');
                }

                $arsip->update(['disk' => 'local']);
                $public->delete($arsip->path_file);

                $this->line('[PINDAH] ' . $line);
                $moved++;
            } catch (\Throwable $e) {
                $this->error('[GAGAL] ' . $line . ' — ' . $e->getMessage());
                $failed++;
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%s Total kandidat: %d | dipindah: %d | file hilang: %d | gagal: %d',
            $dryRun ? '[DRY-RUN]' : 'Selesai.',
            $records->count(),
            $moved,
            $missing,
            $failed,
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
