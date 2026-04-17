<?php

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

    // =====================================================================
    //  SHARED: Build index data for a given role
    // =====================================================================

    private function buildIndexData(string $roleCode): array
    {
        $spps = DokumenSpp::with([
                'tagihan.detailPerjaldin',
                'tagihan.komponenPerjaldin',
                'tagihanPerjaldinKomponen.dipaRevisionItem.coa',
                'ppkVerifikator',
                'dibuatOleh',
                'workflowInstances' => fn($q) => $q->latest()->limit(1),
                'workflowInstances.approvals.actedByUser',
            ])
            ->whereNotNull('tagihan_perjaldin_komponen_id') // hanya SPP Perjaldin
            ->whereHas('workflowInstances.approvals', fn($q) => $q->where('role_code', $roleCode))
            ->latest()
            ->get();

        $processed = collect();

        foreach ($spps as $spp) {
            $wf = $spp->workflowInstances->first();
            if (!$wf) continue;

            $myApproval  = $wf->approvals->where('role_code', $roleCode)->first();
            $ppkApproval = $wf->approvals->where('role_code', 'PPK')->first();
            $kasApproval = $wf->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->first();

            // Determine combined status
            if ($wf->status === 'REVISION') {
                $statusFinal = 'Perlu Revisi';
            } elseif ($wf->status === 'APPROVED') {
                $statusFinal = 'Selesai Diverifikasi';
            } else {
                $pending = $wf->approvals->where('status', 'PENDING');
                $statusFinal = $pending->count() > 1
                    ? 'Menunggu Verifikasi'
                    : 'Dalam Proses';
            }

            $canAct = (
                $myApproval
                && $myApproval->status === 'PENDING'
                && $wf->status === 'IN_PROGRESS'
                && (int) $wf->step_saat_ini === (int) $myApproval->urutan_step
            );

            $spp->myApprovalStatus = $myApproval?->status ?? 'N/A';
            $spp->ppkApprovalStatus = $ppkApproval?->status ?? 'N/A';
            $spp->kasApprovalStatus = $kasApproval?->status ?? 'N/A';
            $spp->statusFinal = $statusFinal;
            $spp->canAct = $canAct;
            $spp->workflow = $wf;

            $processed->push($spp);
        }

        $countPending    = $processed->where('myApprovalStatus', 'PENDING')->count();
        $countApprovedMe = $processed->where('myApprovalStatus', 'APPROVED')->count();
        $countRevisi     = $processed->where('myApprovalStatus', 'REVISION')->count();
        $countSelesai    = $processed->where('statusFinal', 'Selesai Diverifikasi')->count();

        return compact('processed', 'countPending', 'countApprovedMe', 'countRevisi', 'countSelesai');
    }

    private function buildShowData(int $id, string $roleCode): array
    {
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

        $myApproval  = $wf->approvals->where('role_code', $roleCode)->first();
        $ppkApproval = $wf->approvals->where('role_code', 'PPK')->first();
        $kasApproval = $wf->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->first();

        // Determine combined status
        if ($wf->status === 'REVISION') {
            $statusFinal = 'Perlu Revisi';
        } elseif ($wf->status === 'APPROVED') {
            $statusFinal = 'Selesai Diverifikasi';
        } else {
            $pending = $wf->approvals->where('status', 'PENDING');
            $statusFinal = $pending->count() > 1
                ? 'Menunggu Verifikasi'
                : 'Dalam Proses';
        }

        $canAct = (
            $myApproval
            && $myApproval->status === 'PENDING'
            && $wf->status === 'IN_PROGRESS'
            && (int) $wf->step_saat_ini === (int) $myApproval->urutan_step
        );

        $latestRevisionNote = $wf->approvals
            ->where('status', 'REVISION')
            ->sortByDesc('acted_at')
            ->first();

        $tagihan  = $spp->tagihan;
        $komponen = $spp->tagihanPerjaldinKomponen;

        return compact(
            'spp', 'wf', 'tagihan', 'komponen',
            'myApproval', 'ppkApproval', 'kasApproval',
            'statusFinal', 'canAct', 'latestRevisionNote'
        );
    }

    // =====================================================================
    //  PPK — Index & Show
    // =====================================================================

    public function ppkIndex(Request $request)
    {
        $data = $this->buildIndexData('PPK');
        $viewSpps = $data['processed'];

        if ($request->has('status') && $request->status !== 'Semua') {
            $viewSpps = match ($request->status) {
                'Pending'  => $viewSpps->where('myApprovalStatus', 'PENDING'),
                'Approved' => $viewSpps->where('myApprovalStatus', 'APPROVED'),
                'Revisi'   => $viewSpps->where('myApprovalStatus', 'REVISION'),
                default    => $viewSpps,
            };
        }

        $roleLabel  = 'PPK';
        $roleSlug   = 'ppk';
        $indexRoute = 'verifikasi-ppk.spp-perjaldin.index';
        $showRoute  = 'verifikasi-ppk.spp-perjaldin.show';

        return view('verifikasi_spp_perjaldin.index', array_merge(
            $data,
            compact('viewSpps', 'roleLabel', 'roleSlug', 'indexRoute', 'showRoute')
        ));
    }

    public function ppkShow(int $id)
    {
        $data = $this->buildShowData($id, 'PPK');

        $roleLabel    = 'PPK';
        $roleSlug     = 'ppk';
        $indexRoute   = 'verifikasi-ppk.spp-perjaldin.index';
        $approveRoute = 'verifikasi-ppk.spp-perjaldin.approve';
        $revisiRoute  = 'verifikasi-ppk.spp-perjaldin.revisi';

        return view('verifikasi_spp_perjaldin.show', array_merge(
            $data,
            compact('roleLabel', 'roleSlug', 'indexRoute', 'approveRoute', 'revisiRoute')
        ));
    }

    // =====================================================================
    //  KASUBBAG — Index & Show
    // =====================================================================

    public function kasubbagIndex(Request $request)
    {
        $roleCode = 'Kepala Subbagian Keuangan dan Tata Usaha';
        $data     = $this->buildIndexData($roleCode);
        $viewSpps = $data['processed'];

        if ($request->has('status') && $request->status !== 'Semua') {
            $viewSpps = match ($request->status) {
                'Pending'  => $viewSpps->where('myApprovalStatus', 'PENDING'),
                'Approved' => $viewSpps->where('myApprovalStatus', 'APPROVED'),
                'Revisi'   => $viewSpps->where('myApprovalStatus', 'REVISION'),
                default    => $viewSpps,
            };
        }

        $roleLabel  = 'Kepala Subbagian Keuangan dan Tata Usaha';
        $roleSlug   = 'kasubbag';
        $indexRoute = 'verifikasi-kasubag.spp-perjaldin.index';
        $showRoute  = 'verifikasi-kasubag.spp-perjaldin.show';

        return view('verifikasi_spp_perjaldin.index', array_merge(
            $data,
            compact('viewSpps', 'roleLabel', 'roleSlug', 'indexRoute', 'showRoute')
        ));
    }

    public function kasubbagShow(int $id)
    {
        $roleCode = 'Kepala Subbagian Keuangan dan Tata Usaha';
        $data     = $this->buildShowData($id, $roleCode);

        $roleLabel    = 'Kepala Subbagian Keuangan dan Tata Usaha';
        $roleSlug     = 'kasubbag';
        $indexRoute   = 'verifikasi-kasubag.spp-perjaldin.index';
        $approveRoute = 'verifikasi-kasubag.spp-perjaldin.approve';
        $revisiRoute  = 'verifikasi-kasubag.spp-perjaldin.revisi';

        return view('verifikasi_spp_perjaldin.show', array_merge(
            $data,
            compact('roleLabel', 'roleSlug', 'indexRoute', 'approveRoute', 'revisiRoute')
        ));
    }

    // =====================================================================
    //  SHARED — Approve & Revisi actions
    // =====================================================================

    public function approve(Request $request, int $id)
    {
        $spp = DokumenSpp::with('workflowInstance.approvals')
            ->whereNotNull('tagihan_perjaldin_komponen_id')
            ->findOrFail($id);

        $instance = $spp->workflowInstance;
        abort_unless($instance, 404);

        $user     = $request->user();
        $roleCode = $this->detectRoleCode($user);
        $approval = $instance->approvals->where('role_code', $roleCode)->first();

        abort_unless($approval && $approval->status === 'PENDING', 403, 'Anda tidak memiliki aksi yang tersedia.');

        try {
            $this->workflowService->approve($approval, $user, 'Dokumen SPP Perjaldin disetujui.', $request->ip());

            // Refresh instance
            $instance->refresh();

            $statusBaru = $spp->fresh()->status;
            $isFullyApproved = $instance->status === 'APPROVED';

            if ($isFullyApproved) {
                // Sync parent tagihan
                $this->syncParentTagihan($spp);
            }

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

        $user     = $request->user();
        $roleCode = $this->detectRoleCode($user);
        $approval = $instance->approvals->where('role_code', $roleCode)->first();

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

    // =====================================================================
    //  Helpers
    // =====================================================================

    private function detectRoleCode(User $user): string
    {
        if ($user->hasRole('Kepala Subbagian Keuangan dan Tata Usaha')) {
            return 'Kepala Subbagian Keuangan dan Tata Usaha';
        }

        return 'PPK';
    }

    private function syncParentTagihan(DokumenSpp $spp): void
    {
        $tagihan = $spp->tagihan;

        if (!$tagihan || $tagihan->tipe_tagihan !== 'PERJALDIN') {
            return;
        }

        $komponens = $tagihan->komponenPerjaldin()
            ->where('total_nominal', '>', 0)
            ->with('dokumenSpp')
            ->get();

        if ($komponens->isEmpty()) return;

        $approvedStatuses = ['DISETUJUI_SPP', 'APPROVED'];
        $approvedCount = $komponens->filter(fn($k) =>
            $k->dokumenSpp && in_array($k->dokumenSpp->status, $approvedStatuses, true)
        )->count();

        if ($approvedCount === 0) return;

        $tagihan->update([
            'status' => $approvedCount === $komponens->count()
                ? 'SPP_LENGKAP'
                : 'SEBAGIAN_SPP_TERBIT',
        ]);
    }
}
