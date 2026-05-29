<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DokumenNpi;
use App\Models\LogStatusDokumen;
use App\Models\User;
use App\Services\WorkflowService;
use Illuminate\Support\Facades\DB;

class VerifikasiNpiHonorController extends Controller
{
    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Daftar role verifikator NPI yang dimiliki user (mendukung 1 user multi-role).
     */
    private function activeRoleCodes(User $user): array
    {
        $roles = [];
        if ($user->hasRole('Bendahara Penerimaan')) $roles[] = 'Bendahara Penerimaan';
        if ($user->hasRole('PPK')) $roles[] = 'PPK';
        if ($user->hasRole('Kepala Subbagian Keuangan dan Tata Usaha')) $roles[] = 'Kepala Subbagian Keuangan dan Tata Usaha';
        if ($user->hasRole('Koordinator Keuangan')) $roles[] = 'Koordinator Keuangan';

        return $roles;
    }

    /**
     * Cek apakah satu baris approval dapat ditindak oleh user (sesuai role, penugasan, kepemilikan BenPen).
     */
    private function approvalBelongsToUser($approval, array $roleCodes, User $user, DokumenNpi $npi): bool
    {
        if (!in_array($approval->role_code, $roleCodes, true)) return false;
        if ($approval->assigned_user_id && (int) $approval->assigned_user_id !== (int) $user->id) return false;
        if ($approval->role_code === 'Bendahara Penerimaan' && (int) $npi->bendahara_penerimaan_id !== (int) $user->id) return false;

        return true;
    }

    /**
     * Cari approval pending yang sedang aktif (current step) untuk ditindak user.
     */
    private function resolveActableApproval($wf, array $roleCodes, User $user, DokumenNpi $npi, $approvalId = null)
    {
        $candidates = collect($wf?->approvals ?? [])->filter(function ($a) use ($roleCodes, $user, $npi, $wf) {
            return $a->status === 'PENDING'
                && $wf->status === 'IN_PROGRESS'
                && (int) $wf->step_saat_ini === (int) $a->urutan_step
                && $this->approvalBelongsToUser($a, $roleCodes, $user, $npi);
        });

        if ($approvalId) {
            return $candidates->firstWhere('id', (int) $approvalId);
        }

        return $candidates->first();
    }

    /**
     * Menampilkan daftar NPI Honorarium untuk diverifikasi
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $roleCodes = $this->activeRoleCodes($user);
        $roleCode = $roleCodes[0] ?? '';

        abort_unless(!empty($roleCodes), 403, 'Akses ditolak. Anda tidak diperkenankan.');

        // Hanya Dokumen NPI dari SPM Honorarium
        $query = DokumenNpi::with([
            'spm.spp.tagihan.detailHonorarium',
            'bendaharaPenerimaan',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals'
        ])
        ->whereHas('spm.spp.tagihan', function($q) {
            $q->where('tipe_tagihan', 'HONORARIUM');
        })
        ->whereNotIn('status', [DokumenNpi::STATUS_DRAFT]);

        // Tampilkan NPI yang punya minimal satu baris approval untuk salah satu role user
        $query->whereHas('workflowInstances.approvals', function($q) use ($roleCodes, $user) {
            $q->whereIn('role_code', $roleCodes)->where(function($sq) use ($user) {
                $sq->whereNull('assigned_user_id')->orWhere('assigned_user_id', $user->id);
            });
        });

        $allNpis = $query->latest()->get();

        $processed = collect();
        foreach ($allNpis as $npi) {
            $wf = $npi->workflowInstances->first();
            if (!$wf) continue;

            // Semua baris approval yang relevan untuk user ini (lintas role)
            $myApprovals = collect($wf->approvals)->filter(
                fn($a) => $this->approvalBelongsToUser($a, $roleCodes, $user, $npi)
            );

            if ($myApprovals->isEmpty()) continue;

            $pendingMine  = $myApprovals->where('status', 'PENDING');
            $revisionMine = $myApprovals->where('status', 'REVISION');
            $approvedMine = $myApprovals->where('status', 'APPROVED');

            $spm = $npi->spm;
            $spp = $spm?->spp;
            $npi->spmModel = $spm;
            $npi->sppModel = $spp;
            $npi->tagihanModel = $spp?->tagihan;
            $npi->nominal = $spm?->nominal_spm ?? 0;

            $npi->myApprovalStatus = $pendingMine->isNotEmpty() ? 'PENDING'
                : ($revisionMine->isNotEmpty() ? 'REVISION'
                : ($approvedMine->isNotEmpty() ? 'APPROVED' : 'N/A'));

            $npi->myRoles = $myApprovals->pluck('role_code')->unique()->values()->all();
            $npi->pendingRoleCount = $pendingMine->count();

            if ($wf->status === 'REVISION') {
                $npi->statusFinal = 'Perlu Revisi';
            } elseif ($wf->status === 'APPROVED') {
                $npi->statusFinal = 'Selesai';
            } else {
                $npi->statusFinal = $npi->status;
            }

            $npi->canAct = (
                $wf->status === 'IN_PROGRESS'
                && $pendingMine->contains(fn($a) => (int) $wf->step_saat_ini === (int) $a->urutan_step)
            );

            $npi->workflow = $wf;
            $processed->push($npi);
        }

        // Apply Status Filter
        $statusFilter = $request->input('status', 'semua');
        $viewNpis = $processed;
        if ($statusFilter !== 'semua') {
            $viewNpis = match ($statusFilter) {
                'pending'  => $viewNpis->where('myApprovalStatus', 'PENDING')->where('statusFinal', '!=', 'Perlu Revisi'),
                'approved' => $viewNpis->where('myApprovalStatus', 'APPROVED'),
                'revisi'   => $viewNpis->where('myApprovalStatus', 'REVISION'),
                'selesai'  => $viewNpis->where('statusFinal', 'Selesai'),
                default    => $viewNpis,
            };
        }

        // Apply Search
        $search = $request->input('search');
        if ($search) {
            $viewNpis = $viewNpis->filter(function($item) use ($search) {
                return str_contains(strtolower($item->nomor_npi), strtolower($search)) || 
                       str_contains(strtolower($item->spmModel?->nomor_spm), strtolower($search)) ||
                       str_contains(strtolower($item->sppModel?->nomor_spp), strtolower($search)) ||
                       str_contains(strtolower($item->tagihanModel?->nomor_tagihan), strtolower($search)) ||
                       str_contains(strtolower($item->tagihanModel?->deskripsi), strtolower($search));
            });
        }

        $summary = [
            'pending'  => $processed->where('myApprovalStatus', 'PENDING')->where('statusFinal', '!=', 'Perlu Revisi')->count(),
            'approved' => $processed->where('myApprovalStatus', 'APPROVED')->count(),
            'revisi'   => $processed->where('myApprovalStatus', 'REVISION')->count(),
            'selesai'  => $processed->where('statusFinal', 'Selesai')->count(),
        ];

        return view('verifikasi_npi_honor.index', compact(
            'viewNpis', 'summary', 'statusFilter', 'search', 'roleCode', 'roleCodes', 'user'
        ));
    }

    /**
     * Halaman Detail Workspace
     */
    public function show($id, Request $request)
    {
        $user = $request->user();
        $roleCodes = $this->activeRoleCodes($user);
        $roleCode = $roleCodes[0] ?? '';
        abort_unless(!empty($roleCodes), 403, 'Akses ditolak.');

        $npi = DokumenNpi::with([
            'spm.spp.tagihan.detailHonorarium',
            'spm.spp.tagihan.arsipDokumen',
            'spm.dipaRevisionItem.coa',
            'bendaharaPenerimaan',
            'logs.user',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals.actedByUser',
            'workflowInstances.approvals.assignedUser'
        ])
        ->whereHas('spm.spp.tagihan', function($q) {
            $q->where('tipe_tagihan', 'HONORARIUM');
        })
        ->findOrFail($id);

        $wf = $npi->workflowInstances->first();

        // User harus punya minimal satu baris approval pada dokumen ini
        $myApprovals = collect($wf?->approvals ?? [])->filter(
            fn($a) => $this->approvalBelongsToUser($a, $roleCodes, $user, $npi)
        )->values();
        abort_unless($myApprovals->isNotEmpty(), 403, 'Anda tidak memiliki peran verifikasi pada Dokumen NPI ini.');

        $myApproval = $myApprovals->first();

        // Daftar approval pending yang dapat ditindak user saat ini (per role)
        $activeRoleApprovals = $myApprovals->filter(function ($a) use ($wf) {
            return $a->status === 'PENDING'
                && $wf->status === 'IN_PROGRESS'
                && (int) $wf->step_saat_ini === (int) $a->urutan_step;
        })->values();

        $canVerify = $activeRoleApprovals->isNotEmpty();

        $spm = $npi->spm;
        $spp = $spm?->spp;
        $tagihan = $spp?->tagihan;

        // Paralel Verificators tracking
        $benpenApproval = collect($wf?->approvals ?? [])->firstWhere('role_code', 'Bendahara Penerimaan');
        $ppkApproval = collect($wf?->approvals ?? [])->firstWhere('role_code', 'PPK');
        $kasubbagApproval = collect($wf?->approvals ?? [])->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');

        // Logic display readiness
        $rekeningBermasalah = count(array_filter($tagihan->detailHonorarium->toArray(), fn($p) => empty($p['rekening']) || empty($p['nama_rekening'])));
        
        $checklist = collect([
            ['label' => 'Status SPM Awal', 'status' => 'ready', 'message' => 'Telah Disetujui Final'],
            ['label' => 'Kesehatan Rekening (-' . $rekeningBermasalah . ')', 'status' => $rekeningBermasalah === 0 ? 'ready' : 'missing', 'message' => $rekeningBermasalah > 0 ? "Terdapat entitas tanpa rekening." : 'Rekening komplit'],
            ['label' => 'Tujuan Penerimaan NPI', 'status' => filled($npi->bendahara_penerimaan_id) ? 'ready' : 'missing', 'message' => filled($npi->bendahara_penerimaan_id) ? 'Bendahara tervalidasi.' : 'Penunjukan rumpang.'],
        ])->values();

        $recentLogs = $npi->logs()->latest()->take(5)->get();

        return view('verifikasi_npi_honor.detail', compact(
            'npi', 'spm', 'spp', 'tagihan', 'wf', 'myApproval', 'canVerify',
            'activeRoleApprovals', 'benpenApproval', 'ppkApproval', 'kasubbagApproval',
            'checklist', 'roleCode', 'roleCodes', 'recentLogs'
        ));
    }

    /**
     * Handle approval Workflow
     */
    public function approve(Request $request, $id)
    {
        $user = $request->user();
        $roleCodes = $this->activeRoleCodes($user);
        abort_unless(!empty($roleCodes), 403, 'Akses ditolak.');

        $npi = DokumenNpi::with(['workflowInstances' => fn($q) => $q->latest()->limit(1), 'workflowInstances.approvals'])->findOrFail($id);
        $wf = $npi->workflowInstances->first();

        // Tentukan baris approval spesifik yang ditindak (mendukung user multi-role)
        $targetApproval = $this->resolveActableApproval($wf, $roleCodes, $user, $npi, $request->input('approval_id'));
        abort_unless($targetApproval, 403, 'Tidak ada antrean verifikasi aktif yang dapat Anda setujui pada NPI ini.');

        $roleCode = $targetApproval->role_code;

        try {
            DB::beginTransaction();

            $this->workflowService->approveCurrentStep($npi, $user->id, $request->input('catatan'), $targetApproval->id);
            $wf->refresh();
            $isFinished = $wf->status === 'APPROVED';

            if ($isFinished) {
                $npi->update(['status' => DokumenNpi::STATUS_MENUNGGU_UPLOAD]);
                LogStatusDokumen::create([
                    'dokumen_type' => DokumenNpi::class,
                    'dokumen_id' => $npi->id,
                    'user_id' => $user->id,
                    'role_saat_itu' => 'Sistem Verifikasi',
                    'status_baru' => DokumenNpi::STATUS_MENUNGGU_UPLOAD,
                    'aksi' => 'NPI_FINAL_APPROVED',
                    'catatan' => 'Form NPI Honorarium disetujui secara mufakat dan berstatus Menunggu Upload Fisik NPI.',
                    'ip_address' => request()->ip()
                ]);
            }

            LogStatusDokumen::create([
                'dokumen_type' => DokumenNpi::class,
                'dokumen_id' => $npi->id,
                'user_id' => $user->id,
                'role_saat_itu' => $roleCode,
                'status_baru' => $npi->status,
                'aksi' => 'APPROVE_VERIFIKASI_NPI_HONOR',
                'catatan' => "Tindakan Verifikasi {$roleCode} - Setuju. " . $request->input('catatan'),
                'ip_address' => request()->ip()
            ]);

            DB::commit();
            return redirect()->route('verifikasi-npi.honor.detail', $npi->id)->with('success', 'Verifikasi Setuju NPI Honorarium berhasil.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal memverifikasi: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle penolakan revisi
     */
    public function reject(Request $request, $id)
    {
        $request->validate(['catatan' => 'required|string|min:5']);

        $user = $request->user();
        $roleCodes = $this->activeRoleCodes($user);
        abort_unless(!empty($roleCodes), 403, 'Akses ditolak.');

        $npi = DokumenNpi::with(['workflowInstances' => fn($q) => $q->latest()->limit(1), 'workflowInstances.approvals'])->findOrFail($id);
        $wf = $npi->workflowInstances->first();

        $targetApproval = $this->resolveActableApproval($wf, $roleCodes, $user, $npi, $request->input('approval_id'));
        abort_unless($targetApproval, 403, 'Tidak ada antrean verifikasi aktif yang dapat Anda revisi pada NPI ini.');

        $roleCode = $targetApproval->role_code;

        try {
            DB::beginTransaction();

            $this->workflowService->requestRevision($npi, $user->id, $request->input('catatan'), $targetApproval->id);

            $npi->update(['status' => DokumenNpi::STATUS_REVISI]);

            LogStatusDokumen::create([
                'dokumen_type' => DokumenNpi::class,
                'dokumen_id' => $npi->id,
                'user_id' => $user->id,
                'role_saat_itu' => $roleCode,
                'status_baru' => DokumenNpi::STATUS_REVISI,
                'aksi' => 'REVISI_VERIFIKASI_NPI_HONOR',
                'catatan' => "Menggagalkan persetujuan - Membutuhkan Revisi NPI: " . $request->catatan,
                'ip_address' => request()->ip()
            ]);

            DB::commit();
            return redirect()->route('verifikasi-npi.honor.detail', $npi->id)->with('success', 'NPI Honorarium diblokir dan dikembalikan divalidasi ke meja draft Revisi.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal mengembalikan berkas (revisi): ' . $e->getMessage()]);
        }
    }
}
