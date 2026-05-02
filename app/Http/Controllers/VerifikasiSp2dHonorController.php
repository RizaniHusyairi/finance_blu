<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DokumenSp2d;
use App\Models\LogStatusDokumen;
use App\Models\User;
use App\Services\WorkflowService;
use Illuminate\Support\Facades\DB;

class VerifikasiSp2dHonorController extends Controller
{
    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Identifikasi role pengguna.
     */
    private function activeRoleCode(User $user): string
    {
        if ($user->hasRole('PPK')) return 'PPK';
        if ($user->hasRole('Kepala Subbagian Keuangan dan Tata Usaha')) return 'Kepala Subbagian Keuangan dan Tata Usaha';
        if ($user->hasRole('Koordinator Keuangan')) return 'Koordinator Keuangan';
        
        return '';
    }

    /**
     * Menampilkan daftar SP2D Honorarium untuk diverifikasi
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $roleCode = $this->activeRoleCode($user);
        
        abort_unless($roleCode !== '', 403, 'Akses ditolak. Anda tidak difungsikan untuk mengakses modul ini.');

        $query = DokumenSp2d::with([
            'npi.spm.spp.tagihan.detailHonorarium',
            'bendaharaPengeluaran',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals'
        ])
        ->whereHas('npi.spm.spp.tagihan', function($q) {
            $q->where('tipe_tagihan', 'HONORARIUM');
        })
        ->whereNotIn('status', [DokumenSp2d::STATUS_DRAFT, 'BELUM_ADA']);

        // Filter Spesifik Role berdasarkan Penugasan Workflow
        $query->whereHas('workflowInstances.approvals', function($q) use ($roleCode, $user) {
            $q->where('role_code', $roleCode)->where(function($sq) use ($user) {
                $sq->whereNull('assigned_user_id')->orWhere('assigned_user_id', $user->id);
            });
        });

        $allSp2d = $query->latest()->get();

        $processed = collect();
        foreach ($allSp2d as $sp2d) {
            $wf = $sp2d->workflowInstances->first();
            if (!$wf) continue;

            $myApproval = collect($wf->approvals)->where('role_code', $roleCode)->first();
            
            if ($myApproval?->assigned_user_id && $myApproval->assigned_user_id !== $user->id) continue;

            $npi = $sp2d->npi;
            $spm = $npi?->spm;
            $spp = $spm?->spp;
            
            $sp2d->npiModel = $npi;
            $sp2d->spmModel = $spm;
            $sp2d->sppModel = $spp;
            $sp2d->tagihanModel = $spp?->tagihan;
            $sp2d->nominal = $spm?->nominal_spm ?? $spp?->tagihan?->total_netto ?? 0;
            
            $sp2d->myApprovalStatus = $myApproval?->status ?? 'N/A';
            
            if ($wf->status === 'REVISION') {
                $sp2d->statusFinal = 'Perlu Revisi';
            } elseif ($wf->status === 'APPROVED') {
                $sp2d->statusFinal = 'Selesai';
            } else {
                $sp2d->statusFinal = $sp2d->status; 
            }

            $sp2d->canAct = (
                $myApproval
                && $myApproval->status === 'PENDING'
                && $wf->status === 'IN_PROGRESS'
                && (int) $wf->step_saat_ini === (int) $myApproval->urutan_step 
            );

            $sp2d->workflow = $wf;
            $processed->push($sp2d);
        }

        $statusFilter = $request->input('status', 'semua');
        $viewSp2ds = $processed;
        
        if ($statusFilter !== 'semua') {
            $viewSp2ds = match ($statusFilter) {
                'pending'  => $viewSp2ds->where('myApprovalStatus', 'PENDING')->where('statusFinal', '!=', 'Perlu Revisi'),
                'approved' => $viewSp2ds->where('myApprovalStatus', 'APPROVED'),
                'revisi'   => $viewSp2ds->where('myApprovalStatus', 'REVISION'),
                'selesai'  => $viewSp2ds->where('statusFinal', 'Selesai'),
                default    => $viewSp2ds,
            };
        }

        $search = $request->input('search');
        if ($search) {
            $viewSp2ds = $viewSp2ds->filter(function($item) use ($search) {
                return str_contains(strtolower($item->nomor_sp2d), strtolower($search)) || 
                       str_contains(strtolower($item->npiModel?->nomor_npi), strtolower($search)) ||
                       str_contains(strtolower($item->spmModel?->nomor_spm), strtolower($search)) ||
                       str_contains(strtolower($item->sppModel?->nomor_spp), strtolower($search)) ||
                       str_contains(strtolower($item->tagihanModel?->deskripsi), strtolower($search));
            });
        }

        $summary = [
            'pending'  => $processed->where('myApprovalStatus', 'PENDING')->where('statusFinal', '!=', 'Perlu Revisi')->count(),
            'approved' => $processed->where('myApprovalStatus', 'APPROVED')->count(),
            'revisi'   => $processed->where('myApprovalStatus', 'REVISION')->count(),
            'selesai'  => $processed->where('statusFinal', 'Selesai')->count(),
        ];

        return view('verifikasi_sp2d_honor.index', compact(
            'viewSp2ds', 'summary', 'statusFilter', 'search', 'roleCode', 'user'
        ));
    }

    /**
     * Halaman Detail Workspace Verifikasi SP2D Honor
     */
    public function show($id, Request $request)
    {
        $user = $request->user();
        $roleCode = $this->activeRoleCode($user);
        abort_unless($roleCode !== '', 403, 'Akses terlarang.');

        $sp2d = DokumenSp2d::with([
            'npi.spm.spp.tagihan.detailHonorarium',
            'npi.spm.spp.tagihan.arsipDokumen',
            'bendaharaPengeluaran',
            'logs.user',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals.actedByUser',
            'workflowInstances.approvals.assignedUser'
        ])
        ->whereHas('npi.spm.spp.tagihan', function($q) {
            $q->where('tipe_tagihan', 'HONORARIUM');
        })
        ->findOrFail($id);

        $wf = $sp2d->workflowInstances->first();
        $myApproval = collect($wf?->approvals ?? [])->firstWhere('role_code', $roleCode);

        if ($myApproval?->assigned_user_id && $myApproval->assigned_user_id !== $user->id) {
            abort(403, 'Privilege Invalid. Identitas Anda tidak ditugaskan untuk dokumen pencairan ini.');
        }

        $canVerify = (
            $myApproval
            && $myApproval->status === 'PENDING'
            && $wf->status === 'IN_PROGRESS'
            && (int) $wf->step_saat_ini === (int) $myApproval->urutan_step 
        );

        $npi = $sp2d->npi;
        $spm = $npi?->spm;
        $spp = $spm?->spp;
        $tagihan = $spp?->tagihan;
        
        $ppkApproval = collect($wf?->approvals ?? [])->firstWhere('role_code', 'PPK');
        $kasubbagApproval = collect($wf?->approvals ?? [])->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');

        $rekeningBermasalah = count(array_filter($tagihan?->detailHonorarium->toArray() ?? [], fn($p) => empty($p['rekening']) || empty($p['nama_rekening'])));
        
        $checklist = collect([
            ['label' => 'Substansi Form SP2D', 'status' => filled($sp2d->nomor_sp2d) ? 'ready' : 'missing', 'message' => filled($sp2d->nomor_sp2d) ? 'Draf Tervalidasi' : 'Draf Kosong!'],
            ['label' => 'Akar Hierarki NPI', 'status' => 'ready', 'message' => 'Lengkap (NPI - SPP)'],
            ['label' => 'Kesehatan Rekening (-' . $rekeningBermasalah . ')', 'status' => $rekeningBermasalah === 0 ? 'ready' : 'missing', 'message' => $rekeningBermasalah > 0 ? "Terdapat anomali kosong" : 'Bank Clear'],
        ])->values();

        $recentLogs = $sp2d->logs()->latest()->take(5)->get();

        return view('verifikasi_sp2d_honor.detail', compact(
            'sp2d', 'npi', 'spm', 'spp', 'tagihan', 'wf', 'myApproval', 'canVerify',
            'ppkApproval', 'kasubbagApproval', 'checklist', 'roleCode', 'recentLogs'
        ));
    }

    /**
     * Handle Approve Workflow
     */
    public function approve(Request $request, $id)
    {
        $user = $request->user();
        $roleCode = $this->activeRoleCode($user);
        abort_unless($roleCode !== '', 403, 'Akses terlarang.');

        $sp2d = DokumenSp2d::with('workflowInstances')->findOrFail($id);
        $wf = $sp2d->workflowInstances->first();

        try {
            DB::beginTransaction();

            $this->workflowService->approveCurrentStep($sp2d, $user->id, $request->input('catatan'));
            $wf->refresh();
            $isFinished = $wf->status === 'APPROVED';

            if ($isFinished) {
                // Semua Setuju
                $sp2d->update(['status' => DokumenSp2d::STATUS_DISETUJUI_FINAL]);
                LogStatusDokumen::create([
                    'dokumen_type' => DokumenSp2d::class,
                    'dokumen_id' => $sp2d->id,
                    'user_id' => $user->id,
                    'role_saat_itu' => 'Sistem Verifikasi',
                    'status_baru' => DokumenSp2d::STATUS_DISETUJUI_FINAL,
                    'aksi' => 'SP2D_FINAL_APPROVED',
                    'catatan' => 'Verifikasi Kasubbag & PPK telah utuh menyetujui. Siap dieksekusi Final.',
                    'ip_address' => request()->ip()
                ]);
            }

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSp2d::class,
                'dokumen_id' => $sp2d->id,
                'user_id' => $user->id,
                'role_saat_itu' => $roleCode,
                'status_baru' => $sp2d->status,
                'aksi' => 'APPROVE_VERIFIKASI_SP2D_HONOR',
                'catatan' => "Melewati pemeriksaan validasi {$roleCode} - Setuju. " . $request->input('catatan'),
                'ip_address' => request()->ip()
            ]);

            DB::commit();
            return redirect()->route('verifikasi-sp2d.honor.detail', $sp2d->id)->with('success', 'Persetejuan SP2D sukses diafirmasi!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal mengesahkan pemicu: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle Reject (Revisi) SP2D 
     */
    public function reject(Request $request, $id)
    {
        $request->validate(['catatan' => 'required|string|min:5']);

        $user = $request->user();
        $roleCode = $this->activeRoleCode($user);
        abort_unless($roleCode !== '', 403, 'Akses terlarang.');

        $sp2d = DokumenSp2d::with('workflowInstances')->findOrFail($id);
        $wf = $sp2d->workflowInstances->first();

        try {
            DB::beginTransaction();

            $this->workflowService->requestRevision($sp2d, $user->id, $request->input('catatan'));

            $sp2d->update(['status' => DokumenSp2d::STATUS_REVISI]);

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSp2d::class,
                'dokumen_id' => $sp2d->id,
                'user_id' => $user->id,
                'role_saat_itu' => $roleCode,
                'status_baru' => DokumenSp2d::STATUS_REVISI,
                'aksi' => 'REVISI_VERIFIKASI_SP2D_HONOR',
                'catatan' => "Dokumen Dipulangkan Oleh Verifikator Beralasan: " . $request->catatan,
                'ip_address' => request()->ip()
            ]);

            DB::commit();
            return redirect()->route('verifikasi-sp2d.honor.detail', $sp2d->id)->with('success', 'Valid! SP2D Honorarium dicegah dan dituruni revisi ke Bendahara.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Bencana gagal tolak: ' . $e->getMessage()]);
        }
    }
}
