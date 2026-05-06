<?php

namespace App\Http\Controllers;

use App\Models\DokumenSpp;
use App\Models\LogStatusDokumen;
use App\Models\Spp;
use App\Models\Tagihan;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Services\WorkflowService;

class SppVerifikasiController extends Controller
{
    private const SPP_KONTRAK_WORKFLOW = 'SPP_KONTRAK_PPK';

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

        $query = Spp::with([
            'tagihan.pihak',
            'tagihan.detailKontrak.kontrakTermin.kontrak.vendor',
            'tagihan.dipaRevisionItem.coa',
            'dipaRevisionItem.coa',
            'dibuatOleh',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals.actedByUser'
        ])
        ->whereHas('tagihan', fn ($q) => $q->where('tipe_tagihan', 'KONTRAK'))
        ->whereHas('workflowInstances.definition', fn ($q) => $q->where('kode', self::SPP_KONTRAK_WORKFLOW))
        ->whereNotIn('status', ['DRAFT']);

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
            $spp->kasubbagApprovalStatus = $wf->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->first()?->status ?? 'N/A';
            $spp->koordinatorApprovalStatus = $wf->approvals->where('role_code', 'Koordinator Keuangan')->first()?->status ?? 'N/A';

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

        return view('verifikasi_kasubag.spp_index', [
            'viewSpps' => $viewSpps,
            'countPending' => $listMenunggu->count(),
            'countApprovedMe' => $listDisetujui->count(),
            'countRevisi' => $listRevisi->count(),
            'countSelesai' => $listSelesai->unique('id')->count(),
            'roleLabel' => 'Verifikator SPP Kontrak',
            'indexRoute' => 'verifikasi-spp.kontrak.index',
            'showRoute' => 'verifikasi-spp.kontrak.show',
            'roleSlug' => 'verifikator'
        ]);
    }

    public function show($id)
    {
        $user = Auth::user();
        $roleCodes = $this->activeRoleCodes($user);

        abort_unless(count($roleCodes) > 0, 403, 'Akses ditolak.');

        $spp = Spp::with([
            'tagihan.pihak',
            'tagihan.detailKontrak.kontrakTermin.kontrak.vendor',
            'tagihan.dipaRevisionItem.coa',
            'tagihan.potonganTagihan.pajak',
            'tagihan.potonganTagihan.akunPotongan',
            'dipaRevisionItem.coa',
            'arsipDokumen',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals.actedByUser'
        ])->findOrFail($id);
        $this->ensureKontrakSpp($spp);

        $wf = $spp->workflowInstances->first();
        abort_unless($wf, 404, 'Workflow tidak ditemukan untuk dokumen ini.');

        $activeRoleApprovals = [];
        
        // Populate activeRoleApprovals based on user's active roles
        foreach ($roleCodes as $rc) {
            $approval = $wf->approvals->where('role_code', $rc)->first();
            if ($approval && $approval->status === 'PENDING' && $wf->status === 'IN_PROGRESS' && (int)$wf->step_saat_ini === (int)$approval->urutan_step) {
                $activeRoleApprovals[] = [
                    'role' => $rc,
                    'approval_id' => $approval->id,
                    'approveRoute' => route('verifikasi-spp.kontrak.approve', $id),
                    'revisiRoute' => route('verifikasi-spp.kontrak.revisi', $id)
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
        $kasubbagApproval = $wf->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->first();
        $ppkApproval = $wf->approvals->where('role_code', 'PPK')->first();
        $koordinatorApproval = $wf->approvals->where('role_code', 'Koordinator Keuangan')->first();
        
        $myApproval = $wf->approvals->whereIn('role_code', $roleCodes)->first();
        $canAct = count($activeRoleApprovals) > 0;
        
        $operatorApproval    = collect(['status' => 'APPROVED', 'acted_by_user_id' => $spp->dibuat_oleh_id, 'acted_at' => $spp->created_at]);

        $roleSlug     = 'verifikator';
        $indexRoute   = 'verifikasi-spp.kontrak.index';
        $approveRoute = 'verifikasi-spp.kontrak.approve';
        $revisiRoute  = 'verifikasi-spp.kontrak.revisi';
        $roleLabel    = 'Verifikator SPP Kontrak';

        return view('verifikasi_kasubag.spp_show', compact(
            'spp', 'wf', 'activeRoleApprovals', 'latestRevisionNote', 'statusFinal',
            'kasubbagApproval', 'ppkApproval', 'koordinatorApproval', 'canAct', 'myApproval', 'operatorApproval',
            'roleSlug', 'indexRoute', 'approveRoute', 'revisiRoute', 'roleLabel'
        ));
    }

    public function approve(Request $request, $id)
    {
        $spp = Spp::with(['tagihan', 'standingInstruction'])->findOrFail($id);
        $this->ensureKontrakSpp($spp);

        $approvalId = $request->input('approval_id');
        abort_unless($approvalId, 400, 'Approval ID diperlukan.');

        $approval = \App\Models\WorkflowApproval::find($approvalId);
        if ($approval && $approval->role_code === 'PPK') {
            if (!$spp->hasFinalSignedStandingInstruction()) {
                return back()->with('error', 'File Standing Instruction bertanda tangan wajib diunggah sebelum PPK menyetujui SPP.');
            }
        }

        $workflowFullyApproved = false;
        try {
            app(WorkflowService::class)->approveCurrentStep($spp, Auth::id(), 'Dokumen SPP disetujui.', $approvalId);
            $workflowFullyApproved = $this->finalizeWorkflowIfComplete($spp);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses persetujuan: ' . $e->getMessage());
        }

        if ($workflowFullyApproved) {
            $spp->update(['status' => $this->isPerjaldinSpp($spp) ? 'DISETUJUI_SPP' : 'APPROVED']);
            $this->syncParentTagihanAfterSppFinal($spp);
        } else {
            // Kita bisa atur status intermediate jika perlu, tapi 'Dalam Proses' / 'PENDING_KASUBBAG' sudah cukup
            if ($spp->status == 'Menunggu Verifikasi' || $spp->status == 'PENDING_PPK') {
                $spp->update(['status' => 'Dalam Proses']);
            }
        }

        $this->syncPerjaldinKomponenStatus($spp);

        $roleSaAtItu = Auth::user()?->getRoleNames()->first() ?? 'Verifikator';

        LogStatusDokumen::create([
            'dokumen_type' => DokumenSpp::class,
            'dokumen_id' => $spp->id,
            'user_id' => Auth::id(),
            'role_saat_itu' => $roleSaAtItu,
            'status_sebelumnya' => 'Menunggu Verifikasi',
            'status_baru' => $spp->status,
            'aksi' => 'APPROVE_SPP',
            'catatan' => 'Dokumen SPP disetujui oleh ' . $roleSaAtItu,
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', 'Dokumen berhasil disetujui.');
    }

    public function revisi(Request $request, $id)
    {
        $request->validate(['catatan_revisi' => 'required|string|max:1000']);

        $spp = Spp::with('tagihan')->findOrFail($id);
        $this->ensureKontrakSpp($spp);

        $approvalId = $request->input('approval_id');
        abort_unless($approvalId, 400, 'Approval ID diperlukan.');

        try {
            app(WorkflowService::class)->rejectCurrentStep($spp, Auth::id(), $request->catatan_revisi, $approvalId);
            $spp->update(['status' => 'REVISION']);
            
            // Sync jika perjaldin
            if ($this->isPerjaldinSpp($spp)) {
                $spp->tagihan()->update(['status' => 'REVISION']);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses revisi: ' . $e->getMessage());
        }

        $roleSaAtItu = Auth::user()?->getRoleNames()->first() ?? 'Verifikator';

        LogStatusDokumen::create([
            'dokumen_type' => DokumenSpp::class,
            'dokumen_id' => $spp->id,
            'user_id' => Auth::id(),
            'role_saat_itu' => $roleSaAtItu,
            'status_sebelumnya' => 'Menunggu Verifikasi',
            'status_baru' => 'REVISION',
            'aksi' => 'REVISI_SPP',
            'catatan' => $request->catatan_revisi,
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('verifikasi-spp.kontrak.index')->with('success', 'Dokumen dikembalikan untuk revisi.');
    }

    private function resolveOperatorDetailRoute(Spp $spp): string
    {
        $tagihan = $spp->tagihan;

        if (!$tagihan) {
            return route('spps.kontrak.index');
        }

        return match ($tagihan->tipe_tagihan) {
            'PERJALDIN' => route('spps.perjaldin.detail', $tagihan->id),
            'HONORARIUM' => route('spps.honor.detail', $tagihan->id),
            'KONTRAK' => route('spps.kontrak.detail', $tagihan->id),
            default => route('verifikasi-spp.kontrak.index'),
        };
    }

    // ==== MODUL VERIFIKASI SPP KASUBBAG ====

    public function kasubbagIndex(Request $request)
    {
        $roleName = 'Kepala Subbagian Keuangan dan Tata Usaha';

        $spps = Spp::with([
                'tagihan.pihak',
                'tagihan.detailKontrak.kontrakTermin.kontrak.vendor',
                'tagihan.dipaRevisionItem.coa',
                'dipaRevisionItem.coa',
                'workflowInstances' => function($q) {
                    $q->latest()->limit(1);
                },
                'workflowInstances.approvals'
            ])
            ->whereHas('tagihan', fn ($q) => $q->where('tipe_tagihan', 'KONTRAK'))
            ->whereHas('workflowInstances.definition', fn ($q) => $q->where('kode', self::SPP_KONTRAK_WORKFLOW))
            ->whereHas('workflowInstances', function($q) use ($roleName) {
                // Hanya SPP yang punya approval step untuk role Kasubbag
                $q->whereHas('approvals', function($a) use ($roleName) {
                    $a->where('role_code', $roleName);
                });
            })
            ->latest()
            ->get();

        $processedSpps = collect();
        foreach ($spps as $spp) {
            $wf = $spp->workflowInstances->first();
            if (!$wf) continue;

            $kasubbagApproval = $wf->approvals->where('role_code', $roleName)->first();
            $ppkApproval = $wf->approvals->where('role_code', 'PPK')->first();
            $koordinatorApproval = $wf->approvals->where('role_code', 'Koordinator Keuangan')->first();
            
            if ($wf->status === 'REVISION') {
                $statusFinal = 'Perlu Revisi';
            } elseif ($wf->status === 'APPROVED') {
                $statusFinal = 'Selesai Diverifikasi';
            } else {
                if ($ppkApproval && $ppkApproval->status === 'PENDING' && $kasubbagApproval && $kasubbagApproval->status === 'PENDING') {
                    $statusFinal = 'Menunggu Verifikasi';
                } elseif ($ppkApproval && $ppkApproval->status === 'PENDING') {
                    $statusFinal = 'Menunggu PPK';
                } elseif ($kasubbagApproval && $kasubbagApproval->status === 'PENDING') {
                    $statusFinal = 'Menunggu Kasubbag';
                } else {
                    $statusFinal = $wf->status;
                }
            }

            $canAct = (
                $kasubbagApproval
                && $kasubbagApproval->status === 'PENDING'
                && $wf->status === 'IN_PROGRESS'
                && (int) $wf->step_saat_ini === (int) $kasubbagApproval->urutan_step
            );

            $spp->kasubbagApprovalStatus = $kasubbagApproval ? $kasubbagApproval->status : 'N/A';
            $spp->ppkApprovalStatus = $ppkApproval ? $ppkApproval->status : 'N/A';
            $spp->koordinatorApprovalStatus = $koordinatorApproval ? $koordinatorApproval->status : 'N/A';
            $spp->statusFinal = $statusFinal;
            $spp->canAct = $canAct;
            $spp->workflow = $wf;
            
            $processedSpps->push($spp);
        }

        $countPending = $processedSpps->where('kasubbagApprovalStatus', 'PENDING')->count();
        $countApprovedMe = $processedSpps->where('kasubbagApprovalStatus', 'APPROVED')->count();
        $countRevisi = $processedSpps->where('kasubbagApprovalStatus', 'REVISION')->count();
        $countSelesai = $processedSpps->where('statusFinal', 'Selesai Diverifikasi')->count();

        $viewSpps = $processedSpps;
        
        if ($request->has('status') && $request->status !== 'Semua') {
            if ($request->status === 'Pending') {
                $viewSpps = $viewSpps->where('kasubbagApprovalStatus', 'PENDING');
            } elseif ($request->status === 'Approved') {
                $viewSpps = $viewSpps->where('kasubbagApprovalStatus', 'APPROVED');
            } elseif ($request->status === 'Revisi') {
                $viewSpps = $viewSpps->where('kasubbagApprovalStatus', 'REVISION');
            }
        }

        $roleSlug   = 'kasubbag';
        $indexRoute = 'verifikasi-kasubag.spp.index';
        $showRoute  = 'verifikasi-kasubag.spp.show';
        $roleLabel  = 'Kepala Subbagian Keuangan dan Tata Usaha';

        return view('verifikasi_kasubag.spp_index', compact(
            'viewSpps', 
            'countPending', 
            'countApprovedMe', 
            'countRevisi', 
            'countSelesai',
            'roleSlug',
            'indexRoute',
            'showRoute',
            'roleLabel'
        ));
    }

    public function kasubbagShow($id)
    {
        $roleName = 'Kepala Subbagian Keuangan dan Tata Usaha';

        $spp = Spp::with([
            'tagihan.pihak',
            'tagihan.detailKontrak.kontrakTermin.kontrak.vendor',
            'tagihan.dipaRevisionItem.coa',
            'tagihan.potonganTagihan.pajak',
            'tagihan.potonganTagihan.akunPotongan',
            'arsipDokumen',
            'workflowInstances' => function($q) {
                $q->latest()->limit(1);
            },
            'workflowInstances.approvals'
        ])->findOrFail($id);
        $this->ensureKontrakSpp($spp);

        $wf = $spp->workflowInstances->first();
        if (!$wf) {
            return back()->with('error', 'Workflow tidak ditemukan untuk dokumen ini.');
        }

        $kasubbagApproval = $wf->approvals->where('role_code', $roleName)->first();
        $ppkApproval = $wf->approvals->where('role_code', 'PPK')->first();
        $koordinatorApproval = $wf->approvals->where('role_code', 'Koordinator Keuangan')->first();
        $operatorApproval = collect(['status' => 'APPROVED', 'acted_by_user_id' => $spp->dibuat_oleh_id, 'acted_at' => $spp->created_at]);

        if ($wf->status === 'REVISION') {
            $statusFinal = 'Perlu Revisi';
        } elseif ($wf->status === 'APPROVED') {
            $statusFinal = 'Selesai Diverifikasi';
        } else {
            if ($ppkApproval && $ppkApproval->status === 'PENDING' && $kasubbagApproval && $kasubbagApproval->status === 'PENDING') {
                $statusFinal = 'Menunggu Verifikasi';
            } elseif ($ppkApproval && $ppkApproval->status === 'PENDING') {
                $statusFinal = 'Menunggu PPK';
            } elseif ($kasubbagApproval && $kasubbagApproval->status === 'PENDING') {
                $statusFinal = 'Menunggu Kasubbag';
            } else {
                $statusFinal = $wf->status;
            }
        }

        $canAct = (
            $kasubbagApproval
            && $kasubbagApproval->status === 'PENDING'
            && $wf->status === 'IN_PROGRESS'
            && (int) $wf->step_saat_ini === (int) $kasubbagApproval->urutan_step
        );

        $user = auth()->user();
        $activeRoleApprovals = [];
        
        if ($user->hasRole('Kepala Subbagian Keuangan dan Tata Usaha') && $kasubbagApproval && $kasubbagApproval->status === 'PENDING' && $wf->status === 'IN_PROGRESS') {
            $activeRoleApprovals[] = [
                'role' => 'Kepala Subbagian Keuangan dan Tata Usaha',
                'approval_id' => $kasubbagApproval->id,
                'approveRoute' => route('verifikasi-kasubag.spp.approve', $id),
                'revisiRoute' => route('verifikasi-kasubag.spp.revisi', $id)
            ];
        }

        $latestRevisionNote = null;
        $revisions = $wf->approvals->where('status', 'REVISION')->sortByDesc('acted_at');
        if ($revisions->isNotEmpty()) {
            $latestRevisionNote = $revisions->first();
        }

        // Variables for view-route compatibility (kasubbag default)
        $roleSlug     = 'kasubbag';
        $indexRoute   = 'verifikasi-kasubag.spp.index';
        $approveRoute = 'verifikasi-kasubag.spp.approve';
        $revisiRoute  = 'verifikasi-kasubag.spp.revisi';
        $roleLabel    = 'Kepala Subbagian Keuangan dan Tata Usaha';

        return view('verifikasi_kasubag.spp_show', compact(
            'spp', 
            'wf',
            'kasubbagApproval',
            'ppkApproval',
            'koordinatorApproval',
            'operatorApproval',
            'statusFinal',
            'canAct',
            'latestRevisionNote',
            'activeRoleApprovals',
            'roleSlug',
            'indexRoute',
            'approveRoute',
            'revisiRoute',
            'roleLabel'
        ));
    }

    public function approveKasubbag(Request $request, $spp_id)
    {
        $spp = Spp::with('tagihan')->findOrFail($spp_id);
        $this->ensureKontrakSpp($spp);

        try {
            $approvalId = $request->input('approval_id');
            app(WorkflowService::class)->approveCurrentStep($spp, Auth::id(), 'Dokumen SPP disetujui oleh Kasubbag.', $approvalId);
            
            $workflowFullyApproved = $this->finalizeWorkflowIfComplete($spp);

            if ($workflowFullyApproved) {
                // Semua approver (PPK + Kasubbag) sudah approve → status final
                $spp->update(['status' => $this->isPerjaldinSpp($spp) ? 'DISETUJUI_SPP' : 'APPROVED']);

                $this->syncParentTagihanAfterSppFinal($spp);
            }
            // Jika belum fully approved, tidak ubah status SPP — tetap Menunggu Verifikasi

            $statusBaru = $workflowFullyApproved ? $spp->status : 'Disetujui Kasubbag';
            $this->syncPerjaldinKomponenStatus($spp);

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSpp::class,
                'dokumen_id' => $spp->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? 'Kepala Subbagian Keuangan dan Tata Usaha',
                'status_sebelumnya' => 'Menunggu Verifikasi',
                'status_baru' => $statusBaru,
                'aksi' => 'APPROVE_KASUBBAG',
                'catatan' => $workflowFullyApproved
                    ? 'Dokumen SPP disetujui oleh Kasubbag. Seluruh approver telah menyetujui — SPP final.'
                    : 'Dokumen SPP disetujui oleh Kasubbag.',
                'ip_address' => request()->ip(),
            ]);

            if ($workflowFullyApproved) {
                $operators = User::role('Operator BLU')->get();
                Notification::send($operators, new WorkflowNotification([
                    'title' => 'SPP Disetujui Final',
                    'message' => "SPP {$spp->nomor_spp} telah disetujui oleh semua pihak dan siap lanjut ke SPM.",
                    'url' => $this->resolveOperatorDetailRoute($spp),
                    'icon' => 'verified',
                    'color' => 'success'
                ]));
            }

            return redirect()->route('verifikasi-kasubag.spp.index')->with('success', $workflowFullyApproved
                ? "SPP Nomor {$spp->nomor_spp} telah disetujui oleh semua pihak."
                : "SPP Nomor {$spp->nomor_spp} berhasil disetujui Kasubbag.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses approval: ' . $e->getMessage());
        }
    }

    public function revisiKasubbag(Request $request, $spp_id)
    {
        $request->validate([
            'catatan_revisi' => 'required|string|max:1000'
        ]);

        $spp = Spp::with('tagihan')->findOrFail($spp_id);
        $this->ensureKontrakSpp($spp);

        try {
            app(WorkflowService::class)->requestRevision($spp, Auth::id(), $request->catatan_revisi);

            $spp->update([
                'status' => $this->isPerjaldinSpp($spp) ? 'REVISI_KASUBBAG' : 'Revisi',
            ]);
            $this->syncPerjaldinKomponenStatus($spp);

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSpp::class,
                'dokumen_id' => $spp->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? 'Kepala Subbagian Keuangan dan Tata Usaha',
                'status_sebelumnya' => 'Menunggu Verifikasi Kasubbag',
                'status_baru' => $spp->status,
                'aksi' => 'REVISI_KASUBBAG',
                'catatan' => $request->catatan_revisi,
                'ip_address' => request()->ip(),
            ]);

            $operators = User::role('Operator BLU')->get();
            Notification::send($operators, new WorkflowNotification([
                'title' => 'SPP Direvisi Kasubbag',
                'message' => "SPP {$spp->nomor_spp} perlu revisi. Catatan: {$request->catatan_revisi}",
                'url' => $this->resolveOperatorDetailRoute($spp),
                'icon' => 'error_outline',
                'color' => 'danger'
            ]));

            return redirect()->route('verifikasi-kasubag.spp.index')->with('warning', "Catatan revisi untuk SPP {$spp->nomor_spp} telah dikirim.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses revisi: ' . $e->getMessage());
        }
    }

    // ==== MODUL VERIFIKASI SPP KOORDINATOR KEUANGAN ====

    public function koordinatorIndex(Request $request)
    {
        $roleName = 'Koordinator Keuangan';

        $spps = Spp::with([
                'tagihan.pihak',
                'tagihan.detailKontrak.kontrakTermin.kontrak.vendor',
                'tagihan.dipaRevisionItem.coa',
                'dipaRevisionItem.coa',
                'workflowInstances' => function($q) {
                    $q->latest()->limit(1);
                },
                'workflowInstances.approvals'
            ])
            ->whereHas('tagihan', fn ($q) => $q->where('tipe_tagihan', 'KONTRAK'))
            ->whereHas('workflowInstances.definition', fn ($q) => $q->where('kode', self::SPP_KONTRAK_WORKFLOW))
            ->whereHas('workflowInstances', function($q) use ($roleName) {
                $q->whereHas('approvals', function($a) use ($roleName) {
                    $a->where('role_code', $roleName);
                });
            })
            ->latest()
            ->get();

        $processedSpps = collect();
        foreach ($spps as $spp) {
            $wf = $spp->workflowInstances->first();
            if (!$wf) continue;

            $myApproval         = $wf->approvals->where('role_code', $roleName)->first();
            $ppkApproval        = $wf->approvals->where('role_code', 'PPK')->first();
            $kasubbagApproval   = $wf->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->first();

            if ($wf->status === 'REVISION') {
                $statusFinal = 'Perlu Revisi';
            } elseif ($wf->status === 'APPROVED') {
                $statusFinal = 'Selesai Diverifikasi';
            } else {
                $pending = $wf->approvals->where('status', 'PENDING');
                $statusFinal = $pending->count() > 1 ? 'Menunggu Verifikasi' : 'Dalam Proses';
            }

            $canAct = (
                $myApproval
                && $myApproval->status === 'PENDING'
                && $wf->status === 'IN_PROGRESS'
                && (int) $wf->step_saat_ini === (int) $myApproval->urutan_step
            );

            $spp->kasubbagApprovalStatus    = $kasubbagApproval ? $kasubbagApproval->status : 'N/A';
            $spp->ppkApprovalStatus         = $ppkApproval ? $ppkApproval->status : 'N/A';
            $spp->koordinatorApprovalStatus = $myApproval ? $myApproval->status : 'N/A';
            $spp->myApprovalStatus          = $myApproval ? $myApproval->status : 'N/A';
            $spp->statusFinal = $statusFinal;
            $spp->canAct = $canAct;
            $spp->workflow = $wf;

            $processedSpps->push($spp);
        }

        $countPending    = $processedSpps->where('myApprovalStatus', 'PENDING')->count();
        $countApprovedMe = $processedSpps->where('myApprovalStatus', 'APPROVED')->count();
        $countRevisi     = $processedSpps->where('myApprovalStatus', 'REVISION')->count();
        $countSelesai    = $processedSpps->where('statusFinal', 'Selesai Diverifikasi')->count();

        $viewSpps = $processedSpps;

        if ($request->has('status') && $request->status !== 'Semua') {
            if ($request->status === 'Pending') {
                $viewSpps = $viewSpps->where('myApprovalStatus', 'PENDING');
            } elseif ($request->status === 'Approved') {
                $viewSpps = $viewSpps->where('myApprovalStatus', 'APPROVED');
            } elseif ($request->status === 'Revisi') {
                $viewSpps = $viewSpps->where('myApprovalStatus', 'REVISION');
            }
        }

        $roleSlug   = 'koordinator';
        $indexRoute = 'verifikasi-koordinator.spp.index';
        $showRoute  = 'verifikasi-koordinator.spp.show';
        $roleLabel  = 'Koordinator Keuangan';

        return view('verifikasi_kasubag.spp_index', compact(
            'viewSpps',
            'countPending',
            'countApprovedMe',
            'countRevisi',
            'countSelesai',
            'roleSlug',
            'indexRoute',
            'showRoute',
            'roleLabel'
        ));
    }

    public function koordinatorShow($id)
    {
        $roleName = 'Koordinator Keuangan';

        $spp = Spp::with([
            'tagihan.pihak',
            'tagihan.detailKontrak.kontrakTermin.kontrak.vendor',
            'tagihan.dipaRevisionItem.coa',
            'tagihan.potonganTagihan.pajak',
            'tagihan.potonganTagihan.akunPotongan',
            'arsipDokumen',
            'workflowInstances' => function($q) {
                $q->latest()->limit(1);
            },
            'workflowInstances.approvals'
        ])->findOrFail($id);

        $wf = $spp->workflowInstances->first();
        if (!$wf) {
            return back()->with('error', 'Workflow tidak ditemukan untuk dokumen ini.');
        }

        $kasubbagApproval    = $wf->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->first();
        $ppkApproval         = $wf->approvals->where('role_code', 'PPK')->first();
        $koordinatorApproval = $wf->approvals->where('role_code', $roleName)->first();
        $operatorApproval    = collect(['status' => 'APPROVED', 'acted_by_user_id' => $spp->dibuat_oleh_id, 'acted_at' => $spp->created_at]);

        if ($wf->status === 'REVISION') {
            $statusFinal = 'Perlu Revisi';
        } elseif ($wf->status === 'APPROVED') {
            $statusFinal = 'Selesai Diverifikasi';
        } else {
            $pending = $wf->approvals->where('status', 'PENDING');
            $statusFinal = $pending->count() > 1 ? 'Menunggu Verifikasi' : 'Dalam Proses';
        }

        $canAct = (
            $koordinatorApproval
            && $koordinatorApproval->status === 'PENDING'
            && $wf->status === 'IN_PROGRESS'
            && (int) $wf->step_saat_ini === (int) $koordinatorApproval->urutan_step
        );

        $user = auth()->user();
        $activeRoleApprovals = [];
        
        if ($user->hasRole('PPK') && $ppkApproval && $ppkApproval->status === 'PENDING' && $wf->status === 'IN_PROGRESS') {
            $activeRoleApprovals[] = [
                'role' => 'PPK',
                'approval_id' => $ppkApproval->id,
                'approveRoute' => route('verifikasi-spp.kontrak.approve', $id),
                'revisiRoute' => route('verifikasi-spp.kontrak.revisi', $id)
            ];
        }
        if ($user->hasRole('Koordinator Keuangan') && $koordinatorApproval && $koordinatorApproval->status === 'PENDING' && $wf->status === 'IN_PROGRESS') {
            $activeRoleApprovals[] = [
                'role' => 'Koordinator Keuangan',
                'approval_id' => $koordinatorApproval->id,
                'approveRoute' => route('verifikasi-koordinator.spp.approve', $id),
                'revisiRoute' => route('verifikasi-koordinator.spp.revisi', $id)
            ];
        }

        $latestRevisionNote = null;
        $revisions = $wf->approvals->where('status', 'REVISION')->sortByDesc('acted_at');
        if ($revisions->isNotEmpty()) {
            $latestRevisionNote = $revisions->first();
        }

        $roleSlug     = 'koordinator';
        $indexRoute   = 'verifikasi-koordinator.spp.index';
        $approveRoute = 'verifikasi-koordinator.spp.approve';
        $revisiRoute  = 'verifikasi-koordinator.spp.revisi';
        $roleLabel    = 'Koordinator Keuangan';

        return view('verifikasi_kasubag.spp_show', compact(
            'spp',
            'wf',
            'kasubbagApproval',
            'ppkApproval',
            'koordinatorApproval',
            'operatorApproval',
            'statusFinal',
            'canAct',
            'latestRevisionNote',
            'activeRoleApprovals',
            'roleSlug',
            'indexRoute',
            'approveRoute',
            'revisiRoute',
            'roleLabel'
        ));
    }

    public function approveKoordinator(Request $request, $spp_id)
    {
        $spp = Spp::with('tagihan')->findOrFail($spp_id);
        $this->ensureKontrakSpp($spp);

        try {
            $approvalId = $request->input('approval_id');
            app(WorkflowService::class)->approveCurrentStep($spp, Auth::id(), 'Dokumen SPP disetujui oleh Koordinator Keuangan.', $approvalId);

            $workflowFullyApproved = $this->finalizeWorkflowIfComplete($spp);

            if ($workflowFullyApproved) {
                $spp->update(['status' => $this->isPerjaldinSpp($spp) ? 'DISETUJUI_SPP' : 'APPROVED']);
                $this->syncParentTagihanAfterSppFinal($spp);
            }

            $statusBaru = $workflowFullyApproved ? $spp->status : 'Disetujui Koordinator Keuangan';
            $this->syncPerjaldinKomponenStatus($spp);

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSpp::class,
                'dokumen_id' => $spp->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? 'Koordinator Keuangan',
                'status_sebelumnya' => 'Menunggu Verifikasi',
                'status_baru' => $statusBaru,
                'aksi' => 'APPROVE_KOORDINATOR_KEUANGAN',
                'catatan' => $workflowFullyApproved
                    ? 'Dokumen SPP disetujui oleh Koordinator Keuangan. Seluruh approver telah menyetujui — SPP final.'
                    : 'Dokumen SPP disetujui oleh Koordinator Keuangan.',
                'ip_address' => request()->ip(),
            ]);

            if ($workflowFullyApproved) {
                $operators = User::role('Operator BLU')->get();
                Notification::send($operators, new WorkflowNotification([
                    'title' => 'SPP Disetujui Final',
                    'message' => "SPP {$spp->nomor_spp} telah disetujui oleh semua pihak dan siap lanjut ke SPM.",
                    'url' => $this->resolveOperatorDetailRoute($spp),
                    'icon' => 'verified',
                    'color' => 'success'
                ]));
            }

            return redirect()->route('verifikasi-koordinator.spp.index')->with('success', $workflowFullyApproved
                ? "SPP Nomor {$spp->nomor_spp} telah disetujui oleh semua pihak."
                : "SPP Nomor {$spp->nomor_spp} berhasil disetujui Koordinator Keuangan.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses approval: ' . $e->getMessage());
        }
    }

    public function revisiKoordinator(Request $request, $spp_id)
    {
        $request->validate([
            'catatan_revisi' => 'required|string|max:1000'
        ]);

        $spp = Spp::with('tagihan')->findOrFail($spp_id);
        $this->ensureKontrakSpp($spp);

        try {
            $approvalId = $request->input('approval_id');
            app(WorkflowService::class)->requestRevision($spp, Auth::id(), $request->catatan_revisi, $approvalId);

            $spp->update([
                'status' => $this->isPerjaldinSpp($spp) ? 'REVISI_KOORDINATOR' : 'Revisi',
            ]);
            $this->syncPerjaldinKomponenStatus($spp);

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSpp::class,
                'dokumen_id' => $spp->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? 'Koordinator Keuangan',
                'status_sebelumnya' => 'Menunggu Verifikasi Koordinator Keuangan',
                'status_baru' => $spp->status,
                'aksi' => 'REVISI_KOORDINATOR_KEUANGAN',
                'catatan' => $request->catatan_revisi,
                'ip_address' => request()->ip(),
            ]);

            $operators = User::role('Operator BLU')->get();
            Notification::send($operators, new WorkflowNotification([
                'title' => 'SPP Direvisi Koordinator Keuangan',
                'message' => "SPP {$spp->nomor_spp} perlu revisi. Catatan: {$request->catatan_revisi}",
                'url' => $this->resolveOperatorDetailRoute($spp),
                'icon' => 'error_outline',
                'color' => 'danger'
            ]));

            return redirect()->route('verifikasi-koordinator.spp.index')->with('warning', "Catatan revisi untuk SPP {$spp->nomor_spp} telah dikirim.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses revisi: ' . $e->getMessage());
        }
    }

    private function finalizeWorkflowIfComplete(Spp $spp): bool
    {
        $workflow = $spp->workflowInstances()->with('approvals')->latest()->first();

        if (!$workflow) {
            return false;
        }

        $hasBlockingStatus = $workflow->approvals->contains(function ($approval) {
            return in_array($approval->status, ['PENDING', 'WAITING', 'REVISION', 'REJECTED'], true);
        });

        $allApproved = $workflow->approvals->isNotEmpty()
            && $workflow->approvals->every(fn ($approval) => $approval->status === 'APPROVED');

        if ($allApproved && !$hasBlockingStatus && $workflow->status !== 'APPROVED') {
            $workflow->update(['status' => 'APPROVED']);
        }

        return $allApproved && !$hasBlockingStatus;
    }

    private function ensureKontrakSpp(Spp $spp): void
    {
        abort_unless($spp->tagihan?->tipe_tagihan === 'KONTRAK', 404);
    }

    private function isPerjaldinSpp(Spp $spp): bool
    {
        return $spp->tagihan?->tipe_tagihan === 'PERJALDIN'
            || (bool) $spp->tagihan_perjaldin_komponen_id;
    }

    private function syncPerjaldinKomponenStatus(Spp $spp): void
    {
        if ($spp->tagihanPerjaldinKomponen) {
            app(\App\Services\PerjaldinKomponenService::class)
                ->syncKomponenStatus($spp->tagihanPerjaldinKomponen);
        }
    }

    private function syncParentTagihanAfterSppFinal(Spp $spp): void
    {
        $tagihan = $spp->tagihan;

        if (!$tagihan) {
            return;
        }

        if ($tagihan->tipe_tagihan !== 'PERJALDIN') {
            if ($tagihan->status === 'PROSES_SPP') {
                $tagihan->update(['status' => 'SPP_TERBIT']);
            }
            return;
        }

        $komponens = $tagihan->komponenPerjaldin()
            ->where('total_nominal', '>', 0)
            ->with('dokumenSpp')
            ->get();

        if ($komponens->isEmpty()) {
            return;
        }

        $approvedStatuses = ['DISETUJUI_SPP', 'APPROVED', 'Disetujui PPK'];
        $approvedCount = $komponens->filter(function ($komponen) use ($approvedStatuses) {
            return $komponen->dokumenSpp
                && in_array($komponen->dokumenSpp->status, $approvedStatuses, true);
        })->count();

        if ($approvedCount === 0) {
            return;
        }

        $tagihan->update([
            'status' => $approvedCount === $komponens->count()
                ? 'SPP_LENGKAP'
                : 'SEBAGIAN_SPP_TERBIT',
        ]);
    }
}
