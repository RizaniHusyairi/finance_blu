<?php

namespace App\Support;

use App\Models\Tagihan;
use Illuminate\Support\Facades\URL;

/**
 * TTE QR untuk dokumen turunan Tagihan Perjaldin & Honorarium
 * (Nominatif Perjaldin, Daftar Nominatif Pembayaran Perjaldin,
 *  Rekap Honorarium, Nominatif Honorarium).
 *
 * Sumber kebenaran: workflow_approvals milik Tagihan. QR aktif
 * setelah WorkflowInstance berstatus APPROVED dan setiap approval
 * APPROVED. Hash payload menyertakan snapshot tagihan + daftar
 * approver, sehingga perubahan substansi pasca-approval otomatis
 * membuat QR tidak match (terdeteksi sebagai dokumen telah diubah).
 */
class TagihanDocumentTte
{
    public const LABELS = [
        'nominatif_perjaldin' => 'Nominatif Perjalanan Dinas',
        'daftar_nominatif_pembayaran_perjaldin' => 'Daftar Nominatif Pembayaran Perjalanan Dinas',
        'rekap_honorarium' => 'Rekap Honorarium',
        'nominatif_honorarium' => 'Nominatif Honorarium',
    ];

    public const PERJALDIN_TYPES = ['nominatif_perjaldin', 'daftar_nominatif_pembayaran_perjaldin'];
    public const HONORARIUM_TYPES = ['rekap_honorarium', 'nominatif_honorarium'];

    public static function isValidType(string $type): bool
    {
        return array_key_exists($type, self::LABELS);
    }

    public static function typesFor(string $tipeTagihan): array
    {
        return match (strtoupper($tipeTagihan)) {
            'PERJALDIN' => self::PERJALDIN_TYPES,
            'HONORARIUM' => self::HONORARIUM_TYPES,
            default => [],
        };
    }

    public static function labelFor(string $type): string
    {
        return self::LABELS[$type] ?? strtoupper($type);
    }

    /**
     * Tagihan dianggap layak menerima TTE QR ketika workflow
     * instance-nya APPROVED dan semua approval di dalamnya APPROVED.
     */
    public static function isApproved(Tagihan $tagihan): bool
    {
        $workflow = $tagihan->relationLoaded('workflowInstance')
            ? $tagihan->workflowInstance
            : $tagihan->workflowInstance()->with('approvals')->first();

        if (! $workflow || $workflow->status !== 'APPROVED') {
            return false;
        }

        $approvals = $workflow->relationLoaded('approvals')
            ? $workflow->approvals
            : $workflow->approvals()->get();

        return $approvals->isNotEmpty()
            && $approvals->every(fn ($approval) => $approval->status === 'APPROVED');
    }

    public static function numberFor(Tagihan $tagihan, string $type): ?string
    {
        return $tagihan->nomor_tagihan;
    }

    public static function hashPayload(Tagihan $tagihan, string $type): array
    {
        $workflow = $tagihan->relationLoaded('workflowInstance')
            ? $tagihan->workflowInstance
            : $tagihan->workflowInstance()->with('approvals')->first();

        $approvals = $workflow
            ? ($workflow->relationLoaded('approvals') ? $workflow->approvals : $workflow->approvals()->get())
            : collect();

        return [
            'document_type' => 'TAGIHAN_' . strtoupper($type),
            'tagihan_id' => $tagihan->getKey(),
            'nomor_tagihan' => $tagihan->nomor_tagihan,
            'tipe_tagihan' => $tagihan->tipe_tagihan,
            'periode_bulan' => $tagihan->periode_bulan,
            'periode_tahun' => $tagihan->periode_tahun,
            'total_bruto' => (string) $tagihan->total_bruto,
            'status' => $tagihan->status,
            'workflow_id' => $workflow?->id,
            'workflow_status' => $workflow?->status,
            'approvals' => $approvals
                ->sortBy(fn ($approval) => sprintf('%010d-%010d', (int) $approval->urutan_step, (int) $approval->id))
                ->map(fn ($approval) => [
                    'step' => $approval->urutan_step,
                    'role' => $approval->role_code,
                    'status' => $approval->status,
                    'acted_by_user_id' => $approval->acted_by_user_id,
                    'acted_at' => optional($approval->acted_at)->toIso8601String(),
                ])
                ->values()
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

    /**
     * Path file SVG QR TTE; null bila tagihan belum APPROVED penuh
     * di workflow-nya, atau tipe dokumen tidak berlaku untuk tipe tagihan.
     *
     * Bila $signerRole diisi (mis. 'PPK', 'BENDAHARA_PENGELUARAN'), QR yang
     * dihasilkan unik per penandatangan: query param `signer` ikut di-sign,
     * file cache QR juga disimpan terpisah.
     */
    public static function tteQrFilePath(Tagihan $tagihan, string $type, ?string $signerRole = null): ?string
    {
        if (! self::isValidType($type)) {
            return null;
        }

        if (! in_array($type, self::typesFor($tagihan->tipe_tagihan), true)) {
            return null;
        }

        if (! self::isApproved($tagihan)) {
            return null;
        }

        $params = [
            'type' => $type,
            'id' => $tagihan->getKey(),
            'hash' => self::hash($tagihan, $type),
        ];

        $cacheSuffix = '';
        if ($signerRole) {
            $params['signer'] = strtolower($signerRole);
            $cacheSuffix = '_' . strtolower($signerRole);
        }

        $url = URL::signedRoute('public.tagihan-tte.show', $params);

        return DocumentTte::qrFilePath($url, 'tagihan_' . $type . $cacheSuffix . '_tte_' . $tagihan->getKey());
    }
}
