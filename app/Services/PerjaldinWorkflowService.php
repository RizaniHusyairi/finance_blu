<?php

namespace App\Services;

use App\Models\Tagihan;
use App\Models\WorkflowInstance;
use App\Models\WorkflowApproval;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowDefinitionStep;
use App\Models\User;
use App\Models\LogStatusDokumen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Exception;

class PerjaldinWorkflowService
{
    /**
     * Submit tagihan ke workflow.
     */
    public function submit(Tagihan $tagihan, ?User $actor = null, ?string $ipAddress = null): WorkflowInstance
    {
        $this->assertPerjaldinTagihan($tagihan);

        $allowedSubmitStatuses = [
            'DRAFT',
            'REVISI_PPK',
            'REVISI_PPSPM',
            'REVISI_BENDAHARA',
            'REVISI_BENDAHARA_PENERIMAAN',
            'REVISI_BENDAHARA_PENGELUARAN',
            'REVISI_KASUBBAG',
            'REVISI_KOORDINATOR_KEUANGAN',
            'DITOLAK_PPK',
            'DITOLAK_PPSPM',
            'DITOLAK_BENDAHARA_PENERIMAAN',
            'DITOLAK_BENDAHARA_PENGELUARAN',
            'DITOLAK_KASUBBAG',
            'DITOLAK_KOORDINATOR_KEUANGAN',
        ];

        if (!in_array($tagihan->status, $allowedSubmitStatuses)) {
            throw new Exception("Tagihan tidak dapat disubmit karena status saat ini: {$tagihan->status}.");
        }

        return DB::transaction(function () use ($tagihan, $actor, $ipAddress) {
            $definition = $this->getActiveWorkflowDefinition();
            $oldStatus = $tagihan->status;

            $instance = $this->latestWorkflowInstance($tagihan);

            if (!$instance) {
                // Buat instance baru
                $instance = $this->createWorkflowInstance($tagihan, $definition);
                $this->generateApprovalsFromDefinition($instance);
                $instance->update(['status' => 'IN_PROGRESS', 'step_saat_ini' => 1]);
            } else {
                if ($instance->status === 'IN_PROGRESS') {
                    throw new Exception("Tagihan sudah memiliki proses workflow yang sedang berjalan.");
                }

                if ($instance->status === 'REVISION') {
                    // Resubmit setelah revisi selalu mengulang verifikasi awal,
                    // karena substansi dokumen bisa berubah.
                    $instance->approvals()->update([
                        'status' => DB::raw("CASE WHEN urutan_step = 1 THEN 'PENDING' ELSE 'WAITING' END"),
                        'acted_by_user_id' => null,
                        'acted_at' => null,
                        'catatan' => null,
                        'ip_address' => null,
                    ]);
                    $instance->update(['status' => 'IN_PROGRESS', 'step_saat_ini' => 1]);
                } else {
                     throw new Exception("Workflow tagihan sudah {$instance->status} dan tidak bisa disubmit ulang secara langsung.");
                }
            }

            $this->syncTagihanStatus($tagihan);
            $this->writeWorkflowAuditLog($tagihan, $oldStatus, $tagihan->status, 'SUBMIT', 'Dokumen disubmit ke alur workflow.', $actor, $ipAddress);

            return $instance->fresh(['approvals']);
        });
    }

    /**
     * Approve sebuah step workflow.
     */
    public function approve(WorkflowApproval $approval, User $actor, ?string $catatan = null, ?string $ipAddress = null): WorkflowInstance
    {
        $instance = $approval->instance;
        $tagihan = $instance->workflowable;
        $this->assertPerjaldinTagihan($tagihan);

        if ($instance->status !== 'IN_PROGRESS') {
            throw new Exception("Tidak dapat memproses approval, workflow tidak sedang berjalan (status: {$instance->status}).");
        }

        if ($instance->step_saat_ini !== $approval->urutan_step) {
            throw new Exception("Tidak dapat memproses approval pada step ini (bukan step aktif).");
        }

        if ($approval->status !== 'PENDING') {
            throw new Exception("Approval ini sudah diproses.");
        }

        if (!$this->actorCanAct($approval, $actor)) {
            throw new Exception("Anda tidak memiliki hak akses untuk memproses persetujuan ini.");
        }

        return DB::transaction(function () use ($approval, $actor, $catatan, $ipAddress, $instance, $tagihan) {
            $oldStatus = $tagihan->status;

            $approval->update([
                'status' => 'APPROVED',
                'acted_by_user_id' => $actor->id,
                'acted_at' => now(),
                'catatan' => $catatan,
                'ip_address' => $ipAddress,
            ]);

            $remainingInCurrentStep = $instance->approvals()
                ->where('urutan_step', $approval->urutan_step)
                ->where('status', 'PENDING')
                ->exists();

            if (!$remainingInCurrentStep) {
                $nextStep = $instance->approvals()
                    ->where('urutan_step', '>', $approval->urutan_step)
                    ->orderBy('urutan_step', 'asc')
                    ->value('urutan_step');

                if ($nextStep) {
                    $instance->update(['step_saat_ini' => $nextStep]);
                    $instance->approvals()
                        ->where('urutan_step', $nextStep)
                        ->where('status', 'WAITING')
                        ->update(['status' => 'PENDING']);
                } else {
                    $instance->update(['status' => 'APPROVED']);
                }
            }

            $this->syncTagihanStatus($tagihan);
            $this->writeWorkflowAuditLog($tagihan, $oldStatus, $tagihan->status, 'APPROVE', $catatan ?? 'Telah disetujui.', $actor, $ipAddress);

            return $instance->fresh(['approvals']);
        });
    }

    /**
     * Request Revisi dokumen.
     */
    public function requestRevision(WorkflowApproval $approval, User $actor, string $catatan, ?string $ipAddress = null): WorkflowInstance
    {
        $instance = $approval->instance;
        $tagihan = $instance->workflowable;
        $this->assertPerjaldinTagihan($tagihan);

        if ($instance->step_saat_ini !== $approval->urutan_step || $approval->status !== 'PENDING') {
            throw new Exception("Hanya step aktif yang dapat meminta revisi.");
        }

        // Cek rule definisi step
        $stepDef = WorkflowDefinitionStep::where('workflow_definition_id', $instance->workflow_definition_id)
            ->where('urutan_step', $approval->urutan_step)
            ->first();

        if (!$stepDef || !$stepDef->can_request_revision) {
            throw new Exception("Step ini tidak mengizinkan pengembalian untuk revisi.");
        }

        if (!$this->actorCanAct($approval, $actor)) {
            throw new Exception("Anda tidak memiliki hak akses untuk memproses persetujuan ini.");
        }

        return DB::transaction(function () use ($approval, $actor, $catatan, $ipAddress, $instance, $tagihan) {
            $oldStatus = $tagihan->status;

            $approval->update([
                'status' => 'REVISION',
                'acted_by_user_id' => $actor->id,
                'acted_at' => now(),
                'catatan' => $catatan,
                'ip_address' => $ipAddress,
            ]);

            $instance->update([
                'status' => 'REVISION'
            ]);

            $this->syncTagihanStatus($tagihan);
            $this->writeWorkflowAuditLog($tagihan, $oldStatus, $tagihan->status, 'REVISION', $catatan, $actor, $ipAddress);

            return $instance->fresh(['approvals']);
        });
    }

    /**
     * Reject tagihan sepenuhnya.
     */
    public function reject(WorkflowApproval $approval, User $actor, string $catatan, ?string $ipAddress = null): WorkflowInstance
    {
        $instance = $approval->instance;
        $tagihan = $instance->workflowable;
        $this->assertPerjaldinTagihan($tagihan);

        if ($instance->step_saat_ini !== $approval->urutan_step || $approval->status !== 'PENDING') {
            throw new Exception("Hanya step aktif yang dapat ditolak.");
        }

        $stepDef = WorkflowDefinitionStep::where('workflow_definition_id', $instance->workflow_definition_id)
            ->where('urutan_step', $approval->urutan_step)
            ->first();

        if (!$stepDef || !$stepDef->can_reject) {
            throw new Exception("Step ini tidak mengizinkan penolakan mutlak.");
        }

        if (!$this->actorCanAct($approval, $actor)) {
            throw new Exception("Anda tidak memiliki hak akses untuk memproses persetujuan ini.");
        }

        return DB::transaction(function () use ($approval, $actor, $catatan, $ipAddress, $instance, $tagihan) {
            $oldStatus = $tagihan->status;

            $approval->update([
                'status' => 'REJECTED',
                'acted_by_user_id' => $actor->id,
                'acted_at' => now(),
                'catatan' => $catatan,
                'ip_address' => $ipAddress,
            ]);

            $instance->update([
                'status' => 'REJECTED'
            ]);

            $this->syncTagihanStatus($tagihan);
            $this->writeWorkflowAuditLog($tagihan, $oldStatus, $tagihan->status, 'REJECT', $catatan, $actor, $ipAddress);

            return $instance->fresh(['approvals']);
        });
    }

    /**
     * Sinkronisasikan status workflow instance ke tagihan.status
     */
    public function syncTagihanStatus(Tagihan $tagihan): void
    {
        $instance = $this->latestWorkflowInstance($tagihan);

        if (!$instance) {
            // Biarkan seperti yang sekarang, tapi kalau dipaksakan:
            $tagihan->update(['status' => 'DRAFT']);
            return;
        }

        $currentApproval = $this->currentPendingApproval($instance)
            ?? $this->latestRevisionApproval($instance)
            ?? $instance->currentApproval;
        $rolePrefix = $currentApproval ? $this->normalizeRoleCode($currentApproval->role_code) : 'SISTEM';

        $mappedStatus = '';
        switch ($instance->status) {
            case 'DRAFT':
                $mappedStatus = 'DRAFT';
                break;
            case 'IN_PROGRESS':
                $mappedStatus = $this->pendingStatusForInstance($instance, $rolePrefix);
                break;
            case 'REVISION':
                $mappedStatus = "REVISI_{$rolePrefix}";
                break;
            case 'REJECTED':
                $mappedStatus = "DITOLAK_{$rolePrefix}";
                break;
            case 'APPROVED':
                // Setelah seluruh verifikator approve, dokumen Nominatif Perjaldin
                // dan Daftar Nominatif Pembayaran Perjaldin otomatis menerima TTE QR
                // (lihat App\Support\TagihanDocumentTte) sehingga tidak perlu lagi
                // upload scan TTD basah. Backward-compat: bila operator masih
                // meng-upload arsip TTD, tetap dianggap valid.
                $mappedStatus = 'DISETUJUI_PERJALDIN';
                break;
            default:
                $mappedStatus = $instance->status;
        }

        $tagihan->update(['status' => $mappedStatus]);
    }

    /**
     * Apakah dua arsip Nominatif bertandatangan sudah lengkap.
     */
    public function hasNominatifTtdComplete(Tagihan $tagihan): bool
    {
        $jenis = [
            'NOMINATIF_PERJALDIN_TTD',
            'DAFTAR_NOMINATIF_PEMBAYARAN_PERJALDIN_TTD',
        ];

        $found = $tagihan->arsipDokumen()
            ->whereIn('jenis_dokumen', $jenis)
            ->where('is_active', true)
            ->pluck('jenis_dokumen')
            ->unique();

        return $found->count() === count($jenis);
    }

    public function pendingApprovalForRole(Tagihan $tagihan, string $roleCode): ?WorkflowApproval
    {
        $instance = $this->latestWorkflowInstance($tagihan);

        if (!$instance || $instance->status !== 'IN_PROGRESS') {
            return null;
        }

        $roleCodes = [$roleCode, $this->mapRoleCodeToName($roleCode)];

        return $instance->approvals()
            ->where('urutan_step', $instance->step_saat_ini)
            ->where('status', 'PENDING')
            ->whereIn('role_code', array_values(array_unique($roleCodes)))
            ->first();
    }

    /**
     * Helper untuk insert semua steps saat create instance.
     */
    protected function generateApprovalsFromDefinition(WorkflowInstance $instance): void
    {
        $steps = WorkflowDefinitionStep::where('workflow_definition_id', $instance->workflow_definition_id)
            ->orderBy('urutan_step', 'asc')
            ->get();

        if ($steps->isEmpty()) {
            throw new Exception("Workflow definition tidak memiliki rincian steps.");
        }

        foreach ($steps as $step) {
            WorkflowApproval::create([
                'workflow_instance_id' => $instance->id,
                'urutan_step' => $step->urutan_step,
                'nama_step' => $step->nama_step,
                'role_code' => $step->role_code,
                'assigned_user_id' => $this->resolveAssignedUserIdForStep($step->role_code, $instance->workflowable),
                'status' => $step->urutan_step === 1 ? 'PENDING' : 'WAITING',
            ]);
        }
    }

    /**
     * Ambil workflow definition aktif untuk PERJALDIN.
     */
    protected function getActiveWorkflowDefinition(): WorkflowDefinition
    {
        $def = WorkflowDefinition::where('kode', 'PERJALDIN')
            ->where('status_aktif', true)
            ->first();

        if (!$def) {
            throw new Exception("Workflow definition untuk PERJALDIN tidak ditemukan atau tidak aktif.");
        }

        return $def;
    }

    protected function createWorkflowInstance(Tagihan $tagihan, WorkflowDefinition $definition): WorkflowInstance
    {
        return WorkflowInstance::create([
            'workflow_definition_id' => $definition->id,
            'workflowable_type' => Tagihan::class,
            'workflowable_id' => $tagihan->id,
            'status' => 'DRAFT',
            'step_saat_ini' => 1,
        ]);
    }

    protected function latestWorkflowInstance(Tagihan $tagihan): ?WorkflowInstance
    {
        return WorkflowInstance::where('workflowable_type', Tagihan::class)
            ->where('workflowable_id', $tagihan->id)
            ->latest()
            ->first();
    }

    /**
     * Normalisasi Role Code untuk label status ringkas.
     */
    protected function normalizeRoleCode(string $roleCode): string
    {
        $normalized = Str::upper(str_replace([' ', '-'], '_', trim($roleCode)));

        return match ($normalized) {
            'BENDAHARA_PENERIMAAN' => 'BENDAHARA_PENERIMAAN',
            'BENDAHARA_PENGELUARAN' => 'BENDAHARA_PENGELUARAN',
            'KEPALA_SUBBAGIAN_KEUANGAN_DAN_TATA_USAHA' => 'KASUBBAG',
            'KOORDINATOR_KEUANGAN' => 'KOORDINATOR_KEUANGAN',
            default => $normalized,
        };
    }

    /**
     * Mapping dari role_code internal ke nama role Spatie.
     */
    protected const ROLE_CODE_MAP = [
        'PPK'                     => 'PPK',
        'PPSPM'                   => 'PPSPM',
        'BENDAHARA_PENERIMAAN'    => 'Bendahara Penerimaan',
        'BENDAHARA_PENGELUARAN'   => 'Bendahara Pengeluaran',
        'OPERATOR_PERJALDIN'      => 'Operator Perjaldin',
        'OPERATOR_BLU'            => 'Operator BLU',
        'KASUBBAG'                => 'Kepala Subbagian Keuangan dan Tata Usaha',
        'KOORDINATOR_KEUANGAN'    => 'Koordinator Keuangan',
        'Koordinator Keuangan'    => 'Koordinator Keuangan',
    ];

    protected function mapRoleCodeToName(string $roleCode): string
    {
        return self::ROLE_CODE_MAP[$roleCode] ?? $roleCode;
    }

    /**
     * Assign user explicitly if there is exactly 1 user with that role.
     */
    protected function resolveAssignedUserIdByRoleCode(string $roleCode): ?int
    {
        $roleName = $this->mapRoleCodeToName($roleCode);
        try {
            $users = User::role($roleName)->get();
        } catch (\Exception $e) {
            return null;
        }

        if ($users->count() === 1) {
            return $users->first()->id;
        }

        return null;
    }

    /**
     * Prioritaskan verifikator yang dipilih pada form Perjaldin.
     */
    protected function resolveAssignedUserIdForStep(string $roleCode, ?Tagihan $tagihan = null): ?int
    {
        if ($tagihan instanceof Tagihan) {
            $field = match ($this->normalizeRoleCode($roleCode)) {
                'PPK' => 'ppk_user_id',
                'PPSPM' => 'ppspm_user_id',
                'BENDAHARA_PENERIMAAN' => 'bendahara_penerimaan_user_id',
                'BENDAHARA_PENGELUARAN' => 'bendahara_pengeluaran_user_id',
                'KASUBBAG' => 'kasubbag_user_id',
                'KOORDINATOR_KEUANGAN' => 'koordinator_keuangan_user_id',
                default => null,
            };

            if ($field && filled($tagihan->{$field})) {
                return (int) $tagihan->{$field};
            }
        }

        return $this->resolveAssignedUserIdByRoleCode($roleCode);
    }

    /**
     * Validasi actor berwenang execute step approval.
     */
    protected function actorCanAct(WorkflowApproval $approval, User $actor): bool
    {
        if ($approval->assigned_user_id !== null) {
            return $approval->assigned_user_id === $actor->id;
        }

        // Check via mapped role name
        $roleName = $this->mapRoleCodeToName($approval->role_code);
        return $actor->hasRole($roleName);
    }

    protected function currentPendingApproval(WorkflowInstance $instance): ?WorkflowApproval
    {
        return $instance->approvals()
            ->where('urutan_step', $instance->step_saat_ini)
            ->where('status', 'PENDING')
            ->orderBy('id')
            ->first();
    }

    protected function latestRevisionApproval(WorkflowInstance $instance): ?WorkflowApproval
    {
        return $instance->approvals()
            ->where('status', 'REVISION')
            ->latest('acted_at')
            ->first();
    }

    protected function pendingStatusForInstance(WorkflowInstance $instance, string $rolePrefix): string
    {
        $pendingRoles = $instance->approvals()
            ->where('urutan_step', $instance->step_saat_ini)
            ->where('status', 'PENDING')
            ->pluck('role_code')
            ->map(fn ($roleCode) => $this->normalizeRoleCode($roleCode))
            ->values();

        if ($pendingRoles->contains('KASUBBAG')) {
            return 'PENDING_KASUBBAG';
        }

        if ($pendingRoles->count() > 1) {
            return 'PENDING_VERIFIKASI_PERJALDIN';
        }

        return "PENDING_{$rolePrefix}";
    }

    /**
     * Assert tagihan type
     */
    protected function assertPerjaldinTagihan(Tagihan $tagihan): void
    {
        if ($tagihan->tipe_tagihan !== 'PERJALDIN') {
            throw new InvalidArgumentException("Tagihan ini bukan tagihan Perjaldin.");
        }
    }

    /**
     * Audit log
     */
    protected function writeWorkflowAuditLog(
        Tagihan $tagihan,
        ?string $oldStatus,
        string $newStatus,
        string $aksi,
        ?string $catatan,
        ?User $actor,
        ?string $ipAddress
    ): void {
        if (!$actor) return; // Jangan tulis log jika tidak ada actor (system)

        $actorRole = $actor->roles->count() > 0 ? $actor->roles->first()->name : 'SYSTEM';
        $tagihan->logs()->create([
            'user_id'           => $actor->id,
            'role_saat_itu'     => $actorRole,
            'status_sebelumnya' => $oldStatus,
            'status_baru'       => $newStatus,
            'aksi'              => $aksi,
            'catatan'           => $catatan,
            'ip_address'        => $ipAddress,
        ]);
    }
}
