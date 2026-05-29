<?php

namespace App\Support;

use App\Models\Tagihan;

/**
 * Integritas TTE QR untuk Dokumen Berita Acara Kontrak
 * (BAPP / BAST / BAP).
 *
 * Berbeda dengan TagihanDocumentTte (berbasis workflow_approvals),
 * penandatanganan BA dilakukan via magic link (document_signatures:
 * vendor, tim_pemeriksa, ppk). Hash payload menyertakan snapshot
 * identitas tagihan + seluruh data tanda tangan, sehingga perubahan
 * substansi pasca-penandatanganan otomatis membuat hash tidak cocok.
 */
class ContractBaTte
{
    public const TYPES = ['BAPP', 'BAST', 'BAP'];

    public static function isValidType(string $type): bool
    {
        return in_array(strtoupper($type), self::TYPES, true);
    }

    public static function hashPayload(Tagihan $tagihan, string $type): array
    {
        $signatures = $tagihan->relationLoaded('documentSignatures')
            ? $tagihan->documentSignatures
            : $tagihan->documentSignatures()->get();

        $forType = $signatures
            ->where('document_label', $type)
            ->sortBy('id')
            ->values();

        return [
            'document_type' => 'BERITA_ACARA_' . strtoupper($type),
            'tagihan_id' => $tagihan->getKey(),
            'nomor_tagihan' => $tagihan->nomor_tagihan,
            'signatures' => $forType
                ->map(fn ($sig) => [
                    'id' => $sig->id,
                    'role' => $sig->role,
                    'signer_name' => $sig->signer_name,
                    'status' => $sig->status,
                    'signed_at' => optional($sig->signed_at)->toIso8601String(),
                ])
                ->all(),
        ];
    }

    public static function hash(Tagihan $tagihan, string $type): string
    {
        return hash('sha256', json_encode(
            self::hashPayload($tagihan, $type),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ));
    }
}
