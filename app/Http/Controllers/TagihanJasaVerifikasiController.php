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
        
        $tagihans = TagihanJasa::with(['mitra', 'mitraLegacy', 'creator', 'workflowInstance.approvals'])
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
        $tagihan = TagihanJasa::with([
            'mitra',
            'mitraLegacy',
            'creator',
            'details.layananJasa.parent.parent.parent.parent.parent',
            'workflowInstance.approvals.actedByUser',
        ])->findOrFail($id);
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

            // Perbarui status tagihan berdasarkan step workflow yang baru disetujui.
            // Konvensi status menggambarkan ROLE YANG SEDANG MENUNGGU APPROVAL (next pending),
            // bukan role yang baru saja approve.
            $wfInstance = $tagihan->workflowInstance()->latest()->first();
            $currentApproval = $wfInstance
                ? $wfInstance->approvals()->where('urutan_step', $wfInstance->step_saat_ini)->first()
                : null;

            if ($wfInstance && $wfInstance->status === 'APPROVED') {
                // Seluruh tahap verifikasi (termasuk KPA / Kabandara) sudah selesai.
                $tagihan->status = 'DISETUJUI';
                $tagihan->save();
            } elseif ($currentApproval) {
                $statusMap = [
                    'Koordinator Jasa'                          => 'VERIFIKASI_KOORDINATOR',
                    'Kepala Seksi Pelayanan dan Kerjasama'      => 'VERIFIKASI_KASI_JASA',
                    'Kepala Subbagian Keuangan dan Tata Usaha'  => 'VERIFIKASI_KASUBAG_TU',
                    'KPA'                                       => 'VERIFIKASI_KABANDARA',
                    'PLT/PLH'                                   => 'VERIFIKASI_KABANDARA',
                ];
                if (isset($statusMap[$currentApproval->role_code])) {
                    $tagihan->status = $statusMap[$currentApproval->role_code];
                    $tagihan->save();
                }
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
