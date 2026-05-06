<?php

namespace App\Http\Controllers;

use App\Models\Tagihan;
use App\Models\WorkflowApproval;
use App\Services\TagihanKontrakWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Controller verifikasi tagihan kontrak yang melayani semua role:
 * PPK, PPSPM, Koordinator Keuangan, Bendahara Pengeluaran, Bendahara Penerimaan, Kasubbag.
 *
 * Endpoint sama untuk semua role; otorisasi & resolusi step ditentukan
 * berdasarkan role user yang login melalui WorkflowApproval.role_code.
 */
class TagihanKontrakVerifikasiController extends Controller
{
    /**
     * Daftar tagihan yang menunggu verifikasi user (login) saat ini.
     * Menampilkan tagihan kontrak yang punya pending approval pada urutan_step aktif
     * yang assigned ke user atau ke role-nya.
     */
    public function index(\Illuminate\Http\Request $request)
    {
        $user = Auth::user();
        $roleNames = $user->getRoleNames()->toArray();
        $roleCodes = $this->roleCodesFromRoleNames($roleNames);
        $userRole = $user->getRoleNames()->first() ?? '-';

        $base = Tagihan::query()
            ->where('tipe_tagihan', 'KONTRAK')
            ->when($request->filled('search'), function ($q) use ($request) {
                $kw = $request->input('search');
                $q->where(function ($w) use ($kw) {
                    $w->where('nomor_tagihan', 'like', "%{$kw}%")
                      ->orWhere('deskripsi', 'like', "%{$kw}%");
                });
            })
            ->when($request->filled('periode'), function ($q) use ($request) {
                [$y, $m] = explode('-', $request->input('periode'));
                $q->where('periode_tahun', (int) $y)->where('periode_bulan', (int) $m);
            })
            ->with([
                'detailKontrak.termin.kontrak.vendor',
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

        // Tagihan yang user-nya pernah memberikan keputusan
        $tagihansRiwayat = (clone $base)
            ->whereHas('workflowInstance.approvals', function ($qa) use ($user) {
                $qa->where('acted_by_user_id', $user->id)->whereNotNull('acted_at');
            })
            ->latest('updated_at')
            ->get();

        // Gabungan untuk summary cards (perlu + riwayat unik)
        $tagihans = $tagihansPerlu->merge($tagihansRiwayat)->unique('id');

        // Status mapping (untuk summary cards)
        $pendingStatuses = ['PENDING_VERIFIKASI_KONTRAK', 'PENDING_KASUBBAG', 'PENDING_PPK', 'PENDING_PPSPM',
            'PENDING_KOORDINATOR_KEUANGAN', 'PENDING_BENDAHARA_PENGELUARAN', 'PENDING_BENDAHARA_PENERIMAAN'];
        $revisiStatuses  = ['REVISI_PPK', 'REVISI_PPSPM', 'REVISI_KOORDINATOR_KEUANGAN',
            'REVISI_BENDAHARA_PENGELUARAN', 'REVISI_BENDAHARA_PENERIMAAN', 'REVISI_KASUBBAG'];
        $selesaiStatuses = ['READY_FOR_SPP', 'DISETUJUI_KONTRAK'];

        $detailRoute = 'verifikasi-tagihan-kontrak.show';

        return view('tagihan_kontrak_verifikasi.index', compact(
            'tagihansPerlu', 'tagihansRiwayat', 'tagihans',
            'pendingStatuses', 'revisiStatuses', 'selesaiStatuses',
            'detailRoute', 'userRole'
        ));
    }

    /**
     * Detail tagihan untuk verifikasi.
     */
    public function show($id, TagihanKontrakWorkflowService $svc)
    {
        $tagihan = Tagihan::with([
            'detailKontrak.termin.kontrak.vendor',
            'detailKontrak.termin.kontrak.ppkUser.profilable',
            'detailKontrak.termin.kontrak.arsipDokumen',
            'detailKontrak.arsipDokumen',
            'workflowInstance.approvals.actedByUser',
            'workflowInstance.approvals.assignedUser',
            'logs.user',
        ])->findOrFail($id);

        abort_unless($tagihan->tipe_tagihan === 'KONTRAK', 404);

        $user = Auth::user();

        // Resolve ALL pending approvals for dual-role users (e.g. PPSPM + Koordinator)
        $myApprovals = $this->resolvePendingApprovalsForUser($tagihan, $user);
        $myApproval = $myApprovals->first(); // backwards compat
        $canAct = $myApprovals->isNotEmpty();

        // Group approvals by step untuk UI
        $instance = $tagihan->workflowInstance;
        $approvalsByStep = $instance
            ? $instance->approvals->groupBy('urutan_step')
            : collect();

        return view('tagihan_kontrak_verifikasi.show', compact(
            'tagihan', 'myApproval', 'myApprovals', 'canAct', 'instance', 'approvalsByStep'
        ));
    }

    /**
     * Tampilkan dokumen arsip dari kontrak induk (SPK / SPMK / Ringkasan Kontrak final-ttd).
     */
    public function viewKontrakArsip($id, string $jenis)
    {
        $allowed = ['SPK_FINAL_TTD', 'SPMK_FINAL_TTD', 'RINGKASAN_KONTRAK_FINAL_TTD', 'SPK', 'SPMK', 'RINGKASAN_KONTRAK'];
        $jenis = strtoupper($jenis);
        abort_unless(in_array($jenis, $allowed, true), 404);

        $tagihan = Tagihan::with(['detailKontrak.termin.kontrak.arsipDokumen'])->findOrFail($id);
        abort_unless($tagihan->tipe_tagihan === 'KONTRAK', 404);

        $kontrak = $tagihan->detailKontrak?->termin?->kontrak;
        abort_unless($kontrak, 404);

        $arsip = $kontrak->arsipDokumen
            ->where('is_active', true)
            ->firstWhere('jenis_dokumen', $jenis);
        abort_unless($arsip, 404);

        $disk = \Illuminate\Support\Facades\Storage::disk($arsip->disk ?: 'public');
        abort_unless($disk->exists($arsip->path_file), 404);

        return $disk->response($arsip->path_file, $arsip->nama_file_asli ?: basename($arsip->path_file));
    }

    /**
     * Tampilkan arsip detail tagihan kontrak untuk role verifikator.
     */
    public function viewArsip($id, $arsipId)
    {
        $allowed = [
            'BAPP_FINAL_TTD',
            'BAST_FINAL_TTD',
            'BAP_FINAL_TTD',
            'BAPP_GAMBAR_RAB',
            'INVOICE',
            'LAMPIRAN_LAINNYA',
            'FAKTUR_PAJAK',
        ];

        $tagihan = Tagihan::with(['detailKontrak.arsipDokumen'])->findOrFail($id);
        abort_unless($tagihan->tipe_tagihan === 'KONTRAK', 404);

        $detailKontrak = $tagihan->detailKontrak;
        abort_unless($detailKontrak, 404);

        $arsip = $detailKontrak->arsipDokumen
            ->first(fn ($item) => (int) $item->id === (int) $arsipId
                && (bool) $item->is_active
                && in_array($item->jenis_dokumen, $allowed, true));

        abort_unless($arsip, 404);

        $disk = Storage::disk($arsip->disk ?: 'public');
        abort_unless($disk->exists($arsip->path_file), 404);

        return $disk->response(
            $arsip->path_file,
            $arsip->nama_file_asli ?: basename($arsip->path_file)
        );
    }

    public function approve(Request $request, $id, TagihanKontrakWorkflowService $svc)
    {
        $request->validate(['catatan' => 'nullable|string|max:1000']);

        $tagihan = Tagihan::findOrFail($id);
        abort_unless($tagihan->tipe_tagihan === 'KONTRAK', 404);

        $approval = $this->resolveTargetApproval($request, $tagihan);
        if (! $approval) {
            return back()->withErrors(['error' => 'Anda tidak memiliki approval yang pending pada tagihan ini.']);
        }

        try {
            $svc->approve($approval, Auth::user(), $request->input('catatan'), $request->ip());
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('verifikasi-tagihan-kontrak.show', $tagihan->id)
            ->with('success', 'Tagihan berhasil di-approve.');
    }

    public function revisi(Request $request, $id, TagihanKontrakWorkflowService $svc)
    {
        $request->validate(['catatan' => 'required|string|max:1000']);

        $tagihan = Tagihan::findOrFail($id);
        abort_unless($tagihan->tipe_tagihan === 'KONTRAK', 404);

        $approval = $this->resolveTargetApproval($request, $tagihan);
        if (! $approval) {
            return back()->withErrors(['error' => 'Anda tidak memiliki approval yang pending pada tagihan ini.']);
        }

        try {
            $svc->requestRevision($approval, Auth::user(), $request->input('catatan'), $request->ip());
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('verifikasi-tagihan-kontrak.index')
            ->with('success', 'Permintaan revisi terkirim ke Pejabat Pengadaan.');
    }

    public function reject(Request $request, $id, TagihanKontrakWorkflowService $svc)
    {
        $request->validate(['catatan' => 'required|string|max:1000']);

        $tagihan = Tagihan::findOrFail($id);
        abort_unless($tagihan->tipe_tagihan === 'KONTRAK', 404);

        $approval = $this->resolveTargetApproval($request, $tagihan);
        if (! $approval) {
            return back()->withErrors(['error' => 'Anda tidak memiliki approval yang pending pada tagihan ini.']);
        }

        try {
            $svc->reject($approval, Auth::user(), $request->input('catatan'), $request->ip());
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('verifikasi-tagihan-kontrak.index')
            ->with('success', 'Tagihan ditolak.');
    }

    /**
     * Resolve target approval: jika approval_id dikirim dari form (dual-role), gunakan itu.
     * Validasi bahwa approval tersebut memang milik user yang login dan masih PENDING.
     * Fallback ke resolusi otomatis jika approval_id tidak ada.
     */
    private function resolveTargetApproval(Request $request, Tagihan $tagihan): ?WorkflowApproval
    {
        $user = Auth::user();

        if ($request->filled('approval_id')) {
            $roleCodes = $this->roleCodesFromRoleNames($user->getRoleNames()->toArray());
            $approval = WorkflowApproval::where('id', $request->input('approval_id'))
                ->where('status', 'PENDING')
                ->whereIn('role_code', $roleCodes)
                ->whereHas('instance', fn($q) => $q->where('workflowable_id', $tagihan->id)->where('workflowable_type', \App\Models\Tagihan::class))
                ->first();

            return $approval;
        }

        return $this->resolvePendingApprovalForUser($tagihan, $user);
    }

    /** Helper: cari approval PENDING untuk user pada step aktif (kembalikan pertama saja). */
    private function resolvePendingApprovalForUser(Tagihan $tagihan, $user): ?WorkflowApproval
    {
        return $this->resolvePendingApprovalsForUser($tagihan, $user)->first();
    }

    /** Helper: cari SEMUA approval PENDING untuk user pada step aktif (untuk dual-role). */
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

    /** Mapping nama Spatie role → role_code yang dipakai workflow. */
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
            // Juga izinkan match langsung (kalau role_code dipakai sebagai nama di tempat lain)
            $codes[] = $n;
        }
        return array_values(array_unique($codes));
    }
}
