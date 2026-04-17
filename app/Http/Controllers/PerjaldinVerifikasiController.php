<?php

namespace App\Http\Controllers;

use App\Models\Tagihan;
use App\Models\WorkflowApproval;
use App\Notifications\WorkflowNotification;
use App\Services\PerjaldinWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class PerjaldinVerifikasiController extends Controller
{
    private const ROLE_CONFIG = [
        'ppk' => [
            'role_code' => 'PPK',
            'label' => 'PPK',
            'detail_route' => 'verifikasi-ppk.perjaldin.show',
            'index_route' => 'verifikasi-ppk.perjaldin.index',
            'approve_route' => 'verifikasi-ppk.perjaldin.approve',
            'revisi_route' => 'verifikasi-ppk.perjaldin.revisi',
        ],
        'ppspm' => [
            'role_code' => 'PPSPM',
            'label' => 'PPSPM',
            'detail_route' => 'verifikasi-ppspm.perjaldin.show',
            'index_route' => 'verifikasi-ppspm.perjaldin.index',
            'approve_route' => 'verifikasi-ppspm.perjaldin.approve',
            'revisi_route' => 'verifikasi-ppspm.perjaldin.revisi',
        ],
        'bendahara_penerimaan' => [
            'role_code' => 'BENDAHARA_PENERIMAAN',
            'label' => 'Bendahara Penerimaan',
            'detail_route' => 'verifikasi-bendahara-penerimaan.perjaldin.show',
            'index_route' => 'verifikasi-bendahara-penerimaan.perjaldin.index',
            'approve_route' => 'verifikasi-bendahara-penerimaan.perjaldin.approve',
            'revisi_route' => 'verifikasi-bendahara-penerimaan.perjaldin.revisi',
        ],
        'bendahara_pengeluaran' => [
            'role_code' => 'BENDAHARA_PENGELUARAN',
            'label' => 'Bendahara Pengeluaran',
            'detail_route' => 'verifikasi-bendahara.perjaldin.show',
            'index_route' => 'verifikasi-bendahara.perjaldin.index',
            'approve_route' => 'verifikasi-bendahara.perjaldin.approve',
            'revisi_route' => 'verifikasi-bendahara.perjaldin.revisi',
        ],
        'kasubbag' => [
            'role_code' => 'KASUBBAG',
            'label' => 'Kasubbag',
            'detail_route' => 'verifikasi-kasubag.perjaldin.show',
            'index_route' => 'verifikasi-kasubag.index',
            'approve_route' => 'verifikasi-kasubag.perjaldin.approve',
            'revisi_route' => 'verifikasi-kasubag.perjaldin.revisi',
        ],
    ];

    public function ppkIndex()
    {
        return $this->buildWorkflowIndex('ppk');
    }

    public function ppkShow(int $id)
    {
        return $this->buildWorkflowShow($id, 'ppk');
    }

    public function approve(Request $request, int $id, PerjaldinWorkflowService $workflowService)
    {
        return $this->approveForRole($request, $id, 'ppk', $workflowService);
    }

    public function revisi(Request $request, int $id, PerjaldinWorkflowService $workflowService)
    {
        return $this->revisionForRole($request, $id, 'ppk', $workflowService);
    }

    public function ppspmIndex()
    {
        return $this->buildWorkflowIndex('ppspm');
    }

    public function ppspmShow(int $id)
    {
        return $this->buildWorkflowShow($id, 'ppspm');
    }

    public function ppspmApprove(Request $request, int $id, PerjaldinWorkflowService $workflowService)
    {
        return $this->approveForRole($request, $id, 'ppspm', $workflowService);
    }

    public function ppspmRevisi(Request $request, int $id, PerjaldinWorkflowService $workflowService)
    {
        return $this->revisionForRole($request, $id, 'ppspm', $workflowService);
    }

    public function bendaharaPenerimaanIndex()
    {
        return $this->buildWorkflowIndex('bendahara_penerimaan');
    }

    public function bendaharaPenerimaanShow(int $id)
    {
        return $this->buildWorkflowShow($id, 'bendahara_penerimaan');
    }

    public function bendaharaPenerimaanApprove(Request $request, int $id, PerjaldinWorkflowService $workflowService)
    {
        return $this->approveForRole($request, $id, 'bendahara_penerimaan', $workflowService);
    }

    public function bendaharaPenerimaanRevisi(Request $request, int $id, PerjaldinWorkflowService $workflowService)
    {
        return $this->revisionForRole($request, $id, 'bendahara_penerimaan', $workflowService);
    }

    public function bendaharaIndex()
    {
        return $this->buildWorkflowIndex('bendahara_pengeluaran');
    }

    public function bendaharaShow(int $id)
    {
        return $this->buildWorkflowShow($id, 'bendahara_pengeluaran');
    }

    public function bendaharaApprove(Request $request, int $id, PerjaldinWorkflowService $workflowService)
    {
        return $this->approveForRole($request, $id, 'bendahara_pengeluaran', $workflowService);
    }

    public function bendaharaRevisi(Request $request, int $id, PerjaldinWorkflowService $workflowService)
    {
        return $this->revisionForRole($request, $id, 'bendahara_pengeluaran', $workflowService);
    }

    public function kasubagIndex()
    {
        return $this->buildWorkflowIndex('kasubbag');
    }

    public function kasubagShow(int $id)
    {
        return $this->buildWorkflowShow($id, 'kasubbag');
    }

    public function kasubagApprove(Request $request, int $id, PerjaldinWorkflowService $workflowService)
    {
        return $this->approveForRole($request, $id, 'kasubbag', $workflowService);
    }

    public function kasubagRevisi(Request $request, int $id, PerjaldinWorkflowService $workflowService)
    {
        return $this->revisionForRole($request, $id, 'kasubbag', $workflowService);
    }

    private function buildWorkflowIndex(string $roleKey)
    {
        $config = self::ROLE_CONFIG[$roleKey];
        $roleCodes = $this->roleCodeVariants($config['role_code']);
        $search = request('search');
        $periode = request('periode');

        $query = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->whereHas('workflowInstances.approvals', fn ($q) => $q->whereIn('role_code', $roleCodes))
            ->with([
                'detailPerjaldin.pegawai',
                'logs' => fn ($q) => $q->latest(),
                'workflowInstances' => fn ($q) => $q->latest(),
                'workflowInstances.approvals',
            ])
            ->latest();

        if ($search) {
            $query->where(fn ($q) => $q
                ->where('nomor_tagihan', 'like', "%{$search}%")
                ->orWhere('deskripsi', 'like', "%{$search}%")
            );
        }

        if ($periode) {
            $parts = explode('-', $periode);
            $year = $parts[0] ?? null;
            $month = isset($parts[1]) ? (int) $parts[1] : null;

            if ($year && $month) {
                $query->where('periode_tahun', $year)->where('periode_bulan', $month);
            }
        }

        $tagihans = $query->get();
        $tagihansPerlu = $tagihans->filter(
            fn (Tagihan $tagihan) => $this->pendingApproval($tagihan, $config['role_code']) !== null
        )->values();
        $tagihansRiwayat = $tagihans->filter(
            fn (Tagihan $tagihan) => $this->roleHasActed($tagihan, $config['role_code'])
        )->values();
        $visibleTagihans = $tagihansPerlu->merge($tagihansRiwayat)->unique('id')->values();

        $pendingStatuses = $tagihansPerlu->pluck('status')->unique()->values()->all();
        $revisiStatuses = $visibleTagihans->pluck('status')->filter(fn ($status) => str_starts_with((string) $status, 'REVISI_'))->unique()->values()->all();
        $selesaiStatuses = ['DISETUJUI_PERJALDIN'];

        return view('verifikasi_perjaldin.index', [
            'tagihans' => $visibleTagihans,
            'tagihansPerlu' => $tagihansPerlu,
            'tagihansRiwayat' => $tagihansRiwayat,
            'pendingStatuses' => $pendingStatuses,
            'revisiStatuses' => $revisiStatuses,
            'selesaiStatuses' => $selesaiStatuses,
            'userRole' => $config['label'],
            'detailRoute' => $config['detail_route'],
        ]);
    }

    private function buildWorkflowShow(int $id, string $roleKey)
    {
        $config = self::ROLE_CONFIG[$roleKey];

        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->with([
                'detailPerjaldin.pegawai',
                'detailPerjaldin.provinsi',
                'logs.user',
                'workflowInstances' => fn ($q) => $q->latest(),
                'workflowInstances.approvals.actedByUser',
                'workflowInstances.approvals.assignedUser',
            ])
            ->findOrFail($id);

        $currentApproval = $this->pendingApproval($tagihan, $config['role_code']);

        return view('verifikasi_perjaldin.show', [
            'tagihan' => $tagihan,
            'userRole' => $config['label'],
            'roleCode' => $config['role_code'],
            'currentApproval' => $currentApproval,
            'approveRoute' => route($config['approve_route'], $id),
            'revisiRoute' => route($config['revisi_route'], $id),
            'indexRoute' => $config['index_route'],
        ]);
    }

    private function approveForRole(Request $request, int $id, string $roleKey, PerjaldinWorkflowService $workflowService)
    {
        $request->validate(['catatan' => 'nullable|string|max:1000']);
        $config = self::ROLE_CONFIG[$roleKey];
        $tagihan = $this->findTagihanWithWorkflow($id);
        $approval = $this->pendingApproval($tagihan, $config['role_code']);

        if (!$approval) {
            return redirect()->back()->withErrors(['error' => 'Dokumen tidak sedang menunggu tindakan ' . $config['label'] . '.']);
        }

        try {
            $instance = $workflowService->approve($approval, $request->user(), $request->catatan, $request->ip());
            $tagihan->refresh();

            $this->notifyNextActors($tagihan, $instance, $config['label']);

            return redirect()
                ->route($config['index_route'])
                ->with('success', $tagihan->status === 'DISETUJUI_PERJALDIN'
                    ? 'Dokumen Perjaldin disetujui final oleh Kasubbag dan siap diproses Operator BLU.'
                    : 'Verifikasi ' . $config['label'] . ' berhasil disimpan.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    private function revisionForRole(Request $request, int $id, string $roleKey, PerjaldinWorkflowService $workflowService)
    {
        $request->validate(['catatan_revisi' => 'required|string|min:3|max:1000']);
        $config = self::ROLE_CONFIG[$roleKey];
        $tagihan = $this->findTagihanWithWorkflow($id);
        $approval = $this->pendingApproval($tagihan, $config['role_code']);

        if (!$approval) {
            return redirect()->back()->withErrors(['error' => 'Dokumen tidak sedang menunggu tindakan ' . $config['label'] . '.']);
        }

        try {
            $workflowService->requestRevision($approval, $request->user(), $request->catatan_revisi, $request->ip());
            $this->notifyOperatorPerjaldinRevision($tagihan, $config['label'], $request->catatan_revisi);

            return redirect()
                ->route($config['index_route'])
                ->with('success', 'Dokumen dikembalikan untuk revisi oleh ' . $config['label'] . '.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    private function findTagihanWithWorkflow(int $id): Tagihan
    {
        return Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->with([
                'workflowInstances' => fn ($q) => $q->latest(),
                'workflowInstances.approvals',
            ])
            ->findOrFail($id);
    }

    private function pendingApproval(Tagihan $tagihan, string $roleCode): ?WorkflowApproval
    {
        $instance = $tagihan->workflowInstances->first()
            ?? $tagihan->workflowInstance;

        if (!$instance || $instance->status !== 'IN_PROGRESS') {
            return null;
        }

        $roleCodes = $this->roleCodeVariants($roleCode);
        $currentUserId = auth()->id();

        return $instance->approvals
            ->first(fn ($approval) => (int) $approval->urutan_step === (int) $instance->step_saat_ini
                && $approval->status === 'PENDING'
                && in_array($approval->role_code, $roleCodes, true)
                && ($approval->assigned_user_id === null || (int) $approval->assigned_user_id === (int) $currentUserId));
    }

    private function roleHasActed(Tagihan $tagihan, string $roleCode): bool
    {
        $roleCodes = $this->roleCodeVariants($roleCode);
        $currentUserId = auth()->id();

        return $tagihan->workflowInstances
            ->flatMap(fn ($instance) => $instance->approvals)
            ->contains(fn ($approval) => in_array($approval->role_code, $roleCodes, true)
                && in_array($approval->status, ['APPROVED', 'REVISION', 'REJECTED'], true)
                && ($approval->assigned_user_id === null
                    || (int) $approval->assigned_user_id === (int) $currentUserId
                    || (int) $approval->acted_by_user_id === (int) $currentUserId));
    }

    private function roleCodeVariants(string $roleCode): array
    {
        return match ($roleCode) {
            'BENDAHARA_PENERIMAAN' => ['BENDAHARA_PENERIMAAN', 'Bendahara Penerimaan'],
            'BENDAHARA_PENGELUARAN' => ['BENDAHARA_PENGELUARAN', 'Bendahara Pengeluaran'],
            'KASUBBAG' => ['KASUBBAG', 'Kepala Subbagian Keuangan dan Tata Usaha'],
            default => [$roleCode],
        };
    }

    private function notifyNextActors(Tagihan $tagihan, $instance, string $actorRole): void
    {
        try {
            $pendingApprovals = $instance->fresh(['approvals'])->approvals
                ->where('urutan_step', $instance->step_saat_ini)
                ->where('status', 'PENDING');

            foreach ($pendingApprovals as $approval) {
                $roleName = $this->roleNameFromCode($approval->role_code);
                $users = $approval->assigned_user_id
                    ? \App\Models\User::whereKey($approval->assigned_user_id)->get()
                    : \App\Models\User::role($roleName)->get();

                Notification::send($users, new WorkflowNotification([
                    'title' => 'Verifikasi Perjaldin',
                    'message' => "Perjaldin '{$tagihan->deskripsi}' menunggu tindakan {$roleName}.",
                    'url' => $this->routeForRoleCode($approval->role_code),
                    'icon' => 'assignment',
                    'color' => 'primary',
                ]));
            }

            if ($tagihan->status === 'DISETUJUI_PERJALDIN') {
                $operators = \App\Models\User::role('Operator BLU')->get();
                Notification::send($operators, new WorkflowNotification([
                    'title' => 'Perjaldin Siap SPP',
                    'message' => "Perjaldin '{$tagihan->deskripsi}' sudah disetujui {$actorRole}. Silakan pilih COA dan buat SPP.",
                    'url' => route('spps.perjaldin.index'),
                    'icon' => 'receipt_long',
                    'color' => 'success',
                ]));
            }
        } catch (\Exception $e) {
            // Notifikasi tidak boleh menggagalkan transaksi verifikasi.
        }
    }

    private function notifyOperatorPerjaldinRevision(Tagihan $tagihan, string $roleLabel, string $catatan): void
    {
        try {
            $operators = \App\Models\User::role('Operator Perjaldin')->get();
            Notification::send($operators, new WorkflowNotification([
                'title' => 'Revisi Perjaldin dari ' . $roleLabel,
                'message' => "Perjaldin '{$tagihan->deskripsi}' dikembalikan. Catatan: {$catatan}",
                'url' => route('perjaldins.index'),
                'icon' => 'error',
                'color' => 'danger',
            ]));
        } catch (\Exception $e) {
            // Notifikasi tidak boleh menggagalkan aksi revisi.
        }
    }

    private function roleNameFromCode(string $roleCode): string
    {
        return match ($roleCode) {
            'BENDAHARA_PENERIMAAN' => 'Bendahara Penerimaan',
            'BENDAHARA_PENGELUARAN' => 'Bendahara Pengeluaran',
            'KASUBBAG' => 'Kepala Subbagian Keuangan dan Tata Usaha',
            default => $roleCode,
        };
    }

    private function routeForRoleCode(string $roleCode): string
    {
        return match ($roleCode) {
            'PPSPM' => route('verifikasi-ppspm.perjaldin.index'),
            'BENDAHARA_PENERIMAAN', 'Bendahara Penerimaan' => route('verifikasi-bendahara-penerimaan.perjaldin.index'),
            'BENDAHARA_PENGELUARAN', 'Bendahara Pengeluaran' => route('verifikasi-bendahara.perjaldin.index'),
            'KASUBBAG', 'Kepala Subbagian Keuangan dan Tata Usaha' => route('verifikasi-kasubag.index'),
            default => route('verifikasi-ppk.perjaldin.index'),
        };
    }
}
