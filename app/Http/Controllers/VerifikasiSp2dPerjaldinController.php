<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DokumenSp2d;
use App\Models\DokumenNpi;
use App\Models\LogStatusDokumen;
use App\Models\User;
use App\Services\WorkflowService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VerifikasiSp2dPerjaldinController extends Controller
{
    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Helper to detect which role the controller should act as for the currently logged in user 
     */
    private function activeRoleCode(User $user): string
    {
        if ($user->hasRole('PPK')) return 'PPK';
        if ($user->hasRole('Kepala Subbagian Keuangan dan Tata Usaha')) return 'Kepala Subbagian Keuangan dan Tata Usaha';
        if ($user->hasRole('Koordinator Keuangan')) return 'Koordinator Keuangan';
        
        return '';
    }

    /**
     * Daftar SP2D Perjaldin yang butuh verifikasi (Paralel)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $roleCode = $this->activeRoleCode($user);
        
        abort_unless($roleCode !== '', 403, 'Akses ditolak. Anda tidak memiliki peran verifikator yang valid.');

        $query = DokumenSp2d::with([
            'npi.spm.spp.tagihan.detailPerjaldin.pegawai',
            'npi.spm.spp.tagihan.detailPerjaldin.provinsi',
            'bendaharaPengeluaran',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals'
        ])
        ->whereHas('npi.spm.spp', function($q) {
            $q->whereNotNull('tagihan_perjaldin_komponen_id');
        })
        ->whereNotIn('status', [DokumenSp2d::STATUS_DRAFT]); 

        // Filter Spesifik Role
        if (in_array($roleCode, ['PPK', 'Kepala Subbagian Keuangan dan Tata Usaha', 'Koordinator Keuangan'], true)) {
            $query->whereHas('workflowInstances.approvals', function($q) use ($roleCode, $user) {
                $q->where('role_code', $roleCode)->where(function($sq) use ($user) {
                    $sq->whereNull('assigned_user_id')->orWhere('assigned_user_id', $user->id);
                });
            });
        }

        $allSp2ds = $query->latest()->get();

        $processed = collect();
        foreach ($allSp2ds as $sp2d) {
            $wf = $sp2d->workflowInstances->first();
            if (!$wf) continue;

            $myApproval = $wf->approvals->where('role_code', $roleCode)->first();
            if ($myApproval?->assigned_user_id && $myApproval->assigned_user_id !== $user->id) {
                continue;
            }

            $sp2d->npiModel = $sp2d->npi;
            $sp2d->spmModel = $sp2d->npi?->spm;
            $sp2d->sppModel = $sp2d->npi?->spm?->spp;
            $sp2d->tagihanModel = $sp2d->sppModel?->tagihan;
            $sp2d->nominal = $sp2d->nilai_sp2d ?? ($sp2d->spmModel?->nominal_spm ?? 0);
            
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
                'revisi'   => $viewSp2ds->where('statusFinal', 'Perlu Revisi'),
                'selesai'  => $viewSp2ds->where('statusFinal', 'Selesai'),
                default    => $viewSp2ds,
            };
        }

        $search = $request->input('search');
        if ($search) {
            $viewSp2ds = $viewSp2ds->filter(function($item) use ($search) {
                $s = strtolower($search);
                return str_contains(strtolower($item->nomor_sp2d), $s) || 
                       str_contains(strtolower($item->npiModel?->nomor_npi), $s) ||
                       str_contains(strtolower($item->spmModel?->nomor_spm), $s) ||
                       str_contains(strtolower($item->sppModel?->nomor_spp), $s) ||
                       str_contains(strtolower($item->tagihanModel?->nomor_tagihan), $s) ||
                       str_contains(strtolower($item->tagihanModel?->deskripsi), $s);
            });
        }

        $summary = [
            'pending'  => $processed->where('myApprovalStatus', 'PENDING')->where('statusFinal', '!=', 'Perlu Revisi')->count(),
            'approved' => $processed->where('myApprovalStatus', 'APPROVED')->count(),
            'revisi'   => $processed->where('statusFinal', 'Perlu Revisi')->count(),
            'selesai'  => $processed->where('statusFinal', 'Selesai')->count(),
        ];

        return view('verifikasi_sp2d_perjaldin.index', compact('viewSp2ds', 'summary', 'statusFilter', 'search', 'roleCode'));
    }

    /**
     * Halaman Detail Workspace SP2D Perjaldin untuk Verifikator
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $roleCode = $this->activeRoleCode($user);
        abort_unless($roleCode !== '', 403, 'Akses ditolak.');

        $sp2d = DokumenSp2d::with([
            'npi.spm.spp.tagihan.detailPerjaldin.pegawai',
            'npi.spm.spp.tagihan.detailPerjaldin.provinsi',
            'npi.spm.spp.tagihan.komponenPerjaldin.dipaRevisionItem.coa',
            'npi.bendaharaPenerimaan',
            'bendaharaPengeluaran',
            'logs.user',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals.actedByUser',
            'workflowInstances.approvals.assignedUser'
        ])
        ->whereHas('npi.spm.spp', function($q) {
            $q->whereNotNull('tagihan_perjaldin_komponen_id');
        })
        ->findOrFail($id);

        $wf = $sp2d->workflowInstances->first();
        abort_unless($wf, 404, 'Workflow tidak ditemukan untuk SP2D ini.');

        $myApproval = $wf->approvals->where('role_code', $roleCode)->first();
        $canAct = (
            $myApproval
            && $myApproval->status === 'PENDING'
            && $wf->status === 'IN_PROGRESS'
            && (int) $wf->step_saat_ini === (int) $myApproval->urutan_step
            && (!$myApproval->assigned_user_id || $myApproval->assigned_user_id === $user->id)
        );

        $npi = $sp2d->npi;
        $spm = $npi?->spm;
        $spp = $spm?->spp;
        $tagihan = $spp?->tagihan;
        $komponen = $spp?->tagihanPerjaldinKomponen;

        $checks = [
            'sp2d_diajukan'   => in_array($sp2d->status, [DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI, DokumenSp2d::STATUS_DISETUJUI_FINAL, DokumenSp2d::STATUS_REVISI, DokumenSp2d::STATUS_EXECUTED]),
            'npi_tersedia'    => !is_null($npi),
            'spm_tersedia'    => !is_null($spm),
            'spp_tersedia'    => !is_null($spp),
            'tagihan_ada'     => !is_null($tagihan),
            'peserta_ada'     => $tagihan && $tagihan->detailPerjaldin->count() > 0,
            'sp2d_valid'      => !empty($sp2d->nomor_sp2d) && !empty($sp2d->tanggal_sp2d)
        ];

        return view('verifikasi_sp2d_perjaldin.show', compact(
            'sp2d', 'npi', 'spm', 'spp', 'tagihan', 'komponen',
            'wf', 'roleCode', 'canAct', 'checks'
        ));
    }

    /**
     * Setujui SP2D Perjaldin
     */
    public function approve(Request $request, $id)
    {
        $user = $request->user();
        $roleCode = $this->activeRoleCode($user);
        abort_unless($roleCode !== '', 403, 'Akses ditolak.');

        $sp2d = DokumenSp2d::with(['workflowInstances' => fn($q) => $q->latest()->limit(1)])->findOrFail($id);
        $wf = $sp2d->workflowInstances->first();

        // Security check
        $myApproval = $wf?->approvals->where('role_code', $roleCode)->where('status', 'PENDING')->first();
        if (!$myApproval && $wf?->status === 'IN_PROGRESS') {
            return back()->with('error', 'Anda tidak memiliki hak akses atau tindakan ini sudah diselesaikan sebelumnya.');
        }

        DB::beginTransaction();
        try {
            $catatan = $request->input('catatan');

            // Setujui step workflow (Engine WorkflowService automatically marks it as acted)
            $this->workflowService->approveCurrentStep($sp2d, $user->id, $catatan, null, $roleCode);

            // Fetch fresh status to see if entirely approved
            $wf->refresh();
            if ($wf->status === 'APPROVED') {
                $sp2d->update(['status' => DokumenSp2d::STATUS_DISETUJUI_FINAL]);
            }

            LogStatusDokumen::create([
                'dokumen_type'      => DokumenSp2d::class,
                'dokumen_id'        => $sp2d->id,
                'user_id'           => $user->id,
                'role_saat_itu'     => $roleCode,
                'status_sebelumnya' => $sp2d->status,
                'status_baru'       => $sp2d->status,
                'aksi'              => 'VERIFIKUSI_APPROVE',
                'catatan'           => $roleCode . ' menyetujui SP2D Perjaldin' . ($catatan ? ": $catatan" : ''),
                'ip_address'        => $request->ip(),
            ]);

            DB::commit();
            return back()->with('success', 'Berhasil menyetujui dokumen SP2D Perjaldin.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses persetujuan: ' . $e->getMessage());
        }
    }

    /**
     * Kembalikan / Revisi SP2D Perjaldin
     */
    public function reject(Request $request, $id)
    {
        $user = $request->user();
        $roleCode = $this->activeRoleCode($user);
        abort_unless($roleCode !== '', 403, 'Akses ditolak.');

        $request->validate([
            'catatan' => 'required|string|min:5'
        ], [
            'catatan.required' => 'Catatan revisi wajib diisi agar pembuat dokumen tahu apa yang salah.'
        ]);

        $sp2d = DokumenSp2d::with(['workflowInstances' => fn($q) => $q->latest()->limit(1)])->findOrFail($id);
        $wf = $sp2d->workflowInstances->first();

        $myApproval = $wf?->approvals->where('role_code', $roleCode)->where('status', 'PENDING')->first();
        if (!$myApproval && $wf?->status === 'IN_PROGRESS') {
            return back()->with('error', 'Tindakan ini sudah ditangani.');
        }

        DB::beginTransaction();
        try {
            $catatan = $request->input('catatan');

            // Workflow engine transitions status to REVISION
            $this->workflowService->rejectCurrentStep($sp2d, $user->id, $catatan, $roleCode);

            $statusSblm = $sp2d->status;
            $sp2d->update(['status' => DokumenSp2d::STATUS_REVISI]);

            LogStatusDokumen::create([
                'dokumen_type'      => DokumenSp2d::class,
                'dokumen_id'        => $sp2d->id,
                'user_id'           => $user->id,
                'role_saat_itu'     => $roleCode,
                'status_sebelumnya' => $statusSblm,
                'status_baru'       => DokumenSp2d::STATUS_REVISI,
                'aksi'              => 'VERIFIKUSI_REJECT',
                'catatan'           => $roleCode . ' meminta revisi SP2D Perjaldin: ' . $catatan,
                'ip_address'        => $request->ip(),
            ]);

            DB::commit();
            return back()->with('success', 'Dokumen SP2D Perjaldin berhasil dikembalikan untuk revisi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membungkus permintaan revisi: ' . $e->getMessage());
        }
    }
}
