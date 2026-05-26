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

        try {
            $this->workflowService->submit($tagihan, $request->user(), $request->ip());

            return redirect()->back()->with('success', 'Dokumen Perjaldin berhasil diajukan ke PPK, PPSPM, Koordinator Keuangan, Bendahara Penerimaan, dan Bendahara Pengeluaran.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
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
