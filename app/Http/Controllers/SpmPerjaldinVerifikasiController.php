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

    private function buildIndexData(array $roleCodes): array
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
            ->whereHas('workflowInstances.approvals', fn($q) => $q->whereIn('role_code', $roleCodes))
            ->latest()
            ->get();

        $processed = collect();

        foreach ($spms as $spm) {
            $wf = $spm->workflowInstances->first();
            if (!$wf) continue;

            $myApprovals = $wf->approvals->whereIn('role_code', $roleCodes);
            $myApproval  = $myApprovals->where('status', 'PENDING')->first() ?: $myApprovals->first();
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

    private function buildShowData(int $id, array $roleCodes): array
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

        $myApprovals = $wf->approvals->whereIn('role_code', $roleCodes);
        $myApproval  = $myApprovals->where('status', 'PENDING')->first() ?: $myApprovals->first();
        $ppspmApproval = $wf->approvals->where('role_code', 'PPSPM')->first();
        $kasApproval = $wf->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->first();
        $koordinatorApproval = $wf->approvals->where('role_code', 'Koordinator Keuangan')->first();

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

        // Detect dual-role: build array of all role approvals for this user
        $user = auth()->user();
        $activeRoleApprovals = [];
        
        if ($user->hasRole('PPSPM') && $ppspmApproval && $ppspmApproval->status === 'PENDING' && $wf->status === 'IN_PROGRESS') {
            $activeRoleApprovals[] = [
                'role' => 'PPSPM',
                'approval_id' => $ppspmApproval->id,
                'approveRoute' => route('verifikasi-ppspm.spm-perjaldin.approve', $id),
                'revisiRoute' => route('verifikasi-ppspm.spm-perjaldin.revisi', $id)
            ];
        }
        
        if ($user->hasRole('Koordinator Keuangan') && $koordinatorApproval && $koordinatorApproval->status === 'PENDING' && $wf->status === 'IN_PROGRESS') {
            $activeRoleApprovals[] = [
                'role' => 'Koordinator Keuangan',
                'approval_id' => $koordinatorApproval->id,
                'approveRoute' => route('verifikasi-koordinator.spm-perjaldin.approve', $id),
                'revisiRoute' => route('verifikasi-koordinator.spm-perjaldin.revisi', $id)
            ];
        }

        if ($user->hasRole('Kepala Subbagian Keuangan dan Tata Usaha') && $kasApproval && $kasApproval->status === 'PENDING' && $wf->status === 'IN_PROGRESS') {
            $activeRoleApprovals[] = [
                'role' => 'Kepala Subbagian Keuangan dan Tata Usaha',
                'approval_id' => $kasApproval->id,
                'approveRoute' => route('verifikasi-kasubag.spm-perjaldin.approve', $id),
                'revisiRoute' => route('verifikasi-kasubag.spm-perjaldin.revisi', $id)
            ];
        }

        $latestRevisionNote = $wf->approvals
            ->where('status', 'REVISION')
            ->sortByDesc('acted_at')
            ->first();

        $spp      = $spm->spp;
        $tagihan  = $spp?->tagihan;
        $komponen = $spp?->tagihanPerjaldinKomponen;

        return compact(
            'spm', 'spp', 'wf', 'tagihan', 'komponen',
            'myApproval', 'ppspmApproval', 'kasApproval', 'koordinatorApproval',
            'statusFinal', 'canAct', 'latestRevisionNote', 'activeRoleApprovals'
        );
    }

    // =====================================================================
    //  PPSPM — Index & Show
    // =====================================================================

    public function ppspmIndex(Request $request)
    {
        $roles = [];
        if (Auth::user()->hasRole('PPSPM')) $roles[] = 'PPSPM';
        if (Auth::user()->hasRole('Koordinator Keuangan')) $roles[] = 'Koordinator Keuangan';
        if (empty($roles)) abort(403);
        $data = $this->buildIndexData($roles);
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
        $roles = [];
        if (Auth::user()->hasRole('PPSPM')) $roles[] = 'PPSPM';
        if (Auth::user()->hasRole('Koordinator Keuangan')) $roles[] = 'Koordinator Keuangan';
        if (empty($roles)) abort(403);
        $data = $this->buildShowData($id, $roles);

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
        $roles = ['Kepala Subbagian Keuangan dan Tata Usaha'];
        $data     = $this->buildIndexData($roles);
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
        $data     = $this->buildShowData($id, [$roleCode]);

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

    public function koordinatorIndex(Request $request)
    {
        $roles = [];
        if (Auth::user()->hasRole('PPSPM')) $roles[] = 'PPSPM';
        if (Auth::user()->hasRole('Koordinator Keuangan')) $roles[] = 'Koordinator Keuangan';
        if (empty($roles)) abort(403);
        $data = $this->buildIndexData($roles);
        $viewSpms = $data['processed'];

        if ($request->has('status') && $request->status !== 'Semua') {
            $viewSpms = match ($request->status) {
                'Pending'  => $viewSpms->where('myApprovalStatus', 'PENDING'),
                'Approved' => $viewSpms->where('myApprovalStatus', 'APPROVED'),
                'Revisi'   => $viewSpms->where('myApprovalStatus', 'REVISION'),
                default    => $viewSpms,
            };
        }

        $roleLabel  = 'Koordinator Keuangan';
        $roleSlug   = 'koordinator';
        $indexRoute = 'verifikasi-koordinator.spm-perjaldin.index';
        $showRoute  = 'verifikasi-koordinator.spm-perjaldin.show';

        return view('verifikasi_spm_perjaldin.index', array_merge(
            $data,
            compact('viewSpms', 'roleLabel', 'roleSlug', 'indexRoute', 'showRoute')
        ));
    }

    public function koordinatorShow(int $id)
    {
        $roleCode = 'Koordinator Keuangan';
        $data     = $this->buildShowData($id, [$roleCode]);

        $roleLabel    = 'Koordinator Keuangan';
        $roleSlug     = 'koordinator';
        $indexRoute   = 'verifikasi-koordinator.spm-perjaldin.index';
        $approveRoute = 'verifikasi-koordinator.spm-perjaldin.approve';
        $revisiRoute  = 'verifikasi-koordinator.spm-perjaldin.revisi';

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
            $this->workflowService->approveCurrentStep($spm, $user->id, 'Dokumen SPM Perjaldin disetujui.', $approval->id);

            // Refresh instance
            $instance->refresh();
            
            $isFullyApproved = $instance->status === 'APPROVED';
            // Verifikasi paralel selesai: SPM langsung TERBIT ber-TTE QR (tanpa upload manual)
            // dan siap dibuatkan NPI oleh Bendahara Pengeluaran.
            $statusBaru = $isFullyApproved ? DokumenSpm::STATUS_SPM_TERBIT : DokumenSpm::STATUS_MENUNGGU_VERIFIKASI;

            if ($isFullyApproved && $spm->status !== DokumenSpm::STATUS_SPM_TERBIT) {
                $spm->update(['status' => DokumenSpm::STATUS_SPM_TERBIT]);
            }

            LogStatusDokumen::create([
                'dokumen_type'      => DokumenSpm::class,
                'dokumen_id'        => $spm->id,
                'user_id'           => $user->id,
                'role_saat_itu'     => $roleCode,
                'status_sebelumnya' => 'Menunggu Verifikasi',
                'status_baru'       => $statusBaru,
                'aksi'              => 'APPROVE_' . strtoupper(str_replace(' ', '_', $roleCode)),
                'catatan'           => $isFullyApproved
                    ? 'Dokumen SPM Perjaldin disetujui. SPM terbit ber-TTE QR dan siap dibuatkan NPI.'
                    : 'Dokumen SPM Perjaldin disetujui oleh ' . $roleCode . '.',
                'ip_address'        => $request->ip(),
            ]);

            $operators = User::role('Operator BLU')->get();
            Notification::send($operators, new WorkflowNotification([
                'title'   => $isFullyApproved ? 'SPM Perjaldin Terbit ber-TTE' : 'SPM Perjaldin Disetujui ' . $roleCode,
                'message' => $isFullyApproved
                    ? "SPM {$spm->nomor_spm} telah disetujui semua pihak dan terbit ber-TTE QR."
                    : "SPM {$spm->nomor_spm} telah disetujui oleh {$roleCode}.",
                'url'     => route('spms.perjaldin.detail', $spm->spp_id),
                'icon'    => 'verified',
                'color'   => 'success',
            ]));

            // Beritahu Bendahara Pengeluaran bahwa NPI Perjaldin dapat dibuat
            if ($isFullyApproved) {
                $bendahara = User::role('Bendahara Pengeluaran')->get();
                if ($bendahara->isNotEmpty()) {
                    Notification::send($bendahara, new WorkflowNotification([
                        'title'   => 'SPM Perjaldin Terbit — NPI Siap Dibuat',
                        'message' => "SPM {$spm->nomor_spm} telah terbit ber-TTE. NPI Perjalanan Dinas dapat segera Anda buat.",
                        'url'     => route('npis.perjaldin.index'),
                        'icon'    => 'receipt_long',
                        'color'   => 'success',
                    ]));
                }
            }

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
            $this->workflowService->requestRevision($spm, $user->id, $request->catatan_revisi, $approval->id);

            // Update SPM status to REVISI
            if ($spm->status !== DokumenSpm::STATUS_REVISI) {
                $spm->update(['status' => DokumenSpm::STATUS_REVISI]);
            }
            $statusBaru = DokumenSpm::STATUS_REVISI;

            LogStatusDokumen::create([
                'dokumen_type'      => DokumenSpm::class,
                'dokumen_id'        => $spm->id,
                'user_id'           => $user->id,
                'role_saat_itu'     => $roleCode,
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

        if ($user->hasRole('Koordinator Keuangan')) {
            return 'Koordinator Keuangan';
        }

        return 'PPSPM';
    }
}
