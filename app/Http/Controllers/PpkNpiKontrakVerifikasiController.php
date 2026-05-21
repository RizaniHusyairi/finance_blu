<?php

namespace App\Http\Controllers;

use App\Models\DokumenNpi;
use App\Models\LogStatusDokumen;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class PpkNpiKontrakVerifikasiController extends Controller
{
    private function activeRoleCodes(): array
    {
        $roles = [];
        if (\Illuminate\Support\Facades\Auth::user()->hasRole('PPK')) {
            $roles[] = 'PPK';
        }
        if (\Illuminate\Support\Facades\Auth::user()->hasRole('Koordinator Keuangan')) {
            $roles[] = 'Koordinator Keuangan';
        }
        return $roles;
    }

    private function routePrefix(): string
    {
        return request()->routeIs('verifikasi-koordinator.*')
            ? 'verifikasi-koordinator.npi.kontrak'
            : 'verifikasi-ppk.npi.kontrak';
    }

    private function currentRoleCode(array $roleCodes): string
    {
        $preferredRole = request()->routeIs('verifikasi-koordinator.*')
            ? 'Koordinator Keuangan'
            : 'PPK';

        return in_array($preferredRole, $roleCodes, true)
            ? $preferredRole
            : ($roleCodes[0] ?? '');
    }

    /**
     * Halaman antrean verifikasi NPI Kontrak untuk PPK.
     */
    public function index(Request $request)
    {
        $roleCodes = $this->activeRoleCodes();
        if (empty($roleCodes)) abort(403, 'Akses ditolak.');
        $currentRole = $this->currentRoleCode($roleCodes);
        $routePrefix = $this->routePrefix();
        $userId = $request->user()?->id;

        $npiQuery = DokumenNpi::with([
            'spm.spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor',
            'bendaharaPenerimaan',
            'workflowInstances.approvals.assignedUser',
            'workflowInstances.approvals.actedByUser',
        ])
        ->whereHas('spm.spp.tagihan', fn ($q) => $q->where('tipe_tagihan', 'KONTRAK'))
        ->whereHas('workflowInstances', fn ($q) => $q->whereIn('status', ['IN_PROGRESS', 'APPROVED', 'REVISION']))
        ->latest()
        ->get();

        $npiList = $npiQuery->map(function ($npi) use ($currentRole, $userId) {
            $latestInstance = $npi->workflowInstances->sortByDesc('created_at')->first();
            $approvals = collect($latestInstance?->approvals ?? []);

            $npi->_workflowInstance = $latestInstance;
            $npi->_benpenApproval = $approvals->firstWhere('role_code', 'Bendahara Penerimaan');
            $npi->_ppkApproval = $approvals->firstWhere('role_code', 'PPK');
            $npi->_kasubbagApproval = $approvals->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');
            $npi->_koordinatorApproval = $approvals->firstWhere('role_code', 'Koordinator Keuangan');
            $myApprovals = $approvals
                ->where('role_code', $currentRole)
                ->filter(fn ($approval) => !$approval->assigned_user_id || (int) $approval->assigned_user_id === (int) $userId);
            $npi->_currentApproval = $myApprovals->where('status', 'PENDING')->first() ?: $myApprovals->first();

            $allApproved = $approvals->every(fn ($a) => $a->status === 'APPROVED') && $approvals->isNotEmpty();
            $anyRevision = $approvals->contains(fn ($a) => in_array($a->status, ['REVISION', 'REJECTED']));

            if ($allApproved) {
                $npi->_statusFinal = 'Selesai Diverifikasi';
            } elseif ($anyRevision) {
                $npi->_statusFinal = 'Perlu Revisi';
            } else {
                $pending = $approvals->where('status', 'PENDING');
                if ($pending->count() === $approvals->count()) {
                    $npi->_statusFinal = 'Menunggu Verifikasi';
                } else {
                    $pendingRoles = $pending->pluck('role_code')->map(fn ($role) => match($role) {
                        'Bendahara Penerimaan' => 'BenPen',
                        'PPK' => 'PPK',
                        'Kepala Subbagian Keuangan dan Tata Usaha' => 'Kasubbag',
                        'Koordinator Keuangan' => 'Koordinator',
                        default => $role,
                    });
                    $npi->_statusFinal = 'Menunggu ' . $pendingRoles->join(', ');
                }
            }

            return $npi;
        })->filter(fn ($npi) => $npi->_currentApproval)->values();

        // Filtering
        $filterPpk = $request->input('status_ppk', 'semua');
        $filterBenpen = $request->input('status_benpen', 'semua');
        $filterKasubbag = $request->input('status_kasubbag', 'semua');
        $search = $request->input('search');

        $filtered = $npiList;

        if ($filterPpk !== 'semua') {
            $filtered = $filtered->filter(fn ($npi) => $npi->_currentApproval?->status === strtoupper($filterPpk));
        }
        if ($filterBenpen !== 'semua') {
            $filtered = $filtered->filter(fn ($npi) => $npi->_benpenApproval?->status === strtoupper($filterBenpen));
        }
        if ($filterKasubbag !== 'semua') {
            $filtered = $filtered->filter(fn ($npi) => $npi->_kasubbagApproval?->status === strtoupper($filterKasubbag));
        }

        if ($search) {
            $s = strtolower($search);
            $filtered = $filtered->filter(function ($npi) use ($s) {
                $spm = $npi->spm;
                $spp = $spm?->spp;
                $tagihan = $spp?->tagihan;
                $kontrak = $tagihan?->detailKontrak?->kontrakTermin?->kontrak;
                $vendor = $kontrak?->vendor;

                return str_contains(strtolower($npi->nomor_npi ?? ''), $s)
                    || str_contains(strtolower($spm?->nomor_spm ?? ''), $s)
                    || str_contains(strtolower($spp?->nomor_spp ?? ''), $s)
                    || str_contains(strtolower($tagihan?->nomor_tagihan ?? ''), $s)
                    || str_contains(strtolower($kontrak?->nomor_spk ?? ''), $s)
                    || str_contains(strtolower($kontrak?->nama_pekerjaan ?? ''), $s)
                    || str_contains(strtolower($vendor?->nama_pihak ?? ''), $s);
            });
        }

        $summary = [
            'pending' => $npiList->filter(fn ($n) => $n->_currentApproval?->status === 'PENDING')->count(),
            'approved' => $npiList->filter(fn ($n) => $n->_currentApproval?->status === 'APPROVED')->count(),
            'revision' => $npiList->filter(fn ($n) => in_array($n->_currentApproval?->status, ['REVISION', 'REJECTED'])
                || $n->_workflowInstance?->status === 'REVISION')->count(),
            'selesai' => $npiList->filter(fn ($n) => $n->_statusFinal === 'Selesai Diverifikasi')->count(),
        ];

        return view('verifikasi_ppk.npi_kontrak_index', [
            'npiList' => $filtered->values(),
            'summary' => $summary,
            'filterPpk' => $filterPpk,
            'filterBenpen' => $filterBenpen,
            'filterKasubbag' => $filterKasubbag,
            'search' => $search,

            'routePrefix' => $routePrefix,
        ]);
    }

    /**
     * Halaman detail verifikasi NPI Kontrak untuk PPK.
     */
    public function show($npi_id)
    {
        $roleCodes = $this->activeRoleCodes();
        if (empty($roleCodes)) abort(403, 'Akses ditolak.');
        $currentRole = $this->currentRoleCode($roleCodes);
        $routePrefix = $this->routePrefix();
        $userId = auth()->id();

        $npi = DokumenNpi::with([
            'spm.spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor.rekening',
            'spm.spp.tagihan.potonganTagihan.pajak',
            'spm.spp.ppkVerifikator',
            'spm.arsipDokumen',
            'bendaharaPenerimaan',
            'workflowInstances.approvals.assignedUser',
            'workflowInstances.approvals.actedByUser',
            'logs.user',
        ])->findOrFail($npi_id);

        $spm = $npi->spm;
        $spp = $spm?->spp;
        $tagihan = $spp?->tagihan;
        $detailKontrak = $tagihan?->detailKontrak;
        $termin = $detailKontrak?->kontrakTermin;
        $kontrak = $termin?->kontrak;
        $vendor = $kontrak?->vendor;
        $rekening = $vendor?->rekening?->first();

        $nominalNpi = (float) ($spp->nominal_spp ?? $tagihan->total_netto ?? 0);

        // Workflow
        $activeWorkflowInstance = $npi->workflowInstances->sortByDesc('created_at')->first();
        $approvals = collect($activeWorkflowInstance?->approvals ?? []);

        $benpenApproval = $approvals->firstWhere('role_code', 'Bendahara Penerimaan');
        $ppkApproval = $approvals->firstWhere('role_code', 'PPK');
        $kasubbagApproval = $approvals->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');
        $koordinatorApproval = $approvals->firstWhere('role_code', 'Koordinator Keuangan');
        $currentUserApproval = $approvals
            ->where('role_code', $currentRole)
            ->filter(fn ($approval) => !$approval->assigned_user_id || (int) $approval->assigned_user_id === (int) $userId)
            ->first();

        abort_unless($currentUserApproval, 403, 'NPI ini bukan tugas verifikasi Anda.');

        $canApprove = $currentUserApproval && $currentUserApproval->status === 'PENDING';
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
                'time' => $a->acted_at ? \Carbon\Carbon::parse($a->acted_at)->format('d M Y H:i') : '-',
            ])->values();

        // Dokumen pendukung
        $isPelunasan = ($termin?->jenis_termin ?? null) === 'PELUNASAN';
        $potonganPajak = collect($tagihan?->potonganTagihan ?? [])->filter(fn ($item) => $item->jenis_potongan !== 'ANGSURAN_UANG_MUKA');
        $requiresTaxDocuments = $potonganPajak->isNotEmpty();
        $documentStatuses = collect([
            ['key' => 'spm', 'label' => 'SPM', 'path' => true, 'required' => true],
            ['key' => 'spp', 'label' => 'SPP', 'path' => true, 'required' => true],
            ['key' => 'bapp', 'label' => 'BAPP', 'path' => $detailKontrak?->file_bapp, 'required' => true],
            ['key' => 'bast', 'label' => 'BAST', 'path' => $detailKontrak?->file_bast, 'required' => $isPelunasan],
            ['key' => 'bap', 'label' => 'BAP', 'path' => $detailKontrak?->file_bap, 'required' => true],
            ['key' => 'invoice', 'label' => 'Invoice', 'path' => $detailKontrak?->file_invoice, 'required' => true],
            ['key' => 'faktur_pajak', 'label' => 'Faktur Pajak', 'path' => $detailKontrak?->file_faktur_pajak, 'required' => $requiresTaxDocuments],
            ['key' => 'kwitansi', 'label' => 'Kwitansi', 'path' => $detailKontrak?->file_kwitansi ?? null, 'required' => false],
        ])->map(function ($item) {
            $isAvailable = !empty($item['path']);
            $status = !$item['required'] ? 'not_required' : ($isAvailable ? 'ready' : 'missing');
            return array_merge($item, ['status' => $status, 'is_available' => $isAvailable]);
        })->values();

        // Recent activities
        $recentActivities = collect($npi->logs ?? [])
            ->sortByDesc('created_at')
            ->take(8)
            ->map(fn ($log) => [
                'title' => str_replace('_', ' ', $log->aksi ?? 'Aktivitas'),
                'time' => optional($log->created_at)->format('d M Y H:i'),
                'actor' => $log->user?->name ?? 'Sistem',
                'note' => $log->catatan,
            ])->values();

        $bendaharaPengeluaran = User::role('Bendahara Pengeluaran')->first();
        // Dual-role check: find all active roles the user has that are pending on this NPI
        $user = auth()->user();
        $activeRoleApprovals = [];
        $wfStatus = $activeWorkflowInstance?->status ?? '';
        
        if ($user->hasRole('PPK') && $ppkApproval && $ppkApproval->status === 'PENDING' && in_array($wfStatus, ['IN_PROGRESS', 'REVISION'])) {
            $activeRoleApprovals[] = [
                'role' => 'PPK',
                'approval_id' => $ppkApproval->id,
                'approveRoute' => route('verifikasi-ppk.npi.kontrak.approve', $npi->id),
                'revisiRoute' => route('verifikasi-ppk.npi.kontrak.revisi', $npi->id)
            ];
        }
        
        if ($user->hasRole('Koordinator Keuangan') && $koordinatorApproval && $koordinatorApproval->status === 'PENDING' && in_array($wfStatus, ['IN_PROGRESS', 'REVISION'])) {
            $activeRoleApprovals[] = [
                'role' => 'Koordinator Keuangan',
                'approval_id' => $koordinatorApproval->id,
                'approveRoute' => route('verifikasi-koordinator.npi.kontrak.approve', $npi->id),
                'revisiRoute' => route('verifikasi-koordinator.npi.kontrak.revisi', $npi->id)
            ];
        }

        $currentUserStatus = $currentUserApproval?->status ?? 'N/A';

        return view('verifikasi_ppk.npi_kontrak_detail', compact(
            'npi',
            'spm',
            'spp',
            'tagihan',
            'detailKontrak',
            'termin',
            'kontrak',
            'vendor',
            'rekening',
            'nominalNpi',
            'activeWorkflowInstance',
            'benpenApproval',
            'ppkApproval',
            'kasubbagApproval',
            'koordinatorApproval',
            'currentUserApproval',
            'canApprove',
            'canRequestRevision',
            'statusFinal',
            'revisionNotes',
            'documentStatuses',
            'recentActivities',
            'bendaharaPengeluaran',
            'currentRole',
            'routePrefix', 'activeRoleApprovals', 'currentUserStatus'
        ));
    }

    /**
     * Approve NPI oleh PPK.
     */
    public function approve(Request $request, $npi_id)
    {
        $npi = DokumenNpi::findOrFail($npi_id);
        $roleCodes = $this->activeRoleCodes();
        if (empty($roleCodes)) abort(403, 'Akses ditolak.');
        $currentRole = $this->currentRoleCode($roleCodes);
        $routePrefix = $this->routePrefix();

        DB::transaction(function () use ($npi, $request, $currentRole) {
            $workflowService = app(WorkflowService::class);
            $activeInstance = $workflowService->getActiveInstance($npi);
            $approval = $activeInstance?->approvals()
                ->where('role_code', $currentRole)
                ->where('status', 'PENDING')
                ->where(function ($query) {
                    $query->whereNull('assigned_user_id')
                        ->orWhere('assigned_user_id', auth()->id());
                })
                ->first();

            abort_unless($approval, 403, 'Tidak ada antrean verifikasi untuk role Anda pada NPI ini.');

            $instance = $workflowService->approveCurrentStep($npi, auth()->id(), $request->input('catatan'), $approval->id);

            LogStatusDokumen::create([
                'dokumen_type' => DokumenNpi::class,
                'dokumen_id' => $npi->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => $currentRole,
                'status_sebelumnya' => $npi->status,
                'status_baru' => $instance->status === 'APPROVED' ? DokumenNpi::STATUS_DISETUJUI_FINAL : $npi->status,
                'aksi' => 'APPROVE_' . str($currentRole)->upper()->replace(' ', '_') . '_NPI',
                'catatan' => $request->input('catatan', "NPI disetujui {$currentRole}."),
                'ip_address' => request()->ip(),
            ]);

            if ($instance->status === 'APPROVED') {
                $npi->update(['status' => DokumenNpi::STATUS_DISETUJUI_FINAL]);
            }
        });

        return redirect()->route($routePrefix . '.show', $npi_id)
            ->with('success', 'NPI berhasil disetujui.');
    }

    /**
     * Minta revisi NPI oleh PPK.
     */
    public function revisi(Request $request, $npi_id)
    {
        $request->validate([
            'catatan_revisi' => 'required|string|max:1000',
        ]);

        $npi = DokumenNpi::findOrFail($npi_id);
        $roleCodes = $this->activeRoleCodes();
        if (empty($roleCodes)) abort(403, 'Akses ditolak.');
        $currentRole = $this->currentRoleCode($roleCodes);
        $routePrefix = $this->routePrefix();

        DB::transaction(function () use ($npi, $request, $currentRole) {
            $workflowService = app(WorkflowService::class);
            $activeInstance = $workflowService->getActiveInstance($npi);
            $approval = $activeInstance?->approvals()
                ->where('role_code', $currentRole)
                ->where('status', 'PENDING')
                ->where(function ($query) {
                    $query->whereNull('assigned_user_id')
                        ->orWhere('assigned_user_id', auth()->id());
                })
                ->first();

            abort_unless($approval, 403, 'Tidak ada antrean verifikasi untuk role Anda pada NPI ini.');

            $workflowService->requestRevision($npi, auth()->id(), $request->catatan_revisi, $approval->id);

            $npi->update(['status' => DokumenNpi::STATUS_REVISI]);

            LogStatusDokumen::create([
                'dokumen_type' => DokumenNpi::class,
                'dokumen_id' => $npi->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => $currentRole,
                'status_sebelumnya' => $npi->status,
                'status_baru' => DokumenNpi::STATUS_REVISI,
                'aksi' => 'REVISI_' . str($currentRole)->upper()->replace(' ', '_') . '_NPI',
                'catatan' => $request->catatan_revisi,
                'ip_address' => request()->ip(),
            ]);

            $benPenUsers = User::role('Bendahara Pengeluaran')->get();
            if ($benPenUsers->isNotEmpty()) {
                Notification::send($benPenUsers, new WorkflowNotification([
                    'title' => "NPI Kontrak Dikembalikan oleh {$currentRole}",
                    'message' => "NPI {$npi->nomor_npi} dikembalikan oleh {$currentRole}: {$request->catatan_revisi}",
                    'url' => route('npis.kontrak.index'),
                    'icon' => 'reply',
                    'color' => 'warning',
                ]));
            }
        });

        return redirect()->route($routePrefix . '.show', $npi_id)
            ->with('success', 'NPI dikembalikan untuk revisi.');
    }
}
