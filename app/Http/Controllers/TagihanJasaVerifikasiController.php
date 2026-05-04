<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TagihanJasa;
use App\Models\WorkflowInstance;
use App\Models\WorkflowApproval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\WorkflowService;

class TagihanJasaVerifikasiController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $workflowService = app(WorkflowService::class);
        
        $tagihans = TagihanJasa::with(['mitra', 'creator', 'workflowInstance.approvals'])
            ->whereHas('workflowInstance', function ($q) use ($user, $workflowService) {
                $q->where('status', 'IN_PROGRESS')
                  ->whereHas('approvals', function ($q2) use ($user) {
                      $q2->where('status', 'PENDING')
                         ->whereIn('role_code', $user->getRoleNames());
                  });
            })
            ->latest()
            ->get();
            
        // Additional filter for EXACT current step (safety check)
        $filteredTagihans = $tagihans->filter(function($tagihan) use ($workflowService, $user) {
            return $workflowService->hasPendingApprovalForUser($tagihan, $user->id);
        });

        return view('tagihan_jasa.verifikasi_index', ['tagihans' => $filteredTagihans]);
    }

    public function show($id)
    {
        $tagihan = TagihanJasa::with(['mitra', 'creator', 'details.layananJasa', 'workflowInstance.approvals.actedByUser'])->findOrFail($id);
        return view('tagihan_jasa.show', compact('tagihan'));
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'catatan' => 'nullable|string',
        ]);

        $tagihan = TagihanJasa::findOrFail($id);
        $workflowInstance = $tagihan->workflowInstance;

        if (!$workflowInstance) {
            return back()->with('error', 'Workflow tidak ditemukan.');
        }

        try {
            DB::beginTransaction();

            $workflowService = app(WorkflowService::class);
            $workflowService->approveCurrentStep($tagihan, Auth::id(), $request->catatan);

            // Perbarui status tagihan berdasarkan step workflow yang baru disetujui
            $wfInstance = $tagihan->workflowInstance()->latest()->first();
            $currentApproval = $wfInstance ? $wfInstance->approvals()->where('urutan_step', $wfInstance->step_saat_ini)->first() : null;
            
            if ($wfInstance->status === 'APPROVED') {
                $tagihan->status = 'VERIFIKASI_KABANDARA'; // Or 'PUBLISHED' if you auto publish
                $tagihan->save();
            } else if ($currentApproval) {
                // Update status based on next pending role
                if ($currentApproval->role_code === 'Kepala Seksi Pelayanan dan Kerjasama') {
                    $tagihan->status = 'VERIFIKASI_KOORDINATOR'; // Actually it means waiting for Kasi Jasa, so previous was Koordinator
                } else if ($currentApproval->role_code === 'Kepala Subbagian Keuangan dan Tata Usaha') {
                    $tagihan->status = 'VERIFIKASI_KASI_JASA';
                } else if ($currentApproval->role_code === 'KPA') {
                    $tagihan->status = 'VERIFIKASI_KASUBAG_TU';
                }
                $tagihan->save();
            }

            DB::commit();
            return back()->with('success', 'Tagihan Jasa berhasil disetujui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'catatan' => 'required|string',
        ]);

        $tagihan = TagihanJasa::findOrFail($id);
        $workflowInstance = $tagihan->workflowInstance;

        if (!$workflowInstance) {
            return back()->with('error', 'Workflow tidak ditemukan.');
        }

        try {
            DB::beginTransaction();

            $workflowService = app(WorkflowService::class);
            $workflowService->rejectCurrentStep($tagihan, Auth::id(), $request->catatan);

            $tagihan->status = 'DITOLAK';
            $tagihan->save();

            DB::commit();
            return back()->with('success', 'Tagihan Jasa berhasil ditolak/dikembalikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
