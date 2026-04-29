<?php

namespace App\Http\Controllers;

use App\Models\DokumenSp2d;
use App\Models\LogStatusDokumen;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class PpkSp2dKontrakVerifikasiController extends Controller
{
    /**
     * Halaman antrean verifikasi SP2D Kontrak untuk PPK.
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
                    $pendingRoles = $pending->pluck('role_code')->map(fn ($role) => match($role) {
                        'PPK' => 'PPK',
                        'Kepala Subbagian Keuangan dan Tata Usaha' => 'Kasubbag',
                        default => $role,
                    });
                    $sp2d->_statusFinal = 'Menunggu ' . $pendingRoles->join(' & ');
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
            'pending' => $sp2dList->filter(fn ($n) => $n->_ppkApproval?->status === 'PENDING')->count(),
            'approved' => $sp2dList->filter(fn ($n) => $n->_ppkApproval?->status === 'APPROVED')->count(),
            'revision' => $sp2dList->filter(fn ($n) => in_array($n->_ppkApproval?->status, ['REVISION', 'REJECTED'])
                || $n->_workflowInstance?->status === 'REVISION')->count(),
            'selesai' => $sp2dList->filter(fn ($n) => $n->_statusFinal === 'Selesai Diverifikasi')->count(),
        ];

        return view('verifikasi_sp2d.kontrak_index', [
            'sp2dList' => $filtered->values(),
            'summary' => $summary,
            'filterPpk' => $filterPpk,
            'filterKasubbag' => $filterKasubbag,
            'search' => $search,
            'currentRole' => 'PPK',
            'routePrefix' => 'verifikasi-ppk.sp2d.kontrak',
        ]);
    }

    /**
     * Halaman detail verifikasi SP2D Kontrak untuk PPK.
     */
    public function show($sp2d_id)
    {
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

        $ppkApproval = $approvals->firstWhere('role_code', 'PPK');
        $kasubbagApproval = $approvals->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');
        $currentUserApproval = $ppkApproval;

        $canApprove = $ppkApproval && $ppkApproval->status === 'PENDING';
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
        $buktiTransferSp2d = $sp2d->arsipDokumen?->firstWhere('jenis_dokumen', 'BUKTI_TRANSFER_SP2D');

        $documentStatuses = collect([
            ['key' => 'npi', 'label' => 'NPI', 'path' => true, 'required' => true],
            ['key' => 'spm', 'label' => 'SPM', 'path' => true, 'required' => true],
            ['key' => 'spp', 'label' => 'SPP', 'path' => true, 'required' => true],
            ['key' => 'bapp', 'label' => 'BAPP', 'path' => $detailKontrak?->file_bapp, 'required' => true],
            ['key' => 'bast', 'label' => 'BAST', 'path' => $detailKontrak?->file_bast, 'required' => $isPelunasan],
            ['key' => 'bap', 'label' => 'BAP', 'path' => $detailKontrak?->file_bap, 'required' => true],
            ['key' => 'faktur_pajak', 'label' => 'Faktur Pajak', 'path' => $detailKontrak?->file_faktur_pajak, 'required' => $requiresTaxDocuments],
            ['key' => 'bukti_transfer', 'label' => 'Bukti Transfer SP2D', 'path' => $buktiTransferSp2d?->path_file, 'required' => false],
        ])->map(function ($item) {
            $isAvailable = !empty($item['path']);
            $status = !$item['required'] ? 'not_required' : ($isAvailable ? 'ready' : 'missing');
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
            'ppkApproval' => $ppkApproval,
            'kasubbagApproval' => $kasubbagApproval,
            'currentUserApproval' => $currentUserApproval,
            'canApprove' => $canApprove,
            'canRequestRevision' => $canRequestRevision,
            'statusFinal' => $statusFinal,
            'revisionNotes' => $revisionNotes,
            'documentStatuses' => $documentStatuses,
            'currentRole' => 'PPK',
            'routePrefix' => 'verifikasi-ppk.sp2d.kontrak',
        ]);
    }

    /**
     * Approve SP2D oleh PPK.
     */
    public function approve(Request $request, $sp2d_id)
    {
        $sp2d = DokumenSp2d::findOrFail($sp2d_id);

        DB::transaction(function () use ($sp2d, $request) {
            $workflowService = app(WorkflowService::class);
            $instance = $workflowService->approveCurrentStep($sp2d, auth()->id(), $request->input('catatan'));

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSp2d::class,
                'dokumen_id' => $sp2d->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => 'PPK',
                'status_sebelumnya' => $sp2d->status,
                'status_baru' => $instance->status === 'APPROVED' ? DokumenSp2d::STATUS_DISETUJUI_FINAL : $sp2d->status,
                'aksi' => 'APPROVE_PPK_SP2D',
                'catatan' => $request->input('catatan', 'SP2D disetujui PPK.'),
                'ip_address' => request()->ip(),
            ]);

            if ($instance->status === 'APPROVED') {
                $sp2d->update(['status' => DokumenSp2d::STATUS_DISETUJUI_FINAL]);
            }
        });

        return redirect()->route('verifikasi-ppk.sp2d.kontrak.show', $sp2d_id)
            ->with('success', 'SP2D berhasil disetujui.');
    }

    /**
     * Minta revisi SP2D oleh PPK.
     */
    public function revisi(Request $request, $sp2d_id)
    {
        $request->validate([
            'catatan_revisi' => 'required|string|max:1000',
        ]);

        $sp2d = DokumenSp2d::findOrFail($sp2d_id);

        DB::transaction(function () use ($sp2d, $request) {
            $workflowService = app(WorkflowService::class);
            $workflowService->requestRevision($sp2d, auth()->id(), $request->catatan_revisi);

            $sp2d->update(['status' => DokumenSp2d::STATUS_REVISI]);

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSp2d::class,
                'dokumen_id' => $sp2d->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => 'PPK',
                'status_sebelumnya' => $sp2d->status,
                'status_baru' => DokumenSp2d::STATUS_REVISI,
                'aksi' => 'REVISI_PPK_SP2D',
                'catatan' => $request->catatan_revisi,
                'ip_address' => request()->ip(),
            ]);

            $benPenUsers = User::role('Bendahara Pengeluaran')->get();
            if ($benPenUsers->isNotEmpty()) {
                Notification::send($benPenUsers, new WorkflowNotification([
                    'title' => 'SP2D Kontrak Dikembalikan',
                    'message' => "SP2D {$sp2d->nomor_sp2d} dikembalikan oleh PPK: {$request->catatan_revisi}",
                    'url' => route('sp2ds.kontrak.index'),
                    'icon' => 'reply',
                    'color' => 'warning',
                ]));
            }
        });

        return redirect()->route('verifikasi-ppk.sp2d.kontrak.show', $sp2d_id)
            ->with('success', 'SP2D dikembalikan untuk revisi.');
    }
}
