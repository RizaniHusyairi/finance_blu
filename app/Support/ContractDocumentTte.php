<?php

namespace App\Support;

use App\Models\KontrakPengadaan;
use Illuminate\Support\Facades\URL;

/**
 * TTE QR untuk dokumen turunan Kontrak Pengadaan (SPK, SPMK, Ringkasan Kontrak).
 *
 * Berbeda dengan App\Support\DocumentTte (SPP/SPM/NPI/SP2D yang berbasis
 * workflow instance), satu KontrakPengadaan menghasilkan beberapa dokumen.
 * QR hanya aktif setelah kontrak disetujui PPK (status AKTIF + ppk_approved_at).
 */
class ContractDocumentTte
{
    public const LABELS = [
        'spk' => 'SPK',
        'spmk' => 'SPMK',
        'ringkasan_kontrak' => 'Ringkasan Kontrak',
    ];

    public static function isValidType(string $type): bool
    {
        return array_key_exists($type, self::LABELS);
    }

    public static function labelFor(string $type): string
    {
        return self::LABELS[$type] ?? strtoupper($type);
    }

    public static function isApproved(KontrakPengadaan $kontrak): bool
    {
        return $kontrak->isTteApproved();
    }

    public static function numberFor(KontrakPengadaan $kontrak, string $type): ?string
    {
        return match ($type) {
            'spmk' => $kontrak->nomor_spmk,
            default => $kontrak->nomor_spk,
        };
    }

    public static function hashPayload(KontrakPengadaan $kontrak, string $type): array
    {
        return [
            'document_type' => 'KONTRAK_' . strtoupper($type),
            'kontrak_id' => $kontrak->getKey(),
            'nomor_spk' => $kontrak->nomor_spk,
            'nomor_spmk' => $kontrak->nomor_spmk,
            'nama_pekerjaan' => $kontrak->nama_pekerjaan,
            'nilai_total_kontrak' => (string) $kontrak->nilai_total_kontrak,
            'status_kontrak' => $kontrak->status_kontrak,
            'ppk_approved_by' => $kontrak->ppk_approved_by,
            'ppk_approved_at' => optional($kontrak->ppk_approved_at)->toIso8601String(),
        ];
    }

    public static function hash(KontrakPengadaan $kontrak, string $type): string
    {
        return hash('sha256', json_encode(
            self::hashPayload($kontrak, $type),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ));
    }

    /**
     * Path file SVG QR TTE; null bila kontrak belum disetujui PPK.
     */
    public static function tteQrFilePath(KontrakPengadaan $kontrak, string $type): ?string
    {
        if (! self::isApproved($kontrak)) {
            return null;
        }

        $url = URL::signedRoute('public.contract-tte.show', [
            'type' => $type,
            'id' => $kontrak->getKey(),
            'hash' => self::hash($kontrak, $type),
        ]);

        // Pakai ulang generator cache SVG milik DocumentTte.
        return DocumentTte::qrFilePath($url, 'kontrak_' . $type . '_tte_' . $kontrak->getKey());
    }
}
