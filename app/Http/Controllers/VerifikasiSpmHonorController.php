<?php

namespace App\Http\Controllers;

use App\Models\DokumenSpm;
use App\Models\LogStatusDokumen;
use App\Models\User;
use App\Models\WorkflowApproval;
use App\Notifications\WorkflowNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

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
     * Resolusi approval milik user (mendukung user multi-role).
     * Utamakan approval sesuai approval_id yang diposting, lalu fallback ke
     * approval PENDING pertama yang sesuai dengan role & kepemilikan user.
     */
    private function resolveMyApproval($instance, $user, $approvalId = null)
    {
        $roleCodes = $this->activeRoleCodes($user);
        $approvals = collect($instance->approvals ?? []);

        if ($approvalId) {
            $byId = $approvals->first(function ($a) use ($approvalId, $roleCodes) {
                return (int) $a->id === (int) $approvalId
                    && in_array($a->role_code, $roleCodes, true);
            });
            if ($byId) return $byId;
        }

        return $approvals->first(function ($a) use ($roleCodes, $user) {
            return in_array($a->role_code, $roleCodes, true)
                && $a->status === 'PENDING'
                && (empty($a->assigned_user_id) || (int) $a->assigned_user_id === (int) $user->id);
        });
    }

    /**
     * Beritahu Bendahara Pengeluaran bahwa SPM telah terbit ber-TTE dan
     * NPI Honorarium dapat segera dibuat.
     */
    private function notifyBendaharaPengeluaranNpiSiap(DokumenSpm $spm): void
    {
        $bendahara = User::role('Bendahara Pengeluaran')->get();
        if ($bendahara->isEmpty()) {
            return;
        }

        Notification::send($bendahara, new WorkflowNotification([
            'title' => 'SPM Honorarium Terbit — NPI Siap Dibuat',
            'message' => "SPM {$spm->nomor_spm} telah terbit ber-TTE. NPI Honorarium dapat segera Anda buat.",
            'url' => route('npis.honor.index'),
            'icon' => 'receipt_long',
            'color' => 'success',
        ]));
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
        $user = Auth::user();
        $roleCodes = $this->activeRoleCodes($user);
        if (empty($roleCodes)) {
            abort(403, 'Akses ditolak. Anda bukan Verifikator SPM.');
        }

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

        // Dokumen pendukung: SK tetap dari arsip, nominatif & rekap honorarium
        // diterbitkan otomatis oleh sistem sebagai PDF ber-TTE QR.
        $skHonorarium = $tagihan->arsipDokumen->firstWhere('jenis_dokumen', 'SK Honorarium');
        $skPath = $skHonorarium?->path_file ?? $skHonorarium?->file_path ?? null;

        $documentStatuses = collect([
            [
                'label' => 'SK Honorarium',
                'path' => $skPath,
                'url' => $skPath ? \Illuminate\Support\Facades\Storage::url($skPath) : null,
                'status' => $skPath ? 'ready' : 'missing',
                'is_available' => filled($skPath),
                'is_tte' => false,
            ],
            [
                'label' => 'Daftar Nominatif Honorarium',
                'path' => null,
                'url' => route('honorarium.pdf-nominatif', $tagihan->id),
                'status' => 'tte',
                'is_available' => true,
                'is_tte' => true,
            ],
            [
                'label' => 'Dokumen Honorarium',
                'path' => null,
                'url' => route('honorarium.pdf', $tagihan->id),
                'status' => 'tte',
                'is_available' => true,
                'is_tte' => true,
            ],
        ]);

        // Readiness checklist Verifikasi
        $semuaPunyaRekening = $tagihan->detailHonorarium->every(fn($item) => filled($item->rekening) && filled($item->nama_rekening));
        
        $readinessChecklist = [
            ['label' => 'SPM Lengkap dari Operator BLU', 'status' => in_array($spmModel->status, [DokumenSpm::STATUS_MENUNGGU_VERIFIKASI]) ? 'ready' : 'ready'],
            ['label' => 'Honorarium Asli Valid', 'status' => 'ready'],
            ['label' => 'Detail Rekening Personel Valid', 'status' => $semuaPunyaRekening ? 'ready' : 'missing'],
            ['label' => 'Dokumen Pendukung Dapat Dilihat', 'status' => collect($documentStatuses)->every(fn($d) => in_array($d['status'], ['ready', 'tte'], true)) ? 'ready' : 'missing'],
            ['label' => 'Akun COA Anggaran Valid', 'status' => filled($selectedBudgetItem?->coa) ? 'ready' : 'missing'],
        ];

        // Workflow & Approval Status
        $workflowInstance = $spmModel->workflowInstances->sortByDesc('created_at')->first();
        $baseApprovals = $workflowInstance?->approvals ?? collect();

        $ppspmApproval = $baseApprovals->firstWhere('role_code', 'PPSPM');
        $kasubbagApproval = $baseApprovals->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');
        $koordinatorApproval = $baseApprovals->firstWhere('role_code', 'Koordinator Keuangan');
        
        // Bangun daftar approval aktif milik user (mendukung multi-role)
        $activeRoleApprovals = [];
        foreach ($roleCodes as $rc) {
            $approval = $baseApprovals->firstWhere('role_code', $rc);
            if (!$approval) continue;
            if ($approval->status !== 'PENDING') continue;
            if (!empty($approval->assigned_user_id) && (int) $approval->assigned_user_id !== (int) $user->id) continue;
            if ($workflowInstance && $workflowInstance->status !== 'IN_PROGRESS') continue;

            $activeRoleApprovals[] = [
                'role' => $rc,
                'approval_id' => $approval->id,
                'approveRoute' => route('verifikasi-spm.honor.approve', $spmModel->id),
                'revisiRoute' => route('verifikasi-spm.honor.reject', $spmModel->id),
            ];
        }

        // Approval representatif: utamakan yang PENDING milik user, jika tidak ada ambil pertama yang cocok
        $myApproval = $baseApprovals->first(function ($a) use ($roleCodes, $user) {
            return in_array($a->role_code, $roleCodes, true)
                && $a->status === 'PENDING'
                && (empty($a->assigned_user_id) || (int) $a->assigned_user_id === (int) $user->id);
        }) ?: $baseApprovals->first(fn($a) => in_array($a->role_code, $roleCodes, true));

        $isMyPendingApproval = !empty($activeRoleApprovals);

        // Kelas warna status approval milik user (untuk tampilan)
        $myApprovalClass = match ($myApproval->status ?? null) {
            'APPROVED' => 'text-success',
            'PENDING' => 'text-warning',
            'REVISION', 'REJECTED' => 'text-danger',
            default => 'text-secondary',
        };

        // Timeline Workflow SPM Honorarium
        $activities = $spmModel->logs()->with('user')->orderBy('created_at', 'desc')->get();

        return view('verifikasi-spm.honor_detail', compact(
            'spmModel', 'sppModel', 'tagihan', 'dipa', 'selectedBudgetItem',
            'nominalSpm', 'documentStatuses', 'readinessChecklist',
            'workflowInstance', 'ppspmApproval', 'kasubbagApproval',
            'isMyPendingApproval', 'activities', 'roleCodes', 'activeRoleApprovals', 'koordinatorApproval',
            'myApproval', 'myApprovalClass'
        ));
    }

    /**
     * Aksi Persetujuan Verifikasi
     */
    public function approve(Request $request, $spm_id)
    {
        $user = Auth::user();
        $roleCodes = $this->activeRoleCodes($user);
        if (empty($roleCodes)) abort(403, 'Akses terbatas.');

        $spm = DokumenSpm::with(['workflowInstances.approvals', 'spp.tagihan'])->findOrFail($spm_id);

        $instance = $spm->workflowInstances->sortByDesc('created_at')->first();
        if (!$instance) return back()->withErrors('Workflow tidak ditemukan.');

        $myApproval = $this->resolveMyApproval($instance, $user, $request->input('approval_id'));

        if (!$myApproval || $myApproval->status !== 'PENDING') {
            return back()->withErrors('Dokumen tidak memerlukan tindakan persetujuan Anda saat ini.');
        }

        $roleCode = $myApproval->role_code;

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
                // Verifikasi paralel selesai: SPM langsung TERBIT ber-TTE QR
                // (tanpa upload manual), dan siap dibuatkan NPI oleh Bendahara Pengeluaran.
                $spm->update(['status' => DokumenSpm::STATUS_SPM_TERBIT]);
                $instance->update(['status' => 'APPROVED']);

                LogStatusDokumen::create([
                    'dokumen_type' => DokumenSpm::class,
                    'dokumen_id' => $spm->id,
                    'user_id' => $user->id,
                    'role_saat_itu' => 'Sistem Verifikasi',
                    'status_sebelumnya' => $statusLama,
                    'status_baru' => DokumenSpm::STATUS_SPM_TERBIT,
                    'aksi' => 'SPM_TERBIT_TTE',
                    'catatan' => 'Verifikasi SPM diotorisasi sepenuhnya. SPM terbit ber-TTE QR dan siap dibuatkan NPI.',
                    'ip_address' => request()->ip()
                ]);

                // Beritahu Bendahara Pengeluaran bahwa NPI dapat dibuat
                $this->notifyBendaharaPengeluaranNpiSiap($spm);
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

        $user = Auth::user();
        $roleCodes = $this->activeRoleCodes($user);
        if (empty($roleCodes)) abort(403, 'Akses terbatas.');

        $spm = DokumenSpm::with(['workflowInstances.approvals'])->findOrFail($spm_id);

        $instance = $spm->workflowInstances->sortByDesc('created_at')->first();
        if (!$instance) return back()->withErrors('Workflow tidak ditemukan.');

        $myApproval = $this->resolveMyApproval($instance, $user, $request->input('approval_id'));

        if (!$myApproval || $myApproval->status !== 'PENDING') {
            return back()->withErrors('Dokumen tidak dapat di-revisi karena bukan dalam antrean Anda.');
        }

        $roleCode = $myApproval->role_code;

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
