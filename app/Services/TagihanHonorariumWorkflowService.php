<?php

namespace App\Services;

use App\Models\Tagihan;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use Exception;
use InvalidArgumentException;

/**
 * Workflow service untuk Tagihan Honorarium.
 *
 * Struktur workflow mengikuti pola Tagihan Kontrak:
 *   - Step 1 paralel: PPK, PPSPM, Koordinator Keuangan,
 *                     Bendahara Pengeluaran, Bendahara Penerimaan
 *   - Step 2: Kepala Subbagian Keuangan dan Tata Usaha (Kasubbag) — final
 *
 * Mayoritas logika di-reuse dari PerjaldinWorkflowService; hanya mapping
 * assignee per role_code → kolom *_user_id yang di-override agar sesuai
 * form honorarium.
 */
class TagihanHonorariumWorkflowService extends PerjaldinWorkflowService
{
    /** Override mapping role_code → nama Spatie Role agar Koordinator Keuangan ikut. */
    protected const ROLE_CODE_MAP = [
        'PPK'                     => 'PPK',
        'PPSPM'                   => 'PPSPM',
        'KOORDINATOR_KEUANGAN'    => 'Koordinator Keuangan',
        'BENDAHARA_PENERIMAAN'    => 'Bendahara Penerimaan',
        'BENDAHARA_PENGELUARAN'   => 'Bendahara Pengeluaran',
        'KASUBBAG'                => 'Kepala Subbagian Keuangan dan Tata Usaha',
        'OPERATOR_BLU'            => 'Operator BLU',
        'PPABP'                   => 'PPABP',
    ];

    protected function getActiveWorkflowDefinition(): WorkflowDefinition
    {
        $def = WorkflowDefinition::where('kode', 'TAGIHAN_HONORARIUM')
            ->where('status_aktif', true)
            ->first();

        if (! $def) {
            throw new Exception('Workflow definition TAGIHAN_HONORARIUM tidak ditemukan / tidak aktif.');
        }

        return $def;
    }

    /**
     * Tagihan ini harus tipe HONORARIUM (bukan PERJALDIN / KONTRAK).
     */
    protected function assertPerjaldinTagihan(Tagihan $tagihan): void
    {
        if ($tagihan->tipe_tagihan !== 'HONORARIUM') {
            throw new InvalidArgumentException('Tagihan ini bukan tagihan Honorarium.');
        }
    }

    /**
     * Kolom user_id assignee tiap role pada tabel tagihan untuk honorarium.
     */
    protected function resolveAssignedUserIdForStep(string $roleCode, ?Tagihan $tagihan = null): ?int
    {
        if ($tagihan instanceof Tagihan) {
            $field = match ($this->normalizeRoleCode($roleCode)) {
                'PPK'                   => 'ppk_user_id',
                'PPSPM'                 => 'ppspm_user_id',
                'KOORDINATOR_KEUANGAN'  => 'koordinator_keuangan_user_id',
                'BENDAHARA_PENGELUARAN' => 'bendahara_pengeluaran_user_id',
                'BENDAHARA_PENERIMAAN'  => 'bendahara_penerimaan_user_id',
                'KASUBBAG'              => 'kasubbag_user_id',
                default                 => null,
            };

            if ($field && filled($tagihan->{$field})) {
                return (int) $tagihan->{$field};
            }
        }

        return $this->resolveAssignedUserIdByRoleCode($roleCode);
    }

    /**
     * Status mapping untuk Tagihan Honorarium.
     * Saat APPROVED final → DISETUJUI (siap diproses ke pembuatan SPP).
     */
    public function syncTagihanStatus(Tagihan $tagihan): void
    {
        $instance = WorkflowInstance::where('workflowable_type', Tagihan::class)
            ->where('workflowable_id', $tagihan->id)
            ->latest()
            ->first();

        if (! $instance) {
            $tagihan->update(['status' => 'DRAFT']);
            return;
        }

        $currentApproval = $instance->approvals()
                ->where('urutan_step', $instance->step_saat_ini)
                ->where('status', 'PENDING')
                ->orderBy('id')
                ->first()
            ?? $instance->approvals()->where('status', 'REVISION')->latest('acted_at')->first();

        $rolePrefix = $currentApproval ? $this->normalizeRoleCode($currentApproval->role_code) : 'SISTEM';

        $mappedStatus = match ($instance->status) {
            'DRAFT'       => 'DRAFT',
            'IN_PROGRESS' => $this->pendingStatusForHonorarium($instance, $rolePrefix),
            'REVISION'    => "REVISI_{$rolePrefix}",
            'REJECTED'    => "DITOLAK_{$rolePrefix}",
            'APPROVED'    => 'DISETUJUI',
            default       => $instance->status,
        };

        $previousStatus = $tagihan->status;
        $tagihan->update(['status' => $mappedStatus]);

        app(TagihanReadyForSppNotificationService::class)->notifyIfNewlyReady($tagihan, $previousStatus);
    }

    protected function pendingStatusForHonorarium(WorkflowInstance $instance, string $rolePrefix): string
    {
        $pendingRoles = $instance->approvals()
            ->where('urutan_step', $instance->step_saat_ini)
            ->where('status', 'PENDING')
            ->pluck('role_code')
            ->map(fn ($rc) => $this->normalizeRoleCode($rc))
            ->values();

        if ($pendingRoles->contains('KASUBBAG')) {
            return 'PENDING_KASUBBAG';
        }

        if ($pendingRoles->count() > 1) {
            return 'PENDING_VERIFIKASI_HONORARIUM';
        }

        return "PENDING_{$rolePrefix}";
    }

    /**
     * Override normalizeRoleCode agar 'Koordinator Keuangan' ter-map ke KOORDINATOR_KEUANGAN.
     */
    protected function normalizeRoleCode(string $roleCode): string
    {
        $normalized = \Illuminate\Support\Str::upper(str_replace([' ', '-'], '_', trim($roleCode)));

        return match ($normalized) {
            'KEPALA_SUBBAGIAN_KEUANGAN_DAN_TATA_USAHA' => 'KASUBBAG',
            'KOORDINATOR_KEUANGAN'                     => 'KOORDINATOR_KEUANGAN',
            default                                    => $normalized,
        };
    }
}
