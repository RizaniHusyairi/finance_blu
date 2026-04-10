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
    /**
     * Tampilkan daftar antrean verifikasi SPM Kontrak untuk PPSPM.
     */
    public function index(Request $request)
    {
        $roleName = 'PPSPM';

        // Ambil SPM khusus dari SPP Kontrak dan melibatkan workflow dari PPSPM
        $spms = DokumenSpm::with([
            'spp.tagihan.pihak',
            'spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor',
            'workflowInstances' => function ($q) {
                $q->latest()->limit(1);
            },
            'workflowInstances.approvals'
        ])
            ->whereHas('spp.tagihan', fn ($q) => $q->where('tipe_tagihan', 'KONTRAK'))
            ->whereHas('workflowInstances', function ($q) use ($roleName) {
                $q->whereHas('approvals', function ($a) use ($roleName) {
                    $a->where('role_code', $roleName);
                });
            })
            ->latest()
            ->get();

        $processedSpms = collect();
        foreach ($spms as $spm) {
            $wf = $spm->workflowInstances->first();
            if (!$wf) continue;

            $ppspmApproval = $wf->approvals->where('role_code', $roleName)->first();
            $kasubbagApproval = $wf->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->first();

            if ($wf->status === 'REVISION') {
                $statusFinal = 'Perlu Revisi';
            } elseif ($wf->status === 'APPROVED') {
                $statusFinal = 'Selesai Diverifikasi';
            } else {
                if ($ppspmApproval && $ppspmApproval->status === 'PENDING' && $kasubbagApproval && $kasubbagApproval->status === 'PENDING') {
                    $statusFinal = 'Menunggu Verifikasi';
                } elseif ($ppspmApproval && $ppspmApproval->status === 'PENDING') {
                    $statusFinal = 'Menunggu PPSPM';
                } elseif ($kasubbagApproval && $kasubbagApproval->status === 'PENDING') {
                    $statusFinal = 'Menunggu Kasubbag';
                } else {
                    $statusFinal = $wf->status;
                }
            }

            $canAct = ($ppspmApproval && $ppspmApproval->status === 'PENDING' && $wf->status === 'IN_PROGRESS');

            $spm->ppspmApprovalStatus = $ppspmApproval ? $ppspmApproval->status : 'N/A';
            $spm->kasubbagApprovalStatus = $kasubbagApproval ? $kasubbagApproval->status : 'N/A';
            $spm->statusFinal = $statusFinal;
            $spm->canAct = $canAct;
            $spm->workflow = $wf;

            $processedSpms->push($spm);
        }

        $countPending = $processedSpms->where('ppspmApprovalStatus', 'PENDING')->count();
        $countApprovedMe = $processedSpms->where('ppspmApprovalStatus', 'APPROVED')->count();
        $countRevisi = $processedSpms->where('ppspmApprovalStatus', 'REVISION')->count();
        $countSelesai = $processedSpms->where('statusFinal', 'Selesai Diverifikasi')->count();

        $viewSpms = $processedSpms;

        // Terapkan Filter Status Saya (PPSPM)
        if ($request->has('status') && $request->status !== 'Semua') {
            if ($request->status === 'Pending') {
                $viewSpms = $viewSpms->where('ppspmApprovalStatus', 'PENDING');
            } elseif ($request->status === 'Approved') {
                $viewSpms = $viewSpms->where('ppspmApprovalStatus', 'APPROVED');
            } elseif ($request->status === 'Revisi') {
                $viewSpms = $viewSpms->where('ppspmApprovalStatus', 'REVISION');
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
            'countSelesai'
        ));
    }

    /**
     * Tampilkan halaman detail untuk proses pengambilan keputusan oleh PPSPM.
     */
    public function show($id)
    {
        $roleName = 'PPSPM';

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

        $ppspmApproval = $wf->approvals->where('role_code', $roleName)->first();
        $kasubbagApproval = $wf->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->first();
        $operatorApproval = collect(['status' => 'APPROVED', 'acted_by_user_id' => $spmModel->dibuat_oleh_id, 'acted_at' => $spmModel->created_at]);

        if ($wf->status === 'REVISION') {
            $statusFinal = 'Perlu Revisi';
        } elseif ($wf->status === 'APPROVED') {
            $statusFinal = 'Selesai Diverifikasi';
        } else {
            if ($ppspmApproval && $ppspmApproval->status === 'PENDING' && $kasubbagApproval && $kasubbagApproval->status === 'PENDING') {
                $statusFinal = 'Menunggu Verifikasi';
            } elseif ($ppspmApproval && $ppspmApproval->status === 'PENDING') {
                $statusFinal = 'Menunggu PPSPM';
            } elseif ($kasubbagApproval && $kasubbagApproval->status === 'PENDING') {
                $statusFinal = 'Menunggu Kasubbag';
            } else {
                $statusFinal = $wf->status;
            }
        }

        $canAct = ($ppspmApproval && $ppspmApproval->status === 'PENDING' && $wf->status === 'IN_PROGRESS');

        $latestPpspmRevisionNote = $wf->approvals->where('role_code', $roleName)->where('status', 'REVISION')->sortByDesc('acted_at')->first();
        $latestKasubbagRevisionNote = $wf->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->where('status', 'REVISION')->sortByDesc('acted_at')->first();

        // Dokumen pendukung mapping (sama seperti logic SpmKontrakController)
        $tagihan = $spmModel->spp?->tagihan;
        $detailKontrak = $tagihan?->detailKontrak;
        $kontrak = $detailKontrak?->kontrakTermin?->kontrak;
        $isPelunasan = ($detailKontrak?->kontrakTermin?->jenis_termin ?? null) === 'PELUNASAN';
        $requiresTaxDocuments = collect($tagihan?->potonganTagihan ?? [])->filter(fn ($item) => $item->jenis_potongan !== 'ANGSURAN_UANG_MUKA')->isNotEmpty();
        $ebillingDocument = $spmModel->arsipDokumen?->firstWhere('jenis_dokumen', 'E_BILLING');

        $documentStatuses = collect([
            ['key' => 'bapp', 'label' => 'BAPP', 'path' => $detailKontrak?->file_bapp, 'required' => true],
            ['key' => 'bast', 'label' => 'BAST', 'path' => $detailKontrak?->file_bast, 'required' => $isPelunasan],
            ['key' => 'bap', 'label' => 'BAP', 'path' => $detailKontrak?->file_bap, 'required' => true],
            ['key' => 'invoice', 'label' => 'Invoice', 'path' => $detailKontrak?->file_invoice, 'required' => true],
            ['key' => 'faktur_pajak', 'label' => 'Faktur Pajak', 'path' => $detailKontrak?->file_faktur_pajak, 'required' => $requiresTaxDocuments],
            ['key' => 'ebilling', 'label' => 'E-Billing', 'path' => $ebillingDocument?->path_file, 'required' => $requiresTaxDocuments],
            ['key' => 'kwitansi', 'label' => 'Kwitansi', 'path' => $detailKontrak?->file_kwitansi ?? null, 'required' => false],
        ])->map(function ($item) {
            $isAvailable = !empty($item['path']);
            $status = !$item['required'] ? 'not_required' : ($isAvailable ? 'ready' : 'missing');
            return array_merge($item, ['status' => $status, 'is_available' => $isAvailable]);
        })->values();

        return view('verifikasi_ppspm.spm_kontrak_detail', compact(
            'spmModel',
            'wf',
            'ppspmApproval',
            'kasubbagApproval',
            'operatorApproval',
            'statusFinal',
            'canAct',
            'latestPpspmRevisionNote',
            'latestKasubbagRevisionNote',
            'documentStatuses'
        ));
    }

    /**
     * Proses Setujui oleh PPSPM.
     */
    public function approve($id)
    {
        $spm = DokumenSpm::with('spp')->findOrFail($id);

        try {
            app(WorkflowService::class)->approveCurrentStep($spm, Auth::id(), 'Dokumen SPM disetujui oleh PPSPM.');

            $workflowFullyApproved = $this->finalizeWorkflowIfComplete($spm);

            if ($workflowFullyApproved) {
                // Jika semua workflow (termasuk kasubbag) sudah approve, SPM -> Final
                $spm->update(['status' => DokumenSpm::STATUS_DISETUJUI_FINAL]);
            }

            $statusBaru = $workflowFullyApproved ? DokumenSpm::STATUS_DISETUJUI_FINAL : 'Disetujui PPSPM';

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSpm::class,
                'dokumen_id' => $spm->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? 'PPSPM',
                'status_sebelumnya' => 'Menunggu Verifikasi',
                'status_baru' => $statusBaru,
                'aksi' => 'APPROVE_PPSPM',
                'catatan' => $workflowFullyApproved
                    ? 'Dokumen SPM disetujui PPSPM. Seluruh approver telah menyetujui — SPM disahkan.'
                    : 'Dokumen SPM disetujui PPSPM.',
                'ip_address' => request()->ip(),
            ]);

            if ($workflowFullyApproved) {
                $operators = User::role('Operator BLU')->get();
                Notification::send($operators, new WorkflowNotification([
                    'title' => 'SPM Kontrak Disetujui Final',
                    'message' => "SPM {$spm->nomor_spm} telah disetujui oleh semua pihak dan disahkan.",
                    'url' => route('spms.kontrak.detail', $spm->spp_id),
                    'icon' => 'verified',
                    'color' => 'success'
                ]));
            }

            return redirect()->route('verifikasi-ppspm.spm.kontrak.index')->with('success', $workflowFullyApproved
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

        try {
            app(WorkflowService::class)->requestRevision($spm, Auth::id(), $request->catatan_revisi);

            $spm->update([
                'status' => DokumenSpm::STATUS_REVISI,
            ]);

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSpm::class,
                'dokumen_id' => $spm->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? 'PPSPM',
                'status_sebelumnya' => 'Menunggu Verifikasi',
                'status_baru' => DokumenSpm::STATUS_REVISI,
                'aksi' => 'REVISI_PPSPM',
                'catatan' => $request->catatan_revisi,
                'ip_address' => request()->ip(),
            ]);

            $operators = User::role('Operator BLU')->get();
            Notification::send($operators, new WorkflowNotification([
                'title' => 'SPM Kontrak Direvisi PPSPM',
                'message' => "SPM {$spm->nomor_spm} perlu revisi. Catatan PPSPM: {$request->catatan_revisi}",
                'url' => route('spms.kontrak.detail', $spm->spp_id),
                'icon' => 'error_outline',
                'color' => 'danger'
            ]));

            return redirect()->route('verifikasi-ppspm.spm.kontrak.index')->with('warning', "Catatan revisi untuk SPM {$spm->nomor_spm} telah dikirim.");
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
}
