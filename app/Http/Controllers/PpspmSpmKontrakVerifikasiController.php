<?php

namespace App\Http\Controllers;

use App\Models\DokumenSpm;
use App\Models\LogStatusDokumen;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class PpspmSpmKontrakVerifikasiController extends Controller
{
    private function activeRoleCodes(): array
    {
        $roles = [];
        if (Auth::user()->hasRole('PPSPM')) {
            $roles[] = 'PPSPM';
        }
        if (Auth::user()->hasRole('Koordinator Keuangan')) {
            $roles[] = 'Koordinator Keuangan';
        }
        return $roles;
    }

    private function routePrefix(): string
    {
        // Tetap gunakan route prefix sesuai URL saat ini untuk redirect kembali
        return request()->routeIs('verifikasi-koordinator.*')
            ? 'verifikasi-koordinator.spm.kontrak'
            : 'verifikasi-ppspm.spm.kontrak';
    }

    /**
     * Tampilkan daftar antrean verifikasi SPM Kontrak untuk PPSPM.
     */
    public function index(Request $request)
    {
        $roleCodes = $this->activeRoleCodes();
        if (empty($roleCodes)) abort(403, 'Akses ditolak.');
        $routePrefix = $this->routePrefix();

        // Ambil SPM khusus dari SPP Kontrak dan melibatkan workflow dari PPSPM/Koordinator
        $spms = DokumenSpm::with([
            'spp.tagihan.pihak',
            'spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor',
            'workflowInstances' => function ($q) {
                $q->latest()->limit(1);
            },
            'workflowInstances.approvals'
        ])
            ->whereHas('spp.tagihan', fn ($q) => $q->where('tipe_tagihan', 'KONTRAK'))
            ->whereHas('workflowInstances', function ($q) use ($roleCodes) {
                $q->whereHas('approvals', function ($a) use ($roleCodes) {
                    $a->whereIn('role_code', $roleCodes);
                });
            })
            ->latest()
            ->get();

        $processedSpms = collect();
        foreach ($spms as $spm) {
            $wf = $spm->workflowInstances->first();
            if (!$wf) continue;

            $ppspmApproval = $wf->approvals->where('role_code', 'PPSPM')->first();
            $kasubbagApproval = $wf->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->first();
            $koordinatorApproval = $wf->approvals->where('role_code', 'Koordinator Keuangan')->first();
            $myApprovals = $wf->approvals->whereIn('role_code', $roleCodes);
            $currentApproval = $myApprovals->where('status', 'PENDING')->first() ?: $myApprovals->first();

            if ($wf->status === 'REVISION') {
                $statusFinal = 'Perlu Revisi';
            } elseif ($wf->status === 'APPROVED') {
                $statusFinal = 'Selesai Diverifikasi';
            } else {
                $pendingRoles = $wf->approvals
                    ->where('status', 'PENDING')
                    ->pluck('role_code')
                    ->map(fn ($role) => match ($role) {
                        'Kepala Subbagian Keuangan dan Tata Usaha' => 'Kasubbag',
                        'Koordinator Keuangan' => 'Koordinator',
                        default => $role,
                    });

                if ($pendingRoles->count() > 1) {
                    $statusFinal = 'Menunggu Verifikasi';
                } elseif ($pendingRoles->count() === 1) {
                    $statusFinal = 'Menunggu ' . $pendingRoles->first();
                } else {
                    $statusFinal = $wf->status;
                }
            }

            $canAct = ($currentApproval && $currentApproval->status === 'PENDING' && $wf->status === 'IN_PROGRESS');

            $spm->ppspmApprovalStatus = $ppspmApproval ? $ppspmApproval->status : 'N/A';
            $spm->kasubbagApprovalStatus = $kasubbagApproval ? $kasubbagApproval->status : 'N/A';
            $spm->koordinatorApprovalStatus = $koordinatorApproval ? $koordinatorApproval->status : 'N/A';
            $spm->currentApprovalStatus = $currentApproval ? $currentApproval->status : 'N/A';
            $spm->statusFinal = $statusFinal;
            $spm->canAct = $canAct;
            $spm->workflow = $wf;

            $processedSpms->push($spm);
        }

        $countPending = $processedSpms->where('currentApprovalStatus', 'PENDING')->count();
        $countApprovedMe = $processedSpms->where('currentApprovalStatus', 'APPROVED')->count();
        $countRevisi = $processedSpms->where('currentApprovalStatus', 'REVISION')->count();
        $countSelesai = $processedSpms->where('statusFinal', 'Selesai Diverifikasi')->count();

        $viewSpms = $processedSpms;

        // Terapkan Filter Status Saya (PPSPM)
        if ($request->has('status') && $request->status !== 'Semua') {
            if ($request->status === 'Pending') {
                $viewSpms = $viewSpms->where('currentApprovalStatus', 'PENDING');
            } elseif ($request->status === 'Approved') {
                $viewSpms = $viewSpms->where('currentApprovalStatus', 'APPROVED');
            } elseif ($request->status === 'Revisi') {
                $viewSpms = $viewSpms->where('currentApprovalStatus', 'REVISION');
            }
        }

        // Terapkan Filter Kasubbag
        if ($request->has('status_kasubbag') && $request->status_kasubbag !== 'Semua') {
            if ($request->status_kasubbag === 'Pending') {
                $viewSpms = $viewSpms->where('kasubbagApprovalStatus', 'PENDING');
            } elseif ($request->status_kasubbag === 'Approved') {
                $viewSpms = $viewSpms->where('kasubbagApprovalStatus', 'APPROVED');
            } elseif ($request->status_kasubbag === 'Revisi') {
                $viewSpms = $viewSpms->where('kasubbagApprovalStatus', 'REVISION');
            }
        }

        // Terapkan Filter Pencarian (Search)
        if ($request->has('search') && $request->search != '') {
            $search = strtolower($request->search);
            $viewSpms = $viewSpms->filter(function($spm) use ($search) {
                // Check nomor SPM
                if (str_contains(strtolower($spm->nomor_spm ?? ''), $search)) return true;
                if ($spm->spp) {
                    if (str_contains(strtolower($spm->spp->nomor_spp ?? ''), $search)) return true;
                    if ($spm->spp->tagihan) {
                        if (str_contains(strtolower($spm->spp->tagihan->nomor_tagihan ?? ''), $search)) return true;
                        $kontrak = $spm->spp->tagihan->detailKontrak->kontrakTermin->kontrak ?? null;
                        if ($kontrak) {
                            if (str_contains(strtolower($kontrak->nomor_spk ?? ''), $search)) return true;
                            if (str_contains(strtolower($kontrak->nama_pekerjaan ?? ''), $search)) return true;
                            if ($kontrak->vendor && str_contains(strtolower($kontrak->vendor->nama_pihak ?? ''), $search)) return true;
                        }
                    }
                }
                return false;
            });
        }

        return view('verifikasi_ppspm.spm_kontrak_index', compact(
            'viewSpms',
            'countPending',
            'countApprovedMe',
            'countRevisi',
            'countSelesai',
            'routePrefix'
        ));
    }

    /**
     * Tampilkan halaman detail untuk proses pengambilan keputusan oleh PPSPM.
     */
    public function show($id)
    {
        $roleCodes = $this->activeRoleCodes();
        if (empty($roleCodes)) abort(403, 'Akses ditolak.');
        $routePrefix = $this->routePrefix();

        $spmModel = DokumenSpm::with([
            'spp',
            'spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor.rekening',
            'spp.tagihan.detailKontrak.kontrakTermin.kontrak.dipa.activeRevision.items.coa',
            'spp.tagihan.detailKontrak.arsipDokumen',
            'arsipDokumen',
            'spp.tagihan.potonganTagihan.pajak',
            'dipaRevisionItem.coa',
            'workflowInstances' => function ($q) {
                $q->latest()->limit(1);
            },
            'workflowInstances.approvals'
        ])->findOrFail($id);

        $wf = $spmModel->workflowInstances->first();
        if (!$wf) {
            return back()->with('error', 'Workflow tidak ditemukan untuk SPM ini.');
        }

        $ppspmApproval = $wf->approvals->where('role_code', 'PPSPM')->first();
        $kasubbagApproval = $wf->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->first();
        $koordinatorApproval = $wf->approvals->where('role_code', 'Koordinator Keuangan')->first();
        $myApprovals = $wf->approvals->whereIn('role_code', $roleCodes);
        $currentApproval = $myApprovals->where('status', 'PENDING')->first() ?: $myApprovals->first();
        $operatorApproval = collect(['status' => 'APPROVED', 'acted_by_user_id' => $spmModel->dibuat_oleh_id, 'acted_at' => $spmModel->created_at]);

        if ($wf->status === 'REVISION') {
            $statusFinal = 'Perlu Revisi';
        } elseif ($wf->status === 'APPROVED') {
            $statusFinal = 'Selesai Diverifikasi';
        } else {
            $pendingRoles = $wf->approvals
                ->where('status', 'PENDING')
                ->pluck('role_code')
                ->map(fn ($role) => match ($role) {
                    'Kepala Subbagian Keuangan dan Tata Usaha' => 'Kasubbag',
                    'Koordinator Keuangan' => 'Koordinator',
                    default => $role,
                });

            if ($pendingRoles->count() > 1) {
                $statusFinal = 'Menunggu Verifikasi';
            } elseif ($pendingRoles->count() === 1) {
                $statusFinal = 'Menunggu ' . $pendingRoles->first();
            } else {
                $statusFinal = $wf->status;
            }
        }

        $canAct = ($currentApproval && $currentApproval->status === 'PENDING' && $wf->status === 'IN_PROGRESS');

        $latestPpspmRevisionNote = $wf->approvals->where('role_code', 'PPSPM')->where('status', 'REVISION')->sortByDesc('acted_at')->first();
        $latestKasubbagRevisionNote = $wf->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->where('status', 'REVISION')->sortByDesc('acted_at')->first();
        $latestKoordinatorRevisionNote = $wf->approvals->where('role_code', 'Koordinator Keuangan')->where('status', 'REVISION')->sortByDesc('acted_at')->first();
        $latestCurrentRevisionNote = $myApprovals->where('status', 'REVISION')->sortByDesc('acted_at')->first();

        // Dokumen pendukung mapping (sama seperti logic SpmKontrakController)
        $tagihan = $spmModel->spp?->tagihan;
        $detailKontrak = $tagihan?->detailKontrak;
        $kontrak = $detailKontrak?->kontrakTermin?->kontrak;
        $isPelunasan = ($detailKontrak?->kontrakTermin?->jenis_termin ?? null) === 'PELUNASAN';
        $requiresTaxDocuments = collect($tagihan?->potonganTagihan ?? [])->filter(fn ($item) => $item->jenis_potongan !== 'ANGSURAN_UANG_MUKA')->isNotEmpty();
        $documentStatuses = collect([
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

        // Dual-role check: find all active roles the user has that are pending on this SPM
        $user = auth()->user();
        $activeRoleApprovals = [];
        
        if ($user->hasRole('PPSPM') && $ppspmApproval && $ppspmApproval->status === 'PENDING' && $wf->status === 'IN_PROGRESS') {
            $activeRoleApprovals[] = [
                'role' => 'PPSPM',
                'approval_id' => $ppspmApproval->id,
                'approveRoute' => route('verifikasi-ppspm.spm.kontrak.approve', $id),
                'revisiRoute' => route('verifikasi-ppspm.spm.kontrak.revisi', $id)
            ];
        }
        
        if ($user->hasRole('Koordinator Keuangan') && $koordinatorApproval && $koordinatorApproval->status === 'PENDING' && $wf->status === 'IN_PROGRESS') {
            $activeRoleApprovals[] = [
                'role' => 'Koordinator Keuangan',
                'approval_id' => $koordinatorApproval->id,
                'approveRoute' => route('verifikasi-koordinator.spm.kontrak.approve', $id),
                'revisiRoute' => route('verifikasi-koordinator.spm.kontrak.revisi', $id)
            ];
        }

        return view('verifikasi_ppspm.spm_kontrak_detail', compact(
            'spmModel',
            'wf',
            'ppspmApproval',
            'kasubbagApproval',
            'koordinatorApproval',
            'currentApproval',
            'operatorApproval',
            'statusFinal',
            'canAct',
            'latestPpspmRevisionNote',
            'latestKasubbagRevisionNote',
            'latestKoordinatorRevisionNote',
            'latestCurrentRevisionNote',
            'documentStatuses',
            'routePrefix',
            'activeRoleApprovals'
        ));
    }

    /**
     * Proses Setujui oleh PPSPM.
     */
    public function approve(Request $request, $id)
    {
        $spm = DokumenSpm::with('spp')->findOrFail($id);
        $roleCodes = $this->activeRoleCodes();
        if (empty($roleCodes)) abort(403, 'Akses ditolak.');
        $routePrefix = $this->routePrefix();

        $wf = $spm->workflowInstances()->latest()->first();
        if (!$wf) {
            return back()->with('error', 'Workflow verifikasi tidak ditemukan untuk SPM ini.');
        }

        $approval = $this->resolveApprovalForAction($wf, $roleCodes, $request->input('approval_id'));
        if (!$approval) {
            return back()->with('error', 'Tidak ada langkah verifikasi yang menunggu persetujuan Anda pada SPM ini.');
        }
        $approvalId = $approval->id;
        $roleName = $approval->role_code;

        try {
            app(WorkflowService::class)->approveCurrentStep($spm, Auth::id(), "Dokumen SPM disetujui oleh {$roleName}.", $approvalId);

            $workflowFullyApproved = $this->finalizeWorkflowIfComplete($spm);

            if ($workflowFullyApproved) {
                // Verifikasi paralel selesai: SPM langsung TERBIT ber-TTE QR (tanpa upload manual)
                // dan siap dibuatkan NPI oleh Bendahara Pengeluaran.
                $spm->update(['status' => DokumenSpm::STATUS_SPM_TERBIT]);
            }

            $statusBaru = $workflowFullyApproved ? DokumenSpm::STATUS_SPM_TERBIT : "Disetujui {$roleName}";

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSpm::class,
                'dokumen_id' => $spm->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => $roleName,
                'status_sebelumnya' => 'Menunggu Verifikasi',
                'status_baru' => $statusBaru,
                'aksi' => 'APPROVE_' . str($roleName)->upper()->replace(' ', '_'),
                'catatan' => $workflowFullyApproved
                    ? "Dokumen SPM disetujui {$roleName}. SPM terbit ber-TTE QR dan siap dibuatkan NPI."
                    : "Dokumen SPM disetujui {$roleName}.",
                'ip_address' => request()->ip(),
            ]);

            if ($workflowFullyApproved) {
                $operators = User::role('Operator BLU')->get();
                Notification::send($operators, new WorkflowNotification([
                    'title' => 'SPM Kontrak Terbit ber-TTE',
                    'message' => "SPM {$spm->nomor_spm} telah disetujui semua pihak dan terbit ber-TTE QR.",
                    'url' => route('spms.kontrak.detail', $spm->spp_id),
                    'icon' => 'verified',
                    'color' => 'success'
                ]));

                $bendahara = User::role('Bendahara Pengeluaran')->get();
                if ($bendahara->isNotEmpty()) {
                    Notification::send($bendahara, new WorkflowNotification([
                        'title' => 'SPM Kontrak Terbit — NPI Siap Dibuat',
                        'message' => "SPM {$spm->nomor_spm} telah terbit ber-TTE. NPI Kontrak dapat segera Anda buat.",
                        'url' => route('npis.kontrak.index'),
                        'icon' => 'receipt_long',
                        'color' => 'success'
                    ]));
                }
            }

            return redirect()->route($routePrefix . '.index')->with('success', $workflowFullyApproved
                ? "SPM Nomor {$spm->nomor_spm} telah disetujui oleh semua pihak."
                : "SPM Nomor {$spm->nomor_spm} berhasil Anda setujui.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses approval: ' . $e->getMessage());
        }
    }

    /**
     * Proses Revisi oleh PPSPM.
     */
    public function revisi(Request $request, $id)
    {
        $request->validate([
            'catatan_revisi' => 'required|string|max:1000'
        ]);

        $spm = DokumenSpm::with('spp')->findOrFail($id);
        $roleCodes = $this->activeRoleCodes();
        if (empty($roleCodes)) abort(403, 'Akses ditolak.');
        $routePrefix = $this->routePrefix();

        $wf = $spm->workflowInstances()->latest()->first();
        if (!$wf) {
            return back()->with('error', 'Workflow verifikasi tidak ditemukan untuk SPM ini.');
        }

        $approval = $this->resolveApprovalForAction($wf, $roleCodes, $request->input('approval_id'));
        if (!$approval) {
            return back()->with('error', 'Tidak ada langkah verifikasi yang menunggu tindakan Anda pada SPM ini.');
        }
        $approvalId = $approval->id;
        $roleName = $approval->role_code;

        try {
            app(WorkflowService::class)->requestRevision($spm, Auth::id(), $request->catatan_revisi, $approvalId);

            $spm->update([
                'status' => DokumenSpm::STATUS_REVISI,
            ]);

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSpm::class,
                'dokumen_id' => $spm->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => $roleName,
                'status_sebelumnya' => 'Menunggu Verifikasi',
                'status_baru' => DokumenSpm::STATUS_REVISI,
                'aksi' => 'REVISI_' . str($roleName)->upper()->replace(' ', '_'),
                'catatan' => $request->catatan_revisi,
                'ip_address' => request()->ip(),
            ]);

            $operators = User::role('Operator BLU')->get();
            Notification::send($operators, new WorkflowNotification([
                'title' => "SPM Kontrak Direvisi {$roleName}",
                'message' => "SPM {$spm->nomor_spm} perlu revisi. Catatan {$roleName}: {$request->catatan_revisi}",
                'url' => route('spms.kontrak.detail', $spm->spp_id),
                'icon' => 'error_outline',
                'color' => 'danger'
            ]));

            return redirect()->route($routePrefix . '.index')->with('warning', "Catatan revisi untuk SPM {$spm->nomor_spm} telah dikirim.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses revisi: ' . $e->getMessage());
        }
    }

    /**
     * Mengecek apakah semua approval dalam workflow_instances sudah komplit (semua APPROVED tanpa REVISI/PENDING)
     */
    private function finalizeWorkflowIfComplete(DokumenSpm $spm): bool
    {
        $workflow = $spm->workflowInstances()->with('approvals')->latest()->first();

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

    /**
     * Tentukan approval mana yang akan ditindak. Prioritaskan ID eksplisit dari request,
     * jatuhkan ke approval PENDING milik role aktif user pada URL path saat ini
     * (mis. Koordinator mengakses via /verifikasi-koordinator/...).
     */
    private function resolveApprovalForAction($workflow, array $roleCodes, $approvalId)
    {
        if (!empty($approvalId)) {
            $approval = $workflow->approvals()
                ->where('id', $approvalId)
                ->whereIn('role_code', $roleCodes)
                ->first();
            if ($approval) {
                return $approval;
            }
        }

        // Fallback: cari approval PENDING milik salah satu role aktif.
        // Urutkan agar role yang cocok dengan URL path saat ini didahulukan
        // (mis. /verifikasi-koordinator/* → Koordinator Keuangan).
        $preferredRole = request()->routeIs('verifikasi-koordinator.*')
            ? 'Koordinator Keuangan'
            : 'PPSPM';

        $pending = $workflow->approvals()
            ->whereIn('role_code', $roleCodes)
            ->where('status', 'PENDING')
            ->get();

        return $pending->firstWhere('role_code', $preferredRole)
            ?? $pending->first();
    }
}
