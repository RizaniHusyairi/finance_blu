<?php

namespace App\Support;

use App\Models\DokumenNpi;
use App\Models\DokumenSp2d;
use App\Models\DokumenSpm;
use App\Models\DokumenSpp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DocumentTte
{
    public static function typeFor(Model $document): string
    {
        return match (true) {
            $document instanceof DokumenSpp => 'spp',
            $document instanceof DokumenSpm => 'spm',
            $document instanceof DokumenNpi => 'npi',
            $document instanceof DokumenSp2d => 'sp2d',
            default => 'dokumen',
        };
    }

    public static function labelFor(Model $document): string
    {
        return strtoupper(self::typeFor($document));
    }

    public static function numberFor(Model $document): ?string
    {
        return match (true) {
            $document instanceof DokumenSpp => $document->nomor_spp,
            $document instanceof DokumenSpm => $document->nomor_spm,
            $document instanceof DokumenNpi => $document->nomor_npi,
            $document instanceof DokumenSp2d => $document->nomor_sp2d,
            default => null,
        };
    }

    public static function dateFor(Model $document)
    {
        return match (true) {
            $document instanceof DokumenSpp => $document->tanggal_spp,
            $document instanceof DokumenSpm => $document->tanggal_spm,
            $document instanceof DokumenNpi => $document->tanggal_npi,
            $document instanceof DokumenSp2d => $document->tanggal_sp2d,
            default => null,
        };
    }

    public static function amountFor(Model $document): float
    {
        return (float) match (true) {
            $document instanceof DokumenSpp => $document->nominal_spp,
            $document instanceof DokumenSpm => $document->nominal_spm ?: $document->spp?->nominal_spp,
            $document instanceof DokumenNpi => $document->spm?->spp?->nominal_spp,
            $document instanceof DokumenSp2d => $document->npi?->spm?->spp?->nominal_spp,
            default => 0,
        };
    }

    public static function tagihanIdFor(Model $document): ?int
    {
        return match (true) {
            $document instanceof DokumenSpp => $document->tagihan_id ?? $document->tagihan?->id,
            $document instanceof DokumenSpm => $document->spp?->tagihan_id ?? $document->spp?->tagihan?->id,
            $document instanceof DokumenNpi => $document->spm?->spp?->tagihan_id ?? $document->spm?->spp?->tagihan?->id,
            $document instanceof DokumenSp2d => $document->npi?->spm?->spp?->tagihan_id ?? $document->npi?->spm?->spp?->tagihan?->id,
            default => null,
        };
    }

    public static function isFullyVerified(Model $document): bool
    {
        $workflow = $document->relationLoaded('workflowInstance')
            ? $document->workflowInstance
            : $document->workflowInstance()->with('approvals')->first();

        if (! $workflow || $workflow->status !== 'APPROVED') {
            return false;
        }

        $approvals = $workflow->relationLoaded('approvals')
            ? $workflow->approvals
            : $workflow->approvals()->get();

        return $approvals->isNotEmpty()
            && $approvals->every(fn ($approval) => $approval->status === 'APPROVED');
    }

    public static function hashPayload(Model $document): array
    {
        $workflow = $document->relationLoaded('workflowInstance')
            ? $document->workflowInstance
            : $document->workflowInstance()->with('approvals')->first();

        $approvals = $workflow
            ? ($workflow->relationLoaded('approvals') ? $workflow->approvals : $workflow->approvals()->get())
            : collect();

        return [
            'document_type' => self::labelFor($document),
            'document_id' => $document->getKey(),
            'document_number' => self::numberFor($document),
            'document_date' => optional(self::dateFor($document))->toDateString(),
            'tagihan_id' => self::tagihanIdFor($document),
            'amount' => (string) self::amountFor($document),
            'status' => $document->status ?? null,
            'workflow_id' => $workflow?->id,
            'workflow_status' => $workflow?->status,
            'approvals' => $approvals
                ->sortBy(fn ($approval) => sprintf('%010d-%010d', (int) $approval->urutan_step, (int) $approval->id))
                ->map(fn ($approval) => [
                    'id' => $approval->id,
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

    public static function hash(Model $document): string
    {
        return hash('sha256', json_encode(self::hashPayload($document), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public static function activityQrFilePath(?int $tagihanId): ?string
    {
        if (! $tagihanId) {
            return null;
        }

        $url = URL::signedRoute('public.tagihan.aktivitas', ['id' => $tagihanId]);

        return self::qrFilePath($url, 'tagihan_' . $tagihanId);
    }

    public static function tteQrFilePath(Model $document, ?string $signer = null): ?string
    {
        if (! self::isFullyVerified($document)) {
            return null;
        }

        $type = self::typeFor($document);

        $params = [
            'type' => $type,
            'id' => $document->getKey(),
            'hash' => self::hash($document),
        ];

        if ($signer) {
            $params['signer'] = $signer;
        }

        $url = URL::signedRoute('public.document-tte.show', $params);

        $prefix = $type . '_tte_' . $document->getKey() . ($signer ? '_' . $signer : '');

        return self::qrFilePath($url, $prefix);
    }

    public static function qrFilePath(string $url, string $filePrefix): string
    {
        $qrCacheDir = storage_path('app/qr-cache');
        if (! is_dir($qrCacheDir)) {
            @mkdir($qrCacheDir, 0775, true);
        }

        $qrFilePath = $qrCacheDir . DIRECTORY_SEPARATOR . $filePrefix . '_' . md5($url) . '.svg';
        if (! file_exists($qrFilePath)) {
            $qrSvg = (string) QrCode::format('svg')
                ->size(300)->margin(1)->errorCorrection('M')->generate($url);
            file_put_contents($qrFilePath, $qrSvg);
        }

        return str_replace('\\', '/', $qrFilePath);
    }
}
