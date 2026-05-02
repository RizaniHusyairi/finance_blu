import re

file_path = "app/Http/Controllers/SppPerjaldinVerifikasiController.php"
with open(file_path, "r") as f:
    content = f.read()

helpers_start = content.find("    // =====================================================================\n    //  Helpers")
helpers_content = content[helpers_start:]

new_controller = r"""<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DokumenSpp;
use App\Models\LogStatusDokumen;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\SppPerjaldinWorkflowService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class SppPerjaldinVerifikasiController extends Controller
{
    protected SppPerjaldinWorkflowService $workflowService;

    public function __construct(SppPerjaldinWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    private function activeRoleCodes(User $user): array
    {
        $roles = [];
        if ($user->hasRole('PPK')) $roles[] = 'PPK';
        if ($user->hasRole('Koordinator Keuangan')) $roles[] = 'Koordinator Keuangan';
        if ($user->hasRole('Kepala Subbagian Keuangan dan Tata Usaha')) $roles[] = 'Kepala Subbagian Keuangan dan Tata Usaha';
        
        return $roles;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $roleCodes = $this->activeRoleCodes($user);
        
        abort_unless(count($roleCodes) > 0, 403, 'Akses ditolak.');

        $query = DokumenSpp::with([
            'tagihan.detailPerjaldin',
            'tagihan.komponenPerjaldin',
            'tagihanPerjaldinKomponen.dipaRevisionItem.coa',
            'ppkVerifikator',
            'dibuatOleh',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals.actedByUser',
        ])
        ->whereNotNull('tagihan_perjaldin_komponen_id');

        // Filter berdasarkan Assigned Role/User
        $query->whereHas('workflowInstances', function ($q) use ($roleCodes, $user) {
            $q->whereHas('approvals', function ($q2) use ($roleCodes, $user) {
                $q2->whereIn('role_code', $roleCodes)
                   ->where(function ($q3) use ($user) {
                       $q3->whereNull('assigned_user_id')
                          ->orWhere('assigned_user_id', $user->id);
                   });
            });
        });

        $allSpps = $query->latest()->get();

        $listMenunggu = collect();
        $listDisetujui = collect();
        $listRevisi = collect();
        $listSelesai = collect();

        foreach ($allSpps as $spp) {
            $wf = $spp->workflowInstances->first();
            if (!$wf) continue;

            $myApprovals = $wf->approvals->whereIn('role_code', $roleCodes);
            if ($myApprovals->isEmpty()) continue;

            // Jika ada multi-role, kita utamakan yang PENDING
            $approval = $myApprovals->where('status', 'PENDING')->first() ?? $myApprovals->first();

            if ($wf->status === 'REVISION') {
                $statusFinal = 'Perlu Revisi';
            } elseif ($wf->status === 'APPROVED') {
                $statusFinal = 'Selesai Diverifikasi';
            } else {
                $pendingCount = $wf->approvals->where('status', 'PENDING')->count();
                $statusFinal = $pendingCount > 1 ? 'Menunggu Verifikasi' : 'Dalam Proses';
            }

            $spp->statusFinal = $statusFinal;
            $spp->myApprovalStatus = $approval->status;
            
            // For view compatibility
            $spp->ppkApprovalStatus = $wf->approvals->where('role_code', 'PPK')->first()?->status ?? 'N/A';
            $spp->kasApprovalStatus = $wf->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->first()?->status ?? 'N/A';
            $spp->koordinatorApprovalStatus = $wf->approvals->where('role_code', 'Koordinator Keuangan')->first()?->status ?? 'N/A';
            $spp->workflow = $wf;
            $spp->canAct = ($approval->status === 'PENDING' && $wf->status === 'IN_PROGRESS' && (int)$wf->step_saat_ini === (int)$approval->urutan_step);

            if ($approval->status === 'PENDING') {
                $listMenunggu->push($spp);
            } elseif ($approval->status === 'APPROVED') {
                $listDisetujui->push($spp);
            } elseif ($approval->status === 'REVISION') {
                $listRevisi->push($spp);
            }

            if ($statusFinal === 'Selesai Diverifikasi') {
                $listSelesai->push($spp);
            }
        }

        $viewSpps = match($request->get('status', 'Semua')) {
            'Pending' => $listMenunggu,
            'Approved' => $listDisetujui,
            'Revisi' => $listRevisi,
            default => $allSpps
        };
        if ($request->get('status', 'Semua') == 'Semua') {
             $viewSpps = $allSpps;
        }

        return view('verifikasi_spp_perjaldin.index', [
            'viewSpps' => $viewSpps,
            'countPending' => $listMenunggu->count(),
            'countApprovedMe' => $listDisetujui->count(),
            'countRevisi' => $listRevisi->count(),
            'countSelesai' => $listSelesai->unique('id')->count(),
            'roleLabel' => 'Verifikator SPP Perjaldin',
            'indexRoute' => 'verifikasi-spp.perjaldin.index',
            'showRoute' => 'verifikasi-spp.perjaldin.show',
            'roleSlug' => 'verifikator'
        ]);
    }

    public function show(int $id)
    {
        $user = Auth::user();
        $roleCodes = $this->activeRoleCodes($user);

        abort_unless(count($roleCodes) > 0, 403, 'Akses ditolak.');

        $spp = DokumenSpp::with([
            'tagihan.detailPerjaldin.pegawai',
            'tagihan.komponenPerjaldin.dipaRevisionItem.coa',
            'tagihan.komponenPerjaldin.dokumenSpp',
            'tagihanPerjaldinKomponen.dipaRevisionItem.coa',
            'ppkVerifikator',
            'dibuatOleh',
            'logs.user',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals.actedByUser',
        ])
        ->whereNotNull('tagihan_perjaldin_komponen_id')
        ->findOrFail($id);

        $wf = $spp->workflowInstances->first();
        abort_unless($wf, 404, 'Workflow tidak ditemukan untuk dokumen SPP ini.');

        $activeRoleApprovals = [];
        
        // Populate activeRoleApprovals based on user's active roles
        foreach ($roleCodes as $rc) {
            $approval = $wf->approvals->where('role_code', $rc)->first();
            if ($approval && $approval->status === 'PENDING' && $wf->status === 'IN_PROGRESS' && (int)$wf->step_saat_ini === (int)$approval->urutan_step) {
                $activeRoleApprovals[] = [
                    'role' => $rc,
                    'approval_id' => $approval->id,
                    'approveRoute' => route('verifikasi-spp.perjaldin.approve', $id),
                    'revisiRoute' => route('verifikasi-spp.perjaldin.revisi', $id)
                ];
            }
        }

        $latestRevisionNote = $wf->approvals->where('status', 'REVISION')->sortByDesc('acted_at')->first();

        // Status overall
        if ($wf->status === 'REVISION') {
            $statusFinal = 'Perlu Revisi';
        } elseif ($wf->status === 'APPROVED') {
            $statusFinal = 'Selesai Diverifikasi';
        } else {
            $pendingCount = $wf->approvals->where('status', 'PENDING')->count();
            $statusFinal = $pendingCount > 1 ? 'Menunggu Verifikasi' : 'Dalam Proses';
        }

        // Untuk visual timeline kasubbag
        $kasApproval = $wf->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->first();
        $ppkApproval = $wf->approvals->where('role_code', 'PPK')->first();
        $koordinatorApproval = $wf->approvals->where('role_code', 'Koordinator Keuangan')->first();
        
        $myApproval = $wf->approvals->whereIn('role_code', $roleCodes)->first();
        $canAct = count($activeRoleApprovals) > 0;
        
        $tagihan  = $spp->tagihan;
        $komponen = $spp->tagihanPerjaldinKomponen;

        $roleSlug     = 'verifikator';
        $indexRoute   = 'verifikasi-spp.perjaldin.index';
        $approveRoute = 'verifikasi-spp.perjaldin.approve';
        $revisiRoute  = 'verifikasi-spp.perjaldin.revisi';
        $roleLabel    = 'Verifikator SPP Perjaldin';

        return view('verifikasi_spp_perjaldin.show', compact(
            'spp', 'wf', 'tagihan', 'komponen', 'activeRoleApprovals', 'latestRevisionNote', 'statusFinal',
            'kasApproval', 'ppkApproval', 'koordinatorApproval', 'canAct', 'myApproval',
            'roleSlug', 'indexRoute', 'approveRoute', 'revisiRoute', 'roleLabel'
        ));
    }

    public function approve(Request $request, int $id)
    {
        $spp = DokumenSpp::with('workflowInstance.approvals')
            ->whereNotNull('tagihan_perjaldin_komponen_id')
            ->findOrFail($id);

        $instance = $spp->workflowInstance;
        abort_unless($instance, 404);

        $user = $request->user();
        if ($request->filled('approval_id')) {
            $approval = $instance->approvals->where('id', $request->input('approval_id'))->first();
            $roleCode = $approval ? $approval->role_code : $this->detectRoleCode($user);
        } else {
            $roleCode = $this->detectRoleCode($user);
            $approval = $instance->approvals->where('role_code', $roleCode)->first();
        }

        abort_unless($approval && $approval->status === 'PENDING', 403, 'Anda tidak memiliki aksi yang tersedia.');

        try {
            $this->workflowService->approve($approval, $user, $request->ip());
            $isFullyApproved = $instance->fresh()->status === 'APPROVED';

            if ($isFullyApproved) {
                $spp->update(['status' => 'DISETUJUI_SPP']);
                $this->syncParentTagihan($spp);
            }

            $statusBaru = $spp->fresh()->status;

            LogStatusDokumen::create([
                'dokumen_type'      => DokumenSpp::class,
                'dokumen_id'        => $spp->id,
                'user_id'           => $user->id,
                'role_saat_itu'     => $user->getRoleNames()->first() ?? $roleCode,
                'status_sebelumnya' => 'Menunggu Verifikasi',
                'status_baru'       => $statusBaru,
                'aksi'              => 'APPROVE_' . strtoupper(str_replace(' ', '_', $roleCode)),
                'catatan'           => $isFullyApproved
                    ? 'Dokumen SPP Perjaldin disetujui. Semua approver telah menyetujui — SPP final.'
                    : 'Dokumen SPP Perjaldin disetujui oleh ' . $roleCode . '.',
                'ip_address'        => $request->ip(),
            ]);

            $operators = User::role('Operator BLU')->get();
            Notification::send($operators, new WorkflowNotification([
                'title'   => $isFullyApproved ? 'SPP Perjaldin Disetujui Final' : 'SPP Perjaldin Disetujui ' . $roleCode,
                'message' => $isFullyApproved
                    ? "SPP {$spp->nomor_spp} telah disetujui oleh semua pihak dan siap lanjut ke SPM."
                    : "SPP {$spp->nomor_spp} telah disetujui oleh {$roleCode}.",
                'url'     => route('spps.perjaldin.detail', $spp->tagihan_id),
                'icon'    => 'verified',
                'color'   => 'success',
            ]));

            return back()->with('success', $isFullyApproved
                ? "SPP {$spp->nomor_spp} telah disetujui oleh semua pihak."
                : "SPP {$spp->nomor_spp} berhasil disetujui.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses persetujuan: ' . $e->getMessage());
        }
    }

    public function revisi(Request $request, int $id)
    {
        $request->validate([
            'catatan_revisi' => 'required|string|min:3|max:1000',
        ]);

        $spp = DokumenSpp::with('workflowInstance.approvals')
            ->whereNotNull('tagihan_perjaldin_komponen_id')
            ->findOrFail($id);

        $instance = $spp->workflowInstance;
        abort_unless($instance, 404);

        $user = $request->user();
        if ($request->filled('approval_id')) {
            $approval = $instance->approvals->where('id', $request->input('approval_id'))->first();
            $roleCode = $approval ? $approval->role_code : $this->detectRoleCode($user);
        } else {
            $roleCode = $this->detectRoleCode($user);
            $approval = $instance->approvals->where('role_code', $roleCode)->first();
        }

        abort_unless($approval && $approval->status === 'PENDING', 403, 'Anda tidak memiliki aksi yang tersedia.');

        try {
            $this->workflowService->requestRevision($approval, $user, $request->catatan_revisi, $request->ip());

            $statusBaru = $spp->fresh()->status;

            LogStatusDokumen::create([
                'dokumen_type'      => DokumenSpp::class,
                'dokumen_id'        => $spp->id,
                'user_id'           => $user->id,
                'role_saat_itu'     => $user->getRoleNames()->first() ?? $roleCode,
                'status_sebelumnya' => 'Menunggu Verifikasi',
                'status_baru'       => $statusBaru,
                'aksi'              => 'REVISI_' . strtoupper(str_replace(' ', '_', $roleCode)),
                'catatan'           => $request->catatan_revisi,
                'ip_address'        => $request->ip(),
            ]);

            $operators = User::role('Operator BLU')->get();
            Notification::send($operators, new WorkflowNotification([
                'title'   => 'SPP Perjaldin Direvisi',
                'message' => "SPP {$spp->nomor_spp} perlu revisi. Catatan: {$request->catatan_revisi}",
                'url'     => route('spps.perjaldin.detail', $spp->tagihan_id),
                'icon'    => 'error_outline',
                'color'   => 'danger',
            ]));

            return back()->with('warning', "SPP {$spp->nomor_spp} telah dikembalikan untuk revisi.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses revisi: ' . $e->getMessage());
        }
    }

""" + helpers_content

with open(file_path, "w") as fw:
    fw.write(new_controller)
print("Done refactoring SppPerjaldinVerifikasiController")
