<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DokumenSpp;
use App\Models\WorkflowApproval;
use App\Services\SppPerjaldinWorkflowService;

class SppWorkflowController extends Controller
{
    protected $workflowService;

    public function __construct(SppPerjaldinWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    public function submit(Request $request, int $sppId)
    {
        $spp = DokumenSpp::findOrFail($sppId);

        try {
            $this->workflowService->submit($spp, $request->user(), $request->ip());
            return redirect()->back()->with('success', 'Dokumen SPP berhasil disubmit untuk diproses.');
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
            return redirect()->back()->with('success', 'Dokumen SPP berhasil disetujui.');
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
            return redirect()->back()->with('success', 'Dokumen SPP dikembalikan untuk revisi.');
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
            return redirect()->back()->with('success', 'Dokumen SPP ditolak mutlak.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
