<?php

namespace App\Services;

use App\Models\DokumenSpp;
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
        WorkflowInstance::whereIn('workflowable_type', $this->workflowableTypes($document))
            ->where('workflowable_id', $document->getKey())
            ->whereIn('status', ['IN_PROGRESS', 'DRAFT', 'REVISION'])
            ->update(['status' => 'REJECTED']); // tutup instance lama

        $instance = WorkflowInstance::create([
            'workflow_definition_id' => $definition->id,
            'workflowable_type' => $this->workflowableType($document),
            'workflowable_id' => $document->getKey(),
            'step_saat_ini' => 1,
            'status' => 'IN_PROGRESS',
        ]);

        foreach ($definition->steps as $step) {
            $assignee = null;
            if ($step->urutan_step === 1) {
                if ($step->role_code === 'PPK') {
                    $assignee = $assignedUserId;
                } elseif ($step->role_code === 'PPSPM') {
                    $assignee = $assignedUserId;
                } elseif ($step->role_code === 'Kepala Subbagian Keuangan dan Tata Usaha') {
                    $assignee = \App\Models\User::role('Kepala Subbagian Keuangan dan Tata Usaha')->first()?->id;
                } elseif (in_array($step->role_code, ['Bendahara Penerimaan', 'BENDAHARA_PENERIMAAN'], true)) {
                    $documentAttributes = $document->getAttributes();
                    $assignee = $documentAttributes['bendahara_penerimaan_id']
                        ?? $documentAttributes['bendahara_penerimaan_user_id']
                        ?? null;
                } elseif (in_array($step->role_code, ['Koordinator Keuangan', 'KOORDINATOR_KEUANGAN'], true)) {
                    $documentAttributes = $document->getAttributes();
                    $assignee = $documentAttributes['koordinator_keuangan_id']
                        ?? $documentAttributes['koordinator_keuangan_user_id']
                        ?? null;

                    if (!$assignee && $document instanceof \App\Models\DokumenNpi) {
                        $document->loadMissing('spm.spp.tagihan');
                        $assignee = $document->spm?->spp?->tagihan?->koordinator_keuangan_user_id;
                    }

                    $assignee ??= \App\Models\User::role('Koordinator Keuangan')->first()?->id;
                }
            }

            WorkflowApproval::create([
                'workflow_instance_id' => $instance->id,
                'urutan_step' => $step->urutan_step,
                'nama_step' => $step->nama_step,
                'role_code' => $step->role_code,
                'assigned_user_id' => $assignee,
                'status' => ($step->urutan_step === 1) ? 'PENDING' : 'WAITING',
            ]);
        }

        // Override role verifikator final untuk Tagihan Jasa (KPA atau PLT/PLH).
        // Workflow definition default-nya KPA — bisa diganti sesuai pilihan saat input.
        if ($workflowCode === 'TAGIHAN_JASA' && $document instanceof \App\Models\TagihanJasa) {
            $finalRole = $document->final_verifier_role ?? 'KPA';
            if ($finalRole && $finalRole !== 'KPA') {
                WorkflowApproval::where('workflow_instance_id', $instance->id)
                    ->where('role_code', 'KPA')
                    ->update(['role_code' => $finalRole]);
            }
        }

        return $instance;
    }

    /**
     * Approve step aktif saat ini.
     * Jika tidak ada step lanjutan, workflow selesai (APPROVED).
     */
    public function approveCurrentStep(Model $document, int $actedByUserId, ?string $catatan = null, ?int $approvalId = null): WorkflowInstance
    {
        $instance = $this->getActiveInstance($document);

        if (!$instance) {
            throw new \RuntimeException('Tidak ada workflow aktif untuk dokumen ini.');
        }

        if ($approvalId) {
            $approval = $instance->approvals()->where('id', $approvalId)->where('status', 'PENDING')->first();
        } else {
            $approval = $this->getPendingApprovalForUser($instance, $instance->step_saat_ini, $actedByUserId);
        }

        if (!$approval) {
            throw new \RuntimeException('Tidak ada approval step yang pending untuk Anda.');
        }

        $approval->update([
            'status' => 'APPROVED',
            'acted_by_user_id' => $actedByUserId,
            'acted_at' => now(),
            'catatan' => $catatan,
            'ip_address' => request()->ip(),
        ]);

        // Cek apakah masih ada step pending di urutan_step yang SAAT INI
        $remainingInCurrentStep = $instance->approvals()
            ->where('urutan_step', $instance->step_saat_ini)
            ->where('status', 'PENDING')
            ->exists();

        if (!$remainingInCurrentStep) {
            // Cek apakah ada step berikutnya
            $nextApproval = $instance->approvals()
                ->where('urutan_step', '>', $instance->step_saat_ini)
                ->orderBy('urutan_step')
                ->first();

            if ($nextApproval) {
                $instance->update(['step_saat_ini' => $nextApproval->urutan_step]);
                $instance->approvals()
                    ->where('urutan_step', $nextApproval->urutan_step)
                    ->update(['status' => 'PENDING']);
            } else {
                $instance->update(['status' => 'APPROVED']);
            }
        }

        return $instance->fresh();
    }

    /**
     * Request revision pada step aktif.
     */
    public function requestRevision(Model $document, int $actedByUserId, ?string $catatan = null, ?int $approvalId = null): WorkflowInstance
    {
        $instance = $this->getActiveInstance($document);

        if (!$instance) {
            throw new \RuntimeException('Tidak ada workflow aktif untuk dokumen ini.');
        }

        if ($approvalId) {
            $approval = $instance->approvals()->where('id', $approvalId)->where('status', 'PENDING')->first();
        } else {
            $approval = $this->getPendingApprovalForUser($instance, $instance->step_saat_ini, $actedByUserId);
        }

        if (!$approval) {
            throw new \RuntimeException('Tidak ada approval step yang pending untuk Anda.');
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
    public function rejectCurrentStep(Model $document, int $actedByUserId, ?string $catatan = null, ?int $approvalId = null): WorkflowInstance
    {
        $instance = $this->getActiveInstance($document);

        if (!$instance) {
            throw new \RuntimeException('Tidak ada workflow aktif untuk dokumen ini.');
        }

        if ($approvalId) {
            $approval = $instance->approvals()->where('id', $approvalId)->where('status', 'PENDING')->first();
        } else {
            $approval = $this->getPendingApprovalForUser($instance, $instance->step_saat_ini, $actedByUserId);
        }

        if (!$approval) {
            throw new \RuntimeException('Tidak ada approval step yang pending untuk Anda.');
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
        return WorkflowInstance::whereIn('workflowable_type', $this->workflowableTypes($document))
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

        return $this->getPendingApprovalForUser($instance, $instance->step_saat_ini, auth()->id());
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

        return $this->getPendingApprovalForUser($instance, $instance->step_saat_ini, $userId) !== null;
    }

    /**
     * Helper: ambil approval PENDING untuk step tertentu berdasarkan user.
     */
    public function getPendingApprovalForUser(WorkflowInstance $instance, int $step, int $userId): ?WorkflowApproval
    {
        $user = \App\Models\User::find($userId);
        $roles = $user ? $user->getRoleNames()->toArray() : [];

        return $instance->approvals()
            ->where('urutan_step', $step)
            ->where('status', 'PENDING')
            ->where(function ($q) use ($userId, $roles) {
                $q->where('assigned_user_id', $userId)
                  ->orWhere(function ($query) use ($roles) {
                      $query->whereNull('assigned_user_id')
                            ->whereIn('role_code', $roles);
                  });
            })
            ->first();
    }

    private function workflowableType(Model $document): string
    {
        if ($document instanceof DokumenSpp) {
            return DokumenSpp::class;
        }

        return get_class($document);
    }

    private function workflowableTypes(Model $document): array
    {
        $types = [get_class($document), $this->workflowableType($document)];

        return array_values(array_unique($types));
    }
}
