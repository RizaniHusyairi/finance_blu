<?php

namespace App\Http\Controllers;

use App\Models\KontrakMitraJasa;
use App\Models\KontrakPengadaan;
use App\Models\LaporanUtilitas;
use App\Models\MitraJasa;
use App\Models\MitraJasaPenjualan;
use App\Models\MitraJasaPenjualanDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * INF-01 — penyaji file dokumen (kolom model) via STREAMING TERPROTEKSI,
 * menggantikan tautan publik mentah `asset('storage/...')` yang dapat diakses
 * tanpa login. Route selalu di balik middleware auth; dokumen milik mitra hanya
 * dapat diakses oleh staf internal atau mitra pemiliknya.
 *
 * File baru disimpan di disk privat `local`. Untuk file lama yang belum
 * dimigrasi, serving mem-fallback ke disk `public` agar tidak ada akses yang
 * putus selama masa transisi (lihat command `arsip:secure-private` & migrasi
 * kolom file).
 */
class SecureFileController extends Controller
{
    /**
     * Registry kind => [modelClass, allowedFields, ownerMode].
     * ownerMode: 'mitra' (kolom mitra_jasa_id), 'parent' (via relasi penjualan).
     * Kind tanpa mitra_jasa_id (mis. dokumen internal) otomatis hanya bisa
     * diakses staf internal karena akun mitra gagal cek kepemilikan.
     */
    private const REGISTRY = [
        'penjualan'        => [MitraJasaPenjualan::class, ['file_laporan'], 'mitra'],
        'penjualan-detail' => [MitraJasaPenjualanDetail::class, ['file_laporan'], 'parent'],
        'utilitas'         => [LaporanUtilitas::class, ['file_bukti_awal', 'file_bukti'], 'mitra'],
        'kontrak-mitra'    => [KontrakMitraJasa::class, ['file_kontrak'], 'mitra'],
        // Dokumen internal (tanpa mitra_jasa_id) → otomatis hanya staf internal.
        'kontrak-pengadaan' => [KontrakPengadaan::class, ['file_spk_final_ttd', 'file_gambar_rab', 'file_jaminan_uang_muka'], 'mitra'],
    ];

    public function show(string $kind, int $id, string $field)
    {
        $conf = self::REGISTRY[$kind] ?? null;
        abort_if($conf === null, 404);

        [$modelClass, $fields, $ownerMode] = $conf;
        abort_unless(in_array($field, $fields, true), 404);

        /** @var Model $model */
        $model = $modelClass::findOrFail($id);
        $this->authorizeAccess($model, $ownerMode);

        $path = $model->getAttribute($field);
        abort_unless(filled($path), 404);

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->response($path, basename((string) $path));
            }
        }

        abort(404, 'File tidak ditemukan.');
    }

    private function authorizeAccess(Model $model, string $ownerMode): void
    {
        $profile = auth()->user()?->profilable;

        // Staf internal (akun non-mitra) boleh mengakses seluruh dokumen.
        if (! $profile instanceof MitraJasa) {
            return;
        }

        // Akun mitra hanya boleh mengakses dokumen miliknya sendiri.
        $ownerMitraId = $ownerMode === 'parent'
            ? $model->penjualan?->mitra_jasa_id
            : $model->getAttribute('mitra_jasa_id');

        abort_unless(
            $ownerMitraId !== null && (int) $ownerMitraId === (int) $profile->id,
            403,
            'Anda tidak berwenang mengakses dokumen ini.'
        );
    }
}
