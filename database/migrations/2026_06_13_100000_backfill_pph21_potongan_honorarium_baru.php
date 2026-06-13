<?php

use App\Models\MasterTarifPajak;
use App\Models\PotonganTagihan;
use App\Models\Tagihan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill kedua: baris potongan_tagihan PPh 21 untuk tagihan HONORARIUM yang
 * dibuat SETELAH migration 2026_05_30 (saat itu HonorariumController belum
 * membentuk baris pajak otomatis). Tanpa baris ini, honor ber-PPh tidak muncul
 * di seksi Penyetoran Pajak meski detail_honorarium.pph > 0.
 *
 * Mulai sekarang baris dibentuk oleh HonorariumController::syncPotonganPph saat
 * store/update; migration ini hanya menambal data lama yang sudah terlanjur ada.
 * Hanya membuat baris yang belum ada (idempotent).
 */
return new class extends Migration
{
    public function up(): void
    {
        $pajak = MasterTarifPajak::where('status_aktif', true)
            ->where('kode_pajak', 'PPH21-TER')
            ->first()
            ?? MasterTarifPajak::where('status_aktif', true)
                ->where('jenis_pajak', 'like', '%21%')
                ->orderByDesc('berlaku_mulai')
                ->first();

        if (! $pajak) {
            return; // Tidak ada tarif PPh 21 aktif; lewati.
        }

        $tagihanList = Tagihan::where('tipe_tagihan', 'HONORARIUM')
            ->withCount(['potonganTagihan as pajak_count' => function ($q) {
                $q->where('jenis_potongan', 'PAJAK');
            }])
            ->get();

        foreach ($tagihanList as $tagihan) {
            if ($tagihan->pajak_count > 0) {
                continue; // sudah punya baris pajak
            }

            $details = $tagihan->detailHonorarium()->get();
            $totalPph = (float) $details->sum('pph');
            $totalDpp = (float) $details->sum('nilai_honor');

            if ($totalPph <= 0) {
                continue; // tidak ada PPh untuk disetor
            }

            DB::transaction(function () use ($tagihan, $pajak, $totalPph, $totalDpp) {
                PotonganTagihan::create([
                    'tagihan_id' => $tagihan->id,
                    'pajak_id' => $pajak->id,
                    'jenis_potongan' => 'PAJAK',
                    'deskripsi' => 'PPh 21 honorarium',
                    'dpp' => max(0, $totalDpp),
                    'persentase_tarif_snapshot' => $pajak->persentase,
                    'nama_pajak_snapshot' => $pajak->jenis_pajak,
                    'nominal_potongan' => $totalPph,
                ]);

                // Sinkronkan total tagihan agar konsisten.
                $totalBruto = (float) ($tagihan->total_bruto ?: $totalDpp);
                $tagihan->update([
                    'total_potongan' => $totalPph,
                    'total_netto' => max(0, $totalBruto - $totalPph),
                ]);
            });
        }
    }

    public function down(): void
    {
        // Tidak menghapus baris pajak pada rollback untuk mencegah kehilangan data setoran.
    }
};
