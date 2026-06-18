<?php

use App\Models\LayananJasa;
use App\Models\LayananJasaTarif;
use App\Models\LogPerubahanTarifPjp2u;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Koreksi data lama: sebelum perbaikan, diskon PJP2U mengubah tarif_dasar secara
 * permanen sehingga tidak pernah kembali normal. Migrasi ini mengembalikan
 * tarif_dasar ke nilai normal (tarif_lama saat diskon dibuat) untuk layanan yang
 * tarif_dasar-nya masih "nyangkut" di nilai diskon. Bila diskonnya masih dalam masa
 * berlaku, diskon disimpan ulang sebagai periode (LayananJasaTarif) agar tetap
 * berlaku sampai berakhir lalu otomatis kembali normal. Idempoten.
 */
return new class extends Migration
{
    public function up(): void
    {
        $today = now()->startOfDay();

        $leaves = LayananJasa::with('parent.parent.parent')
            ->where('is_leaf', true)
            ->get()
            ->filter(fn (LayananJasa $l) => $l->isPjp2u());

        foreach ($leaves as $layanan) {
            $latest = LogPerubahanTarifPjp2u::where('layanan_jasa_id', $layanan->id)
                ->orderByDesc('berlaku_mulai')
                ->orderByDesc('id')
                ->first();

            if (! $latest || $latest->tipe_perubahan !== LogPerubahanTarifPjp2u::TIPE_DISKON) {
                continue;
            }

            $tarifDasar = (float) $layanan->tarif_dasar;
            $tarifBaru = (float) $latest->tarif_baru;
            $tarifNormal = (float) $latest->tarif_lama;

            // Hanya koreksi bila tarif dasar masih nyangkut di nilai diskon.
            if (abs($tarifDasar - $tarifBaru) > 0.01) {
                continue;
            }

            DB::table('layanan_jasas')->where('id', $layanan->id)
                ->update(['tarif_dasar' => $tarifNormal]);

            $masihAktif = $latest->berlaku_mulai && $latest->berlaku_mulai->lte($today)
                && $latest->berlaku_sampai && $latest->berlaku_sampai->gte($today);

            $sudahAdaPeriode = LayananJasaTarif::where('layanan_jasa_id', $layanan->id)
                ->whereDate('berlaku_mulai', optional($latest->berlaku_mulai)->toDateString())
                ->exists();

            if ($masihAktif && ! $sudahAdaPeriode) {
                LayananJasaTarif::create([
                    'layanan_jasa_id' => $layanan->id,
                    'mitra_jasa_id' => null,
                    'tarif' => $tarifBaru,
                    'persen_diskon' => $tarifNormal > 0 ? round((($tarifNormal - $tarifBaru) / $tarifNormal) * 100, 2) : null,
                    'berlaku_mulai' => $latest->berlaku_mulai->toDateString(),
                    'berlaku_sampai' => $latest->berlaku_sampai->toDateString(),
                    'keterangan' => $latest->alasan,
                    'is_active' => true,
                    'created_by' => null,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Koreksi data — tidak di-rollback otomatis.
    }
};
