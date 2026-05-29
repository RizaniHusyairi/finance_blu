<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DokumenNpi;
use App\Models\DokumenSpm;
use App\Models\LogStatusDokumen;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\WorkflowService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class VerifikasiNpiPerjaldinController extends Controller
{
    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Helper to detect which role the controller should act as for the currently logged in user 
     * in the context of Verifikasi NPI.
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
     * Helper to map Role Code to a display-friendly label/badge format
     */
    private function roleLabel(string $roleCode): string
    {
        return match($roleCode) {
            'Bendahara Penerimaan' => 'Bendahara Penerimaan',
            'PPK' => 'Pejabat Pembuat Komitmen',
            'Kepala Subbagian Keuangan dan Tata Usaha' => 'Kasubbag Keuangan & TU',
            'Koordinator Keuangan' => 'Koordinator Keuangan',
            default => 'Unknown'
        };
    }

    /**
     * Daftar NPI Perjaldin yang butuh verifikasi
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $roleCodes = $this->activeRoleCodes($user);
        $roleCode = $roleCodes[0] ?? '';

        abort_unless(!empty($roleCodes), 403, 'Akses ditolak. Anda tidak memiliki peran verifikator yang valid.');

        // 1. Tampilkan hanya Dokumen NPI dari SPM yang memiliki komponen perjaldin
        $query = DokumenNpi::with([
            'spm.spp.tagihan.detailPerjaldin.pegawai',
            'spm.spp.tagihan.detailPerjaldin.provinsi',
            'spm.spp.tagihan.komponenPerjaldin',
            'bendaharaPenerimaan',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals'
        ])
        ->whereHas('spm.spp', function($q) {
            $q->whereNotNull('tagihan_perjaldin_komponen_id');
        })
        ->whereNotIn('status', [DokumenNpi::STATUS_DRAFT]); // Jangan tampilkan draft bendahara pengeluaran yang belum di-submit

        // Tampilkan NPI yang punya minimal satu baris approval untuk salah satu role user (multi-role)
        $query->whereHas('workflowInstances.approvals', function($q) use ($roleCodes, $user) {
            $q->whereIn('role_code', $roleCodes)->where(function($sq) use ($user) {
                $sq->whereNull('assigned_user_id')->orWhere('assigned_user_id', $user->id);
            });
        });

        $allNpis = $query->latest()->get();

        // Data Processed untuk Table dengan flag "is my action required"
        $processed = collect();
        foreach ($allNpis as $npi) {
            $wf = $npi->workflowInstances->first();
            if (!$wf) continue;

            // Semua baris approval relevan untuk user ini (lintas role)
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

            // Compute combined status for display
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

        // Apply Search Filter
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

        return view('verifikasi_npi_perjaldin.index', compact(
            'viewNpis', 'summary', 'statusFilter', 'search', 'roleCode', 'roleCodes', 'user'
        ));
    }

    /**
     * Halaman Detail Workspace NPI Perjaldin
     */
    public function show($id, Request $request)
    {
        $user = $request->user();
        $roleCodes = $this->activeRoleCodes($user);
        $roleCode = $roleCodes[0] ?? '';
        abort_unless(!empty($roleCodes), 403, 'Akses ditolak.');

        $npi = DokumenNpi::with([
            'spm.spp.tagihan.detailPerjaldin.pegawai',
            'spm.spp.tagihan.detailPerjaldin.provinsi',
            'spm.spp.tagihan.komponenPerjaldin',
            'spm.spp.tagihan.arsipDokumen',
            'spm.spp.tagihanPerjaldinKomponen.dipaRevisionItem.coa',
            'bendaharaPenerimaan',
            'logs.user',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals.actedByUser',
            'workflowInstances.approvals.assignedUser'
        ])
        ->whereHas('spm.spp', function($q) {
            $q->whereNotNull('tagihan_perjaldin_komponen_id');
        })
        ->findOrFail($id);

        $wf = $npi->workflowInstances->first();
        abort_unless($wf, 404, 'Workflow tidak ditemukan untuk dokumen NPI ini.');

        // User harus punya minimal satu baris approval pada dokumen ini (lintas role)
        $myApprovals = collect($wf->approvals)->filter(
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

        $canAct = $activeRoleApprovals->isNotEmpty();

        $spm = $npi->spm;
        $spp = $spm?->spp;
        $tagihan = $spp?->tagihan;
        $komponen = $spp?->tagihanPerjaldinKomponen;

        $approverBenpen = $wf->approvals->where('role_code', 'Bendahara Penerimaan')->first();
        $approverPpk = $wf->approvals->where('role_code', 'PPK')->first();
        $approverKasubbag = $wf->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->first();

        // Validasi kesiapan NPI Perjaldin
        $jumlahPeserta = collect($tagihan?->detailPerjaldin ?? [])->count();
        $coaReady = filled($komponen?->dipaRevisionItem?->coa);
        $checklist = collect([
            ['label' => 'Status SPM Awal', 'status' => 'ready', 'message' => 'SPM sumber telah disetujui final.'],
            ['label' => 'Rincian Peserta Perjaldin', 'status' => $jumlahPeserta > 0 ? 'ready' : 'missing', 'message' => $jumlahPeserta > 0 ? "$jumlahPeserta peserta perjalanan dinas tercatat." : 'Belum ada rincian peserta.'],
            ['label' => 'Tujuan Penerimaan NPI', 'status' => filled($npi->bendahara_penerimaan_id) ? 'ready' : 'missing', 'message' => filled($npi->bendahara_penerimaan_id) ? 'Bendahara Penerimaan tervalidasi.' : 'Penunjukan bendahara rumpang.'],
            ['label' => 'Kode COA / Mata Anggaran', 'status' => $coaReady ? 'ready' : 'missing', 'message' => $coaReady ? 'Beban anggaran DIPA tertaut.' : 'Akun DIPA belum tersedia.'],
        ])->values();

        $recentLogs = $npi->logs()->latest()->take(6)->get();

        return view('verifikasi_npi_perjaldin.show', compact(
            'npi', 'spm', 'spp', 'tagihan', 'komponen', 'wf',
            'myApproval', 'canAct', 'roleCode', 'roleCodes', 'activeRoleApprovals',
            'approverBenpen', 'approverPpk', 'approverKasubbag', 'checklist', 'recentLogs'
        ));
    }

    /**
     * Setujui NPI Perjaldin
     */
    public function approve(Request $request, $id)
    {
        $user = $request->user();
        $roleCodes = $this->activeRoleCodes($user);
        abort_unless(!empty($roleCodes), 403, 'Akses ditolak.');

        $npi = DokumenNpi::with('workflowInstances.approvals')->findOrFail($id);
        $wf = $this->workflowService->getActiveInstance($npi);

        abort_unless($wf, 404, 'Tidak ada antrean verifikasi aktif untuk NPI ini.');
        $wf->load('approvals');

        // Tentukan baris approval spesifik yang ditindak (mendukung user multi-role)
        $myApproval = $this->resolveActableApproval($wf, $roleCodes, $user, $npi, $request->input('approval_id'));
        abort_unless($myApproval, 403, 'Tidak ada antrean verifikasi aktif yang dapat Anda setujui pada NPI ini.');

        $roleCode = $myApproval->role_code;

        DB::beginTransaction();
        try {
            $catatan = $request->input('catatan', 'Disetujui oleh ' . $this->roleLabel($roleCode));

            $this->workflowService->approveCurrentStep($npi, $user->id, $catatan, $myApproval->id);
            $wf->refresh();

            $isFullyApproved = $wf->status === 'APPROVED';
            
            LogStatusDokumen::create([
                'dokumen_type'      => DokumenNpi::class,
                'dokumen_id'        => $npi->id,
                'user_id'           => $user->id,
                'role_saat_itu'     => $roleCode,
                'status_sebelumnya' => $npi->status,
                'status_baru'       => $isFullyApproved ? DokumenNpi::STATUS_DISETUJUI_FINAL : $npi->status,
                'aksi'              => 'APPROVE_' . strtoupper(str_replace(' ', '_', $roleCode)),
                'catatan'           => $catatan,
                'ip_address'        => $request->ip(),
            ]);

            if ($isFullyApproved && $npi->status !== DokumenNpi::STATUS_DISETUJUI_FINAL) {
                $npi->update(['status' => DokumenNpi::STATUS_DISETUJUI_FINAL]);

                // Notifikasi Final
                $pengeluaran = User::role('Bendahara Pengeluaran')->get();
                Notification::send($pengeluaran, new WorkflowNotification([
                    'title'   => 'NPI Perjaldin Disetujui Final',
                    'message' => "NPI {$npi->nomor_npi} telah diverifikasi sepenuhnya dan siap diproses lebih lanjut.",
                    'url'     => route('npis.perjaldin.detail', $npi->spm_id),
                    'icon'    => 'verified',
                    'color'   => 'success',
                ]));
            }

            DB::commit();
            return back()->with('success', 'Berhasil menyetujui dokumen NPI Perjaldin.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    /**
     * Tolak/Revisi NPI Perjaldin
     */
    public function reject(Request $request, $id)
    {
        $user = $request->user();
        $roleCodes = $this->activeRoleCodes($user);
        abort_unless(!empty($roleCodes), 403, 'Akses ditolak.');

        $request->validate([
            'catatan' => 'required|string|min:5|max:1000'
        ]);

        $npi = DokumenNpi::with('workflowInstances.approvals')->findOrFail($id);
        $wf = $this->workflowService->getActiveInstance($npi);

        abort_unless($wf, 404, 'Tidak ada antrean verifikasi aktif untuk NPI ini.');
        $wf->load('approvals');

        $myApproval = $this->resolveActableApproval($wf, $roleCodes, $user, $npi, $request->input('approval_id'));
        abort_unless($myApproval, 403, 'Tidak ada antrean verifikasi aktif yang dapat Anda revisi pada NPI ini.');

        $roleCode = $myApproval->role_code;

        DB::beginTransaction();
        try {
            $this->workflowService->requestRevision($npi, $user->id, $request->catatan, $myApproval->id);
            $wf->refresh();

            LogStatusDokumen::create([
                'dokumen_type'      => DokumenNpi::class,
                'dokumen_id'        => $npi->id,
                'user_id'           => $user->id,
                'role_saat_itu'     => $roleCode,
                'status_sebelumnya' => $npi->status,
                'status_baru'       => DokumenNpi::STATUS_REVISI,
                'aksi'              => 'REJECT_' . strtoupper(str_replace(' ', '_', $roleCode)),
                'catatan'           => $request->catatan,
                'ip_address'        => $request->ip(),
            ]);

            if ($npi->status !== DokumenNpi::STATUS_REVISI) {
                $npi->update(['status' => DokumenNpi::STATUS_REVISI]);

                // Notifikasi ke pembuat draft
                $pengeluaran = User::role('Bendahara Pengeluaran')->get();
                Notification::send($pengeluaran, new WorkflowNotification([
                    'title'   => 'NPI Perjaldin Revisi',
                    'message' => "NPI {$npi->nomor_npi} dikembalikan oleh {$roleCode} dengan catatan: {$request->catatan}",
                    'url'     => route('npis.perjaldin.detail', $npi->spm_id),
                    'icon'    => 'error_outline',
                    'color'   => 'danger',
                ]));
            }

            DB::commit();
            return back()->with('warning', 'Dokumen NPI Perjaldin berhasil direvisi/ditolak.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }
}
