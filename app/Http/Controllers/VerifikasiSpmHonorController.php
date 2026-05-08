<?php

namespace App\Http\Controllers;

use App\Models\DokumenSpm;
use App\Models\LogStatusDokumen;
use App\Models\WorkflowApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class VerifikasiSpmHonorController extends Controller
{
    /**
     * Dapatkan Role Aktif yang dipakai user untuk memfilter antrean
     */
    private function activeRoleCodes($user): array
    {
        $roles = [];
        if ($user->hasRole('PPSPM')) {
            $roles[] = 'PPSPM';
        }
        if ($user->hasRole('Kepala Subbagian Keuangan dan Tata Usaha')) {
            $roles[] = 'Kepala Subbagian Keuangan dan Tata Usaha';
        }
        if ($user->hasRole('Koordinator Keuangan')) {
            $roles[] = 'Koordinator Keuangan';
        }
        return $roles;
    }

    /**
     * Tampilkan Halaman Daftar Antrean Verifikasi SPM Honorarium
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $roleCodes = $this->activeRoleCodes($user);

        if (empty($roleCodes)) {
            abort(403, 'Akses ditolak. Anda bukan Verifikator SPM.');
        }

        $query = DokumenSpm::whereHas('spp.tagihan', function ($q) {
                $q->where('tipe_tagihan', 'HONORARIUM');
            })
            ->whereHas('workflowInstances', function ($query) use ($roleCodes, $user) {
                $query->where('status', '!=', 'DRAFT')
                      ->whereHas('approvals', function ($sub) use ($roleCodes, $user) {
                          $sub->whereIn('role_code', $roleCodes)
                              ->where(function ($q) use ($user) {
                                  $q->whereNull('assigned_user_id')
                                    ->orWhere('assigned_user_id', $user->id);
                              });
                      });
            })
            ->with([
                'spp.tagihan.detailHonorarium',
                'dipaRevisionItem.coa',
                'spp.ppkVerifikator',
                'workflowInstances' => function($q) {
                    $q->latest()->limit(1)->with(['approvals']);
                }
            ]);

        // Pencarian (Search)
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_spm', 'like', "%{$search}%")
                  ->orWhereHas('spp', fn ($sq) => $sq->where('nomor_spp', 'like', "%{$search}%"))
                  ->orWhereHas('spp.tagihan', function ($sq) use ($search) {
                      $sq->where('nomor_tagihan', 'like', "%{$search}%")
                         ->orWhere('deskripsi', 'like', "%{$search}%");
                  });
            });
        }

        // Summary Statistics (Tanpa filter Status)
        $allSpms = $query->get();

        $summary = [
            'pending' => 0,
            'approved' => 0,
            'revisi' => 0,
            'selesai' => 0,
        ];

        foreach ($allSpms as $spm) {
            $instance = $spm->workflowInstances->first();
            
            $myApprovals = collect($instance?->approvals ?? [])->filter(function($app) use ($roleCodes, $user) {
                if (!in_array($app->role_code, $roleCodes)) return false;
                if ($app->role_code === 'PPK' && $app->assigned_user_id !== $user->id) return false;
                return true;
            });
            
            if (in_array($spm->status, [DokumenSpm::STATUS_DISETUJUI_FINAL, DokumenSpm::STATUS_MENUNGGU_UPLOAD, DokumenSpm::STATUS_SPM_TERBIT])) {
                $summary['selesai']++;
            } elseif ($myApprovals->contains('status', 'PENDING')) {
                $summary['pending']++;
            } elseif ($myApprovals->contains('status', 'REVISION') || $myApprovals->contains('status', 'REJECTED')) {
                $summary['revisi']++;
            } elseif ($myApprovals->contains('status', 'APPROVED')) {
                $summary['approved']++;
            }
        }

        // Filter Status Dashboard
        $statusFilter = $request->input('status', 'semua');
        if ($statusFilter !== 'semua') {
            $query->whereHas('workflowInstances', function ($wf) use ($roleCodes, $user, $statusFilter) {
                $wf->whereHas('approvals', function ($app) use ($roleCodes, $user, $statusFilter) {
                    $app->whereIn('role_code', $roleCodes)
                        ->where(function ($q) use ($user) {
                            $q->whereNull('assigned_user_id')->orWhere('assigned_user_id', $user->id);
                        });
                        
                    if ($statusFilter === 'pending') {
                        $app->where('status', 'PENDING');
                    } elseif ($statusFilter === 'approved') {
                        $app->where('status', 'APPROVED');
                    } elseif ($statusFilter === 'revisi') {
                        $app->where('status', 'REVISION');
                    }
                });
            });

            if ($statusFilter === 'selesai') {
                 $query->whereIn('status', [DokumenSpm::STATUS_DISETUJUI_FINAL, DokumenSpm::STATUS_MENUNGGU_UPLOAD, DokumenSpm::STATUS_SPM_TERBIT]);
            } elseif ($statusFilter === 'pending') {
                 $query->whereNotIn('status', [DokumenSpm::STATUS_DISETUJUI_FINAL, DokumenSpm::STATUS_MENUNGGU_UPLOAD, DokumenSpm::STATUS_SPM_TERBIT]);
            }
        }

        // Sort: Pending milikku diprioritaskan
        $spmList = $query->latest()->get()->sortByDesc(function($spm) use ($roleCodes) {
            $instance = $spm->workflowInstances->first();
            $myApprovals = collect($instance?->approvals ?? [])->whereIn('role_code', $roleCodes);
            return $myApprovals->contains('status', 'PENDING') ? 1 : 0;
        })->values();

        return view('verifikasi-spm.honor_index', compact(
            'spmList', 'summary', 'statusFilter', 'search', 'roleCodes'
        ));
    }

    /**
     * Halaman Workspace Detail SPM Honorarium
     */
    public function show($spm_id)
    {
        $roleCode = $this->getActiveRoleCode();
        $user = Auth::user();

        $spmModel = DokumenSpm::with([
            'spp',
            'spp.tagihan.detailHonorarium',
            'spp.tagihan.arsipDokumen',
            'spp.dibuatOleh',
            'spp.ppkVerifikator',
            'dipaRevisionItem.coa',
            'dipaRevisionItem.dipaRevision.masterDipa',
            'workflowInstances.approvals.assignedUser',
            'workflowInstances.approvals.actedByUser',
            'logs.user'
        ])->findOrFail($spm_id);

        $sppModel = $spmModel->spp;
        $tagihan = $sppModel->tagihan;
        $dipa = $spmModel->dipaRevisionItem?->dipaRevision?->masterDipa;
        $selectedBudgetItem = $spmModel->dipaRevisionItem;
        
        $nominalSpm = (float) ($spmModel->nominal_spm ?? 0);

        // Dokumen pendukung
        $arsipJenis = $tagihan->arsipDokumen->pluck('jenis_dokumen')->toArray();
        $dokumenWajib = ['SK Honorarium', 'Daftar Nominatif Bertandatangan', 'Dokumen Honorarium Bertandatangan'];
        
        $documentStatuses = collect($dokumenWajib)->map(function ($jenis) use ($tagihan, $arsipJenis) {
            $doc = $tagihan->arsipDokumen->firstWhere('jenis_dokumen', $jenis);
            return [
                'label' => $jenis,
                'path' => $doc?->file_path,
                'status' => in_array($jenis, $arsipJenis) ? 'ready' : 'missing',
                'is_available' => in_array($jenis, $arsipJenis)
            ];
        });

        // Readiness checklist Verifikasi
        $semuaPunyaRekening = $tagihan->detailHonorarium->every(fn($item) => filled($item->rekening) && filled($item->nama_rekening));
        
        $readinessChecklist = [
            ['label' => 'SPM Lengkap dari Operator BLU', 'status' => in_array($spmModel->status, [DokumenSpm::STATUS_MENUNGGU_VERIFIKASI]) ? 'ready' : 'ready'],
            ['label' => 'Honorarium Asli Valid', 'status' => 'ready'],
            ['label' => 'Detail Rekening Personel Valid', 'status' => $semuaPunyaRekening ? 'ready' : 'missing'],
            ['label' => 'Dokumen Fisik Wajib Ada', 'status' => collect($documentStatuses)->every(fn($d) => $d['status'] === 'ready') ? 'ready' : 'missing'],
            ['label' => 'Akun COA Anggaran Valid', 'status' => filled($selectedBudgetItem?->coa) ? 'ready' : 'missing'],
        ];

        // Workflow & Approval Status
        $workflowInstance = $spmModel->workflowInstances->sortByDesc('created_at')->first();
        $baseApprovals = $workflowInstance?->approvals ?? collect();

        $ppspmApproval = $baseApprovals->firstWhere('role_code', 'PPSPM');
        $kasubbagApproval = $baseApprovals->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');
        $koordinatorApproval = $baseApprovals->firstWhere('role_code', 'Koordinator Keuangan');
        
        $myApproval = $baseApprovals->firstWhere('role_code', $roleCode);
        
        // Cek jika approval ditujukan pada saya secara spesifik
        $isMyPendingApproval = false;
        if ($myApproval && $myApproval->status === 'PENDING') {
            if (empty($myApproval->assigned_user_id) || $myApproval->assigned_user_id == $user->id) {
                $isMyPendingApproval = true;
            }
        }

        // Timeline Workflow SPM Honorarium
        $activities = $spmModel->logs()->with('user')->orderBy('created_at', 'desc')->get();

        return view('verifikasi-spm.honor_detail', compact(
            'spmModel', 'sppModel', 'tagihan', 'dipa', 'selectedBudgetItem',
            'nominalSpm', 'documentStatuses', 'readinessChecklist',
            'workflowInstance', 'ppspmApproval', 'kasubbagApproval',
            'isMyPendingApproval', 'activities', 'roleCodes', 'activeRoleApprovals', 'koordinatorApproval'
        ));
    }

    /**
     * Aksi Persetujuan Verifikasi
     */
    public function approve(Request $request, $spm_id)
    {
        $roleCode = $this->getActiveRoleCode();
        if (!$roleCode) abort(403, 'Akses terbatas.');

        $spm = DokumenSpm::with(['workflowInstances.approvals', 'spp.tagihan'])->findOrFail($spm_id);
        $user = Auth::user();

        $instance = $spm->workflowInstances->sortByDesc('created_at')->first();
        if (!$instance) return back()->withErrors('Workflow tidak ditemukan.');

        $myApproval = collect($instance->approvals)->firstWhere('role_code', $roleCode);
        
        if (!$myApproval || $myApproval->status !== 'PENDING') {
            return back()->withErrors('Dokumen tidak memerlukan tindakan persetujuan Anda saat ini.');
        }

        DB::transaction(function() use ($spm, $instance, $myApproval, $user, $request, $roleCode) {
            // 1. Assign dan Approve
            $myApproval->update([
                'status' => 'APPROVED',
                'acted_by_user_id' => $user->id,
                'acted_at' => now(),
                'catatan' => $request->input('catatan', 'Disetujui oleh ' . $roleCode),
            ]);

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSpm::class,
                'dokumen_id' => $spm->id,
                'user_id' => $user->id,
                'role_saat_itu' => $roleCode,
                'status_sebelumnya' => $spm->status,
                'status_baru' => $spm->status,
                'aksi' => 'APPROVE_SPM_' . str_replace(' ', '_', strtoupper($roleCode)),
                'catatan' => $myApproval->catatan,
                'ip_address' => request()->ip()
            ]);

            // 2. Cek apakah ini penyelesaian final Workflow
            // Jika ada status approval dalam instance ini yang ditolak, workflow sudah berantakan.
            // Jika semua REQUIRED role terlah APPROVED, maka SPM DISETUJUI.
            $allRequiredApprovals = collect($instance->approvals);
            $semuaDisetujui = $allRequiredApprovals->every(fn($a) => $a->status === 'APPROVED');
            $adaRevisi = $allRequiredApprovals->contains(fn($a) => in_array($a->status, ['REVISION', 'REJECTED']));

            if ($adaRevisi) {
                // Do nothing, biar yang nolak aja yg update main status SPM
            } elseif ($semuaDisetujui) {
                $statusLama = $spm->status;
                $spm->update(['status' => DokumenSpm::STATUS_MENUNGGU_UPLOAD]);
                $instance->update(['status' => 'APPROVED']);

                LogStatusDokumen::create([
                    'dokumen_type' => DokumenSpm::class,
                    'dokumen_id' => $spm->id,
                    'user_id' => $user->id,
                    'role_saat_itu' => 'Sistem Verifikasi',
                    'status_sebelumnya' => $statusLama,
                    'status_baru' => DokumenSpm::STATUS_MENUNGGU_UPLOAD,
                    'aksi' => 'SPM_MENUNGGU_UPLOAD',
                    'catatan' => 'Verifikasi SPM diotorisasi sepenuhnya. Menunggu operator upload dokumen SPM.',
                    'ip_address' => request()->ip()
                ]);
            }
        });

        return redirect()->route('verifikasi-spm.honor.detail', $spm->id)->with('success', 'Dokumen SPM Honorarium berhasil Anda Setujui.');
    }

    /**
     * Aksi Revisi / Pengembalian Verifikasi
     */
    public function reject(Request $request, $spm_id)
    {
        $request->validate([
            'catatan' => 'required|string|min:5'
        ], [
            'catatan.required' => 'Catatan revisi WAJIB diisi dengan jelas.',
        ]);

        $roleCode = $this->getActiveRoleCode();
        if (!$roleCode) abort(403, 'Akses terbatas.');

        $spm = DokumenSpm::with(['workflowInstances.approvals'])->findOrFail($spm_id);
        $user = Auth::user();

        $instance = $spm->workflowInstances->sortByDesc('created_at')->first();
        if (!$instance) return back()->withErrors('Workflow tidak ditemukan.');

        $myApproval = collect($instance->approvals)->firstWhere('role_code', $roleCode);
        
        if (!$myApproval || $myApproval->status !== 'PENDING') {
            return back()->withErrors('Dokumen tidak dapat di-revisi karena bukan dalam antrean Anda.');
        }

        DB::transaction(function() use ($spm, $instance, $myApproval, $user, $request, $roleCode) {
            // 1. Tolak Approval Workflow
            $myApproval->update([
                'status' => 'REVISION',
                'acted_by_user_id' => $user->id,
                'acted_at' => now(),
                'catatan' => $request->input('catatan'),
            ]);
            
            $instance->update(['status' => 'REVISION']);

            // 2. Turunkan kasta SPM ke REVISI
            $statusLama = $spm->status;
            $spm->update([
                'status' => DokumenSpm::STATUS_REVISI,
                'catatan_revisi' => "Revisi dari {$roleCode}: " . $request->input('catatan')
            ]);

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSpm::class,
                'dokumen_id' => $spm->id,
                'user_id' => $user->id,
                'role_saat_itu' => $roleCode,
                'status_sebelumnya' => $statusLama,
                'status_baru' => DokumenSpm::STATUS_REVISI,
                'aksi' => 'KEMBALIKAN_REVISI_SPM',
                'catatan' => $request->input('catatan'),
                'ip_address' => request()->ip()
            ]);
        });

        return redirect()->route('verifikasi-spm.honor.detail', $spm->id)->with('success', 'Dokumen SPM Honorarium berhasil dikembalikan untuk revisi ke Operator BLU.');
    }
}
