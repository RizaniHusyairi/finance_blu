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
    /**
     * Tampilkan daftar SPP (untuk semua kategori) yang perlu diverifikasi PPK
     */
    public function sppIndex()
    {
        $spps = Spp::with(['tagihan', 'spm'])
            ->orderByRaw(
                "CASE 
                    WHEN status = ? THEN 1
                    WHEN status = ? THEN 1
                    WHEN status = ? THEN 2
                    WHEN status = ? THEN 2
                    ELSE 3
                END",
                ['Menunggu Verifikasi', 'PENDING_PPK', 'Revisi', 'REVISI_PPK']
            )
            ->latest()
            ->get();

        return view('verifikasi_ppk.spp_index', compact('spps'));
    }

    /**
     * Setujui SPP
     */
    public function approveSpp($spp_id)
    {
        $spp = Spp::with('tagihan')->findOrFail($spp_id);

        if (!in_array($spp->status, ['Menunggu Verifikasi', 'PENDING_PPK'], true)) {
            return back()->with('warning', "SPP {$spp->nomor_spp} tidak sedang menunggu verifikasi.");
        }

        // --- Workflow Engine: approve step aktif ---
        $workflowFullyApproved = false;
        try {
            app(WorkflowService::class)->approveCurrentStep($spp, Auth::id(), 'Dokumen SPP disetujui oleh PPK.');

            $workflowFullyApproved = $this->finalizeWorkflowIfComplete($spp);
        } catch (\RuntimeException $e) {
            // Workflow instance mungkin belum ada untuk SPP lama
        }

        if ($workflowFullyApproved) {
            // Semua approver (PPK + Kasubbag) sudah approve → status final
            $spp->update(['status' => $this->isPerjaldinSpp($spp) ? 'DISETUJUI_SPP' : 'APPROVED']);

            $this->syncParentTagihanAfterSppFinal($spp);
        } else {
            // Baru PPK saja yang approve, Kasubbag belum
            $spp->update(['status' => $this->isPerjaldinSpp($spp) ? 'PENDING_KASUBBAG' : 'Disetujui PPK']);
        }

        $statusBaru = $spp->status;
        $this->syncPerjaldinKomponenStatus($spp);

        LogStatusDokumen::create([
            'dokumen_type' => DokumenSpp::class,
            'dokumen_id' => $spp->id,
            'user_id' => Auth::id(),
            'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? 'PPK',
            'status_sebelumnya' => 'Menunggu Verifikasi',
            'status_baru' => $statusBaru,
            'aksi' => 'APPROVE_PPK',
            'catatan' => $workflowFullyApproved
                ? 'Dokumen SPP disetujui oleh PPK. Seluruh approver telah menyetujui — SPP final.'
                : 'Dokumen SPP disetujui oleh PPK.',
            'ip_address' => request()->ip(),
        ]);

        $operators = User::role('Operator BLU')->get();
        Notification::send($operators, new WorkflowNotification([
            'title' => $workflowFullyApproved ? 'SPP Disetujui Final' : 'SPP Disetujui PPK',
            'message' => $workflowFullyApproved
                ? "SPP {$spp->nomor_spp} telah disetujui oleh semua pihak dan siap lanjut ke SPM."
                : "SPP {$spp->nomor_spp} telah disetujui PPK. Menunggu verifikasi Kasubbag.",
            'url' => $this->resolveOperatorDetailRoute($spp),
            'icon' => 'verified',
            'color' => 'success'
        ]));

        return back()->with('success', $workflowFullyApproved
            ? "SPP Nomor {$spp->nomor_spp} telah disetujui oleh semua pihak."
            : "SPP Nomor {$spp->nomor_spp} berhasil disetujui PPK.");
    }

    /**
     * Kembalikan SPP ke Operator dengan catatan revisi
     */
    public function revisiSpp(Request $request, $spp_id)
    {
        $request->validate([
            'catatan_revisi' => 'required|string|max:1000'
        ]);

        $spp = Spp::with('tagihan')->findOrFail($spp_id);

        if (!in_array($spp->status, ['Menunggu Verifikasi', 'PENDING_PPK'], true)) {
            return back()->with('warning', "SPP {$spp->nomor_spp} tidak sedang menunggu verifikasi.");
        }

        $spp->update([
            'status' => $this->isPerjaldinSpp($spp) ? 'REVISI_PPK' : 'Revisi',
        ]);
        $this->syncPerjaldinKomponenStatus($spp);

        // --- Workflow Engine: request revision ---
        try {
            app(WorkflowService::class)->requestRevision($spp, Auth::id(), $request->catatan_revisi);
        } catch (\RuntimeException $e) {
            // Workflow instance mungkin belum ada untuk SPP lama, lanjutkan tanpa error
        }

        LogStatusDokumen::create([
            'dokumen_type' => DokumenSpp::class,
            'dokumen_id' => $spp->id,
            'user_id' => Auth::id(),
            'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? 'PPK',
            'status_sebelumnya' => 'Menunggu Verifikasi',
            'status_baru' => $spp->status,
            'aksi' => 'REVISI_PPK',
            'catatan' => $request->catatan_revisi,
            'ip_address' => request()->ip(),
        ]);

        $operators = User::role('Operator BLU')->get();
        Notification::send($operators, new WorkflowNotification([
            'title' => 'SPP Direvisi PPK',
            'message' => "SPP {$spp->nomor_spp} perlu revisi. Catatan: {$request->catatan_revisi}",
            'url' => $this->resolveOperatorDetailRoute($spp),
            'icon' => 'error_outline',
            'color' => 'danger'
        ]));

        return back()->with('warning', "Catatan revisi untuk SPP {$spp->nomor_spp} telah dikirim ke Operator BLU.");
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
            default => route('verifikasi-ppk.spp.index'),
        };
    }

    // ==== MODUL VERIFIKASI SPP KASUBBAG ====

    public function kasubbagIndex(Request $request)
    {
        $roleName = 'Kepala Subbagian Keuangan dan Tata Usaha';

        $spps = Spp::with([
                'tagihan.pihak',
                'tagihan.detailKontrak.kontrakTermin.kontrak',
                'workflowInstances' => function($q) {
                    $q->latest()->limit(1);
                },
                'workflowInstances.approvals'
            ])
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

        return view('verifikasi_kasubag.spp_index', compact(
            'viewSpps', 
            'countPending', 
            'countApprovedMe', 
            'countRevisi', 
            'countSelesai'
        ));
    }

    public function kasubbagShow($id)
    {
        $roleName = 'Kepala Subbagian Keuangan dan Tata Usaha';

        $spp = Spp::with([
            'tagihan.pihak',
            'tagihan.detailKontrak.kontrakTermin.kontrak',
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

        $kasubbagApproval = $wf->approvals->where('role_code', $roleName)->first();
        $ppkApproval = $wf->approvals->where('role_code', 'PPK')->first();
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

        $latestRevisionNote = null;
        $revisions = $wf->approvals->where('status', 'REVISION')->sortByDesc('acted_at');
        if ($revisions->isNotEmpty()) {
            $latestRevisionNote = $revisions->first();
        }

        return view('verifikasi_kasubag.spp_show', compact(
            'spp', 
            'wf',
            'kasubbagApproval',
            'ppkApproval',
            'operatorApproval',
            'statusFinal',
            'canAct',
            'latestRevisionNote'
        ));
    }

    public function approveKasubbag($spp_id)
    {
        $spp = Spp::with('tagihan')->findOrFail($spp_id);

        try {
            app(WorkflowService::class)->approveCurrentStep($spp, Auth::id(), 'Dokumen SPP disetujui oleh Kasubbag.');
            
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
