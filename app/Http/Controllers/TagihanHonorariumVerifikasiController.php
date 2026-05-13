<?php

namespace App\Http\Controllers;

use App\Models\Tagihan;
use App\Models\WorkflowApproval;
use App\Services\TagihanHonorariumWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Controller verifikasi tagihan honorarium yang melayani semua role:
 * PPK, PPSPM, Koordinator Keuangan, Bendahara Pengeluaran, Bendahara Penerimaan, Kasubbag.
 *
 * Endpoint sama untuk semua role; otorisasi & resolusi step ditentukan
 * berdasarkan role user yang login melalui WorkflowApproval.role_code.
 */
class TagihanHonorariumVerifikasiController extends Controller
{
    /**
     * Daftar tagihan yang menunggu verifikasi user (login) saat ini.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $roleNames = $user->getRoleNames()->toArray();
        $roleCodes = $this->roleCodesFromRoleNames($roleNames);
        $userRole = $user->getRoleNames()->first() ?? '-';

        $base = Tagihan::query()
            ->where('tipe_tagihan', 'HONORARIUM')
            ->when($request->filled('search'), function ($q) use ($request) {
                $kw = $request->input('search');
                $q->where(function ($w) use ($kw) {
                    $w->where('nomor_tagihan', 'like', "%{$kw}%")
                      ->orWhere('deskripsi', 'like', "%{$kw}%")
                      ->orWhere('nama_supplier', 'like', "%{$kw}%");
                });
            })
            ->with([
                'detailHonorarium',
                'workflowInstance.approvals.assignedUser',
                'workflowInstance.approvals.actedByUser',
                'logs',
            ]);

        // Tagihan yang sedang menunggu aksi user
        $tagihansPerlu = (clone $base)
            ->whereHas('workflowInstance', function ($qi) use ($user, $roleCodes) {
                $qi->where('status', 'IN_PROGRESS')
                    ->whereHas('approvals', function ($qa) use ($user, $roleCodes) {
                        $qa->whereColumn('urutan_step', 'workflow_instances.step_saat_ini')
                            ->where('status', 'PENDING')
                            ->where(function ($w) use ($user, $roleCodes) {
                                $w->where('assigned_user_id', $user->id)
                                    ->orWhere(function ($q2) use ($roleCodes) {
                                        $q2->whereNull('assigned_user_id')
                                            ->whereIn('role_code', $roleCodes);
                                    });
                            });
                    });
            })
            ->latest()
            ->get();

        // Riwayat yang user-nya pernah memberikan keputusan
        $tagihansRiwayat = (clone $base)
            ->whereHas('workflowInstance.approvals', function ($qa) use ($user) {
                $qa->where('acted_by_user_id', $user->id)->whereNotNull('acted_at');
            })
            ->latest('updated_at')
            ->get();

        $tagihans = $tagihansPerlu->merge($tagihansRiwayat)->unique('id');

        $pendingStatuses = ['PENDING_VERIFIKASI_HONORARIUM', 'PENDING_KASUBBAG', 'PENDING_PPK', 'PENDING_PPSPM',
            'PENDING_KOORDINATOR_KEUANGAN', 'PENDING_BENDAHARA_PENGELUARAN', 'PENDING_BENDAHARA_PENERIMAAN'];
        $revisiStatuses  = ['REVISI_PPK', 'REVISI_PPSPM', 'REVISI_KOORDINATOR_KEUANGAN',
            'REVISI_BENDAHARA_PENGELUARAN', 'REVISI_BENDAHARA_PENERIMAAN', 'REVISI_KASUBBAG'];
        $selesaiStatuses = ['DISETUJUI'];

        $detailRoute = 'verifikasi-tagihan-honorarium.show';

        return view('tagihan_honorarium_verifikasi.index', compact(
            'tagihansPerlu', 'tagihansRiwayat', 'tagihans',
            'pendingStatuses', 'revisiStatuses', 'selesaiStatuses',
            'detailRoute', 'userRole'
        ));
    }

    /**
     * Detail tagihan untuk verifikasi.
     */
    public function show($id, TagihanHonorariumWorkflowService $svc)
    {
        $tagihan = Tagihan::with([
            'detailHonorarium',
            'arsipDokumen',
            'workflowInstance.approvals.actedByUser',
            'workflowInstance.approvals.assignedUser',
            'logs.user',
        ])->findOrFail($id);

        abort_unless($tagihan->tipe_tagihan === 'HONORARIUM', 404);

        $user = Auth::user();

        $myApprovals = $this->resolvePendingApprovalsForUser($tagihan, $user);
        $myApproval = $myApprovals->first();
        $canAct = $myApprovals->isNotEmpty();

        $instance = $tagihan->workflowInstance;
        $approvalsByStep = $instance
            ? $instance->approvals->groupBy('urutan_step')
            : collect();

        return view('tagihan_honorarium_verifikasi.show', compact(
            'tagihan', 'myApproval', 'myApprovals', 'canAct', 'instance', 'approvalsByStep'
        ));
    }

    /**
     * Tampilkan arsip dokumen honorarium (SK, Daftar Nominatif, Dokumen Honorarium).
     */
    public function viewArsip($id, $arsipId)
    {
        $allowed = [
            'SK Honorarium',
            'Daftar Nominatif Bertandatangan',
            'Dokumen Honorarium Bertandatangan',
        ];

        $tagihan = Tagihan::with('arsipDokumen')->findOrFail($id);
        abort_unless($tagihan->tipe_tagihan === 'HONORARIUM', 404);

        $arsip = $tagihan->arsipDokumen
            ->first(fn ($item) => (int) $item->id === (int) $arsipId
                && in_array($item->jenis_dokumen, $allowed, true));

        abort_unless($arsip, 404);

        $disk = Storage::disk($arsip->disk ?: 'public');
        abort_unless($disk->exists($arsip->path_file), 404);

        return $disk->response(
            $arsip->path_file,
            $arsip->nama_file_asli ?: basename($arsip->path_file)
        );
    }

    public function approve(Request $request, $id, TagihanHonorariumWorkflowService $svc)
    {
        $request->validate(['catatan' => 'nullable|string|max:1000']);

        $tagihan = Tagihan::findOrFail($id);
        abort_unless($tagihan->tipe_tagihan === 'HONORARIUM', 404);

        $approval = $this->resolveTargetApproval($request, $tagihan);
        if (! $approval) {
            return back()->withErrors(['error' => 'Anda tidak memiliki approval yang pending pada tagihan ini.']);
        }

        try {
            $svc->approve($approval, Auth::user(), $request->input('catatan'), $request->ip());
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('verifikasi-tagihan-honorarium.show', $tagihan->id)
            ->with('success', 'Tagihan honorarium berhasil di-approve.');
    }

    public function revisi(Request $request, $id, TagihanHonorariumWorkflowService $svc)
    {
        $request->validate(['catatan' => 'required|string|max:1000']);

        $tagihan = Tagihan::findOrFail($id);
        abort_unless($tagihan->tipe_tagihan === 'HONORARIUM', 404);

        $approval = $this->resolveTargetApproval($request, $tagihan);
        if (! $approval) {
            return back()->withErrors(['error' => 'Anda tidak memiliki approval yang pending pada tagihan ini.']);
        }

        try {
            $svc->requestRevision($approval, Auth::user(), $request->input('catatan'), $request->ip());
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('verifikasi-tagihan-honorarium.index')
            ->with('success', 'Permintaan revisi terkirim ke PPABP.');
    }

    public function reject(Request $request, $id, TagihanHonorariumWorkflowService $svc)
    {
        $request->validate(['catatan' => 'required|string|max:1000']);

        $tagihan = Tagihan::findOrFail($id);
        abort_unless($tagihan->tipe_tagihan === 'HONORARIUM', 404);

        $approval = $this->resolveTargetApproval($request, $tagihan);
        if (! $approval) {
            return back()->withErrors(['error' => 'Anda tidak memiliki approval yang pending pada tagihan ini.']);
        }

        try {
            $svc->reject($approval, Auth::user(), $request->input('catatan'), $request->ip());
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('verifikasi-tagihan-honorarium.index')
            ->with('success', 'Tagihan honorarium ditolak.');
    }

    private function resolveTargetApproval(Request $request, Tagihan $tagihan): ?WorkflowApproval
    {
        $user = Auth::user();

        if ($request->filled('approval_id')) {
            $roleCodes = $this->roleCodesFromRoleNames($user->getRoleNames()->toArray());
            $approval = WorkflowApproval::where('id', $request->input('approval_id'))
                ->where('status', 'PENDING')
                ->whereIn('role_code', $roleCodes)
                ->whereHas('instance', fn ($q) => $q->where('workflowable_id', $tagihan->id)
                    ->where('workflowable_type', \App\Models\Tagihan::class))
                ->first();

            return $approval;
        }

        return $this->resolvePendingApprovalForUser($tagihan, $user);
    }

    private function resolvePendingApprovalForUser(Tagihan $tagihan, $user): ?WorkflowApproval
    {
        return $this->resolvePendingApprovalsForUser($tagihan, $user)->first();
    }

    private function resolvePendingApprovalsForUser(Tagihan $tagihan, $user): \Illuminate\Support\Collection
    {
        $instance = $tagihan->workflowInstance;
        if (! $instance || $instance->status !== 'IN_PROGRESS') {
            return collect();
        }

        $roleCodes = $this->roleCodesFromRoleNames($user->getRoleNames()->toArray());

        return $instance->approvals()
            ->where('urutan_step', $instance->step_saat_ini)
            ->where('status', 'PENDING')
            ->where(function ($q) use ($user, $roleCodes) {
                $q->where('assigned_user_id', $user->id)
                  ->orWhere(function ($q2) use ($roleCodes) {
                      $q2->whereNull('assigned_user_id')->whereIn('role_code', $roleCodes);
                  });
            })
            ->get();
    }

    private function roleCodesFromRoleNames(array $names): array
    {
        $map = [
            'PPK'                                       => 'PPK',
            'PPSPM'                                     => 'PPSPM',
            'Koordinator Keuangan'                      => 'KOORDINATOR_KEUANGAN',
            'Bendahara Pengeluaran'                     => 'BENDAHARA_PENGELUARAN',
            'Bendahara Penerimaan'                      => 'BENDAHARA_PENERIMAAN',
            'Kepala Subbagian Keuangan dan Tata Usaha'  => 'KASUBBAG',
        ];

        $codes = [];
        foreach ($names as $n) {
            if (isset($map[$n])) $codes[] = $map[$n];
            $codes[] = $n;
        }
        return array_values(array_unique($codes));
    }
}
