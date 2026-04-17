<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DokumenSpm;
use App\Models\LogStatusDokumen;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\WorkflowService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class SpmPerjaldinVerifikasiController extends Controller
{
    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    // =====================================================================
    //  SHARED: Build index data for a given role
    // =====================================================================

    private function buildIndexData(string $roleCode): array
    {
        $spms = DokumenSpm::with([
                'spp.tagihan.detailPerjaldin',
                'spp.tagihan.komponenPerjaldin',
                'spp.tagihanPerjaldinKomponen.dipaRevisionItem.coa',
                'ppspm',
                'dibuatOleh',
                'workflowInstances' => fn($q) => $q->latest()->limit(1),
                'workflowInstances.approvals.actedByUser',
            ])
            ->whereHas('spp', function($q) {
                $q->whereNotNull('tagihan_perjaldin_komponen_id');
            })
            ->whereHas('workflowInstances.approvals', fn($q) => $q->where('role_code', $roleCode))
            ->latest()
            ->get();

        $processed = collect();

        foreach ($spms as $spm) {
            $wf = $spm->workflowInstances->first();
            if (!$wf) continue;

            $myApproval  = $wf->approvals->where('role_code', $roleCode)->first();
            $ppspmApproval = $wf->approvals->where('role_code', 'PPSPM')->first();
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

            $spm->myApprovalStatus = $myApproval?->status ?? 'N/A';
            $spm->ppspmApprovalStatus = $ppspmApproval?->status ?? 'N/A';
            $spm->kasApprovalStatus = $kasApproval?->status ?? 'N/A';
            $spm->statusFinal = $statusFinal;
            $spm->canAct = $canAct;
            $spm->workflow = $wf;

            $processed->push($spm);
        }

        $countPending    = $processed->where('myApprovalStatus', 'PENDING')->count();
        $countApprovedMe = $processed->where('myApprovalStatus', 'APPROVED')->count();
        $countRevisi     = $processed->where('myApprovalStatus', 'REVISION')->count();
        $countSelesai    = $processed->where('statusFinal', 'Selesai Diverifikasi')->count();

        return compact('processed', 'countPending', 'countApprovedMe', 'countRevisi', 'countSelesai');
    }

    private function buildShowData(int $id, string $roleCode): array
    {
        $spm = DokumenSpm::with([
            'spp.tagihan.detailPerjaldin.pegawai',
            'spp.tagihan.komponenPerjaldin.dipaRevisionItem.coa',
            'spp.tagihan.komponenPerjaldin.dokumenSpp',
            'spp.tagihanPerjaldinKomponen.dipaRevisionItem.coa',
            'ppspm',
            'dibuatOleh',
            'logs.user',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals.actedByUser',
        ])
        ->whereHas('spp', function($q) {
            $q->whereNotNull('tagihan_perjaldin_komponen_id');
        })
        ->findOrFail($id);

        $wf = $spm->workflowInstances->first();
        abort_unless($wf, 404, 'Workflow tidak ditemukan untuk dokumen SPM ini.');

        $myApproval  = $wf->approvals->where('role_code', $roleCode)->first();
        $ppspmApproval = $wf->approvals->where('role_code', 'PPSPM')->first();
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

        $spp      = $spm->spp;
        $tagihan  = $spp?->tagihan;
        $komponen = $spp?->tagihanPerjaldinKomponen;

        return compact(
            'spm', 'spp', 'wf', 'tagihan', 'komponen',
            'myApproval', 'ppspmApproval', 'kasApproval',
            'statusFinal', 'canAct', 'latestRevisionNote'
        );
    }

    // =====================================================================
    //  PPSPM — Index & Show
    // =====================================================================

    public function ppspmIndex(Request $request)
    {
        $data = $this->buildIndexData('PPSPM');
        $viewSpms = $data['processed'];

        if ($request->has('status') && $request->status !== 'Semua') {
            $viewSpms = match ($request->status) {
                'Pending'  => $viewSpms->where('myApprovalStatus', 'PENDING'),
                'Approved' => $viewSpms->where('myApprovalStatus', 'APPROVED'),
                'Revisi'   => $viewSpms->where('myApprovalStatus', 'REVISION'),
                default    => $viewSpms,
            };
        }

        $roleLabel  = 'PPSPM';
        $roleSlug   = 'ppspm';
        $indexRoute = 'verifikasi-ppspm.spm-perjaldin.index';
        $showRoute  = 'verifikasi-ppspm.spm-perjaldin.show';

        return view('verifikasi_spm_perjaldin.index', array_merge(
            $data,
            compact('viewSpms', 'roleLabel', 'roleSlug', 'indexRoute', 'showRoute')
        ));
    }

    public function ppspmShow(int $id)
    {
        $data = $this->buildShowData($id, 'PPSPM');

        $roleLabel    = 'PPSPM';
        $roleSlug     = 'ppspm';
        $indexRoute   = 'verifikasi-ppspm.spm-perjaldin.index';
        $approveRoute = 'verifikasi-ppspm.spm-perjaldin.approve';
        $revisiRoute  = 'verifikasi-ppspm.spm-perjaldin.revisi';

        return view('verifikasi_spm_perjaldin.show', array_merge(
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
        $viewSpms = $data['processed'];

        if ($request->has('status') && $request->status !== 'Semua') {
            $viewSpms = match ($request->status) {
                'Pending'  => $viewSpms->where('myApprovalStatus', 'PENDING'),
                'Approved' => $viewSpms->where('myApprovalStatus', 'APPROVED'),
                'Revisi'   => $viewSpms->where('myApprovalStatus', 'REVISION'),
                default    => $viewSpms,
            };
        }

        $roleLabel  = 'Kepala Subbagian Keuangan dan Tata Usaha';
        $roleSlug   = 'kasubbag';
        $indexRoute = 'verifikasi-kasubag.spm-perjaldin.index';
        $showRoute  = 'verifikasi-kasubag.spm-perjaldin.show';

        return view('verifikasi_spm_perjaldin.index', array_merge(
            $data,
            compact('viewSpms', 'roleLabel', 'roleSlug', 'indexRoute', 'showRoute')
        ));
    }

    public function kasubbagShow(int $id)
    {
        $roleCode = 'Kepala Subbagian Keuangan dan Tata Usaha';
        $data     = $this->buildShowData($id, $roleCode);

        $roleLabel    = 'Kepala Subbagian Keuangan dan Tata Usaha';
        $roleSlug     = 'kasubbag';
        $indexRoute   = 'verifikasi-kasubag.spm-perjaldin.index';
        $approveRoute = 'verifikasi-kasubag.spm-perjaldin.approve';
        $revisiRoute  = 'verifikasi-kasubag.spm-perjaldin.revisi';

        return view('verifikasi_spm_perjaldin.show', array_merge(
            $data,
            compact('roleLabel', 'roleSlug', 'indexRoute', 'approveRoute', 'revisiRoute')
        ));
    }

    // =====================================================================
    //  SHARED — Approve & Revisi actions
    // =====================================================================

    public function approve(Request $request, int $id)
    {
        $spm = DokumenSpm::with('workflowInstance.approvals')
            ->whereHas('spp', function($q) {
                $q->whereNotNull('tagihan_perjaldin_komponen_id');
            })
            ->findOrFail($id);

        $instance = $spm->workflowInstance; // Note: Ensure relationship exists, else fallback to active
        if (!$instance) {
            $instance = $this->workflowService->getActiveInstance($spm);
        }
        abort_unless($instance, 404, 'Tidak ada workflow aktif untuk dokumen SPM ini.');

        $user     = $request->user();
        $roleCode = $this->detectRoleCode($user);
        $approval = $instance->approvals->where('role_code', $roleCode)->first();

        abort_unless($approval && $approval->status === 'PENDING', 403, 'Anda tidak memiliki aksi yang tersedia.');

        try {
            $this->workflowService->approveCurrentStep($spm, $user->id, 'Dokumen SPM Perjaldin disetujui.');

            // Refresh instance
            $instance->refresh();
            
            $isFullyApproved = $instance->status === 'APPROVED';
            $statusBaru = $isFullyApproved ? DokumenSpm::STATUS_DISETUJUI_FINAL : DokumenSpm::STATUS_MENUNGGU_VERIFIKASI;

            if ($isFullyApproved && $spm->status !== DokumenSpm::STATUS_DISETUJUI_FINAL) {
                $spm->update(['status' => DokumenSpm::STATUS_DISETUJUI_FINAL]);
            }

            LogStatusDokumen::create([
                'dokumen_type'      => DokumenSpm::class,
                'dokumen_id'        => $spm->id,
                'user_id'           => $user->id,
                'role_saat_itu'     => $user->getRoleNames()->first() ?? $roleCode,
                'status_sebelumnya' => 'Menunggu Verifikasi',
                'status_baru'       => $statusBaru,
                'aksi'              => 'APPROVE_' . strtoupper(str_replace(' ', '_', $roleCode)),
                'catatan'           => $isFullyApproved
                    ? 'Dokumen SPM Perjaldin disetujui. Semua approver telah menyetujui — SPM final.'
                    : 'Dokumen SPM Perjaldin disetujui oleh ' . $roleCode . '.',
                'ip_address'        => $request->ip(),
            ]);

            $operators = User::role('Operator BLU')->get();
            Notification::send($operators, new WorkflowNotification([
                'title'   => $isFullyApproved ? 'SPM Perjaldin Disetujui Final' : 'SPM Perjaldin Disetujui ' . $roleCode,
                'message' => $isFullyApproved
                    ? "SPM {$spm->nomor_spm} telah disetujui oleh semua pihak dan siap lanjut ke NPI."
                    : "SPM {$spm->nomor_spm} telah disetujui oleh {$roleCode}.",
                'url'     => route('spms.perjaldin.detail', $spm->spp_id),
                'icon'    => 'verified',
                'color'   => 'success',
            ]));

            return back()->with('success', $isFullyApproved
                ? "SPM {$spm->nomor_spm} telah disetujui oleh semua pihak."
                : "SPM {$spm->nomor_spm} berhasil disetujui.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses persetujuan: ' . $e->getMessage());
        }
    }

    public function revisi(Request $request, int $id)
    {
        $request->validate([
            'catatan_revisi' => 'required|string|min:3|max:1000',
        ]);

        $spm = DokumenSpm::with('workflowInstance.approvals')
            ->whereHas('spp', function($q) {
                $q->whereNotNull('tagihan_perjaldin_komponen_id');
            })
            ->findOrFail($id);

        $instance = $spm->workflowInstance;
        if (!$instance) {
            $instance = $this->workflowService->getActiveInstance($spm);
        }
        abort_unless($instance, 404, 'Tidak ada workflow aktif untuk dokumen SPM ini.');

        $user     = $request->user();
        $roleCode = $this->detectRoleCode($user);
        $approval = $instance->approvals->where('role_code', $roleCode)->first();

        abort_unless($approval && $approval->status === 'PENDING', 403, 'Anda tidak memiliki aksi yang tersedia.');

        try {
            $this->workflowService->requestRevision($spm, $user->id, $request->catatan_revisi);

            // Update SPM status to REVISI
            if ($spm->status !== DokumenSpm::STATUS_REVISI) {
                $spm->update(['status' => DokumenSpm::STATUS_REVISI]);
            }
            $statusBaru = DokumenSpm::STATUS_REVISI;

            LogStatusDokumen::create([
                'dokumen_type'      => DokumenSpm::class,
                'dokumen_id'        => $spm->id,
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
                'title'   => 'SPM Perjaldin Direvisi',
                'message' => "SPM {$spm->nomor_spm} perlu revisi. Catatan: {$request->catatan_revisi}",
                'url'     => route('spms.perjaldin.detail', $spm->spp_id),
                'icon'    => 'error_outline',
                'color'   => 'danger',
            ]));

            return back()->with('warning', "SPM {$spm->nomor_spm} telah dikembalikan untuk revisi.");
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

        return 'PPSPM';
    }
}
