<?php

namespace App\Http\Controllers;

use App\Models\DokumenSpp;
use App\Models\LogStatusDokumen;
use App\Models\User;
use App\Models\WorkflowApproval;
use App\Models\WorkflowInstance;
use App\Notifications\WorkflowNotification;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class VerifikasiSppHonorController extends Controller
{
    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Memastikan role aktif (PPK atau Kasubbag).
     */
    private function activeRoleCode(User $user): string
    {
        if ($user->hasRole('PPK')) return 'PPK';
        if ($user->hasRole('Koordinator Keuangan')) return 'Koordinator Keuangan';
        if ($user->hasRole('Kepala Subbagian Keuangan dan Tata Usaha')) return 'Kepala Subbagian Keuangan dan Tata Usaha';
        
        return '';
    }

    /**
     * Menampilkan daftar SPP Honorarium untuk verifikasi
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $roleCode = $this->activeRoleCode($user);
        
        abort_unless($roleCode !== '', 403, 'Akses ditolak. Anda tidak memiliki peran verifikator PPK, Koordinator Keuangan, atau Kasubbag.');

        $query = DokumenSpp::with([
            'tagihan.detailHonorarium',
            'tagihan.dipaRevisionItem.coa',
            'dipaRevisionItem.coa',
            'ppkVerifikator',
            'dibuatOleh',
            'workflowInstances' => fn($q) => $q->latest()->limit(1),
            'workflowInstances.approvals'
        ])
        ->whereHas('tagihan', function($q) {
            $q->where('tipe_tagihan', 'HONORARIUM');
        })
        ->whereNotIn('status', ['DRAFT']);

        // Filter berdasarkan Assigned Role/User
        $query->whereHas('workflowInstances', function ($q) use ($roleCode, $user) {
            $q->whereHas('approvals', function ($q2) use ($roleCode, $user) {
                // Untuk PPK periksa target user id-nya
                if ($roleCode === 'PPK') {
                    $q2->where('role_code', 'PPK')
                       ->where('assigned_user_id', $user->id);
                } else {
                    $q2->where('role_code', $roleCode);
                }
            });
        });

        // Terapkan Filter Berdasarkan Input
        $statusFilter = $request->get('status_filter', 'Semua');
        $search = $request->get('search');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nomor_spp', 'like', "%{$search}%")
                  ->orWhereHas('tagihan', function($q2) use ($search) {
                      $q2->where('nomor_tagihan', 'like', "%{$search}%")
                         ->orWhere('deskripsi', 'like', "%{$search}%");
                  })
                  ->orWhereHas('ppkVerifikator', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $allSpps = $query->latest()->get();

        // Categorize tabs
        $listMenunggu = collect();
        $listDisetujui = collect();
        $listRevisi = collect();
        $listSelesai = collect();

        foreach ($allSpps as $spp) {
            $instance = $spp->workflowInstances->first();
            $myApproval = $instance?->approvals->where('role_code', $roleCode)->first();
            if ($myApproval && $myApproval->role_code === 'PPK' && $myApproval->assigned_user_id !== $user->id) {
                $myApproval = null; // PPK assigned_user_id strict check
            }

            if (!$myApproval) continue;

            // Map variables for views
            $spp->my_approval_status = $myApproval->status;

            if ($myApproval->status === 'PENDING') {
                $listMenunggu->push($spp);
            } elseif ($myApproval->status === 'REVISION') {
                $listRevisi->push($spp);
            } elseif ($myApproval->status === 'APPROVED') {
                if (in_array($spp->status, ['DISETUJUI_SPP', 'SPP_TERBIT'])) {
                    $listSelesai->push($spp);
                } else {
                    $listDisetujui->push($spp);
                }
            }
        }

        if ($statusFilter === 'Menunggu Aksi Saya') $filteredSpps = $listMenunggu;
        elseif ($statusFilter === 'Sudah Saya Setujui') $filteredSpps = $listDisetujui;
        elseif ($statusFilter === 'Perlu Revisi') $filteredSpps = $listRevisi;
        elseif ($statusFilter === 'Selesai') $filteredSpps = $listSelesai;
        else $filteredSpps = collect()->merge($listMenunggu)->merge($listRevisi)->merge($listDisetujui)->merge($listSelesai)->sortByDesc('created_at');

        return view('verifikasi-spp.honor.index', compact(
            'filteredSpps',
            'listMenunggu',
            'listDisetujui',
            'listRevisi',
            'listSelesai',
            'statusFilter',
            'roleCode',
            'search'
        ));
    }

    /**
     * Halaman Detail Workspace
     */
    public function detail($spp_id)
    {
        $user = auth()->user();
        $roleCode = $this->activeRoleCode($user);
        abort_unless($roleCode !== '', 403, 'Akses ditolak.');

        $sppModel = DokumenSpp::with([
            'tagihan',
            'tagihan.detailHonorarium',
            'tagihan.arsipDokumen',
            'tagihan.dipaRevisionItem.coa',
            'dibuatOleh',
            'ppkVerifikator',
            'workflowInstances.approvals.actedByUser',
            'logs' => fn($q) => $q->latest()
        ])->findOrFail($spp_id);

        $tagihan = $sppModel->tagihan;
        
        $instance = $sppModel->workflowInstances->first();
        // Cari status approval milik User (strictly on Role & User_ID if PPK)
        $myApproval = $instance?->approvals->where('role_code', $roleCode)->first();
        if ($myApproval && $myApproval->role_code === 'PPK' && $myApproval->assigned_user_id !== $user->id) {
            $myApproval = null;
        }

        $ppkApproval = $instance?->approvals->where('role_code', 'PPK')->first();
        $kasubbagApproval = $instance?->approvals->where('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')->first();
        $koordinatorApproval = $instance?->approvals->where('role_code', 'Koordinator Keuangan')->first();

        $selectedBudgetItem = $sppModel?->dipaRevisionItem ?? \App\Models\DetailDipa::with('coa')->find($tagihan->dipa_revision_item_id);
        
        $dokumenWajib = ['Daftar Nominatif Bertandatangan', 'Dokumen Honorarium Bertandatangan', 'SK Honorarium'];
        $arsipDaftar = $tagihan->arsipDokumen->pluck('jenis_dokumen')->toArray();
        $dokumenLengkap = collect($dokumenWajib)->every(fn($doc) => in_array($doc, $arsipDaftar));

        $semuaPunyaRekening = $tagihan->detailHonorarium->every(fn($item) => filled($item->rekening) && filled($item->nama_rekening));

        $readinessChecklist = collect([
            [
                'label' => 'SPP sudah diajukan',
                'status' => in_array($sppModel->status, ['Menunggu Verifikasi', 'Disetujui PPK', 'DISETUJUI_SPP', 'SPP_TERBIT']) ? 'ready' : 'missing',
            ],
            [
                'label' => 'Detail penerima honorarium tersedia',
                'status' => $tagihan->detailHonorarium->count() > 0 ? 'ready' : 'missing',
            ],
            [
                'label' => 'Rekening tujuan penerima lengkap',
                'status' => $semuaPunyaRekening ? 'ready' : 'missing',
            ],
            [
                'label' => 'Dokumen lampiran terpenuhi',
                'status' => $dokumenLengkap ? 'ready' : 'missing',
            ],
            [
                'label' => 'Nilai Netto Terhindar Match-error',
                'status' => ((float) $sppModel->nominal_spp === (float) $tagihan->total_netto) ? 'ready' : 'missing',
            ]
        ]);

        return view('verifikasi-spp.honor.detail', compact(
            'sppModel',
            'tagihan',
            'roleCode',
            'instance',
            'myApproval',
            'ppkApproval',
            'kasubbagApproval',
            'koordinatorApproval',
            'selectedBudgetItem',
            'readinessChecklist'
        ));
    }

    /**
     * Memproses persetujuan (Approve) SPP
     */
    public function approve(Request $request, $spp_id)
    {
        $user = auth()->user();
        $roleCode = $this->activeRoleCode($user);
        abort_unless($roleCode !== '', 403, 'Akses ditolak.');

        $catatan = $request->input('catatan') ?: 'Disetujui oleh ' . $roleCode;

        $sppModel = DokumenSpp::with(['workflowInstances.approvals'])->findOrFail($spp_id);
        $instance = $sppModel->workflowInstances->first();
        
        $myApproval = $instance?->approvals->where('role_code', $roleCode)->first();
        if (!$myApproval || $myApproval->status !== 'PENDING') {
            return back()->with('error', 'Status verifikasi telah diproses atau tidak valid.');
        }

        DB::transaction(function () use ($sppModel, $instance, $myApproval, $user, $roleCode, $catatan) {
            $statusSebelumnya = $sppModel->status;
            
            // Tandai Approval milik User menjadi APPROVED
            $myApproval->update([
                'status' => 'APPROVED',
                'acted_by_user_id' => $user->id,
                'acted_at' => now(),
                'catatan' => $catatan
            ]);

            // Cek apakah seluruh approver sudah APPROVED
            $allApproved = $instance->approvals->every(fn($app) => $app->status === 'APPROVED');
            
            $statusSppBaru = $sppModel->status;
            if ($allApproved) {
                // Semua Setuju
                $instance->update(['status' => 'APPROVED']);
                $sppModel->update(['status' => 'DISETUJUI_SPP']);
                // Sinkronisasi status sumber/tagihan
                $sppModel->tagihan()->update(['status' => 'SEBAGIAN_SPP_TERBIT']);
                
                $statusSppBaru = 'DISETUJUI_SPP';
            } else {
                // Sebagian Setuju (PPK Setuju tapi Kasubbag Belum)
                if ($roleCode === 'PPK') {
                    $sppModel->update(['status' => 'Disetujui PPK']);
                    $statusSppBaru = 'Disetujui PPK';

                    // Notifikasi Kasubbag
                    $kasubbag = User::role('Kepala Subbagian Keuangan dan Tata Usaha')->first();
                    if ($kasubbag) {
                        Notification::send($kasubbag, new WorkflowNotification([
                            'title' => 'SPP Honorarium Lolos PPK',
                            'message' => "SPP {$sppModel->nomor_spp} membutuhkan verifikasi tahap akhir Kasubbag.",
                            'url' => route('verifikasi-spp.honor.index'),
                            'icon' => 'verified_user',
                            'color' => 'info',
                        ]));
                    }
                }
            }

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSpp::class,
                'dokumen_id' => $sppModel->id,
                'user_id' => $user->id,
                'role_saat_itu' => $roleCode,
                'status_sebelumnya' => $statusSebelumnya,
                'status_baru' => $statusSppBaru,
                'aksi' => 'APPROVE_SPP',
                'catatan' => $catatan,
                'ip_address' => request()->ip(),
            ]);
        });

        return redirect()->route('verifikasi-spp.honor.detail', $spp_id)
            ->with('success', 'Berhasil menyetujui dokumen SPP Honorarium.');
    }

    /**
     * Memproses Pengembalian (Reject/Revisi) SPP
     */
    public function reject(Request $request, $spp_id)
    {
        $request->validate(['catatan' => 'required|string|min:5']);

        $user = auth()->user();
        $roleCode = $this->activeRoleCode($user);
        abort_unless($roleCode !== '', 403, 'Akses ditolak.');

        $catatan = $request->input('catatan');

        $sppModel = DokumenSpp::with(['workflowInstances.approvals'])->findOrFail($spp_id);
        $instance = $sppModel->workflowInstances->first();
        
        $myApproval = $instance?->approvals->where('role_code', $roleCode)->first();
        if (!$myApproval || $myApproval->status !== 'PENDING') {
            return back()->with('error', 'Status verifikasi telah diproses atau tidak valid.');
        }

        DB::transaction(function () use ($sppModel, $instance, $myApproval, $user, $roleCode, $catatan) {
            $statusSebelumnya = $sppModel->status;
            
            // Ubah Approval Jadi REVISION
            $myApproval->update([
                'status' => 'REVISION',
                'acted_by_user_id' => $user->id,
                'acted_at' => now(),
                'catatan' => $catatan
            ]);

            // Instance keseluruhan jadi REVISION
            $instance->update(['status' => 'REVISION']);
            
            // Ubah status SPP
            $sppModel->update(['status' => 'Revisi']);
            
            LogStatusDokumen::create([
                'dokumen_type' => DokumenSpp::class,
                'dokumen_id' => $sppModel->id,
                'user_id' => $user->id,
                'role_saat_itu' => $roleCode,
                'status_sebelumnya' => $statusSebelumnya,
                'status_baru' => 'Revisi',
                'aksi' => 'REJECT_SPP',
                'catatan' => $catatan,
                'ip_address' => request()->ip(),
            ]);
            
            // Notifikasi kembali ke pembuat (Operator BLU)
            if ($sppModel->dibuatOleh) {
                Notification::send($sppModel->dibuatOleh, new WorkflowNotification([
                    'title' => 'Revisi SPP Honorarium',
                    'message' => "SPP Honorarium ({$sppModel->nomor_spp}) dikembalikan. Catatan: {$catatan}",
                    'url' => route('spps.honor.detail', $sppModel->tagihan_id),
                    'icon' => 'error_outline',
                    'color' => 'danger',
                ]));
            }
        });

        return redirect()->route('verifikasi-spp.honor.detail', $spp_id)
            ->with('success', 'Dokumen SPP Honorarium telah dikembalikan untuk revisi.');
    }
}
