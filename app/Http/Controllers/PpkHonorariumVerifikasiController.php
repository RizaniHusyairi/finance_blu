<?php

namespace App\Http\Controllers;

use App\Models\Tagihan;
use App\Models\LogStatusDokumen;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class PpkHonorariumVerifikasiController extends Controller
{
    /**
     * Halaman antrean verifikasi Honorarium untuk PPK.
     */
    public function index(Request $request)
    {
        $tagihanQuery = Tagihan::with([
            'detailHonorarium',
            'workflowInstances.approvals.assignedUser',
            'workflowInstances.approvals.actedByUser',
        ])
        ->where('tipe_tagihan', 'HONORARIUM')
        ->whereHas('workflowInstances', fn ($q) => $q->whereIn('status', ['IN_PROGRESS', 'APPROVED', 'REVISION'])
            ->whereHas('definition', fn ($d) => $d->where('kode', 'TAGIHAN_HONORARIUM')))
        ->latest()
        ->get();

        $tagihanList = $tagihanQuery->map(function ($tagihan) {
            $latestInstance = $tagihan->workflowInstances->sortByDesc('created_at')->first();
            $approvals = collect($latestInstance?->approvals ?? []);

            $tagihan->_workflowInstance = $latestInstance;
            $tagihan->_ppkApproval = $approvals->firstWhere('role_code', 'PPK');
            $tagihan->_bendaharaApproval = $approvals->firstWhere('role_code', 'Bendahara Pengeluaran');

            $allApproved = $approvals->every(fn ($a) => $a->status === 'APPROVED') && $approvals->isNotEmpty();
            $anyRevision = $approvals->contains(fn ($a) => in_array($a->status, ['REVISION', 'REJECTED']));

            if ($allApproved) {
                $tagihan->_statusFinal = 'Selesai Diverifikasi';
            } elseif ($anyRevision) {
                $tagihan->_statusFinal = 'Perlu Revisi';
            } else {
                $pending = $approvals->where('status', 'PENDING');
                if ($pending->count() === $approvals->count()) {
                    $tagihan->_statusFinal = 'Menunggu Verifikasi Paralel';
                } else {
                    $pendingRoles = $pending->pluck('role_code')->map(fn ($role) => match($role) {
                        'PPK' => 'PPK',
                        'Bendahara Pengeluaran' => 'Bendahara Pengeluaran',
                        default => $role,
                    });
                    $tagihan->_statusFinal = 'Menunggu ' . $pendingRoles->join(' & ');
                }
            }

            return $tagihan;
        });

        // Filtering
        $filterPpk = $request->input('status_ppk', 'semua');
        $filterBendahara = $request->input('status_bendahara', 'semua');
        $search = $request->input('search');

        $filtered = $tagihanList;

        if ($filterPpk !== 'semua') {
            $filtered = $filtered->filter(fn ($tagihan) => $tagihan->_ppkApproval?->status === strtoupper($filterPpk));
        }
        if ($filterBendahara !== 'semua') {
            $filtered = $filtered->filter(fn ($tagihan) => $tagihan->_bendaharaApproval?->status === strtoupper($filterBendahara));
        }

        if ($search) {
            $s = strtolower($search);
            $filtered = $filtered->filter(function ($tagihan) use ($s) {
                return str_contains(strtolower($tagihan->nomor_tagihan ?? ''), $s)
                    || str_contains(strtolower($tagihan->deskripsi ?? ''), $s);
            });
        }

        $summary = [
            'pending' => $tagihanList->filter(fn ($n) => $n->_ppkApproval?->status === 'PENDING')->count(),
            'approved' => $tagihanList->filter(fn ($n) => $n->_ppkApproval?->status === 'APPROVED')->count(),
            'revision' => $tagihanList->filter(fn ($n) => in_array($n->_ppkApproval?->status, ['REVISION', 'REJECTED'])
                || $n->_workflowInstance?->status === 'REVISION')->count(),
            'selesai' => $tagihanList->filter(fn ($n) => $n->_statusFinal === 'Selesai Diverifikasi')->count(),
        ];

        return view('honorarium.verifikasi.ppk.index', [
            'tagihanList' => $filtered->values(),
            'summary' => $summary,
            'filterPpk' => $filterPpk,
            'filterBendahara' => $filterBendahara,
            'search' => $search,
            'currentRole' => 'PPK',
            'routePrefix' => 'verifikasi-ppk.honorarium',
        ]);
    }

    /**
     * Halaman detail verifikasi Honorarium untuk PPK.
     */
    public function show($id)
    {
        $tagihan = Tagihan::with([
            'detailHonorarium',
            'arsipDokumen',
            'workflowInstances.approvals.assignedUser',
            'workflowInstances.approvals.actedByUser',
            'logs.user',
        ])->where('tipe_tagihan', 'HONORARIUM')->findOrFail($id);

        $nominalTotal = (float) $tagihan->total_netto;

        // Workflow
        $activeWorkflowInstance = $tagihan->workflowInstances->sortByDesc('created_at')->first();
        $approvals = collect($activeWorkflowInstance?->approvals ?? []);

        $ppkApproval = $approvals->firstWhere('role_code', 'PPK');
        $bendaharaApproval = $approvals->firstWhere('role_code', 'Bendahara Pengeluaran');
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

        // Dokumen
        $daftarNominatif = $tagihan->arsipDokumen->firstWhere('jenis_dokumen', 'Daftar Nominatif Bertandatangan');
        $dokumenHonorarium = $tagihan->arsipDokumen->firstWhere('jenis_dokumen', 'Dokumen Honorarium Bertandatangan');
        $skHonorarium = $tagihan->arsipDokumen->firstWhere('jenis_dokumen', 'SK Honorarium');

        $documentStatuses = collect([
            ['key' => 'daftar_nominatif', 'label' => 'Daftar Nominatif', 'path' => $daftarNominatif?->id, 'required' => true],
            ['key' => 'dokumen_honorarium', 'label' => 'Dokumen Honorarium', 'path' => $dokumenHonorarium?->id, 'required' => true],
            ['key' => 'sk_honorarium', 'label' => 'SK Honorarium', 'path' => $skHonorarium?->id, 'required' => false],
        ])->map(function ($item) {
            $isAvailable = !empty($item['path']);
            $status = !$item['required'] ? 'not_required' : ($isAvailable ? 'ready' : 'missing');
            return array_merge($item, ['status' => $status, 'is_available' => $isAvailable]);
        })->values();

        return view('honorarium.verifikasi.ppk.show', [
            'tagihan' => $tagihan,
            'nominalTotal' => $nominalTotal,
            'activeWorkflowInstance' => $activeWorkflowInstance,
            'ppkApproval' => $ppkApproval,
            'bendaharaApproval' => $bendaharaApproval,
            'currentUserApproval' => $currentUserApproval,
            'canApprove' => $canApprove,
            'canRequestRevision' => $canRequestRevision,
            'statusFinal' => $statusFinal,
            'revisionNotes' => $revisionNotes,
            'documentStatuses' => $documentStatuses,
            'currentRole' => 'PPK',
            'routePrefix' => 'verifikasi-ppk.honorarium',
        ]);
    }

    /**
     * Approve Honorarium oleh PPK.
     */
    public function approve(Request $request, $id)
    {
        $tagihan = Tagihan::findOrFail($id);

        DB::transaction(function () use ($tagihan, $request) {
            $workflowService = app(WorkflowService::class);
            $instance = $workflowService->approveCurrentStep($tagihan, auth()->id(), $request->input('catatan'));

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => 'PPK',
                'status_sebelumnya' => $tagihan->status,
                'status_baru' => $instance->status === 'APPROVED' ? 'DISETUJUI' : $tagihan->status,
                'aksi' => 'APPROVE_PPK',
                'catatan' => $request->input('catatan', 'Honorarium disetujui PPK.'),
                'ip_address' => request()->ip(),
            ]);

            if ($instance->status === 'APPROVED') {
                $tagihan->update(['status' => 'DISETUJUI']);
            }
        });

        return redirect()->route('verifikasi-ppk.honorarium.show', $id)
            ->with('success', 'Honorarium berhasil disetujui.');
    }

    /**
     * Minta revisi Honorarium oleh PPK.
     */
    public function revisi(Request $request, $id)
    {
        $request->validate([
            'catatan_revisi' => 'required|string|max:1000',
        ]);

        $tagihan = Tagihan::findOrFail($id);

        DB::transaction(function () use ($tagihan, $request) {
            $workflowService = app(WorkflowService::class);
            $workflowService->requestRevision($tagihan, auth()->id(), $request->catatan_revisi);

            $tagihan->update(['status' => 'DRAFT']); // Kembali ke PPABP

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => 'PPK',
                'status_sebelumnya' => $tagihan->status,
                'status_baru' => 'DRAFT',
                'aksi' => 'REVISI_PPK',
                'catatan' => $request->catatan_revisi,
                'ip_address' => request()->ip(),
            ]);

            $ppabpUsers = User::role('PPABP')->get();
            if ($ppabpUsers->isNotEmpty()) {
                Notification::send($ppabpUsers, new WorkflowNotification([
                    'title' => 'Honorarium Dikembalikan',
                    'message' => "Honorarium {$tagihan->nomor_tagihan} dikembalikan oleh PPK: {$request->catatan_revisi}",
                    'url' => route('honorarium.index'),
                    'icon' => 'reply',
                    'color' => 'warning',
                ]));
            }
        });

        return redirect()->route('verifikasi-ppk.honorarium.show', $id)
            ->with('success', 'Honorarium dikembalikan untuk revisi.');
    }
}
