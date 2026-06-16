<?php

namespace App\Services;

use App\Models\LayananJasa;
use App\Models\LogPerubahanTarifPjp2u;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Pjp2uTariffLogService
{
    public const STORAGE_DISK = 'public';
    public const STORAGE_DIR = 'log_tarif_pjp2u';

    public function isPjp2u(LayananJasa $layanan): bool
    {
        // PJP2U leaves live under the PJP2U branch. We detect via the breadcrumb
        // (same heuristic used by LayananJasa::isPjp2u) plus require leaf+active.
        if (! $layanan->is_leaf) {
            return false;
        }

        return $layanan->isPjp2u();
    }

    public function record(LayananJasa $layanan, float $tarifLama, float $tarifBaru, array $payload, ?UploadedFile $file = null): LogPerubahanTarifPjp2u
    {
        $filePath = null;
        if ($file) {
            $filePath = $file->store(self::STORAGE_DIR, self::STORAGE_DISK);
        }

        return LogPerubahanTarifPjp2u::create([
            'layanan_jasa_id' => $layanan->id,
            'tarif_lama' => $tarifLama,
            'tarif_baru' => $tarifBaru,
            'tipe_perubahan' => $payload['tipe_perubahan'],
            'berlaku_mulai' => Carbon::parse($payload['berlaku_mulai'])->toDateString(),
            'berlaku_sampai' => ! empty($payload['berlaku_sampai'])
                ? Carbon::parse($payload['berlaku_sampai'])->toDateString()
                : null,
            'nomor_referensi' => $payload['nomor_referensi'] ?? null,
            'alasan' => $payload['alasan'],
            'file_pendukung' => $filePath,
            'created_by' => Auth::id(),
        ]);
    }

    public function latestForLayanan(int $layananId): ?LogPerubahanTarifPjp2u
    {
        return LogPerubahanTarifPjp2u::where('layanan_jasa_id', $layananId)
            ->orderByDesc('berlaku_mulai')
            ->orderByDesc('id')
            ->first();
    }
}
