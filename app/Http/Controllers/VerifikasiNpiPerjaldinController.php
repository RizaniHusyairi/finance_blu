<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DokumenNpi;
use App\Models\DokumenSpm;
use App\Models\LogStatusDokumen;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\WorkflowService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class VerifikasiNpiPerjaldinController extends Controller
{
    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Helper to detect which role the controller should act as for the currently logged in user 
     * in the context of Verifikasi NPI.
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
     * Helper to map Role Code to a display-friendly label/badge format
     */
    private function roleLabel(string $roleCode): string
    {
        return match($roleCode) {
            'Bendahara Penerimaan' => 'Bendahara Penerimaan',
            'PPK' => 'Pejabat Pembuat Komitmen',
            'Kepala Subbagian Keuangan dan Tata Usaha' => 'Kasubbag Keuangan & TU',
            'Koordinator Keuangan' => 'Koordinator Keuangan',
            default => 'Unknown'
        };
    }

    /**
     * Daftar NPI Perjaldin yang butuh verifikasi
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $roleCode = $this->activeRoleCode($user);
        
        abort_unless($roleCode !== '', 403, 'Akses ditolak. Anda tidak memiliki peran verifikator yang valid.');

        // 1. Tampilkan hanya Dokumen NPI dari SPM yang memiliki komponen perjaldin
        $query = DokumenNpi::with([
            'spm.spp.tagihan.detailPerjaldin.pegawai',
            'spm.spp.tagihan.detailPerjaldin.provinsi',
            'spm.spp.tagihan.komponenPerjaldin',
            'bendaharaPenerimaan',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals'
        ])
        ->whereHas('spm.spp', function($q) {
            $q->whereNotNull('tagihan_perjaldin_komponen_id');
        })
        ->whereNotIn('status', [DokumenNpi::STATUS_DRAFT]); // Jangan tampilkan draft bendahara pengeluaran yang belum di-submit

        // Filter Spesifik Role
        if ($roleCode === 'Bendahara Penerimaan') {
            // NPI ditujukan ke Bendahara Penerimaan ini
            $query->where('bendahara_penerimaan_id', $user->id);
            // Workflow approval filter untuk Bendahara Penerimaan is implicit if it's assigned to them, 
            // but we can enforce it.
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

        // Data Processed untuk Table dengan flag "is my action required"
        $processed = collect();
        foreach ($allNpis as $npi) {
            $wf = $npi->workflowInstances->first();
            if (!$wf) continue;

            $myApproval = $wf->approvals->where('role_code', $roleCode)->first();
            
            // Re-check assignment just in case
            if ($myApproval?->assigned_user_id && $myApproval->assigned_user_id !== $user->id) {
                continue;
            }

            $spm = $npi->spm;
            $spp = $spm?->spp;
            $npi->spmModel = $spm;
            $npi->sppModel = $spp;
            $npi->tagihanModel = $spp?->tagihan;
            $npi->nominal = $spm?->nominal_spm ?? 0;
            
            $npi->myApprovalStatus = $myApproval?->status ?? 'N/A';
            
            // Compute combined status for display
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
                && (int) $wf->step_saat_ini === (int) $myApproval->urutan_step // For parallel this is usually true if they are on same step
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

        // Apply Search Filter
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

        return view('verifikasi_npi_perjaldin.index', compact(
            'viewNpis', 'summary', 'statusFilter', 'search', 'roleCode', 'user'
        ));
    }

    /**
     * Halaman Detail Workspace NPI Perjaldin
     */
    public function show($id, Request $request)
    {
        $user = $request->user();
        $roleCode = $this->activeRoleCode($user);
        abort_unless($roleCode !== '', 403, 'Akses ditolak.');

        $npi = DokumenNpi::with([
            'spm.spp.tagihan.detailPerjaldin.pegawai',
            'spm.spp.tagihan.detailPerjaldin.provinsi',
            'spm.spp.tagihan.komponenPerjaldin',
            'spm.spp.tagihanPerjaldinKomponen.dipaRevisionItem.coa',
            'bendaharaPenerimaan',
            'logs.user',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals.actedByUser',
            'workflowInstances.approvals.assignedUser'
        ])
        ->whereHas('spm.spp', function($q) {
            $q->whereNotNull('tagihan_perjaldin_komponen_id');
        })
        ->findOrFail($id);

        $wf = $npi->workflowInstances->first();
        abort_unless($wf, 404, 'Workflow tidak ditemukan untuk dokumen NPI ini.');

        // Cek otorisasi detail page (Apakah ada approval line utk user ini)
        $myApproval = $wf->approvals->where('role_code', $roleCode)->first();
        if ($myApproval?->assigned_user_id && $myApproval->assigned_user_id !== $user->id) {
            abort(403, 'Anda tidak berhak melihat Dokumen NPI ini.');
        }
        if ($roleCode === 'Bendahara Penerimaan' && (int) $npi->bendahara_penerimaan_id !== (int) $user->id) {
            abort(403, 'NPI ini bukan tugas Bendahara Penerimaan Anda.');
        }

        $canAct = (
            $myApproval
            && $myApproval->status === 'PENDING'
            && $wf->status === 'IN_PROGRESS'
            && (int) $wf->step_saat_ini === (int) $myApproval->urutan_step
        );

        $spm = $npi->spm;
        $spp = $spm?->spp;
        $tagihan = $spp?->tagihan;
        $komponen = $spp?->tagihanPerjaldinKomponen;

        $approverBenpen = $wf->approvals->where('role_code', 'Bendahara Penerimaan')->first();
        $approverPpk = $wf->approvals->where('role_code', 'PPK')->first();
        $approverKasubbag = $wf->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->first();

        return view('verifikasi_npi_perjaldin.show', compact(
            'npi', 'spm', 'spp', 'tagihan', 'komponen', 'wf',
            'myApproval', 'canAct', 'roleCode',
            'approverBenpen', 'approverPpk', 'approverKasubbag'
        ));
    }

    /**
     * Setujui NPI Perjaldin
     */
    public function approve(Request $request, $id)
    {
        $user = $request->user();
        $roleCode = $this->activeRoleCode($user);
        abort_unless($roleCode !== '', 403, 'Akses ditolak.');

        $npi = DokumenNpi::with('workflowInstances.approvals')->findOrFail($id);
        $wf = $this->workflowService->getActiveInstance($npi);
        
        abort_unless($wf, 404, 'Tidak ada antrean verifikasi aktif untuk NPI ini.');

        $myApproval = $wf->approvals->where('role_code', $roleCode)->first();
        abort_unless($myApproval && $myApproval->status === 'PENDING', 403, 'Anda tidak memiliki antrean menunggu pada Dokumen NPI ini.');
        
        if ($myApproval->assigned_user_id && $myApproval->assigned_user_id !== $user->id) {
            abort(403, 'Anda bukan verifikator spesifik untuk dokumen ini.');
        }
        if ($roleCode === 'Bendahara Penerimaan' && (int) $npi->bendahara_penerimaan_id !== (int) $user->id) {
            abort(403, 'NPI ini bukan tugas Bendahara Penerimaan Anda.');
        }

        DB::beginTransaction();
        try {
            $catatan = $request->input('catatan', 'Disetujui oleh ' . $this->roleLabel($roleCode));
            
            $this->workflowService->approveCurrentStep($npi, $user->id, $catatan);
            $wf->refresh();

            $isFullyApproved = $wf->status === 'APPROVED';
            
            LogStatusDokumen::create([
                'dokumen_type'      => DokumenNpi::class,
                'dokumen_id'        => $npi->id,
                'user_id'           => $user->id,
                'role_saat_itu'     => $roleCode,
                'status_sebelumnya' => $npi->status,
                'status_baru'       => $isFullyApproved ? DokumenNpi::STATUS_DISETUJUI_FINAL : $npi->status,
                'aksi'              => 'APPROVE_' . strtoupper(str_replace(' ', '_', $roleCode)),
                'catatan'           => $catatan,
                'ip_address'        => $request->ip(),
            ]);

            if ($isFullyApproved && $npi->status !== DokumenNpi::STATUS_DISETUJUI_FINAL) {
                $npi->update(['status' => DokumenNpi::STATUS_DISETUJUI_FINAL]);

                // Notifikasi Final
                $pengeluaran = User::role('Bendahara Pengeluaran')->get();
                Notification::send($pengeluaran, new WorkflowNotification([
                    'title'   => 'NPI Perjaldin Disetujui Final',
                    'message' => "NPI {$npi->nomor_npi} telah diverifikasi sepenuhnya dan siap diproses lebih lanjut.",
                    'url'     => route('npis.perjaldin.detail', $npi->spm_id),
                    'icon'    => 'verified',
                    'color'   => 'success',
                ]));
            }

            DB::commit();
            return back()->with('success', 'Berhasil menyetujui dokumen NPI Perjaldin.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    /**
     * Tolak/Revisi NPI Perjaldin
     */
    public function reject(Request $request, $id)
    {
        $user = $request->user();
        $roleCode = $this->activeRoleCode($user);
        abort_unless($roleCode !== '', 403, 'Akses ditolak.');

        $request->validate([
            'catatan' => 'required|string|min:5|max:1000'
        ]);

        $npi = DokumenNpi::with('workflowInstances.approvals')->findOrFail($id);
        $wf = $this->workflowService->getActiveInstance($npi);
        
        abort_unless($wf, 404, 'Tidak ada antrean verifikasi aktif untuk NPI ini.');

        $myApproval = $wf->approvals->where('role_code', $roleCode)->first();
        abort_unless($myApproval && $myApproval->status === 'PENDING', 403, 'Anda tidak memiliki antrean menunggu pada Dokumen NPI ini.');
        if ($myApproval->assigned_user_id && $myApproval->assigned_user_id !== $user->id) {
            abort(403, 'Anda bukan verifikator spesifik untuk dokumen ini.');
        }
        if ($roleCode === 'Bendahara Penerimaan' && (int) $npi->bendahara_penerimaan_id !== (int) $user->id) {
            abort(403, 'NPI ini bukan tugas Bendahara Penerimaan Anda.');
        }

        DB::beginTransaction();
        try {
            $this->workflowService->requestRevision($npi, $user->id, $request->catatan);
            $wf->refresh();

            LogStatusDokumen::create([
                'dokumen_type'      => DokumenNpi::class,
                'dokumen_id'        => $npi->id,
                'user_id'           => $user->id,
                'role_saat_itu'     => $roleCode,
                'status_sebelumnya' => $npi->status,
                'status_baru'       => DokumenNpi::STATUS_REVISI,
                'aksi'              => 'REJECT_' . strtoupper(str_replace(' ', '_', $roleCode)),
                'catatan'           => $request->catatan,
                'ip_address'        => $request->ip(),
            ]);

            if ($npi->status !== DokumenNpi::STATUS_REVISI) {
                $npi->update(['status' => DokumenNpi::STATUS_REVISI]);

                // Notifikasi ke pembuat draft
                $pengeluaran = User::role('Bendahara Pengeluaran')->get();
                Notification::send($pengeluaran, new WorkflowNotification([
                    'title'   => 'NPI Perjaldin Revisi',
                    'message' => "NPI {$npi->nomor_npi} dikembalikan oleh {$roleCode} dengan catatan: {$request->catatan}",
                    'url'     => route('npis.perjaldin.detail', $npi->spm_id),
                    'icon'    => 'error_outline',
                    'color'   => 'danger',
                ]));
            }

            DB::commit();
            return back()->with('warning', 'Dokumen NPI Perjaldin berhasil direvisi/ditolak.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }
}
