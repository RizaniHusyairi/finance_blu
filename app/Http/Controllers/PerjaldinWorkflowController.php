<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tagihan;
use App\Models\WorkflowApproval;
use App\Services\PerjaldinWorkflowService;

class PerjaldinWorkflowController extends Controller
{
    protected $workflowService;

    public function __construct(PerjaldinWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    public function submit(Request $request, int $tagihanId)
    {
        $tagihan = Tagihan::findOrFail($tagihanId);

        $allowed = ['DRAFT', 'REVISI_PPK', 'REVISI_BENDAHARA', 'DITOLAK_PPK'];
        if (!in_array($tagihan->status, $allowed)) {
            return redirect()->back()->with('error', "Dokumen tidak dapat diajukan karena status saat ini: {$tagihan->status}.");
        }

        $statusLama = $tagihan->status;
        $tagihan->update(['status' => 'PENDING_PPK']);

        \App\Models\LogStatusDokumen::create([
            'dokumen_type'      => Tagihan::class,
            'dokumen_id'        => $tagihan->id,
            'user_id'           => $request->user()->id,
            'role_saat_itu'     => $request->user()->getRoleNames()->first() ?? 'Operator Perjaldin',
            'status_sebelumnya' => $statusLama,
            'status_baru'       => 'PENDING_PPK',
            'aksi'              => 'SUBMIT',
            'catatan'           => 'Dokumen diajukan oleh Operator Perjaldin.',
            'ip_address'        => $request->ip(),
        ]);

        return redirect()->back()->with('success', 'Dokumen Perjaldin berhasil diajukan ke PPK dan Bendahara Pengeluaran.');
    }

    public function approve(Request $request, int $approvalId)
    {
        $request->validate(['catatan' => 'nullable|string|max:1000']);
        $approval = WorkflowApproval::findOrFail($approvalId);

        try {
            $this->workflowService->approve($approval, $request->user(), $request->catatan, $request->ip());
            return redirect()->back()->with('success', 'Dokumen berhasil disetujui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function revision(Request $request, int $approvalId)
    {
        $request->validate(['catatan' => 'required|string|min:3|max:1000']);
        $approval = WorkflowApproval::findOrFail($approvalId);

        try {
            $this->workflowService->requestRevision($approval, $request->user(), $request->catatan, $request->ip());
            return redirect()->back()->with('success', 'Dokumen dikembalikan untuk revisi.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, int $approvalId)
    {
        $request->validate(['catatan' => 'required|string|min:3|max:1000']);
        $approval = WorkflowApproval::findOrFail($approvalId);

        try {
            $this->workflowService->reject($approval, $request->user(), $request->catatan, $request->ip());
            return redirect()->back()->with('success', 'Dokumen ditolak mutlak.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
