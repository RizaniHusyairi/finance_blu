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
            'REVISI_BENDAHARA',
            'REVISI_BENDAHARA_PENGELUARAN',
            'REVISI_KEPALA_SUBBAGIAN_KEUANGAN_DAN_TATA_USAHA',
            'DITOLAK_PPK',
            'DITOLAK_BENDAHARA_PENGELUARAN'
        ];

        if (!in_array($tagihan->status, $allowedSubmitStatuses)) {
            throw new Exception("Tagihan tidak dapat disubmit karena status saat ini: {$tagihan->status}.");
        }

        return DB::transaction(function () use ($tagihan, $actor, $ipAddress) {
            $definition = $this->getActiveWorkflowDefinition();
            $oldStatus = $tagihan->status;

            $instance = $tagihan->workflowInstance;

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
                    // Resubmit
                    $currentApproval = $instance->currentApproval;
                    if ($currentApproval && $currentApproval->status === 'REVISION') {
                        $currentApproval->update([
                            'status' => 'PENDING',
                            'acted_by_user_id' => null,
                            'acted_at' => null,
                            'catatan' => null,
                            'ip_address' => null,
                        ]);
                    }
                    $instance->update(['status' => 'IN_PROGRESS']);
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

            // Cek apakah ada next step
            $nextApproval = WorkflowApproval::where('workflow_instance_id', $instance->id)
                ->where('urutan_step', '>', $approval->urutan_step)
                ->orderBy('urutan_step', 'asc')
                ->first();

            if ($nextApproval) {
                $instance->update([
                    'step_saat_ini' => $nextApproval->urutan_step
                ]);
            } else {
                $instance->update([
                    'status' => 'APPROVED'
                ]);
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
        $instance = $tagihan->workflowInstance;

        if (!$instance) {
            // Biarkan seperti yang sekarang, tapi kalau dipaksakan:
            $tagihan->update(['status' => 'DRAFT']);
            return;
        }

        $currentApproval = $instance->currentApproval;
        $rolePrefix = $currentApproval ? $this->normalizeRoleCode($currentApproval->role_code) : 'SISTEM';

        $mappedStatus = '';
        switch ($instance->status) {
            case 'DRAFT':
                $mappedStatus = 'DRAFT';
                break;
            case 'IN_PROGRESS':
                $mappedStatus = "PENDING_{$rolePrefix}";
                break;
            case 'REVISION':
                $mappedStatus = "REVISI_{$rolePrefix}";
                break;
            case 'REJECTED':
                $mappedStatus = "DITOLAK_{$rolePrefix}";
                break;
            case 'APPROVED':
                $mappedStatus = "DISETUJUI_PERJALDIN";
                break;
            default:
                $mappedStatus = $instance->status;
        }

        $tagihan->update(['status' => $mappedStatus]);
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
                'assigned_user_id' => $this->resolveAssignedUserIdByRoleCode($step->role_code),
                'status' => 'PENDING',
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

    /**
     * Normalisasi Role Code untuk label status ringkas.
     */
    protected function normalizeRoleCode(string $roleCode): string
    {
        return Str::upper(str_replace([' ', '-'], '_', trim($roleCode)));
    }

    /**
     * Mapping dari role_code internal ke nama role Spatie.
     */
    protected const ROLE_CODE_MAP = [
        'PPK'                     => 'PPK',
        'BENDAHARA_PENGELUARAN'   => 'Bendahara Pengeluaran',
        'OPERATOR_PERJALDIN'      => 'Operator Perjaldin',
        'OPERATOR_BLU'            => 'Operator BLU',
        'KASUBBAG'                => 'Kepala Subbagian Keuangan dan Tata Usaha',
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
