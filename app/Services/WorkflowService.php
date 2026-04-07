<?php

namespace App\Services;

use App\Models\WorkflowApproval;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Model;

class WorkflowService
{
    /**
     * Mulai workflow baru untuk dokumen tertentu.
     * Membuat workflow_instance dan menyalin semua step definition menjadi workflow_approvals.
     */
    public function startWorkflow(string $workflowCode, Model $document, ?int $assignedUserId = null): WorkflowInstance
    {
        $definition = WorkflowDefinition::where('kode', $workflowCode)
            ->where('status_aktif', true)
            ->firstOrFail();

        // Batalkan instance aktif sebelumnya jika ada (misal re-submit setelah revisi)
        WorkflowInstance::where('workflowable_type', get_class($document))
            ->where('workflowable_id', $document->getKey())
            ->whereIn('status', ['IN_PROGRESS', 'DRAFT', 'REVISION'])
            ->update(['status' => 'REJECTED']); // tutup instance lama

        $instance = WorkflowInstance::create([
            'workflow_definition_id' => $definition->id,
            'workflowable_type' => get_class($document),
            'workflowable_id' => $document->getKey(),
            'step_saat_ini' => 1,
            'status' => 'IN_PROGRESS',
        ]);

        foreach ($definition->steps as $step) {
            WorkflowApproval::create([
                'workflow_instance_id' => $instance->id,
                'urutan_step' => $step->urutan_step,
                'nama_step' => $step->nama_step,
                'role_code' => $step->role_code,
                'assigned_user_id' => ($step->urutan_step === 1) ? $assignedUserId : null,
                'status' => ($step->urutan_step === 1) ? 'PENDING' : 'PENDING',
            ]);
        }

        return $instance;
    }

    /**
     * Approve step aktif saat ini.
     * Jika tidak ada step lanjutan, workflow selesai (APPROVED).
     */
    public function approveCurrentStep(Model $document, int $actedByUserId, ?string $catatan = null): WorkflowInstance
    {
        $instance = $this->getActiveInstance($document);

        if (!$instance) {
            throw new \RuntimeException('Tidak ada workflow aktif untuk dokumen ini.');
        }

        $approval = $this->getPendingApprovalForStep($instance, $instance->step_saat_ini);

        if (!$approval) {
            throw new \RuntimeException('Tidak ada approval step yang pending.');
        }

        $approval->update([
            'status' => 'APPROVED',
            'acted_by_user_id' => $actedByUserId,
            'acted_at' => now(),
            'catatan' => $catatan,
            'ip_address' => request()->ip(),
        ]);

        // Cek apakah ada step berikutnya
        $nextApproval = $instance->approvals()
            ->where('urutan_step', '>', $instance->step_saat_ini)
            ->orderBy('urutan_step')
            ->first();

        if ($nextApproval) {
            $instance->update(['step_saat_ini' => $nextApproval->urutan_step]);
            $nextApproval->update(['status' => 'PENDING']);
        } else {
            $instance->update(['status' => 'APPROVED']);
        }

        return $instance->fresh();
    }

    /**
     * Request revision pada step aktif.
     */
    public function requestRevision(Model $document, int $actedByUserId, ?string $catatan = null): WorkflowInstance
    {
        $instance = $this->getActiveInstance($document);

        if (!$instance) {
            throw new \RuntimeException('Tidak ada workflow aktif untuk dokumen ini.');
        }

        $approval = $this->getPendingApprovalForStep($instance, $instance->step_saat_ini);

        if (!$approval) {
            throw new \RuntimeException('Tidak ada approval step yang pending.');
        }

        $approval->update([
            'status' => 'REVISION',
            'acted_by_user_id' => $actedByUserId,
            'acted_at' => now(),
            'catatan' => $catatan,
            'ip_address' => request()->ip(),
        ]);

        $instance->update(['status' => 'REVISION']);

        return $instance->fresh();
    }

    /**
     * Reject step aktif secara permanen.
     */
    public function rejectCurrentStep(Model $document, int $actedByUserId, ?string $catatan = null): WorkflowInstance
    {
        $instance = $this->getActiveInstance($document);

        if (!$instance) {
            throw new \RuntimeException('Tidak ada workflow aktif untuk dokumen ini.');
        }

        $approval = $this->getPendingApprovalForStep($instance, $instance->step_saat_ini);

        if (!$approval) {
            throw new \RuntimeException('Tidak ada approval step yang pending.');
        }

        $approval->update([
            'status' => 'REJECTED',
            'acted_by_user_id' => $actedByUserId,
            'acted_at' => now(),
            'catatan' => $catatan,
            'ip_address' => request()->ip(),
        ]);

        $instance->update(['status' => 'REJECTED']);

        return $instance->fresh();
    }

    /**
     * Dapatkan workflow instance yang sedang aktif (IN_PROGRESS) untuk dokumen.
     */
    public function getActiveInstance(Model $document): ?WorkflowInstance
    {
        return WorkflowInstance::where('workflowable_type', get_class($document))
            ->where('workflowable_id', $document->getKey())
            ->where('status', 'IN_PROGRESS')
            ->latest()
            ->first();
    }

    /**
     * Dapatkan approval step yang sedang PENDING pada instance aktif.
     */
    public function getCurrentApproval(Model $document): ?WorkflowApproval
    {
        $instance = $this->getActiveInstance($document);

        if (!$instance) {
            return null;
        }

        return $this->getPendingApprovalForStep($instance, $instance->step_saat_ini);
    }

    /**
     * Cek apakah user tertentu punya pending approval untuk dokumen ini.
     */
    public function hasPendingApprovalForUser(Model $document, int $userId): bool
    {
        $instance = $this->getActiveInstance($document);

        if (!$instance) {
            return false;
        }

        return $instance->approvals()
            ->where('urutan_step', $instance->step_saat_ini)
            ->where('status', 'PENDING')
            ->where('assigned_user_id', $userId)
            ->exists();
    }

    /**
     * Helper: ambil approval PENDING untuk step tertentu.
     */
    private function getPendingApprovalForStep(WorkflowInstance $instance, int $step): ?WorkflowApproval
    {
        return $instance->approvals()
            ->where('urutan_step', $step)
            ->where('status', 'PENDING')
            ->first();
    }
}
