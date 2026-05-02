<?php

namespace App\Services;

use App\Models\DokumenSp2d;
use App\Models\RealisasiAnggaran;
use Illuminate\Support\Facades\DB;
use Exception;

class BudgetRealizationService
{
    /**
     * Record budget realization when SP2D is executed/finalized.
     * 
     * @param DokumenSp2d $sp2d
     * @return bool
     * @throws Exception
     */
    public function recordFromSp2d(DokumenSp2d $sp2d)
    {
        DB::beginTransaction();
        try {
            // Lock SP2D to prevent race conditions when double clicking
            $lockedSp2d = DokumenSp2d::where('id', $sp2d->id)->lockForUpdate()->first();

            if (!$lockedSp2d) {
                throw new Exception("Dokumen SP2D tidak ditemukan.");
            }

            // Cek apakah SP2D ini sudah pernah dicatat realisasinya
            $exists = RealisasiAnggaran::where('dokumen_sp2d_id', $lockedSp2d->id)
                ->where('status', 'TERCATAT')
                ->exists();
                
            if ($exists) {
                DB::rollBack();
                return true; // Cegah double posting
            }

            // Traversal rantai dokumen: Sp2d -> Npi -> Spm -> Spp
            $lockedSp2d->load(['npi.spm.spp.tagihan', 'npi.spm.spp.tagihanPerjaldinKomponen']);
            
            $npi = $lockedSp2d->npi;
            $spm = $npi ? $npi->spm : null;
            $spp = $spm ? $spm->spp : null;
            
            if (!$spp) {
                throw new Exception("Dokumen SPP tidak ditemukan dari rantai SP2D ini.");
            }

            // Dapatkan detail dipa dari SPP jika ada, kalau tidak cari dari Tagihan/Komponen
            $dipaItemId = $spp->dipa_revision_item_id;

            // Mapping berdasarkan sumber
            if ($spp->tagihan_perjaldin_komponen_id && $spp->tagihanPerjaldinKomponen) {
                // Pencatatan untuk Perjaldin Komponen
                $komponen = $spp->tagihanPerjaldinKomponen;
                
                // Prioritaskan DIPA item ID
                $dipaItemId = $dipaItemId ?? $komponen->dipa_revision_item_id;
                
                if (!$dipaItemId) {
                    throw new Exception("DIPA Revision Item ID tidak ditemukan pada Komponen Perjaldin.");
                }

                $this->createRealization(
                    sp2d: $lockedSp2d,
                    dipaItemId: $dipaItemId,
                    source: $komponen,
                    nominal: $spp->nominal_spp ?? $komponen->total_nominal, // Sebaiknya ikuti nominal akhir di SPP
                    tanggal: $lockedSp2d->tanggal_sp2d ?? now()
                );
            } elseif ($spp->tagihan_id && $spp->tagihan) {
                // Pencatatan untuk Tagihan Kontrak / Honorarium
                $tagihan = $spp->tagihan;
                
                $dipaItemId = $dipaItemId ?? $tagihan->dipa_revision_item_id;

                if (!$dipaItemId) {
                    throw new Exception("DIPA Revision Item ID tidak ditemukan pada Tagihan.");
                }

                $this->createRealization(
                    sp2d: $lockedSp2d,
                    dipaItemId: $dipaItemId,
                    source: $tagihan,
                    nominal: $spp->nominal_spp ?? $tagihan->nominal_bruto,
                    tanggal: $lockedSp2d->tanggal_sp2d ?? now()
                );
            } else {
                throw new Exception("Dokumen SPP tidak memiliki referensi ke Tagihan atau Komponen Perjaldin.");
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Rollback/Cancel budget realization if SP2D is cancelled.
     * 
     * @param DokumenSp2d $sp2d
     * @return bool
     */
    public function rollbackRealization(DokumenSp2d $sp2d)
    {
        DB::beginTransaction();
        try {
            $realizations = RealisasiAnggaran::where('dokumen_sp2d_id', $sp2d->id)->get();
            
            foreach ($realizations as $realization) {
                $realization->update(['status' => 'DIBATALKAN']);
                $realization->delete(); // Soft delete if trait SoftDeletes is used
            }
            
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Insert a row to realisasi_anggaran
     */
    private function createRealization($sp2d, $dipaItemId, $source, $nominal, $tanggal)
    {
        // Try to find the master_coa_id from DetailDipa if possible
        $masterCoaId = null;
        $detailDipa = \App\Models\DetailDipa::find($dipaItemId);
        if ($detailDipa && isset($detailDipa->master_coa_id)) {
            $masterCoaId = $detailDipa->master_coa_id;
        }

        RealisasiAnggaran::create([
            'dokumen_sp2d_id' => $sp2d->id,
            'dipa_revision_item_id' => $dipaItemId,
            'master_coa_id' => $masterCoaId,
            'sourceable_id' => $source->id,
            'sourceable_type' => get_class($source),
            'tanggal_pencairan' => $tanggal,
            'nominal_cair' => $nominal,
            'status' => 'TERCATAT',
            'created_by' => auth()->id() ?? 1,
        ]);
    }
}
