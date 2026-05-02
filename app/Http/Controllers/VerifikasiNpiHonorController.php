<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DokumenNpi;
use App\Models\LogStatusDokumen;
use App\Models\User;
use App\Services\WorkflowService;
use Illuminate\Support\Facades\DB;

class VerifikasiNpiHonorController extends Controller
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
        if ($user->hasRole('Bendahara Penerimaan')) return 'Bendahara Penerimaan';
        if ($user->hasRole('PPK')) return 'PPK';
        if ($user->hasRole('Kepala Subbagian Keuangan dan Tata Usaha')) return 'Kepala Subbagian Keuangan dan Tata Usaha';
        if ($user->hasRole('Koordinator Keuangan')) return 'Koordinator Keuangan';
        
        return '';
    }

    /**
     * Menampilkan daftar NPI Honorarium untuk diverifikasi
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $roleCode = $this->activeRoleCode($user);
        
        abort_unless($roleCode !== '', 403, 'Akses ditolak. Anda tidak diperkenankan.');

        // Hanya Dokumen NPI dari SPM Honorarium
        $query = DokumenNpi::with([
            'spm.spp.tagihan.detailHonorarium',
            'bendaharaPenerimaan',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals'
        ])
        ->whereHas('spm.spp.tagihan', function($q) {
            $q->where('tipe_tagihan', 'HONORARIUM');
        })
        ->whereNotIn('status', [DokumenNpi::STATUS_DRAFT]); 

        // Filter Spesifik Role
        if ($roleCode === 'Bendahara Penerimaan') {
            $query->where('bendahara_penerimaan_id', $user->id);
            $query->whereHas('workflowInstances.approvals', function($q) use ($roleCode, $user) {
                $q->where('role_code', $roleCode)->where(function($sq) use ($user) {
                    $sq->whereNull('assigned_user_id')->orWhere('assigned_user_id', $user->id);
                });
            });
        } elseif ($roleCode === 'PPK') {
            $query->whereHas('workflowInstances.approvals', function($q) use ($roleCode, $user) {
                $q->where('role_code', $roleCode)->where(function($sq) use ($user) {
                    $sq->whereNull('assigned_user_id')->orWhere('assigned_user_id', $user->id);
                });
            });
        } elseif (in_array($roleCode, ['Kepala Subbagian Keuangan dan Tata Usaha', 'Koordinator Keuangan'], true)) {
            $query->whereHas('workflowInstances.approvals', function($q) use ($roleCode) {
                $q->where('role_code', $roleCode);
            });
        }

        $allNpis = $query->latest()->get();

        $processed = collect();
        foreach ($allNpis as $npi) {
            $wf = $npi->workflowInstances->first();
            if (!$wf) continue;

            $myApproval = $wf->approvals->where('role_code', $roleCode)->first();
            
            if ($myApproval?->assigned_user_id && $myApproval->assigned_user_id !== $user->id) continue;

            $spm = $npi->spm;
            $spp = $spm?->spp;
            $npi->spmModel = $spm;
            $npi->sppModel = $spp;
            $npi->tagihanModel = $spp?->tagihan;
            $npi->nominal = $spm?->nominal_spm ?? 0;
            
            $npi->myApprovalStatus = $myApproval?->status ?? 'N/A';
            
            if ($wf->status === 'REVISION') {
                $npi->statusFinal = 'Perlu Revisi';
            } elseif ($wf->status === 'APPROVED') {
                $npi->statusFinal = 'Selesai';
            } else {
                $npi->statusFinal = $npi->status; 
            }

            $npi->canAct = (
                $myApproval
                && $myApproval->status === 'PENDING'
                && $wf->status === 'IN_PROGRESS'
                && (int) $wf->step_saat_ini === (int) $myApproval->urutan_step 
            );

            $npi->workflow = $wf;
            $processed->push($npi);
        }

        // Apply Status Filter
        $statusFilter = $request->input('status', 'semua');
        $viewNpis = $processed;
        if ($statusFilter !== 'semua') {
            $viewNpis = match ($statusFilter) {
                'pending'  => $viewNpis->where('myApprovalStatus', 'PENDING')->where('statusFinal', '!=', 'Perlu Revisi'),
                'approved' => $viewNpis->where('myApprovalStatus', 'APPROVED'),
                'revisi'   => $viewNpis->where('myApprovalStatus', 'REVISION'),
                'selesai'  => $viewNpis->where('statusFinal', 'Selesai'),
                default    => $viewNpis,
            };
        }

        // Apply Search
        $search = $request->input('search');
        if ($search) {
            $viewNpis = $viewNpis->filter(function($item) use ($search) {
                return str_contains(strtolower($item->nomor_npi), strtolower($search)) || 
                       str_contains(strtolower($item->spmModel?->nomor_spm), strtolower($search)) ||
                       str_contains(strtolower($item->sppModel?->nomor_spp), strtolower($search)) ||
                       str_contains(strtolower($item->tagihanModel?->nomor_tagihan), strtolower($search)) ||
                       str_contains(strtolower($item->tagihanModel?->deskripsi), strtolower($search));
            });
        }

        $summary = [
            'pending'  => $processed->where('myApprovalStatus', 'PENDING')->where('statusFinal', '!=', 'Perlu Revisi')->count(),
            'approved' => $processed->where('myApprovalStatus', 'APPROVED')->count(),
            'revisi'   => $processed->where('myApprovalStatus', 'REVISION')->count(),
            'selesai'  => $processed->where('statusFinal', 'Selesai')->count(),
        ];

        return view('verifikasi_npi_honor.index', compact(
            'viewNpis', 'summary', 'statusFilter', 'search', 'roleCode', 'user'
        ));
    }

    /**
     * Halaman Detail Workspace
     */
    public function show($id, Request $request)
    {
        $user = $request->user();
        $roleCode = $this->activeRoleCode($user);
        abort_unless($roleCode !== '', 403, 'Akses ditolak.');

        $npi = DokumenNpi::with([
            'spm.spp.tagihan.detailHonorarium',
            'spm.spp.tagihan.arsipDokumen',
            'bendaharaPenerimaan',
            'logs.user',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals.actedByUser',
            'workflowInstances.approvals.assignedUser'
        ])
        ->whereHas('spm.spp.tagihan', function($q) {
            $q->where('tipe_tagihan', 'HONORARIUM');
        })
        ->findOrFail($id);

        $wf = $npi->workflowInstances->first();
        $myApproval = collect($wf?->approvals ?? [])->firstWhere('role_code', $roleCode);

        // Security check assignment
        if ($myApproval?->assigned_user_id && $myApproval->assigned_user_id !== $user->id) {
            abort(403, 'Anda tidak ditugaskan untuk memverifikasi dokumen ini.');
        }

        $canVerify = (
            $myApproval
            && $myApproval->status === 'PENDING'
            && $wf->status === 'IN_PROGRESS'
            && (int) $wf->step_saat_ini === (int) $myApproval->urutan_step 
        );

        $spm = $npi->spm;
        $spp = $spm?->spp;
        $tagihan = $spp?->tagihan;
        
        // Paralel Verificators tracking
        $benpenApproval = collect($wf?->approvals ?? [])->firstWhere('role_code', 'Bendahara Penerimaan');
        $ppkApproval = collect($wf?->approvals ?? [])->firstWhere('role_code', 'PPK');
        $kasubbagApproval = collect($wf?->approvals ?? [])->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');

        // Logic display readiness
        $rekeningBermasalah = count(array_filter($tagihan->detailHonorarium->toArray(), fn($p) => empty($p['rekening']) || empty($p['nama_rekening'])));
        
        $checklist = collect([
            ['label' => 'Status SPM Awal', 'status' => 'ready', 'message' => 'Telah Disetujui Final'],
            ['label' => 'Kesehatan Rekening (-' . $rekeningBermasalah . ')', 'status' => $rekeningBermasalah === 0 ? 'ready' : 'missing', 'message' => $rekeningBermasalah > 0 ? "Terdapat entitas tanpa rekening." : 'Rekening komplit'],
            ['label' => 'Tujuan Penerimaan NPI', 'status' => filled($npi->bendahara_penerimaan_id) ? 'ready' : 'missing', 'message' => filled($npi->bendahara_penerimaan_id) ? 'Bendahara tervalidasi.' : 'Penunjukan rumpang.'],
        ])->values();

        $recentLogs = $npi->logs()->latest()->take(5)->get();

        return view('verifikasi_npi_honor.detail', compact(
            'npi', 'spm', 'spp', 'tagihan', 'wf', 'myApproval', 'canVerify',
            'benpenApproval', 'ppkApproval', 'kasubbagApproval', 'checklist', 'roleCode', 'recentLogs'
        ));
    }

    /**
     * Handle approval Workflow
     */
    public function approve(Request $request, $id)
    {
        $user = $request->user();
        $roleCode = $this->activeRoleCode($user);
        abort_unless($roleCode !== '', 403, 'Akses ditolak.');

        $npi = DokumenNpi::with('workflowInstances')->findOrFail($id);
        $wf = $npi->workflowInstances->first();

        try {
            DB::beginTransaction();

            $this->workflowService->approveCurrentStep($npi, $user->id, $request->input('catatan'));
            $wf->refresh();
            $isFinished = $wf->status === 'APPROVED';

            if ($isFinished) {
                $npi->update(['status' => DokumenNpi::STATUS_DISETUJUI_FINAL]);
                LogStatusDokumen::create([
                    'dokumen_type' => DokumenNpi::class,
                    'dokumen_id' => $npi->id,
                    'user_id' => $user->id,
                    'role_saat_itu' => 'Sistem Verifikasi',
                    'status_baru' => DokumenNpi::STATUS_DISETUJUI_FINAL,
                    'aksi' => 'NPI_FINAL_APPROVED',
                    'catatan' => 'Form NPI Honorarium disetujui secara mufakat dan berstatus Rilis (Terbit).',
                    'ip_address' => request()->ip()
                ]);
            }

            LogStatusDokumen::create([
                'dokumen_type' => DokumenNpi::class,
                'dokumen_id' => $npi->id,
                'user_id' => $user->id,
                'role_saat_itu' => $roleCode,
                'status_baru' => $npi->status,
                'aksi' => 'APPROVE_VERIFIKASI_NPI_HONOR',
                'catatan' => "Tindakan Verifikasi {$roleCode} - Setuju. " . $request->input('catatan'),
                'ip_address' => request()->ip()
            ]);

            DB::commit();
            return redirect()->route('verifikasi-npi.honor.detail', $npi->id)->with('success', 'Verifikasi Setuju NPI Honorarium berhasil.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal memverifikasi: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle penolakan revisi
     */
    public function reject(Request $request, $id)
    {
        $request->validate(['catatan' => 'required|string|min:5']);

        $user = $request->user();
        $roleCode = $this->activeRoleCode($user);
        abort_unless($roleCode !== '', 403, 'Akses ditolak.');

        $npi = DokumenNpi::with('workflowInstances')->findOrFail($id);
        $wf = $npi->workflowInstances->first();

        try {
            DB::beginTransaction();

            $this->workflowService->requestRevision($npi, $user->id, $request->input('catatan'));

            $npi->update(['status' => DokumenNpi::STATUS_REVISI]);

            LogStatusDokumen::create([
                'dokumen_type' => DokumenNpi::class,
                'dokumen_id' => $npi->id,
                'user_id' => $user->id,
                'role_saat_itu' => $roleCode,
                'status_baru' => DokumenNpi::STATUS_REVISI,
                'aksi' => 'REVISI_VERIFIKASI_NPI_HONOR',
                'catatan' => "Menggagalkan persetujuan - Membutuhkan Revisi NPI: " . $request->catatan,
                'ip_address' => request()->ip()
            ]);

            DB::commit();
            return redirect()->route('verifikasi-npi.honor.detail', $npi->id)->with('success', 'NPI Honorarium diblokir dan dikembalikan divalidasi ke meja draft Revisi.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal mengembalikan berkas (revisi): ' . $e->getMessage()]);
        }
    }
}
