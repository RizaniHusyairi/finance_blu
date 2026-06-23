<?php

namespace App\Console\Commands;

use App\Models\DetailKontrak;
use App\Models\DetailPerjaldin;
use App\Models\KontrakMitraJasa;
use App\Models\KontrakPengadaan;
use App\Models\LaporanUtilitas;
use App\Models\LogPerubahanTarifPjp2u;
use App\Models\MitraJasaPenjualan;
use App\Models\MitraJasaPenjualanDetail;
use App\Models\PemakaianGarbarata;
use App\Models\PermohonanNonSchedule;
use App\Models\RiwayatRevisiDipa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * INF-01 — pindahkan file dokumen yang disimpan pada KOLOM MODEL (bukan
 * arsip_dokumen) dari disk publik ke disk privat `local`. Melengkapi
 * `arsip:secure-private` yang hanya menangani tabel arsip_dokumen.
 *
 * Dokumen ini kini disajikan via route streaming terproteksi `secure-file`
 * (lihat SecureFileController), sehingga tidak lagi memerlukan URL publik.
 */
class MovePublicColumnFilesToPrivateCommand extends Command
{
    protected $signature = 'arsip:secure-column-files
                            {--dry-run : Tampilkan kandidat tanpa memindahkan file}';

    protected $description = 'Pindahkan file dokumen berbasis kolom model (laporan/utilitas/kontrak mitra) dari disk publik ke privat (INF-01)';

    /** modelClass => daftar kolom path file. */
    private const TARGETS = [
        MitraJasaPenjualan::class       => ['file_laporan'],
        MitraJasaPenjualanDetail::class => ['file_laporan'],
        LaporanUtilitas::class          => ['file_bukti_awal', 'file_bukti'],
        KontrakMitraJasa::class         => ['file_kontrak'],
        PemakaianGarbarata::class       => ['file_pendukung'],
        PermohonanNonSchedule::class    => ['file_surat'],
        LogPerubahanTarifPjp2u::class   => ['file_pendukung'],
        KontrakPengadaan::class         => ['file_spk_final_ttd', 'file_spmk_final_ttd', 'file_ringkasan_kontrak_final_ttd', 'file_gambar_rab', 'file_jaminan_uang_muka'],
        RiwayatRevisiDipa::class        => ['file_dokumen_dipa'],
        DetailKontrak::class            => ['file_bapp', 'file_bast', 'file_bap', 'file_invoice', 'file_kwitansi', 'file_faktur_pajak', 'file_lampiran_lainnya'],
        DetailPerjaldin::class          => ['spt_file_path', 'tiket_file_path', 'transport_file_path', 'penginapan_file_path', 'uang_harian_file_path'],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $public = Storage::disk('public');
        $local = Storage::disk('local');

        $moved = 0;
        $missing = 0;
        $failed = 0;
        $candidates = 0;

        foreach (self::TARGETS as $modelClass => $fields) {
            // Lewati "field" yang ternyata accessor (bukan kolom DB nyata) — mis.
            // KontrakPengadaan->file_spk_final_ttd yang me-resolve path dari arsip.
            $table = (new $modelClass)->getTable();
            $fields = array_values(array_filter($fields, fn ($f) => Schema::hasColumn($table, $f)));
            if (empty($fields)) {
                continue;
            }

            $modelClass::query()
                ->where(function ($q) use ($fields) {
                    foreach ($fields as $field) {
                        $q->orWhereNotNull($field);
                    }
                })
                ->chunkById(200, function ($rows) use ($fields, $public, $local, $dryRun, &$moved, &$missing, &$failed, &$candidates) {
                    foreach ($rows as $row) {
                        foreach ($fields as $field) {
                            $path = $row->getAttribute($field);
                            if (blank($path)) {
                                continue;
                            }

                            // Sudah di privat → lewati.
                            if ($local->exists($path)) {
                                continue;
                            }

                            $candidates++;
                            $line = sprintf('%s#%d %s | %s', class_basename($row), $row->id, $field, $path);

                            if (! $public->exists($path)) {
                                $this->warn('[FILE HILANG] ' . $line);
                                $missing++;
                                continue;
                            }

                            if ($dryRun) {
                                $this->line('[DRY] ' . $line);
                                continue;
                            }

                            try {
                                $stream = $public->readStream($path);
                                $local->writeStream($path, $stream);
                                if (is_resource($stream)) {
                                    fclose($stream);
                                }

                                if (! $local->exists($path)) {
                                    throw new \RuntimeException('Salinan privat gagal dibuat.');
                                }

                                $public->delete($path);
                                $this->line('[PINDAH] ' . $line);
                                $moved++;
                            } catch (\Throwable $e) {
                                $this->error('[GAGAL] ' . $line . ' — ' . $e->getMessage());
                                $failed++;
                            }
                        }
                    }
                });
        }

        $this->newLine();
        $this->info(sprintf(
            '%s Kandidat: %d | dipindah: %d | file hilang: %d | gagal: %d',
            $dryRun ? '[DRY-RUN]' : 'Selesai.',
            $candidates,
            $moved,
            $missing,
            $failed,
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
