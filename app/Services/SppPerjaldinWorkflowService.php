<?php

namespace App\Services;

use App\Models\DokumenSpp;
use App\Models\TagihanPerjaldinKomponen;
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

class SppPerjaldinWorkflowService
{
    /**
     * Submit SPP ke workflow.
     */
    public function submit(DokumenSpp $spp, ?User $actor = null, ?string $ipAddress = null): WorkflowInstance
    {
        $this->assertSppPerjaldin($spp);

        $allowedSubmitStatuses = [
            'DRAFT',
            'Revisi',
            'REVISI_PPK',
            'REVISI_KASUBBAG',
            'DITOLAK_PPK',
            'DITOLAK_KASUBBAG'
        ];

        if (!in_array($spp->status, $allowedSubmitStatuses)) {
            throw new Exception("SPP tidak dapat disubmit karena status saat ini: {$spp->status}.");
        }

        return DB::transaction(function () use ($spp, $actor, $ipAddress, $allowedSubmitStatuses) {
            $definition = $this->getActiveWorkflowDefinition();
            $oldStatus = $spp->status;

            $instance = $spp->workflowInstance;

            if (!$instance) {
                // Buat instance baru
                $instance = $this->createWorkflowInstance($spp, $definition);
                $this->generateApprovalsFromDefinition($instance);
                $instance->update(['status' => 'IN_PROGRESS', 'step_saat_ini' => 1]);
            } else {
                if ($instance->status === 'IN_PROGRESS') {
                    // Jika SPP masih DRAFT tapi instance sudah IN_PROGRESS,
                    // berarti submit sebelumnya gagal di tengah jalan — recovery: sinkronisasi ulang
                    if (in_array($spp->status, $allowedSubmitStatuses)) {
                        $this->syncSppStatus($spp);
                        $this->writeWorkflowAuditLog($spp, $oldStatus, $spp->status, 'SUBMIT_SPP', 'Dokumen SPP disubmit ke alur workflow (recovery).', $actor, $ipAddress);
                        return $instance->fresh(['approvals']);
                    }
                    throw new Exception("SPP sudah memiliki proses workflow yang sedang berjalan.");
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
                     throw new Exception("Workflow SPP sudah {$instance->status} dan tidak bisa disubmit ulang secara langsung.");
                }
            }

            $this->syncSppStatus($spp);
            $this->writeWorkflowAuditLog($spp, $oldStatus, $spp->status, 'SUBMIT_SPP', 'Dokumen SPP disubmit ke alur workflow.', $actor, $ipAddress);

            return $instance->fresh(['approvals']);
        });
    }

    /**
     * Approve sebuah step workflow.
     */
    public function approve(WorkflowApproval $approval, User $actor, ?string $catatan = null, ?string $ipAddress = null): WorkflowInstance
    {
        $instance = $approval->instance;
        $spp = $instance->workflowable;
        $this->assertSppPerjaldin($spp);

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

        return DB::transaction(function () use ($approval, $actor, $catatan, $ipAddress, $instance, $spp) {
            $oldStatus = $spp->status;

            $approval->update([
                'status' => 'APPROVED',
                'acted_by_user_id' => $actor->id,
                'acted_at' => now(),
                'catatan' => $catatan,
                'ip_address' => $ipAddress,
            ]);

            // Cek apakah masih ada role lain di step yang sama yang belum approve
            $remainingInCurrentStep = $instance->approvals()
                ->where('urutan_step', $approval->urutan_step)
                ->where('status', 'PENDING')
                ->exists();

            if (!$remainingInCurrentStep) {
                // Semua verifikator paralel di step ini sudah approve
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
            }

            $this->syncSppStatus($spp);
            $this->writeWorkflowAuditLog($spp, $oldStatus, $spp->status, 'APPROVE_SPP', $catatan ?? 'Telah disetujui.', $actor, $ipAddress);

            return $instance->fresh(['approvals']);
        });
    }

    /**
     * Request Revisi SPP.
     */
    public function requestRevision(WorkflowApproval $approval, User $actor, string $catatan, ?string $ipAddress = null): WorkflowInstance
    {
        $instance = $approval->instance;
        $spp = $instance->workflowable;
        $this->assertSppPerjaldin($spp);

        if ($instance->step_saat_ini !== $approval->urutan_step || $approval->status !== 'PENDING') {
            throw new Exception("Hanya step aktif yang dapat meminta revisi.");
        }

        $stepDef = WorkflowDefinitionStep::where('workflow_definition_id', $instance->workflow_definition_id)
            ->where('urutan_step', $approval->urutan_step)
            ->first();

        if (!$stepDef || !$stepDef->can_request_revision) {
            throw new Exception("Step ini tidak mengizinkan pengembalian untuk revisi.");
        }

        if (!$this->actorCanAct($approval, $actor)) {
            throw new Exception("Anda tidak memiliki hak akses untuk memproses persetujuan ini.");
        }

        return DB::transaction(function () use ($approval, $actor, $catatan, $ipAddress, $instance, $spp) {
            $oldStatus = $spp->status;

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

            $this->syncSppStatus($spp);
            $this->writeWorkflowAuditLog($spp, $oldStatus, $spp->status, 'REVISION_SPP', $catatan, $actor, $ipAddress);

            return $instance->fresh(['approvals']);
        });
    }

    /**
     * Reject SPP sepenuhnya.
     */
    public function reject(WorkflowApproval $approval, User $actor, string $catatan, ?string $ipAddress = null): WorkflowInstance
    {
        $instance = $approval->instance;
        $spp = $instance->workflowable;
        $this->assertSppPerjaldin($spp);

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

        return DB::transaction(function () use ($approval, $actor, $catatan, $ipAddress, $instance, $spp) {
            $oldStatus = $spp->status;

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

            $this->syncSppStatus($spp);
            $this->writeWorkflowAuditLog($spp, $oldStatus, $spp->status, 'REJECT_SPP', $catatan, $actor, $ipAddress);

            return $instance->fresh(['approvals']);
        });
    }

    /**
     * Sinkronisasikan status workflow instance ke SPP.status
     */
    public function syncSppStatus(DokumenSpp $spp): void
    {
        // Hindari membaca cache relasi lama (yang mungkin bernilai null)
        $spp->load('workflowInstance');
        $instance = $spp->workflowInstance;

        if (!$instance) {
            $spp->update(['status' => 'DRAFT']);
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
                $mappedStatus = "DISETUJUI_SPP";
                break;
            default:
                $mappedStatus = $instance->status;
        }

        $spp->update(['status' => $mappedStatus]);

        if ($spp->tagihanPerjaldinKomponen) {
            app(PerjaldinKomponenService::class)->syncKomponenStatus($spp->tagihanPerjaldinKomponen);
        }
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
     * Ambil workflow definition aktif untuk SPP_PERJALDIN.
     */
    protected function getActiveWorkflowDefinition(): WorkflowDefinition
    {
        $def = WorkflowDefinition::where('kode', 'SPP_PERJALDIN')
            ->where('status_aktif', true)
            ->first();

        if (!$def) {
            throw new Exception("Workflow definition untuk SPP_PERJALDIN tidak ditemukan atau tidak aktif.");
        }

        return $def;
    }

    protected function createWorkflowInstance(DokumenSpp $spp, WorkflowDefinition $definition): WorkflowInstance
    {
        return WorkflowInstance::create([
            'workflow_definition_id' => $definition->id,
            'workflowable_type' => DokumenSpp::class,
            'workflowable_id' => $spp->id,
            'status' => 'DRAFT',
            'step_saat_ini' => 1,
        ]);
    }

    /**
     * Normalisasi Role Code untuk label status ringkas.
     */
    protected function normalizeRoleCode(string $roleCode): string
    {
        if ($roleCode === 'Kepala Subbagian Keuangan dan Tata Usaha') {
            return 'KASUBBAG';
        }

        return Str::upper(str_replace([' ', '-'], '_', trim($roleCode)));
    }

    /**
     * Assign user explicitly if there is exactly 1 user with that role.
     */
    protected function resolveAssignedUserIdByRoleCode(string $roleCode): ?int
    {
        try {
            $users = User::role($roleCode)->active()->get();
        } catch (\Exception $e) {
            $users = User::role($roleCode)->get();
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

        return $actor->hasRole($approval->role_code);
    }

    /**
     * Assert SPP type
     */
    protected function assertSppPerjaldin(DokumenSpp $spp): void
    {
        if (!$spp->tagihanPerjaldinKomponen) {
            throw new InvalidArgumentException("SPP ini bukan dari komponen Perjaldin.");
        }
    }

    /**
     * Audit log
     */
    protected function writeWorkflowAuditLog(
        DokumenSpp $spp,
        ?string $oldStatus,
        string $newStatus,
        string $aksi,
        ?string $catatan,
        ?User $actor,
        ?string $ipAddress
    ): void {
        $actorRole = $actor && $actor->roles->count() > 0 ? $actor->roles->first()->name : 'SYSTEM';
        $spp->logs()->create([
            'user_id' => $actor ? $actor->id : null,
            'role_saat_itu' => $actorRole,
            'status_sebelumnya' => $oldStatus,
            'status_baru' => $newStatus,
            'aksi' => $aksi,
            'catatan' => $catatan,
            'ip_address' => $ipAddress,
        ]);
    }
}
