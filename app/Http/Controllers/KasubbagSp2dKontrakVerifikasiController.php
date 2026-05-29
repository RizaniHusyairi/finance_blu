<?php

namespace App\Http\Controllers;

use App\Models\DokumenSp2d;
use App\Models\LogStatusDokumen;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\WorkflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class KasubbagSp2dKontrakVerifikasiController extends Controller
{
    private function activeRoleCodes(?User $user = null): array
    {
        $user ??= auth()->user();

        return collect([
            'PPSPM',
            'PPK',
            'Kepala Subbagian Keuangan dan Tata Usaha',
            'Koordinator Keuangan',
        ])->filter(fn ($roleCode) => $user?->hasRole($roleCode))->values()->all();
    }

    private function roleLabel(array $roleCodes): string
    {
        return implode(' / ', $roleCodes);
    }

    private function approvalAccessibleToUser($approval, array $roleCodes, User $user): bool
    {
        return $approval
            && in_array($approval->role_code, $roleCodes, true)
            && (! $approval->assigned_user_id || (int) $approval->assigned_user_id === (int) $user->id);
    }

    private function authorizedApprovals($approvals, array $roleCodes, User $user)
    {
        return collect($approvals)
            ->filter(fn ($approval) => $this->approvalAccessibleToUser($approval, $roleCodes, $user))
            ->values();
    }

    private function actionableApprovals($instance, array $roleCodes, User $user)
    {
        return $this->authorizedApprovals($instance?->approvals ?? collect(), $roleCodes, $user)
            ->filter(fn ($approval) => $approval->status === 'PENDING'
                && $instance?->status === 'IN_PROGRESS'
                && (int) $instance?->step_saat_ini === (int) $approval->urutan_step)
            ->values();
    }

    private function resolveApprovalForAction($instance, array $roleCodes, User $user, $approvalId = null)
    {
        $actionableApprovals = $this->actionableApprovals($instance, $roleCodes, $user);

        if ($approvalId) {
            return $actionableApprovals->firstWhere('id', (int) $approvalId);
        }

        return $actionableApprovals->count() === 1 ? $actionableApprovals->first() : null;
    }

    /**
     * Halaman antrean verifikasi SP2D Kontrak untuk Kasubbag.
     */
    public function index(Request $request)
    {
        $sp2dQuery = DokumenSp2d::with([
            'npi.spm.spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor',
            'bendaharaPengeluaran',
            'workflowInstances.approvals.assignedUser',
            'workflowInstances.approvals.actedByUser',
        ])
            ->whereHas('npi.spm.spp.tagihan', fn ($q) => $q->where('tipe_tagihan', 'KONTRAK'))
            ->whereHas('workflowInstances', fn ($q) => $q->whereIn('status', ['IN_PROGRESS', 'APPROVED', 'REVISION']))
            ->latest()
            ->get();

        $sp2dList = $sp2dQuery->map(function ($sp2d) {
            $latestInstance = $sp2d->workflowInstances->sortByDesc('created_at')->first();
            $approvals = collect($latestInstance?->approvals ?? []);

            $sp2d->_workflowInstance = $latestInstance;
            $sp2d->_ppkApproval = $approvals->firstWhere('role_code', 'PPK');
            $sp2d->_kasubbagApproval = $approvals->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');
            $sp2d->_koordinatorApproval = $approvals->firstWhere('role_code', 'Koordinator Keuangan');

            $allApproved = $approvals->every(fn ($a) => $a->status === 'APPROVED') && $approvals->isNotEmpty();
            $anyRevision = $approvals->contains(fn ($a) => in_array($a->status, ['REVISION', 'REJECTED']));

            if ($allApproved) {
                $sp2d->_statusFinal = 'Selesai Diverifikasi';
            } elseif ($anyRevision) {
                $sp2d->_statusFinal = 'Perlu Revisi';
            } else {
                $pending = $approvals->where('status', 'PENDING');
                if ($pending->count() === $approvals->count()) {
                    $sp2d->_statusFinal = 'Menunggu Verifikasi';
                } else {
                    $pendingRoles = $pending->pluck('role_code')->map(fn ($role) => match ($role) {
                        'PPK' => 'PPK',
                        'Kepala Subbagian Keuangan dan Tata Usaha' => 'Kasubbag',
                        default => $role,
                    });
                    $sp2d->_statusFinal = 'Menunggu '.$pendingRoles->join(' & ');
                }
            }

            return $sp2d;
        });

        // Filtering
        $filterPpk = $request->input('status_ppk', 'semua');
        $filterKasubbag = $request->input('status_kasubbag', 'semua');
        $search = $request->input('search');

        $filtered = $sp2dList;

        if ($filterPpk !== 'semua') {
            $filtered = $filtered->filter(fn ($sp2d) => $sp2d->_ppkApproval?->status === strtoupper($filterPpk));
        }
        if ($filterKasubbag !== 'semua') {
            $filtered = $filtered->filter(fn ($sp2d) => $sp2d->_kasubbagApproval?->status === strtoupper($filterKasubbag));
        }

        if ($search) {
            $s = strtolower($search);
            $filtered = $filtered->filter(function ($sp2d) use ($s) {
                $npi = $sp2d->npi;
                $spm = $npi?->spm;
                $spp = $spm?->spp;
                $tagihan = $spp?->tagihan;
                $kontrak = $tagihan?->detailKontrak?->kontrakTermin?->kontrak;
                $vendor = $kontrak?->vendor;

                return str_contains(strtolower($sp2d->nomor_sp2d ?? ''), $s)
                    || str_contains(strtolower($npi?->nomor_npi ?? ''), $s)
                    || str_contains(strtolower($spm?->nomor_spm ?? ''), $s)
                    || str_contains(strtolower($spp?->nomor_spp ?? ''), $s)
                    || str_contains(strtolower($tagihan?->nomor_tagihan ?? ''), $s)
                    || str_contains(strtolower($kontrak?->nomor_spk ?? ''), $s)
                    || str_contains(strtolower($kontrak?->nama_pekerjaan ?? ''), $s)
                    || str_contains(strtolower($vendor?->nama_pihak ?? ''), $s);
            });
        }

        $summary = [
            'pending' => $sp2dList->filter(fn ($n) => $n->_kasubbagApproval?->status === 'PENDING')->count(),
            'approved' => $sp2dList->filter(fn ($n) => $n->_kasubbagApproval?->status === 'APPROVED')->count(),
            'revision' => $sp2dList->filter(fn ($n) => in_array($n->_kasubbagApproval?->status, ['REVISION', 'REJECTED'])
                || $n->_workflowInstance?->status === 'REVISION')->count(),
            'selesai' => $sp2dList->filter(fn ($n) => $n->_statusFinal === 'Selesai Diverifikasi')->count(),
        ];

        return view('verifikasi_sp2d.kontrak_index', [
            'sp2dList' => $filtered->values(),
            'summary' => $summary,
            'filterPpk' => $filterPpk,
            'filterKasubbag' => $filterKasubbag,
            'search' => $search,
            'currentRole' => 'Kepala Subbagian Keuangan dan Tata Usaha',
            'routePrefix' => 'verifikasi-kasubag.sp2d.kontrak',
        ]);
    }

    /**
     * Halaman detail verifikasi SP2D Kontrak untuk Kasubbag.
     */
    public function show($sp2d_id)
    {
        $user = request()->user();
        $roleCodes = $this->activeRoleCodes($user);
        abort_unless(count($roleCodes) > 0, 403, 'Akses ditolak.');

        $sp2d = DokumenSp2d::with([
            'npi.spm.spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor.rekening',
            'npi.spm.spp.tagihan.potonganTagihan.pajak',
            'arsipDokumen',
            'bendaharaPengeluaran',
            'workflowInstances.approvals.assignedUser',
            'workflowInstances.approvals.actedByUser',
            'logs.user',
        ])->findOrFail($sp2d_id);

        $npi = $sp2d->npi;
        $spm = $npi?->spm;
        $spp = $spm?->spp;
        $tagihan = $spp?->tagihan;
        $detailKontrak = $tagihan?->detailKontrak;
        $termin = $detailKontrak?->kontrakTermin;
        $kontrak = $termin?->kontrak;
        $vendor = $kontrak?->vendor;
        $rekening = $vendor?->rekening?->first();

        $nominalSp2d = (float) ($spp?->nominal_spp ?? $tagihan?->total_netto ?? 0);

        // Workflow
        $activeWorkflowInstance = $sp2d->workflowInstances->sortByDesc('created_at')->first();
        $approvals = collect($activeWorkflowInstance?->approvals ?? []);

        $ppspmApproval = $approvals->firstWhere('role_code', 'PPSPM');
        $ppkApproval = $approvals->firstWhere('role_code', 'PPK');
        $kasubbagApproval = $approvals->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');
        $koordinatorApproval = $approvals->firstWhere('role_code', 'Koordinator Keuangan');
        $activeRoleApprovals = $this->authorizedApprovals($approvals, $roleCodes, $user);
        $actionableApprovals = $this->actionableApprovals($activeWorkflowInstance, $roleCodes, $user);
        $currentUserApproval = $actionableApprovals->first() ?: $activeRoleApprovals->firstWhere('status', 'PENDING') ?: $activeRoleApprovals->first();

        $canApprove = $actionableApprovals->isNotEmpty();
        $canRequestRevision = $canApprove;

        $allApproved = $approvals->every(fn ($a) => $a->status === 'APPROVED') && $approvals->isNotEmpty();
        $anyRevision = $approvals->contains(fn ($a) => in_array($a->status, ['REVISION', 'REJECTED']));

        $statusFinal = $allApproved ? 'Selesai Diverifikasi' : ($anyRevision ? 'Perlu Revisi' : 'Menunggu Verifikasi');

        // Catatan revisi
        $revisionNotes = $approvals->filter(fn ($a) => filled($a->catatan) && in_array($a->status, ['REVISION', 'REJECTED']))
            ->map(fn ($a) => [
                'role' => $a->role_code,
                'catatan' => $a->catatan,
                'user' => $a->actedByUser?->name ?? '-',
                'time' => $a->acted_at ? Carbon::parse($a->acted_at)->format('d M Y H:i') : '-',
            ])->values();

        // Dokumen pendukung
        $isPelunasan = ($termin?->jenis_termin ?? null) === 'PELUNASAN';
        $potonganPajak = collect($tagihan?->potonganTagihan ?? [])->filter(fn ($item) => $item->jenis_potongan !== 'ANGSURAN_UANG_MUKA');
        $requiresTaxDocuments = $potonganPajak->isNotEmpty();
        $buktiTransferSp2d = $sp2d->arsipDokumen?->firstWhere('jenis_dokumen', 'BUKTI_TRANSFER_SP2D');

        $documentStatuses = collect([
            ['key' => 'npi', 'label' => 'NPI', 'path' => true, 'required' => true],
            ['key' => 'spm', 'label' => 'SPM', 'path' => true, 'required' => true],
            ['key' => 'spp', 'label' => 'SPP', 'path' => true, 'required' => true],
            ['key' => 'bapp', 'label' => 'BAPP', 'path' => $detailKontrak?->file_bapp, 'required' => true],
            ['key' => 'bast', 'label' => 'BAST', 'path' => $detailKontrak?->file_bast, 'required' => $isPelunasan],
            ['key' => 'bap', 'label' => 'BAP', 'path' => $detailKontrak?->file_bap, 'required' => true],
            ['key' => 'faktur_pajak', 'label' => 'Faktur Pajak', 'path' => $detailKontrak?->file_faktur_pajak, 'required' => $requiresTaxDocuments],
            ['key' => 'bukti_transfer', 'label' => 'Bukti Transfer SP2D', 'path' => $buktiTransferSp2d?->path_file, 'arsip_id' => $buktiTransferSp2d?->id, 'required' => false],
        ])->map(function ($item) {
            $isAvailable = ! empty($item['path']);
            $status = ! $item['required'] ? 'not_required' : ($isAvailable ? 'ready' : 'missing');

            return array_merge($item, ['status' => $status, 'is_available' => $isAvailable]);
        })->values();

        return view('verifikasi_sp2d.kontrak_detail', [
            'sp2d' => $sp2d,
            'npi' => $npi,
            'spm' => $spm,
            'spp' => $spp,
            'tagihan' => $tagihan,
            'detailKontrak' => $detailKontrak,
            'termin' => $termin,
            'kontrak' => $kontrak,
            'vendor' => $vendor,
            'rekening' => $rekening,
            'nominalSp2d' => $nominalSp2d,
            'activeWorkflowInstance' => $activeWorkflowInstance,
            'ppspmApproval' => $ppspmApproval,
            'ppkApproval' => $ppkApproval,
            'kasubbagApproval' => $kasubbagApproval,
            'koordinatorApproval' => $koordinatorApproval,
            'currentUserApproval' => $currentUserApproval,
            'activeRoleApprovals' => $activeRoleApprovals,
            'actionableApprovals' => $actionableApprovals,
            'canApprove' => $canApprove,
            'canRequestRevision' => $canRequestRevision,
            'statusFinal' => $statusFinal,
            'revisionNotes' => $revisionNotes,
            'documentStatuses' => $documentStatuses,
            'currentRole' => $this->roleLabel($roleCodes),
            'routePrefix' => 'verifikasi-kasubag.sp2d.kontrak',
        ]);
    }

    /**
     * Approve SP2D oleh Kasubbag.
     */
    public function approve(Request $request, $sp2d_id)
    {
        $request->validate([
            'approval_id' => 'nullable|integer',
            'catatan' => 'nullable|string',
        ]);

        $user = $request->user();
        $roleCodes = $this->activeRoleCodes($user);
        abort_unless(count($roleCodes) > 0, 403, 'Akses ditolak.');

        $sp2d = DokumenSp2d::with([
            'workflowInstances' => fn ($q) => $q->latest()->limit(1),
            'workflowInstances.approvals',
        ])->findOrFail($sp2d_id);
        $instance = $sp2d->workflowInstances->first();
        $myApproval = $this->resolveApprovalForAction($instance, $roleCodes, $user, $request->input('approval_id'));
        if (! $myApproval) {
            return back()->with('error', 'Anda tidak memiliki approval aktif untuk tindakan ini.');
        }

        DB::transaction(function () use ($sp2d, $request, $myApproval) {
            $workflowService = app(WorkflowService::class);
            $instance = $workflowService->approveCurrentStep($sp2d, auth()->id(), $request->input('catatan'), $myApproval->id);

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSp2d::class,
                'dokumen_id' => $sp2d->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => $myApproval->role_code,
                'status_sebelumnya' => $sp2d->status,
                'status_baru' => $instance->status === 'APPROVED' ? DokumenSp2d::STATUS_DISETUJUI_FINAL : $sp2d->status,
                'aksi' => 'APPROVE_'.str($myApproval->role_code)->upper()->replace(' ', '_').'_SP2D',
                'catatan' => $request->input('catatan', 'SP2D disetujui '.$myApproval->role_code.'.'),
                'ip_address' => request()->ip(),
            ]);

            if ($instance->status === 'APPROVED') {
                $sp2d->update(['status' => DokumenSp2d::STATUS_DISETUJUI_FINAL]);
                $sp2d->unlockNextTerminKontrak();
            }
        });

        return redirect()->route('verifikasi-kasubag.sp2d.kontrak.show', $sp2d_id)
            ->with('success', 'SP2D berhasil disetujui.');
    }

    /**
     * Minta revisi SP2D oleh Kasubbag.
     */
    public function revisi(Request $request, $sp2d_id)
    {
        $request->validate([
            'approval_id' => 'nullable|integer',
            'catatan_revisi' => 'required|string|max:1000',
        ]);

        $user = $request->user();
        $roleCodes = $this->activeRoleCodes($user);
        abort_unless(count($roleCodes) > 0, 403, 'Akses ditolak.');

        $sp2d = DokumenSp2d::with([
            'workflowInstances' => fn ($q) => $q->latest()->limit(1),
            'workflowInstances.approvals',
        ])->findOrFail($sp2d_id);
        $instance = $sp2d->workflowInstances->first();
        $myApproval = $this->resolveApprovalForAction($instance, $roleCodes, $user, $request->input('approval_id'));
        if (! $myApproval) {
            return back()->with('error', 'Anda tidak memiliki approval aktif untuk tindakan ini.');
        }

        DB::transaction(function () use ($sp2d, $request, $myApproval) {
            $workflowService = app(WorkflowService::class);
            $workflowService->requestRevision($sp2d, auth()->id(), $request->catatan_revisi, $myApproval->id);

            $sp2d->update(['status' => DokumenSp2d::STATUS_REVISI]);

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSp2d::class,
                'dokumen_id' => $sp2d->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => $myApproval->role_code,
                'status_sebelumnya' => $sp2d->status,
                'status_baru' => DokumenSp2d::STATUS_REVISI,
                'aksi' => 'REVISI_'.str($myApproval->role_code)->upper()->replace(' ', '_').'_SP2D',
                'catatan' => $request->catatan_revisi,
                'ip_address' => request()->ip(),
            ]);

            $benPenUsers = User::role('Bendahara Pengeluaran')->get();
            if ($benPenUsers->isNotEmpty()) {
                Notification::send($benPenUsers, new WorkflowNotification([
                    'title' => 'SP2D Kontrak Dikembalikan',
                    'message' => "SP2D {$sp2d->nomor_sp2d} dikembalikan oleh {$myApproval->role_code}: {$request->catatan_revisi}",
                    'url' => route('sp2ds.kontrak.index'),
                    'icon' => 'reply',
                    'color' => 'warning',
                ]));
            }
        });

        return redirect()->route('verifikasi-kasubag.sp2d.kontrak.show', $sp2d_id)
            ->with('success', 'SP2D dikembalikan untuk revisi.');
    }
}
