<?php

namespace App\Services;

use App\Models\Tagihan;
use App\Models\TagihanPerjaldinKomponen;
use App\Models\DokumenSpp;
use App\Models\DetailDipa;
use App\Models\LogStatusDokumen;
use Illuminate\Support\Facades\DB;

class PerjaldinKomponenService
{
    /**
     * Mapping kode komponen ke field di detail_perjaldin dan label UI.
     */
    public static function getKomponenMap(): array
    {
        return [
            'TIKET' => [
                'field' => 'biaya_tiket',
                'label' => 'Biaya Tiket',
            ],
            'TRANSPORT' => [
                'field' => 'biaya_transport',
                'label' => 'Biaya Transport',
            ],
            'PENGINAPAN' => [
                'field' => 'biaya_penginapan',
                'label' => 'Biaya Penginapan',
            ],
            'UANG_HARIAN' => [
                'field' => 'uang_harian',
                'label' => 'Uang Harian',
            ],
            'UANG_REPRESENTASI' => [
                'field' => 'uang_representasi',
                'label' => 'Uang Representasi',
            ],
        ];
    }

    /**
     * Periksa apakah tagihan boleh di-rebuild.
     * BLOK jika ada komponen yang sudah memiliki dokumen turunan (SPP/SPM/NPI/SP2D).
     *
     * @throws \RuntimeException
     */
    public function ensureEditableBeforeRebuild(Tagihan $tagihan): void
    {
        $komponens = $tagihan->komponenPerjaldin()
            ->whereHas('dokumenSpp')
            ->with('dokumenSpp')
            ->get();

        if ($komponens->isNotEmpty()) {
            $lockedNames = $komponens->pluck('nama_komponen')->implode(', ');
            throw new \RuntimeException(
                "Tidak dapat mengubah data peserta perjaldin karena komponen berikut sudah memiliki dokumen SPP: {$lockedNames}. "
                . "Batalkan SPP terkait terlebih dahulu sebelum mengedit data peserta."
            );
        }
    }

    /**
     * Rebuild seluruh rekap komponen dari detail_perjaldin.
     * Dipanggil setelah store/update tagihan + detail.
     */
    public function rebuildFromTagihan(Tagihan $tagihan): void
    {
        if ($tagihan->tipe_tagihan !== 'PERJALDIN') {
            throw new \InvalidArgumentException("rebuildFromTagihan hanya untuk tagihan PERJALDIN.");
        }

        $details = $tagihan->detailPerjaldin()->get();
        $map = self::getKomponenMap();

        foreach ($map as $kode => $meta) {
            $field = $meta['field'];
            $label = $meta['label'];

            // Hitung total nominal komponen ini
            $totalNominal = $details->sum(function ($detail) use ($field) {
                return (float) ($detail->{$field} ?? 0);
            });

            // Hitung jumlah peserta yang nilai komponen ini > 0
            $jumlahPeserta = $details->filter(function ($detail) use ($field) {
                return (float) ($detail->{$field} ?? 0) > 0;
            })->count();

            if ($totalNominal > 0) {
                // Upsert komponen
                $komponen = TagihanPerjaldinKomponen::updateOrCreate(
                    [
                        'tagihan_id' => $tagihan->id,
                        'kode_komponen' => $kode,
                    ],
                    [
                        'nama_komponen' => $label,
                        'total_nominal' => $totalNominal,
                        'jumlah_peserta' => $jumlahPeserta,
                    ]
                );

                // Sync status berdasarkan dokumen turunan
                $komponen->syncStatusFromDocuments();
            } else {
                // Total = 0 → hapus record HANYA jika belum punya SPP
                $existing = TagihanPerjaldinKomponen::where('tagihan_id', $tagihan->id)
                    ->where('kode_komponen', $kode)
                    ->first();

                if ($existing && !$existing->hasDokumenTurunan()) {
                    $existing->delete();
                } elseif ($existing) {
                    // Sudah punya SPP tapi total jadi 0 — update nominal saja, jangan hapus
                    $existing->update([
                        'total_nominal' => 0,
                        'jumlah_peserta' => 0,
                    ]);
                }
            }
        }
    }

    /**
     * Sinkronisasi status satu komponen berdasarkan dokumen turunan.
     */
    public function syncKomponenStatus(TagihanPerjaldinKomponen $komponen): string
    {
        return $komponen->syncStatusFromDocuments();
    }

    /**
     * Update COA (dipa_revision_item_id) untuk satu komponen.
     * Jika total_nominal > 0 dan belum ada SPP, status → SIAP_SPP.
     *
     * @throws \RuntimeException
     */
    public function updateKomponenCoa(TagihanPerjaldinKomponen $komponen, int $dipaRevisionItemId): void
    {
        // Validasi item DIPA
        $item = DetailDipa::where('id', $dipaRevisionItemId)
            ->where('status_aktif', true)
            ->whereHas('coa')
            ->first();

        if (!$item) {
            throw new \RuntimeException("Item DIPA/COA yang dipilih tidak valid atau tidak aktif.");
        }

        $komponen->update([
            'dipa_revision_item_id' => $dipaRevisionItemId,
        ]);

        // Update status jika memenuhi syarat
        if ((float) $komponen->total_nominal > 0 && !$komponen->hasDokumenTurunan()) {
            $komponen->update(['status_proses' => TagihanPerjaldinKomponen::STATUS_SIAP_BUAT_SPP]);
        }
    }

    /**
     * Buat DokumenSpp dari satu komponen.
     *
     * @throws \RuntimeException
     */
    public function createSppFromKomponen(TagihanPerjaldinKomponen $komponen, int $userId): DokumenSpp
    {
        // Business rule validation
        if (!$komponen->dipa_revision_item_id) {
            throw new \RuntimeException("COA belum dipilih untuk komponen {$komponen->nama_komponen}.");
        }

        if ((float) $komponen->total_nominal <= 0) {
            throw new \RuntimeException("Total nominal komponen {$komponen->nama_komponen} adalah 0 atau negatif.");
        }

        if ($komponen->hasDokumenTurunan()) {
            throw new \RuntimeException("Komponen {$komponen->nama_komponen} sudah memiliki SPP aktif.");
        }

        $tagihan = $komponen->tagihan;

        // Generate nomor SPP
        $tahun = date('Y');
        $count = DokumenSpp::whereYear('created_at', $tahun)->count() + 1;
        $nomorSpp = 'SPP-PJD-' . $komponen->kode_komponen . '-' . $tahun . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        $spp = DokumenSpp::create([
            'tagihan_id' => $tagihan->id,
            'tagihan_perjaldin_komponen_id' => $komponen->id,
            'komponen_biaya' => $komponen->kode_komponen,
            'dipa_revision_item_id' => $komponen->dipa_revision_item_id,
            'kategori_pembayaran' => 'SP2D BLU - TRF',
            'jenis_tagihan' => 'NON REMUNERASI',
            'nominal_spp' => $komponen->total_nominal,
            'nomor_spp' => $nomorSpp,
            'tanggal_spp' => now()->toDateString(),
            'status' => 'DRAFT',
            'dibuat_oleh_id' => $userId,
        ]);

        // Update status komponen
        $komponen->update(['status_proses' => TagihanPerjaldinKomponen::STATUS_SPP_DRAFT]);

        // Log
        LogStatusDokumen::create([
            'dokumen_type' => DokumenSpp::class,
            'dokumen_id' => $spp->id,
            'user_id' => $userId,
            'role_saat_itu' => auth()->user()?->getRoleNames()?->first() ?? 'Operator',
            'status_sebelumnya' => null,
            'status_baru' => 'DRAFT',
            'aksi' => 'CREATE_SPP_KOMPONEN',
            'catatan' => "SPP dibuat untuk komponen {$komponen->nama_komponen} dari Perjaldin {$tagihan->nomor_tagihan}.",
            'ip_address' => request()->ip(),
        ]);

        return $spp;
    }
}
