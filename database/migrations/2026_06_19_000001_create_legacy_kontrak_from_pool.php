<?php

use App\Models\KontrakMitraJasa;
use App\Models\MitraJasa;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Transisi "kontrak wajib": setiap mitra yang punya pool layanan (mitra_jasa_layanan)
 * tetapi belum tercakup kontrak, dibuatkan satu "Kontrak Legacy" otomatis berisi
 * layanan yang belum tercakup. Dibuat AKTIF agar pool & penagihan tidak putus saat
 * pool nantinya diturunkan dari kontrak; ditandai LEGACY-* + keterangan agar admin
 * melengkapi nomor/jenis/periode/file yang sebenarnya. Idempoten.
 */
return new class extends Migration
{
    public function up(): void
    {
        $today = now();

        MitraJasa::query()
            ->with([
                'layananJasa' => fn ($q) => $q->wherePivot('status_aktif', true)
                    ->where('layanan_jasas.is_active', true)
                    ->where('layanan_jasas.is_leaf', true),
                'kontrak.layananJasa',
            ])
            ->chunk(100, function ($mitras) use ($today) {
                foreach ($mitras as $mitra) {
                    $poolIds = $mitra->layananJasa->pluck('id')->map(fn ($i) => (int) $i)->unique();
                    if ($poolIds->isEmpty()) {
                        continue;
                    }

                    $coveredIds = $mitra->kontrak
                        ->flatMap(fn ($k) => $k->layananJasa->pluck('id'))
                        ->map(fn ($i) => (int) $i)
                        ->unique();

                    $missing = $poolIds->diff($coveredIds)->values();
                    if ($missing->isEmpty()) {
                        continue;
                    }

                    $nomor = 'LEGACY-' . ($mitra->kode_mitra ?: $mitra->id);

                    $kontrak = KontrakMitraJasa::firstOrCreate(
                        ['mitra_jasa_id' => $mitra->id, 'nomor_kontrak' => $nomor],
                        [
                            'nama_kontrak' => 'Kontrak Legacy (perlu dilengkapi) - ' . $mitra->nama_mitra,
                            'jenis_dokumen' => 'DOKUMEN_LAINNYA',
                            'tanggal_kontrak' => $today->toDateString(),
                            'tanggal_mulai' => $today->toDateString(),
                            'tanggal_selesai' => $today->copy()->addYears(5)->toDateString(),
                            'status_kontrak' => 'AKTIF',
                            'keterangan' => 'Dibuat otomatis dari pool layanan mitra saat transisi "kontrak wajib". '
                                . 'Mohon lengkapi nomor, jenis dokumen, periode, dan file kontrak yang sebenarnya.',
                        ],
                    );

                    $sync = $missing->mapWithKeys(fn ($id) => [$id => ['created_by' => null]])->all();
                    $kontrak->layananJasa()->syncWithoutDetaching($sync);
                }
            });
    }

    public function down(): void
    {
        $ids = DB::table('kontrak_mitra_jasa')
            ->where('nomor_kontrak', 'like', 'LEGACY-%')
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('kontrak_mitra_jasa_layanan')->whereIn('kontrak_mitra_jasa_id', $ids)->delete();
            DB::table('kontrak_mitra_jasa')->whereIn('id', $ids)->delete();
        }
    }
};
